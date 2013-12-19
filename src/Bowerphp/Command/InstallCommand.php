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
            ->setDescription('Installs the project dependencies from the bower.json file or a single specified package.')
            ->setDefinition(array(
                new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Shows more details including new commits pulled in when updating packages.'),
                new InputOption('save', 'S', InputOption::VALUE_NONE, 'If flag -S or --save is passed to install, package will be added to bower.json file (only if bower.json file already exists)'),
            ))
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Choose a package.'
            )
            ->setHelp(<<<EOT
The <info>install</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

<info>php bowerphp.phar install</info>

If an optional package name is passed, that package is installed.

<info>php bowerphp.phar install packageName[#version]</info>

EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter        = new LocalAdapter('/');
        $filesystem     = new Filesystem($adapter);
        $httpClient     = new Client();
        $config         = new Config($filesystem);
        $config->setSaveToBowerJsonFile($input->getOption('save'));


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

        $bowerphp = new Bowerphp($filesystem, $config);

        try {
            $installer = new Installer($filesystem, $httpClient, new GithubRepository(), new ZipArchive(), $config, $output);

            if (is_null($packageName)) {
                $bowerphp->installDependencies($installer);
            } else {
                $v = explode("#", $packageName);
                $packageName = isset($v[0]) ? $v[0] : $packageName;
                $version = isset($v[1]) ? $v[1] : "*";

                $package = new Package($packageName, $version);

                $bowerphp->installPackage($package, $installer);
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        $output->writeln('');
    }

}
