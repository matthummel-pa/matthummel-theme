<?php

/**
 * Share journal posts to Bluesky: AI summary + link, auto-post on publish.
 */

namespace App;

/** Bluesky post limit (graphemes). Leave room for link line. */
const MH_BLUESKY_TEXT_MAX = 260;

/**
 * Bluesky handle (e.g. matthummel.bsky.social).
 */
function mh_bluesky_handle(): string
{
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_bluesky_handle', '')) : '';
    if ($mod === '') {
        $mod = 'matthummel.bsky.social';
    }

    return (string) apply_filters('mh/bluesky_handle', $mod);
}

/**
 * App password from Customizer or wp-config constant.
 */
function mh_bluesky_app_password(): string
{
    if (defined('MH_BLUESKY_APP_PASSWORD') && is_string(MH_BLUESKY_APP_PASSWORD) && MH_BLUESKY_APP_PASSWORD !== '') {
        return trim(MH_BLUESKY_APP_PASSWORD);
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_bluesky_app_password', '')) : '';

    return (string) apply_filters('mh/bluesky_app_password', $mod);
}

/**
 * Reuse the OpenAI key from DEV.to export settings.
 */
function mh_bluesky_ai_token(): string
{
    return mh_devto_ai_token();
}

function mh_bluesky_auto_share_enabled(): bool
{
    if (mh_bluesky_app_password() === '') {
        return false;
    }

    return (bool) apply_filters(
        'mh/bluesky_auto_share',
        (bool) get_theme_mod('mh_bluesky_auto_share', true)
    );
}

/**
 * Resolve PDS base URL for API calls (defaults to bsky.social).
 */
function mh_bluesky_pds_url(): string
{
    $custom = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_bluesky_pds', '')) : '';
    if ($custom !== '') {
        return untrailingslashit($custom);
    }

    $handle = mh_bluesky_handle();
    $resolved = mh_bluesky_resolve_pds($handle);

    return (string) apply_filters('mh/bluesky_pds_url', $resolved ?: 'https://bsky.social');
}

/**
 * @return array{accessJwt: string, did: string}|null
 */
function mh_bluesky_session(bool $force = false): ?array
{
    $key = 'mh_bluesky_session_v1';
    if (! $force) {
        $cached = get_transient($key);
        if (is_array($cached) && ! empty($cached['accessJwt']) && ! empty($cached['did'])) {
            return $cached;
        }
    }

    $identifier = mh_bluesky_handle();
    $password = mh_bluesky_app_password();
    if ($password === '') {
        return null;
    }

    $pds = mh_bluesky_pds_url();
    $res = wp_remote_post($pds.'/xrpc/com.atproto.server.createSession', [
        'timeout' => 20,
        'headers' => [
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'identifier' => $identifier,
            'password' => $password,
        ]),
    ]);

    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return null;
    }

    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if (! is_array($body) || empty($body['accessJwt']) || empty($body['did'])) {
        return null;
    }

    $session = [
        'accessJwt' => (string) $body['accessJwt'],
        'did' => (string) $body['did'],
    ];
    set_transient($key, $session, 50 * MINUTE_IN_SECONDS);

    return $session;
}

