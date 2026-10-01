<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * One letter body for the wizard. Header, footer, address, and unsubscribe stay outside it.
 */
function sanitize_editor_html(string $html): string
{
    $html = preg_replace('#<(script|iframe|object|embed|form|style|link|meta|svg)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
    $html = preg_replace('#<(script|iframe|object|embed|form|style|link|meta|svg)\b[^>]*/?\s*>#is', '', $html) ?? $html;
    if (strlen($html) > 100000) {
        $html = substr($html, 0, 100000);
    }

    $clean = wp_kses($html, editor_allowed_html());
    $clean = editor_clean_tags($clean);

    return trim($clean);
}

/**
 * Editor HTML to email-safe inline markup. Scripts, frames, and forms are already gone.
 */
function transform_editor_html(string $html): string
{
    $html = sanitize_editor_html($html);
    if ($html === '') {
        return '';
    }

    $root = editor_dom_root($html);
    if (! $root instanceof \DOMElement) {
        return '';
    }

    return transform_children($root);
}

/**
 * @param  list<int>  $postIds
 */
function starter_body_html(string $slug, array $postIds): string
{
    $parts = [default_note_html()];
    $template = email_template($slug);
    if ($template !== null && $template['max_posts'] > 0) {
        $region = posts_region_html($postIds, $template['max_posts'] > 1);
        if ($region !== '') {
            $parts[] = $region;
        }
    }
    if ($template === null || $template['max_posts'] === 0) {
        $parts[] = '<p></p>';
    }

    return implode("\n", $parts);
}

/**
 * @param  list<int>  $ids
 */
function posts_region_html(array $ids, bool $card): string
{
    $chunks = [];
    $seen = [];
    foreach ($ids as $raw) {
        $id = absint($raw);
        if ($id < 1 || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $post = get_post($id);
        if (! $post instanceof \WP_Post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
            continue;
        }
        $chunks[] = post_editor_html($post, $card);
        if (count($chunks) >= 6) {
            break;
        }
    }
    if ($chunks === []) {
        return '';
    }

    return '<div class="mhn-posts">'.implode('', $chunks).'</div>';
}

function issue_has_body_editor(int $issueId): bool
{
    return $issueId > 0 && (string) get_post_meta($issueId, '_mhn_body_v', true) === '1';
}

function ensure_issue_body(int $issueId): void
{
    if ($issueId < 1 || issue_has_body_editor($issueId)) {
        return;
    }

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return;
    }

    $composed = compose_legacy_body($issueId);
    $content = (string) $post->post_content;
    if (trim(wp_strip_all_tags($composed)) === '' && preg_match('/<!--\s+wp:/', $content) === 1) {
        return;
    }

    update_post_meta($issueId, '_mhn_body', $composed);
    update_post_meta($issueId, '_mhn_body_v', '1');
    update_post_meta($issueId, '_mhn_editor', 'simple');
}

function issue_body_html(int $issueId): string
{
    ensure_issue_body($issueId);

    return (string) get_post_meta($issueId, '_mhn_body', true);
}

function store_issue_body(int $issueId, string $html): void
{
    update_post_meta($issueId, '_mhn_body', sanitize_editor_html($html));
    update_post_meta($issueId, '_mhn_body_v', '1');
    update_post_meta($issueId, '_mhn_editor', 'simple');
}

function compose_legacy_body(int $issueId, ?string $note = null, ?string $ps = null, ?string $buttonLabel = null, ?string $buttonUrl = null): string
{
    $noteHtml = sanitize_editor_html($note ?? (string) get_post_meta($issueId, '_mhn_note', true));
    $slug = (string) get_post_meta($issueId, '_mhn_template', true);
    $template = email_template($slug);
    $region = '';
    if ($template !== null && $template['max_posts'] > 0) {
        $region = posts_region_html(issue_post_ids($issueId), $template['max_posts'] > 1);
    }

    $label = trim(wp_strip_all_tags($buttonLabel ?? (string) get_post_meta($issueId, '_mhn_button_label', true)));
    $url = trim($buttonUrl ?? (string) get_post_meta($issueId, '_mhn_button_url', true));
    $button = '';
    if ($label !== '' && editor_href($url) !== '') {
        $button = '<p><a class="mhn-email-button" href="'.esc_url($url).'">'.esc_html($label).'</a></p>';
    }

    $psHtml = sanitize_editor_html($ps ?? (string) get_post_meta($issueId, '_mhn_ps', true));
    $advanced = '';
    if (issue_editor_mode($issueId) === 'advanced') {
        $text = trim((string) (issue_blocks($issueId)['body'] ?? ''));
        if ($text !== '') {
            $advanced = '<p>'.esc_html($text).'</p>';
        }
    }

    $parts = array_filter(
        [$noteHtml, $advanced, $region, $button, $psHtml],
        static fn (string $part): bool => $part !== ''
    );

    return implode("\n", $parts);
}

/**
 * Reusable intro and sign-off stay around the letter. The editor order is left as written.
 */
function apply_editor_layout(string $layoutId, string $body): string
{
    $layoutId = normalize_layout_id($layoutId);
    if ($layoutId === 'plain') {
        $body = soften_plain_buttons($body);
    }

    $copy = layout_copy();
    $variant = current_letter_look()['variant'];
    $signoff = layout_signoff_html($copy['signoff'], $copy['from_name']);
    if ($variant === 'digest') {
        return letter_callout_html(__('Why it matters', 'matthummel-newsletter'), $copy['intro']).$body.$signoff;
    }
    if ($variant === 'detailed' && $layoutId === 'feature') {
        return letter_caption_html($copy['intro']).$body.$signoff;
    }
    if ($variant === 'detailed' && $layoutId === 'plain') {
        return letter_meta_html([letter_today_label()]).letter_dek_html($copy['intro']).$body.$signoff;
    }
    if ($variant === 'detailed') {
        return letter_dek_html($copy['intro']).$body.$signoff;
    }
    if ($layoutId === 'plain') {
        return layout_eyebrow_html($copy['intro'], 'plain').$body.$signoff;
    }

    return layout_eyebrow_html($copy['intro'], $layoutId).$body.$signoff;
}

function letter_uses_body_editor(int $issueId): bool
{
    if (! issue_has_body_editor($issueId) || issue_editor_mode($issueId) === 'advanced') {
        return false;
    }
    if (in_array(issue_layout_id($issueId), ['welcome', 'post'], true)) {
        return false;
    }
    if (editor_diverged($issueId)) {
        return false;
    }

    $post = get_post($issueId);
    $content = $post instanceof \WP_Post ? trim((string) $post->post_content) : '';
    $body = trim((string) get_post_meta($issueId, '_mhn_body', true));
    $stored = (string) get_post_meta($issueId, '_mhn_compiled', true);
    if ($stored === '' && $content !== '' && $content !== $body) {
        return false;
    }

    return true;
}

function post_editor_html(\WP_Post $post, bool $card): string
{
    $title = html_entity_decode(get_the_title($post), ENT_QUOTES);
    $url = get_permalink($post);
    $href = is_string($url) && $url !== '' ? $url : home_url('/');
    $image = editor_image_tag((int) get_post_thumbnail_id($post), $title, $card);
    $linkedImage = $image === '' ? '' : '<a href="'.esc_url($href).'">'.$image.'</a>';
    $safeTitle = esc_html($title);
    $excerpt = esc_html(post_plain_excerpt($post));
    $inner = $linkedImage.'<h2>'.$safeTitle.'</h2>';
    if ($excerpt !== '') {
        $inner .= '<p>'.$excerpt.'</p>';
    }
    if ($card) {
        $inner .= '<p><a href="'.esc_url($href).'">'.$safeTitle.'</a></p>';
    } else {
        $inner .= '<p><a class="mhn-email-button" href="'.esc_url($href).'">'.esc_html(read_more_label($title)).'</a></p>';
    }
    $class = $card ? 'mhn-post mhn-card-post' : 'mhn-post';

    return '<div class="'.esc_attr($class).'">'.$inner.'</div>';
}

function editor_image_tag(int $attachmentId, string $fallbackAlt, bool $thumb): string
{
    if ($attachmentId < 1 || ! function_exists('wp_get_attachment_image_src')) {
        return '';
    }

    $size = $thumb ? 'medium' : 'large';
    $image = wp_get_attachment_image_src($attachmentId, $size);
    if (! is_array($image)) {
        $image = wp_get_attachment_image_src($attachmentId, 'full');
    }
    if (! is_array($image) || (string) $image[0] === '') {
        return '';
    }

    $alt = trim((string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true));
    if ($alt === '') {
        $alt = trim($fallbackAlt);
    }

    $width = (int) $image[1];
    $height = (int) $image[2];
    $cap = $thumb ? 280 : 600;
    if ($width < 1) {
        $width = $cap;
    }
    if ($width > $cap) {
        $height = $height > 0 ? (int) round($height * ($cap / $width)) : 0;
        $width = $cap;
    }
    if ($height < 1) {
        $height = (int) round($width * 0.56);
    }

    $class = $thumb ? ' class="mhn-thumb"' : '';

    return '<img src="'.esc_url((string) $image[0]).'" alt="'.esc_attr($alt).'" width="'.esc_attr((string) $width).'" height="'.esc_attr((string) $height).'"'.$class.'>';
}

/**
 * @return array<string, array<string, bool>>
 */
function editor_allowed_html(): array
{
    return [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'a' => [
            'href' => true,
            'class' => true,
        ],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h2' => [],
        'h3' => [],
        'blockquote' => [],
        'hr' => [],
        'img' => [
            'src' => true,
            'alt' => true,
            'width' => true,
            'height' => true,
            'class' => true,
        ],
        'div' => [
            'class' => true,
        ],
    ];
}

function editor_clean_tags(string $html): string
{
    $html = preg_replace_callback('/<img\b([^>]*)>/i', static function (array $match): string {
        $attrs = editor_attr_map($match[1]);
        $src = editor_href($attrs['src'] ?? '');
        if ($src === '') {
            return '';
        }
        $class = editor_img_class($attrs['class'] ?? '');
        $tag = '<img src="'.esc_url($src).'" alt="'.esc_attr($attrs['alt'] ?? '').'"';
        if (($attrs['width'] ?? '') !== '') {
            $tag .= ' width="'.esc_attr((string) max(0, (int) $attrs['width'])).'"';
        }
        if (($attrs['height'] ?? '') !== '') {
            $tag .= ' height="'.esc_attr((string) max(0, (int) $attrs['height'])).'"';
        }
        if ($class !== '') {
            $tag .= ' class="'.esc_attr($class).'"';
        }

        return $tag.'>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $match): string {
        $attrs = editor_attr_map($match[1]);
        $href = editor_href($attrs['href'] ?? '');
        if ($href === '') {
            return '<a>';
        }
        $class = str_contains($attrs['class'] ?? '', 'mhn-email-button') ? ' class="mhn-email-button"' : '';

        return '<a href="'.esc_url($href).'"'.$class.'>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<div\b([^>]*)>/i', static function (array $match): string {
        $attrs = editor_attr_map($match[1]);
        $class = editor_div_class($attrs['class'] ?? '');
        if ($class === '') {
            return '<div>';
        }

        return '<div class="'.esc_attr($class).'">';
    }, $html) ?? $html;

    return $html;
}

/**
 * @return array<string, string>
 */
function editor_attr_map(string $raw): array
{
    $found = [];
    if (preg_match_all('/\b(src|href|alt|width|height|class)\s*=\s*(["\'])(.*?)\2/i', $raw, $matches, PREG_SET_ORDER) < 1) {
        return $found;
    }
    foreach ($matches as $row) {
        $found[strtolower($row[1])] = html_entity_decode($row[3], ENT_QUOTES);
    }

    return $found;
}

function editor_img_class(string $class): string
{
    $keep = [];
    foreach (preg_split('/\s+/', trim($class)) ?: [] as $token) {
        if ($token === 'mhn-thumb' || preg_match('/^wp-image-\d+$/', $token) === 1) {
            $keep[] = $token;
        }
    }

    return implode(' ', $keep);
}

function editor_div_class(string $class): string
{
    $allowed = ['mhn-posts', 'mhn-post', 'mhn-card-post'];
    $keep = [];
    foreach (preg_split('/\s+/', trim($class)) ?: [] as $token) {
        if (in_array($token, $allowed, true)) {
            $keep[] = $token;
        }
    }

    return implode(' ', $keep);
}

function editor_href(string $url): string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES));
    if ($url === '' || preg_match('/^(javascript|data|vbscript):/i', $url) === 1) {
        return '';
    }

    return $url;
}

