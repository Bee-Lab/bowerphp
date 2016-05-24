<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Factory\CommandFactory;
use RuntimeException;

/**
 * @group functional
 */
class SearchCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldExecute()
    {
        //when
        $commandTester = CommandFactory::tester('search', array('name' => 'smart'));

        //then
        $this->assertRegExp('/Search results/', $commandTester->getDisplay());
        $this->assertRegExp('/js-geo.git/', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function shouldWriteNoResultWhenNoPackageFound()
    {
        //when
        $commandTester = CommandFactory::tester('search', array('name' => 'unexistant'));

        //then
        $this->assertRegExp('/No results/', $commandTester->getDisplay());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function shouldThrowExceptionWhenNoPackagePass()
    {
        //when
        CommandFactory::tester('search');
    }
}
