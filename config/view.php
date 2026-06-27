<?php
/**
 * Acorn view configuration.
 * Overrides compiled views path to avoid Windows permission conflicts
 * when PHP server runs under a different user than the dev environment.
 */
return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => wp_upload_dir()['basedir'] . '/acorn-views',
];
