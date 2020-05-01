<?php

namespace DraftPhp\Contents\Interfaces;

use React\Promise\PromiseInterface;

interface HasMetaData
{
    public function getMetaData(): PromiseInterface;
}
