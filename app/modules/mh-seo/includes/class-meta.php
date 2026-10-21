<?php

/**
 * Post meta for the SEO fields.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Registers and sanitizes the SEO meta. Meta_Box prints the fields on the edit screen.
 */
class Meta
{
    /**
     * Meta keys edited in the sidebar.
     *
     * @return array<string, string>
     */
    public static function keys(): array
    {
        return [
            '_mh_seo_title' => 'string',
            '_mh_seo_description' => 'string',
            '_mh_seo_canonical' => 'string',
            '_mh_seo_noindex' => 'string',
            '_mh_seo_sitemap_exclude' => 'string',
            '_mh_seo_og_image' => 'integer',
            '_mh_seo_focus_keyword' => 'string',
            '_mh_seo_score' => 'integer',
            '_mh_seo_checks' => 'string',
            '_mh_seo_primary_term' => 'integer',
            '_mh_seo_og_title' => 'string',
            '_mh_seo_og_description' => 'string',
            '_mh_seo_twitter_title' => 'string',
            '_mh_seo_twitter_description' => 'string',
            '_mh_seo_twitter_image' => 'integer',
            '_mh_seo_twitter_card' => 'string',
            '_mh_seo_nofollow' => 'string',
            '_mh_seo_noarchive' => 'string',
            '_mh_seo_nosnippet' => 'string',
            '_mh_seo_noimageindex' => 'string',
            '_mh_seo_max_snippet' => 'string',
            '_mh_seo_max_image_preview' => 'string',
            '_mh_seo_max_video_preview' => 'string',
            '_mh_seo_keywords' => 'string',
            '_mh_seo_breadcrumb_title' => 'string',
            '_mh_seo_pillar' => 'string',
            '_mh_seo_schema_type' => 'string',
            '_mh_seo_schema_faq' => 'string',
            '_mh_seo_schema_howto' => 'string',
        ];
    }

    /**
     * Meta keys stored as "1", "0", or an empty string when never saved.
     *
     * @return array<int, string>
     */
    public static function flag_keys(): array
    {
        return [
            '_mh_seo_noindex',
            '_mh_seo_sitemap_exclude',
            '_mh_seo_nofollow',
            '_mh_seo_noarchive',
            '_mh_seo_nosnippet',
            '_mh_seo_noimageindex',
            '_mh_seo_pillar',
            '_mh_seo_schema_faq',
            '_mh_seo_schema_howto',
        ];
    }

    /**
     * Schema types a post can pick. An empty value keeps the post type's default.
     *
     * @return array<int, string>
     */
    public static function schema_types(): array
    {
        return ['', 'Article', 'BlogPosting', 'TechArticle', 'NewsArticle', 'ProfilePage', 'none'];
    }

    /**
     * The most secondary keywords kept for one post.
     */
    public const MAX_KEYWORDS = 5;

    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    /**
     * Register meta for each supported post type.
     */
    public function register(): void
    {
        foreach (mh_seo_post_types() as $post_type) {
            foreach (self::keys() as $key => $type) {
                register_post_meta(
                    $post_type,
                    $key,
                    [
                        'type' => $type,
                        'single' => true,
                        'show_in_rest' => true,
                        'auth_callback' => static function ($allowed, $meta_key, $post_id, ...$extra) {
                            unset($allowed, $meta_key, $extra);

                            return current_user_can('edit_post', (int) $post_id);
                        },
                        'sanitize_callback' => [$this, 'sanitize'],
                    ]
                );
            }
            register_rest_field(
                $post_type,
                'mh_seo_fallbacks',
                [
                    'get_callback' => [$this, 'fallbacks'],
                    'schema' => [
                        'description' => __('Titles and keywords already stored for this post.', 'mh-seo'),
                        'type' => 'object',
                        'context' => ['edit'],
                    ],
                ]
            );
        }
    }

