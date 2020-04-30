<?php

namespace DraftPhp\Contents;


use DraftPhp\Contents\Interfaces\HasMetaData;
use DraftPhp\FileReader;
use React\Promise\PromiseInterface;

class Html extends AbstractContentFile implements HasMetaData
{
    protected $fileReader;

}
