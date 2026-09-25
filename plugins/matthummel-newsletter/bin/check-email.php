<?php

declare(strict_types=1);

/**
 * Render the newsletter templates without WordPress and check accessibility rules.
 *
 * Usage: php plugins/matthummel-newsletter/bin/check-email.php
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this from the command line.\n");
    exit(1);
}

define('ABSPATH', dirname(__DIR__).'/');

function get_option(string $option, mixed $default = false): mixed
{
    return match ($option) {
        'admin_email' => 'owner@example.com',
        'mhn_settings' => [],
        default => $default,
    };
}

function get_bloginfo(string $show = '', string $filter = 'raw'): string
{
    unset($filter);

    return match ($show) {
        'language' => 'en-US',
        'name' => 'Matt Hummel',
        default => '',
    };
}

function home_url(string $path = '', ?string $scheme = null): string
{
    unset($scheme);
    if ($path === '' || $path === '/') {
        return 'https://matthummel.com/';
    }

    return 'https://matthummel.com/'.ltrim($path, '/');
}

function get_site_icon_url(int $size = 512, string $url = '', int $blog_id = 0): string
{
    unset($size, $url, $blog_id);

    return 'https://matthummel.com/icon.png';
}

function is_rtl(): bool
{
    return false;
}

function wp_parse_url(string $url, int $component = -1): mixed
{
    if ($component === -1) {
        return parse_url($url);
    }

    return parse_url($url, $component);
}

function __(string $text, string $domain = 'default'): string
{
    unset($domain);

    return $text;
}

function esc_html__(string $text, string $domain = 'default'): string
{
    return esc_html(__($text, $domain));
}

function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function esc_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || str_contains($url, '*|') || preg_match('#^(https?:)?//#i', $url) === 1 || str_starts_with($url, '/')) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    return '';
}

function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
{
    unset($remove_breaks);

    return trim(strip_tags($text));
}

/**
 * @param  array<string, array<string, bool>>  $allowed
 */
function wp_kses(string $html, array $allowed): string
{
    $tags = '';
    foreach (array_keys($allowed) as $tag) {
        $tags .= '<'.$tag.'>';
    }

    return strip_tags($html, $tags);
}

/**
 * @return list<array<string, mixed>>
 */
function parse_blocks(string $content): array
{
    $blocks = [];
    $offset = 0;
    $length = strlen($content);
    while ($offset < $length) {
        $next = strpos($content, '<!-- wp:', $offset);
        if ($next === false) {
            break;
        }
        $offset = $next;
        if (preg_match('/<!--\s+wp:([a-z0-9\/-]+)(\s+(\{.*?\}))?\s+(\/)?-->/', $content, $match, 0, $offset) !== 1) {
            break;
        }
        $name = $match[1];
        $selfClosing = ($match[4] ?? '') === '/';
        $start = $offset + strlen($match[0]);
        $attrs = [];
        if (($match[3] ?? '') !== '') {
            $decoded = json_decode($match[3], true);
            if (is_array($decoded)) {
                $attrs = $decoded;
            }
        }
        if ($selfClosing) {
            $offset = $start;

            continue;
        }
        $close = '<!-- /wp:'.$name.' -->';
        $end = strpos($content, $close, $start);
        if ($end === false) {
            break;
        }
        $blocks[] = [
            'blockName' => str_contains($name, '/') ? $name : 'core/'.$name,
            'attrs' => $attrs,
            'innerHTML' => substr($content, $start, $end - $start),
            'innerBlocks' => [],
        ];
        $offset = $end + strlen($close);
    }

    return $blocks;
}

require dirname(__DIR__).'/includes/options.php';
require dirname(__DIR__).'/includes/blocks.php';
require dirname(__DIR__).'/includes/render.php';
require dirname(__DIR__).'/includes/a11y.php';

use function MattHummel\Newsletter\audit_confirm_flow;
use function MattHummel\Newsletter\audit_html;
use function MattHummel\Newsletter\audit_plugin_sources;
use function MattHummel\Newsletter\email_document;
use function MattHummel\Newsletter\plain_text;
use function MattHummel\Newsletter\render_blocks;

