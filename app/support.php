<?php

/**
 * Product support hub — HTML-viewable guides linked from GitHub docs.
 */

namespace App;

/**
 * Turn a GitHub blob URL for an .html file into a CDN URL that renders in the browser.
 *
 * ThemeForest-style packs ship Documentation/*.html buyers open locally. On the web,
 * github.com/blob/... shows source; jsDelivr serves the same file as a real page.
 */
function mh_github_html_doc_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://cdn\.jsdelivr\.net/gh/#i', $url) === 1) {
        return $url;
    }

    if (preg_match(
        '#^https?://github\.com/([^/]+)/([^/]+)/blob/([^/]+)/(.+\.html?)(?:[?#].*)?$#i',
        $url,
        $matches
    ) !== 1) {
        return $url;
    }

    return sprintf(
        'https://cdn.jsdelivr.net/gh/%s/%s@%s/%s',
        $matches[1],
        $matches[2],
        $matches[3],
        $matches[4]
    );
}

/**
 * Canonical online HTML docs hub for a product slug (Acreline marketplace folder).
 */
function mh_product_html_docs_hub_url(string $slug): string
{
    $slug = sanitize_title($slug);

    $hubs = [
        'acreline' => 'https://cdn.jsdelivr.net/gh/matthummel-pa/wp-acreline@main/docs/marketplace/index.html',
    ];

    return $hubs[$slug] ?? '';
}

/**
 * Extra HTML guide links for products that ship a ThemeForest-style Documentation/ set.
 *
 * @return list<array{label: string, url: string, blurb: string}>
 */
function mh_product_html_guide_links(string $slug): array
{
    $slug = sanitize_title($slug);

    if ($slug === 'acreline') {
        $base = 'https://cdn.jsdelivr.net/gh/matthummel-pa/wp-acreline@main/docs/marketplace';

        return [
            [
                'label' => __('Documentation hub', 'sage'),
                'url' => $base.'/index.html',
                'blurb' => __('Same Contents page as Documentation/index.html in the seller pack.', 'sage'),
            ],
            [
                'label' => __('Install & setup', 'sage'),
                'url' => $base.'/buyer-guide.html',
                'blurb' => __('Upload, Customizer, fields, demo seed, child theme, updates, FAQ.', 'sage'),
            ],
            [
                'label' => __('Support', 'sage'),
                'url' => $base.'/support.html',
                'blurb' => __('Where to get help, what to include in a ticket, what Acreline does not do.', 'sage'),
            ],
            [
                'label' => __('Customizer', 'sage'),
                'url' => $base.'/customizer.html',
                'blurb' => __('Identity, eight color styles, header, typography, social.', 'sage'),
            ],
            [
                'label' => __('Listings & bookings', 'sage'),
                'url' => $base.'/listings.html',
                'blurb' => __('Listing, agent, and booking fields; Acreline Core.', 'sage'),
            ],
            [
                'label' => __('Branding', 'sage'),
                'url' => $base.'/branding.html',
                'blurb' => __('House mark, lockup, Forest palette, fair housing note.', 'sage'),
            ],
            [
                'label' => __('FAQ', 'sage'),
                'url' => $base.'/faq.html',
                'blurb' => __('Folder name, plugins, MLS, child theme, showing query string.', 'sage'),
            ],
            [
                'label' => __('Changelog', 'sage'),
                'url' => $base.'/changelog.html',
                'blurb' => __('User-facing release history for the theme zip.', 'sage'),
            ],
        ];
    }

    return [];
}

/**
 * Support hub cards built from the product catalog + HTML guide links.
 *
 * @return list<array<string, mixed>>
 */
function mh_support_hub_products(): array
{
    $catalog = function_exists(__NAMESPACE__.'\\mh_product_catalog_seed_data')
        ? mh_product_catalog_seed_data()
        : [];

    $cards = [];
    foreach ($catalog as $slug => $seed) {
        if (! is_array($seed) || empty($seed['for_sale'])) {
            continue;
        }

        $slug = sanitize_title((string) $slug);
        $guides = mh_product_html_guide_links($slug);
        $docs = [];
        foreach (($seed['docs'] ?? []) as $pair) {
            if (! is_array($pair) || count($pair) < 2) {
                continue;
            }
            $label = trim((string) $pair[0]);
            $url = mh_github_html_doc_url(trim((string) $pair[1]));
            if ($label === '' || $url === '') {
                continue;
            }
            $docs[] = ['label' => $label, 'url' => $url];
        }

        $support = mh_github_html_doc_url((string) ($seed['support'] ?? ''));
        $hub = mh_product_html_docs_hub_url($slug);
        if ($hub === '' && $guides !== []) {
            $hub = (string) $guides[0]['url'];
        }

        $cards[] = [
            'slug' => $slug,
            'title' => (string) ($seed['title'] ?? $slug),
            'blurb' => (string) ($seed['blurb'] ?? $seed['summary'] ?? ''),
            'type' => sanitize_key((string) ($seed['product_type'] ?? 'theme')),
            'version' => (string) ($seed['version'] ?? ''),
            'demo' => (string) ($seed['demo'] ?? ''),
            'github' => (string) ($seed['github'] ?? ''),
            'support' => $support,
            'hub' => $hub,
            'guides' => $guides,
            'docs' => $docs,
            'project_url' => function_exists(__NAMESPACE__.'\\mh_concept_page_url')
                ? mh_concept_page_url($slug)
                : home_url('/projects/'.$slug.'/'),
        ];
    }

    return $cards;
}

/**
 * Public URL for the Support page.
 */
function mh_support_page_url(): string
{
    $page = get_page_by_path('support');
    if ($page instanceof \WP_Post) {
        $url = get_permalink($page);

        return is_string($url) && $url !== '' ? $url : home_url('/support/');
    }

    return home_url('/support/');
}

/**
 * Support hub URL, optionally deep-linked to a product section (#acreline).
 */
function mh_support_page_url_for_product(string $slug = ''): string
{
    $url = mh_support_page_url();
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return $url;
    }

    return rtrim($url, '/').'/#'.$slug;
}
