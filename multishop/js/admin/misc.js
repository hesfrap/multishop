function msAdminShortcutFunc(mode) {
	jQuery(document).bind('keydown', 'alt+ctrl+s', function(){jQuery('#ms_admin_skeyword').focus();})
	if (mode == 'product') {
		jQuery(document).bind('keydown', 'alt+ctrl+n', function(){jQuery('#msadmin_new_product').click();})								   
		jQuery(document).bind('keydown', 'alt+ctrl+e', function(){jQuery('#msadmin_edit_product').click();})								   
	}
	else if (mode == 'category') {
		jQuery(document).bind('keydown', 'alt+ctrl+n', function(){jQuery('#msadmin_new_category').click();})								   
		jQuery(document).bind('keydown', 'alt+ctrl+e', function(){jQuery('#msadmin_edit_category').click();})								   
	}	
	jQuery(document).bind('keydown', 'alt+ctrl+m', function(){jQuery('#ms_admin_minimaxi_wrapper a').click();})				
}