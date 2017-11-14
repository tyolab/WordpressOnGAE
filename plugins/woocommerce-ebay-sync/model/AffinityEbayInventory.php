<?php 

function affinityfunction_orderbyreplace($orderby) {
	global $wpdb;
	
	$ssql = "(SELECT COUNT(tm.meta_value) FROM ".$wpdb->term_relationships." AS wtr,
		".$wpdb->term_taxonomy." AS wtt,
		".$wpdb->termmeta." AS tm
		WHERE wtr.object_id = ".$wpdb->posts.".ID
		AND wtr.term_taxonomy_id = wtt.term_taxonomy_id
		AND wtt.taxonomy = 'product_cat'
		AND wtt.term_id = tm.term_id
		AND tm.meta_key = '_affinity_ebaycategory'
		LIMIT 1)";
	
	if ($_GET['unmapped'] === 'mapped') {
		return 'IF(mt1.meta_value IS NULL, 0, 1) + '.$ssql.' DESC, '. $orderby;
	} else {
		return 'IF(mt1.meta_value IS NULL, 0, 1) + '.$ssql.' ASC, '. $orderby;
	}
}

function AffinityEbayInventoryTitle($where, &$wp_query) {
	global $wpdb;
	if ($search_term = $wp_query->get('s_title')) {
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql($wpdb->esc_like($search_term)) . '%\'';
	}
	return $where;
}

