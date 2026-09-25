<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function register_type(): void
{
    register_post_type('newsletter_issue', [
        'labels' => [
            'name' => __('Issues', 'matthummel-newsletter'),
            'singular_name' => __('Issue', 'matthummel-newsletter'),
            'add_new' => __('Add issue', 'matthummel-newsletter'),
            'add_new_item' => __('Add issue', 'matthummel-newsletter'),
            'edit_item' => __('Edit issue', 'matthummel-newsletter'),
            'new_item' => __('New issue', 'matthummel-newsletter'),
            'view_item' => __('View issue', 'matthummel-newsletter'),
            'search_items' => __('Search issues', 'matthummel-newsletter'),
            'not_found' => __('No issues yet.', 'matthummel-newsletter'),
            'not_found_in_trash' => __('No issues in the trash.', 'matthummel-newsletter'),
            'all_items' => __('Issues', 'matthummel-newsletter'),
            'item_published' => __('Issue saved.', 'matthummel-newsletter'),
            'item_updated' => __('Issue updated.', 'matthummel-newsletter'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'mhn-newsletter',
        'show_in_rest' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'has_archive' => false,
        'rewrite' => false,
        'menu_icon' => 'dashicons-email',
        'capability_type' => 'page',
        'map_meta_cap' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'revisions'],
        'template' => [
            ['core/paragraph', [
                'placeholder' => __('Write the note. Two or three columns stack on phones.', 'matthummel-newsletter'),
            ]],
        ],
    ]);
}

/**
 * @param  bool|string[]  $allowed
 */
function allowed_blocks(bool|array $allowed, \WP_Block_Editor_Context $context): bool|array
{
    $post = $context->post ?? null;
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return $allowed;
    }

    return [
        'core/paragraph',
        'core/heading',
        'core/image',
        'core/buttons',
        'core/button',
        'core/columns',
        'core/column',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/separator',
        'core/spacer',
        'core/group',
    ];
}

function register_patterns(): void
{
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('mhn', [
        'label' => __('Newsletter', 'matthummel-newsletter'),
    ]);

    $home = esc_url(home_url('/'));
    register_block_pattern('mhn/announcement', [
        'title' => __('Announcement', 'matthummel-newsletter'),
        'description' => __('A short note and one button.', 'matthummel-newsletter'),
        'categories' => ['mhn'],
        'postTypes' => ['newsletter_issue'],
        'content' => '<!-- wp:heading --><h2 class="wp-block-heading">'.esc_html__('A short announcement', 'matthummel-newsletter').'</h2><!-- /wp:heading -->'
            .'<!-- wp:paragraph --><p>'.esc_html__('One or two sentences on what changed.', 'matthummel-newsletter').'</p><!-- /wp:paragraph -->'
            .'<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.$home.'">'.esc_html__('Read more', 'matthummel-newsletter').'</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
    ]);

    register_block_pattern('mhn/digest', [
        'title' => __('Digest', 'matthummel-newsletter'),
        'description' => __('A heading and a short list.', 'matthummel-newsletter'),
        'categories' => ['mhn'],
        'postTypes' => ['newsletter_issue'],
        'content' => '<!-- wp:heading --><h2 class="wp-block-heading">'.esc_html__('This week', 'matthummel-newsletter').'</h2><!-- /wp:heading -->'
            .'<!-- wp:paragraph --><p>'.esc_html__('Three things worth a look.', 'matthummel-newsletter').'</p><!-- /wp:paragraph -->'
            .'<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>'.esc_html__('First note', 'matthummel-newsletter').'</li><!-- /wp:list-item -->'
            .'<!-- wp:list-item --><li>'.esc_html__('Second note', 'matthummel-newsletter').'</li><!-- /wp:list-item -->'
            .'<!-- wp:list-item --><li>'.esc_html__('Third note', 'matthummel-newsletter').'</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
    ]);

    register_block_pattern('mhn/single-feature', [
        'title' => __('Single feature', 'matthummel-newsletter'),
        'description' => __('One story, a quote, and a button.', 'matthummel-newsletter'),
        'categories' => ['mhn'],
        'postTypes' => ['newsletter_issue'],
        'content' => '<!-- wp:heading --><h2 class="wp-block-heading">'.esc_html__('One thing I shipped', 'matthummel-newsletter').'</h2><!-- /wp:heading -->'
            .'<!-- wp:paragraph --><p>'.esc_html__('What it is, who it is for, and where to read the rest.', 'matthummel-newsletter').'</p><!-- /wp:paragraph -->'
            .'<!-- wp:quote --><blockquote class="wp-block-quote"><p>'.esc_html__('A sentence worth keeping.', 'matthummel-newsletter').'</p></blockquote><!-- /wp:quote -->'
            .'<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.$home.'">'.esc_html__('Read more', 'matthummel-newsletter').'</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
    ]);
}

