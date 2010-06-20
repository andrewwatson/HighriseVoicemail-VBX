<?php
/*
    Function: highrise_client
        Client for Highrise CRM
{{{
    Parameters:
        $path - path of API and starts with /
        $method - GET, POST, PUT, DELETE
        $params - parameters to send to Highrise
}}} */
function highrise_client($path, $method='GET', $xml = '') 
{ // {{{
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => HIGHRISE_URL.$path,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HEADER => FALSE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_USERPWD => HIGHRISE_TOKEN.':X',
        CURLOPT_RETURNTRANSFER => TRUE
    ));

    switch($method) {
        case 'GET':
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
            break;

        case 'POST':
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            break;

        case 'PUT':
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;

        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

        default:
            return FALSE;
    }

    $results = curl_exec($ch);
    $ch_info = curl_getinfo($ch);
    $ch_error = curl_error($ch);

    if($ch_error) {
        error_log('[highrise_client] CURL failed due to '.$ch_error);
        return FALSE;
    } else {
        if($ch_info['http_code'] >= 200 && $ch_info['http_code'] <= 300) {
            if(!empty($results) && $obj = simplexml_load_string($results)) {
                if(is_object($obj)) return $obj;

                return TRUE;
            } else {
                return $ch_info;
            }
        } else {
            return $results;
        }
    }

    return FALSE;
} // }}}

/*
    Function: highrise_new_note
        Creates a new note
{{{
    Parameters:
        $data - Data for the new note
            subject_id - Id of the subject
            subject_type - "Party", "Deal", or "Kase"
            body - Body of the note
}}} */
function highrise_new_note($data) 
{ // {{{
    $xml = '<note>';
    if($data['body']) $xml .= '<body>'.$data['body'].'</body>';
    if($data['subject_id']) $xml .= '<subject-id>'.$data['subject_id'].'</subject-id>';
    if($data['subject_type']) $xml .= '<subject-type>'.$data['subject_type'].'</subject-type>';
    $xml .= '</note>';

    $new_note = highrise_client('/notes.xml', 'POST', $xml);
    return $new_note;
} // }}}

