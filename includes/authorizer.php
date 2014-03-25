<?php
class Search_Tweets_Widget_Authorizer {
	/**
	 * URL to Authorize the app.
	 *
	 * @var string
	 */
	const AUTH_URL = 'https://api.twitter.com/oauth/authorize?oauth_token=%s';

	/**
	 * @var Search_Tweets_Widget_Plugin
	 */
	private $plugin;

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'load-settings_page_' . $this->plugin->name, array( $this, 'oauth_dance' ) );
	}

	/**
	 * Perform 3-legged OAuth when settings page is loaded.
	 *
	 * @action load-{setting_page}
	 * @see    self::create_setting_menu
	 * @link   https://dev.twitter.com/docs/auth/3-legged-authorization
	 */
	public function oauth_dance() {
		$callback_url = admin_url( 'options-general.php?page=' . $this->plugin->name );

		// Check error message.
		$this->check_error_message();

		$nonce_key = $this->get_nonce_key();

		$client = $this->plugin->client;

		// Twitter redirected to our callback_url.
		if ( isset( $_REQUEST['oauth_verifier'] ) ) {
			$token = $this->plugin->setting->get( 'tmp_token' );

			// Removes temporary token and secret.
			$this->plugin->setting->delete( 'tmp_token' );
			$this->plugin->setting->delete( 'tmp_token_secret' );

			// Parameters to send in request to endpoint.
			$parameters = array(
				'oauth_token'    => $token,
				'oauth_verifier' => $_REQUEST['oauth_verifier'],
			);

			// Converting temporary request token into access_token.
			$resp   = $client->request( 'POST', 'oauth/access_token', compact( 'parameters' ) );
			$status = wp_remote_retrieve_response_code( $resp );

			if ( 200 === intval( $status ) ) {
				/**
				 * @var array $tokens
				 */
				wp_parse_str( wp_remote_retrieve_body( $resp ), $tokens );

				$this->plugin->setting->set( 'token',        $tokens['oauth_token'] );
				$this->plugin->setting->set( 'token_secret', $tokens['oauth_token_secret'] );

				// We successfully got access token, redirect to callback URL
				// with query string from Twitter removed in order to prevent user
				// trying to save the form with 'oauth_verifier' still appear
				wp_redirect( $callback_url );
				exit();

			} else {
				$error_message = wp_remote_retrieve_body( $resp );;

				$this->plugin->setting->delete( 'token' );
				$this->plugin->setting->delete( 'token_secret' );

				$message = sprintf(
					'<p><strong>%s:</strong> %s.</p>',
					__( 'Error retrieving access token', 'search-tweets-widget' ),
					$error_message
				);
				$this->redirect_on_error( $callback_url, $message );
			}

		} else if ( isset( $_REQUEST['start_twitter_oauth'] ) && isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], $nonce_key ) ) {

			$parameters = array(
				'x_auth_access_type' => 'read', // We only need to read.
				'oauth_callback'     => $callback_url,
			);

			// Start performing 3-legged OAuth to Twitter.
			$resp   = $client->request( 'POST', 'oauth/request_token', compact( 'parameters' ) );
			$status = wp_remote_retrieve_response_code( $resp );

			if ( 200 === intval( $status ) ) {
				/**
				 * @var array $body
				 */
				wp_parse_str( wp_remote_retrieve_body( $resp ), $body );

				$this->plugin->setting->set( 'tmp_token',        $body['oauth_token'] );
				$this->plugin->setting->set( 'tmp_token_secret', $body['oauth_token_secret'] );

				wp_redirect( sprintf( self::AUTH_URL, $body['oauth_token'] ) );
				exit();

			} else {
				$error_message = wp_remote_retrieve_body( $resp );

				$this->plugin->setting->delete( 'token' );
				$this->plugin->setting->delete( 'token_secret' );

				$message = sprintf(
					'<p><strong>%s:</strong> %s.</p>',
					__( 'Error retrieving request token', 'search-tweets-widget' ),
					$error_message
				);
				$this->redirect_on_error( $callback_url, $message );
			}
		}
	}

	/**
	 * Redirect if error happens. Temporary message is is stored in option.
	 *
	 * @uses `wp_redirect`
	 *
	 * @param string $location Location to redirect
	 * @param string $message  Message to shown in notices bar after redirect
	 *
	 * @return void
	 */
	private function redirect_on_error( $location, $message = '' ) {
		// Stores the error message.
		$this->plugin->setting->set( 'error_message', $message );

		// Redirect it with error key indicator appended in URL.
		$location = add_query_arg( 'got_error', 1, $location );
		wp_redirect( $location );
		exit();
	}

	/**
	 * Check if error indicator appears in query string and hook it into `admin_notices`
	 * action if error needs to be noticed.
	 */
	private function check_error_message() {
		$message = $this->plugin->setting->get( 'error_message' );

		if ( isset( $_REQUEST['got_error'] ) && ! empty( $message ) ) {
			$plugin = $this->plugin;
			add_action( 'admin_notices', function() use( $plugin, $message ) {
				printf( '<div class="error">%s</div>', $message );
				$plugin->setting->delete( 'error_message' );
			} );
		}
	}

	/**
	 * Gets URL to authorize the app.
	 *
	 * @return string URL
	 */
	public function get_link() {
		return add_query_arg(
			array(
				'start_twitter_oauth' => true,
				'nonce'               => wp_create_nonce( $this->get_nonce_key() ),
			),
			admin_url( 'options-general.php?page=' . $this->plugin->name )
		);
	}

	/**
	 * Gets nonce key.
	 *
	 * @return string Nonce key
	 */
	private function get_nonce_key() {
		return $this->plugin->name . '_authorizer_nonce';
	}
}
