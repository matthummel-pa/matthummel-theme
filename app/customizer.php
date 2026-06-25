<?php

/**
 * Theme Options - colors, fonts, layout width, header CTA, footer text.
 * Emits CSS-variable overrides after app.css so changes apply without a rebuild.
 */

namespace App;

function mh_defaults()
{
    return [
        'mh_color_action' => '#2f6b4e',
        'mh_color_paper'  => '#fbfaf7',
        'mh_color_ink'    => '#17191e',
        'mh_color_body'   => '#2b2f36',
        'mh_font_heading' => 'Geist',
        'mh_font_body'    => 'Inter',
        'mh_container'    => 1180,
        'mh_show_cta'     => true,
        'mh_cta_text'     => 'Find me on Dev.to',
        'mh_cta_url'      => 'https://dev.to/mattbuildsapps',
        'mh_footer_text'  => '',
    ];
}

function mh_mod($key)
{
    $d = mh_defaults();
    return get_theme_mod($key, $d[$key] ?? '');
}

/** name => [google css2 family param (or null), css font stack] */
function mh_fonts()
{
    return [
        'Geist'               => ['Geist:wght@400;500;600;700', '"Geist", system-ui, sans-serif'],
        'Bricolage Grotesque' => ['Bricolage+Grotesque:opsz,wght@12..96,400..800', '"Bricolage Grotesque", system-ui, sans-serif'],
        'Schibsted Grotesk'   => ['Schibsted+Grotesk:wght@400;500;600;700', '"Schibsted Grotesk", system-ui, sans-serif'],
        'Space Grotesk'       => ['Space+Grotesk:wght@400;500;600;700', '"Space Grotesk", system-ui, sans-serif'],
        'Sora'                => ['Sora:wght@400;500;600;700', '"Sora", system-ui, sans-serif'],
        'Inter Tight'         => ['Inter+Tight:wght@400;500;600;700', '"Inter Tight", system-ui, sans-serif'],
        'Fraunces'            => ['Fraunces:opsz,wght@9..144,400..700', '"Fraunces", Georgia, serif'],
        'Inter'               => ['Inter:wght@400;500;600;700', '"Inter", system-ui, sans-serif'],
        'Work Sans'           => ['Work+Sans:wght@400;500;600;700', '"Work Sans", system-ui, sans-serif'],
        'System'              => [null, 'system-ui, -apple-system, sans-serif'],
    ];
}

