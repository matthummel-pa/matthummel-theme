<?php

/**
 * "Connect with GitHub" using the OAuth Device Flow (no redirect URL, no client
 * secret). The user registers a GitHub OAuth App once (Device Flow enabled),
 * pastes the public Client ID, clicks Connect, authorizes on github.com, and the
 * resulting token is stored in mh_gh_token (used by the live repo data engine to
 * raise the API rate limit). Token is requested with NO scope = public read only.
 */

namespace App;

/** Start the device flow: ask GitHub for a user code. */
add_action('wp_ajax_mh_gh_device_start', function () {
    check_ajax_referer('mh_gh_connect', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Not allowed.', 'matthummel')]);
    }
    $client_id = trim((string) get_theme_mod('mh_gh_client_id', ''));
    if ($client_id === '') {
        wp_send_json_error(['message' => __('Enter your GitHub OAuth App Client ID and save first.', 'matthummel')]);
    }

    $r = wp_remote_post('https://github.com/login/device/code', [
        'timeout' => 15,
        'headers' => ['Accept' => 'application/json'],
        'body'    => ['client_id' => $client_id, 'scope' => ''],
    ]);
    if (is_wp_error($r)) {
        wp_send_json_error(['message' => $r->get_error_message()]);
    }
    $j = json_decode(wp_remote_retrieve_body($r), true);
    if (empty($j['device_code']) || empty($j['user_code'])) {
        $msg = $j['error_description'] ?? __('GitHub did not return a device code. Make sure Device Flow is enabled on your OAuth App.', 'matthummel');
        wp_send_json_error(['message' => $msg]);
    }

    // Keep the device_code server-side; the browser never needs it.
    set_transient('mh_gh_device_' . get_current_user_id(), [
        'device_code' => $j['device_code'],
        'client_id'   => $client_id,
        'interval'    => (int) ($j['interval'] ?? 5),
    ], (int) ($j['expires_in'] ?? 900));

    wp_send_json_success([
        'user_code'        => $j['user_code'],
        'verification_uri' => $j['verification_uri'] ?? 'https://github.com/login/device',
        'interval'         => (int) ($j['interval'] ?? 5),
        'expires_in'       => (int) ($j['expires_in'] ?? 900),
    ]);
});

/** Poll for the token after the user authorizes on GitHub. */
add_action('wp_ajax_mh_gh_device_poll', function () {
    check_ajax_referer('mh_gh_connect', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Not allowed.', 'matthummel')]);
    }
    $pending = get_transient('mh_gh_device_' . get_current_user_id());
    if (! $pending) {
        wp_send_json_error(['message' => __('Connection expired. Start again.', 'matthummel')]);
    }

    $r = wp_remote_post('https://github.com/login/oauth/access_token', [
        'timeout' => 15,
        'headers' => ['Accept' => 'application/json'],
        'body'    => [
            'client_id'   => $pending['client_id'],
            'device_code' => $pending['device_code'],
            'grant_type'  => 'urn:ietf:params:oauth:grant-type:device_code',
        ],
    ]);
    if (is_wp_error($r)) {
        wp_send_json_error(['message' => $r->get_error_message()]);
    }
    $j = json_decode(wp_remote_retrieve_body($r), true);

    if (! empty($j['access_token'])) {
        set_theme_mod('mh_gh_token', sanitize_text_field($j['access_token']));
        delete_transient('mh_gh_device_' . get_current_user_id());
        wp_send_json_success(['status' => 'connected']);
    }

    $err = $j['error'] ?? 'authorization_pending';
    if ($err === 'authorization_pending') {
        wp_send_json_success(['status' => 'pending']);
    }
    if ($err === 'slow_down') {
        wp_send_json_success(['status' => 'pending', 'interval' => (int) ($j['interval'] ?? 10)]);
    }
    // expired_token, access_denied, etc.
    delete_transient('mh_gh_device_' . get_current_user_id());
    wp_send_json_error(['message' => $j['error_description'] ?? $err]);
});

/** Disconnect: clear the stored token. */
add_action('wp_ajax_mh_gh_disconnect', function () {
    check_ajax_referer('mh_gh_connect', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Not allowed.', 'matthummel')]);
    }
    set_theme_mod('mh_gh_token', '');
    delete_transient('mh_gh_device_' . get_current_user_id());
    wp_send_json_success(['status' => 'disconnected']);
});

