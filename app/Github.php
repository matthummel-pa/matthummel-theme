<?php

namespace App;

/**
 * Live, cached GitHub repo data for project case studies.
 * Mirrors the matthummel.com [mh_github] feature: repo metadata,
 * latest release, and a cleaned README intro — cached for 6 hours.
 */

/** Optional GitHub token: wp-config constant, then Customizer / updater setting. */
function github_token(): string
{
    if (defined('MH_GITHUB_TOKEN') && is_string(MH_GITHUB_TOKEN) && MH_GITHUB_TOKEN !== '') {
        return trim(MH_GITHUB_TOKEN);
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_gh_token', '')) : '';

    return (string) apply_filters('mh/github_token', $mod);
}

/** Request headers for the GitHub API. */
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

/** GET a GitHub API URL; returns decoded data or null. */
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
    /** Shared request args (UA, Accept, optional auth token). */
    protected static function args(string $accept = 'application/vnd.github+json'): array
    {
        $h = github_headers();
        $h['Accept'] = $accept;

        return ['timeout' => 12, 'headers' => $h];
    }

    /** Cache TTL in seconds (from the Projects setting). */
    protected static function ttl(): int
    {
        return max(1, (int) (function_exists('get_theme_mod') ? get_theme_mod('mh_proj_cache_hours', 6) : 6)) * HOUR_IN_SECONDS;
    }

    /** Fetch + cache a user/org profile. */
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

    /** Fetch + cache a user's repos (sorted, forks excluded). */
    public static function fetchRepos(string $user, int $count = 6, string $sort = 'updated'): array
    {
        $count = max(1, min(30, $count));
        $sort = in_array($sort, ['updated', 'pushed', 'full_name', 'created'], true) ? $sort : 'updated';
        $key = 'mh_ghr3_'.md5($user.$sort.$count);
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
                ];
                if (count($out) >= $count) {
                    break;
                }
            }
        }
        set_transient($key, $out, self::ttl());

        return $out;
    }

    /** Language names for a repo, largest first. Cached. */
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

    /** One-repo extras when the list payload is thin (featured cards). */
    public static function fetchRepoMeta(string $owner, string $repo): array
    {
        $key = 'mh_ghmeta_'.md5($owner.'/'.$repo);
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
                'lang' => (string) ($j['language'] ?? ''),
                'url' => (string) ($j['html_url'] ?? ''),
                'homepage' => (string) ($j['homepage'] ?? ''),
                'topics' => is_array($topics) ? $topics : [],
            ];
        }
        set_transient($key, $d, self::ttl());

        return $d;
    }

    /** Fetch + cache recent releases for a repo. */
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

    /** Fetch + cache repo data. */
    public static function fetch(string $owner, string $repo): array
    {
        $key = 'mh_gh_'.md5($owner.'/'.$repo);

        if (($data = get_transient($key)) !== false) {
            return $data;
        }

        $data = [];
        $jargs = ['timeout' => 12, 'headers' => github_headers()];

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

        $ttl = max(1, (int) (function_exists('get_theme_mod') ? get_theme_mod('mh_proj_cache_hours', 6) : 6));
        set_transient($key, $data, $ttl * HOUR_IN_SECONDS);

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
