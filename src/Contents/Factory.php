<?php


namespace DraftPhp\Contents;


use DraftPhp\FileReader;
use DraftPhp\Utils\Str;
use React\Filesystem\FilesystemInterface;

class Factory
{

    public static function create(FilesystemInterface $filesystem, $filename)
    {
        $string = new Str($filename);

        if($string->endsWith('.html')) {
            return new Html(new FileReader($filesystem, $filename));
        }

        if($string->endsWith('.md')) {
            return new Md(new FileReader($filesystem, $filename));
        }
    }
}
