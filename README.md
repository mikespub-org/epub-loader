# epub-loader readme

epub-loader is a utility resource for ebooks.

- CalibreDbLoader class allows create Calibre databases and add ebooks
- BookExport class allows to export ebooks metadata in csv files
- WikiMatch class allows to match ebooks and authors with Wikidata
- The app directory contains samples and allows to run actions


## Installation

- If a first-time install, copy app/config.php.example to app/config.php
- Edit config.php to match your config
- Run `composer install -o` to install all package dependencies and optimize autoloader
- Open the app directory url: [./app/index.php](./app/index.php)

## Dependencies

- This package depends on [mikespub/php-epub-meta](https://packagist.org/packages/mikespub/php-epub-meta) (seblucas/php-epub-meta) to get metadata from EPub files
- It is a complementary app for [mikespub/seblucas-cops](https://packagist.org/packages/mikespub/seblucas-cops) = COPS - Calibre OPDS (and HTML) PHP Server
