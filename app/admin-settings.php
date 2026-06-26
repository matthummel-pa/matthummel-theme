<?php

/**
 * Full admin "Theme Settings" page — premium-style tabbed options panel.
 * Reads/writes the SAME theme mods the Customizer uses (single source of truth).
 */

namespace App;

/** Combined defaults from the Customizer + layout modules. */
function mh_admin_defaults()
{
    $base = function_exists('App\\mh_defaults') ? mh_defaults() : [];
    if (function_exists('App\\mh_layout_defaults')) {
        foreach (mh_layout_defaults() as $t => $v) {
            $base["mh_layout_{$t}_width"]    = $v['width'];
            $base["mh_layout_{$t}_maxwidth"] = $v['maxwidth'];
            $base["mh_layout_{$t}_sidebar"]  = $v['sidebar'];
        }
    }
    $base = array_merge($base, [
        'mh_topbar_enable' => false, 'mh_topbar_contact' => '', 'mh_topbar_show_social' => true,
        'mh_topbar_cta_text' => '', 'mh_topbar_cta_url' => '', 'mh_topbar_bg' => 'ink', 'mh_topbar_text' => 'white',
        'mh_nav_fullwidth' => false, 'mh_header_width' => 1180, 'mh_header_height' => 0, 'mh_header_gap' => 28,
        'mh_logo_order' => 1, 'mh_nav_order' => 2, 'mh_social_order' => 3, 'mh_cta_order' => 4,
        'mh_popout_desktop' => false, 'mh_popout_tablet' => true, 'mh_popout_mobile' => true,
        'mh_footer_social' => true, 'mh_footer_cols' => 3, 'mh_footer_bg' => 'paper', 'mh_footer_bg_custom' => '',
        'mh_footer_textc' => 'body', 'mh_footer_text_custom' => '',
        'mh_proj_owner' => 'matthummel-pa', 'mh_gh_token' => '', 'mh_proj_cache_hours' => 6, 'mh_gh_client_id' => '',
    ]);
    return $base;
}

function mh_admin_get($key)
{
    $d = mh_admin_defaults();
    return get_theme_mod($key, $d[$key] ?? '');
}

