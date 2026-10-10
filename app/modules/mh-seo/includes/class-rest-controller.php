<?php

/**
 * REST route for the keyword uniqueness check.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * GET /mh-seo/v1/keyword-check
 */
class Rest_Controller
{
    /**
     * Register hooks.
     */
    public function hooks(): void
    {
        add_action('rest_api_init', [$this, 'register']);
    }

    /**
     * Register the route.
     */
    public function register(): void
    {
        register_rest_route(
            'mh-seo/v1',
            '/keyword-check',
            [
                'methods' => 'GET',
                'callback' => [$this, 'check'],
                'permission_callback' => static function () {
                    return current_user_can('edit_posts');
                },
                'args' => [
                    'keyword' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'exclude' => [
                        'type' => 'integer',
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /**
     * Look up the keyword.
     *
     * @param  \WP_REST_Request  $request  Request.
     */
    public function check(\WP_REST_Request $request): \WP_REST_Response
    {
        $keyword = (string) $request->get_param('keyword');
        $exclude = (int) $request->get_param('exclude');
        $found = (new Score)->conflicts($keyword, $exclude);

        return rest_ensure_response(
            [
                'unique' => $found === [],
                'posts' => $found,
            ]
        );
    }
}