class AffinityEbayInventory {
    static function getCategoryLength($id) {
        global $wpdb;
        
        $sql = "SELECT MAX(LENGTH(post_title)) AS clength FROM ".$wpdb->term_relationships." AS wtr,
                ".$wpdb->term_taxonomy." AS wtt,
                ".$wpdb->posts." AS p
                WHERE wtr.object_id = p.ID
                AND wtr.term_taxonomy_id = wtt.term_taxonomy_id
                AND wtt.taxonomy = 'product_cat' AND p.post_type = 'product' AND p.post_status = 'publish'
                AND wtt.term_id = ".intval($id);
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
	static function getAllMappedCats() {
		global $wpdb;
		
		$sql = 'SELECT DISTINCT j.meta_value FROM (
				SELECT DISTINCT meta_value FROM '.$wpdb->postmeta.' WHERE meta_key = \'_affinity_ebaycategory\'
				UNION
				SELECT DISTINCT meta_value FROM '.$wpdb->termmeta.' WHERE meta_key = \'_affinity_ebaycategory\')
				AS j';
		return $wpdb->get_results($sql, ARRAY_A);
	}
	
	static function getLong($id, $ruleVals, $default) {
		global $wpdb;
		
		$scount = 0;
		$add = '';
		$taxsql = '';
		
		foreach ($ruleVals as $k=>$v) {
			if ($v['type'] === 'attr' && $v['value'] === 'fake-fake-condition') {
				$scount += 3;
			} else if ($v['type'] === 'string') {
				$scount += strlen($v['value']);
			} else if ($v['type'] === 'attr' && strpos($v['value'], 'pa_') === 0) {
				$taxsql .= ' LEFT JOIN '.$wpdb->term_relationships.' AS wtr'.$k.' ON (wtr'.$k.'.object_id = p.id) 
						LEFT JOIN '.$wpdb->term_taxonomy.' AS wtt'.$k.' ON (wtr'.$k.'.term_taxonomy_id = wtt'.$k.'.term_taxonomy_id AND wtt'.$k.'.taxonomy = \''.esc_sql($v['value']).'\')
						LEFT JOIN '.$wpdb->terms.' as wt'.$k.' ON (wtt'.$k.'.term_id = wt'.$k.'.term_id)
					';
				$add .= '+LENGTH(GROUP_CONCAT(DISTINCT IF(wt'.$k.'.name IS NULL, \'\', wt'.$k.'.name) SEPARATOR \', \')) ';
				$scount++;
			} else if ($v['type'] === 'attr' && $v['value'] === 'title') {
				$add .= '+LENGTH(p.post_title) ';
				$scount++;
			} else {
				$b = esc_sql('"'.addslashes($v['value']).'";s:5:"value";s:');
				$add .= '+ CONVERT(SUBSTRING(pm.meta_value, LENGTH(\''.$b.'\') + 
						LOCATE(\''.$b.'\', pm.meta_value)), UNSIGNED INTEGER) ';
				$scount++;
			}
		}
		$scount--;
		
		$sql = 'SELECT p.id, '.intval($scount).$add.' AS length FROM 
			(SELECT DISTINCT p.id FROM 
			'.$wpdb->posts.' AS p LEFT JOIN
			'.$wpdb->postmeta.' AS pm ON (p.id = pm.post_id AND pm.meta_key = \'_affinity_titlerule\' AND pm.meta_value = \''.intval($id).'\') LEFT JOIN
			'.$wpdb->term_relationships.' AS tr ON (p.id = tr.object_id) LEFT JOIN
			'.$wpdb->term_taxonomy.' AS tt ON (tt.term_taxonomy_id = tr.term_taxonomy_id) LEFT JOIN
			'.$wpdb->termmeta.' AS tm ON (tt.term_id = tm.term_id AND tm.meta_key = \'_affinity_titlerule\')
			 WHERE 
			p.post_type = \'product\' AND p.post_status = \'publish\' AND (pm.meta_value = \''.intval($id).'\' OR 
			(pm.meta_value IS NULL AND tm.meta_value = \''.intval($id).'\') ';
		
		if ($default) {
			$sql .= ' OR (pm.meta_value IS NULL AND tm.meta_value IS NULL) ';
		}
		
		$sql .= ')) AS j,
			'.$wpdb->posts.' AS p
			'.$taxsql.',
			'.$wpdb->postmeta.' AS pm 
			WHERE
			j.id = p.id AND p.id = pm.post_id AND pm.meta_key = \'_product_attributes\' GROUP BY p.id HAVING length > 80 LIMIT 20;';
		
		$posts = array();
		$res = $wpdb->get_results($sql, ARRAY_A);
		foreach ($res as $r) {
			$posts[] = $r['id'];
		}
		return $posts;
		
	}
	
	static function attributeCounts($attributes) {
		global $wpdb;
		$atts = array();
		foreach ($attributes as $k=>$v) {
		
			$a = '"'.addslashes($v).'"';
			$b = '"'.addslashes($v).'";s:5:"value";s:';
			$c = '%'.addslashes($v).'%';
			
			if (strpos($k, 'pa_') === 0) {
				$data = $wpdb->get_row($wpdb->prepare('SELECT MAX(j.str) AS val, COUNT(*) AS c FROM (SELECT LENGTH(GROUP_CONCAT(wt.name SEPARATOR \', \')) AS str 
						FROM '.$wpdb->term_taxonomy.' AS wtt, '.$wpdb->terms.' as wt, '.$wpdb->term_relationships.' AS wtr,
						'.$wpdb->posts.' AS p
						WHERE wtt.taxonomy = %s
						AND wtt.term_id = wt.term_id AND wtr.term_taxonomy_id = wtt.term_taxonomy_id
						AND wtr.object_id = p.id
						AND
						p.post_type = \'product\' AND p.post_status = \'publish\' GROUP BY p.id) AS j', $k), ARRAY_A);
			} else {
				$data = $wpdb->get_row($wpdb->prepare('SELECT MAX(CONVERT(SUBSTRING(meta_value, LENGTH(%s) + 
						LOCATE(%s, meta_value)), UNSIGNED INTEGER)) AS val, COUNT(*) AS c FROM '.$wpdb->posts.' AS p, 
						'.$wpdb->postmeta.' AS pm WHERE p.id = pm.post_id AND 
						p.post_type = \'product\' AND p.post_status = \'publish\' AND
						pm.meta_key = \'_product_attributes\' AND pm.meta_value LIKE %s', $b, $b, $c), ARRAY_A);
			}
			$atts[$k] = array(intval($data['val']), intval($data['c']));
			
		}
		return $atts;
		
	}
	
	static function getAllAttributes() {
		$taxonomies = wc_get_attribute_taxonomies();
		$taxonomies_arr = array();
		foreach ($taxonomies as $taxonomy) {
			$taxonomies_arr[$taxonomy->attribute_name] = $taxonomy->attribute_label;
				
		}
		
		return $taxonomies_arr;
	}
	
	static function publishUpdates() {
		ini_set('memory_limit','2048M');
		set_time_limit(3600);
		global $wp_query;
		global $post;
		
		$args = array(
				'post_type' => 'product',
				'paged' => 1,
				'posts_per_page' => 5,
				'post_status' => array('publish', 'trash','draft','pending'),
				'meta_query' => array(
						array('relation' => 'OR',
						array(
								'key' => '_affinity_prod_update_status',
								'compare' => 'IN',
								'value' => array('1', '2')
						),
						array(
								'key' => '_affinity_prod_update_status',
								'compare' => 'NOT EXISTS'
						))
				),
				'tax_query' => array(
						array('relation' => 'OR',
						array(
								'taxonomy' => 'product_type',
								'field' => 'slug',
								'terms' => array('simple', 'variable'),
								'operator' => 'IN'
						),
						array(
								'taxonomy' => 'product_type',
								'operator' => 'NOT EXISTS',
						))
				)
		);
		
		$wp_query = new WP_Query($args);
		$found = $wp_query->found_posts;
		$posts = array();
		
		while ($wp_query->have_posts()) {
			$wp_query->the_post();
			$posts[get_the_ID()] = $post;
		}
		
		foreach ($posts as $poste) {
			AffinityEcommerceProduct::productHasChanged($poste, true);
		}
		
		foreach ($posts as $k=>$poste) {
			$a = get_post_meta($k, '_affinity_prod_update_status', true);
			if ($a == 1 || $a == 2) {
				update_post_meta($k, '_affinity_prod_update_status', 3);
			}
		}
		
		if ($found > 5) {
			sleep(5);
			wp_clear_scheduled_hook('wp_affinity_cron_inv');
			wp_schedule_event(time(), get_option('ebayaffinity_pushinvenorytime'), 'wp_affinity_cron_inv');
			spawn_cron();
		}
	}
	
	static function syncAll($id='') {
		if (empty($id)) {
			delete_post_meta_by_key('_affinity_prod_update_status');
		} else {
			delete_post_meta($id, '_affinity_prod_update_status');
		}
	}
	
	static function getDashboardOrdersPerHour() {
		global $wpdb;
		$orig_tz = date_default_timezone_get();
		if (empty($orig_tz)) {
			$orig_tz = 'UTC';
		}
		$ntz = wc_timezone_string();
		if (!empty($ntz)) {
			date_default_timezone_set($ntz);
		}
		$data = $wpdb->get_results("SELECT DATE_FORMAT(pt.post_date, '%k') AS date, COUNT(pt.id) AS counte
				FROM ".$wpdb->posts." AS pt, ".$wpdb->postmeta." AS pm
				WHERE pt.post_type = 'shop_order' AND pt.id = pm.post_id
				AND pm.meta_key = '_affinity_ebayorder' 
				AND pt.post_date >= '".date('Y')."-".date('m')."-".date('d')." 00:00:00'
				AND pt.post_date <= '".date('Y')."-".date('m')."-".date('d')." 23:59:59'
				GROUP BY date
				ORDER BY date");
		$out = array();
		for ($i = 0; $i <= 23; $i++) {
			$d = $i;
			if ($i > 12) {
				$d = $d - 12;
			}
			if ($i == 0) {
				$d = '12AM';
			}
			if ($i == 12) {
				$d .= 'PM';
			}
			$out[$i] = array(
					'date' => $i,
					'day' => $d,
					'value' => 0
			);
			
			foreach ($data as $datum) {
				if ($datum->date == $i) {
					$out[$i]['value'] = $datum->counte;
					break;
				}
			}
		}
		
		if (!empty($ntz)) {
			date_default_timezone_set($orig_tz);
		}
		return array_values($out);
	}
	
	static function getDashboardOrdersPerMonth() {
		global $wpdb;
		$orig_tz = date_default_timezone_get();
		if (empty($orig_tz)) {
			$orig_tz = 'UTC';
		}
		$ntz = wc_timezone_string();
		if (!empty($ntz)) {
			date_default_timezone_set($ntz);
		}
		
		$sdate = mktime(0, 0, 0, date('n') - 11, 1, date('Y'));
		
		$data = $wpdb->get_results("SELECT DATE_FORMAT(pt.post_date, '%m/%y') AS date, COUNT(pt.id) AS counte
				FROM ".$wpdb->posts." AS pt, ".$wpdb->postmeta." AS pm 
				WHERE pt.post_type = 'shop_order' AND pt.id = pm.post_id
				AND pm.meta_key = '_affinity_ebayorder' AND pt.post_date >= '".date('Y', $sdate)."-".date('m', $sdate)."-01' 
				GROUP BY date
				ORDER BY date");
		$out = array();
		for ($i = -11; $i <= 0; $i++) {
			$curdate = date('m/y', mktime(0, 0, 0, date('n') + $i, 1, date('Y')));
			$out[$curdate] = array(
					'date' => $curdate,
					'day' => strtoupper(substr(date('M', mktime(0, 0, 0, date('n') + $i, 1, date('Y'))), 0, 3)),
					'value' => 0
			);
			foreach ($data as $datum) {
				if ($datum->date == $curdate) {
					$out[$curdate]['value'] = $datum->counte;
					break;
				}
			}
		}
		
		if (!empty($ntz)) {
			date_default_timezone_set($orig_tz);
		}
		return array_values($out);
	}
	
	static function getDashboardOrdersPerDay() {
		global $wpdb;
		$orig_tz = date_default_timezone_get();
		if (empty($orig_tz)) {
			$orig_tz = 'UTC';
		}
		$ntz = wc_timezone_string();
		if (!empty($ntz)) {
			date_default_timezone_set($ntz);
		}
		
		$sdate = mktime(0, 0, 0, date('n'), -31, date('Y'));
		
		$data = $wpdb->get_results("SELECT DATE_FORMAT(pt.post_date, '%d/%m/%y') AS date, COUNT(pt.id) AS counte 
				FROM ".$wpdb->posts." AS pt, ".$wpdb->postmeta." AS pm 
				WHERE pt.post_type = 'shop_order' AND pt.id = pm.post_id 
				AND pm.meta_key = '_affinity_ebayorder' AND pt.post_date >= '".date('Y', $sdate)."-".date('m', $sdate)."-".date('d', $sdate)."' 
				GROUP BY date
				ORDER BY date");
		$out = array();
		for ($i = -38; $i <= date('j') + 7; $i++) {
			$curdate = date('d/m/y', mktime(0, 0, 0, date('n'), $i, date('Y')));
			$curdateo = date('M d', mktime(0, 0, 0, date('n'), $i, date('Y')));
			$curdatep = date('l jS F', mktime(0, 0, 0, date('n'), $i, date('Y')));
			$out[$curdate] = array(
					'date' => $curdate,
					'day' => strtoupper(substr(date('l', mktime(0, 0, 0, date('n'), $i, date('Y'))), 0, 3)),
					'dayo' => strtoupper($curdateo),
					'dayp' => $curdatep,
					'value' => 0
			);
			foreach ($data as $datum) {
				if ($datum->date == $curdate) {
					$out[$curdate]['value'] = $datum->counte;
					break;
				}
			}
		}
		
		if (!empty($ntz)) {
			date_default_timezone_set($orig_tz);
		}
		return array_values($out);
	}
	
	static function getDashboardCountRevenue() {
		global $wpdb;
		return $wpdb->get_row("SELECT COUNT(pm1.post_id) AS counte, SUM(pm2.meta_value) AS revenue FROM ".$wpdb->postmeta." AS pm1, ".$wpdb->postmeta." AS pm2
				WHERE pm1.post_id = pm2.post_id AND pm1.meta_key = '_affinity_ebayorder' AND pm2.meta_key = '_order_total'");
	}
	
	static function allProducts($nr=1) {
		return self::getBySearchCategory('', 0, 1, '', 0, '', 0, $nr, false);
	}
	
	static function noEbayCategoryProducts($nr=1) {
		$catrules_cats = AffinityEbayCategory::getCategoriesCatRules();
		$catsRules = array();
		foreach ($catrules_cats as $k=>$catrules_cat) {
			if (!empty($catrules_cat[1])) {
				$catsRules[$k] = $k;
			}
		}
		return self::getBySearchCategory('', 0, 1, '', 0, '_affinity_ebaycategory', null, $nr, false, $catsRules);
	}
	
	static function errorProducts($nr=1) {
		return self::getBySearchCategory('', 0, 1, 'allerrors', null, '', 0, $nr, false);
	}
	
	static function blockedProducts($nr=1) {
		return self::getBySearchCategory('', 0, 1, '_affinity_block', null, '', 0, $nr, false);
	}
	
	static function listedProducts($nr=1) {
		return self::getBySearchCategory('', 0, 1, '_affinity_ebayitemid', null, '', 0, $nr, false);
	}
	
	static function notListedProducts($nr=1) {
		return self::getBySearchCategory('', 0, 1, '', 0, '_affinity_ebayitemid', null, $nr, false);
	}
	
	static function notOptimisedProducts($nr=1) {
		$titlerules_cats = AffinityEbayCategory::getCategoriesTitleRules();
		$title_rules = AffinityTitleRule::getAllRules();
		$catsRules = array();
		$titleRules = array();
		foreach ($titlerules_cats as $k=>$v) {
			foreach ($title_rules as $vv) {
				if (!empty($vv->is_default)) {
					return 0;
				}
				$titleRules[$vv->id] = $vv->id;
				if ($v[1] == $vv->id) {
					$catsRules[$k] = $k;
					break;
				}
			}
		}
		
		return self::getBySearchCategory('', 0, 1, '', 0, '_affinity_titlerule', $titleRules, $nr, false, $catsRules);
		
	}
	
	static function getBySearchCategory($s='', $categoryId=0, $paged=1, $key='', $value=0, $exkey='', $exvalue=0, $posts_per_page=20, $with_cats=true, $ncategoryId=0, $posts=array(), $notposts=array()) {
		global $wpdb;
		
		$min = floor($wpdb->get_var('
	                SELECT min(meta_value + 0)
	                FROM '.$wpdb->posts.'
	                LEFT JOIN '.$wpdb->postmeta.' ON '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
	                WHERE meta_key IN (\'' . implode('\',\'', apply_filters('woocommerce_price_filter_meta_keys', array('_price', '_ebayprice'))) . '\')
	                AND meta_value != \'\''
				));
		
		$max = ceil($wpdb->get_var('
	                SELECT max(meta_value + 0)
	                FROM '.$wpdb->posts.'
	                LEFT JOIN '.$wpdb->postmeta.' ON '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
	                WHERE meta_key IN (\'' . implode('\',\'', apply_filters('woocommerce_price_filter_meta_keys', array('_price', '_ebayprice'))) . '\')
					AND meta_value != \'\''
				));
		
		$args = array(
				'post_type' => 'product',
				'paged' => $paged,
				'post_status' => 'publish',
				'posts_per_page' => $posts_per_page,
				'meta_query' => array(),
				'tax_query' => array(
						array('relation' => 'OR',
						array(
								'taxonomy' => 'product_type',
								'field' => 'slug',
								'terms' => array('simple', 'variable'),
								'operator' => 'IN'
						),
						array(
								'taxonomy' => 'product_type',
								'operator' => 'NOT EXISTS',
						))
				),
				'orderby' => 'title',
				'order' => 'ASC'
		);
		
		if (!empty($_POST['unmapped'])) {
			$args['meta_query'][] = array('relation' => 'OR',
					array(
							'key' => '_affinity_ebaycategory',
							'compare' => 'EXISTS'
					),
					array(
							'key' => '_affinity_ebaycategory',
							'compare' => 'NOT EXISTS'
					)
			);
		}
		
		if (!empty($posts)) {
			$args['post__in'] = array_values($posts);
		}
		
		if (!empty($notposts)) {
			$args['post__not_in'] = array_values($notposts);
		}
		
		if ((!empty($key))) {
			if ($key === 'allerrors') {
				$args['meta_query'][] = array('relation' => 'OR',
						array(
								'key' => '_affinity_prod_arr_adaptation_errors',
								'compare' => '!=',
								'value' => '[]',
								'type' => 'string'
						),
						array(
								'key' => '_affinity_prod_arr_client_errors',
								'compare' => '!=',
								'value' => '[]',
								'type' => 'string'
						)
				);
			} else if (is_array($value)) {
				$args['meta_query'][] = array(
						'key' => $key,
						'compare' => 'IN',
						'value' => $value,
						'type' => 'numeric'
				);
			} else if (!is_null($value)) {
				$args['meta_query'][] = array(
						'key' => $key,
						'compare' => '=',
						'value' => $value,
						'type' => 'numeric'
				);
			} else {
				$args['meta_query'][] = array(
						'key' => $key,
						'compare' => 'EXISTS'
				);
			}
		}

		if ((!empty($exkey))) {
			if ($exkey !== 'prodtwosets') {
				if (is_array($exvalue)) {
					$args['meta_query'][] = array('relation' => 'OR', 
							array(
									'key' => $exkey,
									'compare' => 'NOT EXISTS'
							), 
							array(
									'key' => $exkey,
									'compare' => 'NOT IN',
									'value' => $exvalue,
									'type' => 'numeric'
							)
					);
				} else if (!is_null($exvalue)) {
					$args['meta_query'][] = array('relation' => 'OR', 
							array(
									'key' => $exkey,
									'compare' => 'NOT EXISTS'
							), 
							array(
									'key' => $exkey,
									'compare' => '!=',
									'value' => $exvalue,
									'type' => 'numeric'
							)
					);
				} else {
					$args['meta_query'][] = array(
							'key' => $exkey,
							'compare' => 'NOT EXISTS'
					);
				}
			} else {
				if ($exvalue == 1) {
					$args['meta_query'][] = array('relation' => 'AND',
							array(
									'key' => '_affinity_suggestedCatId',
									'compare' => '>',
									'value' => '0',
									'type' => 'numeric'
							),
							array(
									'key' => '_affinity_ebaycategory',
									'compare' => 'NOT EXISTS'
							)
					);
				} else if ($exvalue == 2) {
					$args['meta_query'][] = array('relation' => 'AND',
							array(
									array('relation' => 'OR',
											array(
													'key' => '_affinity_suggestedCatId',
													'compare' => 'NOT EXISTS'
											),
											array(
													'key' => '_affinity_suggestedCatId',
													'compare' => '=',
													'value' => '0',
													'type' => 'numeric'
											),
									)
							),
							array(
									'key' => '_affinity_ebaycategory',
									'compare' => 'NOT EXISTS'
							)
					);
				} else if ($exvalue == 3) {
					$args['meta_query'][] = array('relation' => 'AND',
							array(
									'key' => '_affinity_suggestedCatId',
									'compare' => 'NOT EXISTS'
							),
							array(
									'key' => '_affinity_ebaycategory',
									'compare' => 'NOT EXISTS'
							)
					);
				}
			}
		}

		if (!affinity_empty($s)) {
			$args['s'] = $s;
		}
		
		if (!empty($categoryId)) {
			$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => $categoryId,
					'operator' => 'IN'
			);
		}
		
		if (!empty($ncategoryId)) {
			$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'id',
					'terms' => $ncategoryId,
					'operator' => 'NOT IN'
			);
		}
		
		if (isset($_POST['s'])) {
			$_GET['s'] = $_POST['s'];
		}
		
		if (isset($_POST['unmapped'])) {
			$_GET['unmapped'] = $_POST['unmapped'];
		}
		
		if (isset($_POST['pricemin'])) {
			$_GET['pricemin'] = $_POST['pricemin'];
		}
		if (isset($_POST['pricemax'])) {
			$_GET['pricemax'] = $_POST['pricemax'];
		}
		if (isset($_POST['showunblocked'])) {
			$_GET['showunblocked'] = $_POST['showunblocked'];
		}
		if (isset($_POST['showblocked'])) {
			$_GET['showblocked'] = $_POST['showblocked'];
		}

		if (isset($_POST['showneedsmapping'])) {
			$_GET['showneedsmapping'] = $_POST['showneedsmapping'];
		}
		if (isset($_POST['shownotitleopt'])) {
			$_GET['shownotitleopt'] = $_POST['shownotitleopt'];
		}
		if (isset($_POST['showerrors'])) {
			$_GET['showerrors'] = $_POST['showerrors'];
		}
		if (isset($_POST['catslugs'])) {
			$_GET['catslugs'] = $_POST['catslugs'];
		}
		if (isset($_POST['sorder'])) {
			$_GET['order'] = $_POST['sorder'];
		}
		
		$args = self::popargs($args, $min, $max);

		global $wp_query;
		global $wpdb;
		if (!empty($_GET['unmapped'])) {
			$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'id',
					'terms' => array(-1),
					'operator' => 'NOT IN'
			);
			add_filter('posts_orderby', 'affinityfunction_orderbyreplace');
		}
		
		if (isset($args['s']) && (!affinity_empty($args['s']))) {
			$args['s_title'] = $args['s'];
			unset($args['s']);
			add_filter('posts_where', 'AffinityEbayInventoryTitle', 10, 2);
			$wp_query = new WP_Query($args);
			$found = $wp_query->found_posts;
			remove_filter('posts_where', 'AffinityEbayInventoryTitle', 10, 2);
		} else {
			$wp_query = new WP_Query($args);
			$found = $wp_query->found_posts;
		}
		
		if (!empty($_GET['unmapped'])) {
			remove_filter('posts_orderby', 'affinityfunction_orderbyreplace');
		}
		
		$data = array();
		
		while ($wp_query->have_posts()) {
			$wp_query->the_post();
			
			$suggestedCatIda = get_post_meta(get_the_ID(), '_affinity_suggestedCatId', true);
			
			$suggestedCatId = array();
			$ebayCategoryName = array();
			
			if (!empty($suggestedCatIda)) {
				$sarr = explode(',', $suggestedCatIda);
				foreach ($sarr as  $suggestedCatIdb) {
					$suggestedCatId[] = $suggestedCatIdb;
					$ebayCategoryName[] = $wpdb->get_var("SELECT GROUP_CONCAT(name ORDER BY level SEPARATOR ' > ') FROM (
						SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($suggestedCatIdb)."
						UNION
						SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
								SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($suggestedCatIdb).")
						UNION
						SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
								SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
										SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($suggestedCatIdb)."))
						UNION
						SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
								SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
										SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
												SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($suggestedCatIdb).")))) AS j");
					
				}
			}
			$suggestedCatId = implode(':::::', $suggestedCatId);
			$ebayCategoryName = implode(':::::', $ebayCategoryName);
			
			$ebayMappedCatId = get_post_meta(get_the_ID(), '_affinity_ebaycategory', true);
			
			$bycat = false;
			
			if (empty($ebayMappedCatId)) {
				$terms = get_the_terms(get_the_ID(), 'product_cat');
				if (is_array($terms)) {
					foreach ($terms as $term) {
						$ebayMappedCatId = get_term_meta($term->term_id, '_affinity_ebaycategory', true);
						if (!empty($ebayMappedCatId)) {
							$bycat = true;
							break;
						}
					}
				}
			}
			
			$ebayMappedCategoryName = '';
			if (!empty($ebayMappedCatId)) {
				$ebayMappedCategoryName = $wpdb->get_var("SELECT GROUP_CONCAT(name ORDER BY level SEPARATOR ' > ') FROM (
				SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($ebayMappedCatId)."
				UNION
				SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
						SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($ebayMappedCatId).")
				UNION
				SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
						SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
								SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($ebayMappedCatId)."))
				UNION
				SELECT level, name FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
						SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
								SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = (
										SELECT parentCategoryId FROM ".$wpdb->prefix."ebayaffinity_categories WHERE categoryId = ".intval($ebayMappedCatId).")))) AS j");
					
			}
			
			if ($bycat) {
				$ebayMappedCategoryName .= ' (by category)';
			}
			
			$product = new WC_Product(get_the_ID());
			
			$datum = array(
					'id' => get_the_ID(),
					'title' => $product->get_title(),
					'img' => $product->get_image(array(64, 64))
			);
			
			$datum['suggestedCatId'] = $suggestedCatId;
			$datum['ebayCategoryName'] = $ebayCategoryName;
			$datum['ebayMappedCatId'] = $ebayMappedCatId;
			$datum['ebayMappedCategoryName'] = $ebayMappedCategoryName;
			
			$data[] = $datum;
		}
		
		$arr = array($data, $found);
		
		if ($with_cats) {
			$arr[] = self::categoryset();
		}
		
		return $arr;
		
	}
	
	static function inventorysingle($id) {
		$taxonomies_arr = self::getAllAttributes();
		
		$args = array(
				'p' => $id,
				'post_type' => 'product'
		);
		global $wp_query;
		$wp_query = new WP_Query($args);
		$data = array();
		$var_attrs = array();
	
		if ($wp_query->have_posts()) {
			$wp_query->the_post();
			$data['blocked'] = get_post_meta(get_the_ID(), '_affinity_block', true);
			$data['ebayitemid'] = get_post_meta(get_the_ID(), '_affinity_ebayitemid', true);
			$data['server_warning'] = get_post_meta(get_the_ID(), '_affinity_prod_arr_adaptation_warnings', true);
			$data['server_error'] = get_post_meta(get_the_ID(), '_affinity_prod_arr_adaptation_errors', true);
			$data['client_warning'] = get_post_meta(get_the_ID(), '_affinity_prod_arr_client_warnings', true);
			$data['client_error'] = get_post_meta(get_the_ID(), '_affinity_prod_arr_client_errors', true);
			
			$data['shiprule'] = get_post_meta(get_the_ID(), '_affinity_shiprule', true);
			$data['titlerule'] = get_post_meta(get_the_ID(), '_affinity_titlerule', true);
			$data['ebaycategory'] = get_post_meta(get_the_ID(), '_affinity_ebaycategory', true);
			$data['ebaydesc'] = get_post_meta(get_the_ID(), '_ebaydesc', true);
			
			$useshort = get_option('ebayaffinity_useshort');
			if (empty($useshort)) {
				$useshort = get_post_meta(get_the_ID(), '_ebayuseshort', true);
			}
			
			if (!affinity_empty($data['ebaydesc'])) {
				$data['ebaydesc'] = apply_filters('the_content', $data['ebaydesc']);
			}
			
			$pid = get_the_ID();
			
			$product = wc_get_product(get_the_ID());
			$post = get_post(get_the_ID());
			
			$data['product'] = $product;
			$data['title'] = $product->get_title();

			if (!empty($useshort)) {
				$data['desc'] = $post->post_excerpt;
			} else if(!empty($post->post_content)){
				$data['desc'] = $post->post_content;
			} else {
				$data['desc'] = $post->post_excerpt;
			}
			
			$data['desc'] = apply_filters('the_content', $data['desc']);
			
			$data['img'] = array(
					$product->get_image(array(400, 400)),
					$product->get_image(array(80, 80))
			);
			$data['attributes'] = array();
			$data['categories'] = array();
			$data['variations'] = array();
			$data['sku'] = $product->get_sku();
			
			if (function_exists('wc_get_price_including_tax')) {
				$data['rrp_price'] = wc_price(wc_get_price_including_tax($product, array('qty' => 1, 'price' => $product->get_regular_price())));
				$data['sale_price'] = wc_price(wc_get_price_including_tax($product, array('qty' => 1, 'price' => $product->get_sale_price())));
				
				$price = wc_get_price_including_tax($product);
			} else {
				$data['rrp_price'] = woocommerce_price($product->get_price_including_tax(1, $product->get_regular_price()));
				$data['sale_price'] = woocommerce_price($product->get_price_including_tax(1, $product->get_sale_price()));
				
				$price = $product->get_price_including_tax();
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
			
			$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
			if (!empty($ebayprice)) {
				$price = $ebayprice;
			}
			
			$data['price'] = wc_price($price);
			$attributes = $product->get_attributes();
			
			$data['imgs'] = array();
			
			$data['imgs'][] = array(
					$product->get_image(array(400, 400)),
					$product->get_image(array(80, 80))
			);
			
			if (method_exists($product, 'get_gallery_image_ids')) {
				$aids = $product->get_gallery_image_ids();
			} else {
				$aids = $product->get_gallery_attachment_ids();
			}
			foreach($aids as $aid) {
				$data['imgs'][] = array(
						wp_get_attachment_image($aid, array(400, 400)),
						wp_get_attachment_image($aid, array(80, 80))
				);
			}
			
			foreach ($attributes as $k=>$attribute) {
				if (empty($attribute['is_variation'])) {
					$value = $product->get_attribute($attribute['name']);
					if (empty($attribute['is_taxonomy'])) {
						$data['attributes'][$attribute['name']] = $value;
					} else {
						$name = $taxonomies_arr[substr($attribute['name'], 3)];
						$data['attributes'][$name] = $value;
					}
				} else if (!empty($attribute['is_variation'])) {
					$var_attrs[$k] = $attribute['name'];
				}
			}
			
			$terms = get_the_terms($pid, 'product_cat');
			if (is_array($terms)) {
				foreach ($terms as $k=>$term) {
					$data['categories'][$term->term_id] = array('name' => $term->name,
							'shiprule' => get_term_meta($term->term_id, '_affinity_shiprule', true),
							'titlerule' => get_term_meta($term->term_id, '_affinity_titlerule', true),
							'ebaycategory' => get_term_meta($term->term_id, '_affinity_ebaycategory', true)
					);
				}
			}
			
			$allatts = get_post_meta($pid, '_product_attributes');
			
			if ($product->is_type('variable')) {
				$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => get_the_ID(), 'posts_per_page' => 100));
		
				$sale_price = array();
				$rrp_price = array();
				$price_arr = array();
				
				while ($variationloop->have_posts()) {
					$variationloop->the_post();
					if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
						$variation = new WC_Product(get_the_ID());
					} else {
						$variation = new WC_Product_Variation(get_the_ID());
					}
					$varr = array();
					$varr['sku'] = $variation->get_sku();
					$varr['img'] = $variation->get_image(array(40, 40));
					
					if (function_exists('wc_get_price_including_tax')) {
						$varr['rrp_price'] = wc_price(wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_regular_price())));
						$varr['sale_price'] = wc_price(wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_sale_price())));
						
						$price = wc_get_price_including_tax($variation);
					} else {
						$varr['rrp_price'] = woocommerce_price($variation->get_price_including_tax(1, $variation->get_regular_price()));
						$varr['sale_price'] = woocommerce_price($variation->get_price_including_tax(1, $variation->get_sale_price()));
						
						$price = $variation->get_price_including_tax();
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

					$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
					if (!empty($ebayprice)) {
						$price = $ebayprice;
					}
					
					$varr['price'] = wc_price($price);
					$varr['attributes'] = array();
					
					$fullkey = '';
					
					foreach ($var_attrs as $k=>$v) {
						$name = $k;
						if (substr($k, 0, 3) === 'pa_') {
							$name = $taxonomies_arr[substr($k, 3)];
							$varr['attributes'][$name] = get_post_meta(get_the_ID(), 'attribute_'.$k, true);
						} else {
							$exp = explode('|', $allatts[0][$name]['value']);
							
							$name = $allatts[0][$name]['name'];
							$varr['attributes'][$name] = get_post_meta(get_the_ID(), 'attribute_'.$k, true);
							
							foreach ($exp as $kke => $vve) {
								$vve = trim($vve);
								if ($vve === $varr['attributes'][$name]) {
									$fullkey .= str_pad($kke, 8, '0', STR_PAD_LEFT);
								}
							}
						}
						
						if (substr($k, 0, 3) === 'pa_') {
							$terms = get_terms($k, array(
									'hide_empty' => false,
							));
							
							foreach ($terms as $key => $term) {
								if ($term->slug === $varr['attributes'][$name]) {
									$varr['attributes'][$name] = $term->name;
									$fullkey .= str_pad($key, 8, '0', STR_PAD_LEFT);
								}
							}
						}
					}
					
					$fullkey .= uniqid(true);
						
					$data['variations'][$fullkey] = $varr;
					
					$rrp_price[] = $varr['rrp_price'];
					$sale_price[] = $varr['sale_price'];
					$price_arr[] = $varr['price'];
				}
				
				ksort($data['variations']);
				$data['variations'] = array_values($data['variations']);
	
				if (is_array($rrp_price)) {
					$rrp_price = array_unique($rrp_price);
					$rrp_price = implode(' / ', $rrp_price);
					$data['rrp_price'] = $rrp_price;
				}
				
				if (is_array($sale_price)) {
					$sale_price = array_unique($sale_price);
					$sale_price = implode(' / ', $sale_price);
					$data['sale_price'] = $sale_price;
				}
				
				if (is_array($price_arr)) {
					$price_arr = array_unique($price_arr);
					$price_arr = implode(' / ', $price_arr);
					$data['price'] = $price_arr;
				}
			}
		}
	
		wp_reset_postdata();
	
		return $data;
	}
	
