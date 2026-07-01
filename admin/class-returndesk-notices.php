<?php
/**
 * Admin notices — Pro upsell banner and review request.
 *
 * @package ReturnDesk
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Notices {

	const REVIEW_DISMISSED_OPTION = 'returndesk_review_dismissed';
	const REVIEW_REMIND_TRANSIENT = 'returndesk_review_remind_later';
	const REVIEW_DAYS             = 7;
	const REVIEW_REMIND_DAYS      = 14;

	// ── Helpers ────────────────────────────────────────────────────────────

	private function on_settings_page(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && 'woocommerce_page_returndesk-settings' === $screen->id;
	}

	// ── Render ─────────────────────────────────────────────────────────────

	public function render(): void {
		if ( ! $this->on_settings_page() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$this->render_review_notice();
	}

	public function render_pro_notice(): void {
		?>
		<div class="gg-pro-notice">
			<p class="gg-pro-notice-text">
				🚀 <strong><?php esc_html_e( 'ReturnDesk Pro', 'windcodex-returndesk' ); ?></strong>
				<?php esc_html_e( '— Auto-approvals, exchange requests, store credit, partial returns, WhatsApp notifications, excluded products, and auto-close.', 'windcodex-returndesk' ); ?>
			</p>
			<a href="https://windcodex.com/product/woocommerce-returns-plugin/" target="_blank" rel="noopener" class="gg-pro-notice-cta">
				<?php esc_html_e( 'Explore ReturnDesk Pro →', 'windcodex-returndesk' ); ?>
			</a>
		</div>
		<?php
	}

	public function render_review_notice(): void {
		if ( get_option( self::REVIEW_DISMISSED_OPTION, '' ) ) {
			return;
		}

		if ( get_transient( self::REVIEW_REMIND_TRANSIENT ) ) {
			return;
		}

		$activated = (int) get_option( 'returndesk_activated_time', 0 );

		if ( ! $activated ) {
			// Seed the time now for existing installs that pre-date this notice.
			update_option( 'returndesk_activated_time', time() );
			return;
		}

		if ( ( time() - $activated ) < ( self::REVIEW_DAYS * DAY_IN_SECONDS ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible gg-review-notice" id="gg-review-notice">
			<p class="gg-review-notice-title">
				<?php esc_html_e( '⭐ Is ReturnDesk saving you time on returns?', 'windcodex-returndesk' ); ?>
			</p>
			<p class="gg-review-notice-body">
				<?php esc_html_e( "You've been using ReturnDesk for 7 days — we hope it's making your return process smoother and saving you hours of manual work.", 'windcodex-returndesk' ); ?>
				<br>
				<?php esc_html_e( "If it's been helpful, a quick review on WordPress.org takes less than 2 minutes and helps thousands of other store owners find the plugin.", 'windcodex-returndesk' ); ?>
			</p>
			<div class="gg-review-notice-actions">
				<a href="https://wordpress.org/support/plugin/windcodex-returndesk/reviews/#new-post"
				   target="_blank" rel="noopener"
				   class="gg-review-btn gg-review-btn-primary"
				   data-gg-review-action="reviewed">
					<?php esc_html_e( '⭐ Leave a Review', 'windcodex-returndesk' ); ?>
				</a>
				<button type="button" class="gg-review-btn gg-review-btn-secondary" data-gg-review-action="reviewed">
					<?php esc_html_e( 'I already did ✓', 'windcodex-returndesk' ); ?>
				</button>
				<button type="button" class="gg-review-btn gg-review-btn-link" data-gg-review-action="later">
					<?php esc_html_e( 'Maybe Later', 'windcodex-returndesk' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	// ── AJAX: dismiss review notice ────────────────────────────────────────

	public function ajax_dismiss(): void {
		check_ajax_referer( 'returndesk_dismiss_review', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$action = sanitize_key( $_POST['dismiss_action'] ?? 'later' );

		if ( 'later' === $action ) {
			set_transient( self::REVIEW_REMIND_TRANSIENT, 1, self::REVIEW_REMIND_DAYS * DAY_IN_SECONDS );
		} else {
			update_option( self::REVIEW_DISMISSED_OPTION, $action );
		}

		wp_send_json_success();
	}

	// ── Enqueue: add dismiss nonce to existing admin script ────────────────

	public function localize_nonce( string $hook ): void {
		if ( 'woocommerce_page_returndesk-settings' !== $hook ) {
			return;
		}
		wp_localize_script( 'returndesk-admin', 'returndesk_notices', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'returndesk_dismiss_review' ),
		) );
	}
}
