<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$this->cObj->data['header']='Customers';
if ($GLOBALS['TSFE']->fe_user->user['uid'] and $this->get['login_as_customer'] && is_numeric($this->get['customer_id'])) {
	$user=mslib_fe::getUser($this->get['customer_id']);
	if ($user['uid']) {
		mslib_befe::loginAsUser($user['uid'], 'admin_customers');
	}
}
$GLOBALS['TSFE']->additionalHeaderData[]='
<script type="text/javascript">
var checkAll = function() {
	for (var x = 0; x < 100; x++) {
		if (document.getElementById(\'ordid_\' + x) != null) {
			document.getElementById(\'ordid_\' + x).checked = true;
		}
	}
}
var uncheckAll = function() {
	for (var x = 0; x < 100; x++) {
		if (document.getElementById(\'ordid_\' + x) != null) {
			document.getElementById(\'ordid_\' + x).checked = false;
		}
	}
}
</script>';
if (is_numeric($this->get['disable']) and is_numeric($this->get['customer_id'])) {
	if ($this->get['disable']) {
		mslib_befe::disableCustomer($this->get['customer_id']);
	} else {
		mslib_befe::enableCustomer($this->get['customer_id']);
	}
} else {
	if (is_numeric($this->get['delete']) and is_numeric($this->get['customer_id'])) {
		mslib_befe::deleteCustomer($this->get['customer_id']);
	}
}
$this->hideHeader=1;
if ($this->get['Search'] and ($this->get['limit']!=$this->cookie['limit'])) {
	$this->cookie['limit']=$this->get['limit'];
	$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
	$GLOBALS['TSFE']->storeSessionData();
}
if ($this->cookie['limit']) {
	$this->get['limit']=$this->cookie['limit'];
} else {
	$this->get['limit']=10;
}
$this->ms['MODULES']['PAGESET_LIMIT']=$this->get['limit'];
$this->searchKeywords=array();
if ($this->get['tx_multishop_pi1']['searchByChar']) {
	switch ($this->get['tx_multishop_pi1']['searchByChar']) {
		case '0-9':
			for ($i=0; $i<10; $i++) {
				$this->searchKeywords[]=$i;
			}
			break;
		case '#':
			$this->searchKeywords[]='#';
			break;
		case 'all':
			break;
		default:
			$this->searchKeywords[]=$this->get['tx_multishop_pi1']['searchByChar'];
			break;
	}
	$this->searchMode='keyword%';
} elseif ($this->get['tx_multishop_pi1']['keyword']) {
	//  using $_REQUEST cause TYPO3 converts "Command & Conquer" to "Conquer" (the & sign sucks ass)
	$this->get['tx_multishop_pi1']['keyword']=trim($this->get['tx_multishop_pi1']['keyword']);
	$this->get['tx_multishop_pi1']['keyword']=$GLOBALS['TSFE']->csConvObj->utf8_encode($this->get['tx_multishop_pi1']['keyword'], $GLOBALS['TSFE']->metaCharset);
	$this->get['tx_multishop_pi1']['keyword']=$GLOBALS['TSFE']->csConvObj->entities_to_utf8($this->get['tx_multishop_pi1']['keyword'], true);
	$this->get['tx_multishop_pi1']['keyword']=mslib_fe::RemoveXSS($this->get['tx_multishop_pi1']['keyword']);
	$this->searchKeywords[]=$this->get['tx_multishop_pi1']['keyword'];
	$this->searchMode='%keyword%';
}
if (is_numeric($this->get['p'])) {
	$p=$this->get['p'];
}
if ($p>0) {
	$offset=(((($p)*$this->ms['MODULES']['PAGESET_LIMIT'])));
} else {
	$p=0;
	$offset=0;
}
$user=$GLOBALS['TSFE']->fe_user->user;
$option_search=array(
	"f.company"=>$this->pi_getLL('admin_company'),
	"f.name"=>$this->pi_getLL('admin_customer_name'),
	"f.username"=>$this->pi_getLL('username'),
	"f.email"=>$this->pi_getLL('admin_customer_email'),
	"f.uid"=>$this->pi_getLL('admin_customer_id')
);
asort($option_search);
$option_item='';
foreach ($option_search as $key=>$val) {
	$option_item.='<option value="'.$key.'" '.($this->get['tx_multishop_pi1']['search_by']==$key ? "selected" : "").'>'.$val.'</option>';
}
$searchCharNav='<div id="msAdminSearchByCharNav"><ul>';
$chars=array();
$chars=array(
	'0-9',
	'a',
	'b',
	'c',
	'd',
	'e',
	'f',
	'g',
	'h',
	'i',
	'j',
	'k',
	'l',
	'm',
	'n',
	'o',
	'p',
	'q',
	'r',
	's',
	't',
	'u',
	'v',
	'w',
	'x',
	'y',
	'z',
	'#',
	'all'
);
foreach ($chars as $char) {
	$searchCharNav.='<li><a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[searchByChar]='.$char.'&tx_multishop_pi1[page_section]=admin_customers').'">'.strtoupper($char).'</a></li>';
}
$searchCharNav.='</ul></div>';
$formTopSearch='
<div id="search-orders">
	<table width="100%">
		<tr>
			<td valign="top">				
					<input name="tx_multishop_pi1[do_search]" type="hidden" value="1" />
					<input name="id" type="hidden" value="'.$this->shop_pid.'" />
					<input name="type" type="hidden" value="2003" />
					<input name="tx_multishop_pi1[page_section]" type="hidden" value="admin_customers" />
					<div class="formfield-container-wrapper">
					<div class="formfield-wrapper">
						<label>'.ucfirst($this->pi_getLL('keyword')).'</label><input type="text" name="tx_multishop_pi1[keyword]" id="skeyword" value="'.htmlspecialchars($this->get['tx_multishop_pi1']['keyword']).'" />
						<select name="tx_multishop_pi1[search_by]">
							<option value="all">'.$this->pi_getLL('all').'</option>
							'.$option_item.'
						</select>						
						<input type="submit" name="Search" class="msadmin_button" value="'.$this->pi_getLL('search').'" />
					</div>	
					<div class="formfield-wrapper">
						<label for="includeDeletedAccounts">'.$this->pi_getLL('show_deleted_accounts').'</label>
						<input type="checkbox" class="PrettyInput" id="includeDeletedAccounts" name="tx_multishop_pi1[show_deleted_accounts]" value="1"'.($this->get['tx_multishop_pi1']['show_deleted_accounts'] ? ' checked="checked"' : '').' />
					</div>	
					</div>
			</td>
			<td nowrap valign="top" align="right" class="searchLimit">
				<div style="float:right;">			
					<label>'.$this->pi_getLL('limit_number_of_records_to').':</label>
					<select name="limit">';
$limits=array();
$limits[]='10';
$limits[]='15';
$limits[]='20';
$limits[]='25';
$limits[]='30';
$limits[]='40';
$limits[]='50';
$limits[]='100';
$limits[]='150';
$limits[]='200';
$limits[]='250';
$limits[]='300';
$limits[]='350';
$limits[]='400';
$limits[]='450';
$limits[]='500';
foreach ($limits as $limit) {
	$formTopSearch.='<option value="'.$limit.'"'.($limit==$this->get['limit'] ? ' selected="selected"' : '').'>'.$limit.'</option>';
}
$formTopSearch.='
					</select>
				</div>
			</td>			
		</tr>
	</table>
	'.$searchCharNav.'
</div>';
$filter=array();
$having=array();
$match=array();
$orderby=array();
$where=array();
$orderby=array();
$select=array();
if (strlen($this->get['tx_multishop_pi1']['keyword'])>0) {
	switch ($this->get['tx_multishop_pi1']['search_by']) {
		case 'f.uid':
			$filter[]="f.uid like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			break;
		case 'f.company':
			$filter[]="f.company like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			break;
		case 'f.name':
			$filter[]="f.name like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			break;
		case 'f.email':
			$filter[]="f.email like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			break;
		case 'f.username':
			$filter[]="f.username like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			break;
		default:
			$option_fields=$option_search;
			$items=array();
			foreach ($option_fields as $fields=>$label) {
				$items[]=$fields." LIKE '%".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
			}
			$filter[]='('.implode(" or ", $items).')';
			break;
	}
} else {
	if (count($this->searchKeywords)) {
		$keywordOr=array();
		foreach ($this->searchKeywords as $searchKeyword) {
			if ($searchKeyword) {
				switch ($this->searchMode) {
					case 'keyword%':
						$this->sqlKeyword=addslashes($searchKeyword).'%';
						break;
					case '%keyword%':
					default:
						$this->sqlKeyword='%'.addslashes($searchKeyword).'%';
						break;
				}
				$keywordOr[]="f.company like '".$this->sqlKeyword."'";
				$keywordOr[]="f.name like '".$this->sqlKeyword."'";
			}
		}
		$filter[]="(".implode(" OR ", $keywordOr).")";
	}
}
switch ($this->get['tx_multishop_pi1']['order_by']) {
	case 'username':
		$order_by='f.username';
		break;
	case 'company':
		$order_by='f.company';
		break;
	case 'crdate':
		$order_by='f.crdate';
		break;
	case 'lastlogin':
		$order_by='f.lastlogin';
		break;
	case 'grand_total':
		$order_by='grand_total';
		break;
	case 'grand_total_this_year':
		$order_by='grand_total_this_year';
		break;
	case 'disable':
		$order_by='f.disable';
		break;
	case 'uid':
	default:
		$order_by='f.uid';
		break;
}
switch ($this->get['tx_multishop_pi1']['order']) {
	case 'a':
		$order='asc';
		$order_link='d';
		break;
	case 'd':
	default:
		$order='desc';
		$order_link='a';
		break;
}
$orderby[]=$order_by.' '.$order;
if (!$this->get['tx_multishop_pi1']['show_deleted_accounts']) {
	$filter[]='(f.deleted=0)';
}
if (!$this->masterShop) {
	$filter[]="f.page_uid='".$this->shop_pid."'";
}
if (!$this->masterShop) {
	$filter[]=$GLOBALS['TYPO3_DB']->listQuery('usergroup', $this->conf['fe_customer_usergroup'], 'fe_users');
}
// subquery to summarize grand total per customer
$select[]='(select sum(grand_total) from tx_multishop_orders where customer_id=f.uid) as grand_total';
// subquery to summarize grand total by year, per customer
$startTime=strtotime(date("Y-01-01 00:00:00"));
$endTime=strtotime(date("Y-12-31 23:59:59"));
$select[]='(select sum(grand_total) from tx_multishop_orders where customer_id=f.uid and crdate BETWEEN '.$startTime.' and '.$endTime.') as grand_total_this_year';
$pageset=mslib_fe::getCustomersPageSet($filter, $offset, $this->ms['MODULES']['PAGESET_LIMIT'], $orderby, $having, $select, $where);
$customers=$pageset['customers'];
if ($pageset['total_rows']>0 && isset($pageset['customers'])) {
	require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/admin_customers_listing.php');
	// pagination
	if (!$this->ms['nopagenav'] and $pageset['total_rows']>$this->ms['MODULES']['PAGESET_LIMIT']) {
		require(t3lib_extMgm::extPath('multishop').'scripts/admin_pages/includes/admin_pagination.php');
		$content.=$tmp;
	}
	// pagination eof	
}
$tmp=$content;
$content='';
$tabs=array();
$tabs['CustomersListing']=array(
	$this->pi_getLL('customers'),
	$tmp
);
$tmp='';
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
             		
    jQuery(\'#order_date_from\').datetimepicker({
    	dateFormat: \'dd/mm/yy\',
        showSecond: true,
		timeFormat: \'HH:mm:ss\'         		
    });
             		
	jQuery(\'#order_date_till\').datetimepicker({
    	dateFormat: \'dd/mm/yy\',
        showSecond: true,
		timeFormat: \'HH:mm:ss\'         		
    });
 
});
</script>
<div id="tab-container">
    <ul class="tabs" id="admin_orders">';
$count=0;
foreach ($tabs as $key=>$value) {
	$count++;
	$content.='<li'.(($count==1) ? ' class="active"' : '').'><a href="#'.$key.'">'.$value[0].'</a></li>';
}
$content.='
    </ul>
    <div class="tab_container">
	<form id="form1" name="form1" method="get" action="index.php">
	'.$formTopSearch.'
	</form>
	';
$count=0;
foreach ($tabs as $key=>$value) {
	$count++;
	$content.='
        <div style="display: block;" id="'.$key.'" class="tab_content">
			'.$value[1].'
        </div>
	';
}
$content.='
    </div>
</div>';
$content.='<p class="extra_padding_bottom"><a class="msadmin_button" href="'.mslib_fe::typolink().'">'.t3lib_div::strtoupper($this->pi_getLL('admin_close_and_go_back_to_catalog')).'</a></p>';
$content='<div class="fullwidth_div">'.mslib_fe::shadowBox($content).'</div>';
?>