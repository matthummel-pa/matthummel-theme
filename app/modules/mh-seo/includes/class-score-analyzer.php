<?php

/**
 * The 19 weighted on-page checks.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Scores a post from plain fields. The block editor runs the same checks in JavaScript.
 */
class Score_Analyzer
{
    /**
     * Default weight of each check. Zero means the check is off.
     *
     * @return array<string, int>
     */
    public static function default_weights(): array
    {
        return [
            'keyword_set' => 0,
            'keyword_title' => 8,
            'keyword_description' => 5,
            'keyword_slug' => 4,
            'keyword_intro' => 6,
            'keyword_subheading' => 5,
            'keyword_image_alt' => 3,
            'keyword_density' => 6,
            'title_length' => 6,
            'description_length' => 6,
            'content_length' => 10,
            'internal_links' => 5,
            'external_links' => 3,
            'image_alts' => 5,
            'featured_image' => 5,
            'short_paragraphs' => 4,
            'heading_structure' => 5,
            'readability' => 8,
            'keyword_unique' => 6,
        ];
    }

    /**
     * Check ids, in display order.
     *
     * @return array<int, string>
     */
    public static function check_ids(): array
    {
        return array_keys(self::default_weights());
    }

    /**
     * Default pass and warn bands.
     *
     * @return array<string, float|int>
     */
    public static function default_thresholds(): array
    {
        return [
            'title_pass_min' => 30,
            'title_pass_max' => 60,
            'title_warn_max' => 70,
            'description_pass_min' => 120,
            'description_pass_max' => 160,
            'description_warn_min' => 70,
            'description_warn_max' => 180,
            'content_fail_below' => 600,
            'content_mid' => 1000,
            'content_pass_at' => 1500,
            'density_pass_min' => 0.5,
            'density_pass_max' => 2.5,
            'density_warn_min' => 0.3,
            'density_warn_max' => 3.5,
            'paragraph_words' => 150,
            'readability_pass_max' => 8,
            'readability_warn_max' => 10,
            'internal_links_pass' => 2,
            'intro_percent' => 10,
        ];
    }

