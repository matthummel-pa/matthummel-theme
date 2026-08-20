<?php

/**
 * Comment form, list, and reply notifications.
 */

namespace App;

/**
 * One-time discussion defaults used by popular blogs: threaded replies and cookie opt-in.
 */
add_action('init', function (): void {
    if (get_option('mh_comment_ux_v1') || wp_installing()) {
        return;
    }

    update_option('thread_comments', '1');
    update_option('thread_comments_depth', '4');
    update_option('show_comments_cookies_opt_in', '1');
    update_option('mh_comment_ux_v1', '1');
}, 20);

add_action('wp_enqueue_scripts', function (): void {
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});

add_filter('comment_form_defaults', function (array $defaults): array {
    $req = (bool) get_option('require_name_email');
    $reqMark = $req ? ' <span class="field-req" aria-hidden="true">'.esc_html__('(required)', 'sage').'</span>' : '';
    $reqAttr = $req ? ' required' : '';

    $defaults['title_reply'] = __('Leave a comment', 'sage');
    $defaults['title_reply_to'] = __('Reply to %s', 'sage');
    $defaults['title_reply_before'] = '<h2 id="reply-title" class="comment-reply-title">';
    $defaults['title_reply_after'] = '</h2>';
    $defaults['cancel_reply_before'] = '<p class="comment-cancel-reply">';
    $defaults['cancel_reply_after'] = '</p>';
    $defaults['cancel_reply_link'] = __('Cancel reply', 'sage');
    $defaults['label_submit'] = __('Post comment', 'sage');
    $defaults['class_form'] = 'comment-form';
    $defaults['class_submit'] = 'btn';
    $defaults['submit_button'] = '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>';
    $defaults['submit_field'] = '<p class="form-submit comment-form-submit">%1$s %2$s</p>';
    $defaults['comment_notes_before'] = sprintf(
        '<p class="comment-notes" id="comment-notes">%s %s</p>',
        '<span id="email-notes">'.esc_html__('Your email stays private. It is only used if you ask for reply notices.', 'sage').'</span>',
        $req ? '<span class="screen-reader-text">'.esc_html__('Required fields are marked required.', 'sage').'</span>' : ''
    );
    $defaults['comment_notes_after'] = '';
    $user = wp_get_current_user();
    if ($user->exists()) {
        $defaults['logged_in_as'] = sprintf(
            '<p class="logged-in-as">%s</p>',
            sprintf(
                /* translators: 1: display name, 2: profile URL, 3: logout URL */
                __('Signed in as %1$s. <a href="%2$s">Edit profile</a>. <a href="%3$s">Log out</a>.', 'sage'),
                esc_html($user->display_name),
                esc_url(get_edit_user_link()),
                esc_url(wp_logout_url(get_permalink()))
            )
        );
    }

    $defaults['comment_field'] = mh_comment_textarea_field();

    $commenter = wp_get_current_commenter();
    $html5 = current_theme_supports('html5', 'comment-form');

    $defaults['fields']['author'] = sprintf(
        '<p class="comment-form-author"><label for="author">%s%s</label><input id="author" name="author" type="text" value="%s" size="30" maxlength="245" autocomplete="name" autocapitalize="words" spellcheck="false"%s></p>',
        esc_html__('Name', 'sage'),
        $reqMark,
        esc_attr($commenter['comment_author'] ?? ''),
        $reqAttr
    );
    $defaults['fields']['email'] = sprintf(
        '<p class="comment-form-email"><label for="email">%s%s</label><input id="email" name="email" type="%s" value="%s" size="30" maxlength="100" autocomplete="email" inputmode="email" aria-describedby="email-notes comment-notes"%s></p>',
        esc_html__('Email', 'sage'),
        $reqMark,
        $html5 ? 'email' : 'text',
        esc_attr($commenter['comment_author_email'] ?? ''),
        $reqAttr
    );
    $defaults['fields']['url'] = sprintf(
        '<p class="comment-form-url"><label for="url">%s <span class="field-opt">%s</span></label><input id="url" name="url" type="%s" value="%s" size="30" maxlength="200" autocomplete="url" inputmode="url"></p>',
        esc_html__('Website', 'sage'),
        esc_html__('(optional)', 'sage'),
        $html5 ? 'url' : 'text',
        esc_attr($commenter['comment_author_url'] ?? '')
    );

    $consent = empty($commenter['comment_author_email']) ? '' : ' checked';
    if (has_action('set_comment_cookies', 'wp_set_comment_cookies') && get_option('show_comments_cookies_opt_in')) {
        $defaults['fields']['cookies'] = sprintf(
            '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%s> <label for="wp-comment-cookies-consent">%s</label></p>',
            $consent,
            esc_html__('Save my name, email, and site in this browser for next time.', 'sage')
        );
    }

    $defaults['fields']['mh_notify'] = sprintf(
        '<p class="comment-form-notify"><input id="mh_notify_replies" name="mh_notify_replies" type="checkbox" value="1"> <label for="mh_notify_replies">%s</label></p>',
        esc_html__('Email me if someone replies to this comment.', 'sage')
    );

    return $defaults;
});

