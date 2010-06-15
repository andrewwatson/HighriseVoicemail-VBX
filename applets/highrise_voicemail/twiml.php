<?php
include('plugins/HighriseVoicemail-VBX/highrise.php');

$CI =& get_instance();
$status = @$_REQUEST['status'];
$flow = @AppletInstance::getFlow();
$flow_id = $flow->id;
$instance_id = AppletInstance::getInstanceId();
$dial_target = AppletInstance::getUserGroupPickerValue('dial-target'); // get the prompt that the user configured
$highrise_vm_user = PluginData::get('highrise_vm_user');

$response = new Response(); // start a new Twiml response

// Finish this up by transcribing
if(!empty($_REQUEST['TranscriptionText'])) {
    define('HIGHRISE_URL', $highrise_vm_user->url);
    define('HIGHRISE_TOKEN', $highrise_vm_user->token);
    define('HIGHRISE_PASSWORD', $highrise_vm_user->password);
    define('HIGHRISE_TIMEZONE', (int) $highrise_vm_user->timezone);

    $chk_people = highrise_client('/people/search.xml?criteria[phone]='.$_REQUEST['Caller']);

    // Person found
    if(!empty($chk_people->person)) {
        if(is_array($chk_people->person)) {
            foreach($chk_people->person as $person) {
                $new_note = highrise_phone_call($person->id, $_REQUEST);
            }
        } else {
            $new_note = highrise_phone_call($chk_people->person->id, $_REQUEST);
        }
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

	// add a <Record>, and use VBX's default transcription handle$response->addRecord(array('transcribe'=>'TRUE', 'transcribeCallback' => site_url('/twiml/transcribe') ));
    $action_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=save-call";
	$transcribe_url = base_url()."twiml/applet/voice/{$flow_id}/{$instance_id}?status=transcribe-call";
    $response->addRecord(array(
        'transcribe'=>'TRUE', 
        // 'action' => $action_url,
        'transcribeCallback' => $transcribe_url 
    ));
}

$response->Respond(); // send response
