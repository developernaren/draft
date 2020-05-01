<?php

namespace Tests\Unit\Utils;

use DraftPhp\Utils\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    private $testString = 'narendra chitrakar';

    public function testEndsWith()
    {
        $string = new Str($this->testString);

        $this->assertTrue($string->endsWith('chitrakar'));
        $this->assertTrue($string->endsWith('r'));
        $this->assertFalse($string->endsWith('arend'));


        $newString = new Str('n');
        $newString->startsWith('na');
        $newString->endsWith('na');
    }

    public function testStartsWith()
    {
        $string = new Str($this->testString);

        $this->assertTrue($string->startsWith('naren'));
        $this->assertTrue($string->startsWith('n'));
        $this->assertTrue($string->startsWith('narendra'));
        $this->assertFalse($string->startsWith('arend'));
    }

    public function testReplaceAllWith()
    {
        $string = new Str($this->testString);
        $this->assertSame($string->replaceAllWith('n', ''), 'aredra chitrakar');
    }


}
