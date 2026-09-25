<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param  array<string, string>|null  $subscriber
 * @return array{subject: string, html: string, text: string}
 */
function issue_message(int $issueId, ?array $subscriber, bool $preview = false): array
{
    $post = get_post($issueId);
    $subject = trim((string) get_post_meta($issueId, '_mhn_subject', true));
    if ($subject === '' && $post instanceof \WP_Post) {
        $subject = $post->post_title;
    }
    if ($subject === '') {
        $subject = (string) get_bloginfo('name');
    }

    $preheader = trim((string) get_post_meta($issueId, '_mhn_preheader', true));
    $content = $post instanceof \WP_Post ? (string) $post->post_content : '';
    $altFallback = $post instanceof \WP_Post ? $post->post_title : $subject;
    $body = render_blocks($content, $altFallback);
    $includeRecent = (string) get_post_meta($issueId, '_mhn_include_recent', true) === '1';
    $sourceId = (int) get_post_meta($issueId, '_mhn_source_post', true);

    if ($preheader === '') {
        $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    }

    $html = email_document($subject, $preheader, $body, $includeRecent, $sourceId);
    $sample = $subscriber ?? [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'status' => 'preview',
    ];

    $html = apply_merge($html, $sample, $issueId, true);
    $text = apply_merge(plain_text($html), $sample, $issueId, false);

    if (! $preview && $subscriber && (int) ($subscriber['id'] ?? 0) > 0) {
        $html = apply_tracking($html, $subscriber, $issueId);
    }

    return [
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];
}

function email_document(string $subject, string $preheader, string $body, bool $includeRecent, int $excludePostId): string
{
    $settings = settings();
    $name = $settings['from_name'] !== '' ? $settings['from_name'] : (string) get_bloginfo('name');
    $home = home_url('/');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $icon = get_site_icon_url(96);
    $logo = '';
    if (is_string($icon) && $icon !== '') {
        $logo = '<img src="'.esc_url($icon).'" width="40" height="40" alt="" style="display:block;border:0;border-radius:4px;margin:0 12px 0 0;">';
    }

    $pad = str_repeat('&nbsp;&zwnj;', 24);
    $recent = $includeRecent ? recent_posts_html($excludePostId, $font) : '';
    $address = nl2br(esc_html($settings['address']));
    $site = wp_parse_url(home_url(), PHP_URL_HOST);
    $site = is_string($site) && $site !== '' ? $site : 'matthummel.com';

    $styles = '<style>'
        .':root{color-scheme:light dark;supported-color-schemes:light dark;}'
        .'body,table,td{margin:0;padding:0;}'
        .'img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;}'
        .'a{color:#0d2e57;}'
        .'.mhn-preheader{display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;}'
        .'@media only screen and (max-width:620px){'
        .'.mhn-shell{width:100%!important;}'
        .'.mhn-px{padding-left:20px!important;padding-right:20px!important;}'
        .'.mhn-col{display:block!important;width:100%!important;max-width:100%!important;}'
        .'.mhn-btn{width:100%!important;}'
        .'.mhn-img{width:100%!important;height:auto!important;}'
        .'}'
        .'@media (prefers-color-scheme:dark){'
        .'.mhn-page,.mhn-page-td{background:#0b1220!important;}'
        .'.mhn-card{background:#162033!important;}'
        .'.mhn-text,.mhn-text p,.mhn-text li,.mhn-text h2,.mhn-text h3{color:#f7f9fc!important;}'
        .'.mhn-muted,.mhn-muted p,.mhn-footer,.mhn-footer p,.mhn-footer a{color:#b7c3d4!important;}'
        .'.mhn-header{background:#0d2e57!important;}'
        .'.mhn-rule{border-color:#2a3b52!important;}'
        .'.mhn-group{background:#0f1b2d!important;}'
        .'}'
        .'[data-ogsc] .mhn-card{background:#162033!important;}'
        .'[data-ogsc] .mhn-text,[data-ogsc] .mhn-text p{color:#f7f9fc!important;}'
        .'</style>';

    $header = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        .'<td class="mhn-header mhn-px" style="background-color:#0d2e57;padding:22px 40px;">'
        .'<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
        .($logo !== '' ? '<td style="vertical-align:middle;">'.$logo.'</td>' : '')
        .'<td style="vertical-align:middle;">'
        .'<a href="'.esc_url($home).'" style="color:#ffffff;font-family:'.$font.';font-size:18px;font-weight:700;text-decoration:none;">'.esc_html($name).'</a>'
        .'</td></tr></table></td></tr></table>';

    $footer = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        .'<td class="mhn-footer mhn-muted mhn-px" style="padding:8px 40px 28px;font-family:'.$font.';font-size:13px;line-height:1.5;color:#3a4554;">'
        .'<p style="margin:0 0 8px;">'.esc_html(sprintf(
            /* translators: %s: site host */
            __('You got this because you signed up at %s. I keep your address on this site.', 'matthummel-newsletter'),
            $site
        )).'</p>'
        .'<p style="margin:0 0 8px;"><a href="*|UNSUB|*" style="color:#0d2e57;text-decoration:underline;">'.esc_html__('Unsubscribe', 'matthummel-newsletter').'</a>'
        .' · <a href="*|PREFERENCES|*" style="color:#0d2e57;text-decoration:underline;">'.esc_html__('Manage preferences', 'matthummel-newsletter').'</a>'
        .' · <a href="*|ARCHIVE|*" style="color:#0d2e57;text-decoration:underline;">'.esc_html__('View in browser', 'matthummel-newsletter').'</a></p>'
        .'<p style="margin:0 0 8px;">'.$address.'</p>'
        .'<p style="margin:0;">© *|CURRENT_YEAR|* '.esc_html($name).'</p>'
        .'</td></tr></table>';

    return '<!DOCTYPE html><html lang="'.esc_attr(get_bloginfo('language') ?: 'en').'" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
        .'<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta http-equiv="X-UA-Compatible" content="IE=edge">'
        .'<meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">'
        .'<title>'.esc_html($subject).'</title>'
        .'<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->'
        .$styles
        .'</head>'
        .'<body class="mhn-page" style="margin:0;padding:0;background-color:#eef3f9;">'
        .'<div class="mhn-preheader" style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
        .esc_html($preheader).$pad.'</div>'
        .'<table role="presentation" class="mhn-page" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef3f9;">'
        .'<tr><td class="mhn-page-td" align="center" style="padding:24px 12px;background-color:#eef3f9;">'
        .'<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->'
        .'<table role="presentation" class="mhn-shell" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;">'
        .'<tr><td class="mhn-card" style="background-color:#ffffff;">'
        .$header
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        .'<td class="mhn-text mhn-px" style="padding:28px 40px 8px;font-family:'.$font.';font-size:16px;line-height:1.6;color:#141c28;">'
        .$body
        .'</td></tr></table>'
        .$recent
        .$footer
        .'</td></tr></table>'
        .'<!--[if mso]></td></tr></table><![endif]-->'
        .'</td></tr></table></body></html>';
}

