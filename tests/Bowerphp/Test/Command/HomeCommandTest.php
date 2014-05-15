<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class HomeCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testExecuteNotFound()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'nonexistante'), array('decorated' => false));
    }

    public function testExecuteVerbose()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'jquery'), array('decorated' => false, 'verbosity' => OutputInterface::VERBOSITY_DEBUG));
    }

    public function testExecuteNonVerbose()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'jquery'), array('decorated' => false, 'verbosity' => OutputInterface::VERBOSITY_NORMAL));
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
