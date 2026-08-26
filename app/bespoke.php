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
            'Featured repositories' => 'Repos worth opening first',
        ],
        'mh_f_code_feat_intro' => [
            'Three codebases I point people to first: a full-stack app, a WordPress plugin, and a Sage theme.' => 'Three codebases I point people to first: a full-stack app, a WordPress plugin, and the Sage theme behind this site.',
        ],
        'mh_f_code_live_h2' => [
            'Recently updated' => 'Recently pushed',
        ],
        'mh_f_code_live_all' => [
            'All public repos' => 'All public repositories',
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
