<?php

declare(strict_types=1);

/**
 * Theme Gutenberg blocks (journal comparison UI).
 */

namespace App;

/**
 * Register static theme blocks. Editor UI is registered from Vite `editor.js`.
 */
add_action('init', function () {
    $blocks = [
        'tool-grid' => [
            'title' => __('Tool comparison grid', 'sage'),
            'description' => __('Two-column grid of AI / workflow tool cards.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'grid-view',
            'supports' => [
                'html' => false,
                'align' => false,
                'className' => true,
            ],
        ],
        'tool-card' => [
            'title' => __('Tool card', 'sage'),
            'description' => __('One tool: mark, role, best for, weak at, human still required.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'index-card',
            'parent' => ['matthummel/tool-grid'],
            'supports' => [
                'html' => false,
                'reusable' => false,
                'className' => true,
            ],
            'attributes' => [
                'mark' => ['type' => 'string', 'default' => 'Cu'],
                'name' => ['type' => 'string', 'default' => ''],
                'role' => ['type' => 'string', 'default' => ''],
                'bestFor' => ['type' => 'string', 'default' => ''],
                'weakAt' => ['type' => 'string', 'default' => ''],
                'humanRequired' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'ship-pipe' => [
            'title' => __('Ship pipeline', 'sage'),
            'description' => __('Numbered handoff steps from brief to ship.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'editor-ol',
            'supports' => [
                'html' => false,
                'className' => true,
            ],
        ],
        'ship-step' => [
            'title' => __('Ship step', 'sage'),
            'description' => __('One numbered step in the ship pipeline.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'marker',
            'parent' => ['matthummel/ship-pipe'],
            'supports' => [
                'html' => false,
                'reusable' => false,
                'className' => true,
            ],
            'attributes' => [
                'title' => ['type' => 'string', 'default' => ''],
                'body' => ['type' => 'string', 'default' => ''],
            ],
        ],
    ];

    foreach ($blocks as $slug => $args) {
        register_block_type('matthummel/'.$slug, array_merge($args, [
            'api_version' => 3,
        ]));
    }
}, 9);

/**
 * Block category for journal comparison blocks.
 */
add_filter('block_categories_all', function (array $categories): array {
    $categories[] = [
        'slug' => 'matthummel',
        'title' => __('Matt Hummel', 'sage'),
        'icon' => null,
    ];

    return $categories;
});
