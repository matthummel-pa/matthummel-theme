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

function mh_seo_plugin_prints_description(): bool
{
    return defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION');
}

/** @return array{title: string, desc: string} */
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
            'title' => 'WordPress sites in Gettysburg | '.$brand,
            'desc' => 'WordPress sites, plugins, and web apps for Gettysburg shops and agencies. I take a few extra projects. Say hello.',
        ],
        'template-projects.blade.php' => [
            'title' => 'Example sites in Gettysburg | '.$brand,
            'desc' => 'Studio WordPress concepts for Gettysburg tours, inns, and shops. See example sites or say hello.',
        ],
        'template-contact.blade.php' => [
            'title' => 'Hire a developer in Gettysburg | '.$brand,
            'desc' => 'Write about a WordPress site, plugin, or snippet. I work in Gettysburg and usually reply in a couple of days. Say hello.',
        ],
        'template-about.blade.php' => [
            'title' => 'WordPress developer in Gettysburg | '.$brand,
            'desc' => 'I’m Matt in Gettysburg. I write about the web and build WordPress sites shops can keep running. Say hello.',
        ],
        'template-code.blade.php' => [
            'title' => 'WordPress developer GitHub and resume | '.$brand,
            'desc' => 'What I do as a WordPress and full-stack developer: GitHub stats, featured repos, resume, and the docs I use. Based in Gettysburg; I work with any location.',
        ],
        'template-now.blade.php' => [
            'title' => 'Now | '.$brand,
            'desc' => 'A short list of where my time is going: WordPress, plugins, journal posts, and a few extra Gettysburg builds.',
        ],
        'index.blade.php' => [
            'title' => 'Journal | '.$brand,
            'desc' => 'Notes on WordPress, plugins, and other web apps from Gettysburg. Developers can copy the examples. Shops and agencies can see how I explain a build.',
        ],
    ];

    return $map[$key] ?? ['title' => '', 'desc' => ''];
}

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

function mh_seo_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

function mh_seo_clip(string $s, int $max): string
{
    $cut = max(1, $max - 1);
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $cut).'…';
    }

    return substr($s, 0, $cut).'…';
}

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

function mh_filter_document_title($title)
{
    if (is_admin() || ! is_string($title)) {
        return $title;
    }
    $custom = mh_seo_document_title();

    return $custom !== '' ? $custom : $title;
}

function mh_filter_meta_description($desc)
{
    if (is_admin() || ! is_string($desc)) {
        return $desc;
    }
    $custom = mh_seo_meta_description();

    return $custom !== '' ? $custom : $desc;
}

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
