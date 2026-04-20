<?php
/**
 * Custom table storage for return requests (free plugin).
 *
 */

defined( 'ABSPATH' ) || exit;

class ReturnDesk_Requests_Store {

	public const TABLE_VERSION = '1.0.0';

	public static function table_name(): string {
		global $wpdb;
		return (string) $wpdb->prefix . 'returndesk_requests';
	}

	public static function maybe_install(): void {
		$installed = (string) get_option( 'returndesk_requests_table_version', '' );
		if ( self::TABLE_VERSION === $installed ) {
			return;
		}
		self::install();
	}

	public static function install(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			customer_id BIGINT(20) UNSIGNED NULL,
			guest_email VARCHAR(190) NULL,
			request_type VARCHAR(20) NOT NULL,
			status VARCHAR(20) NOT NULL,
			reason TEXT NULL,
			items_json LONGTEXT NOT NULL,
			attachment_ids_json LONGTEXT NULL,
			refund_method VARCHAR(50) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY customer_id (customer_id),
			KEY guest_email (guest_email),
			KEY status (status),
			KEY request_type (request_type),
			KEY created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'returndesk_requests_table_version', self::TABLE_VERSION );
	}

	public function insert( array $data ): int {
		global $wpdb;

		$now = current_time( 'mysql' );
		$row = array(
			'order_id'             => absint( $data['order_id'] ?? 0 ),
			'customer_id'          => isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : null,
			'guest_email'          => isset( $data['guest_email'] ) ? sanitize_email( (string) $data['guest_email'] ) : null,
			'request_type'         => sanitize_key( (string) ( $data['request_type'] ?? 'return' ) ),
			'status'               => sanitize_key( (string) ( $data['status'] ?? 'pending' ) ),
			'reason'               => isset( $data['reason'] ) ? sanitize_textarea_field( (string) $data['reason'] ) : null,
			'items_json'           => wp_json_encode( is_array( $data['items'] ?? null ) ? $data['items'] : array() ),
			'attachment_ids_json'  => wp_json_encode( is_array( $data['attachment_ids'] ?? null ) ? $data['attachment_ids'] : array() ),
			'refund_method'        => isset( $data['refund_method'] ) ? sanitize_key( (string) $data['refund_method'] ) : null,
			'created_at'           => $now,
			'updated_at'           => $now,
		);

		// NULL handling for $wpdb->insert: remove nulls so defaults apply.
		foreach ( $row as $key => $value ) {
			if ( null === $value ) {
				unset( $row[ $key ] );
			}
		}

		$ok = $wpdb->insert( self::table_name(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $ok ) {
			return 0;
		}

		return absint( $wpdb->insert_id );
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$table = self::table_name();

		$id = absint( $id );
		if ( $id <= 0 ) {
			return null;
		}

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->hydrate_row( $row );
	}

	public function list_by_customer( int $customer_id, int $limit = 20 ): array {
		global $wpdb;
		$table       = self::table_name();
		$customer_id = absint( $customer_id );
		$limit       = max( 1, min( 200, absint( $limit ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} WHERE customer_id = %d ORDER BY id DESC LIMIT %d",
				$customer_id,
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_rows( $rows );
	}

	public function list_by_order_for_customer( int $customer_id, int $order_id, int $limit = 50 ): array {
		global $wpdb;
		$table       = self::table_name();
		$customer_id = absint( $customer_id );
		$order_id    = absint( $order_id );
		$limit       = max( 1, min( 200, absint( $limit ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} WHERE customer_id = %d AND order_id = %d ORDER BY id DESC LIMIT %d",
				$customer_id,
				$order_id,
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_rows( $rows );
	}

	public function list_by_order_for_guest( int $order_id, string $guest_email, int $limit = 50 ): array {
		global $wpdb;
		$table       = self::table_name();
		$order_id    = absint( $order_id );
		$guest_email = sanitize_email( $guest_email );
		$limit       = max( 1, min( 200, absint( $limit ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} WHERE order_id = %d AND guest_email = %s ORDER BY id DESC LIMIT %d",
				$order_id,
				$guest_email,
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_rows( $rows );
	}

	public function list_by_order( int $order_id, int $limit = 200 ): array {
		global $wpdb;
		$table    = self::table_name();
		$order_id = absint( $order_id );
		$limit    = max( 1, min( 500, absint( $limit ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT %d",
				$order_id,
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_rows( $rows );
	}

	public function list_recent( int $limit = 50 ): array {
		global $wpdb;
		$table = self::table_name();
		$limit = max( 1, min( 500, absint( $limit ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by the plugin.
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_rows( $rows );
	}

	public function update_status( int $id, string $status ): bool {
		global $wpdb;
		$id     = absint( $id );
		$status = sanitize_key( $status );
		if ( $id <= 0 || '' === $status ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table_name(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	public function delete( int $id ): bool {
		global $wpdb;
		$id = absint( $id );
		if ( $id <= 0 ) {
			return false;
		}
		$deleted = $wpdb->delete( self::table_name(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $deleted;
	}

	private function hydrate_rows( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$out[] = $this->hydrate_row( $row );
			}
		}
		return $out;
	}

	private function hydrate_row( array $row ): array {
		$row['id']          = absint( $row['id'] ?? 0 );
		$row['order_id']    = absint( $row['order_id'] ?? 0 );
		$row['customer_id'] = isset( $row['customer_id'] ) ? absint( $row['customer_id'] ) : 0;
		$row['guest_email'] = sanitize_email( (string) ( $row['guest_email'] ?? '' ) );
		$row['request_type'] = sanitize_key( (string) ( $row['request_type'] ?? '' ) );
		$row['status']      = sanitize_key( (string) ( $row['status'] ?? '' ) );
		$row['reason']      = sanitize_textarea_field( (string) ( $row['reason'] ?? '' ) );
		$row['items']       = $this->decode_json_array( (string) ( $row['items_json'] ?? '' ) );
		$row['attachment_ids'] = $this->decode_json_array( (string) ( $row['attachment_ids_json'] ?? '' ) );
		$row['refund_method'] = sanitize_key( (string) ( $row['refund_method'] ?? '' ) );
		return $row;
	}

	private function decode_json_array( string $raw ): array {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
