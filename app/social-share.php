<?php

/**
 * Journal social share: frontend buttons + editor draft generators / auto-share.
 */

namespace App;

/** Soft caps for draft generators (platform guidance, not hard API limits). */
const MH_SOCIAL_FACEBOOK_MAX = 400;

const MH_SOCIAL_REDDIT_TITLE_MAX = 300;

const MH_SOCIAL_REDDIT_BODY_MAX = 900;

const MH_SOCIAL_LINKEDIN_MAX = 700;

/**
 * Share intent URLs for a journal post (frontend + admin “open share” buttons).
 *
 * @return array{
 *   bluesky: string,
 *   linkedin: string,
 *   facebook: string,
 *   reddit: string,
 *   url: string,
 *   title: string
 * }
 */
function mh_post_share_urls(int $postId = 0, string $prefill = ''): array
{
    $post = get_post($postId > 0 ? $postId : get_the_ID());
    $url = $post instanceof \WP_Post ? (string) get_permalink($post) : '';
    $title = $post instanceof \WP_Post
        ? html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : '';

    $text = trim($prefill);
    if ($text === '' && $title !== '') {
        $text = $title;
    }

    $blueskyText = $text;
    if ($url !== '' && ! str_contains($blueskyText, $url)) {
        $blueskyText = trim($blueskyText === '' ? $url : $blueskyText."\n\n".$url);
    }
    if (function_exists(__NAMESPACE__.'\\mh_bluesky_trim_text')) {
        // Intent compose counts the full string; keep under ~300 graphemes.
        $blueskyText = mh_bluesky_trim_text($blueskyText, 300);
    }

    return [
        'url' => $url,
        'title' => $title,
        'bluesky' => 'https://bsky.app/intent/compose?text='.rawurlencode($blueskyText),
        'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url),
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
        'reddit' => 'https://www.reddit.com/submit?url='.rawurlencode($url).'&title='.rawurlencode($title),
    ];
}

/**
 * Plain excerpt helper for social drafts.
 */
function mh_social_post_excerpt(\WP_Post $post, int $words = 28): string
{
    $excerpt = has_excerpt($post)
        ? get_the_excerpt($post)
        : wp_trim_words(wp_strip_all_tags((string) $post->post_content), $words);

    return html_entity_decode(wp_strip_all_tags((string) $excerpt), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function mh_social_trim(string $text, int $max): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text, 'UTF-8') <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max, 'UTF-8');
    $cut = preg_replace('/\s+\S*$/u', '', $cut) ?? $cut;

    return rtrim($cut, '.,;:!?'." \t").'…';
}

