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
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Doctrine\Common\Cache\FilesystemCache;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Search
 */
class SearchCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search for a package by name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name to search for.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command searches for a package by name.

  <info>php %command.full_name% name</info>
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
        $config = new Config($filesystem);

        $this->logHttp($httpClient, $output);

        // http cache
        $cachePlugin = new CachePlugin(array(
           'storage' => new DefaultCacheStorage(new DoctrineCacheAdapter(new FilesystemCache($config->getCacheDir())), 'bowerphp', 86400)
        ));
        $httpClient->addSubscriber($cachePlugin);

        $name = $input->getArgument('name');

        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($config, $filesystem, $httpClient, new GithubRepository(), $consoleOutput);
        $packageNames  =  $bowerphp->searchPackages($name);

        if (count($packageNames) === 0) {
            $output->writeln('No results.');
        } else {
            $output->writeln('Search results:');
            $output->writeln('');
            foreach ($packageNames as $packageName) {
                $package = new Package($packageName);
                $bower = $bowerphp->getPackageInfo($package, 'original_url');

                $consoleOutput->writelnSearchOrLookup($bower['name'], $bower['url'], 4);
            }
        }
    }
}
