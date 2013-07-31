<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

// now parse all the objects in the tmpl file
if ($this->conf['admin_edit_customer_tmpl_path']) {
	$template = $this->cObj->fileResource($this->conf['admin_edit_customer_tmpl_path']);
} else {
	$template = $this->cObj->fileResource(t3lib_extMgm::siteRelPath($this->extKey).'templates/admin_edit_customer.tmpl');
}
// Extract the subparts from the template
$subparts=array();
$subparts['template'] 	= $this->cObj->getSubpart($template, '###TEMPLATE###');

if ($this->post) {
	$erno=array();
	if ($this->post['tx_multishop_pi1']['cid']) {
		$edit_mode=1;
		$user=mslib_fe::getUser($this->post['tx_multishop_pi1']['cid']);
		if($user['email'] <> $this->post['email']) {
			// check if the emailaddress is not already in use
			$usercheck=mslib_fe::getUser($this->post['email'],'email');
			if ($usercheck['uid']) {
				$erno[]='Email address is already in use by '.$usercheck['name'].' ('.$usercheck['username'].')';
			}
		}
		if($user['username'] <> $this->post['username']) {
			// check if the emailaddress is not already in use
			$usercheck=mslib_fe::getUser($this->post['username'],'username');
			if ($usercheck['uid']) {
				$erno[]='Username is already in use by '.$usercheck['name'].' ('.$usercheck['username'].')';
			}
		}		
	} else {
		// check if the emailaddress is not already in use
		$usercheck=mslib_fe::getUser($this->post['email'],'email');
		if ($usercheck['uid']) {
			$erno[]='Email address is already in use by '.$usercheck['name'].' ('.$usercheck['username'].')';
		}	
		// check if the emailaddress is not already in use
		$usercheck=mslib_fe::getUser($this->post['username'],'username');
		if ($usercheck['uid']) {
			$erno[]='Username is already in use by '.$usercheck['name'].' ('.$usercheck['username'].')';
		}			
	}
	if (count($erno)) {
		$this->get['tx_multishop_pi1']['cid']=$this->post['tx_multishop_pi1']['cid'];
		$continue=0;
	} else {
		$continue=1;
	}
	if ($continue) {
		$updateArray=array();
		$updateArray['username']=$this->post['username'];
		if ($this->post['birthday']) {
			$updateArray['date_of_birth']=strtotime($this->post['birthday']);
		}
		$updateArray['first_name']=$this->post['first_name'];
		$updateArray['middle_name']=$this->post['middle_name'];
		$updateArray['last_name']=$this->post['last_name'];
		$updateArray['name']	=	$updateArray['first_name'].' '.$updateArray['middle_name'].' '.$updateArray['last_name'];
		$updateArray['name']	=	preg_replace('/\s+/', ' ', $updateArray['name']);
		
		$updateArray['gender']=$this->post['gender'];
		$updateArray['company']=$this->post['company'];
		$updateArray['street_name']=$this->post['street_name'];
		$updateArray['address_number']=$this->post['address_number'];
		$updateArray['address_ext']=$this->post['address_ext'];
		$updateArray['address']=$updateArray['street_name'].' '.$updateArray['address_number'].$updateArray['address_ext'];
		$updateArray['address']=preg_replace('/\s+/', ' ', $updateArray['address']);	
		$updateArray['zip']=$this->post['zip'];
		$updateArray['city']=$this->post['city'];
		$updateArray['country']=$this->post['country'];
		$updateArray['email']=$this->post['email'];
		$updateArray['telephone']=$this->post['telephone'];
		$updateArray['mobile']=$this->post['mobile'];
		$updateArray['tx_multishop_discount']=$this->post['tx_multishop_discount'];
		if ($this->post['password']) {		
			$updateArray['password'] = mslib_befe::getHashedPassword($this->post['password']);		
		}
		if (is_numeric($this->post['tx_multishop_pi1']['cid'])) {
			// update mode
			if (count($this->post['tx_multishop_pi1']['groups'])) {
				$updateArray['usergroup']=implode(",",$this->post['tx_multishop_pi1']['groups']);
				if (isset($user['usergroup'])) {
					// first get old usergroup data, cause maybe the user is also member of excluded usergroups that we should remain
					$old_usergroups=explode(",",$user['usergroup']);
					foreach ($this->excluded_userGroups as $usergroup) {
						if (in_array($usergroup,$old_usergroups)) {
							$updateArray['usergroup'].=','.$usergroup;
						}
					}
				}
			}
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['updateCustomerUserPreProc'])) {
				$params = array (
					'uid' => $this->post['tx_multishop_pi1']['cid'],									
					'updateArray' => &$updateArray,
					'user' => $user,
					'erno' => $erno
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['updateCustomerUserPreProc'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}	
			if (count($erno)) {
				$this->get['tx_multishop_pi1']['cid']=$this->post['tx_multishop_pi1']['cid'];
				$continue=0;
			} else {
				$continue=1;
			}
			if ($continue) {
				// custom hook that can be controlled by third-party plugin eof				
				$query = $GLOBALS['TYPO3_DB']->UPDATEquery('fe_users', 'uid='.$this->post['tx_multishop_pi1']['cid'],$updateArray);
				$res = $GLOBALS['TYPO3_DB']->sql_query($query);		
				// custom hook that can be controlled by third-party plugin
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['updateCustomerUserPostProc'])) {
					$params = array (
						'uid' => $this->post['tx_multishop_pi1']['cid']
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['updateCustomerUserPostProc'] as $funcRef) {
						t3lib_div::callUserFunction($funcRef, $params, $this);
					}
				}	
			}
			// custom hook that can be controlled by third-party plugin eof				
		} else {
			// insert mode
			if (count($this->post['tx_multishop_pi1']['groups'])) {
				$this->post['tx_multishop_pi1']['groups'][]=$this->conf['fe_customer_usergroup'];
				$updateArray['usergroup']=implode(",",$this->post['tx_multishop_pi1']['groups']);
			} else {
				$updateArray['usergroup'] =	$this->conf['fe_customer_usergroup'];
			}
			$updateArray['pid']					=	$this->conf['fe_customer_pid'];
			$updateArray['tx_multishop_code']	=	md5(uniqid('',TRUE));
			$updateArray['tstamp']				=	time();
			$updateArray['crdate']				=	time();
			if ($this->post['password']) {
				$updateArray['password'] = mslib_befe::getHashedPassword($this->post['password']);
			} else {
				$updateArray['password'] = mslib_befe::getHashedPassword(rand(1000000,9000000));
			}
			$updateArray['page_uid']			=	$this->shop_pid;			
//			$updateArray['tx_multishop_newsletter']			=	$address['tx_multishop_newsletter'];			
			$updateArray['cruser_id']			=	$GLOBALS['TSFE']->fe_user->user['uid'];
			// custom hook that can be controlled by third-party plugin
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['insertCustomerUserPreProc'])) {
				$params = array (
					'uid' => $this->post['tx_multishop_pi1']['cid'],									
					'updateArray' => &$updateArray
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['insertCustomerUserPreProc'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}	
			// custom hook that can be controlled by third-party plugin eof		
			$query = $GLOBALS['TYPO3_DB']->INSERTquery('fe_users', $updateArray);			
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);
			$customer_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
			// ADD TT_ADDRESS RECORD
			$insertArray=array();
			$insertArray['tstamp']						= time();
			$insertArray['company']						= $updateArray['company'];
			$insertArray['name']						= $updateArray['first_name'].' '.$updateArray['middle_name'].' '.$updateArray['last_name'];
			$insertArray['name']						= preg_replace('/\s+/', ' ', $insertArray['name']);
			$insertArray['first_name']					= $updateArray['first_name'];
			$insertArray['middle_name']					= $updateArray['middle_name'];
			$insertArray['last_name']					= $updateArray['last_name'];
			$insertArray['email']						= $updateArray['email'];
			if (!$updateArray['street_name']) {
				// fallback for old custom checkouts
				$insertArray['street_name']		=	$updateArray['address'];
				$insertArray['address_number']		=	$updateArray['address_number'];
				$insertArray['address_ext']			=	$updateArray['address_ext'];
				$insertArray['address']				=	$insertArray['street_name'].' '.$insertArray['address_number'].($insertArray['address_ext']?'-'.$insertArray['address_ext']:'');
				$insertArray['address']				= preg_replace('/\s+/', ' ', $insertArray['address']);				
				
			} else {
				$insertArray['street_name']			=	$updateArray['street_name'];
				$insertArray['address_number']		=	$updateArray['address_number'];
				$insertArray['address_ext']			=	$updateArray['address_ext'];
				$insertArray['address']				=	$updateArray['address'];				
			}
			
			$insertArray['zip']							= $updateArray['zip'];
			$insertArray['phone']						= $updateArray['telephone'];
			$insertArray['mobile']						= $updateArray['mobile'];
			$insertArray['city']						= $updateArray['city'];
			$insertArray['country']						= $updateArray['country'];
			$insertArray['gender'] 						= $updateArray['gender'];
			$insertArray['birthday'] 					= strtotime($updateArray['birthday']);
				
			if ($updateArray['gender'] == 'm') {
				$insertArray['title'] 					= 'Mr.';
					
			} else if ($updateArray['gender'] == 'f') {
				$insertArray['title'] 					= 'Mrs.';
			}					
			$insertArray['region']						= $updateArray['state'];
			
			$insertArray['pid']							= $this->conf['fe_customer_pid'];
			$insertArray['page_uid']					= $this->shop_pid;
			$insertArray['tstamp']						= time();
			$insertArray['tx_multishop_address_type'] 	= 'billing';
			$insertArray['tx_multishop_default'] 		= 1;
			$insertArray['tx_multishop_customer_id'] 	= $customer_id;
			
			$query = $GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);			
			$res = $GLOBALS['TYPO3_DB']->sql_query($query);	
			
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['insertCustomerUserPostProc'])) {
				$params = array (
					'uid' => $customer_id
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['insertCustomerUserPostProc'] as $funcRef) {
					t3lib_div::callUserFunction($funcRef, $params, $this);
				}
			}
		}
		/*
		$updateArray['delivery_company']=
		$updateArray['delivery_first_name']=
		$updateArray['delivery_middle_name']=
		$updateArray['delivery_last_name']=
		$updateArray['delivery_address']=
		$updateArray['delivery_address_number']=
		$updateArray['delivery_zip']=
		$updateArray['delivery_city']=
		$updateArray['delivery_email']=
		$updateArray['delivery_telephone']=
		$updateArray['delivery_mobile']=
		*/
		echo '
		<script type="text/javascript">
		parent.window.location.reload();
		</script>
		';	
		exit();
	}
}
// load enabled countries to array
$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
$enabled_countries=array();
while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2)) != false) {
	$enabled_countries[]=$row2;
}	
$regex = "/^[^\\\W][a-zA-Z0-9\\\_\\\-\\\.]+([a-zA-Z0-9\\\_\\\-\\\.]+)*\\\@[a-zA-Z0-9\\\_\\\-\\\.]+([a-zA-Z0-9\\\_\\\-\\\.]+)*\\\.[a-zA-Z]{2,4}$/";
$regex_for_character = "/[^0-9]$/";
if(!$this->post && is_numeric($this->get['tx_multishop_pi1']['cid'])) {	
	$user=mslib_fe::getUser($this->get['tx_multishop_pi1']['cid']);
	$this->post=$user;
}
$head='';
$head.='
	 <script type="text/javascript">
				/* <![CDATA[ */
				jQuery(function(){
				 jQuery("#username").validate({
							expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('username_is_required').'"
					 });
';
if ($_REQUEST['action'] != 'edit_customer') {
$head.='						 
				jQuery("#password").validate({
							expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('password_is_required', 'Password is required').'"
					 });
';
$head.='
				jQuery("#company").validate();
				jQuery("#radio").validate({
				  expression: "if (jQuery(\'#radio\').is(\':checked\') || jQuery(\'#radio2\').is(\':checked\')) return true; else return false;",
				  message: "'.$this->pi_getLL('title_is_required', 'Title is required').'"
				});
				  
			  jQuery("#first_name").validate({
						  expression: "if (VAL.match('.$regex_for_character.')) return true; else return false;",
						  message: "'.$this->pi_getLL('first_name_required').'"
					 });
			
			  jQuery("#last_name").validate({
						  expression: "if (VAL.match('.$regex_for_character.')) return true; else return false;",
						  message: "'.$this->pi_getLL('surname_is_required').'"
					 });
			  jQuery("#zip").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('zip_is_required').'"
					 });
			  jQuery("#address").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('street_address_is_required').'"
					 });
			  jQuery("#delivery_address_number").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('street_number_is_required').' (delivery address)."
					 });
			  jQuery("#city").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('city_is_required').'"
					 });
			  jQuery("#city").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('city_is_required').' (delivery address)."
					 });
			  '.((count($enabled_countries) > 1)? '
					jQuery("#country").validate({
						  expression: "if (VAL != \'\') return true; else return false;",
							message: "'.$this->pi_getLL('country_is_required').'"
					 });
					':'').'
			  '.((count($enabled_countries) > 1)? '
					jQuery("#delivery_country").validate({
						  expression: "if (VAL != \'\') return true; else return false;",
							message: "'.$this->pi_getLL('country_is_required').' (delivery address)."
					 });
					':'').'
					jQuery("#email").validate({
					expression: "if (VAL.match('.$regex.')) return true; else return false;",
					message: "'.$this->pi_getLL('email_is_required').'"
					 });
					jQuery("#address_number").validate({
						  expression: "if (VAL) return true; else return false;",
							message: "'.$this->pi_getLL('street_number_is_required').'"
					 });
					jQuery("#telephone").validate({
					  expression: "if (!isNaN(VAL) && VAL) return true; else return false;",
						message: "'.$this->pi_getLL('telephone_is_required').'"
					});
