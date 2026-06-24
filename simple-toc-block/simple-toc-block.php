<?php
/**
 * Plugin Name:       Simple TOC Block
 * Description:       A lightweight Table of Contents block that auto-generates a linked outline from your post headings.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Matt
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-toc-block
 *
 * @package SimpleTocBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the block using the metadata in build/block.json.
 */
function simple_toc_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'simple_toc_block_init' );

/**
 * Create a URL-safe, unique anchor slug from heading text.
 *
 * @param string $text Heading text.
 * @param array  $used Slugs already used on this page (passed by reference for dedup).
 * @return string
 */
function simple_toc_make_slug( $text, &$used ) {
	$slug = sanitize_title( $text );
	if ( '' === $slug ) {
		$slug = 'section';
	}
	$base  = $slug;
	$index = 2;
	while ( isset( $used[ $slug ] ) ) {
		$slug = $base . '-' . $index;
		$index++;
	}
	$used[ $slug ] = true;
	return $slug;
}

/**
 * Recursively walk parsed blocks and collect heading data in document order.
 *
 * @param array $blocks    Parsed blocks from parse_blocks().
 * @param array $used      Used slugs (by reference) for dedup.
 * @param array $collected Growing list of headings (by reference).
 */
function simple_toc_collect_headings( $blocks, &$used, &$collected ) {
	foreach ( $blocks as $block ) {
		if ( 'core/heading' === $block['blockName'] ) {
			$html = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
			$text = trim( wp_strip_all_tags( $html ) );

			$level = 2; // Core headings default to H2.
			if ( isset( $block['attrs']['level'] ) ) {
				$level = (int) $block['attrs']['level'];
			} elseif ( preg_match( '/<h([1-6])/i', $html, $m ) ) {
				$level = (int) $m[1];
			}

			if ( '' !== $text ) {
				$collected[] = array(
					'level' => $level,
					'text'  => $text,
					'slug'  => simple_toc_make_slug( $text, $used ),
				);
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			simple_toc_collect_headings( $block['innerBlocks'], $used, $collected );
		}
	}
}

/**
 * Get the full, slug-stamped heading map for a post.
 *
 * This is the single source of truth: the TOC list and the IDs injected into
 * the post's headings both derive from this, so the anchor links always match.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function simple_toc_get_all_headings( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$post      = get_post( $post_id );
	$used      = array();
	$collected = array();

	if ( $post ) {
		simple_toc_collect_headings( parse_blocks( $post->post_content ), $used, $collected );
	}

	$cache[ $post_id ] = $collected;
	return $collected;
}

/**
 * Render nested list markup from heading data.
 *
 * Levels are expected to be normalized to sequential depths (1, 2, 3...).
 *
 * @param array  $headings Heading data with normalized 'level'.
 * @param string $list_tag 'ol' or 'ul'.
 * @return string
 */
function simple_toc_render_list( $headings, $list_tag ) {
	if ( empty( $headings ) ) {
		return '';
	}

	$html = '';
	$prev = 0; // Forces the first item to open one list.
	$open = 0; // Count of currently open lists.

	foreach ( $headings as $heading ) {
		$level = (int) $heading['level'];

		if ( $level > $prev ) {
			for ( $i = 0; $i < ( $level - $prev ); $i++ ) {
				$class = 0 === $open ? '' : ' class="simple-toc__sub"';
				$html .= '<' . $list_tag . $class . '>';
				$open++;
			}
		} else {
			$html .= '</li>';
			if ( $level < $prev ) {
				for ( $i = 0; $i < ( $prev - $level ); $i++ ) {
					$html .= '</' . $list_tag . '></li>';
					$open--;
				}
			}
		}

		$html .= '<li><a href="#' . esc_attr( $heading['slug'] ) . '">' . esc_html( $heading['text'] ) . '</a>';
		$prev  = $level;
	}

	$html .= '</li>';
	for ( $i = 0; $i < $open; $i++ ) {
		$html .= '</' . $list_tag . '>';
		if ( $i < $open - 1 ) {
			$html .= '</li>';
		}
	}

	return $html;
}

/**
 * Inject matching anchor IDs into core/heading blocks on the front end so the
 * TOC links have a target to scroll to.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string
 */
function simple_toc_add_heading_ids( $block_content, $block ) {
	if ( is_admin() || ! is_singular() || empty( $block['blockName'] ) || 'core/heading' !== $block['blockName'] ) {
		return $block_content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $block_content;
	}

	$headings = simple_toc_get_all_headings( $post_id );

	// Walk through headings in document order as each one renders.
	static $pointers = array();
	$pointer = isset( $pointers[ $post_id ] ) ? $pointers[ $post_id ] : 0;

	if ( ! isset( $headings[ $pointer ] ) ) {
		return $block_content;
	}
	$pointers[ $post_id ] = $pointer + 1;

	// Respect an author-supplied id if one already exists.
	if ( preg_match( '/<h[1-6][^>]*\sid=/i', $block_content ) ) {
		return $block_content;
	}

	$slug = $headings[ $pointer ]['slug'];
	return preg_replace( '/(<h[1-6])(\s|>)/i', '$1 id="' . esc_attr( $slug ) . '"$2', $block_content, 1 );
}
add_filter( 'render_block', 'simple_toc_add_heading_ids', 10, 2 );
