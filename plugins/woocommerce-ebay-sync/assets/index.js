var ebayaffinityInvFormAjax_event;

var ebayaffinity_ajax_success = '';
var ebayaffinity_ajax_callback;
var ebayaffinity_ajax_remap_callback
var ebayaffinity_ajax_exkey;
var ebayaffinity_ajax_exvalue;
var ebayaffinity_ajax_key;
var ebayaffinity_ajax_value;
var ebayaffinity_ajax_s = '';
var ebayaffinity_ajax_categoryId = 0;
var ebayaffinity_ajax_paged = 1;
var ebayaffinity_ajax_title = '';
var ebayaffinity_shiprulenum = 0;
var ebayaffinity_ajax_set1 = '';
var ebayaffinity_ajax_set2 = '';
var ebayaffinity_ajax_set1url = '';
var ebayaffinity_ajax_set2url = '';
var ebayaffinity_ajax_catmode = 0;
var ebayaffinity_ajax_invmode = 0;
var ebayaffinity_scrollerer_anim = false;

var ebayaffinity_ajax_catslugs = [];
var ebayaffinity_ajax_pricemin = 0;
var ebayaffinity_ajax_pricemax = 2147483647;
var ebayaffinity_ajax_showunblocked = 1;
var ebayaffinity_ajax_showblocked = 1;
var ebayaffinity_ajax_showneedsmapping = 0;
var ebayaffinity_ajax_shownotitleopt = 0;
var ebayaffinity_ajax_showerrors = 0;
var ebayaffinity_ajax_showerrors = 0;
var ebayaffinity_ajax_unmapped = 'unmapped';
var ebayaffinity_ajax_filteroutprods = [];

var ebayaffinity_ajax_prodsugg = [];

var ebayaffinity_ajax_sorder = 'title';

function ebayaffinity_filter_out(e) {
	if (jQuery(e.target).closest('#ebayaffinity-inv-filter-form, #ebayaffinity-inv-filter-form-2').length == 0) {
		jQuery('.ebayaffinity-inv-filter').animate({'opacity': '0'}, {
			complete: function() {
				jQuery('.ebayaffinity-inv-filter').css('display', 'none');
				jQuery('.ebayaffinity-inv-filter').css('opacity', '');
			},
			duration: '100'
		});
		jQuery('body').unbind('click', ebayaffinity_filter_out);
	}
}

function ebayaffinityInvFormAjax_handle(data) {
	jQuery('ul.page-numbers').empty();
	
	var pages = Math.ceil(data[1] / data[3]);
	var paged = data[2];
	
	if (pages > 1) {
		
		if (paged > 1) {
			jQuery('ul.page-numbers').append('<a class="prev page-numbers" href="admin.php?page=ebay-sync-inventory&amp;paged=1">&#8592;</a></li>');
			jQuery('ul.page-numbers').append(' ');
		}
		
		for (var i = 1 ; i <= pages; i += 1) {
			var template;
			
			if (data[2] == i) {
				template = jQuery('<li><span class="page-numbers current"></span></li>');
				
				template.find('span').text(i);
				
			} else {
				template = jQuery('<li><a class="page-numbers"></a></li>');
				
				template.find('a').text(i);
				template.find('a').attr('href', 'admin.php?page=ebay-sync-inventory&paged=' + i);
			}
			
			jQuery('ul.page-numbers').append(template);
			jQuery('ul.page-numbers').append(' ');
		}
		
		if (paged < pages) {
			jQuery('ul.page-numbers').append('<a class="next page-numbers" href="admin.php?page=ebay-sync-inventory&amp;paged=' + (paged + 1) + '">&#8594;</a></li>');
		}
	}
	
	jQuery('table.ebayaffinity-inv-table tbody').empty();
	
	jQuery('table.ebayaffinity-inv-table tbody').append('<tr>'+
				'<th><input type="checkbox" id="invvisprodall"></th>'+
				'<th colspan="2">Product</th>'+
				'<th class="ebayaffinity-not-mobile">Regular Price</th>'+
				'<th class="ebayaffinity-not-mobile">Sale Price</th>'+
				'<th>eBay Price</th>'+
				'<th class="ebayaffinity-not-mobile ebayaffinity-not-tablet">SKU</th>'+
				'<th class="ebayaffinity-not-mobile">Visibility</th>'+
				'<th class="ebayaffinity-not-mobile">Listing Error</th>'+
			'</tr>');
	
	data = data[0];
	
	for (var i in data) {
		if (data.hasOwnProperty(i)) {
			console.log(data[i]);
			var template = jQuery('<tr>'+
					'<td><input type="checkbox" class="invvisprod"></td>'+
					'<td>'+
						'<a href="#"></a>'+
					'</td>'+
					'<td>'+
						'<a href="#"></a>'+
					'</td>'+
					'<td class="ebayaffinity-not-mobile"></td>'+
					'<td class="ebayaffinity-not-mobile"></td>'+
					'<td></td>'+
					'<td class="ebayaffinity-not-mobile ebayaffinity-not-tablet"></td>'+
					'<td class="ebayaffinity-not-mobile"><div class="ebayaffinity-switch-on-off"><div>&nbsp;</div></div></td>'+
					'<td class="ebayaffinity-not-mobile"></td>'+
				'</tr>');
			
			template.attr('data-id', parseInt(data[i].id, 10));
			template.find('a').attr('href', 'admin.php?page=ebay-sync-inventory&id=' + parseInt(data[i].id, 10));
			template.find('a').eq(0).html(data[i].img);
			template.find('a').eq(1).text(data[i].title);
			template.find('td').eq(3).html(data[i].rrp_price);
			template.find('td').eq(4).html(data[i].sale_price);
			template.find('td').eq(5).html(data[i].price);
			template.find('td').eq(6).text(data[i].sku);
			
			if (data[i]['blocked'] == 1) {
				template.find('.ebayaffinity-switch-on-off').addClass('ebayaffinity-switch-off');
			} else {
				template.find('.ebayaffinity-switch-on-off').addClass('ebayaffinity-switch-on');
			}
			
			if (data[i]['lasterror'] !== undefined && data[i]['lasterror'] !== null && data[i]['lasterror'] !== '') {
				template.find('td').eq(8).append('<div class="ebayaffinity-lasterror"><div></div></div>');
				template.find('.ebayaffinity-lasterror div').text(data[i]['lasterror']);
			}
			
			jQuery('table.ebayaffinity-inv-table tbody').append(template);
		}
	}
	
	ebayaffinityFixOnOffs();
	
	return false;
}

function ebayRemainingAll() {
	jQuery('.ebayaffinity-rules .ebayaffinity-template-container').each(function() {
		ebayaffinityRemaining(jQuery(this));
	});
}

function ebayaffinityRemaining(section) {
	var is_default = section.closest('.ebayaffinity-rule-container').find('.ebay-affinity-rule-default-radio').prop('checked');
	var rule_no = section.closest('.ebayaffinity-rule-container').attr('data-id');
	var dcount = 0;
	jQuery(section).find('div[data-count]').each(function() {
		if (jQuery(this).attr('data-type') == 'attr' && jQuery(this).attr('data-value') == 'title') {
			if (is_default) {
				dcount += parseInt(jQuery(this).attr('data-count'), 10);
			} else {
				var maxlength = 0;
				jQuery('input.ebayaffinity_catshiprules[value=' + rule_no + '], input.ebayaffinity_prodshiprules[value=' + rule_no + ']').each(function() {
					if (jQuery(this).attr('data-length') > maxlength) {
						maxlength = parseInt(jQuery(this).attr('data-length'), 10);
					}
				});
				dcount += maxlength;
			}
		} else {
			dcount += parseInt(jQuery(this).attr('data-count'), 10);
		}
		
		dcount += 1;
	});
	dcount -= 1;
	if (dcount < 0) {
		dcount = 0;
	}
	jQuery(section).find('.ebayaffinity-template-remaining span').text(80 - dcount);
	if ((80 - dcount) < 0) {
		jQuery(section).find('.ebayaffinity-template-remaining').css('color', 'red');
	} else {
		jQuery(section).find('.ebayaffinity-template-remaining').css('color', '');
	}
	if ((80 - dcount) == 1) {
		jQuery(section).find('.ebayaffinity-template-remaining-txt').text('character remaining');
	} else {
		jQuery(section).find('.ebayaffinity-template-remaining-txt').text('characters remaining');
	}
	jQuery(section).find('.ebayaffinity-template-remaining').closest('.ebayaffinity-template-container').append(jQuery(section).find('.ebayaffinity-template-remaining'));
}

function ebayaffinity_receivemessage(event) {
	try {
		if (event.data[0] == 'ebayaffinity_authback') {
			ebayaffinity_authback(event.data[1], event.data[2], event.data[3]);
		}
	} catch (e) {
		//
	}
}
window.addEventListener("message", ebayaffinity_receivemessage, false);

function ebayaffinity_authback(token, user, puuid) {
	if (ebayaffinity_ourpuuid !== null && ebayaffinity_ourpuuid !== '' && ebayaffinity_ourpuuid !== false &&
			puuid !== ebayaffinity_ourpuuid) {
		alert('This eBay account is not the same as your existing eBay account.');
		return;
	}
	
	if (ebayaffinity_ourpuuid !== null && ebayaffinity_ourpuuid !== '' && ebayaffinity_ourpuuid !== false &&
			ebayaffinity_ourpuuid === puuid) {
		jQuery('#ebayaffinity_ebayuserid').val(user);
		jQuery('#ebayaffinity_puuid').val(puuid);
		jQuery('#ebayaffinity_token').val(token);
		jQuery('#ebay-link-settings-form').submit();
		return;
	}
	
	if (ebayaffinity_ouruser !== null && ebayaffinity_ouruser !== '' && ebayaffinity_ouruser !== false &&
			user != ebayaffinity_ouruser) {
		jQuery('#ebayaffinity-pop').css('display', 'block');
		jQuery('#ebayaffinity-pop-back').css('display', 'block');
		
		jQuery('#ebayaffinity-pop').attr('data-user', user);
		jQuery('#ebayaffinity-pop').attr('data-puuid', puuid);
		jQuery('#ebayaffinity-pop').attr('data-token', token);
		
		jQuery('#ebayaffinity-pop-y, #ebayaffinity-pop-n').unbind();
		
		jQuery('#ebayaffinity-pop-y').click(function() {
			jQuery('#ebayaffinity_ebayuserid').val(jQuery('#ebayaffinity-pop').attr('data-user'));
			jQuery('#ebayaffinity_puuid').val(jQuery('#ebayaffinity-pop').attr('data-puuid'));
			jQuery('#ebayaffinity_token').val(jQuery('#ebayaffinity-pop').attr('data-token'));
			jQuery('#ebay-link-settings-form').submit();
		});
		
		jQuery('#ebayaffinity-pop-n').click(function() {
			jQuery('#ebayaffinity-pop').css('display', 'none');
			jQuery('#ebayaffinity-pop-back').css('display', 'none');
		});
		return;
	}
	
	jQuery('#ebayaffinity_ebayuserid').val(user);
	jQuery('#ebayaffinity_puuid').val(puuid);
	jQuery('#ebayaffinity_token').val(token);
	jQuery('#ebay-link-settings-form').submit();
}

function ebayaffinity_auth() {
	try {
		window.showModelDialog(ebayaffinity_authurl, window);
	} catch (e) {
		window.open(ebayaffinity_authurl);
	}
	return false;
}

function ebayaffinity_help() {
	var h = location.hash;
	
	if (h.length > 1) {
		if (jQuery(h).closest('.ebayaffinity-rule-container').css('display') == 'none' && jQuery(h).closest('.ebayaffinity-rule-container').css('opacity') == 1) {
			jQuery('.ebayaffinity-titleopt-help .ebayaffinity-rule-container').each(function() {
				if (jQuery(this).css('display') == 'block' && jQuery(this).css('opacity') == 1) {
					if ('#' + jQuery(this).find('a').eq(0).attr('name') != h) {
						jQuery(this).css('opacity', '');
						jQuery(this).animate({'opacity': '0'}, {
							complete: function() {
								jQuery(this).css('display', 'none');
								jQuery(this).css('opacity', '');
								jQuery(h).closest('.ebayaffinity-rule-container').css('display', 'block');
								jQuery(h).closest('.ebayaffinity-rule-container').css('opacity', '0');
								jQuery(h).closest('.ebayaffinity-rule-container').animate({'opacity': '1'}, {
									complete: function() {
										jQuery(this).css('opacity', '');
										
									},
									duration: '100'
								});
								
							},
							duration: '100'
						});
					}
				}
			});
		}
	}
	
	setTimeout(ebayaffinity_help, 50);
}

function ebayaffinitySetProductsToeBayCat(data) {
	var templateform = jQuery('<form id="submitnow" method="post" action="admin.php?page=ebay-sync-mapping&amp;cat2prod=1"></form>');
	if (ebayaffinity_ajax_catmode == 1) {
		templateform.attr('action', 'admin.php?page=ebay-sync-mapping&cat2cat=1');
	}
	if (ebayaffinity_ajax_invmode == 1) {
		templateform.attr('action', 'admin.php?page=ebay-sync-inventory&id='+data[0][0]);
	}
	for (i in data) {
		if (data.hasOwnProperty(i)) {
			var template = jQuery('<input type="hidden" class="ebayaffinity_prodcats"></input>');
			template.attr('name', 'ebayaffinity_prodcats[' + data[i][0] + ']');
			if (ebayaffinity_ajax_catmode == 1) {
				template.attr('class', 'ebayaffinity_catcats');
				template.attr('name', 'ebayaffinity_catcats[' + data[i][0] + ']');
			}
			template.attr('value', '');
			templateform.append(template);
		}
	}
	jQuery('body').append(templateform);
	
	var product_data = data;
	
	jQuery.ajax({
		method: "POST",
		url: ajaxurl,
		dataType: 'json',
		data: { 
				action: 'ebaycategories', 
				id: -1
		},
		success: function(data) {
			var j = 0;
			
			var prod_template = jQuery('<div class="ebayaffinity-prod-box ebayaffinity-prod-box-closed">'+
					'<div class="ebayaffinity-prod-box-header"></div>'+
					'<a class="ebayaffinity-prod-box-edit" href="#">Edit products</a>'+
					'<div class="ebayaffinity-prod-box-entries"></div>'+
				'</div>');
				
			if (ebayaffinity_ajax_catmode == 1) {
				prod_template.addClass('ebayaffinity-prod-box-cat');
				prod_template.find('.ebayaffinity-prod-box-edit').text('Edit categories');
			}
			
			for (i in product_data) {
				if (product_data.hasOwnProperty(i)) {
					var templatein = jQuery('<div class="ebayaffinity-product-item">'+
							'<div class="ebayaffinity-bt-delete">'+
								'&times;'+
							'</div>'+
							'<span class="ebayaffinity-product-unit ebayaffinity-product-leaf"></span>'+
						'</div>');
						templatein.attr('data-product-id', product_data[i][0]);
						templatein.find('span').text(product_data[i][2]);
						prod_template.find('.ebayaffinity-prod-box-entries').append(templatein);
					j += 1;
				}
			}
			
			if (j == 1) {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' product selected');
			} else {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' products selected');
			}
			
			if (ebayaffinity_ajax_catmode == 1) {
				if (j == 1) {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' category selected');
				} else {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' categories selected');
				}
			}

			var suggcats = [];
			var hassuggcats = false;
			
			var template = jQuery('<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block"></div>');
			var j = 0;
			for (i in data) {
				if (data.hasOwnProperty(i)) {
					if (j == 0) {
						if (ebayaffinity_ajax_catmode == 1 || ebayaffinity_ajax_title == 'Mapping') {
							for (l in product_data) {
								if (product_data.hasOwnProperty(l)) {
									var suggtxt = jQuery('#ebayaffinity_cat_id_' + product_data[l][0]).attr('data-suggtxt');
									var suggid = jQuery('#ebayaffinity_cat_id_' + product_data[l][0]).attr('data-suggid');
									if (suggid != '' && suggtxt != '' && suggid !== undefined && suggtxt !== undefined && suggid !== null && suggtxt !== null) {
										var ssssuggid = suggid.split(':::::');
										var ssssuggtxt = suggtxt.split(':::::');
										for (var jj = 0 ; jj < ssssuggid.length; jj += 1) {
											suggcats['s_' + ssssuggid[jj]] = ssssuggtxt[jj];
										}
										
										hassuggcats = true;
									}
								}
							}
							
							for (l in product_data) {
								if (product_data.hasOwnProperty(l)) {
									try {
										var suggtxt = ebayaffinity_ajax_prodsugg[product_data[l][0]][1];
										var suggid = ebayaffinity_ajax_prodsugg[product_data[l][0]][0];
										if (suggid != '' && suggtxt != '' && suggid !== undefined && suggtxt !== undefined && suggid !== null && suggtxt !== null) {
											var ssssuggid = suggid.split(':::::');
											var ssssuggtxt = suggtxt.split(':::::');
											for (var jj = 0 ; jj < ssssuggid.length; jj += 1) {
												suggcats['s_' + ssssuggid[jj]] = ssssuggtxt[jj];
											}
											hassuggcats = true;
										}
									} catch (f) {
										//
									}
								}
							}
							
							if (hassuggcats) {
								var tempsugg = jQuery('<div id="ebayaffinity_catops_sugg"></div>');
								var ll = 0;
								for (m in suggcats) {
									if (suggcats.hasOwnProperty(m)) {
										ll++;
										var suggid = m.replace('s_', '');
										
										var tempsuggin = jQuery('<div class="ebayaffinity-catopt"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="50"><input type="radio" class="ebayaffinity-radios" name="ebayCategoryId" autocomplete="off"></td><td style="width: auto;"><label></label></td></tr></table></div>');
										tempsuggin.find('input').attr('value', suggid);
										tempsuggin.find('input').attr('id', 'ebayaffinity_radio_' + suggid);
										tempsuggin.find('label').text(suggcats[m]);
										var htmlcatname = tempsuggin.find('label').html();
										htmlcatname = htmlcatname.replace(/&gt;/g, '<span class="ebayaffinity-rightblue">&#x203a;</span>') + '<span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span>';
										tempsuggin.find('label').html(htmlcatname);
										tempsuggin.find('label').attr('for', 'ebayaffinity_radio_' + suggid);
										tempsugg.append(tempsuggin);
									}
								}
								if (ll == 1) {
									template.append(jQuery('<div class="ebayaffinity-ajax-header ebayaffinity-ajax-header-cat">Suggested category</div>'));
								} else {
									template.append(jQuery('<div class="ebayaffinity-ajax-header ebayaffinity-ajax-header-cat">Suggested categories</div>'));
									
								}
								template.append(tempsugg);
								template.append(jQuery('<div class="ebayaffinity-ajax-header ebayaffinity-ajax-header-cat">Other eBay categories</div>'));
							} else {
								template.append(jQuery('<div class="ebayaffinity-ajax-header ebayaffinity-ajax-header-cat">Select an eBay category</div>'));
							}
						} else {
							template.append(jQuery('<div class="ebayaffinity-ajax-header ebayaffinity-ajax-header-cat">Select an eBay category</div>'));
						}
					}
					j += 1;
					var templatein = jQuery('<a data-notpop="1" class="ebayaffinity-inv-detail-data-section-clicker ebayaffinity-inv-detail-data-section-clicker-cat ebayaffinity-inv-detail-data-section-clicker-off" href="#"></a>');
					templatein.text(data[i]['name']);
					templatein.attr('data-categoryId', data[i]['categoryId']);
					if (data[i]['leaf'] == 1) {
						var temple = jQuery('<input class="ebayaffinity-radios" name="ebayCategoryId" autocomplete="off" type="radio">');
						temple.attr('value', data[i]['categoryId']);
						temple.attr('id', 'ebayaffinity_radio_' + data[i]['categoryId']);
						templatein.append(temple);
						templatein.attr('data-leaf', 1);
						templatein.removeAttr('href');
						templatein.addClass('ebayaffinity-inv-detail-data-section-clicker-no');
						temple.css('margin-right', '37px');
						temple.css('margin-top', '34px');
						temple.css('float', 'right');
					}
					template.append(templatein);
				}
			}
			if (j == 0) {
				template.text('No results found.');
			}
			if (jQuery('.ebayaffinity-settingspages').length > 0) { 
				jQuery('.ebayaffinity-settingspages').last().after(template);
			} else {
				jQuery('.ebayaffinity-ebayaffinity-header').eq(0).after(template);
			}
			
			
			jQuery('.ebayaffinity-radios').unbind();
			jQuery('.ebayaffinity-radios').change(function() {
				jQuery('.ebayaffinity-radios:checked').each(function() {
					jQuery('#ebayaffinity-confirmselected').css('display', 'block');
					jQuery('#ebayaffinity-cancel').css('right', '');
					jQuery('#submitnow input').attr('value', jQuery(this).attr('value'));
				});
			});
			
			template = jQuery('<div class="ebayaffinity-header ebayaffinity-header-some-selected" style="display: block;" data-items-ajax="1">'+
							'<div id="ebayaffinity-cancel" style="right: 0;">Cancel</div>'+
							'<div id="ebayaffinity-confirmselected" style="display: none;">Confirm selected <strong>&#x2713;</strong></div>'+
						'</div>');
			
			if (ebayaffinity_ajax_invmode == 0) {
				template.prepend(prod_template);
			}
			
			jQuery('.ebayaffinity-header').eq(0).after(template);
			
			jQuery('.ebayaffinity-prod-box-header').click(function() {
				jQuery(this).closest('.ebayaffinity-header').css('overflow', 'visible');
				if (jQuery(this).closest('.ebayaffinity-prod-box').hasClass('ebayaffinity-prod-box-closed')) {
					jQuery(this).closest('.ebayaffinity-prod-box').removeClass('ebayaffinity-prod-box-closed');
					jQuery(this).closest('.ebayaffinity-prod-box').addClass('ebayaffinity-prod-box-opened');
				} else {
					jQuery(this).closest('.ebayaffinity-prod-box').addClass('ebayaffinity-prod-box-closed');
					jQuery(this).closest('.ebayaffinity-prod-box').removeClass('ebayaffinity-prod-box-opened');
				}
				return false;
			});
			
			jQuery('.ebayaffinity-prod-box .ebayaffinity-bt-delete').click(function() {
				var newprods = parseInt(jQuery('.ebayaffinity-prod-box-header').text().split(' ')[0], 10) - 1;
				if (newprods == 1) {
					jQuery('.ebayaffinity-prod-box-header').text(newprods + ' product selected');
				} else {
					jQuery('.ebayaffinity-prod-box-header').text(newprods + ' products selected');
				}
				if (ebayaffinity_ajax_catmode == 1) {
					if (newprods == 1) {
						jQuery('.ebayaffinity-prod-box-header').text(newprods + ' category selected');
					} else {
						jQuery('.ebayaffinity-prod-box-header').text(newprods + ' categories selected');
					}
				}
				
				if (newprods == 0) {
					jQuery('#ebayaffinity-cancel').css('right', '0');
					jQuery('#ebayaffinity-confirmselected').remove();
				}
				
				jQuery('#submitnow').find('input[name=ebayaffinity_prodcats\\[' + jQuery(this).closest('.ebayaffinity-product-item').attr('data-product-id') + '\\]]').remove();
				jQuery('#submitnow').find('input[name=ebayaffinity_catcats\\[' + jQuery(this).closest('.ebayaffinity-product-item').attr('data-product-id') + '\\]]').remove();
				
				jQuery('#ebayaffinity_cat_id_' + jQuery(this).closest('.ebayaffinity-product-item').attr('data-product-id') + ':checked').prop('checked', false);
				jQuery('#ebayaffinity-checkboxall:checked').prop('checked', false);
				
				jQuery(this).closest('.ebayaffinity-product-item').remove();
			});
			
			if (ebayaffinity_ajax_catmode == 1) {
				jQuery('.ebayaffinity-prod-box-edit').click(function() {
					jQuery('.ebayaffinity-header-some-selected-old').addClass('ebayaffinity-header-some-selected');
					jQuery('.ebayaffinity-header-some-selected-old').addClass('ebayaffinity-header');
					jQuery('.ebayaffinity-header-some-selected-old').removeClass('ebayaffinity-header-some-selected-old');
					jQuery('.ebayaffinity-header[data-items-ajax=1]').remove();
					jQuery('#submitnow').remove();
					if (jQuery('.ebayaffinity-checkbox:checked').length > 0) {
						jQuery('.ebayaffinity-header-some-selected').css('display', 'block');
					} else {
						jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
					}
					jQuery('.ebayaffinity-ajax-inv-block').each(function() {
						if (jQuery(this).css('display') == 'none') {
							jQuery(this).css('display', 'block');
						} else {
							jQuery(this).remove();
						}
					});
					return false;
				});
			} else {
				jQuery('.ebayaffinity-prod-box-edit').click(function() {
					ebayaffinityPullItemsAjax();
					return false;
				});
			}
			
			jQuery('#ebayaffinity-confirmselected').click(function() {
				jQuery('#submitnow').submit();
			});
				
			jQuery('#ebayaffinity-cancel').click(function() {
				if (jQuery('.ebayaffinity-prod-box-edit').length == 0) {
					jQuery('.ebayaffinity-ajax-inv-block').remove();
					jQuery('.ebayaffinity-big-error').css('display', '');
					jQuery('.ebayaffinity-inv-detail').css('display', '');
					jQuery('.ebayaffinity-header-some-selected').remove();
				} else {
					jQuery('.ebayaffinity-prod-box-entries .ebayaffinity-bt-delete').click();
					jQuery('.ebayaffinity-checkbox:checked').prop('checked', false);
					jQuery('#ebayaffinity-checkboxall:checked').prop('checked', false);
					jQuery('.ebayaffinity-prod-box-edit').click();
					jQuery('#submitnow').remove();
				}
				return false;
			});
			
			jQuery('.ebayaffinity-inv-detail-data-section-clicker-cat:not([data-leaf])').click(function() {
				var catobj = jQuery(this);
				if (jQuery(this).attr('data-notpop') == 1) {
					catobj.removeAttr('data-notpop');
					jQuery.ajax({
						method: "POST",
						url: ajaxurl,
						dataType: 'json',
						data: { 
								action: 'ebaysubcategories', 
								id: jQuery(this).attr('data-categoryId')
						},
						success: function(data) {
							catobj.removeClass('ebayaffinity-inv-detail-data-section-clicker-off');
							catobj.addClass('ebayaffinity-inv-detail-data-section-clicker-on');
							var template = jQuery('<div style="display: none;"></div>');
							template.attr('id', 'ebayaffinity_catops_' + catobj.attr('data-categoryId'));
							for (i in data) {
								if (data.hasOwnProperty(i)) {
									var templatein = jQuery('<div class="ebayaffinity-catopt"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="50"><input type="radio" class="ebayaffinity-radios" name="ebayCategoryId" autocomplete="off"></td><td style="width: auto;"><label></label></td></tr></table></div>');
									templatein.find('input').attr('value', data[i]['categoryId']);
									templatein.find('input').attr('id', 'ebayaffinity_radio_' + data[i]['categoryId']);
									templatein.find('label').text(data[i]['catname']);
									var htmlcatname = templatein.find('label').html();
									htmlcatname = htmlcatname.replace(/&gt;/g, '<span class="ebayaffinity-rightblue">&#x203a;</span>') + '<span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span>';
									templatein.find('label').html(htmlcatname);
									templatein.find('label').attr('for', 'ebayaffinity_radio_' + data[i]['categoryId']);
									template.append(templatein);
								}
							}
							catobj.after(template);
							
							jQuery('#ebayaffinity_catops_' + catobj.attr('data-categoryId')).slideDown();
							
							jQuery('.ebayaffinity-radios').unbind();
							jQuery('.ebayaffinity-radios').change(function() {
								jQuery('.ebayaffinity-radios:checked').each(function() {
									jQuery('#ebayaffinity-confirmselected').css('display', 'block');
									jQuery('#ebayaffinity-cancel').css('right', '');
									jQuery('#submitnow input').attr('value', jQuery(this).attr('value'));
								});
							});
						}
					});
				} else {
					if (catobj.hasClass('ebayaffinity-inv-detail-data-section-clicker-on')) {
						catobj.removeClass('ebayaffinity-inv-detail-data-section-clicker-on');
						catobj.addClass('ebayaffinity-inv-detail-data-section-clicker-off');
						jQuery('#ebayaffinity_catops_' + catobj.attr('data-categoryId')).slideUp();
					} else {
						catobj.removeClass('ebayaffinity-inv-detail-data-section-clicker-off');
						catobj.addClass('ebayaffinity-inv-detail-data-section-clicker-on');
						jQuery('#ebayaffinity_catops_' + catobj.attr('data-categoryId')).slideDown();
					}
				}
				return false;
			});
		}
	});
}

