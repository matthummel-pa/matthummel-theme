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
        'template-about.blade.php' => __NAMESPACE__.'\\mh_about_blocks_markup',
        'template-projects.blade.php' => __NAMESPACE__.'\\mh_projects_blocks_markup',
        'index.blade.php' => __NAMESPACE__.'\\mh_journal_blocks_markup',
        'template-resources.blade.php' => __NAMESPACE__.'\\mh_resources_blocks_markup',
        'template-contact.blade.php' => __NAMESPACE__.'\\mh_contact_blocks_markup',
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

/**
 * A data block with its settings. Unlike mh_blocks_wrap(), a setting that is switched off is kept.
 *
 * @param  string  $slug  Folder name in resources/blocks, e.g. "github-repos".
 * @param  array<string, mixed>  $attrs  Block attributes.
 */
function mh_blocks_data(string $slug, array $attrs = []): string
{
    return '<!-- wp:matthummel/'.sanitize_key($slug).($attrs === [] ? '' : ' '.serialize_block_attributes($attrs))." /-->\n\n";
}

/**
 * One Card with a heading and any blocks under it.
 *
 * @param  array<string, mixed>  $attrs  Card attributes (`icon`, `number`).
 * @param  string  $inner  Block markup that follows the heading.
 */
function mh_blocks_card(string $title, string $inner, array $attrs = []): string
{
    return trim($title) === '' ? '' : mh_blocks_wrap('matthummel/card', $attrs, mh_blocks_heading($title, 3).$inner);
}

/**
 * About: hero, story, now, services, hire, resume, hiring facts, process, fit, approach, code, stack, questions, links, closing band.
 */
