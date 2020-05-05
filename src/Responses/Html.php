<?php


namespace DraftPhp\Responses;


use DraftPhp\Commands\Watch;
use DraftPhp\Config;
use React\Filesystem\FilesystemInterface;
use React\Http\Response;
use React\Promise\PromiseInterface;

class Html extends AbstractResponse
{

    protected $config;
    protected $path;
    protected $filename;
    protected $fullPath;
    protected $filesystem;
    protected $pathContentMap = [];


    public function __construct(Config $config, FilesystemInterface $filesystem, string $path, string $fullPath)
    {
        parent::__construct($config, $filesystem, $path, $fullPath);

        $this->headers = array_merge($this->headers, [
            'Content-Type' => 'text/html',
        ]);

        $filename = $this->config->getBuildBaseFolder() . '/' . $path . '/index.html';
        $this->filename = $this->removeMultipleSlashes($filename);
        $this->fullPath = $fullPath;
        $this->filesystem = $filesystem;
    }


    public function toResponse(): PromiseInterface
    {

        $file = $this->filesystem->file($this->filename);
        $script = $this->getWatchJs($this->fullPath);

        return
            $file->exists()
                ->then(function () use ($file, $script) {
                    return $file->getContents()
                        ->then(function ($content) use ($script) {
                            $content .= $script;
                            $hash = $this->getContentHash($content);

                            if (isset(Watch::$contentHashMap[$this->path]) && Watch::$contentHashMap[$this->path] === $hash) {
                                return new Response(204, $this->headers);
                            }

                            Watch::$contentHashMap[$this->path] = $hash;
                            return new Response(200, $this->headers, $content);

                        }, $this->responseNotFound());
                });
    }

    private function getContentHash($content): string
    {
        return hash('crc32', $content);
    }

    protected function getWatchJs($path): string
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
setInterval(watch, 2000);
</script>
HTML;
        return $js;
    }
}
