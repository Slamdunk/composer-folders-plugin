<?php

declare(strict_types=1);

namespace Slam\Composer\Folders;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use InvalidArgumentException;

final class FoldersPlugin implements EventSubscriberInterface, PluginInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'createAndCleanFolders',
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public function createAndCleanFolders(): void
    {
        $this->io->write('> ' . __METHOD__);

        \clearstatcache();

        $realpath = \realpath($this->composer->getConfig()->getConfigSource()->getName());
        \assert(false !== $realpath);
        $rootDir      = \dirname($realpath);
        $extra        = $this->composer->getPackage()->getExtra();
        $foldersExtra = $extra['folders-plugin'];
        \assert(\is_array($foldersExtra));

        if (isset($foldersExtra['create']) && \is_array($foldersExtra['create'])) {
            foreach ($foldersExtra['create'] as $dir => $mode) {
                $path = $rootDir . \DIRECTORY_SEPARATOR . $dir;

                if ($this->isLink($path)) {
                    continue;
                }

                $this->assertPathEqualsRealpath(\dirname($path));

                $mode = \octdec($mode);
                \assert(\is_int($mode));
                if (! \is_dir($path)) {
                    \mkdir($path, $mode, true);
                }
                if (\fileowner($path) === \posix_getuid()) {
                    \chmod($path, $mode);
                }

                $this->io->write(\sprintf(
                    'Created folder (<comment>0%o > 0%o</comment>) <info>./%s</info>',
                    $mode,
                    \fileperms($path),
                    \substr($path, \strlen($rootDir) + 1)
                ));
            }
        }

        if (isset($foldersExtra['clean']) && \is_array($foldersExtra['clean'])) {
            foreach ($foldersExtra['clean'] as $dir => $glob) {
                $path = $rootDir . \DIRECTORY_SEPARATOR . $dir;

                if ($this->isLink($path)) {
                    continue;
                }

                $this->assertPathEqualsRealpath($path);
                if (\str_contains($glob, \DIRECTORY_SEPARATOR)) {
                    throw new InvalidArgumentException(\sprintf('No relative path allowed in glob: "%s" => "%s"', $dir, $glob));
                }

                $path .= \DIRECTORY_SEPARATOR . $glob;

                \shell_exec('rm --force --recursive ' . $path);

                $this->io->write(\sprintf('Cleaned folder <info>./%s</info>', \substr($path, \strlen($rootDir) + 1)));
            }
        }
    }

    private function isLink(string $path): bool
    {
        if (\is_link($path)) {
            $this->io->write(\sprintf(
                '<comment>Symbolic link</comment> untouched: <info>%s</info> -> <info>%s</info>',
                $path,
                \realpath($path)
            ));

            return true;
        }

        return false;
    }

    private function assertPathEqualsRealpath(string $path): void
    {
        if ($path !== \realpath($path)) {
            throw new InvalidArgumentException(\sprintf('No relative path allowed: "%s" !== "%s"', $path, \realpath($path)));
        }
    }
}
