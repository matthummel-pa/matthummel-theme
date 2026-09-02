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

/** Rewrite leftover studio-brand phrasing in visitor-facing strings. */
function mh_visitor_brand_text(string $text): string
{
    if ($text === '') {
        return $text;
    }

    return str_replace(
        [
            'Ridges & Valleys Studio',
            'Ridges &amp; Valleys Studio',
            'Ridges & Valleys',
            'Ridges &amp; Valleys',
            'R&amp;V Studio',
            'R&V Studio',
            'R&amp;V',
            'R&V',
        ],
        'Matt Hummel',
        $text
    );
}

/** Website listed on the GitHub profile, with this site as the fallback. */
function mh_github_blog_url(?array $gh = null): string
{
    $gh = $gh ?? Github::fetchUser(mh_github_login());
    $blog = trim((string) ($gh['blog'] ?? ''));
    if ($blog === '') {
        return home_url('/');
    }

    $url = preg_match('#^https?://#i', $blog) ? $blog : 'https://'.$blog;
    if (str_contains(strtolower($url), 'ridgesandvalleys')) {
        return home_url('/');
    }

    return $url;
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
            'name' => 'pressroot',
            'desc' => 'Sage 11 WordPress theme framework: Customizer tools, GitHub project pages, and a deep options system.',
            'url' => 'https://github.com/matthummel-pa/pressroot',
            'tags' => ['WordPress', 'Sage', 'PHP'],
        ],
        [
            'name' => 'matthummel-theme',
            'desc' => 'Sage 11 WordPress theme for matthummel.com. Blade templates, Tailwind, and pages shops can edit.',
            'url' => 'https://github.com/matthummel-pa/matthummel-theme',
            'tags' => ['WordPress', 'Sage', 'Tailwind'],
        ],
        [
            'name' => 'tocflow',
            'desc' => 'WordPress plugin that builds a table of contents from heading blocks. PHP, Gutenberg, and a small public API.',
            'url' => 'https://github.com/matthummel-pa/tocflow',
            'tags' => ['WordPress', 'Gutenberg', 'PHP'],
        ],
        [
            'name' => 'ridgesandvalleys',
            'desc' => 'Sage 11 WordPress theme for the Ridges & Valleys studio site — Blade, Vite, and the same stack as this portfolio.',
            'url' => 'https://github.com/matthummel-pa/ridgesandvalleys',
            'tags' => ['WordPress', 'Sage', 'Vite'],
        ],
        [
            'name' => 'keepary',
            'desc' => 'Private family app: authentication, invites, and posts. React on the front end, Supabase for data and sign-in.',
            'url' => 'https://github.com/matthummel-pa/keepary',
            'tags' => ['React', 'Supabase', 'TypeScript'],
        ],
    ];
}

/**
 * Repo names to keep off featured and recent lists (tutorial forks, placeholders).
 *
 * @return list<string>
 */
function mh_github_hidden_repo_names(): array
{
    return [
        'freecodecamp',
        'repo_owner',
    ];
}

function mh_github_is_hidden_repo(string $name): bool
{
    $n = strtolower(trim($name));
    if ($n === '') {
        return true;
    }
    foreach (mh_github_hidden_repo_names() as $hidden) {
        if ($n === $hidden || str_contains($n, $hidden)) {
            return true;
        }
    }

    return false;
}

/** Years of in-house / employer web work (higher-ed marketing start ~2009). */
function mh_years_in_house(): int
{
    return max(17, (int) date('Y') - 2009);
}

/**
 * One sentence for adjacent (non-WordPress) work.
 *
 * Used on the recruiter glance, About, Hire, and the home FAQ. WordPress stays
 * the specialty; APIs, React, Power Platform, and deploys are in range — not a
 * second specialty list.
 */
function mh_adjacent_range_copy(): string
{
    return sprintf(
        /* translators: %d: years of in-house web work */
        __('WordPress is the specialty. Adjacent work — APIs, React, Power Platform, deploys — is in range because I am a self-taught problem solver with %d years in-house.', 'sage'),
        mh_years_in_house()
    );
}

/**
 * Recruiter "at a glance" facts for the homepage (scannable in a few seconds).
 *
 * @return array{
 *   role: string,
 *   stack: string,
 *   timezone: string,
 *   location: string,
 *   experience: string,
 *   availability: string,
 *   note: string,
 *   employers: string,
 *   power: string,
 *   range: string,
 *   nda: string,
 *   links: list<array{label: string, href: string, icon: string, external: bool}>
 * }
 */
function mh_recruiter_glance(): array
{
    $gh = Github::fetchUser(mh_github_login());
    $ghYear = trim((string) ($gh['created'] ?? ''));
    if ($ghYear === '') {
        $ghYear = '2025';
    }
    $hireUrl = home_url('/hire/');

    return [
        'role' => field('glance_role', __('WordPress / full-stack PHP', 'sage')),
        'stack' => field('glance_stack', __('Sage 11, Blade, Tailwind, Vite, PHP 8.3, Gutenberg', 'sage')),
        'timezone' => field('glance_tz', __('America/New_York (ET)', 'sage')),
        'location' => field('glance_location', __('Gettysburg, PA · remote OK', 'sage')),
        'experience' => field(
            'glance_experience',
            sprintf(
                /* translators: 1: years of in-house work, 2: public GitHub start year */
                __('%1$d years in-house web · public Sage/WordPress since %2$s', 'sage'),
                mh_years_in_house(),
                $ghYear
            )
        ),
        'availability' => field('glance_avail', __('Full-time, contract, freelance, agency overflow', 'sage')),
        'note' => field(
            'glance_note',
            __('Most production work lived inside employers, so I am now publishing Sage/WordPress work, plugins, and spec builds on GitHub.', 'sage')
        ),
        'employers' => field_html(
            'glance_employers',
            sprintf(
                /* translators: %s: hire page URL */
                __('Employers on the record: <a href="%s">Saliense, All Native Group, and Knowledge Capital Associates (USMC)</a>.', 'sage'),
                esc_url($hireUrl)
            )
        ),
        'power' => field_html(
            'glance_power',
            sprintf(
                /* translators: %s: hire page URL */
                __('PowerApps, Power Automate, and InfoPath for federal agencies — details on the <a href="%s">hire page</a>. There is no public demo.', 'sage'),
                esc_url($hireUrl)
            )
        ),
        'range' => field('glance_range', mh_adjacent_range_copy()),
        'nda' => field(
            'glance_nda',
            __('Hiring managers can ask for a private walkthrough of constrained employer work under NDA.', 'sage')
        ),
        'links' => [
            [
                'label' => __('Contact', 'sage'),
                'href' => home_url('/contact/'),
                'icon' => 'mail',
                'external' => false,
            ],
            [
                'label' => __('Hire', 'sage'),
                'href' => $hireUrl,
                'icon' => 'briefcase',
                'external' => false,
            ],
            [
                'label' => __('Themes & plugins', 'sage'),
                'href' => home_url('/projects/'),
                'icon' => 'globe',
                'external' => false,
            ],
            [
                'label' => __('GitHub', 'sage'),
                'href' => 'https://github.com/'.mh_github_login(),
                'icon' => 'github',
                'external' => true,
            ],
        ],
    ];
}

/**
 * Whether a Work card is a studio product/demo (not a live client site).
 *
 * @param  array<string, mixed>  $project
 */
function mh_project_is_spec(array $project): bool
{
    if (! empty($project['is_client']) || ! empty($project['is_production'])) {
        return false;
    }

    return true;
}

/**
 * Short label for Work cards: Theme, Plugin, or Demo (never "Concept").
 *
 * @param  array<string, mixed>  $project
 */
function mh_spec_badge_label(array $project = []): string
{
    if ($project !== [] && ! mh_project_is_spec($project)) {
        return '';
    }

    $type = sanitize_key((string) ($project['product_type'] ?? ''));
    if ($type === '' && ! empty($project['post_id']) && function_exists(__NAMESPACE__.'\\mh_project_product_type')) {
        $type = mh_project_product_type((int) $project['post_id']);
    }

    if ($type === 'plugin') {
        return __('Plugin', 'sage');
    }

    if ($type === 'theme' || ($project['buy_url'] ?? '') !== '') {
        return __('Theme', 'sage');
    }

    return __('Demo', 'sage');
}

