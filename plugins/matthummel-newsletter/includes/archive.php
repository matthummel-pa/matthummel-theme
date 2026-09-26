<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * List name stored on a snapshot. Never an address.
 */
function audience_label(): string
{
    $list = apply_filters('mhn_send_allowlist', null);
    if ($list !== null) {
        return __('Allowlist', 'matthummel-newsletter');
    }

    return __('Subscribed', 'matthummel-newsletter');
}

/**
 * Render the issue the way it was sent, with merge tags left as placeholders.
 *
 * @return array{subject: string, preheader: string, html: string, text: string}
 */
function issue_archive_message(int $issueId): array
{
    $post = get_post($issueId);
    $subject = trim((string) get_post_meta($issueId, '_mhn_subject', true));
    if ($subject === '' && $post instanceof \WP_Post) {
        $subject = $post->post_title;
    }
    if ($subject === '') {
        $subject = (string) get_bloginfo('name');
    }

    $preheader = trim((string) get_post_meta($issueId, '_mhn_preheader', true));
    $content = $post instanceof \WP_Post ? (string) $post->post_content : '';
    $altFallback = $post instanceof \WP_Post ? $post->post_title : $subject;
    $body = render_blocks($content, $altFallback);
    $body = apply_issue_layout($issueId, $body);
    $subject = layout_subject($issueId, $subject);
    $includeRecent = (string) get_post_meta($issueId, '_mhn_include_recent', true) === '1';
    $sourceId = (int) get_post_meta($issueId, '_mhn_source_post', true);
    if ($preheader === '') {
        $preheader = mb_substr(trim(wp_strip_all_tags($body)), 0, 140);
    }

    $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
    $document = email_document($subject, $preheader, $body, $includeRecent, $sourceId);

    return [
        'subject' => $subject,
        'preheader' => $preheader,
        'html' => $document,
        'text' => plain_text($document),
    ];
}

/**
 * Insert one immutable row for a finished send. A later edit does not update it.
 * The same start time is stored once, so a repeated finish does not add a second row.
 */
function store_sent_snapshot(int $issueId): int
{
    global $wpdb;

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return 0;
    }
    if (! archive_table_exists()) {
        return 0;
    }

    $started = (string) get_post_meta($issueId, '_mhn_send_started_at', true);
    if ($started === '') {
        $started = current_time('mysql');
    }

    $existing = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT id FROM '.archive_table().' WHERE issue_id = %d AND started_at = %s LIMIT 1',
        $issueId,
        $started
    ));
    if ($existing > 0) {
        return $existing;
    }

    $message = issue_archive_message($issueId);
    $settings = settings();
    $finished = current_time('mysql');
    $template = sanitize_key((string) get_post_meta($issueId, '_mhn_template', true));
    if ($template === '') {
        $template = 'custom';
    }
    $list = (string) get_post_meta($issueId, '_mhn_list_label', true);
    if ($list === '') {
        $list = audience_label();
    }

    $inserted = $wpdb->insert(
        archive_table(),
        [
            'issue_id' => $issueId,
            'subject' => $message['subject'],
            'preheader' => $message['preheader'],
            'from_name' => $settings['from_name'],
            'from_email' => $settings['from_email'],
            'template' => $template,
            'started_at' => $started,
            'finished_at' => $finished,
            'sender_id' => (int) get_post_meta($issueId, '_mhn_sender_id', true),
            'recipients' => (int) get_post_meta($issueId, '_mhn_recipient_count', true),
            'delivered' => count_issue_event($issueId, 'sent'),
            'failed' => count_issue_event($issueId, 'failed'),
            'list_label' => $list,
            'plugin_version' => MHN_VERSION,
            'html' => $message['html'],
            'body_text' => $message['text'],
            'created_at' => $finished,
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
    );

    if ($inserted === false) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

/**
 * @return array<string, string>|null
 */
function sent_snapshot(int $id): ?array
{
    global $wpdb;

    if ($id < 1 || ! archive_table_exists()) {
        return null;
    }

    $row = $wpdb->get_row($wpdb->prepare(
        'SELECT * FROM '.archive_table().' WHERE id = %d',
        $id
    ), ARRAY_A);
    if (! is_array($row)) {
        return null;
    }

    $out = [];
    foreach ($row as $key => $value) {
        $out[(string) $key] = (string) $value;
    }

    return $out;
}

/**
 * @return array<string, string>|null
 */
function latest_sent_snapshot(int $issueId): ?array
{
    global $wpdb;

    if ($issueId < 1 || ! archive_table_exists()) {
        return null;
    }

    $id = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT id FROM '.archive_table().' WHERE issue_id = %d ORDER BY id DESC LIMIT 1',
        $issueId
    ));

    return $id > 0 ? sent_snapshot($id) : null;
}

