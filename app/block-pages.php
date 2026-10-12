<?php

declare(strict_types=1);

/**
 * One-time moves from Page content (theme) fields to blocks.
 *
 * Each builder reads a page's saved field values (or the old template's defaults)
 * and returns block markup. The runner saves it as the page's content once, switches
 * the page to the Block page template, and leaves every mh_f_* value in place as a backup.
 * Nothing here runs again for a page that already uses blocks, so edits made in the
 * block editor are never overwritten.
 *
 * This file can be deleted once every site that used the field templates has loaded
 * a release that includes it.
 */

namespace App;

/**
 * Field template → function that builds that page's block markup.
 *
 * @return array<string, callable(int): string>
 */
function mh_block_page_builders(): array
{
    return [
        'front-page.blade.php' => __NAMESPACE__.'\\mh_home_blocks_markup',
    ];
}

/**
 * Pages that used a field template and are not block pages yet.
 *
 * @return list<int>
 */
function mh_block_conversion_page_ids(string $template): array
{
    if ($template === 'front-page.blade.php') {
        $ids = [(int) get_option('page_on_front')];
    } elseif ($template === 'index.blade.php') {
        $ids = [(int) get_option('page_for_posts')];
    } else {
        $ids = get_posts([
            'post_type' => 'page',
            'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
            'numberposts' => 20,
            'no_found_rows' => true,
            'fields' => 'ids',
            'meta_key' => '_wp_page_template',
            'meta_value' => $template,
        ]);
    }

    return array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0 && get_post_type($id) === 'page'));
}

/**
 * Convert every field page that has a builder. Each template is handled once.
 */
function mh_run_block_conversions(): void
{
    $done = (array) get_option('mh_block_pages_done', []);
    $builders = array_diff_key(mh_block_page_builders(), array_flip($done));
    if ($builders === []) {
        return;
    }

    // One request converts at a time. Saving a page can make the site fetch itself, and that request waits its turn.
    if ((int) get_transient('mh_block_pages_lock') > time() - MINUTE_IN_SECONDS) {
        return;
    }
    set_transient('mh_block_pages_lock', time(), MINUTE_IN_SECONDS);

    foreach ($builders as $template => $builder) {
        $ids = mh_block_conversion_page_ids($template);
        foreach ($ids as $id) {
            if (mh_page_uses_blocks($id)) {
                continue;
            }
            $from = $template === 'front-page.blade.php' ? 'template-home.blade.php' : $template;
            if (! mh_convert_page_to_blocks($id, (string) $builder($id), $from)) {
                // The page already had content, or the save failed. It keeps what it has.
                error_log(sprintf('matthummel: page %d (%s) was not converted to blocks.', $id, $template)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- one line when a one-time conversion is skipped.
            }
        }

        // A new install has no pages until the seed has run; try again on the next request.
        if ($ids !== [] || get_option('mh_portfolio_seeded_v2')) {
            $done[] = $template;
        }
    }

    update_option('mh_block_pages_done', array_values(array_unique($done)), true);
    delete_transient('mh_block_pages_lock');
}
add_action('init', __NAMESPACE__.'\\mh_run_block_conversions', 60);

/** A Section block. */
function mh_blocks_section(array $attrs, string $inner): string
{
    return trim($inner) === '' ? '' : mh_blocks_wrap('matthummel/section', $attrs, $inner);
}

/**
 * Home: hero, projects, journal, services, questions, closing band.
 */
function mh_home_blocks_markup(int $id): string
{
    $hero = mh_home_hero();
    $f = static fn (string $key, string $default): string => field($key, $default, $id);

    $buttons = [];
    if ($hero['show_primary']) {
        $buttons[] = ['text' => $hero['cta_primary'], 'url' => $hero['cta_primary_url']];
    }
    if ($hero['show_secondary']) {
        $buttons[] = ['text' => $hero['cta_secondary'], 'url' => $hero['cta_secondary_url'], 'style' => 'arrow'];
    }

    $out = mh_blocks_wrap(
        'matthummel/hero',
        ['layout' => 'home', 'eyebrow' => $hero['eyebrow']],
        mh_blocks_heading($hero['h1'], 1)
        .mh_blocks_paragraph(esc_html($hero['role']))
        .mh_blocks_paragraph(esc_html($hero['subcopy']), 'lead')
        .mh_blocks_buttons($buttons)
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    $out .= mh_blocks_section(
        ['eyebrow' => __('Projects', 'sage'), 'anchor' => 'work', 'navLabel' => __('Projects', 'sage')],
        mh_blocks_heading($f('home_work_h2', __('Selected projects.', 'sage')))
        .mh_blocks_paragraph(esc_html($f('home_work_intro', __('WordPress themes and plugins I built in public. Open one for a short story and a live demo when I have one.', 'sage'))))
        .mh_blocks_wrap('matthummel/featured-projects', [], null)
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('Journal', 'sage'), 'anchor' => 'journal', 'navLabel' => __('Journal', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading($f('home_write_h2', __('Recent writing.', 'sage')))
        .mh_blocks_paragraph(esc_html($f('home_write_intro', __('Notes from WordPress builds — themes, handoffs, and things I want to remember next time.', 'sage'))))
        .mh_blocks_wrap('matthummel/recent-posts', [], null)
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('Services', 'sage'), 'anchor' => 'do', 'navLabel' => __('Services', 'sage')],
        mh_blocks_heading($f('home_build_h2', __('What I do.', 'sage')))
        .mh_blocks_paragraph(esc_html__('For shops and agencies. Stack notes are on About and Code.', 'sage'))
        .mh_blocks_cards([
            ['icon' => 'wordpress', 'title' => $f('home_build_1_title', __('WordPress sites', 'sage')), 'body' => esc_html($f('home_build_1_text', __('Custom themes shops can edit in wp-admin. You own the code.', 'sage')))],
            ['icon' => 'plugins', 'title' => $f('home_build_2_title', __('Plugins & tools', 'sage')), 'body' => esc_html($f('home_build_2_text', __('Small PHP plugins when WordPress needs a new part.', 'sage')))],
            ['icon' => 'code', 'title' => $f('home_build_3_title', __('Full-stack web apps', 'sage')), 'body' => esc_html($f('home_build_3_text', __('Interfaces, services, and APIs when a theme alone is not enough.', 'sage')))],
        ])
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('Questions', 'sage'), 'anchor' => 'questions', 'tone' => 'alt'],
        mh_blocks_heading($f('home_faq_h2', __('Hiring a WordPress developer: common questions.', 'sage')))
        .mh_blocks_paragraph(esc_html($f('home_faq_intro', __('Short answers before you write.', 'sage'))))
        .mh_blocks_faq(mh_page_faq_rows('home_faq', mh_home_faq_defaults(), $id))
    );

    $out .= mh_blocks_wrap(
        'matthummel/cta-band',
        [],
        mh_blocks_heading($f('home_help_h2', __('Want to work together?', 'sage')))
        .mh_blocks_paragraph(field_html('home_help_p2', sprintf(
            /* translators: 1: About page URL, 2: Code section URL. */
            __('Tell me about the role or the site. Background is on <a href="%1$s">About</a>. Repos are on <a href="%2$s">Code</a>.', 'sage'),
            esc_url(home_url('/about/')),
            esc_url(home_url('/about/#code'))
        ), $id))
        .mh_blocks_buttons([['text' => $f('home_link_hello', __('Say hello', 'sage')), 'url' => home_url('/contact/')]])
    );

    return $out;
}
