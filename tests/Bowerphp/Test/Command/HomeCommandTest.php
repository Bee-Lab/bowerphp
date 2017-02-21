<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class HomeCommandTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testExecuteNotFound()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'nonexistante'], ['decorated' => false]);
        $this->assertRegExp('/zzzz/', $commandTester->getDisplay());
    }

    public function testExecuteVerbose()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false, 'verbosity' => OutputInterface::VERBOSITY_DEBUG]);
        $this->assertRegExp('/"name":"jquery"/', $commandTester->getDisplay());
    }

    public function testExecuteNonVerbose()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false, 'verbosity' => OutputInterface::VERBOSITY_NORMAL]);
        $this->assertRegExp('/\s/', $commandTester->getDisplay());
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('home'));
        $commandTester->execute([], ['decorated' => false]);
        $this->assertRegExp('/zzzz/', $commandTester->getDisplay());
    }
}
