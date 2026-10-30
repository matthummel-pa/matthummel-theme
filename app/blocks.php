<?php

declare(strict_types=1);

/**
 * Theme Gutenberg blocks.
 *
 * - Journal blocks (Tool Blocks, ship pipeline), registered from the array below.
 * - Page blocks (hero, section, card grid, questions, CTA band, page nav), registered
 *   from block.json in resources/blocks and rendered by Blade views in
 *   resources/views/blocks. A page that uses them stores its copy in post_content.
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
/**
 * Allowed heading tag for tool-card titles (H2–H4).
 */
function mh_tool_card_heading_tag(string $level): string
{
    $n = (int) $level;

    if ($n < 2 || $n > 4) {
        $n = 3;
    }

    return 'h'.$n;
}

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
    $nameHeadingLevel = mh_tool_card_heading_tag(
        isset($attributes['nameHeadingLevel']) ? (string) $attributes['nameHeadingLevel'] : '3'
    );

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

    $card = sprintf(
        '<article %1$s><span class="mh-tool-card__mark" aria-hidden="true">%2$s</span><%11$s class="mh-tool-card__name">%3$s</%11$s><p class="mh-tool-card__role">%4$s</p><dl class="mh-tool-card__details"><dt class="mh-tool-card__dt mh-tool-card__dt--best">%5$s</dt><dd class="mh-tool-card__dd">%6$s</dd><dt class="mh-tool-card__dt mh-tool-card__dt--weak">%7$s</dt><dd class="mh-tool-card__dd">%8$s</dd><dt class="mh-tool-card__dt mh-tool-card__dt--human">%9$s</dt><dd class="mh-tool-card__dd">%10$s</dd></dl></article>',
        $wrapper,
        $markInner,
        esc_html($name),
        esc_html($role),
        esc_html($bestForLabel),
        wp_kses_post($bestFor),
        esc_html($weakAtLabel),
        wp_kses_post($weakAt),
        esc_html($humanRequiredLabel),
        wp_kses_post($humanRequired),
        $nameHeadingLevel
    );

    return '<li class="mh-tool-grid__item">'.$card.'</li>';
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
        'nameHeadingLevel' => ['type' => 'number', 'default' => 3],
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

/*
 * ── Page blocks (3.7.0) ─────────────────────────────────────────────────────
 * Pages move from Page content (theme) fields to blocks one template at a time.
 * A page renders from blocks when it uses the "Block page" template or carries
 * the _mh_block_page flag (set when its fields are converted).
 */

const MH_BLOCK_PAGE_META = '_mh_block_page';
const MH_BLOCK_PAGE_TEMPLATE = 'template-blocks.blade.php';

/** Remembers which field template a converted page came from, e.g. "template-about.blade.php". */
const MH_BLOCK_PAGE_FROM_META = '_mh_block_page_from';

/** Page blocks that hold other blocks. Every other folder in resources/blocks is a data block. */
const MH_CONTAINER_BLOCKS = ['hero', 'section', 'cards', 'card', 'faq', 'cta-band', 'page-nav'];

/**
 * Whether a page keeps its content in blocks instead of theme fields.
 */
function mh_page_uses_blocks(?int $post_id = null): bool
{
    $post_id = $post_id ?: (int) get_the_ID();
    if ($post_id < 1 || get_post_type($post_id) !== 'page') {
        return false;
    }

    return get_post_meta($post_id, '_wp_page_template', true) === MH_BLOCK_PAGE_TEMPLATE
        || get_post_meta($post_id, MH_BLOCK_PAGE_META, true) === '1';
}

/**
 * Whether the current request is the front-end view of a block page.
 */
function mh_is_block_page_view(): bool
{
    static $is = null;
    if ($is === null && did_action('wp')) {
        // The Journal (posts page) prints its page's blocks above the post list.
        $is = (is_singular('page') || (is_home() && ! is_front_page()))
            && mh_page_uses_blocks((int) get_queried_object_id());
    }

    return (bool) $is;
}

/**
 * The template a page is known by: the field template it was converted from, or its stored template.
 *
 * Code that asks "is this the About page?" keeps working after the page moves to the Block page template.
 */
