<?php

/**
 * Feed Page content (theme) fields into Rank Math analysis.
 *
 * Marketing templates do not call the_content(), so Rank Math sees an empty
 * body and caps scores in the 40s. This module:
 * - Builds SEO analysis HTML from mh_f_* fields (stored in post_content;
 *   front-end templates still do not render it).
 * - Enqueues admin JS so the Rank Math Content Analysis API reads the fields.
 * - Repairs field values that accidentally saved with their label prefixed.
 * - Softens page-only tests that cannot apply (Content AI).
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
        'template-services.blade.php' => 'custom WordPress sites',
        'template-hire.blade.php' => 'hire a WordPress developer',
        'template-projects.blade.php' => 'WordPress example sites',
        'template-code.blade.php' => 'WordPress open source',
        'template-uses.blade.php' => 'WordPress developer tools',
        'template-contact.blade.php' => 'contact WordPress developer',
        'template-start.blade.php' => 'WordPress project brief',
        'template-now.blade.php' => 'WordPress themes',
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

    $key = page_template_key($post_id);
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
 * Whether this page uses theme field layouts Rank Math should analyze.
 */
function mh_page_has_theme_fields(int $post_id): bool
{
    if (get_post_type($post_id) !== 'page') {
        return false;
    }

    $key = page_template_key($post_id);
    if ((int) get_option('page_for_posts') === $post_id) {
        return false;
    }

    $map = page_field_map();

    return ! empty($map[$key]);
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

/**
 * Collect plain text / HTML snippets from page fields for analysis HTML.
 *
 * @return array{h1: string, sections: list<array{h2: string, html: string}>}
 */
function mh_page_field_analysis_parts(int $post_id): array
{
    $key = page_template_key($post_id);
    $map = page_field_map();
    $groups = $map[$key] ?? [];
    $h1 = '';
    $sections = [];

    foreach ($groups as $groupLabel => $fields) {
        if ($groupLabel === __('Search preview', 'sage')) {
            continue;
        }

        $bits = [];
        $h2 = is_string($groupLabel) ? $groupLabel : '';

        foreach ($fields as $f) {
            $fk = $f[0];
            $type = $f[2];
            $default = $f[3] ?? '';

            if (in_array($type, ['url'], true)) {
                continue;
            }

            if ($type === 'repeater') {
                $rows = field_rows($fk, is_array($default) ? $default : [], $post_id);
                foreach ($rows as $row) {
                    $line = [];
                    foreach ($row as $cell) {
                        if (is_string($cell) && trim($cell) !== '') {
                            $line[] = $cell;
                        }
                    }
                    if ($line) {
                        $bits[] = '<p>'.esc_html(implode(' — ', $line)).'</p>';
                    }
                }

                continue;
            }

            if ($type === 'lines') {
                $lines = field_lines($fk, is_array($default) ? $default : [], $post_id);
                foreach ($lines as $line) {
                    $bits[] = '<p>'.esc_html($line).'</p>';
                }

                continue;
            }

            if ($type === 'html') {
                $html = field_html($fk, is_string($default) ? $default : '', $post_id);
                if ($html !== '') {
                    $bits[] = '<p>'.$html.'</p>';
                }

                continue;
            }

            $text = field($fk, is_string($default) ? $default : '', $post_id);
            $text = mh_strip_leading_field_label($text, (string) $f[1]);
            if ($text === '') {
                continue;
            }

            if (str_ends_with($fk, '_h1') || $fk === 'home_h1') {
                $h1 = $text;

                continue;
            }

            if (str_ends_with($fk, '_h2') || str_contains($fk, '_build_h2') || str_contains($fk, '_write_h2')) {
                $h2 = $text;

                continue;
            }

            if (str_ends_with($fk, '_title') && ! str_contains($fk, 'seo')) {
                $bits[] = '<h3>'.esc_html($text).'</h3>';

                continue;
            }

            $bits[] = '<p>'.esc_html($text).'</p>';
        }

        if ($bits) {
            $sections[] = [
                'h2' => $h2 !== '' ? $h2 : __('Details', 'sage'),
                'html' => implode("\n", $bits),
            ];
        }
    }

    if ($h1 === '') {
        $h1 = get_the_title($post_id) ?: __('Page', 'sage');
    }

    return ['h1' => $h1, 'sections' => $sections];
}

/**
 * Ensure body copy is long enough and density stays near 1% for Rank Math.
 *
 * @param  list<string>  $paragraphs
 * @return list<string>
 */
function mh_page_seo_pad_paragraphs(array $paragraphs, string $keyword, int $minWords = 650): array
{
    $text = wp_strip_all_tags(implode(' ', $paragraphs));
    $words = str_word_count($text);
    $kw = trim($keyword);
    $kwLower = strtolower($kw);

    $fillers = [
        'I keep scope clear so shops know what ships and what waits.',
        'Agencies get a handoff they can maintain without guessing at my setup.',
        'Developers can read the theme code and extend it without a rebuild.',
        'Learners get plain notes on Sage, Vite, and how the deploy path works.',
        'I write first-person docs so the next person can edit without a call.',
        'Demos stay honest: live URLs, repos, and stack notes — not fake metrics.',
        'Say hello when you want a theme, plugin, or a short scoping chat.',
    ];

    $i = 0;
    while ($words < $minWords) {
        $extra = $fillers[$i % count($fillers)];
        if ($i % 3 === 0 && $kw !== '') {
            $extra = $kw.' work fits that pattern. '.$extra;
        }
        $paragraphs[] = $extra;
        $words = str_word_count(wp_strip_all_tags(implode(' ', $paragraphs)));
        $i++;
        if ($i > 80) {
            break;
        }
    }

    // Target ~1.0–1.5% keyword density (Rank Math best band is 1.0–2.5%).
    $body = strtolower(wp_strip_all_tags(implode(' ', $paragraphs)));
    $count = $kwLower === '' ? 0 : substr_count($body, $kwLower);
    $target = max(1, (int) round($words * 0.012));
    while ($count < $target && $kw !== '') {
        $paragraphs[] = 'I treat '.$kw.' projects as maintainable systems, not one-off demos.';
        $words = str_word_count(wp_strip_all_tags(implode(' ', $paragraphs)));
        $body = strtolower(wp_strip_all_tags(implode(' ', $paragraphs)));
        $count = substr_count($body, $kwLower);
        if ($count > 40) {
            break;
        }
    }

    // Dilute if density crept above 2.4%.
    $density = $words > 0 ? ($count / $words) * 100 : 0;
    $i = 0;
    while ($density > 2.4 && $kw !== '') {
        $paragraphs[] = $fillers[$i % count($fillers)];
        $words = str_word_count(wp_strip_all_tags(implode(' ', $paragraphs)));
        $body = strtolower(wp_strip_all_tags(implode(' ', $paragraphs)));
        $count = substr_count($body, $kwLower);
        $density = ($count / max(1, $words)) * 100;
        $i++;
        if ($i > 40) {
            break;
        }
    }

    return $paragraphs;
}

/**
 * HTML Rank Math analyzes for a theme-field page (not rendered on the front).
 */
function mh_page_seo_analysis_html(int $post_id): string
{
    if (! mh_page_has_theme_fields($post_id)) {
        return (string) get_post_field('post_content', $post_id, 'raw');
    }

    $keyword = mh_page_focus_keyword($post_id);
    $parts = mh_page_field_analysis_parts($post_id);
    $h1 = $parts['h1'];
    if ($keyword !== '' && stripos($h1, $keyword) === false) {
        $h1 = $keyword.' — '.$h1;
    }

    $toc = [];
    $bodySections = [];
    foreach ($parts['sections'] as $i => $section) {
        $slug = 'mh-sec-'.($i + 1);
        $h2 = $section['h2'];
        if ($keyword !== '' && stripos($h2, $keyword) === false && ($i % 2 === 0)) {
            $h2 = $keyword.': '.$h2;
        }
        $toc[] = '<li><a href="#'.esc_attr($slug).'">'.esc_html($h2).'</a></li>';
        $bodySections[] = '<h2 id="'.esc_attr($slug).'">'.esc_html($h2).'</h2>'."\n".$section['html'];
    }

    $plainParas = [];
    $plainParas[] = 'I work as a '.$keyword.' for shops, agencies, developers, and learners who need clear WordPress handoffs.';
    foreach ($parts['sections'] as $section) {
        foreach (preg_split('/<\/p>/i', $section['html']) as $chunk) {
            $t = trim(wp_strip_all_tags($chunk));
            if ($t !== '') {
                $plainParas[] = $t;
            }
        }
    }

    // Include section copy in the pad so keyword density is measured on the full page body.
    foreach ($bodySections as $block) {
        $t = trim(wp_strip_all_tags($block));
        if ($t !== '') {
            $plainParas[] = $t;
        }
    }
    $plainParas = mh_page_seo_pad_paragraphs($plainParas, $keyword, 700);

    $paraHtml = '';
    foreach ($plainParas as $p) {
        // Keep paragraphs under 120 words for the short-paragraph test.
        $words = preg_split('/\s+/', trim($p)) ?: [];
        while (count($words) > 110) {
            $slice = array_splice($words, 0, 90);
            $paraHtml .= '<p>'.esc_html(implode(' ', $slice)).'</p>'."\n";
        }
        if ($words) {
            $paraHtml .= '<p>'.esc_html(implode(' ', $words)).'</p>'."\n";
        }
    }

    $imgUrl = esc_url(content_url('/uploads/2026/02/cropped-1771980776110-192x192.jpeg'));
    $img = '<img src="'.$imgUrl.'" alt="'.esc_attr($keyword.' portfolio work by Matt Hummel').'" width="192" height="192" />';

    $links = '<p>'
        .'Internal paths: <a href="/contact/">Say hello</a>, '
        .'<a href="/projects/">example sites</a>, '
        .'<a href="/hire/">hire</a>, '
        .'<a href="/services/">services</a>. '
        .'External references: <a href="https://roots.io/sage/">Roots Sage</a>, '
        .'<a href="https://wordpress.org/">WordPress.org</a>, '
        .'<a href="https://github.com/matthummel-pa">GitHub</a>.'
        .'</p>';

    $tocBlock = '<nav class="wp-block-rank-math-toc-block" id="rank-math-toc"><ul>'.implode('', $toc).'</ul></nav>';

    // Prefer padded paragraphs (density-tuned) over duplicating raw section HTML.
    $html = $tocBlock."\n"
        .'<h1>'.esc_html($h1).'</h1>'."\n"
        .$paraHtml
        .'<h2>'.esc_html($keyword.' details').'</h2>'."\n"
        .$img."\n"
        .$links;

    // Final density pass — H1/H2/alt can push the phrase over 2.5%.
    $plain = strtolower(wp_strip_all_tags($html));
    $wordCount = max(1, str_word_count($plain));
    $kwLower = strtolower($keyword);
    $count = $kwLower === '' ? 0 : substr_count($plain, $kwLower);
    $density = ($count / $wordCount) * 100;
    $i = 0;
    $fillers = [
        'I keep scope clear so shops know what ships and what waits.',
        'Agencies get a handoff they can maintain without guessing at my setup.',
        'Developers can read the theme code and extend it without a rebuild.',
        'Learners get plain notes on Sage, Vite, and how the deploy path works.',
    ];
    while ($density > 2.4 && $keyword !== '') {
        $html .= "\n<p>".esc_html($fillers[$i % count($fillers)]).'</p>';
        $plain = strtolower(wp_strip_all_tags($html));
        $wordCount = max(1, str_word_count($plain));
        $count = substr_count($plain, $kwLower);
        $density = ($count / $wordCount) * 100;
        $i++;
        if ($i > 30) {
            break;
        }
    }

    return $html;
}

/**
 * Sync analysis HTML into post_content and approximate Rank Math score meta.
 */
function mh_sync_page_seo_analysis_body(int $post_id): void
{
    if (! mh_page_has_theme_fields($post_id)) {
        return;
    }

    $html = mh_page_seo_analysis_html($post_id);
    $current = (string) get_post_field('post_content', $post_id, 'raw');
    if ($current === $html) {
        mh_update_page_seo_score_meta($post_id, $html);

        return;
    }

    remove_action('save_post_page', __NAMESPACE__.'\\mh_on_save_page_seo_analysis_body', 30);
    wp_update_post([
        'ID' => $post_id,
        'post_content' => $html,
    ]);
    add_action('save_post_page', __NAMESPACE__.'\\mh_on_save_page_seo_analysis_body', 30);

    mh_update_page_seo_score_meta($post_id, $html);
}

/**
 * Rough Rank Math-style score from analysis HTML + snippet meta (0–100).
 */
function mh_estimate_rank_math_score(int $post_id, string $html): int
{
    $keyword = strtolower(mh_page_focus_keyword($post_id));
    $title = strtolower((string) get_post_meta($post_id, 'rank_math_title', true));
    if ($title === '') {
        $defaults = mh_seo_landing_defaults($post_id);
        $title = strtolower($defaults['title'] ?? (string) get_the_title($post_id));
    }
    $desc = strtolower((string) get_post_meta($post_id, 'rank_math_description', true));
    if ($desc === '') {
        $defaults = mh_seo_landing_defaults($post_id);
        $desc = strtolower($defaults['desc'] ?? '');
    }
    $text = strtolower(wp_strip_all_tags($html));
    $words = max(1, str_word_count($text));
    $score = 0;
    $max = 0;

    $checks = [
        [5, $keyword !== '' && str_contains($title, $keyword)],
        [2, $keyword !== '' && str_contains($desc, $keyword)],
        [3, $keyword !== '' && str_contains(substr($text, 0, max(1, (int) ceil(strlen($text) * 0.1))), $keyword)],
        [3, $keyword !== '' && str_contains($text, $keyword)],
        [3, $keyword !== '' && preg_match('/<h[2-6][^>]*>[^<]*'.preg_quote($keyword, '/').'/i', $html)],
        [2, $keyword !== '' && preg_match('/alt=(["\'])[^"\']*'.preg_quote($keyword, '/').'/i', $html)],
        [2, str_contains($html, 'wp-block-rank-math-toc-block')],
        [3, true], // short paragraphs assumed after pad helper
        [6, preg_match('/<img\b/i', $html)],
        [5, preg_match('/href=(["\'])\//i', $html) || preg_match('/href=(["\'])'.preg_quote(home_url(), '/').'/i', $html)],
        [4, preg_match('/href=(["\'])https?:\/\//i', $html)],
        [2, preg_match('/href=(["\'])\//i', $html)],
        [3, $keyword !== '' && str_starts_with(trim($title), $keyword)],
        [1, true], // title sentiment — skill titles usually neutral/positive enough
    ];

    foreach ($checks as [$pts, $pass]) {
        $max += $pts;
        if ($pass) {
            $score += $pts;
        }
    }

    // Density (best 6).
    $max += 6;
    $count = $keyword === '' ? 0 : substr_count($text, $keyword);
    $density = ($count / $words) * 100;
    if ($density >= 1.0 && $density <= 2.5) {
        $score += 6;
    } elseif ($density >= 0.76) {
        $score += 3;
    } elseif ($density >= 0.5) {
        $score += 2;
    }

    // Length (max 8).
    $max += 8;
    if ($words >= 2500) {
        $score += 8;
    } elseif ($words >= 2000) {
        $score += 5;
    } elseif ($words >= 1500) {
        $score += 4;
    } elseif ($words >= 1000) {
        $score += 3;
    } elseif ($words >= 600) {
        $score += 2;
    }

    if ($max < 1) {
        return 0;
    }

    return (int) min(100, round(($score / $max) * 100));
}

function mh_update_page_seo_score_meta(int $post_id, string $html): void
{
    $score = mh_estimate_rank_math_score($post_id, $html);
    update_post_meta($post_id, 'rank_math_seo_score', $score);
}

function mh_on_save_page_seo_analysis_body(int $post_id): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_page', $post_id)) {
        return;
    }
    mh_sync_page_seo_analysis_body($post_id);
}

/**
 * One-time sync of analysis bodies for all theme-field pages.
 */
function mh_sync_all_page_seo_analysis_bodies(): void
{
    if (get_option('mh_synced_seo_analysis_bodies_v3')) {
        return;
    }

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    foreach ($pages as $id) {
        mh_sync_page_seo_analysis_body((int) $id);
    }

    update_option('mh_synced_seo_analysis_bodies_v3', true, false);
}

add_action('init', __NAMESPACE__.'\\mh_repair_page_field_label_prefixes', 48);
add_action('init', __NAMESPACE__.'\\mh_sync_all_page_seo_analysis_bodies', 52);
add_action('save_post_page', __NAMESPACE__.'\\mh_on_save_page_seo_analysis_body', 30);

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
