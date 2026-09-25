<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function activate(): void
{
    install_tables();
    migrate_legacy();
    ensure_pages();
    schedule_cron();
    update_option('mhn_version', MHN_VERSION);
    flush_rewrite_rules();
}

function deactivate(): void
{
    wp_clear_scheduled_hook('mhn_cron_tick');
    $timestamp = wp_next_scheduled('mhn_send_batch');
    while ($timestamp) {
        wp_unschedule_event($timestamp, 'mhn_send_batch');
        $timestamp = wp_next_scheduled('mhn_send_batch');
    }
    if (function_exists('as_unschedule_all_actions')) {
        as_unschedule_all_actions('mhn_send_batch', [], 'matthummel-newsletter');
    }
    flush_rewrite_rules();
}

function boot(): void
{
    maybe_upgrade();

    add_action('init', __NAMESPACE__.'\\load_textdomain');
    add_action('init', __NAMESPACE__.'\\register_type');
    add_action('init', __NAMESPACE__.'\\register_patterns', 100);
    add_filter('allowed_block_types_all', __NAMESPACE__.'\\allowed_blocks', 10, 2);
    add_action('transition_post_status', __NAMESPACE__.'\\on_transition', 10, 3);
    add_action('add_meta_boxes', __NAMESPACE__.'\\add_meta_box');
    add_action('save_post_newsletter_issue', __NAMESPACE__.'\\save_meta', 10, 2);
    add_action('admin_menu', __NAMESPACE__.'\\admin_menu');
    add_action('admin_enqueue_scripts', __NAMESPACE__.'\\admin_assets');
    add_action('admin_post_mhn_signup', __NAMESPACE__.'\\handle_signup');
    add_action('admin_post_nopriv_mhn_signup', __NAMESPACE__.'\\handle_signup');
    add_action('admin_post_mhn_export', __NAMESPACE__.'\\handle_export');
    add_action('admin_post_mhn_subscriber_delete', __NAMESPACE__.'\\handle_delete');
    add_action('admin_post_mhn_subscriber_unsub', __NAMESPACE__.'\\handle_admin_unsub');
    add_action('admin_post_mhn_import', __NAMESPACE__.'\\handle_import');
    add_action('admin_post_mhn_settings', __NAMESPACE__.'\\handle_settings');
    add_action('admin_post_mhn_send_test', __NAMESPACE__.'\\handle_send_test');
    add_action('admin_post_mhn_send', __NAMESPACE__.'\\handle_send');
    add_action('admin_post_mhn_schedule', __NAMESPACE__.'\\handle_schedule');
    add_action('admin_post_mhn_retry', __NAMESPACE__.'\\handle_retry');
    add_action('wp_enqueue_scripts', __NAMESPACE__.'\\public_assets');
    add_shortcode('mhn_updates', __NAMESPACE__.'\\shortcode_updates');
    add_shortcode('mhn_preferences', __NAMESPACE__.'\\shortcode_preferences');
    add_action('template_redirect', __NAMESPACE__.'\\on_template_redirect');
    add_filter('wp_robots', __NAMESPACE__.'\\robots');
    add_action('phpmailer_init', __NAMESPACE__.'\\on_phpmailer');
    add_filter('wp_mail_from', __NAMESPACE__.'\\filter_from');
    add_filter('wp_mail_from_name', __NAMESPACE__.'\\filter_from_name');
    add_filter('cron_schedules', __NAMESPACE__.'\\cron_schedules');
    add_action('mhn_cron_tick', __NAMESPACE__.'\\cron_tick');
    add_action('mhn_send_batch', __NAMESPACE__.'\\send_batch', 10, 1);
}

function load_textdomain(): void
{
    load_plugin_textdomain(
        'matthummel-newsletter',
        false,
        dirname(plugin_basename(MHN_FILE)).'/languages'
    );
}

function maybe_upgrade(): void
{
    $installed = (string) get_option('mhn_version', '');
    if ($installed === MHN_VERSION && get_option('mhn_db_version') === '1') {
        return;
    }

    install_tables();
    migrate_legacy();
    ensure_pages();
    schedule_cron();
    update_option('mhn_version', MHN_VERSION);
}

