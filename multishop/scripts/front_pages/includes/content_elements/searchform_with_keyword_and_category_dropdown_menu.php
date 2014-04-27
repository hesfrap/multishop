<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if ($GLOBALS['categories_id_array']) {
	$categories_id=$GLOBALS['categories_id_array'][0];
} elseif (is_numeric($this->get['categories_id'])) {
	$categories_id=$this->get['categories_id'];
}
if ($this->ms['MODULES']['CACHE_FRONT_END'] and !$this->ms['MODULES']['CACHE_TIME_OUT_CATEGORIES_NAVIGATION_MENU']) {
	$this->ms['MODULES']['CACHE_FRONT_END']=0;
}
if ($this->ms['MODULES']['CACHE_FRONT_END']) {
	$this->cacheLifeTime=$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'cacheLifeTime', 's_advanced');
	if (!$this->cacheLifeTime) {
		$this->cacheLifeTime=$this->ms['MODULES']['CACHE_TIME_OUT_CATEGORIES_NAVIGATION_MENU'];
	}
	$options=array(
		'caching'=>true,
		'cacheDir'=>$this->DOCUMENT_ROOT.'uploads/tx_multishop/tmp/cache/',
		'lifeTime'=>$this->cacheLifeTime
	);
	$Cache_Lite=new Cache_Lite($options);
//	$string='search_by_category_'.$categories_id;
	$string=serialize($GLOBALS['TYPO3_CONF_VARS']['tx_multishop_data']['user_crumbar']).$this->cObj->data['uid'];
}
if (!$this->ms['MODULES']['CACHE_FRONT_END'] or !$categories=$Cache_Lite->get($string)) {
	$categories='';
	$cats=mslib_fe::getSubcatsOnly(0);
	foreach ($cats as $cat) {
		if (!$cat['categories_url']) {
			$categories.='<option value="'.$cat['categories_id'].'" '.($cat['categories_id']==$categories_id ? 'selected' : '').'>'.$cat['categories_name'].'</option>';
		}
	}
	if ($this->ms['MODULES']['CACHE_FRONT_END']) {
		$Cache_Lite->save($categories);
	}
}
$content.='
<form action="index.php" method="get"  enctype="application/x-www-form-urlencoded">
<input name="id" type="hidden" value="'.$this->shop_pid.'" />
<input name="tx_multishop_pi1[page_section]" type="hidden" value="products_search" />
<input name="page" id="page" type="hidden" value="0" />	
<div id="form_1" class="form_mailform">
  <input type="text" id="skeyword" name="skeyword" value="'.htmlspecialchars($this->get['skeyword']).'" /> 
	</div>
		<div id="form_2" class="form_mailform">
		  <select name="categories_id">
			<option value="">Categorie</option>
			'.$categories.'
		  </select>
		</div>
	<div id="form_3" class="form_mailform">
  <input type="submit" id="submit_zoeken" value="'.htmlspecialchars($this->pi_getLL('search')).'" />
</div>
</form>
';
?>