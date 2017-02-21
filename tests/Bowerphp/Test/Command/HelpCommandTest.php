<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class HelpCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('help'));
        $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

        $this->assertRegExp('/displays help for a given command/', $commandTester->getDisplay());
    }
}
