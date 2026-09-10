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
add_filter('woocommerce_order_button_text', function (): string {
    return mh_cart_is_services_only()
        ? __('Book this work', 'sage')
        : __('Pay and get access', 'sage');
});

// ── Checkout: remove default "Your order" heading (we style it via CSS) ───────
add_filter('woocommerce_checkout_order_review_heading', fn (): string => __('Order summary', 'sage'));

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
    $hasService = in_array('service', mh_cart_product_types(), true);

    echo '<div class="mh-install-notes" data-mh-install-notes>';
    echo '<p class="mh-install-notes__kicker">'.esc_html__('For after purchase', 'sage').'</p>';
    echo '<h3 class="mh-install-notes__title">'.esc_html__('What should I set up?', 'sage').'</h3>';
    echo '<p class="mh-install-notes__lede">'.esc_html(
        $hasService
            ? __('Optional. Pick what applies, then add detail. I read this before I start.', 'sage')
            : __('Optional. I use this if you want help installing or a custom setup later.', 'sage')
    ).'</p>';

    if ($prompts !== []) {
        echo '<div class="mh-install-notes__chips" role="group" aria-label="'.esc_attr__('Possible wants', 'sage').'">';
        foreach ($prompts as $key => $label) {
            echo '<button type="button" class="mh-install-notes__chip" data-mh-want="'
                .esc_attr($key).'" aria-pressed="false">'.esc_html($label).'</button>';
        }
        echo '</div>';
    }

    echo '<input type="hidden" name="mh_install_wants" value="" autocomplete="off">';
    echo '</div>';
});

add_action('woocommerce_checkout_create_order', function ($order, $data): void {
    if (! $order instanceof \WC_Order) {
        return;
    }

    $wants = mh_sanitize_install_wants(wp_unslash($_POST['mh_install_wants'] ?? ''));
    $note = sanitize_textarea_field((string) ($data['order_comments'] ?? ''));
    if ($wants === [] && $note === '') {
        return;
    }

    if ($wants !== []) {
        $order->update_meta_data('_mh_install_wants', $wants);
    }
    if ($note !== '') {
        $order->update_meta_data('_mh_install_note', $note);
    }

    $parts = [];
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
 * @return array{wants: list<string>, note: string}
 */
function mh_order_install_notes(\WC_Order $order): array
{
    $wants = mh_sanitize_install_wants($order->get_meta('_mh_install_wants'));
    $note = trim((string) $order->get_meta('_mh_install_note'));
    if ($note === '' && $wants === []) {
        $note = trim((string) $order->get_customer_note());
    }

    return [
        'wants' => mh_install_want_labels($wants),
        'note' => $note,
    ];
}

/**
 * Thank-you and My account: echo submitted install notes.
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
    if ($notes['wants'] === [] && $notes['note'] === '') {
        return;
    }

    echo '<aside class="mh-install-echo" aria-label="'.esc_attr__('Install notes on this order', 'sage').'">';
    echo '<p class="mh-install-echo__kicker">'.esc_html__('I have this for the install', 'sage').'</p>';
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
    echo '<p class="mh-install-echo__foot">'.esc_html__('Reply to the receipt if something changes.', 'sage').'</p>';
    echo '</aside>';
}

function mh_render_order_install_notes_email(\WC_Order $order, bool $sentToAdmin): void
{
    $notes = mh_order_install_notes($order);
    if ($notes['wants'] === [] && $notes['note'] === '') {
        return;
    }

    $title = $sentToAdmin
        ? __('Install wants from the buyer', 'sage')
        : __('Notes I have for your install', 'sage');

    echo '<div class="mh-install-email" style="margin:16px 0;padding:16px;border:1px solid #d1d5db">';
    echo '<p style="margin:0 0 8px;font-weight:700">'.esc_html($title).'</p>';
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
    if ($notes['wants'] === [] && $notes['note'] === '') {
        return;
    }

    echo '<div class="mh-admin-install-notes" style="margin-top:12px;padding:12px 14px;border:1px solid #c3c4c7;background:#fff">';
    echo '<p style="margin:0 0 6px"><strong>'.esc_html__('Install wants', 'sage').'</strong></p>';
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