/**
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_generate_draft(int $postId, string $network, bool $useAi = false): array
{
    $network = strtolower(sanitize_key($network));
    $empty = [
        'ok' => false,
        'network' => $network,
        'title' => '',
        'body' => '',
        'text' => '',
        'tips' => [],
        'share_url' => '',
        'message' => 'Unknown network.',
    ];

    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
        $empty['message'] = 'Not a journal post.';

        return $empty;
    }

    $url = (string) get_permalink($post);
    $title = html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = mh_social_post_excerpt($post);

    return match ($network) {
        'bluesky' => mh_social_draft_bluesky($post, $useAi),
        'facebook' => mh_social_draft_facebook($post, $title, $excerpt, $url, $useAi),
        'reddit' => mh_social_draft_reddit($post, $title, $excerpt, $url, $useAi),
        'linkedin' => mh_social_draft_linkedin($post, $title, $excerpt, $url, $useAi),
        'devto' => mh_social_draft_devto($post),
        default => $empty,
    };
}

/**
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_draft_bluesky(\WP_Post $post, bool $useAi): array
{
    $prepared = mh_bluesky_prepare_share((int) $post->ID, $useAi);
    $urls = mh_post_share_urls((int) $post->ID, (string) ($prepared['body'] ?? ''));

    return [
        'ok' => (bool) $prepared['ok'],
        'network' => 'bluesky',
        'title' => '',
        'body' => (string) ($prepared['body'] ?? ''),
        'text' => (string) ($prepared['text'] ?? ''),
        'tips' => [
            'Hard limit is 300 graphemes (characters including emoji).',
            'Put the link on its own line — Bluesky link facets work best that way.',
            'Skip hashtag spam; one clear sentence beats a keyword pile.',
            'App password lives in Appearance → Customize → Bluesky (never your account password).',
        ],
        'share_url' => $urls['bluesky'],
        'message' => (string) ($prepared['message'] ?? ''),
    ];
}

/**
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_draft_facebook(\WP_Post $post, string $title, string $excerpt, string $url, bool $useAi): array
{
    $body = null;
    if ($useAi) {
        $body = mh_social_ai_compose($post, 'facebook', MH_SOCIAL_FACEBOOK_MAX);
    }
    if (! is_string($body) || $body === '') {
        $body = mh_social_trim(
            'New on the journal: '.$title.($excerpt !== '' ? ' — '.$excerpt : '')."\n\n".$url,
            MH_SOCIAL_FACEBOOK_MAX
        );
    } elseif (! str_contains($body, $url)) {
        $body = mh_social_trim($body."\n\n".$url, MH_SOCIAL_FACEBOOK_MAX + 80);
    }

    update_post_meta((int) $post->ID, '_mh_social_facebook_text', sanitize_textarea_field($body));
    $urls = mh_post_share_urls((int) $post->ID);

    return [
        'ok' => true,
        'network' => 'facebook',
        'title' => '',
        'body' => $body,
        'text' => $body,
        'tips' => [
            'Lead with the takeaway, then the link — Facebook truncates long walls.',
            'Native share dialog uses the page URL + Open Graph; paste this text into the composer if you want custom copy.',
            'One clear CTA (“questions welcome”) beats emoji decoration.',
        ],
        'share_url' => $urls['facebook'],
        'message' => $useAi && mh_devto_ai_token() !== '' ? 'Facebook draft ready (AI).' : 'Facebook draft ready.',
    ];
}

/**
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_draft_reddit(\WP_Post $post, string $title, string $excerpt, string $url, bool $useAi): array
{
    $redditTitle = mh_social_trim($title, MH_SOCIAL_REDDIT_TITLE_MAX);
    $body = null;
    if ($useAi) {
        $body = mh_social_ai_compose($post, 'reddit', MH_SOCIAL_REDDIT_BODY_MAX);
    }
    if (! is_string($body) || $body === '') {
        $body = "I wrote this up after shipping it on a real project.\n\n"
            .($excerpt !== '' ? $excerpt."\n\n" : '')
            ."Full post: {$url}\n\n"
            .'Happy to answer questions in the comments.';
        $body = mh_social_trim($body, MH_SOCIAL_REDDIT_BODY_MAX);
    }

    update_post_meta((int) $post->ID, '_mh_social_reddit_title', sanitize_text_field($redditTitle));
    update_post_meta((int) $post->ID, '_mh_social_reddit_text', sanitize_textarea_field($body));

    $share = 'https://www.reddit.com/submit?url='.rawurlencode($url).'&title='.rawurlencode($redditTitle);

    return [
        'ok' => true,
        'network' => 'reddit',
        'title' => $redditTitle,
        'body' => $body,
        'text' => $redditTitle."\n\n".$body,
        'tips' => [
            'Title sells the click — specific > clever. Stay under ~300 characters.',
            'Read the subreddit rules before posting (self-promo limits, flair, link vs text).',
            'For link posts, the URL is the post; keep self-text short. For text posts, put the URL in the body once.',
            'Engage in comments — Reddit rewards replies more than drive-by links.',
        ],
        'share_url' => $share,
        'message' => $useAi && mh_devto_ai_token() !== '' ? 'Reddit draft ready (AI).' : 'Reddit draft ready.',
    ];
}

/**
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_draft_linkedin(\WP_Post $post, string $title, string $excerpt, string $url, bool $useAi): array
{
    $body = null;
    if ($useAi) {
        $body = mh_social_ai_compose($post, 'linkedin', MH_SOCIAL_LINKEDIN_MAX);
    }
    if (! is_string($body) || $body === '') {
        $body = mh_social_trim(
            $title."\n\n".($excerpt !== '' ? $excerpt."\n\n" : '')."I wrote this up here:\n{$url}",
            MH_SOCIAL_LINKEDIN_MAX
        );
    } elseif (! str_contains($body, $url)) {
        $body = mh_social_trim($body."\n\n{$url}", MH_SOCIAL_LINKEDIN_MAX + 80);
    }

    update_post_meta((int) $post->ID, '_mh_social_linkedin_text', sanitize_textarea_field($body));
    $urls = mh_post_share_urls((int) $post->ID);

    return [
        'ok' => true,
        'network' => 'linkedin',
        'title' => '',
        'body' => $body,
        'text' => $body,
        'tips' => [
            'First two lines show before “see more” — put the hook there.',
            'LinkedIn’s share dialog primarily uses the URL; paste custom copy into the composer.',
            'First person, one idea, no fake metrics.',
        ],
        'share_url' => $urls['linkedin'],
        'message' => $useAi && mh_devto_ai_token() !== '' ? 'LinkedIn draft ready (AI).' : 'LinkedIn draft ready.',
    ];
}

/**
 * DEV.to “what tends to rank” checklist + pointers to the export box.
 *
 * @return array{ok: bool, network: string, title: string, body: string, text: string, tips: list<string>, share_url: string, message: string}
 */
