<?php
/**
 * SeriesInfo class
 *
 * @license    GPL v2 or later (http://www.gnu.org/licenses/gpl.html)
 * @author     mikespub
 */

namespace Marsender\EPubLoader\Metadata;

/**
 * SeriesInfo class contains informations about a series,
 * and methods to load this informations from multiple sources (eg epub file)
 */
class SeriesInfo extends BaseInfo
{
    public string $basePath = '';

    public string $id = '';

    public string $title = '';

    public string $sort = '';

    public string $link = '';

    /** @var array<BookInfo> */
    public array $books = [];

    /** @var array<AuthorInfo> */
    public array $authors = [];
}