	static function popargs($args, $min, $max) {
		if (!isset($_GET['s'])) {
			$_GET['s'] = '';
		}
		if (!isset($_GET['shownotitleopt'])) {
			$_GET['shownotitleopt'] = 0;
		}
		if (!isset($_GET['showerrors'])) {
			$_GET['showerrors'] = 0;
		}
		
		if (!isset($_GET['showonebay'])) {
			$_GET['showonebay'] = 0;
		}
		if (!isset($_GET['shownotonebay'])) {
			$_GET['shownotonebay'] = 0;
		}
		
		if (!isset($_GET['showblocked'])) {
			$_GET['showblocked'] = 1;
		}
		if (!isset($_GET['showunblocked'])) {
			$_GET['showunblocked'] = 1;
		}
		if ((!isset($_GET['showblocked'])) && (!isset($_GET['showunblocked']))) {
			$_GET['showblocked'] = 1;
			$_GET['showunblocked'] = 1;
		}
		
		if (!isset($_GET['showneedsmapping'])) {
			$_GET['showneedsmapping'] = 0;
		}
		if (!isset($_GET['shownotitleopt'])) {
			$_GET['shownotitleopt'] = 0;
		}
		if (!isset($_GET['showerrors'])) {
			$_GET['showerrors'] = 0;
		}
		
		if (!isset($_GET['pricemin'])) {
			$_GET['pricemin'] = $min;
		}
		if (!isset($_GET['pricemax'])) {
			$_GET['pricemax'] = $max;
		}
		if (!isset($_GET['catslugs'])) {
			$_GET['catslugs'] = array();
		}
		
		if (empty($_GET['order'])) {
			$_GET['order'] = 'title';
		}
		
		switch ($_GET['order']) {
			case 'pricedesc':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = '_price';
				$args['order'] = 'DESC';
				break;
			case 'priceasc':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = '_price';
				$args['order'] = 'ASC';
				break;
			case 'seller':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = 'total_sales';
				$args['order'] = 'DESC';
				break;
			default:
				$args['orderby'] = 'title';
				$args['order'] = 'ASC';
				$_GET['order'] = 'title';
		}
		
		if (!empty($_GET['catslugs'])) {
			$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' => $_GET['catslugs'],
					'operator' => 'IN'
			);
		}
		
		
		if ((!empty($_GET['pricemin'])) && (!empty($_GET['pricemax'])) && $_GET['pricemin'] > $min && $_GET['pricemax'] < $max) {
			
			$args['meta_query'][] = array('relation' => 'OR',
					array(
							array('relation' => 'AND',
									array(
											'key' => '_price',
											'compare' => '>=',
											'value' => $_GET['pricemin'],
											'type' => 'numeric'
									),
									array(
											'key' => '_price',
											'compare' => '<=',
											'value' => $_GET['pricemax'],
											'type' => 'numeric'
									)
							)
					),
					array(
							array('relation' => 'AND',
									array(
											'key' => '_ebayprice',
											'compare' => '>=',
											'value' => $_GET['pricemin'],
											'type' => 'numeric'
									),
									array(
											'key' => '_ebayprice',
											'compare' => '<=',
											'value' => $_GET['pricemax'],
											'type' => 'numeric'
									)
							)
					)
			);
		} else if ((!empty($_GET['pricemin'])) && $_GET['pricemin'] > $min) {
			$args['meta_query'][] = array('relation' => 'OR',
					array(
							'key' => '_price',
							'compare' => '>=',
							'value' => $_GET['pricemin'],
							'type' => 'numeric'
					),
					array(
							'key' => '_ebayprice',
							'compare' => '>=',
							'value' => $_GET['pricemin'],
							'type' => 'numeric'
					)
			);
		} else if ((!empty($_GET['pricemax'])) && $_GET['pricemax'] < $max) {
			$args['meta_query'][] = array('relation' => 'OR',
					array(
							'key' => '_price',
							'compare' => '<=',
							'value' => $_GET['pricemax'],
							'type' => 'numeric'
					),
					array(
							'key' => '_ebayprice',
							'compare' => '<=',
							'value' => $_GET['pricemax'],
							'type' => 'numeric'
					)
			);
		}
		
