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
 *
 * @return list<array{title: string, intro: string, items: list<array{name: string, blurb: string, url: string, affiliate: bool, badge: string}>}>
 */
function mh_resources_catalog(): array
{
    $gh = 'https://github.com/'.mh_github_login();

    return [
        [
            'title' => __('Free starters', 'sage'),
            'intro' => __('Open code and example builds from this site. Fork them, read them, or hire me to turn one into a production site.', 'sage'),
            'items' => [
                [
                    'name' => __('Work — example sites', 'sage'),
                    'blurb' => __('WordPress themes and plugins with stack notes, demos, and buy/help CTAs when a pack is for sale.', 'sage'),
                    'url' => home_url('/projects/'),
                    'affiliate' => false,
                    'badge' => __('Portfolio', 'sage'),
                ],
                [
                    'name' => __('Code & GitHub', 'sage'),
                    'blurb' => __('Public repos, contribution activity, and snippets you can adapt. Proof of how I ship.', 'sage'),
                    'url' => home_url('/about/#code'),
                    'affiliate' => false,
                    'badge' => __('Open source', 'sage'),
                ],
                [
                    'name' => __('GitHub profile', 'sage'),
                    'blurb' => __('Themes, plugins, and apps under MIT or similar licenses unless a repo says otherwise.', 'sage'),
                    'url' => $gh,
                    'affiliate' => false,
                    'badge' => __('GitHub', 'sage'),
                ],
            ],
        ],
        [
            'title' => __('Themes for sale', 'sage'),
            'intro' => __('Paid packs from my WordPress projects. Story and screenshots live on Projects; checkout is optional Shop.', 'sage'),
            'items' => [
                [
                    'name' => __('Browse Work', 'sage'),
                    'blurb' => __('See the product page first — problem, approach, stack — then buy if you want the theme or plugin pack.', 'sage'),
                    'url' => home_url('/projects/'),
                    'affiliate' => false,
                    'badge' => __('Primary', 'sage'),
                ],
                [
                    'name' => __('Theme shop', 'sage'),
                    'blurb' => __('Short catalog of purchasable themes. Product links open the matching Work landing.', 'sage'),
                    'url' => function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : home_url('/shop/'),
                    'affiliate' => false,
                    'badge' => __('Paid', 'sage'),
                ],
            ],
        ],
        [
            'title' => __('Tools I recommend', 'sage'),
            'intro' => __('Hosting, editors, and marketing tools I actually use. Affiliate links are marked; swap in your partner URLs when active.', 'sage'),
            'items' => [
                [
                    'name' => 'Cursor',
                    'blurb' => __('AI-assisted editor I use daily for Sage, PHP, and front-end work.', 'sage'),
                    'url' => 'https://cursor.com',
                    'affiliate' => false,
                    'badge' => __('Editor', 'sage'),
                ],
                [
                    'name' => 'SiteGround',
                    'blurb' => __('Managed WordPress hosting I use for client and personal sites (SSH, PHP 8.3, solid support).', 'sage'),
                    'url' => 'https://www.siteground.com',
                    'affiliate' => true,
                    'badge' => __('Hosting', 'sage'),
                ],
                [
                    'name' => 'HubSpot',
                    'blurb' => __('CRM and form capture when a shop needs a simple pipeline without a custom build.', 'sage'),
                    'url' => 'https://www.hubspot.com',
                    'affiliate' => true,
                    'badge' => __('CRM', 'sage'),
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
            'title' => __('WordPress development', 'sage'),
            'icon' => 'wordpress',
            'items' => [
                ['Sage 11', __('The Roots starter theme. Blade templates, Tailwind v4, Vite. Every site I build from scratch starts here.', 'sage'), 'https://roots.io/sage/'],
                ['PHP 8.3', __('The language everything runs on. Typed functions, match expressions, named arguments. Nothing exotic.', 'sage'), null],
                ['Tailwind v4', __('CSS utility framework. Token-based, no config file needed. Fluid type with clamp(), mobile-first always.', 'sage'), 'https://tailwindcss.com'],
                ['Vite', __('Asset bundler. Fast, simple config. Handles CSS, JS, and image hashing for cache-busting.', 'sage'), 'https://vitejs.dev'],
                ['Acorn / Laravel', __('The IoC container that makes Sage feel like a proper application. Service providers, Blade directives, view composers.', 'sage'), 'https://roots.io/acorn/'],
                ['WP-CLI', __('Command-line tools for WordPress. Database exports, plugin management, custom commands. Most deploys never touch wp-admin.', 'sage'), 'https://wp-cli.org'],
                ['Composer', __('PHP dependency manager. Every project has a composer.json. No manual library downloads.', 'sage'), 'https://getcomposer.org'],
                ['Laravel Pint', __('PHP code style fixer. Runs in CI before every deploy. Catches whitespace and formatting issues automatically.', 'sage'), 'https://laravel.com/docs/pint'],
            ],
        ],
        [
            'title' => __('Editor and tools', 'sage'),
            'icon' => 'code',
            'items' => [
                ['Cursor', __('My primary editor. VS Code-compatible with an AI layer that helps rather than gets in the way. This site was planned and built with it and Claude.', 'sage'), 'https://cursor.com'],
                ['GitHub', __('Version control and CI/CD trigger. Every project lives in a GitHub repo. Pushes to main kick off builds and deploys automatically.', 'sage'), 'https://github.com'],
                ['GitHub Actions', __('Automated build and deploy pipeline. Runs Composer, npm, and the release zip on every push. Zero manual uploads.', 'sage'), 'https://github.com/features/actions'],
                ['TablePlus', __('Database GUI for local MySQL and SQLite. Useful for inspecting WordPress tables without writing raw SQL.', 'sage'), 'https://tableplus.com'],
                ['iTerm2 / zsh', __('Terminal. Nothing special — zsh with a minimal prompt. SSH into servers, run WP-CLI, tail logs.', 'sage'), null],
            ],
        ],
        [
            'title' => __('Hosting and deploy', 'sage'),
            'icon' => 'server',
            'items' => [
                ['Hostinger', __('Managed WordPress hosting for this site. LiteSpeed cache, PHP 8.3+, hPanel. Theme installs from the GitHub theme-latest zip.', 'sage'), 'https://www.hostinger.com'],
                ['GitHub Releases', __('Theme deployment method for this site. CI builds a zip, publishes it as a release, and wp-admin pulls it over HTTPS. No FTP.', 'sage'), null],
                ['WordPress Studio / SQLite', __('Local WordPress for development. No MySQL required, no Docker overhead.', 'sage'), null],
            ],
        ],
        [
            'title' => __('Analytics and marketing', 'sage'),
            'icon' => 'chart-bar',
            'items' => [
                ['Google Analytics 4', __('Site traffic, page performance, and audience data. Linked through Google Tag Manager.', 'sage'), 'https://analytics.google.com'],
                ['Google Tag Manager', __('Single container for all tracking scripts. One snippet on the page, everything else managed in GTM.', 'sage'), 'https://tagmanager.google.com'],
                ['HubSpot', __('CRM and contact capture. Picks up form submissions and tracks visitor activity for follow-up.', 'sage'), 'https://www.hubspot.com', true],
                ['Microsoft Clarity / Bing', __('Bing Webmaster Tools for search performance. Microsoft UET for ad conversion tracking.', 'sage'), 'https://clarity.microsoft.com'],
            ],
        ],
        [
            'title' => __('Design and typography', 'sage'),
            'icon' => 'full-stack',
            'items' => [
                ['Inter', __('Display and heading font. Clean, reads well at both large and small sizes. The workhorse.', 'sage'), 'https://rsms.me/inter/'],
                ['IBM Plex Sans', __('Body text font. Slightly warmer than Inter. Works well at the 1.1–1.2rem range.', 'sage'), 'https://www.ibm.com/plex/'],
                ['IBM Plex Mono', __('Code font. Used in blog post code blocks and the monospace CSS variable.', 'sage'), 'https://www.ibm.com/plex/'],
                ['Figma', __('Design when a client needs wireframes or component specs before I build. Not a daily tool — I prefer designing in the browser.', 'sage'), 'https://www.figma.com'],
                ['highlight.js', __('Syntax highlighting for blog post code blocks. Copy button added via a small custom JS module.', 'sage'), 'https://highlightjs.org'],
            ],
        ],
        [
            'title' => __('Power Platform', 'sage'),
            'icon' => 'power',
            'items' => [
                ['Power Apps', __('Canvas and model-driven apps for Microsoft 365 environments. Used at previous roles and on client work when it is the right tool.', 'sage'), 'https://powerapps.microsoft.com'],
                ['Power Automate', __('Workflow automation. Approval flows, SharePoint triggers, Teams notifications. Useful when a team already lives in M365.', 'sage'), 'https://powerautomate.microsoft.com'],
                ['SharePoint', __('Common data source for Power Apps. Lists, document libraries, and permissions.', 'sage'), null],
            ],
        ],
    ];
}
