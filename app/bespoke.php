<?php

/**
 * Pages are custom-field layouts only — no Gutenberg canvas, no patterns,
 * no leftover block HTML in post_content.
 */

namespace App;

/** Pages never use the block editor. Posts keep it for the blog. */
add_filter('use_block_editor_for_post_type', function ($use, $type) {
    return $type === 'page' ? false : $use;
}, 10, 2);

add_action('init', function () {
    remove_post_type_support('page', 'editor');
    remove_post_type_support('page', 'block-templates');
}, 11);

/** No pattern inserter, local or remote. */
add_action('init', function () {
    remove_theme_support('core-block-patterns');
    if (function_exists('unregister_block_pattern_category')) {
        foreach (['featured', 'buttons', 'columns', 'gallery', 'header', 'text', 'query', 'theme', 'uncategorized'] as $cat) {
            unregister_block_pattern_category($cat);
        }
    }
    if (class_exists(\WP_Block_Patterns_Registry::class)) {
        foreach (\WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            if (! empty($pattern['name'])) {
                unregister_block_pattern($pattern['name']);
            }
        }
    }
}, 99);

add_filter('should_load_remote_block_patterns', '__return_false');
add_filter('block_editor_settings_all', function ($settings) {
    $settings['__experimentalBlockPatterns'] = [];
    $settings['__experimentalBlockPatternCategories'] = [];
    $settings['enableOpenverseMediaCategory'] = false;

    return $settings;
});

/** Skip core block stylesheet on custom-field pages (faster, no FSE leftovers). */
add_action('wp_enqueue_scripts', function () {
    if (is_singular('post') || is_singular('attachment')) {
        return;
    }
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');
}, 100);

/**
 * Empty leftover Gutenberg HTML on theme-templated pages only.
 * Custom fields (mh_f_*) stay. Blog posts, privacy policy, and
 * untemplated pages are not touched.
 */
function mh_clear_page_block_content(): void
{
    if (get_option('mh_cleared_page_blocks_v2')) {
        return;
    }

    $skip = array_filter([
        (int) get_option('wp_page_for_privacy_policy'),
    ]);

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        if (in_array($id, $skip, true)) {
            continue;
        }
        if (empty(page_field_map()[page_template_key($id)])) {
            continue;
        }
        $content = (string) get_post_field('post_content', $id, 'raw');
        if ($content === '') {
            continue;
        }
        wp_update_post([
            'ID' => $id,
            'post_content' => '',
        ]);
    }

    update_option('mh_cleared_page_blocks_v2', true);
}

add_action('init', __NAMESPACE__.'\\mh_clear_page_block_content', 45);

/** Who this site is for — reused on Home, About, Services. */
function mh_who_items(): array
{
    return [
        [
            'title' => __('Developers', 'sage'),
            'text' => __('Fork the repos, copy the snippets, ask about a line. Sage, PHP, React, and Power Platform notes without a course funnel.', 'sage'),
        ],
        [
            'title' => __('People learning the web', 'sage'),
            'text' => __('Plain words, short examples, and pages that work on a phone and a keyboard. Start on Code, then read a post.', 'sage'),
        ],
        [
            'title' => __('Shops and teams', 'sage'),
            'text' => __('WordPress you can edit, or a small Power App instead of another spreadsheet. I take a few of these beside a full-time job.', 'sage'),
        ],
        [
            'title' => __('Marketing agencies', 'sage'),
            'text' => __('When a client needs a real WordPress build — theme, bookings, shop, accessibility — and you want a developer who writes things down.', 'sage'),
        ],
    ];
}

/** Primary + footer menus. Safe to re-run; replaces those two menus only. */
function mh_sync_nav_menus(): void
{
    if (get_option('mh_nav_synced_v3') === '3') {
        return;
    }

    $bySlug = [];
    foreach (['home', 'about', 'projects', 'services', 'code', 'contact', 'now', 'blog'] as $slug) {
        $page = get_page_by_path($slug);
        if ($page instanceof \WP_Post) {
            $bySlug[$slug] = (int) $page->ID;
        }
    }

    $build = static function (string $name, array $slugs) use ($bySlug): int {
        $menu = wp_get_nav_menu_object($name);
        $menuId = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                wp_delete_post((int) $item->ID, true);
            }
        }
        $n = 1;
        foreach ($slugs as $slug) {
            if (empty($bySlug[$slug])) {
                continue;
            }
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => get_the_title($bySlug[$slug]),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $bySlug[$slug],
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish',
                'menu-item-position' => $n++,
            ]);
        }

        return $menuId;
    };

    // Logo is Home. Contact is the header button. Keep the bar short.
    $primaryId = $build('Primary', ['projects', 'services', 'blog', 'code', 'about']);
    $footerId = $build('Footer', ['projects', 'blog', 'code', 'about', 'now', 'contact']);

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['primary_navigation'] = $primaryId;
    $locations['footer_navigation'] = $footerId;
    set_theme_mod('nav_menu_locations', $locations);

    update_option('mh_nav_synced_v3', '3');
}

add_action('init', __NAMESPACE__.'\\mh_sync_nav_menus', 50);
