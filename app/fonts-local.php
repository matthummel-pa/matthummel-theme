<?php

/**
 * Self-host Google Fonts. Downloads the active families' woff2 files into
 * wp-content/uploads/mh-fonts/ (server-side, via the WordPress HTTP API),
 * writes a local @font-face stylesheet, and — when enabled — serves that and
 * removes every Google Fonts request from the page.
 *
 * Appearance -> Local Fonts.
 */

namespace App;

function mh_fonts_paths()
{
    $up = wp_upload_dir();
    return [
        'dir' => trailingslashit($up['basedir']) . 'mh-fonts',
        'url' => trailingslashit($up['baseurl']) . 'mh-fonts',
    ];
}

/** name => css2 family slug, for every font currently in use + the defaults + mono. */
function mh_fonts_to_host()
{
    $fonts = function_exists('App\\mh_fonts') ? mh_fonts() : [];
    $names = array_unique(array_filter([
        get_theme_mod('mh_font_heading', 'Geist'),
        get_theme_mod('mh_font_body', 'Inter'),
        get_theme_mod('mh_font_nav', 'Default'),
        get_theme_mod('mh_font_button', 'Default'),
        'Geist', 'Inter',
    ]));
    $slugs = [];
    foreach ($names as $n) {
        if ($n !== 'Default' && ! empty($fonts[$n][0])) {
            $slugs[$n] = $fonts[$n][0];
        }
    }
    $slugs['JetBrains Mono'] = 'JetBrains+Mono:wght@400;500;700';
    return $slugs;
}

/** Admin page. */
add_action('admin_menu', function () {
    add_theme_page(__('Local Fonts', 'matthummel'), __('Local Fonts', 'matthummel'), 'manage_options', 'mh-local-fonts', __NAMESPACE__ . '\\mh_fonts_page');
});

function mh_fonts_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }
    $on    = (bool) get_option('mh_selfhost_on', false);
    $built = (int) get_option('mh_local_fonts_built', 0);
    $count = (int) get_option('mh_local_fonts_count', 0);
    $post  = admin_url('admin-post.php');
    $notice = isset($_GET['mh_fonts']) ? sanitize_key($_GET['mh_fonts']) : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Local Fonts', 'matthummel'); ?></h1>
        <?php if ($notice === 'built') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Fonts downloaded and self-hosted.', 'matthummel'); ?></p></div>
        <?php elseif ($notice === 'builderr') : ?>
            <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Could not download some fonts. Check that the server can reach fonts.googleapis.com.', 'matthummel'); ?></p></div>
        <?php endif; ?>

        <p class="description" style="max-width:640px">
            <?php esc_html_e('Download the fonts your theme uses and serve them from your own server. This removes the external request to Google, improves privacy, and speeds up first paint.', 'matthummel'); ?>
        </p>

        <table class="form-table"><tbody>
            <tr>
                <th><?php esc_html_e('Status', 'matthummel'); ?></th>
                <td>
                    <?php if ($built) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#2f6b4e"></span>
                        <?php printf(esc_html__('%d font files cached, last built %s ago.', 'matthummel'), $count, esc_html(human_time_diff($built))); ?>
                    <?php else : ?>
                        <em><?php esc_html_e('No local fonts yet.', 'matthummel'); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Families', 'matthummel'); ?></th>
                <td><code><?php echo esc_html(implode(', ', array_keys(mh_fonts_to_host()))); ?></code></td>
            </tr>
        </tbody></table>

        <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline-block;margin-right:10px">
            <input type="hidden" name="action" value="mh_build_fonts">
            <?php wp_nonce_field('mh_build_fonts'); ?>
            <button class="button button-primary"><?php echo $built ? esc_html__('Re-download fonts', 'matthummel') : esc_html__('Download fonts now', 'matthummel'); ?></button>
        </form>

        <form method="post" action="<?php echo esc_url($post); ?>" style="display:inline-block">
            <input type="hidden" name="action" value="mh_toggle_fonts">
            <?php wp_nonce_field('mh_toggle_fonts'); ?>
            <label class="mh-switch" style="vertical-align:middle">
                <input type="checkbox" name="mh_selfhost_on" value="1" <?php checked($on); ?> <?php disabled(! $built); ?> onchange="this.form.submit()">
                <?php esc_html_e('Serve fonts locally (and remove Google requests)', 'matthummel'); ?>
            </label>
        </form>
    </div>
    <?php
}

