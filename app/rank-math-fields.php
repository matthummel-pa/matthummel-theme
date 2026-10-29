<?php

/**
 * Page SEO helpers left from the Rank Math setup.
 *
 * - Focus keyword defaults per page template.
 * - One-time repair of field values that were saved with their label prefixed.
 * - Rank Math only: drops page tests that cannot apply and feeds the page
 *   fields to its analyzer. Both are no-ops when Rank Math is not active.
 *
 * The code that wrote keyword-padded analysis HTML into post_content was
 * switched off in 3.6.41 and removed in 3.6.62.
 */

namespace App;

/**
 * Focus keyword defaults when Rank Math meta is empty.
 *
 * @return array<string, string> Template file => primary focus keyword.
 */
function mh_page_focus_keyword_defaults(): array
{
    return [
        'front-page.blade.php' => 'WordPress developer',
        'template-home.blade.php' => 'WordPress developer',
        'template-about.blade.php' => 'WordPress developer',
        'template-services.blade.php' => 'Acreline services',
        'template-portfolio.blade.php' => 'WordPress developer portfolio',
        'template-projects.blade.php' => 'WordPress projects',
        'template-contact.blade.php' => 'contact a WordPress developer',
        'template-start.blade.php' => 'WordPress project brief',
        'template-resources.blade.php' => 'WordPress starters',
        'index.blade.php' => 'WordPress development',
    ];
}

/**
 * Primary focus keyword for a page (Rank Math meta, then template default).
 */
function mh_page_focus_keyword(int $post_id): string
{
    $raw = get_post_meta($post_id, 'rank_math_focus_keyword', true);
    if (is_string($raw) && trim($raw) !== '') {
        $parts = array_map('trim', explode(',', $raw));

        return $parts[0] !== '' ? $parts[0] : '';
    }

    $key = mh_effective_field_map_key($post_id);
    if ((int) get_option('page_for_posts') === $post_id) {
        $key = 'index.blade.php';
    }
    if ((int) get_option('page_on_front') === $post_id) {
        $key = 'front-page.blade.php';
    }

    $defaults = mh_page_focus_keyword_defaults();

    return $defaults[$key] ?? 'WordPress';
}

/**
 * Resolve the effective page_field_map key for a post (its page template).
 */
function mh_effective_field_map_key(int $post_id): string
{
    return page_template_key($post_id);
}

/**
 * Strip a saved field label that was accidentally prepended to the value.
 */
function mh_strip_leading_field_label(string $value, string $label): string
{
    $value = (string) $value;
    $label = trim(wp_strip_all_tags($label));
    if ($label === '' || $value === '') {
        return $value;
    }

    if (str_starts_with($value, $label)) {
        return ltrim(substr($value, strlen($label)));
    }

    // Common corruptions from stacking adjacent labels.
    $aliases = [
        'Document title (under 60 characters)',
        'Meta description (under 155 characters)',
        'Heading',
        'Intro',
        'Intro (basic HTML ok)',
        'Kicker (above name)',
        'Section label',
    ];
    foreach ($aliases as $alias) {
        if (str_starts_with($value, $alias)) {
            $value = ltrim(substr($value, strlen($alias)));
        }
    }

    return $value;
}

/**
 * One-time repair of label-prefixed page field meta.
 */
function mh_repair_page_field_label_prefixes(): void
{
    if (get_option('mh_repaired_field_labels_v1')) {
        return;
    }

    $map = page_field_map();
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        $id = (int) $id;
        $key = page_template_key($id);
        if (empty($map[$key])) {
            continue;
        }
        foreach ($map[$key] as $fields) {
            foreach ($fields as $f) {
                $name = 'mh_f_'.$f[0];
                $type = $f[2];
                if (in_array($type, ['repeater', 'lines'], true)) {
                    continue;
                }
                $raw = get_post_meta($id, $name, true);
                if (! is_string($raw) || $raw === '') {
                    continue;
                }
                $clean = mh_strip_leading_field_label($raw, (string) $f[1]);
                if ($clean !== $raw) {
                    if ($clean === '') {
                        delete_post_meta($id, $name);
                    } else {
                        update_post_meta($id, $name, $clean);
                    }
                }
            }
        }
    }

    update_option('mh_repaired_field_labels_v1', true, false);
}

add_action('init', __NAMESPACE__.'\\mh_repair_page_field_label_prefixes', 48);

/**
 * Drop Content AI test on pages — marketing layouts are not AI drafts.
 *
 * @param  array<string, bool>  $tests
 * @return array<string, bool>
 */
function mh_rank_math_page_tests(array $tests, string $type): array
{
    if ($type === 'page') {
        // Marketing pages use short skill slugs (/about/, /code/) that cannot
        // hold a full focus phrase; Content AI and title-number tests are vanity.
        unset($tests['hasContentAI'], $tests['keywordInPermalink'], $tests['titleHasNumber']);
    }

    return $tests;
}

add_filter('rank_math/researches/tests', __NAMESPACE__.'\\mh_rank_math_page_tests', 10, 2);

/**
 * Enqueue Rank Math Content Analysis API integration for page fields.
 */
function mh_enqueue_rank_math_page_fields(string $hook): void
{
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'page') {
        return;
    }
    if (! defined('RANK_MATH_VERSION')) {
        return;
    }

    $rel = 'resources/js/admin-rank-math-fields.js';
    $path = get_theme_file_path($rel);
    $deps = ['wp-hooks', 'jquery'];
    if (wp_script_is('rank-math-analyzer', 'registered')) {
        $deps[] = 'rank-math-analyzer';
    }

    wp_enqueue_script(
        'mh-admin-rank-math-fields',
        get_theme_file_uri($rel),
        $deps,
        file_exists($path) ? (string) filemtime($path) : '1',
        true
    );
}

add_action('admin_enqueue_scripts', __NAMESPACE__.'\\mh_enqueue_rank_math_page_fields', 20);
