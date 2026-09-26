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

function archive_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_archive';
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
 *     batch_size: int,
 *     intro: string,
 *     signoff: string,
 *     welcome_subject: string,
 *     welcome_body: string
 * }
 */
function settings(): array
{
    $saved = get_option('mhn_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $admin = (string) get_option('admin_email');
    $copy = layout_copy_defaults();
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
        'intro' => $copy['intro'],
        'signoff' => $copy['signoff'],
        'welcome_subject' => $copy['welcome_subject'],
        'welcome_body' => $copy['welcome_body'],
    ];

    $merged = array_merge($defaults, $saved);
    $intro = trim((string) $merged['intro']);
    $signoff = trim((string) $merged['signoff']);
    $welcomeSubject = trim((string) $merged['welcome_subject']);
    $welcomeBody = trim((string) $merged['welcome_body']);

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
        'intro' => $intro !== '' ? $intro : $copy['intro'],
        'signoff' => $signoff !== '' ? $signoff : $copy['signoff'],
        'welcome_subject' => $welcomeSubject !== '' ? $welcomeSubject : $copy['welcome_subject'],
        'welcome_body' => $welcomeBody !== '' ? $welcomeBody : $copy['welcome_body'],
    ];
}

/**
 * Reusable letter copy. Empty saved values fall back to these.
 *
 * @return array{intro: string, signoff: string, welcome_subject: string, welcome_body: string}
 */
function layout_copy_defaults(): array
{
    return [
        'intro' => __('Here is what I have been building.', 'matthummel-newsletter'),
        'signoff' => __('Talk soon,', 'matthummel-newsletter'),
        'welcome_subject' => __('You are on the list', 'matthummel-newsletter'),
        'welcome_body' => __("Thanks for signing up.\n\nNew notes arrive by email. I keep the address on this site. I do not send it to a newsletter service.\n\nYou can unsubscribe any time.", 'matthummel-newsletter'),
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
    $current = settings();
    $defaults = layout_copy_defaults();

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
        'intro' => posted_copy($input, 'intro', $current['intro'], $defaults['intro'], false),
        'signoff' => posted_copy($input, 'signoff', $current['signoff'], $defaults['signoff'], false),
        'welcome_subject' => posted_copy($input, 'welcome_subject', $current['welcome_subject'], $defaults['welcome_subject'], false),
        'welcome_body' => posted_copy($input, 'welcome_body', $current['welcome_body'], $defaults['welcome_body'], true),
    ]);
}

/**
 * @param  array<string, mixed>  $input
 */
function posted_copy(array $input, string $key, string $current, string $fallback, bool $multiline): string
{
    if (! array_key_exists($key, $input)) {
        return $current !== '' ? $current : $fallback;
    }

    $value = $multiline
        ? sanitize_textarea_field((string) $input[$key])
        : sanitize_text_field((string) $input[$key]);
    $value = trim($value);
    $limit = $multiline ? 4000 : 200;
    if (mb_strlen($value) > $limit) {
        $value = mb_substr($value, 0, $limit);
    }

    return $value !== '' ? $value : $fallback;
}
