<?php

namespace Amp\File\Test;

use Amp\File;
use Amp\Loop;

class UvDriverTest extends DriverTest
{
    protected function execute(callable $callback): void
    {
        if (!\extension_loaded("uv")) {
            $this->markTestSkipped(
                "php-uv extension not loaded"
            );
        }

        // TODO: Skip if global loop isn't uv based instead
        $loop = new Loop\UvDriver;
        Loop::set($loop);

        File\filesystem(new File\UvDriver($loop));
        $callback();
    }
}
