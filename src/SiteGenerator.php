<?php


namespace DraftPhp;


use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Node\File;
use function Clue\React\Block\await;

class SiteGenerator
{
    private $config;
    private $filesystem;
    private $loop;

    public function __construct(Config $config, FilesystemInterface $filesystem, LoopInterface $loop)
    {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->loop = $loop;
    }

    public function build()
    {
        await($this->removeBuildFolder(), $this->loop);
        await($this->createFoldersForBuildFiles(), $this->loop);
        await($this->createStaticPages(), $this->loop);
    }

    private function createStaticPages()
    {
        return $this->filesystem->dir($this->config->getPageBaseFolder())
            ->lsRecursive()
            ->then(function ($nodes) {
                foreach ($nodes as $node) {
                    if ($node instanceof File) {
                        $filename = (string)$node;
                        $htmlGenerator = new HtmlGenerator($this->config, $this->filesystem, $filename);
                        $promise = $htmlGenerator->getHtml()->then(function ($content) use ($filename) {
                            $buildFilenameResolver = new BuildFileResolver($this->config, $filename);
                            return $this->filesystem->file($buildFilenameResolver->getName())
                                ->putContents($content);
                        });
                        await($promise, $this->loop);
                    }
                }
            });
    }

    private function createFoldersForBuildFiles()
    {
        $folderCreator = new FolderCreator($this->filesystem, $this->config);
        return $folderCreator->getFoldersToCreate()
            ->then(function ($folders) {
                foreach ($folders as $folder) {
                    try {
                        await($this->filesystem->dir($folder)->createRecursive('rwxrwx---'), $this->loop);
                    } catch (\Exception $exception) {
                        echo $exception->getMessage() . ' ' . $exception->getFile();
                    }
                }
            });


    }

    private function removeBuildFolder()
    {
        return $this->filesystem->dir($this->config->getBuildBaseFolder())
            ->stat()
            ->then(function () {
                return $this->filesystem->dir($this->config->getBuildBaseFolder())->removeRecursive();
            }, function (\Exception $exception) {

            });
    }

}
