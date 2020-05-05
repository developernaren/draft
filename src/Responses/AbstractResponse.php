<?php


namespace DraftPhp\Responses;

use DraftPhp\Config;
use React\Filesystem\FilesystemInterface;
use React\Http\Response;
use React\Promise\PromiseInterface;

abstract class AbstractResponse
{
    protected $config;
    protected $path;
    protected $filename;
    protected $fullPath;
    protected $filesystem;
    protected $pathContentMap = [];

    protected $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
    ];

    public function __construct(Config $config, FilesystemInterface $filesystem, string $path, string $fullPath)
    {
        $this->config = $config;
        $this->path = $path;
    }

    public abstract function toResponse(): PromiseInterface;

    public function responseNotFound()
    {
        return function () {
            return new Response(404, $this->headers, 'Not Found!');
        };
    }

}
