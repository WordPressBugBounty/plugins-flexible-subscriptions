<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\DI;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
