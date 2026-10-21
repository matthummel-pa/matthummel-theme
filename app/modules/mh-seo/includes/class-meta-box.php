<?php

/**
 * The SEO box on the post edit screen.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Prints and saves the SEO fields below the editor.
 *
 * The box works without JavaScript: every panel is shown and every field posts
 * with the form. The script adds tabs, length meters, and live previews, and in
 * the block editor it keeps these fields in step with the SEO sidebar.
 */
class Meta_Box
{
    /**
     * Text fields: posted name => meta key.
     *
     * @return array<string, string>
     */
    private static function text_fields(): array
    {
        return [
            'mh_seo_title' => '_mh_seo_title',
            'mh_seo_description' => '_mh_seo_description',
            'mh_seo_focus_keyword' => '_mh_seo_focus_keyword',
            'mh_seo_keywords' => '_mh_seo_keywords',
            'mh_seo_canonical' => '_mh_seo_canonical',
            'mh_seo_og_image' => '_mh_seo_og_image',
            'mh_seo_og_title' => '_mh_seo_og_title',
            'mh_seo_og_description' => '_mh_seo_og_description',
            'mh_seo_twitter_title' => '_mh_seo_twitter_title',
            'mh_seo_twitter_description' => '_mh_seo_twitter_description',
            'mh_seo_twitter_image' => '_mh_seo_twitter_image',
            'mh_seo_twitter_card' => '_mh_seo_twitter_card',
            'mh_seo_max_snippet' => '_mh_seo_max_snippet',
            'mh_seo_max_image_preview' => '_mh_seo_max_image_preview',
            'mh_seo_max_video_preview' => '_mh_seo_max_video_preview',
            'mh_seo_breadcrumb_title' => '_mh_seo_breadcrumb_title',
            'mh_seo_schema_type' => '_mh_seo_schema_type',
        ];
    }

    /**
     * Checkboxes: posted name => meta key.
     *
     * @return array<string, string>
     */
    private static function flag_fields(): array
    {
        $fields = [];
        foreach (Meta::flag_keys() as $key) {
            $fields[ltrim($key, '_')] = $key;
        }

        return $fields;
    }

