/**
 * Copyright © 2011 - Daniel Friesen
 * @author Daniel Friesen
 * @author James Haley
 * @license Dual-licensed MIT / GPL2+
 */

$('.hovermenu *').on({
  blur: function() {
    $(this).closest('.hovermenu').removeClass('open');
  },
  focus: function() {
    $(this).closest('.hovermenu').addClass('open');
  }
});

$(".hover-navigation *").on({
  blur: function() {
    $(this).closest(".hover-navigation").find('.menu-item.with-sub-menu.open').removeClass('open');
  },
  focus: function() {
    $(this).parents(".menu-item.with-sub-menu").addClass('open');
  }
});