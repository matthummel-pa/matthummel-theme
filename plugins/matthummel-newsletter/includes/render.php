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
    $look = issue_letter_look($issueId);
    push_letter_look($look);

    try {
        return issue_message_body($issueId, $subscriber, $preview, $look);
    } finally {
        pop_letter_look();
    }
}

/**
 * @param  array<string, string>|null  $subscriber
 * @param  array{style: string, masthead: string, button: string}  $look
 * @return array{subject: string, html: string, text: string}
 */
function issue_message_body(int $issueId, ?array $subscriber, bool $preview, array $look): array
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
    $body = apply_issue_layout($issueId, $body);
    $subject = layout_subject($issueId, $subject);
    $includeRecent = (string) get_post_meta($issueId, '_mhn_include_recent', true) === '1';
    $sourceId = (int) get_post_meta($issueId, '_mhn_source_post', true);

    if ($preheader === '') {
        $preheader = suggest_preheader($issueId);
    }
    if ($preheader === '') {
        $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    }

    $sample = $subscriber ?? [
        'id' => '0',
        'email' => 'you@example.com',
        'first_name' => '',
        'last_name' => '',
        'status' => 'preview',
    ];
    if ($preview && (int) ($sample['id'] ?? 0) < 1) {
        if (trim((string) ($sample['first_name'] ?? '')) === '') {
            $sample['first_name'] = 'Ada';
        }
        if (trim((string) ($sample['last_name'] ?? '')) === '') {
            $sample['last_name'] = 'Lovelace';
        }
    }

    $document = email_document($subject, $preheader, $body, $includeRecent, $sourceId, issue_layout_id($issueId), $look);
    $html = apply_merge($document, $sample, $issueId, true);
    $text = apply_merge(plain_text($document), $sample, $issueId, false);
    $subject = apply_merge($subject, $sample, $issueId, false);
    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));

    if (! $preview && $subscriber && (int) ($subscriber['id'] ?? 0) > 0) {
        $html = apply_tracking($html, $subscriber, $issueId);
    }

    return [
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ];
}

function email_font_stack(?string $font = null): string
{
    $choices = letter_font_choices();
    $key = $font ?? current_letter_look()['font'];
    if (! isset($choices[$key])) {
        $key = 'sans';
    }

    return $choices[$key]['stack'];
}

function body_paragraph_style(): string
{
    return 'margin:0 0 16px;font-size:16px;line-height:1.6;color:#0b1220;text-align:left;';
}

/**
 * The only title in the letter. Size and alignment follow the layout.
 */
function letter_title_html(string $text, bool $plain = false, string $layoutId = ''): string
{
    $text = trim(wp_strip_all_tags($text));
    if ($text === '') {
        return '';
    }

    $layoutId = $layoutId !== '' ? sanitize_key($layoutId) : ($plain ? 'plain' : 'standard');
    $align = $layoutId === 'welcome' ? 'center' : 'left';
    [$size, $margin, $extra] = match ($layoutId) {
        'welcome' => ['26px', '0 0 22px', ''],
        'plain' => ['22px', '0 0 22px', 'letter-spacing:-0.02em;'],
        'feature' => ['30px', '0 0 14px', ''],
        'post' => ['26px', '8px 0 12px', ''],
        default => ['32px', '0 0 18px', 'letter-spacing:-0.02em;'],
    };

    return '<h1 class="mhn-text mhn-title" style="margin:'.$margin.';font-family:'.email_font_stack().';font-size:'.$size.';line-height:1.2;font-weight:700;'.$extra.'color:#0d2e57;text-align:'.$align.';">'.esc_html($text).'</h1>';
}

/**
 * Layout tweaks on top of Card, Banner, or Paper. They do not replace that shell.
 *
 * @param  array<string, mixed>  $chrome
 * @return array<string, mixed>
 */
