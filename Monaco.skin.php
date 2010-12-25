<?php
/**
 * Monaco skin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Inez Korczynski <inez@wikia.com>
 * @author Christian Williams
 */
if(!defined('MEDIAWIKI')) {
	die(-1);
}

define('STAR_RATINGS_WIDTH_MULTIPLIER', 20);

############################## MonacoSidebar ##############################
global $wgHooks;
$wgHooks['MessageCacheReplace'][] = 'MonacoSidebar::invalidateCache';

class MonacoSidebar {

	const version = '0.08';

	static function invalidateCache() {
		global $wgMemc;
		$wgMemc->delete(wfMemcKey('mMonacoSidebar', self::version));
		return true;
	}

	public $editUrl = false;

	/**
	 * Parse one line from MediaWiki message to array with indexes 'text' and 'href'
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	public static function parseItem($line) {

		$href = $specialCanonicalName = false;

		$line_temp = explode('|', trim($line, '* '), 3);
		$line_temp[0] = trim($line_temp[0], '[]');
		if(count($line_temp) >= 2 && $line_temp[1] != '') {
			$line = trim($line_temp[1]);
			$link = trim(wfMsgForContent($line_temp[0]));
		} else {
			$line = trim($line_temp[0]);
			$link = trim($line_temp[0]);
		}


		$descText = null;

		if(count($line_temp) > 2 && $line_temp[2] != '') {
			$desc = $line_temp[2];
			if (wfEmptyMsg($desc, $descText = wfMsg($desc))) {
				$descText = $desc;
			}
		}

		if (wfEmptyMsg($line, $text = wfMsg($line))) {
			$text = $line;
		}

		if($link != null) {
			if (wfEmptyMsg($line_temp[0], $link)) {
				$link = $line_temp[0];
			}
			if (preg_match( '/^(?:' . wfUrlProtocols() . ')/', $link )) {
				$href = $link;
			} else {
				$title = Title::newFromText( $link );
				if($title) {
					if ($title->getNamespace() == NS_SPECIAL) {
						$dbkey = $title->getDBkey();
						$specialCanonicalName = SpecialPage::resolveAlias($dbkey);
						if (!$specialCanonicalName) $specialCanonicalName = $dbkey;
					}
					$title = $title->fixSpecialName();
					$href = $title->getLocalURL();
				} else {
					$href = '#';
				}
			}
		}

		return array('text' => $text, 'href' => $href, 'org' => $line_temp[0], 'desc' => $descText, 'specialCanonicalName' => $specialCanonicalName);
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 * @return array
	 */
	public static function getMessageAsArray($messageKey) {
        $message = trim(wfMsgForContent($messageKey));
        if(!wfEmptyMsg($messageKey, $message)) {
                $lines = explode("\n", $message);
                if(count($lines) > 0) {
                        return $lines;
                }
        }
        return null;
	}

	public function getCode() {
		global $wgUser, $wgTitle, $wgRequest, $wgMemc, $wgLang, $wgContLang;
		if($wgUser->isLoggedIn()) {
			if(empty($wgUser->mMonacoSidebar) || ($wgTitle->getNamespace() == NS_USER && $wgRequest->getText('action') == 'delete')) {
				$wgUser->mMonacoSidebar = $this->getMenu($this->getUserLines(), true);
				if(empty($wgUser->mMonacoSidebar)) {
					$wgUser->mMonacoSidebar = -1;
				}
				$wgUser->saveToCache();
			}
			if($wgUser->mMonacoSidebar != -1) {
				return $wgUser->mMonacoSidebar;
			}
		}

		$cache = $wgLang->getCode() == $wgContLang->getCode();
		if($cache) {
			$key = wfMemcKey('mMonacoSidebar', self::version);
			$menu = $wgMemc->get($key);
		}
		if(empty($menu)) {
			$menu = $this->getMenu($this->getMenuLines());
			if($cache) {
				$wgMemc->set($key, $menu, 60 * 60 * 8);
			}
		}
		return $menu;
	}

	public function getUserLines() {
		global $wgUser,  $wgParser, $wgMessageCache;
		$revision = Revision::newFromTitle(Title::newFromText('User:'.$wgUser->getName().'/Monaco-sidebar'));
		if(is_object($revision)) {
			$text = $revision->getText();
			if(!empty($text)) {
				$ret = explode("\n", $wgParser->transformMsg($text, $wgMessageCache->getParserOptions()));
				return $ret;
			}
		}
		return null;
	}

	public function getMenuLines() {
/*		# if a local copy exists, try to use that first
		$revision = Revision::newFromTitle(Title::newFromText('Monaco-sidebar', NS_MEDIAWIKI));
		if(is_object($revision) && trim($revision->getText()) != '') {
			$lines = MonacoSidebar::getMessageAsArray('Monaco-sidebar');
		}
*/
		# if we STILL have no menu lines, fall back to just loading the default from the message system
		if(empty($lines)) {
			$lines = MonacoSidebar::getMessageAsArray('Monaco-sidebar');
		}

		return $lines;
	}

	public function getSubMenu($nodes, $children) {
		$menu = '';
		foreach($children as $key => $val) {
			$link_html = htmlspecialchars($nodes[$val]['text']);
			if ( !empty( $nodes[$val]['children'] ) ) {
				$link_html .= '<em>&rsaquo;</em>';
			}
			
			$menu_item =
				Html::rawElement( 'a', array(
						'href' => !empty($nodes[$val]['href']) ? $nodes[$val]['href'] : '#',
						'class' => $nodes[$val]['class'],
						'rel' => $nodes[$val]['internal'] ? null : 'nofollow'
					), $link_html ) . "\n";
			if ( !empty( $nodes[$val]['children'] ) ) {
				$menu_item .= $this->getSubMenu( $nodes, $nodes[$val]['children'] );
			}
			$menu .=
				Html::rawElement( 'div', array( "class" => "menu-item" ), $menu_item );
		}
		$menu = Html::rawElement( 'div', array( 'class' => 'sub-menu widget' ), $menu );
		return $menu;
	}

