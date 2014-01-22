<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;
use Bowerphp\Test\TestCase;
use Guzzle\Http\Exception\RequestException;
use Mockery;

class BowerphpTest extends TestCase
{
    protected $bowerphp;

    public function setUp()
    {
        parent::setUp();
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');
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
    "dependencies": {

    }
}
EOT;
        $params = array('name' => 'Foo', 'author' => 'Mallo');

        $this->config
            ->shouldReceive('getBowerFileName')->andReturn('bower.json')
            ->shouldReceive('initBowerJsonFile')->with($params)->andReturn(123)
            ->shouldReceive('bowerFileExists')->andReturn(false, true)
            ->shouldReceive('getBowerFileContent')->andReturn(array('name' => 'Bar'))
            ->shouldReceive('setSaveToBowerJsonFile')->with(true)
            ->shouldReceive('updateBowerJsonFile2')->with(array('name' => 'Bar'), $params)->andReturn(456)
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->init($params);
        $bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('install')->with($package)
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallDependencies()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = array('name' => 'jquery-ui', 'dependencies' => array('jquery' => '>=1.6'));

        $installer
            ->shouldReceive('install');
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn($json)
        ;
        $bowerphp = new Bowerphp($this->config);
        $bowerphp->installDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallDependenciesException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';
        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new \RuntimeException(sprintf('Malformed JSON')));;
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->installDependencies($installer);
    }

    public function testUpdatePackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "Foo", "dependencies": {"less": "*"}}';

        $package
            ->shouldReceive('getName')->andReturn('less')
            ->shouldReceive('setVersion')->with('*')
        ;

        $installer
            ->shouldReceive('update')->with($package)
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($json, true))
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUpdatePackageNotFound()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"name": "Foo", "dependencies": {"less": "*"}}';

        $package
            ->shouldReceive('getName')->andReturn('unexistant')
            ->shouldReceive('setVersion')->with('*')
        ;

        $installer
            ->shouldReceive('update')->with($package)
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($json, true))
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updatePackage($package, $installer);

    }

    public function testUpdateDependencies()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $json = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';

        $installer
            ->shouldReceive('update')
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($json,true))
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateDependenciesException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new \RuntimeException());
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdatePackageException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $json = '{"invalid json';

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new \RuntimeException(sprintf('Malformed JSON')));;
        ;
        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateWithoutBowerJsonException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new \RuntimeException(sprintf('Malformed JSON')))
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->updateDependencies($installer);
    }

    public function testGetPackageInfo()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('getPackageInfo');
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->getPackageInfo($package, $installer);
    }

    public function testCreateAClearBowerFile()
    {
        $bowerphp = new Bowerphp($this->config);
        $expected = array('name' => '', 'authors' => array('Beelab <info@bee-lab.net>', ''), 'private' => true, 'dependencies' => new \StdClass());
        $createAClearBowerFile = $this->getMethod('Bowerphp\Bowerphp', 'createAClearBowerFile');
        $this->assertEquals($expected, $createAClearBowerFile->invokeArgs($bowerphp, array(array('name' => '', 'author' => ''))));
    }

    public function testSearchPackages()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $packagesJson = '[{"name":"jquery"},{"name":"jquery-ui"},{"name":"less"}]';

        $this->config
            ->shouldReceive('getAllPackagesUrl')->andReturn('http://example.com')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://example.com')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packagesJson)
        ;

        $bowerphp = new Bowerphp($this->config);
        $this->assertEquals(array('jquery', 'jquery-ui'), $bowerphp->searchPackages($this->httpClient, 'jquery'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot get package list from http://example.com.
     */
    public function testSearchPackagesException()
    {
        $this->config
            ->shouldReceive('getAllPackagesUrl')->andReturn('http://example.com')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://example.com')->andThrow(new RequestException)
        ;

        $bowerphp = new Bowerphp($this->config);
        $bowerphp->searchPackages($this->httpClient, 'jquery');
    }

    public function testGetInstalledPackages()
    {
        $packages = array('a', 'b', 'c');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('getInstalled')->andReturn($packages)
        ;

        $bowerphp = new Bowerphp($this->config);
        $this->assertEquals($packages, $bowerphp->getInstalledPackages($installer));
    }
}
