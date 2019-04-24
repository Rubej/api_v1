<?
require_once '../fnct/f_global.php';
require_once '../fnct/f_crm_contact.php';

//writeToLog($_REQUEST, "_REQUEST");

if($_REQUEST['auth']['application_token'] == 'azyekym1sptxd4cs0ig8sbke754hbkzq' || $_REQUEST['auth']['application_token'] == '9r0pu86dlyel08af310c0gecq4j4tptu') { // webhook Contact -- ONCRMCONTACTUPDATE || ONCRMCONTACTADD
	
	$_LOG[] = $_REQUEST['event'];
	$CONTACT_ID = $_REQUEST['data']['FIELDS']['ID'];
	$contactDetail = getCRMContactDetailResult($CONTACT_ID);
	saveCRMContact($contactDetail);
	$_LOG[] = "{$contactDetail['NAME']} {$contactDetail['LAST_NAME']} - ID: {$contactDetail['ID']}";
	
}
if($_REQUEST['auth']['application_token'] == 'rthdrtwyg05xvb4an2tslpk7ieyuf46m') { // ONCRMCONTACTDELETE
	
	$_LOG[] = $_REQUEST['event'];
	$CONTACT_ID = $_REQUEST['data']['FIELDS']['ID'];
	$_LOG[] = " - ID: {$CONTACT_ID}";
	saveCRMContactDelete($CONTACT_ID);
	
}

if(@!$CONTACT_ID) {
	echo "Webhook";
	exit;
}

writeToWebhookLog("bitrix/webhook_contact", $_LOG);
?>