	public function getMenu($lines, $userMenu = false) {
		global $wgMemc, $wgScript;

		$nodes = $this->parse($lines);

		if(count($nodes) > 0) {
			
			wfRunHooks('MonacoSidebarGetMenu', array(&$nodes));
			
			$mainMenu = array();
			foreach($nodes[0]['children'] as $key => $val) {
				if(isset($nodes[$val]['children'])) {
					$mainMenu[$val] = $nodes[$val]['children'];
				}
				if(isset($nodes[$val]['magic'])) {
					$mainMenu[$val] = $nodes[$val]['magic'];
				}
				if(isset($nodes[$val]['href']) && $nodes[$val]['href'] == 'editthispage') $menu .= '<!--b-->';
				$menu .= '<div id="menu-item_'.$val.'" class="menu-item';
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= ' with-sub-menu';
				}
				$menu .= '">';
				$menu .= '<a id="a-menu-item_'.$val.'" href="'.(!empty($nodes[$val]['href']) ? htmlspecialchars($nodes[$val]['href']) : '#').'"';
				if ( !isset($nodes[$val]['internal']) || !$nodes[$val]['internal'] )
					$menu .= ' rel="nofollow"';
				$menu .= ' tabIndex=3>'.htmlspecialchars($nodes[$val]['text']);
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= '<em>&rsaquo;</em>';
				}
				$menu .= '</a>';
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= $this->getSubMenu($nodes, $nodes[$val]['children']);
				}
				$menu .= '</div>';
				if(isset($nodes[$val]['href']) && $nodes[$val]['href'] == 'editthispage') $menu .= '<!--e-->';
			}
			
			$classes = array();
			if ( $userMenu )
				$classes[] = 'userMenu';
			$classes[] = 'hover-navigation';
			$menu = Html::rawElement( 'nav', array( 'id' => 'navigation', 'class' => implode(' ', $classes) ), $menu );

			if($this->editUrl) {
				$menu = str_replace('href="editthispage"', 'href="'.$this->editUrl.'"', $menu);
			} else {
				$menu = preg_replace('/<!--b-->(.*)<!--e-->/U', '', $menu);
			}

			if(isset($nodes[0]['magicWords'])) {
				$magicWords = $nodes[0]['magicWords'];
				$magicWords = array_unique($magicWords);
				sort($magicWords);
			}

			$menuHash = hash('md5', serialize($nodes));

			foreach($nodes as $key => $val) {
				if(!isset($val['depth']) || $val['depth'] == 1) {
					unset($nodes[$key]);
				}
				unset($nodes[$key]['parentIndex']);
				unset($nodes[$key]['depth']);
				unset($nodes[$key]['original']);
			}

			$nodes['mainMenu'] = $mainMenu;
			if(!empty($magicWords)) {
				$nodes['magicWords'] = $magicWords;
			}

			$wgMemc->set($menuHash, $nodes, 60 * 60 * 24 * 3); // three days

			// use AJAX request method to fetch JS code asynchronously
			$menuJSurl = Xml::encodeJsVar("{$wgScript}?action=ajax&v=" . self::version. "&rs=getMenu&id={$menuHash}");
			$menu .= "<script type=\"text/javascript\">/*<![CDATA[*/wsl.loadScriptAjax({$menuJSurl});/*]]>*/</script>";

			return $menu;
		}
	}

	public function parse($lines) {
		$nodes = array();
		$lastDepth = 0;
		$i = 0;
		if(is_array($lines) && count($lines) > 0) {
			foreach($lines as $line) {
				if(trim($line) === '') {
					continue; // ignore empty lines
				}

				$node = $this->parseLine($line);
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

				if($node['original'] == 'editthispage') {
					$node['href'] = 'editthispage';
					if($node['depth'] == 1) {
						$nodes[0]['editthispage'] = true; // we have to know later if there is editthispage special word used in first level
					}
				} else if(!empty( $node['original'] ) && $node['original']{0} == '#') {
					if($this->handleMagicWord($node)) {
						$nodes[0]['magicWords'][] = $node['magic'];
						if($node['depth'] == 1) {
							$nodes[0]['magicWord'] = true; // we have to know later if there is any magic word used if first level
						}
					} else {
						continue;
					}
				}

				$nodes[$i+1] = $node;
				$nodes[$node['parentIndex']]['children'][] = $i+1;
				$lastDepth = $node['depth'];
				$i++;
			}
		}
		return $nodes;
	}

	public function parseLine($line) {
		$lineTmp = explode('|', trim($line, '* '), 2);
		$lineTmp[0] = trim($lineTmp[0], '[]'); // for external links defined as [http://example.com] instead of just http://example.com

		$internal = false;

		if(count($lineTmp) == 2 && $lineTmp[1] != '') {
			$link = trim(wfMsgForContent($lineTmp[0]));
			$line = trim($lineTmp[1]);
		} else {
			$link = trim($lineTmp[0]);
			$line = trim($lineTmp[0]);
		}

		if(wfEmptyMsg($line, $text = wfMsg($line))) {
			$text = $line;
		}

		if(wfEmptyMsg($lineTmp[0], $link)) {
			$link = $lineTmp[0];
		}

		if(preg_match( '/^(?:' . wfUrlProtocols() . ')/', $link )) {
			$href = $link;
		} else {
			if(empty($link)) {
				$href = '#';
			} else if($link{0} == '#') {
				$href = '#';
			} else {
				$title = Title::newFromText($link);
				if(is_object($title)) {
					$href = $title->fixSpecialName()->getLocalURL();
					$internal = true;
				} else {
					$href = '#';
				}
			}
		}

		$ret = array('original' => $lineTmp[0], 'text' => $text);
		$ret['href'] = $href;
		$ret['internal'] = $internal;
		return $ret;
	}

	public function handleMagicWord(&$node) {
		$original_lower = strtolower($node['original']);
		if(in_array($original_lower, array('#voted#', '#popular#', '#visited#', '#newlychanged#', '#topusers#'))) {
			if($node['text']{0} == '#') {
				$node['text'] = wfMsg(trim($node['original'], ' *')); // TODO: That doesn't make sense to me
			}
			$node['magic'] = trim($original_lower, '#');
			return true;
		} else if(substr($original_lower, 1, 8) == 'category') {
			$param = trim(substr($node['original'], 9), '#');
			if(is_numeric($param)) {
				$category = $this->getBiggestCategory($param);
				$name = $category['name'];
			} else {
				$name = substr($param, 1);
			}
			if($name) {
				$node['href'] = Title::makeTitle(NS_CATEGORY, $name)->getLocalURL();
				if($node['text']{0} == '#') {
					$node['text'] = str_replace('_', ' ', $name);
				}
				$node['magic'] = 'category'.$name;
				return true;
			}
		}
		return false;
	}
