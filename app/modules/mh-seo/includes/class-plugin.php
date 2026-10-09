<?php
/**
 * Wires the plugin together.
 *
 * @package MH_SEO
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Boots every feature and answers the "are we printing the head?" question.
 */
final class Plugin {
	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		( new Meta() )->hooks();
		( new Head() )->hooks();
		( new Sitemap() )->hooks();
		( new Redirects() )->hooks();
		( new IndexNow() )->hooks();
		( new Score() )->hooks();
		( new Admin() )->hooks();
		( new Rest_Controller() )->hooks();
		( new Assets() )->hooks();
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'mh-seo', CLI::class );
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_textdomain( 'mh-seo', MH_SEO_PATH . 'languages/mh-seo-' . determine_locale() . '.mo' );
	}

	/**
	 * Whether this request should print MH SEO head tags.
	 *
	 * Automatic mode stays quiet while Rank Math is active so the two plugins
	 * never print two titles, two descriptions, or two schema graphs.
	 *
	 * @return bool
	 */
	public static function is_managing_head(): bool {
		$mode = (string) ( Settings::get()['head_output'] ?? 'auto' );
		if ( 'off' === $mode ) {
			$managing = false;
		} elseif ( 'on' === $mode ) {
			$managing = true;
		} else {
			$managing = ! mh_seo_rank_math_is_active();
		}
		return (bool) apply_filters( 'mh_seo_is_managing_head', $managing );
	}
}
