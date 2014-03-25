<?php
/**
 * Widget to render tweets from search results.
 */
class Search_Tweets_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			strtolower( __CLASS__ ),
			__( 'Search tweets widget', 'search-tweets-widget' ),
			array(
				'description' => __( 'Tweets from Twitter Search API.', 'search-tweets-widget' ),
				'classname'   => strtolower( __CLASS__ ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$plugin = $GLOBALS['search_tweets_widget'];

		wp_enqueue_style( $plugin->name, $plugin->css_path . 'widget.css', array(), $plugin->version, 'all' );
		extract( $args );
		/**
		 * @var string $name
		 * @var string $id
		 * @var string $description
		 * @var string $class
		 * @var string $before_widget
		 * @var string $before_title
		 * @var string $widget_id
		 * @var string $widget_name
		 * @var string $after_widget
		 * @var string $after_title
		 */

		$title = apply_filters(
			'search_tweets_widget_title',
			empty( $instance['title'] ) ? __( 'Tweets', 'search-tweets-widget' ) : $instance['title']
		);

		// Specifies the number of records to retrieve
		$instance['count'] = intval( $instance['count'] );
		if ( ! $instance['count'] ) {
			$instance['count'] = 20;
 		}

 		echo $before_widget;
 		echo $before_title . $title . $after_title;

 		$tweets = $this->_get_tweets( $widget_id, $instance );

 		echo $this->_get_view( $tweets, $instance );

 		echo $after_widget;
	}

	/**
	 * Get tweets.
	 *
	 * @param string $widget_id
	 * @param array  $instance
	 */
	private function _get_tweets( $widget_id, array $instance ) {
		$plugin = $GLOBALS['search_tweets_widget'];
		extract( $instance );
		/**
		 * @var string $q
		 * @var int    $count
		 * @var string $until
		 * @var string $account_to_follow
		 */

		return $plugin->transient->get( $widget_id, compact( 'q', 'count', 'until' ) );
	}

	/**
	 * Get view to render.
	 *
	 * @param  object $tweets
	 * @param  array  $instance
	 * @return string
	 */
	private function _get_view( $tweets, $instance ) {
		$plugin = $GLOBALS['search_tweets_widget'];
		$error  = $plugin->setting->get( 'error_message' );

		if ( $error ) {
			$plugin->setting->delete( 'error_message' );
		}

		ob_start();
		require apply_filters( 'search_tweets_widget_view_path_for_widget', $plugin->views_path . 'widget.php' );

		return ob_get_clean();
	}

	public function update( $new_instance, $old_instance ) {
		$plugin = $GLOBALS['search_tweets_widget'];

		$new_instance['title'] = esc_html( $new_instance['title'] );
		$new_instance['q']     = esc_attr( $new_instance['q'] );
		$new_instance['count'] = intval( $new_instance['count'] );
		$new_instance['until'] = esc_attr( $new_instance['until'] );

		$new_instance['account_to_follow'] = esc_attr( $new_instance['account_to_follow'] );

		$plugin->transient->delete( $this->id );

		return $new_instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, array(
			'title'             => '',
			'q'                 => '',
			'count'             => 20,
			'until'             => '',
			'account_to_follow' => '',
		) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>"><?php _e( 'Title', 'search-tweets-widget' ); ?></label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('q') ); ?>"><?php _e( 'Search Query', 'search-tweets-widget' ); ?></label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id('q') ); ?>" name="<?php echo esc_attr( $this->get_field_name('q') ); ?>" value="<?php echo esc_attr( $instance['q'] ); ?>">
			<br>
			<span class="description">
			<?php _e( 'The best way to build a query and test if it is valid and will return matched Tweets is to first try it at <a href="https://twitter.com/search" target="_blank">twitter.com/search</a>.', 'search-tweets-widget' ); ?>
			</span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('count') ); ?>"><?php _e( 'Max Count', 'search-tweets-widget' ); ?></label>
			<input class="widefat" type="number" id="<?php echo esc_attr( $this->get_field_id('count') ); ?>" name="<?php echo esc_attr( $this->get_field_name('count') ); ?>" value="<?php echo esc_attr( $instance['count'] ); ?>">
			<br>
			<span><?php _e( 'The number of tweets to return per page, up to a maximum of 100. Defaults to 20.', 'search-tweets-widget' ); ?></span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('until') ); ?>"><?php _e( 'Until', 'search-tweets-widget' ); ?></label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id('until') ); ?>" name="<?php echo esc_attr( $this->get_field_name('until') ); ?>" value="<?php echo esc_attr( $instance['until'] ); ?>">
			<br>
			<span class="description"><?php _e( 'Returns tweets generated before the given date. Date should be formatted as YYYY-MM-DD. Keep in mind that the search index may not go back as far as the date you specify here.', 'search-tweets-widget' ); ?></span>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('account_to_follow') ); ?>"><?php _e( 'Follow us account', 'search-tweets-widget' ); ?></label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id('account_to_follow') ); ?>" name="<?php echo esc_attr( $this->get_field_name('account_to_follow') ); ?>" value="<?php echo esc_attr( $instance['account_to_follow'] ); ?>">
			<br>
			<span class="description"><?php _e( 'Will be rendered after tweets list as link to the account.', 'search-tweets-widget' ); ?></span>
		</p>
		<?php
	}
}
