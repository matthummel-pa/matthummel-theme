<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Letter layouts. The content template still chooses the posts.
 * The layout chooses how that letter is arranged.
 *
 * @return array<string, array{label: string, summary: string}>
 */
function layouts(): array
{
    return [
        'standard' => [
            'label' => __('Standard', 'matthummel-newsletter'),
            'summary' => __('The usual letter, with your intro, the issue, and your sign-off.', 'matthummel-newsletter'),
        ],
        'welcome' => [
            'label' => __('Welcome', 'matthummel-newsletter'),
            'summary' => __('A short letter for someone who just joined the list.', 'matthummel-newsletter'),
        ],
        'plain' => [
            'label' => __('Plain', 'matthummel-newsletter'),
            'summary' => __('The same letter as text only, with no featured image.', 'matthummel-newsletter'),
        ],
        'feature' => [
            'label' => __('Feature', 'matthummel-newsletter'),
            'summary' => __('The featured image on top, then the title and the body.', 'matthummel-newsletter'),
        ],
    ];
}

function normalize_layout_id(string $layoutId): string
{
    $layoutId = sanitize_key($layoutId);

    return isset(layouts()[$layoutId]) ? $layoutId : 'standard';
}

function issue_layout_id(int $issueId): string
{
    if ($issueId < 1) {
        return 'standard';
    }

    return normalize_layout_id((string) get_post_meta($issueId, '_mhn_layout', true));
}

function dashboard_layout_id(): string
{
    return normalize_layout_id((string) get_option('mhn_last_layout', 'standard'));
}

function remember_layout(string $layoutId): void
{
    update_option('mhn_last_layout', normalize_layout_id($layoutId), false);
}

/**
 * @return array{intro: string, signoff: string, welcome_subject: string, welcome_body: string, from_name: string}
 */
function layout_copy(): array
{
    $settings = settings();

    return [
        'intro' => $settings['intro'],
        'signoff' => $settings['signoff'],
        'welcome_subject' => $settings['welcome_subject'],
        'welcome_body' => $settings['welcome_body'],
        'from_name' => $settings['from_name'],
    ];
}

function layout_subject(int $issueId, string $subject): string
{
    if (issue_layout_id($issueId) !== 'welcome') {
        return $subject;
    }
    if ((string) get_post_meta($issueId, '_mhn_subject_auto', true) === '0' && trim($subject) !== '') {
        return $subject;
    }

    $welcome = layout_copy()['welcome_subject'];

    return $welcome !== '' ? $welcome : $subject;
}

/**
 * @return array{intro: string, heading: string, body: string, button_label: string, button_url: string, signoff: string}
 */
function empty_editor_blocks(): array
{
    return [
        'intro' => '',
        'heading' => '',
        'body' => '',
        'button_label' => '',
        'button_url' => '',
        'signoff' => '',
    ];
}

function normalize_editor_mode(string $mode): string
{
    return $mode === 'advanced' ? 'advanced' : 'simple';
}

function issue_editor_mode(int $issueId): string
{
    if ($issueId < 1 || ! function_exists('get_post_meta')) {
        return 'simple';
    }

    return normalize_editor_mode((string) get_post_meta($issueId, '_mhn_editor', true));
}

/**
 * @param  array<string, mixed>  $input
 */
function advanced_fields_posted(array $input): bool
{
    foreach (['mhn_block_intro', 'mhn_block_heading', 'mhn_block_body', 'mhn_block_button_label', 'mhn_block_button_url', 'mhn_block_signoff'] as $key) {
        if (array_key_exists($key, $input)) {
            return true;
        }
    }

    return false;
}

/**
 * @param  array<string, mixed>  $input
 * @return array{intro: string, heading: string, body: string, button_label: string, button_url: string, signoff: string}
 */
