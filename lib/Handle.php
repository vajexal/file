<?php

namespace Amp\File;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\ByteStream\StreamException;

interface Handle extends InputStream, OutputStream
{
    public const DEFAULT_READ_LENGTH = 8192;

    /**
     * Read `$length` bytes from the open file handle starting at the current offset.
     *
     * @param int $length
     *
     * @return string|null
     *
     * @throws StreamException
     */
    public function read(int $length = self::DEFAULT_READ_LENGTH): ?string;

    /**
     * Write $data to the open file handle starting at $offset.
     *
     * @param string $data
     *
     * @throws StreamException
     */
    public function write(string $data): void;

    /**
     * Write $data to the open file handle and close the handle once the write completes.
     *
     * @param string $data
     *
     * @throws StreamException
     */
    public function end(string $data = ""): void;

    /**
     * Close the file handle.
     *
     * Applications are not required to manually close handles -- they will
     * be unloaded automatically when the object is garbage collected.
     *
     * @throws StreamException
     */
    public function close(): void;

    /**
     * Set the handle's internal pointer position.
     *
     * $whence values:
     *
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset.
     *
     * @param int $position
     * @param int $whence
     *
     * @return int New offset position.
     *
     * @throws StreamException
     */
    public function seek(int $position, int $whence = \SEEK_SET): int;

    /**
     * Return the current internal offset position of the file handle.
     *
     * @return int
     *
     * @throws StreamException
     */
    public function tell(): int;

    /**
     * Test for "end-of-file" on the file handle.
     *
     * @return bool
     *
     * @throws StreamException
     */
    public function eof(): bool;

    /**
     * Retrieve the path used when opening the file handle.
     *
     * @return string
     */
    public function path(): string;

    /**
     * Retrieve the mode used when opening the file handle.
     *
     * @return string
     */
    public function mode(): string;
}
