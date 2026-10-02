<?php

/**
 * Code page GitHub snapshot.
 *
 * One stored copy of every GitHub number the /code/ page shows. WP-Cron
 * refreshes it hourly, a stale read schedules a background refresh, and a
 * failed API call keeps the last good data instead of blanking a panel.
 * After a refresh that changed anything, the Code page is purged from
 * LiteSpeed so visitors see the new numbers.
 */

namespace App;

const MH_CODE_GH_OPTION = 'mh_code_gh_snapshot_v1';
const MH_CODE_GH_CRON = 'mh_code_gh_refresh';
const MH_CODE_GH_CRON_NOW = 'mh_code_gh_refresh_now';
const MH_CODE_GH_LOCK = 'mh_code_gh_lock';

/** Seconds before a snapshot counts as stale (default one hour). */
function mh_code_gh_max_age(): int
{
    return max(5 * MINUTE_IN_SECONDS, (int) apply_filters('mh/code_github_max_age', HOUR_IN_SECONDS));
}

/**
 * Raw snapshot parts; a part is replaced only when the new fetch returned data.
 *
 * @return array<string, mixed>
 */
function mh_code_gh_stored(): array
{
    $snap = get_option(MH_CODE_GH_OPTION, []);

    return is_array($snap) ? $snap : [];
}

/**
 * Pull fresh GitHub data into the stored snapshot.
 *
 * @param  bool  $force  Skip per-endpoint transients (cron, admin button, WP-CLI).
 * @return array<string, mixed> The stored snapshot after the refresh.
 */
function mh_code_gh_refresh(bool $force = true): array
{
    $prev = mh_code_gh_stored();
    if (get_transient(MH_CODE_GH_LOCK)) {
        return $prev;
    }
    set_transient(MH_CODE_GH_LOCK, 1, 2 * MINUTE_IN_SECONDS);

    Github::fresh($force);
    try {
        $login = mh_github_login();
        $repos = Github::fetchOwnerRepos($login);
        $pinned = Github::fetchPinnedRepos($login);
        $featured = mh_code_gh_featured($pinned, $repos);

        $parts = [
            'profile' => Github::fetchUser($login),
            'calendar' => Github::fetchContributionCalendar($login),
            'events' => Github::fetchEvents($login, 100),
            'repos' => $repos,
            'featured' => $featured,
            'followers' => Github::fetchFollowers($login, 30),
            'stargazers' => mh_code_gh_fetch_stargazers($login, $featured, 24),
            'watching' => mh_github_watching(36),
        ];
    } finally {
        Github::fresh(false);
        delete_transient(MH_CODE_GH_LOCK);
    }

    // A failed GraphQL call must not swap pinned repos for the curated fallback.
    if ($pinned === [] && Github::failed('pinned') && ($prev['featured_source'] ?? '') === 'pinned') {
        $parts['featured'] = [];
    }
    // The HTML calendar fallback has no commit/PR breakdown; keep the last one.
    if (empty($parts['calendar']['breakdown']) && ! empty($prev['calendar']['breakdown']) && is_array($parts['calendar'])) {
        $parts['calendar']['breakdown'] = $prev['calendar']['breakdown'];
    }

    $snap = [];
    $failed = [];
    foreach ($parts as $key => $value) {
        if (mh_code_gh_part_ok($key, $value)) {
            $snap[$key] = $value;
        } else {
            $failed[] = $key;
            $snap[$key] = $prev[$key] ?? $value;
        }
    }

    $hash = md5((string) wp_json_encode($snap));
    $snap['featured_source'] = in_array('featured', $failed, true)
        ? ($prev['featured_source'] ?? '')
        : ($pinned !== [] ? 'pinned' : 'curated');
    $snap['synced_at'] = time();
    $snap['failed'] = $failed;
    $snap['has_token'] = github_token() !== '';
    $snap['hash'] = $hash;
    update_option(MH_CODE_GH_OPTION, $snap, false);

    if ($hash !== ($prev['hash'] ?? '')) {
        mh_code_gh_purge_page();
    }

    return $snap;
}

