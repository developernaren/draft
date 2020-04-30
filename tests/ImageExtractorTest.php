<?php

namespace Tests;

use DraftPhp\ImageExtractor;
use function Clue\React\Block\await;

class ImageExtractorTest extends AbstractTestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testImagesAreCorrectlyExtractedFromImgTags()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $imageExtractor = new ImageExtractor($content);
                $this->assertContains('img1.jpg', $imageExtractor->getImages());
                $this->assertContains('img2.jpg', $imageExtractor->getImages());
                $this->assertContains('figure.png', $imageExtractor->getImages());
            });

        await($test, $this->loop);
    }

    public function testOnlyUniqueImagesAreFetched()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $imageExtractor = new ImageExtractor($content);
                $this->assertCount(7, $imageExtractor->getImages());
            });

        await($test, $this->loop);
    }

    public function testBackgroundImagesAreFetched()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $imageExtractor = new ImageExtractor($content);
                $this->assertContains('narendra.bmp', $imageExtractor->getImages());
                $this->assertContains('saru.png', $imageExtractor->getImages());
                $this->assertContains('images/hel-lo/saru.png', $imageExtractor->getImages());

            });

        await($test, $this->loop);
    }


}
