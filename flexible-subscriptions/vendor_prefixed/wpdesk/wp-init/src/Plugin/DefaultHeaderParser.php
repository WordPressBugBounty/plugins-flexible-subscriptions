<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Plugin;

class DefaultHeaderParser implements HeaderParser
{
    private const KB_IN_BYTES = 1024;
    private const HEADERS = ['Name' => 'Plugin Name', 'PluginURI' => 'Plugin URI', 'Version' => 'Version', 'Author' => 'Author', 'AuthorURI' => 'Author URI', 'TextDomain' => 'Text Domain', 'DomainPath' => 'Domain Path', 'Network' => 'Network', 'RequiresWP' => 'Requires at least', 'RequiresWC' => 'WC requires at least', 'RequiresPHP' => 'Requires PHP', 'TestedWP' => 'Tested up to', 'TestedWC' => 'WC tested up to', 'UpdateURI' => 'Update URI', 'RequiresPlugins' => 'Requires Plugins'];
    /**
     * Parses the plugin contents to retrieve plugin's metadata.
     * All plugin headers must be on their own line. Plugin description must not have
     * any newlines, otherwise only parts of the description will be displayed.
     * The below is formatted for printing.
     *     /*
     *     Plugin Name: Name of the plugin.
     *     Plugin URI: The home page of the plugin.
     *     Author: Plugin author's name.
     *     Author URI: Link to the author's website.
     *     Version: Plugin version.
     *     Text Domain: Optional. Unique identifier, should be same as the one used in
     *          load_plugin_textdomain().
     *     Domain Path: Optional. Only useful if the translations are located in a
     *          folder above the plugin's base path. For example, if .mo files are
     *          located in the locale folder then Domain Path will be "/locale/" and
     *          must have the first slash. Defaults to the base folder the plugin is
     *          located in.
     *     Network: Optional. Specify "Network: true" to require that a plugin is activated
     *          across all sites in an installation. This will prevent a plugin from being
     *          activated on a single site when Multisite is enabled.
     *     Requires at least: Optional. Specify the minimum required WordPress version.
     *     Requires PHP: Optional. Specify the minimum required PHP version.
     *     * / # Remove the space to close comment.
     * The first 8 KB of the file will be pulled in and if the plugin data is not
     * within that first 8 KB, then the plugin author should correct their plugin
     * and move the plugin data headers to the top.
     * The plugin file is assumed to have permissions to allow for scripts to read
     * the file. This is not checked however and the file is only opened for
     * reading.
     *
     * @param string $plugin_file Absolute path to the main plugin file.
     *
     * @return array{
     *     Name: string,
     *     PluginURI?: string,
     *     Version?: string,
     *     Author?: string,
     *     AuthorURI?: string,
     *     TextDomain?: string,
     *     DomainPath?: string,
     *     Network?: bool,
     *     RequiresWP?: string,
     *     RequiresWC?: string,
     *     RequiresPHP?: string,
     *     TestedWP?: string,
     *     TestedWC?: string,
     *     UpdateURI?: string,
     * }
     */
    public function parse(string $plugin_file): array
    {
        $plugin_data = $this->get_file_data($plugin_file, self::HEADERS);
        if (isset($plugin_data['Network'])) {
            $plugin_data['Network'] = filter_var($plugin_data['Network'], \FILTER_VALIDATE_BOOLEAN);
        }
        // If no text domain is defined fall back to the plugin slug.
        if (empty($plugin_data['TextDomain'])) {
            $plugin_slug = dirname($plugin_file);
            if ('.' !== $plugin_slug && \false === strpos($plugin_slug, '/')) {
                $plugin_data['TextDomain'] = $plugin_slug;
            }
        }
        return $plugin_data;
    }
    /**
     * Retrieves metadata from a file.
     * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
     * Each piece of metadata must be on its own line. Fields can not span multiple
     * lines, the value will get cut at the end of the first line.
     * If the file data is not within that first 8 KB, then the author should correct
     * their plugin file and move the data headers to the top.
     *
     * @link  https://codex.wordpress.org/File_Header
     * @since 2.9.0
     *
     * @param string $file Absolute path to the file.
     * @param array $default_headers List of headers, in the format `array( 'HeaderKey' => 'Header
     *                                Name' )`.
     *
     * @return string[] Array of file header values keyed by header name.
     */
    private function get_file_data(string $file, array $default_headers): array
    {
        // Pull only the first 8 KB of the file in.
        $file_data = file_get_contents($file, \false, null, 0, 8 * self::KB_IN_BYTES);
        if (\false === $file_data) {
            $file_data = '';
        }
        // Make sure we catch CR-only line endings.
        $file_data = \str_replace("\r", "\n", $file_data);
        $headers = [];
        foreach ($default_headers as $field => $regex) {
            if (preg_match('/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_data, $match) && $match[1]) {
                $headers[$field] = $this->_cleanup_header_comment($match[1]);
            }
        }
        return $headers;
    }
    /**
     * Strips close comment and close php tags from file headers used by WP.
     *
     * @see    https://core.trac.wordpress.org/ticket/8497
     */
    private function _cleanup_header_comment(string $str): string
    {
        return trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $str));
    }
}
