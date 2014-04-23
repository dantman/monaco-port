(function (mw, $) {
  // Apply the wikipage.content hook to right sideboxes
  $(function () { mw.hook('wikipage.content').fire($('.sidebox')); });
}(mediaWiki, jQuery));