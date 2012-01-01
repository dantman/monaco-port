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
if(!defined('MEDIAWIKI')) {
	die(-1);
}

define('STAR_RATINGS_WIDTH_MULTIPLIER', 20);

class SkinMonaco extends SkinTemplate {

	/**
	 * Overwrite few SkinTemplate methods which we don't need in Monaco
	 */
	function buildSidebar() {}
	function getCopyrightIcon() {}
	function getPoweredBy() {}
	function disclaimerLink() {}
	function privacyLink() {}
	function aboutLink() {}
	function getHostedBy() {}
	function diggsLink() {}
	function deliciousLink() {}

	/** Using monaco. */
	var $skinname = 'monaco', $stylename = 'monaco',
		$template = 'MonacoTemplate', $useHeadElement = true;

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	public function initPage( OutputPage $out ) {

		wfDebugLog('monaco', '##### SkinMonaco initPage #####');

		wfProfileIn(__METHOD__);
		global $wgHooks, $wgJsMimeType, $wgStylePath, $wgResourceModules;

		SkinTemplate::initPage($out);
/*
		$this->skinname  = 'monaco';
		$this->stylename = 'monaco';
		$this->template  = 'MonacoTemplate';
*/

		// Function addVariables will be called to populate all needed data to render skin
		$wgHooks['SkinTemplateOutputPageBeforeExec'][] = array(&$this, 'addVariables');

		if ( method_exists( 'OutputPage', 'addModuleStyles' ) ) {
			// MediaWiki 1.17 and above, load the bulk of our scripts with the resource loader
			$out->addModuleScripts( 'skins.monaco' );
		} else {
			// MediaWiki 1.16 and below, read our resource loader data and just load
			// the individual script.
			$out->addScriptFile( preg_replace( '#^skins/#', "{$wgStylePath}/", $wgResourceModules['skins.monaco']['scripts'] ) );
		}
		
		$out->addScript(
			'<!--[if IE]><script type="' . htmlspecialchars($wgJsMimeType) .
				'">\'abbr article aside audio canvas details figcaption figure ' .
				'footer header hgroup mark menu meter nav output progress section ' .
				'summary time video\'' .
				'.replace(/\w+/g,function(n){document.createElement(n)})</script><![endif]-->'
		);

		wfProfileOut(__METHOD__);
	}

	/**
	 * Add specific styles for this skin
	 *
	 * Don't add common/shared.css as it's kept in allinone.css
	 *
	 * @param $out OutputPage
	 */
	function setupSkinUserCss( OutputPage $out ){
		global $wgMonacoTheme, $wgMonacoAllowUsetheme, $wgRequest, $wgResourceModules;

		parent::setupSkinUserCss( $out );
		
		if ( method_exists( 'OutputPage', 'addModuleStyles' ) ) {
			// MediaWiki 1.17 and above, load the bulk of our styles with the resource loader
			$out->addModuleStyles( 'skins.monaco' );
		} else {
			// MediaWiki 1.16 and below, read our resource loader array and just load
			// the individual stylesheets. We don't really care much about older
			// versions of MW when people should be upgrading if they really want
			// new features, so anyone not using 1.17 can just deal with the extra
			// http requests... if they really care about the requests they can upgrade
			// as soon as 1.17 is available to them...
			foreach ( $wgResourceModules['skins.monaco']['styles'] as $path => $options ) {
				$path = preg_replace( '#^skins/#', '', $path );
				$out->addStyle( $path, $options['media'] );
			}
		}
		
		// ResourceLoader doesn't do ie specific styles that well iirc, so we have
		// to do those manually.
		$out->addStyle( 'monaco/style/css/monaco_ltie7.css', 'screen', 'lt IE 7' );
		$out->addStyle( 'monaco/style/css/monaco_ie7.css', 'screen', 'IE 7' );
		$out->addStyle( 'monaco/style/css/monaco_ie8.css', 'screen', 'IE 8' );
		
		// Likewise the masthead is a conditional feature so it's hard to include
		// inside of the ResourceLoader.
		if ( $this->showMasthead() ) {
			$out->addStyle( 'monaco/style/css/masthead.css', 'screen' );
		}
		
		$theme = $wgMonacoTheme;
		if ( $wgMonacoAllowUsetheme ) {
			$theme = $wgRequest->getText('usetheme', $theme);
			if ( preg_match('/[^a-z]/', $theme) ) {
				$theme = $wgMonacoTheme;
			}
		}
		if ( preg_match('/[^a-z]/', $theme) ) {
			$theme = "sapphire";
		}
		
		// Theme is another conditional feature, we can't really resource load this
		if ( isset($theme) && is_string($theme) && $theme != "sapphire" )
			$out->addStyle( "monaco/style/{$theme}/css/main.css", 'screen' );
		
		// rtl... hmm, how do we resource load this?
		$out->addStyle( 'monaco/style/rtl.css', 'screen', '', 'rtl' );
		
		// Make sure jQuery is loaded.
		if ( method_exists( 'OutputPage', 'includeJQuery' ) ) {
			$out->includeJQuery();
		}
		
	}

	function showMasthead() {
		global $wgMonacoUseMasthead;
		if ( !$wgMonacoUseMasthead ) {
			return false;
		}
		return !!$this->getMastheadUser();
	}
	
	function getMastheadUser() {
		global $wgTitle;
		if ( !isset($this->mMastheadUser) ) {
			$ns = $wgTitle->getNamespace();
			if ( $ns == NS_USER || $ns == NS_USER_TALK ) {
				$this->mMastheadUser = User::newFromName( strtok( $wgTitle->getText(), '/' ), false );
				$this->mMastheadTitleVisible = false;
			} else {
				$this->mMastheadUser = false;
				$this->mMastheadTitleVisible = true; // title is visible anyways if we're not on a masthead using page
			}
		}
		return $this->mMastheadUser;
	}
	
