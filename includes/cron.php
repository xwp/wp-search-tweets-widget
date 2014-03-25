<?php
class Search_Tweets_Widget_Cron {

	/**
	 * @var Search_Tweets_Widget
	 */
	private $plugin;

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;

		// Schedule hook.
		add_action( $this->plugin->name . '_update_tweets', array( $this, 'update_tweets' ) );

		// After updating schedule setting, re-setup cron.
		add_action( $this->plugin->name . '_after_schedule_sanitized', array( $this, 'setup_cron' ) );
	}

	/**
	 * Update tweets on transient.
	 */
	public function update_tweets() {
		$widgets = $this->plugin->get_active_widgets();
		foreach ( $widgets as $num => $instance ) {
			$has_query = (
				isset( $instance['q'] )
				&&
				! empty( $instance['q'] )
			);

			if ( ! $has_query ) {
				continue;
			}

			$id = "search_tweets_widget_-{$num}";

			$this->plugin->transient->delete( $id );
			$this->plugin->transient->get( $id, array(
				'q'     => $instance['q'],
				'count' => $instance['count'],
			) );
		}
	}

	/**
	 * Setup WP Cron that query Twitter Search API.
	 *
	 * @param array $value
	 */
	public function setup_cron( $value ) {
		$schedule_key = $this->plugin->name . '_update_tweets';
		$old_interval = $this->plugin->setting->get( $schedule_key );
		$old_schedule = wp_next_scheduled( $schedule_key );

		// Clear previous schedule.
		if ( $old_schedule ) {
			wp_unschedule_event( $old_schedule, $schedule_key );
		}

		if ( $this->plugin->setting->has_token() ) {
			wp_schedule_event( time(), $value, $schedule_key );
		}
	}
}
