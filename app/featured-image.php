<?php

/**
 * Generate / replace a journal post featured image via OpenAI Images.
 */

namespace App;

/**
 * Default image model (filterable).
 */
function mh_featured_image_model(): string
{
    return (string) apply_filters('mh/featured_image_model', 'dall-e-3');
}

/**
 * Build a brand-safe prompt from the post (and optional editor override).
 */
function mh_featured_image_prompt(\WP_Post $post, string $custom = ''): string
{
    $custom = trim($custom);
    if ($custom !== '') {
        return $custom;
    }

    $title = html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = function_exists(__NAMESPACE__.'\\mh_social_post_excerpt')
        ? mh_social_post_excerpt($post, 24)
        : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 24);

    $cats = get_the_category($post->ID);
    $cat = $cats ? (string) $cats[0]->name : 'WordPress';

    $base = "Editorial featured image for a developer journal article titled \"{$title}\". "
        ."Category: {$cat}. Topic hint: {$excerpt}. "
        .'Documentary photography or clean technical still life, navy and blue-gray palette, '
        .'natural light, no text, no logos, no watermarks, no people faces, no purple neon glow.';

    return (string) apply_filters('mh/featured_image_prompt', $base, $post);
}

/**
 * Call OpenAI Images and return a temporary image URL.
 *
 * @return array{ok: bool, url?: string, message: string}
 */
function mh_featured_image_openai_url(string $prompt): array
{
    $token = function_exists(__NAMESPACE__.'\\mh_devto_ai_token') ? mh_devto_ai_token() : '';
    if ($token === '') {
        return [
            'ok' => false,
            'message' => __('Add an OpenAI key in Appearance → Customize → DEV.to (same key as social drafts).', 'sage'),
        ];
    }

    $model = mh_featured_image_model();
    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'n' => 1,
        'size' => (string) apply_filters('mh/featured_image_size', '1792x1024'),
        'quality' => (string) apply_filters('mh/featured_image_quality', 'standard'),
        'style' => (string) apply_filters('mh/featured_image_style', 'natural'),
    ];

    $res = wp_remote_post('https://api.openai.com/v1/images/generations', [
        'timeout' => 90,
        'headers' => [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($res)) {
        return ['ok' => false, 'message' => $res->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code !== 200 || ! is_array($body)) {
        $err = is_array($body) ? (string) ($body['error']['message'] ?? '') : '';

        return [
            'ok' => false,
            'message' => $err !== '' ? $err : sprintf(__('OpenAI images request failed (HTTP %d).', 'sage'), $code),
        ];
    }

    $url = (string) ($body['data'][0]['url'] ?? '');
    $b64 = (string) ($body['data'][0]['b64_json'] ?? '');
    if ($url === '' && $b64 === '') {
        return ['ok' => false, 'message' => __('OpenAI returned no image data.', 'sage')];
    }

    if ($url === '' && $b64 !== '') {
        $tmp = wp_tempnam('mh-featured-');
        if (! $tmp || file_put_contents($tmp, base64_decode($b64, true) ?: '') === false) {
            return ['ok' => false, 'message' => __('Could not write temporary image file.', 'sage')];
        }

        return ['ok' => true, 'url' => $tmp, 'message' => __('Image ready.', 'sage'), 'local' => true];
    }

    return ['ok' => true, 'url' => $url, 'message' => __('Image ready.', 'sage'), 'local' => false];
}

/**
 * Sideload a remote (or local temp) image and set it as the featured image.
 *
 * @return array{ok: bool, attachment_id?: int, url?: string, message: string}
 */
function mh_featured_image_attach(int $postId, string $source, bool $isLocal = false, string $title = ''): array
{
    if ($postId <= 0 || $source === '') {
        return ['ok' => false, 'message' => __('Missing post or image source.', 'sage')];
    }

    if (! function_exists('media_handle_sideload')) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
    }

    $tmp = $source;
    if (! $isLocal) {
        $tmp = download_url($source, 90);
        if (is_wp_error($tmp)) {
            return ['ok' => false, 'message' => $tmp->get_error_message()];
        }
    }

    $filename = 'mh-featured-'.$postId.'-'.gmdate('YmdHis').'.png';
    $file = [
        'name' => $filename,
        'tmp_name' => $tmp,
    ];

    $attId = media_handle_sideload($file, $postId, $title !== '' ? $title : null);
    if (is_wp_error($attId)) {
        @unlink($tmp);

        return ['ok' => false, 'message' => $attId->get_error_message()];
    }

    set_post_thumbnail($postId, (int) $attId);
    $url = (string) wp_get_attachment_image_url((int) $attId, 'large');

    update_post_meta((int) $attId, '_wp_attachment_image_alt', $title !== '' ? $title : get_the_title($postId));

    return [
        'ok' => true,
        'attachment_id' => (int) $attId,
        'url' => $url,
        'message' => __('Featured image updated.', 'sage'),
    ];
}

/**
 * Generate a new featured image for a post.
 *
 * @return array{ok: bool, attachment_id?: int, url?: string, prompt?: string, message: string}
 */
function mh_featured_image_generate(int $postId, string $customPrompt = ''): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
        return ['ok' => false, 'message' => __('Post not found.', 'sage')];
    }

    $prompt = mh_featured_image_prompt($post, $customPrompt);
    $gen = mh_featured_image_openai_url($prompt);
    if (! $gen['ok']) {
        return $gen;
    }

    $attach = mh_featured_image_attach(
        $postId,
        (string) $gen['url'],
        ! empty($gen['local']),
        html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8')
    );
    if (! $attach['ok']) {
        return $attach;
    }

    $attach['prompt'] = $prompt;

    return $attach;
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_featured_image_ai',
        __('Generate featured image', 'sage'),
        __NAMESPACE__.'\\mh_featured_image_metabox',
        'post',
        'side',
        'high'
    );
});

