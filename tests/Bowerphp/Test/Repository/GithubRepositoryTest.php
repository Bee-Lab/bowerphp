<?php

namespace Bowerphp\Test\Repository;

use Bowerphp\Repository\GithubRepository;
use Bowerphp\Test\TestCase;

class GithubRepositoryTest extends TestCase
{
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->repository = new GithubRepository();
        $this->repository->setUrl('https://raw.github.com/components/jquery')->setHttpClient($this->httpClient);
    }

    public function testGetBower()
    {
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $url = 'https://raw.github.com/components/jquery/master/bower.json';

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($url)
            ->will($this->returnValue($request))
        ;
        $request
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response))
        ;

        $response
            ->expects($this->once())
            ->method('getEffectiveUrl')
            ->will($this->returnValue($url))
        ;
        $response
            ->expects($this->once())
            ->method('getBody')
            ->with(true)
            ->will($this->returnValue($bowerJson))
        ;



        $bower = $this->repository->getBower();

        $this->assertEquals($bower, $bowerJson);
    }

    public function testFindPackage()
    {
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'https://api.github.com/repos/components/jquery/tags', $tagsJson, $request, $response);

        $tag = $this->repository->findPackage();
        $this->assertEquals('2.0.3', $tag);
    }

    public function testGetRelease()
    {
        $this->repository->setTag(array('zipball_url' => 'foo'));

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'foo', '...', $request, $response, false);

        $this->repository->getRelease();
    }

    public function testClearUrl()
    {
        $clearGitURL = $this->getMethod('Bowerphp\Repository\GithubRepository', 'clearGitURL');
        $this->assertEquals('components/jquery', $clearGitURL->invokeArgs($this->repository, array('git://github.com/components/jquery.git')));
        $this->assertEquals('components/jqueryui', $clearGitURL->invokeArgs($this->repository, array('git://github.com/components/jqueryui')));
    }

    public function testFixVersion()
    {
        $fixVersion = $this->getMethod('Bowerphp\Repository\GithubRepository', 'fixVersion');
        $this->assertEquals('1.9.*', $fixVersion->invokeArgs($this->repository, array('>=1.9')));
        $this->assertEquals('1.9.*', $fixVersion->invokeArgs($this->repository, array('>=1.9.0')));
        $this->assertEquals('1.9.*', $fixVersion->invokeArgs($this->repository, array('>= 1.9.0')));
    }
}
