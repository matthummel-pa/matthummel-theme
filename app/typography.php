<?php

/**
 * Advanced typography: assign fonts + weights per element (headings, body, nav,
 * buttons), nav casing, body letter-spacing, and responsive base font sizes.
 * Reuses \App\mh_fonts() for the font list + CSS stacks and loads any extra
 * Google families the nav/buttons need.
 */

namespace App;

function mh_type_font_choices()
{
    $choices = ['Default' => __('Default (inherit)', 'matthummel')];
    if (function_exists('App\\mh_fonts')) {
        foreach (array_keys(mh_fonts()) as $n) {
            $choices[$n] = $n;
        }
    }
    return $choices;
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_type_adv', [
        'title' => __('Typography (advanced)', 'matthummel'),
        'panel' => 'mh_theme_options',
        'description' => __('Fine-grained control per element. Heading and body font families live in the main Typography section; this adds nav/buttons, weights, and responsive sizes.', 'matthummel'),
    ]);

    $fontChoices = mh_type_font_choices();
    $sel = function ($wp, $id, $label, $choices, $default) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'sanitize_text_field']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_type_adv', 'type' => 'select', 'choices' => $choices]);
    };

    $sel($wp, 'mh_font_nav', __('Navigation font', 'matthummel'), $fontChoices, 'Default');
    $sel($wp, 'mh_font_button', __('Button font', 'matthummel'), $fontChoices, 'Default');

    $weights = ['300' => '300 Light', '400' => '400 Regular', '500' => '500 Medium', '600' => '600 Semibold', '700' => '700 Bold', '800' => '800 Extrabold'];
    $sel($wp, 'mh_weight_heading', __('Heading weight', 'matthummel'), $weights, '600');
    $sel($wp, 'mh_weight_body', __('Body weight', 'matthummel'), ['300' => '300 Light', '400' => '400 Regular', '500' => '500 Medium'], '400');
    $sel($wp, 'mh_weight_nav', __('Nav weight', 'matthummel'), $weights, '500');
    $sel($wp, 'mh_weight_button', __('Button weight', 'matthummel'), $weights, '600');

    $sel($wp, 'mh_nav_case', __('Nav letter case', 'matthummel'), ['none' => __('Normal', 'matthummel'), 'uppercase' => __('UPPERCASE', 'matthummel'), 'lowercase' => __('lowercase', 'matthummel')], 'none');
    $sel($wp, 'mh_ls_body', __('Body letter spacing', 'matthummel'), ['-0.01em' => 'Tight', '0' => 'Normal', '0.01em' => 'Loose'], '0');

    $px = ['auto' => __('Auto (use base)', 'matthummel'), '14' => '14px', '15' => '15px', '16' => '16px', '17' => '17px', '18' => '18px'];
    $sel($wp, 'mh_base_tablet', __('Base font on tablet', 'matthummel'), $px, 'auto');
    $sel($wp, 'mh_base_mobile', __('Base font on mobile', 'matthummel'), $px, 'auto');
}, 24);

/** Load extra Google families for nav/button if they differ. */
add_action('wp_enqueue_scripts', function () {
    if (! function_exists('App\\mh_fonts')) {
        return;
    }
    $fonts = mh_fonts();
    $want  = [];
    foreach (['mh_font_nav', 'mh_font_button'] as $k) {
        $name = get_theme_mod($k, 'Default');
        if ($name !== 'Default' && isset($fonts[$name]) && ! empty($fonts[$name][0])) {
            $want[$fonts[$name][0]] = true;
        }
    }
    if ($want) {
        wp_enqueue_style(
            'mh-fonts-extra',
            'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_keys($want)) . '&display=swap',
            [],
            null
        );
    }
}, 9);

add_action('mh_head_end', function () {
    $fonts = function_exists('App\\mh_fonts') ? mh_fonts() : [];
    $stack = function ($name) use ($fonts) {
        return ($name !== 'Default' && isset($fonts[$name][1])) ? $fonts[$name][1] : '';
    };
    $css = '';

    $nav = $stack(get_theme_mod('mh_font_nav', 'Default'));
    $btn = $stack(get_theme_mod('mh_font_button', 'Default'));
    $navCss = ($nav ? 'font-family:' . $nav . ';' : '') . 'font-weight:' . absint(get_theme_mod('mh_weight_nav', 500)) . ';';
    $case = get_theme_mod('mh_nav_case', 'none');
    if (in_array($case, ['uppercase', 'lowercase'], true)) {
        $navCss .= 'text-transform:' . $case . ';' . ($case === 'uppercase' ? 'letter-spacing:.04em;' : '');
    }
    $css .= '.nav a,.nav-primary a{' . $navCss . '}';

    $btnCss = ($btn ? 'font-family:' . $btn . ';' : '') . 'font-weight:' . absint(get_theme_mod('mh_weight_button', 600)) . ';';
    $css .= '.btn,.btn-outline,.wp-element-button,.wp-block-button__link,button.btn{' . $btnCss . '}';

    $css .= 'h1,h2,h3,h4,h5,h6{font-weight:' . absint(get_theme_mod('mh_weight_heading', 600)) . ';}';

    $ls = preg_replace('/[^0-9.\-em]/', '', (string) get_theme_mod('mh_ls_body', '0'));
    $css .= 'body{font-weight:' . absint(get_theme_mod('mh_weight_body', 400)) . ';letter-spacing:' . ($ls === '' ? '0' : $ls) . ';}';

    $t = get_theme_mod('mh_base_tablet', 'auto');
    $m = get_theme_mod('mh_base_mobile', 'auto');
    if ($t !== 'auto') {
        $css .= '@media(max-width:1024px){body{font-size:' . absint($t) . 'px;}}';
    }
    if ($m !== 'auto') {
        $css .= '@media(max-width:600px){body{font-size:' . absint($m) . 'px;}}';
    }

    echo "\n<style id=\"mh-typography\">" . $css . "</style>\n";
}, 17);
