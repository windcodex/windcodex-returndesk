<?php
/**
 * Return rules engine.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Restrictions {

	private static function get_store_address_defaults(): array {
		$default_country = (string) get_option( 'woocommerce_default_country', '' );
		$country         = $default_country;
		$state           = '';
		if ( strpos( $default_country, ':' ) !== false ) {
			$parts   = explode( ':', $default_country, 2 );
			$country = (string) ( $parts[0] ?? '' );
			$state   = (string) ( $parts[1] ?? '' );
		}

		$country = preg_replace( '/[^A-Z]/', '', strtoupper( (string) $country ) );
		$state   = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $state ) );

		return array(
			'return_store_address_1' => sanitize_text_field( (string) get_option( 'woocommerce_store_address', '' ) ),
			'return_store_address_2' => sanitize_text_field( (string) get_option( 'woocommerce_store_address_2', '' ) ),
			'return_store_city'      => sanitize_text_field( (string) get_option( 'woocommerce_store_city', '' ) ),
			'return_store_state'     => sanitize_text_field( (string) $state ),
			'return_store_country'   => sanitize_text_field( (string) $country ),
			'return_store_postcode'  => sanitize_text_field( (string) get_option( 'woocommerce_store_postcode', '' ) ),
			'return_store_phone'     => '',
		);
	}

	public static function get_default_settings(): array {
		$store_defaults = self::get_store_address_defaults();

		return array(
			'enable_returns'         => 'yes',
			'return_window_days'     => 7,
			'allowed_statuses'       => array( 'completed', 'processing' ),
			'customer_message'       => __( 'Your return request has been submitted and is being reviewed.', 'windcodex-returndesk' ),
			'return_allow_sale_products' => 'yes',
			'return_guidelines'      => __( "Ensure all items are securely packed.\nShare tracking details after shipment.\nRefund is processed after inspection.", 'windcodex-returndesk' ),
			'return_reasons'         => "Product damaged\nWrong item sent\nItem defective\nMissing parts",
			'terms_page_id'          => 0,
			'return_store_address_1' => $store_defaults['return_store_address_1'],
			'return_store_address_2' => $store_defaults['return_store_address_2'],
			'return_store_city'      => $store_defaults['return_store_city'],
			'return_store_state'     => $store_defaults['return_store_state'],
			'return_store_country'   => $store_defaults['return_store_country'],
			'return_store_postcode'  => $store_defaults['return_store_postcode'],
			'return_store_phone'     => $store_defaults['return_store_phone'],
			'admin_notification_email'             => '',
			'email_enable_new_request_admin'       => 'yes',
			'email_enable_new_request_customer'    => 'yes',
			'email_enable_status_update_customer'  => 'yes',
			'email_subject_new_request_admin'      => __( 'New Return Request for Order #{order_id}', 'windcodex-returndesk' ),
			'email_body_new_request_admin'         => __( 'A new {type} request was submitted. Request #{request_id}, Order #{order_id}, Status: {status}.', 'windcodex-returndesk' ),
			'email_heading_new_request_admin'      => __( 'New Return Request Received', 'windcodex-returndesk' ),
			'email_items_title_new_request_admin'  => __( 'Requested Items', 'windcodex-returndesk' ),
			'email_additional_content_new_request_admin' => __( 'You can manage this request from your admin dashboard.', 'windcodex-returndesk' ),
			'email_subject_new_request_customer'   => __( 'Your Return Request for Order #{order_id}', 'windcodex-returndesk' ),
			'email_body_new_request_customer'      => __( 'Your request #{request_id} has been submitted. Current status: {status}.', 'windcodex-returndesk' ),
			'email_heading_new_request_customer'   => __( 'Your Return Request Received!', 'windcodex-returndesk' ),
			'email_items_title_new_request_customer' => __( 'Requested Return Items', 'windcodex-returndesk' ),
			'email_additional_content_new_request_customer' => __( 'Thank you, {site_title}', 'windcodex-returndesk' ),
			'email_subject_status_update_customer' => __( 'Update for Your Request on Order #{order_id}', 'windcodex-returndesk' ),
			'email_body_status_update_customer'    => __( 'Your request #{request_id} ({type}) is now {status}.', 'windcodex-returndesk' ),
			'email_heading_status_update_customer' => __( 'Your Return Request Update', 'windcodex-returndesk' ),
			'email_items_title_status_update_customer' => __( 'Requested Return Items', 'windcodex-returndesk' ),
			'email_additional_content_status_update_customer' => __( 'Thank you, {site_title}', 'windcodex-returndesk' ),
			'email_subject_status_approved_customer' => __( 'Your Return Request Has Been Approved (Order #{order_id})', 'windcodex-returndesk' ),
			'email_body_status_approved_customer'    => __( "Good news! Your request #{request_id} has been approved.\n\nNext steps:\n{return_guidelines}", 'windcodex-returndesk' ),
			'email_heading_status_approved_customer' => __( 'Return Request Approved', 'windcodex-returndesk' ),
			'email_items_title_status_approved_customer' => __( 'Approved Return Items', 'windcodex-returndesk' ),
			'email_additional_content_status_approved_customer' => __( 'If you have questions, reply to this email or contact our support team.', 'windcodex-returndesk' ),
			'email_subject_status_rejected_customer' => __( 'Your Return Request Was Rejected (Order #{order_id})', 'windcodex-returndesk' ),
			'email_body_status_rejected_customer'    => __( "Your request #{request_id} could not be approved at this time.\n\nIf you believe this is a mistake, please contact us with your order details.", 'windcodex-returndesk' ),
			'email_heading_status_rejected_customer' => __( 'Return Request Rejected', 'windcodex-returndesk' ),
			'email_items_title_status_rejected_customer' => __( 'Requested Return Items', 'windcodex-returndesk' ),
			'email_additional_content_status_rejected_customer' => __( 'Thank you, {site_title}', 'windcodex-returndesk' ),
			'email_subject_status_cancelled_customer' => __( 'Your Return Request Was Cancelled (Order #{order_id})', 'windcodex-returndesk' ),
			'email_body_status_cancelled_customer'    => __( 'Your request #{request_id} has been cancelled.', 'windcodex-returndesk' ),
			'email_heading_status_cancelled_customer' => __( 'Return Request Cancelled', 'windcodex-returndesk' ),
			'email_items_title_status_cancelled_customer' => __( 'Requested Return Items', 'windcodex-returndesk' ),
			'email_additional_content_status_cancelled_customer' => __( 'Thank you, {site_title}', 'windcodex-returndesk' ),
		);
	}

	public function get_settings(): array {
		$saved = (array) get_option( defined( 'RETURNDESK_SETTINGS_OPTION' ) ? RETURNDESK_SETTINGS_OPTION : 'returndesk_settings', array() );
		$settings  = array_merge( self::get_default_settings(), $saved );

		return $settings;
	}

	public function is_order_status_allowed( WC_Order $order ): bool {
		$settings = $this->get_settings();
		$allowed  = array_map( 'sanitize_key', (array) $settings['allowed_statuses'] );
		return in_array( $order->get_status(), $allowed, true );
	}

	public function is_item_eligible( WC_Order $order, int $item_id, string $request_type ): array {
		$settings     = $this->get_settings();
		$item         = $order->get_item( $item_id );

		if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return array( 'eligible' => false, 'message' => __( 'Invalid order item.', 'windcodex-returndesk' ) );
		}

		$product_id = $item->get_product_id();
		if ( ! $product_id ) {
			return array( 'eligible' => false, 'message' => __( 'This item is not eligible for requests.', 'windcodex-returndesk' ) );
		}

		$product = $item->get_product();
		if ( $product instanceof WC_Product && 'yes' !== ( $settings['return_allow_sale_products'] ?? 'yes' ) && $product->is_on_sale() ) {
			return array( 'eligible' => false, 'message' => __( 'Sale products are not eligible for return.', 'windcodex-returndesk' ) );
		}

		if ( 'yes' !== $settings['enable_returns'] ) {
			return array( 'eligible' => false, 'message' => __( 'Returns are currently disabled.', 'windcodex-returndesk' ) );
		}

		if ( ! $this->is_order_status_allowed( $order ) ) {
			return array( 'eligible' => false, 'message' => __( 'Order status is not eligible for return.', 'windcodex-returndesk' ) );
		}

		$window_days = max( 1, absint( $settings['return_window_days'] ) );

		$date_created = $order->get_date_completed() ?: $order->get_date_paid() ?: $order->get_date_created();
		$days_since   = $date_created ? floor( ( time() - $date_created->getTimestamp() ) / DAY_IN_SECONDS ) : 999999;
		if ( $days_since > $window_days ) {
			return array(
				'eligible' => false,
				'message'  => sprintf(
					/* translators: %d: days */
					__( 'Request window expired. Allowed within %d days.', 'windcodex-returndesk' ),
					$window_days
				),
			);
		}

		$ordered_qty = max( 1, (int) $item->get_quantity() );
		$used_qty    = $this->get_requested_quantity( (int) $order->get_id(), $item_id );
		$max_qty     = max( 0, $ordered_qty - $used_qty );
		if ( $max_qty <= 0 ) {
			return array( 'eligible' => false, 'message' => __( 'A request already exists for full quantity.', 'windcodex-returndesk' ) );
		}

		return array(
			'eligible'   => true,
			'max_qty'    => $max_qty,
			'product_id' => $product_id,
			'message'    => '',
		);
	}

	public function get_requested_quantity( int $order_id, int $item_id ): int {
		$order_id = absint( $order_id );
		$item_id  = absint( $item_id );
		if ( $order_id <= 0 || $item_id <= 0 ) {
			return 0;
		}

		// Prefer custom table when available (supports multi-item requests).
		if ( class_exists( 'ReturnDesk_Requests_Store' ) ) {
			global $wpdb;

			$table = ReturnDesk_Requests_Store::table_name();
			$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
					"SELECT items_json, status FROM {$table} WHERE order_id = %d",
					$order_id
				),
				ARRAY_A
			);

			$total = 0;
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
				// Allow resubmission when rejected/cancelled.
				if ( ! in_array( $status, array( 'pending', 'approved' ), true ) ) {
					continue;
				}

				$items = json_decode( (string) ( $row['items_json'] ?? '' ), true );
				if ( ! is_array( $items ) ) {
					continue;
				}
				foreach ( $items as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$entry_item_id = absint( $entry['order_item_id'] ?? 0 );
					if ( $entry_item_id !== $item_id ) {
						continue;
					}
					$total += max( 0, absint( $entry['qty'] ?? 0 ) );
				}
			}

			return $total;
		}

		return 0;
	}

	public static function get_request_statuses(): array {
		return array(
			'pending'   => __( 'Return Request', 'windcodex-returndesk' ),
			'approved'  => __( 'Return Approved', 'windcodex-returndesk' ),
			'rejected'  => __( 'Return Rejected', 'windcodex-returndesk' ),
			'cancelled' => __( 'Return Cancelled', 'windcodex-returndesk' ),
		);
	}

}
