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
use Camspiers\JsonPretty\JsonPretty;
use Symfony\Component\Console\Output\ConsoleOutput;

class BowerphpConsoleOutput extends ConsoleOutput
{
    /**
     * writelnInfoPackage
     *
     * @params PackageInterface $package
     */
    public function writelnInfoPackage(PackageInterface $package)
    {
        $this->writeln(sprintf('bower <info>%s</info>',
            str_pad($package->getName() . '#' . $package->getVersion(), 21, ' ', STR_PAD_RIGHT)
        ));
    }

    /**
     * writelnInstalledPackage
     *
     * @params PackageInterface $package
     * @params string           $packageVersion
     */
    public function writelnInstalledPackage(PackageInterface $package, $packageVersion)
    {
        $this->writeln(sprintf('bower <info>%s</info> <fg=cyan>%s</fg=cyan>',
            str_pad($package->getName() . '#' . $packageVersion, 21, ' ', STR_PAD_RIGHT),
            str_pad('install', 10, ' ', STR_PAD_LEFT)
        ));
    }

    /**
     * writelnNoBowerJsonFile
     */
    public function writelnNoBowerJsonFile()
    {
        $this->writeln(sprintf('bower <info>%s</info> <fg=yellow>%s</fg=yellow> %s',
            str_pad("", 21, ' ', STR_PAD_RIGHT),
            str_pad('no-json', 10, ' ', STR_PAD_LEFT),
            'No bower.json file to save to, use bower init to create one', 10, ' ', STR_PAD_LEFT
        ));
    }

    /**
     * Rewrite json with colors and unescaped slashes
     *
     * @param string $jsonString
     */
    public function writelnJson($jsonString)
    {
        $keyColor = preg_replace('/"(\w+)": /', '<info>$1</info>: ', $jsonString);
        $valColor = preg_replace('/"([^"]+)"/', "<fg=cyan>'$1'</fg=cyan>", $keyColor);

        $this->writeln(stripslashes($valColor));
    }

    /**
     * writelnJson
     *
     * @param mixed $jsonPart
     */
    public function writelnJsonText($jsonPart)
    {
        $this->writeln(sprintf('<fg=cyan>%s</fg=cyan>', $this->json_readable_encode($jsonPart)));
    }

    /**
     * FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
     * See http://www.php.net/manual/en/function.json-encode.php
     *
     * @param  mixed  $value
     * @return string
     */
    private function json_readable_encode($value)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        $jsonPretty = new JsonPretty();

        return $jsonPretty->prettify($value, null, '    ');
    }
}
