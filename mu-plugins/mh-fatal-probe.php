<?php

/**
 * Retired. This file used to write PHP fatals into the public theme directory.
 * Host error logs (hPanel on Hostinger) are the place for those messages.
 */

declare(strict_types=1);

if (defined('MH_FATAL_PROBE_LOADED')) {
    return;
}

define('MH_FATAL_PROBE_LOADED', true);
