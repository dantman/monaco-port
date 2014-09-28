<?php

$wgHooks['OutputPageBodyAttributes'][] = 'egExtendedOutputPageBodyAttributes';
function egExtendedOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ) {
	global $wgUser;

	if ( !$wgUser->isLoggedIn() )
		$bodyAttrs['class'] .= ' loggedout';

	if ( $out->getTitle()->equals(Title::newMainPage()) )
		$bodyAttrs['class'] .= ' mainpage';

	return true;
}