function sanitize_editor_blocks(array $input): array
{
    $body = (string) ($input['mhn_block_body'] ?? $input['body'] ?? '');
    if (strlen($body) > 20000) {
        $body = substr($body, 0, 20000);
    }
    $url = function_exists('esc_url_raw') ? esc_url_raw((string) ($input['mhn_block_button_url'] ?? $input['button_url'] ?? '')) : trim((string) ($input['mhn_block_button_url'] ?? $input['button_url'] ?? ''));

    return [
        'intro' => mb_substr(sanitize_text_field((string) ($input['mhn_block_intro'] ?? $input['intro'] ?? '')), 0, 200),
        'heading' => mb_substr(sanitize_text_field((string) ($input['mhn_block_heading'] ?? $input['heading'] ?? '')), 0, 200),
        'body' => function_exists('sanitize_rich_text') ? sanitize_rich_text($body) : trim(wp_strip_all_tags($body)),
        'button_label' => mb_substr(sanitize_text_field((string) ($input['mhn_block_button_label'] ?? $input['button_label'] ?? '')), 0, 120),
        'button_url' => is_string($url) ? $url : '',
        'signoff' => mb_substr(sanitize_text_field((string) ($input['mhn_block_signoff'] ?? $input['signoff'] ?? '')), 0, 200),
    ];
}

/**
 * @return array{intro: string, heading: string, body: string, button_label: string, button_url: string, signoff: string}
 */
function issue_blocks(int $issueId): array
{
    $saved = ($issueId > 0 && function_exists('get_post_meta')) ? get_post_meta($issueId, '_mhn_blocks', true) : [];

    return sanitize_editor_blocks(is_array($saved) ? $saved : []);
}

/**
 * @return array{intro: string, heading: string, body: string, signoff: string}
 */
function advanced_block_defaults(string $layoutId): array
{
    $copy = layout_copy();
    if (normalize_layout_id($layoutId) === 'welcome') {
        return [
            'intro' => $copy['intro'],
            'heading' => $copy['welcome_subject'],
            'body' => $copy['welcome_body'],
            'signoff' => $copy['signoff'],
        ];
    }

    return [
        'intro' => $copy['intro'],
        'heading' => '',
        'body' => '',
        'signoff' => $copy['signoff'],
    ];
}

function apply_issue_layout(int $issueId, string $body): string
{
    $layoutId = $issueId > 0 ? issue_layout_id($issueId) : 'standard';
    if (issue_editor_mode($issueId) !== 'advanced') {
        return apply_layout($layoutId, $body);
    }

    return compose_advanced_letter(issue_blocks($issueId), $layoutId, $body, settings()['from_name']);
}

/**
 * @param  array<string, mixed>  $blocks
 */
function compose_advanced_letter(array $blocks, string $layoutId, string $issueHtml, string $fromName): string
{
    $layoutId = normalize_layout_id($layoutId);
    $defaults = advanced_block_defaults($layoutId);
    $intro = trim((string) ($blocks['intro'] ?? ''));
    $heading = trim((string) ($blocks['heading'] ?? ''));
    $body = trim((string) ($blocks['body'] ?? ''));
    $signoff = trim((string) ($blocks['signoff'] ?? ''));
    $intro = $intro !== '' ? $intro : $defaults['intro'];
    $heading = $heading !== '' ? $heading : $defaults['heading'];
    $signoff = $signoff !== '' ? $signoff : $defaults['signoff'];

    $image = '';
    if ($layoutId === 'feature' || $layoutId === 'standard') {
        $image = extract_layout_image($issueHtml);
    }
    if ($body !== '') {
        $bodyHtml = advanced_copy_html($body);
    } elseif ($defaults['body'] !== '') {
        $bodyHtml = advanced_copy_html($defaults['body']);
    } else {
        $bodyHtml = $image !== '' ? str_replace($image, '', $issueHtml) : $issueHtml;
        if ($heading !== '') {
            $stripped = preg_replace('/<h1\b[^>]*>.*?<\/h1>/is', '', $bodyHtml, 1);
            if (is_string($stripped)) {
                $bodyHtml = $stripped;
            }
        }
    }

    $button = '';
    $label = trim((string) ($blocks['button_label'] ?? ''));
    $url = trim((string) ($blocks['button_url'] ?? ''));
    if ($label !== '' && $url !== '') {
        $button = letter_button($label, $url);
    }

    $headingHtml = letter_title_html($heading, $layoutId === 'plain');
    $introHtml = layout_eyebrow_html($intro, $layoutId);
    $signoffHtml = layout_signoff_html($signoff, $fromName);
    if ($layoutId === 'feature') {
        return present_feature_image($image).$headingHtml.$introHtml.$bodyHtml.$button.$signoffHtml;
    }
    if ($layoutId === 'plain' || $layoutId === 'welcome') {
        return $introHtml.$headingHtml.$bodyHtml.$button.$signoffHtml;
    }

    return $introHtml.$headingHtml.$image.$bodyHtml.$button.$signoffHtml;
}

