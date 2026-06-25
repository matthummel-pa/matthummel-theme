<?php

/**
 * Lightweight SEO + JSON-LD schema. Outputs Open Graph + Twitter meta, an
 * Organization/Person entity, WebSite (with SearchAction), Article (on posts),
 * and a BreadcrumbList. Auto-disables when Yoast or Rank Math is active so it
 * never duplicates their output. Also exposes a [mh_breadcrumbs] shortcode.
 */

namespace App;

function mh_seo_plugin_active()
{
    return defined('WPSEO_VERSION') || class_exists('RankMath') || function_exists('rank_math') || defined('AIOSEO_VERSION');
}

function mh_seo($k)
{
    $d = [
        'mh_seo_enable'  => true,
        'mh_seo_entity'  => 'person',
        'mh_seo_name'    => get_bloginfo('name'),
        'mh_seo_logo'    => '',
        'mh_seo_img'     => '',
        'mh_seo_twitter' => '',
    ];
    return get_theme_mod($k, $d[$k] ?? null);
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $desc = mh_seo_plugin_active()
        ? __('An SEO plugin is active, so the theme will NOT output its own meta/schema (to avoid duplicates).', 'matthummel')
        : __('Outputs Open Graph, Twitter cards, and JSON-LD structured data.', 'matthummel');
    $wp->add_section('mh_seo_section', ['title' => __('SEO & Schema', 'matthummel'), 'panel' => 'mh_theme_options', 'description' => $desc]);

    $wp->add_setting('mh_seo_enable', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_seo_enable', ['label' => __('Output meta + schema', 'matthummel'), 'section' => 'mh_seo_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_seo_entity', ['default' => 'person', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_seo_entity', ['label' => __('Site represents a', 'matthummel'), 'section' => 'mh_seo_section', 'type' => 'select', 'choices' => ['person' => __('Person', 'matthummel'), 'organization' => __('Organization', 'matthummel')]]);

    $wp->add_setting('mh_seo_name', ['default' => get_bloginfo('name'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_seo_name', ['label' => __('Name', 'matthummel'), 'section' => 'mh_seo_section', 'type' => 'text']);

    $wp->add_setting('mh_seo_logo', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control(new \WP_Customize_Image_Control($wp, 'mh_seo_logo', ['label' => __('Logo (square, for schema)', 'matthummel'), 'section' => 'mh_seo_section']));

    $wp->add_setting('mh_seo_img', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control(new \WP_Customize_Image_Control($wp, 'mh_seo_img', ['label' => __('Default social share image', 'matthummel'), 'section' => 'mh_seo_section']));

    $wp->add_setting('mh_seo_twitter', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_seo_twitter', ['label' => __('Twitter/X handle (with @)', 'matthummel'), 'section' => 'mh_seo_section', 'type' => 'text']);
}, 25);

/** Meta + schema in <head>. */
add_action('wp_head', function () {
    if (mh_seo_plugin_active() || ! mh_seo('mh_seo_enable')) {
        return;
    }

    $title = wp_get_document_title();
    $url   = (is_singular() && get_permalink()) ? get_permalink() : home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    $desc  = get_bloginfo('description');
    if (is_singular()) {
        $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', get_the_ID())), 30);
        if ($excerpt) {
            $desc = $excerpt;
        }
    }
    $img = '';
    if (is_singular() && has_post_thumbnail()) {
        $img = get_the_post_thumbnail_url(get_the_ID(), 'large');
    }
    if (! $img) {
        $img = mh_seo('mh_seo_img');
    }
    $tw = mh_seo('mh_seo_twitter');

    $m  = "\n<!-- mh SEO -->\n";
    $m .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    $m .= '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    $m .= '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($desc)) . '">' . "\n";
    $m .= '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    $m .= '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    if ($img) {
        $m .= '<meta property="og:image" content="' . esc_url($img) . '">' . "\n";
    }
    $m .= '<meta name="twitter:card" content="' . ($img ? 'summary_large_image' : 'summary') . '">' . "\n";
    if ($tw) {
        $m .= '<meta name="twitter:site" content="' . esc_attr($tw) . '">' . "\n";
        $m .= '<meta name="twitter:creator" content="' . esc_attr($tw) . '">' . "\n";
    }
    $m .= '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    $m .= '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($desc)) . '">' . "\n";
    if ($img) {
        $m .= '<meta name="twitter:image" content="' . esc_url($img) . '">' . "\n";
    }
    echo $m; // phpcs:ignore -- values escaped above

    // ---- JSON-LD graph ----
    $entityType = mh_seo('mh_seo_entity') === 'organization' ? 'Organization' : 'Person';
    $sameAs = [];
    if (function_exists('App\\mh_social_links')) {
        foreach (mh_social_links() as $s) {
            $sameAs[] = $s['url'];
        }
    }
    $entity = [
        '@type' => $entityType,
        '@id'   => home_url('/#entity'),
        'name'  => mh_seo('mh_seo_name'),
        'url'   => home_url('/'),
    ];
    if (mh_seo('mh_seo_logo')) {
        $entity['logo'] = mh_seo('mh_seo_logo');
        if ($entityType === 'Person') {
            $entity['image'] = mh_seo('mh_seo_logo');
        }
    }
    if ($sameAs) {
        $entity['sameAs'] = array_values($sameAs);
    }

    $graph = [$entity, [
        '@type'           => 'WebSite',
        '@id'             => home_url('/#website'),
        'url'             => home_url('/'),
        'name'            => get_bloginfo('name'),
        'publisher'       => ['@id' => home_url('/#entity')],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}')],
            'query-input' => 'required name=search_term_string',
        ],
    ]];

    if (is_singular('post')) {
        $graph[] = [
            '@type'         => 'Article',
            'headline'      => get_the_title(),
            'datePublished' => get_the_date('c'),
            'dateModified'  => get_the_modified_date('c'),
            'author'        => ['@id' => home_url('/#entity')],
            'publisher'     => ['@id' => home_url('/#entity')],
            'mainEntityOfPage' => get_permalink(),
            'image'         => $img ?: null,
        ];
    }

    // Breadcrumb schema
    $crumbs = mh_breadcrumb_items();
    if (count($crumbs) > 1) {
        $items = [];
        $pos = 1;
        foreach ($crumbs as $cr) {
            $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $cr['name'], 'item' => $cr['url']];
        }
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    $ld = ['@context' => 'https://schema.org', '@graph' => $graph];
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}, 5);

/** Breadcrumb trail items: [ ['name'=>, 'url'=>], ... ]. */
function mh_breadcrumb_items()
{
    $items = [['name' => __('Home', 'matthummel'), 'url' => home_url('/')]];

    if (is_singular()) {
        $post = get_queried_object();
        if (is_singular('post')) {
            $cats = get_the_category();
            if ($cats) {
                $items[] = ['name' => $cats[0]->name, 'url' => get_category_link($cats[0]->term_id)];
            }
        } elseif (! is_page() && $post && ! empty($post->post_type)) {
            $pto = get_post_type_object($post->post_type);
            if ($pto && ! empty($pto->has_archive)) {
                $items[] = ['name' => $pto->labels->name, 'url' => get_post_type_archive_link($post->post_type)];
            }
        }
        if ($post) {
            $items[] = ['name' => get_the_title($post), 'url' => get_permalink($post)];
        }
    } elseif (is_category() || is_tag() || is_tax()) {
        $t = get_queried_object();
        if ($t) {
            $items[] = ['name' => single_term_title('', false), 'url' => get_term_link($t)];
        }
    } elseif (is_post_type_archive()) {
        $items[] = ['name' => post_type_archive_title('', false), 'url' => '#'];
    } elseif (is_search()) {
        $items[] = ['name' => __('Search', 'matthummel'), 'url' => '#'];
    } elseif (is_404()) {
        $items[] = ['name' => __('Not found', 'matthummel'), 'url' => '#'];
    }
    return $items;
}

/** Visible breadcrumb HTML via [mh_breadcrumbs]. */
add_shortcode('mh_breadcrumbs', function () {
    $items = mh_breadcrumb_items();
    if (count($items) < 2) {
        return '';
    }
    $out = '<nav class="mh-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'matthummel') . '"><ol>';
    $last = count($items) - 1;
    foreach ($items as $i => $c) {
        $out .= '<li>';
        $out .= ($i === $last || $c['url'] === '#')
            ? '<span aria-current="page">' . esc_html($c['name']) . '</span>'
            : '<a href="' . esc_url($c['url']) . '">' . esc_html($c['name']) . '</a>';
        $out .= '</li>';
    }
    $out .= '</ol></nav>';
    return $out;
});

add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-breadcrumbs\">.mh-breadcrumbs ol{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0 0 14px;padding:0;font-size:13px;color:var(--color-muted,#5c636c);}.mh-breadcrumbs li:not(:last-child)::after{content:'/';margin-left:6px;opacity:.6;}.mh-breadcrumbs a{color:var(--color-muted,#5c636c);text-decoration:none;}.mh-breadcrumbs a:hover{color:var(--color-green,#2f6b4e);}</style>\n";
}, 18);
