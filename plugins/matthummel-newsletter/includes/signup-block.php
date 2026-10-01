<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function register_signup_block(): void
{
    $path = MHN_DIR.'/build/signup';
    if (! is_readable($path.'/block.json')) {
        return;
    }

    register_block_type($path);
}
