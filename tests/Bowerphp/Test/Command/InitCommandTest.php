<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('init'));
        $commandTester->execute(array('command' => $command->getName()), array('interactive' => false, 'decorated' => false));
    }

    public function tearDown()
    {
        $file = getcwd() . '/bower.json';
        if (is_file($file)) {
            unlink($file);
        }
    }
}
