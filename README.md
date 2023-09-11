# epub-loader

## Prerequisites for this fork
-	PHP 8.x with DOM, GD, Intl, Json, PDO SQLite, SQLite3, XML, XMLWriter and ZLib support (PHP 8.1 or later recommended)
- Release 2.x.x will only work with PHP >= 8.1 - typical for most source code & docker image installs in 2023 and later
- Release 1.x.x still works with PHP 7.4 if necessary - earlier PHP 7.x (or 5.x) versions are *not* supported with this fork

## Dependencies

- This package depends on [mikespub/php-epub-meta](https://packagist.org/packages/mikespub/php-epub-meta) (seblucas/php-epub-meta) to get metadata from EPub files
- It is a complementary app for [mikespub/seblucas-cops](https://packagist.org/packages/mikespub/seblucas-cops) = COPS - Calibre OPDS (and HTML) PHP Server

They have the same PHP version dependencies for 1.x and 2.x releases

## Description

epub-loader is a utility package for ebooks. It can be used as a stand-alone project or included in your own PHP application

- CalibreDbLoader class allows create Calibre databases and add ebooks
- BookExport class allows to export ebooks metadata in csv files
- WikiMatch class allows to match ebooks and authors with Wikidata
- GoogleMatch class allows to match ebooks and authors with Google Books
- The app directory contains samples and allows to run actions

## Installation (stand-alone)

```sh
composer create-project mikespub/epub-loader
```

- If a first-time install, copy app/config.php.example to app/config.php
- Edit config.php to match your config
- Open the app directory url: [./app/index.php](./app/index.php)

## Installation (included)

```sh
composer require mikespub/epub-loader
```

- Run `composer install -o` to install all package dependencies and optimize autoloader if needed
- You can use `Marsender\EPubLoader\RequestHandler` to handle the request like in [./app/index.php](./app/index.php)

```php
use Marsender\EPubLoader\RequestHandler;

// get the global config for epub-loader from somewhere
// get the current action and dbNum if any

// you can define extra actions for your app - see example.php
$handler = new RequestHandler($gConfig, ExtraActions::class);
$result = $handler->request($action, $dbNum);
```

## Adding extra actions

- You can add extra actions on databases and/or epub files as shown in [./app/example.php](./app/example.php)
```php
public function more()
{
    // do some more...
    return [
        'result' => 'This is more...',
        'extra' => 'easy',
    ];
}
```
