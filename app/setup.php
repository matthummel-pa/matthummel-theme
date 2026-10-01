<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'), true);

        if (is_array($dependencies)) {
            foreach ($dependencies as $dependency) {
                if (! wp_script_is($dependency)) {
                    wp_enqueue_script($dependency);
                }
            }
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'footer_navigation' => __('Footer Navigation', 'sage'),
        'footer_bottom_navigation' => __('Footer Bottom Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');
    add_filter('should_load_remote_block_patterns', '__return_false');
    add_filter('should_load_separate_core_block_assets', '__return_true');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');
    add_theme_support('automatic-feed-links');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});

/**
 * Local hostnames that may override home/siteurl. Never includes production.
 *
 * @return list<string>
 */
function mh_local_dev_hosts(): array
{
    return [
        'matthummel-theme.local',
        'localhost',
        '127.0.0.1',
    ];
}

/**
 * Public URL for this request when it is a local-dev host, otherwise null.
 */
function mh_local_dev_public_url(): ?string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return null;
    }

    $name = explode(':', $host, 2)[0];
    if (! in_array($name, mh_local_dev_hosts(), true)) {
        return null;
    }

    $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';

    return $scheme.'://'.$host;
}

add_filter('option_home', function ($value) {
    return mh_local_dev_public_url() ?? $value;
});

add_filter('option_siteurl', function ($value) {
    return mh_local_dev_public_url() ?? $value;
});

/**
 * Privacy policy URL from the WordPress setting, then the theme template, then /privacy/.
 */
function mh_privacy_policy_url(): string
{
    if (function_exists('get_privacy_policy_url')) {
        $fromSetting = get_privacy_policy_url();
        if (is_string($fromSetting) && $fromSetting !== '') {
            return $fromSetting;
        }
    }

    return mh_published_page_url('template-privacy.blade.php', 'privacy');
}

/**
 * Terms URL from the theme template, then /terms/.
 */
function mh_terms_url(): string
{
    return mh_published_page_url('template-terms.blade.php', 'terms');
}

/**
 * Permalink for a published page chosen by template file, then by slug.
 */
function mh_published_page_url(string $template, string $slug): string
{
    static $cache = [];

    $key = $template.'|'.$slug;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $byTemplate = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'no_found_rows' => true,
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
        'meta_key' => '_wp_page_template',
        'meta_value' => $template,
    ]);
    $url = mh_permalink_from_posts($byTemplate);
    if ($url === '') {
        $page = get_page_by_path(sanitize_title($slug));
        if ($page instanceof \WP_Post && $page->post_status === 'publish') {
            $permalink = get_permalink($page);
            $url = is_string($permalink) ? $permalink : '';
        }
    }
    if ($url === '') {
        $url = home_url('/'.sanitize_title($slug).'/');
    }

    $cache[$key] = $url;

    return $url;
}

/**
 * @param  list<\WP_Post>|array<int, mixed>  $posts
 */
function mh_permalink_from_posts(array $posts): string
{
    $page = $posts[0] ?? null;
    if (! $page instanceof \WP_Post) {
        return '';
    }

    $permalink = get_permalink($page);

    return is_string($permalink) ? $permalink : '';
}