function extract_layout_image(string $html): string
{
    $pattern = '/<a\b[^>]*>\s*<img\b[^>]*\bmhn-img\b[^>]*>\s*<\/a>|<img\b[^>]*\bmhn-img\b[^>]*>/i';
    if (preg_match($pattern, $html, $match) !== 1) {
        return '';
    }

    return $match[0];
}

function advanced_heading_html(string $heading): string
{
    return letter_title_html($heading);
}

function advanced_copy_html(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if (! str_contains($text, '<')) {
        return layout_text_html($text);
    }

    $clean = email_kses($text);
    if (! str_contains($clean, '<p')) {
        $clean = '<p>'.$clean.'</p>';
    }
    $styled = preg_replace(
        '/<p(\s|>)/',
        '<p style="'.body_paragraph_style().'"$1',
        $clean
    );

    return decorate_links(is_string($styled) ? $styled : $clean);
}

function apply_layout(string $layoutId, string $body): string
{
    $layoutId = normalize_layout_id($layoutId);
    $copy = layout_copy();
    if ($layoutId === 'welcome') {
        $html = layout_text_html($copy['welcome_body'], true);
        if ($html === '') {
            $html = layout_text_html(layout_copy_defaults()['welcome_body'], true);
        }

        return $html;
    }

    if ($layoutId === 'plain') {
        $body = strip_layout_images($body);
    }

    $image = '';
    if ($layoutId === 'feature') {
        $image = extract_layout_image($body);
        if ($image !== '') {
            $body = remove_layout_image($body);
        }
        $image = present_feature_image($image);
    }

    $heading = take_letter_heading($body, $layoutId === 'plain');
    $intro = layout_eyebrow_html($copy['intro'], $layoutId);
    $signoff = layout_signoff_html($copy['signoff'], $copy['from_name']);
    if ($layoutId === 'feature') {
        return $image.$heading.$intro.$body.$signoff;
    }

    return $intro.$heading.$body.$signoff;
}

/**
 * Sample letter for the dashboard and the layout picker.
 * Links are replaced with # so the preview cannot call the tracker.
 */
function layout_preview_html(string $layoutId): string
{
    $layoutId = normalize_layout_id($layoutId);
    $copy = layout_copy();
    $subject = $layoutId === 'welcome' ? $copy['welcome_subject'] : __('A note from the workshop', 'matthummel-newsletter');
    $body = apply_layout($layoutId, sample_issue_body());
    $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    $html = email_document($subject, $preheader, $body, false, 0, $layoutId);
    $html = apply_merge($html, [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'last_name' => '',
        'status' => 'preview',
    ], 0, true);

    return placeholder_preview_links($html);
}

function layout_preview_admin_url(string $layoutId): string
{
    $layoutId = normalize_layout_id($layoutId);

    return wp_nonce_url(
        admin_url('admin-post.php?action=mhn_layout_preview&layout='.$layoutId),
        'mhn_layout_preview_'.$layoutId
    );
}

