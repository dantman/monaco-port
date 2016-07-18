<?php
if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'monaco' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['monaco'] = __DIR__ . '/i18n';
	$wgUseCommunity = false;
	/* wfWarn(
		'Deprecated PHP entry point used for Vector skin. Please use wfLoadSkin instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'This version of the Monaco skin requires MediaWiki 1.25+' );
}
