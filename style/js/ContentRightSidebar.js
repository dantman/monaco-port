(function (mw, $) {
  // Enable collapsible content to work in sidebars
  mw.loader.using('jquery.makeCollapsible', function () {
    $(function () { $('#right_sidebar').find('.mw-collapsible').makeCollapsible(); });
  });
}(mediaWiki, jQuery));