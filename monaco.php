<?php
/**
 * Monaco skin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Inez Korczynski <inez@wikia.com>
 * @author Christian Williams
 * @author Daniel Friesen
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['skin'][] = array (
	'path' => __FILE__,
	'name' => 'Monaco',
	'author' => array('[http://www.wikia.com/ Wikia]', 'Inez Korczynski', 'Christian Williams', '[http://mediawiki.org/wiki/User:Dantman Daniel Friesen]'),
	'descriptionmsg' => 'monaco-desc',
	'url' => "https://github.com/dantman/monaco-port",
);

$wgValidSkinNames['monaco'] = 'Monaco';
$wgAutoloadClasses['SkinMonaco'] = dirname(__FILE__).'/Monaco.skin.php';
$wgExtensionMessagesFiles['Monaco'] = dirname(__FILE__).'/Monaco.i18n.php';

