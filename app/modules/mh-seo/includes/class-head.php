<?php

/**
 * Front-end head tags.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Prints one set of head tags and fills the document title.
 */
class Head
{
    /**
     * Document for this request.
     *
     * @var array<string, mixed>|null
     */
    private ?array $document = null;

    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('wp', [$this, 'unhook_theme'], 0);
        add_filter('pre_get_document_title', [$this, 'filter_title'], 20);
        add_filter('document_title_separator', [$this, 'separator']);
        add_filter('wp_robots', [$this, 'filter_robots']);
        add_filter('get_canonical_url', [$this, 'filter_canonical'], 10, 2);
        add_action('wp_head', [$this, 'print_tags'], 1);
        add_filter('robots_txt', [$this, 'robots_txt'], 20, 2);
    }

    /**
     * Drop the theme's title and description output before the head is printed.
     *
     * The theme registers App\mh_filter_document_title on document_title at
     * priority 99, and App\mh_print_meta_description on wp_head at priority 1.
     * Removing them from inside wp_head is too late, and a leading backslash
     * does not match the registered callback.
     */
    public function unhook_theme(): void
    {
        if (! $this->active()) {
            return;
        }
        $title = 'App\\mh_filter_document_title';
        remove_filter('document_title', $title, 99);
        remove_filter('pre_get_document_title', $title, 99);
        remove_filter('document_title_parts', $title, 99);
        remove_filter('wpseo_title', $title, 99);
        remove_filter('rank_math/frontend/title', $title, 99);
        remove_filter('aioseo_title', $title, 99);
        remove_action('wp_head', 'App\\mh_print_meta_description', 1);
    }

    /**
     * Replace the document title when MH SEO is in charge.
     *
     * This runs before the theme's document_title filter, so a custom title
     * cannot be suffixed or clipped by the theme.
     *
     * @param  string  $title  Title WordPress already built.
     */
    public function filter_title($title): string
    {
        if (! $this->active()) {
            return (string) $title;
        }

        return (string) $this->document()['title'];
    }

    /**
     * Use the same separator the live site already shows.
     *
     * @param  string  $separator  Current separator.
     */
    public function separator($separator): string
    {
        if (! mh_seo_is_managing_head()) {
            return (string) $separator;
        }

        return '|';
    }

    /**
     * Robots directives. Core prints the tag.
     *
     * @param  array<string, bool|string>  $robots  Existing directives.
     * @return array<string, bool|string>
     */
    public function filter_robots(array $robots): array
    {
        if (! $this->active()) {
            return $robots;
        }

        return self::merge_robots($robots, ! empty($this->document()['noindex']));
    }

    /**
     * Merge document robots with directives already on the request.
     *
     * Nofollow wins, so a paired follow directive is dropped. max-image-preview,
     * max-snippet, and max-video-preview stay.
     *
     * @param  array<string, bool|string>  $robots  Existing directives.
     * @param  bool  $noindex  Whether this document is noindex.
     * @return array<string, bool|string>
     */
    public static function merge_robots(array $robots, bool $noindex): array
    {
        $robots['max-image-preview'] = 'large';
        $robots['max-snippet'] = '-1';
        $robots['max-video-preview'] = '-1';
        if ($noindex) {
            $robots['noindex'] = true;
            unset($robots['index']);
        } else {
            unset($robots['noindex']);
        }
        if (! empty($robots['nofollow'])) {
            $robots['nofollow'] = true;
            unset($robots['follow']);
        } else {
            unset($robots['nofollow']);
            $robots['follow'] = true;
        }

        return $robots;
    }

    /**
     * Custom canonical for singular URLs. Core prints the tag.
     *
     * @param  string  $url  Canonical WordPress chose.
     * @param  \WP_Post|null  $post  Post.
     */
    public function filter_canonical($url, $post): string
    {
        if (! $this->active()) {
            return (string) $url;
        }
        if ($post instanceof \WP_Post) {
            $custom = trim((string) get_post_meta($post->ID, '_mh_seo_canonical', true));
            if ($custom === '') {
                $custom = trim((string) get_post_meta($post->ID, 'rank_math_canonical_url', true));
            }
            if ($custom !== '') {
                return $custom;
            }
        }

        return (string) $url;
    }

    /**
     * Print description, social tags, archive canonicals, and JSON-LD.
     */
    public function print_tags(): void
    {
        if (! $this->active()) {
            return;
        }
        $this->unhook_theme();
        $document = $this->document();
        if (! empty($document['noindex'])) {
            remove_action('wp_head', 'rel_canonical');
        }
        $filtered = apply_filters('mh_seo_document', $document);
        if (is_array($filtered)) {
            $document = $filtered;
        }
        $schema = apply_filters('mh_seo_schema_graph', $document['schema'] ?? [], $document);
        if (is_array($schema)) {
            $document['schema'] = $schema;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Head_Renderer escapes every attribute and encodes JSON with hex flags.
        echo (new Head_Renderer)->render($document);
    }

    /**
     * Virtual robots.txt. This only runs if the host lets the request reach WordPress.
     *
     * @param  string  $output  Existing robots.txt body.
     * @param  bool  $is_public  Whether the site is public. Unused.
     */
    public function robots_txt($output, $is_public): string
    {
        unset($is_public);
        $output = (string) $output;
        if (Sitemap::is_enabled() && ! mh_seo_is_managing_head()) {
            return $this->with_sitemap_line($output, home_url('/sitemap_index.xml'));
        }
        if (! mh_seo_is_managing_head()) {
            return $output;
        }
        $sitemap = Sitemap::is_enabled() ? home_url('/sitemap_index.xml') : home_url('/wp-sitemap.xml');
        $lines = [
            'User-agent: *',
            'Disallow: /wp-admin/',
            'Allow: /wp-admin/admin-ajax.php',
            '',
            'Sitemap: '.$sitemap,
        ];
        $extra = trim((string) (Settings::get()['robots_extra'] ?? ''));
        if ($extra !== '') {
            $lines[] = '';
            $lines[] = $extra;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Point robots.txt at one sitemap without replacing the rest of the file.
     *
     * @param  string  $output  Existing robots.txt body.
     * @param  string  $sitemap  Absolute sitemap index URL.
     */
    private function with_sitemap_line(string $output, string $sitemap): string
    {
        $line = 'Sitemap: '.$sitemap;
        if (preg_match('/^Sitemap:/m', $output)) {
            $replaced = preg_replace('/^Sitemap:.*$/m', $line, $output);

            return is_string($replaced) ? $replaced : $output;
        }

        return rtrim($output)."\n".$line."\n";
    }

    /**
     * Whether to change this request's head.
     */
    private function active(): bool
    {
        if (! mh_seo_is_managing_head()) {
            return false;
        }
        if (is_admin() || is_feed() || is_embed() || is_trackback()) {
            return false;
        }

        return true;
    }

    /**
     * Build the document once per request.
     *
     * @return array<string, mixed>
     */
    private function document(): array
    {
        if ($this->document === null) {
            $this->document = (new Document_Builder)->build(
                (new Context)->from_query(),
                Settings::get()
            );
        }

        return $this->document;
    }
}
