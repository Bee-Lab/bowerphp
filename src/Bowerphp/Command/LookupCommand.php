<?php
/*
 * This file is part of Bowerphp.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Bowerphp\Command;

use Bowerphp\Bowerphp;
use Bowerphp\Config\Config;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\PackageNameVersionExtractor;
use Github\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lookup
 */
class LookupCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('lookup')
            ->setDescription('Look up a package URL by name')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command is used for search with exact match the repository URL package

  <info>php %command.full_name% packageName</info>
EOT
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $githubClient = new Client();
        $config = new Config($filesystem);

        $this->logHttp($githubClient, $output);
        $this->setToken($githubClient);

        $name = $input->getArgument('package');

        $packageNameVersion = PackageNameVersionExtractor::fromString($name);

        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($config, $filesystem, $githubClient, new GithubRepository(), $consoleOutput);

        $package = $bowerphp->lookupPackage($packageNameVersion->name);

        $consoleOutput->writelnSearchOrLookup($package['name'], $package['url']);
    }
}
