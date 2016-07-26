<?php

namespace LPLabs\Composer\Tests\Installer;

use InvalidArgumentException;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use LPLabs\Composer\Util\Filesystem;
use LPLabs\Composer\Installer\WordPressMustUsePluginInstaller;

class WordPressMustUsePluginInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    const PACKAGE_TYPE = 'wordpress-muplugin';

    /**
     * @var string
     */
    protected $packageType;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    public function setUp()
    {
        $this->config = new Config();

        $this->composer = new Composer();

        $this->composer->setConfig($this->config);

        $this->fs = $this->createMock(Filesystem::class);

        $this->io = $this->createMock(IOInterface::class);
    }

    public function testSupportedPackages()
    {
        $installer = new WordPressMustUsePluginInstaller($this->io, $this->composer, self::PACKAGE_TYPE, $this->fs);

        $package = new Package('test-plugin', '1.0.0', '1.0.0');

        $package->setType(self::PACKAGE_TYPE);

        $this->assertTrue($installer->supports($package->getType()));

        $package->setType('some-unsupported-package-type');

        $this->assertFalse($installer->supports($package->getType()));
    }

    public function testInstallPath()
    {
        $installer = new WordPressMustUsePluginInstaller($this->io, $this->composer, self::PACKAGE_TYPE, $this->fs);

        $package = new Package('test-plugin', '1.0.0', '1.0.0');

        $package->setType(self::PACKAGE_TYPE);

        $this->assertEquals(
            'wp-content/mu-plugins/test-plugin/',
            $installer->getInstallPath($package)
        );

        $this->setExpectedException(InvalidArgumentException::class);

        $package->setType('some-unsupported-package-type');

        $installer->getInstallPath($package);
    }
}
