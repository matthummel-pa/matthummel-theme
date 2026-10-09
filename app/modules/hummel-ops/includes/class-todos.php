<?php
defined( 'ABSPATH' ) || exit;

abstract class HOPS_Todo_Provider {
	abstract public function id();
	abstract public function label();
	abstract public function configured();
	abstract public function fetch();
	abstract public function add( $title, $due );
	abstract public function complete( $id );

	protected function item( $id, $title, $due, $url = '' ) {
		return array(
			'id'             => (string) $id,
			'provider'       => $this->id(),
			'provider_label' => $this->label(),
			'title'          => $title,
			'due'            => $due ? substr( $due, 0, 10 ) : null,
			'url'            => $url,
		);
	}

	/** JSON request helper. Returns decoded array or WP_Error. */
	protected function req( $method, $url, $headers = array(), $body = null ) {
		$args = array( 'method' => $method, 'timeout' => 20, 'headers' => $headers );
		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$json = json_decode( $raw, true );
		if ( $code < 200 || $code >= 300 ) {
			$msg = is_array( $json ) && isset( $json['message'] ) ? $json['message'] : ( is_array( $json ) && isset( $json['error'] ) && is_string( $json['error'] ) ? $json['error'] : substr( wp_strip_all_tags( $raw ), 0, 160 ) );
			return new WP_Error( 'hops_http', 'HTTP ' . $code . ': ' . $msg );
		}
		return is_array( $json ) ? $json : array();
	}
}

/** Tasks stored in WordPress itself. */
class HOPS_Todo_Local extends HOPS_Todo_Provider {
	const OPT = 'hops_local_todos';

	public function id() { return 'local'; }
	public function label() { return 'WordPress'; }
	public function configured() { return true; }

	public function fetch() {
		$out = array();
		foreach ( get_option( self::OPT, array() ) as $t ) {
			$out[] = $this->item( $t['id'], $t['title'], $t['due'], '' );
		}
		return $out;
	}

	public function add( $title, $due ) {
		$all   = get_option( self::OPT, array() );
		$all[] = array( 'id' => wp_generate_uuid4(), 'title' => $title, 'due' => $due );
		update_option( self::OPT, $all, false );
		return true;
	}

	public function complete( $id ) {
		$all = array_values( array_filter( get_option( self::OPT, array() ), function ( $t ) use ( $id ) {
			return $t['id'] !== $id;
		} ) );
		update_option( self::OPT, $all, false );
		return true;
	}
}

class HOPS_Todo_Notion extends HOPS_Todo_Provider {
	const API = 'https://api.notion.com/v1/';

	public function id() { return 'notion'; }
	public function label() { return 'Notion'; }

	public function configured() {
		$s = HOPS_Settings::get();
		return '' !== $s['notion_token'] && '' !== $s['notion_database_id'];
	}

	private function headers() {
		$s = HOPS_Settings::get();
		return array(
			'Authorization'  => 'Bearer ' . $s['notion_token'],
			'Notion-Version' => '2022-06-28',
		);
	}

	/** Property name => type, plus the title property's name. Cached 10 minutes. */
	private function schema() {
		$cached = get_transient( 'hops_notion_schema' );
		if ( $cached ) {
			return $cached;
		}
		$s   = HOPS_Settings::get();
		$res = $this->req( 'GET', self::API . 'databases/' . $s['notion_database_id'], $this->headers() );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$types = array();
		$title = 'Name';
		foreach ( isset( $res['properties'] ) ? $res['properties'] : array() as $name => $p ) {
			$types[ $name ] = $p['type'];
			if ( 'title' === $p['type'] ) {
				$title = $name;
			}
		}
		$schema = array( 'types' => $types, 'title' => $title );
		set_transient( 'hops_notion_schema', $schema, 10 * MINUTE_IN_SECONDS );
		return $schema;
	}

