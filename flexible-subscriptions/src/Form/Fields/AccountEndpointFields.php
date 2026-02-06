<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

use WPDesk\FlexibleSubscriptions\Form\Fields\Section;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\InputTextField;

class AccountEndpointFields extends Section {

	#[\Override]
	public function get_fields(): array {
		return [
			( new InputTextField() )
				->set_default_value( 'subscriptions' )
				->set_name( 'woocommerce_myaccount_fsb_subscriptions_endpoint' )
				->set_label( __( 'Subscriptions', 'flexible-subscriptions' ) )
				->set_description( __( 'Endpoint for the My Account &rarr; Subscriptions page', 'flexible-subscriptions' ) ),
			( new InputTextField() )
				->set_default_value( 'view-subscription' )
				->set_name( 'woocommerce_myaccount_fsb_view_subscription_endpoint' )
				->set_label( __( 'View subscription', 'flexible-subscriptions' ) )
				->set_description( __( 'Endpoint for the My Account &rarr; View Subscription page', 'flexible-subscriptions' ) ),
		];
	}
}