function on_transition(string $new, string $old, \WP_Post $post): void
{
    if ($post->post_type !== 'post' || $new !== 'publish' || $old === 'publish') {
        return;
    }
    if (wp_is_post_revision($post->ID)) {
        return;
    }
    if (settings()['auto_draft'] !== 1) {
        return;
    }

    create_from_post($post);
}

function create_from_post(\WP_Post $post): int
{
    $existing = get_posts([
        'post_type' => 'newsletter_issue',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_mhn_source_post',
        'meta_value' => (string) $post->ID,
    ]);
    if (is_array($existing) && $existing !== []) {
        return (int) $existing[0];
    }

    $excerpt = has_excerpt($post)
        ? wp_strip_all_tags(get_the_excerpt($post))
        : wp_trim_words(wp_strip_all_tags($post->post_content), 40);
    $title = html_entity_decode(get_the_title($post), ENT_QUOTES);

    $id = wp_insert_post([
        'post_type' => 'newsletter_issue',
        'post_status' => 'draft',
        'post_title' => $title,
        'post_content' => post_issue_blocks($post, $excerpt),
    ], true);

    if (is_wp_error($id) || ! $id) {
        return 0;
    }

    $issueId = (int) $id;
    update_post_meta($issueId, '_mhn_subject', $title);
    update_post_meta($issueId, '_mhn_preheader', mb_substr(trim($excerpt), 0, 140));
    update_post_meta($issueId, '_mhn_status', 'draft');
    update_post_meta($issueId, '_mhn_source_post', (string) $post->ID);
    update_post_meta($issueId, '_mhn_include_recent', '1');
    update_post_meta($issueId, '_mhn_sent_count', '0');
    update_post_meta($issueId, '_mhn_fail_count', '0');

    if (settings()['auto_send'] === 1) {
        start_campaign($issueId);
    }

    return $issueId;
}

