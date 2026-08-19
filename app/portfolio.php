<?php

/**
 * Portfolio content, social defaults, DEV.to feed, and one-time page seed.
 * Does not delete posts or categories.
 */

namespace App;

function mh_github_login(): string
{
    return 'matthummel-pa';
}

function mh_portfolio_social_defaults(): array
{
    return [
        'github' => 'https://github.com/matthummel-pa',
        'linkedin' => 'https://www.linkedin.com/in/matt-hummel-pa',
        'devto' => 'https://dev.to/matthummel',
        'bluesky' => 'https://bsky.app/profile/matthummel.bsky.social',
        'reddit' => 'https://www.reddit.com/user/matt-hummel',
        'rss' => home_url('/feed/'),
    ];
}

function mh_social_links(): array
{
    $labels = [
        'github' => 'GitHub',
        'linkedin' => 'LinkedIn',
        'devto' => 'DEV.to',
        'bluesky' => 'Bluesky',
        'reddit' => 'Reddit',
        'rss' => 'RSS',
    ];

    $links = [];
    foreach (mh_portfolio_social_defaults() as $key => $url) {
        if ($url === '') {
            continue;
        }
        $links[] = [
            'key' => $key,
            'label' => $labels[$key] ?? ucfirst($key),
            'url' => $url,
        ];
    }

    return $links;
}

/** Featured GitHub codebases to highlight on Code and Home. */
function mh_featured_repos(): array
{
    return [
        [
            'name' => 'keepary',
            'desc' => 'A private family app I built end to end. React, Vite, Tailwind, and Supabase. Real sign-in, invites, and posts.',
            'url' => 'https://github.com/matthummel-pa/keepary',
            'tags' => ['React', 'Supabase', 'Full-stack'],
        ],
        [
            'name' => 'tocflow',
            'desc' => 'A free WordPress table of contents block. It reads your headings and builds a clear, accessible outline.',
            'url' => 'https://github.com/matthummel-pa/tocflow',
            'tags' => ['WordPress', 'Gutenberg', 'PHP'],
        ],
        [
            'name' => 'ridgesandvalleys',
            'desc' => 'The Ridges & Valleys Studio site. Sage 11, local SEO, accessibility, and Gettysburg work.',
            'url' => 'https://github.com/matthummel-pa/ridgesandvalleys',
            'tags' => ['WordPress', 'Sage', 'Local SEO'],
        ],
    ];
}

/** Featured repos plus recent public GitHub work (forks and the profile repo skipped). */
function mh_home_github_repos(int $limit = 6): array
{
    $featured = mh_code_page_repos();
    $live = Github::fetchRepos(mh_github_login(), 12, 'updated');
    $names = array_map(static fn ($r) => strtolower((string) ($r['name'] ?? '')), $featured);
    foreach ($live as $r) {
        $name = strtolower((string) ($r['name'] ?? ''));
        if ($name === '' || in_array($name, $names, true)) {
            continue;
        }
        $featured[] = $r;
        $names[] = $name;
    }

    return array_slice($featured, 0, max(1, $limit));
}

