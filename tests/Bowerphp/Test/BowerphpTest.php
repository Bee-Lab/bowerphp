<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;

class BowerphpTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem, $httpClient, $githubClient, $repo, $bowerphp;

    public function setUp()
    {
        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')->disableOriginalConstructor()->getMock();
        $this->httpClient = $this->getMock('Guzzle\Http\ClientInterface');
        $this->githubClient = $this->getMockBuilder('Github\Client')->disableOriginalConstructor()->getMock();
        $this->bowerphp = new Bowerphp($this->filesystem, $this->httpClient);
        $this->repo = $this->getMockBuilder('Github\Api\Repo')->disableOriginalConstructor()->getMock();
    }

    public function testInit()
    {
        $json =<<<EOT
{
    "name": "Foo",
    "authors": [
        "Beelab <info@bee-lab.net>",
        "Mallo"
    ],
    "private": true,
    "dependencies": [

    ]
}
EOT;
        $params = array('name' => 'Foo', 'author' => 'Mallo');
        $this->filesystem
            ->expects($this->once())
            ->method('write')
            ->with('bower.json', $json)
            ->will($this->returnValue(10))
        ;
        $this->bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $json = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery', $json, $request, $response);
        $this->mockRequest(1, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJson, $request, $response);

        $this->mockRepo(0, 'components', 'jquery', array(array('name' => '1.0.0', 'tarball_url' => '')));
        $this->bowerphp->setGithubClient($this->githubClient);
        $this->bowerphp->installPackage('jquery');
    }

    public function testInstallPackageWithDependency()
    {
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $jsonUI = '{"name":"jquery-ui","url":"git://github.com/components/jqueryui.git"}';
        $bowerJsonUI = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';
        $json = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/jquery-ui', $jsonUI, $request, $response);
        $this->mockRequest(1, 'https://raw.github.com/components/jqueryui/master/bower.json', $bowerJsonUI, $request, $response);
        $this->mockRequest(2, 'http://bower.herokuapp.com/packages/jquery', $json, $request, $response);
        $this->mockRequest(3, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJson, $request, $response);

        $this->mockRepo(0, 'components', 'jqueryui', array(array('name' => '1.0.0', 'tarball_url' => '')));
        $this->mockRepo(1, 'components', 'jquery', array(array('name' => '1.6.1', 'tarball_url' => '')));
        $this->bowerphp->setGithubClient($this->githubClient);
        $this->bowerphp->installPackage('jquery-ui');
    }

    public function testInstallDependencies()
    {
        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')->disableOriginalConstructor()->getMock();

        $jsonL = '{"name":"less","url":"git://github.com/less/less.git"}';
        $bowerJsonL = '{"name": "less", "version": "1.5.0", "main": ["./dist/less-1.5.0.js"]}';
        $jsonJQ = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJQ = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $bower = json_encode(array('dependencies' => array('less' => '*', 'jquery' => '*')));
        $this->filesystem
            ->expects($this->once())
            ->method('read')
            ->with('bower.json')
            ->will($this->returnValue($bower))
        ;
        $this->mockRequest(0, 'http://bower.herokuapp.com/packages/less', $jsonL, $request, $response);
        $this->mockRequest(1, 'https://raw.github.com/less/less/master/bower.json', $bowerJsonL, $request, $response);
        $this->mockRequest(2, 'http://bower.herokuapp.com/packages/jquery', $jsonJQ, $request, $response);
        $this->mockRequest(3, 'https://raw.github.com/components/jquery/master/bower.json', $bowerJsonJQ, $request, $response);

        $this->mockRepo(0, 'less', 'less', array(array('name' => '1.0.0', 'tarball_url' => '')));
        $this->mockRepo(1, 'components', 'jquery', array(array('name' => '1.0.0', 'tarball_url' => '')));
        $this->bowerphp->setGithubClient($this->githubClient);
        $this->bowerphp->installDependencies();
    }

    public function testClearUrl()
    {
        $clearGitURL = self::getMethod('clearGitURL');

        $this->assertEquals('components/jquery', $clearGitURL->invokeArgs($this->bowerphp, array('git://github.com/components/jquery.git')));
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
    protected function mockRequest($n = 0, $url, $json, \PHPUnit_Framework_MockObject_MockObject $request, \PHPUnit_Framework_MockObject_MockObject $response)
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
            ->with(true)
            ->will($this->returnValue($json))
        ;
    }

    /**
     * Mock repo
     *
     * @param integer $n
     * @param string  $repoUser
     * @param string  $repoName
     * @param array   $tags
     */
    protected function mockRepo($n = 0, $repoUser, $repoName, array $tags)
    {
        $this->githubClient
            ->expects($this->at($n))
            ->method('api')
            ->with('repo')
            ->will($this->returnValue($this->repo))
        ;
        $this->repo
            ->expects($this->at($n))
            ->method('tags')
            ->with($repoUser, $repoName)
            ->will($this->returnValue($tags))
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
