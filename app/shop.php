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
