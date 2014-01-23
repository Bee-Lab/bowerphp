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

        $this->assertEquals(json_decode($json,true), $config->getBowerFileContent());

    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Malformed JSON {invalid.
     */
    public function testGetBowerFileContentWithExceptionOnInvalidJson()
    {
        $json = '{invalid';

        $filesystem = Mockery::mock('Gaufrette\Filesystem');

        $filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage No bower.json found. You can run "init" command to create it.
     */
    public function testGetBowerFileContentWithExceptionOnBowerJsonDoesNotExist()
    {
        $filesystem = Mockery::mock('Gaufrette\Filesystem');

        $filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(false)
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();
    }

    public function testUpdateBowerJsonFile()
    {
        $json = '{
    "dependencies": {
        "foobar": "*"
    }
}';

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('foobar')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json, true)->andReturn(123)
        ;

        $config = new Config($this->filesystem);

        $this->assertFalse($config->updateBowerJsonFile($package, '*'));

        $config->setSaveToBowerJsonFile(true);

        $this->assertEquals(123, $config->updateBowerJsonFile($package, '*'));
    }

    public function testUpdateBowerJsonFile2()
    {
        $json = "{\n    \"foo\": 2,\n    \"bar\": 3\n}";

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json, true)->andReturn(456)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(456, $config->updateBowerJsonFile2(array('foo' => 1), array('foo' => 2, 'bar' => 3)));
    }

    public function testGetPackageBowerFileContent()
    {
        $this->markTestIncomplete();
    }

    public function testWriteBowerFile()
    {
        $this->markTestIncomplete();
    }

    public function testSetSaveToBowerJsonFile()
    {
        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $config->setSaveToBowerJsonFile();
    }

    public function testInitBowerJsonFile()
    {
        $json = '{
    "name": "foo",
    "authors": [
        "Beelab <info@bee-lab.net>",
        "bar"
    ],
    "private": true,
    "dependencies": {

    }
}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json)->andReturn(123)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(123, $config->initBowerJsonFile(array('name' => 'foo', 'author' => 'bar')));
    }

    public function testGetBasePackagesUrl()
    {
        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals('http://bower.herokuapp.com/packages/', $config->getBasePackagesUrl());
    }

    public function testBowerFileExists()
    {
        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(false, true)
        ;

        $config = new Config($this->filesystem);

        $this->assertFalse($config->bowerFileExists());
        $this->assertTrue($config->bowerFileExists());
    }
}
