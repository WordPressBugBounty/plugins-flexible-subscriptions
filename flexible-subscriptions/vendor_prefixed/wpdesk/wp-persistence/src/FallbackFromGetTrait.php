<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Persistence;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\NotFoundExceptionInterface;
trait FallbackFromGetTrait
{
    public function get_fallback(string $id, $fallback = null)
    {
        try {
            return $this->get($id);
        } catch (NotFoundExceptionInterface $e) {
            return $fallback;
        }
    }
}
