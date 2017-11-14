<?php

class AffinityPagesManager {
	public function initAdminMenu() {
		add_action('admin_head', array($this, 'head'));
		add_menu_page('eBay Sync', 'eBay Sync', 'manage_options', 'ebay-sync', array($this, 'dashboard'), null, '59');
		add_submenu_page('ebay-sync', 'Dashboard', 'Dashboard', 'manage_options', 'ebay-sync', array($this, 'dashboard'));
		add_submenu_page('ebay-sync', 'Inventory', 'Inventory', 'manage_options', 'ebay-sync-inventory', array($this, 'inventory'));
		add_submenu_page('ebay-sync', 'Mapping', 'Mapping', 'manage_options', 'ebay-sync-mapping', array($this, 'mapping'));
		add_submenu_page('ebay-sync', 'Title Optimisation', 'Title Optimisation', 'manage_options', 'ebay-sync-title-optimisation', array($this, 'titleOptimisation'));
		add_submenu_page('ebay-sync', 'Settings', 'Settings', 'manage_options', 'ebay-sync-settings', array($this, 'settings'));
		add_submenu_page('ebay-sync', 'Help', 'Help', 'manage_options', 'ebay-sync-help', array($this, 'help'));
		add_submenu_page('ebay-sync', 'Logs', 'Logs', 'manage_options', 'ebay-sync-logs', array($this, 'logs'));
		add_submenu_page('ebay-sync', 'Give feedback', 'Give feedback', 'manage_options', 'ebay-sync-feedback', array($this, 'feedback'));

		add_submenu_page(null, 'Account Blocked', 'Account Blocked', 'manage_options', 'ebay-sync-blocked', array($this, 'accountBlocked'));
		add_submenu_page(null, 'Authentication Failed', 'Authentication Failed', 'manage_options', 'ebay-sync-auth-failed', array($this, 'authFailed'));
		
		//Controls compulsory page redirections
		if(!$this->isAnWoobayPage() || $this->isTheLogsPage() || $this->isTheBlockedPage() || $this->isTheAuthFailedPage()) {
			return;
		}
		
		$beingRedirectedToAuthentication = $this->redirectToAuthenticationIfLastCommandReturnedForbidden();
		$this->blockUiIfDeleteAccountListingsIsRunning();
	}
	
	public function isAnWoobayPage() {
		return stristr($_SERVER["REQUEST_URI"], "ebay-sync") !== false || stristr($_SERVER["REQUEST_URI"], "ebay-affinity") !== false;
	}
	
	
	
	
	public function redirectToAuthenticationIfLastCommandReturnedForbidden() {
		if($this->isTheSettingsPage() || $this->isTheAuthFailedPage()) {
			return true;
		}
		
		require_once(__DIR__ . "/../service/AffinityBackendService.php");
		if(AffinityBackendService::hasLastCommandAuthenticationFailed()) {
			$authenticationFailedPage = menu_page_url( 'ebay-sync-auth-failed', false );
			wp_redirect($authenticationFailedPage);
			print "<script>location.href = 'admin.php?page=ebay-sync-auth-failed';</script>";
			exit;
		}
		
		return false;
	}
	
	public function blockUiIfDeleteAccountListingsIsRunning() {
		if($this->isTheBlockedPage()) {
			return;
		}
		
		require_once(__DIR__ . "/../model/AffinityProduct.php");
		if(AffinityProduct::isDeletingAllListingsInProgress()) {
			$accountBlockedUrl = menu_page_url( 'ebay-sync-blocked', false );
			wp_redirect($accountBlockedUrl);
			print "<script>location.href = 'admin.php?page=ebay-sync-blocked';</script>";
			exit;
		}
	}
	
	public function isTheSettingsPage() {
		return stristr($_SERVER["REQUEST_URI"], "ebay-sync-settings") !== false;
	}
	
	public function isTheAuthFailedPage() {
		return stristr($_SERVER["REQUEST_URI"], "ebay-sync-auth-failed") !== false;
	}
	
