<?php

/**
 * Social and structured-data meta: Open Graph, Twitter cards, JSON-LD, and
 * the MH SEO noindex flag.
 *
 * Uses the same title and description the theme already prints, so share
 * previews match search results. Steps aside when MH SEO manages the head or
 * another SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active.
 */

namespace App;

/** Whether the theme should print social/schema meta itself. */
function mh_social_meta_enabled(): bool
{
    if (is_admin() || is_feed() || is_404() || mh_seo_plugin_prints_description()) {
        return false;
    }
    if (function_exists('mh_seo_is_managing_head') && mh_seo_is_managing_head()) {
        return false;
    }

    return (bool) apply_filters('mh/social_meta', true);
}

/**
 * Share image for the current view: featured image, then the front page's, then the site icon.
 *
 * @return array{url: string, width: int, height: int, alt: string}|null
 */
function mh_social_image(): ?array
{
    $ids = [];
    if (is_singular()) {
        $ids[] = (int) get_post_thumbnail_id((int) get_queried_object_id());
    }
    $front = (int) get_option('page_on_front');
    if ($front > 0) {
        $ids[] = (int) get_post_thumbnail_id($front);
    }

    foreach (array_filter($ids) as $id) {
        $src = wp_get_attachment_image_src($id, 'full');
        if (is_array($src) && ! empty($src[0])) {
            return [
                'url' => (string) $src[0],
                'width' => (int) ($src[1] ?? 0),
                'height' => (int) ($src[2] ?? 0),
                'alt' => trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)),
            ];
        }
    }

    $icon = get_site_icon_url(512);

    return $icon !== '' ? ['url' => $icon, 'width' => 512, 'height' => 512, 'alt' => ''] : null;
}

/** Canonical URL for the current view. */
function mh_social_url(): string
{
    if (is_singular()) {
        return (string) (wp_get_canonical_url((int) get_queried_object_id()) ?: get_permalink());
    }
    if (is_front_page()) {
        return home_url('/');
    }
    $term = get_queried_object();
    if ($term instanceof \WP_Term) {
        $link = get_term_link($term);

        return is_wp_error($link) ? home_url('/') : (string) $link;
    }
    if (is_home()) {
        $blog = (int) get_option('page_for_posts');

        return $blog > 0 ? (string) get_permalink($blog) : home_url('/');
    }

    return esc_url_raw(home_url(add_query_arg([])));
}

add_action('wp_head', function (): void {
    if (! mh_social_meta_enabled()) {
        return;
    }

    $title = mh_seo_document_title() ?: wp_get_document_title();
    $desc = mh_seo_meta_description();
    $image = mh_social_image();
    $isPost = is_singular('post');

    $tags = [
        ['property', 'og:locale', str_replace('-', '_', get_bloginfo('language'))],
        ['property', 'og:site_name', get_bloginfo('name')],
        ['property', 'og:type', $isPost ? 'article' : 'website'],
        ['property', 'og:title', $title],
        ['property', 'og:description', $desc],
        ['property', 'og:url', mh_social_url()],
    ];
    if ($image) {
        $tags[] = ['property', 'og:image', $image['url']];
        if ($image['width'] > 0) {
            $tags[] = ['property', 'og:image:width', (string) $image['width']];
            $tags[] = ['property', 'og:image:height', (string) $image['height']];
        }
        if ($image['alt'] !== '') {
            $tags[] = ['property', 'og:image:alt', $image['alt']];
        }
    }
    if ($isPost) {
        $postId = (int) get_queried_object_id();
        $tags[] = ['property', 'article:published_time', (string) get_post_time('c', true, $postId)];
        $tags[] = ['property', 'article:modified_time', (string) get_post_modified_time('c', true, $postId)];
        $cats = get_the_category($postId);
        if ($cats) {
            $tags[] = ['property', 'article:section', $cats[0]->name];
        }
        foreach ((array) get_the_tags($postId) as $tag) {
            if ($tag instanceof \WP_Term) {
                $tags[] = ['property', 'article:tag', $tag->name];
            }
        }
    }
    $tags[] = ['name', 'twitter:card', $image && $image['width'] >= 600 ? 'summary_large_image' : 'summary'];
    $tags[] = ['name', 'twitter:title', $title];
    $tags[] = ['name', 'twitter:description', $desc];
    if ($image) {
        $tags[] = ['name', 'twitter:image', $image['url']];
    }

    foreach ($tags as [$attr, $key, $value]) {
        if ((string) $value === '') {
            continue;
        }
        printf('<meta %1$s="%2$s" content="%3$s">'."\n", esc_attr($attr), esc_attr($key), esc_attr((string) $value));
    }
}, 2);

