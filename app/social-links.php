<?php

/**
 * Social Links — Customizer section + admin settings schema for platform URLs.
 * URL storage and rendering logic lives in menu.php (mh_social_links / mh_socials_map).
 * Keys here must match mh_socials_map() so the Customizer controls the same theme_mods.
 */

namespace App;

/**
 * Platform metadata: label, Blade Icon slug, and default URL.
 * Keys intentionally match mh_socials_map() in menu.php so both read the
 * same mh_social_{key} theme_mods.
 */
function mh_social_platforms(): array
{
    return apply_filters('matthummel/social_platforms', [
        'github'    => ['label' => 'GitHub',     'icon' => 'si-github',    'default' => 'https://github.com/matthummel-pa'],
        'linkedin'  => ['label' => 'LinkedIn',   'icon' => 'si-linkedin',  'default' => ''],
        'devto'     => ['label' => 'Dev.to',     'icon' => 'si-devdotto',  'default' => 'https://dev.to/mattbuildsapps'],
        'x'         => ['label' => 'X (Twitter)','icon' => 'si-x',         'default' => ''],
        'bluesky'   => ['label' => 'Bluesky',    'icon' => 'si-bluesky',   'default' => ''],
        'instagram' => ['label' => 'Instagram',  'icon' => 'si-instagram', 'default' => ''],
        'youtube'   => ['label' => 'YouTube',    'icon' => 'si-youtube',   'default' => ''],
        'facebook'  => ['label' => 'Facebook',   'icon' => 'si-facebook',  'default' => ''],
        'mastodon'  => ['label' => 'Mastodon',   'icon' => 'si-mastodon',  'default' => ''],
        'email'     => ['label' => 'Email',      'icon' => 'si-mail',      'default' => ''],
        'rss'       => ['label' => 'RSS Feed',   'icon' => 'si-rss',       'default' => ''],
    ]);
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
