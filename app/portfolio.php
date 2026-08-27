<?php

/**
 * Portfolio content, social defaults, DEV.to feed, and one-time page seed.
 * Does not delete posts or categories.
 */

namespace App;

function mh_github_login(): string
{
    return 'matthummel-pa';
}

/** Website listed on the GitHub profile (Ridges & Valleys), with a https fallback. */
function mh_github_blog_url(?array $gh = null): string
{
    $gh = $gh ?? Github::fetchUser(mh_github_login());
    $blog = trim((string) ($gh['blog'] ?? ''));
    if ($blog === '') {
        return 'https://ridgesandvalleys.com';
    }

    return preg_match('#^https?://#i', $blog) ? $blog : 'https://'.$blog;
}

/**
 * Whether the GitHub profile is marked Available for hire (`hireable`).
 */
function mh_is_hireable(?array $gh = null): bool
{
    $gh ??= Github::fetchUser(mh_github_login());

    return ! empty($gh['hireable']);
}

/**
 * Unicode emoji from the GitHub profile status (e.g. ☕), or empty string.
 */
function mh_availability_emoji(?array $gh = null): string
{
    $gh ??= Github::fetchUser(mh_github_login());

    return trim((string) ($gh['status_emoji'] ?? ''));
}

/**
 * Short availability label when hireable: GitHub status message, else fallback.
 *
 * @return string Empty when not hireable.
 */
function mh_availability_label(?array $gh = null, ?string $fallback = null): string
{
    $gh ??= Github::fetchUser(mh_github_login());
    if (empty($gh['hireable'])) {
        return '';
    }

    $message = trim((string) ($gh['status_message'] ?? ''));
    if ($message !== '') {
        return $message;
    }

    return $fallback ?? __('Open for work', 'sage');
}

function mh_portfolio_social_defaults(): array
{
    return [
        'github' => 'https://github.com/matthummel-pa',
        'linkedin' => 'https://www.linkedin.com/in/matt-hummel-pa',
        'devto' => 'https://dev.to/matthummeldev',
        'bluesky' => 'https://bsky.app/profile/matthummel.bsky.social',
        'reddit' => 'https://www.reddit.com/user/matt-hummel',
        'rss' => home_url('/feed/'),
    ];
}

function mh_social_links(): array
{
    $labels = [
        'github' => 'GitHub',
        'linkedin' => 'LinkedIn',
        'devto' => 'DEV.to',
        'bluesky' => 'Bluesky',
        'reddit' => 'Reddit',
        'rss' => 'RSS',
    ];

    $links = [];
    foreach (mh_portfolio_social_defaults() as $key => $url) {
        if ($url === '') {
            continue;
        }
        $links[] = [
            'key' => $key,
            'label' => $labels[$key] ?? ucfirst($key),
            'url' => $url,
        ];
    }

    return $links;
}

/** Featured GitHub codebases to highlight on Code and Home. */
function mh_featured_repos(): array
{
    return [
        [
            'name' => 'keepary',
            'desc' => 'Private family app: authentication, invites, and posts. React on the front end, Supabase for data and sign-in.',
            'url' => 'https://github.com/matthummel-pa/keepary',
            'tags' => ['React', 'Supabase', 'TypeScript'],
        ],
        [
            'name' => 'tocflow',
            'desc' => 'WordPress plugin that builds a table of contents from heading blocks. PHP, Gutenberg, and a small public API.',
            'url' => 'https://github.com/matthummel-pa/tocflow',
            'tags' => ['WordPress', 'Gutenberg', 'PHP'],
        ],
        [
            'name' => 'ridgesandvalleys',
            'desc' => 'Sage 11 theme for the Gettysburg studio site. Blade templates, local SEO, and pages shops can edit.',
            'url' => 'https://github.com/matthummel-pa/ridgesandvalleys',
            'tags' => ['WordPress', 'Sage', 'Tailwind'],
        ],
    ];
}

/** Featured repos plus recent public GitHub work (forks and the profile repo skipped). */
function mh_home_github_repos(int $limit = 6): array
{
    $featured = mh_code_page_repos();
    $live = mh_github_live_repos(12);
    $names = array_map(static fn ($r) => strtolower((string) ($r['name'] ?? '')), $featured);
    foreach ($live as $r) {
        $name = strtolower((string) ($r['name'] ?? ''));
        if ($name === '' || in_array($name, $names, true)) {
            continue;
        }
        $featured[] = $r;
        $names[] = $name;
    }

    return array_slice($featured, 0, max(1, $limit));
}

function mh_title_label(string $text): string
{
    $text = trim((string) preg_replace('/[\-_]+/', ' ', $text));
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
        return '';
    }
    $acronyms = [
        // Abbreviations always uppercase
        'wp' => 'WP', 'php' => 'PHP', 'css' => 'CSS', 'html' => 'HTML', 'js' => 'JS',
        'seo' => 'SEO', 'api' => 'API', 'ui' => 'UI', 'ux' => 'UX', 'toc' => 'TOC',
        'scss' => 'SCSS', 'ts' => 'TS', 'mdn' => 'MDN', 'ai' => 'AI',
        // Mixed-case technology names that ucfirst() would get wrong
        'javascript' => 'JavaScript', 'typescript' => 'TypeScript',
        'wordpress' => 'WordPress', 'github' => 'GitHub', 'gitlab' => 'GitLab',
        'graphql' => 'GraphQL', 'postgresql' => 'PostgreSQL', 'sqlite' => 'SQLite',
        'mysql' => 'MySQL', 'mongodb' => 'MongoDB', 'devops' => 'DevOps',
        'tailwindcss' => 'Tailwind CSS', 'tailwind' => 'Tailwind',
        // Compounds that lose their second capital
        'nextjs' => 'Next.js', 'nodejs' => 'Node.js', 'vscode' => 'VS Code',
        'powerapps' => 'Power Apps', 'powerautomate' => 'Power Automate',
        'sharepoint' => 'SharePoint', 'microsoft' => 'Microsoft',
        'youtube' => 'YouTube', 'devto' => 'DEV.to',
        'n8n' => 'n8n',
        'gemini' => 'Gemini',
    ];
    $out = [];
    foreach (explode(' ', $text) as $part) {
        $low = strtolower($part);
        $out[] = $acronyms[$low] ?? ucfirst($low);
    }

    return implode(' ', $out);
}

function mh_repo_demo_url(string $homepage): string
{
    $homepage = trim($homepage);
    if ($homepage === '') {
        return '';
    }
    if (! preg_match('#^https?://#i', $homepage)) {
        $homepage = 'https://'.$homepage;
    }

    return esc_url_raw($homepage) ?: '';
}

function mh_repo_card(array $repo): array
{
    $name = (string) ($repo['name'] ?? '');
    $url = (string) ($repo['url'] ?? '');
    $owner = mh_github_login();
    $meta = [];
    if ($name !== '' && ($url === '' || empty($repo['homepage']) && empty($repo['topics']) && empty($repo['lang']))) {
        $meta = Github::fetchRepoMeta($owner, $name);
    }
    $desc = trim((string) ($repo['desc'] ?? ''));
    if ($desc === '') {
        $desc = (string) ($meta['desc'] ?? '');
    }
    if ($url === '') {
        $url = (string) ($meta['url'] ?? '');
    }
    if ($url === '' && $name !== '') {
        $url = 'https://github.com/'.$owner.'/'.$name;
    }
    $homepage = mh_repo_demo_url((string) ($repo['homepage'] ?? $meta['homepage'] ?? ''));
    $langs = [];
    if ($name !== '') {
        $langs = Github::fetchLanguages($owner, $name);
    }
    if ($langs === [] && ! empty($repo['lang'])) {
        $langs = [(string) $repo['lang']];
    } elseif ($langs === [] && ! empty($meta['lang'])) {
        $langs = [(string) $meta['lang']];
    }
    $tags = $repo['tags'] ?? [];
    if (is_string($tags)) {
        $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
    }
    $topics = $repo['topics'] ?? $meta['topics'] ?? [];
    $skip = [
        'website', 'website-design', 'website-development', 'vibe-coding',
        'toc', 'table-of-contents', 'accessibility',
    ];
    $stack = [];
    foreach (array_merge($langs, $tags, $topics) as $item) {
        $raw = trim((string) $item);
        if ($raw === '' || in_array(strtolower($raw), $skip, true)) {
            continue;
        }
        $label = mh_title_label($raw);
        $key = strtolower($label);
        if ($label === '' || isset($stack[$key])) {
            continue;
        }
        $stack[$key] = $label;
    }

    return [
        'name' => $name,
        'title' => mh_title_label($name),
        'desc' => $desc,
        'url' => $url,
        'demo' => $homepage,
        'stack' => array_values(array_slice($stack, 0, 6)),
        'tags' => array_values($tags),
        'stars' => (int) ($repo['stars'] ?? $meta['stars'] ?? 0),
        'forks' => (int) ($repo['forks'] ?? $meta['forks'] ?? 0),
        'pushed' => (string) ($repo['pushed'] ?? $meta['pushed'] ?? ''),
        'lang' => (string) ($repo['lang'] ?? $meta['lang'] ?? ''),
    ];
}

/** Relative time for GitHub timestamps (UTC). */
function mh_github_ago(string $iso): string
{
    $iso = trim($iso);
    if ($iso === '') {
        return '';
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return '';
    }
    $diff = max(0, time() - $ts);
    if ($diff < MINUTE_IN_SECONDS) {
        return __('just now', 'sage');
    }
    if ($diff < HOUR_IN_SECONDS) {
        $n = (int) floor($diff / MINUTE_IN_SECONDS);

        return sprintf(_n('%s minute ago', '%s minutes ago', $n, 'sage'), (string) $n);
    }
    if ($diff < DAY_IN_SECONDS) {
        $n = (int) floor($diff / HOUR_IN_SECONDS);

        return sprintf(_n('%s hour ago', '%s hours ago', $n, 'sage'), (string) $n);
    }
    if ($diff < 30 * DAY_IN_SECONDS) {
        $n = (int) floor($diff / DAY_IN_SECONDS);

        return sprintf(_n('%s day ago', '%s days ago', $n, 'sage'), (string) $n);
    }

    return date_i18n(get_option('date_format') ?: 'M j, Y', $ts);
}

function mh_github_profile(): array
{
    return Github::fetchUser(mh_github_login());
}

function mh_github_events(int $limit = 10): array
{
    return Github::fetchEvents(mh_github_login(), $limit);
}

function mh_github_calendar(): array
{
    return Github::fetchContributionCalendar(mh_github_login());
}

