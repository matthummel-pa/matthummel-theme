<?php

use App\Providers\ThemeServiceProvider;
use Genero\Sage\WooCommerce\WooCommerceServiceProvider;
use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

require_once __DIR__.'/app/Github.php';
require_once __DIR__.'/app/LinkedIn.php';

/*
|--------------------------------------------------------------------------
| Companion MU plugins (theme-bundled fallback)
|--------------------------------------------------------------------------
|
| Prefer a real must-use install at wp-content/mu-plugins/. If that file is
| missing, load the copy shipped inside this theme so deploys still expose
| Rank Math meta to the REST API.
|
*/

$mhRankMathRestMeta = WPMU_PLUGIN_DIR.'/rank-math-rest-meta.php';
if (! is_readable($mhRankMathRestMeta)) {
    $mhRankMathRestMeta = __DIR__.'/mu-plugins/rank-math-rest-meta.php';
    if (is_readable($mhRankMathRestMeta)) {
        require_once $mhRankMathRestMeta;
    }
}
unset($mhRankMathRestMeta);

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        ThemeServiceProvider::class,
        WooCommerceServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters', 'cache-headers', 'contact', 'portfolio', 'shop', 'concept-pages', 'concept-sidebar', 'woocommerce', 'icons', 'page-fields', 'affiliate', 'theme-updater', 'db-migrate', 'bespoke', 'comments', 'devto-export', 'bluesky-share', 'social-share', 'featured-image'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });
