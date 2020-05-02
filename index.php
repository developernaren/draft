<?php

require_once 'vendor/autoload.php';

use DraftPhp\Config;
use Symfony\Component\Finder\Finder;
use Yosymfony\ResourceWatcher\Crc32ContentHash;
use Yosymfony\ResourceWatcher\ResourceWatcher;
use Yosymfony\ResourceWatcher\ResourceCachePhpFile;

$myChat =  new \DraftPhp\MyChat();

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
           $myChat
        )
    ),
    8080
);

$loop = $server->loop;
$filesystem = \React\Filesystem\Filesystem::create($loop);

$configData = include getBaseDir() . '/config.php';
$config = new Config($configData);

$finder = new Finder();
$finder->files()
    ->name('*.html')
    ->name('*.md')
    ->in([getBaseDir() . '/Mocks/pages', getBaseDir() . '/Mocks/layouts']);

$hashContent = new Crc32ContentHash();
$resourceCache = new ResourceCachePhpFile(getBaseDir() .'/path-cache-file.php');
$watcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
$watcher->initialize();

$loop->addPeriodicTimer(1, function() use ($watcher, $filesystem, $loop, $config, &$myChat){
    $result = $watcher->findChanges();
    $changedFiles = $result->getUpdatedFiles();
    foreach ($changedFiles as $file) {
        $generator = new \DraftPhp\HtmlGenerator($config, $filesystem, $file);
        $generator->getHtml()->then(function ($content) use(&$myChat, $file){
            $myChat->sendMessageToClient($content);
        });
    }
});


$httpServer = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) {
    return new \React\Http\Response(
        200,
        array(
            'Content-Type' => 'text/plain'
        ),
        "Hello World!\n"
    );
});

exec('open file://' . $config->getBuildBaseFolder() .'/index.html');

$server->run();