/** Whether a freshly fetched part has real data worth storing. */
function mh_code_gh_part_ok(string $key, mixed $value): bool
{
    return match ($key) {
        'profile' => is_array($value) && ! empty($value['login']),
        'calendar' => is_array($value) && ! empty($value['weeks']),
        'watching' => is_array($value) && ! empty($value['items']),
        'stargazers' => is_array($value),
        default => is_array($value) && $value !== [],
    };
}

/** Purge the Code page from LiteSpeed (and any plugin listening to the action). */
function mh_code_gh_purge_page(): void
{
    $id = mh_code_page_id();
    if ($id > 0) {
        do_action('litespeed_purge_post', $id);
        clean_post_cache($id);
    }
    do_action('mh/code_github_refreshed', $id);
}

/**
 * Snapshot for templates: stored data plus derived stats. Never calls the API
 * on a normal request once a first snapshot exists.
 *
 * @return array<string, mixed>
 */
function mh_code_gh_snapshot(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }

    $snap = mh_code_gh_stored();
    if (empty($snap['synced_at'])) {
        // First load ever: build inline once, using per-endpoint transients when present.
        $snap = mh_code_gh_refresh(false);
    } elseif (time() - (int) $snap['synced_at'] > mh_code_gh_max_age() && ! wp_next_scheduled(MH_CODE_GH_CRON_NOW)) {
        wp_schedule_single_event(time(), MH_CODE_GH_CRON_NOW);
    }

    return $memo = mh_code_gh_derive($snap);
}

/**
 * Add the numbers templates need (streaks, language mix, recent pushes, badges).
 *
 * @param  array<string, mixed>  $snap
 * @return array<string, mixed>
 */
function mh_code_gh_derive(array $snap): array
{
    $profile = is_array($snap['profile'] ?? null) ? $snap['profile'] : [];
    $calendar = is_array($snap['calendar'] ?? null) ? $snap['calendar'] : ['total' => 0, 'weeks' => []];
    $repos = is_array($snap['repos'] ?? null) ? $snap['repos'] : [];
    // Re-normalize on read (cheap) so card changes ship without waiting for a refresh.
    $featured = array_map(__NAMESPACE__.'\\mh_code_gh_card', is_array($snap['featured'] ?? null) ? $snap['featured'] : []);
    $events = is_array($snap['events'] ?? null) ? $snap['events'] : [];
    $watching = is_array($snap['watching'] ?? null) ? $snap['watching'] : ['source' => 'starred', 'items' => []];

    $starTotal = 0;
    $starRepos = [];
    $forkTotal = 0;
    foreach ($repos as $r) {
        $starTotal += (int) ($r['stars'] ?? 0);
        $forkTotal += (int) ($r['forks'] ?? 0);
        if ((int) ($r['stars'] ?? 0) > 0) {
            $starRepos[] = ['name' => (string) $r['name'], 'stars' => (int) $r['stars'], 'url' => (string) $r['url']];
        }
    }
    usort($starRepos, static fn (array $a, array $b): int => $b['stars'] <=> $a['stars']);

    $featuredNames = array_map(static fn (array $r): string => strtolower((string) ($r['name'] ?? '')), $featured);
    $recent = [];
    foreach ($repos as $r) {
        if (in_array(strtolower((string) $r['name']), $featuredNames, true) || ! empty($r['archived'])) {
            continue;
        }
        $recent[] = mh_code_gh_card($r);
        if (count($recent) >= 6) {
            break;
        }
    }

    $lastPush = $repos[0] ?? null;
    $streaks = mh_github_calendar_streaks($calendar);
    $recentEvents = mh_github_events_within($events, 90);

    $out = [
        'login' => mh_github_login(),
        'url' => (string) (($profile['url'] ?? '') ?: 'https://github.com/'.mh_github_login()),
        'profile' => $profile,
        'calendar' => $calendar,
        'calendar90' => mh_github_calendar_clip($calendar, 90),
        'breakdown' => is_array($calendar['breakdown'] ?? null) ? $calendar['breakdown'] : [],
        'streaks' => $streaks,
        'events' => array_slice($recentEvents, 0, 10),
        'events_by_day' => mh_github_events_group_by_day($recentEvents),
        'featured' => $featured,
        'recent' => $recent,
        'repo_count' => max((int) ($profile['public_repos'] ?? 0), count($repos)),
        'languages' => mh_code_gh_languages($repos),
        'star_total' => $starTotal,
        'star_repos' => $starRepos,
        'fork_total' => $forkTotal,
        'last_push' => is_array($lastPush) ? ['repo' => (string) $lastPush['name'], 'url' => (string) $lastPush['url'], 'when' => (string) $lastPush['pushed']] : null,
        'followers' => is_array($snap['followers'] ?? null) ? $snap['followers'] : [],
        'follower_count' => (int) ($profile['followers'] ?? 0),
        'stargazers' => is_array($snap['stargazers'] ?? null) ? $snap['stargazers'] : [],
        'watching' => $watching,
        'synced_at' => (int) ($snap['synced_at'] ?? 0),
        'failed' => is_array($snap['failed'] ?? null) ? $snap['failed'] : [],
    ];
    $out['badges'] = mh_code_gh_badges($out);

    return $out;
}

