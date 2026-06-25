<?php

/**
 * Additional premium starter patterns (Hero, Pricing, Testimonials, Logo cloud,
 * Feature grid) in the "Matt Hummel" pattern category. Composed from core blocks.
 */

namespace App;

add_action('init', function () {
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern('matthummel/hero', [
        'title'      => __('Hero', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:group {"className":"mh-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-hero"><!-- wp:paragraph {"align":"center","className":"mh-eyebrow"} -->
<p class="has-text-align-center mh-eyebrow">DEVELOPER · DESIGNER · BUILDER</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">I build clean, fast software for the web</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">WordPress themes, web apps, and Power Platform tools — designed to be simple and durable.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">See projects</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Get in touch</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML,
    ]);

    register_block_pattern('matthummel/pricing', [
        'title'      => __('Pricing (3 tiers)', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:columns {"className":"mh-pricing"} -->
<div class="wp-block-columns mh-pricing"><!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">Starter</h3>
<!-- /wp:heading -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">$900</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>One-page site</li><!-- /wp:list-item --><!-- wp:list-item --><li>Responsive + accessible</li><!-- /wp:list-item --><!-- wp:list-item --><li>1 revision round</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Choose</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">Studio</h3>
<!-- /wp:heading -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">$2,500</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>Multi-page site</li><!-- /wp:list-item --><!-- wp:list-item --><li>Custom design system</li><!-- /wp:list-item --><!-- wp:list-item --><li>CMS + training</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Choose</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">Custom</h3>
<!-- /wp:heading -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Let's talk</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>Web apps</li><!-- /wp:list-item --><!-- wp:list-item --><li>Integrations</li><!-- /wp:list-item --><!-- wp:list-item --><li>Ongoing support</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Contact</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
    ]);

    register_block_pattern('matthummel/testimonials', [
        'title'      => __('Testimonials', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:columns {"className":"mh-testimonials"} -->
<div class="wp-block-columns mh-testimonials"><!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph --><p>"Matt shipped exactly what we needed, fast, and it just worked."</p><!-- /wp:paragraph --><cite>Client name, Company</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph --><p>"Clean code, clear communication, on time. Highly recommend."</p><!-- /wp:paragraph --><cite>Client name, Company</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
    ]);

    register_block_pattern('matthummel/logo-cloud', [
        'title'      => __('Logo cloud', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:group {"className":"mh-logo-cloud","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-logo-cloud"><!-- wp:paragraph {"align":"center","className":"mh-eyebrow"} -->
<p class="has-text-align-center mh-eyebrow">TRUSTED BY TEAMS USING</p>
<!-- /wp:paragraph -->
<!-- wp:gallery {"columns":5,"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-5 is-cropped"></figure>
<!-- /wp:gallery --></div>
<!-- /wp:group -->
HTML,
    ]);

    register_block_pattern('matthummel/feature-grid', [
        'title'      => __('Feature grid', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:columns {"className":"mh-feature-grid"} -->
<div class="wp-block-columns mh-feature-grid"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Fast</h3>
<!-- /wp:heading -->
<!-- wp:paragraph --><p>Server-rendered, lean assets, no bloat. Built to score well on Core Web Vitals.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Accessible</h3>
<!-- /wp:heading -->
<!-- wp:paragraph --><p>Semantic markup, keyboard-friendly, and WCAG-minded from the start.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Maintainable</h3>
<!-- /wp:heading -->
<!-- wp:paragraph --><p>Clean Blade templates and a design-token system that's easy to extend.</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
    ]);
});

/** Helper styles for patterns + the mh-card block style. */
add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-patterns\">"
        . '.mh-eyebrow{letter-spacing:.12em;font-size:12px;font-weight:600;text-transform:uppercase;color:var(--color-muted,#5c636c);margin-bottom:10px;}'
        . '.mh-hero{padding:48px 0;}.mh-hero h1{margin:.2em 0;}'
        . '.mh-logo-cloud{padding:24px 0;}'
        . '.is-style-mh-card{background:var(--color-surface,#fff);border:1px solid var(--color-line,#e6e2d9);border-radius:16px;padding:24px;}'
        . '.mh-pricing .wp-block-column,.mh-testimonials .wp-block-column{padding:24px;}'
        . '.mh-pricing h2{color:var(--color-green,#2f6b4e);}'
        . '.mh-feature-grid h3{margin-top:0;}'
        . '.wp-block-mh-icon{margin:8px 0;}'
        . "</style>\n";
}, 13);
