<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function activate(): void
{
    $GLOBALS['mhn_activating'] = true;

    try {
        install_tables();
        migrate_legacy();
        ensure_pages();
        schedule_cron();
        update_option('mhn_version', MHN_VERSION);
        flush_rewrite_rules();
    } finally {
        $GLOBALS['mhn_activating'] = false;
    }
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
    if (upgrade_is_due() && upgrade_context_allowed()) {
        maybe_upgrade();
    }

    add_action('init', __NAMESPACE__.'\\load_textdomain');
    add_action('init', __NAMESPACE__.'\\register_type');
    add_action('init', __NAMESPACE__.'\\register_patterns', 100);
    add_action('init', __NAMESPACE__.'\\register_signup_block');
    add_action('rest_api_init', __NAMESPACE__.'\\register_subscribe_route');
    add_filter('allowed_block_types_all', __NAMESPACE__.'\\allowed_blocks', 10, 2);
    add_action('transition_post_status', __NAMESPACE__.'\\on_transition', 10, 3);
    add_action('added_post_meta', __NAMESPACE__.'\\refresh_auto_body_for_thumbnail', 10, 3);
    add_action('updated_post_meta', __NAMESPACE__.'\\refresh_auto_body_for_thumbnail', 10, 3);
    add_action('add_meta_boxes', __NAMESPACE__.'\\add_meta_box');
    add_action('save_post_newsletter_issue', __NAMESPACE__.'\\save_meta', 10, 2);
    add_action('admin_menu', __NAMESPACE__.'\\admin_menu');
    add_action('admin_enqueue_scripts', __NAMESPACE__.'\\admin_assets');
    add_action('wp_ajax_mhn_wizard_autosave', __NAMESPACE__.'\\ajax_wizard_autosave');
    add_action('wp_ajax_mhn_post_blocks', __NAMESPACE__.'\\ajax_post_blocks');
    add_action('wp_ajax_mhn_emulator_preview', __NAMESPACE__.'\\ajax_emulator_preview');
    add_filter('mce_external_plugins', __NAMESPACE__.'\\editor_mce_plugins', 10, 2);
    add_filter('mce_buttons', __NAMESPACE__.'\\editor_mce_buttons', 10, 2);
    add_filter('tiny_mce_before_init', __NAMESPACE__.'\\editor_mce_init', 10, 2);
    add_action('admin_post_mhn_wizard_preview', __NAMESPACE__.'\\handle_wizard_preview');
    add_action('admin_post_mhn_layout_preview', __NAMESPACE__.'\\handle_layout_preview');
    add_action('admin_post_mhn_signup', __NAMESPACE__.'\\handle_signup');
    add_action('admin_post_nopriv_mhn_signup', __NAMESPACE__.'\\handle_signup');
    add_action('admin_post_mhn_export', __NAMESPACE__.'\\handle_export');
    add_action('admin_post_mhn_subscriber_delete', __NAMESPACE__.'\\handle_delete');
    add_action('admin_post_mhn_subscriber_unsub', __NAMESPACE__.'\\handle_admin_unsub');
    add_action('admin_post_mhn_import', __NAMESPACE__.'\\handle_import');
    add_action('admin_post_mhn_subscriber_names', __NAMESPACE__.'\\handle_subscriber_names');
    add_action('admin_post_mhn_settings', __NAMESPACE__.'\\handle_settings');
    add_action('admin_post_mhn_send_test', __NAMESPACE__.'\\handle_send_test');
    add_action('admin_post_mhn_send', __NAMESPACE__.'\\handle_send');
    add_action('admin_post_mhn_schedule', __NAMESPACE__.'\\handle_schedule');
    add_action('admin_post_mhn_retry', __NAMESPACE__.'\\handle_retry');
    add_action('admin_post_mhn_archive_html', __NAMESPACE__.'\\handle_archive_html');
    add_action('admin_post_mhn_archive_eml', __NAMESPACE__.'\\handle_archive_eml');
    add_action('admin_post_mhn_archive_csv', __NAMESPACE__.'\\handle_archive_csv');
    add_action('admin_post_mhn_archive_frame', __NAMESPACE__.'\\handle_archive_frame');
    add_action('admin_post_mhn_archive_delete', __NAMESPACE__.'\\handle_archive_delete');
    add_action('admin_post_mhn_archive_state', __NAMESPACE__.'\\handle_archive_state');
    add_action('admin_post_mhn_duplicate_issue', __NAMESPACE__.'\\handle_duplicate_issue');
    add_filter('wp_insert_post_data', __NAMESPACE__.'\\protect_sent_issue', 10, 2);
    add_action('pre_get_posts', __NAMESPACE__.'\\filter_issue_admin_list');
    add_filter('views_edit-newsletter_issue', __NAMESPACE__.'\\issue_admin_views');
    add_action('admin_notices', __NAMESPACE__.'\\sent_issue_notice');
    add_action('wp_enqueue_scripts', __NAMESPACE__.'\\public_assets');
    add_shortcode('mhn_updates', __NAMESPACE__.'\\shortcode_updates');
    add_shortcode('mhn_preferences', __NAMESPACE__.'\\shortcode_preferences');
    add_shortcode('mhn_unsubscribe', __NAMESPACE__.'\\shortcode_unsubscribe');
    add_action('template_redirect', __NAMESPACE__.'\\on_template_redirect');
    add_filter('wp_robots', __NAMESPACE__.'\\robots');
    add_filter('rank_math/frontend/robots', __NAMESPACE__.'\\rank_math_robots', 99);
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

function upgrade_is_due(): bool
{
    $installed = (string) get_option('mhn_version', '');

    return $installed !== MHN_VERSION || (string) get_option('mhn_db_version') !== '4';
}

/**
 * Page creation used to run on the front end until the version flag was saved.
 * Keep that work on activation and wp-admin so anonymous requests cannot race it.
 */
function upgrade_context_allowed(): bool
{
    if (! empty($GLOBALS['mhn_activating'])) {
        return true;
    }
    if (wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    return is_admin();
}

function maybe_upgrade(): void
{
    if (! upgrade_is_due()) {
        return;
    }
    if (! acquire_named_lock('mhn_upgrade_lock')) {
        return;
    }

    $GLOBALS['mhn_activating'] = true;

    try {
        if (! upgrade_is_due()) {
            return;
        }
        install_tables();
        migrate_legacy();
        ensure_pages();
        schedule_cron();
        update_option('mhn_version', MHN_VERSION);
    } finally {
        $GLOBALS['mhn_activating'] = false;
        release_named_lock('mhn_upgrade_lock');
    }
}

function install_tables(): void
{
    global $wpdb;

    $charset = $wpdb->get_charset_collate();
    $subscribers = subscribers_table();
    $events = events_table();
    $archive = archive_table();

    $subscribersSql = "CREATE TABLE {$subscribers} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL DEFAULT '',
        first_name varchar(100) NOT NULL DEFAULT '',
        last_name varchar(100) NOT NULL DEFAULT '',
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

    $archiveSql = "CREATE TABLE {$archive} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        issue_id bigint(20) unsigned NOT NULL DEFAULT 0,
        source_post bigint(20) unsigned NOT NULL DEFAULT 0,
        subject longtext NOT NULL,
        preheader longtext NOT NULL,
        from_name varchar(191) NOT NULL DEFAULT '',
        from_email varchar(191) NOT NULL DEFAULT '',
        template varchar(40) NOT NULL DEFAULT '',
        started_at varchar(19) NOT NULL DEFAULT '',
        finished_at varchar(19) NOT NULL DEFAULT '',
        sender_id bigint(20) unsigned NOT NULL DEFAULT 0,
        recipients int(10) unsigned NOT NULL DEFAULT 0,
        delivered int(10) unsigned NOT NULL DEFAULT 0,
        failed int(10) unsigned NOT NULL DEFAULT 0,
        list_label varchar(80) NOT NULL DEFAULT '',
        plugin_version varchar(20) NOT NULL DEFAULT '',
        html longtext NOT NULL,
        body_text longtext NOT NULL,
        created_at varchar(19) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY issue_id (issue_id),
        KEY source_post (source_post),
        KEY finished_at (finished_at),
        KEY template (template)
    ) {$charset};";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($subscribersSql);
    dbDelta($eventsSql);
    dbDelta($archiveSql);
    ensure_subscriber_columns();
    ensure_archive_table();
    ensure_archive_source_column();
    update_option('mhn_db_version', '4');
}

/**
 * Add subscriber columns that shipped after the first install.
 * Safe to run more than once. Plugin version stays 1.0.0; this uses mhn_db_version.
 */
function ensure_subscriber_columns(): void
{
    if (subscriber_column_exists('last_name')) {
        return;
    }

    global $wpdb;

    $wpdb->query('ALTER TABLE '.subscribers_table().' ADD last_name varchar(100) NOT NULL DEFAULT \'\'');
}

/**
 * Create the sent-archive table when dbDelta did not.
 * Safe to run more than once. Rows are inserted later and are not rewritten here.
 */
function ensure_archive_table(): void
{
    if (archive_table_exists()) {
        return;
    }

    global $wpdb;

    $charset = $wpdb->get_charset_collate();
    $table = archive_table();
    $wpdb->query("CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        issue_id bigint(20) unsigned NOT NULL DEFAULT 0,
        source_post bigint(20) unsigned NOT NULL DEFAULT 0,
        subject longtext NOT NULL,
        preheader longtext NOT NULL,
        from_name varchar(191) NOT NULL DEFAULT '',
        from_email varchar(191) NOT NULL DEFAULT '',
        template varchar(40) NOT NULL DEFAULT '',
        started_at varchar(19) NOT NULL DEFAULT '',
        finished_at varchar(19) NOT NULL DEFAULT '',
        sender_id bigint(20) unsigned NOT NULL DEFAULT 0,
        recipients int(10) unsigned NOT NULL DEFAULT 0,
        delivered int(10) unsigned NOT NULL DEFAULT 0,
        failed int(10) unsigned NOT NULL DEFAULT 0,
        list_label varchar(80) NOT NULL DEFAULT '',
        plugin_version varchar(20) NOT NULL DEFAULT '',
        html longtext NOT NULL,
        body_text longtext NOT NULL,
        created_at varchar(19) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY issue_id (issue_id),
        KEY source_post (source_post),
        KEY finished_at (finished_at),
        KEY template (template)
    ) {$charset}");
}

/**
 * Store the source post on sent rows so a Blog post letter can skip a repeat.
 * Safe to run more than once.
 */
function ensure_archive_source_column(): void
{
    if (! archive_table_exists() || archive_has_source_column()) {
        return;
    }

    global $wpdb;

    $wpdb->query('ALTER TABLE '.archive_table().' ADD source_post bigint(20) unsigned NOT NULL DEFAULT 0');
    $wpdb->query('ALTER TABLE '.archive_table().' ADD KEY source_post (source_post)');
}

function archive_table_exists(): bool
{
    global $wpdb;

    $suppressed = $wpdb->suppress_errors(true);
    $wpdb->get_var('SELECT id FROM '.archive_table().' LIMIT 1');
    $missing = (string) $wpdb->last_error !== '';
    $wpdb->last_error = '';
    $wpdb->suppress_errors($suppressed);

    return ! $missing;
}

function subscriber_column_exists(string $column): bool
{
    global $wpdb;

    if (! preg_match('/^[a-z_]+$/', $column)) {
        return false;
    }

    $suppressed = $wpdb->suppress_errors(true);
    $wpdb->get_var('SELECT '.$column.' FROM '.subscribers_table().' LIMIT 1');
    $missing = (string) $wpdb->last_error !== '';
    $wpdb->last_error = '';
    $wpdb->suppress_errors($suppressed);

    return ! $missing;
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

        $id = insert_subscriber([
            'email' => $email,
            'first_name' => '',
            'status' => 'subscribed',
            'opt_in' => 'legacy_single',
            'source' => 'legacy',
            'created_at' => $created,
            'confirmed_at' => $created,
        ]);
        if ($id > 0) {
            $copied++;
        }
    }

    return $copied;
}

