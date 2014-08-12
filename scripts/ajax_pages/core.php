<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$this->ms['page']=$this->get['tx_multishop_pi1']['page_section'];
switch ($this->ms['page']) {
	case 'sort_specials_sections':
		if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
			$no=1;
			foreach ($this->post['specialssections'] as $special_id) {
				if (is_numeric($special_id)) {
					$where="specials_id = ".$special_id." and name='".addslashes($this->get['tx_multishop_pi1']['sort_specials_sections'])."'";
					$updateArray=array(
						'sort_order'=>$no
					);
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_specials_sections', $where, $updateArray);


					var_dump($query);

					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$no++;
				}
			}
		}
		exit();
		break;
	case 'delete_options_group':
		if ($this->ADMIN_USER) {
			if (isset($this->post['tx_multishop_pi1']['group_id']) && $this->post['tx_multishop_pi1']['group_id'] > 0) {
				$group_id=$this->post['tx_multishop_pi1']['group_id'];
				$qry=$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_attributes_options_groups', 'attributes_options_groups_id='.$group_id);
				if ($qry) {
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_attributes_options_groups_to_products_options', 'attributes_options_groups_id='.$group_id);
					$data=array();
					$data['result']='OK';
					echo json_encode($data);
					exit();
				}
			}
		}
		exit();
		break;
	case 'admin_categories_sorting':
		if ($this->ADMIN_USER) {
			$no=1;
			foreach ($this->post['categories_id'] as $catid) {
				if (is_numeric($catid)) {
					$where="categories_id = ".$catid;
					$updateArray=array(
						'sort_order'=>$no
					);
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$no++;
				}
			}
		}
		exit();
		break;
	// attributes options and values editors
	case 'admin_ajax_attributes_options_values':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_ajax_attributes_options_values.php');
		}
		break;
	// attributes options values related to products
	case 'admin_ajax_product_attributes':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_ajax_product_attributes.php');
		}
		exit();
		break;
	case 'admin_ajax_edit_order':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_ajax_edit_order.php');
		}
		exit();
		break;
	case 'downloadCategoryTree':
		if ($this->ADMIN_USER) {
			$multishop_category_array=array();
			$query2=$GLOBALS['TYPO3_DB']->SELECTquery('cd.categories_name, c.categories_id, c.parent_id', // SELECT ...
				'tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
				'c.parent_id =0 and c.status=1 and c.categories_id=cd.categories_id', // WHERE...
				'', // GROUP BY...
				'cd.categories_name', // ORDER BY...
				'' // LIMIT ...
			);
			$res2=$GLOBALS['TYPO3_DB']->sql_query($query2);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res2)>0) {
				while ($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
					$query3=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
						'tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
						'c.parent_id=\''.$row2['categories_id'].'\' and c.status=1 and c.categories_id=cd.categories_id', // WHERE...
						'', // GROUP BY...
						'cd.categories_name', // ORDER BY...
						'' // LIMIT ...
					);
					$res3=$GLOBALS['TYPO3_DB']->sql_query($query3);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($res3)>0) {
						while (($row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3))) {
							$multishop_category_array[]=array(
								'categoryTree'=>$row2['categories_name'].' / '.$row3['categories_name'],
								'mainCatID'=>$row2['categories_id'],
								'mainCatName'=>$row2['categories_name'],
								'subCatID'=>$row3['categories_id'],
								'subCatName'=>$row3['categories_name']
							);
						}
					}
				}
				$xml_string=t3lib_div::array2xml_cs($multishop_category_array);
				echo $xml_string;
				exit();
			}
		}
		exit();
		break;
	case 'getAdminOrdersListingDetails':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/get_admin_orders_listing_details.php');
		}
		exit();
		break;
	case 'retrieveAdminNotificationMessage':
		if ($this->ADMIN_USER) {
			$startTime=(time()-(60));
			$str="SELECT id, title, message, customer_id, crdate from tx_multishop_notification where unread=1 and crdate > ".$startTime." limit 2";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			$messages=array();
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
				$row['crdate']=strftime("%x %X", $row['crdate']);
				$messages[]=$row;
				// update status to read
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_notification', 'id='.$row['id'], array('unread'=>'0'));
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
			echo json_encode($messages, ENT_NOQUOTES);
		}
		exit();
		break;
	case 'admin_update_orders_status':
		if ($this->ADMIN_USER) {
			if (is_numeric($this->post['tx_multishop_pi1']['orders_id']) and is_numeric($this->post['tx_multishop_pi1']['orders_status_id'])) {
				// hook
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUpdateOrdersStatus'])) {
					$params=array(
						'orders_id'=>&$this->post['tx_multishop_pi1']['orders_id'],
						'orders_status_id'=>$this->post['tx_multishop_pi1']['orders_status_id']
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUpdateOrdersStatus'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
				}
				// hook eof
				mslib_befe::updateOrderStatus($this->post['tx_multishop_pi1']['orders_id'], $this->post['tx_multishop_pi1']['orders_status_id'], 1);
			}
		}
		exit();
		break;
	case 'admin_update_order_product_status':
		if ($this->ADMIN_USER) {
			if (is_numeric($this->post['tx_multishop_pi1']['orders_id']) and is_numeric($this->post['tx_multishop_pi1']['order_product_id']) and is_numeric($this->post['tx_multishop_pi1']['orders_status_id'])) {
				mslib_befe::updateOrderProductStatus($this->post['tx_multishop_pi1']['orders_id'], $this->post['tx_multishop_pi1']['order_product_id'], $this->post['tx_multishop_pi1']['orders_status_id']);
			}
		}
		exit();
		break;
	case 'update_currency':
		// change selected currency + exchange rate and save it in temporary session
		if ($this->post['tx_multishop_pi1']['selected_currency']) {
			$this->cookie['selected_currency']=$this->post['tx_multishop_pi1']['selected_currency'];
			$this->cookie['currency_rate']=mslib_fe::currencyConverter($this->ms['MODULES']['CURRENCY_ARRAY']['cu_iso_3'], $this->cookie['selected_currency'], 1);
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
			$GLOBALS['TSFE']->storeSessionData();
		}
		exit();
		break;
	case 'generateBarkode':
