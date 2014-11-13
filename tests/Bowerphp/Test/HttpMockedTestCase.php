<?php
namespace Bowerphp\Test;

use Bowerphp\Config\ConfigInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Mockery;
use PHPUnit_Framework_TestCase;

abstract class HttpMockedTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClientInterface
     */
    protected $httpClient;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var ConfigInterface
     */
    protected $config;

    protected function setUp()
    {
        parent::setUp();
        $this->httpClient = Mockery::mock('\Guzzle\Http\ClientInterface');
        $this->request = Mockery::mock('\Guzzle\Http\Message\RequestInterface');
        $this->response = Mockery::mock('\Guzzle\Http\Message\Response');
        $this->request->shouldReceive('send')->andReturn($this->response);

        $this->config = Mockery::mock('\Bowerphp\Config\ConfigInterface');
        $this->config->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/');
    }

    protected function prepareRequest($query, $packageJson)
    {
        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $query)->andReturn($this->request);
        $this->response->shouldReceive('getBody')->andReturn($packageJson);
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
