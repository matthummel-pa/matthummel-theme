<?php
/**
 * Block editor script.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Loads the sidebar. Nothing is enqueued on the front end.
 */
class Assets {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the sidebar on supported post types.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, mh_seo_post_types(), true ) ) {
			return;
		}
		$asset_path = MH_SEO_PATH . 'build/index.asset.php';
		if ( ! file_exists( $asset_path ) ) {
			return;
		}
		$asset = require $asset_path;
		wp_enqueue_script(
			'mh-seo-sidebar',
			MH_SEO_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		$style = MH_SEO_PATH . 'build/style-index.css';
		if ( file_exists( $style ) ) {
			wp_enqueue_style(
				'mh-seo-sidebar',
				MH_SEO_URL . 'build/style-index.css',
				array(),
				$asset['version']
			);
		}
		wp_set_script_translations( 'mh-seo-sidebar', 'mh-seo', MH_SEO_PATH . 'languages' );

		$settings = Settings::get();
		$weights  = apply_filters( 'mh_seo_score_weights', $settings['score_weights'] );
		if ( ! is_array( $weights ) ) {
			$weights = $settings['score_weights'];
		}
		$config = array(
			'weights'       => $weights,
			'thresholds'    => $settings['thresholds'],
			'siteHost'      => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
			'siteName'      => (string) get_bloginfo( 'name' ),
			'titleTemplate' => (string) $settings['title_template'],
			'homeUrl'       => home_url( '/' ),
			'thumbnails'    => array(),
		);
		foreach ( mh_seo_post_types() as $post_type ) {
			$config['thumbnails'][ $post_type ] = post_type_supports( $post_type, 'thumbnail' );
		}
		wp_add_inline_script(
			'mh-seo-sidebar',
			'window.mhSeo = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}
}
