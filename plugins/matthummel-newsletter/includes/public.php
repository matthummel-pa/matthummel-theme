<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function public_assets(): void
{
    if (! is_page(['get-updates', 'email-preferences', 'unsubscribe'])) {
        return;
    }

    wp_enqueue_style(
        'mhn-public',
        plugins_url('assets/public.css', MHN_FILE),
        [],
        MHN_VERSION
    );
}

/**
 * @param  array<string, bool|string>  $robots
 * @return array<string, bool|string>
 */
function robots(array $robots): array
{
    if (is_page(['email-preferences', 'unsubscribe'])) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }

    return $robots;
}

function shortcode_updates(): string
{
    if (current_user_can('manage_options')) {
        return public_dashboard();
    }

    return signup_form('page', true);
}

function shortcode_preferences(): string
{
    if (isset($_GET['mhn_test'])) {
        return '<p class="mhn-note" role="status">'.esc_html__('This was a test send. It did not change a subscription.', 'matthummel-newsletter').'</p>';
    }

    $token = isset($_GET['mhn_token']) ? sanitize_text_field(wp_unslash($_GET['mhn_token'])) : '';
    $subscriber = subscriber_from_token($token, absint($_GET['mhn_sid'] ?? 0));
    if (! $subscriber) {
        return '<p class="mhn-note" role="status">'.esc_html__('Use the link in the email to manage this address.', 'matthummel-newsletter').'</p>';
    }

    $saved = isset($_GET['mhn_saved']) ? sanitize_key(wp_unslash($_GET['mhn_saved'])) : '';
    $html = '';
    if ($saved === '1') {
        $html .= '<p class="mhn-status" role="status">'.esc_html__('Saved.', 'matthummel-newsletter').'</p>';
    }
    if ($subscriber['status'] === 'unsubscribed') {
        $html .= '<p class="mhn-status" role="status">'.esc_html__('This address is unsubscribed.', 'matthummel-newsletter').'</p>';
    }

    $html .= '<form class="mhn-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    $html .= '<input type="hidden" name="action" value="mhn_preferences_save">';
    $html .= '<input type="hidden" name="mhn_sid" value="'.esc_attr((string) $subscriber['id']).'">';
    $html .= '<input type="hidden" name="mhn_token" value="'.esc_attr($token).'">';
    $html .= wp_nonce_field('mhn_preferences', 'mhn_preferences_nonce', true, false);
    $html .= '<div class="mhn-field"><label for="mhn-pref-email">'.esc_html__('Email', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-pref-email" type="email" value="'.esc_attr($subscriber['email']).'" readonly></div>';
    $html .= '<div class="mhn-field"><label for="mhn-pref-name">'.esc_html__('First name', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-pref-name" name="mhn_fname" type="text" autocomplete="given-name" maxlength="80" value="'.esc_attr($subscriber['first_name'] ?? '').'"></div>';
    $html .= '<div class="mhn-field"><label for="mhn-pref-last">'.esc_html__('Last name', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-pref-last" name="mhn_lname" type="text" autocomplete="family-name" maxlength="80" value="'.esc_attr($subscriber['last_name'] ?? '').'"></div>';
    $html .= '<button type="submit" class="btn">'.esc_html__('Save preferences', 'matthummel-newsletter').'</button>';
    $html .= '</form>';

    $html .= '<form class="mhn-form mhn-unsub" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    $html .= '<input type="hidden" name="action" value="mhn_preferences_unsub">';
    $html .= '<input type="hidden" name="mhn_sid" value="'.esc_attr((string) $subscriber['id']).'">';
    $html .= '<input type="hidden" name="mhn_token" value="'.esc_attr($token).'">';
    $html .= wp_nonce_field('mhn_preferences_unsub', 'mhn_preferences_unsub_nonce', true, false);
    $html .= '<button type="submit" class="btn btn-ghost">'.esc_html__('Unsubscribe', 'matthummel-newsletter').'</button>';
    $html .= '</form>';

    return $html;
}

function shortcode_unsubscribe(): string
{
    if (isset($_GET['mhn_test'])) {
        return '<p class="mhn-note" role="status">'.esc_html__('This was a test send. It did not change a subscription.', 'matthummel-newsletter').'</p>';
    }

    $token = isset($_GET['mhn_token']) ? sanitize_text_field(wp_unslash($_GET['mhn_token'])) : '';
    $id = absint($_GET['mhn_unsub'] ?? 0);
    if ($id < 1) {
        $id = absint($_GET['mhn_sid'] ?? 0);
    }
    $subscriber = subscriber_from_token($token, $id);
    if (! $subscriber) {
        return '<p class="mhn-note" role="status">'.esc_html__('Use the link in the email to unsubscribe.', 'matthummel-newsletter').'</p>';
    }
    if ($subscriber['status'] === 'unsubscribed') {
        return '<p class="mhn-status" role="status">'.esc_html__('This address is unsubscribed.', 'matthummel-newsletter').'</p>';
    }

    $html = '<form class="mhn-form mhn-unsub" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    $html .= '<input type="hidden" name="action" value="mhn_preferences_unsub">';
    $html .= '<input type="hidden" name="mhn_return" value="unsubscribe">';
    $html .= '<input type="hidden" name="mhn_sid" value="'.esc_attr((string) $subscriber['id']).'">';
    $html .= '<input type="hidden" name="mhn_token" value="'.esc_attr($token).'">';
    $html .= wp_nonce_field('mhn_preferences_unsub', 'mhn_preferences_unsub_nonce', true, false);
    $html .= '<p>'.esc_html(sprintf(
        /* translators: %s: email address */
        __('Unsubscribe %s. This does not happen until you confirm.', 'matthummel-newsletter'),
        $subscriber['email']
    )).'</p>';
    $html .= '<button type="submit" class="btn">'.esc_html__('Unsubscribe', 'matthummel-newsletter').'</button>';
    $html .= '</form>';

    return $html;
}

/**
 * @return array<string, string>|null
 */
function subscriber_from_token(string $token, int $id): ?array
{
    if ($token === '' || $id < 1 || strlen($token) !== 64) {
        return null;
    }

    $row = find($id);

    return $row && token_matches($row, $token) ? $row : null;
}

function request_has_subscriber_secret(): bool
{
    foreach (['mhn_confirm', 'mhn_unsub', 'mhn_token', 'mhn_open', 'mhn_click', 'mhn_view', 'mhn_st', 'mhn_sid'] as $key) {
        if (isset($_GET[$key]) || isset($_POST[$key])) {
            return true;
        }
    }

    return function_exists('is_page') && is_page(['email-preferences', 'unsubscribe']);
}

function send_privacy_headers(): void
{
    if (! headers_sent()) {
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }
    nocache_headers();
}

function maybe_send_privacy_headers(): void
{
    if (request_has_subscriber_secret()) {
        send_privacy_headers();
    }
}

function on_template_redirect(): void
{
    maybe_send_privacy_headers();

    if (isset($_GET['mhn_confirm'])) {
        render_confirm_get(sanitize_text_field(wp_unslash((string) $_GET['mhn_confirm'])));
    }

    if (isset($_GET['mhn_unsub'], $_GET['mhn_token'])) {
        handle_unsub_request();
    }

    if (isset($_GET['mhn_open'])) {
        handle_open();
    }

    if (isset($_GET['mhn_click'])) {
        handle_click();
    }

    if (isset($_GET['mhn_view'])) {
        handle_view();
    }
}

function confirm_page_headers(): void
{
    if (! defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    send_privacy_headers();
}

function confirm_page_css(): string
{
    return 'body{margin:0;background:#fff;color:#141c28;font:16px/1.5 "IBM Plex Sans",Inter,system-ui,sans-serif}'
        .'.mhn-confirm{max-width:36rem;margin:0 auto;padding:2rem 1.25rem}'
        .'h1{font-size:1.75rem;line-height:1.3;margin:0 0 .75rem}'
        .'p{margin:0 0 1rem}'
        .'.mhn-confirm-btn{display:inline-block;min-height:44px;padding:.55rem 1rem;border:0;border-radius:4px;background:#0d2e57;color:#fff;font-size:1rem;font-weight:650;line-height:1.4;cursor:pointer;text-decoration:none}'
        .'.mhn-confirm-btn:focus-visible{outline:3px solid #0d2e57;outline-offset:3px}'
        .'label{display:block;font-weight:650;margin:0 0 .35rem}'
        .'input[type=text]{display:block;width:100%;max-width:24rem;min-height:44px;margin:0 0 1rem;padding:.5rem .7rem;border:1px solid #8aa0bd;border-radius:4px;font:inherit;background:#fff;color:#141c28;box-sizing:border-box}'
        .'input[type=text]:focus-visible{outline:3px solid #0d2e57;outline-offset:2px}'
        .'.mhn-status{color:#166534}'
        .'.mhn-status-error{color:#b42318}'
        .'@media (prefers-color-scheme:dark){body{background:#162033;color:#f7f9fc}input[type=text]{background:#0f1b2d;color:#f7f9fc;border-color:#2a3b52}input[type=text]:focus-visible{outline-color:#d6e4ff}.mhn-confirm-btn{background:#d6e4ff;color:#0d2e57}.mhn-confirm-btn:focus-visible{outline-color:#d6e4ff}.mhn-status{color:#b7e4c7}.mhn-status-error{color:#ffb4a8}}';
}

function confirm_document(string $title, string $body): string
{
    $lang = get_bloginfo('language');
    $lang = is_string($lang) && $lang !== '' ? $lang : 'en';
    $dir = is_rtl() ? 'rtl' : 'ltr';

    return '<!DOCTYPE html><html lang="'.esc_attr($lang).'" dir="'.esc_attr($dir).'">'
        .'<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<meta name="robots" content="noindex, nofollow"><title>'.esc_html($title).'</title>'
        .'<style>'.confirm_page_css().'</style></head><body>'.$body.'</body></html>';
}

function confirm_form_html(string $token): string
{
    $row = find_by_confirm_token($token);
    $first = $row['first_name'] ?? '';
    $last = $row['last_name'] ?? '';
    $html = '<main class="mhn-confirm">';
    $html .= '<h1>'.esc_html__('Confirm your subscription', 'matthummel-newsletter').'</h1>';
    $html .= '<p>'.esc_html__('Press the button and I will add this address. Opening this page does not confirm it. A name is optional.', 'matthummel-newsletter').'</p>';
    $html .= '<form class="mhn-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    $html .= '<input type="hidden" name="action" value="mhn_confirm">';
    $html .= '<input type="hidden" name="mhn_confirm" value="'.esc_attr($token).'">';
    $html .= wp_nonce_field('mhn_confirm_subscription', 'mhn_confirm_nonce', true, false);
    $html .= '<p><label for="mhn-confirm-first">'.esc_html__('First name', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-confirm-first" name="mhn_fname" type="text" autocomplete="given-name" maxlength="80" value="'.esc_attr($first).'"></p>';
    $html .= '<p><label for="mhn-confirm-last">'.esc_html__('Last name', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-confirm-last" name="mhn_lname" type="text" autocomplete="family-name" maxlength="80" value="'.esc_attr($last).'"></p>';
    $html .= '<button type="submit" class="mhn-confirm-btn">'.esc_html__('Confirm your subscription', 'matthummel-newsletter').'</button>';
    $html .= '</form></main>';

    return $html;
}

function confirm_result_html(string $status): string
{
    if ($status === 'ok') {
        return '<main class="mhn-confirm"><h1>'.esc_html__('You are on the list', 'matthummel-newsletter').'</h1>'
            .'<p class="mhn-status" role="status">'.esc_html__('Thanks. I will send a note when I publish.', 'matthummel-newsletter').'</p>'
            .'<p><a class="mhn-confirm-btn" href="'.esc_url(home_url('/')).'">'.esc_html__('Back to the site', 'matthummel-newsletter').'</a></p></main>';
    }

    return '<main class="mhn-confirm"><h1>'.esc_html__('This link did not confirm', 'matthummel-newsletter').'</h1>'
        .'<p class="mhn-status mhn-status-error" role="alert">'.esc_html__('Use the button on the link from the email. If you already confirmed, you are on the list.', 'matthummel-newsletter').'</p></main>';
}

/**
 * Confirm only when the POST includes the token and a valid nonce.
 */
function confirm_post_status(string $token, string $nonce, string $first = '', string $last = ''): string
{
    if ($nonce === '' || wp_verify_nonce($nonce, 'mhn_confirm_subscription') === false) {
        return 'error';
    }

    $row = find_by_confirm_token($token);
    if ($row) {
        update_subscriber((int) $row['id'], [
            'first_name' => clean_name($first),
            'last_name' => clean_name($last),
        ]);
    }

    return confirm_subscriber($token) === 'ok' ? 'ok' : 'error';
}

function render_confirm_get(string $token): void
{
    confirm_page_headers();
    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    echo confirm_document(
        __('Confirm your subscription', 'matthummel-newsletter'),
        confirm_form_html($token)
    );
    exit;
}

function posted_text(string $key): string
{
    if (! isset($_POST[$key]) || is_array($_POST[$key])) {
        return '';
    }

    $value = wp_unslash($_POST[$key]);

    return is_string($value) ? $value : '';
}

function handle_confirm_post(): void
{
    $token = isset($_POST['mhn_confirm']) ? sanitize_text_field(wp_unslash($_POST['mhn_confirm'])) : '';
    $nonce = isset($_POST['mhn_confirm_nonce']) ? wp_unslash($_POST['mhn_confirm_nonce']) : '';
    $first = posted_text('mhn_fname');
    $last = posted_text('mhn_lname');
    $status = confirm_post_status($token, is_string($nonce) ? $nonce : '', $first, $last);
    confirm_page_headers();
    status_header($status === 'ok' ? 200 : 400);
    header('Content-Type: text/html; charset=utf-8');
    $title = $status === 'ok'
        ? __('You are on the list', 'matthummel-newsletter')
        : __('This link did not confirm', 'matthummel-newsletter');
    echo confirm_document($title, confirm_result_html($status));
    exit;
}

function handle_unsub_request(): void
{
    $id = absint($_GET['mhn_unsub'] ?? 0);
    $token = isset($_GET['mhn_token']) ? sanitize_text_field(wp_unslash($_GET['mhn_token'])) : '';
    $row = find($id);
    $valid = $row && token_matches($row, $token);
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

    if ($method === 'POST' && $valid) {
        $raw = file_get_contents('php://input');
        $raw = is_string($raw) ? $raw : '';
        $posted = isset($_POST['List-Unsubscribe']) ? sanitize_text_field(wp_unslash($_POST['List-Unsubscribe'])) : '';
        if ($posted === 'One-Click' || str_contains($raw, 'List-Unsubscribe=One-Click')) {
            unsubscribe($id);
            status_header(200);
            nocache_headers();
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Unsubscribed';
            exit;
        }
    }

    if (! $valid || ! $row) {
        wp_safe_redirect(page_url('unsubscribe'));
        exit;
    }

    if (function_exists('is_page') && is_page('unsubscribe')) {
        return;
    }

    wp_safe_redirect(add_query_arg([
        'mhn_unsub' => $id,
        'mhn_token' => $token,
    ], page_url('unsubscribe')));
    exit;
}

function open_should_count(int $issueId, int $subscriberId, string $token): bool
{
    if (settings()['track_opens'] !== 1 || $issueId < 1 || $subscriberId < 1) {
        return false;
    }

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return false;
    }

    $row = find($subscriberId);
    if (! $row) {
        return false;
    }

    return tracking_token_matches($row, $issueId, 'open', '', $token);
}

/**
 * @return array<string, string>|null
 */
function subscriber_for_preview(int $issueId, int $subscriberId, string $token): ?array
{
    if ($subscriberId < 1 || $token === '') {
        return null;
    }

    $row = find($subscriberId);
    if (! $row || ! view_token_matches($row, $issueId, $token)) {
        return null;
    }

    return $row;
}

function decode_click_target(string $encoded): string
{
    $encoded = strtr(trim($encoded), ' ', '+');
    if ($encoded === '' || preg_match('/^[A-Za-z0-9+\/=]+$/', $encoded) !== 1) {
        return '';
    }

    $target = base64_decode($encoded, true);

    return is_string($target) ? $target : '';
}

function click_target_is_allowed(string $target): bool
{
    if ($target === '' || preg_match('/[\s\\\\]/', $target) === 1) {
        return false;
    }

    $parts = wp_parse_url($target);
    if (! is_array($parts)) {
        return false;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (! in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    if (($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
        return false;
    }

    return (string) ($parts['host'] ?? '') !== '';
}

/**
 * Honor a click only when tracking is on and the link was signed for this issue.
 *
 * @return array{url: string, tracked: bool}
 */
function safe_click_target(int $issueId, int $subscriberId, string $token, string $encoded): array
{
    $home = ['url' => home_url('/'), 'tracked' => false];
    if (settings()['track_clicks'] !== 1 || $issueId < 1 || $subscriberId < 1 || $token === '') {
        return $home;
    }

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return $home;
    }

    $target = decode_click_target($encoded);
    if ($target === '' || ! click_target_is_allowed($target)) {
        return $home;
    }

    $row = find($subscriberId);
    if (! $row || ! tracking_token_matches($row, $issueId, 'click', $target, $token)) {
        return $home;
    }

    return ['url' => $target, 'tracked' => true];
}

function handle_open(): void
{
    $issueId = absint($_GET['mhn_open'] ?? 0);
    $subscriberId = absint($_GET['mhn_sid'] ?? 0);
    $token = isset($_GET['mhn_st']) ? sanitize_text_field(wp_unslash($_GET['mhn_st'])) : '';
    if (open_should_count($issueId, $subscriberId, $token)) {
        log_event($issueId, $subscriberId, 'open', '');
    }

    send_privacy_headers();
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

function handle_click(): void
{
    $issueId = absint($_GET['mhn_click'] ?? 0);
    $subscriberId = absint($_GET['mhn_sid'] ?? 0);
    $token = isset($_GET['mhn_st']) ? sanitize_text_field(wp_unslash($_GET['mhn_st'])) : '';
    $encoded = isset($_GET['mhn_u']) ? (string) wp_unslash($_GET['mhn_u']) : '';
    $click = safe_click_target($issueId, $subscriberId, $token, $encoded);
    if ($click['tracked']) {
        $host = wp_parse_url($click['url'], PHP_URL_HOST);
        log_event($issueId, $subscriberId, 'click', is_string($host) ? $host : '');
    }

    send_privacy_headers();
    wp_redirect($click['url']);
    exit;
}

function handle_view(): void
{
    $issueId = absint($_GET['mhn_view'] ?? 0);
    $key = isset($_GET['k']) ? sanitize_text_field(wp_unslash($_GET['k'])) : '';
    $expectedKey = preview_key($issueId);
    $allowed = ($key !== '' && strlen($key) === strlen($expectedKey) && hash_equals($expectedKey, $key)) || current_user_can('manage_options');
    $post = get_post($issueId);
    if (! $allowed || ! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        wp_die(esc_html__('That preview is not available.', 'matthummel-newsletter'), 404);
    }

    $subscriberId = absint($_GET['mhn_sid'] ?? 0);
    $token = isset($_GET['mhn_st']) ? sanitize_text_field(wp_unslash($_GET['mhn_st'])) : '';
    $subscriber = subscriber_for_preview($issueId, $subscriberId, $token);

    $message = issue_message($issueId, $subscriber, true);
    send_privacy_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo $message['html'];
    exit;
}

function signup_form(string $source, bool $showName): string
{
    $status = isset($_GET['signup']) ? sanitize_key(wp_unslash($_GET['signup'])) : '';
    $invalid = in_array($status, ['error', 'mail'], true);
    $compact = $source === 'footer';
    $html = '<div class="'.($compact ? 'mhn-signup mhn-signup-compact' : 'mhn-signup').'" id="'.($compact ? 'footer-signup-form' : 'signup').'">';
    $html .= signup_notice($status);
    $formClass = $compact ? 'footer-follow__form mhn-form' : 'mhn-form';
    $html .= '<form class="'.esc_attr($formClass).'" method="post" action="'.esc_url(admin_url('admin-post.php')).'" novalidate>';
    $html .= '<input type="hidden" name="action" value="mhn_signup">';
    $html .= '<input type="hidden" name="mhn_source" value="'.esc_attr($source).'">';
    $html .= wp_nonce_field('mhn_signup', 'mhn_signup_nonce', true, false);
    $html .= '<p class="mhn-hp" aria-hidden="true"><label for="mhn-hp-'.$source.'">'.esc_html__('Leave blank', 'matthummel-newsletter').'</label>';
    $html .= '<input id="mhn-hp-'.$source.'" type="text" name="mhn_hp" value="" tabindex="-1" autocomplete="off"></p>';

    if ($showName) {
        $html .= '<div class="mhn-field"><label for="mhn-fname">'.esc_html__('First name', 'matthummel-newsletter').' <span>'.esc_html__('(optional)', 'matthummel-newsletter').'</span></label>';
        $html .= '<input id="mhn-fname" name="mhn_fname" type="text" autocomplete="given-name"></div>';
    }

    $emailId = 'mhn-email-'.$source;
    $hintId = 'mhn-email-hint-'.$source;
    $errorId = 'mhn-email-error-'.$source;
    if ($invalid) {
        $described = ' aria-invalid="true" aria-describedby="'.esc_attr($errorId).'"';
    } elseif (! $compact) {
        $described = ' aria-describedby="'.esc_attr($hintId).'"';
    } else {
        $described = '';
    }
    $labelClass = $compact ? ' class="visually-hidden"' : '';
    $inputClass = $compact ? ' class="footer-follow__email"' : '';
    $html .= '<div class="mhn-field"><label'.$labelClass.' for="'.esc_attr($emailId).'">'.esc_html__('Email', 'matthummel-newsletter').'</label>';
    $html .= '<input id="'.esc_attr($emailId).'"'.$inputClass.' name="mhn_email" type="email" required autocomplete="email" placeholder="'.esc_attr__('you@example.com', 'matthummel-newsletter').'"'.$described.'>';
    if (! $compact) {
        $html .= '<p class="mhn-hint" id="'.esc_attr($hintId).'">'.esc_html__('I keep the address on this site. I do not send it to a newsletter service.', 'matthummel-newsletter').'</p>';
    }
    if ($invalid) {
        $html .= '<p class="mhn-error" id="'.esc_attr($errorId).'">'.esc_html__('Use a valid email, then try again.', 'matthummel-newsletter').'</p>';
    }
    $html .= '</div>';
    $html .= '<button type="submit" class="btn">'.esc_html__('Sign up', 'matthummel-newsletter').'</button>';
    $html .= '</form></div>';

    return $html;
}

function signup_notice(string $status): string
{
    $messages = [
        'confirm' => __('Check your email to confirm.', 'matthummel-newsletter'),
        'ok' => __('You are on the list. Thanks.', 'matthummel-newsletter'),
        'dup' => __('That address is already signed up.', 'matthummel-newsletter'),
        'wait' => __('Please wait a while, then try again.', 'matthummel-newsletter'),
        'mail' => __('I could not send the confirmation. Try again in a minute.', 'matthummel-newsletter'),
        'error' => __('Use a valid email, then try again.', 'matthummel-newsletter'),
    ];
    if (! isset($messages[$status])) {
        return '';
    }

    $role = in_array($status, ['error', 'mail', 'wait'], true) ? 'alert' : 'status';
    $class = in_array($status, ['error', 'mail', 'wait'], true) ? 'mhn-status mhn-status-error' : 'mhn-status';

    return '<p class="'.$class.'" role="'.$role.'">'.esc_html($messages[$status]).'</p>';
}

function footer_form_html(): string
{
    return signup_form('footer', false);
}

function public_dashboard(): string
{
    $data = dashboard_data();
    $html = '<section class="mhn-dash" aria-labelledby="mhn-dash-title">';
    $html .= '<h2 id="mhn-dash-title">'.esc_html__('Get updates', 'matthummel-newsletter').'</h2>';
    $html .= '<ul class="mhn-stats">';
    $html .= '<li><strong>'.esc_html((string) $data['subscribed']).'</strong> '.esc_html__('subscribed', 'matthummel-newsletter').'</li>';
    $html .= '<li><strong>'.esc_html((string) $data['pending']).'</strong> '.esc_html__('pending', 'matthummel-newsletter').'</li>';
    $html .= '<li><strong>'.esc_html((string) $data['unsubscribed']).'</strong> '.esc_html__('unsubscribed', 'matthummel-newsletter').'</li>';
    $html .= '</ul>';
    $html .= '<p>'.esc_html(sprintf(
        /* translators: 1: new subscribers in 30 days, 2: previous 30 days */
        __('%1$d new in the last 30 days. %2$d in the 30 days before that.', 'matthummel-newsletter'),
        $data['recent'],
        $data['prior']
    )).'</p>';
    if ($data['legacy'] > 0) {
        $html .= '<p class="mhn-note">'.esc_html(sprintf(
            /* translators: %d: number of legacy addresses */
            __('%d addresses came from the old footer list. They stay subscribed. New signups confirm by email first.', 'matthummel-newsletter'),
            $data['legacy']
        )).'</p>';
    }
    $html .= '<p class="mhn-actions">';
    $html .= '<a class="btn" href="'.esc_url(wizard_url()).'">'.esc_html__('Create newsletter', 'matthummel-newsletter').'</a> ';
    $html .= '<a class="btn" href="'.esc_url(admin_url('post-new.php?post_type=newsletter_issue')).'">'.esc_html__('Block editor', 'matthummel-newsletter').'</a> ';
    $html .= '<a class="btn" href="'.esc_url(admin_url('admin.php?page=mhn-import')).'">'.esc_html__('Import', 'matthummel-newsletter').'</a> ';
    $html .= '<a class="btn" href="'.esc_url(admin_url('admin.php?page=mhn-settings')).'">'.esc_html__('Settings', 'matthummel-newsletter').'</a> ';
    $html .= '<a class="btn" href="'.esc_url(admin_url('admin.php?page=mhn-archive')).'">'.esc_html__('Sent archive', 'matthummel-newsletter').'</a>';
    $html .= '</p>';
    $html .= issues_table($data['issues'], false);
    $html .= '</section>';

    return $html;
}

add_action('send_headers', __NAMESPACE__.'\\maybe_send_privacy_headers');
add_action('admin_post_mhn_confirm', __NAMESPACE__.'\\handle_confirm_post');
add_action('admin_post_nopriv_mhn_confirm', __NAMESPACE__.'\\handle_confirm_post');
add_action('admin_post_mhn_preferences_save', __NAMESPACE__.'\\handle_preferences_save');
add_action('admin_post_nopriv_mhn_preferences_save', __NAMESPACE__.'\\handle_preferences_save');
add_action('admin_post_mhn_preferences_unsub', __NAMESPACE__.'\\handle_preferences_unsub');
add_action('admin_post_nopriv_mhn_preferences_unsub', __NAMESPACE__.'\\handle_preferences_unsub');

function handle_preferences_save(): void
{
    $token = isset($_POST['mhn_token']) ? sanitize_text_field(wp_unslash($_POST['mhn_token'])) : '';
    $nonce = isset($_POST['mhn_preferences_nonce']) ? wp_unslash($_POST['mhn_preferences_nonce']) : '';
    $row = subscriber_from_token($token, absint($_POST['mhn_sid'] ?? 0));
    if (! $row || ! is_string($nonce) || ! wp_verify_nonce($nonce, 'mhn_preferences')) {
        send_privacy_headers();
        wp_safe_redirect(page_url('email-preferences'));
        exit;
    }

    update_subscriber((int) $row['id'], [
        'first_name' => clean_name(posted_text('mhn_fname')),
        'last_name' => clean_name(posted_text('mhn_lname')),
    ]);
    send_privacy_headers();
    wp_safe_redirect(add_query_arg([
        'mhn_sid' => (int) $row['id'],
        'mhn_token' => $token,
        'mhn_saved' => '1',
    ], page_url('email-preferences')));
    exit;
}

function handle_preferences_unsub(): void
{
    $token = isset($_POST['mhn_token']) ? sanitize_text_field(wp_unslash($_POST['mhn_token'])) : '';
    $nonce = isset($_POST['mhn_preferences_unsub_nonce']) ? wp_unslash($_POST['mhn_preferences_unsub_nonce']) : '';
    $return = isset($_POST['mhn_return']) ? sanitize_key(wp_unslash((string) $_POST['mhn_return'])) : '';
    $slug = $return === 'unsubscribe' ? 'unsubscribe' : 'email-preferences';
    $id = absint($_POST['mhn_sid'] ?? 0);
    if ($id < 1) {
        $id = absint($_POST['mhn_unsub'] ?? 0);
    }
    $row = subscriber_from_token($token, $id);
    if (! $row || ! is_string($nonce) || ! wp_verify_nonce($nonce, 'mhn_preferences_unsub')) {
        send_privacy_headers();
        wp_safe_redirect(page_url($slug));
        exit;
    }

    unsubscribe((int) $row['id']);
    send_privacy_headers();
    $args = $slug === 'unsubscribe'
        ? [
            'mhn_unsub' => (int) $row['id'],
            'mhn_token' => $token,
        ]
        : [
            'mhn_sid' => (int) $row['id'],
            'mhn_token' => $token,
            'mhn_saved' => '1',
        ];
    wp_safe_redirect(add_query_arg($args, page_url($slug)));
    exit;
}