function ebayaffinitySetProductsToConfirm(data) {
	var templateform = jQuery('<form id="submitnow" method="post" "action="admin.php?page=ebay-sync-mapping&amp;cat2prod=1"></form>');
	for (i in data) {
		if (data.hasOwnProperty(i)) {
			var template = jQuery('<input type="hidden" class="ebayaffinity_prodcats"></input>');
			template.attr('name', 'ebayaffinity_prodcats[' + data[i][0] + ']');
			template.attr('value', data[i][1]);
			if (ebayaffinity_ajax_catmode == 1) {
				template.attr('class', 'ebayaffinity_catcats');
				template.attr('name', 'ebayaffinity_catcats[' + data[i][0] + ']');
			}
			templateform.append(template);
		}
	}
	jQuery('body').append(templateform);
	jQuery('#submitnow').submit();
}

function ebayaffinitySetProductsShipRule(data) {
	/*
		Ok, so I don't comment enough, but this should return an array of products with ids and titles, 
		while any other useful values should still be set ^^^ above. Phew!
	*/
	
	jQuery('.ebayaffinity-titleopt').css('display', '');
	
	for (i in data) {
		if (data.hasOwnProperty(i)) {
			jQuery('.ebayaffinity-product-item[data-product-id=' + data[i][0] + ']').remove();
			jQuery('#ebayaffinity_prodshiprule_' + data[i][0]).remove();
			var template = jQuery('<input type="hidden" class="ebayaffinity_prodshiprules"></input>');
			template.attr('data-name', data[i][1]);
			template.attr('id', 'ebayaffinity_prodshiprule_' + data[i][0]);
			template.attr('name', 'ebayaffinity_prodshiprule[' + data[i][0] + ']');
			template.attr('data-length', data[i][1].length);
			template.attr('value', ebayaffinity_shiprulenum);
			jQuery('.ebayaffinity-settingsblock, .ebayaffinity-titleopt').eq(0).before(template);
			
			template = jQuery('<div class="ebayaffinity-product-item">'+
						'<div class="ebayaffinity-bt-delete">'+
							' &times; '+
						'</div>'+
						'<span class="ebayaffinity-product-unit ebayaffinity-product-leaf"></span>'+
					'</div>');
					
			template.attr('data-product-id', data[i][0]);
			template.find('span').text(data[i][1]);
			
			jQuery('.ebayaffinity-setting-details-product-container[data-id=' + ebayaffinity_shiprulenum + '] .ebayaffinity-products-applied-to').append(template);
		}
	}
	
	jQuery('.ebayaffinity-product-none').closest('.ebayaffinity-product-item').remove();
	
	jQuery('.ebayaffinity-products-applied-to').each(function() {
		if (jQuery(this).find('.ebayaffinity-product-item').length == 0) {
			jQuery(this).html('<div class="ebayaffinity-product-item">'+
						'<span class="ebayaffinity-category-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span>'+
					'</div>');
		}
		if (jQuery(this).find('.ebayaffinity-product-andmore').length > 0) {
			var t = jQuery(this).find('.ebayaffinity-product-andmore').closest('.ebayaffinity-product-item');
			jQuery(this).append(t);
		}
	});
	ebayRemainingAll();
}

function ebayaffinityDisplayItemsAjax(dataset) {
	var data = dataset;
	var templatetop;
	var templatetopmap;
	
	var pages = parseInt(Math.ceil(data[1] / 10), 10);

	if (ebayaffinity_ajax_title != 'Customise product title') {
		jQuery('.ebayaffinity-header:not([data-items-ajax])').css('display', 'none');
	}
	jQuery('.ebayaffinity-settingspages:not([data-items-ajax])').css('display', 'none');
	jQuery('.ebayaffinity-header[data-items-ajax]').remove();
	jQuery('.ebayaffinity-ajax-inv-block').remove();
	jQuery('.ebayaffinity-settingspages-top-force').css('display', 'block');
	
	var template = jQuery('<div class="ebayaffinity-header ebayaffinity-header-none-selected" data-items-ajax="1">'+
			'<div id="ebayaffinity-cancel-2" style="right: 0; display: none;">Cancel</div>'+
			'<a class="ebayaffinity-filter" href="#"></a>'+
			'<div class="ebayaffinity-search">'+
				'<form action="admin.php" autocomplete="off">'+
					'<input placeholder="Search for a product" name="s" value="" id="ebayaffinity-ajax-s" type="text">'+
				'</form>'+
			'</div>'+
			'<span style=""></span>'+
			'<em style=""></em>'+
		'</div>');
	template.find('span').text(ebayaffinity_ajax_title);
	if (ebayaffinity_ajax_title == 'Customise product title' || ebayaffinity_ajax_title == 'Shipping Rules') {
		template.addClass('ebayaffinity-header-none-selected-cancel');
		template.find('span, em').css('display', 'none');
		//template.find('.ebayaffinity-search').css('float', 'none');
		template.find('#ebayaffinity-cancel-2').css('display', 'block');
	}
	
	if (ebayaffinity_ajax_title == 'Mapping') {
		template.append('<div><a href="admin.php?page=ebay-sync-mapping&amp;cat2cat=1" class="ebayaffinity-map-category">Category view</a></div>');
		if (jQuery('.ebayaffinity-map-category-on').length > 0) {
			template.find('.ebayaffinity-map-category').addClass('ebayaffinity-map-category-on');
		}
		template.append('<div><a href="admin.php?page=ebay-sync-mapping&amp;cat2prod=1" class="ebayaffinity-map-product">Product view</a></div>');
		if (jQuery('.ebayaffinity-map-product-on').length > 0) {
			template.find('.ebayaffinity-map-product').addClass('ebayaffinity-map-product-on');
		}
		template.find('.ebayaffinity-search').addClass('ebayaffinity-search-mapping')
	}
	
	for (var i in data[2]) {
		if (data[2].hasOwnProperty(i)) {
			var opt = jQuery('<option></option>');
			opt.attr('value', i);
			opt.text(data[2][i]);
			if (i == ebayaffinity_ajax_categoryId) {
				opt.attr('selected', 'selected');
			}
			
			template.find('select').append(opt);
		}
	}
	
	var counte = data[1];
	if (ebayaffinity_ajax_exkey == 'prodtwosets') {
		counte = parseInt(dataset[0][1], 10) + parseInt(dataset[1][1], 10);
	}
	if (counte == 1) {
		template.find('em').text(counte + ' product');
	} else {
	 	template.find('em').text(counte + ' products');
	}

	template.find('input').attr('value', ebayaffinity_ajax_s);
	
	if (ebayaffinity_ajax_title != 'Customise product title') {
		jQuery('#wpbody-content > *:visible:not(.clear)').attr('data-items-ajax-showafter', '1');
		jQuery('.ebayaffinity-inv-filter').removeAttr('data-items-ajax-showafter');
		jQuery('div[data-items-ajax-showafter]').css('display', 'none');
	}
	
	if (ebayaffinity_ajax_title != 'Customise product title') {
		jQuery('.ebayaffinity-header').eq(0).after(template);
	}

	if (ebayaffinity_ajax_title == 'Mapping') {
		template = jQuery('<div class="ebayaffinity-header ebayaffinity-header-some-selected" style="display: none;" data-items-ajax="1">'+
				'<div id="ebayaffinity-cancel">Cancel</div>'+
				'<div id="ebayaffinity-confirmselected">Map to eBay category <strong class="ebay-affinity-rot">&#x221f;</strong></div>'+
			'</div>');
	} else {
		template = jQuery('<div class="ebayaffinity-header ebayaffinity-header-some-selected" style="display: none;" data-items-ajax="1">'+
				'<div id="ebayaffinity-cancel">Cancel</div>'+
				'<div id="ebayaffinity-confirmselected">Confirm selected <strong>&#x2713;</strong></div>'+
			'</div>');
	}
		
	jQuery('.ebayaffinity-settingspages-top-force').css('display', 'block');
	if (ebayaffinity_ajax_title != 'Customise product title') {
		jQuery('.ebayaffinity-header').eq(0).after(template);
	}
	
	if (data[0].length > 0) {
		if (ebayaffinity_ajax_title != 'Customise product title') {
			template = jQuery('<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">'+
					'<div class="ebayaffinity-ajax-header">Select a product</div>'+
					'<table class="ebayaffinity-inv-table">'+
						'<tr>'+
							'<td><input type="checkbox" autocomplete="off" name="all" value="1" id="ebayaffinity-checkboxall"></td>'+
							'<td colspan="2" class="ebayaffinity-td-prod-title">&nbsp;</td>'+
						'</tr>'+
					'</table>'+
				'</div>');
		} else {
			template = jQuery('<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">'+
					'<a id="ebayaffinity-cancel-3" href="#">Cancel</a>'+
					'<div class="ebayaffinity-ajax-header">Select a product</div>'+
					'<form action="admin.php" autocomplete="off"><input placeholder="Search for a product" name="s" value="" id="ebayaffinity-ajax-s" type="text"></form>'+
					'<table class="ebayaffinity-inv-table">'+
					'</table>'+
					'<div style="height: 90px; margin: 0; padding: 0; overflow: hidden;">&nbsp;</div>'+
					'<div id="ebayaffinity-confirmselected">Confirm</div>'+
				'</div>');
			template.find('input').val(ebayaffinity_ajax_s);
		}
			
		if (ebayaffinity_ajax_title == 'Mapping') {
			
			template.find('.ebayaffinity-ajax-header').append(jQuery('<div class="ebayaffinity-unmapped-box">'+
					'<label for="ebayaffinity-unmapped">Sort by:</label>'+
					'<select name="ebayaffinity-unmapped" id="ebayaffinity-unmapped" class="ebayaffinity-select">'+
						'<option value="unmapped">Unmapped first</option>'+
						'<option value="mapped">Mapped first</option>'+
					'</select>'+
				'</div>'));
			template.find('select').val(ebayaffinity_ajax_unmapped);
			template.find('.ebayaffinity-td-prod-title').text('WooCommerce product');
			template.find('tr').append(jQuery('<td>Mapped eBay category</td>'));
			template.find('table').addClass('ebayaffinity-inv-table-4');
		}
		
		ebayaffinity_ajax_prodsugg = [];
		
		for (var i in data[0]) {
			if (data[0].hasOwnProperty(i)) {
				var templatein = jQuery('<tr>'+
					'<td><input type="checkbox" autocomplete="off" class="ebayaffinity-checkbox" value="1"></td>'+
					'<td></td>'+
					'<td><label></label></td>'+
				'</tr>');
				
				templatein.attr('data-id', data[0][i]['id']);
				templatein.find('input').attr('name', 'id[' + parseInt(data[0][i]['id'], 10) + ']');
				templatein.find('input').attr('data-name', data[0][i]['title']);
				templatein.find('input').attr('data-title', data[0][i]['title']);
				templatein.find('input').attr('id', 'ebayaffinity_product_id_' + parseInt(data[0][i]['id'], 10));
				if (ebayaffinity_ajax_title == 'Mapping') {
					templatein.find('input').attr('data-name', data[0][i]['suggestedCatId']);
					templatein.find('input').attr('data-suggid', data[0][i]['suggestedCatId']);
					templatein.find('input').attr('data-suggtxt', data[0][i]['ebayCategoryName']);
				}
				
				ebayaffinity_ajax_prodsugg[parseInt(data[0][i]['id'], 10)] = [data[0][i]['suggestedCatId'], data[0][i]['ebayCategoryName']];
				templatein.find('td').eq(1).html(data[0][i]['img']);
				templatein.find('td').eq(2).find('label').text(data[0][i]['title']);
				templatein.find('td').eq(2).find('label').attr('for', 'ebayaffinity_product_id_' + parseInt(data[0][i]['id'], 10));
				
				if (ebayaffinity_ajax_title == 'Mapping') {
					var templateinin = jQuery('<td><label></label></td>');
					templateinin.find('label').attr('for', 'ebayaffinity_product_id_' + parseInt(data[0][i]['id'], 10));
					templateinin.find('label').text(data[0][i]['ebayMappedCategoryName']);
					var htmlcatname = templateinin.find('label').html();
					htmlcatname = htmlcatname.replace(/&gt;/g, '<span class="ebayaffinity-rightblue">&#x203a;</span>') + '<span class="ebayaffinity-rightblue" style="visibility: hidden;">&#x203a;</span>';
					templateinin.find('label').html(htmlcatname);
					templatein.append(templateinin);
				}
				
				template.find('table').append(templatein);
			}
		}
		
		if (pages > 1) {
			var ptemplate = jQuery('<nav class="woocommerce-pagination"><ul class="page-numbers"></ul></nav>');
			if (ebayaffinity_ajax_paged > 1) {
				var ttemplate;
				if (ebayaffinity_ajax_title == 'Customise product title') {
					ttemplate = jQuery('<li style="margin-right: 3px;"><a class="prev page-numbers" href="#">&#x221f;</a></li>');
				} else {
					ttemplate = jQuery('<li style="margin-right: 3px;"><a class="prev page-numbers" href="#">&#x2190;</a></li>');
				}
				ttemplate.find('a').attr('data-paged', parseInt(ebayaffinity_ajax_paged, 10) - 1);
				ptemplate.find('ul').append(ttemplate);
			}
			
			for (var n = 1; n <= pages; n += 1) {
				if (ebayaffinity_ajax_paged == n) {
					var ttemplate = jQuery('<li style="margin-right: 3px;"><span class="page-numbers current"></span></li>');
					ttemplate.find('span').text(n);
					ptemplate.find('ul').append(ttemplate);
				} else {
					var ttemplate = jQuery('<li style="margin-right: 3px;"><a class="page-numbers" href="#"></a></li>');
					ttemplate.find('a').attr('data-paged', n);
					ttemplate.find('a').text(n);
					ptemplate.find('ul').append(ttemplate);
				}
			}
		
			if (ebayaffinity_ajax_paged < pages) {
				var ttemplate;
				if (ebayaffinity_ajax_title == 'Customise product title') {
					ttemplate = jQuery('<li style="margin-left: 2px;"><a class="next page-numbers" href="#">&#x221f;</a></li>');
				} else {
					ttemplate = jQuery('<li style="margin-left: 2px;"><a class="next page-numbers" href="#">&#x2192;</a></li>');
				}
				ttemplate.find('a').attr('data-paged', parseInt(ebayaffinity_ajax_paged, 10) + 1);
				ptemplate.find('ul').append(ttemplate);
			}
			template.find('table').after(ptemplate);
		}
	} else {
		if (ebayaffinity_ajax_title == 'Customise product title') {
			template = jQuery('<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">'+
					'<a id="ebayaffinity-cancel-3" href="#">Cancel</a>'+
					'<div class="ebayaffinity-ajax-header">Select a product</div>'+
					'<form action="admin.php" autocomplete="off"><input placeholder="Search for a product" name="s" value="" id="ebayaffinity-ajax-s" type="text"></form>'+
					'<table class="ebayaffinity-inv-table">'+
					'</table>'+
					'0 results found for \'<span class="searchtxt"></span>\''+
				'</div>');
			template.find('input').val(ebayaffinity_ajax_s);
			template.find('.searchtxt').text(ebayaffinity_ajax_s);
		} else {
			template = jQuery('<div class="ebayaffinity-inv-block ebayaffinity-ajax-inv-block">'+
					'No results found.'+
				'</div>');
		}
	}

	if (jQuery('.ebayaffinity-settingspages-top-force').length > 0) {
		jQuery('.ebayaffinity-settingspages-top-force').css('display', 'block');
		jQuery('.ebayaffinity-settingspages-top-force').eq(0).after(template);
	} else {
		if (ebayaffinity_ajax_title == 'Customise product title') {
			template.addClass('ebayaffinity-inv-block-titleopt');
			jQuery('.ebayaffinity-header').eq(0).after(template);
		} else {
			jQuery('.ebayaffinity-header-none-selected[data-items-ajax]').after(template);
		}
	}
	
	jQuery('#ebayaffinity-unmapped').change(function() {
		ebayaffinity_ajax_unmapped = jQuery(this).val();
		ebayaffinityPullItemsAjax();
	});
	
	jQuery('.ebayaffinity-filter, .ebayaffinity-filter-button').unbind();
	jQuery('.ebayaffinity-filter, .ebayaffinity-filter-button').click(function() {
		jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
		jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px');
		jQuery('.ebayaffinity-inv-filter').css('opacity', '0');
		jQuery('.ebayaffinity-inv-filter').css('display', 'block');
		jQuery('.ebayaffinity-inv-filter').animate({'opacity': '1'}, {
			complete: function() {
				jQuery('.ebayaffinity-inv-filter').css('opacity', '');
			},
			duration: '100'
		});
		jQuery('body').bind('click', ebayaffinity_filter_out);
		
		return false;
	});
	
	jQuery('#ebayaffinity-checkboxall').change(function() {
		if (jQuery(this).is(":checked")) {
			jQuery('.ebayaffinity-checkbox').prop('checked', true);
		} else {
			jQuery('.ebayaffinity-checkbox').prop('checked', false);
		}
		
		if (jQuery('.ebayaffinity-checkbox:checked').length > 0) {
			jQuery('.ebayaffinity-header-some-selected').css('display', 'block');
		} else {
			jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
		}
		
		jQuery('.ebayaffinity-prod-box').remove();
		
		var prod_template = jQuery('<div class="ebayaffinity-prod-box">'+
					'<div class="ebayaffinity-prod-box-header"></div>'+
				'</div>');
				
		var j = jQuery('.ebayaffinity-checkbox:checked').length;
		
		if (j > 0) {		
			if (j == 1) {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' product selected');
			} else {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' products selected');
			}
			
			if (ebayaffinity_ajax_catmode == 1) {
				if (j == 1) {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' category selected');
				} else {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' categories selected');
				}
			}
			jQuery('.ebayaffinity-header-some-selected').prepend(prod_template);
		}
	});
	
	jQuery('.ebayaffinity-checkbox').change(function() {
		if (jQuery('.ebayaffinity-checkbox:checked').length > 0) {
			jQuery('.ebayaffinity-header-some-selected').css('display', 'block');
			jQuery('.ebayaffinity-inv-block-titleopt #ebayaffinity-confirmselected').css('display', 'block');
		} else {
			jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
			jQuery('.ebayaffinity-inv-block-titleopt #ebayaffinity-confirmselected').css('display', 'none');
		}
		
		if (jQuery('.ebayaffinity-checkbox:not(:checked)').length > 0) {
			jQuery('#ebayaffinity-checkboxall').prop('checked', false);
		} else {
			jQuery('#ebayaffinity-checkboxall').prop('checked', true);
		}
		
		jQuery('.ebayaffinity-prod-box').remove();
		
		var prod_template = jQuery('<div class="ebayaffinity-prod-box">'+
					'<div class="ebayaffinity-prod-box-header"></div>'+
				'</div>');
				
		var j = jQuery('.ebayaffinity-checkbox:checked').length;
		
		if (j > 0) {		
			if (j == 1) {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' product selected');
			} else {
				prod_template.find('.ebayaffinity-prod-box-header').text(j + ' products selected');
			}
			
			if (ebayaffinity_ajax_catmode == 1) {
				if (j == 1) {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' category selected');
				} else {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' categories selected');
				}
			}
			jQuery('.ebayaffinity-header-some-selected').prepend(prod_template);
		}
	});

	jQuery('#ebayaffinity-ajax-s').closest('form').submit(function() {
		ebayaffinity_ajax_paged = 1;
		ebayaffinity_ajax_s = jQuery('#ebayaffinity-ajax-s').attr('value');
		
		ebayaffinity_ajax_catslugs = [];
		
		jQuery('.ebayaffinity-inv-filter-block .ebayaffinity-inv-filter-cat input:checked').each(function() {
			ebayaffinity_ajax_catslugs.push(jQuery(this).attr('value'));
		});
		
		ebayaffinity_ajax_pricemin = jQuery('.ebayaffinity-inv-filter-block input[name=pricemin]').val();
		ebayaffinity_ajax_pricemax = jQuery('.ebayaffinity-inv-filter-block input[name=pricemax]').val();
		
		ebayaffinity_ajax_showunblocked = jQuery('.ebayaffinity-inv-filter-block input[name=showunblocked]').val();
		ebayaffinity_ajax_showblocked = jQuery('.ebayaffinity-inv-filter-block input[name=showblocked]').val();
		
		ebayaffinity_ajax_showneedsmapping = jQuery('.ebayaffinity-inv-filter-block input[name=showneedsmapping]').val();
		ebayaffinity_ajax_shownotitleopt = jQuery('.ebayaffinity-inv-filter-block input[name=shownotitleopt]').val();
		ebayaffinity_ajax_showerrors = jQuery('.ebayaffinity-inv-filter-block input[name=showerrors]').val();
		
		ebayaffinity_ajax_sorder = jQuery('.ebayaffinity-inv-filter-block select[name=order]').val();
		
		ebayaffinityPullItemsAjax();
		return false;
	});
	
	jQuery('#ebayaffinity-cancel-3').click(function() {
		jQuery('.ebayaffinity-inv-block-titleopt').remove();
		return false;
	});
	
	jQuery('#ebayaffinity-cancel, #ebayaffinity-cancel-2').click(function() {
		jQuery('.ebayaffinity-checkbox:checked').click();
		jQuery('#ebayaffinity-checkboxall:checked').click();
		if (ebayaffinity_ajax_title != 'Mapping') {
			jQuery('#ebayaffinity-confirmselected').click();
		}
		return false;
	});
	
	jQuery('#ebayaffinity-confirmselected').click(function() {
		var arr = [];
		jQuery('.ebayaffinity-checkbox:checked').each(function() {
			arr.push([
				parseInt(jQuery(this).attr('name').replace('id[', '').replace(']', ''), 10),
				jQuery(this).attr('data-name'),
				jQuery(this).attr('data-title')
			]);
		});
		jQuery('.ebayaffinity-ajax-inv-block, .ebayaffinity-header-none-selected, .ebayaffinity-header-some-selected').remove();
		jQuery('div[data-items-ajax-showafter]').css('display', '');
		jQuery('.ebayaffinity-header:not([data-items-ajax])').css('display', 'block');
		jQuery('.ebayaffinity-settingspages').css('display', 'block');
		ebayaffinity_ajax_s = '';
		ebayaffinity_ajax_callback(arr);
	});
	
	jQuery('#ebayaffinity-remap').click(function() {
		var arr = [];
		jQuery('.ebayaffinity-checkbox:checked').each(function() {
			arr.push([
				parseInt(jQuery(this).attr('name').replace('id[', '').replace(']', ''), 10),
				jQuery(this).attr('data-name'),
				jQuery(this).attr('data-title')
			]);
		});
		jQuery('.ebayaffinity-ajax-inv-block, .ebayaffinity-header-none-selected, .ebayaffinity-header-some-selected').remove();
		jQuery('div[data-items-ajax-showafter]').css('display', 'block');
		jQuery('.ebayaffinity-header:not([data-items-ajax])').css('display', 'block');
		jQuery('.ebayaffinity-settingspages').css('display', 'block');
		ebayaffinity_ajax_remap_callback(arr);
	});
	
	jQuery('.ebayaffinity-checkbox').each(function() {
		if (!jQuery(this).is(":checked")) {
			if (jQuery('#submitnow').find('input[name=ebayaffinity_prodcats\\[' + parseInt(jQuery(this).attr('name').replace('id[', '').replace(']', ''), 10) + '\\]]').length > 0) {
				jQuery(this).click();
			}
		}
	});
	
	jQuery('#submitnow').remove();
}

