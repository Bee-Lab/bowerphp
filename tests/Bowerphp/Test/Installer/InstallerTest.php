<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;

class InstallerTest extends TestCase
{
    protected $installer;

    public function setUp()
    {
        parent::setUp();
        $this->installer = new Installer($this->filesystem, $this->httpClient);
    }

    public function testInstall()
    {
        $package = $this->getMock('Bowerphp\Package\PackageInterface');

        $package
            ->expects($this->once())
            ->method('setTargetDir')
            ->with('bower_components')
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


        $json = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery', $json, $request, $response);
        $this->mockRequest(1, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJson, $request, $response);
        $this->mockRequest(2, 'https://api.github.com/repos/components/jquery/tags', $tagsJson, $request, $response);
        $this->mockRequest(3, 'https://api.github.com/repos/components/jquery/zipball/2.0.3', '..', $request, $response, false);

        $this->installer->install($package);
    }
}