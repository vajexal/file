<?php

namespace Amp\File\Test;

use Amp\File;
use Amp\Loop;

class UvDriverTest extends DriverTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!\extension_loaded("uv")) {
            $this->markTestSkipped(
                "php-uv extension not loaded"
            );
        }

        $loop = new Loop\UvDriver;
        Loop::set($loop);
        File\filesystem(new File\UvDriver($loop));
    }

    /**
     * @dataProvider readlinkPathProvider
     *
     * @param \Closure $linkResolver
     */
    public function testReadlinkError(\Closure $linkResolver): \Generator
    {
        $this->markTestSkipped('UvDriver Test Skipped: Causes Crash');
        yield; // So test still returns a generator.
    }
}
