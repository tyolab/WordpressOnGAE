<?php

class AffinityPricingule {
    function upsert($forceinsert=false) {
    	if ($forceinsert) {
    		// Push to service
    		
    		if ($this->_profile_name !== 'PAYMENT_AFFINITY_POLICY' &&
    				substr($this->_profile_name, 0, strlen('PAYMENT_AFFINITY_POLICY ')) !== 'PAYMENT_AFFINITY_POLICY ') {
    			$this->_profile_name = '';
    			$this->_profile_id = '';
    		}
    		
    		$arr = array('paymentProfile' => array(
    				'profileName' => empty($this->_profile_name)?'PAYMENT_AFFINITY_POLICY':$this->_profile_name,
    				'profileType' => 'PAYMENT',
    				'profileDescription' => 'PAYMENT_AFFINITY_POLICY',
    				'acceptedPaymentMethod' => array('PAY_PAL'),
    				'immediatePay' => true,
    				'paypalEmailAddress' => $this->_paypal
    		));
    		
    		if (!empty($this->_profile_id)) {
    			$arr['paymentProfile']['profileId'] = $this->_profile_id;
    			
    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    			
    			$this->_profile_id = '';
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['paymentProfile'])) {
    					if (!empty($res['arrResult']['data']['paymentProfile'][0])) {
    						if (!empty($res['arrResult']['data']['paymentProfile'][0]['profileId'])) {
    							$this->_profile_id = $res['arrResult']['data']['paymentProfile'][0]['profileId'];
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
    									$arr['paymentProfile']['profileId'] = $this->_profile_id;
    								} else if ($param['name'] === 'DuplicateProfileName') {
    									$this->_profile_name = $param['value'];
    									$arr['paymentProfile']['profileName'] = $this->_profile_name;
    								}
    							}
    								
    							if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    								if ($this->_profile_name === 'PAYMENT_AFFINITY_POLICY' ||
    										substr($this->_profile_name, 0, strlen('PAYMENT_AFFINITY_POLICY ')) === 'PAYMENT_AFFINITY_POLICY ') {
    									// If we get a dupe warning and the profile name is ours, we overwrite with a PUT to make sure the fields are up to date.
    									$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    								}
    							}
    						}
    					}
    				}
    			}
    			if (empty($this->_profile_id)) {
    				unset($arr['paymentProfile']['profileId']);
    			} else {
    				$arr['paymentProfile']['profileId'] = $this->_profile_id;
    			}
    		}

    		if (empty($this->_profile_id)) {
    			// New profile, or the profile name is not ours.
    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'POST');
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data'][0])) {
    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
    						$this->_profile_id = '';
    						$this->_profile_name = '';
    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    							if ($param['name'] === 'DuplicateProfileId') {
    								$this->_profile_id = $param['value'];
    								$arr['paymentProfile']['profileId'] = $this->_profile_id;
    							} else if ($param['name'] === 'DuplicateProfileName') {
    								$this->_profile_name = $param['value'];
    								$arr['paymentProfile']['profileName'] = $this->_profile_name;
    							}
    						}
    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    							if ($this->_profile_name === 'PAYMENT_AFFINITY_POLICY' ||
    									substr($this->_profile_name, 0, strlen('PAYMENT_AFFINITY_POLICY ')) === 'PAYMENT_AFFINITY_POLICY ') {
    								// If we get a dupe warning and the profile name is ours, we overwrite with a PUT to make sure the fields are up to date.
    								$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    							}
    						}
    					}
    				}
    			}
    		}
    		
    		$errors = array();
    		
    		if (!empty($res['arrResult'])) {
    			if (!empty($res['arrResult']['errors'])) {
    				foreach ($res['arrResult']['errors'] as $error) {
    					$errors[] = $error;
    				}
    			}
    			if (!empty($res['arrResult']['warnings'])) {
    				foreach ($res['arrResult']['warnings'] as $warning) {
    					$errors[] = $warning;
    				}
    			}
    			
    			$errors = array_unique($errors);
    			update_option('ebayaffinity_pricing_errors', json_encode($errors));

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
    							update_option('ebayaffinity_pricing_errors', '[]');
    						}
    					}
    				}
    			}
    		
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['paymentProfile'])) {
    					if (!empty($res['arrResult']['data']['paymentProfile'][0])) {
    						if (!empty($res['arrResult']['data']['paymentProfile'][0]['profileId'])) {
    							// Our last PUT/POST was a success.
    							$this->_profile_id = $res['arrResult']['data']['paymentProfile'][0]['profileId'];
    							$this->_profile_name = $res['arrResult']['data']['paymentProfile'][0]['profileName'];
    						}
    					}
    				}
    			}
    		}
    		
    		if (!empty($this->_profile_id)) {
    			update_option('ebayaffinity_pricing_profile_id', $this->_profile_id);
    		}
    		if (!empty($this->_profile_name)) {
    			update_option('ebayaffinity_pricing_profile_name', $this->_profile_name);
    		} else {
    			update_option('ebayaffinity_pricing_profile_name', 'PAYMENT_AFFINITY_POLICY');
    		}
    	}
    }
    
    static function generate($product) {
    	return get_option('ebayaffinity_pricing_profile_name');
    }
}
