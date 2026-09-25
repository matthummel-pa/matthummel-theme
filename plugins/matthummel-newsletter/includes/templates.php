<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Email templates. Filter `mhn_email_templates` to add another.
 *
 * @return array<string, array{label: string, summary: string, min_posts: int, max_posts: int, has_image: bool, has_button: bool, has_ps: bool}>
 */
function email_templates(): array
{
    $templates = [
        'blog-update' => [
            'label' => __('Blog update', 'matthummel-newsletter'),
            'summary' => __('One post, with a note from you above it and an optional P.S. below.', 'matthummel-newsletter'),
            'min_posts' => 1,
            'max_posts' => 1,
            'has_image' => false,
            'has_button' => false,
            'has_ps' => true,
        ],
        'blog-digest' => [
            'label' => __('Blog digest', 'matthummel-newsletter'),
            'summary' => __('Two to six posts in a short card list, plus your note and an optional P.S.', 'matthummel-newsletter'),
            'min_posts' => 2,
            'max_posts' => 6,
            'has_image' => false,
            'has_button' => false,
            'has_ps' => true,
        ],
        'custom' => [
            'label' => __('Custom message', 'matthummel-newsletter'),
            'summary' => __('A letter or announcement, with an optional image and button.', 'matthummel-newsletter'),
            'min_posts' => 0,
            'max_posts' => 0,
            'has_image' => true,
            'has_button' => true,
            'has_ps' => false,
        ],
    ];

    $filtered = apply_filters('mhn_email_templates', $templates);
    if (! is_array($filtered)) {
        $filtered = $templates;
    }

    $ready = [];
    foreach ($filtered as $slug => $template) {
        $key = sanitize_key((string) $slug);
        if ($key === '' || ! is_array($template)) {
            continue;
        }
        $min = max(0, (int) ($template['min_posts'] ?? 0));
        $max = max($min, (int) ($template['max_posts'] ?? 0));
        $ready[$key] = [
            'label' => (string) ($template['label'] ?? $key),
            'summary' => (string) ($template['summary'] ?? ''),
            'min_posts' => $min,
            'max_posts' => $max,
            'has_image' => ! empty($template['has_image']),
            'has_button' => ! empty($template['has_button']),
            'has_ps' => ! empty($template['has_ps']),
        ];
    }

    return $ready;
}

/**
 * @return array{label: string, summary: string, min_posts: int, max_posts: int, has_image: bool, has_button: bool, has_ps: bool}|null
 */
function email_template(string $slug): ?array
{
    $templates = email_templates();

    return $templates[$slug] ?? null;
}

function register_patterns(): void
{
    if (! function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('mhn', [
        'label' => __('Newsletter', 'matthummel-newsletter'),
    ]);

    foreach (email_templates() as $slug => $template) {
        register_block_pattern('mhn/'.$slug, [
            'title' => $template['label'],
            'description' => $template['summary'],
            'categories' => ['mhn'],
            'postTypes' => ['newsletter_issue'],
            'content' => pattern_content($slug),
        ]);
    }
}

function pattern_content(string $slug): string
{
    $template = email_template($slug);
    if ($template === null) {
        return '';
    }

    $parts = [];
    $noteBlocks = rich_text_blocks(default_note_html());
    if ($noteBlocks !== '') {
        $parts[] = $noteBlocks;
    }

    if ($template['max_posts'] > 0) {
        $wanted = $slug === 'blog-digest' ? 3 : 1;
        $wanted = max($template['min_posts'], min($template['max_posts'], $wanted));
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $wanted,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);
        $cards = $template['max_posts'] > 1;
        $found = 0;
        foreach (is_array($posts) ? $posts : [] as $post) {
            if ($post instanceof \WP_Post) {
                $parts[] = post_blocks($post, $cards);
                $found++;
            }
        }
        while ($found < $template['min_posts']) {
            $parts[] = placeholder_post_blocks($cards, $found + 1);
            $found++;
        }
    }

    if ($template['has_button']) {
        $parts[] = button_block(__('Read the latest note', 'matthummel-newsletter'), home_url('/'));
    }

    if ($template['has_ps']) {
        $ps = rich_text_blocks('<p>'.esc_html__('P.S. A last line, if you want one.', 'matthummel-newsletter').'</p>');
        if ($ps !== '') {
            $parts[] = $ps;
        }
    }

    return implode("\n\n", array_filter($parts));
}

