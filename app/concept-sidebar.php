<?php

/**
 * Concept / product landing sidebar fields + GitHub sync.
 */

namespace App;

/**
 * Meta keys that power the ThemeForest-style concept aside.
 *
 * @return list<string>
 */
function mh_project_sidebar_meta_keys(): array
{
    return [
        '_mh_project_github',
        '_mh_project_version',
        '_mh_project_compatible',
        '_mh_project_files_included',
        '_mh_project_docs',
        '_mh_project_license',
        '_mh_project_stars',
        '_mh_project_release_tag',
        '_mh_project_release_url',
        '_mh_project_homepage',
        '_mh_project_topics',
        '_mh_project_languages',
        '_mh_project_last_updated',
        '_mh_project_support',
        '_mh_project_screenshots',
        '_mh_project_brand_tagline',
        '_mh_project_brand_palette',
    ];
}

/**
 * Parse owner/repo from a GitHub URL or owner/repo string.
 *
 * @return array{0: string, 1: string}|null
 */
function mh_project_parse_github_repo(string $raw): ?array
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    if (preg_match('#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$#', $raw, $m)) {
        return [$m[1], $m[2]];
    }

    if (preg_match('#github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)#i', $raw, $m)) {
        return [$m[1], preg_replace('#\.git$#', '', $m[2])];
    }

    return null;
}

/**
 * Resolve the GitHub repo URL for a project (dedicated key, then concept URL).
 */
function mh_project_github_url(int $post_id): string
{
    $github = trim((string) get_post_meta($post_id, '_mh_project_github', true));
    if ($github !== '') {
        $parsed = mh_project_parse_github_repo($github);
        if ($parsed !== null) {
            return 'https://github.com/'.$parsed[0].'/'.$parsed[1];
        }

        return esc_url_raw($github);
    }

    $concept = trim((string) get_post_meta($post_id, '_mh_project_concept', true));
    if ($concept !== '' && mh_project_parse_github_repo($concept) !== null) {
        $parsed = mh_project_parse_github_repo($concept);

        return 'https://github.com/'.$parsed[0].'/'.$parsed[1];
    }

    return '';
}

/**
 * Docs rows: Label|||URL per line ( ;; also splits flattened CLI values).
 *
 * @return list<array{0: string, 1: string}>
 */
function mh_project_docs_pairs(int $post_id): array
{
    $raw = trim((string) get_post_meta($post_id, '_mh_project_docs', true));
    if ($raw === '') {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $lines = array_values(array_filter(array_map('trim', $lines)));
    if (count($lines) === 1 && str_contains($lines[0], ' ;; ')) {
        $lines = array_values(array_filter(array_map('trim', explode(' ;; ', $lines[0]))));
    }

    $pairs = [];
    foreach ($lines as $line) {
        if (! str_contains($line, '|||')) {
            continue;
        }
        [$label, $url] = array_map('trim', explode('|||', $line, 2));
        if ($label === '' || $url === '') {
            continue;
        }
        $pairs[] = [$label, $url];
    }

    return $pairs;
}

/**
 * Screenshot rows: url|caption pairs (flattened pipe lists supported).
 *
 * @return list<array{0: string, 1: string}>
 */
function mh_project_screenshot_pairs(int $post_id): array
{
    $raw = trim((string) get_post_meta($post_id, '_mh_project_screenshots', true));
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));
    if (count($parts) === 1 && substr_count($parts[0], '|') >= 1) {
        $flat = array_values(array_filter(array_map('trim', explode('|', $parts[0]))));
        $pairs = [];
        for ($i = 0; $i + 1 < count($flat); $i += 2) {
            if (str_starts_with($flat[$i], 'http')) {
                $pairs[] = [$flat[$i], $flat[$i + 1]];
            }
        }

        return $pairs;
    }

    $pairs = [];
    foreach ($parts as $line) {
        if (str_contains($line, '|')) {
            [$url, $caption] = array_map('trim', explode('|', $line, 2));
        } else {
            $url = $line;
            $caption = '';
        }
        if ($url === '' || ! str_starts_with($url, 'http')) {
            continue;
        }
        $pairs[] = [$url, $caption];
    }

    return $pairs;
}