var ebayaffinityPullItemsAjax_event;

function ebayaffinityPullItemsAjax() {
	
	try {
		ebayaffinityPullItemsAjax_event.abort();
	} catch (e) {
		//
	}
	
	if (ebayaffinity_ajax_success != '') {
		var template = jQuery('<div class="ebayaffinity-ajax-success"></div>');
		
		template.html(ebayaffinity_ajax_success);
		jQuery('#wpbody-content').prepend(template);
		setTimeout(function() {
			jQuery('.ebayaffinity-ajax-success').animate({'opacity': '0'}, {
				complete: function() {
					jQuery(this).remove();
				},
				duration: '100'
			});
		}, 1000);
	}
	ebayaffinity_ajax_success = '';
	
	ebayaffinityPullItemsAjax_event = jQuery.ajax({
			method: "POST",
			url: ajaxurl,
			dataType: 'json',
			data: { 
					action: 'ebayproducts', 
					paged: ebayaffinity_ajax_paged, 
					s: ebayaffinity_ajax_s, 
					pricemin: ebayaffinity_ajax_pricemin,
					pricemax: ebayaffinity_ajax_pricemax, 
					showunblocked: ebayaffinity_ajax_showunblocked, 
					showblocked: ebayaffinity_ajax_showblocked, 
					showneedsmapping: ebayaffinity_ajax_showneedsmapping, 
					shownotitleopt: ebayaffinity_ajax_shownotitleopt, 
					showerrors: ebayaffinity_ajax_showerrors, 
					catslugs: ebayaffinity_ajax_catslugs,
					sorder: ebayaffinity_ajax_sorder,
					categoryId: ebayaffinity_ajax_categoryId, 
					exkey: ebayaffinity_ajax_exkey, 
					exvalue: ebayaffinity_ajax_exvalue,
					key: ebayaffinity_ajax_key, 
					value: ebayaffinity_ajax_value,
					unmapped: ebayaffinity_ajax_title == 'Mapping' ? ebayaffinity_ajax_unmapped : '',
					filteroutprods: ebayaffinity_ajax_filteroutprods
			},
			success: ebayaffinityDisplayItemsAjax
	});
}

function ebayaffinityFixHeights() {
	var wh = jQuery(window).height();
	var la = jQuery('.ebayaffinity-all');

	la.css('height', '');
	if (la.height() < wh) {
		la.css('height', wh + 'px');
	}
	
	jQuery('.ebayaffinity-block').css('height', '');
	
	jQuery('.ebayaffinity-row').each(function() {
		var h = 0;
		var lb = jQuery(this).find('.ebayaffinity-block');
		lb.each(function() {
			var th = jQuery(this).height();
			if (th > h) {
				h = th;
			}
		});
		lb.css('height', h + 'px');
	});
	
	setTimeout('ebayaffinityFixHeights()', 100);
}

function ebayaffinityMakeCharts() {
	jQuery('.ebayaffinity-pie-chart').each(function() {
		var canvas = jQuery('<canvas width="450" height="450"></canvas>');
		jQuery(this).append(canvas);
		var canvas2 = jQuery(this).find('canvas');
		canvas2.css('height', canvas2.width() + 'px');
		jQuery(window).resize(function() {
			canvas2.css('height', canvas2.width() + 'px');
		});
		
		var c = jQuery(this).find('canvas')[0];
		var context = c.getContext('2d');

		var percent = 0;
		var isPercent = false;
		var empty = false;
		
		if (jQuery(this)[0].hasAttribute('data-percent')) {
			percent = ((jQuery(this).attr('data-percent') / 100) * (Math.PI * 2)) + (Math.PI * 1.5);
			isPercent = true;
			if (jQuery(this).attr('data-percent') == 0) {
				empty = true;
			}
		} else {
			percent = ((jQuery(this).attr('data-number') / jQuery(this).attr('data-total')) * (Math.PI * 2)) + (Math.PI * 1.5);
			if (jQuery(this).attr('data-number') == 0) {
				empty = true;
			}
		}

		context.clearRect(0, 0, c.width, c.height);
		
		var gradient = context.createRadialGradient(c.width / 2, c.height / 2, c.width / 2 - 65, c.height / 2, c.width / 2, 0);
		gradient.addColorStop(0, '#eeeeee');
		gradient.addColorStop(1, '#000000');
		
		if (empty) {
			context.beginPath();
		    context.arc(c.width / 2, c.height / 2, (c.width / 2) - 38, 0, 2 * Math.PI, true);
		    context.lineWidth = 76;
			context.strokeStyle = gradient;
		    context.stroke();
	    } else {
		    context.beginPath();
		    context.arc(c.width / 2, c.height / 2, (c.width / 2) - 38, Math.PI * 1.5, percent, true);
		    context.lineWidth = 76;
		    context.strokeStyle = gradient;
		    context.stroke();
		    gradient = context.createRadialGradient(c.width / 2, c.height / 2, c.width / 2 - 65, c.height / 2, c.width / 2, 0);
			gradient.addColorStop(0, jQuery(this).attr('data-colour'));
			gradient.addColorStop(1, '#000000');
	    	
		    context.beginPath();
		    context.arc(c.width / 2, c.height / 2, (c.width / 2) - 38, Math.PI * 1.5, percent, false);
		    context.lineWidth = 76;
		    context.strokeStyle = gradient;
		    context.stroke();
	    }
	    
	    
	    context.fillStyle = jQuery(this).attr('data-colour');
	    context.strokeStyle = jQuery(this).attr('data-colour');
	    
	    if (jQuery(this).attr('data-number') > 99999999) {
	    	context.font = "40px sans-serif";
	    } else if (jQuery(this).attr('data-number') > 9999999) {
	    	context.font = "50px sans-serif";
		} else if (jQuery(this).attr('data-number') > 999999) {
	    	context.font = "60px sans-serif";
	    } else if (jQuery(this).attr('data-number') > 99999) {
	    	context.font = "80px sans-serif";
	    } else if (jQuery(this).attr('data-number') > 9999) {
	    	context.font = "100px sans-serif";
	    } else {
	    	context.font = "120px sans-serif";
	    }
	    context.textAlign = 'center';
	    context.textBaseline = 'middle';
	    
	    if (isPercent) {
	    	//alert(jQuery(this).attr('data-percent'));
  			context.fillText(jQuery(this).attr('data-percent'), (c.width / 2), (c.height / 2));
  			
  			context.font = "38px sans-serif";
  			if (jQuery(this).attr('data-percent') > 99) {
  				context.fillText('%', (c.width / 2) + 115, (c.height / 2) - 30);
  			} else if (jQuery(this).attr('data-percent') > 9) {
  				context.fillText('%', (c.width / 2) + 80, (c.height / 2) - 30);
  			} else {
  				context.fillText('%', (c.width / 2) + 50, (c.height / 2) - 30);
  			}
  			
  			context.fillStyle = '#777777';
	    	context.strokeStyle = '#777777';
	    	context.font = "28px sans-serif";
	    	
	    	if (jQuery(this).attr('data-msg') == '1') {
	    		context.fillText('Store setup', (c.width / 2), (c.height / 2) - 77);
	    		context.fillText('complete', (c.width / 2), (c.height / 2) + 77);
	    	}
	    } else {
	    	context.fillText(jQuery(this).attr('data-number'), (c.width / 2), (c.height / 2));
	    }
	});
	
	ebayaffinityMakeBarCharts();
}

function ebayaffinityColourScale(val) {
	val = 1 - val;
	var onR = 135;
	var onG = 185;
	var onB = 26;
	var offR = 249;
	var offG = 97;
	var offB = 102;
	
	var r = parseInt(onR + ((offR - onR) * val), 10);
	var g = parseInt(onG + ((offG - onG) * val), 10);
	var b = parseInt(onB + ((offB - onB) * val), 10);
	
	return 'rgb(' + r + ',' + g + ',' + b + ')';
}

function ebayaffinityFixOnOffs() {
	var soo = jQuery('.ebayaffinity-switch-on-off');
	var foo = jQuery('.ebayaffinity-filter-switch-on-off');
	soo.unbind();
	foo.unbind();
	soo.click(function() {
		var thisone = jQuery(this);
		var st = jQuery(document).scrollTop();
		var t = jQuery('<div class="ebayaffinity-header-setup">'+
				'<a href="#" class="ebayaffinity-header-setup-close">&times;</a>'+
				'<span>Change visibility</span>'+
				'<em></em>'+
				'<a href="#" id="ebayaffinity-header-continue">Continue</strong>'+
			'</div>');
			
		if (jQuery(this).hasClass('ebayaffinity-switch-on')) {
			t.find('em').text('Warning. Disabling the visibility for this product will end your listing on eBay. Are you sure you want to proceed?');
		} else {
			t.find('em').text('Warning. Enabling the visibility for this product will create a new listing on eBay. Are you sure you want to proceed?');
		}
			
		t.css('margin-top', st + 'px');
			
		jQuery('#wpbody-content').prepend(t);
	
		jQuery('#wpbody-content').prepend('<div class="ebayaffinity-header-setup-black">&nbsp;</div>');
		
		jQuery('.ebayaffinity-header-setup').css('opacity', '0');
		jQuery('.ebayaffinity-header-setup-black').css('opacity', '0');
		
		jQuery('.ebayaffinity-header-setup').animate({'opacity': '1'}, {
				complete: function() {
					jQuery('.ebayaffinity-header-setup').css('opacity', '');
					jQuery('.ebayaffinity-header-setup-black').css('opacity', '');
				},
				step: function(now, fx) {
					jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
				},
				duration: '100'
		});
		
		jQuery('.ebayaffinity-header-setup-black, .ebayaffinity-header-setup-close').click(function() {
			jQuery('.ebayaffinity-header-setup').animate({'opacity': '0'}, {
					complete: function() {
						jQuery('.ebayaffinity-header-setup').remove();
						jQuery('.ebayaffinity-header-setup-black').remove();
					},
					step: function(now, fx) {
						jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
					},
					duration: '100'
			});
			return false;
		});
	
		jQuery('#ebayaffinity-header-continue').click(function() {
			jQuery('.ebayaffinity-header-setup').animate({'opacity': '0'}, {
				complete: function() {
					jQuery('.ebayaffinity-header-setup').remove();
					jQuery('.ebayaffinity-header-setup-black').remove();
				},
				step: function(now, fx) {
					jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
				},
				duration: '100'
			});
			var parent = jQuery(thisone);
			if (jQuery(thisone).hasClass('ebayaffinity-switch-on')) {
				jQuery.ajax({
					method: "POST",
					url: ajaxurl,
					data: { action: 'blockunblock', id: jQuery(thisone).closest('tr').attr('data-id'), blocked: '1' }
				});
				jQuery(thisone).find('div').animate({'margin-left': '0px'}, {
					complete: function() {
						parent.removeClass('ebayaffinity-switch-on');
						parent.addClass('ebayaffinity-switch-off');
						parent.css('background-color', '');
						parent.css('border-color', '');
					},
					step: function(now, fx) {
						var cs = ebayaffinityColourScale((now / 20));
						parent.css('background-color', cs);
						parent.css('border-color', cs);
					},
					duration: '100'
				});
			} else {
				jQuery.ajax({
					method: "POST",
					url: ajaxurl,
					data: { action: 'blockunblock', id: jQuery(thisone).closest('tr').attr('data-id'), blocked: '0' }
				});
				jQuery(thisone).find('div').animate({'margin-left': '20px'}, {
					complete: function() {
						parent.removeClass('ebayaffinity-switch-off');
						parent.addClass('ebayaffinity-switch-on');
						parent.css('background-color', '');
						parent.css('border-color', '');
					},
					step: function(now, fx) {
						var cs = ebayaffinityColourScale((now / 20));
						parent.css('background-color', cs);
						parent.css('border-color', cs);
					},
					duration: '100'
				});
			}
			return false;
		});
		return false;
	});
	
	foo.click(function() {
		var parent = jQuery(this);
		if (jQuery(this).hasClass('ebayaffinity-filter-switch-on')) {
			jQuery(this).closest('tr').find('input').attr('value', 0);
			if (jQuery('#ebayaffinity-searchcat').length > 0) {
				jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
			}
			jQuery('#ebayaffinity-inv-filter-paged').val(1);
			jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
			jQuery(this).find('div').animate({'margin-left': '0px'}, {
				complete: function() {
					parent.removeClass('ebayaffinity-filter-switch-on');
					parent.addClass('ebayaffinity-filter-switch-off');
					parent.css('opacity', '0.3');
				},
				step: function(now, fx) {
					parent.css('opacity', ((now / 20) * 0.7) + 0.3);
				},
				duration: '100'
			});
		} else {
			jQuery(this).closest('tr').find('input').attr('value', 1);
			if (jQuery('#ebayaffinity-searchcat').length > 0) {
				jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
			}
			jQuery('#ebayaffinity-inv-filter-paged').val(1);
			jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
			jQuery(this).find('div').animate({'margin-left': '20px'}, {
				complete: function() {
					parent.removeClass('ebayaffinity-filter-switch-off');
					parent.addClass('ebayaffinity-filter-switch-on');
					parent.css('opacity', '');
				},
				step: function(now, fx) {
					parent.css('opacity', ((now / 20) * 0.7) + 0.3);
				},
				duration: '100'
			});
		}
	});
}

function updateRule(cont, html) {
	var id = cont.attr('data-id');

	var catids = [];
	cont.find('.ebayaffinity-category-item').each(function() {
		catids.push(jQuery(this).attr('data-category-id'));
	});
	
	var catstr = catids.join(',');
	var xmlDoc;
	var str = '<rules></rules>';

	if (window.DOMParser) {
		var parser = new DOMParser();
		xmlDoc = parser.parseFromString(str, "text/xml");
	} else {
		xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async = false;
		xmlDoc.loadXML(str);
	}

	cont.find('.ebayaffinity-template-unit').each(function() {
		var xmlNode = xmlDoc.createElement('rule');
		xmlNode.setAttribute('type', jQuery(this).attr('data-type'));
		if (jQuery(this).attr('data-type') == 'string') {
			xmlNode.appendChild(xmlDoc.createTextNode(jQuery(this).find('input').attr('value')));
		} else {
			xmlNode.appendChild(xmlDoc.createTextNode(jQuery(this).attr('data-value')));
		}
		xmlDoc.firstChild.appendChild(xmlNode);
	});
	
	var titleTemplate = '';
	try {
		titleTemplate = new XMLSerializer().serializeToString(xmlDoc.documentElement);
	} catch (e) {
		titleTemplate = xmlDoc.xml;
	}

	jQuery.ajax({
		method: "POST",
		url: ajaxurl,
		data: { action: 'ebaytitlerules_upsertrule', id: id, titleTemplate: titleTemplate, arrEbayCategoriesToApply: catstr },
		success: function(data) {
			if (data == -1) {
				cont.html(html);
				alert('Failed to communicate with server');
			}
		}
	});
}

function updateShipRule(cont, html) {
	var rule = cont.attr('data-id');

	var catids = [];
	cont.find('.ebayaffinity-category-item').each(function() {
		catids.push(jQuery(this).attr('data-category-id'));
	});
	
	var catstr = catids.join(',');

	jQuery.ajax({
		method: "POST",
		url: ajaxurl,
		data: { action: 'ajax_affinity_category_shiprule', id: id, rule: rule },
		success: function(data) {
			if (data == -1) {
				cont.html(html);
				alert('Failed to communicate with server');
			}
		}
	});
}

// bar chart
var selectedBarChartPeriod = 'week';

function setBarChartPeriod(period) {
	jQuery('.ebayaffinity-chart-period').each(function(index) {
		var loopPeriod = jQuery(this).attr('data-period');
		if(loopPeriod) {
			if(loopPeriod == period) {
				if((selectedBarChartPeriod != period) || !jQuery(this).hasClass('selected-period')) {
					jQuery(this).addClass('selected-period');
					selectedBarChartPeriod = period;
					jQuery('.ebayaffinity-bar-chart-body[data-type='+loopPeriod+']').addClass('ebayaffinity-bar-chart-body-showing');
				}
			}
			else {
				jQuery('.ebayaffinity-bar-chart-body[data-type='+loopPeriod+']').removeClass('ebayaffinity-bar-chart-body-showing');
				jQuery(this).removeClass('selected-period');
			}
		}
	});
}

