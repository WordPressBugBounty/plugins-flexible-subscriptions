<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal;

use WC_Order;
use WPDesk\FlexibleSubscriptions\Subscription\Subscription;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

class CompositePaymentRequest implements RequestPaymentStrategy {

	/** @var RequestPaymentStrategy[] */
	private array $renewal_strategies;

	private ?RequestPaymentStrategy $supported_strategy = null;

	private LoggerInterface $logger;

	/** @param RequestPaymentStrategy[] $renewal_strategies */
	public function __construct( array $renewal_strategies, LoggerInterface $logger ) {
		$this->renewal_strategies = $renewal_strategies;
		$this->logger             = $logger;
	}

	public function supports( Subscription $subscription, WC_Order $payment_request ): bool {
		foreach ( $this->renewal_strategies as $renewal_strategy ) {
			if ( $renewal_strategy->supports( $subscription, $payment_request ) ) {
				$this->supported_strategy = $renewal_strategy;
				$this->logger->debug(
					'Chosen renewal strategy for subscription "{sid}".',
					[
						'sid'              => $subscription->get_id(),
						'renewal_strategy' => $renewal_strategy,
					]
				);
				return true;
			}
		}
		$this->logger->warning( 'No supported renewal strategy found for subscription "{sid}".', [ 'sid' => $subscription->get_id() ] );
		return false;
	}

	public function request_payment( WC_Order $payment_request ): void {
		assert( $this->supported_strategy instanceof RequestPaymentStrategy );
		$this->logger->debug(
			'Handling payment request with "{strategy}" strategy',
			[
				'payment_request' => $payment_request,
				'strategy'        => $this->supported_strategy,
			]
		);
		$this->supported_strategy->request_payment( $payment_request );
	}
}
