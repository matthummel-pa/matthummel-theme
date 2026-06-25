<?php

/**
 * Additional WordPress theme supports (block editor + media features).
 */

namespace App;

add_action('after_setup_theme', function () {
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-formats', ['aside', 'gallery', 'link', 'image', 'quote', 'video', 'audio', 'status']);
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
}, 20);
