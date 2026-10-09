<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Letter shells. Card is the white card with a navy stripe.
 *
 * @return array<string, array{label: string, summary: string}>
 */
function letter_style_choices(): array
{
    return [
        'card' => [
            'label' => __('Card', 'matthummel-newsletter'),
            'summary' => __('Light grey page, white rounded card, navy stripe.', 'matthummel-newsletter'),
        ],
        'banner' => [
            'label' => __('Banner', 'matthummel-newsletter'),
            'summary' => __('Light grey page, navy masthead, white rounded body.', 'matthummel-newsletter'),
        ],
        'paper' => [
            'label' => __('Paper', 'matthummel-newsletter'),
            'summary' => __('Light grey page, white rounded card, one navy rule.', 'matthummel-newsletter'),
        ],
    ];
}

/**
 * @return array<string, string>
 */
function letter_masthead_choices(): array
{
    return [
        'left' => __('Left', 'matthummel-newsletter'),
        'center' => __('Center', 'matthummel-newsletter'),
    ];
}

/**
 * @return array<string, string>
 */
function letter_button_choices(): array
{
    return [
        'solid' => __('Solid', 'matthummel-newsletter'),
        'outline' => __('Outline', 'matthummel-newsletter'),
    ];
}

/**
 * Web-safe stacks. Email clients ignore webfonts that are not installed.
 *
 * @return array<string, array{label: string, stack: string}>
 */
function letter_font_choices(): array
{
    return [
        'sans' => [
            'label' => __('Sans', 'matthummel-newsletter'),
            'stack' => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif",
        ],
        'serif' => [
            'label' => __('Serif', 'matthummel-newsletter'),
            'stack' => "Georgia,'Iowan Old Style','Palatino Linotype',Palatino,'Times New Roman',Times,serif",
        ],
        'humanist' => [
            'label' => __('Humanist', 'matthummel-newsletter'),
            'stack' => "'Trebuchet MS','Segoe UI',Helvetica,Arial,sans-serif",
        ],
        'editorial' => [
            'label' => __('Editorial', 'matthummel-newsletter'),
            'stack' => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif",
            'title' => "Georgia,'Iowan Old Style','Palatino Linotype',Palatino,'Times New Roman',Times,serif",
        ],
    ];
}

/**
 * Body size stays at 16px or larger. Smaller type fails the send check.
 *
 * @return array<string, array{label: string, body: string, line: string}>
 */
function letter_size_choices(): array
{
    return [
        'regular' => [
            'label' => __('Regular', 'matthummel-newsletter'),
            'body' => '16px',
            'line' => '1.6',
        ],
        'roomy' => [
            'label' => __('Roomy', 'matthummel-newsletter'),
            'body' => '18px',
            'line' => '1.7',
        ],
    ];
}

/**
 * Focused is the short letter. Detailed adds the extra line each layout is built for.
 *
 * @return array<string, array{label: string, summary: string}>
 */
function letter_variant_choices(): array
{
    return [
        'focused' => [
            'label' => __('Focused', 'matthummel-newsletter'),
            'summary' => __('One idea and one action. The short stack for every layout.', 'matthummel-newsletter'),
        ],
        'detailed' => [
            'label' => __('Detailed', 'matthummel-newsletter'),
            'summary' => __('The essay. A dek, a welcome list, a date, an image caption, or a why-I-wrote-this note.', 'matthummel-newsletter'),
        ],
        'digest' => [
            'label' => __('Digest', 'matthummel-newsletter'),
            'summary' => __('Why it matters, then a short list, then one link. Built for scanning.', 'matthummel-newsletter'),
        ],
    ];
}

/**
 * https only. Empty stays empty.
 */
function sanitize_https_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = function_exists('esc_url_raw') ? esc_url_raw($value) : $value;
    if (! is_string($value) || $value === '') {
        return '';
    }

    $scheme = function_exists('wp_parse_url') ? wp_parse_url($value, PHP_URL_SCHEME) : parse_url($value, PHP_URL_SCHEME);

    return $scheme === 'https' ? $value : '';
}

/**
 * https anywhere. http only when the host is this site, so a local library image can be saved.
 */
