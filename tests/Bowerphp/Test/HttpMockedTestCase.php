<?php

namespace Bowerphp\Test;

use Mockery;

abstract class HttpMockedTestCase extends BowerphpTestCase
{
    protected $httpClient;

    protected $request;

    protected $response;

    protected $config;

    protected function setUp()
    {
        parent::setUp();
        $this->httpClient = Mockery::mock('Github\HttpClient\HttpClientInterface');
        $this->response = Mockery::mock('Guzzle\Http\Message\Response');
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');

        $this->config
            ->shouldReceive('getOverridesSection')->andReturn([])
            ->shouldReceive('getOverrideFor')->andReturn([])
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/')
        ;
    }

    protected function prepareRequest($query, $packageJson)
    {
        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $query)->andReturn($this->response)
        ;
        $this->response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;
    }
}
