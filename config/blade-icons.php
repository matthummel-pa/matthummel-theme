<?php

/**
 * Blade Icons configuration.
 * Registers a local "default" icon set (theme resources/svg) under the `mh-` prefix.
 * Third-party sets (Simple Icons, Heroicons, Lucide) auto-register via their own
 * service providers, so they do not need to be listed here.
 *
 * @see https://github.com/blade-ui-kit/blade-icons
 */
return [
    'sets' => [
        'default' => [
            'path'  => 'resources/svg',
            'prefix' => 'mh',
            'class' => '',
            'attributes' => [],
        ],
    ],

    // Global class applied to every icon.
    'class' => '',

    // Global attributes applied to every icon.
    'attributes' => [
        'aria-hidden' => 'true',
    ],

    // Render <svg> inline (true) vs. <use href> sprites (false).
    'fallback' => '',
];
