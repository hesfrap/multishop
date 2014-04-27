<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
set_include_path(t3lib_extMgm::extPath('multishop').PATH_SEPARATOR.t3lib_extMgm::extPath('multishop').'scripts/admin_pages/');
if ($this->post) {
	$erno=array();
	if (!$this->post['name']) {
		$erno[]='No name defined';
	}
	if (!count($erno)) {
		if ($this->post['name']) {
			$insertArray=array();
			$insertArray['name']=$this->post['name'];
			$insertArray['status']=$this->post['status'];
			if (is_numeric($this->post['rules_group_id'])) {
				$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_tax_rule_groups', 'rules_group_id='.$this->post['rules_group_id'], $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				$rules_group_id=$this->post['rules_group_id'];
			} else {
				$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_tax_rule_groups', $insertArray);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				$rules_group_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
			}
			if ($rules_group_id) {
				$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_tax_rules', 'rules_group_id='.$rules_group_id);
				$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				foreach ($this->post['tax_id'] as $cn_iso_nr=>$array) {
					if ($cn_iso_nr) {
						foreach ($array as $zn_country_iso_nr=>$tax_id) {
							$insertArray=array();
							$insertArray['rules_group_id']=$rules_group_id;
							$insertArray['zn_country_iso_nr']=$zn_country_iso_nr;
							$insertArray['cn_iso_nr']=$cn_iso_nr;
							if ($tax_id) {
								$insertArray['tax_id']=$tax_id;
							}
							if ($zn_country_iso_nr && $this->post['state_modus'][$cn_iso_nr][$zn_country_iso_nr]==2) {
								$insertArray['country_tax_id']=$this->post['tax_id'][$cn_iso_nr][0];
							}
							if ($this->post['state_modus'][$cn_iso_nr][$zn_country_iso_nr]) {
								$insertArray['state_modus']=$this->post['state_modus'][$cn_iso_nr][$zn_country_iso_nr];
							}
							if ($insertArray['tax_id'] or $insertArray['state_modus']) {
								$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_tax_rules', $insertArray);
								$res=$GLOBALS['TYPO3_DB']->sql_query($query);
							}
						}
					}
				}
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
if ($this->get['delete'] and is_numeric($this->get['rules_group_id'])) {
	$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_tax_rule_groups', 'rules_group_id=\''.$this->get['rules_group_id'].'\'');
	$res=$GLOBALS['TYPO3_DB']->sql_query($query);
} elseif (is_numeric($this->get['rules_group_id'])) {
	$tax_rules_group=mslib_fe::getTaxRulesGroup($this->get['rules_group_id']);
	$this->post['rules_group_id']=$tax_rules_group['rules_group_id'];
	$this->post['name']=$tax_rules_group['name'];
	$this->post['status']=$tax_rules_group['status'];
}
$content.='
<form action="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'" method="post">
	<fieldset>
		<legend>ADD / UPDATE TAX RULES GROUP</legend>
		<div class="account-field">
				<label for="">Name</label>
				<input type="text" name="name" id="name" value="'.$this->post['name'].'"> 
		</div>
		<div class="account-field">
				<label for="">Status</label>
				<input name="status" type="radio" value="1" '.((!isset($this->post['status']) or $this->post['status']==1) ? 'checked' : '').' /> on
				<input name="status" type="radio" value="0" '.((isset($this->post['status']) and $this->post['status']==0) ? 'checked' : '').' /> off
		</div>		
		<div class="account-field">
				<label for="">&nbsp;</label>
				<input name="rules_group_id" type="hidden" value="'.$this->post['rules_group_id'].'" />
				<input name="Submit" type="submit" value="'.$this->pi_getLL('save').'" class="msadmin_button" />
		</div>
	</fieldset>
';
if (is_array($tax_rules_group) and $tax_rules_group['rules_group_id']) {
	$GLOBALS['TSFE']->additionalHeaderData[]='
	<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'js/jquery.treeview/jquery.treeview.js" type="text/javascript"></script>
	<link rel="stylesheet" href="'.t3lib_extMgm::siteRelPath($this->extKey).'js/jquery.treeview/jquery.treeview.css" />
	<style>
		.tree_item_label
		{
			display:block;
		}
	</style>
	';
	$state_modus_array=array();
	$state_modus_array[0]='Apply country tax only';
	$state_modus_array[1]='Apply state tax only';
	$state_modus_array[2]='Apply both taxes';
	$str="SELECT * from tx_multishop_zones order by name";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$zones=array();
	while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$zones[]=$row;
	}
	$counter=0;
	// load taxes
	$str="SELECT * from tx_multishop_taxes order by name";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$taxes=array();
	while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$taxes[]=$row;
	}
	foreach ($zones as $zone) {
		if ($zone['name']) {
			$counter++;
			$GLOBALS['TSFE']->additionalHeaderData[]='
			<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'js/jquery.treeview/jquery.treeview.js" type="text/javascript"></script>
			<link rel="stylesheet" href="'.t3lib_extMgm::siteRelPath($this->extKey).'js/jquery.treeview/jquery.treeview.css" />
			<script type="text/javascript">
					jQuery(function($) {
						$(".category_listing_ul_'.$counter.'").treeview({
							collapsed: true,
							animated: "medium",
							control:"#sidetreecontrol",
							persist: "location"
						});
					})
			</script>
			';
			$query="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.zone_id='".$zone['id']."' and c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			$tab_content.='
			<ul class="category_listing_ul_'.$counter.'" id="msAdmin_category_listing_ul">		
			';
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$tab_content.='<li class="item_'.$counter.'">';
				$tab_content.='<label class="tree_item_label">';
				$tab_content.=$row['cn_short_en'];
				$tab_content.='</label>';
				$tab_content.='<select name="tax_id['.$row['cn_iso_nr'].'][0]"><option value="">No TAX</option>';
				$query3=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
					'tx_multishop_tax_rules', // FROM ...
					"cn_iso_nr='".$row['cn_iso_nr']."' and zn_country_iso_nr='0' and rules_group_id	 = ".$this->get['rules_group_id'], // WHERE...
					'', // GROUP BY...
					'', // ORDER BY...
					'' // LIMIT ...
				);
				$res3=$GLOBALS['TYPO3_DB']->sql_query($query3);
				$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3);
				foreach ($taxes as $tax) {
					$tab_content.='<option value="'.$tax['tax_id'].'"'.($tax['tax_id']==$row3['tax_id'] ? ' selected' : '').'>'.$tax['name'].'</option>'."\n";
				}
				$tab_content.='</select>';
				// now load the stated
				$query2=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
					'static_country_zones', // FROM ...
					'zn_country_iso_nr='.$row['cn_iso_nr'], // WHERE...
					'', // GROUP BY...
					'', // ORDER BY...
					'' // LIMIT ...
				);
				$res2=$GLOBALS['TYPO3_DB']->sql_query($query2);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res2)>0) {
					$tab_content.='<ul>';
					while ($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
						$tab_content.='<li class="item_'.$counter.''.(!$row['status'] ? ' ' : '').'">';
						$tab_content.='<label class="tree_item_label">';
						$tab_content.=$row2['zn_name_local'];
						$tab_content.='</label>';
						$tab_content.='<select name="tax_id['.$row['cn_iso_nr'].']['.$row2['uid'].']"><option value="">No TAX</option>';
						$query3=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
							'tx_multishop_tax_rules', // FROM ...
							"cn_iso_nr='".$row['cn_iso_nr']."' and zn_country_iso_nr='".$row2['uid']."' and rules_group_id	 = ".$this->get['rules_group_id'], // WHERE...
							'', // GROUP BY...
							'', // ORDER BY...
							'' // LIMIT ...
						);
						$res3=$GLOBALS['TYPO3_DB']->sql_query($query3);
						$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3);
						foreach ($taxes as $tax) {
							$tab_content.='<option value="'.$tax['tax_id'].'"'.($tax['tax_id']==$row3['tax_id'] ? ' selected' : '').'>'.$tax['name'].'</option>'."\n";
						}
						$tab_content.='</select>';
						$tab_content.='<select name="state_modus['.$row['cn_iso_nr'].']['.$row2['uid'].']">';
						foreach ($state_modus_array as $state_modus=>$label) {
							$tab_content.='<option value="'.$state_modus.'"'.($state_modus==$row3['state_modus'] ? ' selected' : '').'>'.htmlspecialchars($label).'</option>'."\n";
						}
						$tab_content.='</select>';
					}
					$tab_content.='</ul>';
				}
				$tab_content.='
				</li>';
			}
			$tab_content.='</ul>';
			$tabs['zone_'.$counter]=array(
				$zone['name'],
				$tab_content
			);
			$tab_content='';
		}
	}
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
			jQuery(activeTab).show();
			return false;
		});
	});
	</script>
	<div id="tab-container">
		<ul class="tabs">
	';
	foreach ($tabs as $key=>$value) {
		$count++;
		$content.='<li'.(($count==1) ? ' class="active"' : '').'><a href="#'.$key.'">'.$value[0].'</a></li>';
	}
	$content.='      
		</ul>
		<div class="tab_container">
	
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
	$content.=$save_block.'
	
		</div>
	</div>
	';
}
$content.='
</form>
';
if (!$this->get['edit']) {
	// load tax rules
	$str="SELECT * from tx_multishop_tax_rule_groups order by rules_group_id";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$tax_rules_groups=array();
	while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$tax_rules_groups[]=$row;
	}
	if (count($tax_rules_groups)) {
		$content.='<table width="100%" border="0" align="center" class="msZebraTable msadmin_border" id="admin_modules_listing">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Status</th>
			<th>Action</th>
		</tr>
		';
		foreach ($tax_rules_groups as $tax_rules_group) {
			$content.='
			<tr class="'.$tr_type.'">
				<td>
					<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&rules_group_id='.$tax_rules_group['rules_group_id'].'&edit=1').'">'.$tax_rules_group['rules_group_id'].'</a>
				</td>
				<td>
					<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&rules_group_id='.$tax_rules_group['rules_group_id'].'&edit=1').'">'.$tax_rules_group['name'].'</a>
				</td>
				<td>
					'.$tax_rules_group['status'].'
				</td>			
				<td>
					<a href="'.mslib_fe::typolink(',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&rules_group_id='.$tax_rules_group['rules_group_id'].'&delete=1').'">delete</a>
				</td>
			</tr>
			';
		}
		$content.='</table>';
	}
}
$content.='<p class="extra_padding_bottom"><a class="msadmin_button" href="'.mslib_fe::typolink().'">'.t3lib_div::strtoupper($this->pi_getLL('admin_close_and_go_back_to_catalog')).'</a></p>';
$content='<div class="fullwidth_div">'.mslib_fe::shadowBox($content).'</div>';
?>