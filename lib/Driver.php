<?php

namespace Amp\File;

interface Driver
{
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
    public function open(string $path, string $mode): Handle;

    /**
     * Execute a file stat operation.
     *
     * If the requested path does not exist the resulting Promise will resolve to NULL.
     *
     * @param string $path The file system path to stat
     *
     * @return array|null
     */
    public function stat(string $path): ?array;

    /**
     * Does the specified path exist?
     *
     * This function should never resolve as a failure -- only a successful bool value
     * indicating the existence of the specified path.
     *
     * @param string $path An absolute file system path
     *
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Retrieve the size in bytes of the file at the specified path.
     *
     * If the path does not exist or is not a regular file this
     * function's returned Promise WILL resolve as a failure.
     *
     * @param string $path An absolute file system path
     *
     * @return int
     *
     * @throws FilesystemException
     */
    public function size(string $path): int;

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
    public function isDir(string $path): bool;

    /**
     * Does the specified path exist and is it a file?
     *
     * If the path does not exist the returned Promise will resolve
     * to FALSE and will not reject with an error.
     *
     * @param string $path An absolute file system path
     *
     * @return bool
     */
    public function isFile(string $path): bool;

    /**
     * Retrieve the path's last modification time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return int
     *
     * @throws FilesystemException
     */
    public function mtime(string $path): int;

    /**
     * Retrieve the path's last access time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return int
     *
     * @throws FilesystemException
     */
    public function atime(string $path): int;

    /**
     * Retrieve the path's creation time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     *
     * @return int
     *
     * @throws FilesystemException
     */
    public function ctime(string $path): int;

    /**
     * Same as stat() except if the path is a link then the link's data is returned.
     *
     * @param string $path The file system path to stat
     *
     * @return array|null An associative array.
     */
    public function lstat(string $path): ?array;

    /**
     * Create a symlink $link pointing to the file/directory located at $target.
     *
     * @param string $target
     * @param string $link
     *
     * @throws FilesystemException
     */
    public function symlink(string $target, string $link): void;

    /**
     * Create a hard link $link pointing to the file/directory located at $target.
     *
     * @param string $target
     * @param string $link
     *
     * @throws FilesystemException
     */
    public function link(string $target, string $link): void;

    /**
     * Read the symlink at $path.
     *
     * @param string $target
     *
     * @return string
     *
     * @throws FilesystemException
     */
    public function readlink(string $target): string;

    /**
     * Rename a file or directory.
     *
     * @param string $from
     * @param string $to
     *
     * @throws FilesystemException
     */
    public function rename(string $from, string $to): void;

    /**
     * Delete a file.
     *
     * @param string $path
     */
    public function unlink(string $path): void;

    /**
     * Create a director.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     *
     * @throws FilesystemException
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): void;

    /**
     * Delete a directory.
     *
     * @param string $path
     */
    public function rmdir(string $path): void;

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
    public function scandir(string $path): array;

    /**
     * chmod a file or directory.
     *
     * @param string $path
     * @param int    $mode
     */
    public function chmod(string $path, int $mode): void;

    /**
     * chown a file or directory.
     *
     * @param string $path
     * @param int    $uid
     * @param int    $gid
     */
    public function chown(string $path, int $uid, int $gid): void;

    /**
     * Update the access and modification time of the specified path.
     *
     * If the file does not exist it will be created automatically.
     *
     * @param string $path
     * @param int    $time The touch time. If $time is not supplied, the current system time is used.
     * @param int    $atime The access time. If $atime is not supplied, value passed to the $time parameter is used.
     */
    public function touch(string $path, int $time = null, int $atime = null): void;

    /**
     * Buffer the specified file's contents.
     *
     * @param string $path The file path from which to buffer contents.
     *
     * @return string Buffered file contents.
     *
     * @throws FilesystemException
     */
    public function get(string $path): string;

    /**
     * Write the contents string to the specified path.
     *
     * @param string $path The file path to which to $contents should be written
     * @param string $contents The data to write to the specified $path
     *
     * @throws FilesystemException
     */
    public function put(string $path, string $contents): void;
}
