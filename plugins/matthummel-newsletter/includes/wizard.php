<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function wizard_url(int $issueId = 0, int $step = 0): string
{
    $args = ['page' => 'mhn-wizard'];
    if ($issueId > 0) {
        $args['issue'] = $issueId;
    }
    if ($step > 0) {
        $args['step'] = $step;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

/**
 * @param  array<string, mixed>  $input
 * @return array{id: int, step: int, error: string, tested: string, queued: string, scheduled: string, find: string}
 */
function wizard_save(array $input): array
{
    $result = [
        'id' => 0,
        'step' => 1,
        'error' => '',
        'tested' => '',
        'queued' => '',
        'scheduled' => '',
        'find' => sanitize_text_field((string) ($input['mhn_find'] ?? '')),
    ];

    $action = sanitize_key((string) ($input['mhn_action'] ?? 'stay'));
    if (! in_array($action, ['back', 'next', 'stay', 'test', 'send', 'schedule'], true)) {
        $action = 'stay';
    }

    $step = wizard_step_number((int) ($input['mhn_step'] ?? 1));
    $id = wizard_issue_id((int) ($input['mhn_issue'] ?? 0));
    if ($id < 1) {
        $slug = sanitize_key((string) ($input['mhn_template'] ?? ''));
        if (email_template($slug) === null) {
            $result['error'] = 'template';
            $result['step'] = 1;

            return $result;
        }
        $layoutId = normalize_layout_id((string) ($input['mhn_layout'] ?? ''));
        $id = create_wizard_issue($slug, $layoutId);
    }
    if ($id < 1) {
        $result['error'] = 'save';
        $result['step'] = $step;

        return $result;
    }

    $result['id'] = $id;
    if (! issue_is_locked($id)) {
        apply_wizard_fields($id, $input, $step);
    }

    if ($action === 'next') {
        $error = wizard_blocks_next($id, $step);
        if ($error !== '') {
            $result['error'] = $error;
            $result['step'] = $step;
            update_post_meta($id, '_mhn_wizard_step', (string) $step);

            return $result;
        }
        $step = min(5, $step + 1);
    } elseif ($action === 'back') {
        $step = max(1, $step - 1);
    } elseif ($action === 'test') {
        $result['tested'] = send_test($id) ? '1' : '0';
        $step = 4;
    } elseif ($action === 'send') {
        $step = 5;
        if (issue_is_locked($id)) {
            $result['error'] = 'locked';
        } elseif (issue_send_blocked($id)) {
            $result['error'] = 'a11y';
        } elseif (issue_skips_sent_post($id)) {
            $result['error'] = 'sent_post';
        } elseif (subscribed_recipient_count() < 1) {
            $result['error'] = 'empty';
        } elseif (empty($input['mhn_confirm_send'])) {
            $result['error'] = 'confirm';
        } elseif (! start_campaign($id)) {
            $result['error'] = 'locked';
        } else {
            $result['queued'] = '1';
        }
    } elseif ($action === 'schedule') {
        $step = 5;
        $local = sanitize_text_field((string) ($input['mhn_schedule_local'] ?? ''));
        if (issue_is_locked($id)) {
            $result['error'] = 'locked';
        } elseif (issue_send_blocked($id)) {
            $result['error'] = 'a11y';
        } elseif (issue_skips_sent_post($id)) {
            $result['error'] = 'sent_post';
        } elseif (subscribed_recipient_count() < 1) {
            $result['error'] = 'empty';
        } elseif (empty($input['mhn_confirm_schedule']) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $local) !== 1) {
            $result['error'] = 'schedule';
        } else {
            $mysql = str_replace('T', ' ', substr($local, 0, 16)).':00';
            update_post_meta($id, '_mhn_scheduled_local', $mysql);
            if (! schedule_issue($id, get_gmt_from_date($mysql))) {
                $result['error'] = 'locked';
            } else {
                $result['scheduled'] = '1';
            }
        }
    }

    update_post_meta($id, '_mhn_wizard_step', (string) $step);
    $result['step'] = $step;

    return $result;
}

function create_wizard_issue(string $slug, string $layoutId = 'standard'): int
{
    $template = email_template($slug);
    if ($template === null) {
        return 0;
    }

    $id = wp_insert_post([
        'post_type' => 'newsletter_issue',
        'post_status' => 'draft',
        'post_title' => $template['label'],
        'post_content' => '',
    ], true);
    if (is_wp_error($id) || ! $id) {
        return 0;
    }

    $issueId = (int) $id;
    $layoutId = normalize_layout_id($layoutId);
    update_post_meta($issueId, '_mhn_template', $slug);
    update_post_meta($issueId, '_mhn_layout', $layoutId);
    remember_layout($layoutId);
    update_post_meta($issueId, '_mhn_status', 'draft');
    update_post_meta($issueId, '_mhn_wizard_step', '1');
    update_post_meta($issueId, '_mhn_note', default_note_html());
    update_post_meta($issueId, '_mhn_ps', '');
    update_post_meta($issueId, '_mhn_post_ids', implode(',', default_post_ids($slug)));
    update_post_meta($issueId, '_mhn_include_recent', '0');
    update_post_meta($issueId, '_mhn_sent_count', '0');
    update_post_meta($issueId, '_mhn_fail_count', '0');
    update_post_meta($issueId, '_mhn_subject_auto', '1');
    update_post_meta($issueId, '_mhn_editor', 'simple');
    update_post_meta($issueId, '_mhn_blocks', empty_editor_blocks());
    compile_issue($issueId);
    fill_subject_defaults($issueId);

    return $issueId;
}

/**
 * @param  array<string, mixed>  $input
 */
function apply_wizard_fields(int $issueId, array $input, int $step): void
{
    save_issue_letter_style($issueId, $input);
    if (isset($input['mhn_editor'])) {
        update_post_meta($issueId, '_mhn_editor', normalize_editor_mode((string) $input['mhn_editor']));
    }
    if (normalize_editor_mode((string) ($input['mhn_editor'] ?? issue_editor_mode($issueId))) === 'advanced' && advanced_fields_posted($input)) {
        update_post_meta($issueId, '_mhn_blocks', sanitize_editor_blocks($input));
    }

    $postedLayout = sanitize_key((string) ($input['mhn_layout'] ?? ''));
    if ($postedLayout !== '' && isset(layouts()[$postedLayout])) {
        $previousLayout = (string) get_post_meta($issueId, '_mhn_layout', true);
        if ($previousLayout === '') {
            $previousLayout = 'standard';
        }
        if ($previousLayout !== $postedLayout) {
            update_post_meta($issueId, '_mhn_layout', $postedLayout);
            remember_layout($postedLayout);
            if ($postedLayout === 'welcome' || $previousLayout === 'welcome') {
                update_post_meta($issueId, '_mhn_subject_auto', '1');
                fill_subject_defaults($issueId);
            }
        } else {
            remember_layout($postedLayout);
        }
    }

    save_blog_post_choice($issueId, $input);
    $sections = sections_from_request($input);
    if ($sections !== null && issue_layout_id($issueId) === 'post') {
        update_post_meta($issueId, '_mhn_sections', $sections);
    }

    $postedTemplate = sanitize_key((string) ($input['mhn_template'] ?? ''));
    if ($postedTemplate !== '' && email_template($postedTemplate) !== null) {
        $previous = (string) get_post_meta($issueId, '_mhn_template', true);
        if ($previous !== $postedTemplate) {
            update_post_meta($issueId, '_mhn_template', $postedTemplate);
            update_post_meta($issueId, '_mhn_post_ids', implode(',', default_post_ids($postedTemplate)));
            update_post_meta($issueId, '_mhn_subject', '');
            update_post_meta($issueId, '_mhn_preheader', '');
            update_post_meta($issueId, '_mhn_subject_auto', '1');
            update_post_meta($issueId, '_mhn_note', default_note_html());
            update_post_meta($issueId, '_mhn_ps', '');
            update_post_meta($issueId, '_mhn_feature_show', '1');
            update_post_meta($issueId, '_mhn_feature_image_id', '0');
            update_post_meta($issueId, '_mhn_button_label', '');
            update_post_meta($issueId, '_mhn_button_url', '');
            update_post_meta($issueId, '_mhn_image_id', '0');
            update_post_meta($issueId, '_mhn_image_alt', '');
            compile_issue($issueId);
            fill_subject_defaults($issueId);
        }
    }

    $contentPosted = $step === 2 || isset($input['mhn_note']) || isset($input['mhn_posts']) || isset($input['mhn_ps']);
    if ($contentPosted && $step === 2) {
        if (isset($input['mhn_note'])) {
            update_post_meta($issueId, '_mhn_note', sanitize_rich_text((string) $input['mhn_note']));
        }
        if (isset($input['mhn_ps'])) {
            update_post_meta($issueId, '_mhn_ps', sanitize_rich_text((string) $input['mhn_ps']));
        }
        if (isset($input['mhn_posts']) && is_array($input['mhn_posts'])) {
            save_wizard_posts($issueId, $input['mhn_posts']);
        } elseif ($step === 2 && ! isset($input['mhn_posts'])) {
            $template = email_template((string) get_post_meta($issueId, '_mhn_template', true));
            if ($template !== null && $template['max_posts'] > 0) {
                update_post_meta($issueId, '_mhn_post_ids', '');
            }
        }
        if (isset($input['mhn_image_id'])) {
            $imageId = absint($input['mhn_image_id']);
            if ($imageId > 0 && wp_attachment_is_image($imageId)) {
                update_post_meta($issueId, '_mhn_image_id', (string) $imageId);
            } else {
                update_post_meta($issueId, '_mhn_image_id', '0');
            }
        }
        if (isset($input['mhn_button_label'])) {
            update_post_meta($issueId, '_mhn_button_label', sanitize_text_field((string) $input['mhn_button_label']));
        }
        if (isset($input['mhn_button_url'])) {
            update_post_meta($issueId, '_mhn_button_url', esc_url_raw((string) $input['mhn_button_url']));
        }
        if (isset($input['mhn_image_alt'])) {
            update_post_meta($issueId, '_mhn_image_alt', sanitize_text_field((string) $input['mhn_image_alt']));
        }
        if (isset($input['mhn_feature_show'])) {
            $show = sanitize_text_field((string) $input['mhn_feature_show']) === '1' ? '1' : '0';
            update_post_meta($issueId, '_mhn_feature_show', $show);
        }
        if (isset($input['mhn_feature_image_id'])) {
            $featureId = absint($input['mhn_feature_image_id']);
            $featureId = $featureId > 0 && wp_attachment_is_image($featureId) ? $featureId : 0;
            update_post_meta($issueId, '_mhn_feature_image_id', (string) $featureId);
        }
        compile_issue($issueId);
        fill_subject_defaults($issueId);
    }

    if ($step >= 3 && isset($input['mhn_subject'])) {
        $subject = sanitize_text_field((string) $input['mhn_subject']);
        $preheader = sanitize_text_field((string) ($input['mhn_preheader'] ?? ''));
        update_post_meta($issueId, '_mhn_subject', $subject);
        update_post_meta($issueId, '_mhn_preheader', mb_substr($preheader, 0, 140));
        update_post_meta($issueId, '_mhn_subject_auto', '0');
        if ($subject !== '') {
            wp_update_post([
                'ID' => $issueId,
                'post_title' => $subject,
            ]);
        }
    }
}

/**
 * @param  array<mixed>  $rawIds
 */
/**
 * @param  array<string, mixed>  $input
 */
function save_blog_post_choice(int $issueId, array $input): void
{
    if (! array_key_exists('mhn_source_post', $input)) {
        return;
    }
    if (issue_layout_id($issueId) !== 'post') {
        return;
    }

    $id = absint($input['mhn_source_post']);
    $post = $id > 0 ? get_post($id) : null;
    $valid = $post instanceof \WP_Post
        && $post->post_status === 'publish'
        && in_array($post->post_type, blog_letter_post_types(), true);
    update_post_meta($issueId, '_mhn_source_post', $valid ? (string) $id : '0');
    if ($valid && (string) get_post_meta($issueId, '_mhn_subject_auto', true) !== '0') {
        fill_subject_defaults($issueId);
    }
}

function save_wizard_posts(int $issueId, array $rawIds): void
{
    $template = email_template((string) get_post_meta($issueId, '_mhn_template', true));
    $max = $template['max_posts'] ?? 0;
    $ids = [];
    foreach ($rawIds as $raw) {
        $id = absint($raw);
        $post = get_post($id);
        if (! $post instanceof \WP_Post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
            continue;
        }
        $ids[] = $id;
    }
    $ids = array_values(array_unique($ids));
    if ($max > 0) {
        $ids = array_slice($ids, 0, $max);
    }
    update_post_meta($issueId, '_mhn_post_ids', implode(',', $ids));
}

function wizard_blocks_next(int $issueId, int $step): string
{
    $template = email_template((string) get_post_meta($issueId, '_mhn_template', true));
    if ($step === 1 && $template === null) {
        return 'template';
    }
    if ($step === 1 && issue_layout_id($issueId) === 'post' && (int) get_post_meta($issueId, '_mhn_source_post', true) < 1) {
        return 'posts';
    }
    if ($step === 2 && issue_layout_id($issueId) === 'welcome') {
        return '';
    }
    if ($step === 2 && issue_layout_id($issueId) === 'post') {
        return '';
    }
    if ($step === 2 && $template !== null) {
        $count = count(published_issue_posts($issueId));
        if ($template['max_posts'] > 0 && ($count < $template['min_posts'] || $count > $template['max_posts'])) {
            return $template['max_posts'] === 1 ? 'posts' : 'range';
        }
        if ($template['max_posts'] === 0 && trim(wp_strip_all_tags((string) get_post_meta($issueId, '_mhn_note', true))) === '') {
            return 'message';
        }
    }
    if ($step === 3 && trim((string) get_post_meta($issueId, '_mhn_subject', true)) === '') {
        return 'subject';
    }

    return '';
}

function wizard_issue_id(int $issueId): int
{
    if ($issueId < 1) {
        return 0;
    }
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return 0;
    }

    return $issueId;
}

