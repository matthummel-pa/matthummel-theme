<?php

/**
 * Dynamic Blade-powered blocks:
 *  - mh/icon       : insert any Blade icon by name (Simple Icons, Heroicons, Lucide, local mh-).
 *  - mh/post-grid  : query posts or projects into a responsive card grid.
 * Server-rendered (render_callback) with plain-JS editors (ServerSideRender previews).
 */

namespace App;

function mh_icon_block_attrs()
{
    return [
        'name'  => ['type' => 'string', 'default' => 'heroicon-o-sparkles'],
        'size'  => ['type' => 'number', 'default' => 32],
        'color' => ['type' => 'string', 'default' => ''],
        'align' => ['type' => 'string', 'default' => 'left'],
        'label' => ['type' => 'string', 'default' => ''],
    ];
}

function mh_postgrid_attrs()
{
    return [
        'postType'    => ['type' => 'string', 'default' => 'post'],
        'count'       => ['type' => 'number', 'default' => 6],
        'columns'     => ['type' => 'number', 'default' => 3],
        'orderby'     => ['type' => 'string', 'default' => 'date'],
        'order'       => ['type' => 'string', 'default' => 'DESC'],
        'showImage'   => ['type' => 'boolean', 'default' => true],
        'showExcerpt' => ['type' => 'boolean', 'default' => true],
        'showDate'    => ['type' => 'boolean', 'default' => true],
        'showCategory'=> ['type' => 'boolean', 'default' => false],
    ];
}

add_action('init', function () {
    foreach (['mh-icon-block' => 'icon', 'mh-postgrid-block' => 'postgrid'] as $handle => $slug) {
        $path = "resources/js/{$handle}-editor.js";
        wp_register_script(
            $handle,
            get_theme_file_uri($path),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
            file_exists(get_theme_file_path($path)) ? filemtime(get_theme_file_path($path)) : '1',
            true
        );
    }

    register_block_type('mh/icon', [
        'api_version'     => 2,
        'editor_script'   => 'mh-icon-block',
        'attributes'      => mh_icon_block_attrs(),
        'render_callback' => __NAMESPACE__ . '\\mh_icon_block_render',
        'supports'        => ['align' => true, 'spacing' => ['margin' => true]],
    ]);

    register_block_type('mh/post-grid', [
        'api_version'     => 2,
        'editor_script'   => 'mh-postgrid-block',
        'attributes'      => mh_postgrid_attrs(),
        'render_callback' => __NAMESPACE__ . '\\mh_postgrid_render',
        'supports'        => ['align' => ['wide', 'full'], 'spacing' => ['margin' => true, 'padding' => true]],
    ]);
}, 11);

/** Render: single icon. */
function mh_icon_block_render($attrs)
{
    $d = [];
    foreach (mh_icon_block_attrs() as $k => $v) {
        $d[$k] = $v['default'];
    }
    $a = wp_parse_args($attrs, $d);

    $name  = preg_replace('/[^a-z0-9\-]/i', '', (string) $a['name']);
    $size  = max(12, absint($a['size']));
    $color = sanitize_hex_color($a['color']);
    $align = in_array($a['align'], ['left', 'center', 'right'], true) ? $a['align'] : 'left';
    $just  = $align === 'center' ? 'center' : ($align === 'right' ? 'flex-end' : 'flex-start');

    $style = 'display:flex;justify-content:' . $just . ';line-height:0;';
    $istyle = 'width:' . $size . 'px;height:' . $size . 'px;' . ($color ? 'color:' . $color . ';' : '');
    $svg = mh_icon($name, 'mh-icon-svg', ['style' => 'width:' . $size . 'px;height:' . $size . 'px;']);

    $aria = $a['label'] ? ' role="img" aria-label="' . esc_attr($a['label']) . '"' : '';
    return '<div class="wp-block-mh-icon" style="' . esc_attr($style) . '"><span class="mh-icon-wrap" style="' . esc_attr($istyle) . '"' . $aria . '>' . $svg . '</span></div>';
}

