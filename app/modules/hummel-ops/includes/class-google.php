<?php
defined( 'ABSPATH' ) || exit;

/** Shared Google OAuth connection: Drive and Calendar (read-only) plus Docs (read/write, used by WP Releases). */
class HOPS_Google {
	const REFRESH   = 'hops_google_refresh';
	const TRANSIENT = 'hops_google_access';
	const SCOPES    = 'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/calendar.readonly https://www.googleapis.com/auth/documents';
	const SCOPE_OPT = 'hops_google_scopes';
	const DOCS      = 'https://www.googleapis.com/auth/documents';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_callback' ) );
		add_action( 'admin_post_hops_disconnect', array( __CLASS__, 'disconnect' ) );
	}

	public static function redirect_uri() {
		return admin_url( 'admin.php?page=hops-drive' );
	}

	public static function configured() {
		$s = HOPS_Settings::get();
		return '' !== $s['google_client_id'] && '' !== $s['google_client_secret'];
	}

	public static function connected() {
		return '' !== (string) get_option( self::REFRESH, '' );
	}

	/** True when the saved connection was granted this scope. Older connections lack the Docs scope. */
	public static function has_scope( $scope ) {
		return self::connected() && false !== strpos( (string) get_option( self::SCOPE_OPT, '' ), $scope );
	}

	public static function auth_url() {
		$s     = HOPS_Settings::get();
		$state = wp_generate_password( 24, false );
		set_transient( 'hops_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );
		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( array(
			'client_id'     => $s['google_client_id'],
			'redirect_uri'  => self::redirect_uri(),
			'response_type' => 'code',
			'scope'         => self::SCOPES,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		), '', '&', PHP_QUERY_RFC3986 );
	}

	public static function handle_callback() {
		if ( ! isset( $_GET['page'], $_GET['code'] ) || 'hops-drive' !== $_GET['page'] || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$dest  = self::redirect_uri();
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$saved = get_transient( 'hops_oauth_state_' . get_current_user_id() );
		delete_transient( 'hops_oauth_state_' . get_current_user_id() );
		if ( ! $saved || ! hash_equals( $saved, $state ) ) {
			wp_safe_redirect( add_query_arg( 'hops_err', 'state', $dest ) );
			exit;
		}

		$s   = HOPS_Settings::get();
		$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'timeout' => 20,
			'body'    => array(
				'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ),
				'client_id'     => $s['google_client_id'],
				'client_secret' => $s['google_client_secret'],
				'redirect_uri'  => self::redirect_uri(),
				'grant_type'    => 'authorization_code',
			),
		) );
		$json = is_wp_error( $res ) ? array() : json_decode( wp_remote_retrieve_body( $res ), true );

		if ( empty( $json['refresh_token'] ) ) {
			wp_safe_redirect( add_query_arg( 'hops_err', 'token', $dest ) );
			exit;
		}
		update_option( self::REFRESH, HOPS_Settings::encrypt( $json['refresh_token'] ), false );
		update_option( self::SCOPE_OPT, isset( $json['scope'] ) ? sanitize_text_field( $json['scope'] ) : '', false );
		if ( ! empty( $json['access_token'] ) ) {
			set_transient( self::TRANSIENT, $json['access_token'], max( 60, (int) $json['expires_in'] - 120 ) );
		}
		wp_safe_redirect( add_query_arg( 'hops_ok', '1', $dest ) );
		exit;
	}

	public static function disconnect() {
		check_admin_referer( 'hops_disconnect' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		delete_option( self::REFRESH );
		delete_option( self::SCOPE_OPT );
		delete_transient( self::TRANSIENT );
		wp_safe_redirect( self::redirect_uri() );
		exit;
	}

	public static function token() {
		$tok = get_transient( self::TRANSIENT );
		if ( $tok ) {
			return $tok;
		}
		$refresh = HOPS_Settings::decrypt( get_option( self::REFRESH, '' ) );
		if ( '' === $refresh ) {
			return new WP_Error( 'hops_nc', 'Google is not connected.' );
		}
		$s   = HOPS_Settings::get();
		$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'timeout' => 20,
			'body'    => array(
				'client_id'     => $s['google_client_id'],
				'client_secret' => $s['google_client_secret'],
				'refresh_token' => $refresh,
				'grant_type'    => 'refresh_token',
			),
		) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( empty( $json['access_token'] ) ) {
			if ( isset( $json['error'] ) && 'invalid_grant' === $json['error'] ) {
				delete_option( self::REFRESH );
			}
			return new WP_Error( 'hops_nc', 'Google rejected the saved connection. Reconnect on the Files page.' );
		}
		set_transient( self::TRANSIENT, $json['access_token'], max( 60, (int) $json['expires_in'] - 120 ) );
		return $json['access_token'];
	}

	/** GET a Google API URL. Returns decoded JSON or WP_Error. */
	public static function api_get( $url ) {
		return self::api_request( 'GET', $url );
	}

	/** POST a JSON body to a Google API URL. Returns decoded JSON or WP_Error. */
	public static function api_post( $url, $body ) {
		return self::api_request( 'POST', $url, $body );
	}

	private static function api_request( $method, $url, $body = null ) {
		$token = self::token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array( 'Authorization' => 'Bearer ' . $token ),
		);
		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 !== $code ) {
			if ( 401 === $code ) {
				delete_transient( self::TRANSIENT );
			}
			$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : 'Google API error ' . $code;
			if ( 403 === $code ) {
				$msg .= ' If this is a permissions error, enable the API in Google Cloud, then disconnect and connect Google again.';
			}
			return new WP_Error( 'hops_api', $msg, array( 'status' => $code ) );
		}
		return is_array( $json ) ? $json : array();
	}
}
