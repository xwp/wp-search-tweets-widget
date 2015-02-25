<?php
/**
 * Plugin Name: Search Tweets Widget
 * Plugin URI: https://github.com/xwp/wp-search-tweets-widget
 * Description: Search for tweets via Twitter search API then makes the results available in widgets
 * Version: 0.1.0
 * Author: XWP, Akeda Bagus
 * Author URI: https://xwp.co/
 * Text Domain: search-tweets-widget
 * Domain Path: /languages
 * License: GPL v2 or later
 * Requires at least: 3.6
 * Tested up to: 3.8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

require_once __DIR__ . '/includes/autoloader.php';

// Register the autoloader.
Search_Tweets_Widget_Autoloader::register( 'Search_Tweets_Widget', trailingslashit( plugin_dir_path( __FILE__ ) ) . '/includes/' );

// Runs this plugin.
$GLOBALS['search_tweets_widget'] = new Search_Tweets_Widget_Plugin();
$GLOBALS['search_tweets_widget']->run( __FILE__ );