function mh_social_draft_devto(\WP_Post $post): array
{
    $prepared = function_exists(__NAMESPACE__.'\\mh_devto_prepare_export')
        ? mh_devto_prepare_export((int) $post->ID, false)
        : ['ok' => false, 'title' => get_the_title($post), 'tags' => [], 'markdown' => ''];

    $tags = is_array($prepared['tags'] ?? null) ? $prepared['tags'] : [];
    $tagLine = $tags !== [] ? implode(', ', array_slice($tags, 0, 4)) : 'wordpress, webdev, php, beginners';
    $title = (string) ($prepared['title'] ?? get_the_title($post));
    $checklist = <<<MD
# {$title}

Suggested tags: {$tagLine}

## DEV.to ranking checklist
1. Specific title with a clear outcome (not “My thoughts on X”).
2. Cover image 1000×420; add alt text on images in the body.
3. Canonical URL pointing back to this journal post (export box sets this).
4. 3–4 tags max; pick ones with active readers (wordpress, webdev, php, beginners…).
5. Open with a short problem → what you built → who it helps. First screen matters.
6. Subheads every few screens; one code sample readers can paste.
7. End with a question so comments start — replies boost distribution.
8. Publish when your timezone’s developers are awake; bump once with a meaningful edit, not spam republish.

Use the **DEV.to** sidebar box to preview Markdown, save a draft, or publish via API.
MD;

    $exportUrl = (string) get_post_meta((int) $post->ID, '_mh_devto_export_url', true);

    return [
        'ok' => true,
        'network' => 'devto',
        'title' => $title,
        'body' => $checklist,
        'text' => $checklist,
        'tips' => [
            'Canonical URL back to matthummel.com avoids duplicate-content confusion.',
            'Series + consistent tags help returning readers more than one viral swing.',
            'API key: Appearance → Customize → DEV.to.',
        ],
        'share_url' => $exportUrl !== '' ? $exportUrl : 'https://dev.to/new',
        'message' => 'DEV.to checklist ready. Use the DEV.to box to export or publish.',
    ];
}

/**
 * Optional OpenAI draft for Facebook / Reddit / LinkedIn. Fails soft.
 */
