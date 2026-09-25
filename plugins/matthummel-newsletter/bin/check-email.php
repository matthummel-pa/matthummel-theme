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

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__).'/');
}

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

function esc_attr__(string $text, string $domain = 'default'): string
{
    return esc_attr(__($text, $domain));
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
function sanitize_key(string $key): string
{
    $key = strtolower($key);
    $clean = preg_replace('/[^a-z0-9_\-]/', '', $key);

    return is_string($clean) ? $clean : '';
}

function plugins_url(string $path = '', string $plugin = ''): string
{
    unset($plugin);

    return 'https://matthummel.com/wp-content/plugins/matthummel-newsletter/'.ltrim($path, '/');
}

function wp_salt(string $scheme = 'auth'): string
{
    unset($scheme);

    return 'mhn-check-salt';
}

function wp_date(string $format, ?int $timestamp = null, mixed $timezone = null): string
{
    unset($timezone);

    return gmdate($format, $timestamp ?? time());
}

function page_url(string $slug): string
{
    return 'https://matthummel.com/'.$slug.'/';
}

function add_query_arg(mixed $key, mixed $value = '', string $url = ''): string
{
    if (is_array($key)) {
        $base = is_string($value) ? $value : '';
        $query = http_build_query($key);
        if ($base === '' || $query === '') {
            return $base;
        }

        return $base.(str_contains($base, '?') ? '&' : '?').$query;
    }

    $pair = rawurlencode((string) $key).'='.rawurlencode((string) $value);
    if ($url === '') {
        return $pair;
    }

    return $url.(str_contains($url, '?') ? '&' : '?').$pair;
}

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

if (! defined('MHN_FILE')) {
    define('MHN_FILE', dirname(__DIR__).'/matthummel-newsletter.php');
}

require dirname(__DIR__).'/includes/options.php';
require dirname(__DIR__).'/includes/blocks.php';
require dirname(__DIR__).'/includes/render.php';
require dirname(__DIR__).'/includes/layouts.php';
require dirname(__DIR__).'/includes/a11y.php';

use function MattHummel\Newsletter\apply_layout;
use function MattHummel\Newsletter\apply_merge;
use function MattHummel\Newsletter\apply_person_tags;
use function MattHummel\Newsletter\audit_confirm_flow;
use function MattHummel\Newsletter\audit_html;
use function MattHummel\Newsletter\audit_plugin_sources;
use function MattHummel\Newsletter\compose_advanced_letter;
use function MattHummel\Newsletter\email_document;
use function MattHummel\Newsletter\layout_preview_html;
use function MattHummel\Newsletter\layouts;
use function MattHummel\Newsletter\plain_text;
use function MattHummel\Newsletter\render_blocks;

if (defined('MHN_LIB_ONLY')) {
    return;
}

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

$present = apply_person_tags('Hi {first_name} {last_name} ({full_name})', [
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
], true);
$missing = apply_person_tags('Hi {first_name|there},', ['first_name' => ''], true);
$escaped = apply_person_tags('{first_name}', ['first_name' => '<script>'], true);
$plainName = apply_person_tags('{first_name}', ['first_name' => '<script>'], false);
if ($present === 'Hi Ada Lovelace (Ada Lovelace)' && $missing === 'Hi there,' && $escaped === '&lt;script&gt;' && $plainName === '<script>') {
    fwrite(STDOUT, "pass  merge tags\n");
} else {
    $failed = true;
    fwrite(STDERR, "fail  merge tags\n");
}

foreach (array_keys(layouts()) as $layoutId) {
    $html = layout_preview_html($layoutId);
    $audit = audit_html($html, plain_text($html), 'Gettysburg, PA');
    $problems = $audit['errors'];
    $hrefs = [];
    preg_match_all('/\shref=(["\'])(.*?)\1/i', $html, $hrefs);
    $badHref = false;
    foreach ($hrefs[2] as $href) {
        if ($href !== '#') {
            $badHref = true;
            $problems[] = 'Preview link is not a placeholder: '.$href;
        }
    }
    if (str_contains($html, 'mhn_open') || str_contains($html, 'mhn_click') || str_contains($html, 'mhn_unsub')) {
        $problems[] = 'Preview still points at the tracker or an unsubscribe endpoint.';
    }
    if ($layoutId === 'welcome') {
        if (! str_contains($html, 'You are on the list') || ! str_contains($html, 'I keep the address on this site')) {
            $problems[] = 'Welcome preview is missing its copy.';
        }
        if (str_contains($html, 'Here is what I have been building.')) {
            $problems[] = 'Welcome preview includes the standard intro.';
        }
    } else {
        if (! str_contains($html, 'Here is what I have been building.') || ! str_contains($html, 'Talk soon,')) {
            $problems[] = 'Letter preview is missing the reusable intro or sign-off.';
        }
        if (! str_contains($html, 'Hi there,')) {
            $problems[] = 'Letter preview did not merge {first_name|there} to there.';
        }
    }
    $visible = preg_replace('/<div class="mhn-preheader\b.*?<\/div>/is', '', $html);
    $visible = is_string($visible) ? $visible : $html;
    $imageAt = strpos($visible, 'class="mhn-img"');
    $introAt = strpos($visible, 'Here is what I have been building.');
    if ($layoutId === 'plain' && $imageAt !== false) {
        $problems[] = 'Plain preview still has a featured image.';
    }
    if ($layoutId === 'feature' && ($imageAt === false || $introAt === false || $imageAt > $introAt)) {
        $problems[] = 'Feature preview does not put the image above the intro.';
    }
    if ($layoutId === 'standard' && ($imageAt === false || $introAt === false || $introAt > $imageAt)) {
        $problems[] = 'Standard preview does not lead with the intro.';
    }
    if ($badHref) {
        $problems[] = 'A preview href was not #.';
    }
    if ($problems === []) {
        fwrite(STDOUT, "pass  layout {$layoutId}\n");

        continue;
    }
    $failed = true;
    fwrite(STDERR, "fail  layout {$layoutId}\n");
    foreach ($problems as $problem) {
        fwrite(STDERR, "  - {$problem}\n");
    }
}

$samplePerson = [
    'id' => '0',
    'email' => 'you@example.com',
    'first_name' => '',
    'last_name' => '',
    'status' => 'preview',
];
$issuePieces = render_blocks($image."\n\n".$note, 'A desk by a window');
$simpleLetter = apply_layout('standard', $issuePieces);
$simpleDoc = apply_merge(email_document('Notes from the workshop', 'Here is what I have been building.', $simpleLetter, false, 0), $samplePerson, 0, true);
$simpleProblems = [];
if (! str_contains($simpleDoc, 'Here is what I have been building.') || str_contains($simpleDoc, 'Shop notes') || str_contains($simpleDoc, 'Read the note')) {
    $simpleProblems[] = 'A simple letter picked up advanced blocks.';
}
if ($simpleProblems === []) {
    fwrite(STDOUT, "pass  simple editor\n");
} else {
    $failed = true;
    fwrite(STDERR, "fail  simple editor\n");
    foreach ($simpleProblems as $problem) {
        fwrite(STDERR, "  - {$problem}\n");
    }
}

$advancedBlocks = [
    'intro' => 'A custom intro for this issue.',
    'heading' => 'Shop notes',
    'body' => "The body of this issue.\n\nHi {first_name|there},",
    'button_label' => 'Read the note',
    'button_url' => 'https://matthummel.com/notes/',
    'signoff' => 'See you,',
];
$advancedInner = compose_advanced_letter($advancedBlocks, 'feature', $issuePieces, 'Matt Hummel');
$advancedDoc = apply_merge(email_document('Shop notes', 'A custom intro for this issue.', $advancedInner, false, 0), $samplePerson, 0, true);
$advancedAudit = audit_html($advancedDoc, plain_text($advancedDoc), 'Gettysburg, PA');
$advancedProblems = $advancedAudit['errors'];
foreach (['A custom intro for this issue.', 'Shop notes', 'Read the note', 'See you,', 'Hi there,'] as $needle) {
    if (! str_contains($advancedDoc, $needle)) {
        $advancedProblems[] = 'Advanced letter is missing '.$needle;
    }
}
$advancedVisible = preg_replace('/<div class="mhn-preheader\b.*?<\/div>/is', '', $advancedDoc) ?? $advancedDoc;
$advancedImage = strpos($advancedVisible, 'class="mhn-img"');
$advancedHeading = strpos($advancedVisible, '<h1');
if ($advancedImage === false || $advancedHeading === false || $advancedImage > $advancedHeading) {
    $advancedProblems[] = 'Feature layout did not put the image above the heading.';
}
if (str_contains($advancedDoc, 'mhn_open') || str_contains($advancedDoc, 'mhn_click')) {
    $advancedProblems[] = 'Advanced letter called the tracker.';
}
$plainAdvanced = compose_advanced_letter($advancedBlocks, 'plain', $issuePieces, 'Matt Hummel');
if (str_contains($plainAdvanced, 'class="mhn-img"')) {
    $advancedProblems[] = 'Plain advanced letter still has a featured image.';
}
if (! str_contains($plainAdvanced, 'Read the note')) {
    $advancedProblems[] = 'Plain advanced letter dropped the button.';
}
if ($advancedProblems === []) {
    fwrite(STDOUT, "pass  advanced editor\n");
} else {
    $failed = true;
    fwrite(STDERR, "fail  advanced editor\n");
    foreach ($advancedProblems as $problem) {
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
