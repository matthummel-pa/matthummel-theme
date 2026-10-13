<?php

/**
 * Decides the title, description, robots, and canonical for one request.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Turns a request context into the tags search engines should see.
 *
 * A custom SEO title is returned exactly as stored. Resolution order is the
 * MH SEO field, the Rank Math field, the saved theme field, then generated
 * defaults (the theme's built-in landing copy, then the title template).
 */
class Document_Builder
{
    /**
     * Build the document for a request.
     *
     * @param  array<string, mixed>  $context  Request context. See Context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     * @return array<string, mixed>
     */
    public function build(array $context, array $settings): array
    {
        $title = $this->title($context, $settings);
        $description = $this->description($context);
        $noindex = $this->is_noindex($context, $settings);
        $canonical = $noindex ? '' : $this->canonical($context);
        $schema = (new Schema)->graph($context, $settings, $title, $description);
        $advanced = is_array($context['robots_advanced'] ?? null) ? $context['robots_advanced'] : [];

        return [
            'title' => $title,
            'description' => $description,
            'noindex' => $noindex,
            'robots' => $this->robots($noindex, $advanced),
            'robots_advanced' => $advanced,
            'canonical' => $canonical,
            'emit_canonical' => ! $noindex && empty($context['core_prints_canonical']),
            'og' => $this->open_graph($context, $title, $description, $canonical),
            'twitter' => $this->twitter($context, $settings, $title, $description),
            'tags' => $this->article_tags($context),
            'schema' => $schema,
        ];
    }

