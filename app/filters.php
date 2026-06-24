<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * [mh_github owner="" repo="" show="desc,stats,intro"] — live cached GitHub repo data.
 */
add_shortcode('mh_github', function ($atts) {
    $a = shortcode_atts(['owner' => '', 'repo' => '', 'show' => 'stats,intro'], $atts);

    if (! $a['owner'] || ! $a['repo']) {
        return '';
    }

    return \App\Github::render($a['owner'], $a['repo'], array_map('trim', explode(',', $a['show'])));
});
