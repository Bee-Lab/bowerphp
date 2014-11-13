<?php
namespace Bowerphp\Test\Package;

use Bowerphp\Package\Search;
use Bowerphp\Test\HttpMockedTestCase;
use Guzzle\Http\Exception\RequestException;
use Mockery;

class SearchTest extends HttpMockedTestCase
{
    public function testSearchPackages()
    {
        //given
        $packagesJson = '[{"name":"jquery","url":"git://github.com/jquery/jquery.git"},{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}]';
        $this->prepareRequest('search/jquery', $packagesJson);
        $search = new Search($this->config, $this->httpClient);

        //when
        $packages = $search->package('jquery');

        //then
        $this->assertCount(2, $packages);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot get package list from http://bower.herokuapp.com.
     */
    public function testThrowExceptionWhenCanNotGetPackage()
    {
        //given
        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andThrow(new RequestException());
        $search = new Search($this->config, $this->httpClient);

        //when
        $search->package('jquery');
    }
}
