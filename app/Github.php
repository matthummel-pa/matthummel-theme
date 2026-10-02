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
    /** When true, cached reads are skipped so a refresh pulls fresh API data. */
    protected static bool $fresh = false;

    /** @var array<string, bool> Calls that failed (vs. returned nothing) during this request. */
    protected static array $failed = [];

    /**
     * Whether a named call failed at the API level this request (e.g. 'pinned').
     */
    public static function failed(string $call): bool
    {
        return ! empty(self::$failed[$call]);
    }

    /**
     * Skip transient reads (writes still happen) while a forced refresh runs.
     */
    public static function fresh(bool $on): void
    {
        self::$fresh = $on;
    }

    /**
     * Read a cached value unless a forced refresh is running.
     *
     * @return mixed False on a miss, like get_transient().
     */
    protected static function cached(string $key): mixed
    {
        return self::$fresh ? false : get_transient($key);
    }

    /**
     * Cache a result. Empty results (API errors, rate limits) get a short TTL
     * so one failed call does not blank a panel for hours.
     */
    protected static function store(string $key, mixed $data, ?int $ttl = null): void
    {
        $ttl ??= self::ttl();
        set_transient($key, $data, empty($data) ? min($ttl, 10 * MINUTE_IN_SECONDS) : $ttl);
    }

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
     */
    protected static function ttl(): int
    {
        return max(1, (int) (function_exists('get_theme_mod') ? get_theme_mod('mh_proj_cache_hours', 6) : 6)) * HOUR_IN_SECONDS;
    }

    /**
     * Fetch and cache a GitHub user or organisation profile.
     *
     * Includes REST `hireable` plus GraphQL profile status (emoji + message)
     * when the public status is set on GitHub.
     *
     * @param  string  $user  GitHub login (username or organisation slug).
     * @return array<string, mixed> Profile data, or empty array on failure.
     */
    public static function fetchUser(string $user): array
    {
        $key = 'mh_ghu3_'.md5($user);
        if (($d = self::cached($key)) !== false) {
            return $d;
        }
        $d = [];
        $r = wp_remote_get("https://api.github.com/users/{$user}", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $status = self::fetchUserStatus($user);
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
                'status_emoji' => $status['emoji'],
                'status_message' => $status['message'],
                'status_busy' => $status['busy'],
            ];
        }
        self::store($key, $d);

        return $d;
    }

    /**
     * Fetch the public GitHub profile status (emoji + short message) via GraphQL.
     *
     * Fails soft when GraphQL is unavailable or the user has no status set.
     *
     * @param  string  $user  GitHub login.
     * @return array{emoji: string, message: string, busy: bool}
     */
    public static function fetchUserStatus(string $user): array
    {
        $empty = ['emoji' => '', 'message' => '', 'busy' => false];
        $user = sanitize_user($user, true);
        if ($user === '') {
            return $empty;
        }

        $key = 'mh_ghus1_'.md5($user);
        if (($cached = self::cached($key)) !== false && is_array($cached)) {
            return array_merge($empty, $cached);
        }

        $query = <<<'GQL'
query ($login: String!) {
  user(login: $login) {
    status {
      message
      emoji
      emojiHTML
      indicatesLimitedAvailability
    }
  }
}
GQL;

        $headers = github_headers();
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        $res = wp_remote_post('https://api.github.com/graphql', [
            'timeout' => 12,
            'headers' => $headers,
            'body' => wp_json_encode([
                'query' => $query,
                'variables' => ['login' => $user],
            ]),
        ]);

        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            set_transient($key, $empty, self::ttl());

            return $empty;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($res), true);
        $status = is_array($payload) ? ($payload['data']['user']['status'] ?? null) : null;
        if (! is_array($status)) {
            set_transient($key, $empty, self::ttl());

            return $empty;
        }

        $emoji = trim(wp_strip_all_tags((string) ($status['emojiHTML'] ?? '')));
        if ($emoji === '') {
            $emoji = self::statusEmojiFromShortcode((string) ($status['emoji'] ?? ''));
        }

        $out = [
            'emoji' => $emoji,
            'message' => trim((string) ($status['message'] ?? '')),
            'busy' => ! empty($status['indicatesLimitedAvailability']),
        ];
        self::store($key, $out);

        return $out;
    }

    /**
     * Map a few common GitHub status shortcodes to Unicode when emojiHTML is missing.
     */
    protected static function statusEmojiFromShortcode(string $shortcode): string
    {
        $shortcode = trim($shortcode);
        if ($shortcode === '') {
            return '';
        }

        $map = [
            ':coffee:' => '☕',
            ':wave:' => '👋',
            ':sparkles:' => '✨',
            ':rocket:' => '🚀',
            ':zap:' => '⚡',
            ':palm_tree:' => '🌴',
            ':house:' => '🏠',
            ':computer:' => '💻',
            ':briefcase:' => '💼',
            ':dart:' => '🎯',
        ];

        return $map[$shortcode] ?? '';
    }

    /**
     * Fetch and cache recent public followers for a user.
     *
     * @param  string  $user  GitHub login.
     * @param  int  $count  Maximum followers to return (1–100).
     * @return list<array{login: string, name: string, avatar: string, url: string}>
     */
    public static function fetchFollowers(string $user, int $count = 24): array
    {
        $count = max(1, min(100, $count));
        $key = 'mh_ghfol1_'.md5($user.$count);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $r = wp_remote_get(
            'https://api.github.com/users/'.rawurlencode($user).'/followers?per_page='.$count,
            self::args()
        );
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                if (! is_array($j)) {
                    continue;
                }
                $login = sanitize_user((string) ($j['login'] ?? ''), true);
                if ($login === '') {
                    continue;
                }
                $out[] = [
                    'login' => $login,
                    'name' => trim((string) ($j['name'] ?? '')) ?: $login,
                    'avatar' => esc_url_raw((string) ($j['avatar_url'] ?? '')),
                    'url' => esc_url_raw((string) ($j['html_url'] ?? 'https://github.com/'.$login)),
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache recent stargazers for a repository.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @param  int  $count  Maximum stargazers to return (1–100).
     * @return list<array{login: string, name: string, avatar: string, url: string, repo: string}>
     */
    public static function fetchStargazers(string $owner, string $repo, int $count = 30): array
    {
        $count = max(1, min(100, $count));
        $repoName = $repo;
        $ownerEnc = rawurlencode($owner);
        $repoEnc = rawurlencode($repo);
        $key = 'mh_ghstar1_'.md5($ownerEnc.'/'.$repoEnc.$count);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $r = wp_remote_get(
            "https://api.github.com/repos/{$ownerEnc}/{$repoEnc}/stargazers?per_page={$count}",
            self::args('application/vnd.github.v3+json')
        );
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                if (! is_array($j)) {
                    continue;
                }
                $login = sanitize_user((string) ($j['login'] ?? ''), true);
                if ($login === '') {
                    continue;
                }
                $out[] = [
                    'login' => $login,
                    'name' => trim((string) ($j['name'] ?? '')) ?: $login,
                    'avatar' => esc_url_raw((string) ($j['avatar_url'] ?? '')),
                    'url' => esc_url_raw((string) ($j['html_url'] ?? 'https://github.com/'.$login)),
                    'repo' => $repoName,
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache repositories a user has starred.
     *
     * @param  string  $user  GitHub login.
     * @param  int  $count  Maximum starred repos to return (1–100).
     * @return list<array{name: string, full: string, desc: string, url: string, stars: int, lang: string, owner: string}>
     */
    public static function fetchStarred(string $user, int $count = 40): array
    {
        $count = max(1, min(100, $count));
        $key = 'mh_ghstarred1_'.md5($user.$count);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $r = wp_remote_get(
            'https://api.github.com/users/'.rawurlencode($user).'/starred?per_page='.$count.'&sort=created',
            self::args()
        );
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                if (! is_array($j)) {
                    continue;
                }
                $name = (string) ($j['name'] ?? '');
                $full = (string) ($j['full_name'] ?? '');
                if ($name === '' || $full === '') {
                    continue;
                }
                $out[] = [
                    'name' => $name,
                    'full' => $full,
                    'desc' => (string) ($j['description'] ?? ''),
                    'url' => esc_url_raw((string) ($j['html_url'] ?? 'https://github.com/'.$full)),
                    'stars' => (int) ($j['stargazers_count'] ?? 0),
                    'lang' => (string) ($j['language'] ?? ''),
                    'owner' => (string) ($j['owner']['login'] ?? ''),
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache repositories a user is watching (subscriptions).
     *
     * GitHub often returns 204 for public watching lists; fails soft to [].
     *
     * @param  string  $user  GitHub login.
     * @param  int  $count  Maximum watched repos to return (1–100).
     * @return list<array{name: string, full: string, desc: string, url: string, stars: int, lang: string, owner: string}>
     */
    public static function fetchWatching(string $user, int $count = 40): array
    {
        $count = max(1, min(100, $count));
        $key = 'mh_ghwatch1_'.md5($user.$count);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $r = wp_remote_get(
            'https://api.github.com/users/'.rawurlencode($user).'/subscriptions?per_page='.$count,
            self::args()
        );
        $code = is_wp_error($r) ? 0 : (int) wp_remote_retrieve_response_code($r);
        if ($code === 200) {
            foreach ((array) json_decode(wp_remote_retrieve_body($r), true) as $j) {
                if (! is_array($j)) {
                    continue;
                }
                $name = (string) ($j['name'] ?? '');
                $full = (string) ($j['full_name'] ?? '');
                if ($name === '' || $full === '') {
                    continue;
                }
                $out[] = [
                    'name' => $name,
                    'full' => $full,
                    'desc' => (string) ($j['description'] ?? ''),
                    'url' => esc_url_raw((string) ($j['html_url'] ?? 'https://github.com/'.$full)),
                    'stars' => (int) ($j['stargazers_count'] ?? 0),
                    'lang' => (string) ($j['language'] ?? ''),
                    'owner' => (string) ($j['owner']['login'] ?? ''),
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch every public, non-fork repository a user owns (up to 300), newest push first.
     *
     * One paginated call feeds star totals, language mix, and the recently pushed list.
     *
     * @param  string  $user  GitHub login.
     * @return list<array{name: string, full: string, desc: string, url: string, homepage: string, lang: string, stars: int, forks: int, topics: list<string>, pushed: string, archived: bool}>
     */
    public static function fetchOwnerRepos(string $user): array
    {
        $key = 'mh_ghown1_'.md5($user);
        if (($d = self::cached($key)) !== false && is_array($d)) {
            return $d;
        }

        $out = [];
        for ($page = 1; $page <= 3; $page++) {
            $r = wp_remote_get(
                'https://api.github.com/users/'.rawurlencode($user).'/repos?per_page=100&type=owner&sort=pushed&page='.$page,
                self::args()
            );
            if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) {
                break;
            }
            $batch = (array) json_decode(wp_remote_retrieve_body($r), true);
            foreach ($batch as $j) {
                if (! is_array($j) || ! empty($j['fork']) || ! empty($j['private'])) {
                    continue;
                }
                $name = (string) ($j['name'] ?? '');
                if ($name === '' || strcasecmp($name, $user) === 0 || mh_github_is_hidden_repo($name)) {
                    continue;
                }
                $topics = $j['topics'] ?? [];
                $out[] = [
                    'name' => $name,
                    'full' => (string) ($j['full_name'] ?? $user.'/'.$name),
                    'desc' => (string) ($j['description'] ?? ''),
                    'url' => esc_url_raw((string) ($j['html_url'] ?? 'https://github.com/'.$user.'/'.$name)),
                    'homepage' => (string) ($j['homepage'] ?? ''),
                    'lang' => (string) ($j['language'] ?? ''),
                    'stars' => (int) ($j['stargazers_count'] ?? 0),
                    'forks' => (int) ($j['forks_count'] ?? 0),
                    'topics' => is_array($topics) ? array_values(array_map('strval', $topics)) : [],
                    'pushed' => (string) ($j['pushed_at'] ?? ''),
                    'archived' => ! empty($j['archived']),
                ];
            }
            if (count($batch) < 100) {
                break;
            }
        }

        self::store($key, $out);

        return $out;
    }

    /**
     * Sum stargazer counts across a user's public, non-fork repositories.
     *
     * @param  string  $user  GitHub login.
     * @return array{total: int, repos: list<array{name: string, stars: int, url: string}>}
     */
    public static function fetchStarTotals(string $user): array
    {
        $total = 0;
        $repos = [];
        foreach (self::fetchOwnerRepos($user) as $repo) {
            $total += $repo['stars'];
            if ($repo['stars'] > 0) {
                $repos[] = ['name' => $repo['name'], 'stars' => $repo['stars'], 'url' => $repo['url']];
            }
        }
        usort($repos, static fn (array $a, array $b): int => $b['stars'] <=> $a['stars']);

        return ['total' => $total, 'repos' => $repos];
    }

    /**
     * Fetch and cache a user's own public repositories, excluding forks.
     *
     * @param  string  $user  GitHub login.
     * @param  int  $count  Maximum number of repositories to return (1–30).
     * @param  string  $sort  Sort field: updated|pushed|full_name|created.
     * @return list<array<string, mixed>>
     */
    public static function fetchRepos(string $user, int $count = 6, string $sort = 'updated'): array
    {
        $count = max(1, min(30, $count));
        $sort = in_array($sort, ['updated', 'pushed', 'full_name', 'created'], true) ? $sort : 'updated';
        $key = 'mh_ghr5_'.md5($user.$sort.$count);
        if (($d = self::cached($key)) !== false) {
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
                if (mh_github_is_hidden_repo($name)) {
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
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache the programming-language breakdown for a repository, largest first.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @return list<string> Language names sorted by byte count descending.
     */
    public static function fetchLanguages(string $owner, string $repo): array
    {
        $owner = rawurlencode($owner);
        $repo = rawurlencode($repo);
        $key = 'mh_ghlang_'.md5($owner.'/'.$repo);
        if (($d = self::cached($key)) !== false) {
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
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache extended single-repository metadata for featured project cards.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @return array<string, mixed> Repository metadata, or empty array on failure.
     */
    public static function fetchRepoMeta(string $owner, string $repo): array
    {
        $key = 'mh_ghmeta3_'.md5($owner.'/'.$repo);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }
        $d = [];
        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}", self::args());
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $topics = $j['topics'] ?? [];
            $license = (string) ($j['license']['spdx_id'] ?? '');
            $d = [
                'desc' => (string) ($j['description'] ?? ''),
                'stars' => (int) ($j['stargazers_count'] ?? 0),
                'forks' => (int) ($j['forks_count'] ?? 0),
                'watchers' => (int) ($j['subscribers_count'] ?? $j['watchers_count'] ?? 0),
                'issues' => (int) ($j['open_issues_count'] ?? 0),
                'lang' => (string) ($j['language'] ?? ''),
                'license' => ($license !== '' && $license !== 'NOASSERTION') ? $license : '',
                'url' => (string) ($j['html_url'] ?? ''),
                'homepage' => (string) ($j['homepage'] ?? ''),
                'topics' => is_array($topics) ? $topics : [],
                'pushed' => (string) ($j['pushed_at'] ?? ''),
            ];
        }
        self::store($key, $d);

        return $d;
    }

    /**
     * Fetch and cache recent GitHub releases for a repository.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @param  int  $count  Maximum number of releases to return (1–20).
     * @return list<array<string, mixed>>
     */
    public static function fetchReleases(string $owner, string $repo, int $count = 5): array
    {
        $count = max(1, min(20, $count));
        $key = 'mh_ghrel_'.md5("{$owner}/{$repo}/{$count}");
        if (($d = self::cached($key)) !== false) {
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
        self::store($key, $out);

        return $out;
    }

    /**
     * Fetch and cache combined repository data: stats, latest release, and README intro.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @return array<string, mixed> Combined data, or empty array on API failure.
     */
    public static function fetch(string $owner, string $repo): array
    {
        $key = 'mh_gh_'.md5($owner.'/'.$repo);

        if (($data = self::cached($key)) !== false) {
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

        self::store($key, $data);

        return $data;
    }

    /**
     * Extract a clean README intro: content up to the second h2, with headings demoted and badges stripped.
     *
     * @param  string  $body  Raw GitHub-rendered README HTML.
     * @return string Sanitised HTML snippet suitable for wp_kses output.
     */
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

    /**
     * Fetch and cache recent public GitHub events for a user.
     *
     * @param  string  $user  GitHub login.
     * @param  int  $count  Maximum number of formatted events to return (1–30).
     * @return list<array{type: string, repo: string, url: string, text: string, when: string}>
     */
    public static function fetchEvents(string $user, int $count = 12): array
    {
        $count = max(1, min(100, $count));
        $key = 'mh_ghev3_'.md5($user.$count);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $out = [];
        $pageSize = min(100, max($count, 30));
        $r = wp_remote_get("https://api.github.com/users/{$user}/events/public?per_page={$pageSize}", self::args());
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
        self::store($key, $out, min(HOUR_IN_SECONDS, self::ttl()));

        return $out;
    }

    /**
     * Fetch and cache the contribution calendar for a user (GraphQL when token is set, HTML fallback).
     *
     * @param  string  $user  GitHub login.
     * @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>}
     */
    public static function fetchContributionCalendar(string $user): array
    {
        $key = 'mh_ghcal3_'.md5($user);
        $empty = ['total' => 0, 'weeks' => [], 'breakdown' => []];
        if (($d = self::cached($key)) !== false) {
            return is_array($d) && isset($d['weeks']) ? $d : $empty;
        }

        $data = github_token() !== '' ? self::calendarFromGraphql($user) : null;
        if ($data === null) {
            $data = self::calendarFromHtml($user);
        }
        if ($data === null) {
            $data = $empty;
        }
        $ttl = min(6 * HOUR_IN_SECONDS, self::ttl());
        set_transient($key, $data, $data['weeks'] === [] ? 10 * MINUTE_IN_SECONDS : $ttl);

        return $data;
    }

    /**
     * Format a single raw GitHub event payload into a display-ready array.
     *
     * @param  array<string, mixed>  $j  Raw event object decoded from the GitHub API.
     * @return array{type: string, repo: string, url: string, label: string, text: string, when: string}|null
     *                                                                                                        Null when the event type is unsupported or the repo is missing.
     */
    protected static function formatEvent(array $j): ?array
    {
        $type = (string) ($j['type'] ?? '');
        $repo = (string) ($j['repo']['name'] ?? '');
        $url = $repo !== '' ? 'https://github.com/'.$repo : '';
        $payload = is_array($j['payload'] ?? null) ? $j['payload'] : [];
        $when = (string) ($j['created_at'] ?? '');

        // GitHub trimmed Events API payloads in 2025: pushes carry before/head
        // (no commit list) and pull requests carry a number but no html_url.
        $pushCount = (int) ($payload['size'] ?? count((array) ($payload['commits'] ?? [])));
        $branch = (string) preg_replace('#^refs/heads/#', '', (string) ($payload['ref'] ?? ''));
        $prNumber = (int) ($payload['number'] ?? $payload['pull_request']['number'] ?? 0);
        $prAction = (string) ($payload['action'] ?? 'updated');
        if ($prAction === 'closed' && ! empty($payload['pull_request']['merged'])) {
            $prAction = 'merged';
        }

        // Branch create/delete events are agent noise next to the PRs they open.
        if (in_array($type, ['CreateEvent', 'DeleteEvent'], true) && ($payload['ref_type'] ?? '') === 'branch') {
            return null;
        }

        $label = match ($type) {
            'PushEvent' => $pushCount > 0
                ? sprintf(
                    'Pushed %s to %s',
                    /* translators: %s: number of commits. */
                    sprintf(_n('%s commit', '%s commits', $pushCount, 'sage'), (string) $pushCount),
                    $branch !== '' ? $branch : 'a branch'
                )
                : sprintf('Pushed to %s', $branch !== '' ? $branch : 'a branch'),
            'PullRequestEvent' => sprintf('%s pull request %s', ucfirst($prAction), $prNumber > 0 ? '#'.$prNumber : ''),
            'IssuesEvent' => sprintf(
                '%s issue %s',
                ucfirst((string) ($payload['action'] ?? 'updated')),
                ! empty($payload['issue']['number']) ? '#'.(int) $payload['issue']['number'] : ''
            ),
            'IssueCommentEvent' => 'Commented on an issue',
            'PullRequestReviewEvent' => 'Reviewed a pull request',
            'CreateEvent' => trim(sprintf(
                'Created %s %s',
                (string) ($payload['ref_type'] ?? 'repository'),
                ($payload['ref_type'] ?? '') === 'repository' ? '' : (string) ($payload['ref'] ?? '')
            )),
            'ReleaseEvent' => sprintf('Published %s', (string) ($payload['release']['tag_name'] ?? 'a release')),
            'ForkEvent' => 'Forked',
            'WatchEvent' => 'Starred',
            'PublicEvent' => 'Made public',
            default => null,
        };

        if ($label === null || $repo === '') {
            return null;
        }

        if ($type === 'PullRequestEvent') {
            $url = ! empty($payload['pull_request']['html_url'])
                ? (string) $payload['pull_request']['html_url']
                : ($prNumber > 0 ? $url.'/pull/'.$prNumber : $url);
        } elseif ($type === 'IssuesEvent' && ! empty($payload['issue']['html_url'])) {
            $url = (string) $payload['issue']['html_url'];
        } elseif ($type === 'ReleaseEvent' && ! empty($payload['release']['html_url'])) {
            $url = (string) $payload['release']['html_url'];
        } elseif ($type === 'PushEvent') {
            $before = (string) ($payload['before'] ?? '');
            $head = (string) ($payload['head'] ?? '');
            if ($before !== '' && $head !== '' && ! preg_match('/^0+$/', $before)) {
                $url .= '/compare/'.substr($before, 0, 12).'...'.substr($head, 0, 12);
            } elseif ($branch !== '') {
                $url .= '/commits/'.$branch;
            }
        }

        $label = trim((string) preg_replace('/\s+/', ' ', $label));
        $short = (string) preg_replace('#^[^/]+/#', '', $repo);

        return [
            'type' => $type,
            'repo' => $repo,
            'url' => $url,
            'label' => $label,
            'text' => in_array($type, ['ForkEvent', 'WatchEvent', 'PublicEvent'], true)
                ? $label.' '.$short
                : $label.' in '.$short,
            'when' => $when,
        ];
    }

    /**
     * Fetch the contribution calendar via the GitHub GraphQL API (requires a token).
     *
     * @param  string  $user  GitHub login.
     * @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>}|null
     *                                                                                                            Null on API error or when the response is malformed.
     */
    protected static function calendarFromGraphql(string $user): ?array
    {
        $query = <<<'GQL'
query ($login: String!) {
  user(login: $login) {
    contributionsCollection {
      totalCommitContributions
      totalPullRequestContributions
      totalPullRequestReviewContributions
      totalIssueContributions
      totalRepositoriesWithContributedCommits
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
        $coll = $json['data']['user']['contributionsCollection'] ?? [];
        $cal = $coll['contributionCalendar'] ?? null;
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
            'breakdown' => [
                'commits' => (int) ($coll['totalCommitContributions'] ?? 0),
                'prs' => (int) ($coll['totalPullRequestContributions'] ?? 0),
                'reviews' => (int) ($coll['totalPullRequestReviewContributions'] ?? 0),
                'issues' => (int) ($coll['totalIssueContributions'] ?? 0),
                'repos' => (int) ($coll['totalRepositoriesWithContributedCommits'] ?? 0),
            ],
        ];
    }

    /**
     * Fetch the contribution calendar by scraping the public GitHub contributions HTML page.
     *
     * @param  string  $user  GitHub login.
     * @return array{total: int, weeks: array<int, array<int, array{date: string, count: int, level: int}>>}|null
     *                                                                                                            Null when the page is unavailable or no day data is found.
     */
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

        // Day cells only carry a 0-4 shade; the real count lives in a <tool-tip for="cell id">.
        $tips = [];
        if (preg_match_all('#<tool-tip[^>]*\bfor="([^"]+)"[^>]*>\s*([\d,]+|No) contributions?#i', $html, $tm, PREG_SET_ORDER)) {
            foreach ($tm as $t) {
                $tips[$t[1]] = strcasecmp($t[2], 'No') === 0 ? 0 : (int) str_replace(',', '', $t[2]);
            }
        }
        $ids = [];
        if (preg_match_all('#<td\b[^>]*>#i', $html, $cells)) {
            foreach ($cells[0] as $cell) {
                if (preg_match('/data-date="(\d{4}-\d{2}-\d{2})"/', $cell, $dm) && preg_match('/\bid="([^"]+)"/', $cell, $im)) {
                    $ids[$dm[1]] = $im[1];
                }
            }
        }

        $days = [];
        foreach ($matches as $m) {
            $date = $m[1] !== '' ? $m[1] : ($m[4] ?? '');
            $level = $m[1] !== '' ? (int) $m[2] : (int) ($m[3] ?? 0);
            if ($date === '' || isset($days[$date])) {
                continue;
            }
            $count = isset($ids[$date], $tips[$ids[$date]]) ? $tips[$ids[$date]] : null;
            $days[$date] = [
                'date' => $date,
                'count' => $count ?? $level,
                'level' => $count !== null ? self::contributionLevel($count) : max(0, min(4, $level)),
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

        $total = array_sum(array_column($list, 'count'));
        if (preg_match('#([\d,]+)\s+contributions?\s+in the last year#i', $html, $tm)) {
            $total = (int) str_replace(',', '', $tm[1]);
        }

        return [
            'total' => $total,
            'weeks' => $weeks,
            'breakdown' => [],
        ];
    }

    /**
     * Map a raw contribution count to a heat-map level (0–4).
     *
     * @param  int  $count  Number of contributions on a given day.
     * @return int Level from 0 (none) to 4 (highest activity).
     */
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

    /**
     * Render selected repository sections (desc, stats, intro) as an HTML string.
     *
     * @param  string  $owner  Repository owner login.
     * @param  string  $repo  Repository name.
     * @param  list<string>  $show  Sections to include: desc, stats, intro.
     * @return string Escaped HTML, or empty string when no data is available.
     */
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

    /**
     * Fetch up to 6 pinned repositories for a GitHub user via GraphQL.
     * Returns the same shape as mh_code_page_repos() entries so repo-card works unchanged.
     *
     * @param  string  $user  GitHub login.
     * @return list<array<string, mixed>>
     */
    public static function fetchPinnedRepos(string $user): array
    {
        $key = 'mh_ghpinned_v2_'.md5($user);
        if (($d = self::cached($key)) !== false) {
            return is_array($d) ? $d : [];
        }

        $empty = [];

        $token = github_token();
        if ($token === '') {
            set_transient($key, $empty, self::ttl());

            return $empty;
        }

        $query = <<<'GQL'
query($login: String!) {
  user(login: $login) {
    pinnedItems(first: 6, types: REPOSITORY) {
      nodes {
        ... on Repository {
          name
          description
          url
          stargazerCount
          forkCount
          isArchived
          primaryLanguage { name color }
          licenseInfo { spdxId }
          repositoryTopics(first: 8) {
            nodes { topic { name } }
          }
          latestRelease { tagName publishedAt url }
          defaultBranchRef {
            target { ... on Commit { history { totalCount } } }
          }
          pushedAt
          homepageUrl
        }
      }
    }
  }
}
GQL;

        $headers = github_headers();
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        $res = wp_remote_post('https://api.github.com/graphql', [
            'timeout' => 12,
            'headers' => $headers,
            'body' => wp_json_encode([
                'query' => $query,
                'variables' => ['login' => $user],
            ]),
        ]);

        $payload = is_wp_error($res) ? null : json_decode((string) wp_remote_retrieve_body($res), true);
        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200 || ! empty($payload['errors']) || ! isset($payload['data']['user'])) {
            self::$failed['pinned'] = true;
            set_transient($key, $empty, 10 * MINUTE_IN_SECONDS);

            return $empty;
        }

        $nodes = $payload['data']['user']['pinnedItems']['nodes'] ?? [];

        $out = [];
        foreach ((array) $nodes as $node) {
            if (! is_array($node) || empty($node['name'])) {
                continue;
            }

            $topics = [];
            foreach ((array) ($node['repositoryTopics']['nodes'] ?? []) as $t) {
                $name = (string) ($t['topic']['name'] ?? '');
                if ($name !== '') {
                    $topics[] = $name;
                }
            }

            $lang = (string) ($node['primaryLanguage']['name'] ?? '');
            $license = (string) ($node['licenseInfo']['spdxId'] ?? '');

            $out[] = [
                'name' => (string) $node['name'],
                'title' => \App\mh_title_label((string) $node['name']),
                'desc' => (string) ($node['description'] ?? ''),
                'url' => (string) ($node['url'] ?? ''),
                'demo' => (string) ($node['homepageUrl'] ?? ''),
                'lang' => $lang,
                'lang_color' => (string) ($node['primaryLanguage']['color'] ?? ''),
                'stars' => (int) ($node['stargazerCount'] ?? 0),
                'forks' => (int) ($node['forkCount'] ?? 0),
                'pushed' => (string) ($node['pushedAt'] ?? ''),
                'tags' => $topics,
                'archived' => ! empty($node['isArchived']),
                'license' => ($license !== '' && $license !== 'NOASSERTION') ? $license : '',
                'commits' => (int) ($node['defaultBranchRef']['target']['history']['totalCount'] ?? 0),
                'release' => (string) ($node['latestRelease']['tagName'] ?? ''),
                'release_url' => (string) ($node['latestRelease']['url'] ?? ''),
                'release_date' => (string) ($node['latestRelease']['publishedAt'] ?? ''),
            ];
        }

        self::store($key, $out);

        return $out;
    }
}
