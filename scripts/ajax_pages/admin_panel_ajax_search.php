<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if ($this->ADMIN_USER) {
	$page='';
	if (isset($this->get['context']['next_page'])) {
		$page=($this->get['context']['next_page']);
	}
	$p=!$page ? 0 : $this->get['context']['next_page'];
	if ($this->ms['MODULES']['CACHE_FRONT_END'] and !$this->ms['MODULES']['CACHE_TIME_OUT_SEARCH_PAGES']) {
		$this->ms['MODULES']['CACHE_FRONT_END']=0;
	}
	if ($this->ms['MODULES']['CACHE_FRONT_END']) {
		$options=array(
			'caching'=>true,
			'cacheDir'=>$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/cache/',
			'lifeTime'=>$this->ms['MODULES']['CACHE_TIME_OUT_SEARCH_PAGES']
		);
		$Cache_Lite=new Cache_Lite($options);
		$string=md5('ajax_products_search_'.$this->showCatalogFromPage.'_'.$_REQUEST['q'].'_'.$page);
	}
	if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$content=$Cache_Lite->get($string))) {
		$data=array();
		if (isset($_REQUEST['q'])) {
			$this->get['q']=$_REQUEST['q'];
			$this->get['q']=trim($this->get['q']);
			$this->get['q']=$GLOBALS['TSFE']->csConvObj->utf8_encode($this->get['q'], $GLOBALS['TSFE']->metaCharset);
			$this->get['q']=$GLOBALS['TSFE']->csConvObj->entities_to_utf8($this->get['q'], TRUE);
			$this->get['q']=mslib_fe::RemoveXSS($this->get['q']);
		}
		//	if ($_REQUEST['q'] and strlen($_REQUEST['q']) < 3) exit();
		/**
		 * Perform a simple text replace
		 * This should be used when the string does not contain HTML
		 * (off by default)
		 */
		define('STR_HIGHLIGHT_SIMPLE', 1);
		/**
		 * Only match whole words in the string
		 * (off by default)
		 */
		define('STR_HIGHLIGHT_WHOLEWD', 2);
		/**
		 * Case sensitive matching
		 * (on by default)
		 */
		define('STR_HIGHLIGHT_CASESENS', 0);
		/**
		 * Overwrite links if matched
		 * This should be used when the replacement string is a link
		 * (off by default)
		 */
		define('STR_HIGHLIGHT_STRIPLINKS', 8);
		/**
		 * Highlight a string in text without corrupting HTML tags
		 * @author      Aidan Lister <aidan@php.net>
		 * @version     3.1.0
		 * @param       string $text Haystack - The text to search
		 * @param       array|string $needle Needle - The string to highlight
		 * @param       bool $options Bitwise set of options
		 * @param       array $highlight Replacement string
		 * @return      Text with needle highlighted
		 */
		function str_highlight($text, $needle='', $options=null, $highlight=null) {
			if (!$needle) {
				return $text;
			}
			// Default highlighting
			if ($highlight===null) {
				$highlight='<strong class="highlight">\1</strong>';
			}
			// Select pattern to use
			if ($options&STR_HIGHLIGHT_SIMPLE) {
				$pattern='#(%s)#';
			} else {
				$pattern='#(?!<.*?)(%s)(?![^<>]*?>)#';
				$sl_pattern='#<a\s(?:.*?)>(%s)</a>#';
			}
			// Case sensitivity
			/*if ($options ^ STR_HIGHLIGHT_CASESENS) {
		
				$pattern .= 'i';
				$sl_pattern .= 'i';
			}*/
			$pattern.='i';
			$sl_pattern.='i';
			$needle=(array)$needle;
			foreach ($needle as $needle_s) {
				$needle_s=preg_quote($needle_s);
				// Escape needle with optional whole word check
				if ($options&STR_HIGHLIGHT_WHOLEWD) {
					$needle_s='\b'.$needle_s.'\b';
				}
				// Strip links
				if ($options&STR_HIGHLIGHT_STRIPLINKS) {
					$sl_regex=sprintf($sl_pattern, $needle_s);
					$text=preg_replace($sl_regex, '\1', $text);
				}
				$regex=sprintf($pattern, $needle_s);
				$text=preg_replace($regex, $highlight, $text);
			}
			return $text;
		}
		if (!is_numeric($p)) {
			$p=0;
		}
		$limit=20;
		$offset=$p*$limit;
		$this->get['limit']=$limit;
		$global_max_page=0;
		$modules=array();
		$orders_filter=array();
		$products_filter=array();
		$customers_filter=array();
		// global paging indicator
		$have_paging=false;
		$results_counter=0;
		if (is_numeric($this->get['q'])) {
			if ($this->ADMIN_USER) {
				$modules['admin_cms']=1;
				$modules['admin_settings']=1;
			}
			// numeric so first find orders and customer ids
			$modules['orders']=1;
			$modules['invoices']=1;
			$modules['customers']=1;
			$modules['products']=1;
			$modules['categories']=1;
			$orders_filter[]='o.orders_id like "'.addslashes($this->get['q']).'%"';
			$invoices_filter[]='i.invoice_id like "'.addslashes($this->get['q']).'%"';
			$customers_filter[]='f.uid like "'.addslashes($this->get['q']).'%"';
			$categories_filter[]='c.products_id like "'.addslashes($this->get['q']).'%"';
		} else {
			// string
			if ($this->ADMIN_USER) {
				$modules['admin_cms']=1;
				$modules['admin_settings']=1;
			}
			$modules['invoices']=1;
			$modules['orders']=1;
			$modules['customers']=1;
			$modules['products']=1;
			$modules['categories']=1;
			$invoices_filter[]='i.invoice_id like "'.addslashes($this->get['q']).'%"';
		}
		if (isset($this->get['context']['section']) && !empty($this->get['context']['section'])) {
			$section=$this->get['context']['section'];
		} else {
			$section='admin_cms';
		}
		$data_json=array();
		$move_next_section=false;
		$next_page=false;
		// cms search
		if ($modules[$section] && $section=='admin_cms') {
			$filter=array();
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>0) {
				$items=array();
				$items[]="cd.name LIKE '%".addslashes($this->get['q'])."%'";
				//$items[] ="cd.content LIKE '%".addslashes($this->get['q'])."%'";
				$filter[]='('.implode(" or ", $items).')';
				$filter[]='c.status = 1';
			}
			//if (!$this->masterShop) $filter[]='page_uid='.$this->shop_pid;
			$select[]='cd.id, cd.name';
			$orderby[]='cd.name';
			$pageset=array();
			$pageset=mslib_fe::getCMSPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
			$resultset['admin_cms']=$pageset;
			if (count($resultset['admin_cms']['admin_cms'])>0) {
				foreach ($resultset['admin_cms']['admin_cms'] as $category) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($category['name'], 0, 50);
					$prod['id']=md5($category['name']);
					$prod['text']=$category['name'];
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_ajax&cmt_id='.$category['id']).'&action=edit_cms';
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					$prod['EditIcons']='';
					$data['listing']['cms'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='admin_settings';
			}
		}
		// admin_settings search
		if (isset($section) && $modules[$section] && $section=='admin_settings') {
			if (isset($move_next_section) && ((isset($this->get['context']['section']) && $this->get['context']['section']!=$section) || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$filter=array();
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>0) {
				$items=array();
				$items[]="c.configuration_title LIKE '%".addslashes($this->get['q'])."%'";
				//$items[] ="c.description LIKE '%".addslashes($this->get['q'])."%'";
				$items[]="c.configuration_key LIKE '%".addslashes($this->get['q'])."%'";
				$items[]="c.configuration_value LIKE '%".addslashes($this->get['q'])."%'";
				$items[]="cv.configuration_value LIKE '%".addslashes($this->get['q'])."%'";
				$filter[]='('.implode(" or ", $items).')';
				$filter[]="(cv.page_uid is NULL or cv.page_uid='".$this->showCatalogFromPage."')";
			}
			//if (!$this->masterShop) $filter[]='page_uid='.$this->shop_pid;
			$select[]='c.id, c.configuration_title';
			$orderby[]='c.configuration_title';
			$pageset=mslib_fe::getAdminSettingsPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
			$resultset['admin_settings']=$pageset;
			if (count($resultset['admin_settings']['admin_settings'])>0) {
				foreach ($resultset['admin_settings']['admin_settings'] as $category) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($category['configuration_title'], 0, 50);
					$prod['id']=md5($category['configuration_title']);
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['text']=$category['configuration_title'];
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_ajax&module_id='.$category['id']).'&action=edit_module';
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					$prod['EditIcons']='';
					$data['listing']['admin_settings'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='orders';
			}
		}
		// admin_settings search eof
		// orders search
		if ($modules[$section] && $section=='orders') {
			if ($move_next_section && ($this->get['context']['section']!=$section || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$filter=$orders_filter;
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>0) {
				$items=array();
				$items[]="orders_id='".addslashes($this->get['q'])."'";
				$items[]="customer_id LIKE '".addslashes($this->get['q'])."%'";
				$filter[]='('.implode(" or ", $items).')';
				$filter[]='(o.deleted=0)';
			}
			if (!$this->masterShop) {
				$filter[]='o.page_uid='.$this->showCatalogFromPage;
			}
			$select[]='o.*, osd.name as orders_status';
			$orderby[]='o.orders_id desc';
			$pageset=mslib_fe::getOrdersPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
			$resultset['orders']=$pageset;
			if (count($resultset['orders']['orders'])) {
				foreach ($resultset['orders']['orders'] as $order) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($order['orders_id'], 0, 50);
					$prod['id']=md5($order['orders_id']);
					$prod['text']=$order['orders_id'];
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_ajax&orders_id='.$order['orders_id']).'&action=edit_order';
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					$prod['EditIcons']='';
					$data['listing']['orders'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='invoices';
			}
		}
		// orders search eof
		// invoices search
		if ($modules[$section] && $section=='invoices') {
			if ($move_next_section && ($this->get['context']['section']!=$section || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$filter=$invoices_filter;
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (!$this->masterShop) {
				$filter[]='i.page_uid='.$this->showCatalogFromPage;
			}
			$select[]='i.invoice_id,i.hash';
			$orderby[]='i.id desc';
			$pageset=mslib_fe::getInvoicesPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
			$resultset['invoices']=$pageset;
			if (count($resultset['invoices']['invoices'])) {
				foreach ($resultset['invoices']['invoices'] as $invoice) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($invoice['invoice_id'], 0, 50);
					$prod['id']=md5($invoice['invoice_id']);
					$prod['text']=$invoice['invoice_id'];
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]='.$invoice['hash']);
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					$prod['EditIcons']='';
					$data['listing']['invoices'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='customers';
			}
		}
		// invoices eof
		// customer search
		if ($modules[$section] && $section=='customers') {
			if ($move_next_section && ($this->get['context']['section']!=$section || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$filter=$customers_filter;
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>0) {
				$items=array();
				$items[]="f.company like '%".addslashes($this->get['q'])."%'";
				$items[]="f.name like '%".addslashes($this->get['q'])."%'";
				$items[]="f.email like '%".addslashes($this->get['q'])."%'";
				$items[]="f.username like '%".addslashes($this->get['q'])."%'";
				$filter[]='('.implode(" or ", $items).')';
				$filter[]='(f.disable=0 and f.deleted=0)';
			}
			$pageset=mslib_fe::getCustomersPageSet($filter, $offset, 0, $orderby, $having, $select, $where);
			$resultset['customers']=$pageset;
			if (count($resultset['customers']['customers'])) {
				foreach ($resultset['customers']['customers'] as $customer) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					if (!$customer['company']) {
						$customer['company']='N/A';
					}
					if (!$customer['name']) {
						$customer['name']=$customer['username'];
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($customer['name'], 0, 50);
					$prod['id']=md5($customer['name']);
					$prod['text']=$customer['name'];
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['Link']=mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]=admin_ajax&tx_multishop_pi1[cid]='.$customer['uid'].'&action=edit_customer');
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					$prod['EditIcons']='';
					$data['listing']['customers'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='categories';
			}
		}
		// customer search eof
		// categories search
		if ($modules[$section] && $section=='categories') {
			if ($move_next_section && ($this->get['context']['section']!=$section || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$filter=$categories_filter;
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>0) {
				$items=array();
				$items[]="cd.categories_name LIKE '%".addslashes($this->get['q'])."%'";
				$filter[]='('.implode(" or ", $items).')';
				$filter[]='c.status = 1';
				//$filter[]='(f.disable=0 and f.deleted=0)';
			}
			if (!$this->masterShop) {
				$filter[]='c.page_uid='.$this->showCatalogFromPage;
			}
			$select[]='c.categories_url, cd.categories_name, c.categories_id';
			$orderby[]='cd.categories_name';
			$pageset=mslib_fe::getCategoriesPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
			$resultset['categories']=$pageset;
			if (count($resultset['categories']['categories'])) {
				foreach ($resultset['categories']['categories'] as $category) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Name']=substr($category['categories_name'], 0, 50);
					$prod['id']=md5($category['categories_name']);
					$prod['text']=$category['categories_name'];
					$prod['Title']=str_highlight($prod['Name'], $this->get['q']);
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_ajax&cid='.$category['categories_id'].'&action=edit_category');
					$prod['Image']='';
					$prod['Desc']='';
					$prod['Price']='';
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=false;
					$prod['SmallListing']=true;
					if ($category['categories_url']) {
						$target=' target="_blank"';
						$link=$category['categories_url'];
					} else {
						$target="";
						// get all cats to generate multilevel fake url
						$level=0;
						$cats=mslib_fe::Crumbar($this->get['categories_id']);
						$cats=array_reverse($cats);
						$where='';
						if (count($cats)>0) {
							foreach ($cats as $item) {
								$where.="categories_id[".$level."]=".$item['id']."&";
								$level++;
							}
							$where=substr($where, 0, (strlen($where)-1));
							$where.='&';
						}
						$where.='categories_id['.$level.']='.$category['categories_id'];
						// get all cats to generate multilevel fake url eof
						$link=$this->FULL_HTTP_URL.mslib_fe::typolink($this->conf['products_listing_page_pid'], '&'.$where.'&tx_multishop_pi1[page_section]=products_listing');
					}
					$data['listing']['categories'][]=$prod;
				}
				$next_page=true;
			} else {
				$move_next_section=true;
				$section='products';
			}
		}
		// categories search eof
		// product search
		if ($modules[$section] && $section=='products') {
			if ($move_next_section && ($this->get['context']['section']!=$section || !isset($this->get['context']['section']))) {
				$p=0;
				$offset=$p*$limit;
			}
			$this->ms['MODULES']['FLAT_DATABASE']=0;
			$filter=array();
			$having=array();
			$match=array();
			$orderby=array();
			$where=array();
			$orderby=array();
			$select=array();
			if (strlen($this->get['q'])>1) {
				$items=array();
				$array=explode(" ", $this->get['q']);
				$total=count($array);
				$oldsearch=0;
				foreach ($array as $item) {
					if (strlen($item)<$this->ms['MODULES']['FULLTEXT_SEARCH_MIN_CHARS']) {
						$oldsearch=1;
						break;
					}
				}
				if ($this->ms['MODULES']['FLAT_DATABASE']) {
					$tbl='';
				} else {
					$tbl='pd.';
				}
				$items[]=$tbl.'products_id like "'.addslashes($this->get['q']).'%"';
				if ($this->ms['MODULES']['REGULAR_SEARCH_MODE']=='%keyword') {
					// do normal indexed search
					$items[]="(".$tbl."products_name like '%".addslashes($this->get['q'])."')";
				} else {
					if ($this->ms['MODULES']['REGULAR_SEARCH_MODE']=='keyword%') {
						// do normal indexed search
						$items[]="(".$tbl."products_name like '".addslashes($this->get['q'])."%')";
					} else {
						// do normal indexed search
						$items[]="(".$tbl."products_name like '%".addslashes($this->get['q'])."%')";
					}
				}
				if ($this->ms['MODULES']['FLAT_DATABASE']) {
					$tbl='pf.';
				} else {
					$tbl='p.';
				}
				$items[]="(".$tbl."products_model like '%".addslashes($this->get['q'])."%')";
				$items[]="(".$tbl."sku_code like '%".addslashes($this->get['q'])."%')";
				$items[]="(".$tbl."ean_code like '%".addslashes($this->get['q'])."%')";
				$items[]="(".$tbl."vendor_code like '%".addslashes($this->get['q'])."%')";
				$filter[]='('.implode(" OR ", $items).')';
				if ($this->ms['MODULES']['FLAT_DATABASE']) {
					$filter[]="pf.sstatus=1";
				} else {
					$filter[]="p.products_status=1";
				}
			}
			if (is_numeric($parent_id) and $parent_id>0) {
				if ($this->ms['MODULES']['FLAT_DATABASE']) {
					$string='(';
					for ($i=0; $i<4; $i++) {
						if ($i>0) {
							$string.=" or ";
						}
						$string.="categories_id_".$i." = '".$parent_id."'";
					}
					$string.=')';
					if ($string) {
						$filter[]=$string;
					}
					//
				} else {
					$cats=mslib_fe::get_subcategory_ids($parent_id);
					$cats[]=$parent_id;
					$filter[]="p2c.categories_id IN (".implode(",", $cats).")";
				}
			}
//			error_log(print_r($filter,1));
			$pageset=mslib_fe::getProductsPageSet($filter, $offset, $limit, $orderby, $having, $select, $where, 0, array(), array(), 'admin_ajax_products_search');
			$resultset['products']=$pageset;
			if (count($resultset['products']['products'])) {
				foreach ($resultset['products']['products'] as $product) {
					if (!$tr_type or $tr_type=='even') {
						$tr_type='odd';
					} else {
						$tr_type='even';
					}
					$prod=array();
					$prod['is_children']=true;
					$prod['Link']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_ajax&cid='.$product['categories_id'].'&pid='.$product['products_id'].'&action=edit_product');
					if ($product['products_image']) {
						$prod['Image']='<div class="ajax_products_image">'.'<img src="'.mslib_befe::getImagePath($product['products_image'], 'products', '50').'">'.'</div>';
					} else {
						$prod['Image']='<div class="ajax_products_image"><div class="no_image_50"></div>
					</div>';
					}
					$prod['Title']='<div class="ajax_products_name">'.substr($product['products_name'], 0, 50).'</div>';
					$prod['id']=md5($product['products_name']);
					$prod['text']=$product['products_name'];
					$prod['Title']=$prod['Title'];
					$product['products_shortdescription']=strip_tags($product['products_shortdescription']);
					$prod['Desc']='<div class="ajax_products_shortdescription">'.str_highlight(substr($product['products_shortdescription'], 0, 75), $this->get['q']).'</div>';
					if ($product['products_price']<>$product['final_price']) {
						$prod['Price']='<div class="ajax_old_price">'.mslib_fe::amount2Cents($product['products_price'], 0).'</div><div class="ajax_specials_price">'.mslib_fe::amount2Cents($product['final_price'], 0).'</div>';
					} else {
						$prod['Price']='<div class="ajax_products_price">'.mslib_fe::amount2Cents($product['products_price'], 0).'</div>';
					}
					$prod['Name']=substr($product['products_name'], 0, 50);
					$prod['skeyword']=$this->get['q'];
					$prod['Page']=$pages;
					$prod['Product']=true;
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
					if ($product['products_url'] and $this->ms['MODULES']['AFFILIATE_SHOP']) {
						$link=$product['products_url'];
					} else {
						$link=$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid, '&'.$where.'&products_id='.$product['products_id']);
					}
					$data['listing']['products'][]=$prod;
				}
				$next_page=true;
			}
		}
		// product search eof
		// now build up the listing
		$data_json=array();
		if (count($data['listing']['cms'])>0) {
			$data_json[]=array(
				'text'=>'CMS',
				'children'=>$data['listing']['cms']
			);
		}
		if (count($data['listing']['admin_settings'])>0) {
			$data_json[]=array(
				'text'=>'Admin settings',
				'children'=>$data['listing']['admin_settings']
			);
		}
		if (count($data['listing']['orders'])>0) {
			$data_json[]=array(
				'text'=>'Orders',
				'children'=>$data['listing']['orders']
			);
		}
		if (count($data['listing']['invoices'])>0) {
			$data_json[]=array(
				'text'=>'Invoices',
				'children'=>$data['listing']['invoices']
			);
		}
		if (count($data['listing']['customers'])>0) {
			$data_json[]=array(
				'text'=>'Customers',
				'children'=>$data['listing']['customers']
			);
		}
		if (count($data['listing']['categories'])>0) {
			$data_json[]=array(
				'text'=>'Categories',
				'children'=>$data['listing']['categories']
			);
		}
		if (count($data['listing']['products'])>0) {
			$data_json[]=array(
				'text'=>'Products',
				'children'=>$data['listing']['products']
			);
		}
		// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/admin_panel_ajax_search.php']['json_encode_preProc'])) {
			$params=array(
				'data_json'=>&$data_json,
				'next_page'=>&$next_page
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/admin_panel_ajax_search.php']['json_encode_preProc'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
		$page_marker=array();
		$page_marker['context']['section']=$section;
		$page_marker['context']['next_page']=$p+1;
		$page_marker['context']['next']=$next_page;
		// custom page hook that can be controlled by third-party plugin eof
		$content=array(
			"products"=>$data_json,
			"total_rows"=>$results_counter,
			"page_marker"=>$page_marker
		);
		$content=json_encode($content, ENT_NOQUOTES);
		// now build up the listing eof
	}
	echo $content;
	exit;
}
?>