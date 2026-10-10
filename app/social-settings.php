<?php

/**
 * Social & AI settings screen (Appearance → Social & AI).
 *
 * Holds every social and AI token, model choice, and the link to each service's
 * token page. Values are saved as theme mods under the same names the theme
 * already reads, so Bluesky, DEV.to, LinkedIn, Facebook, Claude, and Grokbot
 * keep working without further changes. If Hummel Ops is active, the screen is
 * also listed under its menu.
 */

namespace App;

const MH_SOCIAL_SETTINGS_PAGE = 'mh-social-settings';

/**
 * Settings the screen manages.
 * Secrets render as password fields and keep their value when left blank.
 *
 * @return array{secrets: array<string, string>, toggles: array<string, bool>, plain: array<string, string>}
 */
function mh_social_settings_fields(): array
{
    return [
        'secrets' => [
            'mh_anthropic_token' => 'MH_ANTHROPIC_API_KEY',
            'mh_xai_token' => 'MH_XAI_API_KEY',
            'mh_openai_token' => 'MH_OPENAI_API_KEY',
            'mh_facebook_page_token' => 'MH_FACEBOOK_PAGE_TOKEN',
            'mh_bluesky_app_password' => 'MH_BLUESKY_APP_PASSWORD',
            'mh_devto_token' => 'MH_DEVTO_TOKEN',
            'mh_li_token' => 'MH_LINKEDIN_TOKEN',
        ],
        'toggles' => [
            'mh_bluesky_auto_share' => true,
            'mh_devto_auto_import' => true,
        ],
        'plain' => [
            'mh_social_ai_default' => '',
            'mh_anthropic_model' => '',
            'mh_xai_model' => '',
            'mh_xai_model_custom' => '',
            'mh_facebook_page_id' => '',
            'mh_bluesky_pds' => '',
            'mh_li_headline' => '',
            'mh_li_about' => '',
            'mh_li_location' => '',
            'mh_li_open_to_work' => '',
            'mh_bluesky_handle' => '',
        ],
    ];
}

function mh_social_settings_url(): string
{
    return admin_url('themes.php?page='.MH_SOCIAL_SETTINGS_PAGE);
}

add_action('admin_menu', function (): void {
    add_theme_page(
        __('Social & AI', 'sage'),
        __('Social & AI', 'sage'),
        'manage_options',
        MH_SOCIAL_SETTINGS_PAGE,
        __NAMESPACE__.'\\mh_social_settings_render'
    );
}, 20);

// List the same screen under Hummel Ops when that plugin is active.
add_action('admin_menu', function (): void {
    global $menu;
    foreach ((array) $menu as $item) {
        if (($item[2] ?? '') === 'hops-today') {
            add_submenu_page(
                'hops-today',
                __('Social & AI', 'sage'),
                __('Social & AI', 'sage'),
                'manage_options',
                MH_SOCIAL_SETTINGS_PAGE,
                __NAMESPACE__.'\\mh_social_settings_render'
            );
            break;
        }
    }
}, 99);

// The Customizer no longer carries these sections; this screen replaces them.
add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    foreach (['mh_ai_drafting', 'mh_facebook', 'mh_bluesky', 'mh_devto', 'mh_linkedin'] as $section) {
        $wp->remove_section($section);
    }
}, 999);

