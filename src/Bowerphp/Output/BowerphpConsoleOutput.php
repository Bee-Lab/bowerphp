<?php

/*
 * This file is part of the Bowerphp package.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Output;

use Bowerphp\Package\PackageInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class BowerphpConsoleOutput extends ConsoleOutput
{

    /**
     * writelnInfoPackage
     *
     * @params PackageInterface $package
     *
     */
    public function writelnInfoPackage(PackageInterface $package)
    {
        $this->writeln(sprintf('bower <info>%s</info>', str_pad($package->getName() . '#' . $package->getVersion(), 21, ' ', STR_PAD_RIGHT)));
    }


    /**
     * writelnInstalledPackage
     *
     * @params PackageInterface $package
     * @params string $packageVersion
     *
     */
    public function writelnInstalledPackage(PackageInterface $package, $packageVersion)
    {
        $this->writeln(sprintf('bower <info>%s</info> <fg=cyan>%s</fg=cyan>', str_pad($package->getName() . '#' . $packageVersion, 21, ' ', STR_PAD_RIGHT), str_pad('install', 10, ' ', STR_PAD_LEFT)));
    }

}