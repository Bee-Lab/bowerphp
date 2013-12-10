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
use Bowerphp\Installer\Installer;
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Doctrine\Common\Cache\FilesystemCache;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Client;
use Guzzle\Log\MessageFormatter;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
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
        $adapter = new LocalAdapter('/');
        $filesystem = new Filesystem($adapter);
        $httpClient = new Client();

        // debug http interactions
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $logger = function ($message, $priority, $extras) use ($output) {
                $output->writeln('<info>Guzzle</info> ' . $message);
            };
            $logAdapter = new ClosureLogAdapter($logger);
            $logPlugin = new LogPlugin($logAdapter, MessageFormatter::DEBUG_FORMAT);
            $httpClient->addSubscriber($logPlugin);
        }

        // http cache
        $cacheDir = getenv('HOME') . '/.cache/bowerphp';    // TODO read from .bowerrc
        $cachePlugin = new CachePlugin(array(
            'storage' => new DefaultCacheStorage(
                new DoctrineCacheAdapter(
                    new FilesystemCache($cacheDir)
                )
            )
        ));
        $httpClient->addSubscriber($cachePlugin);

        $packageName = $input->getArgument('package');

        $bowerphp = new Bowerphp($filesystem, $httpClient);

        try {
            $installer = new Installer($filesystem, $httpClient, new GithubRepository(), new \ZipArchive(), $cacheDir);

            if (is_null($packageName)) {
                $output->writeln('Installing dependencies:');
                $installed = $bowerphp->installDependencies($installer);
            } else {
                $v = explode("#", $packageName);
                $packageName = isset($v[0]) ? $v[0] : $packageName;
                $version = isset($v[1]) ? $v[1] : "*";

                $output->writeln('Installing:');

                $package = new Package($packageName, $version);

                $bowerphp->installPackage($package, $installer);
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        // TODO this is ugly, since all output is in the end
        #foreach ($installed as $lib => $version) {
        #    $output->writeln(sprintf('<info>%s</info>: %s', $lib, $version));
        #}

        $output->writeln('Done.');
    }

}
