<?php
/**
 * Plugin settings. Responsible for rendering setting page and
 * exposing public method for other compoent to retrieve, update,
 * and delete settings used on plugin.
 */
class Search_Tweets_Widget_Setting {

	/**
	 * @var Search_Tweets_Widget_Plugin
	 */
	private $plugin;

	/**
	 * Capability needed to access setting page.
	 *
	 * @var setting
	 */
	private $cap = 'manage_options';

	/**
	 * Fields to render inside setting page.
	 * Top structure array should be:
	 *
	 * ```
	 * array(
	 *   'setting_section' => array( // fields... ),
	 *   ...
	 * )
	 * ```
	 *
	 * Array of fields on each section:
	 *
	 * ```
	 * array(
	 *   'setting_field_id' => array( // field properties... ),
	 *   ...
	 * )
	 * ```
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Sections available on this setting page. This provides
	 * a map to get properties (title, callback, etc) of a section
	 * so that when iterating `self::$fields` a parameter for
	 * `add_settings_section` can be retrieved.
	 *
	 * @var array
	 */
	private $sections = array();

	/**
	 * Stores temporary value that lives in one request.
	 *
	 * @var array
	 */
	private $tmp = array();

	public function __construct( Search_Tweets_Widget_Plugin $plugin ) {
		$this->plugin = $plugin;

		// Sets section properties.
		$this->sections = array(
			'app' => array(
				'title'    => __( 'Twitter App', 'search-tweets-widget' ),
				'callback' => null,
			),
			'stats' => array(
				'title'    => __( 'Status', 'search-tweets-widget' ),
				'callback' => null,
			),
			'other' => array(
				'title'    => __( 'Other', 'search-tweets-widget' ),
				'callback' => null,
			),
		);

		// Sets fields. These fields always prefixed with plugin's name,
		// but to retrieve the value via get method below no need to
		// prefix the key.
		$this->fields = array(
			'app' => array(
				'consumer_key' => array(
					'title'   => __( 'Consumer Key', 'search-tweets-widget' ),
					'type'    => 'text',
					'pattern' => '[0-9A-Za-z]+',
					'desc'    => __( 'App API key.', 'search-tweets-widget' ),
				),
				'consumer_secret' => array(
					'title'   => __( 'Consumer Secret', 'search-tweets-widget' ),
					'type'    => 'text',
					'pattern' => '[0-9A-Za-z]+',
					'desc'    => __( 'App API key secret.', 'search-tweets-widget' ),
				),
				'authorize_link' => array(
					'title'   => __( 'Authorize', 'search-tweets-widget' ),
					'type'    => 'authorize',
				),
			),
			'stats' => array(
				'limit' => array(
					'title'     => __( 'API Call Limit', 'search-tweets-widget' ),
					'type'      => 'limit',
					'renderer'  => array( $this, 'render_field_rate_limit' ),
					'desc'      => __( 'Rate limit for Twitter search API call.', 'search-tweets-widget' ),
				),
				'remaining' => array(
					'title'     => __( 'Remaining API Call', 'search-tweets-widget' ),
					'type'      => 'remaining',
					'renderer'  => array( $this, 'render_field_rate_limit' ),
					'desc'      => __( 'How much API call can be performed until reset.', 'search-tweets-widget' ),
				),
				'reset' => array(
					'title'     => __( 'Rate Limit Reset', 'search-tweets-widget' ),
					'type'      => 'reset',
					'renderer'  => array( $this, 'render_field_rate_limit' ),
					'desc'      => __( 'When rate limit will expire.', 'search-tweets-widget' ),
				),
			),
			'other' => array(
				'schedule'  => array(
					'title'     => __( 'Update Interval', 'search-tweets-widget' ),
					'type'      => 'select',
					'options'   => array_combine(
						array_keys( wp_get_schedules() ),
						wp_list_pluck( wp_get_schedules(), 'display' )
					),
					'sanitizer' => array( $this, 'sanitize_schedule' ),
					'desc'      => __( 'How often to update the search results.', 'search-tweets-widget' ),
				),
			),
		);

		add_action( 'admin_menu',    array( $this, 'create_setting_menu' ) );
		add_action( 'admin_notices', array( $this, 'settings_errors' ) );
		add_action( 'admin_notices', array( $this, 'notice_to_connect_twitter' ) );
		add_action( 'admin_init',    array( $this, 'register_settings' ) );

		add_action( 'load-settings_page_' . $this->plugin->name, array( $this, 'settings_page_load' ) );
	}

