<?php

/**
 * Hero layout + site-wide scroll animations.
 *  - Hero: columns (1–3), content flex position (H/V), side image + 2nd image,
 *    background cover (with overlay + min-height), and an entrance animation.
 *  - Animations: on-scroll reveal for sections site-wide (IntersectionObserver),
 *    with a choice of effect + speed. Respects prefers-reduced-motion and degrades
 *    gracefully without JS (content only hides once <html> is marked anim-ready).
 */

namespace App;

/** Shared list of animation effects. */
function mh_anim_effects()
{
    return [
        'none'        => __('None', 'matthummel'),
        'fade-up'     => __('Fade up', 'matthummel'),
        'fade-in'     => __('Fade in', 'matthummel'),
        'zoom-in'     => __('Zoom in', 'matthummel'),
        'pop'         => __('Pop', 'matthummel'),
        'blur-in'     => __('Blur in', 'matthummel'),
        'slide-left'  => __('Slide from left', 'matthummel'),
        'slide-right' => __('Slide from right', 'matthummel'),
    ];
}

add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }

    $sel = function ($id, $label, $choices, $default, $section) use ($wp) {
        $wp->add_setting($id, ['default' => $default, 'sanitize_callback' => 'sanitize_text_field']);
        $wp->add_control($id, ['label' => $label, 'section' => $section, 'type' => 'select', 'choices' => $choices]);
    };

    /* ---- Hero ---- */
    $wp->add_section('mh_hero_section', [
        'title'       => __('Hero', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('Homepage hero: columns, content position, images, background cover and entrance animation.', 'matthummel'),
    ]);

    // Editable hero copy (defaults match the built-in text so nothing changes until edited).
    $wp->add_setting('mh_hero_eyebrow', ['default' => __('Web · WordPress · Power Platform', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_hero_eyebrow', ['label' => __('Eyebrow (small label above title)', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'text']);
    $wp->add_setting('mh_hero_title', ['default' => __('Clean, fast software for the web and Microsoft 365.', 'matthummel'), 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_hero_title', ['label' => __('Hero title (H1)', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'textarea']);
    $wp->add_setting('mh_hero_subtext', ['default' => __("I'm Matt Hummel, a full-stack developer. I write about web development, WordPress, and the Power Platform, and share the tools I build on GitHub.", 'matthummel'), 'sanitize_callback' => 'sanitize_textarea_field']);
    $wp->add_control('mh_hero_subtext', ['label' => __('Hero subtext (paragraph)', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'textarea']);

    $sel('mh_hero_cols', __('Columns', 'matthummel'), ['1' => '1', '2' => '2', '3' => '3'], '1', 'mh_hero_section');
    $sel('mh_hero_align_h', __('Content horizontal position', 'matthummel'), ['left' => __('Left', 'matthummel'), 'center' => __('Center', 'matthummel'), 'right' => __('Right', 'matthummel')], 'center', 'mh_hero_section');
    $sel('mh_hero_align_v', __('Content vertical position', 'matthummel'), ['top' => __('Top', 'matthummel'), 'center' => __('Center', 'matthummel'), 'bottom' => __('Bottom', 'matthummel')], 'center', 'mh_hero_section');
    $sel('mh_hero_content_maxw', __('Content max width', 'matthummel'), ['0' => __('Default', 'matthummel'), '420' => '420px', '480' => '480px', '560' => '560px', '640' => '640px', '760' => '760px', 'full' => __('Full', 'matthummel')], '0', 'mh_hero_section');
    $sel('mh_hero_content_gap', __('Content spacing', 'matthummel'), ['0' => __('Default', 'matthummel'), '8' => __('Tight', 'matthummel'), '16' => __('Normal', 'matthummel'), '26' => __('Roomy', 'matthummel'), '40' => __('Extra', 'matthummel')], '0', 'mh_hero_section');
    $cw = ['0' => __('Default', 'matthummel'), '320' => '320px', '380' => '380px', '420' => '420px', '480' => '480px', '560' => '560px', '640' => '640px', 'full' => __('Full', 'matthummel')];
    $sel('mh_hero_content_maxw_tablet', __('Content max width (tablet)', 'matthummel'), $cw, '0', 'mh_hero_section');
    $sel('mh_hero_content_maxw_mobile', __('Content max width (mobile)', 'matthummel'), $cw, '0', 'mh_hero_section');

    // Advanced flexbox controls for the hero layout container.
    $sel('mh_hero_flex_dir', __('Flexbox: direction', 'matthummel'), ['0' => __('Default', 'matthummel'), 'row' => __('Row', 'matthummel'), 'row-reverse' => __('Row reverse', 'matthummel'), 'column' => __('Column', 'matthummel'), 'column-reverse' => __('Column reverse', 'matthummel')], '0', 'mh_hero_section');
    $sel('mh_hero_flex_justify', __('Flexbox: justify (main axis)', 'matthummel'), ['0' => __('Default', 'matthummel'), 'flex-start' => __('Start', 'matthummel'), 'center' => __('Center', 'matthummel'), 'flex-end' => __('End', 'matthummel'), 'space-between' => __('Space between', 'matthummel'), 'space-around' => __('Space around', 'matthummel'), 'space-evenly' => __('Space evenly', 'matthummel')], '0', 'mh_hero_section');
    $sel('mh_hero_flex_align', __('Flexbox: align (cross axis)', 'matthummel'), ['0' => __('Default', 'matthummel'), 'flex-start' => __('Start', 'matthummel'), 'center' => __('Center', 'matthummel'), 'flex-end' => __('End', 'matthummel'), 'stretch' => __('Stretch', 'matthummel'), 'baseline' => __('Baseline', 'matthummel')], '0', 'mh_hero_section');
    $sel('mh_hero_flex_wrap', __('Flexbox: wrap', 'matthummel'), ['0' => __('Default', 'matthummel'), 'wrap' => __('Wrap', 'matthummel'), 'nowrap' => __('No wrap', 'matthummel'), 'wrap-reverse' => __('Wrap reverse', 'matthummel')], '0', 'mh_hero_section');
    $sel('mh_hero_flex_gap', __('Flexbox: gap', 'matthummel'), ['0' => __('Default', 'matthummel'), '16' => '16px', '24' => '24px', '32' => '32px', '48' => '48px', '64' => '64px'], '0', 'mh_hero_section');
    $sel('mh_hero_img_side', __('Image side (2 columns)', 'matthummel'), ['right' => __('Right', 'matthummel'), 'left' => __('Left', 'matthummel')], 'right', 'mh_hero_section');

    $wp->add_setting('mh_hero_img', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control(new \WP_Customize_Image_Control($wp, 'mh_hero_img', ['label' => __('Side image / illustration', 'matthummel'), 'section' => 'mh_hero_section']));
    $wp->add_setting('mh_hero_img2', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control(new \WP_Customize_Image_Control($wp, 'mh_hero_img2', ['label' => __('Second image (3 columns)', 'matthummel'), 'section' => 'mh_hero_section']));

    $wp->add_setting('mh_hero_bg', ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
    $wp->add_control(new \WP_Customize_Image_Control($wp, 'mh_hero_bg', ['label' => __('Background cover image', 'matthummel'), 'section' => 'mh_hero_section']));
    $wp->add_setting('mh_hero_overlay', ['default' => 45, 'sanitize_callback' => 'absint']);
    $wp->add_control('mh_hero_overlay', ['label' => __('Background overlay (%)', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'number', 'input_attrs' => ['min' => 0, 'max' => 90, 'step' => 5]]);
    $sel('mh_hero_minh', __('Hero min height', 'matthummel'), ['0' => __('Default', 'matthummel'), '420' => '420px', '520' => '520px', '640' => '640px', '100vh' => __('Full screen', 'matthummel')], '0', 'mh_hero_section');

    $sel('mh_hero_anim', __('Hero entrance animation', 'matthummel'), mh_anim_effects(), 'zoom-in', 'mh_hero_section');

    /* ---- Animations (scroll reveal) ---- */
    $wp->add_section('mh_anim_section', [
        'title'       => __('Animations', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description' => __('On-scroll reveal animations applied to sections site-wide.', 'matthummel'),
    ]);
    $wp->add_setting('mh_scroll_enable', ['default' => true, 'sanitize_callback' => 'wp_validate_boolean']);
    $wp->add_control('mh_scroll_enable', ['label' => __('Enable on-scroll animations (site-wide)', 'matthummel'), 'section' => 'mh_anim_section', 'type' => 'checkbox']);
    $sel('mh_scroll_effect', __('Scroll animation effect', 'matthummel'), mh_anim_effects(), 'zoom-in', 'mh_anim_section');
    $sel('mh_scroll_speed', __('Animation speed', 'matthummel'), ['fast' => __('Fast', 'matthummel'), 'normal' => __('Normal', 'matthummel'), 'slow' => __('Slow', 'matthummel')], 'normal', 'mh_anim_section');
}, 24);

/** No-flash: mark <html> anim-ready early so animated elements only hide when JS will reveal them. */
add_action('wp_head', function () {
    $heroAnim = get_theme_mod('mh_hero_anim', 'zoom-in');
    if (! get_theme_mod('mh_scroll_enable', true) && $heroAnim === 'none') {
        return;
    }
    echo "<script>document.documentElement.classList.add('mh-anim');</script>\n";
}, 3);

/** Hero layout CSS (dynamic from mods). */
add_action('mh_head_end', function () {
    $cols = max(1, min(3, (int) get_theme_mod('mh_hero_cols', 1)));
    $ah   = get_theme_mod('mh_hero_align_h', 'center');
    $av   = get_theme_mod('mh_hero_align_v', 'center');
    $bg   = esc_url(get_theme_mod('mh_hero_bg', ''));
    $ov   = max(0, min(90, absint(get_theme_mod('mh_hero_overlay', 45))));
    $minh = (string) get_theme_mod('mh_hero_minh', '0');

    $css  = '.mh-hero{position:relative;}';
    $css .= '.mh-hero .mh-hero-inner{position:relative;z-index:2;}';

    if ($cols >= 2) {
        $css .= '.mh-hero .mh-hero-inner{display:flex;gap:48px;flex-wrap:wrap;align-items:center;text-align:left;}';
        $css .= '.mh-hero .mh-hero-content{flex:1 1 360px;min-width:0;}';
        $css .= '.mh-hero .mh-hero-media{flex:1 1 300px;}';
        $css .= '.mh-hero .mh-hero-media img{width:100%;height:auto;display:block;border-radius:18px;box-shadow:0 24px 60px rgba(16,18,24,.16);}';
        if (get_theme_mod('mh_hero_img_side', 'right') === 'left') {
            $css .= '.mh-hero .mh-hero-inner{flex-direction:row-reverse;}';
        }
    }

    // Content is a flex column so the alignment moves EVERY item (eyebrow, title,
    // lead, buttons) — not just the buttons. We also neutralise the children's own
    // auto-margins / text-align (e.g. .lead has margin:0 auto + text-align:center).
    $ai = $ah === 'left' ? 'flex-start' : ($ah === 'right' ? 'flex-end' : 'center');
    $ta = $ah === 'left' ? 'left' : ($ah === 'right' ? 'right' : 'center');
    $css .= '.mh-hero .mh-hero-content{display:flex;flex-direction:column;align-items:' . $ai . ';}';
    $css .= '.mh-hero .mh-hero-content > *{margin-left:0;margin-right:0;text-align:' . $ta . ';max-width:100%;}';
    $css .= '.mh-hero .btn-row{display:flex;flex-wrap:wrap;gap:12px;justify-content:' . $ai . ';}';

    // Content max-width + (for single-column heroes) block position.
    $maxw = (string) get_theme_mod('mh_hero_content_maxw', '0');
    if ($maxw !== '0' && $maxw !== 'full') {
        $css .= '.mh-hero .mh-hero-content{max-width:' . absint($maxw) . 'px;}';
        if ($cols < 2) {
            $bm = $ah === 'left' ? '0 auto 0 0' : ($ah === 'right' ? '0 0 0 auto' : '0 auto');
            $css .= '.mh-hero .mh-hero-content{margin:' . $bm . ';}';
        }
    }

    // Content spacing (gap between copy items).
    $gap = absint(get_theme_mod('mh_hero_content_gap', 0));
    if ($gap > 0) {
        $css .= '.mh-hero .mh-hero-content{gap:' . $gap . 'px;}';
        $css .= '.mh-hero .mh-hero-content > *{margin-top:0;margin-bottom:0;}';
    }

    // Always keep comfortable side padding so content never touches the device edge.
    $css .= '.mh-hero{padding-left:clamp(20px,5vw,28px);padding-right:clamp(20px,5vw,28px);box-sizing:border-box;}';

    // Per-breakpoint content max-width (tablet 641–1024, mobile ≤640). On a single
    // column we also re-apply the block position so it stays put when narrowed.
    $bwv = function ($v) {
        if ($v === 'full') {
            return 'none';
        }
        return ($v !== '' && $v !== '0') ? absint($v) . 'px' : '';
    };
    $bm  = $ah === 'left' ? '0 auto 0 0' : ($ah === 'right' ? '0 0 0 auto' : '0 auto');
    $pos = $cols < 2 ? 'margin:' . $bm . ';' : '';
    $mt  = $bwv((string) get_theme_mod('mh_hero_content_maxw_tablet', '0'));
    if ($mt !== '') {
        $css .= '@media(min-width:641px) and (max-width:1024px){.mh-hero .mh-hero-content{max-width:' . $mt . ';' . $pos . '}}';
    }
    $mm = $bwv((string) get_theme_mod('mh_hero_content_maxw_mobile', '0'));
    if ($mm !== '') {
        $css .= '@media(max-width:640px){.mh-hero .mh-hero-content{max-width:' . $mm . ';' . $pos . '}}';
    }

    // Multi-column: tighten the column gap as it narrows and stack to full width on mobile.
    if ($cols >= 2) {
        $css .= '@media(max-width:880px){.mh-hero .mh-hero-inner{gap:26px;}}';
        $css .= '@media(max-width:640px){.mh-hero .mh-hero-inner{gap:20px;}.mh-hero .mh-hero-content,.mh-hero .mh-hero-media{flex:1 1 100%;}}';
    }

    // Advanced flexbox overrides on the hero layout container (apply when chosen).
    $clean = function ($v) {
        return preg_replace('/[^a-z-]/', '', (string) $v);
    };
    $flex = '';
    $fd = (string) get_theme_mod('mh_hero_flex_dir', '0');
    $fj = (string) get_theme_mod('mh_hero_flex_justify', '0');
    $fa = (string) get_theme_mod('mh_hero_flex_align', '0');
    $fw = (string) get_theme_mod('mh_hero_flex_wrap', '0');
    $fg = absint(get_theme_mod('mh_hero_flex_gap', 0));
    if ($fd !== '0') {
        $flex .= 'flex-direction:' . $clean($fd) . ';';
    }
    if ($fj !== '0') {
        $flex .= 'justify-content:' . $clean($fj) . ';';
    }
    if ($fa !== '0') {
        $flex .= 'align-items:' . $clean($fa) . ';';
    }
    if ($fw !== '0') {
        $flex .= 'flex-wrap:' . $clean($fw) . ';';
    }
    if ($fg > 0) {
        $flex .= 'gap:' . $fg . 'px;';
    }
    if ($flex !== '') {
        $css .= '.mh-hero .mh-hero-inner{display:flex;' . $flex . '}';
    }

    if ($minh && $minh !== '0') {
        $h     = $minh === '100vh' ? '100vh' : (absint($minh) . 'px');
        $items = $av === 'top' ? 'flex-start' : ($av === 'bottom' ? 'flex-end' : 'center');
        $css .= '.mh-hero{min-height:' . $h . ';display:flex;align-items:' . $items . ';}';
        $css .= '.mh-hero > .mh-hero-inner{width:100%;}';
    }

    if ($bg) {
        $css .= '.mh-hero{background-image:url(\'' . $bg . '\');background-size:cover;background-position:center;border-radius:20px;overflow:hidden;}';
        $css .= '.mh-hero .mh-hero-overlay{position:absolute;inset:0;z-index:1;background:rgba(8,10,14,' . ($ov / 100) . ');}';
        $css .= '.mh-hero,.mh-hero .display-title,.mh-hero .lead{color:#fff;}';
        $css .= '.mh-hero .eyebrow{color:rgba(255,255,255,.86);}';
        $css .= '.mh-hero .btn-outline{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.5);}';
    }

    echo "\n<style id=\"mh-hero\">" . $css . "</style>\n";
}, 16);

/** Animation CSS (initial/hidden states under html.mh-anim). */
add_action('mh_head_end', function () {
    $heroAnim = get_theme_mod('mh_hero_anim', 'zoom-in');
    if (! get_theme_mod('mh_scroll_enable', true) && $heroAnim === 'none') {
        return;
    }
    $speed = get_theme_mod('mh_scroll_speed', 'normal');
    $dur   = $speed === 'fast' ? '.42s' : ($speed === 'slow' ? '.95s' : '.66s');

    $css  = 'html.mh-anim [data-anim]{opacity:0;transition:opacity ' . $dur . ' ease,transform ' . $dur . ' cubic-bezier(.2,.75,.25,1),filter ' . $dur . ' ease;will-change:transform,opacity;}';
    $css .= 'html.mh-anim [data-anim].is-in{opacity:1;transform:none;filter:none;}';
    $css .= 'html.mh-anim [data-anim="fade-up"]{transform:translateY(34px);}';
    $css .= 'html.mh-anim [data-anim="zoom-in"]{transform:scale(.88);}';
    $css .= 'html.mh-anim [data-anim="pop"]{transform:scale(.55);transition-timing-function:cubic-bezier(.34,1.56,.64,1);}';
    $css .= 'html.mh-anim [data-anim="blur-in"]{filter:blur(14px);transform:scale(1.03);}';
    $css .= 'html.mh-anim [data-anim="slide-left"]{transform:translateX(-48px);}';
    $css .= 'html.mh-anim [data-anim="slide-right"]{transform:translateX(48px);}';
    $css .= '@media (prefers-reduced-motion: reduce){html.mh-anim [data-anim]{opacity:1!important;transform:none!important;filter:none!important;transition:none!important;}}';

    echo "\n<style id=\"mh-anim\">" . $css . "</style>\n";
}, 17);

/** Scroll-reveal engine. */
add_action('wp_footer', function () {
    $heroAnim = get_theme_mod('mh_hero_anim', 'zoom-in');
    $enable   = (bool) get_theme_mod('mh_scroll_enable', true);
    $effect   = get_theme_mod('mh_scroll_effect', 'zoom-in');
    if (! $enable && $heroAnim === 'none') {
        return;
    }

    // Sections that receive scroll reveal site-wide.
    $selectors = '.home-section,.section-head,.card-grid,.project-grid,.project-card,.mini-card,.cta-card,.mh-cta-band,.mh-stat-strip,.post-single-title,.post-prose > h2,.post-prose > h3,.service-card,.archive-header,.project-hero,.readme-prose,.contact-form';

    $eff = esc_js($effect);
    $sel = esc_js($selectors);
    $on  = $enable ? '1' : '0';

    $js  = '(function(){';
    $js .= 'var R=window.matchMedia("(prefers-reduced-motion: reduce)").matches;';
    // Hero entrance: reveal on first paint.
    $js .= 'var hero=document.querySelectorAll(".mh-hero [data-anim],.mh-hero[data-anim]");';
    $js .= 'requestAnimationFrame(function(){requestAnimationFrame(function(){hero.forEach(function(el){el.classList.add("is-in");});});});';
    $js .= 'function revealAll(){document.querySelectorAll("[data-anim]").forEach(function(el){el.classList.add("is-in");});}';
    $js .= 'if(R){revealAll();return;}';
    $js .= 'if(' . $on . '){';
    $js .= 'var eff="' . $eff . '";';
    $js .= 'if(eff!=="none"){';
    $js .= 'var nodes=[].slice.call(document.querySelectorAll("' . $sel . '"));';
    $js .= 'nodes=nodes.filter(function(el){return !el.closest(".mh-hero");});';
    $js .= 'nodes.forEach(function(el){if(!el.hasAttribute("data-anim")){el.setAttribute("data-anim",eff);}});';
    $js .= 'if("IntersectionObserver" in window){';
    $js .= 'var io=new IntersectionObserver(function(en){en.forEach(function(e){if(e.isIntersecting){e.target.classList.add("is-in");io.unobserve(e.target);}});},{threshold:0.12,rootMargin:"0px 0px -8% 0px"});';
    $js .= 'nodes.forEach(function(el){io.observe(el);});';
    $js .= '}else{nodes.forEach(function(el){el.classList.add("is-in");});}';
    $js .= '}}';
    // Safety: reveal everything after 3s in case the observer never fires.
    $js .= 'setTimeout(revealAll,3000);';
    $js .= '})();';

    echo "\n<script id=\"mh-anim-js\">" . $js . "</script>\n";
}, 50);
