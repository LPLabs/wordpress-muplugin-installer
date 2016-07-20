<?php

namespace LPLabs\Composer\Tests;

use Composer\Config;
use Composer\Composer;
use Composer\IO\ConsoleIO;
use LPLabs\Composer\WordPressMustUsePlugins;

class WordPressMustUsePluginsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var ConsoleIO|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var InstallationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $installationManager;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    protected function setUp()
    {
        $this->io = $this->createMock('Composer\IO\ConsoleIO');
        $this->installationManager = $this->createMock('Composer\Installer\InstallationManager');
        $this->config = $this->createMock('Composer\Config');

        $this->composer = new Composer();
        $this->composer->setConfig($this->config);
        $this->composer->setInstallationManager($this->installationManager);
    }

    public function testActivate()
    {
        $this->installationManager->expects($this->once())->method('addInstaller');
        $this->io->expects($this->once())->method('notice');

        $plugin = new WordPressMustUsePlugins();
        $plugin->activate($this->composer, $this->io);
    }
}
