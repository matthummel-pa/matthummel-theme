<?php

/**
 * Plugin Name:       Matt Hummel Newsletter
 * Plugin URI:        https://matthummel.com
 * Description:       Self-hosted newsletter. Subscribers, issues, and stats stay in this WordPress database. Mail goes out through wp_mail.
 * Version:           1.5.0
 * Requires at least: 6.6
 * Requires PHP:      8.3
 * Author:            Matt Hummel
 * Author URI:        https://matthummel.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       matthummel-newsletter
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('MHN_VERSION', '1.5.0');
define('MHN_FILE', __FILE__);
define('MHN_DIR', __DIR__);

require_once MHN_DIR.'/includes/options.php';
require_once MHN_DIR.'/includes/schema.php';
require_once MHN_DIR.'/includes/subscribers.php';
require_once MHN_DIR.'/includes/blocks.php';
require_once MHN_DIR.'/includes/render.php';
require_once MHN_DIR.'/includes/layouts.php';
require_once MHN_DIR.'/includes/a11y.php';
require_once MHN_DIR.'/includes/mailer.php';
require_once MHN_DIR.'/includes/templates.php';
require_once MHN_DIR.'/includes/issues.php';
require_once MHN_DIR.'/includes/archive.php';
require_once MHN_DIR.'/includes/campaign.php';
require_once MHN_DIR.'/includes/signup.php';
require_once MHN_DIR.'/includes/public.php';
require_once MHN_DIR.'/includes/admin.php';
require_once MHN_DIR.'/includes/wizard.php';
require_once MHN_DIR.'/includes/api.php';

register_activation_hook(MHN_FILE, 'MattHummel\\Newsletter\\activate');
register_deactivation_hook(MHN_FILE, 'MattHummel\\Newsletter\\deactivate');
add_action('plugins_loaded', 'MattHummel\\Newsletter\\boot');
