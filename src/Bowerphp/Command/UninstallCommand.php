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
        $this->setGithubToken($output);

        $packageName = $input->getArgument('package');

        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->githubClient, new GithubRepository(), $consoleOutput);

        try {
            $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);

            $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);

            $package = new Package($packageNameVersion->name, $packageNameVersion->version);
            $bowerphp->uninstallPackage($package, $installer);
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        $output->writeln('');
    }
}