function mh_about_blocks_markup(int $id): string
{
    $f = static fn (string $key, string $default): string => field($key, $default, $id);
    $text = static fn (string $value, string $style = ''): string => mh_blocks_paragraph(esc_html($value), $style);
    $contact = home_url('/contact/');
    $login = mh_github_login();
    $linkedin = LinkedIn::profileUrl();
    $booking = mh_booking_url();
    $bookingButton = $booking !== '' ? [['text' => __('Book a 15-minute call', 'sage'), 'url' => $booking, 'style' => 'outline']] : [];

    // Hero.
    $out = mh_blocks_wrap(
        'matthummel/hero',
        [
            'eyebrow' => $f('about_kicker', __('Matt Hummel', 'sage')),
            'showPortrait' => true,
            'portraitCaption' => $f('about_photo_caption', __('Full-stack and WordPress developer · Gettysburg, PA', 'sage')),
        ],
        mh_blocks_heading($f('about_h1', __('WordPress developer for shops and agencies.', 'sage')), 1)
        .$text($f('about_lede', __('I build WordPress sites and web apps shops can edit, agencies can hand off, and the next developer can read.', 'sage')), 'lead')
        .mh_blocks_buttons(array_merge(
            [['text' => __('Say hello', 'sage'), 'url' => $contact]],
            $bookingButton,
            [['text' => __('Hire me', 'sage'), 'url' => '#hire', 'style' => 'arrow']]
        ))
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    // Story.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Story', 'sage'), 'anchor' => 'story', 'navLabel' => __('Story', 'sage')],
        mh_blocks_heading($f('about_story_h2', __('How I got here.', 'sage')))
        .mh_blocks_paragraphs(mh_about_story_html($id))
        .mh_blocks_buttons([
            ['text' => $f('about_story_cta', __('Say hello', 'sage')), 'url' => $contact],
            ['text' => $f('about_story_now', __('What I\'m doing now', 'sage')), 'url' => '#now', 'style' => 'arrow'],
        ])
        .mh_blocks_data('profile-cards')
    );

    // Now.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Now', 'sage'), 'anchor' => 'now', 'navLabel' => __('Now', 'sage')],
        mh_blocks_heading($f('now_h1', __('What I’m doing now.', 'sage')))
        .$text($f('now_lede', __('A short list of where my time is going.', 'sage')))
        .mh_blocks_list(field_lines('now_items', mh_now_items_defaults(), $id), 'checklist')
        .$text($f('now_life_p1', __('Nights and weekends belong to my family, not projects.', 'sage')))
        .mh_blocks_data('last-updated', array_filter(['text' => trim($f('now_updated', ''))]))
        .mh_blocks_heading($f('about_posts_h2', __('Recent posts.', 'sage')), 3)
        .mh_blocks_data('recent-posts', ['count' => 3, 'linkText' => $f('about_posts_all', __('All posts', 'sage')), 'showRss' => false])
    );

    // What I build. The adjacent-work sentence is printed here once; later sections skip it while it says the same thing.
    $range = trim($f('about_services_intro', mh_adjacent_range_copy()));
    $out .= mh_blocks_section(
        ['eyebrow' => __('Services', 'sage'), 'anchor' => 'build', 'navLabel' => __('What I build', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading($f('about_services_h2', __('What I build.', 'sage')))
        .$text($range)
        .mh_blocks_cards(array_map(
            static fn (array $row): array => ['icon' => $row['icon'], 'title' => $row['title'], 'body' => esc_html($row['body'])],
            mh_about_page_services($id)
        ), 3, true)
        .mh_blocks_paragraph(field_html('about_services_note', sprintf(
            /* translators: %s: contact page URL. */
            __('Curious about a specific project type? <a href="%s">Write a note</a>.', 'sage'),
            esc_url($contact)
        ), $id))
    );

    // Hire.
    $workRange = trim($f('about_work_range', mh_adjacent_range_copy()));
    $workTypes = '';
    foreach (mh_about_page_work_types($id) as $type) {
        $workTypes .= mh_blocks_card($type['title'], $text($type['detail']), ['icon' => 'check']);
    }
    $out .= mh_blocks_section(
        ['eyebrow' => __('Hire me', 'sage'), 'anchor' => 'hire', 'navLabel' => __('Hire', 'sage')],
        mh_blocks_heading($f('about_work_h2', __('Open for work.', 'sage')))
        .mh_blocks_data('availability')
        .$text($f('about_work_p1', __('I’m open to full-time roles, contract work, and freelance projects. Happy to work remote or on-site.', 'sage')))
        .$text($f('about_work_p2', __('If you’re hiring a full-stack developer, need an experienced WordPress specialist, want agency overflow support, or have a web project to discuss, send a short note about what you’re working on.', 'sage')))
        .($workRange !== $range ? $text($workRange) : '')
        .$text($f('about_work_price', __('Written scope before I start. Custom quotes — no menu of add-ons.', 'sage')))
        .mh_blocks_buttons([
            ['text' => $f('about_work_cta', __('Start a conversation', 'sage')), 'url' => $contact],
            ['text' => __('LinkedIn', 'sage'), 'url' => $linkedin, 'style' => 'arrow'],
        ])
        .($workTypes !== '' ? mh_blocks_wrap('matthummel/cards', ['columns' => 2], $workTypes) : '')
        .mh_blocks_heading($f('about_need_h3', __('What I need from you.', 'sage')), 3)
        .$text($f('about_need_intro', __('You don’t need a finished spec. A short description of the problem is enough. Here’s what helps:', 'sage')))
        .mh_blocks_list([
            __('Who the site or app is for', 'sage'),
            __('What it needs to do', 'sage'),
            __('Any existing sites or references you like', 'sage'),
            __('A rough sense of your timeline', 'sage'),
        ], 'checklist')
        .$text($f('about_need_note', __('That’s it. I’ll follow up with clarifying questions or an honest note if I’m not the right fit.', 'sage')))
        .mh_blocks_heading(__('Ready to write?', 'sage'), 3)
        .$text(__('Use the contact form — a paragraph is plenty.', 'sage').' '.mh_reply_sla())
        .mh_blocks_buttons(array_merge([['text' => __('Say hello', 'sage'), 'url' => $contact]], $bookingButton))
        .mh_blocks_paragraph(sprintf(
            /* translators: %s: LinkedIn profile URL */
            __('Or <a href="%s" rel="noopener" target="_blank">message on LinkedIn</a>', 'sage'),
            esc_url($linkedin)
        ))
    );

    // Resume.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Experience', 'sage'), 'anchor' => 'resume', 'navLabel' => __('Resume', 'sage')],
        mh_blocks_heading($f('hire_cv_h2', __('Resume.', 'sage')))
        .$text($f('hire_cv_intro', __('Newest role first. I work with shops and agencies anywhere, and I’m open to full-time, contract, and agency overflow work.', 'sage')))
        .mh_blocks_data('resume')
    );

    // For hiring managers.
    $resumeUrl = esc_url(home_url('/about/#resume'));
    $glanceRange = trim($f('glance_range', mh_adjacent_range_copy()));
    $out .= mh_blocks_section(
        ['eyebrow' => __('At a glance', 'sage'), 'anchor' => 'glance', 'tone' => 'alt'],
        mh_blocks_heading(__('For hiring managers.', 'sage'))
        .$text($f('glance_note', __('The short version, if you are hiring. The resume above has the detail.', 'sage')))
        .mh_blocks_data('recruiter-glance', [
            'role' => $f('glance_role', __('WordPress / full-stack PHP', 'sage')),
            'stack' => $f('glance_stack', __('WordPress, PHP, custom themes & plugins', 'sage')),
            'timezone' => $f('glance_tz', __('America/New_York (ET)', 'sage')),
            'location' => $f('glance_location', __('Gettysburg, PA · remote OK', 'sage')),
            'experience' => $f('glance_experience', ''),
            'availability' => $f('glance_avail', __('Full-time, contract, freelance, agency overflow', 'sage')),
        ])
        .mh_blocks_paragraph(field_html('glance_employers', sprintf(
            /* translators: %s: resume section URL */
            __('Employers on the record: <a href="%s">Saliense, All Native Group, and Knowledge Capital Associates (USMC)</a>.', 'sage'),
            $resumeUrl
        ), $id))
        .mh_blocks_paragraph(field_html('glance_power', sprintf(
            /* translators: %s: resume section URL */
            __('PowerApps, Power Automate, and InfoPath for federal agencies — details in the <a href="%s">resume</a>. There is no public demo.', 'sage'),
            $resumeUrl
        ), $id))
        .($glanceRange !== $range ? $text($glanceRange) : '')
        .mh_blocks_buttons([
            ['text' => __('Contact', 'sage'), 'url' => $contact],
            ['text' => __('Hire', 'sage'), 'url' => '#hire', 'style' => 'arrow'],
            ['text' => __('Projects', 'sage'), 'url' => mh_work_listing_url(), 'style' => 'arrow'],
            ['text' => __('GitHub', 'sage'), 'url' => 'https://github.com/'.$login, 'style' => 'arrow'],
        ])
        .$text($f('glance_nda', __('Hiring managers can ask for a private walkthrough of constrained employer work under NDA.', 'sage')))
    );

    // Process.
    $steps = [
        [__('Write.', 'sage'), __('1–2 days', 'sage'), __('Tell me who the site is for, what is broken, and what a win looks like. A short note is enough.', 'sage'), [__('A reply with questions', 'sage'), __('An honest no if I am the wrong fit', 'sage'), __('No sales pitch', 'sage')]],
        [__('Scope.', 'sage'), __('2–4 days', 'sage'), __('I send a plain list of work, a rough timeline, and what is out of scope. You approve or push back.', 'sage'), [__('Written scope', 'sage'), __('Clear out-of-scope list', 'sage'), __('No lock-in contract', 'sage')]],
        [__('Build.', 'sage'), __('1–2 weeks', 'sage'), __('I build on staged pages you can click. Every line ships after I have read and tested it.', 'sage'), [__('Staged previews', 'sage'), __('Reviewed code before it ships', 'sage'), __('Room for feedback mid-build', 'sage')]],
        [__('Yours.', 'sage'), __('Handoff', 'sage'), __('You own the domain, hosting, database, and code. I leave a short admin guide and stay reachable for questions.', 'sage'), [__('Full ownership', 'sage'), __('Plain-language admin notes', 'sage'), __('No monthly lock-in', 'sage')]],
    ];
    $stepCards = '';
    foreach ($steps as [$title, $timing, $body, $gets]) {
        $stepCards .= mh_blocks_card($title, $text($timing).$text($body).mh_blocks_list($gets, 'checklist'));
    }
    $out .= mh_blocks_section(
        ['eyebrow' => __('Process', 'sage'), 'anchor' => 'process', 'navLabel' => __('Process', 'sage')],
        mh_blocks_heading($f('home_process_h2', __('How a project goes.', 'sage')))
        .$text(__('Four steps. Written scope. You own everything at the end.', 'sage'))
        .mh_blocks_wrap('matthummel/cards', ['columns' => 4, 'numbered' => true], $stepCards)
    );

    // Fit.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Fit', 'sage'), 'anchor' => 'fit', 'tone' => 'alt'],
        mh_blocks_heading(__('Is this a good fit?', 'sage'))
        .$text(__('A few honest checks before we spend time on a call.', 'sage'))
        .mh_blocks_wrap(
            'matthummel/cards',
            ['columns' => 2],
            mh_blocks_card(__('Good fit', 'sage'), mh_blocks_list([
                __('You need a WordPress site or web app you can own', 'sage'),
                __('You want clean code another developer can read', 'sage'),
                __('You have a clear goal — or want help naming one', 'sage'),
                __('You want written scope before work starts', 'sage'),
                __('You are a shop, agency, or hiring manager', 'sage'),
            ], 'checklist'), ['icon' => 'check'])
            .mh_blocks_card(__('Not a fit', 'sage'), mh_blocks_list([
                __('You need a designer — I am a developer (I can refer one)', 'sage'),
                __('You need a site in under a week', 'sage'),
                __('You want ongoing social or ads management', 'sage'),
                __('You need a large ecommerce platform from scratch', 'sage'),
            ]))
        )
    );

    // How I work.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Approach', 'sage'), 'anchor' => 'approach', 'tone' => 'alt'],
        mh_blocks_heading($f('about_values_h2', __('How I work.', 'sage')))
        .$text($f('about_values_intro', __('Sage 11, Blade, Tailwind, Vite, and PHP 8.3 — shipped through GitHub. Here is how I run a WordPress build from the first note to a clean handoff.', 'sage')))
        .mh_blocks_cards(array_map(
            static fn (array $row): array => ['icon' => $row['icon'], 'title' => $row['title'], 'body' => esc_html($row['body'])],
            mh_about_page_approach($id)
        ), 2)
        .mh_blocks_buttons([['text' => __('Repos and stack notes below', 'sage'), 'url' => '#code', 'style' => 'arrow']])
    );

    // Code.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Open source', 'sage'), 'anchor' => 'code', 'navLabel' => __('Code', 'sage'), 'className' => 'code-gh'],
        mh_blocks_heading($f('code_gh_h2', __('Open-source full-stack and WordPress code on GitHub.', 'sage')))
        .$text($f('code_gh_intro', __('Public Sage themes, WordPress plugins, and web apps shops and developers can fork. Stats below pull live from the GitHub API.', 'sage')))
        .mh_blocks_data('github-stats')
        .mh_blocks_heading($f('code_pin_h2', __('Pinned on GitHub', 'sage')), 3)
        .$text($f('code_pin_intro', __('The repos pinned to my GitHub profile — the ones I point developers to first. Pin or unpin a repo on GitHub and this list follows within the hour.', 'sage')))
        .mh_blocks_data('github-repos', ['source' => 'pinned', 'count' => 12])
        .mh_blocks_heading($f('code_live_h2', __('Recently pushed', 'sage')), 3)
        .$text($f('code_live_intro', __('Fresh commits on public GitHub repos — a quick read on what I am shipping this week.', 'sage')))
        .mh_blocks_data('github-repos', ['source' => 'recent', 'count' => 6, 'showAllLink' => true])
        .mh_blocks_heading($f('code_lang_h3', __('Languages on GitHub', 'sage')), 3)
        .$text($f('code_lang_intro', __('Primary language of each public repo, counted live. Small static sites pull HTML up; the themes and plugins are PHP.', 'sage')))
        .mh_blocks_data('github-languages')
    );

    // Stack.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Stack', 'sage'), 'anchor' => 'stack', 'tone' => 'alt', 'className' => 'code-skills-sec'],
        mh_blocks_heading($f('code_sk_h2', __('Skills and tools.', 'sage')))
        .$text($f('code_sk_intro', __('WordPress, Sage, Tailwind, and the rest of the stack behind shipped repos. Not an exhaustive list — just what shows up in public GitHub.', 'sage')))
        .mh_blocks_data('skills')
    );

    // Questions.
    $questions = [
        [__('What does “you own it” mean?', 'sage'), __('The domain, hosting, database, and code are yours. After handoff I have no access unless you invite me. Another developer can pick up where I left off.', 'sage')],
        [__('Do you do design, or just development?', 'sage'), __('Development. I can work from your design or a clear reference. For original design work I will refer someone rather than half-guess it.', 'sage')],
        [__('How long does a WordPress site usually take?', 'sage'), __('A simple site is often two to three weeks. Custom fields or booking can take four to eight. I give a realistic estimate during scoping.', 'sage')],
        [__('Can I edit the site myself after handoff?', 'sage'), __('Yes. Pages use standard WordPress fields. I document anything unusual in plain English before handoff.', 'sage')],
        [__('Do you work with agencies?', 'sage'), __('Yes. I can stay in the background on agency work. Rate is project-based. Write and tell me what you need.', 'sage')],
        [__('Is GitHub the start of your career?', 'sage'), __('No. I have about 17 years of in-house employer web work. Most of that cannot be shown. Public GitHub is the trail I started in 2025.', 'sage')],
        [__('Do you only do WordPress?', 'sage'), mh_adjacent_range_copy()],
    ];
    $out .= mh_blocks_section(
        ['eyebrow' => __('Questions', 'sage'), 'anchor' => 'faq', 'navLabel' => __('FAQ', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading(__('Common questions.', 'sage'))
        .mh_blocks_paragraph(sprintf(
            /* translators: %s: contact page URL */
            __('If yours is not here, <a href="%s">ask</a>.', 'sage'),
            esc_url($contact)
        ))
        .mh_blocks_faq(array_map(
            static fn (array $row): array => ['title' => $row[0], 'text' => esc_html($row[1])],
            $questions
        ))
    );

    // Elsewhere.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Online', 'sage'), 'anchor' => 'elsewhere', 'navLabel' => __('Elsewhere', 'sage')],
        mh_blocks_heading($f('about_elsewhere_h2', __('Where to find me.', 'sage')))
        .$text($f('about_elsewhere_intro', __('Most of my WordPress code and writing shows up here and on GitHub. RSS is the calmest way to follow along.', 'sage')))
        .mh_blocks_data('social-links')
    );

    // Closing band.
    $out .= mh_blocks_wrap(
        'matthummel/cta-band',
        ['eyebrow' => $f('about_cta_kicker', __('Get in touch', 'sage')), 'note' => mh_reply_sla('note')],
        mh_blocks_heading($f('about_cta_h2', __('Need a WordPress or full-stack developer?', 'sage')))
        .$text($f('about_cta_lede', __('Got a question about a post, a project, or a role? Send it over. I usually reply within one business day (ET).', 'sage')))
        .mh_blocks_buttons(array_merge(
            [['text' => $f('about_cta_btn', __('Say hello', 'sage')), 'url' => $contact]],
            $booking !== '' ? [['text' => __('Book a 15-minute call', 'sage'), 'url' => $booking, 'style' => 'ghost']] : [],
            [['text' => '@'.$login, 'url' => 'https://github.com/'.$login, 'style' => 'ghost']]
        ))
    );

    return $out;
}