function layout_letter_chrome(string $layoutKey, array $chrome): array
{
    if ($layoutKey === 'welcome') {
        $chrome['align'] = 'center';
        $chrome['masthead'] = 'center';
        foreach (['masthead_style', 'brand', 'kicker'] as $key) {
            $chrome[$key] = str_replace('text-align:left', 'text-align:center', (string) $chrome[$key]);
        }
        $chrome['masthead_style'] = (string) preg_replace('/padding:[^;]+;/', 'padding:36px 48px 18px;', (string) $chrome['masthead_style']);
        $chrome['show_stripe'] = false;
    }

    if ($layoutKey === 'plain') {
        $font = (string) ($chrome['font'] ?? email_font_stack());
        $align = (string) ($chrome['align'] ?? 'left');
        $chrome['show_stripe'] = false;
        $chrome['show_rule'] = false;
        if (($chrome['style'] ?? '') !== 'banner') {
            $chrome['masthead_style'] = 'padding:22px 32px 8px;background-color:#ffffff;font-family:'.$font.';text-align:'.$align.';';
        }
    }

    if ($layoutKey === 'standard') {
        $chrome['brand'] = str_replace('font-size:18px', 'font-size:15px', (string) $chrome['brand']);
    }

    if ($layoutKey === 'feature' || $layoutKey === 'post') {
        $chrome['show_stripe'] = $layoutKey === 'post' && ($chrome['show_stripe'] ?? false);
        $chrome['show_rule'] = false;
        $chrome['hero'] = 'padding:32px 32px 0;background-color:#ffffff;line-height:0;font-size:0;';
    }

    return $chrome;
}

/**
 * @param  array{style?: string, masthead?: string, button?: string}|null  $look
 */
