<?php

/**
 * Theme updater — Appearance → Update Theme.
 *
 * Primary path: download the built zip from the GitHub Release `theme-latest`
 * (HTTPS into this WordPress install). No SiteGround FTP.
 *
 * Optional: dispatch deploy.yml so CI rebuilds that zip (and may still try FTP).
 *
 * Auth: Appearance → Customize → GitHub token, this screen, or MH_GITHUB_TOKEN.
 * Fine-grained PAT on matthummel-theme:
 *   - Contents: Read (install the zip)
 *   - Actions: Read and write (only if you trigger a rebuild)
 */

namespace App;

/**
 * Repository and workflow configuration used by the theme updater.
 *
 * All values are filterable via mh/updater_* hooks.
 *
 * @since 3.1.0
 *
 * @return array{owner: string, repo: string, workflow: string, ref: string, tag: string}
 */
function updater_repo(): array
{
    return [
        'owner' => (string) apply_filters('mh/updater_owner', 'matthummel-pa'),
        'repo' => (string) apply_filters('mh/updater_repo', 'matthummel-theme'),
        'workflow' => (string) apply_filters('mh/updater_workflow', 'deploy.yml'),
        'ref' => (string) apply_filters('mh/updater_ref', 'main'),
        'tag' => (string) apply_filters('mh/updater_release_tag', 'theme-latest'),
    ];
}

/**
 * POST a JSON payload to a GitHub API endpoint and return the HTTP status code and decoded body.
 *
 * @since 3.1.0
 *
 * @param  string  $url  Full GitHub API endpoint URL.
 * @param  array<string, mixed>  $body  Data to JSON-encode as the request body.
 * @return array{0: int, 1: array<string, mixed>} Tuple of [HTTP status code, decoded response array].
 */
