/**
 * Copyright © 2011 - Daniel Friesen
 * @author Daniel friesen
 * @license Dual-licensed MIT / GPL2+
 */

jQuery(function($) {
	
	$('body')
		.delegate(".hovermenu *", "blur", function(e) {
			$(this).closest('.hovermenu').removeClass('open');
		})
		.delegate(".hovermenu *", "focus", function(e) {
			$(this).closest('.hovermenu').addClass('open');
		});
	
	$('body')
		.delegate(".hover-navigation *", "blur", function(e) {
			$(this).closest(".hover-navigation").find('.menu-item.with-sub-menu.open').removeClass('open');
		})
		.delegate(".hover-navigation *", "focus", function(e) {
			$(this).parents(".menu-item.with-sub-menu").addClass('open');
		});
	
});