function mh_bluesky_resolve_pds(string $handle): string
{
    $res = wp_remote_get('https://bsky.social/xrpc/com.atproto.identity.resolveHandle?handle='.rawurlencode($handle), [
        'timeout' => 12,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return 'https://bsky.social';
    }
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    $did = is_array($body) ? (string) ($body['did'] ?? '') : '';
    if ($did === '') {
        return 'https://bsky.social';
    }

    $docUrl = str_starts_with($did, 'did:web:')
        ? 'https://'.substr($did, 8).'/.well-known/did.json'
        : 'https://plc.directory/'.$did;

    $docRes = wp_remote_get($docUrl, [
        'timeout' => 12,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);
    if (is_wp_error($docRes) || wp_remote_retrieve_response_code($docRes) !== 200) {
        return 'https://bsky.social';
    }

    $doc = json_decode((string) wp_remote_retrieve_body($docRes), true);
    if (! is_array($doc) || empty($doc['service']) || ! is_array($doc['service'])) {
        return 'https://bsky.social';
    }

    foreach ($doc['service'] as $service) {
        if (! is_array($service)) {
            continue;
        }
        if (($service['id'] ?? '') === '#atproto_pds' && ! empty($service['serviceEndpoint'])) {
            return untrailingslashit((string) $service['serviceEndpoint']);
        }
    }

    return 'https://bsky.social';
}

function mh_bluesky_grapheme_len(string $text): int
{
    if (function_exists('grapheme_strlen')) {
        return grapheme_strlen($text);
    }

    return mb_strlen($text, 'UTF-8');
}

function mh_bluesky_trim_text(string $text, int $max = MH_BLUESKY_TEXT_MAX): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mh_bluesky_grapheme_len($text) <= $max) {
        return $text;
    }

    $cut = mb_substr($text, 0, $max, 'UTF-8');
    $cut = preg_replace('/\s+\S*$/u', '', $cut) ?? $cut;
    $cut = rtrim($cut, '.,;:!?');

    return rtrim($cut).'…';
}

/**
 * Build link facet byte indices for AT Protocol rich text.
 *
 * @return array{text: string, facets: list<array<string, mixed>>}
 */
function mh_bluesky_with_link(string $body, string $url): array
{
    $body = trim($body);
    $url = esc_url_raw($url);
    $prefix = $body === '' ? '' : $body."\n\n";
    $full = $prefix.$url;
    $byteStart = strlen($prefix);
    $byteEnd = strlen($full);

    return [
        'text' => $full,
        'facets' => [[
            'index' => [
                'byteStart' => $byteStart,
                'byteEnd' => $byteEnd,
            ],
            'features' => [[
                '$type' => 'app.bsky.richtext.facet#link',
                'uri' => $url,
            ]],
        ]],
    ];
}

/**
 * Rule-based Bluesky copy from title + excerpt.
 */
function mh_bluesky_rule_compose(\WP_Post $post): string
{
    $title = html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = has_excerpt($post)
        ? get_the_excerpt($post)
        : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 22);

    $excerpt = html_entity_decode(wp_strip_all_tags($excerpt), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $line = $title;
    if ($excerpt !== '' && ! str_contains(strtolower($excerpt), strtolower($title))) {
        $line .= ' — '.$excerpt;
    }

    return mh_bluesky_trim_text($line);
}

/**
 * Optional OpenAI summary for Bluesky. Fails soft.
 */
function mh_bluesky_ai_compose(\WP_Post $post): ?string
{
    $token = mh_bluesky_ai_token();
    if ($token === '') {
        return null;
    }

    $title = get_the_title($post);
    $excerpt = has_excerpt($post)
        ? get_the_excerpt($post)
        : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 40);
    $excerpt = wp_strip_all_tags($excerpt);

    $prompt = "Write one Bluesky post announcing my new blog article.\n"
        .'Voice: first person (I/my). Plain, specific, no hashtag spam, no fake metrics.'."\n"
        .'Max '.MH_BLUESKY_TEXT_MAX.' characters. Do NOT include the URL — I append it.'."\n"
        ."Return ONLY the post text, no quotes or labels.\n\n"
        ."Title: {$title}\n"
        ."Summary: {$excerpt}";

    $res = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'model' => apply_filters('mh/bluesky_ai_model', apply_filters('mh/devto_ai_model', 'gpt-4o-mini')),
            'temperature' => 0.5,
            'messages' => [
                ['role' => 'system', 'content' => 'You write short social posts for a WordPress developer blog.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]),
    ]);

    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        return null;
    }

    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    $text = trim((string) ($body['choices'][0]['message']['content'] ?? ''));
    if ($text === '') {
        return null;
    }

    $text = trim($text, " \t\n\r\0\x0B\"'");

    return mh_bluesky_trim_text($text);
}

/**
 * Prepare Bluesky post text + facets for a journal post.
 *
 * @return array{ok: bool, body: string, text: string, facets: list<array<string, mixed>>, url: string, message: string}
 */