function email_document(string $subject, string $preheader, string $body, bool $includeRecent, int $excludePostId, string $layoutId = '', ?array $look = null): string
{
    $settings = settings();
    $name = $settings['from_name'] !== '' ? $settings['from_name'] : (string) get_bloginfo('name');
    $layoutKey = sanitize_key($layoutId);
    $knownLayout = isset(layouts()[$layoutKey]);
    if (! $knownLayout) {
        $layoutKey = '';
    }
    $chrome = layout_letter_chrome($layoutKey, letter_chrome($look ?? current_letter_look()));
    $font = (string) $chrome['font'];

    $pad = '<span aria-hidden="true">'.str_repeat('&nbsp;&zwnj;', 24).'</span>';
    $recent = $includeRecent ? recent_posts_html($excludePostId, $font) : '';
    $address = nl2br(esc_html($settings['address']));
    $site = wp_parse_url(home_url(), PHP_URL_HOST);
    $site = is_string($site) && $site !== '' ? $site : 'matthummel.com';
    $lang = get_bloginfo('language');
    $lang = is_string($lang) && $lang !== '' ? $lang : 'en';
    $dir = is_rtl() ? 'rtl' : 'ltr';
    $hero = take_letter_hero($body);
    if ($layoutKey === '' && $hero !== '') {
        $layoutKey = 'feature';
    }
    $body = ensure_heading($body, $subject, $layoutKey === 'plain', $layoutKey);

    $styles = '<style>'.email_css().'</style>';

    $kicker = letter_kicker($layoutKey, $site);
    $padTop = match ($layoutKey) {
        'feature' => '32px',
        'post' => '32px',
        'plain' => '20px',
        'welcome' => '48px',
        default => '32px',
    };
    $padSide = match ($layoutKey) {
        'welcome' => '48px',
        default => '32px',
    };
    $padBottom = match ($layoutKey) {
        'welcome' => '40px',
        'plain' => '24px',
        default => '28px',
    };
    $bodyAlign = $layoutKey === 'welcome' ? 'center' : 'left';
    $lineHeight = $layoutKey === 'welcome' ? '1.7' : '1.6';
    $heroRow = $hero !== ''
        ? '<tr><td class="mhn-hero-cell" style="'.$chrome['hero'].'">'.$hero.'</td></tr>'
        : '';
    $headerImage = letter_header_image_html($settings);
    if ($headerImage !== '') {
        $chrome['stripe'] = str_replace('border-radius:16px 16px 0 0;', '', (string) $chrome['stripe']);
        $chrome['masthead_style'] = str_replace('border-radius:16px 16px 0 0;', '', (string) $chrome['masthead_style']);
    }
    $stripe = $chrome['show_stripe']
        ? '<tr><td class="mhn-stripe" style="'.$chrome['stripe'].'">&nbsp;</td></tr>'
        : '';
    $masthead = letter_masthead($name, $kicker, $chrome);
    $hairline = $chrome['show_rule'] ? letter_rule($chrome) : '';
    $headRows = match ($layoutKey) {
        'feature' => $masthead.$heroRow,
        'post' => $stripe.$masthead.$heroRow,
        'plain' => $masthead,
        'welcome' => $stripe.$masthead.$hairline,
        default => $stripe.$masthead.$hairline,
    };

    $footer = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        .'<td class="mhn-footer mhn-muted mhn-px" style="'.$chrome['footer'].'">'
        .letter_social_html($settings)
        .'<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#50575e;text-align:left;">'.esc_html(sprintf(
            /* translators: %s: site host */
            __('You got this because you signed up at %s. I keep your address on this site.', 'matthummel-newsletter'),
            $site
        )).'</p>'
        .'<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#50575e;text-align:left;"><a href="*|UNSUB|*" style="color:#50575e;text-decoration:underline;">'.esc_html__('Unsubscribe', 'matthummel-newsletter').'</a>'
        .' · <a href="*|PREFERENCES|*" style="color:#50575e;text-decoration:underline;">'.esc_html__('Manage preferences', 'matthummel-newsletter').'</a>'
        .' · <a href="*|ARCHIVE|*" style="color:#50575e;text-decoration:underline;">'.esc_html__('View in browser', 'matthummel-newsletter').'</a></p>'
        .'<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#50575e;text-align:left;">'.$address.'</p>'
        .'<p style="margin:0;font-size:13px;line-height:1.5;color:#50575e;text-align:left;">© *|CURRENT_YEAR|* '.esc_html($name).'</p>'
        .'</td></tr></table>';

    $styleKey = $chrome['style'];
    $mastheadKey = $layoutKey === 'welcome' ? 'center' : $chrome['masthead'];
    $layoutClass = $layoutKey !== '' ? ' mhn-layout-'.esc_attr($layoutKey) : '';
    $letterAttrs = ' data-mhn-style="'.esc_attr($styleKey).'" data-mhn-masthead="'.esc_attr($mastheadKey).'" data-mhn-button="'.esc_attr($chrome['button']).'" data-mhn-font="'.esc_attr((string) $chrome['font_key']).'" data-mhn-variant="'.esc_attr((string) $chrome['variant']).'"';
    if ($layoutKey !== '') {
        $letterAttrs .= ' data-mhn-layout="'.esc_attr($layoutKey).'"';
    }

    return '<!DOCTYPE html><html lang="'.esc_attr($lang).'" dir="'.esc_attr($dir).'" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
        .'<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta http-equiv="X-UA-Compatible" content="IE=edge">'
        .'<meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light only">'
        .'<title>'.esc_html($subject).'</title>'
        .'<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->'
        .$styles
        .'</head>'
        .'<body class="mhn-page mhn-letter mhn-style-'.esc_attr($styleKey).$layoutClass.'"'.$letterAttrs.' style="'.$chrome['page'].'">'
        .'<div class="mhn-preheader" style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
        .esc_html($preheader).$pad.'</div>'
        .'<table role="presentation" class="mhn-page" width="100%" cellpadding="0" cellspacing="0" border="0"'.$letterAttrs.' style="'.$chrome['page'].'">'
        .'<tr><td class="mhn-page-td" align="center" style="'.$chrome['page_td'].'">'
        .'<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->'
        .'<table role="presentation" class="mhn-shell mhn-frame" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.$chrome['frame'].'">'
        .'<tr><td style="'.$chrome['frame_td'].'">'
        .'<table role="presentation" class="mhn-card" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.$chrome['card'].'">'
        .$headerImage
        .$headRows
        .'<tr><td class="mhn-text mhn-px" lang="'.esc_attr($lang).'" dir="'.esc_attr($dir).'" style="padding:'.$padTop.' '.$padSide.' '.$padBottom.';background-color:#ffffff;font-family:'.$font.';font-size:16px;line-height:'.$lineHeight.';color:#0b1220;text-align:'.$bodyAlign.';">'
        .$body
        .'</td></tr>'
        .($recent !== '' ? '<tr><td>'.$recent.'</td></tr>' : '')
        .'<tr><td>'.$footer.'</td></tr>'
        .'</table></td></tr></table>'
        .'<!--[if mso]></td></tr></table><![endif]-->'
        .'</td></tr></table></body></html>';
}

