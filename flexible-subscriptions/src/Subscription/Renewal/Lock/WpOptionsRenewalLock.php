<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Subscription\Renewal\Lock;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

final class WpOptionsRenewalLock implements RenewalLock {

	private LoggerInterface $logger;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function acquire( int $subscription_id, int $ttl_seconds = 600 ): ?string {
		global $wpdb;

		$lock_key   = $this->lock_key( $subscription_id );
		$owner      = bin2hex( random_bytes( 16 ) );
		$expires_at = time() + max( 1, $ttl_seconds );

		$value = wp_json_encode(
			[
				'owner'      => $owner,
				'expires_at' => $expires_at,
				'created_at' => time(),
			]
		);

		$inserted = $this->insert_lock( $lock_key, $value );
		if ( $inserted === 1 ) {
			return $owner;
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$lock_key
			)
		);

		if ( ! is_string( $existing ) || $existing === '' ) {
			return null;
		}

		$data       = json_decode( $existing, true );
		$locked_til = (int) ( is_array( $data ) ? ( $data['expires_at'] ?? 0 ) : 0 );

		if ( $locked_til > 0 && $locked_til < time() ) {
			// Attempt to remove stale lock and retry once.
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s",
					$lock_key
				)
			);

			if ( $deleted > 0 ) {
				$this->logger->warning(
					'Removed stale renewal lock "{lock_key}" for subscription "{sid}".',
					[
						'lock_key' => $lock_key,
						'sid'      => $subscription_id,
					]
				);
			}

			$inserted = $this->insert_lock( $lock_key, $value );
			if ( $inserted === 1 ) {
				return $owner;
			}
		}

		return null;
	}

	public function release( int $subscription_id, string $owner ): void {
		global $wpdb;

		$lock_key = $this->lock_key( $subscription_id );

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$lock_key
			)
		);

		if ( ! is_string( $existing ) || $existing === '' ) {
			return;
		}

		$data       = json_decode( $existing, true );
		$lock_owner = is_array( $data ) ? (string) ( $data['owner'] ?? '' ) : '';

		if ( $lock_owner !== $owner ) {
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s",
				$lock_key
			)
		);
	}

	private function lock_key( int $subscription_id ): string {
		return 'fsb_lock_renewal_' . $subscription_id;
	}

	private function insert_lock( string $lock_key, string $value ): int {
		global $wpdb;

		return (int) $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
				$lock_key,
				$value
			)
		);
	}
}