/**
 * Contribution calendar clipped to the last N days (newest week columns first).
 *
 * @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>, days: int}
 */
function mh_github_calendar_recent(int $days = 90): array
{
    $days = max(1, min(366, $days));
    $cal = mh_github_calendar();
    $cutoff = gmdate('Y-m-d', time() - ($days - 1) * DAY_IN_SECONDS);

    $weeks = [];
    $total = 0;

    foreach ((array) ($cal['weeks'] ?? []) as $week) {
        if (! is_array($week)) {
            continue;
        }

        $col = [];
        $hasInRange = false;

        foreach ($week as $day) {
            if (! is_array($day)) {
                continue;
            }

            $date = (string) ($day['date'] ?? '');
            if ($date === '' || $date < $cutoff) {
                $col[] = ['date' => '', 'count' => 0, 'level' => 0];

                continue;
            }

            $hasInRange = true;
            $count = (int) ($day['count'] ?? 0);
            $total += $count;
            $col[] = [
                'date' => $date,
                'count' => $count,
                'level' => (int) ($day['level'] ?? 0),
            ];
        }

        if ($hasInRange && $col !== []) {
            $weeks[] = $col;
        }
    }

    // Newest week first (left); day-of-week order within each column stays top → bottom.
    $weeks = array_reverse($weeks);

    return [
        'total' => $total,
        'weeks' => array_values($weeks),
        'days' => $days,
    ];
}

/**
 * Public GitHub events, newest first, limited to the last N days.
 *
 * @return list<array{type: string, repo: string, url: string, text: string, when: string}>
 */
