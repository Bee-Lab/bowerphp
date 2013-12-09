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