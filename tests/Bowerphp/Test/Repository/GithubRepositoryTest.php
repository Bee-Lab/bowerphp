<?php

namespace Bowerphp\Test\Repository;

use Bowerphp\Repository\GithubRepository;
use Bowerphp\Test\TestCase;
use Guzzle\Http\Exception\RequestException;
use Mockery;
use ReflectionClass;

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
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';
        $url = 'https://raw.github.com/components/jquery/master/bower.json';

        $this->httpClient
            ->shouldReceive('get')->with($url)->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getEffectiveUrl')->andReturn($url)
            ->shouldReceive('getBody')->andReturn($bowerJson)
        ;

        $bower = $this->repository->getBower();

        $this->assertEquals($bower, $bowerJson);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetBowerPackageNotFound()
    {
        $url = 'https://raw.github.com/components/jquery/master/bower.json';

        $this->httpClient
            ->shouldReceive('get')->with($url)->andThrow(new RequestException())
        ;

        $bower = $this->repository->getBower();
    }

    public function testFindPackage()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';

        $this->httpClient
            ->shouldReceive('get')->with('https://api.github.com/repos/components/jquery/tags')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->with(true)->andReturn($tagsJson)
        ;

        $tag = $this->repository->findPackage();
        $this->assertEquals('2.0.3', $tag);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFindPackageVersionNotFound()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';

        $this->httpClient
            ->shouldReceive('get')->with('https://api.github.com/repos/components/jquery/tags')->andThrow($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->with(true)->andReturn($tagsJson)
        ;

        $tag = $this->repository->findPackage('3');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFindPackageRepoNotFound()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');

        $this->httpClient
            ->shouldReceive('get')->with('https://api.github.com/repos/components/jquery/tags')->andThrow(new RequestException())
        ;

        $tag = $this->repository->findPackage('3');
    }

    public function testGetRelease()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $this->setTag($this->repository, (array('zipball_url' => 'foo')));

        $this->httpClient
            ->shouldReceive('get')->with('foo')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('...')
        ;

        $this->repository->getRelease();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetReleaseFileNotFound()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');

        $this->setTag($this->repository, (array('zipball_url' => 'foo')));

        $this->httpClient
            ->shouldReceive('get')->with('foo')->andThrow(new RequestException())
        ;

        $this->repository->getRelease();
    }

    public function testGetUrl()
    {
        $this->assertEquals('https://raw.github.com/components/jquery', $this->repository->getUrl());
        $this->repository->setUrl('git://github.com/components/jquery-ui.git', false);
        $this->assertEquals('https://github.com/components/jquery-ui', $this->repository->getUrl());
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
        $this->assertEquals('1.*.*', $fixVersion->invokeArgs($this->repository, array('1')));
        $this->assertEquals('1.*.*', $fixVersion->invokeArgs($this->repository, array('1.*')));
        $this->assertEquals('1.5.*', $fixVersion->invokeArgs($this->repository, array('1.5')));
        $this->assertEquals('1.5.*', $fixVersion->invokeArgs($this->repository, array('1.5.*')));
        $this->assertEquals('*', $fixVersion->invokeArgs($this->repository, array(null)));
    }

    public function testGetTags()
    {
        $tagJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3"},
            {"name": "2.0.2", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.2"}]';

        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $this->httpClient
            ->shouldReceive('get')->with('https://api.github.com/repos/components/jquery/tags')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($tagJson)
        ;

        $this->assertEquals(array('2.0.3', '2.0.2'), $this->repository->getTags());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetTagsException()
    {
        $this->httpClient
            ->shouldReceive('get')->with('https://api.github.com/repos/components/jquery/tags')->andThrow(new RequestException())
        ;

        $this->repository->getTags();
    }

    /**
     * Set value for protected property $tag
     *
     * @param GithubRepository $repository
     * @param array            $value
     */
    protected function setTag(GithubRepository $repository, array $value)
    {
        $class = new ReflectionClass('Bowerphp\Repository\GithubRepository');
        $tag = $class->getProperty('tag');
        $tag->setAccessible(true);
        $tag->setValue($repository, $value);
    }
}
