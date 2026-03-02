<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

use WPDesk\FlexibleSubscriptions\Subscription\Events\BillingFrequencyUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\CancelledDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\EndDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\NextPaymentDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\StartDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\StatusUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionActivated;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionCancelled;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionExpired;
use WPDesk\FlexibleSubscriptions\Subscription\Events\SubscriptionPaused;
use WPDesk\FlexibleSubscriptions\Subscription\Events\TrialEndDateUpdated;
use WPDesk\FlexibleSubscriptions\Subscription\Utils\Status as UtilStatus;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Interval\WPInterval;

/**
 * As the rule of the thumb, most of the getters and setters are only for
 * internal use, to fill the props with the class. Interaction with
 * a Subscription object should be done by dedicated object values
 * (e.g., payment_interval) or through mutators (e.g., update_status,
 * recalculate_periods).
 *
 * @property bool|array{to: string, from?: string, note: string, manual: bool} $status_transition
 */
class Subscription extends \WC_Order {

	public const OBJECT_TYPE = 'fsb_subscription';
	/** @var array<string, array<string, true>> */
	private const ALLOWED_STATUS_TRANSITIONS = [
		'pending'        => [
			'active'         => true,
			'on-hold'        => true,
			'pending-cancel' => true,
			'cancelled'      => true,
			'trash'          => true,
		],
		'on-hold'        => [
			'active'         => true,
			'pending-cancel' => true,
			'expired'        => true,
			'trash'          => true,
		],
		'active'         => [
			'on-hold'        => true,
			'pending-cancel' => true,
			'expired'        => true,
			'trash'          => true,
		],
		'pending-cancel' => [
			'active'    => true,
			'cancelled' => true,
			'trash'     => true,
		],
		'cancelled'      => [
			'trash' => true,
		],
		'expired'        => [
			'trash' => true,
		],
		'trash'          => [],
	];

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'fsb_subscription';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = self::OBJECT_TYPE;

	/**
	 * Extra data for this object. Name value pairs (name + default value). Used to add additional information to parent.
	 *
	 * @var array<string, mixed>
	 */
	protected $extra_data = [
		'billing_frequency'         => 'PT0S',
		'requires_manual_renewal'   => true,
		'cancelled_email_sent'      => false,
		'trial_interval'            => null,
		'expiration_interval'       => null,

		// Extra data that requires manual getting/setting because we don't define getters/setters for it.
		'trial_end_date_utc'        => null,
		'cancelled_date_utc'        => null,
		'end_date_utc'              => null,
		'payment_retry'             => null,
		'start_date_utc'            => null,

		'switch_data'               => [],

		'current_period_start_utc'  => null,
		'current_period_end_utc'    => null,

		'recent_payment_request_id' => null,
	];

	private array $domain_events = [];

	private function record_event( object $event ): void {
		$this->domain_events[] = $event;
	}

	public function release_events(): array {
		$events              = $this->domain_events;
		$this->domain_events = [];
		return $events;
	}

	public function record_payment_request( \WC_Order $payment_request ): void {
		$this->set_recent_payment_request_id( $payment_request->get_id() );
	}

	public function update_billing_frequency( WPInterval $frequency ): bool {
		$old = $this->get_billing_frequency();
		if ( $old->equalTo( $frequency ) ) {
			return false;
		}

		if ( $frequency->isEmpty() ) {
			throw new \DomainException( 'Cannot set billing frequency to empty interval.' );
		}

		$this->set_billing_frequency( $frequency );

		$this->record_event( new BillingFrequencyUpdated( $this, $frequency, $old ) );
		return true;
	}

	public function activate( \DatePeriod $period, ?TransitionContext $context = null ): void {
		if ( ! $this->can_be_updated_to( 'active' ) ) {
			return;
		}

		$context ??= TransitionContext::manual( 'activate' );

		$this->set_current_period_start( $period->getStartDate() );
		$this->set_current_period_end( $period->getEndDate() );

		$this->set_cancelled_date( null );
		if ( ! $this->is_expired( $period->getStartDate() ) ) {
			$this->set_end_date( null );
		}

		$this->set_status( 'active', $context->note, $context->manual );
		$this->record_event( new SubscriptionActivated( $this ) );
	}

