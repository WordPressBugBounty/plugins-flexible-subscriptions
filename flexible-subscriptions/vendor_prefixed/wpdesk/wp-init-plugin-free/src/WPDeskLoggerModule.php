<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\PluginFree;

use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LoggerInterface;
use WPDesk\FlexibleSubscriptions\Vendor\Psr\Log\LogLevel;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Bootstrap\BootstrapContext;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DependencyInjection\ContainerBuilder;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Module\AbstractModule;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin\Plugin;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Logger\SimpleLoggerFactory;
use function WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DI\factory;
use function WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\DI\get;
/**
 * Provides the plugin logger.
 */
final class WPDeskLoggerModule extends AbstractModule
{
    public function build(ContainerBuilder $builder, BootstrapContext $context): void
    {
        if (!class_exists(SimpleLoggerFactory::class)) {
            throw new \LogicException('WPDeskLoggerModule requires "wpdesk/wp-logs" to be installed.');
        }
        $config = $context->module_config(self::class);
        $this->assert_known_keys($config, ['level', 'action_level']);
        $level = $this->validate_level($config['level'] ?? LogLevel::DEBUG, 'level');
        $action_level = $this->validate_level($config['action_level'] ?? null, 'action_level');
        $builder->add_definitions([LoggerInterface::class => factory([self::class, 'create_logger'])->parameter('plugin', get(Plugin::class))->parameter('level', $level)->parameter('action_level', $action_level)]);
    }
    public static function create_logger(Plugin $plugin, string $level, ?string $action_level): LoggerInterface
    {
        return (new SimpleLoggerFactory($plugin->get_slug(), ['level' => $level, 'action_level' => $action_level]))->getLogger();
    }
    /**
     * @param array<string, mixed> $config
     * @param string[]             $allowed_keys
     */
    private function assert_known_keys(array $config, array $allowed_keys): void
    {
        foreach (array_keys($config) as $key) {
            if (!is_string($key) || !in_array($key, $allowed_keys, \true)) {
                throw new \LogicException(sprintf('Unknown WPDeskLoggerModule config key "%s".', (string) $key));
            }
        }
    }
    /**
     * @param mixed $level
     * @return string|null
     */
    private function validate_level($level, string $key)
    {
        if ($level === null && $key === 'action_level') {
            return null;
        }
        $levels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];
        if (!is_string($level) || !in_array($level, $levels, \true)) {
            throw new \LogicException(sprintf('WPDeskLoggerModule "%s" must be a PSR log level.', $key));
        }
        return $level;
    }
}
