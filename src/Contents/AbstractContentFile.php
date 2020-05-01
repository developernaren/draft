<?php

namespace DraftPhp\Contents;

use DraftPhp\FileReader;
use React\Promise\PromiseInterface;

class AbstractContentFile
{
    protected $fileReader;

    public function __construct(FileReader $fileReader)
    {
        $this->fileReader = $fileReader;
    }

    public function getContent(): PromiseInterface
    {
        return $this->fileReader->getContent();
    }

    public function getMetaData(): PromiseInterface
    {
        return $this->getContent()
            ->then(function ($content) {
            return new MetaParser($this->getMeta($content), $this->getBody($content));
        });
    }

    public function getFileReader(): FileReader
    {
        return $this->fileReader;
    }

    protected function getBody($content)
    {
        $endOfMeta = strpos($content, '</draft>');
        return substr($content, $endOfMeta + 8);
    }

    protected function getMeta($content)
    {
        $endOfMeta = strpos($content, '</draft>');
        return substr($content, 7, ($endOfMeta - 8));
    }
}
