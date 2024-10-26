# Change Log for epub-loader (this fork)

1.5.x - 2024xxxx Maintenance release for 1.x (PHP >= 7.4)
  * ...

3.x.x - 2024xxxx
  * Attach notes db instead of opening new connection

3.5.0 - 20241025 Expand metadata parsing + add models
  * Add traits + loading of authors, series and notes
  * Use authors & series arrays in BookInfo
  * Move all *Info models to Models sub-namespace
  * Rename BookInfos class to BookInfo + add other models
  * Modernize properties and parameters in class methods
  * Use common authors, books and series code in handlers
  * Show series and index in books list + support sort
  * Include php-epub-meta app as optional action (partial)
  * Parse wikidata + google cache entries to display links
  * Show identifiers by type in books list
  * Fix title sort and author sort on import and display
  * Use EPub::getSeriesOrCollection() when importing local books
  * Update mikespub/php-epub-meta package to release 3.3

3.4.1 - 20241020 Add cache actions + restructure
  * Move actions on entities to internal group
  * Parse goodreads cache entries to display links
  * Split action handler per action group or metadata
  * Split googlebooks and openlibrary namespaces
  * Import wikidata books + author/series links
  * Split tests by class + expand goodreads check
  * Get books by series + add series info and books
  * Check book links with author and series for GoodReads
  * Restructure import/export classes with source & target
  * Add cache statistics action + check book links
  * Remove support for single action files in app directory
  * Split off cache classes + move namespaces
  * Get available cache entries + expand tests
  * Add GoodReads Series action, paging and match
  * Split GoodReads namespace and add SeriesResult
  * Use action groups instead of actions in display
  * Cache database stats and file counts for big libraries

3.3.3 - 20241004 Add GoodReads actions + import OpenLibrary
  * Add JsonImport for OpenLibrary API cached files
  * Add parsing for OpenLibrary and WikiData files
  * Add GoodReads Author and GoodReads Books actions in app config

3.3.2 - 20241001 Import books from JSON files (GoodReads API)
  * Add JsonImport for GoodReads API cached files

3.3.1 - 20240930 Import books from CSV and JSON files (Google Books API)
  * Clarify license GPL version 2 or later + fix file headers
  * Add JsonImport for Google Books API cached files
  * Split CalibreDbLoader with BookImport and notes (TODO)
  * Remove deprecated BookEPub() methods for 1.x and 2.x
  * Add CSV import to create database from CSV export file
  * Fix tests for renamed COPS tests directory
  * Replace survos/wikidata with mikespub/survos-wikidata to include PR survos/wikidata#1
  * Update packages + check for vendor directory

3.2.0 - 20240905 Update tests + upgrade code
  * Update phpunit tests + upgrade code with rector

3.1.1 - 20240508 Update tests
  * Update dependencies + add more tests
  * Return config array from config.php

3.0.0 - 20240314 Switch to survos/wikidata package (PHP >= 8.2)
  * Add app index tests
  * Upgrade freearhey/wikidata 3.6 to forked survos/wikidata 4.x (PHP >= 8.2)
  * Add calibre users schema

2.5.0 - 20240307 Change URLs to use path_info
  * Use path_info for route urls

2.4.1 - 20240229 Start support for calibre notes
  * Get calibre resource
  * Get calibre notes

2.4.0 - 20240221 Get rid of superglobals
  * Use urlParams in request instead of superglobals

2.3.3 - 20240220 Match authors and books
  * Find author links and match books

2.3.2 - 20240219 Reorganize epub-loader namespaces
  * Use Marsender\EPubLoader\Metadata\Sources namespace
  * Use Marsender\EPubLoader\Metadata namespace
  * Use Marsender\EPubLoader\Export namespace

2.3.1 - 20240218 Update OpenLibraryMatch + add notes db
  * Fix empty matches for OpenLibraryMatch
  * Add notes_sqlite.sql schema from calibre repo

2.3.0 - 20240217 Start OpenLibraryMatch
  * Add OpenLibraryMatch to search in https://openlibrary.org/
  * Rename GoogleMatch to GoogleBooksMatch
  * Rename WikiMatch to WikiDataMatch

2.2.1 - 20240205 Update dependencies
  * Update dependencies in composer.json

2.2.0 - 20230925 Expand GoogleMatch + deprecate  (PHP >= 8.1)
  * Add Google Books volumes and language
  * Mark combined getsetters for BookEPub() as deprecated for 2.1.2

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
