<?php

/**
 * Editable page content — same idea as Ridges & Valleys.
 *
 * A “Page content (theme)” meta box shows the fields for the page’s template.
 * Leave a box empty to keep the built-in default. Values live in post meta
 * `mh_f_{key}` and Blade reads them with \App\field('key', $default).
 */

namespace App;

function field(string $key, string $default = '', ?int $post_id = null): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    if (! $post_id) {
        return $default;
    }
    $val = get_post_meta($post_id, 'mh_f_'.$key, true);

    return ($val === '' || $val === null) ? $default : (string) $val;
}

function field_html(string $key, string $default = '', ?int $post_id = null): string
{
    return wp_kses_post(field($key, $default, $post_id));
}

function field_lines(string $key, array $default = [], ?int $post_id = null): array
{
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = $post_id ? get_post_meta($post_id, 'mh_f_'.$key, true) : '';

    if (is_array($raw)) {
        $items = array_values(array_filter(array_map('trim', array_map('strval', $raw)), static fn ($s) => $s !== ''));

        return $items ?: $default;
    }
    if ($raw !== '' && $raw !== null) {
        $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)), static fn ($s) => $s !== ''));

        return $items ?: $default;
    }

    return $default;
}

function field_rows(string $key, array $default = [], ?int $post_id = null): array
{
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = $post_id ? get_post_meta($post_id, 'mh_f_'.$key, true) : '';
    $rows = (is_array($raw) && $raw !== []) ? array_values($raw) : $default;

    return is_array($rows) && $rows !== [] ? $rows : $default;
}

function page_template_key(int $post_id): string
{
    if ($post_id && (int) get_option('page_on_front') === $post_id) {
        return 'front-page.blade.php';
    }
    if ($post_id && (int) get_option('page_for_posts') === $post_id) {
        return 'index.blade.php';
    }

    return (string) get_post_meta($post_id, '_wp_page_template', true);
}

