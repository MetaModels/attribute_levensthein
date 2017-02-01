[![Build Status](https://travis-ci.org/MetaModels/attribute_levensthein.svg?branch=tng)](https://travis-ci.org/MetaModels/attribute_levensthein)
[![Latest Version tagged](http://img.shields.io/github/tag/MetaModels/attribute_levensthein.svg)](https://github.com/MetaModels/attribute_levensthein/tags)
[![Latest Version on Packagist](http://img.shields.io/packagist/v/MetaModels/attribute_levensthein.svg)](https://packagist.org/packages/MetaModels/attribute_levensthein)
[![Installations via composer per month](http://img.shields.io/packagist/dm/MetaModels/attribute_levensthein.svg)](https://packagist.org/packages/MetaModels/attribute_levensthein)

Levensthein
===========

The levensthein attribute maintains an index of keywords across other attributes which can be searched using the 
levensthein algorithm.

**NOTE:** This uses the autocomplete plugin from jquery-ui.
When installing this extension, you must include jquery-ui from CDN as the
Contao bundled version only includes the accordion plugin.

To do this, simply add and include a template named `j_jquery-ui.html5` in your
page layout with the following contents:
```php
<script src="//code.jquery.com/ui/<?= $GLOBALS['TL_ASSETS']['JQUERY_UI'] ?>/jquery-ui.min.js"></script>
```
**WARNING:** Ensure to remove the corresponding line from `j_accordion.html5`
if you are using accordions on your page as otherwise the two will collide.
