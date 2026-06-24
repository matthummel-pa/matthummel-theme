<?php

/**
 * Block patterns (Callout, CTA band, Stat strip, FAQ) + a "Card" block style.
 * Composed from core blocks and styled by the theme's design tokens.
 */

namespace App;

add_action('init', function () {
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('matthummel', ['label' => __('Matt Hummel', 'matthummel')]);
    }
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern('matthummel/callout', [
        'title'      => __('Callout', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:group {"className":"mh-callout"} -->
<div class="wp-block-group mh-callout"><!-- wp:paragraph -->
<p><strong>Heads up:</strong> Use this callout to highlight an important note, tip, or warning.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
    ]);

    register_block_pattern('matthummel/cta-band', [
        'title'      => __('CTA band', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:group {"className":"mh-cta-band","layout":{"type":"constrained"}} -->
<div class="wp-block-group mh-cta-band"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Have a project in mind?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Tell me what you're building and let's talk.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Start a conversation</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML,
    ]);

    register_block_pattern('matthummel/stat-strip', [
        'title'      => __('Stat strip', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:columns {"className":"mh-stat-strip"} -->
<div class="wp-block-columns mh-stat-strip"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">12+</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Years building</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">30+</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Projects shipped</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">2</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Open-source tools</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
    ]);

    register_block_pattern('matthummel/faq', [
        'title'      => __('FAQ', 'matthummel'),
        'categories' => ['matthummel'],
        'content'    => <<<'HTML'
<!-- wp:group {"className":"mh-faq"} -->
<div class="wp-block-group mh-faq"><!-- wp:details -->
<details class="wp-block-details"><summary>What do you build?</summary><!-- wp:paragraph -->
<p>Websites, WordPress themes, and Power Platform solutions.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
<!-- wp:details -->
<details class="wp-block-details"><summary>How can we work together?</summary><!-- wp:paragraph -->
<p>Reach out via the contact page and tell me about your project.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details --></div>
<!-- /wp:group -->
HTML,
    ]);
});

/** "Card" block style for groups + columns. */
add_action('init', function () {
    if (! function_exists('register_block_style')) {
        return;
    }
    register_block_style('core/group', ['name' => 'mh-card', 'label' => __('Card', 'matthummel')]);
    register_block_style('core/column', ['name' => 'mh-card', 'label' => __('Card', 'matthummel')]);
});
