<?php

/**
 * Header behaviors: sticky, shrink-on-scroll, and transparent (overlay) header.
 * Controls live in the consolidated "Header Layout" section (mh_headerlayout_section).
 * Adds body classes + scoped CSS/JS. No template edits required.
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }

    $wp->add_setting('mh_header_sticky', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_header_sticky', ['label' => __('Sticky header', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_header_shrink', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_header_shrink', ['label' => __('Shrink on scroll (needs sticky)', 'matthummel'), 'section' => 'mh_headerlayout_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_header_transparent', ['default' => 'none', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_header_transparent', [
        'label'   => __('Transparent overlay header', 'matthummel'),
        'section' => 'mh_headerlayout_section',
        'type'    => 'select',
        'choices' => ['none' => __('Off', 'matthummel'), 'front' => __('Front page only', 'matthummel'), 'all' => __('All pages', 'matthummel')],
    ]);
}, 23);

add_filter('body_class', function ($c) {
    if (get_theme_mod('mh_header_sticky', false)) {
        $c[] = 'mh-sticky';
    }
    if (get_theme_mod('mh_header_sticky', false) && get_theme_mod('mh_header_shrink', false)) {
        $c[] = 'mh-shrink';
    }
    $tr = get_theme_mod('mh_header_transparent', 'none');
    if ($tr === 'all' || ($tr === 'front' && is_front_page())) {
        $c[] = 'mh-transparent';
    }
    return $c;
});

add_action('mh_head_end', function () {
    $sticky = get_theme_mod('mh_header_sticky', false);
    $tr     = get_theme_mod('mh_header_transparent', 'none');
    if (! $sticky && $tr === 'none') {
        return;
    }
    $css = '';
    if ($sticky) {
        $css .= 'body.mh-sticky .banner{position:sticky;top:0;z-index:50;background:var(--color-paper,#fbfaf7);transition:padding .2s ease,box-shadow .2s ease;}';
        $css .= 'body.mh-sticky.mh-scrolled .banner{box-shadow:0 4px 20px rgba(23,25,30,.08);}';
        $css .= 'body.mh-shrink.mh-scrolled .banner{padding-top:8px;padding-bottom:8px;}';
    }
    if ($tr !== 'none') {
        $css .= 'body.mh-transparent .banner{background:transparent;}';
        $css .= 'body.mh-transparent.mh-scrolled .banner{background:var(--color-paper,#fbfaf7);}';
    }
    echo "\n<style id=\"mh-headerbe\">" . $css . "</style>\n";
    echo "<script>(function(){function s(){document.body.classList.toggle('mh-scrolled',window.scrollY>10);}window.addEventListener('scroll',s,{passive:true});s();})();</script>";
}, 15);
