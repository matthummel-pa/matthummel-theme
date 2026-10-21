<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/*
 * After signup: the welcome letter, the confirmed page, UTM tags on links
 * back to this site, the public archive, and the signup sources the forms report.
 */

/**
 * Where a form can say it lives. Anything else is stored as "page".
 *
 * @return list<string>
 */
function signup_sources(): array
{
    return ['page', 'footer', 'post', 'sidebar', 'journal', 'thankyou', 'archive'];
}

function normalize_source(string $source): string
{
    $source = sanitize_key($source);

    return in_array($source, signup_sources(), true) ? $source : 'page';
}

/**
 * Compact forms sit in a narrow column: one stacked field and a full-width button.
 */
function source_is_compact(string $source): bool
{
    return in_array($source, ['footer', 'sidebar'], true);
}

function source_label(string $source): string
{
    $labels = [
        'page' => __('Get updates page', 'matthummel-newsletter'),
        'footer' => __('Footer', 'matthummel-newsletter'),
        'post' => __('End of a post', 'matthummel-newsletter'),
        'sidebar' => __('Post sidebar', 'matthummel-newsletter'),
        'journal' => __('Journal page', 'matthummel-newsletter'),
        'thankyou' => __('Thank-you page', 'matthummel-newsletter'),
        'archive' => __('Past issues', 'matthummel-newsletter'),
        'import' => __('Import', 'matthummel-newsletter'),
        'legacy' => __('Old footer list', 'matthummel-newsletter'),
    ];

    return $labels[$source] ?? ($source !== '' ? $source : '—');
}

/* ── Welcome letter ───────────────────────────────────────────────────── */

function welcome_enabled(): bool
{
    return settings()['welcome_send'] === 1;
}

/**
 * Preview text for the welcome letter: the first expectation, then the first line of the body.
 *
 * @param  array{welcome_points: string, welcome_body: string}  $config
 */
function welcome_preheader(array $config): string
{
    foreach ([$config['welcome_points'], $config['welcome_body']] as $text) {
        foreach (preg_split("/\R+/", (string) $text) ?: [] as $line) {
            $line = trim(wp_strip_all_tags((string) $line));
            if ($line !== '') {
                return mb_substr($line, 0, 140);
            }
        }
    }

    return '';
}

/**
 * The Welcome layout rendered from the reusable copy under Settings, with the
 * checklist button when that link is set and the three latest posts.
 *
 * @param  array<string, string>  $subscriber
 * @return array{subject: string, html: string, text: string}
 */
function welcome_message(array $subscriber): array
{
    $config = settings();
    $look = settings_letter_look();
    push_letter_look($look);

    try {
        $body = apply_layout('welcome', '');
        $subject = trim($config['welcome_subject']);
        if ($subject === '') {
            $subject = layout_copy_defaults()['welcome_subject'];
        }
        $document = email_document($subject, welcome_preheader($config), $body, true, 0, 'welcome', $look);
    } finally {
        pop_letter_look();
    }

    $document = apply_utm_links($document, 'welcome');

    return [
        'subject' => trim(str_replace(["\r", "\n"], ' ', apply_merge($subject, $subscriber, 0, false))),
        'html' => apply_merge($document, $subscriber, 0, true),
        'text' => apply_merge(plain_text($document), $subscriber, 0, false),
    ];
}

/**
 * Send the welcome letter to one subscriber. Logged as a `welcome` event.
 *
 * @param  array<string, string>  $subscriber
 */
function send_welcome_mail(array $subscriber): bool
{
    $id = (int) ($subscriber['id'] ?? 0);
    $email = (string) ($subscriber['email'] ?? '');
    if ($id < 1 || ! is_email($email)) {
        return false;
    }

    $message = welcome_message($subscriber);
    $sent = send_mail($email, $message['subject'], $message['html'], $message['text'], [], $subscriber);
    log_event(0, $id, $sent ? 'welcome' : 'welcome_failed', $sent ? '' : mail_error());

    return $sent;
}

/**
 * Runs on `mhn_subscriber_confirmed`, right after the confirm button.
 *
 * @param  array<string, string>  $subscriber
 */
function on_subscriber_confirmed(array $subscriber): void
{
    if (! welcome_enabled()) {
        return;
    }

    send_welcome_mail($subscriber);
}

/**
 * Settings → "Send me the welcome letter". Goes to the signed-in administrator.
 */
