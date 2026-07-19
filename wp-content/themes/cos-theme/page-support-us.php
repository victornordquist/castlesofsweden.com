<?php
/**
 * Template Name: Support Us
 */
get_header();
$is_sv = 'sv' === COS_Language_Routing::current_lang();
?>

<div class="page-title-bar">
	<div class="container">
		<h1><?php the_title(); ?></h1>
	</div>
</div>

<div class="container section support-us">
	<p>
		<?php
		if ( $is_sv ) {
			esc_html_e( 'Castles of Sweden är ett ideellt och volontärdrivet initiativ som arbetar för att bevara och lyfta fram Sveriges rika kulturarv av slott, herrgårdar och palats. Tillsammans med våra medlemmar, partners och stödjare hjälper vi till att koppla samman människor med dessa unika platser genom kunskap, berättande och samarbete.', 'cos-theme' );
		} else {
			esc_html_e( 'Castles of Sweden is a non-profit, volunteer-driven initiative dedicated to preserving and promoting Sweden\'s rich heritage of castles, manors, and palaces. Together with our members, partners, and supporters, we help connect people with these extraordinary places through knowledge, storytelling, and collaboration.', 'cos-theme' );
		}
		?>
	</p>

	<p><?php echo esc_html( $is_sv ? 'Ditt stöd gör att vi kan:' : 'Your support enables us to:' ); ?></p>

	<ul class="support-list">
		<?php if ( $is_sv ) : ?>
			<li><?php esc_html_e( 'Underhålla och utveckla vår interaktiva katalog och karta', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Skapa högkvalitativt utbildningsinnehåll om historia, arkitektur och kulturarv', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Besöka, dokumentera och lyfta fram slott, herrgårdar och palats runt om i Sverige', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Stödja forskning och samarbeten med kulturarvsexperter och institutioner', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Bygga ett kunskapsnätverk som kopplar samman kulturarvsplatser och främjar utbyte av idéer och goda exempel', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Anordna medlemsevenemang, workshops och nätverksmöjligheter', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Hålla plattformen tillgänglig för alla', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Täcka nödvändiga driftskostnader, såsom webbhotell, designverktyg och kommunikation', 'cos-theme' ); ?></li>
		<?php else : ?>
			<li><?php esc_html_e( 'Maintain and expand our interactive directory and map', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Create high-quality educational content on history, architecture, and cultural heritage', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Visit, document, and showcase castles, manors, and palaces across Sweden', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Support research and collaborations with heritage professionals and institutions', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Build a knowledge network that connects heritage sites and encourages the exchange of ideas and best practices', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Organize member events, workshops, and networking opportunities', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Keep the platform accessible to everyone', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Cover essential operating costs, including web hosting, design tools, and outreach', 'cos-theme' ); ?></li>
		<?php endif; ?>
	</ul>

	<h2><?php echo esc_html( $is_sv ? 'Bli medlem' : 'Become a Member' ); ?></h2>

	<p><?php echo esc_html( $is_sv ? 'Medlemskap är öppet för både privatpersoner och kulturarvsfastigheter.' : 'Membership is open to both individuals and heritage properties.' ); ?></p>

	<?php if ( $is_sv ) : ?>
		<p>
			<strong><?php esc_html_e( 'För privatpersoner', 'cos-theme' ); ?></strong>
			<?php esc_html_e( 'är medlemskap ett sätt att stödja bevarandet och främjandet av Sveriges historiska slott, herrgårdar och palats, samtidigt som du hjälper vår gemenskap att växa.', 'cos-theme' ); ?>
		</p>

		<p>
			<strong><?php esc_html_e( 'För slott, herrgårdar, palats och andra kulturarvsverksamheter', 'cos-theme' ); ?></strong>
			<?php esc_html_e( 'erbjuder medlemskap möjligheter att:', 'cos-theme' ); ?>
		</p>
	<?php else : ?>
		<p>
			<strong><?php esc_html_e( 'For individuals', 'cos-theme' ); ?></strong>,
			<?php esc_html_e( 'membership is a way to support the preservation and promotion of Sweden\'s historic castles, manors, and palaces while helping our community grow.', 'cos-theme' ); ?>
		</p>

		<p>
			<strong><?php esc_html_e( 'For castles, manors, palaces, and other heritage businesses', 'cos-theme' ); ?></strong>,
			<?php esc_html_e( 'membership offers opportunities to:', 'cos-theme' ); ?>
		</p>
	<?php endif; ?>

	<ul class="support-list">
		<?php if ( $is_sv ) : ?>
			<li><?php esc_html_e( 'Bli en del av ett nationellt nätverk av kulturarvsdestinationer', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Utbyta kunskap och erfarenhet med andra aktörer', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Delta i medlemsevenemang och workshops', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Öka synligheten genom Castles of Sweden', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Bidra till att stärka och främja Sveriges gemensamma kulturarv', 'cos-theme' ); ?></li>
		<?php else : ?>
			<li><?php esc_html_e( 'Join a national network of heritage destinations', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Exchange knowledge and experience with peers', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Participate in member events and workshops', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Increase visibility through Castles of Sweden', 'cos-theme' ); ?></li>
			<li><?php esc_html_e( 'Contribute to strengthening and promoting Sweden\'s shared cultural heritage', 'cos-theme' ); ?></li>
		<?php endif; ?>
	</ul>

	<div class="support-us__actions">
		<div class="support-us__action">
			<h3><?php echo esc_html( $is_sv ? 'Donera' : 'Donate' ); ?></h3>
			<p>
				<?php
				if ( $is_sv ) {
					esc_html_e( 'Hjälp oss fortsätta vårt arbete med en engångsgåva eller ett återkommande bidrag.', 'cos-theme' );
				} else {
					esc_html_e( 'Help us continue our work with a one-time or recurring contribution.', 'cos-theme' );
				}
				?>
			</p>
			<a class="button" href="https://ko-fi.com/castlesofsweden" target="_blank" rel="noopener"><?php echo esc_html( $is_sv ? 'Donera' : 'Donate' ); ?></a>
		</div>
		<div class="support-us__action">
			<h3><?php echo esc_html( $is_sv ? 'Bli medlem' : 'Become a Member' ); ?></h3>
			<p>
				<?php
				if ( $is_sv ) {
					esc_html_e( 'Bli en del av nätverket av svenska slott och herrgårdar och hjälp till att forma framtiden för Sveriges historiska kulturarv.', 'cos-theme' );
				} else {
					esc_html_e( 'Become part of the Association of Swedish Castles and Manors and help shape the future of Sweden\'s historic heritage.', 'cos-theme' );
				}
				?>
			</p>
			<?php
			$membership_product = get_page_by_path( 'individual-membership', OBJECT, 'product' );
			if ( $membership_product && $is_sv ) {
				$paired_id = (int) get_post_meta( $membership_product->ID, 'cos_translation_id', true );
				$paired    = $paired_id ? get_post( $paired_id ) : null;
				if ( $paired && 'publish' === $paired->post_status ) {
					$membership_product = $paired;
				}
			}
			?>
			<a class="button" href="<?php echo esc_url( $membership_product ? get_permalink( $membership_product ) : '#' ); ?>"><?php echo esc_html( $is_sv ? 'Bli medlem' : 'Become a Member' ); ?></a>
		</div>
	</div>
</div>

<?php get_footer(); ?>
