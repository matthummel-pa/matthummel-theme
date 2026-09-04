<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return ' &hellip;';
});

add_filter('document_title_separator', function () {
    return '|';
});

add_filter('document_title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('wpseo_title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('rank_math/frontend/title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('aioseo_title', __NAMESPACE__.'\\mh_filter_document_title', 99);

add_filter('wpseo_metadesc', __NAMESPACE__.'\\mh_filter_meta_description', 99);
add_filter('rank_math/frontend/description', __NAMESPACE__.'\\mh_filter_meta_description', 99);
add_filter('aioseo_description', __NAMESPACE__.'\\mh_filter_meta_description', 99);

add_action('wp_head', __NAMESPACE__.'\\mh_print_meta_description', 1);

/**
 * Whether a known SEO plugin is active and will print its own meta description.
 *
 * @since 3.1.0
 */
function mh_seo_plugin_prints_description(): bool
{
    return defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION');
}

/**
 * Fallback title and meta description for known landing-page templates.
 *
 * @since 3.1.0
 *
 * @param  int|null  $post_id  Post ID to detect the active template; null uses the current query.
 * @return array{title: string, desc: string}
 */
function mh_seo_landing_defaults(?int $post_id = null): array
{
    $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
    $key = '';
    if (is_front_page()) {
        $key = 'front-page.blade.php';
    } elseif ($post_id) {
        $key = page_template_key($post_id);
        if ((int) get_option('page_for_posts') === $post_id) {
            $key = 'index.blade.php';
        }
    }

    $map = [
        'front-page.blade.php' => [
            'title' => mh_home_hero_default('seo_title', $brand),
            'desc' => __('WordPress developer for shops and agencies. Sage themes, plugins, and clear deploy paths. Say hello.', 'sage'),
        ],
        'template-home.blade.php' => [
            'title' => mh_home_hero_default('seo_title', $brand),
            'desc' => __('WordPress developer for shops and agencies. Sage themes, plugins, and clear deploy paths. Say hello.', 'sage'),
        ],
        'template-services.blade.php' => [
            'title' => __('Custom WordPress Sites & Plugins', 'sage').' | '.$brand,
            'desc' => __('Custom WordPress sites, plugins, and web apps for shops and agencies. Clear scope and clean handoffs. Say hello.', 'sage'),
        ],
        'template-projects.blade.php' => [
            'title' => __('WordPress Example Sites', 'sage').' | '.$brand,
            'desc' => __('WordPress example sites, themes, and plugins I build and maintain. See the work, then say hello.', 'sage'),
        ],
        'template-thankyou.blade.php' => [
            'title' => __('Message Received', 'sage').' | '.$brand,
            'desc' => __('Your note is in my inbox. I reply within one or two business days.', 'sage'),
        ],
        'template-uses.blade.php' => [
            'title' => __('WordPress Developer Tools', 'sage').' | '.$brand,
            'desc' => __('WordPress developer tools and stack I use daily: Sage, Vite, Tailwind, GitHub, and deploy paths.', 'sage'),
        ],
        'template-resources.blade.php' => [
            'title' => __('WordPress Starters & Free Tools', 'sage').' | '.$brand,
            'desc' => __('WordPress starters and free tools I share for shops, agencies, and developers learning the craft.', 'sage'),
        ],
        'template-affiliate-disclosure.blade.php' => [
            'title' => __('Affiliate Disclosure', 'sage').' | '.$brand,
            'desc' => __('Affiliate disclosure for links and products I mention. I only share tools I use in real WordPress work.', 'sage'),
        ],
        'template-hire.blade.php' => [
            'title' => __('Hire a WordPress Developer', 'sage').' | '.$brand,
            'desc' => __('Hire a WordPress developer for full-time, contract, or freelance work. Themes, plugins, and handoffs shops keep.', 'sage'),
        ],
        'template-changelog.blade.php' => [
            'title' => __('Theme Changelog', 'sage').' | '.$brand,
            'desc' => __('Changelog for the Matt Hummel WordPress theme: releases, fixes, and shipping notes.', 'sage'),
        ],
        'template-support.blade.php' => [
            'title' => __('Theme & Plugin Support Docs', 'sage').' | '.$brand,
            'desc' => __('HTML documentation for Acreline and other WordPress products: install, Customizer, FAQ, and support guides.', 'sage'),
        ],
        'template-accessibility.blade.php' => [
            'title' => __('Accessibility Statement', 'sage').' | '.$brand,
            'desc' => __('How I keep this WordPress site usable. Standards I aim for, and how to report an accessibility issue.', 'sage'),
        ],
        'template-privacy.blade.php' => [
            'title' => __('Privacy Policy', 'sage').' | '.$brand,
            'desc' => __('How this WordPress site handles privacy, contact data, and analytics. Plain language for visitors.', 'sage'),
        ],
        'template-terms.blade.php' => [
            'title' => __('Terms of Use', 'sage').' | '.$brand,
            'desc' => __('Terms of use for matthummel.com and related WordPress products. Read before you buy or reuse code.', 'sage'),
        ],
        'template-woocommerce.blade.php' => [
            'title' => '',
            'desc' => __('Cart, checkout, and account for digital WordPress themes from studio Work projects.', 'sage'),
        ],
        'template-contact.blade.php' => [
            'title' => __('Contact WordPress Developer', 'sage').' | '.$brand,
            'desc' => __('Contact WordPress developer Matt Hummel about themes, plugins, or a site your shop can edit.', 'sage'),
        ],
        'template-about.blade.php' => [
            'title' => __('WordPress Developer Bio', 'sage').' | '.$brand,
            'desc' => __('WordPress developer bio: I build sites shops can edit and agencies can hand off. Say hello.', 'sage'),
        ],
        'template-code.blade.php' => [
            'title' => __('WordPress Open Source Code', 'sage').' | '.$brand,
            'desc' => __('WordPress open source and full-stack code I ship on GitHub. Themes, plugins, and notes developers can reuse.', 'sage'),
        ],
        'template-now.blade.php' => [
            'title' => __('WordPress Themes I\'m Building', 'sage').' | '.$brand,
            'desc' => __('WordPress themes and plugins I am building now, plus shipping notes. Updated as work moves.', 'sage'),
        ],
        'template-start.blade.php' => [
            'title' => __('WordPress Project Brief', 'sage').' | '.$brand,
            'desc' => __('WordPress project brief: scope, stack, and handoff for shops and agencies. Say hello to start.', 'sage'),
        ],
        'index.blade.php' => [
            'title' => __('WordPress Development Journal', 'sage').' | '.$brand,
            'desc' => __('Notes on WordPress, plugins, web apps, and Power Platform. Practical posts for shops and developers.', 'sage'),
        ],
    ];

    return $map[$key] ?? ['title' => '', 'desc' => ''];
}

/**
 * Post ID for the current request, including the static front page.
 *
 * @since 3.1.0
 */
function mh_seo_current_post_id(): int
{
    if (is_front_page()) {
        return (int) get_option('page_on_front');
    }

    return (int) get_queried_object_id();
}

function mh_seo_document_title(): string
{
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $label = function_exists('woocommerce_page_title')
            ? wp_strip_all_tags((string) woocommerce_page_title(false))
            : __('Shop', 'sage');
        if ($label === '') {
            $label = __('Shop', 'sage');
        }
        $built = is_shop()
            ? __('WordPress Themes Shop', 'sage').' | '.$brand
            : $label.' | '.__('Shop', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (function_exists('is_product') && is_product()) {
        $post_id = (int) get_queried_object_id();
        $pluginTitle = mh_seo_plugin_meta($post_id, ['rank_math_title', '_yoast_wpseo_title']);
        if ($pluginTitle === false) {
            return '';
        }
        if ($pluginTitle !== '') {
            return mh_seo_len($pluginTitle) > 60 ? mh_seo_clip($pluginTitle, 60) : $pluginTitle;
        }
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $title = trim(get_the_title($post_id));
        if ($title === '') {
            return '';
        }
        $built = $title.' | '.__('WordPress Theme', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (function_exists('is_cart') && is_cart()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('Cart', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('Checkout', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('My account', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (is_singular(mh_project_post_type())) {
        $post_id = (int) get_queried_object_id();
        $pluginTitle = mh_seo_plugin_meta($post_id, ['rank_math_title', '_yoast_wpseo_title']);
        if ($pluginTitle === false) {
            return '';
        }
        if ($pluginTitle !== '') {
            return mh_seo_len($pluginTitle) > 60 ? mh_seo_clip($pluginTitle, 60) : $pluginTitle;
        }

        $title = trim(get_the_title());
        $place = trim((string) get_post_meta($post_id, '_mh_project_place', true));
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        if ($title === '') {
            return '';
        }
        $built = $place !== ''
            ? $title.' — '.$place.' | '.$brand
            : $title.' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    $post_id = mh_seo_current_post_id();
    if ($post_id) {
        $pluginTitle = mh_seo_plugin_meta($post_id, ['rank_math_title', '_yoast_wpseo_title']);
        if ($pluginTitle === false) {
            return '';
        }
        if ($pluginTitle !== '') {
            return mh_seo_len($pluginTitle) > 60 ? mh_seo_clip($pluginTitle, 60) : $pluginTitle;
        }
    }

    $defaults = mh_seo_landing_defaults($post_id);
    $custom = $post_id ? trim(field('seo_title', '', $post_id)) : '';
    $title = $custom !== '' ? $custom : $defaults['title'];
    if ($title === '') {
        return '';
    }

    return mh_seo_len($title) > 60 ? mh_seo_clip($title, 60) : $title;
}

/**
 * Multi-byte character length of a string.
 *
 * @since 3.1.0
 *
 * @param  string  $s  Input string.
 */
function mh_seo_len(string $s): int
{
    return mb_strlen($s);
}

/**
 * Clip a string to at most $max characters, appending an ellipsis.
 *
 * @since 3.1.0
 *
 * @param  string  $s  Input string.
 * @param  int  $max  Maximum character length including the trailing ellipsis.
 */
function mh_seo_clip(string $s, int $max): string
{
    return mb_substr($s, 0, max(1, $max - 1)).'…';
}

/**
 * Rank Math / Yoast title or description for a post.
 *
 * Returns a plain stored value, false when the stored value still has
 * plugin variables (let the plugin's already-processed string through),
 * or an empty string when nothing is set.
 *
 * @param  array<int, string>  $keys  Post meta keys to check in order.
 */
function mh_seo_plugin_meta(int $post_id, array $keys): string|false
{
    foreach ($keys as $key) {
        $value = trim((string) get_post_meta($post_id, $key, true));
        if ($value === '') {
            continue;
        }
        if (str_contains($value, '%')) {
            return false;
        }

        return $value;
    }

    return '';
}

/**
 * Resolved meta description for the current page, clipped to 155 characters.
 *
 * @since 3.1.0
 *
 * @return string Empty string when no description is available.
 */
function mh_seo_meta_description(): string
{
    if (function_exists('is_shop') && is_shop()) {
        $desc = __('WordPress themes and plugins for sale. Browse demos on Projects, check out here, or say hello for a custom build.', 'sage');

        return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
    }
    if (function_exists('is_product') && is_product()) {
        $post_id = (int) get_queried_object_id();
        $pluginDesc = mh_seo_plugin_meta($post_id, ['rank_math_description', '_yoast_wpseo_metadesc']);
        if ($pluginDesc === false) {
            return '';
        }
        if ($pluginDesc !== '') {
            return mh_seo_len($pluginDesc) > 155 ? mh_seo_clip($pluginDesc, 155) : $pluginDesc;
        }
        $desc = wp_strip_all_tags((string) (get_the_excerpt($post_id) ?: get_the_title($post_id)));
        $desc = wp_trim_words($desc, 28, '');
        if ($desc !== '' && ! str_ends_with($desc, '.')) {
            $desc .= '.';
        }
        if ($desc === '') {
            $desc = __('WordPress theme for sale. See the details or say hello for help adapting it.', 'sage');
        }

        return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
    }
    if (function_exists('is_cart') && is_cart()) {
        $desc = __('Review themes in your cart, update quantities, and continue to secure checkout.', 'sage');

        return $desc;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $desc = __('Secure checkout for digital WordPress themes. Access details arrive by email after payment.', 'sage');

        return $desc;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $desc = __('View orders, downloads, and account details for your WordPress theme purchases.', 'sage');

        return $desc;
    }

    if (is_singular(mh_project_post_type())) {
        $post_id = (int) get_queried_object_id();
        $pluginDesc = mh_seo_plugin_meta($post_id, ['rank_math_description', '_yoast_wpseo_metadesc']);
        if ($pluginDesc === false) {
            return '';
        }
        if ($pluginDesc !== '') {
            return mh_seo_len($pluginDesc) > 155 ? mh_seo_clip($pluginDesc, 155) : $pluginDesc;
        }

        $summary = trim((string) get_post_meta($post_id, '_mh_project_summary', true));
        if ($summary === '') {
            $summary = trim((string) get_post_meta($post_id, '_mh_project_blurb', true));
        }
        if ($summary === '') {
            $summary = 'WordPress project example. See the demo or say hello to adapt it for your shop.';
        }
        if ($summary !== '' && ! str_ends_with($summary, '.')) {
            $summary .= '.';
        }

        return mh_seo_len($summary) > 155 ? mh_seo_clip($summary, 155) : $summary;
    }

    $post_id = mh_seo_current_post_id();
    if ($post_id) {
        $pluginDesc = mh_seo_plugin_meta($post_id, ['rank_math_description', '_yoast_wpseo_metadesc']);
        if ($pluginDesc === false) {
            return '';
        }
        if ($pluginDesc !== '') {
            return mh_seo_len($pluginDesc) > 155 ? mh_seo_clip($pluginDesc, 155) : $pluginDesc;
        }
    }

    $defaults = mh_seo_landing_defaults($post_id);
    $custom = $post_id ? trim(field('seo_desc', '', $post_id)) : '';
    $desc = $custom !== '' ? $custom : $defaults['desc'];
    if ($desc === '' && is_singular('post')) {
        $desc = wp_trim_words(wp_strip_all_tags(get_the_excerpt() ?: get_the_title()), 28, '');
        if ($desc !== '' && ! str_ends_with($desc, '.')) {
            $desc .= '.';
        }
    }
    if ($desc === '') {
        return '';
    }

    return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
}

/**
 * Replace the document title with the theme's custom SEO title when one is available.
 *
 * Hooked to document_title, wpseo_title, rank_math/frontend/title, and aioseo_title.
 *
 * @since 3.1.0
 *
 * @param  mixed  $title  Original title string passed by the filter.
 * @return mixed Custom title string, or the original value when no override exists.
 */
function mh_filter_document_title($title)
{
    if (is_admin() || ! is_string($title)) {
        return $title;
    }
    $custom = mh_seo_document_title();

    return $custom !== '' ? $custom : $title;
}

/**
 * Replace the SEO plugin meta description with the theme's custom value when available.
 *
 * Hooked to wpseo_metadesc, rank_math/frontend/description, and aioseo_description.
 *
 * @since 3.1.0
 *
 * @param  mixed  $desc  Original description string passed by the filter.
 * @return mixed Custom description string, or the original value when no override exists.
 */
function mh_filter_meta_description($desc)
{
    if (is_admin() || ! is_string($desc)) {
        return $desc;
    }
    $custom = mh_seo_meta_description();

    return $custom !== '' ? $custom : $desc;
}

/**
 * Output the <meta name="description"> tag when no SEO plugin is active.
 *
 * Hooked to wp_head at priority 1. Skips output when a plugin already handles the tag.
 *
 * @since 3.1.0
 */
function mh_print_meta_description(): void
{
    if (is_admin() || mh_seo_plugin_prints_description()) {
        return;
    }
    $desc = mh_seo_meta_description();
    if ($desc === '') {
        return;
    }
    echo '<meta name="description" content="'.esc_attr($desc).'">'."\n";
}