function editor_link_url(string $url): string
{
    $url = editor_href($url);
    if ($url === '' || str_starts_with(strtolower($url), 'mailto:')) {
        return $url;
    }
    if (function_exists('absolute_url')) {
        $url = absolute_url($url);
    }
    if (str_starts_with($url, '//')) {
        $url = 'https:'.$url;
    }
    if (preg_match('#^https?://#i', $url) !== 1) {
        return '';
    }

    return $url;
}

function editor_https_url(string $url): string
{
    $url = editor_link_url($url);
    if ($url === '' || str_starts_with(strtolower($url), 'mailto:')) {
        return '';
    }
    if (function_exists('email_image_url')) {
        $url = email_image_url($url);
    }
    if (preg_match('#^http://#i', $url) === 1) {
        $secure = preg_replace('#^http://#i', 'https://', $url);
        $url = is_string($secure) ? $secure : '';
    }
    if (preg_match('#^https://#i', $url) !== 1) {
        return '';
    }

    return $url;
}

function editor_dom_root(string $html): ?\DOMElement
{
    $document = new \DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="mhn-root">'.$html.'</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (! $loaded) {
        return null;
    }

    $node = $document->getElementsByTagName('div')->item(0);

    return $node instanceof \DOMElement ? $node : null;
}

function transform_children(\DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= transform_node($child);
    }

    return $html;
}

