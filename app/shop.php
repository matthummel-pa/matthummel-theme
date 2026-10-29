<?php

/**
 * WooCommerce digital product shop helpers (themes & plugins).
 */

namespace App;

/**
 * Whether WooCommerce is available for product landings.
 */
function mh_shop_ready(): bool
{
    return class_exists('\WooCommerce') && function_exists('wc_get_product');
}

/**
 * Whether the public storefront (cart, prices, buy CTAs) is shown.
 *
 * WooCommerce stays installed. Public listing is the Projects CPT.
 */
function mh_public_shop_enabled(): bool
{
    return false;
}

/** Public Projects listing URL. */
function mh_work_listing_url(): string
{
    return home_url('/projects/');
}

/**
 * Absolute add-to-cart URL for a product (skips single product page).
 */
function mh_product_add_to_cart_url(int $product_id): string
{
    if ($product_id <= 0 || ! function_exists('wc_get_cart_url')) {
        return '';
    }

    return add_query_arg('add-to-cart', $product_id, wc_get_cart_url());
}

/**
 * Normalized product payload for concept landings.
 *
 * @return array{
 *   id: int,
 *   name: string,
 *   price: string,
 *   price_html: string,
 *   regular_price: string,
 *   is_free: bool,
 *   purchasable: bool,
 *   permalink: string,
 *   add_to_cart_url: string,
 *   short_description: string
 * }|null
 */
function mh_shop_product_payload(int $product_id): ?array
{
    if ($product_id <= 0 || ! mh_shop_ready()) {
        return null;
    }

    $product = wc_get_product($product_id);
    if (! $product || $product->get_status() !== 'publish') {
        return null;
    }

    $price = (string) $product->get_price();
    $is_free = $price === '' || (float) $price <= 0.0;

    return [
        'id' => $product_id,
        'name' => $product->get_name(),
        'price' => $price,
        'price_html' => $product->get_price_html(),
        'regular_price' => (string) $product->get_regular_price(),
        'is_free' => $is_free,
        'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
        'permalink' => mh_product_landing_url($product_id) ?: $product->get_permalink(),
        'add_to_cart_url' => mh_product_add_to_cart_url($product_id),
        'short_description' => wp_strip_all_tags((string) $product->get_short_description()),
    ];
}

/**
 * Published WooCommerce product ID for a permalink slug.
 */
function mh_product_id_by_slug(string $slug): int
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return 0;
    }

    $found = get_posts([
        'name' => $slug,
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    return $found !== [] ? (int) $found[0] : 0;
}

/**
 * Project ID linked to a WooCommerce product (0 when unset).
 */
function mh_product_project_id(int $product_id): int
{
    if ($product_id <= 0) {
        return 0;
    }

    return max(0, (int) get_post_meta($product_id, '_mh_product_project_id', true));
}

/**
 * Public landing URL for a product — the linked project page when available.
 */
function mh_product_landing_url(int $product_id): string
{
    if (! post_type_exists(mh_project_post_type())) {
        return '';
    }

    $project_id = mh_product_project_id($product_id);
    if ($project_id <= 0) {
        return '';
    }

    $project = get_post($project_id);
    if (! $project instanceof \WP_Post || $project->post_status !== 'publish') {
        return '';
    }

    $url = get_permalink($project_id);

    return is_string($url) ? $url : '';
}

/**
 * Declare WooCommerce theme support + hide shipping UI for digital catalog.
 */
function mh_shop_theme_support(): void
{
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}

add_action('after_setup_theme', __NAMESPACE__.'\\mh_shop_theme_support', 25);

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
 * Read a product's entry from product-catalog.json by WooCommerce product ID.
 *
 * Resolution order: linked project post slug → SKU prefix-stripped slug.
 *
 * @since 3.2.0
 *
 * @param  int  $product_id  WooCommerce product post ID.
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

    // Try linked project slug first.
    $project_id = mh_product_project_id($product_id);
    if ($project_id > 0) {
        $post = get_post($project_id);
        if ($post instanceof \WP_Post) {
            $entry = $resolve((string) $post->post_name);
            if ($entry !== []) {
                return $entry;
            }
            if (stripos((string) $post->post_title, 'Acreline') !== false && isset($catalog['acreline']) && is_array($catalog['acreline'])) {
                return $catalog['acreline'];
            }
        }
    }

    // Product post slug (SEO-friendly marketplace slugs).
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

    // Fallback: strip theme-/plugin- prefix from SKU.
    if (mh_shop_ready() && function_exists('wc_get_product')) {
        $wc = wc_get_product($product_id);
        if ($wc instanceof \WC_Product) {
            $entry = $resolve((string) preg_replace('/^(theme|plugin)-/', '', (string) $wc->get_sku()));
            if ($entry !== []) {
                return $entry;
            }
        }
    }

    return [];
}

/**
 * Build rich HTML long-description for a product from its catalog entry.
 *
 * Used during WooCommerce product sync so Rank Math / Yoast score the
 * product page against substantial, keyword-dense content rather than a
 * one-sentence blurb.
 *
 * @since 3.2.0
 *
 * @param  array<string, mixed>  $entry  Catalog JSON entry for the product.
 * @return string Safe HTML ready for set_description().
 */