/** Featured repos plus recent public GitHub work (forks and the profile repo skipped). */
function mh_home_github_repos(int $limit = 6): array
{
    $featured = mh_code_page_repos();
    $live = mh_github_live_repos(12);
    $names = array_map(static fn ($r) => strtolower((string) ($r['name'] ?? '')), $featured);
    foreach ($live as $r) {
        $name = strtolower((string) ($r['name'] ?? ''));
        if ($name === '' || mh_github_is_hidden_repo($name) || in_array($name, $names, true)) {
            continue;
        }
        $featured[] = $r;
        $names[] = $name;
    }

    return array_slice($featured, 0, max(1, $limit));
}

/**
 * Post/page title for Blade {{ }} output.
 *
 * WordPress texturizes titles (e.g. apostrophe → &#8217;). Blade escapes & again,
 * which shows literal entities on the front end unless decoded first.
 */
function mh_post_title(int|\WP_Post|null $post = null): string
{
    return html_entity_decode((string) get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
        'matthummel' => 'Matt Hummel',
        'ridgesandvalleys' => 'Sage 11 theme',
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
    $desc = mh_visitor_brand_text($desc);
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

/**
 * Normalize a GitHub follower row for the Code page community panel.
 *
 * @param  array<string, mixed>  $row
 * @return array{login: string, name: string, avatar: string, url: string}|null
 */
function mh_github_follower_row(array $row): ?array
{
    $login = sanitize_user((string) ($row['login'] ?? $row['username'] ?? ''), true);
    if ($login === '') {
        return null;
    }
    $name = trim((string) ($row['name'] ?? $login));
    if ($name === '') {
        $name = $login;
    }
    $avatar = esc_url_raw((string) ($row['avatar'] ?? $row['avatar_url'] ?? $row['image'] ?? ''));
    $url = esc_url_raw((string) ($row['url'] ?? ''));
    if ($url === '') {
        $url = 'https://github.com/'.rawurlencode($login);
    }

    return compact('login', 'name', 'avatar', 'url');
}

/**
 * Curated GitHub followers from Code page fields (fallback when API is empty).
 *
 * @return list<array{login: string, name: string, avatar: string, url: string}>
 */
function mh_github_followers_curated(int $limit = 24, ?int $codeId = null): array
{
    $codeId = $codeId ?? mh_code_page_id();
    $rows = field_rows('code_github_followers', [], $codeId ?: null);
    $out = [];
    foreach ($rows as $row) {
        if (! is_array($row)) {
            continue;
        }
        $norm = mh_github_follower_row($row);
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
 * GitHub followers for the Code page: API then curated fields.
 *
 * @return list<array{login: string, name: string, avatar: string, url: string}>
 */
function mh_github_followers(int $limit = 24): array
{
    $limit = max(1, min(80, $limit));
    $filtered = apply_filters('mh/github_followers', null, $limit);
    if (is_array($filtered)) {
        $out = [];
        foreach ($filtered as $row) {
            if (! is_array($row)) {
                continue;
            }
            $norm = mh_github_follower_row($row);
            if ($norm !== null) {
                $out[] = $norm;
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    $login = mh_github_login();
    $key = 'mh_github_followers_v1_'.md5($login);
    $cached = get_transient($key);
    if (is_array($cached)) {
        $api = $cached;
    } else {
        $api = Github::fetchFollowers($login, min(80, max($limit, 30)));
        set_transient($key, $api, 6 * HOUR_IN_SECONDS);
    }

    if ($api !== []) {
        return array_slice($api, 0, $limit);
    }

    return mh_github_followers_curated($limit);
}

/**
 * Normalize a GitHub stargazer row for the Code page.
 *
 * @param  array<string, mixed>  $row
 * @return array{login: string, name: string, avatar: string, url: string, repo: string}|null
 */
function mh_github_stargazer_row(array $row): ?array
{
    $norm = mh_github_follower_row($row);
    if ($norm === null) {
        return null;
    }

    return array_merge($norm, [
        'repo' => sanitize_text_field((string) ($row['repo'] ?? '')),
    ]);
}

/**
 * Recent stargazers across featured Code page repos (deduped by login).
 *
 * @return list<array{login: string, name: string, avatar: string, url: string, repo: string}>
 */
function mh_github_stargazers(int $limit = 24, ?int $post_id = null): array
{
    $limit = max(1, min(48, $limit));
    $login = mh_github_login();
    $key = 'mh_github_stargazers_v1_'.md5($login.(string) $limit);
    $cached = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limit);
    }

    $repos = mh_code_page_repos($post_id);
    $seen = [];
    $out = [];

    foreach ($repos as $repo) {
        $name = trim((string) ($repo['name'] ?? ''));
        if ($name === '' || mh_github_is_hidden_repo($name)) {
            continue;
        }
        foreach (Github::fetchStargazers($login, $name, 20) as $row) {
            $loginKey = strtolower((string) ($row['login'] ?? ''));
            if ($loginKey === '' || isset($seen[$loginKey])) {
                continue;
            }
            $seen[$loginKey] = true;
            $norm = mh_github_stargazer_row($row);
            if ($norm !== null) {
                $out[] = $norm;
            }
            if (count($out) >= $limit) {
                break 2;
            }
        }
    }

    set_transient($key, $out, 6 * HOUR_IN_SECONDS);

    return $out;
}

/**
 * Total stars earned across all public owned repos (not just featured).
 *
 * @return array{total: int, repos: list<array{name: string, stars: int, url: string}>}
 */
function mh_github_stars_earned(): array
{
    $login = mh_github_login();
    $filtered = apply_filters('mh/github_stars_earned', null, $login);
    if (is_array($filtered) && isset($filtered['total'])) {
        return [
            'total' => (int) $filtered['total'],
            'repos' => is_array($filtered['repos'] ?? null) ? $filtered['repos'] : [],
        ];
    }

    return Github::fetchStarTotals($login);
}

/**
 * Total stars across all public owned repos (live API).
 */
function mh_github_star_total(?int $post_id = null): int
{
    unset($post_id);

    return (int) (mh_github_stars_earned()['total'] ?? 0);
}

/**
 * Repos Matt watches: GitHub subscriptions when available, else starred list.
 *
 * Public watching often returns empty (HTTP 204); starred is the public fallback.
 *
 * @return array{source: string, items: list<array{name: string, full: string, desc: string, url: string, stars: int, lang: string, owner: string}>}
 */
function mh_github_watching(int $limit = 36): array
{
    $limit = max(1, min(100, $limit));
    $login = mh_github_login();
    $filtered = apply_filters('mh/github_watching', null, $limit);
    if (is_array($filtered) && isset($filtered['items']) && is_array($filtered['items'])) {
        return [
            'source' => (string) ($filtered['source'] ?? 'filter'),
            'items' => array_slice($filtered['items'], 0, $limit),
        ];
    }

    $watching = Github::fetchWatching($login, $limit);
    if ($watching !== []) {
        return ['source' => 'watching', 'items' => $watching];
    }

    return [
        'source' => 'starred',
        'items' => Github::fetchStarred($login, $limit),
    ];
}

/**
 * Milestone badges earned from live GitHub stats (no fake achievements).
 *
 * @return list<array{label: string, detail: string, icon: string, class: string}>
 */
function mh_code_page_github_badges(?int $post_id = null): array
{
    $profile = mh_github_profile();
    $calendar = mh_github_calendar();
    $starsEarned = mh_github_stars_earned();
    $starTotal = (int) ($starsEarned['total'] ?? 0);
    $followers = (int) ($profile['followers'] ?? 0);
    $repos = (int) ($profile['public_repos'] ?? 0);
    $contributions = (int) ($calendar['total'] ?? 0);
    $badges = [];

    if ($repos >= 1) {
        $badges[] = [
            'label' => __('Open source', 'sage'),
            'detail' => sprintf(_n('%s public repo', '%s public repos', $repos, 'sage'), number_format_i18n($repos)),
            'icon' => 'github',
            'class' => 'code-gh-badge--oss',
        ];
    }

    foreach ([100, 50, 25, 10] as $tier) {
        if ($starTotal >= $tier) {
            $badges[] = [
                'label' => sprintf(__('%s+ stars earned', 'sage'), number_format_i18n($tier)),
                'detail' => sprintf(
                    /* translators: %s: formatted star count */
                    __('Across public repos — %s total', 'sage'),
                    number_format_i18n($starTotal)
                ),
                'icon' => 'star',
                'class' => 'code-gh-badge--stars',
            ];
            break;
        }
    }

    foreach ([100, 50, 25, 10] as $tier) {
        if ($followers >= $tier) {
            $badges[] = [
                'label' => sprintf(__('%s+ followers', 'sage'), number_format_i18n($tier)),
                'detail' => sprintf(
                    /* translators: %s: formatted follower count */
                    __('GitHub community — %s following', 'sage'),
                    number_format_i18n($followers)
                ),
                'icon' => 'users',
                'class' => 'code-gh-badge--followers',
            ];
            break;
        }
    }

    foreach ([1000, 500, 100] as $tier) {
        if ($contributions >= $tier) {
            $badges[] = [
                'label' => sprintf(__('%s+ contributions', 'sage'), number_format_i18n($tier)),
                'detail' => sprintf(
                    /* translators: %s: formatted contribution count */
                    __('Public commits this year — %s total', 'sage'),
                    number_format_i18n($contributions)
                ),
                'icon' => 'git',
                'class' => 'code-gh-badge--contrib',
            ];
            break;
        }
    }

    $activityLabels = [];
    foreach (mh_code_page_repos($post_id) as $repo) {
        $name = trim((string) ($repo['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $meta = Github::fetchRepoMeta(mh_github_login(), $name);
        [$badge] = mh_repo_activity_badge(
            (string) ($meta['pushed'] ?? ''),
            (int) ($meta['stars'] ?? 0),
            (int) ($meta['forks'] ?? 0),
            (string) ($meta['desc'] ?? '')
        );
        if ($badge !== '' && ! in_array($badge, $activityLabels, true)) {
            $activityLabels[] = $badge;
        }
    }
    foreach ($activityLabels as $label) {
        $class = match ($label) {
            'Active' => 'badge--active',
            'Recent' => 'badge--recent',
            'Maintained' => 'badge--maintained',
            'Stable' => 'badge--stable',
            default => 'badge--archived',
        };
        $badges[] = [
            'label' => $label,
            'detail' => __('Repo activity badge on a featured project', 'sage'),
            'icon' => 'git',
            'class' => 'code-gh-badge--activity '.$class,
        ];
    }

    return $badges;
}

/**
 * Resolve the Code page post ID for field lookups.
 */
function mh_code_page_id(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $pages = get_pages([
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-code.blade.php',
        'number' => 1,
        'post_status' => 'publish',
    ]);
    $id = ($pages && ! is_wp_error($pages)) ? (int) ($pages[0]->ID ?? 0) : 0;

    return $id;
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
        if ($name === '' || mh_github_is_hidden_repo($name) || in_array($name, $featuredNames, true)) {
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
    $cache_key = 'mh_oss_live_v3_'.md5($login.(string) $repo_count);

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
        $desc = mh_visitor_brand_text($desc);

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

/** Studio example work for the Projects page. */
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
    if (mh_project_cpt_has_posts()) {
        $live = mh_query_project_cards(['live_only' => true]);
        if ($live !== []) {
            $cats = array_unique(array_map(fn ($p) => (string) ($p['cat'] ?? ''), $live));
            $cats = array_values(array_filter($cats, fn ($c) => $c !== ''));
            sort($cats);

            return $cats;
        }
    }

    $cats = array_unique(array_map(fn ($p) => $p['cat'], mh_studio_projects()));
    sort($cats);

    return $cats;
}

/** Custom post type slug for Work / example sites. */
function mh_project_post_type(): string
{
    return 'project';
}

function mh_project_live_meta_key(): string
{
    return '_mh_project_live';
}

/** Whether a project is shown on the public Work page and home grid. */
function mh_project_is_live(int $post_id): bool
{
    return get_post_meta($post_id, mh_project_live_meta_key(), true) === '1';
}

/** True when at least one project post exists (any status). */
function mh_project_cpt_has_posts(): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    $q = new \WP_Query([
        'post_type' => mh_project_post_type(),
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    $has = $q->found_posts > 0;
    wp_reset_postdata();

    return $has;
}

/**
 * Resolve the public Work / project screenshot URL for a project.
 *
 * Featured image wins when set (so Media Library / Generate featured image
 * updates show on /projects/ and /projects/{slug}/). Falls back to `_mh_project_image`
 * (bundled JPEG filename or absolute URL).
 */
function mh_project_card_image_url(int $post_id): string
{
    if ($post_id < 1) {
        return '';
    }

    if (has_post_thumbnail($post_id)) {
        $thumb = (string) wp_get_attachment_image_url((int) get_post_thumbnail_id($post_id), 'large');
        if ($thumb === '') {
            $thumb = (string) wp_get_attachment_url((int) get_post_thumbnail_id($post_id));
        }
        if ($thumb !== '') {
            return $thumb;
        }
    }

    return mh_studio_project_image_url([
        'image' => (string) get_post_meta($post_id, '_mh_project_image', true),
    ]);
}

/**
 * Map a project post to the Work card array shape.
 *
 * @return array<string, mixed>
 */
function mh_project_post_to_card(\WP_Post $post): array
{
    $post_id = (int) $post->ID;
    $techRaw = (string) get_post_meta($post_id, '_mh_project_tech', true);
    $tech = array_values(array_filter(array_map('trim', explode(',', $techRaw))));

    $buyUrl = function_exists(__NAMESPACE__.'\\mh_project_buy_url') ? mh_project_buy_url($post_id) : '';
    $priceLabel = function_exists(__NAMESPACE__.'\\mh_project_price_label') ? mh_project_price_label($post_id) : '';
    $buyLabel = function_exists(__NAMESPACE__.'\\mh_project_buy_label') ? mh_project_buy_label($post_id) : __('Buy theme', 'sage');
    $productType = function_exists(__NAMESPACE__.'\\mh_project_product_type')
        ? mh_project_product_type($post_id)
        : 'theme';
    $card = [
        'slug' => $post->post_name,
        'title' => wp_specialchars_decode((string) $post->post_title, ENT_QUOTES),
        'cat' => (string) get_post_meta($post_id, '_mh_project_cat', true),
        'place' => (string) get_post_meta($post_id, '_mh_project_place', true),
        'blurb' => (string) get_post_meta($post_id, '_mh_project_blurb', true),
        'tech' => $tech,
        'concept' => (string) get_post_meta($post_id, '_mh_project_concept', true),
        'demo' => mh_project_demo_url($post_id),
        'url' => mh_concept_page_url($post->post_name, $post_id),
        'image' => mh_project_card_image_url($post_id),
        'post_id' => $post_id,
        'product_id' => function_exists(__NAMESPACE__.'\\mh_project_product_id') ? mh_project_product_id($post_id) : 0,
        'product_type' => $productType,
        'buy_url' => $buyUrl,
        'buy_label' => $buyLabel,
        'price_label' => $priceLabel,
    ];
    $card['help_url'] = function_exists(__NAMESPACE__.'\\mh_work_help_url')
        ? mh_work_help_url($card)
        : mh_work_contact_url($card);

    return $card;
}

/**
 * Query project posts for admin or front-end lists.
 *
 * @param  array{live_only?: bool, limit?: int}  $args
 * @return list<array<string, mixed>>
 */
function mh_query_project_cards(array $args = []): array
{
    $liveOnly = ! empty($args['live_only']);
    $limit = isset($args['limit']) ? max(0, (int) $args['limit']) : -1;

    $queryArgs = [
        'post_type' => mh_project_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => ['date' => 'DESC', 'title' => 'ASC'],
        'order' => 'DESC',
        'no_found_rows' => true,
    ];

    if ($liveOnly) {
        $queryArgs['meta_query'] = [[
            'key' => mh_project_live_meta_key(),
            'value' => '1',
            'compare' => '=',
        ]];
    }

    $posts = get_posts($queryArgs);
    $out = [];
    foreach ($posts as $post) {
        if (! $post instanceof \WP_Post) {
            continue;
        }
        $out[] = mh_project_post_to_card($post);
    }

    return $out;
}

/** Live projects for the Work page when the CPT is in use. */
function mh_projects_live_for_work(): array
{
    if (! mh_project_cpt_has_posts()) {
        return [];
    }

    return mh_query_project_cards(['live_only' => true]);
}

/** Find a project card by slug (live-only or any published project). */
function mh_project_card_by_slug(string $slug, bool $liveOnly = false): ?array
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }

    $posts = get_posts([
        'post_type' => mh_project_post_type(),
        'name' => $slug,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'no_found_rows' => true,
    ]);
    if ($posts === []) {
        return null;
    }

    $post = $posts[0];
    if (! $post instanceof \WP_Post) {
        return null;
    }
    if ($liveOnly && ! mh_project_is_live((int) $post->ID)) {
        return null;
    }

    return mh_project_post_to_card($post);
}

/** Create or update one studio concept as a project post. Returns post ID or 0. */
function mh_upsert_project_from_studio(array $project, int $menu_order = 0): int
{
    $slug = sanitize_title((string) ($project['slug'] ?? ''));
    if ($slug === '') {
        return 0;
    }

    $existing = get_posts([
        'post_type' => mh_project_post_type(),
        'name' => $slug,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    $postarr = [
        'post_type' => mh_project_post_type(),
        'post_status' => 'publish',
        'post_title' => (string) ($project['title'] ?? $slug),
        'post_name' => $slug,
        'menu_order' => $menu_order,
    ];

    if ($existing !== []) {
        $postarr['ID'] = (int) $existing[0];
        $post_id = wp_update_post($postarr, true);
    } else {
        $post_id = wp_insert_post($postarr, true);
    }

    if (is_wp_error($post_id) || ! $post_id) {
        return 0;
    }

    $post_id = (int) $post_id;
    $tech = $project['tech'] ?? [];
    if (! is_array($tech)) {
        $tech = [];
    }

    update_post_meta($post_id, '_mh_project_cat', (string) ($project['cat'] ?? ''));
    update_post_meta($post_id, '_mh_project_place', (string) ($project['place'] ?? ''));
    update_post_meta($post_id, '_mh_project_blurb', (string) ($project['blurb'] ?? ''));
    update_post_meta($post_id, '_mh_project_tech', implode(', ', $tech));
    update_post_meta($post_id, '_mh_project_concept', (string) ($project['concept'] ?? ''));
    update_post_meta($post_id, '_mh_project_image', (string) ($project['image'] ?? ''));
    update_post_meta($post_id, '_mh_project_source', 'ridges-and-valleys');

    if (! mh_project_is_live($post_id) && get_post_meta($post_id, mh_project_live_meta_key(), true) === '') {
        update_post_meta($post_id, mh_project_live_meta_key(), '0');
    }

    return $post_id;
}

/** One-time import of studio example projects into the project CPT. */
function mh_import_studio_projects_to_cpt(): void
{
    if (get_option('mh_projects_cpt_seeded_v1')) {
        return;
    }

    $order = 0;
    foreach (mh_studio_projects() as $project) {
        mh_upsert_project_from_studio($project, $order);
        $order++;
    }

    update_option('mh_projects_cpt_seeded_v1', true);
}

function mh_register_project_post_type(): void
{
    register_post_type(mh_project_post_type(), [
        'labels' => [
            'name' => __('Projects', 'sage'),
            'singular_name' => __('Project', 'sage'),
            'add_new' => __('Add project', 'sage'),
            'add_new_item' => __('Add project', 'sage'),
            'edit_item' => __('Edit project', 'sage'),
            'new_item' => __('New project', 'sage'),
            'view_item' => __('View project', 'sage'),
            'search_items' => __('Search projects', 'sage'),
            'not_found' => __('No projects found.', 'sage'),
            'not_found_in_trash' => __('No projects found in Trash.', 'sage'),
            'all_items' => __('Projects', 'sage'),
            'menu_name' => __('Projects', 'sage'),
        ],
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'menu_icon' => 'dashicons-portfolio',
        'menu_position' => 26,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'supports' => ['title', 'thumbnail', 'page-attributes'],
        'has_archive' => false,
        'rewrite' => [
            'slug' => mh_concept_rewrite_slug(),
            'with_front' => false,
        ],
        'query_var' => true,
        'show_in_rest' => false,
    ]);
}

function mh_project_admin_meta_box(\WP_Post $post): void
{
    wp_nonce_field('mh_project_meta', 'mh_project_meta_nonce');

    $cat = (string) get_post_meta($post->ID, '_mh_project_cat', true);
    $place = (string) get_post_meta($post->ID, '_mh_project_place', true);
    $blurb = (string) get_post_meta($post->ID, '_mh_project_blurb', true);
    $tech = (string) get_post_meta($post->ID, '_mh_project_tech', true);
    $concept = (string) get_post_meta($post->ID, '_mh_project_concept', true);
    $demo = (string) get_post_meta($post->ID, '_mh_project_demo', true);
    $eyebrow = (string) get_post_meta($post->ID, '_mh_project_eyebrow', true);
    $summary = (string) get_post_meta($post->ID, '_mh_project_summary', true);
    $challenge = (string) get_post_meta($post->ID, '_mh_project_challenge', true);
    $approach = (string) get_post_meta($post->ID, '_mh_project_approach', true);
    $result = (string) get_post_meta($post->ID, '_mh_project_result', true);
    $deliverables = (string) get_post_meta($post->ID, '_mh_project_deliverables', true);
    $benefits = (string) get_post_meta($post->ID, '_mh_project_benefits', true);
    $faq = (string) get_post_meta($post->ID, '_mh_project_faq', true);
    $productType = (string) get_post_meta($post->ID, '_mh_project_product_type', true);
    if ($productType === '') {
        $productType = 'theme';
    }
    $productId = (string) get_post_meta($post->ID, '_mh_project_product_id', true);
    $price = (string) get_post_meta($post->ID, '_mh_project_price', true);
    $forSale = get_post_meta($post->ID, '_mh_project_for_sale', true) !== '0';
    $image = (string) get_post_meta($post->ID, '_mh_project_image', true);
    $live = mh_project_is_live((int) $post->ID);
    $metrics = [];
    for ($i = 1; $i <= 3; $i++) {
        $metrics[$i] = [
            'value' => (string) get_post_meta($post->ID, "_mh_project_m{$i}_value", true),
            'label' => (string) get_post_meta($post->ID, "_mh_project_m{$i}_label", true),
        ];
    }

    echo '<p><label><input type="checkbox" name="mh_project_live" value="1" '.checked($live, true, false).'> ';
    echo '<strong>'.esc_html__('Show on site', 'sage').'</strong></label></p>';
    echo '<p class="description">'.esc_html__('When checked, this project appears on /projects/, the home grid, and its public /concept/ page.', 'sage').'</p>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Work card', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    mh_project_admin_field_row(__('Category', 'sage'), 'mh_project_cat', $cat, __('Tours, Hotels, Restaurants…', 'sage'));
    mh_project_admin_field_row(__('Place', 'sage'), 'mh_project_place', $place, __('City, State', 'sage'));
    mh_project_admin_field_row(__('Card blurb', 'sage'), 'mh_project_blurb', $blurb, '', 'textarea');
    mh_project_admin_field_row(__('Tech (comma separated)', 'sage'), 'mh_project_tech', $tech, __('WordPress, Sage, WooCommerce', 'sage'));
    mh_project_admin_field_row(
        __('Screenshot file or URL', 'sage'),
        'mh_project_image',
        $image,
        __('Fallback only: used when this project has no Featured image. Example: hallowed-ground.jpg or https://… Featured image always wins on the Work grid and concept page.', 'sage')
    );
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('WooCommerce', 'sage').'</h3>';
    echo '<p class="description">'.esc_html__('Live projects become WooCommerce products when the plugin is active. Buy theme adds to cart; Get help opens the contact form.', 'sage').'</p>';
    echo '<p><label><input type="checkbox" name="mh_project_for_sale" value="1" '.checked($forSale, true, false).'> ';
    echo esc_html__('For sale (Buy theme)', 'sage').'</label></p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="mh_project_product_type">'.esc_html__('Landing type', 'sage').'</label></th><td>';
    echo '<select id="mh_project_product_type" name="mh_project_product_type">';
    foreach ([
        'theme' => __('Theme product', 'sage'),
        'plugin' => __('Plugin product', 'sage'),
        'concept' => __('Concept (hire copy)', 'sage'),
    ] as $value => $label) {
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($value),
            selected($productType, $value, false),
            esc_html($label)
        );
    }
    echo '</select></td></tr>';
    mh_project_admin_field_row(
        __('Theme price (USD)', 'sage'),
        'mh_project_price',
        $price,
        function_exists(__NAMESPACE__.'\\mh_default_theme_price') ? mh_default_theme_price() : '149'
    );
    mh_project_admin_field_row(__('WooCommerce product ID', 'sage'), 'mh_project_product_id', $productId, __('Auto-filled. Override only to point at a different product.', 'sage'));
    mh_project_admin_field_row(__('Benefits (one per line)', 'sage'), 'mh_project_benefits', $benefits, '', 'textarea');
    mh_project_admin_field_row(__('FAQ (Question|||Answer per line)', 'sage'), 'mh_project_faq', $faq, '', 'textarea');
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Concept / product page', 'sage').'</h3>';
    echo '<p class="description">'.esc_html__('These fields power /projects/{slug}/. Edit anytime — changes show on the next page load.', 'sage').'</p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    mh_project_admin_field_row(__('Eyebrow', 'sage'), 'mh_project_eyebrow', $eyebrow, __('Concept · Boutique inn', 'sage'));
    mh_project_admin_field_row(__('Summary', 'sage'), 'mh_project_summary', $summary, '', 'textarea');
    mh_project_admin_field_row(__('The problem', 'sage'), 'mh_project_challenge', $challenge, '', 'textarea');
    mh_project_admin_field_row(__('How I shaped it', 'sage'), 'mh_project_approach', $approach, '', 'textarea');
    mh_project_admin_field_row(__('What you get', 'sage'), 'mh_project_result', $result, '', 'textarea');
    mh_project_admin_field_row(__('Deliverables (one per line)', 'sage'), 'mh_project_deliverables', $deliverables, '', 'textarea');
    mh_project_admin_field_row(__('Live demo URL', 'sage'), 'mh_project_demo', $demo, 'https://', 'url');
    mh_project_admin_field_row(__('GitHub / case URL', 'sage'), 'mh_project_concept', $concept, 'https://github.com/… or legacy case URL', 'url');
    echo '</tbody></table>';

    echo '<h3 style="margin:1.25rem 0 .5rem">'.esc_html__('Concept metrics (up to 3)', 'sage').'</h3>';
    echo '<table class="form-table" role="presentation"><tbody>';
    for ($i = 1; $i <= 3; $i++) {
        echo '<tr><th scope="row">'.esc_html(sprintf(__('Metric %d', 'sage'), $i)).'</th><td>';
        printf(
            '<input class="regular-text" type="text" name="mh_project_m%d_value" value="%s" placeholder="%s" style="max-width:8rem;margin-right:.5rem">',
            $i,
            esc_attr($metrics[$i]['value']),
            esc_attr__('Value', 'sage')
        );
        printf(
            '<input class="regular-text" type="text" name="mh_project_m%d_label" value="%s" placeholder="%s">',
            $i,
            esc_attr($metrics[$i]['label']),
            esc_attr__('Label', 'sage')
        );
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}

function mh_project_admin_field_row(string $label, string $name, string $value, string $placeholder = '', string $type = 'text'): void
{
    $rows = in_array($name, [
        'mh_project_challenge',
        'mh_project_approach',
        'mh_project_result',
        'mh_project_deliverables',
        'mh_project_benefits',
        'mh_project_faq',
    ], true) ? 5 : 3;
    echo '<tr><th scope="row"><label for="'.esc_attr($name).'">'.esc_html($label).'</label></th><td>';
    if ($type === 'textarea') {
        printf(
            '<textarea class="large-text" rows="%4$d" id="%1$s" name="%1$s" placeholder="%2$s">%3$s</textarea>',
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

function mh_save_project_meta(int $post_id): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! isset($_POST['mh_project_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_project_meta_nonce'])), 'mh_project_meta')) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, mh_project_live_meta_key(), isset($_POST['mh_project_live']) ? '1' : '0');
    update_post_meta($post_id, '_mh_project_cat', sanitize_text_field(wp_unslash($_POST['mh_project_cat'] ?? '')));
    update_post_meta($post_id, '_mh_project_place', sanitize_text_field(wp_unslash($_POST['mh_project_place'] ?? '')));
    update_post_meta($post_id, '_mh_project_blurb', sanitize_textarea_field(wp_unslash($_POST['mh_project_blurb'] ?? '')));
    update_post_meta($post_id, '_mh_project_eyebrow', sanitize_text_field(wp_unslash($_POST['mh_project_eyebrow'] ?? '')));
    update_post_meta($post_id, '_mh_project_summary', sanitize_textarea_field(wp_unslash($_POST['mh_project_summary'] ?? '')));
    update_post_meta($post_id, '_mh_project_challenge', sanitize_textarea_field(wp_unslash($_POST['mh_project_challenge'] ?? '')));
    update_post_meta($post_id, '_mh_project_approach', sanitize_textarea_field(wp_unslash($_POST['mh_project_approach'] ?? '')));
    update_post_meta($post_id, '_mh_project_result', sanitize_textarea_field(wp_unslash($_POST['mh_project_result'] ?? '')));
    update_post_meta($post_id, '_mh_project_deliverables', sanitize_textarea_field(wp_unslash($_POST['mh_project_deliverables'] ?? '')));
    update_post_meta($post_id, '_mh_project_benefits', sanitize_textarea_field(wp_unslash($_POST['mh_project_benefits'] ?? '')));
    update_post_meta($post_id, '_mh_project_faq', sanitize_textarea_field(wp_unslash($_POST['mh_project_faq'] ?? '')));
    $productType = sanitize_key((string) wp_unslash($_POST['mh_project_product_type'] ?? 'theme'));
    if (! in_array($productType, ['concept', 'theme', 'plugin'], true)) {
        $productType = 'theme';
    }
    update_post_meta($post_id, '_mh_project_product_type', $productType);
    update_post_meta($post_id, '_mh_project_for_sale', isset($_POST['mh_project_for_sale']) ? '1' : '0');
    $price = sanitize_text_field(wp_unslash($_POST['mh_project_price'] ?? ''));
    if ($price !== '' && ! is_numeric($price)) {
        $price = '';
    }
    update_post_meta($post_id, '_mh_project_price', $price);
    update_post_meta($post_id, '_mh_project_product_id', (string) max(0, (int) ($_POST['mh_project_product_id'] ?? 0)));
    update_post_meta($post_id, '_mh_project_tech', sanitize_text_field(wp_unslash($_POST['mh_project_tech'] ?? '')));
    update_post_meta($post_id, '_mh_project_demo', esc_url_raw(wp_unslash($_POST['mh_project_demo'] ?? '')));
    update_post_meta($post_id, '_mh_project_concept', esc_url_raw(wp_unslash($_POST['mh_project_concept'] ?? '')));
    update_post_meta($post_id, '_mh_project_image', sanitize_text_field(wp_unslash($_POST['mh_project_image'] ?? '')));

    for ($i = 1; $i <= 3; $i++) {
        update_post_meta($post_id, "_mh_project_m{$i}_value", sanitize_text_field(wp_unslash($_POST["mh_project_m{$i}_value"] ?? '')));
        update_post_meta($post_id, "_mh_project_m{$i}_label", sanitize_text_field(wp_unslash($_POST["mh_project_m{$i}_label"] ?? '')));
    }
}

function mh_set_project_live(int $post_id, bool $live): void
{
    if ($post_id <= 0 || get_post_type($post_id) !== mh_project_post_type()) {
        return;
    }
    update_post_meta($post_id, mh_project_live_meta_key(), $live ? '1' : '0');
    if (function_exists(__NAMESPACE__.'\\mh_sync_project_product')) {
        mh_sync_project_product($post_id);
    }
}

/** Distinct meta values for admin filters (category / place). */
function mh_project_meta_choices(string $meta_key): array
{
    global $wpdb;
    $rows = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT pm.meta_value
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s
           AND p.post_type = %s
           AND p.post_status != 'trash'
           AND pm.meta_value <> ''
         ORDER BY pm.meta_value ASC",
        $meta_key,
        mh_project_post_type()
    ));

    if (! is_array($rows)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $rows)));
}

add_action('init', __NAMESPACE__.'\\mh_register_project_post_type', 20);
add_action('init', function (): void {
    if (! get_option('mh_projects_cpt_seeded_v1') && ! wp_installing()) {
        mh_import_studio_projects_to_cpt();
    }
}, 31);

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_project_details',
        __('Project fields', 'sage'),
        __NAMESPACE__.'\\mh_project_admin_meta_box',
        mh_project_post_type(),
        'normal',
        'high'
    );
});

add_action('save_post_'.mh_project_post_type(), function (int $post_id): void {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    mh_save_project_meta($post_id);
});

add_filter('manage_'.mh_project_post_type().'_posts_columns', function (array $columns): array {
    $out = [];
    foreach ($columns as $key => $label) {
        if ($key === 'date') {
            continue;
        }
        $out[$key] = $label;
        if ($key === 'title') {
            $out['mh_project_live'] = __('On site', 'sage');
            $out['mh_project_cat'] = __('Category', 'sage');
            $out['mh_project_place'] = __('Place', 'sage');
        }
    }
    $out['date'] = __('Date', 'sage');

    return $out;
});

add_filter('manage_edit-'.mh_project_post_type().'_sortable_columns', function (array $columns): array {
    $columns['mh_project_cat'] = 'mh_project_cat';
    $columns['mh_project_place'] = 'mh_project_place';
    $columns['mh_project_live'] = 'mh_project_live';
    $columns['date'] = ['date', true];

    return $columns;
});

add_action('manage_'.mh_project_post_type().'_posts_custom_column', function (string $column, int $post_id): void {
    if ($column === 'mh_project_cat') {
        $cat = (string) get_post_meta($post_id, '_mh_project_cat', true);
        if ($cat === '') {
            echo '—';

            return;
        }
        $url = add_query_arg([
            'post_type' => mh_project_post_type(),
            'mh_cat' => $cat,
        ], admin_url('edit.php'));
        printf('<a href="%s">%s</a>', esc_url($url), esc_html($cat));

        return;
    }
    if ($column === 'mh_project_place') {
        $place = (string) get_post_meta($post_id, '_mh_project_place', true);
        if ($place === '') {
            echo '—';

            return;
        }
        $url = add_query_arg([
            'post_type' => mh_project_post_type(),
            'mh_place' => $place,
        ], admin_url('edit.php'));
        printf('<a href="%s">%s</a>', esc_url($url), esc_html($place));

        return;
    }
    if ($column === 'mh_project_live') {
        $live = mh_project_is_live($post_id);
        $url = wp_nonce_url(
            admin_url('admin.php?action=mh_toggle_project_live&post='.$post_id),
            'mh_toggle_project_live_'.$post_id
        );
        $label = $live ? __('On', 'sage') : __('Off', 'sage');
        $class = $live ? 'mh-project-toggle is-on' : 'mh-project-toggle is-off';
        printf(
            '<a class="%1$s" href="%2$s" title="%3$s"><span class="mh-project-toggle__track" aria-hidden="true"></span><span class="mh-project-toggle__label">%4$s</span></a>',
            esc_attr($class),
            esc_url($url),
            esc_attr($live ? __('Hide from site', 'sage') : __('Show on site', 'sage')),
            esc_html($label)
        );
    }
}, 10, 2);

add_action('restrict_manage_posts', function (string $post_type): void {
    if ($post_type !== mh_project_post_type()) {
        return;
    }

    $currentCat = isset($_GET['mh_cat']) ? sanitize_text_field(wp_unslash($_GET['mh_cat'])) : '';
    $currentPlace = isset($_GET['mh_place']) ? sanitize_text_field(wp_unslash($_GET['mh_place'])) : '';
    $currentLive = isset($_GET['mh_live']) ? sanitize_text_field(wp_unslash($_GET['mh_live'])) : '';

    echo '<label class="screen-reader-text" for="mh_filter_cat">'.esc_html__('Filter by category', 'sage').'</label>';
    echo '<select name="mh_cat" id="mh_filter_cat">';
    echo '<option value="">'.esc_html__('All categories', 'sage').'</option>';
    foreach (mh_project_meta_choices('_mh_project_cat') as $cat) {
        printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($cat), selected($currentCat, $cat, false), esc_html($cat));
    }
    echo '</select>';

    echo '<label class="screen-reader-text" for="mh_filter_place">'.esc_html__('Filter by place', 'sage').'</label>';
    echo '<select name="mh_place" id="mh_filter_place">';
    echo '<option value="">'.esc_html__('All places', 'sage').'</option>';
    foreach (mh_project_meta_choices('_mh_project_place') as $place) {
        printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($place), selected($currentPlace, $place, false), esc_html($place));
    }
    echo '</select>';

    echo '<label class="screen-reader-text" for="mh_filter_live">'.esc_html__('Filter by on-site status', 'sage').'</label>';
    echo '<select name="mh_live" id="mh_filter_live">';
    echo '<option value="">'.esc_html__('On site: all', 'sage').'</option>';
    printf('<option value="1"%s>%s</option>', selected($currentLive, '1', false), esc_html__('On site only', 'sage'));
    printf('<option value="0"%s>%s</option>', selected($currentLive, '0', false), esc_html__('Hidden only', 'sage'));
    echo '</select>';
});

