<?php

/**
 * First-party footer newsletter: store emails in a theme table, list + CSV in wp-admin.
 */

namespace App;

/**
 * Newsletter subscribers table name (with prefix).
 *
 * @since 3.6.20
 */
function mh_newsletter_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mh_newsletter';
}

/**
 * The newsletter plugin owns signup, storage, and the admin screen when it is active.
 */
function mh_newsletter_handed_off(): bool
{
    return defined('MHN_VERSION');
}

/**
 * Create or upgrade the subscribers table.
 *
 * @since 3.6.20
 */
function mh_newsletter_install(): void
{
    global $wpdb;

    $table = mh_newsletter_table();
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(190) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email)
    ) {$charset};";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('mh_newsletter_db_version', '1');
}

add_action('after_setup_theme', function (): void {
    if (mh_newsletter_handed_off()) {
        return;
    }
    if (get_option('mh_newsletter_db_version') !== '1') {
        mh_newsletter_install();
    }
});

/**
 * Hash the visitor IP for rate limiting (not stored on the subscriber row).
 *
 * @since 3.6.20
 */
function mh_newsletter_rate_key(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return 'mh_nl_'.md5($ip.'|'.$ua);
}

/**
 * Redirect back to the signup form with a status query arg.
 *
 * @since 3.6.20
 *
 * @param  string  $status  ok|dup|error
 */
function mh_newsletter_redirect(string $status): void
{
    $back = wp_get_referer();
    if (! is_string($back) || $back === '') {
        $back = home_url('/');
    }
    $back = remove_query_arg('signup', $back);
    wp_safe_redirect(add_query_arg('signup', $status, $back).'#footer-signup');
    exit;
}

/**
 * Insert a unique subscriber email.
 *
 * @since 3.6.20
 *
 * @return 'ok'|'dup'|'error'
 */
function mh_newsletter_add(string $email): string
{
    global $wpdb;

    $table = mh_newsletter_table();
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE email = %s LIMIT 1",
        $email
    ));
    if ($exists) {
        return 'dup';
    }

    $ok = $wpdb->insert(
        $table,
        [
            'email' => $email,
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s']
    );

    return $ok ? 'ok' : 'error';
}

add_action('init', function (): void {
    if (mh_newsletter_handed_off()) {
        return;
    }

    $postedAction = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
    if ($postedAction !== 'mh_newsletter') {
        return;
    }

    $nonce = isset($_POST['mh_newsletter_nonce']) ? wp_unslash($_POST['mh_newsletter_nonce']) : '';
    if (! is_string($nonce) || ! wp_verify_nonce($nonce, 'mh_newsletter')) {
        mh_newsletter_redirect('error');
    }

    if (! empty($_POST['mh_nl_hp'])) {
        mh_newsletter_redirect('ok');
    }

    $rateKey = mh_newsletter_rate_key();
    $hits = (int) get_transient($rateKey);
    if ($hits >= 8) {
        mh_newsletter_redirect('error');
    }
    set_transient($rateKey, $hits + 1, HOUR_IN_SECONDS);

    $email = sanitize_email(wp_unslash($_POST['mh_nl_email'] ?? ''));
    if (! is_email($email)) {
        mh_newsletter_redirect('error');
    }

    mh_newsletter_redirect(mh_newsletter_add(strtolower($email)));
});

add_action('admin_menu', function (): void {
    if (mh_newsletter_handed_off()) {
        return;
    }

    add_menu_page(
        __('Get updates', 'sage'),
        __('Get updates', 'sage'),
        'manage_options',
        'mh-newsletter',
        __NAMESPACE__.'\\mh_newsletter_render_admin',
        'dashicons-email-alt',
        58
    );
});

