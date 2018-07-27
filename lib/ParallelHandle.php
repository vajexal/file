<?php

namespace Amp\File;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;
use Amp\Parallel\Worker\TaskException;
use Amp\Parallel\Worker\Worker;
use Amp\Parallel\Worker\WorkerException;
use Concurrent\Awaitable;
use Concurrent\Task;

class ParallelHandle implements Handle
{
    /** @var Worker */
    private $worker;

    /** @var int|null */
    private $id;

    /** @var string */
    private $path;

    /** @var int */
    private $position;

    /** @var int */
    private $size;

    /** @var string */
    private $mode;

    /** @var bool True if an operation is pending. */
    private $busy = false;

    /** @var int Number of pending write operations. */
    private $pendingWrites = 0;

    /** @var bool */
    private $writable = true;

    /** @var Awaitable|null */
    private $closing;

    /**
     * @param Worker $worker
     * @param int    $id
     * @param string $path
     * @param int    $size
     * @param string $mode
     */
    public function __construct(Worker $worker, int $id, string $path, int $size, string $mode)
    {
        $this->worker = $worker;
        $this->id = $id;
        $this->path = $path;
        $this->size = $size;
        $this->mode = $mode;
        $this->position = $this->mode[0] === 'a' ? $this->size : 0;
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Throwable $e) {
            // ignore here
        }
    }

    /** @inheritdoc */
    public function path(): string
    {
        return $this->path;
    }

    /** @inheritdoc */
    public function close(): void
    {
        if ($this->closing) {
            Task::await($this->closing);
            return;
        }

        $id = $this->id;
        $this->id = null;
        $this->writable = false;

        if ($this->worker->isRunning()) {
            $this->closing = Task::async(function () use ($id) {
                $this->worker->enqueue(new Internal\FileTask('fclose', [], $id));
            });
        }
    }

    /** @inheritdoc */
    public function eof(): bool
    {
        return $this->pendingWrites === 0 && $this->size <= $this->position;
    }

    public function read(int $length = self::DEFAULT_READ_LENGTH): ?string
    {
        if ($this->id === null) {
            throw new ClosedException("The file has been closed");
        }

        if ($this->busy) {
            throw new PendingOperationError;
        }

        $this->busy = true;

        try {
            $data = $this->worker->enqueue(new Internal\FileTask('fread', [$length], $this->id));
            $this->position += \strlen($data);
            return $data;
        } catch (TaskException $exception) {
            throw new StreamException("Reading from the file failed", 0, $exception);
        } catch (WorkerException $exception) {
            throw new StreamException("Sending the task to the worker failed", 0, $exception);
        } finally {
            $this->busy = false;
        }
    }

    /** @inheritdoc */
    public function write(string $data): void
    {
        if ($this->id === null) {
            throw new ClosedException("The file has been closed");
        }

        if ($this->busy && $this->pendingWrites === 0) {
            throw new PendingOperationError;
        }

        if (!$this->writable) {
            throw new ClosedException("The file is no longer writable");
        }

        $this->pendingWrites++;
        $this->busy = true;

        try {
            $length = $this->worker->enqueue(new Internal\FileTask('fwrite', [$data], $this->id));
            $this->position += $length;
        } catch (TaskException $exception) {
            throw new StreamException("Writing to the file failed", 0, $exception);
        } catch (WorkerException $exception) {
            throw new StreamException("Sending the task to the worker failed", 0, $exception);
        } finally {
            if (--$this->pendingWrites === 0) {
                $this->busy = false;
            }
        }
    }

    /** @inheritdoc */
    public function end(string $data = ""): void
    {
        $this->write($data);
        $this->close();
    }

    /** @inheritdoc */
    public function seek(int $offset, int $whence = SEEK_SET): int
    {
        if ($this->id === null) {
            throw new ClosedException("The file has been closed");
        }

        if ($this->busy) {
            throw new PendingOperationError;
        }

        switch ($whence) {
            case \SEEK_SET:
            case \SEEK_CUR:
            case \SEEK_END:
                try {
                    $this->position = $this->worker->enqueue(
                        new Internal\FileTask('fseek', [$offset, $whence], $this->id)
                    );

                    if ($this->position > $this->size) {
                        $this->size = $this->position;
                    }

                    return $this->position;
                } catch (TaskException $exception) {
                    throw new StreamException('Seeking in the file failed.', 0, $exception);
                } catch (WorkerException $exception) {
                    throw new StreamException("Sending the task to the worker failed", 0, $exception);
                }

            default:
                throw new \Error('Invalid whence value. Use SEEK_SET, SEEK_CUR, or SEEK_END.');
        }
    }

    /** @inheritdoc */
    public function tell(): int
    {
        return $this->position;
    }

    /** @inheritdoc */
    public function size(): int
    {
        return $this->size;
    }

    /** @inheritdoc */
    public function mode(): string
    {
        return $this->mode;
    }
}