	/**
	 * Create menu and page for plugin setting.
	 *
	 * @action admin_menu
	 */
	public function create_setting_menu() {
		add_options_page(
			__( 'Search Tweets Widget Settings', 'search-tweets-widget' ),
			__( 'Search Tweets Widget',          'search-tweets-widget' ),

			// Cap to edit the settings.
			$this->cap,

			// Menus slug.
			$this->plugin->name,

			// Page setting renderer.
			array( $this, 'render_setting_page' )
		);
	}

	/**
	 * Shows error as admin notices if error occurs from setting options.
	 *
	 * @action admin_notices
	 */
	public function settings_errors() {
		settings_errors( $this->plugin->name );
	}

	/**
	 * Shows error as admin notices if no access token and its secret.
	 *
	 * @action admin_notices
	 */
	public function notice_to_connect_twitter() {
		if ( ! $this->has_token() ) {
			printf( '<div class="error"><p><strong>%s</strong></p></div>', sprintf( __( 'Search Tweets Widget needs access token to operate. Set it via <a href="%s">settings page</a> by authorizing the app.', 'search-tweets-widget' ), admin_url( 'options-general.php?page=' . $this->plugin->name ) ) );
		}
	}

	/**
	 * Register settings for setting page.
	 *
	 * @action admin_init
	 * @return void
	 */
	public function register_settings() {
		// Provides hook to override setting sections and fields right
		// before rendering.
		$this->fields   = apply_filters( 'search_tweets_widget_setting_fields',   $this->fields );
		$this->sections = apply_filters( 'search_tweets_widget_setting_sections', $this->sections );

		foreach ( $this->fields as $section => $fields ) {
			// Section rendering.
			if ( isset( $this->sections[ $section ] ) ) {
				$section_props = $this->sections[ $section ];
				if ( empty( $section_props['page'] ) ) {
					$section_props['page'] = $this->plugin->name;
				}
				add_settings_section( $section, $section_props['title'], $section_props['callback'], $section_props['page'] );
			}

			// Render fields in current section.
			foreach ( $fields as $field => $properties ) {
				$field_id    = $this->plugin->name . '_' . $field;
				$field_args  = array(
					'name'        => $field_id,
					'id'          => $field_id,
					'key'         => $field_id,
					'label_for'   => $field_id,
					'type'        => isset( $properties['type'] ) ? $properties['type'] : '',
					'desc'        => isset( $properties['desc'] ) ? $properties['desc'] : '',
					'value'       => get_option( $field_id ),
					'pattern'     => isset( $properties['pattern'] ) ? $properties['pattern'] : '',
					'placeholder' => '',
				);

				// Default field renderer available from this class.
				$field_renderer = array( $this, 'render_field_' . $properties['type'] );

				// Set options for a select field.
				if ( 'select' === $properties['type'] ) {
					$field_args['options'] = (
						isset( $properties['options'] )
						&&
						is_array( $properties['options'] )
					) ? $properties['options'] : array();
				}

				// If field has it own renderer, use it.
				if ( isset( $properties['renderer'] ) && is_callable( $properties['renderer'] ) ) {
					$field_renderer = $properties['renderer'];
				}

				// Check field sanitizer.
				if ( isset( $properties['sanitizer'] ) && is_callable( $properties['sanitizer'] ) ) {
					$sanitizer = $properties['sanitizer'];
				} else if ( ! empty( $field_args['pattern'] ) ) {
					$pattern   = $field_args['pattern'];
					$sanitizer = function( $value ) use( $pattern ) {
						$regex = '#^(' . $pattern . ')$#';
						if ( ! preg_match( $regex, $value ) ) {
							$value = '';
						}
						return $value;
					};
				} else {
					$sanitizer = 'sanitize_text_field';
				}

				// WordPress settings API.
				add_settings_field( $field_id, $properties['title'], $field_renderer, $this->plugin->name, $section, $field_args );
				add_option( $field_id );
				register_setting( $this->plugin->name, $field_id, $sanitizer );
			}
		}
	}

