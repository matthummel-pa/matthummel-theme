<?php

/**
 * "Social Icons" Gutenberg block (mh/social-links).
 * Dynamic, server-rendered with Blade Icons. Defaults to the site's social
 * links (Customizer) with an optional per-block custom list. Highly themeable:
 * size, gap, shape, brand/mono style, colors, hover colors, alignment, new tab.
 */

namespace App;

/** Shared attribute schema (kept in sync with the editor JS). */
function mh_social_block_attrs()
{
    return [
        'source'      => ['type' => 'string', 'default' => 'site'],     // site | custom
        'customLinks' => ['type' => 'string', 'default' => ''],          // "network|url" per line
        'size'        => ['type' => 'number', 'default' => 22],
        'gap'         => ['type' => 'number', 'default' => 12],
        'shape'       => ['type' => 'string', 'default' => 'circle'],    // none|circle|rounded|square
        'iconStyle'   => ['type' => 'string', 'default' => 'mono'],      // mono|brand
        'color'       => ['type' => 'string', 'default' => ''],
        'bg'          => ['type' => 'string', 'default' => ''],
        'hoverColor'  => ['type' => 'string', 'default' => ''],
        'hoverBg'     => ['type' => 'string', 'default' => ''],
        'align'       => ['type' => 'string', 'default' => 'left'],      // left|center|right
        'newTab'      => ['type' => 'boolean', 'default' => true],
        'label'       => ['type' => 'string', 'default' => ''],
    ];
}

add_action('init', function () {
    // Editor script (plain JS, no build step) — uses global wp.* packages.
    wp_register_script(
        'mh-social-block',
        get_theme_file_uri('resources/js/social-block-editor.js'),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
        filemtime(get_theme_file_path('resources/js/social-block-editor.js')),
        true
    );

    $defaults = [];
    foreach (mh_social_block_attrs() as $k => $v) {
        $defaults[$k] = $v['default'];
    }
    wp_localize_script('mh-social-block', 'mhSocialBlock', [
        'attrs'    => mh_social_block_attrs(),
        'defaults' => $defaults,
        'networks' => array_map(fn ($i) => $i[0], mh_socials_map()),
    ]);

    register_block_type('mh/social-links', [
        'api_version'     => 2,
        'editor_script'   => 'mh-social-block',
        'attributes'      => mh_social_block_attrs(),
        'render_callback' => __NAMESPACE__ . '\\mh_social_block_render',
        'supports'        => [
            'align'   => ['wide', 'full'],
            'spacing' => ['margin' => true, 'padding' => true],
            'html'    => false,
        ],
    ]);
}, 11);

