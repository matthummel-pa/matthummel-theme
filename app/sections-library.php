<?php

/**
 * Expanded, categorized "section" pattern library on top of the base patterns in
 * blocks.php / patterns-extra.php. Adds organized categories so the inserter
 * groups sections by purpose (heroes, CTAs, content, social proof, dev).
 */

namespace App;

add_action('init', function () {
    if (! function_exists('register_block_pattern_category') || ! function_exists('register_block_pattern')) {
        return;
    }

    foreach ([
        'mh-heroes'      => __('MH · Heroes', 'matthummel'),
        'mh-cta'         => __('MH · Call to action', 'matthummel'),
        'mh-content'     => __('MH · Content', 'matthummel'),
        'mh-socialproof' => __('MH · Social proof', 'matthummel'),
        'mh-dev'         => __('MH · Developer', 'matthummel'),
    ] as $slug => $label) {
        register_block_pattern_category($slug, ['label' => $label]);
    }

    $patterns = [];

    $patterns['matthummel/hero-centered-minimal'] = ['title' => __('Hero — centered minimal', 'matthummel'), 'categories' => ['mh-heroes', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:group {"className":"mh-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-hero"><!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">A short, bold statement about what you do</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">One clarifying sentence underneath. Keep it to a single idea.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Primary action</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML];

    $patterns['matthummel/hero-dev'] = ['title' => __('Hero — developer (with repos)', 'matthummel'), 'categories' => ['mh-heroes', 'mh-dev', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:group {"className":"mh-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-hero"><!-- wp:paragraph {"align":"center","className":"mh-eyebrow"} -->
<p class="has-text-align-center mh-eyebrow">OPEN SOURCE · BUILDING IN PUBLIC</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">I build tools developers actually use</h1>
<!-- /wp:heading -->
<!-- wp:mh/repo-grid {"count":4,"columns":2} /--></div>
<!-- /wp:group -->
HTML];

    $patterns['matthummel/cta-split'] = ['title' => __('CTA — split', 'matthummel'), 'categories' => ['mh-cta', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:group {"className":"mh-cta-band","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group mh-cta-band"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Ready to start your project?</h2>
<!-- /wp:heading -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Get in touch</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML];

    $patterns['matthummel/about-two-col'] = ['title' => __('About — two column', 'matthummel'), 'categories' => ['mh-content', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:columns {"verticalAlignment":"center","className":"mh-about"} -->
<div class="wp-block-columns are-vertically-aligned-center mh-about"><!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%"><!-- wp:image {"className":"is-style-rounded"} -->
<figure class="wp-block-image is-style-rounded"><img alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">About</h2>
<!-- /wp:heading -->
<!-- wp:paragraph --><p>Two or three sentences about who you are, what you build, and why it matters. Keep it human and specific.</p><!-- /wp:paragraph -->
<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">More about me</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML];

    $patterns['matthummel/services-three'] = ['title' => __('Services — three cards', 'matthummel'), 'categories' => ['mh-content', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:columns {"className":"mh-feature-grid"} -->
<div class="wp-block-columns mh-feature-grid"><!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Web design</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Clean, fast, accessible sites built to last.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Development</h3><!-- /wp:heading --><!-- wp:paragraph --><p>WordPress themes, web apps, and integrations.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"is-style-mh-card"} -->
<div class="wp-block-column is-style-mh-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Consulting</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Architecture, performance, and Power Platform.</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML];

    $patterns['matthummel/stats-four'] = ['title' => __('Stats — four up', 'matthummel'), 'categories' => ['mh-socialproof', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:columns {"className":"mh-stat-strip"} -->
<div class="wp-block-columns mh-stat-strip"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">12+</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Years</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">40+</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Projects</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">2</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Products</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} --><h3 class="wp-block-heading has-text-align-center">100%</h3><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Open source</p><!-- /wp:paragraph --></div><!-- /wp:column --></div>
<!-- /wp:columns -->
HTML];

    $patterns['matthummel/testimonial-single'] = ['title' => __('Testimonial — single large', 'matthummel'), 'categories' => ['mh-socialproof', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:group {"className":"mh-quote-lg","layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group mh-quote-lg"><!-- wp:quote {"className":"is-style-mh-card"} -->
<blockquote class="wp-block-quote is-style-mh-card"><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">"A genuinely standout testimonial that's specific about the result and easy to believe."</p><!-- /wp:paragraph --><cite>Client name — Company</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group -->
HTML];

    $patterns['matthummel/contact-cta'] = ['title' => __('Contact CTA — with socials', 'matthummel'), 'categories' => ['mh-cta', 'matthummel'], 'content' => <<<'HTML'
<!-- wp:group {"className":"mh-cta-band","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-cta-band"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Let's build something</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Find me on the platforms below, or send a message.</p><!-- /wp:paragraph -->
<!-- wp:mh/social-links {"align":"center","shape":"circle"} /--></div>
<!-- /wp:group -->
HTML];

    foreach ($patterns as $name => $args) {
        register_block_pattern($name, $args);
    }
});

add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-sections\">.mh-about .wp-block-image img{border-radius:16px;}.mh-quote-lg blockquote{font-size:22px;}.mh-quote-lg cite{display:block;text-align:center;margin-top:12px;}</style>\n";
}, 13);
