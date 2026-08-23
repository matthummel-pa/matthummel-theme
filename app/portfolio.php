<?php

/**
 * Portfolio content, social defaults, DEV.to feed, and one-time page seed.
 * Does not delete posts or categories.
 */

namespace App;

function mh_github_login(): string
{
    return 'matthummel-pa';
}

/** Website listed on the GitHub profile (Ridges & Valleys), with a https fallback. */
function mh_github_blog_url(?array $gh = null): string
{
    $gh = $gh ?? Github::fetchUser(mh_github_login());
    $blog = trim((string) ($gh['blog'] ?? ''));
    if ($blog === '') {
        return 'https://ridgesandvalleys.com';
    }

    return preg_match('#^https?://#i', $blog) ? $blog : 'https://'.$blog;
}

function mh_portfolio_social_defaults(): array
{
    return [
        'github' => 'https://github.com/matthummel-pa',
        'linkedin' => 'https://www.linkedin.com/in/matt-hummel-pa',
        'devto' => 'https://dev.to/matthummel',
        'bluesky' => 'https://bsky.app/profile/matthummel.bsky.social',
        'reddit' => 'https://www.reddit.com/user/matt-hummel',
        'rss' => home_url('/feed/'),
    ];
}

function mh_social_links(): array
{
    $labels = [
        'github' => 'GitHub',
        'linkedin' => 'LinkedIn',
        'devto' => 'DEV.to',
        'bluesky' => 'Bluesky',
        'reddit' => 'Reddit',
        'rss' => 'RSS',
    ];

    $links = [];
    foreach (mh_portfolio_social_defaults() as $key => $url) {
        if ($url === '') {
            continue;
        }
        $links[] = [
            'key' => $key,
            'label' => $labels[$key] ?? ucfirst($key),
            'url' => $url,
        ];
    }

    return $links;
}

/** Featured GitHub codebases to highlight on Code and Home. */
function mh_featured_repos(): array
{
    return [
        [
            'name' => 'keepary',
            'desc' => 'Private family app: authentication, invites, and posts. React on the front end, Supabase for data and sign-in.',
            'url' => 'https://github.com/matthummel-pa/keepary',
            'tags' => ['React', 'Supabase', 'TypeScript'],
        ],
        [
            'name' => 'tocflow',
            'desc' => 'WordPress plugin that builds a table of contents from heading blocks. PHP, Gutenberg, and a small public API.',
            'url' => 'https://github.com/matthummel-pa/tocflow',
            'tags' => ['WordPress', 'Gutenberg', 'PHP'],
        ],
        [
            'name' => 'ridgesandvalleys',
            'desc' => 'Sage 11 theme for the Gettysburg studio site. Blade templates, local SEO, and pages shops can edit.',
            'url' => 'https://github.com/matthummel-pa/ridgesandvalleys',
            'tags' => ['WordPress', 'Sage', 'Tailwind'],
        ],
    ];
}

/** Featured repos plus recent public GitHub work (forks and the profile repo skipped). */
function mh_home_github_repos(int $limit = 6): array
{
    $featured = mh_code_page_repos();
    $live = mh_github_live_repos(12);
    $names = array_map(static fn ($r) => strtolower((string) ($r['name'] ?? '')), $featured);
    foreach ($live as $r) {
        $name = strtolower((string) ($r['name'] ?? ''));
        if ($name === '' || in_array($name, $names, true)) {
            continue;
        }
        $featured[] = $r;
        $names[] = $name;
    }

    return array_slice($featured, 0, max(1, $limit));
}

function mh_title_label(string $text): string
{
    $text = trim((string) preg_replace('/[\-_]+/', ' ', $text));
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
        return '';
    }
    $acronyms = [
        'wp' => 'WP', 'php' => 'PHP', 'css' => 'CSS', 'html' => 'HTML', 'js' => 'JS',
        'seo' => 'SEO', 'api' => 'API', 'ui' => 'UI', 'ux' => 'UX', 'toc' => 'TOC',
        'scss' => 'SCSS', 'ts' => 'TS', 'nextjs' => 'Next.js', 'nodejs' => 'Node.js',
        'vscode' => 'VS Code', 'mdn' => 'MDN',
    ];
    $out = [];
    foreach (explode(' ', $text) as $part) {
        $low = strtolower($part);
        $out[] = $acronyms[$low] ?? ucfirst($low);
    }

    return implode(' ', $out);
}

