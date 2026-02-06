<?php

/**
 * This file have to be compatible with PHP >=7.0 to gracefully handle outdated client's websites.
 */
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\LegacyExtension;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\BuiltinExtension;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\ConfigExtension;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\ExtensionsSet;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util\PhpFileLoader;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Configuration\Configuration;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Extension\ConditionalExtension;
final class Init
{
    /** @var bool */
    private static $bootable = \true;
    /** @var Configuration */
    private $config;
    /**
     * @param string|array<string,mixed>|Configuration $config
     *
     * @return self
     */
    public static function setup($config)
    {
        $result = require __DIR__ . '/platform_check.php';
        if ($result === \false) {
            self::$bootable = \false;
        }
        return new self($config);
    }
    /**
     * @param string|array<string, mixed>|Configuration $config
     */
    public function __construct($config)
    {
        if ($config instanceof Configuration) {
            $this->config = $config;
        } elseif (\is_array($config)) {
            $this->config = new Configuration($config);
        } elseif (\is_string($config)) {
            $loader = new PhpFileLoader();
            $this->config = new Configuration($loader->load($config));
        } else {
            throw new \InvalidArgumentException(sprintf('Configuration must be either path to configuration file, array of configuration data or %s instance', Configuration::class));
        }
    }
    /**
     * @param string|null $filename Filename of the booted plugin. May be null, if called from plugin's main file.
     *
     * @return void
     */
    public function boot($filename = null)
    {
        if (self::$bootable === \false) {
            return;
        }
        if ($filename === null) {
            $backtrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
            $filename = $backtrace[0]['file'];
        }
        $extensions = new ExtensionsSet(new BuiltinExtension(), new ConfigExtension(), new ConditionalExtension());
        if ($this->config->get('legacy', \false) && class_exists(\WPDesk\FlexibleSubscriptions\Vendor\WPDesk_Plugin_Info::class)) {
            $extensions->add(new LegacyExtension());
        }
        $kernel = new Kernel($filename, $this->config, $extensions);
        $kernel->boot();
    }
}
