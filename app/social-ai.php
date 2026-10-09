<?php

/**
 * Social AI drafting providers (OpenAI, Claude, Grokbot), per-network API
 * connections, "copy brief" prompts for Claude Cowork / Grokbot, and the
 * the token lookups (the inputs live in social-settings.php).
 *
 * Tokens resolve in this order: wp-config constant, saved theme setting, filter.
 * Prefer the constants on production so keys stay out of the database.
 */

namespace App;

/**
 * Where to generate each key, and which settings section stores it.
 *
 * @return array<string, array{label: string, key_url: string, section: string, hint: string}>
 */
function mh_social_connection_meta(): array
{
    return [
        'bluesky' => [
            'label' => 'Bluesky',
            'key_url' => 'https://bsky.app/settings/app-passwords',
            'section' => 'mh_bluesky',
            'hint' => 'Create an app password (never your account password).',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'key_url' => 'https://developers.facebook.com/tools/explorer/',
            'section' => 'mh_facebook',
            'hint' => 'Graph API Explorer: pick your app and Page, then generate a Page access token.',
        ],
        'devto' => [
            'label' => 'DEV.to',
            'key_url' => 'https://dev.to/settings/extensions',
            'section' => 'mh_devto',
            'hint' => 'Settings → Extensions → DEV Community API Keys.',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'key_url' => 'https://www.linkedin.com/developers/tools/oauth/token-generator',
            'section' => 'mh_linkedin',
            'hint' => 'Developer portal OAuth token generator (needs an app).',
        ],
        'openai' => [
            'label' => 'OpenAI',
            'key_url' => 'https://platform.openai.com/api-keys',
            'section' => 'mh_devto',
            'hint' => 'Create a secret key.',
        ],
        'claude' => [
            'label' => 'Claude Cowork',
            'key_url' => 'https://console.anthropic.com/settings/keys',
            'section' => 'mh_ai_drafting',
            'hint' => 'Anthropic Console → API keys.',
        ],
        'grok' => [
            'label' => 'Grokbot',
            'key_url' => 'https://console.x.ai',
            'section' => 'mh_ai_drafting',
            'hint' => 'xAI Console → API keys.',
        ],
    ];
}

/**
 * Extra provider pages: setup guide and the account/app dashboard where a
 * connection is linked or secrets are managed.
 *
 * @return array<string, array{guide: string, guide_label: string, dashboard: string, dashboard_label: string}>
 */
function mh_social_connection_links(): array
{
    return [
        'bluesky' => [
            'guide' => 'https://docs.bsky.app/docs/get-started',
            'guide_label' => 'Bluesky API guide',
            'dashboard' => 'https://bsky.app/settings/app-passwords',
            'dashboard_label' => 'Manage app passwords',
        ],
        'facebook' => [
            'guide' => 'https://developers.facebook.com/docs/pages-api/getting-started',
            'guide_label' => 'Pages API guide',
            'dashboard' => 'https://developers.facebook.com/apps/',
            'dashboard_label' => 'Your Meta apps',
        ],
        'devto' => [
            'guide' => 'https://developers.forem.com/api/v1',
            'guide_label' => 'Forem API docs',
            'dashboard' => 'https://dev.to/settings/extensions',
            'dashboard_label' => 'Manage API keys',
        ],
        'linkedin' => [
            'guide' => 'https://learn.microsoft.com/linkedin/shared/authentication/authentication',
            'guide_label' => 'LinkedIn auth guide',
            'dashboard' => 'https://www.linkedin.com/developers/apps',
            'dashboard_label' => 'Your LinkedIn apps',
        ],
        'openai' => [
            'guide' => 'https://platform.openai.com/docs/quickstart',
            'guide_label' => 'OpenAI quickstart',
            'dashboard' => 'https://platform.openai.com/settings/organization/billing/overview',
            'dashboard_label' => 'Billing and limits',
        ],
        'claude' => [
            'guide' => 'https://docs.anthropic.com/en/api/getting-started',
            'guide_label' => 'Anthropic API setup',
            'dashboard' => 'https://console.anthropic.com/settings/billing',
            'dashboard_label' => 'Billing and credits',
        ],
        'grok' => [
            'guide' => 'https://docs.x.ai/docs/overview',
            'guide_label' => 'xAI API docs',
            'dashboard' => 'https://console.x.ai/',
            'dashboard_label' => 'xAI console',
        ],
    ];
}

