<?php

declare(strict_types=1);

/**
 * Theme Gutenberg blocks (journal Tool Blocks + ship pipeline).
 */

namespace App;

/**
 * Curated icon choices for Tool Blocks cards (SelectControl + editor preview).
 *
 * @return list<array{value: string, label: string, svg?: string}>
 */
function mh_tool_block_icon_choices(): array
{
    $keys = [
        '' => __('Letter mark only', 'sage'),
        'cursor-ai' => __('Cursor', 'sage'),
        'chatgpt' => __('ChatGPT', 'sage'),
        'claude' => __('Claude', 'sage'),
        'gemini' => __('Gemini', 'sage'),
        'vscode' => __('VS Code', 'sage'),
        'n8n' => __('n8n', 'sage'),
        'code' => __('MCP / code', 'sage'),
        'github' => __('GitHub', 'sage'),
        'vite' => __('Vite', 'sage'),
        'wordpress' => __('WordPress', 'sage'),
        'notion' => __('Notion', 'sage'),
        'git' => __('Git', 'sage'),
        'plugins' => __('Plugins', 'sage'),
        'sage' => __('Sage', 'sage'),
    ];

    $out = [];
    foreach ($keys as $value => $label) {
        $item = [
            'value' => $value,
            'label' => $label,
        ];
        if ($value !== '') {
            $item['svg'] = mh_svg_icon($value, 18);
        }
        $out[] = $item;
    }

    return $out;
}

/**
 * CSS slug for a tool-card mark chip.
 */
function mh_tool_card_mark_slug(string $mark): string
{
    $key = trim($mark);
    $known = [
        'Cu' => 'cu',
        'GPT' => 'gpt',
        'Cl' => 'cl',
        'Ge' => 'ge',
        'VS' => 'vs',
        'n8' => 'n8',
        'MC' => 'mc',
        'GH' => 'gh',
        'Vi' => 'vi',
    ];
    if (isset($known[$key])) {
        return $known[$key];
    }

    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $key) ?? '');

    return $slug !== '' ? substr($slug, 0, 4) : 'cu';
}

/**
 * Front-end (and SSR) markup for a Tool card.
 *
 * @param  array<string, mixed>  $attributes
 */
function mh_render_tool_card_block(array $attributes, string $content = ''): string
{
    $mark = isset($attributes['mark']) ? (string) $attributes['mark'] : 'Cu';
    $icon = isset($attributes['icon']) ? sanitize_key((string) $attributes['icon']) : '';
    $name = isset($attributes['name']) ? (string) $attributes['name'] : '';
    $role = isset($attributes['role']) ? (string) $attributes['role'] : '';
    $bestFor = isset($attributes['bestFor']) ? (string) $attributes['bestFor'] : '';
    $weakAt = isset($attributes['weakAt']) ? (string) $attributes['weakAt'] : '';
    $humanRequired = isset($attributes['humanRequired']) ? (string) $attributes['humanRequired'] : '';
    $bestForLabel = isset($attributes['bestForLabel']) && (string) $attributes['bestForLabel'] !== ''
        ? (string) $attributes['bestForLabel']
        : __('Best for', 'sage');
    $weakAtLabel = isset($attributes['weakAtLabel']) && (string) $attributes['weakAtLabel'] !== ''
        ? (string) $attributes['weakAtLabel']
        : __('Weak at', 'sage');
    $humanRequiredLabel = isset($attributes['humanRequiredLabel']) && (string) $attributes['humanRequiredLabel'] !== ''
        ? (string) $attributes['humanRequiredLabel']
        : __('Human still required', 'sage');

    $slug = mh_tool_card_mark_slug($mark);
    $classes = ['mh-tool-card', 'mh-tool-card--'.$slug];
    if ($icon !== '') {
        $classes[] = 'mh-tool-card--has-icon';
    }

    $markInner = $icon !== ''
        ? mh_svg_icon($icon, 18)
        : esc_html($mark !== '' ? $mark : '·');

    $wrapper = get_block_wrapper_attributes([
        'class' => implode(' ', $classes),
        'data-mark' => $mark,
    ]);

    return sprintf(
        '<div %1$s><span class="mh-tool-card__mark" aria-hidden="true">%2$s</span><p class="mh-tool-card__name">%3$s</p><p class="mh-tool-card__role">%4$s</p><p class="mh-tool-card__dt mh-tool-card__dt--best">%5$s</p><p class="mh-tool-card__dd">%6$s</p><p class="mh-tool-card__dt mh-tool-card__dt--weak">%7$s</p><p class="mh-tool-card__dd">%8$s</p><p class="mh-tool-card__dt mh-tool-card__dt--human">%9$s</p><p class="mh-tool-card__dd">%10$s</p></div>',
        $wrapper,
        $markInner,
        esc_html($name),
        esc_html($role),
        esc_html($bestForLabel),
        wp_kses_post($bestFor),
        esc_html($weakAtLabel),
        wp_kses_post($weakAt),
        esc_html($humanRequiredLabel),
        wp_kses_post($humanRequired)
    );
}

/**
 * Register theme blocks. Editor UI is registered from Vite `editor.js`.
 */
add_action('init', function () {
    $toolCardAttributes = [
        'icon' => ['type' => 'string', 'default' => ''],
        'mark' => ['type' => 'string', 'default' => 'Cu'],
        'name' => ['type' => 'string', 'default' => ''],
        'role' => ['type' => 'string', 'default' => ''],
        'bestForLabel' => ['type' => 'string', 'default' => 'Best for'],
        'weakAtLabel' => ['type' => 'string', 'default' => 'Weak at'],
        'humanRequiredLabel' => ['type' => 'string', 'default' => 'Human still required'],
        'bestFor' => ['type' => 'string', 'default' => ''],
        'weakAt' => ['type' => 'string', 'default' => ''],
        'humanRequired' => ['type' => 'string', 'default' => ''],
    ];

    $blocks = [
        'tool-grid' => [
            'title' => __('Tool Blocks', 'sage'),
            'description' => __('Comparison grid of tool cards. Add as many cards as you need.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'grid-view',
            'supports' => [
                'html' => false,
                'align' => false,
                'className' => true,
            ],
            'attributes' => [
                'ariaLabel' => [
                    'type' => 'string',
                    'default' => 'Comparison of tools I use to ship WordPress work',
                ],
            ],
        ],
        'tool-card' => [
            'title' => __('Tool card', 'sage'),
            'description' => __('One tool: icon or mark, name, role, and comparison fields.', 'sage'),
            'category' => 'matthummel',
            'icon' => 'index-card',
            'parent' => ['matthummel/tool-grid'],
            'supports' => [
                'html' => false,
                'reusable' => false,
                'className' => true,
            ],
            'attributes' => $toolCardAttributes,
            'render_callback' => __NAMESPACE__.'\\mh_render_tool_card_block',
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
 * Expose Tool Blocks icon catalog to the block editor (Vite loads editor.js separately).
 */
add_action('admin_head', function () {
    if (! function_exists('get_current_screen') || ! get_current_screen()?->is_block_editor()) {
        return;
    }

    $payload = [
        'icons' => mh_tool_block_icon_choices(),
    ];

    printf(
        '<script>window.mhToolBlocks = %s;</script>'."\n",
        wp_json_encode($payload)
    );
}, 1);

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
