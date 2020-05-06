<?php

namespace Tests\Extractors;

use DraftPhp\Extractors\AssetExtractor;
use Tests\AbstractTestCase;
use function Clue\React\Block\await;

class AssetExtractorTest extends AbstractTestCase
{
    public function testAssetsAreCorrectlyExtractedFromImgTags()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $assetExtractor = new AssetExtractor($content);
                $this->assertContains('img1.jpg', $assetExtractor->getAssets());
                $this->assertContains('img2.jpg', $assetExtractor->getAssets());
                $this->assertContains('figure.png', $assetExtractor->getAssets());
            });

        await($test, $this->loop);
    }

    public function testOnlyUniqueAssetsAreFetched()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $assetExtractor = new AssetExtractor($content);
                $this->assertCount(9, $assetExtractor->getAssets());
            });

        await($test, $this->loop);
    }

    public function testBackgroundAssetsAreFetched()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $assetExtractor = new AssetExtractor($content);
                $this->assertContains('narendra.bmp', $assetExtractor->getAssets());
                $this->assertContains('saru.png', $assetExtractor->getAssets());
                $this->assertContains('images/hel-lo/saru.png', $assetExtractor->getAssets());

            });

        await($test, $this->loop);
    }

    public function testCssLinksAreFetched()
    {
        $test = $this->filesystem->file($this->baseDir . '/Mocks/html-with-images.html')
            ->getContents()
            ->then(function ($content) {
                $assetExtractor = new AssetExtractor($content);
                $this->assertContains('/style.css', $assetExtractor->getAssets());
                $this->assertContains('/base/styles/big.style.css', $assetExtractor->getAssets());

            });

        await($test, $this->loop);
    }
}