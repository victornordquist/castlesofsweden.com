<?php
/**
 * Plugin Name: Castles of Sweden — Core
 * Description: Custom post types, taxonomies, meta fields, and data import tooling for castlesofsweden.com.
 * Version: 0.1.0
 * Author: Castles of Sweden
 * Text Domain: cos-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'COS_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once COS_CORE_DIR . 'includes/class-post-types.php';
require_once COS_CORE_DIR . 'includes/class-taxonomies.php';
require_once COS_CORE_DIR . 'includes/class-meta-fields.php';
require_once COS_CORE_DIR . 'includes/class-rest-map-endpoint.php';
require_once COS_CORE_DIR . 'includes/class-search.php';
require_once COS_CORE_DIR . 'includes/class-rest-search-endpoint.php';
require_once COS_CORE_DIR . 'includes/class-i18n.php';
require_once COS_CORE_DIR . 'includes/class-newsletter.php';
require_once COS_CORE_DIR . 'includes/class-listing-meta-fields.php';
require_once COS_CORE_DIR . 'includes/class-currency.php';
require_once COS_CORE_DIR . 'includes/class-post-meta-fields.php';
require_once COS_CORE_DIR . 'includes/class-author-meta-fields.php';
require_once COS_CORE_DIR . 'includes/class-user-avatar.php';
require_once COS_CORE_DIR . 'includes/class-building-import-helpers.php';
require_once COS_CORE_DIR . 'includes/class-admin-import-buildings.php';
require_once COS_CORE_DIR . 'includes/class-language-fields.php';
require_once COS_CORE_DIR . 'includes/class-language-routing.php';

COS_Post_Types::init();
COS_Taxonomies::init();
COS_Meta_Fields::init();
COS_REST_Map_Endpoint::init();
COS_REST_Search_Endpoint::init();
COS_I18N::init();
COS_Newsletter::init();
COS_Listing_Meta_Fields::init();
COS_Currency::init();
COS_Post_Meta_Fields::init();
COS_Author_Meta_Fields::init();
COS_User_Avatar::init();
COS_Admin_Import_Buildings::init();
COS_Language_Fields::init();
COS_Language_Routing::init();

register_activation_hook( __FILE__, function () {
	COS_Post_Types::register_building();
	COS_Post_Types::register_listing();
	COS_Taxonomies::register_all();
	COS_Currency::activate();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, array( 'COS_Currency', 'deactivate' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once COS_CORE_DIR . 'includes/cli/class-cli-import-buildings.php';
	WP_CLI::add_command( 'cos import-buildings', 'COS_CLI_Import_Buildings' );
}
