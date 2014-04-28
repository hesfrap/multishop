<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
set_include_path(t3lib_extMgm::extPath('multishop').PATH_SEPARATOR.t3lib_extMgm::extPath('multishop').'scripts/admin_pages/');
if ($this->post) {
	$erno=array();
	if (!$this->post['tax_name']) {
		$erno[]='No name defined';
	}
	if (!count($erno)) {
		if ($this->post['tax_name']) {
			$insertArray=array();
			$insertArray['name']=$this->post['tax_name'];
			$insertArray['rate']=$this->post['tax_rate'];
			$insertArray['status']=$this->post['tax_status'];
			if (is_numeric($this->post['tax_id'])) {
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_taxes', 'tax_id='.$this->post['tax_id'], $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			} else {
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_taxes', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			}
		}
		unset($this->post);
	}
}
if (is_array($erno) and count($erno)>0) {
	$content.='<div class="error_msg">';
	$content.='<h3>'.$this->pi_getLL('the_following_errors_occurred').'</h3><ul>';
	foreach ($erno as $item) {
		$content.='<li>'.$item.'</li>';
	}
	$content.='</ul>';
	$content.='</div>';
}
if ($this->get['delete'] and is_numeric($this->get['tax_id'])) {
	$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_taxes', 'tax_id=\''.$this->get['tax_id'].'\'');
	$res=$GLOBALS['TYPO3_DB']->sql_query($query);
} elseif (is_numeric($this->get['tax_id'])) {
	$tax=mslib_fe::getTaxes($this->get['tax_id']);
	$this->post['tax_id']=$tax['tax_id'];
	$this->post['tax_name']=$tax['name'];
	$this->post['tax_rate']=$tax['rate'];
	$this->post['tax_status']=$tax['status'];
}
$content.='
<form action="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'" method="post">
	<fieldset>
		<legend>ADD / UPDATE TAX</legend>
		<div class="account-field">
				<label for="">TAX name</label>
				<input type="text" name="tax_name" id="tax_name" value="'.$this->post['tax_name'].'"> (name that will be displayed on the invoice. Example: VAT)		
		</div>
		<div class="account-field">
				<label for="">TAX rate</label>
				<input type="text" name="tax_rate" id="tax_rate" value="'.$this->post['tax_rate'].'"> (example: 19 for 19%)
		</div>
		<div class="account-field">
				<label for="">Status</label>
				<input name="tax_status" type="radio" value="1" '.((!isset($this->post['tax_status']) or $this->post['tax_status']==1) ? 'checked' : '').' /> on
				<input name="tax_status" type="radio" value="0" '.((isset($this->post['tax_status']) and $this->post['tax_status']==0) ? 'checked' : '').' /> off
		</div>		
		<div class="account-field">
				<label for="">&nbsp;</label>
				<input name="tax_id" type="hidden" value="'.$this->post['tax_id'].'" />
				<input name="Submit" type="submit" value="'.$this->pi_getLL('save').'" class="msadmin_button" />
		</div>
	</fieldset>
</form>
';
// load taxes
$str="SELECT * from tx_multishop_taxes order by tax_id";
$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
$taxes=array();
while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
	$taxes[]=$row;
}
if (count($taxes)) {
	$content.='<table width="100%" border="0" align="center" class="msZebraTable msadmin_border" id="admin_modules_listing">
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>Rate</th>
		<th>Status</th>
		<th>Action</th>
	</tr>
	';
	foreach ($taxes as $tax) {
		$content.='
		<tr class="'.$tr_type.'">
			<td>
				<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&tax_id='.$tax['tax_id'].'&edit=1').'">'.$tax['tax_id'].'</a>
			</td>
			<td>
				<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&tax_id='.$tax['tax_id'].'&edit=1').'">'.$tax['name'].'</a>
			</td>
			<td>
				'.round($tax['rate'], 4).'%
			</td>
			<td>
				'.$tax['status'].'
			</td>			
			<td>
				<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&tax_id='.$tax['tax_id'].'&delete=1').'">delete</a>
			</td>
		</tr>
		';
	}
	$content.='</table>';
}
$content.='<p class="extra_padding_bottom"><a class="msadmin_button" href="'.mslib_fe::typolink().'">'.t3lib_div::strtoupper($this->pi_getLL('admin_close_and_go_back_to_catalog')).'</a></p>';
$content='<div class="fullwidth_div">'.mslib_fe::shadowBox($content).'</div>';
?>