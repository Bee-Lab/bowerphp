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
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\ZipArchive;
use Github\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * This command shows a list of installed packages.
 * Not to be confused with original "list" command of Symfony, that has been
 * renamed to "list-commands" (see CommandListCommand.php)
 */
class ListCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription('Lists installed packages')
            ->setHelp(<<<EOT
The <info>%command.name%</info> lists installed packages.

  <info>%command.full_name%</info>
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

        $consoleOutput = new BowerphpConsoleOutput($output);
        $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);
        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->githubClient, new GithubRepository(), $consoleOutput);
        $packages = $bowerphp->getInstalledPackages($installer, new Finder());

        foreach ($packages as $package) {
            $consoleOutput->writelnListPackage($package, $bowerphp);
        }
    }
}
