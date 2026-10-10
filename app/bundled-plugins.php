<?php

/**
 * Bundled modules: Hummel Ops and MH SEO now ship inside the theme.
 *
 * Each module returns early when its standalone plugin is already active, so
 * nothing is declared twice. To switch a module off, filter the list:
 *
 *     add_filter('mh/bundled_modules', fn ($m) => array_diff($m, ['mh-seo']));
 *
 * Source lives in app/modules/<name>/ (see docs/BUNDLED-MODULES.md).
 */

namespace App;

foreach ((array) apply_filters('mh/bundled_modules', ['hummel-ops', 'mh-seo']) as $mh_module) {
    $mh_module = preg_replace('/[^a-z0-9-]/', '', (string) $mh_module);
    $mh_file = __DIR__.'/modules/'.$mh_module.'/'.$mh_module.'.php';
    if ($mh_module !== '' && is_readable($mh_file)) {
        require_once $mh_file;
    }
}
unset($mh_module, $mh_file);
