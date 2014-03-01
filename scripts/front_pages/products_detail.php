<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
// custom hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPageJSHook'])) {
	$params = array ();
	foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPageJSHook'] as $funcRef) {
		t3lib_div::callUserFunction($funcRef, $params, $this);
	}
}
// custom hook that can be controlled by third-party plugin eof
if (is_numeric($this->get['products_id'])) {
	//last visited
	$cart = $GLOBALS['TSFE']->fe_user->getKey('ses',$this->cart_page_uid);
	$cart['last_visited'][$this->get['products_id']]=$this->get['products_id'];
	$GLOBALS['TSFE']->fe_user->setKey('ses',$this->cart_page_uid, $cart);
	$GLOBALS['TSFE']->fe_user->storeSessionData();
	//last visited eof
	if (isset($this->get['clear_list'])) {
		$cart = $GLOBALS['TSFE']->fe_user->getKey('ses',$this->cart_page_uid);
		$cart['last_visited']=array();
		$cart['last_visited'][$this->get['products_id']]=$this->get['products_id'];
		$GLOBALS['TSFE']->fe_user->setKey('ses',$this->cart_page_uid, $cart);
		$GLOBALS['TSFE']->fe_user->storeSessionData();	
	}
	unset($cart['last_visited'][$this->get['products_id']]);	
}
if ($this->ms['MODULES']['CACHE_FRONT_END'] and !$this->ms['MODULES']['CACHE_TIME_OUT_PRODUCTS_DETAIL_PAGES']) {
	$this->ms['MODULES']['CACHE_FRONT_END']=0;
}
if ($this->ms['MODULES']['CACHE_FRONT_END']) {
	$options = array(
		'caching' => true,
		'cacheDir' => $this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/cache/',
		'lifeTime' => $this->ms['MODULES']['CACHE_TIME_OUT_PRODUCTS_DETAIL_PAGES']
	);
	$Cache_Lite = new Cache_Lite($options);
	$string=md5($this->cObj->data['uid'].'_'.$this->HTTP_HOST.'_'.$this->server['REQUEST_URI'].$this->server['QUERY_STRING']);
}
if (!$this->ms['MODULES']['CACHE_FRONT_END'] or !$output_array=$Cache_Lite->get($string)) {
	if (strstr($this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'],"/")) {
		require($this->DOCUMENT_ROOT.$this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'].'.php');	
	} elseif($this->ms['MODULES']['PRODUCTS_DETAIL_TYPE']) {
		require(t3lib_extMgm::extPath('multishop').'scripts/front_pages/includes/products_detail/'.$this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'].'.php');		
	} else {
		require_once('includes/products_detail/default.php');
	}
	if ($this->ms['MODULES']['CACHE_FRONT_END']) {
		$output_array['content']=$content;		
		$Cache_Lite->save(serialize($output_array));
	}
} elseif ($output_array) {
	$output_array=unserialize($output_array);
	if ($output_array['http_header']) {
		if (!is_array($output_array['http_header'])) {
			header($output_array['http_header']);	
		} else {
			foreach ($output_array['http_header'] as $http_header) {
				header($http_header);
			}
		}		
	}
	$content.=$output_array['content'];	
}
if (is_array($output_array['meta']) and count($output_array['meta'])) {
	$GLOBALS['TSFE']->additionalHeaderData=array_merge($GLOBALS['TSFE']->additionalHeaderData,$output_array['meta']);
}
unset($output_array);	
?>