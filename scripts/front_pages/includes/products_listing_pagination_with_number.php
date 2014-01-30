<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');
$total_pages = ceil(($pageset['total_rows']/$limit_per_page));
$tmp='';
$tmp.='<div id="pagenav_container_list_wrapper">
<ul id="pagenav_container_list">
<li class="pagenav_first">';
if($p > 0) {
	$tmp .= '<div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('',''.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$this->pi_getLL('first').'</a></div>';
} else {
	$tmp .= '<div class="dyna_button"><span>'.$this->pi_getLL('first').'</span></div>';
}
$tmp.='</li>';
$tmp .= '<li class="pagenav_previous">';
if($p > 0) {
	if (($p-1) > 0) {
		$tmp .= '<div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('','p='.($p-1).'&'.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$this->pi_getLL('previous').'</a></div>';
	} else {
		$tmp .= '<div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('','p='.($p-1).'&'.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$this->pi_getLL('previous').'</a></div>';
	}
} else {
	$tmp .= '<div class="dyna_button"><span>'.$this->pi_getLL('previous').'</span></div>';
}
$tmp .= '</li>';
if ($p == 0 || $p < 9) {
	$start_page_number 		= 1;
	if ($total_pages <= 10) {
		$end_page_number 	= $total_pages;
	} else {
		$end_page_number 	= 10;
	}
} else if ($p >= 9) {
	$start_page_number 	= ($p - 5) + 1;
	$end_page_number 	= ($p + 4) + 1;
	if ($end_page_number > $total_pages) {
		$end_page_number = $total_pages;
	}
}
$tmp .= '<li class="pagenav_number">
<ul id="pagenav_number_wrapper">';
for ($x = $start_page_number; $x <= $end_page_number; $x++) {
	if (($p+1) == $x) {
		$tmp.= '<li><div class="dyna_button"><span>'.$x.'</span></a></li>';
	} else {
		$tmp.= '<li><div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('','p='.($x - 1).'&'.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$x.'</a></div></li>';
	}
}
$tmp.='</ul>
</li>';
$tmp .= '<li class="pagenav_next">';
if((($p+1)*$limit_per_page) < $pageset['total_rows']) {
	$tmp .= '<div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('','p='.($p+1).'&'.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$this->pi_getLL('next').'</a></div>'; 	
} else {
	$tmp .= '<div class="dyna_button"><span>'.$this->pi_getLL('next').'</span></div>';
}
$tmp .= '</li>';
$tmp .= '<li class="pagenav_last">';
if((($p+1)*$limit_per_page) < $pageset['total_rows']) {
	$times=($pageset['total_rows']/$limit_per_page);
	$lastpage=floor($times);
	if ($lastpage==$times) {
		$lastpage--;
	}	
	$tmp .= '<div class="dyna_button"><a class="ajax_link pagination_button" href="'.mslib_fe::typolink('','p='.$lastpage.'&'.mslib_fe::tep_get_all_get_params(array('p','Submit','page','mini_foto','clearcache'))).'">'.$this->pi_getLL('last').'</a></div>';
} else{
	$tmp .= '<div class="dyna_button"><span>'.$this->pi_getLL('last').'</span></div>';
}
$tmp.='</li>';
$tmp .= '</ul></div>';
$content.=$tmp;		
?>