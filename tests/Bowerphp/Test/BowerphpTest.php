<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;
use Guzzle\Http\Exception\RequestException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

/**
 * @group bowerphp
 */
class BowerphpTest extends TestCase
{
    protected $bowerphp;
    protected $config;
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');
        $this->repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $this->output = Mockery::mock('Bowerphp\Output\BowerphpConsoleOutput');

        $this->config
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/')
            ->shouldReceive('getInstallDir')->andReturn(getcwd() . '/bower_components')
            ->shouldReceive('getCacheDir')->andReturn('.')
        ;
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
            ->shouldReceive('initBowerJsonFile')->with($params)->andReturn(123)
            ->shouldReceive('bowerFileExists')->andReturn(false, true)
            ->shouldReceive('getBowerFileContent')->andReturn(array('name' => 'Bar'))
            ->shouldReceive('setSaveToBowerJsonFile')->with(true)
            ->shouldReceive('updateBowerJsonFile2')->with(array('name' => 'Bar'), $params)->andReturn(456)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->init($params);
        $bowerphp->init($params);
    }

    public function testInstallPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $this->installPackage($package, $installer, array('jquery'), array('2.0.3'));

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery', "fileAsString...")
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testDoNotInstallAlreadyInstalledPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
            ->shouldReceive('setInfo')->with(array('name' => 'jquery', 'version' => '2.0.3', 'main'=>'jquery.js'))
        ;

        $this->config
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery', 'version' => '2.0.3'))
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('2.0.3')
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

        $installer
            ->shouldReceive('install')->never()
        ;

        #$this->filesystem
        #    ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
        #    ->shouldReceive('dumpFile')->with('./tmp/jquery', "fileAsString...", 0644)
        #;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallPackageAndSaveBowerJson()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(true)
            ->shouldReceive('updateBowerJsonFile')->with($package)
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery', "fileAsString...")
        ;

        $this->installPackage($package, $installer, array('jquery'), array('2.0.3'));

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallPackageAndSaveBowerJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(true)
            ->shouldReceive('updateBowerJsonFile')->with($package)->andThrow(new RuntimeException())
        ;

        $this->output
            ->shouldReceive('writelnNoBowerJsonFile');
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery', "fileAsString...")
        ;

        $this->installPackage($package, $installer, array('jquery'), array('2.0.3'));

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallDependencies()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        #$this->installPackage($package, $installer, array('jquery-ui', 'jquery'), array('1.10.1', '2.0.3'), array('>=1.6', '*'));
        #$json = array('name' => 'jquery-ui', 'dependencies' => array('jquery' => '>=1.6'));

        $this->installPackage($package, $installer, array('jquery'), array('2.0.1'), array('>=1.6'));
        $json = array('name' => 'pippo', 'dependencies' => array('jquery' => '>=1.6'));

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn($json)
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery', "fileAsString...")
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
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
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException(sprintf('Malformed JSON')));;
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installDependencies($installer);
    }

    public function testUpdatePackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJson = '{"name": "Foo", "dependencies": {"less": "*"}}';

        $this->installPackage($package, $installer, array('less'), array('1.2.1'), array(null), true, array('1.2.3'));

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($bowerJson, true))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Package notinbowerjson not found in bower.json.
     */
    public function testUpdatePackageNotFoundInBowerJson()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJson = '{"name": "Foo", "dependencies": {"less": "*"}}';
        $packageJson = '{"name":"less","url":"git://github.com/less/less.git","version":"1.2.1"}';

        $package
            ->shouldReceive('getName')->andReturn('notinbowerjson')
            ->shouldReceive('getRequiredVersion')->andReturn(null)
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/notinbowerjson/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($bowerJson, true))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot download package jquery (error).
     */
    public function testUpdatePackageNotFoundInRepository()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
            ->shouldReceive('setInfo')->with(array('name' => 'jquery', 'version' => '1.0.0'))
            ->shouldReceive('setVersion')->with('1.0.0')
            ->shouldReceive('setRequires')->with(null)
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery', 'version' => '1.0.0'))
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andThrow(new RequestException('error'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testUpdatePackageJsonException()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'colorbox', 'version' => '1.0.0'))
        ;

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
            ->shouldReceive('setInfo')->with(array('name' => 'colorbox', 'version' => '1.0.0'))
            ->shouldReceive('setVersion')->with('1.0.0')
            ->shouldReceive('setRequires')->with(null)
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid bower.json found in package colorbox: {invalid.
     */
    public function testUpdatePackageWithInvalidBowerJson()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'colorbox', 'version' => '1.0.0'))
        ;

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
            ->shouldReceive('setInfo')->with(array('name' => 'colorbox', 'version' => '1.0.0'))
            ->shouldReceive('setVersion')->with('1.0.0')
            ->shouldReceive('setRequires')->with(null)
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
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{invalid')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    public function testUpdateDependenciesWithNewPackageToInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array('dependencies' => array('jquery-ui' => '*')))
        ;

        $packageJsonJqueryUI = '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}';
        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
        $packageJsonJquery = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJquery = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui', 'jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*', '*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
        ;

        $this->filesystem
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;
        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonJqueryUI, $packageJsonJquery)
        ;
        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJsonJqueryUI, $bowerJsonJquery)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
            ->shouldReceive('writelnUpdatingPackage')
        ;
        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery-ui', 'version' => '1.10.0'))
        ;

        $installer
            ->shouldReceive('update')
            ->shouldReceive('install')
        ;

        $package
            ->shouldReceive('setRequiredVersion')
            ->shouldReceive('setRequires')
            ->shouldReceive('getVersion')->andReturn('1.10.0')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn('{"name":"jquery-ui","version":"1.10.0"}')
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackages();
    }

    public function testUpdateDependencies()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array('dependencies' => array('jquery' => '*')))
        ;

        $this->installPackage($package, $installer, array('jquery'), array('2.0.1'), array('*'), true, array('2.0.3'));

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackages();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Malformed JSON
     */
    public function testUpdatePackageWithMalformedBowerJsonContent()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $packageJson = '{"name":"less","version":"1.2.1"}';
        $json = '{"invalid json';

        $package
            ->shouldReceive('getName')->andReturn('less')
            ->shouldReceive('getRequiredVersion')->andReturn(null, '*')
            ->shouldReceive('getVersion')->andReturn('1.2.1')
            ->shouldReceive('setInfo')
            ->shouldReceive('setRequires')->with(null)
            ->shouldReceive('setRequiredVersion')->with('*')
            ->shouldReceive('setVersion')->with('1.2.1')
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException(sprintf('Malformed JSON')));;
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn($packageJson)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot find package colorbox version 2.*.
     */
    public function testUpdatePackageVersionNotFound()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'colorbox', 'version' => '1.0.0'))
        ;

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('2.*')
            ->shouldReceive('setInfo')->with(array('name' => 'colorbox', 'version' => '1.0.0'))
            ->shouldReceive('setVersion')->with('1.0.0')
            ->shouldReceive('setRequires')->with(null)
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
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{"name":"colorbox"}')
            ->shouldReceive('findPackage')->andReturn(null)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package);
    }

    public function testGetPackageInfo()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
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
            ->shouldReceive('getOriginalUrl')->andReturn('git://github.com/jackmoore/colorbox.git')
            ->shouldReceive('setUrl')->with('git://github.com/jackmoore/colorbox.git', false)
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
            ->shouldReceive('getBower')->with('1.1.0', true, "git://github.com/jackmoore/colorbox.git")->andReturn('a json...')
            ->shouldReceive('getTags')->andReturn(array('1.1.0', '1.0.0-rc1', '1.0.0', '1.0.0-beta'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);

        $this->assertEquals('https://github.com/jackmoore/colorbox', $bowerphp->getPackageInfo($package));
        $this->assertEquals('a json...', $bowerphp->getPackageInfo($package, 'bower'));
        $this->assertEquals(array('1.1.0', '1.0.0', '1.0.0-rc1', '1.0.0-beta'), $bowerphp->getPackageInfo($package, 'versions'));
    }

    public function testReturnLookupForPackage()
    {
        //given
        //FIXME copy-paste from method BowerphpTest::testGetPackageInfo() extract to method
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
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
            ->shouldReceive('getOriginalUrl')->andReturn('git://github.com/jackmoore/colorbox.git')
            ->shouldReceive('setUrl')->with('git://github.com/jackmoore/colorbox.git', false)
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
            ->shouldReceive('getBower')->with('1.1.0', true, "git://github.com/jackmoore/colorbox.git")->andReturn('a json...')
            ->shouldReceive('getTags')->andReturn(array('1.1.0', '1.0.0'))
        ;
        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);

        //when
        $package = $bowerphp->lookupPackage('colorbox');

        //then
        $this->assertEquals('colorbox', $package['name']);
        $this->assertEquals('git://github.com/jackmoore/colorbox.git', $package['url']);
    }

    public function testCreateAClearBowerFile()
    {
        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $expected = array('name' => '', 'authors' => array('Beelab <info@bee-lab.net>', 'pippo'), 'private' => true, 'dependencies' => new \StdClass());
        $method = $this->getMethod('Bowerphp\Bowerphp', 'createAClearBowerFile');
        $this->assertEquals($expected, $method->invokeArgs($bowerphp, array(array('name' => '', 'author' => 'pippo'))));
    }

    public function testSearchPackages()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $packagesJson = '[{"name":"jquery","url":"git://github.com/jquery/jquery.git"},{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}]';

        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andReturn($request);
        $request->shouldReceive('send')->andReturn($response);
        $response->shouldReceive('getBody')->andReturn($packagesJson);

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertEquals(json_decode($packagesJson, true), $bowerphp->searchPackages('jquery'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot get package list from http://bower.herokuapp.com.
     */
    public function testSearchPackagesException()
    {
        $this->config->shouldReceive('getBasePackagesUrl')->andReturn('http://example.com');

        $this->httpClient->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andThrow(new RequestException());

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->searchPackages('jquery');
    }

    public function testGetInstalledPackages()
    {
        $packages = array('a', 'b', 'c');
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('getInstalled')->with($finder)->andReturn($packages)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertEquals($packages, $bowerphp->getInstalledPackages($installer, $finder));
    }

    public function testInstallPackageWithDependenciesToInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonJqueryUI = '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}';
        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
        $packageJsonJquery = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJquery = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui', 'jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*', '*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('setRequiredVersion')
            ->shouldReceive('setRequires')
            ->shouldReceive('getVersion')->andReturn('1.10.0')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonJqueryUI, $packageJsonJquery)
        ;
        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJsonJqueryUI, $bowerJsonJquery)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;
        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
        ;

        $installer
            ->shouldReceive('install')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallPackageWithDependenciesToUpdate()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonJqueryUI = '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}';
        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"2.*"}}';
        $packageJsonJquery = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJquery = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui', 'jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*', '2.*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('setRequiredVersion')
            ->shouldReceive('setRequires')
            ->shouldReceive('getVersion')->andReturn('1.10.0', '1.0.0')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1')
            ->shouldReceive('findPackage')->with('2.*')->andReturn('2.0.3')
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonJqueryUI, $packageJsonJquery)
        ;
        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJsonJqueryUI, $bowerJsonJquery)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery', 'version' => '1.0.0'))
            ->shouldReceive('getBowerFileContent')->andReturn(array())
        ;

        $installer
            ->shouldReceive('install')
            ->shouldReceive('update')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot download package colorbox (error).
     */
    public function testInstallPackageRequestException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andThrow(new RequestException('error'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testInstallPackageJsonException()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid bower.json found in package colorbox: {invalid.
     */
    public function testInstallBowerJsonException()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('{"url":"http://example.org"}')
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{invalid')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot find package colorbox version *.
     */
    public function testInstallPackageVersionNotFound()
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andReturn($request)
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn('{"url":"http://example.org"}')
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{"name":"colorbox"}')
            ->shouldReceive('findPackage')->andReturn(null)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->installPackage($package, $installer);

    }

    public function testUpdateWithDependenciesToUpdate()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonJqueryUI = '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}';
        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
        $packageJsonJquery = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJquery = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui', 'jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*', '*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('setRequiredVersion')
            ->shouldReceive('setRequires')
            ->shouldReceive('getRequires')->andReturn(array('jquery' => '*'))
            ->shouldReceive('getVersion')->andReturn('1.10.0'. '2.0.1')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonJqueryUI, $packageJsonJquery)
        ;
        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJsonJqueryUI, $bowerJsonJquery)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
            ->shouldReceive('writelnUpdatingPackage')
        ;
        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery-ui', 'version' => '1.10.0'), array('name' => 'jquery', 'version' => '2.0.1'))
        ;

        $installer
            ->shouldReceive('update')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package jquery is not installed.
     */
    public function testUpdateUninstalledPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package);
    }

    public function testUpdateWithDependenciesToInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsonJqueryUI = '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}';
        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
        $packageJsonJquery = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJsonJquery = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui', 'jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*', '*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('setRequiredVersion')
            ->shouldReceive('setRequires')
            ->shouldReceive('getRequires')->andReturn(array('jquery' => '*'))
            ->shouldReceive('getVersion')->andReturn('1.10.0')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery-ui')->andReturn($request)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andReturn($request)
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
        ;
        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packageJsonJqueryUI, $packageJsonJquery)
        ;
        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn($bowerJsonJqueryUI, $bowerJsonJquery)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
            ->shouldReceive('writelnUpdatingPackage')
        ;
        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
            ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => 'jquery-ui', 'version' => '1.10.0'), array('name' => 'jquery', 'version' => '2.0.1'))
        ;

        $installer
            ->shouldReceive('update')
            ->shouldReceive('install')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output, $installer);
        $bowerphp->updatePackage($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unsupported info option "baz"
     */
    public function testGetPackageInfoInvalidInfo()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
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
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->getPackageInfo($package, 'baz');
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot download package colorbox (timeout).
     */
    public function testGetPackageInfoRequestException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
        ;

        $this->httpClient
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/colorbox')->andThrow(new RequestException('timeout'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->getPackageInfo($package);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testGetPackageInfoJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->getPackageInfo($package);
    }

    public function testIsPackageInstalled()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true, false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertTrue($bowerphp->isPackageInstalled($package));
        $this->assertFalse($bowerphp->isPackageInstalled($package));
    }

    public function testUninstallPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('uninstall')->with($package)
        ;

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->uninstallPackage($package, $installer);

    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package jquery is not installed.
     */
    public function testUninstallUninstalledPackage()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->uninstallPackage($package, $installer);
    }

    public function testIsPackageExtraneous()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array(), array('dependencies' => array('jquery' => '*')))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertTrue($bowerphp->isPackageExtraneous($package));
        $this->assertFalse($bowerphp->isPackageExtraneous($package));
    }

    public function testIsPackageExtraneousWithoutBowerJsonFile()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException())
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertTrue($bowerphp->isPackageExtraneous($package));
    }

    public function testIsPackageExtraneousWithCheck()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertFalse($bowerphp->isPackageExtraneous($package, true));
    }

    /**
     * Set expectations for installation of packages
     * Note: all array parameters must match counts
     *
     * @param MockInterface $package        mock of Package
     * @param MockInterface $installer      mock of Installer
     * @param array         $names          names of packages
     * @param array         $versions       versions of packages
     * @param array         $requires       required versions of packages
     * @param boolean       $update         if this is an update (instead of an install)
     * @param array         $updateVersions updated versions of packages (after update)
     */
    protected function installPackage(MockInterface $package, MockInterface $installer, array $names, array $versions, array $requires = array('*'), $update = false, array $updateVersions = array())
    {
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJsons = array();
        $bowerJsons = array();

        foreach ($names as $k => $v) {
            $packageJsons[] = '{"name":"' . $names[$k] . '","url":"git://github.com/components/' . $names[$k] . '.git"}';
            $bowerJsons[] = '{"name":"' . $names[$k] . '","version":"' . $versions[$k] . '", "main":"' . $names[$k] . '.js"}';
        }

        $package
            ->shouldReceive('getName')->andReturnValues($names)
            ->shouldReceive('getRequiredVersion')->andReturnValues($requires)
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')/*->with(json_encode($bowerJson))*/
            ->shouldReceive('setVersion')/*->with($versions)*/
        ;

        foreach ($names as $k => $v) {
            $this->filesystem
                ->shouldReceive('exists')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json')->andReturn($update)
                ->shouldReceive('write')->with('./tmp/' . $names[$k], 'fileAsString...')
                ->shouldReceive('write')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json', '{"name":"' . $names[$k] . '","version":"' . $versions[$k] . '"}')
            ;
            $this->httpClient
                ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $names[$k])->andReturn($request)
            ;
            $this->repository
                ->shouldReceive('findPackage')->with($requires[$k])->andReturn($versions[$k])
            ;
        }

        $request
            ->shouldReceive('send')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturnValues($packageJsons)
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturnValues($bowerJsons)
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;
        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
        ;

        $installer
            ->shouldReceive('install')
        ;

        if ($update) {
            $package
                ->shouldReceive('setRequiredVersion')
                ->shouldReceive('setRequires')
                ->shouldReceive('getVersion')->andReturnValues($versions)
            ;

            foreach ($names as $k => $v) {
                $this->config
                    ->shouldReceive('getPackageBowerFileContent')->andReturn(array('name' => $names[$k], 'version' => $versions[$k]))
                ;
                $this->filesystem
                    ->shouldReceive('write')->with('./tmp/' . $names[$k], 'fileAsString...')
                    ->shouldReceive('write')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json', '{"name":"' . $names[$k] . '","version":"' . $updateVersions[$k] . '"}')
                ;
            }
        }
    }
}
