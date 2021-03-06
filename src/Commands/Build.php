<?php


namespace DraftPhp\Commands;


use DraftPhp\Config;
use DraftPhp\SiteGenerator;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Build extends Command
{

    protected static $defaultName = 'build';
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $loop = Factory::create();
        $filesystem = Filesystem::create($loop);
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Building Site');

        $siteGenerator = new SiteGenerator($this->config, $filesystem, $loop);
        $siteGenerator->build();

        $io->writeln('Site Built');

        return 0;
    }
}
