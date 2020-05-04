<?php

namespace DraftPhp\Commands;

use DraftPhp\BuildFileResolver;
use DraftPhp\HtmlGenerator;
use DraftPhp\Watcher\FileChange;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use React\Http\Response;
use React\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DraftPhp\Config;
use Symfony\Component\Finder\Finder;
use Yosymfony\ResourceWatcher\Crc32ContentHash;
use Yosymfony\ResourceWatcher\ResourceWatcher;
use Yosymfony\ResourceWatcher\ResourceCachePhpFile;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;

class Watch extends Command
{
    protected static $defaultName = 'dev';
    private $message;
    private $config;
    private $filesystem;
    private $io;
    private $loop;
    private $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
    ];

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $this->io = $io = new SymfonyStyle($input, $output);
        $this->message = new FileChange($io, $this->config);;
        $this->loop = Factory::create();
        $filesystem = Filesystem::create($this->loop);

        $finder = new Finder();
        $finder->files()
            ->name('*.html')
            ->name('*.md')
            ->in([getBaseDir() . '/Mocks/pages']);

        $hashContent = new Crc32ContentHash();
        $resourceCache = new ResourceCachePhpFile(getBaseDir() . '/path-cache-file.php');
        $watcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
        $watcher->initialize();

        $this->loop->addPeriodicTimer(1, function () use ($watcher, $filesystem) {
            $result = $watcher->findChanges();
            $changedFiles = $result->getUpdatedFiles();
            foreach ($changedFiles as $file) {
                $generator = new HtmlGenerator($this->config, $filesystem, $file);
                $this->message->notifyFileChange($file);
                $generator->getHtml()->then(function ($content) use ($file) {
//                    $this->message->notifyFileBuilt($file, $content);
//                    $content .= '<script>' . file_get_contents(getBaseDir() . '/watch.js') . '</script>';
//                    $content .= '<input type="hidden" name="draft_filename" value="' . $file . '"/>';
                    $buildFile = (new BuildFileResolver($this->config, $file))->getName();

                    $this->io->text($content);
                    $this->io->text($buildFile);
                    $content .= '<script>' . file_get_contents(getBaseDir() . '/watch.js') . '</script>';
                    file_put_contents($buildFile, $content);
                    $this->io->text(sprintf('%s written', $buildFile));
//                    $file = $this->filesystem->file($buildFile);
//                    $writeFile = $file->putContents($content);
//                    await($writeFile, $this->loop);
                });
            }
        });

        $server = new Server(function (ServerRequestInterface $request) use ($filesystem, $io) {

            $filename = $this->config->getBuildBaseFolder() . '/' . $request->getUri()->getPath() . '/index.html';

            $io->block(sprintf('requesting %s', $filename));

            $file = $filesystem->file($filename);

            return
                $file->exists()
                    ->then(function () use ($file) {
                        return $file->getContents()
                            ->then(function ($content) {
                                return new Response(200, $this->headers, $content);
                            });
                    }, function () {
                        return new Response(404, array_merge($this->headers, ['Content-Type' => 'text/html']), 'Not Found!');
                    });
        });

        $socket = new \React\Socket\Server('127.0.0.1:8888', $this->loop);
        $server->listen($socket);

        $this->loop->run();

        return 0;
    }
}
