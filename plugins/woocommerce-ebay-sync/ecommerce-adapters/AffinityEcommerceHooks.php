<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AffinityEcommerceHooks
 *
 * @author mlacava
 */
class AffinityEcommerceHooks {
	public function initHooks() {
		//Product and Order Hooks 
		add_action('wp_insert_post', array($this, 'wpPostAfterSaved'), 999, 3);
		add_action('wp_trash_post', array($this, 'woo_trash'));
		
		add_action('woocommerce_order_status_completed', array($this, 'woo_order_complete'), 0);

		add_action('woocommerce_product_options_general_product_data', array($this, 'woo_add_desc'));
		add_action('woocommerce_process_product_meta', array($this, 'woo_save_desc'));
		
		add_action('woocommerce_product_after_variable_attributes', array($this, 'woo_add_var'), 10, 3);
		add_action('woocommerce_save_product_variation', array($this, 'woo_save_var'), 10, 1);
		
		add_action('woocommerce_duplicate_product', array($this, 'woo_dupe'));
		add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'woo_add_order_fields'));

		add_action('restrict_manage_posts', array($this, 'woo_add_filter'), 50);
		add_action('posts_where', array($this, 'woo_order_filter'));
		add_filter('manage_edit-shop_order_columns', array($this, 'woo_order_column'));
		add_action('manage_shop_order_posts_custom_column' , array($this, 'woo_order_dat'), 10, 2);
		add_action('admin_notices', array($this, 'woo_notice'));
		add_action('woocommerce_payment_complete', array($this, 'woo_order_handler'), 10, 1);
		
		add_action('pmxi_before_xml_import', array($this, 'before_xml_import'), 10, 1);
		add_action('pmxi_after_xml_import', array($this, 'after_xml_import'), 10, 1);
		add_action('pmxi_saved_post', array($this, 'post_saved'), 10, 1);
		
		add_action('admin_notices', array($this, 'woo_admin_notices'));
	}
	
	public function woo_order_complete($orderid) {
		$_POST['_affinity_marked_as_sent'] = 'yes';
		
		$post = get_post($orderid);
		
		require_once(__DIR__ . "/AffinityEcommerceOrder.php");
		AffinityEcommerceOrder::orderHasChanged($post);
			
		require_once(__DIR__ . "/AffinityEcommerceProduct.php");
		$order = new WC_Order($orderid);
		$items = $order->get_items();
		foreach ($items as $item) {
			$post = get_post($item['product_id']);
			if ($post instanceof WP_Post) {
				AffinityEcommerceProduct::productHasChanged($post, false);
			}
		}
	}
	
	public function woo_admin_notices() {
		if ((!empty($_GET['page'])) && $_GET['page'] === 'ebay-sync-settings' && empty($_GET['pnum'])) {
			return;
		}
	
		$url = 'admin.php?page=ebay-sync-settings';
		
		$setup_ebayuserid = get_option('ebayaffinity_ebayuserid');
		$setup_token = get_option('affinityPushAccessToken');

		if (empty($setup_ebayuserid) || empty($setup_token)) {
			echo '<div class="updated fade"><p>' . sprintf( __( '%seBay Sync is almost ready.%s To get started, %sconnect your eBay account%s.', 'woocommerce-ebay-integration' ), '<strong>', '</strong>', '<a href="' . esc_url( $url ) . '">', '</a>' ) . '</p></div>' . "\n";
		}
	}
	
	function before_xml_import($import_id) {
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."ebayaffinity_postmeta_bak");
		$wpdb->query("CREATE TABLE ".$wpdb->prefix."ebayaffinity_postmeta_bak LIKE ".$wpdb->postmeta."");
		$wpdb->query("INSERT INTO ".$wpdb->prefix."ebayaffinity_postmeta_bak SELECT DISTINCT pm.* FROM ".$wpdb->posts." AS p, ".$wpdb->postmeta." AS pm WHERE p.post_type IN ('product', 'product_variation') AND
			p.id = pm.post_id AND pm.meta_key IN ('_affinity_suggestedCatId', '_affinity_ebaycategory', '_affinity_ebayitemid', '_affinity_item_id', '_affinity_prod_all_active_variants',
			'_affinity_prod_all_variants', '_affinity_prod_arr_adaptation_errors', '_affinity_prod_arr_adaptation_warnings','_affinity_prod_arr_client_errors',
			'_affinity_prod_arr_client_warnings', '_affinity_prod_ebay_sku', '_affinity_prod_last_successful_update', '_affinity_prod_update_failure_count',
			'_affinity_prod_update_status', '_affinity_shiprule',
			'_affinity_titlerule', '_affinity_titleopt', '_affinity_block', '_affinity_ebaydesc', '_affinity_ebayprice', '_ebayprice', '_ebayuseshort', '_ebaytemplate', '_ebaydesc', '_affinity_posted_as')");
	}
	
	function post_saved($id) {
		global $wpdb;
		$wpdb->query("INSERT IGNORE INTO ".$wpdb->postmeta." SELECT * FROM ".$wpdb->prefix."ebayaffinity_postmeta_bak WHERE post_id = ".intval($id));
	}
	
	function after_xml_import($import_id) {
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."ebayaffinity_postmeta_bak");
	}
	
	function woo_order_handler($order_id) {
		require_once(__DIR__ . "/AffinityEcommerceProduct.php");
		$order = new WC_Order($order_id);
		$items = $order->get_items();
		foreach ($items as $item) {
			$post = get_post($item['product_id']);
			AffinityEcommerceProduct::productHasChanged($post, false);
		}
	}
	
	function woo_notice() {
		$ordererror = get_option('_affinity_order_error');
		if (!empty($ordererror)) {
			print '<div class="notice notice-error"><p>'.htmlspecialchars($ordererror).'</p></div>';
			delete_option('_affinity_order_error');
		}
		$notice = get_option('ebayaffinity_notice');
		if (!empty($notice)) {
			print '<div class="notice notice-info notice-affinity is-dismissible">'.$notice.'</div>';
		}
	}
	
	function woo_dupe($new_id, $post) {
		delete_post_meta($new_id, '_affinity_ebayitemid');
		update_post_meta($new_id, '_affinity_prod_update_status', 1);
		delete_post_meta($new_id, '_affinity_prod_ebay_sku');
		delete_post_meta($new_id, '_affinity_prod_arr_client_warnings');
		delete_post_meta($new_id, '_affinity_prod_arr_client_errors');
		delete_post_meta($new_id, '_affinity_prod_arr_adaptation_warnings');
		delete_post_meta($new_id, '_affinity_prod_arr_adaptation_errors');
		delete_post_meta($new_id, '_affinity_prod_update_failure_count');
		delete_post_meta($new_id, '_affinity_prod_update_failure_count');
		
		delete_post_meta($new_id, '_affinity_prod_active_variants');
		delete_post_meta($new_id, '_affinity_prod_all_active_variants');
		delete_post_meta($new_id, '_affinity_prod_all_inactive_variants');
		delete_post_meta($new_id, '_affinity_prod_all_variants');
		delete_post_meta($new_id, '_affinity_prod_active_variants');
		delete_post_meta($new_id, '_affinity_prod_inactive_variants');
		
		$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => $new_id, 'posts_per_page' => 100));
		
		while ($variationloop->have_posts()) {
			$variationloop->the_post();
			$new_id = get_the_ID();
			
			delete_post_meta($new_id, '_affinity_ebayitemid');
			delete_post_meta($new_id, '_affinity_prod_ebay_sku');
			delete_post_meta($new_id, '_affinity_prod_arr_client_warnings');
			delete_post_meta($new_id, '_affinity_prod_arr_client_errors');
			delete_post_meta($new_id, '_affinity_prod_arr_adaptation_warnings');
			delete_post_meta($new_id, '_affinity_prod_arr_adaptation_errors');
			delete_post_meta($new_id, '_affinity_prod_update_failure_count');
			delete_post_meta($new_id, '_affinity_prod_update_failure_count');
			delete_post_meta($new_id, '_affinity_prod_active_variants');
			delete_post_meta($new_id, '_affinity_prod_all_active_variants');
			delete_post_meta($new_id, '_affinity_prod_all_inactive_variants');
			delete_post_meta($new_id, '_affinity_prod_all_variants');
			delete_post_meta($new_id, '_affinity_prod_active_variants');
			delete_post_meta($new_id, '_affinity_prod_inactive_variants');
		}
	}
	
	public function wpPostAfterSaved($postId, $post, $isAnUpdate) {
		$postType = $post->post_type;

		if($postType === "product") {
			require_once(__DIR__ . "/AffinityEcommerceProduct.php");
			AffinityEcommerceProduct::productHasChanged($post, !empty($_POST['syncit']));
			return;
		}

		if($postType === "shop_order") {
			require_once(__DIR__ . "/AffinityEcommerceOrder.php");
			AffinityEcommerceOrder::orderHasChanged($post);
			
			require_once(__DIR__ . "/AffinityEcommerceProduct.php");
			$order = new WC_Order($postId);
			$items = $order->get_items();
			foreach ($items as $item) {
				$post = get_post($item['product_id']);
				if ($post instanceof WP_Post) {
					AffinityEcommerceProduct::productHasChanged($post, false);
				}
			}
			
			$shipPrice = get_post_meta($postId, '_order_shipping_affinity', true);
			if (!empty($shipPrice)) {
				update_post_meta($postId, '_order_shipping', $shipPrice);
			}
		}
	}

	function woo_trash($id) {
		require_once(__DIR__ . "/AffinityEcommerceProduct.php");
		require_once(__DIR__. '/../model/AffinityProduct.php');
		//exit();
		
		if (is_array($_GET['post'])) {
			foreach ($_GET['post'] as $pid) {
				$objEcommerceProduct = AffinityEcommerceProduct::get($pid);
				if ($objEcommerceProduct !== false) {
					AffinityProduct::productWasUnpublished($objEcommerceProduct, true);
				}
			}
		} else {
			$objEcommerceProduct = AffinityEcommerceProduct::get($_GET['post']);
			if ($objEcommerceProduct !== false) {
				AffinityProduct::productWasUnpublished($objEcommerceProduct, true);
			}
		}
	}

	function woo_add_var($loop, $variation_data, $variation) {
		woocommerce_wp_text_input(
				array(
						'id' => '_ebayprices[' . $variation->ID . ']',
						'label' => 'eBay Price: ('.get_woocommerce_currency_symbol().')',
						'value' => get_post_meta($variation->ID, '_ebayprice', true),
						'data_type' => 'price'
				)
		);
	}
	
	function woo_save_var($post_id) {
		foreach ($_POST['_ebayprices'] as $k=>$v) {
			if (!empty($v)) {
				update_post_meta($k, '_ebayprice', stripslashes($v));
			} else {
				delete_post_meta($k, '_ebayprice');
			}
		}
		if (!empty($_POST['product_id'])) {
			$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => $_POST['product_id'], 'posts_per_page' => 100));

			$max = 0;
			while ($variationloop->have_posts()) {
				$variationloop->the_post();
				$a = get_post_meta(get_the_ID(), '_ebayprice', true);
				if ($a > $max) {
					$max = $a;
				}
			}
			if (!empty($max)) {
				update_post_meta($_POST['product_id'], '_ebayprice', $max);
			} else {
				delete_post_meta($_POST['product_id'], '_ebayprice');
			}
		}
	}
		
	function woo_add_desc() {
		global $woocommerce, $post;

		echo '';

		woocommerce_wp_textarea_input(
				array(
						'id' => '_ebaydesc',
						'label' => __('eBay Description', 'woocommerce'),
						'placeholder' => '',
						'desc_tip' => 'true',
						'description' => __('eBay Description', 'woocommerce')
				)
		);

		$ebaytemplate = get_post_meta($post->ID, '_ebaytemplate', true);

		if (strlen($ebaytemplate) === 0) {
			update_post_meta($post->ID, '_ebaytemplate', 1);
		}
		
		woocommerce_wp_select(
				array(
						'id' => '_ebaycondition',
						'label' => 'eBay Condition *',
						'options' => array(
								'NEW' => __('New', 'woocommerce'),
								'NEW_OTHER' => __('New other (category specific)', 'woocommerce'),
								'MANUFACTURER_REFURBISHED' => __('Manufacturer refurbished', 'woocommerce'),
								'SELLER_REFURBISHED' => __('Seller refurbished', 'woocommerce'),
								'USED_EXCELLENT' => __('Used', 'woocommerce'),
								'FOR_PARTS_OR_NOT_WORKING' => __('For parts or not working', 'woocommerce')
						)
				)
		);
		
		woocommerce_wp_text_input(
				array(
						'id' => '_ebayprice',
						'label' => 'eBay Price ('.get_woocommerce_currency_symbol().')',
						'data_type' => 'price'
				)
		);

		woocommerce_wp_checkbox(
				array(
						'id' => '_ebaytemplate',
						'label' => __('Use eBay/Custom Template', 'woocommerce'),
						'cbvalue' => 1
				)
		);
		
		$useshort = get_option('ebayaffinity_useshort');
		
		if (empty($useshort)) {
			woocommerce_wp_checkbox(
					array(
							'id' => '_ebayuseshort',
							'label' => __('Use short description for eBay', 'woocommerce'),
							'cbvalue' => 1
					)
			);
		} else {
			woocommerce_wp_checkbox(
					array(
							'id' => '_ebayuseshort',
							'label' => __('Use short description for eBay', 'woocommerce'),
							'cbvalue' => 5000
					)
			);
		}
	}

	function woo_add_order_fields() {
		woocommerce_wp_checkbox( 
			array( 
				'id'            => '_affinity_marked_as_sent', 
				'wrapper_class' => 'show_if_simple', 
				'label'         => __('', 'woocommerce' ), 
				'description'   => __( 'Mark as sent', 'woocommerce' ) 
			)
		);

		woocommerce_wp_text_input(
			array(
				'id' => '_affinity_tracking_number',
				'label' => __('Tracking Number', 'woocommerce'),
				'placeholder' => '# Tracking Number',
			)
		);
		woocommerce_wp_select( 
			array( 
				'id'      => '_affinity_carrier_name', 
				'label'   => __( 'Carrier Name', 'woocommerce' ),  
				'options' => array(
					''   => __( '', 'woocommerce' ),
					'australiapost'   => __( 'Australia Post', 'woocommerce' ),
					'toll'   => __( 'Toll', 'woocommerce' ),
					'couriersplease' => __( 'Couriers Please', 'woocommerce' ),
					'fastway' => __( 'Fastway', 'woocommerce' ),
					'dhl' => __( 'DHL', 'woocommerce' ),
					'startrack' => __( 'StarTrack', 'woocommerce' ),
					'temando' => __( 'Temando', 'woocommerce' ),
					'allied' => __( 'Allied Express', 'woocommerce' ),
					'tnt' => __( 'TNT', 'woocommerce' ),
					'packsend' => __( 'Pack & Send', 'woocommerce' ),
					'smartsend' => __( 'SmartSend', 'woocommerce' ),
					'ego' => __( 'e-go', 'woocommerce' ),
					'fedex' => __( 'FedEx', 'woocommerce' ),
					'other' => __( 'Other', 'woocommerce' )
				)
			)
		);
	}

	function woo_save_desc($post_id) {
		delete_post_meta($post_id, '_affinity_suggestedCatId');
		if ((!empty($_POST['ebaydesc'])) && (!empty($_POST['affinity-copy']))) {
			update_post_meta($post_id, '_ebaydesc', stripslashes($_POST['ebaydesc']));
		} else {
			delete_post_meta($post_id, '_ebaydesc');
		}
		
		if (empty($_POST['_ebayprices'])) {
			if ((!empty($_POST['_ebayprice'])) && (!empty($_POST['_ebayprice']))) {
				update_post_meta($post_id, '_ebayprice', stripslashes($_POST['_ebayprice']));
			} else {
				delete_post_meta($post_id, '_ebayprice');
			}
		}
		
		if (!empty($_POST['_ebaytemplate'])) {
			update_post_meta($post_id, '_ebaytemplate', 1);
		} else {
			update_post_meta($post_id, '_ebaytemplate', 0);
		}

		if (!empty($_POST['_ebaycondition'])) {
			update_post_meta($post_id, '_ebaycondition', $_POST['_ebaycondition']);
		} else {
			delete_post_meta($post_id, '_ebaycondition');
		}
		
		$useshort = get_option('ebayaffinity_useshort');
		
		if (empty($useshort)) {
			if (!empty($_POST['_ebayuseshort'])) {
				update_post_meta($post_id, '_ebayuseshort', 1);
			} else {
				update_post_meta($post_id, '_ebayuseshort', 0);
			}
		}
	}

	function woo_add_filter() {
		global $typenow;
		if ($typenow === 'shop_order') {
?>
			<input type="checkbox" name="affinity_ebayorders" id="affinity_ebayorders" style="margin-left: 4px; margin-top: 7px;" <?php print empty($_GET['affinity_ebayorders'])?'':'checked'?> value="1">
			<label style="padding-top: 7px; margin-right: 4px;" for="affinity_ebayorders">eBay orders only</label>
<?php 
		}
	}

	function woo_order_filter($where) {
		if (is_search()) {
			if (!empty($_GET['affinity_ebayorders'])) {
				global $wpdb;
				$where .= " AND ".$wpdb->posts.".id IN (SELECT post_id FROM ".$wpdb->postmeta." WHERE ".$wpdb->postmeta.".meta_key = '_affinity_ebayorder')";
			}
		}
		return $where;
	}

	function woo_order_column($columns) {
		$asset_dir = plugins_url('/../assets/', __FILE__);
		$columns = array_slice($columns, 0, 3, true) + array('_affinity_ebayorder' => '<img src="'.htmlspecialchars($asset_dir).'ebay.png" alt="eBay order" title="eBay order">') + array_slice($columns, 3, count($columns) - 1, true);
		return $columns;
	}

	function woo_order_dat($column) {
		global $post, $woocommerce, $the_order;
		switch ($column) {
			case '_affinity_ebayorder':
				if (method_exists($the_order, 'get_id')) {
					$a = get_post_meta($the_order->get_id(), '_affinity_ebayorder', true);
				} else {
					$a = get_post_meta($the_order->id, '_affinity_ebayorder', true);
				}
				if (!empty($a)) {
					$asset_dir = plugins_url('/../assets/', __FILE__);			
?>
					<img src="<?php print htmlspecialchars($asset_dir).'ebay.png'?>" alt="eBay order" title="eBay order">
<?php 
				}
				break;
		}
	}
}