	/**
	 * When settings page loads.
	 *
	 * @action load-{setting_page}
	 */
	public function settings_page_load() {

		// Gets Twitter API call rate limit to supply 'rate_limit' fields renderer.
		// Will notifify, via admin_notices, if expected response is not returned.
		try {
			$this->tmp['rate_limit'] = $this->plugin->rate_limit->get();
		} catch ( Exception $e ) {
			// Only shows the notice if already connected to Twitter.
			if ( $this->has_token() ) {
				add_action( 'admin_notices', function() use( $e ) {
					printf( '<div class="error"><p><strong>%s</strong></p></div>', $e->getMessage() );
				} );
			}
		}
	}

	/**
	 * Has access token and access token secret. In other words, has authorization
	 * to Twitter app has been performed successfully?
	 *
	 * @return bool
	 */
	public function has_token() {
		return (
			$this->get( 'token' )
			&&
			$this->get( 'token_secret' )
		);
	}

	/**
	 * Gets a single setting.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function get( $key ) {
		if ( false === strpos( $key, $this->plugin->name . '_' ) ) {
			$key = $this->plugin->name . '_' . $key;
		}
		$value  = get_option( $key );
		if ( empty( $value ) ) {
			$value = apply_filters( "default_option_{$key}", $value );
		}

		return $value;
	}

	/**
	 * Sets a single setting. A key doesn't neeed
	 * to have association with renderer fields.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set( $key, $value ) {
		if ( false === strpos( $key, $this->plugin->name . '_' ) ) {
			$key = $this->plugin->name . '_' . $key;
		}
		update_option( $key, $value );
	}

	/**
	 * Deletes a single setting.
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		if ( false === strpos( $key, $this->plugin->name . '_' ) ) {
			$key = $this->plugin->name . '_' . $key;
		}
		delete_option( $key );
	}

	/**
	 * Setting page renderer.
	 *
	 * @return void
	 */
	public function render_setting_page() {
		if ( ! current_user_can( $this->cap ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'search-tweets-widget' ) );
		}

		// Declare vars to make it available on view.
		$title  = __( 'Search Tweets Widget Settings', 'search-tweets-widget' );
		$plugin = $this->plugin->name;

