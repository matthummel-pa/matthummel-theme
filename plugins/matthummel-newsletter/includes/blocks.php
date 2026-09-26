<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function render_blocks(string $content, string $altFallback): string
{
    $html = '';
    foreach (parse_blocks($content) as $block) {
        $html .= render_block($block, $altFallback);
    }

    return $html;
}

/**
 * @param  array<string, mixed>  $block
 */
function render_block(array $block, string $altFallback): string
{
    $name = isset($block['blockName']) ? (string) $block['blockName'] : '';
    if ($name === '') {
        return '';
    }

    return match ($name) {
        'core/paragraph' => render_paragraph($block),
        'core/heading' => render_heading($block),
        'core/image' => render_image($block, $altFallback),
        'core/buttons', 'core/button' => render_buttons($block),
        'core/columns' => render_columns($block, $altFallback),
        'core/column', 'core/group' => render_group($block, $altFallback),
        'core/list' => render_list($block),
        'core/quote' => render_quote($block),
        'core/separator' => render_separator(),
        'core/spacer' => render_spacer($block),
        default => render_fallback($block, $altFallback),
    };
}

/**
 * @param  array<string, mixed>  $block
 */
function render_paragraph(array $block): string
{
    $html = email_kses((string) ($block['innerHTML'] ?? ''));
    if (trim(wp_strip_all_tags($html)) === '' && ! str_contains($html, '<img')) {
        return '';
    }

    $styled = preg_replace(
        '/<p(\s|>)/',
        '<p style="'.body_paragraph_style().'"$1',
        $html
    );

    return decorate_links(is_string($styled) ? $styled : $html);
}

/**
 * @param  array<string, mixed>  $block
 */
function render_heading(array $block): string
{
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $level = (int) ($attrs['level'] ?? 2);
    $level = max(1, min(3, $level));
    $text = trim(wp_strip_all_tags((string) ($block['innerHTML'] ?? '')));
    if ($text === '') {
        return '';
    }

    if ($level === 1) {
        return letter_title_html($text);
    }

    $size = $level === 2 ? 22 : 18;

    return '<h'.$level.' class="mhn-text" style="margin:0 0 12px;font-family:'.email_font_stack().';font-size:'.$size.'px;line-height:1.3;font-weight:700;color:#0d2e57;text-align:left;">'.esc_html($text).'</h'.$level.'>';
}

/**
 * @param  array<string, mixed>  $block
 */
function render_image(array $block, string $altFallback): string
{
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $src = isset($attrs['url']) ? (string) $attrs['url'] : '';
    $alt = isset($attrs['alt']) ? (string) $attrs['alt'] : '';
    $id = isset($attrs['id']) ? (int) $attrs['id'] : 0;
    $href = isset($attrs['href']) ? (string) $attrs['href'] : '';
    $maxWidth = isset($attrs['maxWidth']) ? (int) $attrs['maxWidth'] : 600;
    if ($maxWidth < 1 || $maxWidth > 600) {
        $maxWidth = 600;
    }
    $width = $maxWidth;
    $height = 0;

    if ($id > 0) {
        $size = isset($attrs['sizeSlug']) && $attrs['sizeSlug'] === 'medium' ? 'medium' : 'large';
        $image = wp_get_attachment_image_src($id, $size);
        if (! is_array($image)) {
            $image = wp_get_attachment_image_src($id, 'large');
        }
        if (is_array($image)) {
            $src = (string) $image[0];
            $width = (int) $image[1];
            $height = (int) $image[2];
        }
        if ($alt === '') {
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        }
    }

    if ($src === '') {
        [$src, $htmlAlt] = image_from_html((string) ($block['innerHTML'] ?? ''));
        if ($alt === '') {
            $alt = $htmlAlt;
        }
    }

    $src = email_image_url($src);
    if ($src === '' || str_contains($src, '*|')) {
        return '';
    }

    unset($altFallback);

    if ($width > $maxWidth && $width > 0) {
        $height = $height > 0 ? (int) round($height * ($maxWidth / $width)) : 0;
        $width = $maxWidth;
    }
    if ($width < 1) {
        $width = $maxWidth;
    }
    if ($height < 1) {
        $height = $width;
    }

    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $img = '<img class="mhn-img" src="'.esc_url($src).'" alt="'.esc_attr($alt).'" width="'.esc_attr((string) $width).'" height="'.esc_attr((string) $height).'" style="display:block;width:100%;max-width:'.$width.'px;height:auto;border:0;margin:0 0 16px;color:#0b1220;background-color:#eef3f9;font-family:'.$font.';font-size:16px;line-height:1.5;">';
    $href = email_image_url($href);
    if ($href === '' || ! preg_match('#^https?://#i', $href)) {
        return $img;
    }

    return '<a href="'.esc_url($href).'" style="color:#0d2e57;text-decoration:underline;">'.$img.'</a>';
}

/**
 * @return array{0: string, 1: string}
 */