/**
 * Featured repos: GitHub pinned repos first, else the curated Code page list
 * enriched with live data (GitHub's description wins over the stored copy).
 *
 * @param  list<array<string, mixed>>  $pinned
 * @param  list<array<string, mixed>>  $repos
 * @return list<array<string, mixed>>
 */
function mh_code_gh_featured(array $pinned, array $repos): array
{
    $out = [];
    foreach ($pinned as $r) {
        if (! mh_github_is_hidden_repo((string) ($r['name'] ?? ''))) {
            $out[] = mh_code_gh_card($r);
        }
    }
    if ($out !== []) {
        return $out;
    }

    $byName = [];
    foreach ($repos as $r) {
        $byName[strtolower((string) $r['name'])] = $r;
    }
    $codeId = mh_code_page_id();
    $rows = field_rows('code_repos', [], $codeId > 0 ? $codeId : null);
    foreach ($rows === [] ? mh_featured_repos() : $rows as $curated) {
        $name = (string) ($curated['name'] ?? '');
        if ($name === '' || mh_github_is_hidden_repo($name)) {
            continue;
        }
        if (is_string($curated['tags'] ?? null)) {
            $curated['tags'] = array_values(array_filter(array_map('trim', explode(',', $curated['tags']))));
        }
        $live = $byName[strtolower($name)] ?? [];
        if ($live === [] && $repos !== []) {
            continue; // Deleted, renamed, or made private on GitHub.
        }
        $card = array_merge($curated, array_filter($live, static fn ($v): bool => $v !== '' && $v !== []));
        $card['tags'] = $live['topics'] ?? ($curated['tags'] ?? []);
        $out[] = mh_code_gh_card($card);
    }

    return $out;
}

/**
 * Normalize any repo row (pinned, REST, curated) into the repo-card shape.
 *
 * @param  array<string, mixed>  $r
 * @return array<string, mixed>
 */
function mh_code_gh_card(array $r): array
{
    $name = (string) ($r['name'] ?? '');
    $lang = (string) ($r['lang'] ?? '');
    $skip = ['website', 'website-design', 'website-development', 'vibe-coding', 'toc', 'table-of-contents', 'accessibility'];
    $generic = '/responsive|seo|development|-site$|-website$|^website|portfolio|template/i';
    $stack = [];
    foreach (array_merge([$lang], (array) ($r['tags'] ?? $r['topics'] ?? [])) as $item) {
        $raw = trim((string) $item);
        if ($raw === '' || in_array(strtolower($raw), $skip, true) || preg_match($generic, $raw)) {
            continue;
        }
        $label = mh_title_label($raw);
        $stack[strtolower($label)] = $label;
    }

    // Rolling build tags (theme-latest) are not versions; show only real releases.
    $release = (string) ($r['release'] ?? '');
    if (! preg_match('/\d/', $release)) {
        $release = '';
    }

    return [
        'name' => $name,
        'title' => mh_title_label($name),
        'desc' => mh_visitor_brand_text(trim((string) ($r['desc'] ?? ''))),
        'url' => (string) (($r['url'] ?? '') ?: 'https://github.com/'.mh_github_login().'/'.$name),
        'demo' => mh_repo_demo_url((string) ($r['demo'] ?? $r['homepage'] ?? '')),
        'lang' => $lang,
        'stars' => (int) ($r['stars'] ?? 0),
        'forks' => (int) ($r['forks'] ?? 0),
        'pushed' => (string) ($r['pushed'] ?? ''),
        'stack' => array_values(array_slice($stack, 0, 6)),
        'tags' => array_values((array) ($r['tags'] ?? $r['topics'] ?? [])),
        'archived' => ! empty($r['archived']),
        'license' => (string) ($r['license'] ?? ''),
        'commits' => (int) ($r['commits'] ?? 0),
        'release' => $release,
        'release_url' => $release !== '' ? (string) ($r['release_url'] ?? '') : '',
        'release_date' => $release !== '' ? (string) ($r['release_date'] ?? '') : '',
    ];
}

