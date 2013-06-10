<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

	if (!$this->imageWidth) $this->imageWidth='100';
	// now parse all the objects in the tmpl file
	if ($this->conf['products_listing_tmpl_path'])  	$template = $this->cObj->fileResource($this->conf['products_listing_tmpl_path']);
	elseif ($this->conf['products_listing_tmpl'])  		$template = $this->cObj->fileResource($this->conf['products_listing_tmpl']);	
	else												$template = $this->cObj->fileResource(t3lib_extMgm::siteRelPath($this->extKey).'templates/products_listing.tmpl');	
	// Extract the subparts from the template
	$subparts=array();
	$subparts['template'] 	= $this->cObj->getSubpart($template, '###TEMPLATE###');
	$subparts['item']		= $this->cObj->getSubpart($subparts['template'], '###ITEM###');
	
	$contentItem='';
	foreach ($products as $current_product)
	{
		$output=array();		
		$final_price=mslib_fe::final_products_price($current_product);		
		$where='';
		if ($current_product['categories_id'])
		{
			// get all cats to generate multilevel fake url
			$level=0;
			$cats=mslib_fe::Crumbar($current_product['categories_id']);
			$cats=array_reverse($cats);
			$where='';
			if (count($cats) > 0)
			{
				foreach ($cats as $cat)
				{
					$where.="categories_id[".$level."]=".$cat['id']."&";
					$level++;
				}
				$where=substr($where,0,(strlen($where)-1));
				$where.='&';
			}
			// get all cats to generate multilevel fake url eof
		}
		$output['link']=mslib_fe::typolink($this->conf['products_detail_page_pid'],$where.'&products_id='.$current_product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
		$output['catlink']=mslib_fe::typolink($this->conf['products_listing_page_pid'],'&'.$where.'&tx_multishop_pi1[page_section]=products_listing');
		if ($current_product['products_image']) $output['image']='<img src="'.mslib_befe::getImagePath($current_product['products_image'],'products',$this->imageWidth).'" alt="'.htmlspecialchars($current_product['products_name']).'" />';
		else $output['image']='<div class="no_image"></div>';
		
		if ($current_product['tax_rate'] and $this->ms['MODULES']['SHOW_PRICES_WITH_AND_WITHOUT_VAT'])
		{
			$output['products_price'].='<div class="price_excluding_vat">'.$this->pi_getLL('excluding_vat').' '.mslib_fe::amount2Cents($current_product['final_price']).'</div>';		
		}			
		if ($current_product['products_price'] <> $current_product['final_price'] )
		{
			if (!$this->ms['MODULES']['DB_PRICES_INCLUDE_VAT'] and ($current_product['tax_rate'] and $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']))
			{				
				$old_price=$current_product['products_price']*(1+$current_product['tax_rate']);
			}			
			else $old_price=$current_product['products_price'];					
			$output['products_price'].='<div class="old_price">'.mslib_fe::amount2Cents($old_price).'</div><div class="specials_price">'.mslib_fe::amount2Cents($final_price).'</div>';
		}
		else
		{
			$output['products_price'].='<div class="price">'.mslib_fe::amount2Cents($final_price).'</div>';
		}			
		if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER))
		{			
			$output['admin_icons']='<div class="admin_menu">
			<a href="'.mslib_fe::typolink($this->shop_pid.',2002','tx_multishop_pi1[page_section]=admin_ajax&cid='.$current_product['categories_id'].'&pid='.$current_product['products_id'].'&action=edit_product').'" onclick="return hs.htmlExpand(this, { objectType: \'iframe\', width: 910, height: 500} )" class="admin_menu_edit"></a>
			<a href="'.mslib_fe::typolink($this->shop_pid.',2002','tx_multishop_pi1[page_section]=admin_ajax&cid='.$current_product['categories_id'].'&pid='.$current_product['products_id'].'&action=delete_product').'" onclick="return hs.htmlExpand(this, { objectType: \'iframe\', width: 910, height: 140} )" class="admin_menu_remove" title="Remove"></a>
			</div>';
		}	
		$markerArray=array();		
		$markerArray['ADMIN_ICONS']						= $output['admin_icons'];
		$markerArray['PRODUCTS_ID']						= $current_product['products_id'];
		if (($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) and !$current_product['products_status'] and !$this->ms['MODULES']['FLAT_DATABASE']) {
			$markerArray['ITEM_CLASS'] = 'disabled_product';
		}
		$markerArray['PRODUCTS_NAME']					= $current_product['products_name'];
		$markerArray['PRODUCTS_SHORTDESCRIPTION']		= $current_product['products_shortdescription'];
		$markerArray['PRODUCTS_DETAIL_PAGE_LINK']		= $output['link'];
		$markerArray['CATEGORIES_NAME']					= $current_product['categories_name'];
		$markerArray['CATEGORIES_NAME_PAGE_LINK']		= $output['catlink'];
		$markerArray['PRODUCTS_IMAGE']					= $output['image'];
		$markerArray['PRODUCTS_PRICE']					= $output['products_price'];
		// custom hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_listing.php']['productsListingRecordHook']))
		{
			$params = array (
				'markerArray' => &$markerArray,
				'product' => &$current_product,
				'output' => &$output,
				'products_compare' => &$products_compare
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_listing.php']['productsListingRecordHook'] as $funcRef)
			{	
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		// custom hook that can be controlled by third-party plugin eof		
		$contentItem .= $this->cObj->substituteMarkerArray($subparts['item'], $markerArray,'###|###');
	}
	// fill the row marker with the expanded rows
	$subpartArray['###CURRENT_CATEGORIES_NAME###'] 					= trim($current['categories_name']);
	$subpartArray['###ITEM###'] 									= $contentItem;
	// completed the template expansion by replacing the "item" marker in the template 
	
	// custom hook that can be controlled by third-party plugin
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_listing.php']['productsListingPagePostHook']))
	{
		$params = array (
			'subpartArray' => &$subpartArray,
			'current' => &$current
		);
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_listing.php']['productsListingPagePostHook'] as $funcRef)
		{	
			t3lib_div::callUserFunction($funcRef, $params, $this);
		}
	}
	// custom hook that can be controlled by third-party plugin eof
	$content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], null, $subpartArray);	
	if ($this->ms['page'] <> 'products_search' and ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)))
	{
		$content.='					
		<script>
		  jQuery(document).ready(function($) {
			$(".disabled_product").css({ opacity: 0.6 });
			$(".disabled_product").hover(
			  function () {
				$(".disabled_product").css({ opacity: 1 });
			  },
			  function () {
				$(".disabled_product").css({ opacity: 0.6 });
			  }
			);			  
			var result = jQuery("#product_listing").sortable({
				cursor:     "move", 
			    //axis:       "y", 
			    update: function(e, ui) { 
			        href = "'.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=product&catid='.$current_product['categories_id']).'"; 
			        jQuery(this).sortable("refresh"); 
			        sorted = jQuery(this).sortable("serialize", "id"); 
			        jQuery.ajax({ 
			                type:   "POST", 
			                url:    href, 
			                data:   sorted, 
			                success: function(msg) {
			                        //do something with the sorted data 
			                }
			        }); 
			    } 
	
			});
		  });
		  </script>					
		';
	}	
?>