function mh_social_token_from(array $constants, string $mod, string $filter): string
{
    foreach ($constants as $const) {
        if (defined($const) && is_string(constant($const)) && constant($const) !== '') {
            return trim((string) constant($const));
        }
    }
    $value = function_exists('get_theme_mod') ? trim((string) get_theme_mod($mod, '')) : '';

    return (string) apply_filters($filter, $value);
}

function mh_anthropic_token(): string
{
    return mh_social_token_from(['MH_ANTHROPIC_API_KEY', 'ANTHROPIC_API_KEY'], 'mh_anthropic_token', 'mh/anthropic_token');
}

function mh_xai_token(): string
{
    return mh_social_token_from(['MH_XAI_API_KEY', 'XAI_API_KEY'], 'mh_xai_token', 'mh/xai_token');
}

function mh_facebook_page_token(): string
{
    return mh_social_token_from(['MH_FACEBOOK_PAGE_TOKEN'], 'mh_fb_page_token', 'mh/facebook_page_token');
}

function mh_facebook_page_id(): string
{
    return trim((string) get_theme_mod('mh_fb_page_id', ''));
}

/**
 * @return array<string, bool> network/provider slug => has credentials
 */
function mh_social_connection_status(): array
{
    $linkedin = function_exists(__NAMESPACE__.'\\linkedin_token') ? linkedin_token() : '';

    return [
        'bluesky' => function_exists(__NAMESPACE__.'\\mh_bluesky_app_password') && mh_bluesky_app_password() !== '',
        'facebook' => mh_facebook_page_token() !== '',
        'devto' => function_exists(__NAMESPACE__.'\\mh_devto_token') && mh_devto_token() !== '',
        'linkedin' => $linkedin !== '',
        'openai' => function_exists(__NAMESPACE__.'\\mh_devto_ai_token') && mh_devto_ai_token() !== '',
        'claude' => mh_anthropic_token() !== '',
        'grok' => mh_xai_token() !== '',
    ];
}

/**
 * AI providers the editor can draft with.
 *
 * @return array<string, string> slug => label
 */
function mh_social_ai_provider_labels(): array
{
    return [
        'openai' => 'OpenAI',
        'claude' => 'Claude Cowork',
        'grok' => 'Grokbot',
    ];
}

/**
 * Model dropdown choices for a provider: built-in list, plus every model the
 * account's key can use (fetched from the provider, cached 12 hours), plus the
 * currently saved model so it never disappears from the list.
 *
 * @return array<string, string> model id => label
 */
function mh_social_model_choices(string $provider, string $current = ''): array
{
    $choices = match ($provider) {
        'claude' => [
            'claude-sonnet-5-5' => 'Claude Sonnet 5.5 (balanced)',
            'claude-opus-5-5' => 'Claude Opus 5.5 (strongest)',
            'claude-haiku-5-5' => 'Claude Haiku 5.5 (fastest)',
            'claude-fable-5-1' => 'Claude Fable 5.1',
        ],
        'grok' => [
            'grok-4' => 'Grok 4',
            'grok-4-fast' => 'Grok 4 Fast',
            'grok-3' => 'Grok 3',
            'grok-3-mini' => 'Grok 3 Mini',
        ],
        default => [
            'gpt-4o-mini' => 'GPT-4o mini',
            'gpt-4o' => 'GPT-4o',
        ],
    };

    foreach (mh_social_remote_models($provider) as $id => $label) {
        $choices[$id] ??= $label;
    }
    if ($current !== '' && ! isset($choices[$current])) {
        $choices[$current] = $current.' (saved)';
    }

    return $choices;
}

