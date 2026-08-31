<?php

/**
 * Plugin-free contact form handler + small archive tweak.
 */

namespace App;

/** Clean archive titles ("Category: Foo" -> "Foo"). */
add_filter('get_the_archive_title_prefix', '__return_empty_string');

/**
 * Transient key scoped to the current visitor's IP and user-agent.
 *
 * @since 3.1.0
 */
function mh_contact_draft_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return 'mh_cf_'.md5($ip.'|'.$ua);
}

/**
 * Retrieve a single field value from the visitor's last submission draft.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key stored in the draft transient.
 * @param  string  $default  Value returned when the draft is missing or empty.
 */
function mh_contact_old(string $key, string $default = ''): string
{
    $draft = get_transient(mh_contact_draft_key());
    if (! is_array($draft) || $key === 'errors') {
        return $default;
    }

    return (string) ($draft[$key] ?? $default);
}

/**
 * Retrieve validation error keys from the visitor's last submission draft.
 *
 * @since 3.1.0
 *
 * @return list<string>
 */
function mh_contact_old_errors(): array
{
    $draft = get_transient(mh_contact_draft_key());
    if (! is_array($draft) || ! isset($draft['errors']) || ! is_array($draft['errors'])) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $draft['errors'])));
}

/**
 * Resolve a pre-filled form field value: draft → query-string → project look-up → default.
 *
 * @since 3.1.0
 *
 * @param  string  $key  Field key (name, email, who, subject, message).
 * @param  string  $default  Fallback when no other source provides a value.
 */
function mh_contact_prefill(string $key, string $default = ''): string
{
    $fromDraft = mh_contact_old($key);
    if ($fromDraft !== '') {
        return $fromDraft;
    }

    $allowedWho = ['developer', 'recruiter', 'learning', 'business', 'agency', 'other'];
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
    $example = mh_contact_example_url($item ?? []);
    $intent = isset($_GET['intent']) ? sanitize_key(wp_unslash($_GET['intent'])) : '';
    $isHelp = $intent === 'help';

    return match ($key) {
        'who' => 'business',
        'subject' => sprintf(__('Help with the %s theme', 'sage'), $title),
        'message' => implode("\n", array_values(array_filter([
            $isHelp
                ? sprintf(__('I have a question about the “%s” theme.', 'sage'), $title)
                : sprintf(__('I would like help with the “%s” theme.', 'sage'), $title),
            '',
            sprintf(__('Project on this site: %s', 'sage'), $share),
            $example !== '' ? sprintf(__('Live demo: %s', 'sage'), $example) : '',
        ]))),
        default => $default,
    };
}

/**
 * Live demo URL for contact prefill. Skips old studio marketing URLs.
 *
 * @param  array<string, mixed>  $item
 */
function mh_contact_example_url(array $item): string
{
    foreach (['demo', 'concept'] as $key) {
        $url = trim((string) ($item[$key] ?? ''));
        if ($url === '' || ! preg_match('#^https?://#i', $url)) {
            continue;
        }
        if (preg_match('#^https?://(www\.)?ridgesandvalleys\.com(/|$)#i', $url)) {
            continue;
        }

        return $url;
    }

    return '';
}

/**
 * "What to send" tip cards shown on the contact page sidebar.
 *
 * @since 3.1.0
 *
 * @return list<array{title: string, text: string}>
 */
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

/**
 * "What happens next" expectation cards shown on the contact page sidebar.
 *
 * @since 3.1.0
 *
 * @return list<array{title: string, text: string}>
 */
function mh_contact_expect(): array
{
    return [
        [
            'title' => __('A real reply', 'sage'),
            'text' => __('I write back in one or two business days, Eastern Time. If I cannot help, I say so.', 'sage'),
        ],
        [
            'title' => __('No ads or social retainers', 'sage'),
            'text' => __('I do not run ads or social accounts. Gettysburg example sites live on this site.', 'sage'),
        ],
        [
            'title' => __('Public code stays free', 'sage'),
            'text' => __('You can copy repos and snippets without writing. A note is kind, not required.', 'sage'),
        ],
    ];
}

/**
 * Social and external links for the "Find me elsewhere" section, with contextual notes added.
 *
 * @since 3.1.0
 *
 * @return list<array{key: string, label: string, url: string, note: string}>
 */
function mh_contact_else_links(): array
{
    $notes = [
        'github' => __('Repos, READMEs, and issues.', 'sage'),
        'linkedin' => __('Work history and a quieter inbox.', 'sage'),
        'devto' => __('Journal posts, cross-posted.', 'sage'),
        'bluesky' => __('Occasional notes.', 'sage'),
        'reddit' => __('Same handle, when I am there.', 'sage'),
        'rss' => __('New posts, no algorithm.', 'sage'),
        'globe' => __('Gettysburg WordPress demos.', 'sage'),
    ];

    $links = mh_social_links();

    foreach ($links as &$link) {
        $link['note'] = $notes[$link['key'] ?? ''] ?? '';
    }
    unset($link);

    return $links;
}