function mh_product_description_html(array $entry): string
{
    $parts = [];

    $summary = trim((string) ($entry['summary'] ?? ''));
    if ($summary !== '') {
        $parts[] = '<p>'.esc_html($summary).'</p>';
    }

    $challenge = trim((string) ($entry['challenge'] ?? ''));
    if ($challenge !== '') {
        $parts[] = '<h2>'.esc_html__('The problem it solves', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($challenge).'</p>';
    }

    $approach = trim((string) ($entry['approach'] ?? ''));
    if ($approach !== '') {
        $parts[] = '<h2>'.esc_html__('How it works', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($approach).'</p>';
    }

    $result = trim((string) ($entry['result'] ?? ''));
    if ($result !== '') {
        $parts[] = '<h2>'.esc_html__('What you get', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($result).'</p>';
    }

    $benefits = $entry['benefits'] ?? [];
    if (is_array($benefits) && $benefits !== []) {
        $parts[] = '<h2>'.esc_html__('Key benefits', 'sage').'</h2><ul>';
        foreach ($benefits as $b) {
            $parts[] = '<li>'.esc_html((string) $b).'</li>';
        }
        $parts[] = '</ul>';
    }

    $deliverables = $entry['deliverables'] ?? [];
    if (is_array($deliverables) && $deliverables !== []) {
        $parts[] = '<h2>'.esc_html__("What's included", 'sage').'</h2><ul>';
        foreach ($deliverables as $d) {
            $parts[] = '<li>'.esc_html((string) $d).'</li>';
        }
        $parts[] = '</ul>';
    }

    $audience = trim((string) ($entry['audience'] ?? ''));
    if ($audience !== '') {
        $parts[] = '<h2>'.esc_html__('Who it is for', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($audience).'</p>';
    }

    $architecture = trim((string) ($entry['architecture'] ?? ''));
    if ($architecture !== '') {
        $parts[] = '<h2>'.esc_html__('Architecture', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($architecture).'</p>';
    }

    $handoff = trim((string) ($entry['handoff'] ?? ''));
    if ($handoff !== '') {
        $parts[] = '<h2>'.esc_html__('Handoff', 'sage').'</h2>';
        $parts[] = '<p>'.esc_html($handoff).'</p>';
    }

    return implode("\n", $parts);
}

/**
 * Full product entry for a WooCommerce product.
 *
 * Merges `product-catalog.json` base data with any `_mh_project_*` meta saved
 * directly on the WC product post. Meta values take precedence over the catalog
 * so admins can fine-tune content without touching the JSON file.
 *
 * Replaces direct `mh_product_catalog_data()` calls in Blade templates wherever
 * WC-product-specific overrides need to be respected.
 *
 * @since 3.4.0
 *
 * @param  int  $product_id  WooCommerce product post ID.
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

/** Default USD price for a project plugin when none is set (0 = free lead magnet). */
function mh_default_plugin_price(): string
{
    return '0';
}

/**
 * Whether a project is explicitly marked for sale (opt-in).
 * Empty meta means not for sale — concept demos stay hire-only.
 */
function mh_project_is_for_sale(int $project_id): bool
{
    return $project_id > 0 && get_post_meta($project_id, '_mh_project_for_sale', true) === '1';
}

/** Items in the WooCommerce cart (0 when the cart is not loaded). */
function mh_cart_count(): int
{
    if (! mh_shop_ready() || ! function_exists('WC')) {
        return 0;
    }

    $cart = WC()->cart;
    if (! $cart) {
        return 0;
    }

    return (int) $cart->get_cart_contents_count();
}

/**
 * Map a published WooCommerce product to the Work card array shape.
 *
 * Meta stored on the product (`_mh_project_*`) overrides catalog JSON defaults
 * so the admin can fine-tune per-product without touching the catalog file.
 *
 * @since 3.3.0
 *
 * @param  int  $product_id  WooCommerce product post ID.
 * @return array<string, mixed> Work card array, or empty when the product is invalid.
 */
function mh_wc_product_to_work_card(int $product_id): array
{
    if (! mh_shop_ready() || ! function_exists('wc_get_product')) {
        return [];
    }

    $wc = wc_get_product($product_id);
    if (! $wc instanceof \WC_Product) {
        return [];
    }

    $entry = mh_product_catalog_data($product_id);

    // Meta on the product overrides catalog JSON.
    $meta = fn (string $key, string $fallback = ''): string => (function () use ($product_id, $key, $fallback, $entry): string {
        $v = trim((string) get_post_meta($product_id, $key, true));

        return $v !== '' ? $v : (string) ($entry[ltrim($key, '_mh_project_')] ?? $fallback);
    })();

    $slug = (string) $wc->get_slug();
    $blurb = trim((string) get_post_meta($product_id, '_mh_project_blurb', true));
    if ($blurb === '') {
        $blurb = trim((string) ($entry['blurb'] ?? ''));
    }
    if ($blurb === '') {
        $blurb = trim(wp_strip_all_tags((string) $wc->get_short_description()));
    }

    $cat = trim((string) get_post_meta($product_id, '_mh_project_cat', true));
    if ($cat === '') {
        $cat = trim((string) ($entry['cat'] ?? ''));
    }

    $place = trim((string) get_post_meta($product_id, '_mh_project_place', true));
    if ($place === '') {
        $place = trim((string) ($entry['place'] ?? ''));
    }

    $techRaw = trim((string) get_post_meta($product_id, '_mh_project_tech', true));
    $tech = $techRaw !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $techRaw))))
        : (is_array($entry['tech'] ?? null) ? $entry['tech'] : []);

    $demo = trim((string) get_post_meta($product_id, '_mh_project_demo', true));
    if ($demo === '') {
        $demo = trim((string) ($entry['demo'] ?? ''));
    }

    $productType = mh_resolve_product_type($product_id);

    // Image: featured image first, then catalog screenshot.
    $image = '';
    if (has_post_thumbnail($product_id)) {
        $image = (string) wp_get_attachment_image_url((int) get_post_thumbnail_id($product_id), 'large');
    }
    if ($image === '') {
        $screenshots = is_array($entry['screenshots'] ?? null) ? $entry['screenshots'] : [];
        if ($screenshots !== []) {
            $imgRel = (string) ($screenshots[0][0] ?? '');
            if ($imgRel !== '') {
                $image = str_starts_with($imgRel, 'http') ? $imgRel : get_theme_file_uri('resources/images/'.$imgRel);
            }
        }
    }

    $isFree = (float) $wc->get_price() <= 0.0;
    $buyLabel = '';
    $priceLabel = '';
    $buyUrl = '';
    if (mh_public_shop_enabled()) {
        $buyLabel = mh_product_buy_copy($product_id, $isFree);
        $priceLabel = $isFree ? __('Free', 'sage') : ('$'.(string) $wc->get_regular_price());
        $buyUrl = mh_product_add_to_cart_url($product_id);
    }

    $productLink = (string) get_permalink($product_id);

    $github = trim((string) get_post_meta($product_id, '_mh_project_github', true));
    if ($github === '') {
        $github = trim((string) ($entry['github'] ?? ''));
    }

    $challenge = trim((string) get_post_meta($product_id, '_mh_project_challenge', true));
    if ($challenge === '') {
        $challenge = trim((string) ($entry['challenge'] ?? ''));
    }

    $approach = trim((string) get_post_meta($product_id, '_mh_project_approach', true));
    if ($approach === '') {
        $approach = trim((string) ($entry['approach'] ?? ''));
    }

    $forSale = get_post_meta($product_id, '_mh_project_for_sale', true) === '1'
        || (bool) ($entry['for_sale'] ?? false);

    $shotUrls = [];
    $catalogShots = is_array($entry['screenshots'] ?? null) ? $entry['screenshots'] : [];
    foreach (array_slice($catalogShots, 0, 4) as $shot) {
        $rel = is_array($shot) ? (string) ($shot[0] ?? '') : (string) $shot;
        if ($rel === '') {
            continue;
        }
        $shotUrls[] = str_starts_with($rel, 'http') ? $rel : get_theme_file_uri('resources/images/'.$rel);
    }
    if ($image !== '' && ($shotUrls === [] || $shotUrls[0] !== $image)) {
        array_unshift($shotUrls, $image);
        $shotUrls = array_values(array_unique($shotUrls));
    }

    $card = [
        'slug' => $slug,
        'title' => wp_specialchars_decode((string) $wc->get_name(), ENT_QUOTES),
        'cat' => $cat,
        'place' => $place,
        'blurb' => $blurb,
        'tech' => $tech,
        'concept' => $github,
        'github' => $github,
        'demo' => $demo,
        'url' => $productLink,
        'image' => $image,
        'screenshots' => array_slice($shotUrls, 0, 4),
        'challenge' => $challenge,
        'approach' => $approach,
        'for_sale' => $forSale,
        'is_concept' => ! $forSale,
        'post_id' => $product_id,
        'product_id' => $product_id,
        'product_type' => $productType,
        'buy_url' => $buyUrl,
        'buy_label' => $buyLabel,
        'price_label' => $priceLabel,
        'is_free' => $isFree,
    ];
    $card['help_url'] = function_exists(__NAMESPACE__.'\\mh_work_help_url') ? mh_work_help_url($card) : home_url('/contact/');

    return $card;
}

/**
 * All published WooCommerce products as Work card arrays, ordered by menu_order then title.
 *
 * Used by mh_work_page_items() after the project CPT is removed.
 *
 * @since 3.3.0
 *
 * @return list<array<string, mixed>>
 */
function mh_wc_products_for_work(): array
{
    if (! mh_shop_ready() || ! function_exists('wc_get_products')) {
        return [];
    }

    $ids = wc_get_products([
        'status' => 'publish',
        'limit' => -1,
        'return' => 'ids',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    $cards = [];
    foreach ($ids as $id) {
        $card = mh_wc_product_to_work_card((int) $id);
        if ($card !== []) {
            $cards[] = $card;
        }
    }

    return $cards;
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
 * Render the "Product fields" metabox on the WooCommerce product edit screen.
 *
 * All `_mh_project_*` fields that previously lived on the project CPT are now
 * stored directly on the WC product post, using the same meta keys. Catalog JSON
 * values are shown as placeholder text so the field can be left blank to inherit.
 *
 * @since 3.3.0
 */
function mh_wc_product_admin_meta_box(\WP_Post $post): void
{
    if ($post->post_type !== 'product') {
        return;
    }

    wp_nonce_field('mh_product_meta', 'mh_product_meta_nonce');

    $id = (int) $post->ID;
    $entry = mh_product_catalog_data($id);

    $g = fn (string $key): string => trim((string) get_post_meta($id, '_mh_project_'.$key, true));
    $ph = fn (string $key): string => trim((string) ($entry[$key] ?? ''));

    $cat = $g('cat');
    $place = $g('place');
    $blurb = $g('blurb');
    $tech = $g('tech');
    $demo = $g('demo');
    $eyebrow = $g('eyebrow');
    $summary = $g('summary');
    $challenge = $g('challenge');
    $approach = $g('approach');
    $result = $g('result');
    $deliverables = $g('deliverables');
    $benefits = $g('benefits');
    $faq = $g('faq');
    $audience = $g('audience');
    $architecture = $g('architecture');
    $handoff = $g('handoff');
    $github = $g('github');
    $version = $g('version');
    $compatible = $g('compatible');
    $license = $g('license');
    $filesIncl = $g('files_included');
    $docs = $g('docs');
    $support = $g('support');
    $productType = $g('product_type') ?: (string) ($entry['product_type'] ?? 'theme');
    $price = $g('price') ?: (string) ($entry['price'] ?? '');
    $forSale = get_post_meta($id, '_mh_project_for_sale', true) === '1'
        || ($entry['for_sale'] ?? false);
    $isLive = get_post_meta($id, mh_project_live_meta_key(), true) === '1'
        || ($entry['live'] ?? false);
    $image = $g('image');

    $metrics = [];
    for ($i = 1; $i <= 3; $i++) {
        $catalogMetric = is_array($entry['metrics'] ?? null) ? ($entry['metrics'][$i - 1] ?? []) : [];
        $metrics[$i] = [
            'value' => (string) get_post_meta($id, "_mh_project_m{$i}_value", true) ?: (string) ($catalogMetric[0] ?? ''),
            'label' => (string) get_post_meta($id, "_mh_project_m{$i}_label", true) ?: (string) ($catalogMetric[1] ?? ''),
        ];
    }

    $fieldRow = static function (string $label, string $name, string $value, string $placeholder = '', string $type = 'text'): void {
        $rows = in_array($name, ['mh_project_challenge', 'mh_project_approach', 'mh_project_result', 'mh_project_deliverables', 'mh_project_benefits', 'mh_project_faq'], true) ? 5 : 3;
        echo '<tr><th scope="row"><label for="'.esc_attr($name).'">'.esc_html($label).'</label></th><td>';
        if ($type === 'textarea') {
            printf(
                '<textarea class="large-text" rows="%4$d" id="%1$s" name="%1$s" placeholder="%2$s">%3$s</textarea>',
                esc_attr($name),
                esc_attr($placeholder),
                esc_textarea($value),
                $rows
            );
        } else {
            printf(
                '<input class="large-text" type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s">',
                esc_attr($type === 'url' ? 'url' : 'text'),
                esc_attr($name),
                esc_attr($value),
                esc_attr($placeholder)
            );
        }
        echo '</td></tr>';
    };

    echo '<p><small>'.esc_html__('Leave a field blank to inherit from product-catalog.json. Values here override the catalog.', 'sage').'</small></p>';
    mh_wc_product_download_admin_notice($id, $forSale, $productType);

    echo '<p><label><input type="checkbox" name="mh_project_live" value="1" '.checked($isLive, true, false).'> ';
    echo '<strong>'.esc_html__('Show on site (work grid + shop)', 'sage').'</strong></label></p>';

    echo '<p><label><input type="checkbox" name="mh_project_for_sale" value="1" '.checked($forSale, true, false).'> ';
    echo esc_html__('For sale (Buy theme / plugin button)', 'sage').'</label></p>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Work card', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    $fieldRow(__('Category', 'sage'), 'mh_project_cat', $cat, $ph('cat') ?: 'Themes, Plugins…');
    $fieldRow(__('Place', 'sage'), 'mh_project_place', $place, $ph('place') ?: 'Real estate agencies · Land & farms');
    $fieldRow(__('Card blurb', 'sage'), 'mh_project_blurb', $blurb, $ph('blurb'), 'textarea');
    $fieldRow(__('Tech (comma-separated)', 'sage'), 'mh_project_tech', $tech, $ph('tech') ?: 'Sage, WordPress, Tailwind');
    $fieldRow(__('Screenshot file or URL', 'sage'), 'mh_project_image', $image, __('Catalog screenshot on the Projects grid. Path: products/acreline/featured.webp or full URL. Featured image is fallback only.', 'sage'));
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Pricing', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="mh_project_product_type">'.esc_html__('Type', 'sage').'</label></th><td>';
    echo '<select id="mh_project_product_type" name="mh_project_product_type">';
    foreach ([
        'theme' => __('Theme', 'sage'),
        'plugin' => __('Plugin', 'sage'),
        'app' => __('App', 'sage'),
        'service' => __('Service', 'sage'),
        'concept' => __('Concept (hire copy)', 'sage'),
    ] as $val => $lbl) {
        printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($val), selected($productType, $val, false), esc_html($lbl));
    }
    echo '</select></td></tr>';
    $fieldRow(__('Price (USD)', 'sage'), 'mh_project_price', $price, mh_default_theme_price());
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Product page', 'sage').'</h3>';
    echo '<p class="description">'.esc_html__('These fields power the single product page. Blank = inherited from product-catalog.json.', 'sage').'</p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    $fieldRow(__('Eyebrow', 'sage'), 'mh_project_eyebrow', $eyebrow, $ph('eyebrow') ?: 'WordPress real estate agency theme');
    $fieldRow(__('Summary', 'sage'), 'mh_project_summary', $summary, $ph('summary'), 'textarea');
    $fieldRow(__('The problem', 'sage'), 'mh_project_challenge', $challenge, $ph('challenge'), 'textarea');
    $fieldRow(__('How it works', 'sage'), 'mh_project_approach', $approach, $ph('approach'), 'textarea');
    $fieldRow(__('What you get', 'sage'), 'mh_project_result', $result, $ph('result'), 'textarea');
    $fieldRow(__('Who it is for', 'sage'), 'mh_project_audience', $audience, $ph('audience'), 'textarea');
    $fieldRow(__('Architecture', 'sage'), 'mh_project_architecture', $architecture, $ph('architecture'), 'textarea');
    $fieldRow(__('Handoff notes', 'sage'), 'mh_project_handoff', $handoff, $ph('handoff'), 'textarea');
    $fieldRow(__('Deliverables (one per line)', 'sage'), 'mh_project_deliverables', $deliverables, '', 'textarea');
    $fieldRow(__('Benefits (one per line)', 'sage'), 'mh_project_benefits', $benefits, '', 'textarea');
    $fieldRow(__('Files included (one per line)', 'sage'), 'mh_project_files_included', $filesIncl, $ph('files_included') ?: 'theme.zip', 'textarea');
    $fieldRow(__('FAQ (Question|||Answer per line)', 'sage'), 'mh_project_faq', $faq, '', 'textarea');
    $fieldRow(__('Docs (Label|||URL per line)', 'sage'), 'mh_project_docs', $docs, '', 'textarea');
    $fieldRow(__('Live demo URL', 'sage'), 'mh_project_demo', $demo, $ph('demo') ?: 'https://');
    $fieldRow(__('GitHub URL', 'sage'), 'mh_project_github', $github, $ph('github') ?: 'https://github.com/');
    $fieldRow(__('Support URL', 'sage'), 'mh_project_support', $support, $ph('support') ?: 'https://');
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Release info', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    $fieldRow(__('Version', 'sage'), 'mh_project_version', $version, $ph('version') ?: '1.0.0');
    $fieldRow(__('Requires (e.g. WordPress 6.6+, PHP 8.3+)', 'sage'), 'mh_project_compatible', $compatible, $ph('compatible') ?: 'WordPress 6.6+, PHP 8.3+');
    $fieldRow(__('License', 'sage'), 'mh_project_license', $license, $ph('license') ?: 'GPLv2 or later');
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Metrics (up to 3)', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    for ($i = 1; $i <= 3; $i++) {
        echo '<tr><th scope="row">'.esc_html(sprintf(__('Metric %d', 'sage'), $i)).'</th><td>';
        printf(
            '<input class="regular-text" type="text" name="mh_project_m%1$d_value" value="%2$s" placeholder="%3$s" style="max-width:8rem;margin-right:.5rem">',
            $i,
            esc_attr($metrics[$i]['value']),
            esc_attr__('Value', 'sage')
        );
        printf(
            '<input class="regular-text" type="text" name="mh_project_m%1$d_label" value="%2$s" placeholder="%3$s">',
            $i,
            esc_attr($metrics[$i]['label']),
            esc_attr__('Label', 'sage')
        );
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

/**
 * Save project-field meta posted from the WooCommerce product metabox.
 *
 * @since 3.3.0
 */
function mh_save_wc_product_project_meta(int $post_id): void
{
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE
        || ! isset($_POST['mh_product_meta_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_product_meta_nonce'])), 'mh_product_meta')
        || ! current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    update_post_meta($post_id, mh_project_live_meta_key(), isset($_POST['mh_project_live']) ? '1' : '0');
    update_post_meta($post_id, '_mh_project_for_sale', isset($_POST['mh_project_for_sale']) ? '1' : '0');
    update_post_meta($post_id, '_mh_project_cat', sanitize_text_field(wp_unslash($_POST['mh_project_cat'] ?? '')));
    update_post_meta($post_id, '_mh_project_place', sanitize_text_field(wp_unslash($_POST['mh_project_place'] ?? '')));
    update_post_meta($post_id, '_mh_project_blurb', sanitize_textarea_field(wp_unslash($_POST['mh_project_blurb'] ?? '')));
    update_post_meta($post_id, '_mh_project_tech', sanitize_text_field(wp_unslash($_POST['mh_project_tech'] ?? '')));
    update_post_meta($post_id, '_mh_project_image', mh_sanitize_project_image_value(wp_unslash($_POST['mh_project_image'] ?? '')));

    $productType = sanitize_key((string) wp_unslash($_POST['mh_project_product_type'] ?? 'theme'));
    if (! in_array($productType, ['theme', 'plugin', 'app', 'service', 'concept'], true)) {
        $productType = 'theme';
    }
    update_post_meta($post_id, '_mh_project_product_type', $productType);

    $price = sanitize_text_field(wp_unslash($_POST['mh_project_price'] ?? ''));
    if ($price !== '' && ! is_numeric($price)) {
        $price = '';
    }
    update_post_meta($post_id, '_mh_project_price', $price);
    update_post_meta($post_id, '_mh_project_eyebrow', sanitize_text_field(wp_unslash($_POST['mh_project_eyebrow'] ?? '')));
    update_post_meta($post_id, '_mh_project_summary', sanitize_textarea_field(wp_unslash($_POST['mh_project_summary'] ?? '')));
    update_post_meta($post_id, '_mh_project_challenge', sanitize_textarea_field(wp_unslash($_POST['mh_project_challenge'] ?? '')));
    update_post_meta($post_id, '_mh_project_approach', sanitize_textarea_field(wp_unslash($_POST['mh_project_approach'] ?? '')));
    update_post_meta($post_id, '_mh_project_result', sanitize_textarea_field(wp_unslash($_POST['mh_project_result'] ?? '')));
    update_post_meta($post_id, '_mh_project_audience', sanitize_textarea_field(wp_unslash($_POST['mh_project_audience'] ?? '')));
    update_post_meta($post_id, '_mh_project_architecture', sanitize_textarea_field(wp_unslash($_POST['mh_project_architecture'] ?? '')));
    update_post_meta($post_id, '_mh_project_handoff', sanitize_textarea_field(wp_unslash($_POST['mh_project_handoff'] ?? '')));
    update_post_meta($post_id, '_mh_project_deliverables', sanitize_textarea_field(wp_unslash($_POST['mh_project_deliverables'] ?? '')));
    update_post_meta($post_id, '_mh_project_benefits', sanitize_textarea_field(wp_unslash($_POST['mh_project_benefits'] ?? '')));
    update_post_meta($post_id, '_mh_project_files_included', sanitize_textarea_field(wp_unslash($_POST['mh_project_files_included'] ?? '')));
    update_post_meta($post_id, '_mh_project_faq', sanitize_textarea_field(wp_unslash($_POST['mh_project_faq'] ?? '')));
    update_post_meta($post_id, '_mh_project_docs', sanitize_textarea_field(wp_unslash($_POST['mh_project_docs'] ?? '')));
    update_post_meta($post_id, '_mh_project_demo', esc_url_raw(wp_unslash($_POST['mh_project_demo'] ?? '')));
    update_post_meta($post_id, '_mh_project_github', esc_url_raw(wp_unslash($_POST['mh_project_github'] ?? '')));
    update_post_meta($post_id, '_mh_project_support', esc_url_raw(wp_unslash($_POST['mh_project_support'] ?? '')));
    update_post_meta($post_id, '_mh_project_version', sanitize_text_field(wp_unslash($_POST['mh_project_version'] ?? '')));
    update_post_meta($post_id, '_mh_project_compatible', sanitize_text_field(wp_unslash($_POST['mh_project_compatible'] ?? '')));
    update_post_meta($post_id, '_mh_project_license', sanitize_text_field(wp_unslash($_POST['mh_project_license'] ?? '')));

    for ($i = 1; $i <= 3; $i++) {
        update_post_meta($post_id, "_mh_project_m{$i}_value", sanitize_text_field(wp_unslash($_POST["mh_project_m{$i}_value"] ?? '')));
        update_post_meta($post_id, "_mh_project_m{$i}_label", sanitize_text_field(wp_unslash($_POST["mh_project_m{$i}_label"] ?? '')));
    }
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
 * Add-to-cart URL for a project theme (empty when not for sale).
 */
function mh_project_buy_url(int $project_id): string
{
    if ($project_id <= 0 || ! mh_shop_ready()) {
        return '';
    }

    $product_id = mh_project_product_id($project_id);
    if ($product_id <= 0) {
        return '';
    }

    $payload = mh_shop_product_payload($product_id);
    if ($payload === null || empty($payload['purchasable'])) {
        return '';
    }

    return (string) $payload['add_to_cart_url'];
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
    $copy = [
        'all' => [
            'h1' => __('Themes and plugins for WordPress', 'sage'),
            'lede' => __('WordPress themes and plugins I build, sell, and support. Browse by type, open a live demo, and buy a yearly license through Freemius. Plugins are free to download.', 'sage'),
            'how_h2' => __('How buying works', 'sage'),
        ],
        'theme' => [
            'h1' => __('WordPress themes', 'sage'),
            'lede' => __('Block themes I build and support. Each one has a live demo where there is one, a yearly license for updates and email support, and a 30-day refund if I cannot fix a problem.', 'sage'),
            'how_h2' => __('How buying works', 'sage'),
        ],
        'plugin' => [
            'h1' => __('Free WordPress plugins', 'sage'),
            'lede' => __('Plugins I build and maintain. They are free: download the zip from the plugin page.', 'sage'),
            'how_h2' => __('How to get a plugin', 'sage'),
        ],
    ];
    if (isset($copy[$scope][$part])) {
        return $copy[$scope][$part];
    }
    if ($part === 'how_h2') {
        return __('How buying works', 'sage');
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
        __('If a theme has a bug or problem I cannot resolve, you get a refund within 30 days.', 'sage'),
    ];
    // The plugin line only belongs on pages that list plugins ('all'); the themes page leaves it out.
    if ($scope !== 'theme') {
        $steps[] = __('Plugins are free. Download the zip from the plugin page.', 'sage');
    }

    return '<ol><li>'.implode('</li><li>', $steps).'</li></ol>';
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
        [__('What happens when my license expires?', 'sage'), __('The theme keeps working on your site. You stop getting updates and email support until you renew.', 'sage')],
        [__('Can I use a theme on client sites?', 'sage'), __('For Cobble & Candle, the 5-site and unlimited-site plans are meant for that. The unlimited plan is white-label, so you can ship it under your own name.', 'sage')],
        [__('Who do I ask if something breaks?', 'sage'), __('Write to hello@matthummel.com. Support is by email, and I reply within one business day (ET).', 'sage')],
    ];
}

/**
 * ID of the Shop page (/shop/), or 0 when it has not been created. Lets the shop link through the
 * old "storefront is off" redirect and nav filters.
 */
function mh_shop_page_id(): int
{
    $page = get_page_by_path('shop');

    return ($page instanceof \WP_Post && get_page_template_slug($page) === 'template-shop.blade.php')
        ? (int) $page->ID
        : 0;
}

/**
 * Rows for the shop listing: live products of any known type (see mh_product_types), each with a status the card can show.
 *
 * status: buy (Freemius plans), free (direct download), soon (not for sale yet), details (anything else).
 * Prices come from the catalog; nothing is invented here.
 *
 * @param  string  $type  'all' or a key from mh_product_types().
 * @return list<array{slug: string, title: string, blurb: string, url: string, image: string, demo: string, type: string, status: string, price: string, free_url: string}>
 */
function mh_shop_items(string $type = 'all'): array
{
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
        ];
    }

    $rank = ['buy' => 0, 'free' => 1, 'soon' => 2, 'details' => 3];
    usort($items, static fn (array $a, array $b): int => $rank[$a['status']] <=> $rank[$b['status']]);

    return $items;
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

/** Plain-text price for work cards (e.g. "$149"). */
function mh_project_price_label(int $project_id): string
{
    if ($project_id <= 0 || ! mh_shop_ready()) {
        return '';
    }

    $product_id = mh_project_product_id($project_id);
    if ($product_id <= 0) {
        return '';
    }

    $payload = mh_shop_product_payload($product_id);
    if ($payload === null || ($payload['price_html'] ?? '') === '') {
        return '';
    }

    return html_entity_decode(wp_strip_all_tags((string) $payload['price_html']), ENT_QUOTES, 'UTF-8');
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

    if (mh_shop_ready() && function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);
        if ($product instanceof \WC_Product) {
            $wcType = sanitize_key((string) $product->get_meta('_mh_product_type'));
            if (in_array($wcType, $allowed, true)) {
                return $wcType;
            }
        }
    }

    $entry = mh_product_catalog_data($product_id);
    $fromEntry = sanitize_key((string) ($entry['product_type'] ?? ''));
    if (in_array($fromEntry, $allowed, true)) {
        return $fromEntry;
    }

    if (function_exists('has_term') && taxonomy_exists('product_cat') && has_term(['services', 'service'], 'product_cat', $product_id)) {
        return 'service';
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

/** Buy / download label for a Woo product, type-aware (including services). */
function mh_product_buy_copy(int $product_id, bool $is_free = false): string
{
    $chrome = mh_product_type_chrome(mh_resolve_product_type($product_id));

    return $is_free ? $chrome['buy_free'] : $chrome['buy'];
}

/**
 * Catalog JSON entry for a product slug.
 *
 * @since 3.5.26
 *
 * @return array<string, mixed>
 */
function mh_catalog_entry_by_slug(string $slug): array
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return [];
    }

    $entry = mh_product_catalog_entries()[$slug] ?? [];

    return is_array($entry) ? $entry : [];
}