/**
 * Models the provider's API reports for this key. Cached; empty on any failure.
 *
 * @return array<string, string>
 */
function mh_social_remote_models(string $provider): array
{
    [$endpoint, $headers, $token] = match ($provider) {
        'claude' => ['https://api.anthropic.com/v1/models?limit=100', ['anthropic-version' => '2023-06-01'], mh_anthropic_token()],
        'grok' => ['https://api.x.ai/v1/models', [], mh_xai_token()],
        default => ['', [], ''],
    };
    if ($token === '' || $endpoint === '' || ! function_exists('get_transient')) {
        return [];
    }

    $cacheKey = 'mh_models_'.$provider.'_'.substr(md5($token), 0, 8);
    $cached = get_transient($cacheKey);
    if (is_array($cached)) {
        return $cached;
    }

    $headers += $provider === 'claude' ? ['x-api-key' => $token] : ['Authorization' => 'Bearer '.$token];
    $res = wp_remote_get($endpoint, ['timeout' => 6, 'headers' => $headers + ['User-Agent' => 'matthummel.com']]);
    $models = [];
    if (! is_wp_error($res) && (int) wp_remote_retrieve_response_code($res) === 200) {
        $body = json_decode((string) wp_remote_retrieve_body($res), true);
        foreach ((array) ($body['data'] ?? []) as $row) {
            $id = sanitize_text_field((string) ($row['id'] ?? ''));
            if ($id !== '') {
                $models[$id] = sanitize_text_field((string) ($row['display_name'] ?? $id));
            }
        }
    }
    // Cache failures briefly so a bad key does not slow every Customizer load.
    set_transient($cacheKey, $models, $models ? 12 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);

    return $models;
}

function mh_social_default_model(string $provider): string
{
    return match ($provider) {
        'claude' => (string) get_theme_mod('mh_anthropic_model', 'claude-sonnet-5-5'),
        'grok' => (string) (get_theme_mod('mh_xai_model_custom', '') ?: get_theme_mod('mh_xai_model', 'grok-4')),
        default => 'gpt-4o-mini',
    };
}

/**
 * Model requested from the editor for this request (empty = saved default).
 */
function mh_social_ai_requested_model(?string $set = null): string
{
    static $model = '';
    if ($set !== null) {
        $model = trim(preg_replace('/[^A-Za-z0-9._:\/-]/', '', $set) ?? '');
    }

    return $model;
}

/**
 * Provider requested for this request (set by the editor AJAX handlers).
 */
function mh_social_ai_requested(?string $set = null): string
{
    static $requested = '';
    if ($set !== null) {
        $requested = array_key_exists($set, mh_social_ai_provider_labels()) ? $set : '';
    }

    return $requested;
}

/**
 * Provider to use: the requested one, else the Customizer default, else the
 * first provider that has a key, else OpenAI.
 */
function mh_social_ai_provider(): string
{
    $labels = mh_social_ai_provider_labels();
    $status = mh_social_connection_status();

    $requested = mh_social_ai_requested();
    if ($requested !== '') {
        return $requested;
    }

    $default = (string) get_theme_mod('mh_social_ai_default', 'claude');
    if (isset($labels[$default]) && ! empty($status[$default])) {
        return $default;
    }
    foreach (array_keys($labels) as $slug) {
        if (! empty($status[$slug])) {
            return $slug;
        }
    }

    return 'openai';
}

function mh_social_ai_available(): bool
{
    $status = mh_social_connection_status();
    foreach (array_keys(mh_social_ai_provider_labels()) as $slug) {
        if (! empty($status[$slug])) {
            return true;
        }
    }

    return false;
}

function mh_social_ai_last_error(?string $set = null): string
{
    static $error = '';
    if ($set !== null) {
        $error = $set;
    }

    return $error;
}