/** Renders the connection widget (called from the admin Projects tab). */
function mh_gh_connect_widget()
{
    $connected = trim((string) get_theme_mod('mh_gh_token', '')) !== '';
    $client_id = trim((string) get_theme_mod('mh_gh_client_id', ''));
    $nonce     = wp_create_nonce('mh_gh_connect');
    $ajax      = admin_url('admin-ajax.php');
    ?>
    <div class="mh-gh-connect" data-nonce="<?php echo esc_attr($nonce); ?>" data-ajax="<?php echo esc_url($ajax); ?>">
        <?php if ($connected) : ?>
            <p class="mh-gh-state is-on"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Connected to GitHub. The live repo data is using your authenticated rate limit.', 'matthummel'); ?></p>
            <button type="button" class="button mh-gh-disconnect"><?php esc_html_e('Disconnect', 'matthummel'); ?></button>
        <?php else : ?>
            <p class="description" style="margin-bottom:8px">
                <?php esc_html_e('One-time setup: on GitHub, go to Settings → Developer settings → OAuth Apps → New, tick "Enable Device Flow", then paste the Client ID above and save. Then click Connect.', 'matthummel'); ?>
            </p>
            <button type="button" class="button button-primary mh-gh-start" <?php disabled($client_id === ''); ?>><span class="dashicons dashicons-github" style="vertical-align:text-bottom"></span> <?php esc_html_e('Connect with GitHub', 'matthummel'); ?></button>
            <?php if ($client_id === '') : ?><span class="description" style="margin-left:8px"><?php esc_html_e('Enter a Client ID above and save first.', 'matthummel'); ?></span><?php endif; ?>
            <div class="mh-gh-step" style="display:none;margin-top:12px;padding:14px;border:1px solid #dcdcde;border-radius:8px;background:#fff">
                <p style="margin:0 0 6px"><?php esc_html_e('1. Your one-time code:', 'matthummel'); ?> <strong class="mh-gh-code" style="font-size:20px;letter-spacing:2px"></strong></p>
                <p style="margin:0 0 6px"><?php esc_html_e('2. Authorize at', 'matthummel'); ?> <a class="mh-gh-uri" href="#" target="_blank" rel="noopener">github.com/login/device</a></p>
                <p class="mh-gh-status" style="margin:0;color:#646970"><?php esc_html_e('Waiting for authorization…', 'matthummel'); ?></p>
            </div>
        <?php endif; ?>
        <p class="mh-gh-error" style="color:#b32d2e;margin-top:8px;display:none"></p>
    </div>
    <script>
    (function(){
      var box = document.currentScript.previousElementSibling;
      if(!box || !box.classList.contains('mh-gh-connect')) box = document.querySelector('.mh-gh-connect');
      if(!box) return;
      var ajax = box.getAttribute('data-ajax'), nonce = box.getAttribute('data-nonce');
      function post(action, extra){
        var d = new FormData(); d.append('action', action); d.append('nonce', nonce);
        if(extra){ Object.keys(extra).forEach(function(k){ d.append(k, extra[k]); }); }
        return fetch(ajax, {method:'POST', credentials:'same-origin', body:d}).then(function(r){return r.json();});
      }
      function err(m){ var e=box.querySelector('.mh-gh-error'); if(e){ e.textContent=m; e.style.display='block'; } }
      var start = box.querySelector('.mh-gh-start');
      if(start) start.addEventListener('click', function(){
        err(''); start.disabled = true; start.textContent = 'Starting…';
        post('mh_gh_device_start').then(function(res){
          if(!res.success){ err(res.data && res.data.message || 'Failed'); start.disabled=false; start.textContent='Connect with GitHub'; return; }
          var step = box.querySelector('.mh-gh-step'); step.style.display='block';
          box.querySelector('.mh-gh-code').textContent = res.data.user_code;
          var uri = box.querySelector('.mh-gh-uri'); uri.href = res.data.verification_uri; uri.textContent = res.data.verification_uri;
          window.open(res.data.verification_uri, '_blank');
          var interval = (res.data.interval || 5) * 1000;
          var poll = function(){
            post('mh_gh_device_poll').then(function(p){
              if(p.success && p.data.status === 'connected'){ box.querySelector('.mh-gh-status').textContent='Connected! Reloading…'; setTimeout(function(){ location.reload(); }, 800); return; }
              if(p.success && p.data.status === 'pending'){ if(p.data.interval) interval = p.data.interval*1000; setTimeout(poll, interval); return; }
              err(p.data && p.data.message || 'Authorization failed'); start.disabled=false; start.textContent='Connect with GitHub'; step.style.display='none';
            });
          };
          setTimeout(poll, interval);
        });
      });
      var dc = box.querySelector('.mh-gh-disconnect');
      if(dc) dc.addEventListener('click', function(){ dc.disabled=true; post('mh_gh_disconnect').then(function(){ location.reload(); }); });
    })();
    </script>
    <?php
}
