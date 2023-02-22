<?php

declare(strict_types=1);

namespace SlamTest\Composer\Folders;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slam\Composer\Folders\FoldersPlugin;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversClass(FoldersPlugin::class)]
final class FoldersPluginTest extends TestCase
{
    private string $workingDir;
    private MockObject&IOInterface $io;
    private FoldersPlugin $plugin;

    protected function setUp(): void
    {
        $this->workingDir = __DIR__ . '/TmpFolder';
        $this->cleanWorkingDir();

        $this->io     = $this->createMock(IOInterface::class);
        $this->plugin = new FoldersPlugin();
    }

    protected function tearDown(): void
    {
        $this->cleanWorkingDir();
    }

    public function testHasSubscribedEvent(): void
    {
        self::assertNotEmpty(FoldersPlugin::getSubscribedEvents());

        $this->plugin->deactivate(new Composer(), $this->io);
        $this->plugin->uninstall(new Composer(), $this->io);
    }

    public function testCreateFolders(): void
    {
        $randomFolder = \uniqid('data_');
        $this->activatePluginFromJsonString(\sprintf(<<<'JSON'
{
    "extra": {
        "folders-plugin": {
            "create": {
                "data":     "0770",
                "data/%s":  "0500",
                "tmp":      "0777"
            }
        }
    }
}
JSON
            , $randomFolder));

        $this->plugin->createAndCleanFolders();

        self::assertDirectoryExists($this->workingDir . '/data');
        self::assertDirectoryIsWritable($this->workingDir . '/data');
        self::assertDirectoryExists($this->workingDir . '/data/' . $randomFolder);
        self::assertDirectoryIsNotWritable($this->workingDir . '/data/' . $randomFolder);
        self::assertDirectoryExists($this->workingDir . '/tmp');
    }

    public function testCleanFolders(): void
    {
        (new Filesystem())->mkdir($this->workingDir . '/tmp');
        (new Filesystem())->touch($fileToKeep = $this->workingDir . \uniqid('/tmp/to_keep_'));
        (new Filesystem())->touch($fileToRemove = $this->workingDir . \uniqid('/tmp/to_remove_'));

        $this->activatePluginFromJsonString(
            <<<'JSON'
{
    "extra": {
        "folders-plugin": {
            "clean": {
                "tmp": "to_remove_*"
            }
        }
    }
}
JSON
        );

        $this->plugin->createAndCleanFolders();

        self::assertFileExists($fileToKeep);
        self::assertFileDoesNotExist($fileToRemove);
    }

    public function testSkipLinks(): void
    {
        (new Filesystem())->mkdir($original = $this->workingDir . '/real');
        (new Filesystem())->symlink($original, $symlink = $this->workingDir . '/link');
        (new Filesystem())->touch($fileToKeep = $symlink . \uniqid('/to_remove_'));

        $this->activatePluginFromJsonString(\sprintf(<<<'JSON'
{
    "extra": {
        "folders-plugin": {
            "create": {
                "%1$s": "0500"
            },
            "clean": {
                "%1$s": "to_remove_*"
            }
        }
    }
}
JSON
            , \basename($symlink)));

        $this->plugin->createAndCleanFolders();

        self::assertDirectoryIsWritable($original);
        self::assertFileExists($fileToKeep);
    }

    public function testForbidNonRealpaths(): void
    {
        $this->activatePluginFromJsonString(
            <<<'JSON'
{
    "extra": {
        "folders-plugin": {
            "create": {
                "data/../tmp": "0700"
            }
        }
    }
}
JSON
        );

        $this->expectException(InvalidArgumentException::class);

        $this->plugin->createAndCleanFolders();
    }

    public function testForbidPathsInGlobClean(): void
    {
        (new Filesystem())->mkdir($this->workingDir . '/tmp');

        $this->activatePluginFromJsonString(
            <<<'JSON'
{
    "extra": {
        "folders-plugin": {
            "clean": {
                "tmp": "/*"
            }
        }
    }
}
JSON
        );

        $this->expectException(InvalidArgumentException::class);

        $this->plugin->createAndCleanFolders();
    }

    private function cleanWorkingDir(): void
    {
        $files = \glob($this->workingDir . '/*');
        self::assertIsArray($files);
        (new Filesystem())->remove($files);
    }

    private function activatePluginFromJsonString(string $json): void
    {
        $jsonFile = $this->workingDir . '/composer.json';
        (new Filesystem())->dumpFile($jsonFile, $json);

        $composer = (new Factory())->createComposer($this->io, $jsonFile, false, $this->workingDir);

        $this->plugin->activate($composer, $this->io);
    }
}