function image_from_html(string $html): array
{
    $src = '';
    $alt = '';
    if (preg_match('/<img\b[^>]*>/i', $html, $tag) === 1) {
        if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $tag[0], $match) === 1) {
            $src = html_entity_decode($match[1], ENT_QUOTES);
        }
        if (preg_match('/\balt=["\']([^"\']*)["\']/i', $tag[0], $match) === 1) {
            $alt = html_entity_decode($match[1], ENT_QUOTES);
        }
    }

    return [$src, $alt];
}

/**
 * @param  array<string, mixed>  $block
 */
function render_buttons(array $block): string
{
    $buttons = [];
    $name = (string) ($block['blockName'] ?? '');
    if ($name === 'core/button') {
        $buttons[] = $block;
    } else {
        $inner = $block['innerBlocks'] ?? [];
        if (is_array($inner)) {
            foreach ($inner as $child) {
                if (is_array($child) && ($child['blockName'] ?? '') === 'core/button') {
                    $buttons[] = $child;
                }
            }
        }
    }

    if ($buttons === []) {
        return button_from_html((string) ($block['innerHTML'] ?? ''));
    }

    $html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr>';
    foreach ($buttons as $button) {
        $html .= '<td style="padding:0 12px 8px 0;">'.render_one_button($button).'</td>';
    }
    $html .= '</tr></table>';

    return $html;
}

/**
 * @param  array<string, mixed>  $block
 */
function render_one_button(array $block): string
{
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $url = isset($attrs['url']) ? (string) $attrs['url'] : '';
    $label = isset($attrs['text']) ? (string) $attrs['text'] : '';
    $html = (string) ($block['innerHTML'] ?? '');

    if ($url === '' && preg_match('/<a\b[^>]*href=["\']([^"\']+)["\']/i', $html, $match) === 1) {
        $url = html_entity_decode($match[1], ENT_QUOTES);
    }
    if ($label === '') {
        $label = trim(wp_strip_all_tags($html));
    }
    if ($label === '' || $url === '') {
        return '';
    }

    return bulletproof_button($label, absolute_url($url));
}

