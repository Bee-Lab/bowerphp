<?php

/*
 * This file is part of the Bowerphp package.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Test\Output;

use Bowerphp\Output\BowerphpConsoleOutput;
use Mockery;

class BowerphpConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testWritelnInfoPackage()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getVersion')->andReturn('2.1')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('2.1')
        ;

        $output
            ->shouldReceive('writeln')->with('bower <info>jquery#2.1           </info>')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnInfoPackage($package);
    }

    public function testWritelnInstalledPackage()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getVersion')->andReturn('2.1')
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $output
            ->shouldReceive('writeln')->with('bower <info>jquery#2.1           </info> <fg=cyan>   install</fg=cyan>')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnInstalledPackage($package);
    }

    public function testWritelnNoBowerJsonFile()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');

        $output
            ->shouldReceive('writeln')->with('bower <info>                     </info> <fg=yellow>   no-json</fg=yellow> No bower.json file to save to, use bower init to create one')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnNoBowerJsonFile();
    }

    public function testWritelnJson()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');

        $output
            ->shouldReceive('writeln')->with('{<info>name</info>: <fg=cyan>\'foo\'</fg=cyan>}')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnJson('{"name": "foo"}');
    }

    public function testWritelnJsonText()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');

        $output
            ->shouldReceive('writeln')->with('<fg=cyan>"name"</fg=cyan>')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnJsonText("name");
    }

    public function testWritelnListPackage()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $bowerphp = Mockery::mock('Bowerphp\Bowerphp');

        $package
            ->shouldReceive('getName')->andReturn('jquery', 'fonts.css')
            ->shouldReceive('getVersion')->andReturn('1.2', '1.0.0')
        ;

        $bowerphp
            ->shouldReceive('isPackageExtraneous')->with($package)->andReturn(true, false)
        ;

        $output
            ->shouldReceive('writeln')->with('jquery#1.2<info> extraneous</info>')
            ->shouldReceive('writeln')->with('fonts.css#1.0.0<info></info>')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnListPackage($package, $bowerphp);
        $BConsoleOutput->writelnListPackage($package, $bowerphp);
    }

    public function testWritelnSearchOrLookup()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');

        $output
            ->shouldReceive('writeln')->with('<fg=cyan>foo</fg=cyan> bar')
            ->shouldReceive('writeln')->with('   <fg=cyan>foo</fg=cyan> bar')
        ;

        $BConsoleOutput = new BowerphpConsoleOutput($output);
        $BConsoleOutput->writelnSearchOrLookup('foo', 'bar');
        $BConsoleOutput->writelnSearchOrLookup('foo', 'bar', 3);
    }
}
