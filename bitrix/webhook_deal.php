<?
require_once '../fnct/f_company.php';
require_once '../fnct/f_crm_deal.php';

$CRMProductList = getCRMProductList();

//writeToLog($_REQUEST, "_REQUEST");
//$_LOG[] = $_REQUEST['data']['FIELDS']['ID'];

if($_REQUEST['auth']['application_token'] == '5ol79y2l9200vr0nreaa5oqwfarahxoe' || $_REQUEST['auth']['application_token'] == 'ux6zf3zq6lgq6fa3h6a4dmbnqk88xvl2') { // ONCRMDEALADD || ONCRMDEALUPDATE
	
	$_LOG[] = $_REQUEST['event'];
	$DEAL_ID = $_REQUEST['data']['FIELDS']['ID'];
	$_LOG[] = "DEAL_ID: {$DEAL_ID}";
	$dealDetail = getCRMDealDetail($DEAL_ID);
	saveCRMDeal($dealDetail['result'], TRUE);
	$COMPANY_ID = $dealDetail['result']['COMPANY_ID'];
	$_LOG[] = "COMPANY_ID: {$COMPANY_ID}";

	if(@!empty($COMPANY_ID) && $dealDetail['result']['STAGE_SEMANTIC_ID'] == "S") {
		if($_ID = getIdByCompany($COMPANY_ID)) {
			$_LOG[] = "_ID: {$_ID}";
			if($dealProducts = getCRMDealProduct($DEAL_ID)) {
				if(!empty($dealProducts['result'])) {
					publishingValid($_ID, $DEAL_ID);
					foreach($dealProducts['result'] as $v) {
						$_LOG[] = "PRODUCT_ID: {$v['PRODUCT_ID']}";
						switch($v['PRODUCT_ID']) {
							case "144": // Freemium
								if(companyUpdate($_ID, "publishing_status", "Freemium Publishing")) {
									companyUpdate($_ID, "ts_publishing_status", "NOW()");
								}
								companyDataAdd($_ID, 'crm.company.publishing', "Freemium Publishing");
								companyDataAdd($_ID, 'data.active', 1);
								$_LOG[] = "Freemium";
								break;
							case "140": // Premium
								if(companyUpdate($_ID, "publishing_status", "Premium Publishing")) {
									companyUpdate($_ID, "ts_publishing_status", "NOW()");
								}
								companyDataAdd($_ID, 'crm.company.publishing', "Premium Publishing");
								companyDataAdd($_ID, 'data.active', 1);
								companyDataAdd($_ID, 'data.featured', 1);
								$_LOG[] = "Premium";
								break;
							case "198": // Top
								companyDataAdd($_ID, 'crm.company.publishing', "Premium Publishing + Top");
								companyDataAdd($_ID, 'data.active', 1);
//								companyDataAdd($_ID, 'data.featured', 1);
								companyDataAdd($_ID, 'data.top', 1);
								$_LOG[] = "Top";
								break;
						}
					}
					setCronNextTime('d_refresh_company');
				}
			} else {
				$_LOG[] = "No products";
			}
		}
	}
	
}

if(@!$DEAL_ID) {
	echo "Webhook";
	exit;
}

writeToWebhookLog("bitrix/webhook_deal", $_LOG);

// -----------
function publishingValid($_ID, $DEAL_ID) {
	
	$message = [];
	$param = array(
		'sql_where' => "id_item = '{$_ID}'",
	);
	$companyDetail = getCompanyData($param);
	$companyDetail = $companyDetail[$_ID];
	if(!verifyTiker($companyDetail['funding']['tiker'])) {
		$GLOBALS['_LOG'][] = $message[] = "{$companyDetail['funding']['tiker']} - Tiker is not valid - string; uppercase letters (A-Z, 0-9); line length from 2 to 10 characters";
	}
	if($companyDetail['crm']['index']['crm']['company']['type'] != 2) {
		$GLOBALS['_LOG'][] = $message[] = "Company type must be 'Issuer'";
	}
	if(!verifyUrl($companyDetail['description']['project_landing_url'])) {
		$GLOBALS['_LOG'][] = $message[] = "{$companyDetail['description']['project_landing_url']} - Corporate site is not valid - string; with protokol (http://, https://)";
	}
	if(!empty($message)) {
		$fields_livefeedmessage = [
			'POST_TITLE' => "Publication is not valid",
			'MESSAGE' => "Publication is not valid: \n".implode("; \n", $message),
			'ENTITYTYPEID' => "2",  // 2 - Deal
			"ENTITYID" => $DEAL_ID,
		];
		addCRMLivefeedmessage($fields_livefeedmessage);
		return FALSE;
	}
	return TRUE;

}
// -----------
function verifyTiker($value){
	
	if(preg_match('|^[\dA-Z]{2,10}$|', $value)) return TRUE;
	return FALSE;
	
}
// -----------
function verifyUrl($value){
	
	if(preg_match('|^https?://|', $value)) return TRUE;
	return FALSE;
	
}
?> 