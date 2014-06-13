<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
/***************************************************************
 *  Copyright notice
 *  (c) 2010 BVB Media BV - Bas van Beek <bvbmedia@gmail.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 * Hint: use extdeveval to insert/update function index above.
 */
class tx_mslib_cart extends tslib_pibase {
	var $cart=array();
	function initLanguage($ms_locallang) {
		$this->pi_loadLL();
		//array_merge with new array first, so a value in locallang (or typoscript) can overwrite values from ../locallang_db
		$this->LOCAL_LANG=array_replace_recursive($this->LOCAL_LANG, is_array($ms_locallang) ? $ms_locallang : array());
		if ($this->altLLkey) {
			$this->LOCAL_LANG=array_replace_recursive($this->LOCAL_LANG, is_array($ms_locallang) ? $ms_locallang : array());
		}
	}
	function init($ref) {
		mslib_fe::init($ref);
	}
	function getCart() {
		$this->cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
		if ($this->cart['user']['country']) {
			$this->tta_user_info['default']['country']=$this->cart['user']['country'];
			$this->tta_user_info['default']['region']=$this->cart['user']['state'];
		}
		unset($this->cart['summarize']);
		if ($this->tta_user_info['default']['country']) {
			$iso_customer=mslib_fe::getCountryByName($this->tta_user_info['default']['country']);
		} else {
			$iso_customer=$this->tta_shop_info;
		}
		if (!$iso_customer['cn_iso_nr']) {
			// fall back (had issue with admin notification)
			$iso_customer=mslib_fe::getCountryByName($this->tta_shop_info['country']);
		}
		$this->cart['user']['countries_id']=$iso_customer['cn_iso_nr'];
		if (is_array($this->cart['products'])) {
			if ($iso_customer['cn_iso_nr']) {
				// if store country is different from customer country and user provided valid VAT id, change VAT rate to zero
				if ($this->ms['MODULES']['DISABLE_VAT_RATE_WHEN_CROSS_BORDERS'] and $this->cart['user']['tx_multishop_vat_id']) {
					if ($iso_customer['country']!=$this->tta_shop_info['country']) {
						$this->ms['MODULES']['DISABLE_VAT_RATE']=1;
					}
				}
				// products				
				if (is_array($this->cart['products'])) {
					// redirect if products stock are negative or quantity ordered is greater than the stock itself
					$redirect_to_cart_page=false;
					foreach ($this->cart['products'] as $key=>&$product) {
						if ($this->get['tx_multishop_pi1']['page_section']=='checkout') {
							$product_db=mslib_fe::getProduct($product['products_id']);
							if (!$this->ms['MODULES']['ALLOW_ORDER_OUT_OF_STOCK_PRODUCT']) {
								if ($product_db['products_quantity']<1) {
									$redirect_to_cart_page=true;
								} else if ($product['qty']>$product_db['products_quantity']) {
									$redirect_to_cart_page=true;
								}
							}
						}
						if ($this->ms['MODULES']['DISABLE_VAT_RATE']) {
							$product['tax']=0;
							$product['tax_rate']=0;
						} else {
							$tax_rate=mslib_fe::taxRuleSet($product['tax_id'], 0, $iso_customer['cn_iso_nr'], 0);
							if (!$tax_rate['total_tax_rate']) {
								$tax_rate['total_tax_rate']=0;
							}
							$product['tax']=mslib_fe::taxDecimalCrop($product['final_price']/100*$tax_rate['total_tax_rate']);
							$product['tax_rate']=($tax_rate['total_tax_rate']/100);
						}
						//error_log(print_r($product,1));
						$total_attributes_tax=0;
						$total_attributes_price=0;
						if (is_array($product['attributes'])) {
							foreach ($product['attributes'] as &$attributes) {
								$total_attributes_price+=($attributes['options_values_price']);
								$attributes['tax']=mslib_fe::taxDecimalCrop($attributes['options_values_price']*$product['tax_rate']);
								$total_attributes_tax+=$attributes['price_prefix'].$attributes['tax'];
							}
						}
						$product['final_price_including_vat']=mslib_fe::taxDecimalCrop(($product['final_price']*(1+$product['tax_rate'])));
						$product['total_price']=(($product['final_price']+$total_attributes_price)*$product['qty']);
						$product['total_price_including_vat']=mslib_fe::taxDecimalCrop((($product['final_price']+$total_attributes_price)*$product['qty'])*(1+$product['tax_rate']));
						$this->cart['summarize']['sub_total']+=$product['total_price'];
						$this->cart['summarize']['sub_total_including_vat']+=$product['total_price_including_vat'];
					}
					if ($redirect_to_cart_page) {
						$link=mslib_fe::typolink($this->shoppingcart_page_pid, '&tx_multishop_pi1[page_section]=shopping_cart', 1);
						if ($link) {
							header("Location: ".$this->FULL_HTTP_URL.$link);
							exit();
						}
					}
				}
				// get shipping tax rate	
				$shipping_method=mslib_fe::getShippingMethod($this->cart['user']['shipping_method'], 's.code', $iso_customer['cn_iso_nr']);
				$tax_rate=mslib_fe::taxRuleSet($shipping_method['tax_id'], 0, $iso_customer['cn_iso_nr'], 0);
				if ($this->ms['MODULES']['DISABLE_VAT_RATE']) {
					$tax_rate['total_tax_rate']=0;
				}
				$shipping_tax_rate=($tax_rate['total_tax_rate']/100);
				// get payment tax rate	
				$payment_method=mslib_fe::getPaymentMethod($this->cart['user']['payment_method'], 'p.code', $iso_customer['cn_iso_nr']);
				$tax_rate=mslib_fe::taxRuleSet($payment_method['tax_id'], 0, $iso_customer['cn_iso_nr'], 0);
				if ($this->ms['MODULES']['DISABLE_VAT_RATE']) {
					$tax_rate['total_tax_rate']=0;
				}
				$payment_tax_rate=($tax_rate['total_tax_rate']/100);
				if ($shipping_tax_rate>0 or $payment_tax_rate>0) {
					$shipping_tax=$this->cart['user']['shipping_method_costs']*$shipping_tax_rate;
					$payment_tax=$this->cart['user']['payment_method_costs']*$payment_tax_rate;
				}
				// rounding was needed to fix 1 cents grand total difference
				// adjusted 25/11/2014 14:02 CET
				// shipping cost bugfix because of the fractions, changed from 2 decimal to 14
				$this->cart['user']['shipping_method_costs']=round($this->cart['user']['shipping_method_costs'], 14);
				$this->cart['user']['payment_method_costs']=round($this->cart['user']['payment_method_costs'], 2);
				$this->cart['user']['shipping_method_costs_including_vat']=round($this->cart['user']['shipping_method_costs']+($this->cart['user']['shipping_method_costs']*$shipping_tax_rate), 2);
				$this->cart['user']['payment_method_costs_including_vat']=round($this->cart['user']['payment_method_costs']+($this->cart['user']['payment_method_costs']*$payment_tax_rate), 2);
				// discount
				if (!$this->cart['discount'] and !$GLOBALS["TSFE"]->fe_user->user['uid'] and $this->cart['user']['email']) {
					// check if guest user is already in the database and if so add possible group discount
					$user_check=mslib_fe::getUser($this->cart['user']['email'], 'email');
					if ($user_check['uid']) {
						$discount_percentage=mslib_fe::getUserGroupDiscount($user_check['uid']);
						if ($discount_percentage) {
							$this->cart['coupon_code']='';
							$this->cart['discount']=$discount_percentage;
							$this->cart['discount_type']='percentage';
						}
					}
				}
				if ($this->cart['discount']) {
					switch ($this->cart['discount_type']) {
						case 'percentage':
							$discount_percentage=$this->cart['discount'];
							$discount_price=round((($this->cart['summarize']['sub_total_including_vat'])/100*$discount_percentage), 2);
							$subtotal=(($this->cart['summarize']['sub_total_including_vat'])/100*(100-$discount_percentage));
							$subtotal_tax=(($this->cart['summarize']['sub_total_including_vat']-$this->cart['summarize']['sub_total'])/100*(100-$discount_percentage));
							$this->cart['discount_amount']=$discount_price;
							$this->cart['discount_percentage']=$discount_percentage;
							break;
						case 'price':
							$discount_price=$this->cart['discount'];
							$discount_percentage=($this->cart['discount']/($this->cart['summarize']['sub_total_including_vat'])*100);
							$subtotal=(($this->cart['summarize']['sub_total_including_vat'])/100*(100-$discount_percentage));
							$subtotal_tax=(($this->cart['summarize']['sub_total_including_vat']-$this->cart['summarize']['sub_total'])/100*(100-$discount_percentage));
							$this->cart['discount_amount']=$discount_price;
							$this->cart['discount_percentage']=$discount_percentage;
							break;
					}
				}
				// custom hook that can be controlled by third-party plugin
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_cart.php']['getCartPostCalc'])) {
					$params=array(
						'cart'=>&$this->cart,
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_cart.php']['getCartPostCalc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
				}
				// custom hook that can be controlled by third-party plugin eofq
				// calculate totals
				$this->cart['summarize']['grand_total_excluding_vat']=($this->cart['summarize']['sub_total']+$this->cart['user']['shipping_method_costs']+$this->cart['user']['payment_method_costs']-$this->cart['discount_amount']);
				$this->cart['summarize']['grand_total']=($this->cart['summarize']['sub_total_including_vat']+$this->cart['user']['shipping_method_costs_including_vat']+$this->cart['user']['payment_method_costs_including_vat']-$this->cart['discount_amount']);
//				$this->cart['summarize']['grand_total_vat']=($this->cart['summarize']['grand_total']-$this->cart['summarize']['grand_total_excluding_vat']);
				$this->cart['summarize']['grand_total_vat']=($this->cart['summarize']['grand_total']-$this->cart['summarize']['grand_total_excluding_vat']);
				// b2b mode 1 cent bugfix: 2013-05-09 cbc
				// I have fixed the b2b issue by updating all the products prices in the database to have max 2 decimals
				// therefore I disabled below bugfix, cause thats a ducktape solution that can break b2c sites
				//$this->cart['summarize']['grand_total']=round($this->cart['summarize']['grand_total_excluding_vat'],2) + round($this->cart['summarize']['grand_total_vat'],2);
			}
		}
		// custom hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_cart.php']['getCartPreSave'])) {
			$params=array(
				'cart'=>&$this->cart,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_cart.php']['getCartPreSave'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		// custom hook that can be controlled by third-party plugin eofq
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $this->cart);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
		return $this->cart;
	}
	function updateCart() {
		if (!$this->ms['MODULES']['ALLOW_ORDER_OUT_OF_STOCK_PRODUCT']) {
			$product_id=$this->post['products_id'];
			if (is_numeric($this->get['products_id']) and $this->get['tx_multishop_pi1']['action']=='add_to_cart') {
				$product_id=$this->get['products_id'];
			}
			if (is_numeric($product_id)) {
				$product=mslib_fe::getProduct($product_id);
				if ($product['products_quantity']<1 && !$this->ms['MODULES']['ALLOW_ORDER_OUT_OF_STOCK_PRODUCT']) {
					if ($product['categories_id']) {
						// get all cats to generate multilevel fake url
						$level=0;
						$cats=mslib_fe::Crumbar($product['categories_id']);
						$cats=array_reverse($cats);
						$where='';
						if (count($cats)>0) {
							foreach ($cats as $cat) {
								$where.="categories_id[".$level."]=".$cat['id']."&";
								$level++;
							}
							$where=substr($where, 0, (strlen($where)-1));
						}
					}
					$link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product_id.'&tx_multishop_pi1[page_section]=products_detail');
					header("Location: ".$this->FULL_HTTP_URL.$link);
					exit;
				}
			}
		}
		// error_log("bastest");
		// hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCart'])) {
			$params=array();
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCart'] as $funcRef) {
				$content.=t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		} else {
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartPreHook'])) {
				$params=array(
					'get'=>&$this->get,
					'post'=>&$this->post
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartPreHook'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
			// custom hook that can be controlled by third-party plugin eof			
			$GLOBALS['dont_update_cart']=1;
			$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
			if (is_numeric($this->get['products_id']) and $this->get['tx_multishop_pi1']['action']=='add_to_cart') {
				$this->post['products_id']=$this->get['products_id'];
			}
			if (is_numeric($this->post['products_id'])) {
				$shopping_cart_item=$this->post['products_id'];
				if ($this->post['tx_multishop_pi1']['cart_item']) {
					$shopping_cart_item=$this->post['tx_multishop_pi1']['cart_item'];
				} elseif (is_array($this->post['attributes'])) {
					$shopping_cart_item=md5($this->post['products_id'].serialize($this->post['attributes']));
				}
				if (is_numeric($cart['products'][$shopping_cart_item]['products_id'])) {
					$products_id=$cart['products'][$shopping_cart_item]['products_id'];
				} else {
					$products_id=$this->post['products_id'];
				}
				$product=mslib_fe::getProduct($products_id);
				if ($product['products_id']) {
					if ($product['products_image']) {
						$product['products_image']=mslib_befe::getImagePath($product['products_image'], 'products', '50');
					}
					if (mslib_fe::ProductHasAttributes($product['products_id']) and !count($this->post['attributes'])) {
						// Product has attributes. We need to redirect the customer to the product detail page so the attributes can be selected
						if ($product['categories_id']) {
							// get all cats to generate multilevel fake url
							$level=0;
							$cats=mslib_fe::Crumbar($product['categories_id']);
							$cats=array_reverse($cats);
							$where='';
							if (count($cats)>0) {
								foreach ($cats as $cat) {
									$where.="categories_id[".$level."]=".$cat['id']."&";
									$level++;
								}
								$where=substr($where, 0, (strlen($where)-1));
//								$where.='&';
							}
							// get all cats to generate multilevel fake url eof
						}
						$link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
						header("Location: ".$this->FULL_HTTP_URL.$link);
						exit;
					}
					if ($this->post['quantity'] and strstr($this->post['quantity'], ",")) {
						$this->post['quantity']=str_replace(",", ".", $this->post['quantity']);
					}
					if (!$this->post['quantity'] or ($this->post['quantity'] and !is_numeric($this->post['quantity']))) {
						$this->post['quantity']=1;
					}
					if ($this->post['quantity']<0) {
						$this->post['quantity']=0;
					}
					$current_quantity=$cart['products'][$shopping_cart_item]['qty'];
					if (!$this->post['tx_multishop_pi1']['cart_item']) {
						$this->post['quantity']=$current_quantity+$this->post['quantity'];
					}
					if ($product['maximum_quantity']>0 and $product['maximum_quantity']<$this->post['quantity']) {
						$this->post['quantity']=$product['maximum_quantity'];
					}
					if ($product['minimum_quantity'] and ($product['minimum_quantity']>($this->post['quantity']))) {
						$this->post['quantity']=$product['minimum_quantity'];
					}
					$product['qty']=($this->post['quantity']);
					// chk if the product has staffel price
					if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
						if ($this->post['quantity']) {
							$quantity=$this->post['quantity'];
						} else {
							$quantity=$cart['products'][$shopping_cart_item]['qty'];
						}
						$product['final_price']=(mslib_fe::calculateStaffelPrice($product['staffel_price'], $quantity)/$quantity);
					}
					// custom hook that can be controlled by third-party plugin
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartProductPricePostHook'])) {
						$params=array(
							'shopping_cart_item'=>$shopping_cart_item,
							'product'=>&$product
						);
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartProductPricePostHook'] as $funcRef) {
							t3lib_div::callUserFunction($funcRef, $params, $this);
						}
					}
					// custom hook that can be controlled by third-party plugin eof						
					// add product to the cart (through form on products_detail page)
					$product['description']='';
					$product['country_tax']=mslib_fe::taxDecimalCrop($product['final_price']*$product['country_tax_rate']);
					$product['region_tax']=mslib_fe::taxDecimalCrop($product['final_price']*$product['region_tax_rate']);
					if ($product['country_tax'] && $product['region_tax']) {
						$product['tax']=$product['country_tax']+$product['region_tax'];
					} else {
						$product['tax']=mslib_fe::taxDecimalCrop($product['final_price']*$product['tax_rate']);
					}
					$cart['products'][$shopping_cart_item]=$product;
					// add possible micro download
					$str="select p.file_number_of_downloads, pd.file_remote_location, pd.file_label, pd.file_location from tx_multishop_products p, tx_multishop_products_description pd where p.products_id='".$product['products_id']."' and pd.language_id='".$this->sys_language_uid."' and p.products_id=pd.products_id";
					$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
						// use current account
						$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
						$cart['products'][$shopping_cart_item]['file_label']=$row['file_label'];
						$cart['products'][$shopping_cart_item]['file_location']=$row['file_location'];
						$cart['products'][$shopping_cart_item]['file_remote_location']=$row['file_remote_location'];
						$cart['products'][$shopping_cart_item]['file_number_of_downloads']=$row['file_number_of_downloads'];
					}
					// add possible micro download eof					
					$attributes_tax=0;
					if (is_array($this->post['attributes'])) {
						foreach ($this->post['attributes'] as $key=>$value) {
							if (is_numeric($key)) {
								$str="SELECT * from tx_multishop_products_options o where o.products_options_id='".$key."' and language_id='".$this->sys_language_uid."'";
								$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
								$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
								$continue=0;
								switch ($row['listtype']) {
									case 'checkbox':
										$multiple=1;
										$continue=1;
										break;
									case 'hidden_field':
									case 'textarea':
									case 'input':
										$cart['products'][$shopping_cart_item]['attributes'][$key]=$row;
										$cart['products'][$shopping_cart_item]['attributes'][$key]['options_id']=$key;
										$cart['products'][$shopping_cart_item]['attributes'][$key]['products_options_values_name']=$value;
										$continue=0;
										$multiple=0;
										break;
									default:
										$continue=1;
										$multiple=0;
										break;
								}
								if ($continue) {
									if (is_array($value)) {
										$array=$value;
									} else if ($value) {
										$array=array($value);
									}
									if (count($array)) {
										if ($multiple) {
											// reset first
											unset($cart['products'][$shopping_cart_item]['attributes'][$key]);
										}
										$products_id=$this->post['products_id'];
										$getAtributesFromProductsId=$products_id;
										// hook to let other plugins further manipulate the option values display
										if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartAttributesPreProc'])) {
											$params=array(
												'products_id'=>&$products_id,
												'getAtributesFromProductsId'=>&$getAtributesFromProductsId
											);
											foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartAttributesPreProc'] as $funcRef) {
												t3lib_div::callUserFunction($funcRef, $params, $this);
											}
										}
										// hook
										foreach ($array as $item) {
											$str="SELECT * from tx_multishop_products_attributes a, tx_multishop_products_options o, tx_multishop_products_options_values ov where a.products_id='".$getAtributesFromProductsId."' and a.options_id='".$key."' and a.options_values_id='".$item."' and (o.hide_in_cart=0 or o.hide_in_cart is null) and a.options_id=o.products_options_id and o.language_id='".$this->sys_language_uid."' and ov.language_id='".$this->sys_language_uid."' and a.options_values_id=ov.products_options_values_id";
											$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
											if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
												$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
												// hook to let other plugins further manipulate the option values display
												if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['cartAttributesArray'])) {
													$params=array(
														'options_id'=>&$key,
														'row'=>&$row
													);
													foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['cartAttributesArray'] as $funcRef) {
														t3lib_div::callUserFunction($funcRef, $params, $this);
													}
												}
												// hook
												$row['country_tax']=mslib_fe::taxDecimalCrop(($row['price_prefix'].$row['options_values_price'])*$product['country_tax_rate']);
												$row['region_tax']=mslib_fe::taxDecimalCrop(($row['price_prefix'].$row['options_values_price'])*$product['region_tax_rate']);
												if ($row['country_tax'] && $row['region_tax']) {
													$row['tax']=$row['country_tax']+$row['region_tax'];
												} else {
													$row['tax']=mslib_fe::taxDecimalCrop(($row['price_prefix'].$row['options_values_price'])*($product['tax_rate']));
												}
//											$attributes_tax += $row['tax'] * $product['qty']; 
												$attributes_tax+=$row['tax'];
												if ($multiple) {
													$cart['products'][$shopping_cart_item]['attributes'][$key][]=$row;
												} else {
													$cart['products'][$shopping_cart_item]['attributes'][$key]=$row;
												}
											}
										}
									}
								}
							} // end if $key
						}
					}
					if (count($_FILES['attributes']['name'])) {
						foreach ($_FILES['attributes'] as $file_key=>$file_data) {
							foreach ($file_data as $optid=>$val) {
								if (is_numeric($optid) && $_FILES['attributes']['error'][$optid]==0 && $file_key=='name') {
									$str="SELECT products_options_name,listtype from tx_multishop_products_options o where o.products_options_id='".$optid."' and language_id='".$this->sys_language_uid."'";
									$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
									$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
									if ($row['products_options_name']) {
										if (strpos($_FILES['attributes']['name'][$optid], '.php')!==false || strpos($_FILES['attributes']['name'][$optid], '.php3')!==false || strpos($_FILES['attributes']['name'][$optid], '.php4')!==false || strpos($_FILES['attributes']['name'][$optid], '.py')!==false || strpos($_FILES['attributes']['name'][$optid], '.php5')!==false) {
											$_FILES['attributes']['name'][$optid].='.protected';
										}
										$target=$this->DOCUMENT_ROOT.'uploads/tx_multishop/order_resources/'.$_FILES['attributes']['name'][$optid];
										move_uploaded_file($_FILES['attributes']['tmp_name'][$optid], $target);
										$cart['products'][$shopping_cart_item]['attributes'][$optid]['products_options_name']=$row['products_options_name'];
										$cart['products'][$shopping_cart_item]['attributes'][$optid]['products_options_values_name']=$_FILES['attributes']['name'][$optid];
										$cart['products'][$shopping_cart_item]['attributes'][$optid]['options_id']=$optid;
										$continue=0;
										$multiple=0;
									}
								}
							}
						}
					}
					if ($product['categories_id']) {
						// get all cats to generate multilevel fake url
						$level=0;
						$cats=mslib_fe::Crumbar($product['categories_id']);
						$cats=array_reverse($cats);
						$where='';
						if (count($cats)>0) {
							foreach ($cats as $cat) {
								$where.="categories_id[".$level."]=".$cat['id']."&";
								$level++;
							}
							$where=substr($where, 0, (strlen($where)-1));
							$where.='&';
						}
						// get all cats to generate multilevel fake url eof
					}
