<?php
defined( 'ABSPATH' ) || exit;

/** The Today screen: calendar, tasks, recent files, and one-click workflows. */
class HOPS_Rundown {

	public static function init() {
		add_action( 'wp_ajax_hops_rundown', array( __CLASS__, 'ajax' ) );
	}

	private static function wrap( $r ) {
		if ( is_wp_error( $r ) ) {
			return array( 'error' => $r->get_error_message(), 'code' => $r->get_error_code() );
		}
		return array( 'data' => $r );
	}

	private static function calendar() {
		$start = new DateTime( 'today', wp_timezone() );
		$end   = ( clone $start )->modify( '+1 day' );
		$url   = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?' . http_build_query( array(
			'timeMin'      => $start->format( DATE_RFC3339 ),
			'timeMax'      => $end->format( DATE_RFC3339 ),
			'singleEvents' => 'true',
			'orderBy'      => 'startTime',
			'maxResults'   => 15,
			'fields'       => 'items(summary,start,end,htmlLink,location,hangoutLink,status)',
		), '', '&', PHP_QUERY_RFC3986 );
		$json = HOPS_Google::api_get( $url );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$out = array();
		foreach ( isset( $json['items'] ) ? $json['items'] : array() as $e ) {
			if ( isset( $e['status'] ) && 'cancelled' === $e['status'] ) {
				continue;
			}
			$all_day = isset( $e['start']['date'] );
			$out[]   = array(
				'title'    => isset( $e['summary'] ) ? $e['summary'] : '(no title)',
				'start'    => $all_day ? $e['start']['date'] : ( isset( $e['start']['dateTime'] ) ? $e['start']['dateTime'] : '' ),
				'end'      => isset( $e['end']['dateTime'] ) ? $e['end']['dateTime'] : '',
				'all_day'  => $all_day,
				'link'     => isset( $e['htmlLink'] ) ? $e['htmlLink'] : '',
				'location' => isset( $e['location'] ) ? $e['location'] : '',
				'meet'     => isset( $e['hangoutLink'] ) ? $e['hangoutLink'] : '',
			);
		}
		return $out;
	}

	private static function files() {
		$url  = 'https://www.googleapis.com/drive/v3/files?' . http_build_query( array(
			'pageSize'                  => 6,
			'orderBy'                   => 'modifiedTime desc',
			'q'                         => "trashed = false and mimeType != 'application/vnd.google-apps.folder'",
			'fields'                    => 'files(id,name,mimeType,modifiedTime,webViewLink,iconLink)',
			'supportsAllDrives'         => 'true',
			'includeItemsFromAllDrives' => 'true',
		), '', '&', PHP_QUERY_RFC3986 );
		$json = HOPS_Google::api_get( $url );
		return is_wp_error( $json ) ? $json : ( isset( $json['files'] ) ? $json['files'] : array() );
	}

	public static function ajax() {
		check_ajax_referer( 'hops' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
		}
		list( $items, $errors ) = HOPS_Todos::collect();
		wp_send_json_success( array(
			'today'    => wp_date( 'Y-m-d' ),
			'calendar' => self::wrap( self::calendar() ),
			'files'    => self::wrap( self::files() ),
			'tasks'    => array( 'items' => $items, 'errors' => $errors ),
		) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$user = wp_get_current_user();
		$name = $user->first_name ? $user->first_name : $user->display_name;
		$hour = (int) wp_date( 'G' );
		$part = $hour < 12 ? 'morning' : ( $hour < 17 ? 'afternoon' : 'evening' );
		$s    = HOPS_Settings::get();
		$st   = HOPS_WPRel::state();
		$last = null;
		foreach ( get_option( HOPS_N8n::LOG, array() ) as $row ) {
			$last = $row;
			break;
		}

		echo '<div class="wrap hops" id="hops-today">';
		HOPS_UI::head(
			'Good ' . $part . ', ' . $name,
			wp_date( 'l, F j' ) . '. Loading your day…',
			'<a class="button" href="' . esc_url( HOPS_UI::url( 'hops-tasks' ) ) . '">All tasks</a><a class="button" href="' . esc_url( HOPS_UI::url( 'hops-workflows' ) ) . '">All workflows</a>',
			'hops-lede'
		);

		$tiles  = HOPS_UI::stat( 'Events today', '–', '', 'hops-stat-events' );
		$tiles .= HOPS_UI::stat( 'Tasks due', '–', '', 'hops-stat-tasks' );
		$tiles .= HOPS_UI::stat(
			'Last workflow run',
			$last ? $last['name'] : 'None yet',
			$last ? HOPS_UI::run_pill( $last['code'] ) . ' ' . esc_html( HOPS_UI::when( $last['time'] ) ) : HOPS_UI::pill( 'Never run', 'neutral' ),
			'hops-stat-run'
		);
		$tiles .= HOPS_UI::stat(
			'Latest WordPress',
			$st['latest'] ? $st['latest'] : 'Not checked',
			$st['checked'] ? '<a href="' . esc_url( HOPS_UI::url( 'hops-wp' ) ) . '">Checked ' . esc_html( human_time_diff( $st['checked'] ) ) . ' ago</a>' : '<a href="' . esc_url( HOPS_UI::url( 'hops-wp' ) ) . '">Run the first check</a>',
			'hops-stat-wp'
		);
		HOPS_UI::stats( $tiles );
		?>
			<div class="hops-cols">
				<div class="hops-col">
					<section class="hops-panel" data-sec="tasks">
						<div class="hops-panel-head"><h2>Tasks this week</h2></div>
						<form class="hops-quick">
							<input type="text" name="title" placeholder="Add a task due today" aria-label="Add a task due today" required>
							<button class="button button-primary">Add</button>
						</form>
						<div class="hops-body">Loading&hellip;</div>
					</section>

					<section class="hops-panel" data-sec="files">
						<div class="hops-panel-head"><h2>Recent files</h2><a href="<?php echo esc_url( HOPS_UI::url( 'hops-drive' ) ); ?>">Open Files</a></div>
						<div class="hops-body">Loading&hellip;</div>
					</section>
				</div>

				<div class="hops-col">
					<section class="hops-panel" data-sec="calendar">
						<div class="hops-panel-head"><h2>Calendar</h2></div>
						<div class="hops-body">Loading&hellip;</div>
					</section>

					<section class="hops-panel" data-sec="workflows">
						<div class="hops-panel-head"><h2>Run a workflow</h2></div>
						<?php if ( empty( $s['workflows'] ) ) : ?>
							<?php echo HOPS_UI::empty_state( 'No workflows yet', 'Add an n8n webhook URL and it shows up here as a Run button.', 'Add a workflow', HOPS_UI::url( 'hops-settings', '#hops-sec-n8n' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php else : ?>
							<div class="hops-wfs is-compact">
								<?php foreach ( $s['workflows'] as $i => $wf ) { HOPS_N8n::card( $i, $wf, true ); } ?>
							</div>
						<?php endif; ?>
					</section>
				</div>
			</div>
		</div>
		<?php
	}
}
