<?php
//zendesk authentication
$zendesk_user = 'username';  
$zendesk_pass = 'password';  
$zendesk_subdomain = 'subdomain'; //look in your zendesk address [subdomain].zendesk.com.  Don't use your fancy http://support.mycompany.com here.

//Mantis config
$mantis_user = 'username';
$mantis_pass = 'password';
$mantis_project = 1;  //which Mantis project will zendesk post to 
$mantis_url = 'url'; //what is the root address of mantis

//////////////
//End config
//////////////
//get the id, which was passed in via the Zendesk trigger
$id = $_GET['id'];

//get info about ticket from Zendesk
$json_url = 'https://' . $zendesk_subdomain . '.zendesk.com/api/v2/tickets/' . $id . '.json';
 
// Initializing curl
$ch = curl_init( $json_url );
 
// Configuring curl options
$options = array(
CURLOPT_RETURNTRANSFER => true,
CURLOPT_USERPWD => $zendesk_user . ":" . $zendesk_pass,   // authentication
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_VERBOSE => true
);

// Setting curl options
curl_setopt_array( $ch, $options );

try {
    $result =  curl_exec($ch); // Getting jSON result string
}
 catch (Exception $e) {
        echo "Exception occured: " . $e;
    }

$ticketInfo = json_decode($result);
 
 //create mantis record
 try {
        $wsdl_url = $mantis_url . '/api/soap/mantisconnect.php?wsdl';
        $client = new SOAPClient($wsdl_url, array('trace' => 1, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
        
        //Uses project number in mantis that was put into the top
        //returns issue number
        $params = array(
            'username'=> $mantis_user,
            'password'=> $mantis_pass,
            'IssueData'=> array(
                'description'=>$ticketInfo->ticket->description,
                'summary'=>$ticketInfo->ticket->subject,
                'additional_information' => 'Zendesk: http://support.foxbright.com/tickets/' . $ticketInfo->ticket->id,
                'category' => 'Customer Request',
                'project'=>array('id'=>$mantis_project)
                )
                );
           //returns mantis issue number created
        $return = $client->__soapCall('mc_issue_add', $params);
        echo $return;
    } catch (Exception $e) {
        echo "Exception occured: " . $e;
    }
    
 //Update zendesk with the same record number
 
    $fields = array (
        'ticket' =>array(
            'fields'=>array(
                'id'=>147688,
                'value'=> $return
            )
        )
    );

// jSON String for request
$json_string = json_encode($fields);
 
$headers = array('Content-Type: application/json','Content-Length: ' . strlen($json_string));
// Initializing curl
$ch = curl_init( $json_url );
 
// Configuring curl options
$options = array(
CURLOPT_RETURNTRANSFER => true,
CURLOPT_USERPWD => $zendesk_user . ":" . $zendesk_pass,   // authentication
CURLOPT_HTTPHEADER => $headers,
CURLOPT_CUSTOMREQUEST=> 'PUT',
CURLOPT_POSTFIELDS => $json_string,
CURLOPT_HEADER => true,
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_VERBOSE => true
);

// Setting curl options
curl_setopt_array( $ch, $options );

try {
    $result =  curl_exec($ch); // Getting jSON result string
}
 catch (Exception $e) {
        echo "Exception occured: " . $e;
    }
    
    
?>
