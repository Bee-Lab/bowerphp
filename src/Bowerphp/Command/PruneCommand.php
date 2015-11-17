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
use Bowerphp\Command\Helper\DialogHelper;
use Bowerphp\Installer\Installer;
use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Util\PackageNameVersionExtractor;
use Bowerphp\Util\ZipArchive;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Prune
 */
class PruneCommand extends Command
{
    private $exploredDependencies;

    private $installationDirectory;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('prune')
            ->setDescription('Uninstalls all the package that are not saved in the bower.json file')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Ask confirmation before deleting each package')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command uninstall all package not saved in the project bower.json file.

  <info>php %command.full_name%</info>
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interactive = $input->getOption('interactive');

        $this->setGithubToken($output);
        $bowerphp = $this->getBowerphp($output);
        $this->installationDirectory = $this->config->getInstallDir();
        $projectRootDependencies = $this->getDependencies();

        try {
            /** @var InstallerInterface $installer */
            $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);
            $installed = $installer->getInstalled(new Finder());

            $newDependenciesToExplore = $projectRootDependencies;
            $dependenciesToExplore = $newDependenciesToExplore;
            $this->exploredDependencies = array();

            while ($dependenciesToExplore !== array()) {
                foreach ($dependenciesToExplore as $dependency) {
                    $dependencies = $this->getBowerDependencies($dependency);
                    $newDependenciesToExplore = array_merge($newDependenciesToExplore, $dependencies);
                    $this->exploredDependencies[$dependency] = $dependency;
                }
                $dependenciesToExplore = $this->getUnexploredDependencies($newDependenciesToExplore);
                $newDependenciesToExplore = array();
            }

            $packageToRemove = $this->getPackageToRemove($installed);
            if (empty($packageToRemove)) {
                $output->writeln('No package to remove');
            } else {
                foreach ($packageToRemove as $packageName) {
                    if ($interactive) {
                        $dialog = $this->getHelper('dialog');
                        if ($dialog->askConfirmation(
                                $output,
                                $dialog->getQuestion('Removing package ' . $packageName, 'Yes'),
                                true
                                )
                        ) {
                            $this->uninstallPackage($packageName, $bowerphp, $installer);
                        }
                    } else {
                        $this->uninstallPackage($packageName, $bowerphp, $installer);
                    }
                }
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        $output->writeln('');
    }

    private function getPackageToRemove($installed)
    {
        $toRemove = array();
        foreach ($installed as $package) {
            $packageName = $package->getName();
            if (!isset($this->exploredDependencies[$packageName])) {
                $toRemove[] = $packageName;
            }
        }
        return $toRemove;
    }

    private function uninstallPackage($packageName, Bowerphp $bowerphp, InstallerInterface $installer)
    {
        $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);

        $package = new Package($packageNameVersion->name, $packageNameVersion->version);
        $bowerphp->uninstallPackage($package, $installer);
    }

    private function getUnexploredDependencies($dependencies)
    {
        $unexploredDependencies = array();
        foreach ($dependencies as $dependency) {
            if (!isset($this->exploredDependencies[$dependency])) {
                $unexploredDependencies[] = $dependency;
            }
        }
        return $unexploredDependencies;
    }

    private function getDependencies($jsonFile='bower.json')
    {
        $bowerjson = json_decode(file_get_contents($jsonFile), true);
        if (isset($bowerjson['dependencies'])) {
            return array_keys($bowerjson['dependencies']);
        }
        return array();
    }

    private function getBowerDependencies($packageName)
    {
        return $this->getDependencies($this->installationDirectory . '/' . $packageName . '/.bower.json');
    }
}
