<?php

namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Library\Marketing\RatePlugin;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\Renderer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Renderer\SimplePhpRenderer;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\ChainResolver;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\View\Resolver\DirResolver;
/**
 * Displays a rating box for the plugin in the WordPress repository.
 */
class RateBox
{
    /** @var Renderer */
    private $renderer;
    public function __construct(?Renderer $renderer = null)
    {
        $this->renderer = $renderer ?? new SimplePhpRenderer(new DirResolver(__DIR__ . '/Views/'));
    }
    /**
     * @param string $url
     * @param string $description
     * @param string $header
     * @param string $footer
     *
     * @return string
     */
    public function render(string $url, string $description = '', string $header = '', string $footer = ''): string
    {
        return $this->renderer->render('rate-plugin', ['url' => $url, 'description' => $description, 'header' => $header, 'footer' => $footer]);
    }
}
