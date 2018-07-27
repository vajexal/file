<?php

namespace Amp\File\Test;

use Amp\File;

class BlockingDriverTest extends DriverTest
{
    protected function execute(callable $callback): void
    {
        File\filesystem(new File\BlockingDriver);
        $callback();
    }
}