add_action('admin_post_mh_newsletter_export', function (): void {
    if (mh_newsletter_handed_off()) {
        return;
    }
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'sage'), 403);
    }
    check_admin_referer('mh_newsletter_export');

    global $wpdb;
    $table = mh_newsletter_table();
    $rows = $wpdb->get_results("SELECT email, created_at FROM {$table} ORDER BY created_at DESC", ARRAY_A);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=get-updates-'.gmdate('Y-m-d').'.csv');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        wp_die(esc_html__('Could not write the export.', 'sage'));
    }
    fputcsv($out, ['email', 'created_at']);
    foreach (is_array($rows) ? $rows : [] as $row) {
        fputcsv($out, [
            (string) ($row['email'] ?? ''),
            (string) ($row['created_at'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
});

add_action('admin_post_mh_newsletter_delete', function (): void {
    if (mh_newsletter_handed_off()) {
        return;
    }
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'sage'), 403);
    }
    check_admin_referer('mh_newsletter_delete');

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ($id > 0) {
        global $wpdb;
        $wpdb->delete(mh_newsletter_table(), ['id' => $id], ['%d']);
    }

    wp_safe_redirect(admin_url('admin.php?page=mh-newsletter&deleted=1'));
    exit;
});

/**
 * Render the Get updates admin screen.
 *
 * @since 3.6.20
 */
function mh_newsletter_render_admin(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'sage'), 403);
    }

    global $wpdb;
    $table = mh_newsletter_table();
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    $where = '';
    $params = [];
    if ($search !== '') {
        $where = 'WHERE email LIKE %s';
        $params[] = '%'.$wpdb->esc_like($search).'%';
    }

    $countSql = "SELECT COUNT(*) FROM {$table} {$where}";
    $total = (int) ($params === []
        ? $wpdb->get_var($countSql)
        : $wpdb->get_var($wpdb->prepare($countSql, ...$params)));

    $listSql = "SELECT id, email, created_at FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $listParams = array_merge($params, [$perPage, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($listSql, ...$listParams), ARRAY_A);

    $pages = max(1, (int) ceil($total / $perPage));
    $exportUrl = wp_nonce_url(admin_url('admin-post.php?action=mh_newsletter_export'), 'mh_newsletter_export');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Get updates', 'sage'); ?></h1>
        <p><?php echo esc_html__('Addresses from the footer signup. Stored in this WordPress database. No newsletter plugin.', 'sage'); ?></p>
        <?php if (isset($_GET['deleted'])) { ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Removed.', 'sage'); ?></p></div>
        <?php } ?>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($exportUrl); ?>"><?php echo esc_html__('Export CSV', 'sage'); ?></a>
            <span class="description"><?php echo esc_html(sprintf(__('%d signed up.', 'sage'), $total)); ?></span>
        </p>
        <form method="get">
            <input type="hidden" name="page" value="mh-newsletter">
            <p class="search-box">
                <label class="screen-reader-text" for="mh-nl-search"><?php echo esc_html__('Search emails', 'sage'); ?></label>
                <input type="search" id="mh-nl-search" name="s" value="<?php echo esc_attr($search); ?>">
                <button type="submit" class="button"><?php echo esc_html__('Search', 'sage'); ?></button>
            </p>
        </form>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Email', 'sage'); ?></th>
                    <th scope="col"><?php echo esc_html__('Signed up', 'sage'); ?></th>
                    <th scope="col"><?php echo esc_html__('Remove', 'sage'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (! $rows) { ?>
                <tr><td colspan="3"><?php echo esc_html__('No signups yet.', 'sage'); ?></td></tr>
            <?php } else { ?>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td><?php echo esc_html((string) ($row['email'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($row['created_at'] ?? '')); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Remove this address?', 'sage')); ?>');">
                                <?php wp_nonce_field('mh_newsletter_delete'); ?>
                                <input type="hidden" name="action" value="mh_newsletter_delete">
                                <input type="hidden" name="id" value="<?php echo esc_attr((string) ($row['id'] ?? '')); ?>">
                                <button type="submit" class="button-link"><?php echo esc_html__('Remove', 'sage'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
        <?php if ($pages > 1) { ?>
            <p>
                <?php for ($i = 1; $i <= $pages; $i++) { ?>
                    <?php
                    $url = add_query_arg([
                        'page' => 'mh-newsletter',
                        'paged' => $i,
                        's' => $search !== '' ? $search : false,
                    ], admin_url('admin.php'));
                    ?>
                    <?php if ($i === $page) { ?>
                        <strong><?php echo esc_html((string) $i); ?></strong>
                    <?php } else { ?>
                        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html((string) $i); ?></a>
                    <?php } ?>
                <?php } ?>
            </p>
        <?php } ?>
    </div>
    <?php
}