function handle_layout_preview(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }

    $layoutId = isset($_GET['layout']) ? sanitize_key(wp_unslash($_GET['layout'])) : 'standard';
    $layoutId = normalize_layout_id($layoutId);
    check_admin_referer('mhn_layout_preview_'.$layoutId);
    nocache_headers();
    header('Content-Type: text/html; charset='.get_bloginfo('charset'));
    header('X-Robots-Tag: noindex, nofollow', true);
    echo layout_preview_html($layoutId);
    exit;
}

function layout_text_html(string $text, bool $compact = false): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return '';
    }

    $style = body_paragraph_style();
    if ($compact) {
        $style = 'margin:0 0 12px;font-size:16px;line-height:1.6;color:#0b1220;text-align:left;';
    }

    $html = '';
    $chunks = preg_split("/\n{2,}/", $text) ?: [];
    foreach ($chunks as $chunk) {
        $chunk = trim((string) $chunk);
        if ($chunk === '') {
            continue;
        }
        $lines = [];
        foreach (explode("\n", $chunk) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $lines[] = esc_html($line);
        }
        if ($lines === []) {
            continue;
        }
        $html .= '<p style="'.$style.'">'.implode('<br>', $lines).'</p>';
    }

    return $html;
}

/**
 * Reusable intro as a short line above the title. Plain uses a navy rule instead of a headline.
 */
function layout_eyebrow_html(string $text, string $layoutId): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return '';
    }

    $lines = [];
    foreach (preg_split("/\n+/", $text) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $lines[] = esc_html($line);
        }
    }
    if ($lines === []) {
        return '';
    }

    $inner = implode('<br>', $lines);
    $font = email_font_stack();
    if ($layoutId === 'plain') {
        return '<table role="presentation" class="mhn-eyebrow-block" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;"><tr>'
            .'<td class="mhn-eyebrow" style="border-left:4px solid #0d2e57;padding:4px 0 4px 16px;font-family:'.$font.';font-size:18px;line-height:1.45;font-weight:600;color:#50575e;text-align:left;">'.$inner.'</td>'
            .'</tr></table>';
    }
    if ($layoutId === 'welcome') {
        return '<p class="mhn-eyebrow" style="margin:0 0 8px;font-family:'.$font.';font-size:16px;line-height:1.5;font-weight:600;color:#50575e;text-align:left;">'.$inner.'</p>';
    }

    return '<p class="mhn-eyebrow" style="margin:0 0 8px;font-family:'.$font.';font-size:16px;line-height:1.5;font-weight:600;color:#50575e;text-align:left;">'.$inner.'</p>';
}

function letter_button(string $label, string $url): string
{
    if (trim($label) === '' || trim($url) === '') {
        return '';
    }

    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px;"><tr><td>'
        .bulletproof_button($label, $url)
        .'</td></tr></table>';
}

function present_feature_image(string $image): string
{
    $image = trim($image);
    if ($image === '') {
        return '';
    }

    $image = str_replace('margin:0 0 16px', 'margin:0', $image);

    return '<div class="mhn-hero" style="margin:0;padding:0;line-height:0;font-size:0;">'.$image.'</div>';
}

function take_letter_heading(string &$body, bool $plain): string
{
    if (preg_match('/<h1\b[^>]*>.*?<\/h1>/is', $body, $match) !== 1) {
        return '';
    }

    $body = str_replace($match[0], '', $body);
    $text = trim(wp_strip_all_tags($match[0]));

    return letter_title_html($text, $plain);
}

function remove_layout_image(string $html): string
{
    $pattern = '/<a\b[^>]*>\s*<img\b[^>]*\bmhn-img\b[^>]*>\s*<\/a>|<img\b[^>]*\bmhn-img\b[^>]*>/i';
    $replaced = preg_replace($pattern, '', $html, 1);

    return is_string($replaced) ? $replaced : $html;
}

