<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class PostMetaStore extends \WC_Order_Data_Store_CPT implements \WC_Object_Data_Store_Interface {

	/**
	 * Define subscription specific data which augments the meta of an order.
	 *
	 * The meta keys here determine the prop data that needs to be manually set.
	 * We can't use the $internal_meta_keys property from WC_Order_Data_Store_CPT because we want its value too, so instead we create our own and merge it into $internal_meta_keys in __construct.
	 *
	 * @var string[]
	 */
	private const SUBSCRIPTION_INTERNAL_META_KEYS = [
		'_trial_end_date_utc',
		'_end_date_utc',
		'_start_date_utc',
		'_cancelled_date_utc',
		'_current_period_start_utc',
		'_current_period_end_utc',
		'_last_payment_request_id',
		'_trial_interval',
		'_expiration_interval',
	];

	/**
	 * Array of subscription specific data which augments the meta of an order in the form meta_key => prop_key
	 *
	 * Used to read/update props on the subscription.
	 *
	 * @var array<string, string>
	 */
	private const META_TO_PROPS_MAP = [
		'_requires_manual_renewal'               => 'requires_manual_renewal',
		'_recent_payment_request_id'             => 'recent_payment_request_id',

		'_billing_frequency'                     => 'billing_frequency',

		'_trial_end_date_utc'                    => 'trial_end_date',
		'_trial_interval'                        => 'trial_interval',
		'_expiration_interval'                   => 'expiration_interval',
		'_cancelled_date_utc'                    => 'cancelled_date',
		'_end_date_utc'                          => 'end_date',
		'_start_date_utc'                        => 'start_date',
		'_current_period_start_utc'              => 'current_period_start',
		'_current_period_end_utc'                => 'current_period_end',
	];

	/**
	 * Properties set by \WC_Order, which are unnecessary in subscription context.
	 */
	private const IGNORED_PROPS = [
		'transaction_id',
		'date_completed',
		'date_paid',
		'cart_hash',
	];

	public function __construct() {
		$this->internal_meta_keys = array_merge( $this->internal_meta_keys, self::SUBSCRIPTION_INTERNAL_META_KEYS );
	}

	/**
	 * @param Subscription $subscription
	 */
	#[\Override]
	public function update( &$subscription ): void {
		// We don't want to call parent here becuase WC_Order_Data_Store_CPT includes a JIT setting of the paid date which is not needed for subscriptions, and also very resource intensive.
		\Abstract_WC_Order_Data_Store_CPT::update( $subscription );
	}

	/**
	 * @param Subscription $subscription
	 */
	#[\Override]
	public function create( &$subscription ): void {
		$subscription_status = $subscription->get_status();

		if ( empty( $subscription_status ) ) {
			$subscription->set_status( Status::PENDING );
		}

		parent::create( $subscription );
	}

	#[\Override]
	final protected function update_post_meta( &$subscription ): void {
		foreach ( $this->get_props_to_update( $subscription, self::META_TO_PROPS_MAP ) as $meta_key => $prop ) {
			if ( 'trial_interval' === $prop ) {
				$prop = 'legacy_trial_interval';
			}

			// @phpstan-ignore method.dynamicName
			$meta_value = $subscription->{"get_$prop"}( 'edit' );

			if ( $meta_value instanceof \DateTimeInterface ) {
				$meta_value = $meta_value->setTimezone( new \DateTimeZone( 'UTC' ) );
				$meta_value = $meta_value->format( 'Y-m-d H:i:s' );
			}

			if ( $meta_value instanceof WPInterval ) {
				$meta_value = (string) $meta_value;
			}

			$this->update_or_delete_post_meta( $subscription, $meta_key, $meta_value );
		}

		parent::update_post_meta( $subscription );
	}

	/**
	 * @param array<string, string> $meta_key_to_props
	 *
	 * @return array<string, string>
	 */
	#[\Override]
	final protected function get_props_to_update( $object, $meta_key_to_props, $meta_type = 'post' ): array {
		$props_to_update   = [];
		$changed_props     = $object->get_changes();
		$meta_key_to_props = array_filter(
			$meta_key_to_props,
			static fn ( $prop ) => ! in_array( $prop, self::IGNORED_PROPS, true ),
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( str_ends_with( $meta_key, '_utc' ) ) {
				if ( array_key_exists( $prop . '_utc', $changed_props ) || array_key_exists( $prop, $changed_props ) ) {
					$props_to_update[ $meta_key ] = $prop;
					continue;
				}
			}

			if ( array_key_exists( $prop, $changed_props ) || ! metadata_exists( $meta_type, $object->get_id(), $meta_key ) ) {
				$props_to_update[ $meta_key ] = $prop;
			}
		}

		return $props_to_update;
	}

	/**
	 * @param Subscription $subscription
	 *
	 * @return void
	 */
	#[\Override]
	protected function read_order_data( &$subscription, $post_object ) {
		parent::read_order_data( $subscription, $post_object );

		$props_to_set = [];

		foreach ( self::META_TO_PROPS_MAP as $meta_key => $prop_key ) {
			if ( in_array( $meta_key, self::SUBSCRIPTION_INTERNAL_META_KEYS, true ) ) {
				try {
					$meta_value = get_post_meta( $subscription->get_id(), $meta_key, true );
				} catch ( \Exception $e ) {
					// There were some errors, possibly during saving and our data doesn't make
					// sense. Ignore when setting props and handle gracefully on Subscription
					// methods calls.
					continue;
				}

				if ( empty( $meta_value ) ) {
					continue;
				}

				$meta_value                = $this->normalize_meta_value_for_prop( $meta_key, $meta_value );
				$props_to_set[ $prop_key ] = $meta_value;
			}
		}

		$subscription->set_props( $props_to_set );
	}
	/**
	 * @param mixed $meta_value
	 * @return mixed
	 */
	private function normalize_meta_value_for_prop( string $meta_key, $meta_value ) {

		if ( str_ends_with( $meta_key, '_utc' ) ) {
			return new \DateTimeImmutable( (string) $meta_value, new \DateTimeZone( 'UTC' ) );
		}
		if ( in_array( $meta_key, [ '_billing_frequency', '_trial_interval', '_expiration_interval' ], true ) ) {
			return new WPInterval( (string) $meta_value );
		}

		return $meta_value;
	}
}
