<?php

/**
 * On-site project pages for the Projects CPT (/projects/{slug}/).
 */

namespace App;

// Product shop helpers + ThemeForest sidebar (loaded with project pages).
if (is_readable($mhShop = get_theme_file_path('app/shop.php'))) {
    require_once $mhShop;
}
if (is_readable($mhSidebar = get_theme_file_path('app/concept-sidebar.php'))) {
    require_once $mhSidebar;
}

/**
 * Path to bundled project narrative JSON (keyed by project slug).
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

/**
 * Public rewrite base for project singles.
 *
 * Shares the /projects/ prefix with the Work listing page. CPT has_archive is
 * false so the page owns /projects/ and singles own /projects/{slug}/.
 */
function mh_concept_rewrite_slug(): string
{
    return 'projects';
}

/**
 * Absolute URL for a project page on this site.
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
    $isProduct = is_array($product);
    if ($isProduct && $productType === 'concept') {
        $productType = 'theme';
    }

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
 * Public eyebrow with leftover “concept” wording swapped for “project”.
 */
function mh_project_display_eyebrow(string $eyebrow): string
{
    $eyebrow = mh_project_public_prose(trim($eyebrow));

    return $eyebrow !== '' ? $eyebrow : __('Project', 'sage');
}

/**
 * Swap leftover “concept” wording in stored project prose.
 */
function mh_project_public_prose(string $text): string
{
    if ($text === '') {
        return '';
    }

    $replaced = preg_replace('/\bConcepts\b/', 'Projects', $text);
    $replaced = preg_replace('/\bconcepts\b/', 'projects', is_string($replaced) ? $replaced : $text);
    $replaced = preg_replace('/\bConcept\b/', 'Project', is_string($replaced) ? $replaced : $text);
    $replaced = preg_replace('/\bconcept\b/', 'project', is_string($replaced) ? $replaced : $text);

    return is_string($replaced) ? $replaced : $text;
}

/**
 * Extra buyer-facing sections for a project page.
 *
 * Stored meta wins. Category defaults fill empty keys so every public
 * project page has architecture, handoff, and FAQ copy.
 *
 * @param  array<string, mixed>  $card
 * @return array{
 *   audience: string,
 *   architecture: string,
 *   handoff: string,
 *   specs: list<array{0: string, 1: string}>,
 *   faq: list<array{q: string, a: string}>
 * }
 */
function mh_project_buyer_docs(int $post_id, array $card): array
{
    $cat = (string) ($card['cat'] ?? '');
    $slug = (string) ($card['slug'] ?? '');
    $title = (string) ($card['title'] ?? '');
    $place = (string) ($card['place'] ?? '');
    $tech = $card['tech'] ?? [];
    if (! is_array($tech)) {
        $tech = [];
    }
    $tech = array_values(array_filter(array_map('strval', $tech)));
    $defaults = mh_project_buyer_defaults($slug, $cat, $title);

    $audience = mh_project_public_prose($post_id > 0 ? trim((string) get_post_meta($post_id, '_mh_project_audience', true)) : '');
    $architecture = mh_project_public_prose($post_id > 0 ? trim((string) get_post_meta($post_id, '_mh_project_architecture', true)) : '');
    $handoff = mh_project_public_prose($post_id > 0 ? trim((string) get_post_meta($post_id, '_mh_project_handoff', true)) : '');

    $demo = mh_project_demo_url($post_id);
    if ($demo === '') {
        $demo = (string) ($card['demo'] ?? '');
    }

    $specs = [];
    if ($cat !== '') {
        $specs[] = [__('Type', 'sage'), $cat];
    }
    if ($place !== '') {
        $specs[] = [__('Place', 'sage'), $place];
    }
    $specs[] = [__('CMS', 'sage'), 'WordPress'];
    $theme = 'Custom WordPress theme';
    foreach ($tech as $item) {
        if (strcasecmp($item, 'Sage') === 0) {
            $theme = 'Sage 11';
            break;
        }
    }
    $specs[] = [__('Theme', 'sage'), $theme];
    if ($tech !== []) {
        $specs[] = [__('Stack', 'sage'), implode(' · ', $tech)];
    }
    if ($demo !== '') {
        $specs[] = [__('Live demo', 'sage'), $demo];
    }

    return [
        'audience' => $audience !== '' ? $audience : $defaults['audience'],
        'architecture' => $architecture !== '' ? $architecture : $defaults['architecture'],
        'handoff' => $handoff !== '' ? $handoff : $defaults['handoff'],
        'specs' => $specs,
        'faq' => $defaults['faq'],
    ];
}