function ensure_pages(): void
{
    if (! upgrade_context_allowed()) {
        return;
    }

    ensure_page(
        'get-updates',
        __('Get updates', 'matthummel-newsletter'),
        '[mhn_updates]',
        'template-get-updates.blade.php'
    );
    ensure_page(
        'email-preferences',
        __('Manage preferences', 'matthummel-newsletter'),
        '[mhn_preferences]',
        'template-get-updates.blade.php'
    );
    sync_owned_title(
        'email-preferences',
        __('Manage preferences', 'matthummel-newsletter'),
        '[mhn_preferences]'
    );
    ensure_page(
        'unsubscribe',
        __('Unsubscribe', 'matthummel-newsletter'),
        '[mhn_unsubscribe]',
        'template-get-updates.blade.php'
    );

    $thanks = find_owned_page('thank-you');
    if ($thanks instanceof \WP_Post) {
        mark_utility_noindex($thanks);
    }
}

function sync_owned_title(string $slug, string $title, string $shortcode): void
{
    $page = find_owned_page($slug);
    if (! $page instanceof \WP_Post) {
        return;
    }
    if (trim((string) $page->post_content) !== $shortcode) {
        return;
    }
    if ($page->post_title === $title) {
        return;
    }

    wp_update_post([
        'ID' => $page->ID,
        'post_title' => $title,
    ]);
}

