<?php

namespace Amp\File\Internal;

use Amp\Loop;
use Concurrent\Awaitable;
use Concurrent\Deferred;

class UvPoll
{
    /** @var string */
    private $watcher;

    /** @var int */
    private $requests = 0;

    /** @var callable */
    private $onDone;

    public function __construct()
    {
        $this->onDone = \Closure::fromCallable([$this, "done"]);

        $this->watcher = Loop::repeat(\PHP_INT_MAX / 2, function () {
            // do nothing, it's a dummy watcher
        });

        Loop::disable($this->watcher);

        Loop::setState(self::class, new class($this->watcher) {
            private $watcher;

            public function __construct(string $watcher)
            {
                $this->watcher = $watcher;
            }

            public function __destruct()
            {
                Loop::cancel($this->watcher);
            }
        });
    }

    public function listen(Awaitable $awaitable): void
    {
        if ($this->requests++ === 0) {
            Loop::enable($this->watcher);
        }

        Deferred::transform($awaitable, $this->onDone);
    }

    private function done(): void
    {
        if (--$this->requests === 0) {
            Loop::disable($this->watcher);
        }

        \assert($this->requests >= 0);
    }
}
