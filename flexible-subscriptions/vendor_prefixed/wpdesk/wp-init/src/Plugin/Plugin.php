<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin;

final class Plugin
{
    /**
     * Plugin basename.
     *
     * Ex: plugin-name/plugin-name.php
     */
    private string $basename;
    /**
     * Absolute path to the main plugin directory.
     */
    private string $directory;
    /**
     * Plugin name to display.
     */
    private string $name;
    /**
     * Absolute path to the main plugin file.
     */
    private string $file;
    /**
     * Plugin identifier.
     */
    private string $slug;
    /**
     * URL to the main plugin directory.
     */
    private string $url;
    /**
     * Plugin version string.
     */
    private string $version;
    private Header $header;
    public function __construct(string $file, Header $header)
    {
        $this->file = $file;
        $this->name = $header->get('Name');
        $this->version = $header->has('Version') ? $header->get('Version') : '0.0.0';
        $this->basename = plugin_basename($file);
        $this->directory = rtrim(plugin_dir_path($file), '/') . '/';
        $this->url = rtrim(plugin_dir_url($file), '/') . '/';
        $this->slug = $header->has('TextDomain') ? $header->get('TextDomain') : basename($this->directory);
        $this->header = $header;
    }
    /**
     * Retrieve the absolute path for the main plugin file.
     */
    public function get_basename(): string
    {
        return $this->basename;
    }
    /**
     * Retrieve the path to a file in the plugin.
     *
     * @param string $path Optional. Path relative to the plugin root.
     */
    public function get_path(string $path = ''): string
    {
        return $this->directory . ltrim($path, '/');
    }
    /**
     * Retrieve the absolute path for the main plugin file.
     */
    public function get_file(): string
    {
        return $this->file;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Retrieve the plugin identifier.
     */
    public function get_slug(): string
    {
        return $this->slug;
    }
    /**
     * Retrieve the URL for a file in the plugin.
     *
     * @param string $path Optional. Path relative to the plugin root.
     */
    public function get_url(string $path = ''): string
    {
        return $this->url . ltrim($path, '/');
    }
    public function get_version(): string
    {
        return $this->version;
    }
    public function header(): Header
    {
        return $this->header;
    }
}
