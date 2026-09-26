<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

/**
 * Email accessibility checks. Palette math and HTML audits run without WordPress
 * so CI can call them. Issue audits need WordPress.
 */

/**
 * Template colors. Light and dark pairs in contrast_pairs() stay at or above WCAG 2.2 AA.
 *
 * @return array<string, string>
 */
function email_palette(): array
{
    return [
        'ink' => '#141c28',
        'navy' => '#0d2e57',
        'paper' => '#ffffff',
        'canvas' => '#eef3f9',
        'muted' => '#3a4554',
        'quote' => '#243041',
        'group' => '#f7f9fc',
        'rule' => '#cfd9e6',
        'soft' => '#dceaf8',
        'quiet' => '#50575e',
        'hairline' => '#8aa0bd',
        'dark_page' => '#0b1220',
        'dark_card' => '#162033',
        'dark_ink' => '#f7f9fc',
        'dark_muted' => '#b7c3d4',
        'dark_link' => '#d6e4ff',
        'dark_group' => '#0f1b2d',
        'dark_rule' => '#2a3b52',
    ];
}

/**
 * @return list<array{0: string, 1: string, 2: float}>
 */
function contrast_pairs(): array
{
    return [
        ['ink', 'paper', 4.5],
        ['navy', 'paper', 4.5],
        ['muted', 'paper', 4.5],
        ['quiet', 'paper', 4.5],
        ['quote', 'paper', 4.5],
        ['ink', 'group', 4.5],
        ['navy', 'group', 4.5],
        ['paper', 'navy', 4.5],
        ['dark_ink', 'dark_card', 4.5],
        ['dark_ink', 'dark_group', 4.5],
        ['dark_ink', 'dark_page', 4.5],
        ['dark_muted', 'dark_card', 4.5],
        ['dark_link', 'dark_card', 4.5],
        ['dark_link', 'dark_group', 4.5],
        ['paper', 'dark_card', 4.5],
    ];
}

function contrast_ratio(string $foreground, string $background): float
{
    $lighter = max(relative_luminance($foreground), relative_luminance($background));
    $darker = min(relative_luminance($foreground), relative_luminance($background));

    return ($lighter + 0.05) / ($darker + 0.05);
}

