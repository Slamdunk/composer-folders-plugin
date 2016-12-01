<?php

namespace Slam\Composer\Folders;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

final class FoldersPlugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;

    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => 'createAndCleanFolders',
        );
    }

    public function createAndCleanFolders()
    {
        $this->io->write('> ' . __METHOD__);

        clearstatcache();

        $rootDir = dirname(realpath($this->composer->getConfig()->getConfigSource()->getName()));
        $extra = $this->composer->getPackage()->getExtra();
        $foldersExtra = $extra['folders-plugin'];

        foreach ($foldersExtra['create'] as $dir => $mode) {
            $path = $rootDir . DIRECTORY_SEPARATOR . $dir;

            if ($this->skipPath($path)) {
                continue;
            }

            $this->checkPath(dirname($path));

            $mode = octdec($mode);
            if (! is_dir($path)) {
                mkdir($path, $mode, true);
            }
            if (fileowner($path) === posix_getuid()) {
                chmod($path, $mode);
            }

            $this->io->write(sprintf('Created folder (<comment>0%o > 0%o</comment>) <info>./%s</info>', $mode, fileperms($path), substr($path, strlen($rootDir) + 1)));
        }

        foreach ($foldersExtra['clean'] as $dir => $glob) {
            $path = $rootDir . DIRECTORY_SEPARATOR . $dir;

            if ($this->skipPath($path)) {
                continue;
            }

            $this->checkPath($path);
            if (strpos($glob, DIRECTORY_SEPARATOR) !== false) {
                throw new \InvalidArgumentException(sprintf('No relative path allowed in glob: "%s" => "%s"', $dir, $glob));
            }

            $path .= DIRECTORY_SEPARATOR . $glob;

            shell_exec('rm --force --recursive ' . $path);

            $this->io->write(sprintf('Cleaned folder <info>./%s</info>', substr($path, strlen($rootDir) + 1)));
        }
    }

    private function skipPath($path)
    {
        if (is_link($path)) {
            $this->io->write(sprintf('<comment>Symbolic link</comment> untouched: <info>%s</info> -> <info>%s</info>', $path, realpath($path)));

            return true;
        }

        return false;
    }

    private function checkPath($path)
    {
        if ($path !== realpath($path)) {
            throw new \InvalidArgumentException(sprintf('No relative path allowed: "%s" !== "%s"', $path, realpath($path)));
        }
    }
}
