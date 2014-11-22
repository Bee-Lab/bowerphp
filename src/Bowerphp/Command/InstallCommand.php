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
use Bowerphp\Util\PackageNameVersionExtractor;
use Bowerphp\Util\ZipArchive;
use Github\Client;
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
            ->setDescription('Installs the project dependencies from the bower.json file or a single specified package')
            ->addOption('save', 'S', InputOption::VALUE_NONE, 'Add installed package to bower.json file.')
            ->addArgument('package', InputArgument::OPTIONAL, 'Choose a package.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

  <info>php %command.full_name%</info>

If an optional package name is passed, that package is installed.

  <info>php %command.full_name% packageName[#version]</info>

If an optional flag <comment>-S</comment> is passed, installed package is added
to bower.json file (only if bower.json file already exists).

EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem     = new Filesystem();
        $githubClient   = new Client();
        $config         = new Config($filesystem);
        $config->setSaveToBowerJsonFile($input->getOption('save'));

        #$this->logHttp($githubClient, $output);
        $this->setToken($githubClient);

        $packageName = $input->getArgument('package');

        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($config, $filesystem, $githubClient, new GithubRepository(), $consoleOutput);

        try {
            $installer = new Installer($filesystem, new ZipArchive(), $config);

            if (is_null($packageName)) {
                $bowerphp->installDependencies($installer);
            } else {
                $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);
                $package = new Package($packageNameVersion->name, $packageNameVersion->version);

                $bowerphp->installPackage($package, $installer);
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            if ($e->getCode() == GithubRepository::VERSION_NOT_FOUND && !empty($package)) {
                $output->writeln(sprintf('Available versions: %s', implode(', ', $bowerphp->getPackageInfo($package, 'versions'))));
            }

            return $e->getCode();
        }

        $output->writeln('');
    }
}
