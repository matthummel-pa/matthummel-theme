<?php
/**
 * The single plugin settings option.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Reads and sanitizes mh_seo_settings.
 */
class Settings {
	public const OPTION = 'mh_seo_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'title_template'       => '%title% | Matt Hummel',
			'head_output'          => 'auto',
			'person_name'          => 'Matt Hummel',
			'person_job_title'     => 'WordPress Developer',
			'person_image'         => '',
			'same_as'              => array( 'https://github.com/matthummel-pa' ),
			'twitter_site'         => '',
			'default_og_image'     => 491,
			'noindex_search'       => 1,
			'noindex_author'       => 1,
			'noindex_tag'          => 1,
			'noindex_date'         => 1,
			'noindex_attachment'   => 1,
			'noindex_404'          => 1,
			'noindex_empty_tax'    => 1,
			'redirect_attachments' => 1,
			'indexnow_enabled'     => 1,
			'indexnow_key'         => '4c8b0c3a27db4628ad675657129286af',
			'sitemap_enabled'      => 0,
			'sitemap_authors'      => 0,
			'sitemap_per_page'     => 1000,
			'sitemap_images'       => 1,
			'robots_extra'         => '',
			'log_404'              => 1,
			'score_weights'        => Score_Analyzer::default_weights(),
			'thresholds'           => Score_Analyzer::default_thresholds(),
		);
	}

	/**
	 * Saved settings merged onto the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings                  = array_merge( self::defaults(), $saved );
		$settings['score_weights'] = ( new Score_Analyzer() )->weights(
			is_array( $saved['score_weights'] ?? null ) ? $saved['score_weights'] : array()
		);
		$thresholds                = Score_Analyzer::default_thresholds();
		if ( is_array( $saved['thresholds'] ?? null ) ) {
			foreach ( $thresholds as $key => $value ) {
				if ( array_key_exists( $key, $saved['thresholds'] ) ) {
					$thresholds[ $key ] = $saved['thresholds'][ $key ];
				}
			}
		}
		$settings['thresholds'] = $thresholds;
		if ( ! is_array( $settings['same_as'] ) ) {
			$settings['same_as'] = self::defaults()['same_as'];
		}
		$settings['sitemap_per_page'] = Sitemap_Xml::per_page( (int) $settings['sitemap_per_page'] );
		return $settings;
	}

	/**
	 * Sanitize a settings form submission.
	 *
	 * Each tab sends a section name. Only that section is replaced, so saving
	 * one tab does not wipe the others. Absent checkboxes in the active section
	 * are stored as off.
	 *
	 * @param mixed $input Raw option value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$clean   = self::get();
		$section = '';
		if ( is_array( $input ) ) {
			$section = sanitize_key( (string) ( $input['section'] ?? '' ) );
		}
		if ( ! is_array( $input ) ) {
			return $clean;
		}

		if ( 'general' === $section ) {
			$template                = sanitize_text_field( (string) ( $input['title_template'] ?? '' ) );
			$clean['title_template'] = '' !== $template ? $template : '%title% | Matt Hummel';
			$mode                    = sanitize_key( (string) ( $input['head_output'] ?? 'auto' ) );
			$clean['head_output']    = in_array( $mode, array( 'auto', 'on', 'off' ), true ) ? $mode : 'auto';
			$clean['person_name']    = sanitize_text_field( (string) ( $input['person_name'] ?? 'Matt Hummel' ) );
			if ( '' === $clean['person_name'] ) {
				$clean['person_name'] = 'Matt Hummel';
			}
			$clean['person_job_title'] = sanitize_text_field( (string) ( $input['person_job_title'] ?? '' ) );
			$clean['person_image']     = esc_url_raw( (string) ( $input['person_image'] ?? '' ) );
			$clean['same_as']          = self::sanitize_urls( (string) ( $input['same_as'] ?? '' ) );
			$clean['twitter_site']     = self::sanitize_handle( (string) ( $input['twitter_site'] ?? '' ) );
			$clean['default_og_image'] = absint( $input['default_og_image'] ?? 0 );
		}

		if ( 'indexing' === $section ) {
			foreach ( self::boolean_keys() as $key ) {
				$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			}
			$clean['indexnow_key'] = self::sanitize_key( (string) ( $input['indexnow_key'] ?? '' ), (string) $clean['indexnow_key'] );
			$clean['robots_extra'] = self::sanitize_robots( (string) ( $input['robots_extra'] ?? '' ) );
		}

		if ( 'sitemaps' === $section ) {
			$clean['sitemap_enabled']    = empty( $input['sitemap_enabled'] ) ? 0 : 1;
			$clean['sitemap_authors']    = empty( $input['sitemap_authors'] ) ? 0 : 1;
			$clean['sitemap_images']     = empty( $input['sitemap_images'] ) ? 0 : 1;
			$clean['sitemap_per_page']   = Sitemap_Xml::per_page( absint( $input['sitemap_per_page'] ?? 1000 ) );
			$clean['sitemap_post_types'] = self::sanitize_post_types( $input['sitemap_post_types'] ?? array() );
			$clean['sitemap_taxonomies'] = self::sanitize_post_types( $input['sitemap_taxonomies'] ?? array() );
		}

		if ( 'score' === $section && ! empty( $input['reset_weights'] ) ) {
			$clean['score_weights'] = Score_Analyzer::default_weights();
			$clean['thresholds']    = Score_Analyzer::default_thresholds();
			unset( $clean['section'] );
			return $clean;
		}

		if ( 'score' === $section ) {
			$weights = Score_Analyzer::default_weights();
			$posted  = is_array( $input['score_weights'] ?? null ) ? $input['score_weights'] : array();
			foreach ( $weights as $id => $default ) {
				$weights[ $id ] = array_key_exists( $id, $posted ) ? max( 0, min( 100, absint( $posted[ $id ] ) ) ) : $default;
			}
			$clean['score_weights'] = $weights;

			$thresholds = Score_Analyzer::default_thresholds();
			$posted_t   = is_array( $input['thresholds'] ?? null ) ? $input['thresholds'] : array();
			foreach ( $thresholds as $key => $default ) {
				if ( ! array_key_exists( $key, $posted_t ) ) {
					continue;
				}
				$thresholds[ $key ] = is_float( $default ) || str_contains( (string) $posted_t[ $key ], '.' )
					? (float) $posted_t[ $key ]
					: (int) $posted_t[ $key ];
			}
			$clean['thresholds'] = $thresholds;
		}

		unset( $clean['section'] );
		return $clean;
	}

	/**
	 * Checkbox keys on the indexing tab.
	 *
	 * @return array<int, string>
	 */
	public static function boolean_keys(): array {
		return array(
			'noindex_search',
			'noindex_author',
			'noindex_tag',
			'noindex_date',
			'noindex_attachment',
			'noindex_404',
			'noindex_empty_tax',
			'redirect_attachments',
			'indexnow_enabled',
			'log_404',
		);
	}

	/**
	 * One valid URL per line.
	 *
	 * @param string $raw Textarea contents.
	 * @return array<int, string>
	 */
	private static function sanitize_urls( string $raw ): array {
		$urls  = array();
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		if ( ! is_array( $lines ) ) {
			$lines = array();
		}
		foreach ( $lines as $line ) {
			$url = esc_url_raw( trim( (string) $line ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Keep a Twitter handle shaped like @name.
	 *
	 * @param string $raw Submitted handle.
	 * @return string
	 */
	private static function sanitize_handle( string $raw ): string {
		$raw = sanitize_text_field( $raw );
		$raw = ltrim( $raw, '@' );
		$raw = (string) preg_replace( '/[^A-Za-z0-9_]/', '', $raw );
		if ( '' === $raw ) {
			return '';
		}
		return '@' . $raw;
	}

	/**
	 * IndexNow keys are 8 to 128 letters, numbers, or dashes.
	 *
	 * @param string $raw      Submitted key.
	 * @param string $fallback Previous valid key.
	 * @return string
	 */
	private static function sanitize_key( string $raw, string $fallback ): string {
		$raw    = (string) preg_replace( '/[^A-Za-z0-9-]/', '', $raw );
		$length = strlen( $raw );
		if ( $length < 8 || $length > 128 ) {
			return $fallback;
		}
		return $raw;
	}

	/**
	 * Extra robots.txt lines, tags removed.
	 *
	 * @param string $raw Submitted text.
	 * @return string
	 */
	private static function sanitize_robots( string $raw ): string {
		$raw = wp_strip_all_tags( $raw );
		if ( strlen( $raw ) > 2000 ) {
			$raw = substr( $raw, 0, 2000 );
		}
		return trim( $raw );
	}

	/**
	 * Public post type names to keep in the sitemap.
	 *
	 * @param mixed $raw Submitted list.
	 * @return array<int, string>
	 */
	private static function sanitize_post_types( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$types = array();
		foreach ( $raw as $type ) {
			$type = sanitize_key( (string) $type );
			if ( '' !== $type && 'attachment' !== $type ) {
				$types[] = $type;
			}
		}
		return array_values( array_unique( $types ) );
	}
}