function sanitize_letter_asset_url(string $value): string
{
    $https = sanitize_https_url($value);
    if ($https !== '') {
        return $https;
    }

    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = function_exists('esc_url_raw') ? esc_url_raw($value) : $value;
    if (! is_string($value) || $value === '') {
        return '';
    }

    $scheme = function_exists('wp_parse_url') ? wp_parse_url($value, PHP_URL_SCHEME) : parse_url($value, PHP_URL_SCHEME);
    $host = function_exists('wp_parse_url') ? wp_parse_url($value, PHP_URL_HOST) : parse_url($value, PHP_URL_HOST);
    $home = function_exists('home_url') ? home_url() : '';
    $homeHost = function_exists('wp_parse_url') ? wp_parse_url((string) $home, PHP_URL_HOST) : parse_url((string) $home, PHP_URL_HOST);
    if ($scheme !== 'http' || ! is_string($host) || ! is_string($homeHost) || strcasecmp($host, $homeHost) !== 0) {
        return '';
    }

    return $value;
}

/**
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}
 */
function letter_look_defaults(): array
{
    return [
        'style' => 'card',
        'masthead' => 'left',
        'button' => 'solid',
        'font' => 'sans',
        'size' => 'regular',
        'variant' => 'detailed',
    ];
}

/**
 * Missing keys keep the fallback. An unknown key does too.
 *
 * @param  array<string, mixed>  $input
 * @param  array{style: string, masthead: string, button: string, font: string, size: string, variant: string}|null  $fallback
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}
 */
function normalize_letter_look(array $input, ?array $fallback = null): array
{
    $fallback ??= letter_look_defaults();
    $style = array_key_exists('style', $input) ? sanitize_key((string) $input['style']) : $fallback['style'];
    $masthead = array_key_exists('masthead', $input) ? sanitize_key((string) $input['masthead']) : $fallback['masthead'];
    $button = array_key_exists('button', $input) ? sanitize_key((string) $input['button']) : $fallback['button'];
    $font = array_key_exists('font', $input) ? sanitize_key((string) $input['font']) : $fallback['font'];
    $size = array_key_exists('size', $input) ? sanitize_key((string) $input['size']) : $fallback['size'];
    $variant = array_key_exists('variant', $input) ? sanitize_key((string) $input['variant']) : $fallback['variant'];

    return [
        'style' => isset(letter_style_choices()[$style]) ? $style : $fallback['style'],
        'masthead' => isset(letter_masthead_choices()[$masthead]) ? $masthead : $fallback['masthead'],
        'button' => isset(letter_button_choices()[$button]) ? $button : $fallback['button'],
        'font' => isset(letter_font_choices()[$font]) ? $font : $fallback['font'],
        'size' => isset(letter_size_choices()[$size]) ? $size : $fallback['size'],
        'variant' => isset(letter_variant_choices()[$variant]) ? $variant : $fallback['variant'],
    ];
}

/**
 * @param  array<string, string>  $allowed
 */
function choice_from_input(array $input, string $key, array $allowed, string $current): string
{
    if (! array_key_exists($key, $input)) {
        return isset($allowed[$current]) ? $current : (string) array_key_first($allowed);
    }

    $value = sanitize_key((string) $input[$key]);

    return isset($allowed[$value]) ? $value : (string) array_key_first($allowed);
}

/**
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}
 */
function settings_letter_look(): array
{
    $config = settings();

    return normalize_letter_look([
        'style' => $config['letter_style'],
        'masthead' => $config['letter_masthead'],
        'button' => $config['letter_button'],
        'font' => $config['letter_font'],
        'size' => $config['letter_size'],
        'variant' => $config['letter_variant'],
    ]);
}

/**
 * Empty issue meta uses the saved settings.
 *
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}
 */
function issue_letter_look(int $issueId): array
{
    $base = settings_letter_look();
    if ($issueId < 1 || ! function_exists('get_post_meta')) {
        return $base;
    }

    $saved = get_post_meta($issueId, '_mhn_letter_style', true);
    if (! is_array($saved) || $saved === []) {
        return $base;
    }

    $input = [];
    foreach (['style', 'masthead', 'button', 'font', 'size', 'variant'] as $key) {
        if (array_key_exists($key, $saved)) {
            $input[$key] = (string) $saved[$key];
        }
    }

    return normalize_letter_look($input, $base);
}

/**
 * @param  array<string, mixed>  $input
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}|null
 */
