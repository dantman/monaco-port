# Intro
The other files just don't look right, so I'm merging the old docs from the other two before me (Arcane and James Haley). Don't be offended, just need to concatenate all extra files into each one. If you wanna know the history of this port and what's changed since I got here, go right ahead! No one's gonna bash you for it.

## Arcane
Arcane's Modified Monaco version 1.3

I'm Arcane (known as Arcane 21 on Wikimedia project websites), and I have forked a modified version of the Monaco skin by Daniel Friesen (i.e. - Dantman) and James Haley for use by anyone who likes the Monaco skin but find it gives them display errors with certain extensions or simply does not scale certain buttons, bars, or other interface elements properly, especially on recent versions of MediaWiki (1.19+)

To that end, I have done the following:

1. I have included edits towards the end of the monobook_modified.css file that fix some alignment errors when using the WikiEditor extension.The WikiEditor fixes are a patch for any alignment errors that may be  present with the original Monaco port, with the exception of fixes for non-WikiEditor specific display anomalies.

2. I have commented out the paypal contribute button feature since it cannot be easily modified in the monaco.skin.php file, but have left instructions at that section how to enabled it and retool it to work for any site with a Paypal account.

Note: There is a version of this skin modified to work for the Orain wikifarm that has this enabled, set by default for their Paypal account.

4. This skin is installed in the exact same way as Dantman's port, and I have included all of the original documentation Daniel Friesen and James Haley included in this port (see below), so please refer to that for installation and configuration. The skin was ported from Wikia by Daniel Friesen, both of which had released the skin and its related coding as open source, further modified by James Haley, and I hereby release this modification of their work under the same conditions and have included all the original documentation as an acknowledgement of their contributions, and would like to thank these parties for making this project possible.

== Problems / Troubleshooting ==

If there are any bugs, problems, or you simply wish to offer suggestions or comments regarding this extension, I may be contacted by email or at the following locations:

https://www.mediawiki.org/wiki/User:Arcane21

Email: arcane@live.com

--------

To install, install monaco-port into a monaco/ folder in your skins/ folder. From the command line you can do this by cd'ing to your skins/ folder inside your MediaWiki installation and running `git clone git://github.com/dantman/monaco-port.git monaco`.

After you have placed the skin into that folder add `require_once("$IP/skins/monaco/monaco.php");` near the end of your LocalSettings.php to finish installation of the skin.

MediaWiki 1.17 includes the hook OutputPageBodyAttributes and the modifications to OutputPage.php and Skin.php necessary for this skin to add body classes into skins. If you are running an earlier verson you may apply the included OutputPageBodyAttributes.patch patch to your MediaWiki code to include the changes introduced into MediaWiki 1.17. This is a forward-compatible patch, you do not have to worry about re-applying it after you upgrade. Note that this patch was designed for MediaWiki 1.16, it has not been tested on earlier versions -- then again this skin probably won't even run on MediaWiki 1.15 since it uses MediaWiki 1.16 features.

You can also include the ExtendedBodyAttributes.php code if you wish to re-introduce the mainpage and loggedout classes that were in Wikia's version of Monaco, doing this will actually make these css classes available globally to all skins that are programmed using the MediaWiki 1.16 headElement code.

There is also another sub skin for monaco included in the package, AniMonaco, which features some of my own ideas geared towards animanga wiki. You can install it similarly using `require_once("$IP/skins/monaco/animonaco.php");`, be sure to install Monaco first.

Additionally you can install the ContentRightSidebar extension using `require_once("$IP/skins/monaco/ContentRightSiebar.php");`, doing so will provide you with a <right-sidebar>...</right-sidebar> tag which will create right floated content in the page that will be moved into the right sidebar in monaco based skins. You can also use it with the args <right-sidebar with-box title="My Title">...</right-sidebar> to include that sidebar in a sidebar box.



## James Haley
Monaco Skin for MediaWiki
=========================

About
-----

This is an unbranded fork of the Monaco skin originally developed by Wikia
which is being maintained for use at [DoomWiki.org](http://doomwiki.org). It was
also previously deployed at the Orain non-profit wiki farm before it went 
offline.

Compared to the original version of the skin, this fork now supports MediaWiki
versions 1.18 to 1.24 officially, with verified support for 1.25+ in the works.
This codebase will usually remain up-to-date against MediaWiki, and will drop
support for older versions unconditionally once it becomes impractical to
continue to support them.

New features in this fork over `dantman/monaco-port` include:  

* A new Widgets framework which cooperates with [Extension:Gadgets](https://www.mediawiki.org/wiki/Extension:Gadgets) to allow sidebar content to be defined through the MediaWiki frontend.
* Special support for [Extension:FlaggedRevs](https://www.mediawiki.org/wiki/Extension:FlaggedRevs) and [Extension:MobileFrontend](https://www.mediawiki.org/wiki/Extension:MobileFrontend) when they are installed.
* Wiki copyright notice is displayed in the footer of every page as with WikiMedia-maintained skins.
* Numerous bug fixes to stylesheets, JavaScript, and php HTML generation.

This fork is maintained by James Haley. I do not offer support for this
software beyond basic installation help, however. Please do not contact
me requesting any customizations for your particular site. Bug reports
are however very much welcome, as are generic feature requests that 
would be useful to everyone that might use it.

Installation
------------

To install, install monaco-port into a monaco/ folder in your skins/ folder.
From the command line you can do this by cd'ing to your skins/ folder inside
your MediaWiki installation and running:

`git clone git://github.com/haleyjd/monaco-port.git monaco`

After you have placed the skin into that folder add:

`require_once("$IP/skins/monaco/monaco.php");`

near the end of your LocalSettings.php to finish installation of the skin.

You can also include the ExtendedBodyAttributes.php code if you wish to
re-introduce the mainpage and loggedout classes that were in Wikia's version of
Monaco, doing this will actually make these css classes available globally to
all skins that are programmed using the MediaWiki 1.16 headElement code.

Additionally you can install the ContentRightSidebar extension using:

`require_once("$IP/skins/monaco/ContentRightSiebar.php");`

Doing so will provide you with a `<right-sidebar>...</right-sidebar>` tag which 
will create right floated content in the page that will be moved into the right
sidebar in monaco based skins. You can also use it with the args 

`<right-sidebar with-box="true" title="My Title">...</right-sidebar>`

to include that sidebar in a sidebar box. Note that a value is required for 
the `with-box` attribute when this extension is used with MediaWiki 1.25 or
later. For consistency, it is suggested that you provide this value anyway,
since it also works with earlier versions of MediaWiki.

License
-------
All of the code released by Wikia was made available under GPL v2.0 or later.
This license can be found in the COPYING file.
