<?php

/**
 * Affiliate disclosure and compensated-link helpers (Journal, Uses, Resources).
 */

namespace App;

const MH_AFFILIATE_META = '_mh_has_affiliate_links';

add_action('init', function (): void {
    if (get_option('mh_affiliate_disclosure_page_v1')) {
        return;
    }

    $existing = get_page_by_path('affiliate-disclosure');
    if ($existing instanceof \WP_Post) {
        update_post_meta($existing->ID, '_wp_page_template', 'template-affiliate-disclosure.blade.php');
        update_option('mh_affiliate_disclosure_page_v1', '1', false);

        return;
    }

    $pageId = wp_insert_post([
        'post_title' => 'Affiliate Disclosure',
        'post_name' => 'affiliate-disclosure',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '',
    ]);

    if (! is_wp_error($pageId) && $pageId) {
        update_post_meta((int) $pageId, '_wp_page_template', 'template-affiliate-disclosure.blade.php');
        update_option('mh_affiliate_disclosure_page_v1', '1', false);
    }
}, 70);

/** Whether a journal post is flagged as containing affiliate links. */
function mh_post_has_affiliate_links(int $postId): bool
{
    return get_post_meta($postId, MH_AFFILIATE_META, true) === '1';
}

/**
 * `rel` attribute for outbound links.
 *
 * Compensated links get `sponsored` (FTC-friendly) plus `noopener`.
 */
function mh_outbound_rel(bool $affiliate = false): string
{
    return $affiliate ? 'sponsored noopener' : 'noopener';
}

/**
 * Whether a URL leaves this site (absolute, different host).
 *
 * Relative paths and same-host absolute URLs stay in-tab.
 */
function mh_is_external_url(string $url): bool
{
    $url = trim($url);
    if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
        return false;
    }
    if (! preg_match('#^https?://#i', $url)) {
        return false;
    }

    $host = strtolower((string) (wp_parse_url($url, PHP_URL_HOST) ?: ''));
    $home = strtolower((string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: ''));
    $host = preg_replace('#^www\.#', '', $host) ?: '';
    $home = preg_replace('#^www\.#', '', $home) ?: '';

    return $host !== '' && $home !== '' && $host !== $home;
}

/** Public URL for the affiliate disclosure page. */
function mh_affiliate_disclosure_url(): string
{
    $page = get_page_by_path('affiliate-disclosure');
    if ($page instanceof \WP_Post) {
        $url = get_permalink($page);

        return is_string($url) ? $url : home_url('/affiliate-disclosure/');
    }

    return home_url('/affiliate-disclosure/');
}

/**
 * Short disclosure copy for Uses / Resources / tool lists.
 */
function mh_affiliate_disclosure_note(): string
{
    return __('Some links on this page are affiliate links. If you buy through them, I may earn a commission at no extra cost to you. I only list tools I would use on a real project.', 'sage');
}

add_action('add_meta_boxes_post', function (): void {
    add_meta_box(
        'mh-affiliate-links',
        __('Affiliate links', 'sage'),
        function (\WP_Post $post): void {
            wp_nonce_field('mh_save_affiliate_meta', 'mh_affiliate_nonce');
            echo '<label><input type="checkbox" name="mh_has_affiliate_links" value="1" '.checked(mh_post_has_affiliate_links($post->ID), true, false).'> ';
            echo esc_html__('This post contains compensated affiliate links.', 'sage').'</label>';
            echo '<p class="description">'.esc_html__('Shows a clear disclosure near the start of the article and marks outbound links as sponsored.', 'sage').'</p>';
        },
        'post',
        'side',
        'default'
    );
});

add_action('save_post_post', function (int $postId): void {
    if (! isset($_POST['mh_affiliate_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_affiliate_nonce'])), 'mh_save_affiliate_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $postId)) {
        return;
    }

    update_post_meta($postId, MH_AFFILIATE_META, isset($_POST['mh_has_affiliate_links']) ? '1' : '0');
});

