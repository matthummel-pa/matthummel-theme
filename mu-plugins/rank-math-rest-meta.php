<?php

/**
 * Plugin Name: Rank Math REST Meta
 * Description: Exposes Rank Math focus keyword, title, and description post meta to the REST API for authorized editors.
 * Version: 1.0.0
 * Author: Matt Hummel
 *
 * Must-use: copy or symlink this file into wp-content/mu-plugins/.
 */

declare(strict_types=1);

if (defined('MH_RANK_MATH_REST_META_LOADED')) {
    return;
}

define('MH_RANK_MATH_REST_META_LOADED', true);

add_action('init', static function (): void {
    $keys = [
        'rank_math_focus_keyword',
        'rank_math_title',
        'rank_math_description',
    ];

    foreach ($keys as $key) {
        register_post_meta('post', $key, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);
    }
});
