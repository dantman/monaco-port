<?php
/**
 * MonacoSidebar class
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

class MonacoSidebar {

	const version = '0.09';

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
						'tabIndex' => 3,
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
			//$menuJSurl = Xml::encodeJsVar("{$wgScript}?action=ajax&v=" . self::version. "&rs=getMenu&id={$menuHash}");
			//$menu .= "<script type=\"text/javascript\">/*<![CDATA[*/wsl.loadScriptAjax({$menuJSurl});/*]]>*/</script>";

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