function mh_repo_demo_url(string $homepage): string
{
    $homepage = trim($homepage);
    if ($homepage === '') {
        return '';
    }
    if (! preg_match('#^https?://#i', $homepage)) {
        $homepage = 'https://'.$homepage;
    }

    return esc_url_raw($homepage) ?: '';
}

function mh_repo_card(array $repo): array
{
    $name = (string) ($repo['name'] ?? '');
    $url = (string) ($repo['url'] ?? '');
    $owner = mh_github_login();
    $meta = [];
    if ($name !== '' && ($url === '' || empty($repo['homepage']) && empty($repo['topics']) && empty($repo['lang']))) {
        $meta = Github::fetchRepoMeta($owner, $name);
    }
    $desc = trim((string) ($repo['desc'] ?? ''));
    if ($desc === '') {
        $desc = (string) ($meta['desc'] ?? '');
    }
    if ($url === '') {
        $url = (string) ($meta['url'] ?? '');
    }
    if ($url === '' && $name !== '') {
        $url = 'https://github.com/'.$owner.'/'.$name;
    }
    $homepage = mh_repo_demo_url((string) ($repo['homepage'] ?? $meta['homepage'] ?? ''));
    $langs = [];
    if ($name !== '') {
        $langs = Github::fetchLanguages($owner, $name);
    }
    if ($langs === [] && ! empty($repo['lang'])) {
        $langs = [(string) $repo['lang']];
    } elseif ($langs === [] && ! empty($meta['lang'])) {
        $langs = [(string) $meta['lang']];
    }
    $tags = $repo['tags'] ?? [];
    if (is_string($tags)) {
        $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
    }
    $topics = $repo['topics'] ?? $meta['topics'] ?? [];
    $skip = [
        'website', 'website-design', 'website-development', 'vibe-coding',
        'toc', 'table-of-contents', 'accessibility',
    ];
    $stack = [];
    foreach (array_merge($langs, $tags, $topics) as $item) {
        $raw = trim((string) $item);
        if ($raw === '' || in_array(strtolower($raw), $skip, true)) {
            continue;
        }
        $label = mh_title_label($raw);
        $key = strtolower($label);
        if ($label === '' || isset($stack[$key])) {
            continue;
        }
        $stack[$key] = $label;
    }

    return [
        'name' => $name,
        'title' => mh_title_label($name),
        'desc' => $desc,
        'url' => $url,
        'demo' => $homepage,
        'stack' => array_values(array_slice($stack, 0, 6)),
        'stars' => (int) ($repo['stars'] ?? $meta['stars'] ?? 0),
        'forks' => (int) ($repo['forks'] ?? $meta['forks'] ?? 0),
        'pushed' => (string) ($repo['pushed'] ?? $meta['pushed'] ?? ''),
        'lang' => (string) ($repo['lang'] ?? $meta['lang'] ?? ''),
    ];
}

/** Relative time for GitHub timestamps (UTC). */
function mh_github_ago(string $iso): string
{
    $iso = trim($iso);
    if ($iso === '') {
        return '';
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return '';
    }
    $diff = max(0, time() - $ts);
    if ($diff < MINUTE_IN_SECONDS) {
        return __('just now', 'sage');
    }
    if ($diff < HOUR_IN_SECONDS) {
        $n = (int) floor($diff / MINUTE_IN_SECONDS);

        return sprintf(_n('%s minute ago', '%s minutes ago', $n, 'sage'), (string) $n);
    }
    if ($diff < DAY_IN_SECONDS) {
        $n = (int) floor($diff / HOUR_IN_SECONDS);

        return sprintf(_n('%s hour ago', '%s hours ago', $n, 'sage'), (string) $n);
    }
    if ($diff < 30 * DAY_IN_SECONDS) {
        $n = (int) floor($diff / DAY_IN_SECONDS);

        return sprintf(_n('%s day ago', '%s days ago', $n, 'sage'), (string) $n);
    }

    return date_i18n(get_option('date_format') ?: 'M j, Y', $ts);
}

function mh_github_profile(): array
{
    return Github::fetchUser(mh_github_login());
}

function mh_github_events(int $limit = 10): array
{
    return Github::fetchEvents(mh_github_login(), $limit);
}

function mh_github_calendar(): array
{
    return Github::fetchContributionCalendar(mh_github_login());
}