function install_tables(): void
{
    global $wpdb;

    $charset = $wpdb->get_charset_collate();
    $subscribers = subscribers_table();
    $events = events_table();

    $subscribersSql = "CREATE TABLE {$subscribers} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL DEFAULT '',
        first_name varchar(100) NOT NULL DEFAULT '',
        status varchar(20) NOT NULL DEFAULT 'pending',
        opt_in varchar(20) NOT NULL DEFAULT 'double',
        confirm_hash varchar(64) NOT NULL DEFAULT '',
        source varchar(40) NOT NULL DEFAULT '',
        created_at varchar(19) NOT NULL DEFAULT '',
        confirmed_at varchar(19) NOT NULL DEFAULT '',
        unsubscribed_at varchar(19) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        UNIQUE KEY email (email),
        KEY status (status),
        KEY confirm_hash (confirm_hash)
    ) {$charset};";

    $eventsSql = "CREATE TABLE {$events} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        issue_id bigint(20) unsigned NOT NULL DEFAULT 0,
        subscriber_id bigint(20) unsigned NOT NULL DEFAULT 0,
        event varchar(20) NOT NULL DEFAULT '',
        detail varchar(255) NOT NULL DEFAULT '',
        created_at varchar(19) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY issue_event (issue_id, event),
        KEY subscriber_event (subscriber_id, event)
    ) {$charset};";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($subscribersSql);
    dbDelta($eventsSql);
    update_option('mhn_db_version', '1');
}

/**
 * Copy footer-list rows into this plugin. They stay subscribed.
 * Those addresses used single opt-in before this plugin existed, so they are
 * not asked to confirm again. New signups use double opt-in.
 */
function migrate_legacy(): int
{
    global $wpdb;

    $legacy = $wpdb->prefix.'mh_newsletter';
    $like = $wpdb->esc_like($legacy);
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
    if ($found !== $legacy) {
        return 0;
    }

    $rows = $wpdb->get_results("SELECT email, created_at FROM {$legacy}", ARRAY_A);
    $copied = 0;
    foreach (is_array($rows) ? $rows : [] as $row) {
        $email = strtolower(sanitize_email((string) ($row['email'] ?? '')));
        if (! is_email($email) || find_by_email($email)) {
            continue;
        }

        $created = (string) ($row['created_at'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}/', $created)) {
            $created = current_time('mysql');
        }

        insert_subscriber([
            'email' => $email,
            'first_name' => '',
            'status' => 'subscribed',
            'opt_in' => 'legacy_single',
            'source' => 'legacy',
            'created_at' => $created,
            'confirmed_at' => $created,
        ]);
        $copied++;
    }

    return $copied;
}

function ensure_pages(): void
{
    ensure_page(
        'get-updates',
        __('Get updates', 'matthummel-newsletter'),
        '[mhn_updates]',
        'template-get-updates.blade.php'
    );
    ensure_page(
        'email-preferences',
        __('Email preferences', 'matthummel-newsletter'),
        '[mhn_preferences]',
        'template-get-updates.blade.php'
    );
}

function ensure_page(string $slug, string $title, string $shortcode, string $template): int
{
    $existing = get_page_by_path($slug);
    if ($existing instanceof \WP_Post) {
        assign_owned_template($existing, $shortcode, $template);

        return (int) $existing->ID;
    }

    $id = wp_insert_post([
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => $shortcode,
    ], true);

    if (is_wp_error($id) || ! $id) {
        return 0;
    }

    if ($template !== '' && theme_view_exists($template)) {
        update_post_meta((int) $id, '_wp_page_template', $template);
    }

    return (int) $id;
}

function theme_view_exists(string $template): bool
{
    if ($template === '') {
        return false;
    }

    $relative = 'resources/views/'.$template;
    if (locate_template([$template, $relative])) {
        return true;
    }

    $dir = get_stylesheet_directory();

    return is_readable($dir.'/'.$relative) || is_readable($dir.'/'.$template);
}

function assign_owned_template(\WP_Post $page, string $shortcode, string $template): void
{
    if (! theme_view_exists($template)) {
        return;
    }

    $current = (string) get_post_meta($page->ID, '_wp_page_template', true);
    if ($current !== '' && $current !== 'default') {
        return;
    }

    if (trim((string) $page->post_content) !== $shortcode) {
        return;
    }

    update_post_meta($page->ID, '_wp_page_template', $template);
}

function schedule_cron(): void
{
    if (! wp_next_scheduled('mhn_cron_tick')) {
        wp_schedule_event(time() + 60, 'mhn_five_minutes', 'mhn_cron_tick');
    }
}

/**
 * @param  array<string, array{interval: int, display: string}>  $schedules
 * @return array<string, array{interval: int, display: string}>
 */
function cron_schedules(array $schedules): array
{
    $schedules['mhn_five_minutes'] = [
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display' => __('Every five minutes', 'matthummel-newsletter'),
    ];

    return $schedules;
}

function page_url(string $slug): string
{
    $page = get_page_by_path($slug);
    if ($page instanceof \WP_Post) {
        $url = get_permalink($page);
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return home_url('/'.$slug.'/');
}