	public function pause( ?TransitionContext $context = null ): void {
		if ( ! $this->can_be_updated_to( 'on-hold' ) ) {
			return;
		}

		$context ??= TransitionContext::manual( 'pause' );
		$this->set_status( 'on-hold', $context->note, $context->manual );
		$this->record_event( new SubscriptionPaused( $this ) );
	}

	public function cancel( \DateTimeImmutable $now, ?TransitionContext $context = null ): void {
		if ( ! $this->can_be_updated_to( 'cancelled' ) && ! $this->can_be_updated_to( 'pending-cancel' ) ) {
			return;
		}

		$context  ??= TransitionContext::manual( 'cancel' );
		$period_end = $this->get_current_period_end();

		$this->set_cancelled_date( $now );

		if ( $this->has_status( 'pending-cancel' ) || ( $this->has_status( 'pending' ) && $this->is_during_first_cycle() ) || $period_end === null ) {
			$this->set_end_date( $now );
			$this->set_current_period_end( null );
			$this->set_status( 'cancelled', $context->note, $context->manual );
		} else {
			$this->set_end_date( $period_end );
			$this->set_status( 'pending-cancel', $context->note, $context->manual );
		}

		$this->record_event( new SubscriptionCancelled( $this ) );
	}

	public function expire( \DateTimeImmutable $now, ?TransitionContext $context = null ): void {
		if ( ! $this->can_be_updated_to( 'expired' ) ) {
			return;
		}

		$context ??= TransitionContext::manual( 'expire' );

		if ( ! $this->get_end_date() instanceof \DateTimeInterface ) {
			$this->set_end_date( $now );
		}

		$this->set_status( 'expired', $context->note, $context->manual );
		$this->record_event( new SubscriptionExpired( $this ) );
	}


	public function update_current_period_end( ?\DateTimeImmutable $date ): bool {
		$old_date = $this->get_current_period_end();
		if ( $old_date == $date ) {
			return false;
		}

		$this->set_current_period_end( $date );

		$this->record_event( new NextPaymentDateUpdated( $this, $date, $old_date ) );

		return true;
	}

	public function update_end_date( ?\DateTimeImmutable $date ): bool {
		$old_date = $this->get_end_date();
		if ( $old_date == $date ) {
			return false;
		}

		$this->set_end_date( $date );

		$this->record_event( new EndDateUpdated( $this, $date, $old_date ) );

		return true;
	}

	public function update_cancelled_date( ?\DateTimeImmutable $date ): bool {
		$old_date = $this->get_cancelled_date();
		if ( $old_date == $date ) {
			return false;
		}

		$this->set_cancelled_date( $date );

		$this->record_event( new CancelledDateUpdated( $this, $date, $old_date ) );

		return true;
	}

	public function update_start_date( ?\DateTimeImmutable $date ): bool {
		$old_date = $this->get_start_date();
		if ( $old_date == $date ) {
			return false;
		}

		$this->set_start_date( $date );

		$this->record_event( new StartDateUpdated( $this, $date, $old_date ) );

		return true;
	}

	public function update_trial_end_date( ?\DateTimeImmutable $date ): bool {
		$old_date = $this->get_trial_end_date();
		if ( $old_date == $date ) {
			return false;
		}

		$this->set_trial_end_date( $date );

		$this->record_event( new TrialEndDateUpdated( $this, $date, $old_date ) );

		return true;
	}