/**
 * Split stored or default prose on blank lines for template paragraphs.
 *
 * @return list<string>
 */
function mh_project_prose_paragraphs(string $text): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }

    $parts = preg_split('/\R{2,}/', $text) ?: [];

    return array_values(array_filter(array_map('trim', $parts)));
}

/**
 * Category (and slug) defaults for buyer documentation.
 *
 * @return array{audience: string, architecture: string, handoff: string, faq: list<array{q: string, a: string}>}
 */
function mh_project_buyer_defaults(string $slug, string $cat, string $title): array
{
    $key = strtolower($cat);
    $name = $title !== '' ? $title : __('this project', 'sage');

    $audience = __('Shops and agencies who want a WordPress site they can edit — not a page-builder mockup. The layout is a starting point; I map it onto your pages, fields, and hosting.', 'sage');
    $architecture = __('Built as a Sage 11 WordPress theme: Blade templates, Tailwind v4, Vite for CSS and JS. Visitor copy lives in wp-admin fields, not hardcoded templates. No page builder, no extra options screen.', 'sage');
    $handoff = __('You own the theme, the fields, and the repo. I document what to edit in wp-admin so another developer can extend it without reverse-engineering CSS.', 'sage');

    if (str_contains($key, 'real')) {
        $audience = __('Brokerages, land offices, and independent agents who need listings that are not a generic home-search plugin. The same pattern fits farms, acreage, and historic inventory in Gettysburg — and listings anywhere you already run them.', 'sage');
        $architecture = __('Listings are a queryable collection: grid plus map, with filters as URL query args (acreage, township, lot type). Financing widgets are vanilla JavaScript, so they run without a page builder or shortcode pack. The WordPress handoff is a Sage 11 theme. Listings can be a custom post type or an imported feed — the UI does not care as long as the fields exist.', 'sage');
        $handoff = __('You own the theme, the listing fields, and the repo. Listing copy, photos, and filters edit in wp-admin. I document the listing schema so another developer can add a filter without touching the template. Rank Math or Yoast owns titles and schema.', 'sage');
    } elseif (str_contains($key, 'tour')) {
        $audience = __('Licensed guides, tour operators, and visitor-facing shops that sell dated tickets instead of a static “call us” page. Built for Gettysburg walking, bus, and lantern tours; the booking pattern ports to other destinations.', 'sage');
        $architecture = __('Tours are structured content (not a pile of pages): catalog, filters, and a booking path that can later sit on WooCommerce or a booking plugin. Maps, when present, are a real map library rather than a screenshot. Sage 11 is the WordPress handoff so the same Blade templates keep working after launch.', 'sage');
        $handoff = __('You get tour fields, the catalog, and the booking labels in wp-admin. Adding a tour should not require a developer. I leave notes for anything that is not obvious, plus the repo if you want another developer on the theme.', 'sage');
    } elseif (str_contains($key, 'retail') || str_contains($key, 'shop')) {
        $audience = __('Independent shops that need a catalog, cart, and checkout they can actually run — not a brochure with a Shop button that goes nowhere.', 'sage');
        $architecture = __('Catalog, filters, and cart are designed as WooCommerce surfaces. Category and product copy stay in WordPress. The theme is Sage 11 so product templates stay in Git, not in a page builder. Wishlist or filter JS stays small and explicit.', 'sage');
        $handoff = __('Products, prices, and photos edit in WooCommerce. I document the product fields and any custom taxonomies. You own the theme zip and can point another developer at the repo.', 'sage');
    } elseif (str_contains($key, 'restaurant') || str_contains($key, 'cafe') || str_contains($key, 'food')) {
        $audience = __('Restaurants, taverns, and cafes that need a menu people can scan on a phone, plus a clear path to order or book a table.', 'sage');
        $architecture = __('The menu is structured content (sections, items, dietary flags) so it can filter without a PDF. Ordering or reservations hook to a form or a specialist plugin — not a third-party iframe that owns your SEO. Sage 11 keeps the templates in version control.', 'sage');
        $handoff = __('Menu items, hours, and photos edit in wp-admin. I document the menu fields so a manager can add a special without calling me. You own the theme and the content.', 'sage');
    } elseif (str_contains($key, 'hotel') || str_contains($key, 'inn') || str_contains($key, 'b&b') || str_contains($key, 'bed')) {
        $audience = __('Inns, B&Bs, and small hotels that want direct bookings instead of sending every stay to a third-party listing site.', 'sage');
        $architecture = __('Rooms are structured content: photos, amenities, and a booking path that can sit on a booking plugin or a simple request form. The marketing pages stay in WordPress so SEO is yours. Sage 11 is the theme layer.', 'sage');
        $handoff = __('Room copy, photos, and rates edit in wp-admin. I document the room fields and the booking handoff. You own the theme; another developer can extend availability without rebuilding the front end.', 'sage');
    }

    if (in_array($slug, ['keystone-homes', 'acreline'], true)) {
        $audience = __('Land offices, farm brokerages, and agents selling acreage or historic homes who cannot use a suburban MLS skin. Acreline is the WordPress starting point: listings, map, and financing tools aimed at that inventory.', 'sage');
        $architecture = __('Listings render as a grid and a map, with acreage, township, and lot-type filters as query args. The land-loan estimate and pre-qualification widgets are vanilla JS. Intended production stack is Sage 11, a listing CPT or imported feed, and Rank Math for titles and schema. No page builder, no IDX chrome unless we wire a real feed later.', 'sage');
        $handoff = __('You own the theme and the listing fields. Brokers edit parcels, photos, and copy in wp-admin. I document the listing schema (filters, map pin fields, financing copy) so the next developer can extend it. Hire me to map this onto your inventory, or start from the public demo and tell me what to change.', 'sage');
    }

    $faq = [
        [
            'q' => sprintf(__('Can I start from %s?', 'sage'), $name),
            'a' => __('Yes. Say which project and what you would change. I map the layout onto your WordPress site — pages you can edit, a theme you own, no page builder.', 'sage'),
        ],
        [
            'q' => __('What do I edit in WordPress after launch?', 'sage'),
            'a' => __('Copy, photos, hours, and listings live in wp-admin. I add fields for anything that needs to change on a regular basis, and I write a short note for the rest.', 'sage'),
        ],
        [
            'q' => __('Do I get the source theme?', 'sage'),
            'a' => __('Yes. A production build is a Sage theme you own, plus the field map. Public demos stay examples; the paid build is yours to host and extend.', 'sage'),
        ],
        [
            'q' => __('Is this a live client site?', 'sage'),
            'a' => __('No. It is an example project on matthummel.com. Hire me to adapt it for your shop, or use it as the brief for an agency overflow job.', 'sage'),
        ],
    ];

    return [
        'audience' => $audience,
        'architecture' => $architecture,
        'handoff' => $handoff,
        'faq' => $faq,
    ];
}

