<?php


namespace DraftPhp;


use DraftPhp\Utils\Str;

class FolderParser
{
    private $folders = [];

    public function __construct(array $folders)
    {
        $this->folders = $folders;
    }

    public function parse(): array
    {
        usort($this->folders, function (string $a, string $b) {
            return substr_count($a, '/') < substr_count($b, '/');
        });
        $this->folders = array_map(function ($folder) {
            $string = new Str($folder);

            if ($string->endsWith('/index.html')) {
                return $string->replaceLastWith('/index.html', '');
            }

            if ($string->endsWith('/index.md')) {
                return $string->replaceLastWith('/index.md', '');
            }

            if ($string->endsWith('.md')) {
                return $string->replaceLastWith('.md', '');
            }

            if ($string->endsWith('.html')) {
                return $string->replaceLastWith('.html', '');
            }

            return (string) $string;

        }, $this->folders);

        $sortedArr = [$this->folders[0]];
        unset($this->folders[0]);

        while (count($this->folders) > 0) {
            foreach ($sortedArr as $sortedFolder) {
                $str = new Str($sortedFolder);
                foreach ($this->folders as $key => $folder) {
                    if ($str->startsWith($folder)) {
                        unset($this->folders[$key]);
                        continue;
                    }
                    $sortedArr[] = $folder;
                }
            }
        }

        return array_unique($sortedArr);
    }
}
