<?php

namespace Amp\File;

use Amp\Loop;

const LOOP_STATE_IDENTIFIER = Driver::class;

/**
 * Retrieve the application-wide filesystem instance.
 *
 * @param Driver $driver Use the specified object as the application-wide filesystem instance
 *
 * @return Driver
 */
function filesystem(Driver $driver = null): Driver
{
    if ($driver === null) {
        $driver = Loop::getState(LOOP_STATE_IDENTIFIER);

        if ($driver) {
            return $driver;
        }

        $driver = driver();
    }

    if (\defined("AMP_WORKER") && $driver instanceof ParallelDriver) {
        throw new \Error("Cannot use the parallel driver within a worker");
    }

    Loop::setState(LOOP_STATE_IDENTIFIER, $driver);

    return $driver;
}

/**
 * Create a new filesystem driver best-suited for the current environment.
 *
 * @return Driver
 */
function driver(): Driver
{
    $driver = Loop::get();

    if ($driver instanceof Loop\UvDriver) {
        return new UvDriver($driver);
    }

    if (\extension_loaded("eio")) {
        return new EioDriver;
    }

    if (\defined("AMP_WORKER")) { // Prevent spawning infinite workers.
        return new BlockingDriver;
    }

    return new ParallelDriver;
}

/**
 * Open a handle for the specified path.
 *
 * @param string $path
 * @param string $mode
 *
 * @return Handle
 *
 * @throws FilesystemException
 */
function open(string $path, string $mode): Handle
{
    return filesystem()->open($path, $mode);
}

/**
 * Execute a file stat operation.
 *
 * If the requested path does not exist the resulting Promise will resolve to NULL.
 * The returned Promise would never resolve as a failure.
 *
 * @param string $path An absolute file system path
 *
 * @return array|null
 */
function stat(string $path): ?array
{
    return filesystem()->stat($path);
}

/**
 * Does the specified path exist?
 *
 * This function should never throw -- only a successful bool value indicating the existence of the specified path.
 *
 * @param string $path An absolute file system path.
 *
 * @return bool
 */
function exists(string $path): bool
{
    return filesystem()->exists($path);
}

/**
 * Retrieve the size in bytes of the file at the specified path.
 *
 * If the path does not exist or is not a regular file this function will throw.
 *
 * @param string $path An absolute file system path
 *
 * @return int
 *
 * @throws FilesystemException If the path does not exist or is not a file
 */
function size(string $path): int
{
    return filesystem()->size($path);
}

/**
 * Does the specified path exist and is it a directory?
 *
 * If the path does not exist the returned Promise will resolve
 * to FALSE and will not reject with an error.
 *
 * @param string $path An absolute file system path
 *
 * @return bool
 */
function isDir(string $path): bool
{
    return filesystem()->isDir($path);
}

/**
 * Does the specified path exist and is it a file?
 *
 * If the path does not exist the function will return `false` and will not throw an error.
 *
 * @param string $path An absolute file system path.
 *
 * @return bool
 */
function isFile(string $path): bool
{
    return filesystem()->isFile($path);
}

/**
 * Retrieve the path's last modification time as a unix timestamp.
 *
 * @param string $path An absolute file system path.
 *
 * @throws FilesystemException If the path does not exist.
 *
 * @return int
 */
function mtime(string $path): int
{
    return filesystem()->mtime($path);
}

/**
 * Retrieve the path's last access time as a unix timestamp.
 *
 * @param string $path An absolute file system path.
 *
 * @return int
 *
 * @throws FilesystemException If the path does not exist
 */
function atime($path)
{
    return filesystem()->atime($path);
}

/**
 * Retrieve the path's creation time as a unix timestamp.
 *
 * @param string $path An absolute file system path.
 *
 * @return int
 *
 * @throws FilesystemException If the path does not exist.
 */
function ctime(string $path): int
{
    return filesystem()->ctime($path);
}

/**
 * Same as stat() except if the path is a link then the link's data is returned.
 *
 * If the requested path does not exist result will be `null`.
 *
 * @param string $path An absolute file system path
 *
 * @return array|null
 */
function lstat(string $path): ?array
{
    return filesystem()->lstat($path);
}

/**
 * Create a symlink $link pointing to the file/directory located at $original.
 *
 * @param string $original
 * @param string $link
 *
 * @throws FilesystemException If the operation fails.
 */
function symlink(string $original, string $link): void
{
    filesystem()->symlink($original, $link);
}

/**
 * Create a hard link $link pointing to the file/directory located at $original.
 *
 * @param string $original
 * @param string $link
 *
 * @throws  FilesystemException If the operation fails.
 */
function link(string $original, string $link): void
{
    filesystem()->symlink($original, $link);
}

/**
 * Read the symlink at $path.
 *
 * @param string $path
 *
 * @return string
 *
 * @throws FilesystemException If the operation fails.
 */
function readlink(string $path): string
{
    return filesystem()->readlink($path);
}

/**
 * Rename a file or directory.
 *
 * @param string $from
 * @param string $to
 *
 * @throws FilesystemException If the operation fails
 */
function rename(string $from, string $to): void
{
    filesystem()->rename($from, $to);
}

/**
 * Delete a file.
 *
 * @param string $path
 */
function unlink(string $path): void
{
    filesystem()->unlink($path);
}

/**
 * Create a director.
 *
 * @param string $path
 * @param int    $mode
 * @param bool   $recursive
 *
 * @throws FilesystemException
 */
function mkdir(string $path, int $mode = 0777, bool $recursive = false): void
{
    filesystem()->mkdir($path, $mode, $recursive);
}

/**
 * Delete a directory.
 *
 * @param string $path
 */
function rmdir(string $path): void
{
    filesystem()->rmdir($path);
}

/**
 * Retrieve an array of files and directories inside the specified path.
 *
 * Dot entries are not included in the resulting array (i.e. "." and "..").
 *
 * @param string $path
 *
 * @return array
 *
 * @throws FilesystemException
 */
function scandir(string $path): array
{
    return filesystem()->scandir($path);
}

/**
 * chmod a file or directory.
 *
 * @param string $path
 * @param int    $mode
 */
function chmod(string $path, int $mode): void
{
    filesystem()->chmod($path, $mode);
}

/**
 * chown a file or directory.
 *
 * @param string $path
 * @param int    $uid -1 to ignore
 * @param int    $gid -1 to ignore
 */
function chown(string $path, int $uid, int $gid = -1): void
{
    filesystem()->chown($path, $uid, $gid);
}

/**
 * Update the access and modification time of the specified path.
 *
 * If the file does not exist it will be created automatically.
 *
 * @param string $path
 * @param int    $time The touch time. If $time is not supplied, the current system time is used.
 * @param int    $atime The access time. If $atime is not supplied, value passed to the $time parameter is used.
 */
function touch(string $path, int $time = null, int $atime = null): void
{
    filesystem()->touch($path, $time, $atime);
}

/**
 * Buffer the specified file's contents.
 *
 * @param string $path The file path from which to buffer contents
 *
 * @return string
 *
 * @throws FilesystemException
 */
function get(string $path): string
{
    return filesystem()->get($path);
}

/**
 * Write the contents string to the specified path.
 *
 * @param string $path The file path to which to $contents should be written
 * @param string $contents The data to write to the specified $path
 *
 * @throws FilesystemException
 */
function put(string $path, string $contents): void
{
    filesystem()->put($path, $contents);
}
