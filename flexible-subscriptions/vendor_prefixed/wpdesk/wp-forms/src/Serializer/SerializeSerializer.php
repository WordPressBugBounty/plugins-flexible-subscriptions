<?php

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Serializer;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Serializer;
class SerializeSerializer implements Serializer
{
    public function serialize($value): string
    {
        return serialize($value);
    }
    public function unserialize(string $value)
    {
        return unserialize($value);
    }
}
