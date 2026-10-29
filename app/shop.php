<?php

/**
 * Shop helpers for themes and plugins: catalog lookups, Freemius plans, free downloads, and the shop listing.
 */

namespace App;

/** Public Projects listing URL. */
function mh_work_listing_url(): string
{
    return home_url('/projects/');
}

/**
 * Decoded product-catalog.json keyed by slug.
 *
 * @since 3.5.14
 *
 * @return array<string, array<string, mixed>>
 */
function mh_product_catalog_entries(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = [];
    $path = get_theme_file_path('resources/data/product-catalog.json');
    if (is_readable($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        $catalog = is_array($decoded) ? $decoded : [];
    }

    return $catalog;
}

/**
 * Read a product's entry from product-catalog.json by project post ID.
 *
 * Resolution: the project post slug, with a few legacy aliases.
 *
 * @since 3.2.0
 *
 * @param  int  $product_id  Project post ID.
 * @return array<string, mixed> Catalog entry, or empty array when not found.
 */
function mh_product_catalog_data(int $product_id): array
{
    if ($product_id <= 0) {
        return [];
    }

    $catalog = mh_product_catalog_entries();

    $aliases = [
        'wordpress-theme-real-estate-agents' => 'acreline',
        'real-estate-wordpress-theme-acreline' => 'acreline',
        'wordpress-tour-theme-walkridge' => 'walkridge',
        'walkridge-tour-wordpress-theme' => 'walkridge',
        'acreline-real-estate-wordpress-theme' => 'acreline',
        'tocflow' => 'tocguide',
    ];

    $resolve = static function (string $slug) use ($catalog, $aliases): array {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return [];
        }
        if (isset($catalog[$slug]) && is_array($catalog[$slug])) {
            return $catalog[$slug];
        }
        $mapped = $aliases[$slug] ?? '';
        if ($mapped !== '' && isset($catalog[$mapped]) && is_array($catalog[$mapped])) {
            return $catalog[$mapped];
        }

        return [];
    };

    // The project post's slug is the catalog key (with a few legacy aliases).
    $productPost = get_post($product_id);
    if ($productPost instanceof \WP_Post) {
        $entry = $resolve((string) $productPost->post_name);
        if ($entry !== []) {
            return $entry;
        }
        if (stripos((string) $productPost->post_title, 'Acreline') !== false && isset($catalog['acreline']) && is_array($catalog['acreline'])) {
            return $catalog['acreline'];
        }
    }

    return [];
}

/**
 * Full product entry for a project.
 *
 * Merges `product-catalog.json` base data with any `_mh_project_*` meta saved
 * directly on the project post. Meta values take precedence over the catalog
 * so admins can fine-tune content without touching the JSON file.
 *
 * Replaces direct `mh_product_catalog_data()` calls in Blade templates wherever
 * project-specific overrides need to be respected.
 *
 * @since 3.4.0
 *
 * @param  int  $product_id  Project post ID.
 * @return array<string, mixed> Merged product entry.
 */
