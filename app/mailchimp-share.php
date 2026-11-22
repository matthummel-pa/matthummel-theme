<?php

/**
 * Mailchimp newsletter campaign for a journal post, from the post edit screen.
 *
 * Three explicit steps, never automatic: create (or refresh) a draft campaign, send a test to the
 * current user, then send to the audience. Nothing is sent when a post is published or saved.
 *
 * Credentials live in wp-config.php, never in the repo or the database:
 *   define('MH_MAILCHIMP_API_KEY', 'xxxxxxxx-us12');   // key ends in the data center, e.g. -us12
 *   define('MH_MAILCHIMP_LIST_ID', 'abc123def4');      // audience ID
 * Optional: MH_MAILCHIMP_FROM_NAME, MH_MAILCHIMP_REPLY_TO, MH_MAILCHIMP_SERVER (override the data center).
 */

namespace App;

const MH_MAILCHIMP_NONCE = 'mh_mailchimp';

/** Read a string constant from wp-config.php. */
function mh_mailchimp_const(string $name): string
{
    return defined($name) && is_string(constant($name)) ? trim((string) constant($name)) : '';
}

function mh_mailchimp_api_key(): string
{
    return mh_mailchimp_const('MH_MAILCHIMP_API_KEY');
}

function mh_mailchimp_list_id(): string
{
    return mh_mailchimp_const('MH_MAILCHIMP_LIST_ID');
}

/** Data center prefix, from the key suffix ("...-us12" gives "us12"), or MH_MAILCHIMP_SERVER. */
function mh_mailchimp_server(): string
{
    $override = mh_mailchimp_const('MH_MAILCHIMP_SERVER');
    if ($override !== '' && preg_match('/^[a-z]{2,3}\d+$/i', $override) === 1) {
        return strtolower($override);
    }
    if (preg_match('/-([a-z]{2,3}\d+)$/i', mh_mailchimp_api_key(), $m) === 1) {
        return strtolower($m[1]);
    }

    return '';
}

function mh_mailchimp_is_configured(): bool
{
    return mh_mailchimp_api_key() !== '' && mh_mailchimp_list_id() !== '' && mh_mailchimp_server() !== '';
}

function mh_mailchimp_from_name(): string
{
    $name = mh_mailchimp_const('MH_MAILCHIMP_FROM_NAME');

    return (string) apply_filters('mh/mailchimp_from_name', $name !== '' ? $name : 'Matt Hummel');
}

function mh_mailchimp_reply_to(): string
{
    $email = mh_mailchimp_const('MH_MAILCHIMP_REPLY_TO');

    return (string) apply_filters('mh/mailchimp_reply_to', $email !== '' ? $email : (string) get_option('admin_email'));
}

/** Link that opens a campaign in the Mailchimp app. */
function mh_mailchimp_admin_url(int $webId): string
{
    $server = mh_mailchimp_server();
    if ($server === '' || $webId <= 0) {
        return '';
    }

    return 'https://'.$server.'.admin.mailchimp.com/campaigns/show/?id='.$webId;
}

/**
 * Call the Mailchimp Marketing API (v3.0).
 *
 * @param  array<string, mixed>  $body
 * @return array{ok: bool, status: int, data: array<string, mixed>, message: string}
 */
