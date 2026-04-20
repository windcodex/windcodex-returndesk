<?php
/**
 * Admin settings and request dashboard.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Admin {

	private const ADMIN_PAGE_SLUG        = 'returndesk-settings';

	private const SETTINGS_OPTION        = 'returndesk_settings';

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'ReturnDesk Settings', 'windcodex-returndesk' ),
			__( 'ReturnDesk', 'windcodex-returndesk' ),
			'manage_woocommerce',
			self::ADMIN_PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'returndesk_settings_group',
			self::SETTINGS_OPTION,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	public function sanitize_settings( array $input ): array {
		$defaults = ReturnDesk_Restrictions::get_default_settings();
		$clean    = array();

		if ( ! empty( $input['return_store_country_state'] ) ) {
			$raw = sanitize_text_field( (string) $input['return_store_country_state'] );
			$country = $raw;
			$state   = '';
			if ( strpos( $raw, ':' ) !== false ) {
				$parts   = explode( ':', $raw, 2 );
				$country = (string) ( $parts[0] ?? '' );
				$state   = (string) ( $parts[1] ?? '' );
			}
			$input['return_store_country'] = preg_replace( '/[^A-Z]/', '', strtoupper( (string) $country ) );
			$input['return_store_state']   = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $state ) );
		}

		$clean['enable_returns']        = ( ( $input['enable_returns'] ?? 'no' ) === 'yes' ) ? 'yes' : 'no';
		$clean['return_window_days']    = max( 1, absint( $input['return_window_days'] ?? $defaults['return_window_days'] ) );
		$clean['return_allow_sale_products'] = ( ( $input['return_allow_sale_products'] ?? 'no' ) === 'yes' ) ? 'yes' : 'no';
		$clean['customer_message']      = sanitize_text_field( $input['customer_message'] ?? $defaults['customer_message'] );
		$clean['return_guidelines']     = wp_kses_post( wp_unslash( (string) ( $input['return_guidelines'] ?? $defaults['return_guidelines'] ) ) );
		$clean['return_reasons']        = sanitize_textarea_field( $input['return_reasons'] ?? $defaults['return_reasons'] );
		$clean['terms_page_id']         = absint( $input['terms_page_id'] ?? $defaults['terms_page_id'] );
		$clean['return_store_address_1'] = sanitize_text_field( $input['return_store_address_1'] ?? $defaults['return_store_address_1'] );
		$clean['return_store_address_2'] = sanitize_text_field( $input['return_store_address_2'] ?? $defaults['return_store_address_2'] );
		$clean['return_store_city']      = sanitize_text_field( $input['return_store_city'] ?? $defaults['return_store_city'] );
		$clean['return_store_state']     = sanitize_text_field( $input['return_store_state'] ?? $defaults['return_store_state'] );
		$clean['return_store_country']   = sanitize_text_field( $input['return_store_country'] ?? $defaults['return_store_country'] );
		$clean['return_store_postcode']  = sanitize_text_field( $input['return_store_postcode'] ?? $defaults['return_store_postcode'] );
		$clean['return_store_phone']     = sanitize_text_field( $input['return_store_phone'] ?? $defaults['return_store_phone'] );
		$clean['admin_notification_email']             = sanitize_email( $input['admin_notification_email'] ?? $defaults['admin_notification_email'] );
		$clean['email_enable_new_request_admin']       = ( ( $input['email_enable_new_request_admin'] ?? 'no' ) === 'yes' ) ? 'yes' : 'no';
		$clean['email_enable_new_request_customer']    = ( ( $input['email_enable_new_request_customer'] ?? 'no' ) === 'yes' ) ? 'yes' : 'no';
		$clean['email_enable_status_update_customer']  = ( ( $input['email_enable_status_update_customer'] ?? 'no' ) === 'yes' ) ? 'yes' : 'no';
		$clean['email_subject_new_request_admin']      = sanitize_text_field( $input['email_subject_new_request_admin'] ?? $defaults['email_subject_new_request_admin'] );
		$clean['email_body_new_request_admin']         = sanitize_textarea_field( $input['email_body_new_request_admin'] ?? $defaults['email_body_new_request_admin'] );
		$clean['email_heading_new_request_admin']      = sanitize_text_field( $input['email_heading_new_request_admin'] ?? $defaults['email_heading_new_request_admin'] );
		$clean['email_items_title_new_request_admin']  = sanitize_text_field( $input['email_items_title_new_request_admin'] ?? $defaults['email_items_title_new_request_admin'] );
		$clean['email_additional_content_new_request_admin'] = sanitize_textarea_field( $input['email_additional_content_new_request_admin'] ?? $defaults['email_additional_content_new_request_admin'] );
		$clean['email_subject_new_request_customer']   = sanitize_text_field( $input['email_subject_new_request_customer'] ?? $defaults['email_subject_new_request_customer'] );
		$clean['email_body_new_request_customer']      = sanitize_textarea_field( $input['email_body_new_request_customer'] ?? $defaults['email_body_new_request_customer'] );
		$clean['email_heading_new_request_customer']   = sanitize_text_field( $input['email_heading_new_request_customer'] ?? $defaults['email_heading_new_request_customer'] );
		$clean['email_items_title_new_request_customer'] = sanitize_text_field( $input['email_items_title_new_request_customer'] ?? $defaults['email_items_title_new_request_customer'] );
		$clean['email_additional_content_new_request_customer'] = sanitize_textarea_field( $input['email_additional_content_new_request_customer'] ?? $defaults['email_additional_content_new_request_customer'] );
		$clean['email_subject_status_update_customer'] = sanitize_text_field( $input['email_subject_status_update_customer'] ?? $defaults['email_subject_status_update_customer'] );
		$clean['email_body_status_update_customer']    = sanitize_textarea_field( $input['email_body_status_update_customer'] ?? $defaults['email_body_status_update_customer'] );
		$clean['email_heading_status_update_customer'] = sanitize_text_field( $input['email_heading_status_update_customer'] ?? $defaults['email_heading_status_update_customer'] );
		$clean['email_items_title_status_update_customer'] = sanitize_text_field( $input['email_items_title_status_update_customer'] ?? $defaults['email_items_title_status_update_customer'] );
		$clean['email_additional_content_status_update_customer'] = sanitize_textarea_field( $input['email_additional_content_status_update_customer'] ?? $defaults['email_additional_content_status_update_customer'] );
		$clean['email_subject_status_approved_customer'] = sanitize_text_field( $input['email_subject_status_approved_customer'] ?? $defaults['email_subject_status_approved_customer'] );
		$clean['email_body_status_approved_customer']    = sanitize_textarea_field( $input['email_body_status_approved_customer'] ?? $defaults['email_body_status_approved_customer'] );
		$clean['email_heading_status_approved_customer'] = sanitize_text_field( $input['email_heading_status_approved_customer'] ?? $defaults['email_heading_status_approved_customer'] );
		$clean['email_items_title_status_approved_customer'] = sanitize_text_field( $input['email_items_title_status_approved_customer'] ?? $defaults['email_items_title_status_approved_customer'] );
		$clean['email_additional_content_status_approved_customer'] = sanitize_textarea_field( $input['email_additional_content_status_approved_customer'] ?? $defaults['email_additional_content_status_approved_customer'] );
		$clean['email_subject_status_rejected_customer'] = sanitize_text_field( $input['email_subject_status_rejected_customer'] ?? $defaults['email_subject_status_rejected_customer'] );
		$clean['email_body_status_rejected_customer']    = sanitize_textarea_field( $input['email_body_status_rejected_customer'] ?? $defaults['email_body_status_rejected_customer'] );
		$clean['email_heading_status_rejected_customer'] = sanitize_text_field( $input['email_heading_status_rejected_customer'] ?? $defaults['email_heading_status_rejected_customer'] );
		$clean['email_items_title_status_rejected_customer'] = sanitize_text_field( $input['email_items_title_status_rejected_customer'] ?? $defaults['email_items_title_status_rejected_customer'] );
		$clean['email_additional_content_status_rejected_customer'] = sanitize_textarea_field( $input['email_additional_content_status_rejected_customer'] ?? $defaults['email_additional_content_status_rejected_customer'] );
		$clean['email_subject_status_cancelled_customer'] = sanitize_text_field( $input['email_subject_status_cancelled_customer'] ?? $defaults['email_subject_status_cancelled_customer'] );
		$clean['email_body_status_cancelled_customer']    = sanitize_textarea_field( $input['email_body_status_cancelled_customer'] ?? $defaults['email_body_status_cancelled_customer'] );
		$clean['email_heading_status_cancelled_customer'] = sanitize_text_field( $input['email_heading_status_cancelled_customer'] ?? $defaults['email_heading_status_cancelled_customer'] );
		$clean['email_items_title_status_cancelled_customer'] = sanitize_text_field( $input['email_items_title_status_cancelled_customer'] ?? $defaults['email_items_title_status_cancelled_customer'] );
		$clean['email_additional_content_status_cancelled_customer'] = sanitize_textarea_field( $input['email_additional_content_status_cancelled_customer'] ?? $defaults['email_additional_content_status_cancelled_customer'] );

		$allowed_statuses = array( 'pending', 'processing', 'completed', 'on-hold' );
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$allowed_statuses = array();
			foreach ( (array) wc_get_order_statuses() as $status_key => $status_label ) {
				$key = ( 0 === strpos( (string) $status_key, 'wc-' ) ) ? substr( (string) $status_key, 3 ) : (string) $status_key;
				$allowed_statuses[] = sanitize_key( $key );
			}
		}
		$selected         = array_map( 'sanitize_key', (array) ( $input['allowed_statuses'] ?? $defaults['allowed_statuses'] ) );
		$selected         = array_values( array_intersect( $selected, $allowed_statuses ) );
		$clean['allowed_statuses'] = ! empty( $selected ) ? $selected : $defaults['allowed_statuses'];

		return $clean;
	}

	private function get_store_address_text( array $settings ): string {
		$parts = array(
			sanitize_text_field( (string) ( $settings['return_store_address_1'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_address_2'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_city'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_state'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_country'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_postcode'] ?? '' ) ),
		);
		$parts = array_values(
			array_filter(
				$parts,
				static function ( $value ) {
					return '' !== trim( (string) $value );
				}
			)
		);

		return implode( ', ', $parts );
	}

	public function render_settings_page(): void {
		require RETURNDESK_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public function render_requests_page(): void {
		$requests = array();
		if ( class_exists( 'ReturnDesk_Requests_Store' ) ) {
			$requests = ( new ReturnDesk_Requests_Store() )->list_recent( 50 );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Return Requests', 'windcodex-returndesk' ) . '</h1>';
		echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Order</th><th>Type</th><th>Qty</th><th>Status</th><th>Reason</th><th>Date</th></tr></thead><tbody>';

		if ( empty( $requests ) ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No requests found.', 'windcodex-returndesk' ) . '</td></tr>';
		}

		foreach ( $requests as $request ) {
			if ( ! is_array( $request ) ) {
				continue;
			}
			$id       = absint( $request['id'] ?? 0 );
			$order_id = absint( $request['order_id'] ?? 0 );
			$order_edit_url = $order_id > 0 ? admin_url( 'post.php?post=' . $order_id . '&action=edit' ) : '';
			$status   = sanitize_key( (string) ( $request['status'] ?? 'pending' ) );
			$reason_raw = sanitize_textarea_field( (string) ( $request['reason'] ?? '' ) );
			$date     = sanitize_text_field( (string) ( $request['created_at'] ?? '' ) );
			$items    = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
			$qty      = 0;
			foreach ( $items as $entry ) {
				if ( is_array( $entry ) ) {
					$qty += max( 0, absint( $entry['qty'] ?? 0 ) );
				}
			}

			echo '<tr>';
			echo '<td>' . esc_html( 'RD-' . $id ) . '</td>';
			if ( $order_id > 0 && '' !== $order_edit_url ) {
				echo '<td><a href="' . esc_url( $order_edit_url ) . '" target="_blank" rel="noopener noreferrer">#' . esc_html( $order_id ) . '</a></td>';
			} else {
				echo '<td>#' . esc_html( $order_id ) . '</td>';
			}
			echo '<td>' . esc_html__( 'Return', 'windcodex-returndesk' ) . '</td>';
			echo '<td>' . esc_html( $qty ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
			$reason_html = nl2br( esc_html( $reason_raw ) );
			echo '<td>' . wp_kses( $reason_html, array( 'br' => array() ) ) . '</td>';
			echo '<td>' . esc_html( $date ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}

	public function ajax_save_settings(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_save_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_textarea_field' )
			: array();

		$existing     = (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );
		$clean    = $this->sanitize_settings( array_merge( $existing, $input ) );
		// Preserve any unknown keys (e.g. Pro-only settings) already stored in the option.
		$merged = array_merge( $existing, $clean );
		update_option( self::SETTINGS_OPTION, $merged );

		wp_send_json_success(
			array(
				'message'  => __( 'Settings saved successfully.', 'windcodex-returndesk' ),
				'settings' => $merged,
			)
		);
	}

	public function ajax_reset_settings(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_reset_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$defaults = ReturnDesk_Restrictions::get_default_settings();
		update_option( self::SETTINGS_OPTION, $defaults );

		wp_send_json_success(
			array(
				'message'  => __( 'Settings reset to defaults.', 'windcodex-returndesk' ),
				'settings' => $defaults,
			)
		);
	}

	public function ajax_send_test_email(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_send_test_email' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$target_email = isset( $_POST['target_email'] ) ? sanitize_email( wp_unslash( $_POST['target_email'] ) ) : '';
		$template     = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : 'status_approved';
		$input        = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_textarea_field' )
			: (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );
		$existing     = (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );
		$settings     = $this->sanitize_settings( array_merge( $existing, $input ) );

		if ( ! is_email( $target_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'windcodex-returndesk' ) ), 400 );
		}

		$tokens = array(
			'{request_id}' => '12345',
			'{order_id}'   => '1001',
			'{order_number}' => '1001',
			'{order_date}'   => gmdate( 'F j, Y' ),
			'{site_title}'   => get_bloginfo( 'name' ),
			'{customer_full_name}'  => 'John Doe',
			'{customer_first_name}' => 'John',
			'{customer_last_name}'  => 'Doe',
			'{customer_username}'   => 'john_doe',
			'{type}'       => 'Return',
			'{status}'     => 'Approved',
			'{return_address}' => $this->get_return_address_text( $settings ),
			'{store_address}'  => $this->get_store_address_text( $settings ),
			'{phone_number}'   => sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) ),
			'{return_guidelines}' => (string) ( $settings['return_guidelines'] ?? '' ),
		);

		switch ( $template ) {
			case 'new_request_admin':
				$enabled = ( 'yes' === ( $settings['email_enable_new_request_admin'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_new_request_admin'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_new_request_admin'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_new_request_admin'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_new_request_admin'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_new_request_admin'] ?? '' ), $tokens );
				break;
			case 'new_request_customer':
				$enabled = ( 'yes' === ( $settings['email_enable_new_request_customer'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_new_request_customer'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_new_request_customer'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_new_request_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_new_request_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_new_request_customer'] ?? '' ), $tokens );
				break;
			case 'status_approved':
				$tokens['{status}'] = 'Approved';
				$enabled = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_status_approved_customer'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_status_approved_customer'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_status_approved_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_approved_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_approved_customer'] ?? '' ), $tokens );
				break;
			case 'status_rejected':
				$tokens['{status}'] = 'Rejected';
				$enabled = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_status_rejected_customer'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_status_rejected_customer'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_status_rejected_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_rejected_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_rejected_customer'] ?? '' ), $tokens );
				break;
			case 'status_cancelled':
				$tokens['{status}'] = 'Cancelled';
				$enabled = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_status_cancelled_customer'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_status_cancelled_customer'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_status_cancelled_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_cancelled_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_cancelled_customer'] ?? '' ), $tokens );
				break;
			default:
				$enabled = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject = strtr( (string) ( $settings['email_subject_status_update_customer'] ?? '' ), $tokens );
				$heading = strtr( (string) ( $settings['email_heading_status_update_customer'] ?? '' ), $tokens );
				$content = strtr( (string) ( $settings['email_body_status_update_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_update_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_update_customer'] ?? '' ), $tokens );
				break;
		}

		if ( ! $enabled ) {
			wp_send_json_error( array( 'message' => __( 'Selected email template is disabled.', 'windcodex-returndesk' ) ), 400 );
		}
		if ( empty( $subject ) || empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Email subject/body cannot be empty.', 'windcodex-returndesk' ) ), 400 );
		}

		$message = wpautop( wp_kses_post( (string) $content ) );
		if ( '' !== trim( (string) $items_title ) ) {
			$message .= '<h2 style="margin:18px 0 10px;font-size:16px;line-height:1.2;">' . esc_html( (string) $items_title ) . '</h2>';
		}
		$message .= $this->build_preview_items_table_html();
		if ( '' !== trim( wp_strip_all_tags( (string) $additional ) ) ) {
			$message .= wpautop( wp_kses_post( (string) $additional ) );
		}

		$message_html = $this->wrap_with_wc_email_template( (string) $heading, $message );

		if ( function_exists( 'wc_mail' ) ) {
			$sent = (bool) wc_mail( $target_email, wp_strip_all_tags( (string) $subject ), $message_html );
		} else {
			$sent = (bool) wp_mail(
				$target_email,
				wp_strip_all_tags( (string) $subject ),
				$message_html,
				array( 'Content-Type: text/html; charset=UTF-8' )
			);
		}
		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'Could not send test email.', 'windcodex-returndesk' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => __( 'Test email sent successfully.', 'windcodex-returndesk' ) ) );
	}

	// build_email_preview_html removed (WooCommerce wrapper is used instead).

	public function ajax_update_request_status(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_update_request_status' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$request_id = isset( $_POST['request_id'] ) ? absint( wp_unslash( $_POST['request_id'] ) ) : 0;
		$status     = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$allowed    = array_keys( ReturnDesk_Restrictions::get_request_statuses() );

		if ( $request_id <= 0 || ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'windcodex-returndesk' ) ), 400 );
		}

		if ( ! in_array( $status, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status.', 'windcodex-returndesk' ) ), 400 );
		}

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( ! is_array( $request ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'windcodex-returndesk' ) ), 400 );
		}
		if ( 'return' !== sanitize_key( (string) ( $request['request_type'] ?? 'return' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Only return requests can be managed in free version.', 'windcodex-returndesk' ) ), 403 );
		}

		$store->update_status( $request_id, $status );
		$this->send_request_status_email( $request_id, $status );

		wp_send_json_success(
			array(
				'status'       => $status,
				'status_label' => ReturnDesk_Restrictions::get_request_statuses()[ $status ],
				'message'      => __( 'Request updated.', 'windcodex-returndesk' ),
			)
		);
	}

	private function send_request_status_email( int $request_id, string $status ): void {
		$order_id      = 0;
		$request_type  = 'return';
		$guest_email   = '';
		$customer_mail = '';
		$order         = null;
		$items         = array();
		$settings      = (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );

		if ( class_exists( 'ReturnDesk_Requests_Store' ) ) {
			$store   = new ReturnDesk_Requests_Store();
			$request = $store->get( $request_id );
			if ( is_array( $request ) ) {
				$order_id     = absint( $request['order_id'] ?? 0 );
				$request_type = sanitize_key( (string) ( $request['request_type'] ?? 'return' ) );
				$guest_email  = sanitize_email( (string) ( $request['guest_email'] ?? '' ) );
				$items        = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
			}
		}

		if ( is_email( $guest_email ) ) {
			$customer_mail = $guest_email;
		} elseif ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$customer_mail = sanitize_email( (string) $order->get_billing_email() );
			}
		}

		if ( ! is_email( $customer_mail ) ) {
			return;
		}

		$order_date = '';
		$first_name = '';
		$last_name  = '';
		$full_name  = '';
		$username   = '';
		if ( $order instanceof WC_Order ) {
			$date_created = $order->get_date_created();
			$order_date   = $date_created ? wc_format_datetime( $date_created ) : '';
			$first_name   = sanitize_text_field( (string) $order->get_billing_first_name() );
			$last_name    = sanitize_text_field( (string) $order->get_billing_last_name() );
			$full_name    = trim( $first_name . ' ' . $last_name );
			$user_id      = absint( $order->get_user_id() );
			if ( $user_id > 0 ) {
				$user = get_userdata( $user_id );
				if ( $user instanceof WP_User ) {
					$username = sanitize_user( (string) $user->user_login );
				}
			}
		}

		$tokens = array(
			'{request_id}' => (string) $request_id,
			'{order_id}'   => (string) $order_id,
			'{order_number}' => $order instanceof WC_Order ? (string) $order->get_order_number() : (string) $order_id,
			'{order_date}'   => (string) $order_date,
			'{site_title}'   => (string) get_bloginfo( 'name' ),
			'{customer_full_name}'  => (string) $full_name,
			'{customer_first_name}' => (string) $first_name,
			'{customer_last_name}'  => (string) $last_name,
			'{customer_username}'   => (string) $username,
			'{type}'       => ucfirst( $request_type ? $request_type : 'return' ),
			'{status}'     => ucfirst( $status ),
			'{return_address}' => $this->get_return_address_text( $settings ),
			'{store_address}' => $this->get_return_address_text( $settings ),
			'{phone_number}'  => sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) ),
			'{return_guidelines}' => (string) ( $settings['return_guidelines'] ?? '' ),
		);

		$status_key = in_array( $status, array( 'approved', 'rejected', 'cancelled' ), true ) ? $status : '';
		$subject_tpl = $status_key ? (string) ( $settings[ 'email_subject_status_' . $status_key . '_customer' ] ?? '' ) : '';
		$heading_tpl = $status_key ? (string) ( $settings[ 'email_heading_status_' . $status_key . '_customer' ] ?? '' ) : '';
		$content_tpl = $status_key ? (string) ( $settings[ 'email_body_status_' . $status_key . '_customer' ] ?? '' ) : '';
		$items_title_tpl = $status_key ? (string) ( $settings[ 'email_items_title_status_' . $status_key . '_customer' ] ?? '' ) : '';
		$additional_tpl  = $status_key ? (string) ( $settings[ 'email_additional_content_status_' . $status_key . '_customer' ] ?? '' ) : '';

		if ( '' === trim( $subject_tpl ) ) {
			$subject_tpl = (string) ( $settings['email_subject_status_update_customer'] ?? '' );
		}
		if ( '' === trim( $heading_tpl ) ) {
			$heading_tpl = (string) ( $settings['email_heading_status_update_customer'] ?? '' );
		}
		if ( '' === trim( $content_tpl ) ) {
			$content_tpl = (string) ( $settings['email_body_status_update_customer'] ?? '' );
		}
		if ( '' === trim( $items_title_tpl ) ) {
			$items_title_tpl = (string) ( $settings['email_items_title_status_update_customer'] ?? '' );
		}
		if ( '' === trim( $additional_tpl ) ) {
			$additional_tpl = (string) ( $settings['email_additional_content_status_update_customer'] ?? '' );
		}

		$subject = strtr( (string) $subject_tpl, $tokens );
		$heading = strtr( (string) $heading_tpl, $tokens );
		$content = strtr( (string) $content_tpl, $tokens );
		$items_title = strtr( (string) $items_title_tpl, $tokens );
		$additional  = strtr( (string) $additional_tpl, $tokens );

		if ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) && ! empty( $subject ) && ! empty( $content ) ) {
			$message = wpautop( wp_kses_post( (string) $content ) );
			if ( '' !== trim( wp_strip_all_tags( (string) $items_title ) ) ) {
				$message .= '<h2 style="margin:18px 0 10px;font-size:16px;line-height:1.2;">' . esc_html( wp_strip_all_tags( (string) $items_title ) ) . '</h2>';
			}
			$message .= ( $order instanceof WC_Order ) ? $this->build_request_items_table_html( $order, $items ) : $this->build_preview_items_table_html();
			if ( '' !== trim( wp_strip_all_tags( (string) $additional ) ) ) {
				$message .= wpautop( wp_kses_post( (string) $additional ) );
			}

		$html = $this->wrap_with_wc_email_template(
			(string) $heading,
			$message,
			( 'new_request_admin' === $template ) ? array( 'WC_Email_New_Order', 'WC_Email_Customer_Processing_Order' ) : array( 'WC_Email_Customer_Processing_Order', 'WC_Email_New_Order' )
		);
			if ( function_exists( 'wc_mail' ) ) {
				wc_mail( $customer_mail, wp_strip_all_tags( (string) $subject ), $html );
			} else {
				wp_mail( $customer_mail, wp_strip_all_tags( (string) $subject ), $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
			}
		}
	}

	private function build_request_items_table_html( WC_Order $order, array $items ): string {
		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( empty( $items ) ) {
			return $this->build_preview_items_table_html();
		}

		$currency = method_exists( $order, 'get_currency' ) ? (string) $order->get_currency() : '';
		$rows     = '';
		foreach ( $items as $entry ) {
			$item_id = absint( $entry['order_item_id'] ?? 0 );
			$qty     = max( 1, absint( $entry['qty'] ?? 0 ) );
			$item    = $item_id ? $order->get_item( $item_id ) : null;
			if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			$product_name = sanitize_text_field( (string) $item->get_name() );
			$ordered_qty  = max( 1, (int) $item->get_quantity() );
			$unit_total   = (float) $item->get_total() / $ordered_qty;
			$line_total   = $unit_total * $qty;
			$price_html   = function_exists( 'wc_price' ) ? wp_kses_post( wc_price( $line_total, array( 'currency' => $currency ) ) ) : esc_html( (string) $line_total );

			$rows .= '<tr>';
			$rows .= '<td style="border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html( $product_name ) . '</td>';
			$rows .= '<td style="border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . $price_html . '</td>';
			$rows .= '<td style="border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html( (string) $qty ) . '</td>';
			$rows .= '</tr>';
		}

		if ( '' === $rows ) {
			return $this->build_preview_items_table_html();
		}

		$table  = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;">';
		$table .= '<thead><tr>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Product', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Price', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Quantity', 'windcodex-returndesk' ) . '</th>';
		$table .= '</tr></thead><tbody>' . $rows . '</tbody></table>';

		return $table;
	}

	private function get_return_address_text( array $settings ): string {
		$parts = array(
			sanitize_text_field( (string) ( $settings['return_store_address_1'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_address_2'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_city'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_state'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_country'] ?? '' ) ),
			sanitize_text_field( (string) ( $settings['return_store_postcode'] ?? '' ) ),
		);
		$parts = array_values(
			array_filter(
				$parts,
				static function ( $value ) {
					return '' !== trim( (string) $value );
				}
			)
		);

		$phone = sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) );
		if ( '' !== $phone ) {
			$parts[] = sprintf(
				/* translators: %s: phone number */
				__( 'Phone: %s', 'windcodex-returndesk' ),
				$phone
			);
		}

		$text = implode( ', ', $parts );
		if ( '' === $text ) {
			$defaults = ReturnDesk_Restrictions::get_default_settings();
			$fallback = $this->get_store_address_text( $defaults );
			$fphone   = sanitize_text_field( (string) ( $defaults['return_store_phone'] ?? '' ) );
			if ( '' !== $fphone ) {
				$fallback = trim(
					$fallback . ', ' . sprintf(
						/* translators: %s: phone number */
						__( 'Phone: %s', 'windcodex-returndesk' ),
						$fphone
					)
				);
			}
			return $fallback;
		}

		return $text;
	}

	public function ajax_delete_request(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_delete_request' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$request_id = isset( $_POST['request_id'] ) ? absint( wp_unslash( $_POST['request_id'] ) ) : 0;
		if ( $request_id <= 0 || ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'windcodex-returndesk' ) ), 400 );
		}

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( ! is_array( $request ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'windcodex-returndesk' ) ), 400 );
		}

		$deleted = $store->delete( $request_id );
		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete request.', 'windcodex-returndesk' ) ), 500 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Request deleted.', 'windcodex-returndesk' ),
			)
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'woocommerce_page_' . self::ADMIN_PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'returndesk-admin', RETURNDESK_PLUGIN_URL . 'admin/assets/admin.css', array(), RETURNDESK_VERSION );

		$deps = array( 'jquery' );
		if ( class_exists( 'WooCommerce' ) ) {
			if ( ! wp_script_is( 'selectWoo', 'registered' ) ) {
				wp_register_script(
					'selectWoo',
					WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js',
					array( 'jquery' ),
					defined( 'WC_VERSION' ) ? WC_VERSION : RETURNDESK_VERSION,
					true
				);
			}
			if ( ! wp_style_is( 'select2', 'registered' ) ) {
				wp_register_style(
					'select2',
					WC()->plugin_url() . '/assets/css/select2.css',
					array(),
					defined( 'WC_VERSION' ) ? WC_VERSION : RETURNDESK_VERSION
				);
			}

			$select_handle = null;
			if ( wp_script_is( 'selectWoo', 'registered' ) ) {
				$select_handle = 'selectWoo';
			} elseif ( wp_script_is( 'select2', 'registered' ) ) {
				$select_handle = 'select2';
			}

			if ( $select_handle ) {
				$deps[] = $select_handle;
				wp_enqueue_script( $select_handle );
			}
			if ( wp_script_is( 'wc-enhanced-select', 'registered' ) ) {
				wp_enqueue_script( 'wc-enhanced-select' );
			}
			wp_enqueue_style( 'select2' );
		}

		wp_enqueue_script( 'returndesk-admin', RETURNDESK_PLUGIN_URL . 'admin/assets/admin.js', $deps, RETURNDESK_VERSION, true );
		wp_enqueue_script( 'returndesk-email-customizer', RETURNDESK_PLUGIN_URL . 'admin/assets/email-customizer.js', array( 'jquery' ), RETURNDESK_VERSION, true );
		$this->enqueue_inline_styles();

		$settings = ( new ReturnDesk_Restrictions() )->get_settings();
		$localized = array(
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'save_nonce'  => wp_create_nonce( 'returndesk_save_settings' ),
			'reset_nonce' => wp_create_nonce( 'returndesk_reset_settings' ),
			'preview_nonce' => wp_create_nonce( 'returndesk_email_preview' ),
			'test_email_nonce' => wp_create_nonce( 'returndesk_send_test_email' ),
			'status_nonce' => wp_create_nonce( 'returndesk_update_request_status' ),
			'delete_nonce' => wp_create_nonce( 'returndesk_delete_request' ),
			'search_nonce' => wp_create_nonce( 'returndesk_search' ),
			'settings'    => $settings,
			'i18n'        => array(
				'saving'        => __( 'Saving...', 'windcodex-returndesk' ),
				'saved'         => __( 'Settings saved!', 'windcodex-returndesk' ),
				'save_error'    => __( 'Could not save settings.', 'windcodex-returndesk' ),
				'reset_confirm' => __( 'Reset all settings to default values?', 'windcodex-returndesk' ),
				'reset_done'    => __( 'Settings reset.', 'windcodex-returndesk' ),
				'request_update_error' => __( 'Could not update request status.', 'windcodex-returndesk' ),
				'request_delete_confirm' => __( 'Delete this request permanently?', 'windcodex-returndesk' ),
				'request_delete_error' => __( 'Could not delete request.', 'windcodex-returndesk' ),
				'approve' => __( 'Approve', 'windcodex-returndesk' ),
				'reject'  => __( 'Reject', 'windcodex-returndesk' ),
				'delete'  => __( 'Delete', 'windcodex-returndesk' ),
				'attachment_preview' => __( 'Attachment Preview', 'windcodex-returndesk' ),
				'close' => __( 'Close', 'windcodex-returndesk' ),
				'back' => __( 'Back', 'windcodex-returndesk' ),
				'next' => __( 'Next', 'windcodex-returndesk' ),
				'test_email_error' => __( 'Could not send test email.', 'windcodex-returndesk' ),
				'preview_subject' => __( 'Subject', 'windcodex-returndesk' ),
				'preview_body'    => __( 'Message', 'windcodex-returndesk' ),
			),
		);

		wp_localize_script( 'returndesk-admin', 'returndesk_admin', $localized );
		wp_localize_script( 'returndesk-email-customizer', 'returndesk_admin', $localized );
	}

	public function ajax_email_preview(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'returndesk_email_preview' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'windcodex-returndesk' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-returndesk' ) ), 403 );
		}

		$this->enqueue_preview_styles();

		$template = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : 'new_request_customer';
		$input    = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_textarea_field' )
			: (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );
		$existing = (array) get_option( self::SETTINGS_OPTION, ReturnDesk_Restrictions::get_default_settings() );
		$settings = $this->sanitize_settings( array_merge( $existing, $input ) );

		$tokens = array(
			'{request_id}' => '12345',
			'{order_id}'   => '1001',
			'{order_number}' => '1001',
			'{order_date}'   => gmdate( 'F j, Y' ),
			'{site_title}'   => get_bloginfo( 'name' ),
			'{customer_full_name}'  => 'John Doe',
			'{customer_first_name}' => 'John',
			'{customer_last_name}'  => 'Doe',
			'{customer_username}'   => 'john_doe',
			'{type}'       => 'Return',
			'{status}'     => 'Approved',
			'{return_address}' => $this->get_return_address_text( $settings ),
			'{store_address}'  => $this->get_store_address_text( $settings ),
			'{phone_number}'   => sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) ),
			'{return_guidelines}' => (string) ( $settings['return_guidelines'] ?? '' ),
		);

		$preview = $this->build_email_template_preview( $template, $settings, $tokens );
		wp_send_json_success( $preview );
	}

	private function enqueue_preview_styles(): void {
		wp_enqueue_style( 'returndesk-admin', RETURNDESK_PLUGIN_URL . 'admin/assets/admin.css', array(), RETURNDESK_VERSION );
		$this->enqueue_inline_styles();
	}

	private function build_email_template_preview( string $template, array $settings, array $tokens ): array {
		$template = in_array( $template, array( 'new_request_admin', 'new_request_customer', 'status_update', 'status_approved', 'status_rejected', 'status_cancelled' ), true ) ? $template : 'status_update';
		if ( 'status_approved' === $template ) {
			$tokens['{status}'] = 'Approved';
		} elseif ( 'status_rejected' === $template ) {
			$tokens['{status}'] = 'Rejected';
		} elseif ( 'status_cancelled' === $template ) {
			$tokens['{status}'] = 'Cancelled';
		}

		$to = 'customer@example.com';
		if ( 'new_request_admin' === $template ) {
			$to = sanitize_email( (string) ( $settings['admin_notification_email'] ?? '' ) );
			if ( ! is_email( $to ) ) {
				$to = sanitize_email( (string) get_option( 'admin_email' ) );
			}
			if ( ! is_email( $to ) ) {
				$to = 'admin@example.com';
			}
		}

		switch ( $template ) {
			case 'new_request_admin':
				$enabled    = ( 'yes' === ( $settings['email_enable_new_request_admin'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_new_request_admin'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_new_request_admin'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_new_request_admin'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_new_request_admin'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_new_request_admin'] ?? '' ), $tokens );
				break;
			case 'new_request_customer':
				$enabled    = ( 'yes' === ( $settings['email_enable_new_request_customer'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_new_request_customer'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_new_request_customer'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_new_request_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_new_request_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_new_request_customer'] ?? '' ), $tokens );
				break;
			case 'status_approved':
				$enabled    = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_status_approved_customer'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_status_approved_customer'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_status_approved_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_approved_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_approved_customer'] ?? '' ), $tokens );
				break;
			case 'status_rejected':
				$enabled    = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_status_rejected_customer'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_status_rejected_customer'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_status_rejected_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_rejected_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_rejected_customer'] ?? '' ), $tokens );
				break;
			case 'status_cancelled':
				$enabled    = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_status_cancelled_customer'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_status_cancelled_customer'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_status_cancelled_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_cancelled_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_cancelled_customer'] ?? '' ), $tokens );
				break;
			default:
				$enabled    = ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) );
				$subject    = strtr( (string) ( $settings['email_subject_status_update_customer'] ?? '' ), $tokens );
				$heading    = strtr( (string) ( $settings['email_heading_status_update_customer'] ?? '' ), $tokens );
				$content    = strtr( (string) ( $settings['email_body_status_update_customer'] ?? '' ), $tokens );
				$items_title = strtr( (string) ( $settings['email_items_title_status_update_customer'] ?? '' ), $tokens );
				$additional  = strtr( (string) ( $settings['email_additional_content_status_update_customer'] ?? '' ), $tokens );
				break;
		}

		$content = wp_kses_post( (string) $content );
		$heading = wp_strip_all_tags( (string) $heading );
		$subject = wp_strip_all_tags( (string) $subject );
		$items_title = wp_strip_all_tags( (string) $items_title );
		$additional  = wp_kses_post( (string) $additional );

		$items_table = $this->build_preview_items_table_html();

		$message  = wpautop( $content );
		if ( '' !== trim( $items_title ) ) {
			$message .= '<h2 class="returndesk-email-preview-heading">' . esc_html( $items_title ) . '</h2>';
		}
		$message .= $items_table;
		if ( '' !== trim( wp_strip_all_tags( $additional ) ) ) {
			$message .= wpautop( $additional );
		}

		if ( ! $enabled ) {
			$message = '<p class="returndesk-email-preview-disabled">' . esc_html__( 'This template is currently disabled.', 'windcodex-returndesk' ) . '</p>' . $message;
		}

		$html = $this->wrap_with_wc_email_template( $heading, $message );

		return array(
			'to'      => $to,
			'subject' => $subject,
			'html'    => $this->wrap_preview_shell( $html ),
		);
	}

	private function enqueue_inline_styles(): void {
		$styles  = '.returndesk-email-preview-heading{margin:18px 0 10px;font-size:16px;line-height:1.2;}';
		$styles .= '.returndesk-email-preview-disabled{color:#b45309;font-weight:700;}';

		wp_register_style( 'returndesk-inline-style', false, array(), RETURNDESK_VERSION );
		wp_enqueue_style( 'returndesk-inline-style' );
		wp_add_inline_style( 'returndesk-inline-style', $styles );
	}

	private function wrap_with_wc_email_template( string $heading, string $message, array $preferred_email_classes = array() ): string {
		$heading = wp_strip_all_tags( $heading );
		$message = wp_kses_post( $message );

		if ( function_exists( 'WC' ) && WC() ) {
			$mailer = WC()->mailer();
			if ( $mailer ) {
				$email = $this->get_wc_email_context( $mailer, $preferred_email_classes );

				ob_start();
				// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email hooks.
				do_action( 'woocommerce_email_header', $heading, $email );
				echo wp_kses_post( $message );
				do_action( 'woocommerce_email_footer', $email );
				// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$wrapped = (string) ob_get_clean();

				if ( '' !== trim( $wrapped ) && method_exists( $mailer, 'style_inline' ) ) {
					return (string) $mailer->style_inline( $wrapped );
				}
				if ( '' !== trim( $wrapped ) ) {
					return $wrapped;
				}

				if ( method_exists( $mailer, 'wrap_message' ) ) {
					$wrapped = (string) $mailer->wrap_message( $heading, $message );
					if ( method_exists( $mailer, 'style_inline' ) ) {
						return (string) $mailer->style_inline( $wrapped );
					}
					return $wrapped;
				}
			}
		}

		return '<div>' . $message . '</div>';
	}

	private function wrap_preview_shell( string $html ): string {
		if ( '' === trim( $html ) ) {
			return $html;
		}
		if ( false !== stripos( $html, '<html' ) ) {
			return $this->inject_preview_viewport_meta( $html );
		}

		ob_start();
		wp_print_styles( array( 'returndesk-admin', 'returndesk-inline-style' ) );
		$styles_html = (string) ob_get_clean();

		return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' . $styles_html . '</head><body><div class="returndesk-email-preview-shell">' . $html . '</div></body></html>';
	}

	private function inject_preview_viewport_meta( string $html ): string {
		if ( false !== stripos( $html, 'name="viewport"' ) ) {
			return $html;
		}

		return preg_replace(
			'/(<head\b[^>]*>)/i',
			'$1<meta name="viewport" content="width=device-width, initial-scale=1">',
			$html,
			1
		) ?: $html;
	}

	/**
	 * Pick a WooCommerce email object as template context so customizers can apply styles.
	 *
	 * @param mixed $mailer
	 * @return WC_Email|false
	 */
	private function get_wc_email_context( $mailer, array $preferred_email_classes = array() ) {
		if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'get_emails' ) ) {
			return false;
		}

		$emails = (array) $mailer->get_emails();
		$preferred_email_classes = ! empty( $preferred_email_classes )
			? $preferred_email_classes
			: array( 'WC_Email_New_Order', 'WC_Email_Customer_Processing_Order' );
		foreach ( $preferred_email_classes as $preferred ) {
			foreach ( $emails as $email ) {
				if ( is_object( $email ) && is_a( $email, $preferred ) ) {
					return $email;
				}
			}
		}
		foreach ( $emails as $email ) {
			if ( is_object( $email ) && is_a( $email, 'WC_Email' ) ) {
				return $email;
			}
		}

		return false;
	}

	private function build_preview_items_table_html(): string {
		$table  = '<table cellspacing="0" cellpadding="6" border="1" width="100%" style="width:100%;font-family:Helvetica Neue, Helvetica, Roboto, Arial, sans-serif;border:1px solid #e5e5e5;margin:0 0 16px;border-collapse:collapse;">';
		$table .= '<thead><tr>';
		$table .= '<th scope="col" style="text-align:left;border:1px solid #e5e5e5;background-color:#f7f7f7;padding:12px;font-size:12px;">' . esc_html__( 'Product', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th scope="col" style="text-align:left;border:1px solid #e5e5e5;background-color:#f7f7f7;padding:12px;font-size:12px;">' . esc_html__( 'Price', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th scope="col" style="text-align:left;border:1px solid #e5e7eb;background-color:#f7f7f7;padding:12px;font-size:12px;">' . esc_html__( 'Return Quantity', 'windcodex-returndesk' ) . '</th>';
		$table .= '</tr></thead><tbody><tr>';
		$table .= '<td style="text-align:left;border:1px solid #e5e5e5;padding:12px;font-size:12px;">' . esc_html__( 'T-shirt', 'windcodex-returndesk' ) . '</td>';
		$table .= '<td style="text-align:left;border:1px solid #e5e5e5;padding:12px;font-size:12px;">$14.95</td>';
		$table .= '<td style="text-align:left;border:1px solid #e5e5e5;padding:12px;font-size:12px;">1</td>';
		$table .= '</tr></tbody></table>';

		return $table;
	}

	public function plugin_action_links( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::ADMIN_PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'windcodex-returndesk' ) . '</a>'
		);
		return $links;
	}
}
