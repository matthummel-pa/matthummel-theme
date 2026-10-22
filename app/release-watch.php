<?php

declare(strict_types=1);

/**
 * Release watch: data for the WordPress release blocks.
 *
 * Twice a day this reads Make WordPress Core for the next release: its schedule,
 * every item on the roadmap grouped by area, and the latest posts tagged for that
 * version. The result is kept in one option, so the blocks never call out to
 * WordPress.org while a visitor waits.
 *
 * Blocks that read it:
 * - mh/release-rundown     resources/blocks/release-rundown (name kept from the old MH Release Watch plugin so saved pages still work)
 * - matthummel/release-banner  resources/blocks/release-banner
 */

namespace App;

const MH_RW_OPTION = 'mh_release_watch';

const MH_RW_CRON = 'mh_release_watch_refresh';

const MH_RW_LOCK = 'mh_release_watch_lock';

const MH_RW_MAKE = 'https://make.wordpress.org/core/wp-json/wp/v2/';

const MH_RW_STABLE = 'https://api.wordpress.org/core/stable-check/1.0/';

/** Roadmap headings that are not feature areas. */
const MH_RW_SKIP_AREAS = ['table of contents', 'open to contributors', 'how to contribute', 'how to get involved'];

/**
 * Stored release data, or null before the first successful check.
 *
 * @return array{version: string, cycle_url: string, release_date: string, schedule: list<array{label: string, date: string}>, roadmap_url: string, areas: list<array{id: string, title: string, intro: string, items: list<array{title: string, url: string, summary: string}>}>, updates: list<array{type: string, slug: string, title: string, url: string, date: string, excerpt: string}>, checked: int, error: string}|null
 */
function mh_release_watch_data(): ?array
{
    $data = get_option(MH_RW_OPTION);
    if (! is_array($data) || empty($data['version'])) {
        // Nothing stored yet: ask cron to fill it rather than fetching during this request.
        if (! wp_next_scheduled(MH_RW_CRON.'_now')) {
            wp_schedule_single_event(time(), MH_RW_CRON.'_now');
        }

        return null;
    }

    return $data;
}

/**
 * Whole days from today (site timezone) to a Y-m-d date. Negative once it has passed.
 */
function mh_release_watch_days_until(string $ymd): ?int
{
    $tz = wp_timezone();
    $target = \DateTimeImmutable::createFromFormat('!Y-m-d', $ymd, $tz);
    if (! $target) {
        return null;
    }
    $today = new \DateTimeImmutable('today', $tz);

    return (int) $today->diff($target)->format('%r%a');
}

/**
 * Format a Y-m-d date with a PHP date format, without shifting it a day by timezone.
 */
function mh_release_watch_date(string $ymd, string $format): string
{
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $ymd, new \DateTimeZone('UTC'));

    return $date ? (string) wp_date($format, $date->getTimestamp(), new \DateTimeZone('UTC')) : '';
}

/* ---------------------------------------------------------------- schedule */

add_action('init', function (): void {
    if (! wp_next_scheduled(MH_RW_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'twicedaily', MH_RW_CRON);
    }
});

add_action(MH_RW_CRON, __NAMESPACE__.'\\mh_release_watch_refresh');
add_action(MH_RW_CRON.'_now', __NAMESPACE__.'\\mh_release_watch_refresh');

/** Stop the checks when the theme is switched off. */
add_action('switch_theme', function (): void {
    wp_clear_scheduled_hook(MH_RW_CRON);
    wp_clear_scheduled_hook(MH_RW_CRON.'_now');
});

/**
 * WP-CLI: `wp mh release-watch` refreshes the data now and prints the result.
 */
if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
    \WP_CLI::add_command('mh release-watch', function (): void {
        delete_transient(MH_RW_LOCK);
        $result = mh_release_watch_refresh();
        if (is_wp_error($result)) {
            \WP_CLI::error($result->get_error_message());
        }
        \WP_CLI::success(sprintf(
            'WordPress %s: %d schedule rows, %d areas, %d updates.',
            $result['version'],
            count($result['schedule']),
            count($result['areas']),
            count($result['updates'])
        ));
    });
}

/**
 * Fetch everything and store it. Keeps the last good data when WordPress.org is down.
 *
 * @return array<string, mixed>|\WP_Error
 */