add_action('admin_post_mh_save_social', function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to do that.', 'sage'), 403);
    }
    check_admin_referer('mh_save_social');

    $fields = mh_social_settings_fields();
    $in = isset($_POST['mh_social']) && is_array($_POST['mh_social']) ? map_deep(wp_unslash($_POST['mh_social']), 'sanitize_textarea_field') : [];

    foreach (array_keys($fields['secrets']) as $mod) {
        if (! empty($in['clear'][$mod])) {
            remove_theme_mod($mod);

            continue;
        }
        $value = trim((string) ($in[$mod] ?? ''));
        if ($value !== '') {
            set_theme_mod($mod, sanitize_text_field($value));
        }
    }

    foreach (array_keys($fields['plain']) as $mod) {
        if (! array_key_exists($mod, $in)) {
            continue;
        }
        if ($mod === 'mh_li_about') {
            $value = sanitize_textarea_field((string) $in[$mod]);
        } elseif ($mod === 'mh_bluesky_pds') {
            $value = esc_url_raw((string) $in[$mod]);
        } elseif ($mod === 'mh_facebook_page_id') {
            $value = (string) preg_replace('/\D+/', '', (string) $in[$mod]);
        } elseif (in_array($mod, ['mh_anthropic_model', 'mh_xai_model', 'mh_xai_model_custom'], true)) {
            $value = trim((string) preg_replace('/[^A-Za-z0-9._:\/-]/', '', (string) $in[$mod]));
        } else {
            $value = sanitize_text_field((string) $in[$mod]);
        }
        if ($mod === 'mh_li_open_to_work' && ! in_array($value, ['', 'yes', 'no'], true)) {
            $value = '';
        }
        if ($mod === 'mh_social_ai_default' && ! array_key_exists($value, mh_social_ai_provider_labels())) {
            $value = 'claude';
        }
        if ($value === '') {
            remove_theme_mod($mod);
        } else {
            set_theme_mod($mod, $value);
        }
    }

    foreach (array_keys($fields['toggles']) as $mod) {
        if (array_key_exists($mod, $in)) {
            set_theme_mod($mod, ! empty($in[$mod]));
        }
    }

    delete_transient('mh_models_claude');
    wp_safe_redirect(add_query_arg('mh_saved', '1', mh_social_settings_url()));
    exit;
});

/**
 * Numbered "how to connect" steps. Steps may contain links, code, and strong tags.
 *
 * @param  array<int, string>  $steps
 */
function mh_social_settings_steps(array $steps): void
{
    if (! $steps) {
        return;
    }
    echo '<div style="margin:8px 0 12px;padding:10px 16px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px"><strong>'.esc_html__('How to connect', 'sage').'</strong><ol style="margin:6px 0 4px 20px">';
    foreach ($steps as $step) {
        echo '<li style="margin:4px 0">'.wp_kses($step, ['a' => ['href' => [], 'target' => [], 'rel' => []], 'code' => [], 'strong' => []]).'</li>';
    }
    echo '</ol></div>';
}

function mh_social_settings_pill(bool $on, string $onText = 'Connected', string $offText = 'Not set up'): string
{
    return sprintf(
        '<span style="display:inline-block;padding:1px 10px;border-radius:999px;font-size:12.5px;line-height:1.6;background:%1$s;color:%2$s">%3$s</span>',
        $on ? '#edfaef' : '#eceef0',
        $on ? '#00630f' : '#3c434a',
        esc_html($on ? $onText : $offText)
    );
}

/**
 * @param  array<string, string>  $links  label => url
 */
function mh_social_settings_links(array $links): string
{
    $out = [];
    foreach ($links as $label => $url) {
        $out[] = sprintf('<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s ↗</a>', esc_url($url), esc_html($label));
    }

    return $out ? '<p style="margin:6px 0 0">'.implode(' · ', $out).'</p>' : '';
}

function mh_social_settings_secret_row(string $mod, string $label, string $constant, array $links = [], string $placeholder = ''): void
{
    $fromConstant = defined($constant) && is_string(constant($constant)) && constant($constant) !== '';
    $saved = trim((string) get_theme_mod($mod, '')) !== '';
    $id = 'mh-social-'.$mod;

    echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
    if ($fromConstant) {
        echo mh_social_settings_pill(true, sprintf(__('Set in wp-config (%s)', 'sage'), $constant)); // phpcs:ignore WordPress.Security.EscapeOutput
    } else {
        printf(
            '<input id="%1$s" type="password" class="regular-text" autocomplete="new-password" name="mh_social[%2$s]" value="" placeholder="%3$s"> ',
            esc_attr($id),
            esc_attr($mod),
            esc_attr($saved ? __('Saved (leave blank to keep)', 'sage') : $placeholder)
        );
        echo mh_social_settings_pill($saved); // phpcs:ignore WordPress.Security.EscapeOutput
        if ($saved) {
            printf(' <label><input type="checkbox" name="mh_social[clear][%1$s]" value="1"> %2$s</label>', esc_attr($mod), esc_html__('Remove', 'sage'));
        }
    }
    echo mh_social_settings_links($links); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</td></tr>';
}

function mh_social_settings_text_row(string $mod, string $label, string $desc = '', string $placeholder = ''): void
{
    $id = 'mh-social-'.$mod;
    echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
    printf(
        '<input id="%1$s" type="text" class="regular-text" name="mh_social[%2$s]" value="%3$s" placeholder="%4$s">',
        esc_attr($id),
        esc_attr($mod),
        esc_attr((string) get_theme_mod($mod, '')),
        esc_attr($placeholder)
    );
    if ($desc !== '') {
        echo '<p class="description">'.esc_html($desc).'</p>';
    }
    echo '</td></tr>';
}