function mh_bluesky_prepare_share(int $postId, bool $useAi = true): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
        return [
            'ok' => false,
            'body' => '',
            'text' => '',
            'facets' => [],
            'url' => '',
            'message' => 'Not a journal post.',
        ];
    }

    $url = (string) get_permalink($post);
    if ($url === '') {
        return [
            'ok' => false,
            'body' => '',
            'text' => '',
            'facets' => [],
            'url' => '',
            'message' => 'Post has no URL yet.',
        ];
    }

    $custom = trim((string) get_post_meta($postId, '_mh_bluesky_custom_text', true));
    if ($custom !== '') {
        $body = mh_bluesky_trim_text($custom);
        $usedAi = false;
    } elseif ($useAi) {
        $ai = mh_bluesky_ai_compose($post);
        $body = is_string($ai) && $ai !== '' ? $ai : mh_bluesky_rule_compose($post);
        $usedAi = is_string($ai) && $ai !== '';
    } else {
        $body = mh_bluesky_rule_compose($post);
        $usedAi = false;
    }

    $body = (string) apply_filters('mh/bluesky_post_body', $body, $post);
    $linked = mh_bluesky_with_link($body, $url);
    $linked = (array) apply_filters('mh/bluesky_post_payload', $linked, $post);

    return [
        'ok' => true,
        'body' => $body,
        'text' => (string) ($linked['text'] ?? ''),
        'facets' => is_array($linked['facets'] ?? null) ? $linked['facets'] : [],
        'url' => $url,
        'message' => $custom !== ''
            ? 'Using custom Bluesky text from the editor.'
            : ($usedAi ? 'AI summary ready.' : 'Rule-based summary ready.'),
    ];
}

/**
 * Publish a prepared post to Bluesky.
 *
 * @param  array{text: string, facets: list<array<string, mixed>>}  $payload
 * @return array{ok: bool, uri: string, cid: string, url: string, message: string}
 */
function mh_bluesky_create_post(array $payload): array
{
    $session = mh_bluesky_session();
    if ($session === null) {
        return [
            'ok' => false,
            'uri' => '',
            'cid' => '',
            'url' => '',
            'message' => 'Add a Bluesky app password under Appearance → Customize → Bluesky.',
        ];
    }

    $pds = mh_bluesky_pds_url();
    $record = [
        '$type' => 'app.bsky.feed.post',
        'text' => $payload['text'],
        'createdAt' => gmdate('Y-m-d\TH:i:s\Z'),
        'langs' => ['en'],
    ];
    if (! empty($payload['facets'])) {
        $record['facets'] = $payload['facets'];
    }

    $res = wp_remote_post($pds.'/xrpc/com.atproto.repo.createRecord', [
        'timeout' => 25,
        'headers' => [
            'Authorization' => 'Bearer '.$session['accessJwt'],
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'repo' => $session['did'],
            'collection' => 'app.bsky.feed.post',
            'record' => $record,
        ]),
    ]);

    if (is_wp_error($res)) {
        return [
            'ok' => false,
            'uri' => '',
            'cid' => '',
            'url' => '',
            'message' => $res->get_error_message(),
        ];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code === 401) {
        delete_transient('mh_bluesky_session_v1');
        $session = mh_bluesky_session(true);
        if ($session !== null) {
            return mh_bluesky_create_post($payload);
        }
    }
    if ($code < 200 || $code >= 300 || ! is_array($body)) {
        $err = is_array($body) ? wp_json_encode($body) : (string) wp_remote_retrieve_body($res);

        return [
            'ok' => false,
            'uri' => '',
            'cid' => '',
            'url' => '',
            'message' => 'Bluesky API error ('.$code.'): '.$err,
        ];
    }

    $uri = (string) ($body['uri'] ?? '');
    $cid = (string) ($body['cid'] ?? '');
    $webUrl = mh_bluesky_uri_to_url($uri);

    return [
        'ok' => true,
        'uri' => $uri,
        'cid' => $cid,
        'url' => $webUrl,
        'message' => 'Posted to Bluesky.',
    ];
}

function mh_bluesky_uri_to_url(string $uri): string
{
    if ($uri === '' || ! str_contains($uri, 'app.bsky.feed.post/')) {
        return '';
    }
    $parts = explode('/', $uri);
    $rkey = end($parts);
    $handle = mh_bluesky_handle();

    return 'https://bsky.app/profile/'.rawurlencode($handle).'/post/'.rawurlencode((string) $rkey);
}