/**
 * Featured paid theme (Acreline) for homepage hero CTAs.
 *
 * @since 3.5.26
 *
 * @return array{slug: string, name: string, price: string, permalink: string, demo: string, add_to_cart_url: string, buy_label: string}
 */
function mh_featured_theme_offer(string $slug = 'acreline'): array
{
    $entry = mh_catalog_entry_by_slug($slug);
    $productId = mh_product_id_by_slug($slug);
    $payload = $productId > 0 ? mh_shop_product_payload($productId) : null;

    $price = '';
    if (is_array($payload) && (string) ($payload['regular_price'] ?? '') !== '') {
        $price = ltrim((string) $payload['regular_price'], '$');
    } elseif ((string) ($entry['price'] ?? '') !== '') {
        $price = ltrim((string) $entry['price'], '$');
    }

    $name = trim((string) ($entry['title'] ?? ''));
    if ($name === '') {
        $name = 'Acreline';
    }

    $permalink = is_array($payload) && (string) ($payload['permalink'] ?? '') !== ''
        ? (string) $payload['permalink']
        : home_url('/product/'.$slug.'/');

    $demo = trim((string) ($entry['demo'] ?? ''));
    $buyLabel = $price !== ''
        ? sprintf(__('Buy %s — $%s', 'sage'), $name, $price)
        : sprintf(__('Buy %s', 'sage'), $name);

    return [
        'slug' => $slug,
        'name' => $name,
        'price' => $price,
        'permalink' => $permalink,
        'demo' => $demo,
        'add_to_cart_url' => is_array($payload) ? (string) ($payload['add_to_cart_url'] ?? '') : '',
        'buy_label' => $buyLabel,
    ];
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
    if ($type === 'concept') {
        $linked = mh_project_product_id($project_id);
        if ($linked > 0) {
            return mh_product_buy_copy($linked, false);
        }
    }

    $chrome = mh_product_type_chrome($type === 'concept' ? 'theme' : $type);

    return $chrome['buy'];
}

