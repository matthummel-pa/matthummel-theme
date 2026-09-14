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
        // Shop Blade already sits in `.container.wide`. A second container here
        // shrinks the product grid on tablet and phone.
        if ($mod === ' woocommerce-wrap--shop') {
            echo '<div class="woocommerce-wrap'.esc_attr($mod).'">';

            return;
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

// ── Cart / Checkout desk layout (article left, tools sidebar right) ───────────
add_action('woocommerce_before_cart', function (): void {
    if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return;
    }
    echo '<div class="woo-desk">';
    echo '<div class="woo-desk__layout">';
    echo '<div class="woo-desk__main">';
}, 1);

add_action('woocommerce_before_cart_collaterals', function (): void {
    if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return;
    }
    echo '</div>'; // .woo-desk__main
    echo '<aside class="woo-desk__aside" aria-label="'.esc_attr__('Order tools', 'sage').'">';
}, 1);

add_action('woocommerce_cart_collaterals', function (): void {
    if (! function_exists('is_cart') || ! is_cart()) {
        return;
    }
    mh_render_woo_desk_aside('cart');
}, 15);

add_action('woocommerce_after_cart', function (): void {
    if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return;
    }
    echo '</aside>'; // .woo-desk__aside
    echo '</div>'; // .woo-desk__layout
    echo '</div>'; // .woo-desk
    mh_render_woo_cart_mobile_bar();
}, 5);

remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

add_action('woocommerce_checkout_before_customer_details', function (): void {
    echo '<div class="woo-desk__main">';
}, 1);

add_action('woocommerce_checkout_after_customer_details', function (): void {
    echo '</div>'; // .woo-desk__main
}, 99);

add_action('woocommerce_checkout_before_order_review_heading', function (): void {
    echo '<aside class="woo-desk__aside" aria-label="'.esc_attr__('Order summary and tools', 'sage').'">';
}, 1);

add_action('woocommerce_checkout_after_order_review', function (): void {
    mh_render_woo_desk_aside('checkout');
    echo '</aside>'; // .woo-desk__aside
}, 60);

/** Sticky mobile checkout CTA under the cart (desktop uses the sidebar). */
function mh_render_woo_cart_mobile_bar(): void
{
    if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return;
    }
    $checkout = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    $total = WC()->cart->get_cart_total();
    $count = (int) WC()->cart->get_cart_contents_count();
    echo '<div class="woo-cart-mobile-bar" data-mh-cart-mobile-bar>';
    echo '<div class="woo-cart-mobile-bar__inner">';
    echo '<p class="woo-cart-mobile-bar__meta">';
    echo '<span>'.esc_html(sprintf(_n('%d item', '%d items', $count, 'sage'), $count)).'</span>';
    echo '<strong class="woo-cart-mobile-bar__total">'.wp_kses_post($total).'</strong>';
    echo '</p>';
    echo '<a class="btn woo-cart-mobile-bar__cta" href="'.esc_url($checkout).'">'.esc_html__('Continue to checkout', 'sage').'</a>';
    echo '</div></div>';
}

// Type badge + short blurb under cart line names.
add_action('woocommerce_after_cart_item_name', function (array $cart_item, string $cart_item_key): void {
    $id = (int) ($cart_item['product_id'] ?? 0);
    if ($id <= 0) {
        return;
    }
    $type = mh_resolve_product_type($id);
    $label = mh_cart_type_label($type);
    $blurb = '';
    $payload = mh_shop_product_payload($id);
    if (is_array($payload)) {
        $blurb = trim((string) ($payload['short_description'] ?? ''));
    }
    if ($blurb === '' && function_exists('App\\mh_product_entry')) {
        $entry = mh_product_entry($id);
        if (is_array($entry)) {
            $blurb = trim(wp_strip_all_tags((string) ($entry['blurb'] ?? $entry['summary'] ?? '')));
        }
    }
    if (strlen($blurb) > 110) {
        $blurb = rtrim(substr($blurb, 0, 107)).'…';
    }
    echo '<span class="woo-cart-line__meta">';
    echo '<span class="woo-cart-line__type">'.esc_html($label).'</span>';
    if ($blurb !== '') {
        echo '<span class="woo-cart-line__blurb">'.esc_html($blurb).'</span>';
    }
    echo '</span>';
}, 10, 2);

// Hide quantity UI when every line is sold individually (digital packs).
add_filter('body_class', function (array $classes): array {
    if (! function_exists('is_cart') || ! is_cart() || ! function_exists('WC') || ! WC()->cart) {
        return $classes;
    }
    foreach (WC()->cart->get_cart() as $item) {
        $product = $item['data'] ?? null;
        if (! $product instanceof \WC_Product || ! $product->is_sold_individually()) {
            return $classes;
        }
    }
    if (WC()->cart->is_empty()) {
        return $classes;
    }
    $classes[] = 'mh-cart-sold-individually';

    return $classes;
});