add_action('pre_get_posts', function (\WP_Query $query): void {
    if (! is_admin() || ! $query->is_main_query()) {
        return;
    }
    if ($query->get('post_type') !== mh_project_post_type()) {
        return;
    }

    $cat = isset($_GET['mh_cat']) ? sanitize_text_field(wp_unslash($_GET['mh_cat'])) : '';
    $place = isset($_GET['mh_place']) ? sanitize_text_field(wp_unslash($_GET['mh_place'])) : '';
    $live = isset($_GET['mh_live']) ? sanitize_text_field(wp_unslash($_GET['mh_live'])) : '';

    $metaQuery = [];
    if ($cat !== '') {
        $metaQuery[] = [
            'key' => '_mh_project_cat',
            'value' => $cat,
            'compare' => '=',
        ];
    }
    if ($place !== '') {
        $metaQuery[] = [
            'key' => '_mh_project_place',
            'value' => $place,
            'compare' => '=',
        ];
    }
    if ($live === '1' || $live === '0') {
        $metaQuery[] = [
            'key' => mh_project_live_meta_key(),
            'value' => $live,
            'compare' => '=',
        ];
    }
    if ($metaQuery !== []) {
        if (count($metaQuery) > 1) {
            $metaQuery['relation'] = 'AND';
        }
        $query->set('meta_query', $metaQuery);
    }

    $orderby = (string) $query->get('orderby');
    if ($orderby === '' || $orderby === 'menu_order title' || $orderby === 'menu_order') {
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');

        return;
    }

    $order = strtoupper((string) $query->get('order')) === 'ASC' ? 'ASC' : 'DESC';
    if ($orderby === 'mh_project_cat') {
        $query->set('meta_key', '_mh_project_cat');
        $query->set('orderby', 'meta_value');
        $query->set('order', $order);
    } elseif ($orderby === 'mh_project_place') {
        $query->set('meta_key', '_mh_project_place');
        $query->set('orderby', 'meta_value');
        $query->set('order', $order);
    } elseif ($orderby === 'mh_project_live') {
        $query->set('meta_key', mh_project_live_meta_key());
        $query->set('orderby', 'meta_value');
        $query->set('order', $order);
    }
});