/**
 * Ensure a product_cat term exists and return its ID.
 */
function mh_woocommerce_ensure_product_category(string $slug, string $label): int
{
    if (! taxonomy_exists('product_cat')) {
        return 0;
    }

    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term instanceof \WP_Term) {
        return (int) $term->term_id;
    }

    $created = wp_insert_term($label, 'product_cat', [
        'slug' => $slug,
    ]);
    if (is_wp_error($created)) {
        return 0;
    }

    return (int) ($created['term_id'] ?? 0);
}

/** Product category used for synced project themes. */
function mh_woocommerce_themes_category_id(): int
{
    return mh_woocommerce_ensure_product_category('themes', __('Themes', 'sage'));
}

/** Product category used for synced project plugins. */
function mh_woocommerce_plugins_category_id(): int
{
    return mh_woocommerce_ensure_product_category('plugins', __('Plugins', 'sage'));
}

/**
 * Resolve an existing WooCommerce product for a project.
 */
function mh_find_product_id_for_project(int $project_id, string $slug): int
{
    // SKU is globally unique in WooCommerce — a SKU match is authoritative.
    // Checking SKU first self-heals stale meta pointers (e.g. if a resync
    // previously created a stub duplicate and stored its ID in the meta).
    if ($slug !== '' && function_exists('wc_get_product_id_by_sku')) {
        $skus = ['theme-'.$slug, 'plugin-'.$slug];
        if ($slug === 'tocguide') {
            $skus[] = 'plugin-tocflow';
        }
        foreach ($skus as $trySku) {
            $bySku = (int) wc_get_product_id_by_sku($trySku);
            if ($bySku > 0 && get_post_status($bySku) !== 'trash') {
                return $bySku;
            }
        }
    }

    $id = (int) get_post_meta($project_id, '_mh_project_product_id', true);
    if ($id > 0 && get_post_type($id) === 'product' && get_post_status($id) !== 'trash') {
        return $id;
    }

    $found = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_mh_product_project_id',
        'meta_value' => (string) $project_id,
    ]);
    if ($found !== []) {
        return (int) $found[0];
    }

    // Acreline marketplace SEO slugs / leftover duplicates.
    $slugCandidates = $slug !== '' ? [$slug] : [];
    if ($slug === 'acreline' || $project_id > 0 && stripos((string) get_the_title($project_id), 'Acreline') !== false) {
        $slugCandidates = array_values(array_unique(array_merge($slugCandidates, [
            'acreline',
            'wordpress-theme-real-estate-agents',
            'real-estate-wordpress-theme-acreline',
        ])));
    }
    if ($slug === 'tocguide') {
        $slugCandidates = array_values(array_unique(array_merge($slugCandidates, [
            'tocguide',
            'tocflow',
        ])));
    }

    foreach ($slugCandidates as $candidate) {
        $bySlug = get_posts([
            'post_type' => 'product',
            'name' => $candidate,
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);
        if ($bySlug !== []) {
            return (int) $bySlug[0];
        }
    }

    return 0;
}

