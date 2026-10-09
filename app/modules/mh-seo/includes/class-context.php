<?php
/**
 * Builds a request context from the main query or a post.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Collects the facts the document builder needs.
 *
 * Archive titles always come from the queried term, user, or post type.
 * A term id is never loaded as a post.
 */
class Context {
	/**
	 * Context for the current front-end request.
	 *
	 * @return array<string, mixed>
	 */
	public function from_query(): array {
		$post          = null;
		$view          = 'other';
		$document_name = '';
		$term          = null;
		$term_empty    = false;
		$url           = $this->current_url();

		if ( is_404() ) {
			$view          = '404';
			$document_name = __( 'Page not found', 'mh-seo' );
		} elseif ( is_search() ) {
			$view          = 'search';
			$document_name = __( 'Search results', 'mh-seo' );
		} elseif ( is_attachment() ) {
			$view   = 'attachment';
			$object = get_queried_object();
			if ( $object instanceof \WP_Post ) {
				$post          = $object;
				$document_name = get_the_title( $post );
			}
		} elseif ( is_front_page() ) {
			$view          = 'front';
			$post          = $this->front_page_post();
			$document_name = $post instanceof \WP_Post ? get_the_title( $post ) : (string) get_bloginfo( 'name' );
			$url           = home_url( '/' );
		} elseif ( is_home() ) {
			$view          = 'blog';
			$posts_page    = (int) get_option( 'page_for_posts' );
			$post          = $posts_page > 0 ? get_post( $posts_page ) : null;
			$document_name = $post instanceof \WP_Post ? get_the_title( $post ) : __( 'Blog', 'mh-seo' );
			$url           = $posts_page > 0 ? (string) get_permalink( $posts_page ) : home_url( '/' );
		} elseif ( is_singular() ) {
			$view   = 'singular';
			$object = get_queried_object();
			if ( $object instanceof \WP_Post ) {
				$post          = $object;
				$document_name = get_the_title( $post );
				$url           = (string) get_permalink( $post );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$view    = is_category() ? 'category' : ( is_tag() ? 'tag' : 'tax' );
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term ) {
				$term          = $queried;
				$document_name = $term->name;
				$term_empty    = 0 === (int) $term->count;
				$link          = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$url = (string) $link;
				}
			}
		} elseif ( is_author() ) {
			$view = 'author';
			$user = get_queried_object();
			if ( $user instanceof \WP_User ) {
				$document_name = $user->display_name;
				$url           = (string) get_author_posts_url( $user->ID );
			}
		} elseif ( is_date() ) {
			$view          = 'date';
			$document_name = __( 'Archives', 'mh-seo' );
		}

		$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		if ( $paged > 1 && in_array( $view, array( 'blog', 'category', 'tag', 'tax', 'author', 'date' ), true ) ) {
			$url = (string) get_pagenum_link( $paged );
		}

