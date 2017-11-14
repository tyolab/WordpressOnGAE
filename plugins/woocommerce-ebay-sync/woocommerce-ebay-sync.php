<?php
/**
 * Plugin Name: WooCommerce eBay Sync
 * Description: Connect your WooCommerce to your eBay store.
 * Version: 1.9.96
 * Author: eBay Australia
 * Author URI: http://www.ebay.com.au/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '9907342c503c0d70410f406e64a808a0', '1953674' );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ||
		in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', array_keys(get_site_option('active_sitewide_plugins'))))) {
	if (!function_exists('affinity_empty')) {
		function affinity_empty($value) {
			if (empty($value) && (!is_numeric($value))) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	if (!class_exists('eBaySync')) {
		class eBaySync {
			protected $tag = 'ebaysync';
			protected $name = 'eBay Sync';
			protected $version = '1.9.96';
			protected $notice = '';
			
			public function getVersion() {
				return $this->version;
			}
			
			public function pluginWasActivated() {
				require_once(__DIR__.'/model/AffinityEbayInstCategory.php');
				AffinityEbayInstCategory::install();
				
				require_once(__DIR__.'/ecommerce-adapters/AffinityDataLayer.php');
				AffinityDataLayer::createInitialOptions();
				AffinityDataLayer::createOrUpdateSchema();
				
				require_once(__DIR__ . "/ecommerce-adapters/AffinityCrontab.php");
				AffinityCrontab::createInitialCrontab();
			}
			
			public function pluginWasUpdated() {
				require_once(__DIR__.'/ecommerce-adapters/AffinityDataLayer.php');
				AffinityDataLayer::createOrUpdateSchema();
				
				wp_clear_scheduled_hook('wp_affinity_cron_orders');
				wp_schedule_event(time() + 600, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 900, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 1800, 'hourly', 'wp_affinity_cron_orders');
				wp_schedule_event(time() + 600 + 2700, 'hourly', 'wp_affinity_cron_orders');
				
				wp_clear_scheduled_hook('wp_affinity_cron_cats');
				wp_schedule_event(time() + 120, 'daily', 'wp_affinity_cron_cats');
				
				add_option('ebayaffinity_clearlogtime', 'monthly');
				add_option('ebayaffinity_logenabled', '1');
				add_option('ebayaffinity_logran', 0);
				$logtime = get_option('ebayaffinity_clearlogtime');
				wp_clear_scheduled_hook('wp_affinity_cron_clearlog');
				if (!empty($logtime)) {
					wp_schedule_event(time() + 900, $logtime, 'wp_affinity_cron_clearlog');
				}
				
				wp_clear_scheduled_hook('wp_affinity_cron_diagtool');
				wp_schedule_event(time() + 60, 'daily', 'wp_affinity_cron_diagtool');
				
				require_once(__DIR__.'/model/AffinityEbayInventory.php');
                AffinityEbayInventory::syncAll();

			}
			
			public function pluginWasDeactivated() {
				require_once(__DIR__ . "/ecommerce-adapters/AffinityCrontab.php");
				AffinityCrontab::clearCrontab();
			}
			
			public function moreScheds($schedules) {
				$schedules['weekly'] = array(
						'interval' => 604800,
						'display' => __('Once weekly')
				);
				$schedules['monthly'] = array(
						'interval' => 2592000,
						'display' => __('Once monthly')
				);
				return $schedules;
			}
			
			public function getAge() {
				$time = filemtime(__FILE__);
				return str_replace('+00:00', '.000Z', gmdate('c', $time));
			}
			
			public function getDist() {
				$path_parts = pathinfo(__FILE__);
				
				if ($path_parts['basename'] == 'ebaysync.php') {
					return 'ZIP';
				} else {
					return 'WooCommerce';
				}
			}
			
			public function __construct() {
				global $pagenow;
				
				if (!empty($_GET['ebayaffinity-perpage'])) {
					setcookie('affinity_perpage', intval($_GET['ebayaffinity-perpage']), time() + 31536000);
				}
				
				if ($pagenow === 'admin.php' || $pagenow === "admin-ajax.php") {
					@ob_start();
				}
				
				if (defined('DOING_CRON')) {
					$_COOKIE['wcaiocc_user_currency_cookie'] = 'AUD';
				}
				
				$lastversion = get_option('ebayaffinity_lastversion');
				if (empty($lastversion) || $lastversion != $this->version) {
					update_option('ebayaffinity_notice', $this->notice);
					$this->pluginWasUpdated();
					update_option('ebayaffinity_lastversion', $this->version);
				}
				
				require_once(__DIR__ . "/includes/AffinityPagesManager.php");
				$pagesManager = new AffinityPagesManager();
				
				
				add_action('admin_menu', array($pagesManager, 'initAdminMenu'));

				require_once(__DIR__ . "/service/AffinityAjaxService.php");
				$ajaxService = new AffinityAjaxService();
				$ajaxService->init();
				
				require_once(__DIR__ . "/ecommerce-adapters/AffinityEcommerceHooks.php");
				$ecommerceHooks = new AffinityEcommerceHooks();
				$ecommerceHooks->initHooks();
				
				require_once(__DIR__ . "/ecommerce-adapters/AffinityCrontab.php");
				$affinityCrontab = new AffinityCrontab();
				$affinityCrontab->createCrontabHooks();
				
				register_activation_hook(__FILE__, array($this, 'pluginWasActivated'));
				register_deactivation_hook(__FILE__, array($this, 'pluginWasDeactivated'));
				
				add_filter('cron_schedules', array($this, 'moreScheds'));
			}
		}
		global $ebaysync;
		
		$ebaysync = new eBaySync();
	}
}
