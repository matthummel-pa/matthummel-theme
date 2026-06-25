<?php

/**
 * Navigation Customizer panel: full flexbox control for the main menu,
 * per-item height/padding/typography, and expanded popout-menu controls.
 * Emitted as CSS via mh_head_end (no rebuild needed).
 */

namespace App;

function mh_nav_choices()
{
    return [
        'dir'      => ['row' => 'Row', 'row-reverse' => 'Row reverse', 'column' => 'Column', 'column-reverse' => 'Column reverse'],
        'justify'  => ['flex-start' => 'Start', 'center' => 'Center', 'flex-end' => 'End', 'space-between' => 'Space between', 'space-around' => 'Space around', 'space-evenly' => 'Space evenly'],
        'align'    => ['stretch' => 'Stretch', 'flex-start' => 'Start', 'center' => 'Center', 'flex-end' => 'End', 'baseline' => 'Baseline'],
        'content'  => ['stretch' => 'Stretch', 'flex-start' => 'Start', 'center' => 'Center', 'flex-end' => 'End', 'space-between' => 'Space between', 'space-around' => 'Space around'],
        'wrap'     => ['nowrap' => 'No wrap', 'wrap' => 'Wrap', 'wrap-reverse' => 'Wrap reverse'],
        'weight'   => ['400' => 'Regular', '500' => 'Medium', '600' => 'Semibold', '700' => 'Bold'],
        'transform'=> ['none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'],
        'textalign'=> ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'],
    ];
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_nav_section', [
        'title'       => __('Navigation', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('Flexbox layout for the menu, per-item sizing/typography, and the popout menu.', 'matthummel'),
    ]);
    $c = mh_nav_choices();

    $select = function ($wp, $id, $label, $choices, $default) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'sanitize_key']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_nav_section', 'type' => 'select', 'choices' => $choices]);
    };
    $number = function ($wp, $id, $label, $default, $max = 80) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'absint']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_nav_section', 'type' => 'number', 'input_attrs' => ['min' => 0, 'max' => $max, 'step' => 1]]);
    };
    $color = function ($wp, $id, $label) {
        $wp->add_setting($id, ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
        $wp->add_control(new \WP_Customize_Color_Control($wp, $id, ['label' => $label, 'section' => 'mh_nav_section']));
    };

    /* Menu container — flexbox */
    $select($wp, 'mh_nav_dir', __('Menu — direction', 'matthummel'), $c['dir'], 'row');
    $select($wp, 'mh_nav_justify', __('Menu — justify content', 'matthummel'), $c['justify'], 'flex-start');
    $select($wp, 'mh_nav_align', __('Menu — align items', 'matthummel'), $c['align'], 'center');
    $select($wp, 'mh_nav_aligncontent', __('Menu — align content (wrap)', 'matthummel'), $c['content'], 'stretch');
    $select($wp, 'mh_nav_wrap', __('Menu — flex wrap', 'matthummel'), $c['wrap'], 'nowrap');
    $number($wp, 'mh_nav_gap', __('Menu — gap (px)', 'matthummel'), 26);

    /* Menu items — box + type */
    $number($wp, 'mh_nav_pad_y', __('Item — padding top/bottom (px)', 'matthummel'), 0);
    $number($wp, 'mh_nav_pad_x', __('Item — padding left/right (px)', 'matthummel'), 0);
    $number($wp, 'mh_nav_height', __('Item — min height (px, 0 = auto)', 'matthummel'), 0, 120);
    $number($wp, 'mh_nav_font', __('Item — font size (px)', 'matthummel'), 15, 40);
    $select($wp, 'mh_nav_weight', __('Item — font weight', 'matthummel'), $c['weight'], '500');
    $select($wp, 'mh_nav_transform', __('Item — text transform', 'matthummel'), $c['transform'], 'none');
    $number($wp, 'mh_nav_spacing', __('Item — letter spacing (px)', 'matthummel'), 0, 10);
    $number($wp, 'mh_nav_radius', __('Item — corner radius (px)', 'matthummel'), 0, 40);
    $color($wp, 'mh_nav_color', __('Item — color', 'matthummel'));
    $color($wp, 'mh_nav_hover', __('Item — hover color', 'matthummel'));

    /* Popout menu */
    $select($wp, 'mh_pop_align', __('Popout — text align', 'matthummel'), $c['textalign'], 'left');
    $number($wp, 'mh_pop_pad_y', __('Popout — item padding (px)', 'matthummel'), 13, 60);
    $number($wp, 'mh_pop_font', __('Popout — item font size (px)', 'matthummel'), 19, 48);
    $select($wp, 'mh_pop_weight', __('Popout — item weight', 'matthummel'), $c['weight'], '600');
    $select($wp, 'mh_pop_transform', __('Popout — item transform', 'matthummel'), $c['transform'], 'none');
    $number($wp, 'mh_pop_gap', __('Popout — gap between items (px)', 'matthummel'), 0, 40);
}, 26);

