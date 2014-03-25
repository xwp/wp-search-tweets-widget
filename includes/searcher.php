<?php
/**
 * As the name suggests, this class' job is to search tweets.
 */
class Search_Tweets_Widget_Searcher {

	private $plugin;

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Given a search params, search the tweets.
	 *
	 * @throws Exception
	 *
	 * @param  array $params
	 * @return mixed
	 * @link   https://dev.twitter.com/docs/api/1.1/get/search/tweets
	 */
	public function search( array $params ) {
		$client = $this->plugin->client;

		if ( ! isset( $params['q'] ) ) {
			return null;
		}

		if ( empty( $params['q'] ) ) {
			return null;
		}

		$token        = $this->plugin->setting->get( 'token' );
		$token_secret = $this->plugin->setting->get( 'token_secret' );

		// Gets the result in JSON.
		$format = 'json';

		// Parameters to send in request to endpoint.
		$parameters = array(
			'oauth_token'        => $token,
			'oauth_token_secret' => $token_secret,
		);

		// Combines supplied params with token params.
		foreach ( $params as $key => $value ) {
			if ( ! empty( $value ) ) {
				$parameters[ $key ] = $value;
			}
		}

		// Makes a request.
		$resp = $client->request( 'GET', 'search/tweets', compact( 'parameters', 'format' ) );

		if ( 200 === intval( wp_remote_retrieve_response_code( $resp ) ) ) {
			$tweets = json_decode( wp_remote_retrieve_body( $resp ) );

			$self   = $this;
			$tweets = array_map(
				function( $tweet ) use( $self ) {
					$tweet->text_formatted = $self->format_tweet( $tweet );
					return $tweet;
				},
				$tweets->statuses
			);

			return (object) array(
				'timestamp' => time(),
				'tweets'    => $tweets,
			);
		} else {
			$message = 'Unexpected result when tried to call Twitter "search/tweets" endpoint. ';
			$resp    = (array) json_decode( wp_remote_retrieve_body( $resp ) );

			if ( isset( $resp['errors'] ) && is_array( $resp['errors'] ) ) {
				$message .= implode( '. ', wp_list_pluck( $resp['errors'], 'message' ) );
			}
			$message = rtrim( $message );

			throw new Exception( $message );
		}

		return null;
	}

	/**
	 * Formats a single tweet so that URLs, hashtags, and mentions are clickable.
	 *
	 * @param  object $tweet
	 * @return string
	 */
	public function format_tweet( $tweet ) {
		$entities = $tweet->entities;
		$content = esc_html( $tweet->text );

		// Makes URLs clickable.
		if ( ! empty( $entities->urls ) ) {
			foreach ( $entities->urls as $url ) {
				$content = str_ireplace(
					$url->url,
					'<a href="' . esc_url( $url->expanded_url ) . '" target="_blank">' . esc_html( $url->display_url ) . '</a>',
					$content
				);
			}
		}

		// Makes hashtags clickable.
		if ( ! empty( $entities->hashtags ) ) {
			foreach ( $entities->hashtags as $hashtag ) {
				$url     = 'http://search.twitter.com/search?q=' . urlencode( $hashtag->text );
				$content = str_ireplace(
					'#' . $hashtag->text,
					'<a href="' . esc_url( $url ) . '" target="_blank">#' . esc_html( $hashtag->text ) . '</a>',
					$content
				);
			}
		}

		// Makes mentions clickable.
		if ( ! empty( $entities->user_mentions ) ) {
			foreach ( $entities->user_mentions as $user ) {
				$url     = 'http://twitter.com/' . urlencode( $user->screen_name );
				$content = str_ireplace(
					'@' . $user->screen_name,
					'<a href="' . esc_url( $url ) . '" target="_blank">@' . esc_html( $user->screen_name ) .'</a>',
					$content
				);
			}
		}

		return $content;
	}
}