//					$cart['products'][$shopping_cart_item]['link']=mslib_fe::typolink($product['page_uid'],'&'.$where.'&products_id='.$products_id.'&tx_multishop_pi1[page_section]=products_detail&tx_multishop_pi1[cart_item]='.$shopping_cart_item);
					$cart['products'][$shopping_cart_item]['link']=mslib_fe::typolink($this->conf['products_detail_page_pid'], $where.'&products_id='.$products_id.'&tx_multishop_pi1[page_section]=products_detail&tx_multishop_pi1[cart_item]='.$shopping_cart_item);
					$cart['products'][$shopping_cart_item]['total_attributes_tax']=$attributes_tax;
					// custom hook that can be controlled by third-party plugin
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartProductPreHook'])) {
						$params=array(
							'shopping_cart_item'=>$shopping_cart_item,
							'array'=>&$cart['products'][$shopping_cart_item],
							'cart'=>&$cart
						);
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartProductPreHook'] as $funcRef) {
							t3lib_div::callUserFunction($funcRef, $params, $this);
						}
					}
					// custom hook that can be controlled by third-party plugin eof							
					$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
					if ($this->ms['eID']) {
						$GLOBALS['TSFE']->fe_user->storeSessionData();
					} else {
						$GLOBALS['TSFE']->storeSessionData();
					}
				}
				if ($this->post['winkelwagen']) {
					// if products relatives are selected
					foreach ($this->post['winkelwagen'] as $key=>$value) {
						if ($value) {
							$rel_products_id=$this->post['relation_products_id'][$key];
							$rel_id=$this->post['relation_id'][$key];
							$rel_carty_quantity=$this->post['relation_cart_quantity'][$key];
							if ($rel_carty_quantity<0) {
								$rel_carty_quantity=0;
							}
							if ($rel_carty_quantity and strstr($rel_carty_quantity, ",")) {
								$rel_carty_quantity=str_replace(",", ".", $rel_carty_quantity);
							}
							$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
							if (preg_match("/^[0-9]+$/", $rel_products_id)) {
								$product=mslib_fe::getProduct($rel_products_id);
								if ($product['products_id']) {
									if ($product['products_image']) {
										$product['products_image']=mslib_befe::getImagePath($product['products_image'], 'products', '50');
									}
									if ($product['categories_id']) {
										// get all cats to generate multilevel fake url
										$level=0;
										$cats=mslib_fe::Crumbar($product['categories_id']);
										$cats=array_reverse($cats);
										$where='';
										if (count($cats)>0) {
											foreach ($cats as $cat) {
												$where.="categories_id[".$level."]=".$cat['id']."&";
												$level++;
											}
											$where=substr($where, 0, (strlen($where)-1));
											$where.='&';
										}
										// get all cats to generate multilevel fake url eof
									}
									$link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
									if (mslib_fe::ProductHasAttributes($product['products_id'])) {
										// Product has attributes. We need to redirect the customer to the product detail page so the attributes can be selected										
										header("Location: ".$this->FULL_HTTP_URL.$link);
										exit;
									}
									// chk if the product has staffel price
									if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
										$product['final_price']=mslib_fe::calculateStaffelPrice($product['staffel_price'], 1);
									}
									if ($product['products_id']) {
										// add product to the cart (through from on products_detail page)
										$product['description']='';
										$current_quantity=$cart['products'][$product['products_id']]['qty'];
										$cart['products'][$product['products_id']]=$product;
										$cart['products'][$product['products_id']]['qty']=$current_quantity+$rel_carty_quantity;
										$cart['products'][$product['products_id']]['link']=$link;
										if ($product['minimum_quantity']>$cart['products'][$product['products_id']]['qty']) {
											$cart['products'][$product['products_id']]['qty']=$product['minimum_quantity'];
										}
										$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
										if ($this->ms['eID']) {
											$GLOBALS['TSFE']->fe_user->storeSessionData();
										} else {
											$GLOBALS['TSFE']->storeSessionData();
										}
									}
								}
							}
						}
					}
				}
				// send notification message to admin
				if ($product['products_id']) {
					$where='';
					if ($product['categories_id']) {
						// get all cats to generate multilevel fake url
						$level=0;
						$cats=mslib_fe::Crumbar($product['categories_id']);
						$cats=array_reverse($cats);
						$where='';
						if (count($cats)>0) {
							foreach ($cats as $cat) {
								$where.="categories_id[".$level."]=".$cat['id']."&";
								$level++;
							}
							$where=substr($where, 0, (strlen($where)-1));
							$where.='&';
						}
						// get all cats to generate multilevel fake url eof
					}
					$link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
					if ($GLOBALS['TSFE']->fe_user->user['username']) {
						$customer_name=$GLOBALS['TSFE']->fe_user->user['username'];
					} else {
						$customer_name=$this->pi_getLL('customer');
					}
					$message=sprintf($this->pi_getLL('customer_added_productx_to_the_shopping_cart'), '<a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_shoppping_cart_stats').'">'.$customer_name.'</a>', '<a href="'.$link.'">'.$product['products_name'].'</a>').'.';
					if (count($cart['products'])>0) {
						if (!$sub_tr_type or $sub_tr_type=='even') {
							$sub_tr_type='odd';
						} else {
							$sub_tr_type='even';
						}
						$mslib_cart=t3lib_div::makeInstance('tx_mslib_cart');
						$mslib_cart->init($this);
						$cart=$mslib_cart->getCart();
						$cart_content.='<br />'.$this->pi_getLL('content').':<br />'.$mslib_cart->getHtmlCartContents('adminNotificationPopup');
						$message.=$cart_content;
					}
					mslib_befe::storeNotificationMessage($this->pi_getLL('customer_action'), $message);
				}
				// end of notification
			} elseif ($this->get['delete_products_id']) {
				$shopping_cart_item=$this->get['delete_products_id'];
				if (is_array($cart['products'][$shopping_cart_item])) {
					// remove the cart item
					unset($cart['products'][$shopping_cart_item]);
					$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
					$GLOBALS['TSFE']->storeSessionData();
				}
			} elseif (is_array($this->post['qty'])) {
				// add/update products in cart (from shopping cart page)
				foreach ($this->post['qty'] as $shopping_cart_item=>$qty) {
					if ($qty and strstr($qty, ",")) {
						$qty=str_replace(",", ".", $qty);
					}
					if (($qty and !is_numeric($qty))) {
						$qty=1;
					}
					if (!$qty or $qty<0) {
						unset($cart['products'][$shopping_cart_item]);
					} else {
						$products_id=$cart['products'][$shopping_cart_item]['products_id'];
						$product=mslib_fe::getProduct($products_id);
						// chk if the product has staffel price
						if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
							$cart['products'][$shopping_cart_item]['final_price']=(mslib_fe::calculateStaffelPrice($product['staffel_price'], $qty)/$qty);
						}
						$cart['products'][$shopping_cart_item]['qty']=$qty;
						if ($product['minimum_quantity']>$cart['products'][$shopping_cart_item]['qty']) {
							$cart['products'][$shopping_cart_item]['qty']=$product['minimum_quantity'];
						}
					}
				}
				$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
				$GLOBALS['TSFE']->storeSessionData();
			}
			// group discount
			if ($GLOBALS['TSFE']->fe_user->user['uid']) {
				$discount=mslib_fe::getUserGroupDiscount($GLOBALS['TSFE']->fe_user->user['uid']);
				if ($discount) {
					$cart['coupon_discount']=$discount;
					$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
					$GLOBALS['TSFE']->storeSessionData();
				}
			}
			// store cart contents for later analyses
			$cart_store_content=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
			mslib_befe::storeCustomerCartContent($cart_store_content);
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartPostHook'])) {
				$params=array(
					'cart'=>&$cart
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['updateCartPostHook'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
			// custom hook that can be controlled by third-party plugin eof			
			if ($product['products_id'] and $this->ms['MODULES']['REDIRECT_BACK_TO_PRODUCTS_DETAIL_PAGE_AFTER_ADD_TO_CART']) {
				$where='';
				if ($product['categories_id']) {
					// get all cats to generate multilevel fake url
					$level=0;
					$cats=mslib_fe::Crumbar($product['categories_id']);
					$cats=array_reverse($cats);
					$where='';
					if (count($cats)>0) {
						foreach ($cats as $cat) {
							$where.="categories_id[".$level."]=".$cat['id']."&";
							$level++;
						}
						$where=substr($where, 0, (strlen($where)-1));
						$where.='&';
					}
					// get all cats to generate multilevel fake url eof
				}
				$link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
				if ($link) {
					header("Location: ".$this->FULL_HTTP_URL.$link);
					exit();
				}
			}
		}
	}
	function countCartQuantity() {
		$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
		$products=$cart['products'];
		$weight=0;
		foreach ($products as $products_id=>$value) {
			if (is_numeric($value['products_id'])) {
				$quantity=($quantity+$value['qty']);
			}
		}
		return $quantity;
	}
	function countCartTotalPrice($subtract_discount=1, $include_vat=0, $country_id=0) {
		$order=array();
		$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
		$address=$cart['user'];
		foreach ($cart['products'] as $shopping_cart_item=>$value) {
			if ($country_id>0) {
				$tax_rate=mslib_fe::taxRuleSet($value['tax_id'], 0, $country_id, 0);
				$value['tax_rate']=($tax_rate['total_tax_rate']/100);
			}
			if (is_numeric($value['products_id'])) {
				$product_amount=$value['final_price'];
				if (is_array($value['attributes'])) {
					foreach ($value['attributes'] as $attribute_key=>$attribute_values) {
						if ($attribute_values['price_prefix']=='+') {
							$product_amount=($product_amount+$attribute_values['options_values_price']);
						} else {
							$product_amount=($product_amount-$attribute_values['options_values_price']);
						}
					}
				}
				$subtotal_price=($value['qty']*$product_amount);
				if ($value['tax_rate'] and $include_vat) {
					$subtotal_price=($subtotal_price*($value['tax_rate']))+$subtotal_price;
				}
				$total_price=$total_price+$subtotal_price;
			}
		}
		if ($subtract_discount and $cart['discount']) {
			switch ($cart['discount_type']) {
				case 'percentage':
					$discount_price=(($total_price)/100*$cart['discount']);
					break;
				case 'price':
					$discount_price=$cart['discount'];
					break;
			}
			$total_price=$total_price-$discount_price;
		}
		return $total_price;
	}
	function countCartTotalTax($country_id=0) {
		$total_product_tax=0;
		$products_tax=array();
		$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
		$address=$cart['user'];
		foreach ($cart['products'] as $shopping_cart_item=>$value) {
			if ($country_id>0) {
				$tax_rate=mslib_fe::taxRuleSet($value['tax_id'], 0, $country_id, 0);
				$value['tax_rate']=($tax_rate['total_tax_rate']/100);
			}
			if (is_numeric($value['products_id'])) {
				$product_amount=$value['final_price'];
				$subtotal_price=($value['qty']*$product_amount);
				if ($value['tax_rate']) {
					$total_product_tax+=($subtotal_price*($value['tax_rate']));
				}
				// attributes
				if (is_array($value['attributes'])) {
					foreach ($value['attributes'] as $attribute_key=>$attribute_value) {
						$total_product_tax+=($attribute_value['options_values_price']*$value['qty'])*$value['tax_rate'];
					}
				}
				// attributes eof
				$products_tax['products_tax']['products_tax_rate_'.$value['products_id']]=$value['tax_rate'];
			}
		}
		$products_tax['total_tax']=$total_product_tax;
		return $products_tax;
	}
	function convertCartToOrder($cart) {
		// var for total amount
		$total_price=0;
		$order=array();
		$address=$cart['user'];
		// check for NULL, convert to empty string - typo3 v6.x related bug
		foreach ($address as $key=>$val) {
			if ($val==null || $val==NULL) {
				$address[$key]='';
			}
		}
		/*
		 * always use *_tax and *_total_tax_rate, unless need different calc for country/region
		 * WARNING: *_country_* and *_region_* not always have value, depends on the tax ruleset
		 * -----------------------------------------------------------------------------------------
		 */
		$orders_tax['shipping_tax']=(string)$address['shipping_tax'];
		$orders_tax['shipping_country_tax']=(string)$address['shipping_country_tax'];
		$orders_tax['shipping_region_tax']=(string)$address['shipping_region_tax'];
		$orders_tax['shipping_total_tax_rate']=(string)$address['shipping_total_tax_rate'];
		$orders_tax['shipping_country_tax_rate']=(string)$address['shipping_country_tax_rate'];
		$orders_tax['shipping_region_tax_rate']=(string)$address['shipping_region_tax_rate'];
		// ----------------------------------------------------------------------------------------
		$orders_tax['payment_tax']=(string)$address['payment_tax'];
		$orders_tax['payment_country_tax']=(string)$address['payment_country_tax'];
		$orders_tax['payment_region_tax']=(string)$address['payment_region_tax'];
		$orders_tax['payment_total_tax_rate']=(string)$address['payment_total_tax_rate'];
		$orders_tax['payment_country_tax_rate']=(string)$address['payment_country_tax_rate'];
		$orders_tax['payment_region_tax_rate']=(string)$address['payment_region_tax_rate'];
		// ----------------------------------------------------------------------------------------
		$grand_total=array();
		$grand_total['shipping_tax']=$orders_tax['shipping_tax'];
		$grand_total['payment_tax']=$orders_tax['payment_tax'];
		// add shipping & payment costs
		if ($address['shipping_method_costs']) {
			$grand_total['shipping_cost']=$address['shipping_method_costs'];
			$total_price=$total_price+$address['shipping_method_costs'];
		}
		if ($address['payment_method_costs']) {
			$grand_total['payment_cost']=$address['payment_method_costs'];
			$total_price=$total_price+$address['payment_method_costs'];
		}
		// first the account	
		if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			$customer_id=$GLOBALS['TSFE']->fe_user->user['uid'];
		} else {
			$tmp_user=mslib_fe::getUser($address['email'], 'email');
			if ($tmp_user['uid']) {
				$customer_id=$tmp_user['uid'];
			}
		}
		if (!$customer_id) {
			// add new account
			$insertArray=array();
			$insertArray['company']=$address['company'];
			$insertArray['name']=$address['first_name'].' '.$address['middle_name'].' '.$address['last_name'];
			$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
			$insertArray['first_name']=$address['first_name'];
			$insertArray['last_name']=$address['last_name'];
			$insertArray['username']=$address['email'];
			$insertArray['email']=$address['email'];
			if (!$address['street_name']) {
				// fallback for old custom checkouts
				$insertArray['street_name']=$address['address'];
				$insertArray['address_number']=$address['address_number'];
				$insertArray['address_ext']=$address['address_ext'];
				$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
				$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
			} else {
				$insertArray['street_name']=$address['street_name'];
				$insertArray['address_number']=$address['address_number'];
				$insertArray['address_ext']=$address['address_ext'];
				$insertArray['address']=$address['address'];
			}
			$insertArray['zip']=$address['zip'];
			$insertArray['telephone']=$address['telephone'];
			$insertArray['city']=$address['city'];
			$insertArray['country']=$address['country'];
			$insertArray['tx_multishop_code']=md5(uniqid('', true));
			$insertArray['tstamp']=time();
			$insertArray['crdate']=time();
			if (isset($address['tx_multishop_newsletter']) && !empty($address['tx_multishop_newsletter'])) {
				$insertArray['tx_multishop_newsletter']=$address['tx_multishop_newsletter'];
			} else {
				$insertArray['tx_multishop_newsletter']='';
			}
			$insertArray['page_uid']=$this->shop_pid;
			$insertArray['password']=mslib_befe::getHashedPassword(mslib_befe::generateRandomPassword(10));
			$insertArray['usergroup']=$this->conf['fe_customer_usergroup'];
			$insertArray['pid']=$this->conf['fe_customer_pid'];
			if (isset($this->cookie['HTTP_REFERER']) && !empty($this->cookie['HTTP_REFERER'])) {
				$insertArray['http_referer']=$this->cookie['HTTP_REFERER'];
			} else {
				$insertArray['http_referer']='';
			}
			$insertArray['ip_address']=$this->server['REMOTE_ADDR'];
			if ($this->ms['MODULES']['CHECKOUT_DISPLAY_VAT_ID_INPUT'] && !empty($address['tx_multishop_vat_id'])) {
				$insertArray['tx_multishop_vat_id']=$address['tx_multishop_vat_id'];
			}
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('fe_users', $insertArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if ($res) {
				$customer_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
				// ADD TT_ADDRESS RECORD
				$insertArray=array();
				$insertArray['tstamp']=time();
				$insertArray['company']=$address['company'];
				$insertArray['name']=$address['first_name'].' '.$address['middle_name'].' '.$address['last_name'];
				$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
				$insertArray['first_name']=$address['first_name'];
				$insertArray['middle_name']=$address['middle_name'];
				$insertArray['last_name']=$address['last_name'];
				$insertArray['email']=$address['email'];
				if (!$address['street_name']) {
					// fallback for old custom checkouts
					$insertArray['street_name']=$address['address'];
					$insertArray['address_number']=$address['address_number'];
					$insertArray['address_ext']=$address['address_ext'];
					$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
					$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
				} else {
					$insertArray['street_name']=$address['street_name'];
					$insertArray['address_number']=$address['address_number'];
					$insertArray['address_ext']=$address['address_ext'];
					$insertArray['address']=$address['address'];
				}
				$insertArray['zip']=$address['zip'];
				$insertArray['phone']=$address['telephone'];
				$insertArray['mobile']=$address['mobile'];
				$insertArray['city']=$address['city'];
				$insertArray['country']=$address['country'];
				$insertArray['gender']=$address['gender'];
				$insertArray['birthday']=strtotime($address['birthday']);
				if ($address['gender']=='m') {
					$insertArray['title']='Mr.';
				} else if ($address['gender']=='f') {
					$insertArray['title']='Mrs.';
				}
				$insertArray['region']=$address['state'];
				$insertArray['pid']=$this->conf['fe_customer_pid'];
				$insertArray['page_uid']=$this->shop_pid;
				$insertArray['tstamp']=time();
				$insertArray['tx_multishop_address_type']='billing';
				$insertArray['tx_multishop_default']=1;
				$insertArray['tx_multishop_customer_id']=$customer_id;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				// insert delivery into tt_address
				if (!$address['different_delivery_address']) {
					$insertArray=array();
					$insertArray['tstamp']=time();
					$insertArray['company']=$address['company'];
					$insertArray['name']=$address['first_name'].' '.$address['middle_name'].' '.$address['last_name'];
					$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
					$insertArray['first_name']=$address['first_name'];
					$insertArray['middle_name']=$address['middle_name'];
					$insertArray['last_name']=$address['last_name'];
					$insertArray['email']=$address['email'];
					if (!$address['street_name']) {
						// fallback for old custom checkouts
						$insertArray['street_name']=$address['address'];
						$insertArray['address_number']=$address['address_number'];
						$insertArray['address_ext']=$address['address_ext'];
						$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
						$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
					} else {
						$insertArray['street_name']=$address['street_name'];
						$insertArray['address_number']=$address['address_number'];
						$insertArray['address_ext']=$address['address_ext'];
						$insertArray['address']=$address['address'];
					}
					$insertArray['zip']=$address['zip'];
					$insertArray['phone']=$address['telephone'];
					$insertArray['mobile']=$address['mobile'];
					$insertArray['city']=$address['city'];
					$insertArray['country']=$address['country'];
					$insertArray['gender']=$address['gender'];
					$insertArray['birthday']=strtotime($address['birthday']);
					if ($address['gender']=='m') {
						$insertArray['title']='Mr.';
					} else if ($address['gender']=='f') {
						$insertArray['title']='Mrs.';
					}
					$insertArray['region']=$address['state'];
				} else {
					$insertArray=array();
					$insertArray['tx_multishop_customer_id']=$customer_id;
					$insertArray['tstamp']=time();
					$insertArray['company']=$address['delivery_company'];
					$insertArray['name']=$address['delivery_first_name'].' '.$address['delivery_middle_name'].' '.$address['delivery_last_name'];
					$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
					$insertArray['first_name']=$address['delivery_first_name'];
					$insertArray['middle_name']=$address['delivery_middle_name'];
					$insertArray['last_name']=$address['delivery_last_name'];
					$insertArray['email']=$address['delivery_email'];
					if (!$address['delivery_street_name']) {
						// fallback for old custom checkouts
						$insertArray['street_name']=$address['delivery_address'];
						$insertArray['address_number']=$address['delivery_address_number'];
						$insertArray['address_ext']=$address['delivery_address_ext'];
						$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
						$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
					} else {
						$insertArray['street_name']=$address['delivery_street_name'];
						$insertArray['address_number']=$address['delivery_address_number'];
						$insertArray['address_ext']=$address['delivery_address_ext'];
						$insertArray['address']=$address['delivery_address'];
					}
					$insertArray['zip']=$address['delivery_zip'];
					$insertArray['phone']=$address['delivery_telephone'];
					$insertArray['mobile']=$address['delivery_mobile'];
					$insertArray['city']=$address['delivery_city'];
					$insertArray['country']=$address['delivery_country'];
					$insertArray['gender']=$address['delivery_gender'];
					$insertArray['birthday']=strtotime($address['delivery_birthday']);
					if ($address['delivery_gender']=='m') {
						$insertArray['title']='Mr.';
					} else if ($address['delivery_gender']=='f') {
						$insertArray['title']='Mrs.';
					}
					$insertArray['region']=$address['delivery_state'];
				}
				$insertArray['pid']=$this->conf['fe_customer_pid'];
				$insertArray['page_uid']=$this->shop_pid;
				$insertArray['tstamp']=time();
				$insertArray['tx_multishop_customer_id']=$customer_id;
				$insertArray['tx_multishop_address_type']='delivery';
				$insertArray['tx_multishop_default']=0;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				// ADD TT_ADDRESS RECORD EOF				
				//hook to let other plugins further manipulate the create table query
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPostProc'])) {
					$params=array(
						'customer_id'=>&$customer_id
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPostProc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
				}
			}
		} else {
			// insert tt_address for existing customer if no record found
			if (!mslib_fe::getFeUserTTaddressDetails($customer_id, 'billing')) {
				// ADD TT_ADDRESS RECORD
				$insertArray=array();
				$insertArray['tstamp']=time();
				$insertArray['company']=$address['company'];
				$insertArray['name']=$address['first_name'].' '.$address['middle_name'].' '.$address['last_name'];
				$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
				$insertArray['first_name']=$address['first_name'];
				$insertArray['middle_name']=$address['middle_name'];
				$insertArray['last_name']=$address['last_name'];
				$insertArray['email']=$address['email'];
				if (!$address['street_name']) {
					// fallback for old custom checkouts
					$insertArray['street_name']=$address['address'];
					$insertArray['address_number']=$address['address_number'];
					$insertArray['address_ext']=$address['address_ext'];
					$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
					$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
				} else {
					$insertArray['street_name']=$address['street_name'];
					$insertArray['address_number']=$address['address_number'];
					$insertArray['address_ext']=$address['address_ext'];
					$insertArray['address']=$address['address'];
				}
				$insertArray['zip']=$address['zip'];
				$insertArray['phone']=$address['telephone'];
				$insertArray['mobile']=$address['mobile'];
				$insertArray['city']=$address['city'];
				$insertArray['country']=$address['country'];
				$insertArray['gender']=$address['gender'];
				$insertArray['birthday']=strtotime($address['birthday']);
				if ($address['gender']=='m') {
					$insertArray['title']='Mr.';
				} else if ($address['gender']=='f') {
					$insertArray['title']='Mrs.';
				}
				$insertArray['region']=$address['state'];
				$insertArray['pid']=$this->conf['fe_customer_pid'];
				$insertArray['page_uid']=$this->shop_pid;
				$insertArray['tstamp']=time();
				$insertArray['tx_multishop_address_type']='billing';
				$insertArray['tx_multishop_default']=1;
				$insertArray['tx_multishop_customer_id']=$customer_id;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
			if (!mslib_fe::getFeUserTTaddressDetails($customer_id, 'delivery')) {
				// insert delivery into tt_address
				if (!$address['different_delivery_address']) {
					$insertArray=array();
					$insertArray['tstamp']=time();
					$insertArray['company']=$address['company'];
					$insertArray['name']=$address['first_name'].' '.$address['middle_name'].' '.$address['last_name'];
					$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
					$insertArray['first_name']=$address['first_name'];
					$insertArray['middle_name']=$address['middle_name'];
					$insertArray['last_name']=$address['last_name'];
					$insertArray['email']=$address['email'];
					if (!$address['street_name']) {
						// fallback for old custom checkouts
						$insertArray['street_name']=$address['address'];
						$insertArray['address_number']=$address['address_number'];
						$insertArray['address_ext']=$address['address_ext'];
						$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
						$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
					} else {
						$insertArray['street_name']=$address['street_name'];
						$insertArray['address_number']=$address['address_number'];
						$insertArray['address_ext']=$address['address_ext'];
						$insertArray['address']=$address['address'];
					}
					$insertArray['zip']=$address['zip'];
					$insertArray['phone']=$address['telephone'];
					$insertArray['mobile']=$address['mobile'];
					$insertArray['city']=$address['city'];
					$insertArray['country']=$address['country'];
					$insertArray['gender']=$address['gender'];
					$insertArray['birthday']=strtotime($address['birthday']);
					if ($address['gender']=='m') {
						$insertArray['title']='Mr.';
					} else if ($address['gender']=='f') {
						$insertArray['title']='Mrs.';
					}
					$insertArray['region']=$address['state'];
				} else {
					$insertArray=array();
					$insertArray['tx_multishop_customer_id']=$customer_id;
					$insertArray['tstamp']=time();
					$insertArray['company']=$address['delivery_company'];
					$insertArray['name']=$address['delivery_first_name'].' '.$address['delivery_middle_name'].' '.$address['delivery_last_name'];
					$insertArray['name']=preg_replace('/\s+/', ' ', $insertArray['name']);
					$insertArray['first_name']=$address['delivery_first_name'];
					$insertArray['middle_name']=$address['delivery_middle_name'];
					$insertArray['last_name']=$address['delivery_last_name'];
					$insertArray['email']=$address['delivery_email'];
					if (!$address['delivery_street_name']) {
						// fallback for old custom checkouts
						$insertArray['street_name']=$address['delivery_address'];
						$insertArray['address_number']=$address['delivery_address_number'];
						$insertArray['address_ext']=$address['delivery_address_ext'];
						$insertArray['address']=$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext'] ? '-'.$insertArray['address_ext'] : '');
						$insertArray['address']=preg_replace('/\s+/', ' ', $insertArray['address']);
					} else {
						$insertArray['street_name']=$address['delivery_street_name'];
						$insertArray['address_number']=$address['delivery_address_number'];
						$insertArray['address_ext']=$address['delivery_address_ext'];
						$insertArray['address']=$address['delivery_address'];
					}
					$insertArray['zip']=$address['delivery_zip'];
					$insertArray['phone']=$address['delivery_telephone'];
					$insertArray['mobile']=$address['delivery_mobile'];
					$insertArray['city']=$address['delivery_city'];
					$insertArray['country']=$address['delivery_country'];
					$insertArray['gender']=$address['delivery_gender'];
					$insertArray['birthday']=strtotime($address['delivery_birthday']);
					if ($address['delivery_gender']=='m') {
						$insertArray['title']='Mr.';
					} else if ($address['delivery_gender']=='f') {
						$insertArray['title']='Mrs.';
					}
					$insertArray['region']=$address['delivery_state'];
				}
				$insertArray['pid']=$this->conf['fe_customer_pid'];
				$insertArray['page_uid']=$this->shop_pid;
				$insertArray['tstamp']=time();
				$insertArray['tx_multishop_customer_id']=$customer_id;
				$insertArray['tx_multishop_address_type']='delivery';
				$insertArray['tx_multishop_default']=0;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		}
		if ($customer_id) {
			// now add the order
			$insertArray=array();
			$insertArray['customer_id']=$customer_id;
			$insertArray['page_uid']=$this->shop_pid;
			if (isset($GLOBALS['TSFE']->fe_user->user['uid']) && !empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
				$insertArray['cruser_id']=$GLOBALS['TSFE']->fe_user->user['uid'];
			} else {
				$insertArray['cruser_id']='';
			}
			$insertArray['customer_comments']=$this->post['customer_comments'];
			$insertArray['billing_company']=$address['company'];
			$insertArray['billing_first_name']=$address['first_name'];
			$insertArray['billing_middle_name']=$address['middle_name'];
			$insertArray['billing_last_name']=$address['last_name'];
			$insertArray['billing_name']=preg_replace('/ +/', ' ', $address['first_name'].' '.$address['middle_name'].' '.$address['last_name']);
			$insertArray['billing_email']=$address['email'];
			$insertArray['billing_gender']=$address['gender'];
			$insertArray['billing_birthday']=strtotime($address['birthday']);
			if (!$address['street_name']) {
				// fallback for old custom checkouts
				$insertArray['billing_street_name']=$address['address'];
				$insertArray['billing_address_number']=$address['address_number'];
				$insertArray['billing_address_ext']=$address['address_ext'];
				$insertArray['billing_address']=$insertArray['billing_street_name'].' '.$insertArray['billing_address_number'].($insertArray['billing_address_ext'] ? '-'.$insertArray['billing_address_ext'] : '');
				$insertArray['billing_address']=preg_replace('/\s+/', ' ', $insertArray['billing_address']);
			} else {
				$insertArray['billing_street_name']=$address['street_name'];
				$insertArray['billing_address_number']=$address['address_number'];
				$insertArray['billing_address_ext']=$address['address_ext'];
				$insertArray['billing_address']=$address['address'];
			}
			/*
						$insertArray['billing_street_name']			=	$address['street_name'];
						$insertArray['billing_address_number']		=	$address['address_number'];
						$insertArray['billing_address_ext']			=	$address['address_ext'];
						$insertArray['billing_address']				=	$insertArray['billing_street_name'].' '.$insertArray['billing_address_number'].($insertArray['billing_address_ext']? '-'.$insertArray['billing_address_ext']:'');
						$insertArray['billing_address'] 			=	preg_replace('/\s+/', ' ', $insertArray['billing_address']);
			*/
			$insertArray['billing_building']='';
			$insertArray['billing_room']='';
			$insertArray['billing_city']=$address['city'];
			$insertArray['billing_zip']=$address['zip'];
			$insertArray['billing_region']=$address['state'];
			$insertArray['billing_country']=$address['country'];
			$insertArray['billing_telephone']=$address['telephone'];
			$insertArray['billing_mobile']=$address['mobile'];
			$insertArray['billing_fax']='';
			if ($this->ms['MODULES']['CHECKOUT_DISPLAY_VAT_ID_INPUT'] && !empty($address['tx_multishop_vat_id'])) {
				$insertArray['billing_vat_id']=$address['tx_multishop_vat_id'];
			}
			if (!$address['different_delivery_address']) {
				$insertArray['delivery_email']=$insertArray['billing_email'];
				$insertArray['delivery_company']=$insertArray['billing_company'];
				$insertArray['delivery_first_name']=$insertArray['billing_first_name'];
				$insertArray['delivery_middle_name']=$insertArray['billing_middle_name'];
				$insertArray['delivery_last_name']=$insertArray['billing_last_name'];
				$insertArray['delivery_telephone']=$insertArray['billing_telephone'];
				$insertArray['delivery_mobile']=$insertArray['billing_mobile'];
				$insertArray['delivery_gender']=$insertArray['billing_gender'];
				$insertArray['delivery_street_name']=$insertArray['billing_street_name'];
				$insertArray['delivery_address_number']=$insertArray['billing_address_number'];
				$insertArray['delivery_address_ext']=$insertArray['billing_address_ext'];
				$insertArray['delivery_address']=$insertArray['billing_address'];
				$insertArray['delivery_zip']=$insertArray['billing_zip'];
				$insertArray['delivery_city']=$insertArray['billing_city'];
				$insertArray['delivery_country']=$insertArray['billing_country'];
				$insertArray['delivery_telephone']=$insertArray['billing_telephone'];
				$insertArray['delivery_region']=$insertArray['billing_region'];
				$insertArray['delivery_name']=$insertArray['billing_name'];
			} else {
				$insertArray['delivery_company']=$address['delivery_company'];
				$insertArray['delivery_first_name']=$address['delivery_first_name'];
				$insertArray['delivery_middle_name']=$address['delivery_middle_name'];
				$insertArray['delivery_last_name']=$address['delivery_last_name'];
				$insertArray['delivery_name']=preg_replace('/ +/', ' ', $address['delivery_first_name'].' '.$address['delivery_middle_name'].' '.$address['delivery_last_name']);
				$insertArray['delivery_email']=$address['delivery_email'];
				$insertArray['delivery_gender']=$address['delivery_gender'];
				if (!$address['street_name']) {
					// fallback for old custom checkouts
					$insertArray['delivery_street_name']=$address['delivery_address'];
					$insertArray['delivery_address_number']=$address['delivery_address_number'];
					$insertArray['delivery_address_ext']=$address['delivery_address_ext'];
					$insertArray['delivery_address']=$insertArray['delivery_street_name'].' '.$insertArray['delivery_address_number'].($insertArray['delivery_address_ext'] ? '-'.$insertArray['delivery_address_ext'] : '');
					$insertArray['delivery_address']=preg_replace('/\s+/', ' ', $insertArray['delivery_address']);
				} else {
					$insertArray['delivery_street_name']=$address['delivery_street_name'];
					$insertArray['delivery_address_number']=$address['delivery_address_number'];
					$insertArray['delivery_address_ext']=$address['delivery_address_ext'];
					$insertArray['delivery_address']=$address['delivery_address'];
				}
				/*
								$insertArray['delivery_street_name']		=	$address['delivery_street_name'];
								$insertArray['delivery_address_number']		=	$address['delivery_address_number'];
								$insertArray['delivery_address_ext']		=	$address['delivery_address_ext'];
								$insertArray['delivery_address']			=	$insertArray['delivery_street_name'].' '.$insertArray['delivery_address_number'].($insertArray['delivery_address_ext']? '-'.$insertArray['delivery_address_ext']:'');
								$insertArray['delivery_address'] 			=	preg_replace('/\s+/', ' ', $insertArray['delivery_address']);
				*/
				$insertArray['delivery_city']=$address['delivery_city'];
				$insertArray['delivery_zip']=$address['delivery_zip'];
				$insertArray['delivery_room']='';
				$insertArray['delivery_region']=$address['delivery_state'];
				$insertArray['delivery_country']=$address['delivery_country'];
				$insertArray['delivery_telephone']=$address['delivery_telephone'];
				$insertArray['delivery_mobile']=$address['delivery_mobile'];
				$insertArray['delivery_fax']='';
				$insertArray['delivery_vat_id']='';
			}
			$insertArray['bill']=1;
			$insertArray['crdate']=time();
			$insertArray['shipping_method']=$address['shipping_method'];
			$insertArray['shipping_method_label']=$address['shipping_method_label'];
			$insertArray['payment_method']=$address['payment_method'];
			$insertArray['payment_method_label']=$address['payment_method_label'];
			$insertArray['shipping_method_costs']=$address['shipping_method_costs'];
			$insertArray['payment_method_costs']=$address['payment_method_costs'];
			$insertArray['hash']=md5(uniqid('', true));
			$insertArray['store_currency']=$this->ms['MODULES']['CURRENCY_ARRAY']['cu_iso_3'];
			if (isset($this->ms['MODULES']['CUSTOMER_ARRAY']['cu_iso_3']) && !empty($this->ms['MODULES']['CUSTOMER_ARRAY']['cu_iso_3'])) {
				$insertArray['customer_currency']=$this->ms['MODULES']['CUSTOMER_ARRAY']['cu_iso_3'];
			} else {
				$insertArray['customer_currency']='';
			}
			if (isset($this->cookie['currency_rate']) && !empty($this->cookie['currency_rate'])) {
				$insertArray['currency_rate']=$this->cookie['currency_rate'];
			} else {
				$insertArray['currency_rate']=1;
			}
			$insertArray['language_id']=$this->sys_language_uid;
			// get default orders status
			$status=mslib_fe::getDefaultOrdersStatus($this->sys_language_uid);
			$insertArray['status']=$status['id'];
			if (isset($this->cookie['HTTP_REFERER']) && !empty($this->cookie['HTTP_REFERER'])) {
				$insertArray['http_referer']=$this->cookie['HTTP_REFERER'];
			} else {
				$insertArray['http_referer']='';
			}
			$insertArray['ip_address']=$this->server['REMOTE_ADDR'];
			$insertArray['user_agent']=$this->server['HTTP_USER_AGENT'];
			if (isset($address['expected_delivery_date'])) {
				$insertArray['expected_delivery_date']=$address['expected_delivery_date'];
			}
			//$insertArray['orders_tax_data']			=	serialize($orders_tax);
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPreProc'])) {
				// hook
				$params=array(
					'ms'=>$this->ms,
					'insertArray'=>&$insertArray
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPreProc'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
				// hook oef
			}
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_orders', $insertArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			// now add the order eof
			$orders_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPostProc'])) {
				// hook
				$params=array(
					'orders_id'=>&$orders_id,
					'insertArray'=>&$insertArray
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPostProc'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
				// hook oef
			}
			if ($orders_id) {
				// now add the orders products
				if ($cart['user']['payment_method']) {
					$this->ms['payment_method']=$cart['user']['payment_method'];
				} elseif ($cart['user']['shipping_method']) {
					$this->ms['shipping_method']=$cart['user']['shipping_method'];
				}
				foreach ($cart['products'] as $shopping_cart_item=>$value) {
					if (is_numeric($value['products_id'])) {
						$insertArray=array();
						$insertArray['orders_id']=$orders_id;
						$insertArray['products_id']=$value['products_id'];
						$insertArray['categories_id']=$value['categories_id'];
						// get all cats
						$cats=mslib_fe::Crumbar($value['categories_id']);
						$cats=array_reverse($cats);
						if (count($cats)>0) {
							$i=0;
							foreach ($cats as $cat) {
								$insertArray['categories_id_'.$i]=$cat['id'];
								$insertArray['categories_name_'.$i]=$cat['name'];
								$i++;
							}
						}
						// get all cats eof						
						if (isset($value['manufacturers_id']) && !empty($value['manufacturers_id'])) {
							$insertArray['manufacturers_id']=$value['manufacturers_id'];
						} else {
							$insertArray['manufacturers_id']='';
						}
						if (isset($value['order_unit_id']) && !empty($value['order_unit_id'])) {
							$insertArray['order_unit_id']=$value['order_unit_id'];
						} else {
							$insertArray['order_unit_id']='';
						}
						if (isset($value['order_unit_name']) && !empty($value['order_unit_name'])) {
							$insertArray['order_unit_name']=$value['order_unit_name'];
						} else {
							$insertArray['order_unit_name']='';
						}
						if (isset($value['order_unit_code']) && !empty($value['order_unit_code'])) {
							$insertArray['order_unit_code']=$value['order_unit_code'];
						} else {
							$insertArray['order_unit_code']='';
						}
						$insertArray['qty']=$value['qty'];
						$insertArray['products_tax']=($value['tax_rate']*100);
						$insertArray['products_name']=$value['products_name'];
						$insertArray['products_model']=$value['products_model'];
						$insertArray['products_description']=$value['products_shortdescription'];
						if (is_array($value['attributes'])) {
							// loading the attributes	
							$insertArray['products_description'].="\n".strip_tags(mslib_fe::showAttributes($value['products_id'], '', $sessionData, 1));
							// loading the attributes eof
						}
						$insertArray['products_price']=$value['products_price'];
						$insertArray['final_price']=$value['final_price'];
						$insertArray['type']='P'; // P for Product, S for Subscription (returning-costs)
						$insertArray['ean_code']=$value['ean_code'];
						$insertArray['sku_code']=$value['sku_code'];
						$insertArray['vendor_code']=$value['vendor_code'];
						// micro download
						if ($value['file_location'] || $value['file_remote_location']) {
							$insertArray['file_label']=$value['file_label'];
							$insertArray['file_location']=$value['file_location'];
							$insertArray['file_remote_location']=$value['file_remote_location'];
							$insertArray['file_number_of_downloads']=$value['file_number_of_downloads'];
							$insertArray['file_download_code']=md5(uniqid(rand()).uniqid(rand()));
						}
						// micro download eof						
						/*
						 * always use total_tax and total_tax_rate, unless need different calc for country/region
						 * WARNING: country_* and region_* not always have value, depends on the tax ruleset
						 * -----------------------------------------------------------------------------------------
						 */
						$product_tax['country_tax_rate']=(string)$value['country_tax_rate'];
						$product_tax['region_tax_rate']=(string)$value['region_tax_rate'];
						$product_tax['total_tax_rate']=(string)$value['tax_rate'];
						// -----------------------------------------------------------------------------------------
						$product_tax['country_tax']=(string)$value['country_tax'];
						$product_tax['region_tax']=(string)$value['region_tax'];
						$product_tax['total_tax']=(string)$value['tax'];
						$product_tax['total_attributes_tax']=(string)$value['total_attributes_tax'];
						$product_tax['total_tax']=$product_tax['total_tax'];
						// -----------------------------------------------------------------------------------------
						// bugfixes bas
						$sub_total_excluding_vat['final_price']=$sub_total['final_price']+($value['final_price']*$value['qty']);
						$sub_total['final_price']=$sub_total['final_price']+($value['final_price']*$value['qty']);
						$sub_total['total_tax']=$sub_total['total_tax']+($product_tax['total_tax']*$value['qty']);
						$sub_total['attributes_tax']=$sub_total['attributes_tax']+($product_tax['total_attributes_tax']*$value['qty']);
						$total_order_tax['total_tax']=$total_order_tax['total_tax']+($product_tax['total_tax']*$value['qty']);
						$total_order_tax['total_attributes_tax']=$total_order_tax['total_attributes_tax']+($product_tax['total_attributes_tax']*$value['qty']);
						$insertArray['products_tax_data']=serialize($product_tax);
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrdersProductPreProc'])) {
							// hook
							$params=array(
								'ms'=>$this->ms,
								'value'=>$value,
								'insertArray'=>&$insertArray
							);
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrdersProductPreProc'] as $funcRef) {
								t3lib_div::callUserFunction($funcRef, $params, $this);
							}
							// hook oef
						}
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_orders_products', $insertArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$orders_products_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
						if ($this->ms['MODULES']['SUBTRACT_STOCK']) {
							if ($this->ms['MODULES']['PRODUCT_ATTRIBUTES_STOCK']) {
								$sql_as_data=array();
								$attributes_count=count($value['attributes']);
								foreach ($value['attributes'] as $attribute_key=>$attribute_values) {
									$sql_as_data[]='(pas.options_id = '.$attribute_values['options_id'].' and pas.options_values_id = '.$attribute_values['options_values_id'].')';
								}
								$sql_as="select pasg.group_id, pasg.attributes_stock from tx_multishop_products_attributes_stock_group pasg, tx_multishop_products_attributes_stock pas where pasg.products_id = ".$value['products_id']." and (".implode(' or ', $sql_as_data).") and pasg.group_id = pas.group_id";
								$res=$GLOBALS['TYPO3_DB']->sql_query($sql_as);
								$total_rows=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
								$used_group=0;
								if ($total_rows>1) {
									$group_counter=array();
									while ($rs_as=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
										$group_counter[$rs_as['group_id']]+=1;
									}
									foreach ($group_counter as $ref_group_id=>$group_ctr_result) {
										if ($group_ctr_result==$attributes_count) {
											$used_group=$ref_group_id;
											break;
										}
									}
								} else {
									$rs_as=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
									$used_group=$rs_as['group_id'];
								}
								if ($used_group>0) {
									$str="update tx_multishop_products_attributes_stock_group set attributes_stock=(attributes_stock-".$value['qty'].") where group_id='".$used_group."'";
									$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								}
								$str="update tx_multishop_products set products_quantity=(products_quantity-".$value['qty'].") where products_id='".$value['products_id']."'";
								$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								$str="select products_quantity, alert_quantity_threshold from tx_multishop_products where products_id='".$value['products_id']."'";
								$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
								if ($row['products_quantity']<=$row['alert_quantity_threshold']) {
									$page=mslib_fe::getCMScontent('email_alert_quantity_threshold_letter', $GLOBALS['TSFE']->sys_language_uid);
									if ($page[0]['content']) {
										// loading the email confirmation letter eof
										// replacing the variables with dynamic values
										$array1=array();
										$array2=array();
										$array1[]='###ORDERED_QTY###';
										$array2[]=$value['qty'];
										$array1[]='###CURRENT_PRODUCT_QUANTITY###';
										$array2[]=$row['products_quantity'];
										$array1[]='###PRODUCT_NAME###';
										$array2[]=$value['products_name'];
										$link_edit_prod=$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2002', 'tx_multishop_pi1[page_section]=admin_ajax&pid='.$value['products_id'].'&cid='.$value['categories_id'].'&action=edit_product');
										$array1[]='###DIRECT_EDIT_PRODUCT_LINK###';
										$array2[]='<a href="'.$link_edit_prod.'" target="_blank">edit product stock</a>';
										// now mail a copy to the merchant
										$merchant=array();
										$merchant['name']=$this->ms['MODULES']['STORE_NAME'];
										$merchant['email']=$this->ms['MODULES']['STORE_EMAIL'];
										$mailTo=array();
										$mailTo[]=$merchant;
										//hook to let other plugins further manipulate the replacers
										if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['mailAlertQuantityThresholdPostProc'])) {
											$params=array(
												'array1'=>&$array1,
												'array2'=>&$array2,
												'page'=>&$page,
												'mailTo'=>&$mailTo
											);
											foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['mailAlertQuantityThresholdPostProc'] as $funcRef) {
												t3lib_div::callUserFunction($funcRef, $params, $this);
											}
										}
										//end of hook to let other plugins further manipulate the replacers
										if ($page[0]['content']) {
											$page[0]['content']=str_replace($array1, $array2, $page[0]['content']);
										}
										if ($page[0]['name']) {
											$page[0]['name']=str_replace($array1, $array2, $page[0]['name']);
										}
										foreach ($mailTo as $mailuser) {
											mslib_fe::mailUser($mailuser, $page[0]['name'], $page[0]['content'], $this->ms['MODULES']['STORE_EMAIL'], $this->ms['MODULES']['STORE_NAME']);
										}
									}
								}
								if ($row['products_quantity']<1) {
									// stock is negative or zero. lets turn of the product
									$str="update tx_multishop_products set products_status=0 where products_id='".$value['products_id']."'";
									$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								}
							} else {
								// now decrease the stocklevel
								$str="update tx_multishop_products set products_quantity=(products_quantity-".$value['qty'].") where products_id='".$value['products_id']."'";
								$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								$str="select products_quantity, alert_quantity_threshold from tx_multishop_products where products_id='".$value['products_id']."'";
								$res=$GLOBALS['TYPO3_DB']->sql_query($str);
								$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
								if ($row['products_quantity']<=$row['alert_quantity_threshold']) {
									$page=mslib_fe::getCMScontent('email_alert_quantity_threshold_letter', $GLOBALS['TSFE']->sys_language_uid);
									if ($page[0]['content']) {
										// loading the email confirmation letter eof
										// replacing the variables with dynamic values
										$array1=array();
										$array2=array();
										$array1[]='###ORDERED_QTY###';
										$array2[]=$value['qty'];
										$array1[]='###CURRENT_PRODUCT_QUANTITY###';
										$array2[]=$row['products_quantity'];
										$array1[]='###PRODUCT_NAME###';
										$array2[]=$value['products_name'];
										$link_edit_prod=$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2002', 'tx_multishop_pi1[page_section]=admin_ajax&pid='.$value['products_id'].'&cid='.$value['categories_id'].'&action=edit_product');
										$array1[]='###DIRECT_EDIT_PRODUCT_LINK###';
										$array2[]='<a href="'.$link_edit_prod.'" target="_blank">edit product stock</a>';
										// now mail a copy to the merchant
										$merchant=array();
										$merchant['name']=$this->ms['MODULES']['STORE_NAME'];
										$merchant['email']=$this->ms['MODULES']['STORE_EMAIL'];
										$mailTo=array();
										$mailTo[]=$merchant;
										//hook to let other plugins further manipulate the replacers
										if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['mailAlertQuantityThresholdPostProc'])) {
											$params=array(
												'array1'=>&$array1,
												'array2'=>&$array2,
												'page'=>&$page,
												'mailTo'=>&$mailTo
											);
											foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['mailAlertQuantityThresholdPostProc'] as $funcRef) {
												t3lib_div::callUserFunction($funcRef, $params, $this);
											}
										}
										//end of hook to let other plugins further manipulate the replacers
										if ($page[0]['content']) {
											$page[0]['content']=str_replace($array1, $array2, $page[0]['content']);
										}
										if ($page[0]['name']) {
											$page[0]['name']=str_replace($array1, $array2, $page[0]['name']);
										}
										foreach ($mailTo as $mailuser) {
											mslib_fe::mailUser($mailuser, $page[0]['name'], $page[0]['content'], $this->ms['MODULES']['STORE_EMAIL'], $this->ms['MODULES']['STORE_NAME']);
										}
									}
								}
								if ($row['products_quantity']<1) {
									if ($this->ms['MODULES']['DISABLE_PRODUCT_WHEN_NEGATIVE_STOCK']) {
										if (!$this->ms['MODULES']['ALLOW_ORDER_OUT_OF_STOCK_PRODUCT']) {
											// stock is negative or zero. lets turn off the product
											mslib_befe::disableProduct($value['products_id']);
										}
									}
								}
								// now decrease the stocklevel eof
							}
						}
						if ($orders_products_id and is_array($value['attributes'])) {
							foreach ($value['attributes'] as $attribute_key=>$attribute_values) {
								$str="SELECT products_options_name,listtype from tx_multishop_products_options o where o.products_options_id='".$attribute_key."' ";
								$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
								$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
//								print_r($row['listtype']);
								switch ($row['listtype']) {
									case 'checkbox':
										$items=$attribute_values;
										break;
									default:
										$items=array($attribute_values);
										break;
								}
								foreach ($items as $item) {
									$attributes_tax['country_tax']=(string)$item['country_tax'];
									$attributes_tax['region_tax']=(string)$item['region_tax'];
									$attributes_tax['tax']=(string)$item['tax'];
									$insertAttributes=array();
									$insertAttributes['orders_id']=$orders_id;
									$insertAttributes['orders_products_id']=$orders_products_id;
									$insertAttributes['products_options']=$item['products_options_name'];
									$insertAttributes['products_options_values']=$item['products_options_values_name'];
									$insertAttributes['options_values_price']=$item['options_values_price'];
									$insertAttributes['price_prefix']=$item['price_prefix'];
									$insertAttributes['products_options_id']=$item['options_id'];
									$insertAttributes['products_options_values_id']=$item['options_values_id'];
									$sub_total_excluding_vat['attributes_price']+=$item['price_prefix'].$item['options_values_price']*$value['qty'];
									$sub_total['attributes_price']+=$item['price_prefix'].$item['options_values_price']*$value['qty'];
									$insertAttributes['attributes_tax_data']=serialize($attributes_tax);
									$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_orders_products_attributes', $insertAttributes);
									$res=$GLOBALS['TYPO3_DB']->sql_query($query);
								}
							}
						}
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrdersProductsPostProc'])) {
							// hook
							$params=array(
								'ms'=>$this->ms,
								'orders_products_id'=>$orders_products_id,
								'insertArray'=>$insertArray,
								'insertAttributes'=>$insertAttributes,
								'cart'=>$cart
							);
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrdersProductsPostProc'] as $funcRef) {
								t3lib_div::callUserFunction($funcRef, $params, $this);
							}
							// hook eof
						}
					}
				}
				$updateArray=array();
				$orders_tax['sub_total_excluding_vat']=(string)array_sum($sub_total_excluding_vat);
				$orders_tax['sub_total']=(string)array_sum($sub_total);
				$orders_tax['total_orders_tax']=(string)array_sum($total_order_tax);
				$orders_tax['total_orders_tax_including_discount']=$orders_tax['total_orders_tax'];
				$grand_total['sub_total']=array_sum($sub_total);
				if ($cart['discount_type']) {
					switch ($cart['discount_type']) {
						case 'percentage':
							$discount_amount=($orders_tax['sub_total']/100*$cart['discount']);
							$discount_percentage=$cart['discount'];
							break;
						case 'price':
							$discount_amount=$cart['discount'];
							$discount_percentage=($discount_amount/$orders_tax['sub_total']*100);
							break;
					}
					if ($discount_amount) {
						$grand_total['sub_total']=($grand_total['sub_total']-$discount_amount);
						$orders_tax['total_orders_tax_including_discount']=($orders_tax['total_orders_tax_including_discount']/100*(100-$discount_percentage));
					}
					$updateArray['discount']=$discount_amount;
				}
				$orders_tax['total_orders_tax']+=$orders_tax['shipping_tax'];
				$orders_tax['total_orders_tax']+=$orders_tax['payment_tax'];
				$orders_tax['total_orders_tax_including_discount']+=$orders_tax['shipping_tax'];
				$orders_tax['total_orders_tax_including_discount']+=$orders_tax['payment_tax'];
				$orders_tax['grand_total']=(string)array_sum($grand_total);
				$updateArray['orders_tax_data']=serialize($orders_tax);
				$updateArray['grand_total']=$orders_tax['grand_total'];
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders', 'orders_id=\''.$orders_id.'\'', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderDiscountPreProc'])) {
					// hook
					$params=array(
						'ms'=>$this->ms,
						'orders_id'=>$orders_id,
						'cart'=>&$cart
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderDiscountPreProc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
					// hook oef
				}
				if ($cart['discount']) {
					if ($cart['discount']) {
						if ($cart['coupon_code']) {
							$str="update tx_multishop_coupons set times_used=(times_used+1) where code='".addslashes($cart['coupon_code'])."'";
							$res=$GLOBALS['TYPO3_DB']->sql_query($str);
							$cart['coupon_code']='';
						}
						$cart['discount']='';
						$cart['discount_type']='';
					}
				}
				mslib_befe::storeCustomerCartContent($cart, $customer_id, 1);
				// debug
				/*
				$order=mslib_fe::getOrder($orders_id);
				$ORDER_DETAILS=mslib_fe::printOrderDetailsTable($order,'email');
				echo $ORDER_DETAILS;
				die();
				*/
				unset($cart['products']);
				unset($cart['user']);
				unset($cart['discount_type']);
				unset($cart['discount_amount']);
				$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
				$GLOBALS['TSFE']->storeSessionData();
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPostProc'])) {
					// hook
					$params=array(
						'orders_id'=>&$orders_id
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['insertOrderPostProc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
					// hook oef
				}
				if ($this->ms['MODULES']['ORDERS_CUSTOM_EXPORT_SCRIPT']) {
					if (strstr($this->ms['MODULES']['ORDERS_CUSTOM_EXPORT_SCRIPT'], "..")) {
						die('error in ORDERS_CUSTOM_EXPORT_SCRIPT value');
					} else {
						require($this->DOCUMENT_ROOT.$this->ms['MODULES']['ORDERS_CUSTOM_EXPORT_SCRIPT'].'.php');
					}
				}
				// if grand total is zero we have to activate directly
				$order=mslib_fe::getOrder($orders_id);
				if ($order['orders_id'] and $order['grand_total']<0.001) {
					mslib_fe::updateOrderStatusToPaid($order['orders_id']);
				}
				return $orders_id;
			}
		}
	}
	function setShippingMethod($shipping_method) {
		if (!$shipping_method) {
			return 0;
		}
		$priceArray=mslib_fe::getShippingCosts($this->cart['user']['countries_id'], $shipping_method);
		$price=$priceArray['shipping_costs'];
		if ($price) {
			$this->cart['user']['shipping_method_costs']=$price;
		} else {
			$this->cart['user']['shipping_method_costs']=0;
		}
		$shipping_method=mslib_fe::getShippingMethod($shipping_method, 's.id', $this->cart['user']['countries_id']);
		if ($shipping_method['tax_id'] && $this->cart['user']['shipping_method_costs']) {
			$this->cart['user']['shipping_total_tax_rate']=$shipping_method['tax_rate'];
			if ($shipping_method['country_tax_rate']) {
				$this->cart['user']['shipping_country_tax_rate']=$shipping_method['country_tax_rate'];
				$this->cart['user']['shipping_country_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['shipping_method_costs']*($shipping_method['country_tax_rate']));
			} else {
				$this->cart['user']['shipping_country_tax_rate']=0;
				$this->cart['user']['shipping_country_tax']=0;
			}
			if ($shipping_method['region_tax_rate']) {
				$this->cart['user']['shipping_region_tax_rate']=$shipping_method['region_tax_rate'];
				$this->cart['user']['shipping_region_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['shipping_method_costs']*($shipping_method['region_tax_rate']));
			} else {
				$this->cart['user']['shipping_region_tax_rate']=0;
				$this->cart['user']['shipping_region_tax']=0;
			}
			if ($this->cart['user']['shipping_region_tax'] && $this->cart['user']['shipping_country_tax']) {
				$this->cart['user']['shipping_tax']=$this->cart['user']['shipping_country_tax']+$this->cart['user']['shipping_region_tax'];
			} else {
				$this->cart['user']['shipping_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['shipping_method_costs']*($shipping_method['tax_rate']));
			}
		} else {
			$this->cart['user']['shipping_tax']=0;
			$this->cart['user']['shipping_country_tax']=0;
			$this->cart['user']['shipping_region_tax']=0;
			$this->cart['user']['shipping_total_tax_rate']=0;
			$this->cart['user']['shipping_country_tax_rate']=0;
			$this->cart['user']['shipping_region_tax_rate']=0;
		}
		$this->cart['user']['shipping_method']=$shipping_method['code'];
		$this->cart['user']['shipping_method_label']=$shipping_method['name'];
		self::storeCart();
	}
	function setPaymentMethod($payment_method) {
		if (!$payment_method) {
			return 0;
		}
		$payment_method=mslib_fe::getPaymentMethod($payment_method);
		if ($payment_method['handling_costs']) {
			if (!strstr($payment_method['handling_costs'], "%")) {
				$this->cart['user']['payment_method_costs']=$payment_method['handling_costs'];
			} else {
				// calculate total payment costs based by %
				$subtotal=$this->cart['summarize']['sub_total_including_vat'];
				if ($subtotal) {
					if (strstr($payment_method['handling_costs'], "%")) {
						$percentage=str_replace("%", '', $payment_method['handling_costs']);
						$payment_method_costs=($subtotal/100*$percentage);
					} else {
						$payment_method_costs=$payment_method['handling_costs'];
					}
				}
			}
		} else {
			$this->cart['user']['payment_method_costs']=0;
		}
		if ($payment_method['tax_id'] && $this->cart['user']['payment_method_costs']) {
			$this->cart['user']['payment_total_tax_rate']=$payment_method['tax_rate'];
			if ($payment_method['country_tax_rate']) {
				$this->cart['user']['payment_country_tax_rate']=$payment_method['country_tax_rate'];
				$this->cart['user']['payment_country_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['payment_method_costs']*($payment_method['country_tax_rate']));
			} else {
				$this->cart['user']['payment_country_tax_rate']=0;
				$this->cart['user']['payment_country_tax']=0;
			}
			if ($payment_method['region_tax_rate']) {
				$this->cart['user']['payment_region_tax_rate']=$payment_method['region_tax_rate'];
				$this->cart['user']['payment_region_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['payment_method_costs']*($payment_method['region_tax_rate']));
			} else {
				$this->cart['user']['payment_region_tax_rate']=0;
				$this->cart['user']['payment_region_tax']=0;
			}
			if ($this->cart['user']['payment_region_tax'] && $this->cart['user']['payment_country_tax']) {
				$this->cart['user']['payment_tax']=$this->cart['user']['payment_country_tax']+$this->cart['user']['payment_region_tax'];
			} else {
				$this->cart['user']['payment_tax']=mslib_fe::taxDecimalCrop($this->cart['user']['payment_method_costs']*($payment_method['tax_rate']));
			}
		} else {
			$this->cart['user']['payment_tax']=0;
			$this->cart['user']['payment_country_tax']=0;
			$this->cart['user']['payment_region_tax']=0;
			$this->cart['user']['payment_total_tax_rate']=0;
			$this->cart['user']['payment_country_tax_rate']=0;
			$this->cart['user']['payment_region_tax_rate']=0;
		}
		$this->cart['user']['payment_method']=$payment_method['code'];
		$this->cart['user']['payment_method_label']=$payment_method['name'];
		// payment eof
		self::storeCart();
	}
	function storeCart() {
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $this->cart);
		$GLOBALS['TSFE']->storeSessionData();
	}
	function setCountry($countries_name) {
		$str3="SELECT * from static_countries where cn_short_en='".addslashes($countries_name)."' ";
		$qry3=$GLOBALS['TYPO3_DB']->sql_query($str3);
		$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3);
		$this->cart['user']['countries_id']=$row3['cn_iso_nr'];
		$this->cart['user']['country']=$row3['cn_short_en'];
		self::storeCart();
		return $this->cart['user']['countries_id'];
	}
	function getCountry() {
		return $this->cart['user']['countries_id'];
	}
	function getHtmlCartContents($sectionTemplateType='') {
		$disable_product_status_col=false;
		$content='';
		switch ($sectionTemplateType) {
			case 'adminNotificationPopup':
				if ($this->conf['order_details_table_adminNotificationPopup_tmpl_path']) {
					$template=$this->cObj->fileResource($this->conf['order_details_table_adminNotificationPopup_tmpl_path']);
				} else {
					$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath('multishop').'templates/order_details_table_adminNotificationPopup.tmpl');
				}
				break;
			case 'ajaxGetMethodCosts':
				if ($this->conf['order_details_table_ajaxGetMethodCosts_tmpl_path']) {
					$template=$this->cObj->fileResource($this->conf['order_details_table_ajaxGetMethodCosts_tmpl_path']);
				} else {
					$disable_product_status_col=true;
					$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath('multishop').'templates/order_details_table_site.tmpl');
				}
				break;
			default:
				if ($sectionTemplateType) {
					if ($this->conf['order_details_table_'.$sectionTemplateType.'_tmpl_path']) {
						$template=$this->cObj->fileResource($this->conf['order_details_table_'.$sectionTemplateType.'_tmpl_path']);
					} else {
						$disable_product_status_col=true;
						$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath('multishop').'templates/order_details_table_site.tmpl');
					}
				} else {
					if ($this->conf['order_details_table_site_tmpl_path']) {
						$template=$this->cObj->fileResource($this->conf['order_details_table_site_tmpl_path']);
					} else {
						$disable_product_status_col=true;
						$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath('multishop').'templates/order_details_table_site.tmpl');
					}
				}
				break;
		}
		// hook to rewrite the whole methods
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['getHtmlCartContents'])) {
			$params=array(
				'sectionTemplateType'=>&$sectionTemplateType,
				'template'=>&$template,
				'disable_product_status_col'=>&$disable_product_status_col,
				'content'=>&$content
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['getHtmlCartContents'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		} else {
			$itemsWrapper=array();
			$c=true;
			foreach ($this->cart['products'] as $shopping_cart_item=>$product) {
				$subPrices='';
				if (!$product['products_image']) {
					$image='<div class="no_image_50"></div>';
				} else {
					$image='<img src="'.$product['products_image'].'">';
				}
				$item=array();
				// ITEM CLASS
				$item['ITEM_CLASS']=(($c=!$c) ? 'odd' : 'even');
				// ITEM IMAGE
				if (!$product['products_image']) {
					$item['ITEM_IMAGE']='<div class="no_image_50"></div>';
				} else {
					if (!strstr(t3lib_div::strtolower($product['products_image']), 'http://') and !strstr(t3lib_div::strtolower($product['products_image']), 'https://')) {
						$item['products_image']=mslib_befe::getImagePath($product['products_image'], 'products', '50');
					}
					$item['ITEM_IMAGE']='<img src="'.$product['products_image'].'" title="'.htmlspecialchars($product['products_name']).'">';
				}
				// ITEM_NAME
				$item['ITEM_NAME']=$product['products_name'];
				if ($product['products_model']) {
					$item['ITEM_NAME'].=' ('.$product['products_model'].') ';
				}
				/*
				if (!empty($product['ean_code'])) {
					$item['ITEM_NAME'] .= '<br/>EAN: '.$product['ean_code'];
				}
				if (!empty($product['sku_code'])) {
					$item['ITEM_NAME'] .= '<br/>SKU: '.$product['sku_code'];
				}
				if (!empty($product['vendor_code'])) {
					$item['ITEM_NAME'] .= '<br/>Vendor: '.$product['vendor_code'];
				}*/
				if (is_array($product['attributes'])) {
					foreach ($product['attributes'] as $attributeKey=>$attributeItem) {
						$item['ITEM_NAME'].='<br />'.$attributeItem['products_options_name'].': '.$attributeItem['products_options_values_name'];
						if ($attributeItem['options_values_price']>0) {
							if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
								$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price_including_vat']);
							} else {
								$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price']);
							}
						}
						$subPrices.='<br />';
					}
				}
				if ($subPrices) {
					$subPrices='<div class="attribute_prices">'.$subPrices.'</div>';
				}
				// ITEM NAME EOF
				// ITEM_MODEL
				$item['ITEM_MODEL']=$product['products_model'];
				// ITEM_QUANTITY
				$item['SHOPPING_CART_KEY']=$shopping_cart_item;
				$item['ITEM_LINK']=$product['link'];
				$item['ITEM_ORDER_UNIT_CODE']=$product['order_unit_code'];
				$item['ITEM_ORDER_UNIT_NAME']=$product['order_unit_name'];
				$item['ITEM_MANUFACTURERS_NAME']=$product['manufacturers_name'];
				$item['ITEM_QUANTITY']=round($product['qty'], 14);
				// ITEM_SKU
				$item['ITEM_SKU']=$product['sku_code'];
				// ITEM_TOTAL
				if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
					$totalPrice=$product['total_price_including_vat'];
				} else {
					$totalPrice=$product['total_price'];
				}
				$item['ITEM_TOTAL']=mslib_fe::amount2Cents($totalPrice).$subprices;
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['getHtmlCartContentsItemPreProc'])) {
					$params=array(
						'item'=>&$item,
						'product'=>&$product,
						'cart'=>&$this->cart,
						'c'=>&$c,
						'subPrices'=>&$subPrices,
						'sectionTemplateType'=>&$sectionTemplateType
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_cart.php']['getHtmlCartContentsItemPreProc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
				}
				$itemsWrapper[]=$item;
			}
			// MERGE TO TEMPLATE
			// Extract the subparts from the template
			$subparts=array();
			$subparts['template']=$this->cObj->getSubpart($template, '###TEMPLATE###');
			$subparts['ITEMS_HEADER_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###ITEMS_HEADER_WRAPPER###');
			$subparts['ITEMS_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###ITEMS_WRAPPER###');
			$subparts['SUBTOTAL_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###SUBTOTAL_WRAPPER###');
			$subparts['SHIPPING_COSTS_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###SHIPPING_COSTS_WRAPPER###');
			$subparts['PAYMENT_COSTS_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###PAYMENT_COSTS_WRAPPER###');
			$subparts['GRAND_TOTAL_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###GRAND_TOTAL_WRAPPER###');
			$subparts['TAX_COSTS_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###TAX_COSTS_WRAPPER###');
			$subparts['DISCOUNT_WRAPPER']=$this->cObj->getSubpart($subparts['template'], '###DISCOUNT_WRAPPER###');
			// remove the status col
			if ($disable_product_status_col) {
				$subProductStatusPart=array();
				$subProductStatusPart['ITEMS_HEADER_PRODUCT_STATUS_WRAPPER']=$this->cObj->getSubpart($subparts['ITEMS_HEADER_WRAPPER'], '###ITEMS_HEADER_PRODUCT_STATUS_WRAPPER###');
				$subProductStatus=array();
				$subProductStatus['###ITEMS_HEADER_PRODUCT_STATUS_WRAPPER###']='';
				$subparts['ITEMS_HEADER_WRAPPER']=$this->cObj->substituteMarkerArrayCached($subparts['ITEMS_HEADER_WRAPPER'], array(), $subProductStatus);
				$subProductStatusPart=array();
				$subProductStatusPart['ITEMS_PRODUCT_STATUS_WRAPPER']=$this->cObj->getSubpart($subparts['ITEMS_WRAPPER'], '###ITEMS_PRODUCT_STATUS_WRAPPER###');
				$subProductStatus=array();
				$subProductStatus['###ITEMS_PRODUCT_STATUS_WRAPPER###']='';
				$subparts['ITEMS_WRAPPER']=$this->cObj->substituteMarkerArrayCached($subparts['ITEMS_WRAPPER'], array(), $subProductStatus);
			}
			// end of remove
			$subpartArray=array();
			//ITEMS_HEADER_WRAPPER
			$markerArray['HEADING_PRODUCTS_NAME']=ucfirst($this->pi_getLL('product'));
			$markerArray['HEADING_QUANTITY']=$this->pi_getLL('qty');
			$markerArray['HEADING_TOTAL']=$this->pi_getLL('total');
			$subpartArray['###ITEMS_HEADER_WRAPPER###']=$this->cObj->substituteMarkerArray($subparts['ITEMS_HEADER_WRAPPER'], $markerArray, '###|###');
			//ITEMS_HEADER_WRAPPER EOF
			//ITEMS_WRAPPER
			$keys=array();
			$keys[]='ITEM_CLASS';
			$keys[]='ITEM_IMAGE';
			$keys[]='ITEM_NAME';
			$keys[]='ITEM_MODEL';
			$keys[]='ITEM_QUANTITY';
			$keys[]='ITEM_LINK';
			$keys[]='SHOPPING_CART_KEY';
			$keys[]='ITEM_MANUFACTURERS_NAME';
			$keys[]='ITEM_ORDER_UNIT_CODE';
			$keys[]='ITEM_ORDER_UNIT_NAME';
			$keys[]='ITEM_SKU';
			$keys[]='ITEM_TOTAL';
			foreach ($itemsWrapper as $item) {
				$markerArray=array();
				foreach ($keys as $key) {
					$markerArray[$key]=$item[$key];
				}
				foreach ($item as $key=>$val) {
					// hooked plugins wants to add more types. lets find them and add them
					if (!in_array($key, $keys)) {
						$markerArray[$key]=$item[$key];
					}
				}
				$contentItem.=$this->cObj->substituteMarkerArray($subparts['ITEMS_WRAPPER'], $markerArray, '###|###');
			}
			$subpartArray['###ITEMS_WRAPPER###']=$contentItem;
			//ITEMS_WRAPPER EOF
			//SUBTOTAL_WRAPPER
			$key='SUBTOTAL_WRAPPER';
			$markerArray=array();
			$markerArray['PRODUCTS_TOTAL_PRICE_LABEL']=$this->pi_getLL('total_price');
			$markerArray['PRODUCTS_SUB_TOTAL_PRICE_LABEL']=$this->pi_getLL('subtotal');
			$markerArray['PRODUCTS_TOTAL_PRICE_INCLUDING_VAT_LABEL']=$this->pi_getLL('total_price');
			$markerArray['PRODUCTS_TOTAL_PRICE_INCLUDING_VAT']=mslib_fe::amount2Cents($this->cart['summarize']['sub_total_including_vat']);
			$markerArray['PRODUCTS_TOTAL_PRICE']=mslib_fe::amount2Cents($this->cart['summarize']['sub_total']);
			$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			//SUBTOTAL_WRAPPER EOF
			//SHIPPING_COSTS_WRAPPER
			$key='SHIPPING_COSTS_WRAPPER';
			if ($this->cart['user']['shipping_method_costs_including_vat']>0) {
				$markerArray=array();
				$shipping_price_value=$order['shipping_method_costs']+$order['orders_tax_data']['shipping_tax'];
				$markerArray['SHIPPING_COSTS_INCLUDING_VAT_LABEL']=$this->pi_getLL('shipping_costs').' ('.$this->cart['user']['shipping_method_label'].'):';
				$markerArray['SHIPPING_COSTS_INCLUDING_VAT']=mslib_fe::amount2Cents($this->cart['user']['shipping_method_costs_including_vat']);
				$markerArray['SHIPPING_COSTS']=mslib_fe::amount2Cents($this->cart['user']['shipping_method_costs']);
				$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			} else {
				$subpartArray['###'.$key.'###']='';
			}
			//SHIPPING_COSTS_WRAPPER EOF
			//PAYMENT_COSTS_WRAPPER
			$key='PAYMENT_COSTS_WRAPPER';
			if ($this->cart['user']['payment_method_costs_including_vat']>0) {
				$markerArray=array();
				$markerArray['PAYMENT_COSTS_INCLUDING_VAT_LABEL']=$this->pi_getLL('payment_costs').' ('.$this->cart['user']['payment_method_label'].'):';
				$markerArray['PAYMENT_COSTS_INCLUDING_VAT']=mslib_fe::amount2Cents($this->cart['user']['payment_method_costs_including_vat']);
				$markerArray['PAYMENT_COSTS']=mslib_fe::amount2Cents($this->cart['user']['payment_method_costs']);
				$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			} else {
				$subpartArray['###'.$key.'###']='';
			}
			//PAYMENT_COSTS_WRAPPER EOF
			//GRAND_TOTAL_WRAPPER
			$key='GRAND_TOTAL_WRAPPER_EXCLUDING_VAT';
			$markerArray['GRAND_TOTAL_COSTS_EXCLUDING_VAT_LABEL']=ucfirst($this->pi_getLL('total_excluding_vat'));
			$markerArray['GRAND_TOTAL_COSTS_EXCLUDING_VAT']=mslib_fe::amount2Cents(($this->cart['summarize']['grand_total']-$this->cart['total_orders_tax_including_discount']));
			$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			//GRAND_TOTAL_WRAPPER
			$key='GRAND_TOTAL_WRAPPER';
			$markerArray['GRAND_TOTAL_COSTS_LABEL']=ucfirst($this->pi_getLL('total'));