function mh_github_live_repos(int $limit = 8): array
{
    return array_map(__NAMESPACE__.'\\mh_repo_card', Github::fetchRepos(mh_github_login(), $limit, 'updated'));
}

/** Ridges & Valleys concept work for the Projects page. */
function mh_studio_projects(): array
{
    $rv = 'https://ridgesandvalleys.com/work/';

    return [
        ['slug' => 'hallowed-ground', 'title' => 'Hallowed Ground Battlefield Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'A guide site with day and night tours and a battlefield map.', 'tech' => ['Sage', 'WooCommerce', 'Maps'], 'image' => 'hallowed-ground.jpg', 'concept' => $rv.'concept-tour-hallowed-ground-tours/'],
        ['slug' => 'first-shot', 'title' => 'First Shot Food & History Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'Walking tours with a simple booking calendar.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'first-shot.jpg', 'concept' => $rv.'concept-tour-first-shot-food-tours/'],
        ['slug' => 'field-of-valor', 'title' => 'Field of Valor History Co.', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'History tours for visitors. Clear list. Easy contact.', 'tech' => ['WordPress', 'SEO'], 'image' => 'field-of-valor.jpg', 'concept' => $rv.'gettysburg-tour-website-concept/'],
        ['slug' => 'keystone-homes', 'title' => 'Keystone Homes & Land', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'Land and farms. Grid, map, and simple filters.', 'tech' => ['WordPress', 'Maps', 'Filters'], 'image' => 'keystone-homes.jpg', 'concept' => $rv.'concept-realtor-keystone-homes-and-land/'],
        ['slug' => 'ridgeline-realty', 'title' => 'Ridgeline Realty', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'Listings you can filter, plus a mortgage calculator.', 'tech' => ['WordPress', 'JavaScript'], 'image' => 'ridgeline-realty.jpg', 'concept' => $rv.'concept-realtor-ridgeline-realty/'],
        ['slug' => 'ridgeline-outfitters', 'title' => 'Ridgeline Outfitters', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'Outdoor gear with filters, a wishlist, and a cart.', 'tech' => ['WooCommerce'], 'image' => 'ridgeline-outfitters.jpg', 'concept' => $rv.'concept-retail-ridgeline-outfitters/'],
        ['slug' => 'diamond-ridge', 'title' => 'Diamond & Ridge Mercantile', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A downtown shop with products, a cart, and checkout.', 'tech' => ['WooCommerce'], 'image' => 'diamond-ridge.jpg', 'concept' => $rv.'concept-gettysburg-retail/'],
        ['slug' => 'diamonds-threads', 'title' => 'Diamonds & Threads', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A boutique shop that is easy to browse.', 'tech' => ['WooCommerce'], 'image' => 'diamonds-threads.jpg', 'concept' => $rv.'gettysburg-boutique-website/'],
        ['slug' => 'cannon-crumb', 'title' => 'Cannon & Crumb', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A cafe menu you can filter, plus online ordering.', 'tech' => ['WordPress', 'Ordering'], 'image' => 'cannon-crumb.jpg', 'concept' => $rv.'concept-restaurant-cannon-and-crumb/'],
        ['slug' => 'field-musket', 'title' => 'Field & Musket Tavern', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A tavern menu and a clear way to book a table.', 'tech' => ['WordPress'], 'image' => 'field-musket.jpg', 'concept' => $rv.'concept-gettysburg-restaurant/'],
        ['slug' => 'pintfield', 'title' => 'Pintfield Creamery', 'cat' => 'Restaurants', 'place' => 'Gettysburg & Adams County, PA', 'blurb' => 'A scoop board, online orders, and three shops.', 'tech' => ['WordPress', 'Ordering'], 'image' => 'pintfield.jpg', 'concept' => $rv.'gettysburg-creamery-website-design/'],
        ['slug' => 'reveille', 'title' => 'Reveille Kitchen & Bar', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'Menu first. Reserve a table online.', 'tech' => ['WordPress'], 'image' => 'reveille.jpg', 'concept' => $rv.'gettysburg-restaurant-website/'],
        ['slug' => 'cupola-field', 'title' => 'The Cupola & Field Hotel', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A small hotel with a simple booking path.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'cupola-field.jpg', 'concept' => $rv.'concept-hotel-cupola-field/'],
        ['slug' => 'lantern-laurel', 'title' => 'The Lantern & Laurel Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'Nine rooms. Book direct on the site.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'lantern-laurel.jpg', 'concept' => $rv.'concept-gettysburg-hotel/'],
        ['slug' => 'willoughby', 'title' => 'Willoughby Run Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'An inn site built to take bookings, not just look pretty.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'willoughby.jpg', 'concept' => $rv.'gettysburg-inn-website/'],
        ['slug' => 'herr-ridge', 'title' => 'Herr Ridge Cottage B&B', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A bed-and-breakfast site that helps people book a stay.', 'tech' => ['WordPress', 'SEO'], 'image' => 'herr-ridge.jpg', 'concept' => $rv.'gettysburg-bed-and-breakfast-website/'],
    ];
}

/** Screenshot URL for a Work card (bundled JPEG, or an http(s) override). */
function mh_studio_project_image_url(array $project): string
{
    $img = trim((string) ($project['image'] ?? ''));
    if ($img === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $img)) {
        return $img;
    }

    $rel = 'resources/images/work/'.ltrim($img, '/');
    if (is_readable(get_theme_file_path($rel))) {
        return get_theme_file_uri($rel);
    }

    return '';
}

function mh_studio_project_categories(): array
{
    $cats = array_unique(array_map(fn ($p) => $p['cat'], mh_studio_projects()));
    sort($cats);

    return $cats;
}

function mh_work_item_by_slug(string $slug): ?array
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }
    $page = get_page_by_path('projects');
    $id = $page instanceof \WP_Post ? (int) $page->ID : null;
    foreach (mh_work_page_items($id) as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }

    return null;
}

function mh_work_permalink(string $slug, ?string $pageUrl = null): string
{
    $page = $pageUrl ?: home_url('/projects/');

    return $page.'#'.sanitize_title($slug);
}

function mh_work_contact_url(array $project): string
{
    $slug = sanitize_title((string) ($project['slug'] ?? ''));

    return add_query_arg([
        'project' => $slug,
        'who' => 'business',
    ], home_url('/contact/'));
}

function mh_code_practice_defaults(): array
{
    return [
        'Full-stack web applications in PHP, WordPress, and React, including REST integrations and auth.',
        'WordPress engineering with Sage 11, Blade, Acorn, Bedrock, Tailwind, Vite, and Gutenberg.',
        'Front-end architecture: semantic HTML, CSS, TypeScript, and accessible UI that shops can still edit.',
        'Plugin and theme work with a public GitHub trail so other developers can read the same code.',
        'Ridges & Valleys is the studio I just started. I work with shops, inns, tours, and agencies in any location.',
        'Microsoft Power Platform (Power Apps, Power Automate, Dataverse) as previous consulting work, not the public offer.',
    ];
}

function mh_code_skill_defaults(): array
{
    return [
        'HTML', 'CSS', 'JavaScript', 'TypeScript', 'PHP', 'WordPress', 'Sage',
        'React', 'Next.js', 'Node.js', 'Tailwind', 'Sass', 'Vite', 'Laravel',
        'Git', 'GitHub', 'VS Code', 'Power Apps',
    ];
}

function mh_code_resume_defaults(): array
{
    return [
        [
            'role' => 'Founder',
            'org' => 'Ridges & Valleys',
            'period' => 'Current',
            'type' => 'Independent Studio',
            'url' => 'https://ridgesandvalleys.com',
            'bullets' => "I just started this studio. It is new, not a full book of work yet.\nWordPress sites for shops, inns, and tours. I am based in Gettysburg; location is not a limit.\nI am still open to agencies, other studios, overflow work, and full-time positions, remote or on-site.",
        ],
        [
            'role' => 'Senior Consultant',
            'org' => 'Saliense',
            'period' => 'Mar 2025 – Previous',
            'type' => 'Government Contract',
            'url' => '',
            'bullets' => "Built PowerApps forms and workflows for government teams.\nCreated Power Automate flows that cut manual, repeatable process work.\nSupported SharePoint and PowerApps users day to day.\nManaged permissions across site collections.\nTurned written requirements into solutions that could scale.\nUsed user feedback to improve how the systems ran.",
        ],
        [
            'role' => 'Applications and SharePoint Administrator',
            'org' => 'All Native Group, The Federal Services Division of Ho-Chunk Inc.',
            'period' => 'Dec 2023 – Feb 2025',
            'type' => 'Government Contract',
            'url' => '',
            'bullets' => "Built PowerApps forms and workflows for government teams.\nCreated Power Automate flows that cut manual, repeatable process work.\nSupported SharePoint and PowerApps users day to day.\nManaged permissions across site collections.\nTurned written requirements into solutions that could scale.\nUsed user feedback to improve how the systems ran.",
        ],
        [
            'role' => 'SharePoint Web Developer',
            'org' => 'Knowledge Capital Associates — Contractor USMC',
            'period' => 'Sep 2021 – Jun 2022',
            'type' => 'Government Contract',
            'url' => '',
            'bullets' => "Created SharePoint sites and managed permissions.\nHelped the SharePoint team migrate sites to SharePoint Online.\nBuilt applications and workflows in PowerApps and Power Automate.\nReplaced InfoPath forms with PowerApps and Designer workflows with Power Automate.\nConfigured site collections and views to match the requirements.",
        ],
        [
            'role' => 'Web Developer',
            'org' => 'Germanna Community College',
            'period' => 'Jul 2011 – Oct 2020',
            'type' => 'Full-time · Public Information and Marketing',
            'url' => 'https://germanna.edu/',
            'bullets' => "Built a responsive WordPress site for Public Information and Marketing.\nWorked with Public Information and Marketing on day-to-day content management.\nBrought pages up to Section 508 and WCAG 2.0.\nMoved the college site to WordPress so staff could edit pages without a developer.\nImplemented Google Analytics and Google Tag Manager for tracking and marketing.",
        ],
    ];
}

function mh_code_resource_defaults(): array
{
    return [
        ['label' => 'WordPress Developer Handbook', 'url' => 'https://developer.wordpress.org/', 'note' => 'Themes, plugins, REST API, and block editor.'],
        ['label' => 'WordPress REST API', 'url' => 'https://developer.wordpress.org/rest-api/', 'note' => 'Application endpoints and authentication.'],
        ['label' => 'Sage', 'url' => 'https://roots.io/sage/docs/', 'note' => 'Blade, Vite, and the Roots theme stack I ship.'],
        ['label' => 'Acorn', 'url' => 'https://roots.io/acorn/docs/', 'note' => 'Laravel components inside WordPress.'],
        ['label' => 'Bedrock', 'url' => 'https://roots.io/bedrock/docs/', 'note' => 'Composer-based WordPress structure.'],
        ['label' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com/docs', 'note' => 'Utility CSS used on this theme.'],
        ['label' => 'Vite', 'url' => 'https://vite.dev/guide/', 'note' => 'Asset pipeline for Sage 11.'],
        ['label' => 'PHP', 'url' => 'https://www.php.net/docs.php', 'note' => 'Language reference for theme and plugin work.'],
        ['label' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org/en-US/docs/Web', 'note' => 'HTML, CSS, and JavaScript.'],
        ['label' => 'React', 'url' => 'https://react.dev/', 'note' => 'UI library for Keepary and other apps.'],
        ['label' => 'TypeScript', 'url' => 'https://www.typescriptlang.org/docs/', 'note' => 'Typed JavaScript for larger front ends.'],
        ['label' => 'GitHub Docs', 'url' => 'https://docs.github.com/en', 'note' => 'REST, GraphQL, and Actions.'],
        ['label' => 'Laravel', 'url' => 'https://laravel.com/docs', 'note' => 'Reference when Acorn overlaps Laravel APIs.'],
        ['label' => 'Microsoft Learn — Power Platform', 'url' => 'https://learn.microsoft.com/power-platform/', 'note' => 'Power Apps, Automate, and Dataverse.'],
    ];
}

function mh_code_snippets(): array
{
    return [
        [
            'title' => 'A tiny WordPress shortcode',
            'lang' => 'php',
            'note' => 'Paste into a plugin or your theme PHP. Then type [hello] in a post. Good first snippet for a blog.',
            'code' => "add_shortcode('hello', function () {\n    return '<p>Hello from a shortcode.</p>';\n});",
        ],
        [
            'title' => 'Skip to content that actually works',
            'lang' => 'html',
            'note' => 'Keep the first focusable control a skip link. Hide it until it has focus.',
            'code' => '<a class="skip-link" href="#main">Skip to main content</a>',
        ],
        [
            'title' => 'Readable line length',
            'lang' => 'css',
            'note' => 'Cap body text around 65 characters. Short lines are easier at a 6–8 grade level.',
            'code' => ".prose {\n  max-width: 65ch;\n  font-size: 1.125rem;\n  line-height: 1.7;\n}",
        ],
        [
            'title' => 'One brand color, reused',
            'lang' => 'css',
            'note' => 'Change --brand once. Links and buttons can share it. Same idea as this theme.',
            'code' => ":root {\n  --brand: #2563EB;\n}\n\na {\n  color: var(--brand);\n}",
        ],
        [
            'title' => 'Safe link in Blade',
            'lang' => 'blade',
            'note' => 'Escape URLs and labels you did not write yourself.',
            'code' => '<a href="{{ esc_url($url) }}">{{ esc_html($label) }}</a>',
        ],
        [
            'title' => 'Get the current page title in Blade',
            'lang' => 'blade',
            'note' => 'Sage templates already have $title. Use get_the_title() if you are not on a standard loop.',
            'code' => "{{ \$title }}\n{{-- or --}}\n{{ get_the_title() }}",
        ],
        [
            'title' => 'Fetch GitHub without hammering the API',
            'lang' => 'php',
            'note' => 'Cache the response. Respect rate limits. Show a fallback if GitHub is down.',
            'code' => "\$key = 'mh_ghu_' . md5(\$user);\n\$data = get_transient(\$key);\nif (false === \$data) {\n    \$data = mh_fetch_github_user(\$user);\n    set_transient(\$key, \$data, 6 * HOUR_IN_SECONDS);\n}",
        ],
    ];
}

function mh_devto_posts(int $limit = 5): array
{
    $key = 'mh_devto_feed_v1';
    $cached = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limit);
    }

    $posts = [];
    $res = wp_remote_get('https://dev.to/feed/matthummel', [
        'timeout' => 8,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);

    if (! is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
        $body = wp_remote_retrieve_body($res);
        if ($body && class_exists(\SimpleXMLElement::class)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $desc = trim(wp_strip_all_tags(html_entity_decode((string) $item->description, ENT_QUOTES, 'UTF-8')));
                    $posts[] = [
                        'title' => (string) $item->title,
                        'url' => (string) $item->link,
                        'date' => (string) $item->pubDate,
                        'ex' => $desc !== '' ? wp_trim_words($desc, 22) : '',
                    ];
                }
            }
        }
    }

    set_transient($key, $posts, 3 * HOUR_IN_SECONDS);

    return array_slice($posts, 0, $limit);
}

function mh_latest_posts(int $limit = 3): array
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
    $out = [];
    foreach ($q->posts as $p) {
        $cats = get_the_category($p->ID);
        $out[] = [
            'title' => get_the_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('', $p),
            'ex' => wp_trim_words(get_the_excerpt($p), 28),
            'cat' => ($cats && ! is_wp_error($cats)) ? $cats[0]->name : '',
            'minutes' => mh_reading_minutes($p),
            'thumb' => mh_post_card_image((int) $p->ID),
        ];
    }
    wp_reset_postdata();

    return $out;
}

function mh_reading_minutes(\WP_Post|int $post): int
{
    $post = get_post($post);
    if (! $post instanceof \WP_Post) {
        return 1;
    }
    $words = str_word_count(wp_strip_all_tags((string) $post->post_content));

    return max(1, (int) ceil($words / 200));
}

function mh_post_card_image(int $post_id): string
{
    $src = get_the_post_thumbnail_url($post_id, 'medium_large');

    return is_string($src) ? $src : '';
}

function mh_post_has_code(\WP_Post|int $post): bool
{
    $post = get_post($post);
    if (! $post instanceof \WP_Post) {
        return false;
    }

    return (bool) preg_match(
        '/```|<pre[\s>]|wp-block-code|wp-block-syntaxhighlighter|class=["\'][^"\']*language-/',
        (string) $post->post_content
    );
}

function mh_published_post_count(): int
{
    $counts = wp_count_posts('post');

    return isset($counts->publish) ? (int) $counts->publish : 0;
}

function mh_journal_is_oldest(): bool
{
    return strtolower((string) get_query_var('order')) === 'asc';
}

function mh_journal_sort_url(string $which): string
{
    $url = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($which === 'oldest') {
        return add_query_arg('order', 'asc', $url);
    }

    return remove_query_arg('order', $url);
}

/** @return list<array{year: int, count: int, url: string}> */
function mh_journal_years(): array
{
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT YEAR(post_date) AS y, COUNT(ID) AS n
         FROM {$wpdb->posts}
         WHERE post_type = 'post' AND post_status = 'publish'
         GROUP BY y
         ORDER BY y DESC"
    );
    $out = [];
    foreach ($rows ?: [] as $row) {
        $year = (int) $row->y;
        if ($year < 1970) {
            continue;
        }
        $out[] = [
            'year' => $year,
            'count' => (int) $row->n,
            'url' => get_year_link($year),
        ];
    }

    return $out;
}

