<?php

class AffinityShippingRule {
    public $_id;
    private $_name;
    private $_flatPriceRegularShipping;
    private $_flatPriceExpressShipping;
    
    public static $shipRules;
    
    static function trunc($todel) {
    	global $wpdb;
    	$sql = "DELETE FROM ".$wpdb->prefix."ebayaffinity_shiprules";
    	if (!empty($todel)) {
    		$sql .= " WHERE id NOT IN (".implode(', ', $todel).")";
    	}
    	$wpdb->query($sql);
    }
    
    function start() {
    	// This is like a dummy record.
    	global $wpdb;
    	
    	$query = $wpdb->prepare(
    			"REPLACE INTO ".$wpdb->prefix."ebayaffinity_shiprules
    				(id, is_default, profile_id, profile_name, standard_freeshipping, express_freeshipping, standard_fee, standard_additional_fee, express_fee, express_additional_fee, handledays, rate_table, pudo)
    				VALUES (%d, %d, %s, %s, %d, %d, %s, %s, %s, %s, %d, %d, %d)",
    			$this->_id, $this->_is_default, '', '', $this->_standard_freeshipping,
    			$this->_express_freeshipping, $this->_standard_fee, $this->_standard_additional_fee, $this->_express_fee, $this->_express_additional_fee, $this->_handledays, $this->_rate_table, $this->_pudo
    	);
    	$wpdb->query($query);
    }
    