';
}
$head.='
					
				});
				/* ]]> */
				jQuery().ready(function(){
					jQuery("#company").bind("keyup",function(){
						if(jQuery(this).val() ==  "" ){
							jQuery(this).next().removeClass("error-yes");
						}
					});
					jQuery("#company").bind("keypress",function(){
						if(jQuery(this).length > 0 ){
							jQuery(this).next().addClass("error-yes");
						} 
					});
					jQuery("#delivery_company").bind("keypress",function(){
						if(jQuery(this).length > 0 ){
							jQuery(this).next().addClass("error-yes");
						} 
					});
					jQuery("#delivery_company").bind("keyup",function(){
						if(jQuery(this).val() ==  "" ){
							jQuery(this).next().removeClass("error-yes");
						}
					});
					//Validation for midle name
					jQuery("#middle_name").bind("keyup click",function(){
						jQuery(this).next().removeClass("left-this");
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
					});
					jQuery("#delivery_middle_name").bind("keyup click",function(){
						jQuery(this).next().removeClass("left-this");
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
					});
					jQuery("#middle_name").bind("blur",function(){
						if (jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).next().hasClass("error-no")){
							jQuery(this).next().removeClass("left-this");
							jQuery(this).next().removeClass("error-no");
						} else {
							jQuery(this).next().addClass("left-this");
							jQuery(this).next().addClass("error-yes");
							jQuery(this).next().removeClass("error-no");
						}
						jQuery(this).next().addClass("error-yes");
						jQuery(this).next().addClass("left-this");
					});
					jQuery("#delivery_middle_name").bind("blur",function(){
						if (jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).next().hasClass("error-no")){
							jQuery(this).next().removeClass("left-this");
							jQuery(this).next().removeClass("error-no");
						} else {
							jQuery(this).next().addClass("left-this");
							jQuery(this).next().addClass("error-yes");
							jQuery(this).next().removeClass("error-no");
						}
						jQuery(this).next().addClass("error-yes");
						jQuery(this).next().addClass("left-this");
					});
					//validation for mobile
					jQuery("#mobile").bind("keyup click",function(){
						jQuery(this).next().removeClass("left-this");
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
					});
					jQuery("#mobile").bind("blur",function(){
						if (jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).next().hasClass("error-no")){
							jQuery(this).next().removeClass("left-this");
							jQuery(this).next().removeClass("error-no");
						} else {
							jQuery(this).next().addClass("left-this");
							jQuery(this).next().addClass("error-yes");
							jQuery(this).next().removeClass("error-no");
						}
						jQuery(this).next().addClass("error-yes");
						jQuery(this).next().addClass("left-this");
					});
					//validation for delivery mobile
					jQuery("#delivery_mobile").bind("keyup click",function(){
						jQuery(this).next().removeClass("left-this");
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).val() ==  ""){
							//jQuery(this).next().addClass("left-this");
						}
					});
					jQuery("#delivery_mobile").bind("blur",function(){
						/**
						if (jQuery(this).val() ==  ""){
							jQuery(this).next().addClass("left-this");
						}
						if (jQuery(this).hasClass("ErrorField") && jQuery(this).next().hasClass("error-no")){
							jQuery(this).next().removeClass("left-this");
							jQuery(this).next().removeClass("error-no");
						} else {
							jQuery(this).next().addClass("left-this");
							jQuery(this).next().addClass("error-yes");
							jQuery(this).next().removeClass("error-no");
						}
						*/
						//jQuery(this).next().addClass("error-yes");
						//jQuery(this).next().addClass("left-this");
					});
					//Display BOX Message
					jQuery("#birthday_visitor").datepicker({ 
													dateFormat: "'.$this->pi_getLL('locale_date_format', 'm/d/Y').'",
													altField: "#birthday",
													altFormat: "yy-mm-dd",
													changeMonth: true,
													changeYear: true,
													showOtherMonths: true,  
													yearRange: "'.(date("Y")-100).':'.date("Y").'" 
													});
					jQuery("#delivery_birthday_visitor").datepicker({ 
						dateFormat: "'.$this->pi_getLL('locale_date_format', 'm/d/Y').'",
						altField: "#delivery_birthday",
						altFormat: "yy-mm-dd",
						changeMonth: true,
						changeYear: true,
						showOtherMonths: true,  
						yearRange: "'.(date("Y")-100).':'.date("Y").'" 
					});
				}); //end of first load
			 </script>';
