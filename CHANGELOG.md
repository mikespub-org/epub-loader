# Change Log for epub-loader (this fork)

2.1.x - 2023xxxx To be continued (PHP >= 8.1)
  * ...

1.5.x - 2023xxxx Maintenance release for 1.x (PHP >= 7.4)
  * ...

2.1.1 - 20230911 Public release for integration (PHP >= 8.1)
  * Make URL endpoint configurable for easier integration
  * Add example.php on how to add extra actions in your own app
  * Add RequestHandler to make it easier to integrate epub-loader
  * Add GoogleMatch to match ebooks and authors with Google Books

2.0.3 - 20230910 Finalize package preparation for composer (PHP >= 8.1)
  * Set package dependency mikespub/php-epub-meta to release 2.x
  * Update package name, PHP requirements and dependencies in composer.json

1.5.5 - 20230910 Finalize package preparation for composer (PHP >= 7.4)
  * Set package dependency mikespub/php-epub-meta to release 1.x
  * Update package name, PHP requirements and dependencies in composer.json

2.0.0 - 20230910 Initial release for PHP >= 8.1 with new EPub update package
  * Use maennchen/zipstream-php to update epub files on the fly (PHP 8.x)

1.5.0 - 20230909 Split off epub-loader from seblucas-cops resources
  * Split off epub-loader, php-epub-meta and tbszip resources again
  * Align resources folders to src and app in code
  * Support class inheritance for most COPS lib and resource classes in code
  * Add resources/epub-loader actions for books, series and wikidata

For previous changes see https://github.com/mikespub-org/seblucas-cops/blob/main/CHANGELOG.md

Changes affecting epub-loader:

1.4.4 - 20230904 Revert OPDS feed changes for old e-readers
  * ...
  * Prepare move from clsTbsZip to ZipEdit when updating EPUB in code

1.4.3 - 20230831 Sort & Filter in OPDS Catalog + Add bootstrap v5 template
  * ...
  * Keep track of changes in ZipFile + fix setCoverInfo() in EPub in code
  * Mark combined getsetters for EPub() as deprecated for 1.5.0 in php-epub-meta
  * Add updated php-epub-meta methods and classes to version in resources - see https://github.com/epubli/epub
  * Fix code base to work with phpstan level 6

1.3.4 - 20230609 Fix EPUB 3 TOC, replace other npm assets and use namespace in PHP resources
  * Fix TOC for EPUB 3 files in resources/php-epub-meta for epubreader
  * ...
  * Use PHP namespace in resources/epub-loader: Marsender\EPubLoader
  * Use PHP namespace in resources/php-epub-meta: SebLucas\EPubMeta
  * Use PHP namespace in resources/tbszip: SebLucas\TbsZip

...
