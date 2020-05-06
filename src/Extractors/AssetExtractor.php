<?php


namespace DraftPhp\Extractors;


use Symfony\Component\DomCrawler\Crawler;

class AssetExtractor
{
    private $domCrawler;
    private $content;
    private $assets = [];
    private $filterXPaths = [
        '//img' => [
            'attribute' => 'src',
        ],
        "//*[contains(@style,'background-image')]" => [
            'attribute' => 'style',
            'pattern' => "[url\((\s)?[?=(',\")]([-\/.\w.]+)[?=(',\")]]"
        ],
        '//link' => [
            'attribute' => 'href'
        ]
    ];

    public function __construct(string $content = null)
    {
        $this->content = $content;
        $this->domCrawler = new Crawler($content);

        foreach ($this->filterXPaths as $xPath => $element) {
            foreach ($this->domCrawler->filterXPath($xPath)->getIterator() as $node) {
                if (isset($element['pattern'])) {
                    $this->addFromPattern($node->getAttribute($element['attribute']), $element['pattern']);
                    continue;
                }
                if (isset($element['attribute'])) {
                    $this->addAsset($node->getAttribute($element['attribute']));
                }
            }
        }
    }

    private function addFromPattern($content, $pattern)
    {
        preg_match($pattern, $content, $matches);
        $this->addAsset(end($matches));
    }

    private function addAsset(string $asset)
    {
        array_push($this->assets, $asset);
    }


    public function getAssets(): array
    {
        return array_unique($this->assets);
    }
}