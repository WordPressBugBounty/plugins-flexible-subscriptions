<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription;

final class LifecycleTransitionRequest {

	public Subscription $subscription;

	public string $to;

	public TransitionContext $context;

	public bool $handled = false;

	public function __construct( Subscription $subscription, string $to, ?TransitionContext $context = null ) {
		$this->subscription = $subscription;
		$this->to           = $to;
		$this->context      = $context ?? TransitionContext::system();
	}
}
