<?php
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<section class="hero" style="background-image: linear-gradient(90deg, rgba(30,26,20,0.7) 0%, rgba(30,26,20,0.35) 65%), url('<?php echo esc_url( COS_THEME_URI . '/assets/images/hero.jpg' ); ?>');">
	<div class="container">
		<h1>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Din guide till Sveriges slott, herrgårdar och palats', 'cos-theme' );
			} else {
				esc_html_e( 'Your Guide to Sweden\'s Castles, Manors & Palaces', 'cos-theme' );
			}
			?>
		</h1>
		<p class="hero__intro">
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Planera ditt nästa besök till Sveriges mest ikoniska och natursköna kulturhistoriska besöksmål. Oavsett om du drömmer om en weekendresa, ett sagolikt bröllop eller vill följa i kungars och drottningars fotspår – här finns något för dig.', 'cos-theme' );
			} else {
				esc_html_e( 'Plan your next visit to Sweden\'s most iconic and scenic heritage sites. Whether you are dreaming of a weekend escape, a fairytale wedding, or to follow in the footsteps of kings and queens — there\'s something here for you.', 'cos-theme' );
			}
			?>
		</p>

		<div class="cos-search cos-search--destinations">
			<form class="cos-search__form" action="<?php echo esc_url( home_url( $is_sv ? '/sv/' : '/' ) ); ?>" method="get" role="search">
				<input type="search" name="s" class="cos-search__input" placeholder="<?php echo esc_attr( $is_sv ? 'Hitta din nästa destination...' : 'Find your next destination...' ); ?>" autocomplete="off">
				<button type="submit" class="button"><?php echo esc_html( $is_sv ? 'Sök' : 'Search' ); ?></button>
			</form>
			<div class="cos-search__results" hidden></div>
		</div>

		<div class="hero__actions">
			<a class="button" href="<?php echo esc_url( home_url( $is_sv ? '/sv/karta/' : '/map/' ) ); ?>"><?php echo esc_html( $is_sv ? 'Visa interaktiv karta' : 'View Interactive Map' ); ?></a>
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
				esc_html_e( 'I vårt magasin utforskar vi arkitektur, historia, trädgårdar och interiörer, konst och kultur, kungligheter samt en tidlös livsstil. Upptäck idéerna, historien och människorna som har format – och fortfarande formar – dessa unika kulturmiljöer, och fördjupa din förståelse för Sveriges rika kulturarv.', 'cos-theme' );
			} else {
				esc_html_e( 'The Journal explores architecture, history, gardens & interiors, art & culture, royals and timeless lifestyle. Uncover the ideas, histories and people that shaped and continue to shape these remarkable landmarks — and gain a deeper understanding of Sweden\'s rich heritage.', 'cos-theme' );
			}
			?>
		</p>
		<a class="button" href="<?php echo esc_url( home_url( $is_sv ? '/sv/magasinet/' : '/journal/' ) ); ?>"><?php echo esc_html( $is_sv ? 'Till magasinet' : 'The Journal' ); ?></a>
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

<section class="section section--beige">
	<div class="container">
		<h2>
			<?php
			if ( $is_sv ) {
				esc_html_e( 'Hitta din nästa destination', 'cos-theme' );
			} else {
				esc_html_e( 'Find your next destination', 'cos-theme' );
			}
			?>
		</h2>
		<div class="tile-grid">
			<?php
			$categories = get_terms( array( 'taxonomy' => 'cos_category', 'hide_empty' => true ) );
			foreach ( $categories as $term ) :
				cos_image_tile( $term, cos_tile_image_url( 'categories', cos_term_image_slug( $term ) ) );
			endforeach;
			?>
		</div>
	</div>
</section>

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
