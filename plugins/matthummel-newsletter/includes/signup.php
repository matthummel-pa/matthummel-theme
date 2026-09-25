<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function handle_signup(): void
{
    $nonce = isset($_POST['mhn_signup_nonce']) ? wp_unslash($_POST['mhn_signup_nonce']) : '';
    if (! is_string($nonce) || ! wp_verify_nonce($nonce, 'mhn_signup')) {
        redirect_signup('error');
    }
    if (! empty($_POST['mhn_hp'])) {
        redirect_signup('confirm');
    }
    if (rate_limited()) {
        redirect_signup('wait');
    }

    bump_rate();
    $email = isset($_POST['mhn_email']) ? sanitize_email(wp_unslash($_POST['mhn_email'])) : '';
    $first = isset($_POST['mhn_fname']) ? sanitize_text_field(wp_unslash($_POST['mhn_fname'])) : '';
    $source = isset($_POST['mhn_source']) ? sanitize_key(wp_unslash($_POST['mhn_source'])) : 'page';
    if (! in_array($source, ['page', 'footer'], true)) {
        $source = 'page';
    }

    redirect_signup(subscribe_address($email, $first, $source));
}

function subscribe_address(string $email, string $first, string $source): string
{
    $email = strtolower(sanitize_email($email));
    $first = mb_substr(sanitize_text_field($first), 0, 80);
    if (! is_email($email)) {
        return 'error';
    }

    $existing = find_by_email($email);
    if ($existing && $existing['status'] === 'subscribed') {
        return 'dup';
    }

    if (confirm_on_cooldown($email)) {
        return 'wait';
    }

    if ($existing) {
        update_subscriber((int) $existing['id'], [
            'first_name' => $first !== '' ? $first : $existing['first_name'],
            'status' => 'pending',
            'opt_in' => 'double',
            'source' => $source,
            'unsubscribed_at' => '',
        ]);
        $id = (int) $existing['id'];
    } else {
        $id = insert_subscriber([
            'email' => $email,
            'first_name' => $first,
            'status' => 'pending',
            'opt_in' => 'double',
            'source' => $source,
        ]);
    }

    if ($id < 1) {
        return 'error';
    }

    $raw = store_confirm_token($id);
    $row = find($id);
    if (! $row) {
        return 'error';
    }

    mark_confirm_cooldown($email);

    return send_confirm_mail($row, $raw) ? 'confirm' : 'mail';
}

function redirect_signup(string $status): void
{
    $fallback = page_url('get-updates');
    $referer = wp_get_referer();
    $back = is_string($referer) && $referer !== '' ? wp_validate_redirect($referer, $fallback) : $fallback;
    if (! is_string($back) || $back === '') {
        $back = $fallback;
    }
    $back = remove_query_arg('signup', $back);
    send_privacy_headers();
    wp_safe_redirect(add_query_arg('signup', $status, $back).'#signup');
    exit;
}

function rate_key(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

    return 'mhn_rl_'.md5($ip);
}

function confirm_cooldown_key(string $email): string
{
    return 'mhn_confirm_cd_'.md5(strtolower($email));
}

function confirm_on_cooldown(string $email): bool
{
    return get_transient(confirm_cooldown_key($email)) !== false;
}

function mark_confirm_cooldown(string $email): void
{
    set_transient(confirm_cooldown_key($email), 1, 30 * MINUTE_IN_SECONDS);
}

function rate_limited(): bool
{
    return (int) get_transient(rate_key()) >= 8;
}

function bump_rate(): void
{
    $key = rate_key();
    set_transient($key, (int) get_transient($key) + 1, HOUR_IN_SECONDS);
}

/**
 * @return array{imported: int, pending: int, skipped: int, invalid: int}
 */
