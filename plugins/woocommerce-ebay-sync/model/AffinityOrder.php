<?php
class AffinityOrder {
	
	const UPDATE_STATUS_NEEDS_TO_BE_SAVED = 1;
	const UPDATE_STATUS_SYNCHRONISED = 2;
	const UPDATE_STATUS_SYNC_ERROR = 3;
	const UPDATE_STATUS_NEVER_PROCESSED = 4;
	
	private static $_arrPersistentFields = array(
		"isEbayOrder" => "affinity_ebayorder",
		"wasSent" => "affinity_marked_as_sent",
		"trackingNumber" => "affinity_tracking_number",
		"carrierName" => "affinity_carrier_name",
		"arrWarnings" => "affinity_order_warnings",
		"ebayTransactionId" => "affinity_transaction_id",
		"ebayItemId" => "affinity_item_id",
		"ebayPurchaseOrderId" => "affinity_purchase_order_id",
		"ebayBuyerId" => "affinity_order_buyer_id",
		"updateStatus" => "affinity_order_update_status"
	);
	
	private static $_arrPersistentFieldsDefaults = array(
		"isEbayOrder" => false,
		"wasSent" => false,
		"arrWarnings" => array(),
		"trackingNumber" => "",
		"carrierName" => "",
		"ebayTransactionId" => "",
		"ebayPurchaseOrderId" => "",
		"ebayItemId" => "",
		"ebayBuyerId" => "",
	);
	
	public $id;
	
	public $productId;
	public $productDescription;
	public $qty;
	public $productUnitPrice;
	public $totalLinePrice;
	public $shippingDescription;
	public $shippingPrice;
	public $totalPaid;
	
	public $firstName;
	public $lastName;
	public $company;
	public $email;
	public $phone;
	public $addressLine1;
	public $addressLine2;
	public $city;
	public $state;
	public $postCode;
	public $country;
	
	public $isEbayOrder;
	public $wasSent;
	public $trackingNumber;
	public $carrierName;
	public $ebayItemId;
	public $ebayTransactionId;
	public $ebayPurchaseOrderId;
	public $ebayBuyerId;
	public $arrWarnings = array();
	public $arrErrors = array();
	public $objEcommerceOrder;
	
	public $updateStatus;
	
	public static function orderReceivedFromEbay($arrRequest) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityEcommerceOrder.php");
		
		$arrObjAffinityOrder = self::transformFromAffinityServerRequest($arrRequest);
		
		foreach($arrObjAffinityOrder as $objAffinityOrder) {
			$objEcommerceOrder = AffinityEcommerceOrder::createOrderReceivedFromEbay($objAffinityOrder);
			$objAffinityOrder->id = $objEcommerceOrder->id;

			self::saveOrderAttribute($objAffinityOrder->id, "isEbayOrder", true);
			self::saveOrderAttribute($objAffinityOrder->id, "ebayItemId", implode(',',$objAffinityOrder->ebayItemId));
			self::saveOrderAttribute($objAffinityOrder->id, "ebayTransactionId", implode(',',$objAffinityOrder->ebayTransactionId));
			self::saveOrderAttribute($objAffinityOrder->id, "ebayPurchaseOrderId", $objAffinityOrder->ebayPurchaseOrderId);
			self::saveOrderAttribute($objAffinityOrder->id, "ebayBuyerId", $objAffinityOrder->ebayBuyerId);
			self::saveOrderAttribute($objAffinityOrder->id, "arrWarnings", $objAffinityOrder->arrWarnings);
			self::saveOrderAttribute($objAffinityOrder->id, "updateStatus", self::UPDATE_STATUS_SYNCHRONISED);
		}
		
