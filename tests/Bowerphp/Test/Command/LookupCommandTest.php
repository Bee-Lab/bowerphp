<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group lookup
 */

class LookupCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('lookup'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'jquery'), array('decorated' => false));

        $this->assertRegExp('/jquery.git/', $commandTester->getDisplay());
        $this->assertRegExp('/git:/', $commandTester->getDisplay());

    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('lookup'));
        $commandTester->execute(array(), array('decorated' => false));
    }
}
