<?php


namespace DraftPhp;


use DraftPhp\Extractors\AssetExtractor;
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
    private $io;

    public function __construct(Config $config, FilesystemInterface $filesystem, LoopInterface $loop, $io)
    {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->loop = $loop;
        $this->io = $io;
    }

    public function build()
    {
        $this->io->title('The site creating has begun');

        $this->io->section('removing build folder for fresh start');
        await($this->removeBuildFolder(), $this->loop);
        $this->io->success('Folder Removed!');

        $folders = [];
        $this->io->title('Creating Folders');
        await($this->createFoldersForBuildFiles($folders), $this->loop);
        $this->io->listing($folders);
        $this->io->success('Folders Created!');


        $pages = [];
        $this->io->section('creating static pages');
        await($this->createStaticPages($pages), $this->loop);
        $this->io->listing($pages);
        $this->io->success('Pages Created!');

        if (empty($this->config->getAssetsBaseFolder())) {
            return;
        }

        $this->io->section('Scanning pages for used assets');
        $images = [];
        await($this->lookForImages($images), $this->loop);
        if (empty($images)) {
            return;
        }

        $images = array_unique($images);
        $this->io->listing($images);

        $imageDirectories = [];

        $this->io->section('Processing assets');
        foreach ( $images as $key => $image) {

            if(!(new Str($image))->startsWith('/')) {
                unset($images[$key]);
                $this->io->warning(sprintf('asset %s does not seem to start with /. This breaks the asset path. Please fix', $image ));
                continue;
            }

            $imageDirectory = $this->config->getBuildBaseFolder() . (new Str($image))->removeAllAfterLast('/');
            if ($this->config->getBuildBaseFolder() !== $imageDirectory) {
                $imageDirectories[] =$imageDirectory;
            }

        }

        $this->io->section('Creating Folders');
        $this->io->listing(array_unique($imageDirectories));

        $folderParsers = new FolderParser(array_unique($imageDirectories));

        $this->io->section('Creating necessary folders for all assets');
        foreach ($folderParsers->parse() as $folder) {

            $createDir = $this->filesystem->dir($folder)->createRecursive();
            await($createDir, $this->loop);
        }
        $this->io->success('');

        $this->io->section('Copying assets to right folders');
        $this->io->listing($images);
        foreach ($images as $image) {
            $sourceImage = $this->config->getAssetsBaseFolder() . $image;
            $targetImage = $this->config->getBuildBaseFolder() . $image;
            $source = $this->filesystem->file($sourceImage);
            $target = $this->filesystem->file($targetImage);
            $copy = $source->copy($target);
            await($copy, $this->loop);
        }
        $this->io->success('Done! Site successfully generated!');

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
                                $imageExtractor = new AssetExtractor($content);
                                return $imageExtractor->getAssets();
                            });

                        $extractedImages = await($imageExtractor, $this->loop);
                        $images = array_merge($extractedImages, $images);
                    }
                }
            });

    }

    private function createStaticPages(&$pages)
    {
        return $this->filesystem->dir($this->config->getPageBaseFolder())
            ->lsRecursive()
            ->then(function ($nodes) use(&$pages) {
                foreach ($nodes as $node) {
                    if ($node instanceof File) {
                        $filename = (string)$node;
                        $htmlGenerator = new HtmlGenerator($this->config, $this->filesystem, $filename);
                        $promise = $htmlGenerator->getHtml()->then(function ($content) use ($filename, &$pages) {
                            $buildFilenameResolver = new BuildFileResolver($this->config, $filename);
                            $buildFilename = $buildFilenameResolver->getName();
                            array_push($pages, $buildFilename);
                            return $this->filesystem->file($buildFilename)
                                ->putContents($content);
                        });
                        await($promise, $this->loop);
                    }
                }
            });
    }

    private function createFoldersForBuildFiles(&$builtFolder)
    {
        $folderCreator = new FolderCreator($this->filesystem, $this->config);
        return $folderCreator->getFoldersToCreate()
            ->then(function ($folders) use (&$builtFolder){
                foreach ($folders as $folder) {
                    try {
                        await($this->filesystem->dir($folder)->createRecursive('rwxrwx---'), $this->loop);
                        array_push($builtFolder, $folder);
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
