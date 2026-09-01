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

    $skip = array_filter(array_merge([
        (int) get_option('wp_page_for_privacy_policy'),
    ], array_values(mh_woocommerce_page_ids())));

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
        'no_found_rows' => true,
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
        'no_found_rows' => true,
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

/**
 * Drop saved page / SEO fields that still stuff Gettysburg or Adams County
 * into marketing copy so skill-first theme defaults show.
 *
 * Demo project place labels on Work cards are left alone (factual example towns).
 */
function mh_reset_location_seo_copy(): void
{
    if (get_option('mh_seo_global_copy_v1')) {
        return;
    }

    $needles = [
        'gettysburg',
        'adams county',
    ];

    // Structured Work cards keep place strings; everything else with city stuffing clears.
    $skipKeys = [
        'mh_f_work_items',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            $raw = $values[0] ?? '';
            $val = maybe_unserialize($raw);
            $blob = strtolower(is_array($val) ? (string) wp_json_encode($val) : (string) $val);
            foreach ($needles as $needle) {
                if (str_contains($blob, $needle)) {
                    delete_post_meta($id, $key);
                    break;
                }
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

    update_option('mh_seo_global_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_reset_location_seo_copy', 48);

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
        'no_found_rows' => true,
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

/** Ridges & Valleys is current; Saliense is previous. Only known old resume rows. */
function mh_apply_code_resume_studio(): void
{
    if (get_option('mh_code_resume_studio_v1')) {
        return;
    }

    $oldIntro = 'Gettysburg, PA. Senior Power Platform consulting is the current full-time role. Independent WordPress and web work is the longer practice and the public offer on this site.';
    $newIntro = 'Gettysburg, PA. I just started Ridges & Valleys, a studio for local shops, inns, and tours. WordPress and public web work is the offer on this site. Power Platform consulting at Saliense is previous, not current.';

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $intro = get_post_meta($id, 'mh_f_code_cv_intro', true);
        if (is_string($intro) && $intro === $oldIntro) {
            update_post_meta($id, 'mh_f_code_cv_intro', $newIntro);
        }

        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (is_array($jobs) && $jobs !== []) {
            $first = $jobs[0] ?? [];
            $isOldCurrent = ((string) ($first['org'] ?? '') === 'Saliense Consulting'
                && strcasecmp((string) ($first['period'] ?? ''), 'Current') === 0);
            if ($isOldCurrent) {
                update_post_meta($id, 'mh_f_code_cv_jobs', mh_code_resume_defaults());
            }
        }

        $practice = get_post_meta($id, 'mh_f_code_do_items', true);
        if (is_array($practice)) {
            $map = [
                'Local Gettysburg and Adams County sites for shops, inns, and tours (often through Ridges & Valleys).' => 'Local Gettysburg and Adams County sites for shops, inns, and tours through Ridges & Valleys, the studio I just started.',
                'Microsoft Power Platform (Power Apps, Power Automate, Dataverse) as a secondary, full-time consulting practice.' => 'Microsoft Power Platform (Power Apps, Power Automate, Dataverse) as previous consulting work, not the public offer.',
            ];
            $changed = false;
            foreach ($practice as $i => $line) {
                $line = (string) $line;
                if (isset($map[$line])) {
                    $practice[$i] = $map[$line];
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_code_do_items', $practice);
            }
        }
    }

    update_option('mh_code_resume_studio_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_studio', 49);

/** Studio is new; still open to agencies and full-time work. */
function mh_apply_code_resume_available(): void
{
    if (get_option('mh_code_resume_available_v1')) {
        return;
    }

    $oldIntro = 'Gettysburg, PA. I just started Ridges & Valleys, a studio for local shops, inns, and tours. WordPress and public web work is the offer on this site. Power Platform consulting at Saliense is previous, not current.';
    $newIntro = 'Gettysburg, PA. I just started Ridges & Valleys, a studio for local shops, inns, and tours. I am still open to agencies, overflow work, and full-time roles. WordPress is the public offer; Power Platform at Saliense is previous.';
    $oldBullets = "Just started a Gettysburg studio for shops, inns, and tours.\nWordPress sites local businesses can edit, with concept work on the studio site.\nPublic theme and plugin work stays on GitHub so other developers can read it.";
    $newBullets = "I just started this studio. It is new, not a full book of work yet.\nWordPress sites for Gettysburg shops, inns, and tours, with concept pages on the studio site.\nI am still open to agencies, other studios, overflow work, and full-time positions.";

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $intro = get_post_meta($id, 'mh_f_code_cv_intro', true);
        if (is_string($intro) && $intro === $oldIntro) {
            update_post_meta($id, 'mh_f_code_cv_intro', $newIntro);
        }

        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            if ($org === 'Ridges & Valleys' && $bullets === $oldBullets) {
                $jobs[$i]['bullets'] = $newBullets;
                $changed = true;
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_resume_available_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_available', 50);

/** Code page: Gettysburg is home; work is not location-limited. */
function mh_apply_code_anywhere(): void
{
    if (get_option('mh_code_anywhere_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_lede' => [
            'I build and maintain WordPress sites, plugins, and other web applications from Gettysburg, Pennsylvania. Most of that work is Sage, Blade, PHP, and front-end architecture shops can keep editing. I also keep a public GitHub profile so other developers can read the same code I ship.' => 'I build and maintain WordPress sites, plugins, and other web applications from Gettysburg, Pennsylvania, for shops and agencies anywhere. Most of that work is Sage, Blade, PHP, and front-end architecture they can keep editing. I also keep a public GitHub profile so other developers can read the same code I ship.',
        ],
        'mh_f_code_cv_intro' => [
            'Gettysburg, PA. I just started Ridges & Valleys, a studio for local shops, inns, and tours. I am still open to agencies, overflow work, and full-time roles. WordPress is the public offer; Power Platform at Saliense is previous.' => 'Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. I am still open to overflow work and full-time roles. WordPress is the public offer; Power Platform at Saliense is previous.',
        ],
    ];
    $oldPractice = 'Local Gettysburg and Adams County sites for shops, inns, and tours through Ridges & Valleys, the studio I just started.';
    $newPractice = 'Ridges & Valleys is the studio I just started. I work with shops, inns, tours, and agencies in any location.';
    $oldBullets = "I just started this studio. It is new, not a full book of work yet.\nWordPress sites for Gettysburg shops, inns, and tours, with concept pages on the studio site.\nI am still open to agencies, other studios, overflow work, and full-time positions.";
    $newBullets = "I just started this studio. It is new, not a full book of work yet.\nWordPress sites for shops, inns, and tours. I am based in Gettysburg; location is not a limit.\nI am still open to agencies, other studios, overflow work, and full-time positions, remote or on-site.";

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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

        $practice = get_post_meta($id, 'mh_f_code_do_items', true);
        if (is_array($practice)) {
            $changed = false;
            foreach ($practice as $i => $line) {
                if ((string) $line === $oldPractice) {
                    $practice[$i] = $newPractice;
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_code_do_items', $practice);
            }
        }

        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            if ($org === 'Ridges & Valleys' && $bullets === $oldBullets) {
                $jobs[$i]['bullets'] = $newBullets;
                $changed = true;
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_anywhere_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_anywhere', 51);

/** Saliense was a gov contract; Germanna CC full-time ended 2020. */
function mh_apply_code_resume_employers(): void
{
    if (get_option('mh_code_resume_employers_v1')) {
        return;
    }

    $oldIntro = 'Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. I am still open to overflow work and full-time roles. WordPress is the public offer; Power Platform at Saliense is previous.';
    $newIntro = 'Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. Saliense was a government contract. Full-time web work at Germanna Community College ended in 2020.';

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $intro = get_post_meta($id, 'mh_f_code_cv_intro', true);
        if (is_string($intro) && $intro === $oldIntro) {
            update_post_meta($id, 'mh_f_code_cv_intro', $newIntro);
        }

        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs) || $jobs === []) {
            continue;
        }
        $needs = false;
        foreach ($jobs as $row) {
            $org = (string) ($row['org'] ?? '');
            $type = (string) ($row['type'] ?? '');
            if ($org === 'Higher education / independent') {
                $needs = true;
            }
            if ($org === 'Saliense Consulting' && $type === 'Full-time') {
                $needs = true;
            }
        }
        if ($needs) {
            update_post_meta($id, 'mh_f_code_cv_jobs', mh_code_resume_defaults());
        }
    }

    update_option('mh_code_resume_employers_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_employers', 52);

/** Replace thin/wrong resume rows with the LinkedIn work history. */
function mh_apply_code_resume_linkedin(): void
{
    if (get_option('mh_code_resume_linkedin_v1')) {
        return;
    }

    $newIntro = 'Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. Roles below match my LinkedIn. I am still open to agencies, overflow work, and full-time positions.';

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        $hasAng = false;
        $hasPublicProducts = false;
        if (is_array($jobs)) {
            foreach ($jobs as $row) {
                $blob = strtolower(wp_json_encode($row) ?: '');
                if (str_contains($blob, 'all native group')) {
                    $hasAng = true;
                }
                if (str_contains($blob, 'public products')) {
                    $hasPublicProducts = true;
                }
            }
        }
        if (is_array($jobs) && $jobs !== [] && (! $hasAng || $hasPublicProducts)) {
            update_post_meta($id, 'mh_f_code_cv_jobs', mh_code_resume_defaults());
        }

        $intro = get_post_meta($id, 'mh_f_code_cv_intro', true);
        if (is_string($intro) && $intro !== '' && $intro !== $newIntro) {
            update_post_meta($id, 'mh_f_code_cv_intro', $newIntro);
        }
    }

    update_option('mh_code_resume_linkedin_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_linkedin', 53);

/** Sharper Germanna bullets; department names in title case. */
function mh_apply_code_resume_germanna_copy(): void
{
    if (get_option('mh_code_resume_germanna_copy_v1')) {
        return;
    }

    $oldBullets = "Developed a responsive WordPress website, enhancing user experience.\nWorked with teams on content management.\nOptimized code for web accessibility (Section 508, WCAG 2.0).\nMigrated site to WordPress, simplifying content editing.\nUsed Google Analytics and Google Tag Manager for tracking and marketing.";
    $jobsDefault = mh_code_resume_defaults();

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            if ($org === 'Germanna Community College' && $bullets === $oldBullets) {
                foreach ($jobsDefault as $def) {
                    if (($def['org'] ?? '') === 'Germanna Community College') {
                        $jobs[$i] = $def;
                        break;
                    }
                }
                $changed = true;
            }
            if (($row['type'] ?? '') === 'Independent studio') {
                $jobs[$i]['type'] = 'Independent Studio';
                $changed = true;
            }
            if (($row['type'] ?? '') === 'Government contract') {
                $jobs[$i]['type'] = 'Government Contract';
                $changed = true;
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_resume_germanna_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_germanna_copy', 54);

/** Sharper Knowledge Capital Associates / USMC bullets. */
function mh_apply_code_resume_kca_copy(): void
{
    if (get_option('mh_code_resume_kca_copy_v1')) {
        return;
    }

    $oldBullets = "Supported SharePoint tasks, including site creation and permissions management.\nCollaborated with the SharePoint team on SharePoint Online migrations.\nDeveloped applications and workflows using PowerApps and Power Automate.\nConverted InfoPath forms to PowerApps and Designer Workflows to Power Automate.\nManaged SharePoint site views and collections as per specifications.";
    $jobsDefault = mh_code_resume_defaults();

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            if (str_contains($org, 'Knowledge Capital Associates') && $bullets === $oldBullets) {
                foreach ($jobsDefault as $def) {
                    if (str_contains((string) ($def['org'] ?? ''), 'Knowledge Capital Associates')) {
                        $jobs[$i] = $def;
                        break;
                    }
                }
                $changed = true;
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_resume_kca_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_kca_copy', 55);

/** All Native Group was a government contract; rewrite shared PowerApps bullets. */
function mh_apply_code_resume_ang_copy(): void
{
    if (get_option('mh_code_resume_ang_copy_v1')) {
        return;
    }

    $oldBullets = "Built custom PowerApps solutions for forms and workflows.\nCreated Power Automate flows to streamline processes.\nProvided technical support for SharePoint and PowerApps.\nManaged permissions and site collections.\nConverted requirements into scalable solutions.\nEnhanced system performance using user feedback.";
    $jobsDefault = mh_code_resume_defaults();

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            foreach ($jobsDefault as $def) {
                $defOrg = (string) ($def['org'] ?? '');
                if ($org === $defOrg && $bullets === $oldBullets) {
                    $jobs[$i] = $def;
                    $changed = true;
                    break;
                }
            }
            if (str_contains($org, 'All Native Group') && ($row['type'] ?? '') === 'Full-time') {
                $jobs[$i]['type'] = 'Government Contract';
                $changed = true;
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_resume_ang_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_ang_copy', 56);

/** Second pass on Saliense / All Native Group PowerApps bullets. */
function mh_apply_code_resume_pp_copy(): void
{
    if (get_option('mh_code_resume_pp_copy_v1')) {
        return;
    }

    $oldBullets = [
        "Built custom PowerApps solutions for forms and workflows.\nCreated Power Automate flows to streamline processes.\nProvided technical support for SharePoint and PowerApps.\nManaged permissions and site collections.\nConverted requirements into scalable solutions.\nEnhanced system performance using user feedback.",
        "Built PowerApps forms and workflows.\nCreated Power Automate flows that cut manual process work.\nSupported SharePoint and PowerApps for daily use.\nManaged site-collection permissions.\nTurned requirements into solutions that could scale.\nUsed user feedback to improve system performance.",
    ];
    $jobsDefault = mh_code_resume_defaults();

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $jobs = get_post_meta($id, 'mh_f_code_cv_jobs', true);
        if (! is_array($jobs)) {
            continue;
        }
        $changed = false;
        foreach ($jobs as $i => $row) {
            $org = (string) ($row['org'] ?? '');
            $raw = $row['bullets'] ?? '';
            $bullets = is_array($raw) ? implode("\n", array_map('strval', $raw)) : (string) $raw;
            if (! in_array($bullets, $oldBullets, true)) {
                continue;
            }
            foreach ($jobsDefault as $def) {
                if ($org === (string) ($def['org'] ?? '')) {
                    $jobs[$i] = $def;
                    $changed = true;
                    break;
                }
            }
        }
        if ($changed) {
            update_post_meta($id, 'mh_f_code_cv_jobs', $jobs);
        }
    }

    update_option('mh_code_resume_pp_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_resume_pp_copy', 57);

/** Code page: richer open-source GitHub section copy (exact old defaults only). */
function mh_apply_code_gh_oss_copy(): void
{
    if (get_option('mh_code_gh_oss_copy_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_gh_h2' => [
            'GitHub.' => 'Open-source WordPress code on GitHub.',
            'GitHub' => 'Open-source WordPress code on GitHub.',
        ],
        'mh_f_code_cal_h2' => [
            'Contribution activity' => 'A year of public commits',
        ],
        'mh_f_code_cal_intro' => [
            'Public contributions over the last year. Darker cells are busier days.' => 'Contribution heat map for the last twelve months. Darker blue means a busier day on public repos.',
        ],
        'mh_f_code_act_h2' => [
            'Recent activity' => 'What shipped lately',
        ],
        'mh_f_code_feat_h2' => [
            'Featured repositories' => 'Featured WordPress and app repos',
            'Repos worth opening first' => 'Featured WordPress and app repos',
        ],
        'mh_f_code_feat_intro' => [
            'Three codebases I point people to first: a full-stack app, a WordPress plugin, and a Sage theme.' => 'Three public codebases I point developers to first: a React app, a WordPress plugin, and the Sage theme behind my Gettysburg studio. Each one is meant to be forked.',
            'Three codebases I point people to first: a full-stack app, a WordPress plugin, and the Sage theme behind this site.' => 'Three public codebases I point developers to first: a React app, a WordPress plugin, and the Sage theme behind my Gettysburg studio. Each one is meant to be forked.',
        ],
        'mh_f_code_live_h2' => [
            'Recently updated' => 'Recently pushed',
        ],
        'mh_f_code_live_intro' => [
            'Latest public updates across my GitHub account — useful if you want to see what I am actively touching.' => 'Fresh commits on public GitHub repos — a quick read on what I am shipping from Gettysburg this week.',
        ],
        'mh_f_code_live_all' => [
            'All public repos' => 'Browse all public repos',
            'All public repositories' => 'Browse all public repos',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_code_gh_oss_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_gh_oss_copy', 58);

/** Code page: contribution calendar is last 30 days, newest first (exact old defaults only). */
function mh_apply_code_gh_cal_30d(): void
{
    if (get_option('mh_code_gh_cal_30d_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_cal_h2' => [
            'A year of public commits' => 'Last 30 days of commits',
            'Contribution activity' => 'Last 30 days of commits',
        ],
        'mh_f_code_cal_intro' => [
            'Contribution heat map for the last twelve months. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 30 days, newest week first. Darker blue means a busier day on public repos.',
            'Public contributions over the last year. Darker cells are busier days.' => 'Contribution heat map for the last 30 days, newest week first. Darker blue means a busier day on public repos.',
        ],
        'mh_f_code_act_intro' => [
            'Pushes, releases, and pull requests from the public timeline.' => 'Pushes, releases, and pull requests from the last 30 days — newest first.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_code_gh_cal_30d_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_gh_cal_30d', 59);

/** Code page: contribution calendar is last 60 days (from the prior 30-day defaults). */
function mh_apply_code_gh_cal_60d(): void
{
    if (get_option('mh_code_gh_cal_60d_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_cal_h2' => [
            'Last 30 days of commits' => 'Last 60 days of commits',
            'A year of public commits' => 'Last 60 days of commits',
            'Contribution activity' => 'Last 60 days of commits',
        ],
        'mh_f_code_cal_intro' => [
            'Contribution heat map for the last 30 days, newest week first. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 60 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
            'Contribution heat map for the last twelve months. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 60 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
            'Public contributions over the last year. Darker cells are busier days.' => 'Contribution heat map for the last 60 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
        ],
        'mh_f_code_act_intro' => [
            'Pushes, releases, and pull requests from the last 30 days — newest first.' => 'Pushes, releases, and pull requests from the last 60 days — newest first.',
            'Pushes, releases, and pull requests from the public timeline.' => 'Pushes, releases, and pull requests from the last 60 days — newest first.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_code_gh_cal_60d_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_gh_cal_60d', 60);

/** Code page: contribution calendar is last 90 days (from the prior 60-day defaults). */
function mh_apply_code_gh_cal_90d(): void
{
    if (get_option('mh_code_gh_cal_90d_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_cal_h2' => [
            'Last 60 days of commits' => 'Last 90 days of commits',
            'Last 30 days of commits' => 'Last 90 days of commits',
            'A year of public commits' => 'Last 90 days of commits',
            'Contribution activity' => 'Last 90 days of commits',
        ],
        'mh_f_code_cal_intro' => [
            'Contribution heat map for the last 60 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
            'Contribution heat map for the last 30 days, newest week first. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
            'Contribution heat map for the last twelve months. Darker blue means a busier day on public repos.' => 'Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
            'Public contributions over the last year. Darker cells are busier days.' => 'Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.',
        ],
        'mh_f_code_act_intro' => [
            'Pushes, releases, and pull requests from the last 60 days — newest first.' => 'Pushes, releases, and pull requests from the last 90 days — newest first.',
            'Pushes, releases, and pull requests from the last 30 days — newest first.' => 'Pushes, releases, and pull requests from the last 90 days — newest first.',
            'Pushes, releases, and pull requests from the public timeline.' => 'Pushes, releases, and pull requests from the last 90 days — newest first.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_code_gh_cal_90d_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_gh_cal_90d', 61);

/** Code page: section copy / SEO-friendly defaults for the open-source redesign. */
function mh_apply_code_section_boost_v1(): void
{
    if (get_option('mh_code_section_boost_v1')) {
        return;
    }

    $swaps = [
        'mh_f_code_lede' => [
            'Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first. Resume and skill chips below.' => 'Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first.',
            'Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first. Resume and skills below.' => 'Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first.',
        ],
        'mh_f_code_do_intro' => [
            'WordPress is the main focus. Most projects are Sage, PHP, and front-end work that clients can keep editing after I hand off. I also write React apps and do Power Platform work when a team lives in Microsoft 365.' => 'WordPress development from Gettysburg is the main focus — Sage themes, plugins, and front-end work shops can edit after handoff. I also ship React apps and do Power Platform consulting when a team lives in Microsoft 365.',
            'WordPress is the public focus. I also write React apps and do some Microsoft Power Platform work when a team already lives in that stack.' => 'WordPress development from Gettysburg is the main focus — Sage themes, plugins, and front-end work shops can edit after handoff. I also ship React apps and do Power Platform consulting when a team lives in Microsoft 365.',
            'WordPress is the main focus from my Gettysburg studio. Most projects are Sage, PHP, and front-end work shops can keep editing after I hand off. I also write React apps and do Power Platform work when a team lives in Microsoft 365.' => 'WordPress development from Gettysburg is the main focus — Sage themes, plugins, and front-end work shops can edit after handoff. I also ship React apps and do Power Platform consulting when a team lives in Microsoft 365.',
        ],
        'mh_f_code_gh_intro' => [
            'Public repos from my Gettysburg studio — Sage themes, WordPress plugins, and other web apps shops and developers can fork. Live stats pull from the GitHub API.' => 'Public repos from my Gettysburg studio — Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats and activity below pull live from the GitHub API.',
        ],
        'mh_f_code_act_h2' => [
            'What shipped lately' => 'Public activity',
        ],
        'mh_f_code_act_intro' => [
            'Pushes, releases, and pull requests from the last 90 days — newest first.' => 'Pushes, releases, and pull requests from the last 90 days — newest first. Open any row to jump into the repo.',
        ],
        'mh_f_code_feat_h2' => [
            'Repos worth opening first' => 'Featured WordPress and app repos',
        ],
        'mh_f_code_sk_h2' => [
            'Skills' => 'Skills and tools.',
        ],
        'mh_f_code_feat_intro' => [
            'Three codebases I point people to first: a full-stack app, a WordPress plugin, and the Sage theme behind this site.' => 'Three public codebases I point developers to first: a React app, a WordPress plugin, and the Sage theme behind my Gettysburg studio. Each one is meant to be forked.',
        ],
        'mh_f_code_live_intro' => [
            'Latest public updates across my GitHub account — useful if you want to see what I am actively touching.' => 'Fresh commits on public GitHub repos — a quick read on what I am shipping from Gettysburg this week.',
        ],
        'mh_f_code_live_all' => [
            'All public repositories' => 'Browse all public repos',
        ],
        'mh_f_code_sk_intro' => [
            'Tools I use on shipped work. Icons match the brands other developers already recognize.' => 'WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos from Gettysburg. Jump a shelf — not an exhaustive list, just what shows up in public GitHub.',
            'Tools I reach for on shipped WordPress and web work. Not an exhaustive list — just what shows up in real repos.' => 'WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos from Gettysburg. Jump a shelf — not an exhaustive list, just what shows up in public GitHub.',
            'Tools I reach for on shipped work. Not an exhaustive list — just the things I actually use.' => 'WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos from Gettysburg. Jump a shelf — not an exhaustive list, just what shows up in public GitHub.',
        ],
        'mh_f_code_doc_h2' => [
            'Documentation I use' => 'Documentation I keep open.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_code_section_boost_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_section_boost_v1', 62);

/** Copy Code page resume meta onto the Hire page once (for editable hire_cv_* fields). */
function mh_migrate_resume_to_hire(): void
{
    if (get_option('mh_resume_to_hire_v1')) {
        return;
    }

    $hireId = mh_page_id_by_template('template-hire.blade.php');
    $codeId = mh_page_id_by_template('template-code.blade.php');
    if ($hireId < 1) {
        update_option('mh_resume_to_hire_v1', true);

        return;
    }

    $map = [
        'mh_f_code_cv_h2' => 'mh_f_hire_cv_h2',
        'mh_f_code_cv_intro' => 'mh_f_hire_cv_intro',
        'mh_f_code_cv_jobs' => 'mh_f_hire_cv_jobs',
    ];

    foreach ($map as $from => $to) {
        $existing = get_post_meta($hireId, $to, true);
        if ($existing !== '' && $existing !== null && $existing !== []) {
            continue;
        }
        $val = $codeId > 0 ? get_post_meta($codeId, $from, true) : '';
        if ($val === '' || $val === null || $val === []) {
            continue;
        }
        update_post_meta($hireId, $to, $val);
    }

    update_option('mh_resume_to_hire_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_migrate_resume_to_hire', 60);

/**
 * Sub-field definitions for the "Who this site is for" repeater (Home, About, Services).
 *
 * @since 3.1.0
 *
 * @return list<array{0: string, 1: string, 2: string}>
 */
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

/**
 * Default "Who this site is for" audience cards, reused on Home, About, and Services pages.
 *
 * @since 3.1.0
 *
 * @return list<array{title: string, text: string, icon: string, href: string, cta: string}>
 */
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
            'cta' => __('Read the journal', 'sage'),
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
    if (get_option('mh_nav_synced_v4') === '4') {
        return;
    }

    $bySlug = [];
    foreach (['home', 'about', 'projects', 'hire', 'services', 'code', 'contact', 'now', 'blog'] as $slug) {
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

    // Logo is Home. Contact is the header button. Hire stays primary; Shop/Uses stay out of the bar.
    $primaryId = $build('Primary', ['projects', 'hire', 'blog', 'code', 'about']);
    $footerId = $build('Footer', ['projects', 'hire', 'blog', 'code', 'about', 'now', 'contact']);

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['primary_navigation'] = $primaryId;
    $locations['footer_navigation'] = $footerId;
    set_theme_mod('nav_menu_locations', $locations);

    update_option('mh_nav_synced_v4', '4');
}

add_action('init', __NAMESPACE__.'\\mh_sync_nav_menus', 50);

/** Rename Writing → Journal and swap known old field defaults once. */
function mh_apply_journal_rename(): void
{
    if (get_option('mh_journal_rename_v1')) {
        return;
    }

    $blogId = (int) get_option('page_for_posts');
    if ($blogId) {
        $page = get_post($blogId);
        if ($page instanceof \WP_Post && $page->post_title === 'Writing') {
            wp_update_post(['ID' => $blogId, 'post_title' => 'Journal']);
        }
    }

    $swaps = [
        'mh_f_write_kicker' => ['Writing' => 'Journal'],
        'mh_f_write_h1' => [
            'Writing, with snippets when they help.' => 'Journal',
            'Writing' => 'Journal',
        ],
        'mh_f_write_lede' => [
            'Notes on WordPress, plugins, and other web apps. Developers can copy the examples. Shops and agencies can see how I explain a build.' => 'I write about WordPress, plugins, and other web apps I build. Posts walk through the problem and, when it helps, the code. Developers can copy the examples; shops and agencies can see how I explain a build.',
        ],
        'mh_f_write_search_ph' => ['Search posts…' => 'Search posts'],
        'mh_f_write_subscribe_h2' => ['Subscribe in a reader' => 'Follow with RSS'],
        'mh_f_write_subscribe_lede' => [
            'No newsletter form. Copy the RSS URL into Feedly, NetNewsWire, or the reader you already use.' => 'There is no email list. Copy the feed URL into Feedly, NetNewsWire, or another reader you already use.',
        ],
        'mh_f_write_follow' => ['Follow along:' => 'More of my notes'],
        'mh_f_write_bio' => [
            'I write notes from Gettysburg, Pennsylvania, and share WordPress, plugin, and other web-app snippets you can paste in. Developers, shops, and agencies are welcome here.' => 'I write from Gettysburg, Pennsylvania. Posts cover WordPress, plugins, and other web apps, often with snippets you can paste in. Developers, shops, and agencies are welcome here.',
        ],
        'mh_f_home_link_writing' => ['Writing' => 'Journal'],
        'mh_f_home_write_kicker' => ['Notes from the bench' => 'Journal'],
        'mh_f_home_write_h2' => ['Writing' => 'Journal'],
        'mh_f_home_write_intro' => [
            'Notes on WordPress, plugins, and other web apps. Many posts include snippets you can paste into a theme or a plugin.' => 'Short posts on WordPress, plugins, and other web apps. Many include snippets you can paste into a theme or a plugin.',
        ],
        'mh_f_seo_title' => [
            'Writing | Matt Hummel' => 'Journal | Matt Hummel',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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

        $places = get_post_meta($id, 'mh_f_about_places', true);
        if (is_array($places)) {
            $changed = false;
            foreach ($places as $i => $row) {
                $text = (string) ($row['text'] ?? '');
                if ($text === 'This site. Writing, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.') {
                    $places[$i]['text'] = 'This site. A journal, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.';
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_about_places', $places);
            }
        }

        $now = get_post_meta($id, 'mh_f_now_items', true);
        if (is_array($now)) {
            $changed = false;
            foreach ($now as $i => $line) {
                if ($line === 'This Sage 11 site is a notebook: writing, snippets, and example shops.') {
                    $now[$i] = 'This Sage 11 site is a notebook: a journal, snippets, and example shops.';
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_now_items', $now);
            }
        }

        $who = get_post_meta($id, 'mh_f_who_items', true);
        if (is_array($who)) {
            $changed = false;
            foreach ($who as $i => $row) {
                if (($row['cta'] ?? '') === 'Read the writing') {
                    $who[$i]['cta'] = 'Read the journal';
                    $changed = true;
                }
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_who_items', $who);
            }
        }
    }

    $menus = wp_get_nav_menus();
    foreach ($menus as $menu) {
        $items = wp_get_nav_menu_items((int) $menu->term_id);
        if (! is_array($items)) {
            continue;
        }
        foreach ($items as $item) {
            if ($item->title === 'Writing') {
                wp_update_nav_menu_item((int) $menu->term_id, (int) $item->ID, [
                    'menu-item-title' => 'Journal',
                    'menu-item-object' => $item->object,
                    'menu-item-object-id' => $item->object_id,
                    'menu-item-type' => $item->type,
                    'menu-item-status' => 'publish',
                    'menu-item-parent-id' => $item->menu_item_parent,
                    'menu-item-position' => $item->menu_order,
                    'menu-item-url' => $item->url,
                ]);
            }
        }
    }

    update_option('mh_journal_rename_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_journal_rename', 51);

/** Home redesign copy — update fields stored in the DB to match the new defaults. */
function mh_apply_home_redesign_copy(): void
{
    if (get_option('mh_home_redesign_v1')) {
        return;
    }

    $swaps = [
        'mh_f_home_role' => [
            'Full-stack developer. WordPress, plugins, and other web apps.' => 'WordPress developer. I mostly build websites.',
        ],
        'mh_f_home_lede' => [
            'I build WordPress sites, plugins, and other web apps in Gettysburg. Shops get a site they can edit. Developers can copy the code.' => 'I build WordPress sites, plugins, and other web apps. Mostly WordPress — it\'s what I enjoy. Shops get something they can edit. Developers get code they can read.',
        ],
        'mh_f_home_link_code' => [
            'Code and snippets' => 'Code',
        ],
        'mh_f_home_stack_kicker' => [
            'Tools I ship with' => 'The stack',
        ],
        'mh_f_home_stack_h2' => [
            'Stack' => 'What I work with',
        ],
        'mh_f_home_now_h2' => [
            'WordPress, plugins, and other web apps.' => 'WordPress, mostly.',
        ],
        'mh_f_home_write_h2' => [
            'Journal' => 'Recent posts',
        ],
        'mh_f_home_write_intro' => [
            'Short posts on WordPress, plugins, and other web apps. Many include snippets you can paste into a theme or a plugin.' => 'Short posts on WordPress, plugins, and other web apps. Lots of snippets.',
        ],
        'mh_f_home_code_kicker' => [
            'Public on GitHub' => 'GitHub',
        ],
        'mh_f_home_code_h2' => [
            'Code to borrow' => 'Code to copy',
        ],
        'mh_f_home_code_intro' => [
            'Public repos on GitHub, plus short snippets. Fork them, copy them, or ask if a line is unclear.' => 'Public repos and short snippets. Fork them or just read.',
            'Public repos plus short snippets. Fork them, copy them, or ask if a line is unclear.' => 'Public repos and short snippets. Fork them or just read.',
        ],
        'mh_f_home_work_kicker' => [
            'Studio concepts' => 'Example sites',
        ],
        'mh_f_home_work_h2' => [
            'Example sites' => 'Work from the studio',
        ],
        'mh_f_home_work_intro' => [
            'Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops. Useful if you run a local business and want to see what a clear WordPress site can look like.' => 'Concept sites from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg shops, tours, and inns.',
            'Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops.' => 'Concept sites from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg shops, tours, and inns.',
        ],
        'mh_f_home_help_p1' => [
            'I build WordPress sites and plugins shops can edit. I\'ve done Power Platform work when a team runs on Microsoft 365, but WordPress is what I reach for.' => 'I build WordPress sites, plugins, and web apps teams can own. Looking for a full-time or contract developer, or a shop that needs a site? Start here.',
        ],
        'mh_f_home_help_h2' => [
            'If you need a hand' => 'Let\'s talk.',
        ],
        'mh_f_home_help_p1' => [
            'I build WordPress sites, plugins, and other web apps. I still do some Power Platform work when a team already lives in Microsoft 365.' => 'I build WordPress sites and plugins from Gettysburg, PA. I\'ve done Power Platform work when a team runs on Microsoft 365, but WordPress is what I reach for.',
            'I build WordPress sites, plugins, and other web apps. I still do some Power Platform work when a team already lives in Microsoft 365.' => 'I build WordPress sites and plugins from Gettysburg, PA. I\'ve done Power Platform work when a team runs on Microsoft 365, but WordPress is what I reach for.',
        ],
        'mh_f_home_help_p2' => [
            'Read <a href="/services/">how I can help</a>, or <a href="/contact/">send a note</a>. A question about a post or a snippet is just as welcome as a build request.' => 'Read <a href="/services/">how I can help</a>, or just send a note. A question about a post is just as welcome as a project inquiry.',
        ],
        'mh_f_home_who_kicker' => [
            'Who this is for' => 'Who it\'s for',
        ],
        'mh_f_who_h2' => [
            'Four doors in' => 'Pick a starting point',
            'Same site. Different starting points.' => 'Same site. Useful from different angles.',
        ],
        'mh_f_who_intro' => [
            'Same site. Different starting points.' => 'Same site. Useful from different angles.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_home_redesign_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_home_redesign_copy', 52);

/** Home v2 — fresh layout: update headings and CTA stored in DB. */
function mh_apply_home_v2_copy(): void
{
    if (get_option('mh_home_v2_copy')) {
        return;
    }

    $swaps = [
        'mh_f_home_write_h2' => [
            'Recent posts' => 'From the journal',
            'Journal' => 'From the journal',
        ],
        'mh_f_home_work_h2' => [
            'Work from the studio' => 'Selected work',
            'Example sites' => 'Selected work',
        ],
        'mh_f_home_help_h2' => [
            "Let\xe2\x80\x99s talk." => 'Working on something?',
            "Let's talk." => 'Working on something?',
            'If you need a hand' => 'Working on something?',
        ],
        'mh_f_home_help_p2' => [
            'Read <a href="/services/">how I can help</a>, or just send a note. A question about a post is just as welcome as a project inquiry.' => 'Say hello. A question about a post is just as welcome as a project inquiry.',
        ],
        'mh_f_home_lede' => [
            "I build WordPress sites, plugins, and other web apps. Mostly WordPress \xe2\x80\x94 it's what I enjoy. Shops get something they can edit. Developers get code they can read." => "I build WordPress sites and plugins from Gettysburg, PA. Shops get something they actually own \xe2\x80\x94 not a subscription they rent. I've done Power Platform work too, but WordPress is what I reach for.",
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_home_v2_copy', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_home_v2_copy', 53);

/** Reword about bio paragraph — cleaner, no 'WordPress stuck', no adversarial 'But'. */
function mh_apply_about_bio_v1(): void
{
    if (get_option('mh_about_bio_v1')) {
        return;
    }

    $old = "I've been building for the web since the higher-ed marketing days. WordPress stuck because it gives shops real ownership — they can update their own pages without calling me. These days I'm focused on building Ridges & Valleys, a local studio for Gettysburg shops, tours, and inns. But I'm also actively open for work.";
    $new = "I've been building websites since the higher-ed marketing days. WordPress is the tool I kept coming back to — it gives shops real ownership, and they can update their own pages without calling me. Right now I'm building Ridges & Valleys, a local studio for Gettysburg shops, tours, and inns, and I'm open for new work at the same time.";

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $val = get_post_meta($id, 'mh_f_home_about_text', true);
        if (is_string($val) && $val === $old) {
            update_post_meta($id, 'mh_f_home_about_text', $new);
        }
    }

    update_option('mh_about_bio_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_bio_v1', 54);

/** One-time About page copy boost (SEO + field defaults). */
function mh_apply_about_section_boost_v1(): void
{
    if (get_option('mh_about_section_boost_v1')) {
        return;
    }

    $swaps = [
        'mh_f_about_h1' => [
            'A little background.' => 'WordPress developer in Gettysburg.',
        ],
        'mh_f_about_lede' => [
            'I work in PHP and Blade, write front-end in Tailwind, and deploy with GitHub Actions. I lean toward clean, maintainable code over clever code — because the person after me needs to read it too. Based in Gettysburg, PA.' => 'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read. Deployments run through GitHub Actions. Need full-time, contract, or agency overflow help? Say hello.',
            'I write PHP and Blade, ship front ends in Tailwind, and deploy with GitHub Actions. Clean, maintainable code over clever code — the next developer needs to read it too. Based in Gettysburg, Pennsylvania.' => 'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read. Deployments run through GitHub Actions. Need full-time, contract, or agency overflow help? Say hello.',
        ],
        'mh_f_about_story_h2' => [
            'How I got here' => 'How I got here.',
        ],
        'mh_f_about_values_h2' => [
            'How I like to work' => 'How I work.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_about_section_boost_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_section_boost_v1', 63);

/** One-time About hero blurb rewrite (after section boost may already have run). */
function mh_apply_about_hero_rewrite_v1(): void
{
    if (get_option('mh_about_hero_rewrite_v1')) {
        return;
    }

    $to = 'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read. Deployments run through GitHub Actions. Need full-time, contract, or agency overflow help? Say hello.';

    $from = [
        'I work in PHP and Blade, write front-end in Tailwind, and deploy with GitHub Actions. I lean toward clean, maintainable code over clever code — because the person after me needs to read it too. Based in Gettysburg, PA.',
        'I write PHP and Blade, ship front ends in Tailwind, and deploy with GitHub Actions. Clean, maintainable code over clever code — the next developer needs to read it too. Based in Gettysburg, Pennsylvania.',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $val = get_post_meta($id, 'mh_f_about_lede', true);
        if (! is_string($val) || $val === '') {
            continue;
        }
        if (in_array($val, $from, true)) {
            update_post_meta($id, 'mh_f_about_lede', $to);
        }
    }

    update_option('mh_about_hero_rewrite_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_hero_rewrite_v1', 64);

/** One-time About above-the-fold hero tighten (kicker + shorter lede). */
function mh_apply_about_atf_v1(): void
{
    if (get_option('mh_about_atf_v1')) {
        return;
    }

    $ledeTo = 'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read.';
    $ledeFrom = [
        'I work in PHP and Blade, write front-end in Tailwind, and deploy with GitHub Actions. I lean toward clean, maintainable code over clever code — because the person after me needs to read it too. Based in Gettysburg, PA.',
        'I write PHP and Blade, ship front ends in Tailwind, and deploy with GitHub Actions. Clean, maintainable code over clever code — the next developer needs to read it too. Based in Gettysburg, Pennsylvania.',
        'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read. Deployments run through GitHub Actions. Need full-time, contract, or agency overflow help? Say hello.',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;

        $kicker = get_post_meta($id, 'mh_f_about_kicker', true);
        if ($kicker === 'About') {
            update_post_meta($id, 'mh_f_about_kicker', 'Matt Hummel');
        }

        $lede = get_post_meta($id, 'mh_f_about_lede', true);
        if (is_string($lede) && in_array($lede, $ledeFrom, true)) {
            update_post_meta($id, 'mh_f_about_lede', $ledeTo);
        }
    }

    update_option('mh_about_atf_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_atf_v1', 65);

/** One-time About hero lede: casual, friendly voice. */
function mh_apply_about_lede_friendly_v1(): void
{
    if (get_option('mh_about_lede_friendly_v1')) {
        return;
    }

    $to = 'I build WordPress sites and plugins from Gettysburg. Shops can edit their own site, and other developers can pick up the code without a headache.';

    $from = [
        'I work in PHP and Blade, write front-end in Tailwind, and deploy with GitHub Actions. I lean toward clean, maintainable code over clever code — because the person after me needs to read it too. Based in Gettysburg, PA.',
        'I write PHP and Blade, ship front ends in Tailwind, and deploy with GitHub Actions. Clean, maintainable code over clever code — the next developer needs to read it too. Based in Gettysburg, Pennsylvania.',
        'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read. Deployments run through GitHub Actions. Need full-time, contract, or agency overflow help? Say hello.',
        'I build WordPress sites and plugins from Gettysburg — Sage themes shops can edit, PHP other developers can read.',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $lede = get_post_meta($id, 'mh_f_about_lede', true);
        if (is_string($lede) && in_array($lede, $from, true)) {
            update_post_meta($id, 'mh_f_about_lede', $to);
        }
    }

    update_option('mh_about_lede_friendly_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_lede_friendly_v1', 66);

/** One-time About page friendly / informational copy boost. */
function mh_apply_about_friendly_copy_v1(): void
{
    if (get_option('mh_about_friendly_copy_v1')) {
        return;
    }

    $swaps = [
        'mh_f_about_p1' => [
            'I started in web doing marketing for higher education. Building landing pages, updating content, and figuring out why something that looked right was not getting clicks. That work taught me more about what people need than any framework ever did.' => 'I started on the web in higher-ed marketing. Landing pages, content updates, and a lot of figuring out why something that looked right still wasn’t getting clicks. That taught me more about what people need than any framework ever did.',
        ],
        'mh_f_about_p2' => [
            'WordPress is the tool I kept coming back to. Not because it is the most exciting option, but because it is the most practical one for most shops. An owner can update hours, add a product, or fix a typo without waiting on a developer. That matters to me.' => 'WordPress is the tool I kept coming back to. Most shops need a site they can edit themselves: update hours, add a product, fix a typo, without waiting on a developer. That still matters to me.',
        ],
        'mh_f_about_p3' => [
            'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. It is a growing body of work for Adams County. Alongside that, I am open for new work — full-time, contract, or project-based.' => 'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. It’s a growing body of work for Adams County. Alongside that, I’m open for new work — full-time, contract, or project-based.',
        ],
        'mh_f_about_p4' => [
            'Most of my public code is on GitHub. Snippets go on the journal. If something helped you, you do not need to ask permission to use it.' => 'Most of my public code lives on GitHub. Shorter notes go on the journal. If something helps you, use it — you don’t need to ask.',
        ],
        'mh_f_about_services_intro' => [
            'Most projects are WordPress sites and plugins from Gettysburg — with React and Power Platform when they fit. Here is the breakdown.' => 'Most days I’m building WordPress sites and plugins from Gettysburg. React and Power Platform show up when they actually help. Here’s the breakdown.',
        ],
        'mh_f_about_services_note' => [
            'Questions about a specific project type? <a href="/contact/">Write a note</a>.' => 'Curious about a specific project type? <a href="/contact/">Write a note</a>.',
        ],
        'mh_f_about_work_p1' => [
            'I am looking for new work alongside the studio. That includes full-time roles, contract arrangements, and freelance projects. Based in Gettysburg, PA — open to remote.' => 'I’m looking for new work alongside the studio — full-time roles, contract gigs, and freelance projects. Based in Gettysburg, PA, and happy to work remote.',
        ],
        'mh_f_about_work_p2' => [
            'If you are a recruiter, an agency, or a shop that needs a WordPress developer, I am glad to hear from you. Start with a short note about what you are working on.' => 'If you’re a recruiter, an agency with overflow, or a shop that needs a WordPress developer, I’d love a short note about what you’re working on.',
        ],
        'mh_f_about_values_intro' => [
            'A short list of how WordPress projects leave my desk in Gettysburg — ownership, editability, and code another developer can read.' => 'A few habits that stick on WordPress projects from Gettysburg — you own the site, you can edit it, and another developer can follow the code.',
        ],
        'mh_f_about_elsewhere_intro' => [
            'I post most of my WordPress code and writing here and on GitHub. The RSS feed is the most reliable way to follow along from Gettysburg.' => 'Most of my WordPress code and writing shows up here and on GitHub. RSS is the calmest way to follow along from Gettysburg.',
        ],
        'mh_f_about_cta_lede' => [
            'A question about a post, a project, or a role — all welcome. I usually reply within a day.' => 'Got a question about a post, a project, or a role? Send it over. I usually reply within a day.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_about_friendly_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_friendly_copy_v1', 67);

/** One-time: plain “How I got here” story — drop odd wording like “flashiest”. */
function mh_apply_about_story_plain_v1(): void
{
    if (get_option('mh_about_story_plain_v1')) {
        return;
    }

    $p1 = 'I started on the web in higher-ed marketing — landing pages, content updates, and figuring out why a page that looked fine still wasn’t getting clicks. That taught me more about what people need than any course or tool.';
    $p2 = 'WordPress is the tool I kept coming back to. Most shops need a site they can edit themselves: update hours, add a product, fix a typo, without waiting on a developer. That still matters to me.';
    $p3 = 'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. Alongside that studio work, I’m open for full-time, contract, or project-based roles.';
    $p4 = 'Most of my public code is on GitHub. Shorter notes go on the journal. If something helps you, use it — you don’t need to ask.';

    $swaps = [
        'mh_f_about_p1' => [
            'I started in web doing marketing for higher education. Building landing pages, updating content, and figuring out why something that looked right was not getting clicks. That work taught me more about what people need than any framework ever did.' => $p1,
            'I started on the web in higher-ed marketing. Landing pages, content updates, and a lot of figuring out why something that looked right still wasn’t getting clicks. That taught me more about what people need than any framework ever did.' => $p1,
        ],
        'mh_f_about_p2' => [
            'WordPress is the tool I kept coming back to. Not because it is the most exciting option, but because it is the most practical one for most shops. An owner can update hours, add a product, or fix a typo without waiting on a developer. That matters to me.' => $p2,
            'WordPress is the tool I kept coming back to. Not the flashiest option — just the most practical one for most shops. An owner can update hours, add a product, or fix a typo without waiting on a developer. That still matters to me.' => $p2,
        ],
        'mh_f_about_p3' => [
            'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. It is a growing body of work for Adams County. Alongside that, I am open for new work — full-time, contract, or project-based.' => $p3,
            'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. It’s a growing body of work for Adams County. Alongside that, I’m open for new work — full-time, contract, or project-based.' => $p3,
        ],
        'mh_f_about_p4' => [
            'Most of my public code is on GitHub. Snippets go on the journal. If something helped you, you do not need to ask permission to use it.' => $p4,
            'Most of my public code lives on GitHub. Shorter notes go on the journal. If something helps you, use it — you don’t need to ask.' => $p4,
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
    }

    update_option('mh_about_story_plain_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_about_story_plain_v1', 68);

/**
 * One-time positioning refresh: full-stack first, WordPress specialty second.
 *
 * Page copy is stored in post meta, so changing Blade fallbacks alone does not
 * update the live site. This migration intentionally replaces the key landing
 * page fields requested in the August 2026 site-wide content refresh.
 */
function mh_apply_fullstack_wordpress_positioning_v1(): void
{
    if (get_option('mh_fullstack_wordpress_positioning_v1')) {
        return;
    }

    $content = [
        'front-page.blade.php' => [
            'home_role' => 'Full-stack web development, with WordPress at the center.',
            'home_lede' => 'I build custom WordPress platforms and web applications with PHP, JavaScript, React, and APIs. Businesses get software they own; agencies get clean code built for a confident handoff.',
            'home_cta_primary' => 'Start a conversation',
            'home_cta_secondary' => 'Explore projects',
            'home_about_h2' => 'Full-stack developer. WordPress specialist. Open to collaboration.',
            'home_about_text' => 'I’ve spent more than 15 years building for the web, from accessible front ends to PHP applications, APIs, and deployment workflows. WordPress is my specialty because it combines a flexible development platform with an editor businesses can actually use.',
            'home_about_p2' => 'I work with businesses that need dependable web software, agencies that need an experienced development partner, and developers who want to compare notes or reuse open-source code. Most of my public work is on GitHub, and you are welcome to fork it.',
            'home_write_h2' => 'Full-stack notes, WordPress code, and project lessons.',
            'home_write_intro' => 'Practical notes from WordPress, PHP, JavaScript, React, APIs, and real project work. Most posts include code you can adapt or use.',
            'seo_title' => 'Full-Stack & WordPress Developer | Matt Hummel',
            'seo_desc' => 'Full-stack developer and WordPress specialist building maintainable web platforms, applications, plugins, and integrations. Based in Gettysburg.',
        ],
        'template-services.blade.php' => [
            'svc_kicker' => 'Full-stack developer · WordPress specialist',
            'svc_h1' => 'Full-stack web development for businesses, agencies, and developers.',
            'svc_lede' => 'Custom WordPress platforms, plugins, integrations, and web applications built with clear scope and clean handoffs. I work directly with businesses, partner quietly with agencies, and collaborate with development teams.',
            'seo_title' => 'Full-Stack & WordPress Development Services | Matt Hummel',
            'seo_desc' => 'Custom WordPress platforms, plugins, integrations, and full-stack web applications for businesses, agencies, and development teams.',
        ],
        'template-about.blade.php' => [
            'about_h1' => 'Full-stack developer. WordPress specialist.',
            'about_lede' => 'I build accessible, maintainable web software from front end to back end, with deep experience in custom WordPress development.',
            'about_services_intro' => 'I work across the stack: accessible interfaces, WordPress and PHP back ends, React applications, APIs, databases, and deployment. WordPress is the specialty, not the limit.',
            'about_work_p2' => 'If you’re hiring a full-stack developer, need an experienced WordPress specialist, want agency overflow support, or have a web project to discuss, send a short note about what you’re working on.',
            'about_cta_h2' => 'Need a full-stack or WordPress development partner?',
            'seo_title' => 'About Matt Hummel — Full-Stack & WordPress Developer',
            'seo_desc' => 'Full-stack developer and WordPress specialist with 15+ years building accessible, maintainable web software from Gettysburg, Pennsylvania.',
        ],
        'template-code.blade.php' => [
            'code_h1' => 'Full-stack and WordPress code you can use.',
            'code_do_intro' => 'I ship across the stack: custom WordPress themes and plugins, PHP, TypeScript, React, APIs, and data-backed applications. The public repos show how I structure code, document decisions, and prepare work for handoff.',
            'code_gh_h2' => 'Open-source full-stack and WordPress code on GitHub.',
            'code_cta_h2' => 'Want to build, collaborate, or compare notes?',
            'seo_title' => 'Open-Source Full-Stack & WordPress Code | Matt Hummel',
            'seo_desc' => 'Public React apps, Sage themes, WordPress plugins, PHP, TypeScript, and GitHub activity. Browse projects, fork repos, or collaborate.',
        ],
        'template-now.blade.php' => [
            'now_work_p1' => 'Alongside the studio I’m actively looking for full-time roles, contract work, freelance projects, and agency partnerships. My focus is full-stack web development, especially WordPress, PHP, JavaScript, React, and API integrations.',
            'now_work_p2' => 'If you’re hiring a full-stack developer, need WordPress expertise, or want a dependable development partner for overflow work, a short note is enough to start.',
        ],
        'template-hire.blade.php' => [
            'hire_h1' => 'Hire a full-stack developer with deep WordPress experience.',
            'hire_lede' => 'Available for full-stack web applications, custom WordPress work, plugins, integrations, agency overflow, and full-time or contract roles. Based in Gettysburg and working remotely anywhere.',
            'seo_title' => 'Hire a Full-Stack & WordPress Developer | Matt Hummel',
            'seo_desc' => 'Available for full-stack web applications, WordPress development, plugins, integrations, agency overflow, and full-time or contract roles.',
        ],
        'template-contact.blade.php' => [
            'cnt_lede' => 'Questions about a post, a code snippet, or GitHub are welcome. So are conversations about full-stack applications, WordPress platforms, roles, and development partnerships. I read everything and reply within one or two business days.',
            'seo_title' => 'Contact a Full-Stack & WordPress Developer | Matt Hummel',
            'seo_desc' => 'Start a conversation about a web application, WordPress platform, plugin, integration, role, or development partnership.',
        ],
        'index.blade.php' => [
            'write_h1' => 'Full-stack and WordPress development notes.',
            'write_lede' => 'Practical notes on WordPress, PHP, JavaScript, React, APIs, and the tools I use on real projects. Most include code you can adapt or use.',
            'seo_title' => 'Journal — Full-Stack & WordPress Development | Matt Hummel',
            'seo_desc' => 'Practical notes on WordPress, PHP, JavaScript, React, APIs, and tools from real projects. Most posts include code you can adapt.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $template = page_template_key($id);
        if (! isset($content[$template])) {
            continue;
        }

        foreach ($content[$template] as $key => $value) {
            update_post_meta($id, 'mh_f_'.$key, $value);
        }

        if ($template === 'template-about.blade.php') {
            update_post_meta($id, 'mh_f_about_services', mh_about_services_defaults());
        }
        if ($template === 'template-code.blade.php') {
            update_post_meta($id, 'mh_f_code_do_items', mh_code_practice_defaults());
        }
    }

    update_option('blogdescription', 'Full-stack developer and WordPress specialist. Web platforms, applications, plugins, and integrations.');
    update_option('mh_fullstack_wordpress_positioning_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_fullstack_wordpress_positioning_v1', 69);

/** Append n8n to Code page skills once (icon + Workflow shelf). */
function mh_apply_code_skills_n8n_v1(): void
{
    if (get_option('mh_code_skills_n8n_v1')) {
        return;
    }

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-code.blade.php',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $raw = get_post_meta($id, 'mh_f_code_skills', true);
        if (! is_string($raw) || trim($raw) === '') {
            continue;
        }
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: []), static fn ($s) => $s !== ''));
        $has = false;
        foreach ($lines as $line) {
            if (strtolower($line) === 'n8n' || strtolower($line) === 'n8n.io') {
                $has = true;
                break;
            }
        }
        if ($has) {
            continue;
        }
        $lines[] = 'n8n';
        update_post_meta($id, 'mh_f_code_skills', implode("\n", $lines));
    }

    update_option('mh_code_skills_n8n_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_code_skills_n8n_v1', 70);

/** Reframe Ridges & Valleys as concept demos, not an active studio. */
function mh_apply_ridges_concept_framing_v1(): void
{
    if (get_option('mh_ridges_concept_framing_v1')) {
        return;
    }

    $core = 'Ridges & Valleys is where I publish Gettysburg concept sites — live WordPress demos for shops, tours, and inns. I\'m building the studio brand as real projects come in; hire me for builds on matthummel.com.';

    $content = [
        'front-page.blade.php' => [
            'home_work_intro' => $core,
        ],
        'template-home.blade.php' => [
            'home_work_intro' => $core,
        ],
        'template-about.blade.php' => [
            'about_p3' => $core,
            'about_work_p1' => 'I\'m looking for full-time roles, contract gigs, and freelance projects on matthummel.com. Based in Gettysburg, PA, and happy to work remote.',
        ],
        'template-now.blade.php' => [
            'now_studio_p1' => $core,
            'now_studio_p2' => 'Browse the demos at ridgesandvalleys.com. When you\'re ready for a real build, say hello here.',
            'now_work_p1' => 'I\'m actively looking for full-time roles, contract work, freelance projects, and agency partnerships. My focus is full-stack web development, especially WordPress, PHP, JavaScript, React, and API integrations.',
        ],
        'template-projects.blade.php' => [
            'work_lede' => $core,
            'work_foot' => 'Repos and snippets: <a href="/code/">Code</a>. Concept demos: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.',
            'work_band_lede' => 'These are concept demos, not a case-study deck. If one fits a tour, inn, shop, or restaurant you run, write and say which concept you want to start from.',
        ],
        'template-contact.blade.php' => [
            'cnt_aside' => 'Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is where I publish Gettysburg concept demos.',
        ],
        'template-code.blade.php' => [
            'code_gh_intro' => 'Public repos from Gettysburg — Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats and activity below pull live from the GitHub API.',
            'code_feat_intro' => 'Three public codebases I point developers to first: a React app, a WordPress plugin, and the Sage theme behind my Gettysburg concept demos. Each one is meant to be forked.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $template = page_template_key($id);
        if (! isset($content[$template])) {
            continue;
        }

        foreach ($content[$template] as $key => $value) {
            update_post_meta($id, 'mh_f_'.$key, $value);
        }

        if ($template === 'template-code.blade.php') {
            $lines = field_lines('code_do_items', mh_code_practice_defaults(), $id);
            $updated = false;
            foreach ($lines as $i => $line) {
                if (! is_string($line)) {
                    continue;
                }
                if (str_contains(strtolower($line), 'ridges') && str_contains(strtolower($line), 'valleys')) {
                    $lines[$i] = 'Ridges & Valleys — Gettysburg concept sites and live WordPress demos for shops, tours, and inns.';
                    $updated = true;
                }
            }
            if ($updated) {
                update_post_meta($id, 'mh_f_code_do_items', implode("\n", $lines));
            }
        }

        if ($template === 'template-hire.blade.php') {
            $jobs = field_rows('hire_cv_jobs', mh_code_resume_defaults(), $id);
            $changed = false;
            foreach ($jobs as $i => $job) {
                if (($job['org'] ?? '') !== 'Ridges & Valleys') {
                    continue;
                }
                $jobs[$i]['type'] = 'Concept work · Gettysburg, PA';
                $jobs[$i]['bullets'] = "Publishing Gettysburg concept sites — live WordPress demos for shops, tours, and inns.\nBuilding the Ridges & Valleys brand as real client projects come in.\nOpen to agencies, overflow dev work, and full-time roles. Remote anywhere.";
                $changed = true;
            }
            if ($changed) {
                update_post_meta($id, 'mh_f_hire_cv_jobs', $jobs);
            }
        }
    }

    update_option('mh_ridges_concept_framing_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_ridges_concept_framing_v1', 71);

/**
 * Drop Ridges & Valleys as a public brand. Visitor copy uses Matt Hummel / this site.
 */
function mh_rewrite_ridges_brand_value(mixed $value): mixed
{
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = mh_rewrite_ridges_brand_value($item);
        }

        return $value;
    }

    if (! is_string($value) || $value === '') {
        return $value;
    }

    $exact = [
        'Ridges & Valleys is where I publish Gettysburg projects — live WordPress demos for shops, tours, and inns. I\'m building the studio brand as real projects come in; hire me for builds on matthummel.com.' => 'I publish Gettysburg WordPress projects here — live demos for shops, tours, and inns. Hire me on this site for a real build.',
        'Ridges & Valleys is where I publish Gettysburg concept sites — live WordPress demos for shops, tours, and inns. I\'m building the studio brand as real projects come in; hire me for builds on matthummel.com.' => 'I publish Gettysburg WordPress projects here — live demos for shops, tours, and inns. Hire me on this site for a real build.',
        'Browse the demos at ridgesandvalleys.com. When you\'re ready for a real build, say hello here.' => 'Browse the Work page. When you\'re ready for a real build, say hello here.',
        'I\'m choosing which Gettysburg projects to publish here first. In the meantime, browse the live demos on Ridges & Valleys, or write and tell me what kind of shop you run.' => 'I\'m choosing which Gettysburg projects to publish here first. Write and tell me what kind of shop you run.',
        'No. Ridges & Valleys holds the Gettysburg WordPress demos. This site is for builds and sharing.' => 'No. This site is for WordPress builds and sharing, including Gettysburg example sites.',
        'I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.' => 'I don’t run ads or social accounts for shops. This site is for WordPress builds and sharing. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.',
        'Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is where I publish Gettysburg WordPress demos.' => 'Prefer GitHub or LinkedIn? Those work too. This site is where I publish Gettysburg WordPress demos.',
        'Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is where I publish Gettysburg concept demos.' => 'Prefer GitHub or LinkedIn? Those work too. This site is where I publish Gettysburg WordPress demos.',
        'Repos and snippets: <a href="/code/">Code</a>. Live demos: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.' => 'Repos and snippets: <a href="/code/">Code</a>. Live demos open from each project page when available.',
        'Repos and snippets: <a href="/code/">Code</a>. Concept demos: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.' => 'Repos and snippets: <a href="/code/">Code</a>. Live demos open from each project page when available.',
        'Ridges & Valleys — Gettysburg projects and live WordPress demos for shops, tours, and inns.' => 'Gettysburg projects — live WordPress demos for shops, tours, and inns.',
        'Ridges & Valleys — Gettysburg concept sites and live WordPress demos for shops, tours, and inns.' => 'Gettysburg projects — live WordPress demos for shops, tours, and inns.',
        'Building the Ridges & Valleys brand as real client projects come in.' => 'Building WordPress sites shops can edit.',
        'I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. Alongside that studio work, I’m open for full-time, contract, or project-based roles.' => 'I publish Gettysburg WordPress projects here — live demos for shops, tours, and inns. Hire me on this site for a real build.',
        'Based in Gettysburg, PA. I just started Ridges & Valleys and I work with shops and agencies in any location. Roles below match my LinkedIn. I am still open to agencies, overflow work, and full-time positions.' => 'Based in Gettysburg, PA — working with shops and agencies anywhere. Open to full-time, contract, and agency overflow work.',
        'Ridges & Valleys is the studio I just started. I work with shops, inns, tours, and agencies in any location.' => 'Gettysburg projects — live WordPress demos for shops, tours, and inns.',
        'Gettysburg projects — live WordPress demos for shops, tours, and inns. The studio brand grows as real work comes in.' => 'Live WordPress demos for shops, tours, and inns in Adams County. Hire me here for a real build.',
        'https://ridgesandvalleys.com' => home_url('/'),
    ];
    if (isset($exact[$value])) {
        return $exact[$value];
    }

    $home = rtrim(home_url('/'), '/');
    $value = str_replace(
        ['https://ridgesandvalleys.com/', 'https://ridgesandvalleys.com', 'Ridges &amp; Valleys', 'Ridges & Valleys'],
        [$home.'/', $home, 'Matt Hummel', 'Matt Hummel'],
        $value
    );

    return (string) (preg_replace('#(?<![a-z0-9.-])ridgesandvalleys\.com#i', 'matthummel.com', $value) ?? $value);
}

function mh_apply_matt_hummel_brand_v1(): void
{
    if (get_option('mh_matt_hummel_brand_v1') || wp_installing()) {
        return;
    }

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
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
            $raw = get_post_meta($id, $key, true);
            $next = mh_rewrite_ridges_brand_value($raw);
            if ($next !== $raw) {
                update_post_meta($id, $key, $next);
            }
        }
    }

    $blogname = (string) get_option('blogname');
    if ($blogname !== '' && (stripos($blogname, 'Ridges') !== false || stripos($blogname, 'Valleys') !== false)) {
        update_option('blogname', 'Matt Hummel');
    }

    $tagline = (string) get_option('blogdescription');
    if ($tagline !== '') {
        $nextTagline = mh_rewrite_ridges_brand_value($tagline);
        if (is_string($nextTagline) && $nextTagline !== $tagline) {
            update_option('blogdescription', $nextTagline);
        }
    }

    update_option('mh_matt_hummel_brand_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_matt_hummel_brand_v1', 72);

/**
 * Refresh About "How I work" intro and approach cards to the technical handoff copy.
 */
function mh_apply_how_i_work_v1(): void
{
    if (get_option('mh_how_i_work_v1')) {
        return;
    }

    $keys = [
        'mh_f_about_values_intro',
        'mh_f_about_approach',
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        foreach ($keys as $key) {
            if (metadata_exists('post', $id, $key)) {
                delete_post_meta($id, $key);
            }
        }
    }

    update_option('mh_how_i_work_v1', true);
}

add_action('init', __NAMESPACE__.'\mh_apply_how_i_work_v1', 73);

/**
 * One-time swap of saved page-field meta to hireable + disclosed-affiliate copy.
 * Only replaces values that still match the previous defaults.
 */
function mh_apply_hireable_affiliate_copy_v1(): void
{
    if (get_option('mh_hireable_affiliate_copy_v1')) {
        return;
    }

    $swaps = [
        'This site. A journal, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.' => 'Hireable portfolio with journal, public code, optional theme sales, and disclosed tool recommendations.',
        'Helping with a few extra builds when I have room — WordPress platforms, plugins, integrations, and full-stack applications.' => 'Curating Uses/Resources with clear affiliate disclosure when a link is compensated.',
        'No. This site is for WordPress builds and sharing, including example sites you can open.' => 'No social management. I may earn from disclosed affiliate links on Uses/Resources, and I sell themes from studio work. The site stays a portfolio first.',
        'Live WordPress demos for shops, tours, and inns. Hire me here for a real build.' => 'Concept WordPress projects with demos and stack notes. Hire me to adapt one, or buy a theme when listed.',
        'Raising kids. Nights and weekends are scarce, so I keep extra projects small.' => 'Raising kids — nights and weekends stay scarce, so side work stays focused.',
        'This Sage 11 site is a notebook: a journal, snippets, and example shops.' => 'Shipping concept sites and themes from studio projects (Work + optional Shop).',
        'Full-stack work: WordPress, plugins, and other web apps.' => 'Open for full-time, contract, and freelance WordPress / full-stack work.',
        'Sharing notes on this blog, DEV.to, Bluesky, and Reddit.' => 'Publishing notes on the journal, DEV.to, Bluesky, and Reddit.',
        'Example sites' => 'Work',
    ];

    $keyed = [
        'mh_f_about_p3' => [
            'I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.' => 'Work shows concept sites and themes from studio projects. Hire me to adapt one, buy a theme when it is for sale, or browse free code on GitHub.',
        ],
        'mh_f_cnt_lede' => [
            'Questions about a post, a code snippet, or GitHub are welcome. So are conversations about full-stack applications, WordPress platforms, roles, and development partnerships. I usually reply in one or two business days.' => 'Open for full-time roles, contract work, freelance builds, and agency overflow. Questions about a post or GitHub are welcome too. I usually reply in one or two business days.',
        ],
        'mh_f_code_h1' => [
            'Full-stack and WordPress code you can use.' => 'Code & open-source work.',
        ],
        'mh_f_footer_blurb' => [
            'Notes, code, and example sites. Developers, shops, and agencies are welcome.' => 'Full-stack & WordPress developer. Portfolio work, themes you can buy, and tools I recommend — with clear affiliate disclosure when a link is compensated.',
        ],
        'mh_f_home_avail_status' => [
            'Open to new projects' => 'Open for work',
        ],
        'mh_f_home_cta_primary' => [
            'Start a conversation' => 'Hire me',
        ],
        'mh_f_home_cta_primary_url' => [
            '/contact/' => '/hire/',
        ],
        'mh_f_home_cta_secondary' => [
            'Explore projects' => 'Browse work',
        ],
        'mh_f_home_help_h2' => [
            'Working on something?' => 'Hiring or building?',
        ],
        'mh_f_home_help_p2' => [
            'Say hello. A question about a post is just as welcome as a project inquiry.' => 'Say hello on <a href="/hire/">Hire</a> or <a href="/contact/">Contact</a>. Roles, freelance builds, and post questions are all welcome.',
        ],
        'mh_f_home_lede' => [
            'I build custom WordPress platforms and web applications with PHP, JavaScript, React, and APIs. Businesses get software they own; agencies get clean code built for a confident handoff.' => 'I build custom WordPress platforms and web apps with PHP, JavaScript, React, and APIs. Open for full-time roles, contract work, and freelance builds. Shops get software they own; agencies get clean handoffs.',
        ],
        'mh_f_home_process_note' => [
            'No ongoing contracts unless you want one. A question about a post is just as welcome as a <a href="/contact/">build request</a>.' => 'Open for full-time, contract, and project work. A question about a post is welcome — so is a <a href="/hire/">hire conversation</a>.',
        ],
        'mh_f_home_role' => [
            'Full-stack web development, with WordPress at the center.' => 'Full-stack & WordPress developer — open for full-time, contract, and freelance.',
        ],
        'mh_f_home_work_h2' => [
            'Example WordPress sites for shops, tours, and inns.' => 'Selected WordPress work.',
        ],
        'mh_f_home_work_intro' => [
            'I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.' => 'Concept sites and themes from real studio work. Browse a project for the story; hire me to adapt it, or buy a theme when one is for sale.',
        ],
        'mh_f_now_studio_p1' => [
            'I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.' => 'I publish concept WordPress projects here. Hire me for a production build, or buy a theme when a project is for sale.',
        ],
        'mh_f_svc_fair' => [
            'I don’t run ads or social accounts for shops. This site is for WordPress builds and sharing. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.' => 'This site is a hireable portfolio first. Themes and tool recommendations are secondary — affiliate links are disclosed. If a build or role fits, <a href="/hire/">start on Hire</a>.',
        ],
        'mh_f_work_band_h2' => [
            'Want a site in this shape?' => 'Like this shape?',
        ],
        'mh_f_work_band_lede' => [
            'These projects are starting points for a real build. If one fits a tour, inn, shop, or restaurant you run, write and say which project you want to start from.' => 'These projects are starting points. Hire me to adapt one, buy the theme when it is listed, or say hello about a role.',
        ],
        'mh_f_work_h1' => [
            'Example WordPress sites.' => 'Selected WordPress work.',
        ],
        'mh_f_work_lede' => [
            'I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.' => 'Concept sites with real stack notes and demos. Hire me to adapt one for your shop, buy a theme when it is for sale, or study the free code on GitHub.',
        ],
    ];

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $all = get_post_meta($id);
        if (! is_array($all)) {
            continue;
        }

        foreach (array_keys($all) as $key) {
            if (! is_string($key) || ! str_starts_with($key, 'mh_f_')) {
                continue;
            }
            $raw = get_post_meta($id, $key, true);
            if (! is_string($raw) || $raw === '') {
                continue;
            }

            if (isset($keyed[$key][$raw])) {
                update_post_meta($id, $key, $keyed[$key][$raw]);

                continue;
            }

            if ($raw === '/contact/' && $key !== 'mh_f_home_cta_primary_url') {
                continue;
            }

            if (isset($swaps[$raw])) {
                update_post_meta($id, $key, $swaps[$raw]);

                continue;
            }

            $next = $raw;
            foreach ($swaps as $old => $new) {
                if (str_contains($next, $old)) {
                    $next = str_replace($old, $new, $next);
                }
            }
            if ($next !== $raw) {
                update_post_meta($id, $key, $next);
            }
        }
    }

    update_option('mh_hireable_affiliate_copy_v1', true);
}

add_action('init', __NAMESPACE__.'\\mh_apply_hireable_affiliate_copy_v1', 74);
