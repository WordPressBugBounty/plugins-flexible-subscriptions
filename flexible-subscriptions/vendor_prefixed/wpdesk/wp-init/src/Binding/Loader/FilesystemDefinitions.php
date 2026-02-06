<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\Loader;

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Binding\DefinitionFactory;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util\Path;
use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util\PhpFileLoader;
class FilesystemDefinitions implements BindingDefinitions
{
    private Path $path;
    private PhpFileLoader $loader;
    private DefinitionFactory $def_factory;
    public function __construct($path, ?PhpFileLoader $loader = null, ?DefinitionFactory $def_factory = null)
    {
        $this->path = new Path((string) $path);
        $this->loader = $loader ?? new PhpFileLoader();
        $this->def_factory = $def_factory ?? new DefinitionFactory();
    }
    public function load(): iterable
    {
        if ($this->path->is_directory()) {
            foreach ($this->path->read_directory() as $filename) {
                yield from $this->load_from_file($filename);
            }
        } else {
            yield from $this->load_from_file($this->path);
        }
    }
    private function load_from_file(Path $filename): iterable
    {
        if (!$filename->is_file()) {
            return;
        }
        $hooks = $this->loader->load((string) $filename);
        if ($filename->get_basename() !== 'index.php') {
            $hooks = [$filename->get_filename_without_extension() => $hooks];
        }
        yield from (new ArrayDefinitions($hooks))->load();
    }
}
