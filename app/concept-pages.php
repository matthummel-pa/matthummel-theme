<?php

/**
 * On-site concept pages for Projects CPT (/concept/{slug}/).
 */

namespace App;

/**
 * Path to bundled concept narrative JSON (keyed by project slug).
 */
function mh_concept_pages_data_path(): string
{
    return get_theme_file_path('resources/data/concept-pages.json');
}

/**
 * @return array<string, array<string, mixed>>
 */
function mh_concept_pages_seed_data(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $path = mh_concept_pages_data_path();
    if (! is_readable($path)) {
        $data = [];

        return $data;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    $data = is_array($decoded) ? $decoded : [];

    return $data;
}

/** Public rewrite base for project singles. */
function mh_concept_rewrite_slug(): string
{
    return 'concept';
}

/**
 * Absolute URL for a project concept page on this site.
 */
function mh_concept_page_url(string $slug = '', ?int $post_id = null): string
{
    if ($post_id && get_post_type($post_id) === mh_project_post_type()) {
        $link = get_permalink($post_id);
        if (is_string($link) && $link !== '') {
            return $link;
        }
    }

    $slug = sanitize_title($slug);
    if ($slug === '') {
        return home_url('/projects/');
    }

    return home_url('/'.mh_concept_rewrite_slug().'/'.$slug.'/');
}

/**
 * Live clickable demo URL (GitHub Pages / Netlify / subdomain), not the R&V case-study URL.
 */
function mh_project_demo_url(int $post_id): string
{
    $demo = trim((string) get_post_meta($post_id, '_mh_project_demo', true));
    if ($demo !== '') {
        return $demo;
    }

    return '';
}

/**
 * Narrative fields for a concept page.
 *
 * @return array{
 *   eyebrow: string,
 *   summary: string,
 *   challenge: string,
 *   approach: string,
 *   result: string,
 *   deliverables: list<string>,
 *   metrics: list<array{0: string, 1: string}>,
 *   demo: string
 * }
 */
function mh_project_concept_narrative(int $post_id): array
{
    $defaults = [
        'eyebrow' => '',
        'summary' => '',
        'challenge' => '',
        'approach' => '',
        'result' => '',
        'deliverables' => [],
        'metrics' => [],
        'demo' => '',
    ];

    if ($post_id <= 0) {
        return $defaults;
    }

    $deliverablesRaw = (string) get_post_meta($post_id, '_mh_project_deliverables', true);
    $deliverables = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $deliverablesRaw) ?: [])));

    $metrics = [];
    for ($i = 1; $i <= 3; $i++) {
        $value = trim((string) get_post_meta($post_id, "_mh_project_m{$i}_value", true));
        $label = trim((string) get_post_meta($post_id, "_mh_project_m{$i}_label", true));
        if ($value === '' && $label === '') {
            continue;
        }
        $metrics[] = [$value, $label];
    }

    $summary = trim((string) get_post_meta($post_id, '_mh_project_summary', true));
    if ($summary === '') {
        $summary = trim((string) get_post_meta($post_id, '_mh_project_blurb', true));
    }

    return [
        'eyebrow' => trim((string) get_post_meta($post_id, '_mh_project_eyebrow', true)),
        'summary' => $summary,
        'challenge' => trim((string) get_post_meta($post_id, '_mh_project_challenge', true)),
        'approach' => trim((string) get_post_meta($post_id, '_mh_project_approach', true)),
        'result' => trim((string) get_post_meta($post_id, '_mh_project_result', true)),
        'deliverables' => $deliverables,
        'metrics' => $metrics,
        'demo' => mh_project_demo_url($post_id),
    ];
}

/**
 * Related live concepts in the same category (excludes current).
 *
 * @return list<array<string, mixed>>
 */
function mh_related_concept_cards(array $project, int $limit = 3): array
{
    $cat = (string) ($project['cat'] ?? '');
    $slug = (string) ($project['slug'] ?? '');
    $all = mh_query_project_cards(['live_only' => true]);
    $same = array_values(array_filter(
        $all,
        fn ($p) => ($p['slug'] ?? '') !== $slug && ($p['cat'] ?? '') === $cat
    ));
    if ($same === []) {
        $same = array_values(array_filter(
            $all,
            fn ($p) => ($p['slug'] ?? '') !== $slug
        ));
    }

    return array_slice($same, 0, max(0, $limit));
}

