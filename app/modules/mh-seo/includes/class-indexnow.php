<?php
/**
 * IndexNow pings and the public key file.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Tells IndexNow when a post is published or updated, and serves the key file.
 */
class IndexNow {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'transition_post_status', array( $this, 'on_publish' ), 10, 3 );
		add_action( 'post_updated', array( $this, 'on_update' ), 20, 3 );
		add_action( 'template_redirect', array( $this, 'serve_key' ), 0 );
	}

	/**
	 * Ping the first time a post, page, or project is published.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return void
	 */
	public function on_publish( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		$this->ping( $post );
	}

	/**
	 * Ping when a published post is updated.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $after   Updated post.
	 * @param \WP_Post $before  Previous post.
	 * @return void
	 */
	public function on_update( int $post_id, \WP_Post $after, \WP_Post $before ): void {
		unset( $post_id );
		if ( 'publish' !== $after->post_status || 'publish' !== $before->post_status ) {
			return;
		}
		$this->ping( $after );
	}

	/**
	 * Serve /{key}.txt with the key as the body.
	 *
	 * @return void
	 */
	public function serve_key(): void {
		if ( ! $this->enabled() ) {
			return;
		}
		$key = (string) ( Settings::get()['indexnow_key'] ?? '' );
		if ( '' === $key ) {
			return;
		}
		$path = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$path = (string) strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' );
		}
		if ( '/' . $key . '.txt' !== $path ) {
			return;
		}
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo esc_html( $key );
		exit;
	}

	/**
	 * Send one URL to IndexNow.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	private function ping( \WP_Post $post ): void {
		if ( ! $this->enabled() || wp_is_post_revision( $post ) || wp_is_post_autosave( $post->ID ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'post', 'page', 'project' ), true ) ) {
			return;
		}
		$key = (string) ( Settings::get()['indexnow_key'] ?? '' );
		$url = get_permalink( $post );
		if ( '' === $key || ! is_string( $url ) || '' === $url ) {
			return;
		}
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'timeout'  => 5,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'     => wp_json_encode(
					array(
						'host'        => $host,
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => array( $url ),
					)
				),
			)
		);
	}

	/**
	 * IndexNow runs only while MH SEO is printing the head, so it does not double-ping Rank Math.
	 *
	 * @return bool
	 */
	private function enabled(): bool {
		return mh_seo_is_managing_head() && ! empty( Settings::get()['indexnow_enabled'] );
	}
}
