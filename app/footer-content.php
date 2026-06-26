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

    // (Sticky header lives in Header Layout → mh_header_sticky; the old duplicate here was removed.)
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

/** Emit Customizer-driven theme colors as CSS variables (keeps markup free of inline styles). */
add_action('mh_head_end', function () {
    $tb = function_exists('App\\mh_topbar') ? mh_topbar() : ['bg' => 'var(--color-ink)', 'text' => '#fff'];
    $po = function_exists('App\\mh_popout') ? mh_popout() : ['bg' => '#17191e', 'text' => '#fff'];
    $ft = mh_footer();
    $css = ':root{'
        . '--mh-topbar-bg:' . $tb['bg'] . ';--mh-topbar-text:' . $tb['text'] . ';'
        . '--mh-popout-bg:' . $po['bg'] . ';--mh-popout-text:' . $po['text'] . ';'
        . '--mh-footer-bg:' . $ft['bg'] . ';--mh-footer-text:' . $ft['text'] . ';'
        . '}';
    echo "\n<style id=\"mh-theme-vars\">" . $css . "</style>\n";
}, 11);

/** Footer column widget areas (block-based widgets). */
add_action('widgets_init', function () {
    for ($i = 1; $i <= 4; $i++) {
        register_sidebar([
            'name'          => sprintf(__('Footer Column %d', 'matthummel'), $i),
            'id'            => "footer-{$i}",
            'description'   => __('Drop any blocks here. Shown when "Footer columns" includes this column.', 'matthummel'),
            'before_widget' => '<section class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="footer-widget-title">',
            'after_title'   => '</h2>',
        ]);
    }
});

/** Footer column count control (added to the Footer & Header section). */
add_action('customize_register', function ($wp) {
    if ($wp->get_section('mh_footer_section')) {
        $wp->add_setting('mh_footer_cols', ['default' => 3, 'sanitize_callback' => 'absint']);
        $wp->add_control('mh_footer_cols', [
            'label'       => __('Footer columns', 'matthummel'),
            'description' => __('Add blocks to each column in Appearance > Widgets (Footer Column 1-4).', 'matthummel'),
            'section'     => 'mh_footer_section',
            'type'        => 'select',
            'choices'     => [1 => '1 column', 2 => '2 columns', 3 => '3 columns', 4 => '4 columns'],
        ]);
    }
}, 24);
