<?php
require_once(__DIR__ . "/../model/AffinityProduct.php");
require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");

/*
 * Class to Authenticate, Format and Authenticate Calls to Affinity Backend Service
 */
class AffinityBackendService {
	const AUTH_ENDPOINT = "auth";
	const SETUP_CALLBACK_URL_ENDPOINT = "profile";
	const CREATE_LISTING_ENDPOINT = "inventory/";
	const CREATE_MULTIVARIATION_LISTING_ENDPOINT = "variation";
	const GET_LISTING_ENDPOINT = "inventory/"; // $endpoint/$productSku
	const END_LISTING_ENDPOINT = "inventory/"; // $endpoint/$productSku
	const END_MULTIVARIATION_LISTING_ENDPOINT = "variation/"; // $endpoint/$productSku
	const SYNC_INVENTORY_ENDPOINT = "sync";
	const GET_CATEGORY_SUGGESTION_ENDPOINT = "category";
	const GET_PROFILE_ENDPOINT = "profile";
    const GET_SHIPPING_DISCOUNT_PROFILES_ENDPOINT = "shippingDiscountProfiles";
	const ORDER_UPDATE_ENDPOINT = "order";
	const CONFIG_UPDATE_ENDPOINT = "adm/config";
	
	const SERVICE_TIMEOUT = 30;
	const MAX_PRODUCTS_PER_INVENTORY_SYNC = 5;
	
	const PUSH_ACCESS_TOKEN_OPTION = "affinityPushAccessToken";
	const LAST_LOGIN_FAILED_OPTION = "affinityLastLoginFailed";
	const LAST_COMMAND_RETURNED_ACCESS_FORBIDDEN_OPTION = "affinityCurrentAuthenticationInvalid";
	const EXPIRY_OPTION = "affinityTokenExpiry";
	
	public static function postConfig() {
		global $woocommerce;
		global $ebaysync;
		
		$arrRequest = array();
		$arrRequest["pluginVersion"] = $ebaysync->getVersion();
		$arrRequest["pluginDistType"] = $ebaysync->getDist();
		$arrRequest["lastPluginUpdatedDate"] = $ebaysync->getAge();
		$arrRequest["wpVersion"] = get_bloginfo('version');
		$arrRequest["wcVersion"] = $woocommerce->version;
		$arrRequest["phpVersion"] = phpversion();
		
		$arrResponse = self::callMethodWithJsonContent(self::CONFIG_UPDATE_ENDPOINT, $arrRequest, true, "POST");
		return $arrResponse['arrResult'];
	}
	
	private static $mappingProductStatusToServiceRequestType = array(
		AffinityProduct::UPDATE_STATUS_LISTING_NEVER_PROCESSED => "CREATE_UPDATE_INVENTORY",
		AffinityProduct::UPDATE_STATUS_NEEDS_TO_BE_SAVED => "CREATE_UPDATE_INVENTORY",
		AffinityProduct::UPDATE_STATUS_NEEDS_TO_BE_DELETED => "END_ITEM",
	);
	
	public static function getServiceUrl() {
		return AffinityDataLayer::getOption("ebayaffinity_backend");
	}
    
	public static function setupCallbackUrl($callbackUrl) {
		$arrRequest = array();
		$arrRequest["storeProfile"]["callbackUrl"] = $callbackUrl;
		
		$arrHttpResponse = self::callMethodWithJsonContent(self::SETUP_CALLBACK_URL_ENDPOINT, $arrRequest, true, "PUT");
		if($arrHttpResponse['httpResponseCode'] == 200) {
			return true;
		}
		
		return false;
	}
	 
	/*
	 * @return true if successfully authenticated, false otherwise
	 */
    public static function authenticate($ebayUser, $puuid) {
		require_once(__DIR__ . "/../model/AffinityGlobalOptions.php");

		$arrRequest["partnerName"] = "wooCommerce";
		$arrRequest["installationId"] = AffinityGlobalOptions::getInstallationId();
		$arrRequest["sellerStoreUrl"] = AffinityGlobalOptions::getCallbackUrl();
		$arrRequest["puuid"] = $puuid;
		
		$arrHttpResponse = self::callMethodWithJsonContent(self::AUTH_ENDPOINT, $arrRequest, false, "POST");
		if($arrHttpResponse['httpResponseCode'] == 200) {
			$arrResult = $arrHttpResponse['arrResult']["data"];
			
			if(isset($arrResult['installationId']) && $arrResult['installationId'] !== $arrRequest['installationId']) {
				AffinityGlobalOptions::setInstallationId($arrResult['installationId']);
			}
			
			if(!empty($arrResult['expiryDate'])) {
				AffinityDataLayer::saveOption(self::EXPIRY_OPTION, $arrResult['expiryDate']);
			} else {
				AffinityDataLayer::saveOption(self::EXPIRY_OPTION, '');
			}
			
			if(count($arrHttpResponse['arrResult']['errors']) < 1) {
				AffinityDataLayer::saveOption(self::LAST_LOGIN_FAILED_OPTION, false);
				AffinityDataLayer::saveOption(self::LAST_COMMAND_RETURNED_ACCESS_FORBIDDEN_OPTION, false);
				return true;
			}
		}
		else {
			if (empty($arrResult)) {
				$arrResult = array();
			}
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Authentication Error", print_r($arrResult, true));
		}
		
		AffinityDataLayer::saveOption(self::LAST_LOGIN_FAILED_OPTION, true);
		return false;
	}
	
	public static function hasLastCommandAuthenticationFailed() {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		return AffinityDataLayer::getOption(self::LAST_COMMAND_RETURNED_ACCESS_FORBIDDEN_OPTION);
	}
	
	public static function deleteAllListings() {
		$arrHttpResponse = self::callMethodWithJsonContent(self::AUTH_ENDPOINT, null, true, "DELETE");
		
		if($arrHttpResponse['httpResponseCode'] != 200) {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error deleting all listings", "Server returned an error:  ". print_r($arrHttpResponse, true));
			return false;
		}
		
		return true;
	}
	
	public static function checkDeleteAllListingsHasFinished() {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		$arrHttpResponse = self::callMethodWithJsonContent(self::AUTH_ENDPOINT . "/status", null, true, "GET");
		
		if($arrHttpResponse['httpResponseCode'] != 200 || !isset($arrHttpResponse['arrResult']['data'])) {
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error checking deleting all listings finished", "Server returned an error or no 'data' in result:  ". print_r($arrHttpResponse, true));
			return false;
		}
		
		$arrData = $arrHttpResponse['arrResult']['data'];
		if($arrData['status'] === "SUSPENDED") {
			return false;
		}
		if($arrData['status'] === "ERROR_DELETE") {
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error while deleting some items", "Error while deleting the following items:  ". print_r($arrData['itemIds'], true));
			return true;
		}
		
		return true;
	}
	
	public static function unhookInstallation() {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		$arrHttpResponse = self::callMethodWithJsonContent(self::AUTH_ENDPOINT . "?endListing=false", null, true, "DELETE");
	
		if($arrHttpResponse['httpResponseCode'] != 200) {
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error checking unhooking installation", "Server returned an error:  ". print_r($arrHttpResponse, true));
			return false;
		}
	
		return true;
	}
	