/**
 * Apply narrative seed data to one project post (does not overwrite non-empty fields).
 */
function mh_seed_project_concept_narrative(int $post_id, array $seed, bool $force = false): void
{
    if ($post_id <= 0 || $seed === []) {
        return;
    }

    $map = [
        '_mh_project_eyebrow' => (string) ($seed['eyebrow'] ?? ''),
        '_mh_project_summary' => (string) ($seed['summary'] ?? ''),
        '_mh_project_challenge' => (string) ($seed['challenge'] ?? ''),
        '_mh_project_approach' => (string) ($seed['approach'] ?? ''),
        '_mh_project_result' => (string) ($seed['result'] ?? ''),
        '_mh_project_demo' => (string) ($seed['demo'] ?? ''),
    ];

    foreach ($map as $key => $value) {
        if ($value === '') {
            continue;
        }
        $existing = (string) get_post_meta($post_id, $key, true);
        if ($force || $existing === '') {
            update_post_meta($post_id, $key, $value);
        }
    }

    $deliverables = $seed['deliverables'] ?? [];
    if (is_array($deliverables) && $deliverables !== []) {
        $text = implode("\n", array_map('strval', $deliverables));
        $existing = (string) get_post_meta($post_id, '_mh_project_deliverables', true);
        if ($force || $existing === '') {
            update_post_meta($post_id, '_mh_project_deliverables', $text);
        }
    }

    $metrics = $seed['metrics'] ?? [];
    if (is_array($metrics)) {
        $i = 1;
        foreach ($metrics as $pair) {
            if ($i > 3 || ! is_array($pair)) {
                break;
            }
            $value = (string) ($pair[0] ?? '');
            $label = (string) ($pair[1] ?? '');
            $vKey = "_mh_project_m{$i}_value";
            $lKey = "_mh_project_m{$i}_label";
            if ($force || (string) get_post_meta($post_id, $vKey, true) === '') {
                update_post_meta($post_id, $vKey, $value);
            }
            if ($force || (string) get_post_meta($post_id, $lKey, true) === '') {
                update_post_meta($post_id, $lKey, $label);
            }
            $i++;
        }
    }
}

/** One-time: enrich CPT projects with on-site concept narrative + flush rewrites. */
function mh_seed_concept_pages_v1(): void
{
    if (get_option('mh_concept_pages_seeded_v1')) {
        return;
    }

    mh_import_studio_projects_to_cpt();

    $seeds = mh_concept_pages_seed_data();
    foreach ($seeds as $slug => $seed) {
        if (! is_array($seed)) {
            continue;
        }
        $posts = get_posts([
            'post_type' => mh_project_post_type(),
            'name' => sanitize_title((string) $slug),
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        if ($posts === []) {
            continue;
        }
        mh_seed_project_concept_narrative((int) $posts[0], $seed, false);
    }

    update_option('mh_concept_pages_seeded_v1', true);
    update_option('mh_concept_rewrite_flushed_v1', false);
}

/** Flush rewrite rules once after making Projects publicly queryable. */
function mh_maybe_flush_concept_rewrites(): void
{
    if (get_option('mh_concept_rewrite_flushed_v1')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('mh_concept_rewrite_flushed_v1', true);
}

/** Hide non-live concept pages from the public site (editors can still preview). */
function mh_gate_concept_page_access(): void
{
    if (is_admin() || ! is_singular(mh_project_post_type())) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ($post_id <= 0) {
        return;
    }

    if (mh_project_is_live($post_id)) {
        return;
    }

    if (current_user_can('edit_post', $post_id)) {
        return;
    }

    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
}

add_action('init', __NAMESPACE__.'\\mh_seed_concept_pages_v1', 35);
add_action('init', __NAMESPACE__.'\\mh_maybe_flush_concept_rewrites', 99);
add_action('template_redirect', __NAMESPACE__.'\\mh_gate_concept_page_access');
