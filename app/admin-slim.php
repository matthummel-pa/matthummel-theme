<?php

/**
 * Strip Kadence / Pressroot product admin from this portfolio theme.
 * Front-end still uses existing theme mods; wp-admin no longer shows
 * Theme Options, Theme Tools, starter sites, or the design library.
 */

namespace App;

add_action('admin_menu', function () {
    remove_menu_page('mh-theme-settings');
    foreach ([
        'mh-theme-tools',
        'mh-starter-sites',
        'mh-pattern-library',
        'mh-local-fonts',
        'mh-demo-import',
    ] as $slug) {
        remove_submenu_page('themes.php', $slug);
        remove_submenu_page('appearance.php', $slug);
    }
}, 999);

add_action('wp_dashboard_setup', function () {
    remove_meta_box('mh_get_started', 'dashboard', 'normal');
    remove_meta_box('mh_get_started', 'dashboard', 'side');
}, 99);

add_action('customize_register', function (\WP_Customize_Manager $wp) {
    $wp->remove_panel('mh_theme_options');
    $wp->remove_panel('mh_quick_setup');

    foreach ($wp->sections() as $id => $section) {
        $panel = $section->panel ?? '';
        if ($panel === 'mh_theme_options' || $panel === 'mh_quick_setup' || str_starts_with((string) $id, 'mh_')) {
            $wp->remove_section($id);
        }
    }
}, 9999);

add_action('admin_head', function () {
    echo '<style id="mh-admin-slim">'
        . '#toplevel_page_mh-theme-settings,'
        . 'a[href*="page=mh-theme-tools"],'
        . 'a[href*="page=mh-starter-sites"],'
        . 'a[href*="page=mh-pattern-library"],'
        . 'a[href*="page=mh-local-fonts"]{display:none!important}'
        . '</style>';
});