function updater_api_post(string $url, array $body): array
{
    $res = wp_remote_post($url, [
        'timeout' => 20,
        'headers' => array_merge(github_headers(), ['Content-Type' => 'application/json']),
        'body' => wp_json_encode($body),
    ]);
    if (is_wp_error($res)) {
        return [0, ['message' => $res->get_error_message()]];
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $data = json_decode((string) wp_remote_retrieve_body($res), true);

    return [$code, is_array($data) ? $data : []];
}

/**
 * Fetch metadata for the most recent run of the deploy workflow.
 *
 * @since 3.1.0
 *
 * @return array{status: string, conclusion: string, html_url: string, created_at: string, event: string, number: int}|null
 *                                                                                                                          Null when no runs exist or the API request fails.
 */
function updater_latest_run(): ?array
{
    $r = updater_repo();
    $url = 'https://api.github.com/repos/'.rawurlencode($r['owner']).'/'.rawurlencode($r['repo'])
        .'/actions/workflows/'.rawurlencode($r['workflow']).'/runs?per_page=1';
    $data = github_get($url);
    if (! $data || empty($data['workflow_runs'][0]) || ! is_array($data['workflow_runs'][0])) {
        return null;
    }
    $run = $data['workflow_runs'][0];

    return [
        'status' => (string) ($run['status'] ?? ''),
        'conclusion' => (string) ($run['conclusion'] ?? ''),
        'html_url' => (string) ($run['html_url'] ?? ''),
        'created_at' => (string) ($run['created_at'] ?? ''),
        'event' => (string) ($run['event'] ?? ''),
        'number' => (int) ($run['run_number'] ?? 0),
    ];
}

/**
 * Fetch the GitHub Release tagged theme-latest that holds the built theme zip.
 *
 * @since 3.1.0
 *
 * @return array<string, mixed>|null Release data, or null when the release does not exist or the request fails.
 */
function updater_latest_release(): ?array
{
    $r = updater_repo();
    $url = 'https://api.github.com/repos/'.rawurlencode($r['owner']).'/'.rawurlencode($r['repo'])
        .'/releases/tags/'.rawurlencode($r['tag']);

    return github_get($url);
}

/**
 * Find the first zip asset attached to a GitHub release.
 *
 * @since 3.1.0
 *
 * @param  array<string, mixed>  $release  Release payload from the GitHub API.
 * @return array<string, mixed>|null The asset array, or null when no zip is found.
 */
function updater_release_zip_asset(array $release): ?array
{
    foreach ($release['assets'] ?? [] as $asset) {
        if (! is_array($asset)) {
            continue;
        }
        $name = strtolower((string) ($asset['name'] ?? ''));
        if (str_ends_with($name, '.zip')) {
            return $asset;
        }
    }

    return null;
}

/**
 * Trigger a GitHub Actions workflow dispatch to rebuild the theme zip.
 *
 * @since 3.1.0
 *
 * @return array{0: bool, 1: string} Tuple of [success flag, human-readable message].
 */
function updater_dispatch(): array
{
    $r = updater_repo();
    $token = github_token();
    if ($token === '') {
        return [false, __('No GitHub token is set. Paste one on this page (or under Appearance → Customize → GitHub) first.', 'sage')];
    }
    $url = 'https://api.github.com/repos/'.rawurlencode($r['owner']).'/'.rawurlencode($r['repo'])
        .'/actions/workflows/'.rawurlencode($r['workflow']).'/dispatches';

    [$code, $data] = updater_api_post($url, ['ref' => $r['ref']]);

    if ($code === 204) {
        return [true, __('GitHub is building a new zip. Wait a minute, refresh this page, then install it.', 'sage')];
    }

    $msg = isset($data['message']) ? (string) $data['message'] : __('Unknown error.', 'sage');
    if ($code === 401 || $code === 403) {
        $msg .= ' '.__('The token likely lacks “Actions: Read and write” on this repository.', 'sage');
    } elseif ($code === 404) {
        $msg .= ' '.__('Check the repo name and that the workflow exists on the default branch.', 'sage');
    }

    return [false, sprintf(__('GitHub returned %1$d: %2$s', 'sage'), $code, $msg)];
}

/**
 * Download theme-latest.zip from GitHub and install it over the active theme via WP_Upgrader.
 *
 * Uses HTTPS only; no FTP required. Does not modify the database, posts, or uploads.
 *
 * @since 3.1.0
 *
 * @return array{0: bool, 1: string} Tuple of [success flag, human-readable message].
 */
function updater_pull(): array
{
    $token = github_token();
    if ($token === '') {
        return [false, __('No GitHub token is set. Paste one on this page first. It needs Contents: Read on this repo.', 'sage')];
    }

    $release = updater_latest_release();
    if (! $release) {
        return [false, __('No theme-latest release yet. Push main (or click “Rebuild zip on GitHub”) and wait for Actions to finish.', 'sage')];
    }

    $asset = updater_release_zip_asset($release);
    $apiUrl = is_array($asset) ? (string) ($asset['url'] ?? '') : '';
    if ($apiUrl === '') {
        return [false, __('The latest release has no zip asset.', 'sage')];
    }

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH.'wp-admin/includes/theme.php';

    $tmp = wp_tempnam((string) ($asset['name'] ?? 'matthummel.zip'));
    if (! is_string($tmp) || $tmp === '') {
        return [false, __('Could not create a temp file for the download.', 'sage')];
    }

    $res = wp_remote_get($apiUrl, [
        'timeout' => 180,
        'redirection' => 5,
        'stream' => true,
        'filename' => $tmp,
        'headers' => array_merge(github_headers(), [
            'Accept' => 'application/octet-stream',
        ]),
    ]);

    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200 || ! is_readable($tmp) || (int) filesize($tmp) < 1000) {
        $res = wp_remote_get($apiUrl, [
            'timeout' => 180,
            'redirection' => 5,
            'headers' => array_merge(github_headers(), [
                'Accept' => 'application/octet-stream',
            ]),
        ]);
        if (is_wp_error($res)) {
            wp_delete_file($tmp);

            return [false, $res->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code !== 200) {
            wp_delete_file($tmp);

            return [false, sprintf(__('GitHub returned %d while downloading the zip. Contents: Read on the token?', 'sage'), $code)];
        }
        if (file_put_contents($tmp, (string) wp_remote_retrieve_body($res)) === false) {
            return [false, __('Could not write the downloaded zip to disk.', 'sage')];
        }
    }

    if (! is_readable($tmp) || (int) filesize($tmp) < 1000) {
        wp_delete_file($tmp);

        return [false, __('The downloaded zip was empty.', 'sage')];
    }

    if (! WP_Filesystem()) {
        wp_delete_file($tmp);

        return [false, __('WordPress could not write to the themes folder.', 'sage')];
    }

    $skin = new \Automatic_Upgrader_Skin;
    $upgrader = new \Theme_Upgrader($skin);
    $result = $upgrader->install($tmp, [
        'overwrite_package' => true,
        'clear_destination' => true,
    ]);
    wp_delete_file($tmp);

    if (is_wp_error($result)) {
        return [false, $result->get_error_message()];
    }
    if ($result === false) {
        $msgs = method_exists($skin, 'get_upgrade_messages') ? $skin->get_upgrade_messages() : [];
        $msg = is_array($msgs) && $msgs !== [] ? implode(' ', array_map('strval', $msgs)) : __('Theme install failed.', 'sage');

        return [false, $msg];
    }

    if (function_exists('wp_clean_themes_cache')) {
        wp_clean_themes_cache();
    }

    $sha = substr((string) ($release['target_commitish'] ?? ''), 0, 7);
    $when = (string) ($release['published_at'] ?? '');

    return [true, sprintf(
        __('Installed theme-latest%s%s. Theme files only — pages, posts, and uploads were not changed.', 'sage'),
        $sha !== '' ? ' ('.$sha.')' : '',
        $when !== '' ? ' · '.$when : ''
    )];
}

add_action('admin_menu', function () {
    add_theme_page(
        __('Update Theme', 'sage'),
        __('Update Theme', 'sage'),
        'update_themes',
        'mh-theme-update',
        __NAMESPACE__.'\\render_theme_updater_page'
    );
});

add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    $wp->add_section('mh_github', [
        'title' => __('GitHub', 'sage'),
        'priority' => 33,
    ]);
    $wp->add_setting('mh_gh_token', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_gh_token', [
        'label' => __('Access token', 'sage'),
        'description' => __('Fine-grained PAT for theme updates. Contents: Read. Add Actions read/write only if you trigger rebuilds from this screen.', 'sage'),
        'section' => 'mh_github',
        'type' => 'password',
    ]);

    $wp->add_section('mh_devto', [
        'title' => __('DEV.to', 'sage'),
        'priority' => 34,
    ]);
    $wp->add_setting('mh_devto_token', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_devto_token', [
        'label' => __('API key', 'sage'),
        'description' => __('From DEV.to → Settings → Extensions. Used for follower list, auto-import, and publishing journal posts to DEV.to. Or set MH_DEVTO_TOKEN in wp-config.', 'sage'),
        'section' => 'mh_devto',
        'type' => 'password',
    ]);
    $wp->add_setting('mh_openai_token', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_openai_token', [
        'label' => __('OpenAI key (optional)', 'sage'),
        'description' => __('Used when exporting to DEV.to, drafting social summaries (Bluesky, Facebook, Reddit, LinkedIn), and Generate featured image on posts and Projects. Or set MH_OPENAI_API_KEY / OPENAI_API_KEY. Without it, rule-based copy still runs; featured image generation stays disabled.', 'sage'),
        'section' => 'mh_devto',
        'type' => 'password',
    ]);
    $wp->add_setting('mh_devto_auto_import', [
        'default' => true,
        'sanitize_callback' => static function ($value): bool {
            return (bool) $value;
        },
    ]);
    $wp->add_control('mh_devto_auto_import', [
        'label' => __('Auto-import new posts', 'sage'),
        'description' => __('Hourly check for new DEV.to articles. Imports them into the Journal under the DEV.to category, styled like regular posts. Run `wp mh devto-import` anytime for a full pull.', 'sage'),
        'section' => 'mh_devto',
        'type' => 'checkbox',
    ]);

    $wp->add_section('mh_bluesky', [
        'title' => __('Bluesky', 'sage'),
        'priority' => 35,
    ]);
    $wp->add_setting('mh_bluesky_handle', [
        'default' => 'matthummel.bsky.social',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_bluesky_handle', [
        'label' => __('Handle', 'sage'),
        'description' => __('Your Bluesky handle, e.g. matthummel.bsky.social', 'sage'),
        'section' => 'mh_bluesky',
        'type' => 'text',
    ]);
    $wp->add_setting('mh_bluesky_app_password', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_bluesky_app_password', [
        'label' => __('App password', 'sage'),
        'description' => __('From Bluesky → Settings → App passwords. Or set MH_BLUESKY_APP_PASSWORD in wp-config. Never use your account password.', 'sage'),
        'section' => 'mh_bluesky',
        'type' => 'password',
    ]);
    $wp->add_setting('mh_bluesky_auto_share', [
        'default' => true,
        'sanitize_callback' => static function ($value): bool {
            return (bool) $value;
        },
    ]);
    $wp->add_control('mh_bluesky_auto_share', [
        'label' => __('Auto-share new journal posts', 'sage'),
        'description' => __('When you publish a post, share a summary + link on Bluesky (~20s after publish). Skips DEV.to imports. Paste a custom summary in Social share & drafts on the post editor, or use the OpenAI key from DEV.to settings.', 'sage'),
        'section' => 'mh_bluesky',
        'type' => 'checkbox',
    ]);
    $wp->add_setting('mh_bluesky_pds', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp->add_control('mh_bluesky_pds', [
        'label' => __('PDS URL (optional)', 'sage'),
        'description' => __('Leave blank to auto-resolve. Only set if you host on a custom PDS.', 'sage'),
        'section' => 'mh_bluesky',
        'type' => 'url',
    ]);
});