function mh_release_watch_refresh()
{
    if (get_transient(MH_RW_LOCK)) {
        return new \WP_Error('mh_rw_locked', 'A release check is already running.');
    }
    set_transient(MH_RW_LOCK, 1, 5 * MINUTE_IN_SECONDS);

    $old = get_option(MH_RW_OPTION);
    $old = is_array($old) ? $old : [];

    $data = mh_release_watch_fetch();
    delete_transient(MH_RW_LOCK);

    if (is_wp_error($data)) {
        // Fail soft: keep showing the last good copy and note why it is stale.
        if ($old) {
            $old['error'] = $data->get_error_message();
            update_option(MH_RW_OPTION, $old, false);
        }
        error_log('[release-watch] '.$data->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- cron has no screen to report to.

        return $data;
    }

    update_option(MH_RW_OPTION, $data, false);

    return $data;
}

/* ---------------------------------------------------------------- fetching */

/**
 * @return array<string, mixed>|\WP_Error
 */
function mh_release_watch_fetch()
{
    $stable = mh_rw_get_json(MH_RW_STABLE);
    if (is_wp_error($stable)) {
        return $stable;
    }
    $latest = (string) (array_search('latest', (array) $stable, true) ?: '');
    if (! preg_match('/^(\d+)\.(\d+)/', $latest, $m)) {
        return new \WP_Error('mh_rw_latest', 'Could not tell the current WordPress version.');
    }

    // The next release is either the next minor (7.2) or the next major (8.0), whichever has a page.
    $page = null;
    $version = '';
    foreach ([$m[1].'.'.((int) $m[2] + 1), ((int) $m[1] + 1).'.0'] as $candidate) {
        $found = mh_rw_get_json(MH_RW_MAKE.'pages?slug='.str_replace('.', '-', $candidate).'&_fields=link,content');
        if (is_wp_error($found)) {
            return $found;
        }
        if (! empty($found[0]['content']['rendered'])) {
            $page = $found[0];
            $version = $candidate;
            break;
        }
    }
    if (! $page) {
        return new \WP_Error('mh_rw_no_cycle', 'No release page for the next WordPress version yet.');
    }

    [$schedule, $roadmap_link] = mh_rw_parse_cycle((string) $page['content']['rendered']);
    $release_date = '';
    foreach ($schedule as $row) {
        if ($row['label'] === 'Release') {
            $release_date = $row['date'];
        }
    }

    $roadmap_url = '';
    $areas = [];
    if ($roadmap_link !== '') {
        $slug = basename(untrailingslashit((string) wp_parse_url($roadmap_link, PHP_URL_PATH)));
        $post = mh_rw_get_json(MH_RW_MAKE.'posts?slug='.rawurlencode($slug).'&_fields=link,content');
        if (! is_wp_error($post) && ! empty($post[0]['content']['rendered'])) {
            $roadmap_url = esc_url_raw((string) $post[0]['link']);
            $areas = mh_rw_parse_roadmap((string) $post[0]['content']['rendered']);
        }
    }

    return [
        'version' => $version,
        'cycle_url' => esc_url_raw((string) $page['link']),
        'release_date' => $release_date,
        'schedule' => $schedule,
        'roadmap_url' => $roadmap_url,
        'areas' => $areas,
        'updates' => mh_rw_fetch_updates($version),
        'checked' => time(),
        'error' => '',
    ];
}

/**
 * GET a URL and decode its JSON body.
 *
 * @return array<mixed>|\WP_Error
 */
function mh_rw_get_json(string $url)
{
    $res = wp_remote_get($url, [
        'timeout' => 20,
        'user-agent' => 'matthummel.com release watch; '.home_url(),
    ]);
    if (is_wp_error($res)) {
        return $res;
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code !== 200) {
        return new \WP_Error('mh_rw_http', sprintf('%s answered HTTP %d.', (string) wp_parse_url($url, PHP_URL_HOST), $code));
    }
    $json = json_decode((string) wp_remote_retrieve_body($res), true);

    return is_array($json) ? $json : new \WP_Error('mh_rw_json', 'WordPress.org sent something that is not JSON.');
}

/** Plain text from HTML: tags gone, entities decoded, whitespace collapsed. */
function mh_rw_text(string $html): string
{
    $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

/** Cut text at a word boundary with an ellipsis. */
function mh_rw_shorten(string $text, int $max): string
{
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max);
    $space = mb_strrpos($cut, ' ');

    return rtrim(mb_substr($cut, 0, $space ?: $max), " ,;:.\t").'…';
}

/** A link from a Make post, with the href.li referrer wrapper removed. */
function mh_rw_url(string $href): string
{
    $href = (string) preg_replace('#^https?://href\.li/\?#', '', trim($href));

    return esc_url_raw($href, ['http', 'https']);
}

/**
 * Parse an HTML fragment. Glossary tooltips are removed so they do not leak into labels.
 *
 * @return array{0: \DOMDocument, 1: \DOMXPath, 2: \DOMElement}
 */
function mh_rw_dom(string $html): array
{
    $doc = new \DOMDocument;
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div id="mh-rw-root">'.$html.'</div>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $xpath = new \DOMXPath($doc);
    foreach (iterator_to_array($xpath->query("//*[contains(@class,'glossary-item-hidden-content')]") ?: []) as $node) {
        $node->parentNode?->removeChild($node);
    }
    $root = $doc->getElementById('mh-rw-root');

    return [$doc, $xpath, $root instanceof \DOMElement ? $root : $doc->createElement('div')];
}

/**
 * Release page: the schedule table and the roadmap link.
 *
 * @return array{0: list<array{label: string, date: string}>, 1: string}
 */
function mh_rw_parse_cycle(string $html): array
{
    [, $xpath] = mh_rw_dom($html);
    $schedule = [];
    foreach ($xpath->query('//tr') ?: [] as $tr) {
        $cells = $tr instanceof \DOMElement ? $tr->getElementsByTagName('td') : null;
        if (! $cells || $cells->length < 2) {
            continue;
        }
        // "~August 2026" rows (alpha) have no exact date and are skipped.
        $when = \DateTimeImmutable::createFromFormat('!j F Y', mh_rw_text((string) $cells->item(0)?->textContent), new \DateTimeZone('UTC'));
        if (! $when) {
            continue;
        }
        $label = mh_rw_milestone_label(mh_rw_text((string) $cells->item(1)?->textContent));
        if ($label !== '') {
            $schedule[] = ['label' => $label, 'date' => $when->format('Y-m-d')];
        }
    }

    $link = $xpath->query("//a[contains(@href,'roadmap-to')]")?->item(0);
    $roadmap = $link instanceof \DOMElement ? mh_rw_url($link->getAttribute('href')) : '';

    return [$schedule, $roadmap];
}

/**
 * Short milestone name from a schedule row: "Beta 1", "RC 2", "Dry run", "Release".
 */
function mh_rw_milestone_label(string $text): string
{
    if (preg_match('/^Beta\s*(\d+)?/i', $text, $m)) {
        return 'Beta '.($m[1] ?? '1');
    }
    if (preg_match('/^Release Candidate\s*(\d+)?/i', $text, $m)) {
        return 'RC '.($m[1] ?? '1');
    }
    if (preg_match('/^Dry run/i', $text)) {
        return 'Dry run';
    }
    if (preg_match('/^WordPress\s+[\d.]+\s+is released/i', $text)) {
        return 'Release';
    }

    return '';
}

/**
 * Roadmap post → feature areas.
 *
 * Each h2 is an area. Inside it, an h3 is one feature (its first paragraph is the
 * summary), a list item is one feature ("Title: summary"), and an area with neither
 * becomes a single feature named after the area.
 *
 * @return list<array{id: string, title: string, intro: string, items: list<array{title: string, url: string, summary: string}>}>
 */
function mh_rw_parse_roadmap(string $html): array
{
    [, , $root] = mh_rw_dom($html);
    $areas = [];
    $area = null;
    $sub = null;

    $closeSub = function () use (&$sub, &$area): void {
        if ($sub && $area !== null) {
            $url = $sub['follow'] !== '' ? $sub['follow'] : ($sub['links'][0] ?? '');
            array_splice($area['items'], $sub['at'], 0, [[
                'title' => $sub['title'],
                'url' => $url,
                'summary' => mh_rw_shorten($sub['summary'], 260),
            ]]);
        }
        $sub = null;
    };
    $closeArea = function () use (&$area, &$areas, $closeSub): void {
        $closeSub();
        if ($area === null) {
            return;
        }
        if (! $area['items']) {
            // No list and no subsections: the area is the feature.
            $area['items'][] = [
                'title' => $area['title'],
                'url' => $area['links'][0] ?? '',
                'summary' => mh_rw_shorten($area['intro'], 260),
            ];
            $area['intro'] = '';
        }
        unset($area['links']);
        $areas[] = $area;
        $area = null;
    };

    foreach ($root->childNodes as $node) {
        if (! $node instanceof \DOMElement) {
            continue;
        }
        $tag = strtolower($node->nodeName);
        $text = mh_rw_text((string) $node->textContent);

        if ($tag === 'h2') {
            $closeArea();
            $title = rtrim($text, ': ');
            if ($title === '' || in_array(strtolower($title), MH_RW_SKIP_AREAS, true)) {
                continue;
            }
            $area = ['id' => sanitize_title($title), 'title' => $title, 'intro' => '', 'items' => [], 'links' => []];

            continue;
        }
        if ($area === null) {
            continue;
        }

        if ($tag === 'h3' || $tag === 'h4') {
            $closeSub();
            $sub = ['title' => rtrim($text, ': '), 'summary' => '', 'links' => [], 'follow' => '', 'at' => count($area['items'])];

            continue;
        }

        $links = [];
        foreach ($node->getElementsByTagName('a') as $a) {
            $url = mh_rw_url($a->getAttribute('href'));
            // Skip in-page jumps back into the roadmap post itself.
            if ($url !== '' && ! preg_match('#/roadmap-to-[^/]+/?\##', $url)) {
                $links[] = $url;
            }
        }

        if ($tag === 'p') {
            if ($sub) {
                if ($sub['summary'] === '') {
                    $sub['summary'] = $text;
                }
                // "Follow <issue> for more details" points at the tracking issue: the best link.
                if ($links && preg_match('/^(Follow|Track|See)\b/i', $text)) {
                    $sub['follow'] = end($links);
                }
                $sub['links'] = array_merge($sub['links'], $links);
            } elseif (! $area['items']) {
                if ($area['intro'] === '') {
                    $area['intro'] = mh_rw_shorten($text, 400);
                }
                // Kept for an area that turns out to be a single feature.
                $area['links'] = array_merge($area['links'], $links);
            }

            continue;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            foreach ($node->childNodes as $li) {
                if (! $li instanceof \DOMElement || strtolower($li->nodeName) !== 'li') {
                    continue;
                }
                $item = mh_rw_list_item($li, (bool) $sub);
                if ($item) {
                    $area['items'][] = $item;
                }
            }
        }
    }
    $closeArea();

    return $areas;
}

/**
 * One roadmap list item: "Title: summary", or a linked line, or a plain sentence.
 *
 * @return array{title: string, url: string, summary: string}|null
 */
function mh_rw_list_item(\DOMElement $li, bool $linkTitle): ?array
{
    $text = mh_rw_text((string) $li->textContent);
    if ($text === '') {
        return null;
    }
    $a = $li->getElementsByTagName('a')->item(0);
    $url = $a instanceof \DOMElement ? mh_rw_url($a->getAttribute('href')) : '';

    if ($linkTitle && $a instanceof \DOMElement) {
        return ['title' => mh_rw_shorten(mh_rw_text((string) $a->textContent), 140), 'url' => $url, 'summary' => ''];
    }

    $colon = mb_strpos($text, ':');
    if ($colon !== false && $colon > 2 && $colon < 90 && mb_strlen($text) > $colon + 3) {
        return [
            'title' => mh_rw_shorten(trim(mb_substr($text, 0, $colon)), 140),
            'url' => $url,
            'summary' => mh_rw_shorten(trim(mb_substr($text, $colon + 1)), 260),
        ];
    }

    return ['title' => mh_rw_shorten($text, 130), 'url' => $url, 'summary' => ''];
}

/**
 * Latest Make Core posts for this version plus the newest "What's new in Gutenberg" posts.
 *
 * @return list<array{type: string, slug: string, title: string, url: string, date: string, excerpt: string}>
 */
function mh_rw_fetch_updates(string $version): array
{
    $tagIds = [];
    $tags = mh_rw_get_json(MH_RW_MAKE.'tags?slug='.rawurlencode(str_replace('.', '-', $version)).',dev-notes,field-guide,proposal,gutenberg-new,feature-projects&_fields=id,slug');
    if (is_wp_error($tags)) {
        return [];
    }
    foreach ($tags as $tag) {
        $tagIds[(string) ($tag['slug'] ?? '')] = (int) ($tag['id'] ?? 0);
    }

    $fields = '&_fields=date,title,link,excerpt,content,tags';
    $posts = [];
    $versionTag = $tagIds[str_replace('.', '-', $version)] ?? 0;
    if ($versionTag) {
        $found = mh_rw_get_json(MH_RW_MAKE.'posts?tags='.$versionTag.'&per_page=20'.$fields);
        $posts = is_wp_error($found) ? [] : $found;
    }
    if (! empty($tagIds['gutenberg-new'])) {
        $found = mh_rw_get_json(MH_RW_MAKE.'posts?tags='.$tagIds['gutenberg-new'].'&per_page=2'.$fields);
        $posts = array_merge($posts, is_wp_error($found) ? [] : $found);
    }

    $bySlug = array_flip($tagIds);
    $out = [];
    foreach ($posts as $post) {
        $title = mh_rw_text((string) ($post['title']['rendered'] ?? ''));
        $url = esc_url_raw((string) ($post['link'] ?? ''));
        // Meeting agendas and summaries are not news about the release.
        if ($title === '' || $url === '' || isset($out[$url]) || preg_match('/^(Dev Chat|Release Party|Editor Chat)|\b(agenda|summary)\b/i', $title)) {
            continue;
        }
        $slugs = array_map(static fn ($id) => $bySlug[(int) $id] ?? '', (array) ($post['tags'] ?? []));
        [$type, $slug] = match (true) {
            in_array('dev-notes', $slugs, true) => ['Dev note', 'dev-note'],
            in_array('field-guide', $slugs, true) => ['Field guide', 'field-guide'],
            in_array('gutenberg-new', $slugs, true) => ['Gutenberg', 'gutenberg'],
            (bool) preg_match('/^Roadmap/i', $title) => ['Roadmap', 'roadmap'],
            in_array('proposal', $slugs, true) || (bool) preg_match('/^Proposal/i', $title) => ['Proposal', 'proposal'],
            in_array('feature-projects', $slugs, true) || (bool) preg_match('/^Introducing/i', $title) => ['New feature', 'new-feature'],
            default => ['Update', 'update'],
        };
        $excerpt = mh_rw_lead_paragraph((string) ($post['content']['rendered'] ?? ''));
        if ($excerpt === '') {
            $excerpt = (string) preg_replace('/\s*(Continue reading|Read more).*$/iu', '', mh_rw_text((string) ($post['excerpt']['rendered'] ?? '')));
        }
        $out[$url] = [
            'type' => $type,
            'slug' => $slug,
            'title' => $title,
            'url' => $url,
            'date' => substr((string) ($post['date'] ?? ''), 0, 10),
            'excerpt' => mh_rw_shorten(trim($excerpt, ' []…'), 200),
        ];
    }

    $out = array_values($out);
    usort($out, static fn ($a, $b) => strcmp($b['date'], $a['date']));

    return array_slice($out, 0, 12);
}

/**
 * First real paragraph of a post: glossary tooltips removed, Gutenberg boilerplate skipped.
 */
function mh_rw_lead_paragraph(string $html): string
{
    if (trim($html) === '') {
        return '';
    }
    [, $xpath] = mh_rw_dom($html);
    foreach ($xpath->query('//p') ?: [] as $p) {
        $text = mh_rw_text((string) $p->textContent);
        // Every "What's new in Gutenberg" post opens with the same paragraph about the series.
        if (mb_strlen($text) < 80 || preg_match('/^\W*What.s new in Gutenberg|labeled with the #gutenberg-new tag/iu', $text)) {
            continue;
        }

        return $text;
    }

    return '';
}
