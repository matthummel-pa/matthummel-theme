<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

function admin_menu(): void
{
    add_menu_page(
        __('Get updates', 'matthummel-newsletter'),
        __('Get updates', 'matthummel-newsletter'),
        'manage_options',
        'mhn-newsletter',
        __NAMESPACE__.'\\page_dashboard',
        'dashicons-email-alt',
        58
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Get updates', 'matthummel-newsletter'),
        __('Dashboard', 'matthummel-newsletter'),
        'manage_options',
        'mhn-newsletter',
        __NAMESPACE__.'\\page_dashboard'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Create newsletter', 'matthummel-newsletter'),
        __('Create newsletter', 'matthummel-newsletter'),
        'manage_options',
        'mhn-wizard',
        __NAMESPACE__.'\\page_wizard'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Subscribers', 'matthummel-newsletter'),
        __('Subscribers', 'matthummel-newsletter'),
        'manage_options',
        'mhn-subscribers',
        __NAMESPACE__.'\\page_subscribers'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Import', 'matthummel-newsletter'),
        __('Import', 'matthummel-newsletter'),
        'manage_options',
        'mhn-import',
        __NAMESPACE__.'\\page_import'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Settings', 'matthummel-newsletter'),
        __('Settings', 'matthummel-newsletter'),
        'manage_options',
        'mhn-settings',
        __NAMESPACE__.'\\page_settings'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Log', 'matthummel-newsletter'),
        __('Log', 'matthummel-newsletter'),
        'manage_options',
        'mhn-log',
        __NAMESPACE__.'\\page_log'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Sent archive', 'matthummel-newsletter'),
        __('Sent archive', 'matthummel-newsletter'),
        'manage_options',
        'mhn-archive',
        __NAMESPACE__.'\\page_sent_archive'
    );
    add_submenu_page(
        'mhn-newsletter',
        __('Delivery', 'matthummel-newsletter'),
        __('Delivery', 'matthummel-newsletter'),
        'manage_options',
        'mhn-deliver',
        __NAMESPACE__.'\\page_deliver'
    );
    add_action('admin_menu', static function (): void {
        remove_submenu_page('mhn-newsletter', 'mhn-deliver');
    }, 99);
}

function admin_assets(string $hook): void
{
    if (! str_contains($hook, 'mhn-') && ! str_contains($hook, 'newsletter_issue')) {
        return;
    }

    wp_enqueue_style(
        'mhn-admin',
        plugins_url('assets/admin.css', MHN_FILE),
        [],
        MHN_VERSION
    );

    $onWizard = str_contains($hook, 'mhn-wizard');
    $onDashboard = str_contains($hook, 'mhn-newsletter');
    if ($onWizard || $onDashboard) {
        wp_enqueue_script(
            'mhn-emulator',
            plugins_url('assets/emulator.js', MHN_FILE),
            [],
            MHN_VERSION,
            true
        );
    }
    if ($onWizard) {
        $config = settings();
        wp_localize_script('mhn-emulator', 'mhnEmulator', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action' => 'mhn_emulator_preview',
            'nonce' => wp_create_nonce('mhn_emulator'),
            'intro' => $config['intro'],
            'fromName' => $config['from_name'],
            'fromEmail' => $config['from_email'],
            'welcomeSubject' => $config['welcome_subject'],
        ]);
    }

    if (! $onWizard) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'mhn-wizard',
        plugins_url('assets/wizard.js', MHN_FILE),
        [],
        MHN_VERSION,
        true
    );
    wp_localize_script('mhn-wizard', 'mhnWizard', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'saved' => __('Draft saved.', 'matthummel-newsletter'),
    ]);
}

/**
 * @return array{
 *     subscribed: int,
 *     pending: int,
 *     unsubscribed: int,
 *     legacy: int,
 *     recent: int,
 *     prior: int,
 *     issues: list<array{id: int, title: string, status: string, sent: int, failed: int, when: string, sent_at: string, scheduled: string}>
 * }
 */
function dashboard_data(): array
{
    $now = current_time('timestamp');
    $recentStart = wp_date('Y-m-d H:i:s', $now - (30 * DAY_IN_SECONDS));
    $priorStart = wp_date('Y-m-d H:i:s', $now - (60 * DAY_IN_SECONDS));
    $nowMysql = wp_date('Y-m-d H:i:s', $now);

    return [
        'subscribed' => count_status('subscribed'),
        'pending' => count_status('pending'),
        'unsubscribed' => count_status('unsubscribed'),
        'legacy' => count_opt_in('legacy_single'),
        'recent' => count_confirmed_between($recentStart, $nowMysql),
        'prior' => count_confirmed_between($priorStart, $recentStart),
        'issues' => recent_issue_rows(),
    ];
}

/**
 * @return list<array{id: int, title: string, status: string, sent: int, failed: int, when: string, sent_at: string, scheduled: string}>
 */