function mh_social_settings_toggle_row(string $mod, string $label, bool $default, string $desc = ''): void
{
    $id = 'mh-social-'.$mod;
    $on = (bool) get_theme_mod($mod, $default);
    echo '<tr><th scope="row">'.esc_html($label).'</th><td>';
    printf('<input type="hidden" name="mh_social[%1$s]" value="0">', esc_attr($mod));
    printf('<label for="%1$s"><input id="%1$s" type="checkbox" name="mh_social[%2$s]" value="1"%3$s> %4$s</label>', esc_attr($id), esc_attr($mod), checked($on, true, false), esc_html__('On', 'sage'));
    if ($desc !== '') {
        echo '<p class="description">'.esc_html($desc).'</p>';
    }
    echo '</td></tr>';
}

function mh_social_settings_textarea_row(string $mod, string $label, string $desc = ''): void
{
    $id = 'mh-social-'.$mod;
    echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
    printf('<textarea id="%1$s" class="large-text" rows="3" name="mh_social[%2$s]">%3$s</textarea>', esc_attr($id), esc_attr($mod), esc_textarea((string) get_theme_mod($mod, '')));
    if ($desc !== '') {
        echo '<p class="description">'.esc_html($desc).'</p>';
    }
    echo '</td></tr>';
}

/**
 * @param  array<string, string>  $choices
 */
function mh_social_settings_select_row(string $mod, string $label, array $choices, string $default, string $desc = ''): void
{
    $id = 'mh-social-'.$mod;
    $current = (string) get_theme_mod($mod, $default);
    if (! isset($choices[$current])) {
        $choices[$current] = $current.' ('.__('saved', 'sage').')';
    }
    echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
    printf('<select id="%1$s" name="mh_social[%2$s]">', esc_attr($id), esc_attr($mod));
    foreach ($choices as $value => $text) {
        printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr((string) $value), selected($current, (string) $value, false), esc_html($text));
    }
    echo '</select>';
    if ($desc !== '') {
        echo '<p class="description">'.esc_html($desc).'</p>';
    }
    echo '</td></tr>';
}

function mh_social_settings_open(string $title, string $statusHtml, string $intro = '', array $links = [], bool $open = false): void
{
    echo '<details class="mh-social-svc" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:0 16px;margin:0 0 12px"'.($open ? ' open' : '').'>';
    echo '<summary style="cursor:pointer;padding:14px 0;font-size:15px;font-weight:600">'.esc_html($title).' '.$statusHtml.'</summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
    echo '<div style="padding:0 0 12px">';
    if ($intro !== '') {
        echo '<p>'.esc_html($intro).'</p>';
    }
    echo mh_social_settings_links($links); // phpcs:ignore WordPress.Security.EscapeOutput
}

function mh_social_settings_close(): void
{
    echo '</div></details>';
}

