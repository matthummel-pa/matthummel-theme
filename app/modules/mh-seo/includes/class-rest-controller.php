<?php
/**
 * REST route for the keyword uniqueness check.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * GET /mh-seo/v1/keyword-check
 */
class Rest_Controller {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'mh-seo/v1',
			'/keyword-check',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'check' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'keyword' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'exclude' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Look up the keyword.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function check( \WP_REST_Request $request ): \WP_REST_Response {
		$keyword = (string) $request->get_param( 'keyword' );
		$exclude = (int) $request->get_param( 'exclude' );
		$found   = ( new Score() )->conflicts( $keyword, $exclude );
		return rest_ensure_response(
			array(
				'unique' => array() === $found,
				'posts'  => $found,
			)
		);
	}
}
