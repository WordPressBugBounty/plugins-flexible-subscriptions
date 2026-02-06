<?php
declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Admin\Settings;

use WPDesk\FlexibleSubscriptions\Form\Fields\Section;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\InputTextField;
use WPDesk\FlexibleSubscriptions\Form\Fields\Tab;

class ProductsTab extends Tab {

	public function __construct() {
		parent::__construct( $this->fields() );
	}

	public function id(): string {
		return 'products';
	}

	public function name(): string {
		return esc_html__( 'Products', 'flexible-subscriptions' );
	}

	private function fields(): array {
		return [
			( new Section(
				[
					( new InputTextField() )
						->set_name( 'fsb_add_to_cart_text' )
						->set_default_value( __( 'Sign up now', 'flexible-subscriptions' ) )
						->set_label( __( 'Add to cart button text', 'flexible-subscriptions' ) ),
					( new InputTextField() )
						->set_name( 'place_order_label' )

						->set_default_value( __( 'Sign up now', 'flexible-subscriptions' ) )

						->set_label( __( 'Place order button text', 'flexible-subscriptions' ) ),
				]
			) )
				->set_label( __( 'Button text', 'flexible-subscriptions' ) )
				->set_description( __( 'At your store, buttons are labeled with regular products in mind (i.e. "Add to cart", "Place order"). By default, a subscription changes this to more appropiate labels, like "Sign up now" or "Subscribe now". You can customise the button text for subscriptions here.', 'flexible-subscriptions' ) ),
		];
	}
}