/**
 * Brand palette pairs: Name|#hex.
 *
 * @return list<array{0: string, 1: string}>
 */
function mh_project_brand_palette_pairs(int $post_id): array
{
    $raw = trim((string) get_post_meta($post_id, '_mh_project_brand_palette', true));
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));
    if (count($parts) === 1 && str_contains($parts[0], '|')) {
        $flat = array_values(array_filter(array_map('trim', explode('|', $parts[0]))));
        $pairs = [];
        for ($i = 0; $i + 1 < count($flat); $i += 2) {
            $pairs[] = [$flat[$i], $flat[$i + 1]];
        }

        return $pairs;
    }

    $pairs = [];
    foreach ($parts as $line) {
        if (! str_contains($line, '|')) {
            continue;
        }
        [$name, $hex] = array_map('trim', explode('|', $line, 2));
        if ($name === '' || $hex === '') {
            continue;
        }
        $pairs[] = [$name, $hex];
    }

    return $pairs;
}

/**
 * Sidebar payload for Blade.
 *
 * @return array{
 *   github: string,
 *   version: string,
 *   compatible: string,
 *   files_included: list<string>,
 *   docs: list<array{0: string, 1: string}>,
 *   license: string,
 *   stars: int,
 *   release_tag: string,
 *   release_url: string,
 *   homepage: string,
 *   topics: list<string>,
 *   languages: list<string>,
 *   last_updated: string,
 *   support: string,
 *   screenshots: list<array{0: string, 1: string}>,
 *   brand_tagline: string,
 *   brand_palette: list<array{0: string, 1: string}>,
 *   has_details: bool
 * }
 */
function mh_project_sidebar(int $post_id): array
{
    $files = mh_project_list_lines((string) get_post_meta($post_id, '_mh_project_files_included', true));
    $topicsRaw = trim((string) get_post_meta($post_id, '_mh_project_topics', true));
    $topics = $topicsRaw === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $topicsRaw) ?: [])));
    $langsRaw = trim((string) get_post_meta($post_id, '_mh_project_languages', true));
    $languages = $langsRaw === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $langsRaw) ?: [])));

    $payload = [
        'github' => mh_project_github_url($post_id),
        'version' => trim((string) get_post_meta($post_id, '_mh_project_version', true)),
        'compatible' => trim((string) get_post_meta($post_id, '_mh_project_compatible', true)),
        'files_included' => $files,
        'docs' => mh_project_docs_pairs($post_id),
        'license' => trim((string) get_post_meta($post_id, '_mh_project_license', true)),
        'stars' => max(0, (int) get_post_meta($post_id, '_mh_project_stars', true)),
        'release_tag' => trim((string) get_post_meta($post_id, '_mh_project_release_tag', true)),
        'release_url' => trim((string) get_post_meta($post_id, '_mh_project_release_url', true)),
        'homepage' => trim((string) get_post_meta($post_id, '_mh_project_homepage', true)),
        'topics' => $topics,
        'languages' => $languages,
        'last_updated' => trim((string) get_post_meta($post_id, '_mh_project_last_updated', true)),
        'support' => trim((string) get_post_meta($post_id, '_mh_project_support', true)),
        'screenshots' => mh_project_screenshot_pairs($post_id),
        'brand_tagline' => trim((string) get_post_meta($post_id, '_mh_project_brand_tagline', true)),
        'brand_palette' => mh_project_brand_palette_pairs($post_id),
        'has_details' => false,
    ];

    $payload['has_details'] = $payload['version'] !== ''
        || $payload['compatible'] !== ''
        || $payload['files_included'] !== []
        || $payload['docs'] !== []
        || $payload['license'] !== ''
        || $payload['github'] !== ''
        || $payload['release_tag'] !== ''
        || $payload['topics'] !== []
        || $payload['languages'] !== []
        || $payload['last_updated'] !== ''
        || $payload['support'] !== '';

    return $payload;
}

