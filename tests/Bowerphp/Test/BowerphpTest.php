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
        $json = <<<EOT
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
        $this->mockLookup();

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

    public function testInstallPackageFromGithubEndPoint()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('https://github.com/ivaynberg/select2.git')
            ->shouldReceive('getRequiredVersion')->andReturn('3.5.1')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('getRequires')
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient')
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('3.5.1')->andReturn('3.5.1')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/select2/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/select2', "fileAsString...")
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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot find package select2 version 3.4.5.
     */
    public function testInstallPackageFromGithubEndPointVersionNotFound()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('https://github.com/ivaynberg/select2.git')
            ->shouldReceive('getRequiredVersion')->andReturn('3.4.5')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient')
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('3.4.5')->andReturn(null)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testDoNotInstallAlreadyInstalledPackage()
    {
        $this->mockLookup();

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $packageJson = '{"name":"jquery","url":"git://github.com/components/jquery.git"}';
        $bowerJson = '{"name":"jquery","version":"2.0.3", "main":"jquery.js"}';

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setVersion')->with('2.0.3')
            ->shouldReceive('setInfo')->with(array('name' => 'jquery', 'version' => '2.0.3', 'main' => 'jquery.js'))
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
        $this->mockLookup();

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
        $this->mockLookup();

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(true)
            ->shouldReceive('updateBowerJsonFile')->with($package)->andThrow(new RuntimeException())
        ;

        $this->output
            ->shouldReceive('writelnNoBowerJsonFile')
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
        $this->mockLookup();

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

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

    public function testInstallDependenciesWithGithubEndpoint()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('https://github.com/ivaynberg/select2.git')
            ->shouldReceive('getRequiredVersion')->andReturn('3.5.1')
            ->shouldReceive('setRepository')->with($this->repository)
            ->shouldReceive('setInfo')
            ->shouldReceive('setVersion')
            ->shouldReceive('getRequires')
        ;

        $this->repository
            ->shouldReceive('setUrl->setHttpClient')
        ;
        $this->repository
            ->shouldReceive('findPackage')->with('3.5.1')->andReturn('3.5.1')
            ->shouldReceive('getRelease')->andReturn('fileAsString...')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/select2/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/select2', "fileAsString...")
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;

        $json = array(
            'name' => 'pippo',
            'dependencies' => array('select2' => 'https://github.com/ivaynberg/select2.git#3.5.1'),
        );
        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn($json)
            ->shouldReceive('isSaveToBowerJsonFile')->andReturn(false)
        ;

        $installer
            ->shouldReceive('install')
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
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException(sprintf('Malformed JSON')))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installDependencies($installer);
    }

    public function testUpdatePackage()
    {
        $this->mockLookup('less', '{"name":"less","url":"git://github.com/less/less.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJson = '{"name": "Foo", "dependencies": {"less": "*"}}';

        $this->installPackage($package, $installer, array('less'), array('1.2.1'), array(null), true, array('1.2.3'));

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($bowerJson, true))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot download package jquery (error).
     */
    public function testUpdatePackageNotFoundInRepository()
    {
        $guzzle = Mockery::mock('Github\HttpClient\HttpClientInterface');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->httpClient
            ->shouldReceive('getHttpClient')->andReturn($guzzle)
        ;

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

        $guzzle
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/jquery')->andThrow(new RequestException('error'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testUpdatePackageJsonException()
    {
        $this->mockLookup('colorbox', '{invalid');

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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid bower.json found in package colorbox: {invalid.
     */
    public function testUpdatePackageWithInvalidBowerJson()
    {
        $this->mockLookup('colorbox', '{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}');

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

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{invalid')
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    public function testUpdateDependenciesWithNewPackageToInstall()
    {
        $this->mockLookups('jquery', 'jquery-ui', '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}', '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array('dependencies' => array('jquery-ui' => '*')))
        ;

        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
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

        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackages($installer);
    }

    public function testUpdateDependencies()
    {
        $this->mockLookup();

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array('dependencies' => array('jquery' => '*')))
        ;

        $this->installPackage($package, $installer, array('jquery'), array('2.0.1'), array('*'), true, array('2.0.3'));

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackages($installer);
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
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException(sprintf('Malformed JSON')))
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn($packageJson)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot find package colorbox version 2.*.
     */
    public function testUpdatePackageVersionNotFound()
    {
        $this->mockLookup('colorbox', '{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}');

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

        $this->repository
            ->shouldReceive('setUrl->setHttpClient');
        $this->repository
            ->shouldReceive('getBower')->andReturn('{"name":"colorbox"}')
            ->shouldReceive('findPackage')->andReturn(null)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    public function testGetPackageInfo()
    {
        $this->mockLookup('colorbox', '{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
        ;

        $this->repository
            ->shouldReceive('setHttpClient')->with($this->httpClient)
            ->shouldReceive('getUrl')->andReturn('https://github.com/jackmoore/colorbox')
            ->shouldReceive('setUrl')->with('git://github.com/jackmoore/colorbox.git', false)
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
            ->shouldReceive('getBower')->with('1.1.0', true, "git://github.com/jackmoore/colorbox.git")->andReturn('a json...')
            ->shouldReceive('getTags')->andReturn(array('1.1.0', '1.0.0-rc1', '1.0.0', '1.0.0-beta'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);

        $this->assertEquals('https://github.com/jackmoore/colorbox', $bowerphp->getPackageInfo($package));
        $this->assertEquals(array('1.1.0', '1.0.0', '1.0.0-rc1', '1.0.0-beta'), $bowerphp->getPackageInfo($package, 'versions'));

        //FIXME extract to another method
        $this->assertEquals('a json...', $bowerphp->getPackageBowerFile($package));
    }

    public function testReturnLookupForPackage()
    {
        $this->mockLookup('colorbox', '{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}');

        //given
        //FIXME copy-paste from method BowerphpTest::testGetPackageInfo() extract to method
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
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
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $guzzle = Mockery::mock('Github\HttpClient\HttpClientInterface');

        $packagesJson = '[{"name":"jquery","url":"git://github.com/jquery/jquery.git"},{"name":"jquery-ui","url":"git://github.com/components/jqueryui"}]';

        $this->httpClient
            ->shouldReceive('getHttpClient')->andReturn($guzzle)
        ;

        $guzzle
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andReturn($response)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($packagesJson)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertEquals(json_decode($packagesJson, true), $bowerphp->searchPackages('jquery'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot get package list from http://bower.herokuapp.com.
     */
    public function testSearchPackagesException()
    {
        $guzzle = Mockery::mock('Github\HttpClient\HttpClientInterface');

        $this->httpClient
            ->shouldReceive('getHttpClient')->andReturn($guzzle)
        ;

        $this->config
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://example.com')
        ;

        $guzzle
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/search/jquery')->andThrow(new RequestException())
        ;

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
        $this->mockLookups('jquery', 'jquery-ui', '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}', '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
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
            ->shouldReceive('getRequires')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
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
        $this->mockLookups('jquery', 'jquery-ui', '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}', '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"2.*"}}';
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
            ->shouldReceive('getRequires')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(false)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1')
            ->shouldReceive('findPackage')->with('2.*')->andReturn('2.0.3')
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
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testInstallPackageJsonException()
    {
        $this->mockLookup('colorbox', '{invalid');

        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
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
        $this->mockLookup('colorbox', '{"url":"http://example.org"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1')
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
        $this->mockLookup('colorbox', '{"url":"http://example.org"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
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
        $this->mockLookups('jquery', 'jquery-ui', '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}', '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
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
            ->shouldReceive('getVersion')->andReturn('1.10.0' . '2.0.1')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('write')->with('./tmp/jquery-ui', 'fileAsString...')
            ->shouldReceive('write')->with('./tmp/jquery', 'fileAsString...')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', '{"name":"jquery-ui","version":"1.10.1"}')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', '{"name":"jquery","version":"2.0.3"}')
        ;

        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
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
        $bowerphp->updatePackage($package, $installer);
    }

    public function testUpdateWithDependenciesToInstall()
    {
        $this->mockLookups('jquery', 'jquery-ui', '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}', '{"name":"jquery-ui","url":"git://github.com/components/jquery-ui.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJsonJqueryUI = '{"name":"jquery-ui","version":"1.10.1", "main":"jquery-ui.js","dependencies":{"jquery":"*"}}';
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

        $this->repository
            ->shouldReceive('findPackage')->with('*')->andReturn('1.10.1', '2.0.3')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unsupported info option "baz"
     */
    public function testGetPackageInfoInvalidInfo()
    {
        $this->mockLookup('colorbox', '{"name":"colorbox","url":"git://github.com/jackmoore/colorbox.git"}');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $request = Mockery::mock('Guzzle\Http\Message\RequestInterface');
        $response = Mockery::mock('Guzzle\Http\Message\Response');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
            ->shouldReceive('getRequiredVersion')->andReturn('1.1')
        ;

        $this->repository
            ->shouldReceive('setHttpClient')->with($this->httpClient)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->getPackageInfo($package, 'baz');
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Package colorbox has malformed json or is missing "url".
     */
    public function testGetPackageInfoJsonException()
    {
        $this->mockLookup('colorbox', '{invalid');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('colorbox')
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
            ->shouldReceive('getRequires')
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

    /**
     * Mock a package lookup
     *
     * @param string $lookedPackage
     * @param string $returnedJson
     */
    protected function mockLookup($lookedPackage = 'jquery', $returnedJson = '{"name":"jquery","url":"git://github.com/jquery/jquery.git"}')
    {
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $guzzle = Mockery::mock('Github\HttpClient\HttpClientInterface');

        $guzzle
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $lookedPackage)->andReturn($response)
        ;

        $this->httpClient
            ->shouldReceive('getHttpClient')->andReturn($guzzle)
        ;
        $response
            ->shouldReceive('getBody')->andReturn($returnedJson)
        ;
    }

    /**
     * Mock a 2-packages lookup
     *
     * @param string $lookedPackage1
     * @param string $lookedPackage2
     * @param string $returnedJson1
     * @param string $returnedJson2
     */
    protected function mockLookups($lookedPackage1, $lookedPackage2, $returnedJson1, $returnedJson2)
    {
        $response = Mockery::mock('Guzzle\Http\Message\Response');
        $guzzle = Mockery::mock('Github\HttpClient\HttpClientInterface');

        $guzzle
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $lookedPackage1)->andReturn($response)
            ->shouldReceive('get')->with('http://bower.herokuapp.com/packages/' . $lookedPackage2)->andReturn($response)
        ;

        $this->httpClient
            ->shouldReceive('getHttpClient')->andReturn($guzzle)
        ;

        $response
            ->shouldReceive('getBody')->andReturn($returnedJson1, $returnedJson2)
        ;
    }
}
