<?php

namespace DraftPhp\Commands;

use DraftPhp\BuildFileResolver;
use DraftPhp\HtmlGenerator;
use DraftPhp\SiteGenerator;
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
        'Content-Type' => 'text/html',
    ];
    private $watcher;
    private $changedFiles = [];
    private $pathContentMap = [];

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
        $this->filesystem = Filesystem::create($this->loop);

        $finder = new Finder();
        $finder->files()
            ->name('*.html')
            ->name('*.md')
            ->in([getBaseDir() . '/Mocks/pages']);

        $hashContent = new Crc32ContentHash();
        $resourceCache = new ResourceCachePhpFile(getBaseDir() . '/path-cache-file.php');
        $this->watcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
        $this->watcher->initialize();

        $siteGenerator = new SiteGenerator($this->config, $this->filesystem, $this->loop);
        $siteGenerator->build();

        $this->loop->addPeriodicTimer(1, function () {
            $result = $this->watcher->findChanges();
            $this->changedFiles = $changedFiles = $result->getUpdatedFiles();
            foreach ($changedFiles as $file) {
                $generator = new HtmlGenerator($this->config, $this->filesystem, $file);
                $this->message->notifyFileChange($file);
                $generator->getHtml()->then(function ($content) use ($file) {
                    $buildFile = (new BuildFileResolver($this->config, $file))->getName();
                    $this->message->notifyFileChange($file);
                    file_put_contents($buildFile, $content);
                    $this->io->text(sprintf('%s build', $buildFile));
                });
            }
        });

        $server = new Server(function (ServerRequestInterface $request) use (&$firstBuild) {

            $path = $request->getUri()->getPath();
            $filename = $this->config->getBuildBaseFolder() . '/' . $path . '/index.html';
            $filename = str_replace('///', '/', $filename);
            $filename = str_replace('//', '/', $filename);

            $file = $this->filesystem->file($filename);
            $script = $this->getWatchJs($request->getUri()->__toString());

            return
                $file->exists()
                    ->then(function () use ($file, $filename, $script, $path) {
                        return $file->getContents()
                            ->then(function ($content) use ($filename, $script, $path) {
                                $content .= $script;
                                $hash = $this->getContentHash($content);

                                if (isset($this->pathContentMap[$path]) && $this->pathContentMap[$path] === $hash) {
                                    return new Response(204, $this->headers);
                                }

                                $this->pathContentMap[$path] = $hash;
                                return new Response(200, $this->headers, $content);

                            });
                    }, function () {
                        return new Response(404, $this->headers, 'Not Found!');
                    });
        });

        exec('open http://localhost:8888');

        $socket = new \React\Socket\Server('127.0.0.1:8888', $this->loop);
        $server->listen($socket);

        $this->loop->run();

        return 0;
    }

    private function getWatchJs($path): string
    {
        $js = <<<HTML
<script>
function watch() {
    fetch('${path}',{
        mode: 'cors',
        headers :{
            'Content-Type' : 'text/plain',
        },
        cache : 'no-cache',
    })
        .then(function (response) {
            return response.text();
        }).then(function (html) {
            if(html){
                const page = document.getElementsByTagName('html')[0]
                page.innerHTML = html;
            }

    })
}
setInterval(watch, 1000);
</script>
HTML;
        return $js;
    }

    private function getContentHash($content): string
    {
        return hash('crc32', $content);
    }
}
