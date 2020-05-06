<?php


namespace DraftPhp\Commands;


use Symfony\Component\Console\Style\SymfonyStyle;

class DraftStyle extends SymfonyStyle
{

    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->block($message, 'OK', 'fg=green;', ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->block($message, 'WARNING', 'fg=red;', ' ', true);
    }
}
