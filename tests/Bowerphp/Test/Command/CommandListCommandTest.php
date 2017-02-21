<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class CommandListCommandTest extends TestCase
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
        $this->assertRegExp('/Memory usage/', $tester->getDisplay());
    }

    public function testWorkingDir()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run(['-d' => '/'], ['decorated' => false]);
        $this->assertRegExp('/Usage/', $tester->getDisplay());
    }

    /**
     * @expectedException        \RuntimeException
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
