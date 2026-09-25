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

    $title = html_entity_decode(get_the_title($post), ENT_QUOTES);

    $id = wp_insert_post([
        'post_type' => 'newsletter_issue',
        'post_status' => 'draft',
        'post_title' => $title,
        'post_content' => '',
    ], true);

    if (is_wp_error($id) || ! $id) {
        return 0;
    }

    $issueId = (int) $id;
    update_post_meta($issueId, '_mhn_template', 'blog-update');
    update_post_meta($issueId, '_mhn_layout', 'standard');
    update_post_meta($issueId, '_mhn_post_ids', (string) $post->ID);
    update_post_meta($issueId, '_mhn_note', default_note_html());
    update_post_meta($issueId, '_mhn_ps', '');
    update_post_meta($issueId, '_mhn_wizard_step', '2');
    update_post_meta($issueId, '_mhn_subject_auto', '1');
    update_post_meta($issueId, '_mhn_status', 'draft');
    update_post_meta($issueId, '_mhn_source_post', (string) $post->ID);
    update_post_meta($issueId, '_mhn_include_recent', '0');
    update_post_meta($issueId, '_mhn_sent_count', '0');
    update_post_meta($issueId, '_mhn_fail_count', '0');
    compile_issue($issueId);
    fill_subject_defaults($issueId);

    if (settings()['auto_send'] === 1) {
        start_campaign($issueId);
    }

    return $issueId;
}

function issue_status(int $issueId): string
{
    $status = (string) get_post_meta($issueId, '_mhn_status', true);

    return $status !== '' ? $status : 'draft';
}

function issue_is_locked(int $issueId): bool
{
    return in_array(issue_status($issueId), ['sending', 'sent'], true);
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

    echo '<div class="mhn-admin mhn-metabox">';
    echo '<p><a href="'.esc_url(wizard_url($post->ID)).'">'.esc_html__('Continue in the step-by-step wizard', 'matthummel-newsletter').'</a></p>';
    render_issue_audit($post->ID);
    $subject = (string) get_post_meta($post->ID, '_mhn_subject', true);
    $preheader = (string) get_post_meta($post->ID, '_mhn_preheader', true);
    $include = (string) get_post_meta($post->ID, '_mhn_include_recent', true) === '1';
    $local = (string) get_post_meta($post->ID, '_mhn_scheduled_local', true);
    $localValue = $local !== '' ? str_replace(' ', 'T', substr($local, 0, 16)) : '';
    $status = issue_status($post->ID);
    $sent = (int) get_post_meta($post->ID, '_mhn_sent_count', true);
    $failed = (int) get_post_meta($post->ID, '_mhn_fail_count', true);
    $preview = archive_url($post->ID, ['id' => '0', 'email' => 'you@example.com', 'first_name' => '']);
    if (issue_is_locked($post->ID)) {
        render_sent_lock_notice($post->ID);
        echo '<p><strong>'.esc_html(status_label($status)).'</strong></p>';
        echo '<p><strong>'.esc_html__('Subject', 'matthummel-newsletter').'</strong><br>'.esc_html($subject !== '' ? $subject : __('(none)', 'matthummel-newsletter')).'</p>';
        echo '<p><strong>'.esc_html__('Preheader', 'matthummel-newsletter').'</strong><br>'.esc_html($preheader !== '' ? $preheader : __('(none)', 'matthummel-newsletter')).'</p>';
        echo '<p><a class="button" href="'.esc_url($preview).'" target="_blank" rel="noopener">'.esc_html__('Preview', 'matthummel-newsletter').'</a></p>';
        echo '</div>';

        return;
    }

    wp_nonce_field('mhn_issue_meta', 'mhn_issue_nonce');
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
    <p class="description"><?php echo esc_html__('Use two or three columns. They stack on phones. Merge tags: {first_name} {last_name} {full_name} {first_name|there} *|EMAIL|* *|UNSUB|* *|PREFERENCES|* *|ARCHIVE|* *|CURRENT_YEAR|*', 'matthummel-newsletter'); ?></p>
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
    echo '</div>';
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
    if (issue_is_locked($postId)) {
        return;
    }

    update_post_meta($postId, '_mhn_subject', sanitize_text_field(wp_unslash($_POST['mhn_subject'] ?? '')));
    update_post_meta($postId, '_mhn_preheader', sanitize_text_field(wp_unslash($_POST['mhn_preheader'] ?? '')));
    update_post_meta($postId, '_mhn_subject_auto', '0');
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

/**
 * @param  array<string, mixed>  $data
 * @param  array<string, mixed>  $postarr
 * @return array<string, mixed>
 */
function protect_sent_issue(array $data, array $postarr): array
{
    if ((string) ($data['post_type'] ?? '') !== 'newsletter_issue') {
        return $data;
    }

    $id = (int) ($postarr['ID'] ?? 0);
    if ($id < 1 || ! issue_is_locked($id)) {
        return $data;
    }

    $existing = get_post($id);
    if (! $existing instanceof \WP_Post) {
        return $data;
    }

    $data['post_content'] = $existing->post_content;
    $data['post_title'] = $existing->post_title;
    $data['post_excerpt'] = $existing->post_excerpt;

    return $data;
}

function sent_issue_notice(): void
{
    if (! current_user_can('manage_options') || ! function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if ($screen === null || $screen->base !== 'post' || $screen->post_type !== 'newsletter_issue') {
        return;
    }

    $postId = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
    if ($postId < 1) {
        return;
    }

    render_sent_lock_notice($postId);
}

function filter_issue_admin_list(\WP_Query $query): void
{
    global $pagenow;

    if (! is_admin() || ! $query->is_main_query() || $pagenow !== 'edit.php') {
        return;
    }
    if ($query->get('post_type') !== 'newsletter_issue') {
        return;
    }

    $showArchived = isset($_GET['mhn_archived']) && (string) wp_unslash($_GET['mhn_archived']) === '1';
    $meta = [
        'relation' => 'AND',
    ];
    if ($showArchived) {
        $meta[] = [
            'key' => '_mhn_archived',
            'value' => '1',
        ];
    } else {
        $meta[] = [
            'relation' => 'OR',
            [
                'key' => '_mhn_archived',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => '_mhn_archived',
                'value' => '1',
                'compare' => '!=',
            ],
        ];
    }
    $query->set('meta_query', $meta);
}

/**
 * @param  array<string, string>  $views
 * @return array<string, string>
 */
function issue_admin_views(array $views): array
{
    $url = admin_url('edit.php?post_type=newsletter_issue&mhn_archived=1');
    $current = isset($_GET['mhn_archived']) && (string) wp_unslash($_GET['mhn_archived']) === '1';
    $class = $current ? ' class="current"' : '';
    $views['mhn_archived'] = '<a href="'.esc_url($url).'"'.$class.'>'.esc_html__('Archived', 'matthummel-newsletter').'</a>';

    return $views;
}