function mh_page_template_slug(?int $post_id = null): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    if ($post_id < 1) {
        return '';
    }
    $from = (string) get_post_meta($post_id, MH_BLOCK_PAGE_FROM_META, true);

    return $from !== '' ? $from : (string) get_post_meta($post_id, '_wp_page_template', true);
}

/**
 * Folder names of the data blocks: PHP-rendered blocks with settings and no inner blocks.
 *
 * @return list<string>
 */
function mh_data_block_slugs(): array
{
    static $slugs = null;
    if ($slugs === null) {
        $slugs = [];
        foreach (glob(get_theme_file_path('resources/blocks/*/block.json')) ?: [] as $file) {
            $slug = basename(dirname($file));
            if (! in_array($slug, MH_CONTAINER_BLOCKS, true)) {
                $slugs[] = $slug;
            }
        }
    }

    return $slugs;
}

/**
 * Data blocks and their sidebar controls for the editor script.
 *
 * Controls come from each block.json: one per attribute, labelled by the
 * theme's own "mhControls" key (label, help, min, max, options).
 *
 * @return list<array{name: string, controls: list<array<string, mixed>>}>
 */
function mh_data_blocks_for_editor(): array
{
    $out = [];
    foreach (mh_data_block_slugs() as $slug) {
        $meta = json_decode((string) file_get_contents(get_theme_file_path('resources/blocks/'.$slug.'/block.json')), true);
        if (! is_array($meta) || empty($meta['name'])) {
            continue;
        }
        $controls = [];
        foreach ((array) ($meta['attributes'] ?? []) as $key => $attr) {
            $ui = (array) ($meta['mhControls'][$key] ?? []);
            if ($ui === []) {
                continue;
            }
            $type = (string) ($attr['type'] ?? 'string');
            $controls[] = [
                'key' => (string) $key,
                'type' => isset($ui['options']) ? 'select' : ($type === 'boolean' ? 'toggle' : (in_array($type, ['number', 'integer'], true) ? 'number' : 'text')),
                'label' => (string) ($ui['label'] ?? $key),
                'help' => (string) ($ui['help'] ?? ''),
                'min' => (int) ($ui['min'] ?? 1),
                'max' => (int) ($ui['max'] ?? 12),
                'options' => array_map(
                    static fn ($value, $label) => ['value' => (string) $value, 'label' => (string) $label],
                    array_keys((array) ($ui['options'] ?? [])),
                    array_values((array) ($ui['options'] ?? []))
                ),
            ];
        }
        $out[] = ['name' => (string) $meta['name'], 'controls' => $controls];
    }

    return $out;
}

/**
 * Pages open in the block editor unless their template still uses theme fields.
 *
 * `use_block_editor_for_post` runs after the per-post-type filter in app/bespoke.php,
 * which keeps field-based pages on the classic screen.
 *
 * @param  bool  $use  Whether the block editor is used so far.
 * @param  \WP_Post  $post  Post being edited.
 */
add_filter('use_block_editor_for_post', function ($use, $post) {
    if (! $post instanceof \WP_Post || $post->post_type !== 'page') {
        return $use;
    }
    if (mh_page_uses_blocks((int) $post->ID)) {
        return true;
    }

    return empty(page_field_map()[page_template_key((int) $post->ID)]);
}, 20, 2);

/** The fields box has nothing to edit on a block page. */
add_action('add_meta_boxes_page', function (\WP_Post $post): void {
    if (mh_page_uses_blocks((int) $post->ID)) {
        remove_meta_box('mh_page_content', 'page', 'normal');
    }
}, 20);

/**
 * Render a page block through its Blade view (resources/views/blocks/<name>.blade.php).
 *
 * @param  array<string, mixed>  $attributes  Block attributes.
 * @param  string  $content  Rendered inner blocks.
 * @param  \WP_Block  $block  Block instance.
 */