function wizard_step_number(int $step): int
{
    return max(1, min(5, $step));
}

function page_wizard(): void
{
    guard_admin();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        check_admin_referer('mhn_wizard', 'mhn_wizard_nonce');
        $result = wizard_save(wp_unslash($_POST));
        wp_safe_redirect(wizard_redirect($result));
        exit;
    }

    render_wizard_page();
}

/**
 * @param  array{id: int, step: int, error: string, tested: string, queued: string, scheduled: string, find: string}  $result
 */
function wizard_redirect(array $result): string
{
    $args = [
        'page' => 'mhn-wizard',
        'step' => $result['step'],
    ];
    if ($result['id'] > 0) {
        $args['issue'] = $result['id'];
    }
    if ($result['error'] !== '') {
        $args['mhn_error'] = $result['error'];
    }
    if ($result['tested'] !== '') {
        $args['tested'] = $result['tested'];
    }
    if ($result['queued'] !== '') {
        $args['queued'] = $result['queued'];
    }
    if ($result['scheduled'] !== '') {
        $args['scheduled'] = $result['scheduled'];
    }
    if ($result['find'] !== '' && $result['step'] === 2) {
        $args['find'] = $result['find'];
    }

    return add_query_arg($args, admin_url('admin.php'));
}

function ajax_wizard_autosave(): void
{
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }
    check_ajax_referer('mhn_wizard', 'mhn_wizard_nonce');
    $input = wp_unslash($_POST);
    $input['mhn_action'] = 'stay';
    wp_send_json_success(wizard_save($input));
}

