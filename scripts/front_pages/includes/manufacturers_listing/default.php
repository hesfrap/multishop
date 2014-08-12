<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$output=array();
// now parse all the objects in the tmpl file
if ($this->conf['manufacturers_listing_tmpl_path']) {
	$template=$this->cObj->fileResource($this->conf['manufacturers_listing_tmpl_path']);
} else {
	$template=$this->cObj->fileResource(t3lib_extMgm::siteRelPath($this->extKey).'templates/manufacturers_listing.tmpl');
}
// Extract the subparts from the template
$subparts=array();
$subparts['template']=$this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['item']=$this->cObj->getSubpart($subparts['template'], '###ITEM###');
$str="SELECT m.manufacturers_id, m.manufacturers_name, m.manufacturers_image from tx_multishop_manufacturers m, tx_multishop_manufacturers_info mi where m.status=1 and mi.language_id='".$this->sys_language_uid."' and m.manufacturers_id=mi.manufacturers_id order by m.sort_order";
$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
$manufacturers=array();
while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
	$manufacturers[]=$row;
}
if (count($manufacturers)>0) {
	$output['manufacturers_header']=$this->pi_getLL('manufacturers');
	$output['manufacturers_uid']=$this->cObj->data['uid'];
	$contentItem='';
	foreach ($manufacturers as $row) {
		$link=mslib_fe::typolink($this->conf['search_page_pid'], '&tx_multishop_pi1[page_section]=manufacturers_products_listing&manufacturers_id='.$row['manufacturers_id']);
		if ($this->ADMIN_USER) {
			$output['admin_manufacturers_sortable_id']='sortable_manufacturer_'.$row['manufacturers_id'].'';
		}
		$output['class_active']=(($row['manufacturers_id']==$this->get['manufacturers_id']) ? 'active': '');
		$output['manufacturers_link']=$link;
		$output['manufacturers_name']=htmlspecialchars($row['manufacturers_name']);
		$markerArray=array();
		$markerArray['ADMIN_MANUFACTURERS_SORTABLE_ID']=$output['admin_manufacturers_sortable_id'];
		$markerArray['CLASS_ACTIVE']=$output['class_active'];
		$markerArray['MANUFACTURERS_LINK']=$output['manufacturers_link'];
		$markerArray['MANUFACTURERS_NAME']=$output['manufacturers_name'];
		if ($row['manufacturers_image']) {
			$markerArray['MANUFACTURERS_IMAGE_NORMAL']='<img src="'.mslib_befe::getImagePath($row['manufacturers_image'], 'manufacturers', 'normal').'">';
		} else {
			$markerArray['MANUFACTURERS_IMAGE_NORMAL']='';
		}
		$contentItem.=$this->cObj->substituteMarkerArray($subparts['item'], $markerArray, '###|###');
	}
	// fill the row marker with the expanded rows
	$subpartArray['###MANUFACTURERS_UID###']=$output['manufacturers_uid'];
	$subpartArray['###MANUFACTURERS_HEADER###']=$output['manufacturers_header'];
	$subpartArray['###ITEM###']=$contentItem;
	// completed the template expansion by replacing the "item" marker in the template
	$content=$this->cObj->substituteMarkerArrayCached($subparts['template'], null, $subpartArray);
}
?>