function ebayaffinityMakeBarCharts() {
	jQuery('.ebayaffinity-bar-chart-body').each(function() {
		var yAxisRange = 1;
		var yAxisValueCount = 7;
		var yAxisValues = [];
		var lstep = 1;
		if (jQuery(this).attr('data-type') == 'today') {
			var barValues = affinity_dashboard_hour_data;
			lstep = 2;
		} else if (jQuery(this).attr('data-type') == 'week') {
			var barValues = affinity_dashboard_day_data.slice(-14);
			barValues = barValues.slice(0, 7);
		} else if (jQuery(this).attr('data-type') == 'month') {
			var barValues = affinity_dashboard_day_data.slice(-38);
			barValues = barValues.slice(0, 31);
			lstep = 1;
		} else if (jQuery(this).attr('data-type') == 'year') {
			var barValues = affinity_dashboard_month_data;
			barValues = barValues.slice(-12);
		}
		var maxVal = 0;
		for(var i = 0; i < barValues.length; i++) {
			if (parseInt(barValues[i].value, 10) > parseInt(maxVal, 10)) {
				maxVal = parseInt(barValues[i].value, 10);
			}
		}
		
		yAxisRange = Math.ceil((maxVal/5)/5) * 5;
		if (yAxisRange < 5) {
			yAxisRange = 1;
		}
		if (maxVal <= 5) {
			yAxisRange = 1;
		}
	
		for(var i = 0, j = 0; i < yAxisValueCount; i++) {
			yAxisValues[i] = j;
			j += yAxisRange;
		}
	
		var canvas = jQuery('<canvas style="display:block;"></canvas>');
		jQuery(this).append(canvas);
		var c = jQuery(this).find('canvas')[0];
		c.width = 1240
		c.height = 720;
		var context = c.getContext('2d');
		var chartWidth = 0;
		var chartHeight = 0;
		var chartOffsetX = 50;
		var chartOffsetY = 100;
		var yAxisLabelOffsetX = 5;
		var yAxisLabelOffsetY = 5;
		chartWidth = (c.width - chartOffsetX) - 20;
		chartHeight = c.height - chartOffsetY;
		
			// draw y axis
	    for(var i = 0 ; i < (yAxisValues.length + 1); i++) {
	    	if((0 < i) && (i < yAxisValues.length)) {
	    		var posX1 = chartOffsetX;
	    		var posX2 = posX1 + chartWidth;
	    		var posY = chartHeight - (chartHeight / yAxisValueCount * i);
		    	
			    context.beginPath();
			    try {
			    	context.setLineDash([5]);
			    } catch (f) {
			    	//
			    }
			    context.moveTo(posX1, posY);
			    context.lineTo(posX2, posY);
			    context.strokeStyle = '#e0e6e8';
			    context.stroke();
			    
			    context.font = "28.58px sans-serif";
			    context.fillStyle = "#a8a8a8";
			    context.fillText(yAxisValues[i], yAxisLabelOffsetX, posY + yAxisLabelOffsetY);
	    	}
	    }
	    
	    chartHeight = c.height - chartOffsetY - 20;
	    
	    var xtra = 0;
	    
	    // draw bar
	    var rects = [];
	    for(var i = 0 ; i < barValues.length; i++) {
	    	if (i > 0) {
		    	if (jQuery(this).attr('data-type') == 'month') {
		    		if (barValues[i].day.substr(0, 1) == 'M') {
		    			xtra += 15;
		    		}
		    	}
	    	}
	    	barWidth = chartWidth / barValues.length;
	    	if (jQuery(this).attr('data-type') == 'month') {
	    		barWidth += -2;
	    	}
	    	
	    	maxBarHeight = chartHeight - (chartHeight / yAxisValueCount);
	    	barHeight = barValues[i].value / yAxisValues[yAxisValues.length - 1] * maxBarHeight;
	    	
	    	var barPosX = chartOffsetX + (i * barWidth) + xtra;
	    	var barPosY = chartHeight - barHeight;
	    	
			var gradient = context.createLinearGradient(barPosX, barPosY, barPosX, barHeight + barPosY);
			gradient.addColorStop(0, '#006ce3');
			gradient.addColorStop(1, '#004fa5');
	    	
	    	context.fillStyle = gradient;
	    	context.globalAlpha = 0.6;
	    	context.fillRect(barPosX + 5, barPosY + 10, barWidth - 5, barHeight);
	    	rects.push([
	    		barValues[i].value,
	    		barPosX + 5, barPosY + 10, barWidth - 5, barHeight,
	    		(barValues[i].dayp === undefined) ? '' : barValues[i].dayp
	    		]);
	    	
	    	if (i % lstep == 0) { 
		    	var dayLabelPosX = barPosX + (barWidth / 2);
		    	var dayLabelPosY = chartHeight + 75;
		    	
			    context.font = "33px sans-serif";
			    if (jQuery(this).attr('data-type') == 'month') {
			    	context.font = "30px sans-serif";
			    }
			    context.fillStyle = "#a8a8a8";
			    context.textAlign = "center"; 
			    if (jQuery(this).attr('data-type') == 'month') {
			    	barValues[i].day = barValues[i].day.substr(0, 1);
			    }
			    context.fillText(barValues[i].day, dayLabelPosX, dayLabelPosY);
			    
			    if (jQuery(this).attr('data-type') != 'year' && jQuery(this).attr('data-type') != 'today') {
				    
				    if (barValues[i].day != barValues[i].date) {
				    	var dateLabelPosX = dayLabelPosX;
				    	var dateLabelPosY = chartHeight + 120;
				    	
					    context.font = "24px sans-serif";
					    context.fillStyle = "#000000";
					    context.textAlign = "center"; 
					    if (jQuery(this).attr('data-type') == 'month') {
				    		if (barValues[i].day.substr(0, 1) == 'T') {
				    			if (i == 0 || barValues[i - 1].day.substr(0, 1) == 'W') {
				    				if (i == 30 || barValues[i + 1].day.substr(0, 1) == 'F') {
						    			var first = '';
						    			var last = '';
						    			try {
						    				for (j in affinity_dashboard_day_data) {
						    					if (affinity_dashboard_day_data.hasOwnProperty(j)) {
						    						if (barValues[i].dayo == affinity_dashboard_day_data[j].dayo) {
						    							first = affinity_dashboard_day_data[j - 2].dayo;
						    							last = affinity_dashboard_day_data[parseInt(j, 10) + 4].dayo;
						    						}
						    					}
						    				}
						    				context.fillText(first + ' - ' + last, dateLabelPosX + 5, dateLabelPosY);
						    			} catch (f) {
						    				
						    			}
						    		}
					    		}
				    		}
				    	} else {
					    	context.fillText(barValues[i].date, dateLabelPosX, dateLabelPosY);
					    }
				    }
				}
		    }
	    }
	    jQuery(this).find('canvas').attr('data-img', jQuery(this).find('canvas')[0].toDataURL("image/png"));
	    jQuery(this).find('canvas').attr('data-rects', JSON.stringify(rects));
	    jQuery(this).find('canvas').mousemove(function (e) {
	    	var ctx = this.getContext('2d');
	    	var canvas = jQuery(this);
	    	
			var img = new Image;

	    	var r = this.getBoundingClientRect();
	    	var fix = parseInt(jQuery(this).width(), 10) / parseInt(jQuery(this).attr('width'), 10);
		    var x = (e.clientX - r.left) / fix;
		    var y = (e.clientY - r.top) / fix;
		    
		    var rects = JSON.parse(jQuery(this).attr('data-rects'));
		    for (var i = 0; i < rects.length; i += 1) {
		    	if (rects[i][4] > 0) {
		    		if (x > rects[i][1] && x < (rects[i][1] + rects[i][3])) {
			    		ctx.fillStyle = '#2F3A4C';
			    		
			    		var sel = rects[i][1];

			    		if (sel != jQuery(this).attr('data-sel')) {
			    			 img.onload = function(){
								ctx.globalAlpha = 1;
								ctx.clearRect(0, 0, c.width, c.height);
								ctx.drawImage(img,0,0);
								jQuery(this).attr('data-sel', sel);
								
								var x1 = rects[i][1] - 5;
			    				var y1 = rects[i][2] - 65;
			    				var w1 = rects[i][3] + 10;
			    				var h1 = 50;
			    				var r1 = 2;
			    				
			    				if (canvas.closest('div').attr('data-type') == 'month') {
			    					var d = 300 - w1;
			    					d = d / 2;
			    					x1 -= d;
			    					w1 = 300;
			    				}
			    				
			    				if (canvas.closest('div').attr('data-type') == 'month') {
			    					h1 += 40;
			    					y1 -= 40;
			    				}
			    				
			    				var xoffs = 0;
			    				if (x1 < 0) {
			    					xoffs = x1;
			    					xoffs *= -1;
			    					x1 = 0;
			    				} else if (x1 + w1 > 1240) {
			    					xoffs = 1240 - (x1 + w1);
			    					x1 = 1240 - w1;
			    				}
			    				
			    				ctx.fillRect(x1, y1, w1, h1);
			    				
			    				ctx.beginPath();
			    				ctx.moveTo((rects[i][1] + (rects[i][3] / 2)) - 10, y1 + h1 - 1);
			    				ctx.lineTo((rects[i][1] + (rects[i][3] / 2)) + 10, y1 + h1 - 1);
			    				ctx.lineTo(rects[i][1] + (rects[i][3] / 2), y1 + h1 + 10);
			    				ctx.closePath();
								ctx.fill();
			    				
			    				ctx.font = "24px sans-serif";
							    ctx.fillStyle = "#3F8CDB";
							    ctx.textAlign = "center";
							    if (canvas.closest('div').attr('data-type') == 'month') {
							    	ctx.fillText(rects[i][0], rects[i][1] + (rects[i][3] / 2) + xoffs, rects[i][2] - 70);
							    	ctx.font = "22px sans-serif";
							    	ctx.fillStyle = "#FFFFFF";
							    	ctx.fillText(rects[i][5], rects[i][1] + (rects[i][3] / 2) + xoffs, rects[i][2] - 32);
			    				} else {
			    					ctx.fillText(rects[i][0], rects[i][1] + (rects[i][3] / 2) + xoffs, rects[i][2] - 32);
			    				}
								
							};
							img.src = jQuery(this).attr('data-img');
							
							jQuery(this).attr('data-sel', sel);
			    		}
			    		
			    		return;
		    		}
		    	}
		    }
		    
		    if (jQuery(this).attr('data-sel') != '') {
				img.onload = function(){
					ctx.globalAlpha = 1;
					ctx.clearRect(0, 0, c.width, c.height);
					ctx.drawImage(img,0,0);
					canvas.attr('data-sel', '');
				};
				img.src = jQuery(this).attr('data-img');
				jQuery(this).attr('data-sel', '');
			}
	    });
	});
}

var minDragging = false;
var minXPos = 0;
var maxDragging = false;
var origLeft = 0;
var origTop = 0;

var affinityXPos = 0;
var affinityDragging = false;

function affinity_titleResize() {
	var c = jQuery('.ebayaffinity-available-attributes').offset().top;
	jQuery('.ebayaffinity-available-attributes .ebayaffinity-attributes-scroll').css('overflow-y', 'auto');
	var b = jQuery(window).scrollTop();
	c -= 40;
	if (b > c) {
		jQuery('.ebayaffinity-available-attributes').css('padding-top', (b - c) + 'px');
		c = jQuery('.ebayaffinity-available-attributes').offset().top;
		c += 20;
		jQuery('.ebayaffinity-available-attributes .ebayaffinity-attributes-scroll').css('height', (jQuery(window).height() - c) + 'px');
	} else {
		c = 280 - b;
		jQuery('.ebayaffinity-available-attributes .ebayaffinity-attributes-scroll').css('height', (jQuery(window).height() - c) + 'px');
		jQuery('.ebayaffinity-available-attributes').css('padding-top', '');
	}
}

function fixeBayPrices() {
	jQuery('.form-field label').each(function() {
		try {
			if (jQuery(this).attr('data-moved') != 1) {
				jQuery(this).attr('data-moved', 1);
				if (jQuery(this).attr('for').substr(0, 11) == '_ebayprices') {
					jQuery(this).before('<div style="height: 0; clear: both; overflow: hidden;">&nbsp;</div>');
					jQuery(this).closest('.woocommerce_variable_attributes').find('.variable_pricing').append(jQuery(this).closest('.form-field'));
				}
			}
		} catch (e) {
			//
		}
	});
	setTimeout('fixeBayPrices()', 200);
}

