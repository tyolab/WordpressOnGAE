<?php

class AffinityReturnsRule {
    function upsert($forceinsert=false) {
    	if ($forceinsert) {
    		// Push to service
    		
    		if ($this->_profile_name !== 'RETURNS_AFFINITY_POLICY' &&
    				substr($this->_profile_name, 0, strlen('RETURNS_AFFINITY_POLICY ')) !== 'RETURNS_AFFINITY_POLICY ') {
    			$this->_profile_name = '';
    			$this->_profile_id = '';
    		}
    		
    		$arr = array('returnsProfile' => array(
    				'profileName' => empty($this->_profile_name)?'RETURNS_AFFINITY_POLICY':$this->_profile_name,
    				'profileType' => 'RETURN_POLICY',
    				'profileDescription' => 'RETURNS_AFFINITY_POLICY',
    				'refundOption' => $this->_refundoption,
    				'returnsWithinOption' => $this->_returnwithin,
    				'returnsAcceptedOption' => $this->_returnaccepted,
    				'shippingCostPaidByOption' => $this->_returncosts,
    				'description' => $this->_description
    		));
    		
    		if ($this->_returnaccepted === 'RETURNS_NOT_ACCEPTED') {
    			unset($arr['returnsProfile']['refundOption']);
    			unset($arr['returnsProfile']['returnsWithinOption']);
    			unset($arr['returnsProfile']['shippingCostPaidByOption']);
    			unset($arr['returnsProfile']['description']);
    		}
    		
    		if (empty($arr['returnsProfile']['description'])) {
    			unset($arr['returnsProfile']['description']);
    		}
    		
    		if (!empty($this->_profile_id)) {
    			$arr['returnsProfile']['profileId'] = $this->_profile_id;

    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    			 
    			$this->_profile_id = '';
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['returnsProfile'])) {
    					if (!empty($res['arrResult']['data']['returnsProfile'][0])) {
    						if (!empty($res['arrResult']['data']['returnsProfile'][0]['profileId'])) {
    							$this->_profile_id = $res['arrResult']['data']['returnsProfile'][0]['profileId'];
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
    									$arr['returnsProfile']['profileId'] = $this->_profile_id;
    								} else if ($param['name'] === 'DuplicateProfileName') {
    									$this->_profile_name = $param['value'];
    									$arr['returnsProfile']['profileName'] = $this->_profile_name;
    								}
    							}
    				
    							if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    								if ($this->_profile_name === 'RETURNS_AFFINITY_POLICY' ||
    										substr($this->_profile_name, 0, strlen('RETURNS_AFFINITY_POLICY ')) === 'RETURNS_AFFINITY_POLICY ') {
    									// If we get a dupe warning and the profile name is ours, we overwrite with a PUT to make sure the fields are up to date.
    									$res = AffinityBackendService::sendProfileSyncRequest($arr, 'PUT');
    								}
    							}
    						}
    					}
    				}
    			}
    			if (empty($this->_profile_id)) {
    				unset($arr['returnsProfile']['profileId']);
    			} else {
    				$arr['returnsProfile']['profileId'] = $this->_profile_id;
    			}
    		}

    		if (empty($this->_profile_id)) {
    			$res = AffinityBackendService::sendProfileSyncRequest($arr, 'POST');

    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data'][0])) {
    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
    						$this->_profile_id = '';
    						$this->_profile_name = '';
    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    							if ($param['name'] === 'DuplicateProfileId') {
    								$this->_profile_id = $param['value'];
    								$arr['returnsProfile']['profileId'] = $this->_profile_id;
    							} else if ($param['name'] === 'DuplicateProfileName') {
    								$this->_profile_name = $param['value'];
    								$arr['returnsProfile']['profileName'] = $this->_profile_name;
    							}
    						}
    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    							if ($this->_profile_name === 'RETURNS_AFFINITY_POLICY' ||
    									substr($this->_profile_name, 0, strlen('RETURNS_AFFINITY_POLICY ')) === 'RETURNS_AFFINITY_POLICY ') {
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
    			update_option('ebayaffinity_returns_errors', json_encode($errors));
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data'][0])) {
    					if (!empty($res['arrResult']['data'][0]['parameter'])) {
    						$this->_profile_id = '';
    						$this->_profile_name = '';
    						foreach ($res['arrResult']['data'][0]['parameter'] as $param) {
    							if ($param['name'] == 'DuplicateProfileId') {
    								$this->_profile_id = $param['value'];
    							} else if ($param['name'] == 'DuplicateProfileName') {
    								$this->_profile_name = $param['value'];
    							}
    						}
    						if ((!empty($this->_profile_id)) && ((!empty($this->_profile_name)))) {
    							// This is a dupe, so we use the existing entry.
    							update_option('ebayaffinity_returns_errors', '[]');
    						}
    					}
    				}
    			}
    			
    			if (!empty($res['arrResult']['data'])) {
    				if (!empty($res['arrResult']['data']['returnsProfile'])) {
    					if (!empty($res['arrResult']['data']['returnsProfile'][0])) {
    						if (!empty($res['arrResult']['data']['returnsProfile'][0]['profileId'])) {
    							// Our last PUT/POST was a success.
    							$this->_profile_id = $res['arrResult']['data']['returnsProfile'][0]['profileId'];
    							$this->_profile_name = $res['arrResult']['data']['returnsProfile'][0]['profileName'];
    						}
    					}
    				}
    			}
    		}
    		
    	    if (!empty($this->_profile_id)) {
    			update_option('ebayaffinity_returns_profile_id', $this->_profile_id);
    		}
    		if (!empty($this->_profile_name)) {
    			update_option('ebayaffinity_returns_profile_name', $this->_profile_name);
    		} else {
    			update_option('ebayaffinity_returns_profile_name', 'RETURNS_AFFINITY_POLICY');
    		}
    	}
    }
    
    static function generate($product) {
    	return get_option('ebayaffinity_returns_profile_name');
    }
}