	public function fetch() {
		$schema = $this->schema();
		if ( is_wp_error( $schema ) ) {
			return $schema;
		}
		$s    = HOPS_Settings::get();
		$done = $s['notion_done_prop'];
		$due  = $s['notion_due_prop'];
		$type = isset( $schema['types'][ $done ] ) ? $schema['types'][ $done ] : '';

		$body = array( 'page_size' => 50 );
		if ( 'checkbox' === $type ) {
			$body['filter'] = array( 'property' => $done, 'checkbox' => array( 'equals' => false ) );
		} elseif ( 'status' === $type || 'select' === $type ) {
			$body['filter'] = array( 'property' => $done, $type => array( 'does_not_equal' => $s['notion_done_value'] ) );
		}
		if ( isset( $schema['types'][ $due ] ) && 'date' === $schema['types'][ $due ] ) {
			$body['sorts'] = array( array( 'property' => $due, 'direction' => 'ascending' ) );
		}

		$res = $this->req( 'POST', self::API . 'databases/' . $s['notion_database_id'] . '/query', $this->headers(), $body );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$out = array();
		foreach ( isset( $res['results'] ) ? $res['results'] : array() as $page ) {
			$props = isset( $page['properties'] ) ? $page['properties'] : array();
			$title = '';
			if ( isset( $props[ $schema['title'] ]['title'] ) ) {
				foreach ( $props[ $schema['title'] ]['title'] as $seg ) {
					$title .= isset( $seg['plain_text'] ) ? $seg['plain_text'] : '';
				}
			}
			$date = isset( $props[ $due ]['date']['start'] ) ? $props[ $due ]['date']['start'] : null;
			$out[] = $this->item( $page['id'], '' !== $title ? $title : '(untitled)', $date, isset( $page['url'] ) ? $page['url'] : '' );
		}
		return $out;
	}

	public function add( $title, $due ) {
		$schema = $this->schema();
		if ( is_wp_error( $schema ) ) {
			return $schema;
		}
		$s     = HOPS_Settings::get();
		$props = array( $schema['title'] => array( 'title' => array( array( 'text' => array( 'content' => $title ) ) ) ) );
		if ( $due && isset( $schema['types'][ $s['notion_due_prop'] ] ) ) {
			$props[ $s['notion_due_prop'] ] = array( 'date' => array( 'start' => $due ) );
		}
		$res = $this->req( 'POST', self::API . 'pages', $this->headers(), array(
			'parent'     => array( 'database_id' => $s['notion_database_id'] ),
			'properties' => $props,
		) );
		return is_wp_error( $res ) ? $res : true;
	}

	public function complete( $id ) {
		$schema = $this->schema();
		if ( is_wp_error( $schema ) ) {
			return $schema;
		}
		$s    = HOPS_Settings::get();
		$done = $s['notion_done_prop'];
		$type = isset( $schema['types'][ $done ] ) ? $schema['types'][ $done ] : '';
		if ( 'checkbox' === $type ) {
			$val = array( 'checkbox' => true );
		} elseif ( 'status' === $type || 'select' === $type ) {
			$val = array( $type => array( 'name' => $s['notion_done_value'] ) );
		} else {
			return new WP_Error( 'hops_notion', 'Done property "' . $done . '" was not found in the database. Check Integrations.' );
		}
		$res = $this->req( 'PATCH', self::API . 'pages/' . rawurlencode( $id ), $this->headers(), array( 'properties' => array( $done => $val ) ) );
		return is_wp_error( $res ) ? $res : true;
	}
}

class HOPS_Todo_Todoist extends HOPS_Todo_Provider {
	const API = 'https://api.todoist.com/api/v1/';

	public function id() { return 'todoist'; }
	public function label() { return 'Todoist'; }

	public function configured() {
		$s = HOPS_Settings::get();
		return '' !== $s['todoist_token'];
	}

	private function headers() {
		$s = HOPS_Settings::get();
		return array( 'Authorization' => 'Bearer ' . $s['todoist_token'] );
	}

	public function fetch() {
		$res = $this->req( 'GET', self::API . 'tasks?limit=200', $this->headers() );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$list = isset( $res['results'] ) ? $res['results'] : $res;
		$out  = array();
		foreach ( $list as $t ) {
			if ( ! is_array( $t ) || ! isset( $t['id'] ) ) {
				continue;
			}
			$due   = isset( $t['due']['date'] ) ? $t['due']['date'] : null;
			$out[] = $this->item( $t['id'], isset( $t['content'] ) ? $t['content'] : '(untitled)', $due, 'https://app.todoist.com/app/task/' . $t['id'] );
		}
		return $out;
	}

