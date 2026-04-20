<?php
/**
 * Frontend shortcodes.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Shortcodes {

	/** @var ReturnDesk_Frontend */
	private $frontend;

	public function __construct( ReturnDesk_Frontend $frontend ) {
		$this->frontend = $frontend;
	}

	public function register(): void {
		add_shortcode( 'returndesk_portal', array( $this, 'render_portal' ) );
	}

	public function render_portal(): string {
		return $this->frontend->get_guest_center_html();
	}
}