// ── Checkout: accessible scroll wrapper around order review ───────────────────
add_action('woocommerce_checkout_before_order_review', function (): void {
    echo '<div class="shop-table-scroll" tabindex="0" role="region" aria-label="'.esc_attr__('Order review', 'sage').'">';
}, 5);
add_action('woocommerce_checkout_after_order_review', function (): void {
    echo '</div>';
}, 50);

/**
 * Product types currently in the cart.
 *
 * @return list<string>
 */
function mh_cart_product_types(): array
{
    if (! function_exists('WC') || ! WC()->cart) {
        return [];
    }

    $types = [];
    foreach (WC()->cart->get_cart() as $item) {
        $id = (int) ($item['product_id'] ?? 0);
        if ($id > 0) {
            $types[] = mh_resolve_product_type($id);
        }
    }

    return array_values(array_unique($types));
}

/** Whether every cart line is a service (no theme/plugin zip). */
function mh_cart_is_services_only(): bool
{
    $types = mh_cart_product_types();

    return $types !== [] && count(array_diff($types, ['service'])) === 0;
}

/**
 * Trust lines for cart / checkout chrome.
 *
 * @return list<array{icon: string, label: string, href?: string}>
 */
function mh_checkout_trust_items(): array
{
    if (mh_cart_is_services_only()) {
        return [
            ['icon' => 'check', 'label' => __('Written scope after payment', 'sage')],
            ['icon' => 'mail', 'label' => __('I reply within one business day', 'sage')],
            ['icon' => 'check', 'label' => __('SSL-encrypted payment', 'sage')],
            ['icon' => 'mail', 'label' => __('Questions? Say hello', 'sage'), 'href' => home_url('/contact/')],
        ];
    }

    return [
        ['icon' => 'check', 'label' => __('GPL license — you own the code', 'sage')],
        ['icon' => 'download', 'label' => __('Download link in the receipt email', 'sage')],
        ['icon' => 'check', 'label' => __('SSL-encrypted payment', 'sage')],
        ['icon' => 'mail', 'label' => __('Questions? Say hello', 'sage'), 'href' => home_url('/contact/')],
    ];
}

/**
 * Numbered “what happens next” for the cart/checkout tools sidebar.
 *
 * @return list<array{title: string, text: string}>
 */
function mh_checkout_next_steps(): array
{
    if (mh_cart_is_services_only()) {
        return [
            [
                'title' => __('Pay once', 'sage'),
                'text' => __('Guest checkout is fine. No retainers.', 'sage'),
            ],
            [
                'title' => __('I write back', 'sage'),
                'text' => __('Kickoff email within one business day (ET).', 'sage'),
            ],
            [
                'title' => __('We ship the work', 'sage'),
                'text' => __('Notes from checkout guide the first pass.', 'sage'),
            ],
        ];
    }

    return [
        [
            'title' => __('Pay once', 'sage'),
            'text' => __('One screen. Guest checkout is fine.', 'sage'),
        ],
        [
            'title' => __('Get the zip', 'sage'),
            'text' => __('Download link lands in the receipt email.', 'sage'),
        ],
        [
            'title' => __('Install or hire me', 'sage'),
            'text' => __('Optional help chips stay on the order.', 'sage'),
        ],
    ];
}

/**
 * Short FAQ rows for the cart/checkout tools sidebar.
 *
 * @return list<array{q: string, a: string}>
 */
function mh_checkout_sidebar_faq(): array
{
    if (mh_cart_is_services_only()) {
        return [
            [
                'q' => __('Do I need an account?', 'sage'),
                'a' => __('No. Guest checkout works. I email next steps to the address you enter.', 'sage'),
            ],
            [
                'q' => __('When do we start?', 'sage'),
                'a' => __('After payment I reply within one business day with scope and timing.', 'sage'),
            ],
        ];
    }

    return [
        [
            'q' => __('Where is the download?', 'sage'),
            'a' => __('In the receipt email, and later under My account → Downloads.', 'sage'),
        ],
        [
            'q' => __('Do I need an account?', 'sage'),
            'a' => __('No. Guest checkout is fine. Use the same email if you want update notices.', 'sage'),
        ],
        [
            'q' => __('Can I get a refund?', 'sage'),
            'a' => __('Digital packs are final once the zip is delivered. Ask before you pay if unsure.', 'sage'),
        ],
    ];
}

/**
 * Suggested add-ons for the cart/checkout sidebar (not already in the cart).
 *
 * @return list<array{title: string, price: string, permalink: string, add_to_cart_url: string}>
 */
