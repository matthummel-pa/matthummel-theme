<?php

/**
 * Remove newsletter tables, issues, and settings. Pages are left in place.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'mhn_subscribers');
$wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'mhn_events');

delete_option('mhn_settings');
delete_option('mhn_db_version');
delete_option('mhn_version');

$issueIds = $wpdb->get_col($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
    'newsletter_issue'
));
foreach (is_array($issueIds) ? $issueIds : [] as $issueId) {
    wp_delete_post((int) $issueId, true);
}

wp_clear_scheduled_hook('mhn_cron_tick');
wp_clear_scheduled_hook('mhn_send_batch');

if (function_exists('as_unschedule_all_actions')) {
    as_unschedule_all_actions('mhn_send_batch', [], 'matthummel-newsletter');
}
