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
 * Split admin list meta into clean lines (newlines preferred; pipes as fallback).
 *
 * @return list<string>
 */
function mh_project_list_lines(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts)));

    // Pipe-flattened admin/CLI values (avoid splitting FAQ "|||" rows).
    if (count($parts) === 1 && str_contains($parts[0], '|') && ! str_contains($parts[0], '|||')) {
        $parts = array_values(array_filter(array_map('trim', explode('|', $parts[0]))));
    }

    return $parts;
}

/**
 * Product type for a project landing: concept | theme | plugin.
 */
function mh_project_product_type(int $post_id): string
{
    $type = sanitize_key((string) get_post_meta($post_id, '_mh_project_product_type', true));
    if (! in_array($type, ['concept', 'theme', 'plugin'], true)) {
        return 'concept';
    }

    return $type;
}

/**
 * Linked WooCommerce product ID (0 when unset).
 */
function mh_project_product_id(int $post_id): int
{
    return max(0, (int) get_post_meta($post_id, '_mh_project_product_id', true));
}

/**
 * Whether this project should render as a sellable product landing.
 */
function mh_project_is_product_landing(int $post_id): bool
{
    $type = mh_project_product_type($post_id);
    if (! in_array($type, ['theme', 'plugin'], true)) {
        return false;
    }

    return mh_project_product_id($post_id) > 0 && mh_shop_ready();
}

/**
 * FAQ pairs from `Question|||Answer` lines.
 *
 * @return list<array{0: string, 1: string}>
 */
function mh_project_faq_pairs(int $post_id): array
{
    $raw = trim((string) get_post_meta($post_id, '_mh_project_faq', true));
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
        [$q, $a] = array_map('trim', explode('|||', $line, 2));
        if ($q === '' || $a === '') {
            continue;
        }
        $pairs[] = [$q, $a];
    }

    return $pairs;
}

/**
 * Narrative fields for a concept / product page.
 *
 * @return array{
 *   eyebrow: string,
 *   summary: string,
 *   challenge: string,
 *   approach: string,
 *   result: string,
 *   deliverables: list<string>,
 *   benefits: list<string>,
 *   faq: list<array{0: string, 1: string}>,
 *   metrics: list<array{0: string, 1: string}>,
 *   demo: string,
 *   product_type: string,
 *   is_product: bool,
 *   product: array<string, mixed>|null
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
        'benefits' => [],
        'faq' => [],
        'metrics' => [],
        'demo' => '',
        'product_type' => 'concept',
        'is_product' => false,
        'product' => null,
    ];

    if ($post_id <= 0) {
        return $defaults;
    }

    $deliverables = mh_project_list_lines((string) get_post_meta($post_id, '_mh_project_deliverables', true));
    $benefits = mh_project_list_lines((string) get_post_meta($post_id, '_mh_project_benefits', true));

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

    $productType = mh_project_product_type($post_id);
    $productId = mh_project_product_id($post_id);
    $product = $productId > 0 ? mh_shop_product_payload($productId) : null;
    $isProduct = in_array($productType, ['theme', 'plugin'], true) && is_array($product);

    return [
        'eyebrow' => trim((string) get_post_meta($post_id, '_mh_project_eyebrow', true)),
        'summary' => $summary,
        'challenge' => trim((string) get_post_meta($post_id, '_mh_project_challenge', true)),
        'approach' => trim((string) get_post_meta($post_id, '_mh_project_approach', true)),
        'result' => trim((string) get_post_meta($post_id, '_mh_project_result', true)),
        'deliverables' => $deliverables,
        'benefits' => $benefits,
        'faq' => mh_project_faq_pairs($post_id),
        'metrics' => $metrics,
        'demo' => mh_project_demo_url($post_id),
        'product_type' => $productType,
        'is_product' => $isProduct,
        'product' => $product,
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
    mh_import_concept_fields_from_seed(false);

    update_option('mh_concept_pages_seeded_v1', true);
    update_option('mh_concept_rewrite_flushed_v1', false);
}

/**
 * Import concept-page custom fields from bundled JSON onto matching project posts.
 *
 * @return int Number of projects touched.
 */
function mh_import_concept_fields_from_seed(bool $force = false): int
{
    $seeds = mh_concept_pages_seed_data();
    $count = 0;

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
        mh_seed_project_concept_narrative((int) $posts[0], $seed, $force);
        $count++;
    }

    return $count;
}

/** Fill any still-empty concept fields (eyebrow, metrics, narrative) from JSON. */
function mh_seed_concept_fields_admin_v1(): void
{
    if (get_option('mh_concept_fields_admin_v1') || wp_installing()) {
        return;
    }

    mh_import_concept_fields_from_seed(false);
    update_option('mh_concept_fields_admin_v1', true);
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
add_action('init', __NAMESPACE__.'\\mh_seed_concept_fields_admin_v1', 36);
add_action('init', __NAMESPACE__.'\\mh_maybe_flush_concept_rewrites', 99);
add_action('template_redirect', __NAMESPACE__.'\\mh_gate_concept_page_access');
