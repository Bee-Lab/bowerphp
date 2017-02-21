<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'colorbox'], ['decorated' => false]);

        $this->assertRegExp('/name: \'jquery-colorbox\'/', $commandTester->getDisplay());
        $this->assertRegExp('/Available versions:/', $commandTester->getDisplay());

        $commandTester->execute(['command' => $command->getName(), 'package' => 'colorbox', 'property' => 'main'], ['decorated' => false]);

        $this->assertEquals('"jquery.colorbox.js"' . PHP_EOL, $commandTester->getDisplay());
    }

    public function testExecuteNoVersionsFound()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'restio'], ['decorated' => false]);

        $this->assertRegExp('/No versions available/', $commandTester->getDisplay());
    }

    public function testExecuteWithRenamedRepo()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery-hammerjs'], ['decorated' => false]);

        $this->assertRegExp('/jquery-hammerjs/', $commandTester->getDisplay());
    }

    public function testExecuteWithSlashedVersion()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'ckeditor#full/4.5.2'], ['decorated' => false]);

        $this->assertRegExp('/name: \'ckeditor\'/', $commandTester->getDisplay());
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('info'));
        $commandTester->execute([], ['decorated' => false]);
    }
}