/**
 * Reuse a stored page ID, then any page with this slug, before inserting.
 * A lock stops two requests from both deciding the page is missing.
 */
function ensure_page(string $slug, string $title, string $shortcode, string $template): int
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return 0;
    }

    $existing = find_owned_page($slug);
    if ($existing instanceof \WP_Post) {
        finish_owned_page($existing, $slug, $shortcode, $template);

        return (int) $existing->ID;
    }

    $lock = 'mhn_lock_page_'.$slug;
    if (! acquire_named_lock($lock)) {
        $waited = wait_for_owned_page($slug);

        return $waited instanceof \WP_Post ? (int) $waited->ID : 0;
    }

    try {
        $existing = find_owned_page($slug);
        if ($existing instanceof \WP_Post) {
            finish_owned_page($existing, $slug, $shortcode, $template);

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

        $post = get_post((int) $id);
        if (! $post instanceof \WP_Post) {
            return 0;
        }

        if ($post->post_name !== $slug) {
            wp_delete_post((int) $post->ID, true);
            $winner = find_owned_page($slug);

            return $winner instanceof \WP_Post ? (int) $winner->ID : 0;
        }

        remember_owned_page($slug, (int) $post->ID);
        finish_owned_page($post, $slug, $shortcode, $template);

        return (int) $post->ID;
    } finally {
        release_named_lock($lock);
    }
}