/**
 * Share a journal post to Bluesky.
 *
 * @return array{ok: bool, body: string, text: string, uri: string, url: string, message: string}
 */
function mh_bluesky_share_post(int $postId, bool $useAi = true, bool $force = false): array
{
    if (! $force && get_post_meta($postId, '_mh_bluesky_uri', true)) {
        return [
            'ok' => true,
            'body' => (string) get_post_meta($postId, '_mh_bluesky_body', true),
            'text' => (string) get_post_meta($postId, '_mh_bluesky_text', true),
            'uri' => (string) get_post_meta($postId, '_mh_bluesky_uri', true),
            'url' => (string) get_post_meta($postId, '_mh_bluesky_url', true),
            'message' => 'Already shared on Bluesky.',
        ];
    }

    $prepared = mh_bluesky_prepare_share($postId, $useAi);
    if (! $prepared['ok']) {
        return [
            'ok' => false,
            'body' => '',
            'text' => '',
            'uri' => '',
            'url' => '',
            'message' => $prepared['message'],
        ];
    }

    $result = mh_bluesky_create_post([
        'text' => $prepared['text'],
        'facets' => $prepared['facets'],
    ]);

    if (! $result['ok']) {
        return [
            'ok' => false,
            'body' => $prepared['body'],
            'text' => $prepared['text'],
            'uri' => '',
            'url' => '',
            'message' => $result['message'],
        ];
    }

    update_post_meta($postId, '_mh_bluesky_uri', sanitize_text_field($result['uri']));
    update_post_meta($postId, '_mh_bluesky_cid', sanitize_text_field($result['cid']));
    if ($result['url'] !== '') {
        update_post_meta($postId, '_mh_bluesky_url', esc_url_raw($result['url']));
    }
    update_post_meta($postId, '_mh_bluesky_body', sanitize_textarea_field($prepared['body']));
    update_post_meta($postId, '_mh_bluesky_text', sanitize_textarea_field($prepared['text']));
    update_post_meta($postId, '_mh_bluesky_shared_at', (string) time());

    return [
        'ok' => true,
        'body' => $prepared['body'],
        'text' => $prepared['text'],
        'uri' => $result['uri'],
        'url' => $result['url'],
        'message' => $prepared['message'].' '.$result['message'],
    ];
}

function mh_bluesky_should_auto_share(\WP_Post $post): bool
{
    if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
        return false;
    }
    if (! mh_bluesky_auto_share_enabled()) {
        return false;
    }
    if (get_post_meta($post->ID, '_mh_bluesky_uri', true)) {
        return false;
    }
    if (get_post_meta($post->ID, '_mh_devto_id', true)) {
        return false;
    }
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) {
        return false;
    }

    return (bool) apply_filters('mh/bluesky_should_auto_share', true, $post);
}

function mh_bluesky_queue_share(int $postId): void
{
    if (wp_next_scheduled('mh_bluesky_share_post', [$postId])) {
        return;
    }
    wp_schedule_single_event(time() + 20, 'mh_bluesky_share_post', [$postId]);
}

add_action('mh_bluesky_share_post', function (int $postId): void {
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || ! mh_bluesky_should_auto_share($post)) {
        return;
    }
    mh_bluesky_share_post($postId, true, false);
});

add_action('transition_post_status', function (string $new, string $old, \WP_Post $post): void {
    if ($new !== 'publish' || $old === 'publish') {
        return;
    }
    if (! mh_bluesky_should_auto_share($post)) {
        return;
    }
    mh_bluesky_queue_share((int) $post->ID);
}, 20, 3);

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_bluesky_share',
        __('Bluesky', 'sage'),
        __NAMESPACE__.'\\mh_bluesky_share_metabox',
        'post',
        'side',
        'default'
    );
});