function compile_issue(int $issueId): void
{
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return;
    }

    $slug = (string) get_post_meta($issueId, '_mhn_template', true);
    $template = email_template($slug);
    if ($template === null) {
        return;
    }

    $content = template_content($issueId, $template);
    wp_update_post([
        'ID' => $issueId,
        'post_content' => $content,
    ]);
    update_post_meta($issueId, '_mhn_compiled', hash('sha256', $content));
    update_post_meta($issueId, '_mhn_include_recent', '0');
}

/**
 * @param  array{label: string, summary: string, min_posts: int, max_posts: int, has_image: bool, has_button: bool, has_ps: bool}  $template
 */
function template_content(int $issueId, array $template): string
{
    $parts = [];

    if ($template['has_image']) {
        $image = image_block(
            (int) get_post_meta($issueId, '_mhn_image_id', true),
            (string) get_post_meta($issueId, '_mhn_image_alt', true)
        );
        if ($image !== '') {
            $parts[] = $image;
        }
    }

    $note = rich_text_blocks((string) get_post_meta($issueId, '_mhn_note', true));
    if ($note !== '') {
        $parts[] = $note;
    }

    if ($template['max_posts'] > 0) {
        $cards = $template['max_posts'] > 1;
        foreach (published_issue_posts($issueId) as $post) {
            $parts[] = post_blocks($post, $cards, $issueId);
        }
    }

    if ($template['has_button']) {
        $button = button_block(
            (string) get_post_meta($issueId, '_mhn_button_label', true),
            (string) get_post_meta($issueId, '_mhn_button_url', true)
        );
        if ($button !== '') {
            $parts[] = $button;
        }
    }

    if ($template['has_ps']) {
        $ps = rich_text_blocks((string) get_post_meta($issueId, '_mhn_ps', true));
        if ($ps !== '') {
            $parts[] = $ps;
        }
    }

    return implode("\n\n", $parts);
}

function rich_text_blocks(string $html): string
{
    $html = sanitize_rich_text($html);
    $html = trim($html);
    if ($html === '' || trim(wp_strip_all_tags($html)) === '') {
        return '';
    }

    if (preg_match('/<(p|ul|ol)\b/i', $html) !== 1) {
        $html = '<p>'.$html.'</p>';
    }

    $found = preg_match_all('/<(p|ul|ol)\b[^>]*>.*?<\/\1>/is', $html, $matches);
    if ($found === false || $found < 1) {
        return paragraph_block(wp_strip_all_tags($html));
    }

    $parts = [];
    foreach ($matches[0] as $chunk) {
        $block = rich_chunk_block($chunk);
        if ($block !== '') {
            $parts[] = $block;
        }
    }

    return implode("\n\n", $parts);
}

function rich_chunk_block(string $chunk): string
{
    if (preg_match('/^<ol\b/i', $chunk) === 1 || preg_match('/^<ul\b/i', $chunk) === 1) {
        $ordered = preg_match('/^<ol\b/i', $chunk) === 1;
        $items = '';
        if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $chunk, $lis) < 1) {
            return '';
        }
        foreach ($lis[1] as $li) {
            $text = email_kses((string) $li);
            if (trim(wp_strip_all_tags($text)) === '') {
                continue;
            }
            $items .= "<!-- wp:list-item -->\n<li>".$text."</li>\n<!-- /wp:list-item -->\n";
        }
        if ($items === '') {
            return '';
        }
        $tag = $ordered ? 'ol' : 'ul';
        $attrs = $ordered ? ' {"ordered":true}' : '';

        return "<!-- wp:list{$attrs} -->\n<{$tag} class=\"wp-block-list\">\n{$items}</{$tag}>\n<!-- /wp:list -->";
    }

    $inner = email_kses($chunk);
    if (trim(wp_strip_all_tags($inner)) === '') {
        return '';
    }
    if (preg_match('/^<p\b/i', $inner) !== 1) {
        $inner = '<p>'.$inner.'</p>';
    }

    return "<!-- wp:paragraph -->\n{$inner}\n<!-- /wp:paragraph -->";
}