function mh_home_fields(): array
{
    return [
        __('Hero', 'sage') => [
            ['home_kicker', __('Kicker (above name)', 'sage'), 'text', __('Gettysburg, PA', 'sage')],
            ['home_h1', __('Heading', 'sage'), 'text', __('Matt Hummel', 'sage')],
            ['home_role', __('Role line', 'sage'), 'text', __('Full-stack developer. WordPress, plugins, and other web apps.', 'sage')],
            ['home_lede', __('Intro', 'sage'), 'textarea', __('I build WordPress sites, plugins, and other web apps. Shops get a site they can edit. Developers can copy the code. I still do some Power Platform work when it helps.', 'sage')],
            ['home_cta_primary', __('Primary button', 'sage'), 'text', __('See example sites', 'sage')],
            ['home_cta_secondary', __('Secondary button', 'sage'), 'text', __('Let’s work together', 'sage')],
            ['home_link_writing', __('Writing link label', 'sage'), 'text', __('Writing', 'sage')],
            ['home_link_code', __('Code link label', 'sage'), 'text', __('Code and snippets', 'sage')],
            ['home_link_about', __('About link label', 'sage'), 'text', __('About', 'sage')],
            ['home_link_hello', __('Contact link label', 'sage'), 'text', __('Say hello', 'sage')],
        ],
        __('Stack', 'sage') => [
            ['home_stack_kicker', __('Kicker', 'sage'), 'text', __('Tools I ship with', 'sage')],
            ['home_stack_h2', __('Heading', 'sage'), 'text', __('Stack', 'sage')],
            ['home_stack', __('Tools (one per line)', 'sage'), 'lines', ['WordPress', 'Plugins', 'PHP', 'JavaScript', 'React', 'HTML & CSS', 'Git', 'Sage / Blade', 'Power Apps', 'Power Automate']],
        ],
        __('Right now', 'sage') => [
            ['home_now_kicker', __('Kicker', 'sage'), 'text', __('Right now', 'sage')],
            ['home_now_h2', __('Heading', 'sage'), 'text', __('WordPress, plugins, and other web apps.', 'sage')],
            ['home_now_link', __('Now page link', 'sage'), 'text', __('What I’m doing now', 'sage')],
            ['home_link_services', __('Services link', 'sage'), 'text', __('How I can help', 'sage')],
        ],
        __('Writing section', 'sage') => [
            ['home_write_kicker', __('Kicker', 'sage'), 'text', __('Notes from the bench', 'sage')],
            ['home_write_h2', __('Heading', 'sage'), 'text', __('Writing', 'sage')],
            ['home_write_intro', __('Intro', 'sage'), 'textarea', __('Notes on WordPress, plugins, and other web apps. Many posts include snippets you can paste into a theme or a plugin.', 'sage')],
            ['home_write_empty', __('Empty state', 'sage'), 'text', __('New posts will show up here. Categories stay as they are.', 'sage')],
            ['home_write_all', __('All posts label', 'sage'), 'text', __('All posts', 'sage')],
        ],
        __('Code section', 'sage') => [
            ['home_code_kicker', __('Kicker', 'sage'), 'text', __('Public on GitHub', 'sage')],
            ['home_code_h2', __('Heading', 'sage'), 'text', __('Code to borrow', 'sage')],
            ['home_code_intro', __('Intro', 'sage'), 'textarea', __('Public repos on GitHub, plus short snippets. Fork them, copy them, or ask if a line is unclear.', 'sage')],
            ['home_code_more', __('More link label', 'sage'), 'text', __('More repos and snippets', 'sage')],
        ],
        __('Example sites section', 'sage') => [
            ['home_work_kicker', __('Kicker', 'sage'), 'text', __('Studio concepts', 'sage')],
            ['home_work_h2', __('Heading', 'sage'), 'text', __('Example sites', 'sage')],
            ['home_work_intro', __('Intro (basic HTML ok)', 'sage'), 'html', __('Concept work from <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a> for Gettysburg tours, inns, and shops. Useful if you run a local business and want to see what a clear WordPress site can look like.', 'sage')],
            ['home_work_more', __('More link label', 'sage'), 'text', __('All example sites', 'sage')],
        ],
        __('Help section', 'sage') => [
            ['home_help_kicker', __('Kicker', 'sage'), 'text', __('A question is enough', 'sage')],
            ['home_help_h2', __('Heading', 'sage'), 'text', __('If you need a hand', 'sage')],
            ['home_help_p1', __('First paragraph', 'sage'), 'textarea', __('I build WordPress sites, plugins, and other web apps. I still do some Power Platform work when a team already lives in Microsoft 365.', 'sage')],
            ['home_help_p2', __('Second paragraph (basic HTML ok)', 'sage'), 'html', __('Read <a href="/services/">how I can help</a>, or <a href="/contact/">send a note</a>. A question about a post or a snippet is just as welcome as a build request.', 'sage')],
        ],
        __('Who this is for', 'sage') => [
            ['home_who_kicker', __('Kicker', 'sage'), 'text', __('Who this is for', 'sage')],
            ['who_h2', __('Heading', 'sage'), 'text', __('Four doors in', 'sage')],
            ['who_intro', __('Intro', 'sage'), 'textarea', __('Same site. Different starting points.', 'sage')],
            ['who_items', __('Audiences', 'sage'), 'repeater', mh_who_items(), [
                ['title', __('Title', 'sage'), 'text'],
                ['text', __('Text', 'sage'), 'textarea'],
            ]],
        ],
        __('Footer (site-wide)', 'sage') => [
            ['footer_blurb', __('Footer sentence', 'sage'), 'textarea', __('Notes, code, and Gettysburg work. Developers, shops, and agencies are welcome.', 'sage')],
        ],
    ];
}