		if (!empty($_GET['showerrors'])) {
			$args['meta_query'][] = array('relation' => 'OR',
					array(
							'key' => '_affinity_prod_arr_adaptation_errors',
							'compare' => '!=',
							'value' => '[]',
							'type' => 'string'
					),
					array(
							'key' => '_affinity_prod_arr_client_errors',
							'compare' => '!=',
							'value' => '[]',
							'type' => 'string'
					)
			);
		}
		
		if (!empty($_GET['showonebay'])) {
			$args['meta_query'][] = array(
					'key' => '_affinity_ebayitemid',
					'compare' => 'EXISTS',
			);
		}
		
		if (!empty($_GET['shownotonebay'])) {
			$args['meta_query'][] = array(
					'key' => '_affinity_ebayitemid',
					'compare' => 'NOT EXISTS',
			);
		}
		
		if (!empty($_GET['showneedsmapping'])) {
			require_once(__DIR__.'/AffinityEbayCategory.php');
			$catrules_cats = AffinityEbayCategory::getCategoriesCatRules();
			$catsRules = array();
				
			foreach ($catrules_cats as $k=>$catrules_cat) {
				if (!empty($catrules_cat[1])) {
					$catsRules[$k] = $k;
				}
			}
				
			$args['meta_query'][] = array(
					'key' => '_affinity_ebaycategory',
					'compare' => 'NOT EXISTS'
			);
				
			if (!empty($catsRules)) {
				$args['tax_query'][] = array(
						'taxonomy' => 'product_cat',
						'field' => 'id',
						'terms' => array_values($catsRules),
						'operator' => 'NOT IN'
				);
			}
		}
		