add_action('admin_head', function (): void {
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== mh_project_post_type()) {
        return;
    }
    echo '<style>
      .column-mh_project_live { width: 6.5rem; }
      .column-mh_project_cat { width: 8rem; }
      .column-mh_project_place { width: 12rem; }
      .mh-project-toggle {
        display: inline-flex; align-items: center; gap: .4rem;
        text-decoration: none; font-weight: 600; font-size: 12px;
      }
      .mh-project-toggle__track {
        width: 2.1rem; height: 1.15rem; border-radius: 999px;
        background: #cbd5e1; position: relative; display: inline-block;
        transition: background .15s;
      }
      .mh-project-toggle__track::after {
        content: ""; position: absolute; top: 2px; left: 2px;
        width: .85rem; height: .85rem; border-radius: 50%;
        background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2);
        transition: transform .15s;
      }
      .mh-project-toggle.is-on .mh-project-toggle__track { background: #16a34a; }
      .mh-project-toggle.is-on .mh-project-toggle__track::after { transform: translateX(.95rem); }
      .mh-project-toggle.is-on .mh-project-toggle__label { color: #15803d; }
      .mh-project-toggle.is-off .mh-project-toggle__label { color: #64748b; }
    </style>';
});

add_filter('post_row_actions', function (array $actions, \WP_Post $post): array {
    if ($post->post_type !== mh_project_post_type()) {
        return $actions;
    }

    $live = mh_project_is_live((int) $post->ID);
    $url = wp_nonce_url(
        admin_url('admin.php?action=mh_toggle_project_live&post='.(int) $post->ID),
        'mh_toggle_project_live_'.(int) $post->ID
    );
    $actions['mh_project_live'] = $live
        ? '<a href="'.esc_url($url).'">'.esc_html__('Hide from site', 'sage').'</a>'
        : '<a href="'.esc_url($url).'">'.esc_html__('Show on site', 'sage').'</a>';

    $permalink = get_permalink($post);
    if (is_string($permalink) && $permalink !== '' && mh_project_is_live((int) $post->ID)) {
        $actions['view'] = '<a href="'.esc_url($permalink).'" target="_blank" rel="noopener">'.esc_html__('View project', 'sage').'</a>';
    }

    return $actions;
}, 10, 2);

add_action('admin_action_mh_toggle_project_live', function (): void {
    $post_id = (int) ($_GET['post'] ?? 0);
    if ($post_id <= 0) {
        wp_die(esc_html__('Invalid project.', 'sage'));
    }
    check_admin_referer('mh_toggle_project_live_'.$post_id);
    if (! current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('You cannot edit this project.', 'sage'));
    }

    mh_set_project_live($post_id, ! mh_project_is_live($post_id));

    $redirect = wp_get_referer();
    if (! is_string($redirect) || $redirect === '') {
        $redirect = admin_url('edit.php?post_type='.mh_project_post_type());
    }
    wp_safe_redirect($redirect);
    exit;
});

