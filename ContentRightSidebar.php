<?php
/**
 * Monaco skin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Daniel Friesen
 */

if( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['skin'][] = array (
	'path' => __FILE__,
	'name' => 'ContentRightSidebar',
	'author' => array('[http://mediawiki.org/wiki/User:Dantman Daniel Friesen]'),
	'descriptionmsg' => 'contentrightsidebar-desc',
	'url' => "https://github.com/dantman/monaco-port",
);

$wgExtensionMessagesFiles['ContentRightSidebar'] = dirname(__FILE__).'/ContentRightSidebar.i18n.php';

/**
 * Register parser extensions
 */
$wgHooks['ParserFirstCallInit'][] = array( 'efContentRightSidebarRegisterParser' ); 
function efContentRightSidebarRegisterParser( &$parser ) {
	$parser->setHook('right-sidebar', 'efContentRightSidebarTag', 0);
	return true;
}

define('RIGHT_SIDEBAR_START_TOKEN', "<!-- RIGHT SIDEBAR START -->");
define('RIGHT_SIDEBAR_END_TOKEN', "<!-- RIGHT SIDEBAR END -->");
define('RIGHT_SIDEBAR_WITHBOX_TOKEN', "<!-- RIGHT SIDEBAR WITHBOX -->");
define('RIGHT_SIDEBAR_TITLE_START_TOKEN', "<!-- RIGHT SIDEBAR TITLE START>");
define('RIGHT_SIDEBAR_TITLE_END_TOKEN', "<RIGHT SIDEBAR TITLE END -->");
define('RIGHT_SIDEBAR_CONTENT_START_TOKEN', "<!-- RIGHT SIDEBAR CONTENT START -->");
define('RIGHT_SIDEBAR_CONTENT_END_TOKEN', "<!-- RIGHT SIDEBAR CONTENT END -->");

function efContentRightSidebarTag( $input, $arg, $parser, $frame ) {
	$input = $parser->recursiveTagParse( $input, $frame );
	
	$with_box = (isset($arg["with-box"]) ? $arg["with-box"] : (isset($arg["withbox"]) ? $arg["withbox"] : null));
	
	$out  = RIGHT_SIDEBAR_START_TOKEN;
	if ( $with_box && !in_array(strtolower($with_box), array("false", "off", "no", "none")) ) {
		$out .= RIGHT_SIDEBAR_WITHBOX_TOKEN;
	}
	if ( isset($arg["title"]) ) {
		$out .= RIGHT_SIDEBAR_TITLE_START_TOKEN . urlencode($arg["title"]) . RIGHT_SIDEBAR_TITLE_END_TOKEN;
	}
	$out .= '<div style="float: right; clear: right; position: relative;">';
	$out .= RIGHT_SIDEBAR_CONTENT_START_TOKEN . $input . RIGHT_SIDEBAR_CONTENT_END_TOKEN;
	$out .= '</div>';
	$out .= RIGHT_SIDEBAR_END_TOKEN;
	
	return $out;
}

function efExtractRightSidebarBoxes( &$html ) {
	$boxes = array();
	
	while(true) {
		$withBox = false;
		$title = '';
		
		$start = strpos($html, RIGHT_SIDEBAR_START_TOKEN);
		if ( $start === false )
			break;
		$end = strpos($html, RIGHT_SIDEBAR_END_TOKEN, $start);
		if ( $end === false )
			break;
		$content = substr($html, $start, $end-$start);
		if ( strpos($content, RIGHT_SIDEBAR_WITHBOX_TOKEN) !== false ) {
			$withBox = true;
		}
		$startTitle = strpos($content, RIGHT_SIDEBAR_TITLE_START_TOKEN);
		if ( $startTitle !== false ) {
			$endTitle = strpos($content, RIGHT_SIDEBAR_TITLE_END_TOKEN, $startTitle);
			if ( $endTitle !== false ) {
				$title = urldecode(substr($content, $startTitle+strlen(RIGHT_SIDEBAR_TITLE_START_TOKEN), $endTitle-$startTitle-strlen(RIGHT_SIDEBAR_TITLE_START_TOKEN)));
			}
		}
		$contentStart = strpos($content, RIGHT_SIDEBAR_CONTENT_START_TOKEN);
		if ( $contentStart !== false ) {
			$content = substr($content, $contentStart+strlen(RIGHT_SIDEBAR_CONTENT_START_TOKEN));
		}
		$contentEnd = strpos($content, RIGHT_SIDEBAR_CONTENT_END_TOKEN);
		if ( $contentStart !== false ) {
			$content = substr($content, 0, $contentEnd);
		}
		$boxes[] = array( "with-box" => $withBox, "title" => $title, "content" => $content );
		$html = substr($html, 0, $start) . substr($html, $end+strlen(RIGHT_SIDEBAR_END_TOKEN));
	}
	
	return $boxes;
}

$wgHooks['MonacoRightSidebar'][] = 'efContentRightSidebarMonacoRightSidebar';
function efContentRightSidebarMonacoRightSidebar( $sk ) {
	$boxes = efExtractRightSidebarBoxes( $sk->data['bodytext'] );
	
	foreach ( $boxes as $box ) {
		if ( $box["with-box"] ) {
			$sk->customBox( $box["title"], $box["content"] );
		} else {
			echo $box["content"];
		}
	}
	
	return true;
}