/**
 * Recent stargazers across featured repos, deduped by login.
 *
 * @param  list<array<string, mixed>>  $featured
 * @return list<array{login: string, name: string, avatar: string, url: string, repo: string}>
 */
function mh_code_gh_fetch_stargazers(string $login, array $featured, int $limit = 24): array
{
    $seen = [];
    $out = [];
    foreach ($featured as $repo) {
        if ((int) ($repo['stars'] ?? 0) < 1) {
            continue;
        }
        foreach (Github::fetchStargazers($login, (string) $repo['name'], 20) as $row) {
            $key = strtolower((string) ($row['login'] ?? ''));
            if ($key === '' || isset($seen[$key]) || strcasecmp($key, $login) === 0) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
            if (count($out) >= $limit) {
                return $out;
            }
        }
    }

    return $out;
}

/**
 * Primary-language mix across public repos, largest first (top five + Other).
 *
 * @param  list<array<string, mixed>>  $repos
 * @return list<array{lang: string, count: int, pct: float, color: string}>
 */
function mh_code_gh_languages(array $repos): array
{
    $counts = [];
    foreach ($repos as $r) {
        $lang = trim((string) ($r['lang'] ?? ''));
        if ($lang !== '') {
            $counts[$lang] = ($counts[$lang] ?? 0) + 1;
        }
    }
    arsort($counts);
    $total = array_sum($counts);
    if ($total === 0) {
        return [];
    }

    $top = array_slice($counts, 0, 5, true);
    $other = $total - array_sum($top);
    if ($other > 0) {
        $top[__('Other', 'sage')] = $other;
    }

    $out = [];
    foreach ($top as $lang => $count) {
        $out[] = [
            'lang' => (string) $lang,
            'count' => (int) $count,
            'pct' => round($count / $total * 100, 1),
            'color' => $lang === __('Other', 'sage') ? '#cbd5e1' : mh_github_lang_color((string) $lang),
        ];
    }

    return $out;
}

/**
 * Milestone badges from live numbers only (no fake achievements).
 *
 * @param  array<string, mixed>  $d  Derived snapshot.
 * @return list<array{label: string, detail: string, icon: string, class: string}>
 */