add_action('customize_save_after', function (): void {
    delete_transient('mh_devto_followers_v1');
    delete_transient('mh_bluesky_session_v1');
});

/**
 * Render the Appearance → Update Theme admin page.
 *
 * Handles token-save and update/build form submissions, then outputs the page HTML.
 *
 * @since 3.1.0
 */
function render_theme_updater_page(): void
{
    if (! current_user_can('update_themes') && ! current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to update the theme.', 'sage'));
    }

    $notice = null;

    if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '')) {
        if (isset($_POST['mh_updater_save_nonce'])) {
            check_admin_referer('mh_theme_token', 'mh_updater_save_nonce');
            $incoming = sanitize_text_field(wp_unslash((string) ($_POST['mh_gh_token'] ?? '')));
            if ($incoming === '') {
                $notice = ['notice-error', __('Paste a token before saving.', 'sage')];
            } else {
                set_theme_mod('mh_gh_token', $incoming);
                $notice = ['notice-success', __('GitHub token saved.', 'sage')];
            }
        } elseif (isset($_POST['mh_updater_nonce'])) {
            check_admin_referer('mh_theme_update', 'mh_updater_nonce');
            $action = sanitize_key(wp_unslash((string) ($_POST['mh_updater_action'] ?? 'pull')));
            [$ok, $msg] = $action === 'build' ? updater_dispatch() : updater_pull();
            $notice = [$ok ? 'notice-success' : 'notice-error', $msg];
        }
    }

    $r = updater_repo();
    $hasToken = github_token() !== '';
    $run = $hasToken ? updater_latest_run() : null;
    $release = $hasToken ? updater_latest_release() : null;
    $asset = $release ? updater_release_zip_asset($release) : null;
    $self = admin_url('themes.php?page=mh-theme-update');

    echo '<div class="wrap">';
    echo '<h1>'.esc_html__('Update Theme', 'sage').'</h1>';
    echo '<p style="max-width:70ch">'.esc_html__('Install the built theme over HTTPS from GitHub (a zip with vendor and Vite assets). This does not use SiteGround FTP and does not touch your database, posts, or uploads.', 'sage').'</p>';

    if ($notice) {
        printf('<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr($notice[0]), esc_html($notice[1]));
    }

    if ($release && $asset) {
        $when = ! empty($release['published_at'])
            ? esc_html(human_time_diff(strtotime((string) $release['published_at'])).' '.__('ago', 'sage'))
            : '';
        $size = size_format((int) ($asset['size'] ?? 0));
        printf(
            '<div class="notice notice-info inline"><p>%1$s <span class="description">%2$s · %3$s</span> — <a href="%4$s" target="_blank" rel="noopener">%5$s</a></p></div>',
            esc_html__('Latest GitHub zip is ready.', 'sage'),
            esc_html($size ?: ''),
            $when,
            esc_url((string) ($release['html_url'] ?? '')),
            esc_html__('view release', 'sage')
        );
    }

    if ($run) {
        if ($run['status'] !== 'completed') {
            $label = esc_html__('A GitHub build is running now…', 'sage');
            $cls = 'notice-warning';
        } elseif ($run['conclusion'] === 'success') {
            $label = esc_html__('✓ Last GitHub build succeeded.', 'sage');
            $cls = 'notice-success';
        } else {
            $label = esc_html(sprintf(__('Last GitHub build: %s.', 'sage'), $run['conclusion'] ?: 'unknown'));
            $cls = 'notice-error';
        }
        $when = $run['created_at'] ? esc_html(human_time_diff(strtotime($run['created_at'])).' '.__('ago', 'sage')) : '';
        printf(
            '<div class="notice %1$s inline"><p>%2$s <span class="description">#%3$d · %4$s · %5$s</span> — <a href="%6$s" target="_blank" rel="noopener">%7$s</a></p></div>',
            esc_attr($cls),
            $label,
            (int) $run['number'],
            esc_html($run['event']),
            $when,
            esc_url($run['html_url']),
            esc_html__('view run on GitHub', 'sage')
        );
    }

    echo '<hr />';

    if (! $hasToken) {
        echo '<h2>'.esc_html__('Token setup (one time)', 'sage').'</h2>';
        echo '<ol style="max-width:70ch">';
        echo '<li>'.wp_kses_post(__('Create a <strong>fine-grained personal access token</strong> at GitHub → Settings → Developer settings → Fine-grained tokens, scoped only to <code>matthummel-theme</code>.', 'sage')).'</li>';
        echo '<li>'.wp_kses_post(__('Give it <strong>Contents: Read</strong> to install the zip. Add <strong>Actions: Read and write</strong> only if you want this screen to trigger a rebuild.', 'sage')).'</li>';
        echo '<li>'.esc_html__('Paste it below and save. You can also set MH_GITHUB_TOKEN in wp-config.php.', 'sage').'</li>';
        echo '</ol>';
        echo '<form method="post" action="">';
        wp_nonce_field('mh_theme_token', 'mh_updater_save_nonce');
        echo '<p><label for="mh_gh_token"><strong>'.esc_html__('GitHub access token', 'sage').'</strong></label><br />';
        echo '<input type="password" class="regular-text" id="mh_gh_token" name="mh_gh_token" autocomplete="off" /></p>';
        printf('<p><button type="submit" class="button">%s</button></p>', esc_html__('Save token', 'sage'));
        echo '</form>';
    } else {
        echo '<form method="post" action="" style="margin-bottom:1.5rem">';
        wp_nonce_field('mh_theme_update', 'mh_updater_nonce');
        echo '<input type="hidden" name="mh_updater_action" value="pull" />';
        printf(
            '<p><button type="submit" class="button button-primary button-hero"%s>%s</button></p>',
            $asset ? '' : ' disabled',
            esc_html__('Install latest zip from GitHub', 'sage')
        );
        printf(
            '<p class="description">%s</p>',
            esc_html__('WordPress downloads the zip over HTTPS and overwrites this theme folder. No FTP.', 'sage')
        );
        echo '</form>';

        echo '<form method="post" action="">';
        wp_nonce_field('mh_theme_update', 'mh_updater_nonce');
        echo '<input type="hidden" name="mh_updater_action" value="build" />';
        printf(
            '<p><button type="submit" class="button">%s</button></p>',
            esc_html__('Rebuild zip on GitHub', 'sage')
        );
        printf(
            '<p class="description">%s</p>',
            esc_html(sprintf(
                __('Runs %1$s@%2$s. When it finishes, come back and install the zip.', 'sage'),
                $r['owner'].'/'.$r['repo'],
                $r['ref']
            ))
        );
        echo '</form>';
        echo '<p style="margin-top:1rem"><a class="button" href="'.esc_url($self).'">'.esc_html__('Refresh status', 'sage').'</a></p>';
    }

    echo '</div>';
}

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh theme-update', function (): void {
        [$ok, $msg] = updater_pull();
        $ok ? \WP_CLI::success($msg) : \WP_CLI::error($msg);
    });
    \WP_CLI::add_command('mh theme-build', function (): void {
        [$ok, $msg] = updater_dispatch();
        $ok ? \WP_CLI::success($msg) : \WP_CLI::error($msg);
    });
}
