<?php

namespace Amp\File\Test;

use Amp\Delayed;
use Amp\File;
use Amp\File\StatCache;

class StatCacheTest extends FilesystemTest
{
    protected function setUp(): void
    {
        parent::setUp();

        StatCache::ttl(1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        StatCache::ttl(3);
    }

    public function testStatCacheExpiration()
    {
        File\filesystem(new File\ParallelDriver);

        $fixtureDir = Fixture::path();
        $path       = "{$fixtureDir}/small.txt";

        $changeTime = yield File\mtime($path);

        yield new Delayed(1000);

        yield File\put($path, 'smaller');

        yield new Delayed(1000);

        $this->assertNotEquals($changeTime, yield File\mtime($path));
    }
}
