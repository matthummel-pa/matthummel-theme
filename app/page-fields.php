<?php

/**
 * Editable page content — same idea as Ridges & Valleys.
 *
 * A “Page content (theme)” meta box shows the fields for the page’s template.
 * Leave a box empty to keep the built-in default. Values live in post meta
 * `mh_f_{key}` and Blade reads them with \App\field('key', $default).
 */

namespace App;

/**
 * Retrieve a single theme page field value from post meta.
 *
 * Reads `mh_f_{key}` post meta and returns the default when the meta is absent or empty.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (without the mh_f_ prefix).
 * @param  string  $default  Value returned when the field is empty or the post cannot be resolved.
 * @param  int|null  $post_id  Post ID; falls back to get_the_ID() when null.
 */
function field(string $key, string $default = '', ?int $post_id = null): string
{
    $post_id = $post_id ?: (int) get_the_ID();
    if (! $post_id) {
        return $default;
    }
    $val = get_post_meta($post_id, 'mh_f_'.$key, true);

    return ($val === '' || $val === null) ? $default : (string) $val;
}

/**
 * Retrieve a theme page field as a fully qualified URL.
 *
 * Relative paths are resolved with home_url(). Absolute URLs (http/https/protocol-relative) are returned as-is.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (without the mh_f_ prefix).
 * @param  string  $default  Fallback URL or path.
 * @param  int|null  $post_id  Post ID; falls back to get_the_ID() when null.
 */
function field_href(string $key, string $default = '', ?int $post_id = null): string
{
    $value = trim(field($key, $default, $post_id));
    if ($value === '') {
        $value = $default;
    }
    if (preg_match('#^(https?:)?//#i', $value) === 1) {
        return $value;
    }

    return home_url($value);
}

/**
 * Retrieve a theme page field as post-kses-filtered HTML.
 *
 * Safe to echo directly; untrusted tags are stripped by wp_kses_post().
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (without the mh_f_ prefix).
 * @param  string  $default  Fallback HTML string.
 * @param  int|null  $post_id  Post ID; falls back to get_the_ID() when null.
 */
function field_html(string $key, string $default = '', ?int $post_id = null): string
{
    return wp_kses_post(field($key, $default, $post_id));
}

/**
 * Retrieve a theme page field as a list of non-empty trimmed lines.
 *
 * Accepts both newline-delimited strings and serialised arrays from post meta.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (without the mh_f_ prefix).
 * @param  string[]  $default  Fallback list returned when the field is absent or blank.
 * @param  int|null  $post_id  Post ID; falls back to get_the_ID() when null.
 * @return list<string>
 */
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

/**
 * Retrieve a theme page field as an array of repeater rows.
 *
 * Returns the default when the meta value is absent or not a non-empty array.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (without the mh_f_ prefix).
 * @param  array[]  $default  Fallback row list.
 * @param  int|null  $post_id  Post ID; falls back to get_the_ID() when null.
 * @return array[]
 */
function field_rows(string $key, array $default = [], ?int $post_id = null): array
{
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = $post_id ? get_post_meta($post_id, 'mh_f_'.$key, true) : '';
    $rows = (is_array($raw) && $raw !== []) ? array_values($raw) : $default;

    return is_array($rows) && $rows !== [] ? $rows : $default;
}

/**
 * Resolve the Blade template filename key for a given page ID.
 *
 * Returns 'front-page.blade.php' for the static front page,
 * 'index.blade.php' for the posts page, and the stored _wp_page_template otherwise.
 *
 * @since 3.1.0
 *
 * @param  int  $post_id  Page post ID.
 * @return string Template filename, e.g. 'template-about.blade.php'.
 */
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

/**
 * Field group definition for the "Search preview" meta box section.
 *
 * Returns an associative array of group label → field definitions, compatible with page_field_map().
 *
 * @since 3.1.0
 *
 * @return array<string, list<array{0: string, 1: string, 2: string, 3: string}>>
 */
function mh_seo_field_group(): array
{
    return [
        __('Search preview', 'sage') => [
            ['seo_title', __('Document title (under 60 characters)', 'sage'), 'text', ''],
            ['seo_desc', __('Meta description (under 155 characters)', 'sage'), 'textarea', ''],
        ],
    ];
}

/**
 * Field group definitions for the Home/front-page template.
 *
 * @since 3.1.0
 *
 * @return array<string, list<array>>
 */
