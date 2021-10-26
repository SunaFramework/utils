<?php declare(strict_types=1);

namespace Suna\Utils;

use FilesystemIterator;
use Suna\Exceptions\InvalidStateException;
use Suna\Exceptions\IOException;
use Suna\Traits\TrStaticClass;

/**
 * FileSystem Helpers
 *
 * @version 0.1
 * @author Web
 */
final class FileSystem
{
    use TrStaticClass;

    /**
     * @param string $dir
     * @param int $mode
     */
    public static function createDir(string $dir, int $mode = 0777): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) { // @ - dir may already exist
            throw new IOException("Unable to create directory '$dir' with mode " . decoct($mode) . '. ' . Helpers::getLastError());
        }
    }

    /**
     * @param string $origin
     * @param string $target
     * @param bool $overwrite
     */
    public static function copy(string $origin, string $target, bool $overwrite = true): void
    {
        if (stream_is_local($origin) && !file_exists($origin)) {
            throw new IOException("File or directory '$origin' not found.");

        } elseif (!$overwrite && file_exists($target)) {
            throw new InvalidStateException("File or directory '$target' already exists.");

        } elseif (is_dir($origin)) {
            static::createDir($target);
            foreach (new \FilesystemIterator($target) as $item) {
                static::delete($item->getPathname());
            }
            foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($origin, FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
                if ($item->isDir()) {
                    static::createDir($target . '/' . $iterator->getSubPathName());
                } else {
                    static::copy($item->getPathname(), $target . '/' . $iterator->getSubPathName());
                }
            }

        } else {
            static::createDir(dirname($target));
            if (
                ($s = @fopen($origin, 'rb'))
                && ($d = @fopen($target, 'wb'))
                && @stream_copy_to_stream($s, $d) === false
            ) {
                throw new IOException("Unable to copy file '$origin' to '$target'. " . Helpers::getLastError());
            }
        }
    }

    /**
     * @param string $path
     */
    public static function delete(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            $func = DIRECTORY_SEPARATOR === '\\' && is_dir($path) ? 'rmdir' : 'unlink';
            if (!@$func($path)) { // @ is escalated to exception
                throw new IOException("Unable to delete '$path'. " . Helpers::getLastError());
            }

        } elseif (is_dir($path)) {
            foreach (new \FilesystemIterator($path) as $item) {
                static::delete($item->getPathname());
            }
            if (!@rmdir($path)) { // @ is escalated to exception
                throw new IOException("Unable to delete directory '$path'. " . Helpers::getLastError());
            }
        }
    }


    /**
     * @param string $origin
     * @param string $target
     * @param bool $overwrite
     */
    public static function rename(string $origin, string $target, bool $overwrite = true): void
    {
        if (!$overwrite && file_exists($target)) {
            throw new InvalidStateException("File or directory '$target' already exists.");

        } elseif (!file_exists($origin)) {
            throw new IOException("File or directory '$origin' not found.");

        } else {
            static::createDir(dirname($target));
            if (realpath($origin) !== realpath($target)) {
                static::delete($target);
            }
            if (!@rename($origin, $target)) {
                throw new IOException("Unable to rename file or directory '$origin' to '$target'. " . Helpers::getLastError());
            }
        }
    }

    /**
     * @param string $file
     * @return string
     */
    public static function read(string $file): string
    {
        $content = @file_get_contents($file);
        if ($content === false) {
            throw new IOException("Unable to read file '$file'. " . Helpers::getLastError());
        }
        return $content;
    }

    /**
     * @param string $file
     * @param string $content
     * @param int|null $mode
     */
    public static function write(string $file, string $content, ?int $mode = 0666): void
    {
        static::createDir(dirname($file));
        if (@file_put_contents($file, $content) === false) {
            throw new IOException("Unable to write file '$file'. " . Helpers::getLastError());
        }
        if ($mode !== null && !@chmod($file, $mode)) {
            throw new IOException("Unable to chmod file '$file' to mode " . decoct($mode) . '. ' . Helpers::getLastError());
        }
    }

    /**
     * @param string $path
     * @param int $dirMode
     * @param int $fileMode
     */
    public static function makeWritable(string $path, int $dirMode = 0777, int $fileMode = 0666): void
    {
        if (is_file($path)) {
            if (!@chmod($path, $fileMode)) {
                throw new IOException("Unable to chmod file '$path' to mode " . decoct($fileMode) . '. ' . Helpers::getLastError());
            }
            return;
        } elseif (is_dir($path)) {
            foreach (new \FilesystemIterator($path) as $item) {
                static::makeWritable($item->getPathname(), $dirMode, $fileMode);
            }
            if (!@chmod($path, $dirMode)) {
                throw new IOException("Unable to chmod directory '$path' to mode " . decoct($dirMode) . '. ' . Helpers::getLastError());
            }
            return;
        }
        throw new IOException("File or directory '$path' not found.");
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        $parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
        $res = [];
        foreach ($parts as $part) {
            if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
                array_pop($res);
            } elseif ($part !== '.') {
                $res[] = $part;
            }
        }
        return $res === [''] ? DIRECTORY_SEPARATOR : implode(DIRECTORY_SEPARATOR, $res);
    }

    /**
     * @param string ...$paths
     * @return string
     */
    public static function joinPaths(string ...$paths): string
    {
        return self::normalizePath(implode('/', $paths));
    }
}