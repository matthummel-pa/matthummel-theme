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
 * One-pass shop archive stats + CollectionPage list items.
 *
 * @return array{count: int, for_sale: int, theme: int, plugin: int, app: int, list_items: list<array<string, mixed>>}
 */
function mh_shop_listing_snapshot(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $cached = [
        'count' => 0,
        'for_sale' => 0,
        'theme' => 0,
        'plugin' => 0,
        'app' => 0,
        'list_items' => [],
    ];

    if (! function_exists('wc_get_products')) {
        return $cached;
    }

    $ids = wc_get_products([
        'limit' => -1,
        'status' => 'publish',
        'return' => 'ids',
        'catalog_visibility' => 'visible',
    ]);
    $cached['count'] = count($ids);

    foreach ($ids as $i => $pid) {
        $pid = (int) $pid;
        $product = wc_get_product($pid);
        if ($product && $product->is_purchasable() && $product->is_in_stock()) {
            $cached['for_sale']++;
        }

        $type = (string) (mh_product_catalog_data($pid)['product_type'] ?? 'theme');
        if ($type === 'plugin') {
            $cached['plugin']++;
        } elseif ($type === 'app') {
            $cached['app']++;
        } else {
            $cached['theme']++;
        }

        $cached['list_items'][] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => (string) get_permalink($pid),
            'name' => html_entity_decode(get_the_title($pid), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
    }

    return $cached;
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
 * Public landing URL for a product — the linked Work concept page when available.
 */
function mh_product_landing_url(int $product_id): string
{
    // Project CPT is retired — keep products on their WooCommerce URLs.
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
 * Point product permalinks at the Work concept page so shop cards open the portfolio landing.
 *
 * @param  string  $permalink  Default product URL.
 * @param  \WC_Product  $product  Product object.
 */
function mh_filter_product_permalink(string $permalink, $product): string
{
    if (! is_object($product) || ! method_exists($product, 'get_id')) {
        return $permalink;
    }

    $landing = mh_product_landing_url((int) $product->get_id());

    return $landing !== '' ? $landing : $permalink;
}

/**
 * Send direct /product/{slug}/ visits to the linked Work concept page (one story URL).
 */
function mh_redirect_product_to_project(): void
{
    if (! mh_shop_ready() || ! function_exists('is_product') || ! is_product()) {
        return;
    }

    // Opt out for Woo admin preview / explicit raw product view.
    if (isset($_GET['mh_wc_product']) || is_preview()) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    $landing = mh_product_landing_url((int) get_queried_object_id());
    if ($landing === '') {
        return;
    }

    wp_safe_redirect($landing, 301);
    exit;
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
 * Soften default WooCommerce chrome on product/cart/checkout for this studio site.
 */
function mh_shop_body_class(array $classes): array
{
    if (! mh_shop_ready()) {
        return $classes;
    }

    if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
        $classes[] = 'mh-shop';
    }

    return $classes;
}

add_filter('body_class', __NAMESPACE__.'\\mh_shop_body_class');

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

    static $catalog = null;
    if ($catalog === null) {
        $path = get_theme_file_path('resources/data/product-catalog.json');
        $catalog = [];
        if (is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            $catalog = is_array($decoded) ? $decoded : [];
        }
    }

    $aliases = [
        'wordpress-theme-real-estate-agents' => 'acreline',
        'real-estate-wordpress-theme-acreline' => 'acreline',
        'wordpress-tour-theme-walkridge' => 'walkridge',
        'walkridge-tour-wordpress-theme' => 'walkridge',
        'acreline-real-estate-wordpress-theme' => 'acreline',
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

    $str = fn (string $key): string => trim((string) get_post_meta($product_id, '_mh_project_'.$key, true));

    // Scalar string fields.
    foreach (['eyebrow', 'summary', 'blurb', 'challenge', 'approach', 'result',
        'audience', 'architecture', 'handoff', 'demo', 'cat', 'place',
        'github', 'version', 'compatible', 'license', 'support'] as $key) {
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

/** Catalog URL for empty-cart / return-to-shop links. */
function mh_theme_catalog_url(): string
{
    // /shop/ is now the primary product listing. /projects/ 301-redirects there.
    if (mh_shop_ready() && function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('shop');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return home_url('/shop/');
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

    $productType = trim((string) get_post_meta($product_id, '_mh_project_product_type', true));
    if ($productType === '' || ! in_array($productType, ['theme', 'plugin', 'concept'], true)) {
        $productType = (string) ($entry['product_type'] ?? 'theme');
    }

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

    // Buy / price labels.
    $isFree = (float) $wc->get_price() <= 0.0;
    $buyLabel = $isFree
        ? ($productType === 'plugin' ? __('Download plugin', 'sage') : __('Get theme', 'sage'))
        : ($productType === 'plugin' ? __('Buy plugin', 'sage') : __('Buy theme', 'sage'));
    $priceLabel = $isFree ? __('Free', 'sage') : ('$'.(string) $wc->get_regular_price());

    $buyUrl = mh_product_add_to_cart_url($product_id);

    $productLink = (string) get_permalink($product_id);

    $card = [
        'slug' => $slug,
        'title' => wp_specialchars_decode((string) $wc->get_name(), ENT_QUOTES),
        'cat' => $cat,
        'place' => $place,
        'blurb' => $blurb,
        'tech' => $tech,
        'concept' => trim((string) ($entry['github'] ?? '')),
        'demo' => $demo,
        'url' => $productLink,
        'image' => $image,
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
    $fieldRow(__('Screenshot file or URL', 'sage'), 'mh_project_image', $image, __('Fallback when no featured image. Path: products/acreline/featured.webp or full URL.', 'sage'));
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Pricing', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="mh_project_product_type">'.esc_html__('Type', 'sage').'</label></th><td>';
    echo '<select id="mh_project_product_type" name="mh_project_product_type">';
    foreach (['theme' => __('Theme', 'sage'), 'plugin' => __('Plugin', 'sage'), 'concept' => __('Concept (hire copy)', 'sage')] as $val => $lbl) {
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
    update_post_meta($post_id, '_mh_project_image', sanitize_text_field(wp_unslash($_POST['mh_project_image'] ?? '')));

    $productType = sanitize_key((string) wp_unslash($_POST['mh_project_product_type'] ?? 'theme'));
    if (! in_array($productType, ['theme', 'plugin', 'concept'], true)) {
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

/** Primary buy label for a project (theme vs plugin). */
function mh_project_buy_label(int $project_id): string
{
    if ($project_id > 0 && mh_project_product_type($project_id) === 'plugin') {
        return __('Buy plugin', 'sage');
    }

    return __('Buy theme', 'sage');
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
        foreach (['theme-'.$slug, 'plugin-'.$slug] as $trySku) {
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

    return $saved;
}

/** Sync every project into WooCommerce (idempotent). */
function mh_sync_all_project_products(): void
{
    if (! mh_shop_ready()) {
        return;
    }

    foreach (mh_query_project_cards(['live_only' => false]) as $card) {
        $id = (int) ($card['post_id'] ?? 0);
        if ($id > 0) {
            mh_sync_project_product($id);
        }
    }
}

/** One-time product seed plus digital-store defaults. */
function mh_seed_project_products(): void
{
    if (! mh_shop_ready() || wp_installing()) {
        return;
    }

    if (! get_option('mh_woocommerce_digital_store_seeded_v1')) {
        update_option('woocommerce_cart_redirect_after_add', 'yes');
        update_option('woocommerce_enable_guest_checkout', 'yes');
        update_option('woocommerce_ship_to_countries', 'disabled');
        if ((string) get_option('woocommerce_default_country') === '') {
            update_option('woocommerce_default_country', 'US:PA');
        }
        update_option('mh_woocommerce_digital_store_seeded_v1', true);
    }

    if (get_option('mh_woocommerce_project_products_seeded_v1')) {
        return;
    }

    // Always mark seeded after the first attempt so a single SKU conflict
    // cannot take down every front-end request via woocommerce_init.
    try {
        mh_sync_all_project_products();
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('mh_seed_project_products: '.$e->getMessage());
        }
    } finally {
        update_option('mh_woocommerce_project_products_seeded_v1', true);
    }
}

/** Add-to-cart label that matches the linked project type. */
function mh_woocommerce_buy_label($product = null): string
{
    $productId = 0;
    if (is_object($product) && method_exists($product, 'get_id')) {
        $productId = (int) $product->get_id();
    } elseif (function_exists('wc_get_product')) {
        $current = wc_get_product(get_the_ID());
        $productId = $current ? (int) $current->get_id() : 0;
    }

    $projectId = $productId > 0 ? (int) get_post_meta($productId, '_mh_product_project_id', true) : 0;
    if ($projectId > 0) {
        return mh_project_buy_label($projectId);
    }

    return __('Buy theme', 'sage');
}

/** Get help link after the add-to-cart form on product pages. */
function mh_woocommerce_get_help_button(): void
{
    if (! function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product(get_the_ID());
    if (! $product) {
        return;
    }

    $projectId = (int) $product->get_meta('_mh_product_project_id');
    $help = home_url('/contact/');
    if ($projectId > 0) {
        $post = get_post($projectId);
        if ($post instanceof \WP_Post) {
            $help = mh_work_help_url(mh_project_post_to_card($post));
        }
    }

    $productName = $product->get_name();
    echo '<p class="product-help-actions">';
    printf(
        '<a class="btn btn-outline" href="%1$s">%2$s <span class="visually-hidden">%3$s — </span>%4$s</a>',
        esc_url($help),
        mh_svg_icon('mail', 16),
        esc_html($productName),
        esc_html__('Get help', 'sage')
    );
    if ($projectId > 0) {
        $projectUrl = get_permalink($projectId);
        if (is_string($projectUrl) && $projectUrl !== '') {
            printf(
                ' <a class="h-text-arrow" href="%1$s"><span class="visually-hidden">%2$s — </span>%3$s</a>',
                esc_url($projectUrl),
                esc_html($productName),
                esc_html__('View project', 'sage').' →'
            );
        }
    }
    echo '</p>';
}

/**
 * Product-type badge overlaid on the thumbnail in the shop loop.
 *
 * Fires on `woocommerce_before_shop_loop_item_title` at priority 15,
 * after the default thumbnail (priority 10) so the badge sits inside
 * the `.woocommerce-loop-product__link` anchor which is position:relative.
 */
function mh_woocommerce_loop_type_badge(): void
{
    global $product;
    if (! is_object($product) || ! method_exists($product, 'get_id')) {
        return;
    }

    $entry = mh_product_catalog_data((int) $product->get_id());
    $productType = (string) ($entry['product_type'] ?? 'theme');
    $eyebrow = trim((string) ($entry['eyebrow'] ?? ''));

    if ($eyebrow === '') {
        $eyebrow = match ($productType) {
            'plugin' => __('WordPress plugin', 'sage'),
            'app' => __('Web app', 'sage'),
            default => __('WordPress theme', 'sage'),
        };
    }

    // Only the short label for the badge (before the ·).
    $badgeLabel = explode(' · ', $eyebrow)[0];

    printf(
        '<span class="product-type-badge product-type-badge--%s" aria-label="%s">%s</span>',
        esc_attr($productType),
        esc_attr($eyebrow),
        esc_html($badgeLabel)
    );
}

/**
 * Tech-stack tags + live-demo pill after the price in the shop loop.
 *
 * Fires on `woocommerce_after_shop_loop_item_title` at priority 15,
 * after the default price (priority 10).
 */
function mh_woocommerce_loop_card_meta(): void
{
    global $product;
    if (! is_object($product) || ! method_exists($product, 'get_id')) {
        return;
    }

    $entry = mh_product_catalog_data((int) $product->get_id());
    $tech = is_array($entry['tech'] ?? null) ? (array) $entry['tech'] : [];
    $demoUrl = trim((string) ($entry['demo'] ?? ''));

    if ($tech === [] && $demoUrl === '') {
        return;
    }

    echo '<div class="product-card-meta">';
    if ($tech !== []) {
        echo '<div class="product-tech-tags" aria-label="'.esc_attr__('Stack', 'sage').'">';
        foreach (array_slice($tech, 0, 4) as $t) {
            printf('<span class="product-tech-tag">%s</span>', esc_html((string) $t));
        }
        echo '</div>';
    }
    if ($demoUrl !== '') {
        printf(
            '<span class="product-demo-badge">%s %s</span>',
            mh_svg_icon('arrow-up-right', 10),
            esc_html__('Live demo', 'sage')
        );
    }
    echo '</div>';
}

/** Get help under each shop-loop add-to-cart button. */
function mh_woocommerce_loop_get_help(): void
{
    global $product;
    if (! is_object($product) || ! method_exists($product, 'get_id')) {
        return;
    }

    $projectId = (int) $product->get_meta('_mh_product_project_id');
    $help = home_url('/contact/');
    if ($projectId > 0) {
        $post = get_post($projectId);
        if ($post instanceof \WP_Post) {
            $help = mh_work_help_url(mh_project_post_to_card($post));
        }
    }

    $name = method_exists($product, 'get_name') ? (string) $product->get_name() : get_the_title();
    printf(
        '<a class="btn btn-outline product-loop-help" href="%1$s"><span class="visually-hidden">%2$s — </span>%3$s</a>',
        esc_url($help),
        esc_html($name),
        esc_html__('Get help', 'sage')
    );
}

add_action('woocommerce_init', __NAMESPACE__.'\\mh_seed_project_products', 30);
add_action('woocommerce_installed', __NAMESPACE__.'\\mh_seed_project_products');

/**
 * Re-sync all products to populate catalog-enriched descriptions.
 *
 * Runs once after theme update to push rich content (challenge, approach, benefits,
 * deliverables) into WooCommerce product descriptions so Rank Math scores improve.
 *
 * @since 3.2.0
 */
function mh_resync_product_descriptions_v3(): void
{
    if (! mh_shop_ready() || wp_installing()) {
        return;
    }

    if (get_option('mh_product_descriptions_synced_v3')) {
        return;
    }

    try {
        mh_sync_all_project_products();
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('mh_resync_product_descriptions_v3: '.$e->getMessage());
        }
    } finally {
        update_option('mh_product_descriptions_synced_v3', true);
    }
}

add_action('woocommerce_init', __NAMESPACE__.'\\mh_resync_product_descriptions_v3', 35);

/**
 * Re-syncs all CPT-linked products after the dedup fix that corrected the
 * project→product meta pointers.  The v3 resync updated the stub duplicates
 * (now trashed); this v4 run ensures the canonical products receive the
 * current rich descriptions and correct prices.
 *
 * @since 3.5.3
 */
function mh_resync_product_descriptions_v4(): void
{
    if (! mh_shop_ready() || wp_installing()) {
        return;
    }

    if (get_option('mh_product_descriptions_synced_v4')) {
        return;
    }

    try {
        mh_sync_all_project_products();
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('mh_resync_product_descriptions_v4: '.$e->getMessage());
        }
    } finally {
        update_option('mh_product_descriptions_synced_v4', true);
    }
}

add_action('woocommerce_init', __NAMESPACE__.'\\mh_resync_product_descriptions_v4', 35);

/**
 * Sync WooCommerce products that are defined in the product catalog JSON but
 * do NOT have a corresponding project CPT post (e.g. WalkRidge).  Runs once
 * per theme update via a versioned option.
 *
 * For each catalog entry with `is_product: true` and `for_sale: true` this
 * function finds the existing WC product by SKU (`theme-<slug>` /
 * `plugin-<slug>`) or by slug and updates its price, description, and SEO
 * meta.  It never creates a new product — creation is intentional and done
 * via wp-admin or the add-product workflow.
 *
 * @since 3.5.3
 */
function mh_resync_catalog_only_products_v1(): void
{
    if (! mh_shop_ready() || wp_installing()) {
        return;
    }

    if (get_option('mh_catalog_only_products_synced_v1')) {
        return;
    }

    try {
        $catalogPath = get_theme_file_path('resources/data/product-catalog.json');
        if (! is_readable($catalogPath)) {
            return;
        }
        $catalog = json_decode((string) file_get_contents($catalogPath), true);
        if (! is_array($catalog)) {
            return;
        }

        foreach ($catalog as $slugKey => $entry) {
            if (empty($entry['is_product']) || empty($entry['for_sale'])) {
                continue;
            }

            $type = sanitize_key((string) ($entry['product_type'] ?? 'theme'));
            $skuPrefix = $type === 'plugin' ? 'plugin-' : 'theme-';
            $sku = $skuPrefix.$slugKey;

            $productId = 0;
            if (function_exists('wc_get_product_id_by_sku')) {
                $productId = (int) wc_get_product_id_by_sku($sku);
            }
            if ($productId <= 0) {
                $bySlug = get_posts([
                    'post_type' => 'product',
                    'name' => $slugKey,
                    'post_status' => ['publish', 'private', 'draft'],
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'no_found_rows' => true,
                ]);
                $productId = $bySlug !== [] ? (int) $bySlug[0] : 0;
            }

            if ($productId <= 0) {
                continue;
            }

            $product = wc_get_product($productId);
            if (! $product instanceof \WC_Product) {
                continue;
            }

            // Only update if the product is not already linked to a CPT project
            // (those are handled by mh_sync_all_project_products).
            $linkedProjectId = (int) get_post_meta($productId, '_mh_product_project_id', true);
            if ($linkedProjectId > 0 && get_post_type($linkedProjectId) === mh_project_post_type()) {
                continue;
            }

            $blurb = trim((string) ($entry['blurb'] ?? ''));
            $richDescription = mh_product_description_html($entry);
            if ($richDescription === '') {
                $richDescription = trim((string) ($entry['summary'] ?? $blurb));
            }

            if ($blurb !== '') {
                $product->set_short_description($blurb);
            }
            if ($richDescription !== '') {
                $product->set_description($richDescription);
            }

            $price = trim((string) ($entry['price'] ?? ''));
            if ($price !== '' && is_numeric($price)) {
                $product->set_regular_price($price);
            }

            // Ensure correct SKU is set.
            $currentSku = (string) $product->get_sku();
            if ($currentSku === '') {
                try {
                    $product->set_sku($sku);
                } catch (\WC_Data_Exception $e) {
                    // SKU conflict — another product owns it; skip SKU update.
                }
            }

            $product->set_virtual(true);
            $product->set_sold_individually(true);
            $product->set_catalog_visibility('visible');
            $product->update_meta_data('_mh_product_type', $type);
            $product->save();

            // Set Rank Math SEO meta directly on the post.
            $postId = $product->get_id();
            $focusKw = trim((string) ($entry['ad_keywords'][0] ?? $entry['name'] ?? ''));
            if ($focusKw !== '' && get_post_meta($postId, 'rank_math_focus_keyword', true) === '') {
                update_post_meta($postId, 'rank_math_focus_keyword', $focusKw);
            }
            if ($blurb !== '' && get_post_meta($postId, 'rank_math_description', true) === '') {
                update_post_meta($postId, 'rank_math_description', $blurb);
            }
        }
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('mh_resync_catalog_only_products_v1: '.$e->getMessage());
        }
    } finally {
        update_option('mh_catalog_only_products_synced_v1', true);
    }
}

add_action('woocommerce_init', __NAMESPACE__.'\\mh_resync_catalog_only_products_v1', 36);

/**
 * Hide publish stub products that have no SKU (acreline-2 style duplicates)
 * from the shop catalog. Does not trash or delete posts.
 *
 * Runs on `init` (not `woocommerce_init`) so product meta is queryable.
 *
 * @since 3.5.9
 */
function mh_hide_sku_less_product_stubs_v1(): void
{
    if (! mh_shop_ready() || wp_installing()) {
        return;
    }

    if (get_option('mh_hide_sku_less_product_stubs_v1')) {
        return;
    }

    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    foreach ($ids as $id) {
        $id = (int) $id;
        $product = wc_get_product($id);
        if (! $product instanceof \WC_Product) {
            continue;
        }
        if ((string) $product->get_sku() !== '') {
            continue;
        }
        if ($product->get_catalog_visibility() === 'hidden') {
            continue;
        }
        $product->set_catalog_visibility('hidden');
        $product->save();
    }

    update_option('mh_hide_sku_less_product_stubs_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_hide_sku_less_product_stubs_v1', 50);

/**
 * Supply Rank Math with a meta description from the product catalog blurb.
 *
 * Rank Math reads the post excerpt for products; the WooCommerce short_description
 * is stored as post_excerpt. This filter catches any remaining gap for product
 * archives or single pages where Rank Math may pull a generic description.
 *
 * @since 3.2.0
 */
function mh_rank_math_product_description(string $desc): string
{
    if (! function_exists('is_product') || ! is_product()) {
        return $desc;
    }

    if ($desc !== '') {
        return $desc;
    }

    $product_id = (int) get_queried_object_id();
    $entry = mh_product_catalog_data($product_id);
    $blurb = trim((string) ($entry['blurb'] ?? ''));

    return $blurb !== '' ? $blurb : $desc;
}

add_filter('rank_math/frontend/description', __NAMESPACE__.'\\mh_rank_math_product_description');
add_filter('wpseo_metadesc', __NAMESPACE__.'\\mh_rank_math_product_description');

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

add_filter('woocommerce_return_to_shop_redirect', __NAMESPACE__.'\\mh_theme_catalog_url');
add_filter('woocommerce_product_get_permalink', __NAMESPACE__.'\\mh_filter_product_permalink', 10, 2);
add_action('template_redirect', __NAMESPACE__.'\\mh_redirect_product_to_project', 5);
add_filter('loop_shop_columns', fn (): int => 3);

/**
 * Output a short product blurb in the shop loop for UX and SEO.
 *
 * Priority 6 places it after the star-rating (5) and before the price (10).
 */
add_action('woocommerce_after_shop_loop_item_title', function (): void {
    global $product;
    if (! is_object($product) || ! method_exists($product, 'get_id')) {
        return;
    }

    $entry = mh_product_catalog_data((int) $product->get_id());
    $blurb = trim((string) ($entry['blurb'] ?? ''));

    if ($blurb === '') {
        $blurb = wp_trim_words(wp_strip_all_tags((string) $product->get_short_description()), 20, '…');
    }

    if ($blurb !== '') {
        printf('<p class="product-loop-blurb">%s</p>', esc_html($blurb));
    }
}, 6);

/** Eager-load the first two shop-loop thumbnails for LCP; lazy-load the rest. */
add_filter('wp_get_attachment_image_attributes', function (array $attr): array {
    if (! function_exists('is_shop') || ! is_shop() || ! in_the_loop()) {
        return $attr;
    }
    if (get_post_type() !== 'product') {
        return $attr;
    }

    static $n = 0;
    $n++;
    $attr['decoding'] = 'async';
    if ($n <= 2) {
        $attr['fetchpriority'] = 'high';
        $attr['loading'] = 'eager';
    } else {
        $attr['loading'] = 'lazy';
    }

    return $attr;
});

/**
 * Inject the catalog featured image when the WC product has no thumbnail set.
 *
 * WooCommerce calls get_image() for the loop thumbnail; the result is filtered
 * here so the catalog `image` key provides the fallback instead of the WC
 * placeholder graphic.
 */
add_filter('woocommerce_product_get_image', function (string $html, \WC_Product $product): string {
    // If WC has a real attachment, honour it.
    if ((int) $product->get_image_id() > 0) {
        return $html;
    }

    $entry = mh_product_catalog_data((int) $product->get_id());
    $imgPath = trim((string) ($entry['image'] ?? ''));
    if ($imgPath === '') {
        return $html;
    }

    $src = get_theme_file_uri('resources/images/'.$imgPath);
    $name = esc_attr(html_entity_decode((string) $product->get_name(), ENT_QUOTES, 'UTF-8'));

    return '<img src="'.esc_url($src).'" alt="'.$name.'" width="800" height="534" class="mh-catalog-img wp-post-image" loading="lazy" decoding="async">';
}, 10, 2);

/**
 * Add a `mh-type-{type}` class to each product <li> in the shop loop so the
 * client-side catalog filter can show/hide items without a page navigation.
 */
add_filter('woocommerce_post_class', function (array $classes, \WC_Product $product): array {
    $entry = mh_product_catalog_data((int) $product->get_id());
    $productType = trim((string) ($entry['product_type'] ?? 'theme')) ?: 'theme';
    $classes[] = 'mh-type-'.sanitize_html_class($productType);

    return $classes;
}, 10, 2);
add_filter('woocommerce_product_single_add_to_cart_text', function ($text, $product = null) {
    return mh_woocommerce_buy_label($product);
}, 10, 2);
add_filter('woocommerce_product_add_to_cart_text', function ($text, $product = null) {
    return mh_woocommerce_buy_label($product);
}, 10, 2);
add_action('woocommerce_after_add_to_cart_form', __NAMESPACE__.'\\mh_woocommerce_get_help_button');
add_action('woocommerce_after_shop_loop_item', __NAMESPACE__.'\\mh_woocommerce_loop_get_help', 15);
add_action('woocommerce_before_shop_loop_item_title', __NAMESPACE__.'\\mh_woocommerce_loop_type_badge', 15);
add_action('woocommerce_after_shop_loop_item_title', __NAMESPACE__.'\\mh_woocommerce_loop_card_meta', 15);
