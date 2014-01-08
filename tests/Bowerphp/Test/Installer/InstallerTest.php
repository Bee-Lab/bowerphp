<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;
use Guzzle\Http\Exception\RequestException;
use Mockery;

class InstallerTest extends TestCase
{
    protected $installer, $repository, $zipArchive, $config, $output;

    public function setUp()
    {
        parent::setUp();

        $this->repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $this->zipArchive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');
        $this->output = Mockery::mock('Bowerphp\Output\BowerphpConsoleOutput');

        $this->installer = new Installer($this->filesystem, $this->httpClient, $this->repository, $this->zipArchive, $this->config, $this->output);

        $this->config
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/')
            ->shouldReceive('getInstallDir')->andReturn(getcwd() . '/bower_components')
            ->shouldReceive('getCacheDir')->andReturn('.')
        ;
    }

    public function testInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn('2.0.3')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $this->filesystem
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $bowerJson, true)
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('close')
        ;

        $this->config
            ->shouldReceive('getSaveToBowerJsonFile')->andReturn(false)
        ;
        $this->installer->install($package);
    }

    public function testInstallWithUpdatingBowerJsonFile()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn('2.0.3')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $this->filesystem
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $bowerJson, true)
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('close')
        ;

        $this->config
            ->shouldReceive('getSaveToBowerJsonFile')->andReturn(true)
            ->shouldReceive('updateBowerJsonFile')->andReturn(true)
        ;
        $this->installer->install($package);
    }

    public function testInstallPackageWithDependencies()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonUI = '{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}';
        $packageJsonJQ = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonUI = '{"name": "jquery-ui", "version": "1.10.3", "main": ["ui/jquery-ui.js"], "dependencies": {"jquery": ">=1.6"}}';
        $bowerJsonJQ = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery-ui')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->times(2)->andReturn($packageJsonUI, $packageJsonJQ)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->times(2)->andReturn($bowerJsonUI, $bowerJsonJQ)
            ->shouldReceive('findPackage')->with('*')->andReturn('*')
            ->shouldReceive('findPackage')->with('>=1.6')->andReturn('*')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $this->filesystem
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...', true)
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', $bowerJsonUI, true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $bowerJsonJQ, true)
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery-ui')->andReturn(true)
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('close')
        ;

        $this->config
            ->shouldReceive('getSaveToBowerJsonFile')->andReturn(false)
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallRequestException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('foo')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/foo')->andThrow(new RequestException);
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallBowerJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{invalid';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallVersionNotFoundException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn(null)
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstallZipOpenException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0.3", "main": "jquery.js"}';

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setRepository')->with($this->repository)
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn('2.0.3')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $this->filesystem
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(false)
        ;

        $this->installer->install($package);
    }

    public function testUpdateToSpecificVersionPackageAlreadyAtThatVersion()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn('{"name": "jquery", "version": "1.10.2"}')
        ;

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('1.10.2')
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->installer->update($package);
    }

    public function testUpdateToSpecificVersionPackageAtOlderVersion()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "2.0", "main": "jquery.js"}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn('{"name": "jquery", "version": "1.4"}')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)->andReturn(123)
        ;

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('1.5')
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('1.5')->andReturn('1.5.2')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('close')
        ;

        $this->installer->update($package);
    }

    public function testUpdateToLatestVersionPackageNeeded()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "1.5.3", "main": "jquery.js"}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn('{"name": "jquery", "version": "1.4.1"}')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)->andReturn(123)
        ;

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn('1.5.3')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('close')
        ;

        $this->installer->update($package);
    }

    public function testUpdateToLatestVersionPackageNotNeeded()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name": "jquery", "version": "1.4.1"}';

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn('{"name": "jquery", "version": "1.4.1"}')
        ;

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJson)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJson)
            ->shouldReceive('findPackage')->with('*')->andReturn('1.4.1')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->installer->update($package);
    }

    public function testUpdateWithOldDependenciesToUpdate()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonUI = '{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}';
        $packageJsonJQ = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonUI = '{"name": "jquery-ui", "version": "2.0.", "dependencies": {"jquery": "2.*"}}';
        $bowerJsonJQ = '{"name": "jquery", "version": "2.0.3"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui')
            ->shouldReceive('getVersion')->andReturn('*')
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery-ui/bower.json')->andReturn(true)
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery-ui/bower.json')->andReturn('{"name": "jquery-ui", "version": "1.0.0", "dependencies": {"jquery": "1.*"}}')
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn('{"name": "jquery", "version": "1.4"}')
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...', true)->andReturn(123)
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...', true)->andReturn(123)
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->times(2)->andReturn($packageJsonUI, $packageJsonJQ)
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonUI)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->times(2)->andReturn($bowerJsonUI, $bowerJsonJQ)
            ->shouldReceive('findPackage')->with('*')->andReturn('2.0.0')
            ->shouldReceive('findPackage')->with('2.*')->andReturn('2.0.3')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery-ui')->andReturn(true)
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(0)
            ->shouldReceive('getNameIndex')->with(0)->andReturn(true)
            ->shouldReceive('close')
        ;

        $this->installer->update($package);
    }

    public function testUpdateWithNewDependenciesToInstall()
    {
        $this->markTestIncomplete();
    }

    public function testGetPackageInfo()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getVersion')->andReturn('1.1')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}')
        ;

        $this->repository
            ->shouldReceive('setHttpClient')->with($this->httpClient)
            ->shouldReceive('getUrl')->andReturn('https://github.com/jackmoore/colorbox')
            ->shouldReceive('setUrl')->with('git://github.com/jackmoore/colorbox.git', false)
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
            ->shouldReceive('getBower')->with('1.1.0', true, "git://github.com/jackmoore/colorbox.git")->andReturn('a json...')
            ->shouldReceive('getTags')->andReturn(array('1.1.0', '1.0.0'))
        ;

        $this->assertEquals('https://github.com/jackmoore/colorbox', $this->installer->getPackageInfo($package));
        $this->assertEquals('a json...', $this->installer->getPackageInfo($package, 'bower'));
        $this->assertEquals(array('1.1.0', '1.0.0'), $this->installer->getPackageInfo($package, 'versions'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetPackageInfoInvalidInfo()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getVersion')->andReturn('1.1')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}')
        ;

        $this->repository
            ->shouldReceive('setHttpClient')->with($this->httpClient)
            ->shouldReceive('getUrl')->andReturn('https://github.com/jackmoore/colorbox')
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
        ;

        $this->installer->getPackageInfo($package, 'baz');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetPackageInfoRequestException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getVersion')->andReturn('1.1')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andThrow(new RequestException)
        ;

        $this->assertEquals('https://github.com/jackmoore/colorbox', $this->installer->getPackageInfo($package));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetPackageInfoJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getVersion')->andReturn('1.1')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('{invalid')
        ;

        $this->assertEquals('https://github.com/jackmoore/colorbox', $this->installer->getPackageInfo($package));
    }

    public function testFilterZipFiles()
    {
        $archive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $archive->shouldReceive('getNumFiles')->andReturn(6);
        $archive->shouldReceive('statIndex')->times(6)->andReturn(
            array('name' => 'foo', 'size' => 10),
            array('name' => 'foo.md', 'size' => 12),
            array('name' => 'foo.txt', 'size' => 13),
            array('name' => '.foo', 'size' => 3),
            array('name' => 'bower.json', 'size' => 3),
            array('name' => 'package.json', 'size' => 3)
        );
        $filterZipFiles = $this->getMethod('Bowerphp\Installer\Installer', 'filterZipFiles');
        $ignore = array('.*', '*.md', 'bower.json', 'package.json');
        $expect = array('foo', 'foo.txt');
        $this->assertEquals($expect, $filterZipFiles->invokeArgs($this->installer, array($archive, $ignore)));
    }

    public function testIsInstalled()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('setTargetDir')->with(getcwd() . '/bower_components')
            ->shouldReceive('getTargetDir')->andReturn(getcwd() . '/bower_components')
            ->shouldReceive('getName')->andReturn('colorbox')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true, false)
        ;

        $this->assertTrue($this->installer->isInstalled($package));
        $this->assertFalse($this->installer->isInstalled($package));
    }

    public function testUninstall()
    {
        $this->markTestIncomplete();
    }
}
