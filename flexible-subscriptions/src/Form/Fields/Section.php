<?php

namespace WPDesk\FlexibleSubscriptions\Form\Fields;

use Traversable;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field;

/**
 * @implements \IteratorAggregate<Field>
 */
class Section extends \WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Field\NoValueField implements \IteratorAggregate {

	/** @var Field[] */
	private array $fields;

	public function __construct( array $fields = [] ) {
		$this->fields = $fields;
	}

	public function get_template_name(): string {
		return 'section';
	}

	public function should_override_form_template(): bool {
		return \true;
	}

	public function get_fields(): array {
		return $this->fields;
	}

	public function getIterator(): Traversable {
		return new \ArrayIterator( $this->get_fields() );
	}
}
