<?php

namespace Bowerphp\Test\Command\Helper;

class DialogHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuestion()
    {
        if (class_exists('Symfony\Component\Console\Helper\DialogHelper')) {
            $helper = new \Bowerphp\Command\Helper\DialogHelper();
            $this->assertEquals('<info>This is a question</info> [<comment>default reply</comment>]: ', $helper->getQuestion('This is a question', 'default reply'));
            $this->assertEquals('<info>Another question</info>- ', $helper->getQuestion('Another question', null, '-'));
        } else {
            $helper = new \Bowerphp\Command\Helper\QuestionHelper();
            $this->assertEquals('<info>This is a question</info> [<comment>default reply</comment>]: ', $helper->getQuestion('This is a question', 'default reply')->getQuestion());
            $this->assertEquals('<info>Another question</info>- ', $helper->getQuestion('Another question', null, '-')->getQuestion());
        }
    }
}
