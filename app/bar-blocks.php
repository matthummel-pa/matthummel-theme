<?php

/**
 * "Bar item" blocks — drop-in blocks that render the SAME items configured in the
 * Customizer (and honor their show/hide toggles), so a bar's widget area can be
 * composed from them and stays in sync with the Customizer settings:
 *   mh/bar-social · mh/bar-cta · mh/bar-message · mh/bar-logo · mh/bar-nav · mh/bar-contact
 * Each reads theme settings; if an item is hidden/empty, the block renders nothing.
 */

namespace App;

function mh_bar_blocks_defs()
{
    return [
        'bar-social'  => ['title' => __('Bar · Social links', 'matthummel'), 'icon' => 'share', 'cb' => 'mh_block_bar_social'],
        'bar-cta'     => ['title' => __('Bar · Button (CTA)', 'matthummel'), 'icon' => 'button', 'cb' => 'mh_block_bar_cta'],
        'bar-message' => ['title' => __('Bar · Message', 'matthummel'), 'icon' => 'megaphone', 'cb' => 'mh_block_bar_message'],
        'bar-logo'    => ['title' => __('Bar · Site logo', 'matthummel'), 'icon' => 'admin-home', 'cb' => 'mh_block_bar_logo'],
        'bar-nav'     => ['title' => __('Bar · Navigation menu', 'matthummel'), 'icon' => 'menu', 'cb' => 'mh_block_bar_nav'],
        'bar-contact' => ['title' => __('Bar · Contact text', 'matthummel'), 'icon' => 'email', 'cb' => 'mh_block_bar_contact'],
    ];
}

add_action('init', function () {
    $path = 'resources/js/bar-blocks-editor.js';
    wp_register_script(
        'mh-bar-blocks',
        get_theme_file_uri($path),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
        file_exists(get_theme_file_path($path)) ? filemtime(get_theme_file_path($path)) : '1',
        true
    );
    $list = [];
    foreach (mh_bar_blocks_defs() as $slug => $d) {
        $list[$slug] = ['title' => $d['title'], 'icon' => $d['icon']];
        register_block_type('mh/' . $slug, [
            'api_version'     => 2,
            'editor_script'   => 'mh-bar-blocks',
            'render_callback' => __NAMESPACE__ . '\\' . $d['cb'],
            'supports'        => ['html' => false, 'spacing' => ['margin' => true]],
        ]);
    }
    wp_localize_script('mh-bar-blocks', 'mhBarBlocks', $list);
}, 12);

/** Empty-state note (only shown inside the editor preview). */
function mh_bar_rest_note($msg)
{
    return (defined('REST_REQUEST') && REST_REQUEST) ? '<span style="opacity:.6;font-style:italic">' . esc_html($msg) . '</span>' : '';
}

/** Social links — mirrors the header social style settings. */
function mh_block_bar_social()
{
    $links = function_exists('App\\mh_social_links') ? mh_social_links() : [];
    if (empty($links)) {
        return mh_bar_rest_note(__('No social links set (Customizer -> Menu & Popout).', 'matthummel'));
    }
    $style = get_theme_mod('mh_social_style', 'icons');
    $out = '<ul class="social' . ($style === 'icons' ? ' is-icons' : '') . '" aria-label="' . esc_attr__('Social links', 'matthummel') . '">';
    foreach ($links as $s) {
        $inner = $style === 'icons' ? mh_social_icon($s['key']) : esc_html($s['label']);
        $out .= '<li><a href="' . esc_url($s['url']) . '" aria-label="' . esc_attr($s['label']) . '" rel="me noopener">' . $inner . '</a></li>';
    }
    return $out . '</ul>';
}

/** Header CTA button (honors the show-button toggle). */
function mh_block_bar_cta()
{
    if (! get_theme_mod('mh_show_cta', true)) {
        return mh_bar_rest_note(__('Header button is hidden (Customizer).', 'matthummel'));
    }
    $t = get_theme_mod('mh_cta_text', __('Find me on Dev.to', 'matthummel'));
    $u = get_theme_mod('mh_cta_url', 'https://dev.to/mattbuildsapps');
    if (! $t || ! $u) {
        return '';
    }
    return '<a class="btn header-cta" href="' . esc_url($u) . '">' . esc_html($t) . '</a>';
}

