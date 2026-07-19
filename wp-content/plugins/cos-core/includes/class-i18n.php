<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MySQL's default collation (utf8mb4_unicode_520_ci) sorts Å/Ä/Ö next to
 * their base letters (A/O), but Swedish alphabetical order treats them as
 * distinct letters at the very end of the alphabet, after Z. This site's
 * content is entirely Swedish place names, so every name-based sort needs
 * that collation applied explicitly — MySQL's stored column collation isn't
 * changed, only the comparison used for ORDER BY.
 */
class COS_I18N {

	const SWEDISH_COLLATION = 'utf8mb4_swedish_ci';

	public static function init() {
		add_filter( 'terms_clauses', array( __CLASS__, 'swedish_term_order' ), 10, 3 );
		add_filter( 'posts_orderby', array( __CLASS__, 'swedish_post_order' ), 10, 2 );
	}

	/**
	 * Fixes get_terms( array( 'orderby' => 'name' ) ) — used for the
	 * region/category term lists throughout the site.
	 */
	public static function swedish_term_order( $clauses, $taxonomies, $args ) {
		if ( ! empty( $args['orderby'] ) && 'name' === $args['orderby'] ) {
			$direction            = ( ! empty( $args['order'] ) && 'DESC' === strtoupper( $args['order'] ) ) ? 'DESC' : 'ASC';
			$clauses['orderby']   = 'ORDER BY CONVERT(t.name USING utf8mb4) COLLATE ' . self::SWEDISH_COLLATION;
			$clauses['order']     = $direction;
		}
		return $clauses;
	}

	/**
	 * Fixes WP_Query( array( 'orderby' => 'title' ) ) wherever it's used.
	 */
	public static function swedish_post_order( $orderby, $query ) {
		global $wpdb;
		if ( 'title' === $query->get( 'orderby' ) ) {
			$direction = ( 'DESC' === strtoupper( $query->get( 'order' ) ) ) ? 'DESC' : 'ASC';
			$orderby   = "CONVERT({$wpdb->posts}.post_title USING utf8mb4) COLLATE " . self::SWEDISH_COLLATION . " {$direction}";
		}
		return $orderby;
	}
}
