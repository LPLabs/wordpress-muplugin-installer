<?php

namespace LPLabs\Composer\Tests;

use LPLabs\Composer\WPMUPluginInstaller;
use Composer\Config;
use Composer\Composer;
use Composer\TestCase;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;

class WPMUPluginInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var InstallationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $im;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var RootPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    protected function setUp()
    {
        parent::setUp();

        $this->io = $this->createMock('Composer\IO\IOInterface');
        $this->im = $this->createMock('Composer\Installer\InstallationManager');
        $this->config = $this->createMock('Composer\Config');
        $this->package = $this->createMock('Composer\Package\RootPackageInterface');

        $this->composer = new Composer();
        $this->composer->setConfig($this->config);
        $this->composer->setInstallationManager($this->im);
        $this->composer->setPackage($this->package);
    }

    public function testActivate()
    {
        $plugin = new WPMUPluginInstaller();
        $plugin->activate($this->composer, $this->io);

        $this->assertInstanceOf(Composer::class, $this->composer);
        $this->assertInstanceOf(IOInterface::class, $this->io);
        $this->assertInstanceOf(WPMUPluginInstaller::class, $plugin);
    }

    public function testEvents()
    {
        $plugin = new WPMUPluginInstaller();
        $plugin->activate($this->composer, $this->io);

        $events = array_keys($plugin->getSubscribedEvents());

        $eventDispatcher = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher
            ->expects($this->exactly(count($events)))
            ->method('dispatchScript');

        $this->composer->setEventDispatcher($eventDispatcher);
        $this->composer->getEventDispatcher()->addSubscriber($plugin);

        foreach ($events as $event) {
            $this->composer->getEventDispatcher()->dispatchScript($event);
        }
    }
}
