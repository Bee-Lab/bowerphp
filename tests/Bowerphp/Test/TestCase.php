<?php

namespace Bowerphp\Test;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $filesystem, $httpClient;

    public function setUp()
    {
        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $this->httpClient = $this->getMock('Guzzle\Http\ClientInterface');
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
    protected function mockRequest($n = 0, $url, $json, \PHPUnit_Framework_MockObject_MockObject $request, \PHPUnit_Framework_MockObject_MockObject $response, $bodyString = true)
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
            ->with($bodyString)
            ->will($this->returnValue($json))
        ;
    }

    /**
     * Mock Config
     */
    protected function mockConfig()
    {
        $this->config
            ->expects($this->any())
            ->method('getBasePackagesUrl')
            ->will($this->returnValue('http://bower.herokuapp.com/packages/'))
        ;
        $this->config
            ->expects($this->any())
            ->method('getInstallDir')
            ->will($this->returnValue(getcwd() . '/bower_components'))
        ;
        $this->config
            ->expects($this->any())
            ->method('getCacheDir')
            ->will($this->returnValue('.'))
        ;
    }

    /**
     *@param  string            $class
     * @param  string           $name
     * @return ReflectionMethod
     */
    protected function getMethod($class, $name)
    {
        $class = new \ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