function mh_mailchimp_request(string $method, string $path, array $body = []): array
{
    $server = mh_mailchimp_server();
    if (! mh_mailchimp_is_configured()) {
        return ['ok' => false, 'status' => 0, 'data' => [], 'message' => 'Mailchimp is not set up. Add MH_MAILCHIMP_API_KEY and MH_MAILCHIMP_LIST_ID to wp-config.php.'];
    }

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            // Mailchimp's API uses HTTP Basic auth, so the key is base64-encoded for the header, not obfuscated.
            'Authorization' => 'Basic '.base64_encode('anystring:'.mh_mailchimp_api_key()), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel-theme',
        ],
    ];
    if ($body !== [] && $method !== 'GET') {
        $args['body'] = wp_json_encode($body);
    }

    $res = wp_remote_request('https://'.$server.'.api.mailchimp.com/3.0/'.ltrim($path, '/'), $args);
    if (is_wp_error($res)) {
        return ['ok' => false, 'status' => 0, 'data' => [], 'message' => 'Could not reach Mailchimp: '.$res->get_error_message()];
    }

    $status = (int) wp_remote_retrieve_response_code($res);
    $json = json_decode((string) wp_remote_retrieve_body($res), true);
    $data = is_array($json) ? $json : [];
    if ($status >= 200 && $status < 300) {
        return ['ok' => true, 'status' => $status, 'data' => $data, 'message' => ''];
    }

    $title = is_string($data['title'] ?? null) ? $data['title'] : 'Error';
    $detail = is_string($data['detail'] ?? null) ? $data['detail'] : '';

    return ['ok' => false, 'status' => $status, 'data' => $data, 'message' => trim("Mailchimp said: {$title}. {$detail}")];
}

/**
 * Email body for a post: title, summary, one button. Table layout and inline styles for mail clients.
 * Mailchimp merge tags in the footer (*|UNSUB|*, *|LIST:ADDRESS|*) are replaced when it sends.
 */
function mh_mailchimp_email_html(\WP_Post $post): string
{
    $title = mh_post_title($post);
    $url = (string) get_permalink($post);
    $excerpt = wp_trim_words(wp_strip_all_tags((string) get_the_excerpt($post)), 45);
    $image = mh_post_card_image((int) $post->ID);
    $label = (string) apply_filters('mh/mailchimp_button_label', 'Read the post');

    $img = $image !== ''
        ? '<tr><td style="padding:0 0 20px"><a href="'.esc_url($url).'"><img src="'.esc_url($image).'" alt="" width="560" style="display:block;width:100%;max-width:560px;height:auto;border:0;border-radius:8px"></a></td></tr>'
        : '';

    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc_html($title).'</title></head>'
        .'<body style="margin:0;padding:0;background:#f4f6f8">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8"><tr><td align="center" style="padding:24px 12px">'
        .'<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:10px">'
        .'<tr><td style="padding:32px 20px 28px;font-family:Arial,Helvetica,sans-serif;color:#0f172a">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
        .$img
        .'<tr><td style="font-size:24px;line-height:1.25;font-weight:700;padding:0 0 12px">'.esc_html($title).'</td></tr>'
        .($excerpt !== '' ? '<tr><td style="font-size:16px;line-height:1.6;color:#334155;padding:0 0 22px">'.esc_html($excerpt).'</td></tr>' : '')
        .'<tr><td style="padding:0 0 8px"><a href="'.esc_url($url).'" style="display:inline-block;background:#1d5fd6;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;padding:12px 22px;border-radius:8px">'.esc_html($label).'</a></td></tr>'
        .'</table></td></tr></table>'
        .'<p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#64748b;max-width:560px;margin:16px 0 0;text-align:center">'
        .'You get this because you signed up at matthummel.com.<br>*|LIST:ADDRESS|*<br><a href="*|UNSUB|*" style="color:#64748b">Unsubscribe</a></p>'
        .'</td></tr></table></body></html>';
}

/**
 * Stored campaign state for a post.
 *
 * @return array{id: string, web_id: int, created: int, sent: int}
 */
function mh_mailchimp_state(int $postId): array
{
    return [
        'id' => (string) get_post_meta($postId, '_mh_mailchimp_campaign_id', true),
        'web_id' => (int) get_post_meta($postId, '_mh_mailchimp_web_id', true),
        'created' => (int) get_post_meta($postId, '_mh_mailchimp_created_at', true),
        'sent' => (int) get_post_meta($postId, '_mh_mailchimp_sent_at', true),
    ];
}

/**
 * Create the draft campaign for a published post, or refresh it if one already exists and has not been sent.
 *
 * @return array{ok: bool, message: string}
 */