function mh_product_entry(int $product_id): array
{
    $entry = mh_product_catalog_data($product_id);

    if ($product_id <= 0) {
        return $entry;
    }

    $entry['product_type'] = mh_resolve_product_type($product_id);

    $str = fn (string $key): string => trim((string) get_post_meta($product_id, '_mh_project_'.$key, true));

    // Scalar string fields.
    foreach (['eyebrow', 'summary', 'blurb', 'challenge', 'approach', 'result',
        'audience', 'architecture', 'handoff', 'demo', 'cat', 'place',
        'github', 'version', 'compatible', 'license', 'support', 'guarantee'] as $key) {
        $v = $str($key);
        if ($v !== '') {
            $entry[$key] = $v;
        }
    }

    // Line-based array fields.
    foreach (['benefits', 'deliverables', 'files_included'] as $key) {
        $raw = $str($key);
        if ($raw !== '') {
            $parsed = array_values(array_filter(array_map('trim', (array) preg_split('/\r\n|\r|\n/', $raw))));
            if ($parsed !== []) {
                $entry[$key] = $parsed;
            }
        }
    }

    // FAQ pairs stored as "Question|||Answer" per line.
    $faqRaw = $str('faq');
    if ($faqRaw !== '') {
        $pairs = [];
        foreach (array_filter(array_map('trim', (array) preg_split('/\r\n|\r|\n/', $faqRaw))) as $line) {
            if (! str_contains($line, '|||')) {
                continue;
            }
            [$q, $a] = array_map('trim', explode('|||', $line, 2));
            if ($q !== '' && $a !== '') {
                $pairs[] = [$q, $a];
            }
        }
        if ($pairs !== []) {
            $entry['faq'] = $pairs;
        }
    }

    // Docs links stored as "Label|||URL" per line.
    $docsRaw = $str('docs');
    if ($docsRaw !== '') {
        $links = [];
        foreach (array_filter(array_map('trim', (array) preg_split('/\r\n|\r|\n/', $docsRaw))) as $line) {
            if (! str_contains($line, '|||')) {
                continue;
            }
            [$label, $url] = array_map('trim', explode('|||', $line, 2));
            if ($label !== '' && $url !== '') {
                $links[] = [$label, $url];
            }
        }
        if ($links !== []) {
            $entry['docs'] = $links;
        }
    }

    // Tech tags stored as comma-separated string.
    $techRaw = $str('tech');
    if ($techRaw !== '') {
        $tags = array_values(array_filter(array_map('trim', explode(',', $techRaw))));
        if ($tags !== []) {
            $entry['tech'] = $tags;
        }
    }

    // Metrics: up to three [value, label] pairs from _mh_project_m{N}_value/label.
    $catalogMetrics = is_array($entry['metrics'] ?? null) ? $entry['metrics'] : [];
    for ($i = 1; $i <= 3; $i++) {
        $v = trim((string) get_post_meta($product_id, "_mh_project_m{$i}_value", true));
        $l = trim((string) get_post_meta($product_id, "_mh_project_m{$i}_label", true));
        if ($v !== '' || $l !== '') {
            $catalogMetrics[$i - 1] = [$v, $l];
        }
    }
    if ($catalogMetrics !== []) {
        $entry['metrics'] = array_values($catalogMetrics);
    }

    return $entry;
}

/** Default USD price for a project theme when none is set on the project. */
function mh_default_theme_price(): string
{
    return '79';
}

/**
 * Whether a project is explicitly marked for sale (opt-in).
 * Empty meta means not for sale — concept demos stay hire-only.
 */
function mh_project_is_for_sale(int $project_id): bool
{
    return $project_id > 0 && get_post_meta($project_id, '_mh_project_for_sale', true) === '1';
}

/**
 * Home Work case studies: Acreline, WalkRidge, TOCflow when present.
 *
 * @param  list<array<string, mixed>>  $cards
 * @return list<array<string, mixed>>
 */
function mh_home_case_study_cards(array $cards, int $limit = 3): array
{
    $want = ['acreline', 'walkridge', 'tocguide', 'tocflow'];
    $bySlug = [];
    foreach ($cards as $card) {
        $slug = sanitize_title((string) ($card['slug'] ?? ''));
        if ($slug === '' || isset($bySlug[$slug])) {
            continue;
        }
        $bySlug[$slug] = $card;
    }

    $out = [];
    foreach ($want as $slug) {
        if (isset($bySlug[$slug])) {
            $out[] = $bySlug[$slug];
            unset($bySlug[$slug]);
        }
    }

    if (count($out) < $limit) {
        foreach ($bySlug as $card) {
            $out[] = $card;
            if (count($out) >= $limit) {
                break;
            }
        }
    }

    if (count($out) < $limit) {
        foreach ($cards as $card) {
            $slug = sanitize_title((string) ($card['slug'] ?? ''));
            $already = false;
            foreach ($out as $picked) {
                if (sanitize_title((string) ($picked['slug'] ?? '')) === $slug) {
                    $already = true;
                    break;
                }
            }
            if ($already) {
                continue;
            }
            $out[] = $card;
            if (count($out) >= $limit) {
                break;
            }
        }
    }

    return array_slice($out, 0, max(1, $limit));
}

