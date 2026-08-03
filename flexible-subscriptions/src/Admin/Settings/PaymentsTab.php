<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\Settings;

use WPDesk\FlexibleSubscriptions\Form\Fields\Section;
use WPDesk\FlexibleSubscriptions\Form\Fields\Tab;
use WPDesk\FlexibleSubscriptions\Settings\PaymentOptions;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\CheckboxField;

class PaymentsTab extends Tab {

	public function __construct() {
		parent::__construct( $this->fields() );
	}

	private function fields(): array {
		return [
			( new Section(
				[
					( new CheckboxField() )
						->set_name( PaymentOptions::REQUIRE_PAYMENT_ON_ZERO_TOTAL )
						->set_default_value( CheckboxField::VALUE_FALSE )
						->set_label( __( 'Zero initial payment', 'flexible-subscriptions' ) )
						->set_sublabel( __( 'Require a payment method when the initial subscription order total is zero', 'flexible-subscriptions' ) )
						->set_description( __( 'Allows a supported payment gateway to save the payment method for automatic renewals when a future subscription payment is due.', 'flexible-subscriptions' ) ),
					( new CheckboxField() )
						->set_name( PaymentOptions::MANUAL_RENEWAL_ENABLED )
						->set_default_value( CheckboxField::VALUE_FALSE )
						->set_label( __( 'Manual payments', 'flexible-subscriptions' ) )
						->set_sublabel( __( 'Allow payment methods that require manual subscription renewals', 'flexible-subscriptions' ) )
						->set_description( __( 'When disabled, only payment gateways supporting automatic subscription renewals are available during checkout.', 'flexible-subscriptions' ) ),
				]
			) )
				->set_label( __( 'Payment options', 'flexible-subscriptions' ) )
				->set_description( __( 'Configure how payment methods are collected and which gateways can be used for subscriptions.', 'flexible-subscriptions' ) ),
		];
	}
}
