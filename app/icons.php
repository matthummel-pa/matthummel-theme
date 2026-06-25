<?php

/**
 * Blade Icons helper layer for the theme.
 * Wraps the global svg() helper (from blade-ui-kit/blade-icons) with safe
 * fallbacks and a social-network -> Simple Icons name map, plus brand colors.
 */

namespace App;

/**
 * Render a Blade icon to an HTML string. Never fatals if the icon/package
 * is missing — returns an empty string (or a generic fallback) instead.
 *
 * @param string $name   Icon name, e.g. "simpleicon-github", "heroicon-o-moon", "mh-arrow-up-right".
 * @param string $class  CSS class(es) for the <svg>.
 * @param array  $attrs  Extra SVG attributes.
 */
function mh_icon($name, $class = '', $attrs = [])
{
    if (function_exists('svg')) {
        try {
            return svg($name, $class, $attrs)->toHtml();
        } catch (\Throwable $e) {
            // fall through to fallback
        }
    }

    // Generic fallback so the UI never breaks if a name can't be resolved.
    $cls = $class ? ' class="' . esc_attr($class) . '"' : '';
    return '<svg' . $cls . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>';
}

/**
 * Map a social network key to a Blade icon name.
 * Brand glyphs come from Simple Icons (prefix "simpleicon"); non-brands use Heroicons.
 */
function mh_social_icon_name($key)
{
    $map = apply_filters('matthummel/social_icon_names', [
        'linkedin'  => 'mh-linkedin',
        'github'    => 'si-github',
        'devto'     => 'si-devdotto',
        'x'         => 'si-x',
        'bluesky'   => 'si-bluesky',
        'youtube'   => 'si-youtube',
        'instagram' => 'si-instagram',
        'facebook'  => 'si-facebook',
        'mastodon'  => 'si-mastodon',
        'rss'       => 'si-rss',
        'email'     => 'heroicon-o-envelope',
    ]);

    return $map[$key] ?? 'mh-arrow-up-right';
}

/** Official-ish brand color for a network (used by the block's "brand" style). */
function mh_social_color($key)
{
    $c = apply_filters('matthummel/social_colors', [
        'linkedin'  => '#0A66C2',
        'github'    => '#181717',
        'devto'     => '#0A0A0A',
        'x'         => '#000000',
        'bluesky'   => '#1185FE',
        'youtube'   => '#FF0000',
        'instagram' => '#E4405F',
        'facebook'  => '#1877F2',
        'mastodon'  => '#6364FF',
        'rss'       => '#F26522',
        'email'     => '#2f6b4e',
    ]);

    return $c[$key] ?? '#2f6b4e';
}

/** Render a social network's icon SVG by key. */
function mh_social_icon($key, $class = '', $attrs = [])
{
    return mh_icon(mh_social_icon_name($key), $class, $attrs);
}

/** Size/fill rules so inline SVGs sit correctly where Font Awesome <i> used to. */
add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-icons\">"
        . '.mh-popout-socials a svg{width:21px;height:21px;fill:currentColor;display:block;transition:transform .15s ease;}'
        . '.mh-popout-socials a:hover svg{transform:translateY(-2px);}'
        . '.footer-socials a svg{width:18px;height:18px;fill:currentColor;display:block;}'
        . '.top-bar-social svg,.social svg{width:16px;height:16px;fill:currentColor;display:block;}'
        // dark-mode toggle uses Heroicons outline (stroke, not fill)
        . '.mh-theme-toggle svg{width:18px;height:18px;fill:none;stroke:currentColor;}'
        . "</style>\n";
}, 9);
