<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class ListCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(getcwd() . '/bower_components/')) {
            mkdir(getcwd() . '/bower_components/');
        }
        mkdir(getcwd() . '/bower_components/bootstrap/');
        mkdir(getcwd() . '/bower_components/jquery/');
        file_put_contents(getcwd() . '/bower.json', '{"name":"project","requirements":{"jquery":"*"}}');
        file_put_contents(getcwd() . '/bower_components/bootstrap/.bower.json', '{"name":"bootstrap","version":"3"}');
        file_put_contents(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"1"}');
    }

    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('list'));
        $commandTester->execute(array('command' => $command->getName()), array('decorated' => false));
        $this->assertRegExp('/bootstrap#3 extraneous/', $commandTester->getDisplay());
        $this->assertRegExp('/jquery#1/', $commandTester->getDisplay());    // TODO check is NOT extraneous
    }

    public function tearDown()
    {
        unlink(getcwd() . '/bower.json');
        unlink(getcwd() . '/bower_components/bootstrap/.bower.json');
        unlink(getcwd() . '/bower_components/jquery/.bower.json');
        rmdir(getcwd() . '/bower_components/bootstrap/');
        rmdir(getcwd() . '/bower_components/jquery/');
        rmdir(getcwd() . '/bower_components/');
    }
}
