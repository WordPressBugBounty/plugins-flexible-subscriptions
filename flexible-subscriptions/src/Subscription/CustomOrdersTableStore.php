<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

class CustomOrdersTableStore extends OrdersTableDataStore {
	/**
	 * Define subscription specific data which augments the meta of an order.
	 *
	 * The meta keys here determine the prop data that needs to be manually set. We can't use
	 * the $internal_meta_keys property from OrdersTableDataStore because we want its value
	 * too, so instead we create our own and merge it into $internal_meta_keys in __construct.
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
		'_cancelled_date_utc'                    => 'cancelled_date',
		'_end_date_utc'                          => 'end_date',
		'_start_date_utc'                        => 'start_date',
		'_current_period_start_utc'              => 'current_period_start',
		'_current_period_end_utc'                => 'current_period_end',
	];

	/**
	 * Table column to Subscription mapping for wc_orders table.
	 *
	 * All columns are inherited from orders except the `transaction_id` column isn't used for subscriptions.
	 *
	 * @var \string[][]
	 */
	protected $order_column_mapping = [
		'id'                   => [
			'type' => 'int',
			'name' => 'id',
		],
		'status'               => [
			'type' => 'string',
			'name' => 'status',
		],
		'type'                 => [
			'type' => 'string',
			'name' => 'type',
		],
		'currency'             => [
			'type' => 'string',
			'name' => 'currency',
		],
		'tax_amount'           => [
			'type' => 'decimal',
			'name' => 'cart_tax',
		],
		'total_amount'         => [
			'type' => 'decimal',
			'name' => 'total',
		],
		'customer_id'          => [
			'type' => 'int',
			'name' => 'customer_id',
		],
		'billing_email'        => [
			'type' => 'string',
			'name' => 'billing_email',
		],
		'date_created_gmt'     => [
			'type' => 'date',
			'name' => 'date_created',
		],
		'date_updated_gmt'     => [
			'type' => 'date',
			'name' => 'date_modified',
		],
		'parent_order_id'      => [
			'type' => 'int',
			'name' => 'parent_id',
		],
		'payment_method'       => [
			'type' => 'string',
			'name' => 'payment_method',
		],
		'payment_method_title' => [
			'type' => 'string',
			'name' => 'payment_method_title',
		],
		'ip_address'           => [
			'type' => 'string',
			'name' => 'customer_ip_address',
		],
		'user_agent'           => [
			'type' => 'string',
			'name' => 'customer_user_agent',
		],
		'customer_note'        => [
			'type' => 'string',
			'name' => 'customer_note',
		],
	];

