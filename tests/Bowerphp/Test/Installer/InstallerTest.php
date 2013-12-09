<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;

class InstallerTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem, $httpClient, $installer;

    public function setUp()
    {
        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $this->httpClient = $this->getMock('Guzzle\Http\ClientInterface');
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
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery', $json, $request, $response);
        $this->mockRequest(1, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJson, $request, $response);
        // TODO here json returned is $bowserJson instead of $tagsJson :-|
        $this->mockRequest(2, 'https://api.github.com/repos/components/jquery/tags', $tagsJson, $request, $response);

        $this->installer->install($package);
    }


    /**
     * Mock request
     *
     * @param integer                                 $n
     * @param string                                  $url
     * @param string                                  $json
     * @param PHPUnit_Framework_MockObject_MockObject $request
     * @param PHPUnit_Framework_MockObject_MockObject $response
     */
    protected function mockRequest($n = 0, $url, $json, \PHPUnit_Framework_MockObject_MockObject $request, \PHPUnit_Framework_MockObject_MockObject $response)
    {
        $this->httpClient
            ->expects($this->at($n))
            ->method('get')
            ->with($url)
            ->will($this->returnValue($request))
        ;
        $request
            ->expects($this->at($n))
            ->method('send')
            ->will($this->returnValue($response))
        ;
        $response
            ->expects($this->at($n))
            ->method('getBody')
            ->with(true)
            ->will($this->returnValue($json))
        ;
    }

    /**
     * @param  string           $name
     * @return ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('\Bowerphp\Bowerphp');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}