/**
 * n8n CRM webhook that receives validated contact and discovery submissions.
 *
 * Override with MH_CRM_WEBHOOK_URL or the mh_crm_webhook_url filter.
 *
 * @since 3.1.27
 */
function mh_crm_webhook_url(): string
{
    if (defined('MH_CRM_WEBHOOK_URL') && is_string(MH_CRM_WEBHOOK_URL) && MH_CRM_WEBHOOK_URL !== '') {
        return MH_CRM_WEBHOOK_URL;
    }

    return (string) apply_filters(
        'mh_crm_webhook_url',
        'https://matthummel.app.n8n.cloud/webhook/crm-contact'
    );
}

/**
 * POST a validated form payload to the CRM webhook.
 *
 * @since 3.1.27
 *
 * @param  array<string, mixed>  $payload
 */
function mh_crm_send(array $payload): bool
{
    $url = mh_crm_webhook_url();
    if ($url === '') {
        return false;
    }

    $body = array_merge([
        'site' => home_url('/'),
        'source' => 'matthummel.com',
        'submitted_at' => gmdate('c'),
    ], $payload);

    $json = wp_json_encode($body);
    if (! is_string($json) || $json === '') {
        return false;
    }

    $res = wp_remote_post($url, [
        'timeout' => 15,
        'redirection' => 3,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
        ],
        'body' => $json,
    ]);

    if (is_wp_error($res)) {
        error_log('mh_crm_send: '.$res->get_error_message());

        return false;
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) {
        error_log('mh_crm_send: HTTP '.$code);

        return false;
    }

    return true;
}

/**
 * Deliver a form payload to n8n, then fall back to wp_mail if the webhook fails.
 *
 * @since 3.1.27
 *
 * @param  array<string, mixed>  $payload
 */
function mh_crm_deliver(array $payload, string $mailSubject, string $mailBody, string $replyName, string $replyEmail): bool
{
    if (mh_crm_send($payload)) {
        return true;
    }

    $to = get_option('admin_email');
    $headers = [];
    if (is_email($replyEmail)) {
        $headers[] = 'Reply-To: '.$replyName.' <'.$replyEmail.'>';
    }

    return (bool) wp_mail($to, '[matthummel.com] '.$mailSubject, $mailBody, $headers);
}

