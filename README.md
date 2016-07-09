#  MGageMorgan's monaco-port
The Monaco skin with Vector buttons and latest MediaWiki compatibility

## Community Widget Re-Implementation
I have implemented a ShoutWiki-style (close, but not exact) Community box. Install instructions are available when the skin is first added to MediaWiki. They will be in a sidebox widget with no title.

### Community Widget Screenshots
Screenshots from my Ubuntu 16.04 box on Chromium in the GNOME desktop interface:
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-01-22.png?raw=true 2)
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-02-38.png?raw=true 3)

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
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-00-24.png?raw=true 1)
![alt-text](https://github.com/MGageMorgan/monaco-port/blob/master/Screenshot%20from%202016-07-08%2021-03-05.png?raw=true 4)`


