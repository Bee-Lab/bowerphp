<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dir = getcwd() . '/bower_components/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!is_dir($dir . 'jquery')) {
            mkdir($dir . 'jquery');
        }
        touch($dir . 'jquery/.bower.json');
        file_put_contents($dir . 'jquery/.bower.json', '{"name": "jquery", "version": "1.10.1"}');
        file_put_contents(getcwd() . '/bower.json', '{"name": "test", "dependencies": {"jquery": "1.*"}}');
    }

    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('update'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'jquery'), array('decorated' => false));

        $this->assertFileExists(getcwd() . '/bower_components/jquery/dist/jquery.js');
        $dotBower = json_decode(file_get_contents(getcwd() . '/bower_components/jquery/.bower.json'), true);
        $this->assertEquals('1.10.1', $dotBower['version']);
    }

    public function testExecuteNonexistentPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('update'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'nonexistent-package'), array('decorated' => false));

        $this->assertRegExp('/Package nonexistent-package is not installed/', $commandTester->getDisplay());
    }

    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('update'));
        $commandTester->execute(array('command' => $command->getName()), array('decorated' => false));

        $this->assertFileExists(getcwd() . '/bower_components/jquery/dist/jquery.js');
        $dotBower = json_decode(file_get_contents(getcwd() . '/bower_components/jquery/.bower.json'), true);
        $this->assertEquals('1.10.1', $dotBower['version']);
    }

    public function tearDown()
    {
        $dir = getcwd() . '/bower_components/';
        if (is_dir($dir)) {
            // see http://stackoverflow.com/a/15111679/369194
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            }
            rmdir($dir);
        }
        unlink(getcwd() . '/bower.json');
    }
}
