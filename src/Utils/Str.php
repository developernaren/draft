<?php

namespace DraftPhp\Utils;

class Str
{
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function endsWith(string $string)
    {
        if (strlen($this) < strlen($string)) {
            return false;
        }

        $position = (int)('-' . strlen($string));

        return (strlen($this) - strlen($string)) === strpos($this, $string, $position);
    }

    public function startsWith(string $string)
    {
        if (strlen($this) < strlen($string)) {
            return false;
        }

        return strpos($this, $string) === 0;
    }

    public function replaceAllWith(string $search, string $replace)
    {
        return str_replace($search, $replace, $this);
    }

    public function __toString()
    {
        return $this->string;
    }

}