function post_issue_blocks(\WP_Post $post, string $excerpt): string
{
    $parts = [];
    $thumb = (int) get_post_thumbnail_id($post);
    if ($thumb > 0) {
        $src = wp_get_attachment_image_url($thumb, 'large');
        $alt = (string) get_post_meta($thumb, '_wp_attachment_image_alt', true);
        if ($alt === '') {
            $alt = html_entity_decode(get_the_title($post), ENT_QUOTES);
        }
        if (is_string($src) && $src !== '') {
            $parts[] = '<!-- wp:image {"id":'.$thumb.',"sizeSlug":"large"} -->'
                .'<figure class="wp-block-image size-large"><img src="'.esc_url($src).'" alt="'.esc_attr($alt).'"/></figure>'
                .'<!-- /wp:image -->';
        }
    }

    $title = html_entity_decode(get_the_title($post), ENT_QUOTES);
    $parts[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">".esc_html($title)."</h2>\n<!-- /wp:heading -->";
    $parts[] = "<!-- wp:paragraph -->\n<p>".esc_html($excerpt)."</p>\n<!-- /wp:paragraph -->";
    $url = get_permalink($post);
    $href = is_string($url) ? $url : home_url('/');
    $parts[] = "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n"
        .'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.esc_url($href).'">'
        .esc_html__('Read more', 'matthummel-newsletter').'</a></div>'
        ."\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->";

    return implode("\n\n", $parts);
}

function issue_status(int $issueId): string
{
    $status = (string) get_post_meta($issueId, '_mhn_status', true);

    return $status !== '' ? $status : 'draft';
}

function set_issue_status(int $issueId, string $status): void
{
    update_post_meta($issueId, '_mhn_status', sanitize_key($status));
}

function status_label(string $status): string
{
    return match ($status) {
        'scheduled' => __('Scheduled', 'matthummel-newsletter'),
        'sending' => __('Sending', 'matthummel-newsletter'),
        'sent' => __('Sent', 'matthummel-newsletter'),
        'failed' => __('Failed', 'matthummel-newsletter'),
        default => __('Draft', 'matthummel-newsletter'),
    };
}

function add_meta_box(): void
{
    \add_meta_box(
        'mhn_issue',
        __('Newsletter', 'matthummel-newsletter'),
        __NAMESPACE__.'\\render_meta_box',
        'newsletter_issue',
        'side',
        'high'
    );
}

function render_meta_box(\WP_Post $post): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    wp_nonce_field('mhn_issue_meta', 'mhn_issue_nonce');
    $subject = (string) get_post_meta($post->ID, '_mhn_subject', true);
    $preheader = (string) get_post_meta($post->ID, '_mhn_preheader', true);
    $include = (string) get_post_meta($post->ID, '_mhn_include_recent', true) === '1';
    $local = (string) get_post_meta($post->ID, '_mhn_scheduled_local', true);
    $localValue = $local !== '' ? str_replace(' ', 'T', substr($local, 0, 16)) : '';
    $status = issue_status($post->ID);
    $sent = (int) get_post_meta($post->ID, '_mhn_sent_count', true);
    $failed = (int) get_post_meta($post->ID, '_mhn_fail_count', true);
    $preview = archive_url($post->ID, ['id' => '0', 'email' => 'you@example.com', 'first_name' => '']);
    $test = wp_nonce_url(
        admin_url('admin-post.php?action=mhn_send_test&issue='.$post->ID),
        'mhn_send_test_'.$post->ID
    );
    $deliver = admin_url('admin.php?page=mhn-deliver&issue='.$post->ID);
    $me = wp_get_current_user();
    ?>
    <p><strong><?php echo esc_html(status_label($status)); ?></strong>
        <?php if ($sent > 0 || $failed > 0) { ?>
            <span class="description"><?php echo esc_html(sprintf(
                /* translators: 1: sent count, 2: failed count */
                __('Sent %1$d, failed %2$d.', 'matthummel-newsletter'),
                $sent,
                $failed
            )); ?></span>
        <?php } ?>
    </p>
    <p>
        <label for="mhn-subject"><strong><?php echo esc_html__('Subject', 'matthummel-newsletter'); ?></strong></label>
        <input type="text" class="widefat" id="mhn-subject" name="mhn_subject" value="<?php echo esc_attr($subject); ?>">
    </p>
    <p>
        <label for="mhn-preheader"><strong><?php echo esc_html__('Preheader', 'matthummel-newsletter'); ?></strong></label>
        <input type="text" class="widefat" id="mhn-preheader" name="mhn_preheader" value="<?php echo esc_attr($preheader); ?>" maxlength="140">
    </p>
    <p>
        <label for="mhn-schedule"><strong><?php echo esc_html__('Send at', 'matthummel-newsletter'); ?></strong></label>
        <input type="datetime-local" class="widefat" id="mhn-schedule" name="mhn_schedule_local" value="<?php echo esc_attr($localValue); ?>">
    </p>
    <p>
        <label><input type="checkbox" name="mhn_include_recent" value="1" <?php checked($include); ?>>
            <?php echo esc_html__('Add recent posts', 'matthummel-newsletter'); ?></label>
    </p>
    <p class="description"><?php echo esc_html__('Use two or three columns. They stack on phones. Merge tags: *|FNAME|* *|EMAIL|* *|UNSUB|* *|PREFERENCES|* *|ARCHIVE|* *|CURRENT_YEAR|*', 'matthummel-newsletter'); ?></p>
    <p>
        <a class="button" href="<?php echo esc_url($preview); ?>" target="_blank" rel="noopener"><?php echo esc_html__('Preview', 'matthummel-newsletter'); ?></a>
        <a class="button" href="<?php echo esc_url($test); ?>"><?php echo esc_html(sprintf(
            /* translators: %s: current user email */
            __('Test to %s', 'matthummel-newsletter'),
            (string) $me->user_email
        )); ?></a>
    </p>
    <p><a href="<?php echo esc_url($deliver); ?>"><?php echo esc_html__('Schedule or send', 'matthummel-newsletter'); ?></a></p>
    <?php
}

function save_meta(int $postId, \WP_Post $post): void
{
    if ($post->post_type !== 'newsletter_issue') {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($postId) || ! current_user_can('manage_options')) {
        return;
    }

    $nonce = isset($_POST['mhn_issue_nonce']) ? sanitize_text_field(wp_unslash($_POST['mhn_issue_nonce'])) : '';
    if ($nonce === '' || ! wp_verify_nonce($nonce, 'mhn_issue_meta')) {
        return;
    }

    update_post_meta($postId, '_mhn_subject', sanitize_text_field(wp_unslash($_POST['mhn_subject'] ?? '')));
    update_post_meta($postId, '_mhn_preheader', sanitize_text_field(wp_unslash($_POST['mhn_preheader'] ?? '')));
    update_post_meta($postId, '_mhn_include_recent', empty($_POST['mhn_include_recent']) ? '0' : '1');

    $local = isset($_POST['mhn_schedule_local']) ? sanitize_text_field(wp_unslash($_POST['mhn_schedule_local'])) : '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $local) === 1) {
        $mysql = str_replace('T', ' ', substr($local, 0, 16)).':00';
        update_post_meta($postId, '_mhn_scheduled_local', $mysql);
        update_post_meta($postId, '_mhn_scheduled_gmt', get_gmt_from_date($mysql));
    }

    if ((string) get_post_meta($postId, '_mhn_status', true) === '') {
        update_post_meta($postId, '_mhn_status', 'draft');
    }
}
