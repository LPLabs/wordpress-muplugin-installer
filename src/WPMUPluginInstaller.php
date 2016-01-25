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
            PackageEvents::PRE_PACKAGE_UNINSTALL => array('onPrePackageUninstall', 0),
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
    protected function getPackageExtra(PackageInterface $package, $key, $default = null)
    {
        $extra = $package->getExtra();

        return isset($extra[ $key ]) ? $extra[ $key ] : $default;
    }

    /**
     * Get the details about the must-use plugin entry point file
     *
     * @param PackageInterface $package
     * @return array
     */
    protected function getEntryFileDetails(PackageInterface $package)
    {
        $details = array();
        $entry = $this->getPackageExtra($package, static::EXTRA_KEY);
        $installPathSrc = $this->composer->getInstallationManager()->getInstallPath($package);
        $installPathDest = realpath($installPathSrc . '/../');

        if (isset($entry)) {
            if (! is_array($entry)) {
                $entry = array($entry);
            }
        } else {
            $entry = array();
            $files = glob($installPathSrc . '/*.php', GLOB_NOSORT);

            foreach ($files as &$file) {
                $entry[] = basename($file);
            }
        }

        foreach ($entry as &$file) {
            $src = realpath($installPathSrc . '/' . $file);
            if ($this->looksLikePlugin($src)) {
                $dest = $installPathDest . '/' . $file;

                $details[ $file ] = compact('src', 'dest');
            }
        }

        return $details;
    }

    /**
     * Install/uninstall the must-use plugin entry point file
     *
     * @param PackageInterface $package
     * @param bool $install If false it will uninstall
     * @throws CopyException
     * @throws UnlinkException
     * @return bool
     */
    protected function manageMustusePlugin(PackageInterface $package, $install = true)
    {
        if ($package->getType() !== static::PACKAGE_TYPE) {
            return false;
        }

        $entries = $this->getEntryFileDetails($package);

        foreach ($entries as $file => $entry) {
            if ($install) {
                if (! isset($entry['src'], $entry['dest']) || ! copy($entry['src'], $entry['dest'])) {
                    $this->io->writeError($file . ' not installed');

                    throw new CopyException(sprintf(
                        '%s not copied to %s',
                        $entry['src'],
                        $entry['dest']
                    ));
                }
            } else {
                if (! isset($entry['dest']) || ! unlink($entry['dest'])) {
                    $this->io->writeError($file . ' not uninstalled');

                    throw new UnlinkException(sprintf(
                        '%s not deleted',
                        $entry['dest']
                    ));
                }
            }
        }

        return true;
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

    /**
     * Does the file look like a WordPress plugin?
     *
     * @param string $file
     * @return bool
     */
    protected function looksLikePlugin($file)
    {
        if (! file_exists($file)) {
            return false;
        }

        $fp = fopen($file, 'r');
        $chunk = str_replace("\r", "\n", fread($fp, 8192));
        fclose($fp);

        return preg_match('/^[ \t\/*#@]*Plugin Name:(.*)$/mi', $chunk, $match) && $match[1];
    }
}
