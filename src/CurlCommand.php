<?php

namespace Neox\Reddit;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CurlCommand extends Command
{
    protected function configure()
    {
        $this->setName('curl');
        $this->setDescription('Wrapped CURLObservable as a standalone app');
        $this->addArgument('url', InputArgument::REQUIRED, 'URL to download');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $returnCode = 0;
        (new CurlObservable($input->getArgument('url')))
            ->subscribe(function ($response) use ($output) {
                if (!is_float($response)) {
                    $output->write($response);
                }
                return $response;
            }, function () use (&$returnCode) {
                $returnCode = 1;
            });

        return $returnCode;
    }
}