function mh_social_ai_compose(\WP_Post $post, string $network, int $maxChars): ?string
{
    $token = mh_devto_ai_token();
    if ($token === '') {
        return null;
    }

    $title = get_the_title($post);
    $excerpt = mh_social_post_excerpt($post, 40);
    $voices = [
        'facebook' => 'Friendly Facebook post. First person (I/my). 2–4 short sentences. No hashtag spam.',
        'reddit' => 'Reddit self-post body only (no title). First person. Helpful, not salesy. Invite questions.',
        'linkedin' => 'LinkedIn post. First person. Hook in line 1. Plain technical voice. No fake metrics.',
    ];
    $voice = $voices[$network] ?? 'Short social post. First person.';

    $prompt = "{$voice}\n"
        ."Max {$maxChars} characters. Do NOT wrap in quotes.\n"
        ."You may omit the URL — I may append it.\n\n"
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
            'model' => apply_filters('mh/social_ai_model', apply_filters('mh/devto_ai_model', 'gpt-4o-mini')),
            'temperature' => 0.5,
            'messages' => [
                ['role' => 'system', 'content' => 'You write social posts for a WordPress developer journal.'],
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

    return mh_social_trim(trim($text, " \t\n\r\0\x0B\"'"), $maxChars);
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_social_share',
        __('Social share & drafts', 'sage'),
        __NAMESPACE__.'\\mh_social_share_metabox',
        'post',
        'normal',
        'high'
    );
});

