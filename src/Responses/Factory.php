<?php

namespace DraftPhp\Responses;

use DraftPhp\Config;
use DraftPhp\Utils\Str;
use React\Filesystem\FilesystemInterface;

class Factory
{

    private static $imageFileExtensions = [
        'jpg',
        'jpeg',
        'png',
        'ico',
        'svg',
    ];


    public static function create(Config $config, FilesystemInterface $filesystem, string $path, string $fullPath)
    {
        if (self::isImage($path) || self::isCss($path)) {
            return new Asset($config, $filesystem, $path, $fullPath);
        }

        return new Html($config, $filesystem, $path, $fullPath);
    }

    private static function isImage($path)
    {
        return (new Str(strtolower($path)))->endsWithAny(self::$imageFileExtensions);
    }

    public static function isCss($path)
    {
        return (new Str($path))->endsWith('css');
    }
}
