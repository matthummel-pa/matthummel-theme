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
                    'url' => home_url('/code/'),
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
            'intro' => __('Paid packs from studio projects. Story and screenshots live on Work; checkout is optional Shop.', 'sage'),
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
                    'blurb' => __('Managed WordPress hosting I use for client and studio sites (SSH, PHP 8.3, solid support).', 'sage'),
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
                [
                    'name' => __('Full Uses list', 'sage'),
                    'blurb' => __('Stack notes for WordPress, deploy, analytics, and design — with the same disclosure rules.', 'sage'),
                    'url' => home_url('/uses/'),
                    'affiliate' => false,
                    'badge' => __('Stack', 'sage'),
                ],
            ],
        ],
    ];
}
