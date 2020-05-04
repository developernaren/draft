<?php

namespace DraftPhp\Responses;

use DraftPhp\Config;
use DraftPhp\Utils\Str;
use React\Filesystem\FilesystemInterface;
use React\Http\Response;
use React\Promise\PromiseInterface;

class Image extends AbstractResponse
{
    private $imageExtension;

    public function __construct(Config $config, FilesystemInterface $filesystem, string $path, string $fullPath)
    {
        parent::__construct($config, $filesystem, $path, $fullPath);
        $filename = $this->config->getBuildBaseFolder() . '/' . $path;
        $filename = str_replace('///', '/', $filename);
        $this->filename = str_replace('//', '/', $filename);
        $this->fullPath = $fullPath;
        $this->filesystem = $filesystem;
        $this->imageExtension = (new Str($path))->getAfterLast('.');

    }

    public function toResponse(): PromiseInterface
    {
        $file = $this->filesystem->file($this->filename);

        return $file->exists()
            ->then(function () use ($file) {
                return $file->getContents()
                    ->then(function ($content) {
                        return new Response(200, array_merge($this->headers, $this->getMimeTypeHeader($this->imageExtension)), $content);
                    });
            }, $this->responseNotFound());
    }

    public final function getMimeTypeHeader(string $imageType): array
    {
        $imageMimeTypes = [
            'png' => ['Content-Type' => 'image/png'],
            'jpeg' => ['Content-Type' => 'image/jpeg'],
            'jpg' => ['Content-Type' => 'image/jpg'],
        ];

        return $imageMimeTypes[$imageType];
    }
}