	public function initialize_first_period(
		\DateTimeImmutable $start_date,
		WPInterval $billing_frequency,
		?\DateTimeImmutable $trial_end_date = null,
		?WPInterval $expiration = null
	): void {
		$this->set_current_period_start( $start_date );
		$this->set_current_period_end( $trial_end_date ?? $start_date->add( $billing_frequency ) );

		if ( ! $expiration instanceof WPInterval ) {
			return;
		}

		$end_date = $start_date->add( $expiration );
		if ( $trial_end_date instanceof \DateTimeInterface ) {
			$end_date = $end_date->add( $trial_end_date->diff( $start_date, true ) );
		}

		$this->set_end_date( $end_date );
	}

	#[\Deprecated( 'Use initialize_first_period() for first cycle setup and advance_billing_period() for renewals.' )]
	public function recalculate_periods(): void {
		if ( ! $this->is_during_first_cycle() ) {
			return;
		}

		$start = $this->to_immutable_date( $this->get_start_date() );
		if ( ! $start instanceof \DateTimeImmutable ) {
			/* We cannot recalculate periods without a start date. Notify in {@see SubscriptionErrorsNotice} */
			return;
		}

		$trial_end = $this->to_immutable_date( $this->get_trial_end_date() );
		if ( ! $trial_end instanceof \DateTimeImmutable && $this->get_trial_interval() instanceof WPInterval ) {
			// Backward compatibility with trial interval.
			$trial_end = $start->add( $this->get_trial_interval() );
		}

		$this->initialize_first_period( $start, $this->get_billing_frequency(), $trial_end, $this->get_expiration() );
	}

	private function to_immutable_date( ?\DateTimeInterface $date ): ?\DateTimeImmutable {
		if ( ! $date instanceof \DateTimeInterface ) {
			return null;
		}

		if ( $date instanceof \DateTimeImmutable ) {
			return $date;
		}

		if ( $date instanceof \DateTime ) {
			return \DateTimeImmutable::createFromMutable( $date );
		}

		return new \DateTimeImmutable( $date->format( 'Y-m-d H:i:s' ), $date->getTimezone() );
	}

	/**
	 * Advance the current billing period by one billing interval.
	 *
	 * This method intentionally does not modify {@see self::get_end_date()} or trial dates.
	 * It is meant to be used after a successful renewal payment, even if the subscription
	 * status did not transition through an "unpaid" state.
	 */
	public function advance_billing_period( \DatePeriod $period ): bool {
		if ( $this->get_current_period_end() == $period->getEndDate() ) {
			return false;
		}

		$this->set_current_period_start( $period->getStartDate() );
		$this->set_current_period_end( $period->getEndDate() );
		$this->set_billing_cycle( $this->get_billing_cycle() + 1 );

		$this->set_cancelled_date( null );
		if ( ! $this->has_status( 'active' ) ) {
			$this->set_status( 'active' );
		}
		$this->record_event( new SubscriptionActivated( $this ) );

		return true;
	}

	/**
	 * Check if the subscription can be changed to a new status.
	 */
	public function can_be_updated_to( string $new_status ): bool {
		return $this->can_transition_from_to( $this->get_status(), $new_status );
	}

	public function can_transition_from_to( string $from_status, string $new_status ): bool {
		$from = $this->normalize_status( $from_status );
		$to   = $this->resolve_transition_target_status_from( $new_status, $from );

		return isset( self::ALLOWED_STATUS_TRANSITIONS[ $from ][ $to ] );
	}

	public function resolve_transition_target_status_from( string $new_status, string $from_status ): string {
		$to   = $this->normalize_status( $new_status );
		$from = $this->normalize_status( $from_status );

		if ( $to === 'pending-cancel' && $from === 'pending' && $this->is_during_first_cycle() ) {
			return 'cancelled';
		}

		return $to;
	}

