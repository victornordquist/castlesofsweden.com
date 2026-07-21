<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<section class="hero" style="background-image: linear-gradient(90deg, rgba(20,17,13,0.8) 0%, rgba(20,17,13,0.45) 80%), url('<?php echo esc_url( COS_THEME_URI . '/assets/images/hero2.jpg' ); ?>');">
	<div class="container">
		<h1>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Hitta din nästa drömdestination', 'cos-theme' );
			} else {
				esc_html_e( 'Find your next dream destination', 'cos-theme' );
			}
			?>
		</h1>
		<p class="hero__intro">
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Utforska Sveriges mest ikoniska slott, palats och herrgårdar. Oavsett om du planerar en weekendresa, drömmer om ett sagolikt bröllop eller följer i kungars och drottningars fotspår, hittar du din nästa oförglömliga destination här.', 'cos-theme' );
			} else {
				esc_html_e( 'Explore Sweden\'s most iconic castles, palaces and manor houses. Whether you\'re planning a weekend escape, dreaming of a fairytale wedding, or following in the footsteps of kings and queens, you\'ll find your next unforgettable destination here.', 'cos-theme' );
			}
			?>
		</p>

		<div class="hero-search" id="hero-search">
			<p class="hero-search__legend"><?php echo esc_html( $is_sv ? 'Vart vill du åka?' : 'Where are you?' ); ?></p>
			<div class="hero-search__where">
				<button type="button" class="button hero-search__near-me" id="hero-near-me" aria-pressed="false">
					<?php cos_pin_icon_svg(); ?>
					<span class="hero-search__near-me-label"><?php echo esc_html( $is_sv ? 'Använd min plats' : 'Use my location' ); ?></span>
				</button>
				<span class="hero-search__or"><?php echo esc_html( $is_sv ? 'eller' : 'or' ); ?></span>
				<select class="button hero-search__region" id="hero-region" name="region">
					<option value=""><?php echo esc_html( $is_sv ? 'Välj landskap' : 'Choose a region' ); ?></option>
					<?php
					$hero_regions = get_terms( array( 'taxonomy' => 'cos_region', 'hide_empty' => false ) );
					foreach ( $hero_regions as $term ) :
						?>
						<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<p class="hero-search__status" id="hero-near-me-status" aria-live="polite" hidden></p>

			<fieldset class="hero-search__categories">
				<legend class="hero-search__legend"><?php echo esc_html( $is_sv ? 'Vad vill du göra?' : 'What would you like to do?' ); ?></legend>
				<div class="hero-search__checkbox-grid">
					<?php
					$hero_categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => false ) );
					foreach ( $hero_categories as $term ) :
						?>
						<label class="hero-search__checkbox">
							<input type="checkbox" name="category[]" value="<?php echo esc_attr( $term->slug ); ?>">
							<span><?php echo esc_html( $term->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<button type="button" class="button hero-search__submit" id="hero-search-submit"><?php echo esc_html( $is_sv ? 'Upptäck destinationer' : 'Discover destinations' ); ?></button>
		</div>
	</div>
</section>

<section class="section section--narrow">
	<div class="container">
		<h2>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Upptäck berättelserna bakom byggnaderna', 'cos-theme' );
			} else {
				esc_html_e( 'Discover the stories behind the buildings', 'cos-theme' );
			}
			?>
		</h2>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'I vårt magasin utforskar vi arkitektur, historia, trädgårdar och interiörer, konst och kultur, kungligheter samt en tidlös livsstil. Upptäck idéerna, historien och människorna som har format, och fortfarande formar, dessa unika kulturmiljöer och fördjupa din förståelse för Sveriges rika kulturarv.', 'cos-theme' );
			} else {
				esc_html_e( 'The Journal explores architecture, history, gardens & interiors, art & culture, royals and timeless lifestyle. Uncover the ideas, histories and people that shaped, and continue to shape, these remarkable landmarks and gain a deeper understanding of Sweden\'s rich heritage.', 'cos-theme' );
			}
			?>
		</p>
		<a class="button" href="<?php echo esc_url( home_url( $is_sv ? '/sv/magasinet/' : '/journal/' ) ); ?>"><?php echo esc_html( $is_sv ? 'Till magasinet' : 'Journal' ); ?></a>
	</div>
</section>

<?php
$latest_articles = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 3,
) );
if ( $latest_articles->have_posts() ) :
	?>
	<div class="front-journal">
		<?php while ( $latest_articles->have_posts() ) : $latest_articles->the_post(); ?>
			<?php cos_front_journal_card( get_the_ID() ); ?>
		<?php endwhile; ?>
	</div>
	<?php
	wp_reset_postdata();
endif;
?>

<?php
$latest_listings = new WP_Query( array(
	'post_type'      => 'cos_listing',
	'posts_per_page' => 4,
	'meta_query'     => array(
		array(
			'relation' => 'OR',
			array( 'key' => 'cos_listing_sold', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'cos_listing_sold', 'value' => '1', 'compare' => '!=' ),
		),
	),
) );
if ( $latest_listings->have_posts() ) :
	?>
	<section class="section section--beige">
		<div class="container">
			<h2>
				<?php echo esc_html( $is_sv ? 'Till salu' : 'For sale' ); ?>
			</h2>
			<div class="card-grid">
				<?php while ( $latest_listings->have_posts() ) : $latest_listings->the_post(); ?>
					<?php cos_listing_card( get_the_ID() ); ?>
				<?php endwhile; ?>
			</div>
			<a class="button" href="<?php echo esc_url( home_url( $is_sv ? '/sv/till-salu/' : '/for-sale/' ) ); ?>"><?php echo esc_html( $is_sv ? 'Se alla objekt' : 'View all listings' ); ?></a>
		</div>
	</section>
	<?php
	wp_reset_postdata();
endif;
?>

<section class="member-section">
	<div class="member-section__image" style="background-image: url('<?php echo esc_url( COS_THEME_URI . '/assets/images/become-a-member.jpg' ); ?>');"></div>
	<div class="member-section__content">
		<h2>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Bli medlem', 'cos-theme' );
			} else {
				esc_html_e( 'Become a member', 'cos-theme' );
			}
			?>
		</h2>
		<p>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Castles of Sweden är ett ideellt och volontärdrivet initiativ som arbetar för att bevara och lyfta fram Sveriges rika kulturarv av slott, herrgårdar och palats. Ditt stöd hjälper oss att fortsätta berätta historierna, skapa inspirerande innehåll och göra dessa unika platser tillgängliga för fler. Varje bidrag gör skillnad.', 'cos-theme' );
			} else {
				esc_html_e( 'Castles of Sweden is a non-profit and volunteer-driven initiative dedicated to preserving and promoting Sweden\'s rich heritage of castles, manors, and palaces. Your support helps us continue to share stories, create meaningful content, and connect more people with these extraordinary places. Every contribution makes a difference.', 'cos-theme' );
			}
			?>
		</p>
		<a class="button" href="<?php echo esc_url( home_url( $is_sv ? '/sv/stod-oss/' : '/support-us/' ) ); ?>"><?php echo esc_html( $is_sv ? 'Bli medlem' : 'Join' ); ?></a>
	</div>
</section>

<?php get_footer(); ?>
