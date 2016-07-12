#  MGageMorgan's monaco-port
The Monaco skin modified to look like ShoutWiki literally using ShoutWiki's version as a reference implementation to base this one off of from scratch.

## Notice on 'wfLoadSkin': Not Complete Yet
I've been saying for awhile that this implementation supports that functionality. It doesn't fully, but I do plan on working on making it utilize the full JSON format by the end of the month (July 2016) or early next month (August 2016). At the time of writing, MediaWiki is still pretty early on in getting this going, but think of it like a package manager. Speaking of, I have a ton of extensions that I'd love to do, but unfortunately time isn't always on my side. I do, however, have roots in web development, and everything I do with it is going to hopefully be my best work. When I'm in college, I hope to make some cash by web development, or partnering with some new folks I meet. 

Just know that I realize that support isn't done yet, but will be soon. Cheers! --MGageMorgan

## Sidebar Improvements!!!
I've been nice and have gone from implementing my own version of SW's Community Widget to fixing the search bar to look like theirs as well as implementing code from their extension "NewsBox" right into the skin itself. Have a look!

![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/sidebar-new.png?raw=true 2)

## Community Widget Re-Implementation
I have implemented a ShoutWiki-style (close, but not exact) Community box. Install instructions are available when the skin is first added to MediaWiki. They will be in a sidebox widget with no title.

### Community Widget Screenshots
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-01-22.png?raw=true 2)
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-03-05.png?raw=true 4)`

**Running in Chromium on Ubuntu 16.04 localhost

## Installation
This section details how to install depending on what/which version of MediaWiki you plan to install Monaco to.

### MediaWiki's wfLoadSkin (MediaWiki >= 1.25.0)
I've brought Monaco up-to-speed on the latest with the new installation method. The new way to install Monaco is:

```php
wfLoadSkin( 'monaco' );
```

### For Older MediaWiki Installations (MediaWiki <= 1.24.0)
If you are running Mediawiki >= 1.25.0, the above subsection applies to you. Everyone else (MediaWiki <= 1.24.0) can install the original way:

```php
require_once("$IP/skins/monaco/monaco.php");
```
## Screenshots
Zoomed-out images of the skin itself:
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/shot1.png?raw=true 1)
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/shot2.png?raw=true 3)