/**
 * Whether a release/tag looks like a real product version (not a deploy alias).
 */
function mh_project_is_semverish(string $tag): bool
{
    $tag = ltrim(trim($tag), 'vV');

    return (bool) preg_match('/^\d+(\.\d+){0,3}([.-][A-Za-z0-9.-]+)?$/', $tag);
}

/**
 * Fetch raw file contents from a GitHub repo (default branch).
 */
function mh_project_github_raw_file(string $owner, string $repo, string $path): string
{
    $url = 'https://raw.githubusercontent.com/'.$owner.'/'.$repo.'/HEAD/'.ltrim($path, '/');
    $res = wp_remote_get($url, ['timeout' => 12, 'headers' => github_headers()]);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
        return '';
    }

    return (string) wp_remote_retrieve_body($res);
}

/**
 * Parse WordPress theme/plugin headers from a file body.
 *
 * @return array<string, string>
 */
function mh_project_parse_wp_headers(string $body): array
{
    $keys = [
        'Version' => 'version',
        'Requires at least' => 'requires_wp',
        'Requires PHP' => 'requires_php',
        'Tested up to' => 'tested_up_to',
        'License' => 'license',
        'Theme Name' => 'theme_name',
        'Plugin Name' => 'plugin_name',
        'Text Domain' => 'text_domain',
    ];
    $out = [];
    foreach ($keys as $header => $field) {
        if (preg_match('/^[ \t\*]*'.preg_quote($header, '/').':[ \t]*(.+)$/mi', $body, $m)) {
            $out[$field] = trim($m[1]);
        }
    }

    return $out;
}

/**
 * Candidate paths for theme/plugin header files.
 *
 * @return list<string>
 */
function mh_project_github_header_paths(string $repo): array
{
    $slug = preg_replace('#^wp-#', '', $repo) ?: $repo;

    return array_values(array_unique([
        'style.css',
        $slug.'.php',
        $repo.'.php',
        'plugin.php',
        $slug.'/'.$slug.'.php',
    ]));
}

/**
 * Probe common docs files on GitHub and return Label|||URL rows.
 *
 * @return list<string>
 */
function mh_project_github_doc_rows(string $owner, string $repo, string $htmlUrl): array
{
    $candidates = [
        'Support' => ['SUPPORT.md', 'docs/SUPPORT.md', 'support.md'],
        'Brand kit' => ['BRAND.md', 'docs/BRAND.md'],
        'Buyer guide' => ['buyer-guide.html', 'docs/buyer-guide.html', 'SELLING.md'],
        'Readme' => ['README.md'],
        'Changelog' => ['CHANGELOG.md', 'changelog.md'],
    ];
    $rows = [];
    foreach ($candidates as $label => $paths) {
        foreach ($paths as $path) {
            $url = 'https://api.github.com/repos/'.$owner.'/'.$repo.'/contents/'.$path;
            $res = wp_remote_head($url, ['timeout' => 8, 'headers' => github_headers()]);
            $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
            if ($code === 200) {
                $rows[] = $label.'|||'.$htmlUrl.'/blob/HEAD/'.$path;
                break;
            }
        }
    }

    return $rows;
}

/**
 * Sync sidebar custom fields from a GitHub repository.
 *
 * @return array{ok: bool, message: string, updated: list<string>}
 */