jQuery(function() {
	jQuery("ul#adminmenu a[href$='ebay-sync-help']").attr('target', '_blank');
	jQuery("ul#adminmenu a[href$='ebay-sync-help']").attr('href', 'https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
	jQuery("ul#adminmenu a[href$='ebay-sync-feedback']").attr('target', '_blank');
	jQuery("ul#adminmenu a[href$='ebay-sync-feedback']").attr('href', 'https://survey.ebay.com/survey/selfserve/1f4b/eci17020');
	
	ebayaffinityFixOnOffs();
	
	jQuery('.ebay-affinity-rule-default-radio').change(function() {
		ebayRemainingAll();
	});
	
	jQuery('.notice-affinity').on('click', '.notice-dismiss', function() {
		jQuery.ajax({
			method: "POST",
			url: ajaxurl,
			data: { action: 'affinitynoticed' }
		});
	});
	
	jQuery('#order_status').change(function() {
		if (jQuery(this).val() == 'wc-completed') {
			jQuery('#_affinity_marked_as_sent').prop('checked', true);
		}
	});
	
	jQuery('#ebayaffinity-inv-filter-form, #affinity-invform').submit(function() {
		jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
		
		var a = jQuery('#ebayaffinity-inv-filter-form').serialize();
		a += '&action=ebayinventoryp';
		
		try {
			ebayaffinityInvFormAjax_event.abort();
		} catch (e) {
			//
		}
		
		ebayaffinityInvFormAjax_event = jQuery.ajax({
				method: "GET",
				url: ajaxurl,
				dataType: 'json',
				data: a,
				success: ebayaffinityInvFormAjax_handle
		});
		
		return false;
	});
	
	jQuery("regergwergwerg").keypress(function (e) {
        // Allow: backspace, delete, tab, escape, enter and .
		alert(e.keyCode);
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             // Allow: Ctrl+A
            (e.keyCode == 65 && e.ctrlKey === true) ||
             // Allow: Ctrl+C
            (e.keyCode == 67 && e.ctrlKey === true) ||
             // Allow: Ctrl+X
            (e.keyCode == 88 && e.ctrlKey === true) ||
             // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                 // let it happen, don't do anything
                 return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            return false;
        } else {
        	return false;
        }
    });
	
	jQuery('ul.page-numbers').on('click', 'a.page-numbers', function() {
		var num = jQuery(this).attr('href').split('&paged=')[1];
		jQuery('#ebayaffinity-inv-filter-paged').val(num);
		jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
		return false;
	});
	
	jQuery('.ebayaffinity-inv-filter-block input, .ebayaffinity-inv-filter-block select').change(function() {
		if (jQuery('#ebayaffinity-searchcat').length > 0) {
			jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
		}
		jQuery('#ebayaffinity-inv-filter-paged').val(1);
		jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
	});
	
	jQuery('select[name=ebayaffinity-perpage]').change(function() {
		if (jQuery('#ebayaffinity-searchcat').length > 0) {
			jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
		}
		jQuery('#ebayaffinity-inv-filter-paged').val(1);
		jQuery('input[name=ebayaffinity-perpage]').val(jQuery('select[name=ebayaffinity-perpage]').val());
		jQuery('input[name=justfiltered]').val(0);
		jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
	});
	
	jQuery('#ebayaffinity-inv-filter-show-more-cat-link').click(function() {
		jQuery('.ebayaffinity-inv-filter-show-more-cat').removeClass('ebayaffinity-inv-filter-show-more-cat');
		jQuery('#ebayaffinity-inv-filter-show-more-cat-link').css('display', 'none');
		jQuery('#ebayaffinity-inv-filter-show-less-cat-link').css('display', 'block');
		jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
		jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px')
		return false;
	});
	
	jQuery('#ebayaffinity-inv-filter-show-less-cat-link').click(function() {
		jQuery('.ebayaffinity-inv-filter-cat').addClass('ebayaffinity-inv-filter-show-more-cat');
		jQuery('.ebayaffinity-inv-filter-cat').slice(0, 4).removeClass('ebayaffinity-inv-filter-show-more-cat');
		jQuery('#ebayaffinity-inv-filter-show-less-cat-link').css('display', 'none');
		jQuery('#ebayaffinity-inv-filter-show-more-cat-link').css('display', 'block');
		jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
		jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px')
		return false;
	});
	
	/*
	if (jQuery('.ebayaffinity-help').length == 0 && jQuery('.ebayaffinity-available-attributes').length > 0) {
		affinity_titleResize();
		jQuery(window).resize(affinity_titleResize);
		jQuery(window).scroll(affinity_titleResize);
	}
	*/
	
	jQuery('.ebayaffinity-available-attributes').on('mousedown', '.ebayaffinity-template-unit', function(e) {
		if (affinityDragging == false) {
			affinityDragging = true;
			origLeft = e.pageX;
			origTop = e.pageY;
			
			var boo = jQuery(this).clone();
			boo.attr('id', 'ebayaffinity-clonedit');
			
			jQuery('body').append(boo);
			
			jQuery('.ebayaffinity-titleopt').unbind('mousemove');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('cursor', '');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('position', '');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('top', '');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('left', '');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('z-index', '');
			jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('opacity', '');
			
			jQuery(this).css('opacity', '0.5');
			
			jQuery('body').mousemove(function(e) {
				if (affinityDragging) {
					jQuery('.ebayaffinity-rule-container .ebayaffinity-template-container').css('background-color', '');
					jQuery('#ebayaffinity-clonedit2').remove();
					
					var left = origLeft;
					left -= e.pageX;
					left *= -1;
					var top = origTop;
					top -= e.pageY;
					top += -1;
					top *= -1;
					boo.css('cursor', 'move');
					boo.css('position', 'absolute');
					boo.css('top', top + 'px');
					boo.css('left', left + 'px');
					
					boo.css('top', (e.pageY - 40) + 'px');
					boo.css('left', (e.pageX - 40) + 'px');
					boo.css('z-index', '20');
					
					top = boo.position().top;
					left = boo.position().left;
					var width = boo.width();
					var height = boo.height();
					
					var area = 0;
					var biggestArea = 0;
					jQuery('.ebayaffinity-rule-container').each(function() {
						if (!jQuery(this).hasClass('ebayaffinity-rule-container-no')) {
							if (jQuery(this).css('z-index') != '20') {
								var a = (jQuery(this).position().top + jQuery(this).height()) - top;
								var b = (top + height) - jQuery(this).position().top;
								var c = (jQuery(this).position().left + jQuery(this).width()) - left;
								var d = (left + width) - jQuery(this).position().left;
								if (a > 0 && b > 0 && c > 0 && d > 0) {
									var thisarea = Math.abs(a - b) * Math.abs(c - d);
									if (thisarea > area) {
										area = thisarea;
										biggestArea = jQuery(this);
									}
								}
							}
						}
					});
					
					if (biggestArea !== 0) {
						var bb = jQuery('#ebayaffinity-clonedit').clone();
						bb.attr('id', 'ebayaffinity-clonedit2');
						bb.attr('style', 'font-size: 36px !important;');
						bb.css('opacity', '0.5');
						bb.css('line-height', '36px');
						bb.text('+');
						
						biggestArea.find('.ebayaffinity-template-container').find('.ebayaffinity-template-remaining').before(bb);
						biggestArea.find('.ebayaffinity-template-container').css('background-color', '#457AFA');
					}
				}
			});	
			
			boo.mouseup(function(e) {
				jQuery('.ebayaffinity-rule-container .ebayaffinity-template-container').css('background-color', '');
				
				var top = jQuery(this).position().top;
				var left = jQuery(this).position().left;
				var width = jQuery(this).width();
				var height = jQuery(this).height();
				var area = 0;
				var biggestArea = 0;
				jQuery('.ebayaffinity-rule-container').each(function() {
					if (!jQuery(this).hasClass('ebayaffinity-rule-container-no')) {
						if (jQuery(this).css('z-index') != '20') {
							var a = (jQuery(this).position().top + jQuery(this).height()) - top;
							var b = (top + height) - jQuery(this).position().top;
							var c = (jQuery(this).position().left + jQuery(this).width()) - left;
							var d = (left + width) - jQuery(this).position().left;
							if (a > 0 && b > 0 && c > 0 && d > 0) {
								var thisarea = Math.abs(a - b) * Math.abs(c - d);
								if (thisarea > area) {
									area = thisarea;
									biggestArea = jQuery(this);
								}
							}
						}
					}
				});
				
				if (biggestArea !== 0) {
					var id = biggestArea.attr('data-id');
					var template = jQuery('<div class="ebayaffinity-template-unit" unselectable="on" onselectstart="return false;" data-type="attr">'+
						'<a class="ebayaffinity-little-del" href="#"></a>'+
						'<input name="ruleTypes" value="attr" type="hidden">'+
						'<input name="ruleVals" value="" type="hidden">'+
					'</div>');
					template.attr('data-value', jQuery(this).attr('data-attr'));
					template.attr('data-count', jQuery(this).attr('data-count'));
					template.find('input[name=ruleVals]').attr('value', jQuery(this).attr('data-attr'));
					template.find('input[name=ruleTypes]').attr('name', 'ruleTypes[' + id + '][]');
					template.find('input[name=ruleVals]').attr('name', 'ruleVals[' + id + '][]');
					template.append(jQuery(this).html());
					biggestArea.find('.ebayaffinity-template-container').append(template);
					jQuery('#ebayaffinity-clonedit').remove();
					jQuery('#ebayaffinity-clonedit2').remove();
					ebayaffinityRemaining(biggestArea.find('.ebayaffinity-template-container'));
						
				}
				jQuery('.ebayaffinity-titleopt').unbind('mousemove');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('cursor', '');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('position', '');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('top', '');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('left', '');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('z-index', '');
				jQuery('.ebayaffinity-available-attributes .ebayaffinity-template-unit').css('opacity', '');
				affinityDragging = false;
				jQuery('#ebayaffinity-clonedit').remove();
				jQuery('#ebayaffinity-clonedit2').remove();
			});
		}
	});
	
	jQuery('.ebayaffinity-titleopt').on('mousedown', '.ebayaffinity-template-container .ebayaffinity-template-unit', function(e) {
		if (affinityDragging == false) {
			affinityDragging = true;
			origLeft = e.pageX;
			origTop = e.pageY;
			var boo = jQuery(this);
			
			jQuery('.ebayaffinity-template-container').unbind('mousemove');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('cursor', '');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('position', '');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('top', '');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('left', '');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('z-index', '');
			jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('opacity', '');
			
			jQuery('#ebayaffinity-dummy').remove();
			
			jQuery('.ebayaffinity-template-container').mousemove(function(e) {
				if (affinityDragging) {
					if (boo.closest('.ebayaffinity-template-container').find('#ebayaffinity-dummy').length == 0) {
						var template = jQuery('<div class="ebayaffinity-template-unit" id="ebayaffinity-dummy" style="visibility: hidden;">Dummy</div>');
						boo.closest('.ebayaffinity-template-container').find('.ebayaffinity-template-remaining').before(template);
					}
					var left = origLeft;
					left -= e.pageX;
					left *= -1;
					var top = origTop;
					top -= e.pageY;
					top += -1;
					top *= -1;
					boo.css('cursor', 'move');
					boo.css('position', 'relative');
					boo.css('top', top + 'px');
					boo.css('left', left + 'px');
					boo.css('z-index', '20');
				}
			});
		}
	});
	
	jQuery('.ebayaffinity-titleopt').on('mouseup', '.ebayaffinity-template-container .ebayaffinity-template-unit', function(e) {
		var top = jQuery(this).position().top;
		var left = jQuery(this).position().left;
		var width = jQuery(this).width();
		var height = jQuery(this).height();
		var area = 0;
		var biggestArea = 0;
		var type = 'after';
		jQuery(this).closest('.ebayaffinity-template-container').find('.ebayaffinity-template-unit').each(function() {
			if (jQuery(this).css('z-index') != '20') {
				var a = (jQuery(this).position().top + jQuery(this).height()) - top;
				var b = (top + height) - jQuery(this).position().top;
				var c = (jQuery(this).position().left + jQuery(this).width()) - left;
				var d = (left + width) - jQuery(this).position().left;
				if (a > 0 && b > 0 && c > 0 && d > 0) {
					var thisarea = Math.abs(a - b) * Math.abs(c - d);
					if (thisarea > area) {
						area = thisarea;
						biggestArea = jQuery(this);
						if ((left + (width / 2)) > (jQuery(this).position().left + (jQuery(this).width() / 2))) {
							type = 'after';
						} else {
							type = 'before';
						}
					}
				}
			}
		});
		
		if (area > 0) {
			if (type == 'after') {
				biggestArea.after(jQuery(this));
			} else {
				biggestArea.before(jQuery(this));
			}
		}
		
		jQuery('.ebayaffinity-template-container').unbind('mousemove');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('cursor', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('position', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('top', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('left', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('z-index', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('background-color', '');
		jQuery('.ebayaffinity-titleopt .ebayaffinity-template-container .ebayaffinity-template-unit').css('opacity', '');
		
		jQuery('#ebayaffinity-dummy').remove();
		affinityDragging = false;
		jQuery('#ebayaffinity-clonedit').remove();
	});
	
	jQuery('.ebayaffinity-inv-filter-price-slider-in').closest('.ebayaffinity-inv-filter-block').mousemove(function(e) {
		if (minDragging) {
			var aaa = jQuery('.ebayaffinity-inv-filter-price-slider-pricemin');
		} else if (maxDragging) {
			var aaa = jQuery('.ebayaffinity-inv-filter-price-slider-pricemax');
		} else {
			return false;
		}
		
		aaa.each(function() {
			var left = origLeft;
			
			left += e.pageX - minXPos;
			if (left <= 0) {
				left = 0;
			}
			if (left >= jQuery('.ebayaffinity-inv-filter-price-slider').width()) {
				left = jQuery('.ebayaffinity-inv-filter-price-slider').width();
			}
			if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemin')) {
				if (left > jQuery('.ebayaffinity-inv-filter-price-slider-pricemax').position().left - 10) {
					left = jQuery('.ebayaffinity-inv-filter-price-slider-pricemax').position().left - 10;
				}
			}
			if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemax')) {
				if (left < jQuery('.ebayaffinity-inv-filter-price-slider-pricemin').position().left + 10) {
					left = jQuery('.ebayaffinity-inv-filter-price-slider-pricemin').position().left + 10;
				}
			}
			jQuery(this).css('left', left + 'px');
			
			jQuery('.ebayaffinity-inv-filter-price-slider-in').css('left', jQuery('.ebayaffinity-inv-filter-price-slider-pricemin').position().left + 'px');
			jQuery('.ebayaffinity-inv-filter-price-slider-in').css('right', (jQuery('.ebayaffinity-inv-filter-price-slider').width() - jQuery('.ebayaffinity-inv-filter-price-slider-pricemax').position().left) + 'px');
			
			var percentage = (left / jQuery('.ebayaffinity-inv-filter-price-slider').width());
			
			var datas = jQuery('.ebayaffinity-inv-filter-price-slider');
			
			var intval = parseInt(((parseInt(datas.attr('data-maxprice'), 10) - parseInt(datas.attr('data-minprice'), 10)) * percentage) + parseInt(datas.attr('data-minprice'), 10), 10);
			
			if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemin')) {
				jQuery('.ebayaffinity-inv-filter-price-slider-pricemin-read').text('$' + intval);
				jQuery('#pricemin').attr('value', intval);
			} else if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemax')) {
				jQuery('.ebayaffinity-inv-filter-price-slider-pricemax-read').text('$' + intval);
				jQuery('#pricemax').attr('value', intval);
			}
		});
	}).mouseup(function() {
		if (minDragging || maxDragging) {
			minDragging = false;
			maxDragging = false;
			if (jQuery('#ebayaffinity-searchcat').length > 0) {
				jQuery('.ebayaffinity-inv-filter input[name=s]').val(jQuery('#ebayaffinity-searchcat').val());
			}
			jQuery('#ebayaffinity-inv-filter-paged').val(1);
			jQuery('#ebayaffinity-inv-filter-form, #ebayaffinity-ajax-s').submit();
		}
	}).mouseleave(function() {
		if (minDragging || maxDragging) {
			minDragging = false;
			maxDragging = false;
		}
	});

	jQuery('.ebayaffinity-inv-filter-price-slider-pricemin, .ebayaffinity-inv-filter-price-slider-pricemax').mousedown(function(e) {
		if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemin')) {
			minXPos = e.pageX;
			minDragging = true;
		} else if (jQuery(this).hasClass('ebayaffinity-inv-filter-price-slider-pricemax')) {
			minXPos = e.pageX;
			maxDragging = true;
		}
		var left = jQuery(this).position().left;
		origLeft = left;	
		jQuery(this).css('left', left + 'px');
		jQuery(this).css('right', '');
		
	});
	
	jQuery('#ebayaffinity-inv-filter-close').click(function() {
		jQuery('body').unbind('click', ebayaffinity_filter_out);
		
		jQuery('.ebayaffinity-inv-filter').animate({'opacity': '0'}, {
			complete: function() {
				jQuery('.ebayaffinity-inv-filter').css('display', 'none');
				jQuery('.ebayaffinity-inv-filter').css('opacity', '');
			},
			duration: '100'
		});
		return false;
	});
	
	jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
	jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px');
	
	if (jQuery('.ebayaffinity-inv-filter').length > 0) {
		jQuery(window).resize(function() {
			jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
			jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px');
		});
	}
	
	jQuery('.ebayaffinity-filter, .ebayaffinity-filter-button').click(function() {
		jQuery('.ebayaffinity-inv-filter').css('height', 'auto');
		jQuery('.ebayaffinity-inv-filter').css('height', jQuery(document).height() + 'px');
		jQuery('.ebayaffinity-inv-filter').css('opacity', '0');
		jQuery('.ebayaffinity-inv-filter').css('display', 'block');
		jQuery('.ebayaffinity-inv-filter').animate({'opacity': '1'}, {
			complete: function() {
				jQuery('.ebayaffinity-inv-filter').css('opacity', '');
			},
			duration: '100'
		});
		jQuery('body').bind('click', ebayaffinity_filter_out);
		
		return false;
	});
	
	jQuery('.ebayaffinity-save-cancel button').click(function() {
		var lsc = jQuery('.ebayaffinity-save-cancel');
		lsc.animate({'margin-top': '-100px'}, {
			complete: function() {
				lsc.css('display', 'none');
			}
		});
	});
	
	jQuery('table.ebayaffinity-inv-table button').click(function() {
		var lsc = jQuery('.ebayaffinity-save-cancel');
		lsc.css('display', 'block');
		lsc.animate({'margin-top': '0'});e
		
	});
	
	jQuery('.ebayaffinity-inv-detail-data-section-clicker:not([data-leaf])').click(function() {
		if (jQuery(this).hasClass('ebayaffinity-inv-detail-data-section-clicker-disabled')) {
			return false;
		}
		var origin = this;
		var tomove = jQuery(this).next();
		if (jQuery(this).hasClass('ebayaffinity-inv-detail-data-section-clicker-off')) {
			tomove.css('display', 'block');
			tomove.css('height', 'auto');
			var height = tomove.height();
			tomove.css('height', '0');
			tomove.animate({'height': height + 'px'}, {
			complete: function() {
				tomove.css('height', 'auto');
				jQuery(origin).removeClass('ebayaffinity-inv-detail-data-section-clicker-off');
				jQuery(origin).addClass('ebayaffinity-inv-detail-data-section-clicker-on');
			},
			duration: '50'});
		
		} else {
			tomove.animate({'height': '0'}, {
			complete: function() {
				tomove.css('display', 'none');
				jQuery(origin).removeClass('ebayaffinity-inv-detail-data-section-clicker-on');
				jQuery(origin).addClass('ebayaffinity-inv-detail-data-section-clicker-off');
			},
			duration: '50'});

		}
		return false;
	});
	
	jQuery('#wpbody-content').on('click', '.ebayaffinity-search', function() {
		if (jQuery(this).find('> form').css('display') == 'none') {
			jQuery(this).css('cursor', 'auto');
			jQuery(this).find('> form').addClass('ebayaffinity-search-mobile');
		}
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-bt-collapse', function() {
		jQuery(this).closest('.ebayaffinity-rule-container').addClass('ebayaffinity-rule-collapsed');
		jQuery(this).closest('.ebayaffinity-rule-container').removeClass('ebayaffinity-rule-expanded');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-category-container').css('display', 'none');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-product-container').css('display', 'none');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-productlong-container').css('display', 'none');
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-bt-expand', function() {
		jQuery(this).closest('.ebayaffinity-rule-container').removeClass('ebayaffinity-rule-collapsed');
		jQuery(this).closest('.ebayaffinity-rule-container').addClass('ebayaffinity-rule-expanded');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-category-container').css('display', 'block');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-product-container').css('display', 'block');
		jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-setting-details-productlong-container').css('display', 'block');
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-template-unit-edible', function() {
		jQuery(this).find('input').focus();
	});
	
	jQuery('.ebayaffinity-titleopt').on('change keypress keydown keyup', '.ebayaffinity-template-unit-edible input', function() {
		jQuery(this).closest('.ebayaffinity-template-unit-edible').attr('data-count', jQuery(this).val().length);
		
		ebayaffinityRemaining(jQuery(this).closest('.ebayaffinity-template-container'));
	});
	
	jQuery('.ebayaffinity-titleopt').on('mouseenter', '.ebayaffinity-template-unit', function() {
		jQuery(this).find('a.ebayaffinity-little-del').css('display', 'block');
	});
	
	jQuery('.ebayaffinity-titleopt').on('mouseleave', '.ebayaffinity-template-unit', function() {
		jQuery(this).find('a.ebayaffinity-little-del').css('display', 'none');
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-bt-del-template', function() {
		jQuery(this).closest('.ebayaffinity-rule-container').remove();
		if (jQuery('.ebayaffinity-rule-container').length == 0) {
			jQuery('.ebayaffinity-no-rules').remove();
			jQuery('.ebayaffinity-rules').prepend('<div class="ebayaffinity-no-rules">No rules as yet.</div>');
		}
	});
	
	jQuery('.ebayaffinity-rules').on('click', '.ebayaffinity-settingsheader-ship .ebayaffinity-bt-del-template', function() {
		var id = parseInt(jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-category-container').attr('data-id'), 10);
		jQuery('.ebayaffinity_catshiprules[value='+id+']').attr('value', '0');
		jQuery('.ebayaffinity_prodshiprules[value='+id+']').remove();
		jQuery(this).closest('.ebayaffinity-settingsblock').remove();
		ebayRemainingAll();
		return false;
	});
	
	jQuery('.ebayaffinity-rules').on('click', '.ebayaffinity-settingsheader-ship .ebayaffinity-bt-del-template-old', function() {
		jQuery(this).closest('.ebayaffinity-settingsblock').css('display', 'none');
		var id = parseInt(jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-category-container').attr('data-id'), 10);
		var template = jQuery('<input type="hidden" class="ebayaffinity_hideshiprules"></input>');
		template.attr('id', 'ebayaffinity_hideshiprules_' + id);
		template.attr('name', 'ebayaffinity_hideshiprules[' + id + ']');
		template.attr('value', id);
		jQuery(this).closest('.ebayaffinity-settingsblock').append(template);
		ebayRemainingAll();
		return false;
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-little-del', function() {
		var close = jQuery(this).closest('.ebayaffinity-rule-container');	
		jQuery(this).closest('.ebayaffinity-template-unit').remove();
		ebayaffinityRemaining(close);
		return false;
	});
	
	if (jQuery('.ebayaffinity-titleopt').length > 0) {
		jQuery('body').click(function() {
			jQuery('.ebayaffinity-bt-add-to-template.ebayaffinity-bt-add-to-template-opened').click();
		});
	}

	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-bt-add-to-template', function(event) {
		event.stopPropagation();
		if (jQuery(this).hasClass('ebayaffinity-bt-add-to-template-opened')) {
			jQuery(this).find('.ebayaffinity-bt-add-to-template-select').animate({'opacity': '0'}, {
				complete: function() {
					jQuery(this).remove();
				},
				duration: '100'
			});
			jQuery(this).removeClass('ebayaffinity-bt-add-to-template-opened');
		} else {
			var id = jQuery(this).closest('.ebayaffinity-rule-container').attr('data-id');
			jQuery('.ebayaffinity-rule-container[data-id!=' + id +'] .ebayaffinity-bt-add-to-template.ebayaffinity-bt-add-to-template-opened').each(function() {
				jQuery(this).find('.ebayaffinity-bt-add-to-template-select').animate({'opacity': '0'}, {
					complete: function() {
						jQuery(this).remove();
					},
					duration: '100'
				});
				jQuery(this).removeClass('ebayaffinity-bt-add-to-template-opened');
			});
		
			jQuery(this).addClass('ebayaffinity-bt-add-to-template-opened');
			var template = jQuery('<div class="ebayaffinity-bt-add-to-template-select"></div>');
			template.append(jQuery('<a href="#" data-attr="title" data-count="0" class="ebayaffinity-add-string">Add text</a>'));
			template.append(jQuery('<a href="#" data-attr="title" class="ebayaffinity-add-attr">Product name</a>'));
			template.find('a[data-attr=title]').attr('data-count', ebayaffinity_lengths.max);
			for (var i in ebayaffinity_attributes) {
				if (ebayaffinity_attributes.hasOwnProperty(i)) {
					var templatein = jQuery('<a href="#" class="ebayaffinity-add-attr"></a>');
					var str = ebayaffinity_attributes[i];
					templatein.text(str + ' ');
					if (i.substr(0, 3) == 'pa_') {
						//str += ' <span class="ebayaffinity-global-mark">&nbsp;</span>';
						templatein.append('<span class="ebayaffinity-global-mark">&nbsp;</span>');
					}
					if (ebayaffinity_attributes[i].length == 0) {
						str = '<em>name missing</em>';
						if (i.substr(0, 3) == 'pa_') {
							str += ' <span class="ebayaffinity-global-mark">&nbsp;</span>';
						}
						templatein.html(str);
					}
					templatein.attr('data-attr', i);
					templatein.attr('data-count', ebayaffinity_attCounts[i][0]);
					templatein.attr('data-num', ebayaffinity_attCounts[i][1]);
					template.append(templatein);
				}
			}
			jQuery(this).append(template);
			jQuery(this).find('.ebayaffinity-bt-add-to-template-select').animate({'opacity': '1'}, {
				duration: '100'
			});
			jQuery(this).find('.ebayaffinity-add-attr').click(function() {
				var id = jQuery(this).closest('.ebayaffinity-rule-container').attr('data-id');
				var template = jQuery('<div class="ebayaffinity-template-unit" unselectable="on" onselectstart="return false;" data-type="attr">'+
					'<a class="ebayaffinity-little-del" href="#"></a>'+
					'<input name="ruleTypes" value="attr" type="hidden">'+
					'<input name="ruleVals" value="" type="hidden">'+
				'</div>');
				template.attr('data-value', jQuery(this).attr('data-attr'));
				template.attr('data-count', jQuery(this).attr('data-count'));
				template.find('input[name=ruleVals]').attr('value', jQuery(this).attr('data-attr'));
				template.find('input[name=ruleTypes]').attr('name', 'ruleTypes[' + id + '][]');
				template.find('input[name=ruleVals]').attr('name', 'ruleVals[' + id + '][]');
				template.append(jQuery(this).html());
				
				jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-template-container').append(template);
				
				ebayaffinityRemaining(jQuery(this).closest('.ebayaffinity-rule-container'));
				
				jQuery(this).closest('.ebayaffinity-bt-add-to-template').removeClass('ebayaffinity-bt-add-to-template-opened');
				jQuery(this).closest('.ebayaffinity-bt-add-to-template-select').remove();
				return false;
			});
			jQuery(this).find('.ebayaffinity-add-string').click(function() {
				var id = jQuery(this).closest('.ebayaffinity-rule-container').attr('data-id');
				var template = jQuery('<div class="ebayaffinity-template-unit ebayaffinity-template-unit-edible" data-count="0" data-type="string">'+
					'<a class="ebayaffinity-little-del" href="#"></a>'+
					'<input type="hidden" value="string" name="ruleTypes[1][]">'+
					'<input type="text" value="" name="ruleVals[1][]">'+
				'</div>');
				template.find('input[type=hidden]').attr('name', 'ruleTypes[' + id + '][]');
				template.find('input[type=text]').attr('name', 'ruleVals[' + id + '][]');
				jQuery(this).closest('.ebayaffinity-rule-container').find('.ebayaffinity-template-container').append(template);
				
				ebayaffinityRemaining(jQuery(this).closest('.ebayaffinity-rule-container'));
				
				jQuery(this).closest('.ebayaffinity-bt-add-to-template').removeClass('ebayaffinity-bt-add-to-template-opened');
				jQuery(this).closest('.ebayaffinity-bt-add-to-template-select').remove();
				return false;
			});
		}
	});
	
	jQuery('a.ebayaffinity-bt-new-shiprule').click(function() {
		jQuery('.ebayaffinity-no-rules').remove();
		
		var template = jQuery('<div class="ebayaffinity-settingsblock">'+
			'<div class="ebayaffinity-settingsheader ebayaffinity-settingsheader-ship">'+
				' <div class="ebayaffinity-settingsheadernote">You are required to select at least one shipping method.</div>'+
				'<div class="ebayaffinity-settingsheadersetdefault">'+
					'<input name="ebayaffinity_shiprule_default" value="3" type="radio">'+
					'<label class="ebayaffinity-label ebayaffinity-label-ship-default">Set as default shipping rule</label>'+
				'</div>'+
				'<span class="ebayaffinity-settings-action-button ebayaffinity-bt-del-template">'+
					'<span>&nbsp;</span>'+
				'</span>'+
				'<div class="ebayaffinity-settings-action-button ebayaffinity-bt-expand">'+
					'<span>&nbsp;</span>'+
				'</div>'+
			'</div>'+
			'<div class="ebayaffinity-setting-details-category-container">'+
				'<div class="ebayaffinity-header">'+
					'<div class="ebayaffinity-title">Categories applied to:</div>'+
					'<div class="ebayaffinity-bt-add-category">Add category</div>'+
				'</div>'+
				'<div class="ebayaffinity-categories-applied-to"><div class="ebayaffinity-category-item"><span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span></div></div>'+
			'</div>'+
			'<div class="ebayaffinity-setting-details-product-container">'+
				'<div class="ebayaffinity-header">'+
					'<div class="ebayaffinity-title">Products applied to:</div>'+
					'<div class="ebayaffinity-bt-add-products">Add products</div>'+
				'</div>'+
				'<div class="ebayaffinity-products-applied-to"><div class="ebayaffinity-product-item"><span class="ebayaffinity-category-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span></div></div>'+
			'</div>'+
			'<div class="ebayaffinity-setting-details-extra-container">'+
				'<div class="ebayaffinity-settingset">'+
					'<div class="ebayaffinity-labeldiv ebayaffinity_rate_table">'+
						'<input type="checkbox" value="1">'+
						'<label class="ebayaffinity-label">Apply domestic postage rate table</label>'+
					'</div>'+
				'</div>'+
				'<div class="ebayaffinity-settingset">'+
					'<div class="ebayaffinity-labeldiv ebayaffinity_rate_table">'+
						'<input type="checkbox" value="1">'+
						'<label class="ebayaffinity-label">Buyers can collect the item at Woolworths or BIG W with Click &amp; Collect</label>'+
					'</div>'+
				'</div>'+
			'</div>'+
            '<div class="ebayaffinity-setting-details-discount-container">' +
                '<div class="ebayaffinity-header">' +
                    '<div class="ebayaffinity-title">Combined Postage Discounts:' +
                        '<div class="ebayaffinity-questiondiv">' +
                            '<div class="ebayaffinity-question"><span class="info">You can offer buyers postage discounts when they buy multiple items from you allowing them to save on postage. eBay Sync currently only supports flat postage rules, which you need to setup in My eBay. Click <a href="http://pages.ebay.com.au/help/pay/shipping-discounts.html" target="_blank">here</a> for more information.</span>?</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="ebayaffinity-discount-applied-to">' +
                    '<div class="ebayaffinity-discount-item">' +
                        '<div class="ebayaffinity-valuediv">' +
                            '<div class="ebayaffinity-discount-item"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
			'<div class="ebayaffinity-settingset ebayaffinity-settingset-mob">'+
				'<div class="ebayaffinity-setting">'+
					'<div class="ebayaffinity-settingcell">'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-settinghead">'+
									'Standard&nbsp;shipping'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<div class="ebayaffinity-settingset ebayaffinity-settingpanel">'+
							'<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">'+
								'<div class="ebayaffinity-labeldiv">'+
									'<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_freeshippingn">Free shipping?</label>'+
								'</div>'+
								'<div class="ebayaffinity-valuediv">'+
									'<input class="ebayaffinity_shiprule_standard_freeshipping" id="ebayaffinity_shiprule_standard_freeshippingn" name="ebayaffinity_shiprule_standard_freeshipping[n]" value="1" type="checkbox">'+
								'</div>'+
								'<div class="ebayaffinity-questiondiv">'+
								'</div>'+
							'</div>'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-labeldiv">'+
									'<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_feen">Shipping fee ($)</label>'+
								'</div>'+
								'<div class="ebayaffinity-valuediv">'+
									'<input id="ebayaffinity_shiprule_standard_feen" name="ebayaffinity_shiprule_standard_fee[n]" value="" type="text">'+
								'</div>'+
								'<div class="ebayaffinity-questiondiv">'+
									'<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for your standard shipping services</span>?</div>'+
								'</div>'+
							'</div>'+
                            '<div class="ebayaffinity-setting">'+
                                '<div class="ebayaffinity-labeldiv">'+
                                    '<label class="ebayaffinity-label" for="ebayaffinity_shiprule_standard_additional_feen">Each Additional ($)</label>'+
                                '</div>'+
                                '<div class="ebayaffinity-valuediv">'+
                                    '<input type="text" class="ebayaffinity_shiprule_additional_fee" id="ebayaffinity_shiprule_standard_additional_feen" name="ebayaffinity_shiprule_standard_additional_fee[n]" value="">'+
                                '</div>'+
                                '<div class="ebayaffinity-questiondiv">'+
                                    '<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for shipping of each additional quantity of the same item.</span>?</div>'+
                                '</div>'+
                            '</div>'+
						'</div>'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-settingsubhead">'+
									'<em>If you do not want to offer standard shipping, leave both fields blank.</em>'+
								'</div>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<div class="ebayaffinity-settingcell">'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-settinghead">'+
									'Express&nbsp;shipping'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<div class="ebayaffinity-settingset ebayaffinity-settingpanel">'+
							'<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">'+
								'<div class="ebayaffinity-labeldiv">'+
									'<label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_freeshippingn">Free shipping?</label>'+
								'</div>'+
								'<div class="ebayaffinity-valuediv">'+
									'<input class="ebayaffinity_shiprule_express_freeshipping" id="ebayaffinity_shiprule_express_freeshippingn" name="ebayaffinity_shiprule_express_freeshipping[n]" value="1" type="checkbox">'+
								'</div>'+
								'<div class="ebayaffinity-questiondiv">'+
								'</div>'+
							'</div>'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-labeldiv">'+
									'<label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_feen">Shipping fee ($)</label>'+
								'</div>'+
								'<div class="ebayaffinity-valuediv">'+
									'<input id="ebayaffinity_shiprule_express_feen" name="ebayaffinity_shiprule_express_fee[n]" value="" type="text">'+
								'</div>'+
								'<div class="ebayaffinity-questiondiv">'+
									'<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for your express shipping services</span>?</div>'+
								'</div>'+
							'</div>'+
                            '<div class="ebayaffinity-setting">'+
                                '<div class="ebayaffinity-labeldiv">'+
                                    '<label class="ebayaffinity-label" for="ebayaffinity_shiprule_express_additional_feen">Each Additional ($)</label>'+
                                '</div>'+
                                '<div class="ebayaffinity-valuediv">'+
                                    '<input type="text" class="ebayaffinity_shiprule_additional_fee" id="ebayaffinity_shiprule_express_additional_feen" name="ebayaffinity_shiprule_express_additional_fee[n]" value="">'+
                                '</div>'+
                                '<div class="ebayaffinity-questiondiv">'+
                                '<div class="ebayaffinity-question"><span class="info">The price in AUD you want to charge for shipping of each additional quantity of the same item.</span>?</div>'+
                                '</div>'+
                            '</div>'+
						'</div>'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-settingsubhead">'+
									'<em>If you do not want to offer express shipping, leave both fields blank.</em>'+
								'</div>'+
							'</div>'+
						'</div>'+
					'</div>'+
					'<div class="ebayaffinity-settingcell">'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting">'+
								'<div class="ebayaffinity-settinghead">'+
									'Handling&nbsp;time'+
								'</div>'+
							'</div>'+
							
						'</div>'+
						'<div class="ebayaffinity-settingset">'+
							'<div class="ebayaffinity-setting ebayaffinity_shiprule_standard_freeshippingrow">'+
								'<div class="ebayaffinity-valuediv">'+
									'<select id="ebayaffinity_shiprule_handledaysn" class="ebayaffinity_shiprule_handledays" name="ebayaffinity_shiprule_handledays[n]">'+
										'<option value="0">0</option>'+
										'<option value="1" selected>1</option>'+
										'<option value="2">2</option>'+
										'<option value="3">3</option>'+
										'<option value="4">4</option>'+
										'<option value="5">5</option>'+
										'<option value="10">10</option>'+
										'<option value="15">15</option>'+
										'<option value="20">20</option>'+
										'<option value="30">30</option>'+
									'</select>'+
								'</div>'+
								'<div class="ebayaffinity-labeldiv ebayaffinity_shiprule_handledayslabel">'+
									'<label class="ebayaffinity-label" for="ebayaffinity_shiprule_handledaysn">business&nbsp;day(s)</label>'+
								'</div>'+
								'<div class="ebayaffinity-questiondiv">'+
									'<div class="ebayaffinity-question"><span class="info">The number of days you will take to ship your eBay orders. <a target="_blank" href="http://pages.ebay.com.au/help/buy/contextual/domestic-handling-time.html">More info</a></span>?</div>'+
								'</div>'+
							'</div>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>');
		
		var ebayaffinity_nextid = parseInt(jQuery('#ebayaffinity_nextid').val(), 10);

		template.find('.ebayaffinity-setting-details-extra-container input').eq(0).attr('id', 'ebayaffinity_rate_table' + ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-extra-container label').eq(0).attr('for', 'ebayaffinity_rate_table' + ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-extra-container input').eq(0).attr('name', 'ebayaffinity_rate_table[' + ebayaffinity_nextid + ']');
		
		template.find('.ebayaffinity-setting-details-extra-container input').eq(1).attr('id', 'ebayaffinity_shiprule_pudo' + ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-extra-container label').eq(1).attr('for', 'ebayaffinity_shiprule_pudo' + ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-extra-container input').eq(1).attr('name', 'ebayaffinity_shiprule_pudo[' + ebayaffinity_nextid + ']');
		
		if (!affinity_isDomesticaRateTableEnabled) {
			template.find('.ebayaffinity-setting-details-extra-container .ebayaffinity-settingset').eq(0).css('display', 'none');
		} else {
			template.find('.ebayaffinity-setting-details-extra-container input').eq(0).prop('checked', true);
		}
		
		if (!affinity_isClickAndCollectEnabled) {
			template.find('.ebayaffinity-setting-details-extra-container .ebayaffinity-settingset').eq(1).css('display', 'none');
		} else {
			template.find('.ebayaffinity-setting-details-extra-container input').eq(1).prop('checked', true);
		}
		
		if ((!affinity_isDomesticaRateTableEnabled) && (!affinity_isClickAndCollectEnabled)) {
			template.find('.ebayaffinity-setting-details-extra-container').attr('style', 'visibility: hidden !important;border: 0 !important; height: 0 !important; overflow: hidden !important; padding: 0 !important; margin: 0 !important;');
		}
		template.find('.ebayaffinity-settingsheader').prepend('#' + ebayaffinity_nextid);
		template.find('.ebayaffinity-settingsheader').prepend(jQuery('<span class="ebayaffinity-not-mobile">Shipping rule </span>'));
		template.find('.ebayaffinity-settingsheadersetdefault input').attr('id', 'ebayaffinity_shiprule_default' + ebayaffinity_nextid);
		template.find('.ebayaffinity-settingsheadersetdefault input').attr('value', ebayaffinity_nextid);
		template.find('.ebayaffinity-settingsheadersetdefault label').attr('for', 'ebayaffinity_shiprule_default' + ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-category-container').attr('data-id', ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-product-container').attr('data-id', ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_standard_freeshippingn]').attr('for', 'ebayaffinity_shiprule_standard_freeshipping' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_standard_freeshippingn').attr('name', 'ebayaffinity_shiprule_standard_freeshipping[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_standard_freeshippingn').attr('id', 'ebayaffinity_shiprule_standard_freeshipping' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_standard_feen]').attr('for', 'ebayaffinity_shiprule_standard_fee' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_standard_feen').attr('name', 'ebayaffinity_shiprule_standard_fee[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_standard_feen').attr('id', 'ebayaffinity_shiprule_standard_fee' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_standard_additional_feen]').attr('for', 'ebayaffinity_shiprule_standard_additional_fee' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_standard_additional_feen').attr('name', 'ebayaffinity_shiprule_standard_additional_fee[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_standard_additional_feen').attr('id', 'ebayaffinity_shiprule_standard_additional_fee' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_handledaysn]').attr('for', 'ebayaffinity_shiprule_handledays' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_handledaysn').attr('name', 'ebayaffinity_shiprule_handledays[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_handledaysn').attr('id', 'ebayaffinity_shiprule_handledays' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_express_freeshippingn]').attr('for', 'ebayaffinity_shiprule_express_freeshipping' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_express_freeshippingn').attr('name', 'ebayaffinity_shiprule_express_freeshipping[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_express_freeshippingn').attr('id', 'ebayaffinity_shiprule_express_freeshipping' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_express_feen]').attr('for', 'ebayaffinity_shiprule_express_fee' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_express_feen').attr('name', 'ebayaffinity_shiprule_express_fee[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_express_feen').attr('id', 'ebayaffinity_shiprule_express_fee' + ebayaffinity_nextid);
		template.find('label[for=ebayaffinity_shiprule_express_additional_feen]').attr('for', 'ebayaffinity_shiprule_express_additional_fee' + ebayaffinity_nextid);
		template.find('#ebayaffinity_shiprule_express_additional_feen').attr('name', 'ebayaffinity_shiprule_express_additional_fee[' + ebayaffinity_nextid + ']');
		template.find('#ebayaffinity_shiprule_express_additional_feen').attr('id', 'ebayaffinity_shiprule_express_additional_fee' + ebayaffinity_nextid);

        if (affinity_shippingDiscountProfiles && affinity_shippingDiscountProfiles.length == 1) {
            template.find('.ebayaffinity-discount-item .ebayaffinity-discount-item').prepend(jQuery('<div class="ebayaffinity-valuediv ebayaffinity-labeldiv ebayaffinity_postage_discount">'+
                '<input type="checkbox" name="ebayaffinity_discount_profile[' + ebayaffinity_nextid + ']" value="' + affinity_shippingDiscountProfiles[0].DiscountProfileID + '" id="ebayaffinity_postage_discount[' + ebayaffinity_nextid + ']">'+
                '<label class="ebayaffinity-label" for="ebayaffinity_postage_discount[' + ebayaffinity_nextid + ']">Apply Flat Postage Discount</label>'+
            '</div>'));
        } else if (affinity_shippingDiscountProfiles && affinity_shippingDiscountProfiles.length > 1) {
            var selectOptions = '';
            for (i in affinity_shippingDiscountProfiles) {
                var profileId = affinity_shippingDiscountProfiles[i].DiscountProfileID;
                var profileName = affinity_shippingDiscountProfiles[i].DiscountProfileName ? affinity_shippingDiscountProfiles[i].DiscountProfileName : affinity_shippingDiscountProfiles[i].DiscountProfileID;
                selectOptions += '<option value="' + profileId + '">' + profileName + '</option>';
            }
            var selectPanel =
                jQuery('<div class="ebayaffinity-valuediv">' +
                '<select name="ebayaffinity_discount_profile[' + ebayaffinity_nextid + ']">' +
                '<option value="">Not Applied</option>' +
                selectOptions +
                '</select>' +
                '</div>');
            template.find('.ebayaffinity-discount-item .ebayaffinity-discount-item').prepend(selectPanel);
        } else {
            template.find('.ebayaffinity-discount-item .ebayaffinity-discount-item').prepend(jQuery('<span class="ebayaffinity-discount-unit ebayaffinity-discount-leaf"><em>None Found</em></span>'));
        }

		ebayaffinity_nextid += 1;
		jQuery('#ebayaffinity_nextid').val(ebayaffinity_nextid);
		
		jQuery('.ebayaffinity-rules').append(template);
		return false;
	});
	
	jQuery('button.ebayaffinity-bt-new-rule').click(function() {
		jQuery('.ebayaffinity-no-rules').remove();
	
		var template = jQuery('<div class="ebayaffinity-rule-container ebayaffinity-rule-collapsed">'+
			'<div class="ebayaffinity-rule-header">'+
				'<input class="ebayaffinity-rule-title" maxlength="25">'+
				'<div class="ebay-affinity-rule-default">'+
					'<input class="ebay-affinity-rule-default-radio" type="radio" name="is_default">'+
					'<label class="ebay-affinity-rule-default-label">Set as default</label>'+
				'</div>'+
				'<span class="ebayaffinity-rule-action-buttons">'+
					'<span class="ebayaffinity-rule-action-button ebayaffinity-bt-del-template">'+
						'<span>&nbsp;</span>'+
					'</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-add-to-template">+'+
					'</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-expand">'+
						'<span>&nbsp;</span>'+
					'</span><span class="ebayaffinity-rule-action-button ebayaffinity-bt-collapse">'+
						'<span>&nbsp;</span>'+
					'</span>'+
				'</span>'+
			'</div>'+
			'<div class="ebayaffinity-template-container">'+
				'<div class="ebayaffinity-template-unit" unselectable="on" onselectstart="return false;" data-type="attr" data-value="title">'+
					'<a class="ebayaffinity-little-del" href="#"></a>'+
					'<input type="hidden" value="attr">'+
					'<input type="hidden" value="title">'+
					'Product name'+
				'</div>'+
				'<div class="ebayaffinity-template-remaining"><span></span> <span class="ebayaffinity-template-remaining-txt">characters remaining</span></div>'+
			'</div>'+
			'<div class="ebayaffinity-setting-details">'+
				'<div class="ebayaffinity-setting-details-category-container">'+
					'<div class="ebayaffinity-header">'+
						'<div class="ebayaffinity-title">Categories applied to:</div>'+
						'<div class="ebayaffinity-bt-add-category">Add category</div>'+
					'</div>'+
					'<div class="ebayaffinity-categories-applied-to">'+
					'<div class="ebayaffinity-category-item">'+
						'<span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span>'+
					'</div>'+
					'</div>'+
				'</div>'+
				'<div class="ebayaffinity-setting-details-product-container">'+
				'<div class="ebayaffinity-header">'+
					'<div class="ebayaffinity-title">Products applied to:</div>'+
					'<div class="ebayaffinity-bt-add-products">Add products</div>'+
				'</div>'+
				'<div class="ebayaffinity-products-applied-to">'+
					'<div class="ebayaffinity-product-item">'+
						'<span class="ebayaffinity-product-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span>'+
					'</div>'+
				'</div>'+
			'</div>'+
			'</div>'+
		'</div>');
		
		var ebayaffinity_nextid = parseInt(jQuery('#ebayaffinity_nextid').val(), 10);
		
		template.find('.ebayaffinity-rule-title').attr('name', 'title[' + ebayaffinity_nextid + ']');
		template.find('.ebayaffinity-rule-title').attr('data-orig', 'Rule #' + ebayaffinity_nextid);
		template.find('.ebayaffinity-rule-title').val('Rule #' + ebayaffinity_nextid);
		
		template.find('.ebayaffinity-template-unit').attr('data-count', ebayaffinity_lengths.max);
		
		template.find('.ebay-affinity-rule-default-radio').attr('id', 'is_default_' + ebayaffinity_nextid);
		template.find('.ebay-affinity-rule-default-radio').attr('value', ebayaffinity_nextid);
		template.find('.ebay-affinity-rule-default-label').attr('for', 'is_default_' + ebayaffinity_nextid);
		template.attr('data-id', ebayaffinity_nextid);
		template.find('.ebayaffinity-template-unit input[value=attr]').attr('name', 'ruleTypes[' + ebayaffinity_nextid + '][]');
		template.find('.ebayaffinity-template-unit input[value=title]').attr('name', 'ruleVals[' + ebayaffinity_nextid + '][]');
		template.find('.ebayaffinity-setting-details-category-container').attr('data-id', ebayaffinity_nextid);
		template.find('.ebayaffinity-setting-details-product-container').attr('data-id', ebayaffinity_nextid);
		ebayaffinity_nextid += 1;
		jQuery('#ebayaffinity_nextid').val(ebayaffinity_nextid);
		
		jQuery('.ebayaffinity-settingssave').before(template);
		
		ebayaffinityRemaining(jQuery('.ebayaffinity-rule-container[data-id=' + (ebayaffinity_nextid - 1) + '] div.ebayaffinity-template-container'));
		
		return false;
	});
	
	jQuery('.ebayaffinity-rules').on('click', '.ebayaffinity-setting-details-category-container div.ebayaffinity-bt-delete', function() {
		var catId = jQuery(this).closest('.ebayaffinity-category-item').attr('data-category-id');
		jQuery('#ebayaffinity_catshiprule_' + catId).attr('value', '0');
		jQuery('.ebayaffinity-bt-add-category').css('display', '');
		jQuery('.ebayaffinity-sel-add-category').remove();
		jQuery(this).closest('.ebayaffinity-category-item').remove();
		
		jQuery('.ebayaffinity-categories-applied-to').each(function() {
			if (jQuery(this).find('.ebayaffinity-category-item').length == 0) {
				jQuery(this).html('<div class="ebayaffinity-category-item">'+
							'<span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span>'+
						'</div>');
			}
		});
		ebayRemainingAll();
	});
	
	jQuery('.ebayaffinity-rules').on('click', '.ebayaffinity-setting-details-product-container div.ebayaffinity-bt-delete', function() {
		var prodId = jQuery(this).closest('.ebayaffinity-product-item').attr('data-product-id');
		jQuery('#ebayaffinity_prodshiprule_' + prodId).attr('value', '0');
		jQuery(this).closest('.ebayaffinity-product-item').remove();
		
		jQuery('.ebayaffinity-products-applied-to').each(function() {
			if (jQuery(this).find('.ebayaffinity-product-item').length == 0) {
				jQuery(this).html('<div class="ebayaffinity-product-item">'+
							'<span class="ebayaffinity-category-unit ebayaffinity-product-leaf ebayaffinity-product-none"><em>None as yet.</em></span>'+
						'</div>');
			}
			if (jQuery(this).find('.ebayaffinity-product-andmore').length > 0) {
				var t = jQuery(this).find('.ebayaffinity-product-andmore').closest('.ebayaffinity-product-item');
				jQuery(this).append(t);
			}
		});
		ebayRemainingAll();
	});
	
	jQuery('.ebayaffinity-rules, .ebayaffinity-setting-details-category-container, .ebayaffinity-inv-block').on('click', '.ebayaffinity-bt-add-category', function() {
		if (jQuery(this).css('display') == 'none') {
			return
		}
		jQuery(this).css('display', 'none');
		var sel = jQuery('<select class="ebayaffinity-sel-add-category"></select>');
		var opt = jQuery('<option>Select a category</option>');
		sel.append(opt);
		
		var cont = jQuery(this).closest('.ebayaffinity-settingsblock, .ebayaffinity-setting-details').find('.ebayaffinity-setting-details-category-container');
		
		jQuery('.ebayaffinity_catshiprules').each(function() {
			var id = cont.attr('data-id');
			if (jQuery(this).attr('value') != id) {
				var opt = jQuery('<option></option');
				opt.attr('value', jQuery(this).attr('id').replace('ebayaffinity_catshiprule_', ''));
				opt.text(jQuery(this).attr('data-name'));
				sel.append(opt);
			}
		});
		jQuery(this).after(sel);
		
		jQuery(this).closest('.ebayaffinity-setting-details-category-container').find('.ebayaffinity-sel-add-category').change(function() {
			jQuery('.ebayaffinity-bt-add-category').css('display', '');
			jQuery(this).closest('.ebayaffinity-setting-details-category-container').find('.ebayaffinity-bt-add-category').css('display', 'none');
			
			jQuery('#ebayaffinity_catshiprule_' + jQuery(this).val()).attr('value', jQuery(this).closest('.ebayaffinity-setting-details-category-container').attr('data-id'));
			
			jQuery('.ebayaffinity-category-item[data-category-id='+jQuery(this).val()+']').remove()
			
			var template = jQuery('<div class="ebayaffinity-category-item">'+
					'<div class="ebayaffinity-bt-delete"> &times; </div>'+
					'<span class="ebayaffinity-category-unit ebayaffinity-category-leaf"></span>'+
				'</div>');
			template.find('span').text(jQuery('#ebayaffinity_catshiprule_' + jQuery(this).val()).attr('data-name'));
			template.attr('data-category-id', jQuery(this).val());
			jQuery(this).closest('.ebayaffinity-setting-details-category-container').find('.ebayaffinity-categories-applied-to').append(template);
			
			var ship_id = jQuery('#ebayaffinity_catshiprule_' + jQuery(this).val()).attr('value');
			var cont = jQuery(this).closest('.ebayaffinity-setting-details-category-container');
			
			jQuery('.ebayaffinity_catshiprules').each(function() {
				if (jQuery(this).attr('value') != ship_id) {
					cont.find('.ebayaffinity-bt-add-category').css('display', '');
				}
			});
			jQuery(this).remove();
			jQuery('.ebayaffinity-category-none').closest('.ebayaffinity-category-item').remove();
			jQuery('.ebayaffinity-categories-applied-to').each(function() {
				if (jQuery(this).find('.ebayaffinity-category-item').length == 0) {
					jQuery(this).html('<div class="ebayaffinity-category-item">'+
								'<span class="ebayaffinity-category-unit ebayaffinity-category-leaf ebayaffinity-category-none"><em>None as yet.</em></span>'+
							'</div>');
				}
			});
			ebayRemainingAll();
		});
	});
	
	jQuery('.ebayaffinity-inv-block').on('click', '.ebayaffinity-settings-action-button.ebayaffinity-bt-expand, .ebayaffinity-settings-action-button.ebayaffinity-bt-collapse', function() {
		if (jQuery(this).hasClass('ebayaffinity-bt-expand')) {
			jQuery(this).removeClass('ebayaffinity-bt-expand');
			jQuery(this).addClass('ebayaffinity-bt-collapse');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-category-container').css('display', 'block');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-product-container').css('display', 'block');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-extra-container').css('display', 'block');
            jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-discount-container').css('display', 'block');
		} else {
			jQuery(this).addClass('ebayaffinity-bt-expand');
			jQuery(this).removeClass('ebayaffinity-bt-collapse');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-category-container').css('display', '');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-product-container').css('display', '');
			jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-extra-container').css('display', '');
            jQuery(this).closest('.ebayaffinity-settingsblock').find('.ebayaffinity-setting-details-discount-container').css('display', '');
		}
	});
	
	jQuery('.ebayaffinity-inv-block').on('click', '.ebayaffinity-setting-details-product-container .ebayaffinity-bt-add-products', function() {
		ebayaffinity_ajax_title = 'Shipping Rules';
		ebayaffinity_shiprulenum = jQuery(this).closest('.ebayaffinity-setting-details-product-container').attr('data-id');
		ebayaffinity_ajax_callback = ebayaffinitySetProductsShipRule;
		ebayaffinityPullItemsAjax();
	});
	
	jQuery('.ebayaffinity-titleopt').on('click', '.ebayaffinity-bt-add-products', function() {
		ebayaffinity_ajax_title = 'Customise product title';
		ebayaffinity_shiprulenum = jQuery(this).closest('.ebayaffinity-setting-details-product-container').attr('data-id');
		ebayaffinity_ajax_callback = ebayaffinitySetProductsShipRule;
		ebayaffinity_ajax_filteroutprods = [];
		jQuery(this).closest('.ebayaffinity-setting-details-product-container').find('.ebayaffinity-product-item').each(function() {
			ebayaffinity_ajax_filteroutprods.push(jQuery(this).attr('data-product-id'));
		});
		ebayaffinityPullItemsAjax();
	});
	
	if (ebayaffinity_ajax_catmode) {
		if (ebayaffinity_ajax_success != '') {
			var template = jQuery('<div class="ebayaffinity-ajax-success"></div>');
			
			template.html(ebayaffinity_ajax_success);
			jQuery('#wpbody-content').prepend(template);
			setTimeout(function() {
				jQuery('.ebayaffinity-ajax-success').animate({'opacity': '0'}, {
					complete: function() {
						jQuery(this).remove();
					},
					duration: '100'
				});
			}, 1000);
		}
		ebayaffinity_ajax_success = '';
	
		jQuery('#ebayaffinity-checkboxall').click(function() {
			if (jQuery(this).is(":checked")) {
				jQuery('.ebayaffinity-checkbox').prop('checked', true);
			} else {
				jQuery('.ebayaffinity-checkbox').prop('checked', false);
			}
			
			if (jQuery('.ebayaffinity-checkbox:checked').length > 0) {
				jQuery('.ebayaffinity-header-some-selected').css('display', 'block');
			} else {
				jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
			}
		
			jQuery('.ebayaffinity-prod-box').remove();
			
			var prod_template = jQuery('<div class="ebayaffinity-prod-box">'+
						'<div class="ebayaffinity-prod-box-header"></div>'+
					'</div>');
					
			if (ebayaffinity_ajax_catmode == 1) {
				prod_template.addClass('ebayaffinity-prod-box-cat');
			}
					
			var j = jQuery('.ebayaffinity-checkbox:checked').length;
			
			if (j > 0) {		
				if (j == 1) {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' product selected');
				} else {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' products selected');
				}
				
				if (ebayaffinity_ajax_catmode == 1) {
					if (j == 1) {
						prod_template.find('.ebayaffinity-prod-box-header').text(j + ' category selected');
					} else {
						prod_template.find('.ebayaffinity-prod-box-header').text(j + ' categories selected');
					}
				}
				jQuery('.ebayaffinity-header-some-selected').prepend(prod_template);
			}
		});
		
		jQuery('.ebayaffinity-checkbox').change(function() {
			if (jQuery('.ebayaffinity-checkbox:checked').length > 0) {
				jQuery('.ebayaffinity-header-some-selected').css('display', 'block');
			} else {
				jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
			}
			
			if (jQuery('.ebayaffinity-checkbox:not(:checked)').length > 0) {
				jQuery('#ebayaffinity-checkboxall').prop('checked', false);
			} else {
				jQuery('#ebayaffinity-checkboxall').prop('checked', true);
			}
			
			jQuery('.ebayaffinity-prod-box').remove();
			
			var prod_template = jQuery('<div class="ebayaffinity-prod-box">'+
						'<div class="ebayaffinity-prod-box-header"></div>'+
					'</div>');
					
			if (ebayaffinity_ajax_catmode == 1) {
				prod_template.addClass('ebayaffinity-prod-box-cat');
			}
					
			var j = jQuery('.ebayaffinity-checkbox:checked').length;
			
			if (j > 0) {		
				if (j == 1) {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' product selected');
				} else {
					prod_template.find('.ebayaffinity-prod-box-header').text(j + ' products selected');
				}
				
				if (ebayaffinity_ajax_catmode == 1) {
					if (j == 1) {
						prod_template.find('.ebayaffinity-prod-box-header').text(j + ' category selected');
					} else {
						prod_template.find('.ebayaffinity-prod-box-header').text(j + ' categories selected');
					}
				}
				jQuery('.ebayaffinity-header-some-selected').prepend(prod_template);
			}
		});
		
		jQuery('#ebayaffinity-cancel').click(function() {
			jQuery('.ebayaffinity-checkbox:checked').click();
			jQuery('#ebayaffinity-checkboxall:checked').click();
			return false;
		});
		
		jQuery('.ebayaffinity-header:not(.ebayaffinity-header-remap) #ebayaffinity-confirmselected, #ebayaffinity-remap').click(function() {
			var arr = [];
			jQuery('.ebayaffinity-checkbox:checked').each(function() {
				arr.push([
					parseInt(jQuery(this).attr('name').replace('id[', '').replace(']', ''), 10),
					jQuery(this).attr('data-name'),
					jQuery(this).attr('data-title')
				]);
			});
			jQuery('.ebayaffinity-ajax-inv-block').css('display', 'none');
			jQuery('.ebayaffinity-header-some-selected').css('display', 'none');
			jQuery('.ebayaffinity-header-some-selected').addClass('ebayaffinity-header-some-selected-old');
			jQuery('.ebayaffinity-header-some-selected').removeClass('ebayaffinity-header');
			jQuery('.ebayaffinity-header-some-selected').removeClass('ebayaffinity-header-some-selected');
			jQuery('div[data-items-ajax-showafter]').css('display', '');
			jQuery('.ebayaffinity-header:not([data-items-ajax])').css('display', 'block');
			jQuery('.ebayaffinity-settingspages').css('display', 'block');
			ebayaffinitySetProductsToeBayCat(arr);
		});
		
		jQuery('.ebayaffinity-header.ebayaffinity-header-remap #ebayaffinity-confirmselected').click(function() {
			var templateform = jQuery('<form id="submitnow" method="post" "action="admin.php?page=ebay-sync-mapping&cat2cat=1"></form>');
			var data = [];
			jQuery('.ebayaffinity-checkbox:checked').each(function() {
				data.push([
					parseInt(jQuery(this).attr('name').replace('id[', '').replace(']', ''), 10),
					jQuery(this).attr('data-name'),
					jQuery(this).attr('data-title')
				]);
			});
			for (i in data) {
				if (data.hasOwnProperty(i)) {
					var template = jQuery('<input type="hidden" class="ebayaffinity_catcats"></input>');
					template.attr('name', 'ebayaffinity_catcats[' + data[i][0] + ']');
					template.attr('value', data[i][1]);
					templateform.append(template);
				}
			}
			jQuery('body').append(templateform);
			jQuery('#submitnow').submit();
		});
	}
	
	jQuery('.ebayaffinity-prod-map-cat').click(function() {
		jQuery('.ebayaffinity-big-error').css('display', 'none');
		jQuery('.ebayaffinity-inv-detail').css('display', 'none');
		ebayaffinitySetProductsToeBayCat([[jQuery('.ebayaffinity-inv-detail').attr('data-id'), jQuery('.ebayaffinity-inv-detail-title').text(), jQuery('.ebayaffinity-inv-detail-title').text()]]);
	});
	
	jQuery('.ebayaffinity-scrollerer').on('click', '.ebayaffinity-scrollbox', function() {
		var htm = jQuery(this).find('img').attr('data-big');
		var b = jQuery(this);
		jQuery('.ebayaffinity-inv-detail-main-left img').animate({'opacity': '0'}, {
			complete: function() {
				jQuery('.ebayaffinity-scrollbox').removeClass('ebayaffinity-scrollbox-selected');
				b.addClass('ebayaffinity-scrollbox-selected');
				jQuery('.ebayaffinity-inv-detail-main-left').html(htm);
				jQuery('.ebayaffinity-inv-detail-main-left img').css('opacity', '0');
				jQuery('.ebayaffinity-inv-detail-main-left img').animate({'opacity': '1' }, { 
					complete: function() {
						jQuery(this).css('opacity', '');
					},
					duration: '100'
				});
			},
			duration: '100'
		});
	});
	
	jQuery('.ebayaffinity-scrollerer-left').click(function() {
		if (ebayaffinity_scrollerer_anim == true) {
			return;
		}
		ebayaffinity_scrollerer_anim = true;
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox').last().addClass('ebayaffinity-scrollbox-todel');
		var template = jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox').last().clone(true);
		template.removeClass('ebayaffinity-scrollbox-todel');
		template.addClass('ebayaffinity-scrollbox-isnew');
		template.css('margin-left', '-104px');
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollerer').prepend(template);
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox-isnew').animate({'margin-left': '2px' }, { 
			complete: function() {
				jQuery(this).css('margin-left', '');
				jQuery(this).removeClass('ebayaffinity-scrollbox-isnew');
				jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox-todel').remove();
				ebayaffinity_scrollerer_anim = false;
			},
			duration: '100'
		});
		jQuery('.ebayaffinity-scrollbox-todel').remove();
	});
	
	jQuery('.ebayaffinity-scrollerer-right').click(function() {
		if (ebayaffinity_scrollerer_anim == true) {
			return;
		}
		ebayaffinity_scrollerer_anim = true;
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox').first().addClass('ebayaffinity-scrollbox-todel');
		var template = jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox').first().clone(true);
		template.removeClass('ebayaffinity-scrollbox-todel');
		template.addClass('ebayaffinity-scrollbox-isnew');
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollerer').append(template);
		
		jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox').first().animate({'margin-left': '-104px' }, { 
			complete: function() {
				jQuery(this).closest('.ebayaffinity-scrollerer-container').find('.ebayaffinity-scrollbox-todel').remove();
				jQuery(this).removeClass('ebayaffinity-scrollbox-isnew');
				ebayaffinity_scrollerer_anim = false;
			},
			duration: '100'
		});
	});
	
	ebayaffinityMakeCharts();
	
	setTimeout('ebayaffinityFixHeights()', 100);
	
	jQuery('.ebayaffinity-chart-period').click(function() {
		var period = jQuery(this).attr('data-period');
		if(period) {
			setBarChartPeriod(period);
		}
	});
	setBarChartPeriod(selectedBarChartPeriod);
	
	jQuery('.ebayaffinity-lasterror').hover(function() {
		jQuery(this).find('div').css('display', 'block');
		jQuery(this).find('div').css('margin-top', ((-1 * (jQuery(this).find('div').height() / 2)) + 10) + 'px');
		jQuery(this).find('div').css('display', '');
	});

	if (jQuery('#_ebaytemplate').length > 0) {
        var htm = jQuery('#_ebaydesc').val();

        fixeBayPrices();

        var container = jQuery('<div id="postbox-ebay-option" class="postbox">' +
        '<button type="button" class="handlediv button-link"><span class="screen-reader-text">Toggle panel: eBay Options</span><span class="toggle-indicator"></span></button>' +
        '<h2 class="hndle ui-sortable-handle"><span>eBay Options</span></h2>' +
        '<div id="postbox-ebay-option-inside" class="inside">' +
        '</div>' +
        '</div>');
        jQuery('#normal-sortables').prepend(container);

        // Sync button
        
        jQuery('#postbox-ebay-option-inside').prepend('<input id="syncnow" class="button" type="button" value="Save & Sync to eBay" name="Sync now" style="margin-top: 6px;"/><div style="clear: both"></div>');
        if (jQuery('#publish').attr('value') == 'Publish') {
        	jQuery('#syncnow').attr('value', 'Publish & Sync to eBay');
        }
        
        jQuery('#syncnow').click(function() {
            jQuery(this).addClass("disabled");
            jQuery(this).after('<input type="hidden" name="syncit" value="1"></input>');
            jQuery('#publish').click();
    		setTimeout(function() {
    			jQuery('#publish').trigger('click');
    		}, 1000);
    		setTimeout(function() {
    			jQuery('#publish').trigger('click');
    		}, 2000);
    		setTimeout(function() {
    			jQuery('#publish').trigger('click');
    		}, 3000);
    		setTimeout(function() {
    			jQuery('#publish').trigger('click');
    		}, 4000);
    		setTimeout(function() {
    			jQuery('#publish').trigger('click');
    		}, 5000);
        });
        
        var ttttt = jQuery('<div style="margin-top: 10px;"></div>');
        jQuery('._ebaycondition_field label').css('margin-right', '10px');
        ttttt.append(jQuery('._ebaycondition_field label'));
        ttttt.append(jQuery('._ebaycondition_field select'));
        ttttt.append(jQuery('<span style="font-size: 12px; margin-left: 20px;">* Availability of eBay conditions vary by category</span>'));

        jQuery('#postbox-ebay-option-inside').append(ttttt);

        // ebay copy option
        var copyOption = jQuery('<div style="margin-top: 10px; float:left;"><input type="checkbox" id="affinity-copy" autocomplete="off" checked name="affinity-copy"><label for="affinity-copy">Use eBay Description</label></div>');
        if (htm == '') {
            copyOption.find('#affinity-copy').prop('checked', false);
        }
        jQuery('#postbox-ebay-option-inside').append(copyOption);

        // ebay short desc option
        ttttt = jQuery('<div style="margin-top: 10px; margin-left: 20px; float:left;"></div>');

        if (jQuery('#_ebayuseshort').attr('value') == '5000') {
            jQuery('#_ebayuseshort').prop('checked', true);
            jQuery('#_ebayuseshort').attr('disabled', 'disabled');
        } else {
            jQuery('#_ebayuseshort').removeAttr('disabled');
        }

        ttttt.append(jQuery('._ebayuseshort_field input'));
        ttttt.append(jQuery('._ebayuseshort_field label'));

        jQuery('#postbox-ebay-option-inside').append(ttttt);

        // ebay template option
        ttttt = jQuery('<div style="margin-top: 10px; margin-left: 20px; float:left;"></div>');
        ttttt.append(jQuery('._ebaytemplate_field input'));
        ttttt.append(jQuery('._ebaytemplate_field label'));

        jQuery('#postbox-ebay-option-inside').append(ttttt);
        jQuery('#postbox-ebay-option-inside').append('<div style="clear: both"></div>');
        jQuery('#postbox-ebay-option-inside').append('<div id="ebay-desc-panel" style="margin-top: 10px;"></div>');

        jQuery('._ebaycondition_field').remove();
        jQuery('._ebaytemplate_field').remove();
        jQuery('._ebayuseshort_field').remove();

		var tt = jQuery('<div style="margin-top: 10px;">'+
			'<div id="postebaydesc" class="postbox " style="margin-bottom: 0; margin-top: 10px;" >'+
			'<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">Toggle panel: eBay Description</span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle"><span>eBay Description</span></h2>'+
			'<div class="inside">'+
			'<div id="wp-ebaydesc-wrap" class="wp-core-ui wp-editor-wrap tmce-active"><style>#wp-ebaydesc-editor-container .wp-editor-area{height:175px; width:100%;}</style>'+
			'<div id="wp-ebaydesc-editor-tools" class="wp-editor-tools hide-if-no-js"><div id="wp-ebaydesc-media-buttons" class="wp-media-buttons"><button type="button" class="button insert-media add_media" data-editor="ebaydesc"><span class="wp-media-buttons-icon"></span> Add Media</button></div>'+
			'<div class="wp-editor-tabs">'+
			'<button id="ebaydesc-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="ebaydesc" type="button">Visual</button>'+
			'<button id="ebaydesc-html" class="wp-switch-editor switch-html" data-wp-editor-id="ebaydesc" type="button">Text</button>'+
			'</div>'+
			'</div>'+
			'<div id="wp-ebaydesc-editor-container" class="wp-editor-container"><div id="qt_ebaydesc_toolbar" class="quicktags-toolbar"></div><textarea class="wp-editor-area" rows="20" autocomplete="off" cols="40" name="ebaydesc" id="ebaydesc"></textarea></div>'+
			'</div>');
			
		if (htm == '') {
			tt.find('#postebaydesc').css('display', 'none');
		}
	
		tt.find('#ebaydesc').val(htm);
		
		try {
			var mceObj = jQuery.extend(true, {}, tinyMCEPreInit.mceInit.content);
			mceObj.body_class = mceObj.body_class.replace('content', 'ebaydesc');
			mceObj.selector = '#ebaydesc';
			mceObj.cache_suffix = mceObj.cache_suffix + 'b';
			tinyMCEPreInit.mceInit.ebaydesc = mceObj;
			
		} catch (e) {
			//
		}
		
		jQuery('#ebay-desc-panel').append(tt);
		
		jQuery('#affinity-copy').change(function() {
			if (jQuery(this).is(":checked")) {
				jQuery('#postebaydesc').css('display', 'block');

				if (jQuery('#_ebayuseshort').is(':checked')) {
					try {
						tinymce.get('excerpt').save();
					} catch (e) {
						// 
					}
					var cont = jQuery('#excerpt').val();
					
				} else {
					try {
						tinymce.get('content').save();
					} catch (e) {
						// 
					}
					var cont = jQuery('#content').val();
				}
				try {
					jQuery('#ebaydesc').val(cont);
					tinyMCE.get('ebaydesc').setContent(cont);
				} catch (e) {
					//
				}

			} else {
				jQuery('#postebaydesc').css('display', 'none');
			}
		});
		
		try {
			tinymce.init(tinyMCEPreInit.mceInit.ebaydesc);
		} catch (e) {
			//
		}
		
		try {
			quicktags({'id': 'ebaydesc', 'name': 'qt_ebaydesc', 'buttons': 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close,dfw'});
		} catch (e) {
			//
		}

    }
	
	jQuery('#ebayaffinity_returnaccepted1, #ebayaffinity_returnaccepted2').change(function() {
		if (jQuery('#ebayaffinity_returnaccepted1').is(':checked')) {
			jQuery('#ebayaffinity_refundoption').closest('.ebayaffinity-setting').css('display', '');
			jQuery('#ebayaffinity_returncosts').closest('.ebayaffinity-setting').css('display', '');
			jQuery('#ebayaffinity_returnwithin').closest('.ebayaffinity-setting').css('display', '');
			jQuery('#ebayaffinity_customtemplate').closest('.ebayaffinity-setting').css('display', '');
		} else {
			jQuery('#ebayaffinity_refundoption').closest('.ebayaffinity-setting').css('display', 'none');
			jQuery('#ebayaffinity_returncosts').closest('.ebayaffinity-setting').css('display', 'none');
			jQuery('#ebayaffinity_returnwithin').closest('.ebayaffinity-setting').css('display', 'none');
			jQuery('#ebayaffinity_customtemplate').closest('.ebayaffinity-setting').css('display', 'none');
		}
	});
	
	jQuery('#ebayaffinity_logofilelabel').each(function() {
		if (window.File && window.FileList && window.FileReader) {
			jQuery.event.props.push('dataTransfer');
		
			jQuery('#ebayaffinity_logofilelabel').wrap('<div id="ebayaffinity_drop"></div>');
			
			jQuery('#ebayaffinity_drop').append('<input type="hidden" id="ebayaffinity_dropfile" name="ebayaffinity_dropfile" />');
			jQuery('#ebayaffinity_drop').prepend('<div>Drop file here<br><small>or</small></div>');
			jQuery('#ebayaffinity_drop').on('dragover', function(e) {
			    e.preventDefault();  
			    e.stopPropagation();
			});
			
			jQuery('#ebayaffinity_drop').on('dragleave', function(e) {
			    e.preventDefault();  
			    e.stopPropagation();
			    jQuery('#ebayaffinity_drop').css('border-color', '');
			});
			
			jQuery('#ebayaffinity_drop').on('dragenter', function(e) {
			    e.preventDefault();  
			    e.stopPropagation();
			    jQuery('#ebayaffinity_drop').css('border-color', '#3383db');
			});
			
			jQuery('#ebayaffinity_drop').on('drop', function(e) {
				e.preventDefault();  
    			e.stopPropagation();
				var files = e.target.files || e.dataTransfer.files;
				for (var i = 0; i < files.length; i++) {
					var reader = new FileReader();
					reader.onload = function(e) {
						if (jQuery('.ebayaffinity-settingsblock-outer-cell-img').length == 0) {
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock').wrap('<div class="ebayaffinity-settingsblock-outer"></div>');
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock').wrap('<div class="ebayaffinity-settingsblock-outer-cell"></div>');
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock-outer-cell').after('<div class="ebayaffinity-settingsblock-outer-cell"><div class="ebayaffinity-settingsblock-outer-cell-img">&nbsp;</div></div>');
						
							jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', 'auto');
							jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').parent().css('height'), 10) - (parseInt(jQuery('.ebayaffinity-settingsblock').css('margin-bottom'), 10) + 2) + 'px');
						}
						jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('background-image', 'none');
						jQuery('#ebayaffinity_imageid').remove();
						var t = jQuery('<div id="ebayaffinity_imageid" style="margin: 0 auto; background-position: center center; background-repeat: no-repeat; width: 250px; height: 250px; background-size: contain">');
						t.css('background-image', 'url(' + e.target.result + ')');
						t.css('margin-top', ((parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height'), 10)/2) - 145) + 'px');
						jQuery('.ebayaffinity-settingsblock-outer-cell-img').append(t);
						
						jQuery('#ebayaffinity_dropfile').val(e.target.result);
						jQuery('#ebayaffinity_drop').css('border-color', '');
						jQuery('#ebayaffinity_logofile')[0].value = null;
					}
					reader.readAsDataURL(files[i]);
				}
			});
			
			jQuery('#ebayaffinity_logofile').change(function() {
				if (this.files && this.files[0]) {
					var reader = new FileReader();

			        reader.onload = function (e) {
			            if (jQuery('.ebayaffinity-settingsblock-outer-cell-img').length == 0) {
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock').wrap('<div class="ebayaffinity-settingsblock-outer"></div>');
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock').wrap('<div class="ebayaffinity-settingsblock-outer-cell"></div>');
							jQuery('.ebayaffinity-settinglogo').closest('.ebayaffinity-settingsblock-outer-cell').after('<div class="ebayaffinity-settingsblock-outer-cell"><div class="ebayaffinity-settingsblock-outer-cell-img">&nbsp;</div></div>');
						
							jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', 'auto');
							jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').parent().css('height'), 10) - (parseInt(jQuery('.ebayaffinity-settingsblock').css('margin-bottom'), 10) + 2) + 'px');
						}
						jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('background-image', 'none');
						jQuery('#ebayaffinity_imageid').remove();
						var t = jQuery('<div id="ebayaffinity_imageid" style="margin: 0 auto; background-position: center center; background-repeat: no-repeat; width: 250px; height: 250px; background-size: contain">');
						t.css('background-image', 'url(' + e.target.result + ')');
						t.css('margin-top', ((parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height'), 10)/2) - 145) + 'px');
						jQuery('.ebayaffinity-settingsblock-outer-cell-img').append(t);
						jQuery('#ebayaffinity_dropfile').val('');
						jQuery('#ebayaffinity_drop').css('border-color', '');
			        }
			
			        reader.readAsDataURL(this.files[0]);
				}
			});
		}
	});
	
	if (jQuery('#ebayaffinity_logourl').length > 0) {
		jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', 'auto');
		jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').parent().css('height'), 10) - (parseInt(jQuery('.ebayaffinity-settingsblock').css('margin-bottom'), 10) + 2) + 'px');
		jQuery('#ebayaffinity_imageid').css('margin-top', ((parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height'), 10)/2) - 145) + 'px');
		
		jQuery(window).resize(function() {
			jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', 'auto');
			jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height', parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').parent().css('height'), 10) - (parseInt(jQuery('.ebayaffinity-settingsblock').css('margin-bottom'), 10) + 2) + 'px');
			jQuery('#ebayaffinity_imageid').css('margin-top', ((parseInt(jQuery('.ebayaffinity-settingsblock-outer-cell-img').css('height'), 10)/2) - 145) + 'px');
		});
	}
	
	jQuery('.ebayaffinity-rules').on('change', '.ebayaffinity_shiprule_standard_freeshipping, .ebayaffinity_shiprule_express_freeshipping', function() {
		if (jQuery(this).is(':checked')) {
			jQuery(this).closest('.ebayaffinity-settingset').find('input[type=text]').attr('disabled', 'disabled');
		} else {
			jQuery(this).closest('.ebayaffinity-settingset').find('input[type=text]').removeAttr('disabled');
		}
	});

	jQuery('#wpbody-content').on('click', 'div.ebayaffinity-ajax-inv-block a.page-numbers', function() {
		ebayaffinity_ajax_paged = jQuery(this).attr('data-paged');
		ebayaffinityPullItemsAjax();
		return false;
	});
	
	if (jQuery('.ebayaffinity-titleopt-help')) {
		ebayaffinity_help();
	}
	
	jQuery('.ebayaffinity-titleopt-help .ebayaffinity-available-attributes a.ebayaffinity-attributes-title').click(function() {
		try {
			history.pushState({}, null, jQuery(this).attr('href'));
		} catch (e) {
			location.hash = jQuery(this).attr('href');
		}
		return false;
	});
	
	jQuery('.ebayaffinity-bt-del-store').click(function() {
		var st = jQuery(document).scrollTop();
		
		var shouldDeleteCreatedListings = jQuery('.ebayaffinity-settings-reset-and-delete-section #resetAndDelete').is(":checked");;
		var msgWarning = "Resetting eBay Sync will delete all of your settings and unlink your eBay account. You will need to manually end any active listings on the eBay site.";
		if(shouldDeleteCreatedListings) {
			msgWarning = "Resetting eBay Sync will delete all of your settings, created products and unlink your eBay account.";
		}
		
		var t = jQuery('<div class="ebayaffinity-header-setup ebayaffinity-header-setup-reset">'+
				'<a href="#" class="ebayaffinity-header-setup-close">&times;</a>'+
				'<span>Reset eBay Sync</span>'+
				'<em>' + msgWarning + '.<br><br> Are you sure you want to proceed?</em>'+
				'<a href="admin.php?page=ebay-sync-settings&amp;wipe=1">Continue</strong>'+
			'</div>');
			
		t.css('margin-top', st + 'px');
			
		if(shouldDeleteCreatedListings) {
			t.find('a').attr('href', 'admin.php?page=ebay-sync-settings&wipe=1&deletelistings=1&hash=' + affinity_hash);
		}
		else {
			t.find('a').attr('href', 'admin.php?page=ebay-sync-settings&wipe=1&hash=' + affinity_hash);
		}
			
		jQuery('#wpbody-content').prepend(t);
	
		jQuery('#wpbody-content').prepend('<div class="ebayaffinity-header-setup-black">&nbsp;</div>');
		
		jQuery('.ebayaffinity-header-setup').css('opacity', '0');
		jQuery('.ebayaffinity-header-setup-black').css('opacity', '0');
		
		jQuery('.ebayaffinity-header-setup').animate({'opacity': '1'}, {
				complete: function() {
					jQuery('.ebayaffinity-header-setup').css('opacity', '');
					jQuery('.ebayaffinity-header-setup-black').css('opacity', '');
				},
				step: function(now, fx) {
					jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
				},
				duration: '100'
		});
		
		jQuery('.ebayaffinity-header-setup-black, .ebayaffinity-header-setup-close').click(function() {
			jQuery('.ebayaffinity-header-setup').animate({'opacity': '0'}, {
					complete: function() {
						jQuery('.ebayaffinity-header-setup').remove();
						jQuery('.ebayaffinity-header-setup-black').remove();
					},
					step: function(now, fx) {
						jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
					},
					duration: '100'
			});
			return false;
		});
	});
	
	jQuery('.ebayaffinity-template-unit, .ebayaffinity-inv-filter-price-slide, .ebayaffinity-inv-filter-price-slider, .ebayaffinity-inv-filter-price-slider-in, .ebayaffinity-inv-filter-price-slider-pricemin, .ebayaffinity-inv-filter-price-slider-pricemax').each(function() {
		jQuery(this).attr('unselectable', 'on');
		jQuery(this).attr('onselectstart', 'return false');
	});
	
	jQuery('#ebayaffinity-helpopen').click(function() {
		jQuery('#ebayaffinity-helpsection').css('display', 'block');
		jQuery(this).css('display', 'none');
	});
	
	jQuery('#ebayaffinity-helpsection a').click(function() {
		jQuery('#ebayaffinity-helpsection').css('display', 'none');
		jQuery('#ebayaffinity-helpopen').css('display', 'block');
		return false;
	});
	
	jQuery('#ebayaffinity-available-attributes-sortby').click(function() {
		if (jQuery(this).hasClass('ebayaffinity-available-attributes-sortbyon')) {
			jQuery(this).removeClass('ebayaffinity-available-attributes-sortbyon');
			jQuery('#ebayaffinity-available-attributes-sortbox').css('display', 'none');
		} else {
			jQuery(this).addClass('ebayaffinity-available-attributes-sortbyon');
			jQuery('#ebayaffinity-available-attributes-sortbox').css('display', 'block');
		}
		return false;
	});
	
	if (jQuery('#ebayaffinity-available-attributes-sortbox').length > 0) {
		jQuery(document).click(function(event) {
			if (jQuery(event.target).closest('#ebayaffinity-available-attributes-sortbox').length > 0) {
				//
			} else if (jQuery(event.target).attr('id') == 'ebayaffinity-available-attributes-sortbox') {
				//
			} else {
				jQuery('#ebayaffinity-available-attributes-sortby').removeClass('ebayaffinity-available-attributes-sortbyon');
				jQuery('#ebayaffinity-available-attributes-sortbox').css('display', 'none');
			}
		});
	}
	
	jQuery('#ebayaffinity-available-attributes-sortbox a').click(function() {
		var typeid = parseInt(jQuery(this).attr('data-id'), 10);
		jQuery('#ebayaffinity-available-attributes-sortbox a').removeClass('ebayaffinity-available-attributes-sortbox-selected');
		jQuery(this).addClass('ebayaffinity-available-attributes-sortbox-selected');
		jQuery('#ebayaffinity-available-attributes-sortby').removeClass('ebayaffinity-available-attributes-sortbyon');
		jQuery('#ebayaffinity-available-attributes-sortbox').css('display', 'none');
		
		if (typeid == 1) {
			jQuery(".ebayaffinity-available-attributes .ebayaffinity-template-unit").sort(function(a, b) {
				return parseInt(jQuery(b).attr('data-num'), 10) - parseInt(jQuery(a).attr('data-num'), 10);
			}).each(function() {
				var elem = jQuery(this);
				elem.remove();
				jQuery(elem).appendTo(".ebayaffinity-available-attributes");
			});
		} else if (typeid == 2) {
			jQuery(".ebayaffinity-available-attributes .ebayaffinity-template-unit").sort(function(a, b) {
				if (jQuery(a).text().toLowerCase() < jQuery(b).text().toLowerCase()) {
					return -1;
				} else if (jQuery(a).text().toLowerCase() > jQuery(b).text().toLowerCase()) {
					return 1;
				}
				return 0;
			}).each(function() {
				var elem = jQuery(this);
				elem.remove();
				jQuery(elem).appendTo(".ebayaffinity-available-attributes");
			});
		} else if (typeid == 3) {
			jQuery(".ebayaffinity-available-attributes .ebayaffinity-template-unit").sort(function(a, b) {
				if (jQuery(a).text().toLowerCase() < jQuery(b).text().toLowerCase()) {
					return 1;
				} else if (jQuery(a).text().toLowerCase() > jQuery(b).text().toLowerCase()) {
					return -1;
				}
				return 0;
			}).each(function() {
				var elem = jQuery(this);
				elem.remove();
				jQuery(elem).appendTo(".ebayaffinity-available-attributes");
			});
		} else if (typeid == 4) {
			jQuery(".ebayaffinity-available-attributes .ebayaffinity-template-unit").sort(function(a, b) {
				if (jQuery(a).find('.ebayaffinity-global-mark').length < jQuery(b).find('.ebayaffinity-global-mark').length) {
					return 1;
				} else if (jQuery(a).find('.ebayaffinity-global-mark').length > jQuery(b).find('.ebayaffinity-global-mark').length) {
					return -1;
				}
				return 0;
			}).each(function() {
				var elem = jQuery(this);
				elem.remove();
				jQuery(elem).appendTo(".ebayaffinity-available-attributes");
			});
		}
		jQuery('.ebayaffinity-available-attributes').append(jQuery('#ebayaffinity-global-ident'));
		
		return false;
	});
	
	jQuery('#ebayaffinity_usecustomtemplate').change(function() {
		if (jQuery(this).is(":checked")) {
			jQuery('#ebayaffinity_customtemplate').closest('.ebayaffinity-setting').css('display', '');
		} else {
			jQuery('#ebayaffinity_customtemplate').closest('.ebayaffinity-setting').css('display', 'none');
		}
	});
	
	jQuery('.ebayaffinity_customtemplatevars').click(function() {
		var st = jQuery("#ebayaffinity_customtemplate").scrollTop();
		var text = jQuery("#ebayaffinity_customtemplate");
	    var caret = text[0].selectionStart;
	    var textAreaText = text.val();
	    var textAdd = jQuery(this).attr('data-dat');
	    text.val(textAreaText.substring(0, caret) + textAdd + textAreaText.substring(caret));
		text[0].selectionStart = caret + textAdd.length;
		jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 0);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 4);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 10);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 20);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 40);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 60);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 80);
		setTimeout(function() {
			jQuery("#ebayaffinity_customtemplate").scrollTop(st);
		}, 100);
		return false;
	});
	
	jQuery('#ebay-link-settings-form3').submit(function() {
		var errors = [];
		var eerror = false;
		jQuery('.ebayaffinity-setting-details-category-container').each(function() {
			if (jQuery(this).closest('.ebayaffinity-settingsblock').css('display') != 'none') {
				var id = jQuery(this).attr('data-id');
				var a = parseFloat(jQuery('input[name=ebayaffinity_shiprule_standard_fee\\[' + id + '\\]]').val(), 10).toFixed(2);
				var b = parseFloat(jQuery('input[name=ebayaffinity_shiprule_express_fee\\[' + id + '\\]]').val(), 10).toFixed(2);
				var c = parseFloat(jQuery('input[name=ebayaffinity_shiprule_standard_additional_fee\\[' + id + '\\]]').val(), 10).toFixed(2);
				var d = parseFloat(jQuery('input[name=ebayaffinity_shiprule_express_additional_fee\\[' + id + '\\]]').val(), 10).toFixed(2);

				if (isNaN(a)) {
					a = '';
				}
				
				if (isNaN(b)) {
					b = '';
				}

				if (isNaN(c)) {
					c = a;
				}

				if (isNaN(d)) {
					d = b;
				}
				
				var e = jQuery('input[name=ebayaffinity_shiprule_oexpress_fee\\[' + id + '\\]]').val();
				
				if (e !== undefined) {
					if (e > 0 && (b == '' || b == '0.00')) {
						eerror = true;
					}
				}

				jQuery('input[name=ebayaffinity_shiprule_standard_fee\\[' + id + '\\]]').val(a);
				jQuery('input[name=ebayaffinity_shiprule_express_fee\\[' + id + '\\]]').val(b);
				jQuery('input[name=ebayaffinity_shiprule_standard_additional_fee\\[' + id + '\\]]').val(c);
				jQuery('input[name=ebayaffinity_shiprule_express_additional_fee\\[' + id + '\\]]').val(d);

				if ((!jQuery('input[name=ebayaffinity_shiprule_standard_freeshipping\\[' + id + '\\]]').is(':checked')) &&
						(!jQuery('input[name=ebayaffinity_shiprule_express_freeshipping\\[' + id + '\\]]').is(':checked')) &&
						(jQuery('input[name=ebayaffinity_shiprule_standard_fee\\[' + id + '\\]]').val() == '' || a == '0.00') &&
						(jQuery('input[name=ebayaffinity_shiprule_express_fee\\[' + id + '\\]]').val() == '' || b == '0.00') &&
						(jQuery('input[name=ebayaffinity_shiprule_standard_additional_fee\\[' + id + '\\]]').val() == '' || c == '0.00') &&
						(jQuery('input[name=ebayaffinity_shiprule_express_additional_fee\\[' + id + '\\]]').val() == '' || d == '0.00')) {
					errors.push(id);
				}
			}
		});
		if (eerror) {
			alert('You cannot remove express shipping from an existing shipping rule. Please create a new rule.');
			return false;
		}
		if (errors.length > 0) {
			alert('At least one shipping service must be selected for the following rules: ' + errors.join(', '));
			return false;
		} else {
			return true;
		}
	});
	
	jQuery('.ebayaffinity-open a').click(function() {
		var id = jQuery(this).attr('data-id');
		
		if (jQuery(this).closest('td').attr('rowspan') == '1') {
			jQuery('.ebayaffinity-open1-' + id+' td').eq(0).attr('rowspan', '2');
			jQuery('.ebayaffinity-open2-' + id).css('display', '');
			jQuery('.ebayaffinity-open2-' + id+' td').eq(0).css('display', '');
		} else {
			jQuery('.ebayaffinity-open1-' + id+' td').eq(0).attr('rowspan', '1');
			jQuery('.ebayaffinity-open2-' + id).css('display', 'none');
			jQuery('.ebayaffinity-open2-' + id+' td').eq(0).css('display', 'none');
		}
		return false;
	});
	
	jQuery('#ebayaffinity-unmapped').change(function() {
		location.href = jQuery(this).val();
	});
	
	jQuery('#ebayaffinity-paypalremove').click(function() {
		jQuery(this).closest('div').find('input').attr('type', 'text');
		jQuery(this).remove();
		jQuery('.ebayaffinity-paypalremove').remove();
		jQuery('#ebayaffinity-paypalsave').css('display', '');
		return false;
	});
	
	jQuery('#ebayaffinity-paypalsave').click(function() {
		jQuery(this).closest('form').submit();
		return false;
	});
	
	jQuery('.ebayaffinity-inv-table').on('change', '#invvisprodall', function() {
		if (jQuery(this).is(':checked')) {
			jQuery('.invvisprod').prop('checked', true);
		} else {
			jQuery('.invvisprod').prop('checked', false);
		}
	});
	
	jQuery('.ebayaffinity-inv-table').on('change', '.invvisprod', function() {
		if (jQuery('.invvisprod').length == jQuery('.invvisprod:checked').length) {
			jQuery('#invvisprodall').prop('checked', true);
		} else {
			jQuery('#invvisprodall').prop('checked', false);
		}
	});
	
	jQuery('select[name=selectinvvisprodall]').change(function() {
		if (jQuery('.invvisprod:checked').length == 0) {
			alert('Please select some products.');
			jQuery(this).val(0);
			return false;
		}
		var visibility;
		var ids = [];
		
		if (jQuery(this).val() == 1) {
			visibility = true;
		} else if (jQuery(this).val() == 2) {
			visibility = false;
		} else {
			return;
		}
		jQuery(this).val(0);
		
		jQuery('.invvisprod:checked').each(function() {
			ids.push(jQuery(this).closest('tr').attr('data-id'));
		});
		
		var thisone = jQuery(this);
		var st = jQuery(document).scrollTop();
		var t = jQuery('<div class="ebayaffinity-header-setup">'+
				'<a href="#" class="ebayaffinity-header-setup-close">&times;</a>'+
				'<span>Change visibility</span>'+
				'<em></em>'+
				'<a href="#" id="ebayaffinity-header-continue">Continue</strong>'+
			'</div>');
			
		if (visibility == false) {
			t.find('em').text('Warning. Disabling the visibility for these products may end your listing on eBay. Are you sure you want to proceed?');
		} else {
			t.find('em').text('Warning. Enabling the visibility for these products may create a new listing on eBay. Are you sure you want to proceed?');
		}
			
		t.css('margin-top', st + 'px');
			
		jQuery('#wpbody-content').prepend(t);
	
		jQuery('#wpbody-content').prepend('<div class="ebayaffinity-header-setup-black">&nbsp;</div>');
		
		jQuery('.ebayaffinity-header-setup').css('opacity', '0');
		jQuery('.ebayaffinity-header-setup-black').css('opacity', '0');
		
		jQuery('.ebayaffinity-header-setup').animate({'opacity': '1'}, {
				complete: function() {
					jQuery('.ebayaffinity-header-setup').css('opacity', '');
					jQuery('.ebayaffinity-header-setup-black').css('opacity', '');
				},
				step: function(now, fx) {
					jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
				},
				duration: '100'
		});
		
		jQuery('.ebayaffinity-header-setup-black, .ebayaffinity-header-setup-close').click(function() {
			jQuery('.ebayaffinity-header-setup').animate({'opacity': '0'}, {
					complete: function() {
						jQuery('.ebayaffinity-header-setup').remove();
						jQuery('.ebayaffinity-header-setup-black').remove();
					},
					step: function(now, fx) {
						jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
					},
					duration: '100'
			});
			return false;
		});
	
		jQuery('#ebayaffinity-header-continue').click(function() {
			jQuery('.ebayaffinity-header-setup').animate({'opacity': '0'}, {
				complete: function() {
					jQuery('.ebayaffinity-header-setup').remove();
					jQuery('.ebayaffinity-header-setup-black').remove();
				},
				step: function(now, fx) {
					jQuery('.ebayaffinity-header-setup-black').css('opacity', now / 2);
				},
				duration: '100'
			});
			var parent = jQuery(thisone);
			if (visibility == false) {
				for (var i = 0; i < ids.length; i+= 1) {
					jQuery('tr[data-id=' + ids[i] + '] .ebayaffinity-switch-on-off').addClass('ebayaffinity-switch-off');
					jQuery('tr[data-id=' + ids[i] + '] .ebayaffinity-switch-on-off').removeClass('ebayaffinity-switch-on');
				}
				
				jQuery.ajax({
					method: "POST",
					url: ajaxurl,
					data: { action: 'blockunblock', id: ids.join(','), blocked: '1' }
				});
				jQuery(thisone).find('div').animate({'margin-left': '0px'}, {
					complete: function() {
						parent.removeClass('ebayaffinity-switch-on');
						parent.addClass('ebayaffinity-switch-off');
						parent.css('background-color', '');
						parent.css('border-color', '');
					},
					step: function(now, fx) {
						var cs = ebayaffinityColourScale((now / 20));
						parent.css('background-color', cs);
						parent.css('border-color', cs);
					},
					duration: '100'
				});
			} else {
				for (var i = 0; i < ids.length; i+= 1) {
					jQuery('tr[data-id=' + ids[i] + '] .ebayaffinity-switch-on-off').removeClass('ebayaffinity-switch-off');
					jQuery('tr[data-id=' + ids[i] + '] .ebayaffinity-switch-on-off').addClass('ebayaffinity-switch-on');
				}
				
				jQuery.ajax({
					method: "POST",
					url: ajaxurl,
					data: { action: 'blockunblock', id: ids.join(','), blocked: '0' }
				});
				jQuery(thisone).find('div').animate({'margin-left': '20px'}, {
					complete: function() {
						parent.removeClass('ebayaffinity-switch-off');
						parent.addClass('ebayaffinity-switch-on');
						parent.css('background-color', '');
						parent.css('border-color', '');
					},
					step: function(now, fx) {
						var cs = ebayaffinityColourScale((now / 20));
						parent.css('background-color', cs);
						parent.css('border-color', cs);
					},
					duration: '100'
				});
			}
			return false;
		});
	});
	
	jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').attr('href', 'https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
	jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').unbind();
	jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').click(function() {
		window.open('https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
		return false;
	});
	
	setTimeout(function() {
		jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').unbind();
		jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').click(function() {
			window.open('https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
			return false;
		});
		setTimeout(function() {
			jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').unbind();
			jQuery('tr[data-plugin="ebaysync\/ebaysync.php"] .open-plugin-details-modal').click(function() {
				window.open('https://ebaysync.zendesk.com/hc/en-us/categories/203792408-eBay-Sync-for-WooCommerce');
				return false;
			});
		}, 500);
	}, 500);
	
	jQuery('.ebayaffinity-titleopt').on('change', 'input.ebayaffinity-rule-title', function() {
		if (jQuery(this).val() == '') {
			jQuery(this).val(jQuery(this).attr('data-orig'));
		}
	});
});


