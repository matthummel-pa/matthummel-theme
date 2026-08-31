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
        'permalink' => $product->get_permalink(),
        'add_to_cart_url' => mh_product_add_to_cart_url($product_id),
        'short_description' => wp_strip_all_tags((string) $product->get_short_description()),
    ];
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

/** Default USD price for a project theme when none is set on the project. */
function mh_default_theme_price(): string
{
    return '149';
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
    return home_url('/projects/');
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

/** Product category used for synced project themes. */
function mh_woocommerce_themes_category_id(): int
{
    if (! taxonomy_exists('product_cat')) {
        return 0;
    }

    $term = get_term_by('slug', 'themes', 'product_cat');
    if ($term instanceof \WP_Term) {
        return (int) $term->term_id;
    }

    $created = wp_insert_term(__('Themes', 'sage'), 'product_cat', [
        'slug' => 'themes',
    ]);
    if (is_wp_error($created)) {
        return 0;
    }

    return (int) ($created['term_id'] ?? 0);
}

/**
 * Resolve an existing WooCommerce product for a project.
 */
function mh_find_product_id_for_project(int $project_id, string $slug): int
{
    $id = (int) get_post_meta($project_id, '_mh_project_product_id', true);
    if ($id > 0 && get_post_type($id) === 'product') {
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

    if ($slug !== '' && function_exists('wc_get_product_id_by_sku')) {
        $bySku = (int) wc_get_product_id_by_sku('theme-'.$slug);
        if ($bySku > 0) {
            return $bySku;
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
    $sku = 'theme-'.($slug !== '' ? $slug : (string) $project_id);
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

    $product->set_name($post->post_title);
    if ($slug !== '') {
        $product->set_slug($slug);
    }
    $product->set_virtual(true);
    $product->set_sold_individually(true);
    $product->set_catalog_visibility('visible');
    $product->set_short_description($blurb);
    $product->set_description($summary);

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
                    $product->set_description($summary);
                }
            }
        }
    }

    $price = trim((string) get_post_meta($project_id, '_mh_project_price', true));
    if ($price !== '' && is_numeric($price)) {
        $product->set_regular_price($price);
    } elseif ($isNew || (string) $product->get_regular_price() === '') {
        $product->set_regular_price(mh_default_theme_price());
    }

    $live = mh_project_is_live($project_id) && $post->post_status === 'publish';
    $forSale = get_post_meta($project_id, '_mh_project_for_sale', true) !== '0';
    $product->set_status($live && $forSale ? 'publish' : 'private');

    $thumb = (int) get_post_thumbnail_id($project_id);
    if ($thumb > 0) {
        $product->set_image_id($thumb);
    }

    $catId = mh_woocommerce_themes_category_id();
    if ($catId > 0) {
        $product->set_category_ids([$catId]);
    }

    $product->update_meta_data('_mh_product_project_id', $project_id);
    $saved = (int) $product->save();
    if ($saved <= 0) {
        return 0;
    }

    update_post_meta($project_id, '_mh_project_product_id', (string) $saved);
    $type = sanitize_key((string) get_post_meta($project_id, '_mh_project_product_type', true));
    if ($type === '') {
        update_post_meta($project_id, '_mh_project_product_type', 'theme');
    }

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

add_action('save_post_'.mh_project_post_type(), function (int $post_id): void {
    if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
        return;
    }
    mh_sync_project_product($post_id);
}, 30);

add_filter('woocommerce_return_to_shop_redirect', __NAMESPACE__.'\\mh_theme_catalog_url');
add_filter('loop_shop_columns', fn (): int => 3);
add_filter('woocommerce_product_single_add_to_cart_text', function ($text, $product = null) {
    return mh_woocommerce_buy_label($product);
}, 10, 2);
add_filter('woocommerce_product_add_to_cart_text', function ($text, $product = null) {
    return mh_woocommerce_buy_label($product);
}, 10, 2);
add_action('woocommerce_after_add_to_cart_form', __NAMESPACE__.'\\mh_woocommerce_get_help_button');
add_action('woocommerce_after_shop_loop_item', __NAMESPACE__.'\\mh_woocommerce_loop_get_help', 15);