function mh_social_share_metabox(\WP_Post $post): void
{
    wp_nonce_field('mh_social_share', 'mh_social_share_nonce');

    $urls = mh_post_share_urls((int) $post->ID);
    $hasBluesky = function_exists(__NAMESPACE__.'\\mh_bluesky_app_password') && mh_bluesky_app_password() !== '';
    $hasDevto = function_exists(__NAMESPACE__.'\\mh_devto_token') && mh_devto_token() !== '';
    $hasAi = function_exists(__NAMESPACE__.'\\mh_devto_ai_token') && mh_devto_ai_token() !== '';
    $blueskyUrl = (string) get_post_meta($post->ID, '_mh_bluesky_url', true);
    $devtoUrl = (string) get_post_meta($post->ID, '_mh_devto_export_url', true);
    $fbDraft = (string) get_post_meta($post->ID, '_mh_social_facebook_text', true);
    $redditTitle = (string) get_post_meta($post->ID, '_mh_social_reddit_title', true);
    $redditBody = (string) get_post_meta($post->ID, '_mh_social_reddit_text', true);
    $liDraft = (string) get_post_meta($post->ID, '_mh_social_linkedin_text', true);
    $blueskyCustom = (string) get_post_meta($post->ID, '_mh_bluesky_custom_text', true);

    echo '<div class="mh-social-share" id="mh-social-share">';
    echo '<p class="description">'.esc_html__('Draft posts for Bluesky, Facebook, Reddit, LinkedIn, and DEV.to. Generate copy, copy it, open a share dialog, or auto-post where credentials exist.', 'sage').'</p>';

    echo '<p><label><input type="checkbox" name="mh_social_use_ai" id="mh-social-use-ai" value="1" '.checked($hasAi, true, false).'> ';
    echo esc_html__('Use OpenAI when generating (same key as Customize → DEV.to)', 'sage').'</label></p>';

    echo '<div class="mh-social-share__actions" style="display:flex;flex-wrap:wrap;gap:8px;margin:12px 0">';
    foreach ([
        'bluesky' => __('Generate Bluesky', 'sage'),
        'facebook' => __('Generate Facebook', 'sage'),
        'reddit' => __('Generate Reddit', 'sage'),
        'linkedin' => __('Generate LinkedIn', 'sage'),
        'devto' => __('DEV.to checklist', 'sage'),
    ] as $net => $label) {
        printf(
            '<button type="button" class="button mh-social-gen" data-network="%1$s">%2$s</button>',
            esc_attr($net),
            esc_html($label)
        );
    }
    echo '</div>';

    echo '<p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">';
    echo '<strong>'.esc_html__('Auto-share / open', 'sage').'</strong> ';
    printf(
        '<button type="button" class="button button-primary" id="mh-social-post-bluesky" %1$s>%2$s</button>',
        disabled(! $hasBluesky, true, false),
        esc_html__('Post to Bluesky now', 'sage')
    );
    printf(
        '<button type="button" class="button" id="mh-social-post-devto" %1$s>%2$s</button>',
        disabled(! $hasDevto, true, false),
        esc_html__('Publish to DEV.to now', 'sage')
    );
    foreach (['bluesky' => 'Bluesky', 'linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'reddit' => 'Reddit'] as $net => $label) {
        printf(
            '<a class="button" href="%1$s" target="_blank" rel="noopener" data-share-link="%2$s">%3$s</a>',
            esc_url($urls[$net] ?? '#'),
            esc_attr($net),
            esc_html(sprintf(__('Open %s', 'sage'), $label))
        );
    }
    echo '</p>';

    if ($blueskyUrl !== '') {
        printf(
            '<p><strong>%1$s</strong> <a href="%2$s" target="_blank" rel="noopener">%3$s</a></p>',
            esc_html__('On Bluesky:', 'sage'),
            esc_url($blueskyUrl),
            esc_html__('View post', 'sage')
        );
    }
    if ($devtoUrl !== '') {
        printf(
            '<p><strong>%1$s</strong> <a href="%2$s" target="_blank" rel="noopener">%3$s</a></p>',
            esc_html__('On DEV.to:', 'sage'),
            esc_url($devtoUrl),
            esc_html__('View article', 'sage')
        );
    }
    if (! $hasBluesky) {
        echo '<p class="description">'.esc_html__('Bluesky auto-post needs an app password: Appearance → Customize → Bluesky.', 'sage').'</p>';
    }
    if (! $hasDevto) {
        echo '<p class="description">'.esc_html__('DEV.to publish needs an API key: Appearance → Customize → DEV.to.', 'sage').'</p>';
    }

    echo '<p><label for="mh-bluesky-custom"><strong>'.esc_html__('Bluesky custom text (optional)', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-bluesky-custom" name="mh_bluesky_custom_text" class="widefat" rows="3" maxlength="320" placeholder="';
    echo esc_attr__('Paste a ChatGPT summary — URL is added on post.', 'sage');
    echo '">'.esc_textarea($blueskyCustom).'</textarea>';

    echo '<p><label for="mh-social-facebook"><strong>'.esc_html__('Facebook draft', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-social-facebook" name="mh_social_facebook_text" class="widefat" rows="4">'.esc_textarea($fbDraft).'</textarea>';

    echo '<p><label for="mh-social-reddit-title"><strong>'.esc_html__('Reddit title', 'sage').'</strong></label></p>';
    echo '<input type="text" id="mh-social-reddit-title" name="mh_social_reddit_title" class="widefat" value="'.esc_attr($redditTitle).'" maxlength="300">';
    echo '<p><label for="mh-social-reddit-body"><strong>'.esc_html__('Reddit body', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-social-reddit-body" name="mh_social_reddit_text" class="widefat" rows="5">'.esc_textarea($redditBody).'</textarea>';

    echo '<p><label for="mh-social-linkedin"><strong>'.esc_html__('LinkedIn draft', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-social-linkedin" name="mh_social_linkedin_text" class="widefat" rows="4">'.esc_textarea($liDraft).'</textarea>';

    echo '<p style="display:flex;flex-wrap:wrap;gap:8px">';
    echo '<button type="button" class="button" id="mh-social-copy">'.esc_html__('Copy active draft', 'sage').'</button>';
    echo '</p>';

    echo '<p id="mh-social-status" class="description" aria-live="polite"></p>';
    echo '<label for="mh-social-preview"><strong>'.esc_html__('Preview / tips', 'sage').'</strong></label>';
    echo '<textarea id="mh-social-preview" class="widefat" rows="10" readonly style="font-family:ui-monospace,monospace;font-size:12px"></textarea>';
    echo '</div>';
}

add_action('save_post_post', function (int $postId): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! isset($_POST['mh_social_share_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mh_social_share_nonce'])), 'mh_social_share')) {
        return;
    }
    if (! current_user_can('edit_post', $postId)) {
        return;
    }

    $map = [
        'mh_bluesky_custom_text' => '_mh_bluesky_custom_text',
        'mh_social_facebook_text' => '_mh_social_facebook_text',
        'mh_social_reddit_title' => '_mh_social_reddit_title',
        'mh_social_reddit_text' => '_mh_social_reddit_text',
        'mh_social_linkedin_text' => '_mh_social_linkedin_text',
    ];
    foreach ($map as $field => $meta) {
        if (! isset($_POST[$field])) {
            continue;
        }
        $raw = wp_unslash($_POST[$field]);
        $value = $field === 'mh_social_reddit_title'
            ? sanitize_text_field($raw)
            : sanitize_textarea_field($raw);
        if ($value === '') {
            delete_post_meta($postId, $meta);
        } else {
            update_post_meta($postId, $meta, $value);
        }
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

    wp_register_script('mh-social-share', '', ['wp-util'], '1', true);
    wp_enqueue_script('mh-social-share');
    wp_add_inline_script('mh-social-share', <<<'JS'
(function () {
  const root = document.getElementById('mh-social-share')
  const status = document.getElementById('mh-social-status')
  const preview = document.getElementById('mh-social-preview')
  const nonce = document.getElementById('mh_social_share_nonce')
  if (!root || !status || !preview || !nonce || typeof ajaxurl === 'undefined') return

  let activeNetwork = 'bluesky'
  let lastPayload = null

  function postId () {
    const el = document.getElementById('post_ID')
    return el ? el.value : '0'
  }

  function useAi () {
    const el = document.getElementById('mh-social-use-ai')
    return !!(el && el.checked)
  }

  function setStatus (msg, isError) {
    status.textContent = msg || ''
    status.style.color = isError ? '#b32d2e' : ''
  }

  function fillFields (data) {
    if (!data) return
    if (data.network === 'facebook' && data.body != null) {
      const el = document.getElementById('mh-social-facebook')
      if (el) el.value = data.body
    }
    if (data.network === 'reddit') {
      const t = document.getElementById('mh-social-reddit-title')
      const b = document.getElementById('mh-social-reddit-body')
      if (t && data.title != null) t.value = data.title
      if (b && data.body != null) b.value = data.body
    }
    if (data.network === 'linkedin' && data.body != null) {
      const el = document.getElementById('mh-social-linkedin')
      if (el) el.value = data.body
    }
    if (data.network === 'bluesky' && data.body != null) {
      const el = document.getElementById('mh-bluesky-custom')
      if (el && !el.value) el.value = data.body
    }
    const tips = Array.isArray(data.tips) ? data.tips.map(function (t) { return '• ' + t }).join('\n') : ''
    preview.value = (data.text || data.body || '') + (tips ? '\n\n— Tips —\n' + tips : '')
    if (data.share_url) {
      const link = root.querySelector('[data-share-link="' + data.network + '"]')
      if (link) link.href = data.share_url
    }
  }

  async function call (action, extra) {
    setStatus('Working…')
    const body = new FormData()
    body.append('action', action)
    body.append('nonce', nonce.value)
    body.append('post_id', postId())
    body.append('use_ai', useAi() ? '1' : '0')
    const custom = document.getElementById('mh-bluesky-custom')
    if (custom) body.append('custom_text', custom.value)
    if (extra) {
      Object.keys(extra).forEach(function (k) { body.append(k, extra[k]) })
    }
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      if (!data || !data.success) {
        setStatus((data && data.data && data.data.message) || 'Request failed', true)
        if (data && data.data) fillFields(data.data)
        return null
      }
      return data.data
    } catch (err) {
      setStatus('Network error', true)
      return null
    }
  }

  root.querySelectorAll('.mh-social-gen').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      activeNetwork = btn.getAttribute('data-network') || 'bluesky'
      const data = await call('mh_social_generate', { network: activeNetwork })
      if (!data) return
      lastPayload = data
      fillFields(data)
      setStatus(data.message || 'Draft ready')
    })
  })

  document.getElementById('mh-social-copy')?.addEventListener('click', async function () {
    const text = preview.value || ''
    if (!text) {
      setStatus('Nothing to copy — generate a draft first.', true)
      return
    }
    try {
      await navigator.clipboard.writeText(text)
      setStatus('Copied to clipboard')
    } catch (err) {
      preview.select()
      setStatus('Select and copy manually (clipboard blocked)', true)
    }
  })

  document.getElementById('mh-social-post-bluesky')?.addEventListener('click', async function () {
    if (!window.confirm('Post this article to Bluesky now?')) return
    const data = await call('mh_social_bluesky_share')
    if (!data) return
    preview.value = data.text || ''
    setStatus((data.message || 'Posted') + (data.url ? ' — ' + data.url : ''))
  })

  document.getElementById('mh-social-post-devto')?.addEventListener('click', async function () {
    if (!window.confirm('Publish this article to DEV.to now?')) return
    // Reuse DEV.to AJAX if its nonce exists; otherwise our endpoint.
    const devNonce = document.getElementById('mh_devto_export_nonce')
    if (devNonce) {
      setStatus('Working…')
      const body = new FormData()
      body.append('action', 'mh_devto_publish')
      body.append('nonce', devNonce.value)
      body.append('post_id', postId())
      body.append('use_ai', useAi() ? '1' : '0')
      try {
        const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
        const data = await res.json()
        if (!data || !data.success) {
          setStatus((data && data.data && data.data.message) || 'DEV.to publish failed', true)
          return
        }
        setStatus((data.data.message || 'Published') + (data.data.url ? ' — ' + data.data.url : ''))
        if (data.data.markdown) preview.value = data.data.markdown
      } catch (err) {
        setStatus('Network error', true)
      }
      return
    }
    const data = await call('mh_social_devto_publish')
    if (!data) return
    setStatus((data.message || 'Published') + (data.url ? ' — ' + data.url : ''))
  })
})()
JS);
});

