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
            'summary' => __('White card, navy stripe, soft blue page.', 'matthummel-newsletter'),
        ],
        'banner' => [
            'label' => __('Banner', 'matthummel-newsletter'),
            'summary' => __('Navy band, white name, white body, soft blue page.', 'matthummel-newsletter'),
        ],
        'paper' => [
            'label' => __('Paper', 'matthummel-newsletter'),
            'summary' => __('White page, ink text, one navy rule.', 'matthummel-newsletter'),
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
 * @param  array<string, mixed>  $input
 * @param  array{style: string, masthead: string, button: string}|null  $fallback
 * @return array{style: string, masthead: string, button: string}
 */
function normalize_letter_look(array $input, ?array $fallback = null): array
{
    $fallback ??= [
        'style' => 'card',
        'masthead' => 'left',
        'button' => 'solid',
    ];
    $style = sanitize_key((string) ($input['style'] ?? ''));
    $masthead = sanitize_key((string) ($input['masthead'] ?? ''));
    $button = sanitize_key((string) ($input['button'] ?? ''));

    return [
        'style' => isset(letter_style_choices()[$style]) ? $style : $fallback['style'],
        'masthead' => isset(letter_masthead_choices()[$masthead]) ? $masthead : $fallback['masthead'],
        'button' => isset(letter_button_choices()[$button]) ? $button : $fallback['button'],
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
 * @return array{style: string, masthead: string, button: string}
 */
function settings_letter_look(): array
{
    $config = settings();

    return normalize_letter_look([
        'style' => $config['letter_style'],
        'masthead' => $config['letter_masthead'],
        'button' => $config['letter_button'],
    ]);
}

/**
 * Empty issue meta uses the saved settings.
 *
 * @return array{style: string, masthead: string, button: string}
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

    return normalize_letter_look([
        'style' => (string) ($saved['style'] ?? ''),
        'masthead' => (string) ($saved['masthead'] ?? ''),
        'button' => (string) ($saved['button'] ?? ''),
    ], $base);
}

/**
 * @param  array<string, mixed>  $input
 * @return array{style: string, masthead: string, button: string}|null
 */
function letter_look_from_request(array $input, int $issueId): ?array
{
    $posted = array_key_exists('mhn_letter_style', $input)
        || array_key_exists('mhn_letter_masthead', $input)
        || array_key_exists('mhn_letter_button', $input);
    if (! $posted) {
        return null;
    }

    $base = issue_letter_look($issueId);

    return normalize_letter_look([
        'style' => (string) ($input['mhn_letter_style'] ?? $base['style']),
        'masthead' => (string) ($input['mhn_letter_masthead'] ?? $base['masthead']),
        'button' => (string) ($input['mhn_letter_button'] ?? $base['button']),
    ], $base);
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
 * @return array{style: string, masthead: string, button: string}
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
 * @param  array{style?: string, masthead?: string, button?: string}  $look
 */
function push_letter_look(array $look): void
{
    if (! isset($GLOBALS['mhn_letter_look_stack']) || ! is_array($GLOBALS['mhn_letter_look_stack'])) {
        $GLOBALS['mhn_letter_look_stack'] = [];
    }

    $GLOBALS['mhn_letter_look_stack'][] = $GLOBALS['mhn_letter_look'] ?? null;
    $GLOBALS['mhn_letter_look'] = normalize_letter_look($look, current_letter_look());
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
 * @param  array{style?: string, masthead?: string, button?: string}  $look
 * @return array{
 *     style: string,
 *     masthead: string,
 *     button: string,
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
    $font = email_font_stack();
    $align = $look['masthead'] === 'center' ? 'center' : 'left';
    $page = $look['style'] === 'paper' ? '#ffffff' : '#eef3f9';
    $frame = $look['style'] === 'card' ? '#dceaf8' : '#ffffff';
    $framePad = $look['style'] === 'card' ? '1px' : '0';
    $brandColor = match ($look['style']) {
        'banner' => '#ffffff',
        'paper' => '#0b1220',
        default => '#0d2e57',
    };
    $kickerColor = $look['style'] === 'banner' ? '#eef3f9' : '#50575e';
    $mastBg = $look['style'] === 'banner' ? '#0d2e57' : '#ffffff';
    $mastPad = $look['style'] === 'banner' ? '28px 32px' : '26px 32px 14px';
    $mastRadius = $look['style'] === 'banner' ? 'border-radius:4px 4px 0 0;' : '';
    $rule = match ($look['style']) {
        'banner' => '',
        'paper' => 'border-top:2px solid #0d2e57;',
        default => 'border-top:2px solid #0d2e57;',
    };
    $heroBg = $look['style'] === 'paper' ? '#ffffff' : '#eef3f9';

    return [
        'style' => $look['style'],
        'masthead' => $look['masthead'],
        'button' => $look['button'],
        'align' => $align,
        'font' => $font,
        'page' => 'margin:0;padding:0;background-color:'.$page.';',
        'page_td' => 'padding:32px 16px;background-color:'.$page.';',
        'frame' => 'width:100%;max-width:600px;background-color:'.$frame.';border-radius:4px;',
        'frame_td' => 'padding:'.$framePad.';background-color:'.$frame.';border-radius:4px;',
        'card' => 'width:100%;background-color:#ffffff;border-collapse:separate;border-radius:4px;',
        'stripe' => 'height:4px;line-height:4px;font-size:0;background-color:#0d2e57;border-radius:4px 4px 0 0;',
        'masthead_style' => 'padding:'.$mastPad.';background-color:'.$mastBg.';font-family:'.$font.';text-align:'.$align.';'.$mastRadius,
        'brand' => 'font-size:15px;line-height:1.3;font-weight:700;letter-spacing:0.01em;color:'.$brandColor.';text-align:'.$align.';',
        'kicker' => 'margin-top:6px;font-size:13px;line-height:1.4;color:'.$kickerColor.';text-align:'.$align.';',
        'rule' => $rule,
        'show_stripe' => $look['style'] === 'card',
        'show_rule' => $look['style'] !== 'banner',
        'hero' => 'padding:0;background-color:'.$heroBg.';line-height:0;font-size:0;',
        'footer' => 'padding:18px 32px 28px;border-top:1px solid #dceaf8;font-family:'.$font.';font-size:13px;line-height:1.5;color:#50575e;text-align:left;',
    ];
}

/**
 * @param  array{style?: string, masthead?: string, button?: string}|null  $look
 * @return array{class: string, fill: string, stroke: string, text: string, anchor: string}
 */
function letter_button_palette(?array $look = null): array
{
    $look = normalize_letter_look($look ?? current_letter_look());
    $font = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
    $shared = 'display:inline-block;'.$font.'font-size:16px;font-weight:700;line-height:1.3;text-align:center;text-decoration:none;min-height:44px;max-width:100%;box-sizing:border-box;-webkit-text-size-adjust:none;';
    if ($look['button'] === 'outline') {
        return [
            'class' => 'mhn-btn mhn-btn-outline',
            'fill' => '#ffffff',
            'stroke' => '#0d2e57',
            'text' => '#0d2e57',
            'anchor' => 'background-color:#ffffff;border:2px solid #0d2e57;border-radius:4px;color:#0d2e57;padding:12px 20px;'.$shared,
        ];
    }

    return [
        'class' => 'mhn-btn mhn-btn-solid',
        'fill' => '#0d2e57',
        'stroke' => '#0d2e57',
        'text' => '#ffffff',
        'anchor' => 'background-color:#0d2e57;border:0;border-radius:4px;color:#ffffff;padding:14px 22px;'.$shared,
    ];
}
