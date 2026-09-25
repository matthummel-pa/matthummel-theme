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

function apply_layout(string $layoutId, string $body): string
{
    $layoutId = normalize_layout_id($layoutId);
    $copy = layout_copy();
    if ($layoutId === 'welcome') {
        $html = layout_text_html($copy['welcome_body']);
        if ($html === '') {
            $html = layout_text_html(layout_copy_defaults()['welcome_body']);
        }

        return $html;
    }

    $letter = layout_text_html($copy['intro']).$body.layout_signoff_html($copy['signoff'], $copy['from_name']);
    if ($layoutId === 'plain') {
        return strip_layout_images($letter);
    }
    if ($layoutId === 'feature') {
        return arrange_feature_body($letter);
    }

    return $letter;
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
    $html = email_document($subject, $preheader, $body, false, 0);
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

function layout_text_html(string $text): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return '';
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
        $html .= '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#141c28;text-align:left;">'.implode('<br>', $lines).'</p>';
    }

    return $html;
}

function layout_signoff_html(string $signoff, string $fromName): string
{
    $html = layout_text_html($signoff);
    $fromName = trim($fromName);
    if ($fromName === '') {
        return $html;
    }

    return $html.layout_text_html($fromName);
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
    $heading = '<h1 class="mhn-text" style="margin:0 0 12px;font-size:28px;line-height:1.3;font-weight:700;color:#0d2e57;text-align:left;">'.esc_html($title).'</h1>';
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
        $note = sanitize_rich_text($raw);
    }

    return emulator_view($issueId, $layoutId, $subject, $note);
}

/**
 * @return array{from_name: string, from_email: string, subject: string, preheader: string, html: string, layout: string}
 */
function emulator_view(int $issueId, string $layoutId, string $subject = '', ?string $note = null): array
{
    $settings = settings();
    $layoutId = normalize_layout_id($layoutId !== '' ? $layoutId : ($issueId > 0 ? issue_layout_id($issueId) : 'standard'));
    $subject = emulator_subject($issueId, $layoutId, $subject);
    $preheader = emulator_preheader();
    $html = emulator_letter_html($issueId, $layoutId, $subject, $note, $preheader);

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

function emulator_preheader(): string
{
    $intro = trim(layout_copy()['intro']);
    if ($intro !== '') {
        return mb_substr($intro, 0, 140);
    }

    return mb_substr(trim(wp_strip_all_tags(layout_copy()['welcome_body'])), 0, 140);
}

function emulator_letter_html(int $issueId, string $layoutId, string $subject, ?string $note, string $preheader): string
{
    $rendered = '';
    if ($layoutId !== 'welcome') {
        $blocks = emulator_blocks($issueId, $note);
        $rendered = $blocks !== '' ? render_blocks($blocks, $subject) : sample_issue_body();
    }
    $body = apply_layout($layoutId, $rendered);
    if ($preheader === '') {
        $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    }
    $html = email_document($subject, $preheader, $body, false, 0);
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
