<?php

namespace LPLabs\Composer\Util;

use Composer\Util\Filesystem as ComposerFilesystem;

class Filesystem extends ComposerFilesystem
{
    public function exists($file)
    {
        return file_exists($file);
    }

    public function isFile($file)
    {
        return is_file($file);
    }

    public function isDir($file)
    {
        return is_dir($file);
    }

    public function isReadable($file)
    {
        return is_readable($file);
    }

    public function isWritable($file)
    {
        return is_writable($file);
    }

    public function canWrite($file)
    {
        return $this->isWritable($this->exists($file) ? $file : dirname($file));
    }

    public function copyFile($src, $dest)
    {
        return $this->isFile($src) && $this->isReadable($src) && $this->canWrite($dest) ? copy($src, $dest) : false;
    }

    public function unlinkFile($file)
    {
        return $this->isFile($file) ? $this->unlink($file) : false;
    }
}
