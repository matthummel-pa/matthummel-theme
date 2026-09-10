<?php

/**
 * WooCommerce support, classic shortcode pages, and Blade wrappers.
 *
 * Required storefront pages (Shop, Cart, Checkout, My account) are created
 * when WooCommerce is active. Cart / Checkout / Account use Blade templates
 * that render classic shortcodes — WooCommerce 9+ otherwise ships block pages
 * that ignore theme templates.
 */

namespace App;

/** Whether the WooCommerce plugin is loaded. */
function mh_woocommerce_is_active(): bool
{
    return class_exists('WooCommerce', false) || defined('WC_VERSION');
}

/**
 * WooCommerce page IDs used by the theme (0 when unset).
 *
 * @return array{shop: int, cart: int, checkout: int, myaccount: int, terms: int}
 */
function mh_woocommerce_page_ids(): array
{
    return [
        'shop' => (int) get_option('woocommerce_shop_page_id'),
        'cart' => (int) get_option('woocommerce_cart_page_id'),
        'checkout' => (int) get_option('woocommerce_checkout_page_id'),
        'myaccount' => (int) get_option('woocommerce_myaccount_page_id'),
        'terms' => (int) get_option('woocommerce_terms_page_id'),
    ];
}

/**
 * Specs for the required WooCommerce pages.
 *
 * @return list<array{slug: string, title: string, option: string, shortcode: string, template: string}>
 */
function mh_woocommerce_page_specs(): array
{
    return [
        [
            'slug' => 'shop',
            'title' => __('Shop', 'sage'),
            'option' => 'woocommerce_shop_page_id',
            'shortcode' => '',
            'template' => '',
        ],
        [
            'slug' => 'cart',
            'title' => __('Cart', 'sage'),
            'option' => 'woocommerce_cart_page_id',
            'shortcode' => '[woocommerce_cart]',
            'template' => 'template-woocommerce.blade.php',
        ],
        [
            'slug' => 'checkout',
            'title' => __('Checkout', 'sage'),
            'option' => 'woocommerce_checkout_page_id',
            'shortcode' => '[woocommerce_checkout]',
            'template' => 'template-woocommerce.blade.php',
        ],
        [
            'slug' => 'my-account',
            'title' => __('My account', 'sage'),
            'option' => 'woocommerce_myaccount_page_id',
            'shortcode' => '[woocommerce_my_account]',
            'template' => 'template-woocommerce.blade.php',
        ],
    ];
}

/**
 * Create or update one WooCommerce page and point the matching option at it.
 */
function mh_ensure_woocommerce_page(array $spec): int
{
    $slug = sanitize_title((string) ($spec['slug'] ?? ''));
    $title = (string) ($spec['title'] ?? '');
    $option = (string) ($spec['option'] ?? '');
    $shortcode = (string) ($spec['shortcode'] ?? '');
    $template = (string) ($spec['template'] ?? '');

    if ($slug === '' || $option === '') {
        return 0;
    }

    $id = (int) get_option($option);
    $post = $id > 0 ? get_post($id) : null;
    if (! $post instanceof \WP_Post || $post->post_type !== 'page') {
        $byPath = get_page_by_path($slug);
        $post = $byPath instanceof \WP_Post ? $byPath : null;
        $id = $post ? (int) $post->ID : 0;
    }

    $payload = [
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => $shortcode,
    ];

    if ($id > 0) {
        $payload['ID'] = $id;
        if ($post instanceof \WP_Post && $post->post_title !== '') {
            unset($payload['post_title']);
        }
        $saved = wp_update_post($payload, true);
    } else {
        $saved = wp_insert_post($payload, true);
    }

    if (is_wp_error($saved) || ! $saved) {
        return 0;
    }

    $id = (int) $saved;
    if ($template !== '') {
        update_post_meta($id, '_wp_page_template', $template);
    }
    update_option($option, $id);

    return $id;
}

/** Seed Shop, Cart, Checkout, and My account; turn off Coming soon. */
function mh_seed_woocommerce_pages(): void
{
    if (! mh_woocommerce_is_active() || wp_installing()) {
        return;
    }

    foreach (mh_woocommerce_page_specs() as $spec) {
        mh_ensure_woocommerce_page($spec);
    }

    $terms = get_page_by_path('terms');
    if ($terms instanceof \WP_Post && (int) get_option('woocommerce_terms_page_id') === 0) {
        update_option('woocommerce_terms_page_id', (int) $terms->ID);
    }

    update_option('woocommerce_coming_soon', 'no');
    if (get_option('woocommerce_store_pages_only') === 'yes') {
        update_option('woocommerce_store_pages_only', 'no');
    }

    if (! get_option('mh_woocommerce_pages_seeded_v1')) {
        update_option('mh_woocommerce_pages_seeded_v1', true);
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }
}

/** Classic shortcode for the current WooCommerce page template. */
function mh_woocommerce_page_shortcode(): string
{
    if (! mh_woocommerce_is_active()) {
        return '';
    }
    if (function_exists('is_cart') && is_cart()) {
        return '[woocommerce_cart]';
    }
    if (function_exists('is_checkout') && is_checkout()) {
        return '[woocommerce_checkout]';
    }
    if (function_exists('is_account_page') && is_account_page()) {
        return '[woocommerce_my_account]';
    }

    return '';
}

