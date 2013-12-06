<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command;

use Bowerphp\Bowerphp;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Github\Client as GithubClient;
use Github\HttpClient\Cache\GaufretteCache;
use Github\HttpClient\CachedHttpClient;
use Guzzle\Http\Client;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inspired by Composer https://github.com/composer/composer
 */
class InstallCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Installs the project dependencies from the bower.json file.')
            ->setDefinition(array(
                // TODO add all options...
                new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Shows more details including new commits pulled in when updating packages.'),
            ))
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Choose a package. Add this one on bower.json file if not-exist'
            )
            ->setHelp(<<<EOT
The <info>install</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

<info>php bowerphp.phar install</info>

EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = new LocalAdapter(getcwd());
        $filesystem = new Filesystem($adapter);
        $httpClient = new Client();
        $cached = new CachedHttpClient();
        $cached->setCache(new GaufretteCache($filesystem));
        $githubClient = new GithubClient($cached);

        // debug http interactions
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $logger = function ($message, $priority, $extras) use ($output) {
                $output->writeln('<info>Guzzle</info> ' . $message);
            };
            $logAdapter = new ClosureLogAdapter($logger);
            $logPlugin = new LogPlugin($logAdapter, MessageFormatter::DEBUG_FORMAT);
            $httpClient->addSubscriber($logPlugin);
        }

        $package = $input->getArgument('package');

        $bowerphp = new Bowerphp($filesystem, $httpClient);
        $bowerphp->setGithubClient($githubClient);

        try {
            if (is_null($package)) {
                $output->writeln('Installing dependencies:');
                $installed = $bowerphp->installDependencies();
            } else {
                $output->writeln('Installing:');
                $installed = $bowerphp->installPackage($package);
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        // TODO this is ugly, since all output is in the end
        foreach ($installed as $lib => $version) {
            $output->writeln(sprintf('<info>%s</info>: %s', $lib, $version));
        }

        $output->writeln('Done.');
    }

}
