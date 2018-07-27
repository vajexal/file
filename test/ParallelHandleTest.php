<?php

namespace Amp\File\Test;

use Amp\File;
use Amp\Parallel\Worker\DefaultPool;

class ParallelHandleTest extends AsyncHandleTest
{
    protected function execute(callable $callback): void
    {
        $pool = new DefaultPool;
        File\filesystem(new File\ParallelDriver($pool));

        try {
            $callback();
        } finally {
            $pool->shutdown();
        }
    }
}