function mh_home_fields(): array
{
    return [
        __('Hero', 'sage') => [
            ['home_kicker', __('Kicker (above name)', 'sage'), 'text', __('Gettysburg, PA', 'sage')],
            ['home_h1', __('Heading', 'sage'), 'text', __('Matt Hummel', 'sage')],
            ['home_role', __('Role line', 'sage'), 'text', __('Full-stack web development, with WordPress at the center.', 'sage')],
            ['home_lede', __('Intro', 'sage'), 'textarea', __('I build custom WordPress platforms and web applications with PHP, JavaScript, React, and APIs. Businesses get software they own; agencies get clean code built for a confident handoff.', 'sage')],
            ['home_cta_primary', __('Primary button label', 'sage'), 'text', __('Start a conversation', 'sage')],
            ['home_cta_primary_url', __('Primary button path or URL', 'sage'), 'text', '/contact/'],
            ['home_cta_secondary', __('Secondary button label', 'sage'), 'text', __('Explore projects', 'sage')],
            ['home_cta_secondary_url', __('Secondary button path or URL', 'sage'), 'text', '/projects/'],
            ['home_link_writing', __('Journal link label', 'sage'), 'text', __('Journal', 'sage')],
            ['home_link_code', __('Code link label', 'sage'), 'text', __('Code', 'sage')],
            ['home_link_about', __('About link label', 'sage'), 'text', __('About', 'sage')],
            ['home_link_hello', __('Contact link label', 'sage'), 'text', __('Say hello', 'sage')],
        ],
        __('What I build', 'sage') => [
            ['home_build_h2', __('Section label', 'sage'), 'text', __('What I build', 'sage')],
            ['home_build_1_title', __('Service 1 title', 'sage'), 'text', __('WordPress sites', 'sage')],
            ['home_build_1_text', __('Service 1 description', 'sage'), 'textarea', __('Clean, fast, and editable. Shops get something they own â not a subscription they rent.', 'sage')],
            ['home_build_2_title', __('Service 2 title', 'sage'), 'text', __('Plugins & tools', 'sage')],
            ['home_build_2_text', __('Service 2 description', 'sage'), 'textarea', __('Custom PHP when WordPress needs a new part. Small, focused, and readable.', 'sage')],
            ['home_build_3_title', __('Service 3 title', 'sage'), 'text', __('Full-stack web apps', 'sage')],
            ['home_build_3_text', __('Service 3 description', 'sage'), 'textarea', __('React interfaces, PHP or Node services, authentication, databases, and APIs built as one maintainable system.', 'sage')],
        ],
        __('Journal section', 'sage') => [
            ['home_write_h2', __('Heading', 'sage'), 'text', __('From the journal', 'sage')],
            ['home_write_empty', __('Empty state', 'sage'), 'text', __('New posts coming soon.', 'sage')],
        ],
        __('Example sites section', 'sage') => [
            ['home_work_h2', __('Heading', 'sage'), 'text', __('WordPress sites for Gettysburg shops, tours, and inns.', 'sage')],
            ['home_work_intro', __('Intro sentence', 'sage'), 'textarea', __('Studio concepts from Ridges & Valleys — working demonstrations of what a WordPress site can look like for local Gettysburg businesses in Adams County, PA.', 'sage')],
        ],
        __('About strip', 'sage') => [
            ['home_about_h2', __('Heading', 'sage'), 'text', __('Based in Gettysburg, PA.', 'sage')],
            ['home_about_text', __('Bio text', 'sage'), 'textarea', __('I\'ve spent more than 15 years building for the web, from accessible front ends to PHP applications, APIs, and deployment workflows. WordPress is my specialty because it combines a flexible development platform with an editor businesses can actually use.', 'sage')],
        ],
        __('Process section', 'sage') => [
            ['home_process_h2', __('Heading', 'sage'), 'text', __('How a project goes.', 'sage')],
            ['home_process_note', __('Note (HTML ok)', 'sage'), 'html', __('No ongoing contracts unless you want one. A question about a post is just as welcome as a <a href="/contact/">build request</a>.', 'sage')],
        ],
        __('About strip extra', 'sage') => [
            ['home_about_p2', __('Second paragraph', 'sage'), 'textarea', __("Most of my public code is on GitHub. Snippets go on the journal. If something helped you, you don't need to ask permission to use it.", 'sage')],
        ],
        __('Availability', 'sage') => [
            ['home_avail_status', __('Status line', 'sage'), 'text', __('Open to new projects', 'sage')],
        ],
        __('Help section', 'sage') => [
            ['home_help_h2', __('Heading', 'sage'), 'text', __('Working on something?', 'sage')],
            ['home_help_p1', __('First paragraph', 'sage'), 'textarea', __("I build WordPress sites and plugins from Gettysburg, PA. I've done Power Platform work when a team runs on Microsoft 365, but WordPress is what I reach for.", 'sage')],
            ['home_help_p2', __('Second paragraph (basic HTML ok)', 'sage'), 'html', __('Say hello. A question about a post is just as welcome as a project inquiry.', 'sage')],
        ],
        __('Footer (site-wide)', 'sage') => [
            ['footer_blurb', __('Footer sentence', 'sage'), 'textarea', __('Notes, code, and Gettysburg work. Developers, shops, and agencies are welcome.', 'sage')],
        ],
    ];
}

