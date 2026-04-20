<?php
/**
 * Frontend return portal and request submission.
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Frontend {

	/** @var ReturnDesk_Restrictions */
	private $restrictions;

	/** @var array<string, array> */
	private $order_requests_cache = array();

	/** @var string */
	private $attachment_upload_subdir = '';

	/**
	 * @return array{0:string,1:string} [reason, details]
	 */
	private function split_reason_and_details( string $reason_raw ): array {
		$reason_raw = trim( (string) $reason_raw );
		if ( '' === $reason_raw ) {
			return array( '', '' );
		}

		$parts = preg_split( "/\\R\\R+/", $reason_raw, 2 );
		if ( is_array( $parts ) && count( $parts ) === 2 ) {
			return array( trim( (string) $parts[0] ), trim( (string) $parts[1] ) );
		}

		$lines = preg_split( "/\\R+/", $reason_raw, 2 );
		if ( is_array( $lines ) && count( $lines ) === 2 ) {
			return array( trim( (string) $lines[0] ), trim( (string) $lines[1] ) );
		}

		return array( $reason_raw, '' );
	}

	private function render_request_reason_block( string $reason_raw ): void {
		list( $reason, $details ) = $this->split_reason_and_details( $reason_raw );
		if ( '' === $reason && '' === $details ) {
			return;
		}

		if ( '' !== $reason ) {
			echo '<div class="returndesk-request-reason"><strong>' . esc_html__( 'Reason:', 'windcodex-returndesk' ) . '</strong> ' . esc_html( $reason ) . '</div>';
		}
		if ( '' !== $details ) {
			$details_html = nl2br( esc_html( $details ) );
			echo '<div class="returndesk-request-details"><strong>' . esc_html__( 'Details:', 'windcodex-returndesk' ) . '</strong> ' . wp_kses( $details_html, array( 'br' => array() ) ) . '</div>';
		}
	}

	/**
	 * @return string|WP_Error
	 */
	private function parse_reason_from_post() {
		$post   = $this->get_post_data();
		$reason = isset( $post['reason'] ) ? sanitize_textarea_field( $post['reason'] ) : '';
		if ( '__other__' === $reason ) {
			$other = isset( $post['reason_other'] ) ? sanitize_text_field( $post['reason_other'] ) : '';
			$other = trim( (string) $other );
			if ( '' === $other ) {
				return new WP_Error( 'reason_other', __( 'Please enter your reason.', 'windcodex-returndesk' ) );
			}
			$reason = $other;
		}

		$reason = trim( (string) $reason );
		if ( '' === $reason ) {
			return new WP_Error( 'reason', __( 'Please select a reason.', 'windcodex-returndesk' ) );
		}

		return $reason;
	}

	/**
	 * @return string|WP_Error
	 */
	private function parse_details_from_post() {
		$post    = $this->get_post_data();
		$details = isset( $post['details'] ) ? sanitize_textarea_field( $post['details'] ) : '';
		$details = trim( (string) $details );
		if ( '' === $details ) {
			return new WP_Error( 'details', __( 'Please enter request details.', 'windcodex-returndesk' ) );
		}
		return $details;
	}

	public function __construct( ReturnDesk_Restrictions $restrictions ) {
		$this->restrictions = $restrictions;
	}

	private function get_post_data(): array {
		$data = filter_input_array( INPUT_POST, FILTER_DEFAULT );
		return is_array( $data ) ? $data : array();
	}

	private function get_query_data(): array {
		$data = filter_input_array( INPUT_GET, FILTER_DEFAULT );
		return is_array( $data ) ? $data : array();
	}

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

		if ( ! $should_enqueue && function_exists( 'has_shortcode' ) ) {
			global $post;
			$content        = ( $post && isset( $post->post_content ) ) ? (string) $post->post_content : '';
			$should_enqueue = ( '' !== $content ) && has_shortcode( $content, 'returndesk_portal' );
		}

		if ( ! $should_enqueue ) {
			return;
		}

		$styles  = '.returndesk-select{display:block !important;width:100% !important;appearance:auto !important;-webkit-appearance:menulist !important;-moz-appearance:menulist !important;background:#fff !important;border:1.5px solid #cbd5e1 !important;border-radius:10px !important;padding:6px 10px !important;min-height:38px !important;}';
		$styles .= '.returndesk-reason-other{display:none;margin-top:10px;}';

		wp_register_style( 'returndesk-inline-style', false, array(), RETURNDESK_VERSION );
		wp_enqueue_style( 'returndesk-inline-style' );
		wp_add_inline_style( 'returndesk-inline-style', $styles );
	}

	public static function add_account_endpoint(): void {
		add_rewrite_endpoint( 'returndesk-requests', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'returndesk-returns', EP_ROOT | EP_PAGES );
	}

	public function query_vars( array $vars ): array {
		$vars[] = 'returndesk-requests';
		$vars[] = 'returndesk-returns';
		return $vars;
	}

	public function add_my_account_menu_item( array $items ): array {
		$new = array();
		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'orders' === $key ) {
				$new['returndesk-requests'] = __( 'Returns', 'windcodex-returndesk' );
			}
		}

		if ( ! isset( $new['returndesk-requests'] ) ) {
			$new['returndesk-requests'] = __( 'Returns', 'windcodex-returndesk' );
		}

		return $new;
	}

	public function endpoint_content(): void {
		echo $this->get_portal_html( get_current_user_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function endpoint_returns_content(): void {
		echo $this->get_start_return_endpoint_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function maybe_handle_request_submission(): void {
		$post = $this->get_post_data();
		if ( isset( $post['returndesk_bulk_submit_request'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_logged_in_bulk_submission().
			if ( is_user_logged_in() ) {
				$this->handle_logged_in_bulk_submission();
				return;
			}
			wc_add_notice( __( 'Please login to submit a return request.', 'windcodex-returndesk' ), 'error' );
			return;
		}

		if ( isset( $post['returndesk_cancel_request'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in cancel handlers.
			if ( is_user_logged_in() ) {
				$this->handle_logged_in_cancel();
				return;
			}

			if ( isset( $post['returndesk_guest_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle_guest_cancel().
				$this->handle_guest_cancel();
				return;
			}
		}
	}

	public function order_view_after_order_table( $order ): void {
		if ( ! ( $order instanceof WC_Order ) || ! is_user_logged_in() ) {
			return;
		}

		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'view-order' ) ) {
			return;
		}

		$current_user = get_current_user_id();
		if ( $current_user <= 0 || (int) $order->get_user_id() !== $current_user ) {
			return;
		}

		$settings = $this->restrictions->get_settings();
		if ( 'yes' !== ( $settings['enable_returns'] ?? 'yes' ) ) {
			return;
		}

		$has_eligible = false;
		foreach ( $order->get_items() as $item_id => $item ) {
			$check = $this->restrictions->is_item_eligible( $order, (int) $item_id, 'return' );
			if ( ! empty( $check['eligible'] ) ) {
				$has_eligible = true;
				break;
			}
		}

		$requests            = $this->get_cached_order_requests_for_customer( $current_user, (int) $order->get_id() );
		$has_existing_request = ! empty( $requests );

		echo '<div class="returndesk-portal">';
		wc_print_notices();
		echo '<div class="returndesk-order-actions">';
		if ( ! $has_existing_request && $has_eligible ) {
			$url = $this->get_start_return_url( (int) $order->get_id() );
			echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Return Order', 'windcodex-returndesk' ) . '</a>';
		}
		echo '</div>';

		$query = $this->get_query_data();
		$request_filter_id = isset( $query['rg_request_id'] ) ? absint( $query['rg_request_id'] ) : 0;
		$this->render_customer_order_request_list( $current_user, (int) $order->get_id(), $requests, $request_filter_id );
		echo '</div>';
	}

	private function handle_logged_in_bulk_submission(): void {
		check_admin_referer( 'returndesk_bulk_submit_request', 'returndesk_bulk_nonce' );
		$post = $this->get_post_data();

		$user_id  = get_current_user_id();
		$order_id = isset( $post['order_id'] ) ? absint( $post['order_id'] ) : 0;
		$reason   = $this->parse_reason_from_post();
		if ( is_wp_error( $reason ) ) {
			wc_add_notice( $reason->get_error_message(), 'error' );
			$return_to = isset( $post['returndesk_return_to'] ) ? esc_url_raw( $post['returndesk_return_to'] ) : '';
			$this->redirect_back_to( $return_to );
		}
		$details  = $this->parse_details_from_post();
		if ( is_wp_error( $details ) ) {
			wc_add_notice( $details->get_error_message(), 'error' );
			$return_to = isset( $post['returndesk_return_to'] ) ? esc_url_raw( $post['returndesk_return_to'] ) : '';
			$this->redirect_back_to( $return_to );
		}
		$reason = trim( $reason . "\n\n" . $details );
		$return_to = isset( $post['returndesk_return_to'] ) ? esc_url_raw( $post['returndesk_return_to'] ) : '';

		$order = wc_get_order( $order_id );
		if ( ! $order || (int) $order->get_user_id() !== $user_id ) {
			wc_add_notice( __( 'Invalid order selected.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back_to( $return_to );
		}

		$terms_validation = $this->validate_terms_acceptance();
		if ( is_wp_error( $terms_validation ) ) {
			wc_add_notice( $terms_validation->get_error_message(), 'error' );
			$this->redirect_back_to( $return_to );
		}

		if ( $this->order_has_any_request_for_customer( $user_id, (int) $order->get_id() ) ) {
			wc_add_notice( __( 'A return request already exists for this order.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back_to( $return_to );
		}

		// Free version: return request always includes full eligible quantities (no partial order selection).
		$selected = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$eligibility = $this->restrictions->is_item_eligible( $order, (int) $item_id, 'return' );
			if ( empty( $eligibility['eligible'] ) ) {
				continue;
			}
			$selected[ (int) $item_id ] = max( 1, absint( $eligibility['max_qty'] ?? 1 ) );
		}

		if ( empty( $selected ) ) {
			wc_add_notice( __( 'No items in this order are currently eligible for return.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back_to( $return_to );
		}

		foreach ( $selected as $item_id => $qty ) {
			$eligibility = $this->restrictions->is_item_eligible( $order, (int) $item_id, 'return' );
			if ( empty( $eligibility['eligible'] ) ) {
				wc_add_notice( $eligibility['message'] ?? __( 'One or more selected items are not eligible.', 'windcodex-returndesk' ), 'error' );
				$this->redirect_back_to( $return_to );
			}
			if ( $qty > (int) ( $eligibility['max_qty'] ?? 0 ) ) {
				wc_add_notice( __( 'Requested quantity exceeds available quantity.', 'windcodex-returndesk' ), 'error' );
				$this->redirect_back_to( $return_to );
			}
		}

		$attachment_ids = $this->upload_attachments_from_field( 'returndesk_attachment_bulk' );
		$items = array();
		foreach ( $selected as $item_id => $qty ) {
			$eligibility = $this->restrictions->is_item_eligible( $order, (int) $item_id, 'return' );
			$items[]     = array(
				'order_item_id' => absint( $item_id ),
				'product_id'    => absint( $eligibility['product_id'] ?? 0 ),
				'qty'           => absint( $qty ),
			);
		}

		$created = $this->create_request_from_order_items(
			$order,
			$items,
			'return',
			$reason,
			$user_id,
			'',
			$attachment_ids,
			'payment_method'
		);
		if ( is_wp_error( $created ) ) {
			wc_add_notice( $created->get_error_message(), 'error' );
			$this->redirect_back_to( $return_to );
		}

		$settings = $this->restrictions->get_settings();
		wc_add_notice( $settings['customer_message'] ?? __( 'Request submitted successfully.', 'windcodex-returndesk' ), 'success' );
		$this->redirect_back_to( $return_to );
	}

	private function redirect_back_to( string $url ): void {
		$url = (string) $url;
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
			exit;
		}
		$this->redirect_back();
	}

	private function upload_attachment_from_field( string $field_name ): int {
		$attachments = $this->upload_attachments_from_field( $field_name );
		$first       = (int) ( $attachments[0] ?? 0 );
		return $first > 0 ? $first : 0;
	}

	/**
	 * @return int[]
	 */
	private function upload_attachments_from_field( string $field_name ): array {
		if ( empty( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified in submission handlers; file array is validated below.
			return array();
		}

		$files = $_FILES[ $field_name ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified in submission handlers; values are validated before use.
		if ( ! isset( $files['tmp_name'] ) || empty( $files['tmp_name'] ) ) {
			return array();
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$max_size     = (int) wp_max_upload_size();
		$allowed_exts = array( 'jpg', 'jpeg', 'png', 'webp', 'pdf' );

		$attachment_ids = array();
		$original_files = $_FILES[ $field_name ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserving original upload payload for restoration.
		$this->begin_attachment_upload_dir();

		try {
			$names = $files['name'] ?? '';
			if ( is_array( $names ) ) {
				$count = count( $names );
				for ( $i = 0; $i < $count; $i++ ) {
					$single = array(
						'name'     => (string) ( $files['name'][ $i ] ?? '' ),
						'type'     => (string) ( $files['type'][ $i ] ?? '' ),
						'tmp_name' => (string) ( $files['tmp_name'][ $i ] ?? '' ),
						'error'    => (int) ( $files['error'][ $i ] ?? 0 ),
						'size'     => (int) ( $files['size'][ $i ] ?? 0 ),
					);

					if ( '' === $single['tmp_name'] ) {
						continue;
					}

					if ( ! empty( $single['error'] ) ) {
						$message = function_exists( 'wp_upload_errstr' ) ? (string) wp_upload_errstr( (int) $single['error'] ) : '';
						if ( '' === $message ) {
							$message = __( 'Upload failed. Please try again.', 'windcodex-returndesk' );
						}
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $message, 'error' );
						}
						continue;
					}

					if ( $max_size > 0 && $single['size'] > $max_size ) {
						$message = sprintf(
							/* translators: 1: max file size. */
							__( 'File is too large. Maximum upload size is %s.', 'windcodex-returndesk' ),
							size_format( $max_size )
						);
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $message, 'error' );
						}
						continue;
					}

					$ext = strtolower( (string) pathinfo( $single['name'], PATHINFO_EXTENSION ) );
					if ( '' !== $ext && ! in_array( $ext, $allowed_exts, true ) ) {
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( __( 'File type is not allowed.', 'windcodex-returndesk' ), 'error' );
						}
						continue;
					}

					$_FILES[ $field_name ] = $single;
					$id                   = media_handle_upload( $field_name, 0 );
					if ( is_wp_error( $id ) || ! $id ) {
						$message = is_wp_error( $id ) ? $id->get_error_message() : __( 'Upload failed. Please try again.', 'windcodex-returndesk' );
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $message, 'error' );
						}
						continue;
					}

					$attachment_ids[] = absint( $id );
				}
			} else {
				$single = array(
					'name'     => (string) ( $files['name'] ?? '' ),
					'type'     => (string) ( $files['type'] ?? '' ),
					'tmp_name' => (string) ( $files['tmp_name'] ?? '' ),
					'error'    => (int) ( $files['error'] ?? 0 ),
					'size'     => (int) ( $files['size'] ?? 0 ),
				);

				if ( '' !== $single['tmp_name'] ) {
					if ( ! empty( $single['error'] ) ) {
						$message = function_exists( 'wp_upload_errstr' ) ? (string) wp_upload_errstr( (int) $single['error'] ) : '';
						if ( '' === $message ) {
							$message = __( 'Upload failed. Please try again.', 'windcodex-returndesk' );
						}
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $message, 'error' );
						}
					} else {
						$ext = strtolower( (string) pathinfo( $single['name'], PATHINFO_EXTENSION ) );
						if ( '' !== $ext && ! in_array( $ext, $allowed_exts, true ) ) {
							if ( function_exists( 'wc_add_notice' ) ) {
								wc_add_notice( __( 'File type is not allowed.', 'windcodex-returndesk' ), 'error' );
							}
						} else {
							$_FILES[ $field_name ] = $single;
							$id                   = media_handle_upload( $field_name, 0 );
							if ( ! is_wp_error( $id ) && $id ) {
								$attachment_ids[] = absint( $id );
							} else {
								$message = is_wp_error( $id ) ? $id->get_error_message() : __( 'Upload failed. Please try again.', 'windcodex-returndesk' );
								if ( function_exists( 'wc_add_notice' ) ) {
									wc_add_notice( $message, 'error' );
								}
							}
						}
					}
				}
			}
		} finally {
			$_FILES[ $field_name ] = $original_files;
			$this->end_attachment_upload_dir();
		}

		return array_values( array_filter( array_map( 'absint', $attachment_ids ) ) );
	}

	private function begin_attachment_upload_dir(): void {
		$this->attachment_upload_subdir = 'returndesk-attachments';
		add_filter( 'upload_dir', array( $this, 'filter_attachment_upload_dir' ) );
	}

	private function end_attachment_upload_dir(): void {
		remove_filter( 'upload_dir', array( $this, 'filter_attachment_upload_dir' ) );
		$this->attachment_upload_subdir = '';
	}

	public function filter_attachment_upload_dir( array $dirs ): array {
		if ( '' === $this->attachment_upload_subdir ) {
			return $dirs;
		}

		$subdir          = '/' . trim( $this->attachment_upload_subdir, '/' );
		$dirs['subdir']   = $subdir;
		$dirs['path']     = trailingslashit( $dirs['basedir'] ) . ltrim( $subdir, '/' );
		$dirs['url']      = trailingslashit( $dirs['baseurl'] ) . ltrim( $subdir, '/' );
		$dirs['error']    = false;
		$dirs['filename'] = $dirs['filename'] ?? '';

		return $dirs;
	}

private function create_request_from_order_items( WC_Order $order, array $items, string $request_type, string $reason, int $customer_id = 0, string $guest_email = '', array $attachment_ids = array(), string $refund_method = 'payment_method' ) {
		$request_type = 'return';
		if ( ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			return new WP_Error( 'storage_missing', __( 'Request storage is unavailable.', 'windcodex-returndesk' ) );
		}

		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( empty( $items ) ) {
			return new WP_Error( 'no_items', __( 'Please select at least one item.', 'windcodex-returndesk' ) );
		}

		foreach ( $items as $entry ) {
			$order_item_id = absint( $entry['order_item_id'] ?? 0 );
			$qty           = max( 1, absint( $entry['qty'] ?? 0 ) );
			$eligibility   = $this->restrictions->is_item_eligible( $order, $order_item_id, $request_type );
			if ( empty( $eligibility['eligible'] ) ) {
				return new WP_Error( 'not_eligible', $eligibility['message'] ?? __( 'One or more selected items are not eligible.', 'windcodex-returndesk' ) );
			}
			if ( $qty > (int) ( $eligibility['max_qty'] ?? 0 ) ) {
				return new WP_Error( 'qty', __( 'Requested quantity exceeds available quantity.', 'windcodex-returndesk' ) );
			}
		}

		$status = 'pending';
		$store  = new ReturnDesk_Requests_Store();
		$id     = $store->insert(
			array(
				'order_id'        => absint( $order->get_id() ),
				'customer_id'     => $customer_id > 0 ? absint( $customer_id ) : null,
				'guest_email'     => '' !== $guest_email ? sanitize_email( $guest_email ) : null,
				'request_type'    => sanitize_key( $request_type ),
				'status'          => $status,
				'reason'          => (string) $reason,
				'items'           => $items,
				'attachment_ids'  => array_values( array_filter( array_map( 'absint', $attachment_ids ) ) ),
				'refund_method'   => sanitize_key( (string) $refund_method ),
			)
		);

		if ( $id <= 0 ) {
			return new WP_Error( 'create_failed', __( 'Could not create request. Please try again.', 'windcodex-returndesk' ) );
		}

		$this->send_request_created_emails( $id, $order, $request_type, $status, $items, $guest_email );
		return $id;
	}

	private function send_request_created_emails( int $request_id, WC_Order $order, string $request_type, string $status, array $items, string $guest_email = '' ): void {
		$order_id   = absint( $order->get_id() );
		$type_label = __( 'Return', 'windcodex-returndesk' );
		$settings   = $this->restrictions->get_settings();
		$admin_mail = sanitize_email( (string) ( $settings['admin_notification_email'] ?? '' ) );
		if ( ! is_email( $admin_mail ) ) {
			$admin_mail = sanitize_email( (string) get_option( 'admin_email' ) );
		}

		$date_created = $order->get_date_created();
		$order_date   = $date_created ? wc_format_datetime( $date_created ) : '';
		$first_name   = sanitize_text_field( (string) $order->get_billing_first_name() );
		$last_name    = sanitize_text_field( (string) $order->get_billing_last_name() );
		$full_name    = trim( $first_name . ' ' . $last_name );
		$username     = '';
		$user_id      = absint( $order->get_user_id() );
		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user instanceof WP_User ) {
				$username = sanitize_user( (string) $user->user_login );
			}
		}

		$tokens = array(
			'{request_id}' => (string) $request_id,
			'{order_id}'   => (string) $order_id,
			'{order_number}' => (string) $order->get_order_number(),
			'{order_date}'   => (string) $order_date,
			'{site_title}'   => (string) get_bloginfo( 'name' ),
			'{customer_full_name}'  => (string) $full_name,
			'{customer_first_name}' => (string) $first_name,
			'{customer_last_name}'  => (string) $last_name,
			'{customer_username}'   => (string) $username,
			'{type}'       => $type_label,
			'{status}'     => ucfirst( $status ),
			'{return_address}' => $this->get_return_address_text( $settings ),
			'{store_address}' => $this->get_return_address_text( $settings ),
			'{phone_number}'  => sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) ),
		);

		$admin_subject = $this->render_email_template( (string) ( $settings['email_subject_new_request_admin'] ?? '' ), $tokens );
		$admin_heading = $this->render_email_template( (string) ( $settings['email_heading_new_request_admin'] ?? '' ), $tokens );
		$admin_content = $this->render_email_template( (string) ( $settings['email_body_new_request_admin'] ?? '' ), $tokens );
		$admin_items_title = $this->render_email_template( (string) ( $settings['email_items_title_new_request_admin'] ?? '' ), $tokens );
		$admin_additional  = $this->render_email_template( (string) ( $settings['email_additional_content_new_request_admin'] ?? '' ), $tokens );
		$admin_items_table = $this->build_request_items_table_html( $order, $items );
		$admin_html = $this->build_email_html( (string) get_bloginfo( 'name' ), $admin_heading, $admin_content, $admin_items_title, $admin_items_table, $admin_additional );

		if ( 'yes' === ( $settings['email_enable_new_request_admin'] ?? 'yes' ) && ! empty( $admin_subject ) && ! empty( $admin_content ) && is_email( $admin_mail ) ) {
			$sent = $this->send_html_email( $admin_mail, $admin_subject, $admin_html );
			$this->log_email_attempt( 'new_request_admin', $admin_mail, $admin_subject, $sent ? 'sent' : 'failed' );
		} else {
			$this->log_email_attempt( 'new_request_admin', $admin_mail, $admin_subject, 'skipped' );
		}

		$customer_mail = $this->get_request_customer_email_from_order( $order, $guest_email );
		if ( is_email( $customer_mail ) ) {
			$customer_subject = $this->render_email_template( (string) ( $settings['email_subject_new_request_customer'] ?? '' ), $tokens );
			$customer_heading = $this->render_email_template( (string) ( $settings['email_heading_new_request_customer'] ?? '' ), $tokens );
			$customer_content = $this->render_email_template( (string) ( $settings['email_body_new_request_customer'] ?? '' ), $tokens );
			$customer_content = $this->append_return_address_to_message( $customer_content, $settings );
			$customer_items_title = $this->render_email_template( (string) ( $settings['email_items_title_new_request_customer'] ?? '' ), $tokens );
			$customer_additional  = $this->render_email_template( (string) ( $settings['email_additional_content_new_request_customer'] ?? '' ), $tokens );
			$customer_items_table = $this->build_request_items_table_html( $order, $items );
			$customer_html = $this->build_email_html( (string) get_bloginfo( 'name' ), $customer_heading, $customer_content, $customer_items_title, $customer_items_table, $customer_additional );

			if ( 'yes' === ( $settings['email_enable_new_request_customer'] ?? 'yes' ) && ! empty( $customer_subject ) && ! empty( $customer_content ) ) {
				$this->send_html_email( $customer_mail, $customer_subject, $customer_html );
			}
		}
	}

	private function send_request_status_email( int $request_id, string $status ): void {
		$request_id = absint( $request_id );
		$order_id   = 0;
		$type_raw   = 'return';
		$items      = array();

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( is_array( $request ) ) {
			$order_id = absint( $request['order_id'] ?? 0 );
			$type_raw = 'return';
			$items    = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
		}

		$type = __( 'Return', 'windcodex-returndesk' );
		$settings = $this->restrictions->get_settings();
		$order    = $order_id ? wc_get_order( $order_id ) : null;
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
		$tokens   = array(
			'{request_id}' => (string) $request_id,
			'{order_id}'   => (string) $order_id,
			'{order_number}' => $order instanceof WC_Order ? (string) $order->get_order_number() : (string) $order_id,
			'{order_date}'   => (string) $order_date,
			'{site_title}'   => (string) get_bloginfo( 'name' ),
			'{customer_full_name}'  => (string) $full_name,
			'{customer_first_name}' => (string) $first_name,
			'{customer_last_name}'  => (string) $last_name,
			'{customer_username}'   => (string) $username,
			'{type}'       => $type,
			'{status}'     => ucfirst( $status ),
			'{return_address}' => $this->get_return_address_text( $settings ),
			'{store_address}' => $this->get_return_address_text( $settings ),
			'{phone_number}'  => sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) ),
			'{return_guidelines}' => (string) ( $settings['return_guidelines'] ?? '' ),
		);

		$customer_mail = $this->get_request_customer_email( $request_id );
		if ( is_email( $customer_mail ) && $order instanceof WC_Order ) {
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

			$subject = $this->render_email_template( $subject_tpl, $tokens );
			$heading = $this->render_email_template( $heading_tpl, $tokens );
			$content = $this->render_email_template( $content_tpl, $tokens );
			$content = $this->append_return_address_to_message( $content, $settings );
			$items_title = $this->render_email_template( $items_title_tpl, $tokens );
			$additional  = $this->render_email_template( $additional_tpl, $tokens );
			$items_table = $this->build_request_items_table_html( $order, $items );
			$html = $this->build_email_html( (string) get_bloginfo( 'name' ), $heading, $content, $items_title, $items_table, $additional );

			if ( 'yes' === ( $settings['email_enable_status_update_customer'] ?? 'yes' ) && ! empty( $subject ) && ! empty( $content ) ) {
				$this->send_html_email( $customer_mail, $subject, $html );
			}
		}
	}

	private function render_email_template( string $template, array $tokens ): string {
		$template = wp_kses_post( $template );
		return strtr( $template, $tokens );
	}

	private function send_html_email( string $to, string $subject, string $html ): bool {
		$to      = sanitize_email( $to );
		$subject = wp_strip_all_tags( (string) $subject );
		if ( ! is_email( $to ) || '' === $subject ) {
			return false;
		}

		if ( function_exists( 'wc_mail' ) ) {
			return (bool) wc_mail( $to, $subject, $html );
		}

		return (bool) wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	private function log_email_attempt( string $event, string $to, string $subject, string $result ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$event   = sanitize_key( $event );
		$to      = sanitize_email( $to );
		$subject = wp_strip_all_tags( (string) $subject );
		$result  = sanitize_key( $result );
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			if ( is_object( $logger ) && method_exists( $logger, 'debug' ) ) {
				$logger->debug(
					sprintf( 'email:%s to:%s result:%s subject:%s', $event, $to, $result, $subject ),
					array( 'source' => 'returndesk-free' )
				);
			}
		}
	}

	private function build_email_html( string $site_title, string $heading, string $content, string $items_title, string $items_table_html, string $additional ): string {
		$heading = wp_strip_all_tags( (string) $heading );
		$content = wp_kses_post( (string) $content );
		$items_title = wp_strip_all_tags( (string) $items_title );
		$additional  = wp_kses_post( (string) $additional );

		$message = wpautop( $content );
		if ( '' !== trim( $items_title ) && '' !== trim( (string) $items_table_html ) ) {
			$message .= '<h2 style="margin:18px 0 10px;font-size:16px;line-height:1.2;">' . esc_html( $items_title ) . '</h2>';
		}
		$message .= (string) $items_table_html;
		if ( '' !== trim( wp_strip_all_tags( $additional ) ) ) {
			$message .= wpautop( $additional );
		}

		if ( function_exists( 'WC' ) && WC() ) {
			$mailer = WC()->mailer();
			if ( $mailer ) {
				$email = $this->get_wc_email_context( $mailer );

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

	/**
	 * Pick a WooCommerce email object as template context so customizers can apply styles.
	 *
	 * @param mixed $mailer
	 * @return WC_Email|false
	 */
	private function get_wc_email_context( $mailer ) {
		if ( ! is_object( $mailer ) || ! method_exists( $mailer, 'get_emails' ) ) {
			return false;
		}

		$emails = (array) $mailer->get_emails();
		foreach ( array( 'WC_Email_New_Order', 'WC_Email_Customer_Processing_Order' ) as $preferred ) {
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

	private function build_request_items_table_html( WC_Order $order, array $items ): string {
		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( empty( $items ) ) {
			return '';
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
			return '';
		}

		$table  = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;">';
		$table .= '<thead><tr>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Product', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Price', 'windcodex-returndesk' ) . '</th>';
		$table .= '<th style="text-align:left;background:#F9FAFB;border:1px solid #E5E7EB;padding:10px;font-size:12px;">' . esc_html__( 'Return Quantity', 'windcodex-returndesk' ) . '</th>';
		$table .= '</tr></thead><tbody>' . $rows . '</tbody></table>';

		return $table;
	}

	private function get_request_customer_email_from_order( WC_Order $order, string $guest_email = '' ): string {
		$guest_email = sanitize_email( $guest_email );
		if ( is_email( $guest_email ) ) {
			return $guest_email;
		}
		return sanitize_email( (string) $order->get_billing_email() );
	}

	private function get_request_customer_email( int $request_id ): string {
		$request_id = absint( $request_id );
		if ( $request_id <= 0 ) {
			return '';
		}

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( is_array( $request ) ) {
			$guest_email = sanitize_email( (string) ( $request['guest_email'] ?? '' ) );
			if ( is_email( $guest_email ) ) {
				return $guest_email;
			}
			$order_id = absint( $request['order_id'] ?? 0 );
			$order    = $order_id ? wc_get_order( $order_id ) : null;
			if ( $order instanceof WC_Order ) {
				return sanitize_email( (string) $order->get_billing_email() );
			}
		}
		return '';
	}

	private function handle_logged_in_cancel(): void {
		check_admin_referer( 'returndesk_cancel_request', 'returndesk_cancel_nonce' );
		$post = $this->get_post_data();

		$request_id = isset( $post['request_id'] ) ? absint( $post['request_id'] ) : 0;
		$user_id    = get_current_user_id();
		if ( ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			wc_add_notice( __( 'Request storage is unavailable.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_to_endpoint();
		}

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( ! is_array( $request ) || absint( $request['customer_id'] ?? 0 ) !== absint( $user_id ) ) {
			wc_add_notice( __( 'Invalid request selected.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_to_endpoint();
		}

		if ( 'pending' !== sanitize_key( (string) ( $request['status'] ?? '' ) ) ) {
			wc_add_notice( __( 'Only pending requests can be cancelled.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_to_endpoint();
		}

		$store->update_status( $request_id, 'cancelled' );
		$this->send_request_status_email( $request_id, 'cancelled' );
		wc_add_notice( __( 'Request cancelled successfully.', 'windcodex-returndesk' ), 'success' );
		$this->redirect_to_endpoint();
	}

	private function handle_guest_cancel(): void {
		check_admin_referer( 'returndesk_guest_cancel_request', 'returndesk_guest_cancel_nonce' );
		$post = $this->get_post_data();

		$request_id = isset( $post['request_id'] ) ? absint( $post['request_id'] ) : 0;
		$order_id   = isset( $post['order_id'] ) ? absint( $post['order_id'] ) : 0;
		$email      = isset( $post['returndesk_guest_email'] ) ? sanitize_email( $post['returndesk_guest_email'] ) : '';

		$order = $this->get_guest_order( $order_id, $email );
		if ( ! $order || $request_id <= 0 || ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			wc_add_notice( __( 'Invalid request selected.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back();
		}

		$store   = new ReturnDesk_Requests_Store();
		$request = $store->get( $request_id );
		if ( ! is_array( $request ) || absint( $request['order_id'] ?? 0 ) !== $order_id || strtolower( sanitize_email( (string) ( $request['guest_email'] ?? '' ) ) ) !== strtolower( $email ) ) {
			wc_add_notice( __( 'Invalid request selected.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back();
		}

		if ( 'pending' !== sanitize_key( (string) ( $request['status'] ?? '' ) ) ) {
			wc_add_notice( __( 'Only pending requests can be cancelled.', 'windcodex-returndesk' ), 'error' );
			$this->redirect_back();
		}

		$store->update_status( $request_id, 'cancelled' );
		$this->send_request_status_email( $request_id, 'cancelled' );
		wc_add_notice( __( 'Request cancelled successfully.', 'windcodex-returndesk' ), 'success' );
		$this->redirect_back();
	}

	private function redirect_to_endpoint(): void {
		$referer = wp_get_referer();
		if ( $referer ) {
			wp_safe_redirect( $referer );
			exit;
		}

		if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
			exit;
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	private function redirect_back(): void {
		$referer = wp_get_referer();
		wp_safe_redirect( $referer ? $referer : home_url( '/' ) );
		exit;
	}

	private function get_guest_order( int $order_id, string $email ): ?WC_Order {
		if ( $order_id <= 0 || ! is_email( $email ) ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$billing_email = sanitize_email( (string) $order->get_billing_email() );
		if ( strtolower( $billing_email ) !== strtolower( $email ) ) {
			return null;
		}

		return $order;
	}

	private function get_portal_lookup_order( int $order_id, string $email ): ?WC_Order {
		if ( $order_id <= 0 ) {
			return null;
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( $user_id <= 0 ) {
				return null;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return null;
			}

			return ( (int) $order->get_user_id() === (int) $user_id ) ? $order : null;
		}

		return $this->get_guest_order( $order_id, $email );
	}

	private function render_order_view_modal( WC_Order $order ): void {
		$settings = $this->restrictions->get_settings();
		$return_reasons    = $this->parse_multiline_options( (string) ( $settings['return_reasons'] ?? '' ) );
		$return_guidelines = $this->parse_multiline_options( (string) ( $settings['return_guidelines'] ?? '' ), true );
		$terms_page_id     = absint( $settings['terms_page_id'] ?? 0 );
		$order_id          = absint( $order->get_id() );
		$modal_id          = 'returndesk-modal-order-' . $order_id;
		?>
		<div class="returndesk-modal" id="<?php echo esc_attr( $modal_id ); ?>" aria-hidden="true">
			<div class="returndesk-modal__backdrop" data-returndesk-close-modal="1"></div>
			<div class="returndesk-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Return Request', 'windcodex-returndesk' ); ?>">
				<div class="returndesk-modal__head">
					<h3 class="returndesk-modal__title"><?php esc_html_e( 'Submit a Return Request', 'windcodex-returndesk' ); ?></h3>
					<button type="button" class="returndesk-modal__close" data-returndesk-close-modal="1" aria-label="<?php esc_attr_e( 'Close', 'windcodex-returndesk' ); ?>">&times;</button>
				</div>

				<?php $this->render_guidelines_notice( __( 'Return Guidelines', 'windcodex-returndesk' ), $return_guidelines ); ?>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'returndesk_bulk_submit_request', 'returndesk_bulk_nonce' ); ?>
					<input type="hidden" name="returndesk_bulk_submit_request" value="1">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
					<input type="hidden" name="returndesk_return_to" value="<?php echo esc_attr( $order->get_view_order_url() ); ?>">

					<div class="returndesk-section-title"><?php esc_html_e( 'Return Product', 'windcodex-returndesk' ); ?></div>
					<div class="returndesk-bulk-items">
						<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
							<?php
							$check    = $this->restrictions->is_item_eligible( $order, (int) $item_id, 'return' );
							$eligible = ! empty( $check['eligible'] );
							$product  = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;
							$thumb    = ( $product instanceof WC_Product ) ? $product->get_image( array( 72, 72 ), array( 'class' => 'returndesk-item-thumb__img' ) ) : '';
							$qty_in_order = max( 1, absint( $item->get_quantity() ) );
							$unit_price = ( $qty_in_order > 0 ) ? ( (float) $item->get_subtotal() / $qty_in_order ) : 0;
							?>
							<div class="returndesk-bulk-item">
								<div class="returndesk-item-thumb"><?php echo wp_kses_post( $thumb ); ?></div>
								<div class="returndesk-item-meta">
									<div class="returndesk-item-name"><?php echo esc_html( $item->get_name() ); ?></div>
									<div class="returndesk-item-price"><?php echo wp_kses_post( wc_price( $unit_price ) ); ?></div>
									<?php if ( ! $eligible ) : ?>
										<small><?php echo esc_html( $check['message'] ?? __( 'Not eligible', 'windcodex-returndesk' ) ); ?></small>
									<?php endif; ?>
								</div>
								<div class="returndesk-item-qty">
									<label>
										<span><?php esc_html_e( 'Qty.', 'windcodex-returndesk' ); ?></span>
										<span><?php echo esc_html( (string) $qty_in_order ); ?></span>
									</label>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="returndesk-bulk-actions">
						<div class="returndesk-field">
							<label class="returndesk-label"><?php esc_html_e( 'Return reason*', 'windcodex-returndesk' ); ?></label>
							<?php $this->render_reason_input( $return_reasons ); ?>
						</div>
						<div class="returndesk-field returndesk-field--full">
							<label class="returndesk-label"><?php esc_html_e( 'Please describe your issue*', 'windcodex-returndesk' ); ?></label>
							<textarea name="details" rows="4" required></textarea>
						</div>
						<div class="returndesk-field returndesk-field--file">
							<label class="returndesk-file">
								<span class="returndesk-file__icon" aria-hidden="true">+</span>
								<span class="returndesk-file__text" data-returndesk-default-text="<?php echo esc_attr__( 'Add photo / file', 'windcodex-returndesk' ); ?>"><?php esc_html_e( 'Add photo / file', 'windcodex-returndesk' ); ?></span>
								<input type="file" name="returndesk_attachment_bulk[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
							</label>
							<div class="returndesk-file-preview" data-returndesk-file-preview="1"></div>
						</div>
						<div class="returndesk-field returndesk-field--terms">
							<?php $this->render_terms_checkbox( $terms_page_id ); ?>
						</div>
						<div class="returndesk-field returndesk-field--submit">
							<button type="submit"><?php esc_html_e( 'Submit Request', 'windcodex-returndesk' ); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	private function render_customer_order_request_list( int $user_id, int $order_id, array $requests = array(), int $request_filter_id = 0 ): void {
		if ( empty( $requests ) ) {
			$requests = $this->get_cached_order_requests_for_customer( $user_id, $order_id );
		}

		if ( empty( $requests ) ) {
			return;
		}

		$request_filter_id = absint( $request_filter_id );
		if ( $request_filter_id > 0 ) {
			$requests = array_values(
				array_filter(
					$requests,
					static function ( $request ) use ( $request_filter_id ) {
						return is_array( $request ) && absint( $request['id'] ?? 0 ) === $request_filter_id;
					}
				)
			);
			if ( empty( $requests ) ) {
				return;
			}
		}

		$order = wc_get_order( $order_id );
		$settings = $this->restrictions->get_settings();

		echo '<div class="returndesk-order-requests">';
		echo '<h3>' . esc_html__( 'Return Request Details', 'windcodex-returndesk' ) . '</h3>';
		echo '<div class="returndesk-request-cards">';
		foreach ( $requests as $request ) {
			if ( ! is_array( $request ) ) {
				continue;
			}
			$id     = absint( $request['id'] ?? 0 );
			$status = sanitize_key( (string) ( $request['status'] ?? 'pending' ) );
			$label  = ReturnDesk_Restrictions::get_request_statuses()[ $status ] ?? ucfirst( $status );
			$items  = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
			$reason = sanitize_textarea_field( (string) ( $request['reason'] ?? '' ) );
			$refund_method = sanitize_key( (string) ( $request['refund_method'] ?? 'payment_method' ) );
			$attachments = is_array( $request['attachment_ids'] ?? null ) ? (array) $request['attachment_ids'] : array();
			$qty    = 0;
			foreach ( $items as $entry ) {
				if ( is_array( $entry ) ) {
					$qty += max( 0, absint( $entry['qty'] ?? 0 ) );
				}
			}
			$date = sanitize_text_field( (string) ( $request['created_at'] ?? '' ) );

			echo '<div class="returndesk-request-card">';
			echo '<div class="returndesk-request-card__head">';
			echo '<div>';
			echo '<div class="returndesk-request-id">' . esc_html( 'RD-' . $id ) . '</div>';
			echo '<div class="returndesk-request-meta">' . esc_html(
				sprintf(
					/* translators: 1: requested quantity, 2: created date */
					__( 'Return - Qty: %1$d - %2$s', 'windcodex-returndesk' ),
					$qty,
					$date
				)
			) . '</div>';
			echo '</div>';
			echo '<span class="returndesk-status returndesk-status-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
			echo '</div>';

			$this->render_request_status_notice( $settings, $status, $refund_method );

			if ( '' !== $reason ) {
				$this->render_request_reason_block( $reason );
			}
			$this->render_refund_method_line( $refund_method );

			$this->render_request_products_summary( $items, $order );

			if ( ! empty( $attachments ) ) {
				$links = array();
				foreach ( $attachments as $attachment_id ) {
					$attachment_id = absint( $attachment_id );
					if ( $attachment_id <= 0 ) {
						continue;
					}
					$url = wp_get_attachment_url( $attachment_id );
					if ( $url ) {
						$links[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View attachment', 'windcodex-returndesk' ) . '</a>';
					}
				}
				if ( ! empty( $links ) ) {
					echo '<div class="returndesk-request-attachments">' . wp_kses_post( implode( ' | ', $links ) ) . '</div>';
				}
			}

			echo '<div class="returndesk-request-actions">';
			if ( 'pending' === $status ) {
				echo '<form method="post" class="returndesk-inline-form">';
				wp_nonce_field( 'returndesk_cancel_request', 'returndesk_cancel_nonce' );
				echo '<input type="hidden" name="returndesk_cancel_request" value="1">';
				echo '<input type="hidden" name="request_id" value="' . esc_attr( $id ) . '">';
				echo '<button type="submit" class="button returndesk-cancel-btn">' . esc_html__( 'Cancel', 'windcodex-returndesk' ) . '</button>';
				echo '</form>';
			}
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	private function get_cached_order_requests_for_customer( int $user_id, int $order_id ): array {
		if ( ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			return array();
		}

		$key = absint( $user_id ) . ':' . absint( $order_id );
		if ( isset( $this->order_requests_cache[ $key ] ) && is_array( $this->order_requests_cache[ $key ] ) ) {
			return $this->order_requests_cache[ $key ];
		}

		$store = new ReturnDesk_Requests_Store();
		$list  = $store->list_by_order_for_customer( $user_id, $order_id, 50 );
		$this->order_requests_cache[ $key ] = is_array( $list ) ? $list : array();

		return $this->order_requests_cache[ $key ];
	}

	private function order_has_any_request_for_customer( int $user_id, int $order_id ): bool {
		$user_id  = absint( $user_id );
		$order_id = absint( $order_id );
		if ( $user_id <= 0 || $order_id <= 0 || ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			return false;
		}
		$requests = $this->get_cached_order_requests_for_customer( $user_id, $order_id );
		return ! empty( $requests );
	}

	private function order_has_any_request_for_guest( int $order_id, string $email ): bool {
		$order_id = absint( $order_id );
		$email    = sanitize_email( $email );
		if ( $order_id <= 0 || '' === $email || ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			return false;
		}
		$store    = new ReturnDesk_Requests_Store();
		$requests = $store->list_by_order_for_guest( $order_id, $email, 1 );
		return ! empty( $requests );
	}

	public function get_portal_html( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '<p>' . esc_html__( 'Please login to access returns.', 'windcodex-returndesk' ) . '</p>';
		}

		$has_any_request = false;
		if ( class_exists( 'ReturnDesk_Requests_Store' ) ) {
			$store           = new ReturnDesk_Requests_Store();
			$has_any_request = ! empty( $store->list_by_customer( $user_id, 1 ) );
		}

		ob_start();
		?>
		<div class="returndesk-portal">
			<?php if ( ! $has_any_request ) : ?>
				<h2><?php esc_html_e( 'Returns', 'windcodex-returndesk' ); ?></h2>
				<p class="returndesk-sub"><?php esc_html_e( 'View and track your return requests. To submit a new request, open an order and click Return.', 'windcodex-returndesk' ); ?></p>
				<p><a class="button" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'Go to Orders', 'windcodex-returndesk' ); ?></a></p>
			<?php endif; ?>

			<?php $this->render_customer_request_list( $user_id ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function get_start_return_endpoint_html(): string {
		$is_logged_in = is_user_logged_in();
		$user_id      = $is_logged_in ? get_current_user_id() : 0;
		if ( ! $is_logged_in || $user_id <= 0 ) {
			return '<p>' . esc_html__( 'Please login to submit a return request.', 'windcodex-returndesk' ) . '</p>';
		}

		$query = $this->get_query_data();
		$order_id = isset( $query['rg_order_id'] ) ? absint( $query['rg_order_id'] ) : 0;
		$request_filter_id = isset( $query['rg_request_id'] ) ? absint( $query['rg_request_id'] ) : 0;
		$order    = $this->get_portal_lookup_order( $order_id, '' );

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		ob_start();
		?>
		<div class="returndesk-portal returndesk-guest-center">
			<?php wc_print_notices(); ?>
			<?php if ( ! $order ) : ?>
				<h2><?php esc_html_e( 'Start Return', 'windcodex-returndesk' ); ?></h2>
				<p class="returndesk-sub"><?php esc_html_e( 'Select an order to submit a return request.', 'windcodex-returndesk' ); ?></p>

				<form method="get" class="returndesk-guest-lookup returndesk-guest-lookup--compact">
					<select name="rg_order_id" required class="returndesk-select">
						<option value=""><?php esc_html_e( 'Select order', 'windcodex-returndesk' ); ?></option>
						<?php foreach ( $orders as $o ) : ?>
							<?php if ( $o instanceof WC_Order ) : ?>
								<option value="<?php echo esc_attr( $o->get_id() ); ?>" <?php selected( $order_id, $o->get_id() ); ?>>
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: order number, 2: date */
											__( '#%1$s - %2$s', 'windcodex-returndesk' ),
											$o->get_order_number(),
											wc_format_datetime( $o->get_date_created() )
										)
									);
									?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<button type="submit"><?php esc_html_e( 'Load', 'windcodex-returndesk' ); ?></button>
				</form>
			<?php endif; ?>

			<?php if ( $order ) : ?>
				<?php $this->render_start_return_page_for_order( $order, $request_filter_id ); ?>
			<?php elseif ( $order_id > 0 ) : ?>
				<div class="returndesk-note returndesk-note-muted"><?php esc_html_e( 'We could not find an order matching that ID.', 'windcodex-returndesk' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_start_return_page_for_order( WC_Order $order, int $request_filter_id = 0 ): void {
		$settings = $this->restrictions->get_settings();
		if ( 'yes' !== ( $settings['enable_returns'] ?? 'yes' ) ) {
			echo '<div class="returndesk-note returndesk-note-muted">' . esc_html__( 'Returns are currently disabled.', 'windcodex-returndesk' ) . '</div>';
			return;
		}

		$requests = $this->get_cached_order_requests_for_customer( get_current_user_id(), (int) $order->get_id() );
		if ( absint( $request_filter_id ) > 0 ) {
			$this->render_customer_order_request_list( get_current_user_id(), (int) $order->get_id(), $requests, absint( $request_filter_id ) );
			return;
		}
		if ( ! empty( $requests ) ) {
			$this->render_customer_order_request_list( get_current_user_id(), (int) $order->get_id(), $requests );
			return;
		}

		$return_to = add_query_arg(
			array_filter(
				array(
					'rg_order_id' => (int) $order->get_id(),
				)
			),
			wc_get_account_endpoint_url( 'returndesk-returns' )
		);

		$this->render_bulk_return_form_for_order( $order, $return_to );
	}

	private function render_bulk_return_form_for_order( WC_Order $order, string $return_to ): void {
		$settings          = $this->restrictions->get_settings();
		$return_reasons    = $this->parse_multiline_options( (string) ( $settings['return_reasons'] ?? '' ) );
		$return_guidelines = $this->parse_multiline_options( (string) ( $settings['return_guidelines'] ?? '' ), true );
		$terms_page_id     = absint( $settings['terms_page_id'] ?? 0 );
		$eligible_rows     = array();

		foreach ( $order->get_items() as $order_item_id => $order_item ) {
			if ( ! is_a( $order_item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$check = $this->restrictions->is_item_eligible( $order, (int) $order_item_id, 'return' );
			if ( empty( $check['eligible'] ) ) {
				continue;
			}
			$eligible_rows[] = array(
				'order_item_id' => (int) $order_item_id,
				'order_item'    => $order_item,
			);
		}

		if ( empty( $eligible_rows ) ) {
			return;
		}

		echo '<div class="returndesk-card">';
		echo '<div class="returndesk-card-head">';
		$view_order_url = wc_get_endpoint_url( 'view-order', (int) $order->get_id(), wc_get_page_permalink( 'myaccount' ) );
		echo '<div class="returndesk-breadcrumb"><a href="' . esc_url( $view_order_url ) . '">' . esc_html__( 'Order View', 'windcodex-returndesk' ) . '</a> <span class="returndesk-breadcrumb-sep">&gt;</span> ' . esc_html__( 'Return Order', 'windcodex-returndesk' ) . '</div>';
		echo '<h2 class="returndesk-card-title">' . esc_html__( 'Return Order', 'windcodex-returndesk' ) . '</h2>';
		echo '</div>';

		$this->render_guidelines_notice( __( 'Return Guidelines', 'windcodex-returndesk' ), $return_guidelines );

		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'returndesk_bulk_submit_request', 'returndesk_bulk_nonce' );
		echo '<input type="hidden" name="returndesk_bulk_submit_request" value="1">';
		echo '<input type="hidden" name="order_id" value="' . esc_attr( (int) $order->get_id() ) . '">';
		echo '<input type="hidden" name="returndesk_return_to" value="' . esc_attr( $return_to ) . '">';
		echo '<input type="hidden" name="refund_method" value="payment_method">';

		echo '<div class="returndesk-section-title">' . esc_html__( 'Return Product', 'windcodex-returndesk' ) . '</div>';
		echo '<div class="returndesk-bulk-items">';
		foreach ( $eligible_rows as $row ) {
			$order_item_id = (int) $row['order_item_id'];
			$order_item    = $row['order_item'];
			$product  = is_callable( array( $order_item, 'get_product' ) ) ? $order_item->get_product() : null;
			$thumb    = ( $product instanceof WC_Product ) ? $product->get_image( array( 72, 72 ), array( 'class' => 'returndesk-item-thumb__img' ) ) : '';
			$qty_in_order = max( 1, absint( $order_item->get_quantity() ) );
			$unit_price = ( $qty_in_order > 0 ) ? ( (float) $order_item->get_subtotal() / $qty_in_order ) : 0;

			echo '<div class="returndesk-bulk-item">';
			echo '<div class="returndesk-item-thumb">' . wp_kses_post( $thumb ) . '</div>';
			echo '<div class="returndesk-item-meta">';
			echo '<div class="returndesk-item-name">' . esc_html( $order_item->get_name() ) . '</div>';
			echo '<div class="returndesk-item-price">' . wp_kses_post( wc_price( $unit_price ) ) . '</div>';
			echo '</div>';
			echo '<div class="returndesk-item-qty"><label><span>' . esc_html__( 'Qty.', 'windcodex-returndesk' ) . '</span><span>' . esc_html( (string) $qty_in_order ) . '</span></label></div>';
			echo '</div>';
		}
		echo '</div>';

		echo '<div class="returndesk-bulk-actions">';
		echo '<div class="returndesk-field">';
		echo '<label class="returndesk-label">' . esc_html__( 'Return reason*', 'windcodex-returndesk' ) . '</label>';
		$this->render_reason_input( $return_reasons );
		echo '</div>';
		echo '<div class="returndesk-field returndesk-field--full">';
		echo '<label class="returndesk-label">' . esc_html__( 'Please describe your issue*', 'windcodex-returndesk' ) . '</label>';
		echo '<textarea name="details" rows="4" required></textarea>';
		echo '</div>';
		echo '<div class="returndesk-field returndesk-field--full">';
		echo '<div class="returndesk-label">' . esc_html__( 'Refund method', 'windcodex-returndesk' ) . '</div>';
		echo '<div class="returndesk-note returndesk-note-muted">' . esc_html__( 'Original payment method', 'windcodex-returndesk' ) . '</div>';
		echo '</div>';
		echo '<div class="returndesk-field returndesk-field--file">';
		$default_text = esc_attr__( 'Add photo / file', 'windcodex-returndesk' );
		echo '<label class="returndesk-file"><span class="returndesk-file__icon" aria-hidden="true">+</span><span class="returndesk-file__text" data-returndesk-default-text="' . esc_attr( $default_text ) . '">' . esc_html__( 'Add photo / file', 'windcodex-returndesk' ) . '</span><input type="file" name="returndesk_attachment_bulk[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple></label><div class="returndesk-file-preview" data-returndesk-file-preview="1"></div>';
		echo '</div>';
		echo '<div class="returndesk-field returndesk-field--terms">';
		$this->render_terms_checkbox( $terms_page_id );
		echo '</div>';
		echo '<div class="returndesk-field returndesk-field--submit">';
		echo '<button type="submit">' . esc_html__( 'Submit Request', 'windcodex-returndesk' ) . '</button>';
		echo '</div>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}

	private function get_start_return_url( int $order_id ): string {
		$url  = wc_get_account_endpoint_url( 'returndesk-returns' );
		$args = array();
		if ( $order_id > 0 ) {
			$args['rg_order_id'] = (int) $order_id;
		}
		return add_query_arg( $args, $url );
	}

	public function get_guest_center_html(): string {
		$is_logged_in = is_user_logged_in();
		$user_id      = $is_logged_in ? get_current_user_id() : 0;
		$query    = $this->get_query_data();
		$order_id = isset( $query['rg_order_id'] ) ? absint( $query['rg_order_id'] ) : 0;
		$email    = isset( $query['rg_email'] ) ? sanitize_email( $query['rg_email'] ) : '';
		if ( $is_logged_in && empty( $email ) && $user_id > 0 ) {
			$current_user = wp_get_current_user();
			$email        = $current_user instanceof WP_User ? sanitize_email( (string) $current_user->user_email ) : '';
		}
		$order = $this->get_portal_lookup_order( $order_id, $email );

		ob_start();
		?>
		<div class="returndesk-portal returndesk-guest-center">
			<h2><?php esc_html_e( 'Return Center', 'windcodex-returndesk' ); ?></h2>
			<p class="returndesk-sub"><?php esc_html_e( 'Find your order to create and track return requests.', 'windcodex-returndesk' ); ?></p>
			<?php wc_print_notices(); ?>

			<form method="get" class="returndesk-guest-lookup">
				<input type="number" name="rg_order_id" min="1" placeholder="<?php esc_attr_e( 'Order ID', 'windcodex-returndesk' ); ?>" value="<?php echo esc_attr( $order_id > 0 ? $order_id : '' ); ?>" required>
				<input type="email" name="rg_email" placeholder="<?php echo esc_attr( $is_logged_in ? __( 'Billing Email (optional)', 'windcodex-returndesk' ) : __( 'Billing Email', 'windcodex-returndesk' ) ); ?>" value="<?php echo esc_attr( $email ); ?>" <?php echo $is_logged_in ? '' : 'required'; ?>>
				<button type="submit"><?php esc_html_e( 'Find Order', 'windcodex-returndesk' ); ?></button>
			</form>

			<?php if ( $order ) : ?>
				<?php if ( $is_logged_in && $user_id > 0 ) : ?>
					<?php $this->render_order_items_form( $order, '', false ); ?>
					<?php $this->render_customer_order_request_list( $user_id, $order->get_id() ); ?>
				<?php else : ?>
					<?php $this->render_order_items_form( $order, $email, true ); ?>
					<?php $this->render_guest_request_list( $order->get_id(), $email ); ?>
				<?php endif; ?>
			<?php elseif ( $order_id > 0 || ! empty( $email ) ) : ?>
				<div class="returndesk-note returndesk-note-muted"><?php esc_html_e( 'We could not find an order matching those details.', 'windcodex-returndesk' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_order_items_form( WC_Order $order, string $guest_email = '', bool $guest_mode = false ): void {
		$items = $order->get_items();
		$return_url = $this->get_start_return_url( (int) $order->get_id() );
		$eligible_rows = array();
		foreach ( $items as $item_id => $item ) {
			$item_id = absint( $item_id );
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$return_check = $this->restrictions->is_item_eligible( $order, $item_id, 'return' );
			if ( empty( $return_check['eligible'] ) ) {
				continue;
			}
			$eligible_rows[] = array(
				'item_id' => $item_id,
				'item'    => $item,
			);
		}
		?>
		<div class="returndesk-card">
			<div class="returndesk-card-head">
				<strong><?php echo esc_html( sprintf( '#%s', $order->get_order_number() ) ); ?></strong>
				<span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
			</div>
			<?php foreach ( $eligible_rows as $row ) : ?>
				<?php
				$item = $row['item'];
				$qty  = is_callable( array( $item, 'get_quantity' ) ) ? max( 1, absint( $item->get_quantity() ) ) : 1;
				?>
				<div class="returndesk-item-row">
					<div class="returndesk-item-name"><?php echo esc_html( $item->get_name() ); ?></div>
					<div class="returndesk-note returndesk-note-muted"><?php echo esc_html( sprintf( /* translators: %d: quantity */ __( 'Qty: %d', 'windcodex-returndesk' ), $qty ) ); ?></div>
				</div>
			<?php endforeach; ?>

			<?php if ( $guest_mode ) : ?>
				<?php if ( ! empty( $eligible_rows ) ) : ?>
					<div class="returndesk-note returndesk-note-muted"><?php esc_html_e( 'Please login to submit a return request.', 'windcodex-returndesk' ); ?></div>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( ! empty( $eligible_rows ) ) : ?>
					<p><a class="button" href="<?php echo esc_url( $return_url ); ?>"><?php esc_html_e( 'Return Order', 'windcodex-returndesk' ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function parse_multiline_options( string $raw, bool $allow_html = false ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		if ( ! is_array( $lines ) ) {
			return array();
		}

		$clean = array();
		foreach ( $lines as $line ) {
			$value = $allow_html ? wp_kses_post( trim( (string) $line ) ) : trim( wp_strip_all_tags( (string) $line ) );
			if ( '' !== trim( wp_strip_all_tags( (string) $value ) ) ) {
				$clean[] = $value;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	private function render_guidelines_notice( string $title, array $guidelines ): void {
		if ( empty( $guidelines ) ) {
			return;
		}
		?>
		<div class="returndesk-note returndesk-note-muted">
			<strong><?php echo esc_html( $title ); ?></strong>
			<ul class="returndesk-note-list">
				<?php foreach ( $guidelines as $line ) : ?>
					<li><?php echo wp_kses_post( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function render_reason_input( array $reasons ): void {
		if ( empty( $reasons ) ) {
			?>
			<input type="text" name="reason" placeholder="<?php esc_attr_e( 'Reason', 'windcodex-returndesk' ); ?>" required>
			<?php
			return;
		}
		?>
		<select name="reason" required class="returndesk-select">
			<option value=""><?php esc_html_e( 'Select reason', 'windcodex-returndesk' ); ?></option>
			<?php foreach ( $reasons as $reason ) : ?>
				<option value="<?php echo esc_attr( $reason ); ?>"><?php echo esc_html( $reason ); ?></option>
			<?php endforeach; ?>
			<option value="__other__"><?php esc_html_e( 'Other', 'windcodex-returndesk' ); ?></option>
		</select>
		<div class="returndesk-reason-other">
			<input type="text" name="reason_other" placeholder="<?php esc_attr_e( 'Enter other reason', 'windcodex-returndesk' ); ?>" autocomplete="off">
		</div>
		<?php
	}

	private function render_terms_checkbox( int $terms_page_id ): void {
		if ( $terms_page_id <= 0 ) {
			return;
		}
		$terms_url = get_permalink( $terms_page_id );
		if ( ! $terms_url ) {
			return;
		}
		?>
		<label class="returndesk-terms">
			<input type="checkbox" name="returndesk_accept_terms" value="1" required>
			<span>
				<?php
				printf(
					wp_kses(
						/* translators: %s: terms page URL */
						__( 'I agree to the <a href="%s" target="_blank" rel="noopener">Return Policy terms</a>.', 'windcodex-returndesk' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					esc_url( $terms_url )
				);
				?>
			</span>
		</label>
		<?php
	}

	private function validate_terms_acceptance() {
		$settings      = $this->restrictions->get_settings();
		$terms_page_id = absint( $settings['terms_page_id'] ?? 0 );
		if ( $terms_page_id <= 0 ) {
			return true;
		}

		$post = $this->get_post_data();
		if ( empty( $post['returndesk_accept_terms'] ) ) {
			return new WP_Error( 'terms_required', __( 'Please accept the return policy terms before submitting request.', 'windcodex-returndesk' ) );
		}

		return true;
	}

	private function add_return_address_notice( array $settings ): void {
		$address = $this->get_return_address_text( $settings );
		if ( '' === $address ) {
			return;
		}

		wc_add_notice(
			sprintf(
				/* translators: %s: return address */
				__( 'Return shipping address: %s', 'windcodex-returndesk' ),
				$address
			),
			'notice'
		);
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

		return implode( ', ', $parts );
	}

	private function append_return_address_to_message( string $message, array $settings ): string {
		$address = $this->get_return_address_text( $settings );
		if ( '' === $address ) {
			return $message;
		}

		if ( false !== strpos( $message, '{return_address}' ) ) {
			return $message;
		}

		$line = sprintf(
			/* translators: %s: return address */
			__( 'Return shipping address: %s', 'windcodex-returndesk' ),
			$address
		);

		return trim( $message ) . "\n\n" . $line;
	}

	private function render_customer_request_list( int $user_id ): void {
		if ( ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			echo '<p class="returndesk-empty">' . esc_html__( 'No requests submitted yet.', 'windcodex-returndesk' ) . '</p>';
			return;
		}

		$store    = new ReturnDesk_Requests_Store();
		$requests = $store->list_by_customer( $user_id, 20 );
		$requests = array_values(
			array_filter(
				$requests,
				static function ( $request ) {
					if ( ! is_array( $request ) ) {
						return false;
					}
					return 'return' === sanitize_key( (string) ( $request['request_type'] ?? 'return' ) );
				}
			)
		);

		if ( empty( $requests ) ) {
			echo '<p class="returndesk-empty">' . esc_html__( 'No requests submitted yet.', 'windcodex-returndesk' ) . '</p>';
			return;
		}

		echo '<h3>' . esc_html__( 'My Return Requests', 'windcodex-returndesk' ) . '</h3>';

		echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
		echo '<thead><tr>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-request-id"><span class="nobr">' . esc_html__( 'Request ID', 'windcodex-returndesk' ) . '</span></th>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order"><span class="nobr">' . esc_html__( 'Order', 'windcodex-returndesk' ) . '</span></th>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-status"><span class="nobr">' . esc_html__( 'Status', 'windcodex-returndesk' ) . '</span></th>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-type"><span class="nobr">' . esc_html__( 'Request Type', 'windcodex-returndesk' ) . '</span></th>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-total"><span class="nobr">' . esc_html__( 'Total', 'windcodex-returndesk' ) . '</span></th>';
		echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-actions"><span class="nobr">' . esc_html__( 'Actions', 'windcodex-returndesk' ) . '</span></th>';
		echo '</tr></thead><tbody>';

		$status_labels = ReturnDesk_Restrictions::get_request_statuses();
		foreach ( $requests as $request ) {
			if ( ! is_array( $request ) ) {
				continue;
			}

			$id       = absint( $request['id'] ?? 0 );
			$order_id = absint( $request['order_id'] ?? 0 );
			$status   = sanitize_key( (string) ( $request['status'] ?? 'pending' ) );
			$label    = $status_labels[ $status ] ?? ucfirst( $status );
			$items    = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();

			$order         = $order_id > 0 ? wc_get_order( $order_id ) : null;
			$order_number  = $order ? $order->get_order_number() : (string) $order_id;
			$view_order_url = '';
			if ( $order_id > 0 ) {
				$view_order_url = add_query_arg(
					array(
						'rg_order_id'    => $order_id,
						'rg_request_id'  => $id,
					),
					wc_get_account_endpoint_url( 'returndesk-returns' )
				);
			}
			if ( $view_order_url ) {
				$view_order_url = add_query_arg(
					array(
						'rg_request_id' => $id,
					),
					$view_order_url
				);
			}

			$qty   = 0;
			$total = 0.0;
			foreach ( $items as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$line_qty = max( 0, absint( $entry['qty'] ?? 0 ) );
				$qty     += $line_qty;

				if ( ! $order || $line_qty <= 0 ) {
					continue;
				}

				$order_item_id = absint( $entry['order_item_id'] ?? 0 );
				if ( $order_item_id <= 0 ) {
					continue;
				}
				$order_item = $order->get_item( $order_item_id );
				if ( ! $order_item || ! is_a( $order_item, 'WC_Order_Item_Product' ) ) {
					continue;
				}

				$qty_in_order = max( 1, absint( $order_item->get_quantity() ) );
				$unit_total   = (float) $order_item->get_total() / $qty_in_order;
				$total       += $unit_total * $line_qty;
			}

			$total_label = sprintf(
				/* translators: %d: item count */
				_n( 'for %d item', 'for %d items', max( 0, $qty ), 'windcodex-returndesk' ),
				max( 0, $qty )
			);

			echo '<tr class="woocommerce-orders-table__row">';

			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-request-id" data-title="' . esc_attr__( 'Request ID', 'windcodex-returndesk' ) . '">';
			echo esc_html( '#RD-' . $id );
			echo '</td>';

			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order" data-title="' . esc_attr__( 'Order', 'windcodex-returndesk' ) . '">';
			if ( $view_order_url ) {
				echo '<a href="' . esc_url( $view_order_url ) . '">' . esc_html( '#' . $order_number ) . '</a>';
			} else {
				echo esc_html( '#' . $order_number );
			}
			echo '</td>';

			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-status" data-title="' . esc_attr__( 'Status', 'windcodex-returndesk' ) . '">' . esc_html( $label ) . '</td>';
			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-type" data-title="' . esc_attr__( 'Request Type', 'windcodex-returndesk' ) . '">' . esc_html__( 'Return', 'windcodex-returndesk' ) . '</td>';

			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-total" data-title="' . esc_attr__( 'Total', 'windcodex-returndesk' ) . '">';
			echo wp_kses_post( wc_price( $total ) ) . ' <small>' . esc_html( $total_label ) . '</small>';
			echo '</td>';

			echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-actions" data-title="' . esc_attr__( 'Actions', 'windcodex-returndesk' ) . '">';
			if ( $view_order_url ) {
				echo '<a class="button" href="' . esc_url( $view_order_url ) . '">' . esc_html__( 'View', 'windcodex-returndesk' ) . '</a>';
			}
			echo '</td>';

			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function render_guest_request_list( int $order_id, string $email ): void {
		if ( ! class_exists( 'ReturnDesk_Requests_Store' ) ) {
			echo '<p class="returndesk-empty">' . esc_html__( 'No requests submitted yet.', 'windcodex-returndesk' ) . '</p>';
			return;
		}

		$store    = new ReturnDesk_Requests_Store();
		$requests = $store->list_by_order_for_guest( $order_id, $email, 20 );
		$requests = array_values(
			array_filter(
				$requests,
				static function ( $request ) {
					if ( ! is_array( $request ) ) {
						return false;
					}
					return 'return' === sanitize_key( (string) ( $request['request_type'] ?? 'return' ) );
				}
			)
		);

		if ( empty( $requests ) ) {
			echo '<p class="returndesk-empty">' . esc_html__( 'No requests submitted yet.', 'windcodex-returndesk' ) . '</p>';
			return;
		}

		echo '<h3>' . esc_html__( 'Order Requests', 'windcodex-returndesk' ) . '</h3>';
		$this->render_request_cards( $requests, 0, true, $order_id, $email );
	}

	private function render_request_cards( array $requests, int $user_id, bool $guest_mode, int $order_id = 0, string $guest_email = '' ): void {
		if ( empty( $requests ) ) {
			return;
		}

		echo '<div class="returndesk-request-cards">';
		$settings = $this->restrictions->get_settings();
		foreach ( $requests as $request ) {
			if ( ! is_array( $request ) ) {
				continue;
			}
			if ( 'return' !== sanitize_key( (string) ( $request['request_type'] ?? 'return' ) ) ) {
				continue;
			}

			$id       = absint( $request['id'] ?? 0 );
			$order_id_for_row = absint( $request['order_id'] ?? $order_id );
			$status   = sanitize_key( (string) ( $request['status'] ?? 'pending' ) );
			$label    = ReturnDesk_Restrictions::get_request_statuses()[ $status ] ?? ucfirst( $status );
			$date     = sanitize_text_field( (string) ( $request['created_at'] ?? '' ) );
			$items    = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
			$reason   = sanitize_textarea_field( (string) ( $request['reason'] ?? '' ) );
			$refund_method = sanitize_key( (string) ( $request['refund_method'] ?? 'payment_method' ) );
			$attachments = is_array( $request['attachment_ids'] ?? null ) ? (array) $request['attachment_ids'] : array();

			$qty = 0;
			foreach ( $items as $entry ) {
				if ( is_array( $entry ) ) {
					$qty += max( 0, absint( $entry['qty'] ?? 0 ) );
				}
			}

			$view_order_url = '';
			if ( ! $guest_mode && $order_id_for_row > 0 ) {
				$view_order_url = wc_get_endpoint_url( 'view-order', $order_id_for_row, wc_get_page_permalink( 'myaccount' ) );
			}

			echo '<div class="returndesk-request-card">';
			echo '<div class="returndesk-request-card__head">';
			echo '<div>';
			echo '<div class="returndesk-request-id">' . esc_html( 'RD-' . $id ) . '</div>';
			echo '<div class="returndesk-request-meta">' . esc_html(
				sprintf(
					/* translators: 1: requested quantity, 2: created date */
					__( 'Return - Qty: %1$d - %2$s', 'windcodex-returndesk' ),
					$qty,
					$date
				)
			) . '</div>';
			echo '</div>';
			echo '<span class="returndesk-status returndesk-status-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
			echo '</div>';

			$this->render_request_status_notice( $settings, $status, $refund_method );

			if ( $order_id_for_row > 0 ) {
				if ( $view_order_url ) {
					echo '<div class="returndesk-request-reason"><strong>' . esc_html__( 'Order:', 'windcodex-returndesk' ) . '</strong> <a href="' . esc_url( $view_order_url ) . '">' . esc_html( '#' . $order_id_for_row ) . '</a></div>';
				} else {
					echo '<div class="returndesk-request-reason"><strong>' . esc_html__( 'Order:', 'windcodex-returndesk' ) . '</strong> ' . esc_html( '#' . $order_id_for_row ) . '</div>';
				}
			}

			if ( '' !== $reason ) {
				$this->render_request_reason_block( $reason );
			}
			$this->render_refund_method_line( $refund_method );

			$order_for_row = $order_id_for_row > 0 ? wc_get_order( $order_id_for_row ) : null;
			$this->render_request_products_summary( $items, $order_for_row );

			if ( ! empty( $attachments ) ) {
				$links = array();
				foreach ( $attachments as $attachment_id ) {
					$attachment_id = absint( $attachment_id );
					if ( $attachment_id <= 0 ) {
						continue;
					}
					$url = wp_get_attachment_url( $attachment_id );
					if ( $url ) {
						$links[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View attachment', 'windcodex-returndesk' ) . '</a>';
					}
				}
				if ( ! empty( $links ) ) {
					echo '<div class="returndesk-request-attachments">' . wp_kses_post( implode( ' | ', $links ) ) . '</div>';
				}
			}

			echo '<div class="returndesk-request-actions">';
			if ( $view_order_url ) {
				echo '<a class="button" href="' . esc_url( $view_order_url ) . '">' . esc_html__( 'View Order', 'windcodex-returndesk' ) . '</a>';
			}

			if ( 'pending' === $status ) {
				echo '<form method="post" class="returndesk-inline-form">';
				if ( $guest_mode ) {
					wp_nonce_field( 'returndesk_guest_cancel_request', 'returndesk_guest_cancel_nonce' );
					echo '<input type="hidden" name="returndesk_guest_mode" value="1">';
					echo '<input type="hidden" name="order_id" value="' . esc_attr( $order_id_for_row ) . '">';
					echo '<input type="hidden" name="returndesk_guest_email" value="' . esc_attr( $guest_email ) . '">';
				} else {
					wp_nonce_field( 'returndesk_cancel_request', 'returndesk_cancel_nonce' );
				}
				echo '<input type="hidden" name="returndesk_cancel_request" value="1">';
				echo '<input type="hidden" name="request_id" value="' . esc_attr( $id ) . '">';
				echo '<button type="submit" class="button returndesk-cancel-btn">' . esc_html__( 'Cancel', 'windcodex-returndesk' ) . '</button>';
				echo '</form>';
			}
			echo '</div>';

			echo '</div>';
		}
		echo '</div>';
	}

	private function render_request_status_notice( array $settings, string $status, string $refund_method = 'payment_method' ): void {
		$status     = sanitize_key( $status );
		$refund_method = sanitize_key( $refund_method );
		$guidelines = array();
		$message    = '';

		if ( 'approved' === $status ) {
			$message    = __( 'Your return request has been approved. Please proceed with shipping the return item(s) back to us.', 'windcodex-returndesk' );
			$guidelines = $this->parse_multiline_options( (string) ( $settings['return_guidelines'] ?? '' ), true );
		} elseif ( 'pending' === $status ) {
			$message = __( 'Your return request is submitted and currently under review. We will notify you once it is processed.', 'windcodex-returndesk' );
		} elseif ( 'rejected' === $status ) {
			$message = __( 'Your return request was not approved. Please contact our support team if you need any help.', 'windcodex-returndesk' );
		} elseif ( 'cancelled' === $status ) {
			$message = __( 'This return request has been cancelled. If needed, you can submit a new return request for this order.', 'windcodex-returndesk' );
		} else {
			return;
		}
		?>
		<div class="returndesk-request-status-box is-<?php echo esc_attr( $status ); ?>">
			<p class="returndesk-request-status-text"><?php echo esc_html( $message ); ?></p>
			<?php if ( 'approved' === $status ) : ?>
				<p class="returndesk-request-status-text"><?php echo esc_html( sprintf( /* translators: %s: refund method label */ __( 'Refund method: %s.', 'windcodex-returndesk' ), $this->get_refund_method_label( $refund_method ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $guidelines ) ) : ?>
				<ul class="returndesk-request-status-steps">
					<?php foreach ( $guidelines as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	private function get_refund_method_label( string $refund_method ): string {
		$refund_method = sanitize_key( $refund_method );
		if ( 'store_credit' === $refund_method ) {
			return __( 'Store credit', 'windcodex-returndesk' );
		}

		return __( 'Original payment method', 'windcodex-returndesk' );
	}

	private function render_refund_method_line( string $refund_method ): void {
		$label = $this->get_refund_method_label( $refund_method );
		echo '<div class="returndesk-request-details"><strong>' . esc_html__( 'Refund method:', 'windcodex-returndesk' ) . '</strong> ' . esc_html( $label ) . '</div>';
	}

	private function render_request_products_summary( array $items, $order ): void {
		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="returndesk-request-products">
			<div class="returndesk-request-section-title"><?php esc_html_e( 'Return Product Details', 'windcodex-returndesk' ); ?></div>
			<?php
			foreach ( $items as $entry ) :
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$order_item_id = absint( $entry['order_item_id'] ?? 0 );
				$line_qty      = max( 0, absint( $entry['qty'] ?? 0 ) );
				if ( $order_item_id <= 0 || $line_qty <= 0 ) {
					continue;
				}

				$item_name = '';
				$thumb_html = '';
				if ( $order instanceof WC_Order ) {
					$order_item = $order->get_item( $order_item_id );
					if ( $order_item && is_a( $order_item, 'WC_Order_Item_Product' ) ) {
						$item_name = (string) $order_item->get_name();
						$product = $order_item->get_product();
						if ( $product instanceof WC_Product ) {
							$thumb_html = $product->get_image( 'thumbnail', array( 'class' => 'returndesk-request-product-thumb-img' ) );
						}
					}
				}
				if ( '' === $item_name ) {
					$item_name = sprintf(
						/* translators: %d: order item id */
						__( 'Item #%d', 'windcodex-returndesk' ),
						$order_item_id
					);
				}
				?>
				<div class="returndesk-request-product-row">
					<div class="returndesk-request-product-thumb">
						<?php
						if ( '' !== $thumb_html ) {
							echo wp_kses_post( $thumb_html );
						} else {
							echo '<span class="returndesk-request-product-thumb-placeholder" aria-hidden="true"></span>';
						}
						?>
					</div>
					<div class="returndesk-request-product-meta">
						<div class="returndesk-request-product-name"><?php echo esc_html( $item_name ); ?></div>
						<div class="returndesk-request-product-qty"><?php echo esc_html( sprintf( /* translators: %d: quantity */ __( 'Qty: %d', 'windcodex-returndesk' ), $line_qty ) ); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

}
