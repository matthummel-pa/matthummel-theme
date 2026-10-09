<?php
/**
 * WP-CLI commands.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * WP-CLI commands for copying Rank Math meta and recalculating scores.
 */
class CLI {
	/**
	 * Copy Rank Math meta into MH SEO meta.
	 *
	 * Existing MH SEO values are left alone. Rank Math values are not deleted.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Print the plan without writing.
	 *
	 * [--run]
	 * : Write the MH SEO meta.
	 *
	 * [--log=<path>]
	 * : CSV file to write. Defaults to the uploads directory on --run.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mh-seo migrate --dry-run
	 *     wp mh-seo migrate --run
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Flags.
	 * @return void
	 */
	public function migrate( array $args, array $assoc_args ): void {
		unset( $args );
		$run     = isset( $assoc_args['run'] );
		$dry_run = isset( $assoc_args['dry-run'] ) || ! $run;
		if ( $run && $dry_run && isset( $assoc_args['dry-run'] ) ) {
			$dry_run = true;
			$run     = false;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids      = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
			WHERE meta_key IN ( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword', 'rank_math_robots', 'rank_math_primary_category' )
			AND meta_value != ''"
		);
		$migrator = new Migrator();
		$site     = (string) get_bloginfo( 'name' );
		$rows     = array();
		foreach ( $ids as $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$source = array();
			foreach ( array( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword', 'rank_math_robots', 'rank_math_primary_category' ) as $key ) {
				$value = get_post_meta( $post_id, $key, true );
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				$source[ $key ] = is_scalar( $value ) ? (string) $value : '';
			}
			$existing = array();
			foreach ( array( '_mh_seo_title', '_mh_seo_description', '_mh_seo_focus_keyword', '_mh_seo_noindex', '_mh_seo_primary_term' ) as $key ) {
				$existing[ $key ] = (string) get_post_meta( $post_id, $key, true );
			}
			$plan = $migrator->plan( $source, $existing, get_the_title( $post ), $site );
			foreach ( $plan as $change ) {
				$rows[] = array(
					'post_id' => $post_id,
					'key'     => $change['key'],
					'value'   => $change['value'],
					'source'  => $change['source'],
				);
				if ( $run ) {
					update_post_meta( $post_id, $change['key'], $change['value'] );
				}
			}
		}
		if ( array() === $rows ) {
			\WP_CLI::success( 'Nothing to copy.' );
		} else {
			\WP_CLI\Utils\format_items( 'table', $rows, array( 'post_id', 'key', 'value' ) );
			\WP_CLI::log( sprintf( '%d value(s) %s.', count( $rows ), $run ? 'copied' : 'would be copied' ) );
		}
		$log = $assoc_args['log'] ?? '';
		if ( $run && '' === $log ) {
			$uploads = wp_upload_dir();
			$log     = trailingslashit( $uploads['basedir'] ) . 'mh-seo-migrate-' . gmdate( 'Ymd-His' ) . '.csv';
		}
		if ( '' !== $log && array() !== $rows ) {
			$this->write_csv( $log, $rows );
			\WP_CLI::log( 'Log: ' . $log );
		}
		\WP_CLI::log( 'Schema blocks such as rank_math_schema_Service are not copied. Re-add those by hand if a draft needs them.' );
		if ( ! $run ) {
			\WP_CLI::log( 'Dry run only. Pass --run to write.' );
		}
	}

	/**
	 * Recalculate stored scores.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Count the posts without writing scores.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mh-seo score
	 *
	 * @param array<int, string>    $args       Positional args.
	 * @param array<string, string> $assoc_args Flags.
	 * @return void
	 */
	public function score( array $args, array $assoc_args ): void {
		unset( $args );
		$ids = get_posts(
			array(
				'post_type'      => mh_seo_post_types(),
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		if ( isset( $assoc_args['dry-run'] ) ) {
			\WP_CLI::success( sprintf( '%d posts would be scored.', count( $ids ) ) );
			return;
		}
		$score = new Score();
		foreach ( $ids as $id ) {
			$value = $score->recompute( (int) $id );
			\WP_CLI::log( sprintf( '%d → %d', (int) $id, $value ) );
		}
		\WP_CLI::success( sprintf( 'Scored %d posts.', count( $ids ) ) );
	}

	/**
	 * Write the migration log.
	 *
	 * @param string                           $path Path.
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @return void
	 */
	private function write_csv( string $path, array $rows ): void {
		$handle = fopen( $path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CLI log file, not a request upload.
		if ( false === $handle ) {
			\WP_CLI::warning( 'Could not write the log file.' );
			return;
		}
		fputcsv( $handle, array( 'post_id', 'key', 'value', 'source' ) );
		foreach ( $rows as $row ) {
			fputcsv( $handle, array( $row['post_id'], $row['key'], $row['value'], $row['source'] ) );
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
}
