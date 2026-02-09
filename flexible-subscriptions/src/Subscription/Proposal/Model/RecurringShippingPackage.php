<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Proposal\Model;

final class RecurringShippingPackage {

	private string $package_key;

	/** @var array<string, \WC_Shipping_Rate> */
	private array $rates;

	private string $selected_method;

	private string $package_details;

	/** @var array */
	private array $package;

	private bool $match_initial_rates;

	/**
	 * @param array<string, \WC_Shipping_Rate> $rates
	 * @param array $package
	 */
	public function __construct(
		string $package_key,
		array $rates,
		string $selected_method,
		string $package_details,
		array $package,
		bool $match_initial_rates
	) {
		$this->package_key         = $package_key;
		$this->rates               = $rates;
		$this->selected_method     = $selected_method;
		$this->package_details     = $package_details;
		$this->package             = $package;
		$this->match_initial_rates = $match_initial_rates;
	}

	public function get_package_key(): string {
		return $this->package_key;
	}

	/** @return array<string, \WC_Shipping_Rate> */
	public function get_rates(): array {
		return $this->rates;
	}

	public function get_selected_method(): string {
		return $this->selected_method;
	}

	public function get_package_details(): string {
		return $this->package_details;
	}

	public function get_package(): array {
		return $this->package;
	}

	public function match_initial_rates(): bool {
		return $this->match_initial_rates;
	}

	public static function index_from_key( string $key ): ?int {
		$position = strrpos( $key, '_' );
		if ( $position === false ) {
			return null;
		}

		$index = substr( $key, $position + 1 );
		if ( $index === '' || ! ctype_digit( $index ) ) {
			return null;
		}

		return (int) $index;
	}
}