    /**
     * Score one post.
     *
     * Expected post keys: seo_title, fallback_title, description, slug, content_html,
     * keyword, has_featured_image, supports_featured_image, site_host, unique (null|bool).
     *
     * @param  array<string, mixed>  $post  Post fields.
     * @param  array<string, int>  $weights  Weight overrides.
     * @param  array<string, mixed>  $thresholds  Threshold overrides.
     * @return array{score: int, results: array<int, array<string, mixed>>, failed: array<int, string>}
     */
    public function analyze(array $post, array $weights = [], array $thresholds = []): array
    {
        $weights = $this->weights($weights);
        $thresholds = array_merge(self::default_thresholds(), $thresholds);
        $keyword = trim((string) ($post['keyword'] ?? ''));
        $title = trim((string) ($post['seo_title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($post['fallback_title'] ?? ''));
        }
        $description = trim((string) ($post['description'] ?? ''));
        $slug = Text::slugify((string) ($post['slug'] ?? ''));
        $prose_html = Text::prose_html((string) ($post['content_html'] ?? ''));
        $plain = Text::plain($prose_html);
        $parsed = $this->parse_html($prose_html);
        $host = $this->host((string) ($post['site_host'] ?? ''));

        $results = [];
        $results[] = $this->keyword_set($keyword, $weights);
        foreach ($this->keyword_results($keyword, $title, $description, $slug, $plain, $parsed, $weights, $thresholds) as $row) {
            $results[] = $row;
        }
        $results[] = $this->title_length($title, $weights, $thresholds);
        $results[] = $this->description_length($description, $weights, $thresholds);
        $results[] = $this->content_length($plain, $weights, $thresholds);
        $results[] = $this->internal_links($parsed['links'], $host, $weights, $thresholds);
        $results[] = $this->external_links($parsed['links'], $host, $weights);
        $results[] = $this->image_alts($parsed['images'], $weights);
        $results[] = $this->featured_image($post, $weights);
        $results[] = $this->short_paragraphs($parsed['paragraphs'], $plain, $weights, $thresholds);
        $results[] = $this->heading_structure($parsed['headings'], $weights);
        $results[] = $this->readability($plain, $weights, $thresholds);
        $results[] = $this->keyword_unique($keyword, $post, $weights);

        $earned = 0.0;
        $possible = 0;
        $failed = [];
        foreach ($results as $row) {
            $weight = (int) $row['weight'];
            if ($row['applies'] && $weight > 0) {
                $possible += $weight;
                $earned += (float) $row['earned'];
            }
            if ($row['applies'] && $row['status'] === 'fail') {
                $failed[] = (string) $row['id'];
            }
        }

        $score = 0;
        if ($possible > 0) {
            $score = (int) round(($earned / $possible) * 100);
        }

        return [
            'score' => max(0, min(100, $score)),
            'results' => $results,
            'failed' => $failed,
        ];
    }

    /**
     * Merge saved weights onto the defaults. Unknown keys are ignored.
     *
     * @param  array<string, mixed>  $weights  Saved weights.
     * @return array<string, int>
     */
    public function weights(array $weights): array
    {
        $merged = self::default_weights();
        foreach ($merged as $id => $default) {
            if (array_key_exists($id, $weights)) {
                $merged[$id] = max(0, (int) $weights[$id]);
            } else {
                $merged[$id] = (int) $default;
            }
        }

        return $merged;
    }

    /**
     * Color band for a score.
     *
     * @param  int  $score  Score from 0 to 100.
     * @return string red, orange, or green.
     */
    public static function color(int $score): string
    {
        if ($score >= 80) {
            return 'green';
        }
        if ($score >= 50) {
            return 'orange';
        }

        return 'red';
    }

    /**
     * Focus keyword gate.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_set(string $keyword, array $weights): array
    {
        $status = $keyword === '' ? 'fail' : 'pass';

        return $this->row('keyword_set', $status, true, $weights, $status === 'pass' ? null : 0.0);
    }

    /**
     * Keyword placement checks. They fail when no keyword is set.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $title  Effective SEO title.
     * @param  string  $description  Meta description.
     * @param  string  $slug  Post slug.
     * @param  string  $plain  Prose text.
     * @param  array<string, mixed>  $parsed  Parsed HTML parts.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<int, array<string, mixed>>
     */
    private function keyword_results(string $keyword, string $title, string $description, string $slug, string $plain, array $parsed, array $weights, array $thresholds): array
    {
        if ($keyword === '') {
            $ids = ['keyword_title', 'keyword_description', 'keyword_slug', 'keyword_intro', 'keyword_subheading', 'keyword_image_alt', 'keyword_density'];
            $rows = [];
            foreach ($ids as $id) {
                $rows[] = $this->row($id, 'fail', true, $weights, 0.0);
            }

            return $rows;
        }

        return [
            $this->keyword_title($keyword, $title, $weights),
            $this->keyword_description($keyword, $description, $weights),
            $this->keyword_slug($keyword, $slug, $weights),
            $this->keyword_intro($keyword, $plain, $weights, $thresholds),
            $this->keyword_subheading($keyword, $parsed['headings'], $weights),
            $this->keyword_image_alt($keyword, $parsed['images'], $weights),
            $this->keyword_density($keyword, $plain, $weights, $thresholds),
        ];
    }

    /**
     * Keyword in the first half of the SEO title, or anywhere as a warning.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $title  SEO title.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_title(string $keyword, string $title, array $weights): array
    {
        $pos = Text::keyword_position($title, $keyword);
        if ($pos < 0) {
            return $this->row('keyword_title', 'fail', true, $weights, 0.0);
        }
        $half = (int) floor(mb_strlen($title) / 2);
        if ($pos < $half) {
            return $this->row('keyword_title', 'pass', true, $weights);
        }

        return $this->row('keyword_title', 'warn', true, $weights);
    }

    /**
     * Keyword in the meta description.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $description  Meta description.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_description(string $keyword, string $description, array $weights): array
    {
        $status = Text::keyword_position($description, $keyword) >= 0 ? 'pass' : 'fail';

        return $this->row('keyword_description', $status, true, $weights, $status === 'pass' ? null : 0.0);
    }

    /**
     * Hyphenated keyword in the slug. Some of the words is a warning.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $slug  Slug.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_slug(string $keyword, string $slug, array $weights): array
    {
        $needle = Text::slugify($keyword);
        if ($needle !== '' && $slug !== '' && str_contains($slug, $needle)) {
            return $this->row('keyword_slug', 'pass', true, $weights);
        }
        $words = array_filter(
            explode('-', $needle),
            static fn (string $word): bool => strlen($word) >= 3
        );
        $hits = 0;
        foreach ($words as $word) {
            if (str_contains($slug, $word)) {
                $hits++;
            }
        }
        if ($hits > 0) {
            return $this->row('keyword_slug', 'warn', true, $weights);
        }

        return $this->row('keyword_slug', 'fail', true, $weights, 0.0);
    }

    /**
     * Keyword in the first portion of the prose.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $plain  Prose.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function keyword_intro(string $keyword, string $plain, array $weights, array $thresholds): array
    {
        $percent = max(1, (int) $thresholds['intro_percent']);
        $length = mb_strlen($plain);
        $slice = $length > 0 ? (int) ceil($length * ($percent / 100)) : 0;
        $slice = max($slice, mb_strlen($keyword));
        $intro = mb_substr($plain, 0, $slice);
        $status = Text::keyword_position($intro, $keyword) >= 0 ? 'pass' : 'fail';

        return $this->row('keyword_intro', $status, true, $weights, $status === 'pass' ? null : 0.0);
    }

    /**
     * Keyword in an H2 or H3.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  array<int, array<string, mixed>>  $headings  Headings.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_subheading(string $keyword, array $headings, array $weights): array
    {
        foreach ($headings as $heading) {
            $level = (int) ($heading['level'] ?? 0);
            if ($level !== 2 && $level !== 3) {
                continue;
            }
            if (Text::keyword_position((string) ($heading['text'] ?? ''), $keyword) >= 0) {
                return $this->row('keyword_subheading', 'pass', true, $weights);
            }
        }

        return $this->row('keyword_subheading', 'fail', true, $weights, 0.0);
    }

    /**
     * Keyword in an image alt attribute.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  array<int, array<string, string>>  $images  Images.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_image_alt(string $keyword, array $images, array $weights): array
    {
        foreach ($images as $image) {
            if (Text::keyword_position((string) ($image['alt'] ?? ''), $keyword) >= 0) {
                return $this->row('keyword_image_alt', 'pass', true, $weights);
            }
        }

        return $this->row('keyword_image_alt', 'fail', true, $weights, 0.0);
    }

    /**
     * Keyword density against the prose word count.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  string  $plain  Prose.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function keyword_density(string $keyword, string $plain, array $weights, array $thresholds): array
    {
        $words = Text::words($plain);
        $total = count($words);
        $hits = Text::keyword_count($plain, $keyword);
        $width = max(1, count(Text::words($keyword)));
        $density = $total > 0 ? (($hits * $width) / $total) * 100 : 0.0;
        $pass_min = (float) $thresholds['density_pass_min'];
        $pass_max = (float) $thresholds['density_pass_max'];
        $warn_min = (float) $thresholds['density_warn_min'];
        $warn_max = (float) $thresholds['density_warn_max'];
        if ($density >= $pass_min && $density <= $pass_max) {
            return $this->row('keyword_density', 'pass', true, $weights);
        }
        if (($density >= $warn_min && $density < $pass_min) || ($density > $pass_max && $density <= $warn_max)) {
            return $this->row('keyword_density', 'warn', true, $weights);
        }

        return $this->row('keyword_density', 'fail', true, $weights, 0.0);
    }

    /**
     * SEO title length.
     *
     * @param  string  $title  Title.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function title_length(string $title, array $weights, array $thresholds): array
    {
        $length = mb_strlen($title);
        if ($length < 1) {
            return $this->row('title_length', 'fail', true, $weights, 0.0);
        }
        $min = (int) $thresholds['title_pass_min'];
        $max = (int) $thresholds['title_pass_max'];
        $warn = (int) $thresholds['title_warn_max'];
        if ($length >= $min && $length <= $max) {
            return $this->row('title_length', 'pass', true, $weights);
        }
        if ($length < $min || ($length > $max && $length <= $warn)) {
            return $this->row('title_length', 'warn', true, $weights);
        }

        return $this->row('title_length', 'fail', true, $weights, 0.0);
    }

    /**
     * Meta description length.
     *
     * @param  string  $description  Description.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function description_length(string $description, array $weights, array $thresholds): array
    {
        $length = mb_strlen($description);
        if ($length < 1) {
            return $this->row('description_length', 'fail', true, $weights, 0.0);
        }
        $pass_min = (int) $thresholds['description_pass_min'];
        $pass_max = (int) $thresholds['description_pass_max'];
        $warn_min = (int) $thresholds['description_warn_min'];
        $warn_max = (int) $thresholds['description_warn_max'];
        if ($length >= $pass_min && $length <= $pass_max) {
            return $this->row('description_length', 'pass', true, $weights);
        }
        if (($length >= $warn_min && $length < $pass_min) || ($length > $pass_max && $length <= $warn_max)) {
            return $this->row('description_length', 'warn', true, $weights);
        }

        return $this->row('description_length', 'fail', true, $weights, 0.0);
    }

    /**
     * Content length. Between the warn floor and the pass mark, points scale up.
     *
     * @param  string  $plain  Prose.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function content_length(string $plain, array $weights, array $thresholds): array
    {
        $count = count(Text::words($plain));
        $fail = (int) $thresholds['content_fail_below'];
        $mid = (int) $thresholds['content_mid'];
        $pass = (int) $thresholds['content_pass_at'];
        $weight = (int) $weights['content_length'];
        if ($count >= $pass) {
            return $this->row('content_length', 'pass', true, $weights);
        }
        if ($count < $fail) {
            return $this->row('content_length', 'fail', true, $weights, 0.0);
        }
        if ($count <= $mid) {
            $span = max(1, $mid - $fail);
            $ratio = 0.5 + (($count - $fail) / $span) * 0.25;
        } else {
            $span = max(1, $pass - $mid);
            $ratio = 0.75 + (($count - $mid) / $span) * 0.25;
        }

        return $this->row('content_length', 'warn', true, $weights, $weight * $ratio);
    }

    /**
     * Links to this site.
     *
     * @param  array<int, string>  $links  Link hrefs.
     * @param  string  $host  Site host.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function internal_links(array $links, string $host, array $weights, array $thresholds): array
    {
        $internal = 0;
        foreach ($links as $href) {
            $kind = $this->link_kind($href, $host);
            if ($kind === 'internal') {
                $internal++;
            }
        }
        $pass = (int) $thresholds['internal_links_pass'];
        if ($internal >= $pass) {
            return $this->row('internal_links', 'pass', true, $weights);
        }
        if ($internal === 1) {
            return $this->row('internal_links', 'warn', true, $weights);
        }

        return $this->row('internal_links', 'fail', true, $weights, 0.0);
    }

    /**
     * Links to other sites.
     *
     * @param  array<int, string>  $links  Link hrefs.
     * @param  string  $host  Site host.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function external_links(array $links, string $host, array $weights): array
    {
        foreach ($links as $href) {
            if ($this->link_kind($href, $host) === 'external') {
                return $this->row('external_links', 'pass', true, $weights);
            }
        }

        return $this->row('external_links', 'fail', true, $weights, 0.0);
    }

    /**
     * At least one image, and every image has alt text.
     *
     * @param  array<int, array<string, string>>  $images  Images.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function image_alts(array $images, array $weights): array
    {
        if ($images === []) {
            return $this->row('image_alts', 'fail', true, $weights, 0.0);
        }
        foreach ($images as $image) {
            if (trim((string) ($image['alt'] ?? '')) === '') {
                return $this->row('image_alts', 'warn', true, $weights);
            }
        }

        return $this->row('image_alts', 'pass', true, $weights);
    }

    /**
     * Featured image. Skipped for post types that do not support one.
     *
     * @param  array<string, mixed>  $post  Post fields.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function featured_image(array $post, array $weights): array
    {
        $supports = ! array_key_exists('supports_featured_image', $post) || ! empty($post['supports_featured_image']);
        if (! $supports) {
            return $this->row('featured_image', 'pass', false, $weights, 0.0);
        }
        $status = ! empty($post['has_featured_image']) ? 'pass' : 'fail';

        return $this->row('featured_image', $status, true, $weights, $status === 'pass' ? null : 0.0);
    }

    /**
     * Paragraphs over the word limit.
     *
     * @param  array<int, string>  $paragraphs  Paragraph texts.
     * @param  string  $plain  Full prose, used when there are no paragraphs.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function short_paragraphs(array $paragraphs, string $plain, array $weights, array $thresholds): array
    {
        $limit = (int) $thresholds['paragraph_words'];
        $long = 0;
        $chunks = $paragraphs;
        if ($chunks === [] && $plain !== '') {
            $chunks = [$plain];
        }
        foreach ($chunks as $chunk) {
            if (count(Text::words($chunk)) > $limit) {
                $long++;
            }
        }
        if ($long >= 3) {
            return $this->row('short_paragraphs', 'fail', true, $weights, 0.0);
        }
        if ($long >= 1) {
            return $this->row('short_paragraphs', 'warn', true, $weights);
        }

        return $this->row('short_paragraphs', 'pass', true, $weights);
    }

    /**
     * Heading levels should not skip, and the body should not contain an H1.
     *
     * @param  array<int, array<string, mixed>>  $headings  Headings in document order.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function heading_structure(array $headings, array $weights): array
    {
        $skips = 0;
        $has_h1 = false;
        $last = 1;
        foreach ($headings as $heading) {
            $level = (int) ($heading['level'] ?? 0);
            if ($level < 1) {
                continue;
            }
            if ($level === 1) {
                $has_h1 = true;
            }
            if ($level > $last + 1) {
                $skips += $level - $last - 1;
            }
            $last = $level;
        }
        if ($has_h1 || $skips >= 2) {
            return $this->row('heading_structure', 'fail', true, $weights, 0.0);
        }
        if ($skips === 1) {
            return $this->row('heading_structure', 'warn', true, $weights);
        }

        return $this->row('heading_structure', 'pass', true, $weights);
    }

    /**
     * Flesch–Kincaid grade. The default pass mark is grade 8.
     *
     * @param  string  $plain  Prose.
     * @param  array<string, int>  $weights  Weights.
     * @param  array<string, mixed>  $thresholds  Thresholds.
     * @return array<string, mixed>
     */
    private function readability(string $plain, array $weights, array $thresholds): array
    {
        $grade = Text::flesch_kincaid_grade($plain);
        if ($grade === null) {
            return $this->row('readability', 'pass', false, $weights, 0.0);
        }
        $pass = (float) $thresholds['readability_pass_max'];
        $warn = (float) $thresholds['readability_warn_max'];
        if ($grade <= $pass) {
            return $this->row('readability', 'pass', true, $weights);
        }
        if ($grade <= $warn) {
            return $this->row('readability', 'warn', true, $weights);
        }

        return $this->row('readability', 'fail', true, $weights, 0.0);
    }

    /**
     * Whether another published post already uses this keyword.
     *
     * @param  string  $keyword  Focus keyword.
     * @param  array<string, mixed>  $post  Post fields. `unique` is null, true, or false.
     * @param  array<string, int>  $weights  Weights.
     * @return array<string, mixed>
     */
    private function keyword_unique(string $keyword, array $post, array $weights): array
    {
        if ($keyword === '' || ! array_key_exists('unique', $post) || $post['unique'] === null) {
            return $this->row('keyword_unique', 'pass', false, $weights, 0.0);
        }
        $status = ! empty($post['unique']) ? 'pass' : 'fail';

        return $this->row('keyword_unique', $status, true, $weights, $status === 'pass' ? null : 0.0);
    }

    /**
     * Build one check result. A warning earns half the weight unless $earned is set.
     *
     * @param  string  $id  Check id.
     * @param  string  $status  pass, warn, or fail.
     * @param  bool  $applies  Whether the check counts.
     * @param  array<string, int>  $weights  Weights.
     * @param  float|null  $earned  Explicit points. Null uses pass = full and warn = half.
     * @return array<string, mixed>
     */
    private function row(string $id, string $status, bool $applies, array $weights, ?float $earned = null): array
    {
        $weight = (int) ($weights[$id] ?? 0);
        if ($earned === null) {
            if ($status === 'pass') {
                $earned = (float) $weight;
            } elseif ($status === 'warn') {
                $earned = $weight / 2;
            } else {
                $earned = 0.0;
            }
        }

        return [
            'id' => $id,
            'status' => $status,
            'applies' => $applies,
            'weight' => $weight,
            'earned' => $earned,
        ];
    }

    /**
     * Headings, images, links, and paragraphs from prose HTML.
     *
     * @param  string  $html  Prose HTML.
     * @return array{headings: array<int, array<string, mixed>>, images: array<int, array<string, string>>, links: array<int, string>, paragraphs: array<int, string>}
     */
    private function parse_html(string $html): array
    {
        $empty = [
            'headings' => [],
            'images' => [],
            'links' => [],
            'paragraphs' => [],
        ];
        if (trim($html) === '') {
            return $empty;
        }
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return $empty;
        }
        $xpath = new \DOMXPath($doc);
        foreach ($this->nodes($xpath, '//h1|//h2|//h3|//h4|//h5|//h6') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $empty['headings'][] = [
                'level' => (int) substr($node->tagName, 1), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
                'text' => trim((string) $node->textContent), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
            ];
        }
        foreach ($this->nodes($xpath, '//img') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $empty['images'][] = [
                'alt' => trim($node->getAttribute('alt')),
                'src' => trim($node->getAttribute('src')),
            ];
        }
        foreach ($this->nodes($xpath, '//a[@href]') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $href = trim($node->getAttribute('href'));
            if ($href !== '') {
                $empty['links'][] = $href;
            }
        }
        foreach ($this->nodes($xpath, '//p') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $text = trim((string) preg_replace('/\s+/u', ' ', (string) $node->textContent)); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API.
            if ($text !== '') {
                $empty['paragraphs'][] = $text;
            }
        }

        return $empty;
    }

    /**
     * Query nodes, or an empty list when the query fails.
     *
     * @param  \DOMXPath  $xpath  Query object.
     * @param  string  $query  XPath query.
     * @return array<int, \DOMNode>
     */
    private function nodes(\DOMXPath $xpath, string $query): array
    {
        $found = $xpath->query($query);
        if ($found === false) {
            return [];
        }
        $list = [];
        foreach ($found as $node) {
            $list[] = $node;
        }

        return $list;
    }

    /**
     * Classify a href as internal, external, or ignore.
     *
     * @param  string  $href  Link href.
     * @param  string  $host  Site host, without www.
     */
    private function link_kind(string $href, string $host): string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return 'ignore';
        }
        $lower = strtolower($href);
        if (str_starts_with($lower, 'mailto:') || str_starts_with($lower, 'tel:') || str_starts_with($lower, 'javascript:')) {
            return 'ignore';
        }
        if (str_starts_with($href, '/') || str_starts_with($href, '?') || str_starts_with($href, './') || str_starts_with($href, '../')) {
            return 'internal';
        }
        $parsed = wp_parse_url($href, PHP_URL_HOST);
        $link_host = $this->host(is_string($parsed) ? $parsed : '');
        if ($link_host === '') {
            return 'internal';
        }
        if ($host !== '' && $link_host === $host) {
            return 'internal';
        }

        return 'external';
    }

    /**
     * Host name without a leading www.
     *
     * @param  string  $host  Host.
     */
    private function host(string $host): string
    {
        $host = strtolower(trim($host));
        $host = (string) preg_replace('/:\d+$/', '', $host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }
}
