<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\OrderDataTransfer\OrderDataTransfer;

/**
 * Applies subscription-specific copying rules to the generic order data transfer.
 */
final class OrderDataCopier {

	/** @var string[] */
	private const IMMUTABLE_DATA_KEYS = [
		'id',
	];

	private const FLEXIBLE_SUBSCRIPTIONS_EXCLUDED_META_KEYS = [
		'_order_number',
		'_subscription_switch_data',
		'_billing_interval',
		'_billing_period',
		'_subscription_resubscribe',
		'_subscription_renewal',
		'_subscription_switch',
		'_suspension_count',
		'_requires_manual_renewal',
		'_cancelled_email_sent',
		'_last_order_date_created',
		'_trial_period',
		'id',
		'_recent_payment_request_id',
		'_billing_frequency',
		'_trial_end_date_utc',
		'_trial_interval',
		'_expiration_interval',
		'_cancelled_date_utc',
		'_end_date_utc',
		'_start_date_utc',
		'_current_period_start_utc',
		'_current_period_end_utc',
		'_last_payment_request_id',
		'_fsb_billing_cycle',
		'_fsb_order_type',
		'_fsb_renewal_period_advanced',
	];

	private const RENEWAL_EXCLUDED_META_KEYS = [
		'_download_permissions_granted',
	];

	private OrderDataTransfer $order_data_transfer;

	public function __construct( OrderDataTransfer $order_data_transfer ) {
		$this->order_data_transfer = $order_data_transfer;
	}

	public function copy_subscription_meta( \WC_Order $from_object, \WC_Order $to_object ): void {
		$this->order_data_transfer->copy_meta_data(
			$from_object,
			$to_object,
			self::FLEXIBLE_SUBSCRIPTIONS_EXCLUDED_META_KEYS,
			fn( array $data ): array => $this->apply_filters( $data, $to_object, $from_object, 'subscription' ),
			fn( array $keys ): array => $this->filter_excluded_meta_keys( $keys, $to_object, $from_object, 'subscription' )
		);
	}

	public function copy_renewal_data( \WC_Order $from_object, \WC_Order $to_object ): void {
		$this->order_data_transfer->copy(
			$from_object,
			$to_object,
			[
				'excluded_meta_keys'        => array_merge(
					self::FLEXIBLE_SUBSCRIPTIONS_EXCLUDED_META_KEYS,
					self::RENEWAL_EXCLUDED_META_KEYS
				),
				'excluded_meta_keys_filter' => fn( array $keys ): array => $this->filter_excluded_meta_keys( $keys, $to_object, $from_object, 'renewal_order' ),
				'data_filter'               => fn( array $data ): array => $this->apply_filters( $data, $to_object, $from_object, 'renewal_order' ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function apply_filters( array $data, \WC_Order $to_object, \WC_Order $from_object, string $copy_type ): array {
		$legacy_data = $this->to_legacy_meta_array( $data );
		$legacy_data = apply_filters( "wcs_{$copy_type}_meta", $legacy_data, $to_object, $from_object );
		$data        = $this->from_legacy_meta_array( $legacy_data );

		$data = apply_filters( "fsub/{$copy_type}/data", $data, $to_object, $from_object );
		$data = apply_filters( 'fsub/object/data', $data, $to_object, $from_object, $copy_type );

		$data = apply_filters( "wc_subscriptions_{$copy_type}_data", $data, $to_object, $from_object );
		$data = apply_filters( 'wc_subscriptions_object_data', $data, $to_object, $from_object, $copy_type );

		return $this->remove_immutable_data( $data );
	}

	/**
	 * @param string[] $excluded_keys
	 * @return string[]
	 */
	private function filter_excluded_meta_keys( array $excluded_keys, \WC_Order $to_object, \WC_Order $from_object, string $copy_type ): array {
		$excluded_keys = apply_filters( "fsub/{$copy_type}/excluded_meta_keys", $excluded_keys, $to_object, $from_object );
		return apply_filters( 'fsub/object/excluded_meta_keys', $excluded_keys, $to_object, $from_object, $copy_type );
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<int, array{meta_key: string, meta_value: mixed}>
	 */
	private function to_legacy_meta_array( array $data ): array {
		$legacy_data = [];

		foreach ( $data as $key => $value ) {
			$legacy_data[] = [
				'meta_key'   => (string) $key,
				'meta_value' => $value,
			];
		}

		return $legacy_data;
	}

	/**
	 * @param array<int, array{meta_key?: mixed, meta_value?: mixed}> $legacy_data
	 * @return array<string, mixed>
	 */
	private function from_legacy_meta_array( array $legacy_data ): array {
		$data = [];

		foreach ( $legacy_data as $row ) {
			if ( ! isset( $row['meta_key'] ) ) {
				continue;
			}

			$data[ (string) $row['meta_key'] ] = $row['meta_value'] ?? null;
		}

		return $data;
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function remove_immutable_data( array $data ): array {
		foreach ( self::IMMUTABLE_DATA_KEYS as $key ) {
			unset( $data[ $key ] );
		}

		return $data;
	}
}
