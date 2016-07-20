<?php
/**
 * Install WordPress must-use plugins with Composer
 *
 * @author Eric King <eric.king@lonelyplanet.com>
 */

namespace LPLabs\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use LPLabs\Composer\Installer\WordPressMustUsePluginInstaller;

class WordPressMustUsePlugins implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $composer->getInstallationManager()->addInstaller(
            new WordPressMustUsePluginInstaller($io, $composer)
        );

        $io->notice(sprintf('<fg=magenta>Composer plugin activated:</> <fg=default>%s</>', self::class));
    }
}
