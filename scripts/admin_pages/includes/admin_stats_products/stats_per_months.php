<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if ($this->get['stats_year_sb']>0) {
	if ($this->get['stats_year_sb']!=$this->cookie['stats_year_sb']) {
		$this->cookie['stats_year_sb']=$this->get['stats_year_sb'];
	}
} else {
	$this->cookie['stats_year_sb']=date("Y");
}
if ($this->get['Search']) {
	if ($this->get['paid_orders_only'] and $this->get['paid_orders_only']!=$this->cookie['paid_orders_only']) {
		$this->cookie['paid_orders_only']=$this->get['paid_orders_only'];
	} else {
		$this->cookie['paid_orders_only']='';
	}
	$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
	$GLOBALS['TSFE']->storeSessionData();
}
$sql_year="select crdate from tx_multishop_orders where deleted=0 order by orders_id asc limit 1";
$qry_year=$GLOBALS['TYPO3_DB']->sql_query($sql_year);
$row_year=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_year);
if ($row_year['crdate']>0) {
	$oldest_year=date("Y", $row_year['crdate']);
} else {
	$oldest_year=date("Y");
}
$current_year=date("Y");
$temp_year='<select name="stats_year_sb" id="stats_year_sb">';
if ($oldest_year) {
	for ($y=$current_year; $y>=$oldest_year; $y--) {
		if ($this->cookie['stats_year_sb']==$y) {
			$temp_year.='<option value="'.$y.'" selected="selected">'.$y.'</option>';
		} else {
			$temp_year.='<option value="'.$y.'">'.$y.'</option>';
		}
	}
} else {
	$temp_year.='<option value="'.$current_year.'" selected="selected">'.$current_year.'</option>';
}
$temp_year.='</select>';
$selected_year='Y-';
if ($this->cookie['stats_year_sb']>0) {
	$selected_year=$this->cookie['stats_year_sb']."-";
}
$content.='
<form action="index.php" method="get" id="orders_stats_form" class="float_right">
<div class="stat-years float_right">'.$temp_year.'</div>
<input name="type" type="hidden" value="2003" />
<input name="Search" type="hidden" value="1" />
<input name="tx_multishop_pi1[page_section]" type="hidden" value="admin_stats_products" />
<input name="tx_multishop_pi1[stats_section]" type="hidden" value="stats_per_months" />
<div class="paid-orders"><input id="checkbox_paid_orders_only" name="paid_orders_only" type="checkbox" value="1" '.($this->cookie['paid_orders_only'] ? 'checked' : '').' /> '.$this->pi_getLL('show_paid_orders_only').'</div>
</form>
<script type="text/javascript" language="JavaScript">
	jQuery(document).ready(function($){
		$("#checkbox_paid_orders_only").click(function(e) {
			$("#orders_stats_form").submit();
		});

		$("#stats_year_sb").change(function(e) {
			$("#orders_stats_form").submit();
		});
	});
</script>	
';
$dates=array();
$content.='<h2>'.htmlspecialchars($this->pi_getLL('sales_volume_by_month')).'</h2>';
for ($i=1; $i<13; $i++) {
	$time=strtotime(date($selected_year.$i."-01")." 00:00:00");
	$dates[strftime("%B %Y", $time)]=date($selected_year."m", $time);
}
$content.='<table width="100%" class="msZebraTable" cellspacing="0" cellpadding="0" border="0" id="product_import_table">';
$content.='<tr class="odd">';
foreach ($dates as $key=>$value) {
	$content.='<td align="right">'.ucfirst($key).'</td>';
}
//$content.='<td align="right" nowrap>'.htmlspecialchars($this->pi_getLL('total')).'</td>';
//$content.='<td align="right" nowrap>'.htmlspecialchars($this->pi_getLL('cumulative')).'</td>';
$content.='</tr>';
$content.='<tr class="even">';
$total_amount=0;
foreach ($dates as $key=>$value) {
	$total_price=0;
	$start_time=strtotime($value."-01 00:00:00");
	//$end_time=strtotime($value."-31 23:59:59");
	$end_time=strtotime($value."-01 23:59:59 +1 MONTH -1 DAY");

	$where=array();
	if ($this->cookie['paid_orders_only']) {
		$where[]='(o.paid=1)';
	} else {
		$where[]='(o.paid=1 or o.paid=0)';
	}
	$where[]='(o.deleted=0)';
	$str="SELECT sum(op.qty) as total, op.products_name, op.products_id, op.categories_id FROM tx_multishop_orders o, tx_multishop_orders_products op WHERE (".implode(" AND ", $where).") and (o.crdate BETWEEN ".$start_time." and ".$end_time.") and o.orders_id=op.orders_id group by op.products_name having total > 0 order by total desc limit 10";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$content.='<td valign="top">	
		';
	if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
		$content.='
		<table width="100%" cellspacing="0" cellpadding="0" border="0" class="msZebraTable" id="product_import_table">
			<tr class="'.$tr_type.'">
				<th valign="top">Qty</td>
				<th valign="top">Product</td>
			</tr>			
		';
		while (($product=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			if (!$tr_type or $tr_type=='even') {
				$tr_type='odd';
			} else {
				$tr_type='even';
			}
			$where='';
			if (!$product['categories_id']) {
				// fix fold old orders that did not have categories id in orders_products table
				$tmpProduct=mslib_fe::getProduct($product['products_id']);
				$product['categories_id']=$tmpProduct;
			}
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
				}
				// get all cats to generate multilevel fake url eof
				$productLink=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$product['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
			} else {
				$productLink='';
			}
			$content.='
			<tr class="'.$tr_type.'">
				<td valign="top" align="right"><strong>'.round($product['total'], 2).'</strong></td>
				<td valign="top"><a href="'.$productLink.'" target="_blank">'.$product['products_name'].'</a></td>
			</tr>
			';
		}
		$content.='</table>';
	}
	$content.='</td>';
}
$content.='</tr>';
$content.='
</table>';
$content.='<p class="extra_padding_bottom">';
$content.='<a class="msadmin_button" href="'.mslib_fe::typolink().'">'.t3lib_div::strtoupper($this->pi_getLL('admin_close_and_go_back_to_catalog')).'</a>';
$content.='
</p>';
$content='<div class="fullwidth_div">'.mslib_fe::shadowBox($content).'</div>';
?>