/**
 * Contact form URL for a project (Get help).
 *
 * @param  array<string, mixed>  $project
 */
function mh_work_help_url(array $project): string
{
    $slug = sanitize_title((string) ($project['slug'] ?? ''));

    return add_query_arg([
        'project' => $slug,
        'who' => 'business',
        'intent' => 'help',
    ], home_url('/contact/'));
}

/**
 * Direct download URL for a free catalog product (price 0) that ships as a fixed GitHub release asset.
 *
 * No cart, no login: the link points at the public release, e.g. TOCguide's tocguide.zip. Returns
 * an empty string for paid products or when the catalog has no fixed repo and asset name.
 */
function mh_project_free_download_url(string $slug): string
{
    $entry = mh_product_catalog_seed_data()[sanitize_key($slug)] ?? null;
    if (! is_array($entry) || (float) ($entry['price'] ?? 1) > 0) {
        return '';
    }

    $download = is_array($entry['download'] ?? null) ? $entry['download'] : [];
    $repo = trim((string) ($download['github_repo'] ?? ''));
    $asset = trim((string) ($download['asset'] ?? ''));
    if (! preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo) || ! preg_match('/^[A-Za-z0-9_.-]+\.zip$/', $asset)) {
        return '';
    }

    return 'https://github.com/'.$repo.'/releases/latest/download/'.$asset;
}

/** True for catalog products still waiting on their Freemius product: no Buy button yet. */
function mh_project_is_coming_soon(string $slug): bool
{
    $entry = mh_product_catalog_seed_data()[sanitize_key($slug)] ?? null;

    return is_array($entry) && ! empty($entry['coming_soon']);
}

/**
 * Default heading ('h1'), intro ('lede') and steps heading ('how_h2') for a shop page. $scope is 'all' or a key from
 * mh_product_types(). The page fields (shop_{scope}_h1, shop_{scope}_lede) override these.
 */
function mh_shop_copy_default(string $scope, string $part): string
{
    // Parts that are the same on every shop page.
    $shared = [
        'avail_h2' => __('Available now', 'sage'),
        'avail_intro' => __('Ready to use today. Each one has a live demo or docs you can open first.', 'sage'),
        'soon_h2' => __('Coming soon', 'sage'),
        'soon_intro' => __('They have live demos. I am finishing licensing, then they go on sale. Want an email when they do?', 'sage'),
        'compare_h2' => __('Compare the themes', 'sage'),
        'compare_intro' => __('Compare the themes side by side, then open a demo and click through one.', 'sage'),
        'cta_h2' => __('Need something that is not here?', 'sage'),
        'cta_text' => __('I build custom WordPress themes and plugins for shops and agencies. Tell me what you need and I will write back.', 'sage'),
        'cta_label' => __('Say hello', 'sage'),
        'cta_eyebrow' => __('Custom work', 'sage'),
        'avail_eyebrow' => __('Ready to use', 'sage'),
        'soon_eyebrow' => __('Next up', 'sage'),
        'compare_eyebrow' => __('Side by side', 'sage'),
        'how_eyebrow' => __('How it works', 'sage'),
        'faq_eyebrow' => __('Questions', 'sage'),
        'faq_h2' => __('Quick answers', 'sage'),
        'help_h3' => __('Still have a question?', 'sage'),
        'help_text' => __('Ask me and I will write back. I reply within one business day (ET).', 'sage'),
        'help_label' => __('Ask me directly', 'sage'),
        'share_eyebrow' => __('Docs and sharing', 'sage'),
        'share_h2' => __('Read first, then share', 'sage'),
        'share_intro' => __('Guides for each product, and an easy way to pass this page to a teammate or a client.', 'sage'),
        'docs_h3' => __('Read first', 'sage'),
        'share_h3' => __('Share this page', 'sage'),
    ];

    $copy = [
        'all' => [
            'h1' => __('WordPress themes and plugins I build and support', 'sage'),
            'lede' => __('I build these so the people who own the site can run it. Open a live demo, pick a yearly license, and get updates and email support from the developer who wrote the code. Plugins are free.', 'sage'),
            'primary' => __('See what is available', 'sage'),
            'how_h2' => __('How buying works', 'sage'),
            'faq_intro' => __('Short answers about licenses, support, and refunds.', 'sage'),
        ],
        'theme' => [
            'h1' => __('WordPress themes you can run yourself', 'sage'),
            'lede' => __('Block themes with a live demo and an owner guide for each. A yearly license covers updates and email support, and there is a 30-day refund if I cannot fix a problem.', 'sage'),
            'primary' => __('See the themes', 'sage'),
            'how_h2' => __('How buying works', 'sage'),
            'faq_intro' => __('Short answers about licenses, support, and refunds.', 'sage'),
        ],
        'plugin' => [
            'h1' => __('Free WordPress plugins', 'sage'),
            'lede' => __('Free plugins I build and maintain. Download the zip, upload it in WordPress, and read the docs first.', 'sage'),
            'primary' => __('See the plugins', 'sage'),
            'how_h2' => __('How to get a plugin', 'sage'),
            'faq_intro' => __('Short answers about downloading, data, and getting help.', 'sage'),
        ],
    ];
    if (isset($copy[$scope][$part])) {
        return $copy[$scope][$part];
    }
    if (isset($shared[$part])) {
        return $shared[$part];
    }

    $plural = (string) (mh_product_types()[$scope]['plural'] ?? '');

    /* translators: %s: plural product type, lower case, e.g. documents */
    return $part === 'h1' ? $plural : sprintf(__('Browse the %s I offer.', 'sage'), strtolower($plural));
}