function mh_project_sidebar_sync_from_github(int $post_id, bool $force = false): array
{
    if ($post_id <= 0 || get_post_type($post_id) !== mh_project_post_type()) {
        return ['ok' => false, 'message' => 'Invalid project.', 'updated' => []];
    }

    $raw = trim((string) get_post_meta($post_id, '_mh_project_github', true));
    if ($raw === '') {
        $raw = trim((string) get_post_meta($post_id, '_mh_project_concept', true));
    }
    $parsed = mh_project_parse_github_repo($raw);
    if ($parsed === null) {
        return [
            'ok' => false,
            'message' => 'Set a GitHub repo URL (or owner/repo) first.',
            'updated' => [],
        ];
    }

    [$owner, $repo] = $parsed;
    $meta = Github::fetchRepoMeta($owner, $repo);
    $combo = Github::fetch($owner, $repo);
    $langs = Github::fetchLanguages($owner, $repo);
    $releases = Github::fetchReleases($owner, $repo, 5);

    if ($meta === [] && $combo === []) {
        return ['ok' => false, 'message' => 'GitHub API returned no data for '.$owner.'/'.$repo.'.', 'updated' => []];
    }

    $htmlUrl = (string) ($meta['url'] ?? $combo['url'] ?? ('https://github.com/'.$owner.'/'.$repo));
    $updated = [];

    $set = static function (string $key, string $value) use ($post_id, $force, &$updated): void {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $existing = trim((string) get_post_meta($post_id, $key, true));
        if (! $force && $existing !== '') {
            return;
        }
        if ($existing === $value) {
            return;
        }
        update_post_meta($post_id, $key, $value);
        $updated[] = $key;
    };

    $set('_mh_project_github', $htmlUrl);

    $license = (string) ($combo['license'] ?? '');
    if ($license === '' && isset($meta['license'])) {
        $license = (string) $meta['license'];
    }
    // Prefer SPDX from combo; style.css may say GPL prose later.
    if ($license !== '' && $license !== 'NOASSERTION') {
        $set('_mh_project_license', $license);
    }

    if (isset($meta['stars']) || isset($combo['stars'])) {
        $stars = (string) (int) ($meta['stars'] ?? $combo['stars'] ?? 0);
        $set('_mh_project_stars', $stars);
    }

    $releaseTag = '';
    $releaseUrl = '';
    foreach ($releases as $rel) {
        $tag = (string) ($rel['tag'] ?? '');
        if ($tag === '') {
            continue;
        }
        if ($releaseTag === '') {
            $releaseTag = $tag;
            $releaseUrl = (string) ($rel['url'] ?? '');
        }
        if (mh_project_is_semverish($tag)) {
            $releaseTag = $tag;
            $releaseUrl = (string) ($rel['url'] ?? '');
            break;
        }
    }
    if ($releaseTag === '' && ! empty($combo['release'])) {
        $releaseTag = (string) $combo['release'];
    }
    $set('_mh_project_release_tag', $releaseTag);
    $set('_mh_project_release_url', $releaseUrl);

    if (mh_project_is_semverish($releaseTag)) {
        $set('_mh_project_version', ltrim($releaseTag, 'vV'));
    }

    $homepage = trim((string) ($meta['homepage'] ?? ''));
    if ($homepage !== '' && ! str_contains($homepage, 'SUPPORT.md')) {
        $set('_mh_project_homepage', $homepage);
        if ($force || trim((string) get_post_meta($post_id, '_mh_project_demo', true)) === '') {
            if (str_starts_with($homepage, 'http')) {
                update_post_meta($post_id, '_mh_project_demo', esc_url_raw($homepage));
                $updated[] = '_mh_project_demo';
            }
        }
    }

    $topics = is_array($meta['topics'] ?? null) ? $meta['topics'] : [];
    if ($topics !== []) {
        $set('_mh_project_topics', implode(', ', array_map('strval', $topics)));
    }

    if ($langs !== []) {
        $set('_mh_project_languages', implode(', ', array_slice($langs, 0, 8)));
        if ($force || trim((string) get_post_meta($post_id, '_mh_project_tech', true)) === '') {
            update_post_meta($post_id, '_mh_project_tech', implode(', ', array_slice($langs, 0, 6)));
            $updated[] = '_mh_project_tech';
        }
    }

    $pushed = (string) ($meta['pushed'] ?? '');
    if ($pushed !== '') {
        $ts = strtotime($pushed);
        if ($ts) {
            $set('_mh_project_last_updated', gmdate('Y-m-d', $ts));
        }
    }

    // Theme/plugin headers → version + compatible.
    $headers = [];
    foreach (mh_project_github_header_paths($repo) as $path) {
        $body = mh_project_github_raw_file($owner, $repo, $path);
        if ($body === '') {
            continue;
        }
        $headers = mh_project_parse_wp_headers($body);
        if ($headers !== []) {
            break;
        }
    }
    if ($headers !== []) {
        if (! empty($headers['version'])) {
            $set('_mh_project_version', $headers['version']);
        }
        $compatParts = [];
        if (! empty($headers['requires_wp'])) {
            $compatParts[] = 'WordPress '.$headers['requires_wp'].'+';
        }
        if (! empty($headers['requires_php'])) {
            $compatParts[] = 'PHP '.$headers['requires_php'].'+';
        }
        if ($compatParts !== []) {
            $set('_mh_project_compatible', implode(', ', $compatParts));
        }
        if (! empty($headers['license']) && strlen($headers['license']) < 40) {
            $set('_mh_project_license', $headers['license']);
        }
    }

    // Docs list from common repo files + live demo.
    $docRows = mh_project_github_doc_rows($owner, $repo, $htmlUrl);
    $demo = trim((string) get_post_meta($post_id, '_mh_project_demo', true));
    if ($demo !== '') {
        $docRows[] = 'Live demo|||'.$demo;
    }
    if ($docRows !== []) {
        $docsText = implode("\n", $docRows);
        $existingDocs = trim((string) get_post_meta($post_id, '_mh_project_docs', true));
        if ($force || $existingDocs === '') {
            update_post_meta($post_id, '_mh_project_docs', $docsText);
            $updated[] = '_mh_project_docs';
        }
        // Support URL = first Support row when empty.
        foreach ($docRows as $row) {
            if (str_starts_with($row, 'Support|||')) {
                $set('_mh_project_support', substr($row, strlen('Support|||')));
                break;
            }
        }
    }

    $desc = trim((string) ($meta['desc'] ?? $combo['desc'] ?? ''));
    if ($desc !== '' && ($force || trim((string) get_post_meta($post_id, '_mh_project_blurb', true)) === '')) {
        update_post_meta($post_id, '_mh_project_blurb', $desc);
        $updated[] = '_mh_project_blurb';
    }

    return [
        'ok' => true,
        'message' => $updated === []
            ? 'GitHub synced — fields already up to date (use force to overwrite).'
            : 'Updated '.count(array_unique($updated)).' field(s) from '.$owner.'/'.$repo.'.',
        'updated' => array_values(array_unique($updated)),
    ];
}

