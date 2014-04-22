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
use Bowerphp\Util\Filesystem;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $httpClient = new Client();
        $config = new Config($filesystem);

        $this->logHttp($httpClient, $output);

        // http cache
        $cachePlugin = new CachePlugin(array(
           'storage' => new DefaultCacheStorage(new DoctrineCacheAdapter(new FilesystemCache($config->getCacheDir())), 'bowerphp', 86400)
        ));
        $httpClient->addSubscriber($cachePlugin);

        $packageName = $input->getArgument('package');

        $v = explode('#', $packageName);
        $packageName   = isset($v[0]) ? $v[0] : $packageName;
        $version       = isset($v[1]) ? $v[1] : '*';

        $package       = new Package($packageName, $version);
        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp      = new Bowerphp($config, $filesystem, $httpClient, new GithubRepository(), $consoleOutput);

        $bower         = $bowerphp->getPackageInfo($package, 'original_url');

        $consoleOutput->writelnSearchOrLookup($bower['name'], $bower['url']);

    }

}
