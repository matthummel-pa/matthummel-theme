<?php

/**
 * Do not let the host cache HTML that points at old Vite hashes (Hostinger LiteSpeed).
 */

namespace App;

add_action('send_headers', function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_robots() || is_favicon()) {
        return;
    }

    nocache_headers();
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
}, 1);

/**
 * Baseline security headers (site audit 3.6.46). Host does not send any.
 *
 * No CSP here: Site Kit, gtag, and LiteSpeed inline scripts would need a nonce plan first.
 * HSTS stays on this host only; subdomains (demos) are not asserted.
 */
add_action('send_headers', function (\WP $wp): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    // Post embeds (/slug/embed/) must stay frameable on other sites.
    if (empty($wp->query_vars['embed'])) {
        header('X-Frame-Options: SAMEORIGIN');
    }
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // No payment=(): the shop's express checkout (Apple Pay / Google Pay) uses the Payment Request API.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}, 2);
