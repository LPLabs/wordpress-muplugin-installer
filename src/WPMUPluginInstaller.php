<?php
/**
 * WordPress must-use plugin installer
 *
 * @author Eric King <eric.king@lonelyplanet.com>
 */

namespace LPLabs\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;

class WPMUPluginInstaller implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var string
     */
    const PACKAGE_TYPE = 'wordpress-muplugin';

    /**
     * @var string
     */
    const EXTRA_KEY = 'wordpress-muplugin-entry';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Run on activation
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    
    /**
     * Setup events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            PackageEvents::POST_PACKAGE_INSTALL  => array('onPostPackageInstall',  0),
            PackageEvents::PRE_PACKAGE_UPDATE    => array('onPrePackageUpdate',    0),
            PackageEvents::POST_PACKAGE_UPDATE   => array('onPostPackageUpdate',   0),
            PackageEvents::PRE_PACKAGE_UNINSTALL => array('onPrePackageUninstall', 0)
        );
    }

    /**
     * Run after a package has been installed
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $this->installMustusePlugin($event->getOperation()->getPackage());
    }

    /**
     * Run before a package has been updated
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPrePackageUpdate(PackageEvent $event)
    {
        $this->uninstallMustusePlugin($event->getOperation()->getInitialPackage());
    }

    /**
     * Run after a package has been updated
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageUpdate(PackageEvent $event)
    {
        $this->installMustusePlugin($event->getOperation()->getTargetPackage());
    }

    /**
     * Run before a package has been uninstalled
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPrePackageUninstall(PackageEvent $event)
    {
        $this->uninstallMustusePlugin($event->getOperation()->getPackage());
    }

    /**
     * Get an item from the package extra array
     *
     * @param PackageInterface $package
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getPackageExtra(PackageInterface $package, $key, $default = '')
    {
        $extra = $package->getExtra();

        return isset($extra[ $key ]) ? $extra[ $key ] : $default;
    }

    /**
     * Get the details about the must-use plugin entry point file
     *
     * @param PackageInterface $package
     * @return false|array
     */
    protected function getEntryFileDetails(PackageInterface $package)
    {
        $entry = $this->getPackageExtra($package, self::EXTRA_KEY);

        if (empty($entry)) {
            return false;
        }

        $installPathSource = $this->composer->getInstallationManager()->getInstallPath($package);

        $src = realpath($installPathSource . '/' . $entry);

        $installPathDestination = realpath($installPathSource . '/../');

        $dest = $installPathDestination !== false ? $installPathDestination . '/' . $entry : false;

        return compact('entry', 'src', 'dest');
    }

    /**
     * Install/uninstall the must-use plugin entry point file
     *
     * @param PackageInterface $package
     * @param bool $install If false it will uninstall
     * @return bool
     */
    protected function manageMustusePlugin(PackageInterface $package, $install = true)
    {
        if ($package->getType() !== self::PACKAGE_TYPE) {
            return false;
        }

        $entry = $this->getEntryFileDetails($package);

        if ($entry !== false) {
            if ($entry['src'] !== false && $entry['dest'] !== false) {
                if ($install) {
                    copy($entry['src'], $entry['dest']);
                } else {
                    unlink($entry['dest']);
                }

                $exists = file_exists($entry['dest']);
                
                return $install ? $exists : ! $exists;
            } else {
                if ($entry['src'] === false) {
                    $this->io->writeError('plugin entry file source was not found');
                }

                if ($entry['dest'] === false) {
                    $this->io->writeError('plugin entry file destination was not found');
                }
            }
        }

        return false;
    }

    /**
     * Copy the must-use plugin entry point file to the mu-plugins directory
     *
     * @param PackageInterface $package
     * @return bool
     */
    protected function installMustusePlugin(PackageInterface $package)
    {
        return $this->manageMustusePlugin($package, true);
    }

    /**
     * Remove the must-use plugin entry point file to the mu-plugins directory
     *
     * @param PackageInterface $package
     * @return bool
     */
    protected function uninstallMustusePlugin(PackageInterface $package)
    {
        return $this->manageMustusePlugin($package, false);
    }
}
