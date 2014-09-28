<?php
/**
 * AniMonaco skin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Daniel Friesen
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['skin'][] = array(
	'path' => __FILE__,
	'name' => 'AniMonaco',
	'author' => array('[http://mediawiki.org/wiki/User:Dantman Daniel Friesen]'),
	'descriptionmsg' => 'animonaco-desc',
	'url' => "https://github.com/dantman/monaco-port",
);

$wgValidSkinNames['animonaco'] = 'AniMonaco';
$wgAutoloadClasses['SkinAniMonaco'] = dirname(__FILE__).'/AniMonaco.skin.php';
$wgAutoloadClasses['SkinAnimonaco'] = dirname(__FILE__).'/AniMonaco.skin.php'; // pre-1.18 autoloading /bug/
$wgExtensionMessagesFiles['AniMonaco'] = dirname(__FILE__).'/AniMonaco.i18n.php';

$egAniMonacoLeaderboardCallback = null;
$egAniMonacoSidebarCallback = null;
$egAniMonacoRightSidebarCallback = null;
