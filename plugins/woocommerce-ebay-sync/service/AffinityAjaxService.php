<?php

class AffinityAjaxService {
	public function init() {
		add_action('wp_ajax_affinitynoticed', array($this, 'ajax_affinitynoticed'));
		add_action('wp_ajax_blockunblock', array($this, 'ajax_blockunblock'));
		add_action('wp_ajax_ebayproducts', array($this, 'ajax_ebayproducts'));
		add_action('wp_ajax_ebaycategories', array($this, 'ajax_ebaycategories'));
		add_action('wp_ajax_ebayitemspecificmapping', array($this, 'ajax_ebayitemspecificsmapping'));
		add_action('wp_ajax_ebaysubcategories', array($this, 'ajax_ebaysubcategories'));
		add_action('wp_ajax_ebaytitlerules', array($this, 'ajax_ebaytitlerules'));
		add_action('wp_ajax_ebaytitlerules_upsertrule', array($this, 'ajax_ebaytitlerules_upsertrule'));
		add_action('wp_ajax_affinity_product_shiprule', array($this, 'ajax_affinity_product_shiprule'));
		add_action('wp_ajax_affinity_category_shiprule', array($this, 'ajax_affinity_category_shiprule'));
		add_action('wp_ajax_nopriv_createorder', array($this, 'ajax_createorder'));
		add_action('wp_ajax_nopriv_croninv', array($this, 'ajax_croninv'));
		add_action('wp_ajax_nopriv_cron', array($this, 'ajax_cron'));
		add_action('wp_ajax_nopriv_cron_sync_all', array($this, 'ajax_cron_sync_all'));
		add_action('wp_ajax_ebayinventoryp', array($this, 'ajax_ebayinventoryp'));
	}
	
	function ajax_ebayinventoryp() {
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		
		$data = AffinityEbayInventory::inventoryset();
		
		$found = $data[1];
		$minprice = $data[2];
		$maxprice = $data[3];
		$data = $data[0];
		
		$out = array();
		
		foreach ($data as $datum) {
			$out[] = array(
					'id' => $datum['id'],
					'img' => $datum['img'],
					'title' => $datum['title'],
					'rrp_price' => $datum['rrp_price'],
					'sale_price' => $datum['sale_price'],
					'price' => $datum['price'],
					'blocked' => $datum['blocked'],
					'lasterror' => $datum['lasterror']
			);
		}
		
		print json_encode(array($out, $found, $_GET['paged'], $_GET['ebayaffinity-perpage']));
		wp_die();
	}
	
	function ajax_affinitynoticed() {
		delete_option('ebayaffinity_notice');
	}
	
	function ajax_blockunblock() {
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		if (isset($_POST['id']) && isset($_POST['blocked'])) {
			if ($_POST['blocked'] == '1') {
				$arr = explode(',', $_POST['id']);
				foreach ($arr as $el) {
					update_post_meta($el, '_affinity_block', '1');
				}
			} else {
				$arr = explode(',', $_POST['id']);
				foreach ($arr as $el) {
					delete_post_meta($el, '_affinity_block');
				}
			}
			$arr = explode(',', $_POST['id']);
			foreach ($arr as $el) {
				update_post_meta($_POST['id'], '_affinity_prod_update_status', '1');
			}
		}
	}

	function ajax_ebayproducts() {
		require_once(__DIR__.'/../model/AffinityEbayInventory.php');
		require_once(__DIR__.'/../model/AffinityEbayCategory.php');
		$_POST['s'] = stripslashes($_POST['s']);
		if (!isset($_POST['key'])) {
			$_POST['key'] = null;
		}
		if (!isset($_POST['value'])) {
			$_POST['value'] = null;
		}
		if (!isset($_POST['exkey'])) {
			$_POST['exkey'] = null;
		}
		if (!isset($_POST['exvalue'])) {
			$_POST['exvalue'] = null;
		}
		if (!isset($_POST['filteroutprods'])) {
			$_POST['filteroutprods'] = array();
		}
		if ($_POST['exkey'] === 'prodtwosets') {
			$catrules_cats = AffinityEbayCategory::getCategoriesCatRules();
			$catsRules = array();
			foreach ($catrules_cats as $k=>$catrules_cat) {
				if (!empty($catrules_cat[1])) {
					$catsRules[$k] = $k;
				}
			}
			if ($_POST['exvalue'] == 0) {
				$ret = array(
						AffinityEbayInventory::getBySearchCategory($_POST['s'], $_POST['categoryId'], $_POST['paged'], $_POST['key'], $_POST['value'], $_POST['exkey'], 1, 20, true, $catsRules),
						AffinityEbayInventory::getBySearchCategory($_POST['s'], $_POST['categoryId'], 1, $_POST['key'], $_POST['value'], $_POST['exkey'], 2, 1, false, $catsRules)
				);
			} else {
				$ret = array(
						AffinityEbayInventory::getBySearchCategory($_POST['s'], $_POST['categoryId'], 1, $_POST['key'], $_POST['value'], $_POST['exkey'], 1, 1, true, $catsRules),
						AffinityEbayInventory::getBySearchCategory($_POST['s'], $_POST['categoryId'], $_POST['paged'], $_POST['key'], $_POST['value'], $_POST['exkey'], 2, 20, false, $catsRules)
				);
			}

			print json_encode($ret);
		} else {
			print json_encode(AffinityEbayInventory::getBySearchCategory($_POST['s'], $_POST['categoryId'], $_POST['paged'], $_POST['key'], $_POST['value'], $_POST['exkey'], $_POST['exvalue'], null, null, null, null, $_POST['filteroutprods']));
		}

		wp_die();
	}