$image = '<!-- wp:image {"url":"https://matthummel.com/photo.jpg","alt":"A desk by a window"} -->'
    .'<figure><img src="https://matthummel.com/photo.jpg" alt="A desk by a window"/></figure>'
    .'<!-- /wp:image -->';
$note = '<!-- wp:paragraph -->'
    .'<p>A short note about the work. <a href="https://matthummel.com/notes/">See the notes on quiet builds</a>.</p>'
    .'<!-- /wp:paragraph -->';

$fixtures = [
    'blog-update' => '<!-- wp:heading {"level":1} -->'
        .'<h1 class="wp-block-heading">A quieter way to hear about new posts</h1>'
        .'<!-- /wp:heading -->'."\n\n".$image."\n\n".$note."\n\n"
        .'<!-- wp:buttons --><div class="wp-block-buttons"><a href="https://matthummel.com/notes/">Read more: A quieter way to hear about new posts</a></div><!-- /wp:buttons -->',
    'blog-digest' => $note."\n\n"
        .'<!-- wp:heading {"level":2} --><h2>First note</h2><!-- /wp:heading -->'."\n\n".$image."\n\n"
        .'<!-- wp:buttons --><div><a href="https://matthummel.com/first/">Read more: First note</a></div><!-- /wp:buttons -->'."\n\n"
        .'<!-- wp:heading {"level":2} --><h2>Second note</h2><!-- /wp:heading -->'."\n\n"
        .'<!-- wp:buttons --><div><a href="https://matthummel.com/second/">Read more: Second note</a></div><!-- /wp:buttons -->',
    'custom' => $note."\n\n".$image."\n\n"
        .'<!-- wp:buttons --><div><a href="https://matthummel.com/notes/">See the latest notes</a></div><!-- /wp:buttons -->',
];

$failed = false;
foreach ($fixtures as $name => $blocks) {
    $body = render_blocks($blocks, 'Unused fallback');
    $html = email_document('Notes from the workshop', 'A short preview of this issue.', $body, false, 0);
    $audit = audit_html($html, plain_text($html), 'Gettysburg, PA');
    $problems = array_merge($audit['errors'], $audit['warnings']);
    if ($problems === []) {
        fwrite(STDOUT, "pass  {$name}\n");

        continue;
    }
    $failed = true;
    fwrite(STDERR, "fail  {$name}\n");
    foreach ($problems as $problem) {
        fwrite(STDERR, "  - {$problem}\n");
    }
}

$sources = audit_plugin_sources();
if ($sources['errors'] === []) {
    fwrite(STDOUT, "pass  headers and defaults\n");
} else {
    $failed = true;
    fwrite(STDERR, "fail  headers and defaults\n");
    foreach ($sources['errors'] as $problem) {
        fwrite(STDERR, "  - {$problem}\n");
    }
}

$confirmFlow = audit_confirm_flow();
if ($confirmFlow === []) {
    fwrite(STDOUT, "pass  confirm link\n");
} else {
    $failed = true;
    fwrite(STDERR, "fail  confirm link\n");
    foreach ($confirmFlow as $problem) {
        fwrite(STDERR, "  - {$problem}\n");
    }
}

$broken = audit_html('<html><body><table><img src="x.jpg"><p style="text-align:center"><a href="https://example.com">click here</a></p></table></body></html>', '');
$mustMention = ['lang', 'dir', 'title', 'presentation', 'h1', 'alt', 'unsubscribe', 'plain-text'];
$blob = strtolower(implode(' ', $broken['errors']));
foreach ($mustMention as $needle) {
    if (! str_contains($blob, $needle)) {
        $failed = true;
        fwrite(STDERR, "fail  broken sample did not mention {$needle}\n");
    }
}
if ($broken['warnings'] === []) {
    $failed = true;
    fwrite(STDERR, "fail  broken sample did not warn on vague link text\n");
}

exit($failed ? 1 : 0);