function handle_wizard_preview(): void
{
    guard_admin();
    $id = isset($_GET['issue']) ? absint(wp_unslash($_GET['issue'])) : 0;
    check_admin_referer('mhn_preview_'.$id);
    $post = get_post($id);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        wp_die(esc_html__('That issue could not be found.', 'matthummel-newsletter'), 404);
    }

    $message = issue_message($id, [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'status' => 'preview',
    ], true);
    nocache_headers();
    header('Content-Type: text/html; charset='.get_bloginfo('charset'));
    echo $message['html'];
    exit;
}

function render_wizard_page(): void
{
    $issueId = wizard_issue_id(isset($_GET['issue']) ? absint(wp_unslash($_GET['issue'])) : 0);
    $step = isset($_GET['step']) ? wizard_step_number(absint(wp_unslash($_GET['step']))) : 0;
    if ($step < 1) {
        $saved = $issueId > 0 ? (int) get_post_meta($issueId, '_mhn_wizard_step', true) : 1;
        $step = wizard_step_number($saved > 0 ? $saved : ($issueId > 0 ? 2 : 1));
    }

    $templateSlug = $issueId > 0 ? (string) get_post_meta($issueId, '_mhn_template', true) : 'blog-update';
    if (email_template($templateSlug) === null) {
        $templateSlug = 'blog-update';
    }
    $layoutId = $issueId > 0 ? issue_layout_id($issueId) : dashboard_layout_id();

    echo '<div class="wrap mhn-admin mhn-wizard">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<p class="mhn-wizard-back"><a href="'.esc_url(admin_url('admin.php?page=mhn-newsletter')).'">'.esc_html__('Get updates', 'matthummel-newsletter').'</a></p>';
    echo '<h1>'.esc_html__('Create newsletter', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Five steps. The draft saves as you go, so you can leave and come back.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    if ($issueId > 0) {
        render_sent_lock_notice($issueId);
    }
    render_wizard_notices($issueId);
    render_wizard_progress($issueId, $step);
    echo '<form id="mhn-wizard" method="post" action="'.esc_url(wizard_url()).'">';
    wp_nonce_field('mhn_wizard', 'mhn_wizard_nonce');
    echo '<input type="hidden" name="mhn_issue" value="'.esc_attr((string) $issueId).'">';
    echo '<input type="hidden" name="mhn_step" value="'.esc_attr((string) $step).'">';
    echo '<input type="hidden" name="mhn_editor" value="'.esc_attr(issue_editor_mode($issueId)).'">';
    if ($step !== 1) {
        echo '<input type="hidden" name="mhn_template" value="'.esc_attr($templateSlug).'">';
        echo '<input type="hidden" name="mhn_layout" value="'.esc_attr($layoutId).'">';
    }
    if ($step > 2 && $layoutId === 'post') {
        echo '<input type="hidden" name="mhn_source_post" value="'.esc_attr((string) (int) get_post_meta($issueId, '_mhn_source_post', true)).'">';
    }

    echo '<div class="mhn-wizard-split">';
    echo '<div class="mhn-wizard-main">';
    render_letter_style_compact(issue_letter_look($issueId));
    render_editor_switch(issue_editor_mode($issueId));
    echo '<div class="mhn-card mhn-wizard-panel">';
    match ($step) {
        1 => render_step_template($templateSlug, $layoutId),
        2 => render_step_content($issueId, $templateSlug),
        3 => render_step_subject($issueId),
        4 => render_step_preview($issueId),
        default => render_step_send($issueId),
    };
    echo '</div>';

    render_wizard_nav($issueId, $step);
    echo '</div>';
    render_email_emulator(emulator_view($issueId, $layoutId), true, false);
    echo '</div></form></div>';
}

function render_wizard_notices(int $issueId): void
{
    $error = isset($_GET['mhn_error']) ? sanitize_key(wp_unslash($_GET['mhn_error'])) : '';
    $message = wizard_error_message($error);
    if ($message !== '') {
        echo '<div class="notice notice-error"><p>'.esc_html($message).'</p></div>';
    }
    if (isset($_GET['tested'])) {
        $me = wp_get_current_user();
        $text = sanitize_key(wp_unslash($_GET['tested'])) === '1'
            ? sprintf(
                /* translators: %s: current user email */
                __('Test sent to %s.', 'matthummel-newsletter'),
                (string) $me->user_email
            )
            : __('The test could not be sent.', 'matthummel-newsletter');
        $class = sanitize_key(wp_unslash($_GET['tested'])) === '1' ? 'notice-success' : 'notice-error';
        echo '<div class="notice '.esc_attr($class).'"><p>'.esc_html($text).'</p></div>';
    }
    if (isset($_GET['queued']) && sanitize_key(wp_unslash($_GET['queued'])) === '1') {
        echo '<div class="notice notice-success"><p>'.esc_html__('Sending has started. Mail goes out in small batches.', 'matthummel-newsletter').'</p></div>';
    }
    if (isset($_GET['scheduled']) && sanitize_key(wp_unslash($_GET['scheduled'])) === '1') {
        echo '<div class="notice notice-success"><p>'.esc_html__('Scheduled. Nothing goes out until that time.', 'matthummel-newsletter').'</p></div>';
    }
    if ($issueId > 0 && editor_diverged($issueId)) {
        echo '<div class="notice notice-warning"><p>'.esc_html__('This draft was also edited in the block editor. The preview sends that version until you save this wizard again. Saving here rebuilds the email from these fields.', 'matthummel-newsletter').'</p></div>';
    }
}

function wizard_error_message(string $code): string
{
    return match ($code) {
        'template' => __('Choose a template first.', 'matthummel-newsletter'),
        'posts' => __('Pick one post to continue.', 'matthummel-newsletter'),
        'range' => __('A digest needs 2 to 6 posts.', 'matthummel-newsletter'),
        'message' => __('Write the message before you continue.', 'matthummel-newsletter'),
        'subject' => __('Add a subject line.', 'matthummel-newsletter'),
        'a11y' => __('Fix the email issues on this step before sending. Images need alt text, and the subject has to be filled in.', 'matthummel-newsletter'),
        'confirm' => __('Check the box before this goes out.', 'matthummel-newsletter'),
        'schedule' => __('Choose a date and time, then check the schedule box.', 'matthummel-newsletter'),
        'empty' => __('Nobody is subscribed yet, so this would not deliver any mail.', 'matthummel-newsletter'),
        'locked' => __('This issue is already sent or still sending.', 'matthummel-newsletter'),
        'sent_post' => __('This post was already sent. The issue is still here.', 'matthummel-newsletter'),
        'save' => __('The draft could not be saved.', 'matthummel-newsletter'),
        default => '',
    };
}

function render_wizard_progress(int $issueId, int $step): void
{
    $labels = [
        1 => __('Template', 'matthummel-newsletter'),
        2 => __('Content', 'matthummel-newsletter'),
        3 => __('Subject', 'matthummel-newsletter'),
        4 => __('Preview', 'matthummel-newsletter'),
        5 => __('Send', 'matthummel-newsletter'),
    ];
    $reached = $issueId > 0 ? max($step, (int) get_post_meta($issueId, '_mhn_wizard_step', true)) : 1;

    echo '<p class="mhn-wizard-kicker">'.esc_html(sprintf(
        /* translators: 1: current step number, 2: step name */
        __('Step %1$d of 5: %2$s', 'matthummel-newsletter'),
        $step,
        $labels[$step]
    )).'</p>';
    echo '<ol class="mhn-wizard-steps">';
    foreach ($labels as $number => $label) {
        $class = $number === $step ? 'is-current' : ($number < $reached ? 'is-done' : '');
        echo '<li class="'.esc_attr($class).'"'.($number === $step ? ' aria-current="step"' : '').'>';
        $body = '<span class="mhn-step-label">'.esc_html((string) $number.'. '.$label).'</span>';
        if ($number === $step) {
            $body .= '<span class="mhn-step-state">'.esc_html__('Current', 'matthummel-newsletter').'</span>';
        } elseif ($number < $reached) {
            $body .= '<span class="mhn-step-state">'.esc_html__('Done', 'matthummel-newsletter').'</span>';
        } else {
            $body .= '<span class="screen-reader-text">'.esc_html__('Not started', 'matthummel-newsletter').'</span>';
        }
        if ($issueId > 0 && $number !== $step && $number <= $reached) {
            echo '<a href="'.esc_url(wizard_url($issueId, $number)).'">'.$body.'</a>';
        } else {
            echo $body;
        }
        echo '</li>';
    }
    echo '</ol>';
}

function render_editor_switch(string $mode): void
{
    $mode = normalize_editor_mode($mode);
    echo '<div class="mhn-editor-switch" role="group" aria-label="'.esc_attr__('Editor', 'matthummel-newsletter').'">';
    echo '<span>'.esc_html__('Editor', 'matthummel-newsletter').'</span>';
    echo '<button type="button" data-mhn-editor="simple" aria-pressed="'.($mode === 'simple' ? 'true' : 'false').'">'.esc_html__('Simple', 'matthummel-newsletter').'</button>';
    echo '<button type="button" data-mhn-editor="advanced" aria-pressed="'.($mode === 'advanced' ? 'true' : 'false').'">'.esc_html__('Advanced', 'matthummel-newsletter').'</button>';
    echo '</div>';
}

function render_advanced_editor(int $issueId): void
{
    $layoutId = issue_layout_id($issueId);
    $saved = issue_blocks($issueId);
    $defaults = advanced_block_defaults($layoutId);
    echo '<h2>'.esc_html__('Advanced', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-advanced-note">'.esc_html__('Each block is one field. Leave intro or sign-off blank to keep the reusable line. The featured image follows the layout: Feature puts it on top, and Plain leaves it out.', 'matthummel-newsletter').'</p>';
    echo '<ol class="mhn-blocks">';
    render_advanced_field('mhn-block-intro', 'mhn_block_intro', __('Intro', 'matthummel-newsletter'), $saved['intro'], $defaults['intro'], false);
    render_advanced_field('mhn-block-heading', 'mhn_block_heading', __('Heading', 'matthummel-newsletter'), $saved['heading'], $defaults['heading'], false);
    render_advanced_field('mhn-block-body', 'mhn_block_body', __('Body', 'matthummel-newsletter'), $saved['body'], $defaults['body'], true);
    render_advanced_field('mhn-block-button-label', 'mhn_block_button_label', __('Button label', 'matthummel-newsletter'), $saved['button_label'], '', false);
    render_advanced_field('mhn-block-button-url', 'mhn_block_button_url', __('Button URL', 'matthummel-newsletter'), $saved['button_url'], 'https://', false);
    render_advanced_field('mhn-block-signoff', 'mhn_block_signoff', __('Sign-off', 'matthummel-newsletter'), $saved['signoff'], $defaults['signoff'], false);
    echo '</ol>';
}

function render_advanced_field(string $id, string $name, string $label, string $value, string $placeholder, bool $multiline): void
{
    echo '<li><label for="'.esc_attr($id).'">'.esc_html($label).'</label>';
    if ($multiline) {
        echo '<textarea class="large-text" rows="6" id="'.esc_attr($id).'" name="'.esc_attr($name).'" placeholder="'.esc_attr($placeholder).'">'.esc_textarea($value).'</textarea>';
    } else {
        $type = $name === 'mhn_block_button_url' ? 'url' : 'text';
        echo '<input class="large-text" type="'.esc_attr($type).'" id="'.esc_attr($id).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'" placeholder="'.esc_attr($placeholder).'">';
    }
    echo '</li>';
}

function render_step_template(string $current, string $layout): void
{
    render_layout_picker($layout);
    $issueId = isset($_GET['issue']) ? absint(wp_unslash($_GET['issue'])) : 0;
    render_blog_post_picker($issueId, $layout);
    echo '<h2>'.esc_html__('Choose what to include', 'matthummel-newsletter').'</h2>';
    echo '<div class="mhn-templates">';
    foreach (email_templates() as $slug => $template) {
        $id = 'mhn-template-'.$slug;
        echo '<label class="mhn-template" for="'.esc_attr($id).'">';
        echo '<input type="radio" name="mhn_template" id="'.esc_attr($id).'" value="'.esc_attr($slug).'" '.checked($current, $slug, false).'>';
        echo '<strong>'.esc_html($template['label']).'</strong>';
        echo '<span>'.esc_html($template['summary']).'</span>';
        echo '</label>';
    }
    echo '</div>';
}

function render_layout_picker(string $current): void
{
    echo '<h2>'.esc_html__('Choose a layout', 'matthummel-newsletter').'</h2>';
    echo '<p>'.esc_html__('One layout for this letter. Standard is the usual one. Welcome does not send on its own.', 'matthummel-newsletter').'</p>';
    echo '<div class="mhn-layouts" role="radiogroup" aria-label="'.esc_attr__('Layouts', 'matthummel-newsletter').'">';
    foreach (layouts() as $id => $layout) {
        $inputId = 'mhn-layout-'.$id;
        echo '<label class="mhn-layout" for="'.esc_attr($inputId).'">';
        echo '<input type="radio" name="mhn_layout" id="'.esc_attr($inputId).'" value="'.esc_attr($id).'" '.checked($current, $id, false).'>';
        echo '<strong>'.esc_html($layout['label']).'</strong>';
        echo '<span>'.esc_html($layout['summary']).'</span>';
        echo '<span class="mhn-layout-preview">';
        echo '<iframe title="'.esc_attr(sprintf(
            /* translators: %s: layout name */
            __('%s preview', 'matthummel-newsletter'),
            $layout['label']
        )).'" sandbox="" tabindex="-1" src="'.esc_url(layout_preview_admin_url($id)).'"></iframe>';
        echo '</span></label>';
    }
    echo '</div>';
}

function render_step_content(int $issueId, string $slug): void
{
    $advanced = issue_editor_mode($issueId) === 'advanced';
    echo '<div data-mhn-simple'.($advanced ? ' hidden' : '').'>';
    render_simple_content($issueId, $slug);
    echo '</div>';
    echo '<div data-mhn-advanced'.($advanced ? '' : ' hidden').'>';
    render_advanced_editor($issueId);
    echo '</div>';
}

function render_simple_content(int $issueId, string $slug): void
{
    if (issue_layout_id($issueId) === 'welcome') {
        render_welcome_step();

        return;
    }
    if (issue_layout_id($issueId) === 'post') {
        render_blog_post_step($issueId);

        return;
    }

    $template = email_template($slug);
    if ($template === null) {
        echo '<p>'.esc_html__('Choose a template first.', 'matthummel-newsletter').'</p>';

        return;
    }

    echo '<h2>'.esc_html($template['label']).'</h2>';
    echo '<p>'.esc_html__('Every template has a note from you. Bold, italic, links, and lists are fine.', 'matthummel-newsletter').'</p>';

    if ($template['has_image']) {
        render_image_picker($issueId);
    }

    echo '<h3>'.esc_html($template['max_posts'] > 0
        ? __('Your note', 'matthummel-newsletter')
        : __('Message', 'matthummel-newsletter')).'</h3>';
    render_merge_hint();
    render_rich_field('mhn_note', 'mhn_note', (string) get_post_meta($issueId, '_mhn_note', true));

    if ($template['max_posts'] > 0) {
        render_post_picker($issueId, $template);
        render_featured_controls($issueId, $template['max_posts'] === 1);
    }

    if ($template['has_button']) {
        $label = (string) get_post_meta($issueId, '_mhn_button_label', true);
        $url = (string) get_post_meta($issueId, '_mhn_button_url', true);
        echo '<h3>'.esc_html__('Button (optional)', 'matthummel-newsletter').'</h3>';
        echo '<p><label for="mhn-button-label">'.esc_html__('Label', 'matthummel-newsletter').'</label><br>';
        echo '<input class="regular-text" id="mhn-button-label" name="mhn_button_label" type="text" value="'.esc_attr($label).'"></p>';
        echo '<p><label for="mhn-button-url">'.esc_html__('Link', 'matthummel-newsletter').'</label><br>';
        echo '<input class="regular-text" id="mhn-button-url" name="mhn_button_url" type="url" value="'.esc_attr($url).'" placeholder="https://"></p>';
    }

    if ($template['has_ps']) {
        echo '<h3>'.esc_html__('P.S. (optional)', 'matthummel-newsletter').'</h3>';
        render_rich_field('mhn_ps', 'mhn_ps', (string) get_post_meta($issueId, '_mhn_ps', true));
    }
}

/**
 * @param  array{label: string, summary: string, min_posts: int, max_posts: int, has_image: bool, has_button: bool, has_ps: bool}  $template
 */
function render_post_picker(int $issueId, array $template): void
{
    $selected = issue_post_ids($issueId);
    $find = isset($_GET['find']) ? sanitize_text_field(wp_unslash($_GET['find'])) : '';
    $posts = wizard_post_choices($selected, $find);
    $multiple = $template['max_posts'] > 1;
    $input = $multiple ? 'checkbox' : 'radio';

    echo '<h3>'.esc_html($multiple
        ? __('Posts', 'matthummel-newsletter')
        : __('Post', 'matthummel-newsletter')).'</h3>';
    echo '<p class="description">'.esc_html($multiple
        ? __('Pick 2 to 6. The latest posts are selected until you change them.', 'matthummel-newsletter')
        : __('The latest post is selected until you pick another.', 'matthummel-newsletter')).'</p>';
    echo '<p class="mhn-find"><label class="screen-reader-text" for="mhn-find">'.esc_html__('Search posts', 'matthummel-newsletter').'</label>';
    echo '<input id="mhn-find" type="search" name="mhn_find" value="'.esc_attr($find).'" placeholder="'.esc_attr__('Search posts', 'matthummel-newsletter').'"> ';
    echo '<button type="submit" class="button" name="mhn_action" value="stay">'.esc_html__('Search', 'matthummel-newsletter').'</button></p>';

    if ($posts === []) {
        echo '<p>'.esc_html__('No posts match that search.', 'matthummel-newsletter').'</p>';

        return;
    }

    echo '<div class="mhn-posts">';
    foreach ($posts as $post) {
        $id = 'mhn-post-'.$post->ID;
        echo '<label for="'.esc_attr($id).'">';
        echo '<input type="'.esc_attr($input).'" name="mhn_posts[]" id="'.esc_attr($id).'" value="'.esc_attr((string) $post->ID).'" '.checked(in_array($post->ID, $selected, true), true, false).'>';
        echo ' '.esc_html(html_entity_decode(get_the_title($post), ENT_QUOTES));
        echo ' <span class="description">'.esc_html(get_the_date('', $post)).'</span>';
        echo '</label>';
    }
    echo '</div>';
}

/**
 * @param  list<int>  $selected
 * @return list<\WP_Post>
 */
function wizard_post_choices(array $selected, string $find): array
{
    $query = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ];
    if ($find !== '') {
        $query['s'] = $find;
    }
    $posts = get_posts($query);
    $choices = [];
    $seen = [];
    foreach (is_array($posts) ? $posts : [] as $post) {
        if ($post instanceof \WP_Post) {
            $choices[] = $post;
            $seen[$post->ID] = true;
        }
    }
    foreach ($selected as $id) {
        if (isset($seen[$id])) {
            continue;
        }
        $post = get_post($id);
        if ($post instanceof \WP_Post && $post->post_status === 'publish') {
            $choices[] = $post;
        }
    }

    return $choices;
}

function render_blog_post_step(int $issueId): void
{
    echo '<h2>'.esc_html__('Blog post', 'matthummel-newsletter').'</h2>';
    echo '<p>'.esc_html__('Pick a published post. The image, excerpt, headings, and categories come from that post. A note here replaces the excerpt. The title stays the post title until you change the subject.', 'matthummel-newsletter').'</p>';
    render_blog_sections($issueId);
    render_blog_post_picker($issueId, 'post');
    echo '<h3>'.esc_html__('Your note', 'matthummel-newsletter').'</h3>';
    render_merge_hint();
    render_rich_field('mhn_note', 'mhn_note', (string) get_post_meta($issueId, '_mhn_note', true));
}

function render_blog_sections(int $issueId): void
{
    $sections = issue_sections($issueId);
    $labels = [
        'image' => __('Image', 'matthummel-newsletter'),
        'excerpt' => __('Excerpt', 'matthummel-newsletter'),
        'headings' => __('Headings', 'matthummel-newsletter'),
        'categories' => __('Categories', 'matthummel-newsletter'),
        'button' => __('Button', 'matthummel-newsletter'),
    ];

    echo '<fieldset class="mhn-sections"><legend>'.esc_html__('Sections', 'matthummel-newsletter').'</legend>';
    echo '<input type="hidden" name="mhn_sections_present" value="1">';
    foreach ($labels as $key => $label) {
        $id = 'mhn-section-'.$key;
        echo '<p class="mhn-check"><label for="'.esc_attr($id).'"><input type="checkbox" id="'.esc_attr($id).'" name="mhn_sections[]" value="'.esc_attr($key).'" '.checked(! empty($sections[$key]), true, false).'> '.esc_html($label).'</label></p>';
    }
    echo '<p class="description">'.esc_html__('Uncheck a section to leave it out of this letter. Other layouts ignore these.', 'matthummel-newsletter').'</p>';
    echo '</fieldset>';
}

function render_blog_post_picker(int $issueId, string $layoutId): void
{
    if (function_exists('current_user_can') && ! current_user_can('manage_options')) {
        return;
    }

    $selected = $issueId > 0 ? (int) get_post_meta($issueId, '_mhn_source_post', true) : 0;
    $open = $layoutId === 'post';
    echo '<div class="mhn-post-picker" data-mhn-post-picker'.($open ? '' : ' hidden').'>';
    echo '<label for="mhn-source-post">'.esc_html__('Post', 'matthummel-newsletter').'</label>';
    echo '<select id="mhn-source-post" name="mhn_source_post">';
    echo '<option value="">'.esc_html__('Choose a post', 'matthummel-newsletter').'</option>';
    foreach (blog_post_choices($selected) as $choice) {
        $post = $choice['post'];
        echo '<option value="'.esc_attr((string) $post->ID).'"'.selected($selected, $post->ID, false).'>'.esc_html($choice['label']).'</option>';
    }
    echo '</select>';
    echo '<p class="description">'.esc_html__('Published posts and projects. Drafts stay off this list.', 'matthummel-newsletter').'</p>';
    echo '</div>';
}

/**
 * @return list<array{post: \WP_Post, label: string}>
 */
function blog_post_choices(int $selectedId): array
{
    if (! function_exists('get_posts')) {
        return [];
    }

    $posts = get_posts([
        'post_type' => blog_letter_post_types(),
        'post_status' => 'publish',
        'posts_per_page' => 40,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);
    $choices = [];
    $seen = [];
    foreach (is_array($posts) ? $posts : [] as $post) {
        if ($post instanceof \WP_Post) {
            $choices[] = $post;
            $seen[$post->ID] = true;
        }
    }
    if ($selectedId > 0 && ! isset($seen[$selectedId])) {
        $selected = get_post($selectedId);
        if ($selected instanceof \WP_Post && $selected->post_status === 'publish' && in_array($selected->post_type, blog_letter_post_types(), true)) {
            array_unshift($choices, $selected);
        }
    }

    $rules = settings()['rule_categories'];
    $ready = [];
    foreach ($choices as $post) {
        if (! letter_in_rule_categories(post_rule_tokens($post), $rules)) {
            continue;
        }
        $title = function_exists('get_the_title')
            ? html_entity_decode(get_the_title($post), ENT_QUOTES)
            : $post->post_title;
        $date = function_exists('get_the_date') ? (string) get_the_date('M j, Y', $post) : '';
        $label = $date !== '' ? $title.' · '.$date : $title;
        if ($post->post_type === 'project') {
            $label .= ' · '.__('Project', 'matthummel-newsletter');
        }
        $ready[] = ['post' => $post, 'label' => $label];
    }

    return $ready;
}

function render_welcome_step(): void
{
    $copy = layout_copy();
    echo '<h2>'.esc_html__('Welcome', 'matthummel-newsletter').'</h2>';
    echo '<p>'.esc_html__('This letter uses the welcome subject and body from Settings. Choosing it does not email anyone.', 'matthummel-newsletter').'</p>';
    echo '<p><strong>'.esc_html__('Subject', 'matthummel-newsletter').'</strong><br>'.esc_html($copy['welcome_subject']).'</p>';
    echo '<div class="mhn-welcome-copy">'.nl2br(esc_html($copy['welcome_body'])).'</div>';
    echo '<p><a href="'.esc_url(admin_url('admin.php?page=mhn-settings')).'">'.esc_html__('Edit reusable copy', 'matthummel-newsletter').'</a></p>';
}

function render_merge_hint(): void
{
    echo '<p class="description">'.esc_html__('Merge tags: {first_name}, {last_name}, {full_name}. A fallback goes after a bar, like {first_name|there}. The preview uses sample names.', 'matthummel-newsletter').'</p>';
}

function render_featured_controls(int $issueId, bool $canReplace): void
{
    $show = (string) get_post_meta($issueId, '_mhn_feature_show', true) !== '0';
    $posts = published_issue_posts($issueId);
    $post = $posts[0] ?? null;
    echo '<h3>'.esc_html__('Featured image', 'matthummel-newsletter').'</h3>';
    echo '<input type="hidden" name="mhn_feature_show" value="0">';
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_feature_show" value="1" '.checked($show, true, false).'> '.esc_html__('Show the featured image', 'matthummel-newsletter').'</label></p>';
    echo '<p class="description">'.esc_html__('The image sits near the top and links to the post. It stays within 600 pixels wide. A digest uses a smaller thumbnail. Alt text comes from the image, or the post title when the image has none. Leave this off and that send has no image.', 'matthummel-newsletter').'</p>';
    if ($post instanceof \WP_Post) {
        $thumb = (int) get_post_thumbnail_id($post);
        $src = $thumb > 0 ? wp_get_attachment_image_url($thumb, 'medium') : '';
        if (is_string($src) && $src !== '') {
            $alt = (string) get_post_meta($thumb, '_wp_attachment_image_alt', true);
            if ($alt === '') {
                $alt = html_entity_decode(get_the_title($post), ENT_QUOTES);
            }
            echo '<p><img src="'.esc_url($src).'" alt="'.esc_attr($alt).'" width="240" height="160" style="width:240px;height:auto;"></p>';
        }
    }
    if (! $canReplace) {
        return;
    }

    $imageId = (int) get_post_meta($issueId, '_mhn_feature_image_id', true);
    $src = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'medium') : '';
    echo '<p class="description">'.esc_html__('Replace it for this send only, or leave the field empty to use the post image.', 'matthummel-newsletter').'</p>';
    echo '<input type="hidden" name="mhn_feature_image_id" value="'.esc_attr((string) $imageId).'">';
    echo '<p id="mhn-feature-preview">';
    if (is_string($src) && $src !== '') {
        echo '<img src="'.esc_url($src).'" alt="" width="240" height="160" style="width:240px;height:auto;">';
    }
    echo '</p>';
    echo '<p><button type="button" class="button" id="mhn-pick-feature">'.esc_html__('Replace image', 'matthummel-newsletter').'</button> ';
    echo '<button type="button" class="button" id="mhn-clear-feature">'.esc_html__('Use the post image', 'matthummel-newsletter').'</button></p>';
}

function render_image_picker(int $issueId): void
{
    $imageId = (int) get_post_meta($issueId, '_mhn_image_id', true);
    $src = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'medium') : '';
    $alt = (string) get_post_meta($issueId, '_mhn_image_alt', true);
    echo '<h3>'.esc_html__('Image (optional)', 'matthummel-newsletter').'</h3>';
    echo '<input type="hidden" name="mhn_image_id" value="'.esc_attr((string) $imageId).'">';
    echo '<p><label for="mhn-image-alt"><strong>'.esc_html__('Alt text', 'matthummel-newsletter').'</strong></label><br>';
    echo '<input class="large-text" type="text" id="mhn-image-alt" name="mhn_image_alt" value="'.esc_attr($alt).'">';
    echo '<span class="description">'.esc_html__('Required before sending if you include an image. Describe it in a few words. Leave the image off if it is only decoration.', 'matthummel-newsletter').'</span></p>';
    echo '<p id="mhn-image-preview">';
    if (is_string($src) && $src !== '') {
        echo '<img src="'.esc_url($src).'" alt="" width="240" height="160" style="width:240px;height:auto;">';
    }
    echo '</p>';
    echo '<p><button type="button" class="button" id="mhn-pick-image">'.esc_html__('Choose image', 'matthummel-newsletter').'</button> ';
    echo '<button type="button" class="button" id="mhn-clear-image">'.esc_html__('Remove', 'matthummel-newsletter').'</button></p>';
}