/** Render: post/project grid. */
function mh_postgrid_render($attrs)
{
    $d = [];
    foreach (mh_postgrid_attrs() as $k => $v) {
        $d[$k] = $v['default'];
    }
    $a = wp_parse_args($attrs, $d);

    $pt      = in_array($a['postType'], ['post', 'projects', 'page'], true) ? $a['postType'] : 'post';
    $count   = max(1, min(24, absint($a['count'])));
    $cols    = max(1, min(4, absint($a['columns'])));
    $orderby = in_array($a['orderby'], ['date', 'title', 'rand', 'menu_order'], true) ? $a['orderby'] : 'date';
    $order   = strtoupper($a['order']) === 'ASC' ? 'ASC' : 'DESC';

    $q = new \WP_Query([
        'post_type'           => $pt,
        'posts_per_page'      => $count,
        'orderby'             => $orderby,
        'order'               => $order,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);

    if (! $q->have_posts()) {
        wp_reset_postdata();
        return (defined('REST_REQUEST') && REST_REQUEST)
            ? '<p style="opacity:.7;font-style:italic">' . esc_html__('No items found for this post type.', 'matthummel') . '</p>'
            : '';
    }

    $uid = 'mh-pg-' . wp_unique_id();
    $css = "#{$uid}{display:grid;grid-template-columns:repeat({$cols},1fr);gap:24px;}"
        . "@media(max-width:780px){#{$uid}{grid-template-columns:repeat(2,1fr);}}"
        . "@media(max-width:520px){#{$uid}{grid-template-columns:1fr;}}"
        . "#{$uid} .mh-pg-card{display:flex;flex-direction:column;border:1px solid var(--color-line,#e6e2d9);border-radius:14px;overflow:hidden;background:var(--color-surface,#fff);}"
        . "#{$uid} .mh-pg-thumb{aspect-ratio:16/10;object-fit:cover;width:100%;display:block;}"
        . "#{$uid} .mh-pg-body{padding:16px 18px 20px;}"
        . "#{$uid} .mh-pg-meta{font-size:12px;color:var(--color-muted,#5c636c);margin:0 0 6px;}"
        . "#{$uid} .mh-pg-title{font-size:18px;margin:0 0 8px;line-height:1.25;}"
        . "#{$uid} .mh-pg-title a{text-decoration:none;color:var(--color-ink,#17191e);}"
        . "#{$uid} .mh-pg-title a:hover{color:var(--color-green,#2f6b4e);}"
        . "#{$uid} .mh-pg-ex{font-size:14px;color:var(--color-body,#2b2f36);margin:0;}";

    $out = '<style>' . $css . '</style><div id="' . esc_attr($uid) . '" class="wp-block-mh-post-grid mh-pg">';
    while ($q->have_posts()) {
        $q->the_post();
        $out .= '<article class="mh-pg-card">';
        if (! empty($a['showImage']) && has_post_thumbnail()) {
            $out .= '<a href="' . esc_url(get_permalink()) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail(get_the_ID(), 'medium_large', ['class' => 'mh-pg-thumb', 'loading' => 'lazy']) . '</a>';
        }
        $out .= '<div class="mh-pg-body">';
        $meta = [];
        if (! empty($a['showDate'])) {
            $meta[] = esc_html(get_the_date());
        }
        if (! empty($a['showCategory']) && $pt === 'post') {
            $cats = get_the_category();
            if ($cats) {
                $meta[] = esc_html($cats[0]->name);
            }
        }
        if ($meta) {
            $out .= '<p class="mh-pg-meta">' . implode(' &middot; ', $meta) . '</p>';
        }
        $out .= '<h3 class="mh-pg-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
        if (! empty($a['showExcerpt'])) {
            $out .= '<p class="mh-pg-ex">' . esc_html(wp_trim_words(get_the_excerpt(), 22)) . '</p>';
        }
        $out .= '</div></article>';
    }
    $out .= '</div>';
    wp_reset_postdata();
    return $out;
}
