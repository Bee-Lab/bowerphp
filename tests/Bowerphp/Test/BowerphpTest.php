<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;
use Bowerphp\Test\TestCase;
use Mockery;

class BowerphpTest extends TestCase
{
    protected $bowerphp;

    public function setUp()
    {
        parent::setUp();
        $this->bowerphp = new Bowerphp($this->filesystem, $this->httpClient);
    }

    public function testInit()
    {
        $json =<<<EOT
{
    "name": "Foo",
    "authors": [
        "Beelab <info@bee-lab.net>",
        "Mallo"
    ],
    "private": true,
    "dependencies": {

    }
}
EOT;
        $params = array('name' => 'Foo', 'author' => 'Mallo');

        $this->filesystem
            ->shouldReceive('write')->with('bower.json', $json)->andReturn(10)
        ;

        $this->bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('install')->with($package)
        ;

        $this->bowerphp->installPackage($package, $installer);
    }

    public function testInstallDependencies()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $installer
            ->shouldReceive('install');
        ;

        $this->bowerphp->installDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallDependenciesException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $this->bowerphp->installDependencies($installer);
    }

    public function testUpdatePackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "Foo", "dependencies": {"less": "*"}}';


        $package
            ->shouldReceive('getName')->andReturn('less')
            ->shouldReceive('setVersion')->with('*')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $installer
            ->shouldReceive('update')->with($package)
        ;

        $this->bowerphp->updatePackage($package, $installer);
    }

    public function testUpdateDependencies()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $installer
            ->shouldReceive('update')
        ;

        $this->bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateDependenciesException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $this->bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdatePackageException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower.json')->andReturn($json)
        ;

        $this->bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateWithoutBowerJsonException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower.json')->andReturn(false)
        ;

        $this->bowerphp->updateDependencies($installer);
    }
}
