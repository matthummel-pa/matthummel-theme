<?php

/**
 * Wires the plugin together.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Boots every feature and answers the "are we printing the head?" question.
 */
final class Plugin
{
    /**
     * Singleton.
     */
    private static ?self $instance = null;

    /**
     * Get the plugin instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register hooks.
     */
    public function boot(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        (new Meta)->hooks();
        (new Head)->hooks();
        (new Sitemap)->hooks();
        (new Redirects)->hooks();
        (new IndexNow)->hooks();
        (new Score)->hooks();
        (new Admin)->hooks();
        (new Rest_Controller)->hooks();
        (new Assets)->hooks();
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('mh-seo', CLI::class);
        }
    }

    /**
     * Load translations.
     */
    public function load_textdomain(): void
    {
        load_textdomain('mh-seo', MH_SEO_PATH.'languages/mh-seo-'.determine_locale().'.mo');
    }

    /**
     * Whether this request should print MH SEO head tags.
     *
     * Automatic mode stays quiet while Rank Math is active so the two plugins
     * never print two titles, two descriptions, or two schema graphs.
     */
    public static function is_managing_head(): bool
    {
        $mode = (string) (Settings::get()['head_output'] ?? 'auto');
        if ($mode === 'off') {
            $managing = false;
        } elseif ($mode === 'on') {
            $managing = true;
        } else {
            $managing = ! mh_seo_rank_math_is_active();
        }

        return (bool) apply_filters('mh_seo_is_managing_head', $managing);
    }
}
