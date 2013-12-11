<?php

namespace Bowerphp\Test;

use Bowerphp\Config\Config;
use Bowerphp\Test\TestCase;

class ConfigTest extends TestCase
{
    public function testConstructor()
    {
        $json = '{"directory": "app/Resources/bower", "storage": { "packages": "/tmp/bower" }}';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/.bowerrc')
            ->will($this->returnValue(true))
        ;

        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/.bowerrc')
            ->will($this->returnValue($json))
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals('/tmp/bower', $config->getCacheDir());
        $this->assertEquals(getcwd() . '/app/Resources/bower', $config->getInstallDir());
    }

    public function testDefaultOptions()
    {
        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/.bowerrc')
            ->will($this->returnValue(false))
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(getenv('HOME') . '/.cache/bowerphp', $config->getCacheDir());
        $this->assertEquals(getcwd() . '/bower_components', $config->getInstallDir());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMalformedJson()
    {
        $json = '{invalid';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/.bowerrc')
            ->will($this->returnValue(true))
        ;

        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/.bowerrc')
            ->will($this->returnValue($json))
        ;

        $config = new Config($this->filesystem);
    }
}