add_filter('bulk_actions-edit-'.mh_project_post_type(), function (array $actions): array {
    $actions['mh_project_show'] = __('Show on site', 'sage');
    $actions['mh_project_hide'] = __('Hide from site', 'sage');
    $actions['mh_project_import_concept'] = __('Import project fields (fill empty)', 'sage');

    return $actions;
});

add_filter('handle_bulk_actions-edit-'.mh_project_post_type(), function (string $redirect, string $action, array $post_ids): string {
    if ($action === 'mh_project_import_concept') {
        $seeds = mh_concept_pages_seed_data();
        $count = 0;
        foreach ($post_ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0 || ! current_user_can('edit_post', $post_id)) {
                continue;
            }
            $slug = (string) get_post_field('post_name', $post_id);
            $seed = is_array($seeds[$slug] ?? null) ? $seeds[$slug] : [];
            if ($seed === []) {
                continue;
            }
            mh_seed_project_concept_narrative($post_id, $seed, false);
            $count++;
        }

        return add_query_arg('mh_project_imported', $count, $redirect);
    }

    if ($action !== 'mh_project_show' && $action !== 'mh_project_hide') {
        return $redirect;
    }

    $live = $action === 'mh_project_show';
    $count = 0;
    foreach ($post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || ! current_user_can('edit_post', $post_id)) {
            continue;
        }
        mh_set_project_live($post_id, $live);
        $count++;
    }

    return add_query_arg('mh_project_bulk', $count, $redirect);
}, 10, 3);

