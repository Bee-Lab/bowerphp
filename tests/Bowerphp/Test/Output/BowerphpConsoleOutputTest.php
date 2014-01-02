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
use Mockery;

class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testWritelnInfoPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getVersion')->andReturn('2.1')
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $BConsoleOutput = new TestOutput();
        $BConsoleOutput->setDecorated(false);
        $BConsoleOutput->writelnInfoPackage($package);

        $this->assertEquals("bower jquery#2.1", $BConsoleOutput->output);
    }

    public function testWritelnInstalledPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getVersion')->andReturn('2.1')
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $BConsoleOutput = new TestOutput();
        $BConsoleOutput->setDecorated(false);
        $BConsoleOutput->writelnInstalledPackage($package, '3.0');

        $this->assertEquals("bower jquery#3.0               install", $BConsoleOutput->output);
    }

    public function testWritelnNoBowerJsonFile()
    {
        $BConsoleOutput = new TestOutput();
        $BConsoleOutput->setDecorated(false);
        $BConsoleOutput->writelnNoBowerJsonFile();

        $this->assertEquals('bower                          no-json No bower.json file to save to, use bower init to create one', $BConsoleOutput->output);
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
