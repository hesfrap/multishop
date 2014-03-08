<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$output=array();
if ($this->ADMIN_USER) {
	$include_disabled_products=1;
} else {
	$include_disabled_products=0;
}
$product=mslib_fe::getProduct($this->get['products_id'],$this->get['categories_id'],'',$include_disabled_products);
if (!$product['products_id']) {
	header('HTTP/1.0 404 Not Found');	
	$output_array['http_header']='HTTP/1.0 404 Not Found';
	$content='<div class="main-title"><h1>The product is not existing</h1></div>';
} else {
	if ($product['minimum_quantity'] > 0) {
		$qty=$product['minimum_quantity'];
	} else {
		$qty=1;
	}
	if(!$this->conf['disableMetatags']) {		
		// meta tags
		if ($product['products_meta_title']) {
			$this->ms['title']	=	$product['products_meta_title'];
		} else {
			$this->ms['title']	=	$product['products_name'];
		}
		$output_array['meta']['title'] 			= '<title>'.htmlspecialchars($this->ms['title']).$this->ms['MODULES']['PAGE_TITLE_DELIMETER'].$this->ms['MODULES']['STORE_NAME'].'</title>';		
		if ($product['products_meta_description']) {
			$this->ms['description']=$product['products_meta_description'];
		} elseif ($product['products_shortdescription']) {
			$this->ms['description']=$product['products_shortdescription'];
		} else {
			$this->ms['description']='';
		}
		//Product information: '.$product['products_name'].'. Order now!
		if ($this->ms['description']) {
			$output_array['meta']['description'] = '<meta name="description" content="'.htmlspecialchars($this->ms['description']).'" />';	
		}
		if ($product['products_meta_keywords']) {
			$output_array['meta']['keywords'] 	= '<meta name="keywords" content="'.htmlspecialchars($product['products_meta_keywords']).'" />';		
		}
		// meta tags eof	
	}
	// facebook image and open graph
	$where='';
	if ($product['categories_id']) {
			// get all cats to generate multilevel fake url
			$level=0;
			$cats=mslib_fe::Crumbar($product['categories_id']);
			$cats=array_reverse($cats);
			$where='';
			if (count($cats) > 0) {
					foreach ($cats as $cat) {
							$where.="categories_id[".$level."]=".$cat['id']."&";
							$level++;
					}
					$where=substr($where,0,(strlen($where)-1));
					$where.='&';
			}
			// get all cats to generate multilevel fake url eof
	}
	$link=mslib_fe::typolink($this->conf['products_detail_page_pid'],'&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
	if ($product['products_image']) {
		$output_array['meta']['image_src']='
		<link rel="image_src" href="'.$this->FULL_HTTP_URL.mslib_befe::getImagePath($product['products_image'],'products','300').'" />
		<meta property="og:image" content="'.$this->FULL_HTTP_URL.mslib_befe::getImagePath($product['products_image'],'products','300').'" />
		';
	}
	$output_array['meta'][]='
	<meta property="og:title" content="'.htmlspecialchars($product['products_name']).'" />
	<meta property="og:type" content="product" />
	'.($product['products_date_added']?'<meta property="article:published_time" content="'.date("Y-m-d",$product['products_date_added']).'" />':'').'
	'.($product['products_date_modified']?'<meta property="article:modified_time" content="'.date("Y-m-d",$product['products_date_modified']).'" />':'').'
	<meta property="og:url" content="'.$this->FULL_HTTP_URL.$link.'" />
	';
	// facebook image and open graph eof
	
	// putting the product vars in an array which will be marked and replaced in dynamic tmpl file
	// products pagination module
	if ($this->ms['MODULES']['PRODUCTS_DETAIL_PAGE_PAGINATION']) {
		// get previous / next record
		$pagination_items=mslib_fe::getNextPreviousProduct($product['products_id'],$product['categories_id']);
		$pagination.='<div id="products_detail_pagination">';
		if ($pagination_items['previous_item']) {
			$pagination.='<div class="pagination_previous"><a href="'.$pagination_items['previous_item'].'">'.$this->pi_getLL('previous').'</a></div>';
		} else {
			$pagination.='<div class="pagination_previous_disabled"><span>'.$this->pi_getLL('previous').'</span></div>';		
		}
		if ($pagination_items['next_item']) {
			$pagination.='<div class="pagination_next"><a href="'.$pagination_items['next_item'].'">'.$this->pi_getLL('next').'</a></div>';
		} else {
			$pagination.='<div class="pagination_next_disabled"><span>'.$this->pi_getLL('next').'</span></div>';
		}
		$pagination.='</div>';
		$output['pagination']=$pagination;
	} 
	// products pagination module eof
	$output['products_name'] .=$product['products_name'];
	if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
		$output['products_name'].='<div class="admin_menu"><a href="'.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=admin_ajax&cid='.$product['categories_id'].'&pid='.$product['products_id']).'&action=edit_product" onclick="return hs.htmlExpand(this, { objectType: \'iframe\', width: 910, height: 500} )" class="admin_menu_edit">Edit</a> <a href="'.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=admin_ajax&cid='.$product['categories_id'].'&pid='.$product['products_id']).'&action=delete_product" onclick="return hs.htmlExpand(this, { objectType: \'iframe\', width: 910, height: 140} )" class="admin_menu_remove" title="Remove"></a></div>';
	}
	$final_price=mslib_fe::final_products_price($product);
	if ($product['tax_id'] and $this->ms['MODULES']['SHOW_PRICES_WITH_AND_WITHOUT_VAT']) {
		$tax=mslib_fe::getTaxById($product['tax_id']);	
		if ($tax) {
			if ($product['staffel_price'] >0) {
				$price_excl_vat=(mslib_fe::calculateStaffelPrice($product['staffel_price'],$qty)/$qty);
			} else {
				$price_excl_vat=$product['final_price'];
			}
			$sub_content.='<div class="price_excluding_vat">'.$this->pi_getLL('excluding_vat').' '.mslib_fe::amount2Cents($price_excl_vat).'</div>';		
		}
	}
	$staffel_price_hid = '';
	if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
		$staffel_price_hid = '<input type="hidden" name="staffel_price" id="staffel_price" value="'.$product['staffel_price'].'" readonly/>';
	}
	$output['products_price']='<div class="price_div">';
	if ($product['products_price'] <> $product['final_price']) {
		if (!$this->ms['MODULES']['DB_PRICES_INCLUDE_VAT'] and ($product['tax_rate'] and $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'])) {
			$old_price=$product['products_price']*(1+$product['tax_rate']);
		} else {
			$old_price=$product['products_price'];		
		}
		if ($old_price) {
			$output['products_price']		.='<div class="old_price">'.mslib_fe::amount2Cents($old_price).'</div>';
		}
		$output['products_price'] .='	<input type="hidden" name="price_hid" id="price_default" value="'.$final_price.'"/>
			'.$staffel_price_hid.'
			<div class="specials_price">'.mslib_fe::amount2Cents($final_price).'</div>												
		';		
	} else {
		$output['products_price'] .='
		<input type="hidden" name="price_hid" id="price_default" value="'.$final_price.'"/>
		<input type="hidden" name="price" id="price" value="'.$final_price.'" readonly/>
		'.$staffel_price_hid.'
		<div class="specials_price">'.mslib_fe::amount2Cents($final_price).'</div>										 
	  ';		
	}	
	$output['products_price'].=$sub_content.'</div>';
	// show selectbox by products multiplication or show default input
	if ($this->get['tx_multishop_pi1']['cart_item']) {
		$cart = $GLOBALS['TSFE']->fe_user->getKey('ses',$this->cart_page_uid);			
		$qty=$cart['products'][$this->get['tx_multishop_pi1']['cart_item']]['qty'];
	}	
	$quantity_html='';
	if ($product['maximum_quantity'] > 0 or (is_numeric($product['products_multiplication']) and $product['products_multiplication'] > 0)) {
		if ($product['maximum_quantity'] > 0) {
			$ending_number=$product['maximum_quantity'];
		}
		if ($product['minimum_quantity'] > 0) {
			$start_number=$product['minimum_quantity'];
		}
		elseif($product['products_multiplication'])	{
			$start_number=$product['products_multiplication'];
		}
		if (!$start_number) {
			$start_number=1;
		}
		$quantity_html.='<select name="quantity" id="quantity">';
		$count=0;
		$steps=10;
		if ($product['maximum_quantity'] and $product['products_multiplication']) {
			$steps=floor($product['maximum_quantity']/$product['products_multiplication']);
		} elseif ($product['maximum_quantity'] and !$product['products_multiplication']) {
			$steps=($ending_number-$start_number)+1;
		}
		$count=$start_number;
		for ($i=0;$i<$steps;$i++) {
			if ($product['products_multiplication']) {
				$item=$product['products_multiplication'];
			} elseif($i) {
				$item=1;
			}
			$count=($count+$item);
			$quantity_html.='<option value="'.$count.'"'.($qty==$count?' selected':'').'>'.$count.'</option>';
		}
		$quantity_html.='</select>
		';	
	} else {
		$quantity_html.='<input type="text" name="quantity" size="5" id="quantity" value="'.$qty.'" />';
	}
	// show selectbox by products multiplication or show default input eof
	$output['quantity']='
	<div class="quantity">
		<label>'. $this->pi_getLL('quantity') .'</label> 
		'.$quantity_html.'
	</div>
	';
	$output['back_button']='
	<div onClick="history.back();return false;" class="back_button">'.$this->pi_getLL('back').'</div>
	';	
	if ($this->ms['MODULES']['SHOW_STOCK_LEVEL_AS_BOOLEAN'] != 'no') {
		switch ($this->ms['MODULES']['SHOW_STOCK_LEVEL_AS_BOOLEAN']) {
			case 'yes_with_image':
				if ($product['products_quantity']) {
					$product['products_quantity']='Voorraad: <img src="'.t3lib_extMgm::siteRelPath($this->extKey).'templates/images/icons/status_green.png" alt="'.htmlspecialchars($this->pi_getLL('in_stock')).'" />';
				} else {
					$product['products_quantity']='Voorraad: <img src="'.t3lib_extMgm::siteRelPath($this->extKey).'templates/images/icons/status_red.png" alt="'.htmlspecialchars($this->pi_getLL('not_in_stock')).'" />';
				}
			break;
				case 'yes_without_image':
				if ($product['products_quantity']) {
					$product['products_quantity']=$this->pi_getLL('admin_yes');
				} else {
					$product['products_quantity']=$this->pi_getLL('admin_no');
				}
			break;
		}
	}
	$output['products_quantity'] = $product['products_quantity'];
	
	$output['products_category'] ='Category: '.$product['categories_name'];
	$output['products_relatives'] =mslib_fe::getProductRelativesBox($product);
	$output['customers_also_bought'] =mslib_fe::getProductRelativesBox($product,'customers_also_bought');
	$tab_header='';
	$tab_content='';
	if ($this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']) {
			for ($i=1;$i<=$this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS'];$i++) {
					if ($product['products_description_tab_content_'.$i]) {
						$tab_header.='<li><a href="#products_description_tab_'.$i.'"><h1>'.$product['products_description_tab_title_'.$i].'</h1></a></li>';
						$tab_content.='
						<div id="products_description_tab_'.$i.'" class="tab_content">
								'.$product['products_description_tab_content_'.$i].'
						</div>
						';
					}
			}
	}
	$output['products_description'].='
		<ul class="tabs">
			'.$tab_header.'
		</ul>
		<div class="tab_container">
			'.$tab_content.'
		</div>
		<script type="text/javascript"> 
			jQuery(document).ready(function($) {
				jQuery(".tab_content").hide(); 
				jQuery("ul.tabs li:first").addClass("active").show();
				jQuery(".tab_content:first").show();
				jQuery("ul.tabs li").click(function() {
					jQuery("ul.tabs li").removeClass("active");
					jQuery(this).addClass("active"); 
					jQuery(".tab_content").hide();
					var activeTab = jQuery(this).find("a").attr("href");
					jQuery(activeTab).show();
					return false;
				});
			});
		</script>
	';	

	$output['products_extra_description'] = $product['products_extra_description'] ;
	$output['products_image']		='
	<div class="image">
	';
	if ($product['products_image']) {
		$image='
		<a id="thumb_0" rel="'.$this->conf['jQueryPopup_rel'].'" class="'.$this->conf['jQueryPopup_rel'].'" href="'.mslib_befe::getImagePath($product['products_image'],'products','normal').'"><img src="'.mslib_befe::getImagePath($product['products_image'],'products','300').'"></a>		
		';
	} else {
		$image='<div class="no_image"></div>';
	}
	$output['products_image'].=$image.'
	</div>
	';
	$tmpoutput='';
	for ($i=1;$i<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES'];$i++) {
		if ($product['products_image'.$i]) {
			$tmpoutput.='
			<a id="thumb_'.$i.'" rel="'.$this->conf['jQueryPopup_rel'].'" class="'.$this->conf['jQueryPopup_rel'].'" href="'.mslib_befe::getImagePath($product['products_image'.$i],'products','normal').'"><img src="'.mslib_befe::getImagePath($product['products_image'.$i],'products','50').'"></a>		
			';
		}
	}
	if ($tmpoutput) {
		$output['products_image_more'].='<div class="more_product_images">'.$tmpoutput.'</div>';	
	}
	// loading the attributes	
	$output['product_attributes']=mslib_fe::showAttributes($product['products_id'],$product['tax_rate']);
	// loading the attributes eof
	// add to basket
	if ($this->ms['MODULES']['AFFILIATE_SHOP'] and $product['products_url']) {
		if (!strstr($product['products_url'],'http://') and !strstr($product['products_url'],'http://')) {
			$product['products_url']='http://'.$product['products_url'];
		}
		$order_now_button.='<input id="multishop_add_to_cart" name="Submit" type="button" value="'.$this->pi_getLL('add_to_basket').'" onclick="window.open(\''.$product['products_url'].'\', \'\',\'\'); return false" />';	
	} else {
		$order_now_button.='<input id="multishop_add_to_cart" name="Submit" type="submit" value="'.htmlspecialchars($this->pi_getLL('add_to_basket')).'" />';
	}
	$output['add_to_cart_button'].='<input name="products_id" id="products_id" type="hidden" value="'.$product['products_id'].'" />'.$order_now_button;
	
	// add to basket eof	
	// now parse all the objects in the tmpl file
	if ($this->conf['product_detail_tmpl_path']) {		
		$template = $this->cObj->fileResource($this->conf['product_detail_tmpl_path']);
	} elseif ($this->conf['product_detail_tmpl']) {
		$template = $this->cObj->fileResource($this->conf['product_detail_tmpl']);	
	} else {
		$template = $this->cObj->fileResource(t3lib_extMgm::siteRelPath($this->extKey).'templates/products_detail.tmpl');
	}
	$markerArray['###CONTENT###'] 					= $output['content']; 
	$markerArray['###PAGINATION###'] 				= $output['pagination']; 
	$markerArray['###STOCK###'] 					= $output['products_quantity'];	
	$markerArray['###PRODUCTS_NAME###'] 			= $output['products_name']; 
	$markerArray['###PRODUCTS_DESCRIPTION###'] 		= $output['products_description']; 
	$markerArray['###PRODUCTS_EXTRA_DESCRIPTION###']= $output['products_extra_description']; 
	$markerArray['###PRODUCTS_CATEGORY###'] 		= $output['products_category']; 
	$markerArray['###PRODUCTS_ATTRIBUTES###'] 		= $output['product_attributes']; 
	$markerArray['###PRODUCTS_RELATIVES###'] 		= $output['products_relatives']; 
	$markerArray['###PRODUCTS_IMAGE###'] 			= $output['products_image']; 
	$markerArray['###PRODUCTS_IMAGE_MORE###'] 		= $output['products_image_more']; 
	$markerArray['###PRODUCTS_PRICE###'] 			= $output['products_price']; 
	$markerArray['###PRODUCTS_SPECIAL_PRICE###'] 	= $output['special_price']; 
	$markerArray['###OTHER_CUSTOMERS_BOUGHT###'] 	= $output['customers_also_bought']; 

	// new 
	$markerArray['###QUANTITY###'] 					= $output['quantity']; 
	$markerArray['###BACK_BUTTON###'] 				= $output['back_button']; 
	$markerArray['###ADD_TO_CART_BUTTON###'] 		= $output['add_to_cart_button'];
	$markerArray['###PRODUCTS_SKU###'] 				= $product['sku_code']; 
	$markerArray['###PRODUCTS_EAN###'] 				= $product['ean_code']; 	
	
	// custom hook that can be controlled by third-party plugin
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostHook'])) {
		$params = array (
			'markerArray' => &$markerArray,
			'product' => &$product,
			'output' => &$output
		);		
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostHook'] as $funcRef) {	
			t3lib_div::callUserFunction($funcRef, $params, $this);
		}
	}
	// custom hook that can be controlled by third-party plugin eof	
	$content .= $output['top_content'].'<form action="'.mslib_fe::typolink($this->conf['shoppingcart_page_pid'],'&tx_multishop_pi1[page_section]=shopping_cart&products_id='.$product['products_id']).'" method="post" name="shopping_cart" id="add_to_shopping_cart_form" enctype="multipart/form-data"><div id="products_detail">'.$this->cObj->substituteMarkerArray($template, $markerArray).'</div><input name="tx_multishop_pi1[cart_item]" type="hidden" value="'.htmlspecialchars($this->get['tx_multishop_pi1']['cart_item']).'" /></form>';
}
?>