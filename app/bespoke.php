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
    if (get_option('mh_cleared_page_blocks_v3')) {
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
        $content = (string) get_post_field('post_content', $id, 'raw');
        if ($content === '') {
            continue;
        }
        wp_update_post([
            'ID' => $id,
            'post_content' => '',
        ]);
    }

    update_option('mh_cleared_page_blocks_v3', true);
}

add_action('init', __NAMESPACE__.'\\mh_clear_page_block_content', 45);

/**
 * Drop saved page fields that still lead with Power Platform-as-day-job copy
 * so theme defaults (full-stack: WordPress, plugins, other web apps) show.
 */
function mh_reset_power_platform_focus_copy(): void
{
    if (get_option('mh_copy_fullstack_v1')) {
        return;
    }

    $needles = [
        'power platform by day',
        'full-time power platform',
        'full-time power apps',
        'i build power apps and wordpress',
        'power apps, power automate, and wordpress',
        'power apps, automate, and wordpress',
        'power apps, automate, and custom wordpress',
        'wordpress and powerplatform',
        'wordpress and power platform',
        'power platform / wordpress',
        'i write about power platform',
        'power platform, wordpress, git',
        'power platform, wordpress, and the messy',
        'power apps, power automate, wordpress',
        'instead of a spreadsheet',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $all = get_post_meta($id);
        if (! is_array($all)) {
            continue;
        }
        foreach ($all as $key => $values) {
            if (! is_string($key) || ! str_starts_with($key, 'mh_f_')) {
                continue;
            }
            $raw = $values[0] ?? '';
            $val = maybe_unserialize($raw);
            $blob = strtolower(is_array($val) ? (string) wp_json_encode($val) : (string) $val);
            $drop = false;
            foreach ($needles as $needle) {
                if (str_contains($blob, $needle)) {
                    $drop = true;
                    break;
                }
            }
            if (! $drop && $key === 'mh_f_home_stack' && is_array($val)) {
                $first = strtolower(trim((string) ($val[0] ?? '')));
                if (str_starts_with($first, 'power')) {
                    $drop = true;
                }
            }
            if (! $drop && $key === 'mh_f_svc_items' && is_array($val)) {
                $firstTitle = strtolower((string) ($val[0]['title'] ?? ''));
                if (str_contains($firstTitle, 'power')) {
                    $drop = true;
                }
            }
            if ($drop) {
                delete_post_meta($id, $key);
            }
        }
    }

    $tagline = strtolower((string) get_option('blogdescription', ''));
    foreach ($needles as $needle) {
        if (str_contains($tagline, $needle)) {
            update_option('blogdescription', 'Full-stack developer. WordPress, plugins, and other web apps.');
            break;
        }
    }

    update_option('mh_copy_fullstack_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_reset_power_platform_focus_copy', 46);

/** Apply SEO playbook copy swaps without wiping custom fields. */
function mh_apply_seo_playbook_copy(): void
{
    if (get_option('mh_seo_playbook_v1')) {
        return;
    }

    $swaps = [
        'mh_f_home_lede' => [
            'I build WordPress sites, plugins, and other web apps. Shops get a site they can edit. Developers can copy the code. I still do some Power Platform work when it helps.' => 'I build WordPress sites, plugins, and other web apps in Gettysburg. Shops get a site they can edit. Developers can copy the code.',
        ],
        'mh_f_svc_lede' => [
            'Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app, you can write. I take a few extra projects at a time. I still do some Power Platform work, but it is not my main focus.' => 'Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app in Gettysburg, you can write. I take a few extra projects; Power Platform is not my main focus.',
            'Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app, you can write. I take a few extra projects at a time. I still do some Power Platform work. It is not my main focus.' => 'Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app in Gettysburg, you can write. I take a few extra projects; Power Platform is not my main focus.',
        ],
        'mh_f_work_lede' => [
            'Studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Business owners can picture a real WordPress shape. Developers can see how the pieces fit. Agencies can use them as a reference when a shop needs a local site.' => 'Studio concepts for Gettysburg tours, inns, shops, and restaurants. Shops can picture a WordPress site they can run. Developers can see how the pieces fit in Gettysburg and Adams County.',
            'Studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Business owners can picture a real WordPress shape. Developers can see how the pieces fit. Agencies can use them as a reference when a client needs a local site.' => 'Studio concepts for Gettysburg tours, inns, shops, and restaurants. Shops can picture a WordPress site they can run. Developers can see how the pieces fit in Gettysburg and Adams County.',
        ],
        'mh_f_cnt_lede' => [
            'Questions about a post, a snippet, or GitHub are welcome. So is a note about a WordPress site, a plugin, or another web app. I usually reply in one or two business days.' => 'Questions about a post, a snippet, or GitHub are welcome. So is a note about a WordPress site in Gettysburg. I usually reply in one or two business days.',
        ],
        'mh_f_about_lede' => [
            'I’m Matt. I live in Gettysburg, Pennsylvania. I write about the web, share code, and sometimes help a shop, a team, or an agency with a WordPress site, a plugin, or another web app. Plain language, and pages that are easy to use.' => 'I’m Matt. I live in Gettysburg, Pennsylvania, and I write about the web, share code, and sometimes help a shop, a team, or an agency with a WordPress site. Plain language, and pages that are easy to use.',
            'I’m Matt. I live in Gettysburg, Pennsylvania. I write about the web, share code, and sometimes help a shop, a team, or an agency with a WordPress site, a plugin, or another web app. Plain language. Pages that are easy to use.' => 'I’m Matt. I live in Gettysburg, Pennsylvania, and I write about the web, share code, and sometimes help a shop, a team, or an agency with a WordPress site. Plain language, and pages that are easy to use.',
        ],
        'mh_f_svc_fair' => [
            'I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.' => 'I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.',
            'I don’t run ads or social accounts for clients. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.' => 'I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        foreach ($swaps as $key => $pairs) {
            $val = get_post_meta($id, $key, true);
            if (! is_string($val) || $val === '') {
                continue;
            }
            foreach ($pairs as $from => $to) {
                if ($val === $from) {
                    update_post_meta($id, $key, $to);
                    break;
                }
            }
        }

        $who = get_post_meta($id, 'mh_f_who_items', true);
        if (is_array($who)) {
            $changed = false;
            foreach ($who as $i => $row) {
                $text = (string) ($row['text'] ?? '');
                if (str_contains($text, 'a client needs')) {
                    $who[$i]['text'] = 'When a shop needs a WordPress site, a plugin, or another web app, and you want a developer who writes things down.';
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_who_items', $who);
            }
        }

        $faq = get_post_meta($id, 'mh_f_svc_faq', true);
        if (is_array($faq)) {
            $changed = false;
            foreach ($faq as $i => $row) {
                $text = (string) ($row['text'] ?? '');
                if (str_contains($text, 'You keep the client.')) {
                    $whoNew = str_replace('You keep the client.', 'You keep the relationship.', $text);
                    $faq[$i]['text'] = $whoNew;
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_svc_faq', $faq);
            }
        }
    }

    update_option('mh_seo_playbook_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_seo_playbook_copy', 47);

/** One-time Code page copy: showcase + resume, only exact old defaults. */
function mh_apply_code_showcase_copy(): void
{
    if (get_option('mh_code_showcase_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_kicker' => [
            'Code' => 'Engineering',
            'Public work' => 'Engineering',
        ],
        'mh_f_code_h1' => [
            'Code' => 'What I do',
            'Code you can copy.' => 'What I do',
        ],
        'mh_f_code_lede' => [
            'Repos and short snippets. If you’re new to WordPress or Sage, start with a snippet, then open a repo README. Questions are welcome on the <a href="/contact/">contact</a> page.' => 'I build and maintain WordPress sites, plugins, and other web applications from Gettysburg, Pennsylvania. Most of that work is Sage, Blade, PHP, and front-end architecture shops can keep editing. I also keep a public GitHub profile so other developers can read the same code I ship.',
            'I keep some of my code on GitHub so other people can read it. Fork a repo, copy a snippet, or write if a line is unclear.' => 'I build and maintain WordPress sites, plugins, and other web applications from Gettysburg, Pennsylvania. Most of that work is Sage, Blade, PHP, and front-end architecture shops can keep editing. I also keep a public GitHub profile so other developers can read the same code I ship.',
        ],
        'mh_f_code_feat_h2' => [
            'Featured repos' => 'Featured repositories',
        ],
        'mh_f_code_feat_intro' => [
            'Open these on GitHub. Fork them if they help.' => 'Three codebases I point people to first: a full-stack app, a WordPress plugin, and a Sage theme.',
        ],
        'mh_f_code_live_h2' => [
            'Live from GitHub' => 'Recently updated',
        ],
        'mh_f_code_live_all' => [
            'All public repos' => 'All public repositories',
        ],
        'mh_f_code_snip_h2' => [
            'Snippets' => 'Reusable snippets',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        foreach ($swaps as $key => $pairs) {
            $val = get_post_meta($id, $key, true);
            if (! is_string($val) || $val === '') {
                continue;
            }
            foreach ($pairs as $from => $to) {
                if ($val === $from) {
                    update_post_meta($id, $key, $to);
                    break;
                }
            }
        }

        $repos = get_post_meta($id, 'mh_f_code_repos', true);
        if (is_array($repos)) {
            $map = [
                'A private family app. Sign-in, invites, and posts. You can read the code.' => 'Private family app: authentication, invites, and posts. React on the front end, Supabase for data and sign-in.',
                'A free WordPress tool that lists your headings so people can jump around a long page.' => 'WordPress plugin that builds a table of contents from heading blocks. PHP, Gutenberg, and a small public API.',
                'The Gettysburg studio site. Clear pages for local shops, inns, and tours.' => 'Sage 11 theme for the Gettysburg studio site. Blade templates, local SEO, and pages shops can edit.',
            ];
            $changed = false;
            foreach ($repos as $i => $row) {
                $desc = (string) ($row['desc'] ?? '');
                if (isset($map[$desc])) {
                    $repos[$i]['desc'] = $map[$desc];
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_code_repos', $repos);
            }
        }
    }

    update_option('mh_code_showcase_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_showcase_copy', 48);

/** Repeater fields for audience cards (Home, About, Services). */
function mh_who_item_fields(): array
{
    return [
        ['title', __('Title', 'sage'), 'text'],
        ['text', __('Text', 'sage'), 'textarea'],
        ['icon', __('Icon key', 'sage'), 'text'],
        ['href', __('Link', 'sage'), 'url'],
        ['cta', __('Link label', 'sage'), 'text'],
    ];
}

/** Who this site is for — reused on Home, About, Services. */
function mh_who_items(): array
{
    $writing = get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/');

    return [
        [
            'title' => __('Developers', 'sage'),
            'text' => __('Copy the code. Fork a repo. Ask if a line is unclear.', 'sage'),
            'icon' => 'code',
            'href' => home_url('/code/'),
            'cta' => __('Browse the code', 'sage'),
        ],
        [
            'title' => __('People learning', 'sage'),
            'text' => __('Short notes. Tiny examples. Pages that work on a phone.', 'sage'),
            'icon' => 'book-open',
            'href' => $writing,
            'cta' => __('Read the writing', 'sage'),
        ],
        [
            'title' => __('Shops and teams', 'sage'),
            'text' => __('A WordPress site you can edit, a plugin, or another web app that fits the work.', 'sage'),
            'icon' => 'briefcase',
            'href' => home_url('/services/'),
            'cta' => __('See how I can help', 'sage'),
        ],
        [
            'title' => __('Agencies', 'sage'),
            'text' => __('When a shop needs a WordPress site, a plugin, or another web app, and you want a developer who writes things down.', 'sage'),
            'icon' => 'users',
            'href' => home_url('/contact/'),
            'cta' => __('Say hello', 'sage'),
        ],
    ];
}

/** Audience cards with saved copy filled in from theme defaults (icon, link, CTA). */
function mh_who_page_items(?int $post_id = null): array
{
    $defaults = mh_who_items();
    $rows = field_rows('who_items', $defaults, $post_id);
    $out = [];

    foreach ($rows as $i => $row) {
        $row = is_array($row) ? $row : [];
        $base = $defaults[$i] ?? [];
        $pick = static function (string $key) use ($row, $base): string {
            $val = trim((string) ($row[$key] ?? ''));

            return $val !== '' ? $val : (string) ($base[$key] ?? '');
        };

        $out[] = [
            'title' => $pick('title'),
            'text' => $pick('text'),
            'icon' => $pick('icon') ?: 'code',
            'href' => $pick('href'),
            'cta' => $pick('cta'),
        ];
    }

    return $out !== [] ? $out : $defaults;
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
