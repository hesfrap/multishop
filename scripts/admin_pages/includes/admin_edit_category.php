<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

$GLOBALS['TSFE']->additionalHeaderData[] = '
<script type="text/javascript">
window.onload = function(){
  var text_input = document.getElementById (\'categories_name_0\');
  text_input.focus ();
  text_input.select ();
}
</script>
';
$update_category_image='';
// hidden filename that is retrieved from the ajax upload
if ($this->post['ajax_categories_image'])	$update_category_image=$this->post['ajax_categories_image'];
if ($this->post and is_array($_FILES) and count($_FILES))
{
	if ($this->post['categories_name'][0]) $this->post['categories_name'][0]=trim($this->post['categories_name'][0]);	
	if (is_array($_FILES) and count($_FILES))
	{	
		$file=$_FILES['categories_image'];
		if ($file['tmp_name'])
		{
			$size=getimagesize($file['tmp_name']);
			if ($size[0] > 5 and $size[1] > 5)
			{		
				$imgtype = mslib_befe::exif_imagetype($file['tmp_name']);
				if ($imgtype)
				{
					// valid image
					$ext = image_type_to_extension($imgtype, false);
					$i=0;	
					$filename=mslib_fe::rewritenamein($this->post['categories_name'][0]).'.'.$ext;
					$folder=mslib_befe::getImagePrefixFolder($filename);
					if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder))
					{
						t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder);
					}
					$folder.='/';				
					$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder.$filename;
					if (file_exists($target))
					{
						do
						{		
							$filename=mslib_fe::rewritenamein($this->post['categories_name'][0]).'-'.$i.'.'.$ext;		
							$folder=mslib_befe::getImagePrefixFolder($filename);													
							if (!is_dir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder))
							{
								t3lib_div::mkdir($this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder);
							}
							$folder.='/';					
							$target=$this->DOCUMENT_ROOT.$this->ms['image_paths']['categories']['original'].'/'.$folder.$filename;
							$i++;
						} while (file_exists($target));
					}
					if (move_uploaded_file($file['tmp_name'],$target))
					{
						$update_category_image=mslib_befe::resizeCategoryImage($target,$filename,$this->DOCUMENT_ROOT.t3lib_extMgm::siteRelPath($this->extKey),1);
					}
				}	
			}
		}
	}
}
if ($this->post)
{
	// sometimes the categories startingpoint is not zero. To protect merchants configure a category that is member of itself we reset the parent_id to zero
	if ($this->post['parent_id']==$this->post['cid']) $this->post['parent_id']=0;
	$updateArray=array();
	$updateArray['custom_settings']				= $this->post['custom_settings'];
	$updateArray['parent_id']					=$this->post['parent_id'];
	$updateArray['categories_url']				=$this->post['categories_url'];	
	$updateArray['status']						=$this->post['status'];

	if ($update_category_image)					$updateArray['categories_image'] =$update_category_image;
	//Options ID
	$option_attributes = "";
	$i_x = 0;
	if (is_array($this->post['products_options']) and count($this->post['products_options']))
	{
		foreach ($this->post['products_options'] as $option_id)
		{
			if ($this->post['html_options'][$i_x] != '0')
			{
				$option_attributes .= $option_id . ":" . $this->post['html_options'][$i_x] . ";";
			}
			$i_x++;
		}
	}
	$updateArray['option_attributes']			=$option_attributes;
	if ($_REQUEST['action']=='add_category')
	{
		$updateArray['page_uid'] = $this->showCatalogFromPage;		
		$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_categories', $updateArray);
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$catid=$GLOBALS['TYPO3_DB']->sql_insert_id();
	}
	elseif ($_REQUEST['action']=='edit_category')
	{		
		$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', 'categories_id=\''.$this->post['cid'].'\'',$updateArray);
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$catid=$this->post['cid'];
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$products=mslib_fe::getProducts('',$catid);
			if (is_array($products)) {
				foreach ($products as $product) {
					// if the flat database module is enabled we have to sync the changes to the flat table
					mslib_befe::convertProductToFlat($product['products_id']);
				}
			}
		}		
	}
	if ($catid)
	{
		foreach ($this->post['categories_name'] as $key => $value)
		{		
			$str="select 1 from tx_multishop_categories_description where categories_id='".$catid."' and language_id='".$key."'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);		
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0)
			{	
				$updateArray=array();
				$updateArray['categories_name']				=$this->post['categories_name'][$key];
				$updateArray['meta_title']					=$this->post['meta_title'][$key];
				$updateArray['meta_keywords']				=$this->post['meta_keywords'][$key];
				$updateArray['meta_description']			=$this->post['meta_description'][$key];
				$updateArray['content']						=$this->post['content'][$key];
				$updateArray['content_footer']				=$this->post['content_footer'][$key];
				$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories_description', 'categories_id=\''.$catid.'\' and language_id=\''.$key.'\'',$updateArray);
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);	
			}
			else
			{
				$updateArray=array();
				$updateArray['categories_id']				=$catid;
				$updateArray['language_id']					=$key;
				$updateArray['categories_name']				=$this->post['categories_name'][$key];
				$updateArray['meta_title']					=$this->post['meta_title'][$key];
				$updateArray['meta_keywords']				=$this->post['meta_keywords'][$key];
				$updateArray['meta_description']			=$this->post['meta_description'][$key];
				$updateArray['content']						=$this->post['content'][$key];
				$updateArray['content_footer']				=$this->post['content_footer'][$key];
				$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_categories_description', $updateArray);
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			}
		}
		// custom hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['saveCategoryPostHook']))
		{
			$params = array (
				'catid' => $catid
			); 
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['saveCategoryPostHook'] as $funcRef)
			{
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}	
		// custom hook that can be controlled by third-party plugin eof			
		$content.= $this->pi_getLL('category_saved').'.';
		$content.= '
		<script type="text/javascript">
		parent.window.location.reload();
		</script>
		';

	}
}
else
{
	if ($_REQUEST['action']=='edit_category')
	{
		$str="SELECT * from tx_multishop_categories c, tx_multishop_categories_description cd where c.categories_id='".$_REQUEST['cid']."' and c.categories_id=cd.categories_id";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		$category=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
		if ($this->get['delete_image'] and is_numeric($this->get['cid']))
		{
			if ($category[$this->get['delete_image']])
			{
				mslib_befe::deleteCategoryImage($category[$this->get['delete_image']]);
				$updateArray=array();
				$updateArray[$this->get['delete_image']]='';
				$category[$this->get['delete_image']]='';
				$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', 'categories_id=\''.$this->get['cid'].'\'',$updateArray);
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);			
			}
		}	
		$str="SELECT * from tx_multishop_categories c, tx_multishop_categories_description cd where c.categories_id='".$this->get['cid']."' and c.categories_id=cd.categories_id";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false)
		{
			$lngcat[$row['language_id']]=$row;
		}	
	}
