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

$mhFatalProbe = __DIR__.'/mu-plugins/mh-fatal-probe.php';
if (is_readable($mhFatalProbe)) {
    require_once $mhFatalProbe;
}
unset($mhFatalProbe);

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
| Drop stale Acorn package manifests
|--------------------------------------------------------------------------
|
| wp-content/cache/acorn survives theme zip / FTP deploys. If packages.php
| still lists a removed Composer provider (e.g. Blade Heroicons), Acorn's
| ProviderRepository::compileManifest() fatals before SkipProviderException
| can run — white-screen / "critical error" on every front-end request.
|
*/

(static function (): void {
    if (! defined('WP_CONTENT_DIR')) {
        return;
    }

    $dir = WP_CONTENT_DIR.'/cache/acorn/framework/cache';
    $stale = false;

    foreach (['packages.php', 'services.php'] as $file) {
        $path = $dir.'/'.$file;
        if (! is_readable($path)) {
            continue;
        }

        $data = include $path;
        if (! is_array($data)) {
            $stale = true;
            break;
        }

        $providers = [];
        if (isset($data['providers']) && is_array($data['providers'])) {
            $providers = $data['providers'];
        } else {
            foreach ($data as $package) {
                if (! is_array($package) || empty($package['providers']) || ! is_array($package['providers'])) {
                    continue;
                }
                foreach ($package['providers'] as $provider) {
                    $providers[] = $provider;
                }
            }
        }

        foreach ($providers as $provider) {
            if (is_string($provider) && $provider !== '' && ! class_exists($provider)) {
                $stale = true;
                break 2;
            }
        }
    }

    if (! $stale) {
        return;
    }

    foreach (['packages.php', 'services.php'] as $file) {
        $path = $dir.'/'.$file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
})();

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

collect(['setup', 'filters', 'cache-headers', 'contact', 'portfolio', 'shop', 'concept-pages', 'woocommerce', 'icons', 'page-fields', 'rank-math-fields', 'affiliate', 'theme-updater', 'db-migrate', 'bespoke', 'comments', 'devto-export', 'bluesky-share', 'social-share', 'featured-image', 'blocks'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });
