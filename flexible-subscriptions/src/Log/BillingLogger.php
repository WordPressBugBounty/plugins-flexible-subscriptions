<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\Log;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\AbstractLogger;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;

final class BillingLogger extends AbstractLogger {

	private LoggerInterface $logger;

	private string $plugin_version;

	public function __construct( LoggerInterface $logger, string $plugin_version ) {
		$this->logger         = $logger;
		$this->plugin_version = $plugin_version;
	}

	public function log( $level, $message, array $context = [] ): void {
		$this->logger->log(
			$level,
			$message,
			$context + [
				'event'          => $context['event'] ?? (string) $message,
				'plugin_version' => $this->plugin_version,
			]
		);
	}
}
