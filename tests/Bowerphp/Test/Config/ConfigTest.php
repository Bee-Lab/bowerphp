<?php

namespace Bowerphp\Test\Config;

use Bowerphp\Config\Config;
use Bowerphp\Test\BowerphpTestCase;
use Mockery;
use Mockery\Exception\NoMatchingExpectationException;

class ConfigTest extends BowerphpTestCase
{
    public function testConstructor()
    {
        $json = '{"directory": "app/Resources/bower", "storage": { "packages": "/tmp/bower" }}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals('/tmp/bower', $config->getCacheDir());
        $this->assertEquals(getcwd() . '/app/Resources/bower', $config->getInstallDir());
    }

    public function testDefaultOptions()
    {
        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(getcwd() . '/bower_components', $config->getInstallDir());
    }

    public function testGetHomeDirUnix()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('Only on Unix/Linux systems');
        }

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $method = $this->getMethod('Bowerphp\Config\Config', 'getHomeDir');
        $this->assertContains('/', $method->invokeArgs($config, []));
    }

    public function testGetHomeDirWindows()
    {
        if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('Only on Windows systems');
        }

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $method = $this->getMethod('Bowerphp\Config\Config', 'getHomeDir');
        $this->assertContains('AppData', $method->invokeArgs($config, []));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Invalid .bowerrc file.
     */
    public function testMalformedJson()
    {
        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn('{invalid')
        ;

        $config = new Config($this->filesystem);
    }

    public function testGetBowerFileContent()
    {
        $json = '{"name": "jquery-ui", "version": "1.10.4", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(json_decode($json, true), $config->getBowerFileContent());
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Malformed JSON in bower.json: {invalid.
     */
    public function testGetBowerFileContentWithExceptionOnInvalidJson()
    {
        $json = '{invalid';

        $filesystem = Mockery::mock('Bowerphp\Util\Filesystem');

        $filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage No bower.json found. You can run "init" command to create it.
     */
    public function testGetBowerFileContentWithExceptionOnBowerJsonDoesNotExist()
    {
        $filesystem = Mockery::mock('Bowerphp\Util\Filesystem');

        $filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(false)
        ;

        $config = new Config($filesystem);
        $config->getBowerFileContent();
    }

    public function testGetOverrideSection()
    {
        $json = '{"name": "jquery-ui", "version": "1.10.4", "main": ["ui/jquery-ui.js"], ' .
            '"dependencies": {"jquery": ">=1.6"}, "overrides": {"jquery": {}}}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(['jquery' => []], $config->getOverridesSection());
    }

    public function testGetOverrideFor()
    {
        $json = '{"name": "jquery-ui", "version": "1.10.4", "main": ["ui/jquery-ui.js"], ' .
            '"dependencies": {"jquery": ">=1.6"}, "overrides": {"jquery": {"foo": "bar"}}}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/.bowerrc')->andReturn($json)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(['foo' => 'bar'], $config->getOverrideFor('jquery'));
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
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json)->andReturn(123)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(0, $config->updateBowerJsonFile($package));

        $config->setSaveToBowerJsonFile(true);

        $this->assertEquals(123, $config->updateBowerJsonFile($package));
    }

    public function testUpdateBowerJsonFile2()
    {
        $json = "{\n    \"foo\": 2,\n    \"bar\": 3\n}";

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json)->andReturn(456)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals(456, $config->updateBowerJsonFile2(['foo' => 1], ['foo' => 2, 'bar' => 3]));
    }

    public function testGetPackageBowerFileContent()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('foobar')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/foobar/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/foobar/.bower.json')->andReturn('{"name":"foobar","version":"1.2.3"}')
        ;

        $config = new Config($this->filesystem);
        $this->assertEquals(['name' => 'foobar', 'version' => '1.2.3'], $config->getPackageBowerFileContent($package));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Could not find .bower.json file for package foobar.
     */
    public function testGetPackageBowerFileContentFileNotFound()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('foobar')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/foobar/.bower.json')->andReturn(false)
        ;

        $config = new Config($this->filesystem);
        $config->getPackageBowerFileContent($package);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Invalid content in .bower.json for package foobar.
     */
    public function testGetPackageBowerFileInvalidContent()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('foobar')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/foobar/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/foobar/.bower.json')->andReturn('{invalid')
        ;

        $config = new Config($this->filesystem);
        $config->getPackageBowerFileContent($package);
    }

    public function testSetSaveToBowerJsonFile()
    {
        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
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
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower.json', $json)->andReturn(123)
        ;

        $config = new Config($this->filesystem);

        try {
            $this->assertEquals(123, $config->initBowerJsonFile(['name' => 'foo', 'author' => 'bar']));
        } catch (NoMatchingExpectationException $e) {
            $this->markTestSkipped('Some json libs (e.g. Debian one) encode strings differently.');
        }
    }

    public function testGetBasePackagesUrl()
    {
        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
        ;

        $config = new Config($this->filesystem);

        $this->assertEquals('http://registry.bower.io/packages/', $config->getBasePackagesUrl());
    }

    public function testBowerFileExists()
    {
        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/.bowerrc')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower.json')->andReturn(false, true)
        ;

        $config = new Config($this->filesystem);

        $this->assertFalse($config->bowerFileExists());
        $this->assertTrue($config->bowerFileExists());
    }
}
