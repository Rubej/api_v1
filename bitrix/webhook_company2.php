<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../fnct/f_global.php';
require_once '../fnct/f_crm_company.php';
require_once '../fnct/f_crm_contact.php';
require_once '../fnct/api_class_opendatabot.php';

writeToLog($_REQUEST, "_REQUEST");

if($_REQUEST['auth']['application_token'] == 'sf5ulx6lfjkrths9dlnjyih0hurz09qx') { // webhook Company -- ONCRMCOMPANYADD
	
	$_LOG[] = $_REQUEST['event'];
	$COMPANY_ID = $_REQUEST['data']['FIELDS']['ID'];
	$companyDetail = getCRMCompanyDetailResult($COMPANY_ID);
	$_LOG[] = " - {$companyDetail['TITLE']}";
	$_LOG[] = " - ID: {$companyDetail['ID']}";

//$companyDetail['UF_CRM_1553274141697'] = '36149063';

	if(!empty($companyDetail['UF_CRM_1553274141697'])) { // Code EDRPOU
		$_LOG[] = " - Code EDRPOU: {$companyDetail['UF_CRM_1553274141697']}";
		$OpenDataBotDetail = OpenDataBotAPI::getFullCompany($companyDetail['UF_CRM_1553274141697']);

//$OpenDataBotDetail['short_name'] = 'short_name';
//$OpenDataBotDetail['location'] = 'location';
//$OpenDataBotDetail['phones'] = '1234567';
//$OpenDataBotDetail['email'] = 'email@email.em';


//		print_r($companyDetail);
//		print_r($OpenDataBotDetail);

		setFields('TITLE', $OpenDataBotDetail['short_name']);
		setFields('UF_CRM_1555604268434', $OpenDataBotDetail['full_name']);
		setFields('UF_CRM_1555605750903', $OpenDataBotDetail['warnings'][0]['mass_address']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['mass_address']['text']);
		setFields('UF_CRM_1555604448357', $OpenDataBotDetail['location']);
		setFields('UF_CRM_1555605097351', $OpenDataBotDetail['registration_date']);
		setFields('UF_CRM_1555605061551', $OpenDataBotDetail['status']);
		setFields('UF_CRM_1555921702', $OpenDataBotDetail['capital']);
		$arr = [];
		foreach($OpenDataBotDetail['activities'] as $v) {
			$arr[] = $v['name'];
		}
		setFields('UF_CRM_1555605148492', implode("\n", $arr));
		$arr = [];
		foreach($OpenDataBotDetail['beneficiaries'] as $v) {
			$arr[] = $v['role'];
			$arr[] = $v['name'];
			$arr[] = $v['location'];
			$arr[] = '';
		}
		setFields('UF_CRM_1555605222289', implode("\n", $arr));
		setFields('UF_CRM_1555605814631', $OpenDataBotDetail['warnings'][0]['pdv']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['pdv']['text']);
		$arr = [];
		foreach(@$OpenDataBotDetail['licenses'] as $k => $v) {
			$arr[] = $k;
			foreach($v as $v1) {
				$arr[] = $v1['department'];
				$arr[] = 'Лицензия № '.$v1['number'];
				$arr[] = 'Действует с '.$v1['start_date'];
				$arr[] = 'Действует до '.$v1['end_date'];
				$arr[] = ($v1['active']) ? '✅️' : '⚠️';
			}
			$arr[] = '';
		}
		setFields('UF_CRM_1555606091340', implode("\n", $arr));
		setFields('UF_CRM_1555605553628', $OpenDataBotDetail['warnings'][0]['courts']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['courts']['text']);
		setFields('UF_CRM_1555605606945', $OpenDataBotDetail['warnings'][0]['tax_debts']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['tax_debts']['text']);
		setFields('UF_CRM_1555606075154', $OpenDataBotDetail['warnings'][0]['bancruptcy']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['bancruptcy']['text']);
		setFields('UF_CRM_1555605882170', $OpenDataBotDetail['warnings'][0]['amk_list']['icon'] . ' ' . $OpenDataBotDetail['warnings'][0]['amk_list']['text']);
		$fields['PHONE'] = [array(
			'VALUE' => $OpenDataBotDetail['phones'],
			'TYPE_ID' => 'PHONE',
			'VALUE_TYPE' => 'WORK',
		)];
		$fields['EMAIL'] = [array(
			'VALUE' => $OpenDataBotDetail['email'],
			'TYPE_ID' => 'EMAIL',
			'VALUE_TYPE' => 'WORK',
		)];
		print_r($fields);
		updateCRMCompany($COMPANY_ID, $fields);

		// Contact
		foreach($OpenDataBotDetail['heads'] as $v) {
			list($LAST_NAME, $NAME, $SECOND_NAME) = getNameFromFullName($v['name']);
			$fields = array(
//				"ASSIGNED_BY_ID" => "144",
				"COMPANY_ID" => $COMPANY_ID,
				"NAME" => $NAME,
				"SECOND_NAME" => $SECOND_NAME,
				"LAST_NAME" => $LAST_NAME,
				"POST" => $v['role'],
			);
			setFields('COMMENTS', $v['restriction']);
			print_r($fields);
			addCRMContact($fields);
		}

	}
	
}

if(@!$COMPANY_ID) {
	echo "Webhook";
//	exit;
}
//writeToWebhookLog("bitrix/webhook_company", $_LOG);
writeToLog($_LOG, "bitrix/webhook_company");

function setFields($name, $value) {

	if(!empty(trim($value))) {
		$GLOBALS['fields'][$name] = $value;
	}

}
?>