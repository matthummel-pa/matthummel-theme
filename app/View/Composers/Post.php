<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Post extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.page-header',
        'partials.content',
        'partials.content-*',
    ];

    /**
     * Retrieve the contextual page title for the current view.
     *
     * Handles home, archive, search, and 404 contexts for the page-header partial.
     */
    public function title(): string
    {
        if ($this->view->name() !== 'partials.page-header') {
            return get_the_title();
        }

        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }

            return __('Latest Posts', 'sage');
        }

        if (is_archive()) {
            return get_the_archive_title();
        }

        if (is_search()) {
            return sprintf(
                /* translators: %s is replaced with the search query */
                __('Search Results for %s', 'sage'),
                get_search_query()
            );
        }

        if (is_404()) {
            return __('Not Found', 'sage');
        }

        return get_the_title();
    }

    /**
     * Retrieve paginated page links for multi-page posts.
     *
     * @return string HTML link list wrapped in <p> tags, or empty string for single-page posts.
     */
    public function pagination(): string
    {
        return wp_link_pages([
            'echo' => 0,
            'before' => '<p>'.__('Pages:', 'sage'),
            'after' => '</p>',
        ]);
    }
}