/*
 * Item Specific Mappings
 */
function EbayAffinityItemSpecificsMapping(id, ecommerceItemSpecificId, ecommerceItemSpecificName, ebayItemSpecificId, customTypedName, mappedName) {
	this.id = id;
	this.ecommerceItemSpecificId = ecommerceItemSpecificId;
	this.ecommerceItemSpecificName = ecommerceItemSpecificName;
	this.ebayItemSpecificId = ebayItemSpecificId;
	this.customTypedName = customTypedName;
	this.mappedName = mappedName;
};

EbayAffinityItemSpecificsMapping.prototype.mapAffinityItemSpecificsMappings = {};

EbayAffinityItemSpecificsMapping.prototype.constructor = EbayAffinityItemSpecificsMapping;

EbayAffinityItemSpecificsMapping.prototype.get = function(id) {
	return EbayAffinityItemSpecificsMapping.prototype.mapAffinityItemSpecificsMappings[id];
};

EbayAffinityItemSpecificsMapping.prototype.getAll = function() {
	return EbayAffinityItemSpecificsMapping.prototype.mapAffinityItemSpecificsMappings;
};

EbayAffinityItemSpecificsMapping.prototype.filterByEcommerceItemSpecificName = function(ecommerceItemSpecificName) {
	var mapAllItemSpecifics = EbayAffinityItemSpecificsMapping.prototype.getAll();
	var objCurrentItemSpecific = null;
	var hasAnyVisibleMapping = false;

	for(var objId in mapAllItemSpecifics) {
		objCurrentItemSpecific = mapAllItemSpecifics[objId];
		ecommerceItemSpecificName = ecommerceItemSpecificName.toLowerCase();

		if(objCurrentItemSpecific.ecommerceItemSpecificName.toLowerCase().indexOf(ecommerceItemSpecificName) === -1 && objCurrentItemSpecific.ebayItemSpecificId.toLowerCase().indexOf(ecommerceItemSpecificName) === -1) {
			EbayAffinityItemSpecificsMapping.prototype.changeVisibilityOfItemSpecificMappingTableRow(objCurrentItemSpecific.id, false);
		}
		else {
			EbayAffinityItemSpecificsMapping.prototype.changeVisibilityOfItemSpecificMappingTableRow(objCurrentItemSpecific.id, true);
			hasAnyVisibleMapping = true;
		}
	}

	if(hasAnyVisibleMapping) {
		jQuery("#ebayaffinity-item-specifics-not-found").hide();
		jQuery("#ebayaffinity-item-specifics-found").show();
	}
	else {
		jQuery("#ebayaffinity-item-specifics-found").hide();
		jQuery("#ebayaffinity-item-specifics-not-found").show();
	}

};

