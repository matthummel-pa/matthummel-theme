<?php

/**
 * Dark mode: a header toggle that flips the CSS-variable palette,
 * persists the choice (localStorage), and supports system-auto.
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_dark_section', ['title' => __('Dark Mode', 'matthummel'), 'panel' => 'mh_theme_options']);
    $wp->add_setting('mh_dark_enable', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_dark_enable', ['label' => __('Show dark mode toggle', 'matthummel'), 'section' => 'mh_dark_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_dark_default', ['default' => 'light', 'sanitize_callback' => 'sanitize_key']);
    $wp->add_control('mh_dark_default', ['label' => __('Default mode', 'matthummel'), 'section' => 'mh_dark_section', 'type' => 'select', 'choices' => ['light' => __('Light', 'matthummel'), 'dark' => __('Dark', 'matthummel'), 'auto' => __('Auto (system)', 'matthummel')]]);
}, 25);

/** No-flash: set the dark class as early as possible. */
add_action('wp_head', function () {
    if (! get_theme_mod('mh_dark_enable', true)) {
        return;
    }
    $def = esc_js(get_theme_mod('mh_dark_default', 'light'));
    echo "<script>(function(){try{var d='{$def}';var m=localStorage.getItem('mh-theme');if(!m){m=(d==='auto')?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):d;}if(m==='dark'){document.documentElement.classList.add('mh-dark');}}catch(e){}})();</script>\n";
}, 2);

/** Toggle behavior. */
add_action('wp_footer', function () {
    if (! get_theme_mod('mh_dark_enable', true)) {
        return;
    }
    echo "<script>(function(){var b=document.querySelector('.mh-theme-toggle');if(!b)return;function set(d){document.documentElement.classList.toggle('mh-dark',d);try{localStorage.setItem('mh-theme',d?'dark':'light');}catch(e){}b.setAttribute('aria-pressed',d?'true':'false');}b.setAttribute('aria-pressed',document.documentElement.classList.contains('mh-dark')?'true':'false');b.addEventListener('click',function(){set(!document.documentElement.classList.contains('mh-dark'));});})();</script>\n";
}, 60);
