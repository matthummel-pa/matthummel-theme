<?php

/**
 * Asset optimization:
 *  - Per-block CSS splitting: core block stylesheets load only when the block is
 *    on the page (should_load_separate_core_block_assets). The theme's own blocks
 *    already emit their CSS inline per render, so they're conditional too.
 *  - Critical CSS: inline above-the-fold CSS in <head> and (optionally) defer the
 *    main built stylesheet via a preload swap, so it isn't render-blocking.
 */

namespace App;

/** Load core block CSS only when the block is used. */
add_filter('should_load_separate_core_block_assets', function ($v) {
    return (bool) get_theme_mod('mh_split_block_css', true);
});

/** Keep CSS to a single render of each block style (dedupe inline emits). */
add_filter('styles_inline_size_limit', function ($n) {
    return max($n, 80000);
});

/** Controls in the Performance section. */
add_action('customize_register', function ($wp) {
    if (! $wp->get_section('mh_perf_section')) {
        return;
    }
    $wp->add_setting('mh_split_block_css', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_split_block_css', ['label' => __('Load block CSS only when used (split assets)', 'matthummel'), 'section' => 'mh_perf_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_critical_css', ['default' => '', 'sanitize_callback' => __NAMESPACE__ . '\\mh_sanitize_css']);
    $wp->add_control('mh_critical_css', [
        'label'       => __('Critical CSS', 'matthummel'),
        'description' => __('Above-the-fold CSS, inlined in <head>. Required before deferring the main stylesheet.', 'matthummel'),
        'section'     => 'mh_perf_section',
        'type'        => 'textarea',
    ]);

    $wp->add_setting('mh_defer_main_css', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_defer_main_css', ['label' => __('Defer main stylesheet (needs Critical CSS)', 'matthummel'), 'section' => 'mh_perf_section', 'type' => 'checkbox']);
}, 29);

/** Prevent breaking out of the inline <style>. */
function mh_sanitize_css($v)
{
    return str_ireplace(['</style', '<script'], '', (string) $v);
}

/** Inline critical CSS early in <head>. */
add_action('wp_head', function () {
    $c = trim((string) get_theme_mod('mh_critical_css', ''));
    if ($c !== '') {
        echo "\n<style id=\"mh-critical\">" . mh_sanitize_css($c) . "</style>\n";
    }
}, 1);

/** Defer the theme's built stylesheet via a preload swap (only with critical CSS present). */
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    if (! get_theme_mod('mh_defer_main_css', false)) {
        return $tag;
    }
    if (trim((string) get_theme_mod('mh_critical_css', '')) === '') {
        return $tag; // need an above-the-fold fallback first
    }
    if (strpos((string) $href, '/public/build/') === false) {
        return $tag; // only the theme's built bundle
    }

    $onload = "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"";
    if (strpos($tag, "rel='stylesheet'") !== false) {
        $swapped = str_replace("rel='stylesheet'", $onload, $tag);
    } elseif (strpos($tag, 'rel="stylesheet"') !== false) {
        $swapped = str_replace('rel="stylesheet"', $onload, $tag);
    } else {
        return $tag;
    }
    return $swapped . '<noscript>' . $tag . '</noscript>';
}, 10, 3);
