<?php

/**
 * Header Layout Customizer: full-width menu toggle, header width/height/gap,
 * and element placement (order) for logo / menu / social / button.
 * Emitted as CSS via mh_head_end (priority after the Navigation panel).
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_headerlayout_section', [
        'title'       => __('Header Layout', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('Full-width menu, header size, and the position of the logo, menu, social links, and button.', 'matthummel'),
    ]);

    $number = function ($wp, $id, $label, $default, $min = 0, $max = 2000) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'absint']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_headerlayout_section', 'type' => 'number', 'input_attrs' => ['min' => $min, 'max' => $max, 'step' => 1]]);
    };

    $wp->add_setting('mh_nav_fullwidth', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_nav_fullwidth', ['label' => __('Full-width menu (spread items across)', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_header_width', ['default' => 1180, 'sanitize_callback' => 'absint']);
    $wp->add_control('mh_header_width', ['label' => __('Header width', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'select', 'choices' => \App\mh_width_options()]);
    $number($wp, 'mh_header_height', __('Header height (px, 0 = auto)', 'matthummel'), 0, 0, 240);
    $number($wp, 'mh_header_gap', __('Header gap between items (px)', 'matthummel'), 28, 0, 120);

    $number($wp, 'mh_logo_order', __('Logo position (1 = first)', 'matthummel'), 1, 1, 9);
    $number($wp, 'mh_nav_order', __('Menu position', 'matthummel'), 2, 1, 9);
    $number($wp, 'mh_social_order', __('Social links position', 'matthummel'), 3, 1, 9);
    $number($wp, 'mh_cta_order', __('Button position', 'matthummel'), 4, 1, 9);
}, 27);

add_action('mh_head_end', function () {
    $g = function ($k, $d) { return get_theme_mod($k, $d); };

    $w   = absint($g('mh_header_width', 1180));
    $h   = absint($g('mh_header_height', 0));
    $gap = absint($g('mh_header_gap', 28));

    $css = '.banner{max-width:' . $w . 'px;gap:' . $gap . 'px;';
    if ($h > 0) {
        $css .= 'min-height:' . $h . 'px;align-items:center;';
    }
    $css .= '}';

    $css .= '.banner .brand{order:' . absint($g('mh_logo_order', 1)) . ';}';
    $css .= '.banner .nav-primary{order:' . absint($g('mh_nav_order', 2)) . ';}';
    $css .= '.banner .social{order:' . absint($g('mh_social_order', 3)) . ';}';
    $css .= '.banner .header-cta,.banner .menu-toggle,.banner .mh-theme-toggle{order:' . absint($g('mh_cta_order', 4)) . ';}';

    if ($g('mh_nav_fullwidth', false)) {
        $css .= '.banner .nav-primary{flex:1 1 0%;margin-left:24px;}';
    }

    echo "\n<style id=\"mh-headerlayout\">" . $css . "</style>\n";
}, 13);
