<?php

/**
 * Saves the score and shows it in the posts list.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * The editor is the source of truth. This class fills the score in when a post is saved elsewhere.
 */
class Score
{
    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('save_post', [$this, 'on_save'], 30, 2);
        add_action('pre_get_posts', [$this, 'sort_and_filter']);
        add_action('restrict_manage_posts', [$this, 'filter_dropdown']);
        add_action('admin_enqueue_scripts', [$this, 'styles']);
        foreach (mh_seo_post_types() as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'column']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'column_value'], 10, 2);
            add_filter("manage_edit-{$post_type}_sortable_columns", [$this, 'sortable']);
            add_action("rest_after_insert_{$post_type}", [$this, 'after_rest'], 20, 3);
        }
    }

    /**
     * Recompute when the editor did not send a score.
     *
     * @param  int  $post_id  Post id.
     * @param  \WP_Post  $post  Post.
     */
    public function on_save(int $post_id, \WP_Post $post): void
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        if (! in_array($post->post_type, mh_seo_post_types(), true)) {
            return;
        }
        $this->recompute($post_id);
    }

    /**
     * Recompute after a REST save that did not include the editor score.
     *
     * @param  \WP_Post  $post  Post.
     * @param  \WP_REST_Request  $request  Request.
     * @param  bool  $creating  Whether this insert created the post.
     */
    public function after_rest(\WP_Post $post, $request, bool $creating): void
    {
        unset($creating);
        if (! $request instanceof \WP_REST_Request) {
            $this->recompute((int) $post->ID);

            return;
        }
        $meta = $request->get_param('meta');
        if (is_array($meta) && array_key_exists('_mh_seo_score', $meta)) {
            return;
        }
        $this->recompute((int) $post->ID);
    }

    /**
     * Store a PHP score for one post.
     *
     * @param  int  $post_id  Post id.
     * @return int Score that was stored.
     */
    public function recompute(int $post_id): int
    {
        $post = get_post($post_id);
        if (! $post instanceof \WP_Post) {
            return 0;
        }
        $settings = Settings::get();
        $weights = apply_filters('mh_seo_score_weights', $settings['score_weights']);
        if (! is_array($weights)) {
            $weights = $settings['score_weights'];
        }
        $context = (new Context)->from_post($post);
        $builder = new Document_Builder;
        $keyword = trim((string) get_post_meta($post_id, '_mh_seo_focus_keyword', true));
        if ($keyword === '') {
            $keyword = (new Migrator)->first_keyword((string) get_post_meta($post_id, 'rank_math_focus_keyword', true));
        }
        $content = (string) $post->post_content;
        if (function_exists('do_blocks')) {
            $content = do_blocks($content);
        }
        $result = (new Score_Analyzer)->analyze(
            [
                'seo_title' => (string) ($context['custom_title'] ?? ''),
                'fallback_title' => $builder->title(
                    array_merge($context, ['custom_title' => '']),
                    $settings
                ),
                'description' => $builder->description($context),
                'slug' => (string) $post->post_name,
                'content_html' => $content,
                'keyword' => $keyword,
                'has_featured_image' => has_post_thumbnail($post),
                'supports_featured_image' => post_type_supports($post->post_type, 'thumbnail'),
                'site_host' => (string) wp_parse_url(home_url(), PHP_URL_HOST),
                'unique' => $keyword === '' ? null : $this->is_unique($keyword, $post_id),
            ],
            $weights,
            is_array($settings['thresholds']) ? $settings['thresholds'] : []
        );
        update_post_meta($post_id, '_mh_seo_score', (int) $result['score']);
        update_post_meta($post_id, '_mh_seo_checks', wp_json_encode($result['failed']));
        wp_cache_delete('mh_seo_noindex_ids', 'mh-seo');

        return (int) $result['score'];
    }

    /**
     * Whether the keyword is unused on other published posts, pages, and projects.
     *
     * @param  string  $keyword  Keyword.
     * @param  int  $exclude  Post id to ignore.
     */
    public function is_unique(string $keyword, int $exclude): bool
    {
        $found = $this->conflicts($keyword, $exclude);

        return $found === [];
    }

    /**
     * Other posts that already use this keyword.
     *
     * @param  string  $keyword  Keyword.
     * @param  int  $exclude  Post id to ignore.
     * @return array<int, array{id: int, title: string, link: string}>
     */
    public function conflicts(string $keyword, int $exclude): array
    {
        global $wpdb;
        $keyword = strtolower(trim($keyword));
        if ($keyword === '') {
            return [];
        }
        $types = ['post', 'page', 'project'];
        $in = "'".implode("','", array_map('esc_sql', $types))."'";
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- post types are escaped; table names cannot be placeholders.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key IN ( '_mh_seo_focus_keyword', 'rank_math_focus_keyword' )
				AND p.post_status = 'publish'
				AND p.post_type IN ( {$in} )
				AND p.ID != %d",
                $exclude
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if (! is_array($rows)) {
            return [];
        }
        $conflicts = [];
        $seen = [];
        foreach ($rows as $row) {
            $first = strtolower((new Migrator)->first_keyword((string) $row->meta_value));
            if ($first !== $keyword || isset($seen[(int) $row->ID])) {
                continue;
            }
            $seen[(int) $row->ID] = true;
            $link = get_edit_post_link((int) $row->ID, 'raw');
            $conflicts[] = [
                'id' => (int) $row->ID,
                'title' => (string) $row->post_title,
                'link' => is_string($link) ? $link : '',
            ];
        }

        return $conflicts;
    }

    /**
     * Add the SEO column.
     *
     * @param  array<string, string>  $columns  Columns.
     * @return array<string, string>
     */
    public function column(array $columns): array
    {
        $columns['mh_seo_score'] = __('SEO', 'mh-seo');

        return $columns;
    }

    /**
     * Print the score.
     *
     * @param  string  $column  Column key.
     * @param  int  $post_id  Post id.
     */
    public function column_value(string $column, int $post_id): void
    {
        if ($column !== 'mh_seo_score') {
            return;
        }
        $raw = get_post_meta($post_id, '_mh_seo_score', true);
        if ((string) $raw === '') {
            echo '<span class="mh-seo-score">—</span>';

            return;
        }
        $score = (int) $raw;
        $color = Score_Analyzer::color($score);
        printf(
            '<span class="mh-seo-score mh-seo-score--%1$s"><span class="mh-seo-score__dot" aria-hidden="true"></span>%2$s</span>',
            esc_attr($color),
            esc_html((string) $score)
        );
    }

    /**
     * Make the column sortable.
     *
     * @param  array<string, string>  $columns  Sortable columns.
     * @return array<string, string>
     */
    public function sortable(array $columns): array
    {
        $columns['mh_seo_score'] = 'mh_seo_score';

        return $columns;
    }

    /**
     * Sort and filter the list table.
     *
     * @param  \WP_Query  $query  Query.
     */
    public function sort_and_filter(\WP_Query $query): void
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || ! in_array($screen->post_type, mh_seo_post_types(), true)) {
            return;
        }
        if ($query->get('orderby') === 'mh_seo_score') {
            $query->set('meta_key', '_mh_seo_score');
            $query->set('orderby', 'meta_value_num');
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter, and the value is allowlisted below.
        $band = isset($_GET['mh_seo_band']) ? sanitize_key(wp_unslash($_GET['mh_seo_band'])) : '';
        if (! in_array($band, ['red', 'orange', 'green'], true)) {
            return;
        }
        $meta = [
            'key' => '_mh_seo_score',
            'type' => 'NUMERIC',
        ];
        if ($band === 'green') {
            $meta['value'] = 80;
            $meta['compare'] = '>=';
        } elseif ($band === 'orange') {
            $meta['value'] = [50, 79];
            $meta['compare'] = 'BETWEEN';
        } else {
            $meta['value'] = 49;
            $meta['compare'] = '<=';
        }
        $query->set('meta_query', [$meta]);
    }

    /**
     * Score filter above the list.
     *
     * @param  string  $post_type  Current post type.
     */
    public function filter_dropdown(string $post_type): void
    {
        if (! in_array($post_type, mh_seo_post_types(), true)) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter, and the value is allowlisted by selected().
        $band = isset($_GET['mh_seo_band']) ? sanitize_key(wp_unslash($_GET['mh_seo_band'])) : '';
        echo '<label class="screen-reader-text" for="mh-seo-band">'.esc_html__('Filter by SEO score', 'mh-seo').'</label>';
        echo '<select name="mh_seo_band" id="mh-seo-band">';
        echo '<option value="">'.esc_html__('All scores', 'mh-seo').'</option>';
        printf('<option value="red" %s>%s</option>', selected($band, 'red', false), esc_html__('Needs work (under 50)', 'mh-seo'));
        printf('<option value="orange" %s>%s</option>', selected($band, 'orange', false), esc_html__('Fair (50 to 79)', 'mh-seo'));
        printf('<option value="green" %s>%s</option>', selected($band, 'green', false), esc_html__('Strong (80 and up)', 'mh-seo'));
        echo '</select>';
    }

    /**
     * Styles for the list column only.
     *
     * @param  string  $hook  Current admin page.
     */
    public function styles(string $hook): void
    {
        if ($hook !== 'edit.php') {
            return;
        }
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->post_type, mh_seo_post_types(), true)) {
            return;
        }
        wp_register_style('mh-seo-admin', false, [], MH_SEO_VERSION);
        wp_enqueue_style('mh-seo-admin');
        wp_add_inline_style(
            'mh-seo-admin',
            '.column-mh_seo_score{width:88px}.mh-seo-score{display:inline-flex;align-items:center;gap:6px;font-variant-numeric:tabular-nums}.mh-seo-score__dot{width:8px;height:8px;border-radius:50%;background:#8c8f94}.mh-seo-score--red .mh-seo-score__dot{background:#d63638}.mh-seo-score--orange .mh-seo-score__dot{background:#dba617}.mh-seo-score--green .mh-seo-score__dot{background:#00a32a}'
        );
    }
}