		if (!empty($_GET['shownotitleopt'])) {
			require_once(__DIR__.'/AffinityEbayCategory.php');
			require_once(__DIR__.'/AffinityTitleRule.php');
			$titlerules_cats = AffinityEbayCategory::getCategoriesTitleRules();
			$title_rules = AffinityTitleRule::getAllRules();
			$catsRules = array();
			$titleRules = array();
			$hasdef = false;
			foreach ($titlerules_cats as $k=>$v) {
				foreach ($title_rules as $vv) {
					if (!empty($vv->is_default)) {
						$hasdef = true;
						break;
					}
					$titleRules[$vv->id] = $vv->id;
					if ($v[1] == $vv->id) {
						$catsRules[$k] = $k;
						break;
					}
				}
			}
			if ($hasdef) {
				$args['meta_query'][] = array(
						'key' => '_affinity_titleopt',
						'compare' => '=',
						'value' => -1,
						'type' => 'numeric'
				);
			} else {
				if (!empty($titleRules)) {
					$args['meta_query'][] = array('relation' => 'OR',
							array(
									'key' => '_affinity_titlerule',
									'compare' => 'NOT IN',
									'value' => array_values($titleRules)
							),
							array(
									'key' => '_affinity_titlerule',
									'compare' => 'NOT EXISTS'
							)
					);
				}
				if (!empty($catsRules)) {
					$args['tax_query'][] = array(
							'taxonomy' => 'product_cat',
							'field' => 'id',
							'terms' => array_values($catsRules),
							'operator' => 'NOT IN'
					);
				}
			}
		}
		
