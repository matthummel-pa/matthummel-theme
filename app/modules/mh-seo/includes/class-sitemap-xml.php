<?php

/**
 * Sitemap paths and XML that do not need WordPress loaded.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Builds sitemap URLs and escaped XML.
 */
class Sitemap_Xml
{
    public const INDEX_PATH = '/sitemap_index.xml';

    public const STYLESHEET_PATH = '/mh-sitemap.xsl';

    public const MAX_PER_PAGE = 50000;

    /**
     * Clamp the entries-per-sitemap setting.
     *
     * @param  int  $value  Requested page size.
     */
    public static function per_page(int $value): int
    {
        return max(1, min(self::MAX_PER_PAGE, $value));
    }

    /**
     * How many child files a count needs.
     *
     * @param  int  $total  Eligible entries.
     * @param  int  $per_page  Page size.
     */
    public static function page_count(int $total, int $per_page): int
    {
        if ($total < 1) {
            return 0;
        }

        return (int) ceil($total / self::per_page($per_page));
    }

    /**
     * Public path for one child sitemap. Page 1 has no number.
     *
     * @param  string  $name  Sitemap name, such as post or category.
     * @param  int  $page  Page number, starting at 1.
     */
    public static function child_path(string $name, int $page): string
    {
        $name = strtolower($name);
        $page = max(1, $page);
        $suffix = $page > 1 ? (string) $page : '';

        return '/'.$name.'-sitemap'.$suffix.'.xml';
    }

    /**
     * Whether this path should redirect to the sitemap index.
     *
     * @param  string  $path  Request path.
     */
    public static function redirects_to_index(string $path): bool
    {
        $path = strtolower(Paths::normalize_source($path));

        return in_array($path, ['/sitemap.xml', '/wp-sitemap.xml'], true);
    }

    /**
     * Match a request path to a sitemap route.
     *
     * @param  string  $path  Request path.
     * @return array{kind: string, name: string, page: int}|null
     */
    public static function match(string $path): ?array
    {
        $path = strtolower(Paths::normalize_source($path));
        if ($path === self::INDEX_PATH) {
            return [
                'kind' => 'index',
                'name' => '',
                'page' => 1,
            ];
        }
        if ($path === self::STYLESHEET_PATH) {
            return [
                'kind' => 'stylesheet',
                'name' => '',
                'page' => 1,
            ];
        }
        if (preg_match('#^/([a-z0-9_-]+)-sitemap([0-9]+)?\.xml$#', $path, $matches) !== 1) {
            return null;
        }
        $page = 1;
        if (isset($matches[2]) && $matches[2] !== '') {
            $page = (int) $matches[2];
            if ($page < 1) {
                return null;
            }
        }

        return [
            'kind' => 'child',
            'name' => $matches[1],
            'page' => $page,
        ];
    }

    /**
     * Whether a post belongs in the sitemap.
     *
     * A blank noindex or exclude value means the post is included. "0" is an
     * explicit "index" or "include" and also belongs. Only "1" removes it.
     *
     * @param  string  $status  Post status.
     * @param  string  $password  Post password.
     * @param  string  $noindex  _mh_seo_noindex.
     * @param  string  $exclude  _mh_seo_sitemap_exclude.
     */
    public static function post_is_included(string $status, string $password, string $noindex, string $exclude): bool
    {
        if ($status !== 'publish' || $password !== '') {
            return false;
        }
        if ($noindex === '1' || $exclude === '1') {
            return false;
        }

        return true;
    }

    /**
     * Sitemap index document.
     *
     * @param  array<int, array{loc: string, lastmod?: string}>  $sitemaps  Child sitemap URLs.
     * @param  string  $stylesheet  Absolute stylesheet URL.
     */
    public static function index(array $sitemaps, string $stylesheet): string
    {
        $xml = self::preamble($stylesheet);
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($sitemaps as $sitemap) {
            $loc = isset($sitemap['loc']) ? (string) $sitemap['loc'] : '';
            if ($loc === '') {
                continue;
            }
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>".self::url($loc)."</loc>\n";
            if (! empty($sitemap['lastmod'])) {
                $xml .= "\t\t<lastmod>".self::text((string) $sitemap['lastmod'])."</lastmod>\n";
            }
            $xml .= "\t</sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * URL set document.
     *
     * @param  array<int, array{loc: string, lastmod?: string, images?: array<int, string>}>  $urls  Page URLs.
     * @param  string  $stylesheet  Absolute stylesheet URL.
     * @param  bool  $images  Whether to print the image namespace.
     */
    public static function urlset(array $urls, string $stylesheet, bool $images): string
    {
        $xml = self::preamble($stylesheet);
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($images) {
            $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        $xml .= ">\n";
        foreach ($urls as $url) {
            $loc = isset($url['loc']) ? (string) $url['loc'] : '';
            if ($loc === '') {
                continue;
            }
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>".self::url($loc)."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= "\t\t<lastmod>".self::text((string) $url['lastmod'])."</lastmod>\n";
            }
            if ($images && ! empty($url['images']) && is_array($url['images'])) {
                foreach ($url['images'] as $image) {
                    $image = (string) $image;
                    if ($image === '') {
                        continue;
                    }
                    $xml .= "\t\t<image:image><image:loc>".self::url($image)."</image:loc></image:image>\n";
                }
            }
            $xml .= "\t</url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * XSL stylesheet for a browser view of the index or a child sitemap.
     */
    public static function stylesheet(): string
    {
        $title = self::text(__('Sitemap', 'mh-seo'));
        $intro = self::text(__('This is the XML sitemap for the site.', 'mh-seo'));
        $url = self::text(__('URL', 'mh-seo'));
        $when = self::text(__('Last modified', 'mh-seo'));

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" exclude-result-prefixes="s image">'."\n"
            .'<xsl:output method="html" encoding="UTF-8" indent="yes"/>'."\n"
            .'<xsl:template match="/">'."\n"
            .'<html><head><meta charset="utf-8"/><title>'.$title.'</title>'."\n"
            .'<style>body{font-family:Georgia,serif;margin:2rem;color:#1c1917}a{color:#1d4ed8}table{border-collapse:collapse;width:100%}td,th{border-bottom:1px solid #e7e5e4;padding:.6rem .4rem;text-align:left;vertical-align:top}th{font-size:.8rem;letter-spacing:.04em;text-transform:uppercase}</style>'."\n"
            .'</head><body><h1>'.$title.'</h1><p>'.$intro.'</p>'."\n"
            .'<table><thead><tr><th>'.$url.'</th><th>'.$when.'</th></tr></thead><tbody>'."\n"
            .'<xsl:for-each select="s:sitemapindex/s:sitemap|s:urlset/s:url">'."\n"
            .'<tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td><xsl:value-of select="s:lastmod"/></td></tr>'."\n"
            .'</xsl:for-each>'."\n"
            .'</tbody></table></body></html></xsl:template></xsl:stylesheet>';
    }

    /**
     * XML declaration and stylesheet instruction.
     *
     * @param  string  $stylesheet  Absolute stylesheet URL.
     */
    private static function preamble(string $stylesheet): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        if ($stylesheet !== '') {
            $xml .= '<?xml-stylesheet type="text/xsl" href="'.self::url($stylesheet).'" ?>'."\n";
        }

        return $xml;
    }

    /**
     * Escape a URL for an XML text node or attribute.
     *
     * @param  string  $value  Raw URL.
     */
    private static function url(string $value): string
    {
        return self::text($value);
    }

    /**
     * Escape text for XML.
     *
     * @param  string  $value  Raw text.
     */
    private static function text(string $value): string
    {
        return esc_xml($value);
    }
}