		require $this->plugin->views_path . 'setting.php';
	}

	/**
	 * Callback for `add_settings_field` where field's type is text.
	 *
	 * @param  array $args Field args passed to `add_settings_field` call
	 * @return void
	 */
	public function render_field_text( $args ) {
		extract( wp_parse_args( $args, array(
			'type'        => 'text',
			'pattern'     => '',
			'placeholder' => '',
		) ) );
		/**
		 * @var string $type
		 * @var string $pattern
		 * @var string $placeholder
		 * @var string $name
		 * @var string $id
		 * @var string $value
		 * @var string $desc
		 */
		$placeholder = apply_filters( "default_option_{$name}", '' );
		?>
		<input
			type="<?php echo esc_attr( $type ) ?>"
			name="<?php echo esc_attr( $name ) ?>"
			id="<?php echo esc_attr( $id ) ?>"
			class="regular-text"
			<?php if ( $pattern ): ?>
				pattern="<?php echo esc_attr( $pattern ) ?>"
			<?php endif; ?>
			placeholder="<?php echo esc_attr( $placeholder ) ?>"
			value="<?php echo esc_attr( $value ); ?>">
		<?php if ( $desc ): ?>
			<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif;
	}

	/**
	 * Callback for `add_settings_field` where field's type is hidden.
	 *
	 * @param  array $args Field args passed to `add_settings_field` call
	 * @return void
	 */
	public function render_field_hidden( $args ) {
		extract( wp_parse_args( $args, array(
			'type' => 'hidden',
		) ) );
		/**
		 * @var string $type
		 * @var string $name
		 * @var string $id
		 * @var string $value
		 */
		$placeholder = apply_filters( "default_option_{$name}", '' );
		?>
		<input
			type="<?php echo esc_attr( $type ) ?>"
			name="<?php echo esc_attr( $name ) ?>"
			id="<?php echo esc_attr( $id ) ?>"
			value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/**
	 * Callback for `add_settings_field` where field's type is select.
	 *
	 * @param array $args Field args passed to `add_settings_field` call
	 */
	public static function render_field_select( $args ) {
		extract( wp_parse_args( $args, array(
			'options' => array(),
		) ) );
		/**
		 * @var array $options
		 * @var string $id
		 * @var string $name
		 * @var string $value
		 * @var string $desc
		 */
		if ( ! isset( $args['options'] ) ) {
			$options = array();
		}

		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $options as $option_val => $option_label ): ?>
				<option value="<?php echo esc_attr( $option_val ); ?>" <?php selected( $option_val, $value ); ?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( $desc ): ?>
		<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif;
	}

	/**
	 * Callback for `add_settings_field` where field's type is rate_limit.
	 *
	 * @param array $args Field args passed to `add_settings_field` call
	 */
	public function render_field_rate_limit( $args ) {
		$rate_limit = isset( $this->tmp['rate_limit'] ) ? $this->tmp['rate_limit'] : array();
		if ( isset( $rate_limit[ $args['type'] ] ) ) {
			$args['value'] = $rate_limit[ $args['type'] ];
		}

		extract( $args );
		/**
		 * @var string $id
		 * @var string $name
		 * @var string $type
		 * @var string $value
		 * @var string $desc
		 */

		if ( $value ) {
			if ( 'reset' === $type ) {
				printf( '<strong>%s</strong>', esc_html( date( 'Y-m-d H:i:s', $value ) ) );
			} else {
				printf( '<strong>%s</strong>', esc_html( $value ) );
			}
		} else {
			printf( '<strong>%s</strong>', __( 'Unknown', 'search-tweets-widget' ) );
		}

		$args['type'] = 'hidden';
		$this->render_field_hidden( $args );

		?>
		<?php if ( $desc ): ?>
		<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif;
	}

	public function render_field_authorize() {
		?>
		<a href="<?php echo esc_url( $this->plugin->authorizer->get_link() ); ?>" class="button"><?php _e( 'Connect to Twitter', 'search-tweets-widget' ); ?></a>
		&nbsp;
		<?php if ( $this->has_token() ): ?>
			<span style="color: green"><?php _e( '✔ Authorized', 'search-tweets-widget' ); ?></span>
		<?php else: ?>
			<span style="color: red"><?php _e( '✘ Not Authorized', 'search-tweets-widget' ); ?></span>
		<?php endif; ?>
		<p class="description"><?php  _e( 'Clicking this button will redirect you to twitter and ask you to authorize the app. The text on the right of the button indicates whether authorization has been performed or not.', 'search-tweets-widget' ); ?></p>
		<?php
	}

	/**
	 * Sanitizer for schedule field.
	 *
	 * @param  string $value
	 * @return string
	 */
	public function sanitize_schedule( $value ) {
		if ( ! in_array( $value, array_keys( wp_get_schedules() ) ) ) {
			$value = 'hourly';
		}

		do_action( $this->plugin->name . '_after_schedule_sanitized', $value );

		return $value;
	}
}
