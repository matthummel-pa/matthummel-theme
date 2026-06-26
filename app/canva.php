<?php

/**
 * Canva integration for the hero image finder.
 *  - Lightweight (works now): a "Canva" tab to open the Canva editor and import any
 *    exported image by URL into the Media Library.
 *  - Full account connect (scaffolding): Canva Connect API OAuth 2.0 (auth-code + PKCE).
 *    Add a Canva developer app's Client ID/Secret + register the shown redirect URL,
 *    click Connect, then list your designs and export one straight into the hero.
 *
 * Endpoints (admin only): admin-post mh_canva_connect / mh_canva_callback /
 * mh_canva_disconnect; REST mh/v1/canva-designs (GET), mh/v1/canva-export (POST).
 */

namespace App;

const MH_CANVA_AUTHORIZE = 'https://www.canva.com/api/oauth/authorize';
const MH_CANVA_TOKEN     = 'https://api.canva.com/rest/v1/oauth/token';
const MH_CANVA_API       = 'https://api.canva.com/rest/v1';
const MH_CANVA_SCOPES    = 'design:meta:read design:content:read asset:read profile:read';

function mh_canva_redirect_uri()
{
    return admin_url('admin-post.php?action=mh_canva_callback');
}

function mh_canva_connected()
{
    $t = get_option('mh_canva_tokens');
    return is_array($t) && ! empty($t['access_token']);
}

/* ---- Customizer: Canva section (credentials + connection status) ---- */
add_action('customize_register', function ($wp) {
    if (! $wp->get_panel('mh_theme_options')) {
        $wp->add_panel('mh_theme_options', ['title' => __('Theme Options', 'matthummel'), 'priority' => 30]);
    }
    $connected = mh_canva_connected();
    $wp->add_section('mh_canva_section', [
        'title'       => __('Canva', 'matthummel'),
        'panel'       => 'mh_theme_options',
        'description'  => sprintf(
            /* translators: 1: connection status line, 2: redirect URI */
            __('Connect a Canva account to pull designs into the hero. Create an app at canva.com/developers, paste the keys below, and register this redirect URL: %2$s — then click Connect (in the link below). %1$s', 'matthummel'),
            $connected ? __('Status: CONNECTED.', 'matthummel') : __('Status: not connected.', 'matthummel'),
            mh_canva_redirect_uri()
        ),
    ]);
    $wp->add_setting('mh_canva_client_id', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_canva_client_id', ['label' => __('Canva Client ID', 'matthummel'), 'section' => 'mh_canva_section', 'type' => 'text']);
    $wp->add_setting('mh_canva_client_secret', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_canva_client_secret', ['label' => __('Canva Client Secret', 'matthummel'), 'section' => 'mh_canva_section', 'type' => 'password']);

    // Connect / Disconnect links (rendered as a read-only control).
    if (class_exists('WP_Customize_Control')) {
        $connect    = wp_nonce_url(admin_url('admin-post.php?action=mh_canva_connect'), 'mh_canva_connect');
        $disconnect = wp_nonce_url(admin_url('admin-post.php?action=mh_canva_disconnect'), 'mh_canva_disconnect');
        $html = $connected
            ? '<p style="color:#1a7f37;font-weight:600;">' . esc_html__('Canva is connected.', 'matthummel') . '</p><p><a class="button" href="' . esc_url($disconnect) . '">' . esc_html__('Disconnect Canva', 'matthummel') . '</a></p>'
            : '<p><a class="button button-primary" href="' . esc_url($connect) . '" target="_top">' . esc_html__('Connect Canva', 'matthummel') . '</a></p><p style="font-size:11px;color:#646970;">' . esc_html__('Save your Client ID/Secret first, then click Connect.', 'matthummel') . '</p>';
        $wp->add_setting('mh_canva_status', ['default' => '', 'sanitize_callback' => '__return_empty_string']);

        $ctrl = new class($wp, 'mh_canva_status', [
            'section' => 'mh_canva_section',
            'label'   => __('Connection', 'matthummel'),
            'settings' => 'mh_canva_status',
        ]) extends \WP_Customize_Control {
            public $mh_html = '';
            public function render_content()
            {
                echo '<span class="customize-control-title">' . esc_html($this->label) . '</span>';
                echo wp_kses_post($this->mh_html);
            }
        };
        $ctrl->mh_html = $html;
        $wp->add_control($ctrl);
    }
}, 27);

/* ---- OAuth: connect ---- */
add_action('admin_post_mh_canva_connect', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_canva_connect')) {
        wp_die('Not allowed');
    }
    $cid = trim((string) get_theme_mod('mh_canva_client_id', ''));
    if ($cid === '') {
        wp_die('Add your Canva Client ID in Customize → Theme Options → Canva first.');
    }
    $verifier  = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $state     = wp_generate_password(24, false);
    set_transient('mh_canva_oauth_' . $state, $verifier, 600);

    $url = add_query_arg([
        'response_type'         => 'code',
        'client_id'             => $cid,
        'redirect_uri'          => mh_canva_redirect_uri(),
        'scope'                 => MH_CANVA_SCOPES,
        'code_challenge'        => $challenge,
        'code_challenge_method' => 'S256',
        'state'                 => $state,
    ], MH_CANVA_AUTHORIZE);
    wp_redirect($url);
    exit;
});

