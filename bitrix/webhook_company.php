<?php
require_once '../fnct/f_global.php';
require_once '../fnct/f_crm_company.php';

//$itemFields = getMatchingFields("crm_bitrix_company");
//$CRMFieldList = getCRMFieldList('company');
//$sourceScriptName = getScriptName();

writeToLog($_REQUEST, "_REQUEST");

if($_REQUEST['auth']['application_token'] == 'sf5ulx6lfjkrths9dlnjyih0hurz09qx') { // webhook Company -- ONCRMCOMPANYADD
	
	$_LOG[] = $_REQUEST['event'];
	$COMPANY_ID = $_REQUEST['data']['FIELDS']['ID'];
	$companyDetail = getCRMCompanyDetailResult($COMPANY_ID);
	saveCRMCompany($companyDetail);
	$_LOG[] = " - {$companyDetail['TITLE']}";
	$_LOG[] = " - ID: {$companyDetail['ID']}";
	$_LOG[] = " - OKPO: {$companyDetail['UF_CRM_1553274141697']}";
	if(!empty($companyDetail['UF_CRM_1553274141697'])) { // OKPO
		$_ID = companySave($companyDetail);
		setCronNextTime('d_refresh_company');
	}
	
}

if(@!$COMPANY_ID) {
	echo "Webhook";
//	exit;
}
$_LOG[] = "id: ".$_ID;
writeToWebhookLog("bitrix/webhook_company", $_LOG);

// -----------
function companyAdd($data) {
	
	$sql = "INSERT hc_company SET id_bitrix_company = '".mysqli_real_escape_string($GLOBALS['connect_data'], $data['ID'])."';";
	mysqli_query($GLOBALS['connect_data'], $sql) or die("<br /><b>File</b>: ".basename(__FILE__)." in ".__LINE__."<br /><b>Error</b>: ".mysqli_error()."<br /><b>SQL</b>: ".$sql."<br /><br />");
	$_ID = mysqli_insert_id($GLOBALS['connect_data']);
	companyUpdate($_ID, "source", "bitrix");
	return $_ID;

}
// -----------
function companySave($data) {

//writeToLog($data, "data");
	if(!$_ID = getIdByCompany($data['ID'])) {
		if(!$_ID = getIdCompanyByUrlTiker($data['WEB'][0]['VALUE'], $data['UF_CRM_1530790102'], "id_bitrix_company = '0'")) {
			$_ID = companyAdd($data); // add company
		} else {
			companyUpdate($_ID, "id_bitrix_company", $data['ID']);
		}
	}
	foreach($data as $k => $v) {
		if(is_array($v)) {
			if(!in_array($k, ["EMAIL", "WEB", "IM"]) && isset($GLOBALS['CRMFieldList'][$k]) && isset($GLOBALS['itemFields'][$k]) && !preg_match('/crm\.index/', $GLOBALS['itemFields'][$k])) {
				sort($v);
				companyDataAdd($_ID, "crm.index.".$GLOBALS['itemFields'][$k], json_encode($v));
			}
			foreach($v as &$v1) {
				if(isset($GLOBALS['CRMFieldList'][$k][$v1])) {
					$v1 = $GLOBALS['CRMFieldList'][$k][$v1];
				}
			}
		} else {
			if(isset($GLOBALS['CRMFieldList'][$k][$v])) {
				if(isset($GLOBALS['itemFields'][$k])) companyDataAdd($_ID, "crm.index.".$GLOBALS['itemFields'][$k], $v);
				$v = $GLOBALS['CRMFieldList'][$k][$v];
			}
		}

		switch($k){
			case "EMAIL":
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				companyDataAdd($_ID, 'ownership.individual.email', $v[0]['VALUE']);
				break;
			case "WEB":
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				$url = getUrlWork($v);
				companyUpdate($_ID, "url", formatUrlShort($url));
				companyDataAdd($_ID, 'description.project_landing_url', $url);
				companyDataAdd($_ID, "description.project_landing_url_short", formatUrlShort($url));
				break;
			case "TITLE":
				companyUpdate($_ID, "title", $v);
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "COMPANY_TYPE":
				if(companyUpdate($_ID, "status", $v)) {
					companyUpdate($_ID, "ts_status", "NOW()");
				}
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "UF_CRM_1530790102": // Tiker
				companyUpdate($_ID, "tiker", $v);
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "UF_CRM_5B379EAB83DF6": // crm.company.token_platform --> funding.token_platform
				companyDataAdd($_ID, 'funding.token_platform', $v[0]);
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "UF_CRM_5BB75C67F16CE": // funding.price_per_token
				if(is_numeric($v)) $v = formatNumeric($v, 3);
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "UF_CRM_5BA7FE03485D2": // Publishing status
				if(companyUpdate($_ID, "publishing_status", $v)) {
					companyUpdate($_ID, "ts_publishing_status", "NOW()");
				}
				companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
				break;
			case "UF_CRM_5BCCA3DFD6C47":
			case "UF_CRM_5BCCA3E00B336":
			case "UF_CRM_5B06EAB74375A":
			case "UF_CRM_5B06EAB763FEF":
				if(strtotime($v) < strtotime("01/01/2015")) break;
			default:
				if(isset($GLOBALS['itemFields'][$k])) companyDataAdd($_ID, $GLOBALS['itemFields'][$k], $v);
		}
	}
	companyUpdate($_ID, "ts_bitrix_company", "NOW()");
	return $_ID;

}
// -----------
function dieError($error){

		$GLOBALS['_LOG'][] = $error;
		writeToWebhookLog("bitrix/webhook", $GLOBALS['_LOG']);
		die($error);

}
?>