EbayAffinityItemSpecificsMapping.prototype.changeVisibilityOfItemSpecificMappingTableRow = function(objId, show) {
	if(show) {
		jQuery(".ebayaffinity-itemspecific-mapping-table").find("tr[data-id='" + objId + "']").show();
	}
	else {
		jQuery(".ebayaffinity-itemspecific-mapping-table").find("tr[data-id='" + objId + "']").hide();
	}
};

EbayAffinityItemSpecificsMapping.prototype.save = function(id) {
	var updatedItemSpecificMappingObj = this.get(id);
	var jQueryRow = jQuery(".ebayaffinity-itemspecific-mapping-table").find("tr[data-id='" + id + "']");

	updatedItemSpecificMappingObj.ebayItemSpecificId = jQueryRow.find(".ebayaffinity-select").val();
	updatedItemSpecificMappingObj.customTypedName = jQueryRow.find(".iptCustomItemSpecific").val();

	jQuery.ajax({
		method: "POST",
		url: ajaxurl,
		dataType: 'json',
		data: { 
			action: 'ebayitemspecificmapping', 
			obj: JSON.stringify(updatedItemSpecificMappingObj)
		},
		success: function(data) {},
		error: function() {
			//
		}
	});
};

EbayAffinityItemSpecificsMapping.prototype.initialize = function() {
	jQuery("document").ready(function() {
		//Selected an eBay item specific name
		jQuery(".ebayaffinity-select").change(function(e) {
			var jQueryRow = jQuery(this).parents("tr");
			var objId = jQueryRow.attr("data-id");

			jQueryRow.find(".iptCustomItemSpecific").val("");
			EbayAffinityItemSpecificsMapping.prototype.save(objId);
			
			if (jQuery(this).find("option:selected").text() == '+ Custom') {
				jQuery(this).closest('tr').find(".iptCustomItemSpecific").css('display', '');
			} else {
				jQuery(this).closest('tr').find(".iptCustomItemSpecific").css('display', 'none');
			}
		});

		//Typed custom name
		jQuery(".iptCustomItemSpecific").change(function(e) {
			var jQueryRow = jQuery(this).parents("tr");
			var objId = jQueryRow.attr("data-id");

			EbayAffinityItemSpecificsMapping.prototype.save(objId);
		});

		//Searching Woobay attributes
		jQuery("#ebayaffinity-item-specific-search").keyup(function(e) {
			EbayAffinityItemSpecificsMapping.prototype.filterByEcommerceItemSpecificName(jQuery(this).val());
		});
		
		//Initialize containers
		var hasAnyAttribute = (Object.keys(EbayAffinityItemSpecificsMapping.prototype.mapAffinityItemSpecificsMappings).length > 0);
		if(hasAnyAttribute) {
			jQuery("#ebayaffinity-item-specifics-not-found").hide();
			jQuery("#ebayaffinity-item-specifics-found").show();
		}
		else {
			jQuery("#ebayaffinity-item-specifics-found").hide();
			jQuery("#ebayaffinity-item-specifics-not-found").show();
		}
	});
};
/*
 * End Item Specific Mappings
 */

