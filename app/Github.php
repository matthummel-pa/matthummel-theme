<?php

namespace App;

/**
 * Live, cached GitHub repo data for project case studies.
 * Mirrors the matthummel.com [mh_github] feature: repo metadata,
 * latest release, and a cleaned README intro — cached for 6 hours.
 */

/**
 * Resolve the GitHub API token: wp-config constant → Customizer/updater theme mod → filter.
 *
 * @since 3.1.0
 *
 * @return string Token string, or empty string when none is configured.
 */
function github_token(): string
{
    if (defined('MH_GITHUB_TOKEN') && is_string(MH_GITHUB_TOKEN) && MH_GITHUB_TOKEN !== '') {
        return trim(MH_GITHUB_TOKEN);
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_gh_token', '')) : '';

    return (string) apply_filters('mh/github_token', $mod);
}

/**
 * Build the default request headers for GitHub API calls.
 *
 * Includes Accept, API version, User-Agent, and Bearer token when available.
 *
 * @since 3.1.0
 *
 * @return array<string, string>
 */
function github_headers(): array
{
    $headers = [
        'Accept' => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
        'User-Agent' => 'matthummel-theme/3 (+'.(function_exists('home_url') ? home_url('/') : 'https://matthummel.com').')',
    ];
    $token = github_token();
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer '.$token;
    }

    return $headers;
}

/**
 * Perform a GET request to a GitHub API URL and return the decoded JSON payload.
 *
 * @since 3.1.0
 *
 * @param  string  $url  Full GitHub API URL.
 * @return array<string, mixed>|null Decoded response data, or null on error or non-200 status.
 */
function github_get(string $url): ?array
{
    $res = wp_remote_get($url, ['timeout' => 12, 'headers' => github_headers()]);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
        return null;
    }
    $data = json_decode((string) wp_remote_retrieve_body($res), true);

    return is_array($data) ? $data : null;
}

class Github
{
    /**
     * Build wp_remote_get/post args with shared headers and timeout.
     *
     * @param  string  $accept  Value for the Accept header.
     * @return array<string, mixed>
     */
    protected static function args(string $accept = 'application/vnd.github+json'): array
    {
        $h = github_headers();
        $h['Accept'] = $accept;

        return ['timeout' => 12, 'headers' => $h];
    }

    /**
     * Transient cache TTL in seconds, derived from the mh_proj_cache_hours theme mod.
     *
     * @return int
     */
    protected static function ttl(): int
    {
        return max(1, (int) (function_exists('get_theme_mod') ? get_theme_mod('mh_proj_cache_hours', 6) : 6)) * HOUR_IN_SECONDS;
    }

    /**
     * Fetch and cache a GitHub user or organisation profile.
     *
     * @param  string  $user  GitHub login (username or organisation slug).
     * @return array<string, mixed> Profile data, or empty array on failure.
     */
    public static function fetchUser(string $user): array
    {
        $key = 'mh_ghu2_'.md5($user);
        if (($d = get_transient($key)) !== false) {
            return $d;
        }
        $d = [];
        $r = wp_remote_get("https://api.github.com/users/{$user}", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $d = [
                'login' => $j['login'] ?? $user,
                'name' => $j['name'] ?? ($j['login'] ?? $user),
                'bio' => $j['bio'] ?? '',
                'avatar' => $j['avatar_url'] ?? '',
                'url' => $j['html_url'] ?? '',
                'location' => $j['location'] ?? '',
                'blog' => $j['blog'] ?? '',
                'hireable' => ! empty($j['hireable']),
                'followers' => (int) ($j['followers'] ?? 0),
                'following' => (int) ($j['following'] ?? 0),
                'public_repos' => (int) ($j['public_repos'] ?? 0),
                'created' => isset($j['created_at']) ? (string) substr((string) $j['created_at'], 0, 4) : '',
            ];
        }
        set_transient($key, $d, self::ttl());

        return $d;
    }

