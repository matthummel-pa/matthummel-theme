<?php

/**
 * Footer builder (colors, social, sticky header) + content controls
 * (global CTA + per-template intros), all in the Customizer.
 */

namespace App;

function mh_footer()
{
    return [
        'bg'          => mh_palette_value(get_theme_mod('mh_footer_bg', 'paper'), get_theme_mod('mh_footer_bg_custom', '')),
        'text'        => mh_palette_value(get_theme_mod('mh_footer_textc', 'body'), get_theme_mod('mh_footer_text_custom', '')),
        'show_social' => (bool) get_theme_mod('mh_footer_social', true),
    ];
}

/** Sticky header body class. */
add_filter('body_class', function ($c) {
    if (get_theme_mod('mh_sticky_header', false)) {
        $c[] = 'mh-sticky-header';
    }
    return $c;
});

/** Wire the global CTA to Customizer values (cta.blade uses these filters). */
add_filter('matthummel/cta_heading', function ($d) { $v = get_theme_mod('mh_cta_heading', ''); return $v !== '' ? $v : $d; });
add_filter('matthummel/cta_text', function ($d) { $v = get_theme_mod('mh_cta_body', ''); return $v !== '' ? $v : $d; });
add_filter('matthummel/cta_label', function ($d) { $v = get_theme_mod('mh_cta_btn_label', ''); return $v !== '' ? $v : $d; });
add_filter('matthummel/cta_url', function ($d) { $v = get_theme_mod('mh_cta_btn_url', ''); return $v !== '' ? $v : $d; });

/** Customizer sections. */
add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }

    /* Footer */
    $wp->add_section('mh_footer_section', ['title' => __('Footer & Header', 'matthummel'), 'panel' => 'mh_theme_options']);

    $wp->add_setting('mh_sticky_header', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_sticky_header', ['label' => __('Sticky header', 'matthummel'), 'section' => 'mh_footer_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_footer_social', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_footer_social', ['label' => __('Show social icons in footer', 'matthummel'), 'section' => 'mh_footer_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_footer_bg', ['default' => 'paper', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_footer_bg', ['label' => __('Footer background', 'matthummel'), 'section' => 'mh_footer_section', 'type' => 'select', 'choices' => mh_palette_choices()]);
    $wp->add_setting('mh_footer_bg_custom', ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp->add_control(new \WP_Customize_Color_Control($wp, 'mh_footer_bg_custom', ['label' => __('Footer background (custom)', 'matthummel'), 'section' => 'mh_footer_section']));

    $wp->add_setting('mh_footer_textc', ['default' => 'body', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_footer_textc', ['label' => __('Footer text', 'matthummel'), 'section' => 'mh_footer_section', 'type' => 'select', 'choices' => mh_palette_choices()]);
    $wp->add_setting('mh_footer_text_custom', ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp->add_control(new \WP_Customize_Color_Control($wp, 'mh_footer_text_custom', ['label' => __('Footer text (custom)', 'matthummel'), 'section' => 'mh_footer_section']));

    /* CTA & intros */
    $wp->add_section('mh_content_section', ['title' => __('CTA & Intros', 'matthummel'), 'panel' => 'mh_theme_options', 'description' => __('Edit the global project CTA and the intro text on the Projects and Contact templates.', 'matthummel')]);

    $content = [
        ['mh_cta_heading', __('CTA heading', 'matthummel'), 'text'],
        ['mh_cta_body', __('CTA text', 'matthummel'), 'textarea'],
        ['mh_cta_btn_label', __('CTA button label', 'matthummel'), 'text'],
        ['mh_cta_btn_url', __('CTA button URL', 'matthummel'), 'url'],
        ['mh_projects_intro', __('Projects archive intro', 'matthummel'), 'textarea'],
        ['mh_contact_intro', __('Contact intro (above form)', 'matthummel'), 'textarea'],
    ];
    foreach ($content as $c) {
        $san = $c[2] === 'url' ? 'esc_url_raw' : ($c[2] === 'textarea' ? 'wp_kses_post' : 'sanitize_text_field');
        $wp->add_setting($c[0], ['default' => '', 'sanitize_callback' => $san]);
        $wp->add_control($c[0], ['label' => $c[1], 'section' => 'mh_content_section', 'type' => $c[2]]);
    }
}, 23);
