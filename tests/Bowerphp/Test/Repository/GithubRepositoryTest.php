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
        $this->repository = new GithubRepository('https://raw.github.com/components/jquery', $this->httpClient);
    }

    public function testGetBower()
    {
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $this->mockRequest(0, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJson, $request, $response);


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
}