function transform_node(\DOMNode $node): string
{
    if ($node instanceof \DOMText) {
        return esc_html($node->textContent);
    }
    if (! $node instanceof \DOMElement) {
        return '';
    }

    $tag = strtolower($node->nodeName);
    if (in_array($tag, ['script', 'iframe', 'object', 'embed', 'form', 'style', 'link', 'meta', 'svg'], true)) {
        return '';
    }

    return match ($tag) {
        'img' => transform_image($node, false),
        'h2', 'h3' => transform_heading($node, $tag),
        'p' => transform_paragraph($node),
        'ul', 'ol' => transform_list($node, $tag),
        'blockquote' => transform_quote($node),
        'hr' => render_separator(),
        'a' => transform_anchor($node),
        'div' => transform_div($node),
        'strong', 'b' => transform_inline_wrap($node, 'strong'),
        'em', 'i' => transform_inline_wrap($node, 'em'),
        'br' => '<br>',
        'li' => transform_inline_children($node),
        default => transform_children($node),
    };
}

function transform_div(\DOMElement $node): string
{
    $inner = transform_children($node);
    if (! element_has_class($node, 'mhn-card-post')) {
        return $inner;
    }
    if (trim(wp_strip_all_tags($inner)) === '' && ! str_contains($inner, 'mhn-img')) {
        return '';
    }

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr><td class="mhn-group" style="padding:16px;background-color:#f7f9fc;">'.$inner.'</td></tr></table>';
}