add_action('customize_register', function ($wp) {
    $d = mh_defaults();

    $wp->add_panel('mh_theme_options', [
        'title'    => __('Theme Options', 'matthummel'),
        'priority' => 30,
    ]);

    /* Colors */
    $wp->add_section('mh_colors', ['title' => __('Colors', 'matthummel'), 'panel' => 'mh_theme_options']);
    $colors = [
        'mh_color_action' => __('Brand / buttons', 'matthummel'),
        'mh_color_paper'  => __('Page background', 'matthummel'),
        'mh_color_ink'    => __('Headings', 'matthummel'),
        'mh_color_body'   => __('Body text', 'matthummel'),
    ];
    foreach ($colors as $id => $label) {
        $wp->add_setting($id, ['default' => $d[$id], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh']);
        $wp->add_control(new \WP_Customize_Color_Control($wp, $id, ['label' => $label, 'section' => 'mh_colors']));
    }

    /* Typography */
    $wp->add_section('mh_type', ['title' => __('Typography', 'matthummel'), 'panel' => 'mh_theme_options']);
    $choices = array_combine(array_keys(mh_fonts()), array_keys(mh_fonts()));
    $wp->add_setting('mh_font_heading', ['default' => $d['mh_font_heading'], 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_font_heading', ['label' => __('Heading font', 'matthummel'), 'section' => 'mh_type', 'type' => 'select', 'choices' => $choices]);
    $wp->add_setting('mh_font_body', ['default' => $d['mh_font_body'], 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_font_body', ['label' => __('Body font', 'matthummel'), 'section' => 'mh_type', 'type' => 'select', 'choices' => $choices]);

    /* Layout width */
    $wp->add_setting('mh_container', ['default' => $d['mh_container'], 'sanitize_callback' => 'absint']);
    $wp->add_control('mh_container', ['label' => __('Content width', 'matthummel'), 'section' => 'mh_layout_section', 'type' => 'select', 'choices' => mh_width_options()]);

    /* Header */
    $wp->add_setting('mh_show_cta', ['default' => $d['mh_show_cta'], 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_show_cta', ['label' => __('Show header button', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_cta_text', ['default' => $d['mh_cta_text'], 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_cta_text', ['label' => __('Button text', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'text']);
    $wp->add_setting('mh_cta_url', ['default' => $d['mh_cta_url'], 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control('mh_cta_url', ['label' => __('Button URL', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'url']);

    /* Footer */
    $wp->add_setting('mh_footer_text', ['default' => $d['mh_footer_text'], 'sanitize_callback' => 'wp_kses_post']);
    $wp->add_control('mh_footer_text', ['label' => __('Footer tagline', 'matthummel'), 'section' => 'mh_footer_section', 'type' => 'textarea']);
});

/* Wire values into the theme's existing filter hooks */
add_filter('matthummel/header_cta_label', fn () => mh_mod('mh_cta_text'));
add_filter('matthummel/header_cta_url', fn () => mh_mod('mh_cta_url'));
add_filter('matthummel/show_header_cta', fn () => (bool) mh_mod('mh_show_cta'));
add_filter('matthummel/footer_text', fn () => mh_mod('mh_footer_text'));

/* Load any non-default chosen fonts (defaults already loaded in setup.php) */
add_action('wp_enqueue_scripts', function () {
    $fonts  = mh_fonts();
    $always = ['Space Grotesk', 'Inter'];
    $picked = array_unique([mh_mod('mh_font_heading'), mh_mod('mh_font_body')]);
    $families = [];
    foreach ($picked as $p) {
        if (in_array($p, $always, true)) {
            continue;
        }
        if (! empty($fonts[$p][0])) {
            $families[] = $fonts[$p][0];
        }
    }
    if ($families) {
        wp_enqueue_style(
            'matthummel-fonts-custom',
            'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap',
            [],
            null
        );
    }
}, 6);

/* Emit CSS-variable overrides AFTER app.css (fires via mh_head_end in the layout) */
add_action('mh_head_end', function () {
    $fonts = mh_fonts();
    $h = $fonts[mh_mod('mh_font_heading')][1] ?? $fonts['Geist'][1];
    $b = $fonts[mh_mod('mh_font_body')][1] ?? $fonts['Inter'][1];

    $css = ':root{'
        . '--color-green:' . mh_mod('mh_color_action') . ';'
        . '--color-khaki:' . mh_mod('mh_color_paper') . ';'
        . '--color-ink:' . mh_mod('mh_color_ink') . ';'
        . '--color-heading:' . mh_mod('mh_color_ink') . ';'
        . '--color-body:' . mh_mod('mh_color_body') . ';'
        . '--font-display:' . $h . ';'
        . '--font-body:' . $b . ';'
        . '}'
        . '.container,.rule,.banner{max-width:' . absint(mh_mod('mh_container')) . 'px}';

    echo "\n<style id=\"mh-customizer\">" . $css . "</style>\n";
});


/** Standard content-width options (px) for select controls. */
function mh_width_options($include_preset = false)
{
    $opts = [];
    if ($include_preset) {
        $opts['0'] = __('Use preset (default)', 'matthummel');
    }
    return $opts + [
        '720'  => '720px (narrow)',
        '960'  => '960px (small)',
        '1080' => '1080px (medium)',
        '1140' => '1140px (standard)',
        '1180' => '1180px (default)',
        '1200' => '1200px',
        '1280' => '1280px (large)',
        '1320' => '1320px',
        '1440' => '1440px (extra wide)',
        '1600' => '1600px (max)',
    ];
}
