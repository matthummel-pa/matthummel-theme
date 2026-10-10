<?php

/**
 * Path helpers for redirects and legacy sitemaps.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Normalizes request paths. No WordPress functions, so the rules can be unit tested.
 */
class Paths
{
    /**
     * Reduce a redirect source to a leading-slash path without a query string.
     *
     * @param  string  $source  Raw source.
     * @return string Empty when the value is not a usable path.
     */
    public static function normalize_source(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $source)) {
            $path = wp_parse_url($source, PHP_URL_PATH);
            $source = is_string($path) ? $path : '';
        }
        $source = explode('?', $source, 2)[0];
        $source = explode('#', $source, 2)[0];
        $source = '/'.ltrim($source, '/');
        $source = (string) preg_replace('#/+#', '/', $source);
        if (str_contains($source, '..')) {
            return '';
        }
        if ($source !== '/') {
            $source = rtrim($source, '/');
        }

        return $source;
    }

    /**
     * Whether a path is one of Rank Math's old sitemap URLs.
     *
     * @param  string  $path  Request path.
     */
    public static function is_legacy_sitemap(string $path): bool
    {
        $path = self::normalize_source($path);
        if (in_array($path, ['/sitemap_index.xml', '/sitemap.xml'], true)) {
            return true;
        }

        return preg_match(
            '#^/(?:post|page|project|category|post_tag|product|local|author|news)-sitemap(?:\d+)?\.xml$#',
            $path
        ) === 1;
    }

    /**
     * Whether a prefix redirect source matches a request path.
     *
     * @param  string  $source  Stored source path.
     * @param  string  $request  Request path.
     */
    public static function prefix_matches(string $source, string $request): bool
    {
        $source = self::normalize_source($source);
        $request = self::normalize_source($request);
        if ($source === '' || $source === '/' || $request === '') {
            return false;
        }

        return $request === $source || str_starts_with($request, $source.'/');
    }
}