function recent_posts_html(int $excludePostId, string $font): string
{
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'post__not_in' => $excludePostId > 0 ? [$excludePostId] : [],
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    if (! is_array($posts) || $posts === []) {
        return '';
    }

    $items = '';
    foreach ($posts as $post) {
        if (! $post instanceof \WP_Post) {
            continue;
        }
        $url = get_permalink($post);
        if (! is_string($url) || $url === '') {
            continue;
        }
        $items .= '<li style="margin:0 0 8px;"><a href="'.esc_url($url).'" style="color:#0d2e57;text-decoration:underline;">'.esc_html(get_the_title($post)).'</a></li>';
    }

    if ($items === '') {
        return '';
    }

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        .'<td class="mhn-text mhn-px" style="padding:8px 40px 12px;font-family:'.$font.';font-size:16px;line-height:1.6;color:#141c28;">'
        .'<h2 class="mhn-text" style="margin:0 0 12px;font-size:18px;line-height:1.3;color:#0d2e57;">'.esc_html__('Recent writing', 'matthummel-newsletter').'</h2>'
        .'<ul style="margin:0 0 8px;padding-left:20px;">'.$items.'</ul>'
        .'</td></tr></table>';
}

/**
 * @param  array<string, string>  $subscriber
 */
function apply_merge(string $value, array $subscriber, int $issueId, bool $html): string
{
    $first = trim((string) ($subscriber['first_name'] ?? ''));
    if ($first === '') {
        $first = __('there', 'matthummel-newsletter');
    }

    $email = (string) ($subscriber['email'] ?? '');
    $unsub = unsub_url($subscriber);
    $prefs = preferences_url($subscriber);
    $archive = archive_url($issueId, $subscriber);
    $year = wp_date('Y');

    $map = [
        '*|FNAME|*' => $html ? esc_html($first) : $first,
        '*|EMAIL|*' => $html ? esc_html($email) : $email,
        '*|UNSUB|*' => $html ? esc_url($unsub) : $unsub,
        '*|UPDATE_PROFILE|*' => $html ? esc_url($prefs) : $prefs,
        '*|PREFERENCES|*' => $html ? esc_url($prefs) : $prefs,
        '*|ARCHIVE|*' => $html ? esc_url($archive) : $archive,
        '*|CURRENT_YEAR|*' => $html ? esc_html($year) : $year,
    ];

    return strtr($value, $map);
}

