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
    // Navbar social: dedicated show/hide + L/C/R position (theme-options driven).
    $wp->add_setting('mh_nav_social', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_nav_social', ['label' => __('Show social icons in navigation bar', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    $sel($wp, 'mh_nav_social_align', __('Navigation social position', 'matthummel'), $align, 'none');
    // Hide social icons on mobile (<=640px) per bar.
    $wp->add_setting('mh_social_nav_hide_mobile', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_social_nav_hide_mobile', ['label' => __('Hide navigation social on mobile', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_social_nav_hide_desktop', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_social_nav_hide_desktop', ['label' => __('Hide navigation social on desktop', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_social_top_hide_mobile', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_social_top_hide_mobile', ['label' => __('Hide top bar social on mobile', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    // Hide CTA buttons on mobile (<=640px) per bar.
    $wp->add_setting('mh_topcta_hide_mobile', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_topcta_hide_mobile', ['label' => __('Hide top bar button on mobile', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_navcta_hide_mobile', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_navcta_hide_mobile', ['label' => __('Hide navigation button on mobile', 'matthummel'), 'section' => 'mh_nav_section', 'type' => 'checkbox']);
    $sel($wp, 'mh_social_style', __('Social links display', 'matthummel'), ['text' => __('Text', 'matthummel'), 'icons' => __('Icons', 'matthummel')], 'icons');
    $sel($wp, 'mh_social_size', __('Social icon size', 'matthummel'), ['14' => '14px', '16' => '16px', '18' => '18px', '20' => '20px', '24' => '24px', '28' => '28px'], '18');
    $sel($wp, 'mh_social_shape', __('Social icon shape', 'matthummel'), ['none' => __('Plain', 'matthummel'), 'circle' => __('Circle', 'matthummel'), 'rounded' => __('Rounded', 'matthummel'), 'square' => __('Square', 'matthummel')], 'none');
    foreach ([['mh_social_color', __('Social icon color', 'matthummel')], ['mh_social_bg', __('Social icon background (chip)', 'matthummel')], ['mh_social_hover', __('Social icon hover color', 'matthummel')]] as $cc) {
        $wp->add_setting($cc[0], ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
        $wp->add_control(new \WP_Customize_Color_Control($wp, $cc[0], ['label' => $cc[1], 'section' => 'mh_nav_section']));
    }

    $ord = ['1' => __('1 (top)', 'matthummel'), '2' => '2', '3' => __('3 (bottom)', 'matthummel')];
    $sel($wp, 'mh_bar_ann', __('Stack order: Announcement bar', 'matthummel'), $ord, '1');
    $sel($wp, 'mh_bar_top', __('Stack order: Top bar', 'matthummel'), $ord, '2');
    $sel($wp, 'mh_bar_nav', __('Stack order: Navigation bar', 'matthummel'), $ord, '3');

    $sel($wp, 'mh_popout_width', __('Popout width', 'matthummel'), [
        '0' => __('Default', 'matthummel'), '320' => '320px', '360' => '360px', '420' => '420px',
        '520' => '520px', '640' => '640px', '760' => '760px', '900' => '900px',
    ], '0');
    $sel($wp, 'mh_popout_cols', __('Popout MENU columns (desktop)', 'matthummel'), ['1' => '1', '2' => '2', '3' => '3', '4' => '4'], '1');
    $sel($wp, 'mh_popout_block_cols', __('Popout BLOCK columns (desktop)', 'matthummel'), ['1' => '1', '2' => '2', '3' => '3', '4' => '4'], '1');

    // Item alignment for the Top bar (added to the Top Bar section) and Message bar (Announcement section).
    $jal = ['none' => __('Default', 'matthummel'), 'left' => __('Left', 'matthummel'), 'center' => __('Center', 'matthummel'), 'right' => __('Right', 'matthummel'), 'between' => __('Space between', 'matthummel')];
    $wp->add_setting('mh_topbar_align', ['default' => 'none', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_topbar_align', ['label' => __('Top bar item alignment', 'matthummel'), 'section' => 'mh_topbar_section', 'type' => 'select', 'choices' => $jal]);
    $wp->add_setting('mh_msgbar_align', ['default' => 'none', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_msgbar_align', ['label' => __('Message bar item alignment', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'select', 'choices' => $jal]);

    $wopts = ['0' => __('Default', 'matthummel'), 'full' => __('Full width', 'matthummel')] + (function_exists('App\\mh_width_options') ? mh_width_options() : []);
    $wp->add_setting('mh_topbar_width', ['default' => '0', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_topbar_width', ['label' => __('Top bar width', 'matthummel'), 'section' => 'mh_topbar_section', 'type' => 'select', 'choices' => $wopts]);
    $wp->add_setting('mh_msgbar_width', ['default' => '0', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_msgbar_width', ['label' => __('Message bar width', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'select', 'choices' => $wopts]);
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
    $sa = $map(get_theme_mod('mh_nav_social_align', 'none'));
    if ($sa !== '') {
        $css .= '.banner .social{' . $sa . '}';
    }

    // Hide social icons on mobile (<=640px) per bar.
    if (get_theme_mod('mh_social_nav_hide_mobile', false)) {
        $css .= '@media(max-width:640px){.banner .social{display:none!important;}}';
    }
    if (get_theme_mod('mh_social_nav_hide_desktop', false)) {
        $css .= '@media(min-width:641px){.banner .social{display:none!important;}}';
    }
    if (get_theme_mod('mh_social_top_hide_mobile', false)) {
        $css .= '@media(max-width:640px){.top-bar-social{display:none!important;}}';
    }
    if (get_theme_mod('mh_topcta_hide_mobile', false)) {
        $css .= '@media(max-width:640px){.top-bar-cta{display:none!important;}}';
    }
    if (get_theme_mod('mh_navcta_hide_mobile', false)) {
        $css .= '@media(max-width:640px){.banner .header-cta{display:none!important;}}';
    }

    // Icon styling for header social links (only when display = icons).
    if (get_theme_mod('mh_social_style', 'icons') === 'icons') {
        $size  = max(10, absint(get_theme_mod('mh_social_size', 18)));
        $shape = get_theme_mod('mh_social_shape', 'none');
        $color = sanitize_hex_color(get_theme_mod('mh_social_color', ''));
        $bg    = sanitize_hex_color(get_theme_mod('mh_social_bg', ''));
        $hover = sanitize_hex_color(get_theme_mod('mh_social_hover', ''));
        $chip  = $shape !== 'none';
        $pad   = $chip ? max(5, (int) round($size * 0.5)) : 0;
        $radius = $shape === 'circle' ? '50%' : ($shape === 'rounded' ? (string) max(4, (int) round($size * 0.35)) . 'px' : '0');
        // Distribute each suffix across BOTH base selectors. Appending e.g. " a svg"
        // to a comma-grouped base only qualifies the LAST member, so the first
        // (".top-bar-social.is-icons", the <ul>) would match bare and inherit the
        // descendant's display:block — collapsing the list to a stack. This helper
        // keeps every rule scoped to the intended element on both bars.
        $bases = ['.top-bar-social.is-icons', '.social.is-icons'];
        $grp = function ($suffix) use ($bases) {
            return implode(',', array_map(static function ($b) use ($suffix) {
                return $b . $suffix;
            }, $bases));
        };
        $css .= $grp('') . '{display:inline-flex;align-items:center;gap:' . ($chip ? '8' : '14') . 'px;list-style:none;margin:0;padding:0;}';
        $css .= $grp(' li') . '{margin:0;list-style:none;}';
        $css .= $grp(' a') . '{display:inline-flex;align-items:center;justify-content:center;padding:' . $pad . 'px;border-radius:' . $radius . ';transition:color .15s ease,background .15s ease,transform .15s ease;'
            . ($color ? 'color:' . $color . ';' : 'color:currentColor;')
            . ($chip && $bg ? 'background:' . $bg . ';' : '') . '}';
        $css .= $grp(' a svg') . '{width:' . $size . 'px;height:' . $size . 'px;fill:currentColor;display:block;}';
        if ($hover) {
            $css .= $grp(' a:hover') . '{color:' . $hover . ';transform:translateY(-1px);}';
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

    // Top bar / message bar item alignment.
    $jmap = function ($v) {
        switch ($v) {
            case 'left':    return 'flex-start';
            case 'center':  return 'center';
            case 'right':   return 'flex-end';
            case 'between': return 'space-between';
        }
        return '';
    };
    $tba = $jmap(get_theme_mod('mh_topbar_align', 'none'));
    if ($tba) {
        $css .= '.top-bar-inner{display:flex;align-items:center;gap:16px;justify-content:' . $tba . ';}';
    }
    $mba = $jmap(get_theme_mod('mh_msgbar_align', 'none'));
    if ($mba) {
        $css .= '.mh-ann-inner{display:flex;align-items:center;gap:14px;text-align:left;justify-content:' . $mba . ';}';
    }

    // Bar widths (constrain the inner content; background stays full-width).
    $bw = function ($v) {
        if ($v === 'full') {
            return 'max-width:none;';
        }
        if ($v && $v !== '0') {
            return 'max-width:' . absint($v) . 'px;';
        }
        return '';
    };
    $tw = $bw(get_theme_mod('mh_topbar_width', '0'));
    if ($tw !== '') {
        $css .= '.top-bar .top-bar-inner{' . $tw . '}';
    }
    $mw = $bw(get_theme_mod('mh_msgbar_width', '0'));
    if ($mw !== '') {
        $css .= '.mh-ann .mh-ann-inner{' . $mw . '}';
    }

    // Base styling for blocks placed in the bars (only when those areas are in use).
    if (is_active_sidebar('topbar') || is_active_sidebar('messagebar') || is_active_sidebar('navbar')) {
        $css .= '.top-bar-blocks,.mh-ann-blocks,.nav-blocks{display:inline-flex;align-items:center;gap:14px;}'
            . '.top-bar-blocks *,.mh-ann-blocks *,.nav-blocks *{color:inherit;}'
            . '.nav-blocks{margin-left:8px;}';
    }

    if ($css !== '') {
        echo "\n<style id=\"mh-header-elements\">" . $css . "</style>\n";
    }
}, 14);


/** Block widget columns for the off-canvas popout (Appearance -> Widgets). */
add_action('widgets_init', function () {
    for ($i = 1; $i <= 4; $i++) {
        register_sidebar([
            'name'          => sprintf(__('Popout column %d', 'matthummel'), $i),
            'id'            => "popout-{$i}",
            'description'   => __('Blocks shown in the off-canvas popout panel. Column count is set in Customize -> Navigation.', 'matthummel'),
            'before_widget' => '<div class="mh-pop-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="mh-pop-widget-title">',
            'after_title'   => '</h4>',
        ]);
    }
    // The top bar, message bar, and navigation bar are configured via Theme Options
    // (Customizer), not widgets — so no bar widget areas are registered here.
});

/** Popout block-column layout (only when popout widgets exist). */
add_action('mh_head_end', function () {
    $active = false;
    for ($i = 1; $i <= 4; $i++) {
        if (is_active_sidebar("popout-{$i}")) { $active = true; break; }
    }
    if (! $active) {
        return;
    }
    echo "\n<style id=\"mh-popout-blocks\">"
        . '.mh-popout-blocks{display:grid;gap:18px 28px;margin-top:22px;}'
        . '.mh-popout-blocks,.mh-popout-blocks a,.mh-popout-blocks *{color:inherit;}'
        . '.mh-pop-widget-title{font-size:13px;text-transform:uppercase;letter-spacing:.08em;opacity:.7;margin:0 0 8px;}'
        . '@media(min-width:1024px){.mh-popout-blocks--cols-2{grid-template-columns:repeat(2,1fr);}.mh-popout-blocks--cols-3{grid-template-columns:repeat(3,1fr);}.mh-popout-blocks--cols-4{grid-template-columns:repeat(4,1fr);}}'
        . "</style>\n";
}, 15);
