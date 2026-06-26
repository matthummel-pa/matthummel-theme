<?php

/**
 * In-Customizer image finder for the hero images.
 *  - Search open sources: Openverse (no key), Unsplash + Pexels (optional keys).
 *  - Generate AI images with a free, no-key endpoint (Pollinations).
 *  - Whatever is picked/generated is downloaded into the Media Library (self-hosted,
 *    not hot-linked) and set as the linked hero image setting.
 *
 * REST: mh/v1/img-search (GET), mh/v1/img-import (POST) — both admin-only.
 */

namespace App;

/* ---- API key settings + the custom finder controls (added to the Hero section) ---- */
add_action('customize_register', function ($wp) {
    if (! $wp->get_section('mh_hero_section')) {
        return; // hero.php registers the section; bail if missing.
    }

    // Optional keys for the keyed sources.
    $wp->add_setting('mh_unsplash_key', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_unsplash_key', ['label' => __('Unsplash API key (optional)', 'matthummel'), 'description' => __('For the Unsplash search tab. Free key from unsplash.com/developers.', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'text']);
    $wp->add_setting('mh_pexels_key', ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
    $wp->add_control('mh_pexels_key', ['label' => __('Pexels API key (optional)', 'matthummel'), 'section' => 'mh_hero_section', 'type' => 'text']);

    // Custom control type (defined here so WP_Customize_Control is available).
    if (! class_exists(__NAMESPACE__ . '\\Mh_Image_Finder_Control')) {
        class Mh_Image_Finder_Control extends \WP_Customize_Control
        {
            public $type = 'mh-image-finder';

            public function render_content()
            {
                $sid = isset($this->settings['default']) ? $this->settings['default']->id : '';
                ?>
                <div class="mh-imgfinder" data-setting="<?php echo esc_attr($sid); ?>">
                    <?php if ($this->label) : ?>
                        <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                    <?php endif; ?>
                    <div class="mh-if-tabs">
                        <button type="button" class="mh-if-tab is-active" data-src="openverse"><?php esc_html_e('Openverse', 'matthummel'); ?></button>
                        <button type="button" class="mh-if-tab" data-src="unsplash"><?php esc_html_e('Unsplash', 'matthummel'); ?></button>
                        <button type="button" class="mh-if-tab" data-src="pexels"><?php esc_html_e('Pexels', 'matthummel'); ?></button>
                        <button type="button" class="mh-if-tab" data-src="ai"><?php esc_html_e('AI', 'matthummel'); ?></button>
                        <button type="button" class="mh-if-tab" data-src="canva"><?php esc_html_e('Canva', 'matthummel'); ?></button>
                    </div>
                    <div class="mh-if-searchrow">
                        <input type="text" class="mh-if-q" placeholder="<?php esc_attr_e('Search images…', 'matthummel'); ?>">
                        <button type="button" class="button mh-if-go"><?php esc_html_e('Search', 'matthummel'); ?></button>
                    </div>
                    <p class="mh-if-note" aria-live="polite"></p>
                    <div class="mh-if-results"></div>
                </div>
                <?php
            }
        }
    }

    foreach ([
        'mh_hero_img'  => __('Find a side image (search / AI)', 'matthummel'),
        'mh_hero_bg'   => __('Find a background image (search / AI)', 'matthummel'),
        'mh_hero_img2' => __('Find a 2nd image (search / AI)', 'matthummel'),
    ] as $sid => $label) {
        $cls = __NAMESPACE__ . '\\Mh_Image_Finder_Control';
        $wp->add_control(new $cls($wp, $sid . '_finder', [
            'label'    => $label,
            'section'  => 'mh_hero_section',
            'settings' => $sid,
        ]));
    }
}, 26);

/* ---- Customizer assets ---- */
add_action('customize_controls_enqueue_scripts', function () {
    $path = 'resources/js/hero-image-finder.js';
    wp_enqueue_script(
        'mh-img-finder',
        get_theme_file_uri($path),
        ['customize-controls', 'jquery'],
        file_exists(get_theme_file_path($path)) ? filemtime(get_theme_file_path($path)) : '1',
        true
    );
    wp_localize_script('mh-img-finder', 'mhIF', [
        'rest'        => esc_url_raw(rest_url()),
        'nonce'       => wp_create_nonce('wp_rest'),
        'hasUnsplash' => trim((string) get_theme_mod('mh_unsplash_key', '')) !== '',
        'hasPexels'   => trim((string) get_theme_mod('mh_pexels_key', '')) !== '',
    ]);
});

add_action('customize_controls_print_styles', function () {
    echo '<style id="mh-imgfinder-css">'
        . '.mh-if-tabs{display:flex;gap:4px;margin:8px 0 6px;}'
        . '.mh-if-tab{flex:1;padding:5px 4px;font-size:11px;line-height:1.2;border:1px solid #c3c4c7;background:#f6f7f7;color:#1d2327;cursor:pointer;border-radius:3px;}'
        . '.mh-if-tab.is-active{background:#2271b1;color:#fff;border-color:#2271b1;}'
        . '.mh-if-searchrow{display:flex;gap:4px;}'
        . '.mh-if-q{flex:1;min-width:0;}'
        . '.mh-if-note{font-size:11px;color:#50575e;margin:6px 0;min-height:14px;}'
        . '.mh-if-results{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:6px;max-height:330px;overflow:auto;}'
        . '.mh-if-tile{height:62px;border:0;border-radius:4px;background-size:cover;background-position:center;cursor:pointer;padding:0;}'
        . '.mh-if-tile.is-loading{opacity:.45;}'
        . '.mh-if-aiwrap{margin-top:4px;}'
        . '.mh-if-aiimg{width:100%;border-radius:6px;display:block;background:#f0f0f1;min-height:80px;}'
        . '.mh-if-use{margin-top:6px;width:100%;}'
        . '</style>';
});

/* ---- REST: search + import ---- */
add_action('rest_api_init', function () {
    $can = function () {
        return current_user_can('edit_theme_options');
    };
    register_rest_route('mh/v1', '/img-search', ['methods' => 'GET', 'permission_callback' => $can, 'callback' => __NAMESPACE__ . '\\mh_img_search_rest']);
    register_rest_route('mh/v1', '/img-import', ['methods' => 'POST', 'permission_callback' => $can, 'callback' => __NAMESPACE__ . '\\mh_img_import_rest']);
});

function mh_img_search_rest($req)
{
    $source = sanitize_key((string) $req->get_param('source'));
    $q      = sanitize_text_field((string) $req->get_param('q'));
    if ($q === '') {
        return new \WP_REST_Response([], 200);
    }
    $out = [];

    if ($source === 'unsplash') {
        $key = trim((string) get_theme_mod('mh_unsplash_key', ''));
        if ($key === '') {
            return new \WP_REST_Response(['error' => 'no_key'], 200);
        }
        $r = wp_remote_get('https://api.unsplash.com/search/photos?per_page=24&query=' . rawurlencode($q), ['timeout' => 15, 'headers' => ['Authorization' => 'Client-ID ' . $key]]);
        if (! is_wp_error($r)) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            foreach (($j['results'] ?? []) as $p) {
                $out[] = ['thumb' => $p['urls']['small'] ?? '', 'full' => $p['urls']['regular'] ?? ($p['urls']['full'] ?? ''), 'credit' => $p['user']['name'] ?? 'Unsplash', 'link' => $p['links']['html'] ?? ''];
            }
        }
    } elseif ($source === 'pexels') {
        $key = trim((string) get_theme_mod('mh_pexels_key', ''));
        if ($key === '') {
            return new \WP_REST_Response(['error' => 'no_key'], 200);
        }
        $r = wp_remote_get('https://api.pexels.com/v1/search?per_page=24&query=' . rawurlencode($q), ['timeout' => 15, 'headers' => ['Authorization' => $key]]);
        if (! is_wp_error($r)) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            foreach (($j['photos'] ?? []) as $p) {
                $out[] = ['thumb' => $p['src']['medium'] ?? '', 'full' => $p['src']['large2x'] ?? ($p['src']['large'] ?? ''), 'credit' => $p['photographer'] ?? 'Pexels', 'link' => $p['url'] ?? ''];
            }
        }
    } else { // openverse (no key)
        $r = wp_remote_get('https://api.openverse.org/v1/images/?page_size=24&mature=false&q=' . rawurlencode($q), ['timeout' => 15, 'headers' => ['User-Agent' => 'matthummel-theme/1.0']]);
        if (! is_wp_error($r)) {
            $j = json_decode(wp_remote_retrieve_body($r), true);
            foreach (($j['results'] ?? []) as $p) {
                $out[] = ['thumb' => $p['thumbnail'] ?? ($p['url'] ?? ''), 'full' => $p['url'] ?? '', 'credit' => trim(($p['creator'] ?? '') . ' · ' . strtoupper((string) ($p['license'] ?? ''))), 'link' => $p['foreign_landing_url'] ?? ''];
            }
        }
    }

    return new \WP_REST_Response($out, 200);
}