    /**
     * The id attribute for a field.
     *
     * WordPress 7.1 gives the box heading the id "mh-seo-title", so field ids
     * get their own prefix to stay unique.
     *
     * @param  string  $name  Posted field name, such as mh_seo_title.
     */
    private static function field_id(string $name): string
    {
        return 'mh-seo-field-'.str_replace('_', '-', (string) preg_replace('/^mh_seo_/', '', $name));
    }

    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('add_meta_boxes', [$this, 'box']);
        add_action('save_post', [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    /**
     * Add the box to each supported post type.
     */
    public function box(): void
    {
        foreach (mh_seo_post_types() as $post_type) {
            add_meta_box(
                'mh-seo',
                __('SEO', 'mh-seo'),
                [$this, 'render'],
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Load the box's style and script on the edit screen.
     *
     * @param  string  $hook  Current admin page.
     */
    public function assets(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        $screen = get_current_screen();
        $post = get_post();
        if (! $screen || ! $post instanceof \WP_Post || ! in_array($screen->post_type, mh_seo_post_types(), true)) {
            return;
        }
        wp_enqueue_media(['post' => $post->ID]);
        wp_enqueue_style('mh-seo-meta-box', MH_SEO_URL.'assets/meta-box.css', [], MH_SEO_VERSION);
        $deps = $screen->is_block_editor() ? ['wp-data', 'wp-editor', 'wp-dom-ready'] : [];
        wp_enqueue_script('mh-seo-meta-box', MH_SEO_URL.'assets/meta-box.js', $deps, MH_SEO_VERSION, true);
        wp_add_inline_script(
            'mh-seo-meta-box',
            'window.mhSeoBox = '.wp_json_encode($this->config($post)).';',
            'before'
        );
    }

    /**
     * Values the script needs.
     *
     * @param  \WP_Post  $post  Post being edited.
     * @return array<string, mixed>
     */
    private function config(\WP_Post $post): array
    {
        $settings = Settings::get();
        $thresholds = array_merge(Score_Analyzer::default_thresholds(), is_array($settings['thresholds'] ?? null) ? $settings['thresholds'] : []);
        $fallback = $this->fallbacks($post);
        $meta = [];
        foreach (array_merge(self::text_fields(), self::flag_fields()) as $name => $key) {
            $meta[$name] = [
                'key' => $key,
                'type' => Meta::keys()[$key] ?? 'string',
            ];
        }

        return [
            'meta' => $meta,
            'title' => [
                'min' => (int) $thresholds['title_pass_min'],
                'max' => (int) $thresholds['title_pass_max'],
                'warnMin' => 1,
                'warnMax' => (int) $thresholds['title_warn_max'],
            ],
            'description' => [
                'min' => (int) $thresholds['description_pass_min'],
                'max' => (int) $thresholds['description_pass_max'],
                'warnMin' => (int) $thresholds['description_warn_min'],
                'warnMax' => (int) $thresholds['description_warn_max'],
            ],
            'siteHost' => (string) wp_parse_url(home_url(), PHP_URL_HOST),
            'permalink' => (string) get_permalink($post),
            'fallbackTitle' => $fallback['title'],
            'fallbackDescription' => $fallback['description'],
            'fallbackImage' => $fallback['image'],
            'maxKeywords' => Meta::MAX_KEYWORDS,
            'i18n' => [
                /* translators: 1: character count, 2: upper end of the target range. */
                'count' => __('%1$d / %2$d characters.', 'mh-seo'),
                'good' => __('Good length.', 'mh-seo'),
                /* translators: 1: lower end of the target range, 2: upper end. */
                'short' => __('A little short. Aim for %1$d to %2$d.', 'mh-seo'),
                /* translators: 1: lower end of the target range, 2: upper end. */
                'long' => __('Too long, so it may get cut off. Aim for %1$d to %2$d.', 'mh-seo'),
                'usingDefault' => __('The field is blank, so the default shown in it is used.', 'mh-seo'),
                'missing' => __('Nothing to show yet.', 'mh-seo'),
                'chooseImage' => __('Choose image', 'mh-seo'),
                'changeImage' => __('Change image', 'mh-seo'),
                'useImage' => __('Use this image', 'mh-seo'),
                /* translators: %d: number of times a keyword appears in the content. */
                'keywordCount' => __('%d in the content', 'mh-seo'),
                /* translators: %d: number of question headings found in the content. */
                'faqCount' => __('%d question headings found in the content.', 'mh-seo'),
                /* translators: %d: number of steps in the first numbered list. */
                'howtoCount' => __('%d steps found in the first numbered list.', 'mh-seo'),
                /* translators: %d: the most keywords that are kept. */
                'keywordLimit' => __('Only the first %d keywords are kept.', 'mh-seo'),
            ],
        ];
    }

    /**
     * Title, description, and image a post gets when its own fields are blank.
     *
     * @param  \WP_Post  $post  Post being edited.
     * @return array{title: string, description: string, image: string}
     */
    private function fallbacks(\WP_Post $post): array
    {
        $context = (new Context)->from_post($post);
        $builder = new Document_Builder;
        $without = $context;
        $without['custom_title'] = '';
        $without['custom_description'] = '';
        $image_id = (int) get_post_thumbnail_id($post->ID);
        if ($image_id < 1) {
            $image_id = (int) (Settings::get()['default_og_image'] ?? 0);
        }

        return [
            'title' => $builder->title($without, Settings::get()),
            'description' => $builder->description($without),
            'image' => $this->image_url($image_id),
        ];
    }

    /**
     * A large preview URL for an attachment, or an empty string.
     *
     * @param  int  $image_id  Attachment id.
     */
    private function image_url(int $image_id): string
    {
        if ($image_id < 1) {
            return '';
        }
        $url = wp_get_attachment_image_url($image_id, 'large');

        return is_string($url) ? $url : '';
    }

    /**
     * Print the box.
     *
     * @param  \WP_Post  $post  Post being edited.
     */
    public function render(\WP_Post $post): void
    {
        wp_nonce_field('mh_seo_save_meta', 'mh_seo_meta_nonce');
        $value = static function (string $key) use ($post): string {
            $stored = get_post_meta($post->ID, $key, true);

            return is_scalar($stored) ? (string) $stored : '';
        };
        $fallback = $this->fallbacks($post);
        $tabs = [
            'search' => __('Search', 'mh-seo'),
            'social' => __('Social', 'mh-seo'),
            'advanced' => __('Advanced', 'mh-seo'),
            'schema' => __('Schema', 'mh-seo'),
        ];

        echo '<div class="mh-seo-box" data-mh-seo-box>';
        $this->render_summary($post);

        echo '<div class="mh-seo-tabs" role="tablist" aria-label="'.esc_attr__('SEO sections', 'mh-seo').'" hidden>';
        foreach ($tabs as $id => $label) {
            printf(
                '<button type="button" class="mh-seo-tab" role="tab" id="mh-seo-tab-%1$s" aria-controls="mh-seo-panel-%1$s" aria-selected="%2$s" tabindex="%3$s">%4$s</button>',
                esc_attr($id),
                $id === 'search' ? 'true' : 'false',
                $id === 'search' ? '0' : '-1',
                esc_html($label)
            );
        }
        echo '</div>';

        foreach ($tabs as $id => $label) {
            printf(
                '<section class="mh-seo-panel" role="tabpanel" id="mh-seo-panel-%1$s" aria-labelledby="mh-seo-tab-%1$s" tabindex="0"><h3 class="mh-seo-panel__title">%2$s</h3>',
                esc_attr($id),
                esc_html($label)
            );
            if ($id === 'search') {
                $this->render_search($value, $fallback);
            } elseif ($id === 'social') {
                $this->render_social($value, $fallback);
            } elseif ($id === 'advanced') {
                $this->render_advanced($post, $value);
            } else {
                $this->render_schema($post, $value);
            }
            echo '</section>';
        }
        echo '</div>';
    }

    /**
     * Score ring and the checks still to fix, as of the last save.
     *
     * @param  \WP_Post  $post  Post being edited.
     */
    private function render_summary(\WP_Post $post): void
    {
        $raw = get_post_meta($post->ID, '_mh_seo_score', true);
        $has_score = is_scalar($raw) && (string) $raw !== '';
        $score = $has_score ? max(0, min(100, (int) $raw)) : 0;
        $color = $has_score ? Score_Analyzer::color($score) : 'none';
        $failed = json_decode((string) get_post_meta($post->ID, '_mh_seo_checks', true), true);
        $failed = is_array($failed) ? $failed : [];
        $labels = Admin::check_labels();
        $circumference = 2 * M_PI * 22;

        echo '<div class="mh-seo-summary">';
        printf(
            '<div class="mh-seo-ring mh-seo-ring--%1$s"><svg viewBox="0 0 52 52" width="52" height="52" aria-hidden="true" focusable="false"><circle class="mh-seo-ring__track" cx="26" cy="26" r="22" /><circle class="mh-seo-ring__value" cx="26" cy="26" r="22" stroke-dasharray="%2$s %3$s" transform="rotate(-90 26 26)" /></svg><span class="mh-seo-ring__number" aria-hidden="true">%4$s</span></div>',
            esc_attr($color),
            esc_attr(number_format($circumference * $score / 100, 2, '.', '')),
            esc_attr(number_format($circumference, 2, '.', '')),
            esc_html($has_score ? (string) $score : '–')
        );
        echo '<div class="mh-seo-summary__text">';
        if ($has_score) {
            printf(
                '<p class="mh-seo-summary__score"><strong>%s</strong> %s</p>',
                /* translators: %d: SEO score from 0 to 100. */
                esc_html(sprintf(__('SEO score %d out of 100', 'mh-seo'), $score)),
                esc_html__('as of the last save.', 'mh-seo')
            );
        } else {
            echo '<p class="mh-seo-summary__score"><strong>'.esc_html__('No SEO score yet.', 'mh-seo').'</strong> '.esc_html__('Save the post to get one.', 'mh-seo').'</p>';
        }
        $names = [];
        foreach ($failed as $id) {
            if (is_string($id) && isset($labels[$id])) {
                $names[] = $labels[$id];
            }
        }
        if ($names !== []) {
            printf(
                '<details class="mh-seo-summary__checks"><summary>%s</summary><ul>',
                /* translators: %d: number of failed checks. */
                esc_html(sprintf(_n('%d check to fix', '%d checks to fix', count($names), 'mh-seo'), count($names)))
            );
            foreach ($names as $name) {
                echo '<li>'.esc_html($name).'</li>';
            }
            echo '</ul></details>';
        } elseif ($has_score) {
            echo '<p class="mh-seo-summary__checks">'.esc_html__('Every check passes.', 'mh-seo').'</p>';
        }
        echo '</div></div>';
    }

    /**
     * Search tab: preview, title, description, keywords.
     *
     * @param  callable(string): string  $value  Reads one meta value.
     * @param  array{title: string, description: string, image: string}  $fallback  Defaults for blank fields.
     */
    private function render_search(callable $value, array $fallback): void
    {
        $icon = get_site_icon_url(32);
        echo '<div class="mh-seo-preview-wrap">';
        echo '<div class="mh-seo-preview-head"><span class="mh-seo-label" id="mh-seo-serp-label">'.esc_html__('Search preview', 'mh-seo').'</span>';
        echo '<span class="mh-seo-toggle" role="group" aria-label="'.esc_attr__('Preview size', 'mh-seo').'" hidden>';
        echo '<button type="button" class="mh-seo-toggle__button" data-serp-device="desktop" aria-pressed="true">'.esc_html__('Desktop', 'mh-seo').'</button>';
        echo '<button type="button" class="mh-seo-toggle__button" data-serp-device="mobile" aria-pressed="false">'.esc_html__('Mobile', 'mh-seo').'</button>';
        echo '</span></div>';
        echo '<div class="mh-seo-serp" data-serp aria-labelledby="mh-seo-serp-label">';
        echo '<div class="mh-seo-serp__site">';
        if ($icon !== '') {
            echo '<img class="mh-seo-serp__icon" src="'.esc_url($icon).'" alt="" width="26" height="26" />';
        } else {
            echo '<span class="mh-seo-serp__icon" aria-hidden="true"></span>';
        }
        echo '<span class="mh-seo-serp__names"><span class="mh-seo-serp__name">'.esc_html((string) get_bloginfo('name')).'</span><span class="mh-seo-serp__url" data-serp-url></span></span></div>';
        echo '<div class="mh-seo-serp__title" data-serp-title>'.esc_html($value('_mh_seo_title') !== '' ? $value('_mh_seo_title') : $fallback['title']).'</div>';
        echo '<div class="mh-seo-serp__desc" data-serp-desc>'.esc_html($value('_mh_seo_description') !== '' ? $value('_mh_seo_description') : $fallback['description']).'</div>';
        echo '</div></div>';

        $this->text_field(
            'mh_seo_title',
            __('SEO title', 'mh-seo'),
            $value('_mh_seo_title'),
            [
                'placeholder' => $fallback['title'],
                'help' => __('Leave blank to use the title template. A title you type here is used exactly, with nothing added.', 'mh-seo'),
                'meter' => 'title',
            ]
        );
        $this->text_field(
            'mh_seo_description',
            __('Meta description', 'mh-seo'),
            $value('_mh_seo_description'),
            [
                'placeholder' => $fallback['description'],
                'help' => __('Leave blank to use the excerpt or the start of the content.', 'mh-seo'),
                'meter' => 'description',
                'rows' => 3,
            ]
        );

        echo '<div class="mh-seo-grid">';
        $this->text_field(
            'mh_seo_focus_keyword',
            __('Focus keyword', 'mh-seo'),
            $value('_mh_seo_focus_keyword'),
            ['help' => __('The one phrase this page should rank for. The score checks are run against it.', 'mh-seo')]
        );
        $this->text_field(
            'mh_seo_keywords',
            __('Secondary keywords', 'mh-seo'),
            $value('_mh_seo_keywords'),
            [
                /* translators: %d: the most keywords that are kept. */
                'help' => sprintf(__('Up to %d, separated by commas. These are notes for you. They are not printed on the page.', 'mh-seo'), Meta::MAX_KEYWORDS),
                'after' => '<ul class="mh-seo-chips" data-keyword-chips aria-live="polite"></ul>',
            ]
        );
        echo '</div>';
    }

    /**
     * Social tab: Open Graph and X fields with card previews.
     *
     * @param  callable(string): string  $value  Reads one meta value.
     * @param  array{title: string, description: string, image: string}  $fallback  Defaults for blank fields.
     */
    private function render_social(callable $value, array $fallback): void
    {
        $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);

        echo '<div class="mh-seo-split"><fieldset class="mh-seo-group"><legend>'.esc_html__('Facebook, LinkedIn, and other link previews', 'mh-seo').'</legend>';
        $this->text_field(
            'mh_seo_og_title',
            __('Social title', 'mh-seo'),
            $value('_mh_seo_og_title'),
            ['help' => __('Leave blank to use the SEO title.', 'mh-seo'), 'counter' => 60]
        );
        $this->text_field(
            'mh_seo_og_description',
            __('Social description', 'mh-seo'),
            $value('_mh_seo_og_description'),
            ['help' => __('Leave blank to use the meta description.', 'mh-seo'), 'counter' => 200, 'rows' => 2]
        );
        $this->image_field(
            'mh_seo_og_image',
            __('Social image', 'mh-seo'),
            (int) $value('_mh_seo_og_image'),
            __('Leave empty to use the featured image, then the site default. 1200 by 630 pixels works best.', 'mh-seo')
        );
        echo '</fieldset>';
        $this->card('og', __('Link preview', 'mh-seo'), $host, $fallback);
        echo '</div>';

        echo '<div class="mh-seo-split"><fieldset class="mh-seo-group"><legend>'.esc_html__('X (Twitter)', 'mh-seo').'</legend>';
        $this->select_field(
            'mh_seo_twitter_card',
            __('Card size', 'mh-seo'),
            $value('_mh_seo_twitter_card'),
            [
                '' => __('Automatic: large when there is an image', 'mh-seo'),
                'summary_large_image' => __('Large image', 'mh-seo'),
                'summary' => __('Small image beside the text', 'mh-seo'),
            ]
        );
        $this->text_field(
            'mh_seo_twitter_title',
            __('X title', 'mh-seo'),
            $value('_mh_seo_twitter_title'),
            ['help' => __('Leave blank to use the social title.', 'mh-seo'), 'counter' => 70]
        );
        $this->text_field(
            'mh_seo_twitter_description',
            __('X description', 'mh-seo'),
            $value('_mh_seo_twitter_description'),
            ['help' => __('Leave blank to use the social description.', 'mh-seo'), 'counter' => 200, 'rows' => 2]
        );
        $this->image_field(
            'mh_seo_twitter_image',
            __('X image', 'mh-seo'),
            (int) $value('_mh_seo_twitter_image'),
            __('Leave empty to use the social image.', 'mh-seo')
        );
        echo '</fieldset>';
        $this->card('twitter', __('X preview', 'mh-seo'), $host, $fallback);
        echo '</div>';
    }

    /**
     * Advanced tab: canonical, robots, sitemap, breadcrumb, pillar.
     *
     * @param  \WP_Post  $post  Post being edited.
     * @param  callable(string): string  $value  Reads one meta value.
     */
    private function render_advanced(\WP_Post $post, callable $value): void
    {
        $this->text_field(
            'mh_seo_canonical',
            __('Canonical URL', 'mh-seo'),
            $value('_mh_seo_canonical'),
            [
                'type' => 'url',
                'placeholder' => (string) get_permalink($post),
                'help' => __('Leave blank to use this post’s own address.', 'mh-seo'),
            ]
        );

        // A post Rank Math already hid stays hidden until the box is unticked on purpose.
        $noindex = $value('_mh_seo_noindex');
        if ($noindex === '') {
            $robots = get_post_meta($post->ID, 'rank_math_robots', true);
            $inherited = is_array($robots) ? in_array('noindex', $robots, true) : (is_string($robots) && str_contains($robots, 'noindex'));
            $noindex = $inherited ? '1' : '';
        }

        echo '<fieldset class="mh-seo-group"><legend>'.esc_html__('Robots', 'mh-seo').'</legend><div class="mh-seo-checks-grid">';
        $this->checkbox('mh_seo_noindex', __('Hide from search engines', 'mh-seo'), $noindex === '1', 'noindex');
        $this->checkbox('mh_seo_nofollow', __('Tell search engines not to follow links here', 'mh-seo'), $value('_mh_seo_nofollow') === '1', 'nofollow');
        $this->checkbox('mh_seo_noarchive', __('No cached copy', 'mh-seo'), $value('_mh_seo_noarchive') === '1', 'noarchive');
        $this->checkbox('mh_seo_nosnippet', __('No text snippet in results', 'mh-seo'), $value('_mh_seo_nosnippet') === '1', 'nosnippet');
        $this->checkbox('mh_seo_noimageindex', __('Do not index images on this page', 'mh-seo'), $value('_mh_seo_noimageindex') === '1', 'noimageindex');
        echo '</div><div class="mh-seo-grid mh-seo-grid--three">';
        $this->text_field(
            'mh_seo_max_snippet',
            __('Longest snippet', 'mh-seo'),
            $value('_mh_seo_max_snippet'),
            ['type' => 'number', 'min' => '-1', 'placeholder' => '-1', 'help' => __('Characters. Blank or -1 means no limit.', 'mh-seo')]
        );
        $this->select_field(
            'mh_seo_max_image_preview',
            __('Image preview size', 'mh-seo'),
            $value('_mh_seo_max_image_preview'),
            [
                '' => __('Large (site default)', 'mh-seo'),
                'standard' => __('Standard', 'mh-seo'),
                'none' => __('None', 'mh-seo'),
            ]
        );
        $this->text_field(
            'mh_seo_max_video_preview',
            __('Longest video preview', 'mh-seo'),
            $value('_mh_seo_max_video_preview'),
            ['type' => 'number', 'min' => '-1', 'placeholder' => '-1', 'help' => __('Seconds. Blank or -1 means no limit.', 'mh-seo')]
        );
        echo '</div>';
        echo '<p class="mh-seo-robots"><span class="mh-seo-label">'.esc_html__('Robots tag this page will send', 'mh-seo').'</span> <code data-robots-preview></code></p>';
        echo '</fieldset>';

        echo '<fieldset class="mh-seo-group"><legend>'.esc_html__('Site structure', 'mh-seo').'</legend>';
        $this->checkbox('mh_seo_sitemap_exclude', __('Leave this out of the sitemap', 'mh-seo'), $value('_mh_seo_sitemap_exclude') === '1');
        echo '<p class="description">'.esc_html__('Hiding a page from search engines also leaves it out of the sitemap. The sitemap checkbox is for a page that can stay indexed but should not be listed.', 'mh-seo').'</p>';
        $this->checkbox('mh_seo_pillar', __('This is pillar content', 'mh-seo'), $value('_mh_seo_pillar') === '1');
        echo '<p class="description">'.esc_html__('Marks your most important pages. They get a “Pillar” label in the SEO column so you can link to them from related posts.', 'mh-seo').'</p>';
        $this->text_field(
            'mh_seo_breadcrumb_title',
            __('Breadcrumb title', 'mh-seo'),
            $value('_mh_seo_breadcrumb_title'),
            [
                'placeholder' => get_the_title($post),
                'help' => __('A shorter name for this page in the breadcrumb schema. Leave blank to use the post title.', 'mh-seo'),
            ]
        );
        echo '</fieldset>';
    }

    /**
     * Schema tab: article type, FAQ, and HowTo.
     *
     * @param  \WP_Post  $post  Post being edited.
     * @param  callable(string): string  $value  Reads one meta value.
     */
    private function render_schema(\WP_Post $post, callable $value): void
    {
        $default = __('Web page only, no article', 'mh-seo');
        if ($post->post_type === 'post') {
            $default = 'BlogPosting';
        } elseif ($post->post_type !== 'page') {
            $default = 'Article';
        }
        $this->select_field(
            'mh_seo_schema_type',
            __('Article type', 'mh-seo'),
            $value('_mh_seo_schema_type'),
            [
                /* translators: %s: the schema type this post gets by default. */
                '' => sprintf(__('Default (%s)', 'mh-seo'), $default),
                'Article' => 'Article',
                'BlogPosting' => 'BlogPosting',
                'TechArticle' => 'TechArticle',
                'NewsArticle' => 'NewsArticle',
                'ProfilePage' => __('ProfilePage: a page about me (the About page gets this on its own)', 'mh-seo'),
                'none' => __('None: web page only', 'mh-seo'),
            ],
            __('The page always gets WebSite, Person, WebPage, and breadcrumb data. This sets the article part. ProfilePage marks the page as being about the site Person, with no article.', 'mh-seo')
        );

        echo '<fieldset class="mh-seo-group"><legend>'.esc_html__('Extra structured data', 'mh-seo').'</legend>';
        $this->checkbox('mh_seo_schema_faq', __('Also add FAQ data from the question headings', 'mh-seo'), $value('_mh_seo_schema_faq') === '1');
        echo '<p class="description">'.esc_html__('Questions blocks always add FAQ data. Tick this to also turn each H2, H3, or H4 that ends with a question mark into a question, with the text under it as the answer. Google shows FAQ results for only a few kinds of sites, but AI answer engines still read the data.', 'mh-seo').' <span data-faq-count></span></p>';
        $this->checkbox('mh_seo_schema_howto', __('Add HowTo data from the first numbered list', 'mh-seo'), $value('_mh_seo_schema_howto') === '1');
        echo '<p class="description">'.esc_html__('Each item in the first numbered list becomes a step. Use this only when that list is the steps of the page. Google no longer shows HowTo results, but the data is still valid.', 'mh-seo').' <span data-howto-count></span></p>';
        echo '</fieldset>';
    }

    /**
     * One labeled text input or textarea.
     *
     * @param  string  $name  Field name.
     * @param  string  $label  Visible label.
     * @param  string  $value  Stored value.
     * @param  array<string, mixed>  $args  Optional type, placeholder, help, rows, meter, counter, min, after.
     */
    private function text_field(string $name, string $label, string $value, array $args = []): void
    {
        $id = self::field_id($name);
        $help = (string) ($args['help'] ?? '');
        $meter = (string) ($args['meter'] ?? '');
        $counter = (int) ($args['counter'] ?? 0);
        $describedby = [];
        if ($meter !== '' || $counter > 0) {
            $describedby[] = $id.'-status';
        }
        if ($help !== '') {
            $describedby[] = $id.'-help';
        }
        $attributes = sprintf(
            ' id="%1$s" name="%2$s" placeholder="%3$s"%4$s',
            esc_attr($id),
            esc_attr($name),
            esc_attr((string) ($args['placeholder'] ?? '')),
            $describedby !== [] ? ' aria-describedby="'.esc_attr(implode(' ', $describedby)).'"' : ''
        );

        echo '<div class="mh-seo-field">';
        echo '<label class="mh-seo-label" for="'.esc_attr($id).'">'.esc_html($label).'</label>';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $attributes is built from esc_attr() values above.
        if (! empty($args['rows'])) {
            echo '<textarea class="widefat" rows="'.esc_attr((string) (int) $args['rows']).'"'.$attributes.'>'.esc_textarea($value).'</textarea>';
        } else {
            $type = in_array(($args['type'] ?? 'text'), ['text', 'url', 'number'], true) ? (string) ($args['type'] ?? 'text') : 'text';
            echo '<input class="'.($type === 'number' ? 'small-text' : 'widefat').'" type="'.esc_attr($type).'" value="'.esc_attr($value).'"'.(isset($args['min']) ? ' min="'.esc_attr((string) $args['min']).'" step="1"' : '').$attributes.' />';
        }
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        if ($meter !== '') {
            printf(
                '<div class="mh-seo-meter" data-meter="%1$s" data-for="%2$s"><div class="mh-seo-meter__track" aria-hidden="true"><span class="mh-seo-meter__zone"></span><span class="mh-seo-meter__fill"></span></div><p class="mh-seo-meter__text" id="%2$s-status" aria-live="polite"></p></div>',
                esc_attr($meter),
                esc_attr($id)
            );
        } elseif ($counter > 0) {
            printf(
                '<p class="mh-seo-counter" id="%1$s-status" data-counter="%2$d" data-for="%1$s" aria-live="polite"></p>',
                esc_attr($id),
                (int) $counter
            );
        }
        if ($help !== '') {
            echo '<p class="description" id="'.esc_attr($id).'-help">'.esc_html($help).'</p>';
        }
        if (! empty($args['after'])) {
            echo wp_kses(
                (string) $args['after'],
                ['ul' => ['class' => true, 'data-keyword-chips' => true, 'aria-live' => true]]
            );
        }
        echo '</div>';
    }

    /**
     * One labeled select.
     *
     * @param  string  $name  Field name.
     * @param  string  $label  Visible label.
     * @param  string  $value  Stored value.
     * @param  array<string, string>  $options  Value => label.
     * @param  string  $help  Help text under the field.
     */
    private function select_field(string $name, string $label, string $value, array $options, string $help = ''): void
    {
        $id = self::field_id($name);
        echo '<div class="mh-seo-field">';
        echo '<label class="mh-seo-label" for="'.esc_attr($id).'">'.esc_html($label).'</label>';
        echo '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'"'.($help !== '' ? ' aria-describedby="'.esc_attr($id).'-help"' : '').'>';
        foreach ($options as $option => $text) {
            echo '<option value="'.esc_attr((string) $option).'"'.selected($value, (string) $option, false).'>'.esc_html($text).'</option>';
        }
        echo '</select>';
        if ($help !== '') {
            echo '<p class="description" id="'.esc_attr($id).'-help">'.esc_html($help).'</p>';
        }
        echo '</div>';
    }

    /**
     * One checkbox.
     *
     * @param  string  $name  Field name.
     * @param  string  $label  Visible label.
     * @param  bool  $checked  Whether it is ticked.
     * @param  string  $directive  Robots directive this box adds, shown as a small tag.
     */
    private function checkbox(string $name, string $label, bool $checked, string $directive = ''): void
    {
        $id = self::field_id($name);
        echo '<label class="mh-seo-checkbox" for="'.esc_attr($id).'">';
        echo '<input type="checkbox" id="'.esc_attr($id).'" name="'.esc_attr($name).'" value="1"'.checked($checked, true, false).($directive !== '' ? ' data-robots="'.esc_attr($directive).'"' : '').' /> ';
        echo '<span>'.esc_html($label).'</span>';
        if ($directive !== '') {
            echo ' <code class="mh-seo-tag">'.esc_html($directive).'</code>';
        }
        echo '</label>';
    }

    /**
     * An image picker. Without JavaScript it is a plain attachment id field.
     *
     * @param  string  $name  Field name.
     * @param  string  $label  Visible label.
     * @param  int  $image_id  Stored attachment id.
     * @param  string  $help  Help text under the field.
     */
    private function image_field(string $name, string $label, int $image_id, string $help): void
    {
        $id = self::field_id($name);
        $url = $this->image_url($image_id);
        echo '<div class="mh-seo-field mh-seo-image" data-image-field>';
        echo '<label class="mh-seo-label" for="'.esc_attr($id).'">'.esc_html($label).' <span class="mh-seo-image__id-note">'.esc_html__('(attachment ID)', 'mh-seo').'</span></label>';
        echo '<input class="small-text mh-seo-image__id" type="number" min="0" step="1" id="'.esc_attr($id).'" name="'.esc_attr($name).'" value="'.esc_attr($image_id > 0 ? (string) $image_id : '').'" data-url="'.esc_url($url).'" aria-describedby="'.esc_attr($id).'-help" />';
        echo '<div class="mh-seo-image__row" hidden>';
        echo '<img class="mh-seo-image__thumb" src="'.esc_url($url).'" alt=""'.($url === '' ? ' hidden' : '').' />';
        echo '<button type="button" class="button" data-image-pick aria-describedby="'.esc_attr($id).'-help">'.esc_html($url === '' ? __('Choose image', 'mh-seo') : __('Change image', 'mh-seo')).'</button> ';
        echo '<button type="button" class="button-link mh-seo-image__clear" data-image-clear'.($url === '' ? ' hidden' : '').'>'.esc_html__('Remove', 'mh-seo').'</button>';
        echo '</div>';
        echo '<p class="description" id="'.esc_attr($id).'-help">'.esc_html($help).'</p>';
        echo '</div>';
    }

    /**
     * A link-preview card the script fills in.
     *
     * @param  string  $kind  "og" or "twitter".
     * @param  string  $label  Heading above the card.
     * @param  string  $host  Site host shown on the card.
     * @param  array{title: string, description: string, image: string}  $fallback  Defaults for blank fields.
     */
    private function card(string $kind, string $label, string $host, array $fallback): void
    {
        echo '<div class="mh-seo-card-wrap"><span class="mh-seo-label">'.esc_html($label).'</span>';
        echo '<div class="mh-seo-card mh-seo-card--'.esc_attr($kind).'" data-card="'.esc_attr($kind).'">';
        echo '<div class="mh-seo-card__image"><img src="'.esc_url($fallback['image']).'" alt=""'.($fallback['image'] === '' ? ' hidden' : '').' /><span class="mh-seo-card__empty"'.($fallback['image'] === '' ? '' : ' hidden').'>'.esc_html__('No image', 'mh-seo').'</span></div>';
        echo '<div class="mh-seo-card__body"><span class="mh-seo-card__host">'.esc_html($host).'</span><strong class="mh-seo-card__title">'.esc_html($fallback['title']).'</strong><span class="mh-seo-card__desc">'.esc_html($fallback['description']).'</span></div>';
        echo '</div></div>';
    }

    /**
     * Save the box.
     *
     * @param  int  $post_id  Post id.
     */
    public function save(int $post_id): void
    {
        if (! isset($_POST['mh_seo_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_seo_meta_nonce'])), 'mh_seo_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id) || ! current_user_can('edit_post', $post_id)) {
            return;
        }
        $meta = new Meta;
        foreach (self::text_fields() as $posted => $key) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Meta::sanitize() cleans each value by key on the next line.
            $raw = isset($_POST[$posted]) && is_scalar($_POST[$posted]) ? wp_unslash($_POST[$posted]) : '';
            update_post_meta($post_id, $key, $meta->sanitize($raw, $key, 'post'));
        }
        foreach (self::flag_fields() as $posted => $key) {
            update_post_meta($post_id, $key, isset($_POST[$posted]) ? '1' : '0');
        }
    }
}