    /**
     * The document title.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     */
    public function title(array $context, array $settings): string
    {
        $custom = trim((string) ($context['custom_title'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $rank = trim((string) ($context['rank_math_title'] ?? ''));
        if ($rank !== '' && ! str_contains($rank, '%')) {
            return $rank;
        }

        $field = trim((string) ($context['field_title'] ?? ''));
        if ($field !== '') {
            return $this->with_page($field, $context);
        }

        $landing = trim((string) ($context['landing_title'] ?? ''));
        if ($landing !== '') {
            return $this->with_page($landing, $context);
        }

        $name = trim((string) ($context['document_name'] ?? ''));
        $place = trim((string) ($context['project_place'] ?? ''));
        if ($place !== '' && $name !== '') {
            $name .= ' — '.$place;
        }

        $template = trim((string) ($settings['title_template'] ?? ''));
        if ($template === '') {
            $template = '%title% | Matt Hummel';
        }
        $site = trim((string) ($context['site_name'] ?? 'Matt Hummel'));
        $title = strtr(
            $template,
            [
                '%title%' => $name,
                '%sep%' => '|',
                '%sitename%' => $site,
            ]
        );
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));

        return $this->with_page($title, $context);
    }

    /**
     * The meta description.
     *
     * Stored descriptions, including the MH SEO term description, are not clipped.
     * A term's own description is stripped and trimmed to about 155 characters.
     *
     * @param  array<string, mixed>  $context  Request context.
     */
    public function description(array $context): string
    {
        $custom = trim((string) ($context['custom_description'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $rank = trim((string) ($context['rank_math_description'] ?? ''));
        if ($rank !== '' && ! str_contains($rank, '%')) {
            return $rank;
        }

        $field = trim((string) ($context['field_description'] ?? ''));
        if ($field !== '') {
            return $field;
        }

        $archive = $this->term_archive_description($context);
        if ($archive !== '') {
            return $archive;
        }

        $landing = trim((string) ($context['landing_description'] ?? ''));
        if ($landing !== '') {
            return $landing;
        }

        $summary = trim((string) ($context['project_summary'] ?? ''));
        if ($summary !== '') {
            return self::trim_chars($summary, 155);
        }

        $excerpt = trim((string) ($context['excerpt'] ?? ''));
        if ($excerpt !== '') {
            return self::trim_chars($excerpt, 155);
        }

        $content = trim((string) ($context['content_text'] ?? ''));
        if ($content !== '') {
            return self::trim_chars($content, 155);
        }

        return '';
    }

    /**
     * Description for a category, tag, or taxonomy archive.
     *
     * The MH SEO term description is used whole. The term's own description
     * is plain text, cut on a word boundary.
     *
     * @param  array<string, mixed>  $context  Request context.
     */
    private function term_archive_description(array $context): string
    {
        $view = (string) ($context['view'] ?? '');
        if (! in_array($view, ['category', 'tag', 'tax'], true)) {
            return '';
        }

        $stored = trim((string) ($context['term_seo_description'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $own = Text::plain((string) ($context['term_description'] ?? ''));
        if ($own === '') {
            return '';
        }

        return self::trim_chars($own, 155);
    }

    /**
     * Whether this request should be noindex, follow.
     *
     * An explicit MH SEO choice wins. Otherwise an existing Rank Math noindex is kept.
     * Unsubscribe pages stay noindex because their Rank Math value is read here and never rewritten.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     */
    public function is_noindex(array $context, array $settings): bool
    {
        $view = (string) ($context['view'] ?? '');
        $rules = [
            'search' => 'noindex_search',
            'author' => 'noindex_author',
            'tag' => 'noindex_tag',
            'date' => 'noindex_date',
            'attachment' => 'noindex_attachment',
            '404' => 'noindex_404',
        ];
        if (isset($rules[$view]) && ! empty($settings[$rules[$view]])) {
            return true;
        }
        if (! empty($context['term_empty']) && ! empty($settings['noindex_empty_tax'])) {
            return true;
        }

        $flag = $context['noindex_flag'] ?? null;
        if ($flag === true || $flag === '1' || $flag === 1) {
            return true;
        }
        if ($flag === false || $flag === '0' || $flag === 0) {
            return false;
        }

        return ! empty($context['rank_math_noindex']);
    }

    /**
     * Robots content value.
     *
     * @param  bool  $noindex  Whether the page is noindex.
     * @param  array<string, bool|string>  $advanced  Per-post robots choices. See Context.
     */
    public function robots(bool $noindex, array $advanced = []): string
    {
        $parts = [$noindex ? 'noindex' : 'index'];
        $parts[] = ! empty($advanced['nofollow']) ? 'nofollow' : 'follow';
        foreach (['noarchive', 'nosnippet', 'noimageindex'] as $flag) {
            if (! empty($advanced[$flag])) {
                $parts[] = $flag;
            }
        }
        foreach (self::robots_limits($advanced) as $name => $value) {
            $parts[] = $name.':'.$value;
        }

        return implode(', ', $parts);
    }

    /**
     * The three preview limits, with the site defaults filled in.
     *
     * @param  array<string, bool|string>  $advanced  Per-post robots choices.
     * @return array<string, string>
     */
    public static function robots_limits(array $advanced): array
    {
        $limits = [
            'max-image-preview' => 'large',
            'max-snippet' => '-1',
            'max-video-preview' => '-1',
        ];
        foreach ($limits as $name => $default) {
            $value = trim((string) ($advanced[$name] ?? ''));
            if ($value !== '') {
                $limits[$name] = $value;
            }
        }

        return $limits;
    }

    /**
     * Canonical URL, or an empty string when the page is noindex.
     *
     * @param  array<string, mixed>  $context  Request context.
     */
    public function canonical(array $context): string
    {
        $custom = trim((string) ($context['canonical'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return trim((string) ($context['url'] ?? ''));
    }

    /**
     * Trim on a word boundary. Used for generated descriptions, not stored ones.
     *
     * @param  string  $text  Text.
     * @param  int  $max  Maximum characters.
     */
    public static function trim_chars(string $text, int $max): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $slice = mb_substr($text, 0, $max);
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space > 40) {
            $slice = mb_substr($slice, 0, $space);
        }

        return rtrim($slice, " \t,;:-");
    }

    /**
     * Append a page label for paged archives. Custom titles are not passed through here.
     *
     * @param  string  $title  Fallback title.
     * @param  array<string, mixed>  $context  Request context.
     */
    private function with_page(string $title, array $context): string
    {
        $label = trim((string) ($context['page_label'] ?? ''));
        if ($label === '' || (int) ($context['paged'] ?? 1) < 2) {
            return $title;
        }

        return $title.' | '.$label;
    }

    /**
     * Tag names for article:tag, only on article pages.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @return array<int, string>
     */
    private function article_tags(array $context): array
    {
        $view = (string) ($context['view'] ?? '');
        if ($view !== 'singular') {
            return [];
        }
        $tags = $context['tags'] ?? [];
        if (! is_array($tags)) {
            return [];
        }
        $clean = [];
        foreach ($tags as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $clean[] = $tag;
            }
        }

        return $clean;
    }

    /**
     * Open Graph tags.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $title  Resolved title.
     * @param  string  $description  Resolved description.
     * @param  string  $canonical  Canonical URL.
     * @return array<string, string>
     */
    private function open_graph(array $context, string $title, string $description, string $canonical): array
    {
        $view = (string) ($context['view'] ?? '');
        $type = 'website';
        if ($view === 'singular') {
            $type = 'article';
        } elseif ($view === 'author') {
            $type = 'profile';
        }
        $url = $canonical !== '' ? $canonical : (string) ($context['url'] ?? '');
        $own_title = trim((string) ($context['og_title'] ?? ''));
        if ($own_title !== '') {
            $title = $own_title;
        }
        $own_description = trim((string) ($context['og_description'] ?? ''));
        if ($own_description !== '') {
            $description = $own_description;
        }
        $tags = [
            'og:locale' => (string) ($context['locale'] ?? 'en_US'),
            'og:type' => $type,
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $url,
            'og:site_name' => (string) ($context['site_name'] ?? ''),
        ];
        $image = is_array($context['image'] ?? null) ? $context['image'] : [];
        if (! empty($image['url'])) {
            $tags['og:image'] = (string) $image['url'];
            if (str_starts_with((string) $image['url'], 'https://')) {
                $tags['og:image:secure_url'] = (string) $image['url'];
            }
            if (! empty($image['width'])) {
                $tags['og:image:width'] = (string) (int) $image['width'];
            }
            if (! empty($image['height'])) {
                $tags['og:image:height'] = (string) (int) $image['height'];
            }
            if (! empty($image['alt'])) {
                $tags['og:image:alt'] = (string) $image['alt'];
            }
            if (! empty($image['mime'])) {
                $tags['og:image:type'] = (string) $image['mime'];
            }
        }
        if ($type === 'article') {
            if (! empty($context['published'])) {
                $tags['article:published_time'] = (string) $context['published'];
            }
            if (! empty($context['modified'])) {
                $tags['article:modified_time'] = (string) $context['modified'];
            }
            if (! empty($context['section'])) {
                $tags['article:section'] = (string) $context['section'];
            }
        }

        return $tags;
    }

    /**
     * Twitter card tags. Site and creator are included only when a handle is saved.
     *
     * Each field falls back from the X value to the Open Graph value to the search value.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     * @param  string  $title  Resolved title.
     * @param  string  $description  Resolved description.
     * @return array<string, string>
     */
    private function twitter(array $context, array $settings, string $title, string $description): array
    {
        $image = is_array($context['image'] ?? null) ? $context['image'] : [];
        $own_image = is_array($context['twitter_image'] ?? null) ? $context['twitter_image'] : [];
        if (! empty($own_image['url'])) {
            $image = $own_image;
        }
        foreach (['twitter_title', 'og_title'] as $key) {
            $own = trim((string) ($context[$key] ?? ''));
            if ($own !== '') {
                $title = $own;
                break;
            }
        }
        foreach (['twitter_description', 'og_description'] as $key) {
            $own = trim((string) ($context[$key] ?? ''));
            if ($own !== '') {
                $description = $own;
                break;
            }
        }
        $card = ! empty($image['url']) ? 'summary_large_image' : 'summary';
        $own_card = (string) ($context['twitter_card'] ?? '');
        if (in_array($own_card, ['summary', 'summary_large_image'], true)) {
            $card = $own_card;
        }
        $tags = [
            'twitter:card' => $card,
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];
        if (! empty($image['url'])) {
            $tags['twitter:image'] = (string) $image['url'];
        }
        $site = trim((string) ($settings['twitter_site'] ?? ''));
        if ($site !== '') {
            $tags['twitter:site'] = $site;
            $tags['twitter:creator'] = $site;
        }

        return $tags;
    }
}
