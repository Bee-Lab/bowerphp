<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class LookupCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('lookup'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false]);

        $this->assertRegExp('/jquery-dist.git/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('lookup'));
        $commandTester->execute([], ['decorated' => false]);
    }
}