function mh_bluesky_share_metabox(\WP_Post $post): void
{
    wp_nonce_field('mh_bluesky_share', 'mh_bluesky_share_nonce');
    $shareUrl = (string) get_post_meta($post->ID, '_mh_bluesky_url', true);
    $custom = (string) get_post_meta($post->ID, '_mh_bluesky_custom_text', true);
    $hasPass = mh_bluesky_app_password() !== '';
    $hasAi = mh_bluesky_ai_token() !== '';

    echo '<p class="description">'.esc_html__('Share this journal post on Bluesky. Paste a ChatGPT summary below, or let the theme draft one on publish.', 'sage').'</p>';

    if ($shareUrl !== '') {
        printf(
            '<p><strong>%1$s</strong> <a href="%2$s" target="_blank" rel="noopener">%3$s</a></p>',
            esc_html__('On Bluesky:', 'sage'),
            esc_url($shareUrl),
            esc_html__('View post', 'sage')
        );
    }

    if (! $hasPass) {
        echo '<p class="description">'.esc_html__('Add a Bluesky app password under Appearance → Customize → Bluesky.', 'sage').'</p>';
    }

    echo '<p><label for="mh-bluesky-custom"><strong>'.esc_html__('Custom post text (optional)', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-bluesky-custom" name="mh_bluesky_custom_text" class="widefat" rows="4" maxlength="320" placeholder="';
    echo esc_attr__('Paste your ChatGPT summary here — URL is added automatically.', 'sage');
    echo '">'.esc_textarea($custom).'</textarea>';

    echo '<p><label><input type="checkbox" name="mh_bluesky_use_ai" value="1" '.checked($hasAi, true, false).'> ';
    echo esc_html__('AI summary when custom text is empty', 'sage').'</label></p>';
    echo '<p class="description">'.esc_html__('Uses the OpenAI key from Customize → DEV.to. Without it, title + excerpt is used.', 'sage').'</p>';

    echo '<p style="display:flex;flex-direction:column;gap:6px">';
    echo '<button type="button" class="button" id="mh-bluesky-preview">'.esc_html__('Preview post', 'sage').'</button>';
    echo '<button type="button" class="button button-primary" id="mh-bluesky-share" '.disabled(! $hasPass, true, false).'>'.esc_html__('Post to Bluesky now', 'sage').'</button>';
    echo '</p>';
    echo '<p id="mh-bluesky-status" class="description" aria-live="polite"></p>';
    echo '<textarea id="mh-bluesky-preview-box" class="widefat" rows="6" readonly style="font-family:ui-monospace,monospace;font-size:12px"></textarea>';
}

add_action('save_post_post', function (int $postId): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! isset($_POST['mh_bluesky_share_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_bluesky_share_nonce'])), 'mh_bluesky_share')) {
        return;
    }
    if (! current_user_can('edit_post', $postId)) {
        return;
    }
    $custom = isset($_POST['mh_bluesky_custom_text'])
        ? sanitize_textarea_field(wp_unslash($_POST['mh_bluesky_custom_text']))
        : '';
    if ($custom === '') {
        delete_post_meta($postId, '_mh_bluesky_custom_text');
    } else {
        update_post_meta($postId, '_mh_bluesky_custom_text', $custom);
    }
}, 15);

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'post') {
        return;
    }

    wp_register_script('mh-bluesky-share', '', ['wp-util'], '1', true);
    wp_enqueue_script('mh-bluesky-share');
    wp_add_inline_script('mh-bluesky-share', <<<'JS'
(function () {
  const status = document.getElementById('mh-bluesky-status')
  const preview = document.getElementById('mh-bluesky-preview-box')
  const nonce = document.getElementById('mh_bluesky_share_nonce')
  const custom = document.getElementById('mh-bluesky-custom')
  if (!status || !preview || !nonce || typeof ajaxurl === 'undefined') return

  function useAi () {
    const el = document.querySelector('input[name="mh_bluesky_use_ai"]')
    return !!(el && el.checked)
  }

  function postId () {
    const el = document.getElementById('post_ID')
    return el ? el.value : '0'
  }

  function setStatus (msg, isError) {
    status.textContent = msg || ''
    status.style.color = isError ? '#b32d2e' : ''
  }

  async function call (action) {
    setStatus('Working…')
    const body = new FormData()
    body.append('action', action)
    body.append('nonce', nonce.value)
    body.append('post_id', postId())
    body.append('use_ai', useAi() ? '1' : '0')
    if (custom) body.append('custom_text', custom.value)
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      if (!data || !data.success) {
        setStatus((data && data.data && data.data.message) || 'Request failed', true)
        if (data && data.data && data.data.text) preview.value = data.data.text
        return null
      }
      return data.data
    } catch (err) {
      setStatus('Network error', true)
      return null
    }
  }

  document.getElementById('mh-bluesky-preview')?.addEventListener('click', async function () {
    const data = await call('mh_bluesky_preview')
    if (!data) return
    preview.value = data.text || ''
    setStatus(data.message || 'Preview ready')
  })

  document.getElementById('mh-bluesky-share')?.addEventListener('click', async function () {
    if (!window.confirm('Post this article to Bluesky now?')) return
    const data = await call('mh_bluesky_share')
    if (!data) return
    preview.value = data.text || ''
    setStatus((data.message || 'Posted') + (data.url ? ' — ' + data.url : ''))
  })
})()
JS);
});

