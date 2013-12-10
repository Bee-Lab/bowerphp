<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;

class InstallerTest extends TestCase
{
    protected $installer, $repository, $zipArchive;

    public function setUp()
    {
        parent::setUp();
        $this->repository = $this->getMock('Bowerphp\Repository\RepositoryInterface');
        $this->zipArchive = $this->getMock('ZipArchive');
        $this->installer = new Installer($this->filesystem, $this->httpClient, $this->repository, $this->zipArchive);
    }

    public function testInstall()
    {
        $package = $this->getMock('Bowerphp\Package\PackageInterface');

        $package
            ->expects($this->once())
            ->method('setTargetDir')
            ->with(getcwd() . '/bower_components')
        ;
        $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('jquery'))
        ;
        $package
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('*'))
        ;

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery', $packageJson, $request, $response);

        $this->repository
            ->expects($this->once())
            ->method('setUrl')
            ->will($this->returnSelf())
        ;
        $this->repository
            ->expects($this->once())
            ->method('setHttpClient')
            ->with($this->httpClient)
            ->will($this->returnSelf())
        ;
        $this->repository
            ->expects($this->once())
            ->method('getBower')
            ->will($this->returnValue($bowerJson))
        ;
        $this->repository
            ->expects($this->once())
            ->method('findPackage')
            ->with('*')
            ->will($this->returnValue('2.0.3'))
        ;
        $this->repository
            ->expects($this->once())
            ->method('getRelease')
            ->will($this->returnValue('fileAsString...'))
        ;

        $this->zipArchive
            ->expects($this->once())
            ->method('open')
            ->with('./tmp/jquery')
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->once())
            ->method('getNameIndex')
            ->with(0)
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->once())
            ->method('close')
        ;

        $this->installer->install($package);
    }

    public function testInstallPackageWithDependencies()
    {
        $package = $this->getMock('Bowerphp\Package\PackageInterface');

        $package
            ->expects($this->once())
            ->method('setTargetDir')
            ->with(getcwd() . '/bower_components')
        ;
        $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('jquery-ui'))
        ;
        $package
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('*'))
        ;

        $packageJsonUI = '{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}';
        $packageJsonJQ = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonUI = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';
        $bowerJsonJQ = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery-ui', $packageJsonUI, $request, $response);

        $this->repository
            ->expects($this->at(0))
            ->method('setUrl')
            ->will($this->returnValue($this->repository))
        ;
        $this->repository
            ->expects($this->at(1))
            ->method('setHttpClient')
            ->with($this->httpClient)
            ->will($this->returnValue($this->repository))
        ;
        $this->repository
            ->expects($this->at(2))
            ->method('getBower')
            ->will($this->returnValue($bowerJsonUI))
        ;
        $this->repository
            ->expects($this->at(3))
            ->method('findPackage')
            ->with('*')
            ->will($this->returnValue('*'))
        ;
        $this->repository
            ->expects($this->at(4))
            ->method('getRelease')
            ->will($this->returnValue('fileAsString...'))
        ;

        $this->mockRequest(1, 'http://bower.herokuapp.com/packages/jquery', $packageJsonJQ, $request, $response);

        $this->repository
            ->expects($this->at(5))
            ->method('setUrl')
            ->will($this->returnValue($this->repository))
        ;
        $this->repository
            ->expects($this->at(6))
            ->method('setHttpClient')
            ->with($this->httpClient)
            ->will($this->returnValue($this->repository))
        ;
        $this->repository
            ->expects($this->at(7))
            ->method('getBower')
            ->will($this->returnValue($bowerJsonJQ))
        ;
        $this->repository
            ->expects($this->at(8))
            ->method('findPackage')
            ->with('>=1.6')
            ->will($this->returnValue('*'))
        ;
        $this->repository
            ->expects($this->at(9))
            ->method('getRelease')
            ->will($this->returnValue('fileAsString...'))
        ;

        $this->zipArchive
            ->expects($this->at(0))
            ->method('open')
            ->with('./tmp/jquery-ui')
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->at(1))
            ->method('getNameIndex')
            ->with(0)
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->at(2))
            ->method('close')
        ;
        $this->zipArchive
            ->expects($this->at(3))
            ->method('open')
            ->with('./tmp/jquery')
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->at(4))
            ->method('getNameIndex')
            ->with(0)
            ->will($this->returnValue(true))
        ;
        $this->zipArchive
            ->expects($this->at(5))
            ->method('close')
        ;

        $this->installer->install($package);
    }
}
