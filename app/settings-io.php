<?php

/**
 * Theme Tools: Style Kit presets + Import / Export / Reset of all theme settings.
 * Appearance -> Theme Tools. All actions are nonce-protected and capability-gated.
 */

namespace App;

/** Every theme mod that belongs to this theme (mh_* keys). */
function mh_owned_mods()
{
    $mods = get_theme_mods() ?: [];
    $out  = [];
    foreach ($mods as $k => $v) {
        if (strpos($k, 'mh_') === 0) {
            $out[$k] = $v;
        }
    }
    return $out;
}

/** One-click design presets (palette + fonts + radius). */
function mh_style_kits()
{
    return apply_filters('matthummel/style_kits', [
        'editorial' => [
            'label' => __('Editorial', 'matthummel'),
            'desc'  => __('Clean paper + green. The theme default.', 'matthummel'),
            'mods'  => [
                'mh_color_action' => '#2f6b4e', 'mh_color_paper' => '#fbfaf7',
                'mh_color_ink' => '#17191e', 'mh_color_body' => '#2b2f36',
                'mh_font_heading' => 'Geist', 'mh_font_body' => 'Inter',
                'mh_btn_radius' => '8', 'mh_card_radius' => '16',
            ],
        ],
        'sage_classic' => [
            'label' => __('Sage Classic', 'matthummel'),
            'desc'  => __('Khaki + serif — matches matthummel.com.', 'matthummel'),
            'mods'  => [
                'mh_color_action' => '#4e6b4a', 'mh_color_paper' => '#dccfa6',
                'mh_color_ink' => '#2a303b', 'mh_color_body' => '#3a3d44',
                'mh_font_heading' => 'Fraunces', 'mh_font_body' => 'Inter',
                'mh_btn_radius' => '4', 'mh_card_radius' => '14',
            ],
        ],
        'warm_sand' => [
            'label' => __('Warm Sand', 'matthummel'),
            'desc'  => __('Terracotta + warm neutrals.', 'matthummel'),
            'mods'  => [
                'mh_color_action' => '#b4612f', 'mh_color_paper' => '#faf6ef',
                'mh_color_ink' => '#2a2422', 'mh_color_body' => '#44403c',
                'mh_font_heading' => 'Fraunces', 'mh_font_body' => 'Inter',
                'mh_btn_radius' => '12', 'mh_card_radius' => '18',
            ],
        ],
        'midnight' => [
            'label' => __('Midnight', 'matthummel'),
            'desc'  => __('Dark canvas, soft blue accent.', 'matthummel'),
            'mods'  => [
                'mh_color_action' => '#6ea8fe', 'mh_color_paper' => '#0f1420',
                'mh_color_ink' => '#f5f7fb', 'mh_color_body' => '#c7ccd6',
                'mh_font_heading' => 'Space Grotesk', 'mh_font_body' => 'Inter',
                'mh_btn_radius' => '8', 'mh_card_radius' => '16',
            ],
        ],
        'mono_slate' => [
            'label' => __('Mono Slate', 'matthummel'),
            'desc'  => __('Sharp, near-black, minimal.', 'matthummel'),
            'mods'  => [
                'mh_color_action' => '#111827', 'mh_color_paper' => '#f7f7f8',
                'mh_color_ink' => '#0b0c0e', 'mh_color_body' => '#3a3d44',
                'mh_font_heading' => 'Inter Tight', 'mh_font_body' => 'Inter',
                'mh_btn_radius' => '4', 'mh_card_radius' => '8',
            ],
        ],
    ]);
}

/** Admin page registration. */
add_action('admin_menu', function () {
    add_theme_page(
        __('Theme Tools', 'matthummel'),
        __('Theme Tools', 'matthummel'),
        'edit_theme_options',
        'mh-theme-tools',
        __NAMESPACE__ . '\\mh_tools_render'
    );
});

