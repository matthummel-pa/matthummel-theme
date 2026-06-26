<?php

/**
 * Social Links — platform metadata, Customizer settings registration,
 * and admin settings schema tab.
 *
 * Controls (UI) live in Quick Setup (quick-setup.php) so there is one
 * clear entry point for first-time setup. These settings are registered
 * here so get_theme_mod() works correctly everywhere and sanitization
 * is applied on save.
 *
 * Keys match mh_socials_map() in menu.php so both read the same
 * mh_social_{key} theme_mods.
 */

namespace App;

/**
 * Platform metadata: label, Blade Icon slug, and default URL.
 */
function mh_social_platforms(): array
{
    return apply_filters('matthummel/social_platforms', [
        'github'    => ['label' => 'GitHub',      'icon' => 'si-github',    'default' => 'https://github.com/matthummel-pa'],
        'linkedin'  => ['label' => 'LinkedIn',    'icon' => 'si-linkedin',  'default' => ''],
        'devto'     => ['label' => 'Dev.to',      'icon' => 'si-devdotto',  'default' => 'https://dev.to/mattbuildsapps'],
        'x'         => ['label' => 'X (Twitter)', 'icon' => 'si-x',         'default' => ''],
        'bluesky'   => ['label' => 'Bluesky',     'icon' => 'si-bluesky',   'default' => ''],
        'instagram' => ['label' => 'Instagram',   'icon' => 'si-instagram', 'default' => ''],
        'youtube'   => ['label' => 'YouTube',     'icon' => 'si-youtube',   'default' => ''],
        'facebook'  => ['label' => 'Facebook',    'icon' => 'si-facebook',  'default' => ''],
        'mastodon'  => ['label' => 'Mastodon',    'icon' => 'si-mastodon',  'default' => ''],
        'email'     => ['label' => 'Email',       'icon' => 'si-mail',      'default' => ''],
        'rss'       => ['label' => 'RSS Feed',    'icon' => 'si-rss',       'default' => ''],
    ]);
}

/* ── Register Customizer settings (controls live in Quick Setup) ─────────── */

add_action('customize_register', function (\WP_Customize_Manager $wp) {
    foreach (mh_social_platforms() as $key => $p) {
        $wp->add_setting("mh_social_{$key}", [
            'default'           => $p['default'],
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ]);
    }
}, 25);

/* ── Add Social Links tab to Appearance → Theme Settings ────────────────── */

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
