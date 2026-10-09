<?php

/**
 * Functions the theme can call.
 */

declare(strict_types=1);
use MH_SEO\Plugin;

/**
 * Whether MH SEO is printing the front-end head tags.
 *
 * The theme should skip its own title and description output when this is true.
 * It stays false while Rank Math is active, unless the head-tag setting is forced on.
 */
function mh_seo_is_managing_head(): bool
{
    return Plugin::is_managing_head();
}

/**
 * Whether Rank Math (free or Pro) is loaded.
 */
function mh_seo_rank_math_is_active(): bool
{
    return defined('RANK_MATH_VERSION') || class_exists('RankMath', false);
}

/**
 * Post types that get the SEO panel and score column.
 *
 * @return array<int, string>
 */
function mh_seo_post_types(): array
{
    $types = ['post', 'page', 'project', 'newsletter_issue'];
    $filtered = apply_filters('mh_seo_post_types', $types);
    if (! is_array($filtered)) {
        return $types;
    }
    $clean = [];
    foreach ($filtered as $type) {
        $type = sanitize_key((string) $type);
        if ($type !== '') {
            $clean[] = $type;
        }
    }

    return array_values(array_unique($clean));
}