/**
 * Default steps for the shop pages (HTML list). The plugins page gets a short "how to get it" list;
 * 'theme' gets the buying steps only, and 'all' adds a line about free plugins.
 */
function mh_shop_how_default(string $scope = 'all'): string
{
    if ($scope === 'plugin') {
        return '<ol><li>'.implode('</li><li>', [
            __('Download the zip from the plugin page. There is nothing to buy.', 'sage'),
            __('In WordPress, go to Plugins, then Add Plugin, then Upload Plugin, and choose the zip.', 'sage'),
            __('Activate it. The docs linked on the plugin page cover setup.', 'sage'),
        ]).'</li></ol>';
    }

    $steps = [
        __('Pick a plan on the theme page. Checkout opens right there, and Freemius takes the payment.', 'sage'),
        __('Your receipt, license key, and download arrive by email. Your account page keeps them.', 'sage'),
        __('Updates and email support run for the year you paid for. If you do not renew, the theme keeps working; updates and support stop.', 'sage'),
        __('If I cannot fix a bug or problem, you get a refund within 30 days.', 'sage'),
    ];
    // The plugin line only belongs on pages that list plugins ('all'); the themes page leaves it out.
    if ($scope !== 'theme') {
        $steps[] = __('Plugins are free. Download the zip from the plugin page.', 'sage');
    }

    return '<ol><li>'.implode('</li><li>', $steps).'</li></ol>';
}

/**
 * Default trust row for a shop page: a short HTML list of things that are true of every product shown.
 * The plugins page gets its own list.
 */
function mh_shop_trust_default(string $scope = 'all'): string
{
    $items = $scope === 'plugin'
        ? [
            __('Free, no account', 'sage'),
            __('GPLv2 or later code', 'sage'),
            __('Docs linked on the plugin page', 'sage'),
        ]
        : [
            __('A live demo for every theme', 'sage'),
            __('30-day refund if I cannot fix a problem', 'sage'),
            __('Updates and email support for the year', 'sage'),
            __('GPLv2 or later code', 'sage'),
        ];

    return '<ul><li>'.implode('</li><li>', $items).'</li></ul>';
}

/**
 * Default questions and answers for a shop page. The plugins page gets its own short set;
 * every other scope ('all', 'theme') shares the theme questions.
 *
 * @return list<array{0: string, 1: string}> Question, answer.
 */