/*
    Function: highrise_new_person
        Creates a new person
{{{
    Parameters:
        $data - (array) Data to create new person
            first_name - (string) - First name
            last_name - (string) - Last name
            title - (string) - Title at company
            company_name - (string) - Name of company
            background - (string) - Description of this person
            emails - (array) - List of emails for this person
                (string) - Email address. Will default "Work" for location.

                OR

                address
                location
            addresses - (array) - List of addresses for this person
                (string) - Address of this person. ie: 1234 Reading Ln, San Francisco, CA, 94105, United States. Will also default "Work" for location

                OR

                street
                city
                state
                zip
                country
                location - ("Work", "Home", "Other")
            phones - (array) - List of phone numbers for this person
                (string) - (array) Phone number. No parsing. Will also default "Work" for location.

                OR

                number - Phone number.
                location - ("Work", "Mobile", "Fax", "Pager", "Home", "Other") Location.
            ims - (array) - List of instant messenger handles for this person.
                address - (string) Screenname of this user.
                protocol - ("AIM", "MSN", "ICQ", "Jabber", "Yahoo", "Skype", "QQ", "Sametime", "Gadu-Gadu", "Google Talk", "other") Service this IM uses.
                location - ("Work", "Personal", "Other")
            websites - (array) - List of web sites for this person
                (string)

                OR

                url 
                location - ("Work", "Personal", "Other")
}}} */
function highrise_new_person($data) 
{ // {{{
    $xml = '<person>';
    if($data['first_name']) $xml .= '<first-name>'.$data['first_name'].'</first-name>';
    if($data['last_name']) $xml .= '<last-name>'.$data['last_name'].'</last-name>';
    if($data['title']) $xml .= '<title>'.$data['title'].'</title>';
    if($data['company_name']) $xml .= '<company-name>'.$data['company_name'].'</company-name>';
    if($data['background']) $xml .= '<background>'.$data['background'].'</background>';

    $xml .= '<contact-data>';
    if($data['emails'] && is_array($data['emails']))  {
        $xml .= '<email-addresses>';
        foreach($data['emails'] as $email) {
            if(is_string($email)) {
                $xml .=
                    '<email-address>'.
                        '<address>'.$email.'</address>'.
                        '<location>Work</location>'.
                    '</email-address>';
            } else if(is_array($email)) {
                $xml .=
                    '<email-address>'.
                        '<address>'.@$email['address'].'</address>'.
                        ($email['location'] ? $email['location'] : 'Work').
                    '</email-address>';
            }
        }
        $xml .= '</email-addresses>';
    }

    if($data['phones'] && is_array($data['phones'])) {
        $xml .= '<phone-numbers>';
        foreach($data['phones'] as $phone) {
            if(is_string($phone)) {
                $xml .=
                    '<phone-number>'.
                        '<number>'.$phone.'</number>'.
                        '<location>Work</location>'.
                    '</phone-number>';
            } else if(is_array($phone)) {
                $xml .=
                    '<phone-number>'.
                        '<number>'.@$phone['number'].'</number>'.
                        ($phone['location'] ? $phone['location'] : 'Work').
                    '</phone-number>';
            }
        }
        $xml .= '</phone-numbers>';
    }

    if($data['addresses'] && is_array($data['addresses'])) {
        $xml .= '<addresses>';
        foreach($data['addresses'] as $address) {
            if(is_string($address)) {
                $address = explode(',', $address);
                $xml .=
                    '<address>'.
                        '<street>'.$address[0].'</street>'.
                        '<city>'.$address[1].'</city>'.
                        '<state>'.$address[2].'</state>'.
                        '<zip>'.$address[3].'</zip>'.
                        '<country>'.($address[4] ? $address[4] : 'United States').'</country>'.
                        '<location>Work</location>'.
                    '</address>';
            } else if(is_array($address)) {
                $xml .=
                    '<address>'.
                        '<street>'.$address['street'].'</street>'.
                        '<city>'.$address['city'].'</city>'.
                        '<state>'.$address['state'].'</state>'.
                        '<zip>'.$address['zip'].'</zip>'.
                        '<country>'.($address['country'] ? $address['country'] : 'United States').'</country>'.
                        '<location>'.($address['location'] ? $address['location'] : 'Work').'<location>'.
                    '</address>';
            }
        }
        $xml .= '</addresses>';
    }

    if($data['websites'] && is_array($data['websites'])) {
        $xml .= '<web-addresses>';
        foreach($data['websites'] as $website) {
            if(is_string($website)) {
                $xml .= 
                    '<web-address>'.
                        '<url>'.$website.'</url>'.
                        '<location>Work</location>'.
                    '</web-address>';
            } else if(is_array($website)) {
                $xml .= 
                    '<web-address>'.
                        '<url>'.$website['url'].'</url>'.
                        '<location>'.($website['location'] ? $website['location'] : 'Work').'</location>'.
                    '</web-address>';
            }
        }
        $xml .= '</web-addresses>';
    }

    if($data['ims'] && is_array($data['ims'])) {
        $xml = '<instant-messengers>';
        foreach($data['ims'] as $im) {
            if(is_array($im)) {
                $xml .= 
                    '<instant-messenger>'.
                        '<address>'.$im['address'].'</address>'.
                        '<protocol>'.$im['protocol'].'</protocol>'.
                        '<location>'.($im['location'] ? $im['location'] : '').'</location>'.
                    '<instant-messenger>';
            }
        }
        $xml .= '</instant-messengers>';
    }

    $xml .= '</contact-data>';
    $xml .= '</person>';

    $new_person = highrise_client('/people.xml', 'POST', $xml);
    return $new_person;
} // }}}

/*
    Function: highrise_new_task
        Creates a new task
{{{
    Parameters:
        $data - (array) Data to create a new task
            subject_id - (int) Id of the subject
            subject_type - (string) "Party", "Deal", or "Kase"
            body - (string) Body of the task
            frame - (string) "today", "tomorrow", "this_week", "next_week", or "later"
}}} */
function highrise_new_task($data) 
{ // {{{
    $xml = '<task>'; 
    if($data['body']) $xml .= '<body>'.$data['body'].'</body>';
    if($data['frame']) $xml .= '<frame>'.$data['frame'].'</frame>';
    if($data['subject_id']) $xml .= '<subject-id>'.$data['subject_id'].'</subject-id>';
    if($data['subject_type']) $xml .= '<subject-type>'.$data['subject_type'].'</subject-type>';
    if($data['recording_id']) $xml .= '<recording-id>'.$data['recording_id'].'</recording-id>';
    $xml .= '</task>';

    $new_task = highrise_client('/tasks.xml', 'POST', $xml);
    return $new_task;
} // }}}
?>
