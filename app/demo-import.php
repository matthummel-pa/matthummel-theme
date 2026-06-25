<?php

/**
 * Starter Sites — one-click demo import. Each starter site composes pages from
 * the registered block patterns, applies a Style Kit, sets the front page, and
 * builds + assigns the primary menu. Existing pages are reused, never duplicated.
 *
 * Appearance -> Starter Sites.
 */

namespace App;

function mh_starter_sites()
{
    return [
        'portfolio' => [
            'label' => __('Developer Portfolio', 'matthummel'),
            'desc'  => __('A personal portfolio with live GitHub repos, services, stats, and a contact CTA.', 'matthummel'),
            'kit'   => 'editorial',
            'pages' => [
                'home'     => ['title' => __('Home', 'matthummel'), 'patterns' => ['matthummel/hero-dev', 'matthummel/services-three', 'matthummel/stats-four', 'matthummel/testimonial-single', 'matthummel/contact-cta']],
                'about'    => ['title' => __('About', 'matthummel'), 'patterns' => ['matthummel/about-two-col', 'matthummel/feature-grid']],
                'projects' => ['title' => __('Projects', 'matthummel'), 'raw' => '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Projects</h2><!-- /wp:heading --><!-- wp:mh/repo-grid {"count":6,"columns":2} /-->'],
                'contact'  => ['title' => __('Contact', 'matthummel'), 'patterns' => ['matthummel/contact-cta']],
            ],
            'menu'  => ['home', 'about', 'projects', 'contact'],
            'front' => 'home',
        ],
        'agency' => [
            'label' => __('Studio / Agency', 'matthummel'),
            'desc'  => __('A services-led site with hero, services, pricing, testimonials, and CTAs.', 'matthummel'),
            'kit'   => 'sage_classic',
            'pages' => [
                'home'     => ['title' => __('Home', 'matthummel'), 'patterns' => ['matthummel/hero-centered-minimal', 'matthummel/services-three', 'matthummel/pricing', 'matthummel/testimonials', 'matthummel/cta-split']],
                'about'    => ['title' => __('About', 'matthummel'), 'patterns' => ['matthummel/about-two-col', 'matthummel/stats-four']],
                'services' => ['title' => __('Services', 'matthummel'), 'patterns' => ['matthummel/services-three', 'matthummel/pricing']],
                'contact'  => ['title' => __('Contact', 'matthummel'), 'patterns' => ['matthummel/contact-cta']],
            ],
            'menu'  => ['home', 'about', 'services', 'contact'],
            'front' => 'home',
        ],
    ];
}

/** Assemble a page's block markup from patterns + raw. */
function mh_compose_page($def)
{
    $out = '';
    if (! empty($def['patterns']) && class_exists('WP_Block_Patterns_Registry')) {
        $reg = \WP_Block_Patterns_Registry::get_instance();
        foreach ($def['patterns'] as $name) {
            $p = $reg->get_registered($name);
            if ($p && ! empty($p['content'])) {
                $out .= $p['content'] . "\n";
            }
        }
    }
    if (! empty($def['raw'])) {
        $out .= $def['raw'];
    }
    return $out !== '' ? $out : '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->';
}

add_action('admin_menu', function () {
    add_theme_page(__('Starter Sites', 'matthummel'), __('Starter Sites', 'matthummel'), 'edit_theme_options', 'mh-starter-sites', __NAMESPACE__ . '\\mh_starter_render');
});