function mh_render_page_block(array $attributes, string $content, \WP_Block $block): string
{
    // The view is named after the folder, which matches the part after the namespace.
    // Most blocks are matthummel/*; mh/release-rundown keeps the old plugin's name so saved pages still render.
    $slug = sanitize_key(substr((string) strrchr((string) $block->name, '/'), 1));
    if ($slug === 'card') {
        $content = mh_html_add_class($content, 'H3', 'about-svc-card__title');
        $content = mh_html_add_class($content, 'P', 'about-svc-card__body');
    }

    $isHomeHero = $slug === 'hero' && ($attributes['layout'] ?? 'page') === 'home';
    if ($isHomeHero) {
        $content = mh_home_hero_classes($content);
    }

    $html = view('blocks.'.$slug, [
        'attributes' => $attributes,
        'content' => $content,
        'block' => $block,
    ])->render();

    // The hero and the CTA band are dark in both modes, where the outline button's dark label fails contrast.
    if ($slug === 'hero' || $slug === 'cta-band') {
        $tags = new \WP_HTML_Tag_Processor($html);
        while ($tags->next_tag(['tag_name' => 'A', 'class_name' => 'btn-outline'])) {
            $tags->remove_class('btn-outline');
            $tags->add_class('btn-ghost');
        }
        $html = $tags->get_updated_html();
    }

    // Hero buttons sit in the same row class the field templates use, which styles the text link for a dark band.
    if ($slug === 'hero' && ! $isHomeHero) {
        $html = (string) preg_replace('/class="wp-block-buttons\b/', 'class="page-header-split__actions wp-block-buttons', $html);
    }

    return $html;
}

/**
 * Swap the page-hero classes on the Home hero's inner blocks for the Home hero's own.
 *
 * Heading → name, lead paragraph → lede, other paragraphs → role line, buttons → action row.
 */
function mh_home_hero_classes(string $content): string
{
    $tags = new \WP_HTML_Tag_Processor($content);
    $primaryDone = false;
    while ($tags->next_tag()) {
        switch ($tags->get_tag()) {
            case 'H1':
                $tags->remove_class('display-title');
                $tags->remove_class('is-hero');
                $tags->add_class('h-hero__name');
                break;
            case 'P':
                if ($tags->has_class('lead')) {
                    $tags->remove_class('lead');
                    $tags->add_class('h-hero__lede');
                } else {
                    $tags->add_class('h-hero__role');
                }
                break;
            case 'DIV':
                if ($tags->has_class('wp-block-buttons')) {
                    $tags->add_class('h-hero__actions');
                }
                break;
            case 'A':
                if (! $primaryDone && $tags->has_class('btn')) {
                    $tags->add_class('h-hero__cta');
                    $primaryDone = true;
                }
                break;
        }
    }

    return $tags->get_updated_html();
}

/**
 * Add a class to every element with the given tag in an HTML fragment.
 *
 * @param  string  $html  HTML fragment.
 * @param  string  $tag  Upper-case tag name, e.g. "H3".
 * @param  string  $class  One or more class names.
 */
function mh_html_add_class(string $html, string $tag, string $class): string
{
    $tags = new \WP_HTML_Tag_Processor($html);
    while ($tags->next_tag(['tag_name' => $tag])) {
        foreach (preg_split('/\s+/', trim($class)) ?: [] as $name) {
            $tags->add_class($name);
        }
    }

    return $tags->get_updated_html();
}

/** Register the page blocks from their block.json files. */
add_action('init', function (): void {
    foreach (array_merge(MH_CONTAINER_BLOCKS, mh_data_block_slugs()) as $slug) {
        register_block_type(get_theme_file_path('resources/blocks/'.$slug), [
            'render_callback' => __NAMESPACE__.'\\mh_render_page_block',
        ]);
    }

    // Extra styles for core blocks, picked in the block sidebar. The classes are mapped below.
    register_block_style('core/button', ['name' => 'ghost', 'label' => __('Ghost', 'sage')]);
    register_block_style('core/button', ['name' => 'arrow', 'label' => __('Text with arrow', 'sage')]);
    register_block_style('core/paragraph', ['name' => 'lead', 'label' => __('Lead', 'sage')]);
    register_block_style('core/list', ['name' => 'checklist', 'label' => __('Checklist', 'sage')]);
    // A plain Group on the theme card recipe (lime border, card surface). Styled in resources/css/blocks.css.
    register_block_style('core/group', ['name' => 'card', 'label' => __('Card', 'sage')]);
}, 9);

/**
 * Give core blocks the theme's own classes on block pages.
 *
 * The theme styles `.btn`, `.display-title` and `.lead`; core prints
 * `.wp-block-button__link`, `.wp-block-heading` and plain paragraphs. Adding the
 * theme class here means there is one set of styles, not a second copy for blocks.
 *
 * @param  string  $html  Rendered block.
 * @param  array<string, mixed>  $block  Parsed block.
 */