function recent_issue_rows(): array
{
    $posts = get_posts([
        'post_type' => 'newsletter_issue',
        'post_status' => 'any',
        'posts_per_page' => 20,
        'orderby' => 'modified',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);

    $rows = [];
    foreach (is_array($posts) ? $posts : [] as $post) {
        if (! $post instanceof \WP_Post || issue_is_archived($post->ID)) {
            continue;
        }
        if (count($rows) >= 8) {
            break;
        }
        $rows[] = [
            'id' => $post->ID,
            'title' => $post->post_title !== '' ? $post->post_title : __('(no title)', 'matthummel-newsletter'),
            'status' => issue_status($post->ID),
            'sent' => (int) get_post_meta($post->ID, '_mhn_sent_count', true),
            'failed' => (int) get_post_meta($post->ID, '_mhn_fail_count', true),
            'when' => $post->post_modified,
            'sent_at' => (string) get_post_meta($post->ID, '_mhn_sent_at', true),
            'scheduled' => (string) get_post_meta($post->ID, '_mhn_scheduled_local', true),
        ];
    }

    return $rows;
}

/**
 * @param  list<array{id: int, title: string, status: string, sent: int, failed: int, when: string, sent_at?: string, scheduled?: string}>  $issues
 */
function issues_table(array $issues, bool $admin): string
{
    $html = '<table class="'.($admin ? 'widefat striped' : 'mhn-issues').'"><thead><tr>';
    $html .= '<th>'.esc_html__('Issue', 'matthummel-newsletter').'</th>';
    $html .= '<th>'.esc_html__('Status', 'matthummel-newsletter').'</th>';
    $html .= '<th>'.esc_html__('Sent', 'matthummel-newsletter').'</th>';
    $html .= '<th>'.esc_html__('Actions', 'matthummel-newsletter').'</th>';
    $html .= '</tr></thead><tbody>';

    if ($issues === []) {
        $html .= '<tr><td colspan="4">'.esc_html__('No issues yet.', 'matthummel-newsletter').'</td></tr>';
    }

    foreach ($issues as $issue) {
        $edit = get_edit_post_link($issue['id'], 'raw');
        $test = wp_nonce_url(admin_url('admin-post.php?action=mhn_send_test&issue='.$issue['id']), 'mhn_send_test_'.$issue['id']);
        $html .= '<tr>';
        $html .= '<td>'.esc_html($issue['title']).'</td>';
        $html .= '<td>'.esc_html(status_label($issue['status'])).'</td>';
        $html .= '<td>'.esc_html((string) $issue['sent']).'</td>';
        $continue = in_array($issue['status'], ['sent', 'sending'], true)
            ? __('View', 'matthummel-newsletter')
            : __('Continue', 'matthummel-newsletter');
        $html .= '<td>';
        $html .= '<a href="'.esc_url(wizard_url($issue['id'])).'">'.esc_html($continue).'</a>';
        if ($issue['status'] === 'sent') {
            $html .= ' · <a href="'.esc_url(duplicate_issue_url($issue['id'])).'">'.esc_html__('Duplicate as new draft', 'matthummel-newsletter').'</a>';
            $saved = latest_sent_snapshot($issue['id']);
            if ($saved !== null) {
                $html .= ' · <a href="'.esc_url(sent_archive_admin_url((int) $saved['id'])).'">'.esc_html__('Saved copy', 'matthummel-newsletter').'</a>';
            }
        } else {
            if (is_string($edit) && $edit !== '') {
                $html .= ' · <a href="'.esc_url($edit).'">'.esc_html__('Editor', 'matthummel-newsletter').'</a>';
            }
            $html .= ' · <a href="'.esc_url($test).'">'.esc_html__('Send test', 'matthummel-newsletter').'</a>';
            $html .= ' · <a href="'.esc_url(wizard_url($issue['id'], 5)).'">'.esc_html__('Send', 'matthummel-newsletter').'</a>';
        }
        $html .= '</td></tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

function guard_admin(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to do that.', 'matthummel-newsletter'), 403);
    }
}

function page_dashboard(): void
{
    guard_admin();
    $data = dashboard_data();
    $create = wizard_url();

    echo '<div class="wrap mhn-admin">';
    echo '<header class="mhn-dash-head">';
    echo '<div>';
    echo '<h1>'.esc_html__('Get updates', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Addresses stay in this WordPress database. Mail goes out through wp_mail.', 'matthummel-newsletter').'</p>';
    echo '</div>';
    echo '<a class="button button-primary" href="'.esc_url($create).'">'.esc_html__('Create newsletter', 'matthummel-newsletter').'</a>';
    echo '</header>';

    echo '<div class="mhn-dash-grid">';
    echo '<section class="mhn-card mhn-audience" aria-labelledby="mhn-audience-heading">';
    echo '<h2 id="mhn-audience-heading">'.esc_html__('Audience', 'matthummel-newsletter').'</h2>';
    echo '<dl class="mhn-admin-stats">';
    foreach ([
        'subscribed' => __('Subscribed', 'matthummel-newsletter'),
        'pending' => __('Pending', 'matthummel-newsletter'),
        'unsubscribed' => __('Unsubscribed', 'matthummel-newsletter'),
    ] as $key => $label) {
        echo '<div class="mhn-stat">';
        echo '<dt>'.esc_html($label).'</dt>';
        echo '<dd>'.esc_html((string) $data[$key]).'</dd>';
        echo '</div>';
    }
    echo '</dl>';
    echo '<p class="description">'.esc_html(sprintf(
        /* translators: 1: new subscribers in 30 days, 2: previous 30 days */
        __('%1$d new in the last 30 days. %2$d in the 30 days before that.', 'matthummel-newsletter'),
        $data['recent'],
        $data['prior']
    )).'</p>';
    if ($data['legacy'] > 0) {
        echo '<div class="mhn-legacy"><p>'.esc_html(sprintf(
            /* translators: %d: legacy single opt-in addresses */
            __('%d addresses came from the old footer list (single opt-in). They stay subscribed and are not asked to confirm again. Everyone new confirms by email first.', 'matthummel-newsletter'),
            $data['legacy']
        )).'</p></div>';
    }
    echo '</section>';
    render_dashboard_preview();
    echo '</div>';

    echo '<nav class="mhn-dash-links" aria-label="'.esc_attr__('Newsletter tools', 'matthummel-newsletter').'">';
    foreach ([
        admin_url('admin.php?page=mhn-subscribers') => __('Subscribers', 'matthummel-newsletter'),
        admin_url('admin.php?page=mhn-import') => __('Import', 'matthummel-newsletter'),
        admin_url('admin.php?page=mhn-archive') => __('Sent archive', 'matthummel-newsletter'),
        admin_url('admin.php?page=mhn-settings') => __('Settings', 'matthummel-newsletter'),
        admin_url('post-new.php?post_type=newsletter_issue') => __('Block editor', 'matthummel-newsletter'),
        page_url('get-updates') => __('View page', 'matthummel-newsletter'),
    ] as $url => $label) {
        echo '<a href="'.esc_url($url).'">'.esc_html($label).'</a>';
    }
    echo '</nav>';

    echo '<section class="mhn-card mhn-recent mhn-table-card" aria-labelledby="mhn-recent-heading">';
    echo '<h2 id="mhn-recent-heading">'.esc_html__('Recent newsletters', 'matthummel-newsletter').'</h2>';
    if ($data['issues'] === []) {
        echo '<div class="mhn-empty">';
        echo '<h3>'.esc_html__('No newsletters yet', 'matthummel-newsletter').'</h3>';
        echo '<p>'.esc_html__('Create one when you are ready. It stays a draft until you send it.', 'matthummel-newsletter').'</p>';
        echo '<a class="button button-primary" href="'.esc_url($create).'">'.esc_html__('Create newsletter', 'matthummel-newsletter').'</a>';
        echo '</div>';
    } else {
        echo '<p class="description">'.esc_html__('Delivered and failed counts show after a send starts.', 'matthummel-newsletter').'</p>';
        echo admin_issues_table($data['issues']);
    }
    echo '</section>';
    echo '</div>';
}

/**
 * @param  array{id: int, title: string, status: string, sent: int, failed: int, when: string, sent_at: string, scheduled: string}  $issue
 */
function render_dashboard_preview(): void
{
    render_email_emulator(emulator_view(0, dashboard_layout_id()), false, true);
}

function issue_has_delivery(array $issue): bool
{
    return in_array($issue['status'], ['sent', 'sending', 'failed'], true)
        || $issue['sent'] > 0
        || $issue['failed'] > 0;
}

/**
 * @param  array{status: string, sent_at: string, scheduled: string}  $issue
 */
function issue_time_label(array $issue): string
{
    if (in_array($issue['status'], ['sent', 'failed'], true) && $issue['sent_at'] !== '') {
        return admin_datetime($issue['sent_at']);
    }
    if ($issue['status'] === 'sending') {
        return $issue['sent_at'] !== ''
            ? admin_datetime($issue['sent_at'])
            : __('Still sending', 'matthummel-newsletter');
    }
    if ($issue['status'] === 'scheduled' && $issue['scheduled'] !== '') {
        return sprintf(
            /* translators: %s: scheduled local date and time */
            __('Scheduled for %s', 'matthummel-newsletter'),
            admin_datetime($issue['scheduled'])
        );
    }

    return __('Not sent', 'matthummel-newsletter');
}

function admin_datetime(string $mysql): string
{
    $mysql = trim($mysql);
    if ($mysql === '' || str_starts_with($mysql, '0000-00-00')) {
        return '';
    }

    $format = trim((string) get_option('date_format').' '.(string) get_option('time_format'));
    $formatted = mysql2date($format !== '' ? $format : 'Y-m-d H:i', $mysql);

    return is_string($formatted) && $formatted !== '' ? $formatted : $mysql;
}

function subscriber_status_label(string $status): string
{
    return match ($status) {
        'subscribed' => __('Subscribed', 'matthummel-newsletter'),
        'pending' => __('Pending', 'matthummel-newsletter'),
        'unsubscribed' => __('Unsubscribed', 'matthummel-newsletter'),
        default => $status,
    };
}

function admin_status_badge(string $label, string $slug): string
{
    $icon = match ($slug) {
        'scheduled' => 'dashicons-clock',
        'sending' => 'dashicons-update',
        'sent', 'subscribed' => 'dashicons-yes',
        'failed' => 'dashicons-warning',
        'pending' => 'dashicons-email-alt',
        'unsubscribed' => 'dashicons-dismiss',
        default => 'dashicons-edit',
    };

    return '<span class="mhn-badge"><span class="dashicons '.esc_attr($icon).'" aria-hidden="true"></span> '.esc_html($label).'</span>';
}

function admin_count_cell(bool $show, int $count): string
{
    if (! $show) {
        return '<span class="mhn-muted"><span aria-hidden="true">—</span><span class="screen-reader-text">'.esc_html__('Not sent yet', 'matthummel-newsletter').'</span></span>';
    }

    return esc_html((string) $count);
}

/**
 * @param  list<array{id: int, title: string, status: string, sent: int, failed: int, when: string, sent_at: string, scheduled: string}>  $issues
 */
function admin_issues_table(array $issues): string
{
    $html = '<table class="widefat striped mhn-issues-admin"><thead><tr>';
    foreach ([
        __('Issue', 'matthummel-newsletter'),
        __('Status', 'matthummel-newsletter'),
        __('Sent time', 'matthummel-newsletter'),
        __('Delivered', 'matthummel-newsletter'),
        __('Failed', 'matthummel-newsletter'),
        __('Actions', 'matthummel-newsletter'),
    ] as $heading) {
        $html .= '<th scope="col">'.esc_html($heading).'</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($issues as $issue) {
        $showCounts = issue_has_delivery($issue);
        $html .= '<tr>';
        $html .= '<th scope="row"><a href="'.esc_url(wizard_url($issue['id'])).'">'.esc_html($issue['title']).'</a></th>';
        $html .= '<td>'.admin_status_badge(status_label($issue['status']), $issue['status']).'</td>';
        $html .= '<td>'.esc_html(issue_time_label($issue)).'</td>';
        $html .= '<td>'.admin_count_cell($showCounts, $issue['sent']).'</td>';
        $html .= '<td>'.admin_count_cell($showCounts, $issue['failed']).'</td>';
        $html .= '<td class="mhn-row-actions">'.admin_issue_actions($issue).'</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

/**
 * @param  array{id: int, status: string}  $issue
 */
function admin_issue_actions(array $issue): string
{
    $edit = get_edit_post_link($issue['id'], 'raw');
    $test = wp_nonce_url(admin_url('admin-post.php?action=mhn_send_test&issue='.$issue['id']), 'mhn_send_test_'.$issue['id']);
    $continue = in_array($issue['status'], ['sent', 'sending'], true)
        ? __('View', 'matthummel-newsletter')
        : __('Continue', 'matthummel-newsletter');
    $html = '<a href="'.esc_url(wizard_url($issue['id'])).'">'.esc_html($continue).'</a>';

    if ($issue['status'] === 'sent') {
        $html .= '<a href="'.esc_url(duplicate_issue_url($issue['id'])).'">'.esc_html__('Duplicate as new draft', 'matthummel-newsletter').'</a>';
        $saved = latest_sent_snapshot($issue['id']);
        if ($saved !== null) {
            $html .= '<a href="'.esc_url(sent_archive_admin_url((int) $saved['id'])).'">'.esc_html__('Saved copy', 'matthummel-newsletter').'</a>';
        }

        return $html;
    }

    if (is_string($edit) && $edit !== '') {
        $html .= '<a href="'.esc_url($edit).'">'.esc_html__('Editor', 'matthummel-newsletter').'</a>';
    }
    $html .= '<a href="'.esc_url($test).'">'.esc_html__('Send test', 'matthummel-newsletter').'</a>';
    $html .= '<a href="'.esc_url(wizard_url($issue['id'], 5)).'">'.esc_html__('Send', 'matthummel-newsletter').'</a>';

    return $html;
}

function page_subscribers(): void
{
    guard_admin();
    global $wpdb;

    $table = subscribers_table();
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    $where = '';
    $params = [];
    if ($search !== '') {
        $like = '%'.$wpdb->esc_like($search).'%';
        $where = 'WHERE email LIKE %s OR first_name LIKE %s OR last_name LIKE %s';
        $params = [$like, $like, $like];
    }

    $countSql = "SELECT COUNT(*) FROM {$table} {$where}";
    $total = (int) ($params === [] ? $wpdb->get_var($countSql) : $wpdb->get_var($wpdb->prepare($countSql, ...$params)));
    $listSql = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
    $rows = $wpdb->get_results($wpdb->prepare($listSql, ...array_merge($params, [$perPage, $offset])), ARRAY_A);
    $pages = max(1, (int) ceil($total / $perPage));
    $export = wp_nonce_url(admin_url('admin-post.php?action=mhn_export'), 'mhn_export');

    echo '<div class="wrap mhn-admin mhn-subscribers">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Subscribers', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Search, rename, or export the addresses on this site.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Removed.', 'matthummel-newsletter').'</p></div>';
    }
    if (isset($_GET['unsub'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Unsubscribed.', 'matthummel-newsletter').'</p></div>';
    }
    if (isset($_GET['named'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Name saved.', 'matthummel-newsletter').'</p></div>';
    }
    echo '<div class="mhn-sub-toolbar">';
    echo '<form class="mhn-sub-search" method="get">';
    echo '<input type="hidden" name="page" value="mhn-subscribers">';
    echo '<label class="screen-reader-text" for="mhn-search">'.esc_html__('Search subscribers', 'matthummel-newsletter').'</label>';
    echo '<input type="search" id="mhn-search" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Search by name or email', 'matthummel-newsletter').'">';
    echo '<button class="button" type="submit">'.esc_html__('Search', 'matthummel-newsletter').'</button>';
    echo '</form>';
    echo '<p class="mhn-sub-meta"><a class="button" href="'.esc_url($export).'">'.esc_html__('Export CSV', 'matthummel-newsletter').'</a> ';
    echo '<span class="description">'.esc_html(sprintf(
        /* translators: %d: subscriber count */
        __('%d addresses.', 'matthummel-newsletter'),
        $total
    )).'</span></p>';
    echo '</div>';
    echo '<section class="mhn-card mhn-table-card">';
    echo '<table class="widefat striped mhn-sub-table"><thead><tr>';
    foreach ([__('Email', 'matthummel-newsletter'), __('First name', 'matthummel-newsletter'), __('Last name', 'matthummel-newsletter'), __('Status', 'matthummel-newsletter'), __('Opt-in', 'matthummel-newsletter'), __('Signed up', 'matthummel-newsletter'), __('Actions', 'matthummel-newsletter')] as $heading) {
        echo '<th>'.esc_html($heading).'</th>';
    }
    echo '</tr></thead><tbody>';
    if (! is_array($rows) || $rows === []) {
        echo '<tr><td colspan="7">'.esc_html__('No subscribers yet.', 'matthummel-newsletter').'</td></tr>';
    } else {
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            echo '<tr>';
            $formId = 'mhn-name-'.$id;
            echo '<td>'.esc_html((string) ($row['email'] ?? '')).'</td>';
            echo '<td><form id="'.esc_attr($formId).'" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            wp_nonce_field('mhn_subscriber_names');
            echo '<input type="hidden" name="action" value="mhn_subscriber_names"><input type="hidden" name="id" value="'.esc_attr((string) $id).'"></form>';
            echo '<label class="screen-reader-text" for="mhn-first-'.esc_attr((string) $id).'">'.esc_html__('First name', 'matthummel-newsletter').'</label>';
            echo '<input form="'.esc_attr($formId).'" id="mhn-first-'.esc_attr((string) $id).'" name="mhn_fname" type="text" autocomplete="given-name" maxlength="80" value="'.esc_attr((string) ($row['first_name'] ?? '')).'"></td>';
            echo '<td><label class="screen-reader-text" for="mhn-last-'.esc_attr((string) $id).'">'.esc_html__('Last name', 'matthummel-newsletter').'</label>';
            echo '<input form="'.esc_attr($formId).'" id="mhn-last-'.esc_attr((string) $id).'" name="mhn_lname" type="text" autocomplete="family-name" maxlength="80" value="'.esc_attr((string) ($row['last_name'] ?? '')).'"> ';
            echo '<button class="button" type="submit" form="'.esc_attr($formId).'">'.esc_html__('Save', 'matthummel-newsletter').'</button></td>';
            $signedUp = admin_datetime((string) ($row['created_at'] ?? ''));
            echo '<td>'.admin_status_badge(subscriber_status_label((string) ($row['status'] ?? '')), (string) ($row['status'] ?? '')).'</td>';
            echo '<td>'.esc_html(opt_in_label((string) ($row['opt_in'] ?? ''))).'</td>';
            echo '<td>'.esc_html($signedUp !== '' ? $signedUp : (string) ($row['created_at'] ?? '')).'</td>';
            echo '<td class="mhn-row-actions">';
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            wp_nonce_field('mhn_subscriber_unsub');
            echo '<input type="hidden" name="action" value="mhn_subscriber_unsub"><input type="hidden" name="id" value="'.esc_attr((string) $id).'">';
            echo '<button class="button-link" type="submit">'.esc_html__('Unsubscribe', 'matthummel-newsletter').'</button></form>';
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(\''.esc_js(__('Delete this address? They can sign up again.', 'matthummel-newsletter')).'\');">';
            wp_nonce_field('mhn_subscriber_delete');
            echo '<input type="hidden" name="action" value="mhn_subscriber_delete"><input type="hidden" name="id" value="'.esc_attr((string) $id).'">';
            echo '<button class="button-link" type="submit">'.esc_html__('Delete', 'matthummel-newsletter').'</button></form>';
            echo '</td></tr>';
        }
    }
    echo '</tbody></table>';
    if ($pages > 1) {
        echo '<p>';
        for ($i = 1; $i <= $pages; $i++) {
            $url = add_query_arg([
                'page' => 'mhn-subscribers',
                'paged' => $i,
                's' => $search !== '' ? $search : false,
            ], admin_url('admin.php'));
            if ($i === $page) {
                echo ' <strong>'.esc_html((string) $i).'</strong>';
            } else {
                echo ' <a href="'.esc_url($url).'">'.esc_html((string) $i).'</a>';
            }
        }
        echo '</p>';
    }
    echo '</section></div>';
}

function opt_in_label(string $optIn): string
{
    return match ($optIn) {
        'legacy_single' => __('Legacy single opt-in', 'matthummel-newsletter'),
        'import' => __('Imported with permission', 'matthummel-newsletter'),
        default => __('Double opt-in', 'matthummel-newsletter'),
    };
}

function page_import(): void
{
    guard_admin();
    echo '<div class="wrap mhn-admin mhn-narrow">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Import CSV', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Columns: email, and optional first_name and last_name. Addresses stay on this site.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    if (isset($_GET['imported'])) {
        echo '<div class="notice notice-success"><p>'.esc_html(sprintf(
            /* translators: 1: subscribed imports, 2: pending imports, 3: skipped, 4: invalid */
            __('Imported %1$d as subscribed, %2$d as pending. Skipped %3$d. Invalid %4$d.', 'matthummel-newsletter'),
            absint($_GET['imported'] ?? 0),
            absint($_GET['pending'] ?? 0),
            absint($_GET['skipped'] ?? 0),
            absint($_GET['invalid'] ?? 0)
        )).'</p></div>';
    }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="mhn_import">';
    wp_nonce_field('mhn_import');
    echo '<section class="mhn-card"><h2>'.esc_html__('File', 'matthummel-newsletter').'</h2>';
    echo '<p><label for="mhn-csv">'.esc_html__('CSV file', 'matthummel-newsletter').'</label>';
    echo '<input type="file" id="mhn-csv" name="mhn_csv" accept=".csv,text/csv" required></p>';
    echo '</section>';
    echo '<section class="mhn-card"><h2>'.esc_html__('Permission', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-check"><label><input type="radio" name="mhn_mode" value="consented" required> '.esc_html__('They already opted in. Mark them subscribed and do not email them.', 'matthummel-newsletter').'</label></p>';
    echo '<p class="mhn-check"><label><input type="radio" name="mhn_mode" value="pending"> '.esc_html__('They still need to confirm. Mark them pending.', 'matthummel-newsletter').'</label></p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_send_confirm" value="1"> '.esc_html__('Email confirmation links now (pending rows only).', 'matthummel-newsletter').'</label></p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_allow_unsub" value="1"> '.esc_html__('Include addresses that already unsubscribed.', 'matthummel-newsletter').'</label></p>';
    echo '</section>';
    echo '<p class="mhn-settings-save"><button class="button button-primary" type="submit">'.esc_html__('Import', 'matthummel-newsletter').'</button></p>';
    echo '</form></div>';
}

/**
 * @param  array{letter_style: string, letter_masthead: string, letter_button: string}  $config
 */
function render_letter_style_card(array $config): void
{
    $look = normalize_letter_look([
        'style' => $config['letter_style'],
        'masthead' => $config['letter_masthead'],
        'button' => $config['letter_button'],
    ]);

    echo '<section class="mhn-card" aria-labelledby="mhn-letter-style-heading">';
    echo '<h2 id="mhn-letter-style-heading">'.esc_html__('Letter style', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html__('The shell for every layout. New letters start here. Change one letter from the wizard.', 'matthummel-newsletter').'</p>';
    echo '<fieldset class="mhn-letter-options"><legend>'.esc_html__('Style', 'matthummel-newsletter').'</legend>';
    foreach (letter_style_choices() as $id => $choice) {
        $inputId = 'mhn-letter-style-'.$id;
        echo '<p class="mhn-check"><label for="'.esc_attr($inputId).'">';
        echo '<input type="radio" name="letter_style" id="'.esc_attr($inputId).'" value="'.esc_attr($id).'" '.checked($look['style'], $id, false).'> ';
        echo '<strong>'.esc_html($choice['label']).'</strong> '.esc_html($choice['summary']);
        echo '</label></p>';
    }
    echo '</fieldset>';
    echo '<fieldset class="mhn-letter-options"><legend>'.esc_html__('Masthead', 'matthummel-newsletter').'</legend>';
    foreach (letter_masthead_choices() as $id => $label) {
        $inputId = 'mhn-letter-masthead-'.$id;
        echo '<p class="mhn-check"><label for="'.esc_attr($inputId).'">';
        echo '<input type="radio" name="letter_masthead" id="'.esc_attr($inputId).'" value="'.esc_attr($id).'" '.checked($look['masthead'], $id, false).'> ';
        echo esc_html($label);
        echo '</label></p>';
    }
    echo '</fieldset>';
    echo '<fieldset class="mhn-letter-options"><legend>'.esc_html__('Button', 'matthummel-newsletter').'</legend>';
    foreach (letter_button_choices() as $id => $label) {
        $inputId = 'mhn-letter-button-'.$id;
        echo '<p class="mhn-check"><label for="'.esc_attr($inputId).'">';
        echo '<input type="radio" name="letter_button" id="'.esc_attr($inputId).'" value="'.esc_attr($id).'" '.checked($look['button'], $id, false).'> ';
        echo esc_html($label);
        echo '</label></p>';
    }
    echo '<p class="description">'.esc_html__('Solid is a navy fill. Outline is a navy border. The button stays hidden until it has a label and a URL.', 'matthummel-newsletter').'</p>';
    echo '</fieldset></section>';
}

/**
 * @param  array{style: string, masthead: string, button: string}  $look
 */
function render_letter_style_compact(array $look): void
{
    $look = normalize_letter_look($look);
    echo '<div class="mhn-letter-controls">';
    echo '<div class="mhn-choice" role="radiogroup" aria-label="'.esc_attr__('Letter style', 'matthummel-newsletter').'">';
    echo '<span>'.esc_html__('Letter style', 'matthummel-newsletter').'</span>';
    foreach (letter_style_choices() as $id => $choice) {
        echo '<label><input type="radio" name="mhn_letter_style" value="'.esc_attr($id).'" '.checked($look['style'], $id, false).'> '.esc_html($choice['label']).'</label>';
    }
    echo '</div>';
    echo '<div class="mhn-choice" role="radiogroup" aria-label="'.esc_attr__('Masthead', 'matthummel-newsletter').'">';
    echo '<span>'.esc_html__('Masthead', 'matthummel-newsletter').'</span>';
    foreach (letter_masthead_choices() as $id => $label) {
        echo '<label><input type="radio" name="mhn_letter_masthead" value="'.esc_attr($id).'" '.checked($look['masthead'], $id, false).'> '.esc_html($label).'</label>';
    }
    echo '</div>';
    echo '<div class="mhn-choice" role="radiogroup" aria-label="'.esc_attr__('Button', 'matthummel-newsletter').'">';
    echo '<span>'.esc_html__('Button', 'matthummel-newsletter').'</span>';
    foreach (letter_button_choices() as $id => $label) {
        echo '<label><input type="radio" name="mhn_letter_button" value="'.esc_attr($id).'" '.checked($look['button'], $id, false).'> '.esc_html($label).'</label>';
    }
    echo '</div></div>';
}

function page_settings(): void
{
    guard_admin();
    $config = settings();
    echo '<div class="wrap mhn-admin mhn-settings">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Newsletter settings', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('Who the letter is from, and what happens when you publish.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Saved.', 'matthummel-newsletter').'</p></div>';
    }
    $domainWarning = from_domain_warning();
    if ($domainWarning !== '') {
        echo '<div class="notice notice-warning"><p>'.esc_html($domainWarning).'</p></div>';
    }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="mhn_settings">';
    wp_nonce_field('mhn_settings');

    echo '<section class="mhn-card"><h2>'.esc_html__('Sender', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html__('Use an address on your domain so SPF, DKIM, and DMARC can align.', 'matthummel-newsletter').'</p>';
    echo '<p><label for="mhn-from-name">'.esc_html__('From name', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" id="mhn-from-name" name="from_name" value="'.esc_attr($config['from_name']).'"></p>';
    echo '<p><label for="mhn-from-email">'.esc_html__('From email', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" type="email" id="mhn-from-email" name="from_email" value="'.esc_attr($config['from_email']).'"></p>';
    echo '<p><label for="mhn-reply">'.esc_html__('Reply-to', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" type="email" id="mhn-reply" name="reply_to" value="'.esc_attr($config['reply_to']).'"></p>';
    echo '</section>';

    echo '<section class="mhn-card"><h2>'.esc_html__('Postal line', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html__('Shown in every issue. A street address is the usual postal requirement. The default is Gettysburg, PA.', 'matthummel-newsletter').'</p>';
    echo '<p><label for="mhn-address">'.esc_html__('Mailing address', 'matthummel-newsletter').'</label>';
    echo '<textarea class="large-text" rows="3" id="mhn-address" name="address">'.esc_textarea($config['address']).'</textarea></p>';
    echo '</section>';

    echo '<section class="mhn-card" aria-labelledby="mhn-copy-heading"><h2 id="mhn-copy-heading">'.esc_html__('Reusable copy', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html__('Standard uses the intro and the sign-off on every issue. Welcome uses its own subject and body. Edit them here once.', 'matthummel-newsletter').'</p>';
    echo '<p><label for="mhn-intro">'.esc_html__('Intro', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" id="mhn-intro" name="intro" value="'.esc_attr($config['intro']).'"></p>';
    echo '<p><label for="mhn-signoff">'.esc_html__('Sign-off', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" id="mhn-signoff" name="signoff" value="'.esc_attr($config['signoff']).'"></p>';
    echo '<p><label for="mhn-welcome-subject">'.esc_html__('Welcome subject', 'matthummel-newsletter').'</label>';
    echo '<input class="regular-text" id="mhn-welcome-subject" name="welcome_subject" value="'.esc_attr($config['welcome_subject']).'"></p>';
    echo '<p><label for="mhn-welcome-body">'.esc_html__('Welcome body', 'matthummel-newsletter').'</label>';
    echo '<textarea class="large-text" rows="8" id="mhn-welcome-body" name="welcome_body">'.esc_textarea($config['welcome_body']).'</textarea></p>';
    echo '</section>';

    render_letter_style_card($config);

    echo '<section class="mhn-card"><h2>'.esc_html__('Publishing', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="auto_draft" value="1" '.checked($config['auto_draft'], 1, false).'> '.esc_html__('When a post is published, save a draft issue for review.', 'matthummel-newsletter').'</label></p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="auto_send" value="1" '.checked($config['auto_send'], 1, false).'> '.esc_html__('Also send that issue automatically. Off unless you check this.', 'matthummel-newsletter').'</label></p>';
    echo '<p><label for="mhn-batch">'.esc_html__('Batch size', 'matthummel-newsletter').'</label>';
    echo '<input type="number" min="5" max="100" id="mhn-batch" name="batch_size" value="'.esc_attr((string) $config['batch_size']).'"></p>';
    echo '</section>';

    echo '<section class="mhn-card"><h2>'.esc_html__('Tracking', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html__('Both stay off until you check them. Opens use a tiny image on this site. Clicks go through this site.', 'matthummel-newsletter').'</p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="track_opens" value="1" '.checked($config['track_opens'], 1, false).'> '.esc_html__('Count opens.', 'matthummel-newsletter').'</label></p>';
    echo '<p class="mhn-check"><label><input type="checkbox" name="track_clicks" value="1" '.checked($config['track_clicks'], 1, false).'> '.esc_html__('Count clicks.', 'matthummel-newsletter').'</label></p>';
    echo '</section>';

    echo '<p class="mhn-settings-note">'.esc_html__('Sending uses wp_mail, so an SMTP plugin on this site is picked up automatically. Publish SPF, DKIM, and DMARC for the From domain before a real send.', 'matthummel-newsletter').'</p>';
    echo '<p class="mhn-settings-save"><button class="button button-primary" type="submit">'.esc_html__('Save settings', 'matthummel-newsletter').'</button></p>';
    echo '</form></div>';
}

function page_log(): void
{
    guard_admin();
    global $wpdb;

    $rows = $wpdb->get_results('SELECT * FROM '.events_table().' ORDER BY id DESC LIMIT 50', ARRAY_A);
    echo '<div class="wrap mhn-admin">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html__('Send log', 'matthummel-newsletter').'</h1>';
    echo '<p>'.esc_html__('The latest 50 events on this site.', 'matthummel-newsletter').'</p>';
    echo '</div></header>';
    echo '<section class="mhn-card mhn-table-card">';
    echo '<table class="widefat striped"><thead><tr>';
    foreach ([__('When', 'matthummel-newsletter'), __('Event', 'matthummel-newsletter'), __('Issue', 'matthummel-newsletter'), __('Subscriber', 'matthummel-newsletter'), __('Detail', 'matthummel-newsletter')] as $heading) {
        echo '<th>'.esc_html($heading).'</th>';
    }
    echo '</tr></thead><tbody>';
    if (! is_array($rows) || $rows === []) {
        echo '<tr><td colspan="5">'.esc_html__('No events yet.', 'matthummel-newsletter').'</td></tr>';
    } else {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>'.esc_html((string) ($row['created_at'] ?? '')).'</td>';
            echo '<td>'.esc_html((string) ($row['event'] ?? '')).'</td>';
            echo '<td>'.esc_html((string) ($row['issue_id'] ?? '')).'</td>';
            echo '<td>'.esc_html((string) ($row['subscriber_id'] ?? '')).'</td>';
            echo '<td>'.esc_html((string) ($row['detail'] ?? '')).'</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></section></div>';
}

function page_deliver(): void
{
    guard_admin();
    $issueId = isset($_GET['issue']) ? absint($_GET['issue']) : 0;
    $post = get_post($issueId);
    if (! $post instanceof \WP_Post || $post->post_type !== 'newsletter_issue') {
        echo '<div class="wrap mhn-admin mhn-narrow">';
        echo '<header class="mhn-dash-head"><div><h1>'.esc_html__('Delivery', 'matthummel-newsletter').'</h1>';
        echo '<p>'.esc_html__('Choose an issue first.', 'matthummel-newsletter').'</p></div></header></div>';

        return;
    }

    $me = wp_get_current_user();
    $local = (string) get_post_meta($issueId, '_mhn_scheduled_local', true);
    $localValue = $local !== '' ? str_replace(' ', 'T', substr($local, 0, 16)) : '';
    $ready = count_status('subscribed');

    echo '<div class="wrap mhn-admin mhn-narrow">';
    echo '<header class="mhn-dash-head"><div>';
    echo '<h1>'.esc_html(get_the_title($post)).'</h1>';
    echo '<p>'.esc_html(status_label(issue_status($issueId))).'</p>';
    echo '</div></header>';
    echo '<hr class="wp-header-end">';
    render_issue_audit($issueId);
    if (isset($_GET['tested'])) {
        $ok = ($_GET['tested'] ?? '') === '1';
        echo '<div class="notice '.($ok ? 'notice-success' : 'notice-error').'"><p>'.esc_html($ok
            ? __('Test sent to your account email.', 'matthummel-newsletter')
            : __('The test could not be sent.', 'matthummel-newsletter')).'</p></div>';
    }
    if (isset($_GET['queued'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Sending started.', 'matthummel-newsletter').'</p></div>';
    }
    if (isset($_GET['scheduled'])) {
        echo '<div class="notice notice-success"><p>'.esc_html__('Scheduled.', 'matthummel-newsletter').'</p></div>';
    }

    echo '<section class="mhn-card"><h2>'.esc_html__('Send a test', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html(sprintf(
        /* translators: %s: current user email */
        __('This goes only to %s.', 'matthummel-newsletter'),
        (string) $me->user_email
    )).'</p>';
    echo '<p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=mhn_send_test&issue='.$issueId), 'mhn_send_test_'.$issueId)).'">'.esc_html__('Send test', 'matthummel-newsletter').'</a></p></section>';

    echo '<section class="mhn-card"><h2>'.esc_html__('Schedule', 'matthummel-newsletter').'</h2>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="mhn_schedule"><input type="hidden" name="issue" value="'.esc_attr((string) $issueId).'">';
    wp_nonce_field('mhn_schedule_'.$issueId);
    echo '<p><label for="mhn-when">'.esc_html__('Send at', 'matthummel-newsletter').'</label>';
    echo '<input type="datetime-local" id="mhn-when" name="mhn_schedule_local" value="'.esc_attr($localValue).'" required></p>';
    echo '<p><button class="button" type="submit">'.esc_html__('Schedule', 'matthummel-newsletter').'</button></p></form></section>';

    echo '<section class="mhn-card"><h2>'.esc_html__('Send to the list', 'matthummel-newsletter').'</h2>';
    echo '<p class="mhn-card-lead">'.esc_html(sprintf(
        /* translators: %d: confirmed subscriber count */
        __('This emails %d confirmed subscribers.', 'matthummel-newsletter'),
        $ready
    )).'</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="mhn_send"><input type="hidden" name="issue" value="'.esc_attr((string) $issueId).'">';
    wp_nonce_field('mhn_send_'.$issueId);
    echo '<p class="mhn-check"><label><input type="checkbox" name="mhn_confirm_send" value="1" required> '.esc_html__('I mean to email every confirmed subscriber.', 'matthummel-newsletter').'</label></p>';
    echo '<p><button class="button button-primary" type="submit">'.esc_html__('Send now', 'matthummel-newsletter').'</button></p></form></section>';

    if ((int) get_post_meta($issueId, '_mhn_fail_count', true) > 0) {
        echo '<section class="mhn-card"><h2>'.esc_html__('Retry failed', 'matthummel-newsletter').'</h2>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="mhn_retry"><input type="hidden" name="issue" value="'.esc_attr((string) $issueId).'">';
        wp_nonce_field('mhn_retry_'.$issueId);
        echo '<p><button class="button" type="submit">'.esc_html__('Retry failed', 'matthummel-newsletter').'</button></p></form></section>';
    }
    echo '</div>';
}

function handle_export(): void
{
    guard_admin();
    check_admin_referer('mhn_export');
    global $wpdb;

    $columns = export_columns();
    $rows = $wpdb->get_results('SELECT '.implode(', ', $columns).' FROM '.subscribers_table().' ORDER BY id DESC', ARRAY_A);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=get-updates-'.gmdate('Y-m-d').'.csv');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        wp_die(esc_html__('Could not write the export.', 'matthummel-newsletter'));
    }
    fputcsv($out, $columns);
    foreach (is_array($rows) ? $rows : [] as $row) {
        $line = [];
        foreach ($columns as $key) {
            $line[] = csv_cell((string) ($row[$key] ?? ''));
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

/**
 * @return list<string>
 */
function export_columns(): array
{
    return ['email', 'first_name', 'last_name', 'status', 'opt_in', 'created_at', 'confirmed_at'];
}

function handle_subscriber_names(): void
{
    guard_admin();
    check_admin_referer('mhn_subscriber_names');
    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (find($id)) {
        update_subscriber($id, [
            'first_name' => clean_name(posted_text('mhn_fname')),
            'last_name' => clean_name(posted_text('mhn_lname')),
        ]);
    }

    $search = isset($_POST['s']) ? sanitize_text_field(wp_unslash($_POST['s'])) : '';
    wp_safe_redirect(add_query_arg([
        'page' => 'mhn-subscribers',
        'named' => '1',
        's' => $search !== '' ? $search : false,
    ], admin_url('admin.php')));
    exit;
}

function csv_cell(string $value): string
{
    if (preg_match('/^[=+\-@]/', $value) === 1) {
        return "'".$value;
    }

    return $value;
}

function handle_delete(): void
{
    guard_admin();
    check_admin_referer('mhn_subscriber_delete');
    delete_subscriber(isset($_POST['id']) ? absint($_POST['id']) : 0);
    wp_safe_redirect(admin_url('admin.php?page=mhn-subscribers&deleted=1'));
    exit;
}

function handle_admin_unsub(): void
{
    guard_admin();
    check_admin_referer('mhn_subscriber_unsub');
    unsubscribe(isset($_POST['id']) ? absint($_POST['id']) : 0);
    wp_safe_redirect(admin_url('admin.php?page=mhn-subscribers&unsub=1'));
    exit;
}

function handle_import(): void
{
    guard_admin();
    check_admin_referer('mhn_import');
    $file = $_FILES['mhn_csv'] ?? null;
    if (! is_array($file) || ! isset($file['tmp_name'], $file['size'], $file['name']) || ! is_uploaded_file((string) $file['tmp_name'])) {
        wp_die(esc_html__('Choose a CSV file.', 'matthummel-newsletter'));
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv' || (int) $file['size'] > 2 * MB_IN_BYTES) {
        wp_die(esc_html__('Use a CSV under 2 MB.', 'matthummel-newsletter'));
    }

    $mode = isset($_POST['mhn_mode']) ? sanitize_key(wp_unslash($_POST['mhn_mode'])) : '';
    if (! in_array($mode, ['consented', 'pending'], true)) {
        wp_die(esc_html__('Choose how these addresses should be treated.', 'matthummel-newsletter'));
    }

    $counts = import_rows(
        (string) $file['tmp_name'],
        $mode,
        $mode === 'pending' && ! empty($_POST['mhn_send_confirm']),
        ! empty($_POST['mhn_allow_unsub'])
    );

    wp_safe_redirect(add_query_arg([
        'page' => 'mhn-import',
        'imported' => $counts['imported'],
        'pending' => $counts['pending'],
        'skipped' => $counts['skipped'],
        'invalid' => $counts['invalid'],
    ], admin_url('admin.php')));
    exit;
}

function handle_settings(): void
{
    guard_admin();
    check_admin_referer('mhn_settings');
    update_settings([
        'from_name' => wp_unslash($_POST['from_name'] ?? ''),
        'from_email' => wp_unslash($_POST['from_email'] ?? ''),
        'reply_to' => wp_unslash($_POST['reply_to'] ?? ''),
        'address' => wp_unslash($_POST['address'] ?? ''),
        'auto_draft' => $_POST['auto_draft'] ?? '',
        'auto_send' => $_POST['auto_send'] ?? '',
        'track_opens' => $_POST['track_opens'] ?? '',
        'track_clicks' => $_POST['track_clicks'] ?? '',
        'batch_size' => $_POST['batch_size'] ?? 25,
        'intro' => wp_unslash($_POST['intro'] ?? ''),
        'signoff' => wp_unslash($_POST['signoff'] ?? ''),
        'welcome_subject' => wp_unslash($_POST['welcome_subject'] ?? ''),
        'welcome_body' => wp_unslash($_POST['welcome_body'] ?? ''),
        'letter_style' => wp_unslash($_POST['letter_style'] ?? ''),
        'letter_masthead' => wp_unslash($_POST['letter_masthead'] ?? ''),
        'letter_button' => wp_unslash($_POST['letter_button'] ?? ''),
    ]);
    wp_safe_redirect(admin_url('admin.php?page=mhn-settings&saved=1'));
    exit;
}

function handle_send_test(): void
{
    guard_admin();
    $issueId = isset($_REQUEST['issue']) ? absint($_REQUEST['issue']) : 0;
    check_admin_referer('mhn_send_test_'.$issueId);
    $ok = send_test($issueId);
    wp_safe_redirect(admin_url('admin.php?page=mhn-deliver&issue='.$issueId.'&tested='.($ok ? '1' : '0')));
    exit;
}

function handle_send(): void
{
    guard_admin();
    $issueId = isset($_POST['issue']) ? absint($_POST['issue']) : 0;
    check_admin_referer('mhn_send_'.$issueId);
    if (empty($_POST['mhn_confirm_send'])) {
        wp_die(esc_html__('Confirm the send first.', 'matthummel-newsletter'));
    }
    if (in_array(issue_status($issueId), ['sent', 'sending'], true)) {
        wp_die(esc_html__('This issue is already sent or still sending.', 'matthummel-newsletter'));
    }
    if (issue_send_blocked($issueId)) {
        wp_die(esc_html(implode(' ', audit_issue($issueId)['errors'])));
    }
    start_campaign($issueId);
    wp_safe_redirect(admin_url('admin.php?page=mhn-deliver&issue='.$issueId.'&queued=1'));
    exit;
}

function handle_schedule(): void
{
    guard_admin();
    $issueId = isset($_POST['issue']) ? absint($_POST['issue']) : 0;
    check_admin_referer('mhn_schedule_'.$issueId);
    $local = isset($_POST['mhn_schedule_local']) ? sanitize_text_field(wp_unslash($_POST['mhn_schedule_local'])) : '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $local) !== 1) {
        wp_die(esc_html__('Choose a date and time.', 'matthummel-newsletter'));
    }
    if (issue_send_blocked($issueId)) {
        wp_die(esc_html(implode(' ', audit_issue($issueId)['errors'])));
    }
    $mysql = str_replace('T', ' ', substr($local, 0, 16)).':00';
    update_post_meta($issueId, '_mhn_scheduled_local', $mysql);
    schedule_issue($issueId, get_gmt_from_date($mysql));
    wp_safe_redirect(admin_url('admin.php?page=mhn-deliver&issue='.$issueId.'&scheduled=1'));
    exit;
}

function handle_retry(): void
{
    guard_admin();
    $issueId = isset($_POST['issue']) ? absint($_POST['issue']) : 0;
    check_admin_referer('mhn_retry_'.$issueId);
    retry_failed($issueId);
    wp_safe_redirect(admin_url('admin.php?page=mhn-deliver&issue='.$issueId.'&queued=1'));
    exit;
}