/** Download any remote image into the Media Library; returns its local URL. */
function mh_img_import_rest($req)
{
    $url = esc_url_raw((string) $req->get_param('url'));
    if ($url === '') {
        return new \WP_REST_Response(['error' => 'No URL'], 200);
    }
    $resp = wp_remote_get($url, ['timeout' => 40]);
    if (is_wp_error($resp)) {
        return new \WP_REST_Response(['error' => $resp->get_error_message()], 200);
    }
    if ((int) wp_remote_retrieve_response_code($resp) !== 200) {
        return new \WP_REST_Response(['error' => 'HTTP ' . wp_remote_retrieve_response_code($resp)], 200);
    }
    $body  = wp_remote_retrieve_body($resp);
    if ($body === '') {
        return new \WP_REST_Response(['error' => 'Empty response'], 200);
    }
    $ctype = (string) wp_remote_retrieve_header($resp, 'content-type');
    $ext   = strpos($ctype, 'png') !== false ? 'png' : (strpos($ctype, 'webp') !== false ? 'webp' : (strpos($ctype, 'gif') !== false ? 'gif' : 'jpg'));
    $name  = 'mh-hero-' . wp_generate_password(8, false) . '.' . $ext;

    $up = wp_upload_bits($name, null, $body);
    if (! empty($up['error'])) {
        return new \WP_REST_Response(['error' => $up['error']], 200);
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $filetype = wp_check_filetype($up['file']);
    $aid = wp_insert_attachment([
        'post_mime_type' => $filetype['type'] ?: ('image/' . $ext),
        'post_title'     => $name,
        'post_status'    => 'inherit',
    ], $up['file']);
    if (is_wp_error($aid) || ! $aid) {
        return new \WP_REST_Response(['error' => 'Attachment failed'], 200);
    }
    wp_update_attachment_metadata($aid, wp_generate_attachment_metadata($aid, $up['file']));

    return new \WP_REST_Response(['url' => wp_get_attachment_image_url($aid, 'full'), 'id' => $aid], 200);
}
