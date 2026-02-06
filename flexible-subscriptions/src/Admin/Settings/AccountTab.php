<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\Settings;

use WPDesk\FlexibleSubscriptions\Form\Fields\AccountEndpointFields;
use WPDesk\FlexibleSubscriptions\Form\Fields\Tab;

class AccountTab extends Tab {

	public function __construct() {
		parent::__construct( $this->fields() );
	}

	private function fields(): array {
		return [
			( new AccountEndpointFields() )
				->set_name( 'account_endpoints' )
				->set_label( __( 'Account endpoints', 'flexible-subscriptions' ) )
				->set_description(
					__(
						'Endpoints are appended to your page URLs to handle specific actions on the accounts pages. They should be unique and can be left blank to disable the endpoint.
',
						'flexible-subscriptions'
					)
				),
		];
	}
}
