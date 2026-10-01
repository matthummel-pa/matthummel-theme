<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function register_subscribe_route(): void
{
    register_rest_route(
        'matthummel-newsletter/v1',
        '/subscribe',
        [
            'methods' => 'POST',
            'callback' => __NAMESPACE__.'\\rest_subscribe',
            'permission_callback' => __NAMESPACE__.'\\rest_subscribe_permission',
            'args' => [
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => __NAMESPACE__.'\\rest_subscribe_validate_email',
                ],
                'mhn_hp' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]
    );
}

function rest_subscribe_permission(\WP_REST_Request $request): bool|\WP_Error
{
    $header = $request->get_header('X-WP-Nonce');
    $nonce = is_string($header) ? sanitize_text_field(wp_unslash($header)) : '';
    if ($nonce === '' || ! wp_verify_nonce($nonce, 'wp_rest')) {
        return new \WP_Error(
            'mhn_forbidden',
            __('Reload the page and try again.', 'matthummel-newsletter'),
            ['status' => 403]
        );
    }

    return true;
}

function rest_subscribe_validate_email(mixed $value): bool|\WP_Error
{
    if (is_string($value) && is_email($value) !== false) {
        return true;
    }

    $limited = rest_subscribe_count_attempt();
    if ($limited instanceof \WP_Error) {
        return $limited;
    }

    return new \WP_Error(
        'mhn_invalid_email',
        __('Use a valid email, then try again.', 'matthummel-newsletter'),
        ['status' => 400]
    );
}

function rest_subscribe(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
{
    $limited = rest_subscribe_count_attempt();
    if ($limited instanceof \WP_Error) {
        return $limited;
    }

    $honeypot = sanitize_text_field((string) $request->get_param('mhn_hp'));
    if ($honeypot !== '') {
        return new \WP_Error(
            'mhn_rejected',
            __('Use a valid email, then try again.', 'matthummel-newsletter'),
            ['status' => 400]
        );
    }

    $email = sanitize_email((string) $request->get_param('email'));

    return rest_subscribe_from_status(subscribe_address($email, '', 'block'));
}

function rest_subscribe_from_status(string $status): \WP_REST_Response|\WP_Error
{
    $message = rest_subscribe_message($status);
    if ($status === 'confirm' || $status === 'dup') {
        return new \WP_REST_Response([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    $http = match ($status) {
        'wait' => 429,
        'mail' => 500,
        default => 400,
    };
    $code = match ($status) {
        'wait' => 'mhn_wait',
        'mail' => 'mhn_mail',
        default => 'mhn_invalid_email',
    };

    return new \WP_Error($code, $message, ['status' => $http]);
}

function rest_subscribe_message(string $status): string
{
    return match ($status) {
        'confirm' => __('Check your email to confirm.', 'matthummel-newsletter'),
        'dup' => __('That address is already signed up.', 'matthummel-newsletter'),
        'wait' => __('Please wait a while, then try again.', 'matthummel-newsletter'),
        'mail' => __('I could not send the confirmation. Try again in a minute.', 'matthummel-newsletter'),
        default => __('Use a valid email, then try again.', 'matthummel-newsletter'),
    };
}

function rest_subscribe_count_attempt(): ?\WP_Error
{
    if (rest_subscribe_is_limited()) {
        return new \WP_Error(
            'mhn_rate_limited',
            __('Please wait a while, then try again.', 'matthummel-newsletter'),
            ['status' => 429]
        );
    }

    rest_bump_subscribe_rate();

    return null;
}

function rest_subscribe_is_limited(): bool
{
    return (int) get_transient(rest_subscribe_rate_key()) >= 5;
}

function rest_bump_subscribe_rate(): void
{
    $key = rest_subscribe_rate_key();
    set_transient($key, (int) get_transient($key) + 1, 10 * MINUTE_IN_SECONDS);
}

function rest_subscribe_rate_key(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';

    return 'mhn_rest_rl_'.md5($ip);
}