/** @return list<array{title: string, url: string, date: string, comments: int}> */
function mh_popular_posts(int $limit = 5, int $exclude = 0): array
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'post__not_in' => $exclude ? [$exclude] : [],
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'orderby' => [
            'comment_count' => 'DESC',
            'date' => 'DESC',
        ],
    ]);
    $out = [];
    foreach ($q->posts as $p) {
        $comments = (int) $p->comment_count;
        if ($comments < 1) {
            continue;
        }
        $out[] = [
            'title' => get_the_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('', $p),
            'comments' => $comments,
        ];
    }

    return $out;
}

add_action('pre_get_posts', function (\WP_Query $query): void {
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }
    if (! ($query->is_home() || $query->is_category() || $query->is_tag() || $query->is_date() || $query->is_search())) {
        return;
    }
    if (strtolower((string) $query->get('order')) !== 'asc') {
        return;
    }
    $query->set('orderby', 'date');
    $query->set('order', 'ASC');
});

function mh_post_summary(\WP_Post $post): string
{
    $excerpt = trim((string) $post->post_excerpt);
    if ($excerpt !== '') {
        return wp_strip_all_tags($excerpt);
    }

    return wp_trim_words(wp_strip_all_tags($post->post_content), 48);
}

/**
 * Add heading ids and return [html, toc[]].
 *
 * @return array{0: string, 1: array<int, array{level: int, id: string, text: string}>}
 */
