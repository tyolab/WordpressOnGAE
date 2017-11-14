<?php
require_once(ABSPATH . 'wp-admin/includes/screen.php');

class AffinityEcommerceOrder {
	public $id;
	public $clientId;
	public $orderDate;
	public $modifiedDate;
	public $totalPrice;
	public $status;
	
	public static function get($id) {
		$objNativeOrder = new WC_Order($id);
		$objEcommerceOrder = self::transformNativeOrderIntoEcommerceOrder($objNativeOrder);
		return $objEcommerceOrder;
    }
	
	public static function createOrderReceivedFromEbay($objAffinityOrder) {
		require_once(__DIR__.'/../model/AffinityLog.php');
		AffinityLog::saveLog(AffinityLog::TYPE_DEBUG, "Creating Order in Ecommerce", print_r($objAffinityOrder, true));
		
		$address = array(
            'first_name' => $objAffinityOrder->firstName,
            'last_name'  => $objAffinityOrder->lastName,
            'company'    => "eBay Buyer ID: " . $objAffinityOrder->ebayBuyerId,
            'email'      => $objAffinityOrder->email,
            'phone'      => $objAffinityOrder->phone,
            'address_1'  => $objAffinityOrder->addressLine1,
            'address_2'  => $objAffinityOrder->addressLine2, 
            'city'       => $objAffinityOrder->city,
            'state'      => $objAffinityOrder->state,
            'postcode'   => $objAffinityOrder->postCode,
            'country'    => $objAffinityOrder->country
        );

        $objNativeOrder = wc_create_order();
        $objNativeOrder->set_address($address, 'billing');
        $objNativeOrder->set_address($address, 'shipping');
        
        if (method_exists($objNativeOrder, 'get_id')) {
        	$order_id = $objNativeOrder->get_id();
        } else {
        	$order_id = $objNativeOrder->id;
        }
        
        $orderTotal = 0;
        
        foreach ($objAffinityOrder->ebayItemId as $kk=>$v) {
        
			$options = array('eBay Item' => $objAffinityOrder->ebayItemId[$kk]);
	        
			if($objAffinityOrder->productId[$kk] > 0 && strpos($objAffinityOrder->productId[$kk], '-') === false) {
				if ($objAffinityOrder->variationId[$kk] > 0 && strpos($objAffinityOrder->variationId[$kk], '-') === false) {
					$prod = new WC_Product_Variation($objAffinityOrder->variationId[$kk]);
					$varrs = $prod->get_variation_attributes();
					if (!empty($objAffinityOrder->variationDetails[$kk])) {
						foreach ($objAffinityOrder->variationDetails[$kk] as $v=>$k) {
							$options[$v] = $objAffinityOrder->variationDetails[$kk][$v];
						}
					} else {
						foreach ($varrs as $v=>$k) {
							$options[$v] = $k;
						}
					}
				} else {
					if (!empty($objAffinityOrder->variationDetails[$kk])) {
						foreach ($objAffinityOrder->variationDetails[$kk] as $v=>$k) {
							$options[$v] = $objAffinityOrder->variationDetails[$kk][$v];
						}
					}
					
					$prod = wc_get_product($objAffinityOrder->productId[$kk]);
				}
				
				$totals = array(
						'subtotal' => $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk],
						'total' => $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk],
						'subtotal_tax' => 0,
						'tax' => 0
				);
				
				$objNativeOrder->add_product($prod, $objAffinityOrder->qty[$kk], array('variation' => $options, 'totals' => $totals));
				
				$allOrderItems = $objNativeOrder->get_items();
				$productItem = array_pop($allOrderItems);
				$orderTotal += $objNativeOrder->get_line_total($productItem);
			}
			else {
				if (class_exists('WC_Order_Item_Fee')) {
					$item = new WC_Order_Item_Fee();
					$item->set_props(array(
							'name' => $objAffinityOrder->productDescription[$kk],
							'tax_class' => 0,
							'total' => $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk],
							'total_tax' => 0,
							'taxes' => array(
									'total' => 0,
							),
							'order_id' => $order_id,
					));
					$item->save();
					$objNativeOrder->add_item($item);
				} else {
					$objProductAsFee = new stdClass();
					$objProductAsFee->name = $objAffinityOrder->productDescription[$kk];
					$objProductAsFee->amount = $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk];
					$objProductAsFee->taxable = false; //price already includes taxes
					$objProductAsFee->tax_data = array(); 
					$objNativeOrder->add_fee($objProductAsFee);
				}
				$orderTotal += $objAffinityOrder->price[$kk] * $objAffinityOrder->qty[$kk];
			}
        }
		
        if (class_exists('WC_Order_Item_Fee')) {
        	$item = new WC_Order_Item_Fee();
        	$item->set_props(array(
        			'name' => $objAffinityOrder->shippingDescription,
        			'tax_class' => 0,
        			'total' => $objAffinityOrder->shippingPrice,
        			'total_tax' => 0,
        			'taxes' => array(
        					'total' => 0,
        			),
        			'order_id' => $order_id,
        	));
        	$item->save();
        	$objNativeOrder->add_item($item);
        } else {
			$objShippingAsFee = new stdClass();
			$objShippingAsFee->name = $objAffinityOrder->shippingDescription;
			$objShippingAsFee->amount = $objAffinityOrder->shippingPrice;
			$objShippingAsFee->taxable = false; //price already includes taxes
			$objShippingAsFee->tax_data = array(); 
			$objNativeOrder->add_fee($objShippingAsFee);
        }
        
		$orderTotal += $objAffinityOrder->shippingPrice;
		
		$objNativeOrder->set_total($orderTotal);
		
		$objNativeOrder->update_status('processing', 'New Order Received from eBay!');
		
		if (function_exists('wc_reduce_stock_levels')) {
			wc_reduce_stock_levels($order_id);
		} else {
			$objNativeOrder->reduce_order_stock();
		}
		
		update_post_meta($order_id, '_cart_discount', '0');
		update_post_meta($order_id, '_cart_discount_tax', '0');
		update_post_meta($order_id, '_order_tax', '0');
		update_post_meta($order_id, '_order_shipping_tax', '0');
		update_post_meta($order_id, '_order_shipping',$objAffinityOrder->shippingPrice);
		update_post_meta($order_id, '_order_shipping_affinity',$objAffinityOrder->shippingPrice);
		
		$objEcommerceOrder = self::transformNativeOrderIntoEcommerceOrder($objNativeOrder);
		return $objEcommerceOrder;
	}
	
    public static function orderHasChanged($objWpPost) {
		$objEcommerceOrder = self::get($objWpPost->ID);
		
		require_once(__DIR__. '/../model/AffinityOrder.php');
		AffinityOrder::orderChanged($objEcommerceOrder);
	}
	
	public static function getEditOrderLink($orderId) {
		return get_edit_post_link($orderId);
	}
	
	private static function transformNativeOrderIntoEcommerceOrder($objNativeOrder) {
		$objEcommerceOrder = new AffinityEcommerceOrder();
		
		if (method_exists($objNativeOrder, 'get_id')) {
			$order_id = $objNativeOrder->get_id();
		} else {
			$order_id = $objNativeOrder->id;
		}
		
		$post = get_post($order_id);
		
		$objEcommerceOrder->id = $order_id;
		$objEcommerceOrder->clientId = $objNativeOrder->get_user_id();
		$objEcommerceOrder->orderDate = $post->post_date;
		$objEcommerceOrder->modifiedDate = $post->post_modified;
		$objEcommerceOrder->status = $objNativeOrder->get_status();
		$objEcommerceOrder->totalPrice = $objNativeOrder->get_total();
		
		return $objEcommerceOrder;
	}
	
	
}