<?php

/**
 * Temporary live fatal capture for SiteGround recovery.
 * Writes the last PHP fatal to wp-content/mh-last-fatal.txt.
 */

declare(strict_types=1);

if (defined('MH_FATAL_PROBE_LOADED')) {
    return;
}

define('MH_FATAL_PROBE_LOADED', true);

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $type = (int) ($error['type'] ?? 0);
    if (! in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }

    if (! defined('WP_CONTENT_DIR')) {
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

    @file_put_contents(WP_CONTENT_DIR.'/mh-last-fatal.txt', $line, LOCK_EX);
});