add_action('admin_notices', function (): void {
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== mh_project_post_type()) {
        return;
    }

    if (isset($_GET['mh_project_bulk'])) {
        $count = (int) $_GET['mh_project_bulk'];
        if ($count > 0) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    _n('Updated %d project.', 'Updated %d projects.', $count, 'sage'),
                    $count
                ))
            );
        }
    }

    if (isset($_GET['mh_project_imported'])) {
        $count = (int) $_GET['mh_project_imported'];
        if ($count > 0) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    _n('Imported project fields for %d project (empty fields only).', 'Imported project fields for %d projects (empty fields only).', $count, 'sage'),
                    $count
                ))
            );
        }
    }
});

function mh_work_item_by_slug(string $slug): ?array
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }

    if (mh_project_cpt_has_posts()) {
        $fromCpt = mh_project_card_by_slug($slug);
        if ($fromCpt !== null) {
            return $fromCpt;
        }
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
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return $pageUrl ?: home_url('/projects/');
    }

    if (mh_project_cpt_has_posts()) {
        $card = mh_project_card_by_slug($slug);
        if ($card !== null) {
            return (string) ($card['url'] ?? mh_concept_page_url($slug, isset($card['post_id']) ? (int) $card['post_id'] : null));
        }
    }

    return mh_concept_page_url($slug);
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
        'Example sites — live WordPress demos for shops, tours, and inns.',
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
        str_contains($low, 'example site')
        || str_contains($low, 'studio project')
        || str_contains($low, 'live wordpress demo')
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

    if (preg_match('/^(.+?)(?:\s*[—–]\s*|\s+-\s+)(.+)$/u', $text, $matches)) {
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
            'org' => 'Matt Hummel',
            'period' => 'Current',
            'type' => 'Studio work · Remote',
            'url' => 'https://matthummel.com',
            'bullets' => "Publishing concept WordPress sites — Sage 11 examples, not a client gallery.\nBuilding WordPress sites shops can edit.\nOpen to agencies, overflow dev work, and full-time roles. Remote anywhere.",
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
            'id' => (int) $p->ID,
            'title' => mh_post_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('M j, Y', $p),
            'date_iso' => get_the_date('c', $p),
            'ex' => wp_trim_words(get_the_excerpt($p), 28),
            'cat' => $cat ? $cat->name : '',
            'cat_url' => $cat ? (string) get_category_link($cat->term_id) : '',
            'cat_slug' => $cat ? $cat->slug : '',
            'minutes' => mh_reading_minutes($p),
            'thumb' => mh_post_card_image((int) $p->ID),
            'deemphasize' => false,
            'cluster_day' => '',
        ];
    }
    wp_reset_postdata();

    return $out;
}

