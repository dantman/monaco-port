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
$wgAutoloadClasses['MonacoSidebar'] = dirname(__FILE__).'/MonacoSidebar.class.php';
$wgExtensionMessagesFiles['Monaco'] = dirname(__FILE__).'/Monaco.i18n.php';

$wgHooks['MessageCacheReplace'][] = 'MonacoSidebar::invalidateCache';

$wgResourceModules['skins.monaco'] = array(
	'styles' => array(
		'skins/monaco/style/css/monobook_modified.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/reset_modified.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/buttons.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/sprite.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/root.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/header.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/article.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/widgets.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/modal.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/footer.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/star_rating.css' => array( 'media' => 'screen' ),
		'skins/monaco/style/css/ny.css' => array( 'media' => 'screen' ),
	),
	'scripts' => 'skins/monaco/style/js/monaco.js',
);

/* Bad Configs - These are Wikia junk used inside Monaco.skin.php that should be slowly removed */
$wgSearchDefaultFulltext = false; // bad config
$wgSpecialPagesRequiredLogin = array(); // bad config, it should be possible to check if the user has special page access without doing something like this

$wgMastheadVisible = false; // we may want to integrate masthead into Monaco and make it a optional skin feature

/* Config Settings */
$wgMonacoAllowUsetheme = false; // Set to false to disable &usetheme= support.
$wgMonacoTheme = "sapphire"; // load a pre-made Monaco theme from the styles folder
$wgMonacoDynamicCreateOverride = false; // Override "Special:CreatePage" urls with something else
$wgMonacoUseMoreButton = true; // Set to false to disable the more button and just list out links
$wgMonacoUseSitenoticeIsland = false; // Use an island above the content area for the sitenotice instead of embedding it above the page title
$wgMonacoUseMasthead = true; // Show the masthead on userpages
$wgMonacoCompactSpecialPages = false; // Compact the tab_bar on special pages