function finish_owned_page(\WP_Post $page, string $slug, string $shortcode, string $template): void
{
    if ($page->post_status === 'trash') {
        wp_untrash_post($page->ID);
        $restored = get_post($page->ID);
        if ($restored instanceof \WP_Post) {
            $page = $restored;
        }
    }

    if ($page->post_status !== 'publish' && trim((string) $page->post_content) === $shortcode) {
        wp_update_post([
            'ID' => $page->ID,
            'post_status' => 'publish',
        ]);
    }

    assign_owned_template($page, $shortcode, $template);
    mark_utility_noindex($page);
    remember_owned_page($slug, (int) $page->ID);
}

function find_owned_page(string $slug): ?\WP_Post
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }

    $storedId = (int) get_option(page_option_key($slug), 0);
    if ($storedId > 0) {
        $stored = get_post($storedId);
        if ($stored instanceof \WP_Post && $stored->post_type === 'page' && $stored->post_status !== 'auto-draft') {
            return $stored;
        }
    }

    $found = owned_pages_named($slug);
    if ($found === []) {
        $found = owned_pages_named($slug.'__trashed');
    }
    $page = $found[0] ?? null;
    if (! $page instanceof \WP_Post) {
        return null;
    }

    remember_owned_page($slug, (int) $page->ID);

    return $page;
}

/**
 * @return list<\WP_Post>
 */
function owned_pages_named(string $slug): array
{
    $found = get_posts([
        'post_type' => 'page',
        'name' => $slug,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
        'posts_per_page' => 1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
        'suppress_filters' => true,
    ]);

    $pages = [];
    foreach ($found as $page) {
        if ($page instanceof \WP_Post) {
            $pages[] = $page;
        }
    }

    return $pages;
}

function is_noindex_utility_slug(string $slug): bool
{
    $slug = sanitize_title($slug);
    if (in_array($slug, ['email-preferences', 'unsubscribe', 'thank-you'], true)) {
        return true;
    }

    return preg_match('/^(?:email-preferences|unsubscribe|thank-you)-\d+$/', $slug) === 1;
}

function wait_for_owned_page(string $slug): ?\WP_Post
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $page = find_owned_page($slug);
        if ($page instanceof \WP_Post) {
            return $page;
        }
        usleep(200000);
    }

    return null;
}

function page_option_key(string $slug): string
{
    return 'mhn_page_id_'.sanitize_title($slug);
}

function remember_owned_page(string $slug, int $id): void
{
    if ($id < 1) {
        return;
    }

    $key = page_option_key($slug);
    if ((int) get_option($key, 0) === $id) {
        return;
    }

    update_option($key, $id, false);
}

/**
 * add_option is atomic. A stale lock older than two minutes can be taken again.
 */
function acquire_named_lock(string $key): bool
{
    $key = sanitize_key($key);
    if ($key === '') {
        return false;
    }
    if (add_option($key, (string) time(), '', false)) {
        return true;
    }

    $started = (int) get_option($key, 0);
    if ($started > 0 && (time() - $started) < 120) {
        return false;
    }

    delete_option($key);

    return add_option($key, (string) time(), '', false);
}

function release_named_lock(string $key): void
{
    $key = sanitize_key($key);
    if ($key === '') {
        return;
    }

    delete_option($key);
}

function mark_utility_noindex(\WP_Post $page): void
{
    if (! is_noindex_utility_slug((string) $page->post_name)) {
        return;
    }

    $robots = get_post_meta($page->ID, 'rank_math_robots', true);
    $wanted = ['noindex', 'nofollow'];
    if ($robots === $wanted) {
        return;
    }

    update_post_meta($page->ID, 'rank_math_robots', $wanted);
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
    $page = find_owned_page($slug);
    if ($page instanceof \WP_Post && $page->post_status !== 'trash') {
        $url = get_permalink($page);
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return home_url('/'.sanitize_title($slug).'/');
}