	public static function hasLastLoginFailed() {
		return AffinityDataLayer::getOption(self::LAST_LOGIN_FAILED_OPTION);
	}
	
	public static function hasWoobayGotPushToken() {
		$t = self::getCurrentPushToken();
		return !empty($t);
	}
    
    public static function saveListing($objAffinityProduct) {
		$endPoint = self::CREATE_LISTING_ENDPOINT . $objAffinityProduct->sku;
		
		$arrRequest = self::transformAffinityProductInServiceProduct($objAffinityProduct);
		unset($arrRequest['syncRequestType']);
		unset($arrRequest['sku']);
		
		$arrResponse = self::callMethodWithJsonContent($endPoint, $arrRequest, true, 'POST');
		
		//Backend also returns 500 when an error with the listing has happened
		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			$arrResult = $arrResponse['arrResult'];
			self::processReceivedListing($arrResult);
		}
		else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			require_once(__DIR__ . "/../model/AffinityProduct.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Saving Listing", print_r($arrResponse, true));
			
			if (!empty($arrResponse['arrResult'])) {
				$arrErrors = !empty($arrResponse['arrResult']['errors']) ? $arrResponse['arrResult']['errors'] : array();
			} else {
				$arrErrors = array();
			}
			
			foreach ($arrErrors as $arrError) {
				if ($arrError == 'Internal System Error') {
					$sysError_expire = get_option('affinity_syserror_expire');
					if (empty($sysError_expire) || $sysError_expire < time()) {
						update_option('affinity_syserror_expire', time() + 3600);
						AffinityEcommerceUtils::sendNotificationMail(AffinityEcommerceUtils::getAdminEmail(), $arrError, 'An API error occurred. <a href="https://ebaysync.zendesk.com/hc/en-us/requests/new">Please contact support</a>.', '', false);
					}
				}
			}
			
