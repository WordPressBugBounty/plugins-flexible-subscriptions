<?php

declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util;

final class Path
{
    private string $path;
    public function __construct(string $path)
    {
        $this->path = $path;
    }
    public function canonical(): self
    {
        $root = $this->is_posix_absolute_path($this->path) ? '/' : '';
        return new self($root . implode('/', $this->find_canonical_parts()));
    }
    public function absolute(?string $base_path = null): self
    {
        if ($this->is_absolute_path($this->path)) {
            if ($this->is_windows_absolute_path($this->path)) {
                return $this;
            }
            return $this->canonical();
        }
        $base_path ??= getcwd();
        return (new self(rtrim($base_path, '/\\') . '/' . $this->path))->canonical();
    }
    /** @return list<string> */
    private function find_canonical_parts(): array
    {
        $parts = explode('/', $this->path);
        $root = $this->is_posix_absolute_path($this->path) ? '/' : '';
        $canonical_parts = [];
        // Collapse "." and "..", if possible.
        foreach ($parts as $part) {
            if ('.' === $part || '' === $part) {
                continue;
            }
            // Collapse ".." with the previous part, if one exists
            // Don't collapse ".." if the previous part is also "..".
            if ('..' === $part && \count($canonical_parts) > 0 && '..' !== $canonical_parts[\count($canonical_parts) - 1]) {
                array_pop($canonical_parts);
                continue;
            }
            // Only add ".." prefixes for relative paths.
            if ('..' !== $part || '' === $root) {
                $canonical_parts[] = $part;
            }
        }
        return $canonical_parts;
    }
    public function is_directory(): bool
    {
        return is_dir($this->path);
    }
    public function is_file(): bool
    {
        return is_file($this->path);
    }
    public function get_basename(): string
    {
        return basename($this->path);
    }
    public function get_filename_without_extension(): string
    {
        return pathinfo($this->path, \PATHINFO_FILENAME);
    }
    public function join(string ...$parts): self
    {
        return new self($this->path . '/' . implode('/', $parts));
    }
    /** @return self[] */
    public function read_directory(): array
    {
        if (!$this->is_directory()) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a directory', $this->path));
        }
        return array_map(fn($file): Path => (new self($file))->absolute($this->path), array_values(array_diff(scandir($this->path), ['.', '..'])));
    }
    public function __toString(): string
    {
        return $this->path;
    }
    private function is_absolute_path(string $path): bool
    {
        return $this->is_posix_absolute_path($path) || $this->is_windows_absolute_path($path);
    }
    private function is_posix_absolute_path(string $path): bool
    {
        return isset($path[0]) && $path[0] === '/';
    }
    private function is_windows_absolute_path(string $path): bool
    {
        return isset($path[0]) && ($path[0] === '\\' || (bool) preg_match('/^[A-Za-z]:[\/\\\\]/', $path));
    }
}
