<?php

namespace Amp\File;

use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Pool;
use Amp\Parallel\Worker\TaskException;
use Amp\Parallel\Worker\WorkerException;

class ParallelDriver implements Driver
{
    /** @var Pool */
    private $pool;

    /**
     * @param Pool|null $pool
     */
    public function __construct(Pool $pool = null)
    {
        $this->pool = $pool ?: Worker\pool();
    }

    /** @inheritdoc */
    public function open(string $path, string $mode): Handle
    {
        $worker = $this->pool->get();

        try {
            [$id, $size, $mode] = $worker->enqueue(new Internal\FileTask("fopen", [$path, $mode]));
        } catch (TaskException $exception) {
            throw new FilesystemException("Could not open file", $exception);
        } catch (WorkerException $exception) {
            throw new FilesystemException("Could not send open request to worker", $exception);
        }

        return new ParallelHandle($worker, $id, $path, $size, $mode);
    }

    private function runFileTask(Internal\FileTask $task)
    {
        try {
            return $this->pool->enqueue($task);
        } catch (TaskException $exception) {
            throw new FilesystemException("The file operation failed", $exception);
        } catch (WorkerException $exception) {
            throw new FilesystemException("Could not send the file task to worker", $exception);
        }
    }

    /** @inheritdoc */
    public function unlink(string $path): void
    {
        $this->runFileTask(new Internal\FileTask("unlink", [$path]));
        StatCache::clear($path);
    }

    /** @inheritdoc */
    public function stat(string $path): ?array
    {
        if ($stat = StatCache::get($path)) {
            return $stat;
        }

        $stat = $this->runFileTask(new Internal\FileTask("stat", [$path]));
        if (!empty($stat)) {
            StatCache::set($path, $stat);
        }

        return $stat;
    }

    /** @inheritdoc */
    public function rename(string $from, string $to): void
    {
        $this->runFileTask(new Internal\FileTask("rename", [$from, $to]));
    }

    /** @inheritdoc */
    public function isFile(string $path): bool
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            return false;
        }

        if ($stat["mode"] & 0100000) {
            return true;
        }

        return false;
    }

    /** @inheritdoc */
    public function isDir(string $path): bool
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            return false;
        }

        if ($stat["mode"] & 0040000) {
            return true;
        }

        return false;
    }

    /** @inheritdoc */
    public function link(string $target, string $link): void
    {
        $this->runFileTask(new Internal\FileTask("link", [$target, $link]));
    }

    /** @inheritdoc */
    public function symlink(string $target, string $link): void
    {
        $this->runFileTask(new Internal\FileTask("symlink", [$target, $link]));
    }

    /** @inheritdoc */
    public function readlink(string $path): string
    {
        return $this->runFileTask(new Internal\FileTask("readlink", [$path]));
    }

    /** @inheritdoc */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): void
    {
        $this->runFileTask(new Internal\FileTask("mkdir", [$path, $mode, $recursive]));
    }

    /** @inheritdoc */
    public function scandir(string $path): array
    {
        return $this->runFileTask(new Internal\FileTask("scandir", [$path]));
    }

    /** @inheritdoc */
    public function rmdir(string $path): void
    {
        $this->runFileTask(new Internal\FileTask("rmdir", [$path]));
        StatCache::clear($path);
    }

    /** @inheritdoc */
    public function chmod(string $path, int $mode): void
    {
        $this->runFileTask(new Internal\FileTask("chmod", [$path, $mode]));
    }

    /** @inheritdoc */
    public function chown(string $path, int $uid, int $gid): void
    {
        $this->runFileTask(new Internal\FileTask("chown", [$path, $uid, $gid]));
    }

    /** @inheritdoc */
    public function exists(string $path): bool
    {
        return $this->runFileTask(new Internal\FileTask("exists", [$path]));
    }

    /** @inheritdoc */
    public function size(string $path): int
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            throw new FilesystemException("Specified path does not exist");
        }

        if ($stat["mode"] & 0100000) {
            return $stat["size"];
        }

        throw new FilesystemException("Specified path is not a regular file");
    }

    /** @inheritdoc */
    public function mtime(string $path): int
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            throw new FilesystemException("Specified path does not exist");
        }

        return $stat["mtime"];
    }

    /** @inheritdoc */
    public function atime(string $path): int
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            throw new FilesystemException("Specified path does not exist");
        }

        return $stat["atime"];
    }

    /** @inheritdoc */
    public function ctime(string $path): int
    {
        $stat = $this->stat($path);

        if (empty($stat)) {
            throw new FilesystemException("Specified path does not exist");
        }

        return $stat["ctime"];
    }

    /** @inheritdoc */
    public function lstat(string $path): ?array
    {
        return $this->runFileTask(new Internal\FileTask("lstat", [$path]));
    }

    /** @inheritdoc */
    public function touch(string $path, int $time = null, int $atime = null): void
    {
        $this->runFileTask(new Internal\FileTask("touch", [$path, $time, $atime]));
    }

    /** @inheritdoc */
    public function get(string $path): string
    {
        return $this->runFileTask(new Internal\FileTask("get", [$path]));
    }

    /** @inheritdoc */
    public function put(string $path, string $contents): void
    {
        $this->runFileTask(new Internal\FileTask("put", [$path, $contents]));
    }
}