add_filter('render_block', function ($html, $block) {
    // Widget areas (the footer) keep their own classes: a footer Heading stays a label, not a section title.
    if (! is_string($html) || $html === '' || ! mh_is_block_page_view() || mh_in_widget_area()) {
        return $html;
    }

    $classes = (string) ($block['attrs']['className'] ?? '');

    switch ($block['blockName'] ?? '') {
        case 'core/heading':
            $level = (int) ($block['attrs']['level'] ?? 2);
            if ($level === 1) {
                return mh_html_add_class($html, 'H1', 'display-title is-hero');
            }

            return $level === 2 ? mh_html_add_class($html, 'H2', 'display-title is-section') : $html;

        case 'core/paragraph':
            return str_contains($classes, 'is-style-lead') ? mh_html_add_class($html, 'P', 'lead') : $html;

        case 'core/button':
            $theme = match (true) {
                str_contains($classes, 'is-style-outline') => 'btn btn-outline',
                str_contains($classes, 'is-style-ghost') => 'btn btn-ghost',
                str_contains($classes, 'is-style-arrow') => 'h-text-arrow',
                default => 'btn',
            };

            $html = mh_html_add_class($html, 'A', $theme);
            if ($theme !== 'h-text-arrow') {
                return $html;
            }

            // A text link, not a button: drop core's button classes so its dark fill does not apply.
            $tags = new \WP_HTML_Tag_Processor($html);
            while ($tags->next_tag(['tag_name' => 'A'])) {
                $tags->remove_class('wp-block-button__link');
                $tags->remove_class('wp-element-button');
            }

            return $tags->get_updated_html();

        case 'core/buttons':
            $tags = new \WP_HTML_Tag_Processor($html);
            if ($tags->next_tag(['tag_name' => 'DIV'])) {
                $tags->add_class('mh-buttons');
            }

            return $tags->get_updated_html();
    }

    return $html;
}, 10, 2);

/**
 * True while WordPress is printing a widget area (`dynamic_sidebar()`), including
 * Customizer partial refreshes. Core fires the before/after actions as a pair.
 *
 * @since 3.7.13
 */
function mh_in_widget_area(): bool
{
    return did_action('dynamic_sidebar_before') > did_action('dynamic_sidebar_after');
}

/**
 * Hostinger Reach "classic" subscription form on the site recipe.
 *
 * The plugin block prints `<form class="hostinger-reach-block-subscription-form">`
 * with its own button and input classes (a Reach *builder* form is an iframe
 * instead and is not touched here). Where the theme already maps buttons to
 * `.btn` — widget areas and block pages — the submit becomes `.btn`; in a widget
 * area the inputs also take `.footer-follow__email`, so the theme's existing
 * light, dark, hover, and focus rules apply. Elsewhere the block is left alone.
 * Submit still goes through the plugin's `view.js`, which only needs the form
 * class and the message element.
 *
 * @since 3.7.13
 *
 * @param  string  $html  Rendered block.
 */
function mh_reach_form_block_html(mixed $html): mixed
{
    if (! is_string($html) || ! str_contains($html, 'hostinger-reach-block-subscription-form')) {
        return $html;
    }

    $inWidgetArea = mh_in_widget_area();
    if (! $inWidgetArea && ! mh_is_block_page_view()) {
        return $html;
    }

    $tags = new \WP_HTML_Tag_Processor($html);

    while ($tags->next_tag()) {
        $tag = $tags->get_tag();

        if ($tag === 'BUTTON' && $tags->has_class('hostinger-reach-block-submit')) {
            // Drop the plugin's button classes so its stylesheet (pill, full width, 40px) does not fight `.btn`.
            foreach (['hostinger-reach-block-submit', 'has-light-color', 'has-color-3-background-color', 'has-text-color', 'has-background', 'has-link-color'] as $class) {
                $tags->remove_class($class);
            }
            $tags->add_class('btn');
            $tags->add_class('mh-reach-submit');

            continue;
        }

        if ($inWidgetArea && $tag === 'INPUT' && in_array($tags->get_attribute('type'), ['email', 'text'], true)) {
            $tags->add_class('footer-follow__email');
        }
    }

    return $tags->get_updated_html();
}