function handle_welcome_test(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_welcome_test');

    $user = wp_get_current_user();
    $email = (string) $user->user_email;
    $sent = false;
    if (is_email($email)) {
        $sample = [
            'id' => '0',
            'email' => $email,
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
            'status' => 'preview',
        ];
        $message = welcome_message($sample);
        $sent = send_mail($email, $message['subject'], $message['html'], $message['text'], [], null);
    }

    wp_safe_redirect(admin_url('admin.php?page=mhn-settings&welcome_test='.($sent ? '1' : '0')));
    exit;
}

/* ── UTM tags ─────────────────────────────────────────────────────────── */

function utm_enabled(): bool
{
    return settings()['utm_links'] === 1;
}

/**
 * utm_campaign for one issue: its slug, or issue-{id} when it has none.
 */
function issue_campaign(int $issueId): string
{
    if ($issueId < 1) {
        return 'newsletter';
    }

    $post = get_post($issueId);
    $slug = $post instanceof \WP_Post ? (string) $post->post_name : '';
    if ($slug === '' && $post instanceof \WP_Post) {
        $slug = sanitize_title($post->post_title);
    }

    return $slug !== '' ? mb_substr($slug, 0, 60) : 'issue-'.$issueId;
}

/**
 * Add utm_source=newsletter, utm_medium=email, and utm_campaign to links that
 * point at this site. Other hosts, tracked links, and links that already carry
 * utm_ parameters are left alone. Merge-tag placeholders are not https URLs.
 */
function apply_utm_links(string $html, string $campaign): string
{
    if (! utm_enabled()) {
        return $html;
    }

    $home = wp_parse_url(home_url(), PHP_URL_HOST);
    if (! is_string($home) || $home === '') {
        return $html;
    }
    $home = strtolower(preg_replace('/^www\./', '', $home) ?? $home);
    $campaign = sanitize_title($campaign);
    if ($campaign === '') {
        $campaign = 'newsletter';
    }

    $rewritten = preg_replace_callback(
        '/href=(["\'])(https?:\/\/[^"\']+)\1/i',
        static function (array $match) use ($home, $campaign): string {
            $url = html_entity_decode($match[2], ENT_QUOTES);
            $host = wp_parse_url($url, PHP_URL_HOST);
            if (! is_string($host) || $host === '') {
                return $match[0];
            }
            $host = strtolower(preg_replace('/^www\./', '', $host) ?? $host);
            if ($host !== $home || preg_match('/[?&](?:utm_|mhn_)/', $url) === 1) {
                return $match[0];
            }

            $tagged = add_query_arg([
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => $campaign,
            ], $url);

            return 'href='.$match[1].esc_url($tagged).$match[1];
        },
        $html
    );

    return is_string($rewritten) ? $rewritten : $html;
}

/* ── Confirmed page ───────────────────────────────────────────────────── */

/**
 * Where the confirm button lands. The Get updates page when it is published,
 * otherwise an empty string and the plain confirm page stays.
 */
function confirmed_landing_url(): string
{
    $page = find_owned_page('get-updates');
    if (! $page instanceof \WP_Post || $page->post_status !== 'publish') {
        return '';
    }

    $url = get_permalink($page);
    if (! is_string($url) || $url === '') {
        return '';
    }

    return add_query_arg('signup', 'confirmed', $url).'#signup';
}

/**
 * Three latest posts for the confirmed page and the archive page.
 *
 * @return list<array{title: string, url: string}>
 */
function start_here_posts(int $limit = 3): array
{
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    $items = [];
    foreach (is_array($posts) ? $posts : [] as $post) {
        if (! $post instanceof \WP_Post) {
            continue;
        }
        $url = get_permalink($post);
        if (! is_string($url) || $url === '') {
            continue;
        }
        $items[] = ['title' => get_the_title($post), 'url' => $url];
    }

    return $items;
}

/**
 * The panel a new subscriber sees after pressing Confirm. No form: they are done.
 */