		if ((!empty($_GET['showblocked'])) && empty($_GET['showunblocked'])) {
			$args['meta_query'][] = array(
					'key' => '_affinity_block',
					'compare' => '=',
					'value' => 1,
					'type' => 'numeric'
			);
		} else if ((!empty($_GET['showunblocked'])) && empty($_GET['showblocked'])) {
			$args['meta_query'][] = array(
					'key' => '_affinity_block',
					'compare' => 'NOT EXISTS',
			);
		}
		
		if (!empty($_GET['s'])) {
			$args['s'] = stripslashes($_GET['s']);
		}
		
		return $args;
	}
		
	static function inventoryset() {
		global $wpdb;
	
		$min = floor($wpdb->get_var('
	                SELECT min(meta_value + 0)
	                FROM '.$wpdb->posts.'
	                LEFT JOIN '.$wpdb->postmeta.' ON '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
	                WHERE meta_key IN (\'' . implode('\',\'', apply_filters('woocommerce_price_filter_meta_keys', array('_price', '_ebayprice'))) . '\')
	                AND meta_value != \'\''
				));
	
		$max = ceil($wpdb->get_var('
	                SELECT max(meta_value + 0)
	                FROM '.$wpdb->posts.'
	                LEFT JOIN '.$wpdb->postmeta.' ON  '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
	                WHERE meta_key IN (\'' . implode('\',\'', apply_filters('woocommerce_price_filter_meta_keys', array('_price', '_ebayprice'))) . '\')
					AND meta_value != \'\''
				));
	
		if (!isset($_GET['paged'])) {
			$_GET['paged'] = null;
		}
		
		$paged = intval($_GET['paged']);
	
		if (empty($paged)) {
			$paged = 1;
		}
		
		if (empty($_GET['ebayaffinity-perpage'])) {
			if (!empty($_COOKIE['affinity_perpage'])) {
				$_GET['ebayaffinity-perpage'] = $_COOKIE['affinity_perpage'];
			} else {
				$_GET['ebayaffinity-perpage'] = 10;
			}
		}
	
		$args = array(
				'post_type' => 'product',
				'paged' => $paged,
				'posts_per_page' => $_GET['ebayaffinity-perpage'],
				'post_status' => 'publish',
				'meta_query'=> array(),
				'tax_query' => array(
						array('relation' => 'OR',
						array(
								'taxonomy' => 'product_type',
								'field' => 'slug',
								'terms' => array('simple', 'variable'),
								'operator' => 'IN'
						),
						array(
								'taxonomy' => 'product_type',
								'operator' => 'NOT EXISTS',
						))
				)
		);
		
		if (!isset($_GET['order'])) {
			$_GET['order'] = null;
		}
		
		$args = self::popargs($args, $min, $max);
	
		global $wp_query;
		
		if (isset($args['s']) && (!affinity_empty($args['s']))) {
			$args['s_title'] = $args['s'];
			unset($args['s']);
			add_filter('posts_where', 'AffinityEbayInventoryTitle', 10, 2);
			$wp_query = new WP_Query($args);
			$found = $wp_query->found_posts;
			remove_filter('posts_where', 'AffinityEbayInventoryTitle', 10, 2);
		} else {
			$wp_query = new WP_Query($args);
			$found = $wp_query->found_posts;
		}
	
		$data = array();
	
		while ($wp_query->have_posts()) {
			$wp_query->the_post();
	
			$blocked = get_post_meta(get_the_ID(), '_affinity_block', true);
			$errors1 = json_decode(get_post_meta(get_the_ID(), '_affinity_prod_arr_adaptation_errors', true));
			$errors2 = json_decode(get_post_meta(get_the_ID(), '_affinity_prod_arr_client_errors', true));
			$err = array();
			if (!empty($errors1)) {
				foreach ($errors1 as $e) {
					$err[] = $e;
				}
			}
			if (!empty($errors2)) {
				foreach ($errors2 as $e) {
					$err[] = $e;
				}
			}
			$err = array_unique($err);
			$lasterror = implode("\n", $err);
	
			$product = wc_get_product(get_the_ID());
	
			$sku = $product->get_sku();
			
			if (function_exists('wc_get_price_including_tax')) {
				$rrp_price = wc_price(wc_get_price_including_tax($product, array('qty' => 1, 'price' => $product->get_regular_price())));
				$sale_price = wc_price(wc_get_price_including_tax($product, array('qty' => 1, 'price' => $product->get_sale_price())));
				
				$price = wc_get_price_including_tax($product, array('qty' => 1, 'price' => $product->get_price()));
			} else {
				$rrp_price = woocommerce_price($product->get_price_including_tax(1, $product->get_regular_price()));
				$sale_price = woocommerce_price($product->get_price_including_tax(1, $product->get_sale_price()));
				
				$price = $product->get_price_including_tax(1, $product->get_price());
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
			
			$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
			if (!empty($ebayprice)) {
				$price = $ebayprice;
			}

			$price = wc_price($price);
	
			$id = get_the_ID();
			
			if ($product->is_type('variable')) {
				$variationloop = new WP_Query(array('post_type' => 'product_variation', 'post_parent' => get_the_ID(), 'posts_per_page' => 100));
				
				while ($variationloop->have_posts()) {
					$variationloop->the_post();
					if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
						$variation = new WC_Product(get_the_ID());
					} else {
						$variation = new WC_Product_Variation(get_the_ID());
					}
					if (!is_array($sku)) {
						$sku = array();
					}
					$sku[] = $variation->get_sku();
					if (!is_array($rrp_price)) {
						$rrp_price = array();
					}
					if (function_exists('wc_get_price_including_tax')) {
						$rrp_price[] = wc_price(wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_regular_price())));
					} else {
						$rrp_price[] = woocommerce_price($variation->get_price_including_tax(1, $variation->get_regular_price()));
					}
					
					if (!is_array($sale_price)) {
						$sale_price = array();
					}
					if (function_exists('wc_get_price_including_tax')) {
						$sale_price[] = wc_price(wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_sale_price())));
					} else {
						$sale_price[] = woocommerce_price($variation->get_price_including_tax(1, $variation->get_sale_price()));
					}
					
					if (!is_array($price)) {
						$price = array();
					}
					
					if (function_exists('wc_get_price_including_tax')) {
						$price_v = wc_get_price_including_tax($variation, array('qty' => 1, 'price' => $variation->get_price()));
					} else {
						$price_v = $variation->get_price_including_tax(1, $variation->get_price());
					}
					
					$adjust = get_option('ebayaffinity_priceadjust');
					
					if (strpos($adjust, 'num') !== false) {
						$adjust = str_replace('num', '', $adjust);
						$adjust = floatval($adjust);
						$price_v += $adjust;
					} else {
						if (!empty($adjust)) {
							$price_v += $price_v * ($adjust / 100);
						}
					}
					
					$ebayprice = get_post_meta(get_the_ID(), '_ebayprice', true);
					if (!empty($ebayprice)) {
						$price_v = $ebayprice;
					}
					
					$price[] = wc_price($price_v);
				}
		
				if (is_array($sku)) {
					$sku = array_unique($sku);
					$sku = implode(' / ', $sku);
				}
		
				if (is_array($rrp_price)) {
					$rrp_price = array_unique($rrp_price);
					$rrp_price = implode(' / ', $rrp_price);
				}
		
				if (is_array($sale_price)) {
					$sale_price = array_unique($sale_price);
					$sale_price = implode(' / ', $sale_price);
				}
					
				if (is_array($price)) {
					$price = array_unique($price);
					$price = implode(' / ', $price);
				}
			}
			$data[] = array(
					'id' => $id,
					'title' => $product->get_title(),
					'img' => $product->get_image(array(64, 64)),
					'rrp_price' => $rrp_price,
					'sale_price' => $sale_price,
					'price' => $price,
					'sku' => $sku,
					'blocked' => $blocked,
					'lasterror' => $lasterror
			);
		}
		return array($data, $found, $min, $max);
	}
		
	static function categoryset($byid=false, $map=false) {
		$args = array(
				'taxonomy'     => 'product_cat',
				'orderby'      => 'name',
				'show_count'   => 0,
				'pad_counts'   => 0,
				'hierarchical' => 0,
				'title_li'     => '',
				'hide_empty'   => 1
		);
		
		if ($map) {
			$args['hide_empty'] = 0;
		}

		$categories = get_categories($args);
		$cats = array();
		foreach ($categories as $category) {
			if ($map) {
				$t = array(
						html_entity_decode($category->name, ENT_QUOTES, get_option('blog_charset')),
						get_term_meta($category->term_id, '_affinity_suggestedCatId', true),
						get_term_meta($category->term_id, '_affinity_ebaycategory', true),
						$category->parent
				);
			} else {
				$t = html_entity_decode($category->name, ENT_QUOTES, get_option('blog_charset'));
			}
			if ($byid) {
				$cats[$category->cat_ID] = $t;
			} else {
				$cats[$category->slug] = $t;
			}
		}
		return $cats;
	}
	
	static function titlelength() {
		global $wpdb;
		return $wpdb->get_row("SELECT MAX(LENGTH(post_title)) AS max, MIN(LENGTH(post_title)) AS min FROM " . $wpdb->posts . " WHERE post_status = 'publish' AND post_type = 'product'", ARRAY_A);
	}

	static function maxOf($field, $prod, $curr){
		if($field == 'title'){
			$len = strlen($prod[$field]);
			if($len > $curr)
				return strlen($prod[$field]);
		} else {
			if(strlen($prod['attributes'][$field]['value']) > $curr){
				return strlen($prod['attributes'][$field]['value']);
			}
		}
		return $curr;
	}
}