function mh_github_events_recent(int $limit = 10, int $days = 90): array
{
    $days = max(1, min(120, $days));
    $cutoff = time() - $days * DAY_IN_SECONDS;
    $out = [];

    foreach (mh_github_events(max($limit * 3, 40)) as $ev) {
        if (! is_array($ev)) {
            continue;
        }
        $when = (string) ($ev['when'] ?? '');
        $ts = $when !== '' ? strtotime($when) : false;
        if ($ts === false || $ts < $cutoff) {
            continue;
        }
        $out[] = $ev;
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

/**
 * Public events keyed by site-local calendar day (Y-m-d) for contribution tooltips.
 *
 * @return array<string, list<array{type: string, repo: string, url: string, text: string, when: string}>>
 */
function mh_github_events_by_day(int $days = 90): array
{
    $days = max(1, min(120, $days));
    $cutoff = time() - $days * DAY_IN_SECONDS;
    $tz = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
    $byDay = [];

    foreach (mh_github_events(100) as $ev) {
        if (! is_array($ev)) {
            continue;
        }
        $when = (string) ($ev['when'] ?? '');
        if ($when === '') {
            continue;
        }
        try {
            $dt = new \DateTimeImmutable($when);
        } catch (\Exception) {
            continue;
        }
        if ($dt->getTimestamp() < $cutoff) {
            continue;
        }
        $key = $dt->setTimezone($tz)->format('Y-m-d');
        $byDay[$key][] = $ev;
    }

    return $byDay;
}

/**
 * Hover / focus tip for one contribution day (count + recent public actions).
 *
 * @param  list<array{text?: string}>  $dayEvents
 */
function mh_github_day_tip(string $date, int $count, array $dayEvents = []): string
{
    if ($date === '') {
        return '';
    }

    $ts = strtotime($date.' UTC');
    $label = $ts !== false ? date_i18n('M j, Y', $ts) : $date;
    $lines = [
        sprintf(
            /* translators: 1: contribution count, 2: formatted date */
            _n('%1$s contribution on %2$s', '%1$s contributions on %2$s', $count, 'sage'),
            number_format_i18n($count),
            $label
        ),
    ];

    $seen = [];
    foreach ($dayEvents as $ev) {
        $text = trim((string) ($ev['text'] ?? ''));
        if ($text === '' || isset($seen[$text])) {
            continue;
        }
        $seen[$text] = true;
        $lines[] = $text;
        if (count($lines) >= 5) {
            break;
        }
    }

    $extra = count($dayEvents) - (count($lines) - 1);
    if ($extra > 0) {
        $lines[] = sprintf(
            /* translators: %s: number of additional events */
            __('+%s more on this day', 'sage'),
            number_format_i18n($extra)
        );
    }

    return implode("\n", $lines);
}

function mh_github_live_repos(int $limit = 8): array
{
    return array_map(__NAMESPACE__.'\\mh_repo_card', Github::fetchRepos(mh_github_login(), $limit, 'updated'));
}

/**
 * Recently pushed repos for the Code page, skipping featured picks.
 *
 * @return list<array<string, mixed>>
 */
function mh_code_page_live_repos(int $limit = 6, ?int $post_id = null): array
{
    $featuredNames = array_map(
        static fn (array $r): string => strtolower((string) ($r['name'] ?? '')),
        mh_code_page_repos($post_id)
    );
    $live = [];

    foreach (mh_github_live_repos(max($limit * 2, 12)) as $repo) {
        $name = strtolower((string) ($repo['name'] ?? ''));
        if ($name === '' || in_array($name, $featuredNames, true)) {
            continue;
        }
        $live[] = $repo;
        if (count($live) >= $limit) {
            break;
        }
    }

    return $live;
}

/**
 * Short category label for a repo card (scan aid, not page copy).
 */
function mh_code_repo_category(array $repo): string
{
    $stack = array_map(static fn ($item): string => strtolower(trim((string) $item)), $repo['stack'] ?? []);
    $tags = array_map(static fn ($item): string => strtolower(trim((string) $item)), $repo['tags'] ?? []);
    $name = strtolower((string) ($repo['name'] ?? ''));
    $desc = strtolower((string) ($repo['desc'] ?? ''));
    $haystack = array_merge($stack, $tags);

    if (
        in_array('sage', $haystack, true)
        || str_contains($name, 'sage')
        || str_contains($desc, 'sage theme')
        || str_contains($desc, 'sage 11')
    ) {
        return __('Sage theme', 'sage');
    }
    if (
        in_array('wordpress', $haystack, true)
        || in_array('gutenberg', $haystack, true)
        || str_contains($desc, 'wordpress plugin')
        || str_contains($desc, 'gutenberg')
    ) {
        return __('WordPress plugin', 'sage');
    }
    if (
        in_array('react', $haystack, true)
        || in_array('supabase', $haystack, true)
        || in_array('next.js', $haystack, true)
        || in_array('nextjs', $haystack, true)
        || str_contains($desc, 'react')
    ) {
        return __('Web app', 'sage');
    }
    if (in_array('php', $haystack, true)) {
        return __('PHP project', 'sage');
    }

    return __('Open source', 'sage');
}

/**
 * Display slug for a repo link (owner/name).
 */
function mh_code_repo_slug(array $repo): string
{
    $url = (string) ($repo['url'] ?? '');
    if ($url !== '') {
        $path = trim((string) (wp_parse_url($url, PHP_URL_PATH) ?: ''), '/');
        if ($path !== '') {
            return $path;
        }
    }

    $name = (string) ($repo['name'] ?? '');
    if ($name === '') {
        return '';
    }

    return mh_github_login().'/'.$name;
}

/**
 * Icon name for a GitHub public event type (feeds into mh_svg_icon).
 */
function mh_github_event_icon(string $type): string
{
    return match ($type) {
        'PushEvent' => 'git',
        'ReleaseEvent', 'PublicEvent' => 'globe',
        'PullRequestEvent', 'PullRequestReviewEvent' => 'code',
        'CreateEvent' => 'code',
        'IssuesEvent' => 'search',
        'IssueCommentEvent' => 'pen',
        'ForkEvent' => 'git',
        'WatchEvent' => 'github',
        default => 'code',
    };
}

/**
 * Brand-ish hex for a programming language (GitHub-style dots).
 */
function mh_github_lang_color(string $lang): string
{
    $map = [
        'PHP' => '#7a86b8',
        'JavaScript' => '#f7df1e',
        'TypeScript' => '#3178c6',
        'CSS' => '#563d7c',
        'HTML' => '#e34c26',
        'Blade' => '#e3342f',
        'Shell' => '#89e051',
        'Python' => '#3572a5',
        'Ruby' => '#701516',
        'SCSS' => '#c6538c',
        'Sass' => '#c6538c',
        'Vue' => '#41b883',
        'Go' => '#00add8',
        'Rust' => '#dea584',
        'Java' => '#b07219',
        'C#' => '#178600',
        'C++' => '#f34b7d',
        'Dockerfile' => '#384d54',
    ];

    return $map[$lang] ?? '#6b7280';
}

/**
 * Month labels for a contribution calendar grid (week columns).
 *
 * @param  array<int, array<int, array{date?: string}>>  $weeks
 * @return list<array{label: string, week: int}>
 */
function mh_github_calendar_months(array $weeks): array
{
    $labels = [];
    $last = '';

    foreach ($weeks as $wi => $week) {
        $date = '';
        foreach ($week as $day) {
            if (! empty($day['date'])) {
                $date = (string) $day['date'];
                break;
            }
        }
        if ($date === '') {
            continue;
        }
        $ts = strtotime($date);
        if ($ts === false) {
            continue;
        }
        $key = date('Y-m', $ts);
        if ($key === $last) {
            continue;
        }
        $last = $key;
        $labels[] = [
            'label' => date_i18n('F', $ts),
            'week' => (int) $wi,
        ];
    }

    return $labels;
}

/**
 * Enriched OSS data for the home page "Code you can use" section.
 *
 * Combines featured repo list with live GitHub API data:
 *   - Repo meta (stars, forks, language, last push)
 *   - Language breakdown as percentages (from the languages endpoint)
 *   - Activity score (0–100) and badge label
 *   - Recent public events (push, release, PR)
 *
 * All API calls are individually transient-cached. The composite result
 * is cached for one hour so the home page stays fast on repeated loads.
 */
function mh_home_oss_live_data(int $repo_count = 3): array
{
    $login = mh_github_login();
    $cache_key = 'mh_oss_live_v2_'.md5($login.(string) $repo_count);

    if (($cached = get_transient($cache_key)) !== false && is_array($cached)) {
        return $cached;
    }

    $profile = Github::fetchUser($login);
    $events = Github::fetchEvents($login, 6);
    $base = array_slice(mh_featured_repos(), 0, $repo_count);
    $repos = [];

    foreach ($base as $r) {
        $name = (string) ($r['name'] ?? '');
        if ($name === '') {
            continue;
        }

        /* Live metadata */
        $meta = Github::fetchRepoMeta($login, $name);
        $langs = Github::fetchLanguages($login, $name);  // ['PHP', 'JavaScript', ...]

        /* Re-fetch raw language bytes for percentage bars */
        $lang_bytes = mh_github_lang_bytes($login, $name);
        $lang_bars = mh_github_lang_percentages($lang_bytes, 4);

        /* Merge description: live API wins if the hardcoded one is the default */
        $desc = trim((string) ($r['desc'] ?? ''));
        if ($desc === '' || $desc === ($meta['desc'] ?? '')) {
            $desc = trim((string) ($meta['desc'] ?? $desc));
        }

        /* Build URL */
        $url = (string) ($r['url'] ?? '');
        if ($url === '') {
            $url = 'https://github.com/'.$login.'/'.$name;
        }

        /* Stars, forks, language */
        $stars = (int) ($meta['stars'] ?? 0);
        $forks = (int) ($meta['forks'] ?? 0);
        $lang = (string) ($meta['lang'] ?? ($langs[0] ?? ''));
        $pushed = (string) ($meta['pushed'] ?? '');

        /* Activity badge */
        [$badge, $badge_class, $health] = mh_repo_activity_badge($pushed, $stars, $forks, $desc);

        /* Relative time */
        $pushed_ago = '';
        if ($pushed !== '') {
            $t = strtotime($pushed);
            $pushed_ago = $t ? human_time_diff($t).' ago' : '';
        }

        $repos[] = [
            'name' => $name,
            'display_name' => mh_title_label($name),
            'desc' => $desc,
            'url' => $url,
            'tags' => $r['tags'] ?? [],
            'stars' => $stars,
            'forks' => $forks,
            'lang' => $lang,
            'langs' => $langs,
            'lang_bars' => $lang_bars,
            'pushed' => $pushed,
            'pushed_ago' => $pushed_ago,
            'badge' => $badge,
            'badge_class' => $badge_class,
            'health' => $health,
        ];
    }

    $result = compact('profile', 'events', 'repos');
    set_transient($cache_key, $result, HOUR_IN_SECONDS);

    return $result;
}

/**
 * Fetch raw language byte counts for a repo.
 * Returns ['PHP' => 12345, 'JavaScript' => 6789, ...] or [].
 */
function mh_github_lang_bytes(string $owner, string $repo): array
{
    $key = 'mh_langbytes_'.md5($owner.'/'.$repo);
    if (($d = get_transient($key)) !== false) {
        return is_array($d) ? $d : [];
    }
    $out = [];
    $r = wp_remote_get(
        'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo).'/languages',
        ['timeout' => 10, 'headers' => github_headers()]
    );
    if (! is_wp_error($r) && (int) wp_remote_retrieve_response_code($r) === 200) {
        $j = json_decode((string) wp_remote_retrieve_body($r), true);
        if (is_array($j)) {
            arsort($j);
            $out = array_slice($j, 0, 6, true);
        }
    }
    set_transient($key, $out, 6 * HOUR_IN_SECONDS);

    return $out;
}

/**
 * Convert raw language bytes into percentage bars for display.
 * Returns [['lang' => 'PHP', 'pct' => 72.5, 'icon' => 'php'], ...].
 */
function mh_github_lang_percentages(array $bytes, int $limit = 4): array
{
    $total = array_sum($bytes);
    if ($total <= 0) {
        return [];
    }
    $out = [];
    foreach (array_slice($bytes, 0, $limit, true) as $lang => $count) {
        $out[] = [
            'lang' => $lang,
            'pct' => round(($count / $total) * 100, 1),
        ];
    }

    return $out;
}

/**
 * Compute an activity badge, CSS class, and 0–100 health score for a repo.
 *
 * @return array{string, string, int} [$badge_label, $css_class, $score]
 */
function mh_repo_activity_badge(string $pushed, int $stars, int $forks, string $desc): array
{
    $score = 30; // base

    /* Recency (0–50 points) */
    $days_since = PHP_INT_MAX;
    if ($pushed !== '') {
        $t = strtotime($pushed);
        if ($t) {
            $days_since = max(0, (int) floor((time() - $t) / 86400));
        }
    }
    if ($days_since <= 7) {
        $score += 50;
        $badge = 'Active';
        $class = 'badge--active';
    } elseif ($days_since <= 30) {
        $score += 35;
        $badge = 'Recent';
        $class = 'badge--recent';
    } elseif ($days_since <= 90) {
        $score += 20;
        $badge = 'Maintained';
        $class = 'badge--maintained';
    } elseif ($days_since <= 365) {
        $score += 8;
        $badge = 'Stable';
        $class = 'badge--stable';
    } else {
        $badge = 'Archived';
        $class = 'badge--archived';
    }

    /* Popularity (+5 per star, max 15) */
    $score += min(15, $stars * 5);

    /* Engagement (+3 per fork, max 9) */
    $score += min(9, $forks * 3);

    /* Quality signals */
    if ($desc !== '') {
        $score += 5;
    }

    return [$badge, $class, min(100, $score)];
}

/** Ridges & Valleys concept work for the Projects page. */
function mh_studio_projects(): array
{
    $rv = 'https://ridgesandvalleys.com/work/';

    return [
        ['slug' => 'hallowed-ground', 'title' => 'Hallowed Ground Battlefield Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'A guide site with day and night tours and a battlefield map.', 'tech' => ['Sage', 'WooCommerce', 'Maps'], 'image' => 'hallowed-ground.jpg', 'concept' => $rv.'concept-tour-hallowed-ground-tours/'],
        ['slug' => 'first-shot', 'title' => 'First Shot Food & History Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'Walking tours with a simple booking calendar.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'first-shot.jpg', 'concept' => $rv.'concept-tour-first-shot-food-tours/'],
        ['slug' => 'field-of-valor', 'title' => 'Field of Valor History Co.', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'History tours for visitors. Clear list. Easy contact.', 'tech' => ['WordPress', 'SEO'], 'image' => 'field-of-valor.jpg', 'concept' => $rv.'gettysburg-tour-website-concept/'],
        ['slug' => 'keystone-homes', 'title' => 'Keystone Homes & Land', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'Land and farms. Grid, map, and simple filters.', 'tech' => ['WordPress', 'Maps', 'Filters'], 'image' => 'keystone-homes.jpg', 'concept' => $rv.'concept-realtor-keystone-homes-and-land/'],
        ['slug' => 'ridgeline-realty', 'title' => 'Ridgeline Realty', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'Listings you can filter, plus a mortgage calculator.', 'tech' => ['WordPress', 'JavaScript'], 'image' => 'ridgeline-realty.jpg', 'concept' => $rv.'concept-realtor-ridgeline-realty/'],
        ['slug' => 'ridgeline-outfitters', 'title' => 'Ridgeline Outfitters', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'Outdoor gear with filters, a wishlist, and a cart.', 'tech' => ['WooCommerce'], 'image' => 'ridgeline-outfitters.jpg', 'concept' => $rv.'concept-retail-ridgeline-outfitters/'],
        ['slug' => 'diamond-ridge', 'title' => 'Diamond & Ridge Mercantile', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A downtown shop with products, a cart, and checkout.', 'tech' => ['WooCommerce'], 'image' => 'diamond-ridge.jpg', 'concept' => $rv.'concept-gettysburg-retail/'],
        ['slug' => 'diamonds-threads', 'title' => 'Diamonds & Threads', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A boutique shop that is easy to browse.', 'tech' => ['WooCommerce'], 'image' => 'diamonds-threads.jpg', 'concept' => $rv.'gettysburg-boutique-website/'],
        ['slug' => 'cannon-crumb', 'title' => 'Cannon & Crumb', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A cafe menu you can filter, plus online ordering.', 'tech' => ['WordPress', 'Ordering'], 'image' => 'cannon-crumb.jpg', 'concept' => $rv.'concept-restaurant-cannon-and-crumb/'],
        ['slug' => 'field-musket', 'title' => 'Field & Musket Tavern', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A tavern menu and a clear way to book a table.', 'tech' => ['WordPress'], 'image' => 'field-musket.jpg', 'concept' => $rv.'concept-gettysburg-restaurant/'],
        ['slug' => 'pintfield', 'title' => 'Pintfield Creamery', 'cat' => 'Restaurants', 'place' => 'Gettysburg & Adams County, PA', 'blurb' => 'A scoop board, online orders, and three shops.', 'tech' => ['WordPress', 'Ordering'], 'image' => 'pintfield.jpg', 'concept' => $rv.'gettysburg-creamery-website-design/'],
        ['slug' => 'reveille', 'title' => 'Reveille Kitchen & Bar', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'Menu first. Reserve a table online.', 'tech' => ['WordPress'], 'image' => 'reveille.jpg', 'concept' => $rv.'gettysburg-restaurant-website/'],
        ['slug' => 'cupola-field', 'title' => 'The Cupola & Field Hotel', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A small hotel with a simple booking path.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'cupola-field.jpg', 'concept' => $rv.'concept-hotel-cupola-field/'],
        ['slug' => 'lantern-laurel', 'title' => 'The Lantern & Laurel Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'Nine rooms. Book direct on the site.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'lantern-laurel.jpg', 'concept' => $rv.'concept-gettysburg-hotel/'],
        ['slug' => 'willoughby', 'title' => 'Willoughby Run Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'An inn site built to take bookings, not just look pretty.', 'tech' => ['WordPress', 'Bookings'], 'image' => 'willoughby.jpg', 'concept' => $rv.'gettysburg-inn-website/'],
        ['slug' => 'herr-ridge', 'title' => 'Herr Ridge Cottage B&B', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A bed-and-breakfast site that helps people book a stay.', 'tech' => ['WordPress', 'SEO'], 'image' => 'herr-ridge.jpg', 'concept' => $rv.'gettysburg-bed-and-breakfast-website/'],
    ];
}

/** Screenshot URL for a Work card (bundled JPEG, or an http(s) override). */
function mh_studio_project_image_url(array $project): string
{
    $img = trim((string) ($project['image'] ?? ''));
    if ($img === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $img)) {
        return $img;
    }

    $rel = 'resources/images/work/'.ltrim($img, '/');
    if (is_readable(get_theme_file_path($rel))) {
        return get_theme_file_uri($rel);
    }

    return '';
}

function mh_studio_project_categories(): array
{
    $cats = array_unique(array_map(fn ($p) => $p['cat'], mh_studio_projects()));
    sort($cats);

    return $cats;
}

function mh_work_item_by_slug(string $slug): ?array
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }
    $page = get_page_by_path('projects');
    $id = $page instanceof \WP_Post ? (int) $page->ID : null;
    foreach (mh_work_page_items($id) as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }

    return null;
}

function mh_work_permalink(string $slug, ?string $pageUrl = null): string
{
    $page = $pageUrl ?: home_url('/projects/');

    return $page.'#'.sanitize_title($slug);
}

function mh_work_contact_url(array $project): string
{
    $slug = sanitize_title((string) ($project['slug'] ?? ''));

    return add_query_arg([
        'project' => $slug,
        'who' => 'business',
    ], home_url('/contact/'));
}

function mh_code_practice_defaults(): array
{
    return [
        'Custom WordPress themes with Sage 11 — Blade templates, Tailwind v4, Vite, PHP 8.3, deployed with GitHub Actions.',
        'Plugin development — single-purpose PHP plugins with PHPDoc, standard WP hooks, and clean uninstall.',
        'Front-end architecture — semantic HTML, accessible CSS, TypeScript, React, and interfaces that work on every device.',
        'Full-stack applications — React interfaces, PHP or Node services, authentication, databases, and deployment workflows.',
        'REST API integrations — data pipelines connecting WordPress and web apps to external services and third-party APIs.',
        'Ridges & Valleys — a WordPress studio for Gettysburg shops, tours, inns, and restaurants. Open to agencies and developers anywhere.',
    ];
}

/**
 * Shelf label for a practice line on the Code page.
 */
function mh_code_practice_group(string $text): string
{
    $low = strtolower(trim($text));

    if (
        str_contains($low, 'power platform')
        || str_contains($low, 'power apps')
        || str_contains($low, 'sharepoint')
    ) {
        return __('Microsoft', 'sage');
    }
    if (
        str_contains($low, 'ridges')
        || str_contains($low, 'valleys')
        || str_contains($low, 'gettysburg studio')
    ) {
        return __('Studio', 'sage');
    }
    if (
        str_contains($low, 'front-end')
        || str_contains($low, 'frontend')
        || str_contains($low, 'semantic html')
    ) {
        return __('Front-end', 'sage');
    }
    if (
        str_contains($low, 'rest api')
        || str_contains($low, 'integrations')
        || str_contains($low, 'data pipeline')
    ) {
        return __('Integrations', 'sage');
    }

    if (
        str_contains($low, 'full-stack')
        || str_contains($low, 'react')
        || str_contains($low, 'node')
        || str_contains($low, 'authentication')
    ) {
        return __('Full stack', 'sage');
    }

    return __('WordPress', 'sage');
}

/**
 * Icon name for a practice group shelf.
 */
function mh_code_practice_group_icon(string $group): string
{
    return match (strtolower(trim($group))) {
        'wordpress' => 'wordpress',
        'front-end', 'frontend' => 'code',
        'integrations' => 'globe',
        'studio' => 'briefcase',
        'microsoft' => 'briefcase',
        'full stack' => 'code',
        default => 'code',
    };
}

/**
 * Split a practice line into a scan title and supporting detail.
 *
 * @return array{title: string, body: string}
 */
function mh_code_practice_parse(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return ['title' => '', 'body' => ''];
    }

    if (preg_match('/^(.+?)\s*[—–-]\s*(.+)$/u', $text, $matches)) {
        return [
            'title' => trim($matches[1]),
            'body' => trim($matches[2]),
        ];
    }

    if (preg_match('/^([^:]+):\s*(.+)$/', $text, $matches)) {
        return [
            'title' => trim($matches[1]),
            'body' => trim($matches[2]),
        ];
    }

    return ['title' => $text, 'body' => ''];
}

function mh_code_skill_defaults(): array
{
    return [
        'HTML', 'CSS', 'JavaScript', 'TypeScript', 'PHP', 'WordPress', 'Sage',
        'React', 'Next.js', 'Node.js', 'Tailwind', 'Sass', 'Vite', 'Laravel',
        'Git', 'GitHub', 'VS Code', 'Power Apps', 'n8n',
    ];
}

/**
 * Shelf label for a skill on the Code page (preserves list order within each group).
 */
function mh_code_skill_group(string $name): string
{
    $key = strtolower(trim($name));

    return match ($key) {
        'wordpress', 'sage', 'php' => 'WordPress',
        'html', 'css', 'javascript', 'typescript', 'react', 'next.js', 'nextjs', 'tailwind', 'sass', 'vite' => 'Front-end',
        'git', 'github', 'vs code', 'vscode', 'node.js', 'nodejs', 'laravel' => 'Ship',
        'power apps', 'power-apps', 'power automate', 'power-automate', 'sharepoint' => 'Microsoft',
        'n8n', 'n8n.io' => 'Workflow',
        default => __('More', 'sage'),
    };
}

/**
 * Icon name for a skill group shelf.
 */
function mh_code_skill_group_icon(string $group): string
{
    return match (strtolower(trim($group))) {
        'wordpress' => 'wordpress',
        'front-end', 'frontend' => 'code',
        'ship' => 'github',
        'microsoft' => 'briefcase',
        'workflow' => 'n8n',
        default => 'globe',
    };
}

/**
 * Short UI hint for a skill tile (scan aid, not page copy).
 */
function mh_code_skill_hint(string $name): string
{
    $key = strtolower(trim($name));

    return match ($key) {
        'html' => __('Semantic markup', 'sage'),
        'css' => __('Layout and tokens', 'sage'),
        'javascript' => __('UI behavior', 'sage'),
        'typescript' => __('Typed front ends', 'sage'),
        'php' => __('Themes and plugins', 'sage'),
        'wordpress' => __('CMS builds', 'sage'),
        'sage' => __('Blade + Vite themes', 'sage'),
        'react' => __('Component UIs', 'sage'),
        'next.js', 'nextjs' => __('React apps', 'sage'),
        'node.js', 'nodejs' => __('Tooling and APIs', 'sage'),
        'tailwind' => __('Utility CSS', 'sage'),
        'sass' => __('Stylesheets', 'sage'),
        'vite' => __('Asset pipeline', 'sage'),
        'laravel' => __('PHP framework', 'sage'),
        'git' => __('Version control', 'sage'),
        'github' => __('Repos and Actions', 'sage'),
        'vs code', 'vscode' => __('Daily editor', 'sage'),
        'power apps', 'power-apps' => __('Canvas apps', 'sage'),
        'power automate', 'power-automate' => __('Workflow automation', 'sage'),
        'sharepoint' => __('Intranet sites', 'sage'),
        'n8n', 'n8n.io' => __('Workflow automation', 'sage'),
        default => __('In active repos', 'sage'),
    };
}

function mh_code_resume_defaults(): array
{
    return [
        [
            'role' => 'Founder',
            'org' => 'Ridges & Valleys',
            'period' => 'Current',
            'type' => 'Independent Studio · Gettysburg, PA',
            'url' => 'https://ridgesandvalleys.com',
            'bullets' => "WordPress studio focused on shops, inns, tours, and restaurants in Gettysburg and Adams County, PA.\nBuilding concept sites that show what a real WordPress build looks like for each local business type — not mockups, working sites on a production stack.\nOpen to agencies, overflow dev work, and full-time roles. Remote anywhere.",
        ],
        [
            'role' => 'Senior Consultant',
            'org' => 'Saliense',
            'period' => 'Mar 2025 – Jul 2026',
            'type' => 'Government Contract · Remote',
            'url' => '',
            'bullets' => "Scoped and delivered Power Platform solutions for federal agency clients — from requirements intake to production deployment.\nBuilt canvas PowerApps replacing legacy paper and email processes for government operations teams.\nDesigned Power Automate approval flows that reduced manual hand-off time on multi-step government workflows.\nServed as technical point of contact between agency stakeholders and the development team.",
        ],
        [
            'role' => 'Applications and SharePoint Administrator',
            'org' => 'All Native Group · Federal Services Division',
            'period' => 'Dec 2023 – Feb 2025',
            'type' => 'Government Contract · Remote',
            'url' => '',
            'bullets' => "Administered SharePoint Online environments across multiple government site collections — permissions, views, content types, and governance.\nMigrated legacy InfoPath forms and Designer workflows to PowerApps and Power Automate.\nProvided Tier 1 and Tier 2 support to SharePoint and PowerApps users across federal teams.\nDocumented system configurations and standard operating procedures for handoff.",
        ],
        [
            'role' => 'SharePoint Web Developer',
            'org' => 'Knowledge Capital Associates — USMC Contractor',
            'period' => 'Sep 2021 – Jun 2022',
            'type' => 'Government Contract',
            'url' => '',
            'bullets' => "Built and maintained SharePoint sites for Marine Corps operational teams.\nLed migration of SharePoint 2013 on-premises sites to SharePoint Online.\nReplaced InfoPath forms with PowerApps and legacy Designer workflows with Power Automate.\nConfigured site collection architecture, navigation, and permissions to agency standards.",
        ],
        [
            'role' => 'Web Developer',
            'org' => 'Germanna Community College',
            'period' => 'Jul 2011 – Oct 2020',
            'type' => 'Full-time · Public Information and Marketing',
            'url' => 'https://germanna.edu/',
            'bullets' => "Led a full rebuild of the college's public-facing WordPress site — designed, developed, and handed off to non-technical staff who edited it daily.\nBrought all public-facing pages into WCAG 2.0 / Section 508 compliance during a major site audit.\nImplemented Google Analytics and Google Tag Manager to support institutional marketing reporting.\nManaged day-to-day content for Public Information and Marketing — news, events, department pages.",
        ],
    ];
}

function mh_code_resource_defaults(): array
{
    return [
        ['label' => 'WordPress Developer Handbook', 'url' => 'https://developer.wordpress.org/', 'note' => 'Themes, plugins, REST API, and block editor.', 'group' => 'WordPress'],
        ['label' => 'WordPress REST API', 'url' => 'https://developer.wordpress.org/rest-api/', 'note' => 'Application endpoints and authentication.', 'group' => 'WordPress'],
        ['label' => 'Sage', 'url' => 'https://roots.io/sage/docs/', 'note' => 'Blade, Vite, and the Roots theme stack I ship.', 'group' => 'Roots'],
        ['label' => 'Acorn', 'url' => 'https://roots.io/acorn/docs/', 'note' => 'Laravel components inside WordPress.', 'group' => 'Roots'],
        ['label' => 'Bedrock', 'url' => 'https://roots.io/bedrock/docs/', 'note' => 'Composer-based WordPress structure.', 'group' => 'Roots'],
        ['label' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com/docs', 'note' => 'Utility CSS used on this theme.', 'group' => 'Front-end'],
        ['label' => 'Vite', 'url' => 'https://vite.dev/guide/', 'note' => 'Asset pipeline for Sage 11.', 'group' => 'Front-end'],
        ['label' => 'MDN Web Docs', 'url' => 'https://developer.mozilla.org/en-US/docs/Web', 'note' => 'HTML, CSS, and JavaScript.', 'group' => 'Front-end'],
        ['label' => 'React', 'url' => 'https://react.dev/', 'note' => 'UI library for Keepary and other apps.', 'group' => 'Front-end'],
        ['label' => 'TypeScript', 'url' => 'https://www.typescriptlang.org/docs/', 'note' => 'Typed JavaScript for larger front ends.', 'group' => 'Front-end'],
        ['label' => 'PHP', 'url' => 'https://www.php.net/docs.php', 'note' => 'Language reference for theme and plugin work.', 'group' => 'Language'],
        ['label' => 'Laravel', 'url' => 'https://laravel.com/docs', 'note' => 'Reference when Acorn overlaps Laravel APIs.', 'group' => 'Language'],
        ['label' => 'GitHub Docs', 'url' => 'https://docs.github.com/en', 'note' => 'REST, GraphQL, and Actions.', 'group' => 'Ship'],
        ['label' => 'Microsoft Learn — Power Platform', 'url' => 'https://learn.microsoft.com/power-platform/', 'note' => 'Power Apps, Automate, and Dataverse.', 'group' => 'Ship'],
    ];
}

/**
 * Icon name for a documentation group label.
 */
function mh_code_doc_group_icon(string $group): string
{
    return match (strtolower(trim($group))) {
        'wordpress' => 'wordpress',
        'roots' => 'git',
        'front-end', 'frontend' => 'code',
        'language' => 'php',
        'ship' => 'github',
        default => 'globe',
    };
}

/**
 * Host label for a documentation URL (display only).
 */
function mh_code_doc_host(string $url): string
{
    $host = (string) (wp_parse_url($url, PHP_URL_HOST) ?: '');
    $host = preg_replace('#^www\.#i', '', $host) ?: '';

    return $host;
}

function mh_code_snippets(): array
{
    return [
        [
            'title' => 'A tiny WordPress shortcode',
            'lang' => 'php',
            'note' => 'Paste into a plugin or your theme PHP. Then type [hello] in a post. Good first snippet for a blog.',
            'code' => "add_shortcode('hello', function () {\n    return '<p>Hello from a shortcode.</p>';\n});",
        ],
        [
            'title' => 'Skip to content that actually works',
            'lang' => 'html',
            'note' => 'Keep the first focusable control a skip link. Hide it until it has focus.',
            'code' => '<a class="skip-link" href="#main">Skip to main content</a>',
        ],
        [
            'title' => 'Readable line length',
            'lang' => 'css',
            'note' => 'Cap body text around 65 characters. Short lines are easier at a 6–8 grade level.',
            'code' => ".prose {\n  max-width: 65ch;\n  font-size: 1.125rem;\n  line-height: 1.7;\n}",
        ],
        [
            'title' => 'One brand color, reused',
            'lang' => 'css',
            'note' => 'Change --brand once. Links and buttons can share it. Same idea as this theme.',
            'code' => ":root {\n  --brand: #2563EB;\n}\n\na {\n  color: var(--brand);\n}",
        ],
        [
            'title' => 'Safe link in Blade',
            'lang' => 'blade',
            'note' => 'Escape URLs and labels you did not write yourself.',
            'code' => '<a href="{{ esc_url($url) }}">{{ esc_html($label) }}</a>',
        ],
        [
            'title' => 'Get the current page title in Blade',
            'lang' => 'blade',
            'note' => 'Sage templates already have $title. Use get_the_title() if you are not on a standard loop.',
            'code' => "{{ \$title }}\n{{-- or --}}\n{{ get_the_title() }}",
        ],
        [
            'title' => 'Fetch GitHub without hammering the API',
            'lang' => 'php',
            'note' => 'Cache the response. Respect rate limits. Show a fallback if GitHub is down.',
            'code' => "\$key = 'mh_ghu_' . md5(\$user);\n\$data = get_transient(\$key);\nif (false === \$data) {\n    \$data = mh_fetch_github_user(\$user);\n    set_transient(\$key, \$data, 6 * HOUR_IN_SECONDS);\n}",
        ],
    ];
}

/**
 * DEV.to username used for profile links and the public RSS feed.
 */
function mh_devto_username(): string
{
    return (string) apply_filters('mh/devto_username', 'matthummeldev');
}

/**
 * Public DEV.to profile URL.
 */
function mh_devto_profile_url(): string
{
    return 'https://dev.to/'.rawurlencode(mh_devto_username());
}

/**
 * Resolve the DEV.to API key: wp-config → Customizer → filter.
 *
 * Needed for private endpoints such as followers. Generate a key at
 * https://dev.to/settings/extensions
 */
function mh_devto_token(): string
{
    if (defined('MH_DEVTO_TOKEN') && is_string(MH_DEVTO_TOKEN) && MH_DEVTO_TOKEN !== '') {
        return trim(MH_DEVTO_TOKEN);
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_devto_token', '')) : '';

    return (string) apply_filters('mh/devto_token', $mod);
}

function mh_devto_posts(int $limit = 5): array
{
    $key = 'mh_devto_feed_v2';
    $cached = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limit);
    }

    $posts = [];
    $feed = 'https://dev.to/feed/'.rawurlencode(mh_devto_username());
    $res = wp_remote_get($feed, [
        'timeout' => 8,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);

    if (! is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
        $body = wp_remote_retrieve_body($res);
        if ($body && class_exists(\SimpleXMLElement::class)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $desc = trim(wp_strip_all_tags(html_entity_decode((string) $item->description, ENT_QUOTES, 'UTF-8')));
                    $posts[] = [
                        'title' => (string) $item->title,
                        'url' => (string) $item->link,
                        'date' => (string) $item->pubDate,
                        'ex' => $desc !== '' ? wp_trim_words($desc, 22) : '',
                    ];
                }
            }
        }
    }

    set_transient($key, $posts, 3 * HOUR_IN_SECONDS);

    return array_slice($posts, 0, $limit);
}

/**
 * Normalize a follower row for the journal sidebar.
 *
 * @param  array<string, mixed>  $row
 * @return array{username: string, name: string, image: string, url: string}|null
 */
function mh_devto_follower_row(array $row): ?array
{
    $raw = strtolower(trim((string) ($row['username'] ?? $row['user_username'] ?? '')));
    $username = preg_replace('/[^a-z0-9_]/', '', $raw) ?? '';
    if ($username === '') {
        return null;
    }
    $name = trim((string) ($row['name'] ?? $row['user_name'] ?? $username));
    if ($name === '') {
        $name = $username;
    }
    $image = esc_url_raw((string) ($row['profile_image'] ?? $row['image'] ?? ''));
    $url = esc_url_raw((string) ($row['url'] ?? ''));
    if ($url === '') {
        $url = 'https://dev.to/'.rawurlencode($username);
    }

    return [
        'username' => $username,
        'name' => $name,
        'image' => $image,
        'url' => $url,
    ];
}

/**
 * Curated DEV.to followers from Journal page fields (fallback when API is empty).
 *
 * @return list<array{username: string, name: string, image: string, url: string}>
 */
function mh_devto_followers_curated(int $limit = 24, ?int $writeId = null): array
{
    $writeId = $writeId ?? mh_writing_id();
    $rows = field_rows('write_devto_followers', [], $writeId ?: null);
    $out = [];
    foreach ($rows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $norm = mh_devto_follower_row($row);
        if ($norm === null) {
            continue;
        }
        $out[] = $norm;
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

/**
 * DEV.to followers for the journal sidebar: API (when keyed) then curated fields.
 *
 * @return list<array{username: string, name: string, image: string, url: string}>
 */
function mh_devto_followers(int $limit = 24): array
{
    $limit = max(1, min(80, $limit));
    $filtered = apply_filters('mh/devto_followers', null, $limit);
    if (is_array($filtered)) {
        $out = [];
        foreach ($filtered as $row) {
            if (! is_array($row)) {
                continue;
            }
            $norm = mh_devto_follower_row($row);
            if ($norm !== null) {
                $out[] = $norm;
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    $key = 'mh_devto_followers_v1';
    $cached = get_transient($key);
    if (is_array($cached)) {
        $api = $cached;
    } else {
        $api = [];
        $token = mh_devto_token();
        if ($token !== '') {
            $page = 1;
            while (count($api) < 80 && $page <= 3) {
                $res = wp_remote_get(add_query_arg([
                    'per_page' => 80,
                    'page' => $page,
                    'sort' => '-created_at',
                ], 'https://dev.to/api/followers/users'), [
                    'timeout' => 8,
                    'headers' => [
                        'User-Agent' => 'matthummel.com',
                        'api-key' => $token,
                        'Accept' => 'application/vnd.forem.api-v1+json',
                    ],
                ]);
                if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
                    break;
                }
                $body = json_decode((string) wp_remote_retrieve_body($res), true);
                if (! is_array($body) || $body === []) {
                    break;
                }
                foreach ($body as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $norm = mh_devto_follower_row([
                        'username' => $row['username'] ?? '',
                        'name' => $row['name'] ?? '',
                        'profile_image' => $row['profile_image'] ?? '',
                    ]);
                    if ($norm !== null) {
                        $api[] = $norm;
                    }
                }
                if (count($body) < 80) {
                    break;
                }
                $page++;
            }
        }
        set_transient($key, $api, 6 * HOUR_IN_SECONDS);
    }

    if ($api !== []) {
        return array_slice($api, 0, $limit);
    }

    return mh_devto_followers_curated($limit);
}

function mh_latest_posts(int $limit = 3): array
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
    $out = [];
    foreach ($q->posts as $p) {
        $cats = get_the_category($p->ID);
        $cat = ($cats && ! is_wp_error($cats)) ? $cats[0] : null;
        $out[] = [
            'title' => get_the_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('M j, Y', $p),
            'date_iso' => get_the_date('c', $p),
            'ex' => wp_trim_words(get_the_excerpt($p), 28),
            'cat' => $cat ? $cat->name : '',
            'cat_url' => $cat ? (string) get_category_link($cat->term_id) : '',
            'cat_slug' => $cat ? $cat->slug : '',
            'minutes' => mh_reading_minutes($p),
            'thumb' => mh_post_card_image((int) $p->ID),
        ];
    }
    wp_reset_postdata();

    return $out;
}

function mh_reading_minutes(\WP_Post|int $post): int
{
    $post = get_post($post);
    if (! $post instanceof \WP_Post) {
        return 1;
    }
    $words = str_word_count(wp_strip_all_tags((string) $post->post_content));

    return max(1, (int) ceil($words / 200));
}

function mh_post_card_image(int $post_id): string
{
    $src = get_the_post_thumbnail_url($post_id, 'medium_large');

    return is_string($src) ? $src : '';
}

function mh_post_has_code(\WP_Post|int $post): bool
{
    $post = get_post($post);
    if (! $post instanceof \WP_Post) {
        return false;
    }

    return (bool) preg_match(
        '/```|<pre[\s>]|wp-block-code|wp-block-syntaxhighlighter|class=["\'][^"\']*language-/',
        (string) $post->post_content
    );
}

function mh_published_post_count(): int
{
    $counts = wp_count_posts('post');

    return isset($counts->publish) ? (int) $counts->publish : 0;
}

function mh_journal_is_oldest(): bool
{
    return strtolower((string) get_query_var('order')) === 'asc';
}

function mh_journal_sort_url(string $which): string
{
    $url = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($which === 'oldest') {
        return add_query_arg('order', 'asc', $url);
    }

    return remove_query_arg('order', $url);
}

/** @return list<array{year: int, count: int, url: string}> */
function mh_journal_years(): array
{
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT YEAR(post_date) AS y, COUNT(ID) AS n
         FROM {$wpdb->posts}
         WHERE post_type = 'post' AND post_status = 'publish'
         GROUP BY y
         ORDER BY y DESC"
    );
    $out = [];
    foreach ($rows ?: [] as $row) {
        $year = (int) $row->y;
        if ($year < 1970) {
            continue;
        }
        $out[] = [
            'year' => $year,
            'count' => (int) $row->n,
            'url' => get_year_link($year),
        ];
    }

    return $out;
}

/** @return list<array{title: string, url: string, date: string, comments: int}> */
function mh_popular_posts(int $limit = 5, int $exclude = 0): array
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'post__not_in' => $exclude ? [$exclude] : [],
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'orderby' => [
            'comment_count' => 'DESC',
            'date' => 'DESC',
        ],
    ]);
    $out = [];
    foreach ($q->posts as $p) {
        $comments = (int) $p->comment_count;
        if ($comments < 1) {
            continue;
        }
        $out[] = [
            'title' => get_the_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('', $p),
            'comments' => $comments,
        ];
    }

    return $out;
}

add_action('pre_get_posts', function (\WP_Query $query): void {
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }
    if (! ($query->is_home() || $query->is_category() || $query->is_tag() || $query->is_date() || $query->is_search())) {
        return;
    }
    if (strtolower((string) $query->get('order')) !== 'asc') {
        return;
    }
    $query->set('orderby', 'date');
    $query->set('order', 'ASC');
});

function mh_post_summary(\WP_Post $post): string
{
    $excerpt = trim((string) $post->post_excerpt);
    if ($excerpt !== '') {
        return wp_strip_all_tags($excerpt);
    }

    return wp_trim_words(wp_strip_all_tags($post->post_content), 48);
}

/**
 * Add heading ids and return [html, toc[]].
 *
 * @return array{0: string, 1: array<int, array{level: int, id: string, text: string}>}
 */
function mh_content_with_toc(string $html): array
{
    $toc = [];
    $used = [];
    $html = (string) preg_replace_callback(
        '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
        static function ($m) use (&$toc, &$used) {
            $level = (int) $m[1];
            $attrs = $m[2] ?? '';
            $inner = $m[3];
            $text = trim(wp_strip_all_tags($inner));
            if ($text === '') {
                return $m[0];
            }
            $id = '';
            if (preg_match('/\sid=(["\'])([^"\']+)\1/i', $attrs, $idm)) {
                $id = $idm[2];
            } else {
                $base = sanitize_title($text) ?: 'section';
                $id = $base;
                $n = 2;
                while (isset($used[$id])) {
                    $id = $base.'-'.$n;
                    $n++;
                }
                $attrs .= ' id="'.esc_attr($id).'"';
            }
            $used[$id] = true;
            $toc[] = [
                'level' => $level,
                'id' => $id,
                'text' => $text,
            ];

            return '<h'.$level.$attrs.'>'.$inner.'</h'.$level.'>';
        },
        $html
    );

    return [$html, $toc];
}

/**
 * Create standard portfolio pages once. Never deletes posts or categories.
 */
function mh_seed_portfolio_pages(): void
{
    if (get_option('mh_portfolio_seeded_v2')) {
        return;
    }

    $pages = [
        'home' => ['title' => 'Home', 'template' => 'template-home.blade.php'],
        'about' => ['title' => 'About', 'template' => 'template-about.blade.php'],
        'projects' => ['title' => 'Work', 'template' => 'template-projects.blade.php'],
        'services' => ['title' => 'Services', 'template' => 'template-services.blade.php'],
        'code' => ['title' => 'Code', 'template' => 'template-code.blade.php'],
        'contact' => ['title' => 'Contact', 'template' => 'template-contact.blade.php'],
        'now' => ['title' => 'Now', 'template' => 'template-now.blade.php'],
    ];

    $ids = [];
    foreach ($pages as $slug => $meta) {
        $existing = get_page_by_path($slug);
        if ($existing instanceof \WP_Post) {
            $ids[$slug] = $existing->ID;
            update_post_meta($existing->ID, '_wp_page_template', $meta['template']);

            continue;
        }
        $id = wp_insert_post([
            'post_title' => $meta['title'],
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($id && ! is_wp_error($id)) {
            $ids[$slug] = (int) $id;
            update_post_meta($id, '_wp_page_template', $meta['template']);
        }
    }

    $blog = get_page_by_path('blog');
    if (! $blog) {
        $blogId = wp_insert_post([
            'post_title' => 'Journal',
            'post_name' => 'blog',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($blogId && ! is_wp_error($blogId)) {
            $ids['blog'] = (int) $blogId;
        }
    } else {
        $ids['blog'] = $blog->ID;
        if ($blog->post_title === 'Writing') {
            wp_update_post(['ID' => $blog->ID, 'post_title' => 'Journal']);
        }
    }

    if (! empty($ids['home']) && ! empty($ids['blog'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids['home']);
        update_option('page_for_posts', $ids['blog']);
    }

    $menuName = 'Primary';
    $menu = wp_get_nav_menu_object($menuName);
    if (! $menu) {
        $menuId = wp_create_nav_menu($menuName);
    } else {
        $menuId = (int) $menu->term_id;
        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                wp_delete_post((int) $item->ID, true);
            }
        }
    }

    $order = ['home', 'about', 'projects', 'services', 'code', 'blog', 'contact'];
    $n = 1;
    foreach ($order as $slug) {
        if (empty($ids[$slug])) {
            continue;
        }
        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => get_the_title($ids[$slug]),
            'menu-item-object' => 'page',
            'menu-item-object-id' => $ids[$slug],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $n++,
        ]);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['primary_navigation'] = (int) $menuId;
    set_theme_mod('nav_menu_locations', $locations);

    foreach (mh_portfolio_social_defaults() as $key => $url) {
        $current = get_theme_mod("mh_social_{$key}", '');
        if ($current === ''
            || $current === 'https://www.linkedin.com/in/matthummel'
            || $current === 'https://dev.to/matthummel') {
            set_theme_mod("mh_social_{$key}", $url);
        }
    }

    update_option('mh_portfolio_seeded_v2', true);
}

/**
 * Profile photo: Customizer upload, then GitHub avatar, then bundled headshot, then Gravatar.
 */
function mh_profile_photo_url(int $size = 160): string
{
    $id = (int) get_theme_mod('mh_profile_photo', 0);
    if ($id > 0) {
        $src = wp_get_attachment_image_url($id, [$size, $size]);
        if (is_string($src) && $src !== '') {
            return $src;
        }
    }

    $user = Github::fetchUser(mh_github_login());
    if (! empty($user['avatar'])) {
        $sep = str_contains((string) $user['avatar'], '?') ? '&' : '?';

        return $user['avatar'].$sep.'s='.$size;
    }

    $rel = 'resources/images/matt-hummel.jpg';
    if (is_readable(get_theme_file_path($rel))) {
        return get_theme_file_uri($rel);
    }

    $email = (string) get_option('admin_email');

    return $email !== '' ? (string) get_avatar_url($email, ['size' => $size]) : '';
}

add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    $wp->add_section('mh_identity', [
        'title' => __('Profile photo', 'sage'),
        'priority' => 32,
    ]);
    $wp->add_setting('mh_profile_photo', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp->add_control(new \WP_Customize_Media_Control($wp, 'mh_profile_photo', [
        'label' => __('Photo', 'sage'),
        'description' => __('Used next to your name on Home, About, and posts. A square crop works best. Leave empty to use your GitHub profile photo.', 'sage'),
        'section' => 'mh_identity',
        'mime_type' => 'image',
    ]));
});

function mh_font_stylesheet(): string
{
    return 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@600;700;800;900&display=swap';
}

$enqueueFonts = static function (): void {
    wp_enqueue_style('mh-fonts', mh_font_stylesheet(), [], null);
};
add_action('wp_enqueue_scripts', $enqueueFonts, 5);
add_action('enqueue_block_editor_assets', $enqueueFonts, 5);
add_filter('style_loader_tag', function (string $html, string $handle): string {
    if ($handle !== 'mh-fonts') {
        return $html;
    }

    $pre = '<link rel="preconnect" href="https://fonts.googleapis.com">'."\n";
    $pre .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'."\n";

    return $pre.$html;
}, 10, 2);

add_action('after_switch_theme', __NAMESPACE__.'\\mh_seed_portfolio_pages');
add_action('init', function () {
    if (! get_option('mh_portfolio_seeded_v2') && ! wp_installing()) {
        mh_seed_portfolio_pages();
    }
}, 30);

/**
 * Ensure the project brief page exists (idempotent).
 *
 * @since 3.1.4
 */
function mh_ensure_start_page(): void
{
    if (wp_installing()) {
        return;
    }

    $existing = get_page_by_path('start');
    if ($existing instanceof \WP_Post) {
        update_post_meta($existing->ID, '_wp_page_template', 'template-start.blade.php');

        return;
    }

    $id = wp_insert_post([
        'post_title' => 'Start',
        'post_name' => 'start',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '',
    ], true);

    if (is_wp_error($id) || ! $id) {
        return;
    }

    update_post_meta((int) $id, '_wp_page_template', 'template-start.blade.php');
}
add_action('init', __NAMESPACE__.'\\mh_ensure_start_page', 35);

/**
 * Ensure a published page exists with a Blade template (idempotent).
 */
function mh_ensure_theme_page(string $slug, string $title, string $template): int
{
    if (wp_installing()) {
        return 0;
    }

    $existing = get_page_by_path($slug);
    if ($existing instanceof \WP_Post) {
        update_post_meta($existing->ID, '_wp_page_template', $template);
        if ($existing->post_status !== 'publish') {
            wp_update_post([
                'ID' => $existing->ID,
                'post_status' => 'publish',
            ]);
        }

        return (int) $existing->ID;
    }

    $id = wp_insert_post([
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '',
    ], true);

    if (is_wp_error($id) || ! $id) {
        return 0;
    }

    update_post_meta((int) $id, '_wp_page_template', $template);

    return (int) $id;
}

/**
 * Ensure Hire me page exists (idempotent).
 */
function mh_ensure_hire_page(): void
{
    mh_ensure_theme_page('hire', 'Hire me', 'template-hire.blade.php');
}
add_action('init', __NAMESPACE__.'\\mh_ensure_hire_page', 35);

/**
 * Ensure Changelog and other utility pages exist (idempotent).
 */
function mh_ensure_utility_pages(): void
{
    $pages = [
        'changelog' => ['Changelog', 'template-changelog.blade.php'],
        'privacy' => ['Privacy', 'template-privacy.blade.php'],
        'terms' => ['Terms', 'template-terms.blade.php'],
        'accessibility' => ['Accessibility', 'template-accessibility.blade.php'],
        'uses' => ['Uses', 'template-uses.blade.php'],
        'thank-you' => ['Thank you', 'template-thankyou.blade.php'],
    ];

    foreach ($pages as $slug => [$title, $template]) {
        mh_ensure_theme_page($slug, $title, $template);
    }
}
add_action('init', __NAMESPACE__.'\\mh_ensure_utility_pages', 35);

/**
 * Ensure the DEV.to journal category exists.
 */
function mh_devto_category_id(): int
{
    $existing = get_term_by('slug', 'dev-to', 'category');
    if ($existing instanceof \WP_Term) {
        return (int) $existing->term_id;
    }

    $created = wp_insert_term('DEV.to', 'category', [
        'slug' => 'dev-to',
        'description' => __('Posts cross-posted from DEV.to.', 'sage'),
    ]);
    if (is_wp_error($created)) {
        $term = get_term_by('name', 'DEV.to', 'category');

        return $term instanceof \WP_Term ? (int) $term->term_id : 0;
    }

    return (int) ($created['term_id'] ?? 0);
}

/**
 * Strip the DEV.to hash suffix from an article slug for WordPress.
 */
function mh_devto_post_slug(string $slug): string
{
    $slug = sanitize_title($slug);
    $trimmed = preg_replace('/-[a-z0-9]{3,6}$/', '', $slug);

    return is_string($trimmed) && $trimmed !== '' ? $trimmed : $slug;
}

/**
 * Escape inline markdown (links, bold, code) into safe HTML.
 */
function mh_devto_inline_html(string $text): string
{
    $out = '';
    $offset = 0;
    $pattern = '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)|`([^`]+)`|\*\*(.+?)\*\*|\*(.+?)\*/';
    if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        return esc_html($text);
    }
    foreach ($matches as $m) {
        $start = (int) $m[0][1];
        $out .= esc_html(substr($text, $offset, $start - $offset));
        if (! empty($m[1][0]) && ! empty($m[2][0])) {
            $out .= '<a href="'.esc_url($m[2][0]).'">'.esc_html($m[1][0]).'</a>';
        } elseif (! empty($m[3][0])) {
            $out .= '<code>'.esc_html($m[3][0]).'</code>';
        } elseif (! empty($m[4][0])) {
            $out .= '<strong>'.esc_html($m[4][0]).'</strong>';
        } elseif (! empty($m[5][0])) {
            $out .= '<em>'.esc_html($m[5][0]).'</em>';
        } else {
            $out .= esc_html($m[0][0]);
        }
        $offset = $start + strlen($m[0][0]);
    }
    $out .= esc_html(substr($text, $offset));

    return $out;
}

/**
 * Convert DEV.to markdown into Gutenberg block markup.
 */
function mh_devto_markdown_to_blocks(string $md): string
{
    $md = str_replace(["\r\n", "\r"], "\n", $md);
    $lines = explode("\n", $md);
    $blocks = [];
    $i = 0;
    $n = count($lines);

    while ($i < $n) {
        $line = $lines[$i];
        $trim = trim($line);

        if ($trim === '') {
            $i++;

            continue;
        }

        if ($trim === '---' || $trim === '***' || $trim === '___') {
            $blocks[] = "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->";
            $i++;

            continue;
        }

        if (preg_match('/^```(\w*)\s*$/', $trim, $fence)) {
            $lang = $fence[1] !== '' ? $fence[1] : '';
            $code = [];
            $i++;
            while ($i < $n && ! preg_match('/^```\s*$/', trim($lines[$i]))) {
                $code[] = $lines[$i];
                $i++;
            }
            $i++;
            $body = esc_html(implode("\n", $code));
            $class = $lang !== '' ? ' language-'.sanitize_html_class($lang) : '';
            $blocks[] = "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code class=\"{$class}\">{$body}</code></pre>\n<!-- /wp:code -->";

            continue;
        }

        if (preg_match('/^(#{2,4})\s+(.+)$/', $trim, $hm)) {
            $level = strlen($hm[1]);
            $heading = mh_devto_inline_html($hm[2]);
            if ($level === 2) {
                $blocks[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$heading}</h2>\n<!-- /wp:heading -->";
            } else {
                $blocks[] = "<!-- wp:heading {\"level\":{$level}} -->\n<h{$level} class=\"wp-block-heading\">{$heading}</h{$level}>\n<!-- /wp:heading -->";
            }
            $i++;

            continue;
        }

        if (preg_match('/^!\[[^\]]*\]\((https?:\/\/[^)\s]+)\)/', $trim, $im)) {
            $src = esc_url($im[1]);
            $blocks[] = "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"{$src}\" alt=\"\"/></figure>\n<!-- /wp:image -->";
            $i++;

            continue;
        }

        if (preg_match('/^[-*]\s+(.+)$/', $trim) || preg_match('/^\d+\.\s+(.+)$/', $trim)) {
            $ordered = (bool) preg_match('/^\d+\.\s+/', $trim);
            $items = [];
            while ($i < $n) {
                $t = trim($lines[$i]);
                if ($ordered && preg_match('/^\d+\.\s+(.+)$/', $t, $lm)) {
                    $items[] = '<li>'.mh_devto_inline_html($lm[1]).'</li>';
                    $i++;
                } elseif (! $ordered && preg_match('/^[-*]\s+(.+)$/', $t, $lm)) {
                    $items[] = '<li>'.mh_devto_inline_html($lm[1]).'</li>';
                    $i++;
                } else {
                    break;
                }
            }
            if ($ordered) {
                $blocks[] = "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">".implode('', $items)."</ol>\n<!-- /wp:list -->";
            } else {
                $blocks[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">".implode('', $items)."</ul>\n<!-- /wp:list -->";
            }

            continue;
        }

        $para = [];
        while ($i < $n) {
            $t = trim($lines[$i]);
            if ($t === '' || $t === '---' || preg_match('/^#{2,4}\s+/', $t) || preg_match('/^```/', $t) || preg_match('/^[-*]\s+/', $t) || preg_match('/^\d+\.\s+/', $t) || preg_match('/^!\[[^\]]*\]\(/', $t)) {
                break;
            }
            $para[] = $t;
            $i++;
        }
        if ($para !== []) {
            $blocks[] = "<!-- wp:paragraph -->\n<p>".mh_devto_inline_html(implode(' ', $para))."</p>\n<!-- /wp:paragraph -->";
        }
    }

    return implode("\n\n", $blocks);
}

/**
 * Find a journal post previously imported from a DEV.to article id.
 */
function mh_devto_find_imported_post(int $articleId): int
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_mh_devto_id',
        'meta_value' => (string) $articleId,
    ]);

    return ! empty($q->posts[0]) ? (int) $q->posts[0] : 0;
}

