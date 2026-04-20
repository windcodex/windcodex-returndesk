<?php
/**
 * Public assets.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Public {
	public function enqueue_assets(): void {
		$should_enqueue = false;

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			if ( function_exists( 'WC' ) && WC() && isset( WC()->query ) && is_object( WC()->query ) && method_exists( WC()->query, 'get_current_endpoint' ) ) {
				$endpoint = (string) WC()->query->get_current_endpoint();
				$should_enqueue = in_array( $endpoint, array( 'returndesk-requests', 'returndesk-returns', 'view-order' ), true );
			}

			if ( ! $should_enqueue ) {
				global $wp;
				$query_vars = ( is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) ? $wp->query_vars : array();
				$should_enqueue = array_key_exists( 'returndesk-requests', $query_vars )
					|| array_key_exists( 'returndesk-returns', $query_vars )
					|| array_key_exists( 'view-order', $query_vars );
			}
		}

		if ( ! $should_enqueue ) {
			return;
		}

		$css_path = RETURNDESK_PLUGIN_DIR . 'public/assets/public.css';
		$js_path  = RETURNDESK_PLUGIN_DIR . 'public/assets/public.js';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : RETURNDESK_VERSION;
		$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : RETURNDESK_VERSION;

		wp_enqueue_style(
			'returndesk-public',
			RETURNDESK_PLUGIN_URL . 'public/assets/public.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'returndesk-public',
			RETURNDESK_PLUGIN_URL . 'public/assets/public.js',
			array(),
			$js_ver,
			true
		);
	}
}