/** Handle the contact form submission (template-contact.blade.php). */
add_action('init', function () {
    $postedAction = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
    if ($postedAction !== 'mh_contact') {
        return;
    }

    $contact = get_page_by_path('contact');
    $back = $contact instanceof \WP_Post ? get_permalink($contact) : home_url('/contact/');
    $back = remove_query_arg('contact', $back);

    // On success, redirect to /thank-you/ for analytics conversion tracking.
    $thankyouPage = get_page_by_path('thank-you');
    $thankyouUrl = $thankyouPage instanceof \WP_Post ? get_permalink($thankyouPage) : home_url('/thank-you/');

    $redirect = function ($status) use ($back, $thankyouUrl) {
        if ($status === 'ok') {
            wp_safe_redirect($thankyouUrl);
        } else {
            wp_safe_redirect(add_query_arg('contact', $status, $back).'#contact-status');
        }
        exit;
    };

    $nonce = isset($_POST['mh_contact_nonce']) ? wp_unslash($_POST['mh_contact_nonce']) : '';
    if (! is_string($nonce) || ! wp_verify_nonce($nonce, 'mh_contact')) {
        $redirect('error');
    }

    // Honeypot: bots fill this; pretend success so bots stop retrying.
    if (! empty($_POST['mh_hp'])) {
        delete_transient(mh_contact_draft_key());
        $redirect('ok');
    }

    $name = sanitize_text_field(wp_unslash($_POST['mh_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['mh_email'] ?? ''));
    $subject = sanitize_text_field(wp_unslash($_POST['mh_subject'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['mh_message'] ?? ''));
    $whoKey = sanitize_key(wp_unslash($_POST['mh_who'] ?? ''));
    $whoLabels = [
        'developer' => __('A developer', 'sage'),
        'recruiter' => __('A recruiter or hiring manager', 'sage'),
        'learning' => __('Someone learning web development', 'sage'),
        'business' => __('A shop or small business', 'sage'),
        'agency' => __('A marketing or design agency', 'sage'),
        'other' => __('Something else', 'sage'),
    ];
    $who = $whoLabels[$whoKey] ?? '';

    $draft = [
        'name' => $name,
        'email' => $email,
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

    $mailSubject = $subject !== '' ? $subject : __('New contact form message', 'matthummel');
    $body = "Name: {$name}\nEmail: {$email}";
    if ($who !== '') {
        $body .= "\nWho: {$who}";
    }
    $body .= "\n\n{$message}";

    mh_crm_deliver(
        [
            'form' => 'contact',
            'name' => $name,
            'email' => $email,
            'who' => $who,
            'who_key' => $whoKey,
            'subject' => $subject,
            'message' => $message,
            'page' => $back,
        ],
        $mailSubject,
        $body,
        $name,
        $email
    );
    delete_transient(mh_contact_draft_key());

    $redirect('ok');
});

/**
 * Transient key for the project discovery brief (/start/).
 *
 * @since 3.1.3
 */
function mh_discovery_draft_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return 'mh_df_'.md5($ip.'|'.$ua);
}

/**
 * Retrieve a single field value from the visitor's last discovery draft.
 *
 * @since 3.1.3
 */
function mh_discovery_old(string $key, string $default = ''): string
{
    $draft = get_transient(mh_discovery_draft_key());
    if (! is_array($draft) || $key === 'errors') {
        return $default;
    }

    return (string) ($draft[$key] ?? $default);
}

/**
 * Validation error keys from the visitor's last discovery draft.
 *
 * @since 3.1.3
 *
 * @return list<string>
 */
function mh_discovery_old_errors(): array
{
    $draft = get_transient(mh_discovery_draft_key());
    if (! is_array($draft) || ! isset($draft['errors']) || ! is_array($draft['errors'])) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $draft['errors'])));
}

/**
 * Label maps for discovery select fields.
 *
 * @since 3.1.3
 *
 * @return array{project_type: array<string, string>, role: array<string, string>, timeline: array<string, string>}
 */
function mh_discovery_labels(): array
{
    return [
        'project_type' => [
            'new-site' => __('New WordPress site', 'sage'),
            'rebuild' => __('Rebuild or redesign', 'sage'),
            'plugin' => __('Plugin or custom feature', 'sage'),
            'overflow' => __('Agency overflow / white-label', 'sage'),
            'other' => __('Something else', 'sage'),
        ],
        'role' => [
            'agency-pm' => __('Agency project manager', 'sage'),
            'agency-owner' => __('Agency owner', 'sage'),
            'designer' => __('Designer', 'sage'),
            'developer' => __('Developer', 'sage'),
            'shop-owner' => __('Shop or business owner', 'sage'),
            'other' => __('Something else', 'sage'),
        ],
        'timeline' => [
            'asap' => __('ASAP', 'sage'),
            '2-4w' => __('2–4 weeks', 'sage'),
            '1-2m' => __('1–2 months', 'sage'),
            'flexible' => __('Flexible', 'sage'),
            'unsure' => __('Not sure yet', 'sage'),
        ],
    ];
}

/** Handle the project discovery brief (template-start.blade.php). */
add_action('init', function () {
    $postedAction = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
    if ($postedAction !== 'mh_discovery') {
        return;
    }

    $start = get_page_by_path('start');
    $back = $start instanceof \WP_Post ? get_permalink($start) : home_url('/start/');
    $back = remove_query_arg('start', $back);

    $thankyouPage = get_page_by_path('thank-you');
    $thankyouUrl = $thankyouPage instanceof \WP_Post ? get_permalink($thankyouPage) : home_url('/thank-you/');

    $redirect = function (string $status) use ($back, $thankyouUrl): void {
        if ($status === 'ok') {
            wp_safe_redirect(add_query_arg('from', 'start', $thankyouUrl));
        } else {
            wp_safe_redirect(add_query_arg('start', $status, $back).'#start-status');
        }
        exit;
    };

    $nonce = isset($_POST['mh_discovery_nonce']) ? wp_unslash($_POST['mh_discovery_nonce']) : '';
    if (! is_string($nonce) || ! wp_verify_nonce($nonce, 'mh_discovery')) {
        $redirect('error');
    }

    if (! empty($_POST['mh_hp'])) {
        delete_transient(mh_discovery_draft_key());
        $redirect('ok');
    }

    $labels = mh_discovery_labels();

    $name = sanitize_text_field(wp_unslash($_POST['mh_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['mh_email'] ?? ''));
    $company = sanitize_text_field(wp_unslash($_POST['mh_company'] ?? ''));
    $roleKey = sanitize_key(wp_unslash($_POST['mh_role'] ?? ''));
    $typeKey = sanitize_key(wp_unslash($_POST['mh_project_type'] ?? ''));
    $client = sanitize_text_field(wp_unslash($_POST['mh_client'] ?? ''));
    $urlRaw = esc_url_raw(wp_unslash($_POST['mh_url'] ?? ''));
    $need = sanitize_textarea_field(wp_unslash($_POST['mh_need'] ?? ''));
    $success = sanitize_textarea_field(wp_unslash($_POST['mh_success'] ?? ''));
    $audience = sanitize_text_field(wp_unslash($_POST['mh_audience'] ?? ''));
    $timelineKey = sanitize_key(wp_unslash($_POST['mh_timeline'] ?? ''));
    $editors = sanitize_text_field(wp_unslash($_POST['mh_editors'] ?? ''));
    $stack = sanitize_textarea_field(wp_unslash($_POST['mh_stack'] ?? ''));
    $notes = sanitize_textarea_field(wp_unslash($_POST['mh_notes'] ?? ''));

    if ($roleKey !== '' && ! isset($labels['role'][$roleKey])) {
        $roleKey = '';
    }
    if ($typeKey !== '' && ! isset($labels['project_type'][$typeKey])) {
        $typeKey = '';
    }
    if ($timelineKey !== '' && ! isset($labels['timeline'][$timelineKey])) {
        $timelineKey = '';
    }

    $draft = [
        'name' => $name,
        'email' => $email,
        'company' => $company,
        'role' => $roleKey,
        'project_type' => $typeKey,
        'client' => $client,
        'url' => $urlRaw,
        'need' => $need,
        'success' => $success,
        'audience' => $audience,
        'timeline' => $timelineKey,
        'editors' => $editors,
        'stack' => $stack,
        'notes' => $notes,
        'errors' => [],
    ];

    if ($name === '') {
        $draft['errors'][] = 'name';
    }
    if (! is_email($email)) {
        $draft['errors'][] = 'email';
    }
    if ($typeKey === '') {
        $draft['errors'][] = 'project_type';
    }
    if ($need === '') {
        $draft['errors'][] = 'need';
    }
    if ($success === '') {
        $draft['errors'][] = 'success';
    }

    if ($draft['errors'] !== []) {
        set_transient(mh_discovery_draft_key(), $draft, 10 * MINUTE_IN_SECONDS);
        $redirect('error');
    }

    $lines = [
        'Name: '.$name,
        'Email: '.$email,
    ];
    if ($company !== '') {
        $lines[] = 'Company: '.$company;
    }
    if ($roleKey !== '') {
        $lines[] = 'Role: '.$labels['role'][$roleKey];
    }
    $lines[] = '';
    $lines[] = 'Project type: '.$labels['project_type'][$typeKey];
    if ($client !== '') {
        $lines[] = 'End client: '.$client;
    }
    if ($urlRaw !== '') {
        $lines[] = 'Current URL: '.$urlRaw;
    }
    $lines[] = '';
    $lines[] = "What needs building:\n{$need}";
    $lines[] = '';
    $lines[] = "Win looks like:\n{$success}";
    if ($audience !== '') {
        $lines[] = '';
        $lines[] = 'Audience: '.$audience;
    }
    if ($timelineKey !== '') {
        $lines[] = 'Timeline: '.$labels['timeline'][$timelineKey];
    }
    if ($editors !== '') {
        $lines[] = 'Editors after handoff: '.$editors;
    }
    if ($stack !== '') {
        $lines[] = '';
        $lines[] = "Hosting / stack / must-haves:\n{$stack}";
    }
    if ($notes !== '') {
        $lines[] = '';
        $lines[] = "Other notes:\n{$notes}";
    }

    $mailSubject = sprintf(
        __('Project brief — %s', 'sage'),
        $company !== '' ? $company : $name
    );

    mh_crm_deliver(
        [
            'form' => 'discovery',
            'name' => $name,
            'email' => $email,
            'company' => $company,
            'role' => $roleKey !== '' ? $labels['role'][$roleKey] : '',
            'role_key' => $roleKey,
            'project_type' => $labels['project_type'][$typeKey],
            'project_type_key' => $typeKey,
            'client' => $client,
            'url' => $urlRaw,
            'need' => $need,
            'success' => $success,
            'audience' => $audience,
            'timeline' => $timelineKey !== '' ? $labels['timeline'][$timelineKey] : '',
            'timeline_key' => $timelineKey,
            'editors' => $editors,
            'stack' => $stack,
            'notes' => $notes,
            'message' => $need,
            'subject' => $mailSubject,
            'page' => $back,
        ],
        $mailSubject,
        implode("\n", $lines),
        $name,
        $email
    );
    delete_transient(mh_discovery_draft_key());

    $redirect('ok');
});