add_filter('the_content', function (string $content): string {
    if (! is_singular('post') || ! mh_post_has_affiliate_links((int) get_the_ID()) || ! class_exists('WP_HTML_Tag_Processor')) {
        return $content;
    }

    $html = new \WP_HTML_Tag_Processor($content);

    while ($html->next_tag('a')) {
        if (! $html->has_class('affiliate-link') && $html->get_attribute('data-affiliate') !== 'true') {
            continue;
        }

        $existing = (string) $html->get_attribute('rel');
        $tokens = array_filter(preg_split('/\s+/', trim($existing)) ?: []);
        $tokens = array_values(array_unique([...$tokens, 'sponsored', 'noopener']));
        $html->set_attribute('rel', implode(' ', $tokens));
    }

    return $html->get_updated_html();
}, 20);

/**
 * Curated Resources catalog: free starters + recommended tools (some affiliate).
 *
 * Replace affiliate URLs with your live partner links when you join a program.
 * Set `affiliate` => true only when the URL is a compensated referral.
 * `key` is what the Resource links block stores to pick a group; do not rename one.
 *
 * @return list<array{key: string, title: string, intro: string, items: list<array{name: string, blurb: string, url: string, affiliate: bool, badge: string}>}>
 */
function mh_resources_catalog(): array
{
    $gh = 'https://github.com/'.mh_github_login();

    return [
        [
            'key' => 'starters',
            'title' => __('Free starters and open source', 'sage'),
            'intro' => __('Free code and example sites. Look around, copy what helps, or hire me to turn one into a finished site.', 'sage'),
            'items' => [
                [
                    'name' => __('Projects', 'sage'),
                    'blurb' => __('Live demos you can click through.', 'sage'),
                    'use' => __('A working demo of each theme and plugin, with notes on how it was built and a link to the code.', 'sage'),
                    'fit' => __('Seeing how I build before you hire me.', 'sage'),
                    'icon' => 'page',
                    'cta' => __('Browse projects', 'sage'),
                    'use_label' => __('What you get', 'sage'),
                    'fit_label' => __('Good for', 'sage'),
                    'url' => home_url('/projects/'),
                    'affiliate' => false,
                    'badge' => __('Example sites', 'sage'),
                ],
                [
                    'name' => __('Code on this site', 'sage'),
                    'blurb' => __('Read the code behind this site.', 'sage'),
                    'use' => __('My pinned projects, recent updates, and the languages I work in, pulled straight from GitHub.', 'sage'),
                    'fit' => __('Developers who want to check my work.', 'sage'),
                    'icon' => 'code',
                    'cta' => __('See the code', 'sage'),
                    'use_label' => __('What you get', 'sage'),
                    'fit_label' => __('Good for', 'sage'),
                    'url' => home_url('/about/#code'),
                    'affiliate' => false,
                    'badge' => __('Open source', 'sage'),
                ],
                [
                    'name' => __('GitHub profile', 'sage'),
                    'blurb' => __('Everything public, in one place.', 'sage'),
                    'use' => __('My public themes, plugins, and apps. Most are free to reuse, and each project says how.', 'sage'),
                    'fit' => __('Copying a project as the starting point for your own.', 'sage'),
                    'icon' => 'github',
                    'cta' => __('Open GitHub', 'sage'),
                    'use_label' => __('What you get', 'sage'),
                    'fit_label' => __('Good for', 'sage'),
                    'url' => $gh,
                    'affiliate' => false,
                    'badge' => __('GitHub', 'sage'),
                ],
                [
                    'name' => __('AI Dev Standards Playbook', 'sage'),
                    'blurb' => __('The AI coding setup I use, shared.', 'sage'),
                    'use' => __('The rules and shortcuts I give Claude Code on every WordPress project. Install it once and it stays up to date.', 'sage'),
                    'fit' => __('Developers who use Claude Code for WordPress work.', 'sage'),
                    'icon' => 'claude',
                    'cta' => __('Get the playbook', 'sage'),
                    'use_label' => __('What you get', 'sage'),
                    'fit_label' => __('Good for', 'sage'),
                    'url' => $gh.'/ai-dev-standards-playbook',
                    'affiliate' => false,
                    'badge' => __('Claude Code', 'sage'),
                ],
                [
                    'name' => 'TOCguide',
                    'blurb' => __('A free table-of-contents plugin.', 'sage'),
                    'use' => __('A free plugin from the official WordPress plugin directory.', 'sage'),
                    'fit' => __('Long posts and guides that need a table of contents.', 'sage'),
                    'icon' => 'wordpress',
                    'cta' => __('Get TOCguide', 'sage'),
                    'use_label' => __('What you get', 'sage'),
                    'fit_label' => __('Good for', 'sage'),
                    'url' => 'https://wordpress.org/plugins/tocguide/',
                    'affiliate' => false,
                    'badge' => __('Plugin', 'sage'),
                ],
            ],
        ],
        [
            'key' => 'themes',
            'title' => __('Themes for sale', 'sage'),
            'intro' => __('Paid themes from my WordPress projects. Read the project page first, then buy from the shop if it fits.', 'sage'),
            'items' => [
                [
                    'name' => __('Project pages', 'sage'),
                    'blurb' => __('What each theme is for, how it works, and a demo you can try before you buy.', 'sage'),
                    'url' => home_url('/projects/'),
                    'affiliate' => false,
                    'badge' => __('Start here', 'sage'),
                ],
                [
                    'name' => __('Theme shop', 'sage'),
                    'blurb' => __('The short list of themes for sale. Each product links back to its project page.', 'sage'),
                    'url' => home_url('/shop/'),
                    'affiliate' => false,
                    'badge' => __('Paid', 'sage'),
                ],
            ],
        ],
        [
            'key' => 'tools',
            'title' => __('Tools I use every week', 'sage'),
            'intro' => __('The AI, hosting, and automation tools I use. Referral links are marked.', 'sage'),
            'items' => [
                [
                    'name' => 'Claude Code',
                    'blurb' => __('An AI assistant for writing and checking code.', 'sage'),
                    'use' => __('Planning, cleanup, and code reviews on every project. I read and test everything before it goes live.', 'sage'),
                    'fit' => __('Developers who want a helper, not an autopilot.', 'sage'),
                    'icon' => 'claude',
                    'url' => 'https://claude.com/product/claude-code',
                    'affiliate' => false,
                    'badge' => __('AI', 'sage'),
                ],
                [
                    'name' => 'Hostinger',
                    'blurb' => __('Fast, managed WordPress hosting.', 'sage'),
                    'use' => __('This site lives here. It\'s fast, has built-in backups, and gives me a test copy to try changes on before they go live.', 'sage'),
                    'fit' => __('Small businesses that want a fast site without looking after a server.', 'sage'),
                    'icon' => 'server',
                    // Swap in the referral URL and set 'affiliate' => true only after joining Hostinger's program.
                    'url' => 'https://www.hostinger.com',
                    'affiliate' => false,
                    'badge' => __('Hosting', 'sage'),
                ],
                [
                    'name' => 'n8n',
                    'blurb' => __('Connects your apps so busywork runs itself.', 'sage'),
                    'use' => __('Contact forms, notifications, and content tasks. It links WordPress to my other tools without custom code each time.', 'sage'),
                    'fit' => __('Anyone who keeps repeating the same task between two apps.', 'sage'),
                    'icon' => 'n8n',
                    'url' => 'https://n8n.io',
                    'affiliate' => false,
                    'badge' => __('Automation', 'sage'),
                ],
                [
                    'name' => 'GitHub',
                    'blurb' => __('Where all my project code lives.', 'sage'),
                    'use' => __('Every project is stored here. When I save a change, it packages the theme for install automatically, so nothing gets uploaded by hand.', 'sage'),
                    'fit' => __('Any project that more than one person, or one future you, will touch.', 'sage'),
                    'icon' => 'github',
                    'url' => 'https://github.com',
                    'affiliate' => false,
                    'badge' => __('Code', 'sage'),
                ],
                [
                    'name' => 'WordPress Studio',
                    'blurb' => __('A real WordPress site on my laptop.', 'sage'),
                    'use' => __('A copy of each site on my laptop, so I can build and break things before anyone else sees them.', 'sage'),
                    'fit' => __('Developers who want a test site running in minutes.', 'sage'),
                    'icon' => 'wordpress',
                    'url' => 'https://developer.wordpress.com/studio/',
                    'affiliate' => false,
                    'badge' => __('Testing', 'sage'),
                ],
                [
                    'name' => 'Rank Math',
                    'blurb' => __('Search settings you can manage yourself.', 'sage'),
                    'use' => __('Page titles, search descriptions, and redirects, all editable in WordPress without touching code.', 'sage'),
                    'fit' => __('Site owners who want to manage their own search basics.', 'sage'),
                    'icon' => 'search',
                    'url' => 'https://rankmath.com',
                    'affiliate' => false,
                    'badge' => __('SEO', 'sage'),
                ],
            ],
        ],
    ];
}