function layout_signoff_html(string $signoff, string $fromName): string
{
    $html = layout_text_html($signoff);
    $fromName = trim($fromName);
    $name = '';
    if ($fromName !== '') {
        $name = '<p class="mhn-signoff-name" style="margin:0 0 8px;font-family:'.email_font_stack().';font-size:16px;line-height:1.6;font-weight:700;color:#0d2e57;text-align:left;">'.esc_html($fromName).'</p>';
    }
    if ($html === '' && $name === '') {
        return '';
    }

    return '<div class="mhn-signoff" style="margin-top:8px;">'.$html.$name.'</div>';
}

function strip_layout_images(string $html): string
{
    $stripped = preg_replace('/<a\b[^>]*>\s*<img\b[^>]*>\s*<\/a>/i', '', $html);
    $stripped = preg_replace('/<img\b[^>]*>/i', '', is_string($stripped) ? $stripped : $html);

    return is_string($stripped) ? $stripped : $html;
}

function arrange_feature_body(string $html): string
{
    $image = '';
    $pattern = '/<a\b[^>]*>\s*<img\b[^>]*\bmhn-img\b[^>]*>\s*<\/a>|<img\b[^>]*\bmhn-img\b[^>]*>/i';
    if (preg_match($pattern, $html, $match) === 1) {
        $image = $match[0];
        $replaced = preg_replace($pattern, '', $html, 1);
        if (is_string($replaced)) {
            $html = $replaced;
        }
    }

    $heading = '';
    if (preg_match('/<h1\b[^>]*>.*?<\/h1>/is', $html, $match) === 1) {
        $heading = $match[0];
        $html = str_replace($heading, '', $html);
    }

    return $image.$heading.$html;
}

function sample_issue_body(): string
{
    $title = __('A note from the workshop', 'matthummel-newsletter');
    $greeting = layout_text_html('Hi {first_name|there},');
    $excerpt = layout_text_html(__('A short note about the work I shipped this week.', 'matthummel-newsletter'));
    $heading = letter_title_html($title);
    $button = bulletproof_button(
        sprintf(
            /* translators: %s: sample issue title */
            __('Read more: %s', 'matthummel-newsletter'),
            $title
        ),
        'https://matthummel.com/notes/'
    );

    return $greeting.sample_feature_image().$heading.$excerpt.$button;
}

function sample_feature_image(): string
{
    if (! defined('MHN_FILE') || ! function_exists('plugins_url')) {
        return '';
    }

    $src = plugins_url('assets/preview-feature.png', MHN_FILE);
    $safe = esc_url($src);
    if ($safe === '') {
        return '';
    }

    return '<img class="mhn-img" src="'.$safe.'" alt="'.esc_attr(__('A wide navy panel', 'matthummel-newsletter')).'" width="600" height="220" style="display:block;width:100%;max-width:600px;height:auto;border:0;margin:0 0 16px;background-color:#eef3f9;">';
}

function placeholder_preview_links(string $html): string
{
    $rewritten = preg_replace('/\shref=(["\']).*?\1/i', ' href="#"', $html);
    $rewritten = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', is_string($rewritten) ? $rewritten : $html);

    return is_string($rewritten) ? $rewritten : $html;
}

/**
 * Inbox chrome plus a safe letter. Empty names resolve {first_name|there} to there.
 * Nothing is mailed and the tracker is not called.
 *
 * @param  array<string, mixed>  $input
 * @return array{from_name: string, from_email: string, subject: string, preheader: string, html: string, layout: string}
 */
