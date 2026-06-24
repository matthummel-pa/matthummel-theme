<?php

namespace App;

/**
 * Live, cached GitHub repo data for project case studies.
 * Mirrors the matthummel.com [mh_github] feature: repo metadata,
 * latest release, and a cleaned README intro — cached for 6 hours.
 */
class Github
{
    /** Fetch + cache repo data. */
    public static function fetch(string $owner, string $repo): array
    {
        $key = 'mh_gh_' . md5($owner . '/' . $repo);

        if (($data = get_transient($key)) !== false) {
            return $data;
        }

        $data = [];
        $jargs = ['timeout' => 12, 'headers' => [
            'User-Agent' => 'matthummel.com',
            'Accept' => 'application/vnd.github+json',
        ]];

        $r = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}", $jargs);
        if (! is_wp_error($r) && wp_remote_retrieve_response_code($r) === 200) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            $data['desc']    = $j['description'] ?? '';
            $data['stars']   = (int) ($j['stargazers_count'] ?? 0);
            $data['forks']   = (int) ($j['forks_count'] ?? 0);
            $data['lang']    = $j['language'] ?? '';
            $data['license'] = (isset($j['license']['spdx_id']) && $j['license']['spdx_id'] !== 'NOASSERTION')
                ? $j['license']['spdx_id'] : '';
            $data['url']     = $j['html_url'] ?? '';
        }

        $rel = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest", $jargs);
        if (! is_wp_error($rel) && wp_remote_retrieve_response_code($rel) === 200) {
            $jr = json_decode(wp_remote_retrieve_body($rel), true);
            $data['release'] = $jr['tag_name'] ?? '';
        }

        $rm = wp_remote_get("https://api.github.com/repos/{$owner}/{$repo}/readme", ['timeout' => 12, 'headers' => [
            'User-Agent' => 'matthummel.com',
            'Accept' => 'application/vnd.github.html',
        ]]);
        if (! is_wp_error($rm) && wp_remote_retrieve_response_code($rm) === 200) {
            $data['intro'] = self::readmeIntro(wp_remote_retrieve_body($rm));
        }

        set_transient($key, $data, 6 * HOUR_IN_SECONDS);

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
            $out .= '<p class="lead">' . esc_html($d['desc']) . '</p>';
        }

        if (in_array('stats', $show, true)) {
            $items = [];
            if (isset($d['stars']))    $items[] = '<li><strong>' . number_format($d['stars']) . '</strong><span>Stars</span></li>';
            if (isset($d['forks']))    $items[] = '<li><strong>' . number_format($d['forks']) . '</strong><span>Forks</span></li>';
            if (! empty($d['lang']))   $items[] = '<li><strong>' . esc_html($d['lang']) . '</strong><span>Language</span></li>';
            if (! empty($d['license']))$items[] = '<li><strong>' . esc_html($d['license']) . '</strong><span>License</span></li>';
            if (! empty($d['release']))$items[] = '<li><strong>' . esc_html($d['release']) . '</strong><span>Release</span></li>';
            if ($items) $out .= '<ul class="stat-grid">' . implode('', $items) . '</ul>';
        }

        if (in_array('intro', $show, true) && ! empty($d['intro'])) {
            $allowed = [
                'p' => [], 'a' => ['href' => [], 'rel' => [], 'title' => []], 'strong' => [], 'em' => [],
                'code' => [], 'pre' => [], 'ul' => [], 'ol' => [], 'li' => [], 'br' => [],
                'h3' => [], 'h4' => [], 'blockquote' => [],
            ];
            $out .= '<div class="readme-prose">' . wp_kses($d['intro'], $allowed) . '</div>';
        }

        return $out . '</div>';
    }
}