/**
 * Run one short completion through the chosen provider. Fails soft (null);
 * the reason is available from mh_social_ai_last_error().
 */
function mh_social_ai_complete(string $system, string $prompt, int $maxTokens = 400, ?string $provider = null): ?string
{
    $provider = $provider ?: mh_social_ai_provider();
    mh_social_ai_last_error('');

    $text = match ($provider) {
        'claude' => mh_social_ai_call_claude($system, $prompt, $maxTokens),
        'grok' => mh_social_ai_call_openai_compatible(
            'https://api.x.ai/v1/chat/completions',
            mh_xai_token(),
            (string) apply_filters('mh/xai_model', mh_social_ai_requested_model() !== '' && $provider === 'grok' ? mh_social_ai_requested_model() : mh_social_default_model('grok')),
            $system,
            $prompt,
            $maxTokens,
            'Grokbot'
        ),
        default => mh_social_ai_call_openai_compatible(
            'https://api.openai.com/v1/chat/completions',
            function_exists(__NAMESPACE__.'\\mh_devto_ai_token') ? mh_devto_ai_token() : '',
            (string) apply_filters('mh/social_ai_model', apply_filters('mh/devto_ai_model', mh_social_ai_requested_model() !== '' ? mh_social_ai_requested_model() : 'gpt-4o-mini')),
            $system,
            $prompt,
            $maxTokens,
            'OpenAI'
        ),
    };

    if ($text === null || $text === '') {
        return null;
    }

    return trim($text, " \t\n\r\0\x0B\"'");
}

function mh_social_ai_call_claude(string $system, string $prompt, int $maxTokens): ?string
{
    $token = mh_anthropic_token();
    if ($token === '') {
        mh_social_ai_last_error('Claude Cowork needs an Anthropic API key (Appearance → Social & AI).');

        return null;
    }

    $model = (string) apply_filters('mh/anthropic_model', mh_social_ai_requested_model() !== '' ? mh_social_ai_requested_model() : (string) get_theme_mod('mh_anthropic_model', 'claude-sonnet-5-5'));
    $res = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'timeout' => 45,
        'headers' => [
            'x-api-key' => $token,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]),
    ]);

    if (is_wp_error($res)) {
        mh_social_ai_last_error('Claude request failed: '.$res->get_error_message());

        return null;
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code !== 200) {
        mh_social_ai_last_error('Claude returned HTTP '.$code.': '.sanitize_text_field((string) ($body['error']['message'] ?? 'request rejected')));

        return null;
    }

    $text = '';
    foreach ((array) ($body['content'] ?? []) as $block) {
        if (($block['type'] ?? '') === 'text') {
            $text .= (string) ($block['text'] ?? '');
        }
    }

    return trim($text);
}

function mh_social_ai_call_openai_compatible(string $endpoint, string $token, string $model, string $system, string $prompt, int $maxTokens, string $label): ?string
{
    if ($token === '') {
        mh_social_ai_last_error($label.' needs an API key (Appearance → Social & AI).');

        return null;
    }

    $res = wp_remote_post($endpoint, [
        'timeout' => 45,
        'headers' => [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'model' => $model,
            'temperature' => 0.5,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]),
    ]);

    if (is_wp_error($res)) {
        mh_social_ai_last_error($label.' request failed: '.$res->get_error_message());

        return null;
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code !== 200) {
        $detail = $body['error']['message'] ?? $body['error'] ?? 'request rejected';
        mh_social_ai_last_error($label.' returned HTTP '.$code.': '.sanitize_text_field(is_string($detail) ? $detail : 'request rejected'));

        return null;
    }

    return trim((string) ($body['choices'][0]['message']['content'] ?? ''));
}

/**
 * Prompt to paste into Claude Cowork or Grokbot (no API key needed).
 */