/* ---- OAuth: callback ---- */
add_action('admin_post_mh_canva_callback', function () {
    if (! current_user_can('edit_theme_options')) {
        wp_die('Not allowed');
    }
    $code  = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
    $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
    $back  = admin_url('customize.php?autofocus[section]=mh_canva_section');
    if (! $code || ! $state) {
        wp_safe_redirect(add_query_arg('mh_canva', 'error', $back));
        exit;
    }
    $verifier = get_transient('mh_canva_oauth_' . $state);
    delete_transient('mh_canva_oauth_' . $state);
    if (! $verifier) {
        wp_safe_redirect(add_query_arg('mh_canva', 'state', $back));
        exit;
    }
    $cid = trim((string) get_theme_mod('mh_canva_client_id', ''));
    $sec = trim((string) get_theme_mod('mh_canva_client_secret', ''));
    $resp = wp_remote_post(MH_CANVA_TOKEN, [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Basic ' . base64_encode($cid . ':' . $sec), 'Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'code_verifier' => $verifier,
            'redirect_uri'  => mh_canva_redirect_uri(),
        ],
    ]);
    if (is_wp_error($resp)) {
        wp_safe_redirect(add_query_arg('mh_canva', 'token', $back));
        exit;
    }
    $j = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($j['access_token'])) {
        wp_safe_redirect(add_query_arg('mh_canva', 'denied', $back));
        exit;
    }
    $j['obtained_at'] = time();
    update_option('mh_canva_tokens', $j, false);
    wp_safe_redirect(add_query_arg('mh_canva', 'ok', $back));
    exit;
});

/* ---- OAuth: disconnect ---- */
add_action('admin_post_mh_canva_disconnect', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_canva_disconnect')) {
        wp_die('Not allowed');
    }
    delete_option('mh_canva_tokens');
    wp_safe_redirect(admin_url('customize.php?autofocus[section]=mh_canva_section'));
    exit;
});