function issue_is_archived(int $issueId): bool
{
    return (string) get_post_meta($issueId, '_mhn_archived', true) === '1';
}

function set_issue_archived(int $issueId, bool $archived): void
{
    if ($issueId < 1 || get_post_type($issueId) !== 'newsletter_issue') {
        return;
    }

    update_post_meta($issueId, '_mhn_archived', $archived ? '1' : '0');
}

/**
 * @param  array{show?: string, search?: string, template?: string, from?: string, to?: string, orderby?: string, order?: string, page?: int, per_page?: int}  $args
 * @return array{rows: list<array<string, string>>, total: int, page: int, per_page: int}
 */
function sent_archive_query(array $args): array
{
    global $wpdb;

    $empty = ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 20];
    if (! archive_table_exists()) {
        return $empty;
    }

    $show = (string) ($args['show'] ?? 'active');
    if (! in_array($show, ['active', 'archived', 'all'], true)) {
        $show = 'active';
    }
    $search = mb_substr(sanitize_text_field((string) ($args['search'] ?? '')), 0, 100);
    $template = sanitize_key((string) ($args['template'] ?? ''));
    $from = archive_date((string) ($args['from'] ?? ''));
    $to = archive_date((string) ($args['to'] ?? ''));
    $orderby = (string) ($args['orderby'] ?? 'finished_at');
    if (! in_array($orderby, ['finished_at', 'subject', 'template'], true)) {
        $orderby = 'finished_at';
    }
    $order = strtoupper((string) ($args['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $perPage = max(1, min(100, (int) ($args['per_page'] ?? 20)));
    $page = max(1, (int) ($args['page'] ?? 1));

    $where = ['1=1'];
    $params = [];
    $meta = $wpdb->postmeta;
    if ($show === 'archived') {
        $where[] = "EXISTS (SELECT 1 FROM {$meta} pm WHERE pm.post_id = a.issue_id AND pm.meta_key = '_mhn_archived' AND pm.meta_value = '1')";
    } elseif ($show === 'active') {
        $where[] = "NOT EXISTS (SELECT 1 FROM {$meta} pm WHERE pm.post_id = a.issue_id AND pm.meta_key = '_mhn_archived' AND pm.meta_value = '1')";
    }
    if ($search !== '') {
        $like = '%'.$wpdb->esc_like($search).'%';
        $where[] = '(a.subject LIKE %s OR a.template LIKE %s)';
        $params[] = $like;
        $params[] = $like;
    }
    if ($template !== '') {
        $where[] = 'a.template = %s';
        $params[] = $template;
    }
    if ($from !== '') {
        $where[] = 'a.finished_at >= %s';
        $params[] = $from.' 00:00:00';
    }
    if ($to !== '') {
        $where[] = 'a.finished_at <= %s';
        $params[] = $to.' 23:59:59';
    }

    $sqlWhere = implode(' AND ', $where);
    $table = archive_table();
    $countSql = "SELECT COUNT(*) FROM {$table} a WHERE {$sqlWhere}";
    $total = (int) $wpdb->get_var(archive_prepare($countSql, $params));
    $offset = ($page - 1) * $perPage;
    $listSql = "SELECT a.* FROM {$table} a WHERE {$sqlWhere} ORDER BY a.{$orderby} {$order} LIMIT %d OFFSET %d";
    $listed = $wpdb->get_results(archive_prepare($listSql, array_merge($params, [$perPage, $offset])), ARRAY_A);

    $rows = [];
    foreach (is_array($listed) ? $listed : [] as $row) {
        if (! is_array($row)) {
            continue;
        }
        $clean = [];
        foreach ($row as $key => $value) {
            $clean[(string) $key] = (string) $value;
        }
        $rows[] = $clean;
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

/**
 * @param  list<int|string>  $params
 */
function archive_prepare(string $sql, array $params): string
{
    global $wpdb;

    if ($params === []) {
        return $sql;
    }

    $prepared = $wpdb->prepare($sql, ...$params);

    return is_string($prepared) ? $prepared : $sql;
}

function archive_date(string $value): string
{
    $value = sanitize_text_field($value);

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
}

function template_label(string $slug): string
{
    $template = email_template($slug);
    if ($template === null) {
        return $slug !== '' ? $slug : __('Custom message', 'matthummel-newsletter');
    }

    return $template['label'];
}

function sender_label(int $userId): string
{
    if ($userId < 1) {
        return __('Scheduled', 'matthummel-newsletter');
    }

    $user = get_userdata($userId);
    if (! $user instanceof \WP_User) {
        return '#'.$userId;
    }

    $login = (string) $user->user_login;

    return $login !== '' ? $login : '#'.$userId;
}

function duplicate_issue(int $issueId): int
{
    if (! current_user_can('manage_options')) {
        return 0;
    }

    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        return 0;
    }

    $copyId = wp_insert_post([
        'post_type' => 'newsletter_issue',
        'post_status' => 'draft',
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_author' => get_current_user_id(),
    ], true);
    if (is_wp_error($copyId) || ! $copyId) {
        return 0;
    }

    $copyId = (int) $copyId;
    $keys = [
        '_mhn_template',
        '_mhn_layout',
        '_mhn_editor',
        '_mhn_blocks',
        '_mhn_note',
        '_mhn_ps',
        '_mhn_post_ids',
        '_mhn_include_recent',
        '_mhn_subject',
        '_mhn_preheader',
        '_mhn_subject_auto',
        '_mhn_source_post',
        '_mhn_feature_show',
        '_mhn_feature_image_id',
        '_mhn_button_label',
        '_mhn_button_url',
        '_mhn_image_id',
        '_mhn_image_alt',
    ];
    foreach ($keys as $key) {
        $value = get_post_meta($issueId, $key, true);
        if ($value === '' || $value === false) {
            continue;
        }
        update_post_meta($copyId, $key, $value);
    }

    update_post_meta($copyId, '_mhn_status', 'draft');
    update_post_meta($copyId, '_mhn_wizard_step', '2');
    update_post_meta($copyId, '_mhn_sent_count', '0');
    update_post_meta($copyId, '_mhn_fail_count', '0');
    update_post_meta($copyId, '_mhn_archived', '0');

    return $copyId;
}

function duplicate_issue_url(int $issueId): string
{
    return wp_nonce_url(
        admin_url('admin-post.php?action=mhn_duplicate_issue&issue='.$issueId),
        'mhn_duplicate_'.$issueId
    );
}

function sent_archive_admin_url(int $snapshotId = 0): string
{
    $args = ['page' => 'mhn-archive'];
    if ($snapshotId > 0) {
        $args['snapshot'] = $snapshotId;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

function archive_action_url(int $snapshotId, string $action): string
{
    return wp_nonce_url(
        admin_url('admin-post.php?action='.$action.'&snapshot='.$snapshotId),
        $action.'_'.$snapshotId
    );
}

function render_sent_lock_notice(int $issueId): void
{
    if (! issue_is_locked($issueId)) {
        return;
    }

    echo '<div class="notice notice-info"><p>';
    if (issue_status($issueId) === 'sent') {
        echo esc_html__('This issue was sent. It is read-only. Duplicate it to make a new draft.', 'matthummel-newsletter');
        echo ' <a class="button" href="'.esc_url(duplicate_issue_url($issueId)).'">'.esc_html__('Duplicate as new draft', 'matthummel-newsletter').'</a>';
        $snap = latest_sent_snapshot($issueId);
        if ($snap !== null) {
            echo ' <a class="button" href="'.esc_url(sent_archive_admin_url((int) $snap['id'])).'">'.esc_html__('View saved copy', 'matthummel-newsletter').'</a>';
        }
    } else {
        echo esc_html__('This issue is sending. Editing is locked until it finishes.', 'matthummel-newsletter');
    }
    echo '</p></div>';
}

function archive_capability_error(): ?\WP_Error
{
    if (current_user_can('manage_options')) {
        return null;
    }

    return new \WP_Error('mhn_forbidden', __('Sorry, you are not allowed to do that.', 'matthummel-newsletter'));
}

function sent_archive_html(int $id): string|\WP_Error
{
    $denied = archive_capability_error();
    if ($denied instanceof \WP_Error) {
        return $denied;
    }

    $row = sent_snapshot($id);
    if ($row === null) {
        return new \WP_Error('mhn_missing', __('That saved copy is not here.', 'matthummel-newsletter'));
    }

    return $row['html'];
}

function sent_archive_eml(int $id): string|\WP_Error
{
    $denied = archive_capability_error();
    if ($denied instanceof \WP_Error) {
        return $denied;
    }

    $row = sent_snapshot($id);
    if ($row === null) {
        return new \WP_Error('mhn_missing', __('That saved copy is not here.', 'matthummel-newsletter'));
    }

    $boundary = 'mhn_'.wp_generate_password(16, false, false);
    $fromName = str_replace(['"', "\r", "\n"], '', $row['from_name']);
    $fromEmail = sanitize_email($row['from_email']);
    $subject = str_replace(["\r", "\n"], ' ', $row['subject']);
    $stamp = strtotime($row['finished_at']);
    $date = gmdate('D, d M Y H:i:s O', $stamp === false ? time() : $stamp);
    $lines = [
        'From: "'.$fromName.'" <'.$fromEmail.'>',
        'To: undisclosed-recipients:;',
        'Subject: '.$subject,
        'Date: '.$date,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
        '',
        '--'.$boundary,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        '',
        $row['body_text'],
        '--'.$boundary,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        '',
        $row['html'],
        '--'.$boundary.'--',
        '',
    ];

    return implode("\r\n", $lines);
}

function sent_archive_csv(): string|\WP_Error
{
    $denied = archive_capability_error();
    if ($denied instanceof \WP_Error) {
        return $denied;
    }

    global $wpdb;

    if (! archive_table_exists()) {
        return '';
    }

    $columns = [
        'id',
        'issue_id',
        'subject',
        'preheader',
        'from_name',
        'from_email',
        'template',
        'started_at',
        'finished_at',
        'sender_id',
        'recipients',
        'delivered',
        'failed',
        'list_label',
        'plugin_version',
    ];
    $rows = $wpdb->get_results(
        'SELECT '.implode(', ', $columns).' FROM '.archive_table().' ORDER BY id DESC',
        ARRAY_A
    );
    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        return new \WP_Error('mhn_export', __('Could not write the export.', 'matthummel-newsletter'));
    }

    fputcsv($handle, $columns);
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (! is_array($row)) {
            continue;
        }
        $line = [];
        foreach ($columns as $key) {
            $line[] = csv_cell((string) ($row[$key] ?? ''));
        }
        fputcsv($handle, $line);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return is_string($csv) ? $csv : '';
}

function delete_sent_snapshot(int $id): bool
{
    global $wpdb;

    if (! current_user_can('manage_options') || $id < 1 || ! archive_table_exists()) {
        return false;
    }

    return $wpdb->delete(archive_table(), ['id' => $id], ['%d']) === 1;
}

function handle_duplicate_issue(): void
{
    $issueId = isset($_GET['issue']) ? absint(wp_unslash($_GET['issue'])) : 0;
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_duplicate_'.$issueId);

    $copy = duplicate_issue($issueId);
    if ($copy < 1) {
        wp_die(esc_html__('Could not duplicate that issue.', 'matthummel-newsletter'));
    }

    wp_safe_redirect(wizard_url($copy, 2));
    exit;
}

function handle_archive_html(): void
{
    archive_download('html');
}

function handle_archive_eml(): void
{
    archive_download('eml');
}

function handle_archive_frame(): void
{
    archive_download('frame');
}

function handle_archive_csv(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_archive_csv');

    $csv = sent_archive_csv();
    if (is_wp_error($csv)) {
        wp_die(esc_html($csv->get_error_message()), 403);
    }

    archive_download_headers('text/csv', 'newsletter-archive-'.gmdate('Y-m-d').'.csv');
    echo $csv;
    exit;
}

function archive_download(string $kind): void
{
    $id = isset($_GET['snapshot']) ? absint(wp_unslash($_GET['snapshot'])) : 0;
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_archive_'.$kind.'_'.$id);

    if ($kind === 'eml') {
        $body = sent_archive_eml($id);
        $type = 'message/rfc822';
        $name = 'newsletter-'.$id.'.eml';
    } else {
        $body = sent_archive_html($id);
        $type = 'text/html; charset=utf-8';
        $name = 'newsletter-'.$id.'.html';
    }
    if (is_wp_error($body)) {
        $status = $body->get_error_code() === 'mhn_forbidden' ? 403 : 404;
        wp_die(esc_html($body->get_error_message()), $status);
    }

    if ($kind === 'frame') {
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow', true);
        header('Referrer-Policy: no-referrer');
        header('Content-Security-Policy: sandbox; default-src \'none\'; style-src \'unsafe-inline\'; img-src https: http: data:; font-src https: http: data:');
        echo $body;
        exit;
    }

    archive_download_headers($type, $name);
    echo $body;
    exit;
}

function archive_download_headers(string $type, string $filename): void
{
    nocache_headers();
    header('Content-Type: '.$type);
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
}

function handle_archive_delete(): void
{
    $id = isset($_POST['snapshot']) ? absint(wp_unslash($_POST['snapshot'])) : 0;
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_archive_delete_'.$id);

    $confirm = isset($_POST['mhn_confirm_delete']) ? sanitize_text_field(wp_unslash($_POST['mhn_confirm_delete'])) : '';
    if ($confirm !== '1') {
        wp_safe_redirect(add_query_arg([
            'page' => 'mhn-archive',
            'snapshot' => $id,
            'mhn_confirm' => 'delete',
        ], admin_url('admin.php')));
        exit;
    }

    delete_sent_snapshot($id);
    wp_safe_redirect(admin_url('admin.php?page=mhn-archive&deleted=1'));
    exit;
}

function handle_archive_state(): void
{
    $issueId = isset($_POST['issue']) ? absint(wp_unslash($_POST['issue'])) : 0;
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
    check_admin_referer('mhn_archive_state_'.$issueId);

    $archived = isset($_POST['mhn_archived']) && (string) wp_unslash($_POST['mhn_archived']) === '1';
    set_issue_archived($issueId, $archived);
    $back = isset($_POST['redirect']) ? esc_url_raw(wp_unslash($_POST['redirect'])) : '';
    if ($back === '' || ! str_contains($back, 'page=mhn-archive')) {
        $back = sent_archive_admin_url();
    }
    wp_safe_redirect($back);
    exit;
}

function page_sent_archive(): void
{
    guard_admin();

    $snapshotId = isset($_GET['snapshot']) ? absint(wp_unslash($_GET['snapshot'])) : 0;
    $confirm = isset($_GET['mhn_confirm']) ? sanitize_key(wp_unslash($_GET['mhn_confirm'])) : '';
    if ($snapshotId > 0 && $confirm === 'delete') {
        render_archive_delete($snapshotId);

        return;
    }
    if ($snapshotId > 0) {
        render_archive_view($snapshotId);

        return;
    }

    render_archive_list();
}

function render_archive_delete(int $snapshotId): void
{
    $row = sent_snapshot($snapshotId);
    echo '<div class="wrap mhn-admin mhn-narrow">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Delete saved copy', 'matthummel-newsletter').'</h1>';
    echo '</div></header>';
    if ($row === null) {
        echo '<p class="mhn-settings-note">'.esc_html__('That saved copy is not here.', 'matthummel-newsletter').'</p></div>';

        return;
    }

    echo '<section class="mhn-card">';
    echo '<p class="mhn-card-lead">'.esc_html(sprintf(
        /* translators: 1: subject, 2: finished timestamp */
        __('Delete the saved copy of “%1$s” from %2$s. The subscriber list stays. This cannot be undone.', 'matthummel-newsletter'),
        $row['subject'],
        $row['finished_at']
    )).'</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="mhn_archive_delete">';
    echo '<input type="hidden" name="snapshot" value="'.esc_attr((string) $snapshotId).'">';
    wp_nonce_field('mhn_archive_delete_'.$snapshotId);
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_confirm_delete" value="1"> '.esc_html__('I understand this removes the saved copy.', 'matthummel-newsletter').'</label></p>';
    submit_button(__('Delete saved copy', 'matthummel-newsletter'), 'delete');
    echo '</form>';
    echo '<p><a href="'.esc_url(sent_archive_admin_url($snapshotId)).'">'.esc_html__('Back', 'matthummel-newsletter').'</a></p>';
    echo '</section></div>';
}

function render_archive_view(int $snapshotId): void
{
    $row = sent_snapshot($snapshotId);
    echo '<div class="wrap mhn-admin">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<p class="mhn-wizard-back"><a href="'.esc_url(sent_archive_admin_url()).'">'.esc_html__('Back to Sent archive', 'matthummel-newsletter').'</a></p>';
    echo '<h1>'.esc_html__('Saved copy', 'matthummel-newsletter').'</h1>';
    echo '</div></header>';
    if ($row === null) {
        echo '<p class="mhn-settings-note">'.esc_html__('That saved copy is not here.', 'matthummel-newsletter').'</p></div>';

        return;
    }

    $issueId = (int) $row['issue_id'];
    echo '<section class="mhn-card"><dl class="mhn-archive-meta">';
    $fields = [
        __('Subject', 'matthummel-newsletter') => $row['subject'],
        __('Preheader', 'matthummel-newsletter') => $row['preheader'],
        __('From', 'matthummel-newsletter') => trim($row['from_name'].' <'.$row['from_email'].'>'),
        __('Template', 'matthummel-newsletter') => template_label($row['template']),
        __('Started', 'matthummel-newsletter') => $row['started_at'],
        __('Finished', 'matthummel-newsletter') => $row['finished_at'],
        __('Sender', 'matthummel-newsletter') => sender_label((int) $row['sender_id']),
        __('Recipients', 'matthummel-newsletter') => $row['recipients'],
        __('Delivered', 'matthummel-newsletter') => $row['delivered'],
        __('Failed', 'matthummel-newsletter') => $row['failed'],
        __('List', 'matthummel-newsletter') => $row['list_label'],
        __('Plugin', 'matthummel-newsletter') => $row['plugin_version'],
    ];
    foreach ($fields as $label => $value) {
        echo '<dt>'.esc_html($label).'</dt><dd>'.esc_html($value).'</dd>';
    }
    echo '</dl>';
    echo '<p class="mhn-archive-actions">';
    echo '<a class="button" href="'.esc_url(archive_action_url($snapshotId, 'mhn_archive_html')).'">'.esc_html__('Download HTML', 'matthummel-newsletter').'</a> ';
    echo '<a class="button" href="'.esc_url(archive_action_url($snapshotId, 'mhn_archive_eml')).'">'.esc_html__('Download EML', 'matthummel-newsletter').'</a> ';
    if ($issueId > 0 && issue_status($issueId) === 'sent') {
        echo '<a class="button" href="'.esc_url(duplicate_issue_url($issueId)).'">'.esc_html__('Duplicate as new draft', 'matthummel-newsletter').'</a> ';
    }
    echo '<a class="button" href="'.esc_url(sent_archive_admin_url($snapshotId).'&mhn_confirm=delete').'">'.esc_html__('Delete', 'matthummel-newsletter').'</a>';
    echo '</p></section>';
    $frame = archive_action_url($snapshotId, 'mhn_archive_frame');
    echo '<iframe class="mhn-archive-frame" title="'.esc_attr__('Saved newsletter', 'matthummel-newsletter').'" sandbox="" src="'.esc_url($frame).'"></iframe>';
    echo '</div>';
}

function render_archive_list(): void
{
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $template = isset($_GET['template']) ? sanitize_key(wp_unslash($_GET['template'])) : '';
    $from = isset($_GET['from']) ? archive_date(sanitize_text_field(wp_unslash($_GET['from']))) : '';
    $to = isset($_GET['to']) ? archive_date(sanitize_text_field(wp_unslash($_GET['to']))) : '';
    $show = isset($_GET['show']) ? sanitize_key(wp_unslash($_GET['show'])) : 'active';
    if (! in_array($show, ['active', 'archived', 'all'], true)) {
        $show = 'active';
    }
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'finished_at';
    $order = isset($_GET['order']) ? strtoupper(sanitize_key(wp_unslash($_GET['order']))) : 'DESC';
    if (! in_array($orderby, ['finished_at', 'subject', 'template'], true)) {
        $orderby = 'finished_at';
    }
    if ($order !== 'ASC') {
        $order = 'DESC';
    }
    $page = max(1, isset($_GET['paged']) ? absint(wp_unslash($_GET['paged'])) : 1);
    $result = sent_archive_query([
        'show' => $show,
        'search' => $search,
        'template' => $template,
        'from' => $from,
        'to' => $to,
        'orderby' => $orderby,
        'order' => $order,
        'page' => $page,
    ]);

    echo '<div class="wrap mhn-admin mhn-archive">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Sent archive', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Private record of each finished send. Merge tags stay as placeholders. Subscriber addresses are not stored here.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Saved copy deleted.', 'matthummel-newsletter').'</p></div>';
    }

    echo '<section class="mhn-card"><form method="get" class="mhn-archive-filters">';
    echo '<input type="hidden" name="page" value="mhn-archive">';
    echo '<input type="hidden" name="orderby" value="'.esc_attr($orderby).'">';
    echo '<input type="hidden" name="order" value="'.esc_attr(strtolower($order)).'">';
    echo '<label class="screen-reader-text" for="mhn-archive-s">'.esc_html__('Search subject or template', 'matthummel-newsletter').'</label>';
    echo '<input type="search" id="mhn-archive-s" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Subject or template', 'matthummel-newsletter').'"> ';
    echo '<label class="screen-reader-text" for="mhn-archive-template">'.esc_html__('Template', 'matthummel-newsletter').'</label>';
    echo '<select id="mhn-archive-template" name="template">';
    echo '<option value="">'.esc_html__('All templates', 'matthummel-newsletter').'</option>';
    foreach (email_templates() as $slug => $item) {
        echo '<option value="'.esc_attr($slug).'" '.selected($template, $slug, false).'>'.esc_html($item['label']).'</option>';
    }
    echo '</select> ';
    echo '<label for="mhn-archive-from">'.esc_html__('From', 'matthummel-newsletter').'</label> ';
    echo '<input type="date" id="mhn-archive-from" name="from" value="'.esc_attr($from).'"> ';
    echo '<label for="mhn-archive-to">'.esc_html__('To', 'matthummel-newsletter').'</label> ';
    echo '<input type="date" id="mhn-archive-to" name="to" value="'.esc_attr($to).'"> ';
    echo '<label class="screen-reader-text" for="mhn-archive-show">'.esc_html__('Archive state', 'matthummel-newsletter').'</label>';
    echo '<select id="mhn-archive-show" name="show">';
    echo '<option value="active" '.selected($show, 'active', false).'>'.esc_html__('On the main list', 'matthummel-newsletter').'</option>';
    echo '<option value="archived" '.selected($show, 'archived', false).'>'.esc_html__('Archived', 'matthummel-newsletter').'</option>';
    echo '<option value="all" '.selected($show, 'all', false).'>'.esc_html__('All', 'matthummel-newsletter').'</option>';
    echo '</select> ';
    submit_button(__('Filter', 'matthummel-newsletter'), 'secondary', '', false);
    echo '</form>';

    $csv = wp_nonce_url(admin_url('admin-post.php?action=mhn_archive_csv'), 'mhn_archive_csv');
    echo '<p><a class="button" href="'.esc_url($csv).'">'.esc_html__('Download CSV', 'matthummel-newsletter').'</a></p></section>';
    echo '<section class="mhn-card mhn-table-card">';

    $query = [
        's' => $search,
        'template' => $template,
        'from' => $from,
        'to' => $to,
        'show' => $show,
    ];
    echo '<table class="widefat striped mhn-issues-admin"><thead><tr>';
    echo '<th>'.archive_sort_link('finished_at', __('Date', 'matthummel-newsletter'), $orderby, $order, $query).'</th>';
    echo '<th>'.archive_sort_link('subject', __('Subject', 'matthummel-newsletter'), $orderby, $order, $query).'</th>';
    echo '<th>'.archive_sort_link('template', __('Template', 'matthummel-newsletter'), $orderby, $order, $query).'</th>';
    echo '<th>'.esc_html__('List', 'matthummel-newsletter').'</th>';
    echo '<th>'.esc_html__('Recipients', 'matthummel-newsletter').'</th>';
    echo '<th>'.esc_html__('Delivered', 'matthummel-newsletter').'</th>';
    echo '<th>'.esc_html__('Failed', 'matthummel-newsletter').'</th>';
    echo '<th>'.esc_html__('Actions', 'matthummel-newsletter').'</th>';
    echo '</tr></thead><tbody>';

    if ($result['rows'] === []) {
        echo '<tr><td colspan="8">'.esc_html__('No saved copies yet.', 'matthummel-newsletter').'</td></tr>';
    }

    foreach ($result['rows'] as $row) {
        $id = (int) $row['id'];
        $issueId = (int) $row['issue_id'];
        $hidden = issue_is_archived($issueId);
        echo '<tr>';
        echo '<td>'.esc_html($row['finished_at']).'</td>';
        echo '<td><a href="'.esc_url(sent_archive_admin_url($id)).'">'.esc_html($row['subject']).'</a></td>';
        echo '<td>'.esc_html(template_label($row['template'])).'</td>';
        echo '<td>'.esc_html($row['list_label']).'</td>';
        echo '<td>'.esc_html($row['recipients']).'</td>';
        echo '<td>'.esc_html($row['delivered']).'</td>';
        echo '<td>'.esc_html($row['failed']).'</td>';
        echo '<td class="mhn-archive-actions">';
        echo '<a href="'.esc_url(sent_archive_admin_url($id)).'">'.esc_html__('View', 'matthummel-newsletter').'</a> · ';
        echo '<a href="'.esc_url(archive_action_url($id, 'mhn_archive_html')).'">'.esc_html__('HTML', 'matthummel-newsletter').'</a> · ';
        echo '<a href="'.esc_url(archive_action_url($id, 'mhn_archive_eml')).'">'.esc_html__('EML', 'matthummel-newsletter').'</a> · ';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="mhn_archive_state">';
        echo '<input type="hidden" name="issue" value="'.esc_attr((string) $issueId).'">';
        echo '<input type="hidden" name="mhn_archived" value="'.esc_attr($hidden ? '0' : '1').'">';
        echo '<input type="hidden" name="redirect" value="'.esc_attr(archive_list_url($query, $orderby, $order, $page)).'">';
        wp_nonce_field('mhn_archive_state_'.$issueId);
        echo '<button type="submit" class="button-link">'.esc_html($hidden ? __('Restore', 'matthummel-newsletter') : __('Archive', 'matthummel-newsletter')).'</button>';
        echo '</form> · ';
        echo '<a href="'.esc_url(sent_archive_admin_url($id).'&mhn_confirm=delete').'">'.esc_html__('Delete', 'matthummel-newsletter').'</a>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';

    $pages = (int) ceil($result['total'] / max(1, $result['per_page']));
    if ($pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        if ($page > 1) {
            echo '<a class="button" href="'.esc_url(archive_list_url($query, $orderby, $order, $page - 1)).'">'.esc_html__('Previous', 'matthummel-newsletter').'</a> ';
        }
        echo '<span class="paging-input">'.esc_html(sprintf(
            /* translators: 1: current page, 2: total pages */
            __('Page %1$d of %2$d', 'matthummel-newsletter'),
            $page,
            $pages
        )).'</span> ';
        if ($page < $pages) {
            echo '<a class="button" href="'.esc_url(archive_list_url($query, $orderby, $order, $page + 1)).'">'.esc_html__('Next', 'matthummel-newsletter').'</a>';
        }
        echo '</div></div>';
    }

    echo '</section></div>';
}

/**
 * @param  array{s: string, template: string, from: string, to: string, show: string}  $query
 */
function archive_sort_link(string $column, string $label, string $orderby, string $order, array $query): string
{
    $next = ($orderby === $column && $order === 'ASC') ? 'desc' : 'asc';
    $url = archive_list_url($query, $column, strtoupper($next), 1);

    return '<a href="'.esc_url($url).'">'.esc_html($label).'</a>';
}

/**
 * @param  array{s: string, template: string, from: string, to: string, show: string}  $query
 */
function archive_list_url(array $query, string $orderby, string $order, int $page): string
{
    return add_query_arg([
        'page' => 'mhn-archive',
        's' => $query['s'],
        'template' => $query['template'],
        'from' => $query['from'],
        'to' => $query['to'],
        'show' => $query['show'],
        'orderby' => $orderby,
        'order' => strtolower($order),
        'paged' => max(1, $page),
    ], admin_url('admin.php'));
}
