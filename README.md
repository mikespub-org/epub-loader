# epub-loader

## Prerequisites for this fork
-	PHP 8.x with DOM, GD, Intl, Json, PDO SQLite, SQLite3, XML, XMLWriter and ZLib support (PHP 8.1 or later recommended)
- Release 2.x.x will only work with PHP >= 8.1 - typical for most source code & docker image installs in 2023 and later
- Release 1.x.x still works with PHP 7.4 if necessary - earlier PHP 7.x (or 5.x) versions are *not* supported with this fork

## Dependencies

- This package depends on [mikespub/php-epub-meta](https://packagist.org/packages/mikespub/php-epub-meta) (seblucas/php-epub-meta) to get metadata from EPub files
- It is a complementary app for [mikespub/seblucas-cops](https://packagist.org/packages/mikespub/seblucas-cops) = COPS - Calibre OPDS (and HTML) PHP Server

They have the same PHP version dependencies for 1.x and 2.x releases

## Description (original)

epub-loader is a utility resource for ebooks.

- CalibreDbLoader class allows create Calibre databases and add ebooks
- BookExport class allows to export ebooks metadata in csv files
- WikiMatch class allows to match ebooks and authors with Wikidata
- GoogleMatch class allows to match ebooks and authors with Google Books
- The app directory contains samples and allows to run actions

## Installation

- If a first-time install, copy app/config.php.example to app/config.php
- Edit config.php to match your config
- Run `composer install -o` to install all package dependencies and optimize autoloader
- Open the app directory url: [./app/index.php](./app/index.php)