add_action('after_setup_theme', function (): void {
    if (! mh_woocommerce_is_active()) {
        return;
    }

    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 480,
        'single_image_width' => 720,
        'product_grid' => [
            'default_rows' => 3,
            'min_rows' => 1,
            'max_rows' => 8,
            'default_columns' => 3,
            'min_columns' => 1,
            'max_columns' => 4,
        ],
    ]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}, 20);

add_action('wp', function (): void {
    if (! mh_woocommerce_is_active()) {
        return;
    }

    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    // Theme Blade heroes include breadcrumbs; drop the default WC trail to avoid duplicates.
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
    // Hero already prints the product name as the page H1.
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);

    add_action('woocommerce_before_main_content', function (): void {
        $mod = '';
        if (function_exists('is_cart') && is_cart()) {
            $mod = ' woocommerce-wrap--cart';
        } elseif (function_exists('is_checkout') && is_checkout()) {
            $mod = ' woocommerce-wrap--checkout';
        } elseif (function_exists('is_account_page') && is_account_page()) {
            $mod = ' woocommerce-wrap--account';
        } elseif (function_exists('is_product') && is_product()) {
            $mod = ' woocommerce-wrap--product';
        } elseif (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
            $mod = ' woocommerce-wrap--shop';
        }
        echo '<div class="container wide page-block woocommerce-wrap'.esc_attr($mod).'">';
    }, 10);

    add_action('woocommerce_after_main_content', function (): void {
        echo '</div>';
    }, 10);
});

// ── Cart: accessible scroll wrapper on the table ──────────────────────────────
add_action('woocommerce_before_cart_table', function (): void {
    echo '<div class="shop-table-scroll" tabindex="0" role="region" aria-label="'.esc_attr__('Cart items', 'sage').'">';
}, 5);
add_action('woocommerce_after_cart_table', function (): void {
    echo '</div>';
}, 50);

// ── Checkout: accessible scroll wrapper around order review ───────────────────
add_action('woocommerce_checkout_before_order_review', function (): void {
    echo '<div class="shop-table-scroll" tabindex="0" role="region" aria-label="'.esc_attr__('Order review', 'sage').'">';
}, 5);
add_action('woocommerce_checkout_after_order_review', function (): void {
    echo '</div>';
}, 50);

// ── Checkout: trust badge row below the payment button ────────────────────────
add_action('woocommerce_review_order_after_submit', function (): void {
    echo '<div class="checkout-trust-signals" aria-label="'.esc_attr__('Checkout security', 'sage').'">';
    echo '<span class="checkout-trust-signal">'.esc_html__('SSL encrypted', 'sage').'</span>';
    echo '<span class="checkout-trust-signal">'.esc_html__('GPL license', 'sage').'</span>';
    echo '<span class="checkout-trust-signal">'.esc_html__('Instant download', 'sage').'</span>';
    echo '</div>';
}, 15);

// ── Cart: "continue shopping" link at top of cart ────────────────────────────
add_action('woocommerce_before_cart', function (): void {
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    echo '<div class="woo-cart-header-actions">';
    echo '<a class="woo-continue-link" href="'.esc_url($shopUrl).'">← '.esc_html__('Continue shopping', 'sage').'</a>';
    echo '</div>';
}, 5);

// ── Product loop: stamp mh-type-* class onto each product <li> ───────────────
// Used by the JS filter in shop-filter.js. The product loop <li> classes come
// from post_class(), so we filter that hook for product post types.
add_filter('post_class', function (array $classes, array $class, int $postId): array {
    if (get_post_type($postId) !== 'product') {
        return $classes;
    }

    $type = mh_resolve_product_type($postId);
    $classes[] = 'mh-type-'.sanitize_html_class($type);

    return $classes;
}, 10, 3);

// ── Shop archive: remove star ratings (not relevant for digital products) ─────
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);

// ── My account: add download shortcut link in account nav ────────────────────
add_filter('woocommerce_account_menu_items', function (array $items): array {
    // Ensure Downloads appears early and prominently (position 2).
    if (isset($items['downloads'])) {
        $dl = ['downloads' => $items['downloads']];
        unset($items['downloads']);
        $keys = array_keys($items);
        $values = array_values($items);
        $pos = min(1, count($keys));
        array_splice($keys, $pos, 0, array_keys($dl));
        array_splice($values, $pos, 0, array_values($dl));
        $items = array_combine($keys, $values);
    }

    return $items;
});

// ── Checkout: set custom order button text ────────────────────────────────────
add_filter('woocommerce_order_button_text', fn (): string => __('Complete purchase →', 'sage'));

// ── Checkout: remove default "Your order" heading (we style it via CSS) ───────
add_filter('woocommerce_checkout_order_review_heading', fn (): string => __('Order summary', 'sage'));

add_action('init', __NAMESPACE__.'\\mh_seed_woocommerce_pages', 42);
add_action('woocommerce_installed', __NAMESPACE__.'\\mh_seed_woocommerce_pages');
