<?php


namespace Tests;


use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;

class AbstractTestCase extends TestCase
{

    protected $filesystem;
    protected $loop;
    protected $baseDir;

    public function setUp(): void
    {
        $this->loop = Factory::create();
        $this->filesystem = Filesystem::create($this->loop);
        $this->baseDir = getBaseDir();
    }

}
