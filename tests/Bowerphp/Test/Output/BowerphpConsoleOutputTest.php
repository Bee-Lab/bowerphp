<?php

/*
 * This file is part of the Bowerphp package.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Tests\Output;

use Bowerphp\Output\BowerphpConsoleOutput;

class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testWritelnInfoPackage()
    {
        $BConsoleOutput = new TestOutput();
        $BConsoleOutput->setDecorated(false);

        $package = $this->getMock('Bowerphp\Package\PackageInterface');

        $package
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue("2.1"))
        ;

         $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue("Jquery"))
        ;

        $BConsoleOutput->writelnInfoPackage($package);

        $this->assertEquals("bower Jquery#2.1", $BConsoleOutput->output);
    }

    public function testWritelnInstalledPackage()
    {
        $BConsoleOutput = new TestOutput();
        $BConsoleOutput->setDecorated(false);

        $package = $this->getMock('Bowerphp\Package\PackageInterface');

        $package
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue("2.1"))
        ;

         $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue("Jquery"))
        ;

        $version = "3.0";
        $BConsoleOutput->writelnInstalledPackage($package, $version);

        $this->assertEquals("bower Jquery#3.0               install", $BConsoleOutput->output);
    }

}


class TestOutput extends BowerphpConsoleOutput
{
    public $output = '';

    public function clear()
    {
        $this->output = '';
    }

    protected function doWrite($message, $newline)
    {
        $this->output .= trim($message);
    }
}
