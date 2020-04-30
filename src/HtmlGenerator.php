<?php


namespace DraftPhp;


use DraftPhp\Contents\Factory;
use DraftPhp\Contents\Html;
use DraftPhp\Contents\MetaParser;
use React\Filesystem\FilesystemInterface;
use React\Promise\Deferred;
use Symfony\Component\DomCrawler\Crawler;
use function Clue\React\Block\await;

class HtmlGenerator
{

    private $filesystem;
    private $filename;
    private $config;

    public function __construct(Config $config, FilesystemInterface $filesystem, string $filename)
    {
        $this->filesystem = $filesystem;
        $this->filename = $filename;
        $this->config = $config;
    }

    public function getHtml()
    {
        $deferred = new Deferred();

        $content = Factory::create($this->filesystem, $this->config->getPageBaseFolder() . $this->filename);
        $content->getMetaData()
            ->then(function (MetaParser $parser) use (&$deferred) {
                $layoutFile = $this->config->getLayoutBaseFolder() . $parser->getLayout();
                $this->filesystem->getContents($layoutFile)
                    ->then(function ($layoutContent) use ($parser, &$deferred) {
                        $pageBuilder = new PageBuilder($parser, $layoutContent);
                        $deferred->resolve($pageBuilder->toHtml());
                    });
            });

        return $deferred->promise();
    }
}
