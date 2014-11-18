<?php
namespace Bowerphp\Test\Package;

use Bowerphp\Package\Lookup;
use Bowerphp\Test\HttpMockedTestCase;

class LookupTest extends HttpMockedTestCase
{
    public function testLookupForPackage()
    {
        //given
        $packageJson = '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}';
        $this->prepareRequest('jquery', $packageJson);
        $lookup = new Lookup($this->config, $this->httpClient);

        //when
        $package = $lookup->package('jquery');

        //then
        $this->assertEquals('jquery', $package['name']);
        $this->assertEquals('git://github.com/jquery/jquery.git', $package['url']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package jquery has malformed json or is missing "url".
     */
    public function testThrowExceptionWhenUrlIsEmpty()
    {
        //given
        $packageJson = '{"name":"jquery","url":""}';
        $this->prepareRequest('jquery', $packageJson);
        $lookup = new Lookup($this->config, $this->httpClient);

        //when
        $lookup->package('jquery');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package jquery has malformed json or is missing "url".
     */
    public function testThrowExceptionWhenJsonIsMalformed()
    {
        //given
        $packageJson = '';
        $this->prepareRequest('jquery', $packageJson);
        $lookup = new Lookup($this->config, $this->httpClient);

        //when
        $lookup->package('jquery');
    }
}