function mh_starter_render()
{
    if (! current_user_can('edit_theme_options')) {
        return;
    }
    $post = admin_url('admin-post.php');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Starter Sites', 'matthummel'); ?></h1>
        <?php if (isset($_GET['mh_demo']) && $_GET['mh_demo'] === 'done') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Starter site imported — pages, menu, front page, and style kit are set. View your site!', 'matthummel'); ?></p></div>
        <?php endif; ?>
        <p class="description" style="max-width:640px"><?php esc_html_e('One click builds a full set of pages from the theme\'s patterns, applies a matching Style Kit, sets the homepage, and creates the primary menu. Existing pages with the same name are reused.', 'matthummel'); ?></p>

        <div style="display:flex;flex-wrap:wrap;gap:18px;margin-top:18px">
            <?php foreach (mh_starter_sites() as $id => $site) : ?>
                <div style="width:320px;border:1px solid #dcdcde;border-radius:12px;padding:18px;background:#fff">
                    <h2 style="margin-top:0;font-size:16px"><?php echo esc_html($site['label']); ?></h2>
                    <p style="color:#646970;font-size:13px;min-height:48px"><?php echo esc_html($site['desc']); ?></p>
                    <p style="font-size:12px;color:#646970"><?php printf(esc_html__('Pages: %s', 'matthummel'), esc_html(implode(', ', array_map(fn ($p) => $p['title'], $site['pages'])))); ?></p>
                    <form method="post" action="<?php echo esc_url($post); ?>" onsubmit="return confirm('<?php echo esc_js(__('Import this starter site? It will create pages, set the homepage and menu, and apply a style kit.', 'matthummel')); ?>');">
                        <input type="hidden" name="action" value="mh_import_demo">
                        <input type="hidden" name="site" value="<?php echo esc_attr($id); ?>">
                        <?php wp_nonce_field('mh_import_demo'); ?>
                        <button class="button button-primary" style="width:100%"><?php esc_html_e('Import this starter site', 'matthummel'); ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

add_action('admin_post_mh_import_demo', function () {
    if (! current_user_can('edit_theme_options') || ! check_admin_referer('mh_import_demo')) {
        wp_die('Not allowed');
    }
    $sites = mh_starter_sites();
    $id    = isset($_POST['site']) ? sanitize_key($_POST['site']) : '';
    if (! isset($sites[$id])) {
        wp_safe_redirect(admin_url('themes.php?page=mh-starter-sites'));
        exit;
    }
    $site = $sites[$id];

    // 1) style kit
    if (function_exists('App\\mh_style_kits')) {
        $kits = mh_style_kits();
        if (isset($kits[$site['kit']])) {
            foreach ($kits[$site['kit']]['mods'] as $k => $v) {
                set_theme_mod($k, $v);
            }
        }
    }

    // 2) pages
    $ids = [];
    foreach ($site['pages'] as $slug => $def) {
        $existing = get_page_by_path($slug);
        if ($existing) {
            $ids[$slug] = $existing->ID;
            // refresh content only if the page is empty
            if (trim((string) $existing->post_content) === '') {
                wp_update_post(['ID' => $existing->ID, 'post_content' => mh_compose_page($def)]);
            }
            continue;
        }
        $ids[$slug] = wp_insert_post([
            'post_title'   => $def['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => mh_compose_page($def),
        ]);
    }

    // 3) front page
    if (! empty($site['front']) && ! empty($ids[$site['front']])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids[$site['front']]);
    }

    // 4) primary menu
    $menu = wp_get_nav_menu_object(__('Primary', 'matthummel'));
    $menu_id = $menu ? $menu->term_id : wp_create_nav_menu(__('Primary', 'matthummel'));
    if (! is_wp_error($menu_id)) {
        $have = wp_get_nav_menu_items($menu_id) ?: [];
        if (empty($have)) {
            foreach ($site['menu'] as $slug) {
                if (! empty($ids[$slug])) {
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title'     => $site['pages'][$slug]['title'],
                        'menu-item-object'    => 'page',
                        'menu-item-object-id' => $ids[$slug],
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                    ]);
                }
            }
        }
        $loc = get_theme_mod('nav_menu_locations', []);
        $loc['primary_navigation'] = $menu_id;
        set_theme_mod('nav_menu_locations', $loc);
    }

    wp_safe_redirect(admin_url('themes.php?page=mh-starter-sites&mh_demo=done'));
    exit;
});