	/**
	 * There's a slight difference between `update_status` and
	 * `set_status`. Updating status should be treated as mutator for
	 * subscription class and be used to publicly change the status;
	 * therefore, it is a part of API.
	 *
	 * On the other hand, `set_status` is an internal function to set
	 * status property, but it doesn't require triggering a status
	 * change event (which is usually guaranteed by `update_status`).
	 *
	 * @see self::set_status()
	 */
	#[\Override]
	public function update_status( $new_status, $note = '', $manual = false ): bool {
		if ( ! $this->get_id() ) {
			return false;
		}

		if ( ! $this->can_be_updated_to( $new_status ) ) {
			return false;
		}

		$old_status = $this->get_status();
		$this->set_status( $new_status, $note, $manual );

		if ( $old_status !== $new_status ) {
			$this->record_event( new StatusUpdated( $this, $new_status, $old_status ) );
		}

		$this->save();

		return true;
	}

	#[\Override]
	protected function status_transition(): void {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( is_array( $status_transition ) ) {
			$new_status      = $status_transition['to'] ?? '';
			$previous_status = $status_transition['from'] ?? null;

			do_action( 'fsub/subscription/status/updated', $this, $new_status, $previous_status );

			if ( $new_status !== '' ) {
				do_action( "fsub/subscription/status/updated/to/{$new_status}", $this, $previous_status );
			}

			if ( ! empty( $previous_status ) ) {
				do_action( "fsub/subscription/status/updated/from/{$previous_status}", $this, $new_status );
				if ( $new_status !== '' ) {
					do_action( "fsub/subscription/status/updated/from/{$previous_status}/to/{$new_status}", $this );
				}
			}

			if ( ! empty( $status_transition['from'] ) ) {
				/* translators: 1: old order status 2: new order status */
				$transition_note = sprintf( __( 'Order status changed from %1$s to %2$s.', 'flexible-subscriptions' ), wc_get_order_status_name( $status_transition['from'] ), wc_get_order_status_name( $status_transition['to'] ) );
				$this->add_order_note( trim( "{$status_transition['note']} {$transition_note}" ), 0, $status_transition['manual'] );
			} else {
				/* translators: %s: new order status */
				$transition_note = sprintf( __( 'Order status set to %s.', 'flexible-subscriptions' ), wc_get_order_status_name( $status_transition['to'] ) );

				// Note the transition occurred.
				$this->add_order_note( trim( "{$status_transition['note']} {$transition_note}" ), 0, $status_transition['manual'] );

			}
		}
	}

	#[\Override]
	public function get_base_data() {
		$data = parent::get_base_data();

		// HPOS uses ArrayUtil with loose comparison; DateInterval objects trigger a fatal on comparison.
		foreach ( [ 'billing_frequency', 'trial_interval', 'expiration_interval' ] as $key ) {
			if ( isset( $data[ $key ] ) && $data[ $key ] instanceof \DateInterval ) {
				$data[ $key ] = (string) $data[ $key ];
			}
		}

		return $data;
	}

	#[\Override]
	public function get_type(): string {
		return 'fsb_subscription';
	}

