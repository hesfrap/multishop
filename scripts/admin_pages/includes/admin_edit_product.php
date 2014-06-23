<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$GLOBALS['TSFE']->additionalHeaderData[]='
<script type="text/javascript">
function limitText(limitField, limitNum) {
    if (limitField.value.length > limitNum) {
        limitField.value = limitField.value.substring(0, limitNum);
    }
}
jQuery(document).ready(function($) {
	var text_input = $(\'#products_name_0\');
	text_input.focus();
	text_input.select();
	$(\'.select2BigDropWider\').select2({
		dropdownCssClass: "bigdropWider", // apply css that makes the dropdown taller
		width:\'220px\'
	});
});
</script>';
$tabs=array();
$update_category_image='';
if ($this->post and $_FILES) {
	if ($this->post['products_name'][0]) {
		$this->post['products_name'][0]=trim($this->post['products_name'][0]);
	}
	$update_product_files=array();
	$update_product_images=array();
	if (!$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']) {
		$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']=5;
	}
	for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
		// hidden filename that is retrieved from the ajax upload
		$i=$x;
		if ($i==0) {
			$i='';
		}
		if ($this->post['ajax_products_image'.$i]) {
			$update_product_images['products_image'.$i]=$this->post['ajax_products_image'.$i];
		}
	}
	if (is_array($_FILES) and count($_FILES)) {
		foreach ($_FILES as $key=>$file) {
			if ($file['tmp_name']) {
				switch ($key) {
					case 'file_location':
						// digital download
						$total_files=count($file['tmp_name']);
						if ($total_files) {
							for ($i=0; $i<$total_files; $i++) {
								preg_match("/\.(.*)$/", $file['name'][$i], $tmp);
								$ext=$tmp[1];
								$file_name=md5(uniqid(rand()).uniqid(rand())).'.'.$ext;
								$target=$this->DOCUMENT_ROOT.'/uploads/tx_multishop/micro_downloads/'.$file_name;
								if (move_uploaded_file($file['tmp_name'][$i], $target)) {
									$update_product_files[$i]['file_label']=$file['name'][$i];
									$update_product_files[$i]['file_location']=$target;
								}
							}
						}
						// digital download eof					
						break;
					default:
						// product image
						for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
							// hidden filename that is retrieved from the ajax upload
							$i=$x;
							if ($i==0) {
								$i='';
							}
							$field='products_image'.$i;
							if ($key==$field) {
								// products image
								$size=getimagesize($file['tmp_name']);
								if ($size[0]>5 and $size[1]>5) {
									$imgtype=mslib_befe::exif_imagetype($file['tmp_name']);
									if ($imgtype) {
										// valid image
										$ext=image_type_to_extension($imgtype, false);
										if ($ext) {
											$i=0;
											$filename=mslib_fe::rewritenamein($this->post['products_name'][0]).'.'.$ext;
											$folder=mslib_befe::getImagePrefixFolder($filename);
											$array=explode(".", $filename);
											if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder)) {
												t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder);
											}
											$folder.='/';
											$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder.$filename;
											if (file_exists($target)) {
												do {
													$filename=mslib_fe::rewritenamein($this->post['products_name'][0]).($i>0 ? '-'.$i : '').'.'.$ext;
													$folder_name=mslib_befe::getImagePrefixFolder($filename);
													$array=explode(".", $filename);
													$folder=$folder_name;
													if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder)) {
														t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder);
													}
													$folder.='/';
													$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder.$filename;
													$i++;
												} while (file_exists($target));
											}
											if (move_uploaded_file($file['tmp_name'], $target)) {
												$target_origineel=$target;
												$update_product_images[$key]=mslib_befe::resizeProductImage($target_origineel, $filename, $this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey), 1);
											}
										}
									}
								}
								// products image eof								
							}
						}
						break;
				}
			}
		}
	}
}
if ($this->post) {
	// updating products table
	$updateArray=array();
	if (isset($this->post['manufacturers_products_id'])) {
		$updateArray['vendor_code']=$this->post['manufacturers_products_id'];
	}
	if (isset($this->post['products_multiplication'])) {
		$updateArray['products_multiplication']=$this->post['products_multiplication'];
	}
	if (strstr($this->post['product_capital_price'], ",")) {
		$this->post['product_capital_price']=str_replace(",", ".", $this->post['product_capital_price']);
	}
	if (strstr($this->post['products_price'], ",")) {
		$this->post['products_price']=str_replace(",", ".", $this->post['products_price']);
	}
	if ($this->post['specials_new_products_price'] and strstr($this->post['specials_new_products_price'], ",")) {
		$this->post['specials_new_products_price']=str_replace(",", ".", $this->post['specials_new_products_price']);
	}
	if ($this->post['products_date_available']) {
		$updateArray['products_date_available']=strtotime($this->post['products_date_available']);
	} else {
		$updateArray['products_date_available']=time();
	}
	if ($this->post['products_date_added']) {
		$updateArray['products_date_added']=strtotime($this->post['products_date_added']);
	} else {
		$updateArray['products_date_added']=time();
	}
	if ($this->post['ean_code']) {
		$this->post['ean_code']=str_pad($this->post['ean_code'], 13, '0', STR_PAD_LEFT);
		$updateArray['ean_code']=$this->post['ean_code'];
	}
	if (isset($this->post['starttime'])) {
		$updateArray['starttime']=strtotime($this->post['starttime']);
	}
	if (isset($this->post['endtime'])) {
		$updateArray['endtime']=strtotime($this->post['endtime']);
	}
	$updateArray['alert_quantity_threshold']=$this->post['alert_quantity_threshold'];
	$updateArray['custom_settings']=$this->post['custom_settings'];
	$updateArray['products_model']=$this->post['products_model'];
	$updateArray['products_quantity']=$this->post['products_quantity'];
	$updateArray['product_capital_price']=$this->post['product_capital_price'];
	$updateArray['products_condition']=$this->post['products_condition'];
	$updateArray['sku_code']=$this->post['sku_code'];
	$updateArray['products_price']=$this->post['products_price'];
	$updateArray['products_weight']=$this->post['products_weight'];
	$updateArray['products_status']=$this->post['products_status'];
	$updateArray['order_unit_id']=$this->post['order_unit_id'];
	$updateArray['tax_id']=$this->post['tax_id'];
	$updateArray['file_number_of_downloads']=$this->post['file_number_of_downloads'];
	if ($this->post['manufacturers_name']!='') {
		$manufacturer=mslib_fe::getManufacturer($this->post['manufacturers_name'], 'manufacturers_name');
		if ($manufacturer['manufacturers_id']) {
			$updateArray['manufacturers_id']=$manufacturer['manufacturers_id'];
		} else {
			$updateArray2=array();
			$updateArray2['manufacturers_name']=$this->post['manufacturers_name'];
			$updateArray2['date_added']=time();
			$updateArray2['status']=1;
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_manufacturers', $updateArray2);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			$manufacturers_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
			if ($manufacturers_id) {
				$updateArray2=array();
				$updateArray2['manufacturers_id']=$manufacturers_id;
				$updateArray2['language_id']=$this->sys_language_uid;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_manufacturers_info', $updateArray2);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				$updateArray['manufacturers_id']=$manufacturers_id;
			}
		}
	} else {
		$updateArray['manufacturers_id']=$this->post['manufacturers_id'];
	}
	if ($update_product_images) {
		foreach ($update_product_images as $key=>$value) {
			$updateArray[$key]=$value;
		}
	}
	if ($updateArray['products_image']) {
		$updateArray['contains_image']=1;
	}
	if ($this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
		$staffel_price_data=array();
		if ($this->post['sp'] and is_array($this->post['sp'])) {
			foreach ($this->post['sp'] as $row_idx=>$col_vals) {
				if (empty($col_vals[1])) {
					$col_vals[1]=$col_vals[1]+1;
				}
				$col_val=implode('-', $col_vals);
				$sprice=$this->post['staffel_price'][$row_idx];
				$staffel_price_data[$row_idx]=$col_val.':'.$sprice;
			}
		}
		if (count($staffel_price_data)>0) {
			$staffel_price_data=str_replace(",", ".", $staffel_price_data);
			$updateArray['staffel_price']=implode(';', $staffel_price_data);
		} else {
			$updateArray['staffel_price']='';
		}
	}
	if (isset($this->post['minimum_quantity'])) {
		$updateArray['minimum_quantity']=$this->post['minimum_quantity'];
	}
	if (isset($this->post['maximum_quantity'])) {
		$updateArray['maximum_quantity']=$this->post['maximum_quantity'];
	}
	if ($_REQUEST['action']=='edit_product' and $this->post['pid']) {
		if (isset($this->post['save_as_new'])) {
			if (!$updateArray['products_image']) {
				$product_original=mslib_fe::getProduct($this->post['pid']);
				foreach ($product_original as $arr_key=>$arr_val) {
					if (strpos($arr_key, 'products_image')!==false) {
						$updateArray[$arr_key]=$arr_val;
					}
				}
			}
			if ($updateArray['products_image']) {
				$updateArray['contains_image']=1;
			}
			$updateArray['page_uid']=$this->showCatalogFromPage;
			$updateArray['cruser_id']=$GLOBALS['TSFE']->fe_user->user['uid'];
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			$prodid=$GLOBALS['TYPO3_DB']->sql_insert_id();
			$updateArray=array();
			$updateArray['categories_id']=$_REQUEST['categories_id'];
			$updateArray['products_id']=$prodid;
			$updateArray['sort_order']=time();
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_to_categories', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		} else {
			$prodid=$this->post['pid'];
			$updateArray['products_last_modified']=time();
			// if product is originally coming from products importer we have to define that the merchant changed it
			$filter=array();
			$filter[]='products_id='.$prodid;
			if (mslib_befe::ifExists('1', 'tx_multishop_products', 'imported_product', $filter)) {
				// lock changed columns
				mslib_befe::updateImportedProductsLockedFields($prodid, 'tx_multishop_products', $updateArray);
			}
			$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', 'products_id=\''.$prodid.'\'', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if (!$updateArray['products_status']) {
				// call disable method cause that one also removes possible flat database record
				mslib_befe::disableProduct($row['products_id']);
			}
			if (is_numeric($this->post['categories_id'])) {
				if (is_numeric($this->post['old_categories_id']) and ($this->post['old_categories_id']<>$this->post['categories_id'])) {
					// if product is originally coming from products importer we have to define that the merchant changed it
					$filter=array();
					$filter[]='products_id='.$prodid;
					if (mslib_befe::ifExists('1', 'tx_multishop_products', 'imported_product', $filter)) {
						// lock changed columns
						mslib_befe::updateImportedProductsLockedFields($prodid, 'tx_multishop_products_to_categories', array('categories_id'=>$this->post['categories_id']));
					}
					$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_products_to_categories', 'products_id=\''.$prodid.'\' and categories_id=\''.$this->post['old_categories_id'].'\'');
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$updateArray=array();
					$updateArray['categories_id']=$this->post['categories_id'];
					$updateArray['products_id']=$prodid;
					$updateArray['sort_order']=time();
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_to_categories', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		}
	} else {
		$updateArray['page_uid']=$this->showCatalogFromPage;
		$updateArray['cruser_id']=$GLOBALS['TSFE']->fe_user->user['uid'];
		$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products', $updateArray);
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		$prodid=$GLOBALS['TYPO3_DB']->sql_insert_id();
		$updateArray=array();
		$updateArray['categories_id']=$_REQUEST['categories_id'];
		$updateArray['products_id']=$prodid;
		$updateArray['sort_order']=time();
		$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_to_categories', $updateArray);
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
	}
	if ($prodid) {
		if ($this->ms['MODULES']['PRODUCT_EDIT_METHOD_FILTER']) {
			// shipping/payment methods
			$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_products_method_mappings', 'products_id=\''.$prodid.'\'');
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if (is_array($this->post['payment_method']) and count($this->post['payment_method'])) {
				foreach ($this->post['payment_method'] as $value) {
					$updateArray=array();
					$updateArray['products_id']=$prodid;
					$updateArray['method_id']=$value;
					$updateArray['type']='payment';
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_method_mappings', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
			if (is_array($this->post['shipping_method']) and count($this->post['shipping_method'])) {
				foreach ($this->post['shipping_method'] as $value) {
					$updateArray=array();
					$updateArray['products_id']=$prodid;
					$updateArray['method_id']=$value;
					$updateArray['type']='shipping';
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_method_mappings', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
			// shipping/payment methods eof
		}
		foreach ($this->post['products_name'] as $key=>$value) {
			if (is_numeric($key)) {
				$str="select 1 from tx_multishop_products_description where products_id='".$prodid."' and language_id='".$key."'";
				$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
				$updateArray=array();
				$updateArray['products_name']=$this->post['products_name'][$key];
				$updateArray['delivery_time']=$this->post['delivery_time'][$key];
				$updateArray['products_shortdescription']=$this->post['products_shortdescription'][$key];
				$updateArray['products_description']=$this->post['products_description'][$key];
				$updateArray['products_meta_keywords']=$this->post['products_meta_keywords'][$key];
				$updateArray['products_meta_title']=$this->post['products_meta_title'][$key];
				$updateArray['products_meta_keywords']=$this->post['products_meta_keywords'][$key];
				$updateArray['products_meta_description']=$this->post['products_meta_description'][$key];
				$updateArray['products_negative_keywords']=$this->post['products_negative_keywords'][$key];
				$updateArray['products_url']=$this->post['products_url'][$key];
				if ($update_product_files[$key]['file_label']) {
					$updateArray['file_label']=$update_product_files[$key]['file_label'];
				}
				if ($update_product_files[$key]['file_location']) {
					$updateArray['file_location']=$update_product_files[$key]['file_location'];
				}
				$updateArray['file_remote_location']=$this->post['file_remote_location'][$key];
				// EXTRA TAB CONTENT
				if ($this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']) {
					for ($i=1; $i<=$this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']; $i++) {
						$updateArray['products_description_tab_title_'.$i]=$this->post['products_description_tab_title_'.$i][$key];
						$updateArray['products_description_tab_content_'.$i]=$this->post['products_description_tab_content_'.$i][$key];
					}
				}
				// EXTRA TAB CONTENT EOF				
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
					// if product is originally coming from products importer we have to define that the merchant changed it
					$filter=array();
					$filter[]='products_id='.$prodid;
					if (mslib_befe::ifExists('1', 'tx_multishop_products', 'imported_product', $filter)) {
						// lock changed columns				
						mslib_befe::updateImportedProductsLockedFields($prodid, 'tx_multishop_products_description', $updateArray);
					}
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_description', 'products_id=\''.$prodid.'\' and language_id=\''.$key.'\'', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				} else {
					if (isset($this->post['save_as_new'])) {
						if (strpos($updateArray['products_name'], '(copy')===false) {
							$updateArray['products_name'].=' (copy '.$prodid.')';
						} else {
							if (strpos($updateArray['products_name'], '(copy '.$prodid.')')!==false) {
								$updateArray['products_name']=str_replace('(copy '.$prodid.')', ' (copy '.$prodid.')', $updateArray['products_name']);
							} else {
								$updateArray['products_name']=str_replace('(copy)', ' (copy '.$prodid.')', $updateArray['products_name']);
							}
						}
					}
					$updateArray['products_id']=$prodid;
					$updateArray['language_id']=$key;
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_description', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		}
		// specials price
		if ($this->post['specials_new_products_price']) {
			$specials_start_date=0;
			$specials_expired_date=0;
			if ($this->post['specials_price_start']>0) {
				$specials_start_date=strtotime($this->post['specials_price_start']);
			}
			if ($this->post['specials_price_expired']>0) {
				$specials_expired_date=strtotime($this->post['specials_price_expired']);
			}
			$str="SELECT * from tx_multishop_specials where products_id='".$prodid."'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
				$specials_id=$row['specials_id'];
				$updateArray=array();
				$updateArray['specials_new_products_price']=$this->post['specials_new_products_price'];
				$updateArray['start_date']=$specials_start_date;
				$updateArray['expires_date']=$specials_expired_date;
				/* if ($this->post['tax_id'])
				{
					// we have to substract the vat so the price is excl. vat
					$tax_rate=mslib_fe::getTaxRate($this->post['tax_id']);
					$updateArray['specials_new_products_price']=round($updateArray['specials_new_products_price']/(1+$tax_rate),4);		
				}	 */
				$updateArray['status']=1;
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_specials', 'products_id=\''.$prodid.'\'', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			} else {
				$updateArray=array();
				$updateArray['products_id']=$prodid;
				$updateArray['specials_new_products_price']=$this->post['specials_new_products_price'];
				$updateArray['start_date']=$specials_start_date;
				$updateArray['expires_date']=$specials_expired_date;
				/* if ($this->post['tax_id'])
				{
					// we have to substract the vat so the price is excl. vat
					$tax_rate=mslib_fe::getTaxRate($this->post['tax_id']);
					$updateArray['specials_new_products_price']=round($updateArray['specials_new_products_price']/(1+$tax_rate),4);								
				} */
				$updateArray['status']=1;
				$updateArray['page_uid']=$this->showCatalogFromPage;
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_specials', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				$specials_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
			}
			if ($specials_id) {
				$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_specials_sections', 'specials_id=\''.$specials_id.'\'');
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
			if ($specials_id and is_array($this->post['specials_sections'])) {
				foreach ($this->post['specials_sections'] as $section) {
					$updateArray=array();
					$updateArray['status']=1;
					$updateArray['specials_id']=$specials_id;
					$updateArray['name']=$section;
					$updateArray['date']=time();
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_specials_sections', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		} elseif ($_REQUEST['action']=='edit_product') {
			$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_specials', 'products_id=\''.$prodid.'\'');
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		}
		if ($this->post['tx_multishop_pi1']['options']) {
			$option_sort_order=array();
			$values_sort_order=array();
			$counter=1;
			foreach ($this->post['tx_multishop_pi1']['options'] as $opt_sort=>$opt_id) {
				// settling down the options
				$pa_option=$opt_id;
				if ($this->post['tx_multishop_pi1']['is_manual_options'][$opt_sort]>0) {
					$sql_chk=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_id', // SELECT ...
						'tx_multishop_products_options', // FROM ...
						"products_options_name = '".addslashes($pa_option)."' and language_id = '".$this->sys_language_uid."'", // WHERE...
						'', // GROUP BY...
						'sort_order desc', // ORDER BY...
						'' // LIMIT ...
					);
					$qry_chk=$GLOBALS['TYPO3_DB']->sql_query($sql_chk);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_chk)>0) {
						$rs_chk=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_chk);
						$pa_option=$rs_chk['products_options_id'];
					} else {
						$sql_chk=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_id', // SELECT ...
							'tx_multishop_products_options', // FROM ...
							'', // WHERE...
							'', // GROUP BY...
							'products_options_id desc', // ORDER BY...
							'1' // LIMIT ...
						);
						$qry_chk=$GLOBALS['TYPO3_DB']->sql_query($sql_chk);
						$rs_chk=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_chk);
						$max_optid=$rs_chk['products_options_id']+1;
						// use microtime as the default sorting
						$tmp_mtime=explode(" ", microtime());
						$mtime=array_sum($tmp_mtime);
						// prep for insertion
						$insertArray=array();
						$insertArray['products_options_id']=$max_optid;
						$insertArray['language_id']='0';
						$insertArray['products_options_name']=$pa_option;
						$insertArray['listtype']='pulldownmenu';
						$insertArray['attributes_values']='0';
						$insertArray['sort_order']=$mtime;
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options', $insertArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$pa_option=$max_optid;
					}
				}
				// settling down the attributes values
				$pa_value=$this->post['tx_multishop_pi1']['attributes'][$opt_sort];
				if (!empty($pa_value)) {
					if ($this->post['tx_multishop_pi1']['is_manual_attributes'][$opt_sort]>0) {
						if (!empty($pa_value)) {
							$sql_chk=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_values_id', // SELECT ...
								'tx_multishop_products_options_values', // FROM ...
								"products_options_values_name = '".addslashes($pa_value)."' and language_id = '".$this->sys_language_uid."'", // WHERE...
								'', // GROUP BY...
								'', // ORDER BY...
								'' // LIMIT ...
							);
							$qry_chk=$GLOBALS['TYPO3_DB']->sql_query($sql_chk);
							if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_chk)>0) {
								$rs_chk=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_chk);
								$pa_value=$rs_chk['products_options_values_id'];
							} else {
								$insertArray=array();
								$insertArray['products_options_values_id']='';
								$insertArray['language_id']=$this->sys_language_uid;
								$insertArray['products_options_values_name']=$pa_value;
								$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values', $insertArray);
								$GLOBALS['TYPO3_DB']->sql_query($query);
								$pa_value=$GLOBALS['TYPO3_DB']->sql_insert_id();
							}
							$sql_chk=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_values_to_products_options_id', // SELECT ...
								'tx_multishop_products_options_values_to_products_options', // FROM ...
								"products_options_id = '".$pa_option."' and  products_options_values_id = '".$pa_value."'", // WHERE...
								'', // GROUP BY...
								'', // ORDER BY...
								'' // LIMIT ...
							);
							$qry_chk=$GLOBALS['TYPO3_DB']->sql_query($sql_chk);
							if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry_chk)) {
								// use microtime as the default sorting
								$tmp_mtime=explode(" ", microtime());
								$mtime=array_sum($tmp_mtime);
								// insert new relations
								$insertArray=array();
								$insertArray['products_options_values_to_products_options_id']='';
								$insertArray['products_options_id']=$pa_option;
								$insertArray['products_options_values_id']=$pa_value;
								$insertArray['sort_order']=$mtime;
								$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options', $insertArray);
								$GLOBALS['TYPO3_DB']->sql_query($query);
							}
						}
					} else {
						$sql_chk=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_values_to_products_options_id', // SELECT ...
							'tx_multishop_products_options_values_to_products_options', // FROM ...
							"products_options_id = '".$pa_option."' and  products_options_values_id = '".$pa_value."'", // WHERE...
							'', // GROUP BY...
							'', // ORDER BY...
							'' // LIMIT ...
						);
						$qry_chk=$GLOBALS['TYPO3_DB']->sql_query($sql_chk);
						if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry_chk)) {
							// use microtime as the default sorting
							$tmp_mtime=explode(" ", microtime());
							$mtime=array_sum($tmp_mtime);
							// insert new relations
							$insertArray=array();
							$insertArray['products_options_values_to_products_options_id']='';
							$insertArray['products_options_id']=$pa_option;
							$insertArray['products_options_values_id']=$pa_value;
							$insertArray['sort_order']=$mtime;
							$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options', $insertArray);
							$GLOBALS['TYPO3_DB']->sql_query($query);
						}
					}
				}
				$pa_prefix=$this->post['tx_multishop_pi1']['prefix'][$opt_sort];
				$pa_price=$this->post['tx_multishop_pi1']['price'][$opt_sort];
				if (empty($pa_prefix) && $pa_price>0) {
					if (!empty($pa_price)) {
						if ($this->post['specials_new_products_price']) {
							if ($this->post['specials_new_products_price']>$pa_price) {
								$pa_prefix='-';
								$pa_price=$this->post['specials_new_products_price']-$pa_price;
							} else {
								$pa_prefix='+';
								$pa_price=$pa_price-$this->post['specials_new_products_price'];
							}
						} else {
							if ($this->post['products_price']>$pa_price) {
								$pa_prefix='-';
								$pa_price=$this->post['products_price']-$pa_price;
							} else {
								$pa_prefix='+';
								$pa_price=$pa_price-$this->post['products_price'];
							}
						}
					}
				}
				$pa_id=$this->post['tx_multishop_pi1']['pa_id'][$opt_sort];
				// sort the option
				if (!isset($option_sort_order[$pa_option])) {
					$option_sort_order[$pa_option]=$counter;
					$counter++;
				}
				// sort the values
				if (!isset($values_sort_order[$opt_id][$pa_value])) {
					$values_sort_order[$opt_id][$pa_value]=count($values_sort_order[$opt_id])+1;
				}
				if (!empty($prodid) && $prodid>0 && !empty($pa_option) && $pa_option>0 && !empty($pa_value) && $pa_value>0) {
					if ($pa_id>0) {
						$attributesArray=array();
						$attributesArray['products_id']=$prodid;
						$attributesArray['options_id']=$pa_option;
						$attributesArray['options_values_id']=$pa_value;
						$attributesArray['price_prefix']=$pa_prefix;
						$attributesArray['options_values_price']=$pa_price;
						$attributesArray['sort_order_option_name']=$option_sort_order[$opt_id];
						$attributesArray['sort_order_option_value']=$values_sort_order[$opt_id][$this->post['tx_multishop_pi1']['attributes'][$opt_sort]];
						$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', 'products_attributes_id=\''.$pa_id.'\'', $attributesArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					} else {
						$attributesArray=array();
						$attributesArray['products_id']=$prodid;
						$attributesArray['options_id']=$pa_option;
						$attributesArray['options_values_id']=$pa_value;
						$attributesArray['price_prefix']=$pa_prefix;
						$attributesArray['options_values_price']=$pa_price;
						$attributesArray['sort_order_option_name']=$option_sort_order[$pa_option];
						$attributesArray['sort_order_option_value']=$values_sort_order[$pa_option][$this->post['tx_multishop_pi1']['attributes'][$opt_sort]];
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_attributes', $attributesArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					}
				}
			}
		}
		if (is_array($this->post['predefined_option']) and count($this->post['predefined_option'])) {
			$current_option_id='';
			foreach ($this->post['predefined_option'] as $option_id=>$values) {
				if (is_numeric($option_id)) {
					$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_products_attributes', 'options_id=\''.$option_id.'\' and products_id=\''.$prodid.'\'');
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					foreach ($values as $value_id) {
						if ($value_id) {
							if (is_numeric($value_id)) {
								$attributesArray=array();
								$attributesArray['products_id']=$prodid;
								$attributesArray['options_id']=$option_id;
								$attributesArray['options_values_id']=$value_id;
								$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_attributes', $attributesArray);
								$res=$GLOBALS['TYPO3_DB']->sql_query($query);
							}
						}
					}
					$current_option_id=$option_id;
				}
			}
		}
		if (count($this->post['exclude_feed'])) {
			$sql_check="delete from tx_multishop_feeds_excludelist where exclude_id='".addslashes($prodid)."' and exclude_type='products'";
			$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
			foreach ($this->post['exclude_feed'] as $feed_id) {
				$updateArray=array();
				$updateArray['feed_id']=$feed_id;
				$updateArray['exclude_id']=$prodid;
				$updateArray['exclude_type']='products';
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_feeds_excludelist', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		} else {
			$sql_check="delete from tx_multishop_feeds_excludelist where exclude_id='".addslashes($prodid)."' and exclude_type='products'";
			$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
		}
		if (count($this->post['exclude_stock_feed'])) {
			$sql_check="delete from tx_multishop_feeds_stock_excludelist where exclude_id='".addslashes($prodid)."' and exclude_type='products'";
			$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
			foreach ($this->post['exclude_stock_feed'] as $feed_id) {
				$updateArray=array();
				$updateArray['feed_id']=$feed_id;
				$updateArray['exclude_id']=$prodid;
				$updateArray['exclude_type']='products';
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_feeds_stock_excludelist', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		} else {
			$sql_check="delete from tx_multishop_feeds_stock_excludelist where exclude_id='".addslashes($prodid)."' and exclude_type='products'";
			$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
		}
		if ($_REQUEST['action']=='edit_product') {
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['updateProductPostHook'])) {
				$params=array(
					'products_id'=>$prodid
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['updateProductPostHook'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
			// custom hook that can be controlled by third-party plugin eof			
		} else {
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['insertProductPostHook'])) {
				$params=array(
					'products_id'=>$prodid
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['insertProductPostHook'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
			// custom hook that can be controlled by third-party plugin eof				
		}
		// OLD OBSOLUTE HOOK
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_product.php']['saveProductPostHook'])) {
			$params=array(
				'prodid'=>$prodid
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_product.php']['saveProductPostHook'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		// lets notify plugin that we have update action in product
		tx_mslib_catalog::productsUpdateNotifierForPlugin($this->post, $prodid);
		// custom hook that can be controlled by third-party plugin eof
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			// if the flat database module is enabled we have to sync the changes to the flat table
			mslib_befe::convertProductToFlat($prodid);
		}
		if ($this->post['tx_multishop_pi1']['referrer']) {
			header("Location: ".$this->post['tx_multishop_pi1']['referrer']);
			exit();
		} else {
			header("Location: ".$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit', 1));
			exit();
		}
	}
	//window.opener.location.reload();
	//parent.window.hs.close();
} else {
	if ($_REQUEST['action']=='edit_product' && is_numeric($this->get['pid'])) {
		$str="SELECT p.*, c.categories_id, pd.file_location, pd.file_label, p.custom_settings from tx_multishop_products p, tx_multishop_products_description pd, tx_multishop_products_to_categories p2c, tx_multishop_categories c, tx_multishop_categories_description cd where p2c.products_id='".$this->get['pid']."' ";
		if (is_numeric($this->get['cid'])) {
			$str.=" and p2c.categories_id=".$this->get['cid'];
		}
		$str.=" and p.products_id=pd.products_id and p.products_id=p2c.products_id and p2c.categories_id=c.categories_id and p2c.categories_id=cd.categories_id";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		$product=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
		if ($this->get['delete_image'] and is_numeric($this->get['pid'])) {
			if ($product[$this->get['delete_image']]) {
				mslib_befe::deleteProductImage($product[$this->get['delete_image']]);
				$updateArray=array();
				$updateArray[$this->get['delete_image']]='';
				$product[$this->get['delete_image']]='';
				if ($this->get['delete_image']=='products_image') {
					$updateArray['contains_image']=0;
				}
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', 'products_id=\''.$this->get['pid'].'\'', $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		}
		$str="SELECT * from tx_multishop_products p, tx_multishop_products_description pd where p.products_id='".$this->get['pid']."' and p.products_id=pd.products_id";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			$lngproduct[$row['language_id']]=$row;
		}
		if ($this->get['delete_micro_download'] and is_numeric($this->get['pid']) and is_numeric($this->get['language_id'])) {
			// delete the micro download file
			if ($lngproduct[$this->get['language_id']]['file_location']) {
				@unlink($lngproduct[$this->get['language_id']]['file_location']);
				$lngproduct[$this->get['language_id']]['file_label']='';
				$lngproduct[$this->get['language_id']]['file_location']='';
				$updateArray=array();
				$updateArray['file_label']='';
				$updateArray['file_location']='';
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_description', 'products_id=\''.$this->get['pid'].'\' and language_id='.$this->get['language_id'], $updateArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		}
	}
	if ($product['products_id'] or $_REQUEST['action']=='add_product') {
		// now parse all the objects in the tmpl file
		if ($this->conf['admin_edit_product_tmpl_path']) {
			$template=$this->cObj->fileResource($this->conf['admin_edit_product_tmpl_path']);
		} else {
			$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath($this->extKey).'templates/admin_edit_product.tmpl');
		}
		// Extract the subparts from the template
		$subparts=array();
		$subparts['template']=$this->cObj->getSubpart($template, '###TEMPLATE###');
		$subparts['js_header']=$this->cObj->getSubpart($subparts['template'], '###JS_HEADER###');
		$subparts['details_content']=$this->cObj->getSubpart($subparts['template'], '###DETAILS_CONTENT###');
		if ($_REQUEST['action']=='add_product') {
			$heading_page='<h1>'.$this->pi_getLL('admin_add_new_product').'</h1>';
		} else {
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
			$details_link=$this->FULL_HTTP_URL.mslib_fe::typolink($this->conf['products_detail_page_pid'], $where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
			$heading_page='<h1>'.$this->pi_getLL('admin_edit_product').' (ID: '.$product['products_id'].')</h1><span class="viewfront"><a href="'.$details_link.'" target="_blank">'.$this->pi_getLL('admin_edit_view_front_product', 'View in front').'</a></span>';
		}
		/*
		 * js header
		 */
		$js_header='';
		$markerArray=array();
		$markerArray['AJAX_PID']=(isset($this->get['pid']) ? $this->get['pid'] : 0);
		$markerArray['AJAX_URL_COPY_PRODUCT']=mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=copy_duplicate_product');
		$markerArray['AJAX_URL_GET_TAX_RULESET']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_tax_ruleset');
		$markerArray['AJAX_URL_GET_SPECIAL_SECTIONS']=mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=getSpecialSections');
		if ($product['specials_new_products_price']) {
			$markerArray['AJAX_REQUEST_SPECIAL_PRICE']='getSpecialsSections('.$_REQUEST['pid'].');';
		} else {
			$markerArray['AJAX_REQUEST_SPECIAL_PRICE']='';
		}
		$markerArray['DATE_FORMAT']=$this->pi_getLL('locale_date_format_js', 'yy/mm/dd');
		$markerArray['YEAR_RANGE']=date("Y").':'.(date("Y")+2);
		$markerArray['AJAX_URL_PRODUCT_RELATIVE']=mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_relatives&relation_types=cross-sell');
		$js_extra=array();
		// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['adminEditProductJsExtra'])) {
			$params=array(
				'markerArray'=>&$markerArray,
				'product'=>&$product,
				'js_extra'=>&$js_extra
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['adminEditProductJsExtra'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		// custom page hook that can be controlled by third-party plugin eof
		if (!count($js_extra['functions'])) {
			$markerArray['JS_FUNCTIONS_EXTRA']='';
		} else {
			$markerArray['JS_FUNCTIONS_EXTRA']=implode("\n", $js_extra['functions']);
		}
		if (!count($js_extra['triggers'])) {
			$markerArray['JS_TRIGGERS_EXTRA']='';
		} else {
			$markerArray['JS_TRIGGERS_EXTRA']=implode("\n", $js_extra['triggers']);
		}
		$js_header.=$this->cObj->substituteMarkerArray($subparts['js_header'], $markerArray, '###|###');
		/*
		 * details tab
		*/
		$details_content='';
		foreach ($this->languages as $key=>$language) {
			$details_tab_content='';
			if ($this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']) {
				for ($i=1; $i<=$this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']; $i++) {
					$details_tab_content.='
					<div class="account-field" id="msEditProductInputTabTitle_'.$i.'">
						<label for="products_description_tab_title_'.$i.'">'.$this->pi_getLL('admin_title_tab_'.$i, 'TITLE TAB '.$i).'</label>
						<input type="text" class="text" name="products_description_tab_title_'.$i.'['.$language['uid'].']" id="products_description_tab_title_'.$i.'['.$language['uid'].']" value="'.htmlspecialchars($lngproduct[$language['uid']]['products_description_tab_title_'.$i.'']).'">
					</div>
					<div class="account-field" id="msEditProductInputTabContent_'.$i.'">
						<label for="products_description_tab_content_'.$i.'">'.$this->pi_getLL('admin_full_description_tab_'.$i, 'DESCRIPTION TAB '.$i).'</label>
						<textarea name="products_description_tab_content_'.$i.'['.$language['uid'].']" id="products_description_tab_content_'.$i.'['.$language['uid'].']" class="mceEditor" rows="4">'.htmlspecialchars($lngproduct[$language['uid']]['products_description_tab_content_'.$i]).'</textarea>
					</div>';
				}
			}
			$flag_path='';
			if ($language['flag']) {
				$flag_path='sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif';
			}
			$language_label='';
			if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.$flag_path)) {
				$language_label.='<img src="'.$this->FULL_HTTP_URL_TYPO3.$flag_path.'"> ';
			}
			$language_label.=''.$language['title'];
			$markerArray=array();
			$markerArray['LANGUAGE_UID']=$language['uid'];
			$markerArray['LABEL_LANGUAGE']=t3lib_div::strtoupper($this->pi_getLL('language'));
			$markerArray['LANGUAGE_LABEL']=$language_label;
			$markerArray['LABEL_PRODUCT_NAME']=$this->pi_getLL('admin_name');
			$markerArray['VALUE_PRODUCT_NAME']=htmlspecialchars($lngproduct[$language['uid']]['products_name']);
			$markerArray['LABEL_SHORT_DESCRIPTION']=$this->pi_getLL('admin_short_description');
			$markerArray['TEXTAREA_SHORT_DESCRIPTION_PARAMS']='';
			if (!$this->ms['MODULES']['PRODUCTS_SHORT_DESCRIPTION_CONTAINS_HTML_MARKUP']) {
				$markerArray['TEXTAREA_SHORT_DESCRIPTION_PARAMS']='onKeyDown="limitText(this,255);" onKeyUp="limitText(this,255);"';
			}
			$markerArray['TEXTAREA_SHORT_DESCRIPTION_CLASS']=($this->ms['MODULES']['PRODUCTS_SHORT_DESCRIPTION_CONTAINS_HTML_MARKUP'] ? ' class="mceEditor" ' : ' class="text expand20-100" ');
			$markerArray['VALUE_SHORT_DESCRIPTION']=htmlspecialchars($lngproduct[$language['uid']]['products_shortdescription']);
			$markerArray['LABEL_PRODUCT_DESCRIPTION']=$this->pi_getLL('admin_full_description');
			$markerArray['VALUE_PRODUCT_DESCRIPTION']=htmlspecialchars($lngproduct[$language['uid']]['products_description']);
			$markerArray['LABEL_PRODUCT_URL']=$this->pi_getLL('admin_external_url');
			$markerArray['VALUE_PRODUCT_URL']=htmlspecialchars($lngproduct[$language['uid']]['products_url']);
			$markerArray['LABEL_DELIVERY_TIME']=$this->pi_getLL('admin_delivery_time');
			$markerArray['VALUE_DELIVERY_TIME']=htmlspecialchars($lngproduct[$language['uid']]['delivery_time']);
			$markerArray['LABEL_NEGATIVE_KEYWORDS']='Negative keywords';
			$markerArray['VALUE_NEGATIVE_KEYWORDS']=htmlspecialchars($lngproduct[$language['uid']]['products_negative_keywords']);
			$markerArray['DETAILS_TAB_CONTENT']=$details_tab_content;
			$details_content.=$this->cObj->substituteMarkerArray($subparts['details_content'], $markerArray, '###|###');
		}
		/*
		 * options tab
		 */
		$input_vat_rate='<select name="tax_id" id="tax_id"><option value="0">No TAX</option>';
		$str="SELECT * FROM `tx_multishop_tax_rule_groups`";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		$product_tax_rate=0;
		$data=mslib_fe::getTaxRuleSet($product['tax_id'], $product['products_price']);
		$product_tax_rate=$data['total_tax_rate'];
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			$input_vat_rate.='<option value="'.$row['rules_group_id'].'" '.(($row['rules_group_id']==$product['tax_id']) ? 'selected' : '').'>'.htmlspecialchars($row['name']).'</option>';
		}
		$input_vat_rate.='</select>';
		if ($_REQUEST['action']=='edit_product') {
			$str="SELECT * from tx_multishop_specials where products_id='".$_REQUEST['pid']."' and status=1";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			$specials_price=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
			if ($specials_price['specials_new_products_price']) {
				$product['specials_new_products_price']=$specials_price['specials_new_products_price'];
				$product['specials_start_date']=$specials_price['start_date'];
				$product['specials_expired_date']=$specials_price['expires_date'];
			}
		}
		$price_tax=mslib_fe::taxDecimalCrop(($product['products_price']*$product_tax_rate)/100);
		$special_price_tax=mslib_fe::taxDecimalCrop(($product['specials_new_products_price']*$product_tax_rate)/100);
		$capital_price_tax=mslib_fe::taxDecimalCrop(($product['product_capital_price']*$product_tax_rate)/100);
		$price_excl_vat_display=mslib_fe::taxDecimalCrop($product['products_price'], 2, false);
		$price_incl_vat_display=mslib_fe::taxDecimalCrop($product['products_price']+$price_tax, 2, false);
		$special_price_excl_vat_display=mslib_fe::taxDecimalCrop($product['specials_new_products_price'], 2, false);
		$special_price_incl_vat_display=mslib_fe::taxDecimalCrop($product['specials_new_products_price']+$special_price_tax, 2, false);
		$capital_price_excl_vat_display=mslib_fe::taxDecimalCrop($product['product_capital_price'], 2, false);
		$capital_price_incl_vat_display=mslib_fe::taxDecimalCrop($product['product_capital_price']+$capital_price_tax, 2, false);
		$staffel_price_block='';
		if ($this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
			$staffel_price_block.='
				<div class="account-field">
				<script>
				jQuery(document).ready(function($) {
					jQuery("#add_staffel_input").click(function(event) {
						var counter_data = parseInt(jQuery(\'#sp_row_counter\').val());
						var counter_col = parseInt(jQuery(\'#sp_row_counter\').val());
		
						//if (document.getElementById(\'sp_\' + counter_col + \'_qty_2\').value == \'\') {
						//	var next_qty_col_1 = 0;
						//} else {
							//var counter_data = parseInt(document.getElementById(\'sp_row_counter\').value);
							//alert(counter_data);
							if (counter_data == 0) {
								counter_data = counter_data + 1;
								var elem = \'<tr id="sp_\' + counter_data + \'">\';
								elem += \'<td>\';
								elem += \'<input type="text" class="price small_input" name="sp[\' + counter_data + \'][]" id="sp_\' + counter_data + \'_qty_1" readonly="readonly" value="1" />\';
								elem += \'</td>\';
								elem += \'<td>\';
								elem += \'<input type="text" class="price small_input" name="sp[\' + counter_data + \'][]" id="sp_\' + counter_data + \'_qty_2" value="" />\';
								elem += \'</td>\';
								elem += \'<td>\';
								elem += \'<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" id="display_name" name="display_name" class="msStaffelPriceExcludingVat" value=""><label for="display_name">Excl. VAT</label></div>\';
								elem += \'<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msStaffelPriceIncludingVat" value=""><label for="display_name">Incl. VAT</label></div>\';
								elem += \'<div class="msAttributesField hidden"><input type="hidden" name="staffel_price[\' + counter_data + \']" class="price small_input" id="staffel_price" value=""></div>\';
								elem += \'<td>\';
								elem += \'<input type="button" value="x" onclick="remStaffelInput(\' + counter_data + \')"  class="msadmin_button" />\';
								elem += \'</td>\';
								elem += \'</tr>\';
								jQuery(\'#sp_end_row\').before(elem);
							} else {
								counter_data = counter_data + 1;
								//alert(\'sp_\' + counter_col + \'_qty_2\');
								var counter_id = \'#sp_\' + counter_col + \'_qty_2\';
								if (jQuery(counter_id).val() == \'\') {
									var next_qty_col_1 = 0;
								} else {
									var next_qty_col_1 = parseInt(jQuery(counter_id).val()) + 1;
								}
								var elem = \'<tr id="sp_\' + counter_data + \'">\';
								elem += \'<td>\';
								elem += \'<input type="text" class="price small_input" name="sp[\' + counter_data + \'][]" id="sp_\' + counter_data + \'_qty_1" value="\' + next_qty_col_1 + \'" />\';
								elem += \'</td>\';
								elem += \'<td>\';
								elem += \'<input type="text" class="price small_input" name="sp[\' + counter_data + \'][]" id="sp_\' + counter_data + \'_qty_2" value="" />\';
								elem += \'</td>\';
								elem += \'<td>\';
								elem += \'<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" id="display_name" name="display_name" class="msStaffelPriceExcludingVat" value=""><label for="display_name">'.$this->pi_getLL('excluding_vat').'</label></div>\';
								elem += \'<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msStaffelPriceIncludingVat" value=""><label for="display_name">'.$this->pi_getLL('including_vat').'</label></div>\';
								elem += \'<div class="msAttributesField hidden"><input type="hidden" name="staffel_price[\' + counter_data + \']" class="price small_input" id="staffel_price" value=""></div>\';
								elem += \'</td>\';
								elem += \'<td>\';
								elem += \'<input type="button" value="x" onclick="remStaffelInput(\' + counter_data + \')"  class="msadmin_button" />\';
								elem += \'</td>\';
								elem += \'</tr>\';
								jQuery(\'#sp_end_row\').before(elem);
							}
							jQuery(\'#sp_row_counter\').val(counter_data);
						//}
						event.preventDefault();
					});
					function staffelPrice(o) {
						o.next().val(o.val());
					}
					jQuery(".staffel_price_display").keyup(function() {
						staffelPrice($(this));
					});
				});
				var remStaffelInput = function(c) {
					jQuery(\'#sp_\' + c).remove();
					var counter_data = parseInt(document.getElementById(\'sp_row_counter\').value);
					document.getElementById(\'sp_row_counter\').value = counter_data - 1;
				}
				$(document).on("keyup", ".msStaffelPriceExcludingVat", function() {
					productPrice(true, $(this));
				});
				$(document).on("keyup", ".msStaffelPriceIncludingVat", function() {
					productPrice(false, $(this));
				});
				</script>';
			if (empty($product['staffel_price'])) {
				$staffel_price_block.='
						<div class="account-field toggle_advanced_option" id="msEditProductInputStaffelPrice">
							<label for="products_price">'.$this->pi_getLL('admin_staffel_price').'</label>
							<input type="button" value="'.t3lib_div::strtoupper($this->pi_getLL('admin_add_staffel_price')).'" id="add_staffel_input" />
							<label>&nbsp;</label>
							<table cellpadding="0" cellspacing="0">
								<tr id="sp_end_row"><td align="right" colspan=4"><input type="hidden" id="sp_row_counter" value="0" /></td></tr>
							</table>
				
						</div>';
			} else {
				$staffel_price_block.='
						<div class="account-field" id="msEditProductInputStaffelPrice">
							<label for="products_price">'.$this->pi_getLL('admin_staffel_price').'</label>
							<table cellpadding="0" cellspacing="0">
								<tr>
									<td>'.t3lib_div::strtolower($this->pi_getLL('admin_from')).'</td>
									<td>'.t3lib_div::strtolower($this->pi_getLL('admin_till')).'</td>
									<td align="center">'.t3lib_div::strtolower($this->pi_getLL('admin_price')).'</td>
									<td>&nbsp;</td>
								</tr>';
				$sp_rows=explode(';', $product['staffel_price']);
				foreach ($sp_rows as $sp_idx=>$sp_row) {
					$sp_idx+=1;
					list($sp_col, $sp_price)=explode(':', $sp_row);
					list($sp_col_1, $sp_col_2)=explode('-', $sp_col);
					$staffel_tax=mslib_fe::taxDecimalCrop(($sp_price*$product_tax_rate)/100);
					$sp_price_display=mslib_fe::taxDecimalCrop($sp_price, 2, false);
					$staffel_price_display_incl=mslib_fe::taxDecimalCrop($sp_price+$staffel_tax, 2, false);
					$staffel_price_block.='
									<tr id="sp_'.$sp_idx.'">
										<td><input type="text" class="price small_input" name="sp['.$sp_idx.'][]" id="sp_'.$sp_idx.'_qty_1" readonly="readonly" value="'.$sp_col_1.'" /></td>
										<td><input type="text" class="price small_input" name="sp['.$sp_idx.'][]" id="sp_'.$sp_idx.'_qty_2" value="'.$sp_col_2.'" /></td>
										<td>
										<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" id="display_name" name="display_name" class="msStaffelPriceExcludingVat" value="'.htmlspecialchars($sp_price_display).'"><label for="display_name">'.$this->pi_getLL('excluding_vat').'</label></div>
										<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msStaffelPriceIncludingVat" value="'.htmlspecialchars($staffel_price_display_incl).'"><label for="display_name">'.$this->pi_getLL('including_vat').'</label></div>
										<div class="msAttributesField hidden"><input type="hidden" name="staffel_price['.$sp_idx.']" class="price small_input" id="staffel_price" value="'.htmlspecialchars($sp_price).'"></div>
										<td><input type="button" value="X" onclick="remStaffelInput(\''.$sp_idx.'\')"  class="msadmin_button" /></td>
									</tr>';
				}
				$staffel_price_block.='<tr id="sp_end_row"><td align="right" colspan=4"><input type="hidden" id="sp_row_counter" value="'.count($sp_rows).'" /><input type="button" value="'.$this->pi_getLL('admin_add_staffel_price').'" id="add_staffel_input" /></td></tr>
							</table>
					</div>';
			}
			$staffel_price_block.='</div>';
		}
		$manufacturer_input='<select name="manufacturers_id"><option value="">'.$this->pi_getLL('admin_choose_manufacturer').'</option>';
		$str="SELECT * from tx_multishop_manufacturers where status=1 order by manufacturers_name";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			$manufacturer_input.='<option value="'.$row['manufacturers_id'].'" '.(($row['manufacturers_id']==$product['manufacturers_id']) ? 'selected' : '').'>'.htmlspecialchars($row['manufacturers_name']).'</option>';
		}
		$manufacturer_input.='</select>';
		$order_unit='<select name="order_unit_id"><option value="">'.$this->pi_getLL('default').'</option>';
		$str="SELECT o.id, o.code, od.name from tx_multishop_order_units o, tx_multishop_order_units_description od where o.page_uid='".$this->shop_pid."' and o.id=od.order_unit_id and od.language_id='0' order by o.id desc";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			$order_unit.='<option value="'.$row['id'].'" '.(($row['id']==$product['order_unit_id']) ? 'selected' : '').'>'.htmlspecialchars($row['name']).'</option>';
		}
		$order_unit.='</select>';
		$options_tab_virtual_product='';
		foreach ($this->languages as $key=>$language) {
			$flag_path='';
			if ($language['flag']) {
				$flag_path='sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif';
			}
			$language_lable='';
			if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.$flag_path)) {
				$language_lable.='<img src="'.$this->FULL_HTTP_URL_TYPO3.$flag_path.'"> ';
			}
			$language_lable.=''.$language['title'];
			$options_tab_virtual_product.='
				<div class="account-field toggle_advanced_option msEditProductLanguageDivider" id="msEditProductInputLanguageDivider_'.$language['uid'].'">
					<label>'.t3lib_div::strtoupper($this->pi_getLL('language')).'</label>
					<strong>'.$language_lable.'</strong>
				</div>
				<div class="account-field toggle_advanced_option" id="msEditProductInputVirtualProductFile_'.$language['uid'].'">
					<label for="file_location">'.$this->pi_getLL('file').'</label>
					<input name="file_location['.$language['uid'].']" type="file" />';
			if ($lngproduct[$language['uid']]['file_label'] and $lngproduct[$language['uid']]['file_location']) {
				$label='download '.htmlspecialchars($lngproduct[$language['uid']]['file_label']);
				$options_tab_virtual_product.='<a href="'.mslib_fe::typolink(",2002", '&tx_multishop_pi1[page_section]=get_micro_download_by_admin&language_id='.$language['uid'].'&products_id='.$product['products_id']).'" alt="'.$label.'" title="'.$label.'">'.$label.'</a> <a href="'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax&pid='.$_REQUEST['pid'].'&action=edit_product&delete_micro_download=1&language_id='.$language['uid']).'" onclick="return confirm(\''.$this->pi_getLL('admin_label_js_are_you_sure').'\')"><img src="'.$this->FULL_HTTP_URL_MS.'templates/images/icons/delete2.png" border="0" alt="delete '.htmlspecialchars($lngproduct[$language['uid']]['file_label']).'"></a>';
			}
			$options_tab_virtual_product.='</div>
				<div class="account-field toggle_advanced_option" id="msEditProductInputVirtualProductExternalUrl_'.$language['uid'].'">
					<label for="file_remote_location">'.$this->pi_getLL('admin_external_url').'</label>
					<input type="text" class="text" name="file_remote_location['.$language['uid'].']" id="file_remote_location['.$language['uid'].']"  value="'.htmlspecialchars($lngproduct[$language['uid']]['file_remote_location']).'">
				</div>';
		}
		$shipping_payment_method='';
		if ($this->ms['MODULES']['PRODUCT_EDIT_METHOD_FILTER']) {
			$payment_methods=mslib_fe::loadPaymentMethods();
			// loading shipping methods eof
			$shipping_methods=mslib_fe::loadShippingMethods();
			if (count($payment_methods) or count($shipping_methods)) {
				$shipping_payment_method.='
						<div class="account-field div_products_mappings toggle_advanced_option" id="msEditProductInputPaymentMethod">
							<label>'.$this->pi_getLL('admin_mapped_methods').'</label>
							<div class="innerbox_methods">
								<div class="innerbox_payment_methods">
									<h4>'.$this->pi_getLL('admin_payment_methods').'</h4>
									<ul>';
				// load mapped ids
				$method_mappings=array();
				if ($product['products_id']) {
					$method_mappings=mslib_befe::getMethodsByProduct($product['products_id']);
				}
				$tr_type='';
				if (count($payment_methods)) {
					foreach ($payment_methods as $code=>$item) {
						if (!$tr_type or $tr_type=='even') {
							$tr_type='odd';
						} else {
							$tr_type='even';
						}
						$count++;
						$shipping_payment_method.='<li class="'.$tr_type.'"  id="multishop_payment_method_'.$item['id'].'">';
						if ($price_wrap) {
							$tmpcontent.=$price_wrap;
						}
						$shipping_payment_method.='<input name="payment_method[]" id="payment_method_'.$item['id'].'" type="checkbox" value="'.htmlspecialchars($item['id']).'"'.((is_array($method_mappings['payment']) and in_array($item['id'], $method_mappings['payment'])) ? ' checked' : '').' /><span>'.$item['name'].'</span></li>';
					}
				}
				$shipping_payment_method.='
									</ul>
								</div>
								<div class="innerbox_shipping_methods" id="msEditProductInputShippingMethod">
									<h4>'.$this->pi_getLL('admin_shipping_methods').'</h4>
							 		<ul id="multishop_shipping_method">';
				$count=0;
				$tr_type='';
				if (count($shipping_methods)) {
					foreach ($shipping_methods as $code=>$item) {
						$count++;
						$shipping_payment_method.='<li>';
						if ($price_wrap) {
							$shipping_payment_method.=$price_wrap;
						}
						$shipping_payment_method.='<input name="shipping_method[]" id="shipping_method_'.$item['id'].'" type="checkbox" value="'.htmlspecialchars($item['id']).'"'.((is_array($method_mappings['shipping']) and in_array($item['id'], $method_mappings['shipping'])) ? ' checked' : '').'  /><span>'.$item['name'].'</span>';
						$shipping_payment_method.='</li>';
					}
				}
				$shipping_payment_method.='
					 				</ul>
								</div>
							</div>
						</div>';
			}
		}
		/*
		 * images tab
		 */
		$images_tab_block='';
		for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
			$i=$x;
			if ($i==0) {
				$i='';
			}
			$images_tab_block.='
			<div class="account-field" id="msEditProductInputImage_'.$i.'">
				<label for="products_image'.$i.'">'.$this->pi_getLL('admin_image').' '.($i+1).'</label>
				<div id="products_image'.$i.'">
					<noscript>
						<input name="products_image'.$i.'" type="file" />
					</noscript>
				</div>
				<input name="ajax_products_image'.$i.'" id="ajax_products_image'.$i.'" type="hidden" value="" />';
			if ($_REQUEST['action']=='edit_product' and $product['products_image'.$i]) {
				$images_tab_block.='<img src="'.mslib_befe::getImagePath($product['products_image'.$i], 'products', '50').'">';
				$images_tab_block.=' <a href="'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax&cid='.$_REQUEST['cid'].'&pid='.$_REQUEST['pid'].'&action=edit_product&delete_image=products_image'.$i).'" onclick="return confirm(\'Are you sure?\')"><img src="'.$this->FULL_HTTP_URL_MS.'templates/images/icons/delete2.png" border="0" alt="'.$this->pi_getLL('admin_delete_image').'"></a>';
			}
			$images_tab_block.='</div>';
		}
		$images_tab_block.='<script>
		jQuery(document).ready(function($) {';
		for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
			$i=$x;
			if ($i==0) {
				$i='';
			}
			$images_tab_block.='
			var products_name=$("#products_name_0").val();
			var uploader'.$i.' = new qq.FileUploader({
				element: document.getElementById(\'products_image'.$i.'\'),
				action: \''.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_upload_product_images').'\',
				params: {
					products_name: products_name,
					file_type: \'products_image'.$i.'\'
				},
				template: \'<div class="qq-uploader">\' +
						  \'<div class="qq-upload-drop-area"><span>'.$this->pi_getLL('admin_label_drop_files_here_to_upload').'</span></div>\' +
						  \'<div class="qq-upload-button">'.addslashes(htmlspecialchars($this->pi_getLL('choose_image'))).'</div>\' +
						  \'<ul class="qq-upload-list"></ul>\' +
						  \'</div>\',
				onComplete: function(id, fileName, responseJSON){
					var filenameServer = responseJSON[\'filename\'];
					$("#ajax_products_image'.$i.'").val(filenameServer);
				},
				debug: false
			});';
		}
		$images_tab_block.='
			$(\'#products_name_0\').change(function() {
			var products_name=$("#products_name_0").val();';
		for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
			$i=$x;
			if ($i==0) {
				$i='';
			}
			$images_tab_block.='
					uploader'.$i.'.setParams({
					   products_name: products_name,
					   file_type: \'products_image'.$i.'\'
					});';
		}
		$images_tab_block.='
			});
		});
		</script>';
		/*
		 * meta tags tab
		 */
		$meta_tags_block='';
		foreach ($this->languages as $key=>$language) {
			$meta_tags_block.='
			<div class="account-field" id="msEditProductInputMeta_'.$language['uid'].'">
			<label>'.t3lib_div::strtoupper($this->pi_getLL('language')).'</label>';
			if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif')) {
				$meta_tags_block.='<img src="'.$this->FULL_HTTP_URL_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif"> ';
			}
			$meta_tags_block.=''.$language['title'].'
			</div>
			<div class="account-field" id="msEditProductInputMetaTitle_'.$language['uid'].'">
				<label for="products_meta_title">'.$this->pi_getLL('admin_label_input_meta_title').'</label>
				<input type="text" class="text" name="products_meta_title['.$language['uid'].']" id="products_meta_title['.$language['uid'].']" value="'.htmlspecialchars($lngproduct[$language['uid']]['products_meta_title']).'">
			</div>
			<div class="account-field" id="msEditProductInputMetaKeywords_'.$language['uid'].'">
				<label for="products_meta_keywords">'.$this->pi_getLL('admin_label_input_meta_keywords').'</label>
				<input type="text" class="text" name="products_meta_keywords['.$language['uid'].']" id="products_meta_keywords['.$language['uid'].']" value="'.htmlspecialchars($lngproduct[$language['uid']]['products_meta_keywords']).'">
			</div>
			<div class="account-field" id="msEditProductInputMetaDesc_'.$language['uid'].'">
				<label for="products_meta_description">'.$this->pi_getLL('admin_label_input_meta_description').'</label>
				<input type="text" class="text" name="products_meta_description['.$language['uid'].']" id="products_meta_description['.$language['uid'].']" value="'.htmlspecialchars($lngproduct[$language['uid']]['products_meta_description']).'">
			</div>
			';
		}
		/*
		 * attributes tab
		*/
		$attributes_tab_block='';
		// product Attribute
		if (!$this->ms['MODULES']['DISABLE_PRODUCT_ATTRIBUTES_TAB_IN_EDITOR']) {
			$attributes_tab_block.='
			<input name="options_form" type="hidden" value="1" />
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				jQuery(document).on("click", "#addAttributes", function(event) {
					$(this).parent().parent().hide();
					if ($(\'#add_attributes_holder>td\').html() !=\'\' && $(\'#add_attributes_holder>td\').html() !=\'&nbsp;\') {
						return false;
					}
					var new_attributes_html=\'\';
					new_attributes_html+=\'<span class="new_product_attributes">'.$this->pi_getLL('admin_label_add_new_product_attributes').'</span><div class="wrap-attributes-item" rel="new">\';
					new_attributes_html+=\'<table>\';
					new_attributes_html+=\'<tr class="option_row">\';

					new_attributes_html+=\'<td class="product_attribute_option">\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[options][]" id="tmp_options_sb" style="width:200px" />\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[is_manual_options][]" value="0" />\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[pa_id][]" value="0" />\';
					new_attributes_html+=\'</td>\';

					new_attributes_html+=\'<td class="product_attribute_value">\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[attributes][]" id="tmp_attributes_sb" style="width:200px" />\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[is_manual_attributes][]" value="0" />\';
					new_attributes_html+=\'</td>\';

					new_attributes_html+=\'<td class="product_attribute_prefix">\';
					new_attributes_html+=\'<select name="tx_multishop_pi1[prefix][]">\';
					new_attributes_html+=\'<option value="">&nbsp;</option>\';
					new_attributes_html+=\'<option value="+" selected="selected">+</option>\';
					new_attributes_html+=\'<option value="-">-</option>\';
					new_attributes_html+=\'</select>\';
					new_attributes_html+=\'</td>\';

					new_attributes_html+=\'<td class="product_attribute_price">\';
					new_attributes_html+=\'<div class="msAttributesField">\';
					new_attributes_html+=\''.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msAttributesPriceExcludingVat">\';
					new_attributes_html+=\'<label for="display_name">'.$this->pi_getLL('excluding_vat').'</label>\';
					new_attributes_html+=\'</div>\';
					new_attributes_html+=\'<div class="msAttributesField">\';
					new_attributes_html+=\''.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msAttributesPriceIncludingVat">\';
					new_attributes_html+=\'<label for="display_name">'.$this->pi_getLL('including_vat').'</label>\';
					new_attributes_html+=\'</div>\';
					new_attributes_html+=\'<div class="msAttributesField hidden">\';
					new_attributes_html+=\'<input type="hidden" name="tx_multishop_pi1[price][]" />\';
					new_attributes_html+=\'</div>\';
					new_attributes_html+=\'</td>\';

					new_attributes_html+=\'<td>\';
					new_attributes_html+=\'<input type="button" value="'.htmlspecialchars($this->pi_getLL('save')).'" class="msadmin_button save_new_attributes">&nbsp;<input type="button" value="'.htmlspecialchars($this->pi_getLL('cancel')).'" class="msadmin_button delete_tmp_product_attributes">\';
					new_attributes_html+=\'</td>\';
					new_attributes_html+=\'</tr>\';

					new_attributes_html+=\'</table>\';
					new_attributes_html+=\'</div>\';
					$(\'#add_attributes_holder>td\').html(new_attributes_html);
					// init selec2
					select2_sb("#tmp_options_sb", "'.$this->pi_getLL('admin_label_choose_option').'", "new_product_attribute_options_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_options').'");
					select2_values_sb("#tmp_attributes_sb", "'.$this->pi_getLL('admin_label_choose_attribute').'", "new_product_attribute_values_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_values').'");
					event.preventDefault();
				});
				jQuery(document).on("click", ".add_new_attributes_values", function(event) {
					var option_id=$(this).attr("rel");
					var d = new Date();
					var n = d.getTime();
					var new_option_cn="product_attribute_options" + n;
					var new_value_cn="product_attribute_values" + n;
					// cloned the first row of the option group
					var element_cloned=$($(this).parent().prev()).children().first().clone();
					// give the cloned row proper background color
					if ($($(this).parent().prev()).children().last().hasClass("odd_item_row")) {
						$(element_cloned).removeClass("odd_item_row").addClass("new_attributes even_item_row");
					} else {
						$(element_cloned).removeClass("even_item_row").addClass("new_attributes odd_item_row");
					}
					$(element_cloned).removeAttr("id");
					$(element_cloned).attr("rel", "new");
					// cleaned up the cloned value
					$(element_cloned).find("td.product_attribute_option>div").remove();
					$(element_cloned).find("td.product_attribute_value>div").remove();
					$(element_cloned).find("input[class^=\'product_attribute_options\']").attr("class", function(i, c){
						var classes_name=c.split(" ");
						var class_name="";
						$.each(classes_name, function(i, x){
							if (x.indexOf("product_attribute_options")!==-1) {
								class_name=x;
							}
						});
						$(this).removeClass(class_name).addClass(new_option_cn);
						// clear the pa_id
						$(this).next().next().val("");
					});
					$(element_cloned).find("input[class^=\'product_attribute_values\']").attr("class", function(i, c){
						var classes_name=c.split(" ");
						var class_name="";
						$.each(classes_name, function(i, x){
							if (x.indexOf("product_attribute_values")!==-1) {
								class_name=x;
							}
						});
						$(this).removeClass(class_name).addClass(new_value_cn);
						$(this).removeAttr("id");
						$(this).val("");
						$(this).next().removeAttr("id");
						$(this).next().val("");
					});
					$(element_cloned).find("div.product_attribute_prefix>select").val("+");
					$(element_cloned).find("div.msAttributesField>input").val("0.00");
					// add new shiny cloned attributes row
					$($(this).parent().prev()).append(element_cloned);
					// init selec2
					select2_sb(".product_attribute_options" + n, "'.$this->pi_getLL('admin_label_choose_option').'", "new_product_attribute_options_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_options').'");
					select2_values_sb(".product_attribute_values" + n, "'.$this->pi_getLL('admin_label_choose_attribute').'", "new_product_attribute_values_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_values').'");
					event.preventDefault();
				});
				jQuery(document).on("click", ".save_new_attributes", function(){
					var pa_main_divwrapper=$(this).parent().parent().parent().parent().parent();
					var pa_option_sb=$("#tmp_options_sb").select2("data");
					var pa_attributes_sb=$("#tmp_attributes_sb").select2("data");
					if (pa_option_sb !== null && pa_attributes_sb !== null) {
						var selected_pa_option_id=pa_option_sb.id;
						var selected_pa_option_text=pa_option_sb.text;
					} else {
						var selected_pa_option_id="";
						var selected_pa_option_text="";
					}
					var target_liwrapper_id="#products_attributes_item_" + selected_pa_option_id + " > div.items_wrapper";
					if (selected_pa_option_id != "") {
						var delete_button_html=\'<input type="button" value="'.htmlspecialchars($this->pi_getLL('delete')).'" class="msadmin_button delete_product_attributes">\';
						// add class for marker
						$(pa_main_divwrapper).addClass("new_attributes");
						// check for the main tr if it exists
						if ($("#product_attributes_content_row").length===0) {
							var new_tr=\'<tr id="product_attributes_content_row"><td colspan="5"><ul id="products_attributes_items"></ul></td></tr>\';
							$(new_tr).insertBefore("#add_attributes_holder");
							// activate sortable on ul > li
							sort_li();
						}
						// destroy select2 before moving to <li>
						$("#tmp_options_sb").select2("destroy");
						$("#tmp_attributes_sb").select2("destroy");
						// check if the <li> is exist
						if ($(target_liwrapper_id).length) {
							// directly append if exist
							if ($(target_liwrapper_id).children().last().hasClass("odd_item_row")) {
								$(pa_main_divwrapper).addClass("even_item_row");
							} else {
								$(pa_main_divwrapper).addClass("odd_item_row");
							}
							// rewrite the button
							$(this).parent().empty().html(delete_button_html);
							// flush it to existing li
							$(target_liwrapper_id).append(pa_main_divwrapper);
							if ($(target_liwrapper_id).is(":hidden")) {
								$(target_liwrapper_id).prev().children().removeClass("items_wrapper_folded").addClass("items_wrapper_unfolded").html("fold");
								$(target_liwrapper_id).show();
							}
						} else {
							var li_class="odd_group_row";
							if ($(".products_attributes_items").children().last().hasClass("odd_group_row")) {
								li_class="even_group_row";
							}
							var new_li = $("<li/>", {
								id: "products_attributes_item_" + selected_pa_option_id,
								alt: selected_pa_option_text,
								class: "products_attributes_item " + li_class
							});
							$(new_li).append(\'<span class="option_name">\' + selected_pa_option_text + \' <a href="#" class="items_wrapper_unfolded">fold</a></span><div class="items_wrapper"></div><div class="add_new_attributes"><input type="button" class="msadmin_button add_new_attributes_values" value="'.$this->pi_getLL('admin_add_new_value').' [+]" rel="\' + selected_pa_option_id + \'" /></div>\');
							$(pa_main_divwrapper).addClass("odd_item_row");
							// rewrite the button
							$(this).parent().empty().html(delete_button_html);
							// flush it to existing li
							$(new_li).children("div.items_wrapper").append(pa_main_divwrapper);
							// flush new li to the newly created tr > ul
							$("#products_attributes_items").append(new_li);
							// activate sorting for li children
							sort_li_children();
						}
						// appended to select2 class name for newly created select2 instantiation
						// so it wont refresh others select2 elements
						var d = new Date();
						var n = d.getTime();
						$("#tmp_options_sb").addClass("product_attribute_options" + n);
						$("#tmp_attributes_sb").addClass("product_attribute_values" + n);
						// remove id for reuse later
						$("#tmp_options_sb").removeAttr("id");
						$("#tmp_attributes_sb").removeAttr("id");
						// init the select2 for new product attributes
						select2_sb(".product_attribute_options" + n, "'.$this->pi_getLL('admin_label_choose_option').'", "product_attribute_options_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_options').'");
						select2_values_sb(".product_attribute_values" + n, "'.$this->pi_getLL('admin_label_choose_attribute').'", "product_attribute_values_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_values').'");
						// clear the temp holder
						$("tr#add_attributes_holder > td").html("&nbsp;");
						$("#add_attributes_button").show();
					} else {
						msDialog("ERROR","'.$this->pi_getLL('admin_label_please_select_options_and_attributes_value').'");
					}
				});
				$(document).on("click", "#manual_button", function(event) {
					jQuery("#attributes_header").show();
				});
				$(document).on("click", "span.option_name", function(e){
					e.preventDefault();
					var self = $(this).children("a");
					var li_this=$(self).parent().parent();
					if($(self).hasClass("items_wrapper_unfolded")) {
						$(li_this).children("div.items_wrapper").hide();
						$(li_this).children("div.add_new_attributes").hide();
						$(self).removeClass("items_wrapper_unfolded");
						$(self).addClass("items_wrapper_folded").html("unfold");
					} else {
						$(li_this).children("div.items_wrapper").show();
						$(li_this).children("div.add_new_attributes").show();
						$(self).removeClass("items_wrapper_folded");
						$(self).addClass("items_wrapper_unfolded").html("fold");
					}
				});
				jQuery(document).on("click", ".delete_product_attributes", function(){
					var pa_main_divwrapper=$(this).parent().parent().parent().parent().parent();
					var pa_main_liwrapper=$(pa_main_divwrapper).parent();
					var product_attribute_id=$(pa_main_divwrapper).attr("rel");
					if (product_attribute_id != "new") {
						href = "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=delete_product_attributes&pid='.$product['products_id']).'";
						jQuery.ajax({
							type:"POST",
							url:href,
							data: "paid=" + product_attribute_id,
							success: function(msg) {
								//do something with the sorted data
							}
						});
					}
					$(pa_main_divwrapper).remove();
					if ($(pa_main_liwrapper).children().length === 0) {
						$(pa_main_liwrapper).parent().remove();
					}
				});
				jQuery(document).on("click", ".delete_tmp_product_attributes", function(){
					var pa_main_divwrapper=$(this).parent().parent().parent().parent().parent();
					$(pa_main_divwrapper).remove();

					$("tr#add_attributes_holder > td").html("&nbsp;");
					$("#add_attributes_button").show();
				});
				var select2_sb = function(selector_str, placeholder, dropdowncss, ajax_url) {
					$(selector_str).select2({
						placeholder: placeholder,
						createSearchChoice:function(term, data) {
							if (attributesOptions[term] === undefined) {
								attributesOptions[term]={id: term, text: term};
							}
							return {id:term, text:term};
						},
						minimumInputLength: 0,
						query: function(query) {
							if (attributesSearchOptions[query.term] !== undefined) {
								query.callback({results: attributesSearchOptions[query.term]});
							} else {
								$.ajax(ajax_url, {
									data: {
										q: query.term
									},
									dataType: "json"
								}).done(function(data) {
									attributesSearchOptions[query.term]=data;
									query.callback({results: data});
								});
							}
						},
						initSelection: function(element, callback) {
							var id=$(element).val();
							if (id!=="") {
								if (attributesOptions[id] !== undefined) {
									callback(attributesOptions[id]);
								} else {
									$.ajax(ajax_url, {
										data: {
											preselected_id: id
										},
										dataType: "json"
									}).done(function(data) {
										attributesOptions[data.id]={id: data.id, text: data.text};
										callback(data);
									});
								}
							}
						},
						formatResult: function(data){
							if (data.text === undefined) {
								$.each(data, function(i,val){
									return val.text;
								});
							} else {
								return data.text;
							}
						},
						formatSelection: function(data){
							if (data.text === undefined) {
								return data[0].text;
							} else {
								return data.text;
							}
						},
						dropdownCssClass: dropdowncss,
						escapeMarkup: function (m) { return m; }
					}).on("select2-selecting", function(e) {
						if (e.object.id == e.object.text) {
							$(this).next().val("1");
						} else {
							$(this).next().val("0");
						}
					});
				}
				var select2_values_sb = function(selector_str, placeholder, dropdowncss, ajax_url) {
					$(selector_str).select2({
						placeholder: placeholder,
						createSearchChoice:function(term, data) {
							if ($(data).filter(function() {
								return this.text.localeCompare(term)===0;
							}).length===0) {
								if (attributesValues[term] === undefined) {
									attributesValues[term]={id: term, text: term};
								}
								return {id:term, text:term};
							}
						},
						minimumInputLength: 0,
						query: function(query) {
							if (attributesSearchValues[query.term] !== undefined) {
								query.callback({results: attributesSearchValues[query.term]});
							} else {
								$.ajax(ajax_url, {
									data: {
										q: query.term + "||optid=" +  $(selector_str).parent().prev().children("input").val()
									},
									dataType: "json"
								}).done(function(data) {
									attributesSearchValues[query.term]=data;
									query.callback({results: data});
								});
							}
						},
						initSelection: function(element, callback) {
							var id=$(element).val();
							if (id!=="") {
								if (attributesValues[id] !== undefined) {
									callback(attributesValues[id]);
								} else {
									$.ajax(ajax_url, {
										data: {
											preselected_id: id,
										},
										dataType: "json"
									}).done(function(data) {
										attributesValues[data.id]={id: data.id, text: data.text};
										callback(data);
									});
								}
							}
						},
						formatResult: function(data){
							var tmp_data=data.text.split("||");
							return tmp_data[0];
						},
						formatSelection: function(data){
							if (data.text === undefined) {
								return data[0].text;
							} else {
								return data.text;
							}
						},
						dropdownCssClass: dropdowncss,
						escapeMarkup: function (m) { return m; } // we do not want to escape markup since we are displaying html in results
					}).on("select2-selecting", function(e) {
						if (e.object.id == e.object.text) {
							$(this).next().val("1");
						} else {
							$(this).next().val("0");
						}
					});;
				}
				var sort_li = function () {
					jQuery("#products_attributes_items").sortable({
						'.($product['products_id'] ? '
						update: function(e, ui) {
							href = "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=sort_product_attributes_option&pid='.$product['products_id']).'";
							jQuery(this).sortable("refresh");
							sorted = jQuery(this).sortable("serialize", "id");
							jQuery.ajax({
								type:"POST",
								url:href,
								data:sorted,
								success: function(msg) {
									//do something with the sorted data
								}
							});
						},
						' : '').'
						cursor:"move",
						items:">li.products_attributes_item"
					});
				}
				var sort_li_children = function () {
					jQuery(".items_wrapper").sortable({
						'.($product['products_id'] ? '
						update: function(e, ui) {
							href = "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=sort_product_attributes_value&pid='.$product['products_id']).'";
							jQuery(this).sortable("refresh");
							sorted = jQuery(this).sortable("serialize", "id");
							jQuery.ajax({
								type:"POST",
								url:href,
								data:sorted,
								success: function(msg) {
									//do something with the sorted data
								}
							});
						},
						' : '').'
						cursor:"move",
						items:">div.wrap-attributes-item"
					});
				}
				sort_li();
				sort_li_children();
				$(".items_wrapper").hide();
				$(".add_new_attributes").hide();
				select2_sb(".product_attribute_options", "'.$this->pi_getLL('admin_label_choose_option').'", "product_attribute_options_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_options').'");
				select2_values_sb(".product_attribute_values", "'.$this->pi_getLL('admin_label_choose_attribute').'", "product_attribute_values_dropdown", "'.mslib_fe::typolink(',2002', '&tx_multishop_pi1[page_section]=admin_ajax_product_attributes&tx_multishop_pi1[admin_ajax_product_attributes]=get_attributes_values').'");
			});
			</script>
			<h1>'.$this->pi_getLL('admin_product_attributes').'</h1>
			';
			if ($this->get['cid']) {
				// optional predefined attributes menu
				$catCustomSettings=mslib_fe::loadInherentCustomSettingsByCategory($this->get['cid']);
				$productOptions=array();
				if ($product['products_id']) {
					$productOptions=mslib_fe::getProductOptions($product['products_id']);
				}
				// ADMIN_PREDEFINED_ATTRIBUTE_FIELDS
				if ($catCustomSettings ['ADMIN_PREDEFINED_ATTRIBUTE_FIELDS']) {
					$fields=explode(";", $catCustomSettings ['ADMIN_PREDEFINED_ATTRIBUTE_FIELDS']);
					if (is_array($fields) and count($fields)) {
						$attributes_tab_block.='
						<style>
							#predefined_attributes
							{
								width:100%;
							}
							#predefined_attributes label
							{
								color: #999;
								font-size:12px;
								font-weight:bold;
							}
							#predefined_attributes .options_attributes
							{
								width:150px;float:left;overflow:hidden;
								padding-bottom:10px;
							}
						</style>
			<div class="wrap-attributes" id="msEditProductInputAttributes">
			<table width="100%" cellpadding="2" cellspacing="2">
				<tr class="option_row2" >
				   <td>
						<div id="predefined_attributes">
						';
						foreach ($fields as $field) {
							if (strstr($field, ":")) {
								$array=explode(":", $field);
								if (strstr($array [1], '{asc}')) {
									$order_by='asc';
									$array [1]=str_replace('{asc}', '', $array [1]);
								} elseif (strstr($array [1], '{desc}')) {
									$order_by='desc';
									$array [1]=str_replace('{desc}', '', $array [1]);
								} else {
									$order_column='povp.sort_order';
									$order_by='asc';
								}
								$option_id=$array [0];
								$list_type=$array [1];
								$query=$GLOBALS ['TYPO3_DB']->SELECTquery('*', // SELECT ...
									'tx_multishop_products_options', // FROM ...
									'products_options_id=\''.$option_id.'\' and language_id=\''.$this->sys_language_uid.'\'', // WHERE.
									'', // GROUP BY...
									'', // ORDER BY...
									'') // LIMIT ...
								;
								$res=$GLOBALS ['TYPO3_DB']->sql_query($query);
								if ($GLOBALS ['TYPO3_DB']->sql_num_rows($res)>0) {
									$i=0;
									while (($row=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($res))!=false) {
										$query_opt_2_values=$GLOBALS ['TYPO3_DB']->SELECTquery('pov.products_options_values_id, pov.products_options_values_name', // SELECT
											// ...
											'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp', // FROM
											// ...
											"pov.language_id='".$this->sys_language_uid."' and povp.products_options_id = ".$option_id." and pov.products_options_values_id=povp.products_options_values_id", // WHERE.
											'', // GROUP BY...
											'povp.sort_order '.$order_by, // ORDER BY...
											'') // LIMIT ...
										;
										$res_opt_2_values=$GLOBALS ['TYPO3_DB']->sql_query($query_opt_2_values);
										if ($GLOBALS ['TYPO3_DB']->sql_num_rows($res_opt_2_values)>0) {
											$attributes_tab_block.='<div class="options_attributes"><label>'.$row ['products_options_name'].'</label>';
											if ($list_type=='list') {
												$attributes_tab_block.='
													<div class="options_attributes_wrapper">
															 <select class="option-attributes" name="predefined_option['.$option_id.'][]" id="option'.$option_id.'"><option value="">'.htmlspecialchars('None').'</option>';
												while (($row_opt_2_values=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($res_opt_2_values))!=false) {
													$selected=(is_array($productOptions [$option_id]) and in_array($row_opt_2_values ['products_options_values_id'], $productOptions [$option_id])) ? " selected" : "";
													$attributes_tab_block.='<option value="'.$row_opt_2_values ['products_options_values_id'].'"'.$selected.'>'.htmlspecialchars($row_opt_2_values ['products_options_values_name']).'</option>'."\n";
												}
												$attributes_tab_block.='</select></div>'."\n";
											} elseif ($list_type=='multiple') {
												$attributes_tab_block.='
													<div class="options_attributes_wrapper">
													<select class="option-attributes option-attributes-multiple" name="predefined_option['.$option_id.'][]" id="option'.$option_id.'" size="10" style=";height:100px;" multiple="multiple"><option value="">'.htmlspecialchars('None').'</option>';
												while (($row_opt_2_values=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($res_opt_2_values))!=false) {
													$selected=(is_array($productOptions [$option_id]) and in_array($row_opt_2_values ['products_options_values_id'], $productOptions [$option_id])) ? " selected" : "";
													$attributes_tab_block.='<option value="'.$row_opt_2_values ['products_options_values_id'].'"'.$selected.'>'.htmlspecialchars($row_opt_2_values ['products_options_values_name']).'</option>'."\n";
												}
												$attributes_tab_block.='</select></div>'."\n";
											} elseif ($list_type=='checkbox') {
												$attributes_tab_block.='<div class="options_attributes_wrapper">
													<input name="predefined_option['.$option_id.'][]" type="hidden" value="" />
													';
												while (($row_opt_2_values=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($res_opt_2_values))!=false) {
													$selected=(is_array($productOptions [$option_id]) and in_array($row_opt_2_values ['products_options_values_id'], $productOptions [$option_id])) ? " checked" : "";
													$attributes_tab_block.='<div class="option_attributes_radio"><input type="checkbox" name="predefined_option['.$option_id.'][]" value="'.$row_opt_2_values ['products_options_values_id'].'" class="option-attributes" id="option'.$option_id.'"'.$selected.'>'.htmlspecialchars($row_opt_2_values ['products_options_values_name']).'</div>'."\n";
												}
												$attributes_tab_block.='</div>'."\n";
											} elseif ($list_type=='radio') {
												$attributes_tab_block.='<div class="options_attributes_wrapper">
						<div class="option_attributes_radio">
							<input type="radio" name="predefined_option['.$option_id.'][]" id="option'.$option_id.'" value=""  class="option-attributes">'.htmlspecialchars('None').'
						</div>
													';
												while (($row_opt_2_values=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($res_opt_2_values))!=false) {
													$selected=(is_array($productOptions [$option_id]) and in_array($row_opt_2_values ['products_options_values_id'], $productOptions [$option_id])) ? " checked" : "";
													$attributes_tab_block.='<div class="option_attributes_radio"><input type="radio" name="predefined_option['.$option_id.'][]" id="option'.$option_id.'" value="'.$row_opt_2_values ['products_options_values_id'].'" class="option-attributes"'.$selected.'>'.htmlspecialchars($row_opt_2_values ['products_options_values_name']).'</div>'."\n";
												}
												$attributes_tab_block.='</div>'."\n";
											}
											$attributes_tab_block.='</div>'."\n";
										}
										$i++;
									}
								}
							}
						}
						$attributes_tab_block.='</div>
						</td></tr></table>
						</div>
						'."\n";
					}
				}
			}
			// end optional predefined attributes menu
			$sql_pa=$GLOBALS ['TYPO3_DB']->SELECTquery('popt.required,popt.products_options_id, popt.products_options_name, popt.listtype, patrib.*', // SELECT ...
				'tx_multishop_products_options popt, tx_multishop_products_attributes patrib', // FROM ...
				"patrib.products_id='".$product['products_id']."' and popt.language_id = '0' and patrib.options_id = popt.products_options_id", // WHERE.
				'', // GROUP BY...
				'patrib.sort_order_option_name, patrib.sort_order_option_value', // ORDER BY...
				'' // LIMIT ...
			);
			$qry_pa=$GLOBALS ['TYPO3_DB']->sql_query($sql_pa);
			$attributes_tab_block.='<table width="100%" cellpadding="2" cellspacing="2" id="product_attributes_table">';
			$js_select2_cache='';
			$js_select2_cache_options=array();
			$js_select2_cache_values=array();
			$js_select2_cache='
			<script type="text/javascript">
				var attributesSearchOptions=[];
				var attributesSearchValues=[];
				var attributesOptions=[];
				var attributesValues=[];'."\n";
			if ($product['products_id']) {
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_pa)>0) {
					$ctr=1;
					$options_data=array();
					$attributes_data=array();
					while (($row=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($qry_pa))!=false) {
						$row['options_values_name']=mslib_fe::getNameOptions($row['options_values_id']);
						$options_data[$row['products_options_id']]=$row['products_options_name'];
						$attributes_data[$row['products_options_id']][]=$row;
						// js cache
						$js_select2_cache_options[$row['products_options_id']]='attributesOptions['.$row['products_options_id'].']={id:"'.$row['products_options_id'].'", text:"'.$row['products_options_name'].'"}';
						$js_select2_cache_values[$row['options_values_id']]='attributesValues['.$row['options_values_id'].']={id:"'.$row['options_values_id'].'", text:"'.$row['options_values_name'].'"}';
					}
					if (count($options_data)) {
						$attributes_tab_block.='<tr id="product_attributes_content_row">';
						$attributes_tab_block.='<td colspan="5"><ul id="products_attributes_items">';
						foreach ($options_data as $option_id=>$option_name) {
							if (!isset($group_row_type) || $group_row_type=='even_group_row') {
								$group_row_type='odd_group_row';
							} else {
								$group_row_type='even_group_row';
							}
							$attributes_tab_block.='<li id="products_attributes_item_'.$option_id.'" alt="'.$option_name.'" class="products_attributes_item '.$group_row_type.'">
							<span class="option_name">'.$option_name.' <a href="#" class="items_wrapper_folded">unfold</a></span>
							<div class="items_wrapper">
							';
							foreach ($attributes_data[$option_id] as $attribute_data) {
								if (!isset($item_row_type) || $item_row_type=='even_item_row') {
									$item_row_type='odd_item_row';
								} else {
									$item_row_type='even_item_row';
								}
								$attributes_tab_block.='<div class="wrap-attributes-item '.$item_row_type.'" id="item_product_attribute_'.$attribute_data['products_attributes_id'].'" rel="'.$attribute_data['products_attributes_id'].'">';
								$attributes_tab_block.='<table>';
								$attributes_tab_block.='<tr class="option_row">';
								$attributes_tab_block.='<td class="product_attribute_option">';
								$attributes_tab_block.='<input type="hidden" name="tx_multishop_pi1[options][]" id="option_'.$attribute_data['products_attributes_id'].'" class="product_attribute_options" value="'.$option_id.'" style="width:200px" />';
								$attributes_tab_block.='<input type="hidden" name="tx_multishop_pi1[is_manual_options][]" id="manual_option_'.$attribute_data['products_attributes_id'].'" value="0" />';
								$attributes_tab_block.='<input type="hidden" name="tx_multishop_pi1[pa_id][]" value="'.$attribute_data['products_attributes_id'].'" />';
								/*$attributes_tab_block.='<select name="tx_multishop-pi1[options][]" id="option_'.$attribute_data['products_attributes_id'].'" class="product_attribute_options">';
								$attributes_tab_block.='<option value="">'.$this->pi_getLL('admin_label_choose_option').'</option>';
								// fetch attributes options
								$str=$GLOBALS ['TYPO3_DB']->SELECTquery('*', // SELECT ...
									'tx_multishop_products_options', // FROM ...
									'language_id = 0', // WHERE.
									'', // GROUP BY...
									'sort_order', // ORDER BY...
									'' // LIMIT ...
								);
								$qry=$GLOBALS ['TYPO3_DB']->sql_query($str);
								while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
									if ($row2['products_options_id']==$option_id) {
										$attributes_tab_block.='<option value="'.$row2['products_options_id'].'" selected="selected">'.$row2['products_options_name'].'</option>';
									} else {
										$attributes_tab_block.='<option value="'.$row2['products_options_id'].'">'.$row2['products_options_name'].'</option>';
									}
								}
								$attributes_tab_block.='</select>';*/
								$attributes_tab_block.='</td>';
								$attributes_tab_block.='<td class="product_attribute_value">';
								$attributes_tab_block.='<input type="hidden" name="tx_multishop_pi1[attributes][]" id="attribute_'.$attribute_data['products_attributes_id'].'" class="product_attribute_values" value="'.$attribute_data['options_values_id'].'" style="width:200px" />';
								$attributes_tab_block.='<input type="hidden" name="tx_multishop_pi1[is_manual_attributes][]" id="manual_attributes_'.$attribute_data['products_attributes_id'].'" value="0" />';
								/*$attributes_tab_block.='<select name="tx_multishop_pi1[attributes][]" id="attribute_'.$attribute_data['products_attributes_id'].'" class="product_attribute_values">';
								$attributes_tab_block.='<option value="">'.$this->pi_getLL('admin_label_choose_attribute').'</option>';
								// fetch values
								$str2=$GLOBALS ['TYPO3_DB']->SELECTquery('optval.*', // SELECT...
									'tx_multishop_products_options_values as optval, tx_multishop_products_options_values_to_products_options as optval2opt', // FROM...
									'optval2opt.products_options_id = '.$option_id.' and optval2opt.products_options_values_id = optval.products_options_values_id and optval.language_id = 0', // WHERE...
									'', // GROUP BY...
									'optval2opt.sort_order', // ORDER BY...
									'' // LIMIT...
								);
								$qry2=$GLOBALS ['TYPO3_DB']->sql_query($str2);
								while (($row3=$GLOBALS ['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
									if ($row3['products_options_values_id']==$attribute_data['options_values_id']) {
										$attributes_tab_block.='<option value="'.$row3['products_options_values_id'].'" selected="selected">'.$row3['products_options_values_name'].'</option>';
									} else {
										$attributes_tab_block.='<option value="'.$row3['products_options_values_id'].'">'.$row3['products_options_values_name'].'</option>';
									}
								}
								$attributes_tab_block.='</select>';*/
								$attributes_tab_block.='</td>';
								$attributes_tab_block.='<td class="product_attribute_prefix">';
								$attributes_tab_block.='<select name="tx_multishop_pi1[prefix][]">';
								$attributes_tab_block.='<option value="">&nbsp;</option>';
								$attributes_tab_block.='<option value="+"'.($attribute_data['price_prefix']=='+' ? ' selected="selected"' : '').'>+</option>';
								$attributes_tab_block.='<option value="-"'.($attribute_data['price_prefix']=='-' ? ' selected="selected"' : '').'>-</option>';
								$attributes_tab_block.='</select>';
								//$attributes_tab_block.='<input type="text" name="tx_multishop_pi1[prefix][]" value="'.$attribute_data['price_prefix'].'" />';
								$attributes_tab_block.='</td>';
								// recalc price to display
								$attributes_tax=mslib_fe::taxDecimalCrop(($attribute_data['options_values_price']*$product_tax_rate)/100);
								$attribute_price_display=mslib_fe::taxDecimalCrop($attribute_data['options_values_price'], 2, false);
								$attribute_price_display_incl=mslib_fe::taxDecimalCrop($attribute_data['options_values_price']+$attributes_tax, 2, false);
								$attributes_tab_block.='<td>
											<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" id="display_name" name="display_name" class="msAttributesPriceExcludingVat" value="'.$attribute_price_display.'"><label for="display_name">'.$this->pi_getLL('excluding_vat').'</label></div>
											<div class="msAttributesField">'.mslib_fe::currency().' <input type="text" name="display_name" id="display_name" class="msAttributesPriceIncludingVat" value="'.$attribute_price_display_incl.'"><label for="display_name">'.$this->pi_getLL('including_vat').'</label></div>
											<div class="msAttributesField hidden"><input type="hidden" name="tx_multishop_pi1[price][]" value="'.$attribute_data['options_values_price'].'" /></div>
										</td>';
								$attributes_tab_block.='<td class="product_attribute_price"><input type="button" value="'.htmlspecialchars($this->pi_getLL('delete')).'" class="msadmin_button delete_product_attributes"></td>';
								$attributes_tab_block.='</tr>';
								$attributes_tab_block.='</table>';
								$attributes_tab_block.='</div>';
							}
							$attributes_tab_block.='</div><div class="add_new_attributes"><input type="button" class="msadmin_button add_new_attributes_values" value="'.$this->pi_getLL('admin_add_new_value').' [+]" rel="'.$option_id.'" /></div>';
							$attributes_tab_block.='</li>';
						}
						$attributes_tab_block.='</ul></td>';
						$attributes_tab_block.='</tr>';
					}
				}
				$count_js_cache_options=count($js_select2_cache_options);
				$count_js_cache_values=count($js_select2_cache_values);
				if ($count_js_cache_options) {
					$js_select2_cache.=implode(";\n", $js_select2_cache_options);
				}
				if ($count_js_cache_values) {
					if ($count_js_cache_options) {
						$js_select2_cache.=";\n";
					}
					$js_select2_cache.=implode(";\n", $js_select2_cache_values).";\n";
				}
			}
			$js_select2_cache.='</script>';
			if (!empty($js_select2_cache)) {
				$GLOBALS['TSFE']->additionalHeaderData['js_select2_cache']=$js_select2_cache;
			}
			$attributes_tab_block.='<tr id="add_attributes_holder">
					<td colspan="5">&nbsp;</td>
			</tr>';
			$attributes_tab_block.='<tr id="add_attributes_button">
					<td colspan="5" align="right"><input id="addAttributes" type="button" class="msadmin_button" value="'.$this->pi_getLL('admin_add_new_attribute').' [+]"></td>
			</tr>
			</table>
			<script>
			$(document).on("keyup", ".msAttributesPriceExcludingVat", function() {
				productPrice(true, $(this));
			});
			$(document).on("keyup", ".msAttributesPriceIncludingVat", function() {
				productPrice(false, $(this));
			});
			</script>
			';
		}
		// product Attribute eof
		/*
		 * product relatives tab
		 */
		// product Relatives
		$product_relatives_block='';
		if ($_REQUEST['action']=='edit_product') {
			$form_category_search='
			<table>
				<tr>
					<td><label>'.$this->pi_getLL('admin_keyword').'</label></td>
					<td>
						<input type="text" name="keypas" id="key" value=""> </input>
					</td>
					<td>'.mslib_fe::tx_multishop_draw_pull_down_menu('rel_catid" id="rel_catid', mslib_fe::tx_multishop_get_category_tree('', '', '')).'</td>
					<td>
						<input type="button" id="filter" value="'.$this->pi_getLL('admin_search').'" />
					<td>
			
				</tr>
			</table>';
			$product_relatives_block='<h1>'.$this->pi_getLL('admin_related_products').'</h1>'.$form_category_search.'<div id="load"><img src="'.$this->FULL_HTTP_URL_MS.'templates/images/loading2.gif"><strong>Loading....</strong></div><div id="related_product_placeholder"></div>';
		}
		/*
		 * product copy tab
		 */
		$product_copy_block='';
		if ($_REQUEST['action']=='edit_product') {
			$product_copy_block.='
				<h1>'.$this->pi_getLL('admin_copy_duplicate_product').'</h1>
				<div class="account-field" id="msEditProductInputDuplicateProduct">
		
				<label for="cid">'.$this->pi_getLL('admin_select_category').'</label>
				'.mslib_fe::tx_multishop_draw_pull_down_menu('cid', mslib_fe::tx_multishop_get_category_tree('', '', ''), $this->get['cid'], 'class="select2BigDropWider"').'
				</div>
				<div id="cp_buttons">
					<input type="button" value="'.t3lib_div::strtoupper($this->pi_getLL('admin_relate_product_to_category')).'" id="cp_product" />
					<input type="button" value="'.t3lib_div::strtoupper($this->pi_getLL('admin_duplicate_product')).'" id="dp_product" />
				</div>
				<div id="has_cd">
				</div>';
		}
		/*
		 * layout page
		*/
		$subpartArray=array();
		$subpartArray['###VALUE_REFERRER###']='';
		if ($this->post['tx_multishop_pi1']['referrer']) {
			$subpartArray['###VALUE_REFERRER###']=$this->post['tx_multishop_pi1']['referrer'];
		} else {
			$subpartArray['###VALUE_REFERRER###']=$_SERVER['HTTP_REFERER'];
		}
		$subpartArray['###LABEL_TABS_PRODUCTS_DETAILS###']=$this->pi_getLL('admin_details');
		$subpartArray['###LABEL_TABS_PRODUCT_OPTIONS###']=$this->pi_getLL('admin_options');
		$subpartArray['###LABEL_TABS_PRODUCT_IMAGES###']=$this->pi_getLL('admin_images');
		$subpartArray['###LABEL_TABS_META_TAGS###']='META';
		$subpartArray['###LABEL_TABS_PRODUCT_ATTRIBUTES###']=$this->pi_getLL('admin_attributes');
		$subpartArray['###LABEL_TABS_PRODUCT_RELATIVES###']=$this->pi_getLL('admin_related_products');
		$subpartArray['###LABEL_TABS_PRODUCT_COPY###']=$this->pi_getLL('admin_copy_duplicate_product');
		$subpartArray['###JS_HEADER###']=$js_header;
		$subpartArray['###VALUE_ADVANCED_OPTION###']=($_COOKIE['hide_advanced_options']==1 ? $this->pi_getLL('admin_show_options') : $this->pi_getLL('admin_hide_options'));
		$subpartArray['###LABEL_BUTTON_CANCEL###']=$this->pi_getLL('admin_cancel');
		$subpartArray['###LINK_BUTTON_CANCEL###']=$subpartArray['###VALUE_REFERRER###'];
		$subpartArray['###FOOTER_LINK_BUTTON_CANCEL###']=$subpartArray['###VALUE_REFERRER###'];
		$subpartArray['###LABEL_BUTTON_SAVE###']=$this->pi_getLL('admin_save');
		if ($_REQUEST['action']=='edit_product' && is_numeric($this->get['pid'])) {
			$subpartArray['###BUTTON_SAVE_AS_NEW###']='<input name="save_as_new" type="submit" value="'.$this->pi_getLL('admin_save_as_new').'" class="submit save_as_new" />';
			$subpartArray['###FOOTER_BUTTON_SAVE_AS_NEW###']='<input name="save_as_new" type="submit" value="'.$this->pi_getLL('admin_save_as_new').'" class="submit save_as_new" />';
		} else {
			$subpartArray['###BUTTON_SAVE_AS_NEW###']='';
			$subpartArray['###FOOTER_BUTTON_SAVE_AS_NEW###']='';
		}
		$subpartArray['###FOOTER_VALUE_ADVANCED_OPTION###']=($_COOKIE['hide_advanced_options']==1 ? $this->pi_getLL('admin_show_options') : $this->pi_getLL('admin_hide_options'));
		$subpartArray['###FOOTER_LABEL_BUTTON_CANCEL###']=$this->pi_getLL('admin_cancel');
		$subpartArray['###FOOTER_LABEL_BUTTON_SAVE###']=$this->pi_getLL('admin_save');
		$subpartArray['###PRODUCT_PID###']=$product['products_id'];
		$subpartArray['###FORM_ACTION_URL###']=mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]=admin_ajax&pid='.$this->get['pid']);
		if ($_COOKIE['hide_advanced_options']==1) {
			$subpartArray['###JS_ADVANCED_OPTION_TOGGLE###']='$(".toggle_advanced_option").hide();'."\n";
		} else {
			$subpartArray['###JS_ADVANCED_OPTION_TOGGLE###']='$(".toggle_advanced_option").show();'."\n";
		}
		$subpartArray['###LABEL_HEADING_EDIT_PRODUCT###']=$heading_page;
		$subpartArray['###LABEL_PRODUCT_STATUS###']=$this->pi_getLL('admin_visible');
		$subpartArray['###LABEL_PRODUCT_STATUS_ON_CHECKED###']=(($product['products_status'] or $_REQUEST['action']=='add_product') ? 'checked="checked"' : '');
		$subpartArray['###LABEL_ADMIN_YES###']=$this->pi_getLL('admin_yes');
		$subpartArray['###LABEL_PRODUCT_STATUS_OFF_CHECKED###']=((!$product['products_status'] and $_REQUEST['action']=='edit_product') ? 'checked="checked"' : '');
		$subpartArray['###LABEL_ADMIN_NO###']=$this->pi_getLL('admin_no');
		$subpartArray['###LABEL_PRODUCT_CATEGORY###']=$this->pi_getLL('admin_category');
		$subpartArray['###VALUE_OLD_CATEGORY_ID###']=$product['categories_id'];
		$subpartArray['###INPUT_CATEGORY_TREE###']=mslib_fe::tx_multishop_draw_pull_down_menu('categories_id" id="categories_id', mslib_fe::tx_multishop_get_category_tree('', '', ''), $this->get['cid'], 'class="select2BigDropWider"');
		$subpartArray['###DETAILS_CONTENT###']=$details_content;
		//exclude list products
		$feed_checkbox='';
		$feed_stock_checkbox='';
		$sql_feed='SELECT * from tx_multishop_product_feeds';
		$qry_feed=$GLOBALS['TYPO3_DB']->sql_query($sql_feed);
		while ($rs_feed=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_feed)) {
			if ($_REQUEST['action']=='edit_product') {
				$sql_check="select id from tx_multishop_feeds_excludelist where feed_id='".addslashes($rs_feed['id'])."' and exclude_id='".addslashes($product['products_id'])."' and exclude_type='products'";
				$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_check)) {
					$feed_checkbox.='<input type="checkbox" name="exclude_feed[]" value="'.$rs_feed['id'].'" checked="checked" />&nbsp;'.$rs_feed['name'].'&nbsp;';
				} else {
					$feed_checkbox.='<input type="checkbox" name="exclude_feed[]" value="'.$rs_feed['id'].'" />&nbsp;'.$rs_feed['name'].'&nbsp;';
				}
				$sql_stock_check="select id from tx_multishop_feeds_stock_excludelist where feed_id='".addslashes($rs_feed['id'])."' and exclude_id='".addslashes($product['products_id'])."' and exclude_type='products'";
				$qry_stock_check=$GLOBALS['TYPO3_DB']->sql_query($sql_stock_check);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_stock_check)) {
					$feed_stock_checkbox.='<input type="checkbox" name="exclude_stock_feed[]" value="'.$rs_feed['id'].'" checked="checked" />&nbsp;'.$rs_feed['name'].'&nbsp;';
				} else {
					$feed_stock_checkbox.='<input type="checkbox" name="exclude_stock_feed[]" value="'.$rs_feed['id'].'" />&nbsp;'.$rs_feed['name'].'&nbsp;';
				}
			} else {
				$feed_checkbox.='<input type="checkbox" name="exclude_feed[]" value="'.$rs_feed['id'].'" />&nbsp;'.$rs_feed['name'].'&nbsp;';
				$feed_stock_checkbox.='<input type="checkbox" name="exclude_stock_feed[]" value="'.$rs_feed['id'].'" />&nbsp;'.$rs_feed['name'].'&nbsp;';
			}
		}
		$subpartArray['###LABEL_EXCLUDE_FROM_FEED###']=$this->pi_getLL('exclude_from_feeds', 'Exclude from feeds');
		if (empty($feed_checkbox)) {
			$subpartArray['###FEEDS_LIST###']=$this->pi_getLL('admin_label_no_feeds');
		} else {
			$subpartArray['###FEEDS_LIST###']=$feed_checkbox;
		}
		$subpartArray['###LABEL_EXCLUDE_STOCK_FROM_FEED###']=$this->pi_getLL('exclude_stock_from_feeds', 'Exclude stock from feeds');
		if (empty($feed_stock_checkbox)) {
			$subpartArray['###STOCK_FEEDS_LIST###']=$this->pi_getLL('admin_label_no_feeds');
		} else {
			$subpartArray['###STOCK_FEEDS_LIST###']=$feed_stock_checkbox;
		}
		//exclude list products eol
		/*
		 * options tab marker
		 */
		if ($product['specials_start_date']==0 || empty($product['specials_start_date'])) {
			$product['specials_start_date_sys']='';
			$product['specials_start_date_visual']='';
		} else {
			$product['specials_start_date_visual']=date($this->pi_getLL('locale_date_format'), $product['specials_start_date']);
			$product['specials_start_date_sys']=date("Y-m-d", $product['specials_start_date']);
		}
		if ($product['specials_expired_date']==0 || empty($product['specials_expired_date'])) {
			$product['specials_expired_date_sys']='';
			$product['specials_expired_date_visual']='';
		} else {
			$product['specials_expired_date_visual']=date($this->pi_getLL('locale_date_format'), $product['specials_expired_date']);
			$product['specials_expired_date_sys']=date("Y-m-d", $product['specials_expired_date']);
		}
		$subpartArray['###LABEL_HEADING_TAB_OPTION###']=$this->pi_getLL('admin_product_options');
		$subpartArray['###LABEL_VAT_RATE###']=$this->pi_getLL('admin_vat_rate');
		$subpartArray['###INPUT_VATE_RATE###']=$input_vat_rate;
		$subpartArray['###LABEL_PRICE###']=t3lib_div::strtoupper($this->pi_getLL('admin_price'));
		$subpartArray['###LABEL_NORMAL_PRICE###']=t3lib_div::strtoupper($this->pi_getLL('admin_normal_price'));
		$subpartArray['###LABEL_CURRENCY0###']=mslib_fe::currency();
		$subpartArray['###LABEL_CURRENCY1###']=mslib_fe::currency();
		$subpartArray['###LABEL_CURRENCY2###']=mslib_fe::currency();
		$subpartArray['###LABEL_CURRENCY3###']=mslib_fe::currency();
		$subpartArray['###LABEL_CURRENCY4###']=mslib_fe::currency();
		$subpartArray['###LABEL_CURRENCY5###']=mslib_fe::currency();
		$subpartArray['###VALUE_EXCL_VAT_PRICE###']=htmlspecialchars($price_excl_vat_display);
		$subpartArray['###VALUE_INCL_VAT_PRICE###']=htmlspecialchars($price_incl_vat_display);
		$subpartArray['###VALUE_ORIGINAL_PRICE###']=htmlspecialchars($product['products_price']);
		$subpartArray['###LABEL_SPECIAL_PRICE###']=t3lib_div::strtoupper($this->pi_getLL('admin_specials_price'));
		$subpartArray['###VALUE_EXCL_VAT_SPECIAL_PRICE###']=htmlspecialchars($special_price_excl_vat_display);
		$subpartArray['###VALUE_INCL_VAT_SPECIAL_PRICE###']=htmlspecialchars($special_price_incl_vat_display);
		$subpartArray['###VALUE_ORIGINAL_SPECIAL_PRICE###']=htmlspecialchars($product['specials_new_products_price']);
		$subpartArray['###LABEL_SPECIAL_PRICE_START###']=t3lib_div::strtoupper($this->pi_getLL('special_price_start'));
		$subpartArray['###VALUE_SPECIAL_PRICE_START_VISUAL###']=$product['specials_start_date_visual'];
		$subpartArray['###VALUE_SPECIAL_PRICE_START_SYS###']=$product['specials_start_date_sys'];
		$subpartArray['###LABEL_SPECIAL_PRICE_EXPIRED###']=t3lib_div::strtoupper($this->pi_getLL('special_price_expired'));
		$subpartArray['###VALUE_SPECIAL_PRICE_EXPIRED_VISUAL###']=$product['specials_expired_date_visual'];
		$subpartArray['###VALUE_SPECIAL_PRICE_EXPIRED_SYS###']=$product['specials_expired_date_sys'];
		$subpartArray['###LABEL_CAPITAL_PRICE###']=$this->pi_getLL('capital_price');
		$subpartArray['###VALUE_EXCL_VAT_CAPITAL_PRICE###']=htmlspecialchars($capital_price_excl_vat_display);
		$subpartArray['###VALUE_INCL_VAT_CAPITAL_PRICE###']=htmlspecialchars($capital_price_incl_vat_display);
		$subpartArray['###VALUE_ORIGINAL_CAPITAL_PRICE###']=htmlspecialchars($product['product_capital_price']);
		$subpartArray['###INPUT_STAFFEL_PRICE_BLOCK###']=$staffel_price_block;
		$subpartArray['###LABEL_STOCK###']=t3lib_div::strtoupper($this->pi_getLL('admin_stock'));
		$subpartArray['###VALUE_STOCK###']=$product['products_quantity'];
		$subpartArray['###LABEL_THRESHOLD_QTY###']=t3lib_div::strtoupper($this->pi_getLL('admin_alert_quantity_threshold', 'Alert stock threshold'));
		$subpartArray['###VALUE_THRESHOLD_QTY###']=$product['alert_quantity_threshold'];
		$subpartArray['###LABEL_DATE_AVAILABLE###']=t3lib_div::strtoupper($this->pi_getLL('products_date_available'));
		if ($product['products_date_available']==0 || empty($product['products_date_available'])) {
			$product['products_date_available_sys']='';
			$product['products_date_available_visual']='';
		} else {
			$product['products_date_available_visual']=date($this->pi_getLL('locale_date_format'), $product['products_date_available']);
			$product['products_date_available_sys']=date("Y-m-d", $product['products_date_available']);
		}
		if ($product['products_date_added']==0 || empty($product['products_date_added'])) {
			$product['products_date_added_sys']='';
			$product['products_date_added_visual']='';
		} else {
			$product['products_date_added_visual']=date($this->pi_getLL('locale_date_format'), $product['products_date_added']);
			$product['products_date_added_sys']=date("Y-m-d", $product['products_date_added']);
		}
		if ($product['starttime']==0) {
			$product['endtime_sys']='';
			$product['endtime_visual']='';
		} else {
			$product['starttime_visual']=date($this->pi_getLL('locale_datetime_format'), $product['starttime']);
			$product['starttime_sys']=date("Y-m-d H:i:s", $product['starttime']);
		}
		if ($product['endtime']==0) {
			$product['endtime_sys']='';
			$product['endtime_visual']='';
		} else {
			$product['endtime_visual']=date($this->pi_getLL('locale_datetime_format'), $product['endtime']);
			$product['endtime_sys']=date("Y-m-d H:i:s", $product['endtime']);
		}
		$subpartArray['###VALUE_DATE_AVAILABLE_VISUAL###']=$product['products_date_available_visual'];
		$subpartArray['###VALUE_DATE_AVAILABLE_SYS###']=$product['products_date_available_sys'];
		$subpartArray['###VALUE_STARTTIME_VISUAL###']=$product['starttime_visual'];
		$subpartArray['###VALUE_STARTTIME_SYS###']=$product['starttime_sys'];
		$subpartArray['###VALUE_ENDTIME_VISUAL###']=$product['endtime_visual'];
		$subpartArray['###VALUE_ENDTIME_SYS###']=$product['endtime_sys'];
		$subpartArray['###LABEL_DATE_ADDED###']=t3lib_div::strtoupper($this->pi_getLL('date_added'));
		$subpartArray['###VALUE_DATE_ADDED_VISUAL###']=$product['products_date_added_visual'];
		$subpartArray['###VALUE_DATE_ADDED_SYS###']=$product['products_date_added_sys'];
		$subpartArray['###LABEL_PRODUCT_MODEL###']=$this->pi_getLL('admin_model');
		$subpartArray['###VALUE_PRODUCT_MODEL###']=htmlspecialchars($product['products_model']);
		$subpartArray['###LABEL_PRODUCT_MANUFACTURER###']=$this->pi_getLL('admin_manufacturer');
		$subpartArray['###INPUT_MANUFACTURER###']=$manufacturer_input;
		$subpartArray['###LABEL_ADD_NEW_MANUFACTURER###']=$this->pi_getLL('admin_or_add_a_new_manufacturer');
		$subpartArray['###LABEL_PRODUCT_WEIGHT###']=$this->pi_getLL('admin_weight');
		$subpartArray['###VALUE_PRODUCT_WEIGHT###']=htmlspecialchars($product['products_weight']);
		$subpartArray['###LABEL_PRODUCT_CONDITION###']=$this->pi_getLL('admin_condition');
		$subpartArray['###CONDITION_NEW_SELECTED###']=($product['products_condition']=='new' ? ' selected' : '');
		$subpartArray['###CONDITION_USED_SELECTED###']=($product['products_condition']=='used' ? ' selected' : '');
		$subpartArray['###CONDITION_REFURBISHED_SELECTED###']=($product['products_condition']=='refurbished' ? ' selected' : '');
		$subpartArray['###LABEL_CONDITION_NEW###']=$this->pi_getLL('new');
		$subpartArray['###LABEL_CONDITION_USED###']=$this->pi_getLL('used');
		$subpartArray['###LABEL_CONDITION_REFURBISHED###']=$this->pi_getLL('refurbished');
		$subpartArray['###LABEL_EAN_CODE###']=$this->pi_getLL('admin_ean_code');
		$subpartArray['###VALUE_EAN_CODE###']=htmlspecialchars($product['ean_code']);
		$subpartArray['###LABEL_SKU_CODE###']=$this->pi_getLL('admin_sku_code');
		$subpartArray['###VALUE_SKU_CODE###']=htmlspecialchars($product['sku_code']);
		$subpartArray['###LABEL_MANUFACTURER_CODE###']=$this->pi_getLL('admin_manufacturers_products_id');
		$subpartArray['###VALUE_MANUFACTURER_CODE###']=htmlspecialchars($product['vendor_code']);
		$subpartArray['###LABEL_PRODUCT_UNIT###']=$this->pi_getLL('admin_product_units', 'PRODUCT UNITS');
		$subpartArray['###LABEL_ORDER_UNIT###']=$this->pi_getLL('admin_order_unit', 'Order Unit');
		$subpartArray['###INPUT_PRODUCT_UNIT###']=$order_unit;
		$subpartArray['###LABEL_MINIMUM_QTY###']=$this->pi_getLL('admin_minimum_quantity');
		$subpartArray['###VALUE_MINIMUM_QTY###']=(isset($product['minimum_quantity']) ? $product['minimum_quantity'] : '1');
		$subpartArray['###LABEL_MAXIMUM_QTY###']=$this->pi_getLL('admin_maximum_quantity');
		$subpartArray['###VALUE_MAXIMUM_QTY###']=($product['maximum_quantity'] ? $product['maximum_quantity'] : '');
		$subpartArray['###LABEL_QTY_MULTIPLICATION###']=$this->pi_getLL('admin_quantity_multiplication');
		$subpartArray['###VALUE_QTY_MULTIPLICATION###']=($product['products_multiplication'] ? $product['products_multiplication'] : '');
		$subpartArray['###LABEL_VIRTUAL_PRODUCT###']=$this->pi_getLL('admin_virtual_product', 'Virtual Product');
		$subpartArray['###LABEL_FILE_NUMBER_OF_DOWNLOADS###']=$this->pi_getLL('file_number_of_downloads', 'NUMBER OF DOWNLOADS');
		$subpartArray['###VALUE_FILE_NUMBER_OF_DOWNLOADS###']=($product['file_number_of_downloads'] ? $product['file_number_of_downloads'] : '');
		$subpartArray['###INPUT_EDIT_SHIPPING_AND_PAYMENT_METHOD###']=$shipping_payment_method;
		$subpartArray['###VALUE_PRODUCT_PID0###']=$product['products_id'];
		$subpartArray['###VALUE_PRODUCT_PID1###']=$product['products_id'];
		$subpartArray['###VALUE_HIDDEN_FORM_ACTION###']=$_REQUEST['action'];
		$subpartArray['###LABEL_ADVANCED_SETTINGS###']=$this->pi_getLL('admin_advanced_settings');
		$subpartArray['###LABEL_CUSTOM_CONFIGURATION###']=$this->pi_getLL('admin_custom_configuration');
		$subpartArray['###VALUE_CUSTOM_CONFIGURATION###']=htmlspecialchars($product['custom_settings']);
		$subpartArray['###OPTIONS_TAB_VIRTUAL_PRODUCT###']=$options_tab_virtual_product;
		/*
		 * images tab marker
		 */
		$subpartArray['###LABEL_HEADING_TAB_IMAGES###']=$this->pi_getLL('admin_product_images');
		$subpartArray['###INPUT_IMAGES_BLOCK###']=$images_tab_block;
		/*
		 * meta tags tab marker
		*/
		$subpartArray['###LABEL_HEADING_TAB_META_TAGS###']='META TAGS';
		$subpartArray['###INPUT_META_TAGS_BLOCK###']=$meta_tags_block;
		/*
		 * attributes tab marker
		*/
		$subpartArray['###INPUT_ATTRIBUTES_BLOCK###']=$attributes_tab_block;
		/*
		 * product relatives tab marker
		*/
		$subpartArray['###INPUT_PRODUCT_RELATIVES_BLOCK###']=$product_relatives_block;
		/*
		 * product copy tab marker
		*/
		$subpartArray['###INPUT_PRODUCT_COPY_BLOCK###']=$product_copy_block;
		// plugin marker place holder
		$plugins_extra_tab=array();
		$plugins_extra_tab['tabs_header']=array();
		$plugins_extra_tab['tabs_content']=array();
		// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['adminEditProductPreProc'])) {
			$params=array(
				'subpartArray'=>&$subpartArray,
				'product'=>&$product,
				'plugins_extra_tab'=>&$plugins_extra_tab
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_product.php']['adminEditProductPreProc'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		// custom page hook that can be controlled by third-party plugin eof
		if (!count($plugins_extra_tab['tabs_header']) && !count($plugins_extra_tab['tabs_content'])) {
			$subpartArray['###LABEL_EXTRA_PLUGIN_TABS###']='';
			$subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###']='';
		} else {
			$subpartArray['###LABEL_EXTRA_PLUGIN_TABS###']=implode("\n", $plugins_extra_tab['tabs_header']);
			$subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###']=implode("\n", $plugins_extra_tab['tabs_content']);
		}
		$subpartArray['###ADMIN_LABEL_JS_PLEASE_SELECT_CATEGORY_FOR_THIS_PRODUCT###']=$this->pi_getLL('admin_label_js_please_select_category_for_this_product');
		$subpartArray['###ADMIN_LABEL_JS_PRODUCT_NAME_IS_EMPTY###']=$this->pi_getLL('admin_label_js_product_name_is_empty');
		$subpartArray['###ADMIN_LABEL_JS_DEFINE_PRODUCT_NAME_FIRST_IN_DETAILS_TABS###']=$this->pi_getLL('admin_label_js_define_product_name_first_in_details_tabs');
		$subpartArray['###ADMIN_LABEL_PRODUCT_NOT_LOADED_SORRY_WE_CANT_FIND_IT###']=$this->pi_getLL('admin_label_product_not_loaded_sorry_we_cant_find_it');
		$content.=$this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
	} else {
		$content.=$this->pi_getLL('admin_label_product_not_loaded_sorry_we_cant_find_it');
	}
}
?>