<?php

/**
 * Social Links — store platform URLs in theme mods, expose in Customizer
 * and admin settings page. Powers all social icons throughout the theme
 * (top bar, navbar, footer). Leave a URL blank to hide that platform's icon.
 *
 * Helper:  \App\mh_social_links()  → array of active links
 * Filter:  matthummel/social_links → extend/override the list
 */

namespace App;

/**
 * All supported platforms with labels, icon slugs, and defaults.
 * Icon slugs map to Blade Icons (Simple Icons set where available).
 */
function mh_social_platforms(): array
{
    return apply_filters('matthummel/social_platforms', [
        'github'    => ['label' => 'GitHub',      'icon' => 'si-github',    'default' => 'https://github.com/matthummel-pa'],
        'linkedin'  => ['label' => 'LinkedIn',    'icon' => 'si-linkedin',  'default' => ''],
        'devto'     => ['label' => 'Dev.to',      'icon' => 'si-devdotto',  'default' => 'https://dev.to/mattbuildsapps'],
        'twitter'   => ['label' => 'X / Twitter', 'icon' => 'si-x',        'default' => ''],
        'bluesky'   => ['label' => 'Bluesky',     'icon' => 'si-bluesky',  'default' => ''],
        'instagram' => ['label' => 'Instagram',   'icon' => 'si-instagram', 'default' => ''],
        'youtube'   => ['label' => 'YouTube',     'icon' => 'si-youtube',   'default' => ''],
        'rss'       => ['label' => 'RSS Feed',    'icon' => 'si-rss',      'default' => ''],
    ]);
}

/**
 * Returns only platforms that have a URL set, merged with their metadata.
 * Usage:  foreach (\App\mh_social_links() as $key => $link) { ... }
 *         $link['url'], $link['label'], $link['icon']
 */
function mh_social_links(): array
{
    $links = [];
    foreach (mh_social_platforms() as $key => $p) {
        $url = get_theme_mod("mh_social_{$key}", $p['default']);
        if ($url) {
            $links[$key] = array_merge($p, ['url' => esc_url($url)]);
        }
    }
    return apply_filters('matthummel/social_links', $links);
}

/* ── Customizer section ──────────────────────────────────────────────────── */

add_action('customize_register', function (\WP_Customize_Manager $wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', [
            'title'    => __('Theme Options', 'matthummel'),
            'priority' => 30,
        ]);
    }

    $wp->add_section('mh_social_section', [
        'title'       => __('Social Links', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('Enter the full URL for each platform. Leave blank to hide an icon. These power all social icons across the header, top bar, and footer.', 'matthummel'),
        'priority'    => 40,
    ]);

    foreach (mh_social_platforms() as $key => $p) {
        $wp->add_setting("mh_social_{$key}", [
            'default'           => $p['default'],
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ]);
        $wp->add_control("mh_social_{$key}", [
            'label'   => $p['label'],
            'section' => 'mh_social_section',
            'type'    => 'url',
        ]);
    }
}, 25);

/* ── Extend admin settings schema ───────────────────────────────────────── */

add_filter('matthummel/admin_schema', function (array $schema): array {
    $fields = [];
    foreach (mh_social_platforms() as $key => $p) {
        $fields[] = [
            'key'   => "mh_social_{$key}",
            'label' => $p['label'],
            'type'  => 'url',
        ];
    }

    $schema['social'] = [
        'icon'   => 'dashicons-share',
        'label'  => __('Social Links', 'matthummel'),
        'fields' => $fields,
    ];

    return $schema;
});
