<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;
use Bowerphp\Test\TestCase;

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
            ->expects($this->once())
            ->method('write')
            ->with('bower.json', $json)
            ->will($this->returnValue(10))
        ;
        $this->bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $package = $this->getMock('Bowerphp\Package\PackageInterface');
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->expects($this->once())
            ->method('install')
            ->with($package)
        ;

        $this->bowerphp->installPackage($package, $installer);
    }

    public function testInstallDependencies()
    {
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;
        $installer
            ->expects($this->once())
            ->method('install')
        ;

        $this->bowerphp->installDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallDependenciesException()
    {
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;

        $this->bowerphp->installDependencies($installer);
    }

    public function testUpdatePackage()
    {
        $json = '{"name": "Foo", "dependencies": {"less": "*"}}';

        $package = $this->getMock('Bowerphp\Package\PackageInterface');
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('less'))
        ;
        $package
            ->expects($this->once())
            ->method('setVersion')
            ->with('*')
        ;

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;

        $installer
            ->expects($this->once())
            ->method('update')
            ->with($package)
        ;

        $this->bowerphp->updatePackage($package, $installer);
    }

    public function testUpdateDependencies()
    {
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;
        $installer
            ->expects($this->once())
            ->method('update')
        ;

        $this->bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateDependenciesException()
    {
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;

        $this->bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdatePackageException()
    {
        $package = $this->getMock('Bowerphp\Package\PackageInterface');
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(true))
        ;
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;

        $this->bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateWithoutBowerJsonException()
    {
        $installer = $this->getMock('Bowerphp\Installer\InstallerInterface');

        $this->filesystem
            ->expects($this->once())
            ->method('has')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue(false))
        ;

        $this->bowerphp->updateDependencies($installer);
    }
}