function mh_social_settings_render(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $status = mh_social_connection_status();
    $meta = mh_social_connection_links();
    $on = count(array_filter($status));

    echo '<div class="wrap">';
    echo '<h1>'.esc_html__('Social & AI', 'sage').'</h1>';
    echo '<p class="description">'.esc_html(sprintf(__('%1$d of %2$d connected. Tokens here power Draft with in the post editor and the Bluesky, DEV.to, and LinkedIn tools.', 'sage'), $on, count($status))).'</p>';
    if (isset($_GET['mh_saved'])) { // phpcs:ignore WordPress.Security.NonceVerification
        echo '<div class="notice notice-success is-dismissible"><p>'.esc_html__('Settings saved.', 'sage').'</p></div>';
    }

    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="max-width:860px">';
    wp_nonce_field('mh_save_social');
    echo '<input type="hidden" name="action" value="mh_save_social">';

    $aiReady = ! empty($status['claude']) || ! empty($status['grok']) || ! empty($status['openai']);
    mh_social_settings_open(
        __('AI drafting', 'sage'),
        mh_social_settings_pill($aiReady, __('Ready', 'sage'), __('No key yet', 'sage')),
        __('Used by Draft with in the post editor. Without a key, use Copy brief and paste it into Claude Cowork or Grokbot.', 'sage'),
        [],
        true
    );
    mh_social_settings_steps([
        __('Pick one provider and open its key page (links below). Add a payment method there if it asks.', 'sage'),
        __('Create an API key, copy it once, and paste it into that provider\'s field. Leave the others blank.', 'sage'),
        __('Save. The model list fills in from your account, then choose the default provider and model.', 'sage'),
        __('In a post, pick Draft with and Model, then Generate. No key? Use Copy brief and paste it into Claude Cowork or Grokbot.', 'sage'),
    ]);
    echo '<table class="form-table" role="presentation">';
    mh_social_settings_select_row('mh_social_ai_default', __('Default provider', 'sage'), mh_social_ai_provider_labels(), 'claude', __('Used when the post editor does not choose one.', 'sage'));
    mh_social_settings_secret_row('mh_anthropic_token', __('Claude Cowork: Anthropic API key', 'sage'), 'MH_ANTHROPIC_API_KEY', [
        'Generate API key' => mh_social_connection_meta()['claude']['key_url'],
        $meta['claude']['guide_label'] => $meta['claude']['guide'],
        $meta['claude']['dashboard_label'] => $meta['claude']['dashboard'],
    ], 'sk-ant-...');
    mh_social_settings_select_row('mh_anthropic_model', __('Claude model', 'sage'), mh_social_model_choices('claude', (string) get_theme_mod('mh_anthropic_model', '')), 'claude-sonnet-5-5', __('With a valid key, every model your account can use is listed.', 'sage'));
    mh_social_settings_secret_row('mh_xai_token', __('Grokbot: xAI API key', 'sage'), 'MH_XAI_API_KEY', [
        'Generate API key' => mh_social_connection_meta()['grok']['key_url'],
        $meta['grok']['guide_label'] => $meta['grok']['guide'],
        $meta['grok']['dashboard_label'] => $meta['grok']['dashboard'],
    ], 'xai-...');
    mh_social_settings_select_row('mh_xai_model', __('Grok model / bot', 'sage'), mh_social_model_choices('grok', (string) get_theme_mod('mh_xai_model', '')), 'grok-4', __('Lists every model your xAI key can call, including custom or fine-tuned models on your account.', 'sage'));
    mh_social_settings_text_row('mh_xai_model_custom', __('Custom Grok model ID', 'sage'), __('Overrides the list. Use it for a model that is not shown above.', 'sage'));
    mh_social_settings_secret_row('mh_openai_token', __('OpenAI API key', 'sage'), 'MH_OPENAI_API_KEY', [
        'Generate API key' => mh_social_connection_meta()['openai']['key_url'],
        $meta['openai']['guide_label'] => $meta['openai']['guide'],
        $meta['openai']['dashboard_label'] => $meta['openai']['dashboard'],
    ], 'sk-...');
    echo '</table>';
    mh_social_settings_close();

    $networks = [
        'bluesky' => [
            'title' => __('Bluesky', 'sage'),
            'steps' => [
                '<a href="https://bsky.app/settings/app-passwords" target="_blank" rel="noopener noreferrer">Open Settings, Privacy and security, App passwords</a>.',
                'Choose <strong>Add App Password</strong>, name it <code>matthummel.com</code>, and copy the password.',
                'Paste it below with your handle (for example <code>matthummel.bsky.social</code>). Never use your account password.',
                'Save. Publish a post to see it shared about 20 seconds later.',
            ],
            'intro' => __('Create an app password, never your account password.', 'sage'),
            'rows' => static function (array $links): void {
                mh_social_settings_text_row('mh_bluesky_handle', __('Handle', 'sage'), '', 'matthummel.bsky.social');
                mh_social_settings_secret_row('mh_bluesky_app_password', __('App password', 'sage'), 'MH_BLUESKY_APP_PASSWORD', $links);
                mh_social_settings_toggle_row('mh_bluesky_auto_share', __('Auto-share new journal posts', 'sage'), true, __('Shares a summary and link about 20 seconds after you publish. Skips DEV.to imports.', 'sage'));
                mh_social_settings_text_row('mh_bluesky_pds', __('PDS URL (optional)', 'sage'), __('Leave blank to auto-resolve. Set it only if you host on a custom PDS.', 'sage'));
            },
        ],
        'facebook' => [
            'title' => __('Facebook', 'sage'),
            'steps' => [
                '<a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener noreferrer">Create a Meta app</a> (type: Business) and add your Facebook Page.',
                'Open the <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener noreferrer">Graph API Explorer</a>, pick your app, choose <strong>Get Page Access Token</strong>, and grant <code>pages_manage_posts</code> and <code>pages_read_engagement</code>.',
                'Exchange it for a <a href="https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived/" target="_blank" rel="noopener noreferrer">long-lived token</a> so it does not expire in an hour.',
                'Run <code>me/accounts</code> in the Explorer to read the numeric Page ID. Paste the ID and token below.',
            ],
            'intro' => __('Use the Graph API Explorer to generate a Page access token for your app and Page.', 'sage'),
            'rows' => static function (array $links): void {
                mh_social_settings_text_row('mh_facebook_page_id', __('Page ID', 'sage'));
                mh_social_settings_secret_row('mh_facebook_page_token', __('Page access token', 'sage'), 'MH_FACEBOOK_PAGE_TOKEN', $links);
            },
        ],
        'devto' => [
            'title' => __('DEV.to', 'sage'),
            'steps' => [
                '<a href="https://dev.to/settings/extensions" target="_blank" rel="noopener noreferrer">Open Settings, Extensions</a> and find DEV Community API Keys.',
                'Enter a description such as <code>matthummel.com</code> and choose <strong>Generate API Key</strong>.',
                'Paste the key below and save. Turn on auto-import to pull in new DEV.to articles each hour.',
            ],
            'intro' => __('Settings, Extensions, DEV Community API Keys.', 'sage'),
            'rows' => static function (array $links): void {
                mh_social_settings_secret_row('mh_devto_token', __('API key', 'sage'), 'MH_DEVTO_TOKEN', $links);
                mh_social_settings_toggle_row('mh_devto_auto_import', __('Auto-import new posts', 'sage'), true, __('Hourly check for new DEV.to articles, imported into the Journal under the DEV.to category.', 'sage'));
            },
        ],
        'linkedin' => [
            'title' => __('LinkedIn', 'sage'),
            'steps' => [
                '<a href="https://www.linkedin.com/developers/apps" target="_blank" rel="noopener noreferrer">Create a LinkedIn app</a> linked to a Company Page, then open its Products tab and add <strong>Sign In with LinkedIn using OpenID Connect</strong>.',
                'Open the <a href="https://www.linkedin.com/developers/tools/oauth/token-generator" target="_blank" rel="noopener noreferrer">token generator</a>, pick your app, tick <code>openid</code>, <code>profile</code>, and <code>email</code>, and generate a token.',
                'Paste the access token below. LinkedIn tokens expire, so repeat this when the Hire page stops showing your photo.',
            ],
            'intro' => __('Needs a LinkedIn developer app. The token generator issues a token for it.', 'sage'),
            'rows' => static function (array $links): void {
                mh_social_settings_secret_row('mh_li_token', __('Access token', 'sage'), 'MH_LINKEDIN_TOKEN', $links);
                mh_social_settings_text_row('mh_li_headline', __('Headline override', 'sage'));
                mh_social_settings_textarea_row('mh_li_about', __('About blurb', 'sage'));
                mh_social_settings_text_row('mh_li_location', __('Location label', 'sage'), '', 'Gettysburg, PA');
                mh_social_settings_select_row('mh_li_open_to_work', __('Open to work badge', 'sage'), ['' => __('Follow GitHub hireable', 'sage'), 'yes' => __('Force on', 'sage'), 'no' => __('Force off', 'sage')], '');
            },
        ],
    ];

    foreach ($networks as $slug => $net) {
        $links = [
            'Generate token' => mh_social_connection_meta()[$slug]['key_url'],
            $meta[$slug]['guide_label'] => $meta[$slug]['guide'],
            $meta[$slug]['dashboard_label'] => $meta[$slug]['dashboard'],
        ];
        mh_social_settings_open($net['title'], mh_social_settings_pill(! empty($status[$slug])), $net['intro'], $links);
        mh_social_settings_steps($net['steps'] ?? []);
        echo '<table class="form-table" role="presentation">';
        $net['rows']([]);
        echo '</table>';
        mh_social_settings_close();
    }

    echo '<p class="description">'.esc_html__('Prefer to keep tokens out of the database? Define the matching constant in wp-config.php (for example MH_ANTHROPIC_API_KEY) and it takes priority.', 'sage').'</p>';
    submit_button(__('Save social settings', 'sage'));
    echo '</form></div>';
}