/*
	private $biggestCategories;

	public function getBiggestCategory($index) {
		global $wgMemc, $wgBiggestCategoriesBlacklist;
		$limit = max($index, 15);
		if($limit > count($this->biggestCategories)) {
			$key = wfMemcKey('biggest', $limit);
			$data = $wgMemc->get($key);
			if(empty($data)) {
				$filterWordsA = array();
				foreach($wgBiggestCategoriesBlacklist as $word) {
					$filterWordsA[] = '(cl_to not like "%'.$word.'%")';
				}
				$dbr =& wfGetDB( DB_SLAVE );
				$tables = array("categorylinks");
				$fields = array("cl_to, COUNT(*) AS cnt");
				$where = count($filterWordsA) > 0 ? array(implode(' AND ', $filterWordsA)) : array();
				$options = array("ORDER BY" => "cnt DESC", "GROUP BY" => "cl_to", "LIMIT" => $limit);
				$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);
				$categories = array();
				while ($row = $dbr->fetchObject($res)) {
					$this->biggestCategories[] = array('name' => $row->cl_to, 'count' => $row->cnt);
				}
				$wgMemc->set($key, $this->biggestCategories, 60 * 60 * 24 * 7);
			} else {
				$this->biggestCategories = $data;
			}
		}
		return isset($this->biggestCategories[$index-1]) ? $this->biggestCategories[$index-1] : null;
	}
*/
}
############################## MonacoSidebar ##############################

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
	public function initPage(&$out) {

		wfDebugLog('monaco', '##### SkinMonaco initPage #####');

		wfProfileIn(__METHOD__);
		global $wgHooks;

		SkinTemplate::initPage($out);
/*
		$this->skinname  = 'monaco';
		$this->stylename = 'monaco';
		$this->template  = 'MonacoTemplate';
*/

		// Function addVariables will be called to populate all needed data to render skin
		$wgHooks['SkinTemplateOutputPageBeforeExec'][] = array(&$this, 'addVariables');

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
		global $wgMonacoTheme;

		parent::setupSkinUserCss( $out );
		
		//$out->addStyle( 'common/shared.css' );
		$out->addStyle( 'monaco/style/css/monobook_modified.css', 'screen' );
		$out->addStyle( 'monaco/style/css/reset_modified.css', 'screen' );
		// @note Original monaco included extra wikia_ui/buttons.css here which Wikia dropped into skins/common
		$out->addStyle( 'monaco/style/css/sprite.css', 'screen' );
		$out->addStyle( 'monaco/style/css/root.css', 'screen' );
		$out->addStyle( 'monaco/style/css/header.css', 'screen' );
		$out->addStyle( 'monaco/style/css/article.css', 'screen' );
		$out->addStyle( 'monaco/style/css/widgets.css', 'screen' ); // ?
		$out->addStyle( 'monaco/style/css/modal.css', 'screen' ); // ?
		$out->addStyle( 'monaco/style/css/footer.css', 'screen' );
		$out->addStyle( 'monaco/style/css/star_rating.css', 'screen' );
		$out->addStyle( 'monaco/style/css/ny.css', 'screen' );
		
		$out->addStyle( 'monaco/style/css/monaco_ltie7.css', 'screen', 'lt IE 7' );
		$out->addStyle( 'monaco/style/css/monaco_ie7.css', 'screen', 'IE 7' );
		$out->addStyle( 'monaco/style/css/monaco_ie8.css', 'screen', 'IE 8' );
		
		if ( isset($wgMonacoTheme) && is_string($wgMonacoTheme) && $wgMonacoTheme != "sapphire" )
			$out->addStyle( "monaco/style/{$wgMonacoTheme}/css/main.css", 'screen' );
		
		$out->addStyle( 'monaco/style/rtl.css', 'screen', '', 'rtl' );
		
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
		wfProfileOut(__METHOD__ . '::printCSS');

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
		}/* else if(strpos(strtolower($node['org']), '#category') === 0) {
			$param = trim(substr($node['org'], 9), '#');
			if(is_numeric($param)) {
				$cat = $this->getBiggestCategory($param);
				$name = $cat['name'];
			} else {
				$name = substr($param, 1);
			}
			$articles = $this->getMostVisitedArticlesForCategory($name);
			if(count($articles) == 0) {
                                $node ['magic'] = true ;
                                $node['href'] = Title::makeTitle(NS_CATEGORY, $name)->getLocalURL();
                                $node ['text'] = $name ;
                                $node['text'] = str_replace('_', ' ', $node['text']);
			} else {
				$node['magic'] = true;
				$node['href'] = Title::makeTitle(NS_CATEGORY, $name)->getLocalURL();
				if(strpos($node['text'], '#') === 0) {
					$node['text'] = str_replace('_', ' ', $name);
				}
				foreach($articles as $key => $val) {
					$title = Title::newFromId($val);
					if(is_object($title)) {
						$node['children'][] = $this->lastExtraIndex;
						$nodes[$this->lastExtraIndex]['text'] = $title->getText();
						$nodes[$this->lastExtraIndex]['href'] = $title->getLocalUrl();
						$this->lastExtraIndex++;
					}
				}
				$node['children'][] = $this->lastExtraIndex;
				$nodes[$this->lastExtraIndex]['text'] = strtolower(wfMsg('moredotdotdot'));
				$nodes[$this->lastExtraIndex]['href'] = $node['href'];
				$nodes[$this->lastExtraIndex]['class'] = 'Monaco-sidebar_more';
				$this->lastExtraIndex++;
			}
		}*/

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
/*
	var $biggestCategories = array();

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 * @author Piotr Molski
	 * @return array
	 *//*
	private function getBiggestCategory($index) {
		wfProfileIn( __METHOD__ );
		global $wgMemc, $wgBiggestCategoriesBlacklist;

		$limit = max($index, 15);

		if($limit > count($this->biggestCategories)) {
			$key = wfMemcKey('biggest', $limit);
			$data = $wgMemc->get($key);
			if(empty($data)) {
				$filterWordsA = array();
				foreach($wgBiggestCategoriesBlacklist as $word) {
					$filterWordsA[] = '(cl_to not like "%'.$word.'%")';
				}
				$dbr =& wfGetDB( DB_SLAVE );
				$tables = array("categorylinks");
				$fields = array("cl_to, COUNT(*) AS cnt");
				$where = count($filterWordsA) > 0 ? array(implode(' AND ', $filterWordsA)) : array();
				$options = array(
					"ORDER BY" => "cnt DESC",
					"GROUP BY" => "cl_to",
					"LIMIT" => $limit);
				$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);
				$categories = array();
				while ($row = $dbr->fetchObject($res)) {
	       			$this->biggestCategories[] = array('name' => $row->cl_to, 'count' => $row->cnt);
				}
				$wgMemc->set($key, $this->biggestCategories, 60 * 60 * 24 * 7);
			} else {
				$this->biggestCategories = $data;
			}
		}
		wfProfileOut( __METHOD__ );
		return isset($this->biggestCategories[$index-1]) ? $this->biggestCategories[$index-1] : null;
	}*/

	/**
	 * @author Piotr Molski
	 * @author Inez Korczynski <inez@wikia.com>
	 * @return array
	 *//*
	private function getMostVisitedArticlesForCategory($name, $limit = 7) {
		wfProfileIn(__METHOD__);

		global $wgMemc;
		$key = wfMemcKey('popular-art');
		$data = $wgMemc->get($key);

		if(!empty($data) && isset($data[$name])) {
			wfProfileOut(__METHOD__);
			return $data[$name];
		}

		$name = str_replace(" ", "_", $name);

		$dbr =& wfGetDB( DB_SLAVE );
		$query = "SELECT cl_from FROM categorylinks USE INDEX (cl_from), page_visited USE INDEX (page_visited_cnt_inx) WHERE article_id = cl_from AND cl_to = '".addslashes($name)."' ORDER BY COUNT DESC LIMIT $limit";
		$res = $dbr->query($query);
		$result = array();
		while($row = $dbr->fetchObject($res)) {
			$result[] = $row->cl_from;
		}
		if(count($result) < $limit) {
			$query = "SELECT cl_from FROM categorylinks WHERE cl_to = '".addslashes($name)."' ".(count($result) > 0 ? " AND cl_from NOT IN (".implode(',', $result).") " : "")." LIMIT ".($limit - count($result));
			$res = $dbr->query($query);
			while($row = $dbr->fetchObject($res)) {
				$result[] = $row->cl_from;
			}
		}

		if(empty($data) || !is_array($data)) {
			$data = array($data);
		}
		$data[$name] = $result;
		$wgMemc->set($key, $data, 60 * 60 * 3);
		wfProfileOut( __METHOD__ );
		return $result;
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

		// rarely ever happens, but it does
		if ( empty( $tpl->data['content_actions'] ) ) {
			return $links;
		}

		# @todo: might actually be useful to move this to a global var and handle this in extension files --TOR
		$force_right = array( 'userprofile', 'talk', 'TheoryTab' );
		foreach($tpl->data['content_actions'] as $key => $val) {
			/* Fix icons */
			if($key == 'unprotect') {
				//unprotect uses the same icon as protect
				$val['icon'] = 'protect';
			} else if ($key == 'undelete') {
				//undelete uses the same icon as delelte
				$val['icon'] = 'delete';
			} else if ($key == 'purge') {
				$val['icon'] = 'refresh';
			} else if ($key == 'addsection') {
				$val['icon'] = 'talk';
			}

			if($key == 'report-problem') {
				// Do nothing
			} else if( strpos($key, 'nstab-') === 0 || in_array($key, $force_right) ) {
				$links['right'][$key] = $val;
			} else {
				$links['left'][$key] = $val;
			}
		}
		wfProfileOut( __METHOD__ );
		return $links;
	}

	/**
	 * This is helper function for getNavigationMenu and it's responsible for support special tags like #TOPVOTED
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function addExtraItemsToNavigationMenu(&$node, &$nodes) {
		wfProfileIn( __METHOD__ );
/*
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
			$results[] = array('url' => Title::makeTitle(NS_SPECIAL, 'Top/'.$extraWords[strtolower($node['org'])][0])->getLocalURL(), 'text' => strtolower(wfMsg('moredotdotdot')), 'class' => 'Monaco-sidebar_more');
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
		} else if(strpos(strtolower($node['org']), '#category') === 0) {
			$param = trim(substr($node['org'], 9), '#');
			if(is_numeric($param)) {
				$cat = $this->getBiggestCategory($param);
				$name = $cat['name'];
			} else {
				$name = substr($param, 1);
			}
			$articles = $this->getMostVisitedArticlesForCategory($name);
			if(count($articles) == 0) {
				$node = null;
			} else {
				$node['magic'] = true;
				$node['href'] = Title::makeTitle(NS_CATEGORY, $name)->getLocalURL();
				if(strpos($node['text'], '#') === 0) {
					$node['text'] = str_replace('_', ' ', $name);
				}
				foreach($articles as $key => $val) {
					$title = Title::newFromId($val);
					if(is_object($title)) {
						$node['children'][] = $this->lastExtraIndex;
						$nodes[$this->lastExtraIndex]['text'] = $title->getText();
						$nodes[$this->lastExtraIndex]['href'] = $title->getLocalUrl();
						$this->lastExtraIndex++;
					}
				}
				$node['children'][] = $this->lastExtraIndex;
				$nodes[$this->lastExtraIndex]['text'] = strtolower(wfMsg('moredotdotdot'));
				$nodes[$this->lastExtraIndex]['href'] = $node['href'];
				$nodes[$this->lastExtraIndex]['class'] = 'yuimenuitemmore';
				$this->lastExtraIndex++;
			}
		}
*/
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Generate links for user menu - depends on if user is logged in or not
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getUserLinks($tpl) {
		wfProfileIn( __METHOD__ );
		global $wgUser, $wgTitle;

		$data = array();

		if(!$wgUser->isLoggedIn()) {
			$returnto = "returnto={$this->thisurl}";
			if( $this->thisquery != '' )
				$returnto .= "&returntoquery={$this->thisquery}";

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

	private function printMenu($id, $last_count='', $level=0) {
		global /*$wgUploadPath, */$wgArticlePath;
		$menu_output = "";
		$script_output = "";
		$count = 1;

		$fixed_art_path = str_replace ('$1', "", $wgArticlePath);

		$output = '';
		if(isset($this->navmenu[$id]['children'])) {
			$script_output .= '<script type="text/javascript">/*<![CDATA[*/';
			if ($level) {
				$menu_output .= '<div class="sub-menu widget" id="sub-menu' . $last_count . '" style="display:none" >';
				$script_output .= 'submenu_array["sub-menu' . $last_count . '"] = "' . $last_count . '";';
				$script_output .= '$("navigation_widget").onmouseout = clearMenu;';
				$script_output .= '$("sub-menu' . $last_count . '").onmouseout = clearMenu;if($("sub-menu' . $last_count . '").captureEvents) $("sub-menu' . $last_count .'").captureEvents(Event.MOUSEOUT);';
			}
			$extraAttributes = ' rel="nofollow"';
			foreach($this->navmenu[$id]['children'] as $child) {
				//$mouseover = ' onmouseover="' . ($level ? 'sub_' : '') . 'menuItemAction(\'' .
				($level ? $last_count . '_' : '_') .$count . '\');"';
				//$mouseout = ' onmouseout="clearBackground(\'_' . $count . '\')"';
				$menu_output .='<div class="menu-item" id="' . ($level ? 'sub-' : '') . 'menu-item' . ($level ? $last_count . '_' :'_') .$count . '">';
				$menu_output .= '<a id="' . ($level ? 'a-sub-' : 'a-') . 'menu-item' . ($level ? $last_count . '_' : '_') .$count . '" href="'.(!empty($this->navmenu[$child]['href']) ? htmlspecialchars($this->navmenu[$child]['href']) : '#').'" class="'.(!empty($this->navmenu[$child]['class']) ? htmlspecialchars($this->navmenu[$child]['class']) : '').'"' . $extraAttributes . '>';

				if (($fixed_art_path) == $this->navmenu[$child]['href']) {
					$prevent_blank = '.onclick = YAHOO.util.Event.preventDefault ; ' ;
				} else {
					$prevent_blank = '' ;
				}

				if(!$level) {
					$script_output .= 'menuitem_array["menu-item' . $last_count . '_' .$count .'"]= "' . $last_count . '_' .$count . '";';
/*
					$script_output .= '$("menu-item' . $last_count . '_' .$count .'").onmouseover = menuItemAction;if ($("menu-item' . $last_count . '_' .$count.'").captureEvents) $("menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOVER);';
					$script_output .= '$("menu-item' . $last_count . '_' .$count .'").onmouseout = clearBackground;if ($("menu-item' . $last_count . '_' .$count.'").captureEvents) $("menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOUT);';
*/
					$script_output .= '$("a-menu-item' . $last_count . '_' .$count .'").onmouseover = menuItemAction;if ($("a-menu-item' . $last_count . '_' .$count.'").captureEvents) $("a-menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOVER);';

					$script_output .= '$("a-menu-item' . $last_count . '_' .$count .'").onmouseout = clearBackground;if ($("a-menu-item' . $last_count . '_' .$count.'").captureEvents) $("a-menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOUT);';

				}
				else {
					$script_output .= 'submenuitem_array["sub-menu-item' . $last_count . '_'.$count .'"] = "' . $last_count . '_' .$count . '";';
/*
					$script_output .= '$("sub-menu-item' . $last_count . '_' .$count.'").onmouseover = sub_menuItemAction;if ($("sub-menu-item' . $last_count . '_'.$count .'").captureEvents) $("sub-menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOVER);';
*/
					$script_output .= '$("a-sub-menu-item' . $last_count . '_' .$count.'").onmouseover = sub_menuItemAction;if ($("a-sub-menu-item' . $last_count . '_'.$count .'").captureEvents) $("a-sub-menu-item' . $last_count . '_' .$count.'").captureEvents(Event.MOUSEOVER);';
					if ('' != $prevent_blank) {
						$script_output .= '$("a-sub-menu-item' . $last_count . '_' .$count.'")' . $prevent_blank ;
					}
				}
				$menu_output .= htmlspecialchars($this->navmenu[$child]['text']);
				if ( !empty( $this->navmenu[$child]['children'] ) ) {
					//$menu_output .= '<img src="' . $wgUploadPath . '/common/new/right_arrow.gif?1"
					$menu_output .= '<em>&rsaquo;</em>';
				}
				$menu_output .= '</a>';
				$menu_output .= $this->printMenu($child, $last_count . '_' . $count, $level+1);
				$menu_output .= '</div>';
				$count++;
			}
			if ($level) {
				$menu_output .= '</div>';
			}
			$script_output .= '/*]]>*/</script>';
		}

		if ($menu_output.$script_output!="") {
			$output .= "<div id=\"navigation{$last_count}\">";
			$output .= $menu_output . $script_output;
			$output .= "</div>";
		}
		return $output;
	}

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

		/*$skin = $wgUser->getSkin();
		$namespace = $wgTitle->getNamespace();*/
		$skin = $this->data['skin'];
		$action = $wgRequest->getText('action');
		$namespace = $wgTitle->getNamespace();

		$this->set( 'blankimg', $this->data['stylepath'].'/monaco/style/images/blank.gif' );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
		
		$this->setupRightSidebar();
		
		$this->html( 'headelement' );

?>
<?php
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

	<!-- HEADER -->
<?php

// curse like cobranding
$this->printCustomHeader();


wfProfileIn( __METHOD__ . '-header'); ?>
		<div id="wikia_header" class="reset color2">
			<div class="monaco_shrinkwrap">
			<div id="monacoBranding">
				<?php wfRunHooks( 'MonacoBranding', array( $this ) ) ?>
		<?php
$categorylist = $this->data['data']['categorylist'];
if(isset($categorylist['nodes']) && count($categorylist['nodes']) > 0 ) {
?>
				<button id="headerButtonHub" class="header-button color1"><?php echo isset($categorylist['cat']['text']) ? $categorylist['cat']['text'] : '' ?><img src="<?php $this->text('blankimg') ?>" /></button>

<?php
}
?>
			</div>
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
	?>
				<span id="header_username"><a href="<?php echo htmlspecialchars($this->data['userlinks']['userpage']['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-userpage') ?>><?php echo htmlspecialchars($this->data['userlinks']['userpage']['text']) ?></a></span>
				<span id="header_mytalk"><a href="<?php echo htmlspecialchars($this->data['userlinks']['mytalk']['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-mytalk') ?>><?php echo htmlspecialchars($this->data['userlinks']['mytalk']['text']) ?></a></span>
				<span id="header_watchlist"><a href="<?php echo htmlspecialchars($this->data['userlinks']['watchlist']['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-watchlist') ?>><?php echo htmlspecialchars($this->data['userlinks']['watchlist']['text']) ?></a></span>
<?php
			if ( $this->useUserMore() ) { ?>
				<span class="more hovermenu">
					<button id="headerButtonUser" class="header-button color1"><?php echo trim(wfMsgHtml('moredotdotdot'), ' .') ?><img src="<?php $this->text('blankimg') ?>" /></button>
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
					<a href="<?php echo htmlspecialchars($this->data['userlinks']['logout']['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-logout') ?>><?php echo htmlspecialchars($this->data['userlinks']['logout']['text']) ?></a>
				</span>
	<?php
	} else {
?>
				<span id="userLogin">
					<a id="login" href="<?php echo htmlspecialchars($this->data['userlinks']['login']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['login']['text']) ?></a>
				</span>

					<a class="wikia-button" id="register" href="<?php echo htmlspecialchars($this->data['userlinks']['register']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['register']['text']) ?></a>

<?php
	}
}
?>
		</div>
		</div>
	</div>

	<div class="monaco_shrinkwrap"><div id="background_accent1"></div></div>
	<div style="position: relative;"><div id="background_accent2"></div></div>

<?php if (wfRunHooks('AlternateNavLinks')):

		// Rewrite the logo to have the last modified timestamp so that a the newer one will be used after an update.
		// $wgLogo =
		?>
		<div id="background_strip" class="reset">
			<div class="monaco_shrinkwrap">

			<div id="accent_graphic1"></div>
			<div id="accent_graphic2"></div>
			<div id="wiki_logo" style="background-image: url(<?php $this->html( 'logopath' ) ?>);"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>" accesskey="z" rel="home"><?php echo $wgSitename ?></a></div>
			<!--[if lt IE 7]>
			<style type="text/css">
				#wiki_logo {
					background-image: none !important;
					filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?php echo $wgLogo ?>', sizingMethod='image');
				}
			</style>
			<![endif]-->
			</div>
		</div>
