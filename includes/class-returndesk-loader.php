<?php
/**
 * Hook loader for ReturnDesk.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Loader {

	private static $instance = null;
	private $actions         = array();
	private $filters         = array();

	public static function instance(): ReturnDesk_Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->define_hooks();
			self::$instance->run();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function define_hooks(): void {
		$restrictions  = new ReturnDesk_Restrictions();
		$frontend      = new ReturnDesk_Frontend( $restrictions );
		$admin         = new ReturnDesk_Admin();
		$public        = new ReturnDesk_Public();

		$this->add_action( 'init', 'ReturnDesk_Frontend', 'add_account_endpoint' );
		$this->add_action( 'template_redirect', $frontend, 'maybe_handle_request_submission' );
		$this->add_action( 'woocommerce_order_details_after_order_table', $frontend, 'order_view_after_order_table', 10, 1 );
		$this->add_filter( 'query_vars', $frontend, 'query_vars' );
		$this->add_filter( 'woocommerce_account_menu_items', $frontend, 'add_my_account_menu_item' );
		$this->add_action( 'woocommerce_account_returndesk-requests_endpoint', $frontend, 'endpoint_content' );
		$this->add_action( 'woocommerce_account_returndesk-returns_endpoint', $frontend, 'endpoint_returns_content' );

		$this->add_action( 'admin_menu', $admin, 'register_menu' );
		$this->add_action( 'admin_init', $admin, 'register_settings' );
		$this->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->add_action( 'wp_ajax_returndesk_save_settings', $admin, 'ajax_save_settings' );
		$this->add_action( 'wp_ajax_returndesk_reset_settings', $admin, 'ajax_reset_settings' );
		$this->add_action( 'wp_ajax_returndesk_email_preview', $admin, 'ajax_email_preview' );
		$this->add_action( 'wp_ajax_returndesk_send_test_email', $admin, 'ajax_send_test_email' );
		$this->add_action( 'wp_ajax_returndesk_update_request_status', $admin, 'ajax_update_request_status' );
		$this->add_action( 'wp_ajax_returndesk_delete_request', $admin, 'ajax_delete_request' );

		$this->add_filter( 'plugin_action_links_' . RETURNDESK_PLUGIN_BASE, $admin, 'plugin_action_links' );

		$this->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
		$this->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_assets' );
	}

	public function add_action( $hook, $component, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	public function add_filter( $hook, $component, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
