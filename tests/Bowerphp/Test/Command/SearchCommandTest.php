<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Factory\CommandFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class SearchCommandTest extends TestCase
{
    /**
     * @test
     */
    public function shouldExecute()
    {
        //when
        $commandTester = CommandFactory::tester('search', ['name' => 'smart']);

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
        $commandTester = CommandFactory::tester('search', ['name' => 'unexistant']);

        //then
        $this->assertRegExp('/No results/', $commandTester->getDisplay());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function shouldThrowExceptionWhenNoPackagePass()
    {
        //when
        CommandFactory::tester('search');
    }
}