			AffinityProduct::listingReturnReceived($objAffinityProduct->sku, "", array(), array("Server returned an unexpected error (http response code: " . $arrResponse['httpResponseCode'] . "). Please try again later!"));
		}
	}
	
	private static function processReceivedListing($arrListingResult) {
		$sku = (isset($arrListingResult['data']['sku'])) ? $arrListingResult['data']['sku'] : "";
		if(empty($sku)) {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Empty SKU returned", "When saving a product, a SKU was expected to be returned!<br>" . print_r($arrListingResult, true));
			return;
		}

		$listingID = (isset($arrListingResult['data']['listingId'])) ? $arrListingResult['data']['listingId'] : "";
		$arrWarnings = !empty($arrListingResult['warnings']) ? $arrListingResult['warnings'] : array();
		$arrErrors = !empty($arrListingResult['errors']) ? $arrListingResult['errors'] : array();
		
		foreach ($arrErrors as $arrError) {
			if ($arrError == 'Internal System Error') {
				$sysError_expire = get_option('affinity_syserror_expire');
				if (empty($sysError_expire) || $sysError_expire < time()) {
					update_option('affinity_syserror_expire', time() + 3600);
					AffinityEcommerceUtils::sendNotificationMail(AffinityEcommerceUtils::getAdminEmail(), $arrError, 'An API error occurred. <a href="https://ebaysync.zendesk.com/hc/en-us/requests/new">Please contact support</a>.', '', false);
				}
			}
		}
		
		AffinityProduct::listingReturnReceived($sku, $listingID, $arrWarnings, $arrErrors);
	}
	
	private static function processReceivedMultiVariationListing($arrListingResult, $objAffinityProduct) {
		$arrWarnings = !empty($arrListingResult['warnings']) ? $arrListingResult['warnings'] : array();
		$arrErrors = !empty($arrListingResult['errors']) ? $arrListingResult['errors'] : array();
		
		foreach ($arrErrors as $arrError) {
			if ($arrError == 'Internal System Error') {
				$sysError_expire = get_option('affinity_syserror_expire');
			
				if (empty($sysError_expire) || $sysError_expire < time()) {
					update_option('affinity_syserror_expire', time() + 3600);
					AffinityEcommerceUtils::sendNotificationMail(AffinityEcommerceUtils::getAdminEmail(), $arrError, 'An API error occurred. <a href="https://ebaysync.zendesk.com/hc/en-us/requests/new">Please contact support</a>.', '', false);
				}
			}
		}
		
		$arrData = (count($arrErrors) > 0) ? $arrListingResult['data'] : $arrListingResult['data'];
		$listingID = "";
		if(isset($arrData['inventory']) && is_array($arrData['inventory'])) {
			foreach($arrData['inventory'] as $arrInventoryItem) {
				if(is_array($arrInventoryItem['errors'])) {
					foreach($arrInventoryItem['errors'] as $strError) {
						$arrErrors[] = "Error on variation " . $arrInventoryItem['data']['sku'] . ": $strError";
					}
				}
				
				if(isset($arrInventoryItem['data']['listingId']) && !empty($arrInventoryItem['data']['listingId'])) {
					$listingID = $arrInventoryItem['data']['listingId'];
				}
			}
		}
		
		$sku = (isset($arrData['groupId'])) ? $arrData['groupId'] : "";
		if(empty($sku)) {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Empty SKU returned", "When saving a product, a SKU was expected to be returned!<br>" . print_r($arrListingResult, true));
			return;
		}
		
		if(empty($listingID)) {
			$listingID = (isset($arrData['variationGroup']['listingId'])) ? $arrData['variationGroup']['listingId'] : "";
			if(empty($listingID) && count($arrErrors) == 0) {
				require_once(__DIR__ . "/../model/AffinityLog.php");
				AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Empty Listing ID returned", "When saving a product, a Listing ID was expected to be returned!<br>" . print_r($arrListingResult, true));
				return;
			}
		}
		
		AffinityProduct::multiVariationListingReturnReceived($objAffinityProduct, $listingID, $arrWarnings, $arrErrors);
	}
	
	public static function saveMultiVariationListing($objAffinityProduct, $retrial = false) {
		$endPoint = self::CREATE_MULTIVARIATION_LISTING_ENDPOINT;
		
		$skus = array();
		$relist = false;
		foreach($objAffinityProduct->arrVariationProducts as $obj) {
			if (isset($skus[$obj->sku])) {
				$relist = true;
				break;
			}
			$skus[$obj->sku] = $obj->sku;
		}
		
		if ($relist) {
			self::tryToRelistMultiVariationListing($objAffinityProduct);
			return;
		}
		
		$arrRequest = self::transformAffinityProductInServiceProductVariation($objAffinityProduct);
		$arrResponse = self::callMethodWithJsonContent($endPoint, $arrRequest, true, 'POST');
		
		$tryingToRelist = false;
		
		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			if(is_array($arrResponse['arrResult']['errors']) && count($arrResponse['arrResult']['errors']) > 0) {
				$arrErrors = $arrResponse['arrResult']['errors'];
				
				AffinityProduct::multiVariationListingReturnReceived($objAffinityProduct, "", array(), $arrErrors);
				 
				foreach($arrErrors as $strErrorMessage) {
					require_once(__DIR__ . "/../model/AffinityLog.php");
					AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Processing error $strErrorMessage", "No detail");
					
					if (!$retrial) {
						if(
							stristr($strErrorMessage, "Duplicate VariationSpecifics trait value in the VariationSpecificsSet container") !== FALSE ||
							stristr($strErrorMessage, "Selected category is not enabled for variation listings") !== FALSE ||
							stristr($strErrorMessage, "Duplicate name-value combination in variation specifics") !== FALSE ||
							stristr($strErrorMessage, "Duplicate custom variation label") !== FALSE ||
							stristr($strErrorMessage, "Missing name in the variation specifics") !== FALSE ||
							stristr($strErrorMessage, "Variation Specifics provided does not match with the variation specifics") !== FALSE
						) {
							AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Condition to try relisting found", "Trying to relist $objAffinityProduct->sku");
							$tryingToRelist = true;
							self::tryToRelistMultiVariationListing($objAffinityProduct);
							break;
						}
					}
				}
			}
				
			if(!$tryingToRelist) {
				$arrResult = $arrResponse['arrResult'];
				self::processReceivedMultiVariationListing($arrResult, $objAffinityProduct);
			}
		} else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Saving MultiVariation Listing", print_r($arrResponse, true));
			AffinityProduct::multiVariationListingReturnReceived($objAffinityProduct, "", array(), array("Server returned an unexpected error (http response code: " . $arrResponse['httpResponseCode'] . "). Please try again later!"));
		}
	}
	
	public static function tryToRelistMultiVariationListing($objAffinityProduct) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Trying to relist $objAffinityProduct->sku", "No Details");
		
		require_once(__DIR__ . "/../model/AffinityProduct.php");
		
		AffinityProduct::multiVariationListingReturnReceived($objAffinityProduct, "", array(), array("The API returned a constraint error for your product. Please check if there is any duplicate titles/variations and try again!"));
		
		$arrEndMultiVariationResponse = self::endMultiVariationListing($objAffinityProduct);
			
		if(empty($objAffinityProduct->ebayListingID) || 1==1 && $arrEndMultiVariationResponse['httpResponseCode'] == 200 || $arrEndMultiVariationResponse['httpResponseCode'] == 201) {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Successfully ended $objAffinityProduct->sku", "arrEndMultiVariationResponse: " . print_r($arrEndMultiVariationResponse, true));
			$objAffinityProduct->addProductExistingClientWarnings("The original listing has been ended because some changes made to the product specifications were not supported!");
			
			$objFreshVariationProduct = AffinityProduct::get($objAffinityProduct->id);
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Saving fresh product", "Product after refresh: " . print_r($objFreshVariationProduct, true));
			$arrNewMultiVariationListingResponse = self::saveMultiVariationListing($objFreshVariationProduct, true);

			if($arrNewMultiVariationListingResponse['httpResponseCode'] == 200 || $arrNewMultiVariationListingResponse['httpResponseCode'] == 201) {
				AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Successfully recreated $objAffinityProduct->sku", "arrEndMultiVariationResponse: " . print_r($arrNewMultiVariationListingResponse, true));
				$objFreshVariationProduct->addProductExistingClientWarnings("A new listing was automatically created with the new specifications!");
			}
		}
		else {
			AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Error ending $objAffinityProduct->sku", "arrEndMultiVariationResponse: " . print_r($arrEndMultiVariationResponse, true));
			$objAffinityProduct->addProductExistingClientWarnings("We received an error from eBay while trying to recreate your listing! Please end your item manually on eBay and try again!");
		}
	}
    
    public static function endListing($objAffinityProduct) {
		$endPoint = self::END_LISTING_ENDPOINT . $objAffinityProduct->sku;
		
		$arrResponse = self::callMethodWithJsonContent($endPoint, array(), true, 'DELETE');
		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			$arrResult = $arrResponse['arrResult'];
			AffinityProduct::listingWasEnded($arrResult['data']['sku'], $arrResult['warnings'], $arrResult['errors']);
		}
		else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Failed Ending Listing", print_r($arrResponse, true));
		}
		
		return $arrResponse;
	}
	
	public static function endMultiVariationListing($objMainVariationProduct) {
		$endPoint = self::END_MULTIVARIATION_LISTING_ENDPOINT . $objMainVariationProduct->sku;
		$arrRequest = self::transformAffinityProductInServiceProductVariation($objMainVariationProduct);
		$arrRequest["itemId"] = $objMainVariationProduct->ebayListingID;
		
		$arrResponse = self::callMethodWithJsonContent($endPoint, $arrRequest, true, 'DELETE');
		$arrResult = $arrResponse['arrResult'];
		
		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			AffinityProduct::multiVariationListingWasEnded($objMainVariationProduct, $arrResult['warnings'], $arrResult['errors']);
		}
		else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Failed Ending MultiVariation Listing", print_r($arrResponse, true));
			AffinityProduct::multiVariationListingWasEnded($objMainVariationProduct, $arrResult['warnings'], $arrResult['errors']);
		}
		
		return $arrResponse;
	}
	
	public static function getListing($objAffinityProduct) {
		$endPoint = self::END_LISTING_ENDPOINT . $objAffinityProduct->sku;
		$arrResponse = self::callMethodWithJsonContent($endPoint, array(), true, 'GET');
		return $arrResponse;
	}
    
    public static function syncInventory($arrObjAffinityProducts) {
		if(count($arrObjAffinityProducts) < 1) {
			return;
		}
		
		$arrRequest = array();
		$arrRequest["inventory"] = array();
		
		$productsSent = 1;
		foreach($arrObjAffinityProducts as $objAffinityProduct) {
			if($objAffinityProduct->isMultiVariationProduct) {
				self::saveMultiVariationListing($objAffinityProduct->variationObjMainProduct);
				continue;
			}
			
			$arrRequest["inventory"][] = self::transformAffinityProductInServiceProduct($objAffinityProduct);
			$productsSent++;
			
			if(count($arrRequest["inventory"]) >= self::MAX_PRODUCTS_PER_INVENTORY_SYNC) {
				self::sendInventorySyncRequest($arrRequest);
				$productsSent = 1;
				$arrRequest["inventory"] = array();
			}
		}
		
		//If there's still any product left, send them
		if(count($arrRequest["inventory"]) > 0) {
			self::sendInventorySyncRequest($arrRequest);
		}
	}
	
	private static function sendInventorySyncRequest($arrRequest) {
		$arrResponse = self::callMethodWithJsonContent(self::SYNC_INVENTORY_ENDPOINT, $arrRequest, true, 'POST');
		
		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			$arrInventory = is_array($arrResponse['arrResult']['inventory']) ? $arrResponse['arrResult']['inventory'] : array();
			
			foreach($arrInventory as $arrListing) {
				self::processReceivedListing($arrListing);
			}
		}
		else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Synchronising Inventory", print_r($arrResponse, true));
		}
		
		return $arrResponse;
	}
	
	public static function getSuggestionForTitle($title) {
		$arrRequest = array();
		$arrRequest["query"] = $title;
		$arrRequest["queryType"] = 2;
		$arrRequest["maxCount"] = 5;
		
		$arrResponse = self::callMethodWithJsonContent(self::GET_CATEGORY_SUGGESTION_ENDPOINT, $arrRequest);
		return $arrResponse['arrResult'];
	}
	
	public static function getCategoryTree() {
		ini_set('memory_limit', '2048M');
		$arrRequest = array();
		$arrRequest["treemap"] = 1;
		
		$arrResponse = self::callMethodWithJsonContent(self::GET_CATEGORY_SUGGESTION_ENDPOINT, $arrRequest);
		return $arrResponse['arrResult'];
	}
	
	public static function getCategoryExpired() {
		ini_set('memory_limit', '2048M');
		$arrRequest = array();
		$arrRequest["expired"] = 'true';
		
		$arrResponse = self::callMethodWithJsonContent(self::GET_CATEGORY_SUGGESTION_ENDPOINT, $arrRequest);
		return $arrResponse['arrResult'];
	}
	
	public static function getAttributesForCategories($arrRequest) {
		$curkey = sha1(serialize($arrRequest));
		
		$expires = time() + 86400;
		
		$catatts = get_option('ebayaffinity_categoryattributes');
		if (empty($catatts)) {
			$catatts = array();
		}
		
		foreach ($catatts as $k=>$v) {
			if ($v['expires'] < time()) {
				unset($catatts[$k]);
			}
		}
		
		if (empty($catatts[$curkey])) {
			$arrResponse = self::callMethodWithJsonContent(self::GET_CATEGORY_SUGGESTION_ENDPOINT, $arrRequest, true, 'POST');
	
			if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
				if (empty($arrResponse['arrResult']['errors'])) {
					$catatts[$curkey] = array(
							'arrResponse' => $arrResponse,
							'expires' => $expires
					);
					update_option('ebayaffinity_categoryattributes', $catatts);
				}
			}
			else {
				require_once(__DIR__ . "/../model/AffinityLog.php");
				AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Get Suggested Attributes", print_r($arrResponse, true));
			}
		} else {
			$arrResponse = $catatts[$curkey]['arrResponse'];
		}
	
		return $arrResponse;
	}
	
	public static function getProfile() {
		$arrResponse = self::callMethodWithJsonContent(self::GET_PROFILE_ENDPOINT, array());
		return $arrResponse['arrResult'];
	}

    public static function getShippingDiscountProfiles() {
        $arrResponse = self::callMethodWithJsonContent(self::GET_SHIPPING_DISCOUNT_PROFILES_ENDPOINT, array());
        return $arrResponse['arrResult'];
    }

	public static function getSellerCosts() {
		$arrResponse = self::callMethodWithJsonContent(self::GET_PROFILE_ENDPOINT, array("type" => "SELLERCOSTS"));
		return $arrResponse['arrResult'];
	}
	
	public static function getSellerLimits() {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		$arrResponse = self::callMethodWithJsonContent(self::GET_PROFILE_ENDPOINT, array("type" => "SELLERLIMITS"));
		
		if($arrResponse['httpResponseCode'] == 200 && (!empty($arrResponse['arrResult'])) && (!empty($arrResponse['arrResult']['data'])) && is_array($arrResponse['arrResult']['data']) && isset($arrResponse['arrResult']['data']['limitValue']) && is_array($arrResponse['arrResult']['data']['limitValue'])) {
			$productsLimit = -1;
			$valueLimit = -1;
			
			$arrLimits = $arrResponse['arrResult']['data']['limitValue'];
			foreach($arrLimits as $arrLimit) {
				if($arrLimit['limitType'] === "TOTAL_ITEMS_QTY") {
					$productsLimit = $arrLimit['qty'];
					continue;
				}
				
				if($arrLimit['limitType'] === "TOTAL_ITEMS_GMS") {
					$valueLimit = $arrLimit['amount']['value'];
					continue;
				}
			}
			
			return array(
				"products" => $productsLimit,
				"value" => $valueLimit
			);
		}
		
		AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Getting Seller Limits", print_r($arrResponse, true));
		return false;
	}
	
	public static function sendProfileSyncRequest($arrRequest, $method='POST') {
		$arrResponse = self::callMethodWithJsonContent(self::GET_PROFILE_ENDPOINT, $arrRequest, true, $method);

		if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
			//
		}
		else {
			require_once(__DIR__ . "/../model/AffinityLog.php");
			AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Error Synchronising Profile", print_r($arrResponse, true));
		}
	
		return $arrResponse;
	}
	
	public static function sendOrderShippingDetails(AffinityOrder $objAffinityOrder) {
		global $affinity_sendOrderShippingDetails;
		
		$ebayItemIds = explode(',', $objAffinityOrder->ebayItemId);
		$ebayTransactionIds = explode(',', $objAffinityOrder->ebayTransactionId);
		
		foreach ($ebayItemIds as $k=>$v) {
			$arrRequest = array(
				"purchaseOrderId" => $objAffinityOrder->ebayPurchaseOrderId,
				"itemId" => $ebayItemIds[$k],
				"transactionId" => $ebayTransactionIds[$k],
				"markAsShipped" => ($objAffinityOrder->wasSent || $objAffinityOrder->wasSent === "yes") ? true : false,
				"shippingCourierName" => !empty($objAffinityOrder->carrierName) ? $objAffinityOrder->carrierName : null,
				"shippingTrackingNumber" => !empty($objAffinityOrder->trackingNumber) ? $objAffinityOrder->trackingNumber : null,
			);
			
			$req = json_encode($arrRequest);
			if (!empty($affinity_sendOrderShippingDetails)) {
				if ($req == $affinity_sendOrderShippingDetails) {
					return;
				}
			}
			$affinity_sendOrderShippingDetails = $req;
			
			$arrResponse = self::callMethodWithJsonContent(self::ORDER_UPDATE_ENDPOINT, $arrRequest, true, 'PUT');
			if ((!empty($arrResponse['arrResult'])) && (!empty($arrResponse['arrResult']['errors'])) && (!empty($arrResponse['arrResult']['errors'][0]))) {
				update_option('_affinity_order_error', $arrResponse['arrResult']['errors'][0]);
			}
		}
		return $arrResponse['arrResult'];
	}
	
	public static function getNowOrderDetails() {
		$arrResponse = self::callMethodWithJsonContent(self::ORDER_UPDATE_ENDPOINT, array('from'=>str_replace('+00:00', '', 
				gmdate('c', time() - (3600*48))).'.000Z', 'to'=>str_replace('+00:00', '', gmdate('c', time() + (3600*48))).'.000Z'), true, 'GET');
		return $arrResponse['arrResult'];
	}
	
	/*
	 * @return [
	 *	'headers' => $httpResponseHeaders, 
	 *  'success' = true | false, 
	 *	'arrResult' => arrWithResultJsonDecoded
	 * ]
	 */
	private static function callMethodWithJsonContent($endPoint, $arrToBeConvertedToParameters, $needsAuthentication = true, $method = 'GET') {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceUtils.php");
		$endPoint = self::getServiceUrl() . $endPoint;
		$result = AffinityEcommerceUtils::callMethodWithJsonContent($endPoint, $arrToBeConvertedToParameters, array('method' => $method));
		
		//Auth Denied
		if(($result["httpResponseCode"] == 401 || $result["httpResponseCode"] == 403) && $needsAuthentication) {
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
			AffinityDataLayer::saveOption(self::LAST_COMMAND_RETURNED_ACCESS_FORBIDDEN_OPTION, true);
			AffinityEcommerceUtils::redirectToAffinityAuthenticationPage();
		}
		
		return $result;
	}
	
	private static function getCurrentPushToken() {
		require_once(__DIR__ . "/AffinityEnc.php");
		return AffinityEnc::getToken();
	}
	
	private static function transformAffinityProductInServiceProduct($objAffinityProduct) {
		require_once(__DIR__ . "/../model/AffinityLog.php");
		
		$postedas = get_post_meta($objAffinityProduct->id, '_affinity_posted_as', true);
		if ((!empty($postedas)) && $postedas == 'multi' && (!empty($objAffinityProduct->ebayListingID))) {
			// Emergency delete
			
			$endPoint = self::END_MULTIVARIATION_LISTING_ENDPOINT . $objAffinityProduct->sku;
			$arrRequest = array();
			$arrRequest["groupId"] = $objAffinityProduct->sku;
			$arrRequest["itemId"] = $objAffinityProduct->ebayListingID;
			
			$arrResponse = self::callMethodWithJsonContent($endPoint, $arrRequest, true, 'DELETE');
			$arrResult = $arrResponse['arrResult'];
			
			if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
				AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Ended MultiVariation Listing (due to type change)", print_r($arrResponse, true));
			}
			else {
				AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Failed Ending MultiVariation Listing (due to type change)", print_r($arrResponse, true));
			}
		}
		
		$props = self::getArrItemSpecifics($objAffinityProduct);
		if (is_array($props)) {
			array_walk_recursive($props, array('AffinityBackendService', 'stripInvalidCharacterArr'));
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'mpn') {
				$found = true;
				$prodIndent['mpn'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'MPN',
					'values' => array('Does Not Apply')
			);
			$prodIndent['mpn'] = array('Does Not Apply');
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'upc') {
				$found = true;
				$prodIndent['upc'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'UPC',
					'values' => array('Does Not Apply')
			);
			$prodIndent['upc'] = array('Does Not Apply');
		}
			
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'brand') {
				$found = true;
				$prodIndent['brand'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'Brand',
					'values' => array('Does Not Apply')
			);
			$prodIndent['brand'] = array('Does Not Apply');
		}
		
		$return = array(
			"sku" => $objAffinityProduct->sku,
			"syncRequestType" => self::$mappingProductStatusToServiceRequestType[$objAffinityProduct->updateStatus],
			"product" => array(
				"localizedProductDetails" => array(
					array(
						"title" => self::stripInvalidCharacter($objAffinityProduct->title),
						"description" => self::stripInvalidCharacter(self::getDescription($objAffinityProduct)),
						"properties" => $props
					)
				),
				"imageLinks" => self::getArrImageUrls($objAffinityProduct, true, $objAffinityProduct->ebayCategoryId),
				"productIdentifiers" => $prodIndent
			),
			"offer" => array(
				"ebayOfferDetails" => array(array(
					"categoryIdentifier" => $objAffinityProduct->ebayCategoryId,
					"pricingDetails" => array(
						"price" => array(
							"value" => number_format($objAffinityProduct->price, 2, '.', ''),
							"currency" => "AUD"
						)
					),
					"listingPolicies" => array(
						"policies" => array(
							"SHIPPING_POLICY_ID" => $objAffinityProduct->shippingPolicy,
							"RETURN_POLICY_ID" => get_option('ebayaffinity_returns_profile_id'),
							"PAYMENT_POLICY_ID" => get_option('ebayaffinity_pricing_profile_id'),
						)
					),
					"useCatalogProductDetails" => false,
					"listingDescription" => self::stripInvalidCharacter($objAffinityProduct->listingDescription),
				))
			),
			"availability" => array(
				"globalShipToHomeQuantity" => $objAffinityProduct->qtyAvailable
			)
		);
		
		if ((!empty($objAffinityProduct->length)) || (!empty($objAffinityProduct->width)) || (!empty($objAffinityProduct->height))) {
			$dunit = get_option('woocommerce_dimension_unit');
			$m = 1;
			switch ($dunit) {
				case 'm':
					$dunit = 'METER';
					break;
				case 'cm':
					$dunit = 'CENTIMETER';
					break;
				case 'mm':
					$m = 0.1;
					$dunit = 'CENTIMETER';
					break;
				case 'in':
					$dunit = 'INCH';
					break;
				case 'yd':
					$m = 3;
					$dunit = 'FEET';
					break;
			}
			
			if (empty($return['additionalInformation'])) {
				$return['additionalInformation'] = array();
			}
			if (empty($return['additionalInformation']['packageWeightAndDimensions'])) {
				$return['additionalInformation']['packageWeightAndDimensions'] = array();
			}
			$return['additionalInformation']['packageWeightAndDimensions']['dimension'] = array(
					"width" => $objAffinityProduct->width*$m,
					"height" => $objAffinityProduct->height*$m,
					"length" => $objAffinityProduct->length*$m,
					"unit" => $dunit
			);
		}
		
		if ((!empty($objAffinityProduct->weight))) {
			$wunit = get_option('woocommerce_weight_unit');
			switch ($wunit) {
				case 'kg':
					$wunit = 'KILOGRAM';
					break;
				case 'g':
					$wunit = 'GRAM';
					break;
				case 'lbs':
					$wunit = 'POUND';
					break;
				case 'oz':
					$wunit = 'OUNCE';
					break;
			}
			if (empty($return['additionalInformation'])) {
				$return['additionalInformation'] = array();
			}
			if (empty($return['additionalInformation']['packageWeightAndDimensions'])) {
				$return['additionalInformation']['packageWeightAndDimensions'] = array();
			}
			$return['additionalInformation']['packageWeightAndDimensions']['weight'] = array(
					"value" => floatval($objAffinityProduct->weight),
					"unit" => $wunit
			);
		}
		
		$condition = $objAffinityProduct->condition;
		if (empty($return['additionalInformation'])) {
			$return['additionalInformation'] = array();
		}
		$return['additionalInformation']['conditionDetails'] = array('condition' => $condition);
		
		if ($objAffinityProduct->price < $objAffinityProduct->retailPrice) {
			$return['offer']['ebayOfferDetails'][0]['pricingDetails']['strikeThroughPrice'] = array(
					"value" => number_format($objAffinityProduct->retailPrice, 2, '.', ''),
					"currency" => "AUD"
			);
		}
		
		update_post_meta($objAffinityProduct->id, '_affinity_posted_as', 'single');
		
		return $return;
	}
	
	private static function transformAffinityProductInServiceProductVariation($objAffinityProduct) {
		$postedas = get_post_meta($objAffinityProduct->id, '_affinity_posted_as', true);
		
		if ((!empty($postedas)) && $postedas == 'single' && (!empty($objAffinityProduct->ebayListingID))) {
			// Emergency delete
			
			$endPoint = self::END_LISTING_ENDPOINT . $objAffinityProduct->sku;
			$arrRequest = array();
			
			$arrResponse = self::callMethodWithJsonContent($endPoint, $arrRequest, true, 'DELETE');
			$arrResult = $arrResponse['arrResult'];
			
			if($arrResponse['httpResponseCode'] == 200 || $arrResponse['httpResponseCode'] == 201) {
				AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Ended Listing (due to type change)", print_r($arrResponse, true));
			}
			else {
				AffinityLog::saveLog(AffinityLog::TYPE_ERROR, "Failed Ending Listing (due to type change)", print_r($arrResponse, true));
			}
		}
		
		$arrVariationsToIncludeOnInventory = $objAffinityProduct->getVariationsShouldBeSynchronisedToEbay();
		$arrServiceVariantProduct = array();
		$arrSkusToNotBeRepeated = array();
		foreach($arrVariationsToIncludeOnInventory as $objVariantProduct) {
			$arrServiceVariantProduct = array_merge($arrServiceVariantProduct, self::getArrVariantProductWithDisabledVariants($objVariantProduct));
			
			foreach($arrServiceVariantProduct as $objServiceVariantProduct) {
				$arrSkusToNotBeRepeated[] = $objServiceVariantProduct['sku'];
			}
		}
		
		$arrServiceVariantProduct = array_merge($arrServiceVariantProduct, self::getArrDeletedVariants($objAffinityProduct, $arrSkusToNotBeRepeated));
		

		$a = self::getArrItemSpecifics($objAffinityProduct, "arrAllVariationsItemSpecifics");
		$b = self::getArrItemSpecifics($objAffinityProduct);
		foreach ($a as $k=>$c) {
			foreach ($b as $kk=>$d) {
				if (strtolower($c['name']) === strtolower($d['name'])) {
					unset($b[$kk]);
				}
			}
		}
		$b = array_values($b);
		
		$arrPicturesVariesOn = array();
		foreach ($a as $c) {
			$arrPicturesVariesOn[] = $c['name'];
		}		
		
		// We need to flatten $arrServiceVariantProduct !
		
		$tmp_arrServiceVariantProduct = array();
		foreach ($arrServiceVariantProduct as $k=>$v) {
			$lengths = array();
			$lengths2 = array();
			$propnames = array();
			foreach ($v['product']['localizedProductDetails'][0]['properties'] as $kk=>$vv) {
				if (count($vv['values']) > 1) {
					$lengths2 = $lengths;
					$lengths = array();
					
					$propnames[] = $vv['name'];

					if (!empty($lengths2)) {
						foreach ($lengths2 as $kkk=>$vvv) {
							foreach ($vv['values'] as $val) {
								$lengths[] = $vvv.'------------'.$val;
							}
						}
					} else {
						foreach ($vv['values'] as $val) {
							$lengths[] = $val;
						}
					}
					unset($v['product']['localizedProductDetails'][0]['properties'][$kk]);
				}
			}

			$v['product']['localizedProductDetails'][0]['properties'] = array_values($v['product']['localizedProductDetails'][0]['properties']);
			if (empty($lengths) && empty($propnames)) {
				$tmp_arrServiceVariantProduct[] = $v;
			} else {
				foreach ($lengths as $el) {
					$v2 = $v;
					$vals = explode('------------', $el);

					foreach ($vals as $kkkk=>$vvvv) {
						$v2['product']['localizedProductDetails'][0]['properties'][] = array('name' => $propnames[$kkkk], 'values' => array($vvvv));
					}
					
					$v2['sku'] .= '--'.substr(md5($el), 0, 10);
					
					$tmp_arrServiceVariantProduct[] = $v2;
				}
			}
		}
		
		$arrServiceVariantProduct = $tmp_arrServiceVariantProduct;
		
		$props = $b;
		array_walk_recursive($props, array('AffinityBackendService', 'stripInvalidCharacterArr'));
		
		$variationSpecifications = $a;
		array_walk_recursive($variationSpecifications, array('AffinityBackendService', 'stripInvalidCharacterArr'));
		
		array_walk_recursive($arrPicturesVariesOn, array('AffinityBackendService', 'stripInvalidCharacterArr'));
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'mpn') {
				$found = true;
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'MPN',
					'values' => array('Does Not Apply')
			);
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'upc') {
				$found = true;
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'UPC',
					'values' => array('Does Not Apply')
			);
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'brand') {
				$found = true;
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'Brand',
					'values' => array('Does Not Apply')
			);
		}
		
		$return = array(
			"groupId" => $objAffinityProduct->sku,
			"imageLinks" => self::getArrImageUrls($objAffinityProduct, true, $objAffinityProduct->ebayCategoryId),
			"localizedGroupDetails" => array(
				array(
					"groupInformation" => array(
						"title" => self::stripInvalidCharacter($objAffinityProduct->title),
						"description" => self::stripInvalidCharacter(str_replace('class="ebayaffinity_producttable_buynow"', 'class="ebayaffinity_producttable_buynow" style="display: none;"', $objAffinityProduct->listingDescription)),
						"properties" => $props,
					),
					"variationInformation" => array(
						"pictureVariesOn" => $arrPicturesVariesOn,
						"variationSpecifications" => $variationSpecifications,
					),
					"locale" => "en_AU"
				)
			),
			"inventory" => $arrServiceVariantProduct
		);
		
		update_post_meta($objAffinityProduct->id, '_affinity_posted_as', 'multi');
		
		return $return;
	}
	
	private static function getArrVariantProduct($objAffinityProduct) {
		$objAffinityProduct->arrMObjItemSpecifics = AffinityItemSpecific::transformFromArrEcommerceItemSpecifics($objAffinityProduct->variationObjMainProduct->objEcommerceProduct->arrEcommerceItemSpecifics);
		$objAffinityProduct->arrObjItemSpecificsEbayProductIdentifiers = AffinityItemSpecific::getArrEbayProductIdentifiers($objAffinityProduct->variationObjMainProduct->objEcommerceProduct);
		$objAffinityProduct->ebayCategoryId = $objAffinityProduct->variationObjMainProduct->ebayCategoryId;
		
		$a = self::getArrItemSpecifics($objAffinityProduct, "arrItemSpecificsOfCurrentVariation");
		$b = self::getArrItemSpecifics($objAffinityProduct, 'arrMObjItemSpecifics');
		foreach ($a as $k=>$c) {
			foreach ($b as $kk=>$d) {
				if (strtolower($c['name']) === strtolower($d['name'])) {
					unset($b[$kk]);
				}
			}
		}
		$b = array_values($b);
		
		if ((!empty($objAffinityProduct->objMainImage)) && (!empty($objAffinityProduct->objMainImage->fullUrl))) {
			$noautohttp = get_option('ebayaffinity_noautohttp');
			
			if (empty($noautohttp)) {
				$objAffinityProduct->objMainImage->fullUrl = str_replace('https://', 'http://', $objAffinityProduct->objMainImage->fullUrl);
			}
		}
		
		$props = array_merge($a, $b);
		if (is_array($props)) {
			array_walk_recursive($props, array('AffinityBackendService', 'stripInvalidCharacterArr'));
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'mpn') {
				$found = true;
				$prodIndent['mpn'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'MPN',
					'values' => array('Does Not Apply')
			);
			$prodIndent['mpn'] = array('Does Not Apply');
		}
		
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'upc') {
				$found = true;
				$prodIndent['upc'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'UPC',
					'values' => array('Does Not Apply')
			);
			$prodIndent['upc'] = array('Does Not Apply');
		}
			
		$found = false;
		foreach ($props as $k => $v) {
			if (strtolower($v['name']) == 'brand') {
				$found = true;
				$prodIndent['brand'] = $v['values'];
			}
		}
		if (!$found) {
			$props[] = array(
					'name' => 'Brand',
					'values' => array('Does Not Apply')
			);
			$prodIndent['brand'] = array('Does Not Apply');
		}
		
		$return = array(
			"sku" => $objAffinityProduct->sku,
			"product" => array(
				"imageLinks" => !empty($objAffinityProduct->objMainImage) && (!empty($objAffinityProduct->objMainImage->fullUrl)) ? array($objAffinityProduct->objMainImage->fullUrl) : null,
				"localizedProductDetails" => array(
					array(
						"title" => self::stripInvalidCharacter("Variation " . $objAffinityProduct->sku),
						"description" => self::stripInvalidCharacter(self::getDescription($objAffinityProduct->variationObjMainProduct)),
						"properties" => $props
					)
				),
				"productIdentifiers" => $prodIndent
			),
			"offer" => array(
				"ebayOfferDetails" => array(
					array(
						"categoryIdentifier" => intval($objAffinityProduct->variationObjMainProduct->ebayCategoryId),
						"listingDescription" => self::stripInvalidCharacter(str_replace('class="ebayaffinity_producttable_buynow"', 'class="ebayaffinity_producttable_buynow" style="display: none;"', $objAffinityProduct->variationObjMainProduct->listingDescription)),
						"pricingDetails" => array(
							"price" => array(
								"value" => number_format($objAffinityProduct->price, 2, '.', ''),
								"currency" => "AUD"
							)
						),
						"listingPolicies" => array(
							"policies" => array(
								"SHIPPING_POLICY_ID" => $objAffinityProduct->variationObjMainProduct->shippingPolicy,
								"RETURN_POLICY_ID" => get_option('ebayaffinity_returns_profile_id'),
								"PAYMENT_POLICY_ID" => get_option('ebayaffinity_pricing_profile_id'),
							)
						),
					)
				)
			),
			"availability" => array(
				"globalShipToHomeQuantity" => $objAffinityProduct->qtyAvailable
			)
		);
		
		if ((!empty($objAffinityProduct->length)) || (!empty($objAffinityProduct->width)) || (!empty($objAffinityProduct->height))) {
			$dunit = get_option('woocommerce_dimension_unit');
			$m = 1;
			switch ($dunit) {
				case 'm':
					$dunit = 'METER';
					break;
				case 'cm':
					$dunit = 'CENTIMETER';
					break;
				case 'mm':
					$m = 0.1;
					$dunit = 'CENTIMETER';
					break;
				case 'in':
					$dunit = 'INCH';
					break;
				case 'yd':
					$m = 3;
					$dunit = 'FEET';
					break;
			}
			
			if (empty($return['additionalInformation'])) {
				$return['additionalInformation'] = array();
			}
			if (empty($return['additionalInformation']['packageWeightAndDimensions'])) {
				$return['additionalInformation']['packageWeightAndDimensions'] = array();
			}
			$return['additionalInformation']['packageWeightAndDimensions']['dimension'] = array(
					"width" => $objAffinityProduct->width*$m,
					"height" => $objAffinityProduct->height*$m,
					"length" => $objAffinityProduct->length*$m,
					"unit" => $dunit
			);
		}
		
		if ((!empty($objAffinityProduct->weight))) {
			$wunit = get_option('woocommerce_weight_unit');
			switch ($wunit) {
				case 'kg':
					$wunit = 'KILOGRAM';
					break;
				case 'g':
					$wunit = 'GRAM';
					break;
				case 'lbs':
					$wunit = 'POUND';
					break;
				case 'oz':
					$wunit = 'OUNCE';
					break;
			}
			if (empty($return['additionalInformation'])) {
				$return['additionalInformation'] = array();
			}
			if (empty($return['additionalInformation']['packageWeightAndDimensions'])) {
				$return['additionalInformation']['packageWeightAndDimensions'] = array();
			}
			$return['additionalInformation']['packageWeightAndDimensions']['weight'] = array(
					"value" => floatval($objAffinityProduct->weight),
					"unit" => $wunit
			);
		}
		
		$condition = $objAffinityProduct->variationObjMainProduct->condition;
		if (empty($return['additionalInformation'])) {
			$return['additionalInformation'] = array();
		}
		$return['additionalInformation']['conditionDetails'] = array('condition' => $condition);
		
		if ($objAffinityProduct->price < $objAffinityProduct->retailPrice) {
			$return['offer']['ebayOfferDetails'][0]['pricingDetails']['strikeThroughPrice'] = array(
					"value" => number_format($objAffinityProduct->retailPrice, 2, '.', ''),
					"currency" => "AUD"
			);
		}
		
		return $return;
	}
	
	private static function getArrVariantProductWithDisabledVariants($objAffinityProduct) {
		$objCurrentVariant = self::getArrVariantProduct($objAffinityProduct);
		
		$return = array();
		$return[] = $objCurrentVariant;
		
		if(is_array($objAffinityProduct->tempArrInactiveVariantDetails) && count($objAffinityProduct->tempArrInactiveVariantDetails) > 0) {
			
			require_once(__DIR__ . "/../model/AffinityVariantDetails.php");
			foreach($objAffinityProduct->tempArrInactiveVariantDetails as $objVariantDetails) {
				$objDisabledVariant = $objCurrentVariant;
				$objDisabledVariant['sku'] = $objVariantDetails->ebaySku;
				$objDisabledVariant['product']['localizedProductDetails'][0]['title'] = "Variation " . $objVariantDetails->ebaySku;
				$objDisabledVariant['product']['localizedProductDetails'][0]['properties'] = self::getArrItemSpecifics($objVariantDetails, "arrItemSpecifics");
				$objDisabledVariant['availability']['globalShipToHomeQuantity'] = 0;
				$return[] = $objDisabledVariant;
			}
		}
		
		return $return;
	}
	
	private static function getArrDeletedVariants($objAffinityProduct, $arrSkusToNotInclude = array()) {
		global $wpdb;
		$objCurrentVariant = self::getArrVariantProduct($objAffinityProduct);
		$return = array();
		
		$arrDeletedVariantDetails = $objAffinityProduct->getDeletedVariantDetails();
		
		if(is_array($arrDeletedVariantDetails) && count($arrDeletedVariantDetails) > 0) {
			require_once(__DIR__ . "/../model/AffinityVariantDetails.php");
			foreach($arrDeletedVariantDetails as $objVariantDetails) {
				if(in_array($objVariantDetails->ebaySku, $arrSkusToNotInclude)) {
					continue;
				}
				
				$objDeletedVariant = $objCurrentVariant;
				$objDeletedVariant['sku'] = $objVariantDetails->ebaySku;
				$objDeletedVariant['product']['localizedProductDetails'][0]['title'] = "Variation " . $objVariantDetails->ebaySku;
				$objDeletedVariant['product']['localizedProductDetails'][0]['properties'] = self::getArrItemSpecifics($objVariantDetails, "arrItemSpecifics");

				$counte = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) AS counte FROM ".$wpdb->prefix."woocommerce_order_itemmeta 
						WHERE meta_key = '_variation_id' AND meta_value = %s LIMIT 1", $objVariantDetails->variantProductId));
				
				if ($counte > 0) {
					$objDeletedVariant['availability']['globalShipToHomeQuantity'] = 0;
				} else {
					$objDeletedVariant['availability']['globalShipToHomeQuantity'] = -1;
				}
				
				$return[] = $objDeletedVariant;
			}
		}
		
		return $return;
	}
	
	private static function getArrImageUrls($objAffinityProduct, $blockadult=false, $categoryId=-1) {
		$arrImageUrls = array();
		
		$noautohttp = get_option('ebayaffinity_noautohttp');
		
		$i = 1;
		
		if ($blockadult) {
			$adulturl = str_replace('service/assets/adult.jpg', 'assets/adult.jpg', plugins_url('assets/adult.jpg', __FILE__));
		
			if (!empty($categoryId)) {
				if ($categoryId == 176997 || $categoryId == 176996) {
					// Adult!
					if (empty($noautohttp)) {
						$arrImageUrls[] = str_replace('https://', 'http://', $adulturl);
					} else {
						$arrImageUrls[] = $adulturl;
					}
					$i++;
				}
			}
		
		}
		$noautohttp = get_option('ebayaffinity_noautohttp');
		
		if (empty($noautohttp)) {
			$arrImageUrls[] = str_replace('https://', 'http://', $objAffinityProduct->objMainImage->fullUrl);
		} else {
			$arrImageUrls[] = $objAffinityProduct->objMainImage->fullUrl;
		}
		foreach($objAffinityProduct->arrObjAdditionalImages as $objAdditionalImage) {
			if (empty($noautohttp)) {
				$arrImageUrls[] = str_replace('https://', 'http://', $objAdditionalImage->fullUrl);
			} else {
				$arrImageUrls[] = $objAdditionalImage->fullUrl;
			}
			$i++;
			
			//Limit the listing to 12 images
			if($i > 12) {
				break;
			}
		}
		
		return $arrImageUrls;
	}
	
	private static function getArrItemSpecifics($objAffinityProduct, $arrayToIterate = "arrObjItemSpecifics", $allowMultipleValues = true) {
		$arrObjItemSpecifics = $objAffinityProduct->$arrayToIterate;
		
		$arrItemSpecifics = array();
		foreach($arrObjItemSpecifics as $objItemSpecific) {
			$arrItemSpecific = array();
			$arrItemSpecific["name"] = AffinityEcommerceItemSpecific::getAttributeTaxonomyLabel($objItemSpecific->name);
			
			if($allowMultipleValues) {
				$arrItemSpecific["values"] = $objItemSpecific->arrValues;
			}
			else {
				$arrItemSpecific["values"] = is_array($objItemSpecific->arrValues) ? array($objItemSpecific->arrValues[0]) : $objItemSpecific->arrValues;
			}
			
			$arrItemSpecifics[] = $arrItemSpecific;
		}
		
		return $arrItemSpecifics;
	}
	
	private static function getArrayProductIdentifiers($objAffinityProduct) {
		global $affinity_apirecses;
		$req = array("isbn", "upc", "ean", "mpn", "gtin");
		$arrProductIdentifiers = array();
		
		foreach($objAffinityProduct->arrObjItemSpecificsEbayProductIdentifiers as $objItemSpecific) {
			$propertyName = strtolower($objItemSpecific->name);
			$arrProductIdentifiers[$propertyName] = $objItemSpecific->arrValues;
		}
		
		if (empty($affinity_apirecses[$objAffinityProduct->ebayCategoryId])) {
			$affinity_apirecs = array();
			$atts = self::getAttributesForCategories(array($objAffinityProduct->ebayCategoryId));
			if (!empty($atts['arrResult']['data'])) {
				foreach ($atts['arrResult']['data'] as $data) {
					if (!empty($data['NameRecommendation'])) {
						foreach ($data['NameRecommendation'] as $namerec) {
							$affinity_apirecs[strtolower($namerec['Name'])] = $namerec['Name'];
						}
					}
				}
			}
			$affinity_apirecses[$objAffinityProduct->ebayCategoryId] = $affinity_apirecs;
		} else {
			$affinity_apirecs = $affinity_apirecses[$objAffinityProduct->ebayCategoryId];
		}
		
		foreach ($req as $reqe) {
			if (!empty($affinity_apirecs[$reqe])) {
				$found = false;
				foreach ($arrProductIdentifiers as $k=>$v) {
					if (strtolower($k) === $reqe) {
						$found = true;
					}
				}
				if (!$found) {
					$arrProductIdentifiers[strtolower($reqe)] = array('Does Not Apply');
				}
			}
		}
		
		if(count($arrProductIdentifiers) < 1) {
			return null;
		}
		
		return $arrProductIdentifiers;
	}
	
	private static function getDescription($objAffinityProduct) {
		$desc = trim($objAffinityProduct->description);
		$sdesc = strip_tags($objAffinityProduct->shortDescription);
		$sdesc = trim($sdesc);
		
		if (!affinity_empty($sdesc)) {
			$desc = $sdesc;
		}
		
		$desc = strip_tags($desc);
		if (strlen($desc) > 65) {
			$desc = substr($desc, 0, 62).'...';
		}
		
		return $desc;
	}

    private static function stripInvalidCharacter($text) {
    	$text = htmlspecialchars_decode(htmlspecialchars($text, ENT_IGNORE, 'UTF-8'));
    	$text = preg_replace('/[^\PC\s]/u', '', $text); // Remove all that aren't "not control characters nor spaces".
    	return $text;
    }
    
    private static function stripInvalidCharacterArr(&$text) {
    	$text = htmlspecialchars_decode(htmlspecialchars($text, ENT_IGNORE, 'UTF-8'));
    	$text = preg_replace('/[^\PC\s]/u', '', $text); // Remove all that aren't "not control characters nor spaces".
    }
}
