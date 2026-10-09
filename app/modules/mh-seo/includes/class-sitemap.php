<?php
/**
 * XML sitemaps.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Serves /sitemap_index.xml and the child sitemaps.
 *
 * Core's sitemap stays in place until this one is turned on. Nothing is pinged;
 * Google retired sitemap ping.
 */
class Sitemap {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_rewrites' ), 99 );
		add_filter( 'wp_sitemaps_enabled', array( $this, 'filter_core_sitemaps' ) );
		add_action( 'template_redirect', array( $this, 'dispatch' ), 0 );
		add_action( 'save_post', array( $this, 'invalidate_post' ), 20 );
		add_action( 'deleted_post', array( $this, 'invalidate_post' ) );
		add_action( 'created_term', array( $this, 'invalidate_term' ) );
		add_action( 'edited_term', array( $this, 'invalidate_term' ) );
		add_action( 'delete_term', array( $this, 'invalidate_term' ) );
		add_action( 'update_option_' . Settings::OPTION, array( $this, 'settings_changed' ), 10, 2 );
		add_action( 'add_option_' . Settings::OPTION, array( $this, 'settings_changed' ), 10, 2 );
	}

	/**
	 * Register rewrite rules and flush them on activation.
	 *
	 * The front end also matches REQUEST_URI, so a missed flush still serves
	 * the sitemap. The flush keeps the rules in step for the next request.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$sitemap = new self();
		if ( self::is_enabled() ) {
			$sitemap->add_rewrite_rules();
		}
		flush_rewrite_rules();
	}

	/**
	 * Whether the MH SEO sitemap is the one that should be served.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return ! empty( Settings::get()['sitemap_enabled'] );
	}

	/**
	 * Post types checked on the settings screen.
	 *
	 * A site that has never saved the list gets every public type that has
	 * a published entry, except attachments.
	 *
	 * @return array<int, string>
	 */
	public static function selected_post_types(): array {
		$saved = get_option( Settings::OPTION, array() );
		if ( is_array( $saved ) && array_key_exists( 'sitemap_post_types', $saved ) && is_array( $saved['sitemap_post_types'] ) ) {
			return self::clean_names( $saved['sitemap_post_types'] );
		}
		return self::default_post_types();
	}

	/**
	 * Taxonomies checked on the settings screen.
	 *
	 * @return array<int, string>
	 */
	public static function selected_taxonomies(): array {
		$saved = get_option( Settings::OPTION, array() );
		if ( is_array( $saved ) && array_key_exists( 'sitemap_taxonomies', $saved ) && is_array( $saved['sitemap_taxonomies'] ) ) {
			return self::clean_names( $saved['sitemap_taxonomies'] );
		}
		return self::default_taxonomies();
	}

	/**
	 * Public post types that already have published content.
	 *
	 * @return array<int, string>
	 */
	public static function default_post_types(): array {
		$names = array();
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $name ) {
			if ( 'attachment' === $name ) {
				continue;
			}
			$counts = wp_count_posts( $name );
			$total  = ( $counts instanceof \stdClass && isset( $counts->publish ) ) ? (int) $counts->publish : 0;
			if ( $total > 0 ) {
				$names[] = $name;
			}
		}
		if ( array() === $names ) {
			return array( 'post', 'page' );
		}
		return $names;
	}

	/**
	 * Public taxonomies that already have terms.
	 *
	 * @return array<int, string>
	 */
	public static function default_taxonomies(): array {
		$names = array();
		foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $name ) {
			$count = wp_count_terms(
				array(
					'taxonomy'   => $name,
					'hide_empty' => true,
				)
			);
			if ( ! is_wp_error( $count ) && (int) $count > 0 ) {
				$names[] = $name;
			}
		}
		return $names;
	}

	/**
	 * Add rewrite tags while the sitemap is enabled, and flush once after a settings save.
	 *
	 * @return void
	 */
	public function register_rewrites(): void {
		if ( self::is_enabled() ) {
			$this->add_rewrite_rules();
		}
		if ( get_option( 'mh_seo_flush_rewrites' ) ) {
			delete_option( 'mh_seo_flush_rewrites' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Rewrite tags for the index, the stylesheet, and child sitemaps.
	 *
	 * @return void
	 */
	private function add_rewrite_rules(): void {
		add_rewrite_tag( '%mh_seo_sitemap%', '([^&]+)' );
		add_rewrite_tag( '%mh_seo_sitemap_page%', '([0-9]*)' );
		add_rewrite_rule( '^sitemap_index\.xml$', 'index.php?mh_seo_sitemap=index', 'top' );
		add_rewrite_rule( '^mh-sitemap\.xsl$', 'index.php?mh_seo_sitemap=stylesheet', 'top' );
		add_rewrite_rule(
			'^([a-z0-9_-]+)-sitemap([0-9]*)\.xml$',
			'index.php?mh_seo_sitemap=$matches[1]&mh_seo_sitemap_page=$matches[2]',
			'top'
		);
	}

	/**
	 * Turn off the core sitemap only while this one is on.
	 *
	 * @param mixed $enabled Whether core would serve /wp-sitemap.xml.
	 * @return bool
	 */
	public function filter_core_sitemaps( $enabled ): bool {
		if ( self::is_enabled() ) {
			return false;
		}
		return (bool) $enabled;
	}

	/**
	 * Serve a sitemap, redirect the old index URLs, or leave the request alone.
	 *
	 * @return void
	 */
	public function dispatch(): void {
		if ( self::is_enabled() ) {
			$this->serve();
			return;
		}
		$this->legacy_redirect();
	}

	/**
	 * Drop cached sitemap bodies after a post change.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function invalidate_post( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		self::flush_cache();
	}

	/**
	 * Drop cached sitemap bodies after a term change.
	 *
	 * @param int $term_id Term id. Unused.
	 * @return void
	 */
	public function invalidate_term( int $term_id ): void {
		unset( $term_id );
		self::flush_cache();
	}

	/**
	 * Drop the cache and refresh rewrite rules after settings are saved.
	 *
	 * @param mixed $old      Previous value. Unused.
	 * @param mixed $updated New value. Unused.
	 * @return void
	 */
	public function settings_changed( $old = null, $updated = null ): void {
		unset( $old, $updated );
		self::flush_cache();
		update_option( 'mh_seo_flush_rewrites', '1', false );
	}

	/**
	 * Bump the cache generation so the next request rebuilds every sitemap.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		update_option( 'mh_seo_sitemap_cache', (string) time(), false );
		wp_cache_delete( 'mh_seo_sitemap_excluded_posts', 'mh-seo' );
	}

	/**
	 * Send the old Rank Math sitemap URLs to the core sitemap.
	 *
	 * This only runs while the MH SEO sitemap is off and head tags are on,
	 * which is when the core sitemap is the one in use.
	 *
	 * @return void
	 */
	public function legacy_redirect(): void {
		if ( ! mh_seo_is_managing_head() ) {
			return;
		}
		if ( ! Paths::is_legacy_sitemap( $this->request_path() ) ) {
			return;
		}
		wp_safe_redirect( home_url( '/wp-sitemap.xml' ), 301 );
		exit;
	}

	/**
	 * Render the sitemap for this request, or redirect the retired index URLs.
	 *
	 * @return void
	 */
	private function serve(): void {
		$path = $this->request_path();
		if ( Sitemap_Xml::redirects_to_index( $path ) ) {
			wp_safe_redirect( home_url( Sitemap_Xml::INDEX_PATH ), 301 );
			exit;
		}
		$route = Sitemap_Xml::match( $path );
		if ( null === $route ) {
			return;
		}
		if ( 'stylesheet' === $route['kind'] ) {
			$this->send( Sitemap_Xml::stylesheet(), 200, 'application/xml' );
		}
		if ( 'index' === $route['kind'] ) {
			$sitemaps = $this->cached(
				'index',
				function () {
					return $this->index_entries();
				}
			);
			$this->send( Sitemap_Xml::index( $sitemaps, $this->stylesheet_url() ), 200, 'application/xml' );
		}
		$child = $this->child_entries( $route['name'], $route['page'] );
		if ( null === $child ) {
			$this->send( Sitemap_Xml::urlset( array(), $this->stylesheet_url(), false ), 404, 'application/xml' );
		}
		$images = ! empty( Settings::get()['sitemap_images'] );
		$this->send( Sitemap_Xml::urlset( $child, $this->stylesheet_url(), $images ), 200, 'application/xml' );
	}

	/**
	 * Child sitemaps that belong in the index.
	 *
	 * @return array<int, array{loc: string, lastmod: string}>
	 */
	private function index_entries(): array {
		$entries  = array();
		$per_page = $this->per_page();
		foreach ( $this->post_type_names() as $name ) {
			$total = $this->count_posts( $name );
			$pages = Sitemap_Xml::page_count( $total, $per_page );
			$mod   = $this->newest_post_modified( $name );
			for ( $page = 1; $page <= $pages; $page++ ) {
				$entries[] = array(
					'loc'     => home_url( Sitemap_Xml::child_path( $name, $page ) ),
					'lastmod' => $mod,
				);
			}
		}
		foreach ( $this->taxonomy_names() as $name ) {
			$total = $this->count_terms( $name );
			$pages = Sitemap_Xml::page_count( $total, $per_page );
			$mod   = $this->newest_term_modified( $name );
			for ( $page = 1; $page <= $pages; $page++ ) {
				$entries[] = array(
					'loc'     => home_url( Sitemap_Xml::child_path( $name, $page ) ),
					'lastmod' => $mod,
				);
			}
		}
		if ( $this->authors_included() ) {
			$total = $this->count_authors();
			$pages = Sitemap_Xml::page_count( $total, $per_page );
			for ( $page = 1; $page <= $pages; $page++ ) {
				$entries[] = array(
					'loc'     => home_url( Sitemap_Xml::child_path( 'author', $page ) ),
					'lastmod' => '',
				);
			}
		}
		return $entries;
	}

	/**
	 * URLs for one child sitemap, or null when that file should 404.
	 *
	 * @param string $name Sitemap name.
	 * @param int    $page Page number.
	 * @return array<int, array{loc: string, lastmod: string, images: array<int, string>}>|null
	 */
	private function child_entries( string $name, int $page ): ?array {
		if ( $page < 1 ) {
			return null;
		}
		$per_page = $this->per_page();
		if ( in_array( $name, $this->post_type_names(), true ) ) {
			if ( $page > Sitemap_Xml::page_count( $this->count_posts( $name ), $per_page ) ) {
				return null;
			}
			return $this->cached(
				'post:' . $name . ':' . $page,
				function () use ( $name, $page ) {
					return $this->post_entries( $name, $page );
				}
			);
		}
		if ( in_array( $name, $this->taxonomy_names(), true ) ) {
			if ( $page > Sitemap_Xml::page_count( $this->count_terms( $name ), $per_page ) ) {
				return null;
			}
			return $this->cached(
				'tax:' . $name . ':' . $page,
				function () use ( $name, $page ) {
					return $this->term_entries( $name, $page );
				}
			);
		}
		if ( 'author' === $name && $this->authors_included() && ! $this->name_is_content( 'author' ) ) {
			if ( $page > Sitemap_Xml::page_count( $this->count_authors(), $per_page ) ) {
				return null;
			}
			return $this->cached(
				'author:' . $page,
				function () use ( $page ) {
					return $this->author_entries( $page );
				}
			);
		}
		return null;
	}

	/**
	 * Whether a sitemap name is already a post type or taxonomy.
	 *
	 * @param string $name Sitemap name.
	 * @return bool
	 */
	private function name_is_content( string $name ): bool {
		return in_array( $name, $this->post_type_names(), true ) || in_array( $name, $this->taxonomy_names(), true );
	}

	/**
	 * Published posts for one page of a post-type sitemap.
	 *
	 * @param string $post_type Post type.
	 * @param int    $page      Page number.
	 * @return array<int, array{loc: string, lastmod: string, images: array<int, string>}>
	 */
	private function post_entries( string $post_type, int $page ): array {
		$query = new \WP_Query( $this->post_query_args( $post_type, $page, false ) );
		$urls  = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$link = get_permalink( $post );
			if ( ! is_string( $link ) || '' === $link ) {
				continue;
			}
			$urls[] = array(
				'loc'     => $link,
				'lastmod' => $this->format_gmt( (string) $post->post_modified_gmt ),
				'images'  => $this->images_for_post( $post ),
			);
		}
		return $urls;
	}

	/**
	 * Term archive URLs for one page.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $page     Page number.
	 * @return array<int, array{loc: string, lastmod: string, images: array<int, string>}>
	 */
	private function term_entries( string $taxonomy, int $page ): array {
		$per_page = $this->per_page();
		$query    = new \WP_Term_Query( $this->term_query_args( $taxonomy, $per_page, ( $page - 1 ) * $per_page ) );
		$terms    = is_array( $query->terms ) ? $query->terms : array();
		$mods     = $this->term_modified_map( $terms );
		$urls     = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) || ! is_string( $link ) ) {
				continue;
			}
			$urls[] = array(
				'loc'     => $link,
				'lastmod' => $mods[ $term->term_id ] ?? '',
				'images'  => array(),
			);
		}
		return $urls;
	}

	/**
	 * Author archive URLs for one page.
	 *
	 * @param int $page Page number.
	 * @return array<int, array{loc: string, lastmod: string, images: array<int, string>}>
	 */
	private function author_entries( int $page ): array {
		$query = new \WP_User_Query(
			array(
				'has_published_posts' => true,
				'number'              => $this->per_page(),
				'paged'               => $page,
				'orderby'             => 'ID',
				'order'               => 'ASC',
				'fields'              => array( 'ID' ),
				'count_total'         => false,
			)
		);
		$ids   = array_map( 'absint', $query->get_results() );
		$mods  = $this->author_modified_map( $ids );
		$urls  = array();
		foreach ( $ids as $user_id ) {
			if ( $user_id < 1 ) {
				continue;
			}
			$urls[] = array(
				'loc'     => get_author_posts_url( $user_id ),
				'lastmod' => $mods[ $user_id ] ?? '',
				'images'  => array(),
			);
		}
		return $urls;
	}

	/**
	 * Published, public, non-password posts that are not excluded.
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	private function count_posts( string $post_type ): int {
		$query = new \WP_Query( $this->post_query_args( $post_type, 1, true ) );
		return (int) $query->found_posts;
	}

	/**
	 * Query args shared by the count and the page of posts.
	 *
	 * @param string $post_type Post type.
	 * @param int    $page      Page number.
	 * @param bool   $count     True when only found_posts is needed.
	 * @return array<string, mixed>
	 */
	private function post_query_args( string $post_type, int $page, bool $count ): array {
		$args    = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'has_password'           => false,
			'posts_per_page'         => $count ? 1 : $this->per_page(),
			'paged'                  => $count ? 1 : $page,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => ! $count,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => ! $count,
			'fields'                 => $count ? 'ids' : 'all',
		);
		$exclude = $this->excluded_post_ids();
		if ( array() !== $exclude ) {
			$args['post__not_in'] = $exclude;
		}
		return $args;
	}

	/**
	 * Terms with posts, minus terms marked noindex.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return int
	 */
	private function count_terms( string $taxonomy ): int {
		$args    = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		);
		$exclude = $this->excluded_term_ids( $taxonomy );
		if ( array() !== $exclude ) {
			$args['exclude'] = $exclude;
		}
		$count = wp_count_terms( $args );
		if ( is_wp_error( $count ) ) {
			return 0;
		}
		return (int) $count;
	}

	/**
	 * Term query args for one page.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $number   Page size.
	 * @param int    $offset   Offset.
	 * @return array<string, mixed>
	 */
	private function term_query_args( string $taxonomy, int $number, int $offset ): array {
		$args    = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'number'     => $number,
			'offset'     => $offset,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		);
		$exclude = $this->excluded_term_ids( $taxonomy );
		if ( array() !== $exclude ) {
			$args['exclude'] = $exclude;
		}
		return $args;
	}

	/**
	 * Users who have at least one published post.
	 *
	 * @return int
	 */
	private function count_authors(): int {
		$query = new \WP_User_Query(
			array(
				'has_published_posts' => true,
				'number'              => 1,
				'fields'              => 'ID',
				'count_total'         => true,
			)
		);
		return (int) $query->get_total();
	}

	/**
	 * Post types that are public, selected, and not attachments.
	 *
	 * @return array<int, string>
	 */
	private function post_type_names(): array {
		$public = get_post_types( array( 'public' => true ), 'names' );
		$names  = array();
		foreach ( self::selected_post_types() as $name ) {
			if ( 'attachment' === $name || ! isset( $public[ $name ] ) ) {
				continue;
			}
			$names[] = $name;
		}
		return $names;
	}

	/**
	 * Taxonomies that are public, selected, and not hidden from search engines.
	 *
	 * @return array<int, string>
	 */
	private function taxonomy_names(): array {
		$public   = get_taxonomies( array( 'public' => true ), 'names' );
		$settings = Settings::get();
		$names    = array();
		foreach ( self::selected_taxonomies() as $name ) {
			if ( ! isset( $public[ $name ] ) ) {
				continue;
			}
			if ( 'post_tag' === $name && ! empty( $settings['noindex_tag'] ) ) {
				continue;
			}
			$names[] = $name;
		}
		return $names;
	}

	/**
	 * Author archives are listed only when that sitemap is on and those
	 * archives are allowed to be indexed.
	 *
	 * @return bool
	 */
	private function authors_included(): bool {
		$settings = Settings::get();
		return ! empty( $settings['sitemap_authors'] ) && empty( $settings['noindex_author'] );
	}

	/**
	 * Saved page size.
	 *
	 * @return int
	 */
	private function per_page(): int {
		return Sitemap_Xml::per_page( (int) ( Settings::get()['sitemap_per_page'] ?? 1000 ) );
	}

	/**
	 * Stylesheet URL printed in each sitemap.
	 *
	 * @return string
	 */
	private function stylesheet_url(): string {
		return home_url( Sitemap_Xml::STYLESHEET_PATH );
	}

	/**
	 * Published post ids that are noindexed or excluded from the sitemap.
	 *
	 * An explicit MH SEO "index" value wins over a leftover Rank Math noindex.
	 *
	 * @return array<int, int>
	 */
	private function excluded_post_ids(): array {
		$cache_key = 'mh_seo_sitemap_excluded_posts';
		$cached    = wp_cache_get( $cache_key, 'mh-seo' );
		if ( is_array( $cached ) ) {
			return array_map( 'intval', $cached );
		}
		global $wpdb;
		$table = $wpdb->postmeta;
		$posts = $wpdb->posts;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names cannot be placeholders.
		$rows = $wpdb->get_col(
			"SELECT DISTINCT pm.post_id
			FROM {$table} pm
			INNER JOIN {$posts} p ON p.ID = pm.post_id
			WHERE p.post_status = 'publish'
			AND (
				( pm.meta_key = '_mh_seo_noindex' AND pm.meta_value = '1' )
				OR ( pm.meta_key = '_mh_seo_sitemap_exclude' AND pm.meta_value = '1' )
				OR (
					pm.meta_key = 'rank_math_robots'
					AND pm.meta_value LIKE '%noindex%'
					AND pm.post_id NOT IN (
						SELECT post_id FROM {$table}
						WHERE meta_key = '_mh_seo_noindex' AND meta_value = '0'
					)
				)
			)"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = array_map( 'intval', is_array( $rows ) ? $rows : array() );
		wp_cache_set( $cache_key, $ids, 'mh-seo', HOUR_IN_SECONDS );
		return $ids;
	}

	/**
	 * Term ids marked noindex for one taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int, int>
	 */
	private function excluded_term_ids( string $taxonomy ): array {
		$cache_key = 'mh_seo_sitemap_excluded_terms_' . $taxonomy;
		$cached    = wp_cache_get( $cache_key, 'mh-seo' );
		if ( is_array( $cached ) ) {
			return array_map( 'intval', $cached );
		}
		global $wpdb;
		$meta = $wpdb->termmeta;
		$tax  = $wpdb->term_taxonomy;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names cannot be placeholders.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT tm.term_id
				FROM {$meta} tm
				INNER JOIN {$tax} tt ON tt.term_id = tm.term_id
				WHERE tt.taxonomy = %s
				AND (
					( tm.meta_key = '_mh_seo_noindex' AND tm.meta_value = '1' )
					OR (
						tm.meta_key = 'rank_math_robots'
						AND tm.meta_value LIKE %s
						AND tm.term_id NOT IN (
							SELECT term_id FROM {$meta}
							WHERE meta_key = '_mh_seo_noindex' AND meta_value = '0'
						)
					)
				)",
				$taxonomy,
				'%noindex%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = array_map( 'intval', is_array( $rows ) ? $rows : array() );
		wp_cache_set( $cache_key, $ids, 'mh-seo', HOUR_IN_SECONDS );
		return $ids;
	}

	/**
	 * Newest public modification time for a post type, in W3C format.
	 *
	 * @param string $post_type Post type.
	 * @return string
	 */
	private function newest_post_modified( string $post_type ): string {
		global $wpdb;
		$exclude = $this->excluded_post_ids();
		$args    = array( $post_type );
		$not_in  = '';
		if ( array() !== $exclude ) {
			$not_in = ' AND ID NOT IN (' . implode( ',', array_fill( 0, count( $exclude ), '%d' ) ) . ')';
			$args   = array_merge( $args, $exclude );
		}
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and the integer list are escaped.
		$gmt = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND post_password = ''{$not_in}",
				$args
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->format_gmt( is_string( $gmt ) ? $gmt : '' );
	}

	/**
	 * Newest post modification for every term in a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	private function newest_term_modified( string $taxonomy ): string {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names cannot be placeholders.
		$gmt = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(p.post_modified_gmt)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tt.taxonomy = %s AND p.post_status = 'publish' AND p.post_password = ''",
				$taxonomy
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->format_gmt( is_string( $gmt ) ? $gmt : '' );
	}

	/**
	 * Newest modification time for each term id.
	 *
	 * @param array<int, \WP_Term> $terms Terms on this page.
	 * @return array<int, string>
	 */
	private function term_modified_map( array $terms ): array {
		$ids = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$ids[] = (int) $term->term_id;
			}
		}
		if ( array() === $ids ) {
			return array();
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- integer placeholders are built from absint ids.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id, MAX(p.post_modified_gmt) AS modified
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tt.term_id IN ({$placeholders}) AND p.post_status = 'publish' AND p.post_password = ''
				GROUP BY tt.term_id",
				$ids
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$map = array();
		if ( ! is_array( $rows ) ) {
			return $map;
		}
		foreach ( $rows as $row ) {
			$map[ (int) $row->term_id ] = $this->format_gmt( (string) $row->modified );
		}
		return $map;
	}

	/**
	 * Newest modification time for each author id.
	 *
	 * @param array<int, int> $user_ids Author ids.
	 * @return array<int, string>
	 */
	private function author_modified_map( array $user_ids ): array {
		$user_ids = array_values( array_filter( array_map( 'absint', $user_ids ) ) );
		if ( array() === $user_ids ) {
			return array();
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- integer placeholders are built from absint ids.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_author, MAX(post_modified_gmt) AS modified
				FROM {$wpdb->posts}
				WHERE post_author IN ({$placeholders}) AND post_status = 'publish' AND post_password = ''
				GROUP BY post_author",
				$user_ids
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$map = array();
		if ( ! is_array( $rows ) ) {
			return $map;
		}
		foreach ( $rows as $row ) {
			$map[ (int) $row->post_author ] = $this->format_gmt( (string) $row->modified );
		}
		return $map;
	}

	/**
	 * Featured image, then up to ten images from the content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<int, string>
	 */
	private function images_for_post( \WP_Post $post ): array {
		if ( empty( Settings::get()['sitemap_images'] ) ) {
			return array();
		}
		$urls  = array();
		$thumb = (int) get_post_thumbnail_id( $post );
		if ( $thumb > 0 ) {
			$src = wp_get_attachment_image_url( $thumb, 'full' );
			if ( is_string( $src ) && '' !== $src ) {
				$urls[] = $src;
			}
		}
		if ( preg_match_all( '/<img\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $post->post_content, $matches ) ) {
			foreach ( $matches[2] as $src ) {
				if ( count( $urls ) >= 10 ) {
					break;
				}
				$absolute = $this->absolute_image( (string) $src );
				if ( '' !== $absolute ) {
					$urls[] = $absolute;
				}
			}
		}
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Turn a content image src into an absolute http(s) URL.
	 *
	 * @param string $src Raw src.
	 * @return string
	 */
	private function absolute_image( string $src ): string {
		$src = trim( $src );
		if ( '' === $src || str_starts_with( strtolower( $src ), 'data:' ) ) {
			return '';
		}
		if ( str_starts_with( $src, '//' ) ) {
			$src = 'https:' . $src;
		} elseif ( str_starts_with( $src, '/' ) ) {
			$src = home_url( $src );
		}
		$src = esc_url_raw( $src );
		if ( '' === $src || ! wp_http_validate_url( $src ) ) {
			return '';
		}
		return $src;
	}

	/**
	 * Format a GMT mysql datetime as a W3C timestamp.
	 *
	 * @param string $gmt Datetime from the database.
	 * @return string
	 */
	private function format_gmt( string $gmt ): string {
		if ( '' === $gmt || str_starts_with( $gmt, '0000-00-00' ) ) {
			return '';
		}
		$timestamp = strtotime( $gmt . ' GMT' );
		if ( false === $timestamp ) {
			return '';
		}
		return gmdate( 'Y-m-d\TH:i:s+00:00', $timestamp );
	}

	/**
	 * Remember a generated sitemap body until a post, term, or setting changes.
	 *
	 * @param string   $key      Cache key suffix.
	 * @param callable $callback Builder. Must return an array.
	 * @return array<int|string, mixed>
	 */
	private function cached( string $key, callable $callback ): array {
		$generation = (string) get_option( 'mh_seo_sitemap_cache', '1' );
		$cache_key  = 'mh_seo_sm_' . md5( $generation . '|' . $key );
		$cached     = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$value = $callback();
		if ( ! is_array( $value ) ) {
			$value = array();
		}
		set_transient( $cache_key, $value, 12 * HOUR_IN_SECONDS );
		return $value;
	}

	/**
	 * Request path without the query string.
	 *
	 * @return string
	 */
	private function request_path(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}
		$raw  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path = strtok( $raw, '?' );
		return is_string( $path ) ? $path : '';
	}

	/**
	 * Print a finished sitemap and stop.
	 *
	 * @param string $body   Escaped XML.
	 * @param int    $status HTTP status.
	 * @param string $type   Content type, without charset.
	 * @return void
	 */
	private function send( string $body, int $status, string $type ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: ' . $type . '; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sitemap_Xml escapes every URL and text node.
		echo $body;
		exit;
	}

	/**
	 * Keep slug-like names and drop attachments.
	 *
	 * @param array<int, mixed> $raw Submitted or stored names.
	 * @return array<int, string>
	 */
	private static function clean_names( array $raw ): array {
		$names = array();
		foreach ( $raw as $name ) {
			$name = sanitize_key( (string) $name );
			if ( '' !== $name && 'attachment' !== $name ) {
				$names[] = $name;
			}
		}
		return array_values( array_unique( $names ) );
	}
}
