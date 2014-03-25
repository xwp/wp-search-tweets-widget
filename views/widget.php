<?php if ( $tweets ) : ?>
	<ul class="tweets">
	<?php foreach ( $tweets->tweets as $tweet ) : ?>
		<li class="tweet">
			<img src="<?php echo esc_url( $tweet->user->profile_image_url ) ?>" alt="" />
			<div class="author">
				<?php echo esc_html( $tweet->user->name ) ?>
				<a class="account" href="<?php echo esc_url( "http://twitter.com/{$tweet->user->screen_name}" ) ?>" target="_blank">
					@<?php echo esc_html( $tweet->user->screen_name ) ?>
				</a>
			</div>
			<div class="text">
				<?php echo wpautop( $tweet->text_formatted ) // xss ok ?>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>

	<?php if ( $instance['account_to_follow'] ) : ?>
	<a class="follow" href="<?php echo esc_url("https://twitter.com/intent/user?screen_name={$instance['account_to_follow']}") ?>" target="_blank"><?php printf( __( 'Follow @%s on Twitter', 'search-tweets-widget' ), $instance['account_to_follow'] ) ?></a>
	<?php endif; ?>

<?php else: ?>
	<?php if ( $error ) : ?>
		<p><?php echo esc_html( $error ); ?></p>
	<?php else: ?>
		<p><?php _e( 'No tweets matching your search query', 'search-tweets-widget' ); ?></p>
	<?php endif; ?>
<?php endif; ?>
