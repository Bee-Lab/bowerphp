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
use Bowerphp\Config\Config;
use Bowerphp\Installer\Installer;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\ZipArchive;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Client;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Uninstall
 */
class UninstallCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->setDescription('Uninstalls a single specified package')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command uninstall a package.

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
        $config     = new Config($filesystem);

        $this->logHttp($httpClient, $output);

        // http cache
        $cachePlugin = new CachePlugin(array(
           'storage' => new DefaultCacheStorage(new DoctrineCacheAdapter(new FilesystemCache($config->getCacheDir())), 'bowerphp', 86400)
        ));
        $httpClient->addSubscriber($cachePlugin);

        $packageName = $input->getArgument('package');

        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($config, $filesystem, $httpClient, new GithubRepository(), $consoleOutput);

        try {
            $installer = new Installer($filesystem, new ZipArchive(), $config);

            $ver = explode("#", $packageName);
            $packageName = isset($ver[0]) ? $ver[0] : $packageName;
            $version = isset($ver[1]) ? $ver[1] : "*";

            $package = new Package($packageName, $version);
            $bowerphp->uninstallPackage($package, $installer);
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        $output->writeln('');
    }
}
