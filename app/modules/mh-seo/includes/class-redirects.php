<?php
/**
 * A small redirect list.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Stores redirects, sends them, and provides the Tools screen.
 */
class Redirects {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_attachment' ), 2 );
		add_action( 'template_redirect', array( $this, 'log_404' ), 20 );
		add_action( 'post_updated', array( $this, 'slug_changed' ), 10, 3 );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_mh_seo_save_redirect', array( $this, 'save' ) );
		add_action( 'admin_post_mh_seo_delete_redirect', array( $this, 'delete' ) );
		add_action( 'admin_post_mh_seo_import_redirects', array( $this, 'import' ) );
		add_action( 'admin_post_mh_seo_export_redirects', array( $this, 'export' ) );
	}

	/**
	 * Create the tables and seed the two known redirects.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset   = $wpdb->get_charset_collate();
		$redirects = $wpdb->prefix . 'mh_seo_redirects';
		$missing   = $wpdb->prefix . 'mh_seo_404';
		$sql       = "CREATE TABLE {$redirects} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source varchar(191) NOT NULL,
			target varchar(2048) NOT NULL DEFAULT '',
			code smallint(6) unsigned NOT NULL DEFAULT 301,
			match_type varchar(20) NOT NULL DEFAULT 'exact',
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			last_hit datetime DEFAULT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source (source)
		) {$charset};
		CREATE TABLE {$missing} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url varchar(191) NOT NULL,
			hits bigint(20) unsigned NOT NULL DEFAULT 1,
			last_hit datetime NOT NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY url (url)
		) {$charset};";
		dbDelta( $sql );
		self::seed();
	}

	/**
	 * Add the privacy and terms redirects when they are not already there.
	 *
	 * @return void
	 */
	private static function seed(): void {
		$redirects = new self();
		$redirects->insert_if_missing( '/privacy-policy', '/privacy/', 301, 'exact' );
		$redirects->insert_if_missing( '/terms-of-use', '/terms/', 301, 'exact' );
	}

	/**
	 * Send a matching redirect.
	 *
	 * @return void
	 */
	public function maybe_redirect(): void {
		if ( is_admin() ) {
			return;
		}
		$path = $this->request_path();
		if ( '' === $path || $this->is_reserved( $path ) ) {
			return;
		}
		$row = $this->find( $path );
		if ( ! is_object( $row ) ) {
			return;
		}
		$this->hit( (int) $row->id );
		$code = (int) $row->code;
		if ( 410 === $code ) {
			status_header( 410 );
			nocache_headers();
			echo esc_html__( 'This page is gone.', 'mh-seo' );
			exit;
		}
		$target = (string) $row->target;
		if ( str_starts_with( $target, '/' ) ) {
			$target = home_url( $target );
		}
		if ( wp_validate_redirect( $target, '' ) === $target ) {
			wp_safe_redirect( $target, $code );
		} else {
			// External targets are allowed. wp_safe_redirect() only accepts hosts on this site.
			wp_redirect( esc_url_raw( $target ), $code ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		}
		exit;
	}

	/**
	 * Send attachment pages to the parent post, or to the file when there is no parent.
	 *
	 * @return void
	 */
	public function maybe_redirect_attachment(): void {
		if ( ! mh_seo_is_managing_head() || empty( Settings::get()['redirect_attachments'] ) || ! is_attachment() ) {
			return;
		}
		$post_id = (int) get_queried_object_id();
		$parent  = (int) wp_get_post_parent_id( $post_id );
		$target  = $parent > 0 ? (string) get_permalink( $parent ) : (string) wp_get_attachment_url( $post_id );
		if ( '' === $target ) {
			$target = home_url( '/' );
		}
		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Record a 404, capped at 500 rows.
	 *
	 * @return void
	 */
	public function log_404(): void {
		if ( ! is_404() || empty( Settings::get()['log_404'] ) || ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
			return;
		}
		$path = $this->request_path();
		if ( '' === $path || $this->is_reserved( $path ) || $this->is_static_asset( $path ) ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_404';
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM {$table} WHERE url = %s LIMIT 1", $path ) );
		if ( is_object( $existing ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits = hits + 1, last_hit = %s WHERE id = %d", $now, (int) $existing->id ) );
		} else {
			$wpdb->insert(
				$table,
				array(
					'url'      => $path,
					'hits'     => 1,
					'last_hit' => $now,
					'created'  => $now,
				),
				array( '%s', '%d', '%s', '%s' )
			);
		}
		$this->cap_404( $table );
	}

	/**
	 * Add a 301 when a published slug changes.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $after   Post after the update.
	 * @param \WP_Post $before  Post before the update.
	 * @return void
	 */
	public function slug_changed( int $post_id, \WP_Post $after, \WP_Post $before ): void {
		unset( $post_id );
		if ( wp_is_post_revision( $after ) || 'publish' !== $after->post_status || 'publish' !== $before->post_status ) {
			return;
		}
		if ( $after->post_name === $before->post_name || ! in_array( $after->post_type, array( 'post', 'page', 'project' ), true ) ) {
			return;
		}
		$new    = (string) get_permalink( $after );
		$old    = (string) preg_replace(
			'#/' . preg_quote( $after->post_name, '#' ) . '/?$#',
			'/' . $before->post_name . '/',
			$new
		);
		$source = Paths::normalize_source( $old );
		$target = Paths::normalize_source( $new );
		if ( '' === $source || $source === $target ) {
			return;
		}
		$this->insert_if_missing( $source, $target, 301, 'exact' );
	}

	/**
	 * Tools → Redirects.
	 *
	 * @return void
	 */
	public function menu(): void {
		add_management_page(
			__( 'Redirects', 'mh-seo' ),
			__( 'Redirects', 'mh-seo' ),
			'manage_options',
			'mh-seo-redirects',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Redirects screen.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$rows = $this->all();
		$logs = $this->recent_404();
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Redirects', 'mh-seo' ) . '</h1>';
		echo '<p>' . esc_html__( 'Send an old address to a new one. The privacy and terms redirects are included and can be edited.', 'mh-seo' ) . '</p>';
		$this->render_form();
		echo '<h2>' . esc_html__( 'Saved redirects', 'mh-seo' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'From', 'mh-seo' ) . '</th><th>' . esc_html__( 'To', 'mh-seo' ) . '</th><th>' . esc_html__( 'Code', 'mh-seo' ) . '</th><th>' . esc_html__( 'Match', 'mh-seo' ) . '</th><th>' . esc_html__( 'Hits', 'mh-seo' ) . '</th><th></th>';
		echo '</tr></thead><tbody>';
		if ( array() === $rows ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No redirects yet.', 'mh-seo' ) . '</td></tr>';
		}
		foreach ( $rows as $row ) {
			$delete = wp_nonce_url(
				admin_url( 'admin-post.php?action=mh_seo_delete_redirect&id=' . (int) $row->id ),
				'mh_seo_delete_redirect_' . (int) $row->id
			);
			echo '<tr>';
			echo '<td><code>' . esc_html( (string) $row->source ) . '</code></td>';
			echo '<td><code>' . esc_html( (string) $row->target ) . '</code></td>';
			echo '<td>' . esc_html( (string) $row->code ) . '</td>';
			echo '<td>' . esc_html( (string) $row->match_type ) . '</td>';
			echo '<td>' . esc_html( (string) $row->hits ) . '</td>';
			echo '<td><a href="' . esc_url( $delete ) . '">' . esc_html__( 'Delete', 'mh-seo' ) . '</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<h2>' . esc_html__( 'Import and export', 'mh-seo' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
		wp_nonce_field( 'mh_seo_import_redirects' );
		echo '<input type="hidden" name="action" value="mh_seo_import_redirects" />';
		echo '<input type="file" name="mh_seo_csv" accept=".csv,text/csv" /> ';
		submit_button( __( 'Import CSV', 'mh-seo' ), 'secondary', 'submit', false );
		echo '</form>';
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mh_seo_export_redirects' ), 'mh_seo_export_redirects' ) ) . '">' . esc_html__( 'Export CSV', 'mh-seo' ) . '</a></p>';
		echo '<h2>' . esc_html__( 'Recent missing pages', 'mh-seo' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Address', 'mh-seo' ) . '</th><th>' . esc_html__( 'Hits', 'mh-seo' ) . '</th><th>' . esc_html__( 'Last seen', 'mh-seo' ) . '</th></tr></thead><tbody>';
		if ( array() === $logs ) {
			echo '<tr><td colspan="3">' . esc_html__( 'Nothing recorded.', 'mh-seo' ) . '</td></tr>';
		}
		foreach ( $logs as $log ) {
			echo '<tr><td><code>' . esc_html( (string) $log->url ) . '</code></td><td>' . esc_html( (string) $log->hits ) . '</td><td>' . esc_html( (string) $log->last_hit ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Add form.
	 *
	 * @return void
	 */
	private function render_form(): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mh_seo_save_redirect' );
		echo '<input type="hidden" name="action" value="mh_seo_save_redirect" />';
		echo '<table class="form-table"><tr><th scope="row"><label for="mh-seo-source">' . esc_html__( 'From', 'mh-seo' ) . '</label></th>';
		echo '<td><input name="source" id="mh-seo-source" class="regular-text" placeholder="/old-address" required /></td></tr>';
		echo '<tr><th scope="row"><label for="mh-seo-target">' . esc_html__( 'To', 'mh-seo' ) . '</label></th>';
		echo '<td><input name="target" id="mh-seo-target" class="regular-text" placeholder="/new-address" /></td></tr>';
		echo '<tr><th scope="row"><label for="mh-seo-code">' . esc_html__( 'Type', 'mh-seo' ) . '</label></th><td><select name="code" id="mh-seo-code">';
		echo '<option value="301">' . esc_html__( '301, moved permanently', 'mh-seo' ) . '</option>';
		echo '<option value="302">' . esc_html__( '302, temporary', 'mh-seo' ) . '</option>';
		echo '<option value="410">' . esc_html__( '410, gone', 'mh-seo' ) . '</option>';
		echo '</select></td></tr>';
		echo '<tr><th scope="row"><label for="mh-seo-match">' . esc_html__( 'Match', 'mh-seo' ) . '</label></th><td><select name="match_type" id="mh-seo-match">';
		echo '<option value="exact">' . esc_html__( 'Exact address', 'mh-seo' ) . '</option>';
		echo '<option value="prefix">' . esc_html__( 'Starts with', 'mh-seo' ) . '</option>';
		echo '</select></td></tr></table>';
		submit_button( __( 'Add redirect', 'mh-seo' ) );
		echo '</form>';
	}

	/**
	 * Save a redirect from the form.
	 *
	 * @return void
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot edit redirects.', 'mh-seo' ) );
		}
		check_admin_referer( 'mh_seo_save_redirect' );
		$source = Paths::normalize_source( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' );
		$target = $this->clean_target( isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '' );
		$code   = isset( $_POST['code'] ) ? absint( wp_unslash( $_POST['code'] ) ) : 301;
		$match  = isset( $_POST['match_type'] ) ? sanitize_key( wp_unslash( $_POST['match_type'] ) ) : 'exact';
		if ( '' === $source || ! in_array( $code, array( 301, 302, 410 ), true ) ) {
			wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-error=1' ) );
			exit;
		}
		if ( ! in_array( $match, array( 'exact', 'prefix' ), true ) ) {
			$match = 'exact';
		}
		$this->upsert( $source, $target, $code, $match );
		wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-saved=1' ) );
		exit;
	}

	/**
	 * Delete one redirect.
	 *
	 * @return void
	 */
	public function delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot edit redirects.', 'mh-seo' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( 'mh_seo_delete_redirect_' . $id );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'mh_seo_redirects', array( 'id' => $id ), array( '%d' ) );
		wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-deleted=1' ) );
		exit;
	}

	/**
	 * Import a CSV of source,target,code,match.
	 *
	 * @return void
	 */
	public function import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot edit redirects.', 'mh-seo' ) );
		}
		check_admin_referer( 'mh_seo_import_redirects' );
		if ( ! isset( $_FILES['mh_seo_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['mh_seo_csv']['tmp_name'] ) ) {
			wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-error=1' ) );
			exit;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- tmp path is checked with is_uploaded_file.
		$handle = fopen( $_FILES['mh_seo_csv']['tmp_name'], 'rb' );
		if ( false === $handle ) {
			wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-error=1' ) );
			exit;
		}
		$line = 0;
		$row  = fgetcsv( $handle );
		while ( false !== $row ) {
			++$line;
			if ( $line > 500 ) {
				break;
			}
			if ( ! is_array( $row ) || count( $row ) < 2 ) {
				continue;
			}
			if ( 1 === $line && 'source' === strtolower( trim( (string) $row[0] ) ) ) {
				continue;
			}
			$source = Paths::normalize_source( (string) $row[0] );
			$target = $this->clean_target( (string) $row[1] );
			$code   = isset( $row[2] ) ? absint( $row[2] ) : 301;
			$match  = isset( $row[3] ) ? sanitize_key( (string) $row[3] ) : 'exact';
			if ( '' === $source || ! in_array( $code, array( 301, 302, 410 ), true ) ) {
				continue;
			}
			if ( ! in_array( $match, array( 'exact', 'prefix' ), true ) ) {
				$match = 'exact';
			}
			$this->upsert( $source, $target, $code, $match );
			$row = fgetcsv( $handle );
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		wp_safe_redirect( admin_url( 'tools.php?page=mh-seo-redirects&mh-seo-saved=1' ) );
		exit;
	}

	/**
	 * Download the redirect list.
	 *
	 * @return void
	 */
	public function export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot edit redirects.', 'mh-seo' ) );
		}
		check_admin_referer( 'mh_seo_export_redirects' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mh-seo-redirects.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming a CSV download.
		if ( false === $out ) {
			exit;
		}
		fputcsv( $out, array( 'source', 'target', 'code', 'match' ) );
		foreach ( $this->all() as $row ) {
			fputcsv( $out, array( $row->source, $row->target, $row->code, $row->match_type ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Insert a redirect only when that source is new.
	 *
	 * @param string $source     Source path.
	 * @param string $target     Target path or URL.
	 * @param int    $code       Status code.
	 * @param string $match_type Exact or prefix.
	 * @return void
	 */
	public function insert_if_missing( string $source, string $target, int $code, string $match_type ): void {
		if ( $this->find_exact( $source ) ) {
			return;
		}
		$this->upsert( $source, $target, $code, $match_type );
	}

	/**
	 * Insert or replace a redirect by source path.
	 *
	 * @param string $source     Source path.
	 * @param string $target     Target.
	 * @param int    $code       Status code.
	 * @param string $match_type Exact or prefix.
	 * @return void
	 */
	private function upsert( string $source, string $target, int $code, string $match_type ): void {
		global $wpdb;
		$table    = $wpdb->prefix . 'mh_seo_redirects';
		$existing = $this->find_exact( $source );
		$now      = current_time( 'mysql', true );
		if ( is_object( $existing ) ) {
			$wpdb->update(
				$table,
				array(
					'target'     => $target,
					'code'       => $code,
					'match_type' => $match_type,
				),
				array( 'id' => (int) $existing->id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);
			return;
		}
		$wpdb->insert(
			$table,
			array(
				'source'     => $source,
				'target'     => $target,
				'code'       => $code,
				'match_type' => $match_type,
				'hits'       => 0,
				'created'    => $now,
			),
			array( '%s', '%s', '%d', '%s', '%d', '%s' )
		);
	}

	/**
	 * Find an exact row, then the longest matching prefix.
	 *
	 * @param string $path Request path.
	 * @return object|null
	 */
	private function find( string $path ): ?object {
		$exact = $this->find_exact( $path );
		if ( is_object( $exact ) ) {
			return $exact;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_redirects';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE match_type = 'prefix' ORDER BY CHAR_LENGTH(source) DESC" );
		if ( ! is_array( $rows ) ) {
			return null;
		}
		foreach ( $rows as $row ) {
			if ( Paths::prefix_matches( (string) $row->source, $path ) ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Exact source lookup.
	 *
	 * @param string $source Source path.
	 * @return object|null
	 */
	private function find_exact( string $source ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_redirects';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source = %s LIMIT 1", $source ) );
		return is_object( $row ) ? $row : null;
	}

	/**
	 * Every redirect, oldest first.
	 *
	 * @return array<int, object>
	 */
	private function all(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_redirects';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Latest 404 rows.
	 *
	 * @return array<int, object>
	 */
	private function recent_404(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_404';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY last_hit DESC LIMIT 50" );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count a hit.
	 *
	 * @param int $id Redirect id.
	 * @return void
	 */
	private function hit( int $id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'mh_seo_redirects';
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits = hits + 1, last_hit = %s WHERE id = %d", $now, $id ) );
	}

	/**
	 * Keep the 404 log from growing past 500 rows.
	 *
	 * @param string $table Table name.
	 * @return void
	 */
	private function cap_404( string $table ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count <= 500 ) {
			return;
		}
		$extra = $count - 500;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} ORDER BY last_hit ASC LIMIT %d", $extra ) );
		if ( ! is_array( $ids ) || array() === $ids ) {
			return;
		}
		$ids = array_map( 'absint', $ids );
		$in  = implode( ',', $ids );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" );
	}

	/**
	 * Request path.
	 *
	 * @return string
	 */
	private function request_path(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}
		$raw = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		return Paths::normalize_source( (string) strtok( $raw, '?' ) );
	}

	/**
	 * Paths we should never redirect or log.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_reserved( string $path ): bool {
		$prefixes = array( '/wp-admin', '/wp-login', '/wp-json', '/wp-sitemap', '/wp-cron' );
		foreach ( $prefixes as $prefix ) {
			if ( $path === $prefix || str_starts_with( $path, $prefix . '/' ) || str_starts_with( $path, $prefix . '.' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Skip files that are not pages.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private function is_static_asset( string $path ): bool {
		return 1 === preg_match( '/\.(?:css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|xml|txt)$/i', $path );
	}

	/**
	 * A relative path or an http(s) URL.
	 *
	 * @param string $target Raw target.
	 * @return string
	 */
	private function clean_target( string $target ): string {
		$target = trim( $target );
		if ( '' === $target ) {
			return '';
		}
		if ( str_starts_with( $target, '/' ) ) {
			return Paths::normalize_source( $target );
		}
		$url = esc_url_raw( $target );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return '';
		}
		return $url;
	}
}