	public function isTheBlockedPage() {
		return stristr($_SERVER["REQUEST_URI"], "ebay-sync-blocked") !== false;
	}
	
	public function isTheLogsPage() {
		return stristr($_SERVER["REQUEST_URI"], "ebay-sync-logs") !== false;
	}
	
	public function head() {
		$pd = untrailingslashit(plugins_url('/../', __FILE__ ));
		print '<script type="text/javascript" src="'. $pd .'/assets/index.js?v=1.1"></script>';
		print '<link rel="stylesheet" href="'. $pd .'/assets/index.css?v=1.1">';
	}

	public function dashboard() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$catver = get_option('ebayaffinity_catver');
		if ($catver < 11) {
			require_once(__DIR__.'/../model/AffinityEbayInstCategory.php');
			AffinityEbayInstCategory::install();
		}
		
		if (!empty($_GET['sync'])) {
			$setup1 = get_option('ebayaffinity_setup1');
			$setup2 = get_option('ebayaffinity_setup2');
			$setup3 = get_option('ebayaffinity_setup3');
			$setup4 = get_option('ebayaffinity_setup4');
			if ((!empty($setup1)) && (!empty($setup2)) && (!empty($setup3)) && (!empty($setup4))) {
				require_once(__DIR__.'/../service/AffinityEmail.php');
				AffinityEmail::setups();
				
				update_option('ebayaffinity_setup5', 1);
				
				require_once(__DIR__.'/../model/AffinityEbayInventory.php');
				AffinityEbayInventory::syncAll();
				
				wp_clear_scheduled_hook('wp_affinity_cron_inv');
				wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron_inv');
				wp_clear_scheduled_hook('wp_affinity_cron_orders');
				wp_schedule_event(time() + 600, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 900, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 1800, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 2700, 'hourly', 'wp_affinity_cron_orders');
				spawn_cron();
			}
		}
		require_once(__DIR__ . '/dashboard.php');
	}
	
	public function accountBlocked() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		require_once(__DIR__ . '/accountBlocked.php');
	}
	
	public function authFailed() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		require_once(__DIR__ . '/authenticationFailed.php');
	}

	public function inventory() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$catver = get_option('ebayaffinity_catver');
		if ($catver < 11) {
			require_once(__DIR__.'/../model/AffinityEbayInstCategory.php');
			AffinityEbayInstCategory::install();
		}
		
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');

		if (!empty($_GET['id'])) {
			if (!empty($_POST['ebayaffinity_prodcats'])) {
				foreach ($_POST['ebayaffinity_prodcats'] as $k=>$v) {
					if (empty($v)) {
						delete_post_meta($k, '_affinity_ebaycategory');
					} else {
						update_option('ebayaffinity_setup4', 1);
						update_post_meta($k, '_affinity_ebaycategory', $v);
					}
				}
			}
			if (isset($_POST['titlerule'])) {
				if (empty($_POST['titlerule'])) {
					delete_post_meta($_GET['id'], '_affinity_titlerule');
				} else {
					update_post_meta($_GET['id'], '_affinity_titlerule', $_POST['titlerule']);
				}
			}
			if (isset($_POST['shiprule'])) {
				if (empty($_POST['shiprule'])) {
					delete_post_meta($_GET['id'], '_affinity_shiprule');
				} else {
					update_post_meta($_GET['id'], '_affinity_shiprule', $_POST['shiprule']);
				}
			}
			if ((!empty($_POST)) && (!empty($_GET['id']))) {
				update_post_meta($_GET['id'], '_affinity_prod_update_status', '1');
			}
		}
		
		if ((!empty($_GET['id'])) && (!empty($_GET['sync']))) {
			AffinityEcommerceProduct::productHasChanged(get_post($_GET['id']), true);
		}
		
		require_once(__DIR__ . '/inventory.php');
	}

	public function mapping() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$catver = get_option('ebayaffinity_catver');
		if ($catver < 11) {
			require_once(__DIR__.'/../model/AffinityEbayInstCategory.php');
			AffinityEbayInstCategory::install();
		}
		
		if (!empty($_POST)) {
			if (!empty($_POST['ebayaffinity_prodcats'])) {
				foreach ($_POST['ebayaffinity_prodcats'] as $k=>$v) {
					if (empty($v)) {
						delete_post_meta($k, '_affinity_ebaycategory');
					} else {
						update_option('ebayaffinity_setup4', 1);
						update_post_meta($k, '_affinity_ebaycategory', $v);
					}
				}
			}
			if (!empty($_POST['ebayaffinity_catcats'])) {
				foreach ($_POST['ebayaffinity_catcats'] as $k=>$v) {
					if (empty($v)) {
						delete_term_meta($k, '_affinity_ebaycategory');
					} else {
						update_option('ebayaffinity_setup4', 1);
						update_term_meta($k, '_affinity_ebaycategory', $v);
					}
				}
			}
			
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityCrontab.php");
			AffinityCrontab::cron_sched_sync_all();
		}

		require_once(__DIR__ . '/mapping.php');
	}

	public function orders() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		require_once(__DIR__ . '/orders.php');
	}

	public function settings() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$catver = get_option('ebayaffinity_catver');
		if ($catver < 11) {
			require_once(__DIR__.'/../model/AffinityEbayInstCategory.php');
			AffinityEbayInstCategory::install();
		}

		if (!empty($_GET['hash']) && !empty($_GET['wipe']) && stripslashes($_GET['hash']) === sha1(php_uname().date('dmY'))) {
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityDataLayer.php");
			require_once(__DIR__ . "/../service/AffinityBackendService.php");
			
			if (!empty($_GET['deletelistings'])) {
				require_once(__DIR__ . "/../model/AffinityProduct.php");
				AffinityProduct::endAllListingsFromAccountAsync();
				$blockedPage = menu_page_url( 'ebay-sync-blocked', false );
				wp_redirect($blockedPage);
				print "<script>location.href = 'admin.php?page=ebay-sync-blocked';</script>";
				exit;
			}				
			else {
				if (AffinityBackendService::unhookInstallation()) {
					AffinityDataLayer::wipeAllAffinityData();
				}
			}
		}
		
		if (!empty($_GET['clearlogs'])) {
			global $wpdb;
			$wpdb->query("DELETE FROM `".$wpdb->prefix."ebayaffinity_log`");
		}

		require_once(__DIR__ . "/../model/AffinityGlobalOptions.php");
		require_once(__DIR__.'/../model/AffinityShippingRule.php');
		require_once(__DIR__.'/../model/AffinityPricingRule.php');
		require_once(__DIR__.'/../model/AffinityReturnsRule.php');
		require_once(__DIR__ . "/../service/AffinityBackendService.php");
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');

		if (!empty($_POST['save'])) {
			if (empty($_GET['pnum'])) {
				if (!empty($_POST['ebayaffinity_ebayuserid'])) {
					update_option('ebayaffinity_ebayuserid', stripslashes($_POST['ebayaffinity_ebayuserid']));
				} else {
					$_POST['ebayaffinity_ebayuserid'] = addslashes(get_option('ebayaffinity_ebayuserid'));
				}
				if (!empty($_POST['ebayaffinity_puuid'])) {
					update_option('ebayaffinity_puuid', stripslashes($_POST['ebayaffinity_puuid']));
				} else {
					$_POST['ebayaffinity_puuid'] = addslashes(get_option('ebayaffinity_puuid'));
				}
				if (!empty($_POST['ebayaffinity_token'])) {
					update_option('affinityPushAccessToken', stripslashes($_POST['ebayaffinity_token']));
						
					$authenticationSuccessful = AffinityBackendService::authenticate(stripslashes($_POST['ebayaffinity_ebayuserid']), stripslashes($_POST['ebayaffinity_puuid']));
					if($authenticationSuccessful) {
						
						$rules = AffinityShippingRule::getAllRules();
						if (count($rules) == 0) {
							update_option('ebayaffinity_shipping_errors', '[]');
							
							$profile = AffinityBackendService::getProfile();
							$isClickAndCollectEnabled = !empty($profile['data']['isClickAndCollectEnabled']);
							$isDomesticaRateTableEnabled = !empty($profile['data']['isDomesticaRateTableEnabled']);
							
							$rule = new AffinityShippingRule();
							$rule->_id = 1;
							$rule->_standard_freeshipping = 0;
							$rule->_express_freeshipping = 0;
							$rule->_standard_fee = '0.00';
							$rule->_express_fee = '0.00';
							$rule->_standard_additional_fee = '0.00';
							$rule->_express_additional_fee = '0.00';
							$rule->_handledays = 1;
							$rule->_rate_table = $isDomesticaRateTableEnabled?1:0;
							$rule->_pudo = $isClickAndCollectEnabled?1:0;
							$rule->_is_default = 1;
							$rule->start();
						}

						if (empty($_POST['ebayaffinity_paypal'])) {
							$paypal = get_option('woocommerce_paypal_settings');
						} else {
							$paypal = array('email' => stripslashes($_POST['ebayaffinity_paypal']));
						}
						

						$rules = new AffinityPricingule();
						$rules->_profile_name = get_option('ebayaffinity_pricing_profile_name');
						$rules->_profile_id = get_option('ebayaffinity_pricing_profile_id');
						$rules->_paypal = empty($paypal['email'])?'':$paypal['email'];
						$rules->upsert(true);

						if (!empty($paypal['email'])) {
							update_option('ebayaffinity_paypal', $paypal['email']);
						}

						$a = json_decode(get_option('ebayaffinity_pricing_errors'), true);

						wp_clear_scheduled_hook('wp_affinity_cron');
						wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron');
						spawn_cron();
					}
					else {
						update_option('ebayaffinity_last_auth_failed', 1);
					}
				} else {
					$hasWoobayGotPushToken = AffinityBackendService::hasWoobayGotPushToken();
					if ($hasWoobayGotPushToken) {
						if (empty($_POST['ebayaffinity_paypal'])) {
							$paypal = get_option('woocommerce_paypal_settings');
						} else {
							$paypal = array('email' => stripslashes($_POST['ebayaffinity_paypal']));
						}
						
						$rules = new AffinityPricingule();
						$rules->_profile_name = get_option('ebayaffinity_pricing_profile_name');
						$rules->_profile_id = get_option('ebayaffinity_pricing_profile_id');
						$rules->_paypal = empty($paypal['email'])?'':$paypal['email'];
						$rules->upsert(true);

						if (!empty($paypal['email'])) {
							update_option('ebayaffinity_paypal', $paypal['email']);
						}
					}
					wp_clear_scheduled_hook('wp_affinity_cron');
					wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron');
					spawn_cron();
				}
			} else if ($_GET['pnum'] == 2) {
				$dir = wp_upload_dir();
				$dir = $dir['basedir'];
				$imgcontent = '';
				@mkdir($dir, 0777, true);
				
				global $imgerror;
				$imgerror = '';

				if (!empty($_POST['ebayaffinity_logourl'])) {
					if (!class_exists('WP_Http')) {
						require_once(ABSPATH . WPINC. '/class-http.php');
					}
					$ret = wp_remote_request($_POST['ebayaffinity_logourl']);
					if (is_object($ret) && get_class($ret) === 'WP_Error') {
						$imgerror = 'Could not retrieve image from URL.';
					} else {
						if (!empty($ret['body'])) {
							$imgcontent = $ret['body'];
						}
					}
				} else if (!empty($_POST['ebayaffinity_dropfile'])) {
					$arr = explode(',', $_POST['ebayaffinity_dropfile']);
					array_shift($arr);
					$imgcontent = base64_decode(implode(',', $arr));
					unset($arr);
				} else if ((!empty($_FILES['ebayaffinity_logofile'])) && (!empty($_FILES['ebayaffinity_logofile']['tmp_name']))) {
					$imgcontent = file_get_contents($_FILES['ebayaffinity_logofile']['tmp_name']);
				}
				$filename = '';
				
				if (!empty($imgcontent)) {
					$tmpfname = tempnam(sys_get_temp_dir(), "IMGCHK");
					file_put_contents($tmpfname, $imgcontent);
					$about = getimagesize($tmpfname);
					unlink($tmpfname);
					switch($about[2]) {
						case IMAGETYPE_JPEG:
							$filename = 'ebayaffinity.jpg';
							break;
						case IMAGETYPE_PNG:
							$filename = 'ebayaffinity.png';
							break;
						case IMAGETYPE_GIF:
							$filename = 'ebayaffinity.gif';
							break;
						default:
							$imgcontent = '';
							$imgerror = 'Please ensure that the file is a JPEG, PNG, or GIF.';
					}
					if ((!empty($filename)) && (!empty($imgcontent))) {
						$tfilename = uniqid('', true).$filename;
						if ($about[0] > 500 || $about[1] > 500) {
							file_put_contents($dir.'/'.$tfilename, $imgcontent);
							$editor = wp_get_image_editor($dir.'/'.$tfilename);
							if (get_class($editor) !== 'WP_Error') {
								$editor->resize(500, 500, false);
								$editor->set_quality(90);
								$filename = 'r'.$filename;
								$editor->save($dir.'/'.$filename);
								update_option('ebayaffinity_logo', $filename);
							} else {
								$imgerror = 'The image that you have uploaded is '.$about[0].'x'.$about[1].'. Please ensure that it is no larger than 500x500.';
							}
							unlink($dir.'/'.$tfilename);
						} else {
							file_put_contents($dir.'/'.$filename, $imgcontent);
							update_option('ebayaffinity_logo', $filename);
						}
					}
				}
				
				update_option('ebayaffinity_customtemplate', empty($_POST['ebayaffinity_usecustomtemplate'])?'':stripslashes($_POST['ebayaffinity_customtemplate']));
				update_option('ebayaffinity_useshort', empty($_POST['ebayaffinity_useshort'])?'':'1');
				
				if (empty($imgerror)) {
					update_option('ebayaffinity_setup1', 1);
				}
			} else if ($_GET['pnum'] == 3) {
				global $ebayaffinity_delerrors;
				
				$ebayaffinity_delerrors = array();
				if (!empty($_POST['ebayaffinity_catshiprule'])) {
					foreach ($_POST['ebayaffinity_catshiprule'] as $k=>$v) {
						if (empty($v)) {
							delete_term_meta($k, '_affinity_shiprule');
						} else {
							update_term_meta($k, '_affinity_shiprule', $v);
						}
					}
				}
				if (!empty($_POST['ebayaffinity_prodshiprule'])) {
					foreach ($_POST['ebayaffinity_prodshiprule'] as $k=>$v) {
						if (empty($v)) {
							delete_post_meta($k, '_affinity_shiprule');
						} else {
							update_post_meta($k, '_affinity_shiprule', $v);
						}
					}
				}
				if (!empty($_POST['ebayaffinity_hideshiprules'])) {
					require_once(__DIR__.'/../model/AffinityEbayCategory.php');
					$cats = AffinityEbayCategory::getCategoriesShipRules();
					$catshave = array();
					foreach ($cats as $k=>$v) {
						$catshave[$v[1]] = $v[1];
					}
					
					$_POST['ebayaffinity_hideshiprules'] = array_unique($_POST['ebayaffinity_hideshiprules']);
					
					foreach ($_POST['ebayaffinity_hideshiprules'] as $hs) {
						if (!empty($hs)) {
							if ($_POST['ebayaffinity_shiprule_default'] == $hs) {
								$ebayaffinity_delerrors[] = "You're deleting your default shipping option. Please select a new default shipping option.";
								continue;
							} else if (isset($catshave[$hs])) {
								$ebayaffinity_delerrors[] = "Sorry, we weren't able to delete the shipping option because there are active listings associated with it. Please move the listings to another shipping option.";
								continue;
							} else {
								$prods = AffinityEbayInventory::getBySearchCategory('', 0, 1, '_affinity_shiprule', $hs, '', 0, 1, false);
								if ($prods[1] > 0) {
									$ebayaffinity_delerrors[] = "Sorry, we weren't able to delete the shipping option because there are active listings associated with it. Please move the listings to another shipping option.";
									continue;
								} else {
									delete_metadata('post', null, '_affinity_shiprule', $hs, true);
									delete_metadata('term', null, '_affinity_shiprule', $hs, true);
									unset($_POST['ebayaffinity_shiprule_handledays'][$hs]);
								}
							}
						}
					}
				}
				
				$ebayaffinity_delerrors = array_unique($ebayaffinity_delerrors);

				update_option('ebayaffinity_shipping_errors', '[]');
				$todel = array();
				if (!empty($_POST['ebayaffinity_shiprule_handledays'])) {
					foreach ($_POST['ebayaffinity_shiprule_handledays'] as $k=>$v) {
						if (!empty($_POST['ebayaffinity_shiprule_standard_freeshipping'][$k])) {
							$_POST['ebayaffinity_shiprule_standard_fee'][$k] = 0;
							$_POST['ebayaffinity_shiprule_additional_fee'][$k] = 0;
						}
						if (!empty($_POST['ebayaffinity_shiprule_express_freeshipping'][$k])) {
							$_POST['ebayaffinity_shiprule_express_fee'][$k] = 0;
							$_POST['ebayaffinity_shiprule_express_additional_fee'][$k] = 0;
						}
						$rule = new AffinityShippingRule();
						$rule->_id = $k;
						$todel[] = intval($k);
						$rule->_standard_freeshipping = empty($_POST['ebayaffinity_shiprule_standard_freeshipping'][$k])?0:1;
						$rule->_express_freeshipping = empty($_POST['ebayaffinity_shiprule_express_freeshipping'][$k])?0:1;
						if (empty($_POST['ebayaffinity_shiprule_standard_fee'][$k]) || floatval($_POST['ebayaffinity_shiprule_standard_fee'][$k]) == 0) {
							$rule->_standard_fee = '';
						} else {
							$rule->_standard_fee = number_format($_POST['ebayaffinity_shiprule_standard_fee'][$k], 2, '.', '');
						}
						if (empty($_POST['ebayaffinity_shiprule_standard_additional_fee'][$k]) || floatval($_POST['ebayaffinity_shiprule_standard_additional_fee'][$k]) == 0) {
							$rule->_standard_additional_fee = '';
						} else {
							$rule->_standard_additional_fee = number_format($_POST['ebayaffinity_shiprule_standard_additional_fee'][$k], 2, '.', '');
						}
						if (empty($_POST['ebayaffinity_shiprule_express_fee'][$k]) || floatval($_POST['ebayaffinity_shiprule_express_fee'][$k]) == 0) {
							$rule->_express_fee = '';
						} else {
							$rule->_express_fee = number_format($_POST['ebayaffinity_shiprule_express_fee'][$k], 2, '.', '');
						}
						if (empty($_POST['ebayaffinity_shiprule_express_additional_fee'][$k]) || floatval($_POST['ebayaffinity_shiprule_express_additional_fee'][$k]) == 0) {
							$rule->_express_additional_fee = '';
						} else {
							$rule->_express_additional_fee = number_format($_POST['ebayaffinity_shiprule_express_additional_fee'][$k], 2, '.', '');
						}
						$rule->_handledays = intval($_POST['ebayaffinity_shiprule_handledays'][$k]);
						if (empty($_POST['ebayaffinity_rate_table'])) {
							$rule->_rate_table = 0;
						} else {
							$rule->_rate_table = empty($_POST['ebayaffinity_rate_table'][$k])?0:1;
						}
						if (empty($_POST['ebayaffinity_shiprule_pudo'])) {
							$rule->_pudo = 0;
						} else {
							$rule->_pudo = empty($_POST['ebayaffinity_shiprule_pudo'][$k])?0:1;
						}
						$rule->_profile_id = empty($_POST['ebayaffinity_shiprule_profile_id'][$k])?'':$_POST['ebayaffinity_shiprule_profile_id'][$k];
						$rule->_profile_name = empty($_POST['ebayaffinity_shiprule_profile_name'][$k])?'':$_POST['ebayaffinity_shiprule_profile_name'][$k];

						if ($_POST['ebayaffinity_shiprule_default'] == $k) {
							$rule->_is_default = 1;
						} else {
							$rule->_is_default = 0;
						}

                        $rule->_discount_profile_id = empty($_POST['ebayaffinity_discount_profile'][$k])?'':$_POST['ebayaffinity_discount_profile'][$k];

						$rule->upsert(true);
					}
				}
				for ($i = 1; $i < $_POST['ebayaffinity_nextid']; $i++) {
					if (!isset($_POST['ebayaffinity_shiprule_handledays'][$i])) {
						delete_metadata('post', null, '_affinity_shiprule', $i, true);
						delete_metadata('term', null, '_affinity_shiprule', $i, true);
					}
				}
				AffinityShippingRule::trunc($todel);
				
				$a = json_decode(get_option('ebayaffinity_shipping_errors'), true);

				if (count($a) == 0) {
					update_option('ebayaffinity_setup2', 1);
				}
			} else if ($_GET['pnum'] == 4) {
				$rule = new AffinityReturnsRule();
				$rule->_profile_name = get_option('ebayaffinity_returns_profile_name');
				$rule->_profile_id = get_option('ebayaffinity_returns_profile_id');
				$rule->_refundoption = stripslashes($_POST['ebayaffinity_refundoption']);
				$rule->_returnwithin = stripslashes($_POST['ebayaffinity_returnwithin']);
				$rule->_returnaccepted = stripslashes($_POST['ebayaffinity_returnaccepted']);
				$rule->_returncosts = stripslashes($_POST['ebayaffinity_returncosts']);
				$rule->_description = stripslashes($_POST['ebayaffinity_returndesc']);
				$rule->upsert(true);
			
				update_option('ebayaffinity_returnaccepted', stripslashes($_POST['ebayaffinity_returnaccepted']));
				update_option('ebayaffinity_refundoption', stripslashes($_POST['ebayaffinity_refundoption']));
				update_option('ebayaffinity_returnwithin', stripslashes($_POST['ebayaffinity_returnwithin']));
				update_option('ebayaffinity_returncosts', stripslashes($_POST['ebayaffinity_returncosts']));
				update_option('ebayaffinity_returndesc', stripslashes($_POST['ebayaffinity_returndesc']));

				$a = json_decode(get_option('ebayaffinity_returns_errors'), true);
			
				if (count($a) == 0) {
					update_option('ebayaffinity_setup3', 1);
				}
			} else if ($_GET['pnum'] == 5) {
				update_option('ebayaffinity_pushinvenorytime', stripslashes($_POST['ebayaffinity_pushinvenorytime']));
				update_option('ebayaffinity_stockbuffer', intval($_POST['ebayaffinity_stockbuffer']));
				
				$pa = '';
				if ($_POST['ebayaffinity_priceadjust_posneg'] == '-') {
					$pa .= '-';
				}
				$pa .= floatval($_POST['ebayaffinity_priceadjust']);
				
				if ($_POST['ebayaffinity_priceadjust_percdoll'] == '$') {
					$pa .= 'num';
				}
				
				update_option('ebayaffinity_priceadjust', $pa);
				update_option('ebayaffinity_stocklevel', intval($_POST['ebayaffinity_stocklevel']));
				update_option('ebayaffinity_noautoattributes', empty($_POST['ebayaffinity_autoattributes'])?'1':'');
				update_option('ebayaffinity_noautohttp', empty($_POST['ebayaffinity_autohttp'])?'1':'');
				update_option('ebayaffinity_noautoresize', empty($_POST['ebayaffinity_autoresize'])?'1':'');
				
				wp_clear_scheduled_hook('wp_affinity_cron');
				wp_clear_scheduled_hook('wp_affinity_cron_inv');
				wp_clear_scheduled_hook('wp_affinity_cron_orders');
				wp_schedule_event(time() + 600, stripslashes($_POST['ebayaffinity_pushinvenorytime']), 'wp_affinity_cron');
				wp_schedule_event(time() + 1200, stripslashes($_POST['ebayaffinity_pushinvenorytime']), 'wp_affinity_cron_inv');
				wp_schedule_event(time() + 600, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 900, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 1800, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 2700, 'hourly', 'wp_affinity_cron_orders');
				
				update_option('ebayaffinity_clearlogtime', stripslashes($_POST['ebayaffinity_clearlogtime']));
				
				wp_clear_scheduled_hook('wp_affinity_cron_clearlog');
				update_option('ebayaffinity_logenabled', empty($_POST['ebayaffinity_logenabled'])?'':'1');
				
				if (!empty($_POST['ebayaffinity_clearlogtime'])) {
					wp_schedule_event(time() + 900, get_option('ebayaffinity_clearlogtime'), 'wp_affinity_cron_clearlog');
				}
			}
			
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityCrontab.php");
			AffinityCrontab::cron_sched_sync_all();
		}

		//print '<pre>';
		//print_r(AffinityBackendService::getProfile());
		//print '</pre>';

		require_once(__DIR__ . '/settings.php');
	}

	public function titleOptimisation() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		require_once(__DIR__.'/../model/AffinityTitleRule.php');
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		if (!empty($_POST)) {
			if (!empty($_POST['ebayaffinity_catshiprule'])) {
				foreach ($_POST['ebayaffinity_catshiprule'] as $k=>$v) {
					if (empty($v)) {
						delete_term_meta($k, '_affinity_titlerule');
					} else {
						update_term_meta($k, '_affinity_titlerule', $v);
					}
				}
			}
			if (!empty($_POST['ebayaffinity_prodshiprule'])) {
				foreach ($_POST['ebayaffinity_prodshiprule'] as $k=>$v) {
					if (empty($v)) {
						delete_post_meta($k, '_affinity_titlerule');
					} else {
						update_post_meta($k, '_affinity_titlerule', $v);
					}
				}
			}
			for ($i = 1; $i < $_POST['ebayaffinity_nextid']; $i++) {
				if (!isset($_POST['ruleTypes'][$i])) {
					delete_metadata('post', null, '_affinity_titlerule', $i, true);
					delete_metadata('term', null, '_affinity_titlerule', $i, true);
				}
			}
			AffinityTitleRule::trunc();
			if (!empty($_POST['ruleTypes'])) {
				foreach ($_POST['ruleTypes'] as $k=>$v) {
					$xml = new XMLWriter();
					$xml->openMemory();
					$xml->startElement('rules');
					foreach ($v as $kk=>$vv) {
						$xml->startElement('rule');
						$xml->writeAttribute('type', stripslashes($vv));
						$xml->text(stripslashes($_POST['ruleVals'][$k][$kk]));
						$xml->endElement();
					}
					$xml->endElement();
					$xml->endDocument();
					$xmlstr = $xml->outputMemory(true);
					$rule = new AffinityTitleRule();
					$rule->_id = $k;
					$rule->_title = stripslashes($_POST['title'][$k]);
					$rule->_titleTemplate = $xmlstr;
					if (isset($_POST['is_default']) && $_POST['is_default'] == $k) {
						$rule->_is_default = 1;
					} else {
						$rule->_is_default = 0;
					}
					$rule->upsert(true);
				}
			}
			
			require_once(__DIR__ . "/../ecommerce-adapters/AffinityCrontab.php");
			AffinityCrontab::cron_sched_sync_all();
		}
		require_once(__DIR__ . '/titleOptimisation.php');
	}

	public function logs() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		require_once(__DIR__ . '/logs.php');
	}
	
	public function help() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		wp_redirect('https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
		print "<script>location.href = 'https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce';</script>";
		exit;
	}
	
	public function feedback() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		wp_redirect('https://survey.ebay.com/survey/selfserve/1f4b/eci17020');
		print "<script>location.href = 'https://survey.ebay.com/survey/selfserve/1f4b/eci17020';</script>";
		exit;
	}
}