/**
 * Fetch published articles for the configured DEV.to username.
 *
 * @return list<array<string, mixed>>
 */
function mh_devto_fetch_article_list(int $perPage = 30): array
{
    $res = wp_remote_get(add_query_arg([
        'username' => mh_devto_username(),
        'per_page' => max(1, min(100, $perPage)),
    ], 'https://dev.to/api/articles'), [
        'timeout' => 12,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return [];
    }
    $body = json_decode((string) wp_remote_retrieve_body($res), true);

    return is_array($body) ? $body : [];
}

/**
 * Fetch one DEV.to article (includes body_markdown).
 *
 * @return array<string, mixed>|null
 */
function mh_devto_fetch_article(int $id): ?array
{
    $res = wp_remote_get('https://dev.to/api/articles/'.$id, [
        'timeout' => 12,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return null;
    }
    $body = json_decode((string) wp_remote_retrieve_body($res), true);

    return is_array($body) ? $body : null;
}

/**
 * Sideload a remote cover image and attach it as the featured image.
 */
function mh_devto_sideload_cover(int $postId, string $url): void
{
    if ($url === '' || $postId <= 0) {
        return;
    }
    if (! function_exists('media_sideload_image')) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
    }
    $id = media_sideload_image($url, $postId, null, 'id');
    if (! is_wp_error($id) && $id) {
        set_post_thumbnail($postId, (int) $id);
    }
}

/**
 * Import or update one DEV.to article as a regular journal post.
 *
 * @param  array<string, mixed>  $article  Full article payload from DEV.to.
 * @return array{ok: bool, post_id: int, action: string, message: string}
 */
function mh_devto_import_article(array $article, bool $force = false): array
{
    $articleId = (int) ($article['id'] ?? 0);
    $title = trim((string) ($article['title'] ?? ''));
    if ($articleId <= 0 || $title === '') {
        return ['ok' => false, 'post_id' => 0, 'action' => 'skip', 'message' => 'Missing article id or title'];
    }

    $catId = mh_devto_category_id();
    if ($catId <= 0) {
        return ['ok' => false, 'post_id' => 0, 'action' => 'skip', 'message' => 'Could not create DEV.to category'];
    }

    $existingId = mh_devto_find_imported_post($articleId);
    if ($existingId > 0 && ! $force) {
        wp_set_post_categories($existingId, [$catId], true);

        return ['ok' => true, 'post_id' => $existingId, 'action' => 'exists', 'message' => 'Already imported'];
    }

    $md = (string) ($article['body_markdown'] ?? '');
    if ($md === '' && ! empty($article['id'])) {
        $full = mh_devto_fetch_article($articleId);
        if ($full) {
            $article = $full;
            $md = (string) ($full['body_markdown'] ?? '');
        }
    }

    $devtoUrl = (string) ($article['url'] ?? '');
    $content = mh_devto_markdown_to_blocks($md);
    if ($devtoUrl !== '') {
        $content .= "\n\n<!-- wp:paragraph -->\n<p><em>Originally posted on <a href=\"".esc_url($devtoUrl)."\">DEV.to</a>.</em></p>\n<!-- /wp:paragraph -->";
    }

    $slug = mh_devto_post_slug((string) ($article['slug'] ?? ''));
    $excerpt = trim((string) ($article['description'] ?? ''));
    $published = (string) ($article['published_at'] ?? $article['created_at'] ?? current_time('mysql'));
    $date = gmdate('Y-m-d H:i:s', strtotime($published) ?: time());

    $payload = [
        'post_title' => $title,
        'post_name' => $slug !== '' ? $slug : sanitize_title($title),
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_date' => get_date_from_gmt($date),
        'post_date_gmt' => $date,
        'post_category' => [$catId],
    ];

    if ($existingId > 0) {
        $payload['ID'] = $existingId;
        $postId = wp_update_post($payload, true);
        $action = 'updated';
    } else {
        $postId = wp_insert_post($payload, true);
        $action = 'created';
    }

    if (is_wp_error($postId) || ! $postId) {
        return [
            'ok' => false,
            'post_id' => 0,
            'action' => 'error',
            'message' => is_wp_error($postId) ? $postId->get_error_message() : 'Insert failed',
        ];
    }

    $postId = (int) $postId;
    update_post_meta($postId, '_mh_devto_id', (string) $articleId);
    if ($devtoUrl !== '') {
        update_post_meta($postId, '_mh_devto_url', esc_url_raw($devtoUrl));
    }

    $cover = (string) ($article['cover_image'] ?? $article['social_image'] ?? '');
    if ($cover !== '' && ! has_post_thumbnail($postId)) {
        mh_devto_sideload_cover($postId, $cover);
    }

    return ['ok' => true, 'post_id' => $postId, 'action' => $action, 'message' => $title];
}

/**
 * Import all public DEV.to articles into the journal.
 *
 * @return list<array{ok: bool, post_id: int, action: string, message: string}>
 */
function mh_devto_import_all(bool $force = false): array
{
    $list = mh_devto_fetch_article_list(30);
    $results = [];
    foreach ($list as $summary) {
        if (! is_array($summary) || empty($summary['id'])) {
            continue;
        }
        $full = mh_devto_fetch_article((int) $summary['id']);
        if (! $full) {
            $results[] = [
                'ok' => false,
                'post_id' => 0,
                'action' => 'error',
                'message' => 'Could not fetch article '.(int) $summary['id'],
            ];

            continue;
        }
        $results[] = mh_devto_import_article($full, $force);
    }

    return $results;
}

/**
 * Whether automatic DEV.to → journal import is enabled.
 */
function mh_devto_auto_import_enabled(): bool
{
    if (! function_exists('get_theme_mod')) {
        return true;
    }

    return (bool) apply_filters(
        'mh/devto_auto_import',
        (bool) get_theme_mod('mh_devto_auto_import', true)
    );
}

/**
 * Cron callback: pull new DEV.to articles into the journal.
 *
 * Skips posts that already have `_mh_devto_id` meta unless forced elsewhere.
 */
function mh_devto_cron_sync(): void
{
    if (! mh_devto_auto_import_enabled()) {
        return;
    }

    $results = mh_devto_import_all(false);
    $created = 0;
    $errors = 0;
    foreach ($results as $row) {
        if (! empty($row['ok']) && ($row['action'] ?? '') === 'created') {
            $created++;
        }
        if (empty($row['ok'])) {
            $errors++;
        }
    }

    update_option('mh_devto_last_sync', [
        'at' => time(),
        'created' => $created,
        'errors' => $errors,
        'checked' => count($results),
    ], false);
}

/**
 * Schedule (or clear) the hourly DEV.to sync event.
 */
function mh_devto_schedule_cron(): void
{
    $hook = 'mh_devto_sync';
    $scheduled = wp_next_scheduled($hook);

    if (! mh_devto_auto_import_enabled()) {
        if ($scheduled) {
            wp_unschedule_event($scheduled, $hook);
        }

        return;
    }

    if (! $scheduled) {
        wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'hourly', $hook);
    }
}