/**
 * The stack behind shipped work, grouped for the Resources page (was /uses/ until 3.6.50).
 *
 * Each item is [name, description, url|null, affiliate?]. A 4th value of true marks a compensated link.
 *
 * @return list<array{title: string, icon: string, items: list<array>}>
 */
function mh_uses_sections(): array
{
    return [
        [
            'title' => __('Building WordPress sites', 'sage'),
            'blurb' => __('What every site I build is made with.', 'sage'),
            'icon' => 'wordpress',
            'items' => [
                ['Sage 11', __('The starting point for every custom WordPress theme I build. It keeps the code clean and organized.', 'sage'), 'https://roots.io/sage/'],
                ['PHP 8.3', __('The programming language WordPress runs on. I keep every site on a current, supported version.', 'sage'), null],
                ['Tailwind v4', __('A styling toolkit that keeps designs consistent and builds every page for phones first.', 'sage'), 'https://tailwindcss.com'],
                ['Vite', __('Bundles a site\'s styles and scripts into small files so pages load quickly.', 'sage'), 'https://vite.dev'],
                ['Acorn / Laravel', __('Brings proven tools from the Laravel framework into WordPress, so custom features are easier to build and keep running.', 'sage'), 'https://roots.io/acorn/'],
                ['WP-CLI', __('Runs WordPress tasks like database exports and plugin updates from the command line, without clicking through the dashboard.', 'sage'), 'https://wordpress.org/cli/'],
                ['Composer', __('Keeps track of the code libraries a site depends on, so nothing is downloaded and copied by hand.', 'sage'), 'https://getcomposer.org'],
                ['Laravel Pint', __('Tidies up code formatting automatically before every release.', 'sage'), 'https://laravel.com/framework/docs/pint'],
                ['Block editor (Gutenberg)', __('The standard WordPress editor. Pages are built from blocks you can edit yourself, with no hidden code.', 'sage'), 'https://developer.wordpress.org/block-editor/'],
                ['PHPCS + WordPress Coding Standards', __('Checks every code change against WordPress\'s own security and quality rules.', 'sage'), 'https://github.com/WordPress/WordPress-Coding-Standards'],
                ['PHPStan', __('Scans the code for mistakes before a person ever reviews it.', 'sage'), 'https://phpstan.org'],
            ],
        ],
        [
            'title' => __('Where I write code', 'sage'),
            'blurb' => __('Where I plan, write, and check my work.', 'sage'),
            'icon' => 'code',
            'items' => [
                ['Claude Code', __('An AI assistant for planning and reviewing code. It follows my own rules and checks each change for common WordPress problems.', 'sage'), 'https://claude.com/product/claude-code'],
                ['GitHub', __('Every project\'s code lives here, with a full history of every change.', 'sage'), 'https://github.com'],
                ['GitHub Actions', __('Builds and packages each release automatically, so nothing is uploaded by hand.', 'sage'), 'https://github.com/features/actions'],
                ['TablePlus', __('A simple app for looking inside a site\'s database without writing code.', 'sage'), 'https://tableplus.com'],
                ['iTerm2 / zsh', __('My command line. Nothing fancy, just where I connect to servers and run WordPress commands.', 'sage'), null],
            ],
        ],
        [
            'title' => __('Hosting and going live', 'sage'),
            'blurb' => __('How a site gets from my laptop to the internet.', 'sage'),
            'icon' => 'server',
            'items' => [
                ['Hostinger', __('Hosting for this site. Fast caching, an up-to-date server, and an easy control panel. Theme updates install straight from GitHub.', 'sage'), 'https://www.hostinger.com', true],
                ['Freemius', __('Handles checkout and license keys for the themes I sell. Freemius takes the payment, so card details never touch this site.', 'sage'), 'https://freemius.com'],
                ['GitHub Releases', __('How theme updates reach this site. Each update is packaged on GitHub and installed securely from WordPress, with no file uploads.', 'sage'), null],
                ['WordPress Studio / SQLite', __('Runs WordPress on my laptop for building and testing. Quick to set up, with nothing extra to install.', 'sage'), null],
            ],
        ],
        [
            'title' => __('Search and analytics', 'sage'),
            'blurb' => __('How people find the site, and what they do once they\'re here.', 'sage'),
            'icon' => 'chart-bar',
            'items' => [
                ['Google Analytics 4', __('Shows how many people visit, which pages they read, and when someone buys a theme.', 'sage'), 'https://marketingplatform.google.com/about/analytics/'],
                ['Site Kit by Google', __('Google\'s free WordPress plugin. It connects the site to Google Analytics without extra code.', 'sage'), 'https://sitekit.withgoogle.com'],
                ['Rank Math', __('Sets the title and description each page shows in search results. The theme fills in sensible defaults when one is blank.', 'sage'), 'https://rankmath.com'],
                ['IndexNow', __('Tells Bing and other search engines the moment a page is published or updated, so it shows up sooner.', 'sage'), 'https://www.indexnow.org'],
                ['Bing Webmaster Tools', __('Shows how the site is doing in Bing search results.', 'sage'), 'https://www.bing.com/webmasters'],
            ],
        ],
        [
            'title' => __('AI and automation', 'sage'),
            'blurb' => __('Tools that take care of repeat work.', 'sage'),
            'icon' => 'n8n',
            'items' => [
                ['n8n', __('Handles what happens after someone fills out a form on this site, like sending notifications.', 'sage'), 'https://n8n.io'],
                ['AI Dev Standards Playbook', __('My public set of rules for Claude Code. Install it once and it stays up to date.', 'sage'), 'https://github.com/matthummel-pa/ai-dev-standards-playbook'],
                ['WPVibe', __('Lets Claude help with WordPress posts, pages, and settings. Theme changes start as a draft, and I decide what goes live.', 'sage'), 'https://wpvibe.ai'],
                ['Notion', __('Project notes, plans, and to-do lists in one place.', 'sage'), 'https://www.notion.com'],
            ],
        ],
        [
            'title' => __('Testing and accessibility', 'sage'),
            'blurb' => __('Checks every site gets before launch.', 'sage'),
            'icon' => 'shield',
            'items' => [
                ['Lighthouse', __('Google\'s free check for speed and search basics. I aim for a score of 90 or higher on phones.', 'sage'), 'https://developer.chrome.com/docs/lighthouse/'],
                ['axe-core', __('Checks pages for accessibility problems automatically. I also test with a keyboard and a screen reader, because tools miss things.', 'sage'), 'https://github.com/dequelabs/axe-core'],
            ],
        ],
        [
            'title' => __('Design and fonts', 'sage'),
            'blurb' => __('Fonts, plus design tools when a project needs them.', 'sage'),
            'icon' => 'full-stack',
            'items' => [
                ['Inter', __('The font for headings. Clean and easy to read at any size.', 'sage'), 'https://rsms.me/inter/'],
                ['IBM Plex Sans', __('The font for body text. A little warmer than Inter and easy on the eyes.', 'sage'), 'https://www.ibm.com/plex/'],
                ['IBM Plex Mono', __('The font for code samples in blog posts.', 'sage'), 'https://www.ibm.com/plex/'],
                ['Figma', __('For mockups when a client wants to see designs before I build. Most of the time I design right in the browser.', 'sage'), 'https://www.figma.com'],
                ['highlight.js', __('Adds color to code samples in blog posts, plus a copy button.', 'sage'), 'https://highlightjs.org'],
            ],
        ],
        [
            'title' => __('Power Platform', 'sage'),
            'blurb' => __('Microsoft tools from earlier jobs.', 'sage'),
            'icon' => 'power',
            'items' => [
                ['Power Apps', __('Custom business apps for teams on Microsoft 365. I used it in earlier jobs and still reach for it on client work when it fits.', 'sage'), 'https://www.microsoft.com/en-us/power-platform/products/power-apps/'],
                ['Power Automate', __('Automates routine office tasks like approvals and Teams alerts. Handy when a team already works in Microsoft 365.', 'sage'), 'https://www.microsoft.com/en-us/power-platform/products/power-automate/'],
                ['SharePoint', __('Microsoft\'s place for shared documents and lists. Often where Power Apps keep their data.', 'sage'), null],
            ],
        ],
    ];
}

