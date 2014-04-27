<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if (!mslib_fe::loggedin() and $this->ms['MODULES']['DISABLE_CHECKOUT_FOR_GUESTS']) {
	// redirect to login page
	header('Location: '.$this->FULL_HTTP_URL.mslib_fe::typolink($this->conf['login_pid']));
	exit();
}
//$this->ms['page']='checkout';
$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
if (is_array($cart['products']) and count($cart['products'])) {
	// load customer country
	// use for filtering the available payment on the customer country
	$address=$cart['user'];
	if ($this->post['country']!=$address['country']) {
		$address['country']=$this->post['country'];
	}
	if (!$address['country']) {
		$user_country=0;
	} else {
		$iso_customer=mslib_fe::getCountryByName($address['country']);
		$user_country=$iso_customer['cn_iso_nr'];
	}
	$delivery_user_country=$user_country;
	if (isset($this->post['different_delivery_address']) && $this->post['different_delivery_address']>0 && !empty($this->post['delivery_country'])) {
		$iso_customer=mslib_fe::getCountryByName($this->post['delivery_country']);
		$delivery_user_country=$iso_customer['cn_iso_nr'];
	}
	$payment_methods=array();
	$shipping_methods=array();
	if ($this->ms['MODULES']['PRODUCT_EDIT_METHOD_FILTER']) {
		$pids=array();
		foreach ($cart['products'] as $key=>$array) {
			if (is_numeric($array['products_id'])) {
				$pids[]=$array['products_id'];
			}
		}
		if (count($pids)) {
			$payment_methods=mslib_fe::getProductMappedMethods($pids, 'payment', $user_country);
			$shipping_methods=mslib_fe::getProductMappedMethods($pids, 'shipping');
		}
	}
	if (!count($payment_methods) and !count($shipping_methods)) {
		// nothing is loaded. this cant be valid so let's load the default methods.
		// loading payment methods
		$payment_methods=mslib_fe::loadPaymentMethods(0, $user_country);
		// loading shipping methods eof
		$shipping_methods=mslib_fe::loadShippingMethods(0, $delivery_user_country);
	}
}
if (count($shipping_methods)==0 and count($payment_methods)==0) {
	$stepCodes=array(
		'checkout_address',
		'checkout_review',
		'checkout_finished'
	);
} else {
	$stepCodes=array(
		'checkout_address',
		'checkout_shipping_payment_method',
		'checkout_review',
		'checkout_finished'
	);
}
function CheckoutStepping(&$steps, $current, $pointer) {
	$output='<ul id="checkout_crumbar">';
	for ($i=0; $i<count($steps); $i++) {
		$output.='<li class="'.$steps[$i].' '.(($current==$steps[$i]) ? 'active' : '').'">';
		if ($steps[$i]==$current) {
			$output.='<strong>';
		}
		$output.='<span class="step">'.$pointer->pi_getLL('step').' '.($i+1).':</span><span class="step_label">'.$pointer->pi_getLL($steps[$i]).'</span>';
		if ($steps[$i]==$current) {
			$output.='</strong>';
		}
		$output.='</li>';
	}
	$output.='</ul>';
	return $output;
}

if ($this->get['tx_multishop_pi1']['previous_checkout_section']) {
	for ($i=1; $i<=count($stepCodes); $i++) {
		if ($this->get['tx_multishop_pi1']['previous_checkout_section']==current($stepCodes)) {
			if ($this->post) {
				$posted_page=current($stepCodes);
			}
			break;
		} else {
			if (!next($stepCodes)) {
				break;
			}
		}
	}
}
$content.='';
require(current($stepCodes).'.php');
?>