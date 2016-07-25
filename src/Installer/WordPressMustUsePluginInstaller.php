<?php
/**
 * Install WordPress must-use plugins with Composer
 *
 * @author Eric King <eric.king@lonelyplanet.com>
 */

namespace LPLabs\Composer\Installer;

use RuntimeException;
use Composer\Installer\LibraryInstaller;
use Composer\Installers\WordPressInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class WordPressMustUsePluginInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $installed = parent::isInstalled($repo, $package);

        if ($installed) {
            foreach ($this->getEntryFileLocations($package) as $entryFile) {
                if (! file_exists($entryFile)) {
                    $installed = false;
                    break;
                }
            }
        }

        return $installed;
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        $this->installEntryFiles($package);
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->uninstallEntryFiles($initial);

        parent::update($repo, $initial, $target);

        $this->installEntryFiles($target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->uninstallEntryFiles($package);

        parent::uninstall($repo, $package);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installer = new WordPressInstaller($package, $this->composer, $this->io);

        return $installer->getInstallPath($package, 'wordpress');
    }

    /**
     * Install each must-use plugin entry file
     *
     * @param PackageInterface $package
     * @return void
     */
    protected function installEntryFiles(PackageInterface $package)
    {
        foreach ($this->getEntryFileLocations($package) as $src => $dest) {
            $copied = $this->filesystem->copyFile($src, $dest);

            $this->io->notice(
                sprintf(
                    '    <fg=default>Copying <fg=magenta>%1$s</> to <fg=magenta>%2$s</> -</> %3$s',
                    $src,
                    $dest,
                    $copied ? '<fg=green>OK</>' : '<fg=red>FAILED</>'
                )
            );

            if (! $copied) {
                throw new RuntimeException(sprintf('Cannot copy %s to %s', $src, $dest));
            }
        }
    }

    /**
     * Uninstall each must-use plugin entry file
     *
     * @param PackageInterface $package
     * @return void
     */
    protected function uninstallEntryFiles(PackageInterface $package)
    {
        foreach ($this->getEntryFileLocations($package) as $dest) {
            $unlinked = $this->filesystem->unlinkFile($dest);

            $this->io->notice(
                sprintf(
                    '    <fg=default>Removing <fg=magenta>%1$s</> -</> %2$s',
                    $dest,
                    $unlinked ? '<fg=green>OK</>' : '<fg=red>FAILED</>'
                )
            );

            if (! $unlinked) {
                throw new RuntimeException('Cannot unlink ' . $dest);
            }
        }
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

        return array_key_exists($key, $extra) ? $extra[ $key ] : $default;
    }

    /**
     * Get the file location source/destination of the must-use plugin entry point files
     *
     * @param PackageInterface $package
     * @return array
     */
    protected function getEntryFileLocations(PackageInterface $package)
    {
        return array_reduce(
            $this->getPackageEntryPoints($package),
            function ($locations, $entryPoint) {
                $locations[ $entryPoint ] = dirname(dirname($entryPoint)) . DIRECTORY_SEPARATOR . basename($entryPoint);

                return $locations;
            },
            []
        );
    }

    /**
     * Get the package entry points
     *
     * @param PackageInterface $package
     * @return array
     */
    protected function getPackageEntryPoints(PackageInterface $package)
    {
        $dir = $this->composer->getInstallationManager()->getInstallPath($package);
        $entry = $this->getPackageExtra($package, 'wordpress-muplugin-entry');
        $entryPoints = $entry ? (is_array($entry) ? $entry : [ $entry ]) : [];

        if (empty($entryPoints)) {
            $phpFiles = glob(rtrim($dir, '/') . '/*.php', GLOB_NOSORT);

            foreach ($phpFiles as $file) {
                $entryPoints[] = basename($file);
            }
        }

        foreach ($entryPoints as $index => $file) {
            $entryPoints[ $index ] = $dir . $file;
        }

        return array_filter($entryPoints, [ $this, 'looksLikePlugin' ]);
    }

    /**
     * Does the file look like a WordPress plugin?
     *
     * @param string $file
     * @return bool
     */
    protected function looksLikePlugin($file)
    {
        if (! $file || ! file_exists($file) || ! is_readable($file)) {
            return false;
        }

        $chunk = str_replace("\r", "\n", file_get_contents($file, false, null, 0, 8192));

        return preg_match('/^[ \t\/*#@]*Plugin Name:(.*)$/mi', $chunk, $match) && $match[1];
    }
}
