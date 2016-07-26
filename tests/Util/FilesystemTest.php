<?php

namespace LPLabs\Composer\Tests\Util;

use LPLabs\Composer\Util\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $tmpDir;

    /**
     * @var string
     */
    protected $tmpFile;

    /**
     * Setup the tests
     *
     * @return void
     */
    public function setUp()
    {
        $tmpDir = realpath(sys_get_temp_dir());

        $this->fs = new Filesystem;

        $this->tmpDir = $this->getTempDir();

        $this->tmpFile = tempnam($this->tmpDir, 'file');
    }

    /**
     * Cleanup
     *
     * @return void
     */
    public function tearDown()
    {
        $this->fs->removeDirectory($this->tmpDir);
    }

    protected function getTempDir()
    {
        $path = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . uniqid('wordpress-muplugin-installer');

        $this->recreateDirectory($path);

        return realpath($path);
    }

    protected function recreateDirectory($directory)
    {
        if (is_dir($directory)) {
            $this->fs->removeDirectory($directory);
        }

        mkdir($directory, 0777, true);
    }

    public function testExists()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->exists($this->tmpDir));

        $this->assertTrue($fs->exists($this->tmpFile));

        $this->assertFalse($fs->exists($this->tmpFile . DIRECTORY_SEPARATOR . '404.txt'));
    }

    public function testIsFile()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->isFile($this->tmpFile));

        $this->assertFalse($fs->isFile($this->tmpDir));
    }

    public function testIsDir()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->isDir($this->tmpDir));

        $this->assertFalse($fs->isDir($this->tmpFile));
    }

    public function testIsReadable()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->isReadable($this->tmpDir));

        $this->assertTrue($fs->isReadable($this->tmpFile));

        $unreadableFile = tempnam($this->tmpDir, 'unreadable');

        chmod($unreadableFile, 0000);

        $this->assertFalse($fs->isReadable($unreadableFile));
    }

    public function testIsWritable()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->isWritable($this->tmpDir));

        $this->assertTrue($fs->isWritable($this->tmpFile));

        $readOnlyFile = tempnam($this->tmpDir, 'readOnly');

        chmod($readOnlyFile, 0444);

        $this->assertFalse($fs->isWritable($readOnlyFile));
    }

    public function testCanWrite()
    {
        $fs = new Filesystem;

        $unwritableDir = $this->tmpDir . DIRECTORY_SEPARATOR . 'unwritable';

        mkdir($unwritableDir, 0555);

        $this->assertTrue($fs->canWrite($this->tmpDir));

        $this->assertTrue($fs->canWrite($this->tmpDir . DIRECTORY_SEPARATOR . 'new-file.txt'));

        $this->assertFalse($fs->canWrite($unwritableDir));

        $this->assertFalse($fs->canWrite($unwritableDir . DIRECTORY_SEPARATOR . 'new-file.txt'));
    }

    public function testCopyFile()
    {
        $fs = new Filesystem;

        $this->assertTrue($fs->copyFile($this->tmpFile, $this->tmpFile . 'Copy'));

        $this->assertFalse($fs->copyFile($this->tmpDir, $this->tmpDir . 'Copy'));
    }

    public function testUnlinkFile()
    {
        $fs = new Filesystem;

        $deleteMe = tempnam($this->tmpDir, 'deleteMe');

        $this->assertTrue($fs->unlinkFile($deleteMe));
    }
}