/** JSON-LD: BlogPosting on posts, WebSite + Person on the front page, WebPage on other pages. */
add_action('wp_head', function (): void {
    if (! mh_social_meta_enabled() || is_home() || (function_exists('is_woocommerce') && is_woocommerce())) {
        return;
    }

    $home = home_url('/');
    $person = [
        '@type' => 'Person',
        '@id' => $home.'#person',
        'name' => get_bloginfo('name'),
        'url' => $home,
        'sameAs' => array_values(array_filter(array_map(
            static fn (array $l): string => $l['key'] === 'rss' ? '' : (string) $l['url'],
            function_exists(__NAMESPACE__.'\\mh_social_links') ? mh_social_links() : []
        ))),
    ];
    $image = mh_social_image();
    $desc = mh_seo_meta_description();
    $graph = [];

    if (is_front_page()) {
        $graph[] = [
            '@type' => 'WebSite',
            '@id' => $home.'#website',
            'url' => $home,
            'name' => get_bloginfo('name'),
            'description' => $desc,
            'publisher' => ['@id' => $home.'#person'],
            'inLanguage' => get_bloginfo('language'),
        ];
        $graph[] = $person;
    } elseif (is_singular('post')) {
        $postId = (int) get_queried_object_id();
        $url = mh_social_url();
        $cats = get_the_category($postId);
        $postTags = get_the_tags($postId);
        $postTags = is_array($postTags) ? $postTags : [];
        $graph[] = array_filter([
            '@type' => 'BlogPosting',
            '@id' => $url.'#article',
            'mainEntityOfPage' => $url,
            'headline' => wp_strip_all_tags(get_the_title($postId)),
            'description' => $desc,
            'image' => $image['url'] ?? null,
            'datePublished' => get_post_time('c', true, $postId),
            'dateModified' => get_post_modified_time('c', true, $postId),
            'author' => ['@id' => $home.'#person'],
            'publisher' => ['@id' => $home.'#person'],
            'articleSection' => $cats ? $cats[0]->name : null,
            'keywords' => $postTags ? implode(', ', wp_list_pluck($postTags, 'name')) : null,
            'inLanguage' => get_bloginfo('language'),
        ]);
        $graph[] = $person;
    } elseif (is_page()) {
        $graph[] = array_filter([
            '@type' => 'WebPage',
            '@id' => mh_social_url().'#webpage',
            'url' => mh_social_url(),
            'name' => mh_seo_document_title() ?: wp_get_document_title(),
            'description' => $desc,
            'primaryImageOfPage' => $image['url'] ?? null,
            'isPartOf' => ['@id' => $home.'#website'],
            'inLanguage' => get_bloginfo('language'),
        ]);
    }

    if ($graph === []) {
        return;
    }

    echo '<script type="application/ld+json">'
        .wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        ."</script>\n";
}, 3);

/** Honor the MH SEO "noindex" checkbox while the theme prints the head. */
add_filter('wp_robots', function (array $robots): array {
    if (! mh_social_meta_enabled() || ! is_singular()) {
        return $robots;
    }
    if ((string) get_post_meta((int) get_queried_object_id(), '_mh_seo_noindex', true) === '1') {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
});