function confirmed_panel_html(): string
{
    $config = settings();
    $html = '<section class="mhn-signup mhn-welcome" id="signup" aria-labelledby="mhn-welcome-title">';
    $html .= '<h2 id="mhn-welcome-title" class="mhn-signup__title">'.esc_html__('You are on the list', 'matthummel-newsletter').'</h2>';
    $html .= '<p class="mhn-status" role="status">'.esc_html(welcome_enabled()
        ? __('Thanks. A welcome note is on its way. If it lands in Promotions or Spam, move it to your inbox so the next one arrives.', 'matthummel-newsletter')
        : __('Thanks. I will send a note when I publish.', 'matthummel-newsletter')).'</p>';

    if ($config['checklist_url'] !== '') {
        $html .= '<p class="mhn-welcome__lead">'.esc_html__('Here is the checklist I promised. The welcome note has the same link.', 'matthummel-newsletter').'</p>';
        $html .= '<p class="mhn-welcome__cta"><a class="btn" href="'.esc_url($config['checklist_url']).'">'.esc_html__('Open the WordPress Handoff Checklist', 'matthummel-newsletter').'</a></p>';
    }

    $posts = start_here_posts();
    if ($posts !== []) {
        $html .= '<h3 class="mhn-welcome__h">'.esc_html__('Start with these', 'matthummel-newsletter').'</h3>';
        $html .= '<ul class="mhn-welcome__posts">';
        foreach ($posts as $post) {
            $html .= '<li><a href="'.esc_url($post['url']).'">'.esc_html($post['title']).'</a></li>';
        }
        $html .= '</ul>';
    }

    $html .= '<p class="mhn-welcome__reply">'.esc_html__('Reply to the welcome note and tell me what you are building. I read every reply.', 'matthummel-newsletter').'</p>';

    $links = [];
    $archive = public_archive_url();
    if ($archive !== '') {
        $links[] = '<a href="'.esc_url($archive).'">'.esc_html__('Read past issues', 'matthummel-newsletter').'</a>';
    }
    $links[] = '<a href="'.esc_url(home_url('/')).'">'.esc_html__('Back to the site', 'matthummel-newsletter').'</a>';
    $html .= '<p class="mhn-welcome__links">'.implode(' · ', $links).'</p>';
    $html .= '</section>';

    return $html;
}

/* ── Public archive ───────────────────────────────────────────────────── */

function public_archive_enabled(): bool
{
    return settings()['public_archive'] === 1;
}

/**
 * The Past issues page, when the archive is on and the page is published.
 */
function public_archive_url(): string
{
    if (! public_archive_enabled()) {
        return '';
    }

    $page = find_owned_page('newsletter');
    if (! $page instanceof \WP_Post || $page->post_status !== 'publish') {
        return '';
    }

    $url = get_permalink($page);

    return is_string($url) ? $url : '';
}

/**
 * Create the Past issues page. Called when the setting is turned on in wp-admin.
 */
function ensure_archive_page(): int
{
    return ensure_page(
        'newsletter',
        __('Past issues', 'matthummel-newsletter'),
        '[mhn_archive]',
        'template-get-updates.blade.php'
    );
}

function public_issue_url(int $snapshotId): string
{
    $base = public_archive_url();

    return add_query_arg('mhn_issue', $snapshotId, $base !== '' ? $base : home_url('/'));
}

/**
 * A sent row the public may read: a full-list send that is not archived.
 *
 * @param  array<string, string>  $row
 */
function snapshot_is_public(array $row): bool
{
    if ((string) ($row['list_label'] ?? '') === __('Allowlist', 'matthummel-newsletter')) {
        return false;
    }

    return ! issue_is_archived((int) ($row['issue_id'] ?? 0));
}

function archive_display_date(string $mysql): string
{
    $time = strtotime($mysql);
    if ($time === false) {
        return $mysql;
    }

    return wp_date((string) get_option('date_format', 'F j, Y'), $time);
}

/**
 * [mhn_archive]: sent letters, newest first, then the signup form.
 */
