<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/autoload.php';
$ticketNumber = $_POST['number'];
$ticketId = $_POST['id'];
require_once __DIR__ . '/functions.php';

function GetTicketInfo_New($TheticketNumber, $wsdl, $username, $password) {
	$authWsdl = $wsdl;
	$opts = array('trace' => 1);
	$client = new ATWS\Client($authWsdl, $opts);
	$zoneInfo = $client->getZoneInfo($username);
	$authOpts = array(
		'login' => $username,
		'password' => $password,
		'trace' => 1,   // Allows us to debug by getting the XML requests sent
	);
	$wsdl = str_replace('.asmx', '.wsdl', $zoneInfo->getZoneInfoResult->URL);
	$client = new ATWS\Client($wsdl, $authOpts);
	//Ticket Object Query (the root of all things)
	$ticketquery = new ATWS\AutotaskObjects\Query('Ticket');
	$ticketNumberField = new ATWS\AutotaskObjects\QueryField('ticketnumber');
	$ticketNumberField->addExpression('Equals',$TheticketNumber);
	$ticketquery->addField($ticketNumberField);
	//get the ticket
	$ticket = $client->query($ticketquery);
	// Create TicketEntities
	$TicketEntities = $ticket->queryResult->EntityResults->Entity;
	// Now we get the ticket ContactID, and query against that for the Ticket Contact
	$contactId = $TicketEntities->ContactID;
	$cquery = new ATWS\AutotaskObjects\Query('Contact');
	$contactidfield = new ATWS\AutotaskObjects\QueryField('id');
	$contactidfield->addExpression('Equals',$contactId);
	$cquery->addField($contactidfield);
	//get the contact
	$contact = $client->query($cquery);
	// Create ContactEntities
	$ContactEntities = $contact->queryResult->EntityResults->Entity;
	//Now we get the ticket AccountID, and query against that to get the company name
	$AccountId = $TicketEntities->AccountID;
	$aquery = new ATWS\AutotaskObjects\Query('Account');
	$accountidfield = new ATWS\AutotaskObjects\QueryField('id');
	$accountidfield->addExpression('Equals',$AccountId);
	$aquery->addField($accountidfield);
	//get the account
	$account = $client->query($aquery);
	// Create AccountEntities
	$AccountEntities = $account->queryResult->EntityResults->Entity;
	//Now we get the ticket AssignedResourceID, and query against that to get the company name
	$AssignedResourceID = $TicketEntities->AssignedResourceID;
	$rquery = new ATWS\AutotaskObjects\Query('Resource');
	$ResourceIdField = new ATWS\AutotaskObjects\QueryField('id');
	$ResourceIdField->addExpression('Equals',$AssignedResourceID);
	$rquery->addField($ResourceIdField);
	//get the Resource
	$AssignedResource = $client->query($rquery);
	// Create ResourceEntities
	$ResourceEntities = $AssignedResource->queryResult->EntityResults->Entity;
	// Now we create each piece of data as a var
    $TicketTitle = $TicketEntities->Title;
    $TicketUDF = $TicketEntities->userDefinedFields;
	$FirstName = $ContactEntities->FirstName;
	$LastName = $ContactEntities->LastName;
	$TheName = $FirstName." ".$LastName;
	$Phone = $ContactEntities->Phone;
	$Email = $ContactEntities->EMailAddress;
	$AccountName = $AccountEntities->AccountName;
	$ResourceUsername = $ResourceEntities->UserName;
	//Now we put this data into an array, and return that array so that ticketSlack can make a message
	$ticketDataArray = [
        "TicketTitle" => $TicketTitle,
        "TicketUDF" => $TicketUDF,
		"ContactName" => $TheName,
		"ContactPhone" => $Phone,
		"ContactEmail" => $Email,
		"CompanyName" => $AccountName,
		"ResourceUsername" => $ResourceUsername,
		"AccountId" => $AccountId
		];
	return $ticketDataArray;
}

#I WANT YOU TO USE SSL ~~ Comment this part out at your own risk
if (empty($_SERVER['HTTPS'])) {
    die("SSL WAS NOT USED <br />We want you to use SSL for your own good. Please go back and use SSL");
}
#end ssl check
##########################################################
####THIS FUNCTION IS IMPORTANT TO PREVENT DATA LEAKAGE####
##########################################################
if(!($_GET['s'] == $extensiontoken)) {
	die("Invalid Token or No Token Received");
}
# Now that we've checked security, we'll do some real work
//Fire GetTicketInfo to get our array of data
$ticketData = GetTicketInfo_New($ticketNumber,$wsdl,$username,$password);
//Unwrap the array

$ticketTitle = $ticketData["TicketTitle"];
$ContactName = $ticketData["ContactName"];
$ContactPhone = $ticketData["ContactPhone"];
$ContactEmail = $ticketData["ContactEmail"];
$companyName = $ticketData["CompanyName"];
//Fire MakeSlackNewTicketMessage to get an encoded message for Slack
//$message = MakeSlackNewTicketMessage($ticketNumber,$ticketId,$ticketTitle,$ContactName,$ContactPhone,$ContactEmail,$companyName,$atzone);
##TESTMODE is created from the checkbox in form.html. It stops the message from being dispatched to Slack but displayes it in the browser.
error_log(var_export($ticketData,true),0);


if($testmode){
	echo urldecode($message)."<br />";
	echo $room."<br />";
	echo $slacknotificationsendpoint;
	echo "<br /><br /><br /><br /><br />";
	print_r($ticketData);
}
else {
	slack($message,'#'.$ticketnotificationroom,$slacknotificationsendpoint);
}
?>