/** Ridges & Valleys concept work for the Projects page. */
function mh_studio_projects(): array
{
    return [
        ['slug' => 'hallowed-ground', 'title' => 'Hallowed Ground Battlefield Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'A licensed-guide site with day and after-dark paths, five tours, and a battlefield map. Built as a Sage and WooCommerce handoff.', 'tech' => ['Sage', 'WooCommerce', 'Maps']],
        ['slug' => 'first-shot', 'title' => 'First Shot Food & History Tours', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'A walking-tour site with a calendar booking flow, add-ons, and a demo payment path.', 'tech' => ['WordPress', 'Bookings']],
        ['slug' => 'field-of-valor', 'title' => 'Field of Valor History Co.', 'cat' => 'Tours', 'place' => 'Gettysburg, PA', 'blurb' => 'An editorial history-tour site for Gettysburg travelers. Clear tours, easy contact.', 'tech' => ['WordPress', 'SEO']],
        ['slug' => 'keystone-homes', 'title' => 'Keystone Homes & Land', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'Land and farms listings with grid and map views, acreage filters, and financing tools.', 'tech' => ['WordPress', 'Maps', 'Filters']],
        ['slug' => 'ridgeline-realty', 'title' => 'Ridgeline Realty', 'cat' => 'Real estate', 'place' => 'Gettysburg, PA', 'blurb' => 'A realty concept with filterable listings and a live mortgage calculator.', 'tech' => ['WordPress', 'JavaScript']],
        ['slug' => 'ridgeline-outfitters', 'title' => 'Ridgeline Outfitters', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'Outdoor gear shop with filters, quick view, a wishlist, and a free-shipping bar in the cart.', 'tech' => ['WooCommerce']],
        ['slug' => 'diamond-ridge', 'title' => 'Diamond & Ridge Mercantile', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A downtown shop concept with a full product catalog, slide-in cart, and checkout.', 'tech' => ['WooCommerce']],
        ['slug' => 'diamonds-threads', 'title' => 'Diamonds & Threads', 'cat' => 'Retail', 'place' => 'Gettysburg, PA', 'blurb' => 'A vintage-inspired boutique site for Adams County. Character first, still easy to shop.', 'tech' => ['WooCommerce']],
        ['slug' => 'cannon-crumb', 'title' => 'Cannon & Crumb', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'An all-day cafe with a filterable menu and a real online-ordering cart.', 'tech' => ['WordPress', 'Ordering']],
        ['slug' => 'field-musket', 'title' => 'Field & Musket Tavern', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A farm-to-table tavern with a seasonal menu and a clear reservation path.', 'tech' => ['WordPress']],
        ['slug' => 'pintfield', 'title' => 'Pintfield Creamery', 'cat' => 'Restaurants', 'place' => 'Gettysburg & Adams County, PA', 'blurb' => 'A creamery site with a live 32-flavor scoop board, online ordering, and three locations.', 'tech' => ['WordPress', 'Ordering']],
        ['slug' => 'reveille', 'title' => 'Reveille Kitchen & Bar', 'cat' => 'Restaurants', 'place' => 'Gettysburg, PA', 'blurb' => 'A downtown farm-to-table restaurant. Menu first, with online reservations.', 'tech' => ['WordPress']],
        ['slug' => 'cupola-field', 'title' => 'The Cupola & Field Hotel', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A modern boutique hotel with a live reservation engine and guest-request tools.', 'tech' => ['WordPress', 'Bookings']],
        ['slug' => 'lantern-laurel', 'title' => 'The Lantern & Laurel Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A nine-room heritage inn with a direct booking bar and a warm, quiet identity.', 'tech' => ['WordPress', 'Bookings']],
        ['slug' => 'willoughby', 'title' => 'Willoughby Run Inn', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'An elegant boutique-inn concept built to win direct bookings in Gettysburg.', 'tech' => ['WordPress', 'Bookings']],
        ['slug' => 'herr-ridge', 'title' => 'Herr Ridge Cottage B&B', 'cat' => 'Hotels', 'place' => 'Gettysburg, PA', 'blurb' => 'A cozy bed-and-breakfast site that turns searches into booked stays.', 'tech' => ['WordPress', 'SEO']],
    ];
}

function mh_studio_project_categories(): array
{
    $cats = array_unique(array_map(fn ($p) => $p['cat'], mh_studio_projects()));
    sort($cats);

    return $cats;
}

function mh_code_snippets(): array
{
    return [
        [
            'title' => 'A tiny WordPress shortcode',
            'lang' => 'php',
            'note' => 'Paste into a plugin or your theme PHP. Then type [hello] in a post. Good first snippet for a blog.',
            'code' => "add_shortcode('hello', function () {\n    return '<p>Hello from a shortcode.</p>';\n});",
        ],
        [
            'title' => 'Skip to content that actually works',
            'lang' => 'html',
            'note' => 'Keep the first focusable control a skip link. Hide it until it has focus.',
            'code' => '<a class="skip-link" href="#main">Skip to main content</a>',
        ],
        [
            'title' => 'Readable line length',
            'lang' => 'css',
            'note' => 'Cap body text around 65 characters. Short lines are easier at a 6–8 grade level.',
            'code' => ".prose {\n  max-width: 65ch;\n  font-size: 1.125rem;\n  line-height: 1.7;\n}",
        ],
        [
            'title' => 'One brand color, reused',
            'lang' => 'css',
            'note' => 'Change --brand once. Links and buttons can share it. Same idea as this theme.',
            'code' => ":root {\n  --brand: #2563EB;\n}\n\na {\n  color: var(--brand);\n}",
        ],
        [
            'title' => 'Safe link in Blade',
            'lang' => 'blade',
            'note' => 'Escape URLs and labels you did not write yourself.',
            'code' => '<a href="{{ esc_url($url) }}">{{ esc_html($label) }}</a>',
        ],
        [
            'title' => 'Get the current page title in Blade',
            'lang' => 'blade',
            'note' => 'Sage templates already have $title. Use get_the_title() if you are not on a standard loop.',
            'code' => "{{ \$title }}\n{{-- or --}}\n{{ get_the_title() }}",
        ],
        [
            'title' => 'Fetch GitHub without hammering the API',
            'lang' => 'php',
            'note' => 'Cache the response. Respect rate limits. Show a fallback if GitHub is down.',
            'code' => "\$key = 'mh_ghu_' . md5(\$user);\n\$data = get_transient(\$key);\nif (false === \$data) {\n    \$data = mh_fetch_github_user(\$user);\n    set_transient(\$key, \$data, 6 * HOUR_IN_SECONDS);\n}",
        ],
    ];
}

function mh_devto_posts(int $limit = 5): array
{
    $key = 'mh_devto_feed_v1';
    $cached = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limit);
    }

    $posts = [];
    $res = wp_remote_get('https://dev.to/feed/matthummel', [
        'timeout' => 8,
        'headers' => ['User-Agent' => 'matthummel.com'],
    ]);

    if (! is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
        $body = wp_remote_retrieve_body($res);
        if ($body && class_exists(\SimpleXMLElement::class)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $posts[] = [
                        'title' => (string) $item->title,
                        'url' => (string) $item->link,
                        'date' => (string) $item->pubDate,
                    ];
                }
            }
        }
    }

    set_transient($key, $posts, 3 * HOUR_IN_SECONDS);

    return array_slice($posts, 0, $limit);
}