function button_from_html(string $html): string
{
    if (preg_match('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $match) !== 1) {
        return '';
    }

    $label = trim(wp_strip_all_tags($match[2]));
    if ($label === '') {
        return '';
    }

    return bulletproof_button($label, absolute_url(html_entity_decode($match[1], ENT_QUOTES)));
}

function bulletproof_button(string $label, string $url): string
{
    $safeLabel = esc_html($label);
    $href = href_attr($url);
    $palette = letter_button_palette();
    $font = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";

    return '<!--[if mso]>'
        .'<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.$href.'" style="height:48px;v-text-anchor:middle;width:280px;" arcsize="8%" strokecolor="'.$palette['stroke'].'" fillcolor="'.$palette['fill'].'">'
        .'<w:anchorlock/>'
        .'<center style="color:'.$palette['text'].';'.$font.'font-size:16px;font-weight:bold;">'.$safeLabel.'</center>'
        .'</v:roundrect>'
        .'<![endif]-->'
        .'<!--[if !mso]><!-->'
        .'<a class="'.esc_attr($palette['class']).'" href="'.$href.'" style="'.$palette['anchor'].'">'.$safeLabel.'</a>'
        .'<!--<![endif]-->';
}

/**
 * @param  array<string, mixed>  $block
 */
function render_columns(array $block, string $altFallback): string
{
    $columns = [];
    $inner = $block['innerBlocks'] ?? [];
    if (is_array($inner)) {
        foreach ($inner as $child) {
            if (is_array($child) && ($child['blockName'] ?? '') === 'core/column') {
                $columns[] = $child;
            }
        }
    }

    $count = count($columns);
    if ($count === 0) {
        return render_group($block, $altFallback);
    }

    $stack = $count > 3;
    $contentWidth = 520;
    $px = $stack ? $contentWidth : (int) floor($contentWidth / min($count, 3));
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;"><tr>';
    foreach ($columns as $column) {
        $body = '';
        $kids = $column['innerBlocks'] ?? [];
        if (is_array($kids)) {
            foreach ($kids as $kid) {
                if (is_array($kid)) {
                    $body .= render_block($kid, $altFallback);
                }
            }
        }
        $html .= '<td class="mhn-col" width="'.esc_attr((string) $px).'" valign="top" style="display:inline-block;width:'.$px.'px;max-width:100%;vertical-align:top;padding:0 8px 12px 0;">'.$body.'</td>';
    }
    $html .= '</tr></table>';

    return $html;
}

/**
 * @param  array<string, mixed>  $block
 */
function render_group(array $block, string $altFallback): string
{
    $body = '';
    $inner = $block['innerBlocks'] ?? [];
    if (is_array($inner)) {
        foreach ($inner as $child) {
            if (is_array($child)) {
                $body .= render_block($child, $altFallback);
            }
        }
    }
    if ($body === '') {
        $body = email_kses((string) ($block['innerHTML'] ?? ''));
    }
    if (trim(wp_strip_all_tags($body)) === '' && ! str_contains($body, '<img') && ! str_contains($body, 'mhn-btn')) {
        return '';
    }

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr><td class="mhn-group" style="padding:16px;background-color:#f7f9fc;">'.$body.'</td></tr></table>';
}

/**
 * @param  array<string, mixed>  $block
 */
function render_list(array $block): string
{
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $tag = ! empty($attrs['ordered']) ? 'ol' : 'ul';
    $items = '';
    $inner = $block['innerBlocks'] ?? [];
    if (is_array($inner)) {
        foreach ($inner as $child) {
            if (! is_array($child)) {
                continue;
            }
            $text = email_kses((string) ($child['innerHTML'] ?? ''));
            $text = preg_replace('/^<li[^>]*>|<\/li>$/i', '', trim($text)) ?? $text;
            if (trim(wp_strip_all_tags($text)) === '') {
                continue;
            }
            $items .= '<li style="margin:0 0 8px;">'.$text.'</li>';
        }
    }

    if ($items === '') {
        return email_kses((string) ($block['innerHTML'] ?? ''));
    }

    return decorate_links('<'.$tag.' class="mhn-text" style="margin:0 0 16px;padding-left:20px;font-size:16px;line-height:1.6;color:#0b1220;text-align:left;">'.$items.'</'.$tag.'>');
}

/**
 * @param  array<string, mixed>  $block
 */
function render_quote(array $block): string
{
    $text = '';
    $inner = $block['innerBlocks'] ?? [];
    if (is_array($inner)) {
        foreach ($inner as $child) {
            if (is_array($child)) {
                $text .= email_kses((string) ($child['innerHTML'] ?? ''));
            }
        }
    }
    if (trim(wp_strip_all_tags($text)) === '') {
        $text = email_kses((string) ($block['innerHTML'] ?? ''));
    }
    if (trim(wp_strip_all_tags($text)) === '') {
        return '';
    }

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr><td class="mhn-text" style="border-left:3px solid #0d2e57;padding:4px 0 4px 16px;font-size:16px;line-height:1.6;color:#243041;font-style:italic;text-align:left;">'.decorate_links($text).'</td></tr></table>';
}

function render_separator(): string
{
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr><td style="border-top:1px solid #cfd9e6;font-size:0;line-height:0;" class="mhn-rule">&nbsp;</td></tr></table>';
}

/**
 * @param  array<string, mixed>  $block
 */
function render_spacer(array $block): string
{
    $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    $height = (int) ($attrs['height'] ?? 24);
    $height = max(8, min(64, $height));

    return '<div style="height:'.$height.'px;line-height:'.$height.'px;font-size:1px;">&nbsp;</div>';
}

/**
 * @param  array<string, mixed>  $block
 */
function render_fallback(array $block, string $altFallback): string
{
    $html = '';
    $inner = $block['innerBlocks'] ?? [];
    if (is_array($inner)) {
        foreach ($inner as $child) {
            if (is_array($child)) {
                $html .= render_block($child, $altFallback);
            }
        }
    }
    if ($html !== '') {
        return $html;
    }

    return decorate_links(email_kses((string) ($block['innerHTML'] ?? '')));
}

function decorate_links(string $html): string
{
    $styled = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $match): string {
        $attrs = $match[1];
        if (stripos($attrs, 'text-decoration') !== false) {
            return $match[0];
        }

        return '<a style="color:#0d2e57;text-decoration:underline;"'.$attrs.'>';
    }, $html);

    return is_string($styled) ? $styled : $html;
}

function email_kses(string $html): string
{
    return wp_kses($html, [
        'a' => ['href' => true, 'title' => true],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'br' => [],
        'p' => [],
        'span' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
    ]);
}

function email_image_url(string $url): string
{
    $url = absolute_url($url);
    if ($url === '' || preg_match('#^http://#i', $url) !== 1) {
        return $url;
    }

    $host = wp_parse_url($url, PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';
    $site = wp_parse_url(home_url(), PHP_URL_HOST);
    $site = is_string($site) ? strtolower($site) : '';
    $production = $host === 'matthummel.com' || $host === 'www.matthummel.com';
    $secureSite = function_exists('is_ssl') && is_ssl() && $host !== '' && $host === $site;
    if (! $production && ! $secureSite) {
        return $url;
    }

    $secure = preg_replace('#^http://#i', 'https://', $url);

    return is_string($secure) ? $secure : $url;
}

function absolute_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || str_contains($url, '*|') || str_starts_with($url, '#')) {
        return $url;
    }
    if (preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }
    if (str_starts_with($url, '//')) {
        return 'https:'.$url;
    }
    if (str_starts_with($url, '/')) {
        return home_url($url);
    }

    return $url;
}

function href_attr(string $url): string
{
    if (str_contains($url, '*|')) {
        return esc_attr($url);
    }

    return esc_url(absolute_url($url));
}