function mh_cart_sidebar_addons(int $limit = 3): array
{
    $inCart = [];
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $item) {
            $post = get_post((int) ($item['product_id'] ?? 0));
            if ($post instanceof \WP_Post) {
                $inCart[] = (string) $post->post_name;
            }
        }
    }

    $candidates = mh_cart_is_services_only()
        ? ['acreline', 'acreline-site-care', 'acreline-setup-launch']
        : ['acreline-express-install', 'acreline-setup-launch', 'acreline-site-care', 'tocflow'];

    $out = [];
    foreach ($candidates as $slug) {
        if (in_array($slug, $inCart, true)) {
            continue;
        }
        $id = function_exists('App\\mh_product_id_by_slug') ? mh_product_id_by_slug($slug) : 0;
        $payload = $id > 0 ? mh_shop_product_payload($id) : null;
        if (! is_array($payload) || empty($payload['add_to_cart_url'])) {
            continue;
        }
        $title = html_entity_decode((string) ($payload['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $price = html_entity_decode(wp_strip_all_tags((string) ($payload['price_html'] ?? '')), ENT_QUOTES, 'UTF-8');
        $price = trim(preg_replace('/\.00\b/', '', preg_replace('/\s+/', ' ', $price) ?? $price) ?? $price);
        $out[] = [
            'title' => $title,
            'price' => $price,
            'permalink' => (string) ($payload['permalink'] ?? ''),
            'add_to_cart_url' => (string) ($payload['add_to_cart_url'] ?? ''),
        ];
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

/** Human label for a product type badge on cart lines. */
function mh_cart_type_label(string $type): string
{
    return match ($type) {
        'plugin' => __('Plugin', 'sage'),
        'service' => __('Service', 'sage'),
        'app' => __('App', 'sage'),
        default => __('Theme', 'sage'),
    };
}

/** Render the sticky tools column for cart or checkout. */
function mh_render_woo_desk_aside(string $context): void
{
    if (! in_array($context, ['cart', 'checkout'], true)) {
        return;
    }
    echo view('partials.woo-desk-aside', [
        'context' => $context,
        'servicesOnly' => mh_cart_is_services_only(),
    ])->render();
}

/**
 * Suggested products when the cart is empty.
 *
 * @return list<array{title: string, price: string, permalink: string, add_to_cart_url: string}>
 */
function mh_checkout_start_here_products(): array
{
    $out = [];
    foreach (['acreline', 'acreline-express-install', 'acreline-setup-launch'] as $slug) {
        $id = function_exists('App\\mh_product_id_by_slug') ? mh_product_id_by_slug($slug) : 0;
        $payload = $id > 0 ? mh_shop_product_payload($id) : null;
        if (! is_array($payload)) {
            continue;
        }
        $title = html_entity_decode((string) ($payload['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $price = html_entity_decode(wp_strip_all_tags((string) ($payload['price_html'] ?? '')), ENT_QUOTES, 'UTF-8');
        $price = trim(preg_replace('/\.00\b/', '', preg_replace('/\s+/', ' ', $price) ?? $price) ?? $price);
        $out[] = [
            'title' => $title,
            'price' => $price,
            'permalink' => (string) ($payload['permalink'] ?? ''),
            'add_to_cart_url' => (string) ($payload['add_to_cart_url'] ?? ''),
        ];
    }

    return $out;
}

/**
 * Cart lines for the studio slip drawer.
 *
 * @return list<array{name: string, price: string, permalink: string, type: string}>
 */
function mh_studio_cart_lines(): array
{
    if (! function_exists('WC') || ! WC()->cart) {
        return [];
    }

    $out = [];
    foreach (WC()->cart->get_cart() as $item) {
        $product = $item['data'] ?? null;
        $id = (int) ($item['product_id'] ?? 0);
        if (! $product instanceof \WC_Product || $id <= 0) {
            continue;
        }
        $name = html_entity_decode($product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $line = WC()->cart->get_product_subtotal($product, (int) ($item['quantity'] ?? 1));
        $line = html_entity_decode(wp_strip_all_tags((string) $line), ENT_QUOTES, 'UTF-8');
        $out[] = [
            'name' => $name,
            'price' => trim(preg_replace('/\.00\b/', '', $line) ?? $line),
            'permalink' => $product->get_permalink(),
            'type' => mh_resolve_product_type($id),
        ];
    }

    return $out;
}

/** Cart total string for the studio slip. */
function mh_studio_cart_total(): string
{
    if (! function_exists('WC') || ! WC()->cart) {
        return '';
    }

    $total = html_entity_decode(wp_strip_all_tags((string) WC()->cart->get_cart_total()), ENT_QUOTES, 'UTF-8');

    return trim(preg_replace('/\.00\b/', '', $total) ?? $total);
}

// Stay on the page after add-to-cart and open the studio slip.
add_filter('woocommerce_add_to_cart_redirect', function (string $url): string {
    if (function_exists('is_checkout') && is_checkout()) {
        return $url;
    }
    $ref = wp_get_referer();
    if (! is_string($ref) || $ref === '') {
        return $url;
    }

    return add_query_arg('mh-slip', '1', remove_query_arg(['add-to-cart', 'added-to-cart'], $ref));
});

// ── Checkout: trust badge row below the payment button ────────────────────────
add_action('woocommerce_review_order_after_submit', function (): void {
    echo '<div class="checkout-trust-signals" aria-label="'.esc_attr__('Checkout security', 'sage').'">';
    if (mh_cart_is_services_only()) {
        echo '<span class="checkout-trust-signal">'.esc_html__('SSL encrypted', 'sage').'</span>';
        echo '<span class="checkout-trust-signal">'.esc_html__('I write back after payment', 'sage').'</span>';
        echo '<span class="checkout-trust-signal">'.esc_html__('No mailing list', 'sage').'</span>';
    } else {
        echo '<span class="checkout-trust-signal">'.esc_html__('SSL encrypted', 'sage').'</span>';
        echo '<span class="checkout-trust-signal">'.esc_html__('Download in the receipt', 'sage').'</span>';
        echo '<span class="checkout-trust-signal">'.esc_html__('No mailing list', 'sage').'</span>';
    }
    echo '</div>';
}, 15);

// ── Cart: "continue shopping" link at top of cart ────────────────────────────
add_action('woocommerce_before_cart', function (): void {
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    echo '<div class="woo-cart-header-actions">';
    echo '<a class="woo-continue-link" href="'.esc_url($shopUrl).'">← '.esc_html__('Continue shopping', 'sage').'</a>';
    echo '</div>';
}, 5);

// ── Shop archive: remove star ratings (not relevant for digital products) ─────
// mh-type-* loop classes live on woocommerce_post_class in shop.php.
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
add_filter('woocommerce_order_button_text', function (): string {
    return mh_cart_is_services_only()
        ? __('Book this work', 'sage')
        : __('Pay and get access', 'sage');
});

// ── Checkout: remove default "Your order" heading (we style it via CSS) ───────
add_filter('woocommerce_checkout_order_review_heading', fn (): string => __('Order summary', 'sage'));

// form-checkout.php hardcodes “Your order”; map it on checkout screens.
add_filter('gettext', function (string $translation, string $text, string $domain): string {
    if ($domain !== 'woocommerce' || $text !== 'Your order') {
        return $translation;
    }
    if (! function_exists('is_checkout') || ! is_checkout()) {
        return $translation;
    }

    return __('Order summary', 'sage');
}, 20, 3);

// ── Cart: clearer proceed CTA ─────────────────────────────────────────────────
remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
add_action('woocommerce_proceed_to_checkout', function (): void {
    $url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    echo '<a href="'.esc_url($url).'" class="checkout-button button alt wc-forward">';
    echo esc_html__('Continue to checkout', 'sage');
    echo '</a>';
    echo '<p class="woo-checkout-nudge">'.esc_html__('One more screen. Guest checkout is fine.', 'sage').'</p>';
}, 20);

// ── Checkout: optional install wants / needs ─────────────────────────────────
add_filter('woocommerce_enable_order_notes_field', '__return_true');

add_filter('woocommerce_checkout_fields', function (array $fields): array {
    if (! isset($fields['order']['order_comments']) || ! is_array($fields['order']['order_comments'])) {
        $fields['order']['order_comments'] = [
            'type' => 'textarea',
            'class' => ['notes'],
        ];
    }

    $hasService = in_array('service', mh_cart_product_types(), true);
    $fields['order']['order_comments']['label'] = $hasService
        ? __('Anything else I should know', 'sage')
        : __('Notes for after purchase', 'sage');
    $fields['order']['order_comments']['placeholder'] = $hasService
        ? __('Domain, brand colors, MLS, listings to import, launch date…', 'sage')
        : __('Site URL, colors, or anything I should know if you want help installing.', 'sage');
    $fields['order']['order_comments']['required'] = false;
    $fields['order']['order_comments']['maxlength'] = 2000;
    $fields['order']['order_comments']['class'][] = 'mh-install-notes__field';
    $fields['order']['order_comments']['priority'] = 20;

    return $fields;
});

add_action('woocommerce_before_order_notes', function (): void {
    $prompts = mh_checkout_install_prompts();
    $suggested = mh_cart_suggested_want_keys();
    $hasService = in_array('service', mh_cart_product_types(), true);

    echo '<div class="mh-install-notes" data-mh-install-notes data-mh-brief-config="'
        .esc_attr(wp_json_encode(mh_install_brief_config())).'">';
    echo '<p class="mh-install-notes__kicker">'.esc_html__('For after purchase', 'sage').'</p>';
    echo '<h3 class="mh-install-notes__title">'.esc_html__('What should I set up?', 'sage').'</h3>';
    echo '<p class="mh-install-notes__lede">'.esc_html(
        $hasService
            ? __('Optional. Pick what applies, then add detail. I read this before I start.', 'sage')
            : __('Optional. I use this if you want help installing or a custom setup later.', 'sage')
    ).'</p>';

    if ($suggested !== []) {
        echo '<p class="mh-install-notes__hint">'.esc_html__('I marked a few from what is in the cart. Untap anything that is wrong.', 'sage').'</p>';
    }

    if ($prompts !== []) {
        echo '<div class="mh-install-notes__chips" role="group" aria-label="'.esc_attr__('Possible wants', 'sage').'">';
        foreach ($prompts as $key => $label) {
            $on = in_array($key, $suggested, true);
            echo '<button type="button" class="mh-install-notes__chip'.($on ? ' is-suggested' : '').'" data-mh-want="'
                .esc_attr($key).'" aria-pressed="'.($on ? 'true' : 'false').'">'.esc_html($label).'</button>';
        }
        echo '</div>';
    }

    echo '<p class="form-row mh-install-notes__site">';
    echo '<label for="mh_install_site">'.esc_html__('Site I should look at', 'sage').'</label>';
    echo '<input type="url" class="input-text" name="mh_install_site" id="mh_install_site" placeholder="https://yoursite.com" inputmode="url" autocomplete="url">';
    echo '</p>';

    echo '<input type="hidden" name="mh_install_wants" value="'.esc_attr(implode(',', $suggested)).'" autocomplete="off">';
    echo '</div>';
});

add_action('woocommerce_checkout_create_order', function ($order, $data): void {
    if (! $order instanceof \WC_Order) {
        return;
    }

    $wants = mh_sanitize_install_wants(wp_unslash($_POST['mh_install_wants'] ?? ''));
    $note = sanitize_textarea_field((string) ($data['order_comments'] ?? ''));
    $site = mh_sanitize_install_site((string) wp_unslash($_POST['mh_install_site'] ?? ''));
    if ($wants === [] && $note === '' && $site === '') {
        return;
    }

    if ($wants !== []) {
        $order->update_meta_data('_mh_install_wants', $wants);
    }
    if ($note !== '') {
        $order->update_meta_data('_mh_install_note', $note);
    }
    if ($site !== '') {
        $order->update_meta_data('_mh_install_site', $site);
    }

    $parts = [];
    $brief = mh_install_brief_text($wants, $site, '');
    if ($brief !== '') {
        $parts[] = $brief;
    }
    if ($site !== '') {
        $parts[] = __('Site:', 'sage').' '.$site;
    }
    $labels = mh_install_want_labels($wants);
    if ($labels !== []) {
        $parts[] = __('Wants for the install:', 'sage').' '.implode(', ', $labels);
    }
    if ($note !== '') {
        $parts[] = $note;
    }
    $order->set_customer_note(implode("\n\n", $parts));
}, 20, 2);

add_action('woocommerce_thankyou', 'App\\mh_render_order_install_notes_front', 8);
add_action('woocommerce_order_details_after_order_table', function ($order): void {
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return;
    }
    mh_render_order_install_notes_front($order);
}, 8);
add_action('woocommerce_email_after_order_table', function ($order, $sent_to_admin): void {
    if (! $order instanceof \WC_Order) {
        return;
    }
    mh_render_order_install_notes_email($order, (bool) $sent_to_admin);
    if ($sent_to_admin || ! mh_order_has_catalog_download($order)) {
        return;
    }
    echo '<p>'.esc_html__('When I ship a new theme or plugin zip, I email this same address. The latest file stays on My account → Downloads.', 'sage').'</p>';
}, 12, 2);
add_action('woocommerce_admin_order_data_after_billing_address', function ($order): void {
    if (! $order instanceof \WC_Order) {
        return;
    }
    mh_render_order_install_notes_admin($order);
});

/**
 * Allowlisted install-want keys and labels.
 *
 * @return array<string, string>
 */
function mh_install_want_catalog(): array
{
    return [
        'brand' => __('Brand colors and fonts', 'sage'),
        'domain' => __('Domain or URL', 'sage'),
        'mls' => __('MLS or IDX', 'sage'),
        'listings' => __('Import listings', 'sage'),
        'keep-pages' => __('Keep current pages', 'sage'),
        'walkthrough' => __('Need a walkthrough', 'sage'),
        'launch-date' => __('Have a launch date', 'sage'),
        'install-help' => __('Help installing', 'sage'),
        'existing-site' => __('I have a site already', 'sage'),
        'child-theme' => __('Prefer a child theme', 'sage'),
    ];
}

/**
 * Chips shown on checkout for the current cart.
 *
 * @return array<string, string>
 */
function mh_checkout_install_prompts(): array
{
    $catalog = mh_install_want_catalog();
    $keys = in_array('service', mh_cart_product_types(), true)
        ? ['brand', 'domain', 'mls', 'listings', 'keep-pages', 'walkthrough', 'launch-date']
        : ['install-help', 'brand', 'existing-site', 'child-theme'];

    $out = [];
    foreach ($keys as $key) {
        if (isset($catalog[$key])) {
            $out[$key] = $catalog[$key];
        }
    }

    return $out;
}

/**
 * Chip keys suggested from product slugs already in the cart.
 *
 * @return list<string>
 */
function mh_cart_suggested_want_keys(): array
{
    if (! function_exists('WC') || ! WC()->cart) {
        return [];
    }

    $map = [
        'acreline-express-install' => ['domain', 'brand'],
        'express-install' => ['domain', 'brand'],
        'acreline-setup-launch' => ['domain', 'walkthrough'],
        'listing-population-10-listings' => ['listings'],
        'listing-population-25-listings' => ['listings'],
        'marketing-content-pack' => ['brand'],
        'acreline-site-care' => ['walkthrough'],
        'site-care' => ['walkthrough'],
        'acreline' => ['install-help'],
    ];

    $allowed = array_keys(mh_checkout_install_prompts());
    $out = [];
    foreach (WC()->cart->get_cart() as $item) {
        $id = (int) ($item['product_id'] ?? 0);
        $post = $id > 0 ? get_post($id) : null;
        $slug = $post instanceof \WP_Post ? (string) $post->post_name : '';
        foreach ($map[$slug] ?? [] as $key) {
            if (! in_array($key, $allowed, true) || in_array($key, $out, true)) {
                continue;
            }
            $out[] = $key;
        }
    }

    return array_slice($out, 0, 3);
}

/** Allowlisted http(s) URL for an install site, or empty. */
function mh_sanitize_install_site(string $raw): string
{
    $url = esc_url_raw(trim($raw), ['http', 'https']);
    if ($url === '' || ! preg_match('#^https?://#i', $url)) {
        return '';
    }

    return $url;
}

function mh_install_site_host(string $url): string
{
    $host = (string) wp_parse_url($url, PHP_URL_HOST);
    $host = preg_replace('/^www\./i', '', $host) ?? $host;

    return $host;
}

/**
 * @param  list<string>  $items
 */
function mh_join_and(array $items): string
{
    $items = array_values(array_filter($items, static fn ($item): bool => trim((string) $item) !== ''));
    $n = count($items);
    if ($n === 0) {
        return '';
    }
    if ($n === 1) {
        return (string) $items[0];
    }
    if ($n === 2) {
        return $items[0].' '.__('and', 'sage').' '.$items[1];
    }
    $last = array_pop($items);

    return implode(', ', $items).', '.__('and', 'sage').' '.$last;
}

/**
 * First-person kickoff sentence from wants + optional site.
 *
 * @param  list<string>  $wantKeys
 */
function mh_install_brief_text(array $wantKeys, string $site = '', string $note = ''): string
{
    $labels = mh_install_want_labels($wantKeys);
    $host = mh_install_site_host($site);
    $list = mh_join_and($labels);

    $sentence = '';
    if ($host !== '' && $list !== '') {
        $sentence = sprintf(
            /* translators: 1: site host, 2: list of wants */
            __('I will start from %1$s — %2$s.', 'sage'),
            $host,
            $list
        );
    } elseif ($host !== '') {
        $sentence = sprintf(
            /* translators: %s site host */
            __('I will start from %s.', 'sage'),
            $host
        );
    } elseif ($list !== '') {
        $sentence = sprintf(
            /* translators: %s list of wants */
            __('I will start with %s.', 'sage'),
            $list
        );
    }

    $note = trim($note);
    if ($note === '') {
        return $sentence;
    }

    return $sentence === '' ? $note : $sentence.' '.$note;
}

/**
 * Config for the live checkout brief (JSON in the page).
 *
 * @return array<string, mixed>
 */
function mh_install_brief_config(): array
{
    $hasService = in_array('service', mh_cart_product_types(), true);

    return [
        'labels' => mh_install_want_catalog(),
        'empty' => $hasService
            ? __('Tap what applies. This sentence becomes your kickoff.', 'sage')
            : __('Tap what applies if you want help after you download.', 'sage'),
        'and' => __('and', 'sage'),
        'with' => __('I will start with %s.', 'sage'),
        'from' => __('I will start from %s.', 'sage'),
        'fromWith' => __('I will start from %1$s — %2$s.', 'sage'),
    ];
}

/**
 * @param  mixed  $raw  Comma string or list of keys from checkout POST.
 * @return list<string>
 */
function mh_sanitize_install_wants(mixed $raw): array
{
    if (is_string($raw)) {
        $raw = explode(',', $raw);
    }
    if (! is_array($raw)) {
        return [];
    }

    $allowed = array_keys(mh_install_want_catalog());
    $out = [];
    foreach ($raw as $key) {
        $key = sanitize_key((string) $key);
        if ($key === '' || ! in_array($key, $allowed, true) || in_array($key, $out, true)) {
            continue;
        }
        $out[] = $key;
    }

    return $out;
}

/**
 * @param  list<string>  $keys
 * @return list<string>
 */
function mh_install_want_labels(array $keys): array
{
    $catalog = mh_install_want_catalog();
    $out = [];
    foreach ($keys as $key) {
        if (isset($catalog[$key])) {
            $out[] = $catalog[$key];
        }
    }

    return $out;
}

/**
 * @return array{wants: list<string>, want_keys: list<string>, note: string, site: string, brief: string}
 */
function mh_order_install_notes(\WC_Order $order): array
{
    $wantKeys = mh_sanitize_install_wants($order->get_meta('_mh_install_wants'));
    $note = trim((string) $order->get_meta('_mh_install_note'));
    $site = mh_sanitize_install_site((string) $order->get_meta('_mh_install_site'));
    if ($note === '' && $wantKeys === [] && $site === '') {
        $note = trim((string) $order->get_customer_note());
    }

    return [
        'wants' => mh_install_want_labels($wantKeys),
        'want_keys' => $wantKeys,
        'note' => $note,
        'site' => $site,
        'brief' => mh_install_brief_text($wantKeys, $site, ''),
    ];
}

/**
 * Thank-you and My account: kickoff ticket with submitted install notes.
 *
 * @param  mixed  $order  Order object or order ID from Woo hooks.
 */
function mh_render_order_install_notes_front(mixed $order): void
{
    if (is_numeric($order)) {
        $order = function_exists('wc_get_order') ? wc_get_order((int) $order) : null;
    }
    if (! $order instanceof \WC_Order) {
        return;
    }

    $notes = mh_order_install_notes($order);
    $hasDetail = $notes['wants'] !== [] || $notes['note'] !== '' || $notes['site'] !== '';
    $isThankYou = function_exists('is_order_received_page') && is_order_received_page();
    if (! $hasDetail && ! $isThankYou) {
        return;
    }

    $copy = trim($notes['brief'].($notes['note'] !== '' ? "\n\n".$notes['note'] : '').($notes['site'] !== '' ? "\n".$notes['site'] : ''));
    $number = $order->get_order_number();

    echo '<aside class="mh-install-echo mh-ticket" aria-label="'.esc_attr__('Kickoff brief for this order', 'sage').'">';
    echo '<p class="mh-install-echo__kicker">'.esc_html(sprintf(
        /* translators: %s order number */
        __('Kickoff brief · ticket %s', 'sage'),
        $number
    )).'</p>';
    if ($notes['brief'] !== '') {
        echo '<p class="mh-install-echo__brief">'.esc_html($notes['brief']).'</p>';
    } elseif ($isThankYou) {
        echo '<p class="mh-install-echo__brief">'.esc_html__('No extra notes. Reply to the receipt if something comes up.', 'sage').'</p>';
    }
    if ($notes['site'] !== '') {
        echo '<p class="mh-install-echo__site"><a href="'.esc_url($notes['site']).'">'.esc_html(mh_install_site_host($notes['site'])).'</a></p>';
    }
    if ($notes['wants'] !== []) {
        echo '<ul class="mh-install-echo__wants">';
        foreach ($notes['wants'] as $label) {
            echo '<li>'.esc_html($label).'</li>';
        }
        echo '</ul>';
    }
    if ($notes['note'] !== '') {
        echo '<p class="mh-install-echo__note">'.nl2br(esc_html($notes['note'])).'</p>';
    }
    if ($copy !== '') {
        echo '<p class="mh-install-echo__copy-wrap"><button type="button" class="mh-install-echo__copy" data-mh-copy-brief>'
            .esc_html__('Copy brief', 'sage').'</button></p>';
        echo '<textarea class="mh-install-echo__copy-src" readonly hidden>'.esc_textarea($copy).'</textarea>';
    }
    echo '<p class="mh-install-echo__foot">'.esc_html__('Reply to the receipt if something changes.', 'sage').'</p>';
    echo '</aside>';
}

function mh_render_order_install_notes_email(\WC_Order $order, bool $sentToAdmin): void
{
    $notes = mh_order_install_notes($order);
    if ($notes['wants'] === [] && $notes['note'] === '' && $notes['site'] === '') {
        return;
    }

    $title = $sentToAdmin
        ? __('Install wants from the buyer', 'sage')
        : __('Notes I have for your install', 'sage');

    echo '<div class="mh-install-email" style="margin:16px 0;padding:16px;border:1px solid #d1d5db">';
    echo '<p style="margin:0 0 8px;font-weight:700">'.esc_html($title).'</p>';
    if ($notes['brief'] !== '') {
        echo '<p style="margin:0 0 8px">'.esc_html($notes['brief']).'</p>';
    }
    if ($notes['site'] !== '') {
        echo '<p style="margin:0 0 8px"><a href="'.esc_url($notes['site']).'">'.esc_html($notes['site']).'</a></p>';
    }
    if ($notes['wants'] !== []) {
        echo '<p style="margin:0 0 8px">'.esc_html(implode(' · ', $notes['wants'])).'</p>';
    }
    if ($notes['note'] !== '') {
        echo '<p style="margin:0;white-space:pre-wrap">'.esc_html($notes['note']).'</p>';
    }
    echo '</div>';
}

function mh_render_order_install_notes_admin(\WC_Order $order): void
{
    $notes = mh_order_install_notes($order);
    if ($notes['wants'] === [] && $notes['note'] === '' && $notes['site'] === '') {
        return;
    }

    echo '<div class="mh-admin-install-notes" style="margin-top:12px;padding:12px 14px;border:1px solid #c3c4c7;background:#fff">';
    echo '<p style="margin:0 0 6px"><strong>'.esc_html__('Kickoff brief', 'sage').'</strong></p>';
    if ($notes['brief'] !== '') {
        echo '<p style="margin:0 0 6px">'.esc_html($notes['brief']).'</p>';
    }
    if ($notes['site'] !== '') {
        echo '<p style="margin:0 0 6px"><a href="'.esc_url($notes['site']).'">'.esc_html($notes['site']).'</a></p>';
    }
    if ($notes['wants'] !== []) {
        echo '<p style="margin:0 0 6px">'.esc_html(implode(' · ', $notes['wants'])).'</p>';
    }
    if ($notes['note'] !== '') {
        echo '<p style="margin:0;white-space:pre-wrap">'.esc_html($notes['note']).'</p>';
    }
    echo '</div>';
}

// ── Checkout: email first, plain labels ───────────────────────────────────────
add_filter('woocommerce_billing_fields', function (array $fields): array {
    if (isset($fields['billing_email'])) {
        $fields['billing_email']['priority'] = 4;
        $fields['billing_email']['label'] = mh_cart_is_services_only()
            ? __('Email for receipt and kickoff', 'sage')
            : __('Email for receipt and files', 'sage');
        $fields['billing_email']['placeholder'] = __('you@shop.com', 'sage');
        $fields['billing_email']['class'][] = 'mh-checkout-email';
    }
    if (isset($fields['billing_first_name'])) {
        $fields['billing_first_name']['priority'] = 10;
    }
    if (isset($fields['billing_phone'])) {
        $fields['billing_phone']['required'] = false;
    }

    return $fields;
});

add_action('woocommerce_checkout_before_customer_details', function (): void {
    $copy = mh_cart_is_services_only()
        ? __('Email first. Guest checkout is fine. I do not add you to a list.', 'sage')
        : __('Email first — that is where the zip goes. Guest checkout is fine. I do not add you to a list.', 'sage');
    echo '<p class="woo-checkout-intro">'.esc_html($copy).'</p>';
});

// ── Add to cart: short confirmation + checkout ────────────────────────────────
add_filter('woocommerce_add_to_cart_message_html', function (string $message, $products): string {
    if (! is_array($products) || $products === []) {
        return $message;
    }

    $id = (int) array_key_first($products);
    $name = html_entity_decode((string) get_the_title($id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($name === '') {
        return $message;
    }

    $checkout = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    $next = mh_resolve_product_type($id) === 'service'
        ? __('Checkout books the work. I write back after payment.', 'sage')
        : __('Checkout next — the zip lands in your receipt email.', 'sage');

    return '<span class="mh-cart-added">'.sprintf(
        /* translators: %s product name */
        esc_html__('%s is in your cart.', 'sage'),
        esc_html($name)
    ).'</span> <span class="mh-cart-added__next">'.esc_html($next).'</span> <a href="'
        .esc_url($checkout).'" class="button wc-forward">'.esc_html__('Checkout', 'sage').'</a>';
}, 10, 2);

// ── Order received ────────────────────────────────────────────────────────────
add_filter('woocommerce_thankyou_order_received_text', function (string $text, $order): string {
    $hasService = false;
    if ($order instanceof \WC_Order) {
        foreach ($order->get_items() as $item) {
            if (mh_resolve_product_type((int) $item->get_product_id()) === 'service') {
                $hasService = true;
                break;
            }
        }
    }

    return $hasService
        ? __('Thanks. I have the order and will write within one business day (ET) with next steps.', 'sage')
        : __('Thanks. Check the receipt email for your download link. It usually lands within a minute.', 'sage');
}, 10, 2);

add_action('init', __NAMESPACE__.'\\mh_seed_woocommerce_pages', 42);
add_action('woocommerce_installed', __NAMESPACE__.'\\mh_seed_woocommerce_pages');

/**
 * Drop WooCommerce's float-column CSS. Theme grid in portfolio.css owns
 * 3/2/1 columns; `woocommerce-smallscreen` otherwise forces 48% widths
 * below 768px and leaves skinny cards on tablet and phone.
 *
 * @param  array<string, array<string, mixed>>  $styles
 * @return array<string, array<string, mixed>>
 */
add_filter('woocommerce_enqueue_styles', function (array $styles): array {
    unset($styles['woocommerce-layout'], $styles['woocommerce-smallscreen']);

    return $styles;
});

/** Skip gallery scripts off the single product page. Skip WC block CSS everywhere (classic templates). */
add_action('wp_enqueue_scripts', function (): void {
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('wc-blocks-vendors-style');
    wp_dequeue_style('wc-blocks-checkout-style');
    wp_dequeue_style('wc-blocks-cart-style');

    // Classic Blade templates — drop storefront block/order-attribution JS.
    wp_dequeue_script('wc-order-attribution');
    wp_dequeue_script('sourcebuster-js');
    if (function_exists('WC') && WC()->cart && WC()->cart->is_empty()) {
        wp_dequeue_script('wc-cart-fragments');
    }

    if (! function_exists('is_product') || is_product()) {
        return;
    }

    wp_dequeue_script('zoom');
    wp_dequeue_script('flexslider');
    wp_dequeue_script('photoswipe');
    wp_dequeue_script('photoswipe-ui-default');
    wp_dequeue_script('wc-single-product');
    wp_dequeue_style('photoswipe');
    wp_dequeue_style('photoswipe-default-skin');
}, 99);

/** Emoji detection CSS/JS is unused on this portfolio theme. */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('emoji_svg_url', '__return_false');