function mh_bluesky_ajax_guard(): int
{
    if (! current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }
    check_ajax_referer('mh_bluesky_share', 'nonce');
    $postId = (int) ($_POST['post_id'] ?? 0);
    if ($postId <= 0 || ! current_user_can('edit_post', $postId)) {
        wp_send_json_error(['message' => 'Invalid post'], 400);
    }

    if (isset($_POST['custom_text'])) {
        $custom = sanitize_textarea_field(wp_unslash($_POST['custom_text']));
        if ($custom === '') {
            delete_post_meta($postId, '_mh_bluesky_custom_text');
        } else {
            update_post_meta($postId, '_mh_bluesky_custom_text', $custom);
        }
    }

    return $postId;
}

add_action('wp_ajax_mh_bluesky_preview', function (): void {
    $postId = mh_bluesky_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    $prepared = mh_bluesky_prepare_share($postId, $useAi);
    if (! $prepared['ok']) {
        wp_send_json_error(['message' => $prepared['message']]);
    }
    wp_send_json_success([
        'body' => $prepared['body'],
        'text' => $prepared['text'],
        'message' => $prepared['message'],
    ]);
});

add_action('wp_ajax_mh_bluesky_share', function (): void {
    $postId = mh_bluesky_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    $result = mh_bluesky_share_post($postId, $useAi, true);
    if (! $result['ok']) {
        wp_send_json_error([
            'message' => $result['message'],
            'text' => $result['text'],
        ]);
    }
    wp_send_json_success($result);
});

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh bluesky-share', function ($args, $assoc): void {
        $postId = (int) ($args[0] ?? 0);
        if ($postId <= 0) {
            \WP_CLI::error('Usage: wp mh bluesky-share <post_id> [--dry-run] [--skip-ai] [--force]');
        }
        $dry = isset($assoc['dry-run']);
        $useAi = ! isset($assoc['skip-ai']);
        $force = isset($assoc['force']);

        $prepared = mh_bluesky_prepare_share($postId, $useAi);
        if (! $prepared['ok']) {
            \WP_CLI::error($prepared['message']);
        }

        \WP_CLI::line('Body: '.$prepared['body']);
        \WP_CLI::line('Full post:');
        \WP_CLI::line($prepared['text']);

        if ($dry) {
            \WP_CLI::success('Dry run — not posted.');

            return;
        }

        $result = mh_bluesky_share_post($postId, $useAi, $force);
        if (! $result['ok']) {
            \WP_CLI::error($result['message']);
        }
        \WP_CLI::success($result['message'].($result['url'] !== '' ? ' '.$result['url'] : ''));
    }, [
        'shortdesc' => 'Share a journal post on Bluesky (AI summary + link).',
        'synopsis' => [
            ['type' => 'positional', 'name' => 'post_id', 'optional' => false],
            ['type' => 'flag', 'name' => 'dry-run', 'optional' => true, 'description' => 'Preview text only; do not post.'],
            ['type' => 'flag', 'name' => 'skip-ai', 'optional' => true],
            ['type' => 'flag', 'name' => 'force', 'optional' => true, 'description' => 'Post even if already shared.'],
        ],
    ]);
}
