<?php

namespace Amp\File\Test;

use Amp\File;

class EioDriverTest extends DriverTest
{
    protected function execute(callable $callback): void
    {
        if (!\extension_loaded("eio")) {
            $this->markTestSkipped(
                "eio extension not loaded"
            );
        }

        File\filesystem(new File\EioDriver);
        $callback();
    }
}
