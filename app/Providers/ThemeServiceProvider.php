<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

/**
 * Theme service provider.
 *
 * Extends Acorn's SageServiceProvider. Add custom bindings or boot logic
 * here as the theme grows. The parent class handles Blade, view composers,
 * and the Sage asset pipeline automatically.
 */
class ThemeServiceProvider extends SageServiceProvider
{
    // Parent register() and boot() are inherited automatically.
    // Override only when custom container bindings or boot hooks are needed.
}
