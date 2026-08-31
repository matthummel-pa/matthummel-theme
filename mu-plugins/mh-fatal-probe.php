<?php

/**
 * Temporary live fatal capture for SiteGround recovery.
 * Logs fatals to wp-content/mh-last-fatal.txt (and theme copy).
 */

declare(strict_types=1);

if (defined('MH_FATAL_PROBE_LOADED')) {
    return;
}

define('MH_FATAL_PROBE_LOADED', true);

if (defined('WP_CONTENT_DIR')) {
    @ini_set('log_errors', '1');
    @ini_set('error_log', WP_CONTENT_DIR.'/mh-last-fatal.txt');
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $type = (int) ($error['type'] ?? 0);
    if (! in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }

    $line = sprintf(
        "%s type=%d file=%s line=%d message=%s\n",
        gmdate('c'),
        $type,
        (string) ($error['file'] ?? ''),
        (int) ($error['line'] ?? 0),
        (string) ($error['message'] ?? '')
    );

    $targets = [];
    if (defined('WP_CONTENT_DIR')) {
        $targets[] = WP_CONTENT_DIR.'/mh-last-fatal.txt';
    }
    // Theme dir is writable via the same user FTP uses.
    $targets[] = dirname(__DIR__).'/mh-last-fatal.txt';

    foreach (array_unique($targets) as $path) {
        @file_put_contents($path, $line, LOCK_EX);
    }
});