add_filter('render_block_hostinger-reach/subscription', __NAMESPACE__.'\\mh_reach_form_block_html');

/**
 * Signup fallback where the Hostinger Reach form block is placed but the plugin is not running.
 *
 * With the plugin off, the saved `hostinger-reach/subscription` block is not registered and prints nothing, so
 * the page body or the footer column goes blank. This swaps in a short message with a "Say hello" button and
 * the RSS feed. Nothing changes while the plugin is active, because the block is then registered.
 *
 * @since 3.18.4
 *
 * @param  string  $html  Rendered block.
 * @param  array<string, mixed>  $block  Parsed block.
 */
function mh_reach_form_fallback(mixed $html, array $block): mixed
{
    if (($block['blockName'] ?? '') !== 'hostinger-reach/subscription') {
        return $html;
    }

    if (\WP_Block_Type_Registry::get_instance()->is_registered('hostinger-reach/subscription')) {
        return $html;
    }

    return '<div class="updates-fallback">'
        .'<p>'.esc_html__('The email signup is not working right now. Say hello and I will add you to the list by hand, or follow the RSS feed.', 'sage').'</p>'
        .'<p class="updates-fallback__actions">'
        .'<a class="btn" href="'.esc_url(home_url('/contact/')).'">'.esc_html__('Say hello', 'sage').'</a> '
        .'<a class="btn btn-outline" href="'.esc_url(get_feed_link()).'" rel="alternate" type="application/rss+xml">'.esc_html__('RSS feed', 'sage').'</a>'
        .'</p>'
        .'</div>';
}

add_filter('render_block', __NAMESPACE__.'\\mh_reach_form_fallback', 10, 2);

/**
 * "On this page" links for a block page: every top-level Section with an anchor and a label.
 *
 * @return list<array{0: string, 1: string}>
 */
function mh_block_page_nav_pills(?int $post_id = null): array
{
    $post = get_post($post_id ?: (int) get_the_ID());
    if (! $post instanceof \WP_Post) {
        return [];
    }

    $pills = [];
    foreach (parse_blocks((string) $post->post_content) as $block) {
        if (($block['blockName'] ?? '') !== 'matthummel/section') {
            continue;
        }
        $anchor = sanitize_html_class((string) ($block['attrs']['anchor'] ?? ''));
        $label = trim((string) ($block['attrs']['navLabel'] ?? ''));
        if ($anchor !== '' && $label !== '') {
            $pills[] = [$anchor, $label];
        }
    }

    return $pills;
}

/**
 * Icon choices for the Card block (key, label, inline SVG for the picker).
 *
 * @return list<array{value: string, label: string, svg: string}>
 */
function mh_page_block_icon_choices(): array
{
    $keys = ['wordpress', 'plugins', 'code', 'sage', 'php', 'javascript', 'tailwind', 'vite', 'git', 'github', 'mail', 'pen', 'check', 'briefcase', 'globe', 'map', 'calendar', 'users', 'chart-bar', 'star', 'cursor-ai', 'claude'];
    $out = [];
    foreach ($keys as $key) {
        $svg = mh_svg_icon($key, 18);
        if ($svg !== '') {
            $out[] = ['value' => $key, 'label' => ucwords(str_replace('-', ' ', $key)), 'svg' => $svg];
        }
    }

    return $out;
}

add_action('admin_head', function (): void {
    if (! function_exists('get_current_screen') || ! get_current_screen()?->is_block_editor()) {
        return;
    }

    printf(
        '<script>window.mhPageBlocks = %s;</script>'."\n",
        wp_json_encode(['icons' => mh_page_block_icon_choices(), 'dataBlocks' => mh_data_blocks_for_editor()])
    );
}, 1);

/*
 * ── Building block markup in PHP ────────────────────────────────────────────
 * Used by the one-time converters that move a page's saved field copy into
 * blocks. The HTML matches what each core block's save() prints, so the editor
 * opens converted pages without "unexpected or invalid content" notices.
 * Text arguments are plain text unless the name says HTML.
 */

