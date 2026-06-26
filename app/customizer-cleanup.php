<?php

/**
 * Customizer housekeeping (runs last, priority 999).
 * Reorganises the Theme Options panel for a cleaner, more discoverable UI without
 * touching the source modules that register each control:
 *   - splits the overloaded "Navigation" section into focused sections
 *     (Navigation · Social Icons · Responsive), and re-parents stray controls,
 *   - orders every section logically,
 *   - adds a short description to each section.
 */

namespace App;

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        return;
    }

    // --- New grouping sections ---
    if (! $wp->get_section('mh_social_section')) {
        $wp->add_section('mh_social_section', [
            'title'       => __('Social Icons', 'matthummel'),
            'panel'       => 'mh_theme_options',
            'description' => __('Where social icons appear, their style, and your account URLs.', 'matthummel'),
        ]);
    }
    if (! $wp->get_section('mh_responsive_section')) {
        $wp->add_section('mh_responsive_section', [
            'title'       => __('Responsive (mobile & tablet)', 'matthummel'),
            'panel'       => 'mh_theme_options',
            'description' => __('Per-device visibility and widths. Mobile ≤640px · tablet 641–1024px.', 'matthummel'),
        ]);
    }

    // --- Re-parent controls into better homes (runtime only) ---
    $move = [
        'mh_social_section' => [
            'mh_nav_social', 'mh_nav_social_align', 'mh_social_style', 'mh_social_size', 'mh_social_shape',
            'mh_social_color', 'mh_social_bg', 'mh_social_hover',
            'mh_social_linkedin', 'mh_social_github', 'mh_social_devto', 'mh_social_x', 'mh_social_bluesky',
            'mh_social_youtube', 'mh_social_instagram', 'mh_social_facebook', 'mh_social_mastodon', 'mh_social_email', 'mh_social_rss',
        ],
        'mh_responsive_section' => [
            'mh_social_nav_hide_mobile', 'mh_social_nav_hide_desktop', 'mh_social_top_hide_mobile',
            'mh_topcta_hide_mobile', 'mh_navcta_hide_mobile', 'mh_navcta_hide_tablet',
            'mh_topbar_oneline_tablet', 'mh_logo_shrink_mobile', 'mh_menu_label_hide_mobile',
            'mh_topbar_width_tablet', 'mh_topbar_width_mobile', 'mh_nav_width_tablet', 'mh_nav_width_mobile',
            'mh_msgbar_width_tablet', 'mh_msgbar_width_mobile',
        ],
        'mh_headerlayout_section' => [
            'mh_logo_align', 'mh_darkicon_align', 'mh_popbtn_align', 'mh_cta_align',
            'mh_bar_ann', 'mh_bar_top', 'mh_bar_nav',
        ],
        'mh_popout_section' => [
            'mh_popout_width', 'mh_popout_cols', 'mh_popout_block_cols',
            'mh_pop_align', 'mh_pop_pad_y', 'mh_pop_font', 'mh_pop_weight', 'mh_pop_transform', 'mh_pop_gap',
        ],
    ];
    foreach ($move as $section => $ids) {
        $base = 30; // place moved controls after a section's native controls
        foreach ($ids as $i => $id) {
            $c = $wp->get_control($id);
            if ($c) {
                $c->section  = $section;
                $c->priority = $base + $i;
            }
        }
    }

    // --- Logical section order within Theme Options ---
    $order = [
        'mh_colors' => 20, 'mh_type' => 30, 'mh_type_adv' => 35,
        'mh_headerlayout_section' => 40, 'mh_nav_section' => 45, 'mh_popout_section' => 50,
        'mh_social_section' => 55, 'mh_topbar_section' => 60, 'mh_ann_section' => 65,
        'mh_hero_section' => 70, 'mh_anim_section' => 75, 'mh_footer_section' => 80,
        'mh_content_section' => 85, 'mh_layout_section' => 90, 'mh_responsive_section' => 95,
        'mh_dark_section' => 100, 'mh_seo_section' => 110, 'mh_perf_section' => 120,
        'mh_code_section' => 130, 'mh_news_section' => 140, 'mh_cookie_section' => 150,
        'mh_extras_section' => 160, 'mh_wl_section' => 170,
    ];
    foreach ($order as $sid => $prio) {
        $s = $wp->get_section($sid);
        if ($s) {
            $s->priority = $prio;
        }
    }

    // --- Short, dev-friendly section descriptions (only if one isn't already set) ---
    $desc = [
        'mh_nav_section'         => __('Primary menu layout (flexbox) and link styling.', 'matthummel'),
        'mh_popout_section'      => __('Off-canvas menu: breakpoints, panel style, columns and item styling.', 'matthummel'),
        'mh_topbar_section'      => __('Slim utility bar above the main navigation.', 'matthummel'),
        'mh_ann_section'         => __('Site-wide announcement bar — schedulable and dismissible.', 'matthummel'),
        'mh_headerlayout_section' => __('Header sizing, element placement, sticky/transparent behaviour and bar order.', 'matthummel'),
        'mh_layout_section'      => __('Content width and sidebar per content type.', 'matthummel'),
        'mh_content_section'     => __('Editable copy for the CTA band and page intros.', 'matthummel'),
        'mh_anim_section'        => __('On-scroll reveal animations applied site-wide.', 'matthummel'),
    ];
    foreach ($desc as $sid => $d) {
        $s = $wp->get_section($sid);
        if ($s && empty($s->description)) {
            $s->description = $d;
        }
    }
}, 999);