function sanitize_rich_text(string $html): string
{
    $clean = wp_kses($html, [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'a' => ['href' => true],
        'ul' => [],
        'ol' => [],
        'li' => [],
    ]);
    $clean = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $clean) ?? $clean;

    return trim($clean);
}

function default_note_html(): string
{
    return '<p>'.esc_html(sprintf(
        /* translators: %s is the first-name merge tag. Leave the braces in place. */
        __('Hi %s,', 'matthummel-newsletter'),
        '{first_name|there}'
    )).'</p>';
}

function post_blocks(\WP_Post $post, bool $card, int $issueId = 0): string
{
    $parts = [];
    $title = html_entity_decode(get_the_title($post), ENT_QUOTES);
    $image = featured_image_block($post, $card, $issueId, $title);
    if ($image !== '') {
        $parts[] = $image;
    }
    $heading = heading_block($title, $card ? 2 : 1);
    if ($heading !== '') {
        $parts[] = $heading;
    }
    $excerpt = paragraph_block(post_plain_excerpt($post));
    if ($excerpt !== '') {
        $parts[] = $excerpt;
    }
    $url = get_permalink($post);
    $button = button_block(read_more_label($title), is_string($url) ? $url : home_url('/'));
    if ($button !== '') {
        $parts[] = $button;
    }

    $body = implode("\n\n", $parts);
    if (! $card || $body === '') {
        return $body;
    }

    return "<!-- wp:group -->\n<div class=\"wp-block-group\">\n{$body}\n</div>\n<!-- /wp:group -->";
}

function placeholder_post_blocks(bool $card, int $index): string
{
    $title = sprintf(
        /* translators: %d: placeholder number */
        __('Post title %d', 'matthummel-newsletter'),
        $index
    );
    $body = heading_block($title, $card ? 2 : 1);
    $body .= "\n\n".paragraph_block(__('A sentence or two from the post.', 'matthummel-newsletter'));
    $body .= "\n\n".button_block(read_more_label($title), home_url('/'));
    if (! $card) {
        return $body;
    }

    return "<!-- wp:group -->\n<div class=\"wp-block-group\">\n{$body}\n</div>\n<!-- /wp:group -->";
}

function featured_image_block(\WP_Post $post, bool $card, int $issueId, string $title): string
{
    if ($issueId > 0 && (string) get_post_meta($issueId, '_mhn_feature_show', true) === '0') {
        return '';
    }

    $attachmentId = (int) get_post_thumbnail_id($post);
    if (! $card && $issueId > 0) {
        $replace = (int) get_post_meta($issueId, '_mhn_feature_image_id', true);
        if ($replace > 0 && wp_attachment_is_image($replace)) {
            $attachmentId = $replace;
        }
    }

    $url = get_permalink($post);
    $href = is_string($url) ? $url : '';

    return image_block($attachmentId, '', $title, [
        'max_width' => $card ? 280 : 600,
        'href' => $href,
        'size' => $card ? 'medium' : 'large',
    ]);
}

/**
 * @param  array{max_width?: int, href?: string, size?: string}  $options
 */
