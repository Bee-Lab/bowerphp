<?php
namespace Bowerphp\Test\Package;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Package\Search;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Mockery;
use PHPUnit_Framework_TestCase;

class SearchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var ConfigInterface
     */
    private $config;

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

    /**
     * @test
     */
    public function shouldSearchPackages()
    {
        //given
        $packagesJson = '[{"name":"jquery","url":"git://github.com/jquery/jquery.git"},{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}]';

        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andReturn($this->request);
        $this->response->shouldReceive('getBody')->andReturn($packagesJson);

        $search = new Search($this->config, $this->httpClient);

        //when
        $packages = $search->package('jquery');

        //then
        $this->assertCount(2, $packages);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot get package list from http://bower.herokuapp.com.
     */
    public function shouldThrowExceptionWhenCanNotGetPackage()
    {
        //given
        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andThrow(new RequestException());
        $search = new Search($this->config, $this->httpClient);

        //when
        $search->package('jquery');
    }
}