	#[\Override]
	public function get_view_order_url(): string {
		return wc_get_endpoint_url( 'view-fsb-subscription', (string) $this->get_id(), wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * @return string[]
	 */
	#[\Override]
	public function get_valid_statuses(): array {
		return array_keys( UtilStatus::get_statuses() );
	}

	/**
	 * @param string|string[] $status
	 */
	#[\Override]
	public function has_status( $status ): bool {
		$check_with_prefix = parent::has_status( $status );

		if ( $check_with_prefix === false ) {
			$status = array_map(
				[ $this, 'normalize_status' ],
				(array) $status
			);
			return parent::has_status( $status );
		}

		return $check_with_prefix;
	}

	/**
	 * Micro override for the function which supports \DateTimeImmutable
	 * dates as we prefer immutable object in UTC timezone across our plugin.
	 *
	 * @param \DateTimeInterface|string|int $value
	 */
	#[\Override]
	final protected function set_date_prop( $prop, $value ): void {
		if ( ! in_array( $prop, [ 'start_date_utc', 'current_period_start_utc', 'current_period_end_utc', 'end_date_utc', 'trial_end_date_utc', 'cancelled_date_utc' ], true ) ) {
			// We don't want to mess with other date props.
			parent::set_date_prop( $prop, $value );
			return;
		}

		if ( $value instanceof \DateTimeInterface ) {
			// Make sure, WC_DateTime is respected, as used internally by WC
			if ( $value instanceof \WC_DateTime ) {
				$this->set_prop( $prop, $value );
				return;
			}

			if ( $value instanceof \DateTime ) {
				$value = \DateTimeImmutable::createFromMutable( $value );
			}
			$this->set_prop( $prop, $value );
		} elseif ( is_string( $value ) ) {
			if ( empty( $value ) || '0000-00-00 00:00:00' === $value ) {
				$this->set_prop( $prop, null );
				return;
			}

			$datetime = new \DateTimeImmutable( $value, new \DateTimeZone( 'UTC' ) );

			$this->set_prop( $prop, $datetime );
		} else {
			$this->set_prop( $prop, null );
		}
	}

	#[\Override]
	public function payment_complete( $transaction_id = '' ) {
		$last_order = $this->get_recent_payment_request_id() ? wc_get_order( $this->get_recent_payment_request_id() ) : null;

		if ( $last_order instanceof \WC_Order && $last_order->needs_payment() ) {
			$last_order->payment_complete( $transaction_id );
		}

		// Make sure subscriber has a default role
		$user = $this->get_user();
		if ( $user instanceof \WP_User ) {
			$user->add_role( 'subscriber' );
		}

		// Add an order note depending on initial payment
		$this->add_order_note( __( 'Payment status marked complete.', 'flexible-subscriptions' ) );

		if ( ! $this->is_active() ) {
			$start = clone $last_order->get_date_paid();
			if ( ! $start instanceof \DateTimeInterface ) {
				$start = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
			}
			// normally this should be outside, but we cannot control payment_complete called by external plugins
			$calculator = new BillingPeriodCalculator();
			$period     = $calculator->calculate_next_period( $start, $this->get_billing_frequency() );
			$this->activate( $period );
		} else {
			$this->save();
		}

		do_action( 'woocommerce_subscription_payment_complete', $this );
	}

	/**
	 * TODO: is this really necessary or this should be handled with some
	 * service?
	 *
	 * @deprecated 1.7.0 Not part of the public API. Implement a dedicated workflow or service instead.
	 */
	public function can_change_payment_method(): bool {
		return $this->has_payment_gateway() && false;
	}

	public function get_payment_method_to_display(): string {
		if ( $this->has_payment_gateway() ) {
			return $this->get_payment_gateway()->get_title();
		}

		if ( $this->is_manual() ) {
			return __( 'Manual Renewal', 'flexible-subscriptions' );
		}

		return $this->get_payment_method_title();
	}

	/** @internal */
	public function get_billing_frequency(): WPInterval {
		$freq = $this->get_prop( 'billing_frequency' );

		if ( $freq instanceof WPInterval ) {
			return $freq;
		}

		return new WPInterval( $freq );
	}

	#[\Deprecated( 'Use get_trial_end_date() instead. Relying on interval is obsolete, when start date cannot be changed.' )]
	public function get_trial_interval(): ?WPInterval {
		$interval = $this->get_prop( 'trial_interval' );

		if ( $interval instanceof WPInterval ) {
			return $interval;
		}

		if ( is_null( $interval ) ) {
			return null;
		}

		return new WPInterval( $interval );
	}

	/** @internal */
	public function get_expiration(): ?WPInterval {
		$interval = $this->get_prop( 'expiration_interval' );
		if ( $interval instanceof WPInterval ) {
			return $interval;
		}

		if ( is_null( $interval ) ) {
			return null;
		}

		return new WPInterval( $interval );
	}

	public function can_expire(): bool {
		return $this->get_expiration() instanceof WPInterval || $this->get_end_date() !== null;
	}

	public function is_expired( ?\DateTimeInterface $now = null ): bool {
		if ( ! $this->can_expire() ) {
			return false;
		}

		$now ??= new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		return $this->get_end_date() instanceof \DateTimeInterface && $this->get_end_date() <= $now;
	}

	/**
	 * @return \DatePeriod<\DateTimeInterface, \DateTimeInterface, int>
	 */
	public function get_current_period(): \DatePeriod {
		if (
			! $this->get_current_period_start() instanceof \DateTimeInterface ||
			! $this->get_current_period_end() instanceof \DateTimeInterface
		) {
			return new NullPeriod();
		}

		return new \DatePeriod(
			$this->get_current_period_start(),
			$this->get_billing_frequency(),
			$this->get_current_period_end()
		);
	}

	public function is_during_first_cycle(): bool {
		return is_null( $this->get_current_period_start() ) || is_null( $this->get_start_date() ) || $this->get_current_period_start()->format( 'Y-m-d\TH:i' ) === $this->get_start_date()->format( 'Y-m-d\TH:i' );
	}

	public function get_parent(): ?\WC_Order {
		$parent_id = $this->get_parent_id();

		if ( $parent_id > 0 ) {
			$order = wc_get_order( $parent_id );

			if ( $order instanceof \WC_Order ) {
				return $order;
			}
		}

		return null;
	}

	public function get_payment_gateway(): ?\WC_Payment_Gateway {
		$gateway = wc_get_payment_gateway_by_order( $this );
		if ( $gateway instanceof \WC_Payment_Gateway ) {
			return $gateway;
		}

		return null;
	}

	/**
	 * @phpstan-assert-if-true !null $this->get_payment_gateway()
	 */
	public function has_payment_gateway(): bool {
		return (bool) wc_get_payment_gateway_by_order( $this );
	}

	public function is_manual(): bool {
		return $this->get_requires_manual_renewal();
	}

	public function is_finalized(): bool {
		return in_array( $this->get_status(), [ 'cancelled', 'trash', 'expired', 'switched', 'pending-cancel' ], true );
	}

	public function is_cancelled(): bool {
		return (bool) $this->get_prop( 'cancelled_date_utc', 'edit' );
	}

	public function is_pending_cancel(): bool {
		return $this->has_status( 'pending-cancel' );
	}

	// TODO: sub pending cancellation is still active
	public function is_active(): bool {
		return $this->has_status( 'active' );
	}


	public function get_billing_cycle(): int {
		$cycle = (int) $this->get_meta( '_fsb_billing_cycle', true );
		return $cycle > 0 ? $cycle : 1;
	}

	public function has_trial(): bool {
		return $this->get_trial_interval() !== null || $this->get_prop( 'trial_end_date_utc' );
	}

	/** @internal */
	public function get_recent_payment_request_id(): ?int {
		return (int) $this->get_prop( 'recent_payment_request_id' ) ?: null;
	}

	public function get_cancelled_date(): ?\DateTimeInterface {
		return $this->get_prop( 'cancelled_date_utc', 'edit' );
	}

	public function get_requires_manual_renewal(): bool {
		return filter_var( $this->get_prop( 'requires_manual_renewal' ), \FILTER_VALIDATE_BOOL );
	}

	public function get_start_date(): ?\DateTimeInterface {
		return $this->get_prop( 'start_date_utc', 'edit' );
	}

	public function get_trial_end_date(): ?\DateTimeInterface {
		if ( $this->has_trial() === false ) {
			return null;
		}

		$end_date = $this->get_prop( 'trial_end_date_utc' );

		if ( $end_date instanceof \DateTimeInterface ) {
			return $end_date;
		}

		if ( $this->get_start_date() instanceof \DateTimeInterface ) {
			return $this->get_start_date()->add( $this->get_trial_interval() );
		}

		return null;
	}

	public function get_end_date(): ?\DateTimeInterface {
		return $this->get_prop( 'end_date_utc', 'edit' );
	}

	#[\Deprecated( 'Use get_current_period_end() instead.' )]
	public function get_next_payment_date(): ?\DateTimeInterface {
		return $this->get_current_period_end();
	}

	public function get_current_period_start(): ?\DateTimeInterface {
		return $this->get_prop( 'current_period_start_utc' );
	}

	public function get_current_period_end(): ?\DateTimeInterface {
		return $this->get_prop( 'current_period_end_utc' );
	}

	/** @internal */
	public function set_manual( bool $manual ): void {
		$this->set_requires_manual_renewal( $manual );
	}

	/** @internal */
	public function set_start_date( $start_date ): void {
		$this->set_date_prop( 'start_date_utc', $start_date );
	}

	/**
	 * @param WPInterval|string|null $frequency
	 * @internal
	 */
	public function set_billing_frequency( $frequency ): void {
		if ( is_string( $frequency ) ) {
			try {
				$frequency = new WPInterval( $frequency );
			} catch ( \Exception $e ) {
				$frequency = null;
			}
		}

		if ( $frequency instanceof WPInterval ) {
			$this->set_prop( 'billing_frequency', $frequency );
		} else {
			$this->set_prop( 'billing_frequency', null );
		}
	}

	/**
	 * @param WPInterval|string|null $duration
	 * @internal
	 */
	public function set_trial_interval( $duration ): void {
		if ( is_string( $duration ) ) {
			try {
				$duration = new WPInterval( $duration );
			} catch ( \Exception $e ) {
				$duration = null;
			}
		}

		if ( $duration instanceof WPInterval ) {
			$this->set_prop( 'trial_interval', $duration );
		} else {
			$this->set_prop( 'trial_interval', null );
		}
	}

	/** @internal */
	public function set_expiration( $duration ): void {
		if ( is_string( $duration ) ) {
			try {
				$duration = new WPInterval( $duration );
			} catch ( \Exception $e ) {
				$duration = null;
			}
		}

		if ( $duration instanceof WPInterval ) {
			$this->set_prop( 'expiration_interval', $duration );
		} else {
			$this->set_prop( 'expiration_interval', null );
		}
	}

	/** @internal */
	public function set_billing_cycle( int $cycle ): void {
		$cycle = max( 1, $cycle );
		$this->update_meta_data( '_fsb_billing_cycle', (string) $cycle );
	}

	/**
	 * @param numeric $request_id
	 * @internal
	 */
	public function set_recent_payment_request_id( $request_id ): void {
		$this->set_prop( 'recent_payment_request_id', $request_id );
	}

	/** @internal */
	public function set_cancelled_date( $cancelled_date ): void {
		$this->set_date_prop( 'cancelled_date_utc', $cancelled_date );
	}

	/** @internal */
	public function set_requires_manual_renewal( $requires_manaul_renewal ): void {
		$this->set_prop( 'requires_manual_renewal', filter_var( $requires_manaul_renewal, \FILTER_VALIDATE_BOOL ) );
	}

	/** @internal */
	public function set_trial_end_date( $trail_end ): void {
		$this->set_date_prop( 'trial_end_date_utc', $trail_end );
	}

	/** @internal */
	#[\Deprecated( 'Use set_current_period_end() instead.' )]
	public function set_next_payment_date( $next_payment_date ): void {
		$this->set_current_period_end( $next_payment_date );
	}

	/** @internal */
	public function set_end_date( $end_date ): void {
		$this->set_date_prop( 'end_date_utc', $end_date );
	}

	/** @internal */
	public function set_current_period_start( $current_period_start ): void {
		$this->set_date_prop( 'current_period_start_utc', $current_period_start );
	}

	/** @internal */
	public function set_current_period_end( $current_period_end ): void {
		$this->set_date_prop( 'current_period_end_utc', $current_period_end );
	}

	private function normalize_status( string $status ): string {
		if ( str_starts_with( $status, 'wc-' ) ) {
			return substr( $status, 3 );
		}

		return $status;
	}
}
