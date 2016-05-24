<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class CommandListCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('list-commands'));
        $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

        $this->assertRegExp('/Available commands/', $commandTester->getDisplay());
    }

    public function testProfile()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run(['-d' => '/', '--profile' => ''], ['decorated' => false]);
    }

    public function testWorkingDir()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run(['-d' => '/'], ['decorated' => false]);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid working directory specified.
     */
    public function testWrongWorkingDir()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run(['-d' => '/thisDirDoesNotExist'], ['decorated' => false]);
    }
}