		$context                         = $this->base( $view, $post, $document_name, $url );
		$context['term_empty']           = $term_empty;
		$context['term_seo_description'] = '';
		$context['term_description']     = '';
		if ( $term instanceof \WP_Term ) {
			$context['term_seo_description'] = $this->term_meta_string( (int) $term->term_id, '_mh_seo_description' );
			$context['term_description']     = (string) $term->description;
		}
		$context['paged']      = $paged;
		$context['page_label'] = $paged > 1 ? sprintf(
			/* translators: %d: archive page number. */
			__( 'Page %d', 'mh-seo' ),
			$paged
		) : '';
		$context['core_prints_canonical'] = is_singular();
		$context['breadcrumbs']           = $this->breadcrumbs( $view, $post, $document_name, $url );
		return $context;
	}

	/**
	 * Context for one post, used by the editor preview and the score fallback.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	public function from_post( \WP_Post $post ): array {
		$view                             = ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) ? 'front' : 'singular';
		$url                              = (string) get_permalink( $post );
		$context                          = $this->base( $view, $post, get_the_title( $post ), $url );
		$context['term_empty']            = false;
		$context['term_seo_description']  = '';
		$context['term_description']      = '';
		$context['paged']                 = 1;
		$context['page_label']            = '';
		$context['core_prints_canonical'] = true;
		$context['breadcrumbs']           = $this->breadcrumbs( $view, $post, get_the_title( $post ), $url );
		return $context;
	}

	/**
	 * Shared singular and archive fields.
	 *
	 * @param string        $view          View name.
	 * @param \WP_Post|null $post          Post, when this request has one.
	 * @param string        $document_name Name used by the title template.
	 * @param string        $url           Canonical candidate.
	 * @return array<string, mixed>
	 */
	private function base( string $view, ?\WP_Post $post, string $document_name, string $url ): array {
		$post_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
		$landing = $this->landing_defaults( $post_id );
		$image   = $this->image( $post_id );
		$content = '';
		$excerpt = '';
		$place   = '';
		$summary = '';
		if ( $post instanceof \WP_Post ) {
			$content = $this->rendered_content( $post );
			$excerpt = trim( (string) $post->post_excerpt );
			$place   = $this->meta_string( $post_id, '_mh_project_place' );
			$summary = $this->meta_string( $post_id, '_mh_project_summary' );
			if ( '' === $summary ) {
				$summary = $this->meta_string( $post_id, '_mh_project_blurb' );
			}
		}

		$section = '';
		$primary = $this->primary_term( $post );
		if ( $primary instanceof \WP_Term ) {
			$section = $primary->name;
		}

		return array(
			'view'                  => $view,
			'post_type'             => $post instanceof \WP_Post ? $post->post_type : '',
			'post_id'               => $post_id,
			'document_name'         => $document_name,
			'custom_title'          => $this->meta_string( $post_id, '_mh_seo_title' ),
			'rank_math_title'       => $this->meta_string( $post_id, 'rank_math_title' ),
			'landing_title'         => (string) ( $landing['title'] ?? '' ),
			'field_title'           => $this->theme_field( $post_id, 'seo_title' ),
			'custom_description'    => $this->meta_string( $post_id, '_mh_seo_description' ),
			'rank_math_description' => $this->meta_string( $post_id, 'rank_math_description' ),
			'landing_description'   => (string) ( $landing['description'] ?? '' ),
			'field_description'     => $this->theme_field( $post_id, 'seo_desc' ),
			'excerpt'               => $excerpt,
			'content_text'          => Text::plain( Text::prose_html( $content ) ),
			'project_place'         => $place,
			'project_summary'       => $summary,
			'url'                   => $url,
			'canonical'             => $this->custom_canonical( $post_id ),
			'noindex_flag'          => $this->noindex_flag( $post_id ),
			'rank_math_noindex'     => $this->rank_math_noindex( $post_id ),
			'site_name'             => (string) get_bloginfo( 'name' ),
			'site_url'              => home_url( '/' ),
			'locale'                => (string) get_locale(),
			'language'              => str_replace( '_', '-', (string) get_locale() ),
			'image'                 => $image,
			'published'             => $post instanceof \WP_Post ? (string) get_post_time( 'c', true, $post ) : '',
			'modified'              => $post instanceof \WP_Post ? (string) get_post_modified_time( 'c', true, $post ) : '',
			'section'               => $section,
			'tags'                  => $this->tag_names( $post ),
			'word_count'            => count( Text::words( Text::plain( Text::prose_html( $content ) ) ) ),
		);
	}

	/**
	 * The static front page, when one is set.
	 *
	 * @return \WP_Post|null
	 */
	private function front_page_post(): ?\WP_Post {
		$object = get_queried_object();
		if ( $object instanceof \WP_Post ) {
			return $object;
		}
		$front_id = (int) get_option( 'page_on_front' );
		$post     = $front_id > 0 ? get_post( $front_id ) : null;
		return $post instanceof \WP_Post ? $post : null;
	}

	/**
	 * Landing-page title and description from the theme, then from the mh_seo_landing_defaults filter.
	 *
	 * @param int $post_id Post id, or 0 when this request is not a post.
	 * @return array{title: string, description: string}
	 */
	private function landing_defaults( int $post_id ): array {
		$defaults = array(
			'title'       => '',
			'description' => '',
		);
		if ( $post_id > 0 && function_exists( '\App\mh_seo_landing_defaults' ) ) {
			$theme = \App\mh_seo_landing_defaults( $post_id );
			if ( is_array( $theme ) ) {
				$defaults['title']       = trim( (string) ( $theme['title'] ?? '' ) );
				$defaults['description'] = trim( (string) ( $theme['description'] ?? ( $theme['desc'] ?? '' ) ) );
			}
		}
		$filtered = apply_filters( 'mh_seo_landing_defaults', $defaults, $post_id );
		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}
		return array(
			'title'       => trim( (string) ( $filtered['title'] ?? '' ) ),
			'description' => trim( (string) ( $filtered['description'] ?? ( $filtered['desc'] ?? '' ) ) ),
		);
	}

	/**
	 * Post meta key for a saved theme SEO field.
	 *
	 * The seo_title and seo_desc values are stored as mh_f_seo_title and mh_f_seo_desc.
	 *
	 * @param string $field Field name passed to the theme helper.
	 * @return string
	 */
	public static function theme_meta_key( string $field ): string {
		switch ( $field ) {
			case 'seo_title':
				return 'mh_f_seo_title';
			case 'seo_desc':
				return 'mh_f_seo_desc';
			default:
				return '';
		}
	}

	/**
	 * A saved theme SEO field.
	 *
	 * The mh_f_ post meta is the value saved on the page. The theme helper can
	 * return a built-in default when that meta is empty, so it is only a fallback.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Field name.
	 * @return string
	 */
	private function theme_field( int $post_id, string $key ): string {
		if ( $post_id < 1 ) {
			return '';
		}
		$meta_key = self::theme_meta_key( $key );
		if ( '' !== $meta_key ) {
			$saved = $this->meta_string( $post_id, $meta_key );
			if ( '' !== $saved ) {
				return $saved;
			}
		}
		if ( function_exists( '\App\field' ) ) {
			$value = \App\field( $key, '', $post_id );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}
		}
		return $this->meta_string( $post_id, $key );
	}

	/**
	 * Term meta as a trimmed string.
	 *
	 * @param int    $term_id Term id.
	 * @param string $key     Meta key.
	 * @return string
	 */
	private function term_meta_string( int $term_id, string $key ): string {
		if ( $term_id < 1 ) {
			return '';
		}
		$value = get_term_meta( $term_id, $key, true );
		if ( is_string( $value ) ) {
			return trim( $value );
		}
		if ( is_numeric( $value ) ) {
			return (string) $value;
		}
		return '';
	}

	/**
	 * Post meta as a trimmed string.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @return string
	 */
	private function meta_string( int $post_id, string $key ): string {
		if ( $post_id < 1 ) {
			return '';
		}
		$value = get_post_meta( $post_id, $key, true );
		if ( is_string( $value ) ) {
			return trim( $value );
		}
		if ( is_numeric( $value ) ) {
			return (string) $value;
		}
		return '';
	}

	/**
	 * Explicit noindex choice: "1", "0", or null when the key was never saved.
	 *
	 * @param int $post_id Post id.
	 * @return string|null
	 */
	private function noindex_flag( int $post_id ): ?string {
		if ( $post_id < 1 ) {
			return null;
		}
		$value = get_post_meta( $post_id, '_mh_seo_noindex', true );
		if ( '1' === (string) $value ) {
			return '1';
		}
		if ( '0' === (string) $value ) {
			return '0';
		}
		return null;
	}

	/**
	 * Whether Rank Math stored noindex for this post.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	private function rank_math_noindex( int $post_id ): bool {
		if ( $post_id < 1 ) {
			return false;
		}
		$value = get_post_meta( $post_id, 'rank_math_robots', true );
		if ( is_array( $value ) ) {
			return in_array( 'noindex', $value, true );
		}
		return is_string( $value ) && str_contains( $value, 'noindex' );
	}

	/**
	 * Custom canonical, then the Rank Math value if one was stored.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function custom_canonical( int $post_id ): string {
		$custom = $this->meta_string( $post_id, '_mh_seo_canonical' );
		if ( '' !== $custom ) {
			return $custom;
		}
		return $this->meta_string( $post_id, 'rank_math_canonical_url' );
	}

	/**
	 * Primary category: MH SEO first, then Rank Math.
	 *
	 * @param \WP_Post|null $post Post.
	 * @return \WP_Term|null
	 */
	private function primary_term( ?\WP_Post $post ): ?\WP_Term {
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return null;
		}
		$term_id = (int) get_post_meta( $post->ID, '_mh_seo_primary_term', true );
		if ( $term_id < 1 ) {
			$term_id = (int) get_post_meta( $post->ID, 'rank_math_primary_category', true );
		}
		if ( $term_id > 0 ) {
			$term = get_term( $term_id, 'category' );
			if ( $term instanceof \WP_Term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}
		$terms = get_the_terms( $post, 'category' );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term ) {
					return $term;
				}
			}
		}
		return null;
	}

	/**
	 * Tag names for article:tag and schema keywords.
	 *
	 * @param \WP_Post|null $post Post.
	 * @return array<int, string>
	 */
	private function tag_names( ?\WP_Post $post ): array {
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}
		$terms = get_the_terms( $post, 'post_tag' );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$names = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$names[] = $term->name;
			}
		}
		return $names;
	}

	/**
	 * Social image: the post's image, the featured image, then the global default.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	private function image( int $post_id ): array {
		$empty    = array(
			'url'    => '',
			'width'  => 0,
			'height' => 0,
			'alt'    => '',
			'mime'   => '',
		);
		$image_id = $post_id > 0 ? (int) get_post_meta( $post_id, '_mh_seo_og_image', true ) : 0;
		if ( $image_id < 1 && $post_id > 0 ) {
			$image_id = (int) get_post_thumbnail_id( $post_id );
		}
		if ( $image_id < 1 ) {
			$image_id = (int) ( Settings::get()['default_og_image'] ?? 0 );
		}
		if ( $image_id < 1 ) {
			return $empty;
		}
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return $empty;
		}
		$alt  = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$mime = get_post_mime_type( $image_id );
		return array(
			'url'    => (string) $src[0],
			'width'  => (int) ( $src[1] ?? 0 ),
			'height' => (int) ( $src[2] ?? 0 ),
			'alt'    => is_string( $alt ) ? $alt : '',
			'mime'   => is_string( $mime ) ? $mime : '',
		);
	}

	/**
	 * Block content rendered to HTML when WordPress can do it.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function rendered_content( \WP_Post $post ): string {
		$content = (string) $post->post_content;
		if ( function_exists( 'do_blocks' ) ) {
			$content = do_blocks( $content );
		}
		return (string) preg_replace( '/<!--.*?-->/s', '', $content );
	}

	/**
	 * Breadcrumb trail for schema.
	 *
	 * @param string        $view          View name.
	 * @param \WP_Post|null $post          Post, when there is one.
	 * @param string        $document_name Current item name.
	 * @param string        $url           Current item URL.
	 * @return array<int, array{name: string, url: string}>
	 */
	private function breadcrumbs( string $view, ?\WP_Post $post, string $document_name, string $url ): array {
		$crumbs = array(
			array(
				'name' => __( 'Home', 'mh-seo' ),
				'url'  => home_url( '/' ),
			),
		);
		if ( 'front' === $view ) {
			return $crumbs;
		}
		if ( ( 'singular' === $view || 'front' === $view ) && $post instanceof \WP_Post ) {
			if ( 'post' === $post->post_type ) {
				$blog_id = (int) get_option( 'page_for_posts' );
				if ( $blog_id > 0 ) {
					$crumbs[] = array(
						'name' => get_the_title( $blog_id ),
						'url'  => (string) get_permalink( $blog_id ),
					);
				}
				$term = $this->primary_term( $post );
				if ( $term instanceof \WP_Term ) {
					$link = get_term_link( $term );
					if ( ! is_wp_error( $link ) ) {
						$crumbs[] = array(
							'name' => $term->name,
							'url'  => (string) $link,
						);
					}
				}
			} elseif ( 'page' === $post->post_type ) {
				$ancestors = array_reverse( get_post_ancestors( $post ) );
				foreach ( $ancestors as $ancestor_id ) {
					$crumbs[] = array(
						'name' => get_the_title( (int) $ancestor_id ),
						'url'  => (string) get_permalink( (int) $ancestor_id ),
					);
				}
			} elseif ( 'project' === $post->post_type ) {
				$archive  = get_post_type_archive_link( 'project' );
				$crumbs[] = array(
					'name' => __( 'Projects', 'mh-seo' ),
					'url'  => is_string( $archive ) ? $archive : home_url( '/projects/' ),
				);
			}
			$crumbs[] = array(
				'name' => get_the_title( $post ),
				'url'  => (string) get_permalink( $post ),
			);
			return $crumbs;
		}

		if ( 'category' === $view ) {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id > 0 ) {
				$crumbs[] = array(
					'name' => get_the_title( $blog_id ),
					'url'  => (string) get_permalink( $blog_id ),
				);
			}
		}
		if ( '' !== $document_name ) {
			$crumbs[] = array(
				'name' => $document_name,
				'url'  => $url,
			);
		}
		return $crumbs;
	}

	/**
	 * Current request URL without the query string.
	 *
	 * @return string
	 */
	private function current_url(): string {
		$path = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$raw  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$path = (string) strtok( $raw, '?' );
		}
		return home_url( $path );
	}
}