/**
 * Admin meta box: product sidebar fields + GitHub refresh.
 */
function mh_project_sidebar_admin_meta_box(\WP_Post $post): void
{
    wp_nonce_field('mh_project_sidebar_meta', 'mh_project_sidebar_meta_nonce');

    $github = (string) get_post_meta($post->ID, '_mh_project_github', true);
    if ($github === '') {
        $concept = (string) get_post_meta($post->ID, '_mh_project_concept', true);
        if (mh_project_parse_github_repo($concept) !== null) {
            $github = $concept;
        }
    }

    $syncUrl = wp_nonce_url(
        admin_url('admin-post.php?action=mh_sync_project_sidebar&post_id='.(int) $post->ID),
        'mh_sync_project_sidebar_'.(int) $post->ID
    );
    $forceUrl = wp_nonce_url(
        admin_url('admin-post.php?action=mh_sync_project_sidebar&post_id='.(int) $post->ID.'&force=1'),
        'mh_sync_project_sidebar_'.(int) $post->ID
    );

    if (! empty($_GET['mh_sidebar_sync'])) {
        $notice = sanitize_text_field(wp_unslash((string) ($_GET['mh_sidebar_msg'] ?? '')));
        $ok = ($_GET['mh_sidebar_sync'] === '1');
        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            $ok ? 'notice notice-success inline' : 'notice notice-error inline',
            esc_html($notice !== '' ? $notice : ($ok ? __('Synced from GitHub.', 'sage') : __('Sync failed.', 'sage')))
        );
    }

    echo '<p class="description">'.esc_html__('These fields drive the concept page sidebar (version, compatibility, docs, license). Generate them from the product’s GitHub repo, then edit as needed.', 'sage').'</p>';
    echo '<p style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">';
    printf(
        '<a class="button button-primary" href="%s">%s</a>',
        esc_url($syncUrl),
        esc_html__('Fill empty fields from GitHub', 'sage')
    );
    printf(
        '<a class="button" href="%s">%s</a>',
        esc_url($forceUrl),
        esc_html__('Overwrite all from GitHub', 'sage')
    );
    echo '</p>';

    echo '<table class="form-table" role="presentation"><tbody>';
    mh_project_sidebar_admin_field(__('GitHub repo', 'sage'), 'mh_project_github', $github, 'https://github.com/owner/repo or owner/repo', 'url');
    mh_project_sidebar_admin_field(__('Version', 'sage'), 'mh_project_version', (string) get_post_meta($post->ID, '_mh_project_version', true), '1.2.2');
    mh_project_sidebar_admin_field(__('Compatible with', 'sage'), 'mh_project_compatible', (string) get_post_meta($post->ID, '_mh_project_compatible', true), 'WordPress 6.6+, PHP 8.3+');
    mh_project_sidebar_admin_field(__('Files included', 'sage'), 'mh_project_files_included', (string) get_post_meta($post->ID, '_mh_project_files_included', true), __('One item per line', 'sage'), 'textarea');
    mh_project_sidebar_admin_field(__('Documentation', 'sage'), 'mh_project_docs', (string) get_post_meta($post->ID, '_mh_project_docs', true), "Brand kit|||https://…\nSupport|||https://…", 'textarea');
    mh_project_sidebar_admin_field(__('Support URL', 'sage'), 'mh_project_support', (string) get_post_meta($post->ID, '_mh_project_support', true), 'https://', 'url');
    mh_project_sidebar_admin_field(__('License', 'sage'), 'mh_project_license', (string) get_post_meta($post->ID, '_mh_project_license', true), 'GPL-2.0');
    mh_project_sidebar_admin_field(__('Stars', 'sage'), 'mh_project_stars', (string) get_post_meta($post->ID, '_mh_project_stars', true), '0');
    mh_project_sidebar_admin_field(__('Latest release tag', 'sage'), 'mh_project_release_tag', (string) get_post_meta($post->ID, '_mh_project_release_tag', true), 'v1.0.0');
    mh_project_sidebar_admin_field(__('Latest release URL', 'sage'), 'mh_project_release_url', (string) get_post_meta($post->ID, '_mh_project_release_url', true), 'https://', 'url');
    mh_project_sidebar_admin_field(__('Homepage', 'sage'), 'mh_project_homepage', (string) get_post_meta($post->ID, '_mh_project_homepage', true), 'https://', 'url');
    mh_project_sidebar_admin_field(__('Topics (comma separated)', 'sage'), 'mh_project_topics', (string) get_post_meta($post->ID, '_mh_project_topics', true), 'wordpress, theme');
    mh_project_sidebar_admin_field(__('Languages (comma separated)', 'sage'), 'mh_project_languages', (string) get_post_meta($post->ID, '_mh_project_languages', true), 'PHP, CSS');
    mh_project_sidebar_admin_field(__('Last updated', 'sage'), 'mh_project_last_updated', (string) get_post_meta($post->ID, '_mh_project_last_updated', true), '2026-08-31');
    mh_projrge-text" rows="%4$d" id="%1$s" name="%1$s" placeholder="%2$s">%3$s</textarea>',
            esc_attr($name),
            esc_attr($placeholder),
            esc_textarea($value),
            $rows
        );
    } else {
        printf(
            '<input class="large-text" type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s">',
            esc_attr($type === 'url' ? 'url' : 'text'),
            esc_attr($name),
            esc_attr($value),
            esc_attr($placeholder)
        );
    }
    echo '</td></tr>';
}