function transform_paragraph(\DOMElement $node): string
{
    $button = sole_button($node);
    if ($button !== '') {
        return $button;
    }

    $inner = transform_children($node);
    if (trim(wp_strip_all_tags($inner)) === '' && ! str_contains($inner, '<img') && ! str_contains($inner, 'v:roundrect')) {
        return '';
    }
    if (str_contains($inner, 'v:roundrect')) {
        return $inner;
    }

    return '<p style="'.body_paragraph_style().'">'.$inner.'</p>';
}

function transform_heading(\DOMElement $node, string $tag): string
{
    $text = trim($node->textContent);
    if ($text === '') {
        return '';
    }

    $size = $tag === 'h2' ? 22 : 18;

    return '<'.$tag.' class="mhn-text" style="margin:0 0 12px;font-family:'.email_font_stack().';font-size:'.$size.'px;line-height:1.3;font-weight:700;color:#0d2e57;text-align:left;">'.esc_html($text).'</'.$tag.'>';
}

function transform_list(\DOMElement $node, string $tag): string
{
    $items = '';
    foreach ($node->childNodes as $child) {
        if (! $child instanceof \DOMElement || strtolower($child->nodeName) !== 'li') {
            continue;
        }
        $text = transform_inline_children($child);
        if (trim(wp_strip_all_tags($text)) === '') {
            continue;
        }
        $items .= '<li style="margin:0 0 8px;">'.$text.'</li>';
    }
    if ($items === '') {
        return '';
    }

    return '<'.$tag.' class="mhn-text" style="margin:0 0 16px;padding-left:20px;font-size:16px;line-height:1.6;color:#0b1220;text-align:left;">'.$items.'</'.$tag.'>';
}

function transform_quote(\DOMElement $node): string
{
    $text = transform_children($node);
    if (trim(wp_strip_all_tags($text)) === '') {
        return '';
    }

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr><td class="mhn-text" style="border-left:3px solid #0d2e57;padding:4px 0 4px 16px;font-size:16px;line-height:1.6;color:#243041;font-style:italic;text-align:left;">'.$text.'</td></tr></table>';
}

function transform_anchor(\DOMElement $node): string
{
    if (element_has_class($node, 'mhn-email-button')) {
        $href = editor_link_url($node->getAttribute('href'));
        $label = trim($node->textContent);
        if ($href === '' || $label === '') {
            return '';
        }

        return letter_button($label, $href);
    }

    $image = sole_image($node);
    $href = editor_link_url($node->getAttribute('href'));
    if ($image !== null) {
        $img = transform_image($image, element_has_class($image, 'mhn-thumb'));
        if ($img === '' || $href === '') {
            return $img;
        }

        return '<a href="'.esc_url($href).'" style="color:#0d2e57;text-decoration:underline;">'.$img.'</a>';
    }

    $label = transform_inline_children($node);
    if ($href === '' || trim(wp_strip_all_tags($label)) === '') {
        return '';
    }

    return '<a href="'.esc_url($href).'" style="color:#0d2e57;font-size:16px;line-height:1.4;text-decoration:underline;display:inline-block;min-height:44px;">'.$label.'</a>';
}

