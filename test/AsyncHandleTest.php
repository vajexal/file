<?php

namespace Amp\File\Test;

use Amp\File;
use Concurrent\Task;

abstract class AsyncHandleTest extends HandleTest
{
    public function testSimultaneousReads(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");

            $awaitable1 = Task::async([$handle, 'read'], 20);
            $awaitable2 = Task::async([$handle, 'read'], 20);

            $expected = \substr(File\get(__FILE__), 0, 20);
            $this->assertSame($expected, Task::await($awaitable1));

            $this->expectException(File\PendingOperationError::class);
            Task::await($awaitable2);
        });
    }

    public function testSeekWhileReading(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");

            $awaitable1 = Task::async([$handle, 'read'], 10);
            $awaitable2 = Task::async([$handle, 'seek'], 0);

            $expected = \substr(File\get(__FILE__), 0, 10);
            $this->assertSame($expected, Task::await($awaitable1));

            $this->expectException(File\PendingOperationError::class);
            Task::await($awaitable2);
        });
    }

    public function testReadWhileWriting(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");

            $data = "test";

            $awaitable1 = Task::async([$handle, 'write'], $data);
            $awaitable2 = Task::async([$handle, 'read'], 10);

            Task::await($awaitable1);

            $this->expectException(File\PendingOperationError::class);
            Task::await($awaitable2);
        });
    }

    public function testWriteWhileReading(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");

            $awaitable1 = Task::async([$handle, 'read'], 10);
            $awaitable2 = Task::async([$handle, 'write'], "test");

            $expected = \substr(File\get(__FILE__), 0, 10);
            $this->assertSame($expected, Task::await($awaitable1));

            $this->expectException(File\PendingOperationError::class);
            Task::await($awaitable2);
        });
    }
}
