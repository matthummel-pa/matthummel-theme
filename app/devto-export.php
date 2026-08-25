<?php

/**
 * Convert journal posts to DEV.to: HTML → Markdown, friendly rewrite, API publish.
 */

namespace App;

/**
 * Resolve an optional OpenAI-compatible key for DEV.to rewrites.
 */
function mh_devto_ai_token(): string
{
    foreach (['MH_OPENAI_API_KEY', 'OPENAI_API_KEY'] as $const) {
        if (defined($const) && is_string(constant($const)) && constant($const) !== '') {
            return trim((string) constant($const));
        }
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_openai_token', '')) : '';

    return (string) apply_filters('mh/devto_ai_token', $mod);
}

/**
 * Convert post HTML (Gutenberg or classic) into Markdown.
 */
function mh_html_to_markdown(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = preg_replace('/<!--\s*\/?wp:[^>]*-->/', '', $html) ?? $html;

    if (! class_exists(\DOMDocument::class)) {
        return trim(wp_strip_all_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="UTF-8"><div id="mh-root">'.$html.'</div>';
    @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $root = $dom->getElementById('mh-root');
    if (! $root) {
        return trim(wp_strip_all_tags($html));
    }

    $md = mh_dom_nodes_to_markdown($root->childNodes);

    $md = preg_replace("/\n{3,}/", "\n\n", $md) ?? $md;
    $md = preg_replace("/\n[ \t]+\n/", "\n\n", $md) ?? $md;

    return trim($md);
}

/**
 * @param  \DOMNodeList<\DOMNode>|iterable<\DOMNode>  $nodes
 */
function mh_dom_nodes_to_markdown($nodes): string
{
    $out = '';
    foreach ($nodes as $node) {
        $out .= mh_dom_node_to_markdown($node);
    }

    return $out;
}

function mh_dom_node_to_markdown(\DOMNode $node): string
{
    if ($node->nodeType === XML_TEXT_NODE) {
        $text = preg_replace('/\s+/', ' ', $node->nodeValue ?? '') ?? '';

        return $text;
    }
    if ($node->nodeType !== XML_ELEMENT_NODE || ! $node instanceof \DOMElement) {
        return '';
    }

    $tag = strtolower($node->tagName);
    $inner = mh_dom_nodes_to_markdown($node->childNodes);

    return match ($tag) {
        'h1' => "\n\n# ".trim(wp_strip_all_tags($inner))."\n\n",
        'h2' => "\n\n## ".trim(wp_strip_all_tags($inner))."\n\n",
        'h3' => "\n\n### ".trim(wp_strip_all_tags($inner))."\n\n",
        'h4' => "\n\n#### ".trim(wp_strip_all_tags($inner))."\n\n",
        'p' => "\n\n".trim($inner)."\n\n",
        'br' => "\n",
        'hr' => "\n\n---\n\n",
        'strong', 'b' => '**'.trim($inner).'**',
        'em', 'i' => '*'.trim($inner).'*',
        'code' => (str_contains($inner, '`') ? $inner : '`'.trim($inner).'`'),
        'a' => mh_dom_link_to_markdown($node, $inner),
        'img' => mh_dom_img_to_markdown($node),
        'blockquote' => mh_dom_blockquote_to_markdown($inner),
        'ul' => mh_dom_list_to_markdown($node, false),
        'ol' => mh_dom_list_to_markdown($node, true),
        'li' => trim($inner),
        'pre' => mh_dom_pre_to_markdown($node),
        'figure', 'div', 'span', 'section', 'article' => mh_dom_nodes_to_markdown($node->childNodes),
        default => trim(wp_strip_all_tags($inner)),
    };
}

function mh_dom_link_to_markdown(\DOMElement $node, string $inner): string
{
    $href = trim($node->getAttribute('href'));
    $label = trim(wp_strip_all_tags($inner));
    if ($href === '') {
        return $label;
    }
    if ($label === '') {
        $label = $href;
    }

    return '['.$label.']('.$href.')';
}

function mh_dom_img_to_markdown(\DOMElement $node): string
{
    $src = trim($node->getAttribute('src'));
    if ($src === '') {
        return '';
    }
    $alt = trim($node->getAttribute('alt'));

    return "\n\n![".$alt.']('.$src.")\n\n";
}

function mh_dom_blockquote_to_markdown(string $inner): string
{
    $lines = preg_split('/\R/', trim(wp_strip_all_tags($inner))) ?: [];
    $quoted = array_map(static fn ($line) => '> '.ltrim($line), $lines);

    return "\n\n".implode("\n", $quoted)."\n\n";
}

function mh_dom_list_to_markdown(\DOMElement $node, bool $ordered): string
{
    $items = [];
    $i = 1;
    foreach ($node->childNodes as $child) {
        if (! $child instanceof \DOMElement || strtolower($child->tagName) !== 'li') {
            continue;
        }
        $text = trim(mh_dom_nodes_to_markdown($child->childNodes));
        if ($text === '') {
            continue;
        }
        $prefix = $ordered ? $i.'. ' : '- ';
        $items[] = $prefix.$text;
        $i++;
    }
    if ($items === []) {
        return '';
    }

    return "\n\n".implode("\n", $items)."\n\n";
}

function mh_dom_pre_to_markdown(\DOMElement $node): string
{
    $code = '';
    $lang = '';
    foreach ($node->getElementsByTagName('code') as $codeEl) {
        if ($codeEl instanceof \DOMElement) {
            $class = $codeEl->getAttribute('class');
            if (preg_match('/language-([a-z0-9_+-]+)/i', $class, $m)) {
                $lang = strtolower($m[1]);
            }
            $code = $codeEl->textContent ?? '';
            break;
        }
    }
    if ($code === '') {
        $code = $node->textContent ?? '';
    }
    $code = html_entity_decode($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $code = rtrim($code, "\n");

    return "\n\n```{$lang}\n{$code}\n```\n\n";
}

/**
 * Make relative site URLs absolute for DEV.to readers.
 */
function mh_devto_absolutize_urls(string $md): string
{
    $home = untrailingslashit(home_url('/'));

    $md = preg_replace_callback(
        '/\]\((\/[^)\s]*)\)/',
        static function (array $m) use ($home): string {
            return ']('.$home.$m[1].')';
        },
        $md
    ) ?? $md;

    $md = preg_replace_callback(
        '/\]\((?!https?:\/\/|mailto:|#)([^)\s]+)\)/',
        static function (array $m) use ($home): string {
            $path = ltrim($m[1], '/');

            return ']('.$home.'/'.$path.')';
        },
        $md
    ) ?? $md;

    return $md;
}

/**
 * Rule-based rewrite so journal copy reads well on DEV.to.
 */
function mh_devto_rule_rewrite(string $md, \WP_Post $post): string
{
    $md = mh_devto_absolutize_urls($md);

    // Drop import footers if this was previously pulled from DEV.to.
    $md = preg_replace('/\n+---\n+\*?Originally posted on \[DEV\.to\].*$/is', '', $md) ?? $md;
    $md = preg_replace('/\n+\*?Originally posted on \[DEV\.to\].*$/im', '', $md) ?? $md;

    $replacements = [
        'on this site' => 'on [matthummel.com]('.untrailingslashit(home_url('/')).')',
        'on my site' => 'on [matthummel.com]('.untrailingslashit(home_url('/')).')',
        'this journal' => 'my blog',
        'the journal' => 'my blog',
        'Say hello' => 'reach out',
        'say hello' => 'reach out',
    ];
    $md = str_replace(array_keys($replacements), array_values($replacements), $md);

    // Soften shop-owner CTAs that feel odd in the DEV.to feed.
    $md = preg_replace(
        '/\n+Extra copy-paste examples live on .+?\n/is',
        "\n",
        $md
    ) ?? $md;

    $permalink = get_permalink($post) ?: home_url('/');
    if (! str_contains($md, 'Originally published on')) {
        $md = rtrim($md)."\n\n---\n\n*Originally published on [matthummel.com]({$permalink}).*\n";
    }

    return trim($md)."\n";
}

/**
 * Optional OpenAI rewrite for a warmer DEV.to voice. Fails soft.
 */
function mh_devto_ai_rewrite(string $md, string $title): ?string
{
    $token = mh_devto_ai_token();
    if ($token === '') {
        return null;
    }

    $prompt = "Rewrite the following blog post markdown for DEV.to.\n"
        ."Keep first-person voice (I/my). Keep all code fences and links.\n"
        ."Make it friendly to developers on DEV.to: clear headings, short paragraphs,\n"
        ."no WordPress admin jargon, no fake metrics.\n"
        ."Return ONLY markdown, no explanation.\n\n"
        ."Title: {$title}\n\n{$md}";

    $res = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 45,
        'headers' => [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'matthummel.com',
        ],
        'body' => wp_json_encode([
            'model' => apply_filters('mh/devto_ai_model', 'gpt-4o-mini'),
            'temperature' => 0.4,
            'messages' => [
                ['role' => 'system', 'content' => 'You convert WordPress journal posts into clean DEV.to markdown.'],
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
    // Strip accidental fences around the whole doc.
    if (preg_match('/^```(?:markdown|md)?\s*([\s\S]*?)\s*```$/i', $text, $m)) {
        $text = trim($m[1]);
    }

    return $text."\n";
}

/**
 * Suggest up to 4 DEV.to tags from post categories/tags.
 *
 * @return list<string>
 */
function mh_devto_suggest_tags(\WP_Post $post): array
{
    $map = [
        'wordpress' => 'wordpress',
        'web-development' => 'webdev',
        'web-apps' => 'webdev',
        'power-apps' => 'powerapps',
        'power-automate' => 'powerautomate',
        'tutorials' => 'tutorial',
        'accessibility' => 'a11y',
        'snippets' => 'php',
        'dev-to' => 'devto',
        'tips-and-troubleshooting' => 'beginners',
    ];

    $out = [];
    foreach (get_the_category($post->ID) as $cat) {
        $slug = $cat->slug;
        $tag = $map[$slug] ?? sanitize_title($slug);
        $tag = substr(str_replace('-', '', $tag), 0, 20);
        if ($tag !== '' && ! in_array($tag, $out, true)) {
            $out[] = $tag;
        }
        if (count($out) >= 4) {
            return $out;
        }
    }
    $tags = get_the_tags($post->ID);
    if (is_array($tags)) {
        foreach ($tags as $tagObj) {
            $tag = substr(str_replace('-', '', sanitize_title($tagObj->slug)), 0, 20);
            if ($tag !== '' && ! in_array($tag, $out, true)) {
                $out[] = $tag;
            }
            if (count($out) >= 4) {
                break;
            }
        }
    }
    if ($out === []) {
        $out[] = 'webdev';
    }

    return $out;
}

/**
 * Build a DEV.to-ready payload from a journal post.
 *
 * @return array{ok: bool, title: string, markdown: string, tags: list<string>, canonical_url: string, description: string, cover: string, message: string}
 */
function mh_devto_prepare_export(int $postId, bool $useAi = true): array
{
    $post = get_post($postId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'post') {
        return [
            'ok' => false,
            'title' => '',
            'markdown' => '',
            'tags' => [],
            'canonical_url' => '',
            'description' => '',
            'cover' => '',
            'message' => 'Not a journal post.',
        ];
    }

    $html = (string) $post->post_content;
    $md = mh_html_to_markdown($html);
    $md = mh_devto_rule_rewrite($md, $post);

    if ($useAi) {
        $ai = mh_devto_ai_rewrite($md, get_the_title($post));
        if (is_string($ai) && $ai !== '') {
            $md = mh_devto_absolutize_urls($ai);
            if (! str_contains($md, 'Originally published on')) {
                $permalink = get_permalink($post) ?: home_url('/');
                $md = rtrim($md)."\n\n---\n\n*Originally published on [matthummel.com]({$permalink}).*\n";
            }
        }
    }

    $md = (string) apply_filters('mh/devto_export_markdown', $md, $post);
    $md = preg_replace("/\n{3,}/", "\n\n", $md) ?? $md;
    $md = trim($md)."\n";

    $excerpt = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags($md), 28);
    $cover = get_the_post_thumbnail_url($post, 'full') ?: '';

    return [
        'ok' => true,
        'title' => get_the_title($post),
        'markdown' => $md,
        'tags' => mh_devto_suggest_tags($post),
        'canonical_url' => (string) get_permalink($post),
        'description' => $excerpt,
        'cover' => is_string($cover) ? $cover : '',
        'message' => 'Ready',
    ];
}

/**
 * Create or update a DEV.to article from a prepared payload.
 *
 * @param  array{title: string, markdown: string, tags: list<string>, canonical_url: string, description: string, cover: string}  $payload
 * @return array{ok: bool, id: int, url: string, message: string}
 */
function mh_devto_publish_article(array $payload, bool $published = false, int $existingId = 0): array
{
    $token = mh_devto_token();
    if ($token === '') {
        return [
            'ok' => false,
            'id' => 0,
            'url' => '',
            'message' => 'Add a DEV.to API key in Customizer → DEV.to (or MH_DEVTO_TOKEN).',
        ];
    }

    $article = [
        'title' => $payload['title'],
        'body_markdown' => $payload['markdown'],
        'published' => $published,
        'tags' => array_values(array_slice($payload['tags'], 0, 4)),
        'canonical_url' => $payload['canonical_url'],
        'description' => mb_substr(wp_strip_all_tags($payload['description']), 0, 200),
    ];
    if (($payload['cover'] ?? '') !== '') {
        $article['main_image'] = $payload['cover'];
    }

    $headers = [
        'api-key' => $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/vnd.forem.api-v1+json',
        'User-Agent' => 'matthummel.com',
    ];

    if ($existingId > 0) {
        $res = wp_remote_request('https://dev.to/api/articles/'.$existingId, [
            'method' => 'PUT',
            'timeout' => 30,
            'headers' => $headers,
            'body' => wp_json_encode(['article' => $article]),
        ]);
    } else {
        $res = wp_remote_post('https://dev.to/api/articles', [
            'timeout' => 30,
            'headers' => $headers,
            'body' => wp_json_encode(['article' => $article]),
        ]);
    }

    if (is_wp_error($res)) {
        return ['ok' => false, 'id' => 0, 'url' => '', 'message' => $res->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $body = json_decode((string) wp_remote_retrieve_body($res), true);
    if ($code < 200 || $code >= 300 || ! is_array($body)) {
        $err = is_array($body) ? wp_json_encode($body) : (string) wp_remote_retrieve_body($res);

        return ['ok' => false, 'id' => 0, 'url' => '', 'message' => 'DEV.to API error ('.$code.'): '.$err];
    }

    return [
        'ok' => true,
        'id' => (int) ($body['id'] ?? 0),
        'url' => (string) ($body['url'] ?? ''),
        'message' => $published ? 'Published on DEV.to' : 'Draft created on DEV.to',
    ];
}

/**
 * Export a journal post to DEV.to (draft or published).
 *
 * @return array{ok: bool, id: int, url: string, markdown: string, message: string}
 */
function mh_devto_export_post(int $postId, bool $publish = false, bool $useAi = true): array
{
    $prepared = mh_devto_prepare_export($postId, $useAi);
    if (! $prepared['ok']) {
        return [
            'ok' => false,
            'id' => 0,
            'url' => '',
            'markdown' => '',
            'message' => $prepared['message'],
        ];
    }

    $existingId = (int) get_post_meta($postId, '_mh_devto_export_id', true);
    $result = mh_devto_publish_article($prepared, $publish, $existingId);
    if ($result['ok']) {
        if ($result['id'] > 0) {
            update_post_meta($postId, '_mh_devto_export_id', (string) $result['id']);
        }
        if ($result['url'] !== '') {
            update_post_meta($postId, '_mh_devto_export_url', esc_url_raw($result['url']));
        }
        update_post_meta($postId, '_mh_devto_export_md', $prepared['markdown']);
        update_post_meta($postId, '_mh_devto_export_at', (string) time());
    }

    return [
        'ok' => $result['ok'],
        'id' => $result['id'],
        'url' => $result['url'],
        'markdown' => $prepared['markdown'],
        'message' => $result['message'],
    ];
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'mh_devto_export',
        __('DEV.to', 'sage'),
        __NAMESPACE__.'\\mh_devto_export_metabox',
        'post',
        'side',
        'default'
    );
});

function mh_devto_export_metabox(\WP_Post $post): void
{
    wp_nonce_field('mh_devto_export', 'mh_devto_export_nonce');
    $exportUrl = (string) get_post_meta($post->ID, '_mh_devto_export_url', true);
    $exportId = (string) get_post_meta($post->ID, '_mh_devto_export_id', true);
    $hasToken = mh_devto_token() !== '';
    $hasAi = mh_devto_ai_token() !== '';

    echo '<p class="description">'.esc_html__('Convert this journal post to DEV.to-friendly Markdown, then create a draft or publish.', 'sage').'</p>';
    if ($exportUrl !== '') {
        printf(
            '<p><strong>%1$s</strong> <a href="%2$s" target="_blank" rel="noopener">%3$s</a>%4$s</p>',
            esc_html__('On DEV.to:', 'sage'),
            esc_url($exportUrl),
            esc_html__('View article', 'sage'),
            $exportId !== '' ? ' <code>#'.esc_html($exportId).'</code>' : ''
        );
    }
    if (! $hasToken) {
        echo '<p class="description">'.esc_html__('Set a DEV.to API key under Appearance → Customize → DEV.to to push drafts.', 'sage').'</p>';
    }
    echo '<p><label><input type="checkbox" name="mh_devto_use_ai" value="1" '.checked($hasAi, true, false).'> ';
    echo esc_html__('AI rewrite (needs OpenAI key)', 'sage').'</label></p>';
    echo '<p class="description">'.esc_html__('Without an AI key, a rule-based rewrite still runs (absolute links, softer CTAs, canonical footer).', 'sage').'</p>';

    echo '<p style="display:flex;flex-direction:column;gap:6px">';
    echo '<button type="button" class="button" id="mh-devto-preview">'.esc_html__('Preview Markdown', 'sage').'</button>';
    echo '<button type="button" class="button" id="mh-devto-draft" '.disabled(! $hasToken, true, false).'>'.esc_html__('Create DEV.to draft', 'sage').'</button>';
    echo '<button type="button" class="button button-primary" id="mh-devto-publish" '.disabled(! $hasToken, true, false).'>'.esc_html__('Publish to DEV.to', 'sage').'</button>';
    echo '</p>';
    echo '<p id="mh-devto-status" class="description" aria-live="polite"></p>';
    echo '<textarea id="mh-devto-md" class="widefat" rows="12" readonly style="font-family:ui-monospace,monospace;font-size:12px"></textarea>';
}

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'post') {
        return;
    }

    wp_register_script('mh-devto-export', '', ['wp-util'], '1', true);
    wp_enqueue_script('mh-devto-export');
    wp_add_inline_script('mh-devto-export', <<<'JS'
(function () {
  const status = document.getElementById('mh-devto-status')
  const mdBox = document.getElementById('mh-devto-md')
  const nonce = document.getElementById('mh_devto_export_nonce')
  if (!status || !mdBox || !nonce || typeof ajaxurl === 'undefined') return

  function useAi () {
    const el = document.querySelector('input[name="mh_devto_use_ai"]')
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
    try {
      const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' })
      const data = await res.json()
      if (!data || !data.success) {
        setStatus((data && data.data && data.data.message) || 'Request failed', true)
        if (data && data.data && data.data.markdown) mdBox.value = data.data.markdown
        return null
      }
      return data.data
    } catch (err) {
      setStatus('Network error', true)
      return null
    }
  }

  document.getElementById('mh-devto-preview')?.addEventListener('click', async function () {
    const data = await call('mh_devto_preview')
    if (!data) return
    mdBox.value = data.markdown || ''
    setStatus(data.message || 'Markdown ready')
  })

  document.getElementById('mh-devto-draft')?.addEventListener('click', async function () {
    const data = await call('mh_devto_draft')
    if (!data) return
    mdBox.value = data.markdown || ''
    setStatus(data.message + (data.url ? ' — ' + data.url : ''))
  })

  document.getElementById('mh-devto-publish')?.addEventListener('click', async function () {
    if (!window.confirm('Publish this post to DEV.to now?')) return
    const data = await call('mh_devto_publish')
    if (!data) return
    mdBox.value = data.markdown || ''
    setStatus(data.message + (data.url ? ' — ' + data.url : ''))
  })
})()
JS);
});

function mh_devto_ajax_guard(): int
{
    if (! current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }
    check_ajax_referer('mh_devto_export', 'nonce');
    $postId = (int) ($_POST['post_id'] ?? 0);
    if ($postId <= 0 || ! current_user_can('edit_post', $postId)) {
        wp_send_json_error(['message' => 'Invalid post'], 400);
    }

    return $postId;
}

add_action('wp_ajax_mh_devto_preview', function (): void {
    $postId = mh_devto_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    $prepared = mh_devto_prepare_export($postId, $useAi);
    if (! $prepared['ok']) {
        wp_send_json_error(['message' => $prepared['message']]);
    }
    update_post_meta($postId, '_mh_devto_export_md', $prepared['markdown']);
    wp_send_json_success([
        'markdown' => $prepared['markdown'],
        'message' => $useAi && mh_devto_ai_token() !== ''
            ? __('Markdown ready (AI rewrite when key works).', 'sage')
            : __('Markdown ready (rule-based DEV.to rewrite).', 'sage'),
        'tags' => $prepared['tags'],
    ]);
});

add_action('wp_ajax_mh_devto_draft', function (): void {
    $postId = mh_devto_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    $result = mh_devto_export_post($postId, false, $useAi);
    if (! $result['ok']) {
        wp_send_json_error(['message' => $result['message'], 'markdown' => $result['markdown']]);
    }
    wp_send_json_success($result);
});

add_action('wp_ajax_mh_devto_publish', function (): void {
    $postId = mh_devto_ajax_guard();
    $useAi = ! empty($_POST['use_ai']);
    $result = mh_devto_export_post($postId, true, $useAi);
    if (! $result['ok']) {
        wp_send_json_error(['message' => $result['message'], 'markdown' => $result['markdown']]);
    }
    wp_send_json_success($result);
});

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh devto-export', function ($args, $assoc): void {
        $postId = (int) ($args[0] ?? 0);
        if ($postId <= 0) {
            \WP_CLI::error('Usage: wp mh devto-export <post_id> [--publish] [--skip-ai] [--stdout]');
        }
        $publish = isset($assoc['publish']);
        $useAi = ! isset($assoc['skip-ai']);
        $stdout = isset($assoc['stdout']);

        if ($stdout) {
            $prepared = mh_devto_prepare_export($postId, $useAi);
            if (! $prepared['ok']) {
                \WP_CLI::error($prepared['message']);
            }
            \WP_CLI::line($prepared['markdown']);

            return;
        }

        $result = mh_devto_export_post($postId, $publish, $useAi);
        if (! $result['ok']) {
            \WP_CLI::warning($result['markdown'] !== '' ? 'Markdown was generated but push failed.' : '');
            \WP_CLI::error($result['message']);
        }
        \WP_CLI::success($result['message'].($result['url'] !== '' ? ' '.$result['url'] : ''));
    }, [
        'shortdesc' => 'Convert a journal post to DEV.to Markdown and optionally publish.',
        'synopsis' => [
            ['type' => 'positional', 'name' => 'post_id', 'optional' => false],
            ['type' => 'flag', 'name' => 'publish', 'optional' => true],
            ['type' => 'flag', 'name' => 'skip-ai', 'optional' => true, 'description' => 'Skip OpenAI rewrite; use rule-based only.'],
            ['type' => 'flag', 'name' => 'stdout', 'optional' => true, 'description' => 'Print Markdown only; do not call the API.'],
        ],
    ]);
}
