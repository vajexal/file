<?php

namespace Amp\File\Test;

use Amp\ByteStream\ClosedException;
use Amp\File;
use Amp\PHPUnit\TestCase;

abstract class HandleTest extends TestCase
{
    protected function setUp()
    {
        Fixture::init();
        File\StatCache::clear();
    }

    protected function tearDown()
    {
        Fixture::clear();
    }

    abstract protected function execute(callable $callback);

    public function testWrite(): void
    {
        $this->execute(function () {
            $path = Fixture::path() . "/write";
            $handle = File\open($path, "c+");
            $this->assertSame(0, $handle->tell());

            $handle->write("foo");
            $handle->write("bar");
            $handle->seek(0);
            $contents = $handle->read();
            $this->assertSame(6, $handle->tell());
            $this->assertTrue($handle->eof());
            $this->assertSame("foobar", $contents);

            $handle->close();
        });
    }

    public function testWriteAfterClose(): void
    {
        $this->execute(function () {
            $path = Fixture::path() . "/write";
            $handle = File\open($path, "c+");
            $handle->close();

            $this->expectException(ClosedException::class);
            $handle->write("bar");
        });
    }

    public function testDoubleClose(): void
    {
        $this->execute(function () {
            $path = Fixture::path() . "/write";
            $handle = File\open($path, "c+");
            $handle->close();
            $handle->close(); // shouldn't throw
            $this->assertTrue(true);
        });
    }

    public function testWriteAfterEnd(): void
    {
        $this->execute(function () {
            $path = Fixture::path() . "/write";
            $handle = File\open($path, "c+");
            $this->assertSame(0, $handle->tell());
            $handle->end("foo");

            $this->expectException(ClosedException::class);
            $handle->write("bar");
        });
    }

    public function testReadingToEof(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $contents = "";
            $position = 0;

            $stat = File\stat(__FILE__);
            $chunkSize = (int) \floor($stat["size"] / 5);

            while (!$handle->eof()) {
                $chunk = $handle->read($chunkSize);
                $contents .= $chunk;
                $position += \strlen($chunk);
                $this->assertSame($position, $handle->tell());
            }

            $this->assertNull($handle->read());
            $this->assertSame(File\get(__FILE__), $contents);

            $handle->close();
        });
    }

    public function testSequentialReads(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");

            $contents = "";
            $contents .= $handle->read(10);
            $contents .= $handle->read(10);

            $expected = \substr(File\get(__FILE__), 0, 20);
            $this->assertSame($expected, $contents);

            $handle->close();
        });
    }

    public function testReadingFromOffset(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $this->assertSame(0, $handle->tell());
            $handle->seek(10);
            $this->assertSame(10, $handle->tell());
            $chunk = $handle->read(90);
            $this->assertSame(100, $handle->tell());
            $expected = \substr(File\get(__FILE__), 10, 90);
            $this->assertSame($expected, $chunk);

            $handle->close();
        });
    }

    public function testSeekThrowsOnInvalidWhence(): void
    {
        $this->execute(function () {
            $this->expectException(\Error::class);

            try {
                $handle = File\open(__FILE__, "r");
                $handle->seek(0, 99999);
            } finally {
                $handle->close();
            }
        });
    }

    public function testSeekSetCur(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $this->assertSame(0, $handle->tell());
            $handle->seek(10);
            $this->assertSame(10, $handle->tell());
            $handle->seek(-10, \SEEK_CUR);
            $this->assertSame(0, $handle->tell());
            $handle->close();
        });
    }

    public function testSeekSetEnd(): void
    {
        $this->execute(function () {
            $size = File\size(__FILE__);
            $handle = File\open(__FILE__, "r");
            $this->assertSame(0, $handle->tell());
            $handle->seek(-10, \SEEK_END);
            $this->assertSame($size - 10, $handle->tell());
            $handle->close();
        });
    }

    public function testPath(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $this->assertSame(__FILE__, $handle->path());
            $handle->close();
        });
    }

    public function testMode(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $this->assertSame("r", $handle->mode());
            $handle->close();
        });
    }

    public function testClose(): void
    {
        $this->execute(function () {
            $handle = File\open(__FILE__, "r");
            $handle->close();

            $this->expectException(ClosedException::class);
            $handle->read();
        });
    }
}