//		$markerArray['GRAND_TOTAL_COSTS'] = mslib_fe::amount2Cents($subtotal+$order['orders_tax_data']['total_orders_tax']+$order['payment_method_costs']+$order['shipping_method_costs']-$order['discount']);
			$markerArray['GRAND_TOTAL_COSTS']=mslib_fe::amount2Cents($this->cart['summarize']['grand_total']);
			$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			//GRAND_TOTAL_WRAPPER EOF
			/*
					//DISCOUNT_WRAPPER
					$key='DISCOUNT_WRAPPER';
					if ($this->cart['discount_amount'] > 0) {
						$DISCOUNT_WRAPPER = '
						<tr>
							<td class="msFrontGrandTotalDiscountLabel">'.$this->pi_getLL('discount').':</td>
							<td class="msFrontGrandTotalDiscountValue">'.mslib_fe::amount2Cents($this->cart['discount_amount']).'</td>
						</tr>';
						$subpartArray['###'.$key.'###'] = $DISCOUNT_WRAPPER;
					} else {
						$subpartArray['###'.$key.'###'] = '';
					}
			*/
			//DISCOUNT_WRAPPER EOF
// new
//error_log(print_r($this->cart,1));
// still not good. having partials of orders class
			//DISCOUNT_WRAPPER
			$key='DISCOUNT_WRAPPER';
			if ($this->cart['discount_amount']>0) {
				$this->cart['summarize']['total_orders_tax_including_discount']=($this->cart['summarize']['total_orders_tax_including_discount']-$this->cart['discount_amount']);
				$this->cart['summarize']['grand_total']=($this->cart['summarize']['grand_total']-$row['discount']);
				$markerArray=array();
				$markerArray['DISCOUNT_LABEL']=$this->pi_getLL('discount').':';
				$markerArray['DISCOUNT']=mslib_fe::amount2Cents($this->cart['discount_amount']);
				$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
				// trick to reduce TAX costs
				if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
					$this->cart['summarize']['grand_total_vat']=(1-($this->cart['discount_amount']/$this->cart['summarize']['sub_total_including_vat']))*$this->cart['summarize']['grand_total_vat'];
				}
			} else {
				$subpartArray['###'.$key.'###']='';
			}
