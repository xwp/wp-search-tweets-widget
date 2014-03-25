<?php
/**
 * This is the plugin class and acts as container for component instances and
 * basic properties of a plugin. Using container like this will avoid poluting
 * global namespaces. There's no global constants and only one global object
 * defined, that's this class' instance, and it's used by widget class.
 */
class Search_Tweets_Widget_Plugin {

	/**
	 * @var array
	 */
	private $items = array();

	public function run( $path ) {
		// Basic plugin information.
		$this->name    = 'search_tweets_widget'; // This maybe used to prefix options, slug of menu or page, and filters/actions.
		$this->version = '0.1.0';

		// Path.
		$this->plugin_path   = trailingslashit( plugin_dir_path( $path ) );
		$this->plugin_url    = trailingslashit( plugin_dir_url( $path ) );
		$this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
		$this->views_path    = $this->plugin_path . trailingslashit( 'views' );
		$this->css_path      = $this->plugin_url  . trailingslashit( 'css' );

		// Instances.
		$this->setting    = new Search_Tweets_Widget_Setting( $this );
		$this->client     = new Search_Tweets_Widget_Client( $this );
		$this->searcher   = new Search_Tweets_Widget_Searcher( $this );
		$this->rate_limit = new Search_Tweets_Widget_Rate_Limit( $this );
		$this->transient  = new Search_Tweets_Widget_Transient( $this );
		$this->cron       = new Search_Tweets_Widget_Cron( $this );
		$this->authorizer = new Search_Tweets_Widget_Authorizer( $this );

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the widget.
	 *
	 * @action widgets_init
	 */
	public function register_widget() {
		register_widget( 'Search_Tweets_Widget' );
	}

	/**
	 * Get active widgets.
	 *
	 * @return mixed
	 */
	public function get_active_widgets() {
		return get_option( 'widget_search_tweets_widget' );
	}

	public function __set( $key, $value ) {
		$this->items[ $key ] = $value;
	}

	public function __get( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			return $this->items[ $key ];
		}

		return null;
	}

	public function __isset( $key ) {
		return isset( $this->items[ $key ] );
	}

	public function __unset( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			unset( $this->items[ $key ], $this->raws[ $key ], $this->frozen[ $key ] );
		}
	}

}