	function isMastheadTitleVisible() {
		if ( !$this->showMasthead() ) {
			return true;
		}
		$this->getMastheadUser();
		return $this->mMastheadTitleVisible;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 * @author Christian Williams
 	 * @author Daniel Friesen <http://daniel.friesen.name/>
	 * Added this functionality to MediaWiki, may need to add a patch to MW 1.16 and older
	 * This allows the skin to add body attributes while still integrating with
	 * MediaWiki's new headelement code. I modified the original Monaco code to
	 * use this cleaner method. I did not port loggedout or mainpage as these are
	 * generic, I added a separate hook so that a generic extension can be made
	 * to add those universally to all new skins.
	 */
	function addToBodyAttributes( $out, &$bodyAttrs ) {
		global $wgRequest;
		
		$bodyAttrs['class'] .= ' color2';
		
		$action = $wgRequest->getVal('action');
		if (in_array($action, array('edit', 'history', 'diff', 'delete', 'protect', 'unprotect', 'submit'))) {
			$bodyAttrs['class'] .= ' action_' . $action;
		} else if (empty($action) || in_array($action, array('view', 'purge'))) {
			$bodyAttrs['class'] .= ' action_view';
		}
		
		if ( $this->showMasthead() ) {
			if ( $this->isMastheadTitleVisible() ) {
				$bodyAttrs['class'] .= ' masthead-special';
			} else {
				$bodyAttrs['class'] .= ' masthead-regular';
			}
		}
		
		$bodyAttrs['id'] = "body";
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	public function addVariables(&$obj, &$tpl) {
		wfProfileIn(__METHOD__);
		global $wgLang, $wgContLang, $wgUser, $wgRequest, $wgTitle, $parserMemc;

		// We want to cache populated data only if user language is same with wiki language
		$cache = $wgLang->getCode() == $wgContLang->getCode();

		wfDebugLog('monaco', sprintf('Cache: %s, wgLang: %s, wgContLang %s', (int) $cache, $wgLang->getCode(), $wgContLang->getCode()));

		if($cache) {
			$key = wfMemcKey('MonacoDataOld');
			$data_array = $parserMemc->get($key);
		}

		if(empty($data_array)) {
			wfDebugLog('monaco', 'There is no cached $data_array, let\'s populate');
			wfProfileIn(__METHOD__ . ' - DATA ARRAY');
			// @kill $data_array['categorylist'] = DataProvider::getCategoryList();
			$data_array['toolboxlinks'] = $this->getToolboxLinks();
			//$data_array['sidebarmenu'] = $this->getSidebarLinks();
			wfProfileOut(__METHOD__ . ' - DATA ARRAY');
			if($cache) {
				$parserMemc->set($key, $data_array, 4 * 60 * 60 /* 4 hours */);
			}
		}

		if($wgUser->isLoggedIn()) {
			if(empty($wgUser->mMonacoData) || ($wgTitle->getNamespace() == NS_USER && $wgRequest->getText('action') == 'delete')) {

				wfDebugLog('monaco', 'mMonacoData for user is empty');

				$wgUser->mMonacoData = array();

				wfProfileIn(__METHOD__ . ' - DATA ARRAY');
				/*
				$text = $this->getTransformedArticle('User:'.$wgUser->getName().'/Monaco-sidebar', true);
				if(empty($text)) {
					$wgUser->mMonacoData['sidebarmenu'] = false;
				} else {
					$wgUser->mMonacoData['sidebarmenu'] = $this->parseSidebarMenu($text);
				}
				*/

				$text = $this->getTransformedArticle('User:'.$wgUser->getName().'/Monaco-toolbox', true);
				if(empty($text)) {
					$wgUser->mMonacoData['toolboxlinks'] = false;
				} else {
					$wgUser->mMonacoData['toolboxlinks'] = $this->parseToolboxLinks($text);
				}
				wfProfileOut(__METHOD__ . ' - DATA ARRAY');

				$wgUser->saveToCache();
			}

			/*
			if($wgUser->mMonacoData['sidebarmenu'] !== false && is_array($wgUser->mMonacoData['sidebarmenu'])) {
				wfDebugLog('monaco', 'There is user data for sidebarmenu');
				$data_array['sidebarmenu'] = $wgUser->mMonacoData['sidebarmenu'];
			}
			*/

			if($wgUser->mMonacoData['toolboxlinks'] !== false && is_array($wgUser->mMonacoData['toolboxlinks'])) {
				wfDebugLog('monaco', 'There is user data for toolboxlinks');
				$data_array['toolboxlinks'] = $wgUser->mMonacoData['toolboxlinks'];
			}
		}

		foreach($data_array['toolboxlinks'] as $key => $val) {
			if(isset($val['org']) && $val['org'] == 'whatlinkshere') {
				if(isset($tpl->data['nav_urls']['whatlinkshere'])) {
					$data_array['toolboxlinks'][$key]['href'] = $tpl->data['nav_urls']['whatlinkshere']['href'];
				} else {
					unset($data_array['toolboxlinks'][$key]);
				}
			}
			if(isset($val['org']) && $val['org'] == 'permalink') {
				if(isset($tpl->data['nav_urls']['permalink'])) {
					$data_array['toolboxlinks'][$key]['href'] = $tpl->data['nav_urls']['permalink']['href'];
				} else {
					unset($data_array['toolboxlinks'][$key]);
				}
			}
		}

		/*
		foreach($data_array['sidebarmenu'] as $key => $val) {
			if(isset($val['org']) && $val['org'] == 'editthispage') {
				if(isset($tpl->data['content_actions']['edit'])) {
					$data_array['sidebarmenu'][$key]['href'] = $tpl->data['content_actions']['edit']['href'];
				} else {
					unset($data_array['sidebarmenu'][$key]);
					foreach($data_array['sidebarmenu'] as $key1 => $val1) {
						if(isset($val1['children'])) {
							foreach($val1['children'] as $key2 => $val2) {
								if($key == $val2) {
									unset($data_array['sidebarmenu'][$key1]['children'][$key2]);
								}
							}
						}
					}
				}
			}
		}

		if( $wgUser->isAllowed( 'editinterface' ) ) {
			if(isset($data_array['sidebarmenu'])) {
				$monacoSidebarUrl = Title::makeTitle(NS_MEDIAWIKI, 'Monaco-sidebar')->getLocalUrl('action=edit');
				foreach($data_array['sidebarmenu'] as $nodeKey => $nodeVal) {
					if(empty($nodeVal['magic']) && isset($nodeVal['children']) && isset($nodeVal['depth']) && $nodeVal['depth'] === 1) {
						$data_array['sidebarmenu'][$nodeKey]['children'][] = $this->lastExtraIndex;
						$data_array['sidebarmenu'][$this->lastExtraIndex] = array(
							'text' => wfMsg('monaco-edit-this-menu'),
							'href' => $monacoSidebarUrl,
							'class' => 'Monaco-sidebar_edit'
						);
					}
				}
			}
		}
		*/

		$tpl->set('data', $data_array);

		// This is for WidgetLanguages
		$this->language_urls = $tpl->data['language_urls'];

		// Article content links (View, Edit, Delete, Move, etc.)
		$tpl->set('articlelinks', $this->getArticleLinks($tpl));

		// User actions links
		$tpl->set('userlinks', $this->getUserLinks($tpl));
/* @todo Look at this a tiny bit to figure out what js files to load
		if ($wgUser->isLoggedIn()) {
			$package = 'monaco_loggedin_js';
		}
		else {
			// list of namespaces and actions on which we should load package with YUI
			$ns = array(NS_SPECIAL);
			$actions = array('edit', 'preview', 'submit');

			if ( in_array($wgTitle->getNamespace(), $ns) || in_array($wgRequest->getVal('action', 'view'), $actions) ) {
				// edit mode & special/blog pages (package with YUI)
				$package = 'monaco_anon_everything_else_js';
			}
			else {
				// view mode (package without YUI)
				$package = 'monaco_anon_article_js';
			}$this->data['stylepath'].'/monaco/style/images/blank.gif'
		}

		global $wgStylePath, $wgStyleVersion;
		// use WikiaScriptLoader to load StaticChute in parallel with other scripts added by wgOut->addScript
	/*	wfProfileIn(__METHOD__ . '::JSloader');

		$jsReferences = array();

		/*if($allinone && $package == 'monaco_anon_article_js') {
			global $parserMemc, $wgStyleVersion, $wgEnableViewYUI;
			$cb = $parserMemc->get(wfMemcKey('wgMWrevId'));

			$addParam = "";
			if (!empty($wgEnableViewYUI)) {
				$addParam = "&yui=1";
			}

			global $wgDevelEnvironment;
			if(empty($wgDevelEnvironment)){
				$prefix = "__wikia_combined/";
			} else {
				global $wgWikiaCombinedPrefix;
				$prefix = $wgWikiaCombinedPrefix;
			}
			$jsReferences[] = "/{$prefix}cb={$cb}{$wgStyleVersion}&type=CoreJS";
		} else {
			$jsHtml = $StaticChute->getChuteHtmlForPackage($package);

			if ($package == "monaco_anon_article_js") {
				$jsHtml .= $StaticChute->getChuteHtmlForPackage("yui");
			}

			// get URL of StaticChute package (or a list of separated files) and use WSL to load it
			preg_match_all("/src=\"([^\"]+)/", $jsHtml, $matches, PREG_SET_ORDER);

			foreach($matches as $script) {
				$jsReferences[] = str_replace('&amp;', '&', $script[1]);
			}
		}*/
/*

		// scripts from getReferencesLinks() method
		foreach($tpl->data['references']['js'] as $script) {
			if (!empty($script['url'])) {
				$url = $script['url'];
				/*if($allinone && $package == 'monaco_anon_article_js' && strpos($url, 'title=-') > 0) {
					continue;
				}*//*
				$jsReferences[] = $url;
			}
		}
/*
		// scripts from $wgOut->mScripts
		// <script type="text/javascript" src="URL"></script>
		// load them using WSL and remove from $wgOut->mScripts
		//
		// macbre: Perform only for Monaco skin! New Answers skin does not use WikiaScriptLoader
		if ((get_class($this) == 'SkinMonaco') || (get_class($this) == 'SkinAnswers')) {
			global $wgJsMimeType;

			$headScripts = $tpl->data['headscripts'];
			preg_match_all("#<script type=\"{$wgJsMimeType}\" src=\"([^\"]+)\"></script>#", $headScripts, $matches, PREG_SET_ORDER);
			foreach($matches as $script) {
				$jsReferences[] = str_replace('&amp;', '&', $script[1]);
				$headScripts = str_replace($script[0], '', $headScripts);
			}
			$tpl->data['headscripts'] = $headScripts;

			// generate code to load JS files
			$jsReferences = Wikia::json_encode($jsReferences);
			$jsLoader = <<<EOF

		<script type="text/javascript">/*<![CDATA[* /
			(function(){
				var jsReferences = $jsReferences;
				var len = jsReferences.length;
				for(var i=0; i<len; i++)
					wsl.loadScript(jsReferences[i]);
			})();
		/*]]>* /</script>
EOF;

			$tpl->set('JSloader', $jsLoader);
		}

		wfProfileOut(__METHOD__ . '::JSloader');
*/
		// macbre: move media="print" CSS to bottom (RT #25638)
		//global $wgOut;
/*
		wfProfileIn(__METHOD__ . '::printCSS');

		$tmpOut = new OutputPage();
		$printStyles = array();
/*
		// let's filter media="print" CSS out
		$tmpOut->styles = $wgOut->styles;

		foreach($tmpOut->styles as $style => $options) {
			if (isset($options['media']) && $options['media'] == 'print') {
				unset($tmpOut->styles[$style]);
				$printStyles[$style] = $options;
			}
		}*/
/*
		// re-render CSS to be included in head
		$tpl->set('csslinks-urls', $tmpOut->styles);
		$tpl->set('csslinks', $tmpOut->buildCssLinks());

		// render CSS to be included at the bottom
		$tmpOut->styles = $printStyles;
		$tpl->set('csslinksbottom-urls', $printStyles);
		$tpl->set('csslinksbottom', $tmpOut->buildCssLinks());
*/
		//wfProfileOut(__METHOD__ . '::printCSS');

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function parseToolboxLinks($lines) {
		$nodes = array();
		if(is_array($lines)) {
			foreach($lines as $line) {
				$trimmed = trim($line, ' *');
				if (strlen($trimmed) == 0) { # ignore empty lines
					continue;
				}
				$item = MonacoSidebar::parseItem($trimmed);

				$nodes[] = $item;
			}
		}
		return $nodes;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getLines($message_key) {
		$revision = Revision::newFromTitle(Title::newFromText($message_key, NS_MEDIAWIKI));
		if(is_object($revision)) {
			if(trim($revision->getText()) != '') {
				$temp = MonacoSidebar::getMessageAsArray($message_key);
				if(count($temp) > 0) {
					wfDebugLog('monaco', sprintf('Get LOCAL %s, which contains %s lines', $message_key, count($temp)));
					$lines = $temp;
				}
			}
		}

		if(empty($lines)) {
			$lines = MonacoSidebar::getMessageAsArray($message_key);
			wfDebugLog('monaco', sprintf('Get %s, which contains %s lines', $message_key, count($lines)));
		}

		return $lines;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getToolboxLinks() {
		return $this->parseToolboxLinks($this->getLines('Monaco-toolbox'));
	}

	var $lastExtraIndex = 1000;

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function addExtraItemsToSidebarMenu(&$node, &$nodes) {
		wfProfileIn( __METHOD__ );

		$extraWords = array(
					'#voted#' => array('highest_ratings', 'GetTopVotedArticles'),
					'#popular#' => array('most_popular', 'GetMostPopularArticles'),
					'#visited#' => array('most_visited', 'GetMostVisitedArticles'),
					'#newlychanged#' => array('newly_changed', 'GetNewlyChangedArticles'),
					'#topusers#' => array('community', 'GetTopFiveUsers'));

		if(isset($extraWords[strtolower($node['org'])])) {
			if(substr($node['org'],0,1) == '#') {
				if(strtolower($node['org']) == strtolower($node['text'])) {
					$node['text'] = wfMsg(trim(strtolower($node['org']), ' *'));
				}
				$node['magic'] = true;
			}
			$results = DataProvider::$extraWords[strtolower($node['org'])][1]();
			$results[] = array('url' => SpecialPage::getTitleFor('Top/'.$extraWords[strtolower($node['org'])][0])->getLocalURL(), 'text' => strtolower(wfMsg('moredotdotdot')), 'class' => 'Monaco-sidebar_more');
			global $wgUser;
			if( $wgUser->isAllowed( 'editinterface' ) ) {
				if(strtolower($node['org']) == '#popular#') {
					$results[] = array('url' => Title::makeTitle(NS_MEDIAWIKI, 'Most popular articles')->getLocalUrl(), 'text' => wfMsg('monaco-edit-this-menu'), 'class' => 'Monaco-sidebar_edit');
				}
			}
			foreach($results as $key => $val) {
				$node['children'][] = $this->lastExtraIndex;
				$nodes[$this->lastExtraIndex]['text'] = $val['text'];
				$nodes[$this->lastExtraIndex]['href'] = $val['url'];
				if(!empty($val['class'])) {
					$nodes[$this->lastExtraIndex]['class'] = $val['class'];
				}
				$this->lastExtraIndex++;
			}
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function parseSidebarMenu($lines) {
		wfProfileIn(__METHOD__);
		$nodes = array();
		$nodes[] = array();
		$lastDepth = 0;
		$i = 0;
		if(is_array($lines)) {
			foreach($lines as $line) {
				if (strlen($line) == 0) { # ignore empty lines
					continue;
				}
				$node = MonacoSidebar::parseItem($line);
				$node['depth'] = strrpos($line, '*') + 1;
				if($node['depth'] == $lastDepth) {
					$node['parentIndex'] = $nodes[$i]['parentIndex'];
				} else if ($node['depth'] == $lastDepth + 1) {
					$node['parentIndex'] = $i;
				} else {
					for($x = $i; $x >= 0; $x--) {
						if($x == 0) {
							$node['parentIndex'] = 0;
							break;
						}
						if($nodes[$x]['depth'] == $node['depth'] - 1) {
							$node['parentIndex'] = $x;
							break;
						}
					}
				}
				if(substr($node['org'],0,1) == '#') {
					$this->addExtraItemsToSidebarMenu($node, $nodes);
				}
				$nodes[$i+1] = $node;
				$nodes[$node['parentIndex']]['children'][] = $i+1;
				$lastDepth = $node['depth'];
				$i++;
			}
		}
		wfProfileOut(__METHOD__);
		return $nodes;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getSidebarLinks() {
		return $this->parseSidebarMenu($this->getLines('Monaco-sidebar'));
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getTransformedArticle($name, $asArray = false) {
		wfProfileIn(__METHOD__);
		global $wgParser, $wgMessageCache;
		$revision = Revision::newFromTitle(Title::newFromText($name));
		if(is_object($revision)) {
			$text = $revision->getText();
			if(!empty($text)) {
				$ret = $wgParser->transformMsg($text, $wgMessageCache->getParserOptions());
				if($asArray) {
					$ret = explode("\n", $ret);
				}
				wfProfileOut(__METHOD__);
				return $ret;
			}
		}
		wfProfileOut(__METHOD__);
		return null;
	}

	/**
	 * Create arrays containing articles links (separated arrays for left and right part)
	 * Based on data['content_actions']
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getArticleLinks($tpl) {
		wfProfileIn( __METHOD__ );
		$links = array();

		if ( isset($tpl->data['content_navigation']) ) {
			// Use MediaWiki 1.18's better vector based content_navigation structure
			// to organize our tabs better
			foreach ( $tpl->data['content_navigation'] as $section => $nav ) {
				foreach ( $nav as $key => $val ) {
					if ( isset($val["redundant"]) && $val["redundant"] ) {
						continue;
					}
					
					$kk = ( isset($val["id"]) && substr($val["id"], 0, 3) == "ca-" ) ? substr($val["id"], 3) : $key;
					
					$msgKey = $kk;
					if ( $kk == "edit" ) {
						$title = $this->getRelevantTitle();
						$msgKey = $title->exists() || ( $title->getNamespace() == NS_MEDIAWIKI && !wfEmptyMsg( $title->getText() ) )
							? "edit" : "create";
					}
					
					// @note We know we're in 1.18 so we don't need to pass the second param to wfEmptyMsg anymore
					$tabText = wfMsg("monaco-tab-$msgKey");
					if ( $tabText && $tabText != '-' && !wfEmptyMsg("monaco-tab-$msgKey") ) {
						$val["text"] = $tabText;
					}
					
					switch($section) {
					case "namespaces": $side = 'right'; break;
					case "variants": $side = 'variants'; break;
					default: $side = 'left'; break;
					}
					$links[$side][$kk] = $val;
				}
			}
		} else {
			
			// rarely ever happens, but it does
			if ( empty( $tpl->data['content_actions'] ) ) {
				return $links;
			}

			# @todo: might actually be useful to move this to a global var and handle this in extension files --TOR
			$force_right = array( 'userprofile', 'talk', 'TheoryTab' );
			foreach($tpl->data['content_actions'] as $key => $val) {
				$msgKey = $key;
				if ( $key == "edit" ) {
					$msgKey = $this->mTitle->exists() || ( $this->mTitle->getNamespace() == NS_MEDIAWIKI && !wfEmptyMsg( $this->mTitle->getText(), wfMsg($this->mTitle->getText()) ) )
						? "edit" : "create";
				}
				$tabText = wfMsg("monaco-tab-$msgKey");
				if ( $tabText && $tabText != '-' && !wfEmptyMsg("monaco-tab-$msgKey", $tabText) ) {
					$val["text"] = $tabText;
				}

				if ( strpos($key, 'varlang-') === 0 ) {
					$links['variants'][$key] = $val;
				} else if ( strpos($key, 'nstab-') === 0 || in_array($key, $force_right) ) {
					$links['right'][$key] = $val;
				} else {
					$links['left'][$key] = $val;
				}
			}
		}
		if ( isset($links['left']) ) {
			foreach ( $links['left'] as $key => &$v ) {
				/* Fix icons */
				if($key == 'unprotect') {
					//unprotect uses the same icon as protect
					$v['icon'] = 'protect';
				} else if ($key == 'undelete') {
					//undelete uses the same icon as delelte
					$v['icon'] = 'delete';
				} else if ($key == 'purge') {
					$v['icon'] = 'refresh';
				} else if ($key == 'addsection') {
					$v['icon'] = 'talk';
				}
			}
		}
		
		wfProfileOut( __METHOD__ );
		return $links;
	}

	/**
	 * Generate links for user menu - depends on if user is logged in or not
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getUserLinks($tpl) {
		wfProfileIn( __METHOD__ );
		global $wgUser, $wgTitle, $wgRequest;

		$data = array();

		$page = Title::newFromURL( $wgRequest->getVal( 'title', '' ) );
		$page = $wgRequest->getVal( 'returnto', $page );
		$a = array();
		if ( strval( $page ) !== '' ) {
			$a['returnto'] = $page;
			$query = $wgRequest->getVal( 'returntoquery', $this->thisquery );
			if( $query != '' ) {
				$a['returntoquery'] = $query;
			}
		}
		$returnto = wfArrayToCGI( $a );

		if(!$wgUser->isLoggedIn()) {
			$signUpHref = Skin::makeSpecialUrl( 'UserLogin', $returnto );
			$data['login'] = array(
				'text' => wfMsg('login'),
				'href' => $signUpHref . "&type=login"
				);

			$data['register'] = array(
				'text' => wfMsg('nologinlink'),
				'href' => $signUpHref . "&type=signup"
				);

		} else {

			$data['userpage'] = array(
				'text' => $wgUser->getName(),
				'href' => $tpl->data['personal_urls']['userpage']['href']
				);

			$data['mytalk'] = array(
				'text' => $tpl->data['personal_urls']['mytalk']['text'],
				'href' => $tpl->data['personal_urls']['mytalk']['href']
				);

			if (isset($tpl->data['personal_urls']['watchlist'])) {
				$data['watchlist'] = array(
					/*'text' => $tpl->data['personal_urls']['watchlist']['text'],*/
					'text' => wfMsg('prefs-watchlist'),
					'href' => $tpl->data['personal_urls']['watchlist']['href']
					);
			}

			// In some cases, logout will be removed explicitly (such as when it is replaced by fblogout).
			if(isset($tpl->data['personal_urls']['logout'])){
				$data['logout'] = array(
					'text' => $tpl->data['personal_urls']['logout']['text'],
					'href' => $tpl->data['personal_urls']['logout']['href']
				);
			}


			$data['more']['userpage'] = array(
				'text' => wfMsg('mypage'),
				'href' => $tpl->data['personal_urls']['userpage']['href']
				);

			if(isset($tpl->data['personal_urls']['userprofile'])) {
				$data['more']['userprofile'] = array(
					'text' => $tpl->data['personal_urls']['userprofile']['text'],
					'href' => $tpl->data['personal_urls']['userprofile']['href']
					);
			}

			$data['more']['mycontris'] = array(
				'text' => wfMsg('mycontris'),
				'href' => $tpl->data['personal_urls']['mycontris']['href']
				);

			$data['more']['preferences'] = array(
				'text' => $tpl->data['personal_urls']['preferences']['text'],
				'href' => $tpl->data['personal_urls']['preferences']['href']
				);
		}

		// This function ignores anything from PersonalUrls hook which it doesn't expect.  This
		// loops lets it expect anything starting with "fb*" (because we need that for facebook connect).
		// Perhaps we should have some system to let PersonalUrls hook work again on its own?
		// - Sean Colombo
		
		foreach($tpl->data['personal_urls'] as $urlName => $urlData){
			if(strpos($urlName, "fb") === 0){
				$data[$urlName] = $urlData;
			}
		}

		wfProfileOut( __METHOD__ );
		return $data;
	}
} // end SkinMonaco

class MonacoTemplate extends QuickTemplate {

	/*
	 * Build returnto parameter with new returntoquery from MW 1.16
	 *
	 * @author Marooned
	 * @return string
	 */
	static function getReturntoParam($customReturnto = null) {
		global $wgTitle, $wgRequest;
		
		if ($customReturnto) {
			$returnto = "returnto=$customReturnto";
		} else {
			$thisurl = $wgTitle->getPrefixedURL();
			$returnto = "returnto=$thisurl";
		}
		
		if (!$wgRequest->wasPosted()) {
			$query = $wgRequest->getValues();
			unset($query['title']);
			unset($query['returnto']);
			unset($query['returntoquery']);
			$thisquery = wfUrlencode(wfArrayToCGI($query));
			if($thisquery != '')
				$returnto .= "&returntoquery=$thisquery";
		}
		return $returnto;
	}

	/**
	 * Shortcut for building these crappy blankimg based icons wikia probably could
	 * have implemented in a less ugly way.
	 * @author Daniel Friesen
	 */
	function blankimg( $attrs = array() ) {
		return Html::element( 'img', array( "src" => $this->data['blankimg'] ) + $attrs );
	}

	/**
	 * Make this a method so that subskins can override this if they reorganize
	 * the user header and need the more button to function.
	 * 
	 * @author Daniel Friesen
	 */
	function useUserMore() {
		global $wgMonacoUseMoreButton;
		return $wgMonacoUseMoreButton;
	}

	function execute() {
		wfProfileIn( __METHOD__ );
		global $wgContLang, $wgArticle, $wgUser, $wgLogo, $wgStyleVersion, $wgRequest, $wgTitle, $wgSitename;
		global $wgMonacoUseSitenoticeIsland;

		/*$skin = $wgUser->getSkin();
		$namespace = $wgTitle->getNamespace();*/
		$skin = $this->data['skin'];
		$action = $wgRequest->getText('action');
		$namespace = $wgTitle->getNamespace();

		$this->set( 'blankimg', $this->data['stylepath'].'/monaco/style/images/blank.gif' );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
		
		$this->setupRightSidebar();
		ob_start();
		wfRunHooks('MonacoRightSidebar', array($this));
		$this->addToRightSidebar( ob_get_contents() );
		ob_end_clean();
		
		$this->html( 'headelement' );

/*
$allinone = $wgRequest->getBool('allinone', $wgAllInOne);
echo WikiaAssets::GetCoreCSS($skin->themename, $wgContLang->isRTL(), $allinone); // StaticChute + browser specific
echo WikiaAssets::GetExtensionsCSS($this->data['csslinks-urls']);
echo WikiaAssets::GetThemeCSS($skin->themename, $skin->skinname); 
echo WikiaAssets::GetSiteCSS($skin->themename, $wgContLang->isRTL(), $allinone); // Common.css, Monaco.css, -
echo WikiaAssets::GetUserCSS($this->data['csslinks-urls']);
*/

	$this->printAdditionalHead(); // @fixme not valid
?>
<?php		wfProfileOut( __METHOD__ . '-head');  ?>

<?php
wfProfileIn( __METHOD__ . '-body'); ?>
<?php

	// this hook allows adding extra HTML just after <body> opening tag
	// append your content to $html variable instead of echoing
	$html = '';
	wfRunHooks('GetHTMLAfterBody', array ($this, &$html));
	echo $html;
?>
<div id="skiplinks"> 
	<a class="skiplink" href="#article" tabIndex=1>Skip to Content</a> 
	<a class="skiplink wikinav" href="#widget_sidebar" tabIndex=1>Skip to Navigation</a> 
</div>

	<div id="background_accent1"></div>
	<div id="background_accent2"></div>

	<!-- HEADER -->
<?php
		// curse like cobranding
		$this->printCustomHeader();

		wfProfileIn( __METHOD__ . '-header'); ?>
	<div id="wikia_header" class="color2">
		<div class="monaco_shrinkwrap">
<?php $this->printMonacoBranding(); ?>
<?php $this->printUserData(); ?>
		</div>
	</div>

<?php if (wfRunHooks('AlternateNavLinks')): ?>
		<div id="background_strip" class="reset">
			<div class="monaco_shrinkwrap">

			<div id="accent_graphic1"></div>
			<div id="accent_graphic2"></div>
			</div>
		</div>
<?php endif; ?>
		<!-- /HEADER -->
<?php		wfProfileOut( __METHOD__ . '-header'); ?>

		<!-- PAGE -->
<?php		wfProfileIn( __METHOD__ . '-page'); ?>

	<div id="monaco_shrinkwrap_main" class="monaco_shrinkwrap with_left_sidebar<?php if ( $this->hasRightSidebar() ) { echo ' with_right_sidebar'; } ?>">
		<div id="page_wrapper">
<?php wfRunHooks('MonacoBeforePage', array($this)); ?>
<?php $this->printBeforePage(); ?>
<?php if ( $wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) { ?>
			<div class="page">
				<div id="siteNotice"><?php $this->html('sitenotice') ?></div>
			</div>
<?php } ?>
			<div id="wikia_page" class="page">
<?php
			$this->printMasthead();
			wfRunHooks('MonacoBeforePageBar', array($this));
			$this->printPageBar(); ?>
					<!-- ARTICLE -->

<?php		wfProfileIn( __METHOD__ . '-article'); ?>
				<article id="article" aria-role=main aria-labeledby="firstHeading">
					<a name="top" id="top"></a>
					<?php wfRunHooks('MonacoAfterArticle', array($this)); // recipes: not needed? ?>
					<?php if ( !$wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
					<?php $this->printFirstHeading(); ?>
					<div id="bodyContent" class="body_content">
						<h2 id="siteSub"><?php $this->msg('tagline') ?></h2>
						<?php if($this->data['subtitle']) { ?><div id="contentSub"><?php $this->html('subtitle') ?></div><?php } ?>
						<?php if($this->data['undelete']) { ?><div id="contentSub2"><?php     $this->html('undelete') ?></div><?php } ?>
						<?php if($this->data['newtalk'] ) { ?><div class="usermessage noprint"><?php $this->html('newtalk')  ?></div><?php } ?>
						<?php if(!empty($skin->newuemsg)) { echo $skin->newuemsg; } ?>

						<!-- start content -->
<?php
						// Display content
						$this->printContent();

						$this->printCategories();
						?>
						<!-- end content -->
						<?php if($this->data['dataAfterContent']) { $this->html('dataAfterContent'); } ?>
						<div class="visualClear"></div>
					</div>

				</article>
				<!-- /ARTICLE -->
				<?php

			wfProfileOut( __METHOD__ . '-article'); ?>

			<!-- ARTICLE FOOTER -->
<?php		wfProfileIn( __METHOD__ . '-articlefooter'); ?>
<?php
global $wgTitle, $wgOut;
$custom_article_footer = '';
$namespaceType = '';
wfRunHooks( 'CustomArticleFooter', array( &$this, &$tpl, &$custom_article_footer ));
if ($custom_article_footer !== '') {
	echo $custom_article_footer;
} else {
	//default footer
	if ($wgTitle->exists() && $wgTitle->isContentPage() && !$wgTitle->isTalkPage()) {
		$namespaceType = 'content';
	}
	//talk footer
	elseif ($wgTitle->isTalkPage()) {
		$namespaceType = 'talk';
	}
	//disable footer on some namespaces
	elseif ($namespace == NS_SPECIAL) {
		$namespaceType = 'none';
	}

	$action = $wgRequest->getVal('action', 'view');
	if ($namespaceType != 'none' && in_array($action, array('view', 'purge', 'edit', 'history', 'delete', 'protect'))) {
		$nav_urls = $this->data['nav_urls'];

		$actions = '';
		if (!empty($this->data['content_actions']['history']) || !empty($nav_urls['recentchangeslinked'])) {

			$actions =
								'<ul id="articleFooterActions3" class="actions clearfix">' .
								(!empty($this->data['content_actions']['history']) ? ('
								<li id="fe_history"><a id="fe_history_icon" href="' . htmlspecialchars($this->data['content_actions']['history']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_history_img" class="sprite history" /></a> <div><a id="fe_history_link" href="' . htmlspecialchars($this->data['content_actions']['history']['href']) . '">' . $this->data['content_actions']['history']['text'] . '</a></div></li>') : '') .

								(!empty($nav_urls['recentchangeslinked']) ? ('
								<li id="fe_recent"><a id="fe_recent_icon" href="' . htmlspecialchars($nav_urls['recentchangeslinked']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_recent_img" class="sprite recent" /></a> <div><a id="fe_recent_link" href="' . htmlspecialchars($nav_urls['recentchangeslinked']['href']) . '">' . wfMsgHtml('recentchangeslinked') . '</a></div></li>') : '');

		}
		if (!empty($nav_urls['permalink']) || !empty($nav_urls['whatlinkshere'])) {
			$actions .=
								'<ul id="articleFooterActions4" class="actions clearfix">' .

								(!empty($nav_urls['permalink']) ? ('
								<li id="fe_permalink"><a id="fe_permalink_icon" href="' . htmlspecialchars($nav_urls['permalink']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_permalink_img" class="sprite move" /></a> <div><a id="fe_permalink_link" href="' . htmlspecialchars($nav_urls['permalink']['href']) . '">' . $nav_urls['permalink']['text'] . '</a></div></li>') : '') .

								((!empty($nav_urls['whatlinkshere'])) ? ('
								<li id="fe_whatlinkshere"><a id="fe_whatlinkshere_icon" href="' . htmlspecialchars($nav_urls['whatlinkshere']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_whatlinkshere_img" class="sprite pagelink" /></a> <div><a id="fe_whatlinkshere_link" href="' . htmlspecialchars($nav_urls['whatlinkshere']['href']) . '">' . wfMsgHtml('whatlinkshere') . '</a></div></li>') : '') . '</ul>';



		}

		global $wgArticle, $wgLang;
?>
			<div id="articleFooter" class="reset article_footer">
				<table cellspacing="0">
					<tr>
						<td class="col1">
							<ul class="actions" id="articleFooterActions">
<?php
		if ($namespaceType == 'talk') {
			$custom_article_footer = '';
			wfRunHooks('AddNewTalkSection', array( &$this, &$tpl, &$custom_article_footer ));
			if ($custom_article_footer != '')
				echo $custom_article_footer;
		} else {
			echo "								";
			echo Html::rawElement( 'li', null,
				Html::rawElement( 'a', array( "id" => "fe_edit_icon", "href" => $wgTitle->getEditURL() ),
					$this->blankimg( array( "id" => "fe_edit_img", "class" => "sprite edit" ) ) ) .
				' ' .
				Html::rawElement( 'div', null,
					wfMsgHtml('monaco-footer-improve',
						Html::element( 'a', array( "id" => "fe_edit_link", "href" => $wgTitle->getEditURL() ), wfMsg('monaco-footer-improve-linktext') ) ) ) );
			echo "\n";
		}

		if(is_object($wgArticle) && $wgArticle->getTimestamp()) {
			$timestamp = $wgArticle->getTimestamp();
			$lastUpdate = $wgLang->date($timestamp);
			$userId = $wgArticle->getUser();
			if($userId > 0) {
				$user = User::newFromName($wgArticle->getUserText());
				$userPageTitle = $user->getUserPage();
				$userPageLink = $userPageTitle->getLocalUrl();
				$userPageExists = $userPageTitle->exists();
				$feUserIcon = $this->blankimg( array( "id" => "fe_user_img", "class" => "sprite user" ) );
				if ( $userPageExists )
					$feUserIcon = Html::rawElement( 'a', array( "id" => "fe_user_icon", "href" => $userPageLink ), $feUserIcon );
?>
								<li><?php echo $feUserIcon ?> <div><?php echo wfMsgHtml('monaco-footer-lastedit', $skin->link( $userPageTitle, htmlspecialchars($user->getName()), array( "id" => "fe_user_link" ) ), Html::element('time', array( 'datetime' => wfTimestamp( TS_ISO_8601, $$timestamp ) ), $lastUpdate)) ?></div></li>
<?php
			}
		}
?>
							</ul>
							<?php // echo $namespaceType == 'content' ? $actions : '' ?>
						</td>
						<td class="col2">
<?php
		//if ($namespaceType != 'content' ) {
?>
							<?php echo $actions ?>
<?php
		//} else {
?>
							<ul class="actions" id="articleFooterActions2">
								<li><a id="fe_random_icon" href="<?php echo Skin::makeSpecialUrl( 'Randompage' ) ?>"><img src="<?php $this->text('blankimg') ?>" id="fe_random_img" class="sprite random" /></a> <div><a id="fe_random_link" href="<?php echo Skin::makeSpecialUrl( 'Randompage' ) ?>"><?php echo wfMsgHtml('viewrandompage') ?></a></div></li>

						</td>
					</tr>
				</table>
			</div>
<?php
	} //end $namespaceType != 'none'
} //end else from CustomArticleFooter hook
?>
				<!-- /ARTICLE FOOTER -->
<?php		wfProfileOut( __METHOD__ . '-articlefooter'); ?>

			</div>
			<!-- /PAGE -->
<?php		wfProfileOut( __METHOD__ . '-page'); ?>

			<noscript><link rel="stylesheet" type="text/css" href="<?php $this->text( 'stylepath' ) ?>/monaco/style/css/noscript.css?<?php echo $wgStyleVersion ?>" /></noscript>
<?php
	if(!($wgRequest->getVal('action') != '' || $namespace == NS_SPECIAL)) {
		//$this->html('WikiaScriptLoader');
		$this->html('JSloader');
		$this->html('headscripts');
	}
	//echo '<script type="text/javascript">/*<![CDATA[*/for(var i=0;i<wgAfterContentAndJS.length;i++){wgAfterContentAndJS[i]();}/*]]>*/</script>' . "\n";

?>
		</div>
<?php $this->printRightSidebar() ?>
		<!-- WIDGETS -->
<?php		wfProfileIn( __METHOD__ . '-navigation'); ?>
		<div id="widget_sidebar" class="reset widget_sidebar left_sidebar sidebar">

<?php
		// Rewrite the logo to have the last modified timestamp so that a the newer one will be used after an update.
		// $wgLogo =
		?>
			<div id="wiki_logo" style="background-image: url(<?php $this->html( 'logopath' ) ?>);"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>" accesskey="z" rel="home"><?php echo $wgSitename ?></a></div>
			<!--[if lt IE 7]>
			<style type="text/css">
				#wiki_logo {
					background-image: none !important;
					filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo Xml::escapeJsString( $this->data['logopath'] ) ?>', sizingMethod='image');
				}
			</style>
			<![endif]-->

			<!-- SEARCH/NAVIGATION -->
			<div class="widget sidebox navigation_box" id="navigation_widget" aria-role=navigation>
<?php
	global $wgSitename;
	$msgSearchLabel = wfMsgHtml('Tooltip-search');
	$searchLabel = wfEmptyMsg('Tooltip-search', $msgSearchLabel) ? (wfMsgHtml('ilsubmit').' '.$wgSitename.'...') : $msgSearchLabel;
?>
			<div id="search_box" class="color1" aria-role="search">
				<form action="<?php $this->text('searchaction') ?>" id="searchform">
					<label style="display: none;" for="search_field"><?php echo htmlspecialchars($searchLabel) ?></label>
					<?php echo Html::input( 'search', '', 'text', array(
						'id' => "searchInput",
						'name' => "search",
						'type' => "text",
						'maxlength' => 200,
						'alt' => $searchLabel,
						'aria-label' => $searchLabel,
						'placeholder' => $searchLabel,
						'tabIndex' => 2,
						'aria-required' => 'true',
						'aria-flowto' => "search-button",
					) + $skin->tooltipAndAccesskeyAttribs('search') ); ?>
					<?php global $wgSearchDefaultFulltext; ?>
					<input type="hidden" name="<?php echo ( $wgSearchDefaultFulltext ) ? 'fulltext' : 'go'; ?>" value="1" />
					<input type="image" alt="<?php echo htmlspecialchars(wfMsgHtml('search')) ?>" src="<?php $this->text('blankimg') ?>" id="search-button" class="sprite search" tabIndex=2 />
				</form>
			</div>
<?php
	$monacoSidebar = new MonacoSidebar();
	if(isset($this->data['content_actions']['edit'])) {
		$monacoSidebar->editUrl = $this->data['content_actions']['edit']['href'];
	}
	echo $monacoSidebar->getCode();

	echo '<table cellspacing="0" id="link_box_table">';
	//BEGIN: create dynamic box
	$showDynamicLinks = true;
	$dynamicLinksArray = array();

	global $wgRequest;
	if ( $wgRequest->getText( 'action' ) == 'edit' || $wgRequest->getText( 'action' ) == 'submit' ) {
		$showDynamicLinks = false;
	}

	if ( $showDynamicLinks ) {
		$dynamicLinksInternal = array();
		
		global $wgMonacoDynamicCreateOverride;
		$createPage = null;
		$writeArticleUrl = wfMsg('dynamic-links-write-article-url');
		if ( $writeArticleUrl && $writeArticleUrl !== '-' && !wfEmptyMsg('dynamic-links-write-article-url', $writeArticleUrl) ) {
			$createPage = Title::newFromText($writeArticleUrl);
		}
		if ( !isset($createPage) && !empty($wgMonacoDynamicCreateOverride) ) {
			$createPage = Title::newFromText($wgMonacoDynamicCreateOverride);
		}
		if ( !isset($createPage) ) {
			if ( SpecialPage::exists('CreatePage') ) {
				$createPage = SpecialPage::getTitleFor('CreatePage');
			}
		}
		if ( isset($createPage) && ( $wgUser->isAllowed('edit') || $wgUser->isAnon() ) ) {
			/* Redirect to login page instead of showing error, see Login friction project */
			$dynamicLinksInternal["write-article"] = array(
				'url' => $wgUser->isAnon() ? SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$createPage->getPrefixedDBkey())) : $createPage->getLocalURL(),
				'icon' => 'edit',
			);
		}
		global $wgEnableUploads, $wgUploadNavigationUrl;
		if ( ( $wgEnableUploads || $wgUploadNavigationUrl ) && ( $wgUser->isAllowed('upload') || $wgUser->isAnon() || $wgUploadNavigationUrl ) ) {
			$uploadPage = SpecialPage::getTitleFor('Upload');
			/* Redirect to login page instead of showing error, see Login friction project */
			if ( $wgUploadNavigationUrl ) {
				$url = $wgUploadNavigationUrl;
			} else {
				$url = $wgUser->isAnon() ? SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$uploadPage->getPrefixedDBkey())) : $uploadPage->getLocalURL();
			}
			$dynamicLinksInternal["add-image"] = array(
				'url' => $url,
				'icon' => 'photo',
			);
		}
		
		$this->extendDynamicLinks( $dynamicLinksInternal );
		wfRunHooks( 'MonacoDynamicLinks', array( $this, &$dynamicLinksInternal ) );
		$this->extendDynamicLinksAfterHook( $dynamicLinksInternal );
		
		$dynamicLinksUser = array();
		foreach ( explode( "\n", wfMsgForContent('dynamic-links') ) as $line ) {
			if ( !$line || $line[0] == ' ' )
				continue;
			$line = trim($line, '* ');
			$url = wfMsg("dynamic-links-$line-url");
			if ( $url && $url !== '-' && !wfEmptyMsg("dynamic-links-$line-url", $url) ) {
				$url = Title::newFromText($url);
				if ( $url ) {
					$dynamicLinksUser[$line] = array(
						"url" => $url,
						"icon" => "edit", // @note Wikia used messy css sprites so we can't really let this be customized easily
					);
				}
			}
		}
		
		foreach ( $dynamicLinksUser as $key => $value )
			$dynamicLinksArray[$key] = $value;
		foreach ( $dynamicLinksInternal as $key => $value )
			$dynamicLinksArray[$key] = $value;
	}

	if (count($dynamicLinksArray) > 0) {
?>
		<tbody id="link_box_dynamic">
			<tr>
				<td colspan="2">
					<ul>
<?php
			foreach ($dynamicLinksArray as $key => $link) {
				$link['id'] = "dynamic-links-$key";
				if ( !isset($link['text']) )
					$link['text'] = wfMsg("dynamic-links-$key");
				echo "						";
				echo Html::rawElement( 'li', array( "id" => "{$link['id']}-row", "class" => "link_box_dynamic_item" ),
					Html::rawElement( 'a', array( "id" => "{$link['id']}-icon", "href" => $link['url'], "tabIndex" => -1 ),
						$this->blankimg( array( "id" => "{$link['id']}-img", "class" => "sprite {$link['icon']}", "alt" => "" ) ) ) .
					' ' .
					Html::element( 'a', array( "id" => "{$link['id']}-link", "href" => $link["url"], "tabIndex" => 3 ), $link["text"] ) );
				echo "\n";
			}
?>
					</ul>
				</td>
			</tr>
		</tbody>
<?php
	}
	//END: create dynamic box

	//BEGIN: create static box
	$linksArrayL = $linksArrayR = array();
	$linksArray = $this->data['data']['toolboxlinks'];

	//add user specific links
	if(!empty($nav_urls['contributions'])) {
		$linksArray[] = array('href' => $nav_urls['contributions']['href'], 'text' => wfMsg('contributions'));
	}
	if(!empty($nav_urls['blockip'])) {
		$linksArray[] = array('href' => $nav_urls['blockip']['href'], 'text' => wfMsg('blockip'));
	}
	if(!empty($nav_urls['emailuser'])) {
		$linksArray[] = array('href' => $nav_urls['emailuser']['href'], 'text' => wfMsg('emailuser'));
	}

	if(is_array($linksArray) && count($linksArray) > 0) {
		global $wgSpecialPagesRequiredLogin;
		for ($i = 0, $max = max(array_keys($linksArray)); $i <= $max; $i++) {
			$item = isset($linksArray[$i]) ? $linksArray[$i] : false;
			//Redirect to login page instead of showing error, see Login friction project
			if ($item !== false && $wgUser->isAnon() && isset($item['specialCanonicalName']) && in_array($item['specialCanonicalName'], $wgSpecialPagesRequiredLogin)) {
				$returnto = SpecialPage::getTitleFor($item['specialCanonicalName'])->getPrefixedDBkey();
				$item['href'] = SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$returnto));
			}
			$i & 1 ? $linksArrayR[] = $item : $linksArrayL[] = $item;
		}
	}

	if(count($linksArrayL) > 0 || count($linksArrayR) > 0) {
?>
		<tbody id="link_box" class="color2 linkbox_static">
			<tr>
				<td>
					<ul>
<?php
		if(is_array($linksArrayL) && count($linksArrayL) > 0) {
			foreach($linksArrayL as $key => $val) {
				if ($val === false) {
					echo '<li>&nbsp;</li>';
				} else {
?>
						<li><a<?php if ( !isset($val['internal']) || !$val['internal'] ) { ?> rel="nofollow"<?php } ?> href="<?php echo htmlspecialchars($val['href']) ?>" tabIndex=3><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php
				}
			}
		}
?>
					</ul>
				</td>
				<td>
					<ul>
<?php
		if(is_array($linksArrayR) && count($linksArrayR) > 0) {
		    foreach($linksArrayR as $key => $val) {
				if ($val === false) {
					echo '<li>&nbsp;</li>';
				} else {
?>
						<li><a<?php if ( !isset($val['internal']) || !$val['internal'] ) { ?> rel="nofollow"<?php } ?> href="<?php echo htmlspecialchars($val['href']) ?>" tabIndex=3><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php
				}
			}
		}
?>
						<li style="font-size: 1px; position: absolute; top: -10000px"><a href="<?php echo Title::newFromText('Special:Recentchanges')->getLocalURL() ?>" accesskey="r">Recent changes</a><a href="<?php echo Title::newFromText('Special:Random')->getLocalURL() ?>" accesskey="x">Random page</a></li>
					</ul>
				</td>
			</tr>
		</tbody>
<?php
	}
	//END: create static box
?>
	</table>
			</div>
			<!-- /SEARCH/NAVIGATION -->
<?php		$this->printExtraSidebar(); ?>
<?php		wfRunHooks( 'MonacoSidebarEnd', array( $this ) ); ?>
<?php		wfProfileOut( __METHOD__ . '-navigation'); ?>
<?php		wfProfileIn( __METHOD__ . '-widgets'); ?>

		</div>
		<!-- /WIDGETS -->
	<!--/div-->
<?php
wfProfileOut( __METHOD__ . '-widgets');

// curse like cobranding
$this->printCustomFooter();
?>

<?php

echo '</div>';

$this->html('bottomscripts'); /* JS call to runBodyOnloadHook */
wfRunHooks('SpecialFooter');
?>
		<div id="positioned_elements" class="reset"></div>
<?php
$this->delayedPrintCSSdownload();
$this->html('reporttime');
wfProfileOut( __METHOD__ . '-body');
?>

	</body>
</html>
<?php
		wfProfileOut( __METHOD__ );
	} // end execute()

	//@author Marooned
	function delayedPrintCSSdownload() {
		global $wgRequest;

		//regular download
		if ($wgRequest->getVal('printable')) {
			// RT #18411
			$this->html('mergedCSSprint');
			// RT #25638
			echo "\n\t\t";
			$this->html('csslinksbottom');
		} else {
		}
	}

	// allow subskins to tweak dynamic links
	function extendDynamicLinks( &$dynamicLinks ) {}
	function extendDynamicLinksAfterHook( &$dynamicLinks ) {}

	// allow subskins to add extra sidebar extras
	function printExtraSidebar() {}
	
	function sidebarBox( $bar, $cont, $options=array() ) {
		$titleClass = "sidebox_title";
		$contentClass = "sidebox_contents";
		if ( isset($options["widget"]) && $options["widget"] ) {
			$titleClass .= " widget_contents";
			$contentClass .= " widget_title";
		}
		
		$attrs = array( "class" => "widget sidebox" );
		if ( isset($options["id"]) ) {
			$attrs["id"] = $options["id"];
		}
		if ( isset($options["class"]) ) {
			$attrs["class"] .= " {$options["class"]}";
		}
		
		$box = "			";
		$box .= Html::openElement( 'div', $attrs );
		$box .= "\n";
		if ( isset($bar) ) {
			$box .= "				";
			$out = wfMsg( $bar );
			$out = wfEmptyMsg($bar, $out) ? $bar : $out;
			if ( $out )
				$box .= Html::element( 'h3', array( "class" => "color1 $titleClass" ), $out ) . "\n";
		}
		if ( is_array( $cont ) ) {
			$boxContent .= "					<ul>\n";
			foreach ( $cont as $key => $val ) {
				$boxContent .= "						" . $this->makeListItem($key, $val) . "\n";

			}
			$boxContent .= "					</ul>\n";
		} else {
			$boxContent = $cont;
		}
		if ( !isset($options["wrapcontents"]) || $options["wrapcontents"] ) {
			$boxContent = "				".Html::rawElement( 'div', array( "class" => $contentClass ), "\n".$boxContent."				" ) . "\n";
		}
		$box .= $boxContent;
		$box .= Xml::closeElement( 'div ');
		echo $box;
	}
	
	function customBox( $bar, $cont ) {
		$this->sidebarBox( $bar, $cont );
	}
	
	// hook for subskins
	function setupRightSidebar() {}
	
	function addToRightSidebar($html) {
		$this->mRightSidebar .= $html;
	}
	
	function hasRightSidebar() {
		return (bool)trim($this->mRightSidebar);
	}
	
	// Hook for things that you only want in the sidebar if there are already things
	// inside the sidebar.
	function lateRightSidebar() {}
	
	function printRightSidebar() {
		if ( $this->hasRightSidebar() ) {
?>
		<!-- RIGHT SIDEBAR -->
		<div id="right_sidebar" class="sidebar right_sidebar">
<?php $this->lateRightSidebar(); ?>
<?php wfRunHooks('MonacoRightSidebar::Late', array($this)); ?>
<?php echo $this->mRightSidebar ?>
		</div>
		<!-- /RIGHT SIDEBAR -->
<?php
		}
	}
	
	function printMonacoBranding() {
		ob_start();
		wfRunHooks( 'MonacoBranding', array( $this ) );
		$branding = ob_get_contents();
		ob_end_clean();
		
		if ( trim($branding) ) { ?>
			<div id="monacoBranding">
<?php echo $branding; ?>
			</div>
<?php
		}
	}
	
	function printUserData() {
		$skin = $this->data['skin'];
		?>
			<div id="userData">
<?php
		
		$custom_user_data = "";
		if( !wfRunHooks( 'CustomUserData', array( &$this, &$tpl, &$custom_user_data ) ) ){
			wfDebug( __METHOD__ . ": CustomUserData messed up skin!\n" );
		}
		
		if( $custom_user_data ) {
			echo $custom_user_data;
		} else {
			global $wgUser;
			
			// Output the facebook connect links that were added with PersonalUrls.
			// @author Sean Colombo
			foreach($this->data['userlinks'] as $linkName => $linkData){
				// 
				if( !empty($linkData['html']) ){
					echo $linkData['html']; 
				}
			}
			
			if ($wgUser->isLoggedIn()) {
				foreach( array( "username" => "userpage", "mytalk" => "mytalk", "watchlist" => "watchlist" ) as $key ) {
					echo "				" . Html::rawElement( 'span', array( 'id' => "header_$id" ),
						Html::element( 'a', array( 'href' => $this->data['userlinks'][$key]['href'] ) + $skin->tooltipAndAccesskeyAttribs("pt-$key"), $this->data['userlinks'][$key]['text'] ) ) . "\n";
				}
				
			?>
<?php
				if ( $this->useUserMore() ) { ?>
				<span class="more hovermenu">
					<button id="headerButtonUser" class="header-button color1" tabIndex="-1"><?php echo trim(wfMsgHtml('moredotdotdot'), ' .') ?><img src="<?php $this->text('blankimg') ?>" /></button>
					<span class="invisibleBridge"></span>
					<div id="headerMenuUser" class="headerMenu color1 reset">
						<ul>
<?php
				foreach ( $this->data['userlinks']['more'] as $key => $link ) {
					echo Html::rawElement( 'li', array( 'id' => "header_$key" ),
						Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
				} ?>
						</ul>
					</div>
				</span>
<?php
				} else {
					foreach ( $this->data['userlinks']['more'] as $key => $link ) {
						echo Html::rawElement( 'span', array( 'id' => "header_$key" ),
							Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
					} ?>
<?php
				} ?>
				<span>
					<?php echo Html::element( 'a', array( 'href' => $this->data['userlinks']['logout']['href'] ) + $skin->tooltipAndAccesskeyAttribs('pt-logout'), $this->data['userlinks']['logout']['text'] ); ?>
				</span>
<?php
			} else {
?>
				<span id="userLogin">
					<a class="wikia-button" id="login" href="<?php echo htmlspecialchars($this->data['userlinks']['login']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['login']['text']) ?></a>
				</span>

					<a class="wikia-button" id="register" href="<?php echo htmlspecialchars($this->data['userlinks']['register']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['register']['text']) ?></a>

<?php
			}
		} ?>
			</div>
<?php
	}
	
	// allow subskins to add pre-page islands
	function printBeforePage() {}

	// curse like cobranding
	function printCustomHeader() {}
	function printCustomFooter() {}

	// Made a separate method so recipes, answers, etc can override. This is for any additional CSS, Javacript, etc HTML
	// that appears within the HEAD tag
	function printAdditionalHead(){}

	function printMasthead() {
		$skin = $this->data['skin'];
		if ( !$skin->showMasthead() ) {
			return;
		}
		global $wgLang;
		$user = $skin->getMastheadUser();
		$username = $user->isAnon() ? wfMsg('masthead-anonymous-user') : $user->getName();
		$editcount = $wgLang->formatNum($user->isAnon() ? 0 : $user->getEditcount());
		?>
			<div id="user_masthead" class="accent reset clearfix">
				<div id="user_masthead_head" class="clearfix">
					<h2><?php echo htmlspecialchars($username); ?>
<?php if ( $user->isAnon() ) { ?>
						<small id="user_masthead_anon"><?php echo $user->getName(); ?></small>
<?php } else { ?>
						<div id="user_masthead_scorecard" class="dark_text_1"><?php echo htmlspecialchars($editcount); ?></div>
<?php } ?>
					</h2>
				</div>
				<ul id="user_masthead_tabs" class="nav_links">
<?php
				foreach ( $this->data['articlelinks']['right'] as $navLink ) {
					$class = "color1";
					if ( isset($navLink["class"]) ) {
						$class .= " {$navLink["class"]}";
					}
					echo Html::rawElement( 'li', array( "class" => $class ),
						Html::element( 'a', array( "href" => $navLink["href"] ), $navLink["text"] ) );
				} ?>
				</ul>
			</div>
<?php
		unset($this->data['articlelinks']['right']); // hide the right articlelinks since we've already displayed them
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printPageBar(){
		// Allow for other skins to conditionally include it
		$this->realPrintPageBar();
	}
	function realPrintPageBar(){
		foreach ( $this->data['articlelinks'] as $side => $links ) {
			foreach ( $links as $key => $link ) {
				$this->data['articlelinks'][$side][$key]["id"] = "ca-$key";
				if ( $side == "left" && !isset($link["icon"]) ) {
					$this->data['articlelinks'][$side][$key]["icon"] = $key;
				}
			}
		}
		
		$bar = array();
		if ( isset($this->data['articlelinks']['right']) ) {
			$bar[] = array(
				"id" => "page_tabs",
				"type" => "tabs",
				"class" => "primary_tabs",
				"links" => $this->data['articlelinks']['right'],
			);
		}
		if ( isset($this->data['articlelinks']['variants']) ) {
			global $wgContLang;
			$preferred = $wgContLang->getPreferredVariant();
			$bar[] = array(
				"id" => "page_variants",
				"type" => "tabs",
				"class" => "page_variants",
				"links" => array(
					array(
						"class" => 'selected',
						"text" => $wgContLang->getVariantname( $preferred ),
						"href" => $this->data['skin']->getTitle()->getLocalURL( '', $preferred ),
						"links" => $this->data['articlelinks']['variants'],
					)
				)
			);
		}
		$bar[] = array(
			"id" => "page_controls",
			"type" => "buttons",
			"class" => "page_controls",
			"bad_hook" => "MonacoAfterArticleLinks",
			"links" => $this->data['articlelinks']['left'],
		);
		$this->printCustomPageBar( $bar );
	}

	var $primaryPageBarPrinted = false;
	function printCustomPageBar( $bar ) {
		global $wgMonacoCompactSpecialPages;
		$isPrimary = !$this->primaryPageBarPrinted;
		$this->primaryPageBarPrinted = true;
		
		$count = 0;
		foreach( $bar as $list ) {
			$count += count($list['links']);
		}
		$useCompactBar = $wgMonacoCompactSpecialPages && $count == 1;
		$deferredList = null;
		
		$divClass = "reset color1 page_bar clearfix";
		
		foreach( $bar as $i => $list ) {
			if ( $useCompactBar && $list["id"] == "page_tabs" && !empty($list["links"]) && isset($list["links"]['nstab-special']) ) {
				$deferredList = $list;
				$deferredList['class'] .= ' compact_page_tabs';
				$divClass .= ' compact_page_bar';
				unset($bar[$i]);
				break;
			}
		}
		
		echo "		";
		echo Html::openElement( 'div', array( "id" => $isPrimary ? "page_bar" : null, "class" => $divClass ) );
		echo "\n";
		if ( !$useCompactBar || !isset($deferredList) ) {
			foreach ( $bar as $list ) {
				$this->printCustomPageBarList( $list );
			}
		}
		echo "		</div>\n";
		if ( isset($deferredList) ) {
			$this->printCustomPageBarList( $deferredList );
		}
	}

	function printCustomPageBarList( $list ) {
		if ( !isset($list["type"]) ) {
			$list["type"] = "buttons";
		}
		$attrs = array(
			"class" => "page_{$list["type"]}",
			"id" => $list["id"],
			"role" => $list["type"] == "tabs" ? "navigation" : "toolbar",
		);
		if ( isset($list["class"]) && $list["class"] ) {
			$attrs["class"] .= " {$list["class"]}";
		}
		
		$this->printCustomPageBarListLinks( $list["links"], $attrs, "			", $list["bad_hook"] );
	}
	
	function printCustomPageBarListLinks( $links, $attrs=array(), $indent='', $hook=null ) {
		echo $indent;
		echo Html::openElement( 'ul', $attrs );
		echo "\n";
		foreach ( $links as $link ) {
			if ( isset($link["links"]) ) {
				$link["class"] = trim("{$link["class"]} hovermenu");
			}
			$liAttrs = array(
				"id" => isset($link["id"]) ? $link["id"] : null,
				"class" => isset($link["class"]) ? $link["class"] : null,
			);
			$aAttrs = array(
				"href" => $link["href"],
			);
			if ( isset($link["id"]) ) {
				$aAttrs += $this->data['skin']->tooltipAndAccesskeyAttribs( $link["id"] );
			}
			echo "$indent	";
			echo Html::openElement( 'li', $liAttrs );
			if ( isset($link["icon"]) ) {
				echo $this->blankimg( array( "class" => "sprite {$link["icon"]}", "alt" => "" ) );
			}
			echo Html::element( 'a', $aAttrs, $link["text"] );
			
			if ( isset($link["links"]) ) {
				echo $this->blankimg();
				$this->printCustomPageBarListLinks( $link["links"], array(), "$indent	" );
			}
			
			echo Xml::closeElement( 'li' );
			echo "\n";
		}
		if ( $hook ) {
			wfRunHooks( $hook );
		}
		echo "$indent</ul>\n";
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printFirstHeading(){
		if ( !$this->data['skin']->isMastheadTitleVisible() ) {
			return;
		}
		?><h1 id="firstHeading" class="firstHeading" aria-level="1"><?php $this->html('title');
		wfRunHooks( 'MonacoPrintFirstHeading' );
		?></h1><?php
	}

	// Made a separate method so recipes, answers, etc can override.
	function printContent(){
		$this->html('bodytext');
	}

	// Made a separate method so recipes, answers, etc can override.
	function printCategories(){
		// Display categories
		if($this->data['catlinks']) {
			$this->html('catlinks');
		}
	}

}