/**
 * Create or update the WooCommerce product that sells this project as a theme.
 */
function mh_sync_project_product(int $project_id): int
{
    if ($project_id <= 0 || ! mh_shop_ready() || ! class_exists('WC_Product_Simple')) {
        return 0;
    }

    try {
        return mh_sync_project_product_unchecked($project_id);
    } catch (\Throwable $e) {
        // Catalog sync must never white-screen the public site.
        if (function_exists('error_log')) {
            error_log('mh_sync_project_product('.$project_id.'): '.$e->getMessage());
        }

        return 0;
    }
}

/**
 * @internal Prefer mh_sync_project_product().
 */
function mh_sync_project_product_unchecked(int $project_id): int
{
    $post = get_post($project_id);
    if (! $post instanceof \WP_Post || $post->post_type !== mh_project_post_type()) {
        return 0;
    }

    $slug = sanitize_title((string) $post->post_name);
    $type = sanitize_key((string) get_post_meta($project_id, '_mh_project_product_type', true));
    if ($type === '') {
        $type = 'theme';
        update_post_meta($project_id, '_mh_project_product_type', $type);
    }
    $skuPrefix = $type === 'plugin' ? 'plugin-' : 'theme-';
    $sku = $skuPrefix.($slug !== '' ? $slug : (string) $project_id);
    $productId = mh_find_product_id_for_project($project_id, $slug);
    $isNew = $productId <= 0;
    $product = $isNew ? new \WC_Product_Simple : wc_get_product($productId);
    if (! $product instanceof \WC_Product) {
        $product = new \WC_Product_Simple;
        $isNew = true;
    }

    $blurb = trim((string) get_post_meta($project_id, '_mh_project_blurb', true));
    $summary = trim((string) get_post_meta($project_id, '_mh_project_summary', true));
    if ($summary === '') {
        $summary = $blurb;
    }

    // Enrich blurb + description from the product catalog JSON when available.
    // A rich description helps Rank Math / Yoast score the product page against
    // substantial, keyword-dense content instead of a one-sentence blurb.
    $catalogEntry = isset($catalog[$slug]) ? $catalog[$slug] : [];
    if ($catalogEntry === [] && is_readable(get_theme_file_path('resources/data/product-catalog.json'))) {
        static $catalogJson = null;
        if ($catalogJson === null) {
            $decoded = json_decode((string) file_get_contents(get_theme_file_path('resources/data/product-catalog.json')), true);
            $catalogJson = is_array($decoded) ? $decoded : [];
        }
        $catalogEntry = $catalogJson[$slug] ?? [];
    }

    if ($catalogEntry !== []) {
        $catalogBlurb = trim((string) ($catalogEntry['blurb'] ?? ''));
        if ($catalogBlurb !== '') {
            $blurb = $catalogBlurb;
        }
        $catalogSummary = trim((string) ($catalogEntry['summary'] ?? ''));
        if ($catalogSummary !== '') {
            $summary = $catalogSummary;
        }
    }

    $richDescription = $catalogEntry !== [] ? mh_product_description_html($catalogEntry) : '';
    if ($richDescription === '') {
        $richDescription = $summary;
    }

    $product->set_name($post->post_title);
    if ($slug !== '') {
        $product->set_slug($slug);
    }
    $product->set_virtual(true);
    $product->set_sold_individually(true);
    $product->set_catalog_visibility('visible');
    $product->set_short_description($blurb);
    $product->set_description($richDescription);

    $currentSku = (string) $product->get_sku();
    if ($isNew || $currentSku === '') {
        try {
            $product->set_sku($sku);
        } catch (\WC_Data_Exception $e) {
            // SKU already owned by another product — adopt it instead of fatalling.
            $existingId = function_exists('wc_get_product_id_by_sku')
                ? (int) wc_get_product_id_by_sku($sku)
                : 0;
            if ($existingId > 0) {
                $existing = wc_get_product($existingId);
                if ($existing instanceof \WC_Product) {
                    $product = $existing;
                    $isNew = false;
                    $product->set_name($post->post_title);
                    if ($slug !== '') {
                        $product->set_slug($slug);
                    }
                    $product->set_virtual(true);
                    $product->set_sold_individually(true);
                    $product->set_catalog_visibility('visible');
                    $product->set_short_description($blurb);
                    $product->set_description($richDescription);
                }
            }
        }
    }

    $price = trim((string) get_post_meta($project_id, '_mh_project_price', true));
    if ($price !== '' && is_numeric($price)) {
        $product->set_regular_price($price);
        if ((float) $price <= 0.0) {
            $product->set_sale_price('');
            $product->set_price('0');
        }
    } elseif ($isNew || (string) $product->get_regular_price() === '') {
        $fallback = $type === 'plugin' ? mh_default_plugin_price() : mh_default_theme_price();
        $product->set_regular_price($fallback);
        if ((float) $fallback <= 0.0) {
            $product->set_sale_price('');
            $product->set_price('0');
        }
    }

    $live = mh_project_is_live($project_id) && $post->post_status === 'publish';
    $forSale = mh_project_is_for_sale($project_id);
    $product->set_status($live && $forSale ? 'publish' : 'private');

    $thumb = (int) get_post_thumbnail_id($project_id);
    if ($thumb > 0) {
        $product->set_image_id($thumb);
    }

    $catId = $type === 'plugin'
        ? mh_woocommerce_plugins_category_id()
        : mh_woocommerce_themes_category_id();
    if ($catId > 0) {
        $product->set_category_ids([$catId]);
    }

    $product->update_meta_data('_mh_product_project_id', $project_id);
    $saved = (int) $product->save();
    if ($saved <= 0) {
        return 0;
    }

    update_post_meta($project_id, '_mh_project_product_id', (string) $saved);
    mh_apply_catalog_download_file($saved, false);

    return $saved;
}