/** Download handler. */
add_action('admin_post_mh_build_fonts', function () {
    if (! current_user_can('manage_options') || ! check_admin_referer('mh_build_fonts')) {
        wp_die('Not allowed');
    }
    $paths = mh_fonts_paths();
    if (! wp_mkdir_p($paths['dir'])) {
        wp_safe_redirect(admin_url('themes.php?page=mh-local-fonts&mh_fonts=builderr'));
        exit;
    }

    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
    $css_all = "/* Self-hosted by the matthummel theme. Do not edit; regenerate from Appearance -> Local Fonts. */\n";
    $count = 0;
    $ok = true;

    foreach (mh_fonts_to_host() as $name => $slug) {
        $r = wp_remote_get('https://fonts.googleapis.com/css2?family=' . $slug . '&display=swap', [
            'timeout' => 20,
            'headers' => ['User-Agent' => $ua],
        ]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) {
            $ok = false;
            continue;
        }
        $css = wp_remote_retrieve_body($r);
        $css = preg_replace_callback('#url\((https://fonts\.gstatic\.com/[^)]+\.woff2)\)#', function ($m) use ($paths, &$count, &$ok) {
            $url  = $m[1];
            $file = md5($url) . '.woff2';
            $dest = $paths['dir'] . '/' . $file;
            if (! file_exists($dest)) {
                $f = wp_remote_get($url, ['timeout' => 25]);
                if (is_wp_error($f) || wp_remote_retrieve_response_code($f) !== 200) {
                    $ok = false;
                    return $m[0];
                }
                file_put_contents($dest, wp_remote_retrieve_body($f));
            }
            $count++;
            return 'url(' . $paths['url'] . '/' . $file . ')';
        }, $css);
        $css_all .= "\n/* {$name} */\n" . $css;
    }

    file_put_contents($paths['dir'] . '/fonts.css', $css_all);
    update_option('mh_local_fonts_url', $paths['url'] . '/fonts.css');
    update_option('mh_local_fonts_built', time());
    update_option('mh_local_fonts_count', $count);

    wp_safe_redirect(admin_url('themes.php?page=mh-local-fonts&mh_fonts=' . ($ok && $count ? 'built' : 'builderr')));
    exit;
});

/** Toggle handler. */
add_action('admin_post_mh_toggle_fonts', function () {
    if (! current_user_can('manage_options') || ! check_admin_referer('mh_toggle_fonts')) {
        wp_die('Not allowed');
    }
    update_option('mh_selfhost_on', isset($_POST['mh_selfhost_on']));
    wp_safe_redirect(admin_url('themes.php?page=mh-local-fonts'));
    exit;
});

/** Front-end: serve local fonts + strip Google when enabled. */
add_action('wp_enqueue_scripts', function () {
    if (! get_option('mh_selfhost_on', false)) {
        return;
    }
    $url = get_option('mh_local_fonts_url', '');
    if ($url) {
        wp_enqueue_style('mh-local-fonts', $url, [], (int) get_option('mh_local_fonts_built', 1));
    }
}, 4);

/** Remove any Google Fonts <link> on the front end when self-hosting. */
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    if (get_option('mh_selfhost_on', false) && strpos((string) $href, 'fonts.googleapis.com') !== false) {
        return '';
    }
    return $tag;
}, 10, 3);

/** Drop the Google preconnect hints too when self-hosting. */
add_filter('wp_resource_hints', function ($hints, $relation) {
    if ($relation === 'preconnect' && get_option('mh_selfhost_on', false)) {
        $hints = array_filter($hints, function ($h) {
            $href = is_array($h) ? ($h['href'] ?? '') : $h;
            return strpos((string) $href, 'fonts.g') === false;
        });
    }
    return $hints;
}, 20, 2);
