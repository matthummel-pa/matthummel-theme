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

    return send_confirm_mail($row, $raw) ? 'confirm' : 'mail';
}

function redirect_signup(string $status): void
{
    $back = wp_get_referer();
    if (! is_string($back) || $back === '') {
        $back = page_url('get-updates');
    }
    $back = remove_query_arg('signup', $back);
    wp_safe_redirect(add_query_arg('signup', $status, $back).'#signup');
    exit;
}

function rate_key(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

    return 'mhn_rl_'.md5($ip.'|'.$ua);
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
        $map = ['email' => 0, 'first' => null, 'is_header' => false];
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
        $first = $firstIndex === null ? '' : sanitize_text_field((string) ($row[$firstIndex] ?? ''));
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
                    'first_name' => $first !== '' ? mb_substr($first, 0, 80) : $existing['first_name'],
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
                    'first_name' => mb_substr($first, 0, 80),
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

        $held = hold_pending($email, $first, $existing);
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
function hold_pending(string $email, string $first, ?array $existing): int
{
    $first = mb_substr($first, 0, 80);
    if ($existing) {
        update_subscriber((int) $existing['id'], [
            'first_name' => $first !== '' ? $first : $existing['first_name'],
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
 * @return array{email: int, first: int|null, is_header: bool}
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
    ];
    $map = ['email' => 0, 'first' => null, 'is_header' => false];
    foreach ($header as $index => $label) {
        $key = strtolower(trim((string) $label));
        if (! isset($known[$key])) {
            continue;
        }
        $map['is_header'] = true;
        if ($known[$key] === 'email') {
            $map['email'] = (int) $index;
        } else {
            $map['first'] = (int) $index;
        }
    }

    return $map;
}
