<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WindCodex ReturnDesk
 * Tagline:           Returns for WooCommerce
 * Description:       Add a self-service returns workflow to WooCommerce with eligibility rules, a customer portal, and request management.
 * Version:           1.0.0
 * Author:            WindCodex
 * Author URI:        https://www.windcodex.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       windcodex-returndesk
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   10.6
 *
 */

defined( 'ABSPATH' ) || exit;

define( 'RETURNDESK_VERSION',     '1.0.0' );
define( 'RETURNDESK_PLUGIN_FILE', __FILE__ );
define( 'RETURNDESK_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'RETURNDESK_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'RETURNDESK_PLUGIN_BASE', plugin_basename( __FILE__ ) );
if ( ! defined( 'RETURNDESK_SETTINGS_OPTION' ) ) {
	define( 'RETURNDESK_SETTINGS_OPTION', 'returndesk_settings' );
}

function returndesk_check_woocommerce(): void {
	if ( class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_action( 'admin_notices', 'returndesk_missing_wc_notice' );
	deactivate_plugins( RETURNDESK_PLUGIN_BASE );
}
add_action( 'plugins_loaded', 'returndesk_check_woocommerce' );

function returndesk_missing_wc_notice(): void {
	echo '<div class="notice notice-error"><p>';
	echo wp_kses_post(
		sprintf(
			/* translators: %s: WooCommerce plugin URL */
			__( '<strong>ReturnDesk</strong> requires <a href="%s">WooCommerce</a> to be installed and active.', 'windcodex-returndesk' ),
			'https://wordpress.org/plugins/woocommerce/'
		)
	);
	echo '</p></div>';
}

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

function returndesk_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-loader.php';
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-restrictions.php';
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-frontend.php';
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-requests-store.php';
	require_once RETURNDESK_PLUGIN_DIR . 'admin/class-returndesk-admin.php';
	require_once RETURNDESK_PLUGIN_DIR . 'public/class-returndesk-public.php';

	ReturnDesk_Requests_Store::maybe_install();
	ReturnDesk_Loader::instance();
}
add_action( 'plugins_loaded', 'returndesk_init', 20 );

function returndesk_activate(): void {
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-restrictions.php';
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-frontend.php';
	require_once RETURNDESK_PLUGIN_DIR . 'includes/class-returndesk-requests-store.php';

	$defaults = ReturnDesk_Restrictions::get_default_settings();
	$current_new = (array) get_option( RETURNDESK_SETTINGS_OPTION, array() );
	$current     = $current_new;

	update_option( RETURNDESK_SETTINGS_OPTION, array_merge( $defaults, $current ) );
	update_option( 'returndesk_version', RETURNDESK_VERSION );
	ReturnDesk_Requests_Store::install();

	// Ensure My Account endpoint is registered.
	ReturnDesk_Frontend::add_account_endpoint();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'returndesk_activate' );