function letter_look_from_request(array $input, int $issueId): ?array
{
    $posted = array_key_exists('mhn_letter_style', $input)
        || array_key_exists('mhn_letter_masthead', $input)
        || array_key_exists('mhn_letter_button', $input)
        || array_key_exists('mhn_letter_font', $input)
        || array_key_exists('mhn_letter_size', $input)
        || array_key_exists('mhn_letter_variant', $input);
    if (! $posted) {
        return null;
    }

    $base = issue_letter_look($issueId);
    $look = [];
    $map = [
        'style' => 'mhn_letter_style',
        'masthead' => 'mhn_letter_masthead',
        'button' => 'mhn_letter_button',
        'font' => 'mhn_letter_font',
        'size' => 'mhn_letter_size',
        'variant' => 'mhn_letter_variant',
    ];
    foreach ($map as $key => $field) {
        if (array_key_exists($field, $input)) {
            $look[$key] = (string) $input[$field];
        }
    }

    return normalize_letter_look($look, $base);
}

/**
 * @param  array<string, mixed>  $input
 */
function save_issue_letter_style(int $issueId, array $input): void
{
    $look = letter_look_from_request($input, $issueId);
    if ($look === null || ! function_exists('update_post_meta')) {
        return;
    }

    if ($look === settings_letter_look()) {
        delete_post_meta($issueId, '_mhn_letter_style');

        return;
    }

    update_post_meta($issueId, '_mhn_letter_style', $look);
}

/**
 * @return array{style: string, masthead: string, button: string, font: string, size: string, variant: string}
 */
function current_letter_look(): array
{
    $look = $GLOBALS['mhn_letter_look'] ?? null;
    if (is_array($look)) {
        return normalize_letter_look($look);
    }

    return settings_letter_look();
}

/**
 * @param  array{style?: string, masthead?: string, button?: string, font?: string, size?: string, variant?: string}  $look
 */
function push_letter_look(array $look): void
{
    if (! isset($GLOBALS['mhn_letter_look_stack']) || ! is_array($GLOBALS['mhn_letter_look_stack'])) {
        $GLOBALS['mhn_letter_look_stack'] = [];
    }

    $GLOBALS['mhn_letter_look_stack'][] = $GLOBALS['mhn_letter_look'] ?? null;
    $GLOBALS['mhn_letter_look'] = normalize_letter_look($look, current_letter_look());
}

function letter_is_detailed(): bool
{
    return current_letter_look()['variant'] === 'detailed';
}

function letter_is_digest(): bool
{
    return current_letter_look()['variant'] === 'digest';
}

/**
 * @param  array{style?: string, masthead?: string, button?: string, font?: string, size?: string, variant?: string}|null  $look
 * @return array{label: string, body: string, line: string}
 */
function letter_type_spec(?array $look = null): array
{
    $look = normalize_letter_look($look ?? current_letter_look());
    $choices = letter_size_choices();

    return $choices[$look['size']] ?? $choices['regular'];
}

function pop_letter_look(): void
{
    $stack = $GLOBALS['mhn_letter_look_stack'] ?? [];
    if (! is_array($stack) || $stack === []) {
        unset($GLOBALS['mhn_letter_look']);

        return;
    }

    $previous = array_pop($stack);
    $GLOBALS['mhn_letter_look_stack'] = $stack;
    if (is_array($previous)) {
        $GLOBALS['mhn_letter_look'] = $previous;

        return;
    }

    unset($GLOBALS['mhn_letter_look']);
}

/**
 * Plugin letter CSS. The preview iframe and the sent message both receive this string.
 */
function email_css(): string
{
    $path = dirname(__DIR__).'/assets/email.css';
    if (! is_readable($path)) {
        return '';
    }

    $css = file_get_contents($path);
    if (! is_string($css)) {
        return '';
    }

    return trim(str_replace('</style', '', $css));
}

/**
 * Inline shell styles. Email clients that drop the style block still keep these.
 *
 * @param  array{style?: string, masthead?: string, button?: string, font?: string, size?: string, variant?: string}  $look
 * @return array{
 *     style: string,
 *     masthead: string,
 *     button: string,
 *     font_key: string,
 *     variant: string,
 *     align: string,
 *     font: string,
 *     page: string,
 *     page_td: string,
 *     frame: string,
 *     frame_td: string,
 *     card: string,
 *     stripe: string,
 *     masthead_style: string,
 *     brand: string,
 *     kicker: string,
 *     rule: string,
 *     show_stripe: bool,
 *     show_rule: bool,
 *     hero: string,
 *     footer: string
 * }
 */