function mh_mailchimp_create_draft(int $postId): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
        return ['ok' => false, 'message' => 'Not a journal post.'];
    }
    if ($post->post_status !== 'publish') {
        return ['ok' => false, 'message' => 'Publish the post first so the email links to a live page.'];
    }

    $state = mh_mailchimp_state($postId);
    if ($state['sent'] > 0) {
        return ['ok' => false, 'message' => 'This post was already sent as a newsletter.'];
    }

    $title = mh_post_title($post);
    $settings = [
        'subject_line' => $title,
        'preview_text' => wp_trim_words(wp_strip_all_tags((string) get_the_excerpt($post)), 20),
        'title' => 'Journal: '.$title,
        'from_name' => mh_mailchimp_from_name(),
        'reply_to' => mh_mailchimp_reply_to(),
    ];

    if ($state['id'] !== '') {
        $update = mh_mailchimp_request('PATCH', 'campaigns/'.rawurlencode($state['id']), ['settings' => $settings]);
        if (! $update['ok']) {
            return ['ok' => false, 'message' => $update['message']];
        }
        $campaignId = $state['id'];
        $message = 'Draft updated in Mailchimp.';
    } else {
        $created = mh_mailchimp_request('POST', 'campaigns', [
            'type' => 'regular',
            'recipients' => ['list_id' => mh_mailchimp_list_id()],
            'settings' => $settings,
        ]);
        $campaignId = is_string($created['data']['id'] ?? null) ? $created['data']['id'] : '';
        if (! $created['ok'] || $campaignId === '') {
            return ['ok' => false, 'message' => $created['ok'] ? 'Mailchimp did not return a campaign ID.' : $created['message']];
        }
        update_post_meta($postId, '_mh_mailchimp_campaign_id', sanitize_text_field($campaignId));
        update_post_meta($postId, '_mh_mailchimp_web_id', (int) ($created['data']['web_id'] ?? 0));
        update_post_meta($postId, '_mh_mailchimp_created_at', time());
        $message = 'Draft created in Mailchimp. Nothing has been sent.';
    }

    $content = mh_mailchimp_request('PUT', 'campaigns/'.rawurlencode($campaignId).'/content', ['html' => mh_mailchimp_email_html($post)]);
    if (! $content['ok']) {
        return ['ok' => false, 'message' => 'The campaign exists but its content did not save. '.$content['message']];
    }

    return ['ok' => true, 'message' => $message];
}

/**
 * Send a test copy of the draft to one address.
 *
 * @return array{ok: bool, message: string}
 */
function mh_mailchimp_send_test(int $postId, string $email): array
{
    $state = mh_mailchimp_state($postId);
    if ($state['id'] === '') {
        return ['ok' => false, 'message' => 'Create the draft first.'];
    }
    if (! is_email($email)) {
        return ['ok' => false, 'message' => 'Your account has no valid email address.'];
    }

    $res = mh_mailchimp_request('POST', 'campaigns/'.rawurlencode($state['id']).'/actions/test', [
        'test_emails' => [$email],
        'send_type' => 'html',
    ]);

    return $res['ok'] ? ['ok' => true, 'message' => 'Test email sent to '.$email.'.'] : ['ok' => false, 'message' => $res['message']];
}

/**
 * Send the draft to the whole audience. Runs the Mailchimp send checklist first and stops if it is not ready.
 *
 * @return array{ok: bool, message: string}
 */