jQuery("document").ready(function() {
	jQuery('#wpbody-content').on('hover', '.ebayaffinity-tooltip', function() {
		jQuery(".ebayaffinity-tooltip-content").remove();
		
		var r = false;
		if (jQuery(this).hasClass('ebayaffinity-tooltipr')) {
			r = true;
		}
		
		var tooltipLink = jQuery(this).find("a");
		var topPosition = tooltipLink.offset().top + 10;
		var leftPosition = tooltipLink.offset().left - 40;
		var content = jQuery(this).attr("data-tooltip");

		var tooltipElement = jQuery("<div class='ebayaffinity-tooltip-content'></div>");
		if (r) {
			tooltipElement.addClass('ebayaffinity-tooltip-contentr');
		}
		tooltipElement.text(content);

		jQuery("body").append(tooltipElement);
		if (r) {
			tooltipElement.css("top", (topPosition - 35) + "px");
			tooltipElement.css("left", (leftPosition - tooltipElement.width()) + 420 + "px");
		} else {
			tooltipElement.css("top", topPosition + "px");
			tooltipElement.css("left", leftPosition - tooltipElement.width() + "px");
		}
	});
	jQuery('#wpbody-content').on('mouseout', '.ebayaffinity-tooltip', function() {
		jQuery(".ebayaffinity-tooltip-content").remove();
	});
});

/*
 * Order Fields Controller
 */
jQuery("document").ready(function() {
	if(jQuery("#order_data").length < 1 || jQuery("#_affinity_tracking_number").length < 1) {
		return;
	}
	
	jQuery("#_affinity_tracking_number").keyup(function() {
		if(jQuery(this).val().length > 0) {
			jQuery("#_affinity_marked_as_sent").prop("checked", true);
		}
		else {
			jQuery("#_affinity_marked_as_sent").prop('checked', false);
		}
	});
	
	jQuery("#_affinity_carrier_name").parents("form").submit(function() {
		if(jQuery("#_affinity_tracking_number").val().length > 0 && jQuery("#_affinity_carrier_name").val() === "") {
			alert("A carrier must be selected if you insert a tracking number");
			return false;
		}
		if(jQuery("#_affinity_tracking_number").val().length === 0 && jQuery("#_affinity_carrier_name").val() !== "") {
			alert("A tracking number must be inserted if you provide a carrier name");
			return false;
		}
		
		return true;
	});
	
	jQuery("#_affinity_marked_as_sent").change(function() {
		if(typeof jQuery(this).attr("checked") === "undefined") {
			jQuery("#_affinity_tracking_number").val("");
			jQuery("#_affinity_carrier_name").val("");
		}
	});
});
/*
 * End Order Fields Controller
 */