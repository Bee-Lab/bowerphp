<?php

namespace Bowerphp\Test;

use Bowerphp\Bowerphp;
use Bowerphp\Test\TestCase;
use Guzzle\Http\Exception\RequestException;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

/**
 * @group todo
 */
class BowerphpTest extends TestCase
{
    protected $bowerphp;

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
            ->shouldReceive('getBowerFileName')->andReturn('bower.json')
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);
    }

    public function testInstallDependencies()
    {
        $this->markTestIncomplete('TODO....');

        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        #$this->installPackage($package, $installer, array('jquery-ui', 'jquery'), array('1.10.1', '2.0.3'), array('>=1.6', '*'));
        #$json = array('name' => 'jquery-ui', 'dependencies' => array('jquery' => '>=1.6'));

        $this->installPackage($package, $installer, array('jquery'), array('2.0.1'), array('>=1.6'));
        $json = array('name' => 'pippo', 'dependencies' => array('jquery' => '>=1.6'));

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn($json)
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

        $this->installPackage($package, $installer, array('less'), array('1.2.1'), array('*'), true, array('1.2.3'));

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
    public function testUpdatePackageNotFound()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $bowerJson = '{"name": "Foo", "dependencies": {"less": "*"}}';
        $packageJson = '{"name":"less","url":"git://github.com/less/less.git","version":"1.2.1"}';

        $package
            ->shouldReceive('getName')->andReturn('notinbowerjson')
        ;

        $this->filesystem
            ->shouldReceive('has')->with(getcwd() . '/bower_components/notinbowerjson/.bower.json')->andReturn(true)
        ;

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(json_decode($bowerJson, true))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);

    }

    public function testUpdateDependencies()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andReturn(array('dependencies' => array('jquery' => '>=1.6')))
        ;

        $this->installPackage($package, $installer, array('jquery'), array('2.0.1'), array('>=1.6'), true, array('2.0.1'));

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
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
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException());
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updateDependencies($installer);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Malformed JSON
     */
    public function testUpdatePackageMalformedJsonException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $packageJson = '{"name":"less","version":"1.2.1"}';
        $json = '{"invalid json';

        $package
            ->shouldReceive('getName')->andReturn('less')
            ->shouldReceive('getRequiredVersion')->andReturn('*')
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
            ->shouldReceive('has')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/less/.bower.json')->andReturn($packageJson)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUpdateWithoutBowerJsonException()
    {
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $this->config
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException(sprintf('Malformed JSON')))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updateDependencies($installer);
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
            ->shouldReceive('setUrl')->with('git://github.com/jackmoore/colorbox.git', false)
            ->shouldReceive('findPackage')->with('1.1')->andReturn('1.1.0')
            ->shouldReceive('setUrl')->with('https://github.com/jackmoore/colorbox', true)
            ->shouldReceive('getBower')->with('1.1.0', true, "git://github.com/jackmoore/colorbox.git")->andReturn('a json...')
            ->shouldReceive('getTags')->andReturn(array('1.1.0', '1.0.0'))
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);

        $this->assertEquals('https://github.com/jackmoore/colorbox', $bowerphp->getPackageInfo($package));
        $this->assertEquals('a json...', $bowerphp->getPackageInfo($package, 'bower'));
        $this->assertEquals(array('1.1.0', '1.0.0'), $bowerphp->getPackageInfo($package, 'versions'));
    }

    public function testCreateAClearBowerFile()
    {
        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $expected = array('name' => '', 'authors' => array('Beelab <info@bee-lab.net>', 'pippo'), 'private' => true, 'dependencies' => new \StdClass());
        $createAClearBowerFile = $this->getMethod('Bowerphp\Bowerphp', 'createAClearBowerFile');
        $this->assertEquals($expected, $createAClearBowerFile->invokeArgs($bowerphp, array(array('name' => '', 'author' => 'pippo'))));
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertEquals(array('jquery', 'jquery-ui'), $bowerphp->searchPackages('jquery'));
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->searchPackages($this->httpClient, 'jquery');
    }

    public function testGetInstalledPackages()
    {
        $packages = array('a', 'b', 'c');
        $installer = Mockery::mock('Bowerphp\Installer\InstallerInterface');

        $installer
            ->shouldReceive('getInstalled')->andReturn($packages)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertEquals($packages, $bowerphp->getInstalledPackages($installer));
    }

    public function testInstallWithUpdatingBowerJsonFile()
    {
        $this->markTestIncomplete('testInstallWithUpdatingBowerJsonFile');
    }

    public function testInstallPackageWithDependencies()
    {
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Cannot download package colorbox (error).
     */
    public function testInstallRequestException()
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
    public function testInstallJsonException()
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
    public function testInstallVersionNotFoundException()
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

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->installPackage($package, $installer);

    }

    public function testUpdateToSpecificVersionPackageAlreadyAtThatVersion()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateToSpecificVersionPackageAtOlderVersion()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateToLatestVersionPackageNeeded()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateToLatestVersionPackageNotNeeded()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateWithOldDependenciesToUpdate()
    {
        $this->markTestIncomplete();
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
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $bowerphp->updatePackage($package, $installer);
    }

    public function testUpdateWithNewDependenciesToInstall()
    {
        $this->markTestIncomplete();
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
            ->shouldReceive('has')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(true, false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertTrue($bowerphp->isPackageInstalled($package));
        $this->assertFalse($bowerphp->isPackageInstalled($package));
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
            ->shouldReceive('has')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(false)
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
            ->shouldReceive('getBowerFileContent')->andThrow(new RuntimeException)
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
            ->shouldReceive('has')->with(getcwd() . '/bower_components/colorbox/.bower.json')->andReturn(false)
        ;

        $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $this->output);
        $this->assertFalse($bowerphp->isPackageExtraneous($package, true));
    }

    /**
     * Set expectations for installation of packages
     * Note: all array parameters must match counts
     *
     * @param MockInterface $package          mock of Package
     * @param MockInterface $installer        mock of Installer
     * @param array         $names            names of packages
     * @param array         $versions         versions of packages
     * @param array         $requires         required versions of packages
     * @param boolean       $update           if this is an update (instead of an install)
     * @param array         $updateVersions   updated versions of packages (after update)
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
                ->shouldReceive('write')->with('./tmp/' . $names[$k], 'fileAsString...', true)
                ->shouldReceive('write')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json', '{"name":"' . $names[$k] . '","version":"' . $versions[$k] . '"}', true)
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
            ->shouldReceive('getTags')->andReturn(array())  // XXX unuseful
        ;

        $this->output
            ->shouldReceive('writelnInfoPackage')
            ->shouldReceive('writelnInstalledPackage')
        ;
        $this->config
            ->shouldReceive('getSaveToBowerJsonFile')->andReturn(false)
        ;

        $installer
            ->shouldReceive('install')->with($package)
        ;

        if ($update) {
            $package
                ->shouldReceive('setRequiredVersion')
                ->shouldReceive('setRequires')
                ->shouldReceive('getVersion')->andReturnValues($versions)
            ;

            foreach ($names as $k => $v) {
                $dotBowerJson = '{"name":"' . $names[$k] . '","version":"' . $versions[$k] . '"}';

                $this->filesystem
                    ->shouldReceive('has')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json')->andReturn(true)
                    ->shouldReceive('read')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json')->andReturn($dotBowerJson)
                    ->shouldReceive('write')->with('./tmp/' . $names[$k], 'fileAsString...', true)
                    ->shouldReceive('write')->with(getcwd() . '/bower_components/' . $names[$k] . '/.bower.json', '{"name":"' . $names[$k] . '","version":"' . $updateVersions[$k] . '"}', true)
                ;
            }
        }

    }
}