/** Return a valid Canva access token, refreshing if needed. */
function mh_canva_token()
{
    $t = get_option('mh_canva_tokens');
    if (! is_array($t) || empty($t['access_token'])) {
        return '';
    }
    $expires = (int) ($t['obtained_at'] ?? 0) + (int) ($t['expires_in'] ?? 3600) - 120;
    if (time() < $expires) {
        return $t['access_token'];
    }
    if (empty($t['refresh_token'])) {
        return $t['access_token'];
    }
    $cid = trim((string) get_theme_mod('mh_canva_client_id', ''));
    $sec = trim((string) get_theme_mod('mh_canva_client_secret', ''));
    $resp = wp_remote_post(MH_CANVA_TOKEN, [
        'timeout' => 20,
        'headers' => ['Authorization' => 'Basic ' . base64_encode($cid . ':' . $sec), 'Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => ['grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']],
    ]);
    if (is_wp_error($resp)) {
        return $t['access_token'];
    }
    $j = json_decode(wp_remote_retrieve_body($resp), true);
    if (! empty($j['access_token'])) {
        $j['obtained_at'] = time();
        if (empty($j['refresh_token'])) {
            $j['refresh_token'] = $t['refresh_token'];
        }
        update_option('mh_canva_tokens', $j, false);
        return $j['access_token'];
    }
    return $t['access_token'];
}

/* ---- REST: list designs + export ---- */
add_action('rest_api_init', function () {
    $can = function () {
        return current_user_can('edit_theme_options');
    };
    register_rest_route('mh/v1', '/canva-designs', ['methods' => 'GET', 'permission_callback' => $can, 'callback' => __NAMESPACE__ . '\\mh_canva_designs_rest']);
    register_rest_route('mh/v1', '/canva-export', ['methods' => 'POST', 'permission_callback' => $can, 'callback' => __NAMESPACE__ . '\\mh_canva_export_rest']);
});

function mh_canva_designs_rest()
{
    $tok = mh_canva_token();
    if (! $tok) {
        return new \WP_REST_Response(['error' => 'not_connected'], 200);
    }
    $r = wp_remote_get(MH_CANVA_API . '/designs?limit=24', ['timeout' => 20, 'headers' => ['Authorization' => 'Bearer ' . $tok]]);
    if (is_wp_error($r)) {
        return new \WP_REST_Response(['error' => $r->get_error_message()], 200);
    }
    $j = json_decode(wp_remote_retrieve_body($r), true);
    $out = [];
    foreach (($j['items'] ?? []) as $d) {
        $out[] = ['id' => $d['id'] ?? '', 'title' => $d['title'] ?? __('Untitled', 'matthummel'), 'thumb' => $d['thumbnail']['url'] ?? ''];
    }
    return new \WP_REST_Response($out, 200);
}

function mh_canva_export_rest($req)
{
    $tok = mh_canva_token();
    if (! $tok) {
        return new \WP_REST_Response(['error' => 'not_connected'], 200);
    }
    $design = sanitize_text_field((string) $req->get_param('design_id'));
    if ($design === '') {
        return new \WP_REST_Response(['error' => 'no_design'], 200);
    }
    $start = wp_remote_post(MH_CANVA_API . '/exports', [
        'timeout' => 25,
        'headers' => ['Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json'],
        'body'    => wp_json_encode(['design_id' => $design, 'format' => ['type' => 'png']]),
    ]);
    if (is_wp_error($start)) {
        return new \WP_REST_Response(['error' => $start->get_error_message()], 200);
    }
    $j  = json_decode(wp_remote_retrieve_body($start), true);
    $jid = $j['job']['id'] ?? '';
    if (! $jid) {
        return new \WP_REST_Response(['error' => 'export_failed', 'detail' => $j], 200);
    }
    // Poll up to ~20s.
    for ($i = 0; $i < 10; $i++) {
        $p = wp_remote_get(MH_CANVA_API . '/exports/' . rawurlencode($jid), ['timeout' => 20, 'headers' => ['Authorization' => 'Bearer ' . $tok]]);
        if (is_wp_error($p)) {
            break;
        }
        $pj = json_decode(wp_remote_retrieve_body($p), true);
        $status = $pj['job']['status'] ?? '';
        if ($status === 'success') {
            $url = $pj['job']['urls'][0] ?? '';
            return new \WP_REST_Response(['url' => $url], 200);
        }
        if ($status === 'failed') {
            return new \WP_REST_Response(['error' => 'export_failed'], 200);
        }
        usleep(2000000);
    }
    return new \WP_REST_Response(['error' => 'timeout'], 200);
}

/* ---- Pass Canva state to the finder JS ---- */
add_action('customize_controls_enqueue_scripts', function () {
    if (wp_script_is('mh-img-finder', 'enqueued') || wp_script_is('mh-img-finder', 'registered')) {
        wp_localize_script('mh-img-finder', 'mhCanva', [
            'connected' => mh_canva_connected(),
            'editor'    => 'https://www.canva.com/create',
        ]);
    }
}, 20);
