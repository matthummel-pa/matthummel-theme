<?php

/**
 * Code highlighting for core/code blocks: Prism syntax highlighting, line
 * numbers, and an optional filename label. Prism (cdnjs) loads only on pages
 * that actually contain a code block. Editor controls add language/filename/
 * line-numbers to the standard Code block (see resources/js/code-block-editor.js).
 */

namespace App;

const MH_PRISM = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0';

function mh_code_hl_on()
{
    return (bool) get_theme_mod('mh_code_highlight', true);
}

/** Toggle in the Performance section. */
add_action('customize_register', function ($wp) {
    if (! $wp->get_section('mh_perf_section')) {
        return;
    }
    $wp->add_setting('mh_code_highlight', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_code_highlight', ['label' => __('Syntax-highlight code blocks (Prism)', 'matthummel'), 'section' => 'mh_perf_section', 'type' => 'checkbox']);
}, 28);

/** Editor controls on core/code. */
add_action('enqueue_block_editor_assets', function () {
    if (! mh_code_hl_on()) {
        return;
    }
    $path = 'resources/js/code-block-editor.js';
    wp_enqueue_script(
        'mh-code-block',
        get_theme_file_uri($path),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-hooks', 'wp-i18n'],
        file_exists(get_theme_file_path($path)) ? filemtime(get_theme_file_path($path)) : '1',
        true
    );
});

/** Register Prism assets (enqueued on demand). */
add_action('wp_enqueue_scripts', function () {
    if (! mh_code_hl_on()) {
        return;
    }
    wp_register_style('mh-prism-theme', MH_PRISM . '/themes/prism-tomorrow.min.css', [], '1.29.0');
    wp_register_style('mh-prism-ln', MH_PRISM . '/plugins/line-numbers/prism-line-numbers.min.css', [], '1.29.0');
    wp_register_script('mh-prism', MH_PRISM . '/prism.min.js', [], '1.29.0', true);
    wp_register_script('mh-prism-auto', MH_PRISM . '/plugins/autoloader/prism-autoloader.min.js', ['mh-prism'], '1.29.0', true);
    wp_register_script('mh-prism-ln', MH_PRISM . '/plugins/line-numbers/prism-line-numbers.min.js', ['mh-prism'], '1.29.0', true);
    wp_add_inline_script('mh-prism-auto', "if(window.Prism&&Prism.plugins&&Prism.plugins.autoloader){Prism.plugins.autoloader.languages_path='" . MH_PRISM . "/components/';}");
});

/** Transform core/code on render: filename label + enqueue Prism when present. */
add_filter('render_block', function ($content, $block) {
    if (! mh_code_hl_on() || ($block['blockName'] ?? '') !== 'core/code') {
        return $content;
    }
    // Only enhance blocks the editor tagged with a language.
    if (strpos($content, 'language-') === false && strpos($content, 'data-filename') === false) {
        return $content;
    }

    foreach (['mh-prism-theme', 'mh-prism-ln'] as $s) {
        wp_enqueue_style($s);
    }
    foreach (['mh-prism', 'mh-prism-auto', 'mh-prism-ln'] as $j) {
        wp_enqueue_script($j);
    }

    // Pull an optional filename and render it as a caption above the <pre>.
    if (preg_match('/data-filename="([^"]+)"/', $content, $m) && $m[1] !== '') {
        $file = esc_html($m[1]);
        $content = preg_replace('/\sdata-filename="[^"]*"/', '', $content, 1);
        $content = '<figure class="mh-code"><figcaption class="mh-code-file">' . $file . '</figcaption>' . $content . '</figure>';
    }
    return $content;
}, 10, 2);

add_action('mh_head_end', function () {
    if (! mh_code_hl_on()) {
        return;
    }
    echo "\n<style id=\"mh-code-hl\">.mh-code{margin:1.5em 0;}.mh-code-file{font:600 12px/1 var(--font-mono,monospace);background:#0b0c0e;color:#9aa4b2;padding:9px 14px;border-radius:10px 10px 0 0;border:1px solid #1f242c;border-bottom:0;}.mh-code .wp-block-code,.mh-code pre{margin-top:0;border-top-left-radius:0;border-top-right-radius:0;}pre[class*=language-]{border-radius:10px;}</style>\n";
}, 14);
