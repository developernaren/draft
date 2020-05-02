<?php


namespace DraftPhp;


use DraftPhp\Utils\Str;
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

        if(empty($this->config->getAssetsBaseFolder())) {
            return;
        }

        $images = [];
        await($this->lookForImages($images), $this->loop);

        $imageDirectories = [];

        foreach (array_unique($images) as $image) {
            $imageDirectories[] = $this->config->getBuildBaseFolder() . (new Str($image))->replaceAfterLast('/');
        }

        $folderParsers = new FolderParser($imageDirectories);

        foreach ($folderParsers->parse() as $folder) {
            $createDir = $this->filesystem->dir($folder)->createRecursive();
            await($createDir, $this->loop);
        }

        foreach ($images as $image) {
            $sourceImage = $this->config->getAssetsBaseFolder() .  $image;
            $targetImage = $this->config->getBuildBaseFolder() . $image;
            $source = $this->filesystem->file($sourceImage);
            $target = $this->filesystem->file($targetImage);
            $copy = $source->copy($target);
            await($copy, $this->loop);
        }

    }
    public function lookForImages(&$images)
    {
        return $this->filesystem->dir($this->config->getBuildBaseFolder())
            ->lsRecursive()
            ->then(function ($nodes) use (&$images) {
                foreach ($nodes as $node) {
                    if ($node instanceof File) {
                        $imageExtractor = $node->getContents()
                            ->then(function ($content) use (&$images, &$processedFileCount) {
                                $imageExtractor = new ImageExtractor($content);
                                return $imageExtractor->getImages();
                            });

                        $extractedImages = await($imageExtractor, $this->loop);
                        $images = array_merge($extractedImages, $images);
                    }
                }
            });

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