function page_field_map(): array
{
    $home = array_merge(mh_home_fields(), mh_seo_field_group());
    $seo = mh_seo_field_group();

    $svcItems = [
        ['title' => __('WordPress sites', 'sage'), 'text' => __('New sites, old sites that need care, and themes you can edit. Plain words. Pages that work.', 'sage')],
        ['title' => __('WordPress plugins', 'sage'), 'text' => __('Small plugins that do one job well. You can read the code. You can change it.', 'sage')],
        ['title' => __('Full-stack web applications', 'sage'), 'text' => __('React interfaces, PHP or Node services, authentication, databases, and APIs — like Keepary on GitHub.', 'sage')],
        ['title' => __('Integrations and APIs', 'sage'), 'text' => __('Connections between WordPress, business systems, and third-party services, built for reliable handoffs.', 'sage')],
    ];

    $placeItems = [
        ['title' => __('matthummel.com', 'sage'), 'text' => __('This site. A journal, public code, snippets, and a quiet way to say hello. Built so you can learn or copy without a sales funnel.', 'sage')],
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

    $map = [
        'front-page.blade.php' => $home,
        'template-home.blade.php' => $home,
        'template-about.blade.php' => [
            __('Intro', 'sage') => [
                ['about_kicker', __('Kicker', 'sage'), 'text', __('Matt Hummel', 'sage')],
                ['about_h1', __('Heading', 'sage'), 'text', __('Full-stack developer. WordPress specialist.', 'sage')],
                ['about_lede', __('Intro', 'sage'), 'textarea', __('I build accessible, maintainable web software from front end to back end, with deep experience in custom WordPress development.', 'sage')],
            ],
            __('How I got here', 'sage') => [
                ['about_story_h2', __('Heading', 'sage'), 'text', __('How I got here.', 'sage')],
                ['about_p1', __('Paragraph 1', 'sage'), 'textarea', __('I started on the web in higher-ed marketing — landing pages, content updates, and figuring out why a page that looked fine still wasn’t getting clicks. That taught me more about what people need than any course or tool.', 'sage')],
                ['about_p2', __('Paragraph 2', 'sage'), 'textarea', __('WordPress is the tool I kept coming back to. Most shops need a site they can edit themselves: update hours, add a product, fix a typo, without waiting on a developer. That still matters to me.', 'sage')],
                ['about_p3', __('Paragraph 3', 'sage'), 'textarea', __('I started Ridges & Valleys as a WordPress studio for Gettysburg shops, tours, and inns. Alongside that studio work, I’m open for full-time, contract, or project-based roles.', 'sage')],
                ['about_p4', __('Paragraph 4', 'sage'), 'textarea', __('Most of my public code is on GitHub. Shorter notes go on the journal. If something helps you, use it — you don’t need to ask.', 'sage')],
                ['about_story_cta', __('Primary button', 'sage'), 'text', __('Say hello', 'sage')],
                ['about_story_now', __('Secondary link', 'sage'), 'text', __('What I\'m doing now', 'sage')],
            ],
            __('What I build', 'sage') => [
                ['about_services_h2', __('Heading', 'sage'), 'text', __('What I build.', 'sage')],
                ['about_services_intro', __('Intro', 'sage'), 'textarea', __('I work across the stack: accessible interfaces, WordPress and PHP back ends, React applications, APIs, databases, and deployment. WordPress is the specialty, not the limit.', 'sage')],
                ['about_services', __('Service cards', 'sage'), 'repeater', mh_about_services_defaults(), [
                    ['icon', __('Icon key', 'sage'), 'text'],
                    ['title', __('Title', 'sage'), 'text'],
                    ['body', __('Body', 'sage'), 'textarea'],
                ]],
                ['about_services_note', __('Footer note (basic HTML ok)', 'sage'), 'html', __('Curious about a specific project type? <a href="/contact/">Write a note</a>.', 'sage')],
            ],
            __('Open for work', 'sage') => [
                ['about_work_h2', __('Heading', 'sage'), 'text', __('Open for work.', 'sage')],
                ['about_work_p1', __('Paragraph 1', 'sage'), 'textarea', __('I’m looking for new work alongside the studio — full-time roles, contract gigs, and freelance projects. Based in Gettysburg, PA, and happy to work remote.', 'sage')],
                ['about_work_p2', __('Paragraph 2', 'sage'), 'textarea', __('If you’re hiring a full-stack developer, need an experienced WordPress specialist, want agency overflow support, or have a web project to discuss, send a short note about what you’re working on.', 'sage')],
                ['about_work_cta', __('Button label', 'sage'), 'text', __('Start a conversation', 'sage')],
                ['about_work_types', __('Arrangements', 'sage'), 'repeater', mh_about_work_types_defaults(), [
                    ['title', __('Title', 'sage'), 'text'],
                    ['detail', __('Detail', 'sage'), 'textarea'],
                ]],
            ],
            __('How I work', 'sage') => [
                ['about_values_h2', __('Heading', 'sage'), 'text', __('How I work.', 'sage')],
                ['about_values_intro', __('Intro', 'sage'), 'textarea', __('A few habits that stick on WordPress projects from Gettysburg — you own the site, you can edit it, and another developer can follow the code.', 'sage')],
                ['about_approach', __('Principles', 'sage'), 'repeater', mh_about_approach_defaults(), [
                    ['icon', __('Icon key', 'sage'), 'text'],
                    ['title', __('Title', 'sage'), 'text'],
                    ['body', __('Body', 'sage'), 'textarea'],
                ]],
            ],
            __('Journal', 'sage') => [
                ['about_posts_h2', __('Heading', 'sage'), 'text', __('Recent posts.', 'sage')],
                ['about_posts_all', __('All posts label', 'sage'), 'text', __('All posts', 'sage')],
            ],
            __('Elsewhere', 'sage') => [
                ['about_elsewhere_h2', __('Heading', 'sage'), 'text', __('Where to find me.', 'sage')],
                ['about_elsewhere_intro', __('Intro', 'sage'), 'textarea', __('Most of my WordPress code and writing shows up here and on GitHub. RSS is the calmest way to follow along from Gettysburg.', 'sage')],
            ],
            __('Call to action', 'sage') => [
                ['about_cta_kicker', __('Kicker', 'sage'), 'text', __('Get in touch', 'sage')],
                ['about_cta_h2', __('Heading', 'sage'), 'text', __('Need a full-stack or WordPress development partner?', 'sage')],
                ['about_cta_lede', __('Intro', 'sage'), 'textarea', __('Got a question about a post, a project, or a role? Send it over. I usually reply within a day.', 'sage')],
                ['about_cta_btn', __('Button label', 'sage'), 'text', __('Write a note', 'sage')],
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
                    __('This Sage 11 site is a notebook: a journal, snippets, and example shops.', 'sage'),
                    __('Sharing notes on this blog, DEV.to, Bluesky, and Reddit.', 'sage'),
                    __('Helping with a few extra builds when I have room — WordPress platforms, plugins, integrations, and full-stack applications.', 'sage'),
                ]],
                ['now_link', __('Link label', 'sage'), 'text', __('Say hello', 'sage')],
            ],
        ],
        'template-services.blade.php' => [
            __('Intro', 'sage') => [
                ['svc_kicker', __('Kicker', 'sage'), 'text', __('Full-stack developer · WordPress specialist', 'sage')],
                ['svc_h1', __('Heading', 'sage'), 'text', __('Full-stack web development for businesses, agencies, and developers.', 'sage')],
                ['svc_lede', __('Intro', 'sage'), 'textarea', __('Custom WordPress platforms, plugins, integrations, and web applications built with clear scope and clean handoffs. I work directly with businesses, partner quietly with agencies, and collaborate with development teams.', 'sage')],
            ],
            __('Who this is for', 'sage') => [
                ['who_h2', __('Heading', 'sage'), 'text', __('Who this site is for', 'sage')],
                ['who_intro', __('Intro', 'sage'), 'textarea', __('Developers, people learning the web, shops, and agencies can all use this site. Pick the door that fits.', 'sage')],
                ['who_items', __('Audiences', 'sage'), 'repeater', mh_who_items(), mh_who_item_fields()],
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
                    ['title' => __('Scope', 'sage'), 'text' => __('I send a short list of work, a timeline, and what I will not do (ads, social, ongoing support contracts).', 'sage')],
                    ['title' => __('Ship', 'sage'), 'text' => __('You get pages you can edit, notes in plain words, and the repo if the work is public.', 'sage')],
                ], [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('Quick answers', 'sage') => [
                ['svc_faq_h2', __('Heading', 'sage'), 'text', __('Quick answers', 'sage')],
                ['svc_faq', __('Questions', 'sage'), 'repeater', [
                    ['title' => __('Do you take agency overflow?', 'sage'), 'text' => __('Yes, when the work is a real WordPress site, plugin, or other web app. You keep the relationship. I stay the developer.', 'sage')],
                    ['title' => __('Can I copy the code for free?', 'sage'), 'text' => __('Yes. Public repos and snippets are there to borrow. A note if you ship something with them is kind, not required.', 'sage')],
                    ['title' => __('Do you run ads or social?', 'sage'), 'text' => __('No. Local Gettysburg marketing lives at Ridges & Valleys. This site is for building and sharing.', 'sage')],
                ], [
                    ['title', __('Question', 'sage'), 'text'],
                    ['text', __('Answer', 'sage'), 'textarea'],
                ]],
                ['svc_fair_h2', __('CTA kicker', 'sage'), 'text', __('A fair picture', 'sage')],
                ['svc_fair', __('Paragraph (basic HTML ok)', 'sage'), 'html', __('I don’t run ads or social accounts for shops. Local Gettysburg marketing lives at <a href="https://ridgesandvalleys.com">Ridges &amp; Valleys</a>. If a build would help, <a href="/contact/">write a short note</a> and tell me what you’re trying to do.', 'sage')],
            ],
        ],
        'template-start.blade.php' => [
            __('Intro', 'sage') => [
                ['start_kicker', __('Kicker', 'sage'), 'text', __('Project brief', 'sage')],
                ['start_h1', __('Heading', 'sage'), 'text', __('Prepare for our first meeting.', 'sage')],
                ['start_lede', __('Intro', 'sage'), 'textarea', __('Four short steps. The answers agencies and shops usually cover in discovery. I read every brief before we talk so the first call is useful, not a blank page.', 'sage')],
            ],
            __('Form', 'sage') => [
                ['start_submit', __('Submit button', 'sage'), 'text', __('Send brief', 'sage')],
                ['start_reply_note', __('Note under submit', 'sage'), 'text', __('I usually reply within one or two business days (Eastern Time).', 'sage')],
                ['start_error', __('Error message', 'sage'), 'text', __('Something went wrong. Check the required fields and try again.', 'sage')],
            ],
        ],
        'template-contact.blade.php' => [
            __('Intro', 'sage') => [
                ['cnt_kicker', __('Kicker', 'sage'), 'text', __('Contact', 'sage')],
                ['cnt_h1', __('Heading', 'sage'), 'text', __('Say hello.', 'sage')],
                ['cnt_lede', __('Intro', 'sage'), 'textarea', __('Questions about a post, a code snippet, or GitHub are welcome. So are conversations about full-stack applications, WordPress platforms, roles, and development partnerships. I usually reply in one or two business days.', 'sage')],
            ],
            __('Form', 'sage') => [
                ['cnt_form_h2', __('Heading', 'sage'), 'text', __('Write a note', 'sage')],
                ['cnt_form_intro', __('Intro', 'sage'), 'textarea', __('Name, email, and a few sentences are enough. I read every note. This form is the reliable inbox.', 'sage')],
                ['cnt_who_label', __('Audience label', 'sage'), 'text', __('Who you are', 'sage')],
                ['cnt_message_hint', __('Message hint', 'sage'), 'text', __('A few sentences are enough. Paste a URL if you have one. No need for a long brief.', 'sage')],
                ['cnt_reply_note', __('Note under submit', 'sage'), 'text', __('I usually reply in one or two business days (Eastern Time).', 'sage')],
                ['cnt_success', __('Success message', 'sage'), 'text', __('Thanks. I got it and will write back soon.', 'sage')],
                ['cnt_error', __('Error message', 'sage'), 'text', __('Something went wrong. Check the required fields and try again.', 'sage')],
                ['cnt_submit', __('Submit button', 'sage'), 'text', __('Send hello', 'sage')],
            ],
            __('Elsewhere', 'sage') => [
                ['cnt_else_h2', __('Heading', 'sage'), 'text', __('Find me elsewhere', 'sage')],
                ['cnt_aside', __('Aside', 'sage'), 'textarea', __('Prefer GitHub or LinkedIn? Those work too. Ridges & Valleys is the Gettysburg studio site.', 'sage')],
            ],
            __('What to send', 'sage') => [
                ['cnt_tips_kicker', __('Kicker', 'sage'), 'text', __('A useful note', 'sage')],
                ['cnt_tips_h2', __('Heading', 'sage'), 'text', __('What to send', 'sage')],
                ['cnt_tips_intro', __('Intro', 'sage'), 'textarea', __('You do not need a pitch deck. These three things help me reply in the right shape.', 'sage')],
                ['cnt_tips', __('Cards', 'sage'), 'repeater', mh_contact_tips(), [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
            __('What happens next', 'sage') => [
                ['cnt_expect_kicker', __('Kicker', 'sage'), 'text', __('After you hit send', 'sage')],
                ['cnt_expect_h2', __('Heading', 'sage'), 'text', __('What happens next', 'sage')],
                ['cnt_expect_intro', __('Intro', 'sage'), 'textarea', __('A fair picture of how I use this inbox.', 'sage')],
                ['cnt_expect', __('Cards', 'sage'), 'repeater', mh_contact_expect(), [
                    ['title', __('Title', 'sage'), 'text'],
                    ['text', __('Text', 'sage'), 'textarea'],
                ]],
            ],
        ],
        'template-code.blade.php' => [
            __('Intro', 'sage') => [
                ['code_kicker', __('Kicker', 'sage'), 'text', __('Code', 'sage')],
                ['code_h1', __('Heading', 'sage'), 'text', __('Full-stack and WordPress code you can use.', 'sage')],
                ['code_lede', __('Intro (basic HTML ok)', 'sage'), 'html', __('Most of my work is public on GitHub — repos you can fork, snippets you can paste, and themes written so any developer can read them without asking me first.', 'sage')],
            ],
            __('Practice', 'sage') => [
                ['code_do_h2', __('Heading', 'sage'), 'text', __('What I work on.', 'sage')],
                ['code_do_intro', __('Intro', 'sage'), 'textarea', __('I ship across the stack: custom WordPress themes and plugins, PHP, TypeScript, React, APIs, and data-backed applications. The public repos show how I structure code, document decisions, and prepare work for handoff.', 'sage')],
                ['code_do_items', __('What I do (one per line)', 'sage'), 'lines', mh_code_practice_defaults()],
            ],
            __('GitHub', 'sage') => [
                ['code_gh_h2', __('Section heading', 'sage'), 'text', __('Open-source full-stack and WordPress code on GitHub.', 'sage')],
                ['code_gh_intro', __('Section intro', 'sage'), 'textarea', __('Public repos from my Gettysburg studio — Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats and activity below pull live from the GitHub API.', 'sage')],
                ['code_cal_h2', __('Calendar heading', 'sage'), 'text', __('Last 90 days of commits', 'sage')],
                ['code_cal_intro', __('Calendar intro', 'sage'), 'text', __('Contribution heat map for the last 90 days, newest week first. Hover a day to see what shipped. Darker blue means a busier day on public repos.', 'sage')],
                ['code_act_h2', __('Activity heading', 'sage'), 'text', __('Public activity', 'sage')],
                ['code_act_intro', __('Activity intro', 'sage'), 'text', __('Pushes, releases, and pull requests from the last 90 days — newest first. Open any row to jump into the repo.', 'sage')],
                ['code_feat_h2', __('Featured heading', 'sage'), 'text', __('Featured WordPress and app repos', 'sage')],
                ['code_feat_intro', __('Featured intro', 'sage'), 'text', __('Three public codebases I point developers to first: a React app, a WordPress plugin, and the Sage theme behind my Gettysburg studio. Each one is meant to be forked.', 'sage')],
                ['code_repos', __('Featured repos', 'sage'), 'repeater', $codeRepos, [
                    ['name', __('Name', 'sage'), 'text'],
                    ['desc', __('Description', 'sage'), 'textarea'],
                    ['url', __('URL', 'sage'), 'url'],
                    ['tags', __('Tags (comma separated)', 'sage'), 'text'],
                ]],
                ['code_live_h2', __('Updated repos heading', 'sage'), 'text', __('Recently pushed', 'sage')],
                ['code_live_intro', __('Updated repos intro', 'sage'), 'text', __('Fresh commits on public GitHub repos — a quick read on what I am shipping from Gettysburg this week.', 'sage')],
                ['code_live_all', __('All repos label', 'sage'), 'text', __('Browse all public repos', 'sage')],
            ],
            __('Skills', 'sage') => [
                ['code_sk_h2', __('Heading', 'sage'), 'text', __('Skills and tools.', 'sage')],
                ['code_sk_intro', __('Intro', 'sage'), 'text', __('WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos from Gettysburg. Jump a shelf — not an exhaustive list, just what shows up in public GitHub.', 'sage')],
                ['code_skills', __('Skills (one per line)', 'sage'), 'lines', mh_code_skill_defaults()],
            ],
            __('Documentation', 'sage') => [
                ['code_doc_h2', __('Heading', 'sage'), 'text', __('Documentation I keep open.', 'sage')],
                ['code_doc_intro', __('Intro', 'sage'), 'textarea', __('Official handbooks first, then Roots and the front-end stack behind this site. Jump a shelf, open a card — every link is the official docs.', 'sage')],
                ['code_docs', __('Links', 'sage'), 'repeater', mh_code_resource_defaults(), [
                    ['label', __('Label', 'sage'), 'text'],
                    ['url', __('URL', 'sage'), 'url'],
                    ['note', __('Note', 'sage'), 'text'],
                    ['group', __('Group', 'sage'), 'text'],
                ]],
            ],
            __('Call to action', 'sage') => [
                ['code_cta_kicker', __('Kicker', 'sage'), 'text', __('Work together', 'sage')],
                ['code_cta_h2', __('Heading', 'sage'), 'text', __('Want to build, collaborate, or compare notes?', 'sage')],
                ['code_cta_lede', __('Intro', 'sage'), 'textarea', __('Fork a repo, copy a snippet, or write if you want to work together. A question about a line of code is just as welcome as a project.', 'sage')],
                ['code_cta_btn', __('Button label', 'sage'), 'text', __('Hire me', 'sage')],
            ],
        ],
        'template-hire.blade.php' => [
            __('Intro', 'sage') => [
                ['hire_kicker', __('Kicker', 'sage'), 'text', __('Hire me', 'sage')],
                ['hire_h1', __('Heading', 'sage'), 'text', __('Hire a full-stack developer with deep WordPress experience.', 'sage')],
                ['hire_lede', __('Intro', 'sage'), 'textarea', __('Available for full-stack web applications, custom WordPress work, plugins, integrations, agency overflow, and full-time or contract roles. Based in Gettysburg and working remotely anywhere.', 'sage')],
            ],
            __('LinkedIn', 'sage') => [
                ['hire_li_h2', __('Section heading', 'sage'), 'text', __('LinkedIn profile.', 'sage')],
                ['hire_li_intro', __('Intro', 'sage'), 'textarea', __('Roles below match my LinkedIn. Connect there for a quieter inbox, or write here if you already know what you need.', 'sage')],
            ],
            __('Resume', 'sage') => [
                ['hire_cv_h2', __('Heading', 'sage'), 'text', __('Resume.', 'sage')],
                ['hire_cv_intro', __('Intro', 'sage'), 'textarea', __('Based in Gettysburg, PA — working with shops and agencies anywhere. Open to full-time, contract, and agency overflow work.', 'sage')],
                ['hire_cv_jobs', __('Roles', 'sage'), 'repeater', mh_code_resume_defaults(), [
                    ['role', __('Role', 'sage'), 'text'],
                    ['org', __('Organization', 'sage'), 'text'],
                    ['period', __('Dates', 'sage'), 'text'],
                    ['type', __('Type', 'sage'), 'text'],
                    ['url', __('Organization URL', 'sage'), 'url'],
                    ['bullets', __('Highlights (one per line)', 'sage'), 'textarea'],
                ]],
            ],
            __('Skills', 'sage') => [
                ['hire_sk_h2', __('Heading', 'sage'), 'text', __('Skills I bring.', 'sage')],
                ['hire_sk_intro', __('Intro', 'sage'), 'text', __('Stack I use on shipped WordPress and web work — same tools you will see on GitHub.', 'sage')],
            ],
        ],
        'template-projects.blade.php' => [
            __('Intro', 'sage') => [
                ['work_kicker', __('Kicker', 'sage'), 'text', __('Work', 'sage')],
                ['work_h1', __('Heading', 'sage'), 'text', __('Example sites.', 'sage')],
                ['work_lede', __('Intro', 'sage'), 'textarea', __('Studio concepts for Gettysburg tours, inns, shops, and restaurants. Shops can picture a WordPress site they can run. Developers can see how the pieces fit in Gettysburg and Adams County.', 'sage')],
                ['work_foot', __('Footer line (basic HTML ok)', 'sage'), 'html', __('Repos and snippets: <a href="/code/">Code</a>. Studio site: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.', 'sage')],
                ['work_search_ph', __('Search placeholder', 'sage'), 'text', __('Search sites…', 'sage')],
                ['work_cta_view', __('View concept label', 'sage'), 'text', __('View concept', 'sage')],
                ['work_cta_use', __('Use concept label', 'sage'), 'text', __('Use this concept', 'sage')],
                ['work_band_h2', __('Bottom CTA heading', 'sage'), 'text', __('Want a site in this shape?', 'sage')],
                ['work_band_lede', __('Bottom CTA intro', 'sage'), 'textarea', __('These are studio concepts, not a case-study deck. If one fits a tour, inn, shop, or restaurant you run, write and say which concept you want to start from.', 'sage')],
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
                ['write_kicker', __('Kicker', 'sage'), 'text', __('Journal', 'sage')],
                ['write_h1', __('Heading', 'sage'), 'text', __('Full-stack and WordPress development notes.', 'sage')],
                ['write_lede', __('Intro', 'sage'), 'textarea', __('Practical notes on WordPress, PHP, JavaScript, React, APIs, and the tools I use on real projects. Most include code you can adapt or use.', 'sage')],
                ['write_browse', __('Jump to posts label', 'sage'), 'text', __('Browse posts', 'sage')],
                ['write_recent_h2', __('Recent heading', 'sage'), 'text', __('Recent posts', 'sage')],
                ['write_devto_h2', __('DEV.to heading', 'sage'), 'text', __('Also on DEV.to', 'sage')],
                ['write_search_ph', __('Search placeholder', 'sage'), 'text', __('Search posts', 'sage')],
                ['write_subscribe_h2', __('Subscribe heading', 'sage'), 'text', __('Follow with RSS', 'sage')],
                ['write_subscribe_lede', __('Subscribe intro', 'sage'), 'textarea', __('There is no email list. Copy the feed URL into Feedly, NetNewsWire, or another reader you already use.', 'sage')],
                ['write_follow', __('Follow line', 'sage'), 'text', __('More of my notes', 'sage')],
                ['write_aside_years', __('Years heading', 'sage'), 'text', __('Years', 'sage')],
                ['write_aside_discussed', __('Discussed heading', 'sage'), 'text', __('Most discussed', 'sage')],
                ['write_aside_tags', __('Tags heading', 'sage'), 'text', __('Tags', 'sage')],
                ['write_aside_devto', __('DEV.to friends heading', 'sage'), 'text', __('DEV.to friends', 'sage')],
                ['write_aside_devto_thanks', __('Thank-you line', 'sage'), 'textarea', __('Thank you to everyone following along on DEV.to. You make posting worth it.', 'sage')],
                ['write_aside_devto_cta', __('Follow on DEV.to label', 'sage'), 'text', __('Follow on DEV.to', 'sage')],
                ['write_devto_followers', __('DEV.to followers (optional curated list)', 'sage'), 'repeater', [], [
                    ['username', __('Username', 'sage'), 'text'],
                    ['name', __('Display name', 'sage'), 'text'],
                    ['image', __('Avatar URL', 'sage'), 'url'],
                ]],
                ['write_share_note', __('Note under each post', 'sage'), 'html', __('Extra copy-paste examples live on the <a href="/code/">Code</a> page. You’re welcome to reuse them. Questions about a snippet? <a href="/contact/">Say hello</a>.', 'sage')],
                ['write_bio', __('Default author bio', 'sage'), 'textarea', __('I write from Gettysburg, Pennsylvania. Posts cover WordPress, plugins, and other web apps, often with snippets you can paste in. Developers, shops, and agencies are welcome here.', 'sage')],
            ],
        ],
    ];

    foreach ($map as $tpl => $groups) {
        $map[$tpl] = array_merge($groups, $seo);
    }

    return $map;
}

function mh_code_page_repos(?int $post_id = null): array
{
    $rows = field_rows('code_repos', [], $post_id);
    if ($rows === []) {
        return array_map(__NAMESPACE__.'\\mh_repo_card', mh_featured_repos());
    }
    $out = [];
    foreach ($rows as $r) {
        $tags = $r['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
        }
        $out[] = mh_repo_card([
            'name' => (string) ($r['name'] ?? ''),
            'desc' => (string) ($r['desc'] ?? ''),
            'url' => (string) ($r['url'] ?? ''),
            'tags' => $tags,
        ]);
    }

    return $out;
}

function mh_code_page_practice(?int $post_id = null): array
{
    return field_lines('code_do_items', mh_code_practice_defaults(), $post_id);
}

/**
 * Practice lines grouped for the Code page (preserves first-seen group order).
 *
 * @return list<array{label: string, icon: string, items: list<array{title: string, body: string, text: string, group: string}>}>
 */
function mh_code_page_practice_grouped(?int $post_id = null): array
{
    $grouped = [];
    $order = [];

    foreach (mh_code_page_practice($post_id) as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $key = mh_code_practice_group($line);
        if (! isset($grouped[$key])) {
            $grouped[$key] = [];
            $order[] = $key;
        }
        $parsed = mh_code_practice_parse($line);
        $grouped[$key][] = [
            'title' => $parsed['title'],
            'body' => $parsed['body'],
            'text' => $line,
            'group' => $key,
        ];
    }

    $out = [];
    foreach ($order as $key) {
        $out[] = [
            'label' => $key,
            'icon' => mh_code_practice_group_icon($key),
            'items' => $grouped[$key],
        ];
    }

    return $out;
}

/**
 * @return list<array{icon: string, title: string, body: string}>
 */
function mh_about_page_services(?int $post_id = null): array
{
    $rows = field_rows('about_services', [], $post_id);
    if ($rows === []) {
        return mh_about_services_defaults();
    }
    $out = [];
    foreach ($rows as $r) {
        $title = trim((string) ($r['title'] ?? ''));
        $body = trim((string) ($r['body'] ?? ''));
        if ($title === '' && $body === '') {
            continue;
        }
        $out[] = [
            'icon' => (string) ($r['icon'] ?? 'code'),
            'title' => $title,
            'body' => $body,
        ];
    }

    return $out !== [] ? $out : mh_about_services_defaults();
}

/**
 * @return list<array{title: string, detail: string}>
 */
function mh_about_page_work_types(?int $post_id = null): array
{
    $rows = field_rows('about_work_types', [], $post_id);
    if ($rows === []) {
        return mh_about_work_types_defaults();
    }
    $out = [];
    foreach ($rows as $r) {
        $title = trim((string) ($r['title'] ?? ''));
        $detail = trim((string) ($r['detail'] ?? ''));
        if ($title === '' && $detail === '') {
            continue;
        }
        $out[] = [
            'title' => $title,
            'detail' => $detail,
        ];
    }

    return $out !== [] ? $out : mh_about_work_types_defaults();
}

/**
 * @return list<array{icon: string, title: string, body: string}>
 */
function mh_about_page_approach(?int $post_id = null): array
{
    $rows = field_rows('about_approach', [], $post_id);
    if ($rows === []) {
        return mh_about_approach_defaults();
    }
    $out = [];
    foreach ($rows as $r) {
        $title = trim((string) ($r['title'] ?? ''));
        $body = trim((string) ($r['body'] ?? ''));
        if ($title === '' && $body === '') {
            continue;
        }
        $out[] = [
            'icon' => (string) ($r['icon'] ?? 'code'),
            'title' => $title,
            'body' => $body,
        ];
    }

    return $out !== [] ? $out : mh_about_approach_defaults();
}

function mh_code_page_skills(?int $post_id = null): array
{
    return field_lines('code_skills', mh_code_skill_defaults(), $post_id);
}

/**
 * Skills grouped for the Code page (preserves first-seen group order).
 *
 * @return list<array{label: string, icon: string, items: list<array{name: string, hint: string, group: string}>}>
 */
function mh_code_page_skills_grouped(?int $post_id = null): array
{
    $grouped = [];
    $order = [];

    foreach (mh_code_page_skills($post_id) as $skill) {
        $skill = trim((string) $skill);
        if ($skill === '') {
            continue;
        }
        $key = mh_code_skill_group($skill);
        if (! isset($grouped[$key])) {
            $grouped[$key] = [];
            $order[] = $key;
        }
        $grouped[$key][] = [
            'name' => $skill,
            'hint' => mh_code_skill_hint($skill),
            'group' => $key,
        ];
    }

    $out = [];
    foreach ($order as $key) {
        $out[] = [
            'label' => $key,
            'icon' => mh_code_skill_group_icon($key),
            'items' => $grouped[$key],
        ];
    }

    return $out;
}

function mh_code_page_resume(?int $post_id = null): array
{
    if ($post_id === null) {
        $hireId = mh_page_id_by_template('template-hire.blade.php');
        if ($hireId > 0) {
            $hireRows = field_rows('hire_cv_jobs', [], $hireId);
            if ($hireRows !== []) {
                return mh_normalize_resume_rows($hireRows);
            }
        }
        $codeId = mh_page_id_by_template('template-code.blade.php');
        if ($codeId > 0) {
            $post_id = $codeId;
        }
    }

    $rows = field_rows('hire_cv_jobs', [], $post_id);
    if ($rows === []) {
        $rows = field_rows('code_cv_jobs', [], $post_id);
    }
    if ($rows === []) {
        $rows = mh_code_resume_defaults();
    }

    return mh_normalize_resume_rows($rows);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return list<array{role: string, org: string, period: string, type: string, url: string, bullets: list<string>}>
 */
function mh_normalize_resume_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $bullets = $r['bullets'] ?? [];
        if (is_string($bullets)) {
            $bullets = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $bullets) ?: [])));
        }
        if (! is_array($bullets)) {
            $bullets = [];
        }
        $out[] = [
            'role' => (string) ($r['role'] ?? ''),
            'org' => (string) ($r['org'] ?? ''),
            'period' => (string) ($r['period'] ?? ''),
            'type' => (string) ($r['type'] ?? ''),
            'url' => (string) ($r['url'] ?? ''),
            'bullets' => $bullets,
        ];
    }

    return $out;
}

