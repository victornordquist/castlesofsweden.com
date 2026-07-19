</main>

<section class="newsletter-signup">
	<div class="container">
		<h2><?php esc_html_e( 'Sign up to hear more from Castles of Sweden', 'cos-theme' ); ?></h2>

		<?php if ( isset( $_GET['cos_subscribe'] ) ) : ?>
			<?php if ( 'success' === $_GET['cos_subscribe'] ) : ?>
				<p class="newsletter-signup__message newsletter-signup__message--success"><?php esc_html_e( 'Thanks for signing up!', 'cos-theme' ); ?></p>
			<?php elseif ( 'invalid' === $_GET['cos_subscribe'] ) : ?>
				<p class="newsletter-signup__message newsletter-signup__message--error"><?php esc_html_e( 'Please enter a valid email address.', 'cos-theme' ); ?></p>
			<?php else : ?>
				<p class="newsletter-signup__message newsletter-signup__message--error"><?php esc_html_e( 'Something went wrong. Please try again.', 'cos-theme' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>

		<form class="newsletter-signup__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="cos_subscribe">
			<?php wp_nonce_field( 'cos_subscribe', 'cos_subscribe_nonce' ); ?>
			<input type="text" name="cos_website" class="newsletter-signup__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
			<input type="email" name="cos_email" required placeholder="<?php esc_attr_e( 'Your email address', 'cos-theme' ); ?>" class="newsletter-signup__input">
			<button type="submit" class="button"><?php esc_html_e( 'Subscribe', 'cos-theme' ); ?></button>
		</form>

		<p class="newsletter-signup__legal">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: privacy policy link */
					__( 'By sharing your email address you\'re agreeing to receive marketing emails from Castles of Sweden and confirm you\'re 18 years old or over. Please see our %s for more information on how we look after your personal data.', 'cos-theme' ),
					'<a href="' . esc_url( get_privacy_policy_url() ) . '">' . esc_html__( 'Privacy policy', 'cos-theme' ) . '</a>'
				),
				array( 'a' => array( 'href' => array() ) )
			);
			?>
		</p>
	</div>
</section>

<footer class="site-footer">
	<div class="container">
		<div class="footer-columns">
			<div>
				<h3><?php bloginfo( 'name' ); ?></h3>
				<p><?php esc_html_e( 'Your Guide to Sweden\'s Castles, Manors & Palaces.', 'cos-theme' ); ?></p>
			</div>
			<div>
				<h3><?php esc_html_e( 'Explore', 'cos-theme' ); ?></h3>
				<ul>
					<?php foreach ( cos_primary_nav_links() as $label => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h3><?php esc_html_e( 'Follow', 'cos-theme' ); ?></h3>
				<ul class="social-links">
					<li>
						<a href="https://www.instagram.com/castlesofsweden/" target="_blank" rel="noopener">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
							<?php esc_html_e( 'Instagram', 'cos-theme' ); ?>
						</a>
					</li>
					<li>
						<a href="https://www.facebook.com/castlesofsweden/" target="_blank" rel="noopener">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
							<?php esc_html_e( 'Facebook', 'cos-theme' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<div>
				<h3><?php esc_html_e( 'Support Us', 'cos-theme' ); ?></h3>
				<p><a href="<?php echo esc_url( home_url( '/support-us/' ) ); ?>"><?php esc_html_e( 'Learn how you can help preserve Sweden\'s heritage', 'cos-theme' ); ?></a></p>
			</div>
		</div>
		<div class="footer-bottom">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
