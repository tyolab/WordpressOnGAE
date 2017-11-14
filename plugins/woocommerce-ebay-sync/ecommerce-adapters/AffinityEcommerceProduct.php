<?php

class AffinityEcommerceProduct {
	public $id;
	public $title;
	public $shortDescription;
	public $description;
	public $priceIncludingTax;
	public $isStockBeingManaged;
	public $qtyAvailable;
	public $status;

	public $objMainImage;
	public $arrObjAdditionalImages;

	public $arrEcommerceItemSpecifics = array();
	public $arrEcommerceCategories = array();
	public $arrEcommerceShipping = array();

	public $arrEcommerceProductVariations = array();
	public $arrAllVariationDifferentItemSpecifics = array();
	public $arrCurrentVariationItemSpecifics = array();

	private function getVariations() {
		$objWpQuery = new WP_Query(array(
            'post_type' => 'product_variation',
            'post_parent' => $this->id,
			'posts_per_page' => 100
        ));

        if(!$objWpQuery->have_posts()) {
            return array();
        }

		require_once(__DIR__ . "/AffinityEcommerceItemSpecific.php");

        $arrEcommerceProductVariations = array();
		$arrVariationItemSpecifics = array();

		while($objWpQuery->have_posts()) {
			$objWpQuery->the_post();

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$objNativeProduct = new WC_Product( get_the_ID() );
			} else {
				$objNativeProduct = new WC_Product_Variation( get_the_ID() );
			}
			$objVariationProduct = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);

			$ab = AffinityEcommerceItemSpecific::getVariationAttributesAsItemSpecifics( $objVariationProduct->id );

			$arrVariationItemSpecific = $ab[0];
			$arrVariationItemSpecifics = array_merge($arrVariationItemSpecifics, $arrVariationItemSpecific);
			$objVariationProduct->arrCurrentVariationItemSpecifics = $arrVariationItemSpecific;

