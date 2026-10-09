<?php
/**
 * Maps Rank Math post meta onto MH SEO meta.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Plans a copy from Rank Math keys to MH SEO keys. It never deletes the old keys.
 */
class Migrator {
	/**
	 * Plan meta writes for one post.
	 *
	 * Existing MH SEO values are left alone so a second run does not overwrite edits.
	 *
	 * @param array<string, string> $source    Rank Math meta values keyed by meta key.
	 * @param array<string, string> $existing  Current MH SEO meta values.
	 * @param string                $post_title Post title, used when a Rank Math title still has tokens.
	 * @param string                $site_name  Site name, used for the %sitename% token.
	 * @return array<int, array{key: string, value: string, source: string}>
	 */
	public function plan( array $source, array $existing, string $post_title, string $site_name ): array {
		$changes = array();

		$title = $this->expand( trim( (string) ( $source['rank_math_title'] ?? '' ) ), $post_title, $site_name );
		$this->add( $changes, '_mh_seo_title', $title, (string) ( $existing['_mh_seo_title'] ?? '' ), (string) ( $source['rank_math_title'] ?? '' ) );

		$description = trim( (string) ( $source['rank_math_description'] ?? '' ) );
		if ( str_contains( $description, '%' ) ) {
			$description = '';
		}
		$this->add( $changes, '_mh_seo_description', $description, (string) ( $existing['_mh_seo_description'] ?? '' ), (string) ( $source['rank_math_description'] ?? '' ) );

		$keyword = $this->first_keyword( (string) ( $source['rank_math_focus_keyword'] ?? '' ) );
		$this->add( $changes, '_mh_seo_focus_keyword', $keyword, (string) ( $existing['_mh_seo_focus_keyword'] ?? '' ), (string) ( $source['rank_math_focus_keyword'] ?? '' ) );

		$robots = (string) ( $source['rank_math_robots'] ?? '' );
		if ( str_contains( $robots, 'noindex' ) ) {
			$this->add( $changes, '_mh_seo_noindex', '1', (string) ( $existing['_mh_seo_noindex'] ?? '' ), $robots );
		}

		$primary = (int) ( $source['rank_math_primary_category'] ?? 0 );
		if ( $primary > 0 ) {
			$this->add( $changes, '_mh_seo_primary_term', (string) $primary, (string) ( $existing['_mh_seo_primary_term'] ?? '' ), (string) $primary );
		}

		return $changes;
	}

	/**
	 * First keyword in a comma-separated list.
	 *
	 * @param string $value Stored keyword value.
	 * @return string
	 */
	public function first_keyword( string $value ): string {
		$parts = array_map( 'trim', explode( ',', $value ) );
		return (string) ( $parts[0] ?? '' );
	}

	/**
	 * Replace the Rank Math tokens this site actually stores.
	 *
	 * @param string $value      Stored title.
	 * @param string $post_title Post title.
	 * @param string $site_name  Site name.
	 * @return string Empty when an unknown token is left over.
	 */
	public function expand( string $value, string $post_title, string $site_name ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( ! str_contains( $value, '%' ) ) {
			return $value;
		}
		$expanded = str_ireplace(
			array( '%title%', '%sitename%', '%sep%', '%page%', '%excerpt%', '%sitedesc%' ),
			array( $post_title, $site_name, '|', '', '', '' ),
			$value
		);
		$expanded = trim( (string) preg_replace( '/\s+/u', ' ', $expanded ) );
		$expanded = trim( $expanded, "| \t" );
		if ( str_contains( $expanded, '%' ) ) {
			return '';
		}
		return $expanded;
	}

	/**
	 * Append a change when the new value is useful and the destination is empty.
	 *
	 * @param array<int, array{key: string, value: string, source: string}> $changes  Change list.
	 * @param string                                                        $key      Destination meta key.
	 * @param string                                                        $value    New value.
	 * @param string                                                        $existing Current MH SEO value.
	 * @param string                                                        $source   Original Rank Math value.
	 * @return void
	 */
	private function add( array &$changes, string $key, string $value, string $existing, string $source ): void {
		if ( '' === $value || '' !== $existing ) {
			return;
		}
		$changes[] = array(
			'key'    => $key,
			'value'  => $value,
			'source' => $source,
		);
	}
}
