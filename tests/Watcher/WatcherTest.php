<?php


namespace Tests\Watcher;


use DraftPhp\Watcher\Watcher;
use Tests\AbstractTestCase;

class WatcherTest extends AbstractTestCase
{

    public function testWatcher()
    {
        (new Watcher())->watch();
    }
}