add_action('mh_devto_sync', __NAMESPACE__.'\\mh_devto_cron_sync');
add_action('init', __NAMESPACE__.'\\mh_devto_schedule_cron', 40);
add_action('after_switch_theme', __NAMESPACE__.'\\mh_devto_schedule_cron');
add_action('customize_save_after', __NAMESPACE__.'\\mh_devto_schedule_cron');

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh devto-import', function ($args, $assoc): void {
        $force = isset($assoc['force']);
        \WP_CLI::log('Ensuring DEV.to category…');
        $catId = mh_devto_category_id();
        if ($catId <= 0) {
            \WP_CLI::error('Could not create the DEV.to category.');
        }
        \WP_CLI::log("Category id {$catId}. Fetching articles…");
        $results = mh_devto_import_all($force);
        $created = 0;
        $updated = 0;
        $exists = 0;
        $errors = 0;
        foreach ($results as $row) {
            $label = strtoupper($row['action']);
            if ($row['ok']) {
                \WP_CLI::log("[{$label}] #{$row['post_id']} {$row['message']}");
                if ($row['action'] === 'created') {
                    $created++;
                } elseif ($row['action'] === 'updated') {
                    $updated++;
                } else {
                    $exists++;
                }
            } else {
                \WP_CLI::warning("[{$label}] {$row['message']}");
                $errors++;
            }
        }
        \WP_CLI::success("Import done. created={$created} updated={$updated} exists={$exists} errors={$errors}");
    }, [
        'shortdesc' => 'Import DEV.to articles into the journal as regular posts.',
        'synopsis' => [
            [
                'type' => 'flag',
                'name' => 'force',
                'optional' => true,
                'description' => 'Update content for posts that were already imported.',
            ],
        ],
    ]);

    \WP_CLI::add_command('mh devto-sync', function (): void {
        if (! mh_devto_auto_import_enabled()) {
            \WP_CLI::warning('Auto-import is disabled (Customizer → DEV.to). Running once anyway…');
        }
        mh_devto_cron_sync();
        $last = get_option('mh_devto_last_sync', []);
        \WP_CLI::success(sprintf(
            'Sync finished. checked=%d created=%d errors=%d',
            (int) ($last['checked'] ?? 0),
            (int) ($last['created'] ?? 0),
            (int) ($last['errors'] ?? 0)
        ));
    }, [
        'shortdesc' => 'Run the scheduled DEV.to → journal sync once.',
    ]);
}