function mh_latest_posts(int $limit = 3): array
{
    $q = new \WP_Query([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
    $out = [];
    foreach ($q->posts as $p) {
        $cats = get_the_category($p->ID);
        $out[] = [
            'title' => get_the_title($p),
            'url' => get_permalink($p),
            'date' => get_the_date('', $p),
            'ex' => wp_trim_words(get_the_excerpt($p), 28),
            'cat' => ($cats && ! is_wp_error($cats)) ? $cats[0]->name : '',
        ];
    }
    wp_reset_postdata();

    return $out;
}

/**
 * Create standard portfolio pages once. Never deletes posts or categories.
 */
function mh_seed_portfolio_pages(): void
{
    if (get_option('mh_portfolio_seeded_v2')) {
        return;
    }

    $pages = [
        'home' => ['title' => 'Home', 'template' => 'template-home.blade.php'],
        'about' => ['title' => 'About', 'template' => 'template-about.blade.php'],
        'projects' => ['title' => 'Work', 'template' => 'template-projects.blade.php'],
        'services' => ['title' => 'Services', 'template' => 'template-services.blade.php'],
        'code' => ['title' => 'Code', 'template' => 'template-code.blade.php'],
        'contact' => ['title' => 'Contact', 'template' => 'template-contact.blade.php'],
        'now' => ['title' => 'Now', 'template' => 'template-now.blade.php'],
    ];

    $ids = [];
    foreach ($pages as $slug => $meta) {
        $existing = get_page_by_path($slug);
        if ($existing instanceof \WP_Post) {
            $ids[$slug] = $existing->ID;
            update_post_meta($existing->ID, '_wp_page_template', $meta['template']);

            continue;
        }
        $id = wp_insert_post([
            'post_title' => $meta['title'],
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($id && ! is_wp_error($id)) {
            $ids[$slug] = (int) $id;
            update_post_meta($id, '_wp_page_template', $meta['template']);
        }
    }

    $blog = get_page_by_path('blog');
    if (! $blog) {
        $blogId = wp_insert_post([
            'post_title' => 'Writing',
            'post_name' => 'blog',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($blogId && ! is_wp_error($blogId)) {
            $ids['blog'] = (int) $blogId;
        }
    } else {
        $ids['blog'] = $blog->ID;
        wp_update_post(['ID' => $blog->ID, 'post_title' => 'Writing']);
    }

    if (! empty($ids['home']) && ! empty($ids['blog'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids['home']);
        update_option('page_for_posts', $ids['blog']);
    }

    $menuName = 'Primary';
    $menu = wp_get_nav_menu_object($menuName);
    if (! $menu) {
        $menuId = wp_create_nav_menu($menuName);
    } else {
        $menuId = (int) $menu->term_id;
        $items = wp_get_nav_menu_items($menuId);
        if (is_array($items)) {
            foreach ($items as $item) {
                wp_delete_post((int) $item->ID, true);
            }
        }
    }

    $order = ['home', 'about', 'projects', 'services', 'code', 'blog', 'contact'];
    $n = 1;
    foreach ($order as $slug) {
        if (empty($ids[$slug])) {
            continue;
        }
        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-title' => get_the_title($ids[$slug]),
            'menu-item-object' => 'page',
            'menu-item-object-id' => $ids[$slug],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $n++,
        ]);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['primary_navigation'] = (int) $menuId;
    set_theme_mod('nav_menu_locations', $locations);

    foreach (mh_portfolio_social_defaults() as $key => $url) {
        $current = get_theme_mod("mh_social_{$key}", '');
        if ($current === '' || $current === 'https://www.linkedin.com/in/matthummel') {
            set_theme_mod("mh_social_{$key}", $url);
        }
    }

    update_option('mh_portfolio_seeded_v2', true);
}

/**
 * Profile photo: Customizer upload, then bundled headshot, then GitHub, then Gravatar.
 */
function mh_profile_photo_url(int $size = 160): string
{
    $id = (int) get_theme_mod('mh_profile_photo', 0);
    if ($id > 0) {
        $src = wp_get_attachment_image_url($id, [$size, $size]);
        if (is_string($src) && $src !== '') {
            return $src;
        }
    }

    $rel = 'resources/images/matt-hummel.jpg';
    if (is_readable(get_theme_file_path($rel))) {
        return get_theme_file_uri($rel);
    }

    $user = Github::fetchUser(mh_github_login());
    if (! empty($user['avatar'])) {
        $sep = str_contains((string) $user['avatar'], '?') ? '&' : '?';

        return $user['avatar'].$sep.'s='.$size;
    }

    $email = (string) get_option('admin_email');

    return $email !== '' ? (string) get_avatar_url($email, ['size' => $size]) : '';
}

add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    $wp->add_section('mh_identity', [
        'title' => __('Profile photo', 'sage'),
        'priority' => 32,
    ]);
    $wp->add_setting('mh_profile_photo', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp->add_control(new \WP_Customize_Media_Control($wp, 'mh_profile_photo', [
        'label' => __('Photo', 'sage'),
        'description' => __('Used next to your name in the header, and larger on Home and About. A square crop works best. Leave empty to keep the photo bundled with the theme.', 'sage'),
        'section' => 'mh_identity',
        'mime_type' => 'image',
    ]));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'mh-fonts',
        'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap',
        [],
        null
    );
}, 5);

add_action('after_switch_theme', __NAMESPACE__.'\\mh_seed_portfolio_pages');
add_action('init', function () {
    if (! get_option('mh_portfolio_seeded_v2') && ! wp_installing()) {
        mh_seed_portfolio_pages();
    }
}, 30);

add_filter('matthummel/cta_heading', fn () => __('Have a small project in mind?', 'matthummel'));
add_filter('matthummel/cta_text', fn () => __('I take a few WordPress, Power Platform, and full-stack jobs on the side. Write a short note and I will reply in one or two business days.', 'matthummel'));
add_filter('matthummel/cta_label', fn () => __('Get in touch', 'matthummel'));
