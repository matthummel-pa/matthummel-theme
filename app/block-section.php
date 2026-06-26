<?php

/**
 * mh/section — page-builder-style section wrapper.
 *
 * Wraps InnerBlocks with background colour/image, padding, and container
 * width controls. Server-side rendered so CSS variables always apply.
 *
 * Also registers the 'matthummel' block category so all mh/* blocks
 * are grouped together in the inserter.
 */

namespace App;

/* ── Block category ─────────────────────────────────────────────────── */

add_filter('block_categories_all', function (array $cats, $context): array {
    array_unshift($cats, [
        'slug'  => 'matthummel',
        'title' => __('Matthummel', 'matthummel'),
        'icon'  => null,
    ]);
    return $cats;
}, 10, 2);

/* ── Section block attributes ───────────────────────────────────────── */

function mh_section_attrs(): array
{
    return [
        'bgColor'        => ['type' => 'string',  'default' => ''],
        'bgImageUrl'     => ['type' => 'string',  'default' => ''],
        'bgOverlay'      => ['type' => 'number',  'default' => 40],
        'paddingTop'     => ['type' => 'string',  'default' => 'md'],
        'paddingBottom'  => ['type' => 'string',  'default' => 'md'],
        'containerWidth' => ['type' => 'string',  'default' => 'contained'],
        'textColor'      => ['type' => 'string',  'default' => ''],
        'hasRule'        => ['type' => 'boolean', 'default' => false],
        'anchor'         => ['type' => 'string',  'default' => ''],
    ];
}

/* ── Registration ───────────────────────────────────────────────────── */

add_action('init', function () {
    $path = 'resources/js/mh-section-editor.js';
    if (file_exists(get_theme_file_path($path))) {
        wp_register_script(
            'mh-section',
            get_theme_file_uri($path),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            filemtime(get_theme_file_path($path)),
            true
        );
    }

    register_block_type('mh/section', [
        'api_version'     => 2,
        'editor_script'   => 'mh-section',
        'attributes'      => mh_section_attrs(),
        'render_callback' => __NAMESPACE__ . '\\mh_section_render',
        'supports'        => [
            'align'   => ['full'],
            'anchor'  => true,
            'html'    => false,
            'spacing' => false,
            'color'   => false,
        ],
    ]);
}, 10);

/* ── Render callback ────────────────────────────────────────────────── */

function mh_section_render(array $attrs, string $content): string
{
    $a = wp_parse_args($attrs, array_map(fn($v) => $v['default'], mh_section_attrs()));

    // Build class list
    $classes = ['mh-section'];
    if ($a['bgColor'])        $classes[] = 'mh-section--bg-' . sanitize_html_class($a['bgColor']);
    if ($a['paddingTop'])     $classes[] = 'mh-section--pt-' . sanitize_html_class($a['paddingTop']);
    if ($a['paddingBottom'])  $classes[] = 'mh-section--pb-' . sanitize_html_class($a['paddingBottom']);
    if ($a['containerWidth']) $classes[] = 'mh-section--width-' . sanitize_html_class($a['containerWidth']);
    if ($a['textColor'])      $classes[] = 'mh-section--text-' . sanitize_html_class($a['textColor']);

    // Inline style (only for bg images — colours use CSS vars via class)
    $style = '';
    if (!empty($a['bgImageUrl'])) {
        $overlay = max(0, min(80, absint($a['bgOverlay'])));
        $alpha   = round($overlay / 100, 2);
        $style   = sprintf(
            'background-image:linear-gradient(rgba(0,0,0,%s),rgba(0,0,0,%s)),url(%s);background-size:cover;background-position:center;',
            $alpha,
            $alpha,
            esc_url($a['bgImageUrl'])
        );
    }

    // Optional anchor
    $id = !empty($a['anchor']) ? ' id="' . esc_attr($a['anchor']) . '"' : '';

    // Optional rule above section
    $rule = !empty($a['hasRule']) ? '<hr class="rule" aria-hidden="true">' . "\n" : '';

    return $rule
        . '<section class="' . esc_attr(implode(' ', $classes)) . '"'
        . $id
        . ($style ? ' style="' . esc_attr($style) . '"' : '')
        . '>'
        . '<div class="mh-section-inner">'
        . $content
        . '</div>'
        . '</section>';
}
