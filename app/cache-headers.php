<?php

/**
 * Do not let SiteGround cache HTML that points at old Vite hashes.
 */

namespace App;

add_action('send_headers', function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_robots() || is_favicon()) {
        return;
    }

    nocache_headers();
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
}, 1);
