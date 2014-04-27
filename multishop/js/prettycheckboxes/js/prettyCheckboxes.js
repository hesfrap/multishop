/* ------------------------------------------------------------------------
	prettyCheckboxes
	
	Developped By: Stephane Caron (http://www.no-margin-for-errors.com)
	Inspired By: All the non user friendly custom checkboxes solutions ;)
	Version: 1.1
	
	Copyright: Feel free to redistribute the script/modify it, as
			   long as you leave my infos at the top.
------------------------------------------------------------------------- */
	
jQuery.fn.prettyCheckboxes=function(a){a=jQuery.extend({checkboxWidth:17,checkboxHeight:17,className:"prettyCheckbox",display:"list"},a);jQuery(this).each(function(){$label=jQuery('label[for="'+jQuery(this).attr("id")+'"]');$label.prepend("<span class='holderWrap'><span class='holder'></span></span>");if(jQuery(this).is(":checked")){$label.addClass("checked")}$label.addClass(a.className).addClass(jQuery(this).attr("type")).addClass(a.display);$label.find("span.holderWrap").width(a.checkboxWidth).height(a.checkboxHeight);$label.find("span.holder").width(a.checkboxWidth);jQuery(this).addClass("hiddenCheckbox");$label.bind("click",function(){jQuery("input#"+jQuery(this).attr("for")).triggerHandler("click");if(jQuery("input#"+jQuery(this).attr("for")).is(":checkbox")){jQuery(this).toggleClass("checked");jQuery("input#"+jQuery(this).attr("for")).checked=true;jQuery(this).find("span.holder").css("top",0)}else{$toCheck=jQuery("input#"+jQuery(this).attr("for"));jQuery('input[name="'+$toCheck.attr("name")+'"]').each(function(){jQuery('label[for="'+jQuery(this).attr("id")+'"]').removeClass("checked")});jQuery(this).addClass("checked");$toCheck.checked=true}});jQuery("input#"+$label.attr("for")).bind("keypress",function(b){if(b.keyCode==32){if(jQuery.browser.msie){jQuery('label[for="'+jQuery(this).attr("id")+'"]').toggleClass("checked")}else{jQuery(this).trigger("click")}return false}})})};checkAllPrettyCheckboxes=function(b,a){if(jQuery(b).is(":checked")){jQuery(a).find("input[type=checkbox]:not(:checked)").each(function(){jQuery('label[for="'+jQuery(this).attr("id")+'"]').trigger("click");if(jQuery.browser.msie){jQuery(this).attr("checked","checked")}else{jQuery(this).attr("checked","checked")}})}else{jQuery(a).find("input[type=checkbox]:checked").each(function(){jQuery('label[for="'+jQuery(this).attr("id")+'"]').trigger("click");if(jQuery.browser.msie){jQuery(this).attr("checked","")}else{jQuery(this).attr("checked","")}})}};
// now load it
jQuery(document).ready(function($){$('input.PrettyInput').prettyCheckboxes({checkboxWidth: 17,checkboxHeight: 17,className : 'prettyCheckbox',display: 'list'});});		