function mh_featured_image_metabox(\WP_Post $post): void
{
    wp_nonce_field('mh_featured_image', 'mh_featured_image_nonce');

    $hasAi = function_exists(__NAMESPACE__.'\\mh_devto_ai_token') && mh_devto_ai_token() !== '';
    $thumbId = (int) get_post_thumbnail_id($post);
    $thumbUrl = $thumbId > 0 ? (string) wp_get_attachment_image_url($thumbId, 'medium') : '';

    echo '<div class="mh-featured-ai" id="mh-featured-ai" data-post-id="'.esc_attr((string) $post->ID).'">';
    echo '<p class="description">'.esc_html__('Creates a new DALL·E image from the title (and optional prompt), uploads it to Media, and sets it as the featured image on this post.', 'sage').'</p>';

    if ($thumbUrl !== '') {
        echo '<p class="mh-featured-ai__preview"><img src="'.esc_url($thumbUrl).'" alt="" style="max-width:100%;height:auto;border-radius:6px;border:1px solid #d0d5dd"></p>';
    } else {
        echo '<p class="mh-featured-ai__preview description">'.esc_html__('No featured image yet.', 'sage').'</p>';
    }

    echo '<p><label for="mh-featured-ai-prompt"><strong>'.esc_html__('Prompt override (optional)', 'sage').'</strong></label></p>';
    echo '<textarea id="mh-featured-ai-prompt" class="widefat" rows="4" placeholder="';
    echo esc_attr__('Leave blank to build from title + excerpt.', 'sage');
    echo '"></textarea>';

    echo '<p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px">';
    printf(
        '<button type="button" class="button button-primary" id="mh-featured-ai-generate" %1$s>%2$s</button>',
        disabled(! $hasAi, true, false),
        esc_html__('Generate new featured image', 'sage')
    );
    echo '</p>';

    if (! $hasAi) {
        echo '<p class="description">'.esc_html__('Needs an OpenAI key: Appearance → Customize → DEV.to (OpenAI key).', 'sage').'</p>';
    } else {
        echo '<p class="description">'.esc_html__('Uses the same OpenAI key as social drafts. Replaces the current featured image.', 'sage').'</p>';
    }

    echo '<p id="mh-featured-ai-status" class="description" aria-live="polite"></p>';
    echo '</div>';
}

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (! $screen || $screen->post_type !== 'post') {
        return;
    }

    wp_add_inline_script('jquery-core', <<<'JS'
(function () {
  const root = document.getElementById('mh-featured-ai')
  if (!root) return
  const nonce = document.getElementById('mh_featured_image_nonce')
  const btn = document.getElementById('mh-featured-ai-generate')
  const promptEl = document.getElementById('mh-featured-ai-prompt')
  const status = document.getElementById('mh-featured-ai-status')
  const preview = root.querySelector('.mh-featured-ai__preview')
  if (!nonce || !btn) return

  function setStatus (msg, isError) {
    if (!status) return
    status.textContent = msg || ''
    status.style.color = isError ? '#b32d2e' : ''
  }

  btn.addEventListener('click', async function () {
    if (!window.confirm('Generate a new featured image and replace the current one?')) return
    btn.disabled = true
    setStatus('Generating… this can take up to a minute.')
    const body = new FormData()
    body.append('action', 'mh_featured_image_generate')
    body.append('nonce', nonce.value)
    body.append('post_id', root.getAttribute('data-post-id') || '')
    body.append('prompt', promptEl ? promptEl.value : '')
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      if (!data || !data.success) {
        setStatus((data && data.data && data.data.message) || 'Generation failed', true)
        btn.disabled = false
        return
      }
      const url = data.data && data.data.url
      if (url && preview) {
        preview.innerHTML = '<img src="' + url + '" alt="" style="max-width:100%;height:auto;border-radius:6px;border:1px solid #d0d5dd">'
      }
      setStatus(data.data.message || 'Featured image updated.')
      const attId = data.data && data.data.attachment_id
      if (attId && window.wp && wp.data && wp.data.dispatch) {
        try { wp.data.dispatch('core/editor').editPost({ featured_media: attId }) } catch (e) {}
      } else if (attId && window.wp && wp.media && wp.media.featuredImage) {
        try { wp.media.featuredImage.set(attId) } catch (e) {}
      }
    } catch (err) {
      setStatus('Network error', true)
    }
    btn.disabled = false
  })
})()
JS);
});

add_action('wp_ajax_mh_featured_image_generate', function (): void {
    $postId = (int) ($_POST['post_id'] ?? 0);
    if (! check_ajax_referer('mh_featured_image', 'nonce', false)) {
        wp_send_json_error(['message' => __('Invalid nonce.', 'sage')], 403);
    }
    if ($postId <= 0 || ! current_user_can('edit_post', $postId)) {
        wp_send_json_error(['message' => __('You cannot edit this post.', 'sage')], 403);
    }

    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash((string) $_POST['prompt'])) : '';
    $result = mh_featured_image_generate($postId, $prompt);
    if (! $result['ok']) {
        wp_send_json_error(['message' => $result['message']]);
    }

    wp_send_json_success($result);
});