function render_rich_field(string $id, string $name, string $value): void
{
    wp_editor($value, $id, [
        'textarea_name' => $name,
        'media_buttons' => false,
        'teeny' => false,
        'textarea_rows' => 8,
        'quicktags' => false,
        'tinymce' => [
            'toolbar1' => 'bold,italic,link,bullist,numlist,undo,redo',
            'toolbar2' => '',
            'wordpress_adv_hidden' => true,
        ],
    ]);
}

function render_step_subject(int $issueId): void
{
    $subject = (string) get_post_meta($issueId, '_mhn_subject', true);
    if ($subject === '') {
        $subject = suggest_subject($issueId);
    }
    $preheader = (string) get_post_meta($issueId, '_mhn_preheader', true);
    if ($preheader === '') {
        $preheader = suggest_preheader($issueId);
    }

    echo '<h2>'.esc_html__('Subject and preview text', 'matthummel-newsletter').'</h2>';
    echo '<p>'.esc_html__('These start from the note and the posts. Change them if you want a different inbox line.', 'matthummel-newsletter').'</p>';
    echo '<p><label for="mhn-subject"><strong>'.esc_html__('Subject', 'matthummel-newsletter').'</strong></label><br>';
    echo '<input class="large-text" type="text" id="mhn-subject" name="mhn_subject" maxlength="120" data-mhn-count="mhn-subject-count" value="'.esc_attr($subject).'"></p>';
    echo '<p class="description"><span id="mhn-subject-count">'.esc_html((string) mb_strlen($subject)).'</span> '.esc_html__('characters. Aim for about 50. Over 60, inboxes may cut the subject off.', 'matthummel-newsletter').'</p>';
    render_issue_audit($issueId);
    echo '<p><label for="mhn-preheader"><strong>'.esc_html__('Preview text', 'matthummel-newsletter').'</strong></label><br>';
    echo '<input class="large-text" type="text" id="mhn-preheader" name="mhn_preheader" maxlength="140" data-mhn-count="mhn-preheader-count" value="'.esc_attr($preheader).'" placeholder="'.esc_attr(layout_copy()['intro']).'"></p>';
    echo '<p class="description"><span id="mhn-preheader-count">'.esc_html((string) mb_strlen($preheader)).'</span> '.esc_html__('characters. Aim for about 90. This hidden line is the inbox preview. It starts as your reusable intro.', 'matthummel-newsletter').'</p>';
}

