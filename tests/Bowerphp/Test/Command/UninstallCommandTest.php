<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class UninstallCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dir = getcwd().'/bower_components/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!is_dir($dir.'test-package')) {
            mkdir($dir.'test-package');
        }
        touch($dir.'test-package/.bower.json');
        touch($dir.'test-package/bower.json');
        touch($dir.'test-package/aFile');
        touch($dir.'test-package/anotherFile');
    }

    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('uninstall'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'test-package'), array('decorated' => false));

        $this->assertFileNotExists(getcwd().'/bower_components/test-package/.bower.json');
        $this->assertFileNotExists(getcwd().'/bower_components/test-package/bower.json');
        $this->assertFileNotExists(getcwd().'/bower_components/test-package/aFile');
        $this->assertFileNotExists(getcwd().'/bower_components/test-package/anotherFile');
    }

    public function testExecuteNonexistentPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('uninstall'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'nonexistent-package'), array('decorated' => false));

        $this->assertRegExp('/Package nonexistent-package is not installed/', $commandTester->getDisplay());

        $dir = getcwd().'/bower_components/';
        unlink($dir.'test-package/.bower.json');
        unlink($dir.'test-package/bower.json');
        unlink($dir.'test-package/aFile');
        unlink($dir.'test-package/anotherFile');
        rmdir($dir.'/test-package/');
    }

    public function tearDown()
    {
        $dir = getcwd().'/bower_components/';
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
}
