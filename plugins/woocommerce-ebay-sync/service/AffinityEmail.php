<?php
class AffinityEmail {
	static public function toEmail() {
		return 'DL-eBay-AU-Sync-Support@ebay.com';
	}
	
	static public function installs() {
		$sentcheck = get_option('ebayaffinity_sent_installs');
		
		if (empty($sentcheck)) {
			$subject = 'Seller has successfully installed the eBay Sync plugin';
			$message = 'Hi Sync Support Team,<br>
<br>
Seller has successfully installed the Sync plugin.<br>
Visit website: <a href="'.htmlspecialchars(get_option('siteurl')).'">'.htmlspecialchars(get_option('siteurl')).'</a><br>
<br>
Kind Regards<br>
eBay Sync Team';
		
			update_option('ebayaffinity_sent_installs', 1);
			self::sendEmail($subject, $message);
		}
	}
	
	static public function setups() {
		$sentcheck = get_option('ebayaffinity_sent_setups');
		
		if (empty($sentcheck)) {
			$subject = get_option('ebayaffinity_ebayuserid').' successfully completed the seller set up process for eBay Sync';
			$message = 'Hi Sync Support Team,<br>
<br>
'.get_option('ebayaffinity_ebayuserid').' has successfully completed the seller setup process.<br>
Track seller\'s progress on eBay: <a href="'.get_option('ebayaffinity_ebaysite').'usr/'.rawurlencode(get_option('ebayaffinity_ebayuserid')).'">'.htmlspecialchars(get_option('ebayaffinity_ebayuserid')).'</a><br>
<br>
Kind Regards<br>
eBay Sync Team';
			str_replace("\r", "", $message);
			update_option('ebayaffinity_sent_setups', 1);
			self::sendEmail($subject, $message);
		}
	}
	
	static public function products($itemid) {
		$sentcheck = get_option('ebayaffinity_sent_products');
		
		if (empty($sentcheck)) {
			require_once(__DIR__.'/../ecommerce-adapters/AffinityDataLayer.php');

			$post_id = AffinityDataLayer::findObjectIdWithGivenData('affinity_ebayitemid', $itemid);
		
			$post = get_post($post_id);
		
			$title = $post->post_title;
		
			$subject = get_option('ebayaffinity_ebayuserid').' successfully listed first product on eBay';
			$message = 'Hi Sync Support Team,<br>
<br>
'.get_option('ebayaffinity_ebayuserid').' has successfully listed the very first product on eBay<br>
View product: <a href="'.get_option('ebayaffinity_ebaysite').'itm/'.rawurlencode($itemid).'">'.htmlspecialchars($title).'</a><br>
Track seller\'s progress on eBay: <a href="'.get_option('ebayaffinity_ebaysite').'usr/'.rawurlencode(get_option('ebayaffinity_ebayuserid')).'">'.htmlspecialchars(get_option('ebayaffinity_ebayuserid')).'</a><br>
<br>
Kind Regards<br>
eBay Sync Team';
			update_option('ebayaffinity_sent_products', 1);
			self::sendEmail($subject, $message);
		}
	}
	
	static public function sendEmail($subject, $message) {
		$headers  = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type: text/html; charset=".get_bloginfo('charset')."" . "\r\n";
		$headers .= "From: ".get_option('woocommerce_email_from_name')." <".get_option('woocommerce_email_from_address').">" . "\r\n";
		
		$to = self::toEmail();
		
		wp_mail($to, $subject, $message, $headers);
	}
}