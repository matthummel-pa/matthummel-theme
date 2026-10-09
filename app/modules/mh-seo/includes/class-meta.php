<?php
/**
 * Post meta for the SEO fields.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Registers meta and a classic editor box for the same fields.
 */
class Meta {
	/**
	 * Meta keys edited in the sidebar.
	 *
	 * @return array<string, string>
	 */
	public static function keys(): array {
		return array(
			'_mh_seo_title'           => 'string',
			'_mh_seo_description'     => 'string',
			'_mh_seo_canonical'       => 'string',
			'_mh_seo_noindex'         => 'string',
			'_mh_seo_sitemap_exclude' => 'string',
			'_mh_seo_og_image'        => 'integer',
			'_mh_seo_focus_keyword'   => 'string',
			'_mh_seo_score'           => 'integer',
			'_mh_seo_checks'          => 'string',
			'_mh_seo_primary_term'    => 'integer',
		);
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post', array( $this, 'save_box' ) );
	}

	/**
	 * Register meta for each supported post type.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( mh_seo_post_types() as $post_type ) {
			foreach ( self::keys() as $key => $type ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $type,
						'single'            => true,
						'show_in_rest'      => true,
						'auth_callback'     => static function ( $allowed, $meta_key, $post_id, ...$extra ) {
							unset( $allowed, $meta_key, $extra );
							return current_user_can( 'edit_post', (int) $post_id );
						},
						'sanitize_callback' => array( $this, 'sanitize' ),
					)
				);
			}
			register_rest_field(
				$post_type,
				'mh_seo_fallbacks',
				array(
					'get_callback' => array( $this, 'fallbacks' ),
					'schema'       => array(
						'description' => __( 'Titles and keywords already stored for this post.', 'mh-seo' ),
						'type'        => 'object',
						'context'     => array( 'edit' ),
					),
				)
			);
		}
	}

	/**
	 * Sanitize one meta value.
	 *
	 * @param mixed  $value       Raw value.
	 * @param string $meta_key    Meta key.
	 * @param string $object_type Object type. Unused.
	 * @param mixed  ...$extra    Extra arguments from WordPress. Unused.
	 * @return mixed
	 */
	public function sanitize( $value, string $meta_key = '', string $object_type = '', ...$extra ) {
		unset( $object_type, $extra );
		if ( '_mh_seo_noindex' === $meta_key || '_mh_seo_sitemap_exclude' === $meta_key ) {
			if ( true === $value || '1' === (string) $value || 1 === $value ) {
				return '1';
			}
			if ( false === $value || '0' === (string) $value || 0 === $value ) {
				return '0';
			}
			return '';
		}
		if ( '_mh_seo_og_image' === $meta_key || '_mh_seo_primary_term' === $meta_key ) {
			return absint( $value );
		}
		if ( '_mh_seo_score' === $meta_key ) {
			return max( 0, min( 100, absint( $value ) ) );
		}
		if ( '_mh_seo_checks' === $meta_key ) {
			return $this->sanitize_checks( (string) $value );
		}
		if ( '_mh_seo_canonical' === $meta_key ) {
			return esc_url_raw( (string) $value );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Keep only known failed-check ids.
	 *
	 * @param string $value JSON list.
	 * @return string
	 */
	private function sanitize_checks( string $value ): string {
		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return '[]';
		}
		$allowed = Score_Analyzer::check_ids();
		$clean   = array();
		foreach ( $decoded as $id ) {
			if ( is_string( $id ) && in_array( $id, $allowed, true ) ) {
				$clean[] = $id;
			}
		}
		$encoded = wp_json_encode( $clean );
		return is_string( $encoded ) ? $encoded : '[]';
	}

	/**
	 * Values the editor can show before MH SEO meta is filled in.
	 *
	 * @param array<string, mixed> $post    REST post array.
	 * @param string               $field   Field name. Unused.
	 * @param mixed                $request REST request. Unused.
	 * @return array<string, mixed>
	 */
	public function fallbacks( array $post, string $field = '', $request = null ): array {
		unset( $field, $request );
		$post_id = (int) ( $post['id'] ?? 0 );
		$object  = get_post( $post_id );
		if ( ! $object instanceof \WP_Post ) {
			return array();
		}
		$context                       = ( new Context() )->from_post( $object );
		$settings                      = Settings::get();
		$builder                       = new Document_Builder();
		$without                       = $context;
		$without['custom_title']       = '';
		$without['custom_description'] = '';
		$keyword                       = $this->keyword_fallback( $post_id );
		$robots                        = get_post_meta( $post_id, 'rank_math_robots', true );
		$noindex                       = is_array( $robots ) ? in_array( 'noindex', $robots, true ) : ( is_string( $robots ) && str_contains( $robots, 'noindex' ) );
		return array(
			'title'       => $builder->title( $without, $settings ),
			'description' => $builder->description( $without ),
			'keyword'     => $keyword,
			'noindex'     => $noindex,
		);
	}

	/**
	 * First Rank Math focus keyword, when MH SEO does not have one yet.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function keyword_fallback( int $post_id ): string {
		$own = trim( (string) get_post_meta( $post_id, '_mh_seo_focus_keyword', true ) );
		if ( '' !== $own ) {
			return '';
		}
		return ( new Migrator() )->first_keyword( (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
	}

	/**
	 * Classic editor box. The block editor uses the sidebar instead.
	 *
	 * @return void
	 */
	public function box(): void {
		foreach ( mh_seo_post_types() as $post_type ) {
			add_meta_box(
				'mh-seo',
				__( 'SEO', 'mh-seo' ),
				array( $this, 'render_box' ),
				$post_type,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Classic editor fields.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function render_box( \WP_Post $post ): void {
		wp_nonce_field( 'mh_seo_save_meta', 'mh_seo_meta_nonce' );
		$title       = (string) get_post_meta( $post->ID, '_mh_seo_title', true );
		$description = (string) get_post_meta( $post->ID, '_mh_seo_description', true );
		$keyword     = (string) get_post_meta( $post->ID, '_mh_seo_focus_keyword', true );
		$canonical   = (string) get_post_meta( $post->ID, '_mh_seo_canonical', true );
		$noindex     = (string) get_post_meta( $post->ID, '_mh_seo_noindex', true );
		$image       = (int) get_post_meta( $post->ID, '_mh_seo_og_image', true );
		echo '<p><label for="mh-seo-title"><strong>' . esc_html__( 'SEO title', 'mh-seo' ) . '</strong></label><br />';
		echo '<input class="widefat" type="text" id="mh-seo-title" name="mh_seo_title" value="' . esc_attr( $title ) . '" />';
		echo '<span class="description">' . esc_html__( 'Leave blank to use the title template. A title you type here is used exactly, with nothing added.', 'mh-seo' ) . '</span></p>';
		echo '<p><label for="mh-seo-description"><strong>' . esc_html__( 'Meta description', 'mh-seo' ) . '</strong></label><br />';
		echo '<textarea class="widefat" rows="3" id="mh-seo-description" name="mh_seo_description">' . esc_textarea( $description ) . '</textarea></p>';
		echo '<p><label for="mh-seo-keyword"><strong>' . esc_html__( 'Focus keyword', 'mh-seo' ) . '</strong></label><br />';
		echo '<input class="widefat" type="text" id="mh-seo-keyword" name="mh_seo_focus_keyword" value="' . esc_attr( $keyword ) . '" /></p>';
		echo '<p><label for="mh-seo-canonical"><strong>' . esc_html__( 'Canonical URL', 'mh-seo' ) . '</strong></label><br />';
		echo '<input class="widefat" type="url" id="mh-seo-canonical" name="mh_seo_canonical" value="' . esc_attr( $canonical ) . '" /></p>';
		echo '<p><label for="mh-seo-image"><strong>' . esc_html__( 'Social image ID', 'mh-seo' ) . '</strong></label><br />';
		echo '<input type="number" min="0" id="mh-seo-image" name="mh_seo_og_image" value="' . esc_attr( (string) $image ) . '" /></p>';
		$exclude = (string) get_post_meta( $post->ID, '_mh_seo_sitemap_exclude', true );
		echo '<p><label><input type="checkbox" name="mh_seo_noindex" value="1" ' . checked( '1', $noindex, false ) . ' /> ' . esc_html__( 'Hide from search engines', 'mh-seo' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="mh_seo_sitemap_exclude" value="1" ' . checked( '1', $exclude, false ) . ' /> ' . esc_html__( 'Leave this out of the sitemap', 'mh-seo' ) . '</label></p>';
		echo '<p class="description">' . esc_html__( 'Hiding a page from search engines also leaves it out of the sitemap. The sitemap checkbox is for a page that can stay indexed but should not be listed.', 'mh-seo' ) . '</p>';
	}

	/**
	 * Save the classic editor box.
	 *
	 * The block editor does not send this nonce, so it cannot wipe the REST meta.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function save_box( int $post_id ): void {
		if ( ! isset( $_POST['mh_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mh_seo_meta_nonce'] ) ), 'mh_seo_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = array(
			'mh_seo_title'         => '_mh_seo_title',
			'mh_seo_description'   => '_mh_seo_description',
			'mh_seo_focus_keyword' => '_mh_seo_focus_keyword',
			'mh_seo_canonical'     => '_mh_seo_canonical',
			'mh_seo_og_image'      => '_mh_seo_og_image',
		);
		foreach ( $fields as $posted => $key ) {
			$raw = isset( $_POST[ $posted ] ) ? wp_unslash( $_POST[ $posted ] ) : '';
			update_post_meta( $post_id, $key, $this->sanitize( $raw, $key, 'post' ) );
		}
		$noindex = isset( $_POST['mh_seo_noindex'] ) ? '1' : '0';
		update_post_meta( $post_id, '_mh_seo_noindex', $noindex );
		$exclude = isset( $_POST['mh_seo_sitemap_exclude'] ) ? '1' : '0';
		update_post_meta( $post_id, '_mh_seo_sitemap_exclude', $exclude );
	}
}
