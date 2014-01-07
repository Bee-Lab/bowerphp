<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class HomeCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'nonexistante'), array('decorated' => false));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(array(), array('decorated' => false));
    }
}
