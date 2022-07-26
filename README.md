php-woff-converter
================================================================================

[![Latest Release](https://poser.pugx.org/teicee/woff-converter/v/stable.png)](https://packagist.org/packages/teicee/woff-converter)
[![Total Downloads](https://poser.pugx.org/teicee/woff-converter/downloads.png)](https://packagist.org/packages/teicee/woff-converter)
[![License](https://poser.pugx.org/teicee/woff-converter/license.png)](https://packagist.org/packages/teicee/woff-converter)

**PHP class to convert a WOFF font file into a TTF/OTF font file**


Description
--------------------------------------------------------------------------------

Nowadays, web project are using fonts provided in the WOFF/WOFF2 file format.
Sometimes the TTF version also exists but is increasingly no longer the case.
However the TTF file format can still be useful, for example with PDF tools.

### Features
- Import webfont in WOFF (Web Open Font Format) file format 1.0
  ([W3C specifications](https://www.w3.org/TR/WOFF/))
- Export font data in TTF (TrueType Font) file format
  ([Apple reference](https://developer.apple.com/fonts/TrueType-Reference-Manual/),
   [Microsoft specifications](https://docs.microsoft.com/fr-fr/typography/opentype/spec/otff))
- Full PHP library, only one file containing the static utility class is needed

### Requirements
PHP version 7.0 or higher

### License
This software is distributed under the [LGPL 2.1](http://www.gnu.org/licenses/lgpl-2.1.html) license.
Please read [LICENSE](https://raw.githubusercontent.com/teicee/php-woff-converter/main/LICENSE) for information on the software availability and distribution.


Installation
--------------------------------------------------------------------------------

This library is available on [Packagist](https://packagist.org/packages/teicee/woff-converter),
and installation via [Composer](https://getcomposer.org) is the simplest way to add it into your project.

### Install with composer

Just add the package dependency to your `composer.json` file:
```sh
composer require teicee/woff-converter 1.x-dev
```

Make sure that the autoload file from Composer is loaded.
```php
// somewhere early in your project's loading, require the Composer autoloader
// see: http://getcomposer.org/doc/00-intro.md
require 'vendor/autoload.php';
```

### Download and install

Alternatively, if you're not using Composer, you can
[download WoffConverter as a zip file](https://github.com/teicee/php-woff-converter/archive/main.zip),
then copy the `src/WoffConverter.php` file into one of the `include_path` directories specified in your PHP configuration.

Or you can also download only the PHP class file, directly from the project repository:
```sh
curl https://raw.githubusercontent.com/teicee/php-woff-converter/main/src/WoffConverter.php
```

Then you have to load the class file manually in your code:
```php
<?php
require 'path/to/src/WoffConverter.php';
```


Usage
--------------------------------------------------------------------------------

### Quick start

Just pass the path your WOFF file and the corresponding TTF file will be generated:
```php
use TIC\Fonts\WoffConverter;

// Convert a WOFF file in TTF...
WoffConverter::WOFFtoTTF("path/to/fonts/foobar.woff");
```

**Note:** You can specify the output TTF file in the 2nd optional argument.
By default it's derived from the input by replacing the extension `.woff` by `.ttf`.


### Settings

No settings, just a public boolean property if you need debug informations:
```php
// Enable debug on stdout
WoffConverter::$debug = true;
```

With this debug option, intermediate data will be displayed on stdout.


TODO
--------------------------------------------------------------------------------

- Implement a decoder for WOFF2 file format (with Brotli uncompress)