function mh_code_gh_badges(array $d): array
{
    $badges = [];
    $tier = static function (int $value, array $tiers): int {
        foreach ($tiers as $t) {
            if ($value >= $t) {
                return $t;
            }
        }

        return 0;
    };

    if ($d['repo_count'] > 0) {
        $badges[] = [
            'label' => __('Open source', 'sage'),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(_n('%s public repo', '%s public repos', $d['repo_count'], 'sage'), number_format_i18n($d['repo_count'])),
            'icon' => 'github',
            'class' => 'code-gh-badge--oss',
        ];
    }
    $contrib = (int) ($d['calendar']['total'] ?? 0);
    if ($t = $tier($contrib, [5000, 2500, 1000, 500, 100])) {
        $badges[] = [
            /* translators: %s: a formatted number. */
            'label' => sprintf(__('%s+ contributions', 'sage'), number_format_i18n($t)),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(__('%s in the last year', 'sage'), number_format_i18n($contrib)),
            'icon' => 'git',
            'class' => 'code-gh-badge--contrib',
        ];
    }
    $longest = (int) ($d['streaks']['longest'] ?? 0);
    if ($t = $tier($longest, [100, 60, 30, 14, 7])) {
        $badges[] = [
            /* translators: %s: a formatted number. */
            'label' => sprintf(__('%s-day streak', 'sage'), number_format_i18n($t)),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(_n('Longest run: %s day in a row', 'Longest run: %s days in a row', $longest, 'sage'), number_format_i18n($longest)),
            'icon' => 'calendar',
            'class' => 'code-gh-badge--streak',
        ];
    }
    if ($t = $tier((int) $d['star_total'], [100, 50, 25, 10, 5])) {
        $badges[] = [
            /* translators: %s: a formatted number. */
            'label' => sprintf(__('%s+ stars earned', 'sage'), number_format_i18n($t)),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(__('%s across public repos', 'sage'), number_format_i18n((int) $d['star_total'])),
            'icon' => 'star',
            'class' => 'code-gh-badge--stars',
        ];
    }
    if ($t = $tier((int) $d['follower_count'], [500, 100, 50, 25, 10])) {
        $badges[] = [
            /* translators: %s: a formatted number. */
            'label' => sprintf(__('%s+ followers', 'sage'), number_format_i18n($t)),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(__('%s people follow along', 'sage'), number_format_i18n((int) $d['follower_count'])),
            'icon' => 'users',
            'class' => 'code-gh-badge--followers',
        ];
    }
    $releases = count(array_filter($d['featured'], static fn (array $r): bool => $r['release'] !== ''));
    if ($releases > 0) {
        $badges[] = [
            'label' => __('Ships releases', 'sage'),
            /* translators: %s: a formatted number. */
            'detail' => sprintf(_n('%s featured repo with tagged releases', '%s featured repos with tagged releases', $releases, 'sage'), number_format_i18n($releases)),
            'icon' => 'download',
            'class' => 'code-gh-badge--release',
        ];
    }

    return $badges;
}

/* ---------- Scheduling, admin refresh, WP-CLI ---------- */

add_action(MH_CODE_GH_CRON, __NAMESPACE__.'\\mh_code_gh_refresh');
add_action(MH_CODE_GH_CRON_NOW, __NAMESPACE__.'\\mh_code_gh_refresh');

add_action('init', function (): void {
    if (! wp_next_scheduled(MH_CODE_GH_CRON)) {
        wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', MH_CODE_GH_CRON);
    }
}, 40);

add_action('switch_theme', function (): void {
    wp_clear_scheduled_hook(MH_CODE_GH_CRON);
    wp_clear_scheduled_hook(MH_CODE_GH_CRON_NOW);
});

/** Keep the Code page in LiteSpeed for an hour, not the site-wide week. */
add_action('template_redirect', function (): void {
    if (is_page() && get_page_template_slug() === 'template-code.blade.php') {
        do_action('litespeed_control_set_ttl', HOUR_IN_SECONDS);
    }
});

/** Admin bar: "Refresh GitHub data" on the Code page. */
add_action('admin_bar_menu', function (\WP_Admin_Bar $bar): void {
    if (! current_user_can('manage_options') || is_admin() || ! is_page() || get_page_template_slug() !== 'template-code.blade.php') {
        return;
    }
    $bar->add_node([
        'id' => 'mh-code-gh-refresh',
        'title' => esc_html__('Refresh GitHub data', 'sage'),
        'href' => wp_nonce_url(admin_url('admin-post.php?action=mh_code_gh_refresh'), 'mh_code_gh_refresh'),
    ]);
}, 90);

add_action('admin_post_mh_code_gh_refresh', function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to do that.', 'sage'), 403);
    }
    check_admin_referer('mh_code_gh_refresh');
    mh_code_gh_refresh(true);
    $id = mh_code_page_id();
    wp_safe_redirect($id > 0 ? (string) get_permalink($id) : home_url('/'));
    exit;
});

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh github-refresh', function (): void {
        $snap = mh_code_gh_refresh(true);
        $d = mh_code_gh_derive($snap);
        \WP_CLI::log(sprintf(
            'Repos %d · featured %d · contributions %d · stars %d · followers %d · events %d',
            $d['repo_count'],
            count($d['featured']),
            (int) ($d['calendar']['total'] ?? 0),
            $d['star_total'],
            $d['follower_count'],
            count($d['events'])
        ));
        if ($snap['failed'] !== []) {
            \WP_CLI::warning('Kept last good data for: '.implode(', ', $snap['failed']));
        }
        \WP_CLI::success($snap['has_token'] ? 'GitHub snapshot refreshed.' : 'Refreshed without a token (no pinned repos or GraphQL stats).');
    });
}