function page_field_map(): array
{
    $home = mh_home_fields();

    $svcItems = [
        ['title' => __('WordPress sites', 'sage'), 'text' => __('New sites, old sites that need care, and themes you can edit. Plain words. Pages that work.', 'sage')],
        ['title' => __('WordPress plugins', 'sage'), 'text' => __('Small plugins that do one job well. You can read the code. You can change it.', 'sage')],
        ['title' => __('Other web apps', 'sage'), 'text' => __('When WordPress is the wrong tool. React, APIs, and data — like Keepary on GitHub.', 'sage')],
        ['title' => __('Power Platform', 'sage'), 'text' => __('Some Power Apps and Power Automate work, when a spreadsheet should be a small app instead. This is not my main focus.', 'sage')],
    ];

    $placeItems = [
        ['title' => __('matthummel.com', 'sage'), 'text' => __('This site. Writing, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.', 'sage')],
        ['title' => __('Ridges & Valleys', 'sage'), 'text' => __('A Gettysburg studio for Adams County shops, inns, and tours. You own the domain and the hosting.', 'sage'), 'url' => 'https://ridgesandvalleys.com'],
    ];

    $codeRepos = [];
    foreach (mh_featured_repos() as $r) {
        $codeRepos[] = [
            'name' => $r['name'],
            'desc' => $r['desc'],
            'url' => $r['url'],
            'tags' => implode(', ', $r['tags']),
        ];
    }

    $codeSnips = [];
    foreach (mh_code_snippets() as $s) {
        $codeSnips[] = [
            'title' => $s['title'],
            'note' => $s['note'],
            'code' => $s['code'],
        ];
    }

    $workItems = [];
    foreach (mh_studio_projects() as $p) {
        $workItems[] = [
            'slug' => $p['slug'],
            'title' => $p['title'],
            'cat' => $p['cat'],
            'place' => $p['place'],
            'blurb' => $p['blurb'],
            'tech' => implode(', ', $p['tech']),
            'concept' => $p['concept'] ?? '',
            'image' => $p['image'] ?? '',
        ];
    }

    return [
        'front-page.blade.php' => $home,
        'template-home.blade.php' => $home,
        'template-about.blade.php' => [
            __('Intro', 'sage') => [
                ['about_kicker', __('Kicker', 'sage'), 'text', __('About', 'sage')],
                ['about_h1', __('Heading', 'sage'), 'text', __('Glad you’re here.', 'sage')],
                ['about_lede', __('Intro', 'sage'), 'textarea', __('I’m Matt. I live in Gettysburg, Pennsylvania. I write about the web, share code, and sometimes help a shop, a team, or an agency with a WordPress site, a plugin, or another web app. Plain language. Pages that are easy to use.', 'sage')],
            ],
            __('Who this is for', 'sage') => [
                ['who_h2', __('Heading', 'sage'), 'text', __('Who this site is for', 'sage')],
                ['who_intro', __('Intro', 'sage'), 'textarea', __('Developers, people learning the web, shops, and agencies can all use this site. Pick the door that fits.', 'sage')],
                ['who_items', __('Audiences', 'sage'), 'repeater', mh_who_items(), [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('How I got here', 'sage') => [
                ['about_story_h2', __('Heading', 'sage'), 'text', __('How I got here', 'sage')],
                ['about_p1', __('Paragraph 1', 'sage'), 'textarea', __('I started by building WordPress sites for higher-ed marketing teams. That taught me to care about what people need, not just the stack.', 'sage')],
                ['about_p2', __('Paragraph 2', 'sage'), 'textarea', __('Then I learned full-stack work: sites, plugins, and other web apps. I still use Power Platform when it fits. It is not the main thing I do.', 'sage')],
                ['about_p3', __('Paragraph 3', 'sage'), 'textarea', __('On GitHub I keep it short: full-stack developer. WordPress, plugins, and other web apps.', 'sage')],
            ],
            __('Two places', 'sage') => [
                ['about_places_h2', __('Heading', 'sage'), 'text', __('Two places I publish', 'sage')],
                ['about_places', __('Places', 'sage'), 'repeater', $placeItems, [
                    ['title', __('Name', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                    ['url', __('Link (optional)', 'sage'), 'url'],
                ]],
            ],
            __('How I like to work', 'sage') => [
                ['about_values_h2', __('Heading', 'sage'), 'text', __('How I like to work', 'sage')],
                ['about_values', __('List', 'sage'), 'lines', [
                    __('Plain words, about a 6–8 grade reading level.', 'sage'),
                    __('Accessible pages as a default, not a later patch.', 'sage'),
                    __('You can use a keyboard, a phone, or dark mode.', 'sage'),
                    __('I use AI as a helper. I still read every line before it ships.', 'sage'),
                ]],
                ['about_links', __('Links under the list (basic HTML ok)', 'sage'), 'html', __('<a href="/now/">What I’m doing now</a> · <a href="/contact/">Say hello</a>', 'sage')],
            ],
        ],
        'template-now.blade.php' => [
            __('Intro', 'sage') => [
                ['now_kicker', __('Kicker', 'sage'), 'text', __('Now', 'sage')],
                ['now_h1', __('Heading', 'sage'), 'text', __('What I’m doing now.', 'sage')],
                ['now_lede', __('Intro', 'sage'), 'textarea', __('A short list of where my time is going, updated August 2026.', 'sage')],
            ],
            __('List', 'sage') => [
                ['now_items', __('Items', 'sage'), 'lines', [
                    __('Full-stack work: WordPress, plugins, and other web apps.', 'sage'),
                    __('Raising kids in Gettysburg. Nights and weekends are scarce, so I keep extra projects small.', 'sage'),
                    __('This Sage 11 site is a notebook: writing, snippets, and example shops.', 'sage'),
                    __('Sharing notes on this blog, DEV.to, Bluesky, and Reddit.', 'sage'),
                    __('Helping with a few extra builds when I have room — sites, plugins, and sometimes Power Platform.', 'sage'),
                ]],
                ['now_link', __('Link label', 'sage'), 'text', __('Say hello', 'sage')],
            ],
        ],
        'template-services.blade.php' => [
            __('Intro', 'sage') => [
                ['svc_kicker', __('Kicker', 'sage'), 'text', __('Services', 'sage')],
                ['svc_h1', __('Heading', 'sage'), 'text', __('If you want a hand.', 'sage')],
                ['svc_lede', __('Intro', 'sage'), 'textarea', __('Most of this site is free to read and copy. If you need a WordPress site, a plugin, or another web app, you can write. I take a few extra projects at a time. I still do some Power Platform work. It is not my main focus.', 'sage')],
            ],
            __('Who this is for', 'sage') => [
                ['who_h2', __('Heading', 'sage'), 'text', __('Who this site is for', 'sage')],
                ['who_intro', __('Intro', 'sage'), 'textarea', __('Developers, people learning the web, shops, and agencies can all use this site. Pick the door that fits.', 'sage')],
                ['who_items', __('Audiences', 'sage'), 'repeater', mh_who_items(), [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('Ways I can help', 'sage') => [
                ['svc_ways_h2', __('Heading', 'sage'), 'text', __('Ways I can help', 'sage')],
                ['svc_items', __('Cards', 'sage'), 'repeater', $svcItems, [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('How a project usually goes', 'sage') => [
                ['svc_process_h2', __('Heading', 'sage'), 'text', __('How a project usually goes', 'sage')],
                ['svc_process', __('Steps', 'sage'), 'repeater', [
                    ['title' => __('Write', 'sage'), 'text' => __('Tell me who it is for and what is broken or missing. A paragraph is enough.', 'sage')],
                    ['title' => __('Scope', 'sage'), 'text' => __('I send a short list of work, a timeline, and what I will not do (ads, social, ongoing retainers).', 'sage')],
                    ['title' => __('Ship', 'sage'), 'text' => __('You get pages you can edit, notes in plain words, and the repo if the work is public.', 'sage')],
                ], [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('Quick answers', 'sage') => [
                ['svc_faq_h2', __('Heading', 'sage'), 'text', __('Quick answers', 'sage')],
                ['svc_faq', __('Questions', 'sage'), 'repeater', [
                    ['title' => __('Do you take agency overflow?', 'sage'), 'text' => __('Yes, when the work is a real WordPress site, plugin, or other web app. You keep the client. I stay the developer.', 'sage')],
                    ['title' => __('Can I copy the code for free?', 'sage'), 'text' => __('Yes. Public repos and snippets are there to borrow. A note if you ship something with them is kind, not required.', 'sage')],
                    ['title' => __('Do you run ads or social?', 'sage'), 'text' => __('No. Local Gettysburg marketing lives at Ridges & Valleys. This site is for building and sharing.', 'sage')],
                ], [
                    ['title', __('Question', 'sage'), 'text'],
                    ['text', __('Answer', 'sage'), 'textarea'],
                ]],
                ['svc_fair_h2', __('CTA kicker', 'sage'), 'text', __('A fair picture', 'sage')],
                ['svc_fair', __('Paragraph (basic HTML ok)', 'sage'), 'html', __('I don’t run ads or social accounts for clients. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. Here, sharing comes first. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.', 'sage')],
            ],
        ],
        'template-contact.blade.php' => [
            __('Intro', 'sage') => [
                ['cnt_kicker', __('Kicker', 'sage'), 'text', __('Contact', 'sage')],
                ['cnt_h1', __('Heading', 'sage'), 'text', __('Say hello.', 'sage')],
                ['cnt_lede', __('Intro', 'sage'), 'textarea', __('Questions about a post, a snippet, or GitHub are welcome. So is a note about a WordPress site, a plugin, or another web app. I usually reply in one or two business days.', 'sage')],
                ['cnt_who_label', __('Audience label', 'sage'), 'text', __('I am…', 'sage')],
                ['cnt_success', __('Success message', 'sage'), 'text', __('Thanks. I got it and will write back soon.', 'sage')],
                ['cnt_error', __('Error message', 'sage'), 'text', __('Something went wrong. Check the fields and try again.', 'sage')],
                ['cnt_submit', __('Submit button', 'sage'), 'text', __('Send hello', 'sage')],
            ],
            __('Elsewhere', 'sage') => [
                ['cnt_else_h2', __('Heading', 'sage'), 'text', __('Find me elsewhere', 'sage')],
                ['cnt_aside', __('Aside', 'sage'), 'textarea', __('Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is the Gettysburg studio site.', 'sage')],
            ],
        ],
        'template-code.blade.php' => [
            __('Intro', 'sage') => [
                ['code_kicker', __('Kicker', 'sage'), 'text', __('Code', 'sage')],
                ['code_h1', __('Heading', 'sage'), 'text', __('Code you can copy.', 'sage')],
                ['code_lede', __('Intro (basic HTML ok)', 'sage'), 'html', __('Repos and short snippets. If you’re new to WordPress or Sage, start with the snippets, then open a repo and read the README. Agencies and shops can treat this as a sample of how I write. Questions are welcome on the <a href="/contact/">contact</a> page.', 'sage')],
            ],
            __('Featured repos', 'sage') => [
                ['code_feat_h2', __('Heading', 'sage'), 'text', __('Featured repos', 'sage')],
                ['code_feat_intro', __('Intro', 'sage'), 'text', __('Open these on GitHub. Fork them if they help.', 'sage')],
                ['code_repos', __('Repos', 'sage'), 'repeater', $codeRepos, [
                    ['name', __('Name', 'sage'), 'text'],
                    ['desc', __('Description', 'sage'), 'textarea'],
                    ['url', __('URL', 'sage'), 'url'],
                    ['tags', __('Tags (comma separated)', 'sage'), 'text'],
                ]],
            ],
            __('Live GitHub', 'sage') => [
                ['code_live_h2', __('Heading', 'sage'), 'text', __('Live from GitHub', 'sage')],
                ['code_live_all', __('All repos label', 'sage'), 'text', __('All public repos', 'sage')],
            ],
            __('Snippets', 'sage') => [
                ['code_snip_h2', __('Heading', 'sage'), 'text', __('Snippets', 'sage')],
                ['code_snip_intro', __('Intro', 'sage'), 'textarea', __('Tiny examples, the same style I drop into blog posts. Copy them into a post, a theme, or a gist. Change the names so they match your project. Sharing is the point.', 'sage')],
                ['code_snips', __('Snippets', 'sage'), 'repeater', $codeSnips, [
                    ['title', __('Title', 'sage'), 'text'],
                    ['note', __('Note', 'sage'), 'textarea'],
                    ['code', __('Code', 'sage'), 'textarea'],
                ]],
            ],
        ],
        'template-projects.blade.php' => [
            __('Intro', 'sage') => [
                ['work_kicker', __('Kicker', 'sage'), 'text', __('Work', 'sage')],
                ['work_h1', __('Heading', 'sage'), 'text', __('Example sites.', 'sage')],
                ['work_lede', __('Intro', 'sage'), 'textarea', __('Studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Business owners can picture a real WordPress shape. Developers can see how the pieces fit. Agencies can use them as a reference when a client needs a local site.', 'sage')],
                ['work_foot', __('Footer line (basic HTML ok)', 'sage'), 'html', __('Repos and snippets: <a href="/code/">Code</a>. Studio site: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.', 'sage')],
            ],
            __('Example sites', 'sage') => [
                ['work_items', __('Sites', 'sage'), 'repeater', $workItems, [
                    ['slug', __('Slug', 'sage'), 'text'],
                    ['title', __('Title', 'sage'), 'text'],
                    ['cat', __('Type', 'sage'), 'text'],
                    ['place', __('Place', 'sage'), 'text'],
                    ['blurb', __('Blurb', 'sage'), 'textarea'],
                    ['tech', __('Tech (comma separated)', 'sage'), 'text'],
                    ['concept', __('Concept page URL', 'sage'), 'url'],
                    ['image', __('Screenshot file or URL', 'sage'), 'text'],
                ]],
            ],
        ],
        'index.blade.php' => [
            __('Intro', 'sage') => [
                ['write_kicker', __('Kicker', 'sage'), 'text', __('Writing', 'sage')],
                ['write_h1', __('Heading', 'sage'), 'text', __('Writing, with snippets when they help.', 'sage')],
                ['write_lede', __('Intro', 'sage'), 'textarea', __('Notes on WordPress, plugins, and other web apps. Developers can copy the examples. Shops and agencies can see how I explain a build. I also write on DEV.to.', 'sage')],
                ['write_devto_h2', __('DEV.to heading', 'sage'), 'text', __('Also on DEV.to', 'sage')],
                ['write_follow', __('Follow line', 'sage'), 'text', __('Follow along:', 'sage')],
                ['write_share_note', __('Note under each post', 'sage'), 'html', __('Extra copy-paste examples live on the <a href="/code/">Code</a> page. You’re welcome to reuse them. Questions about a snippet? <a href="/contact/">Say hello</a>.', 'sage')],
                ['write_bio', __('Default author bio', 'sage'), 'textarea', __('I write notes from Gettysburg, Pennsylvania, and share WordPress, plugin, and other web-app snippets you can paste in. Developers, shops, and agencies are welcome here.', 'sage')],
            ],
        ],
    ];
}

function mh_code_page_repos(?int $post_id = null): array
{
    $rows = field_rows('code_repos', [], $post_id);
    if ($rows === []) {
        return mh_featured_repos();
    }
    $out = [];
    foreach ($rows as $r) {
        $tags = $r['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
        }
        $out[] = [
            'name' => (string) ($r['name'] ?? ''),
            'desc' => (string) ($r['desc'] ?? ''),
            'url' => (string) ($r['url'] ?? ''),
            'tags' => $tags,
        ];
    }

    return $out;
}

function mh_code_page_snips(?int $post_id = null): array
{
    $rows = field_rows('code_snips', [], $post_id);
    if ($rows === []) {
        return mh_code_snippets();
    }
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'title' => (string) ($r['title'] ?? ''),
            'note' => (string) ($r['note'] ?? ''),
            'code' => (string) ($r['code'] ?? ''),
            'lang' => 'text',
        ];
    }

    return $out;
}

function mh_work_page_items(?int $post_id = null): array
{
    $defaults = [];
    foreach (mh_studio_projects() as $p) {
        $defaults[$p['slug']] = $p;
    }

    $rows = field_rows('work_items', [], $post_id);
    if ($rows === []) {
        return mh_studio_projects();
    }
    $out = [];
    foreach ($rows as $r) {
        $tech = $r['tech'] ?? [];
        if (is_string($tech)) {
            $tech = array_values(array_filter(array_map('trim', explode(',', $tech))));
        }
        $slug = sanitize_title((string) ($r['slug'] ?? $r['title'] ?? ''));
        $base = $defaults[$slug] ?? [];
        $out[] = [
            'slug' => $slug,
            'title' => (string) (($r['title'] ?? '') !== '' ? $r['title'] : ($base['title'] ?? '')),
            'cat' => (string) (($r['cat'] ?? '') !== '' ? $r['cat'] : ($base['cat'] ?? '')),
            'place' => (string) (($r['place'] ?? '') !== '' ? $r['place'] : ($base['place'] ?? '')),
            'blurb' => (string) (($r['blurb'] ?? '') !== '' ? $r['blurb'] : ($base['blurb'] ?? '')),
            'tech' => $tech !== [] ? $tech : ($base['tech'] ?? []),
            'concept' => (string) (($r['concept'] ?? '') !== '' ? $r['concept'] : ($base['concept'] ?? '')),
            'image' => (string) (($r['image'] ?? '') !== '' ? $r['image'] : ($base['image'] ?? '')),
        ];
    }

    return $out;
}

function mh_front_id(): int
{
    return (int) get_option('page_on_front');
}

function mh_writing_id(): int
{
    return (int) get_option('page_for_posts');
}

function field_group_hint(string $label): string
{
    $hints = [
        __('Hero', 'sage') => __('Top of the home page, next to the photo.', 'sage'),
        __('Footer (site-wide)', 'sage') => __('The sentence in the site footer. Edited on Home so every page stays in sync.', 'sage'),
        __('Example sites', 'sage') => __('Each row is one concept site. Empty the list to restore the built-in set.', 'sage'),
        __('Snippets', 'sage') => __('Copy-paste examples shown on the Code page and a few on Home. Empty the list to restore the built-in set.', 'sage'),
        __('Featured repos', 'sage') => __('Hand-picked GitHub projects. Empty the list to restore the built-in set.', 'sage'),
    ];

    return $hints[$label] ?? '';
}

function render_rep_row(string $name, $index, array $sub, array $row): string
{
    $h = '<div class="mh-rep-row">';
    $h .= '<div class="mh-rep-tools">';
    $h .= '<button type="button" class="button-link mh-rep-up" title="'.esc_attr__('Move up', 'sage').'">↑</button>';
    $h .= '<button type="button" class="button-link mh-rep-down" title="'.esc_attr__('Move down', 'sage').'">↓</button>';
    $h .= '<button type="button" class="button-link mh-rep-del" title="'.esc_attr__('Remove', 'sage').'">✕</button>';
    $h .= '</div><div class="mh-rep-fields">';

    $heading = trim((string) ($row['name'] ?? $row['title'] ?? ''));
    if ($heading !== '') {
        $h .= '<p class="mh-rep-title">'.esc_html($heading).'</p>';
    }

    foreach ($sub as $sf) {
        $sk = $sf[0];
        $slabel = $sf[1];
        $stype = $sf[2] ?? 'text';
        $fname = $name.'['.$index.']['.$sk.']';
        $val = $row[$sk] ?? '';
        $wide = in_array($stype, ['textarea', 'lines'], true);

        $h .= '<div class="mh-rep-field'.($wide ? ' mh-rep-field-wide' : '').'">';
        $h .= '<label>'.esc_html($slabel).'</label>';
        if ($stype === 'textarea') {
            $h .= '<textarea name="'.esc_attr($fname).'" rows="2">'.esc_textarea((string) $val).'</textarea>';
        } elseif ($stype === 'url') {
            $h .= '<input type="url" name="'.esc_attr($fname).'" value="'.esc_attr((string) $val).'">';
        } else {
            $h .= '<input type="text" name="'.esc_attr($fname).'" value="'.esc_attr((string) $val).'">';
        }
        $h .= '</div>';
    }

    return $h.'</div></div>';
}

add_action('add_meta_boxes', function () {
    add_meta_box('mh_page_content', __('Page content (theme)', 'sage'), __NAMESPACE__.'\\render_page_fields_box', 'page', 'normal', 'high');
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'page') {
        return;
    }
    $rel = 'resources/js/admin-repeater.js';
    $path = get_theme_file_path($rel);
    wp_enqueue_script('mh-admin-repeater', get_theme_file_uri($rel), [], file_exists($path) ? (string) filemtime($path) : '1', true);
});

function render_page_fields_box(\WP_Post $post): void
{
    $key = page_template_key($post->ID);
    $map = page_field_map();

    if (empty($map[$key])) {
        echo '<p>'.esc_html__('This page uses the default template. Choose Home, About, Work, Services, Code, Contact, Now, or Writing to edit theme fields here.', 'sage').'</p>';

        return;
    }

    wp_nonce_field('mh_page_fields', 'mh_page_fields_nonce');

    echo '<style>
        .mh-fields h4{margin:1.4em 0 .3em}
        .mh-fields label{display:block;font-weight:600;margin:.9em 0 .25em;font-size:13px}
        .mh-fields input[type=text],.mh-fields input[type=url],.mh-fields textarea,.mh-fields select{width:100%}
        .mh-fields textarea{min-height:56px}
        .mh-fields .mh-desc,.mh-fields .mh-ghint{color:#646970;font-size:12px;line-height:1.5}
        .mh-pf-acc{border:1px solid #dcdcde;border-radius:6px;margin:.55em 0;background:#fff}
        .mh-pf-acc>summary{cursor:pointer;padding:.7em .85em;font-size:12px;text-transform:uppercase;letter-spacing:.06em;font-weight:600;background:#f6f7f7}
        .mh-pf-acc-b{padding:.1em .9em .9em}
        .mh-rep{margin:.4em 0}
        .mh-rep-rows{display:flex;flex-direction:column;gap:.7rem}
        .mh-rep-row{display:flex;gap:.6rem;border:1px solid #dcdcde;border-left:4px solid #c3c4c7;border-radius:6px;background:#fbfbfc;padding:.7rem .8rem}
        .mh-rep-tools{display:flex;flex-direction:column;gap:.15rem}
        .mh-rep-fields{flex:1;display:grid;grid-template-columns:1fr 1fr;gap:.15rem .75rem}
        .mh-rep-field-wide{grid-column:1/-1}
        .mh-rep-title{grid-column:1/-1;margin:0;font-weight:700}
        .mh-rep-add{margin-top:.7rem!important}
    </style>';

    echo '<div class="mh-fields"><p class="mh-desc">'.esc_html__('Grey placeholder text is what the page uses if you leave a box empty. Type to replace it. Repeaters: add, reorder, or remove rows. Clearing every row restores the built-in list.', 'sage').'</p>';

    $i = 0;
    foreach ($map[$key] as $group => $fields) {
        echo '<details class="mh-pf-acc"'.($i === 0 ? ' open' : '').'>';
        echo '<summary>'.esc_html($group).'</summary><div class="mh-pf-acc-b">';
        $i++;
        if ($hint = field_group_hint($group)) {
            echo '<p class="mh-ghint">'.esc_html($hint).'</p>';
        }
        foreach ($fields as $f) {
            $k = $f[0];
            $label = $f[1];
            $type = $f[2];
            $place = $f[3] ?? '';
            $name = 'mh_f_'.$k;
            $val = get_post_meta($post->ID, $name, true);
            printf('<label for="%1$s">%2$s</label>', esc_attr($name), esc_html($label));
            switch ($type) {
                case 'textarea':
                    printf('<textarea id="%1$s" name="%1$s" rows="3" placeholder="%3$s">%2$s</textarea>', esc_attr($name), esc_textarea((string) $val), esc_attr((string) $place));
                    break;
                case 'html':
                    printf('<textarea id="%1$s" name="%1$s" rows="3" placeholder="%3$s">%2$s</textarea>', esc_attr($name), esc_textarea((string) $val), esc_attr((string) $place));
                    echo '<p class="mh-ghint">'.esc_html__('Basic HTML is allowed (links, strong).', 'sage').'</p>';
                    break;
                case 'url':
                    printf('<input type="url" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s">', esc_attr($name), esc_attr((string) $val), esc_attr((string) $place));
                    break;
                case 'lines':
                    $current = is_array($val) ? $val : (is_array($place) ? $place : []);
                    printf('<textarea id="%1$s" name="%1$s" rows="%3$d" class="mh-lines">%2$s</textarea>', esc_attr($name), esc_textarea(implode("\n", array_map('strval', $current))), max(3, count($current) + 1));
                    echo '<p class="mh-ghint">'.esc_html__('One item per line.', 'sage').'</p>';
                    break;
                case 'repeater':
                    $sub = $f[4] ?? [];
                    $rows = is_array($val) && $val !== [] ? array_values($val) : (is_array($place) ? $place : []);
                    echo '<div class="mh-rep" data-rep-name="'.esc_attr($name).'">';
                    echo '<div class="mh-rep-rows">';
                    foreach ($rows as $ri => $row) {
                        echo render_rep_row($name, (int) $ri, $sub, is_array($row) ? $row : []);
                    }
                    echo '</div>';
                    echo '<template class="mh-rep-tpl">'.render_rep_row($name, '__i__', $sub, []).'</template>';
                    echo '<button type="button" class="button mh-rep-add">＋ '.esc_html__('Add item', 'sage').'</button>';
                    echo '</div>';
                    break;
                default:
                    printf('<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s">', esc_attr($name), esc_attr((string) $val), esc_attr((string) $place));
            }
        }
        echo '</div></details>';
    }
    echo '</div>';
}

add_action('save_post_page', function ($post_id) {
    if (! isset($_POST['mh_page_fields_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_page_fields_nonce'])), 'mh_page_fields')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_page', $post_id)) {
        return;
    }

    $key = page_template_key((int) $post_id);
    if (isset($_POST['page_template']) && $_POST['page_template'] !== 'default') {
        $key = sanitize_text_field(wp_unslash($_POST['page_template']));
    }

    $map = page_field_map();
    if (empty($map[$key])) {
        return;
    }

    foreach ($map[$key] as $fields) {
        foreach ($fields as $f) {
            $k = $f[0];
            $type = $f[2];
            $name = 'mh_f_'.$k;

            if ($type === 'lines') {
                $raw = isset($_POST[$name]) ? (string) wp_unslash($_POST[$name]) : '';
                $items = array_values(array_filter(array_map(
                    static fn ($s) => sanitize_text_field(trim($s)),
                    preg_split('/\r\n|\r|\n/', $raw)
                ), static fn ($s) => $s !== ''));
                if ($items) {
                    update_post_meta($post_id, $name, $items);
                } else {
                    delete_post_meta($post_id, $name);
                }

                continue;
            }

            if ($type === 'repeater') {
                $sub = $f[4] ?? [];
                $rawRows = (isset($_POST[$name]) && is_array($_POST[$name])) ? wp_unslash($_POST[$name]) : [];
                $clean = [];
                foreach ($rawRows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $cleanRow = [];
                    $hasValue = false;
                    foreach ($sub as $sf) {
                        $sk = $sf[0];
                        $st = $sf[2] ?? 'text';
                        $sv = $row[$sk] ?? '';
                        if ($st === 'textarea') {
                            $cleanRow[$sk] = sanitize_textarea_field((string) $sv);
                        } elseif ($st === 'url') {
                            $cleanRow[$sk] = esc_url_raw(trim((string) $sv));
                        } else {
                            $cleanRow[$sk] = sanitize_text_field((string) $sv);
                        }
                        if (trim((string) $cleanRow[$sk]) !== '') {
                            $hasValue = true;
                        }
                    }
                    if ($hasValue) {
                        $clean[] = $cleanRow;
                    }
                }
                if ($clean) {
                    update_post_meta($post_id, $name, $clean);
                } else {
                    delete_post_meta($post_id, $name);
                }

                continue;
            }

            if (! isset($_POST[$name])) {
                continue;
            }
            $raw = wp_unslash($_POST[$name]);
            $val = match ($type) {
                'textarea' => sanitize_textarea_field($raw),
                'html' => wp_kses_post((string) $raw),
                'url' => esc_url_raw(trim((string) $raw)),
                default => sanitize_text_field($raw),
            };
            if ($val === '') {
                delete_post_meta($post_id, $name);
            } else {
                update_post_meta($post_id, $name, $val);
            }
        }
    }
});