function mh_mailchimp_send_campaign(int $postId): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
        return ['ok' => false, 'message' => 'Only a published post can be sent.'];
    }
    $state = mh_mailchimp_state($postId);
    if ($state['id'] === '') {
        return ['ok' => false, 'message' => 'Create the draft first.'];
    }
    if ($state['sent'] > 0) {
        return ['ok' => false, 'message' => 'This post was already sent as a newsletter.'];
    }

    $check = mh_mailchimp_request('GET', 'campaigns/'.rawurlencode($state['id']).'/send-checklist');
    if (! $check['ok']) {
        return ['ok' => false, 'message' => $check['message']];
    }
    if (empty($check['data']['is_ready'])) {
        $problems = [];
        foreach ((array) ($check['data']['items'] ?? []) as $item) {
            if (is_array($item) && ($item['type'] ?? '') === 'error' && is_string($item['details'] ?? null)) {
                $problems[] = wp_strip_all_tags($item['details']);
            }
        }

        return ['ok' => false, 'message' => 'Mailchimp says it is not ready to send: '.($problems !== [] ? implode(' ', $problems) : 'open the draft in Mailchimp to see why.')];
    }

    // Record the send before the call returns so a double click or a retry can never send twice.
    if (! add_post_meta($postId, '_mh_mailchimp_sending', time(), true)) {
        return ['ok' => false, 'message' => 'A send is already in progress or was already attempted. Check Mailchimp.'];
    }
    $res = mh_mailchimp_request('POST', 'campaigns/'.rawurlencode($state['id']).'/actions/send');
    if (! $res['ok']) {
        delete_post_meta($postId, '_mh_mailchimp_sending');

        return ['ok' => false, 'message' => $res['message']];
    }
    update_post_meta($postId, '_mh_mailchimp_sent_at', time());

    return ['ok' => true, 'message' => 'Sent to your Mailchimp audience.'];
}

/* ───────────────────────── Post meta ───────────────────────── */

add_action('init', function (): void {
    foreach (['_mh_mailchimp_campaign_id' => 'string', '_mh_mailchimp_web_id' => 'integer', '_mh_mailchimp_created_at' => 'integer', '_mh_mailchimp_sent_at' => 'integer', '_mh_mailchimp_sending' => 'integer'] as $key => $type) {
        // Server-side state only: not exposed in REST, and only editors can touch it.
        register_post_meta('post', $key, [
            'type' => $type,
            'single' => true,
            'show_in_rest' => false,
            'auth_callback' => static fn (): bool => current_user_can('publish_posts'),
        ]);
    }
});

/* ───────────────────────── AJAX ───────────────────────── */

