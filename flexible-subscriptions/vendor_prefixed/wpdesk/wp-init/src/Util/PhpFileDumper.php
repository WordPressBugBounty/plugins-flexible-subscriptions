<?php

// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
declare (strict_types=1);
namespace WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Init\Util;

class PhpFileDumper
{
    public function dump(array $config, string $filename): void
    {
        $directory = dirname($filename);
        $this->createCompilationDirectory($directory);
        $content = '<?php' . \PHP_EOL . \PHP_EOL;
        $content .= 'declare(strict_types=1);' . \PHP_EOL . \PHP_EOL;
        $content .= 'return ' . var_export($config, \true) . ';' . \PHP_EOL;
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
        $this->writeFileAtomic($filename, $content);
    }
    private function createCompilationDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0777, \true) && !is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('Compilation directory does not exist and cannot be created: %s.', $directory));
        }
        if (!is_writable($directory)) {
            throw new \InvalidArgumentException(sprintf('Compilation directory is not writable: %s.', $directory));
        }
    }
    private function writeFileAtomic(string $fileName, string $content): void
    {
        $tmpFile = @tempnam(dirname($fileName), 'swap-compile');
        if ($tmpFile === \false) {
            throw new \InvalidArgumentException(sprintf('Error while creating temporary file in %s', dirname($fileName)));
        }
        @chmod($tmpFile, 0666);
        $written = file_put_contents($tmpFile, $content);
        if ($written === \false) {
            @unlink($tmpFile);
            throw new \InvalidArgumentException(sprintf('Error while writing to %s', $tmpFile));
        }
        @chmod($tmpFile, 0666);
        $renamed = @rename($tmpFile, $fileName);
        if (!$renamed) {
            @unlink($tmpFile);
            throw new \InvalidArgumentException(sprintf('Error while renaming %s to %s', $tmpFile, $fileName));
        }
    }
}
