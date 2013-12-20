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

    public function testGetBowerFileContent()
    {

        $json = '{"name": "jquery-ui", "version": "1.10.4", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $getBowerFileContent = $this->getMethod('Bowerphp\Config\Config', 'getBowerFileContent');
        $this->assertEquals(json_decode($json,true), $config->getBowerFileContent());

    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetBowerFileContentWithExceptionOnInvalidJson()
    {
        $json = '{"directory": "app/Resources/bower", "storage": { "packages": "/tmp/bower" }}';

        $filesystem = Mockery::mock('Gaufrette\Filesystem');

        $filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn("[asdasd")
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();

    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetBowerFileContentWithExceptionOnBowerJsonNotExist()
    {
        $json = '{"directory": "app/Resources/bower", "storage": { "packages": "/tmp/bower" }}';
        $jsonPackage = '{"name": "jquery-ui", "version": "1.10.4", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $filesystem = Mockery::mock('Gaufrette\Filesystem');

        $filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(false)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($jsonPackage)
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();

    }

    public function testuUpdateBowerJsonFile()
    {
        $this->markTestIncomplete();
    }

    public function testGetPackageBowerFileContent()
    {
        $this->markTestIncomplete();

    }

    public function testWriteBowerFile()
    {
        $this->markTestIncomplete();

    }

}