add_filter('matthummel/cta_heading', fn () => __('Have a small project in mind?', 'matthummel'));
add_filter('matthummel/cta_text', fn () => __('I take on full-stack web applications, custom WordPress work, plugins, integrations, and agency overflow. Write a short note and I will reply in one or two business days.', 'matthummel'));
add_filter('matthummel/cta_label', fn () => __('Get in touch', 'matthummel'));

/**
 * Default service cards for the About page.
 *
 * @return list<array{icon: string, title: string, body: string}>
 */
function mh_about_services_defaults(): array
{
    return [
        [
            'icon' => 'wordpress',
            'title' => __('WordPress sites', 'sage'),
            'body' => __('New sites, sites that need a cleanup, and themes shops can edit themselves. Practical WordPress — not a pile of plugins they will never use.', 'sage'),
        ],
        [
            'icon' => 'plugins',
            'title' => __('Plugins and tools', 'sage'),
            'body' => __('Custom pieces when WordPress needs something it does not ship with. Small, documented, and written so the next developer is not stuck guessing.', 'sage'),
        ],
        [
            'icon' => 'code',
            'title' => __('Full-stack web applications', 'sage'),
            'body' => __('React interfaces, PHP or Node services, authentication, databases, APIs, and deployment workflows built as one maintainable system.', 'sage'),
        ],
    ];
}