    /**
     * Sanitize one meta value.
     *
     * @param  mixed  $value  Raw value.
     * @param  string  $meta_key  Meta key.
     * @param  string  $object_type  Object type. Unused.
     * @param  mixed  ...$extra  Extra arguments from WordPress. Unused.
     * @return mixed
     */
    public function sanitize($value, string $meta_key = '', string $object_type = '', ...$extra)
    {
        unset($object_type, $extra);
        if (in_array($meta_key, self::flag_keys(), true)) {
            if ($value === true || (string) $value === '1' || $value === 1) {
                return '1';
            }
            if ($value === false || (string) $value === '0' || $value === 0) {
                return '0';
            }

            return '';
        }
        if (in_array($meta_key, ['_mh_seo_og_image', '_mh_seo_twitter_image', '_mh_seo_primary_term'], true)) {
            return absint($value);
        }
        if ($meta_key === '_mh_seo_score') {
            return max(0, min(100, absint($value)));
        }
        if ($meta_key === '_mh_seo_checks') {
            return $this->sanitize_checks((string) $value);
        }
        if ($meta_key === '_mh_seo_canonical') {
            return esc_url_raw((string) $value);
        }
        if ($meta_key === '_mh_seo_twitter_card') {
            return in_array((string) $value, ['summary', 'summary_large_image'], true) ? (string) $value : '';
        }
        if ($meta_key === '_mh_seo_max_image_preview') {
            return in_array((string) $value, ['none', 'standard', 'large'], true) ? (string) $value : '';
        }
        if ($meta_key === '_mh_seo_max_snippet' || $meta_key === '_mh_seo_max_video_preview') {
            return self::sanitize_limit($value);
        }
        if ($meta_key === '_mh_seo_schema_type') {
            return in_array((string) $value, self::schema_types(), true) ? (string) $value : '';
        }
        if ($meta_key === '_mh_seo_keywords') {
            return implode(', ', self::keyword_list((string) $value));
        }

        return sanitize_text_field((string) $value);
    }

    /**
     * A robots limit: an empty string for the default, or a whole number from -1 up.
     *
     * @param  mixed  $value  Raw value.
     */
    public static function sanitize_limit($value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || ! preg_match('/^-?\d+$/', $value)) {
            return '';
        }

        return (string) max(-1, (int) $value);
    }

    /**
     * Secondary keywords as a clean list.
     *
     * @param  string  $value  Comma-separated keywords.
     * @return array<int, string>
     */
    public static function keyword_list(string $value): array
    {
        $clean = [];
        foreach (explode(',', $value) as $keyword) {
            $keyword = trim(sanitize_text_field($keyword));
            if ($keyword === '' || in_array(strtolower($keyword), array_map('strtolower', $clean), true)) {
                continue;
            }
            $clean[] = $keyword;
            if (count($clean) >= self::MAX_KEYWORDS) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Keep only known failed-check ids.
     *
     * @param  string  $value  JSON list.
     */
    private function sanitize_checks(string $value): string
    {
        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return '[]';
        }
        $allowed = Score_Analyzer::check_ids();
        $clean = [];
        foreach ($decoded as $id) {
            if (is_string($id) && in_array($id, $allowed, true)) {
                $clean[] = $id;
            }
        }
        $encoded = wp_json_encode($clean);

        return is_string($encoded) ? $encoded : '[]';
    }

    /**
     * Values the editor can show before MH SEO meta is filled in.
     *
     * @param  array<string, mixed>  $post  REST post array.
     * @param  string  $field  Field name. Unused.
     * @param  mixed  $request  REST request. Unused.
     * @return array<string, mixed>
     */
    public function fallbacks(array $post, string $field = '', $request = null): array
    {
        unset($field, $request);
        $post_id = (int) ($post['id'] ?? 0);
        $object = get_post($post_id);
        if (! $object instanceof \WP_Post) {
            return [];
        }
        $context = (new Context)->from_post($object);
        $settings = Settings::get();
        $builder = new Document_Builder;
        $without = $context;
        $without['custom_title'] = '';
        $without['custom_description'] = '';
        $keyword = $this->keyword_fallback($post_id);
        $robots = get_post_meta($post_id, 'rank_math_robots', true);
        $noindex = is_array($robots) ? in_array('noindex', $robots, true) : (is_string($robots) && str_contains($robots, 'noindex'));

        return [
            'title' => $builder->title($without, $settings),
            'description' => $builder->description($without),
            'keyword' => $keyword,
            'noindex' => $noindex,
        ];
    }

    /**
     * First Rank Math focus keyword, when MH SEO does not have one yet.
     *
     * @param  int  $post_id  Post id.
     */
    private function keyword_fallback(int $post_id): string
    {
        $own = trim((string) get_post_meta($post_id, '_mh_seo_focus_keyword', true));
        if ($own !== '') {
            return '';
        }

        return (new Migrator)->first_keyword((string) get_post_meta($post_id, 'rank_math_focus_keyword', true));
    }
}
