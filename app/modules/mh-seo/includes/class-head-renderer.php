<?php
/**
 * Prints escaped head tags.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Renders description, canonical, Open Graph, Twitter, and JSON-LD.
 *
 * The robots tag is left to WordPress core via the wp_robots filter.
 */
class Head_Renderer {
	/**
	 * Render the tags for one document.
	 *
	 * @param array<string, mixed> $document Built document.
	 * @return string
	 */
	public function render( array $document ): string {
		$html        = '';
		$description = trim( (string) ( $document['description'] ?? '' ) );
		if ( '' !== $description ) {
			$html .= '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		if ( ! empty( $document['emit_canonical'] ) ) {
			$canonical = trim( (string) ( $document['canonical'] ?? '' ) );
			if ( '' !== $canonical ) {
				$html .= '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
			}
		}

		$og = is_array( $document['og'] ?? null ) ? $document['og'] : array();
		foreach ( $og as $property => $content ) {
			$content = trim( (string) $content );
			if ( '' === $content ) {
				continue;
			}
			if ( 'article:tag' === $property ) {
				continue;
			}
			$html .= '<meta property="' . esc_attr( (string) $property ) . '" content="' . esc_attr( $content ) . '" />' . "\n";
		}
		$tags = $document['tags'] ?? array();
		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				$tag = trim( (string) $tag );
				if ( '' === $tag ) {
					continue;
				}
				$html .= '<meta property="article:tag" content="' . esc_attr( $tag ) . '" />' . "\n";
			}
		}

		$twitter = is_array( $document['twitter'] ?? null ) ? $document['twitter'] : array();
		foreach ( $twitter as $name => $content ) {
			$content = trim( (string) $content );
			if ( '' === $content ) {
				continue;
			}
			$html .= '<meta name="' . esc_attr( (string) $name ) . '" content="' . esc_attr( $content ) . '" />' . "\n";
		}

		if ( ! empty( $document['schema'] ) && is_array( $document['schema'] ) ) {
			$json = wp_json_encode(
				$document['schema'],
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			);
			if ( is_string( $json ) && '' !== $json ) {
				$html .= '<script type="application/ld+json">' . $json . '</script>' . "\n";
			}
		}

		return $html;
	}
}