/**
 * Related live projects in the same category (excludes current).
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
        '_mh_project_audience' => (string) ($seed['audience'] ?? ''),
        '_mh_project_architecture' => (string) ($seed['architecture'] ?? ''),
        '_mh_project_handoff' => (string) ($seed['handoff'] ?? ''),
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
    update_option('mh_project_rewrite_flushed_v2', false);
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

/** Flush rewrite rules once after the public slug becomes /projects/{slug}/. */
function mh_maybe_flush_concept_rewrites(): void
{
    if (get_option('mh_project_rewrite_flushed_v2')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('mh_project_rewrite_flushed_v2', true);
}

/**
 * 301 /concept/ and /concept/{slug}/ to /projects/ and /projects/{slug}/.
 */
function mh_redirect_legacy_concept_urls(): void
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $requestPath = trim((string) (parse_url($uri, PHP_URL_PATH) ?? ''), '/');
    $homePath = trim((string) (parse_url(home_url('/'), PHP_URL_PATH) ?? ''), '/');
    if ($homePath !== '') {
        if ($requestPath === $homePath || ! str_starts_with($requestPath, $homePath.'/')) {
            return;
        }
        $requestPath = substr($requestPath, strlen($homePath) + 1);
    }

    if ($requestPath !== 'concept' && ! str_starts_with($requestPath, 'concept/')) {
        return;
    }

    $rest = $requestPath === 'concept' ? '' : substr($requestPath, strlen('concept/'));
    $target = $rest === ''
        ? home_url('/projects/')
        : home_url('/projects/'.$rest.'/');

    $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
    if ($query !== '') {
        $target .= (str_contains($target, '?') ? '&' : '?').$query;
    }

    wp_safe_redirect($target, 301);
    exit;
}

/** Hide non-live project pages from the public site (editors can still preview). */
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
add_action('template_redirect', __NAMESPACE__.'\\mh_redirect_legacy_concept_urls', 0);
add_action('template_redirect', __NAMESPACE__.'\\mh_gate_concept_page_access');