if ($category['categories_id'] or $_REQUEST['action']=='add_category')
{
	if (!$category['parent_id']) $category['parent_id']=$this->get['cid'];
	$save_block='
		<div class="save_block">
			<input name="cancel" type="button" value="'.$this->pi_getLL('cancel').'" onClick="parent.window.hs.close();" class="submit" />
			<input name="Submit" type="submit" value="'.$this->pi_getLL('save').'" class="submit" />
		</div>		
	';	
	$content 	.= '
	<form class="admin_category_edit" name="admin_categories_edit_'.$category['categories_id'].'" id="admin_categories_edit_'.$category['categories_id'].'" method="post" action="'.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=admin_ajax&cid='.$_REQUEST['cid']).'" enctype="multipart/form-data">';
	
	$tmpcontent.='<div style="float:right;">'.$save_block.'</div>';
	if ($_REQUEST['action']=='add_category') {
		$tmpcontent.='<div class="main-heading"><h1>'.$this->pi_getLL('add_category').'</h1></div>';
	} else if ($_REQUEST['action']=='edit_category') {
		$level=0;
		$cats=mslib_fe::Crumbar($category['categories_id']);
		
		$cats=array_reverse($cats);
		$where='';
		if (count($cats) > 0) {
			foreach ($cats as $item) {
				$where.="categories_id[".$level."]=".$item['id']."&";
				$level++;
			}
			$where=substr($where,0,(strlen($where)-1));
		}
		// get all cats to generate multilevel fake url eof
		$details_link = mslib_fe::typolink($this->conf['products_listing_page_pid'],$where.'&tx_multishop_pi1[page_section]=products_listing');
		$tmpcontent.='<div class="main-heading"><h1>'.$this->pi_getLL('edit_category').' (ID: '.$category['categories_id'].')</h1><span class="viewfront"><a href="'.$details_link.'" target="_blank">'.$this->pi_getLL('admin_edit_view_front_category', 'View in front').'</a></span></div>';
	}
	foreach ($this->languages as $key => $language)
	{
		$tmpcontent	.='
		<div class="account-field" id="msEditCategoryInputName_'.$language['uid'].'">
		<label>'.t3lib_div::strtoupper($this->pi_getLL('language')).'</label>';
		if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif')) $tmpcontent	.='<img src="'.$this->FULL_HTTP_URL_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif"> ';
		$tmpcontent	.=''.$language['title'].'
		</div>	
		<div class="account-field" id="msEditCategoryInputCategoryName_'.$language['uid'].'">
			<label for="categories_name">'.$this->pi_getLL('admin_name').'</label>
			<input spellcheck="true" type="text" class="text" name="categories_name['.$language['uid'].']" id="categories_name_'.$language['uid'].'" value="'.htmlspecialchars($lngcat[$language['uid']]['categories_name']).'">
		</div>		
		';
	}
	// when editing the current category we must prevent the user to chain the selected category to it's childs.
	$skip_ids=array();
	if ($_REQUEST['action'] =='edit_category')
	{
		if (is_numeric($this->get['cid']) and $this->get['cid'] > 0)
		{
			$str="select categories_id from tx_multishop_categories where parent_id='".$this->get['cid']."'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false)
			{
				$skip_ids[]=$row['categories_id'];
			}
		}
		$skip_ids[]=$category['categories_id'];
	}
	$tmpcontent	.='		
		<div class="account-field" id="msEditCategoryInputParent">
			<label for="parent_id">'.$this->pi_getLL('admin_parent').'</label>	
			'. mslib_fe::tx_multishop_draw_pull_down_menu('parent_id', mslib_fe::tx_multishop_get_category_tree('','',$skip_ids), $category['parent_id']).'
		</div>
	###EXTRA_FIELDS_0###				
	';
	
	$tmpcontent .= '
		<div class="account-field" id="msEditCategoryInputVisibility">
			<label for="status">'.$this->pi_getLL('admin_visible').'</label>	
			<input name="status" type="radio" value="1" '.(($category['status'] or $_REQUEST['action']=='add_category')?'checked':'').' /> '.$this->pi_getLL('admin_yes').' <input name="status" type="radio" value="0" '.((!$category['status'] and $_REQUEST['action'] =='edit_category')?'checked':'').' /> '.$this->pi_getLL('admin_no').' 
		</div>
		###EXTRA_FIELDS_1###
		<div class="account-field" id="msEditCategoryInputImage">
			<label for="categories_image">'.$this->pi_getLL('admin_image').'</label>
			<div id="categories_image">		
				<noscript>				
					<input name="categories_image" type="file" />
				</noscript>         
			</div>		
			<input name="ajax_categories_image" id="ajax_categories_image" type="hidden" value="" />				
			';
		if ($_REQUEST['action'] =='edit_category' and $category['categories_image'])
		{
			$tmpcontent.='<img src="'.mslib_befe::getImagePath($category['categories_image'],'categories','normal').'">';
			$tmpcontent.=' <a href="'.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=admin_ajax&cid='.$_REQUEST['cid'].'&action=edit_category&delete_image=categories_image').'" onclick="return confirm(\'Are you sure?\')"><img src="'.$this->FULL_HTTP_URL_MS.'templates/images/icons/delete2.png" border="0" alt="delete image"></a>';			
		}		
		$tmpcontent.='</div>
		###EXTRA_FIELDS_2###
		';
$tmpcontent.='
    <script type="text/javascript">	
		jQuery(document).ready(function($) {
			var categories_name=$("#categories_name_0").val();								   
            var uploader = new qq.FileUploader({
                element: document.getElementById(\'categories_image\'),
                action: \''.mslib_fe::typolink(',2002','&tx_multishop_pi1[page_section]=admin_upload_product_images').'\',
				params: {
					categories_name: categories_name,
					file_type: \'categories_image\'
				},	
				template: \'<div class="qq-uploader">\' + 
	                \'<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>\' +
    	            \'<div class="qq-upload-button">'.addslashes(htmlspecialchars($this->pi_getLL('choose_image'))).'</div>\' +
        	        \'<ul class="qq-upload-list"></ul>\' + 
            		\'</div>\',
				onComplete: function(id, fileName, responseJSON){
					var filenameServer = responseJSON[\'filename\'];
					$("#ajax_categories_image").val(filenameServer);
			    },
                debug: false				
            });   
			$(\'#categories_name_0\').change(function() {
			var categories_name=$("#categories_name_0").val();
				uploader.setParams({
				   categories_name: categories_name,
				   file_type: \'categories_image\'
				});		
			});			
		});		
    </script>		
';  
	$tmpcontent.='
		<div class="account-field" id="msEditCategoryInputExternalUrl">
			<label for="categories_url">'.$this->pi_getLL('admin_external_url').'</label>
			<input type="text" class="text" name="categories_url" id="categories_url" value="'.htmlspecialchars($category['categories_url']).'">
		</div>
		###EXTRA_FIELDS_3###';
	
// custom hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['addItemsToTabDetails'])) {
	$params = array (
		'tmpcontent' => &$tmpcontent,
		'category' => &$category
	); 
	foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['addItemsToTabDetails'] as $funcRef) {
		t3lib_div::callUserFunction($funcRef, $params, $this);
	}
}	
// custom hook that can be controlled by third-party plugin eof

// delete unused replacement tags for extrafields in DETAILS tab
for ($ex = 0; $ex < 4; $ex++) {
	$tmpcontent = str_replace("###EXTRA_FIELDS_".$ex."###", '', $tmpcontent);
}

$tabs['category_main']=array('DETAILS',$tmpcontent);
$tmpcontent='';
	foreach ($this->languages as $key => $language)
	{
		$tmpcontent	.='
		<div class="account-field" id="msEditCategoryInputContent_'.$language['uid'].'">
		<label>'.t3lib_div::strtoupper($this->pi_getLL('language')).'</label>';
		if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif')) $tmpcontent	.='<img src="'.$this->FULL_HTTP_URL_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif"> ';
		$tmpcontent	.=''.$language['title'].'
		</div>	
		<div class="account-field" id="msEditCategoryInputContentHeader_'.$language['uid'].'">
					<label for="content">'.t3lib_div::strtoupper($this->pi_getLL('content')).' '.t3lib_div::strtoupper($this->pi_getLL('top')).'</label>
					<textarea spellcheck="true" name="content['.$language['uid'].']" id="content['.$language['uid'].']" class="mceEditor" rows="4">'.htmlspecialchars($lngcat[$language['uid']]['content']).'</textarea>
				</div>		
				<div class="account-field" id="msEditCategoryInputContentFooter_'.$language['uid'].'">
					<label for="content_footer">'.t3lib_div::strtoupper($this->pi_getLL('content')).' '.t3lib_div::strtoupper($this->pi_getLL('bottom')).'</label>
					<textarea spellcheck="true" name="content_footer['.$language['uid'].']" id="content_footer['.$language['uid'].']" class="mceEditor" rows="4">'.htmlspecialchars($lngcat[$language['uid']]['content_footer']).'</textarea>
		</div>			
		';
	}	
$tabs['category_content']=array('CONTENT',$tmpcontent);
$tmpcontent='';	
	foreach ($this->languages as $key => $language)
	{
		$tmpcontent	.='
		<div class="account-field" id="msEditCategoryInputMeta_'.$language['uid'].'">
		<label>'.t3lib_div::strtoupper($this->pi_getLL('language')).'</label>';
		if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif')) $tmpcontent	.='<img src="'.$this->FULL_HTTP_URL_TYPO3.'sysext/cms/tslib/media/flags/flag_'.$language['flag'].'.gif"> ';
		$tmpcontent	.=''.$language['title'].'
		</div>						
		<div class="account-field" id="msEditCategoryInputMetaTitle_'.$language['uid'].'">
			<label for="meta_title">META TITLE</label>
			<input type="text" class="text" name="meta_title['.$language['uid'].']" id="meta_title['.$language['uid'].']" value="'.htmlspecialchars($lngcat[$language['uid']]['meta_title']).'">
		</div>				
		<div class="account-field" id="msEditCategoryInputMetaKeywords_'.$language['uid'].'">
			<label for="meta_keywords">META KEYWORDS</label>
			<input type="text" class="text" name="meta_keywords['.$language['uid'].']" id="meta_keywords['.$language['uid'].']" value="'.htmlspecialchars($lngcat[$language['uid']]['meta_keywords']).'">
		</div>				
		<div class="account-field" id="msEditCategoryInputMetaDesc_'.$language['uid'].'">
			<label for="meta_description">META DESCRIPTION</label>
			<input type="text" class="text" name="meta_description['.$language['uid'].']" id="meta_description['.$language['uid'].']" value="'.htmlspecialchars($lngcat[$language['uid']]['meta_description']).'">
		</div>
		';
	}		
		
$tabs['category_meta']=array('META',$tmpcontent);		
$tmpcontent='';
$tmpcontent.='
		<div class="account-field" id="msEditCategoryInputCustomSettings">
			<label for="custom_settings">'.$this->pi_getLL('admin_custom_configuration').'</label>
			<textarea name="custom_settings" class="expand20-200" cols="" rows="15">'.htmlspecialchars($category['custom_settings']).'</textarea>
		</div>		
		';		
$tabs['category_advanced']=array('ADVANCED',$tmpcontent);

// tabber
$content.='
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
		jQuery(activeTab).fadeIn(0);
		return false;
	});
 
});
</script>
<div id="tab-container">
    <ul class="tabs" id="admin_orders">	
';
// custom hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['addTabsHook'])) {
	$params = array (
		'tabs' => &$tabs,
		'category' => &$category
	); 
	foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_edit_category.php']['addTabsHook'] as $funcRef) {
		t3lib_div::callUserFunction($funcRef, $params, $this);
	}
}	
// custom hook that can be controlled by third-party plugin eof
$count=0;
foreach ($tabs as $key => $value) {
	$count++;
	$content.='<li'.(($count==1)?' class="active"':'').'><a href="#'.$key.'">'.$value[0].'</a></li>';
}
$content.='        
    </ul>
    <div class="tab_container">
';
$count=0;	
foreach ($tabs as $key => $value) {
	$count++;
	$content.='
        <div style="display: block;" id="'.$key.'" class="tab_content">
			'.$value[1].'
        </div>
	';
}
$content.=$save_block.'
    </div>
</div>
';
// tabber eof
	$content.='
	<input name="cid" type="hidden" value="'.$category['categories_id'].'" />
	<input name="action" type="hidden" value="'.$_REQUEST['action'].'" />
	</form>';
	$content.='
			<div id="ajax_message_'.$category['categories_id'].'" class="ajax_message"></div>
	';
}
}
?>