function mh_content_with_toc(string $html): array
{
    $toc = [];
    $used = [];
    $html = (string) preg_replace_callback(
        '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
        static function ($m) use (&$toc, &$used) {
            $level = (int) $m[1];
            $attrs = $m[2] ?? '';
            $inner = $m[3];
            $text = trim(wp_strip_all_tags($inner));
            if ($text === '') {
                return $m[0];
            }
            $id = '';
            if (preg_match('/\sid=(["\'])([^"\']+)\1/i', $attrs, $idm)) {
                $id = $idm[2];
            } else {
                $base = sanitize_title($text) ?: 'section';
                $id = $base;
                $n = 2;
                while (isset($used[$id])) {
                    $id = $base.'-'.$n;
                    $n++;
                }
                $attrs .= ' id="'.esc_attr($id).'"';
            }
            $used[$id] = true;
            $toc[] = [
                'level' => $level,
                'id' => $id,
                'text' => $text,
            ];

            return '<h'.$level.$attrs.'>'.$inner.'</h'.$level.'>';
        },
        $html
    );

    return [$html, $toc];
}

/**
 * Create standard portfolio pages once. Never deletes posts or categories.
 */
function mh_seed_portfolio_pages(): void
{
    if (get_option('mh_portfolio_seeded_v2')) {
        return;
    }

    $pages = [
        'home' => ['title' => 'Home', 'template' => 'template-home.blade.php'],
        'about' => ['title' => 'About', 'template' => 'template-about.blade.php'],
        'projects' => ['title' => 'Work', 'template' => 'template-projects.blade.php'],
        'services' => ['title' => 'Services', 'template' => 'template-services.blade.php'],
        'code' => ['title' => 'Code', 'template' => 'template-code.blade.php'],
        'contact' => ['title' => 'Contact', 'template' => 'template-contact.blade.php'],
        'now' => ['title' => 'Now', 'template' => 'template-now.blade.php'],
    ];

    $ids = [];
    foreach ($pages as $slug => $meta) {
        $existing = get_page_by_path($slug);
        if ($existing instanceof \WP_Post) {
            $ids[$slug] = $existing->ID;
            update_post_meta($existing->ID, '_wp_page_template', $meta['template']);

            continue;
        }
        $id = wp_insert_post([
            'post_title' => $meta['title'],
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($id && ! is_wp_error($id)) {
            $ids[$slug] = (int) $id;
            update_post_meta($id, '_wp_page_template', $meta['template']);
        }
    }

    $blog = get_page_by_path('blog');
    if (! $blog) {
        $blogId = wp_insert_post([
            'post_title' => 'Journal',
            'post_name' => 'blog',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($blogId && ! is_wp_error($blogId)) {
            $ids['blog'] = (int) $blogId;
        }
    } else {
        $ids['blog'] = $blog->ID;
        if ($blog->post_title === 'Writing') {
            wp_update_post(['ID' => $blog->ID, 'post_title' => 'Journal']);
        }
    }

    if (! empty($ids['home']) && ! empty($ids['blog'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids['home']);
        update_option('page_for_posts', $ids['blog']);
    }

    $menuName = 'Primary';
    $menu = wp_get_nav_menu_object($menuName);
    if (! $menu) {
        $menuId = wp_create_nav_menu($menuName);
    } else {
        $menuId = (int) $menu->term_id;
        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                wp_delete_post((int) $item->ID, true);
            }
        }
    }

    $order = ['home', 'about', 'projects', 'services', 'code', 'blog', 'contact'];
    $n = 1;
    foreach ($order as $slug) {
        if (empty($ids[$slug])) {
            continue;
        }
        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => get_the_title($ids[$slug]),
            'menu-item-object' => 'page',
            'menu-item-object-id' => $ids[$slug],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $n++,
        ]);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['primary_navigation'] = (int) $menuId;
    set_theme_mod('nav_menu_locations', $locations);

    foreach (mh_portfolio_social_defaults() as $key => $url) {
        $current = get_theme_mod("mh_social_{$key}", '');
        if ($current === '' || $current === 'https://www.linkedin.com/in/matthummel') {
            set_theme_mod("mh_social_{$key}", $url);
        }
    }

    update_option('mh_portfolio_seeded_v2', true);
}

/**
 * Profile photo: Customizer upload, then bundled headshot, then GitHub, then Gravatar.
 */
function mh_profile_photo_url(int $size = 160): string
{
    $id = (int) get_theme_mod('mh_profile_photo', 0);
    if ($id > 0) {
        $src = wp_get_attachment_image_url($id, [$size, $size]);
        if (is_string($src) && $src !== '') {
            return $src;
        }
    }

    $rel = 'resources/images/matt-hummel.jpg';
    if (is_readable(get_theme_file_path($rel))) {
        return get_theme_file_uri($rel);
    }

    $user = Github::fetchUser(mh_github_login());
    if (! empty($user['avatar'])) {
        $sep = str_contains((string) $user['avatar'], '?') ? '&' : '?';

        return $user['avatar'].$sep.'s='.$size;
    }

    $email = (string) get_option('admin_email');

    return $email !== '' ? (string) get_avatar_url($email, ['size' => $size]) : '';
}

add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    $wp->add_section('mh_identity', [
        'title' => __('Profile photo', 'sage'),
        'priority' => 32,
    ]);
    $wp->add_setting('mh_profile_photo', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp->add_control(new \WP_Customize_Media_Control($wp, 'mh_profile_photo', [
        'label' => __('Photo', 'sage'),
        'description' => __('Used next to your name in the header, and larger on Home and About. A square crop works best. Leave empty to keep the photo bundled with the theme.', 'sage'),
        'section' => 'mh_identity',
        'mime_type' => 'image',
    ]));
});

function mh_font_stylesheet(): string
{
    return 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@600;700;800;900&display=swap';
}

$enqueueFonts = static function (): void {
    wp_enqueue_style('mh-fonts', mh_font_stylesheet(), [], null);
};
add_action('wp_enqueue_scripts', $enqueueFonts, 5);
add_action('enqueue_block_editor_assets', $enqueueFonts, 5);
add_filter('style_loader_tag', function (string $html, string $handle): string {
    if ($handle !== 'mh-fonts') {
        return $html;
    }

    $pre = '<link rel="preconnect" href="https://fonts.googleapis.com">'."\n";
    $pre .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'."\n";

    return $pre.$html;
}, 10, 2);

add_action('after_switch_theme', __NAMESPACE__.'\\mh_seed_portfolio_pages');
add_action('init', function () {
    if (! get_option('mh_portfolio_seeded_v2') && ! wp_installing()) {
        mh_seed_portfolio_pages();
    }
}, 30);

add_filter('matthummel/cta_heading', fn () => __('Have a small project in mind?', 'matthummel'));
add_filter('matthummel/cta_text', fn () => __('I take a few WordPress, plugin, and other web-app jobs. Some Power Platform too. Write a short note and I will reply in one or two business days.', 'matthummel'));
add_filter('matthummel/cta_label', fn () => __('Get in touch', 'matthummel'));
