<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function send_test(int $issueId): bool
{
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return false;
    }

    $user = wp_get_current_user();
    $email = (string) $user->user_email;
    if (! is_email($email)) {
        return false;
    }

    $message = issue_message($issueId, [
        'id' => '0',
        'email' => $email,
        'first_name' => (string) $user->first_name,
        'status' => 'test',
    ], true);

    $ok = send_mail($email, '[Test] '.$message['subject'], $message['html'], $message['text']);
    log_event($issueId, 0, $ok ? 'test' : 'test_failed', $ok ? $email : mail_error());

    return $ok;
}

function start_campaign(int $issueId, bool $resetCounts = true): bool
{
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return false;
    }

    $status = issue_status($issueId);
    if (in_array($status, ['sending', 'sent'], true)) {
        return false;
    }

    set_issue_status($issueId, 'sending');
    if ($resetCounts) {
        update_post_meta($issueId, '_mhn_sent_count', '0');
        update_post_meta($issueId, '_mhn_fail_count', '0');
    }
    queue_batch($issueId, 0);

    return true;
}

function retry_failed(int $issueId): bool
{
    global $wpdb;

    $wpdb->query($wpdb->prepare(
        'DELETE FROM '.events_table().' WHERE issue_id = %d AND event = %s',
        $issueId,
        'failed'
    ));
    set_issue_status($issueId, 'draft');

    return start_campaign($issueId, false);
}

function schedule_issue(int $issueId, string $gmt): bool
{
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return false;
    }
    if ($gmt === '' || issue_status($issueId) === 'sending') {
        return false;
    }

    update_post_meta($issueId, '_mhn_scheduled_gmt', $gmt);
    set_issue_status($issueId, 'scheduled');

    return true;
}

function queue_batch(int $issueId, int $delay = 0): void
{
    $timestamp = time() + max(0, $delay);
    if (function_exists('as_schedule_single_action')) {
        $already = function_exists('as_next_scheduled_action')
            ? as_next_scheduled_action('mhn_send_batch', [$issueId], 'matthummel-newsletter')
            : false;
        if (! $already) {
            as_schedule_single_action($timestamp, 'mhn_send_batch', [$issueId], 'matthummel-newsletter');
        }

        return;
    }

    if (! wp_next_scheduled('mhn_send_batch', [$issueId])) {
        wp_schedule_single_event($timestamp, 'mhn_send_batch', [$issueId]);
    }
}

function send_batch(int|string|array $issueId = 0): void
{
    if (is_array($issueId)) {
        $issueId = $issueId['issue_id'] ?? ($issueId[0] ?? 0);
    }
    $issueId = (int) $issueId;
    if ($issueId < 1) {
        return;
    }

    $status = issue_status($issueId);
    if (! in_array($status, ['sending', 'scheduled'], true)) {
        return;
    }

    if (! claim_batch($issueId)) {
        return;
    }

    try {
        $rows = next_recipients($issueId, settings()['batch_size']);
        if ($rows === []) {
            finish_campaign($issueId);

            return;
        }

        foreach ($rows as $subscriber) {
            deliver_issue($issueId, $subscriber);
        }

        update_post_meta($issueId, '_mhn_sent_count', (string) count_issue_event($issueId, 'sent'));
        update_post_meta($issueId, '_mhn_fail_count', (string) count_issue_event($issueId, 'failed'));

        if (next_recipients($issueId, 1) === []) {
            finish_campaign($issueId);

            return;
        }

        $delay = (int) apply_filters('mhn_batch_delay', 30);
        queue_batch($issueId, max(1, $delay));
    } finally {
        release_batch($issueId);
    }
}

/**
 * @param  array<string, string>  $subscriber
 */
function deliver_issue(int $issueId, array $subscriber): bool
{
    $subscriberId = (int) $subscriber['id'];
    if (! recipient_allowed((string) $subscriber['email'])) {
        log_event($issueId, $subscriberId, 'skipped', 'allowlist');

        return false;
    }
    if (has_event($issueId, $subscriberId, 'sent')) {
        return true;
    }

    $message = issue_message($issueId, $subscriber, false);
    $ok = send_mail(
        (string) $subscriber['email'],
        $message['subject'],
        $message['html'],
        $message['text'],
        [],
        $subscriber
    );
    log_event($issueId, $subscriberId, $ok ? 'sent' : 'failed', $ok ? '' : mail_error());

    return $ok;
}

function finish_campaign(int $issueId): void
{
    $sent = count_issue_event($issueId, 'sent');
    $failed = count_issue_event($issueId, 'failed');
    update_post_meta($issueId, '_mhn_sent_count', (string) $sent);
    update_post_meta($issueId, '_mhn_fail_count', (string) $failed);
    update_post_meta($issueId, '_mhn_sent_at', current_time('mysql'));
    set_issue_status($issueId, $sent === 0 && $failed > 0 ? 'failed' : 'sent');
}

function claim_batch(int $issueId): bool
{
    $key = 'mhn_lock_'.$issueId;
    if (get_transient($key)) {
        return false;
    }
    set_transient($key, '1', 2 * MINUTE_IN_SECONDS);

    return true;
}

function release_batch(int $issueId): void
{
    delete_transient('mhn_lock_'.$issueId);
}

function cron_tick(): void
{
    $ids = get_posts([
        'post_type' => 'newsletter_issue',
        'post_status' => 'any',
        'posts_per_page' => 10,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_mhn_status',
        'meta_value' => 'scheduled',
    ]);

    $now = current_time('mysql', true);
    foreach (is_array($ids) ? $ids : [] as $id) {
        $when = (string) get_post_meta((int) $id, '_mhn_scheduled_gmt', true);
        if ($when !== '' && $when <= $now) {
            start_campaign((int) $id);
        }
    }
}
