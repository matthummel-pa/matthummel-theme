<?php

/**
 * Affiliate disclosure and link handling for journal posts.
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

function mh_post_has_affiliate_links(int $postId): bool
{
    return get_post_meta($postId, MH_AFFILIATE_META, true) === '1';
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