/**
 * Wrap inner markup in a block comment.
 *
 * @param  string  $name  Block name, e.g. "matthummel/section" or "core/buttons".
 * @param  array<string, mixed>  $attrs  Block attributes; empty values are left out.
 * @param  string|null  $inner  Inner markup, or null for a self-closing block.
 */
function mh_blocks_wrap(string $name, array $attrs = [], ?string $inner = ''): string
{
    $attrs = array_filter($attrs, static fn ($value) => $value !== '' && $value !== null && $value !== false && $value !== []);
    $short = str_starts_with($name, 'core/') ? substr($name, 5) : $name;
    $json = $attrs === [] ? '' : ' '.serialize_block_attributes($attrs);
    if ($inner === null) {
        return "<!-- wp:{$short}{$json} /-->\n\n";
    }

    return "<!-- wp:{$short}{$json} -->\n".rtrim($inner)."\n<!-- /wp:{$short} -->\n\n";
}

/** Core Heading. */
function mh_blocks_heading(string $text, int $level = 2): string
{
    $level = min(6, max(1, $level));

    return mh_blocks_wrap('core/heading', $level === 2 ? [] : ['level' => $level], sprintf('<h%1$d class="wp-block-heading">%2$s</h%1$d>', $level, esc_html($text)));
}

/**
 * Core Paragraph. `$html` may hold inline HTML (links, strong, em).
 *
 * @param  string  $style  Block style name, e.g. "lead", or an empty string.
 */
function mh_blocks_paragraph(string $html, string $style = ''): string
{
    $html = trim(wp_kses($html, ['a' => ['href' => true, 'rel' => true, 'target' => true], 'strong' => [], 'em' => [], 'code' => [], 'br' => []]));
    if ($html === '') {
        return '';
    }
    $class = $style !== '' ? 'is-style-'.sanitize_html_class($style) : '';

    return mh_blocks_wrap('core/paragraph', ['className' => $class], $class !== '' ? '<p class="'.$class.'">'.$html.'</p>' : '<p>'.$html.'</p>');
}

/** Several paragraphs from text with blank lines or <p> tags between them. */
function mh_blocks_paragraphs(string $html, string $firstStyle = ''): string
{
    $parts = preg_split('#</p>\s*<p[^>]*>|\n\s*\n#', trim((string) preg_replace('#^\s*<p[^>]*>|</p>\s*$#', '', trim($html)))) ?: [];
    $out = '';
    foreach ($parts as $i => $part) {
        $out .= mh_blocks_paragraph($part, $i === 0 ? $firstStyle : '');
    }

    return $out;
}

/**
 * Core Buttons.
 *
 * @param  list<array{text: string, url: string, style?: string}>  $buttons  `style` is "", "outline", "ghost" or "arrow".
 */
function mh_blocks_buttons(array $buttons): string
{
    $inner = '';
    foreach ($buttons as $button) {
        $text = trim((string) ($button['text'] ?? ''));
        $url = trim((string) ($button['url'] ?? ''));
        if ($text === '' || $url === '') {
            continue;
        }
        $style = sanitize_html_class((string) ($button['style'] ?? ''));
        $class = $style !== '' ? 'is-style-'.$style : '';
        $inner .= mh_blocks_wrap(
            'core/button',
            ['className' => $class],
            '<div class="wp-block-button'.($class !== '' ? ' '.$class : '').'"><a class="wp-block-button__link wp-element-button" href="'.esc_url($url).'">'.esc_html($text).'</a></div>'
        );
    }

    return $inner === '' ? '' : mh_blocks_wrap('core/buttons', [], '<div class="wp-block-buttons">'.rtrim($inner).'</div>');
}

/**
 * Core List.
 *
 * @param  list<string>  $items  Plain-text items.
 * @param  string  $style  Block style name, e.g. "checklist", or an empty string.
 */
function mh_blocks_list(array $items, string $style = ''): string
{
    $inner = '';
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $inner .= mh_blocks_wrap('core/list-item', [], '<li>'.esc_html($item).'</li>');
        }
    }
    if ($inner === '') {
        return '';
    }
    $class = $style !== '' ? 'is-style-'.sanitize_html_class($style) : '';

    return mh_blocks_wrap('core/list', ['className' => $class], '<ul class="wp-block-list'.($class !== '' ? ' '.$class : '').'">'.rtrim($inner).'</ul>');
}

