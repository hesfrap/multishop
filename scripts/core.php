<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

if ($this->ADMIN_USER)
{
	// if the user is logged in and has admin rights lets check if the shop is fully configured
	$content.=mslib_fe::giveSiteConfigurationNotice();
}
$this->ms['page']=$this->get['tx_multishop_pi1']['page_section'];
switch ($this->ms['page'])
{
	case 'payment_page':
		if ($this->get['tx_multishop_pi1']['hash']) {
			// display payment button for order
			$order=mslib_fe::getOrder($this->get['tx_multishop_pi1']['hash'],'hash');
			if ($order['orders_id'] and !$order['paid'])
			{
				if ($order['payment_method'])
				{
					$content.='<h2 class="pay_order_heading">Pay order '.$order['orders_id'].'</h2>';					
					// load optional payment button
					$mslib_payment=t3lib_div::makeInstance('mslib_payment');
					$mslib_payment->init($this);
					$paymentMethods=$mslib_payment->getEnabledPaymentMethods();
					if (is_array($paymentMethods)) {
						foreach ($paymentMethods as $user_method) {				
							if ($user_method['code']==$order['payment_method'])
							{
								if ($user_method['vars'] and $user_method['provider'])
								{
									$vars=unserialize($user_method['vars']);
									if ($mslib_payment->setPaymentMethod($user_method['provider']))
									{
										$extkey='multishop_'.$user_method['provider'];
										if (t3lib_extMgm::isLoaded($extkey))
										{
											require(t3lib_extMgm::extPath($extkey).'class.multishop_payment_method.php');
											$paymentMethod=t3lib_div::makeInstance('tx_multishop_payment_method');
											$paymentMethod->setPaymentMethod($user_method['provider']);
											$paymentMethod->setVariables($vars);
											$content.=$paymentMethod->displayPaymentButton($order['orders_id'],$this);
										}
										break;
									}
								}						
							}
						}
					}			
				}				
			}
			elseif ($order['paid'])
			{
				// order has been paid, so dont load the psp
				$content.='Thank you!<br />Order '.$order['orders_id'].' has been successfully paid.';
			}			
		}		
	break;
	case 'info':
		// cms information pages
		$output_array=array();
		if ($this->get['tx_multishop_pi1']['cms_hash'])
		{
			require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/info.php');
		}
	break;	
	case 'ultrasearch':
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/ultrasearch.php');
	break;	
	case 'checkout':
		if (strstr($this->ms['MODULES']['CHECKOUT_TYPE'],"..")) {
			die('error in CHECKOUT_TYPE value');
		} else {
			if (strstr($this->ms['MODULES']['CHECKOUT_TYPE'],"/")) {
				// relative mode
				require($this->DOCUMENT_ROOT.$this->ms['MODULES']['CHECKOUT_TYPE'].'/checkout.php');	
			} else {
				require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/includes/checkout/'.$this->ms['MODULES']['CHECKOUT_TYPE'].'/checkout.php');	
			}
		}	
	break;
	case 'admin_price_update_dl_xls':
		if ($this->ADMIN_USER) {
		   require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/price_update/mass_price_update_xls_export.php');
		}
	break;
	case 'admin_price_update_up_xls':
		if ($this->ADMIN_USER) {
			if (isset($this->post['Submit'])) {
				$dest =$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/' . $_FILES['datafile']['name'];
				if (move_uploaded_file($_FILES['datafile']['tmp_name'], $dest)) {
					$filename = $_FILES['datafile']['name'];
				} else {
					$filename = '';
				}
			}
			
			if (!empty($filename)) {
				require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/price_update/mass_price_update_xls_import.php');
			}
		}
	break;
	case 'admin_orders_stats_dl_xls':
		if ($this->ADMIN_USER) {
			require_once(t3lib_extMgm::extPath('phpexcel_service').'Classes/PHPExcel.php');
			require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/admin_stats_orders/orders_stats_xls_export.php');
		}
	break;
	case 'shopping_cart':
		if (strstr($this->ms['MODULES']['SHOPPING_CART_TYPE'],"..")) die('error in SHOPPING_CART_TYPE value');
		else 
		{
			if (strstr($this->ms['MODULES']['SHOPPING_CART_TYPE'],"/"))
			{
				// relative mode
				require($this->DOCUMENT_ROOT.$this->ms['MODULES']['SHOPPING_CART_TYPE'].'.php');	
			}
			else
			{
				require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/includes/shopping_cart/default.php');	
			}
		}				
	break;		
	case 'products_detail':
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/products_detail.php');	
	break;
	case 'products_search':
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/products_search.php');	
	break;	
	case 'products_listing':
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/products_listing.php');
	break;
	case 'manufacturers_products_listing':
		if (strstr($this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'],"..")) die('error in MANUFACTURERS_PRODUCTS_LISTING_TYPE value');
		else 
		{
			if (strstr($this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'],"/"))
			{
				// relative mode
				require($this->DOCUMENT_ROOT.$this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'].'.php');	
			}
			else
			{
				require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/manufacturers_products_listing.php');	
			}
		}
	break;
	case 'manufacturers':
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/manufacturers.php');	
	break;			
	case 'admin_sitemap_generator':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_sitemap_generator.php');			
	break;
	case 'admin_system_clear_database':
		if ($this->ROOTADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_clear_database.php');	
	break;
	case 'admin_system_consistency_checker':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_consistency_checker.php');	
	break;
	case 'admin_system_images_update':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_images_update.php');	
	break;
	case 'admin_system_clear_cooluri_cache':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_clear_cooluri_cache.php');	
	break;
	case 'admin_system_rebuild_flat_database':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_rebuild_flat_database.php');	
	break;
	case 'admin_system_orphan_files':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_system_orphan_files.php');	
	break;
	case 'admin_mass_product_updater':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_mass_product_updater.php');	
	break;	

	case 'admin_list_manual_orders':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/manual_order/admin_list_manual_orders.php');	
	break;
	case 'admin_proced_manual_order':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/manual_order/admin_proced_manual_order.php');	
	break;
		
		
	case 'admin_shipping_options':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_shipping_options.php');	
	break;

	case 'admin_add_order':
		if ($this->ADMIN_USER) require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_add_order.php');	
	break;
	case 'psp_accepturl':
	case 'psp_pendingurl':
	case 'psp_declineurl':
	case 'psp_exceptionurl':
	case 'psp_cancelurl':
		$page=mslib_fe::getCMScontent($this->ms['page'],$GLOBALS['TSFE']->sys_language_uid);
		if ($page[0]['name'])	$header_label=$page[0]['name'];
		else					$header_label='Payment';
		$content.='<div class="main-heading"><h2>'.$header_label.'</h2></div>';
		if ($page[0]['content'])
		{
			$content.=$page[0]['content'];
		}	
		else
		{
			// show standard thank you
			if ($this->ms['page']=='psp_accepturl') 		$content.=$this->pi_getLL('your_payment_has_been_completed');
			else									$content.=$this->pi_getLL('your_payment_has_not_been_completed');
		}
	break;
	// psp thank you or error pages eof
	case 'custom_page':
	// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customPage'])) {
			$params = array (
				'content' => &$content
			); 
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customPage'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}	
	// custom page hook that can be controlled by third-party plugin eof
	break;
	default:
		$this->ms['page']='home';
		// load cms top
		if (!$this->get['p']) {
			$lifetime=36000;
			$string='home_top_'.$GLOBALS['TSFE']->sys_language_uid;
			if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$tmp=mslib_befe::cacheLite('get',$string,$lifetime,0)))
			{	
				$tmp=mslib_fe::printCMScontent('home_top',$GLOBALS['TSFE']->sys_language_uid);
				if ($this->ms['MODULES']['CACHE_FRONT_END'])
				{
					// if empty we stuff it with a space, so the database query wont be executed next time
					if (!$tmp) $tmp=' ';
					mslib_befe::cacheLite('save',$string,$lifetime,0,$tmp);
				}
			}
			$content.=$tmp;	
		}
		// load cms top eof
		if ($this->ms['MODULES']['HOME_PRODUCTS_LISTING'])
		{
			require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/products_listing.php');	
		}
		// load cms bottom
		if (!$this->get['p']) {		
			$string='home_bottom'.$GLOBALS['TSFE']->sys_language_uid;
			if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$tmp=mslib_befe::cacheLite('get',$string,$lifetime,0)))
			{	
				$tmp=mslib_fe::printCMScontent('home_bottom',$GLOBALS['TSFE']->sys_language_uid);
				if ($this->ms['MODULES']['CACHE_FRONT_END'])
				{
					// if empty we stuff it with a space, so the database query wont be executed next time
					if (!$tmp) $tmp=' ';
					mslib_befe::cacheLite('save',$string,$lifetime,0,$tmp);
				}
			}
			$content.=$tmp;	
		}
		// load cms bottom eof	
	break;	
}	
if ($this->ms['MODULES']['SHOW_INNER_FOOTER_NAV'])
{
	$content.='
	<div id="tx_multishop_footer_menu">
	<ul>
		<li><a href="'.mslib_fe::typolink('','tx_multishop_pi1[page_section]=manufacturers').'">'.ucfirst($this->pi_getLL('manufacturers')).'</a></li>
		<li><a href="'.mslib_fe::typolink('','tx_multishop_pi1[page_section]=shopping_cart').'">'.ucfirst($this->pi_getLL('basket')).'</a></li>
		<li><a href="'.mslib_fe::typolink('','tx_multishop_pi1[page_section]=checkout').'">'.ucfirst($this->pi_getLL('go_to_checkout')).'</a></li>
	</ul>
	</div>
	';	
}
if (!$this->ms['MODULES']['DISABLE_CRUMBAR'] and $GLOBALS['TYPO3_CONF_VARS']["tx_multishop"]['crumbar_html']) $content=$GLOBALS['TYPO3_CONF_VARS']["tx_multishop"]['crumbar_html'].$content;
$content='<div id="tx_multishop_pi1_core">'.$content.'</div>';
?>