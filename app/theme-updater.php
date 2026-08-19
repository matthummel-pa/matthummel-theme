<?php

/**
 * Theme updater — push the latest committed theme to the live site from
 * wp-admin. Lives at Appearance → Update Theme.
 *
 * Clicking "Update theme from GitHub" triggers deploy.yml via the GitHub
 * Actions API (workflow_dispatch). CI then builds Sage and FTP-uploads to
 * SiteGround — the same pipeline a git push to main uses. Code only; it
 * never touches the database, uploads, or content.
 *
 * Auth uses Appearance → Customize → GitHub token (mh_gh_token), the same
 * field saved on this page, or MH_GITHUB_TOKEN in wp-config.php. Dispatch
 * needs a fine-grained PAT on matthummel-theme with:
 *   - Actions:  Read and write
 *   - Contents: Read-only
 */

namespace App;

/** Repo + workflow the updater deploys. All filterable. */
function updater_repo(): array
{
    return [
        'owner' => (string) apply_filters('mh/updater_owner', 'matthummel-pa'),
        'repo' => (string) apply_filters('mh/updater_repo', 'matthummel-theme'),
        'workflow' => (string) apply_filters('mh/updater_workflow', 'deploy.yml'),
        'ref' => (string) apply_filters('mh/updater_ref', 'main'),
    ];
}

/** POST JSON to the GitHub API with the theme's GitHub auth headers. */
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

/** The most recent run of the deploy workflow, or null. */
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

/** Trigger the deploy workflow. Returns [bool ok, string message]. */
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
        return [true, __('Deploy triggered. The live theme updates in about 1–2 minutes — watch the status below.', 'sage')];
    }

    $msg = isset($data['message']) ? (string) $data['message'] : __('Unknown error.', 'sage');
    if ($code === 401 || $code === 403) {
        $msg .= ' '.__('The token likely lacks “Actions: Read and write” on this repository.', 'sage');
    } elseif ($code === 404) {
        $msg .= ' '.__('Check the repo name and that the workflow exists on the default branch.', 'sage');
    }

    return [false, sprintf(__('GitHub returned %1$d: %2$s', 'sage'), $code, $msg)];
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
        'description' => __('Fine-grained PAT for theme updates and optional API rate limits. Actions: Read and write, Contents: Read-only, scoped to matthummel-theme.', 'sage'),
        'section' => 'mh_github',
        'type' => 'password',
    ]);
});

function render_theme_updater_page(): void
{
    if (! current_user_can('update_themes') && ! current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to update the theme.', 'sage'));
    }

    $notice = null;

    if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['mh_updater_save_nonce'])) {
        check_admin_referer('mh_theme_token', 'mh_updater_save_nonce');
        $incoming = trim((string) ($_POST['mh_gh_token'] ?? ''));
        if ($incoming === '') {
            $notice = ['notice-error', __('Paste a token before saving.', 'sage')];
        } else {
            set_theme_mod('mh_gh_token', sanitize_text_field($incoming));
            $notice = ['notice-success', __('GitHub token saved.', 'sage')];
        }
    }

    if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['mh_updater_nonce'])) {
        check_admin_referer('mh_theme_update', 'mh_updater_nonce');
        [$ok, $msg] = updater_dispatch();
        $notice = [$ok ? 'notice-success' : 'notice-error', $msg];
    }

    $r = updater_repo();
    $hasToken = github_token() !== '';
    $run = $hasToken ? updater_latest_run() : null;
    $self = admin_url('themes.php?page=mh-theme-update');

    echo '<div class="wrap">';
    echo '<h1>'.esc_html__('Update Theme', 'sage').'</h1>';
    echo '<p style="max-width:70ch">'.esc_html__('Deploy the latest committed theme to the live site. This runs the same build-and-FTP pipeline a git push uses, so it updates theme files only — never your content, database, or uploads.', 'sage').'</p>';

    if ($notice) {
        printf('<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr($notice[0]), esc_html($notice[1]));
    }

    if ($run) {
        if ($run['status'] !== 'completed') {
            $label = esc_html__('A deploy is running now…', 'sage');
            $cls = 'notice-warning';
        } elseif ($run['conclusion'] === 'success') {
            $label = esc_html__('✓ Last deploy succeeded.', 'sage');
            $cls = 'notice-success';
        } else {
            $label = esc_html(sprintf(__('Last deploy: %s.', 'sage'), $run['conclusion'] ?: 'unknown'));
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
        echo '<li>'.wp_kses_post(__('Give it <strong>Actions: Read and write</strong> and <strong>Contents: Read-only</strong>.', 'sage')).'</li>';
        echo '<li>'.esc_html__('Paste it below and save. You can also set MH_GITHUB_TOKEN in wp-config.php.', 'sage').'</li>';
        echo '</ol>';
        echo '<form method="post" action="">';
        wp_nonce_field('mh_theme_token', 'mh_updater_save_nonce');
        echo '<p><label for="mh_gh_token"><strong>'.esc_html__('GitHub access token', 'sage').'</strong></label><br />';
        echo '<input type="password" class="regular-text" id="mh_gh_token" name="mh_gh_token" autocomplete="off" /></p>';
        printf('<p><button type="submit" class="button">%s</button></p>', esc_html__('Save token', 'sage'));
        echo '</form>';
    } else {
        echo '<form method="post" action="">';
        wp_nonce_field('mh_theme_update', 'mh_updater_nonce');
        printf(
            '<p><button type="submit" class="button button-primary button-hero">%s</button></p>',
            esc_html__('Update theme from GitHub', 'sage')
        );
        printf(
            '<p class="description">%s</p>',
            esc_html(sprintf(
                __('Deploys %1$s@%2$s via %3$s. Anyone can also trigger this by pushing to the branch.', 'sage'),
                $r['owner'].'/'.$r['repo'],
                $r['ref'],
                $r['workflow']
            ))
        );
        echo '</form>';
        echo '<p style="margin-top:1rem"><a class="button" href="'.esc_url($self).'">'.esc_html__('Refresh status', 'sage').'</a></p>';
    }

    echo '</div>';
}

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('mh theme-update', function (): void {
        [$ok, $msg] = updater_dispatch();
        $ok ? \WP_CLI::success($msg) : \WP_CLI::error($msg);
    });
}
