<?php
defined('ABSPATH') || exit;

class HOPS_Drive
{
    public static function init()
    {
        add_action('wp_ajax_hops_drive_list', [__CLASS__, 'ajax_list']);
    }

    public static function ajax_list()
    {
        check_ajax_referer('hops');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed.'], 403);
        }

        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'folder';
        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : 'root';
        $term = isset($_POST['q']) ? trim(sanitize_text_field(wp_unslash($_POST['q']))) : '';
        $page = isset($_POST['pageToken']) ? sanitize_text_field(wp_unslash($_POST['pageToken'])) : '';

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $folder)) {
            $folder = 'root';
        }

        $order = 'folder,name';
        if ($term !== '') {
            $esc = str_replace(['\\', "'"], ['\\\\', "\\'"], $term);
            $q = "fullText contains '{$esc}' and trashed = false";
            $order = 'modifiedTime desc';
        } elseif ($mode === 'shared') {
            $q = 'sharedWithMe = true and trashed = false';
            $order = 'modifiedTime desc';
        } elseif ($mode === 'recent') {
            $q = 'trashed = false';
            $order = 'modifiedTime desc';
        } else {
            $q = "'{$folder}' in parents and trashed = false";
        }

        $args = [
            'pageSize' => 50,
            'fields' => 'nextPageToken,files(id,name,mimeType,modifiedTime,webViewLink,iconLink)',
            'orderBy' => $order,
            'q' => $q,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ];
        if ($page !== '') {
            $args['pageToken'] = $page;
        }

        $json = HOPS_Google::api_get('https://www.googleapis.com/drive/v3/files?'.http_build_query($args, '', '&', PHP_QUERY_RFC3986));
        if (is_wp_error($json)) {
            wp_send_json_error(['message' => $json->get_error_message()]);
        }
        wp_send_json_success([
            'files' => isset($json['files']) ? $json['files'] : [],
            'next' => isset($json['nextPageToken']) ? $json['nextPageToken'] : '',
        ]);
    }

    public static function render()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $ready = HOPS_Google::configured() && HOPS_Google::connected();
        echo '<div class="wrap hops">';
        HOPS_UI::head(
            'Files',
            'Browse and search your Google Drive documents without leaving WordPress. Access is read-only.',
            $ready ? '<button type="button" class="button hops-danger" data-hops-confirm="disconnect" data-confirm-title="Disconnect Google?" data-confirm-body="Files, today’s calendar, and the WordPress Releases documents stop updating until you connect again." data-confirm-button="Disconnect Google">Disconnect Google</button>' : ''
        );
        ?>
			<?php if (isset($_GET['hops_err'])) { ?>
				<div class="notice notice-error"><p>Google sign-in did not complete. Check the client ID, secret, and redirect URI in Integrations, then try again.</p></div>
			<?php } elseif (isset($_GET['hops_ok'])) { ?>
				<div class="notice notice-success is-dismissible"><p>Google is connected. Drive files and today’s calendar are now available.</p></div>
			<?php } ?>

			<?php if (! HOPS_Google::configured()) { ?>
				<?php echo HOPS_UI::empty_state( // phpcs:ignore WordPress.Security.EscapeOutput
				    'Add your Google client first',
				    'Create an OAuth client in Google Cloud, then paste its client ID and secret in Integrations. It takes about five minutes and you only do it once.',
				    'Open Google setup',
				    HOPS_UI::url('hops-settings', '#hops-sec-google')
				); ?>
			<?php } elseif (! HOPS_Google::connected()) { ?>
				<?php echo HOPS_UI::empty_state( // phpcs:ignore WordPress.Security.EscapeOutput
				    'Connect Google to see your files',
				    'One sign-in shows your Drive documents here and today’s events on the Today screen.',
				    'Connect Google',
				    HOPS_Google::auth_url()
				); ?>
			<?php } else { ?>
				<form id="hops-disconnect" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden>
					<input type="hidden" name="action" value="hops_disconnect">
					<?php wp_nonce_field('hops_disconnect'); ?>
				</form>
				<div class="hops-drive" id="hops-drive">
					<div class="hops-toolbar">
						<div class="hops-seg" role="tablist" aria-label="Where to look">
							<button type="button" role="tab" class="hops-tab" data-mode="folder" aria-selected="true">My Drive</button>
							<button type="button" role="tab" class="hops-tab" data-mode="recent" aria-selected="false">Recent</button>
							<button type="button" role="tab" class="hops-tab" data-mode="shared" aria-selected="false">Shared with me</button>
						</div>
						<form class="hops-search" role="search">
							<input type="search" placeholder="Search by file name" aria-label="Search Drive by file name">
							<button class="button">Search</button>
						</form>
					</div>
					<nav class="hops-crumbs" aria-label="Folder path"></nav>
					<div class="hops-status" role="status" aria-live="polite"></div>
					<div class="hops-table-wrap">
					<table class="widefat striped hops-table">
						<thead><tr><th>Name</th><th>Type</th><th>Modified</th></tr></thead>
						<tbody></tbody>
					</table>
					</div>
					<p><button type="button" class="button hops-more" hidden>Load more files</button></p>
				</div>
			<?php } ?>
		</div>
		<?php
    }
}