	public function add( $title, $due ) {
		$body = array( 'content' => $title );
		if ( $due ) {
			$body['due_date'] = $due;
		}
		$res = $this->req( 'POST', self::API . 'tasks', $this->headers(), $body );
		return is_wp_error( $res ) ? $res : true;
	}

	public function complete( $id ) {
		$res = $this->req( 'POST', self::API . 'tasks/' . rawurlencode( $id ) . '/close', $this->headers() );
		return is_wp_error( $res ) ? $res : true;
	}
}

class HOPS_Todo_Trello extends HOPS_Todo_Provider {
	const API = 'https://api.trello.com/1/';

	public function id() { return 'trello'; }
	public function label() { return 'Trello'; }

	public function configured() {
		$s = HOPS_Settings::get();
		return '' !== $s['trello_key'] && '' !== $s['trello_token'] && '' !== $s['trello_board_id'];
	}

	private function auth( $extra = array() ) {
		$s = HOPS_Settings::get();
		return http_build_query( array_merge( array( 'key' => $s['trello_key'], 'token' => $s['trello_token'] ), $extra ), '', '&', PHP_QUERY_RFC3986 );
	}

	public function fetch() {
		$s   = HOPS_Settings::get();
		$res = $this->req( 'GET', self::API . 'boards/' . rawurlencode( $s['trello_board_id'] ) . '/cards?' . $this->auth( array( 'fields' => 'name,due,dueComplete,url' ) ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$out = array();
		foreach ( $res as $c ) {
			if ( ! empty( $c['dueComplete'] ) ) {
				continue;
			}
			$out[] = $this->item( $c['id'], $c['name'], isset( $c['due'] ) ? $c['due'] : null, isset( $c['url'] ) ? $c['url'] : '' );
		}
		return $out;
	}

	public function add( $title, $due ) {
		$s     = HOPS_Settings::get();
		$lists = $this->req( 'GET', self::API . 'boards/' . rawurlencode( $s['trello_board_id'] ) . '/lists?' . $this->auth( array( 'filter' => 'open', 'fields' => 'name' ) ) );
		if ( is_wp_error( $lists ) ) {
			return $lists;
		}
		if ( empty( $lists[0]['id'] ) ) {
			return new WP_Error( 'hops_trello', 'No open lists on that Trello board.' );
		}
		$params = array( 'idList' => $lists[0]['id'], 'name' => $title );
		if ( $due ) {
			$params['due'] = $due;
		}
		$res = $this->req( 'POST', self::API . 'cards?' . $this->auth( $params ) );
		return is_wp_error( $res ) ? $res : true;
	}

	/** Completing archives the card. */
	public function complete( $id ) {
		$res = $this->req( 'PUT', self::API . 'cards/' . rawurlencode( $id ) . '?' . $this->auth( array( 'closed' => 'true' ) ) );
		return is_wp_error( $res ) ? $res : true;
	}
}

class HOPS_Todos {

	public static function init() {
		add_action( 'wp_ajax_hops_todos_list', array( __CLASS__, 'ajax_list' ) );
		add_action( 'wp_ajax_hops_todo_add', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_hops_todo_done', array( __CLASS__, 'ajax_done' ) );
	}

	/** Add a provider here to bring another to-do app into the hub. */
	public static function providers() {
		static $p = null;
		if ( null === $p ) {
			$all = array( new HOPS_Todo_Local(), new HOPS_Todo_Notion(), new HOPS_Todo_Todoist(), new HOPS_Todo_Trello() );
			$p   = array();
			foreach ( $all as $prov ) {
				$p[ $prov->id() ] = $prov;
			}
		}
		return $p;
	}

	public static function configured_providers() {
		return array_filter( self::providers(), function ( $p ) {
			return $p->configured();
		} );
	}

	/** Open tasks from every configured provider. */
	public static function collect() {
		$items  = array();
		$errors = array();
		foreach ( self::configured_providers() as $p ) {
			$r = $p->fetch();
			if ( is_wp_error( $r ) ) {
				$errors[ $p->label() ] = $r->get_error_message();
			} else {
				$items = array_merge( $items, $r );
			}
		}
		usort( $items, function ( $a, $b ) {
			$c = ( isset( $a['due'] ) ? $a['due'] : '9999-99-99' ) <=> ( isset( $b['due'] ) ? $b['due'] : '9999-99-99' );
			return 0 !== $c ? $c : strcasecmp( $a['title'], $b['title'] );
		} );
		return array( $items, $errors );
	}

	private static function guard() {
		check_ajax_referer( 'hops' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
		}
	}

	public static function ajax_list() {
		self::guard();
		list( $items, $errors ) = self::collect();
		wp_send_json_success( array( 'items' => $items, 'errors' => $errors, 'today' => wp_date( 'Y-m-d' ) ) );
	}

	public static function ajax_add() {
		self::guard();
		$title = isset( $_POST['title'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['title'] ) ) ) : '';
		$due   = isset( $_POST['due'] ) ? sanitize_text_field( wp_unslash( $_POST['due'] ) ) : '';
		$prov  = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		if ( '' === $title ) {
			wp_send_json_error( array( 'message' => 'Enter a task title.' ), 400 );
		}
		if ( '' !== $due && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due ) ) {
			wp_send_json_error( array( 'message' => 'Due date must be YYYY-MM-DD.' ), 400 );
		}
		$all = self::configured_providers();
		if ( ! isset( $all[ $prov ] ) ) {
			$s    = HOPS_Settings::get();
			$prov = isset( $all[ $s['todo_default'] ] ) ? $s['todo_default'] : 'local';
		}
		$r = $all[ $prov ]->add( $title, $due ?: null );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message() ) );
		}
		wp_send_json_success( array( 'provider' => $prov ) );
	}

	public static function ajax_done() {
		self::guard();
		$prov = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		$id   = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$all  = self::configured_providers();
		if ( ! isset( $all[ $prov ] ) || '' === $id ) {
			wp_send_json_error( array( 'message' => 'Unknown task.' ), 400 );
		}
		$r = $all[ $prov ]->complete( $id );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message() ) );
		}
		wp_send_json_success();
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$provs = self::configured_providers();
		$s     = HOPS_Settings::get();
		echo '<div class="wrap hops">';
		HOPS_UI::head(
			'Tasks',
			'Every open task from WordPress, Notion, Todoist, and Trello in one list, grouped by due date.',
			'<a class="button" href="' . esc_url( HOPS_UI::url( 'hops-settings', '#hops-sec-tasks' ) ) . '">Connect an app</a>'
		);
		?>
			<div id="hops-tasks">
				<?php if ( count( $provs ) < 2 ) : ?>
					<div class="notice notice-info inline"><p>You are seeing WordPress tasks only. <a href="<?php echo esc_url( HOPS_UI::url( 'hops-settings', '#hops-sec-tasks' ) ); ?>">Connect Notion, Todoist, or Trello</a> to bring those lists in.</p></div>
				<?php endif; ?>
				<section class="hops-panel">
					<div class="hops-panel-head"><h2>Add a task</h2></div>
					<form class="hops-task-form hops-form">
						<label class="grow">Task
							<input type="text" name="title" placeholder="Send the Q4 campaign brief" required>
						</label>
						<label>Due date
							<input type="date" name="due">
						</label>
						<label>Save to
							<select name="provider">
								<?php foreach ( $provs as $id => $p ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $s['todo_default'], $id ); ?>><?php echo esc_html( $p->label() ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<button class="button button-primary">Add task</button>
					</form>
				</section>
				<div class="hops-chips" role="group" aria-label="Filter by app">
					<button type="button" class="button hops-chip" data-filter="" aria-pressed="true">All <span class="n"></span></button>
					<?php foreach ( $provs as $id => $p ) : ?>
						<button type="button" class="button hops-chip" data-filter="<?php echo esc_attr( $id ); ?>" aria-pressed="false"><?php echo esc_html( $p->label() ); ?> <span class="n"></span></button>
					<?php endforeach; ?>
				</div>
				<div class="hops-errors"></div>
				<div class="hops-status" role="status" aria-live="polite"></div>
				<section class="hops-panel hops-groups" hidden></section>
			</div>
		</div>
		<?php
	}
}