    /**
     * Fetch and cache a user's own public repositories, excluding forks.
     *
     * @param  string  $user   GitHub login.
     * @param  int     $count  Maximum number of repositories to return (1–30).
     * @param  string  $sort   Sort field: updated|pushed|full_name|created.
     * @return list<array<string, mixed>>
     */
    public static function fetchRepos(string $user, int $count = 6, string $sort = 'updated'): array
    {
        $count = max(1, min(30, $count));
        $sort = in_array($sort, ['updated', 'pushed', 'full_name', 'created'], true) ? $sort : 'updated';
        $key = 'mh_ghr4_'.md5($user.$sort.$count);
        if (($d = get_transient($key)) !== false) {
            return $d;
        }
        $out = [];
        $r = wp_remote_get("https://api.github.com/users/{$user}/repos?per_page=30&sort={$sort}&type=owner", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                if (! empty($j['fork'])) {
                    continue;
                }
                $name = (string) ($j['name'] ?? '');
                if ($name === '' || strcasecmp($name, $user) === 0) {
                    continue;
                }
                $topics = $j['topics'] ?? [];
                $out[] = [
                    'name' => $name,
                    'full' => $j['full_name'] ?? '',
                    'desc' => $j['description'] ?? '',
                    'stars' => (int) ($j['stargazers_count'] ?? 0),
                    'forks' => (int) ($j['forks_count'] ?? 0),
                    'lang' => $j['language'] ?? '',
                    'url' => $j['html_url'] ?? '',
                    'homepage' => $j['homepage'] ?? '',
                    'topics' => is_array($topics) ? $topics : [],
                    'pushed' => (string) ($j['pushed_at'] ?? ''),
                    'updated' => (string) ($j['updated_at'] ?? ''),
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        set_transient($key, $out, self::ttl());

        return $out;
    }

    /**
     * Fetch and cache the programming-language breakdown for a repository, largest first.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo   Repository name.
     * @return list<string> Language names sorted by byte count descending.
     */
    public static function fetchLanguages(string $owner, string $repo): array
    {
        $owner = rawurlencode($owner);
        $repo = rawurlencode($repo);
        $key = 'mh_ghlang_'.md5($owner.'/'.$repo);
        if (($d = get_transient($key)) !== false) {
            return is_array($d) ? $d : [];
        }
        $out = [];
        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/languages", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            if (is_array($j)) {
                arsort($j);
                $out = array_values(array_map('strval', array_keys($j)));
            }
        }
        set_transient($key, $out, self::ttl());

        return $out;
    }

    /**
     * Fetch and cache extended single-repository metadata for featured project cards.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo   Repository name.
     * @return array<string, mixed> Repository metadata, or empty array on failure.
     */
    public static function fetchRepoMeta(string $owner, string $repo): array
    {
        $key = 'mh_ghmeta2_'.md5($owner.'/'.$repo);
        if (($d = get_transient($key)) !== false) {
            return is_array($d) ? $d : [];
        }
        $d = [];
        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $topics = $j['topics'] ?? [];
            $d = [
                'desc' => (string) ($j['description'] ?? ''),
                'stars' => (int) ($j['stargazers_count'] ?? 0),
                'forks' => (int) ($j['forks_count'] ?? 0),
                'lang' => (string) ($j['language'] ?? ''),
                'url' => (string) ($j['html_url'] ?? ''),
                'homepage' => (string) ($j['homepage'] ?? ''),
                'topics' => is_array($topics) ? $topics : [],
                'pushed' => (string) ($j['pushed_at'] ?? ''),
            ];
        }
        set_transient($key, $d, self::ttl());

