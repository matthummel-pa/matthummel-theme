<?php

/**
 * Hummel Ops: personal operations hub for wp-admin (daily rundown, tasks, n8n workflows,
 * Google Drive files, WordPress release tracker). Version 2.3.0.
 *
 * Bundled with the matthummel theme. If the standalone Hummel Ops plugin is active,
 * it wins and this copy stays out of the way (no duplicate classes).
 */
defined('ABSPATH') || exit;

if (defined('HOPS_VERSION')) {
    return;
}

define('HOPS_VERSION', '2.5.0');
define('HOPS_DIR', trailingslashit(__DIR__));
define('HOPS_URL', trailingslashit(get_theme_file_uri('app/modules/hummel-ops')));

require_once HOPS_DIR.'includes/class-ui.php';
require_once HOPS_DIR.'includes/class-settings.php';
require_once HOPS_DIR.'includes/class-google.php';
require_once HOPS_DIR.'includes/class-n8n.php';
require_once HOPS_DIR.'includes/class-n8n-viz.php';
require_once HOPS_DIR.'includes/class-pipeline.php';
require_once HOPS_DIR.'includes/class-drive.php';
require_once HOPS_DIR.'includes/class-todos.php';
require_once HOPS_DIR.'includes/class-rundown.php';
require_once HOPS_DIR.'includes/class-wprel.php';

// Plugins stop their cron on deactivation. A theme does it when it is switched away.
add_action('switch_theme', ['HOPS_WPRel', 'deactivate']);

$hops_boot = static function () {
    HOPS_Settings::init();
    HOPS_Google::init();
    HOPS_N8n::init();
    HOPS_Pipeline::init();
    HOPS_Drive::init();
    HOPS_Todos::init();
    HOPS_Rundown::init();
    HOPS_WPRel::init();
};
// The theme loads after plugins_loaded has already fired, so boot right away in that case.
did_action('plugins_loaded') ? $hops_boot() : add_action('plugins_loaded', $hops_boot);
unset($hops_boot);

add_action('admin_menu', function () {
    $cap = 'manage_options';
    add_menu_page('Hummel Ops', 'Hummel Ops', $cap, 'hops-today', ['HOPS_Rundown', 'render'], 'dashicons-controls', 58);
    add_submenu_page('hops-today', 'Today', 'Today', $cap, 'hops-today', ['HOPS_Rundown', 'render']);
    add_submenu_page('hops-today', 'Tasks', 'Tasks', $cap, 'hops-tasks', ['HOPS_Todos', 'render']);
    add_submenu_page('hops-today', 'Workflows', 'Workflows', $cap, 'hops-workflows', ['HOPS_N8n', 'render']);
    add_submenu_page('hops-today', 'Files', 'Files', $cap, 'hops-drive', ['HOPS_Drive', 'render']);
    add_submenu_page('hops-today', 'WP Releases', 'WP Releases', $cap, 'hops-wp', ['HOPS_WPRel', 'render']);
    add_submenu_page('hops-today', 'Integrations', 'Integrations', $cap, 'hops-settings', ['HOPS_Settings', 'render']);
});

add_action('admin_enqueue_scripts', function () {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (strpos($page, 'hops-') !== 0) {
        return;
    }
    wp_enqueue_style('hops-admin', HOPS_URL.'assets/admin.css', [], HOPS_VERSION);
    wp_enqueue_script('hops-admin', HOPS_URL.'assets/admin.js', [], HOPS_VERSION, true);
    wp_localize_script('hops-admin', 'HOPS', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hops'),
        'page' => $page,
        'urls' => [
            'files' => admin_url('admin.php?page=hops-drive'),
            'tasks' => admin_url('admin.php?page=hops-tasks'),
            'settings' => admin_url('admin.php?page=hops-settings'),
        ],
    ]);
});
