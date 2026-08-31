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
            'title' => 'Full-Stack & WordPress Developer | '.$brand,
            'desc' => 'Full-stack developer and WordPress specialist building maintainable web platforms, applications, plugins, and integrations. Say hello to start.',
        ],
        'template-home.blade.php' => [
            'title' => 'Full-Stack & WordPress Developer | '.$brand,
            'desc' => 'Full-stack developer and WordPress specialist building maintainable web platforms, applications, plugins, and integrations. Say hello to start.',
        ],
        'template-services.blade.php' => [
            'title' => 'Full-Stack & WordPress Development Services | '.$brand,
            'desc' => 'Custom WordPress platforms, plugins, integrations, and full-stack web applications for businesses, agencies, and development teams.',
        ],
        'template-start.blade.php' => [
            'title' => 'Project brief for WordPress work | '.$brand,
            'desc' => 'A short discovery form for agencies and shops. Four steps so I can prepare for our first meeting.',
        ],
        'template-projects.blade.php' => [
            'title' => 'Example WordPress Sites | '.$brand,
            'desc' => 'Studio WordPress projects for tours, inns, and shops. See example sites or say hello.',
        ],
        'template-thankyou.blade.php' => [
            'title' => 'Message received | '.$brand,
            'desc' => 'Your message is in my inbox. I reply within a day.',
        ],
        'template-uses.blade.php' => [
            'title' => 'Uses — tools and stack | '.$brand,
            'desc' => 'The tools I use on real WordPress projects — Sage, Tailwind, Cursor AI, GitHub Actions, and more. Say hello if you want to build together.',
        ],
        'template-hire.blade.php' => [
            'title' => 'Hire a Full-Stack & WordPress Developer | '.$brand,
            'desc' => 'Available for full-stack web applications, WordPress development, plugins, integrations, agency overflow, and full-time or contract roles.',
        ],
        'template-changelog.blade.php' => [
            'title' => 'Changelog | '.$brand,
            'desc' => 'A public record of notable updates to matthummel.com — new pages, design changes, accessibility fixes, and stack notes.',
        ],
        'template-accessibility.blade.php' => [
            'title' => 'Accessibility statement | '.$brand,
            'desc' => 'WCAG 2.1 Level AA and Section 508 conformance statement for matthummel.com. Features, known limitations, and how to report issues.',
        ],
        'template-privacy.blade.php' => [
            'title' => 'Privacy policy | '.$brand,
            'desc' => 'What data matthummel.com collects, how it is used, and your rights. No analytics, no ads, no data selling.',
        ],
        'template-terms.blade.php' => [
            'title' => 'Terms of use | '.$brand,
            'desc' => 'Terms covering code reuse (MIT licence), written content copyright, example-site disclaimers, and acceptable use of matthummel.com.',
        ],
        'template-woocommerce.blade.php' => [
            'title' => '',
            'desc' => 'Shop cart, checkout, and account pages for matthummel.com.',
        ],
        'template-contact.blade.php' => [
            'title' => 'Contact a Full-Stack & WordPress Developer | '.$brand,
            'desc' => 'Start a conversation about a web application, WordPress platform, plugin, integration, role, or development partnership.',
        ],
        'template-about.blade.php' => [
            'title' => 'About Matt Hummel — Full-Stack & WordPress Developer',
            'desc' => 'Full-stack developer and WordPress specialist with 15+ years building accessible, maintainable web software. Say hello.',
        ],
        'template-code.blade.php' => [
            'title' => 'Open-Source Full-Stack & WordPress Code | '.$brand,
            'desc' => 'Public React apps, Sage themes, WordPress plugins, PHP, TypeScript, and GitHub activity. Browse projects, fork repos, or collaborate.',
        ],
        'template-now.blade.php' => [
            'title' => 'What I\'m doing now | '.$brand,
            'desc' => 'Publishing example WordPress projects, writing notes, and open for new work.',
        ],
        'index.blade.php' => [
            'title' => 'Journal — Full-Stack & WordPress Development | '.$brand,
            'desc' => 'Practical notes on WordPress, PHP, JavaScript, React, APIs, and tools from real projects. Most posts include code you can adapt.',
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
