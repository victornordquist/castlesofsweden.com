<?php get_header(); ?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php the_title(); ?></h1>
	</div>
</div>

<div class="container section support-us">
	<p><?php esc_html_e( 'Castles of Sweden is a non-profit, volunteer-driven initiative dedicated to preserving and promoting Sweden\'s rich heritage of castles, manors, and palaces. Together with our members, partners, and supporters, we help connect people with these extraordinary places through knowledge, storytelling, and collaboration.', 'cos-theme' ); ?></p>

	<p><?php esc_html_e( 'Your support enables us to:', 'cos-theme' ); ?></p>

	<ul class="support-list">
		<li><?php esc_html_e( 'Maintain and expand our interactive directory and map', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Create high-quality educational content on history, architecture, and cultural heritage', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Visit, document, and showcase castles, manors, and palaces across Sweden', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Support research and collaborations with heritage professionals and institutions', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Build a knowledge network that connects heritage sites and encourages the exchange of ideas and best practices', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Organize member events, workshops, and networking opportunities', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Keep the platform accessible to everyone', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Cover essential operating costs, including web hosting, design tools, and outreach', 'cos-theme' ); ?></li>
	</ul>

	<h2><?php esc_html_e( 'Become a Member', 'cos-theme' ); ?></h2>

	<p><?php esc_html_e( 'Membership is open to both individuals and heritage properties.', 'cos-theme' ); ?></p>

	<p>
		<strong><?php esc_html_e( 'For individuals', 'cos-theme' ); ?></strong>,
		<?php esc_html_e( 'membership is a way to support the preservation and promotion of Sweden\'s historic castles, manors, and palaces while helping our community grow.', 'cos-theme' ); ?>
	</p>

	<p>
		<strong><?php esc_html_e( 'For castles, manors, palaces, and other heritage businesses', 'cos-theme' ); ?></strong>,
		<?php esc_html_e( 'membership offers opportunities to:', 'cos-theme' ); ?>
	</p>

	<ul class="support-list">
		<li><?php esc_html_e( 'Join a national network of heritage destinations', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Exchange knowledge and experience with peers', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Participate in member events and workshops', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Increase visibility through Castles of Sweden', 'cos-theme' ); ?></li>
		<li><?php esc_html_e( 'Contribute to strengthening and promoting Sweden\'s shared cultural heritage', 'cos-theme' ); ?></li>
	</ul>

	<div class="support-us__actions">
		<div class="support-us__action">
			<h3><?php esc_html_e( 'Donate', 'cos-theme' ); ?></h3>
			<p><?php esc_html_e( 'Help us continue our work with a one-time or recurring contribution.', 'cos-theme' ); ?></p>
			<a class="button" href="https://ko-fi.com/castlesofsweden" target="_blank" rel="noopener"><?php esc_html_e( 'Donate', 'cos-theme' ); ?></a>
		</div>
		<div class="support-us__action">
			<h3><?php esc_html_e( 'Become a Member', 'cos-theme' ); ?></h3>
			<p><?php esc_html_e( 'Become part of the Association of Swedish Castles and Manors and help shape the future of Sweden\'s historic heritage.', 'cos-theme' ); ?></p>
			<?php $membership_product = get_page_by_path( 'individual-membership', OBJECT, 'product' ); ?>
			<a class="button" href="<?php echo esc_url( $membership_product ? get_permalink( $membership_product ) : '#' ); ?>"><?php esc_html_e( 'Become a Member', 'cos-theme' ); ?></a>
		</div>
	</div>
</div>

<?php get_footer(); ?>
