<?php

namespace Bowerphp\Test\Command\Helper;

use Bowerphp\Command\Helper\DialogHelper;

class DialogHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuestion()
    {
        $helper = new DialogHelper(false);
        $this->assertEquals('<info>This is a question</info> [<comment>default reply</comment>]: ', $helper->getQuestion('This is a question', 'default reply'));
        $this->assertEquals('<info>Another question</info>- ', $helper->getQuestion('Another question', null, '-'));
    }
}
