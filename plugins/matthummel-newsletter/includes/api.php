<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

function mhn_render_footer_form(): string
{
    return MattHummel\Newsletter\footer_form_html();
}
