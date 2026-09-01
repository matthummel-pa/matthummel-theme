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
    return ' &hellip;';
});

add_filter('document_title_separator', function () {
    return '|';
});

add_filter('document_title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('wpseo_title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('rank_math/frontend/title', __NAMESPACE__.'\\mh_filter_document_title', 99);
add_filter('aioseo_title', __NAMESPACE__.'\\mh_filter_document_title', 99);

add_filter('wpseo_metadesc', __NAMESPACE__.'\\mh_filter_meta_description', 99);
add_filter('rank_math/frontend/description', __NAMESPACE__.'\\mh_filter_meta_description', 99);
add_filter('aioseo_description', __NAMESPACE__.'\\mh_filter_meta_description', 99);

add_action('wp_head', __NAMESPACE__.'\\mh_print_meta_description', 1);

/**
 * Whether a known SEO plugin is active and will print its own meta description.
 *
 * @since 3.1.0
 */
function mh_seo_plugin_prints_description(): bool
{
    return defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION');
}

/**
 * Fallback title and meta description for known landing-page templates.
 *
 * @since 3.1.0
 *
 * @param  int|null  $post_id  Post ID to detect the active template; null uses the current query.
 * @return array{title: string, desc: string}
 */
function mh_seo_landing_defaults(?int $post_id = null): array
{
    $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
    $key = '';
    if (is_front_page()) {
        $key = 'front-page.blade.php';
    } elseif ($post_id) {
        $key = page_template_key($post_id);
        if ((int) get_option('page_for_posts') === $post_id) {
            $key = 'index.blade.php';
        }
    }

    $map = [
        'front-page.blade.php' => [
            'title' => __('WordPress Developer for Shops & Agencies', 'sage').' | '.$brand,
            'desc' => __('I build WordPress platforms shops can edit and agencies can hand off. Sage themes, plugins, and clear deploy paths. Say hello.', 'sage'),
        ],
        'template-home.blade.php' => [
            'title' => __('WordPress Developer for Shops & Agencies', 'sage').' | '.$brand,
            'desc' => __('I build WordPress platforms shops can edit and agencies can hand off. Sage themes, plugins, and clear deploy paths. Say hello.', 'sage'),
        ],
        'template-services.blade.php' => [
            'title' => __('WordPress Web Design in Gettysburg', 'sage').' | '.$brand,
            'desc' => __('Custom WordPress sites, plugins, and web apps for Gettysburg shops and agencies. Written scope and clean handoffs. Say hello.', 'sage'),
        ],
        'template-start.blade.php' => [
            'title' => __('WordPress Project Brief', 'sage').' | '.$brand,
            'desc' => __('A short discovery form for shops and agencies. Four steps so I can prepare for our first meeting. Say hello when you are ready.', 'sage'),
        ],
        'template-projects.blade.php' => [
            'title' => __('Example WordPress Sites in Gettysburg', 'sage').' | '.$brand,
            'desc' => __('Live WordPress demos for tours, inns, and shops. Browse the gallery or hire me for a real build in Gettysburg. Say hello.', 'sage'),
        ],
        'template-thankyou.blade.php' => [
            'title' => __('Message Received', 'sage').' | '.$brand,
            'desc' => __('Your note is in my inbox. I reply within one or two business days.', 'sage'),
        ],
        'template-uses.blade.php' => [
            'title' => __('WordPress Dev Stack & Tools', 'sage').' | '.$brand,
            'desc' => __('Sage, PHP, Tailwind, Vite, and the tools I use on real WordPress projects in Gettysburg and remote work. Affiliate links disclosed.', 'sage'),
        ],
        'template-resources.blade.php' => [
            'title' => __('Free WordPress Starters & Tools', 'sage').' | '.$brand,
            'desc' => __('Open code, studio themes, and tools I recommend for WordPress work. Affiliate links disclosed. Hire me for a full build.', 'sage'),
        ],
        'template-affiliate-disclosure.blade.php' => [
            'title' => __('Affiliate Disclosure', 'sage').' | '.$brand,
            'desc' => __('How compensated links work on Journal, Uses, Resources, and elsewhere. Portfolio and hire work come first. Questions welcome.', 'sage'),
        ],
        'template-hire.blade.php' => [
            'title' => __('Hire a WordPress Developer', 'sage').' | '.$brand,
            'desc' => __('Full-time, contract, and freelance WordPress work for shops and agencies. Remote or on-site near Gettysburg. Say hello.', 'sage'),
        ],
        'template-changelog.blade.php' => [
            'title' => __('Theme Changelog', 'sage').' | '.$brand,
            'desc' => __('Notable updates to matthummel.com — new pages, design changes, accessibility fixes, and stack notes.', 'sage'),
        ],
        'template-accessibility.blade.php' => [
            'title' => __('Accessibility Statement', 'sage').' | '.$brand,
            'desc' => __('WCAG 2.1 Level AA goals for matthummel.com. Features, known limits, and how to report issues.', 'sage'),
        ],
        'template-privacy.blade.php' => [
            'title' => __('Privacy Policy', 'sage').' | '.$brand,
            'desc' => __('What matthummel.com collects through forms and analytics, how it is used, and your rights. Contact me with questions.', 'sage'),
        ],
        'template-terms.blade.php' => [
            'title' => __('Terms of Use', 'sage').' | '.$brand,
            'desc' => __('Code reuse, content copyright, example-site disclaimers, and acceptable use of matthummel.com.', 'sage'),
        ],
        'template-woocommerce.blade.php' => [
            'title' => '',
            'desc' => __('Cart, checkout, and account for digital WordPress themes from studio Work projects.', 'sage'),
        ],
        'template-contact.blade.php' => [
            'title' => __('Contact a WordPress Developer', 'sage').' | '.$brand,
            'desc' => __('Start a conversation about a WordPress site, plugin, role, or agency overflow. I reply within two business days. Say hello.', 'sage'),
        ],
        'template-about.blade.php' => [
            'title' => __('About Matt Hummel, WordPress Developer', 'sage').' | '.$brand,
            'desc' => __('Full-stack developer in Gettysburg building WordPress sites shops can edit and agencies can hand off. Say hello.', 'sage'),
        ],
        'template-code.blade.php' => [
            'title' => __('Open WordPress & Full-Stack Code', 'sage').' | '.$brand,
            'desc' => __('Public Sage themes, plugins, PHP, and GitHub activity you can fork or study. Hire me when you want a production build.', 'sage'),
        ],
        'template-now.blade.php' => [
            'title' => __('What I\'m Building Now', 'sage').' | '.$brand,
            'desc' => __('Studio projects, open work, and writing — updated periodically. Say hello about a role or build.', 'sage'),
        ],
        'index.blade.php' => [
            'title' => __('WordPress Development Journal', 'sage').' | '.$brand,
            'desc' => __('Practical WordPress, PHP, and front-end notes from real projects. Most posts include code you can adapt.', 'sage'),
        ],
    ];

    return $map[$key] ?? ['title' => '', 'desc' => ''];
}

/**
 * Post ID for the current request, including the static front page.
 *
 * @since 3.1.0
 */
function mh_seo_current_post_id(): int
{
    if (is_front_page()) {
        return (int) get_option('page_on_front');
    }

    return (int) get_queried_object_id();
}

function mh_seo_document_title(): string
{
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $label = function_exists('woocommerce_page_title')
            ? wp_strip_all_tags((string) woocommerce_page_title(false))
            : __('Shop', 'sage');
        if ($label === '') {
            $label = __('Shop', 'sage');
        }
        $built = is_shop()
            ? __('WordPress Theme Shop', 'sage').' | '.$brand
            : $label.' | '.__('Themes', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (function_exists('is_product') && is_product()) {
        $post_id = (int) get_queried_object_id();
        $pluginTitle = mh_seo_plugin_meta($post_id, ['rank_math_title', '_yoast_wpseo_title']);
        if ($pluginTitle === false) {
            return '';
        }
        if ($pluginTitle !== '') {
            return mh_seo_len($pluginTitle) > 60 ? mh_seo_clip($pluginTitle, 60) : $pluginTitle;
        }
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $title = trim(get_the_title($post_id));
        if ($title === '') {
            return '';
        }
        $built = $title.' | '.__('WordPress Theme', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (function_exists('is_cart') && is_cart()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('Cart', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('Checkout', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        $built = __('My account', 'sage').' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    if (is_singular(mh_project_post_type())) {
        $post_id = (int) get_queried_object_id();
        $pluginTitle = mh_seo_plugin_meta($post_id, ['rank_math_title', '_yoast_wpseo_title']);
        if ($pluginTitle === false) {
            return '';
        }
        if ($pluginTitle !== '') {
            return mh_seo_len($pluginTitle) > 60 ? mh_seo_clip($pluginTitle, 60) : $pluginTitle;
        }

        $title = trim(get_the_title());
        $place = trim((string) get_post_meta($post_id, '_mh_project_place', true));
        $brand = trim((string) get_bloginfo('name', 'display')) ?: 'Matt Hummel';
        if ($title === '') {
            return '';
        }
        $built = $place !== ''
            ? $title.' — '.$place.' | '.$brand
            : $title.' | '.$brand;

        return mh_seo_len($built) > 60 ? mh_seo_clip($built, 60) : $built;
    }

    $post_id = mh_seo_current_post_id();
    $defaults = mh_seo_landing_defaults($post_id);
    $custom = $post_id ? trim(field('seo_title', '', $post_id)) : '';
    $title = $custom !== '' ? $custom : $defaults['title'];
    if ($title === '') {
        return '';
    }

    return mh_seo_len($title) > 60 ? mh_seo_clip($title, 60) : $title;
}

/**
 * Multi-byte character length of a string.
 *
 * @since 3.1.0
 *
 * @param  string  $s  Input string.
 */
function mh_seo_len(string $s): int
{
    return mb_strlen($s);
}

/**
 * Clip a string to at most $max characters, appending an ellipsis.
 *
 * @since 3.1.0
 *
 * @param  string  $s  Input string.
 * @param  int  $max  Maximum character length including the trailing ellipsis.
 */
function mh_seo_clip(string $s, int $max): string
{
    return mb_substr($s, 0, max(1, $max - 1)).'…';
}

/**
 * Rank Math / Yoast title or description for a post.
 *
 * Returns a plain stored value, false when the stored value still has
 * plugin variables (let the plugin's already-processed string through),
 * or an empty string when nothing is set.
 *
 * @param  array<int, string>  $keys  Post meta keys to check in order.
 */
function mh_seo_plugin_meta(int $post_id, array $keys): string|false
{
    foreach ($keys as $key) {
        $value = trim((string) get_post_meta($post_id, $key, true));
        if ($value === '') {
            continue;
        }
        if (str_contains($value, '%')) {
            return false;
        }

        return $value;
    }

    return '';
}

/**
 * Resolved meta description for the current page, clipped to 155 characters.
 *
 * @since 3.1.0
 *
 * @return string Empty string when no description is available.
 */
function mh_seo_meta_description(): string
{
    if (function_exists('is_shop') && is_shop()) {
        $desc = __('Studio WordPress themes with concept pages for context. Buy the pack here or say hello for a custom Gettysburg build.', 'sage');

        return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
    }
    if (function_exists('is_product') && is_product()) {
        $post_id = (int) get_queried_object_id();
        $pluginDesc = mh_seo_plugin_meta($post_id, ['rank_math_description', '_yoast_wpseo_metadesc']);
        if ($pluginDesc === false) {
            return '';
        }
        if ($pluginDesc !== '') {
            return mh_seo_len($pluginDesc) > 155 ? mh_seo_clip($pluginDesc, 155) : $pluginDesc;
        }
        $desc = wp_strip_all_tags((string) (get_the_excerpt($post_id) ?: get_the_title($post_id)));
        $desc = wp_trim_words($desc, 28, '');
        if ($desc !== '' && ! str_ends_with($desc, '.')) {
            $desc .= '.';
        }
        if ($desc === '') {
            $desc = __('WordPress theme for sale. See the details or say hello for help adapting it.', 'sage');
        }

        return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
    }
    if (function_exists('is_cart') && is_cart()) {
        $desc = __('Review themes in your cart, update quantities, and continue to secure checkout.', 'sage');

        return $desc;
    }
    if (function_exists('is_checkout') && is_checkout()) {
        $desc = __('Secure checkout for digital WordPress themes. Access details arrive by email after payment.', 'sage');

        return $desc;
    }
    if (function_exists('is_account_page') && is_account_page()) {
        $desc = __('View orders, downloads, and account details for your WordPress theme purchases.', 'sage');

        return $desc;
    }

    if (is_singular(mh_project_post_type())) {
        $post_id = (int) get_queried_object_id();
        $pluginDesc = mh_seo_plugin_meta($post_id, ['rank_math_description', '_yoast_wpseo_metadesc']);
        if ($pluginDesc === false) {
            return '';
        }
        if ($pluginDesc !== '') {
            return mh_seo_len($pluginDesc) > 155 ? mh_seo_clip($pluginDesc, 155) : $pluginDesc;
        }

        $summary = trim((string) get_post_meta($post_id, '_mh_project_summary', true));
        if ($summary === '') {
            $summary = trim((string) get_post_meta($post_id, '_mh_project_blurb', true));
        }
        if ($summary === '') {
            $summary = 'WordPress project example. See the demo or say hello to adapt it for your shop.';
        }
        if ($summary !== '' && ! str_ends_with($summary, '.')) {
            $summary .= '.';
        }

        return mh_seo_len($summary) > 155 ? mh_seo_clip($summary, 155) : $summary;
    }

    $post_id = mh_seo_current_post_id();
    $defaults = mh_seo_landing_defaults($post_id);
    $custom = $post_id ? trim(field('seo_desc', '', $post_id)) : '';
    $desc = $custom !== '' ? $custom : $defaults['desc'];
    if ($desc === '' && is_singular('post')) {
        $desc = wp_trim_words(wp_strip_all_tags(get_the_excerpt() ?: get_the_title()), 28, '');
        if ($desc !== '' && ! str_ends_with($desc, '.')) {
            $desc .= '.';
        }
    }
    if ($desc === '') {
        return '';
    }

    return mh_seo_len($desc) > 155 ? mh_seo_clip($desc, 155) : $desc;
}

/**
 * Replace the document title with the theme's custom SEO title when one is available.
 *
 * Hooked to document_title, wpseo_title, rank_math/frontend/title, and aioseo_title.
 *
 * @since 3.1.0
 *
 * @param  mixed  $title  Original title string passed by the filter.
 * @return mixed Custom title string, or the original value when no override exists.
 */
function mh_filter_document_title($title)
{
    if (is_admin() || ! is_string($title)) {
        return $title;
    }
    $custom = mh_seo_document_title();

    return $custom !== '' ? $custom : $title;
}

/**
 * Replace the SEO plugin meta description with the theme's custom value when available.
 *
 * Hooked to wpseo_metadesc, rank_math/frontend/description, and aioseo_description.
 *
 * @since 3.1.0
 *
 * @param  mixed  $desc  Original description string passed by the filter.
 * @return mixed Custom description string, or the original value when no override exists.
 */
function mh_filter_meta_description($desc)
{
    if (is_admin() || ! is_string($desc)) {
        return $desc;
    }
    $custom = mh_seo_meta_description();

    return $custom !== '' ? $custom : $desc;
}

/**
 * Output the <meta name="description"> tag when no SEO plugin is active.
 *
 * Hooked to wp_head at priority 1. Skips output when a plugin already handles the tag.
 *
 * @since 3.1.0
 */
function mh_print_meta_description(): void
{
    if (is_admin() || mh_seo_plugin_prints_description()) {
        return;
    }
    $desc = mh_seo_meta_description();
    if ($desc === '') {
        return;
    }
    echo '<meta name="description" content="'.esc_attr($desc).'">'."\n";
}
