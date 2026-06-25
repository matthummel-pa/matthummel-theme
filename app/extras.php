<?php

/**
 * Typography & Extras Customizer: base font size, line heights, link underline,
 * button + card radius, text-selection color, and a scroll-to-top button.
 * Emitted as CSS via mh_head_end (no rebuild).
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_extras_section', ['title' => __('Typography & Extras', 'matthummel'), 'panel' => 'mh_theme_options']);

    $select = function ($wp, $id, $label, $choices, $default) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'sanitize_text_field']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_extras_section', 'type' => 'select', 'choices' => $choices]);
    };
    $bool = function ($wp, $id, $label, $default) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'wp_validate_boolean']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_extras_section', 'type' => 'checkbox']);
    };

    $select($wp, 'mh_base_font', __('Base font size', 'matthummel'), ['15' => '15px', '16' => '16px', '17' => '17px (default)', '18' => '18px', '19' => '19px'], '17');
    $select($wp, 'mh_body_lh', __('Body line height', 'matthummel'), ['1.5' => 'Tight (1.5)', '1.6' => '1.6', '1.7' => '1.7 (default)', '1.8' => 'Relaxed (1.8)', '2' => 'Loose (2.0)'], '1.7');
    $select($wp, 'mh_head_lh', __('Heading line height', 'matthummel'), ['1' => '1.0', '1.1' => '1.1', '1.12' => '1.12 (default)', '1.2' => '1.2', '1.3' => '1.3'], '1.12');
    $select($wp, 'mh_head_spacing', __('Heading letter spacing', 'matthummel'), ['-0.03em' => 'Tighter', '-0.02em' => 'Tight (default)', '0' => 'Normal', '0.02em' => 'Wide'], '-0.02em');
    $bool($wp, 'mh_link_underline', __('Underline content links', 'matthummel'), false);
    $select($wp, 'mh_btn_radius', __('Button corner radius', 'matthummel'), ['0' => 'Square', '4' => '4px', '8' => '8px (default)', '12' => '12px', '999' => 'Pill'], '8');
    $select($wp, 'mh_card_radius', __('Card corner radius', 'matthummel'), ['6' => '6px', '10' => '10px', '14' => '14px', '16' => '16px (default)', '20' => '20px'], '16');
    $bool($wp, 'mh_scrolltop', __('Show scroll-to-top button', 'matthummel'), true);

    $wp->add_setting('mh_selection', ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp->add_control(new \WP_Customize_Color_Control($wp, 'mh_selection', ['label' => __('Text selection color', 'matthummel'), 'section' => 'mh_extras_section']));
}, 28);

add_action('mh_head_end', function () {
    $g = function ($k, $d) { return get_theme_mod($k, $d); };
    $css  = 'body{font-size:' . absint($g('mh_base_font', 17)) . 'px;line-height:' . floatval($g('mh_body_lh', '1.7')) . ';}';
    $ls   = preg_replace('/[^0-9.\-em]/', '', (string) $g('mh_head_spacing', '-0.02em'));
    $css .= 'h1,h2,h3,h4{line-height:' . floatval($g('mh_head_lh', '1.12')) . ';letter-spacing:' . $ls . ';}';
    if ($g('mh_link_underline', false)) {
        $css .= '.post-prose a,.entry-content a{text-decoration:underline;text-underline-offset:2px;}';
    }
    $css .= '.btn,.btn-outline{border-radius:' . absint($g('mh_btn_radius', 8)) . 'px;}';
    $css .= '.mini-card,.project-card,.cta-card,.service-card{border-radius:' . absint($g('mh_card_radius', 16)) . 'px;}';
    $sel = sanitize_hex_color($g('mh_selection', ''));
    if ($sel) {
        $css .= '::selection{background:' . $sel . ';color:#fff;}';
    }
    if ($g('mh_scrolltop', true)) {
        $css .= '.mh-totop{position:fixed;right:20px;bottom:20px;width:44px;height:44px;border-radius:50%;border:0;background:var(--color-green);color:#fff;font-size:20px;line-height:1;cursor:pointer;opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease;z-index:90;box-shadow:0 6px 20px rgba(23,25,30,.18);}.mh-totop.is-visible{opacity:1;visibility:visible;}.mh-totop:hover{background:var(--color-green-ink);}';
    }
    echo "\n<style id=\"mh-extras\">" . $css . "</style>\n";
}, 14);

add_action('wp_footer', function () {
    if (! get_theme_mod('mh_scrolltop', true)) {
        return;
    }
    echo '<button class="mh-totop" type="button" aria-label="' . esc_attr__('Back to top', 'matthummel') . '">&uarr;</button>';
    echo "<script>(function(){var b=document.querySelector('.mh-totop');if(!b)return;function t(){b.classList.toggle('is-visible',window.scrollY>400);}window.addEventListener('scroll',t,{passive:true});t();b.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});})();</script>";
}, 55);