function mh_shop_faq_defaults(string $scope = 'all'): array
{
    if ($scope === 'plugin') {
        return [
            [__('Are the plugins really free?', 'sage'), __('Yes. Download the zip from the plugin page. There is nothing to buy and no account to make.', 'sage')],
            [__('Where do I get help?', 'sage'), __('Start with the docs linked on the plugin page. For a bug, or a question the docs do not answer, write to hello@matthummel.com.', 'sage')],
            [__('Do the plugins collect data?', 'sage'), __('TOCguide does not. It has no accounts and no tracking.', 'sage')],
        ];
    }

    return [
        [__('Can I try a theme before I buy?', 'sage'), __('Yes. Theme pages link to a live demo where there is one. Cobble & Candle has three demos you can click through.', 'sage')],
        [__('What is a block theme?', 'sage'), __('A block theme is built with the WordPress block editor and site editor. You change layouts and styles inside WordPress, without editing code and without a page builder plugin.', 'sage')],
        [__('What does a license cover?', 'sage'), __('A yearly license covers updates and email support for the number of sites on your plan: one site, five sites, or unlimited. The theme keeps working on your site if you do not renew.', 'sage')],
        [__('What happens when my license expires?', 'sage'), __('The theme keeps working on your site. You stop getting updates and email support until you renew.', 'sage')],
        [__('Can I use a theme on client sites?', 'sage'), __('For Cobble & Candle, the 5-site and unlimited-site plans are meant for that. The unlimited plan is white-label, so you can ship it under your own name.', 'sage')],
        [__('Who do I ask if something breaks?', 'sage'), __('Write to hello@matthummel.com. Support is by email, and I reply within one business day (ET).', 'sage')],
    ];
}

/**
 * Rows for the shop listing: live products of any known type (see mh_product_types), each with a status the card can show.
 *
 * status: buy (Freemius plans), free (direct download), soon (not for sale yet), details (anything else).
 * Prices come from the catalog; nothing is invented here.
 *
 * @param  string  $type  'all' or a key from mh_product_types().
 * @return list<array{slug: string, title: string, blurb: string, url: string, image: string, demo: string, type: string, status: string, price: string, free_url: string, place: string, facts: list<string>, points: list<string>, version: string, works_with: string, docs_url: string, docs_label: string, docs: list<array{0: string, 1: string}>, source_url: string}>
 */
