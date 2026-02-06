<?php

declare(strict_types=1);

namespace WPDesk\FlexibleSubscriptions\HookProvider;

/**
 * Allow to prevent a whole hook attachment loop.
 */
interface StoppableProvider {

	public function should_stop(): bool;
}