			$arrEcommerceProductVariations[$ab[1]] = $objVariationProduct;
		}

		$arrVariationItemSpecifics_tmp = array();

		foreach($arrVariationItemSpecifics as $val) {
			$arrVariationItemSpecifics_tmp[$val->orderOrder] = $val;
		}

		ksort($arrVariationItemSpecifics_tmp);
		$arrVariationItemSpecifics_tmp= array_values($arrVariationItemSpecifics_tmp);

		$arrVariationItemSpecifics = $arrVariationItemSpecifics_tmp;

		ksort($arrEcommerceProductVariations);
		$arrEcommerceProductVariations = array_values($arrEcommerceProductVariations);

		$this->arrAllVariationDifferentItemSpecifics = AffinityEcommerceItemSpecific::getArrUniqueItemSpecificsIdsAndValues($arrVariationItemSpecifics);
		return $arrEcommerceProductVariations;
    }

    public static function get($id) {
    	try {
			$objNativeProduct = new WC_Product($id);
    	} catch (Exception $e) {
    		return false;
    	}
		$objEcommerceProduct = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);
		return $objEcommerceProduct;
    }

    /*
	 * @Todo
	 * Filter:	Virtual/Downloadable Products
	 *			Not Stock Managed
	 */
    public static function getAll($arrCustomFilters = null) {
		global $wp_query;

        if(!is_array($arrCustomFilters)) {
            $arrCustomFilters = array();
        }

        $arrFilters = array_merge(
			array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'tax_query' => array(
						'relation' => 'OR',
						array(
								'taxonomy' => 'product_type',
								'field' => 'slug',
								'terms' => array('simple', 'variable'),
								'operator' => 'IN'
						),
						array(
								'taxonomy' => 'product_type',
								'operator' => 'NOT EXISTS',
						)
				)
			),
			$arrCustomFilters);
        $wp_query = new WP_Query($arrFilters);

        if(!$wp_query->have_posts()) {
            return false;
        }

		$arrEcommerceProducts = array();
		while($wp_query->have_posts()) {
			$wp_query->the_post();
			$objNativeProduct = new WC_Product(get_the_ID());

			$arrEcommerceProducts[] = self::transformNativeProductIntoEcommerceProduct($objNativeProduct);
		}

		return $arrEcommerceProducts;
    }

	private static function getItemSpecifics($objNativeProduct) {
		require_once(__DIR__. '/AffinityEcommerceItemSpecific.php');
		return AffinityEcommerceItemSpecific::getProductAttributes($objNativeProduct);
	}

	private static function getEcommerceCategories($objNativeProduct) {
		require_once(__DIR__. '/AffinityEcommerceCategory.php');
		return AffinityEcommerceCategory::getProductCategories($objNativeProduct);
	}

	private static function getEcommerceShipping($objNativeProduct) {
		require_once(__DIR__. '/../model/AffinityShippingRule.php');
		return AffinityShippingRule::generate($objNativeProduct);
	}

	private static function transformTemplate($obj) {
		$dir = wp_upload_dir();
		$penicilurl = str_replace('ecommerce-adapters/assets/search2.png', 'assets/search2.png', plugins_url('assets/search2.png', __FILE__));
		$ebayurl = get_option('ebayaffinity_ebaysite');
		$userid = get_option('ebayaffinity_ebayuserid');
		$searchurl = $ebayurl.'sch/'.rawurlencode($userid).'/m.html';
		$template = get_option('ebayaffinity_customtemplate');
		if (empty($template)) {
			$template = file_get_contents(__DIR__. '/../assets/product.html');
		}
		$logo = get_option('ebayaffinity_logo');
		$storelogo = '';

		if (!empty($logo)) {
			$surl = $ebayurl.'usr/'.rawurlencode($userid);
			$storelogo = '<a target="_top" href="'.esc_html($surl).'"><img src="'.esc_html($dir['baseurl'].'/'.$logo).'" alt=""></a>';
		}
		$template = str_replace('[[STORELOGO]]', $storelogo, $template);
		$template = str_replace('[[DESC]]', $obj->description, $template);

		$img = '';
		if (!empty($obj->objMainImage->fullUrl)) {
			$img = '<img src="'.esc_html($obj->objMainImage->fullUrl).'" alt="">';
		}
		$template = str_replace('[[IMG]]', $img, $template);
		$template = str_replace('[[TITLE]]', esc_html($obj->title), $template);
		$template = str_replace('[[PRICE]]', $obj->stylepriceIncludingTax, $template);
		$template = str_replace('[[BINCLICK]]', '', $template);
		$template = str_replace('[[SEARCHURL]]', $searchurl, $template);
		$template = str_replace('[[PENCILURL]]', $penicilurl, $template);

		// We should remove unwanted HTML tags. The only real way to do this is using DOMDocument, but it's not always available.
		// Some people try to do it with regular expressions, but such implementations tend to be somewhat broken.
		if (class_exists('DOMDocument')) {
			libxml_clear_errors();

			$template = htmlspecialchars_decode(htmlspecialchars($template, ENT_IGNORE, 'UTF-8'));
			$template = preg_replace('/[^\PC\s]/u', '', $template);

			$template2 = '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>'.$template.'</body></html>';
			$dom = new DOMDocument();
			if (@$dom->loadHTML($template2)) {
				$iframes = $dom->getElementsByTagName('iframe');
				foreach ($iframes as $iframe) {
					$iframe->parentNode->removeChild($iframe);
				}
				$embeds = $dom->getElementsByTagName('embed');
				foreach ($embeds as $embed) {
					$embed->parentNode->removeChild($embed);
				}
				$objects = $dom->getElementsByTagName('object');
				foreach ($objects as $object) {
					$object->parentNode->removeChild($object);
				}
				$applets = $dom->getElementsByTagName('applet');
				foreach ($applets as $applet) {
					$applet->parentNode->removeChild($applet);
				}
				$bases = $dom->getElementsByTagName('base');
				foreach ($bases as $base) {
					$base->parentNode->removeChild($base);
				}
				$meta = $dom->getElementsByTagName('meta');
				foreach ($bases as $base) {
					$meta->parentNode->removeChild($meta);
				}
				$template = $dom->saveHTML();

				$template = preg_replace('/.*<body>/s', '', $template);
				$template = preg_replace('/<\/body>.*/s', '', $template);
			}
		}

		return $template;
	}

	private static function smartTags($obj, $objn) {
		$template = $obj->listingDescription;

		$img = '';
		if (!empty($obj->objMainImage->fullUrl)) {
			$img = '<img src="'.esc_html($obj->objMainImage->fullUrl).'" alt="">';
		}

		$post = get_post($objn->get_id());

		$template = str_replace('[[TITLE]]', esc_html($obj->title), $template);
		$template = str_replace('[[PRICE]]', $obj->stylepriceIncludingTax, $template);
		$template = str_replace('[[DESC]]', apply_filters('the_content', $post->post_content), $template);
		$template = str_replace('[[IMG]]', $img, $template);

		// We should remove unwanted HTML tags. The only real way to do this is using DOMDocument, but it's not always available.
		// Some people try to do it with regular expressions, but such implementations tend to be somewhat broken.
		if (class_exists('DOMDocument')) {
			libxml_clear_errors();

			$template = htmlspecialchars_decode(htmlspecialchars($template, ENT_IGNORE, 'UTF-8'));
			$template = preg_replace('/[^\PC\s]/u', '', $template);

			$template2 = '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>'.$template.'</body></html>';
			$dom = new DOMDocument();
			if (@$dom->loadHTML($template2)) {
				$iframes = $dom->getElementsByTagName('iframe');
				foreach ($iframes as $iframe) {
					$iframe->parentNode->removeChild($iframe);
				}
				$embeds = $dom->getElementsByTagName('embed');
				foreach ($embeds as $embed) {
					$embed->parentNode->removeChild($embed);
				}
				$objects = $dom->getElementsByTagName('object');
				foreach ($objects as $object) {
					$object->parentNode->removeChild($object);
				}
				$applets = $dom->getElementsByTagName('applet');
				foreach ($applets as $applet) {
					$applet->parentNode->removeChild($applet);
				}
				$bases = $dom->getElementsByTagName('base');
				foreach ($bases as $base) {
					$base->parentNode->removeChild($base);
				}
				$meta = $dom->getElementsByTagName('meta');
				foreach ($bases as $base) {
					$meta->parentNode->removeChild($meta);
				}
				$template = $dom->saveHTML();

				$template = preg_replace('/.*<body>/s', '', $template);
				$template = preg_replace('/<\/body>.*/s', '', $template);
			}
		}

		return $template;
	}

	private static function forceResize($mainImageId, $arrImageInfo) {
		$noautoresize = get_option('ebayaffinity_noautoresize');

		if (!empty($noautoresize)) {
			return $arrImageInfo;
		}

		if ($arrImageInfo[1] < 500 || $arrImageInfo[2] < 500) {
			if (function_exists('imagecreatefromstring')) {
				$filePath = get_attached_file($mainImageId);
				$finfo = getimagesize($filePath);

				$fouth = dirname($filePath).'/ebayaffinity_'.basename($filePath);
				$fcouth = dirname($arrImageInfo[0]).'/ebayaffinity_'.basename($arrImageInfo[0]);

				$neww = 500;
				$newh = 500;
				if ($arrImageInfo[1] > $neww) {
					$neww = $arrImageInfo[1];
				}
				if ($arrImageInfo[2] > $newh) {
					$newh = $arrImageInfo[2];
				}

				if (file_exists($fouth)) {
					if (filemtime($fouth) > filemtime($filePath)) {
						$arrImageInfo[0] = $fcouth;
						$arrImageInfo[1] = $neww;
						$arrImageInfo[2] = $newh;
						return $arrImageInfo;
					}
				}

				$im = imagecreatefromstring(file_get_contents($filePath));
				if (function_exists('imagepalettetotruecolor')) {
					imagepalettetotruecolor($im);
				}

				$width = imagesx($im);
				$height = imagesy($im);
				$out = imagecreatetruecolor($neww, $newh);

				$dx = intval(($neww / 2) - ($width / 2));
				$dy = intval(($newh / 2) - ($height / 2));

				imagealphablending($im, true);
				imagealphablending($out, true);
				$trans_colour = imagecolorallocate($out, 255, 255, 255);
				imagefill($out, 0, 0, $trans_colour);
				imagecopy($out, $im, $dx, $dy, 0, 0, $width, $height);

				if ($finfo[2] == IMAGETYPE_PNG) {
					imagepng($out, $fouth, 9);
					$arrImageInfo[0] = $fcouth;
					$arrImageInfo[1] = $neww;
					$arrImageInfo[2] = $newh;
				} else if ($finfo[2] == IMAGETYPE_GIF) {
					imagetruecolortopalette($out, true, 255);
					imagegif($out, $fouth);
					$arrImageInfo[0] = $fcouth;
					$arrImageInfo[1] = $neww;
					$arrImageInfo[2] = $newh;
				} else if ($finfo[2] == IMAGETYPE_JPEG) {
					imageinterlace($out, 1);
					imagejpeg($out, $fouth, 90);
					$arrImageInfo[0] = $fcouth;
					$arrImageInfo[1] = $neww;
					$arrImageInfo[2] = $newh;
				}

				imagedestroy($out);
				imagedestroy($im);
			}
		}
		return $arrImageInfo;
	}

	private static function transformNativeProductIntoEcommerceProduct($objNativeProduct) {
		require_once(__DIR__ . "/AffinityEcommerceItemSpecific.php");
		require_once(__DIR__. '/../model/AffinityTitleRule.php');

		$objEcommerceProduct = new AffinityEcommerceProduct();
		$objEcommerceProduct->id = $objNativeProduct->get_id();
		$post = get_post($objNativeProduct->get_id());

		$condition = get_post_meta($objNativeProduct->get_id(), '_ebaycondition', true);
		if (empty($condition)) {
			$condition = 'NEW';
		}
		$objEcommerceProduct->condition = $condition;

		$title = AffinityTitleRule::generate($objNativeProduct);
		$objEcommerceProduct->title = $title[1];

		$objEcommerceProduct->shortDescription = $post->post_excerpt;

		$ebaydesc = get_post_meta($objNativeProduct->get_id(), '_ebaydesc', true);
		$useshort = get_option('ebayaffinity_useshort');
		$desc = $post->post_content;
		if (empty($useshort)) {
			$useshort = get_post_meta($objNativeProduct->get_id(), '_ebayuseshort', true);
		}
		if (!empty($ebaydesc)) {
			$objEcommerceProduct->description = apply_filters('the_content', $ebaydesc);
		} else if (!empty($useshort)) {
			$objEcommerceProduct->description = apply_filters('the_content', $post->post_excerpt);
		} else if(!empty($desc)){ //If the description exists
			$objEcommerceProduct->description = apply_filters('the_content', $post->post_content);
		} else { //if no long description, or ebay description, use short description
			$objEcommerceProduct->description = apply_filters('the_content', $post->post_excerpt);
		}

		if (function_exists('wc_get_price_including_tax')) {
			$rrp_price = wc_get_price_including_tax($objNativeProduct, array('qty' => 1, 'price' => $objNativeProduct->get_regular_price()));
		} else {
			$rrp_price = $objNativeProduct->get_price_including_tax(1, $objNativeProduct->get_regular_price());
		}
		if (function_exists('wc_get_price_including_tax')) {
			$price = wc_get_price_including_tax($objNativeProduct);
		} else {
			$price = $objNativeProduct->get_price_including_tax();
		}

		if (empty($price)) {
			$price = $rrp_price;
		}
		$adjust = get_option('ebayaffinity_priceadjust');
		if (strpos($adjust, 'num') !== false) {
			$adjust = str_replace('num', '', $adjust);
			$adjust = floatval($adjust);
			$price += $adjust;
		} else {
			if (!empty($adjust)) {
				$price += $price * ($adjust / 100);
			}
		}

		$ebayprice = get_post_meta($objNativeProduct->get_id(), '_ebayprice', true);
		if (!empty($ebayprice)) {
			$price = $ebayprice;
		}

		$objEcommerceProduct->weight = $objNativeProduct->get_weight();
		$objEcommerceProduct->length = $objNativeProduct->get_length();
		$objEcommerceProduct->width = $objNativeProduct->get_width();
		$objEcommerceProduct->height = $objNativeProduct->get_height();

		if (empty($objEcommerceProduct->weight)) {
			$objEcommerceProduct->weight = 0;
		}

		if (empty($objEcommerceProduct->length)) {
			$objEcommerceProduct->length = 0;
		}

		if (empty($objEcommerceProduct->width)) {
			$objEcommerceProduct->width = 0;
		}

		if (empty($objEcommerceProduct->height)) {
			$objEcommerceProduct->height = 0;
		}

		$wc = wc_get_product($objNativeProduct->get_id());
		if ($wc === false) {
			return false;
		}

		if ($wc->is_type('variable')) {
			$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => $wc->get_id(), 'posts_per_page' => 100));
			$price_arr = array();

			while ($variationloop->have_posts()) {
				$variationloop->the_post();
				if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
					$variation = new WC_Product(get_the_ID());
				} else {
					$variation = new WC_Product_Variation(get_the_ID());
				}
				if (function_exists('wc_get_price_including_tax')) {
					$rprice = wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_regular_price()));
				} else {
					$rprice = $variation->get_price_including_tax(1, $variation->get_regular_price());
				}

				if (function_exists('wc_get_price_including_tax')) {
					$sprice = wc_get_price_including_tax($variation);
				} else {
					$sprice = $variation->get_price_including_tax();
				}
				if (empty($sprice)) {
					$sprice = $rprice;
				}

				$adjust = get_option('ebayaffinity_priceadjust');
				if (strpos($adjust, 'num') !== false) {
					$adjust = str_replace('num', '', $adjust);
					$adjust = floatval($adjust);
					$sprice += $adjust;
				} else {
					if (!empty($adjust)) {
						$sprice += $sprice * ($adjust / 100);
					}
				}

				$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
				if (!empty($ebayprice)) {
					$sprice = $ebayprice;
				}

				$price_arr[] = $sprice;
			}

			if (!empty($price_arr)) {
				$price_arr = array_unique($price_arr);
				if (count($price_arr) > 1) {
					sort($price_arr);
					$min = str_replace('.00', '', wc_price($price_arr[0]));
					$max = str_replace('.00', '', wc_price($price_arr[count($price_arr) - 1]));
					$stylePrice = $min . ' to ' . $max;
				} else {
					$stylePrice = wc_price($price_arr[0]);
				}
			}
		}

		$objEcommerceProduct->retailPriceIncludingTax = number_format(floatval($rrp_price), 2, '.', '');
		$objEcommerceProduct->priceIncludingTax = number_format(floatval($price), 2, '.', '');

		if (empty($stylePrice)) {
			$objEcommerceProduct->stylepriceIncludingTax = wc_price($price);
		} else {
			$objEcommerceProduct->stylepriceIncludingTax = $stylePrice;
		}

		$objEcommerceProduct->isStockBeingManaged = $objNativeProduct->managing_stock();
		$objEcommerceProduct->isInStock = $objNativeProduct->is_in_stock();
		$objEcommerceProduct->qtyAvailable = $objNativeProduct->get_stock_quantity();
		$objEcommerceProduct->status = $post->post_status;

		$mainImageId = $objNativeProduct->get_image_id();
		if($mainImageId) {
			$arrImageInfo = wp_get_attachment_image_src($mainImageId, 'full');

			if(!empty($arrImageInfo[0]) && !empty($arrImageInfo[1]) && !empty($arrImageInfo[2])) {
				$arrImageInfo = self::forceResize($mainImageId, $arrImageInfo);

				$comps = explode('?', $arrImageInfo[0]);
				$path = explode('/', $comps[0]);
				foreach ($path as $k=>$v) {
					if ($k > 2) {
						$path[$k] = rawurlencode(rawurldecode($v));
					}
				}
				$arrImageInfo[0] = implode('/', $path);
				if (!empty($comps[1])) {
					$arrImageInfo[0] .= '?' . $comps[1];
				}

				require_once(__DIR__. '/AffinityEcommerceImage.php');
				$objMainImage = new AffinityEcommerceImage();
				$objMainImage->imageId = $mainImageId;
				$objMainImage->fullUrl = $arrImageInfo[0];
				$objMainImage->width = $arrImageInfo[1];
				$objMainImage->height = $arrImageInfo[2];
				$objMainImage->imageSize = filesize( get_attached_file( $mainImageId ) );
				$objEcommerceProduct->objMainImage = $objMainImage;
			}
		}

		if (method_exists($objNativeProduct, 'get_gallery_image_ids')) {
			$attachmentIds = $objNativeProduct->get_gallery_image_ids();
		} else {
			$attachmentIds = $objNativeProduct->get_gallery_attachment_ids();
		}
		$objEcommerceProduct->arrObjAdditionalImages = array();
		foreach($attachmentIds as $attachmentId) {
			$arrImageInfo = wp_get_attachment_image_src($attachmentId, 'full');

			if(!empty($arrImageInfo[0]) && !empty($arrImageInfo[1]) && !empty($arrImageInfo[2])) {
				$arrImageInfo = self::forceResize($attachmentId, $arrImageInfo);

				$comps = explode('?', $arrImageInfo[0]);
				$path = explode('/', $comps[0]);
				foreach ($path as $k=>$v) {
					if ($k > 2) {
						$path[$k] = rawurlencode(rawurldecode($v));
					}
				}
				$arrImageInfo[0] = implode('/', $path);
				if (!empty($comps[1])) {
					$arrImageInfo[0] .= '?' . $comps[1];
				}

				require_once(__DIR__. '/AffinityEcommerceImage.php');
				$objImage = new AffinityEcommerceImage();
				$objImage->imageId = $mainImageId;
				$objImage->fullUrl = $arrImageInfo[0];
				$objImage->width = $arrImageInfo[1];
				$objImage->height = $arrImageInfo[2];
				$objImage->imageSize = filesize( get_attached_file( $attachmentId ) );
				$objEcommerceProduct->arrObjAdditionalImages[] = $objImage;
			}
		}

		$objEcommerceProduct->arrEcommerceItemSpecifics = self::getItemSpecifics($objNativeProduct);
		$objEcommerceProduct->arrEcommerceCategories = self::getEcommerceCategories($objNativeProduct);
		$objEcommerceProduct->arrEcommerceShipping = self::getEcommerceShipping($objNativeProduct);

		if ($wc->is_type('variable')) {
			$objEcommerceProduct->arrEcommerceProductVariations = $objEcommerceProduct->getVariations();
		} else {
			$objEcommerceProduct->arrEcommerceProductVariations = array();
		}

		$ebaytemplate = get_post_meta($objNativeProduct->get_id(), '_ebaytemplate', true);

		if (strlen($ebaytemplate) == 0 || $ebaytemplate == '1') {
			$objEcommerceProduct->listingDescription = self::transformTemplate($objEcommerceProduct);
		} else {
			$objEcommerceProduct->listingDescription = $objEcommerceProduct->description;
		}

		$objEcommerceProduct->listingDescription = self::smartTags($objEcommerceProduct, $objNativeProduct);

		return $objEcommerceProduct;
	}

	public static function productHasChanged($objWpPost, $toebay=false) {
		require_once(__DIR__ . "/AffinityEcommerceUtils.php");
		require_once(__DIR__. '/../model/AffinityGlobalOptions.php');
		require_once(__DIR__. '/../model/AffinityProduct.php');

		$objEcommerceProduct = self::get($objWpPost->ID);
		$affinityProduct = AffinityProduct::transformFromEcommerceProduct($objEcommerceProduct);

		$arrClientErrors = json_decode($affinityProduct->jsonEncodedArrClientErrors);
		if($objWpPost->post_status === "publish" && (!$affinityProduct->shouldNotBeSentToEbay) && empty($arrClientErrors)) {
			AffinityProduct::productWasPublished($objEcommerceProduct, $toebay);
		}
		else {
			AffinityProduct::productWasUnpublished($objEcommerceProduct, $toebay);
		}
	}


}