function mh_shop_items(string $type = 'all'): array
{
    $catalog = mh_product_catalog_seed_data();
    $items = [];
    foreach (mh_query_project_cards(['live_only' => true]) as $card) {
        $slug = (string) ($card['slug'] ?? '');
        $kind = (string) ($card['product_type'] ?? '');
        if ($slug === '' || ! isset(mh_product_types()[$kind]) || ($type !== 'all' && $kind !== $type)) {
            continue;
        }

        $status = 'details';
        $price = '';
        $freeUrl = mh_project_free_download_url($slug);
        $fs = mh_project_freemius($slug);
        if ($fs !== null) {
            $status = 'buy';
            $lowest = null;
            foreach ($fs['tiers'] as $tier) {
                $amount = (float) preg_replace('/[^0-9.]/', '', $tier['price']);
                if ($amount > 0 && ($lowest === null || $amount < $lowest['amount'])) {
                    $lowest = ['amount' => $amount, 'label' => $tier['price']];
                }
            }
            if ($lowest !== null) {
                /* translators: %s: lowest yearly price, e.g. $59 */
                $price = sprintf(__('From %s per year', 'sage'), $lowest['label']);
            }
        } elseif ($freeUrl !== '') {
            $status = 'free';
            $price = __('Free', 'sage');
        } elseif (mh_project_is_coming_soon($slug)) {
            $status = 'soon';
            $price = __('Coming soon', 'sage');
        }

        $entry = is_array($catalog[$slug] ?? null) ? $catalog[$slug] : [];

        // Two short facts from the catalog metrics (block counts and the like). A release number is skipped:
        // the version shows on its own line.
        $facts = [];
        foreach ((array) ($entry['metrics'] ?? []) as $metric) {
            $value = trim((string) ($metric[0] ?? ''));
            $label = trim((string) ($metric[1] ?? ''));
            if ($value === '' || preg_match('/release/i', $label)) {
                continue;
            }
            $facts[] = trim($value.' '.$label);
            if (count($facts) === 2) {
                break;
            }
        }

        $points = array_slice(array_values(array_filter(array_map(
            static fn ($point): string => trim((string) $point),
            (array) ($entry['benefits'] ?? [])
        ))), 0, 2);

        // Quality stats buyers look for (accessibility, speed scores and the like). Only what the catalog states:
        // a value and a short label each, at most four.
        $quality = [];
        foreach ((array) ($entry['quality'] ?? []) as $stat) {
            $statValue = trim((string) ($stat[0] ?? ''));
            $statLabel = trim((string) ($stat[1] ?? ''));
            if ($statValue !== '' && $statLabel !== '') {
                $quality[] = [$statValue, $statLabel];
            }
        }
        $quality = array_slice($quality, 0, 4);

        $docsUrl = '';
        $docsLabel = '';
        $docs = [];
        foreach ((array) ($entry['docs'] ?? []) as $doc) {
            if (is_array($doc) && str_starts_with((string) ($doc[1] ?? ''), 'http') && trim((string) ($doc[0] ?? '')) !== '') {
                $docs[] = [trim((string) $doc[0]), (string) $doc[1]];
            }
        }
        // The card's own demo link already opens this page (TOCguide's docs site is its demo), so
        // the card's docs link is the first docs entry that goes somewhere else.
        foreach ($docs as [$label, $url]) {
            if (untrailingslashit($url) !== untrailingslashit((string) ($card['demo'] ?? ''))) {
                [$docsLabel, $docsUrl] = [$label, $url];
                break;
            }
        }

        $source = (string) ($entry['github'] ?? '');

        $items[] = [
            'slug' => $slug,
            'title' => (string) ($card['title'] ?? ''),
            'blurb' => (string) ($card['blurb'] ?? ''),
            'url' => (string) ($card['url'] ?? ''),
            'image' => (string) ($card['image'] ?? ''),
            'demo' => (string) ($card['demo'] ?? ''),
            'type' => $kind,
            'status' => $status,
            'price' => $price,
            'free_url' => $freeUrl,
            'place' => trim((string) ($entry['place'] ?? '')),
            'facts' => $facts,
            'points' => $points,
            'quality' => $quality,
            'version' => trim((string) ($entry['version'] ?? '')),
            'works_with' => trim((string) preg_replace('/\s*\([^)]*\)/', '', (string) ($entry['compatible'] ?? ''))),
            'docs_url' => $docsUrl,
            'docs_label' => $docsLabel,
            'docs' => $docs,
            'source_url' => str_starts_with($source, 'http') ? $source : '',
        ];
    }

    $rank = ['buy' => 0, 'free' => 1, 'soon' => 2, 'details' => 3];
    usort($items, static fn (array $a, array $b): int => $rank[$a['status']] <=> $rank[$b['status']]);

    return $items;
}

/**
 * JSON-LD for a shop page: a CollectionPage with an ItemList of the products shown, plus a FAQPage that
 * matches the visible questions. Returns an empty string when there is nothing to describe.
 *
 * @param  list<array<string, mixed>>  $items  Rows from mh_shop_items().
 * @param  list<array{0: string, 1: string}>  $faq  Visible question and answer pairs.
 */
