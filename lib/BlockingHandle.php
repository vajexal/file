<?php

namespace Amp\File;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;

class BlockingHandle implements Handle
{
    private $fh;
    private $path;
    private $mode;

    /**
     * @param resource $fh An open uv filesystem descriptor
     * @param string   $path
     * @param string   $mode
     */
    public function __construct($fh, string $path, string $mode)
    {
        $this->fh = $fh;
        $this->path = $path;
        $this->mode = $mode;
    }

    public function __destruct()
    {
        if ($this->fh !== null) {
            \fclose($this->fh);
        }
    }

    /** @inheritdoc */
    public function read(int $length = self::DEFAULT_READ_LENGTH): ?string
    {
        if ($this->fh === null) {
            throw new ClosedException("The file has been closed");
        }

        $data = \fread($this->fh, $length);

        if ($data !== false) {
            return '' !== $data ? $data : null;
        }

        throw new StreamException("Failed reading from file handle");
    }

    /** @inheritdoc */
    public function write(string $data): void
    {
        if ($this->fh === null) {
            throw new ClosedException("The file has been closed");
        }

        $len = \fwrite($this->fh, $data);

        if ($len === false) {
            throw new StreamException("Failed writing to file handle");
        }
    }

    /** @inheritdoc */
    public function end(string $data = ""): void
    {
        try {
            $this->write($data);
        } finally {
            $this->close();
        }
    }

    /** @inheritdoc */
    public function close(): void
    {
        if ($this->fh === null) {
            return;
        }

        $fh = $this->fh;
        $this->fh = null;

        if (@\fclose($fh)) {
            return;
        }

        throw new StreamException("Failed closing file handle");
    }

    /** @inheritdoc */
    public function seek(int $position, int $whence = \SEEK_SET): int
    {
        if ($this->fh === null) {
            throw new ClosedException("The file has been closed");
        }

        switch ($whence) {
            case \SEEK_SET:
            case \SEEK_CUR:
            case \SEEK_END:
                if (@\fseek($this->fh, $position, $whence) === -1) {
                    throw new StreamException("Could not seek in file");
                }
                return $this->tell();
            default:
                throw new \Error(
                    "Invalid whence parameter; SEEK_SET, SEEK_CUR or SEEK_END expected"
                );
        }
    }

    /** @inheritdoc */
    public function tell(): int
    {
        if ($this->fh === null) {
            throw new ClosedException("The file has been closed");
        }

        return \ftell($this->fh);
    }

    /** @inheritdoc */
    public function eof(): bool
    {
        if ($this->fh === null) {
            throw new ClosedException("The file has been closed");
        }

        return \feof($this->fh);
    }

    /** @inheritdoc */
    public function path(): string
    {
        return $this->path;
    }

    /** @inheritdoc */
    public function mode(): string
    {
        return $this->mode;
    }
}