	function ajax_ebaycategories() {
		require_once(__DIR__.'/../model/AffinityEbayCategory.php');
		print json_encode(AffinityEbayCategory::getByParent(intval($_POST['id'])));
		wp_die();
	}

	function ajax_ebaysubcategories() {
		require_once(__DIR__.'/../model/AffinityEbayCategory.php');
		print json_encode(AffinityEbayCategory::getSubsByParent(intval($_POST['id'])));
		wp_die();
	}

	function ajax_ebayitemspecificsmapping() {
		if (!current_user_can( 'manage_options'))  {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		require_once(__DIR__ . '/../model/AffinityItemSpecificMapping.php');
		AffinityItemSpecificMapping::saveFromJson($_POST['obj']);

		require_once(__DIR__ . "/../ecommerce-adapters/AffinityCrontab.php");
		AffinityCrontab::cron_sched_sync_all();
		wp_die();
	}


	function ajax_ebaytitlerules() {
		require_once(__DIR__.'/../model/AffinityTitleRule.php');
		print json_encode(AffinityTitleRule::getAllRules());
		wp_die();
	}

	function ajax_ebaytitlerules_upsertrule() {
		require_once(__DIR__.'/../model/AffinityTitleRule.php');
		require_once(__DIR__.'/../ecommerce-adapters/AffinityEcommerceCategory.php');

		$obj = new AffinityTitleRule();
		if (!empty($_POST['id'])) {
			$obj->_id = $_POST['id'];
		}
		if (!empty($_POST['titleTemplate'])) {
			$obj->_titleTemplate = stripslashes($_POST['titleTemplate']);
		}
		if (!empty($_POST['arrEbayCategoriesToApply'])) {
			$obj->_arrEbayCategoriesToApply = explode(',', $_POST['arrEbayCategoriesToApply']);
		}
		if (!empty($_POST['arrProductsToApply'])) {
			$obj->_arrProductsToApply = explode(',', $_POST['arrProductsToApply']);;
		}
		print $obj->upsert();

		wp_die();
	}

	function ajax_affinity_category_shiprule() {
		if (isset($_POST['id']) && isset($_POST['rule'])) {
			if (!empty($_POST['rule'])) {
				update_term_meta($_POST['id'], '_affinity_shiprule', intval($_POST['rule']));
			} else {
				delete_term_meta($_POST['id'], '_affinity_shiprule');
			}
		}
	}

	function ajax_createorder() {
		require_once(__DIR__.'/AffinityLocalUpdateService.php');

		$token = $_GET['aT'];
		$arrResult = AffinityLocalUpdateService::orderNotificationReceivedToken($token);
		@ob_end_clean();
		
		echo json_encode($arrResult);
		exit();
	}
	
	function ajax_cron_sync_all() {
		require_once(__DIR__.'/AffinityLocalUpdateService.php');
	
		$token = $_GET['aT'];
		$arrResult = AffinityLocalUpdateService::cronSyncAll($token);
		@ob_end_clean();
	
		echo json_encode($arrResult);
		exit();
	}
	
	function ajax_croninv() {
		$t = wp_next_scheduled('wp_affinity_cron_inv');
		$a = wp_get_schedule('wp_affinity_cron_inv');
		$s = wp_get_schedules();
		$diff = $s[$a]['interval'] - ($t - time());
		if ($diff < 120 && $diff > -120) {
			print '{"not working":"not working"}';
			exit();
		}
		
		require_once(__DIR__.'/AffinityLocalUpdateService.php');
	
		$token = $_GET['aT'];
		$arrResult = AffinityLocalUpdateService::cronInv($token);
		@ob_end_clean();
	
		echo json_encode($arrResult);
		exit();
	}
	
	function ajax_cron() {
		$t = wp_next_scheduled('wp_affinity_cron');
		$a = wp_get_schedule('wp_affinity_cron');
		$s = wp_get_schedules();
		$diff = $s[$a]['interval'] - ($t - time());
		if ($diff < 120 && $diff > -120) {
			print '{"not working":"not working"}';
			exit();
		}
		
		require_once(__DIR__.'/AffinityLocalUpdateService.php');
	
		$token = $_GET['aT'];
		$arrResult = AffinityLocalUpdateService::cron($token);
		@ob_end_clean();
	
		echo json_encode($arrResult);
		exit();
	}

	function ajax_affinity_product_shiprule() {
		if (isset($_POST['id']) && isset($_POST['rule'])) {
			if (!empty($_POST['rule'])) {
				update_post_meta($_POST['id'], '_affinity_shiprule', intval($_POST['rule']));
			} else {
				delete_post_meta($_POST['id'], '_affinity_shiprule');
			}
		}
	}
}