/**
 * Default open-for-work arrangement cards.
 *
 * @return list<array{title: string, detail: string}>
 */
function mh_about_work_types_defaults(): array
{
    return [
        [
            'title' => __('Full-time roles', 'sage'),
            'detail' => __('Full-stack web development with deep WordPress and PHP experience. Open to on-site, hybrid, or remote from Gettysburg.', 'sage'),
        ],
        [
            'title' => __('Contract and freelance', 'sage'),
            'detail' => __('Project-based work with a clear, written scope for shops and agencies.', 'sage'),
        ],
        [
            'title' => __('Agency sub-contracting', 'sage'),
            'detail' => __('You keep the client relationship. I build the WordPress platform, integration, or web application.', 'sage'),
        ],
        [
            'title' => __('Part-time arrangements', 'sage'),
            'detail' => __('A few hours a week or a focused sprint. Flexible.', 'sage'),
        ],
    ];
}

/**
 * Default approach / how-I-work cards for About.
 *
 * @return list<array{icon: string, title: string, body: string}>
 */
function mh_about_approach_defaults(): array
{
    return [
        [
            'icon' => 'briefcase',
            'title' => __('Shops own everything', 'sage'),
            'body' => __('Hosting, domain, database, and code belong to the shop before we close out. No lingering access unless you invite me back.', 'sage'),
        ],
        [
            'icon' => 'users',
            'title' => __('The admin experience is part of the build', 'sage'),
            'body' => __('A site that is hard to update does not get updated. I aim for edit flows an owner can finish in a couple of minutes without pinging me.', 'sage'),
        ],
        [
            'icon' => 'code',
            'title' => __('Plain, readable code', 'sage'),
            'body' => __('If a developer cannot understand a function in half a minute, it is too clever. I write for the next person who has to read it.', 'sage'),
        ],
        [
            'icon' => 'cursor-ai',
            'title' => __('AI assists. I review everything.', 'sage'),
            'body' => __('I use Cursor, Claude, and ChatGPT to move faster on the boring parts. Every line still gets read and tested by me before it ships.', 'sage'),
        ],
        [
            'icon' => 'book-open',
            'title' => __('Accessibility and plain language by default', 'sage'),
            'body' => __('Keyboard-friendly, screen-reader-aware pages, written so a busy shop owner can follow along. Not a checkbox — just how the work should go.', 'sage'),
        ],
        [
            'icon' => 'plugins',
            'title' => __('Small, focused plugins', 'sage'),
            'body' => __('One plugin should do one thing well. I cut weight that does not earn its keep. Most sites need a handful of plugins, not a drawer full.', 'sage'),
        ],
    ];
}