function emulator_preview_message(array $input): array
{
    $issueId = function_exists('wizard_issue_id') ? wizard_issue_id((int) ($input['mhn_issue'] ?? 0)) : 0;
    $layoutId = normalize_layout_id((string) ($input['mhn_layout'] ?? ($issueId > 0 ? issue_layout_id($issueId) : 'standard')));
    $subject = mb_substr(sanitize_text_field((string) ($input['mhn_subject'] ?? '')), 0, 120);
    $note = null;
    if (array_key_exists('mhn_note', $input)) {
        $raw = (string) $input['mhn_note'];
        if (strlen($raw) > 20000) {
            $raw = substr($raw, 0, 20000);
        }
        $note = function_exists('sanitize_rich_text') ? sanitize_rich_text($raw) : trim(wp_strip_all_tags($raw));
    }
    $editor = array_key_exists('mhn_editor', $input)
        ? normalize_editor_mode((string) $input['mhn_editor'])
        : issue_editor_mode($issueId);
    $blocks = null;
    if ($editor === 'advanced') {
        $blocks = advanced_fields_posted($input) ? sanitize_editor_blocks($input) : issue_blocks($issueId);
    }

    return emulator_view($issueId, $layoutId, $subject, $note, $editor, $blocks);
}

/**
 * @param  array{intro: string, heading: string, body: string, button_label: string, button_url: string, signoff: string}|null  $blocks
 * @return array{from_name: string, from_email: string, subject: string, preheader: string, html: string, layout: string}
 */
function emulator_view(int $issueId, string $layoutId, string $subject = '', ?string $note = null, string $editor = '', ?array $blocks = null): array
{
    $settings = settings();
    $layoutId = normalize_layout_id($layoutId !== '' ? $layoutId : ($issueId > 0 ? issue_layout_id($issueId) : 'standard'));
    $editor = $editor !== '' ? normalize_editor_mode($editor) : issue_editor_mode($issueId);
    $subject = emulator_subject($issueId, $layoutId, $subject);
    $intro = '';
    if ($editor === 'advanced') {
        $blocks ??= issue_blocks($issueId);
        $intro = trim((string) ($blocks['intro'] ?? ''));
        if ($subject === __('A note from the workshop', 'matthummel-newsletter')) {
            $heading = trim((string) ($blocks['heading'] ?? ''));
            if ($heading !== '') {
                $subject = $heading;
            }
        }
    }
    $preheader = emulator_preheader($intro);
    $html = emulator_letter_html($issueId, $layoutId, $subject, $note, $preheader, $editor, $blocks);

    return [
        'from_name' => $settings['from_name'],
        'from_email' => $settings['from_email'],
        'subject' => $subject,
        'preheader' => $preheader,
        'html' => $html,
        'layout' => $layoutId,
    ];
}

function emulator_subject(int $issueId, string $layoutId, string $posted): string
{
    $posted = trim($posted);
    if ($posted !== '') {
        return $posted;
    }
    if ($layoutId === 'welcome') {
        if ($issueId > 0 && (string) get_post_meta($issueId, '_mhn_subject_auto', true) === '0') {
            $saved = trim((string) get_post_meta($issueId, '_mhn_subject', true));
            if ($saved !== '') {
                return $saved;
            }
        }
        $welcome = trim(layout_copy()['welcome_subject']);
        if ($welcome !== '') {
            return $welcome;
        }
    }
    if ($layoutId !== 'welcome' && $issueId > 0 && issue_layout_id($issueId) === 'welcome') {
        $suggested = trim(suggest_subject($issueId));
        if ($suggested !== '' && $suggested !== layout_copy()['welcome_subject']) {
            return $suggested;
        }
    }
    if ($issueId > 0) {
        $saved = trim((string) get_post_meta($issueId, '_mhn_subject', true));
        if ($saved !== '') {
            return $saved;
        }
    }

    return __('A note from the workshop', 'matthummel-newsletter');
}

function emulator_preheader(string $introOverride = ''): string
{
    $intro = trim($introOverride);
    if ($intro === '') {
        $intro = trim(layout_copy()['intro']);
    }
    if ($intro !== '') {
        return mb_substr($intro, 0, 140);
    }

    return mb_substr(trim(wp_strip_all_tags(layout_copy()['welcome_body'])), 0, 140);
}

