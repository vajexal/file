<?php

namespace Amp\File\Test;

use Amp\File;

class BlockingHandleTest extends HandleTest
{
    protected function execute(callable $callback): void
    {
        File\filesystem(new File\BlockingDriver);
        $callback();
    }
}