function shortcode_archive(): string
{
    if (! public_archive_enabled()) {
        if (current_user_can('manage_options')) {
            return '<p class="mhn-note" role="status">'.esc_html__('The public archive is off. Turn it on under Get updates → Settings → After signup.', 'matthummel-newsletter').'</p>';
        }

        return '';
    }

    $paged = max(1, absint($_GET['issues_page'] ?? 1));
    $result = sent_archive_query([
        'show' => 'active',
        'public' => true,
        'per_page' => 20,
        'page' => $paged,
        'orderby' => 'finished_at',
        'order' => 'DESC',
    ]);

    $html = '<div class="mhn-archive" id="issues">';
    if ($result['rows'] === []) {
        $html .= '<p class="mhn-note">'.esc_html__('No issues yet. The first one lands here after it goes out.', 'matthummel-newsletter').'</p>';
    } else {
        $html .= '<ol class="mhn-archive__list" reversed>';
        foreach ($result['rows'] as $row) {
            if (! snapshot_is_public($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $html .= '<li class="mhn-archive__item">';
            $html .= '<a class="mhn-archive__title" href="'.esc_url(public_issue_url($id)).'">'.esc_html((string) $row['subject']).'</a>';
            $html .= '<time class="mhn-archive__date" datetime="'.esc_attr(str_replace(' ', 'T', (string) $row['finished_at'])).'">'.esc_html(archive_display_date((string) $row['finished_at'])).'</time>';
            $preheader = trim((string) ($row['preheader'] ?? ''));
            if ($preheader !== '') {
                $html .= '<p class="mhn-archive__lede">'.esc_html($preheader).'</p>';
            }
            $html .= '</li>';
        }
        $html .= '</ol>';

        $pages = (int) ceil($result['total'] / $result['per_page']);
        if ($pages > 1) {
            $html .= '<nav class="mhn-archive__nav" aria-label="'.esc_attr__('More issues', 'matthummel-newsletter').'">';
            $base = public_archive_url();
            if ($base === '') {
                $base = home_url('/');
            }
            if ($paged > 1) {
                $html .= '<a href="'.esc_url(add_query_arg('issues_page', $paged - 1, $base).'#issues').'">'.esc_html__('Newer issues', 'matthummel-newsletter').'</a>';
            }
            if ($paged < $pages) {
                $html .= '<a href="'.esc_url(add_query_arg('issues_page', $paged + 1, $base).'#issues').'">'.esc_html__('Older issues', 'matthummel-newsletter').'</a>';
            }
            $html .= '</nav>';
        }
    }
    $html .= '</div>';

    $html .= '<h2 class="mhn-archive__signup-title">'.esc_html__('Get the next one', 'matthummel-newsletter').'</h2>';
    $html .= signup_form('archive', true);

    return $html;
}

/**
 * The stored letter for the web: merge tags filled with their fallbacks, footer
 * links pointed at the signup page, and a line at the top saying when it went out.
 *
 * @param  array<string, string>  $row
 */
function public_issue_html(array $row): string
{
    $id = (int) ($row['id'] ?? 0);
    $self = public_issue_url($id);
    $signup = page_url('get-updates');
    $html = apply_person_tags((string) ($row['html'] ?? ''), ['first_name' => '', 'last_name' => ''], true);
    $html = strtr($html, [
        '*|FNAME|*' => esc_html__('there', 'matthummel-newsletter'),
        '*|EMAIL|*' => '',
        '*|UNSUB|*' => esc_url($signup),
        '*|UPDATE_PROFILE|*' => esc_url($signup),
        '*|PREFERENCES|*' => esc_url($signup),
        '*|ARCHIVE|*' => esc_url($self),
        '*|CURRENT_YEAR|*' => esc_html(wp_date('Y')),
    ]);

    $font = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
    $banner = '<p class="mhn-web-banner" style="margin:0;padding:12px 16px;'.$font.'font-size:14px;line-height:1.5;color:#0b1220;background:#eef3f9;text-align:center;">'
        .esc_html(sprintf(
            /* translators: %s: send date */
            __('This letter went out on %s.', 'matthummel-newsletter'),
            archive_display_date((string) ($row['finished_at'] ?? ''))
        ))
        .' <a href="'.esc_url($signup).'" style="color:#0d2e57;font-weight:700;">'.esc_html__('Get the next one by email', 'matthummel-newsletter').'</a>'
        .'</p>';
    $withBanner = preg_replace('/<body([^>]*)>/i', '<body$1>'.$banner, $html, 1);
    if (is_string($withBanner)) {
        $html = $withBanner;
    }

    return str_replace('</head>', '<link rel="canonical" href="'.esc_url($self).'"></head>', $html);
}

/**
 * ?mhn_issue={snapshot id}: one sent letter, readable by anyone, when the archive is on.
 */
function handle_public_issue(): void
{
    $id = absint($_GET['mhn_issue'] ?? 0);
    if (! public_archive_enabled()) {
        wp_safe_redirect(page_url('get-updates'));
        exit;
    }

    $row = $id > 0 ? sent_snapshot($id) : null;
    if (! $row || ! snapshot_is_public($row)) {
        wp_die(esc_html__('That issue is not available.', 'matthummel-newsletter'), 404);
    }

    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src https: http: data:; font-src https: data:; base-uri 'none'; form-action 'none'");
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A whole email document: every value in it was escaped when the letter was rendered and stored, and the placeholders above are filled with escaped values.
    echo public_issue_html($row);
    exit;
}