/**
 * @param  array<string, string>  $subscriber
 */
function apply_tracking(string $html, array $subscriber, int $issueId): string
{
    $config = settings();
    if ($config['track_clicks'] === 1) {
        $rewritten = preg_replace_callback(
            '/href=(["\'])(https?:\/\/[^"\']+)\1/i',
            static function (array $match) use ($subscriber, $issueId): string {
                $url = html_entity_decode($match[2], ENT_QUOTES);
                if (preg_match('/[?&]mhn_(?:unsub|token|confirm|view|prefs)=/', $url) === 1) {
                    return $match[0];
                }
                $tracked = click_url($issueId, $subscriber, $url);

                return 'href='.$match[1].esc_url($tracked).$match[1];
            },
            $html
        );
        if (is_string($rewritten)) {
            $html = $rewritten;
        }
    }

    if ($config['track_opens'] === 1) {
        $pixel = '<img src="'.esc_url(open_url($issueId, $subscriber)).'" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;">';
        $html = str_replace('</body>', $pixel.'</body>', $html);
    }

    return $html;
}

function plain_text(string $html): string
{
    $text = preg_replace('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $html) ?? $html;
    $text = preg_replace('/<(br|\/p|\/h\d|\/li|\/tr)\b[^>]*>/i', "\n", $text) ?? $text;
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

/**
 * @param  array<string, string>  $subscriber
 */
function unsub_url(array $subscriber): string
{
    if ((int) ($subscriber['id'] ?? 0) < 1) {
        return add_query_arg('mhn_test', '1', page_url('email-preferences'));
    }

    return add_query_arg([
        'mhn_unsub' => (int) $subscriber['id'],
        'mhn_token' => subscriber_token($subscriber, 'manage'),
    ], home_url('/'));
}

/**
 * @param  array<string, string>  $subscriber
 */
function preferences_url(array $subscriber): string
{
    if ((int) ($subscriber['id'] ?? 0) < 1) {
        return add_query_arg('mhn_test', '1', page_url('email-preferences'));
    }

    return add_query_arg([
        'mhn_sid' => (int) $subscriber['id'],
        'mhn_token' => subscriber_token($subscriber, 'manage'),
    ], page_url('email-preferences'));
}

/**
 * @param  array<string, string>  $subscriber
 */
function archive_url(int $issueId, array $subscriber): string
{
    $args = [
        'mhn_view' => $issueId,
        'k' => preview_key($issueId),
    ];
    if ((int) ($subscriber['id'] ?? 0) > 0) {
        $args['mhn_sid'] = (int) $subscriber['id'];
        $args['mhn_st'] = subscriber_token($subscriber, 'manage');
    }

    return add_query_arg($args, home_url('/'));
}

/**
 * @param  array<string, string>  $subscriber
 */
function open_url(int $issueId, array $subscriber): string
{
    return add_query_arg([
        'mhn_open' => $issueId,
        'mhn_sid' => (int) $subscriber['id'],
        'mhn_st' => subscriber_token($subscriber, 'manage'),
    ], home_url('/'));
}

/**
 * @param  array<string, string>  $subscriber
 */
function click_url(int $issueId, array $subscriber, string $target): string
{
    return add_query_arg([
        'mhn_click' => $issueId,
        'mhn_sid' => (int) $subscriber['id'],
        'mhn_st' => subscriber_token($subscriber, 'manage'),
        'mhn_u' => base64_encode($target),
    ], home_url('/'));
}

function preview_key(int $issueId): string
{
    return hash_hmac('sha256', 'mhn-view|'.$issueId, wp_salt('auth'));
}

function confirm_url(string $rawToken): string
{
    return add_query_arg('mhn_confirm', $rawToken, home_url('/'));
}