function mh_social_brief(int $postId): string
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post) {
        return '';
    }

    $title = html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $url = (string) get_permalink($post);
    $excerpt = mh_social_post_excerpt($post, 60);
    $bluesky = defined(__NAMESPACE__.'\\MH_BLUESKY_TEXT_MAX') ? MH_BLUESKY_TEXT_MAX : 260;

    return "Write social posts announcing my new article. Voice: first person, plain and specific, no hashtag spam, no invented metrics.\n\n"
        ."Article: {$title}\n"
        ."URL: {$url}\n"
        ."Summary: {$excerpt}\n\n"
        ."Return one draft per network, labelled:\n"
        ."- Bluesky: max {$bluesky} characters, URL added after the text.\n"
        .'- Facebook: max '.MH_SOCIAL_FACEBOOK_MAX." characters, 2-4 short sentences.\n"
        .'- LinkedIn: max '.MH_SOCIAL_LINKEDIN_MAX." characters, hook in the first line.\n"
        .'- Reddit: a title (max '.MH_SOCIAL_REDDIT_TITLE_MAX.') and a body (max '.MH_SOCIAL_REDDIT_BODY_MAX.") that is helpful rather than salesy.\n"
        ."- DEV.to: five tags and a one-line description.\n";
}

/**
 * Link to the Social & AI settings screen (Appearance → Social & AI).
 */
function mh_social_customizer_url(string $section = ''): string
{
    return function_exists(__NAMESPACE__.'\\mh_social_settings_url')
        ? mh_social_settings_url().($section !== '' ? '#mh-social-'.$section : '')
        : admin_url('themes.php?page=mh-social-settings');
}

/**
 * Meta box block: choose the drafting provider and copy a brief.
 */
function mh_social_ai_controls_html(): string
{
    $status = mh_social_connection_status();
    $labels = mh_social_ai_provider_labels();
    $selected = mh_social_ai_provider();

    $html = '<p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">';
    $html .= '<label><input type="checkbox" name="mh_social_use_ai" id="mh-social-use-ai" value="1" '.checked(mh_social_ai_available(), true, false).'> ';
    $html .= esc_html__('Use AI when generating', 'sage').'</label>';
    $html .= '<label for="mh-social-provider"><strong>'.esc_html__('Draft with', 'sage').'</strong></label>';
    $html .= '<select id="mh-social-provider">';
    foreach ($labels as $slug => $label) {
        $suffix = ! empty($status[$slug]) ? '' : ' — '.__('no key', 'sage');
        $html .= sprintf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr($slug),
            selected($selected, $slug, false),
            esc_html($label.$suffix)
        );
    }
    $html .= '</select>';

    $models = [];
    foreach (array_keys($labels) as $slug) {
        $models[$slug] = mh_social_model_choices($slug, mh_social_default_model($slug));
    }
    $html .= '<label for="mh-social-model"><strong>'.esc_html__('Model', 'sage').'</strong></label>';
    $defaults = [];
    foreach (array_keys($labels) as $slug) {
        $defaults[$slug] = mh_social_default_model($slug);
    }
    $html .= '<select id="mh-social-model" data-models="'.esc_attr((string) wp_json_encode($models)).'" data-defaults="'.esc_attr((string) wp_json_encode($defaults)).'">';
    foreach ($models[$selected] ?? [] as $id => $label) {
        $html .= sprintf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($id), selected(mh_social_default_model($selected), $id, false), esc_html($label));
    }
    $html .= '</select>';
    $html .= '<button type="button" class="button mh-social-brief" data-target="Claude Cowork">'.esc_html__('Copy brief for Claude Cowork', 'sage').'</button>';
    $html .= '<button type="button" class="button mh-social-brief" data-target="Grokbot">'.esc_html__('Copy brief for Grokbot', 'sage').'</button>';
    $html .= '</p>';

    return $html;
}

/**
 * Meta box block: connection status with "get a token" and "add token" links.
 */