/** Server render. */
function mh_social_block_render($attrs, $content = '')
{
    $d = [];
    foreach (mh_social_block_attrs() as $k => $v) {
        $d[$k] = $v['default'];
    }
    $a = wp_parse_args($attrs, $d);

    // Resolve the link list.
    $items = [];
    if ($a['source'] === 'custom' && trim((string) $a['customLinks']) !== '') {
        $map = mh_socials_map();
        foreach (preg_split('/\r\n|\r|\n/', (string) $a['customLinks']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $key   = sanitize_key($parts[0]);
            $url   = isset($parts[1]) ? $parts[1] : '';
            if ($url === '') {
                continue;
            }
            $items[] = [
                'key'   => $key,
                'label' => $map[$key][0] ?? ucfirst($key),
                'url'   => $url,
            ];
        }
    } else {
        foreach (mh_social_links() as $s) {
            $items[] = ['key' => $s['key'], 'label' => $s['label'], 'url' => $s['url']];
        }
    }

    if (empty($items)) {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return '<p style="opacity:.7;font-style:italic">' . esc_html__('No social links yet — add URLs in Customizer → Theme Options → Menu & Popout, or switch this block to “Custom links”.', 'matthummel') . '</p>';
        }
        return '';
    }

    $size  = max(10, absint($a['size']));
    $gap   = max(0, absint($a['gap']));
    $shape = in_array($a['shape'], ['none', 'circle', 'rounded', 'square'], true) ? $a['shape'] : 'circle';
    $style = $a['iconStyle'] === 'brand' ? 'brand' : 'mono';
    $align = in_array($a['align'], ['left', 'center', 'right'], true) ? $a['align'] : 'left';
    $newTab = ! empty($a['newTab']);

    $color = sanitize_hex_color($a['color']);
    $bg    = sanitize_hex_color($a['bg']);
    $hc    = sanitize_hex_color($a['hoverColor']);
    $hb    = sanitize_hex_color($a['hoverBg']);

    $chip = $shape !== 'none';
    $pad  = $chip ? max(6, (int) round($size * 0.55)) : 0;
    $radius = $shape === 'circle' ? '50%' : ($shape === 'rounded' ? max(4, (int) round($size * 0.35)) . 'px' : '0');
    $justify = $align === 'center' ? 'center' : ($align === 'right' ? 'flex-end' : 'flex-start');

    // Defaults per style.
    $baseColor = $color ?: ($chip ? '#2f6b4e' : 'var(--color-muted, #5c636c)');
    $baseBg    = $chip ? ($bg ?: 'var(--color-green-tint, #eaf1ec)') : 'transparent';
    $hovColor  = $hc ?: ($chip ? '#ffffff' : 'var(--color-green, #2f6b4e)');
    $hovBg     = $chip ? ($hb ?: 'var(--color-green, #2f6b4e)') : 'transparent';

    $uid = 'mh-sl-' . wp_unique_id();

    $css  = "#{$uid}{display:flex;flex-wrap:wrap;align-items:center;gap:{$gap}px;justify-content:{$justify};margin:0;padding:0;list-style:none;}";
    $css .= "#{$uid} .mh-sl-item{display:inline-flex;align-items:center;justify-content:center;line-height:0;text-decoration:none;padding:{$pad}px;border-radius:{$radius};transition:transform .15s ease,background .15s ease,color .15s ease;}";
    $css .= "#{$uid} .mh-sl-item svg{width:{$size}px;height:{$size}px;fill:currentColor;display:block;}";
    if ($style === 'mono') {
        $css .= "#{$uid} .mh-sl-item{color:{$baseColor};background:{$baseBg};}";
        $css .= "#{$uid} .mh-sl-item:hover{color:{$hovColor};background:{$hovBg};transform:translateY(-2px);}";
    } else {
        // brand: per-item color set inline; chip bg/hover shared
        $css .= "#{$uid} .mh-sl-item{background:" . ($chip ? ($bg ?: 'var(--color-cream, #f4f2ec)') : 'transparent') . ";}";
        $css .= "#{$uid} .mh-sl-item:hover{transform:translateY(-2px);" . ($chip ? "filter:brightness(.92);" : "opacity:.8;") . "}";
    }

    $out  = '<style>' . $css . '</style>';
    $out .= '<ul id="' . esc_attr($uid) . '" class="wp-block-mh-social-links mh-sl shape-' . esc_attr($shape) . ' style-' . esc_attr($style) . ' align-' . esc_attr($align) . '" role="list">';
    foreach ($items as $s) {
        $itemStyle = $style === 'brand' ? ' style="color:' . esc_attr(mh_social_color($s['key'])) . '"' : '';
        $rel = $newTab ? 'me noopener noreferrer' : 'me';
        $tgt = $newTab ? ' target="_blank"' : '';
        $out .= '<li class="mh-sl-li"><a class="mh-sl-item" href="' . esc_url($s['url']) . '" aria-label="' . esc_attr($s['label']) . '" rel="' . esc_attr($rel) . '"' . $tgt . $itemStyle . '>';
        $out .= mh_social_icon($s['key']);
        $out .= '</a></li>';
    }
    $out .= '</ul>';

    return $out;
}