/**
 * Catalog `download` object, or null when this entry is not a zip product.
 *
 * @param  array<string, mixed>  $entry
 * @return array{repo: string, release: string, asset: string, prefix: string, label: string}|null
 */
function mh_catalog_download_spec(array $entry): ?array
{
    $type = sanitize_key((string) ($entry['product_type'] ?? 'theme'));
    if (in_array($type, ['service', 'concept'], true)) {
        return null;
    }

    $download = is_array($entry['download'] ?? null) ? $entry['download'] : [];
    $repo = trim((string) ($download['github_repo'] ?? ''));
    if ($repo === '') {
        $github = trim((string) ($entry['github'] ?? ''));
        if (preg_match('#github\\.com/([^/]+/[^/?#]+)#', $github, $m) === 1) {
            $repo = trim($m[1], '/');
        }
    }
    if ($repo === '' || ! preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo)) {
        return null;
    }

    return [
        'repo' => $repo,
        'release' => trim((string) ($download['release'] ?? 'latest')) ?: 'latest',
        'asset' => trim((string) ($download['asset'] ?? 'latest')) ?: 'latest',
        'prefix' => trim((string) ($download['asset_prefix'] ?? '')),
        'label' => trim((string) ($download['label'] ?? '')),
    ];
}

/**
 * Pick a GitHub release zip from a catalog download spec.
 *
 * @param  array{repo: string, release: string, asset: string, prefix: string, label: string}  $spec
 * @return array{name: string, url: string, label: string, size: int}|null
 */
function mh_github_release_download_file(array $spec): ?array
{
    $repo = $spec['repo'];
    $tag = $spec['release'];
    $url = $tag === '' || $tag === 'latest'
        ? "https://api.github.com/repos/{$repo}/releases/latest"
        : 'https://api.github.com/repos/'.$repo.'/releases/tags/'.rawurlencode($tag);

    $data = github_get($url);
    if (! is_array($data) || empty($data['assets']) || ! is_array($data['assets'])) {
        return null;
    }

    $assets = [];
    foreach ($data['assets'] as $asset) {
        if (! is_array($asset)) {
            continue;
        }
        $name = (string) ($asset['name'] ?? '');
        $fileUrl = (string) ($asset['browser_download_url'] ?? '');
        if ($name === '' || $fileUrl === '' || ! str_ends_with(strtolower($name), '.zip')) {
            continue;
        }
        $assets[] = [
            'name' => $name,
            'url' => $fileUrl,
            'size' => (int) ($asset['size'] ?? 0),
        ];
    }
    if ($assets === []) {
        return null;
    }

    $wanted = $spec['asset'];
    if ($wanted !== '' && $wanted !== 'latest') {
        foreach ($assets as $asset) {
            if ($asset['name'] === $wanted) {
                return $asset + ['label' => $spec['label'] !== '' ? $spec['label'] : $asset['name']];
            }
        }
    }

    $prefix = $spec['prefix'];
    $versioned = [];
    foreach ($assets as $asset) {
        $pattern = $prefix !== ''
            ? '#^'.preg_quote($prefix, '#').'(\\d+\\.\\d+\\.\\d+)\\.zip$#'
            : '#^[A-Za-z0-9-]+-(\\d+\\.\\d+\\.\\d+)\\.zip$#';
        if (preg_match($pattern, $asset['name'], $m) === 1) {
            $versioned[$m[1]] = $asset;
        }
    }
    if ($versioned !== []) {
        uksort($versioned, 'version_compare');
        $asset = end($versioned);
        if (is_array($asset)) {
            return $asset + ['label' => $spec['label'] !== '' ? $spec['label'] : $asset['name']];
        }
    }

    foreach ($assets as $asset) {
        $label = $spec['label'] !== '' ? $spec['label'] : $asset['name'];
        if ($asset['name'] === $label) {
            return $asset + ['label' => $label];
        }
    }

    return null;
}