function mh_social_connections_html(): string
{
    $status = mh_social_connection_status();
    $html = '<div class="mh-social-connections" style="margin-top:16px"><p><strong>'.esc_html__('API connections', 'sage').'</strong></p>';
    $html .= '<table class="widefat striped" style="max-width:720px"><tbody>';
    foreach (mh_social_connection_meta() as $slug => $meta) {
        $html .= '<tr>';
        $html .= '<td><strong>'.esc_html($meta['label']).'</strong></td>';
        $html .= '<td>'.(! empty($status[$slug])
            ? '<span style="color:#1a7f37">'.esc_html__('Token saved', 'sage').'</span>'
            : '<span style="color:#b32d2e">'.esc_html__('No token', 'sage').'</span>').'</td>';
        $html .= '<td><a href="'.esc_url($meta['key_url']).'" target="_blank" rel="noopener noreferrer" title="'.esc_attr($meta['hint']).'">'.esc_html__('Generate token', 'sage').'</a></td>';
        $extra = mh_social_connection_links()[$slug] ?? null;
        $html .= '<td>'.($extra ? '<a href="'.esc_url($extra['guide']).'" target="_blank" rel="noopener noreferrer">'.esc_html__('Setup guide', 'sage').'</a>' : '').'</td>';
        $html .= '<td><a href="'.esc_url(mh_social_customizer_url()).'">'.esc_html__('Add token', 'sage').'</a></td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';

    return $html;
}

/**
 * Editor script additions: provider select + copy brief buttons.
 */
add_action('admin_enqueue_scripts', function (string $hook): void {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'post') {
        return;
    }

    wp_register_script('mh-social-ai', '', ['wp-util'], '1', true);
    wp_enqueue_script('mh-social-ai');
    wp_add_inline_script('mh-social-ai', <<<'JS'
(function () {
  const root = document.getElementById('mh-social-share')
  const nonce = document.getElementById('mh_social_share_nonce')
  const status = document.getElementById('mh-social-status')
  const preview = document.getElementById('mh-social-preview')
  if (!root || !nonce || typeof ajaxurl === 'undefined') return

  function say (msg, isError) {
    if (!status) return
    status.textContent = msg || ''
    status.style.color = isError ? '#b32d2e' : ''
  }

  const providerSel = document.getElementById('mh-social-provider')
  const modelSel = document.getElementById('mh-social-model')
  if (providerSel && modelSel) {
    let models = {}
    let defaults = {}
    try {
      models = JSON.parse(modelSel.getAttribute('data-models') || '{}')
      defaults = JSON.parse(modelSel.getAttribute('data-defaults') || '{}')
    } catch (e) { /* keep empty */ }
    const fill = function () {
      const list = models[providerSel.value] || {}
      modelSel.innerHTML = ''
      Object.keys(list).forEach(function (id) {
        const opt = document.createElement('option')
        opt.value = id
        opt.textContent = list[id]
        if (id === defaults[providerSel.value]) opt.selected = true
        modelSel.appendChild(opt)
      })
    }
    providerSel.addEventListener('change', fill)
  }

  root.querySelectorAll('.mh-social-brief').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      const body = new FormData()
      body.append('action', 'mh_social_brief')
      body.append('nonce', nonce.value)
      body.append('post_id', (document.getElementById('post_ID') || {}).value || '0')
      try {
        const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
        const data = await res.json()
        if (!data || !data.success) {
          say((data && data.data && data.data.message) || 'Could not build the brief', true)
          return
        }
        if (preview) preview.value = data.data.brief
        await navigator.clipboard.writeText(data.data.brief)
        say('Brief copied. Paste it into ' + btn.getAttribute('data-target') + '.')
      } catch (err) {
        say('Could not copy the brief. Select it in the preview box instead.', true)
      }
    })
  })
})()
JS);
});

add_action('wp_ajax_mh_social_brief', function (): void {
    $postId = mh_social_ajax_guard();
    wp_send_json_success(['brief' => mh_social_brief($postId)]);
});
