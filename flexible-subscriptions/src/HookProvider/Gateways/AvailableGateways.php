<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider\Gateways;

use WPDesk\FlexibleSubscriptions\Cart\SubscriptionCandidatesList;
use WPDesk\FlexibleSubscriptions\Settings\PaymentOptions;
use WPDesk\FlexibleSubscriptions\Utils\HookProvider;

class AvailableGateways implements HookProvider {

	private SubscriptionCandidatesList $candidates;

	private PaymentOptions $payment_options;

	public function __construct( SubscriptionCandidatesList $candidates, PaymentOptions $payment_options ) {
		$this->candidates      = $candidates;
		$this->payment_options = $payment_options;
	}

	public function hooks(): void {
		add_filter( 'woocommerce_available_payment_gateways', $this, 100 );
		add_filter( 'woocommerce_no_available_payment_methods_message', [ $this, 'no_available_payment_methods_message' ] );
	}

	/**
	 * @param \WC_Payment_Gateway[] $gateways
	 *
	 * @return \WC_Payment_Gateway[]
	 */
	public function __invoke( $gateways ) {
		// Explicitly disable WooPayments for any cart containing a subscription.
		if ( $this->candidates->count() > 0 && isset( $gateways[ WooPaymentsIncompatibility::WOOCOMMERCE_PAYMENTS_ID ] ) ) {
			unset( $gateways[ WooPaymentsIncompatibility::WOOCOMMERCE_PAYMENTS_ID ] );
		}

		if (
			apply_filters(
				'fsub/payment/manual_renewal/enabled',
				$this->payment_options->manual_renewal_enabled()
			)
		) {
			return $gateways;
		}

		if ( count( $this->candidates ) > 1 ) {
			$gateways = array_filter(
				$gateways,
				fn ( $g ) => $g->supports( 'multiple_subscriptions' )
			);
		} elseif ( count( $this->candidates ) === 1 ) {
			$gateways = array_filter(
				$gateways,
				fn ( $g ) => $g->supports( 'subscriptions' )
			);
		}

		return $gateways;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	public function no_available_payment_methods_message( $message ): string {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			// translators: 1-2: opening/closing tags - link to documentation.
			$no_gateways_message = sprintf( __( 'Sorry, it seems there are no available payment methods which support subscriptions. Please see %1$sEnabling Payment Gateways for Subscriptions%2$s if you require assistance.', 'flexible-subscriptions' ), '<a href="https://docs.woocommerce.com/document/subscriptions/enabling-payment-gateways-for-subscriptions/">', '</a>' );
		} else {
			$no_gateways_message = __( 'Sorry, it seems there are no available payment methods which support subscriptions. Please contact us if you require assistance or wish to make alternate arrangements.', 'flexible-subscriptions' );
		}

		return $no_gateways_message;
	}
}
