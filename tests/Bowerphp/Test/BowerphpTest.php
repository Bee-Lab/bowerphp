<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;

class BowerphpTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $json = json_encode(array('name' => 'Foo', 'authors' => array('Beelab <info@bee-lab.net>', 'Mallo'), 'private' => true, 'dependencies' => array()), JSON_PRETTY_PRINT);
        $params = array('name' => 'Foo', 'author' => 'Mallo');
        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->once())->method('write')->with('bower.json', $json);
        $bowerphp = new Bowerphp($filesystem);
        $bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $this->markTestIncomplete();
    }

    public function testInstallDependencies()
    {
        $this->markTestIncomplete();
    }
}
