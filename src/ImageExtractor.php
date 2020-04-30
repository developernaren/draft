<?php

namespace DraftPhp;

use DraftPhp\Utils\Str;
use Symfony\Component\DomCrawler\Crawler;

class ImageExtractor
{

    private $content;
    private $domCrawler;
    private $images = [];

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->domCrawler = new Crawler($content);

        foreach ($this->domCrawler->filterXPath('//img')->getIterator() as $image) {
            $this->addImage($image->getAttribute('src'));
        }

        foreach ($this->domCrawler->filterXPath("//*[contains(@style,'background-image')]")->getIterator() as $node) {
            $this->getImageFromBackgroundImage($node);
        }

    }

    private function getImageFromBackgroundImage($node)
    {

        preg_match("[url\((\s)?[?=(',\")]([-\/.\w.]+)[?=(',\")]]", $node->getAttribute('style'), $matches);

        $this->addImage(end($matches));
    }

    public function addImage(string $image)
    {
        $string = new Str($image);
        if (!in_array($image, $this->images) && !$string->startsWith('http')) {
            array_push($this->images, trim($image));
        }
    }

    public function getImages(): array
    {
        return $this->images;
    }

}