$GLOBALS['TSFE']->additionalHeaderData[] = $head;
$head='';
if (is_array($erno) and count($erno) > 0) {
	$content.='<div class="error_msg">';
	$content.='<h3>'.$this->pi_getLL('the_following_errors_occurred').'</h3><ul class="ul-display-error">';
	$content.='<li class="item-error" style="display:none"></li>';
	foreach ($erno as $item) {
		$content.='<li class="item-error">'.$item.'</li>';
	}
	$content.='</ul>';
	$content.='</div>';
} else {
	$content.='<div class="error_msg" style="display:none">';
	$content.='<h3>'.$this->pi_getLL('the_following_errors_occurred').'</h3><ul class="ul-display-error">';
	$content.='<li class="item-error" style="display:none"></li>';
	$content.='</ul></div>';
}

// load countries
$countries_input = '';
if (count($enabled_countries) ==1)  {
	$countries_input='<input name="country" type="hidden" value="'.t3lib_div::strtolower($enabled_countries[0]['cn_short_en']).'" />';
	$countries_input.='<input name="delivery_country" type="hidden" value="'.t3lib_div::strtolower($enabled_countries[0]['cn_short_en']).'" />';
} else {
	foreach ($enabled_countries as $country) {
		$tmpcontent_con.='<option value="'.t3lib_div::strtolower($country['cn_short_en']).'" '.((t3lib_div::strtolower($this->post['country'])==t3lib_div::strtolower($country['cn_short_en']))?'selected':'').'>'.htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang,$country['cn_short_en'])).'</option>';
		$tmpcontent_con_delivery.='<option value="'.t3lib_div::strtolower($country['cn_short_en']).'" '.(($this->post['delivery_country']==t3lib_div::strtolower($country['cn_short_en']))?'selected':'').'>'.htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang,$country['cn_short_en'])).'</option>';
	}
	if ($tmpcontent_con) {
		$countries_input='
		<label for="country" id="account-country">'.ucfirst($this->pi_getLL('country')).'*</label>
		<select name="country" id="country" class="country">
		<option value="">'.ucfirst($this->pi_getLL('choose_country')).'</option>
		'.$tmpcontent_con.'
		</select><span class="error-space"></span>';
	}			
}
// country eof
// now lets load the users 
$groups=mslib_fe::getUserGroups($this->conf['fe_customer_pid']);
$customer_groups_input = '';
if (is_array($groups) and count($groups)) {
	$customer_groups_input.='<div class="account-field multiselect_horizontal"><label>'.$this->pi_getLL('member_of').'</label><select id="groups" class="multiselect" multiple="multiple" name="tx_multishop_pi1[groups][]">'."\n";
	if ($erno) {
		$this->post['usergroup']=implode(",",$this->post['tx_multishop_pi1']['groups']);
	}
	foreach ($groups as $group) {
		$customer_groups_input.='<option value="'.$group['uid'].'"'.(mslib_fe::inUserGroup($group['uid'],$this->post['usergroup'])?' selected="selected"':'').'>'.$group['title'].'</option>'."\n";
	}
	$customer_groups_input.='</select></div>'."\n";
}
$login_as_this_user_link = '';
if ($this->get['tx_multishop_pi1']['cid']) {
	$login_as_this_user_link = '<a href="'.mslib_fe::typolink($this->shop_pid.',2003','tx_multishop_pi1[page_section]=admin_customers&login_as_customer=1&customer_id='.$this->get['tx_multishop_pi1']['cid']).'" target="_parent" class="msadmin_button">'.$this->pi_getLL('login_as_user').'</a>';	
}

