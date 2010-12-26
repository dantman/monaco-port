<?php
/**
 * Internationalisation file for skin Monaco.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Daniel Friesen
 */
$messages['en'] = array(
	'monaco-desc' => "Wikia's Monaco theme, ported for use in MediaWiki.",
'dynamic-links' => "
 You can add dynamic link ids to monaco using this message, add a new id on each line
 (you can use a * at the start like in sidebar and monaco-sidebar to make this a list)
 Lines starting with a space will be treated as a comment.
 After adding a new id to the list you can use the 'dynamic-links-<id>' message
 to customize the text of the link and 'dynamic-links-<id>-url' message with the name
 of a page title on the wiki for the dynamic link to link to.

",
	'dynamic-links-write-article' => "Create a new article",
	'dynamic-links-write-article-url' => '-',
	'dynamic-links-add-image' => "Upload a new image",
	'monaco-sidebar' => "
* mainpage|mainpage-description
* randompage-url|randompage
* portal-url|community
** portal-url|portal
** currentevents-url|currentevents",
	'monaco-toolbox' => "
recentchanges-url|recentchanges 
randompage-url|randompage 
specialpages-url|specialpages 
helppage|help",
	'community' => "Community",
	'specialpages-url' => "Special:SpecialPages",
	'monaco-footer-improve' => 'Improve {{SITENAME}} by $1',
	'monaco-footer-improve-linktext' => 'editing this page',
	'monaco-footer-lastedit' => '$1 made an edit on $2',
	'viewrandompage' => "View random page",
);
