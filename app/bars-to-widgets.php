<?php

/**
 * Bars -> widgets consolidation. The top bar, message bar, and navbar are now
 * built from their widget areas (Appearance -> Widgets) using the Bar blocks.
 * This strips the now-redundant / conflicting Customizer controls so there's a
 * single, clean way to configure each bar's content. The underlying settings
 * remain (harmless) so nothing fatals; the controls are simply removed.
 */

namespace App;

add_action('customize_register', function ($wp) {
    // Top bar -> pure widget area (remove the whole section + its controls).
    foreach ([
        'mh_topbar_enable', 'mh_topbar_contact', 'mh_topbar_show_social',
        'mh_topbar_cta_text', 'mh_topbar_cta_url', 'mh_topbar_bg', 'mh_topbar_bg_custom',
        'mh_topbar_text', 'mh_topbar_text_custom', 'mh_topbar_align', 'mh_topbar_width',
    ] as $c) {
        $wp->remove_control($c);
    }
    $wp->remove_section('mh_topbar_section');

    // Message bar -> content via the "Message bar" widget area.
    // Kept: enable, background, text color, dismiss, schedule (not expressible as blocks).
    foreach (['mh_ann_text', 'mh_ann_ltext', 'mh_ann_lurl', 'mh_msgbar_align', 'mh_msgbar_width'] as $c) {
        $wp->remove_control($c);
    }

    // Navbar social + CTA -> "Navigation bar" widget area (Bar blocks).
    // Removes the placement toggle + legacy/duplicate social & CTA controls.
    foreach (['mh_social_location', 'mh_social_align', 'mh_social_order', 'mh_show_cta', 'mh_cta_text', 'mh_cta_url'] as $c) {
        $wp->remove_control($c);
    }
}, 999);