function mh_shop_jsonld(array $items, array $faq, string $name, string $description): string
{
    $url = (string) get_permalink();
    if ($url === '') {
        return '';
    }

    $page = [
        '@type' => 'CollectionPage',
        '@id' => $url.'#collection',
        'url' => $url,
        'name' => wp_strip_all_tags($name),
        'description' => wp_strip_all_tags($description),
    ];
    if ($items !== []) {
        $list = [];
        foreach (array_values($items) as $i => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => (string) $item['url'],
                'name' => wp_strip_all_tags((string) $item['title']),
            ];
        }
        $page['mainEntity'] = ['@type' => 'ItemList', 'numberOfItems' => count($list), 'itemListElement' => $list];
    }
    $graph = [$page];

    $questions = [];
    foreach ($faq as [$question, $answer]) {
        $questions[] = [
            '@type' => 'Question',
            'name' => wp_strip_all_tags((string) $question),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags((string) $answer)],
        ];
    }
    if ($questions !== []) {
        $graph[] = ['@type' => 'FAQPage', '@id' => $url.'#faq', 'mainEntity' => $questions];
    }

    return (string) wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
}

/**
 * Freemius checkout settings for a catalog product, or null when it is not sold through Freemius.
 *
 * Freemius is the merchant of record, so these products skip WooCommerce entirely. The sandbox
 * token and ctx come from wp-config constants (never the repo) and only exist on staging.
 *
 * @return array{store_id: string, product_id: string, plan_id: string, public_key: string, account_url: string, tiers: list<array{label: string, licenses: int, price: string}>, sandbox: array{token: string, ctx: string}|null}|null
 */
function mh_project_freemius(string $slug): ?array
{
    $entry = mh_product_catalog_seed_data()[sanitize_key($slug)]['freemius'] ?? null;
    if (! is_array($entry)) {
        return null;
    }

    $productId = preg_replace('/\D/', '', (string) ($entry['product_id'] ?? ''));
    $planId = preg_replace('/\D/', '', (string) ($entry['plan_id'] ?? ''));
    $publicKey = (string) ($entry['public_key'] ?? '');
    if ($productId === '' || $planId === '' || ! str_starts_with($publicKey, 'pk_')) {
        return null;
    }

    $tiers = [];
    foreach ((array) ($entry['tiers'] ?? []) as $tier) {
        if (! is_array($tier) || empty($tier['label'])) {
            continue;
        }
        $tiers[] = [
            'label' => sanitize_text_field((string) $tier['label']),
            'licenses' => max(0, (int) ($tier['licenses'] ?? 1)),
            'price' => sanitize_text_field((string) ($tier['price'] ?? '')),
        ];
    }
    if ($tiers === []) {
        return null;
    }

    $sandbox = null;
    // Never on the live domain, even if the constants are copied over by a staging publish.
    $isLive = in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['matthummel.com', 'www.matthummel.com'], true);
    if (! $isLive && defined('MH_FREEMIUS_SANDBOX_TOKEN') && defined('MH_FREEMIUS_SANDBOX_CTX')) {
        $sandbox = [
            'token' => (string) constant('MH_FREEMIUS_SANDBOX_TOKEN'),
            'ctx' => (string) constant('MH_FREEMIUS_SANDBOX_CTX'),
        ];
    }

    return [
        'store_id' => preg_replace('/\D/', '', (string) ($entry['store_id'] ?? '')),
        'product_id' => $productId,
        'plan_id' => $planId,
        'public_key' => $publicKey,
        'account_url' => home_url('/account/'),
        'tiers' => $tiers,
        'sandbox' => $sandbox,
    ];
}

/** Allowed public product types for shop / product UI. */
function mh_product_type_keys(): array
{
    return ['theme', 'plugin', 'app', 'service'];
}

/**
 * Resolve a product's public type from meta, catalog, or Services category.
 */
function mh_resolve_product_type(int $product_id): string
{
    if ($product_id <= 0) {
        return 'theme';
    }

    $allowed = mh_product_type_keys();

    $meta = sanitize_key((string) get_post_meta($product_id, '_mh_project_product_type', true));
    if (in_array($meta, $allowed, true)) {
        return $meta;
    }

    $entry = mh_product_catalog_data($product_id);
    $fromEntry = sanitize_key((string) ($entry['product_type'] ?? ''));
    if (in_array($fromEntry, $allowed, true)) {
        return $fromEntry;
    }

    return 'theme';
}