//		if ($this->ADMIN_USER)
//		{
		if ($this->get['tx_multishop_pi1']['string']) {
			// hook
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['generateBarkode'])) {
				$params=array(
					'this'=>&$this,
					'get'=>&$this->get,
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['generateBarkode'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
			// hook oef
			require($this->DOCUMENT_ROOT_MS.'res/barcode-coder/php-barcode-2.0.1.php');
			$font=$this->DOCUMENT_ROOT_MS.'res/barcode-coder/code39.ttf';
			// download a ttf font here for example : http://www.dafont.com/fr/nottke.font
			//$font     = './NOTTB___.TTF';
			// - -
			$canvas_width=200;
			$canvas_height=75;
			$fontSize=9; // GD1 in px ; GD2 in point
			$marge=10; // between barcode and hri in pixel
			$x=100; // barcode center
			$y=40; // barcode center
			$height=50; // barcode height in 1D ; module size in 2D
			$width=1; // barcode width in 1D ; not use in 2D
			$angle=0; // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
			$code=$this->get['tx_multishop_pi1']['string']; // barcode, of course ;)
			$type='code39';
			/*
				 *    standard 2 of 5 (std25)
				 *    interleaved 2 of 5 (int25)
				 *    ean 8 (ean8)
				 *    ean 13 (ean13)
				 *    code 11 (code11)
				 *    code 39 (code39)
				 *    code 93 (code93)
				 *    code 128 (code128)
				 *    codabar (codabar)
				 *    msi (msi)
				 *    datamatrix (datamatrix)
			*/
			// -------------------------------------------------- //
			//                    USEFUL
			// -------------------------------------------------- //
			function drawCross($im, $color, $x, $y) {
				imageline($im, $x-10, $y, $x+10, $y, $color);
				imageline($im, $x, $y-10, $x, $y+10, $color);
			}

			// -------------------------------------------------- //
			//            ALLOCATE GD RESOURCE
			// -------------------------------------------------- //
			$im=imagecreatetruecolor($canvas_width, $canvas_height);
			$black=ImageColorAllocate($im, 0x00, 0x00, 0x00);
			$white=ImageColorAllocate($im, 0xff, 0xff, 0xff);
			$red=ImageColorAllocate($im, 0xff, 0x00, 0x00);
			$blue=ImageColorAllocate($im, 0x00, 0x00, 0xff);
			imagefilledrectangle($im, 0, 0, 300, 300, $white);
			// -------------------------------------------------- //
			//                      BARCODE
			// -------------------------------------------------- //
			$data=Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
			// -------------------------------------------------- //
			//                        HRI
			// -------------------------------------------------- //
			/*
							if ( isset($font) ){
							$box = imagettfbbox($fontSize, 0, $font, $data['hri']);
							$len = $box[2] - $box[0];
							Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
							imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
							}
			*/
			// -------------------------------------------------- //
			//                     ROTATE
			// -------------------------------------------------- //
			// Beware ! the rotate function should be use only with right angle
			// Remove the comment below to see a non right rotation
			/** /
			 * $rot = imagerotate($im, 45, $white);
			 * imagedestroy($im);
			 * $im     = imagecreatetruecolor(900, 300);
			 * $black  = ImageColorAllocate($im,0x00,0x00,0x00);
			 * $white  = ImageColorAllocate($im,0xff,0xff,0xff);
			 * $red    = ImageColorAllocate($im,0xff,0x00,0x00);
			 * $blue   = ImageColorAllocate($im,0x00,0x00,0xff);
			 * imagefilledrectangle($im, 0, 0, 900, 300, $white);
			 * // Barcode rotation : 90�
			 * $angle = 90;
			 * $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
			 * Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
			 * imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
			 * imagettftext($im, 10, 0, 60, 290, $black, $font, 'BARCODE ROTATION : 90�');
			 * // barcode rotation : 135
			 * $angle = 135;
			 * Barcode::gd($im, $black, $x+300, $y, $angle, $type, array('code'=>$code), $width, $height);
			 * Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
			 * imagettftext($im, $fontSize, $angle, $x + 300 + $xt, $y + $yt, $blue, $font, $data['hri']);
			 * imagettftext($im, 10, 0, 360, 290, $black, $font, 'BARCODE ROTATION : 135�');
			 * // last one : image rotation
			 * imagecopy($im, $rot, 580, -50, 0, 0, 300, 300);
			 * imagerectangle($im, 0, 0, 299, 299, $black);
			 * imagerectangle($im, 299, 0, 599, 299, $black);
			 * imagerectangle($im, 599, 0, 899, 299, $black);
			 * imagettftext($im, 10, 0, 690, 290, $black, $font, 'IMAGE ROTATION');
			 * /**/
			// -------------------------------------------------- //
			//                    MIDDLE AXE
			// -------------------------------------------------- //
//				imageline($im, $x, 0, $x, 250, $red);
//				imageline($im, 0, $y, 250, $y, $red);
			// -------------------------------------------------- //
			//                  BARCODE BOUNDARIES
			// -------------------------------------------------- //
//				for($i=1; $i<5; $i++){
//				drawCross($im, $blue, $data['p'.$i]['x'], $data['p'.$i]['y']);
//				}
			// -------------------------------------------------- //
			//                    GENERATE
			// -------------------------------------------------- //
			header('Content-type: image/gif');
			imagegif($im);
			imagedestroy($im);
			exit();
		}
//		}
		break;
	case 'psp':
		if ($_REQUEST['tx_multishop_pi1']['payment_lib']) {
			$mslib_payment=t3lib_div::makeInstance('mslib_payment');
			$mslib_payment->init($this);
//			$payment_methods=$mslib_payment->getInstalledPaymentMethods($this);
			if ($mslib_payment->setPaymentMethod($_REQUEST['tx_multishop_pi1']['payment_lib'])) {
				// psp installed and is activated
				$extkey='multishop_'.$_REQUEST['tx_multishop_pi1']['payment_lib'];
				if (t3lib_extMgm::isLoaded($extkey)) {
					require(t3lib_extMgm::extPath($extkey).'class.multishop_payment_method.php');
					$paymentMethod=t3lib_div::makeInstance('tx_multishop_payment_method');
					$paymentMethod->init($this);
					$paymentMethod->paymentNotificationHandler();
				}
			} else {
//				error_log("no");
			}
		}
		exit();
		break;
	case 'admin_panel':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_panel.php');
		exit();
		break;
	case 'get_method_costs':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/get_method_costs.php');
		exit();
		break;
	case 'get_country_payment_methods':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/get_country_payment_methods.php');
		exit();
		break;
	case 'confirm_create_account':
		if ($this->get['tx_multishop_pi1']['hash']) {
			// hook
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['confirm_create_account'])) {
				$params=array('content'=>&$content);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['confirm_create_account'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			} else {
				require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/confirm_create_account.php');
			}
		}
		exit();
		break;
	case 'download_invoice':
		if ($this->get['tx_multishop_pi1']['hash']) {
			if (strstr($this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'], "..")) {
				die('error in DOWNLOAD_INVOICE_TYPE value');
			} else {
				if (strstr($this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'], "/")) {
					// relative mode
					require($this->DOCUMENT_ROOT.$this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'].'.php');
				} else {
					require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/download_invoice_b2c.php');
				}
			}
		}
		exit();
		break;
	case 'download_product_feed':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/download_product_feed.php');
		exit();
		break;
	case 'admin_ajax_upload':
		if ($this->ADMIN_USER) {
			if (isset($_SERVER["CONTENT_LENGTH"])) {
				switch ($this->get['file_type']) {
					case 'fe_user_image':
						$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
						if (isset($_FILES['qqfile'])) {
							move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
						} else {
							$input=fopen("php://input", "r");
							$temp=tmpfile();
							$realSize=stream_copy_to_stream($input, $temp);
							fclose($input);
							$target=fopen($temp_file, "w");
							fseek($temp, 0, SEEK_SET);
							stream_copy_to_stream($temp, $target);
							fclose($target);
						}
						$size=getimagesize($temp_file);
						if ($size[0]>5 and $size[1]>5) {
							$imgtype=mslib_befe::exif_imagetype($temp_file);
							if ($imgtype) {
								// valid image
								$ext=image_type_to_extension($imgtype, false);
								if ($ext) {
									$i=0;
									//$filename=mslib_fe::rewritenamein($this->get['products_name']).'.'.$ext;
									$name=md5(time());
									$filename=$name.'.'.$ext;
									$targetFolder=$this->DOCUMENT_ROOT.'uploads/pics/';
									$target=$targetFolder.$filename;
									if (file_exists($target)) {
										do {
											$filename=$name.($i>0 ? '-'.$i : '').'.'.$ext;
											$target=$targetFolder.$filename;
											$i++;
										} while (file_exists($target));
									}
									if (copy($temp_file, $target)) {
										//$filename=mslib_befe::resizeProductImage($target,$filename,$this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey),1);
										copy($temp_file, $target);
										if (is_numeric($this->get['tx_multishop_pi1']['uid'])) {
											$updateArray=array(
												'image'=>$filename
											);
											$query=$GLOBALS['TYPO3_DB']->UPDATEquery('fe_users', 'uid='.$this->get['tx_multishop_pi1']['uid'], $updateArray);
											$res=$GLOBALS['TYPO3_DB']->sql_query($query);
										}
										$result=array();
										$result['success']=true;
										$result['error']=false;
										$result['filename']=$filename;
										echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
										exit();
									}
								}
							}
						}
						break;
				}
			}
		}
		exit();
		break;
	case 'admin_upload_redactor':
		if ($this->ADMIN_USER) {
			$continueUpload=0;
			switch ($this->get['tx_multishop_pi1']['redactorType']) {
				case 'imageGetJson':
					$fileUploadPathRelative='uploads/tx_multishop/images/cmsimages';
					$fileUploadPathAbsolute=$this->DOCUMENT_ROOT.$fileUploadPathRelative;
					if (is_dir($fileUploadPathAbsolute)) {
						$items=t3lib_div::getAllFilesAndFoldersInPath(array(), $fileUploadPathAbsolute.'/');
						if (count($items)) {
							$array=array();
							foreach ($items as $item) {
								$path_parts=pathinfo($item);
								$file=array();
								$file['title']=$path_parts['filename'];
								$file['thumb']=str_replace($this->DOCUMENT_ROOT, '', $item);
								$file['image']=$file['thumb'];
								$file['folder']=str_replace($fileUploadPathAbsolute, '', $path_parts['dirname']);
								$array[]=$file;
							}
							echo htmlspecialchars(json_encode($array), ENT_NOQUOTES);
						}
					}
					exit();
					break;
				case 'clipboardUploadUrl':
					if ($this->post['contentType'] and $this->post['data']) {
						switch ($this->post['contentType']) {
							case 'image/png':
							case 'image/jpg':
							case 'image/gif':
							case 'image/jpeg':
							case 'image/pjpeg':
								$fileUploadPathRelative='uploads/tx_multishop/images/cmsimages';
								$fileUploadPathAbsolute=$this->DOCUMENT_ROOT.$fileUploadPathRelative;
								$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
								file_put_contents($temp_file, base64_decode($this->post['data']));
								if (file_exists($temp_file)) {
									$size=getimagesize($temp_file);
									if ($size[0]>5 and $size[1]>5) {
										$imgtype=mslib_befe::exif_imagetype($temp_file);
										if ($imgtype) {
											// valid image
											$ext=image_type_to_extension($imgtype, false);
											if ($ext) {
												$continueUpload=1;
											}
										}
									}
								}
								break;
						}
					}
					break;
				case 'imageUpload':
					$_FILES['file']['type']=strtolower($_FILES['file']['type']);
					switch ($_FILES['file']['type']) {
						case 'image/png':
						case 'image/jpg':
						case 'image/gif':
						case 'image/jpeg':
						case 'image/pjpeg':
							$fileUploadPathRelative='uploads/tx_multishop/images/cmsimages';
							$fileUploadPathAbsolute=$this->DOCUMENT_ROOT.$fileUploadPathRelative;
							$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
							move_uploaded_file($_FILES['file']['tmp_name'], $temp_file);
							$size=getimagesize($temp_file);
							if ($size[0]>5 and $size[1]>5) {
								$imgtype=mslib_befe::exif_imagetype($temp_file);
								if ($imgtype) {
									// valid image
									$ext=image_type_to_extension($imgtype, false);
									if ($ext) {
										$continueUpload=1;
									}
								}
							}
							break;
					}
					break;
				case 'fileUpload':
					$fileUploadPathRelative='uploads/tx_multishop/images/cmsfiles';
					$fileUploadPathAbsolute=$this->DOCUMENT_ROOT.$fileUploadPathRelative;
					$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
					move_uploaded_file($_FILES['file']['tmp_name'], $temp_file);
					$path_parts=pathinfo($_FILES["file"]["name"]);
					$ext=$path_parts['extension'];
					if ($ext) {
						$continueUpload=1;
					}
					break;
			}
			if ($continueUpload) {
				if (!$this->get['tx_multishop_pi1']['title']) {
					$this->get['tx_multishop_pi1']['title']=uniqid();
				}
				$i=0;
				$filename=mslib_fe::rewritenamein($this->get['tx_multishop_pi1']['title']).'.'.$ext;
				$target=$fileUploadPathAbsolute.'/'.$filename;
				if (file_exists($target)) {
					do {
						$filename=mslib_fe::rewritenamein($this->get['tx_multishop_pi1']['title']).($i>0 ? '-'.$i : '').'.'.$ext;
						$target=$fileUploadPathAbsolute.'/'.$filename;
						$i++;
					} while (file_exists($target));
				}
				if (copy($temp_file, $target)) {
					$fileLocation=$this->FULL_HTTP_URL.$fileUploadPathRelative.'/'.$filename;
					$result=array(
						'filelink'=>$fileLocation
					);
					echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
					exit();
				}
			}
		}
		exit();
		break;
	case 'admin_upload_product_images':
		if ($this->ADMIN_USER) {
			if (isset($_SERVER["CONTENT_LENGTH"])) {
				switch ($this->get['file_type']) {
					case 'categories_image':
						$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
						if (isset($_FILES['qqfile'])) {
							move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
						} else {
							$input=fopen("php://input", "r");
							$temp=tmpfile();
							$realSize=stream_copy_to_stream($input, $temp);
							fclose($input);
							$target=fopen($temp_file, "w");
							fseek($temp, 0, SEEK_SET);
							stream_copy_to_stream($temp, $target);
							fclose($target);
						}
						$size=getimagesize($temp_file);
						if ($size[0]>5 and $size[1]>5) {
							$imgtype=mslib_befe::exif_imagetype($temp_file);
							if ($imgtype) {
								// valid image
								$ext=image_type_to_extension($imgtype, false);
								if ($ext) {
									$i=0;
									$filename=mslib_fe::rewritenamein($this->get['categories_name']).'.'.$ext;
									$folder=mslib_befe::getImagePrefixFolder($filename);
									$array=explode(".", $filename);
									if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder)) {
										t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder);
									}
									$folder.='/';
									$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder.$filename;
									if (file_exists($target)) {
										do {
											$filename=mslib_fe::rewritenamein($this->get['categories_name']).($i>0 ? '-'.$i : '').'.'.$ext;
											$folder_name=mslib_befe::getImagePrefixFolder($filename);
											$array=explode(".", $filename);
											$folder=$folder_name;
											if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder)) {
												t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder);
											}
											$folder.='/';
											$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder.$filename;
											$i++;
										} while (file_exists($target));
									}
									if (copy($temp_file, $target)) {
										$filename=mslib_befe::resizeCategoryImage($target, $filename, $this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey), 1);
//												error_log('bass'.print_r($this->ds,1));
										$fileLocation=$this->FULL_HTTP_URL.mslib_befe::getImagePath($filename, 'profile_images', '100');
//										error_log($fileLocation);
										$result=array();
										$result['success']=true;
										$result['error']=false;
										$result['filename']=$filename;
										$result['fileLocation']=$fileLocation;
										echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
										exit();
									}
								}
							}
						}
						break;
					case 'manufacturers_images':
						$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
						if (isset($_FILES['qqfile'])) {
							move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
						} else {
							$input=fopen("php://input", "r");
							$temp=tmpfile();
							$realSize=stream_copy_to_stream($input, $temp);
							fclose($input);
							$target=fopen($temp_file, "w");
							fseek($temp, 0, SEEK_SET);
							stream_copy_to_stream($temp, $target);
							fclose($target);
						}
						$size=getimagesize($temp_file);
						if ($size[0]>5 and $size[1]>5) {
							$imgtype=mslib_befe::exif_imagetype($temp_file);
							if ($imgtype) {
								// valid image
								$ext=image_type_to_extension($imgtype, false);
								if ($ext) {
									$i=0;
									$filename=mslib_fe::rewritenamein($this->get['manufacturers_name']).'.'.$ext;
									$folder=mslib_befe::getImagePrefixFolder($filename);
									$array=explode(".", $filename);
									if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder)) {
										t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder);
									}
									$folder.='/';
									$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder.$filename;
									if (file_exists($target)) {
										do {
											$filename=mslib_fe::rewritenamein($this->get['manufacturers_name']).($i>0 ? '-'.$i : '').'.'.$ext;
											$folder_name=mslib_befe::getImagePrefixFolder($filename);
											$array=explode(".", $filename);
											$folder=$folder_name;
											if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder)) {
												t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder);
											}
											$folder.='/';
											$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['manufacturers']['original'].'/'.$folder.$filename;
											$i++;
										} while (file_exists($target));
									}
									if (copy($temp_file, $target)) {
										$filename=mslib_befe::resizeManufacturerImage($target, $filename, $this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey), 1);
										$result=array();
										$result['success']=true;
										$result['error']=false;
										$result['filename']=$filename;
										echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
										exit();
									}
								}
							}
						}
						break;
					default:
						for ($x=0; $x<$this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
							// hidden filename that is retrieved from the ajax upload
							$i=$x;
							if ($i==0) {
								$i='';
							}
							$field='products_image'.$i;
							if ($this->get['file_type']==$field) {
								$temp_file=$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/'.uniqid();
								if (isset($_FILES['qqfile'])) {
									move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
								} else {
									$input=fopen("php://input", "r");
									$temp=tmpfile();
									$realSize=stream_copy_to_stream($input, $temp);
									fclose($input);
									$target=fopen($temp_file, "w");
									fseek($temp, 0, SEEK_SET);
									stream_copy_to_stream($temp, $target);
									fclose($target);
								}
								$size=getimagesize($temp_file);
								if ($size[0]>5 and $size[1]>5) {
									$imgtype=mslib_befe::exif_imagetype($temp_file);
									if ($imgtype) {
										// valid image
										$ext=image_type_to_extension($imgtype, false);
										if ($ext) {
											$i=0;
											$filename=mslib_fe::rewritenamein($this->get['products_name']).'.'.$ext;
											$folder=mslib_befe::getImagePrefixFolder($filename);
											$array=explode(".", $filename);
											if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder)) {
												t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder);
											}
											$folder.='/';
											$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['products']['original'].'/'.$folder.$filename;
											if (file_exists($target)) {
												do {
													$filename=mslib_fe::rewritenamein($this->get['products_name']).($i>0 ? '-'.$i : '').'.'.$ext;
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
											if (copy($temp_file, $target)) {
												$filename=mslib_befe::resizeProductImage($target, $filename, $this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey), 1);
												$result=array();
												$result['success']=true;
												$result['error']=false;
												$result['filename']=$filename;
												echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
												exit();
											}
										}
									}
								}
							}
						}
						break;
				}
			}
		}
		exit();
		break;
	case 'update_products_status':
		if ($this->ADMIN_USER) {
			if (is_numeric($this->post['products_id'])) {
				$str="select products_id,products_status from tx_multishop_products where products_id='".$this->post['products_id']."'";
				$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
				if ($row['products_id']) {
					switch ($row['products_status']) {
						case '0':
							mslib_befe::enableProduct($row['products_id']);
							$new_value=1;
							break;
						case '1':
							mslib_befe::disableProduct($row['products_id']);
							$new_value=0;
							break;
					}
					$item=array();
					$item['html']=$new_value;
					$json=mslib_befe::array2json($item);
					echo $json;
				}
			}
		}
		exit();
		break;
	// products attributes groups
	case 'update_attributes_options_groups_sortable':
		// this is the AJAX server for changing the sort order of the product attributes
		if ($this->ADMIN_USER) {
			switch ($this->get['tx_multishop_pi1']['type']) {
				case 'options_groups':
					if (is_array($this->post['options_groups']) and count($this->post['options_groups'])) {
						$no=1;
						foreach ($this->post['options_groups'] as $prod_id) {
							if (is_numeric($prod_id)) {
								$where="attributes_options_groups_id = ".$prod_id;
								$updateArray=array(
									'sort_order'=>$no
								);
								$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_attributes_options_groups', $where, $updateArray);
								$res=$GLOBALS['TYPO3_DB']->sql_query($query);
								$no++;
							}
						}
					}
					break;
			}
		}
		exit();
		break;
	// products attributes groups eol
	case 'update_customer_order_details':
		if ($this->ADMIN_USER and is_numeric($this->get['orders_id'])) {
			$order=mslib_fe::getOrder($this->get['orders_id']);
			if ($order['orders_id'] and !$order['is_locked']) {
				$details_type=$this->get['details_type'];
				$orders_id=$this->get['orders_id'];
				$keys=array();
				$keys[]='company';
				$keys[]='name';
				$keys[]='street_name';
				$keys[]='address_number';
				$keys[]='address_ext';
				$keys[]='building';
				$keys[]='zip';
				$keys[]='city';
				$keys[]='country';
				$keys[]='email';
				$keys[]='telephone';
				$keys[]='mobile';
				$keys[]='fax';
				$keys[]='vat_id';
				$keys[]='coc_id';
				$updateArray=array();
				switch ($details_type) {
					case "delivery_details":
						foreach ($keys as $key) {
							$string='delivery_'.$key;
							$updateArray[$string]=$this->post['tx_multishop_pi1'][$string];
						}
						$updateArray['delivery_address']=preg_replace('/ +/', ' ', $updateArray['delivery_street_name'].' '.$updateArray['delivery_address_number'].' '.$updateArray['delivery_address_ext']);
						break;
					case "billing_details":
						foreach ($keys as $key) {
							$string='billing_'.$key;
							$updateArray[$string]=$this->post['tx_multishop_pi1'][$string];
						}
						$updateArray['billing_address']=preg_replace('/ +/', ' ', $updateArray['billing_street_name'].' '.$updateArray['billing_address_number'].' '.$updateArray['billing_address_ext']);
						break;
				}
				if (count($updateArray)) {
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders', 'orders_id=\''.$orders_id.'\'', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		}
		exit();
		break;
	case 'update_multishop':
		if ($this->ADMIN_USER) {
			$item=array();
			$item['html']=mslib_befe::RunMultishopUpdate();
			$json=mslib_befe::array2json($item);
			echo $json;
		}
		exit();
		break;
	case 'admin_panel_ajax_search':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_panel_ajax_search.php');
		}
		exit();
		break;
	case 'ajax_products_search':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/ajax_products_search.php');
		exit();
		break;
	case 'ajax_attributes_option_value_search':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/ajax_attributes_option_value_search.php');
		exit();
		break;
	case 'ajax_products_attributes_search':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/ajax_products_attributes_search.php');
		exit();
		break;
	case 'ajax_products_staffelprice_search':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/ajax_products_staffelprice_search.php');
		exit();
		break;
	case 'getSpecialSections':
		if ($this->ADMIN_USER) {
			$content='';
			$sections=array();
			$str="SELECT pi_flexform from tt_content where hidden=0 and deleted=0 and list_type='multishop_pi1' and pi_flexform like '%section_code%'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
				preg_match("/<field index=\"section_code\">.*?<value.*?>(.*?)<\/value>/is", $row['pi_flexform'], $matches);
				if ($matches[1]) {
					$sections[$matches[1]]=$matches[1];
				}
				asort($sections);
			}
			if (count($sections)) {
				$content.='
					<label for="specials_portleds">'.$this->pi_getLL('admin_show_in_section').'</label>
					<div class="label_value_container">
					<ul class="twocols_ul">
				';
				$i=0;
				foreach ($sections as $section) {
					$str="SELECT ss.name from tx_multishop_specials s, tx_multishop_specials_sections ss where s.products_id='".$this->post['products_id']."' and s.status=1 and s.specials_id=ss.specials_id and ss.name='".addslashes($section)."'";
					$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
					$rows=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
					$content.='
					<li><input id="specials_sections_'.$i.'" name="specials_sections[]" type="checkbox" value="'.htmlspecialchars($section).'" '.($rows ? 'checked' : '').' />
					'.htmlspecialchars($section).'</li>
					';
					$i++;
//						<label for="specials_sections_'.$i.'">'.htmlspecialchars($section).'</label>
				}
				$content.='
				</ul>
				</div>
				';
				echo $content;
			}
		}
		exit();
		break;
	case 'admin_ajax_product_relatives':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_ajax_product_relatives.php');
		}
		exit();
		break;
	case 'product_relatives_save':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/product_relatives_save.php');
		}
		exit();
		break;
	case 'admin_shipping_costs_ajax':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/admin_shipping_costs_ajax.php');
		}
		exit();
		break;
	case 'admin_ajax':
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/admin_ajax.php');
		}
		break;
	case 'captcha':
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/captcha.php');
		exit();
		break;
	case "products_to_basket":
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/products_to_basket.php');
		exit();
		break;
	case "remove_from_basket":
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/remove_from_basket.php');
		exit();
		break;
	case "get_staffel_price":
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/get_staffel_price.php');
		exit();
		break;
	case "get_tax_ruleset":
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/tax_ruleset.php');
		exit();
		break;
	case "copy_duplicate_product":
		if ($this->ADMIN_USER) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/copy_duplicate_product.php');
		}
		exit();
		break;
	case "get_discount":
		require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/get_discount.php');
		exit();
		break;
	case "cronjob":
		if ($this->get['tx_multishop_pi1']['encryption_key'] and ($this->get['tx_multishop_pi1']['encryption_key']==$this->ms['MODULES']['MULTISHOP_ENCRYPTION_KEY'])) {
			require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/cronjob.php');
		}
		exit();
		break;
	case "ultrasearch_server":
		if (strstr($this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'], "..")) {
			die('error in ULTRASEARCH_SERVER_TYPE value');
		} else {
			if (strstr($this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'], "/")) {
				// relative mode
				require($this->DOCUMENT_ROOT.$this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'].'.php');
			} else {
				require(t3lib_extMgm::extPath('multishop').'scripts/ajax_pages/includes/ultrasearch_server/default.php');
			}
		}
		exit();
		break;
	case 'method_sortables':
		if ($this->ADMIN_USER) {
			$key='multishop_shipping_method';
			if (is_array($this->post[$key]) and count($this->post[$key])) {
				$no=1;
				foreach ($this->post[$key] as $prod_id) {
					if (is_numeric($prod_id)) {
						$where="id = ".$prod_id;
						$updateArray=array(
							'sort_order'=>$no
						);
						$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods', $where, $updateArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$no++;
					}
				}
			}
			$key='multishop_payment_method';
			if (is_array($this->post[$key]) and count($this->post[$key])) {
				$no=1;
				foreach ($this->post[$key] as $prod_id) {
					if (is_numeric($prod_id)) {
						$where="id = ".$prod_id;
						$updateArray=array(
							'sort_order'=>$no
						);
						$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_payment_methods', $where, $updateArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$no++;
					}
				}
			}
		}
		exit();
		break;
	case 'product':
		if ($this->ADMIN_USER) {
			$cat_id=mslib_fe::RemoveXSS(t3lib_div::_GET('catid'));
			$getPost=$this->post['productlisting'];
			$sort_type=$this->ms['MODULES']['PRODUCTS_LISTING_SORT_ORDER_OPTION'];
			if ($sort_type=='desc') {
				$no=time();
			} else {
				$no=1;
			}
			foreach ($getPost as $prod_id) {
				if (is_numeric($prod_id) and is_numeric($cat_id)) {
					$where="categories_id = $cat_id and products_id = $prod_id";
					$updateArray=array(
						'sort_order'=>$no
					);
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_to_categories', $where, $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', "products_id = $prod_id", $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					if ($this->ms['MODULES']['FLAT_DATABASE']) {
						// if the flat database module is enabled we have to sync the changes to the flat table
						mslib_befe::convertProductToFlat($prod_id);
					}
					if ($sort_type=='desc') {
						$no--;
					} else {
						$no++;
					}
				}
			}
		}
		exit();
		break;
	case 'menu':
		if ($this->ADMIN_USER) {
			$getPost=$this->post['sortable_maincat'];
			$no=1;
			foreach ($getPost as $cat_id) {
				if (is_numeric($cat_id)) {
					$where="categories_id = $cat_id";
					$updateArray=array(
						'sort_order'=>$no
					);
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$no++;
				}
			}
		}
		exit();
		break;
	case 'subcatlisting':
		if ($this->ADMIN_USER) {
			$getPost=$this->post['sortable_subcat'];
			$no=1;
			foreach ($getPost as $cat_id) {
				if (is_numeric($cat_id)) {
					$where="categories_id = $cat_id";
					$updateArray=array(
						'sort_order'=>$no
					);
					// FOR PROJECTS WHERE YOU WANT TO GROUP BY COLUMN NUMBER
					if ($this->post['tx_multishop_pi1']['col']) {
						$col=str_replace('msCol','',$this->post['tx_multishop_pi1']['col']);
						if(is_numeric($col)) {
							$updateArray['col_position']=$col;
						}
					}
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					$no++;
				}
			}
		}
		exit();
		break;
	case 'get_micro_download':
		// this script is for downloading a paid micro download
		if (is_numeric($this->get['orders_id']) and $this->get['code']) {
			$str="SELECT file_locked, file_downloaded, file_remote_location, file_number_of_downloads, orders_products_id, file_label, file_location, products_name from tx_multishop_orders_products where orders_id=".$this->get['orders_id']." and file_download_code='".addslashes($this->get['code'])."'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
				if ($row['file_locked']) {
					echo 'Sorry, but the maximum number of downloads has been exceeded.';
					exit();
				} else {
					$body_data='';
					// download action is valid. lets proceed
					if ($row['file_remote_location']) {
						// file is stored on remote location. lets download it and send it to the browser
						$body_data=mslib_fe::file_get_contents($row['file_remote_location']);
						if (!$row['file_label']) {
							$row['file_label']=basename($row['file_remote_location']);
						}
						if (!$row['file_label']) {
							$row['file_label']=$row['products_name'];
						}
					} elseif ($row['file_location'] and file_exists($row['file_location'])) {
						$body_data=mslib_fe::file_get_contents($row['file_location']);
						if (!$row['file_label']) {
							$row['file_label']=$row['products_name'];
						}
					}
					if ($body_data) {
						$query="update tx_multishop_orders_products set file_downloaded=(file_downloaded+1) where orders_products_id='".$row['orders_products_id']."'";
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$row['file_downloaded']++;
						if ($row['file_downloaded']>=$row['file_number_of_downloads']) {
							// maximum allowed downloads exceeded. lets lock it.
							$updateArray=array(
								'file_locked'=>'1'
							);
							$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders_products', 'orders_products_id='.addslashes($row['orders_products_id']), $updateArray);
							$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						}
						// log the download request for statistic purposes
						$updateArray=array();
						$updateArray['orders_id']=$this->get['orders_id'];
						$updateArray['orders_products_id']=$row['orders_products_id'];
						$updateArray['ip_address']=$this->REMOTE_ADDR;
						$updateArray['date_of_download']=time();
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_orders_products_downloads', $updateArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
						header("Last-Modified: ".gmdate("D,d M YH:i:s")." GMT");
						header("Cache-Control: no-cache, must-revalidate");
						header("Pragma: no-cache");
						header("Content-type: application/x-msexcel");
						header("Content-Disposition: attachment; filename=\"".basename($row['file_label'])."\"");
						header("Content-Description: TYPO3 Multishop Generated Data");
						echo $body_data;
						exit();
					}
				}
			}
		}
		exit();
		break;
	case 'get_micro_download_by_admin':
		// this script is for downloading a micro download by the admin user
		if ($this->ADMIN_USER) {
			if (is_numeric($this->get['language_id']) and is_numeric($this->get['products_id'])) {
				$str="SELECT file_label, file_location from tx_multishop_products_description where language_id='".$this->get['language_id']."' and products_id='".$this->get['products_id']."'";
				$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
					$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
					if ($row['file_location'] and file_exists($row['file_location'])) {
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
						header("Last-Modified: ".gmdate("D,d M YH:i:s")." GMT");
						header("Cache-Control: no-cache, must-revalidate");
						header("Pragma: no-cache");
						header("Content-type: application/x-msexcel");
						header("Content-Disposition: attachment; filename=\"".basename($row['file_label'])."\"");
						header("Content-Description: TYPO3 Multishop Generated Data");
						@readfile($row['file_location']);
						exit();
					}
				}
			}
		}
		exit();
		break;
	// psp thank you or error pages eof
	case 'custom_page':
		// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customAjaxPage'])) {
			$params=array(
				'content'=>&$content
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customAjaxPage'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
			if ($this->get['tx_multishop_pi1']['output']=='json') {
				echo $content;
				exit(0);
			}
		}
		// custom page hook that can be controlled by third-party plugin eof
		break;
	default:
		break;
}
?>