/**
 * Allow a remote zip URL in WooCommerce approved download directories.
 *
 * WooCommerce 8+ rejects GitHub release URLs unless the parent path is
 * enabled. WP-CLI and front-end init are not site admins, so auto-add
 * would leave the rule disabled and `set_downloads()` would fatal.
 */
function mh_approve_product_download_url(string $fileUrl): bool
{
    $fileUrl = trim($fileUrl);
    if ($fileUrl === '' || ! function_exists('wc_get_container')) {
        return false;
    }

    try {
        $register = wc_get_container()->get(
            Register::class
        );
    } catch (\Throwable $e) {
        return false;
    }

    if (! is_object($register) || ! method_exists($register, 'add_approved_directory')) {
        return false;
    }

    $parent = preg_replace('#/[^/]+$#', '/', $fileUrl) ?: $fileUrl;
    if (class_exists(URL::class)) {
        try {
            $parsed = new URL($fileUrl);
            if (method_exists($parsed, 'get_parent_url')) {
                $fromParent = trim((string) $parsed->get_parent_url());
                if ($fromParent !== '') {
                    $parent = $fromParent;
                }
            }
        } catch (\Throwable $e) {
            // keep dirname fallback
        }
    }

    try {
        $register->add_approved_directory($parent, true);
        $existing = method_exists($register, 'get_by_url') ? $register->get_by_url($parent) : false;
        if (is_object($existing) && method_exists($existing, 'is_enabled') && ! $existing->is_enabled() && method_exists($register, 'enable_by_id')) {
            $register->enable_by_id((int) $existing->get_id());
        }

        return method_exists($register, 'is_valid_path')
            ? (bool) $register->is_valid_path($fileUrl)
            : true;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Mark a catalog product virtual+downloadable and attach the GitHub zip.
 *
 * Skips when files already exist unless $force. Does not localize the zip
 * (that is CLI-only) so front-end init never pulls a 20 MB file.
 *
 * @return array{ok: bool, message: string, file: string}
 */
function mh_apply_catalog_download_file(int $product_id, bool $force = false, bool $notify = true): array
{
    $empty = ['ok' => false, 'message' => '', 'file' => ''];
    if ($product_id <= 0 || ! mh_shop_ready() || ! class_exists('WC_Product_Download')) {
        return array_merge($empty, ['message' => 'shop not ready']);
    }

    $product = wc_get_product($product_id);
    if (! $product instanceof \WC_Product) {
        return array_merge($empty, ['message' => 'product missing']);
    }

    $entry = mh_product_catalog_data($product_id);
    $spec = mh_catalog_download_spec($entry);
    if ($spec === null) {
        return array_merge($empty, ['message' => 'no download spec']);
    }

    $oldVersion = trim((string) $product->get_meta('_mh_download_version'));

    $existing = $product->get_downloads();
    if ($existing !== [] && ! $force) {
        if (trim((string) $product->get_meta('_mh_download_version')) === '') {
            $entryVersion = trim((string) ($entry['version'] ?? ''));
            if ($entryVersion !== '') {
                $product->update_meta_data('_mh_download_version', $entryVersion);
                $product->save();
            }
        }

        return ['ok' => true, 'message' => 'already attached', 'file' => ''];
    }

    $file = mh_github_release_download_file($spec);
    if ($file === null) {
        return array_merge($empty, ['message' => 'no GitHub zip for '.$spec['repo']]);
    }

    $download = new \WC_Product_Download;
    $download->set_name($file['label']);
    $download->set_file($file['url']);

    $product->set_virtual(true);
    $product->set_downloadable(true);
    $product->set_download_limit(-1);
    $product->set_download_expiry(-1);
    $product->set_reviews_allowed(false);
    $product->set_sold_individually(true);
    mh_approve_product_download_url($file['url']);
    try {
        $product->set_downloads([$download]);
    } catch (\Throwable $e) {
        return array_merge($empty, ['message' => $e->getMessage()]);
    }
    $version = mh_version_from_zip_name($file['name']);
    if ($version === '') {
        $version = trim((string) ($entry['version'] ?? ''));
    }
    if ($version !== '') {
        $product->update_meta_data('_mh_download_version', $version);
    }
    $product->update_meta_data('_mh_download_asset', $file['name']);
    $saved = (int) $product->save();
    if ($saved <= 0) {
        return array_merge($empty, ['message' => 'save failed']);
    }

    if ($notify && $oldVersion !== '' && $version !== '' && version_compare($version, $oldVersion, '>')) {
        mh_notify_buyers_of_download_update($saved, $version, $oldVersion, false);
    }

    return ['ok' => true, 'message' => 'attached', 'file' => $file['name']];
}

/**
 * Copy a remote Woo download into woocommerce_uploads (protected).
 *
 * @return array{ok: bool, message: string, file: string}
 */
function mh_localize_product_download(int $product_id): array
{
    $empty = ['ok' => false, 'message' => '', 'file' => ''];
    if ($product_id <= 0 || ! mh_shop_ready()) {
        return array_merge($empty, ['message' => 'shop not ready']);
    }

    $product = wc_get_product($product_id);
    if (! $product instanceof \WC_Product) {
        return array_merge($empty, ['message' => 'product missing']);
    }

    $downloads = $product->get_downloads();
    if ($downloads === []) {
        return array_merge($empty, ['message' => 'no files to localize']);
    }

    $uploads = wp_upload_dir();
    if (! empty($uploads['error'])) {
        return array_merge($empty, ['message' => (string) $uploads['error']]);
    }

    $dir = trailingslashit((string) $uploads['basedir']).'woocommerce_uploads';
    if (! wp_mkdir_p($dir)) {
        return array_merge($empty, ['message' => 'could not create woocommerce_uploads']);
    }

    $changed = false;
    $localized = [];
    foreach ($downloads as $key => $download) {
        if (! $download instanceof \WC_Product_Download) {
            continue;
        }
        $remote = trim((string) $download->get_file());
        if (! preg_match('#^https?://#i', $remote)) {
            continue;
        }

        $tmp = download_url($remote, 180);
        if (is_wp_error($tmp)) {
            return array_merge($empty, ['message' => $tmp->get_error_message()]);
        }

        $name = sanitize_file_name((string) $download->get_name());
        if ($name === '') {
            $name = 'download.zip';
        }
        if (! str_ends_with(strtolower($name), '.zip')) {
            $name .= '.zip';
        }

        $dest = $dir.'/'.$name;
        if (! @copy((string) $tmp, $dest)) {
            @unlink((string) $tmp);

            return array_merge($empty, ['message' => 'could not write '.$name]);
        }
        @unlink((string) $tmp);

        $localUrl = trailingslashit((string) $uploads['baseurl']).'woocommerce_uploads/'.$name;
        $download->set_file($localUrl);
        $downloads[$key] = $download;
        $localized[] = $name;
        $changed = true;
    }

    if (! $changed) {
        return ['ok' => true, 'message' => 'already local', 'file' => ''];
    }

    $product->set_downloads($downloads);
    $product->save();

    return ['ok' => true, 'message' => 'localized', 'file' => implode(', ', $localized)];
}

/**
 * WooCommerce product IDs for for-sale catalog slugs (themes and plugins).
 *
 * @return array<string, int>
 */
function mh_catalog_download_product_ids(): array
{
    $ids = [];
    foreach (mh_product_catalog_entries() as $slug => $entry) {
        if (! is_array($entry) || empty($entry['for_sale'])) {
            continue;
        }
        if (mh_catalog_download_spec($entry) === null) {
            continue;
        }

        $slug = sanitize_title((string) $slug);
        $type = sanitize_key((string) ($entry['product_type'] ?? 'theme'));
        $skuPrefix = $type === 'plugin' ? 'plugin-' : 'theme-';
        $productId = 0;
        if (function_exists('wc_get_product_id_by_sku')) {
            $productId = (int) wc_get_product_id_by_sku($skuPrefix.$slug);
        }
        if ($productId <= 0) {
            $bySlug = get_posts([
                'post_type' => 'product',
                'name' => $slug,
                'post_status' => ['publish', 'private', 'draft'],
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
            ]);
            $productId = $bySlug !== [] ? (int) $bySlug[0] : 0;
        }
        if ($productId > 0) {
            $ids[$slug] = $productId;
        }
    }

    return $ids;
}

/**
 * Admin warning when a for-sale theme/plugin has no downloadable file.
 */
function mh_wc_product_download_admin_notice(int $product_id, bool $forSale, string $productType): void
{
    if (! $forSale || in_array($productType, ['service', 'concept'], true) || ! function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product($product_id);
    if (! $product instanceof \WC_Product) {
        return;
    }

    $files = $product->get_downloads();
    if ($product->is_downloadable() && $files !== []) {
        $names = [];
        foreach ($files as $download) {
            if ($download instanceof \WC_Product_Download) {
                $names[] = $download->get_name();
            }
        }
        printf(
            '<div class="notice notice-success inline"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: %s: download file names */
                __('Buyers get these files after payment: %s', 'sage'),
                implode(', ', $names)
            ))
        );

        return;
    }

    echo '<div class="notice notice-warning inline"><p>';
    echo esc_html__('This product is for sale but has no downloadable file. After checkout the receipt and My account → Downloads stay empty. Run `wp mh shop-downloads` or add a zip under Product data → Downloadable files.', 'sage');
    echo '</p></div>';
}

/**
 * Semver from a zip filename such as acreline-1.5.4.zip.
 */
function mh_version_from_zip_name(string $name): string
{
    return preg_match('#(\\d+\\.\\d+\\.\\d+)\\.zip$#i', $name, $m) === 1 ? $m[1] : '';
}

/**
 * Latest zip version for a Woo product (attached download, then catalog).
 */
function mh_product_download_version(int $product_id): string
{
    if ($product_id <= 0) {
        return '';
    }

    $stored = trim((string) get_post_meta($product_id, '_mh_download_version', true));
    if ($stored !== '') {
        return $stored;
    }

    $entry = mh_product_catalog_data($product_id);

    return trim((string) ($entry['version'] ?? ''));
}

/**
 * Paid orders that include a catalog download product.
 *
 * @return list<array{order: \WC_Order, item: \WC_Order_Item_Product, email: string, notified: string}>
 */
function mh_product_download_recipients(int $product_id): array
{
    if ($product_id <= 0 || ! function_exists('wc_get_orders')) {
        return [];
    }

    $orders = wc_get_orders([
        'status' => ['completed', 'processing'],
        'limit' => 500,
        'type' => 'shop_order',
        'return' => 'objects',
    ]);

    $recipients = [];
    foreach ($orders as $order) {
        if (! $order instanceof \WC_Order) {
            continue;
        }
        $email = sanitize_email((string) $order->get_billing_email());
        if ($email === '' || ! is_email($email)) {
            continue;
        }
        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product || (int) $item->get_product_id() !== $product_id) {
                continue;
            }
            $recipients[] = [
                'order' => $order,
                'item' => $item,
                'email' => $email,
                'notified' => trim((string) $item->get_meta('_mh_notified_version', true)),
            ];
        }
    }

    return $recipients;
}