/**
 * Brand image above the masthead. Omitted when the URL is empty.
 *
 * @param  array{header_image?: string, header_alt?: string}  $settings
 */
function letter_header_image_html(array $settings): string
{
    $url = sanitize_https_url((string) ($settings['header_image'] ?? ''));
    if ($url === '') {
        return '';
    }

    $alt = trim((string) ($settings['header_alt'] ?? ''));
    if ($alt === '') {
        $alt = 'Matt Hummel';
    }

    return '<tr><td class="mhn-header-image" style="padding:0;line-height:0;font-size:0;background-color:#ffffff;border-radius:16px 16px 0 0;">'
        .'<img class="mhn-img mhn-header-img" src="'.esc_url($url).'" alt="'.esc_attr($alt).'" width="600" height="200" style="display:block;width:100%;max-width:600px;height:auto;border:0;margin:0;border-radius:16px 16px 0 0;background-color:#eceff1;">'
        .'</td></tr>';
}

/**
 * Text links. Icon fonts do not survive most inboxes.
 *
 * @param  array<string, mixed>  $settings
 */
function letter_social_html(array $settings): string
{
    $links = [
        'social_site' => __('Site', 'matthummel-newsletter'),
        'social_github' => 'GitHub',
        'social_linkedin' => 'LinkedIn',
        'social_youtube' => 'YouTube',
        'social_instagram' => 'Instagram',
    ];
    $parts = [];
    foreach ($links as $key => $label) {
        $url = sanitize_https_url((string) ($settings[$key] ?? ''));
        if ($url === '') {
            continue;
        }
        $parts[] = '<a href="'.esc_url($url).'" style="color:#50575e;text-decoration:underline;">'.esc_html($label).'</a>';
    }
    if ($parts === []) {
        return '';
    }

    return '<p class="mhn-social" style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#50575e;text-align:left;">'.implode(' · ', $parts).'</p>';
}

function letter_kicker(string $layoutKey, string $site): string
{
    if ($layoutKey !== '' && isset(layouts()[$layoutKey])) {
        return (string) layouts()[$layoutKey]['label'];
    }

    return $site;
}

function letter_byline(string $name, string $kicker, string $font): string
{
    return '<p class="mhn-kicker mhn-muted" style="margin:0 0 18px;font-family:'.$font.';font-size:13px;line-height:1.4;color:#50575e;text-align:left;">'
        .'<span class="mhn-brand" style="font-size:16px;line-height:1.4;font-weight:700;color:#0d2e57;">'.esc_html($name).'</span>'
        .' · '.esc_html($kicker)
        .'</p>';
}

/**
 * @param  array{align: string, masthead_style: string, brand: string, kicker: string}  $chrome
 */
function letter_masthead(string $name, string $kicker, array $chrome): string
{
    $align = $chrome['align'] === 'center' ? 'center' : 'left';

    return '<tr><td class="mhn-masthead mhn-px" align="'.$align.'" style="'.$chrome['masthead_style'].'">'
        .'<div class="mhn-brand" style="'.$chrome['brand'].'">'.esc_html($name).'</div>'
        .'<div class="mhn-kicker mhn-muted" style="'.$chrome['kicker'].'">'.esc_html($kicker).'</div>'
        .'</td></tr>';
}