function mh_comment_textarea_field(): string
{
    $tools = [
        'bold' => __('Bold', 'sage'),
        'italic' => __('Italic', 'sage'),
        'link' => __('Link', 'sage'),
        'code' => __('Inline code', 'sage'),
        'quote' => __('Quote', 'sage'),
        'ul' => __('List', 'sage'),
    ];

    $buttons = '';
    foreach ($tools as $id => $label) {
        $buttons .= sprintf(
            '<button type="button" class="comment-tool" data-comment-tool="%s">%s</button>',
            esc_attr($id),
            esc_html($label)
        );
    }

    return sprintf(
        '<div class="comment-compose">
            <div class="comment-compose-head">
                <label for="comment">%1$s <span class="field-req" aria-hidden="true">%2$s</span></label>
                <div class="comment-tabs" role="tablist" aria-label="%3$s">
                    <button type="button" class="comment-tab is-active" role="tab" id="comment-tab-write" aria-selected="true" aria-controls="comment-panel-write">%4$s</button>
                    <button type="button" class="comment-tab" role="tab" id="comment-tab-preview" aria-selected="false" aria-controls="comment-panel-preview">%5$s</button>
                </div>
            </div>
            <div class="comment-toolbar" role="toolbar" aria-label="%6$s" aria-controls="comment">%7$s</div>
            <div id="comment-panel-write" class="comment-panel" role="tabpanel" aria-labelledby="comment-tab-write">
                <textarea id="comment" name="comment" cols="45" rows="8" maxlength="8000" required aria-describedby="comment-format-hint comment-count" aria-required="true" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="true"></textarea>
            </div>
            <div id="comment-panel-preview" class="comment-panel" role="tabpanel" aria-labelledby="comment-tab-preview" hidden>
                <div class="comment-preview" id="comment-preview" aria-live="polite"></div>
            </div>
            <p class="comment-compose-meta">
                <span id="comment-format-hint" class="field-hint">%8$s</span>
                <span id="comment-count" class="comment-count" aria-live="polite">0 / 8000</span>
            </p>
        </div>',
        esc_html__('Comment', 'sage'),
        esc_html__('(required)', 'sage'),
        esc_html__('Comment editor mode', 'sage'),
        esc_html__('Write', 'sage'),
        esc_html__('Preview', 'sage'),
        esc_html__('Formatting', 'sage'),
        $buttons,
        esc_html__('Tip: **bold**, _italic_, `code`, [text](https://), and > quotes. ASCII punctuation is kept as typed.', 'sage')
    );
}

/**
 * Map common smart punctuation back to ASCII so markdown still matches.
 */
function mh_comment_ascii(string $text): string
{
    return strtr($text, [
        "\u{2018}" => "'",
        "\u{2019}" => "'",
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{00AB}" => '"',
        "\u{00BB}" => '"',
        "\u{2013}" => '-',
        "\u{2014}" => '--',
        "\u{2212}" => '-',
        "\u{2026}" => '...',
        "\u{00A0}" => ' ',
    ]);
}

/**
 * Turn a small Markdown subset into allowed comment HTML.
 * User-typed ASCII (*, _, `, >, quotes, &, <) is preserved.
 */
function mh_comment_from_markdown(string $raw): string
{
    $text = mh_comment_ascii(str_replace(["\r\n", "\r"], "\n", $raw));
    $slots = [];
    $stash = static function (string $html) use (&$slots): string {
        $key = '%%MH'.count($slots).'%%';
        $slots[$key] = $html;

        return $key;
    };

    $text = preg_replace_callback('/```(?:[a-zA-Z0-9_-]+)?\n([\s\S]*?)```/', function ($m) use ($stash) {
        return $stash('<pre><code>'.esc_html(rtrim($m[1], "\n")).'</code></pre>');
    }, $text) ?? $text;

    $text = preg_replace_callback('/`([^`\n]+)`/', function ($m) use ($stash) {
        return $stash('<code>'.esc_html($m[1]).'</code>');
    }, $text) ?? $text;

    $text = esc_html($text);
    $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" rel="nofollow ugc">$1</a>', $text) ?? $text;
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/(?<![A-Za-z0-9*])\*(?!\*)(.+?)(?<!\*)\*(?![A-Za-z0-9*])/s', '<em>$1</em>', $text) ?? $text;
    $text = preg_replace('/(?<![A-Za-z0-9])_([^_\n]+)_(?![A-Za-z0-9])/', '<em>$1</em>', $text) ?? $text;

    $lines = explode("\n", $text);
    $out = [];
    $inList = false;
    $inQuote = false;
    foreach ($lines as $line) {
        if (preg_match('/^&gt;\s?(.*)$/', $line, $m)) {
            if ($inList) {
                $out[] = '</ul>';
                $inList = false;
            }
            if (! $inQuote) {
                $out[] = '<blockquote>';
                $inQuote = true;
            }
            $out[] = $m[1];

            continue;
        }
        if ($inQuote) {
            $out[] = '</blockquote>';
            $inQuote = false;
        }
        if (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
            if (! $inList) {
                $out[] = '<ul>';
                $inList = true;
            }
            $out[] = '<li>'.$m[1].'</li>';

            continue;
        }
        if ($inList) {
            $out[] = '</ul>';
            $inList = false;
        }
        $out[] = $line;
    }
    if ($inQuote) {
        $out[] = '</blockquote>';
    }
    if ($inList) {
        $out[] = '</ul>';
    }

    $text = implode("\n", $out);
    $text = strtr($text, $slots);
    $text = wpautop($text);

    return $text;
}