/**
 * Email buyers when a purchased zip gets a newer version.
 *
 * @return int Number of messages sent
 */
function mh_notify_buyers_of_download_update(int $product_id, string $new_version, string $old_version = '', bool $resend = false): int
{
    if ($product_id <= 0 || $new_version === '' || ! function_exists('WC')) {
        return 0;
    }
    if ($old_version !== '' && ! version_compare($new_version, $old_version, '>')) {
        return 0;
    }

    $mailer = WC()->mailer();
    if (! is_object($mailer) || ! method_exists($mailer, 'get_emails')) {
        return 0;
    }

    $emails = $mailer->get_emails();
    $email = is_array($emails) ? ($emails['mh_customer_download_update'] ?? null) : null;
    if (! $email instanceof Email_Customer_Download_Update || ! $email->is_enabled()) {
        return 0;
    }

    $sent = 0;
    $seen = [];
    foreach (mh_product_download_recipients($product_id) as $row) {
        if (isset($seen[$row['email']])) {
            continue;
        }
        if (! $resend && $row['notified'] !== '' && ! version_compare($new_version, $row['notified'], '>')) {
            continue;
        }

        $email->trigger($row['order'], $product_id, $new_version, $old_version !== '' ? $old_version : $row['notified']);
        if ($email->sent) {
            $seen[$row['email']] = true;
            $row['item']->update_meta_data('_mh_notified_version', $new_version);
            $row['item']->save();
            $sent++;
        }
    }

    return $sent;
}

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh shop-downloads', function ($args, array $assoc): void {
        unset($args);
        if (! mh_shop_ready()) {
            \WP_CLI::error('WooCommerce is not active.');
        }

        $force = ! empty($assoc['force']);
        $localize = ! empty($assoc['localize']);
        $notify = empty($assoc['no-notify']);
        $only = sanitize_title((string) ($assoc['slug'] ?? ''));
        $ids = mh_catalog_download_product_ids();
        if ($ids === []) {
            \WP_CLI::warning('No catalog products with a download spec.');

            return;
        }

        foreach ($ids as $slug => $productId) {
            if ($only !== '' && $slug !== $only) {
                continue;
            }
            $result = mh_apply_catalog_download_file($productId, $force, $notify);
            \WP_CLI::log(sprintf('%s #%d %s %s', $slug, $productId, $result['message'], $result['file']));
            if ($localize && $result['ok']) {
                $local = mh_localize_product_download($productId);
                \WP_CLI::log(sprintf('  localize: %s %s', $local['message'], $local['file']));
            }
        }

        \WP_CLI::success('Shop downloads sync finished.');
    });

    \WP_CLI::add_command('mh shop-notify-updates', function ($args, array $assoc): void {
        unset($args);
        if (! mh_shop_ready()) {
            \WP_CLI::error('WooCommerce is not active.');
        }

        $only = sanitize_title((string) ($assoc['slug'] ?? ''));
        $resend = ! empty($assoc['resend']);
        $ids = mh_catalog_download_product_ids();
        $sent = 0;
        foreach ($ids as $slug => $productId) {
            if ($only !== '' && $slug !== $only) {
                continue;
            }
            $version = mh_product_download_version($productId);
            if ($version === '') {
                \WP_CLI::warning($slug.' has no download version yet.');

                continue;
            }
            $count = mh_notify_buyers_of_download_update($productId, $version, '', $resend);
            $sent += $count;
            \WP_CLI::log(sprintf('%s #%d emailed %d buyer(s)', $slug, $productId, $count));
        }

        \WP_CLI::success(sprintf('Update emails sent: %d', $sent));
    });
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_product_project_fields',
        __('Product fields', 'sage'),
        __NAMESPACE__.'\\mh_wc_product_admin_meta_box',
        'product',
        'normal',
        'high'
    );
});

add_action('save_post_product', function (int $post_id): void {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    mh_save_wc_product_project_meta($post_id);
}, 20);

add_filter('loop_shop_columns', fn (): int => 3);