function letter_chrome(array $look): array
{
    $look = normalize_letter_look($look);
    $font = email_font_stack($look['font']);
    $titleFont = email_title_stack($look['font']);
    $align = $look['masthead'] === 'center' ? 'center' : 'left';
    $page = '#eceff1';
    $brandColor = match ($look['style']) {
        'banner' => '#ffffff',
        'paper' => '#0b1220',
        default => '#0d2e57',
    };
    $kickerColor = $look['style'] === 'banner' ? '#e8eef6' : '#50575e';
    $mastBg = $look['style'] === 'banner' ? '#0d2e57' : '#ffffff';
    $mastPad = $look['style'] === 'banner' ? '28px 40px' : '26px 40px 14px';
    $mastRadius = $look['style'] === 'banner' ? 'border-radius:16px 16px 0 0;' : '';
    $rule = $look['style'] === 'banner' ? '' : 'border-top:2px solid #0d2e57;';
    $card = 'width:100%;background-color:#ffffff;background-image:linear-gradient(180deg,#ffffff 0%,#f7f8fa 100%);border:1px solid #d8dde3;border-collapse:separate;border-radius:16px;';

    return [
        'style' => $look['style'],
        'masthead' => $look['masthead'],
        'button' => $look['button'],
        'font_key' => $look['font'],
        'variant' => $look['variant'],
        'align' => $align,
        'font' => $font,
        'page' => 'margin:0;padding:0;background-color:'.$page.';',
        'page_td' => 'padding:64px 48px;background-color:'.$page.';',
        'frame' => 'width:100%;max-width:600px;background-color:transparent;border-radius:16px;',
        'frame_td' => 'padding:0;background-color:transparent;border-radius:16px;',
        'card' => $card,
        'stripe' => 'height:4px;line-height:4px;font-size:0;background-color:#0d2e57;border-radius:16px 16px 0 0;',
        'masthead_style' => 'padding:'.$mastPad.';background-color:'.$mastBg.';font-family:'.$font.';text-align:'.$align.';'.$mastRadius,
        'brand' => 'font-family:'.$titleFont.';font-size:15px;line-height:1.3;font-weight:700;letter-spacing:0.01em;color:'.$brandColor.';text-align:'.$align.';',
        'kicker' => 'margin-top:6px;font-size:13px;line-height:1.4;color:'.$kickerColor.';text-align:'.$align.';',
        'rule' => $rule,
        'show_stripe' => $look['style'] === 'card',
        'show_rule' => $look['style'] !== 'banner',
        'hero' => 'padding:0;background-color:#ffffff;line-height:0;font-size:0;',
        'footer' => 'padding:20px 40px 32px;border-top:1px solid #e2e6ea;font-family:'.$font.';font-size:13px;line-height:1.5;color:#50575e;text-align:left;background-color:#ffffff;',
    ];
}

/**
 * @param  array{style?: string, masthead?: string, button?: string, font?: string, size?: string, variant?: string}|null  $look
 * @return array{class: string, fill: string, stroke: string, text: string, anchor: string}
 */
function letter_button_palette(?array $look = null): array
{
    $look = normalize_letter_look($look ?? current_letter_look());
    $font = 'font-family:'.email_font_stack($look['font']).';';
    $shared = 'display:inline-block;'.$font.'font-size:16px;font-weight:700;line-height:1.3;text-align:center;text-decoration:none;min-height:44px;max-width:100%;box-sizing:border-box;-webkit-text-size-adjust:none;';
    if ($look['button'] === 'outline') {
        return [
            'class' => 'mhn-btn mhn-btn-outline',
            'fill' => '#ffffff',
            'stroke' => '#0d2e57',
            'text' => '#0d2e57',
            'anchor' => 'background-color:#ffffff;border:2px solid #0d2e57;border-radius:8px;color:#0d2e57;padding:12px 20px;'.$shared,
        ];
    }

    return [
        'class' => 'mhn-btn mhn-btn-solid',
        'fill' => '#0d2e57',
        'stroke' => '#0d2e57',
        'text' => '#ffffff',
        'anchor' => 'background-color:#0d2e57;border:0;border-radius:8px;color:#ffffff;padding:14px 22px;'.$shared,
    ];
}
