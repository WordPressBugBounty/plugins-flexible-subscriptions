<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util;

class PhpFileLoader
{
    /**
     * @param string|Path $resource
     *
     * @return mixed
     */
    public function load($resource)
    {
        return (static function () use ($resource) {
            if (!is_readable((string) $resource)) {
                throw new \RuntimeException("Could not load {$resource}");
            }
            $data = include (string) $resource;
            if ($data === \false) {
                throw new \RuntimeException("Could not load {$resource}");
            }
            return $data;
        })();
    }
}