function image_block(int $attachmentId, string $preferredAlt, string $fallbackAlt = '', array $options = []): string
{
    if ($attachmentId < 1) {
        return '';
    }

    $size = ($options['size'] ?? '') === 'medium' ? 'medium' : 'large';
    $image = wp_get_attachment_image_src($attachmentId, $size);
    if (! is_array($image)) {
        $image = wp_get_attachment_image_src($attachmentId, 'full');
    }
    if (! is_array($image) || (string) $image[0] === '') {
        return '';
    }

    $alt = trim($preferredAlt);
    if ($alt === '') {
        $alt = (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
    }
    if ($alt === '') {
        $alt = trim($fallbackAlt);
    }

    $maxWidth = (int) ($options['max_width'] ?? 600);
    if ($maxWidth < 1 || $maxWidth > 600) {
        $maxWidth = 600;
    }
    $href = trim((string) ($options['href'] ?? ''));
    $payload = [
        'id' => $attachmentId,
        'sizeSlug' => $size,
        'url' => (string) $image[0],
        'alt' => $alt,
        'maxWidth' => $maxWidth,
    ];
    if ($href !== '') {
        $payload['href'] = $href;
    }
    $encoded = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $encoded = is_string($encoded) ? $encoded : '{}';
    $img = '<img src="'.esc_url((string) $image[0]).'" alt="'.esc_attr($alt).'"/>';
    $figure = $href !== ''
        ? '<figure class="wp-block-image"><a href="'.esc_url($href).'">'.$img.'</a></figure>'
        : '<figure class="wp-block-image">'.$img.'</figure>';

    return '<!-- wp:image '.$encoded.' -->'.$figure.'<!-- /wp:image -->';
}

function heading_block(string $text, int $level): string
{
    $text = trim(wp_strip_all_tags($text));
    if ($text === '') {
        return '';
    }

    $level = max(1, min(3, $level));
    $tag = 'h'.$level;
    $attrs = $level === 2 ? '' : ' {"level":'.$level.'}';

    return "<!-- wp:heading{$attrs} -->\n<{$tag} class=\"wp-block-heading\">".esc_html($text)."</{$tag}>\n<!-- /wp:heading -->";
}

function paragraph_block(string $text): string
{
    $text = trim(wp_strip_all_tags($text));
    if ($text === '') {
        return '';
    }

    return "<!-- wp:paragraph -->\n<p>".esc_html($text)."</p>\n<!-- /wp:paragraph -->";
}

function read_more_label(string $title): string
{
    $title = trim(wp_strip_all_tags($title));
    if ($title === '') {
        return __('Read the latest note', 'matthummel-newsletter');
    }

    return sprintf(
        /* translators: %s: post title */
        __('Read more: %s', 'matthummel-newsletter'),
        $title
    );
}

function button_block(string $label, string $url): string
{
    $label = trim(wp_strip_all_tags($label));
    $url = esc_url_raw($url);
    if ($label === '' || $url === '') {
        return '';
    }

    return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n"
        .'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.esc_url($url).'">'
        .esc_html($label).'</a></div>'
        ."\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->";
}

function post_plain_excerpt(\WP_Post $post): string
{
    if (has_excerpt($post)) {
        return wp_strip_all_tags(get_the_excerpt($post));
    }

    return wp_trim_words(wp_strip_all_tags($post->post_content), 32, '…');
}

/**
 * @return list<int>
 */
function issue_post_ids(int $issueId): array
{
    $raw = (string) get_post_meta($issueId, '_mhn_post_ids', true);
    if ($raw === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $raw) as $part) {
        $id = absint($part);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * @return list<\WP_Post>
 */
function published_issue_posts(int $issueId): array
{
    $posts = [];
    foreach (issue_post_ids($issueId) as $id) {
        $post = get_post($id);
        if ($post instanceof \WP_Post && $post->post_type === 'post' && $post->post_status === 'publish') {
            $posts[] = $post;
        }
    }

    return $posts;
}

/**
 * @return list<int>
 */
function default_post_ids(string $slug): array
{
    $template = email_template($slug);
    if ($template === null || $template['max_posts'] < 1) {
        return [];
    }

    $count = $slug === 'blog-digest' ? 3 : 1;
    $count = max(1, min($template['max_posts'], $count));
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    $ids = [];
    foreach (is_array($posts) ? $posts : [] as $post) {
        if ($post instanceof \WP_Post) {
            $ids[] = (int) $post->ID;
        }
    }

    return $ids;
}

function suggest_subject(int $issueId): string
{
    $slug = (string) get_post_meta($issueId, '_mhn_template', true);
    $posts = published_issue_posts($issueId);
    if ($slug === 'blog-update' && isset($posts[0])) {
        return html_entity_decode(get_the_title($posts[0]), ENT_QUOTES);
    }
    if ($slug === 'blog-digest' && $posts !== []) {
        $first = html_entity_decode(get_the_title($posts[0]), ENT_QUOTES);
        if (count($posts) === 1) {
            return $first;
        }

        return $first.' '.__('and more', 'matthummel-newsletter');
    }

    $note = trim(wp_strip_all_tags((string) get_post_meta($issueId, '_mhn_note', true)));
    if ($note !== '') {
        $line = preg_split('/\R/u', $note) ?: [];
        $first = trim((string) ($line[0] ?? ''));
        if ($first !== '') {
            return mb_substr($first, 0, 80);
        }
    }

    $template = email_template($slug);

    return $template['label'] ?? __('Newsletter', 'matthummel-newsletter');
}

function suggest_preheader(int $issueId): string
{
    $note = trim(wp_strip_all_tags((string) get_post_meta($issueId, '_mhn_note', true)));
    if ($note !== '') {
        return mb_substr($note, 0, 140);
    }

    $posts = published_issue_posts($issueId);
    $slug = (string) get_post_meta($issueId, '_mhn_template', true);
    if ($slug === 'blog-digest' && count($posts) > 1) {
        $titles = [];
        foreach ($posts as $post) {
            $titles[] = html_entity_decode(get_the_title($post), ENT_QUOTES);
        }

        return mb_substr(implode(', ', $titles), 0, 140);
    }
    if (isset($posts[0])) {
        return mb_substr(post_plain_excerpt($posts[0]), 0, 140);
    }

    return '';
}

function fill_subject_defaults(int $issueId): void
{
    if (issue_layout_id($issueId) === 'welcome' && (string) get_post_meta($issueId, '_mhn_subject_auto', true) !== '0') {
        $subject = layout_copy()['welcome_subject'];
        $preheader = mb_substr(trim(wp_strip_all_tags(layout_copy()['welcome_body'])), 0, 140);
        update_post_meta($issueId, '_mhn_subject', $subject);
        update_post_meta($issueId, '_mhn_preheader', $preheader);
        update_post_meta($issueId, '_mhn_subject_auto', '1');
        if ($subject !== '') {
            wp_update_post([
                'ID' => $issueId,
                'post_title' => $subject,
            ]);
        }

        return;
    }

    if ((string) get_post_meta($issueId, '_mhn_subject_auto', true) === '0') {
        $subject = trim((string) get_post_meta($issueId, '_mhn_subject', true));
        if ($subject !== '') {
            wp_update_post([
                'ID' => $issueId,
                'post_title' => $subject,
            ]);
        }

        return;
    }

    $subject = suggest_subject($issueId);
    update_post_meta($issueId, '_mhn_subject', $subject);
    update_post_meta($issueId, '_mhn_preheader', mb_substr(suggest_preheader($issueId), 0, 140));
    update_post_meta($issueId, '_mhn_subject_auto', '1');
    if ($subject !== '') {
        wp_update_post([
            'ID' => $issueId,
            'post_title' => $subject,
        ]);
    }
}

function editor_diverged(int $issueId): bool
{
    $stored = (string) get_post_meta($issueId, '_mhn_compiled', true);
    if ($stored === '') {
        return false;
    }

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post) {
        return false;
    }

    return hash('sha256', (string) $post->post_content) !== $stored;
}