//		error_log(print_r($this->cart['summarize'],1));
			//DISCOUNT_WRAPPER EOF
			//TAX_COSTS_WRAPPER
			$key='TAX_COSTS_WRAPPER';
			if ($this->cart['summarize']['grand_total_vat']) {
				$markerArray=array();
				$markerArray['TAX_RATE_LABEL']=$this->pi_getLL('vat').':';
				$markerArray['TAX_COSTS']=mslib_fe::amount2Cents($this->cart['summarize']['grand_total_vat']);
				$subpartArray['###'.$key.'###']=$this->cObj->substituteMarkerArray($subparts[$key], $markerArray, '###|###');
			} else {
				$subpartArray['###'.$key.'###']='';
			}
// new
			//TAX_COSTS_WRAPPER
			/*
					$key='TAX_COSTS_WRAPPER';
					if ($order['orders_tax_data']['total_orders_tax'] > 0) {
						$TAX_COSTS_WRAPPER = '
						<tr>
							<td class="msFrontGrandTotalVatLabel">'.$this->pi_getLL('included_vat','Included VAT').'</td>
							<td class="msFrontGrandTotalVatValue">'.mslib_fe::amount2Cents($this->cart['summarize']['grand_total_vat']).'</td>
						</tr>';
						$subpartArray['###'.$key.'###'] = $TAX_COSTS_WRAPPER;
					} else {
						$subpartArray['###'.$key.'###'] = '';
					}
			*/
			//TAX_COSTS_WRAPPER EOF
			// finally convert global markers and return output
			$content=$this->cObj->substituteMarkerArrayCached($subparts['template'], null, $subpartArray);
			/*
			// old
						$orderDetails.='<tr class="'.$tr_type.'">
						<td class="checkout_listing_image_td"><div class="checkout_listing_image">'.$image.'</div></td>
						<td class="checkout_listing_qty">'.$item['qty'].'</td>
						<td class="checkout_listing_name"><span class="bold">'.$item['products_name'].($item['products_model']?' <span class="checkout_listing_products_model">('.$item['products_model'].')</span>':'').'</span>';
						if (is_array($item['attributes'])) {
							foreach ($item['attributes'] as $attributeKey => $attributeItem) {
								$orderDetails.='<br />'.$attributeItem['products_options_name'].': '.$attributeItem['products_options_values_name'];
								if ($attributeItem['options_values_price'] >0) {
									if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
										$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price_including_vat']);
									} else {
										$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price']);
									}
								}
								$subPrices.='<br />';
							}
						}
						if ($subPrices) {
							$subPrices='<div class="attribute_prices">'.$subPrices.'</div>';
						}
						if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
							$totalPrice=$item['total_price_including_vat'];
						} else {
							$totalPrice=$item['total_price'];
						}
						$orderDetails.='</td>
							<td class="checkout_listing_vat">' . (($item['tax_rate']) ? ($item['tax_rate']*100).'%' : '&nbsp;') . '</td>
							<td class="checkout_listing_price">' . mslib_fe::amount2Cents($totalPrice) . '</td>
						</tr>';

					$orderDetails='';
					$orderDetails.='<table width="100%" border="0" align="center" class="border" id="shoppingcart_listing">';
					$orderDetails.='<tr><th class="checkout_products_image">&nbsp;</th><th class="checkout_products_qty">'.$this->pi_getLL('qty').'</th><th class="checkout_products_name">'.$this->pi_getLL('products_name').'</th><th class="checkout_products_vat">'.$this->pi_getLL('vat').'</th>';
					if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'])
					{
						$orderDetails.='<th nowrap class="checkout_products_price_vat">'.$this->pi_getLL('price_including_vat').'</th>';
					}
					else
					{
						$orderDetails.='<th nowrap class="checkout_products_price_vat">'.$this->pi_getLL('price_excluding_vat').'</th>';
					}
					$orderDetails.='</tr>';
					foreach ($this->cart['products'] as $shopping_cart_item => $item) {
						$subPrices='';
						if (!$item['products_image']) 	$image	='<div class="no_image_50"></div>';
						else
						{
							$image	='<img src="'.$item['products_image'].'">';
						}
						if (!$tr_type or $tr_type=='even') 	$tr_type='odd';
						else								$tr_type='even';
						$orderDetails.='<tr class="'.$tr_type.'">
						<td class="checkout_listing_image_td"><div class="checkout_listing_image">'.$image.'</div></td>
						<td class="checkout_listing_qty">'.$item['qty'].'</td>
						<td class="checkout_listing_name"><span class="bold">'.$item['products_name'].($item['products_model']?' <span class="checkout_listing_products_model">('.$item['products_model'].')</span>':'').'</span>';
						if (is_array($item['attributes'])) {
							foreach ($item['attributes'] as $attributeKey => $attributeItem) {
								$orderDetails.='<br />'.$attributeItem['products_options_name'].': '.$attributeItem['products_options_values_name'];
								if ($attributeItem['options_values_price'] >0) {
									if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
										$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price_including_vat']);
									} else {
										$subPrices.=mslib_fe::amount2Cents($attributeItem['options_values_price']);
									}
								}
								$subPrices.='<br />';
							}
						}
						if ($subPrices) {
							$subPrices='<div class="attribute_prices">'.$subPrices.'</div>';
						}
						if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
							$totalPrice=$item['total_price_including_vat'];
						} else {
							$totalPrice=$item['total_price'];
						}
						$orderDetails.='</td>
							<td class="checkout_listing_vat">' . (($item['tax_rate']) ? ($item['tax_rate']*100).'%' : '&nbsp;') . '</td>
							<td class="checkout_listing_price">' . mslib_fe::amount2Cents($totalPrice) . '</td>
						</tr>';
					}
					$orderDetails.='
					</table>
					<table cellpadding="0" cellspacing="0" border="0" align="right" id="msFrontCartContentsSubtotalWrapper">';
					if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
						$subtotal=$this->cart['summarize']['sub_total_including_vat'];
					} else {
						$subtotal=$this->cart['summarize']['sub_total'];
					}
					$orderDetails.='<tr class="msCartSummarySubtotal"><td class="label"><strong>'.$this->pi_getLL('subtotal').'</strong></td><td class="values" align="right">'.mslib_fe::amount2Cents($subtotal).'</td></tr>';
					if ($this->cart['discount_amount']) {
						$orderDetails.='<tr class="msCartSummaryDiscount"><td class="label">'.$this->pi_getLL('discount').'</td><td class="values" align="right">'.mslib_fe::amount2Cents($this->cart['discount_amount']).'</td></tr>';
					}
					if ($this->cart['user']['payment_method_costs_including_vat'] > 0) {
						$orderDetails.='<tr class="msCartSummaryPaymentCosts"><td class="label">'.$this->pi_getLL('payment_costs').' ('.$this->cart['user']['payment_method_label'].')</td><td class="values" align="right">'.mslib_fe::amount2Cents($this->cart['user']['payment_method_costs_including_vat']).'</td></tr>';
					}
					if ($this->cart['user']['shipping_method_costs_including_vat'] > 0) {
						$orderDetails.='<tr class="msCartSummaryShippingCosts"><td class="label">'.$this->pi_getLL('shipping_costs').' ('.$this->cart['user']['shipping_method_label'].')</td><td class="values" align="right">'.mslib_fe::amount2Cents($this->cart['user']['shipping_method_costs_including_vat']).'</td></tr>';
					}
					$orderDetails.='<tr><td colspan="2" class="values"><hr class="hr"></hr></td></tr>';
					$orderDetails.='<tr class="msCartSummaryTotal"><td class="label"><h4>'.$this->pi_getLL('total').'</h4></td><td align="right" class="values"><strong>'.mslib_fe::amount2Cents($this->cart['summarize']['grand_total']).'</strong></td></tr>';
					if ($this->cart['summarize']['grand_total_vat']) {
						$orderDetails.='<tr class="msCartSummaryIncludedVat"><td class="label">'.$this->pi_getLL('included_vat').'</td><td class="values" align="right">'.mslib_fe::amount2Cents($this->cart['summarize']['grand_total_vat']).'</td></tr>';
					}
					$orderDetails.='</table>';
					$orderDetails.='
					</table>';
			return $orderDetails;
			*/
		}
		return $content;
	}
	function removeFromCart($itemKey) {
		unset($this->cart['products'][$itemKey]);
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $this->cart);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
	function emptyCart() {
		unset($this->cart['products']);
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $this->cart);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
}
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_cart.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_cart.php"]);
}
?>