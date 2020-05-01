<?php

namespace DraftPhp;

class Config
{
    private $options = [
        'pages_dir' => '',
        'layout_dir' => '',
        'build_dir' => '',
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function getLayoutBaseFolder()
    {
        return $this->options['layout_dir'];
    }

    public function getPageBaseFolder()
    {
        return $this->options['pages_dir'];
    }

    public function getBuildBaseFolder()
    {
        return $this->options['build_dir'];
    }
}