function mh_save_project_sidebar_meta(int $post_id): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! isset($_POST['mh_project_sidebar_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_project_sidebar_meta_nonce'])), 'mh_project_sidebar_meta')) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $github = sanitize_text_field(wp_unslash($_POST['mh_project_github'] ?? ''));
    if ($github !== '' && str_starts_with($github, 'http')) {
        $github = esc_url_raw($github);
    }
    update_post_meta($post_id, '_mh_project_github', $github);

    update_post_meta($post_id, '_mh_project_version', sanitize_text_field(wp_unslash($_POST['mh_project_version'] ?? '')));
    update_post_meta($post_id, '_mh_project_compatible', sanitize_text_field(wp_unslash($_POST['mh_project_compatible'] ?? '')));
    update_post_meta($post_id, '_mh_project_files_included', sanitize_textarea_field(wp_unslash($_POST['mh_project_files_included'] ?? '')));
    update_post_meta($post_id, '_mh_project_docs', sanitize_textarea_field(wp_unslash($_POST['mh_project_docs'] ?? '')));
    update_post_meta($post_id, '_mh_project_support', esc_url_raw(wp_unslash($_POST['mh_project_support'] ?? '')));
    update_post_meta($post_id, '_mh_project_license', sanitize_text_field(wp_unslash($_POST['mh_project_license'] ?? '')));
    update_post_meta($post_id, '_mh_project_stars', (string) max(0, (int) ($_POST['mh_project_stars'] ?? 0)));
    update_post_meta($post_id, '_mh_project_release_tag', sanitize_text_field(wp_unslash($_POST['mh_project_release_tag'] ?? '')));
    update_post_meta($post_id, '_mh_project_release_url', esc_url_raw(wp_unslash($_POST['mh_project_release_url'] ?? '')));
    update_post_meta($post_id, '_mh_project_homepage', esc_url_raw(wp_unslash($_POST['mh_project_homepage'] ?? '')));
    update_post_meta($post_id, '_mh_project_topics', sanitize_text_field(wp_unslash($_POST['mh_project_topics'] ?? '')));
    update_post_meta($post_id, '_mh_project_languages', sanitize_text_field(wp_unslash($_POST['mh_project_languages'] ?? '')));
    update_post_meta($post_id, '_mh_project_last_updated', sanitize_text_field(wp_unslash($_POST['mh_project_last_updated'] ?? '')));
    update_post_meta($post_id, '_mh_project_screenshots', sanitize_textarea_field(wp_unslash($_POST['mh_project_screenshots'] ?? '')));
    update_post_meta($post_id, '_mh_project_brand_tagline', sanitize_text_field(wp_unslash($_POST['mh_project_brand_tagline'] ?? '')));
    update_post_meta($post_id, '_mh_project_brand_palette', sanitize_textarea_field(wp_unslash($_POST['mh_project_brand_palette'] ?? '')));
}

function mh_admin_sync_project_sidebar(): void
{
    $post_id = (int) ($_GET['post_id'] ?? 0);
    if ($post_id <= 0 || ! current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('You cannot edit this project.', 'sage'));
    }
    check_admin_referer('mh_sync_project_sidebar_'.$post_id);

    $force = ! empty($_GET['force']);
    $result = mh_project_sidebar_sync_from_github($post_id, $force);

    $redirect = add_query_arg(
        [
            'post' => $post_id,
            'action' => 'edit',
            'mh_sidebar_sync' => $result['ok'] ? '1' : '0',
            'mh_sidebar_msg' => $result['message'],
        ],
        admin_url('post.php')
    );
    wp_safe_redirect($redirect);
    exit;
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_project_sidebar',
        __('Concept sidebar (ThemeForest)', 'sage'),
        __NAMESPACE__.'\\mh_project_sidebar_admin_meta_box',
        mh_project_post_type(),
        'normal',
        'default'
    );
});

add_action('save_post_'.mh_project_post_type(), function (int $post_id): void {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    mh_save_project_sidebar_meta($post_id);
});

add_action('admin_post_mh_sync_project_sidebar', __NAMESPACE__.'\\mh_admin_sync_project_sidebar');