function mh_journal_day_key(array $post): string
{
    $iso = (string) ($post['date_iso'] ?? '');

    return $iso !== '' ? substr($iso, 0, 10) : '';
}

/**
 * Same-day clusters (known dump days or 3+ posts) get de-emphasized on Home.
 *
 * @param  array<string, int>  $counts
 */
function mh_journal_is_cluster_day(string $day, array $counts): bool
{
    if ($day === '2026-06-22') {
        return true;
    }

    return ($counts[$day] ?? 0) >= 3;
}

/**
 * Prefer WordPress/Sage/PHP posts when picking a featured journal item.
 *
 * @param  array<string, mixed>  $post
 */
function mh_journal_technical_score(array $post): int
{
    $score = (int) ($post['minutes'] ?? 0);
    $hay = strtolower((string) ($post['title'] ?? '').' '.(string) ($post['cat'] ?? '').' '.(string) ($post['ex'] ?? ''));
    foreach (['wordpress', 'sage', 'php', 'blade', 'vite', 'gutenberg', 'plugin', 'theme'] as $needle) {
        if (str_contains($hay, $needle)) {
            $score += 4;
        }
    }

    return $score;
}

/**
 * Homepage journal cards: feature the newest distinct technical post.
 *
 * Same-day clusters (including the Jun 22 2026 batch) stay off the featured
 * slot; at most one quiet item from a cluster may appear in the stack.
 *
 * @return list<array<string, mixed>>
 */
