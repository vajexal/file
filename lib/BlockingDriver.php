<?php

namespace Amp\File;

class BlockingDriver implements Driver
{
    /** @inheritdoc */
    public function open(string $path, string $mode): Handle
    {
        $mode = \str_replace(['b', 't', 'e'], '', $mode);

        switch ($mode) {
            case "r":
            case "r+":
            case "w":
            case "w+":
            case "a":
            case "a+":
            case "x":
            case "x+":
            case "c":
            case "c+":
                break;

            default:
                throw new \Error("Invalid file mode");
        }

        if (!$fh = \fopen($path, $mode . 'be')) {
            throw new FilesystemException("Failed opening file handle");
        }

        return new BlockingHandle($fh, $path, $mode);
    }

    /** @inheritdoc */
    public function stat(string $path): ?array
    {
        if ($stat = StatCache::get($path)) {
            return $stat;
        }

        if ($stat = @\stat($path)) {
            StatCache::set($path, $stat);
            \clearstatcache(true, $path);

            return $stat;
        }

        return null;
    }

    /** @inheritdoc */
    public function exists(string $path): bool
    {
        if ($exists = @\file_exists($path)) {
            \clearstatcache(true, $path);
        }

        return $exists;
    }

    /** @inheritdoc */
    public function size(string $path): int
    {
        if (!@\file_exists($path)) {
            throw new FilesystemException("Path does not exist");
        }

        if (!@\is_file($path)) {
            throw new FilesystemException("Path is not a regular file");
        }

        if (($size = @\filesize($path)) === false) {
            throw new FilesystemException(\error_get_last()["message"]);
        }

        \clearstatcache(true, $path);

        return $size;
    }

    /** @inheritdoc */
    public function isDir(string $path): bool
    {
        if (!@\file_exists($path)) {
            return false;
        }

        $isDir = @\is_dir($path);
        \clearstatcache(true, $path);

        return $isDir;
    }

    /** @inheritdoc */
    public function isFile(string $path): bool
    {
        if (!@\file_exists($path)) {
            return false;
        }

        $isFile = @\is_file($path);
        \clearstatcache(true, $path);

        return $isFile;
    }

    /** @inheritdoc */
    public function mtime(string $path): int
    {
        if (!@\file_exists($path)) {
            throw new FilesystemException("Path does not exist");
        }

        $mtime = @\filemtime($path);
        \clearstatcache(true, $path);

        return $mtime;
    }

    /** @inheritdoc */
    public function atime(string $path): int
    {
        if (!@\file_exists($path)) {
            throw new FilesystemException("Path does not exist");
        }

        $atime = @\fileatime($path);
        \clearstatcache(true, $path);

        return $atime;
    }

    /** @inheritdoc */
    public function ctime(string $path): int
    {
        if (!@\file_exists($path)) {
            throw new FilesystemException("Path does not exist");
        }

        $ctime = @\filectime($path);
        \clearstatcache(true, $path);

        return $ctime;
    }

    /** @inheritdoc */
    public function lstat(string $path): ?array
    {
        if ($stat = @\lstat($path)) {
            \clearstatcache(true, $path);

            return $stat;
        }

        return null;
    }

    /** @inheritdoc */
    public function symlink(string $target, string $link): void
    {
        if (!@\symlink($target, $link)) {
            throw new FilesystemException("Could not create symbolic link");
        }
    }

    /** @inheritdoc */
    public function link(string $target, string $link): void
    {
        if (!@\link($target, $link)) {
            throw new FilesystemException("Could not create hard link");
        }
    }

    /** @inheritdoc */
    public function readlink(string $path): string
    {
        if (false === ($result = @\readlink($path))) {
            throw new FilesystemException("Could not read symbolic link");
        }

        return $result;
    }

    /** @inheritdoc */
    public function rename(string $from, string $to): void
    {
        if (!@\rename($from, $to)) {
            throw new FilesystemException("Could not rename file");
        }
    }

    /** @inheritdoc */
    public function unlink(string $path): void
    {
        StatCache::clear($path);
        @\unlink($path);
    }

    /** @inheritdoc */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): void
    {
        if (!\mkdir($path, $mode, $recursive) && !\is_dir($path)) {
            throw new FilesystemException(\sprintf('Directory "%s" was not created', $path));
        }
    }

    /** @inheritdoc */
    public function rmdir(string $path): void
    {
        StatCache::clear($path);
        @\rmdir($path);
    }

    /** @inheritdoc */
    public function scandir(string $path): array
    {
        if (!@\is_dir($path)) {
            throw new FilesystemException("Not a directory");
        }

        if ($entries = @\scandir($path, SCANDIR_SORT_NONE)) {
            \clearstatcache(true, $path);

            return \array_values(\array_filter($entries, function ($entry) {
                return !($entry === "." || $entry === "..");
            }));
        }

        throw new FilesystemException("Failed reading contents from {$path}");
    }

    /** @inheritdoc */
    public function chmod(string $path, int $mode): void
    {
        @\chmod($path, $mode);
    }

    /** @inheritdoc */
    public function chown(string $path, int $uid, int $gid): void
    {
        if ($uid !== -1 && !@\chown($path, $uid)) {
            throw new FilesystemException(\error_get_last()["message"]);
        }

        if ($gid !== -1 && !@\chgrp($path, $gid)) {
            throw new FilesystemException(\error_get_last()["message"]);
        }
    }

    /** @inheritdoc */
    public function touch(string $path, int $time = null, int $atime = null): void
    {
        $time = $time ?? \time();
        $atime = $atime ?? $time;

        /** @noinspection PotentialMalwareInspection */
        \touch($path, $time, $atime);
    }

    /** @inheritdoc */
    public function get(string $path): string
    {
        $result = @\file_get_contents($path);

        if ($result === false) {
            throw new FilesystemException(\error_get_last()["message"]);
        }

        return $result;
    }

    /** @inheritdoc */
    public function put(string $path, string $contents): void
    {
        $result = @\file_put_contents($path, $contents);

        if ($result === false) {
            throw new FilesystemException(\error_get_last()["message"]);
        }
    }
}