function transform_image(\DOMElement $node, bool $thumb): string
{
    $src = editor_https_url($node->getAttribute('src'));
    if ($src === '') {
        return '';
    }

    $class = $node->getAttribute('class');
    $thumb = $thumb || str_contains($class, 'mhn-thumb');
    $cap = $thumb ? 280 : 600;
    $width = (int) $node->getAttribute('width');
    $height = (int) $node->getAttribute('height');
    if (preg_match('/wp-image-(\d+)/', $class, $match) === 1 && function_exists('wp_get_attachment_image_src')) {
        $file = wp_get_attachment_image_src((int) $match[1], 'large');
        if (is_array($file)) {
            if ($width < 1) {
                $width = (int) $file[1];
            }
            if ($height < 1) {
                $height = (int) $file[2];
            }
            $fileSrc = editor_https_url((string) $file[0]);
            if ($fileSrc !== '') {
                $src = $fileSrc;
            }
        }
    }
    if ($width < 1) {
        $width = $cap;
    }
    if ($width > $cap && $width > 0) {
        $height = $height > 0 ? (int) round($height * ($cap / $width)) : 0;
        $width = $cap;
    }
    if ($width > 600) {
        $height = $height > 0 ? (int) round($height * (600 / $width)) : 0;
        $width = 600;
    }
    if ($height < 1) {
        $height = (int) round($width * 0.56);
    }

    $alt = $node->getAttribute('alt');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    return '<img class="mhn-img" src="'.esc_url($src).'" alt="'.esc_attr($alt).'" width="'.esc_attr((string) $width).'" height="'.esc_attr((string) $height).'" style="display:block;width:100%;max-width:'.$width.'px;height:auto;border:0;margin:0 0 16px;color:#0b1220;background-color:#eef3f9;font-family:'.$font.';font-size:16px;line-height:1.5;">';
}

function transform_inline_wrap(\DOMElement $node, string $tag): string
{
    $inner = transform_inline_children($node);
    if (trim(wp_strip_all_tags($inner)) === '') {
        return '';
    }

    return '<'.$tag.'>'.$inner.'</'.$tag.'>';
}

function transform_inline_children(\DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        if ($child instanceof \DOMText) {
            $html .= esc_html($child->textContent);

            continue;
        }
        if (! $child instanceof \DOMElement) {
            continue;
        }
        $tag = strtolower($child->nodeName);
        if ($tag === 'br') {
            $html .= '<br>';

            continue;
        }
        if ($tag === 'strong' || $tag === 'b' || $tag === 'em' || $tag === 'i') {
            $html .= transform_inline_wrap($child, $tag === 'b' ? 'strong' : ($tag === 'i' ? 'em' : $tag));

            continue;
        }
        if ($tag === 'a') {
            $html .= transform_anchor($child);

            continue;
        }
        $html .= transform_inline_children($child);
    }

    return $html;
}

function sole_button(\DOMElement $node): string
{
    $link = null;
    foreach ($node->childNodes as $child) {
        if ($child instanceof \DOMText && trim($child->textContent) === '') {
            continue;
        }
        if ($link !== null || ! $child instanceof \DOMElement || strtolower($child->nodeName) !== 'a' || ! element_has_class($child, 'mhn-email-button')) {
            return '';
        }
        $link = $child;
    }
    if (! $link instanceof \DOMElement) {
        return '';
    }

    $href = editor_link_url($link->getAttribute('href'));
    $label = trim($link->textContent);
    if ($href === '' || $label === '') {
        return '';
    }

    return letter_button($label, $href);
}

function sole_image(\DOMElement $node): ?\DOMElement
{
    $image = null;
    foreach ($node->childNodes as $child) {
        if ($child instanceof \DOMText && trim($child->textContent) === '') {
            continue;
        }
        if ($image !== null || ! $child instanceof \DOMElement || strtolower($child->nodeName) !== 'img') {
            return null;
        }
        $image = $child;
    }

    return $image;
}

function element_has_class(\DOMElement $node, string $class): bool
{
    $tokens = preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [];

    return in_array($class, $tokens, true);
}