function mh_home_journal_posts(int $limit = 5): array
{
    $all = mh_latest_posts(max(18, $limit * 4));
    $counts = [];
    foreach ($all as $p) {
        $day = mh_journal_day_key($p);
        if ($day === '') {
            continue;
        }
        $counts[$day] = ($counts[$day] ?? 0) + 1;
    }

    $featured = null;
    $stack = [];
    $quiet = [];

    foreach ($all as $p) {
        $day = mh_journal_day_key($p);
        $isQuiet = mh_journal_is_cluster_day($day, $counts);
        $p['deemphasize'] = $isQuiet;
        $p['cluster_day'] = $isQuiet ? $day : '';
        if ($isQuiet) {
            $quiet[] = $p;

            continue;
        }
        if ($featured === null) {
            $featured = $p;

            continue;
        }
        $stack[] = $p;
    }

    if ($featured === null && $all !== []) {
        usort($quiet, static fn ($a, $b) => mh_journal_technical_score($b) <=> mh_journal_technical_score($a));
        $featured = $quiet[0] ?? $all[0];
        $featured['deemphasize'] = false;
        $featuredUrl = (string) ($featured['url'] ?? '');
        $quiet = array_values(array_filter(
            $quiet,
            static fn ($p) => (string) ($p['url'] ?? '') !== $featuredUrl
        ));
    }

    $out = [];
    if (is_array($featured)) {
        $out[] = $featured;
    }
    foreach ($stack as $p) {
        if (count($out) >= $limit) {
            break;
        }
        $out[] = $p;
    }
    if (count($out) < $limit && $quiet !== []) {
        $one = $quiet[0];
        $one['deemphasize'] = true;
        $out[] = $one;
    }

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

function mh_is_devto_post(int $postId): bool
{
    if ($postId < 1) {
        return false;
    }

    if ((string) get_post_meta($postId, '_mh_devto_id', true) !== '') {
        return true;
    }

    $catId = mh_devto_category_id();

    return ($catId > 0 && has_category($catId, $postId)) || has_category('dev-to', $postId);
}

function mh_journal_featured_post_id(): int
{
    foreach (mh_home_journal_posts(5) as $p) {
        if (! empty($p['deemphasize'])) {
            continue;
        }
        $id = (int) ($p['id'] ?? 0);
        if ($id > 0) {
            return $id;
        }
    }

    $exclude = [];
    $catId = mh_devto_category_id();
    if ($catId > 0) {
        $exclude[] = $catId;
    }

    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 1,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'category__not_in' => $exclude,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => '_mh_devto_id',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => '_mh_devto_id',
                'value' => '',
                'compare' => '=',
            ],
        ],
    ]);

    $id = isset($q->posts[0]) ? (int) $q->posts[0]->ID : 0;
    wp_reset_postdata();

    return $id;
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
            'title' => mh_post_title($p),
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
 * Markup for the closed-by-default What changed separator.
 */
function mh_what_changed_details(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    return '<details class="post-what-changed">'
        .'<summary class="post-what-changed__summary">'
        .'<span class="post-what-changed__label">'.esc_html__('What changed', 'sage').'</span>'
        .'<span class="post-what-changed__hint">'.esc_html__('Revision notes', 'sage').'</span>'
        .'</summary>'
        .'<div class="post-what-changed__body">'.$body.'</div>'
        .'</details>';
}

/**
 * Turn “What changed” notes into a closed-by-default details separator.
 *
 * Authors can:
 * - add CSS class `what-changed` on a blockquote,
 * - start a blockquote with “What changed”, or
 * - use a paragraph “What changed:” followed by a list (common Gutenberg pattern).
 *
 * Already-wrapped `<details class="post-what-changed">` markup is left alone.
 */
function mh_enhance_what_changed(string $html): string
{
    if ($html === '' || ! str_contains(strtolower($html), 'what changed')) {
        return $html;
    }

    // Paragraph label + following list (live journal posts often use this).
    $html = (string) preg_replace_callback(
        '/<p(\s[^>]*)?>\s*(?:<strong>)?\s*what\s+changed\s*:?\s*(?:<\/strong>)?\s*<\/p>\s*(<(?:ul|ol)\b[^>]*>.*?<\/(?:ul|ol)>)/is',
        static function (array $m): string {
            return mh_what_changed_details($m[2]);
        },
        $html
    );

    // Blockquotes with class or leading “What changed”.
    return (string) preg_replace_callback(
        '/<blockquote(\s[^>]*)?>(.*?)<\/blockquote>/is',
        static function (array $m): string {
            $attrs = $m[1] ?? '';
            $inner = $m[2] ?? '';
            $plain = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(preg_replace('/<[^>]+>/', ' ', $inner) ?? $inner)) ?? '');
            $hasClass = (bool) preg_match('/\bclass=(["\'])([^"\']*)\1/i', $attrs, $cm)
                && preg_match('/(?:^|\s)what-changed(?:\s|$)/i', $cm[2] ?? '');
            $startsWithLabel = (bool) preg_match('/^what\s+changed\b/i', $plain)
                || (bool) preg_match('/<(?:p|h[2-4]|strong)(\s[^>]*)?>\s*(?:<strong>)?\s*what\s+changed\b/i', $inner);

            if (! $hasClass && ! $startsWithLabel) {
                return $m[0];
            }

            $body = $inner;
            $body = (string) preg_replace(
                '/^\s*<(p|h[2-4]|strong)(\s[^>]*)?>\s*(?:<strong>)?\s*what\s+changed\s*(?:<\/strong>)?\s*:?\s*<\/\1>\s*/is',
                '',
                $body,
                1
            );
            if ($body === $inner) {
                $body = (string) preg_replace(
                    '/^\s*(?:<strong>)?\s*what\s+changed\s*(?:<\/strong>)?\s*:?\s*/is',
                    '',
                    $body,
                    1
                );
            }
            $body = trim($body);
            if ($body === '') {
                $body = $inner;
            }

            return mh_what_changed_details($body);
        },
        $html
    );
}

/**
 * Add heading ids and return [html, toc[]].
 *
 * @return array{0: string, 1: array<int, array{level: int, id: string, text: string}>}
 */
function mh_content_with_toc(string $html): array
{
    $html = mh_enhance_what_changed($html);
    $toc = [];
    $used = [];
    $html = (string) preg_replace_callback(
        '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
        static function ($m) use (&$toc, &$used) {
            $level = (int) $m[1];
            $attrs = $m[2] ?? '';
            $inner = $m[3];
            $text = trim(html_entity_decode(wp_strip_all_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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
        'resources' => ['Resources', 'template-resources.blade.php'],
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
            'detail' => __('Full-stack web development with deep WordPress and PHP experience. Open to on-site, hybrid, or remote.', 'sage'),
        ],
        [
            'title' => __('Contract and freelance', 'sage'),
            'detail' => __('Project-based work with a clear, written scope for shops and agencies.', 'sage'),
        ],
        [
            'title' => __('Agency sub-contracting', 'sage'),
            'detail' => __('A handful of silent-sub jobs. You keep the relationship. I build the WordPress platform, integration, or web application.', 'sage'),
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
            'title' => __('You own the stack at handoff', 'sage'),
            'body' => __('Hosting, DNS, the database, and the Git repo sit in accounts under your name before we close. The Sage theme, plugins, and deploy notes go with the site — so another developer can pick up without guessing.', 'sage'),
        ],
        [
            'icon' => 'users',
            'title' => __('wp-admin is part of the architecture', 'sage'),
            'body' => __('I build with Sage 11, Blade, Tailwind, and page fields shops edit in WordPress — not a page builder. If an owner cannot update hours or a product in a couple of minutes, the theme is not done.', 'sage'),
        ],
        [
            'icon' => 'code',
            'title' => __('AI drafts. I ship the review', 'sage'),
            'body' => __('Cursor, Claude, and ChatGPT help with scaffolding and first-pass PHP. I still read, test, and own every line that reaches production — Vite builds, PHP 8.3, and GitHub Actions included.', 'sage'),
        ],
        [
            'icon' => 'plugins',
            'title' => __('Small plugins. Clear hooks', 'sage'),
            'body' => __('Custom work lives in focused PHP plugins or theme modules with standard WordPress hooks, PHPDoc, and a clean uninstall path. Kitchen-sink plugins stay off the site.', 'sage'),
        ],
        [
            'icon' => 'book-open',
            'title' => __('Readable PHP for the next developer', 'sage'),
            'body' => __('Blade stays thin; logic lives in App helpers with typed functions and explicit names. If another developer cannot follow a function in half a minute, I rewrite it before handoff.', 'sage'),
        ],
        [
            'icon' => 'pen',
            'title' => __('Accessible markup and plain words', 'sage'),
            'body' => __('Semantic HTML, keyboard paths, and solid contrast. Labels, errors, and handoff notes read like they were written for a busy shop owner — welcoming to developers, clear for everyone else.', 'sage'),
        ],
    ];
}
