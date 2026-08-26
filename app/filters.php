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
            'title' => 'WordPress web design in Gettysburg | '.$brand,
            'desc' => 'I build WordPress sites in Gettysburg that shops can edit. Developers can copy the code. See example sites or say hello.',
        ],
        'template-home.blade.php' => [
            'title' => 'WordPress web design in Gettysburg | '.$brand,
            'desc' => 'I build WordPress sites in Gettysburg that shops can edit. Developers can copy the code. See example sites or say hello.',
        ],
        'template-services.blade.php' => [
            'title' => 'WordPress Developer for Hire in Gettysburg | '.$brand,
            'desc' => 'Custom WordPress sites, plugins, and web apps. Clear scope, full ownership at handoff, no lock-in. Based in Gettysburg, PA — open for new work.',
        ],
        'template-start.blade.php' => [
            'title' => 'Project brief for WordPress work | '.$brand,
            'desc' => 'A short discovery form for agencies and shops. Four steps so I can prepare for our first meeting in Gettysburg or remote.',
        ],
        'template-projects.blade.php' => [
            'title' => 'Example sites in Gettysburg | '.$brand,
            'desc' => 'Studio WordPress concepts for Gettysburg tours, inns, and shops. See example sites or say hello.',
        ],
        'template-thankyou.blade.php' => [
            'title' => 'Message received | '.$brand,
            'desc' => 'Your message is in my inbox. I reply within a day.',
        ],
        'template-uses.blade.php' => [
            'title' => 'Uses — tools and stack | '.$brand,
            'desc' => 'The tools, frameworks, and services I use on real WordPress projects — Sage, Tailwind, Cursor AI, GitHub Actions, HubSpot, and more.',
        ],
        'template-hire.blade.php' => [
            'title' => 'Hire a WordPress developer in Gettysburg | '.$brand,
            'desc' => 'Open for WordPress site builds, plugins, agency overflow, and Power Platform work. Based in Gettysburg, PA. Clear scope, full ownership at handoff.',
        ],
        'template-changelog.blade.php' => [
            'title' => 'Changelog | '.$brand,
            'desc' => 'A public record of notable updates to matthummel.com — new pages, design changes, accessibility fixes, and stack notes.',
        ],
        'template-accessibility.blade.php' => [
            'title' => 'Accessibility statement | '.$brand,
            'desc' => 'WCAG 2.1 Level AA and Section 508 conformance statement for matthummel.com. Features, known limitations, and how to report accessibility issues.',
        ],
        'template-privacy.blade.php' => [
            'title' => 'Privacy policy | '.$brand,
            'desc' => 'What data matthummel.com collects, how it is used, and your rights. No analytics, no ads, no data selling. Contact form and comment data only.',
        ],
        'template-terms.blade.php' => [
            'title' => 'Terms of use | '.$brand,
            'desc' => 'Terms covering code reuse (MIT licence), written content copyright, concept site disclaimers, and acceptable use of matthummel.com.',
        ],
        'template-contact.blade.php' => [
            'title' => 'Hire a developer in Gettysburg | '.$brand,
            'desc' => 'Write about a WordPress site, plugin, or snippet. I work in Gettysburg and usually reply in a couple of days. Say hello.',
        ],
        'template-about.blade.php' => [
            'title' => 'About Matt Hummel — WordPress developer in Gettysburg | '.$brand,
            'desc' => 'I write PHP and Blade, deploy with GitHub Actions, and lean toward code the next developer can read. Based in Gettysburg, PA. Open for new work.',
        ],
        'template-code.blade.php' => [
            'title' => 'Open-source WordPress code on GitHub | '.$brand,
            'desc' => 'Public GitHub repos, contribution history, and a resume from Gettysburg, PA. Fork Sage themes, plugins, and web apps — or say hello.',
        ],
        'template-now.blade.php' => [
            'title' => 'What I\'m doing now | '.$brand,
            'desc' => 'Building Ridges & Valleys studio, writing WordPress posts with copy-paste code, and open for new work. Updated August 2026.',
        ],
        'index.blade.php' => [
            'title' => 'Journal — WordPress development notes | '.$brand,
            'desc' => 'Short posts on WordPress, PHP, Sage, and the tools I use on real projects. Most posts include code you can copy and drop in.',
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
 * Resolved meta description for the current page, clipped to 155 characters.
 *
 * @since 3.1.0
 *
 * @return string Empty string when no description is available.
 */
function mh_seo_meta_description(): string
{
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
