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

/* ───────────────────────── Facebook Page posting ───────────────────────── */

/** Facebook Page ID from wp-config or the Customizer. */
function mh_facebook_page_id(): string
{
    if (defined('MH_FACEBOOK_PAGE_ID') && (string) MH_FACEBOOK_PAGE_ID !== '') {
        return (string) MH_FACEBOOK_PAGE_ID;
    }

    return trim((string) get_theme_mod('mh_facebook_page_id', ''));
}

/** Facebook Page access token from wp-config or the Customizer. Never printed. */
function mh_facebook_page_token(): string
{
    if (defined('MH_FACEBOOK_PAGE_TOKEN') && (string) MH_FACEBOOK_PAGE_TOKEN !== '') {
        return (string) MH_FACEBOOK_PAGE_TOKEN;
    }

    return trim((string) get_theme_mod('mh_facebook_page_token', ''));
}

/** Whether the editor can post straight to the Page. */
function mh_facebook_can_post(): bool
{
    return mh_facebook_page_id() !== '' && mh_facebook_page_token() !== '';
}

/**
 * Publish a link post on the Facebook Page through the Graph API.
 *
 * @return array{ok: bool, message: string, id: string, url: string}
 */
function mh_facebook_post_to_page(int $postId, string $message): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
        return ['ok' => false, 'message' => 'Publish the post first, then share it.', 'id' => '', 'url' => ''];
    }
    if (! mh_facebook_can_post()) {
        return ['ok' => false, 'message' => 'Add the Page ID and token under Appearance → Customize → Facebook Page.', 'id' => '', 'url' => ''];
    }

    $message = trim($message);
    if ($message === '') {
        return ['ok' => false, 'message' => 'Write or generate the Facebook text first.', 'id' => '', 'url' => ''];
    }

    $res = wp_remote_post('https://graph.facebook.com/v21.0/'.rawurlencode(mh_facebook_page_id()).'/feed', [
        'timeout' => 20,
        'body' => [
            'message' => $message,
            'link' => (string) get_permalink($post),
            'access_token' => mh_facebook_page_token(),
        ],
    ]);
    if (is_wp_error($res)) {
        return ['ok' => false, 'message' => 'Facebook request failed: '.$res->get_error_message(), 'id' => '', 'url' => ''];
    }

    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    $id = is_array($body) ? (string) ($body['id'] ?? '') : '';
    if (wp_remote_retrieve_response_code($res) >= 300 || $id === '') {
        $detail = is_array($body) ? (string) ($body['error']['message'] ?? '') : '';

        return ['ok' => false, 'message' => 'Facebook said no'.($detail !== '' ? ': '.$detail : '.'), 'id' => '', 'url' => ''];
    }

    $url = 'https://www.facebook.com/'.rawurlencode($id);
    update_post_meta($postId, '_mh_social_facebook_text', sanitize_textarea_field($message));
    update_post_meta($postId, '_mh_facebook_post_id', sanitize_text_field($id));
    update_post_meta($postId, '_mh_facebook_url', esc_url_raw($url));
    update_post_meta($postId, '_mh_facebook_shared_at', (string) time());

    return ['ok' => true, 'message' => 'Posted to the Facebook Page.', 'id' => $id, 'url' => $url];
}

/* ───────────────────────── Editor box ───────────────────────── */

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

/** Small action icons for the social box (currentColor, 16px). */
function mh_social_icon(string $name): string
{
    $paths = [
        'sparkles' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/><path d="M5 2l.6 1.6L7.2 4.2l-1.6.6L5 6.4l-.6-1.6L2.8 4.2l1.6-.6z"/>',
        'send' => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'open' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
    ];
    $d = $paths[$name] ?? $paths['open'];

    return '<svg class="mh-social__ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$d.'</svg>';
}

/**
 * Tags the social box markup helpers may emit (buttons with inline SVG).
 *
 * @return array<string, array<string, bool>>
 */
function mh_social_allowed_html(): array
{
    $svg = ['class' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'focusable' => true, 'd' => true, 'x' => true, 'y' => true, 'rx' => true];

    return [
        'svg' => $svg,
        'path' => $svg,
        'rect' => $svg,
        'span' => ['class' => true],
        'button' => ['type' => true, 'class' => true, 'disabled' => true, 'title' => true, 'data-mh-act' => true, 'data-network' => true, 'data-url' => true],
    ];
}

