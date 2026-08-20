<?php

/**
 * Plugin-free contact form handler + small archive tweak.
 */

namespace App;

/** Clean archive titles ("Category: Foo" -> "Foo"). */
add_filter('get_the_archive_title_prefix', '__return_empty_string');

function mh_contact_draft_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return 'mh_cf_'.md5($ip.'|'.$ua);
}

function mh_contact_old(string $key, string $default = ''): string
{
    $draft = get_transient(mh_contact_draft_key());
    if (! is_array($draft) || $key === 'errors') {
        return $default;
    }

    return (string) ($draft[$key] ?? $default);
}

function mh_contact_old_errors(): array
{
    $draft = get_transient(mh_contact_draft_key());
    if (! is_array($draft) || ! isset($draft['errors']) || ! is_array($draft['errors'])) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $draft['errors'])));
}

function mh_contact_prefill(string $key, string $default = ''): string
{
    $fromDraft = mh_contact_old($key);
    if ($fromDraft !== '') {
        return $fromDraft;
    }

    $allowedWho = ['developer', 'learning', 'business', 'agency', 'other'];
    if ($key === 'who') {
        $who = isset($_GET['who']) ? sanitize_key(wp_unslash($_GET['who'])) : '';
        if (in_array($who, $allowedWho, true)) {
            return $who;
        }
    }

    if ($key === 'subject' && isset($_GET['subject'])) {
        return sanitize_text_field(wp_unslash($_GET['subject']));
    }

    if ($key === 'message' && isset($_GET['message'])) {
        return sanitize_textarea_field(wp_unslash($_GET['message']));
    }

    $slug = isset($_GET['project']) ? sanitize_title(wp_unslash($_GET['project'])) : '';
    if ($slug === '') {
        return $default;
    }

    $item = mh_work_item_by_slug($slug);
    $title = (string) ($item['title'] ?? $slug);
    $share = mh_work_permalink($slug);
    $concept = (string) ($item['concept'] ?? '');

    return match ($key) {
        'who' => 'business',
        'subject' => sprintf(__('Use the %s concept', 'sage'), $title),
        'message' => implode("\n", array_values(array_filter([
            sprintf(__('I would like to use the “%s” concept for my site.', 'sage'), $title),
            '',
            sprintf(__('Project on this site: %s', 'sage'), $share),
            $concept !== '' ? sprintf(__('Concept page: %s', 'sage'), $concept) : '',
        ]))),
        default => $default,
    };
}

function mh_contact_tips(): array
{
    return [
        [
            'title' => __('Who you are', 'sage'),
            'text' => __('Developer, learner, shop, or agency. One line is enough. It changes how I reply.', 'sage'),
        ],
        [
            'title' => __('What you need', 'sage'),
            'text' => __('A URL, a snippet name, or a sentence about the site or plugin. Skip the long brief.', 'sage'),
        ],
        [
            'title' => __('When you need it', 'sage'),
            'text' => __('A rough date helps. If you are only asking about code, say that — no timeline needed.', 'sage'),
        ],
    ];
}

function mh_contact_expect(): array
{
    return [
        [
            'title' => __('A real reply', 'sage'),
            'text' => __('I write back in one or two business days, Eastern Time. If I cannot help, I say so.', 'sage'),
        ],
        [
            'title' => __('No ads or social retainers', 'sage'),
            'text' => __('I do not run ads or social accounts. Local Gettysburg marketing lives at Ridges & Valleys.', 'sage'),
        ],
        [
            'title' => __('Public code stays free', 'sage'),
            'text' => __('You can copy repos and snippets without writing. A note is kind, not required.', 'sage'),
        ],
    ];
}

function mh_contact_else_links(): array
{
    $notes = [
        'github' => __('Repos, READMEs, and issues.', 'sage'),
        'linkedin' => __('Work history and a quieter inbox.', 'sage'),
        'devto' => __('Writing, cross-posted.', 'sage'),
        'bluesky' => __('Occasional notes.', 'sage'),
        'reddit' => __('Same handle, when I am there.', 'sage'),
        'rss' => __('New posts, no algorithm.', 'sage'),
        'globe' => __('Gettysburg studio site.', 'sage'),
    ];

    $links = mh_social_links();
    $links[] = [
        'key' => 'globe',
        'label' => 'Ridges & Valleys',
        'url' => 'https://ridgesandvalleys.com',
    ];

    foreach ($links as &$link) {
        $link['note'] = $notes[$link['key'] ?? ''] ?? '';
    }
    unset($link);

    return $links;
}

/** Handle the contact form submission (template-contact.blade.php). */
add_action('init', function () {
    if (! isset($_POST['action']) || $_POST['action'] !== 'mh_contact') {
        return;
    }

    $contact = get_page_by_path('contact');
    $back = $contact instanceof \WP_Post ? get_permalink($contact) : home_url('/contact/');
    $back = remove_query_arg('contact', $back);

    $redirect = function ($status) use ($back) {
        wp_safe_redirect(add_query_arg('contact', $status, $back).'#contact-status');
        exit;
    };

    $nonce = isset($_POST['mh_contact_nonce']) ? $_POST['mh_contact_nonce'] : '';
    if (! wp_verify_nonce($nonce, 'mh_contact')) {
        $redirect('error');
    }

    // Honeypot: bots fill this; pretend success and bail.
    if (! empty($_POST['mh_hp'])) {
        delete_transient(mh_contact_draft_key());
        $redirect('success');
    }

    $name = sanitize_text_field($_POST['mh_name'] ?? '');
    $email = sanitize_email($_POST['mh_email'] ?? '');
    $subject = sanitize_text_field($_POST['mh_subject'] ?? '');
    $message = sanitize_textarea_field($_POST['mh_message'] ?? '');
    $whoKey = sanitize_key($_POST['mh_who'] ?? '');
    $whoLabels = [
        'developer' => __('A developer', 'sage'),
        'learning' => __('Learning the web', 'sage'),
        'business' => __('A shop or team', 'sage'),
        'agency' => __('A marketing agency', 'sage'),
        'other' => __('Something else', 'sage'),
    ];
    $who = $whoLabels[$whoKey] ?? '';

    $draft = [
        'name' => $name,
        'email' => (string) ($_POST['mh_email'] ?? ''),
        'who' => $whoKey,
        'subject' => $subject,
        'message' => $message,
        'errors' => [],
    ];

    if ($name === '') {
        $draft['errors'][] = 'name';
    }
    if (! is_email($email)) {
        $draft['errors'][] = 'email';
    }
    if ($message === '') {
        $draft['errors'][] = 'message';
    }

    if ($draft['errors'] !== []) {
        set_transient(mh_contact_draft_key(), $draft, 10 * MINUTE_IN_SECONDS);
        $redirect('error');
    }

    $to = get_option('admin_email');
    $mailSubject = $subject !== '' ? $subject : __('New contact form message', 'matthummel');
    $body = "Name: {$name}\nEmail: {$email}";
    if ($who !== '') {
        $body .= "\nWho: {$who}";
    }
    $body .= "\n\n{$message}";
    $headers = ['Reply-To: '.$name.' <'.$email.'>'];

    wp_mail($to, '[matthummel.com] '.$mailSubject, $body, $headers);
    delete_transient(mh_contact_draft_key());

    $redirect('success');
});
