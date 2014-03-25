<?php
/**
 * Transient wrapper for tweets object.
 */
class Search_Tweets_Widget_Transient {

	/**
	 * Plugin instance.
	 *
	 * @var Search_Tweets_Widget_Plugin
	 */
	private $plugin;

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * @param string $id
	 * @param mixed  $value
	 */
	public function set( $id, $value ) {
		set_transient( $id, $value );
	}

	/**
	 * @param string $id     Maybe widget id, but can be any id or key to be stored as suffix key's transient
	 * @param array  $params Params to be passed to searcher
	 * @return mixed
	 */
	public function get( $id, array $params = array() ) {
		$tweets = get_transient( $id );

		$is_valid = (
			! empty( $tweets->timestamp )
			&&
			( time() - absint( $tweets->timestamp ) < ( 60 * 60 * 24 ) ) // Makes sure tweets are fresh enough.
		);

		if ( ! $is_valid ) {
			try {
				$tweets = $this->plugin->searcher->search( $params );
				$this->set( $id, $tweets );
			} catch ( Exception $e ) {
				$this->plugin->setting->set( 'error_message', $e->getMessage() );
			}
		}

		if ( ! $tweets ) {
			return null;
		}

		return $tweets;
	}

	/**
	 * @param string $id
	 */
	public function delete( $id ) {
		delete_transient( $id );
	}
}
