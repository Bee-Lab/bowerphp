<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class InfoCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'colorbox'), array('decorated' => false));

        $this->assertRegExp('/name: \'jquery-colorbox\'/', $commandTester->getDisplay());
        $this->assertRegExp('/Available versions:/', $commandTester->getDisplay());

        $commandTester->execute(array('command' => $command->getName(), 'package' => 'colorbox', 'property' => 'main'), array('decorated' => false));

        $this->assertEquals('"jquery.colorbox.js"' . PHP_EOL, $commandTester->getDisplay());
    }

    public function testExecuteNoVersionsFound()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(array('command' => $command->getName(), 'package' => 'restio'), array('decorated' => false));

        $this->assertRegExp('/No versions available/', $commandTester->getDisplay());
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(array(), array('decorated' => false));
    }
}