/**
 * One icon button for a social card.
 *
 * @param  array<string, string>  $data  Extra data attributes (without the data- prefix).
 */
function mh_social_button(string $icon, string $label, string $network, string $action, bool $primary = false, bool $disabled = false, array $data = []): string
{
    $attrs = '';
    foreach ($data as $key => $value) {
        $attrs .= ' data-'.esc_attr($key).'="'.esc_attr($value).'"';
    }

    return sprintf(
        '<button type="button" class="button%1$s mh-social__btn" data-mh-act="%2$s" data-network="%3$s"%4$s%5$s title="%6$s">%7$s<span>%6$s</span></button>',
        $primary ? ' button-primary' : '',
        esc_attr($action),
        esc_attr($network),
        $disabled ? ' disabled' : '',
        $attrs,
        esc_attr($label),
        mh_social_icon($icon)
    );
}

function mh_social_share_metabox(\WP_Post $post): void
{
    wp_nonce_field('mh_social_share', 'mh_social_share_nonce');

    $urls = mh_post_share_urls((int) $post->ID);
    $hasBluesky = function_exists(__NAMESPACE__.'\\mh_bluesky_app_password') && mh_bluesky_app_password() !== '';
    $hasFacebook = mh_facebook_can_post();
    $hasDevto = function_exists(__NAMESPACE__.'\\mh_devto_token') && mh_devto_token() !== '';
    $hasAi = function_exists(__NAMESPACE__.'\\mh_devto_ai_token') && mh_devto_ai_token() !== '';
    $published = $post->post_status === 'publish';
    $blueskyUrl = (string) get_post_meta($post->ID, '_mh_bluesky_url', true);
    $facebookUrl = (string) get_post_meta($post->ID, '_mh_facebook_url', true);
    $devtoUrl = (string) get_post_meta($post->ID, '_mh_devto_export_url', true);
    $fbDraft = (string) get_post_meta($post->ID, '_mh_social_facebook_text', true);
    $redditTitle = (string) get_post_meta($post->ID, '_mh_social_reddit_title', true);
    $redditBody = (string) get_post_meta($post->ID, '_mh_social_reddit_text', true);
    $liDraft = (string) get_post_meta($post->ID, '_mh_social_linkedin_text', true);
    $blueskyCustom = (string) get_post_meta($post->ID, '_mh_bluesky_custom_text', true);

    $cards = [
        [
            'network' => 'bluesky',
            'label' => 'Bluesky',
            'max' => 300,
            'connected' => $hasBluesky,
            'connect_hint' => __('App password: Appearance → Customize → Bluesky', 'sage'),
            'fields' => [['textarea', 'mh-bluesky-custom', 'mh_bluesky_custom_text', $blueskyCustom, 3, __('Custom text (optional). The link is added on post.', 'sage')]],
            'post_label' => __('Post to Bluesky', 'sage'),
            'post_action' => 'post-bluesky',
            'can_post' => $hasBluesky && $published,
            'open_url' => $urls['bluesky'],
            'done_url' => $blueskyUrl,
            'done_label' => __('View on Bluesky', 'sage'),
        ],
        [
            'network' => 'facebook',
            'label' => 'Facebook',
            'max' => MH_SOCIAL_FACEBOOK_MAX,
            'connected' => $hasFacebook,
            'connect_hint' => __('Page ID + token: Appearance → Customize → Facebook Page. Until then the share dialog opens.', 'sage'),
            'fields' => [['textarea', 'mh-social-facebook', 'mh_social_facebook_text', $fbDraft, 4, '']],
            'post_label' => __('Post to Page', 'sage'),
            'post_action' => 'post-facebook',
            'can_post' => $hasFacebook && $published,
            'open_url' => $urls['facebook'],
            'done_url' => $facebookUrl,
            'done_label' => __('View on Facebook', 'sage'),
        ],
        [
            'network' => 'linkedin',
            'label' => 'LinkedIn',
            'max' => MH_SOCIAL_LINKEDIN_MAX,
            'connected' => null,
            'connect_hint' => '',
            'fields' => [['textarea', 'mh-social-linkedin', 'mh_social_linkedin_text', $liDraft, 4, '']],
            'post_label' => '',
            'post_action' => '',
            'can_post' => false,
            'open_url' => $urls['linkedin'],
            'done_url' => '',
            'done_label' => '',
        ],
        [
            'network' => 'reddit',
            'label' => 'Reddit',
            'max' => MH_SOCIAL_REDDIT_BODY_MAX,
            'connected' => null,
            'connect_hint' => '',
            'fields' => [
                ['text', 'mh-social-reddit-title', 'mh_social_reddit_title', $redditTitle, 0, __('Title', 'sage')],
                ['textarea', 'mh-social-reddit-body', 'mh_social_reddit_text', $redditBody, 4, ''],
            ],
            'post_label' => '',
            'post_action' => '',
            'can_post' => false,
            'open_url' => $urls['reddit'],
            'done_url' => '',
            'done_label' => '',
        ],
        [
            'network' => 'devto',
            'label' => 'DEV.to',
            'max' => 0,
            'connected' => $hasDevto,
            'connect_hint' => __('API key: Appearance → Customize → DEV.to', 'sage'),
            'fields' => [],
            'post_label' => __('Publish to DEV.to', 'sage'),
            'post_action' => 'post-devto',
            'can_post' => $hasDevto && $published,
            'open_url' => '',
            'done_url' => $devtoUrl,
            'done_label' => __('View article', 'sage'),
        ],
    ];

    echo '<div class="mh-social" id="mh-social-share" data-permalink="'.esc_url((string) get_permalink($post)).'" data-title="'.esc_attr(html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')).'">';
    echo '<div class="mh-social__top">';
    echo '<label class="mh-social__ai"><input type="checkbox" name="mh_social_use_ai" id="mh-social-use-ai" value="1" '.checked($hasAi, true, false).'> ';
    echo wp_kses(mh_social_icon('sparkles'), mh_social_allowed_html()).' '.esc_html__('Use OpenAI when generating', 'sage').'</label>';
    if (! $published) {
        echo '<span class="mh-social__note">'.esc_html__('Posting unlocks once the post is published.', 'sage').'</span>';
    }
    echo '</div>';

    echo '<div class="mh-social__grid">';
    foreach ($cards as $card) {
        $net = $card['network'];
        echo '<section class="mh-social__card mh-social__card--'.esc_attr($net).'" data-card="'.esc_attr($net).'" aria-labelledby="mh-social-h-'.esc_attr($net).'">';
        echo '<header class="mh-social__head">';
        echo '<h3 id="mh-social-h-'.esc_attr($net).'" class="mh-social__name">'.wp_kses(mh_svg_icon($net, 16), mh_social_allowed_html()).' '.esc_html($card['label']).'</h3>';
        if ($card['connected'] === true) {
            echo '<span class="mh-social__pill is-on">'.wp_kses(mh_social_icon('check'), mh_social_allowed_html()).' '.esc_html__('Connected', 'sage').'</span>';
        } elseif ($card['connected'] === false) {
            echo '<span class="mh-social__pill" title="'.esc_attr($card['connect_hint']).'">'.esc_html__('Not connected', 'sage').'</span>';
        }
        echo '</header>';

        foreach ($card['fields'] as [$type, $id, $name, $value, $rows, $placeholder]) {
            if ($type === 'text') {
                echo '<input type="text" id="'.esc_attr($id).'" name="'.esc_attr($name).'" class="widefat mh-social__field" value="'.esc_attr($value).'" maxlength="300" placeholder="'.esc_attr($placeholder).'" data-mh-text="'.esc_attr($net).'">';
            } else {
                echo '<textarea id="'.esc_attr($id).'" name="'.esc_attr($name).'" class="widefat mh-social__field" rows="'.(int) $rows.'" placeholder="'.esc_attr($placeholder).'" data-mh-text="'.esc_attr($net).'" data-max="'.(int) $card['max'].'">'.esc_textarea($value).'</textarea>';
            }
        }
        if ($card['max'] > 0 && $card['fields'] !== []) {
            echo '<div class="mh-social__meter" data-meter-for="'.esc_attr($net).'" data-max="'.(int) $card['max'].'"><span class="mh-social__meter-track"><span class="mh-social__meter-fill"></span></span><span class="mh-social__meter-num"></span></div>';
        }

        echo '<div class="mh-social__actions">';
        echo wp_kses(mh_social_button('sparkles', $net === 'devto' ? __('Checklist', 'sage') : __('Generate', 'sage'), $net, 'generate'), mh_social_allowed_html());
        if ($card['post_action'] !== '') {
            echo wp_kses(mh_social_button('send', $card['post_label'], $net, $card['post_action'], true, ! $card['can_post']), mh_social_allowed_html());
        }
        if ($card['open_url'] !== '') {
            echo wp_kses(mh_social_button('open', __('Share dialog', 'sage'), $net, 'open', false, false, ['url' => $card['open_url']]), mh_social_allowed_html());
        }
        if ($card['fields'] !== []) {
            echo wp_kses(mh_social_button('copy', __('Copy', 'sage'), $net, 'copy'), mh_social_allowed_html());
        }
        echo '</div>';

        echo '<p class="mh-social__done" data-done-for="'.esc_attr($net).'"'.($card['done_url'] === '' ? ' hidden' : '').'>';
        echo wp_kses(mh_social_icon('check'), mh_social_allowed_html()).' <a href="'.esc_url($card['done_url'] !== '' ? $card['done_url'] : '#').'" target="_blank" rel="noopener">'.esc_html($card['done_label'] !== '' ? $card['done_label'] : __('View', 'sage')).'</a>';
        echo '</p>';
        if ($card['connected'] === false && $card['connect_hint'] !== '') {
            echo '<p class="description mh-social__hint">'.esc_html($card['connect_hint']).'</p>';
        }
        echo '</section>';
    }
    echo '</div>';

    echo '<p id="mh-social-status" class="mh-social__status" role="status" aria-live="polite"></p>';
    echo '<details class="mh-social__tips"><summary>'.esc_html__('Preview and tips from the last generate', 'sage').'</summary>';
    echo '<textarea id="mh-social-preview" class="widefat" rows="8" readonly></textarea></details>';
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
    wp_add_inline_style('wp-admin', <<<'CSS'
.mh-social__top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px 16px;margin:0 0 12px}
.mh-social__ai{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.mh-social__note{font-size:12px;color:#646970}
.mh-social__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:12px}
.mh-social__card{padding:12px 14px 10px;border:1px solid #dcdcde;border-radius:6px;background:#fff;border-top:3px solid #8c8f94}
.mh-social__card--bluesky{border-top-color:#1185fe}.mh-social__card--facebook{border-top-color:#1877f2}.mh-social__card--linkedin{border-top-color:#0a66c2}.mh-social__card--reddit{border-top-color:#ff4500}.mh-social__card--devto{border-top-color:#0a0a0a}
.mh-social__head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:0 0 8px}
.mh-social__name{display:inline-flex;align-items:center;gap:6px;margin:0;font-size:13px}
.mh-social__name svg{width:16px;height:16px}
.mh-social__pill{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;background:#f0f0f1;color:#50575e;border:1px solid #dcdcde}
.mh-social__pill.is-on{background:#edfaef;border-color:#b8e6bf;color:#1d6b2f}
.mh-social__field{margin:0 0 6px;font-size:13px}
.mh-social__meter{display:flex;align-items:center;gap:8px;margin:0 0 8px;font-size:11px;color:#646970;font-variant-numeric:tabular-nums}
.mh-social__meter-track{flex:1;height:4px;border-radius:2px;background:#e0e0e0;overflow:hidden}
.mh-social__meter-fill{display:block;height:100%;width:0;background:#2271b1;transition:width .15s}
.mh-social__meter.is-over .mh-social__meter-fill{background:#d63638}.mh-social__meter.is-over .mh-social__meter-num{color:#d63638;font-weight:600}
.mh-social__actions{display:flex;flex-wrap:wrap;gap:6px}
.mh-social__btn{display:inline-flex!important;align-items:center;gap:6px;padding:0 10px!important;min-height:30px}
.mh-social__btn .mh-social__ico{flex:none}
.mh-social__btn.is-busy{opacity:.6;pointer-events:none}
.mh-social__done{display:flex;align-items:center;gap:6px;margin:8px 0 0;font-size:12px;color:#1d6b2f}
.mh-social__hint{margin:8px 0 0!important}
.mh-social__status{margin:12px 0 0;min-height:1.4em;font-size:13px}
.mh-social__status.is-error{color:#b32d2e}.mh-social__status.is-ok{color:#1d6b2f}
.mh-social__tips{margin-top:10px}.mh-social__tips summary{cursor:pointer;color:#2271b1}
.mh-social__tips textarea{margin-top:8px;font:12px/1.5 ui-monospace,Menlo,monospace}
CSS);
    wp_register_script('mh-social-share', '', ['wp-util'], '2', true);
    wp_enqueue_script('mh-social-share');
    wp_add_inline_script('mh-social-share', <<<'JS'
(function () {
  const root = document.getElementById('mh-social-share')
  const status = document.getElementById('mh-social-status')
  const preview = document.getElementById('mh-social-preview')
  const nonce = document.getElementById('mh_social_share_nonce')
  if (!root || !status || !preview || !nonce || typeof ajaxurl === 'undefined') return

  const permalink = root.getAttribute('data-permalink') || ''
  const postTitle = root.getAttribute('data-title') || ''

  function postId () { const el = document.getElementById('post_ID'); return el ? el.value : '0' }
  function useAi () { const el = document.getElementById('mh-social-use-ai'); return !!(el && el.checked) }
  function field (network, which) {
    const sel = which === 'title' ? 'input[data-mh-text="' + network + '"]' : 'textarea[data-mh-text="' + network + '"]'
    return root.querySelector(sel)
  }
  function textOf (network) {
    const t = field(network, 'title'); const b = field(network, 'body')
    return [t && t.value, b && b.value].filter(Boolean).join('\n\n').trim()
  }
  function setStatus (msg, kind) {
    status.textContent = msg || ''
    status.className = 'mh-social__status' + (kind ? ' is-' + kind : '')
  }
  function setBusy (btn, on) { if (btn) btn.classList.toggle('is-busy', !!on) }
  function markDone (network, url) {
    const p = root.querySelector('[data-done-for="' + network + '"]')
    if (!p) return
    const a = p.querySelector('a')
    if (a && url) a.href = url
    p.hidden = !url
  }
  function meter (network) {
    const m = root.querySelector('[data-meter-for="' + network + '"]')
    const b = field(network, 'body')
    if (!m || !b) return
    const max = parseInt(m.getAttribute('data-max'), 10) || 0
    const len = Array.from(b.value).length
    const fill = m.querySelector('.mh-social__meter-fill')
    const num = m.querySelector('.mh-social__meter-num')
    if (fill) fill.style.width = Math.min(100, (len / Math.max(max, 1)) * 100) + '%'
    if (num) num.textContent = len + ' / ' + max
    m.classList.toggle('is-over', max > 0 && len > max)
  }
  function fill (data) {
    if (!data || !data.network) return
    const n = data.network
    const t = field(n, 'title'); const b = field(n, 'body')
    if (t && data.title != null) t.value = data.title
    if (b && data.body != null && (n !== 'bluesky' || !b.value)) b.value = data.body
    meter(n)
    const tips = Array.isArray(data.tips) ? data.tips.map(function (x) { return '• ' + x }).join('\n') : ''
    preview.value = (data.text || data.body || '') + (tips ? '\n\n— Tips —\n' + tips : '')
  }
  function shareUrl (network) {
    const text = textOf(network)
    const u = encodeURIComponent(permalink)
    if (network === 'facebook') return 'https://www.facebook.com/sharer/sharer.php?u=' + u + (text ? '&quote=' + encodeURIComponent(text) : '')
    if (network === 'linkedin') return 'https://www.linkedin.com/sharing/share-offsite/?url=' + u
    if (network === 'reddit') {
      const t = field('reddit', 'title')
      return 'https://www.reddit.com/submit?url=' + u + '&title=' + encodeURIComponent((t && t.value) || postTitle)
    }
    if (network === 'bluesky') {
      let body = text || postTitle
      if (body.indexOf(permalink) === -1) body = (body ? body + '\n\n' : '') + permalink
      return 'https://bsky.app/intent/compose?text=' + encodeURIComponent(body)
    }
    return ''
  }
  async function call (action, extra) {
    const body = new FormData()
    body.append('action', action)
    body.append('nonce', nonce.value)
    body.append('post_id', postId())
    body.append('use_ai', useAi() ? '1' : '0')
    const custom = field('bluesky', 'body')
    if (custom) body.append('custom_text', custom.value)
    Object.keys(extra || {}).forEach(function (k) { body.append(k, extra[k]) })
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      if (!data || !data.success) {
        const d = (data && data.data) || {}
        if (d.network) fill(d)
        return { ok: false, message: d.message || 'Request failed', data: d }
      }
      return { ok: true, data: data.data }
    } catch (err) {
      return { ok: false, message: 'Network error' }
    }
  }
  async function devtoPublish () {
    const devNonce = document.getElementById('mh_devto_export_nonce')
    if (!devNonce) return call('mh_social_devto_publish')
    const body = new FormData()
    body.append('action', 'mh_devto_publish'); body.append('nonce', devNonce.value)
    body.append('post_id', postId()); body.append('use_ai', useAi() ? '1' : '0')
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      return data && data.success ? { ok: true, data: data.data } : { ok: false, message: (data && data.data && data.data.message) || 'DEV.to publish failed' }
    } catch (err) { return { ok: false, message: 'Network error' } }
  }

  root.addEventListener('input', function (e) {
    const n = e.target && e.target.getAttribute && e.target.getAttribute('data-mh-text')
    if (n) meter(n)
  })
  root.querySelectorAll('[data-meter-for]').forEach(function (m) { meter(m.getAttribute('data-meter-for')) })

  root.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-mh-act]')
    if (!btn || btn.disabled) return
    const act = btn.getAttribute('data-mh-act')
    const network = btn.getAttribute('data-network')

    if (act === 'open') {
      window.open(shareUrl(network) || btn.getAttribute('data-url') || '#', '_blank', 'noopener,width=640,height=560')
      return
    }
    if (act === 'copy') {
      const text = textOf(network)
      if (!text) { setStatus('Nothing to copy yet — generate or type a draft first.', 'error'); return }
      try { await navigator.clipboard.writeText(text); setStatus('Copied the ' + network + ' draft.', 'ok') } catch (err) { setStatus('Clipboard blocked — select and copy manually.', 'error') }
      return
    }

    setBusy(btn, true); setStatus('Working…')
    let res
    if (act === 'generate') {
      res = await call('mh_social_generate', { network })
      if (res.ok) { fill(res.data); setStatus(res.data.message || 'Draft ready.', 'ok') }
    } else if (act === 'post-bluesky') {
      if (!window.confirm('Post this to Bluesky now?')) { setBusy(btn, false); setStatus(''); return }
      res = await call('mh_social_bluesky_share')
      if (res.ok) { preview.value = res.data.text || ''; markDone('bluesky', res.data.url); setStatus(res.data.message || 'Posted to Bluesky.', 'ok') }
    } else if (act === 'post-facebook') {
      if (!window.confirm('Post this to the Facebook Page now?')) { setBusy(btn, false); setStatus(''); return }
      res = await call('mh_social_facebook_share', { message: textOf('facebook') })
      if (res.ok) { markDone('facebook', res.data.url); setStatus(res.data.message || 'Posted to Facebook.', 'ok') }
    } else if (act === 'post-devto') {
      if (!window.confirm('Publish this article to DEV.to now?')) { setBusy(btn, false); setStatus(''); return }
      res = await devtoPublish()
      if (res.ok) { markDone('devto', res.data.url); if (res.data.markdown) preview.value = res.data.markdown; setStatus(res.data.message || 'Published to DEV.to.', 'ok') }
    }
    if (res && !res.ok) setStatus(res.message, 'error')
    setBusy(btn, false)
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

add_action('wp_ajax_mh_social_facebook_share', function (): void {
    $postId = mh_social_ajax_guard();
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- mh_social_ajax_guard() ran check_ajax_referer() above.
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $result = mh_facebook_post_to_page($postId, $message);
    if (! $result['ok']) {
        wp_send_json_error(['message' => $result['message']]);
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
