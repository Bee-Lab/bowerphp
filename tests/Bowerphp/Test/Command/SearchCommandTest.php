<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class SearchCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('search'));
        $commandTester->execute(array('command' => $command->getName(), 'name' => 'smart'), array('decorated' => false));

        $this->assertRegExp('/Search results/', $commandTester->getDisplay());
        $this->assertRegExp('/jquery.smartbanner.git/', $commandTester->getDisplay());
        $this->assertRegExp('/git:/', $commandTester->getDisplay());
    }

    public function testExecuteNoResults()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('search'));
        $commandTester->execute(array('command' => $command->getName(), 'name' => 'unexistant'), array('decorated' => false));

        $this->assertRegExp('/No results/', $commandTester->getDisplay());
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('search'));
        $commandTester->execute(array(), array('decorated' => false));
    }
}