function render_step_preview(int $issueId): void
{
    $me = wp_get_current_user();

    echo '<h2>'.esc_html__('Preview', 'matthummel-newsletter').'</h2>';
    render_issue_audit($issueId);
    echo '<p>'.esc_html__('The email preview updates as you edit. Switch Desktop and Mobile there, then send a test to yourself. Preview links stay on this page.', 'matthummel-newsletter').'</p>';
    if ($issueId < 1) {
        echo '<p>'.esc_html__('Save the draft first.', 'matthummel-newsletter').'</p>';

        return;
    }

    echo '<p><button type="submit" class="button" name="mhn_action" value="test">'.esc_html(sprintf(
        /* translators: %s: current user email */
        __('Send a test to %s', 'matthummel-newsletter'),
        (string) $me->user_email
    )).'</button></p>';
}

function render_step_send(int $issueId): void
{
    $count = subscribed_recipient_count();
    $locked = $issueId > 0 && issue_is_locked($issueId);
    $status = $issueId > 0 ? issue_status($issueId) : 'draft';
    $local = $issueId > 0 ? (string) get_post_meta($issueId, '_mhn_scheduled_local', true) : '';
    $localValue = $local !== '' ? str_replace(' ', 'T', substr($local, 0, 16)) : '';
    $zone = wp_timezone_string();

    echo '<h2>'.esc_html__('Send or schedule', 'matthummel-newsletter').'</h2>';
    render_issue_audit($issueId);
    echo '<p class="mhn-recipient-count"><strong>'.esc_html((string) $count).'</strong> ';
    echo esc_html(_n('subscribed address will get this.', 'subscribed addresses will get this.', $count, 'matthummel-newsletter')).'</p>';
    echo '<p>'.esc_html__('Nothing is sent until you check a box below and choose Send now or Schedule.', 'matthummel-newsletter').'</p>';

    if ($locked) {
        echo '<div class="notice notice-info"><p>'.esc_html(sprintf(
            /* translators: %s: issue status label */
            __('This issue is %s. It will not send again from here.', 'matthummel-newsletter'),
            status_label($status)
        )).'</p></div>';

        return;
    }

    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_confirm_send" value="1"> '.esc_html(sprintf(
        /* translators: %d: recipient count */
        __('Send now to %d subscribed addresses.', 'matthummel-newsletter'),
        $count
    )).'</label></p>';
    echo '<p><button type="submit" class="button button-primary" name="mhn_action" value="send">'.esc_html__('Send now', 'matthummel-newsletter').'</button></p>';
    echo '<hr>';
    echo '<p><label for="mhn-schedule"><strong>'.esc_html__('Schedule', 'matthummel-newsletter').'</strong></label><br>';
    echo '<input type="datetime-local" id="mhn-schedule" name="mhn_schedule_local" value="'.esc_attr($localValue).'"> ';
    echo '<span class="description">'.esc_html(sprintf(
        /* translators: %s: site timezone */
        __('Site time (%s).', 'matthummel-newsletter'),
        $zone !== '' ? $zone : 'UTC'
    )).'</span></p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_confirm_schedule" value="1"> '.esc_html(sprintf(
        /* translators: %d: recipient count */
        __('Schedule this send to %d subscribed addresses.', 'matthummel-newsletter'),
        $count
    )).'</label></p>';
    echo '<p><button type="submit" class="button" name="mhn_action" value="schedule">'.esc_html__('Schedule', 'matthummel-newsletter').'</button></p>';
}

function render_wizard_nav(int $issueId, int $step): void
{
    echo '<p class="mhn-wizard-actions">';
    if ($step < 5) {
        echo '<button type="submit" class="button button-primary" name="mhn_action" value="next">'.esc_html__('Next', 'matthummel-newsletter').'</button> ';
    }
    if ($step > 1) {
        echo '<button type="submit" class="button" name="mhn_action" value="back">'.esc_html__('Back', 'matthummel-newsletter').'</button> ';
    }
    if ($issueId > 0) {
        $edit = get_edit_post_link($issueId, 'raw');
        if (is_string($edit) && $edit !== '') {
            echo '<a class="mhn-quiet-link" href="'.esc_url($edit).'">'.esc_html__('Open the block editor', 'matthummel-newsletter').'</a> ';
        }
    }
    echo '<span id="mhn-save-status" class="mhn-save-status" aria-live="polite"></span>';
    echo '</p>';
}