function mh_social_ajax_guard(): int
{
    if (! current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }
    check_ajax_referer('mh_social_share', 'nonce');
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

add_action('wp_ajax_mh_social_generate', function (): void {
    $postId = mh_social_ajax_guard();
    $network = sanitize_key((string) ($_POST['network'] ?? ''));
    $useAi = ! empty($_POST['use_ai']);
    $draft = mh_social_generate_draft($postId, $network, $useAi);
    if (! $draft['ok']) {
        wp_send_json_error($draft);
    }
    wp_send_json_success($draft);
});

add_action('wp_ajax_mh_social_devto_publish', function (): void {
    $postId = mh_social_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    if (! function_exists(__NAMESPACE__.'\\mh_devto_export_post')) {
        wp_send_json_error(['message' => 'DEV.to export is not available.']);
    }
    $result = mh_devto_export_post($postId, true, $useAi);
    if (! ($result['ok'] ?? false)) {
        wp_send_json_error(['message' => (string) ($result['message'] ?? 'Publish failed')]);
    }
    wp_send_json_success($result);
});

add_action('wp_ajax_mh_social_bluesky_share', function (): void {
    $postId = mh_social_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    if (! function_exists(__NAMESPACE__.'\\mh_bluesky_share_post')) {
        wp_send_json_error(['message' => 'Bluesky share is not available.']);
    }
    $result = mh_bluesky_share_post($postId, $useAi, true);
    if (! ($result['ok'] ?? false)) {
        wp_send_json_error([
            'message' => (string) ($result['message'] ?? 'Share failed'),
            'text' => (string) ($result['text'] ?? ''),
        ]);
    }
    wp_send_json_success($result);
});

/**
 * Render frontend share button group markup (Blade calls this or builds URLs itself).
 *
 * @return array{bluesky: string, linkedin: string, facebook: string, reddit: string, url: string, title: string}
 */
function mh_post_share_context(int $postId = 0): array
{
    return mh_post_share_urls($postId);
}