		return $arrObjAffinityOrder;
	}
	
	public static function orderChanged($objEcommerceOrder) {
		self::saveOrderShippingDetailsFormData($objEcommerceOrder);
		
		$objAffinityOrder = self::transformFromEcommerceOrder($objEcommerceOrder);
		
		if(!$objAffinityOrder->isEbayOrder) {
			return;
		}
		
		self::saveOrderAttribute($objAffinityOrder->id, "updateStatus", self::UPDATE_STATUS_NEEDS_TO_BE_SAVED);
		
		require_once(__DIR__ . "/../service/AffinityBackendService.php");
		AffinityBackendService::sendOrderShippingDetails($objAffinityOrder);
	}
	
	public static function orderUpdateReturnReceived($orderId, $arrWarnings, $arrErrors) {
		require_once(__DIR__ . "/AffinityLog.php");
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Processing Order Return", "Order ID: $orderId - Warnings: " . print_r($arrWarnings, true) . " - Errors: " . print_r($arrErrors, true));
		
		self::saveProductAttribute($orderId, "updateStatus", self::UPDATE_STATUS_SYNCHRONISED);
	}
	
	public static function getOrderIdAssociatedWithItemAndTransactionID($itemId, $transactionId) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		$existingOrderIdWithTransactionId = AffinityDataLayer::findObjectIdWithGivenData(self::$_arrPersistentFields['ebayTransactionId'], $transactionId);
		
		
		if(!empty($existingOrderIdWithTransactionId)) {
			$existingOrderItemId = AffinityDataLayer::getDataAssociatedToEcommerceObject($existingOrderIdWithTransactionId, self::$_arrPersistentFields['ebayItemId']);
			
			if($existingOrderItemId == $itemId) {
				return $existingOrderIdWithTransactionId;
			}
		}
		
		return null;
	}
	
	private static function saveOrderAttribute($orderId, $attributeName, $attributeValue) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		AffinityDataLayer::saveDataAssociatedToEcommerceObject($orderId, self::$_arrPersistentFields[$attributeName], $attributeValue);
	}
	
	private static function saveOrderShippingDetailsFormData($objEcommerceOrder) {	
		self::saveOrderAttribute($objEcommerceOrder->id, 'wasSent', empty($_POST['_affinity_marked_as_sent'])?'':$_POST['_affinity_marked_as_sent']);
		
		if (!empty($_POST['_affinity_tracking_number'])) {
			self::saveOrderAttribute($objEcommerceOrder->id, 'trackingNumber', $_POST['_affinity_tracking_number']);
		}
		
		if (!empty($_POST['_affinity_carrier_name'])) {
			self::saveOrderAttribute($objEcommerceOrder->id, 'carrierName', $_POST['_affinity_carrier_name']);
		}
	}
	
	private static function transformFromAffinityServerRequest($arrRequest) {
		require_once(__DIR__ . "/AffinityProduct.php");
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		$arrReturn = array();
		
		foreach($arrRequest['data']['OrderArray']['Order'] as $arrOrderInRequestFormat) {
			$objAffinityOrder = new AffinityOrder();
			$objAffinityOrder->ebayPurchaseOrderId = $arrOrderInRequestFormat['OrderID'];
			$objAffinityOrder->ebayBuyerId = $arrOrderInRequestFormat['BuyerUserID'];
			
			$qty = 0;
			
			$objAffinityOrder->email = '';
			
			foreach ($arrOrderInRequestFormat['TransactionArray']['Transaction'] as $k=>$requestItem) {
				if (empty($requestItem['TransactionID'])) {
					continue;
				}
				
				if (substr($requestItem['Item']['SKU'], 0, 7) == 'woobay-') {
					//
				} else {
					continue;
				}
				
				if (empty($objAffinityOrder->ebayItemId)) {
					$objAffinityOrder->ebayItemId = array();
				}
				$objAffinityOrder->ebayItemId['_'.$k] = $requestItem['Item']['ItemID'];
				
				if ((!empty($requestItem['Buyer'])) && (!empty($requestItem['Buyer']['Email']))) {
					$objAffinityOrder->email = $requestItem['Buyer']['Email'];
				}
				
				if (empty($objAffinityOrder->price)) {
					$objAffinityOrder->price = array();
				}
				$objAffinityOrder->price['_'.$k] = $requestItem['TransactionPrice']['value'];
				
				if (empty($objAffinityOrder->ebayTransactionId)) {
					$objAffinityOrder->ebayTransactionId = array();
				}
				$objAffinityOrder->ebayTransactionId['_'.$k] = $requestItem['TransactionID'];
				
				if (empty($objAffinityOrder->qty)) {
					$objAffinityOrder->qty = array();
				}
				$qty += $requestItem['QuantityPurchased'];
				$objAffinityOrder->qty['_'.$k] = $requestItem['QuantityPurchased'];
				
				if (empty($objAffinityOrder->productDescription)) {
					$objAffinityOrder->productDescription = array();
				}
				$objAffinityOrder->productDescription['_'.$k] = "eBay Listing ID: " . $requestItem['Item']['ItemID'] . "; Qty: " . $requestItem['QuantityPurchased'] . "; Title: " . $requestItem['Item']['Title'];
				
				$variationId = 0;
				
				if (empty($objAffinityOrder->variationDetails)) {
					$objAffinityOrder->variationDetails = array();
				}
				
				if (!empty($requestItem['Variation']) && !empty($requestItem['Variation']['SKU'])) {
					$tt = explode('--', $requestItem['Variation']['SKU']);
					$variationId = AffinityProduct::getProductIdAssociatedToSku($tt[0]);
					if (empty($objAffinityOrder->variationDetails['_'.$k])) {
						$objAffinityOrder->variationDetails['_'.$k] = array();
					}
					foreach ($requestItem['Variation']['VariationSpecifics']['NameValueList'] as $vd) {
						$objAffinityOrder->variationDetails['_'.$k][$vd['Name']] = $vd['Value'][0];
					}
				}
				
				if (empty($objAffinityOrder->productId)) {
					$objAffinityOrder->productId = array();
				}
				
				if (empty($objAffinityOrder->variationId)) {
					$objAffinityOrder->variationId = array();
				}
				
				$productId = AffinityProduct::getProductIdAssociatedToListing($requestItem['Item']['ItemID']);
				if($productId > 0) {
					$objAffinityOrder->productId['_'.$k] = $productId;
				
					if ($variationId > 0) {
						$objAffinityOrder->variationId['_'.$k] = $variationId;
					}
				}
				else {
					require_once(__DIR__ . "/AffinityLog.php");
					AffinityLog::saveLog(AffinityLog::TYPE_WARNING, "Order Product Item hasn't been found on WooCommerce", "There's no order associated to the eBay Listing ID " . $requestItem['Item']['ItemID']);
					$objAffinityOrder->arrWarnings[] = "There's no product associated to the eBay Listing ID " . $requestItem['Item']['ItemID'];
				}
			}
			
			if ($qty == 0) {
				continue;
			}
			
			if (empty($objAffinityOrder->ebayTransactionId)) {
				continue;
			}
			
			$existingOrderWithEbayOrderId = self::getOrderIdAssociatedWithItemAndTransactionID(implode(',', $objAffinityOrder->ebayItemId), implode(',', $objAffinityOrder->ebayTransactionId));
				
			if(!empty($existingOrderWithEbayOrderId)) {
				continue;
				//throw new Exception("An order (ID: " . $existingOrderWithEbayOrderId . ") has already been placed with eBay Listing ID = " . $requestItem['Item']['ItemID'] . " and eBay Transaction ID = " . $requestItem['TransactionID']);
			}

			$names = explode(" ", trim($arrOrderInRequestFormat['ShippingAddress']['Name']));
			$firstName = $names[0];
			unset($names[0]);
			$lastName = implode(" ", $names);

			$objAffinityOrder->firstName = $firstName;
			$objAffinityOrder->lastName = $lastName;
			$objAffinityOrder->phone = $arrOrderInRequestFormat['ShippingAddress']['Phone'];
			$objAffinityOrder->addressLine1 = $arrOrderInRequestFormat['ShippingAddress']['Street1'];
			$objAffinityOrder->addressLine2 = $arrOrderInRequestFormat['ShippingAddress']['Street2'];
			$objAffinityOrder->city = $arrOrderInRequestFormat['ShippingAddress']['CityName'];
			$objAffinityOrder->state = $arrOrderInRequestFormat['ShippingAddress']['StateOrProvince'];
			switch ($objAffinityOrder->state) {
				case 'Australian Capital Territory':
					$objAffinityOrder->state = 'ACT';
					break;
				case 'New South Wales':
					$objAffinityOrder->state = 'NSW';
					break;
				case 'Northern Territory':
					$objAffinityOrder->state = 'NT';
					break;
				case 'Queensland':
					$objAffinityOrder->state = 'QLD';
					break;
				case 'South Australia':
					$objAffinityOrder->state = 'SA';
					break;
				case 'Tasmania':
					$objAffinityOrder->state = 'TAS';
					break;
				case 'Victoria':
					$objAffinityOrder->state = 'VIC';
					break;
				case 'Western Australia':
					$objAffinityOrder->state = 'WA';
					break;
				default:
			}
			
			$objAffinityOrder->postCode = $arrOrderInRequestFormat['ShippingAddress']['PostalCode'];
			$objAffinityOrder->country = $arrOrderInRequestFormat['ShippingAddress']['Country'];
			$objAffinityOrder->company = "";
			

			$objAffinityOrder->productUnitPrice = 0;
			$objAffinityOrder->totalLinePrice = 0;
			$objAffinityOrder->shippingPrice = 0;

			$objAffinityOrder->productUnitPrice = $arrOrderInRequestFormat['Subtotal']['value'] / $qty;
			$objAffinityOrder->totalLinePrice = $arrOrderInRequestFormat['Subtotal']['value'];
			$objAffinityOrder->shippingPrice = $arrOrderInRequestFormat['ShippingServiceSelected']['ShippingServiceCost']['value'];


			$shipsrv = $arrOrderInRequestFormat['ShippingServiceSelected']['ShippingService'];
			
			$shipstr = '';
			foreach ($arrOrderInRequestFormat['ShippingDetails']['ShippingServiceOptions'] as $v) {
				if ($shipsrv === $v['ShippingService']) {
					$shipstr =  '- Deliver until ' . $v['ShippingTimeMax'] . ' days';
					break;
				}
				if ($shipsrv === 'AU_standardShipping' && $v['ShippingService'] === 'AU_StandardDelivery') {
					$shipstr =  '- Deliver until ' . $v['ShippingTimeMax'] . ' days';
					break;
				}
				if ($shipsrv === 'AU_expeditedShipping' && $v['ShippingService'] === 'AU_ExpressDelivery') {
					$shipstr =  '- Deliver until ' . $v['ShippingTimeMax'] . ' days';
					break;
				}
			}
			
			$objAffinityOrder->shippingDescription = $arrOrderInRequestFormat['ShippingServiceSelected']['ShippingService'] . $shipstr;
			$objAffinityOrder->totalPaid = $objAffinityOrder->totalLinePrice + $objAffinityOrder->shippingPrice;
			
			$arrReturn[] = $objAffinityOrder;
		}
		
		return $arrReturn;
	}
	
	private static function transformFromEcommerceOrder($objEcommerceOrder) {
		require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
		
		$objAffinityOrder = new AffinityOrder();
		$objAffinityOrder->id = $objEcommerceOrder->id;
		$objAffinityOrder->objEcommerceOrder = $objEcommerceOrder;
				
		//Fetch persistent fields
		foreach(self::$_arrPersistentFields as $strObjFieldName => $strPersistentOptionName) {
			$optionValue = AffinityDataLayer::getDataAssociatedToEcommerceObject($objEcommerceOrder->id, $strPersistentOptionName);
			$objAffinityOrder->$strObjFieldName = !empty($optionValue) ? $optionValue : (empty(self::$_arrPersistentFieldsDefaults[$strObjFieldName])?'':self::$_arrPersistentFieldsDefaults[$strObjFieldName]);
		}
		
		return $objAffinityOrder;
	}
}
