<?php

namespace Bowerphp\Test;

use Bowerphp\Config\Config;
use Bowerphp\Test\TestCase;
use Mockery;

class ConfigTest extends TestCase
{
    public function testConstructor()
    {
        $json = '{"directory": "app/Resources/bower", "storage": { "packages": "/tmp/bower" }}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals('/tmp/bower', $config->getCacheDir());
        $this->assertEquals(getcwd() . '/app/Resources/bower', $config->getInstallDir());
    }

    public function testDefaultOptions()
    {
        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
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
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
        ;

        $config = new Config($this->filesystem);
    }
}
