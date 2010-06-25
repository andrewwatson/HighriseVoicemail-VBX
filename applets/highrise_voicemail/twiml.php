<?php
$CI =& get_instance();
$plugin = OpenVBX::$currentPlugin;
$plugin = $plugin->getInfo();
$plugin_url = base_url().'plugins/'.$plugin['dir_name'];
$status = @$_REQUEST['status'];
$flow = @AppletInstance::getFlow();
$flow_id = $flow->id;
$instance_id = AppletInstance::getInstanceId();
$dial_target = AppletInstance::getUserGroupPickerValue('dial-target'); // get the prompt that the user configured
$highrise_vm_user = PluginData::get('highrise_vm_user');

include($plugin['plugin_path'].'/highrise.php');

$response = new Response(); // start a new Twiml response

// Finish this up by transcribing
if(!empty($_REQUEST['TranscriptionText'])) {
    define('HIGHRISE_URL', $highrise_vm_user->url);
    define('HIGHRISE_TOKEN', $highrise_vm_user->token);
    define('HIGHRISE_PASSWORD', $highrise_vm_user->password);
    define('HIGHRISE_TIMEZONE', (int) $highrise_vm_user->timezone);

    // Note body
    $body = 
        'New Voicemail from '.format_phone($_REQUEST['Caller']).' on '.gmdate('M d g:i a', gmmktime() + (60*60*HIGHRISE_TIMEZONE)).":\n".
        '"'.$_REQUEST['TranscriptionText']."\"\n".
        $_REQUEST['RecordingUrl'].'.mp3';

    $chk_people = highrise_client('/people/search.xml?criteria[phone]='.$_REQUEST['Caller']);

    // Person found
    if(!empty($chk_people->person)) {
        if(is_array($chk_people->person)) {
            foreach($chk_people->person as $person) {
                $new_note = highrise_new_note(array( 'body'=>$body, 'subject_type'=>'Party', 'subject_id'=>$person->id ));
                highrise_new_task(array(
                    'body' => 'Call back '.format_phone($_REQUEST['Caller'].' back').' - '.gmdate('M d g:i a', gmmktime() + (60*60*HIGHRISE_TIMEZONE)),
                    'frame' => 'today',
                    'recording_id' => $new_note->id
                ));
            }
        } else {
            $new_note = highrise_new_note(array( 
                'body' => $body, 
                'subject_type' => 'Party', 
                'subject_id' => $chk_people->person->id 
            ));
            highrise_new_task(array(
                'body' => 'Call back '.format_phone($_REQUEST['Caller'].' back').' - '.gmdate('M d g:i a', gmmktime() + (60*60*HIGHRISE_TIMEZONE)),
                'frame' => 'today',
                'recording_id' => $new_note->id
            ));
        }

    // If person is not found, create a new contact
    } else {
        $new_person = highrise_new_person(array(
            'first_name' => 'New OpenVBX',
            'last_name' => format_phone($_REQUEST['Caller']),
            'background' => 'New contact created by OpenVBX on '.gmdate('M d g:i a', gmmktime() + (60*60*HIGHRISE_TIMEZONE)),
            'phones' => array($_REQUEST['Caller'])
        ));

        $new_note = highrise_new_note(array( 'body'=>$body, 'subject_type'=>'Party', 'subject_id'=>$new_person->id ), $_REQUEST);
        highrise_new_task(array(
            'body' => 'Call back '.format_phone($_REQUEST['Caller'].' back').' - '.gmdate('M d g:i a', gmmktime() + (60*60*HIGHRISE_TIMEZONE)),
            'frame' => 'today',
            'recording_id' => $new_note->id
        ));
    }

    $params = http_build_query($_REQUEST);
    $redirect_url = site_url('twiml/transcribe').'?'.$params;
    header("Location: $redirect_url");

// Save the call to db
} else if(!empty($_REQUEST['RecordingUrl'])) {
	// add a voice message 
	OpenVBX::addVoiceMessage(
        AppletInstance::getUserGroupPickerValue('dial-target'),
        $_REQUEST['CallSid'],
        $_REQUEST['Caller'],
        $_REQUEST['Called'], 
        $_REQUEST['RecordingUrl'],
        $_REQUEST['Duration']
    );		

// First time going to this instance of flow
} else {
	$isUser = $dial_target instanceOf VBX_User? TRUE : FALSE;

	if($isUser) $prompt = $dial_target->voicemail;
	else $prompt = AppletInstance::getAudioSpeechPickerValue('prompt');

	$verb = AudioSpeechPickerWidget::getVerbForValue($prompt, new Say("Please leave a message."));
	$response->append($verb);

	// add a <Record>, and use VBX's default transcription handle$response->addRecord(array('transcribe'=>'TRUE', 'transcribeCallback'=>site_url('/twiml/transcribe') ));
    $action_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=save-call";
	$transcribe_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=transcribe-call";
    $response->addRecord(array(
        'transcribe'=>'TRUE', 
        // 'action'=>$action_url,
        'transcribeCallback'=>$transcribe_url 
    ));
}

$response->Respond(); // send response