	/**
	 * Table column to Subscription mapping for wc_operational_data table.
	 *
	 * For subscriptions, all columns are inherited from orders except for the following columns:
	 *
	 * - cart_hash
	 * - new_order_email_sent
	 * - order_stock_reduced
	 * - date_paid_gmt
	 * - recorded_sales
	 * - date_completed_gmt
	 *
	 * @var \string[][]
	 */
	protected $operational_data_column_mapping = [
		'id'                          => [ 'type' => 'int' ],
		'order_id'                    => [ 'type' => 'int' ],
		'created_via'                 => [
			'type' => 'string',
			'name' => 'created_via',
		],
		'woocommerce_version'         => [
			'type' => 'string',
			'name' => 'version',
		],
		'prices_include_tax'          => [
			'type' => 'bool',
			'name' => 'prices_include_tax',
		],
		'coupon_usages_are_counted'   => [
			'type' => 'bool',
			'name' => 'recorded_coupon_usage_counts',
		],
		'download_permission_granted' => [
			'type' => 'bool',
			'name' => 'download_permissions_granted',
		],
		'order_key'                   => [
			'type' => 'string',
			'name' => 'order_key',
		],
		'shipping_tax_amount'         => [
			'type' => 'decimal',
			'name' => 'shipping_tax',
		],
		'shipping_total_amount'       => [
			'type' => 'decimal',
			'name' => 'shipping_total',
		],
		'discount_tax_amount'         => [
			'type' => 'decimal',
			'name' => 'discount_tax',
		],
		'discount_total_amount'       => [
			'type' => 'decimal',
			'name' => 'discount_total',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Exclude the subscription related meta data we set and manage manually from the objects "meta" data.
		$this->internal_meta_keys = array_merge( $this->internal_meta_keys, self::SUBSCRIPTION_INTERNAL_META_KEYS );
	}

	/**
	 * Returns data store object to use backfilling.
	 *
	 * @return PostMetaStore
	 */
	#[\Override]
	protected function get_post_data_store_for_backfill() {
		return new PostMetaStore();
	}

	/**
	 * Returns count of subscriptions with a specific status.
	 *
	 * @param string $status Subscription status. The wcs_get_subscription_statuses() function returns a list of valid statuses.
	 *
	 * @return int The number of subscriptions with a specific status.
	 */
	#[\Override]
	public function get_order_count( $status ) {
		global $wpdb;

		// phpcs:disable WordPress.DB
		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i WHERE type = 'fsb_subscription' AND status = %s",
					self::get_orders_table_name(),
					$status
				)
			)
		);
		// phpcs:enable
	}

	/**
	 * Get all subscriptions matching the passed in args.
	 *
	 * @param array $args
	 *
	 * @return array of orders
	 */
	#[\Override]
	public function get_orders( $args = [] ) {
		$args        = wp_parse_args(
			$args,
			[
				'type'   => 'fsb_subscription',
				'return' => 'objects',
			]
		);
		$parent_args = $args;

		// We only want IDs from the parent method
		$parent_args['return'] = 'ids';

		$subscriptions = wc_get_orders( $parent_args );

		if ( isset( $args['paginate'] ) && $args['paginate'] ) {

			if ( 'objects' === $args['return'] ) {
				$return = array_map( 'wcs_get_subscription', $subscriptions->orders );
			} else {
				$return = $subscriptions->orders;
			}

			return (object) [
				'orders'        => $return,
				'total'         => $subscriptions->total,
				'max_num_pages' => $subscriptions->max_num_pages,
			];

		} else {

			if ( 'objects' === $args['return'] ) {
				$return = array_map( 'wcs_get_subscription', $subscriptions );
			} else {
				$return = $subscriptions;
			}

			return $return;
		}
	}

	/**
	 * @param Subscription $subscription
	 */
	#[\Override]
	public function create( &$subscription ) {
		$subscription_status = $subscription->get_status();

		if ( empty( $subscription_status ) ) {
			$subscription->set_status( Status::PENDING );
		}
		parent::create( $subscription );
		// do_action( 'woocommerce_new_subscription', $subscription->get_id() );
	}

	/**
	 * Updates a subscription in the database.
	 *
	 * @param Subscription $subscription Subscription object
	 */
	#[\Override]
	public function update( &$subscription ) {
		// We don't want to call parent::update() here because OrdersTableDataStore includes a JIT setting of the paid date which is not needed for subscriptions, and also very resource intensive due to needed to search related orders to get the latest orders paid date.
		if ( null === $subscription->get_date_created( 'edit' ) ) {
			$subscription->set_date_created( time() );
		}

		$subscription->set_version( \Automattic\Jetpack\Constants::get_constant( 'WC_VERSION' ) );

		// Fetch changes.
		$changes = $subscription->get_changes();
		$this->persist_updates( $subscription );

		// Update download permissions if necessary.
		if ( array_key_exists( 'billing_email', $changes ) || array_key_exists( 'customer_id', $changes ) ) {
			$data_store = \WC_Data_Store::load( 'customer-download' );
			$data_store->update_user_by_order_id( $subscription->get_id(), $subscription->get_customer_id(), $subscription->get_billing_email() );
		}

		// Mark user account as active.
		if ( array_key_exists( 'customer_id', $changes ) ) {
			wc_update_user_last_active( $subscription->get_customer_id() );
		}

		$subscription->apply_changes();
		$this->clear_caches( $subscription );

		// For backwards compatibility we trigger the `woocommerce_update_order` hook.
		// do_action( 'woocommerce_update_order', $subscription->get_id(), $subscription );
		// do_action( 'woocommerce_update_subscription', $subscription->get_id(), $subscription );
	}

	/**
	 * Saves a subscription to the database.
	 *
	 * When a subscription is saved to the database we need to ensure we also save core subscription properties. The
	 * parent::persist_order_to_db() will create and save the WC_Order inherited data, this method will save the
	 * subscription core properties.
	 *
	 * @param Subscription $subscription The subscription to save.
	 * @param bool         $force_all_fields Optional. Whether to force all fields to be saved. Default false.
	 */
	#[\Override]
	protected function persist_order_to_db( &$subscription, bool $force_all_fields = false ) {
		$is_update = ( 0 !== absint( $subscription->get_id() ) );

		// Call the parent function first so WC can get an ID if this a new subscription.
		parent::persist_order_to_db( $subscription, $force_all_fields );

		// Get the subscription's current raw metadata.
		$subscription_meta_data = array_column( $this->data_store_meta->read_meta( $subscription ), null, 'meta_key' );

		// Determine what fields need to be saved. Forcing all fields to be saved is only allowed when updating.
		if ( $force_all_fields && $is_update ) {
			$props_to_save = self::META_TO_PROPS_MAP;
		} else {
			$props_to_save = $this->get_props_to_update( $subscription, self::META_TO_PROPS_MAP );
		}

		foreach ( $props_to_save as $meta_key => $prop ) {
			$meta_value = $subscription->{"get_$prop"}( 'edit' );

			if ( $meta_value instanceof \DateTimeInterface ) {
				$meta_value = $meta_value->setTimezone( new \DateTimeZone( 'UTC' ) );
				$meta_value = $meta_value->format( 'Y-m-d H:i:s' );
			}

			if ( $meta_value instanceof WPInterval ) {
				$meta_value = (string) $meta_value;
			}

			$existing_meta_data = $subscription_meta_data[ $meta_key ] ?? null;
			$new_meta_data      = [
				'key'   => $meta_key,
				'value' => $meta_value,
			];

			if ( $existing_meta_data === null ) {
				$this->data_store_meta->add_meta( $subscription, (object) $new_meta_data );
			} elseif ( $existing_meta_data->meta_value !== $new_meta_data['value'] ) {
				$new_meta_data['id'] = $existing_meta_data->meta_id;
				$this->data_store_meta->update_meta( $subscription, (object) $new_meta_data );
			}
		}
	}

	/**
	 * Initializes the subscription based on data received from the database.
	 *
	 * @param WC_Abstract_Order $subscription      The subscription object.
	 * @param int               $subscription_id   The subscription's ID.
	 * @param stdClass          $subscription_data All the subscription's data, retrieved from the database.
	 */
	#[\Override]
	protected function init_order_record( \WC_Abstract_Order &$subscription, int $subscription_id, \stdClass $subscription_data ) {
		// Call the parent version of this function which will set all the core order properties that a subscription inherits.
		parent::init_order_record( $subscription, $subscription_id, $subscription_data );

		if ( empty( $subscription_data->meta_data ) ) {
			return;
		}

		// Flag the subscription as still being read from the database while we set our subscription properties.
		$subscription->set_object_read( false );

		// Set subscription specific properties that we store in meta.
		$meta_data    = wp_list_pluck( $subscription_data->meta_data, 'meta_value', 'meta_key' );
		$props_to_set = [];

		foreach ( self::META_TO_PROPS_MAP as $meta_key => $prop_key ) {
			$is_internal_meta = in_array( $meta_key, $this->internal_meta_keys, true );

			// We only need to set props that are internal meta keys or dates. Everything else is treated as meta.
			if ( $is_internal_meta === false ) {
				continue;
			}

			// If there's no meta data, we don't need to set anything.
			if ( ! isset( $meta_data[ $meta_key ] ) ) {
				continue;
			}

			try {
				$props_to_set[ $prop_key ] = maybe_unserialize( $meta_data[ $meta_key ] );
			} catch ( \Exception $e ) {
				// There were some errors, possibly during saving and our data doesn't make
				// sense. Ignore when setting props and handle gracefully on Subscription methods
				// calls.
			}
		}

		$subscription->set_props( $props_to_set );

		// Flag the subscription as read.
		$subscription->set_object_read( true );
	}

	/**
	 * Produces an array with keys 'row' and 'format' that can be passed to `$wpdb->update()` as the `$data` and
	 * `$format` parameters. Values are taken from the order changes array and properly formatted for inclusion in the
	 * database.
	 *
	 * @param \WC_Abstract_Order $order          Order.
	 * @param array              $column_mapping Table column mapping.
	 * @param bool               $only_changes   Whether to consider only changes in the order object or all fields.
	 * @return array
	 *
	 * @since 6.8.0
	 */
	#[\Override]
	protected function get_db_row_from_order( $order, $column_mapping, $only_changes = false ) {
		$changes = $only_changes ? $order->get_changes() : array_merge( $order->get_data(), $order->get_changes() );

		// Make sure 'status' is correctly prefixed.
		if ( array_key_exists( 'status', $column_mapping ) && array_key_exists( 'status', $changes ) ) {
			$changes['status'] = $this->get_post_status( $order );
		}

		$row        = [];
		$row_format = [];

		foreach ( $column_mapping as $column => $details ) {
			if ( ! isset( $details['name'] ) || ! array_key_exists( $details['name'], $changes ) ) {
				continue;
			}

			$row[ $column ]        = $this->format_object_value_for_db( $changes[ $details['name'] ], $details['type'] );
			$row_format[ $column ] = $this->database_util->get_wpdb_format_for_type( $details['type'] );
		}

		if ( ! $row ) {
			return false;
		}

		return [
			'data'   => $row,
			'format' => $row_format,
		];
	}

	private function format_object_value_for_db( $value, string $type ) {
		switch ( $type ) {
			case 'decimal':
				$value = wc_format_decimal( $value, false, true );
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'bool':
				$value = wc_string_to_bool( $value );
				break;
			case 'string':
				$value = strval( $value );
				break;
			case 'date':
				// Date properties are converted to the WP timezone (see WC_Data::set_date_prop() method), however
				// for our own tables we persist dates in GMT.
				if ( $value instanceof \DateTimeInterface ) {
					$value = $value->format( 'Y-m-d H:i:s' );
				} else {
					$value = $value ? ( new DateTime( $value ) )->setTimezone( new DateTimeZone( '+00:00' ) )->format( 'Y-m-d H:i:s' ) : null;
				}
				break;
			case 'date_epoch':
				$value = $value ? ( new DateTime( "@{$value}" ) )->format( 'Y-m-d H:i:s' ) : null;
				break;
			default:
				throw new \Exception( 'Invalid type received: ' . $type );
		}

		return $value;
	}
}
