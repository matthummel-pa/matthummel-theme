<?php

/**
 * Settings screens.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Settings → MH SEO.
 */
class Admin
{
    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register']);
        add_action('admin_notices', [$this, 'notice']);
        add_action('admin_post_mh_seo_calculate_scores', [$this, 'calculate_scores']);
        add_action('admin_enqueue_scripts', [$this, 'media']);
    }

    /**
     * Add the settings page.
     */
    public function menu(): void
    {
        add_options_page(
            __('MH SEO', 'mh-seo'),
            __('MH SEO', 'mh-seo'),
            'manage_options',
            'mh-seo',
            [$this, 'render']
        );
    }

    /**
     * Register the setting.
     */
    public function register(): void
    {
        register_setting(
            'mh_seo',
            Settings::OPTION,
            [
                'type' => 'object',
                'sanitize_callback' => [Settings::class, 'sanitize'],
                'default' => Settings::defaults(),
            ]
        );
    }

    /**
     * Explain the Rank Math overlap while both plugins are active.
     */
    public function notice(): void
    {
        if (! current_user_can('manage_options') || ! mh_seo_rank_math_is_active()) {
            return;
        }
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->id, ['dashboard', 'plugins', 'settings_page_mh-seo'], true)) {
            return;
        }
        echo '<div class="notice notice-info"><p>';
        if (mh_seo_is_managing_head()) {
            esc_html_e('MH SEO is printing head tags while Rank Math is still active. Turn one of them off so titles and schema are not printed twice.', 'mh-seo');
        } else {
            esc_html_e('MH SEO is installed and is not printing head tags, because Rank Math is still active. Compare a few pages, then deactivate Rank Math when you are ready to switch.', 'mh-seo');
        }
        echo '</p></div>';
    }

    /**
     * Media picker on the settings page.
     *
     * @param  string  $hook  Current admin page.
     */
    public function media(string $hook): void
    {
        if ($hook !== 'settings_page_mh-seo') {
            return;
        }
        wp_enqueue_media();
    }

    /**
     * Settings page.
     */
    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch, and the value is allowlisted below.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
        if (! in_array($tab, ['general', 'indexing', 'sitemaps', 'score'], true)) {
            $tab = 'general';
        }
        $settings = Settings::get();
        echo '<div class="wrap"><h1>'.esc_html__('MH SEO', 'mh-seo').'</h1>';
        echo '<p>'.esc_html($this->status_line()).'</p>';
        echo '<nav class="nav-tab-wrapper">';
        $this->tab_link('general', __('General', 'mh-seo'), $tab);
        $this->tab_link('indexing', __('Indexing', 'mh-seo'), $tab);
        $this->tab_link('sitemaps', __('Sitemaps', 'mh-seo'), $tab);
        $this->tab_link('score', __('Score', 'mh-seo'), $tab);
        echo '</nav>';
        echo '<form method="post" action="options.php">';
        settings_fields('mh_seo');
        echo '<input type="hidden" name="'.esc_attr(Settings::OPTION).'[section]" value="'.esc_attr($tab).'" />';
        if ($tab === 'general') {
            $this->general_fields($settings);
        } elseif ($tab === 'indexing') {
            $this->indexing_fields($settings);
        } elseif ($tab === 'sitemaps') {
            $this->sitemap_fields($settings);
        } else {
            $this->score_fields($settings);
        }
        submit_button(__('Save changes', 'mh-seo'));
        echo '</form>';
        if ($tab === 'score') {
            $this->score_tools();
        }
        echo '</div>';
    }

    /**
     * One sentence about whether head tags are being printed.
     */
    private function status_line(): string
    {
        if (mh_seo_is_managing_head()) {
            return __('Head tags are on. MH SEO is printing the title, description, social tags, and schema.', 'mh-seo');
        }
        if (mh_seo_rank_math_is_active()) {
            return __('Head tags are off while Rank Math is active, so the two plugins do not both print them.', 'mh-seo');
        }

        return __('Head tags are off. Rank Math is not active, and MH SEO is not printing them either.', 'mh-seo');
    }

    /**
     * A tab link.
     *
     * @param  string  $id  Tab id.
     * @param  string  $label  Label.
     * @param  string  $current  Current tab.
     */
    private function tab_link(string $id, string $label, string $current): void
    {
        $url = admin_url('options-general.php?page=mh-seo&tab='.$id);
        printf(
            '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
            esc_url($url),
            $current === $id ? 'nav-tab-active' : '',
            esc_html($label)
        );
    }

    /**
     * General fields.
     *
     * @param  array<string, mixed>  $settings  Settings.
     */
    private function general_fields(array $settings): void
    {
        echo '<table class="form-table">';
        $this->text_row(
            'title_template',
            __('Title template', 'mh-seo'),
            (string) $settings['title_template'],
            sprintf(
                /* translators: 1: title token, 2: separator token, 3: site name token. */
                __('Used when a post has no SEO title of its own. A title typed on the post is used exactly, with nothing added. Tokens: %1$s, %2$s, %3$s.', 'mh-seo'),
                '%title%',
                '%sep%',
                '%sitename%'
            )
        );
        echo '<tr><th scope="row">'.esc_html__('Head tags', 'mh-seo').'</th><td>';
        $mode = (string) $settings['head_output'];
        foreach ([
            'auto' => __('Automatic. Stay off while Rank Math is active.', 'mh-seo'),
            'on' => __('On. Print tags even if Rank Math is active.', 'mh-seo'),
            'off' => __('Off. Never print tags.', 'mh-seo'),
        ] as $value => $label) {
            printf(
                '<label><input type="radio" name="%1$s[head_output]" value="%2$s" %3$s /> %4$s</label><br />',
                esc_attr(Settings::OPTION),
                esc_attr($value),
                checked($mode, $value, false),
                esc_html($label)
            );
        }
        echo '<p class="description">'.esc_html__('The theme can check the MH_SEO_ACTIVE constant and mh_seo_is_managing_head() before it prints its own title or description.', 'mh-seo').'</p>';
        echo '</td></tr>';
        $this->text_row('person_name', __('Your name', 'mh-seo'), (string) $settings['person_name'], __('Used in the Person schema on every page. The site is a person, not a company.', 'mh-seo'));
        $this->text_row('person_job_title', __('Job title', 'mh-seo'), (string) $settings['person_job_title'], '');
        $this->text_row('person_image', __('Profile photo URL', 'mh-seo'), (string) $settings['person_image'], __('Optional. Shown as the Person image.', 'mh-seo'));
        $same = is_array($settings['same_as']) ? implode("\n", $settings['same_as']) : '';
        echo '<tr><th scope="row"><label for="mh-seo-same-as">'.esc_html__('Profile links', 'mh-seo').'</label></th><td>';
        printf(
            '<textarea class="large-text" rows="5" id="mh-seo-same-as" name="%1$s[same_as]">%2$s</textarea>',
            esc_attr(Settings::OPTION),
            esc_textarea($same)
        );
        echo '<p class="description">'.esc_html__('One URL per line. GitHub is filled in. Add LinkedIn, Dev.to, and Bluesky when you have the exact addresses.', 'mh-seo').'</p></td></tr>';
        $this->text_row('twitter_site', __('X / Twitter handle', 'mh-seo'), (string) $settings['twitter_site'], __('Optional. Leave blank to skip twitter:site and twitter:creator.', 'mh-seo'));
        echo '<tr><th scope="row"><label for="mh-seo-og">'.esc_html__('Default social image', 'mh-seo').'</label></th><td>';
        printf(
            '<input type="number" min="0" id="mh-seo-og" name="%1$s[default_og_image]" value="%2$d" /> ',
            esc_attr(Settings::OPTION),
            (int) $settings['default_og_image']
        );
        echo '<button type="button" class="button" id="mh-seo-og-pick">'.esc_html__('Choose image', 'mh-seo').'</button>';
        echo '<p class="description">'.esc_html__('Attachment ID used when a post has no social image and no featured image. The current site default is 491.', 'mh-seo').'</p>';
        echo '</td></tr></table>';
        $this->media_script();
    }

    /**
     * Indexing fields.
     *
     * @param  array<string, mixed>  $settings  Settings.
     */
    private function indexing_fields(array $settings): void
    {
        $boxes = [
            'noindex_search' => __('Hide search results', 'mh-seo'),
            'noindex_author' => __('Hide author archives', 'mh-seo'),
            'noindex_tag' => __('Hide tag archives', 'mh-seo'),
            'noindex_date' => __('Hide date archives', 'mh-seo'),
            'noindex_attachment' => __('Hide attachment pages', 'mh-seo'),
            'noindex_404' => __('Hide 404 pages', 'mh-seo'),
            'noindex_empty_tax' => __('Hide empty categories', 'mh-seo'),
            'redirect_attachments' => __('Send attachment pages to the parent post', 'mh-seo'),
            'indexnow_enabled' => __('Ping IndexNow when a post is published or updated', 'mh-seo'),
            'log_404' => __('Keep a short log of missing pages', 'mh-seo'),
        ];
        echo '<table class="form-table">';
        foreach ($boxes as $key => $label) {
            echo '<tr><th scope="row">'.esc_html($label).'</th><td>';
            printf(
                '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
                esc_attr(Settings::OPTION),
                esc_attr($key),
                checked(! empty($settings[$key]), true, false),
                esc_html__('On', 'mh-seo')
            );
            echo '</td></tr>';
        }
        $this->text_row('indexnow_key', __('IndexNow key', 'mh-seo'), (string) $settings['indexnow_key'], __('The plugin serves this key at /{key}.txt. The value already used on the site is filled in.', 'mh-seo'));
        echo '<tr><th scope="row"><label for="mh-seo-robots">'.esc_html__('Extra robots.txt lines', 'mh-seo').'</label></th><td>';
        printf(
            '<textarea class="large-text code" rows="4" id="mh-seo-robots" name="%1$s[robots_extra]">%2$s</textarea>',
            esc_attr(Settings::OPTION),
            esc_textarea((string) $settings['robots_extra'])
        );
        echo '<p class="description">'.esc_html__('The plugin already adds a rule for wp-admin and a Sitemap line. If the host returns 404 for /robots.txt before WordPress runs, this text never gets a chance to print. That is a hosting check, not a plugin setting.', 'mh-seo').'</p>';
        echo '</td></tr></table>';
    }

    /**
     * Sitemap fields.
     *
     * @param  array<string, mixed>  $settings  Settings.
     */
    private function sitemap_fields(array $settings): void
    {
        $option = Settings::OPTION;
        echo '<table class="form-table">';
        echo '<tr><th scope="row">'.esc_html__('XML sitemap', 'mh-seo').'</th><td>';
        printf(
            '<label><input type="checkbox" name="%1$s[sitemap_enabled]" value="1" %2$s /> %3$s</label>',
            esc_attr($option),
            checked(! empty($settings['sitemap_enabled']), true, false),
            esc_html__('On', 'mh-seo')
        );
        echo '<p class="description">'.esc_html__('While this is on, the sitemap index is /sitemap_index.xml and WordPress core sitemaps are turned off. While it is off, core sitemaps stay on.', 'mh-seo').'</p>';
        if (Sitemap::is_enabled()) {
            $url = home_url('/sitemap_index.xml');
            echo '<p>'.esc_html__('Sitemap index:', 'mh-seo').' <a href="'.esc_url($url).'">'.esc_html($url).'</a></p>';
        }
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="mh-seo-sitemap-per-page">'.esc_html__('Entries per sitemap', 'mh-seo').'</label></th><td>';
        printf(
            '<input type="number" min="1" max="50000" id="mh-seo-sitemap-per-page" name="%1$s[sitemap_per_page]" value="%2$d" />',
            esc_attr($option),
            (int) $settings['sitemap_per_page']
        );
        echo '<p class="description">'.esc_html__('Default is 1000. A type with more entries is split into post-sitemap.xml, post-sitemap2.xml, and so on.', 'mh-seo').'</p>';
        echo '</td></tr>';
        echo '<tr><th scope="row">'.esc_html__('Include images', 'mh-seo').'</th><td>';
        printf(
            '<label><input type="checkbox" name="%1$s[sitemap_images]" value="1" %2$s /> %3$s</label>',
            esc_attr($option),
            checked(! empty($settings['sitemap_images']), true, false),
            esc_html__('On', 'mh-seo')
        );
        echo '<p class="description">'.esc_html__('Adds the featured image and images in the content.', 'mh-seo').'</p>';
        echo '</td></tr>';
        echo '<tr><th scope="row">'.esc_html__('Author sitemap', 'mh-seo').'</th><td>';
        printf(
            '<label><input type="checkbox" name="%1$s[sitemap_authors]" value="1" %2$s /> %3$s</label>',
            esc_attr($option),
            checked(! empty($settings['sitemap_authors']), true, false),
            esc_html__('On', 'mh-seo')
        );
        echo '<p class="description">'.esc_html__('Off by default. If author archives are hidden from search engines, this file is left empty even when the box is checked.', 'mh-seo').'</p>';
        echo '</td></tr>';
        $this->sitemap_object_rows(__('Post types', 'mh-seo'), 'sitemap_post_types', Sitemap::selected_post_types(), $this->public_post_types());
        $this->sitemap_object_rows(__('Taxonomies', 'mh-seo'), 'sitemap_taxonomies', Sitemap::selected_taxonomies(), $this->public_taxonomies());
        echo '</table>';
        echo '<p class="description">'.esc_html__('Attachments are never included. Empty types are left out of the index. Pages hidden from search engines, password-protected pages, and pages marked “leave this out of the sitemap” are left out too. Tag archives stay out while “Hide tag archives” is on.', 'mh-seo').'</p>';
    }

    /**
     * Checkbox list for post types or taxonomies.
     *
     * @param  string  $label  Row label.
     * @param  string  $key  Setting key.
     * @param  array<int, string>  $selected  Saved slugs.
     * @param  array<string, string>  $choices  Slug to label.
     */
    private function sitemap_object_rows(string $label, string $key, array $selected, array $choices): void
    {
        echo '<tr><th scope="row">'.esc_html($label).'</th><td>';
        if ($choices === []) {
            echo '<p>'.esc_html__('None are public yet.', 'mh-seo').'</p></td></tr>';

            return;
        }
        foreach ($choices as $slug => $name) {
            printf(
                '<label><input type="checkbox" name="%1$s[%2$s][]" value="%3$s" %4$s /> %5$s</label><br />',
                esc_attr(Settings::OPTION),
                esc_attr($key),
                esc_attr($slug),
                checked(in_array($slug, $selected, true), true, false),
                esc_html($name)
            );
        }
        echo '</td></tr>';
    }

    /**
     * Public post types except attachments.
     *
     * @return array<string, string>
     */
    private function public_post_types(): array
    {
        $choices = [];
        foreach (get_post_types(['public' => true], 'objects') as $type) {
            if ($type->name === 'attachment') {
                continue;
            }
            $choices[$type->name] = $type->labels->name;
        }

        return $choices;
    }

    /**
     * Public taxonomies.
     *
     * @return array<string, string>
     */
    private function public_taxonomies(): array
    {
        $choices = [];
        foreach (get_taxonomies(['public' => true], 'objects') as $taxonomy) {
            $choices[$taxonomy->name] = $taxonomy->labels->name;
        }

        return $choices;
    }

    /**
     * Score weight fields.
     *
     * @param  array<string, mixed>  $settings  Settings.
     */
    private function score_fields(array $settings): void
    {
        $weights = is_array($settings['score_weights']) ? $settings['score_weights'] : [];
        echo '<p>'.esc_html__('Each check can be worth 0 to 100 points. Zero turns that check off. A warning earns half the points. The score is the points earned divided by the points available.', 'mh-seo').'</p>';
        echo '<table class="form-table">';
        foreach (self::check_labels() as $id => $label) {
            echo '<tr><th scope="row"><label for="mh-seo-weight-'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
            printf(
                '<input type="number" min="0" max="100" id="mh-seo-weight-%1$s" name="%2$s[score_weights][%1$s]" value="%3$d" />',
                esc_attr($id),
                esc_attr(Settings::OPTION),
                (int) ($weights[$id] ?? 0)
            );
            echo '</td></tr>';
        }
        echo '</table>';
        echo '<p><button type="submit" class="button" name="'.esc_attr(Settings::OPTION).'[reset_weights]" value="1">'.esc_html__('Reset to defaults', 'mh-seo').'</button></p>';
    }

    /**
     * Tools that are not part of the settings form.
     */
    private function score_tools(): void
    {
        echo '<hr /><h2>'.esc_html__('Calculate scores', 'mh-seo').'</h2>';
        echo '<p>'.esc_html__('Scores are saved when you update a post in the editor. This fills them in for posts you have not opened yet.', 'mh-seo').'</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        wp_nonce_field('mh_seo_calculate_scores');
        echo '<input type="hidden" name="action" value="mh_seo_calculate_scores" />';
        submit_button(__('Calculate scores now', 'mh-seo'), 'secondary');
        echo '</form>';
    }

    /**
     * Score a batch of posts, then continue until they are done.
     */
    public function calculate_scores(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You cannot calculate scores.', 'mh-seo'));
        }
        check_admin_referer('mh_seo_calculate_scores');
        $offset = isset($_GET['offset']) ? absint(wp_unslash($_GET['offset'])) : 0;
        $ids = get_posts(
            [
                'post_type' => mh_seo_post_types(),
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 40,
                'offset' => $offset,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
            ]
        );
        $score = new Score;
        foreach ($ids as $id) {
            $score->recompute((int) $id);
        }
        if (count($ids) < 40) {
            wp_safe_redirect(admin_url('options-general.php?page=mh-seo&tab=score&mh-seo-scored=1'));
            exit;
        }
        $next = wp_nonce_url(
            admin_url('admin-post.php?action=mh_seo_calculate_scores&offset='.($offset + 40)),
            'mh_seo_calculate_scores'
        );
        wp_safe_redirect($next);
        exit;
    }

    /**
     * A text field row.
     *
     * @param  string  $key  Setting key.
     * @param  string  $label  Label.
     * @param  string  $value  Current value.
     * @param  string  $description  Help text.
     */
    private function text_row(string $key, string $label, string $value, string $description): void
    {
        echo '<tr><th scope="row"><label for="mh-seo-'.esc_attr($key).'">'.esc_html($label).'</label></th><td>';
        printf(
            '<input class="regular-text" type="text" id="mh-seo-%1$s" name="%2$s[%1$s]" value="%3$s" />',
            esc_attr($key),
            esc_attr(Settings::OPTION),
            esc_attr($value)
        );
        if ($description !== '') {
            echo '<p class="description">'.esc_html($description).'</p>';
        }
        echo '</td></tr>';
    }

    /**
     * Small media picker. It only runs on this settings page.
     */
    private function media_script(): void
    {
        echo '<script>document.getElementById("mh-seo-og-pick")?.addEventListener("click",function(event){event.preventDefault();var frame=wp.media({title:"'.esc_js(__('Default social image', 'mh-seo')).'",multiple:false,library:{type:"image"}});frame.on("select",function(){var file=frame.state().get("selection").first().toJSON();var input=document.getElementById("mh-seo-og");if(input){input.value=file.id;}});frame.open();});</script>';
    }

    /**
     * Everyday names for the checks.
     *
     * @return array<string, string>
     */
    public static function check_labels(): array
    {
        return [
            'keyword_set' => __('Focus keyword is set', 'mh-seo'),
            'keyword_title' => __('Keyword in the SEO title', 'mh-seo'),
            'keyword_description' => __('Keyword in the meta description', 'mh-seo'),
            'keyword_slug' => __('Keyword in the address', 'mh-seo'),
            'keyword_intro' => __('Keyword near the start', 'mh-seo'),
            'keyword_subheading' => __('Keyword in a subheading', 'mh-seo'),
            'keyword_image_alt' => __('Keyword in image alt text', 'mh-seo'),
            'keyword_density' => __('Keyword used a sensible number of times', 'mh-seo'),
            'title_length' => __('SEO title length', 'mh-seo'),
            'description_length' => __('Meta description length', 'mh-seo'),
            'content_length' => __('Content length', 'mh-seo'),
            'internal_links' => __('Links to your own pages', 'mh-seo'),
            'external_links' => __('Link to another site', 'mh-seo'),
            'image_alts' => __('Images have alt text', 'mh-seo'),
            'featured_image' => __('Featured image', 'mh-seo'),
            'short_paragraphs' => __('Short paragraphs', 'mh-seo'),
            'heading_structure' => __('Heading structure', 'mh-seo'),
            'readability' => __('Readability (grade 8 or below)', 'mh-seo'),
            'keyword_unique' => __('Keyword not used on another post', 'mh-seo'),
        ];
    }
}