add_action('init', function (): void {
    remove_filter('comment_text', 'wptexturize');
    remove_filter('comment_text', 'convert_chars');
    remove_filter('comment_text', 'wpautop', 30);
    remove_filter('comment_text', 'make_clickable', 9);
}, 100);

add_filter('pre_comment_content', function (string $content): string {
    return mh_comment_ascii($content);
}, 5);

add_filter('comment_text', function (string $text): string {
    if (preg_match('/<[a-z][\s\S]*>/i', $text)) {
        return wpautop($text);
    }

    return mh_comment_from_markdown($text);
}, 5);

add_action('comment_post', function ($id, $approved): void {
    $id = (int) $id;
    if (! empty($_POST['mh_notify_replies'])) {
        add_comment_meta($id, 'mh_notify_replies', '1', true);
    }
    if ((string) $approved === '1') {
        mh_mail_comment_reply($id);
    }
}, 20, 2);

add_action('comment_unapproved_to_approved', function ($comment): void {
    if ($comment instanceof \WP_Comment) {
        mh_mail_comment_reply((int) $comment->comment_ID);
    }
});

function mh_mail_comment_reply(int $commentId): void
{
    $comment = get_comment($commentId);
    if (! $comment || (int) $comment->comment_parent === 0) {
        return;
    }
    $parent = get_comment($comment->comment_parent);
    if (! $parent || get_comment_meta((int) $parent->comment_ID, 'mh_notify_replies', true) !== '1') {
        return;
    }
    $to = $parent->comment_author_email;
    if (! is_email($to) || strcasecmp($to, (string) $comment->comment_author_email) === 0) {
        return;
    }
    $post = get_post((int) $comment->comment_post_ID);
    if (! $post) {
        return;
    }
    $subject = sprintf(
        /* translators: %s: post title */
        __('New reply on "%s"', 'sage'),
        $post->post_title
    );
    $body = sprintf(
        "%s\n\n%s\n",
        sprintf(
            /* translators: %s: commenter name */
            __('%s replied to your comment:', 'sage'),
            $comment->comment_author
        ),
        wp_strip_all_tags($comment->comment_content)
    );
    $body .= "\n".get_comment_link($comment)."\n";
    wp_mail($to, $subject, $body);
}

/**
 * Markup for one comment in the list.
 *
 * @param  mixed  $comment
 * @param  array<string, mixed>  $args
 */
function mh_comment_list_item($comment, array $args, int $depth): void
{
    $comment = get_comment($comment);
    if (! $comment) {
        return;
    }

    $GLOBALS['comment'] = $comment;
    $approved = (string) $comment->comment_approved === '1';
    $stamp = (int) get_comment_time('U', true, $comment);
    $ago = $stamp ? human_time_diff($stamp, time()) : '';
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class($depth > 1 ? 'comment--child' : '', $comment); ?>>
      <article class="comment-body" id="div-comment-<?php comment_ID(); ?>">
        <header class="comment-meta">
          <?php echo get_avatar($comment, (int) ($args['avatar_size'] ?? 48), '', esc_attr($comment->comment_author), ['class' => 'comment-avatar']); ?>
          <div class="comment-meta-text">
            <p class="comment-author vcard">
              <span class="fn"><?php comment_author_link($comment); ?></span>
            </p>
            <p class="comment-metadata">
              <a class="comment-time" href="<?php echo esc_url(get_comment_link($comment)); ?>">
                <time datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>">
                  <?php
                    echo esc_html(get_comment_date('', $comment));
    if ($ago !== '') {
        echo ' | '.esc_html(sprintf(
            /* translators: %s: human time difference */
            __('%s ago', 'sage'),
            $ago
        ));
    }
    ?>
                </time>
              </a>
              <button type="button" class="comment-copy-link" data-copy="<?php echo esc_url(get_comment_link($comment)); ?>">
                <?php esc_html_e('Copy link', 'sage'); ?>
              </button>
              <?php edit_comment_link(__('Edit', 'sage'), '<span class="comment-edit">', '</span>'); ?>
            </p>
          </div>
        </header>
        <?php if (! $approved) { ?>
          <p class="comment-awaiting" role="status"><?php esc_html_e('Your comment is waiting for approval.', 'sage'); ?></p>
        <?php } ?>
        <div class="comment-content">
          <?php comment_text(); ?>
        </div>
        <footer class="comment-actions">
          <?php
            comment_reply_link(array_merge($args, [
                'depth' => $depth,
                'max_depth' => $args['max_depth'],
                'reply_text' => __('Reply', 'sage'),
                'login_text' => __('Log in to reply', 'sage'),
            ]));
    ?>
        </footer>
      </article>
    <?php
}
