<?php
/**
 * AniMonaco skin, a Monaco subskin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Daniel Friesen <http://daniel.friesen.name/>
 */
if(!defined('MEDIAWIKI')) {
	die(-1);
}

class SkinAniMonaco extends SkinMonaco {

	var $skinname = 'animonaco', $stylename = 'animonaco',
		$template = 'AniMonacoTemplate';

	function setupSkinUserCss( OutputPage $out ){

		parent::setupSkinUserCss( $out );

		$out->addStyle( 'monaco/style/css/animonaco.css', 'screen' );

	}

}

class AniMonacoTemplate extends MonacoTemplate {

	// @todo Find a clean way to hook into the user links and put a break after My Talk so that we have one line Username + My Talk and the rest on the next line

	function execute() {
		global $wgRequest;
		if ( function_exists('efInfoboxExtract') ) {
			// Extract the infobox if the InfoboxInclude extension is setup
			$action = $wgRequest->getText("action", "view");
			$isView = $action === "view" || $action === "purge";
			if ( $isView ) {
				$html = $this->data['bodytext'];
				$this->mInfobox = efInfoboxExtract( $html );
				$this->data['bodytext'] = $html;
			}
		}
		parent::execute();
	}

	function printBeforePage() {
		global $egAniMonacoLeaderboardCallback;
		if ( !$egAniMonacoLeaderboardCallback ) {
			return;
		}
		ob_start();
		call_user_func( $egAniMonacoLeaderboardCallback, $this );
		$leaderboard = ob_get_contents();
		ob_end_clean();
		if ( !trim($leaderboard) ) {
			return;
		}
?>
		<div id="ad_page" class="page">
<?php echo $leaderboard; ?>
		</div>
<?php
	}

	function setupRightSidebar() {
		if ( $this->mInfobox )
			$this->addToRightSidebar($this->mInfobox);
	}

	function lateRightSidebar() {
		global $egAniMonacoRightSidebarCallback;
		if ( !$egAniMonacoRightSidebarCallback ) {
			return;
		}
		ob_start();
		call_user_func( $egAniMonacoRightSidebarCallback, $this );
		$rsidebar = ob_get_contents();
		ob_end_clean();
		if ( !trim($rsidebar) ) {
			return;
		}
		$this->sidebarBox(null, $rsidebar, array( "wrapcontents" => false, "class" => "ad_box" ));
	}

	function printExtraSidebar() {
		global $wgTitle, $wgSitename, $egTwitterName, $wgRequest, $egAniMonacoSidebarCallback;
		$action = $wgRequest->getText("action", "view");
		$isView = $action === "view" || $action === "purge";
		if ( $isView && $wgTitle->isContentPage() ) {
			$url = $wgTitle->getFullURL();
			$eurl = htmlspecialchars($url);
			if ( $egAniMonacoSidebarCallback ) {
				call_user_func( $egAniMonacoSidebarCallback, $this );
			}
			if ( $wgTitle->exists() ) {
?>
	<script type="text/javascript">
	var reddit_url = "<?php echo Xml::escapeJsString( $url ) ?>";
	var reddit_title = "<?php echo Xml::escapeJsString( "{$wgTitle->getPrefixedText()} - $wgSitename" ); ?>";
	</script>
	<div class="widget sidebox sharebox">
		<table border=0 style="width: 100%;">
		<tr>
			<td></td>
			<td><fb:like href="<?php echo $eurl ?>" layout="box_count" width="50" font="arial"></fb:like></td>
			<td><a href="http://twitter.com/share" class="twitter-share-button" data-url="<?php echo $eurl ?>" data-text="<?php echo htmlspecialchars("{$wgTitle->getPrefixedText()} on the $wgSitename"); ?>" data-count="vertical"<?php if ( $egTwitterName ) echo' data-via="'.htmlspecialchars($egTwitterName).'"'; ?>>Tweet</a></script></td>
			<td></td>
		</tr>
		<tr>
			<td><a class="DiggThisButton DiggMedium"></a></td>
			<td><script type="text/javascript" src="http://reddit.com/static/button/button2.js"></script></td>
			<td><a title="Post to Google Buzz" class="google-buzz-button" href="http://www.google.com/buzz/post" data-button-style="normal-count" data-url="<?php echo $eurl ?>"></a></td>
			<td><script src="http://www.stumbleupon.com/hostedbadge.php?s=5&r=<?php htmlspecialchars(urlencode($url)) ?>"></script></td>
		</tr>
		<tr>
			<td colspan=4>
				<?php $save = "http://www.delicious.com/save?url=".urlencode($url)."&title=".urlencode("{$wgTitle->getPrefixedText()} - $wgSitename"); ?>
				<img src="http://l.yimg.com/hr/img/delicious.small.gif" height="10" width="10" alt="Delicious" />
				<a href="<?php echo htmlspecialchars($save) ?>" onclick="window.open(this.href+'&v=5&noui&jump=close','delicious','toolbar=no,width=550,height=550'); return false;"> Bookmark this on Delicious</a>
			</td>
		</table>
	</div>
	<script type="text/javascript">
		(function() {
			var srcArray = [
				"http://platform.twitter.com/widgets.js",
				"http://widgets.digg.com/buttons.js",
				"http://connect.facebook.net/en_US/all.js#xfbml=1",
				"http://www.google.com/buzz/api/button.js"
			];
			var anchor = document.getElementsByTagName('script')[0];
			while( srcArray.length ) {
				var s = document.createElement('script');
				s.type = "text/javascript";
				s.async = true;
				s.src = srcArray.shift();
				anchor.parentNode.insertBefore(s, anchor);
			}
		})();
	</script>
<?php
			}
		}
	}

}

