<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;
use Mockery;

class InstallerTest extends TestCase
{
    protected $installer;
    protected $zipArchive;
    protected $config;

    protected function setUp()
    {
        parent::setUp();

        $this->zipArchive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');

        $this->installer = new Installer($this->filesystem, $this->zipArchive, $this->config);

        $this->config
            ->shouldReceive('getOverridesSection')->andReturn([])
            ->shouldReceive('getOverrideFor')->andReturn([])
            ->shouldReceive('getBasePackagesUrl')->andReturn('http://bower.herokuapp.com/packages/')
            ->shouldReceive('getInstallDir')->andReturn(getcwd() . '/bower_components')
            ->shouldReceive('getCacheDir')->andReturn('.')
        ;
    }

    public function testInstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getInfo')->andReturn(['name' => 'jquery', 'version' => '2.0.3'])
            ->shouldReceive('getVersion')->andReturn('2.0.3')
            ->shouldReceive('getRequiredVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('jquery')
            ->shouldReceive('statIndex')->andReturn(['name' => 'jquery/foo', 'size' => 10, 'mtime' => 1396303200])
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $json = '{
    "name": "jquery",
    "version": "2.0.3"
}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/foo', 'foo content')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $json)
            ->shouldReceive('touch')->with(getcwd() . '/bower_components/jquery/foo', 1396303200)
        ;