/**
 * @param  array{intro: string, heading: string, body: string, button_label: string, button_url: string, signoff: string}|null  $blocks
 */
function emulator_letter_html(int $issueId, string $layoutId, string $subject, ?string $note, string $preheader, string $editor = 'simple', ?array $blocks = null): string
{
    $rendered = '';
    if ($layoutId !== 'welcome' || $editor === 'advanced') {
        $compiled = emulator_blocks($issueId, $note);
        $rendered = $compiled !== '' ? render_blocks($compiled, $subject) : sample_issue_body();
    }
    if ($editor === 'advanced') {
        $body = compose_advanced_letter($blocks ?? empty_editor_blocks(), $layoutId, $rendered, settings()['from_name']);
    } else {
        $body = apply_layout($layoutId, $rendered);
    }
    if ($preheader === '') {
        $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    }
    $html = email_document($subject, $preheader, $body, false, 0, $layoutId);
    $html = apply_merge($html, [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'last_name' => '',
        'status' => 'preview',
    ], 0, true);

    return placeholder_preview_links($html);
}

function emulator_blocks(int $issueId, ?string $note): string
{
    if ($issueId < 1) {
        return '';
    }

    $slug = (string) get_post_meta($issueId, '_mhn_template', true);
    $template = email_template($slug);
    if ($note !== null && $template !== null) {
        return template_content($issueId, $template, $note);
    }

    $post = get_post($issueId);

    return $post instanceof \WP_Post ? (string) $post->post_content : '';
}

/**
 * @param  array{from_name: string, from_email: string, subject: string, preheader: string, html: string, layout: string}  $view
 */
function render_email_emulator(array $view, bool $live, bool $compact): void
{
    $class = 'mhn-emulator'.($compact ? ' is-compact' : '');
    echo '<section class="'.esc_attr($class).'" data-mhn-emulator'.($live ? ' data-mhn-live="1"' : '').' aria-label="'.esc_attr__('Email preview', 'matthummel-newsletter').'">';
    echo '<div class="mhn-emulator-toolbar">';
    echo '<h2>'.esc_html__('Preview', 'matthummel-newsletter').'</h2>';
    echo '<div class="mhn-device" role="group" aria-label="'.esc_attr__('Preview width', 'matthummel-newsletter').'">';
    echo '<button type="button" data-mhn-device="desktop" aria-pressed="true">'.esc_html__('Desktop', 'matthummel-newsletter').'</button>';
    echo '<button type="button" data-mhn-device="mobile" aria-pressed="false">'.esc_html__('Mobile', 'matthummel-newsletter').'</button>';
    echo '</div></div>';
    echo '<div class="mhn-emulator-chrome">';
    echo '<p class="mhn-emulator-from"><span data-mhn-from-name>'.esc_html($view['from_name']).'</span> ';
    echo '&lt;<span class="mhn-emulator-email" data-mhn-from-email>'.esc_html($view['from_email']).'</span>&gt;</p>';
    echo '<p class="mhn-emulator-subject" data-mhn-subject-line>'.esc_html($view['subject']).'</p>';
    echo '<p class="mhn-emulator-preheader" data-mhn-preheader-line>'.esc_html($view['preheader']).'</p>';
    echo '</div>';
    echo '<div class="mhn-emulator-stage is-desktop" data-mhn-stage>';
    echo '<iframe data-mhn-emulator-frame title="'.esc_attr__('Email preview', 'matthummel-newsletter').'" sandbox="" srcdoc="'.esc_attr($view['html']).'"></iframe>';
    echo '</div></section>';
}

function ajax_emulator_preview(): void
{
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'forbidden'], 403);
    }
    check_ajax_referer('mhn_emulator', 'nonce');
    $message = emulator_preview_message(wp_unslash($_POST));
    wp_send_json_success($message);
}