/**
 * One group of the Resources catalog by its key ("starters", "themes", "tools").
 *
 * @return array{key: string, title: string, intro: string, items: list<array<string, mixed>>}|null
 */
function mh_resources_catalog_group(string $key): ?array
{
    foreach (mh_resources_catalog() as $group) {
        if ($group['key'] === $key) {
            return $group;
        }
    }

    return null;
}

/**
 * Whether the Resources catalog or the stack list holds at least one compensated link.
 */
function mh_resources_have_affiliate_links(): bool
{
    foreach (mh_resources_catalog() as $group) {
        foreach ($group['items'] as $item) {
            if (! empty($item['affiliate'])) {
                return true;
            }
        }
    }
    foreach (mh_uses_sections() as $group) {
        foreach ($group['items'] as $row) {
            if (! empty($row[3])) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Whether a block that prints affiliate links has to print the disclosure itself.
 *
 * True when the page it sits on has no Affiliate disclosure block, so the note cannot be lost by deleting that block.
 */
function mh_affiliate_note_is_missing(): bool
{
    $post = get_post();

    return ! $post instanceof \WP_Post || ! has_block('matthummel/affiliate-note', $post);
}

/**
 * Jump links for the Resources overview: every Section on the page that holds a Resource links or Stack list block.
 *
 * @return list<array{anchor: string, label: string, count: int}>
 */
function mh_resources_jump_rows(?int $post_id = null): array
{
    $post = get_post($post_id ?: (int) get_the_ID());
    if (! $post instanceof \WP_Post) {
        return [];
    }

    $rows = [];
    foreach (parse_blocks((string) $post->post_content) as $block) {
        $anchor = sanitize_html_class((string) ($block['attrs']['anchor'] ?? ''));
        if (($block['blockName'] ?? '') !== 'matthummel/section' || $anchor === '') {
            continue;
        }
        foreach ((array) ($block['innerBlocks'] ?? []) as $inner) {
            if (($inner['blockName'] ?? '') === 'matthummel/resource-catalog') {
                $group = mh_resources_catalog_group((string) ($inner['attrs']['section'] ?? 'starters'));
                if ($group !== null) {
                    $rows[] = ['anchor' => $anchor, 'label' => $group['title'], 'count' => count($group['items'])];
                }
            }
            if (($inner['blockName'] ?? '') === 'matthummel/stack-list') {
                $rows[] = ['anchor' => $anchor, 'label' => __('More tools', 'sage'), 'count' => count(mh_uses_sections())];
            }
        }
    }

    return $rows;
}