/**
 * First published page using a named Blade template.
 */
function mh_page_id_by_template(string $template): int
{
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
        'no_found_rows' => true,
        'fields' => 'ids',
        'meta_key' => '_wp_page_template',
        'meta_value' => $template,
    ]);

    return isset($pages[0]) ? (int) $pages[0] : 0;
}

function mh_code_page_resources(?int $post_id = null): array
{
    $rows = field_rows('code_docs', [], $post_id);
    if ($rows === []) {
        $rows = mh_code_resource_defaults();
    }
    $defaultsByUrl = [];
    foreach (mh_code_resource_defaults() as $def) {
        $defaultsByUrl[(string) ($def['url'] ?? '')] = $def;
    }

    $out = [];
    foreach ($rows as $r) {
        $url = trim((string) ($r['url'] ?? ''));
        $label = trim((string) ($r['label'] ?? ''));
        if ($url === '' || $label === '') {
            continue;
        }
        $fallback = $defaultsByUrl[$url] ?? [];
        $group = trim((string) ($r['group'] ?? $fallback['group'] ?? ''));
        if ($group === '') {
            $group = __('Reference', 'sage');
        }
        $out[] = [
            'label' => $label,
            'url' => $url,
            'note' => (string) ($r['note'] ?? ''),
            'group' => $group,
            'host' => mh_code_doc_host($url),
            'icon' => mh_code_doc_group_icon($group),
        ];
    }

    return $out;
}

