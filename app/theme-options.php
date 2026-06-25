<?php

/**
 * Layout engine: per-content-type width preset + custom width + sidebar,
 * driven by theme mods, exposed in the Customizer (Theme Options -> Layout),
 * and applied via body classes + a precise inline width override.
 */

namespace App;

function mh_layout_defaults()
{
    return [
        'page'    => ['width' => 'default', 'maxwidth' => 0, 'sidebar' => false],
        'post'    => ['width' => 'narrow',  'maxwidth' => 0, 'sidebar' => false],
        'archive' => ['width' => 'default', 'maxwidth' => 0, 'sidebar' => false],
        'project' => ['width' => 'default', 'maxwidth' => 0, 'sidebar' => false],
    ];
}

function mh_width_choices()
{
    return [
        'default' => __('Default', 'matthummel'),
        'full'    => __('Full width', 'matthummel'),
        'narrow'  => __('Narrow', 'matthummel'),
        'boxed'   => __('Boxed', 'matthummel'),
    ];
}

function mh_layout_labels()
{
    return [
        'page'    => __('Pages', 'matthummel'),
        'post'    => __('Posts', 'matthummel'),
        'archive' => __('Archives', 'matthummel'),
        'project' => __('Projects', 'matthummel'),
    ];
}

/** Which layout bucket the current view falls into. Projects target the CPT only. */
function mh_current_layout_type()
{
    if (is_singular('projects') || is_post_type_archive('projects')) {
        return 'project';
    }
    if (is_singular('post')) {
        return 'post';
    }
    if (is_page()) {
        return 'page';
    }
    if (is_search() || is_home() || is_archive()) {
        return 'archive';
    }
    return 'page';
}

function mh_active_layout()
{
    $d = mh_layout_defaults();
    $t = mh_current_layout_type();
    return [
        'type'     => $t,
        'width'    => get_theme_mod("mh_layout_{$t}_width", $d[$t]['width']),
        'maxwidth' => absint(get_theme_mod("mh_layout_{$t}_maxwidth", $d[$t]['maxwidth'])),
        'sidebar'  => (bool) get_theme_mod("mh_layout_{$t}_sidebar", $d[$t]['sidebar']),
    ];
}

/** Layout classes on <body>. */
add_filter('body_class', function ($classes) {
    $l = mh_active_layout();
    $classes[] = 'mh-w-' . sanitize_html_class($l['width']);
    $classes[] = 'mh-type-' . sanitize_html_class($l['type']);
    if ($l['sidebar']) {
        $classes[] = 'mh-has-sidebar';
    }
    return $classes;
});

/** Per-type custom width override (emitted after app.css via the mh_head_end hook). */
add_action('mh_head_end', function () {
    $d = mh_layout_defaults();
    $css = '';
    foreach (array_keys($d) as $type) {
        $w = absint(get_theme_mod("mh_layout_{$type}_maxwidth", $d[$type]['maxwidth']));
        if ($w > 0) {
            $css .= "body.mh-type-{$type} .main .container{max-width:{$w}px}";
        }
    }
    if ($css !== '') {
        echo "\n<style id=\"mh-layout-widths\">" . $css . "</style>\n";
    }
});

/** Primary (right) sidebar widget area used when a layout enables it. */
add_action('widgets_init', function () {
    register_sidebar([
        'name'          => __('Primary Sidebar', 'matthummel'),
        'id'            => 'sidebar-primary',
        'description'   => __('Shown on the right when a layout has its sidebar enabled.', 'matthummel'),
        'before_widget' => '<section class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);
});

/** Customizer: Theme Options -> Layout. */
add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', [
            'title'    => __('Theme Options', 'matthummel'),
            'priority' => 30,
        ]);
    }

    $wp->add_section('mh_layout_section', [
        'title'       => __('Layout', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('Set a width preset and/or an exact custom width per content type. Standard widths: 1140, 1200, 1280, 1320, 1440 px.', 'matthummel'),
    ]);

    $d = mh_layout_defaults();
    foreach (mh_layout_labels() as $type => $label) {
        $wp->add_setting("mh_layout_{$type}_width", [
            'default'           => $d[$type]['width'],
            'sanitize_callback' => 'sanitize_key',
        ]);
        $wp->add_control("mh_layout_{$type}_width", [
            'label'   => sprintf(__('%s — width preset', 'matthummel'), $label),
            'section' => 'mh_layout_section',
            'type'    => 'select',
            'choices' => mh_width_choices(),
        ]);

        $wp->add_setting("mh_layout_{$type}_maxwidth", [
            'default'           => $d[$type]['maxwidth'],
            'sanitize_callback' => 'absint',
        ]);
        $wp->add_control("mh_layout_{$type}_maxwidth", [
            'label'       => sprintf(__('%s — custom width', 'matthummel'), $label),
            'description' => __('Overrides the preset above when set. "Use preset" follows the width preset.', 'matthummel'),
            'section'     => 'mh_layout_section',
            'type'        => 'select',
            'choices'     => \App\mh_width_options(true),
        ]);

        $wp->add_setting("mh_layout_{$type}_sidebar", [
            'default'           => $d[$type]['sidebar'],
            'sanitize_callback' => 'wp_validate_boolean',
        ]);
        $wp->add_control("mh_layout_{$type}_sidebar", [
            'label'   => sprintf(__('%s — show sidebar', 'matthummel'), $label),
            'section' => 'mh_layout_section',
            'type'    => 'checkbox',
        ]);
    }
}, 20);

