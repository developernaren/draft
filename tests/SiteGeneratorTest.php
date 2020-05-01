<?php

namespace Tests;

use DraftPhp\Config;
use DraftPhp\SiteGenerator;
use function Clue\React\Block\await;

class SiteGeneratorTest extends AbstractTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->deleteBuildFolder();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->deleteBuildFolder();
    }

    public function testSiteGeneration()
    {
        $siteGenerator = new SiteGenerator($this->config, $this->filesystem, $this->loop);
        $siteGenerator->build();
        $this->assertTrue(file_exists($this->baseDir . '/Mocks/build/non-html/index.html'));
        $this->assertTrue(file_exists($this->baseDir . '/Mocks/build/index.html'));
        $this->assertTrue(file_exists($this->baseDir . '/Mocks/build/blogs/index.html'));
        $this->assertTrue(file_exists($this->baseDir . '/Mocks/build/blogs/2020/index.html'));
    }
}
