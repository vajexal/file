<?php

namespace Amp\File\Test;

use Amp\File;
use Amp\PHPUnit\TestCase;

abstract class DriverTest extends TestCase
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

    public function testScandir(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $actual = File\scandir($fixtureDir);
            $expected = ["dir", "small.txt"];
            $this->assertSame($expected, $actual);
        });
    }

    public function testScandirThrowsIfPathNotADirectory(): void
    {
        $this->execute(function () {
            $this->expectException(File\FilesystemException::class);
            File\scandir(__FILE__);
        });
    }

    public function testScandirThrowsIfPathDoesntExist(): void
    {
        $this->execute(function () {
            $path = Fixture::path() . "/nonexistent";
            $this->expectException(File\FilesystemException::class);
            File\scandir($path);
        });
    }

    public function testSymlink(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();

            $original = "{$fixtureDir}/small.txt";
            $link = "{$fixtureDir}/symlink.txt";
            File\symlink($original, $link);
            $this->assertTrue(\is_link($link));
            File\unlink($link);
        });
    }

    public function testLstat(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();

            $target = "{$fixtureDir}/small.txt";
            $link = "{$fixtureDir}/symlink.txt";
            File\symlink($target, $link);
            $this->assertInternalType('array', File\lstat($link));
            File\unlink($link);
        });
    }

    public function testFileStat(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $stat = File\stat("{$fixtureDir}/small.txt");
            $this->assertInternalType("array", $stat);
        });
    }

    public function testDirStat(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $stat = File\stat("{$fixtureDir}/dir");
            $this->assertInternalType("array", $stat);
        });
    }

    public function testNonexistentPathStatResolvesToNull(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $stat = File\stat("{$fixtureDir}/nonexistent");
            $this->assertNull($stat);
        });
    }

    public function testExists(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $this->assertFalse(File\exists("{$fixtureDir}/nonexistent"));
            $this->assertTrue(File\exists("{$fixtureDir}/small.txt"));
        });
    }

    public function testGet(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $this->assertSame("small", File\get("{$fixtureDir}/small.txt"));
        });
    }

    public function testSize(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $stat = File\stat($path);
            $size = $stat["size"];
            File\StatCache::clear($path);
            $this->assertSame($size, File\size($path));
        });
    }

    public function testSizeFailsOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->expectException(File\FilesystemException::class);
            File\size($path);
        });
    }

    public function testSizeFailsOnDirectoryPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/dir";
            $this->assertTrue(File\isDir($path));
            File\StatCache::clear($path);
            $this->expectException(File\FilesystemException::class);
            File\size($path);
        });
    }

    public function testIsDirResolvesTrueOnDirectoryPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/dir";
            $this->assertTrue(File\isDir($path));
        });
    }

    public function testIsDirResolvesFalseOnFilePath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $this->assertFalse(File\isDir($path));
        });
    }

    public function testIsDirResolvesFalseOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->assertFalse(File\isDir($path));
        });
    }

    public function testIsFileResolvesTrueOnFilePath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $this->assertTrue(File\isFile($path));
        });
    }

    public function testIsFileResolvesFalseOnDirectoryPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/dir";
            $this->assertFalse(File\isFile($path));
        });
    }

    public function testIsFileResolvesFalseOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->assertFalse(File\isFile($path));
        });
    }

    public function testRename(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();

            $contents1 = "rename test";
            $old = "{$fixtureDir}/rename1.txt";
            $new = "{$fixtureDir}/rename2.txt";

            File\put($old, $contents1);
            File\rename($old, $new);
            $contents2 = File\get($new);
            File\unlink($new);

            $this->assertSame($contents1, $contents2);
        });
    }

    public function testUnlink(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $toUnlink = "{$fixtureDir}/unlink";
            File\put($toUnlink, "unlink me");
            File\unlink($toUnlink);
            $this->assertNull(File\stat($toUnlink));
        });
    }

    public function testMkdirRmdir(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();

            $dir = "{$fixtureDir}/newdir";

            \umask(0022);

            File\mkdir($dir);
            $stat = File\stat($dir);
            $this->assertSame('0755', $this->getPermissionsFromStat($stat));
            File\rmdir($dir);
            $this->assertNull(File\stat($dir));

            // test for 0, because previous array_filter made that not work
            $dir = "{$fixtureDir}/newdir/with/recursive/creation/0/1/2";

            File\mkdir($dir, 0764, true);
            $stat = File\stat($dir);
            $this->assertSame('0744', $this->getPermissionsFromStat($stat));
        });
    }

    public function testMtime(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $stat = File\stat($path);
            $statMtime = $stat["mtime"];
            File\StatCache::clear($path);
            $this->assertSame($statMtime, File\mtime($path));
        });
    }

    public function testMtimeFailsOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->expectException(File\FilesystemException::class);
            File\mtime($path);
        });
    }

    public function testAtime(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $stat = File\stat($path);
            $statAtime = $stat["atime"];
            File\StatCache::clear($path);
            $this->assertSame($statAtime, File\atime($path));
        });
    }

    public function testAtimeFailsOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->expectException(File\FilesystemException::class);
            File\atime($path);
        });
    }

    public function testCtime(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/small.txt";
            $stat = File\stat($path);
            $statCtime = $stat["ctime"];
            File\StatCache::clear($path);
            $this->assertSame($statCtime, File\ctime($path));
        });
    }

    public function testCtimeFailsOnNonexistentPath(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();
            $path = "{$fixtureDir}/nonexistent";
            $this->expectException(File\FilesystemException::class);
            File\ctime($path);
        });
    }

    /**
     * @group slow
     */
    public function testTouch(): void
    {
        $this->execute(function () {
            $fixtureDir = Fixture::path();

            $touch = "{$fixtureDir}/touch";
            File\put($touch, "touch me");

            $oldStat = File\stat($touch);
            /** @noinspection PotentialMalwareInspection */
            File\touch($touch, \time() + 10, \time() + 20);
            File\StatCache::clear($touch);
            $newStat = File\stat($touch);
            File\unlink($touch);

            $this->assertTrue($newStat["atime"] > $oldStat["atime"]);
            $this->assertTrue($newStat["mtime"] > $oldStat["mtime"]);
        });
    }

    /**
     * @param array $stat
     *
     * @return string
     */
    private function getPermissionsFromStat(array $stat): string
    {
        return \substr(\decoct($stat["mode"]), 1);
    }
}
