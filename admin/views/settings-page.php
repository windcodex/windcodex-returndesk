<?php
/**
 * ReturnDesk settings page.
 *
 */

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file with local view variables.
defined( 'ABSPATH' ) || exit;

$restrictions = new ReturnDesk_Restrictions();
$settings     = $restrictions->get_settings();
$statuses = array(
	'pending'    => __( 'Pending payment', 'windcodex-returndesk' ),
	'processing' => __( 'Processing', 'windcodex-returndesk' ),
	'completed'  => __( 'Completed', 'windcodex-returndesk' ),
	'on-hold'    => __( 'On hold', 'windcodex-returndesk' ),
);
if ( function_exists( 'wc_get_order_statuses' ) ) {
	$statuses = array();
	foreach ( (array) wc_get_order_statuses() as $status_key => $status_label ) {
		$key = ( 0 === strpos( (string) $status_key, 'wc-' ) ) ? substr( (string) $status_key, 3 ) : (string) $status_key;
		$statuses[ $key ] = $status_label;
	}
}
$request_statuses = ReturnDesk_Restrictions::get_request_statuses();
$requests = array();
if ( class_exists( 'ReturnDesk_Requests_Store' ) ) {
	$requests = ( new ReturnDesk_Requests_Store() )->list_recent( 50 );
}
?>
<div class="gg-header-card">
	<div class="gg-header-card-inner">
		<div class="gg-breadcrumb">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=returndesk-settings' ) ); ?>"><?php esc_html_e( 'ReturnDesk', 'windcodex-returndesk' ); ?></a>
			<span class="gg-breadcrumb-sep">/</span>
			<span id="gg-breadcrumb-current"><?php esc_html_e( 'General', 'windcodex-returndesk' ); ?></span>
		</div>
		<div class="gg-help-wrap">
			<button type="button" class="gg-help-btn" id="gg-help-btn" aria-expanded="false" aria-haspopup="true">
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'Help', 'windcodex-returndesk' ); ?>
			</button>
			<div class="gg-help-dropdown" id="gg-help-dropdown" hidden>
				<a href="https://docs.windcodex.com/docs/returndesk" target="_blank" rel="noopener" class="gg-help-item">
					<span class="gg-help-item-icon dashicons dashicons-media-document"></span>
					<?php esc_html_e( 'Documentation', 'windcodex-returndesk' ); ?>
				</a>
				<a href="https://wordpress.org/support/plugin/windcodex-returndesk/reviews/#new-post" target="_blank" rel="noopener" class="gg-help-item">
					<span class="gg-help-item-icon dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Submit a Review', 'windcodex-returndesk' ); ?>
				</a>
				<a href="https://windcodex.com/product/woocommerce-returns-plugin/" target="_blank" rel="noopener" class="gg-help-item">
					<span class="gg-help-item-icon dashicons dashicons-awards"></span>
					<?php esc_html_e( 'Upgrade to Pro', 'windcodex-returndesk' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<div class="gg-wrap wrap">
<?php do_action( 'returndesk_before_settings' ); ?>
<div class="gg-tabs-nav" role="tablist">
	<button class="gg-tab-btn gg-tab-active" data-tab="general" data-breadcrumb="General"><?php esc_html_e( 'General', 'windcodex-returndesk' ); ?></button>
	<button class="gg-tab-btn" data-tab="advanced" data-breadcrumb="Advanced"><?php esc_html_e( 'Advanced', 'windcodex-returndesk' ); ?></button>
	<button class="gg-tab-btn" data-tab="requests" data-breadcrumb="Requests"><?php esc_html_e( 'Requests', 'windcodex-returndesk' ); ?></button>
</div>

	<form id="gg-settings-form" method="post">
		<div class="gg-tab-panel gg-tab-panel-active" data-panel="general">
			<div class="gg-card" data-section="common-settings">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Common Settings', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Shared request rules and order-level eligibility.', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-card-header-actions">
						<button type="button" class="gg-card-accordion-toggle" data-card-accordion-toggle aria-expanded="true" aria-controls="gg-common-settings-content">
							<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Toggle Common Settings section', 'windcodex-returndesk' ); ?></span>
						</button>
					</div>
				</div>

				<div id="gg-common-settings-content" class="gg-card-accordion-content">
				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Allowed Order Statuses', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Only orders in these statuses can create requests', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body">
						<select class="gg-input gg-multi-select" name="returndesk_settings[allowed_statuses][]" multiple data-placeholder="<?php esc_attr_e( 'Select order statuses...', 'windcodex-returndesk' ); ?>">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( $key, (array) $settings['allowed_statuses'], true ) ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="gg-form-row gg-form-row-last">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Terms & Conditions Page', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body">
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'returndesk_settings[terms_page_id]',
								'echo'              => 1,
								'show_option_none'  => esc_html__( '-- Select Page --', 'windcodex-returndesk' ),
								'option_none_value' => '0',
								'selected'          => absint( $settings['terms_page_id'] ?? 0 ),
								'class'             => 'gg-input gg-input-sm gg-native-select',
							)
						);
						?>
					</div>
				</div>
				</div>
			</div>

			<div class="gg-card" data-section="common-address">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Return Address', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Address shown to customers for return shipments.', 'windcodex-returndesk' ); ?></div>
					</div>
				</div>

				<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Address Line 1', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[return_store_address_1]" value="<?php echo esc_attr( $settings['return_store_address_1'] ?? '' ); ?>"></div></div>
				<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Address Line 2', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[return_store_address_2]" value="<?php echo esc_attr( $settings['return_store_address_2'] ?? '' ); ?>"></div></div>
				<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'City', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input gg-input-sm" name="returndesk_settings[return_store_city]" value="<?php echo esc_attr( $settings['return_store_city'] ?? '' ); ?>"></div></div>
				<?php
				$store_country = preg_replace( '/[^A-Z]/', '', strtoupper( (string) ( $settings['return_store_country'] ?? '' ) ) );
				$store_state   = preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) ( $settings['return_store_state'] ?? '' ) ) );
				?>
				<?php if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && method_exists( WC()->countries, 'country_dropdown_options' ) ) : ?>
					<div class="gg-form-row">
						<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Country / State', 'windcodex-returndesk' ); ?></div></div>
						<div class="gg-row-body">
							<input type="hidden" name="returndesk_settings[return_store_country]" value="<?php echo esc_attr( $store_country ); ?>">
							<input type="hidden" name="returndesk_settings[return_store_state]" value="<?php echo esc_attr( $store_state ); ?>">
							<select class="gg-input gg-input-sm gg-native-select wc-enhanced-select" name="returndesk_settings[return_store_country_state]" data-placeholder="<?php esc_attr_e( 'Select a country / state...', 'windcodex-returndesk' ); ?>">
								<?php WC()->countries->country_dropdown_options( $store_country, $store_state ); ?>
							</select>
						</div>
					</div>
				<?php else : ?>
					<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'State', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input gg-input-sm" name="returndesk_settings[return_store_state]" value="<?php echo esc_attr( $store_state ); ?>"></div></div>
					<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Country', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input gg-input-sm" name="returndesk_settings[return_store_country]" value="<?php echo esc_attr( $store_country ); ?>"></div></div>
				<?php endif; ?>

				<div class="gg-form-row"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Postcode / ZIP', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input gg-input-sm" name="returndesk_settings[return_store_postcode]" value="<?php echo esc_attr( $settings['return_store_postcode'] ?? '' ); ?>"></div></div>
				<div class="gg-form-row gg-form-row-last"><div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Phone Number', 'windcodex-returndesk' ); ?></div></div><div class="gg-row-body"><input type="text" class="gg-input gg-input-sm" name="returndesk_settings[return_store_phone]" value="<?php echo esc_attr( $settings['return_store_phone'] ?? '' ); ?>"></div></div>
			</div>

			<div class="gg-card" data-section="return-settings">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Return Settings', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Configure return-specific behavior for your store.', 'windcodex-returndesk' ); ?></div>
					</div>
				</div>

				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Enable Returns', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Allow customer initiated return requests', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-toggle-row">
							<label class="gg-toggle-wrap">
								<input type="checkbox" name="returndesk_settings[enable_returns]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['enable_returns'], 'yes' ); ?>>
								<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
							</label>
							<div class="gg-toggle-label">
								<span class="gg-toggle-main"><?php esc_html_e( 'Customers can submit returns', 'windcodex-returndesk' ); ?></span>
								<span class="gg-toggle-sub"><?php esc_html_e( 'When disabled, return requests are blocked globally.', 'windcodex-returndesk' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Return Window (days)', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Maximum days from order date to allow returns', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body"><input type="number" min="1" class="gg-input gg-input-sm" name="returndesk_settings[return_window_days]" value="<?php echo esc_attr( $settings['return_window_days'] ); ?>"></div>
				</div>

				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Allow Returns For Sale Products', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-toggle-row">
							<label class="gg-toggle-wrap">
								<input type="checkbox" name="returndesk_settings[return_allow_sale_products]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['return_allow_sale_products'] ?? 'yes', 'yes' ); ?>>
								<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
							</label>
							<div class="gg-toggle-label">
								<span class="gg-toggle-main"><?php esc_html_e( 'Allow returns for discounted/sale items', 'windcodex-returndesk' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Return Guidelines', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Shown to customers when they submit or view an approved return.', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body">
						<?php
						wp_editor(
							(string) ( $settings['return_guidelines'] ?? '' ),
							'returndesk_return_guidelines',
							array(
								'textarea_name' => 'returndesk_settings[return_guidelines]',
								'textarea_rows' => 6,
								'media_buttons' => false,
								'teeny'         => true,
								'quicktags'     => true,
							)
						);
						?>
					</div>
				</div>

				<div class="gg-form-row gg-form-row-last">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Return Reasons (one per line)', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[return_reasons]"><?php echo esc_textarea( $settings['return_reasons'] ?? '' ); ?></textarea></div>
				</div>
			</div>

		</div>

		<div class="gg-tab-panel" data-panel="advanced">
			<div class="gg-card">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Customer Experience', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Customer-facing response and communication defaults.', 'windcodex-returndesk' ); ?></div>
					</div>
				</div>

				<div class="gg-form-row gg-form-row-last">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Customer Success Message', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Shown after customer submits a request', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[customer_message]" value="<?php echo esc_attr( $settings['customer_message'] ); ?>"></div>
				</div>
			</div>

			<div class="gg-card">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Email Notifications', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Manage admin/customer templates and delivery behavior.', 'windcodex-returndesk' ); ?></div>
					</div>
				</div>

				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Admin Notification Email', 'windcodex-returndesk' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Leave blank to use site admin email', 'windcodex-returndesk' ); ?></div>
					</div>
					<div class="gg-row-body"><input type="email" autocomplete="email" class="gg-input gg-input-sm" name="returndesk_settings[admin_notification_email]" value="<?php echo esc_attr( $settings['admin_notification_email'] ); ?>"></div>
				</div>

				<?php
				$rg_address_parts = array(
					sanitize_text_field( (string) ( $settings['return_store_address_1'] ?? '' ) ),
					sanitize_text_field( (string) ( $settings['return_store_address_2'] ?? '' ) ),
					sanitize_text_field( (string) ( $settings['return_store_city'] ?? '' ) ),
					sanitize_text_field( (string) ( $settings['return_store_state'] ?? '' ) ),
					sanitize_text_field( (string) ( $settings['return_store_country'] ?? '' ) ),
					sanitize_text_field( (string) ( $settings['return_store_postcode'] ?? '' ) ),
				);
				$rg_address_parts = array_values(
					array_filter(
						$rg_address_parts,
						static function ( $value ) {
							return '' !== trim( (string) $value );
						}
					)
				);
				$rg_phone = sanitize_text_field( (string) ( $settings['return_store_phone'] ?? '' ) );
				if ( '' !== $rg_phone ) {
					$rg_address_parts[] = sprintf(
						/* translators: %s: phone number */
						__( 'Phone: %s', 'windcodex-returndesk' ),
						$rg_phone
					);
				}
				$rg_return_address = implode( ', ', $rg_address_parts );
				if ( '' === $rg_return_address ) {
					$rg_defaults       = ReturnDesk_Restrictions::get_default_settings();
					$rg_address_parts  = array(
						sanitize_text_field( (string) ( $rg_defaults['return_store_address_1'] ?? '' ) ),
						sanitize_text_field( (string) ( $rg_defaults['return_store_address_2'] ?? '' ) ),
						sanitize_text_field( (string) ( $rg_defaults['return_store_city'] ?? '' ) ),
						sanitize_text_field( (string) ( $rg_defaults['return_store_state'] ?? '' ) ),
						sanitize_text_field( (string) ( $rg_defaults['return_store_country'] ?? '' ) ),
						sanitize_text_field( (string) ( $rg_defaults['return_store_postcode'] ?? '' ) ),
					);
					$rg_address_parts  = array_values(
						array_filter(
							$rg_address_parts,
							static function ( $value ) {
								return '' !== trim( (string) $value );
							}
						)
					);
					$rg_return_address = implode( ', ', $rg_address_parts );
					$rg_phone          = sanitize_text_field( (string) ( $rg_defaults['return_store_phone'] ?? '' ) );
					if ( '' !== $rg_phone ) {
						$rg_return_address = trim(
							$rg_return_address . ', ' . sprintf(
								/* translators: %s: phone number */
								__( 'Phone: %s', 'windcodex-returndesk' ),
								$rg_phone
							)
						);
					}
				}
				?>

				<div class="gg-email-customizer-inline">
					<div class="gg-email-customizer-grid">
						<div class="gg-email-customizer-controls gg-email-customizer-controls-inline">
							<div class="gg-form-row">
								<div class="gg-row-label">
									<div class="gg-row-title"><?php esc_html_e( 'Email Type', 'windcodex-returndesk' ); ?></div>
									<div class="gg-row-hint"><?php esc_html_e( 'Pick a template to edit and preview', 'windcodex-returndesk' ); ?></div>
								</div>
								<div class="gg-row-body">
									<select id="gg-email-template" class="gg-input gg-input-sm gg-native-select">
										<option value="new_request_admin"><?php esc_html_e( 'New Request (Admin)', 'windcodex-returndesk' ); ?></option>
										<option value="new_request_customer"><?php esc_html_e( 'New Request (Customer)', 'windcodex-returndesk' ); ?></option>
										<option value="status_approved"><?php esc_html_e( 'Return Approved (Customer)', 'windcodex-returndesk' ); ?></option>
										<option value="status_rejected"><?php esc_html_e( 'Return Rejected (Customer)', 'windcodex-returndesk' ); ?></option>
										<option value="status_cancelled"><?php esc_html_e( 'Return Cancelled (Customer)', 'windcodex-returndesk' ); ?></option>
									</select>
								</div>
							</div>

							<div class="gg-email-template-panels">
								<div class="gg-email-template-panel" data-template="new_request_admin">
									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Enable', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body">
											<div class="gg-toggle-row">
												<label class="gg-toggle-wrap">
													<input type="checkbox" name="returndesk_settings[email_enable_new_request_admin]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['email_enable_new_request_admin'], 'yes' ); ?>>
													<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
												</label>
												<div class="gg-toggle-label"><span class="gg-toggle-main"><?php esc_html_e( 'Send admin email when a new request is created', 'windcodex-returndesk' ); ?></span></div>
											</div>
										</div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label">
											<div class="gg-row-title"><?php esc_html_e( 'Email Subject', 'windcodex-returndesk' ); ?></div>
										</div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_subject_new_request_admin]" value="<?php echo esc_attr( $settings['email_subject_new_request_admin'] ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Heading', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_heading_new_request_admin]" value="<?php echo esc_attr( $settings['email_heading_new_request_admin'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[email_body_new_request_admin]"><?php echo esc_textarea( $settings['email_body_new_request_admin'] ); ?></textarea></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Return Items Title', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_items_title_new_request_admin]" value="<?php echo esc_attr( $settings['email_items_title_new_request_admin'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row gg-form-row-last">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Additional Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="3" name="returndesk_settings[email_additional_content_new_request_admin]"><?php echo esc_textarea( $settings['email_additional_content_new_request_admin'] ?? '' ); ?></textarea></div>
									</div>
								</div>

								<div class="gg-email-template-panel gg-hidden" data-template="new_request_customer">
									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Enable', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body">
											<div class="gg-toggle-row">
												<label class="gg-toggle-wrap">
													<input type="checkbox" name="returndesk_settings[email_enable_new_request_customer]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['email_enable_new_request_customer'], 'yes' ); ?>>
													<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
												</label>
												<div class="gg-toggle-label"><span class="gg-toggle-main"><?php esc_html_e( 'Send customer email after creating request', 'windcodex-returndesk' ); ?></span></div>
											</div>
										</div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label">
											<div class="gg-row-title"><?php esc_html_e( 'Email Subject', 'windcodex-returndesk' ); ?></div>
										</div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_subject_new_request_customer]" value="<?php echo esc_attr( $settings['email_subject_new_request_customer'] ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Heading', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_heading_new_request_customer]" value="<?php echo esc_attr( $settings['email_heading_new_request_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[email_body_new_request_customer]"><?php echo esc_textarea( $settings['email_body_new_request_customer'] ); ?></textarea></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Return Items Title', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_items_title_new_request_customer]" value="<?php echo esc_attr( $settings['email_items_title_new_request_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row gg-form-row-last">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Additional Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="3" name="returndesk_settings[email_additional_content_new_request_customer]"><?php echo esc_textarea( $settings['email_additional_content_new_request_customer'] ?? '' ); ?></textarea></div>
									</div>
								</div>

								<div class="gg-email-template-panel gg-hidden" data-template="status_approved">
									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Enable', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body">
											<div class="gg-toggle-row">
												<label class="gg-toggle-wrap">
													<input type="checkbox" name="returndesk_settings[email_enable_status_update_customer]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['email_enable_status_update_customer'], 'yes' ); ?>>
													<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
												</label>
												<div class="gg-toggle-label"><span class="gg-toggle-main"><?php esc_html_e( 'Send customer email on status changes', 'windcodex-returndesk' ); ?></span></div>
											</div>
										</div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label">
											<div class="gg-row-title"><?php esc_html_e( 'Email Subject', 'windcodex-returndesk' ); ?></div>
										</div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_subject_status_approved_customer]" value="<?php echo esc_attr( $settings['email_subject_status_approved_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Heading', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_heading_status_approved_customer]" value="<?php echo esc_attr( $settings['email_heading_status_approved_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[email_body_status_approved_customer]"><?php echo esc_textarea( $settings['email_body_status_approved_customer'] ?? '' ); ?></textarea></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Return Items Title', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_items_title_status_approved_customer]" value="<?php echo esc_attr( $settings['email_items_title_status_approved_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row gg-form-row-last">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Additional Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="3" name="returndesk_settings[email_additional_content_status_approved_customer]"><?php echo esc_textarea( $settings['email_additional_content_status_approved_customer'] ?? '' ); ?></textarea></div>
									</div>
								</div>

								<div class="gg-email-template-panel gg-hidden" data-template="status_rejected">
									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Enable', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body">
											<div class="gg-toggle-row">
												<label class="gg-toggle-wrap">
													<input type="checkbox" name="returndesk_settings[email_enable_status_update_customer]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['email_enable_status_update_customer'], 'yes' ); ?>>
													<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
												</label>
												<div class="gg-toggle-label"><span class="gg-toggle-main"><?php esc_html_e( 'Send customer email on status changes', 'windcodex-returndesk' ); ?></span></div>
											</div>
										</div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label">
											<div class="gg-row-title"><?php esc_html_e( 'Email Subject', 'windcodex-returndesk' ); ?></div>
										</div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_subject_status_rejected_customer]" value="<?php echo esc_attr( $settings['email_subject_status_rejected_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Heading', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_heading_status_rejected_customer]" value="<?php echo esc_attr( $settings['email_heading_status_rejected_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[email_body_status_rejected_customer]"><?php echo esc_textarea( $settings['email_body_status_rejected_customer'] ?? '' ); ?></textarea></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Return Items Title', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_items_title_status_rejected_customer]" value="<?php echo esc_attr( $settings['email_items_title_status_rejected_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row gg-form-row-last">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Additional Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="3" name="returndesk_settings[email_additional_content_status_rejected_customer]"><?php echo esc_textarea( $settings['email_additional_content_status_rejected_customer'] ?? '' ); ?></textarea></div>
									</div>
								</div>

								<div class="gg-email-template-panel gg-hidden" data-template="status_cancelled">
									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Enable', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body">
											<div class="gg-toggle-row">
												<label class="gg-toggle-wrap">
													<input type="checkbox" name="returndesk_settings[email_enable_status_update_customer]" value="yes" class="gg-toggle-checkbox screen-reader-text" <?php checked( $settings['email_enable_status_update_customer'], 'yes' ); ?>>
													<span class="gg-toggle-track gg-toggle-sm"><span class="gg-toggle-thumb"></span></span>
												</label>
												<div class="gg-toggle-label"><span class="gg-toggle-main"><?php esc_html_e( 'Send customer email on status changes', 'windcodex-returndesk' ); ?></span></div>
											</div>
										</div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label">
											<div class="gg-row-title"><?php esc_html_e( 'Email Subject', 'windcodex-returndesk' ); ?></div>
										</div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_subject_status_cancelled_customer]" value="<?php echo esc_attr( $settings['email_subject_status_cancelled_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Heading', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_heading_status_cancelled_customer]" value="<?php echo esc_attr( $settings['email_heading_status_cancelled_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Email Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="4" name="returndesk_settings[email_body_status_cancelled_customer]"><?php echo esc_textarea( $settings['email_body_status_cancelled_customer'] ?? '' ); ?></textarea></div>
									</div>

									<div class="gg-form-row">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Return Items Title', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><input type="text" class="gg-input" name="returndesk_settings[email_items_title_status_cancelled_customer]" value="<?php echo esc_attr( $settings['email_items_title_status_cancelled_customer'] ?? '' ); ?>"></div>
									</div>

									<div class="gg-form-row gg-form-row-last">
										<div class="gg-row-label"><div class="gg-row-title"><?php esc_html_e( 'Additional Content', 'windcodex-returndesk' ); ?></div></div>
										<div class="gg-row-body"><textarea class="gg-input" rows="3" name="returndesk_settings[email_additional_content_status_cancelled_customer]"><?php echo esc_textarea( $settings['email_additional_content_status_cancelled_customer'] ?? '' ); ?></textarea></div>
									</div>
								</div>
							</div>

							<div class="gg-email-placeholders">
								<div class="gg-email-placeholders-title"><?php esc_html_e( 'Available Placeholders', 'windcodex-returndesk' ); ?></div>
								<div class="gg-email-placeholders-list">
									<code>{site_title}</code>
									<code>{order_date}</code>
									<code>{order_number}</code>
									<code>{customer_full_name}</code>
									<code>{customer_first_name}</code>
									<code>{customer_last_name}</code>
									<code>{customer_username}</code>
									<code>{store_address}</code>
									<code>{phone_number}</code>
									<code>{request_id}</code>
									<code>{order_id}</code>
									<code>{type}</code>
									<code>{status}</code>
									<code>{return_address}</code>
									<code>{return_guidelines}</code>
								</div>
							</div>

							<input type="hidden" id="gg-email-token-return-address" value="<?php echo esc_attr( $rg_return_address ); ?>">
							<input type="hidden" id="gg-email-preview-site" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
						</div>

							<div class="gg-email-customizer-preview">
								<div class="gg-email-preview-toolbar">
									<div class="gg-email-preview-devices button-group">
									<button type="button" class="button gg-email-device-btn is-active" data-preview-device="desktop" aria-label="<?php esc_attr_e( 'Desktop preview', 'windcodex-returndesk' ); ?>">
										<span class="dashicons dashicons-desktop" aria-hidden="true"></span>
									</button>
									<button type="button" class="button gg-email-device-btn" data-preview-device="mobile" aria-label="<?php esc_attr_e( 'Mobile preview', 'windcodex-returndesk' ); ?>">
										<span class="dashicons dashicons-smartphone" aria-hidden="true"></span>
									</button>
								</div>

								<div class="gg-email-preview-test">
									<input type="email" id="gg-email-test-target" autocomplete="email" class="gg-input gg-input-sm" placeholder="<?php esc_attr_e( 'test@example.com', 'windcodex-returndesk' ); ?>">
									<button type="button" id="gg-email-test-btn" class="button button-primary"><?php esc_html_e( 'Send a test email', 'windcodex-returndesk' ); ?></button>
								</div>
							</div>

							<div class="gg-email-preview-meta">
								<div class="gg-email-preview-row">
									<div class="gg-email-preview-label"><?php esc_html_e( 'To', 'windcodex-returndesk' ); ?></div>
									<div class="gg-email-preview-value"><span id="gg-email-preview-to">customer@example.com</span></div>
								</div>
								<div class="gg-email-preview-row">
									<div class="gg-email-preview-label"><?php esc_html_e( 'Subject', 'windcodex-returndesk' ); ?></div>
									<div class="gg-email-preview-value"><span id="gg-email-preview-subject"></span></div>
								</div>
							</div>

							<div class="gg-email-preview-wrap" data-device="desktop">
								<iframe
									id="gg-email-preview-iframe"
									class="gg-email-preview-iframe"
									title="<?php esc_attr_e( 'Email preview', 'windcodex-returndesk' ); ?>"
									sandbox=""
								></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>

		<div class="gg-tab-panel" data-panel="requests">
			<div class="gg-card">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Return Requests', 'windcodex-returndesk' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Latest return requests submitted by customers.', 'windcodex-returndesk' ); ?></div>
					</div>
				</div>
				<div class="gg-row-body">
					<div class="gg-table-toolbar">
						<input type="text" id="gg-requests-search" class="gg-input gg-input-sm" placeholder="<?php esc_attr_e( 'Search by ID, order, reason...', 'windcodex-returndesk' ); ?>">
						<select id="gg-requests-status-filter" class="gg-input gg-input-sm gg-native-select">
							<option value="all"><?php esc_html_e( 'All Statuses', 'windcodex-returndesk' ); ?></option>
							<?php foreach ( $request_statuses as $status_key => $status_label ) : ?>
								<option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<table class="widefat striped gg-requests-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Order', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Type', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Qty', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Status', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Attachment', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Date', 'windcodex-returndesk' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'windcodex-returndesk' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$visible_requests = array();
							foreach ( (array) $requests as $request_row ) {
								if ( ! is_array( $request_row ) ) {
									continue;
								}
								$request_type = sanitize_key( (string) ( $request_row['request_type'] ?? 'return' ) );
								if ( 'return' !== $request_type ) {
									continue;
								}
								$visible_requests[] = $request_row;
							}
							?>
							<?php if ( empty( $visible_requests ) ) : ?>
								<tr><td colspan="9"><?php esc_html_e( 'No requests found.', 'windcodex-returndesk' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $visible_requests as $request ) : ?>
									<?php
									$id       = absint( $request['id'] ?? 0 );
									$order_id = absint( $request['order_id'] ?? 0 );
									$type     = sanitize_key( (string) ( $request['request_type'] ?? 'return' ) );
									$status   = sanitize_key( (string) ( $request['status'] ?? 'pending' ) );
									$reason_raw = sanitize_textarea_field( (string) ( $request['reason'] ?? '' ) );
									$reason_raw = trim( (string) $reason_raw );
									$reason     = '';
									$details    = '';
									if ( '' !== $reason_raw ) {
										$parts = preg_split( "/\\R\\R+/", $reason_raw, 2 );
										if ( is_array( $parts ) && count( $parts ) === 2 ) {
											$reason  = trim( (string) $parts[0] );
											$details = trim( (string) $parts[1] );
										} else {
											$lines = preg_split( "/\\R+/", $reason_raw, 2 );
											if ( is_array( $lines ) && count( $lines ) === 2 ) {
												$reason  = trim( (string) $lines[0] );
												$details = trim( (string) $lines[1] );
											} else {
												$reason = $reason_raw;
											}
										}
									}
									$reason_search = trim( (string) preg_replace( '/\\s+/', ' ', $reason_raw ) );
									$items    = is_array( $request['items'] ?? null ) ? (array) $request['items'] : array();
									$qty      = 0;
									foreach ( $items as $entry ) {
										if ( is_array( $entry ) ) {
											$qty += max( 0, absint( $entry['qty'] ?? 0 ) );
										}
									}
									$attachment_ids = is_array( $request['attachment_ids'] ?? null ) ? (array) $request['attachment_ids'] : array();
									$attachment_cnt = count( $attachment_ids );
									$attachment_title = '';
									if ( $attachment_cnt > 1 ) {
										$attachment_title = sprintf(
											/* translators: %d: number of attachments */
											_n( '%d attachment', '%d attachments', $attachment_cnt, 'windcodex-returndesk' ),
											$attachment_cnt
										);
									}
									$attachment_items = array();
									foreach ( $attachment_ids as $attachment_id ) {
										$attachment_id = absint( $attachment_id );
										if ( $attachment_id <= 0 ) {
											continue;
										}
										$attachment_url = wp_get_attachment_url( $attachment_id );
										if ( ! $attachment_url ) {
											continue;
										}
										$attachment_items[] = array(
											'url'      => esc_url_raw( $attachment_url ),
											'name'     => sanitize_text_field( wp_basename( (string) wp_parse_url( $attachment_url, PHP_URL_PATH ) ) ),
											'is_image' => wp_attachment_is_image( $attachment_id ),
										);
									}
									$status_label   = $request_statuses[ $status ] ?? ucfirst( $status );
									$date           = sanitize_text_field( (string) ( $request['created_at'] ?? '' ) );
									$order_edit_url = $order_id > 0 ? admin_url( 'post.php?post=' . $order_id . '&action=edit' ) : '';
									$refund_method  = sanitize_key( (string) ( $request['refund_method'] ?? '' ) );
									$order_obj      = $order_id > 0 ? wc_get_order( $order_id ) : false;
									?>
									<tr data-request-row="<?php echo esc_attr( $id ); ?>" data-request-status-val="<?php echo esc_attr( $status ); ?>" data-request-type="<?php echo esc_attr( $type ); ?>" data-request-order="<?php echo esc_attr( $order_id ); ?>" data-request-title="<?php echo esc_attr( 'RD-' . $id ); ?>" data-request-reason="<?php echo esc_attr( $reason_search ); ?>">
										<td><?php echo esc_html( 'RD-' . $id ); ?></td>
										<td>
											<?php if ( $order_id > 0 && '' !== $order_edit_url ) : ?>
												<a href="<?php echo esc_url( $order_edit_url ); ?>" target="_blank" rel="noopener noreferrer">#<?php echo esc_html( $order_id ); ?></a>
											<?php else : ?>
												#<?php echo esc_html( $order_id ); ?>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( ucfirst( $type ) ); ?></td>
										<td><?php echo esc_html( $qty ); ?></td>
										<td><span class="gg-request-status gg-status-<?php echo esc_attr( $status ); ?>" data-request-status title="<?php esc_attr_e( 'Click to view details', 'windcodex-returndesk' ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
										<td>
											<div class="gg-request-reason">
												<?php if ( '' !== $reason ) : ?>
													<div class="gg-request-reason-main"><?php echo esc_html( $reason ); ?></div>
												<?php endif; ?>
												<?php if ( '' !== $details ) : ?>
													<div class="gg-request-reason-details"><?php echo esc_html( $details ); ?></div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<?php if ( ! empty( $attachment_items ) ) : ?>
												<button
													type="button"
													class="gg-attachment-view"
													data-attachments="<?php echo esc_attr( wp_json_encode( $attachment_items ) ); ?>"
												>
													<?php esc_html_e( 'View', 'windcodex-returndesk' ); ?>
												</button>
												<?php if ( $attachment_cnt > 1 ) : ?>
													<span class="gg-attachment-count" title="<?php echo esc_attr( $attachment_title ); ?>">
														(<?php echo esc_html( $attachment_cnt ); ?>)
													</span>
												<?php endif; ?>
											<?php else : ?>
												&ndash;
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( $date ); ?></td>
											<td>
												<div class="gg-request-actions">
													<?php if ( 'pending' === $status ) : ?>
														<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-approve" data-request-action="approved" data-request-id="<?php echo esc_attr( $id ); ?>" title="<?php echo esc_attr__( 'Approve', 'windcodex-returndesk' ); ?>" aria-label="<?php echo esc_attr__( 'Approve', 'windcodex-returndesk' ); ?>">
															<span class="dashicons dashicons-yes-alt gg-action-icon" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Approve', 'windcodex-returndesk' ); ?></span>
														</button>
														<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-reject" data-request-action="rejected" data-request-id="<?php echo esc_attr( $id ); ?>" title="<?php echo esc_attr__( 'Reject', 'windcodex-returndesk' ); ?>" aria-label="<?php echo esc_attr__( 'Reject', 'windcodex-returndesk' ); ?>">
															<span class="dashicons dashicons-no-alt gg-action-icon" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Reject', 'windcodex-returndesk' ); ?></span>
														</button>
													<?php elseif ( in_array( $status, array( 'rejected', 'cancelled' ), true ) ) : ?>
														<button type="button" class="gg-btn-mini gg-btn-mini-icon gg-btn-mini-danger" data-request-delete="1" data-request-id="<?php echo esc_attr( $id ); ?>" title="<?php echo esc_attr__( 'Delete', 'windcodex-returndesk' ); ?>" aria-label="<?php echo esc_attr__( 'Delete', 'windcodex-returndesk' ); ?>">
															<span class="dashicons dashicons-trash gg-action-icon" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'windcodex-returndesk' ); ?></span>
														</button>
													<?php endif; ?>
												</div>
											</td>
									</tr>
									<tr class="gg-request-details-row" data-request-details-row="<?php echo esc_attr( $id ); ?>" style="display:none;">
										<td colspan="9">
											<div class="gg-request-details-card">
												<div class="gg-request-view-status">
													<strong><?php esc_html_e( 'Status:', 'windcodex-returndesk' ); ?></strong>
													<span class="gg-request-status gg-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_label ); ?></span>
												</div>
												<div class="gg-request-view-section-title"><?php esc_html_e( 'Returned Items', 'windcodex-returndesk' ); ?></div>
												<?php if ( ! empty( $items ) ) : ?>
													<?php $show_item_label = ( count( $items ) > 1 ); ?>
													<?php foreach ( $items as $item_index => $entry ) : ?>
														<?php
														$line_item_id = absint( $entry['order_item_id'] ?? 0 );
														$line_qty     = max( 1, absint( $entry['qty'] ?? 0 ) );
														$line_name    = sprintf(
															/* translators: %d: order item id */
															__( 'Item #%d', 'windcodex-returndesk' ),
															$line_item_id
														);
														$line_price     = '';
														if ( $order_obj instanceof WC_Order && $line_item_id > 0 ) {
															$order_item = $order_obj->get_item( $line_item_id );
															if ( $order_item && is_callable( array( $order_item, 'get_name' ) ) ) {
																$line_name = sanitize_text_field( (string) $order_item->get_name() );
															}
															if ( $order_item && is_callable( array( $order_item, 'get_total' ) ) && is_callable( array( $order_item, 'get_quantity' ) ) ) {
																$ordered_qty = max( 1, absint( $order_item->get_quantity() ) );
																$line_total  = ( (float) $order_item->get_total() / $ordered_qty ) * $line_qty;
																$line_price  = wp_kses_post( wc_price( $line_total, array( 'currency' => $order_obj->get_currency() ) ) );
															}
														}
														?>
														<?php if ( $show_item_label ) : ?>
															<div class="gg-request-view-item-index"><?php echo esc_html( sprintf( __( 'Item %d', 'windcodex-returndesk' ), absint( $item_index ) + 1 ) ); ?></div>
														<?php endif; ?>
														<table class="gg-request-view-table" cellspacing="0" cellpadding="0">
															<tr><th><?php esc_html_e( 'Product Name', 'windcodex-returndesk' ); ?></th><td><?php echo esc_html( $line_name ); ?></td></tr>
															<tr><th><?php esc_html_e( 'Quantity', 'windcodex-returndesk' ); ?></th><td><?php echo esc_html( (string) $line_qty ); ?></td></tr>
															<tr><th><?php esc_html_e( 'Price', 'windcodex-returndesk' ); ?></th><td><?php echo '' !== $line_price ? wp_kses_post( $line_price ) : '&ndash;'; ?></td></tr>
														</table>
													<?php endforeach; ?>
												<?php endif; ?>
												<?php if ( '' !== $reason ) : ?>
													<p class="gg-request-view-reason"><strong><?php esc_html_e( 'Return Reason:', 'windcodex-returndesk' ); ?></strong> <?php echo esc_html( $reason ); ?></p>
												<?php endif; ?>
												<?php if ( '' !== $details ) : ?>
													<p class="gg-request-view-reason"><strong><?php esc_html_e( 'Return Message:', 'windcodex-returndesk' ); ?></strong> <?php echo esc_html( $details ); ?></p>
												<?php endif; ?>
												<?php if ( ! empty( $attachment_items ) ) : ?>
													<div class="gg-request-details-block">
														<strong><?php esc_html_e( 'Attachments:', 'windcodex-returndesk' ); ?></strong>
														<ul class="gg-request-details-items">
															<?php foreach ( $attachment_items as $file ) : ?>
																<li><a href="<?php echo esc_url( (string) ( $file['url'] ?? '' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) ( $file['name'] ?? __( 'Attachment', 'windcodex-returndesk' ) ) ); ?></a></li>
															<?php endforeach; ?>
														</ul>
													</div>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="gg-footer-bar" id="gg-footer-bar" data-tab-visible="general,advanced">
			<div class="gg-footer-left">
				<button type="button" id="gg-save-btn" class="gg-btn-primary"><span class="gg-btn-label"><?php esc_html_e( 'Save Settings', 'windcodex-returndesk' ); ?></span></button>
				<button type="button" id="gg-reset-btn" class="gg-btn-reset"><span class="gg-btn-label"><?php esc_html_e( 'Reset to Defaults', 'windcodex-returndesk' ); ?></span></button>
			</div>
			<div class="gg-toast" id="gg-toast"></div>
		</div>
	</form>
</div>
