<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param  list<string>  $extraHeaders
 * @param  array<string, string>|null  $subscriber
 */
function send_mail(string $to, string $subject, string $html, string $text, array $extraHeaders = [], ?array $subscriber = null): bool
{
    if (! is_email($to)) {
        return false;
    }

    $headers = array_merge([
        'Content-Type: text/html; charset=UTF-8',
    ], $extraHeaders);

    $config = settings();
    if (is_email($config['reply_to'])) {
        $headers[] = 'Reply-To: '.$config['from_name'].' <'.$config['reply_to'].'>';
    }

    if ($subscriber && (int) ($subscriber['id'] ?? 0) > 0) {
        $unsub = unsub_url($subscriber);
        $headers[] = 'List-Unsubscribe: <'.$unsub.'>';
        $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
    }

    $GLOBALS['mhn_sending'] = true;
    $GLOBALS['mhn_alt_body'] = $text;
    $sent = wp_mail($to, $subject, $html, $headers);
    $GLOBALS['mhn_sending'] = false;
    $GLOBALS['mhn_alt_body'] = '';

    return (bool) $sent;
}

function mail_error(): string
{
    global $phpmailer;

    if (is_object($phpmailer) && isset($phpmailer->ErrorInfo)) {
        return mb_substr((string) $phpmailer->ErrorInfo, 0, 240);
    }

    return '';
}

/**
 * @param  \PHPMailer\PHPMailer\PHPMailer|object  $phpmailer
 */
function on_phpmailer(object $phpmailer): void
{
    if (empty($GLOBALS['mhn_alt_body']) || ! property_exists($phpmailer, 'AltBody')) {
        return;
    }

    $phpmailer->AltBody = (string) $GLOBALS['mhn_alt_body'];
}

function filter_from(string $email): string
{
    if (empty($GLOBALS['mhn_sending'])) {
        return $email;
    }

    $from = settings()['from_email'];

    return is_email($from) ? $from : $email;
}

function filter_from_name(string $name): string
{
    if (empty($GLOBALS['mhn_sending'])) {
        return $name;
    }

    $from = settings()['from_name'];

    return $from !== '' ? $from : $name;
}

/**
 * @param  array<string, string>  $subscriber
 */
function send_confirm_mail(array $subscriber, string $rawToken): bool
{
    $name = settings()['from_name'];
    $url = confirm_url($rawToken);
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $button = bulletproof_button(__('Confirm signup', 'matthummel-newsletter'), $url);
    $body = '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#141c28;">'
        .esc_html__('Confirm this address and I will send occasional notes when I publish. If you did not ask for this, ignore the email.', 'matthummel-newsletter')
        .'</p>'.$button;
    $html = email_document(
        __('Confirm your signup', 'matthummel-newsletter'),
        __('One click confirms your address.', 'matthummel-newsletter'),
        $body,
        false,
        0
    );
    $html = apply_merge($html, $subscriber, 0, true);
    $text = apply_merge(plain_text($html), $subscriber, 0, false);
    $subject = sprintf(
        /* translators: %s: site name */
        __('Confirm your signup — %s', 'matthummel-newsletter'),
        $name
    );

    $sent = send_mail((string) $subscriber['email'], $subject, $html, $text, [], null);
    log_event(0, (int) $subscriber['id'], $sent ? 'confirm' : 'confirm_failed', $sent ? '' : mail_error());

    return $sent;
}

function recipient_allowed(string $email): bool
{
    $list = apply_filters('mhn_send_allowlist', null);
    if ($list === null) {
        return true;
    }
    if (! is_array($list)) {
        return false;
    }

    $needle = strtolower($email);
    foreach ($list as $item) {
        if (strtolower((string) $item) === $needle) {
            return true;
        }
    }

    return false;
}