/**
 * Projects: hero, project browser, inside each one, how it works, questions, closing band.
 */
function mh_projects_blocks_markup(int $id): string
{
    $f = static fn (string $key): string => field($key, mh_projects_listing_default($key), $id);
    $text = static fn (string $value, string $style = ''): string => mh_blocks_paragraph(esc_html($value), $style);
    $contact = home_url('/contact/');
    $hire = home_url('/about/#hire');

    $out = mh_blocks_wrap(
        'matthummel/hero',
        ['eyebrow' => $f('work_kicker'), 'background' => 'scene'],
        mh_blocks_heading($f('work_h1'), 1)
        .$text($f('work_lede'), 'lead')
        .mh_blocks_buttons([
            ['text' => $f('work_hero_cta_primary'), 'url' => $contact],
            ['text' => $f('work_hero_cta_secondary'), 'url' => $hire, 'style' => 'arrow'],
        ])
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    // Projects: the browser fills itself in; its labels are block settings.
    $out .= mh_blocks_section(
        ['anchor' => 'gallery', 'navLabel' => __('Projects', 'sage')],
        mh_blocks_heading(mh_projects_listing_default('gallery_h2'))
        .$text($f('work_fit_intro'), 'lead')
        .mh_blocks_data('project-browser', [
            'searchPlaceholder' => $f('work_search_ph'),
            'noProjectsTitle' => $f('work_empty_h2'),
            'noProjectsText' => $f('work_empty_text'),
            'noProjectsButton' => $f('work_empty_cta'),
        ])
        .mh_blocks_paragraph(field_html('work_foot', mh_projects_listing_default('foot'), $id))
    );

    // Inside each one.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Inside each one', 'sage'), 'anchor' => 'inside', 'navLabel' => __('Inside each one', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading($f('work_context_h2'))
        .$text($f('work_context_p1'), 'lead')
        .mh_blocks_list(field_lines('work_show_items', mh_work_show_defaults(), $id), 'checklist')
        .mh_blocks_paragraph(field_html('work_context_p2', mh_projects_listing_default('context_p2'), $id))
    );

    // How it works.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Three steps', 'sage'), 'anchor' => 'how', 'navLabel' => __('How it works', 'sage')],
        mh_blocks_heading($f('work_how_h2'))
        .$text($f('work_how_intro'), 'lead')
        .mh_blocks_cards(array_map(
            static fn (array $step): array => ['title' => $step['title'], 'body' => esc_html($step['body'])],
            mh_work_page_how($id)
        ), 3, true)
    );

    // Questions. The SEO plugin owns structured data, so new markup sets no `schema` attribute.
    $out .= mh_blocks_section(
        ['eyebrow' => __('Questions', 'sage'), 'anchor' => 'work-faq', 'navLabel' => __('FAQ', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading($f('work_faq_h2'))
        .$text($f('work_faq_intro'))
        .mh_blocks_faq(array_map(
            static fn (array $row): array => ['title' => $row['title'], 'text' => esc_html($row['text'])],
            mh_work_page_faq($id)
        ))
        .mh_blocks_buttons([['text' => __('Ask me', 'sage'), 'url' => $contact]])
    );

    // Closing band.
    $out .= mh_blocks_wrap(
        'matthummel/cta-band',
        ['eyebrow' => __('Work with me', 'sage'), 'note' => mh_reply_sla('note')],
        mh_blocks_heading($f('work_band_h2'))
        .$text($f('work_band_lede'))
        .mh_blocks_buttons([
            ['text' => $f('work_hero_cta_primary'), 'url' => $contact],
            ['text' => $f('work_hero_cta_secondary'), 'url' => $hire, 'style' => 'ghost'],
        ])
    );

    return $out;
}

/**
 * Journal (the posts page): hero, post list, RSS, topics, closing band.
 */
function mh_journal_blocks_markup(int $id): string
{
    $f = static fn (string $key, string $default): string => field($key, $default, $id);
    $text = static fn (string $value, string $style = ''): string => mh_blocks_paragraph(esc_html($value), $style);

    $out = mh_blocks_wrap(
        'matthummel/hero',
        ['eyebrow' => $f('write_kicker', __('Journal', 'sage'))],
        mh_blocks_heading($f('write_h1', __('WordPress development notes.', 'sage')), 1)
        .$text($f('write_lede', __('Short, practical WordPress development posts on PHP, JavaScript, and the tools I use. Most come with code you can paste into your own project.', 'sage')), 'lead')
        .mh_blocks_buttons([
            ['text' => __('Browse posts', 'sage'), 'url' => '#journal-posts'],
            ['text' => __('Subscribe by RSS', 'sage'), 'url' => home_url('/feed/'), 'style' => 'arrow'],
        ])
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    // Posts: the list fills itself in from the Journal's own query.
    $out .= mh_blocks_section(
        ['anchor' => 'journal-posts', 'navLabel' => __('Posts', 'sage')],
        mh_blocks_heading(__('Browse WordPress development posts', 'sage'))
        .$text(__('Search as you type, pick a topic, or switch between grid and list. Most posts include code you can adapt.', 'sage'), 'lead')
        .mh_blocks_data('journal-list', ['searchPlaceholder' => $f('write_search_ph', __('Search posts', 'sage'))])
    );

    // RSS and profiles.
    $out .= mh_blocks_section(
        ['anchor' => 'subscribe'],
        mh_blocks_heading($f('write_subscribe_h2', __('Follow with RSS', 'sage')))
        .$text($f('write_subscribe_lede', __('There is no email list. Copy the feed URL into Feedly, NetNewsWire, or another reader you already use.', 'sage')))
        .mh_blocks_data('rss-feed')
        .$text($f('write_follow', __('More of my writing', 'sage')))
        .mh_blocks_data('social-links')
    );

    // Topics.
    $out .= mh_blocks_section(
        ['anchor' => 'topics', 'navLabel' => __('Topics', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading(__('What I write about', 'sage'))
        .$text(__('Mostly WordPress — themes, plugins, and the practical notes that help a build ship.', 'sage'))
        .mh_blocks_cards([
            ['icon' => 'wordpress', 'title' => __('WordPress', 'sage'), 'body' => esc_html__('Custom themes, plugins, admin UI, and handoffs shops can keep using.', 'sage')],
            ['icon' => 'php', 'title' => __('PHP', 'sage'), 'body' => esc_html__('Plugins, hooks, and clean code another developer can pick up without a scavenger hunt.', 'sage')],
            ['icon' => 'javascript', 'title' => __('JavaScript', 'sage'), 'body' => esc_html__('Front-end behavior, forms, and small modules from real WordPress projects.', 'sage')],
            ['icon' => 'tailwind', 'title' => __('CSS & layout', 'sage'), 'body' => esc_html__('Readable layouts, fluid type, and styles that hold up on phones and wide screens.', 'sage')],
            ['icon' => 'vite', 'title' => __('Build & deploy', 'sage'), 'body' => esc_html__('Shipping themes safely — builds, updates, and notes so launch is not a fire drill.', 'sage')],
            ['icon' => 'cursor-ai', 'title' => __('AI-assisted work', 'sage'), 'body' => esc_html__('Using AI on the repeatable parts, then reviewing every line before it ships.', 'sage')],
        ])
    );

    // Closing band.
    $out .= mh_blocks_wrap(
        'matthummel/cta-band',
        ['eyebrow' => __('Get in touch', 'sage'), 'note' => mh_reply_sla('note')],
        mh_blocks_heading(__('Questions about a post?', 'sage'))
        .$text(__('A note about a snippet, a WordPress question, or a role is welcome. I usually reply within one business day (ET).', 'sage'))
        .mh_blocks_buttons([['text' => __('Say hello', 'sage'), 'url' => home_url('/contact/')]])
    );

    return $out;
}

/**
 * Resources: hero, overview with jump links and the affiliate disclosure, one section per catalog group, the stack list, closing band.
 */
function mh_resources_blocks_markup(int $id): string
{
    $f = static fn (string $key, string $default): string => field($key, $default, $id);
    $text = static fn (string $value, string $style = ''): string => mh_blocks_paragraph(esc_html($value), $style);

    $out = mh_blocks_wrap(
        'matthummel/hero',
        ['eyebrow' => $f('resources_kicker', __('Resources', 'sage')), 'className' => 'page-header--resources'],
        mh_blocks_heading($f('resources_h1', __('Free starters, themes, tools, and the stack I use.', 'sage')), 1)
        .$text($f('resources_lede', __('Free WordPress starters to study, tools I recommend, and the stack behind every site I ship. For developers and business owners alike.', 'sage')), 'lead')
        .mh_blocks_buttons([
            ['text' => __('Say hello', 'sage'), 'url' => home_url('/contact/')],
            ['text' => __('Jump to the stack', 'sage'), 'url' => '#uses', 'style' => 'arrow'],
        ])
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    // Overview: the jump list counts the links itself, and the disclosure shows only while a link is compensated.
    $out .= mh_blocks_section(
        ['anchor' => 'resources-intro-heading', 'navLabel' => __('Overview', 'sage'), 'className' => 'resources-overview'],
        mh_blocks_heading($f('resources_intro_h2', __('What you will find here.', 'sage')))
        .$text($f('resources_intro_p', __('Starters are free to fork. Tool recommendations may include disclosed affiliate links. The stack list at the end is what I use on shipped work.', 'sage')), 'lead')
        .mh_blocks_data('resource-jump')
        .mh_blocks_data('affiliate-note')
    );

    // One section per catalog group. The heading and intro are copy; the cards come from the catalog.
    foreach (mh_resources_catalog() as $group) {
        $anchor = 'resources-'.sanitize_title($group['title']);
        $out .= mh_blocks_section(
            ['anchor' => $anchor, 'navLabel' => $group['title'], 'className' => 'resources-group'],
            mh_blocks_heading($group['title'])
            .$text($group['intro'])
            .mh_blocks_data('resource-catalog', ['section' => $group['key']])
        );
    }

    // The stack I use (was /uses/).
    $out .= mh_blocks_section(
        ['eyebrow' => __('Uses', 'sage'), 'anchor' => 'uses', 'navLabel' => __('Stack I use', 'sage'), 'tone' => 'alt', 'className' => 'uses-stack'],
        mh_blocks_heading($f('uses_intro_h2', __('The stack I use.', 'sage')))
        .$text($f('uses_intro_p', __('What I actually use on shipped WordPress and web work — not exhaustive, just what I reach for. External links open in a new tab; affiliate links are labeled.', 'sage')), 'lead')
        .mh_blocks_data('stack-list')
    );

    // Closing band. "Available now" shows only while GitHub says so, as before.
    $out .= mh_blocks_wrap(
        'matthummel/cta-band',
        ['showAvailability' => true, 'note' => mh_reply_sla('note')],
        mh_blocks_heading(__('Want this stack on your project?', 'sage'))
        .$text(__('Resources here are starting points. Hire me for a production WordPress site, plugin, or web app — full-time, contract, or project.', 'sage'))
        .mh_blocks_buttons([['text' => __('Say hello', 'sage'), 'url' => home_url('/contact/')]])
    );

    return $out;
}

/**
 * Contact: hero, the form with the facts and profile links beside it, what to send, what happens next, questions.
 */
function mh_contact_blocks_markup(int $id): string
{
    $f = static fn (string $key, string $default): string => field($key, $default, $id);
    $text = static fn (string $value, string $style = ''): string => mh_blocks_paragraph(esc_html($value), $style);
    $cards = static fn (array $rows): string => mh_blocks_cards(array_map(
        static fn (array $row): array => ['title' => (string) ($row['title'] ?? ''), 'body' => esc_html((string) ($row['text'] ?? ''))],
        $rows
    ));
    $booking = mh_booking_url();

    $out = mh_blocks_wrap(
        'matthummel/hero',
        ['eyebrow' => $f('cnt_kicker', __('Contact', 'sage')), 'className' => 'contact-hero'],
        mh_blocks_heading($f('cnt_h1', __('Say hello.', 'sage')), 1)
        .$text($f('cnt_lede', __('Want to contact a WordPress developer about a role, a build, or something you are stuck on? Send a few sentences. I read everything and reply within one business day (ET).', 'sage')), 'lead')
    );

    $out .= mh_blocks_wrap('matthummel/page-nav', [], null);

    // The form's labels are block settings. A reply note left empty follows the site's reply time.
    $replyNote = trim($f('cnt_reply_note', ''));
    $out .= mh_blocks_section(
        ['anchor' => 'write', 'navLabel' => __('Write', 'sage'), 'className' => 'contact-write'],
        mh_blocks_heading($f('cnt_form_h2', __('Write a note.', 'sage')))
        .$text($f('cnt_form_intro', __('Name, email, and a few sentences are enough. No pitch deck required. This form goes straight to my inbox.', 'sage')), 'lead')
        .mh_blocks_buttons($booking !== '' ? [['text' => __('Book a 15-minute call', 'sage'), 'url' => $booking, 'style' => 'outline']] : [])
        .mh_blocks_data('contact-form', array_filter([
            'whoLabel' => $f('cnt_who_label', __('Who you are', 'sage')),
            'messageHint' => $f('cnt_message_hint', __('No pitch deck needed. Paste a URL if you have one.', 'sage')),
            'submitLabel' => $f('cnt_submit', __('Send note', 'sage')),
            'replyNote' => $replyNote !== mh_reply_sla() ? $replyNote : '',
            'successMessage' => $f('cnt_success', __('Thanks — I got it and will write back soon.', 'sage')),
            'errorMessage' => $f('cnt_error', __('Something went wrong. Check the required fields and try again.', 'sage')),
        ]))
        .mh_blocks_heading(__('Before you write', 'sage'), 3)
        .mh_blocks_data('contact-facts')
        .mh_blocks_heading($f('cnt_else_h2', __('Find me elsewhere', 'sage')), 3)
        .$text($f('cnt_aside', __('Prefer GitHub or LinkedIn? Those work too.', 'sage')))
        .mh_blocks_data('social-links', ['showNotes' => true])
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('What to include', 'sage'), 'anchor' => 'tips', 'navLabel' => __('What helps', 'sage'), 'tone' => 'alt'],
        mh_blocks_heading($f('cnt_tips_h2', __('Three things that help.', 'sage')))
        .$text($f('cnt_tips_intro', __('You don\'t need a spec. These three make it easier for me to reply with something useful.', 'sage')), 'lead')
        .$cards(field_rows('cnt_tips', mh_contact_tips(), $id))
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('After you send', 'sage'), 'anchor' => 'next', 'navLabel' => __('What happens next', 'sage')],
        mh_blocks_heading($f('cnt_expect_h2', __('What happens next.', 'sage')))
        .$text($f('cnt_expect_intro', __('A fair picture of how I use this inbox.', 'sage')), 'lead')
        .$cards(field_rows('cnt_expect', mh_contact_expect(), $id))
    );

    $out .= mh_blocks_section(
        ['eyebrow' => __('Questions', 'sage'), 'anchor' => 'questions', 'tone' => 'alt'],
        mh_blocks_heading($f('cnt_faq_h2', __('Before you contact a WordPress developer.', 'sage')))
        .$text($f('cnt_faq_intro', __('Short answers about what to send and what happens next.', 'sage')))
        .mh_blocks_faq(array_map(
            static fn (array $row): array => ['title' => $row['title'], 'text' => esc_html($row['text'])],
            mh_page_faq_rows('cnt_faq', mh_contact_faq_defaults(), $id)
        ))
        .mh_blocks_paragraph(field_html('cnt_faq_foot', sprintf(
            /* translators: 1: Projects URL, 2: About URL, 3: Journal URL */
            __('Want background first? See the <a href="%1$s">projects</a>, read <a href="%2$s">about me</a>, or browse the <a href="%3$s">journal</a>.', 'sage'),
            esc_url(mh_work_listing_url()),
            esc_url(home_url('/about/')),
            esc_url(get_permalink((int) get_option('page_for_posts')) ?: home_url('/blog/'))
        ), $id))
    );

    return $out;
}