function mh_tools_render()
{
    if (! current_user_can('edit_theme_options')) {
        return;
    }
    $notice = isset($_GET['mh_done']) ? sanitize_key($_GET['mh_done']) : '';
    $post   = admin_url('admin-post.php');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Theme Tools', 'matthummel'); ?></h1>

        <?php if ($notice === 'kit') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Style kit applied.', 'matthummel'); ?></p></div>
        <?php elseif ($notice === 'import') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings imported.', 'matthummel'); ?></p></div>
        <?php elseif ($notice === 'reset') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Theme settings reset to defaults.', 'matthummel'); ?></p></div>
        <?php elseif ($notice === 'importerr') : ?>
            <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Could not import that file. Make sure it is a JSON export from this theme.', 'matthummel'); ?></p></div>
        <?php endif; ?>

        <h2 style="margin-top:24px"><?php esc_html_e('Style Kits', 'matthummel'); ?></h2>
        <p class="description"><?php esc_html_e('One click applies a full palette + font + radius set. You can still fine-tune everything afterward in the Customizer.', 'matthummel'); ?></p>
        <div style="display:flex;flex-wrap:wrap;gap:14px;margin:16px 0 32px">
            <?php foreach (mh_style_kits() as $id => $kit) :
                $m = $kit['mods']; ?>
                <form method="post" action="<?php echo esc_url($post); ?>" style="width:230px;border:1px solid #dcdcde;border-radius:10px;padding:14px;background:#fff">
                    <div style="display:flex;gap:6px;margin-bottom:10px">
                        <?php foreach (['mh_color_paper', 'mh_color_action', 'mh_color_ink', 'mh_color_body'] as $ck) : ?>
                            <span style="width:26px;height:26px;border-radius:50%;border:1px solid rgba(0,0,0,.1);background:<?php echo esc_attr($m[$ck]); ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <strong style="font-size:14px"><?php echo esc_html($kit['label']); ?></strong>
                    <p style="margin:4px 0 10px;color:#646970;font-size:12px"><?php echo esc_html($kit['desc']); ?></p>
                    <p style="margin:0 0 12px;font-size:12px;color:#646970"><?php echo esc_html($m['mh_font_heading'] . ' / ' . $m['mh_font_body']); ?></p>
                    <input type="hidden" name="action" value="mh_apply_kit">
                    <input type="hidden" name="kit" value="<?php echo esc_attr($id); ?>">
                    <?php wp_nonce_field('mh_apply_kit'); ?>
                    <button class="button button-primary" style="width:100%"><?php esc_html_e('Apply', 'matthummel'); ?></button>
                </form>
            <?php endforeach; ?>
        </div>

        <hr>

        <h2 style="margin-top:24px"><?php esc_html_e('Export settings', 'matthummel'); ?></h2>
        <p class="description"><?php esc_html_e('Download all theme settings as a JSON file you can re-import on another site.', 'matthummel'); ?></p>
        <form method="post" action="<?php echo esc_url($post); ?>" style="margin:12px 0 28px">
            <input type="hidden" name="action" value="mh_export_settings">
            <?php wp_nonce_field('mh_export_settings'); ?>
            <button class="button"><?php esc_html_e('Download export (.json)', 'matthummel'); ?></button>
        </form>

        <h2><?php esc_html_e('Import settings', 'matthummel'); ?></h2>
        <p class="description"><?php esc_html_e('Upload a JSON export to apply those settings here.', 'matthummel'); ?></p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($post); ?>" style="margin:12px 0 28px">
            <input type="file" name="mh_import_file" accept="application/json,.json" required>
            <input type="hidden" name="action" value="mh_import_settings">
            <?php wp_nonce_field('mh_import_settings'); ?>
            <button class="button button-primary"><?php esc_html_e('Import file', 'matthummel'); ?></button>
        </form>

        <hr>

        <h2 style="color:#b32d2e"><?php esc_html_e('Reset', 'matthummel'); ?></h2>
        <p class="description"><?php esc_html_e('Remove all of this theme\'s settings and return to defaults. This cannot be undone, so export first.', 'matthummel'); ?></p>
        <form method="post" action="<?php echo esc_url($post); ?>" style="margin:12px 0" onsubmit="return confirm('<?php echo esc_js(__('Reset all theme settings to defaults?', 'matthummel')); ?>');">
            <input type="hidden" name="action" value="mh_reset_settings">
            <?php wp_nonce_field('mh_reset_settings'); ?>
            <button class="button" style="border-color:#b32d2e;color:#b32d2e"><?php esc_html_e('Reset theme settings', 'matthummel'); ?></button>
        </form>
    </div>
    <?php
}

/** Apply a style kit. */
add_action('admin_post_mh_apply_kit', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_apply_kit')) {
        wp_die('Not allowed');
    }
    $kits = mh_style_kits();
    $id   = isset($_POST['kit']) ? sanitize_key($_POST['kit']) : '';
    if (isset($kits[$id])) {
        foreach ($kits[$id]['mods'] as $k => $v) {
            set_theme_mod($k, $v);
        }
    }
    wp_safe_redirect(admin_url('themes.php?page=mh-theme-tools&mh_done=kit'));
    exit;
});

/** Export all mh_* theme mods as JSON download. */
add_action('admin_post_mh_export_settings', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_export_settings')) {
        wp_die('Not allowed');
    }
    $payload = [
        'theme'   => 'matthummel',
        'version' => wp_get_theme()->get('Version'),
        'date'    => gmdate('c'),
        'mods'    => mh_owned_mods(),
    ];
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=matthummel-settings-' . gmdate('Ymd-His') . '.json');
    echo wp_json_encode($payload, JSON_PRETTY_PRINT);
    exit;
});

/** Import settings from an uploaded JSON file. */
add_action('admin_post_mh_import_settings', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_import_settings')) {
        wp_die('Not allowed');
    }
    $ok = false;
    if (! empty($_FILES['mh_import_file']['tmp_name']) && is_uploaded_file($_FILES['mh_import_file']['tmp_name'])) {
        $raw  = file_get_contents($_FILES['mh_import_file']['tmp_name']);
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['mods']) && is_array($data['mods'])) {
            foreach ($data['mods'] as $k => $v) {
                if (is_string($k) && strpos($k, 'mh_') === 0 && (is_scalar($v) || is_array($v))) {
                    set_theme_mod($k, $v);
                }
            }
            $ok = true;
        }
    }
    wp_safe_redirect(admin_url('themes.php?page=mh-theme-tools&mh_done=' . ($ok ? 'import' : 'importerr')));
    exit;
});

/** Reset: remove all mh_* theme mods. */
add_action('admin_post_mh_reset_settings', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_reset_settings')) {
        wp_die('Not allowed');
    }
    foreach (array_keys(mh_owned_mods()) as $k) {
        remove_theme_mod($k);
    }
    wp_safe_redirect(admin_url('themes.php?page=mh-theme-tools&mh_done=reset'));
    exit;
});
