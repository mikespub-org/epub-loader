# EPub Loader

## Prerequisites for this fork
-	PHP 8.x with DOM, GD, Intl, Json, PDO SQLite, SQLite3, XML, XMLWriter and ZLib support (PHP 8.1 or later recommended)
- Release 3.x.x will only work with PHP >= 8.2 - typical for most source code & docker image installs in 2024 and later
- Release 2.x.x will only work with PHP >= 8.1 - typical for most source code & docker image installs in 2023 and later
- Release 1.x.x still works with PHP 7.4 if necessary - earlier PHP 7.x (or 5.x) versions are *not* supported with this fork

## Dependencies

- This package depends on [mikespub/php-epub-meta](https://packagist.org/packages/mikespub/php-epub-meta) (seblucas/php-epub-meta) to get metadata from EPub files
- It is a complementary app for [mikespub/seblucas-cops](https://packagist.org/packages/mikespub/seblucas-cops) = COPS - Calibre OPDS (and HTML) PHP Server

They have the same PHP version dependencies for 1.x and 2.x releases

## Description

EPub Loader is a utility package for ebooks. It can be used as a stand-alone project or included in your own PHP application.

It supports multiple databases defined by name, database path and epub path where its ebooks are located. You can create new Calibre-compatible databases with EPub Loader, or use existing Calibre databases from elsewhere.

Main features are:

- Import:
  - create Calibre database with available ebooks
  - import CSV records into new Calibre database
  - import JSON files from Lookup into new Calibre database
- Export:
  - export CSV records with available ebooks
  - dump CSV records from Calibre database
- Lookup:
  - match ebooks and authors with WikiData
  - match ebooks and authors with Google Books
  - match ebooks and authors with OpenLibrary
  - match ebooks and authors with GoodReads
- Extra:
  - run extra actions defined in the app directory

## Installation (stand-alone)

```sh
composer create-project mikespub/epub-loader
```

- If a first-time install, copy `app/config.php.example` to `app/config.php`
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
// get the current action, dbNum and urlParams if any

// you can define extra actions for your app - see example.php
$handler = new RequestHandler($gConfig, ExtraActions::class, $cacheDir);
$result = $handler->request($action, $dbNum, $urlParams);

// handle the result yourself or let epub-loader generate the output
$result['endpoint'] = 'index.php/loader';
$result['app_name'] = 'My EPub Loader';
echo $handler->output($result, $templateDir, $template);
```

## Adding extra actions

- You can add extra actions on databases and/or epub files as shown in [./app/example.php](./app/example.php)
```php
class ExtraActions extends ActionHandler
{
    // ...

    public function more()
    {
        // do some more...
        return [
            'result' => 'This is more...',
            'extra' => 'easy',
        ];
    }
}
```

## License & Copyright

This package is available under the GNU General Public License v2 or later - see [LICENSE](LICENSE)

EPub Loader was first distributed as [resource for COPS](https://github.com/seblucas/cops/tree/master/resources/epub-loader) - Calibre OPDS (and HTML) PHP Server. This fork is now a separate package that can work alone or be integrated with other web applications.

Copyright (C) 2013-2021 Didier Corbière
