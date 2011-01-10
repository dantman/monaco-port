/**
 * Copyright © 2011 - Daniel Friesen
 * @author Daniel friesen
 * @license Dual-licensed MIT / GPL2+
 */

jQuery('.hovermenu *')
	.live("blur", function(e) {
		$(this).closest('.hovermenu').removeClass('open');
	})
	.live("focus", function(e) {
		$(this).closest('.hovermenu').addClass('open');
	});

jQuery(".hover-navigation *")
	.live("blur", function(e) {
		$(this).closest(".hover-navigation").find('.menu-item.with-sub-menu.open').removeClass('open');
	})
	.live("focus", function(e) {
		$(this).parents(".menu-item.with-sub-menu").addClass('open');
	});

