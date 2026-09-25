<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function subscribers_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_subscribers';
}

function events_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_events';
}

/**
 * Saved settings merged over defaults. Auto-send and tracking stay off until turned on.
 *
 * @return array{
 *     from_name: string,
 *     from_email: string,
 *     reply_to: string,
 *     address: string,
 *     auto_draft: int,
 *     auto_send: int,
 *     track_opens: int,
 *     track_clicks: int,
 *     batch_size: int
 * }
 */
function settings(): array
{
    $saved = get_option('mhn_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $admin = (string) get_option('admin_email');
    $defaults = [
        'from_name' => (string) get_bloginfo('name'),
        'from_email' => $admin,
        'reply_to' => $admin,
        'address' => 'Gettysburg, PA',
        'auto_draft' => 1,
        'auto_send' => 0,
        'track_opens' => 0,
        'track_clicks' => 0,
        'batch_size' => 25,
    ];

    $merged = array_merge($defaults, $saved);

    return [
        'from_name' => (string) $merged['from_name'],
        'from_email' => (string) $merged['from_email'],
        'reply_to' => (string) $merged['reply_to'],
        'address' => (string) $merged['address'],
        'auto_draft' => (int) $merged['auto_draft'] === 1 ? 1 : 0,
        'auto_send' => (int) $merged['auto_send'] === 1 ? 1 : 0,
        'track_opens' => (int) $merged['track_opens'] === 1 ? 1 : 0,
        'track_clicks' => (int) $merged['track_clicks'] === 1 ? 1 : 0,
        'batch_size' => max(5, min(100, (int) $merged['batch_size'])),
    ];
}

/**
 * @param  array<string, mixed>  $input
 */
function update_settings(array $input): void
{
    $admin = (string) get_option('admin_email');
    $from = sanitize_email((string) ($input['from_email'] ?? ''));
    $reply = sanitize_email((string) ($input['reply_to'] ?? ''));
    $name = sanitize_text_field((string) ($input['from_name'] ?? ''));
    $address = sanitize_textarea_field((string) ($input['address'] ?? ''));

    update_option('mhn_settings', [
        'from_name' => $name !== '' ? $name : (string) get_bloginfo('name'),
        'from_email' => is_email($from) ? $from : $admin,
        'reply_to' => is_email($reply) ? $reply : $admin,
        'address' => $address !== '' ? $address : 'Gettysburg, PA',
        'auto_draft' => empty($input['auto_draft']) ? 0 : 1,
        'auto_send' => empty($input['auto_send']) ? 0 : 1,
        'track_opens' => empty($input['track_opens']) ? 0 : 1,
        'track_clicks' => empty($input['track_clicks']) ? 0 : 1,
        'batch_size' => max(5, min(100, absint($input['batch_size'] ?? 25))),
    ]);
}