    function upsert($forceinsert=false) {
    	global $wpdb;
    	 
    	if ($forceinsert) {
    		// Push to service
    		
    		if ($this->_profile_name !== 'SHIPPING_AFFINITY_POLICY_'.$this->_id && 
    				substr($this->_profile_name, 0, strlen('SHIPPING_AFFINITY_POLICY_'.$this->_id.' ')) !== 'SHIPPING_AFFINITY_POLICY_'.$this->_id.' ') {
    			$this->_profile_name = '';
    			$this->_profile_id = '';
    		}
    		
    		$ratetables = get_option('ebayaffinity_ratetables');
    		
    		$arr = array('shippingProfile' => array(
    				'profileName' => empty($this->_profile_name)?'SHIPPING_AFFINITY_POLICY_'.$this->_id:$this->_profile_name,
    				'profileType' => 'SHIPPING',
    				'profileDescription' => 'SHIPPING_AFFINITY_POLICY_'.$this->_id,
    				'regularFreeShipping' => !empty($this->_standard_freeshipping),
    				'regularShippingCost' => $this->_standard_fee,
    				'regularShippingAdditionalCost' => $this->_standard_additional_fee,
    				'handlingTimeInDays' => $this->_handledays,
    				'expressFreeShipping' => !empty($this->_express_freeshipping),
    				'expressShippingCost' => $this->_express_fee,
    				'expressShippingAdditionalCost' => $this->_express_additional_fee,
    				'applyDomesticRateTable' => !empty($this->_rate_table),
    				'eligibleForPickupDropOff' => !empty($this->_pudo),
                    'extraShippingDiscountProfileId' => $this->_discount_profile_id,
    		));
    		
    		if ($arr['shippingProfile']['regularFreeShipping'] == false && empty($arr['shippingProfile']['regularShippingCost'])) {
    			unset($arr['shippingProfile']['regularFreeShipping']);
    			unset($arr['shippingProfile']['regularShippingCost']);
    			unset($arr['shippingProfile']['regularShippingAdditionalCost']);
    		}
    		
    		if ($arr['shippingProfile']['expressFreeShipping'] == false && empty($arr['shippingProfile']['expressShippingCost'])) {
    			unset($arr['shippingProfile']['expressFreeShipping']);
    			unset($arr['shippingProfile']['expressShippingCost']);
                unset($arr['shippingProfile']['expressShippingAdditionalCost']);
    		}
    		
    		if (isset($arr['shippingProfile']['regularFreeShipping']) && $arr['shippingProfile']['regularFreeShipping'] == true) {
    			unset($arr['shippingProfile']['regularShippingCost']);
                unset($arr['shippingProfile']['regularShippingAdditionalCost']);
    		}
    		
    		if (isset($arr['shippingProfile']['expressFreeShipping']) && $arr['shippingProfile']['expressFreeShipping'] == true) {
    			unset($arr['shippingProfile']['expressShippingCost']);
                unset($arr['shippingProfile']['expressShippingAdditionalCost']);
    		}
    		
    		if (!empty($this->_profile_id)) {
    			$arr['shippingProfile']['profileId'] = $this->_profile_id;
    			
    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    			
    			//print '<pre>put1 '.print_r($arr, true).'</pre><pre>'.print_r($res, true).'</pre>';
    			
    			$this->_profile_id = '';
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['shippingProfile'])) {
    					if (!empty($res['arrResult']['data']['shippingProfile'][0])) {
    						if (!empty($res['arrResult']['data']['shippingProfile'][0]['profileId'])) {
    							$this->_profile_id = $res['arrResult']['data']['shippingProfile'][0]['profileId'];
    						}
    					}
    				}
    				
    				if (empty($this->_profile_id)) {
	    				if (!empty($res['arrResult']['data'][0])) {
	    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
	    						$this->_profile_id = '';
	    						$this->_profile_name = '';
	    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
	    							if ($param['name'] === 'DuplicateProfileId') {
	    								$this->_profile_id = $param['value'];
	    								$arr['shippingProfile']['profileId'] = $this->_profile_id;
	    							} else if ($param['name'] === 'DuplicateProfileName') {
	    								$this->_profile_name = $param['value'];
	    								$arr['shippingProfile']['profileName'] = $this->_profile_name;
	    							}
	    						}
	    						
	    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
	    							if ($this->_profile_name === 'SHIPPING_AFFINITY_POLICY_'.$this->_id ||
	    									substr($this->_profile_name, 0, strlen('SHIPPING_AFFINITY_POLICY_'.$this->_id.' ')) === 'SHIPPING_AFFINITY_POLICY_'.$this->_id.' ') {
	    								// If we get a dupe warning and the profile name is ours, we overwrite with a PUT to make sure the fields are up to date.
	    								$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
	    								
	    								//print '<pre>put2 '.print_r($arr, true).'</pre><pre>'.print_r($res, true).'</pre>';
	    							}
	    						}
	    					}
	    				}
    				}
    			}
    			if (empty($this->_profile_id)) {
    				unset($arr['shippingProfile']['profileId']);
    			} else {
    				$arr['shippingProfile']['profileId'] = $this->_profile_id;
    			}
    		}

    		if (empty($this->_profile_id)) {
    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'POST');
    			
    			//print '<pre>post '.print_r($arr, true).'</pre><pre>'.print_r($res, true).'</pre>';
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data'][0])) {
    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
    						$this->_profile_id = '';
    						$this->_profile_name = '';
    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    							if ($param['name'] === 'DuplicateProfileId') {
    								$this->_profile_id = $param['value'];
    								$arr['shippingProfile']['profileId'] = $this->_profile_id;
    							} else if ($param['name'] === 'DuplicateProfileName') {
    								$this->_profile_name = $param['value'];
    								$arr['shippingProfile']['profileName'] = $this->_profile_name;
    							}
    						}
    						
    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    							if ($this->_profile_name === 'SHIPPING_AFFINITY_POLICY_'.$this->_id ||
	    								substr($this->_profile_name, 0, strlen('SHIPPING_AFFINITY_POLICY_'.$this->_id.' ')) === 'SHIPPING_AFFINITY_POLICY_'.$this->_id.' ') {
    								// If we get a dupe warning and the profile name is ours, we overwrite with a PUT to make sure the fields are up to date.
    								$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');		
    								
    								//print '<pre>put3 '.print_r($arr, true).'</pre><pre>'.print_r($res, true).'</pre>';
    								if (!empty($res['arrResult']['data'])) {
    									if (!empty($res['arrResult']['data'][0])) {
    										if (!empty($res['arrResult']['data'][0]['parameter'])) {
    											foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    												// We cannot update this rule.
    												if ($param['name'] === 'XPATH') {
    													$this->_profile_id = '';
    													$this->_profile_name = 'SHIPPING_AFFINITY_POLICY_'.$this->_id.' '.uniqid('');
    													unset($arr['shippingProfile']['profileId']);
    													$arr['shippingProfile']['profileName'] = $this->_profile_name;
    													$res = AffinityBackendService::sendProfileSyncRequest($arr, 'POST');
    													
    													//print '<pre>post2 '.print_r($arr, true).'</pre><pre>'.print_r($res, true).'</pre>';
    													break;
    												}
    											}
    										}
    									}
    								}
    							}
    						}
    					}
    				}
    			}
    		}
    		
			$errors = json_decode(get_option('ebayaffinity_shipping_errors'));
			$oerrors = $errors;
    		
    		if (!empty($res['arrResult'])) {
    			if (!empty($res['arrResult']['errors'])) {
    				foreach ($res['arrResult']['errors'] as $error) {
    					$errors[] = 'Shipping rule #'.$this->_id.': '.$error;
    				}
    			}
    			if (!empty($res['arrResult']['warnings'])) {
    				foreach ($res['arrResult']['warnings'] as $warning) {
    					$errors[] = 'Shipping rule #'.$this->_id.': '.$warning;
    				}
    			}
    			
    			$errors = array_unique($errors);
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data'][0])) {
    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
    						$this->_profile_id = '';
    						$this->_profile_name = '';
    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    							if ($param['name'] === 'DuplicateProfileId') {
    								$this->_profile_id = $param['value'];
    							} else if ($param['name'] === 'DuplicateProfileName') {
    								$this->_profile_name = $param['value'];
    							}
    						}
    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    							// This is a dupe, so we use the existing entry.
    							$errors = $oerrors;
    						}
    					}
    				}
    			}
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['shippingProfile'])) {
    					if (!empty($res['arrResult']['data']['shippingProfile'][0])) {
    						if (!empty($res['arrResult']['data']['shippingProfile'][0]['profileId'])) {
    							// Our last PUT/POST was a success.
    							$this->_profile_id = $res['arrResult']['data']['shippingProfile'][0]['profileId'];
    							$this->_profile_name = $res['arrResult']['data']['shippingProfile'][0]['profileName'];
    						}
    					}
    				}
    			}
    			
    			update_option('ebayaffinity_shipping_errors', json_encode($errors));
    		}
    		
    		$query = $wpdb->prepare(
    				"REPLACE INTO ".$wpdb->prefix."ebayaffinity_shiprules 
    				(id, is_default, profile_id, profile_name, standard_freeshipping, express_freeshipping, standard_fee, standard_additional_fee, express_fee, express_additional_fee, handledays, rate_table, pudo)
    				VALUES (%d, %d, %s, %s, %d, %d, %s, %s, %s, %s, %d, %d, %d)",
    				$this->_id, $this->_is_default, $this->_profile_id, $this->_profile_name, $this->_standard_freeshipping, 
    				$this->_express_freeshipping, $this->_standard_fee, $this->_standard_additional_fee, $this->_express_fee, $this->_express_additional_fee, $this->_handledays, $this->_rate_table, $this->_pudo
    				);
    	} else if (!empty($this->_id)) {
    		$query = $wpdb->prepare(
    				"UPDATE '.$wpdb->prefix.'ebayaffinity_shiprules SET rule=%s WHERE id = %d",
    				$this->_titleTemplate, $this->_id
    				);
    	} else {
    		$query = $wpdb->prepare(
    				"INSERT INTO '.$wpdb->prefix.'ebayaffinity_shiprules (rule) VALUES (%s)",
    				$this->_titleTemplate
    				);
    	}
    	 
    	if ($wpdb->query($query)) {
    		if (!empty($wpdb->insert_id)) {
    			$this->id = $wpdb->insert_id;
    		} else {
    			$this->id = 0;
    		}
    		return $this->id;
    	} else {
    		return -1;
    	}
    }
    
    static function getAllRules() {
    	global $wpdb;
    	return $wpdb->get_results("SELECT id, is_default, profile_id, profile_name, standard_freeshipping, express_freeshipping, standard_fee, standard_additional_fee, express_fee, express_additional_fee, handledays, rate_table, pudo FROM ".$wpdb->prefix."ebayaffinity_shiprules ORDER BY id");
    }
    
    static function getRule($id) {
    	if (empty($id)) {
    		return false;
    	}
    	if (empty(self::$shipRules)) {
    		self::$shipRules = self::getAllRules();
    	}
    	foreach (self::$shipRules as $shipRule) {
    		if ($shipRule->id == $id) {
    			return $shipRule;
    		}
    	}
    	return false;
    }
    
    static function getDefaultRule() {
    	if (empty(self::$shipRules)) {
    		self::$shipRules = self::getAllRules();
    	}
    	foreach (self::$shipRules as $shipRule) {
    		if (!empty($shipRule->is_default)) {
    			return $shipRule;
    		}
    	}
    	return false;
    }
    
    static function generate($product) {
    	if (empty(self::$shipRules)) {
    		self::$shipRules = self::getAllRules();
    	}
    	
    	$rule = self::getRule(get_post_meta($product->get_id(), '_affinity_shiprule', true));
    	
    	if (empty($rule)) {
    		$terms = get_the_terms($product->get_id(), 'product_cat');
    		if (is_array($terms)) {
    			foreach ($terms as $k=>$term) {
    				$r = self::getRule(get_term_meta($term->term_id, '_affinity_shiprule', true));
    				if (!empty($r)) {
    					$rule = $r;
    					break;
    				}
    			}
    		}
    	}
    	if (empty($rule)) {
    		$rule = self::getDefaultRule();
    	}
    	if (empty($rule)) {
    		$rule = array(
    				'id' => 0,
    				'profile_id' => 0,
    				'profile_name' => '',
		    		'standard_freeshipping' => 1,
		    		'express_freeshipping' => 1,
		    		'standard_fee' => 0,
		    		'standard_additional_fee' => 0,
		    		'express_fee' => 0,
		    		'express_additional_fee' => 0,
		    		'handledays' => 0,
    				'rate_table' => 0,
    				'pudo' => 0
 			);
    	}
    	$rule = (array)$rule;
    	$rule['ruleid'] = $rule['id'];

    	return $rule;
    }
}