/** Tabbed field schema. */
function mh_admin_schema()
{
    $fonts       = function_exists('App\\mh_fonts') ? array_keys(mh_fonts()) : ['Space Grotesk', 'Inter'];
    $fontChoices = array_combine($fonts, $fonts);
    $w           = function_exists('App\\mh_width_choices') ? mh_width_choices() : ['default' => 'Default'];
    $labels      = function_exists('App\\mh_layout_labels') ? mh_layout_labels() : ['page' => 'Pages'];

    $layoutFields = [];
    foreach ($labels as $t => $lab) {
        $layoutFields[] = ['key' => "mh_layout_{$t}_width", 'label' => "{$lab} — width preset", 'type' => 'select', 'choices' => $w];
        $layoutFields[] = ['key' => "mh_layout_{$t}_maxwidth", 'label' => "{$lab} — custom width", 'type' => 'select', 'choices' => mh_width_options(true)];
        $layoutFields[] = ['key' => "mh_layout_{$t}_sidebar", 'label' => "{$lab} — show sidebar", 'type' => 'checkbox'];
    }

    return [
        'general' => ['icon' => 'dashicons-admin-settings', 'label' => __('General', 'matthummel'), 'fields' => [
            ['key' => 'mh_cta_text', 'label' => __('Header button text', 'matthummel'), 'type' => 'text'],
            ['key' => 'mh_cta_url', 'label' => __('Header button URL', 'matthummel'), 'type' => 'text'],
            ['key' => 'mh_show_cta', 'label' => __('Show header button', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_footer_text', 'label' => __('Footer tagline', 'matthummel'), 'type' => 'textarea'],
        ]],
        'design' => ['icon' => 'dashicons-art', 'label' => __('Design', 'matthummel'), 'fields' => [
            ['key' => 'mh_color_action', 'label' => __('Brand / buttons', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_color_paper', 'label' => __('Background', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_color_ink', 'label' => __('Headings', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_color_body', 'label' => __('Body text', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_font_heading', 'label' => __('Heading font', 'matthummel'), 'type' => 'select', 'choices' => $fontChoices],
            ['key' => 'mh_font_body', 'label' => __('Body font', 'matthummel'), 'type' => 'select', 'choices' => $fontChoices],
            ['key' => 'mh_container', 'label' => __('Default content width', 'matthummel'), 'type' => 'select', 'choices' => mh_width_options()],
        ]],
        'layout' => ['icon' => 'dashicons-screenoptions', 'label' => __('Layout', 'matthummel'), 'fields' => $layoutFields],
        'header' => ['icon' => 'dashicons-editor-kitchensink', 'label' => __('Header', 'matthummel'), 'fields' => [
            ['key' => 'mh_nav_fullwidth', 'label' => __('Full-width menu', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_header_width', 'label' => __('Header width', 'matthummel'), 'type' => 'select', 'choices' => mh_width_options()],
            ['key' => 'mh_header_height', 'label' => __('Header height (px, 0 = auto)', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_header_gap', 'label' => __('Header gap (px)', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_logo_order', 'label' => __('Logo position', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_nav_order', 'label' => __('Menu position', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_social_order', 'label' => __('Social links position', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_cta_order', 'label' => __('Button position', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_topbar_enable', 'label' => __('Enable top bar', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_topbar_contact', 'label' => __('Top bar contact text', 'matthummel'), 'type' => 'text'],
            ['key' => 'mh_topbar_show_social', 'label' => __('Top bar social links', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_topbar_cta_text', 'label' => __('Top bar button text', 'matthummel'), 'type' => 'text'],
            ['key' => 'mh_topbar_cta_url', 'label' => __('Top bar button URL', 'matthummel'), 'type' => 'text'],
            ['key' => 'mh_topbar_bg', 'label' => __('Top bar background', 'matthummel'), 'type' => 'select', 'choices' => mh_palette_choices()],
            ['key' => 'mh_topbar_text', 'label' => __('Top bar text color', 'matthummel'), 'type' => 'select', 'choices' => mh_palette_choices()],
            ['key' => 'mh_popout_desktop', 'label' => __('Menu icon on desktop', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_popout_tablet', 'label' => __('Menu icon on tablet', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_popout_mobile', 'label' => __('Menu icon on mobile', 'matthummel'), 'type' => 'checkbox'],
        ]],
        'footer' => ['icon' => 'dashicons-editor-insertmore', 'label' => __('Footer', 'matthummel'), 'fields' => [
            ['key' => 'mh_footer_social', 'label' => __('Show social icons in footer', 'matthummel'), 'type' => 'checkbox'],
            ['key' => 'mh_footer_cols', 'label' => __('Footer columns', 'matthummel'), 'type' => 'select', 'choices' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4']],
            ['key' => 'mh_footer_bg', 'label' => __('Footer background', 'matthummel'), 'type' => 'select', 'choices' => mh_palette_choices()],
            ['key' => 'mh_footer_bg_custom', 'label' => __('Footer background (custom hex)', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_footer_textc', 'label' => __('Footer text color', 'matthummel'), 'type' => 'select', 'choices' => mh_palette_choices()],
            ['key' => 'mh_footer_text_custom', 'label' => __('Footer text (custom hex)', 'matthummel'), 'type' => 'color'],
            ['key' => 'mh_footer_text', 'label' => __('Footer tagline', 'matthummel'), 'type' => 'textarea'],
        ], 'note' => __('Footer columns map to block widget areas under Appearance > Widgets.', 'matthummel')],
        'projects' => ['icon' => 'dashicons-portfolio', 'label' => __('Projects', 'matthummel'), 'fields' => [
            ['key' => 'mh_proj_owner', 'label' => __('Default GitHub owner', 'matthummel'), 'type' => 'text', 'desc' => __('Used for the live repo data when a project has no owner set.', 'matthummel')],
            ['key' => 'mh_gh_token', 'label' => __('GitHub API token (optional)', 'matthummel'), 'type' => 'text', 'desc' => __('A read-only token raises the GitHub API rate limit. Stored as a theme setting.', 'matthummel')],
            ['key' => 'mh_proj_cache_hours', 'label' => __('GitHub data cache (hours)', 'matthummel'), 'type' => 'number'],
            ['key' => 'mh_gh_client_id', 'label' => __('GitHub OAuth Client ID', 'matthummel'), 'type' => 'text', 'desc' => __('Public Client ID from your GitHub OAuth App (Device Flow enabled). Needed for "Connect with GitHub".', 'matthummel')],
            ['key' => 'mh_gh_connect', 'label' => __('GitHub connection', 'matthummel'), 'type' => 'github_connect'],
        ], 'note' => __('Per-project owner/repo, eyebrow and demo URL are set on each project via the Project Details box.', 'matthummel')],
    ];
}

/** Top-level admin menu. */
add_action('admin_menu', function () {
    add_menu_page(
        __('Theme Settings', 'matthummel'),
        __('Theme Settings', 'matthummel'),
        'manage_options',
        'mh-theme-settings',
        'App\\mh_render_settings_page',
        'dashicons-admin-customizer',
        59
    );
});

/** Color picker on our page only. */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_mh-theme-settings') {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_add_inline_script('wp-color-picker', 'jQuery(function($){$(".mh-color").wpColorPicker();});');
});

function mh_render_field($f)
{
    $key  = $f['key'];
    $type = $f['type'];
    $val  = mh_admin_get($key);
    echo '<div class="mh-field mh-field-' . esc_attr($type) . '">';
    echo '<label for="' . esc_attr($key) . '">' . esc_html($f['label']) . '</label>';
    echo '<div class="mh-field-control">';
    switch ($type) {
        case 'github_connect':
            if (function_exists('App\\mh_gh_connect_widget')) {
                mh_gh_connect_widget();
            }
            break;
        case 'textarea':
            echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="3">' . esc_textarea($val) . '</textarea>';
            break;
        case 'checkbox':
            echo '<label class="mh-switch"><input type="checkbox" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="1" ' . checked((bool) $val, true, false) . '> <span>' . esc_html__('Enabled', 'matthummel') . '</span></label>';
            break;
        case 'select':
            echo '<select id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">';
            foreach ($f['choices'] as $k => $lab) {
                echo '<option value="' . esc_attr($k) . '" ' . selected($val, $k, false) . '>' . esc_html($lab) . '</option>';
            }
            echo '</select>';
            break;
        case 'color':
            echo '<input type="text" class="mh-color" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
            break;
        case 'number':
            echo '<input type="number" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" min="0" step="10">';
            break;
        default:
            echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
    }
    if (! empty($f['desc'])) {
        echo '<p class="mh-field-desc">' . esc_html($f['desc']) . '</p>';
    }
    echo '</div></div>';
}

function mh_render_settings_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }
    $schema = mh_admin_schema();
    $first  = array_key_first($schema);
    ?>
    <style>
      .mh-admin{max-width:1120px}
      .mh-admin-head{display:flex;align-items:center;gap:16px;background:#fff;border:1px solid #e2e2e2;border-radius:12px;padding:18px 22px;margin:20px 0}
      .mh-admin-logo svg{width:44px;height:44px;display:block}
      .mh-admin-head h1{margin:0;font-size:20px;padding:0}
      .mh-admin-head p{margin:2px 0 0;color:#646970}
      .mh-admin-head .button{margin-left:auto}
      .mh-admin-body{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
      .mh-admin-tabs{display:flex;flex-direction:column;gap:4px;background:#fff;border:1px solid #e2e2e2;border-radius:12px;padding:10px;position:sticky;top:46px}
      .mh-tab-btn{display:flex;align-items:center;gap:8px;text-align:left;background:none;border:0;padding:10px 12px;border-radius:8px;cursor:pointer;font-size:14px;color:#1d2327}
      .mh-tab-btn:hover{background:#f0f0f1}
      .mh-tab-btn.is-active{background:#2f6b4e;color:#fff}
      .mh-tab-btn.is-active .dashicons{color:#fff}
      .mh-admin-form{background:#fff;border:1px solid #e2e2e2;border-radius:12px;padding:8px 24px 24px}
      .mh-tab-panel{display:none}
      .mh-tab-panel.is-active{display:block}
      .mh-tab-panel>h2{font-size:18px;margin:18px 0 4px}
      .mh-note{background:#f6f7f7;border-left:3px solid #2f6b4e;padding:10px 14px;color:#50575e;border-radius:0 6px 6px 0}
      .mh-field{display:grid;grid-template-columns:230px 1fr;gap:16px;align-items:start;padding:14px 0;border-bottom:1px solid #f0f0f1}
      .mh-field>label{font-weight:600;padding-top:6px}
      .mh-field-control input[type=text],.mh-field-control input[type=number],.mh-field-control select,.mh-field-control textarea{width:100%;max-width:430px}
      .mh-field-desc{color:#646970;font-size:12px;margin:6px 0 0}
      .mh-admin-save{margin-top:18px}
      .mh-switch{display:inline-flex;align-items:center;gap:8px}
      @media(max-width:782px){.mh-admin-body{grid-template-columns:1fr}.mh-admin-tabs{flex-direction:row;flex-wrap:wrap;position:static}}
    </style>

    <div class="wrap mh-admin">
      <div class="mh-admin-head">
        <span class="mh-admin-logo" aria-hidden="true">
          <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="120" rx="30" fill="#2f6b4e"/><text x="60" y="80" text-anchor="middle" fill="#fff" font-family="'Space Grotesk',Arial,sans-serif" font-size="56" font-weight="700">MH</text></svg>
        </span>
        <div>
          <h1><?php esc_html_e('Theme Settings', 'matthummel'); ?></h1>
          <p><?php esc_html_e('Matt Hummel — Sage theme options', 'matthummel'); ?></p>
        </div>
        <a class="button" href="<?php echo esc_url(admin_url('customize.php')); ?>"><?php esc_html_e('Open Customizer', 'matthummel'); ?></a>
      </div>

      <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'matthummel'); ?></p></div>
      <?php endif; ?>

      <div class="mh-admin-body">
        <nav class="mh-admin-tabs" aria-label="<?php esc_attr_e('Settings sections', 'matthummel'); ?>">
          <?php foreach ($schema as $id => $tab) : ?>
            <button type="button" class="mh-tab-btn <?php echo $id === $first ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr($id); ?>">
              <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span> <?php echo esc_html($tab['label']); ?>
            </button>
          <?php endforeach; ?>
        </nav>

        <form class="mh-admin-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="mh_save_theme_settings">
          <?php wp_nonce_field('mh_save_theme_settings'); ?>
          <?php foreach ($schema as $id => $tab) : ?>
            <section class="mh-tab-panel <?php echo $id === $first ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr($id); ?>">
              <h2><?php echo esc_html($tab['label']); ?></h2>
              <?php if (! empty($tab['note'])) : ?><p class="mh-note"><?php echo esc_html($tab['note']); ?></p><?php endif; ?>
              <?php foreach ($tab['fields'] as $f) {
                  mh_render_field($f);
              } ?>
            </section>
          <?php endforeach; ?>
          <p class="mh-admin-save"><button class="button button-primary button-large"><?php esc_html_e('Save changes', 'matthummel'); ?></button></p>
        </form>
      </div>
    </div>

    <script>
      (function(){
        var btns = document.querySelectorAll('.mh-tab-btn');
        var panels = document.querySelectorAll('.mh-tab-panel');
        btns.forEach(function(b){
          b.addEventListener('click', function(){
            var t = b.getAttribute('data-tab');
            btns.forEach(function(x){ x.classList.toggle('is-active', x === b); });
            panels.forEach(function(p){ p.classList.toggle('is-active', p.getAttribute('data-tab') === t); });
          });
        });
      })();
    </script>
    <?php
}

/** Save handler. */
add_action('admin_post_mh_save_theme_settings', function () {
    if (! current_user_can('manage_options')) {
        wp_die(__('You do not have permission to do this.', 'matthummel'));
    }
    check_admin_referer('mh_save_theme_settings');

    foreach (mh_admin_schema() as $tab) {
        foreach ($tab['fields'] as $f) {
            $key  = $f['key'];
            $type = $f['type'];
            if ($type === 'github_connect') {
                continue;
            }
            if ($type === 'checkbox') {
                set_theme_mod($key, isset($_POST[$key]));
                continue;
            }
            $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            switch ($type) {
                case 'color':
                    $val = sanitize_hex_color($raw);
                    break;
                case 'number':
                    $val = absint($raw);
                    break;
                case 'textarea':
                    $val = wp_kses_post($raw);
                    break;
                case 'select':
                    $val = sanitize_text_field($raw);
                    break;
                default:
                    $val = (substr($key, -4) === '_url') ? esc_url_raw($raw) : sanitize_text_field($raw);
            }
            set_theme_mod($key, $val);
        }
    }

    wp_safe_redirect(add_query_arg(['page' => 'mh-theme-settings', 'updated' => '1'], admin_url('admin.php')));
    exit;
});