/** Announcement message + link. */
function mh_block_bar_message()
{
    $t = (string) get_theme_mod('mh_ann_text', '');
    if (trim($t) === '') {
        return mh_bar_rest_note(__('No message set (Customizer -> Announcement Bar).', 'matthummel'));
    }
    $out = '<span class="mh-ann-msg">' . wp_kses_post($t) . '</span>';
    $lu = get_theme_mod('mh_ann_lurl', '');
    $lt = get_theme_mod('mh_ann_ltext', '');
    if ($lu && $lt) {
        $out .= ' <a class="mh-ann-link" href="' . esc_url($lu) . '">' . esc_html($lt) . ' &rarr;</a>';
    }
    return $out;
}

/** Site logo (custom logo or site name). */
function mh_block_bar_logo()
{
    if (function_exists('has_custom_logo') && has_custom_logo()) {
        return get_custom_logo();
    }
    return '<a class="brand-name" href="' . esc_url(home_url('/')) . '" rel="home">' . esc_html(get_bloginfo('name')) . '</a>';
}

/** Primary navigation menu. */
function mh_block_bar_nav()
{
    if (! has_nav_menu('primary_navigation')) {
        return mh_bar_rest_note(__('No primary menu assigned (Appearance -> Menus).', 'matthummel'));
    }
    return wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav', 'echo' => false, 'container' => false]);
}

/** Top bar contact text. */
function mh_block_bar_contact()
{
    $c = (string) get_theme_mod('mh_topbar_contact', '');
    if (trim($c) === '') {
        return mh_bar_rest_note(__('No contact text set (Customizer -> Top Bar).', 'matthummel'));
    }
    return '<span class="top-bar-contact">' . wp_kses_post($c) . '</span>';
}

/**
 * Editor-only CSS. The block/widgets editor canvas (incl. the Customizer "Widgets"
 * panel) doesn't load the theme's front-end stylesheet, so the SSR previews of the
 * bar blocks fall back to browser defaults — social icons render at intrinsic SVG
 * size and inherit the editor's blue link color, stacked as a bulleted list.
 * This scopes them to a sensible inline row so the preview matches the front end.
 */
add_action('enqueue_block_editor_assets', function () {
    $css = <<<'CSS'
/* mh bar-block editor previews */
[data-type^="mh/bar-"] .social{display:flex;flex-wrap:wrap;align-items:center;gap:14px;list-style:none;margin:0;padding:0;}
[data-type^="mh/bar-"] .social li{margin:0;padding:0;list-style:none;}
[data-type^="mh/bar-"] .social li::marker{content:"";}
[data-type^="mh/bar-"] .social a{display:inline-flex;align-items:center;color:#3b3f46;text-decoration:none;line-height:0;box-shadow:none;}
[data-type^="mh/bar-"] .social a:hover{color:#0f1115;}
[data-type^="mh/bar-"] .social svg{width:20px;height:20px;display:block;fill:currentColor;}
[data-type^="mh/bar-"] .social.is-icons a{padding:0;}
[data-type^="mh/bar-"] .nav{display:flex;flex-wrap:wrap;align-items:center;gap:16px;list-style:none;margin:0;padding:0;font-size:14px;}
[data-type^="mh/bar-"] .nav li{list-style:none;margin:0;}
[data-type^="mh/bar-"] .nav a{text-decoration:none;color:#17191e;}
[data-type^="mh/bar-"] .header-cta,[data-type^="mh/bar-"] .btn{display:inline-block;font-size:13px;padding:8px 16px;border-radius:6px;background:#1f6f43;color:#fff;text-decoration:none;}
[data-type^="mh/bar-"] .brand-name{font-weight:600;text-decoration:none;color:#17191e;}
CSS;
    wp_register_style('mh-bar-editor', false, [], '1');
    wp_enqueue_style('mh-bar-editor');
    wp_add_inline_style('mh-bar-editor', $css);
});