/**
 * Chrome copy and icon for a product type.
 *
 * @return array{eyebrow: string, buy: string, buy_free: string, icon: string, seo: string, short: string}
 */
function mh_product_type_chrome(string $type): array
{
    return match ($type) {
        'plugin' => [
            'eyebrow' => __('WordPress plugin', 'sage'),
            'buy' => __('Buy plugin', 'sage'),
            'buy_free' => __('Download plugin — free', 'sage'),
            'icon' => 'plugins',
            'seo' => __('WordPress Plugin', 'sage'),
            'short' => __('Plugin', 'sage'),
        ],
        'app' => [
            'eyebrow' => __('Web app', 'sage'),
            'buy' => __('Buy app', 'sage'),
            'buy_free' => __('Get app — free', 'sage'),
            'icon' => 'globe',
            'seo' => __('Web App', 'sage'),
            'short' => __('App', 'sage'),
        ],
        'service' => [
            'eyebrow' => __('Service', 'sage'),
            'buy' => __('Buy service', 'sage'),
            'buy_free' => __('Get started — free', 'sage'),
            'icon' => 'briefcase',
            'seo' => __('Service', 'sage'),
            'short' => __('Service', 'sage'),
        ],
        default => [
            'eyebrow' => __('WordPress theme', 'sage'),
            'buy' => __('Buy theme', 'sage'),
            'buy_free' => __('Get theme — free', 'sage'),
            'icon' => 'wordpress',
            'seo' => __('WordPress Theme', 'sage'),
            'short' => __('Theme', 'sage'),
        ],
    };
}

/**
 * One-line install guarantee for paid digital products.
 *
 * Catalog `guarantee` wins when set. Empty for free products and services.
 *
 * @since 3.5.26
 *
 * @param  array<string, mixed>  $entry
 */
function mh_product_guarantee_copy(array $entry = [], bool $is_free = false, bool $is_service = false): string
{
    if ($is_free || $is_service) {
        return '';
    }

    $custom = trim((string) ($entry['guarantee'] ?? ''));
    if ($custom !== '') {
        return $custom;
    }

    return __('Download today. If the zip won’t install on WP 6.6+/PHP 8.3, I’ll make it right.', 'sage');
}

/**
 * Soft install-help label next to Add to cart.
 *
 * @since 3.5.26
 */
function mh_product_install_help_copy(): string
{
    return __('Need it installed & branded? Get help', 'sage');
}

/**
 * Theme-relative path under resources/images/ when $src is a catalog path
 * or a baked theme URL (any host/port). Empty for truly external URLs.
 */
function mh_product_theme_image_rel(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }

    $rel = '';
    if (preg_match('#^(https?:)?//#i', $src) === 1) {
        $path = rawurldecode((string) (parse_url($src, PHP_URL_PATH) ?? ''));
        if (preg_match('#/themes/[^/]+/resources/images/(.+)$#', $path, $m) === 1) {
            $rel = $m[1];
        }
    } else {
        $rel = ltrim($src, '/');
        if (str_starts_with($rel, 'resources/images/')) {
            $rel = substr($rel, strlen('resources/images/'));
        }
    }

    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || str_contains($rel, '..')) {
        return '';
    }

    return $rel;
}

/** Turn a catalog path or absolute URL into a public image URL. */
function mh_product_media_url(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }

    $rel = mh_product_theme_image_rel($src);
    if ($rel !== '') {
        return get_theme_file_uri('resources/images/'.$rel);
    }

    if (preg_match('#^(https?:)?//#i', $src) === 1) {
        return $src;
    }

    return get_theme_file_uri('resources/images/'.ltrim($src, '/'));
}

/** Primary buy label for a project (theme vs plugin). */
function mh_project_buy_label(int $project_id): string
{
    if ($project_id <= 0) {
        return __('Buy theme', 'sage');
    }

    $type = mh_project_product_type($project_id);
    $chrome = mh_product_type_chrome($type === 'concept' ? 'theme' : $type);

    return $chrome['buy'];
}
