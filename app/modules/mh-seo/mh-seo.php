<?php

/**
 * MH SEO: titles, descriptions, schema, sitemaps, redirects, and a writing score. Version 1.1.1.
 *
 * Bundled with the matthummel theme. If the standalone MH SEO plugin is active, it wins
 * and this copy stays out of the way (no duplicate classes).
 *
 * The theme steps back from its own title and description output with:
 *
 *     if ( defined( 'MH_SEO_ACTIVE' ) && MH_SEO_ACTIVE && function_exists( 'mh_seo_is_managing_head' ) && mh_seo_is_managing_head() ) {
 *         return;
 *     }
 *
 * mh_seo_is_managing_head() is false while Rank Math is active, unless head tags are forced on in the settings.
 */

declare(strict_types=1);
use MH_SEO\Plugin;
use MH_SEO\Redirects;
use MH_SEO\Sitemap;

if (! defined('ABSPATH')) {
    exit;
}

if (defined('MH_SEO_VERSION')) {
    return;
}

define('MH_SEO_VERSION', '1.1.1');
define('MH_SEO_FILE', __FILE__);
define('MH_SEO_PATH', trailingslashit(__DIR__));
define('MH_SEO_URL', trailingslashit(get_theme_file_uri('app/modules/mh-seo')));

/**
 * The theme checks this constant to see that MH SEO is loaded.
 */
define('MH_SEO_ACTIVE', true);

require_once MH_SEO_PATH.'includes/class-text.php';
require_once MH_SEO_PATH.'includes/class-score-analyzer.php';
require_once MH_SEO_PATH.'includes/class-schema.php';
require_once MH_SEO_PATH.'includes/class-document-builder.php';
require_once MH_SEO_PATH.'includes/class-head-renderer.php';
require_once MH_SEO_PATH.'includes/class-paths.php';
require_once MH_SEO_PATH.'includes/class-migrator.php';
require_once MH_SEO_PATH.'includes/class-settings.php';
require_once MH_SEO_PATH.'includes/class-plugin.php';
require_once MH_SEO_PATH.'includes/functions.php';
require_once MH_SEO_PATH.'includes/class-meta.php';
require_once MH_SEO_PATH.'includes/class-context.php';
require_once MH_SEO_PATH.'includes/class-head.php';
require_once MH_SEO_PATH.'includes/class-sitemap-xml.php';
require_once MH_SEO_PATH.'includes/class-sitemap.php';
require_once MH_SEO_PATH.'includes/class-redirects.php';
require_once MH_SEO_PATH.'includes/class-indexnow.php';
require_once MH_SEO_PATH.'includes/class-score.php';
require_once MH_SEO_PATH.'includes/class-admin.php';
require_once MH_SEO_PATH.'includes/class-rest-controller.php';
require_once MH_SEO_PATH.'includes/class-assets.php';
require_once MH_SEO_PATH.'includes/class-cli.php';

// A theme has no activation hook. Create the tables and rewrite rules once per version instead.
add_action(
    'init',
    static function () {
        if (get_option('mh_seo_bundled_version') === MH_SEO_VERSION) {
            return;
        }
        Redirects::install();
        Sitemap::activate();
        update_option('mh_seo_bundled_version', MH_SEO_VERSION, false);
    },
    99
);

// Flush rewrite rules when the theme is switched away, as the plugin did on deactivation.
add_action(
    'switch_theme',
    static function () {
        flush_rewrite_rules();
    }
);

// The theme loads after plugins_loaded has already fired, so boot right away in that case.
$mh_seo_boot = static function () {
    Plugin::instance()->boot();
};
did_action('plugins_loaded') ? $mh_seo_boot() : add_action('plugins_loaded', $mh_seo_boot);
unset($mh_seo_boot);