/**
 * @param  array{rule: string}  $chrome
 */
function letter_rule(array $chrome): string
{
    if ($chrome['rule'] === '') {
        return '';
    }

    return '<tr><td class="mhn-hairline mhn-rule mhn-px" style="padding:0 32px;font-size:0;line-height:0;">'
        .'<div style="'.$chrome['rule'].'font-size:0;line-height:0;">&nbsp;</div>'
        .'</td></tr>';
}

function letter_hairline(): string
{
    return letter_rule(letter_chrome(['style' => 'card']));
}

/**
 * Pull a full-bleed feature image out of the letter so the title can sit under it.
 */
function take_letter_hero(string &$body): string
{
    if (preg_match('/<div class="mhn-hero\b[^>]*>.*?<\/div>/is', $body, $match) !== 1) {
        return '';
    }

    $body = str_replace($match[0], '', $body);

    return $match[0];
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
        .'<td class="mhn-text mhn-px" style="padding:8px 32px 12px;font-family:'.$font.';font-size:16px;line-height:1.6;color:#0b1220;text-align:left;">'
        .'<h2 class="mhn-text" style="margin:0 0 12px;font-size:18px;line-height:1.3;color:#0d2e57;">'.esc_html__('Recent writing', 'matthummel-newsletter').'</h2>'
        .'<ul style="margin:0 0 8px;padding-left:20px;">'.$items.'</ul>'
        .'</td></tr></table>';
}

/**
 * @param  array<string, string>  $subscriber
 */
function apply_merge(string $value, array $subscriber, int $issueId, bool $html): string
{
    $value = apply_person_tags($value, $subscriber, $html);
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
 * Replace {first_name}, {last_name}, and {full_name}. A bar adds a fallback: {first_name|there}.
 *
 * @param  array<string, string>  $subscriber
 */
function apply_person_tags(string $value, array $subscriber, bool $html): string
{
    $first = trim((string) ($subscriber['first_name'] ?? ''));
    $last = trim((string) ($subscriber['last_name'] ?? ''));
    $full = trim($first.' '.$last);
    $values = [
        'first_name' => $first,
        'last_name' => $last,
        'full_name' => $full,
    ];

    $replaced = preg_replace_callback(
        '/\{(first_name|last_name|full_name)(?:\|([^{}|]*))?\}/',
        static function (array $match) use ($values, $html): string {
            $current = $values[$match[1]] ?? '';
            if ($current === '') {
                $current = (string) ($match[2] ?? '');
            }

            return $html ? esc_html($current) : $current;
        },
        $value
    );

    return is_string($replaced) ? $replaced : $value;
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
                if (preg_match('/[?&]mhn_(?:unsub|token|confirm|view|prefs|click|open|st)=/', $url) === 1) {
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

function ensure_heading(string $body, string $subject, bool $plain = false, string $layoutId = ''): string
{
    if (preg_match('/<h1\b/i', $body) === 1) {
        return $body;
    }

    $heading = letter_title_html($subject, $plain, $layoutId !== '' ? $layoutId : ($plain ? 'plain' : ''));
    if ($heading === '') {
        return $body;
    }

    $withEyebrow = preg_replace(
        '/^(<p class="mhn-eyebrow\b.*?<\/p>|<table\b[^>]*\bmhn-eyebrow-block\b.*?<\/table>)/is',
        '$1'.$heading,
        ltrim($body),
        1,
        $count
    );
    if (is_string($withEyebrow) && $count === 1) {
        return $withEyebrow;
    }

    return $heading.$body;
}

function plain_text(string $html): string
{
    $html = preg_replace('/<div class="mhn-preheader\b.*?<\/div>/is', '', $html) ?? $html;
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
        $args['mhn_st'] = view_token($subscriber, $issueId);
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
        'mhn_st' => tracking_token($subscriber, $issueId, 'open'),
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
        'mhn_st' => tracking_token($subscriber, $issueId, 'click', $target),
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
