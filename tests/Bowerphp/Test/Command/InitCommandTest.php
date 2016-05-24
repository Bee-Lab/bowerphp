<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class InitCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('init'));
        $commandTester->execute(['command' => $command->getName()], ['interactive' => false, 'decorated' => false]);

        $json = json_decode(file_get_contents(getcwd() . '/bower.json'), true);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('authors', $json);
        $this->assertArrayHasKey('dependencies', $json);
    }

    protected function tearDown()
    {
        $file = getcwd() . '/bower.json';
        if (is_file($file)) {
            unlink($file);
        }
    }
}