<?php endif; ?>
		<!-- /HEADER -->
<?php		wfProfileOut( __METHOD__ . '-header'); ?>

		<!-- PAGE -->
<?php		wfProfileIn( __METHOD__ . '-page'); ?>

	<div class="monaco_shrinkwrap" id="monaco_shrinkwrap_main">
<?php wfRunHooks('MonacoBeforePage', array($this)); ?>
<?php $this->printBeforePage(); ?>
		<div id="wikia_page" class="page">
<?php
wfRunHooks('MonacoBeforePageBar', array($this));
			$this->printPageBar(); ?>
					<!-- ARTICLE -->

<?php		wfProfileIn( __METHOD__ . '-article'); ?>
			<article id="article" aria-role=main aria-labeledby="firstHeading">
				<a name="top" id="top"></a>
				<?php wfRunHooks('MonacoAfterArticle', array($this)); // recipes: not needed? ?>
				<?php if ( $this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
				<?php $this->printFirstHeading(); ?>
				<div id="bodyContent">
					<h2 id="siteSub"><?php $this->msg('tagline') ?></h2>
					<div id="contentSub"><?php $this->html('subtitle') ?></div>
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
								<li id="fe_history"><a id="fe_history_icon" href="' . htmlspecialchars($this->data['content_actions']['history']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_history_img" class="sprite history" alt="' . wfMsgHtml('history_short') . '" /></a> <div><a id="fe_history_link" href="' . htmlspecialchars($this->data['content_actions']['history']['href']) . '">' . $this->data['content_actions']['history']['text'] . '</a></div></li>') : '') .

								(!empty($nav_urls['recentchangeslinked']) ? ('
								<li id="fe_recent"><a id="fe_recent_icon" href="' . htmlspecialchars($nav_urls['recentchangeslinked']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_recent_img" class="sprite recent" alt="' . wfMsgHtml('recentchangeslinked') . '" /></a> <div><a id="fe_recent_link" href="' . htmlspecialchars($nav_urls['recentchangeslinked']['href']) . '">' . wfMsgHtml('recentchangeslinked') . '</a></div></li>') : '');

		}
		if (!empty($nav_urls['permalink']) || !empty($nav_urls['whatlinkshere'])) {
			$actions .=
								'<ul id="articleFooterActions4" class="actions clearfix">' .

								(!empty($nav_urls['permalink']) ? ('
								<li id="fe_permalink"><a id="fe_permalink_icon" href="' . htmlspecialchars($nav_urls['permalink']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_permalink_img" class="sprite move" alt="' . wfMsgHtml('permalink') . '" /></a> <div><a id="fe_permalink_link" href="' . htmlspecialchars($nav_urls['permalink']['href']) . '">' . $nav_urls['permalink']['text'] . '</a></div></li>') : '') .

								((!empty($nav_urls['whatlinkshere'])) ? ('
								<li id="fe_whatlinkshere"><a id="fe_whatlinkshere_icon" href="' . htmlspecialchars($nav_urls['whatlinkshere']['href']) . '"><img src="'.htmlspecialchars($this->data['blankimg']).'" id="fe_whatlinkshere_img" class="sprite pagelink" alt="' . wfMsgHtml('whatlinkshere') . '" /></a> <div><a id="fe_whatlinkshere_link" href="' . htmlspecialchars($nav_urls['whatlinkshere']['href']) . '">' . wfMsgHtml('whatlinkshere') . '</a></div></li>') : '') . '</ul>';



		}

		global $wgArticle, $wgLang;
?>
			<div id="articleFooter" class="reset">
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
?>
								<li><a id="fe_edit_icon" href="<?php echo htmlspecialchars($wgTitle->getEditURL()) ?>"><img src="<?php $this->text('blankimg') ?>" id="fe_edit_img" class="sprite edit" alt="<?php echo wfMsgHtml('edit') ?>" /></a> <div><?php echo wfMsgHtml('monaco-footer-improve', '<a id="fe_edit_link" href="'.htmlspecialchars($wgTitle->getEditURL()).'">'.wfMsgHtml('monaco-footer-improve-linktext').'</a>'); ?></div></li>
<?php
		}

		if(is_object($wgArticle)) {
			$timestamp = $wgArticle->getTimestamp();
			$lastUpdate = $wgLang->date($timestamp);
			$userId = $wgArticle->getUser();
			if($userId > 0) {
				$user = User::newFromName($wgArticle->getUserText());
				$userPageTitle = $user->getUserPage();
				$userPageLink = $userPageTitle->getLocalUrl();
				$userPageExists = $userPageTitle->exists();
				$feUserIcon = Html::element( 'img', array( "src" => $this->data['blankimg'], "id" => "fe_user_img", "class" => "sprite user", "alt" => wfMsg('userpage') ) );
				if ( $userPageExists )
					$feUserIcon = Html::rawElement( 'a', array( "id" => "fe_user_icon", "href" => $userPageLink ), $feUserIcon );
?>
								<li><?php echo $feUserIcon ?> <div><?php echo wfMsgHtml('monaco-footer-lastedit', $skin->link( $userPageTitle, htmlspecialchars($user->getName()), array( "id" => "fe_user_link" ) ), $lastUpdate) ?></div></li>
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
								<li><a id="fe_random_icon" href="<?php echo Skin::makeSpecialUrl( 'Randompage' ) ?>"><img src="<?php $this->text('blankimg') ?>" id="fe_random_img" class="sprite random" alt="<?php echo wfMsgHtml('randompage') ?>" /></a> <div><a id="fe_random_link" href="<?php echo Skin::makeSpecialUrl( 'Randompage' ) ?>"><?php echo wfMsgHtml('viewrandompage') ?></a></div></li>

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
		$this->html('WikiaScriptLoader');
		$this->html('JSloader');
		$this->html('headscripts');
	}
	echo '<script type="text/javascript">/*<![CDATA[*/for(var i=0;i<wgAfterContentAndJS.length;i++){wgAfterContentAndJS[i]();}/*]]>*/</script>' . "\n";

?>
<?php $this->printRightSidebar() ?>
		<!-- WIDGETS -->
<?php		wfProfileIn( __METHOD__ . '-navigation'); ?>
		<div id="widget_sidebar" class="reset widget_sidebar">

			<!-- SEARCH/NAVIGATION -->
			<div class="widget" id="navigation_widget" aria-role=navigation>
<?php
	global $wgSitename;
	$msgSearchLabel = wfMsgHtml('Tooltip-search');
	$searchLabel = wfEmptyMsg('Tooltip-search', $msgSearchLabel) ? (wfMsgHtml('ilsubmit').' '.$wgSitename.'...') : $msgSearchLabel;
?>
			<div id="search_box" class="color1" aria-role="search">
				<form action="<?php $this->text('searchaction') ?>" id="searchform">
					<label style="display: none;" for="search_field"><?php echo htmlspecialchars($searchLabel) ?></label>
					<input id="search_field" name="search" type="text" maxlength="200" onfocus="sf_focus(event);" alt="<?php echo htmlspecialchars($searchLabel) ?>" aria-label="<?php echo htmlspecialchars($searchLabel) ?>" placeholder="<?php echo htmlspecialchars($searchLabel) ?>" autocomplete="off"<?php echo $skin->tooltipAndAccesskey('search'); ?> tabIndex=2 aria-required=true aria-flowto="search-button" />
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

		global $wgMonacoDynamicCreateOverride;
		$createPage = null;
		if( !empty($wgMonacoDynamicCreateOverride) ) {
			$createPage = Title::newFromText($wgMonacoDynamicCreateOverride);
		}
		if ( !isset($createPage) ) {
			if ( SpecialPage::exists('CreatePage') ) {
				$createPage = SpecialPage::getTitleFor('CreatePage');
			}
		}
		if ( isset($createPage) && ( $wgUser->isAllowed('edit') || $wgUser->isAnon() ) ) {
			/* Redirect to login page instead of showing error, see Login friction project */
			$dynamicLinksArray[] = array(
				'url' => $wgUser->isAnon() ? SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$createPage->getPrefixedDBkey())) : $createPage->getLocalURL(),
				'text' => wfMsg('dynamic-links-write-article'),
				'id' => 'dynamic-links-write-article',
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
			$dynamicLinksArray[] = array(
				'url' => $url,
				'text' => wfMsg('dynamic-links-add-image'),
				'id' => 'dynamic-links-add-image',
				'icon' => 'photo',
			);
		}
	}

	if (count($dynamicLinksArray) > 0) {
?>
		<tbody id="link_box_dynamic">
			<tr>
				<td colspan="2">
					<ul>
<?php
			foreach ($dynamicLinksArray as $link) {
				//print_r($link);
				echo '<li id="' . $link['id']  .'-row" class="link_box_dynamic_item"><a id="' . $link['id'] . '-icon" href="' . htmlspecialchars($link['url']) . '" tabIndex=-1><img src="'.htmlspecialchars($this->data['blankimg']).'" id="' . $link['id'] . '-img" class="sprite '. $link['icon'] .'" alt="' . htmlspecialchars($link['text']) . '" /></a> <a id="' . $link['id'] . '-link" href="' . htmlspecialchars($link['url']) . '" tabIndex=3>'. htmlspecialchars($link['text']) .'</a></li>';
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
			$cssMediaWiki = $this->data['csslinksbottom-urls'];
			//$cssStaticChute = $this->data['mergedCSSprint'];

			$cssReferences = array_keys($cssMediaWiki);

			// detect whether to use merged JS/CSS files
			echo <<<EOF
		<script type="text/javascript">/*<![CDATA[*/
			(function(){
				var cssReferences = $cssReferences;
				var len = cssReferences.length;
				for(var i=0; i<len; i++)
					setTimeout("wsl.loadCSS.call(wsl, '" + cssReferences[i] + "', 'print')", 100);
			})();
		/*]]>*/</script>
EOF;
		}
	}

	// allow subskins to add extra sidebar extras
	function printExtraSidebar() {}
	
	// hook for subskins
	function setupRightSidebar() {}
	
	function addToRightSidebar($html) {
		$this->mRightSidebar .= $html;
	}
	
	function printRightSidebar() {
		if ( $this->mRightSidebar ) {
?>
		<!-- RIGHT SIDEBAR -->
		<div id="right_sidebar">
			<?php echo $this->mRightSidebar ?>
		</div>
		<!-- /RIGHT SIDEBAR -->
<?php
		}
	}
	
	// allow subskins to add pre-page islands
	function printBeforePage() {}

	// curse like cobranding
	function printCustomHeader() {}
	function printCustomFooter() {}

	// Made a separate method so recipes, answers, etc can override. This is for any additional CSS, Javacript, etc HTML
	// that appears within the HEAD tag
	function printAdditionalHead(){}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printPageBar(){
		// Allow for other skins to conditionally include it
		$this->realPrintPageBar();
	}
	function realPrintPageBar(){
		global $wgUser, $wgTitle;
                $skin = $wgUser->getSkin();
	 	?>
		<div id="page_bar" class="reset color1 clearfix">
				<ul id="page_controls" role="toolbar">
		  <?php
			if(isset($this->data['articlelinks']['left'])) {
				foreach($this->data['articlelinks']['left'] as $key => $val) {
		  ?>
							  <li id="control_<?php echo $key ?>" class="<?php echo $val['class'] ?>"><img src="<?php $this->text('blankimg') ?>" class="sprite <?php echo (isset($val['icon'])) ? $val['icon'] : $key ?>" alt="" /><a id="ca-<?php echo $key ?>" href="<?php echo htmlspecialchars($val['href']) ?>" <?php echo $skin->tooltipAndAccesskey('ca-'.$key) ?>><?php echo htmlspecialchars(ucfirst($val['text'])) ?></a></li>
		  <?php
				}
				wfRunHooks( 'MonacoAfterArticleLinks' );
			}
		  ?>
						  </ul>
						  <ul id="page_tabs" role="navigation">
		  <?php
		  $showright = true;
		  $namespace = $wgTitle->getNamespace();
		  global $wgMastheadVisible;
		  if (!empty($wgMastheadVisible)) {
			  $showright = false;
		  }
		  if(isset($this->data['articlelinks']['right']) && $showright ) {
			  foreach($this->data['articlelinks']['right'] as $key => $val) {
		  ?>
							  <li class="<?php echo $val['class'] ?>"><a href="<?php echo htmlspecialchars($val['href']) ?>" id="ca-<?php echo $key ?>" <?php echo $skin->tooltipAndAccesskey('ca-'.$key) ?> class="<?php echo $val['class'] ?>"><?php echo htmlspecialchars(ucfirst($val['text'])) ?></a></li>
		  <?php
			  }
		  }
		  ?>
				</ul>
			</div>
	<?php
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printFirstHeading(){
		?><h1 id="firstHeading" class="firstHeading" aria-level="1"><?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title');
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
