<?php

/**
 * Integrations & custom code:
 *  - Head / body-open / footer code injection (analytics, pixels, verification).
 *  - Live Custom CSS.
 *  - [mh_newsletter] shortcode (Mailchimp-compatible embedded form).
 *  - Cookie-consent notice (dismissible, stored locally).
 */

namespace App;

/** Admins with unfiltered_html keep raw markup; others get kses. */
function mh_sanitize_code($value)
{
    return current_user_can('unfiltered_html') ? $value : wp_kses_post($value);
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }

    /* ---- Custom Code ---- */
    $wp->add_section('mh_code_section', ['title' => __('Custom Code', 'matthummel'), 'panel' => 'mh_theme_options', 'description' => __('Inject scripts/markup. Use for analytics, pixels, and verification tags.', 'matthummel')]);
    foreach ([
        'mh_code_head'   => __('Head code (before </head>)', 'matthummel'),
        'mh_code_body'   => __('Body code (after <body>)', 'matthummel'),
        'mh_code_footer' => __('Footer code (before </body>)', 'matthummel'),
        'mh_custom_css'  => __('Custom CSS', 'matthummel'),
    ] as $id => $label) {
        $wp->add_setting($id, ['default' => '', 'sanitize_callback' => __NAMESPACE__ . '\\mh_sanitize_code']);
        $wp->add_control($id, ['label' => $label, 'section' => 'mh_code_section', 'type' => 'textarea']);
    }

    /* ---- Newsletter ---- */
    $wp->add_section('mh_news_section', ['title' => __('Newsletter', 'matthummel'), 'panel' => 'mh_theme_options', 'description' => __('Settings for the [mh_newsletter] shortcode. Paste your Mailchimp form action URL.', 'matthummel')]);
    $wp->add_setting('mh_news_action', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control('mh_news_action', ['label' => __('Form action URL (Mailchimp)', 'matthummel'), 'section' => 'mh_news_section', 'type' => 'url']);
    $wp->add_setting('mh_news_heading', ['default' => __('Subscribe', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_news_heading', ['label' => __('Heading', 'matthummel'), 'section' => 'mh_news_section', 'type' => 'text']);
    $wp->add_setting('mh_news_note', ['default' => __('No spam. Unsubscribe anytime.', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_news_note', ['label' => __('Sub-note', 'matthummel'), 'section' => 'mh_news_section', 'type' => 'text']);
    $wp->add_setting('mh_news_button', ['default' => __('Subscribe', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_news_button', ['label' => __('Button text', 'matthummel'), 'section' => 'mh_news_section', 'type' => 'text']);

    /* ---- Cookie notice ---- */
    $wp->add_section('mh_cookie_section', ['title' => __('Cookie Notice', 'matthummel'), 'panel' => 'mh_theme_options']);
    $wp->add_setting('mh_cookie_enable', ['default' => false, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_cookie_enable', ['label' => __('Show cookie notice', 'matthummel'), 'section' => 'mh_cookie_section', 'type' => 'checkbox']);
    $wp->add_setting('mh_cookie_text', ['default' => __('We use cookies to improve your experience.', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_cookie_text', ['label' => __('Message', 'matthummel'), 'section' => 'mh_cookie_section', 'type' => 'text']);
    $wp->add_setting('mh_cookie_btn', ['default' => __('Got it', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_cookie_btn', ['label' => __('Accept button', 'matthummel'), 'section' => 'mh_cookie_section', 'type' => 'text']);
    $wp->add_setting('mh_cookie_lurl', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control('mh_cookie_lurl', ['label' => __('Policy link URL', 'matthummel'), 'section' => 'mh_cookie_section', 'type' => 'url']);
    $wp->add_setting('mh_cookie_ltext', ['default' => __('Learn more', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_cookie_ltext', ['label' => __('Policy link text', 'matthummel'), 'section' => 'mh_cookie_section', 'type' => 'text']);
}, 27);

/* ---- Code injection ---- */
add_action('wp_head', function () {
    $c = get_theme_mod('mh_code_head', '');
    if ($c) {
        echo "\n" . $c . "\n"; // phpcs:ignore -- intentional raw injection by admin
    }
}, 99);

function mh_inject_body()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $c = get_theme_mod('mh_code_body', '');
    if ($c) {
        echo "\n" . $c . "\n"; // phpcs:ignore
    }
}
add_action('wp_body_open', __NAMESPACE__ . '\\mh_inject_body', 20);
add_action('get_header', __NAMESPACE__ . '\\mh_inject_body', 20);

add_action('wp_footer', function () {
    $c = get_theme_mod('mh_code_footer', '');
    if ($c) {
        echo "\n" . $c . "\n"; // phpcs:ignore
    }
}, 99);

/* ---- Custom CSS (last, so it wins) ---- */
add_action('mh_head_end', function () {
    $css = trim((string) get_theme_mod('mh_custom_css', ''));
    if ($css !== '') {
        echo "\n<style id=\"mh-custom-css\">" . wp_strip_all_tags($css) . "</style>\n";
    }
}, 99);

/* ---- Newsletter shortcode ---- */
add_shortcode('mh_newsletter', function ($atts) {
    $a = shortcode_atts(['heading' => '', 'button' => '', 'note' => ''], $atts);
    $action  = get_theme_mod('mh_news_action', '');
    $heading = $a['heading'] ?: get_theme_mod('mh_news_heading', __('Subscribe', 'matthummel'));
    $button  = $a['button'] ?: get_theme_mod('mh_news_button', __('Subscribe', 'matthummel'));
    $note    = $a['note'] ?: get_theme_mod('mh_news_note', '');

    $out  = '<div class="mh-news">';
    if ($heading) {
        $out .= '<h3 class="mh-news-h">' . esc_html($heading) . '</h3>';
    }
    $out .= '<form class="mh-news-form" action="' . esc_url($action) . '" method="post" target="_blank" novalidate>';
    $out .= '<input type="email" name="EMAIL" required placeholder="' . esc_attr__('you@example.com', 'matthummel') . '" aria-label="' . esc_attr__('Email address', 'matthummel') . '">';
    // honeypot (Mailchimp anti-bot)
    $out .= '<div style="position:absolute;left:-5000px" aria-hidden="true"><input type="text" name="b_honeypot" tabindex="-1" value=""></div>';
    $out .= '<button type="submit" class="btn">' . esc_html($button) . '</button>';
    $out .= '</form>';
    if ($note) {
        $out .= '<p class="mh-news-note">' . esc_html($note) . '</p>';
    }
    $out .= '</div>';
    return $out;
});

/* ---- Cookie notice ---- */
add_action('wp_footer', function () {
    if (! get_theme_mod('mh_cookie_enable', false)) {
        return;
    }
    $text = esc_html(get_theme_mod('mh_cookie_text', ''));
    $btn  = esc_html(get_theme_mod('mh_cookie_btn', __('Got it', 'matthummel')));
    $lurl = esc_url(get_theme_mod('mh_cookie_lurl', ''));
    $ltxt = esc_html(get_theme_mod('mh_cookie_ltext', ''));
    $link = ($lurl && $ltxt) ? ' <a href="' . $lurl . '">' . $ltxt . '</a>' : '';
    echo '<div class="mh-cookie" role="dialog" aria-label="' . esc_attr__('Cookie notice', 'matthummel') . '"><p>' . $text . $link . '</p><button class="btn mh-cookie-ok">' . $btn . '</button></div>';
    echo '<style>.mh-cookie{position:fixed;left:16px;right:16px;bottom:16px;max-width:560px;margin:0 auto;background:var(--color-ink,#17191e);color:#fff;padding:14px 16px;border-radius:12px;display:flex;gap:14px;align-items:center;justify-content:space-between;z-index:95;box-shadow:0 10px 30px rgba(0,0,0,.25);font-size:14px;}.mh-cookie p{margin:0;}.mh-cookie a{color:#fff;text-decoration:underline;}.mh-cookie.is-hidden{display:none;}</style>';
    echo "<script>(function(){var b=document.querySelector('.mh-cookie');if(!b)return;var k='mh-cookie-ok';try{if(localStorage.getItem(k)==='1'){b.classList.add('is-hidden');return;}}catch(e){}var ok=b.querySelector('.mh-cookie-ok');if(ok)ok.addEventListener('click',function(){b.classList.add('is-hidden');try{localStorage.setItem(k,'1');}catch(e){}});})();</script>";
}, 60);

add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-news-css\">.mh-news{max-width:460px;}.mh-news-h{margin:0 0 10px;}.mh-news-form{display:flex;gap:8px;flex-wrap:wrap;}.mh-news-form input[type=email]{flex:1 1 200px;padding:11px 14px;border:1px solid var(--color-line,#e6e2d9);border-radius:8px;font:inherit;}.mh-news-note{font-size:12px;color:var(--color-muted,#5c636c);margin:8px 0 0;}</style>\n";
}, 19);