/**
 * Documentation links grouped for the Code page (preserves first-seen group order).
 *
 * @return list<array{label: string, icon: string, items: list<array{label: string, url: string, note: string, group: string, host: string, icon: string}>}>
 */
function mh_code_page_resources_grouped(?int $post_id = null): array
{
    $grouped = [];
    $order = [];

    foreach (mh_code_page_resources($post_id) as $doc) {
        $key = (string) ($doc['group'] ?? __('Reference', 'sage'));
        if (! isset($grouped[$key])) {
            $grouped[$key] = [];
            $order[] = $key;
        }
        $grouped[$key][] = $doc;
    }

    $out = [];
    foreach ($order as $key) {
        $out[] = [
            'label' => $key,
            'icon' => mh_code_doc_group_icon($key),
            'items' => $grouped[$key],
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

/**
 * Resolve the ordered list of studio project items for the Work/Projects page.
 *
 * Merges admin-saved repeater rows with built-in defaults, resolving image URLs.
 * Returns the built-in project list when no rows have been saved.
 *
 * @since 3.1.0
 *
 * @param  int|null  $post_id  Projects page post ID; falls back to get_the_ID() when null.
 * @return list<array<string, mixed>>
 */
function mh_work_page_items(?int $post_id = null): array
{
    $defaults = [];
    foreach (mh_studio_projects() as $p) {
        $defaults[$p['slug']] = $p;
    }

    $rows = field_rows('work_items', [], $post_id);
    if ($rows === []) {
        return array_map(function (array $p): array {
            $p['image'] = mh_studio_project_image_url($p);

            return $p;
        }, mh_studio_projects());
    }
    $out = [];
    foreach ($rows as $r) {
        $tech = $r['tech'] ?? [];
        if (is_string($tech)) {
            $tech = array_values(array_filter(array_map('trim', explode(',', $tech))));
        }
        $slug = sanitize_title((string) ($r['slug'] ?? $r['title'] ?? ''));
        $base = $defaults[$slug] ?? [];
        $item = [
            'slug' => $slug,
            'title' => (string) (($r['title'] ?? '') !== '' ? $r['title'] : ($base['title'] ?? '')),
            'cat' => (string) (($r['cat'] ?? '') !== '' ? $r['cat'] : ($base['cat'] ?? '')),
            'place' => (string) (($r['place'] ?? '') !== '' ? $r['place'] : ($base['place'] ?? '')),
            'blurb' => (string) (($r['blurb'] ?? '') !== '' ? $r['blurb'] : ($base['blurb'] ?? '')),
            'tech' => $tech !== [] ? $tech : ($base['tech'] ?? []),
            'concept' => (string) (($r['concept'] ?? '') !== '' ? $r['concept'] : ($base['concept'] ?? '')),
            'image' => (string) (($r['image'] ?? '') !== '' ? $r['image'] : ($base['image'] ?? '')),
        ];
        $item['image'] = mh_studio_project_image_url($item);
        $out[] = $item;
    }

    return $out;
}

/**
 * Post ID of the static front page, or 0 when not set.
 *
 * @since 3.1.0
 */
function mh_front_id(): int
{
    return (int) get_option('page_on_front');
}

/**
 * Post ID of the designated blog/posts page, or 0 when not set.
 *
 * @since 3.1.0
 */
function mh_writing_id(): int
{
    return (int) get_option('page_for_posts');
}

function field_group_hint(string $label): string
{
    $hints = [
        __('Hero', 'sage') => __('Top of the home page, next to the photo.', 'sage'),
        __('Footer (site-wide)', 'sage') => __('The sentence in the site footer. Edited on Home so every page stays in sync.', 'sage'),
        __('Who this is for', 'sage') => __('Four cards: developers, learners, shops, agencies. Each can link to a page.', 'sage'),
        __('Example sites', 'sage') => __('Each row is one concept site. Empty the list to restore the built-in set.', 'sage'),
        __('Snippets', 'sage') => __('Copy-paste examples shown on Home. Empty the list to restore the built-in set.', 'sage'),
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
        echo '<p>'.esc_html__('This page uses the default template. Choose Home, About, Work, Services, Code, Contact, Now, or Journal to edit theme fields here.', 'sage').'</p>';

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