function import_rows(string $path, string $mode, bool $sendConfirm, bool $allowUnsub): array
{
    $counts = ['imported' => 0, 'pending' => 0, 'skipped' => 0, 'invalid' => 0];
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return $counts;
    }

    $header = fgetcsv($handle);
    if (! is_array($header)) {
        fclose($handle);

        return $counts;
    }

    $map = csv_map($header);
    $firstRow = $map['is_header'] ? null : $header;
    if (! $map['is_header']) {
        $map = ['email' => 0, 'first' => null, 'last' => null, 'is_header' => false];
    }

    $rows = $firstRow ? [$firstRow] : [];
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);

    foreach ($rows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $email = strtolower(sanitize_email((string) ($row[$map['email']] ?? '')));
        $firstIndex = $map['first'];
        $lastIndex = $map['last'];
        $first = $firstIndex === null ? '' : sanitize_text_field((string) ($row[$firstIndex] ?? ''));
        $last = $lastIndex === null ? '' : sanitize_text_field((string) ($row[$lastIndex] ?? ''));
        if (! is_email($email)) {
            $counts['invalid']++;

            continue;
        }

        $existing = find_by_email($email);
        if ($existing && $existing['status'] === 'unsubscribed' && ! $allowUnsub) {
            $counts['skipped']++;

            continue;
        }
        if ($existing && $existing['status'] === 'subscribed' && $mode === 'consented') {
            $counts['skipped']++;

            continue;
        }

        if ($mode === 'consented') {
            if ($existing) {
                update_subscriber((int) $existing['id'], [
                    'first_name' => $first !== '' ? clean_name($first) : $existing['first_name'],
                    'last_name' => $last !== '' ? clean_name($last) : ($existing['last_name'] ?? ''),
                    'status' => 'subscribed',
                    'opt_in' => 'import',
                    'source' => 'import',
                    'confirm_hash' => '',
                    'confirmed_at' => $existing['confirmed_at'] !== '' ? $existing['confirmed_at'] : current_time('mysql'),
                    'unsubscribed_at' => '',
                ]);
            } else {
                insert_subscriber([
                    'email' => $email,
                    'first_name' => clean_name($first),
                    'last_name' => clean_name($last),
                    'status' => 'subscribed',
                    'opt_in' => 'import',
                    'source' => 'import',
                    'confirmed_at' => current_time('mysql'),
                ]);
            }
            $counts['imported']++;

            continue;
        }

        if ($existing && $existing['status'] === 'subscribed') {
            $counts['skipped']++;

            continue;
        }

        $held = hold_pending($email, $first, $last, $existing);
        if ($held < 1) {
            $counts['invalid']++;

            continue;
        }
        if ($sendConfirm) {
            $row = find($held);
            if ($row) {
                $raw = store_confirm_token($held);
                send_confirm_mail($row, $raw);
            }
        }
        $counts['pending']++;
    }

    return $counts;
}

/**
 * Store a pending address and do not send mail.
 *
 * @param  array<string, string>|null  $existing
 */
function hold_pending(string $email, string $first, string $last, ?array $existing): int
{
    $first = clean_name($first);
    $last = clean_name($last);
    if ($existing) {
        update_subscriber((int) $existing['id'], [
            'first_name' => $first !== '' ? $first : $existing['first_name'],
            'last_name' => $last !== '' ? $last : ($existing['last_name'] ?? ''),
            'status' => 'pending',
            'opt_in' => 'double',
            'source' => 'import',
            'unsubscribed_at' => '',
        ]);
        store_confirm_token((int) $existing['id']);

        return (int) $existing['id'];
    }

    $id = insert_subscriber([
        'email' => $email,
        'first_name' => $first,
        'last_name' => $last,
        'status' => 'pending',
        'opt_in' => 'double',
        'source' => 'import',
    ]);
    if ($id < 1) {
        return 0;
    }
    store_confirm_token($id);

    return $id;
}

/**
 * @param  list<string>|array<int, string|null>  $header
 * @return array{email: int, first: int|null, last: int|null, is_header: bool}
 */
function csv_map(array $header): array
{
    $known = [
        'email' => 'email',
        'e-mail' => 'email',
        'first_name' => 'first',
        'firstname' => 'first',
        'fname' => 'first',
        'first' => 'first',
        'name' => 'first',
        'last_name' => 'last',
        'lastname' => 'last',
        'lname' => 'last',
        'surname' => 'last',
        'last' => 'last',
    ];
    $map = ['email' => 0, 'first' => null, 'last' => null, 'is_header' => false];
    foreach ($header as $index => $label) {
        $key = strtolower(trim((string) $label));
        if (! isset($known[$key])) {
            continue;
        }
        $map['is_header'] = true;
        $slot = $known[$key];
        if ($slot === 'email') {
            $map['email'] = (int) $index;
        } elseif ($slot === 'last') {
            $map['last'] = (int) $index;
        } else {
            $map['first'] = (int) $index;
        }
    }

    return $map;
}
