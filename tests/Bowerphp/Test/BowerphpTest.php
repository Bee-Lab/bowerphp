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
    "dependencies": [

    ]
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

        $json = '{"name": "jquery", "version": "1.6", "dependencies": }';
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with(getcwd() . '/bower.json')
            ->will($this->returnValue($json))
        ;

        $this->bowerphp->installDependencies($installer);
    }
}
