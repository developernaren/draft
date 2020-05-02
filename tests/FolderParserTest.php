<?php

namespace Tests;

use DraftPhp\FolderParser;
use PHPUnit\Framework\TestCase;

class FolderParserTest extends TestCase
{

    public function testFolderParserWorks()
    {
        $folders = [
            'home/pages',
            'home/pages/blogs/',
            'home/pages/dates/posts',
            'home/this-is-the-long-ass-folder/posts/',
            'home/pages/dates/',
            'home/naren/dates/',
            'home/pages/naren/',
        ];

        $folderParser = new FolderParser($folders);
        $folders = $folderParser->parse();


        $this->assertContains('home/pages/blogs/', $folders);
        $this->assertContains('home/pages/dates/posts', $folders);
        $this->assertContains('home/this-is-the-long-ass-folder/posts/', $folders);
        $this->assertContains('home/pages/dates/', $folders);
        $this->assertContains('home/naren/dates/', $folders);
        $this->assertContains('home/pages/naren/', $folders);


    }
}
