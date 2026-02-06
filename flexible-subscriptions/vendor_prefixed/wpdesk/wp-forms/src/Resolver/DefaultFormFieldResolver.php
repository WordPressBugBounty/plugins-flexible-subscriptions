<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Forms\Resolver;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\DirResolver;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\Resolver;
/**
 * Use with View to resolver form fields to default templates.
 *
 * @package WPDesk\Forms\Resolver
 */
class DefaultFormFieldResolver implements Resolver
{
    /** @var Resolver */
    private $dir_resolver;
    public function __construct()
    {
        $this->dir_resolver = new DirResolver(__DIR__ . '/../../templates');
    }
    public function resolve($name, ?Renderer $renderer = null): string
    {
        return $this->dir_resolver->resolve($name, $renderer);
    }
}
