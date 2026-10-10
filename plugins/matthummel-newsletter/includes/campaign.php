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
    if (in_array($status, ['sending', 'sent'], true) || issue_send_blocked($issueId) || issue_skips_sent_post($issueId)) {
        return false;
    }

    set_issue_status($issueId, 'sending');
    if ($resetCounts) {
        update_post_meta($issueId, '_mhn_sent_count', '0');
        update_post_meta($issueId, '_mhn_fail_count', '0');
    }
    update_post_meta($issueId, '_mhn_send_started_at', current_time('mysql'));
    update_post_meta($issueId, '_mhn_sender_id', (string) get_current_user_id());
    update_post_meta($issueId, '_mhn_recipient_count', (string) subscribed_recipient_count());
    update_post_meta($issueId, '_mhn_list_label', audience_label());
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
    if ($gmt === '' || issue_status($issueId) === 'sending' || issue_send_blocked($issueId) || issue_skips_sent_post($issueId)) {
        return false;
    }

    update_post_meta($issueId, '_mhn_scheduled_gmt', $gmt);
    set_issue_status($issueId, 'scheduled');

    return true;
}

function queue_batch(int $issueId, int $delay = 0): void
{
    if ($issueId < 1 || batch_is_pending($issueId)) {
        return;
    }

    $timestamp = time() + max(0, $delay);
    if (uses_action_scheduler()) {
        as_schedule_single_action($timestamp, 'mhn_send_batch', [$issueId], 'matthummel-newsletter');

        return;
    }

    wp_schedule_single_event($timestamp, 'mhn_send_batch', [$issueId]);
}

function uses_action_scheduler(): bool
{
    return function_exists('as_schedule_single_action') && function_exists('as_next_scheduled_action');
}

function batch_is_pending(int $issueId): bool
{
    if ($issueId < 1) {
        return false;
    }

    if (uses_action_scheduler()) {
        $next = as_next_scheduled_action('mhn_send_batch', [$issueId], 'matthummel-newsletter');

        return is_numeric($next) && (int) $next > 0;
    }

    $next = wp_next_scheduled('mhn_send_batch', [$issueId]);

    return is_numeric($next) && (int) $next > 0;
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
        queue_batch($issueId, 60);

        return;
    }

    try {
        $rows = next_recipients($issueId, settings()['batch_size']);
        if ($rows === []) {
            finish_campaign($issueId);

            return;
        }

        foreach ($rows as $subscriber) {
            try {
                deliver_issue($issueId, $subscriber);
            } catch (\Throwable $error) {
                log_event($issueId, (int) ($subscriber['id'] ?? 0), 'failed', $error->getMessage());
            }
        }

        update_post_meta($issueId, '_mhn_sent_count', (string) count_issue_event($issueId, 'sent'));
        update_post_meta($issueId, '_mhn_fail_count', (string) count_issue_event($issueId, 'failed'));

        if (next_recipients($issueId, 1) === []) {
            finish_campaign($issueId);
        }
    } finally {
        release_batch($issueId);
        resume_sending_batch($issueId);
    }
}

/**
 * Keep a half-finished send moving. A timeout or a thrown mail error used to
 * leave the issue on "sending" with no follow-up, so the rest of the list
 * never received it and Send stayed blocked.
 */
function resume_sending_batch(int $issueId): void
{
    if ($issueId < 1 || issue_status($issueId) !== 'sending') {
        return;
    }

    if (next_recipients($issueId, 1) === []) {
        finish_campaign($issueId);

        return;
    }

    $delay = (int) apply_filters('mhn_batch_delay', 30);
    queue_batch($issueId, max(1, $delay));
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
    store_sent_snapshot($issueId);
}

function batch_lock_key(int $issueId): string
{
    return 'mhn_lock_'.$issueId;
}

function claim_batch(int $issueId): bool
{
    $key = batch_lock_key($issueId);
    $now = time();
    if (add_option($key, (string) $now, '', false)) {
        return true;
    }

    $started = (int) get_option($key, 0);
    if ($started > 0 && ($now - $started) < 10 * MINUTE_IN_SECONDS) {
        return false;
    }

    delete_option($key);

    return add_option($key, (string) $now, '', false);
}

function batch_is_locked(int $issueId): bool
{
    $started = (int) get_option(batch_lock_key($issueId), 0);

    return $started > 0 && (time() - $started) < 10 * MINUTE_IN_SECONDS;
}

function release_batch(int $issueId): void
{
    delete_option(batch_lock_key($issueId));
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

    resume_orphaned_sends();
}

/**
 * A send left on "sending" with nobody working it gets another batch.
 */
function resume_orphaned_sends(): void
{
    $ids = get_posts([
        'post_type' => 'newsletter_issue',
        'post_status' => 'any',
        'posts_per_page' => 5,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_mhn_status',
        'meta_value' => 'sending',
    ]);

    foreach (is_array($ids) ? $ids : [] as $id) {
        $id = (int) $id;
        if ($id < 1 || batch_is_locked($id) || batch_is_pending($id)) {
            continue;
        }

        resume_sending_batch($id);
    }
}
