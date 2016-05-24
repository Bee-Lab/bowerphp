<?php

namespace Bowerphp\Test\Repository;

use Bowerphp\Repository\GithubRepository;
use Bowerphp\Repository\RepositoryInterface;
use Bowerphp\Test\TestCase;
use Mockery;
use ReflectionClass;

class GithubRepositoryTest extends TestCase
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    protected $guzzle;

    protected function setUp()
    {
        parent::setUp();

        $this->guzzle = Mockery::mock('Guzzle\Http\ClientInterface');
        $this->httpClient->shouldReceive('getHttpClient')->andReturn($this->guzzle);
        $this->guzzle->shouldReceive('setHeaders');

        $this->repository = new GithubRepository();
        $this->repository->setUrl('https://raw.githubusercontent.com/components/jquery')->setHttpClient($this->httpClient);
    }

    public function testGetBower()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(true)
            ->shouldReceive('download')->with('components', 'jquery', 'bower.json', 'master')->andReturn($bowerJson)
        ;

        $this->assertEquals($bowerJson, $this->repository->getBower());
        $this->assertEquals($bowerJson, $this->repository->getBower('master', false, 'https://raw.githubusercontent.com/components/jquery'));
    }

    public function testGetBowerWithByteMarkOrder()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';
        $bowerJsonWithBOM = "\xef\xbb\xbf" . $bowerJson;

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(true)
            ->shouldReceive('download')->with('components', 'jquery', 'bower.json', 'master')->andReturn($bowerJsonWithBOM)
        ;

        $this->assertEquals($bowerJson, $this->repository->getBower());
    }

    public function testGetBowerWithoutBowerJsonButWithPackageJson()
    {
        $this->markTestIncomplete('TODO to be fixed, second expectation on "exists" does not work');

        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';
        $expectedJson = '{
    "name": "jquery",
    "version": "2.0.3",
    "main": "jquery.js"
}';

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(false)->ordered()
            ->shouldReceive('exists')->with('components', 'jquery', 'package.json', 'master')->andReturn(true)->ordered()
            ->shouldReceive('download')->with('components', 'jquery', 'package.json', 'master')->andReturn($bowerJson)
        ;

        $this->assertEquals($expectedJson, $this->repository->getBower());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetBowerWithoutBowerJsonNorPackageJson()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(false)
            ->shouldReceive('exists')->with('components', 'jquery', 'package.json', 'master')->andReturn(false)
            ->shouldReceive('download')->with('components', 'jquery', 'package.json', 'master')->andThrow(new \RuntimeException())
        ;

        $this->repository->getBower();
    }

    /**
     * See issue https://github.com/Bee-Lab/bowerphp/issues/33
     * For some strange reason, Modernizr has package.json ONLY in master :-|
     */
    public function testGetBowerWithPackageJsonOnlyInMaster()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $originalJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js", "dependencies": {"foo": "bar"}}';
        $expectedJson = '{
    "name": "jquery",
    "version": "2.0.3",
    "main": "jquery.js"
}';

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'v2.7.2')->andReturn(false)->ordered()
            ->shouldReceive('exists')->with('components', 'jquery', 'package.json', 'v2.7.2')->andReturn(false)->ordered()
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(false)->ordered()
            ->shouldReceive('exists')->with('components', 'jquery', 'package.json', 'master')->andReturn(true)->ordered()
            ->shouldReceive('download')->with('components', 'jquery', 'package.json', 'master')->andReturn($originalJson)
        ;

        $this->assertEquals($expectedJson, $this->repository->getBower());
    }

    public function testGetBowerWithHomepage()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $bower1 = ['name' => 'jquery', 'version' => '2.0.3', 'main' => 'jquery.js'];
        $bower2 = ['name' => 'jquery', 'version' => '2.0.3', 'main' => 'jquery.js', 'homepage' => 'https://raw.githubusercontent.com/components/jquery'];

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('exists')->with('components', 'jquery', 'bower.json', 'master')->andReturn(true)
            ->shouldReceive('download')->with('components', 'jquery', 'bower.json', 'master')->andReturn(json_encode($bower1))
        ;

        $this->assertEquals($bower2, json_decode($this->repository->getBower('master', true), true));
        $this->assertEquals($bower2, json_decode($this->repository->getBower('master', true, 'https://raw.githubusercontent.com/components/jquery'), true));
    }

    public function testFindPackage()
    {
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($tagsJson);

        $tag = $this->repository->findPackage();
        $this->assertEquals('2.0.3', $tag);
    }

    public function testFindPackageWithoutTags()
    {
        $this->mockTagsRequest('[ ]');

        $tag = $this->repository->findPackage();
        $this->assertEquals('master', $tag);
    }

    /**
     * "version with v" = something like "v1.2.3" instead of "1.2.3"
     */
    public function testFindPackageWithVersionWithV()
    {
        $tagsJson = '[{"name": "v2.0.3", "zipball_url": "", "tarball_url": ""}, {"name": "v2.0.2", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($tagsJson);

        $tag = $this->repository->findPackage('v2.0.2');
        $this->assertEquals('v2.0.2', $tag);
    }

    public function testLatestPackage()
    {
        $tagsJson = '[{"name": "v2.0.3", "zipball_url": "", "tarball_url": ""}, {"name": "v2.0.2", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($tagsJson);

        $tag = $this->repository->findPackage('latest');
        $this->assertEquals('v2.0.3', $tag);
    }

    /**
     * "version with ~" = something like "~1.2.3"
     */
    public function testFindPackageWithVersionWithTilde()
    {
        $response = '[{"name": "2.1.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.5", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3-beta3", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3b1", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($response);

        $tag = $this->repository->findPackage('~2.0.3');
        $this->assertEquals('2.0.5', $tag);

        $tag = $this->repository->findPackage('~2.0');
        $this->assertEquals('2.0.5', $tag);

        $tag = $this->repository->findPackage('~2.1');
        $this->assertEquals('2.1.4', $tag);
    }

    /**
     * >1.2.3 AND <2.3.4
     */
    public function testFindPackageWithVersionWithCompund()
    {
        $response = '[{"name": "2.1.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.5", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3-beta3", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3b1", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($response);

        $tag = $this->repository->findPackage('>=2.0.3 <2.0.4');
        $this->assertEquals('2.0.3', $tag);

        $tag = $this->repository->findPackage('>2.0 <2.1.5');
        $this->assertEquals('2.1.4', $tag);

        $tag = $this->repository->findPackage('>2.0.3 <=2.0.4');
        $this->assertEquals('2.0.4', $tag);
    }

    /**
     * "version with any wildcard" = something like "1.2.x" or "1.2.X" or "1.2.*"
     */
    public function testFindPackageWithVersionWithWildcard()
    {
        $response = '[{"name": "2.1.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.5", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3-beta3", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3b1", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.2", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.1", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.0", "zipball_url": "", "tarball_url": ""}]'
        ;
        $this->mockTagsRequest($response);

        $wildcards = [
            'x',
            'X',
            '*',
        ];

        foreach ($wildcards as $wildcard) {
            $tag = $this->repository->findPackage('2.0.' . $wildcard);
            $this->assertEquals('2.0.5', $tag);

            $tag = $this->repository->findPackage('2.x.' . $wildcard);
            $this->assertEquals('2.1.4', $tag);
        }
    }

    /**
     * Add non-allowed characters, e.g., jquery's wonderful 1.8.3+1
     */
    public function testFindPackageWithVersionWithJunk()
    {
        $response = '[{"name": "2.1.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.5", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.4", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3+1", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3+3", "zipball_url": "", "tarball_url": ""}, '
            . '{"name": "2.0.3", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.2", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.1", "zipball_url": "", "tarball_url": ""},'
            . '{"name": "2.0.0", "zipball_url": "", "tarball_url": ""}]'
        ;
        $this->mockTagsRequest($response);

        $tag = $this->repository->findPackage('2.0.*');
        $this->assertEquals('2.0.5', $tag);

        $tag = $this->repository->findPackage('2.0.3');
        $this->assertEquals('2.0.3', $tag);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFindPackageVersionNotFound()
    {
        $tagsJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3", "tarball_url": ""}, {"name": "2.0.2", "zipball_url": "", "tarball_url": ""}]';
        $this->mockTagsRequest($tagsJson);

        $tag = $this->repository->findPackage('3');
    }

    public function testGetRelease()
    {
        $repo = Mockery::mock('Github\Api\Repo');
        $contents = Mockery::mock('Github\Api\Repository\Contents');

        $this->setTag($this->repository, (['name' => 'foo']));

        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;

        $repo
            ->shouldReceive('contents')->andReturn($contents)
        ;

        $contents
            ->shouldReceive('archive')->with('components', 'jquery', 'zipball', 'foo')->andReturn('...')
        ;

        $this->repository->getRelease();
    }

    public function testGetUrl()
    {
        $this->assertEquals('https://raw.githubusercontent.com/components/jquery', $this->repository->getUrl());
        $this->repository->setUrl('git://github.com/components/jquery-ui.git', false);
        $this->assertEquals('https://github.com/components/jquery-ui', $this->repository->getUrl());
    }

    public function testClearUrl()
    {
        $clearGitURL = $this->getMethod('Bowerphp\Repository\GithubRepository', 'clearGitURL');
        $this->assertEquals('components/jquery', $clearGitURL->invokeArgs($this->repository, ['git://github.com/components/jquery.git']));
        $this->assertEquals('components/jqueryui', $clearGitURL->invokeArgs($this->repository, ['git://github.com/components/jqueryui']));
        $this->assertEquals('MAXakaWIZARD/jquery.appear', $clearGitURL->invokeArgs($this->repository, ['git@github.com:MAXakaWIZARD/jquery.appear.git']));
        $this->assertEquals('components/jqueryui', $clearGitURL->invokeArgs($this->repository, ['https://github.com/components/jqueryui.git']));
        $this->assertEquals('components/jqueryui', $clearGitURL->invokeArgs($this->repository, ['https://github.com/components/jqueryui']));
        $this->assertEquals('components/jqueryui/master/jquery-ui.min.js', $clearGitURL->invokeArgs($this->repository, ['https://raw.githubusercontent.com/components/jqueryui/master/jquery-ui.min.js']));
    }

    public function testGetTags()
    {
        $tagJson = '[{"name": "2.0.3", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3"},
            {"name": "2.0.3+1", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.3+1"},
            {"name": "2.0.2", "zipball_url": "https://api.github.com/repos/components/jquery/zipball/2.0.2"}]';
        $this->mockTagsRequest($tagJson);

        $this->assertEquals(['2.0.2', '2.0.3'], $this->repository->getTags());
    }

    public function testGetTagsWithoutTags()
    {
        $this->mockTagsRequest('[ ]');

        $this->assertEquals([], $this->repository->getTags());
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

    /**
     * @param string $responseJson
     */
    private function mockTagsRequest($responseJson)
    {
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $repo = Mockery::mock('Github\Api\Repo');

        $repo
            ->shouldReceive('setPerPage')
            ->shouldReceive('getPerPage')->andReturn(30)
            ->shouldReceive('tags')->with('components', 'jquery')->andReturn(json_decode($responseJson, true))
        ;
        $this->httpClient
            ->shouldReceive('api')->with('repo')->andReturn($repo)
        ;
        $this->guzzle
            ->shouldReceive('getLastResponse')->andReturn($response)
        ;
        $response
            ->shouldReceive('getLastHeader')
            ->shouldReceive('getHeader')
        ;
    }
}
