<?php
/**
 * Gets rate limit informations of a given token.
 */
class Search_Tweets_Widget_Rate_Limit {

	/**
	 * @var Search_Tweets_Widget_Plugin
	 */
	private $plugin;

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Gets rate limit.
	 *
	 * Exception is not caught.
	 */
	public function get() {
		$client = $this->plugin->client;

		$token        = $this->plugin->setting->get( 'token' );
		$token_secret = $this->plugin->setting->get( 'token_secret' );

		// Gets the result in JSON.
		$format = 'json';

		// Parameters to send in request to endpoint.
		$parameters = array(
			'oauth_token'        => $token,
			'oauth_token_secret' => $token_secret,
			'resources'          => 'search',
		);

		// Makes a request.
		$resp = $client->request( 'GET', 'application/rate_limit_status', compact( 'parameters', 'format' ) );

		if ( 200 === intval( wp_remote_retrieve_response_code( $resp ) ) ) {
			$rate_limit = (array) json_decode( wp_remote_retrieve_body( $resp ), true );

			return array(
				'remaining' => $rate_limit['resources']['search']['/search/tweets']['remaining'],
				'limit'     => $rate_limit['resources']['search']['/search/tweets']['limit'],
				'reset'     => $rate_limit['resources']['search']['/search/tweets']['reset'],
			);
		} else {
			$message = 'Unexpected result when tried to call Twitter "application/rate_limit_status" endpoint. ';
			$resp    = (array) json_decode( wp_remote_retrieve_body( $resp ) );

			if ( isset( $resp['errors'] ) && is_array( $resp['errors'] ) ) {
				$message .= implode( '. ', wp_list_pluck( $resp['errors'], 'message' ) );
			}
			$message = rtrim( $message ) . '.';

			throw new Exception( $message );
		}

		return null;
	}
}
