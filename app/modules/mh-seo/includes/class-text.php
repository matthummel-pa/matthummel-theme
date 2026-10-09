<?php
/**
 * Plain-text helpers for the score checks.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Counts words, sentences, and syllables in English prose.
 */
class Text {
	/**
	 * Strip code, scripts, and styles so they do not affect the score.
	 *
	 * @param string $html Post HTML.
	 * @return string
	 */
	public static function prose_html( string $html ): string {
		$html = (string) preg_replace( '#<pre\b[^>]*>.*?</pre>#is', ' ', $html );
		$html = (string) preg_replace( '#<script\b[^>]*>.*?</script>#is', ' ', $html );
		$html = (string) preg_replace( '#<style\b[^>]*>.*?</style>#is', ' ', $html );
		return $html;
	}

	/**
	 * Visible text with collapsed whitespace.
	 *
	 * @param string $html HTML or plain text.
	 * @return string
	 */
	public static function plain( string $html ): string {
		$html = (string) preg_replace( '/<\/(?:p|div|li|h[1-6]|tr|blockquote)>/i', ' ', $html );
		$html = (string) preg_replace( '/<br\s*\/?>/i', ' ', $html );
		// Scoring text, not HTML sent to the browser. strip_tags keeps this class usable without WordPress loaded.
		$text = trim( (string) preg_replace( '/\s+/u', ' ', strip_tags( $html ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		return $text;
	}

	/**
	 * Words in a string.
	 *
	 * @param string $text Plain text.
	 * @return array<int, string>
	 */
	public static function words( string $text ): array {
		$text = trim( $text );
		if ( '' === $text ) {
			return array();
		}
		$parts = preg_split( '/\s+/u', $text );
		if ( ! is_array( $parts ) ) {
			return array();
		}
		return array_values( array_filter( $parts, static fn( string $word ): bool => '' !== $word ) );
	}

	/**
	 * Sentence count. A block of words with no punctuation counts as one sentence.
	 *
	 * @param string $text Plain text.
	 * @return int
	 */
	public static function sentences( string $text ): int {
		$text = trim( $text );
		if ( '' === $text ) {
			return 0;
		}
		$parts = preg_split( '/[.!?]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return 1;
		}
		$count = 0;
		foreach ( $parts as $part ) {
			if ( '' !== trim( (string) $part ) ) {
				++$count;
			}
		}
		return max( 1, $count );
	}

	/**
	 * English syllable estimate for one word.
	 *
	 * @param string $word A single word.
	 * @return int
	 */
	public static function syllables( string $word ): int {
		$word = strtolower( (string) preg_replace( '/[^a-z]/i', '', $word ) );
		if ( '' === $word ) {
			return 0;
		}
		if ( strlen( $word ) <= 3 ) {
			return 1;
		}
		$word  = (string) preg_replace( '/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word );
		$word  = (string) preg_replace( '/^y/', '', $word );
		$found = preg_match_all( '/[aeiouy]{1,}/', $word );
		return max( 1, (int) $found );
	}

	/**
	 * Syllables across every word.
	 *
	 * @param array<int, string> $words Words.
	 * @return int
	 */
	public static function syllable_total( array $words ): int {
		$total = 0;
		foreach ( $words as $word ) {
			$total += self::syllables( $word );
		}
		return $total;
	}

	/**
	 * Flesch–Kincaid grade level, or null when the text is too short to score.
	 *
	 * @param string $text Plain prose.
	 * @return float|null
	 */
	public static function flesch_kincaid_grade( string $text ): ?float {
		$words = self::words( $text );
		$count = count( $words );
		if ( $count < 1 ) {
			return null;
		}
		$sentences = self::sentences( $text );
		if ( $sentences < 1 ) {
			return null;
		}
		$syllables = self::syllable_total( $words );
		return ( 0.39 * ( $count / $sentences ) ) + ( 11.8 * ( $syllables / $count ) ) - 15.59;
	}

	/**
	 * Case-insensitive phrase search.
	 *
	 * @param string $haystack Text to search.
	 * @param string $keyword  Focus keyword.
	 * @return int Byte-safe character offset, or -1 when missing. Uses mb_stripos.
	 */
	public static function keyword_position( string $haystack, string $keyword ): int {
		$keyword = trim( $keyword );
		if ( '' === $keyword || '' === $haystack ) {
			return -1;
		}
		$pos = mb_stripos( $haystack, $keyword );
		return false === $pos ? -1 : (int) $pos;
	}

	/**
	 * How many times the keyword phrase appears.
	 *
	 * @param string $haystack Text to search.
	 * @param string $keyword  Focus keyword.
	 * @return int
	 */
	public static function keyword_count( string $haystack, string $keyword ): int {
		$keyword = trim( $keyword );
		if ( '' === $keyword || '' === $haystack ) {
			return 0;
		}
		$found = preg_match_all( '/' . preg_quote( $keyword, '/' ) . '/iu', $haystack );
		return false === $found ? 0 : (int) $found;
	}

	/**
	 * Turn a keyword into a slug fragment.
	 *
	 * @param string $text Keyword or title.
	 * @return string
	 */
	public static function slugify( string $text ): string {
		$text = strtolower( trim( $text ) );
		$text = (string) preg_replace( '/[^a-z0-9]+/', '-', $text );
		return trim( $text, '-' );
	}
}