        $this->installer->install($package);
    }

    public function testUpdate()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
            ->shouldReceive('getInfo')->andReturn(['name' => 'jquery', 'version' => '2.0.3'])
            ->shouldReceive('getRequiredVersion')->andReturn('2.0.3')
            ->shouldReceive('getVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('')
            ->shouldReceive('statIndex')->andReturn(['name' => 'jquery/foo', 'size' => 10, 'mtime' => 1396303200])
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $json = '{
    "name": "jquery",
    "version": "2.0.3"
}';

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/bower.json')->andReturn(false)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/foo', 'foo content')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $json)
            ->shouldReceive('touch')->with(getcwd() . '/bower_components/jquery/foo', 1396303200)
        ;

        $this->installer->update($package);
    }

    public function testInstallAndMergeInfoWithBowerJsonContents()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $info = [
            'name'         => 'jquery-ui',
            'version'      => '1.12.1',
            'dependencies' => [
                'jquery' => '>=1.6',
            ],
        ];

        $package
            ->shouldReceive('getName')->andReturn('jquery-ui')
            ->shouldReceive('getVersion')->andReturn('1.12.1')
            ->shouldReceive('getInfo')->andReturn([])->once()
            ->shouldReceive('setInfo')->with($info)->andReturnSelf()
            ->shouldReceive('getInfo')->andReturn($info);

        $json = '{
    "name": "jquery-ui",
    "version": "1.12.1",
    "dependencies": {
        "jquery": ">=1.6"
    }
}';

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery-ui')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('jquery-ui')
            ->shouldReceive('statIndex')->andReturn(['name' => 'jquery-ui/bower.json', 'size' => 107, 'mtime' => 1483795099])
            ->shouldReceive('getStream')->with('jquery-ui/bower.json')->andReturn($json)
            ->shouldReceive('close');

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery-ui/bower.json')->andReturn(true)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/.bower.json', $json)
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery-ui/bower.json', $json)
            ->shouldReceive('touch')->with(getcwd() . '/bower_components/jquery-ui/bower.json', 1483795099)
            ->shouldReceive('read')->with(getcwd() . '/bower_components/jquery-ui/bower.json')->andReturn($json)
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Unable to open zip file ./tmp/jquery.
     */
    public function testInstallZipOpenException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(false)
        ;

        $this->installer->install($package);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Unable to open zip file ./tmp/jquery.
     */
    public function testUpdateZipOpenException()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(false)
        ;

        $this->installer->update($package);
    }

    public function testFilterZipFiles()
    {
        $archive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $archive
            ->shouldReceive('getNameIndex')->with(0)->andReturn('dir/')
            ->shouldReceive('getNumFiles')->andReturn(12)
            ->shouldReceive('statIndex')->times(12)->andReturn(
                ['name' => 'dir/.foo', 'size' => 10],
                ['name' => 'dir/foo', 'size' => 10],
                ['name' => 'dir/foo.ext', 'size' => 12],
                ['name' => 'dir/bar/foo.ext', 'size' => 13],
                ['name' => 'dir/bar/anotherdir', 'size' => 13],
                ['name' => 'dir/anotherdir', 'size' => 13],
                ['name' => 'dir/_foo', 'size' => 3],
                ['name' => 'dir/_fooz/bar', 'size' => 3],
                ['name' => 'dir/filename', 'size' => 3],
                ['name' => 'dir/okfile', 'size' => 3],
                ['name' => 'dir/subdir/file', 'size' => 3],
                ['name' => 'dir/zzdir/subdir/file', 'size' => 3]
            );
        $filterZipFiles = $this->getMethod('Bowerphp\Installer\Installer', 'filterZipFiles');
        $ignore = ['**/.*', '_*', 'subdir', '/anotherdir', 'filename',  '*.ext'];
        $expect = [
            'dir/foo',
            'dir/bar/anotherdir',
            'dir/okfile',
            'dir/zzdir/subdir/file',
        ];
        $this->assertEquals($expect, $filterZipFiles->invokeArgs($this->installer, [$archive, $ignore]));
    }

    public function testUninstall()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components/jquery/.bower.json')->andReturn(true)
            ->shouldReceive('remove')->with(getcwd() . '/bower_components/jquery')
        ;

        $this->installer->uninstall($package);
    }

    public function testGetInstalled()
    {
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');

        $finder
            ->shouldReceive('directories->in')->andReturn(['package1', 'package2']);

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components')->andReturn(true)
            ->shouldReceive('exists')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with('package2/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn('{"name":"package1","version":"1.0.0"}')
            ->shouldReceive('read')->with('package2/.bower.json')->andReturn('{"name":"package2","version":"1.2.3"}')
        ;

        $this->assertCount(2, $this->installer->getInstalled($finder));
    }

    public function testGetInstalledWithoutInstalledPackages()
    {
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components')->andReturn(false)
        ;

        $this->assertEquals([], $this->installer->getInstalled($finder));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Invalid content in .bower.json for package package1.
     */
    public function testGetInstalledWithoutBowerJsonFile()
    {
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');

        $finder
            ->shouldReceive('directories->in')->andReturn(['package1']);

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components')->andReturn(true)
            ->shouldReceive('exists')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn(null)
        ;

        $this->installer->getInstalled($finder);
    }

    public function testFindDependentPackages()
    {
        $package = Mockery::mock('Bowerphp\Package\PackageInterface');
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');

        $package
            ->shouldReceive('getName')->andReturn('jquery')
        ;

        $finder
            ->shouldReceive('directories->in')->andReturn(['package1', 'package2']);

        $this->filesystem
            ->shouldReceive('exists')->with(getcwd() . '/bower_components')->andReturn(true)
            ->shouldReceive('exists')->with('package1/.bower.json')->andReturn(true)
            ->shouldReceive('exists')->with('package2/.bower.json')->andReturn(true)
            ->shouldReceive('read')->with('package1/.bower.json')->andReturn('{"name":"package1","version":"1.0.0","dependencies":{"jquery": ">=1.3.2"}}')
            ->shouldReceive('read')->with('package2/.bower.json')->andReturn('{"name":"package2","version":"1.2.3","dependencies":{"jquery": ">=1.6"}}')
        ;

        $packages = $this->installer->findDependentPackages($package, $finder);

        $this->assertCount(2, $packages);
        $this->assertArrayHasKey('>=1.3.2', $packages);
        $this->assertArrayHasKey('>=1.6', $packages);
    }

    /**
     * @dataProvider providerIgnored
     */
    public function testIsIgnored($filename)
    {
        $ignore = [
            '**/.*',
            '_*',
            'docs-assets',
            'examples',
            '/fonts',
            '/fontsWithSlash/',
            'js/tests',
            'CNAME',
            'CONTRIBUTING.md',
            'Gruntfile.js',
            'browserstack.json',
            'composer.json',
            'package.json',
            '*.html',
        ];

        $ignored = $this->installer->isIgnored($filename, $ignore, [], 'twbs-bootstrap-6d03173/');
        $this->assertTrue($ignored);
    }

    /**
     * @dataProvider providerNotIgnored
     */
    public function testIsNotIgnored($filename)
    {
        $ignore = [
            '**/.*',
            '_*',
            'docs-assets',
            'examples',
            '/fonts',
            'js/tests',
            'CNAME',
            'CONTRIBUTING.md',
            'Gruntfile.js',
            'browserstack.json',
            'bower.json',
            'composer.json',
            'package.json',
            '*.html',
        ];
        $force = [
            'bower.json',
        ];

        $ignored = $this->installer->isIgnored($filename, $ignore, $force, 'twbs-bootstrap-6d03173/');
        $this->assertFalse($ignored);
    }

    public function providerIgnored()
    {
        return [
            ['twbs-bootstrap-6d03173/.editorconfig'],
            ['twbs-bootstrap-6d03173/.gitattributes'],
            ['twbs-bootstrap-6d03173/.gitignore'],
            ['twbs-bootstrap-6d03173/.travis.yml'],
            ['twbs-bootstrap-6d03173/CNAME'],
            ['twbs-bootstrap-6d03173/CONTRIBUTING.md'],
            ['twbs-bootstrap-6d03173/Gruntfile.js'],
            ['twbs-bootstrap-6d03173/_config.yml'],
            ['twbs-bootstrap-6d03173/_includes/ads.html'],
            ['twbs-bootstrap-6d03173/_includes/footer.html'],
            ['twbs-bootstrap-6d03173/_includes/header.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-about.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-components.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-css.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-customize.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-getting-started.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-javascript.html'],
            ['twbs-bootstrap-6d03173/_includes/nav-main.html'],
            ['twbs-bootstrap-6d03173/_includes/old-bs-docs.html'],
            ['twbs-bootstrap-6d03173/_includes/social-buttons.html'],
            ['twbs-bootstrap-6d03173/_layouts/default.html'],
            ['twbs-bootstrap-6d03173/_layouts/home.html'],
            ['twbs-bootstrap-6d03173/about.html'],
            ['twbs-bootstrap-6d03173/components.html'],
            ['twbs-bootstrap-6d03173/composer.json'],
            ['twbs-bootstrap-6d03173/css.html'],
            ['twbs-bootstrap-6d03173/customize.html'],
            ['twbs-bootstrap-6d03173/docs-assets/css/docs.css'],
            ['twbs-bootstrap-6d03173/docs-assets/css/pygments-manni.css'],
            ['twbs-bootstrap-6d03173/docs-assets/ico/apple-touch-icon-144-precomposed.png'],
            ['twbs-bootstrap-6d03173/docs-assets/ico/favicon.png'],
            ['twbs-bootstrap-6d03173/docs-assets/js/application.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/customizer.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/filesaver.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/holder.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/ie8-responsive-file-warning.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/jszip.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/less.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/raw-files.js'],
            ['twbs-bootstrap-6d03173/docs-assets/js/uglify.js'],
            ['twbs-bootstrap-6d03173/examples/carousel/carousel.css'],
            ['twbs-bootstrap-6d03173/examples/carousel/index.html'],
            ['twbs-bootstrap-6d03173/examples/grid/grid.css'],
            ['twbs-bootstrap-6d03173/examples/grid/index.html'],
            ['twbs-bootstrap-6d03173/examples/jumbotron-narrow/index.html'],
            ['twbs-bootstrap-6d03173/examples/jumbotron-narrow/jumbotron-narrow.css'],
            ['twbs-bootstrap-6d03173/examples/jumbotron/index.html'],
            ['twbs-bootstrap-6d03173/examples/jumbotron/jumbotron.css'],
            ['twbs-bootstrap-6d03173/examples/justified-nav/index.html'],
            ['twbs-bootstrap-6d03173/examples/justified-nav/justified-nav.css'],
            ['twbs-bootstrap-6d03173/examples/navbar-fixed-top/index.html'],
            ['twbs-bootstrap-6d03173/examples/navbar-fixed-top/navbar-fixed-top.css'],
            ['twbs-bootstrap-6d03173/examples/navbar-static-top/index.html'],
            ['twbs-bootstrap-6d03173/examples/navbar-static-top/navbar-static-top.css'],
            ['twbs-bootstrap-6d03173/examples/navbar/index.html'],
            ['twbs-bootstrap-6d03173/examples/navbar/navbar.css'],
            ['twbs-bootstrap-6d03173/examples/non-responsive/index.html'],
            ['twbs-bootstrap-6d03173/examples/non-responsive/non-responsive.css'],
            ['twbs-bootstrap-6d03173/examples/offcanvas/index.html'],
            ['twbs-bootstrap-6d03173/examples/offcanvas/offcanvas.css'],
            ['twbs-bootstrap-6d03173/examples/offcanvas/offcanvas.js'],
            ['twbs-bootstrap-6d03173/examples/screenshots/carousel.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/grid.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/jumbotron-narrow.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/jumbotron.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/justified-nav.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/navbar-fixed.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/navbar-static.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/navbar.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/non-responsive.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/offcanvas.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/sign-in.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/starter-template.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/sticky-footer-navbar.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/sticky-footer.jpg'],
            ['twbs-bootstrap-6d03173/examples/screenshots/theme.jpg'],
            ['twbs-bootstrap-6d03173/examples/signin/index.html'],
            ['twbs-bootstrap-6d03173/examples/signin/signin.css'],
            ['twbs-bootstrap-6d03173/examples/starter-template/index.html'],
            ['twbs-bootstrap-6d03173/examples/starter-template/starter-template.css'],
            ['twbs-bootstrap-6d03173/examples/sticky-footer-navbar/index.html'],
            ['twbs-bootstrap-6d03173/examples/sticky-footer-navbar/sticky-footer-navbar.css'],
            ['twbs-bootstrap-6d03173/examples/sticky-footer/index.html'],
            ['twbs-bootstrap-6d03173/examples/sticky-footer/sticky-footer.css'],
            ['twbs-bootstrap-6d03173/examples/theme/index.html'],
            ['twbs-bootstrap-6d03173/examples/theme/theme.css'],
            ['twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.eot'],
            ['twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.svg'],
            ['twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.ttf'],
            ['twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.woff'],
            ['twbs-bootstrap-6d03173/getting-started.html'],
            ['twbs-bootstrap-6d03173/index.html'],
            ['twbs-bootstrap-6d03173/javascript.html'],
            ['twbs-bootstrap-6d03173/js/.jshintrc'],
            ['twbs-bootstrap-6d03173/js/tests/index.html'],
            ['twbs-bootstrap-6d03173/js/tests/unit/affix.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/alert.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/button.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/carousel.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/collapse.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/dropdown.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/modal.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/phantom.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/popover.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/scrollspy.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/tab.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/tooltip.js'],
            ['twbs-bootstrap-6d03173/js/tests/unit/transition.js'],
            ['twbs-bootstrap-6d03173/js/tests/vendor/jquery.js'],
            ['twbs-bootstrap-6d03173/js/tests/vendor/qunit.css'],
            ['twbs-bootstrap-6d03173/js/tests/vendor/qunit.js'],
            ['twbs-bootstrap-6d03173/package.json'],
        ];
    }

    public function providerNotIgnored()
    {
        return [
            ['twbs-bootstrap-6d03173/DOCS-LICENSE'],
            ['twbs-bootstrap-6d03173/LICENSE'],
            ['twbs-bootstrap-6d03173/LICENSE-MIT'],
            ['twbs-bootstrap-6d03173/README.md'],
            ['twbs-bootstrap-6d03173/bower.json'],
            ['twbs-bootstrap-6d03173/dist/css/bootstrap-theme.css'],
            ['twbs-bootstrap-6d03173/dist/css/bootstrap-theme.min.css'],
            ['twbs-bootstrap-6d03173/dist/css/bootstrap.css'],
            ['twbs-bootstrap-6d03173/dist/css/bootstrap.min.css'],
            ['twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.eot'],
            ['twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.svg'],
            ['twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.ttf'],
            ['twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.woff'],
            ['twbs-bootstrap-6d03173/dist/js/bootstrap.js'],
            ['twbs-bootstrap-6d03173/dist/js/bootstrap.min.js'],
            ['twbs-bootstrap-6d03173/js/affix.js'],
            ['twbs-bootstrap-6d03173/js/alert.js'],
            ['twbs-bootstrap-6d03173/js/button.js'],
            ['twbs-bootstrap-6d03173/js/carousel.js'],
            ['twbs-bootstrap-6d03173/js/collapse.js'],
            ['twbs-bootstrap-6d03173/js/dropdown.js'],
            ['twbs-bootstrap-6d03173/js/modal.js'],
            ['twbs-bootstrap-6d03173/js/popover.js'],
            ['twbs-bootstrap-6d03173/js/scrollspy.js'],
            ['twbs-bootstrap-6d03173/js/tab.js'],
            ['twbs-bootstrap-6d03173/js/tooltip.js'],
            ['twbs-bootstrap-6d03173/js/transition.js'],
            ['twbs-bootstrap-6d03173/less/alerts.less'],
            ['twbs-bootstrap-6d03173/less/badges.less'],
            ['twbs-bootstrap-6d03173/less/bootstrap.less'],
            ['twbs-bootstrap-6d03173/less/breadcrumbs.less'],
            ['twbs-bootstrap-6d03173/less/button-groups.less'],
            ['twbs-bootstrap-6d03173/less/buttons.less'],
            ['twbs-bootstrap-6d03173/less/carousel.less'],
            ['twbs-bootstrap-6d03173/less/close.less'],
            ['twbs-bootstrap-6d03173/less/code.less'],
            ['twbs-bootstrap-6d03173/less/component-animations.less'],
            ['twbs-bootstrap-6d03173/less/dropdowns.less'],
            ['twbs-bootstrap-6d03173/less/forms.less'],
            ['twbs-bootstrap-6d03173/less/glyphicons.less'],
            ['twbs-bootstrap-6d03173/less/grid.less'],
            ['twbs-bootstrap-6d03173/less/input-groups.less'],
            ['twbs-bootstrap-6d03173/less/jumbotron.less'],
            ['twbs-bootstrap-6d03173/less/labels.less'],
            ['twbs-bootstrap-6d03173/less/list-group.less'],
            ['twbs-bootstrap-6d03173/less/media.less'],
            ['twbs-bootstrap-6d03173/less/mixins.less'],
            ['twbs-bootstrap-6d03173/less/modals.less'],
            ['twbs-bootstrap-6d03173/less/navbar.less'],
            ['twbs-bootstrap-6d03173/less/navs.less'],
            ['twbs-bootstrap-6d03173/less/normalize.less'],
            ['twbs-bootstrap-6d03173/less/pager.less'],
            ['twbs-bootstrap-6d03173/less/pagination.less'],
            ['twbs-bootstrap-6d03173/less/panels.less'],
            ['twbs-bootstrap-6d03173/less/popovers.less'],
            ['twbs-bootstrap-6d03173/less/print.less'],
            ['twbs-bootstrap-6d03173/less/progress-bars.less'],
            ['twbs-bootstrap-6d03173/less/responsive-utilities.less'],
            ['twbs-bootstrap-6d03173/less/scaffolding.less'],
            ['twbs-bootstrap-6d03173/less/tables.less'],
            ['twbs-bootstrap-6d03173/less/theme.less'],
            ['twbs-bootstrap-6d03173/less/thumbnails.less'],
            ['twbs-bootstrap-6d03173/less/tooltip.less'],
            ['twbs-bootstrap-6d03173/less/type.less'],
            ['twbs-bootstrap-6d03173/less/utilities.less'],
            ['twbs-bootstrap-6d03173/less/variables.less'],
            ['twbs-bootstrap-6d03173/less/wells.less'],
        ];
    }

    public function providerIgnoreExceptions()
    {
        return [
            ['zeroclipboard-zeroclipboard-1ec7da6/test/shared/private.tests.js.html', true],
            ['zeroclipboard-zeroclipboard-1ec7da6/src/meta/composer.json.tmpl', true],
            ['zeroclipboard-zeroclipboard-1ec7da6/dist/ZeroClipboard.swf', false],
            ['zeroclipboard-zeroclipboard-1ec7da6/bower.json', false],
        ];
    }

    /**
     * @dataProvider providerIgnoreExceptions
     */
    public function testIgnoreExceptions($file, $shouldIgnore)
    {
        $ignore = [
            '*',
            '!/bower.json',
            '!/dist/**',
        ];
        $ignored = $this->installer->isIgnored($file, $ignore, [], 'zeroclipboard-zeroclipboard-1ec7da6/');
        $this->assertEquals($ignored, $shouldIgnore);
    }

    public function providerIgnoreAllExceptNegations()
    {
        return [
            ['pippo/src/foo.js', true],
            ['pippo/dist/.htaccess', false],
            ['pippo/bower.json', true],
        ];
    }

    /**
     * See issue https://github.com/Bee-Lab/bowerphp/issues/126
     *
     * @dataProvider providerIgnoreAllExceptNegations
     */
    public function testIgnoreAllExceptNegations($file, $shouldIgnore)
    {
        $ignore = [
            '**/*',
            '!/dist/**',
        ];
        $ignored = $this->installer->isIgnored($file, $ignore, [], 'pippo/');
        $this->assertEquals($ignored, $shouldIgnore);
    }
}
