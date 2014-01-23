<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;
use Mockery;
use RuntimeException;

class InstallerTest extends TestCase
{
    protected $installer, $zipArchive, $config;

    public function setUp()
    {
        parent::setUp();

        $this->zipArchive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');

        $this->installer = new Installer($this->filesystem, $this->zipArchive, $this->config);

        $this->config
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/')
            ->shouldReceive('getInstallDir')->andReturn(getcwd() . '/bower_components')
            ->shouldReceive('getCacheDir')->andReturn('.')
        ;
    }

    public function testInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getInfo')->andReturn(null)
            ->shouldReceive('getVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('')
            ->shouldReceive('statIndex')->andReturn(array('name' => 'jquery/foo', 'size' => 10))
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $this->filesystem
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/foo', 'foo content', true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}', true)
        ;

        $this->installer->install($package);
    }

    public function testUpdate()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getInfo')->andReturn(null)
            ->shouldReceive('getVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('')
            ->shouldReceive('statIndex')->andReturn(array('name' => 'jquery/foo', 'size' => 10))
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $this->filesystem
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/foo', 'foo content', true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}', true)
        ;

        $this->installer->update($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unable to open zip file ./tmp/jquery.
     */
    public function testInstallZipOpenException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(false)
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unable to open zip file ./tmp/jquery.
     */
    public function testUpdateZipOpenException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(false)
        ;

        $this->installer->update($package);
    }

    public function testFilterZipFiles()
    {
        $archive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $archive->shouldReceive('getNumFiles')->andReturn(6);
        $archive->shouldReceive('statIndex')->times(6)->andReturn(
            array('name' => 'foo', 'size' => 10),
            array('name' => 'foo.md', 'size' => 12),
            array('name' => 'foo.txt', 'size' => 13),
            array('name' => '.foo', 'size' => 3),
            array('name' => 'bower.json', 'size' => 3),
            array('name' => 'package.json', 'size' => 3)
        );
        $filterZipFiles = $this->getMethod('Bowerphp\Installer\Installer', 'filterZipFiles');
        $ignore = array('.*', '*.md', 'bower.json', 'package.json');
        $expect = array('foo', 'foo.txt');
        $this->assertEquals($expect, $filterZipFiles->invokeArgs($this->installer, array($archive, $ignore)));
    }

    public function testUninstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('listKeys')->with(getcwd() . '/bower_components/jquery/')->andReturn(array('dirs' => array(getcwd() . '/bower_components/jquery/subdir'), 'keys' => array(getcwd() . '/bower_components/jquery/file1', getcwd() . '/bower_components/jquery/file2')))
            ->shouldReceive('listKeys')->with(getcwd() . '/bower_components/jquery/subdir/')->andReturn(array('dirs' => array(), 'keys' => array()))
            ->shouldReceive('delete')->with(getcwd() . '/bower_components/jquery/file1')->andReturn(true)
            ->shouldReceive('delete')->with(getcwd() . '/bower_components/jquery/file2')->andReturn(true)
            ->shouldReceive('delete')->with(getcwd() . '/bower_components/jquery/subdir/')->andReturn(true)
            ->shouldReceive('delete')->with(getcwd() . '/bower_components/jquery/')->andReturn(true)
        ;

        $this->installer->uninstall($package);
    }

    public function testGetInstalled()
    {
        $this->filesystem
            ->shouldReceive('listKeys')->with(getcwd() . '/bower_components')->andReturn(array('dirs' => array('package1', 'package2')))
            ->shouldReceive('has')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with('package2/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn('{"name":"package1","version":"1.0.0"}')
            ->shouldReceive('read')->with('package2/.bower.json')->andReturn('{"name":"package2","version":"1.2.3"}')
        ;

        $this->assertCount(2, $this->installer->getInstalled());
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid content in .bower.json for package package1.
     */
    public function testGetInstalledWithoutBowerJsonFile()
    {
        $this->filesystem
            ->shouldReceive('listKeys')->with(getcwd() . '/bower_components')->andReturn(array('dirs' => array('package1')))
            ->shouldReceive('has')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn(null)
        ;

        $this->installer->getInstalled();
    }

    public function testFindDependentPackages()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('listKeys')->with(getcwd() . '/bower_components')->andReturn(array('dirs' => array('package1', 'package2')))
            ->shouldReceive('has')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with('package2/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn('{"name":"package1","version":"1.0.0","dependencies":{"jquery": ">=1.3.2"}}')
            ->shouldReceive('read')->with('package2/.bower.json')->andReturn('{"name":"package2","version":"1.2.3","dependencies":{"jquery": ">=1.6"}}')
        ;

        $packages = $this->installer->findDependentPackages($package);

        $this->assertCount(2, $packages);
        $this->assertArrayHasKey('>=1.3.2', $packages);
        $this->assertArrayHasKey('>=1.6', $packages);
    }
}