add_action('mh_head_end', function () {
    $g = function ($k, $d) { return get_theme_mod($k, $d); };

    $css = '.nav-primary .nav{'
        . 'flex-direction:' . sanitize_key($g('mh_nav_dir', 'row')) . ';'
        . 'justify-content:' . sanitize_key($g('mh_nav_justify', 'flex-start')) . ';'
        . 'align-items:' . sanitize_key($g('mh_nav_align', 'center')) . ';'
        . 'align-content:' . sanitize_key($g('mh_nav_aligncontent', 'stretch')) . ';'
        . 'flex-wrap:' . sanitize_key($g('mh_nav_wrap', 'nowrap')) . ';'
        . 'gap:' . absint($g('mh_nav_gap', 26)) . 'px;'
        . '}';

    $h   = absint($g('mh_nav_height', 0));
    $col = sanitize_hex_color($g('mh_nav_color', ''));
    $hov = sanitize_hex_color($g('mh_nav_hover', ''));
    $item = 'display:inline-flex;align-items:center;'
        . 'padding:' . absint($g('mh_nav_pad_y', 0)) . 'px ' . absint($g('mh_nav_pad_x', 0)) . 'px;'
        . 'font-size:' . absint($g('mh_nav_font', 15)) . 'px;'
        . 'font-weight:' . absint($g('mh_nav_weight', 500)) . ';'
        . 'text-transform:' . sanitize_key($g('mh_nav_transform', 'none')) . ';'
        . 'letter-spacing:' . absint($g('mh_nav_spacing', 0)) . 'px;'
        . 'border-radius:' . absint($g('mh_nav_radius', 0)) . 'px;';
    if ($h > 0) { $item .= 'min-height:' . $h . 'px;'; }
    if ($col) { $item .= 'color:' . $col . ';'; }
    $css .= '.nav-primary .nav a{' . $item . '}';
    if ($hov) { $css .= '.nav-primary .nav a:hover{color:' . $hov . ';}'; }

    $css .= '.mh-popout-menu{text-align:' . sanitize_key($g('mh_pop_align', 'left')) . ';}';
    $pgap = absint($g('mh_pop_gap', 0));
    $pitem = 'padding-top:' . absint($g('mh_pop_pad_y', 13)) . 'px;'
        . 'padding-bottom:' . absint($g('mh_pop_pad_y', 13)) . 'px;'
        . 'font-size:' . absint($g('mh_pop_font', 19)) . 'px;'
        . 'font-weight:' . absint($g('mh_pop_weight', 600)) . ';'
        . 'text-transform:' . sanitize_key($g('mh_pop_transform', 'none')) . ';';
    if ($pgap > 0) { $pitem .= 'margin-bottom:' . $pgap . 'px;'; }
    $css .= '.mh-popout-menu a{' . $pitem . '}';

    echo "\n<style id=\"mh-nav\">" . $css . "</style>\n";
}, 12);
