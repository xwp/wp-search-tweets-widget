=== Search Tweets Widget ===
Contributors:      akeda, westonruter, xwp
Tags:              twitter, search, widget
Requires at least: 3.6
Tested up to:      3.8.1
Stable tag:        trunk
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Provides Twitter query search, like `from:WordPress, OR from:photomatt`, and gets the search results rendered as a widget.

== Description ==

Provides Twitter query search, like `from:WordPress, OR from:photomatt`, and gets the search results rendered as a widget. The results will get updated frequently.

**Development of this plugin is done on [GitHub](https://github.com/xwp/wp-search-tweets-widget). Pull requests are always welcome**.

== Installation ==

1. Upload **Search Tweets Widget** plugin to your blog's `wp-content/plugins/` directory and activate.
2. Go to **Settings** > **Search Tweets Widget** to fill your consumer key and consumer secret key. You need to authorize the app afterward.

= How to use =

1. Go to **Appearance** > **Widgets**
1. Drag **Search tweets widget** from **Available Widgets** to available widget area
1. Fill **Search Query** in widget form, something like `from:WordPress, OR from:photomatt`
1. For more query operators, check <a href="https://dev.twitter.com/docs/using-search">the doc</a>

== Frequently Asked Questions ==

= How do I change widget's markup? =

There's a filter `search_tweets_widget_view_path_for_widget` where you can override view path. Please take a look at `views/widget.php` for reference.

== Screenshots ==

1. Settings page
2. Widget form
3. Rendered widget

== Changelog ==

= 0.1.0 =
Initial release