/**
 * A Questions block from question/answer rows.
 *
 * @param  list<array{title: string, text: string}>  $rows  Rows as stored by the FAQ repeaters.
 * @param  array<string, mixed>  $attrs  Block attributes. `schema` is legacy and unused: the SEO plugin owns the page's structured data.
 */
function mh_blocks_faq(array $rows, array $attrs = []): string
{
    $inner = '';
    foreach ($rows as $i => $row) {
        $question = trim((string) ($row['title'] ?? ''));
        $answer = mh_blocks_paragraphs((string) ($row['text'] ?? ''));
        if ($question === '' || $answer === '') {
            continue;
        }
        $open = $i === 0;
        $inner .= mh_blocks_wrap(
            'core/details',
            ['showContent' => $open],
            '<details class="wp-block-details"'.($open ? ' open' : '').'><summary>'.esc_html($question).'</summary>'.rtrim($answer).'</details>'
        );
    }

    return $inner === '' ? '' : mh_blocks_wrap('matthummel/faq', $attrs, $inner);
}

/**
 * A Card grid from rows with icon/title/body (the About and Home card repeaters).
 *
 * @param  list<array<string, string>>  $rows  Rows with `title` and `body` (or `text`), and optionally `icon`.
 */
function mh_blocks_cards(array $rows, int $columns = 3, bool $numbered = false): string
{
    $inner = '';
    foreach ($rows as $row) {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $inner .= mh_blocks_wrap(
            'matthummel/card',
            ['icon' => sanitize_key((string) ($row['icon'] ?? ''))],
            mh_blocks_heading($title, 3).mh_blocks_paragraphs((string) ($row['body'] ?? $row['text'] ?? ''))
        );
    }

    return $inner === '' ? '' : mh_blocks_wrap('matthummel/cards', ['columns' => $columns === 3 ? null : $columns, 'numbered' => $numbered], $inner);
}

/**
 * Store block markup as a page's content and mark the page as block-built.
 *
 * The page's mh_f_* field values are not touched; they stay as a backup.
 * Nothing is written when the page already has content, unless `$force` is set.
 */
function mh_save_page_blocks(int $post_id, string $markup, bool $force = false): bool
{
    $post = get_post($post_id);
    if (! $post instanceof \WP_Post || $post->post_type !== 'page' || trim($markup) === '') {
        return false;
    }
    if (! $force && trim((string) $post->post_content) !== '') {
        return false;
    }

    // Flag first: saving the post can fetch the page (the SEO score does), and that request must see a block page.
    update_post_meta($post_id, MH_BLOCK_PAGE_META, '1');
    $result = wp_update_post(['ID' => $post_id, 'post_content' => wp_slash(trim($markup)."\n")], true);
    if (is_wp_error($result)) {
        delete_post_meta($post_id, MH_BLOCK_PAGE_META);

        return false;
    }

    return true;
}

/**
 * Move a field page to blocks: save the markup, switch it to the Block page template,
 * and remember which field template it came from.
 *
 * The front page and the posts page keep their template (WordPress picks those by role).
 *
 * @param  string  $from  Field template the page used, e.g. "template-about.blade.php".
 */
function mh_convert_page_to_blocks(int $post_id, string $markup, string $from): bool
{
    $old = (string) get_post_meta($post_id, '_wp_page_template', true);
    $isRolePage = in_array($post_id, [(int) get_option('page_on_front'), (int) get_option('page_for_posts')], true);

    // Switch the template before saving. The old template file may be archived already, and
    // wp_update_post() stops with "invalid_page_template" when a page points at a file that is gone.
    update_post_meta($post_id, '_wp_page_template', $isRolePage ? 'default' : MH_BLOCK_PAGE_TEMPLATE);
    update_post_meta($post_id, MH_BLOCK_PAGE_FROM_META, (string) preg_replace('/[^a-z0-9.\-]/', '', strtolower($from)));

    if (mh_save_page_blocks($post_id, $markup)) {
        return true;
    }

    // Nothing was saved: put the page back as it was.
    update_post_meta($post_id, '_wp_page_template', $old !== '' ? $old : 'default');
    delete_post_meta($post_id, MH_BLOCK_PAGE_FROM_META);

    return false;
}
