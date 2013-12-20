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
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\ZipArchive;
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
use Symfony\Component\Process\Process;

/**
 * Home
 */
class HomeCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('home')
            ->setDescription('Opens a package homepage into your favorite browser.')
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Choose a package.'
            )
            ->setHelp(<<<EOT
The <info>home</info> command opens a package homepage into your favorite browser.
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
            'storage' => new DefaultCacheStorage(
                new DoctrineCacheAdapter(
                    new FilesystemCache($config->getCacheDir())
                )
            )
        ));
        $httpClient->addSubscriber($cachePlugin);

        $packageName = $input->getArgument('package');

        $v = explode("#", $packageName);
        $packageName = isset($v[0]) ? $v[0] : $packageName;
        $version = isset($v[1]) ? $v[1] : "*";

        $package = new Package($packageName, $version);
        $bowerphp = new Bowerphp($config);
        $installer = new Installer($filesystem, $httpClient, new GithubRepository(), new ZipArchive(), $config, $output);

        $url = $bowerphp->getPackageInfo($package, $installer);

        $default = $this->getDefaulBrowser();

        $arg = "$default \"$url\"";

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $output->writeln($arg);
        } else {
            $output->writeln('');
        }

        $browser = new Process($arg);
        $browser->start();
        while ($browser->isRunning()) {
            // do nothing...
        }
    }

    /**
     * @return string
     */
    private function getDefaulBrowser()
    {
        $xdgOpen = new Process('which xdg-open');
        $xdgOpen->run();
        if (!$xdgOpen->isSuccessful()) {
            $open = new Process('which open');
            $open->run();
            if (!$open->isSuccessful()) {
                throw new \RuntimeException('Cound not open default browser.');
            }

            return trim($open->getOutput());
        }

        return trim($xdgOpen->getOutput());
    }

}