$subpartArray = array();
if ($_REQUEST['action'] == 'edit_customer') {
	$subpartArray['###LABEL_USERNAME###'] 				= ucfirst($this->pi_getLL('username'));
	$subpartArray['###USERNAME_READONLY###'] 			= ($this->get['action']=='edit_customer'?'readonly="readonly"':'');
	$subpartArray['###VALUE_USERNAME###'] 				= htmlspecialchars($this->post['username']);
	$subpartArray['###LABEL_PASSWORD###'] 				= ucfirst($this->pi_getLL('password'));
	$subpartArray['###VALUE_PASSWORD###'] 				= '';
	$subpartArray['###LABEL_GENDER###'] 				= ucfirst($this->pi_getLL('title'));
	$subpartArray['###GENDER_MR_CHECKED###'] 			= (($this->post['gender']=='0')?'checked="checked"':'');
	$subpartArray['###LABEL_GENDER_MR###'] 				= ucfirst($this->pi_getLL('mr'));
	$subpartArray['###GENDER_MRS_CHECKED###'] 			= (($this->post['gender']=='1')?'checked="checked"':'');
	$subpartArray['###LABEL_GENDER_MRS###'] 			= ucfirst($this->pi_getLL('mrs'));
	$subpartArray['###LABEL_FIRSTNAME###'] 				= ucfirst($this->pi_getLL('first_name'));
	$subpartArray['###VALUE_FIRSTNAME###'] 				= htmlspecialchars($this->post['first_name']);
	$subpartArray['###LABEL_MIDDLENAME###'] 			= ucfirst($this->pi_getLL('middle_name'));
	$subpartArray['###VALUE_MIDDLENAME###'] 			= htmlspecialchars($this->post['middle_name']);
	$subpartArray['###LABEL_LASTNAME###'] 				= ucfirst($this->pi_getLL('last_name'));
	$subpartArray['###VALUE_LASTNAME###'] 				= htmlspecialchars($this->post['last_name']);
	$subpartArray['###LABEL_COMPANY###'] 				= ucfirst($this->pi_getLL('company'));
	$subpartArray['###VALUE_COMPANY###'] 				= htmlspecialchars($this->post['company']);
	$subpartArray['###LABEL_STREET_ADDRESS###'] 		= ucfirst($this->pi_getLL('street_address'));
	$subpartArray['###VALUE_STREET_ADDRESS###'] 		= htmlspecialchars($this->post['street_name']);
	$subpartArray['###LABEL_STREET_ADDRESS_NUMBER###'] 	= ucfirst($this->pi_getLL('street_address_number'));
	$subpartArray['###VALUE_STREET_ADDRESS_NUMBER###'] 	= htmlspecialchars($this->post['address_number']);
	$subpartArray['###LABEL_ADDRESS_EXTENTION###'] 		= ucfirst($this->pi_getLL('address_extension'));
	$subpartArray['###VALUE_ADDRESS_EXTENTION###'] 		= htmlspecialchars($this->post['address_ext']);
	$subpartArray['###LABEL_POSTCODE###'] 				= ucfirst($this->pi_getLL('zip'));
	$subpartArray['###VALUE_POSTCODE###'] 				= htmlspecialchars($this->post['zip']);
	$subpartArray['###LABEL_CITY###'] 					= ucfirst($this->pi_getLL('city'));
	$subpartArray['###VALUE_CITY###'] 					= htmlspecialchars($this->post['city']);
	$subpartArray['###COUNTRIES_INPUT###'] 				= $countries_input;
	$subpartArray['###LABEL_EMAIL###'] 					= ucfirst($this->pi_getLL('e-mail_address'));
	$subpartArray['###VALUE_EMAIL###'] 					= htmlspecialchars($this->post['email']);
	$subpartArray['###LABEL_TELEPHONE###'] 				= ucfirst($this->pi_getLL('telephone'));
	$subpartArray['###VALUE_TELEPHONE###'] 				= htmlspecialchars($this->post['telephone']);
	$subpartArray['###LABEL_MOBILE###'] 				= ucfirst($this->pi_getLL('mobile'));
	$subpartArray['###VALUE_MOBILE###'] 				= htmlspecialchars($this->post['mobile']);
	$subpartArray['###LABEL_BIRTHDATE###'] 				= ucfirst($this->pi_getLL('birthday'));
	$subpartArray['###VALUE_VISIBLE_BIRTHDATE###'] 		= ($this->post['date_of_birth']?htmlspecialchars(strftime("%x",  $this->post['date_of_birth'])):'');
	$subpartArray['###VALUE_HIDDEN_BIRTHDATE###'] 		= ($this->post['date_of_birth']?htmlspecialchars(strftime("%F",  $this->post['date_of_birth'])):'');
	$subpartArray['###LABEL_DISCOUNT###'] 				= ucfirst($this->pi_getLL('discount'));
	$subpartArray['###VALUE_DISCOUNT###'] 				= ($this->post['tx_multishop_discount']>0 ?htmlspecialchars($this->post['tx_multishop_discount']):'');
	$subpartArray['###CUSTOMER_GROUPS_INPUT###'] 		= $customer_groups_input;
	$subpartArray['###VALUE_CUSTOMER_ID###'] 			= $this->get['tx_multishop_pi1']['cid'];
	if ($_GET['action'] == 'edit_customer') {
		$subpartArray['###LABEL_BUTTON_SAVE###'] 		= ucfirst($this->pi_getLL('update_account'));
	} else {
		$subpartArray['###LABEL_BUTTON_SAVE###'] 		= ucfirst($this->pi_getLL('save'));
	}
	$subpartArray['###LOGIN_AS_THIS_USER_LINK###'] 		= $login_as_this_user_link;
} else {
	if ($this->post['gender']=='1') {
		$mr_checked 	= '';
		$mrs_checked 	= 'checked="checked"';
	} else {
		$mr_checked 	= 'checked="checked"';
		$mrs_checked 	= '';
	}
	$subpartArray['###LABEL_USERNAME###'] 				= ucfirst($this->pi_getLL('username'));
	$subpartArray['###USERNAME_READONLY###'] 			= ($this->get['action']=='edit_customer'?'readonly="readonly"':'');
	$subpartArray['###VALUE_USERNAME###'] 				= htmlspecialchars($this->post['username']);
	$subpartArray['###VALUE_PASSWORD###'] 				= htmlspecialchars($this->post['password']);
	$subpartArray['###LABEL_PASSWORD###'] 				= ucfirst($this->pi_getLL('password'));
	$subpartArray['###LABEL_GENDER###'] 				= ucfirst($this->pi_getLL('title'));
	$subpartArray['###GENDER_MR_CHECKED###'] 			= $mr_checked;
	$subpartArray['###LABEL_GENDER_MR###'] 				= ucfirst($this->pi_getLL('mr'));
	$subpartArray['###GENDER_MRS_CHECKED###'] 			= $mrs_checked;
	$subpartArray['###LABEL_GENDER_MRS###'] 			= ucfirst($this->pi_getLL('mrs'));
	$subpartArray['###LABEL_FIRSTNAME###'] 				= ucfirst($this->pi_getLL('first_name'));
	$subpartArray['###VALUE_FIRSTNAME###'] 				= htmlspecialchars($this->post['first_name']);
	$subpartArray['###LABEL_MIDDLENAME###'] 			= ucfirst($this->pi_getLL('middle_name'));
	$subpartArray['###VALUE_MIDDLENAME###'] 			= htmlspecialchars($this->post['middle_name']);
	$subpartArray['###LABEL_LASTNAME###'] 				= ucfirst($this->pi_getLL('last_name'));
	$subpartArray['###VALUE_LASTNAME###'] 				= htmlspecialchars($this->post['last_name']);
	$subpartArray['###LABEL_COMPANY###'] 				= ucfirst($this->pi_getLL('company'));
	$subpartArray['###VALUE_COMPANY###'] 				= htmlspecialchars($this->post['company']);
	$subpartArray['###LABEL_STREET_ADDRESS###'] 		= ucfirst($this->pi_getLL('street_address'));
	$subpartArray['###VALUE_STREET_ADDRESS###'] 		= htmlspecialchars($this->post['street_name']);
	$subpartArray['###LABEL_STREET_ADDRESS_NUMBER###'] 	= ucfirst($this->pi_getLL('street_address_number'));
	$subpartArray['###VALUE_STREET_ADDRESS_NUMBER###'] 	= htmlspecialchars($this->post['address_number']);
	$subpartArray['###LABEL_ADDRESS_EXTENTION###'] 		= ucfirst($this->pi_getLL('address_extension'));
	$subpartArray['###VALUE_ADDRESS_EXTENTION###'] 		= htmlspecialchars($this->post['address_ext']);
	$subpartArray['###LABEL_POSTCODE###'] 				= ucfirst($this->pi_getLL('zip'));
	$subpartArray['###VALUE_POSTCODE###'] 				= htmlspecialchars($this->post['zip']);
	$subpartArray['###LABEL_CITY###'] 					= ucfirst($this->pi_getLL('city'));
	$subpartArray['###VALUE_CITY###'] 					= htmlspecialchars($this->post['city']);
	$subpartArray['###COUNTRIES_INPUT###'] 				= $countries_input;
	$subpartArray['###LABEL_EMAIL###'] 					= ucfirst($this->pi_getLL('e-mail_address'));
	$subpartArray['###VALUE_EMAIL###'] 					= htmlspecialchars($this->post['email']);
	$subpartArray['###LABEL_TELEPHONE###'] 				= ucfirst($this->pi_getLL('telephone'));
	$subpartArray['###VALUE_TELEPHONE###'] 				= htmlspecialchars($this->post['telephone']);
	$subpartArray['###LABEL_MOBILE###'] 				= ucfirst($this->pi_getLL('mobile'));
	$subpartArray['###VALUE_MOBILE###'] 				= htmlspecialchars($this->post['mobile']);
	$subpartArray['###LABEL_BIRTHDATE###'] 				= ucfirst($this->pi_getLL('birthday'));
	$subpartArray['###VALUE_VISIBLE_BIRTHDATE###'] 		= ($this->post['date_of_birth']?htmlspecialchars(strftime("%x",  $this->post['date_of_birth'])):'');
	$subpartArray['###VALUE_HIDDEN_BIRTHDATE###'] 		= ($this->post['date_of_birth']?htmlspecialchars(strftime("%F",  $this->post['date_of_birth'])):'');
	$subpartArray['###LABEL_DISCOUNT###'] 				= ucfirst($this->pi_getLL('discount'));
	$subpartArray['###VALUE_DISCOUNT###'] 				= ($this->post['tx_multishop_discount']>0 ?htmlspecialchars($this->post['tx_multishop_discount']):'');
	$subpartArray['###CUSTOMER_GROUPS_INPUT###'] 		= $customer_groups_input;
	$subpartArray['###VALUE_CUSTOMER_ID###'] 			= '';
	$subpartArray['###LABEL_BUTTON_SAVE###'] 			= ucfirst($this->pi_getLL('save'));
	$subpartArray['###LOGIN_AS_THIS_USER_LINK###'] 		= '';
}
// custom page hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['adminEditCustomerTmplPreProc'])) {
	$params = array (
		'subpartArray' => &$subpartArray,
		'user' => &$user
	); 
	foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer.php']['adminEditCustomerTmplPreProc'] as $funcRef) {
		t3lib_div::callUserFunction($funcRef, $params, $this);
	}
}	
// custom page hook that can be controlled by third-party plugin eof	
$content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
?>