add_action('wp_ajax_mh_mailchimp', function (): void {
    check_ajax_referer(MH_MAILCHIMP_NONCE, 'nonce');
    $postId = absint(wp_unslash($_POST['post_id'] ?? 0));
    // Sending to subscribers needs the same capability as publishing.
    if ($postId <= 0 || ! current_user_can('edit_post', $postId) || ! current_user_can('publish_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }

    $step = sanitize_key((string) ($_POST['step'] ?? ''));
    $result = match ($step) {
        'draft' => mh_mailchimp_create_draft($postId),
        'test' => mh_mailchimp_send_test($postId, (string) (wp_get_current_user()->user_email ?? '')),
        'send' => mh_mailchimp_send_campaign($postId),
        default => ['ok' => false, 'message' => 'Unknown step.'],
    };

    $state = mh_mailchimp_state($postId);
    $payload = [
        'message' => $result['message'],
        'hasDraft' => $state['id'] !== '',
        'sent' => $state['sent'] > 0,
        'openUrl' => mh_mailchimp_admin_url($state['web_id']),
    ];
    $result['ok'] ? wp_send_json_success($payload) : wp_send_json_error($payload);
});

/* ───────────────────────── Editor box ───────────────────────── */

add_action('add_meta_boxes_post', function (): void {
    if (! current_user_can('publish_posts')) {
        return;
    }
    add_meta_box('mh_mailchimp', __('Mailchimp newsletter', 'sage'), __NAMESPACE__.'\\mh_mailchimp_metabox', 'post', 'side', 'default');
});

function mh_mailchimp_metabox(\WP_Post $post): void
{
    if (! mh_mailchimp_is_configured()) {
        echo '<p>'.esc_html__('Not set up. Add MH_MAILCHIMP_API_KEY and MH_MAILCHIMP_LIST_ID to wp-config.php.', 'sage').'</p>';

        return;
    }

    $state = mh_mailchimp_state((int) $post->ID);
    $published = $post->post_status === 'publish';
    $open = mh_mailchimp_admin_url($state['web_id']);
    ?>
    <div id="mh-mailchimp" data-post="<?php echo esc_attr((string) $post->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce(MH_MAILCHIMP_NONCE)); ?>">
      <?php if ($state['sent'] > 0) { ?>
        <p><strong><?php esc_html_e('Sent as a newsletter.', 'sage'); ?></strong>
          <?php echo esc_html(wp_date(get_option('date_format').' '.get_option('time_format'), $state['sent'])); ?></p>
      <?php } else { ?>
        <?php if (! $published) { ?>
          <p class="description"><?php esc_html_e('Publish the post first, so the email links to a live page.', 'sage'); ?></p>
        <?php } ?>
        <p class="description"><?php esc_html_e('Nothing is sent when you publish. Create a draft, send yourself a test, then send.', 'sage'); ?></p>
        <p>
          <button type="button" class="button" data-step="draft" <?php disabled(! $published); ?>><?php echo esc_html($state['id'] !== '' ? __('Update draft', 'sage') : __('Create draft', 'sage')); ?></button>
          <button type="button" class="button" data-step="test" <?php disabled(! $published || $state['id'] === ''); ?>><?php esc_html_e('Send me a test', 'sage'); ?></button>
        </p>
        <p><button type="button" class="button button-primary" data-step="send" <?php disabled(! $published || $state['id'] === ''); ?>><?php esc_html_e('Send to subscribers', 'sage'); ?></button></p>
      <?php } ?>
      <p id="mh-mailchimp-open" <?php echo $open === '' ? 'hidden' : ''; ?>><a href="<?php echo esc_url($open); ?>" target="_blank" rel="noopener"><?php esc_html_e('Open in Mailchimp', 'sage'); ?></a></p>
      <p id="mh-mailchimp-status" role="status" aria-live="polite"></p>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'post' || ! mh_mailchimp_is_configured()) {
        return;
    }
    wp_register_script('mh-mailchimp', false, [], '1', true);
    wp_enqueue_script('mh-mailchimp');
    wp_localize_script('mh-mailchimp', 'mhMailchimpI18n', [
        'working' => __('Working…', 'sage'),
        'confirmSend' => __('Send this newsletter to every subscriber now? This cannot be undone.', 'sage'),
        'networkError' => __('Network error', 'sage'),
    ]);
    wp_add_inline_script('mh-mailchimp', <<<'JS'
(function () {
  const root = document.getElementById('mh-mailchimp')
  const status = document.getElementById('mh-mailchimp-status')
  if (!root || !status || typeof ajaxurl === 'undefined') return
  const i18n = window.mhMailchimpI18n || {}
  const open = document.getElementById('mh-mailchimp-open')
  const send = root.querySelector('[data-step="send"]')
  const test = root.querySelector('[data-step="test"]')
  const draft = root.querySelector('[data-step="draft"]')

  root.addEventListener('click', async function (event) {
    const btn = event.target.closest('button[data-step]')
    if (!btn || btn.disabled) return
    const step = btn.dataset.step
    if (step === 'send' && !window.confirm(i18n.confirmSend)) return
    const body = new FormData()
    body.append('action', 'mh_mailchimp')
    body.append('nonce', root.dataset.nonce)
    body.append('post_id', root.dataset.post)
    body.append('step', step)
    btn.disabled = true
    status.textContent = i18n.working
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const json = await res.json()
      const data = (json && json.data) || {}
      status.textContent = data.message || ''
      if (data.openUrl && open) {
        open.hidden = false
        open.querySelector('a').href = data.openUrl
      }
      if (json && json.success && step === 'draft') {
        if (send) send.disabled = false
        if (test) test.disabled = false
        draft.textContent = draft.textContent.replace('Create', 'Update')
      }
      if (json && json.success && step === 'send') {
        if (send) send.disabled = true
        if (test) test.disabled = true
        if (draft) draft.disabled = true
      }
    } catch (e) {
      status.textContent = i18n.networkError
    } finally {
      if (step !== 'send') btn.disabled = false
    }
  })
})()
JS);
});
