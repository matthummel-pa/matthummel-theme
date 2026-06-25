<?php

/**
 * Announcement bar: scheduled (optional start/end date), dismissible, themable.
 * Renders at the very top of <body> via wp_body_open/get_header (guarded once).
 */

namespace App;

function mh_ann_defaults()
{
    return [
        'mh_ann_enable'  => false,
        'mh_ann_text'    => '',
        'mh_ann_lurl'    => '',
        'mh_ann_ltext'   => '',
        'mh_ann_bg'      => '#17191e',
        'mh_ann_color'   => '#ffffff',
        'mh_ann_dismiss' => true,
        'mh_ann_start'   => '',
        'mh_ann_end'     => '',
    ];
}

function mh_ann($k)
{
    $d = mh_ann_defaults();
    return get_theme_mod($k, $d[$k] ?? null);
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $wp->add_section('mh_ann_section', [
        'title' => __('Announcement Bar', 'matthummel'),
        'panel' => 'mh_theme_options',
        'description' => __('A site-wide bar at the very top. Optionally schedule it with a start/end date.', 'matthummel'),
    ]);

    $wp->add_setting('mh_ann_enable', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_ann_enable', ['label' => __('Show announcement bar', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_ann_text', ['default' => '', 'sanitize_callback' => 'wp_kses_post']);
    $wp->add_control('mh_ann_text', ['label' => __('Message', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'text']);

    $wp->add_setting('mh_ann_ltext', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_ann_ltext', ['label' => __('Link text', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'text']);
    $wp->add_setting('mh_ann_lurl', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control('mh_ann_lurl', ['label' => __('Link URL', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'url']);

    foreach ([['mh_ann_bg', __('Background', 'matthummel'), '#17191e'], ['mh_ann_color', __('Text color', 'matthummel'), '#ffffff']] as $col) {
        $wp->add_setting($col[0], ['default' => $col[2], 'sanitize_callback' => 'sanitize_hex_color']);
        $wp->add_control(new \WP_Customize_Color_Control($wp, $col[0], ['label' => $col[1], 'section' => 'mh_ann_section']));
    }

    $wp->add_setting('mh_ann_dismiss', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_ann_dismiss', ['label' => __('Allow visitors to dismiss', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'checkbox']);

    $wp->add_setting('mh_ann_start', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_ann_start', ['label' => __('Start date (optional)', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'date']);
    $wp->add_setting('mh_ann_end', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_ann_end', ['label' => __('End date (optional)', 'matthummel'), 'section' => 'mh_ann_section', 'type' => 'date']);
}, 21);

function mh_ann_render()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (! mh_ann('mh_ann_enable') || ! is_active_sidebar('messagebar')) {
        return;
    }
    $today = current_time('Y-m-d');
    $start = mh_ann('mh_ann_start');
    $end   = mh_ann('mh_ann_end');
    if ($start && $today < $start) {
        return;
    }
    if ($end && $today > $end) {
        return;
    }

    $bg      = sanitize_hex_color(mh_ann('mh_ann_bg')) ?: '#17191e';
    $col     = sanitize_hex_color(mh_ann('mh_ann_color')) ?: '#ffffff';
    $dismiss = (bool) mh_ann('mh_ann_dismiss');
    $sw      = get_option('sidebars_widgets');
    $ver     = substr(md5((string) $start . (string) $end . wp_json_encode($sw['messagebar'] ?? [])), 0, 8);

    echo '<div class="mh-ann" data-ver="' . esc_attr($ver) . '" style="background:' . esc_attr($bg) . ';color:' . esc_attr($col) . '">';
    echo '<div class="mh-ann-inner">';
    dynamic_sidebar('messagebar');
    echo '</div>';
    if ($dismiss) {
        echo '<button class="mh-ann-x" aria-label="' . esc_attr__('Dismiss', 'matthummel') . '" style="color:' . esc_attr($col) . '">&times;</button>';
    }
    echo '</div>';
    echo '<style>.mh-ann{position:relative;font-size:14px;}.mh-ann-inner{max-width:1180px;margin:0 auto;padding:8px 40px;text-align:center;}.mh-ann-inner *{color:inherit;}.mh-ann-x{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:0;font-size:20px;line-height:1;cursor:pointer;opacity:.8;}.mh-ann-x:hover{opacity:1;}.mh-ann.is-hidden{display:none;}</style>';
    if ($dismiss) {
        echo "<script>(function(){var b=document.querySelector('.mh-ann');if(!b)return;var k='mh-ann-'+b.getAttribute('data-ver');try{if(localStorage.getItem(k)==='1'){b.classList.add('is-hidden');}}catch(e){}var x=b.querySelector('.mh-ann-x');if(x)x.addEventListener('click',function(){b.classList.add('is-hidden');try{localStorage.setItem(k,'1');}catch(e){}});})();</script>";
    }
}
add_action('wp_body_open', __NAMESPACE__ . '\\mh_ann_render', 5);
add_action('get_header', __NAMESPACE__ . '\\mh_ann_render', 5);
