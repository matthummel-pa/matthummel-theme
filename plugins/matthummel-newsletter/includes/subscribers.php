<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, string>|null
 */
function find_by_email(string $email): ?array
{
    global $wpdb;

    $row = $wpdb->get_row($wpdb->prepare(
        'SELECT * FROM '.subscribers_table().' WHERE email = %s LIMIT 1',
        strtolower($email)
    ), ARRAY_A);

    return is_array($row) ? stringify_row($row) : null;
}

/**
 * @return array<string, string>|null
 */
function find(int $id): ?array
{
    global $wpdb;

    if ($id < 1) {
        return null;
    }

    $row = $wpdb->get_row($wpdb->prepare(
        'SELECT * FROM '.subscribers_table().' WHERE id = %d LIMIT 1',
        $id
    ), ARRAY_A);

    return is_array($row) ? stringify_row($row) : null;
}

/**
 * @param  array<string, string>  $row
 * @return array<string, string>
 */
function stringify_row(array $row): array
{
    $out = [];
    foreach ($row as $key => $value) {
        $out[(string) $key] = (string) $value;
    }

    return $out;
}

/**
 * @param  array<string, string>  $data
 */
function insert_subscriber(array $data): int
{
    global $wpdb;

    $now = current_time('mysql');
    $ok = $wpdb->insert(
        subscribers_table(),
        [
            'email' => strtolower($data['email'] ?? ''),
            'first_name' => $data['first_name'] ?? '',
            'status' => $data['status'] ?? 'pending',
            'opt_in' => $data['opt_in'] ?? 'double',
            'confirm_hash' => $data['confirm_hash'] ?? '',
            'source' => $data['source'] ?? '',
            'created_at' => $data['created_at'] ?? $now,
            'confirmed_at' => $data['confirmed_at'] ?? '',
            'unsubscribed_at' => $data['unsubscribed_at'] ?? '',
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    return $ok ? (int) $wpdb->insert_id : 0;
}

/**
 * @param  array<string, string>  $data
 */
function update_subscriber(int $id, array $data): void
{
    global $wpdb;

    if ($id < 1 || $data === []) {
        return;
    }

    $formats = [];
    foreach ($data as $value) {
        $formats[] = '%s';
    }

    $wpdb->update(subscribers_table(), $data, ['id' => $id], $formats, ['%d']);
}

function subscribed_recipient_count(): int
{
    $list = apply_filters('mhn_send_allowlist', null);
    if ($list === null) {
        return count_status('subscribed');
    }
    if (! is_array($list)) {
        return 0;
    }

    $count = 0;
    foreach ($list as $email) {
        $row = find_by_email((string) $email);
        if ($row !== null && ($row['status'] ?? '') === 'subscribed') {
            $count++;
        }
    }

    return $count;
}

function count_status(string $status): int
{
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM '.subscribers_table().' WHERE status = %s',
        $status
    ));
}

function count_opt_in(string $optIn): int
{
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM '.subscribers_table().' WHERE opt_in = %s',
        $optIn
    ));
}

function count_confirmed_between(string $start, string $end): int
{
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM '.subscribers_table().' WHERE status = %s AND confirmed_at >= %s AND confirmed_at < %s',
        'subscribed',
        $start,
        $end
    ));
}

function log_event(int $issueId, int $subscriberId, string $event, string $detail = ''): void
{
    global $wpdb;

    $wpdb->insert(
        events_table(),
        [
            'issue_id' => $issueId,
            'subscriber_id' => $subscriberId,
            'event' => substr(sanitize_key($event), 0, 20),
            'detail' => mb_substr(sanitize_text_field($detail), 0, 255),
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );
}

function has_event(int $issueId, int $subscriberId, string $event): bool
{
    global $wpdb;

    $id = $wpdb->get_var($wpdb->prepare(
        'SELECT id FROM '.events_table().' WHERE issue_id = %d AND subscriber_id = %d AND event = %s LIMIT 1',
        $issueId,
        $subscriberId,
        $event
    ));

    return (int) $id > 0;
}

/**
 * @param  array<string, string>  $subscriber
 */
function subscriber_token(array $subscriber, string $action): string
{
    $payload = $action.'|'.(int) $subscriber['id'].'|'.strtolower((string) $subscriber['email']);

    return hash_hmac('sha256', $payload, wp_salt('auth'));
}

/**
 * @param  array<string, string>  $subscriber
 */
function token_matches(array $subscriber, string $token): bool
{
    $expected = subscriber_token($subscriber, 'manage');

    return $token !== '' && hash_equals($expected, $token);
}

function store_confirm_token(int $id): string
{
    $raw = bin2hex(random_bytes(16));
    update_subscriber($id, [
        'confirm_hash' => hash_hmac('sha256', $raw, wp_salt('auth')),
        'status' => 'pending',
    ]);

    return $raw;
}

/**
 * @return array<string, string>|null
 */
function find_by_confirm_token(string $raw): ?array
{
    global $wpdb;

    if ($raw === '' || ! preg_match('/^[a-f0-9]{32}$/', $raw)) {
        return null;
    }

    $hash = hash_hmac('sha256', $raw, wp_salt('auth'));
    $row = $wpdb->get_row($wpdb->prepare(
        'SELECT * FROM '.subscribers_table().' WHERE confirm_hash = %s LIMIT 1',
        $hash
    ), ARRAY_A);

    return is_array($row) ? stringify_row($row) : null;
}

function confirm_subscriber(string $raw): string
{
    $row = find_by_confirm_token($raw);
    if (! $row) {
        return 'error';
    }
    if ($row['status'] === 'unsubscribed') {
        return 'error';
    }

    update_subscriber((int) $row['id'], [
        'status' => 'subscribed',
        'opt_in' => 'double',
        'confirm_hash' => '',
        'confirmed_at' => current_time('mysql'),
        'unsubscribed_at' => '',
    ]);
    log_event(0, (int) $row['id'], 'confirmed', '');

    return 'ok';
}

function unsubscribe(int $id): bool
{
    $row = find($id);
    if (! $row) {
        return false;
    }
    if ($row['status'] === 'unsubscribed') {
        return true;
    }

    update_subscriber($id, [
        'status' => 'unsubscribed',
        'confirm_hash' => '',
        'unsubscribed_at' => current_time('mysql'),
    ]);
    log_event(0, $id, 'unsubscribed', '');

    return true;
}

function delete_subscriber(int $id): void
{
    global $wpdb;

    if ($id < 1) {
        return;
    }

    $wpdb->delete(subscribers_table(), ['id' => $id], ['%d']);
}

/**
 * Next confirmed subscribers who do not yet have a terminal event for this issue.
 *
 * @return list<array<string, string>>
 */
function next_recipients(int $issueId, int $limit): array
{
    global $wpdb;

    $limit = max(1, min(100, $limit));
    $subscribers = subscribers_table();
    $events = events_table();
    $sql = "SELECT s.* FROM {$subscribers} s
        WHERE s.status = %s
        AND NOT EXISTS (
            SELECT 1 FROM {$events} e
            WHERE e.issue_id = %d
            AND e.subscriber_id = s.id
            AND e.event IN ('sent', 'failed', 'skipped')
        )
        ORDER BY s.id ASC
        LIMIT %d";

    $rows = $wpdb->get_results($wpdb->prepare($sql, 'subscribed', $issueId, $limit), ARRAY_A);
    $out = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (is_array($row)) {
            $out[] = stringify_row($row);
        }
    }

    return $out;
}

function count_issue_event(int $issueId, string $event): int
{
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM '.events_table().' WHERE issue_id = %d AND event = %s',
        $issueId,
        $event
    ));
}