        return $d;
    }

    /**
     * Fetch and cache recent GitHub releases for a repository.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo   Repository name.
     * @param  int     $count  Maximum number of releases to return (1–20).
     * @return list<array<string, mixed>>
     */
    public static function fetchReleases(string $owner, string $repo, int $count = 5): array
    {
        $count = max(1, min(20, $count));
        $key = 'mh_ghrel_'.md5("{$owner}/{$repo}/{$count}");
        if (($d = get_transient($key)) !== false) {
            return $d;
        }
        $out = [];
        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/releases?per_page={$count}", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                $out[] = [
                    'tag' => $j['tag_name'] ?? '',
                    'name' => ($j['name'] ?? '') ?: ($j['tag_name'] ?? ''),
                    'url' => $j['html_url'] ?? '',
                    'date' => isset($j['published_at']) ? date_i18n(get_option('date_format'), strtotime($j['published_at'])) : '',
                    'prerelease' => ! empty($j['prerelease']),
                ];
            }
        }
        set_transient($key, $out, self::ttl());

        return $out;
    }

    /**
     * Fetch and cache combined repository data: stats, latest release, and README intro.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo   Repository name.
     * @return array<string, mixed> Combined data, or empty array on API failure.
     */
    public static function fetch(string $owner, string $repo): array
    {
        $key = 'mh_gh_'.md5($owner.'/'.$repo);

        if (($data = get_transient($key)) !== false) {
            return $data;
        }

        $data = [];
        $jargs = self::args();

        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}", $jargs);
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $data['desc'] = $j['description'] ?? '';
            $data['stars'] = (int) ($j['stargazers_count'] ?? 0);
            $data['forks'] = (int) ($j['forks_count'] ?? 0);
            $data['lang'] = $j['language'] ?? '';
            $data['license'] = (isset($j['license']['spdx_id']) && $j['license']['spdx_id'] !== 'NOASSERTION')
                ? $j['license']['spdx_id'] : '';
            $data['url'] = $j['html_url'] ?? '';
        }

        $rel = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest", $jargs);
        if (! is_wp_error($rel) && wp_remote_retrieve_response_code($rel) === 200) {
            $jr = json_decode(wp_remote_retrieve_body($rel), true);
            $data['release'] = $jr['tag_name'] ?? '';
        }

        $rmHeaders = github_headers();
        $rmHeaders['Accept'] = 'application/vnd.github.html';
        $rm = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/readme", ['timeout' => 12, 'headers' => $rmHeaders]);
        if (! is_wp_error($rm) && wp_remote_retrieve_response_code($rm) === 200) {
            $data['intro'] = self::readmeIntro(wp_remote_retrieve_body($rm));
        }

        set_transient($key, $data, self::ttl());

        return $data;
    }

    /** Extract a clean README intro: up to the 2nd <h2>, headings demoted, badges/anchors stripped. */
    protected static function readmeIntro(string $body): string
    {
        $p1 = stripos($body, '<h2');
        $cut = strlen($body);
        if ($p1 !== false) {
            $p2 = stripos($body, '<h2', $p1 + 3);
            $cut = ($p2 !== false) ? $p2 : strlen($body);
        }
        $intro = substr($body, 0, $cut);

        if (($h1 = stripos($intro, '</h1>')) !== false) {
            $intro = substr($intro, $h1 + 5);
        }

        $intro = str_ireplace(['<h2', '</h2>'], ['<h3', '</h3>'], $intro);
        $intro = preg_replace('#<img[^>]*>#i', '', $intro);
        $intro = preg_replace('~<svg[^>]*>.*?</svg>~is', '', $intro);
        $intro = preg_replace('~<a[^>]*href="#[^"]*"[^>]*>.*?</a>~is', '', $intro);

        return (string) $intro;
    }

    /** Recent public activity (Push, PRs, issues, releases). Cached. */
    public static function fetchEvents(string $user, int $count = 12): array
    {
        $count = max(1, min(30, $count));
        $key = 'mh_ghev1_'.md5($user.$count);
        if (($d = get_transient($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $r = wp_remote_get("https://api.github.com/users/{$user}/events/public?per_page=30", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                $item = self::formatEvent(is_array($j) ? $j : []);
                if ($item === null) {
                    continue;
                }
                $out[] = $item;
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        set_transient($key, $out, min(HOUR_IN_SECONDS, self::ttl()));

        return $out;
    }

    /** Contribution calendar: GraphQL when a token exists, else the public SVG page. */
    public static function fetchContributionCalendar(string $user): array
    {
        $key = 'mh_ghcal2_'.md5($user);
        if (($d = get_transient($key)) !== false) {
            return is_array($d) ? $d : ['total' => 0, 'weeks' => []];
        }

        $empty = ['total' => 0, 'weeks' => []];
        $data = github_token() !== '' ? self::calendarFromGraphql($user) : null;
        if ($data === null) {
            $data = self::calendarFromHtml($user);
        }
        if ($data === null) {
            $data = $empty;
        }
        set_transient($key, $data, min(6 * HOUR_IN_SECONDS, self::ttl()));

        return $data;
    }

    /** @return array{type: string, repo: string, url: string, text: string, when: string}|null */
    protected static function formatEvent(array $j): ?array
    {
        $type = (string) ($j['type'] ?? '');
        $repo = (string) ($j['repo']['name'] ?? '');
        $url = $repo !== '' ? 'https://github.com/'.$repo : '';
        $payload = is_array($j['payload'] ?? null) ? $j['payload'] : [];
        $when = (string) ($j['created_at'] ?? '');

        $pushCount = max(1, (int) ($payload['size'] ?? count((array) ($payload['commits'] ?? []))));
        $text = match ($type) {
            'PushEvent' => sprintf(
                'Pushed %s to %s',
                sprintf(_n('%s commit', '%s commits', $pushCount, 'sage'), (string) $pushCount),
                $repo
            ),
            'PullRequestEvent' => sprintf(
                '%s pull request %s in %s',
                ucfirst((string) ($payload['action'] ?? 'updated')),
                ! empty($payload['pull_request']['number']) ? '#'.(int) $payload['pull_request']['number'] : '',
                $repo
            ),
            'IssuesEvent' => sprintf(
                '%s issue %s in %s',
                ucfirst((string) ($payload['action'] ?? 'updated')),
                ! empty($payload['issue']['number']) ? '#'.(int) $payload['issue']['number'] : '',
                $repo
            ),
            'IssueCommentEvent' => sprintf('Commented on an issue in %s', $repo),
            'PullRequestReviewEvent' => sprintf('Reviewed a pull request in %s', $repo),
            'CreateEvent' => trim(sprintf(
                'Created %s %s in %s',
                (string) ($payload['ref_type'] ?? 'repository'),
                (string) ($payload['ref'] ?? ''),
                $repo
            )),
            'ReleaseEvent' => sprintf(
                'Published %s on %s',
                (string) ($payload['release']['tag_name'] ?? 'a release'),
                $repo
            ),
            'ForkEvent' => sprintf('Forked %s', $repo),
            'WatchEvent' => sprintf('Starred %s', $repo),
            'PublicEvent' => sprintf('Made %s public', $repo),
            default => null,
        };

        if ($text === null || $repo === '') {
            return null;
        }

        if ($type === 'PullRequestEvent' && ! empty($payload['pull_request']['html_url'])) {
            $url = (string) $payload['pull_request']['html_url'];
        } elseif ($type === 'IssuesEvent' && ! empty($payload['issue']['html_url'])) {
            $url = (string) $payload['issue']['html_url'];
        } elseif ($type === 'ReleaseEvent' && ! empty($payload['release']['html_url'])) {
            $url = (string) $payload['release']['html_url'];
        } elseif ($type === 'PushEvent' && ! empty($payload['ref'])) {
            $ref = preg_replace('#^refs/heads/#', '', (string) $payload['ref']);
            $url = 'https://github.com/'.$repo.'/commits/'.$ref;
        }

        return [
            'type' => $type,
            'repo' => $repo,
            'url' => $url,
            'text' => trim((string) preg_replace('/\s+/', ' ', $text)),
            'when' => $when,
        ];
    }

    /** @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>}|null */
    protected static function calendarFromGraphql(string $user): ?array
    {
        $query = <<<'GQL'
query ($login: String!) {
  user(login: $login) {
    contributionsCollection {
      contributionCalendar {
        totalContributions
        weeks {
          contributionDays {
            date
            contributionCount
          }
        }
      }
    }
  }
}
GQL;
        $res = wp_remote_post('https://api.github.com/graphql', [
            'timeout' => 15,
            'headers' => array_merge(github_headers(), ['Content-Type' => 'application/json']),
            'body' => wp_json_encode(['query' => $query, 'variables' => ['login' => $user]]),
        ]);
        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            return null;
        }
        $json = json_decode((string) wp_remote_retrieve_body($res), true);
        $cal = $json['data']['user']['contributionsCollection']['contributionCalendar'] ?? null;
        if (! is_array($cal)) {
            return null;
        }
        $weeks = [];
        foreach ((array) ($cal['weeks'] ?? []) as $week) {
            $days = [];
            foreach ((array) ($week['contributionDays'] ?? []) as $day) {
                $count = (int) ($day['contributionCount'] ?? 0);
                $days[] = [
                    'date' => (string) ($day['date'] ?? ''),
                    'count' => $count,
                    'level' => self::contributionLevel($count),
                ];
            }
            if ($days !== []) {
                $weeks[] = $days;
            }
        }

        return [
            'total' => (int) ($cal['totalContributions'] ?? 0),
            'weeks' => $weeks,
        ];
    }

    /** @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>}|null */
    protected static function calendarFromHtml(string $user): ?array
    {
        $res = wp_remote_get('https://github.com/users/'.rawurlencode($user).'/contributions', [
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'matthummel-theme/3 (+'.(function_exists('home_url') ? home_url('/') : 'https://matthummel.com').')',
                'Accept' => 'text/html',
            ],
        ]);
        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            return null;
        }
        $html = (string) wp_remote_retrieve_body($res);
        if (! preg_match_all('/data-date="(\d{4}-\d{2}-\d{2})"[^>]*data-level="(\d+)"|data-level="(\d+)"[^>]*data-date="(\d{4}-\d{2}-\d{2})"/', $html, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $days = [];
        foreach ($matches as $m) {
            $date = $m[1] !== '' ? $m[1] : ($m[4] ?? '');
            $level = $m[1] !== '' ? (int) $m[2] : (int) ($m[3] ?? 0);
            if ($date === '' || isset($days[$date])) {
                continue;
            }
            $days[$date] = [
                'date' => $date,
                'count' => $level,
                'level' => max(0, min(4, $level)),
            ];
        }
        if ($days === []) {
            return null;
        }
        ksort($days);
        $list = array_values($days);
        $weeks = [];
        $week = [];
        $first = new \DateTimeImmutable($list[0]['date']);
        $pad = (int) $first->format('w');
        for ($i = 0; $i < $pad; $i++) {
            $week[] = ['date' => '', 'count' => 0, 'level' => 0];
        }
        foreach ($list as $day) {
            $week[] = $day;
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }
        if ($week !== []) {
            while (count($week) < 7) {
                $week[] = ['date' => '', 'count' => 0, 'level' => 0];
            }
            $weeks[] = $week;
        }

        return [
            'total' => array_sum(array_column($list, 'count')),
            'weeks' => $weeks,
        ];
    }

    protected static function contributionLevel(int $count): int
    {
        return match (true) {
            $count <= 0 => 0,
            $count <= 2 => 1,
            $count <= 5 => 2,
            $count <= 9 => 3,
            default => 4,
        };
    }

    /** Render selected parts (desc, stats, intro) as HTML. */
    public static function render(string $owner, string $repo, array $show = ['stats', 'intro']): string
    {
        $d = self::fetch($owner, $repo);
        if (empty($d)) {
            return '';
        }

        $out = '<div class="mh-gh">';

        if (in_array('desc', $show, true) && ! empty($d['desc'])) {
            $out .= '<p class="lead">'.esc_html($d['desc']).'</p>';
        }

        if (in_array('stats', $show, true)) {
            $items = [];
            if (isset($d['stars'])) {
                $items[] = '<li><strong>'.number_format($d['stars']).'</strong><span>Stars</span></li>';
            }
            if (isset($d['forks'])) {
                $items[] = '<li><strong>'.number_format($d['forks']).'</strong><span>Forks</span></li>';
            }
            if (! empty($d['lang'])) {
                $items[] = '<li><strong>'.esc_html($d['lang']).'</strong><span>Language</span></li>';
            }
            if (! empty($d['license'])) {
                $items[] = '<li><strong>'.esc_html($d['license']).'</strong><span>License</span></li>';
            }
            if (! empty($d['release'])) {
                $items[] = '<li><strong>'.esc_html($d['release']).'</strong><span>Release</span></li>';
            }
            if ($items) {
                $out .= '<ul class="stat-grid">'.implode('', $items).'</ul>';
            }
        }

        if (in_array('intro', $show, true) && ! empty($d['intro'])) {
            $allowed = [
                'p' => [], 'a' => ['href' => [], 'rel' => [], 'title' => []], 'strong' => [], 'em' => [],
                'code' => [], 'pre' => [], 'ul' => [], 'ol' => [], 'li' => [], 'br' => [],
                'h3' => [], 'h4' => [], 'blockquote' => [],
            ];
            $out .= '<div class="readme-prose">'.wp_kses($d['intro'], $allowed).'</div>';
        }

        return $out.'</div>';
    }
}
