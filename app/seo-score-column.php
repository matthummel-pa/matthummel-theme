<?php

/**
 * MH SEO score column on the Posts, Pages, and other public list screens.
 *
 * Reads the score the MH SEO plugin saves to `_mh_seo_score` (0–100) and shows
 * it as a green / yellow / red badge, like Rank Math. Read-only: nothing here
 * calculates or writes scores. Loads only when MH SEO is active.
 */

namespace App;

const MH_SEO_SCORE_META = '_mh_seo_score';
const MH_SEO_SCORE_COLUMN = 'mh_seo_score';

/** Whether the MH SEO plugin is active (it defines MH_SEO_ACTIVE). */
function mh_seo_score_column_enabled(): bool
{
    return (bool) apply_filters('mh/seo_score_column', defined('MH_SEO_ACTIVE') && MH_SEO_ACTIVE);
}

/**
 * Post types that get the column: public types with an admin list screen.
 *
 * @return list<string>
 */
function mh_seo_score_post_types(): array
{
    $types = get_post_types(['public' => true, 'show_ui' => true]);
    unset($types['attachment']);

    return array_values((array) apply_filters('mh/seo_score_post_types', array_values($types)));
}

/**
 * Score band for a 0–100 score, using Rank Math's cut-offs.
 *
 * @return array{key: string, label: string}
 */
function mh_seo_score_band(int $score): array
{
    return match (true) {
        $score >= 80 => ['key' => 'good', 'label' => __('Good', 'sage')],
        $score >= 50 => ['key' => 'ok', 'label' => __('Needs work', 'sage')],
        default => ['key' => 'bad', 'label' => __('Poor', 'sage')],
    };
}

add_action('admin_init', function (): void {
    if (! mh_seo_score_column_enabled()) {
        return;
    }

    foreach (mh_seo_score_post_types() as $type) {
        add_filter("manage_{$type}_posts_columns", __NAMESPACE__.'\\mh_seo_score_add_column');
        add_action("manage_{$type}_posts_custom_column", __NAMESPACE__.'\\mh_seo_score_render_column', 10, 2);
        add_filter("manage_edit-{$type}_sortable_columns", __NAMESPACE__.'\\mh_seo_score_sortable');
    }
});

/**
 * Insert the SEO column right after the title.
 *
 * @param  array<string, string>  $columns
 * @return array<string, string>
 */
function mh_seo_score_add_column(array $columns): array
{
    $out = [];
    foreach ($columns as $key => $label) {
        $out[$key] = $label;
        if ($key === 'title') {
            $out[MH_SEO_SCORE_COLUMN] = __('SEO', 'sage');
        }
    }
    if (! isset($out[MH_SEO_SCORE_COLUMN])) {
        $out[MH_SEO_SCORE_COLUMN] = __('SEO', 'sage');
    }

    return $out;
}

/**
 * Print the score badge and focus keyword for one row.
 */
function mh_seo_score_render_column(string $column, int $post_id): void
{
    if ($column !== MH_SEO_SCORE_COLUMN) {
        return;
    }

    $raw = get_post_meta($post_id, MH_SEO_SCORE_META, true);
    $keyword = trim((string) get_post_meta($post_id, '_mh_seo_focus_keyword', true));
    if ($keyword === '') {
        $keyword = trim((string) get_post_meta($post_id, 'rank_math_focus_keyword', true));
    }

    if ($raw === '' || ! is_numeric($raw)) {
        printf(
            '<span class="mh-seo-score mh-seo-score--none" title="%1$s">%2$s</span>',
            esc_attr__('Not scored yet. Open and update the post to score it.', 'sage'),
            esc_html__('N/A', 'sage')
        );
    } else {
        $score = max(0, min(100, (int) $raw));
        $band = mh_seo_score_band($score);
        printf(
            '<span class="mh-seo-score mh-seo-score--%1$s" title="%2$s">%3$s<span class="mh-seo-score__max"> / 100</span></span>',
            esc_attr($band['key']),
            /* translators: 1: score band label (Good, Needs work, Poor), 2: score out of 100. */
            esc_attr(sprintf(__('SEO score: %1$s (%2$s/100)', 'sage'), $band['label'], number_format_i18n($score))),
            esc_html(number_format_i18n($score))
        );
    }

    if ($keyword !== '') {
        printf(
            '<span class="mh-seo-score__kw"><span class="screen-reader-text">%1$s </span>%2$s</span>',
            esc_html__('Focus keyword:', 'sage'),
            esc_html($keyword)
        );
    }
}

/**
 * @param  array<string, string>  $columns
 * @return array<string, string|array<int, string|bool>>
 */
function mh_seo_score_sortable(array $columns): array
{
    $columns[MH_SEO_SCORE_COLUMN] = [MH_SEO_SCORE_COLUMN, true];

    return $columns;
}

/**
 * Sort by score without dropping posts that have no score (they sort as lowest).
 */
add_action('pre_get_posts', function (\WP_Query $query): void {
    if (! is_admin() || ! $query->is_main_query() || $query->get('orderby') !== MH_SEO_SCORE_COLUMN || ! mh_seo_score_column_enabled()) {
        return;
    }

    $query->set('meta_query', [
        'relation' => 'OR',
        'mh_seo_score_clause' => ['key' => MH_SEO_SCORE_META, 'compare' => 'EXISTS', 'type' => 'NUMERIC'],
        ['key' => MH_SEO_SCORE_META, 'compare' => 'NOT EXISTS'],
    ]);
    $query->set('orderby', ['mh_seo_score_clause' => strtoupper((string) $query->get('order')) === 'ASC' ? 'ASC' : 'DESC', 'date' => 'DESC']);
});

add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook !== 'edit.php' || ! mh_seo_score_column_enabled()) {
        return;
    }

    wp_register_style('mh-seo-score-column', false, [], '1.0.0');
    wp_enqueue_style('mh-seo-score-column');
    wp_add_inline_style('mh-seo-score-column', '
        .column-mh_seo_score { width: 9.5em; }
        .mh-seo-score { display: inline-flex; align-items: baseline; gap: 1px; padding: 3px 9px; border-radius: 999px; font-weight: 600; font-size: 12px; line-height: 1.5; white-space: nowrap; color: #fff; }
        .mh-seo-score__max { font-weight: 400; opacity: .85; font-size: 11px; }
        .mh-seo-score--good { background: #15803d; }
        .mh-seo-score--ok { background: #f59e0b; color: #1f2937; }
        .mh-seo-score--bad { background: #dc2626; }
        .mh-seo-score--none { background: #f0f0f1; color: #50575e; border: 1px solid #dcdcde; }
        .mh-seo-score__kw { display: block; margin-top: 4px; max-width: 12em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #646970; font-size: 12px; }
    ');
});