/* ----------------------------------------------------------------------
   Top utility bar (above the main nav)
---------------------------------------------------------------------- */

/** Map a palette choice to a CSS value (global token or custom hex). */
function mh_palette_value($choice, $custom = '')
{
    $map = [
        'ink'    => 'var(--color-ink)',
        'green'  => 'var(--color-green)',
        'cream'  => 'var(--color-cream)',
        'paper'  => 'var(--color-khaki)',
        'body'   => 'var(--color-body)',
        'white'  => '#ffffff',
    ];
    if ($choice === 'custom') {
        return $custom !== '' ? $custom : 'transparent';
    }
    return $map[$choice] ?? 'transparent';
}

function mh_palette_choices()
{
    return [
        'ink'    => __('Ink (dark)', 'matthummel'),
        'green'  => __('Brand green', 'matthummel'),
        'cream'  => __('Cream', 'matthummel'),
        'paper'  => __('Paper', 'matthummel'),
        'white'  => __('White', 'matthummel'),
        'custom' => __('Custom…', 'matthummel'),
    ];
}

function mh_topbar()
{
    return [
        'enable'      => (bool) get_theme_mod('mh_topbar_enable', false),
        'contact'     => get_theme_mod('mh_topbar_contact', ''),
        'show_social' => (bool) get_theme_mod('mh_topbar_show_social', true),
        'cta_text'    => get_theme_mod('mh_topbar_cta_text', ''),
        'cta_url'     => get_theme_mod('mh_topbar_cta_url', ''),
        'bg'          => mh_palette_value(get_theme_mod('mh_topbar_bg', 'ink'), get_theme_mod('mh_topbar_bg_custom', '')),
        'text'        => mh_palette_value(get_theme_mod('mh_topbar_text', 'white'), get_theme_mod('mh_topbar_text_custom', '')),
    ];
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_topbar_section', [
        'title'       => __('Top Bar', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('A slim utility bar above the main nav for contact info, social links, and a CTA.', 'matthummel'),
    ]);

    $controls = [
        ['mh_topbar_enable', __('Enable top bar', 'matthummel'), 'checkbox', false, 'wp_validate_boolean'],
        ['mh_topbar_contact', __('Contact text (left)', 'matthummel'), 'text', '', 'sanitize_text_field'],
        ['mh_topbar_show_social', __('Show social links (right)', 'matthummel'), 'checkbox', true, 'wp_validate_boolean'],
        ['mh_topbar_cta_text', __('CTA text (right)', 'matthummel'), 'text', '', 'sanitize_text_field'],
        ['mh_topbar_cta_url', __('CTA URL', 'matthummel'), 'text', '', 'esc_url_raw'],
    ];
    foreach ($controls as $c) {
        $wp->add_setting($c[0], ['default' => $c[3], 'sanitize_callback' => $c[4]]);
        $wp->add_control($c[0], ['label' => $c[1], 'section' => 'mh_topbar_section', 'type' => $c[2]]);
    }

    foreach (['bg' => __('Background color', 'matthummel'), 'text' => __('Text color', 'matthummel')] as $slug => $label) {
        $def = $slug === 'bg' ? 'ink' : 'white';
        $wp->add_setting("mh_topbar_{$slug}", ['default' => $def, 'sanitize_callback' => 'sanitize_key']);
        $wp->add_control("mh_topbar_{$slug}", ['label' => $label, 'section' => 'mh_topbar_section', 'type' => 'select', 'choices' => mh_palette_choices()]);
        $wp->add_setting("mh_topbar_{$slug}_custom", ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
        $wp->add_control(new \WP_Customize_Color_Control($wp, "mh_topbar_{$slug}_custom", ['label' => $label . ' ' . __('(custom)', 'matthummel'), 'section' => 'mh_topbar_section']));
    }
}, 21);
