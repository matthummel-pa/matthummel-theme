<?php

/**
 * Extra header/navigation controls (added to the Customizer "Navigation" section):
 *  - Per-element alignment for the logo, dark-mode icon, and popout button (L/C/R).
 *  - Reorder the three stacked bars: announcement, top bar, navigation.
 *  - Popout width + multi-column popout menu on desktop.
 * All opt-in: nothing is emitted until a setting is changed, so the default
 * layout is untouched.
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    if (! $wp->get_section('mh_nav_section')) {
        $wp->add_section('mh_nav_section', ['title' => __('Navigation', 'matthummel'), 'panel' => 'mh_theme_options']);
    }

    $sel = function ($wp, $id, $label, $choices, $default) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'sanitize_key']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_nav_section', 'type' => 'select', 'choices' => $choices]);
    };

    $align = ['none' => __('Default', 'matthummel'), 'left' => __('Left', 'matthummel'), 'center' => __('Center', 'matthummel'), 'right' => __('Right', 'matthummel')];
    $sel($wp, 'mh_logo_align', __('Logo position', 'matthummel'), $align, 'none');
    $sel($wp, 'mh_darkicon_align', __('Dark-mode icon position', 'matthummel'), $align, 'none');
    $sel($wp, 'mh_popbtn_align', __('Menu (popout) button position', 'matthummel'), $align, 'none');
    $sel($wp, 'mh_cta_align', __('Header button (CTA) position', 'matthummel'), $align, 'none');

    $ord = ['1' => __('1 (top)', 'matthummel'), '2' => '2', '3' => __('3 (bottom)', 'matthummel')];
    $sel($wp, 'mh_bar_ann', __('Stack order: Announcement bar', 'matthummel'), $ord, '1');
    $sel($wp, 'mh_bar_top', __('Stack order: Top bar', 'matthummel'), $ord, '2');
    $sel($wp, 'mh_bar_nav', __('Stack order: Navigation bar', 'matthummel'), $ord, '3');

    $sel($wp, 'mh_popout_width', __('Popout width', 'matthummel'), [
        '0' => __('Default', 'matthummel'), '320' => '320px', '360' => '360px', '420' => '420px',
        '520' => '520px', '640' => '640px', '760' => '760px', '900' => '900px',
    ], '0');
    $sel($wp, 'mh_popout_cols', __('Popout columns (desktop)', 'matthummel'), ['1' => '1', '2' => '2', '3' => '3', '4' => '4'], '1');
}, 13);

add_action('mh_head_end', function () {
    $css = '';

    $map = function ($v) {
        if ($v === 'left')   return 'margin-right:auto;';
        if ($v === 'right')  return 'margin-left:auto;';
        if ($v === 'center') return 'margin-left:auto;margin-right:auto;';
        return '';
    };
    foreach ([
        'mh_logo_align'     => '.banner .brand',
        'mh_darkicon_align' => '.banner .mh-theme-toggle',
        'mh_popbtn_align'   => '.banner .menu-toggle',
        'mh_cta_align'      => '.banner .header-cta',
    ] as $mod => $sel) {
        $m = $map(get_theme_mod($mod, 'none'));
        if ($m !== '') {
            $css .= $sel . '{' . $m . '}';
        }
    }

    // Reorder the three top bars (only when changed from default 1/2/3).
    $ann = absint(get_theme_mod('mh_bar_ann', 1));
    $top = absint(get_theme_mod('mh_bar_top', 2));
    $nav = absint(get_theme_mod('mh_bar_nav', 3));
    if (! ($ann === 1 && $top === 2 && $nav === 3)) {
        $css .= '#app{display:flex;flex-direction:column;}';
        $css .= '#app > a:first-child{order:-10;}';
        $css .= '.mh-ann{order:' . $ann . ';}.top-bar{order:' . $top . ';}.banner{order:' . $nav . ';}';
        $css .= '.mh-popout-overlay,#mh-popout{order:0;}';
        $css .= '.main-wrap{order:90;}.content-info{order:91;}';
    }

    // Popout width + desktop columns.
    $w = absint(get_theme_mod('mh_popout_width', 0));
    if ($w) {
        $css .= '#mh-popout.mh-popout{width:' . $w . 'px;max-width:92vw;}';
    }
    $cols = absint(get_theme_mod('mh_popout_cols', 1));
    if ($cols > 1) {
        $css .= '@media(min-width:1024px){#mh-popout .mh-popout-menu{display:grid;grid-template-columns:repeat(' . $cols . ',minmax(0,1fr));gap:6px 28px;align-items:start;}}';
    }

    if ($css !== '') {
        echo "\n<style id=\"mh-header-elements\">" . $css . "</style>\n";
    }
}, 14);
