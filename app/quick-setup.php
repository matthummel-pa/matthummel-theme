<?php

/**
 * Quick Setup — a top-level Customizer panel that consolidates the most
 * important first-time settings in one place. All controls share the SAME
 * setting keys as the main Theme Options sections, so changes sync instantly.
 *
 * Priority 25 puts it above Theme Options (30) and Site Identity (20+).
 */

namespace App;

add_action('customize_register', function (\WP_Customize_Manager $wp) {

    /* ── Panel ───────────────────────────────────────────────────────────── */
    $wp->add_panel('mh_quick_setup', [
        'title'       => __('⚡ Quick Setup', 'matthummel'),
        'description' => __('New here? Configure the most important settings in one place. Full controls live inside Theme Options.', 'matthummel'),
        'priority'    => 25,
    ]);

    /* ── Section: Branding ───────────────────────────────────────────────── */
    $wp->add_section('mh_qs_branding', [
        'title'       => __('Branding', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('Your site identity and core color. The logo is set under Site Identity (above).', 'matthummel'),
        'priority'    => 10,
    ]);

    // Brand color — shared with Theme Options → Colors → mh_color_action
    $wp->add_control(new \WP_Customize_Color_Control($wp, 'mh_qs_color_action', [
        'settings' => 'mh_color_action',
        'label'    => __('Brand color (buttons & accents)', 'matthummel'),
        'section'  => 'mh_qs_branding',
    ]));

    // Page background — shared with mh_color_paper
    $wp->add_control(new \WP_Customize_Color_Control($wp, 'mh_qs_color_paper', [
        'settings' => 'mh_color_paper',
        'label'    => __('Page background', 'matthummel'),
        'section'  => 'mh_qs_branding',
    ]));

    /* ── Section: Typography ─────────────────────────────────────────────── */
    $wp->add_section('mh_qs_type', [
        'title'       => __('Typography', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('Choose a heading + body font pairing. Full controls in Theme Options → Typography.', 'matthummel'),
        'priority'    => 20,
    ]);

    $fontChoices = function_exists('App\\mh_fonts')
        ? array_combine(array_keys(mh_fonts()), array_keys(mh_fonts()))
        : ['Geist' => 'Geist', 'Inter' => 'Inter', 'Space Grotesk' => 'Space Grotesk', 'Fraunces' => 'Fraunces'];

    $wp->add_control('mh_qs_font_heading', [
        'settings' => 'mh_font_heading',
        'label'    => __('Heading font', 'matthummel'),
        'section'  => 'mh_qs_type',
        'type'     => 'select',
        'choices'  => $fontChoices,
    ]);

    $wp->add_control('mh_qs_font_body', [
        'settings' => 'mh_font_body',
        'label'    => __('Body font', 'matthummel'),
        'section'  => 'mh_qs_type',
        'type'     => 'select',
        'choices'  => $fontChoices,
    ]);

    /* ── Section: Header & CTA ───────────────────────────────────────────── */
    $wp->add_section('mh_qs_header', [
        'title'       => __('Header Button', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('The call-to-action button in the top-right of the header.', 'matthummel'),
        'priority'    => 30,
    ]);

    $wp->add_control('mh_qs_show_cta', [
        'settings' => 'mh_show_cta',
        'label'    => __('Show header button', 'matthummel'),
        'section'  => 'mh_qs_header',
        'type'     => 'checkbox',
    ]);

    $wp->add_control('mh_qs_cta_text', [
        'settings' => 'mh_cta_text',
        'label'    => __('Button text', 'matthummel'),
        'section'  => 'mh_qs_header',
        'type'     => 'text',
    ]);

    $wp->add_control('mh_qs_cta_url', [
        'settings' => 'mh_cta_url',
        'label'    => __('Button URL', 'matthummel'),
        'section'  => 'mh_qs_header',
        'type'     => 'url',
    ]);

    /* ── Section: Social ──────────────────────────────────────────────────── */
    $wp->add_section('mh_qs_social', [
        'title'       => __('Social Links', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('Enter a URL to show that platform\'s icon. Leave blank to hide it.', 'matthummel'),
        'priority'    => 40,
    ]);

    // Show all platforms (keys match mh_social_platforms() and menu.php's mh_socials_map)
    $platforms = function_exists('App\\mh_social_platforms') ? mh_social_platforms() : [
        'github'    => ['label' => 'GitHub',     'default' => ''],
        'linkedin'  => ['label' => 'LinkedIn',   'default' => ''],
        'devto'     => ['label' => 'Dev.to',     'default' => ''],
        'x'         => ['label' => 'X (Twitter)','default' => ''],
        'bluesky'   => ['label' => 'Bluesky',    'default' => ''],
        'instagram' => ['label' => 'Instagram',  'default' => ''],
        'youtube'   => ['label' => 'YouTube',    'default' => ''],
        'facebook'  => ['label' => 'Facebook',   'default' => ''],
        'mastodon'  => ['label' => 'Mastodon',   'default' => ''],
        'email'     => ['label' => 'Email',      'default' => ''],
        'rss'       => ['label' => 'RSS Feed',   'default' => ''],
    ];
    foreach ($platforms as $key => $p) {
        $wp->add_control("mh_qs_social_{$key}", [
            'settings' => "mh_social_{$key}",
            'label'    => $p['label'],
            'section'  => 'mh_qs_social',
            'type'     => 'url',
        ]);
    }

    /* ── Section: Footer ─────────────────────────────────────────────────── */
    $wp->add_section('mh_qs_footer', [
        'title'       => __('Footer', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('Footer tagline shown below your name/copyright. Full footer controls in Theme Options.', 'matthummel'),
        'priority'    => 50,
    ]);

    $wp->add_control('mh_qs_footer_text', [
        'settings' => 'mh_footer_text',
        'label'    => __('Footer tagline', 'matthummel'),
        'section'  => 'mh_qs_footer',
        'type'     => 'textarea',
    ]);

    /* ── Section: Style Kit ──────────────────────────────────────────────── */
    $wp->add_section('mh_qs_style_kit', [
        'title'       => __('Style Kits', 'matthummel'),
        'panel'       => 'mh_quick_setup',
        'description' => __('Apply a one-click design preset. Full import/export tools at Appearance → Theme Tools.', 'matthummel'),
        'priority'    => 60,
    ]);

    $kits = function_exists('App\\mh_style_kits') ? array_combine(
        array_keys(mh_style_kits()),
        array_column(mh_style_kits(), 'label')
    ) : [];
    $kits = array_merge(['' => __('— Choose a preset —', 'matthummel')], $kits);

    $wp->add_setting('mh_qs_apply_kit', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_key',
        'transport'         => 'postMessage',
    ]);
    $wp->add_control('mh_qs_apply_kit', [
        'label'       => __('Apply style kit', 'matthummel'),
        'description' => __('Selecting a kit applies its colors and fonts immediately. Save to persist.', 'matthummel'),
        'section'     => 'mh_qs_style_kit',
        'type'        => 'select',
        'choices'     => $kits,
    ]);

}, 26);

/* ── Style-Kit controls-pane handler ─────────────────────────────────────── *
 * Must run in the CONTROLS pane (customize_controls_enqueue_scripts), not the
 * preview iframe. Calling wp.customize(key).set() from the controls side
 * updates both the control UI and triggers the transport, so the change is
 * actually queued for Save.
 */
add_action('customize_controls_enqueue_scripts', function () {
    if (! function_exists('App\\mh_style_kits')) {
        return;
    }
    $kits_json = wp_json_encode(mh_style_kits());
    wp_add_inline_script(
        'customize-controls',
        "(function(){
            var kits = {$kits_json};
            wp.customize('mh_qs_apply_kit', function(setting){
                setting.bind(function(kit){
                    if (!kit || !kits[kit]) return;
                    var mods = kits[kit].mods || {};
                    Object.keys(mods).forEach(function(key){
                        var s = wp.customize(key);
                        if (s) { s.set(mods[key]); }
                    });
                    // Reset selector to placeholder so picking the same kit again still fires
                    setTimeout(function(){ setting.set(''); }, 100);
                });
            });
        })();"
    );
});