function relative_luminance(string $hex): float
{
    $hex = ltrim(strtolower($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || preg_match('/^[0-9a-f]{6}$/', $hex) !== 1) {
        return 0.0;
    }

    $channels = [
        hexdec(substr($hex, 0, 2)) / 255,
        hexdec(substr($hex, 2, 2)) / 255,
        hexdec(substr($hex, 4, 2)) / 255,
    ];
    $linear = array_map(static function (float $channel): float {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }, $channels);

    return (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);
}

/**
 * @return array{errors: list<string>, warnings: list<string>}
 */
function audit_palette(): array
{
    $palette = email_palette();
    $errors = [];
    foreach (contrast_pairs() as $pair) {
        [$fgKey, $bgKey, $minimum] = $pair;
        $fg = $palette[$fgKey] ?? '';
        $bg = $palette[$bgKey] ?? '';
        $ratio = contrast_ratio($fg, $bg);
        if ($ratio + 0.01 < $minimum) {
            $errors[] = sprintf('%s on %s is %.2f:1 (needs %.1f:1).', $fg, $bg, $ratio, $minimum);
        }
    }

    return ['errors' => $errors, 'warnings' => []];
}

/**
 * @return array{errors: list<string>, warnings: list<string>}
 */
function audit_html(string $html, string $text = '', string $address = ''): array
{
    $errors = [];
    $warnings = [];

    if (preg_match('/<html\b[^>]*\blang=["\'][^"\']+["\']/i', $html) !== 1) {
        $errors[] = 'The html element needs a lang attribute.';
    }
    if (preg_match('/<html\b[^>]*\bdir=["\'](ltr|rtl)["\']/i', $html) !== 1) {
        $errors[] = 'The html element needs a dir attribute.';
    }
    if (preg_match('/<title>\s*[^<]+<\/title>/i', $html) !== 1) {
        $errors[] = 'The email needs a title.';
    }
    if (stripos($html, 'name="color-scheme"') === false || stripos($html, 'prefers-color-scheme:dark') === false) {
        $errors[] = 'Dark mode needs a color-scheme meta tag and a prefers-color-scheme rule.';
    }

    if (preg_match_all('/<table\b[^>]*>/i', $html, $tables) > 0) {
        foreach ($tables[0] as $table) {
            if (stripos($table, 'role="presentation"') === false && stripos($table, "role='presentation'") === false) {
                $errors[] = 'Layout tables need role="presentation".';
                break;
            }
        }
    }

    $h1 = preg_match_all('/<h1\b/i', $html);
    if ($h1 !== 1) {
        $errors[] = 'The email needs exactly one h1.';
    }
    if (preg_match_all('/<h([1-6])\b/i', $html, $levels) > 0) {
        $previous = 0;
        foreach ($levels[1] as $level) {
            $current = (int) $level;
            if ($previous > 0 && $current > $previous + 1) {
                $errors[] = 'Heading levels skip from h'.$previous.' to h'.$current.'.';
                break;
            }
            $previous = $current;
        }
    }

    if (preg_match_all('/<img\b[^>]*>/i', $html, $images) > 0) {
        $missingAlt = 0;
        foreach ($images[0] as $image) {
            if (preg_match('/\balt=/i', $image) !== 1) {
                $errors[] = 'Every image needs an alt attribute.';
                break;
            }
            $isContent = stripos($image, 'mhn-img') !== false;
            $empty = preg_match('/\balt=["\']\s*["\']/i', $image) === 1;
            if ($isContent && $empty) {
                $missingAlt++;
            }
        }
        if ($missingAlt > 0) {
            $errors[] = $missingAlt === 1
                ? 'Add alt text for the image before sending.'
                : 'Add alt text for '.$missingAlt.' images before sending.';
        }
    }

    if (stripos($html, 'class="mhn-preheader"') === false || stripos($html, 'aria-hidden="true"') === false) {
        $errors[] = 'Hide preheader filler with aria-hidden so screen readers skip it.';
    }
    if (stripos($html, 'Unsubscribe') === false) {
        $errors[] = 'The footer needs a visible unsubscribe link.';
    }
    if (stripos($html, 'You got this because') === false) {
        $errors[] = 'The footer needs a line that says why the person got this email.';
    }
    if ($address !== '' && ! str_contains($html, $address)) {
        $errors[] = 'The footer needs the physical mailing address.';
    }
    if (stripos($html, 'font-size:16px') === false) {
        $errors[] = 'Body text needs a 16px font size.';
    }
    $readable = preg_replace('/<div class="mhn-preheader\b.*?<\/div>/is', '', $html) ?? $html;
    if (preg_match('/font-size:\s*(?:[1-9]|1[0-2])px/i', $readable) === 1) {
        $errors[] = 'Text is smaller than 13px.';
    }
    $centeredWelcome = str_contains($html, 'data-mhn-layout="welcome"');
    if (! $centeredWelcome && preg_match('/<p\b[^>]*text-align:\s*(center|justify)/i', $html) === 1) {
        $errors[] = 'Paragraphs should be left-aligned.';
    }
    if (stripos($html, 'min-height:44px') === false) {
        $errors[] = 'Buttons need a tap target of at least 44px.';
    }
    if (strlen($html) > 102400) {
        $errors[] = 'HTML is over 100KB, so Gmail may clip it.';
    }
    if (preg_match('/\{(?:first_name|last_name|full_name)(?:\|[^{}]*)?\}/', $html) === 1) {
        $errors[] = 'A name merge tag is still in the email. Use {first_name}, {last_name}, {full_name}, or a fallback such as {first_name|there}.';
    }

    $contentImages = preg_match_all('/<img\b[^>]*mhn-img/i', $html);
    $visible = trim(strip_tags($html));
    if (is_int($contentImages) && $contentImages > 0 && strlen($visible) < ($contentImages * 80)) {
        $errors[] = 'The email needs more text relative to its images.';
    }

    if (preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/is', $html, $links, PREG_SET_ORDER) > 0) {
        foreach ($links as $link) {
            $attrs = $link[1];
            $label = strtolower(trim(html_entity_decode(strip_tags($link[2]), ENT_QUOTES)));
            $label = trim($label, " \t\n\r\0\x0B.?!");
            $isButton = stripos($attrs, 'mhn-btn') !== false;
            $underlined = stripos($attrs, 'text-decoration:underline') !== false || stripos($attrs, 'text-decoration: underline') !== false;
            if (is_vague_link($label)) {
                $warnings[] = 'Link text is vague: "'.$label.'". Name the destination.';
            }
            if (! $isButton && ! $underlined) {
                $errors[] = 'Links need an underline, not color alone.';
                break;
            }
        }
    }

    $palette = audit_palette();
    $errors = array_merge($errors, $palette['errors']);
    $allowed = array_map('strtolower', array_values(email_palette()));
    if (preg_match_all('/#([0-9a-fA-F]{6})\b/', $html, $colors) > 0) {
        foreach (array_unique($colors[1]) as $color) {
            if (! in_array('#'.strtolower($color), $allowed, true)) {
                $errors[] = 'Unexpected color #'.$color.' is outside the checked palette.';
                break;
            }
        }
    }

    if (trim($text) === '') {
        $errors[] = 'The email needs a plain-text part.';
    } elseif (stripos($text, 'Unsubscribe') === false) {
        $errors[] = 'The plain-text part needs the unsubscribe line.';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
    ];
}

function is_vague_link(string $label): bool
{
    if ($label === '') {
        return false;
    }

    $vague = [
        'click here',
        'here',
        'read more',
        'link',
        'learn more',
        'more',
        'this',
        'this link',
        'continue',
        'continue reading',
        'info',
        'read the post',
    ];

    return in_array($label, $vague, true);
}

/**
 * @return array{errors: list<string>, warnings: list<string>}
 */
function source_function_body(string $source, string $name): string
{
    $start = strpos($source, 'function '.$name.'(');
    if ($start === false) {
        return '';
    }

    $next = strpos($source, "\nfunction ", $start + strlen($name));
    if ($next === false) {
        return substr($source, $start);
    }

    return substr($source, $start, $next - $start);
}

function audit_plugin_sources(): array
{
    $root = dirname(__DIR__);
    $errors = [];
    $mailer = (string) file_get_contents($root.'/includes/mailer.php');
    $public = (string) file_get_contents($root.'/includes/public.php');
    $options = (string) file_get_contents($root.'/includes/options.php');
    $signup = (string) file_get_contents($root.'/includes/signup.php');
    $render = (string) file_get_contents($root.'/includes/render.php');

    if (! str_contains($mailer, 'List-Unsubscribe-Post: List-Unsubscribe=One-Click') || ! str_contains($mailer, 'List-Unsubscribe:')) {
        $errors[] = 'Campaign mail needs List-Unsubscribe and List-Unsubscribe-Post headers.';
    }
    if (! str_contains($public, 'List-Unsubscribe=One-Click') || ! str_contains($public, 'unsubscribe(')) {
        $errors[] = 'One-click unsubscribe must update the list in the same request.';
    }
    if (! str_contains(source_function_body($public, 'handle_unsub_request'), 'token_matches(')) {
        $errors[] = 'One-click unsubscribe must keep using the manage token.';
    }
    if (! str_contains($options, "'track_opens' => 0") || ! str_contains($options, "'track_clicks' => 0")) {
        $errors[] = 'Open and click tracking must stay off by default.';
    }
    if (! str_contains($options, "'auto_send' => 0")) {
        $errors[] = 'Automatic sending must stay off by default.';
    }
    if (! str_contains($signup, "'opt_in' => 'double'")) {
        $errors[] = 'New signups need double opt-in.';
    }

    foreach (['click_url', 'open_url'] as $name) {
        $body = source_function_body($render, $name);
        if (! str_contains($body, 'tracking_token(') || str_contains($body, 'subscriber_token(')) {
            $errors[] = $name.' must use a tracking token, not the manage token.';
        }
    }

    $archive = source_function_body($render, 'archive_url');
    if (! str_contains($archive, 'view_token(') || str_contains($archive, 'subscriber_token(')) {
        $errors[] = 'Browser preview must not carry the manage token.';
    }

    $click = source_function_body($public, 'safe_click_target');
    if (! str_contains($click, 'track_clicks') || ! str_contains($click, 'home_url') || ! str_contains($click, 'tracking_token_matches(')) {
        $errors[] = 'Click redirects must stay on this site unless tracking is on and the link is signed.';
    }
    $handler = source_function_body($public, 'handle_click');
    if (! str_contains($handler, 'safe_click_target(') || str_contains($handler, 'token_matches(')) {
        $errors[] = 'The click handler must use the signed target only.';
    }
    if (str_contains(source_function_body($public, 'handle_open'), 'token_matches(')) {
        $errors[] = 'Open tracking must not accept the manage token.';
    }
    if (str_contains(source_function_body($public, 'handle_view'), 'token_matches(')) {
        $errors[] = 'Browser preview must not accept the manage token.';
    }
    if (! str_contains($public, 'Referrer-Policy: no-referrer')) {
        $errors[] = 'Public redirects need Referrer-Policy: no-referrer.';
    }

    $rate = source_function_body($signup, 'rate_key');
    if ($rate === '' || str_contains($rate, 'HTTP_USER_AGENT')) {
        $errors[] = 'Signup rate limit must not include the user agent.';
    }
    $subscribe = source_function_body($signup, 'subscribe_address');
    $cooldownAt = strpos($subscribe, 'confirm_on_cooldown(');
    $tokenAt = strpos($subscribe, 'store_confirm_token(');
    if ($cooldownAt === false || $tokenAt === false || $cooldownAt > $tokenAt || ! str_contains($signup, 'mhn_confirm_cd_')) {
        $errors[] = 'Confirmation mail needs a per-address cooldown before a new link is stored.';
    }
    if (! str_contains(source_function_body($signup, 'redirect_signup'), 'wp_validate_redirect(')) {
        $errors[] = 'Signup must only redirect back to this site.';
    }

    return ['errors' => $errors, 'warnings' => []];
}

/**
 * The confirm link must render a page. Only a nonce-protected POST may subscribe.
 *
 * @return list<string>
 */
function audit_confirm_flow(): array
{
    $root = dirname(__DIR__);
    $public = (string) file_get_contents($root.'/includes/public.php');
    $mailer = (string) file_get_contents($root.'/includes/mailer.php');
    $errors = [];
    $get = source_function_body($public, 'on_template_redirect');
    if ($get === '' || str_contains($get, 'confirm_subscriber(')) {
        $errors[] = 'A GET on the confirm link must not confirm the subscription.';
    }
    $form = source_function_body($public, 'confirm_form_html');
    if (! str_contains($form, 'Confirm your subscription') || ! str_contains($form, '<button') || ! str_contains($form, 'wp_nonce_field(')) {
        $errors[] = 'The confirm page needs a heading, a button, and a nonce.';
    }
    if (! str_contains($public, 'focus-visible')) {
        $errors[] = 'The confirm button needs a visible focus style.';
    }
    $post = source_function_body($public, 'confirm_post_status');
    $nonceAt = strpos($post, 'wp_verify_nonce(');
    $confirmAt = strpos($post, 'confirm_subscriber(');
    if ($nonceAt === false || $confirmAt === false || $nonceAt > $confirmAt) {
        $errors[] = 'Confirmation must check the nonce before it confirms.';
    }
    $headers = source_function_body($public, 'confirm_page_headers');
    if (! str_contains($headers, 'DONOTCACHEPAGE') || ! str_contains($headers, 'send_privacy_headers(')) {
        $errors[] = 'The confirm page must send no-store headers and DONOTCACHEPAGE.';
    }
    if (! str_contains(source_function_body($public, 'send_privacy_headers'), 'nocache_headers()') || ! str_contains($public, 'Referrer-Policy: no-referrer')) {
        $errors[] = 'The confirm page must send Referrer-Policy and nocache headers.';
    }
    if (str_contains($mailer, 'One click confirms')) {
        $errors[] = 'The confirmation email must not say the link alone confirms.';
    }
    if (! str_contains(source_function_body($public, 'confirm_result_html'), 'role="status"')) {
        $errors[] = 'The confirm POST needs a success state.';
    }

    return $errors;
}

/**
 * @return array{errors: list<string>, warnings: list<string>}
 */
function audit_issue(int $issueId): array
{
    $message = issue_message($issueId, [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'status' => 'preview',
    ], true);
    $result = audit_html($message['html'], $message['text'], settings()['address']);

    $subject = trim((string) get_post_meta($issueId, '_mhn_subject', true));
    if ($subject === '') {
        $result['errors'][] = 'Add a subject line.';
    } elseif (mb_strlen($subject) > 60) {
        $result['warnings'][] = 'The subject is over 60 characters, so inboxes may cut it off.';
    }

    $preheader = trim((string) get_post_meta($issueId, '_mhn_preheader', true));
    if ($preheader === '') {
        $result['warnings'][] = 'Add preview text. Inboxes show it under the subject.';
    }

    $domain = from_domain_warning();
    if ($domain !== '') {
        $result['warnings'][] = $domain;
    }

    $result['errors'] = array_values(array_unique($result['errors']));
    $result['warnings'] = array_values(array_unique($result['warnings']));

    return $result;
}

function issue_send_blocked(int $issueId): bool
{
    return audit_issue($issueId)['errors'] !== [];
}

function from_domain_warning(): string
{
    $email = settings()['from_email'];
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $at = strrchr($email, '@');
    $fromHost = is_string($at) ? substr($at, 1) : '';
    if (! is_string($host) || $host === '' || $fromHost === '') {
        return '';
    }

    $site = strtolower(preg_replace('/^www\./', '', $host) ?? $host);
    $from = strtolower(preg_replace('/^www\./', '', $fromHost) ?? $fromHost);
    if ($site === $from) {
        return '';
    }

    return __('The From address is not on this site\'s domain. Use a mailbox on your domain before a real send.', 'matthummel-newsletter');
}

function render_issue_audit(int $issueId): void
{
    if ($issueId < 1 || ! function_exists('get_post')) {
        return;
    }

    $audit = audit_issue($issueId);
    foreach ($audit['errors'] as $line) {
        echo '<div class="notice notice-error"><p>'.esc_html($line).'</p></div>';
    }
    foreach ($audit['warnings'] as $line) {
        echo '<div class="notice notice-warning"><p>'.esc_html($line).'</p></div>';
    }
}
