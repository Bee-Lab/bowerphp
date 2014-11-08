<?php

namespace Bowerphp\Test\Installer;

use Bowerphp\Installer\Installer;
use Bowerphp\Test\TestCase;
use Mockery;
use RuntimeException;

class InstallerTest extends TestCase
{
    protected $installer;
    protected $zipArchive;
    protected $config;

    public function setUp()
    {
        parent::setUp();

        $this->zipArchive = Mockery::mock('Bowerphp\Util\ZipArchive');
        $this->config = Mockery::mock('Bowerphp\Config\ConfigInterface');

        $this->installer = new Installer($this->filesystem, $this->zipArchive, $this->config);

        $this->config
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
            ->shouldReceive('getInfo')->andReturn(array('name' => 'jquery', 'version' => '2.0.3'))
            ->shouldReceive('getVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('jquery')
            ->shouldReceive('statIndex')->andReturn(array('name' => 'jquery/foo', 'size' => 10, 'mtime' => 1396303200))
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $json = '{
    "name": "jquery",
    "version": "2.0.3"
}';

        $this->filesystem
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
            ->shouldReceive('getInfo')->andReturn(array('name' => 'jquery', 'version' => '2.0.3'))
            ->shouldReceive('getRequiredVersion')->andReturn('2.0.3')
        ;

        $this->zipArchive
            ->shouldReceive('open')->with('./tmp/jquery')->andReturn(true)
            ->shouldReceive('getNumFiles')->andReturn(1)
            ->shouldReceive('getNameIndex')->with(0)->andReturn('')
            ->shouldReceive('statIndex')->andReturn(array('name' => 'jquery/foo', 'size' => 10, 'mtime' => 1396303200))
            ->shouldReceive('getStream')->with('jquery/foo')->andReturn('foo content')
            ->shouldReceive('close')
        ;

        $json = '{
    "name": "jquery",
    "version": "2.0.3"
}';

        $this->filesystem
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/foo', 'foo content')
            ->shouldReceive('write')->with(getcwd() . '/bower_components/jquery/.bower.json', $json)
            ->shouldReceive('touch')->with(getcwd() . '/bower_components/jquery/foo', 1396303200)
        ;

        $this->installer->update($package);
    }

    /**
     * @expectedException        RuntimeException
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
     * @expectedException        RuntimeException
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
                array('name' => 'dir/.foo', 'size' => 10),
                array('name' => 'dir/foo', 'size' => 10),
                array('name' => 'dir/foo.ext', 'size' => 12),
                array('name' => 'dir/bar/foo.ext', 'size' => 13),
                array('name' => 'dir/bar/anotherdir', 'size' => 13),
                array('name' => 'dir/anotherdir', 'size' => 13),
                array('name' => 'dir/_foo', 'size' => 3),
                array('name' => 'dir/_fooz/bar', 'size' => 3),
                array('name' => 'dir/filename', 'size' => 3),
                array('name' => 'dir/okfile', 'size' => 3),
                array('name' => 'dir/subdir/file', 'size' => 3),
                array('name' => 'dir/zzdir/subdir/file', 'size' => 3)
            );
        $filterZipFiles = $this->getMethod('Bowerphp\Installer\Installer', 'filterZipFiles');
        $ignore = array('**/.*', '_*', 'subdir', '/anotherdir', 'filename',  '*.ext');
        $expect = array(
            'dir/foo',
            'dir/bar/anotherdir',
            'dir/okfile',
            'dir/zzdir/subdir/file'
        );
        $this->assertEquals($expect, $filterZipFiles->invokeArgs($this->installer, array($archive, $ignore)));
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
            ->shouldReceive('directories->in')->andReturn(array('package1', 'package2'));
        ;

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

        $this->assertEquals(array(), $this->installer->getInstalled($finder));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid content in .bower.json for package package1.
     */
    public function testGetInstalledWithoutBowerJsonFile()
    {
        $finder = Mockery::mock('Symfony\Component\Finder\Finder');

        $finder
            ->shouldReceive('directories->in')->andReturn(array('package1'));
        ;

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
            ->shouldReceive('directories->in')->andReturn(array('package1', 'package2'));
        ;

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
        $ignore = array(
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
        );

        $ignored = $this->installer->isIgnored($filename, $ignore, 'twbs-bootstrap-6d03173/');
        $this->assertTrue($ignored);
    }

    /**
     * @dataProvider providerNotIgnored
     */
    public function testIsNotIgnored($filename)
    {
        $ignore = array(
            "**/.*",
            "_*",
            "docs-assets",
            "examples",
            "/fonts",
            "js/tests",
            "CNAME",
            "CONTRIBUTING.md",
            "Gruntfile.js",
            "browserstack.json",
            "composer.json",
            "package.json",
            "*.html",
        );

        $ignored = $this->installer->isIgnored($filename, $ignore, 'twbs-bootstrap-6d03173/');
        $this->assertFalse($ignored);
    }

    public function providerIgnored()
    {
        return array (
            array('twbs-bootstrap-6d03173/.editorconfig'),
            array('twbs-bootstrap-6d03173/.gitattributes'),
            array('twbs-bootstrap-6d03173/.gitignore'),
            array('twbs-bootstrap-6d03173/.travis.yml'),
            array('twbs-bootstrap-6d03173/CNAME'),
            array('twbs-bootstrap-6d03173/CONTRIBUTING.md'),
            array('twbs-bootstrap-6d03173/Gruntfile.js'),
            array('twbs-bootstrap-6d03173/_config.yml'),
            array('twbs-bootstrap-6d03173/_includes/ads.html'),
            array('twbs-bootstrap-6d03173/_includes/footer.html'),
            array('twbs-bootstrap-6d03173/_includes/header.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-about.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-components.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-css.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-customize.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-getting-started.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-javascript.html'),
            array('twbs-bootstrap-6d03173/_includes/nav-main.html'),
            array('twbs-bootstrap-6d03173/_includes/old-bs-docs.html'),
            array('twbs-bootstrap-6d03173/_includes/social-buttons.html'),
            array('twbs-bootstrap-6d03173/_layouts/default.html'),
            array('twbs-bootstrap-6d03173/_layouts/home.html'),
            array('twbs-bootstrap-6d03173/about.html'),
            array('twbs-bootstrap-6d03173/components.html'),
            array('twbs-bootstrap-6d03173/composer.json'),
            array('twbs-bootstrap-6d03173/css.html'),
            array('twbs-bootstrap-6d03173/customize.html'),
            array('twbs-bootstrap-6d03173/docs-assets/css/docs.css'),
            array('twbs-bootstrap-6d03173/docs-assets/css/pygments-manni.css'),
            array('twbs-bootstrap-6d03173/docs-assets/ico/apple-touch-icon-144-precomposed.png'),
            array('twbs-bootstrap-6d03173/docs-assets/ico/favicon.png'),
            array('twbs-bootstrap-6d03173/docs-assets/js/application.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/customizer.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/filesaver.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/holder.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/ie8-responsive-file-warning.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/jszip.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/less.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/raw-files.js'),
            array('twbs-bootstrap-6d03173/docs-assets/js/uglify.js'),
            array('twbs-bootstrap-6d03173/examples/carousel/carousel.css'),
            array('twbs-bootstrap-6d03173/examples/carousel/index.html'),
            array('twbs-bootstrap-6d03173/examples/grid/grid.css'),
            array('twbs-bootstrap-6d03173/examples/grid/index.html'),
            array('twbs-bootstrap-6d03173/examples/jumbotron-narrow/index.html'),
            array('twbs-bootstrap-6d03173/examples/jumbotron-narrow/jumbotron-narrow.css'),
            array('twbs-bootstrap-6d03173/examples/jumbotron/index.html'),
            array('twbs-bootstrap-6d03173/examples/jumbotron/jumbotron.css'),
            array('twbs-bootstrap-6d03173/examples/justified-nav/index.html'),
            array('twbs-bootstrap-6d03173/examples/justified-nav/justified-nav.css'),
            array('twbs-bootstrap-6d03173/examples/navbar-fixed-top/index.html'),
            array('twbs-bootstrap-6d03173/examples/navbar-fixed-top/navbar-fixed-top.css'),
            array('twbs-bootstrap-6d03173/examples/navbar-static-top/index.html'),
            array('twbs-bootstrap-6d03173/examples/navbar-static-top/navbar-static-top.css'),
            array('twbs-bootstrap-6d03173/examples/navbar/index.html'),
            array('twbs-bootstrap-6d03173/examples/navbar/navbar.css'),
            array('twbs-bootstrap-6d03173/examples/non-responsive/index.html'),
            array('twbs-bootstrap-6d03173/examples/non-responsive/non-responsive.css'),
            array('twbs-bootstrap-6d03173/examples/offcanvas/index.html'),
            array('twbs-bootstrap-6d03173/examples/offcanvas/offcanvas.css'),
            array('twbs-bootstrap-6d03173/examples/offcanvas/offcanvas.js'),
            array('twbs-bootstrap-6d03173/examples/screenshots/carousel.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/grid.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/jumbotron-narrow.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/jumbotron.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/justified-nav.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/navbar-fixed.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/navbar-static.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/navbar.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/non-responsive.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/offcanvas.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/sign-in.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/starter-template.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/sticky-footer-navbar.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/sticky-footer.jpg'),
            array('twbs-bootstrap-6d03173/examples/screenshots/theme.jpg'),
            array('twbs-bootstrap-6d03173/examples/signin/index.html'),
            array('twbs-bootstrap-6d03173/examples/signin/signin.css'),
            array('twbs-bootstrap-6d03173/examples/starter-template/index.html'),
            array('twbs-bootstrap-6d03173/examples/starter-template/starter-template.css'),
            array('twbs-bootstrap-6d03173/examples/sticky-footer-navbar/index.html'),
            array('twbs-bootstrap-6d03173/examples/sticky-footer-navbar/sticky-footer-navbar.css'),
            array('twbs-bootstrap-6d03173/examples/sticky-footer/index.html'),
            array('twbs-bootstrap-6d03173/examples/sticky-footer/sticky-footer.css'),
            array('twbs-bootstrap-6d03173/examples/theme/index.html'),
            array('twbs-bootstrap-6d03173/examples/theme/theme.css'),
            array('twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.eot'),
            array('twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.svg'),
            array('twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.ttf'),
            array('twbs-bootstrap-6d03173/fonts/glyphicons-halflings-regular.woff'),
            array('twbs-bootstrap-6d03173/getting-started.html'),
            array('twbs-bootstrap-6d03173/index.html'),
            array('twbs-bootstrap-6d03173/javascript.html'),
            array('twbs-bootstrap-6d03173/js/.jshintrc'),
            array('twbs-bootstrap-6d03173/js/tests/index.html'),
            array('twbs-bootstrap-6d03173/js/tests/unit/affix.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/alert.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/button.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/carousel.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/collapse.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/dropdown.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/modal.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/phantom.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/popover.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/scrollspy.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/tab.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/tooltip.js'),
            array('twbs-bootstrap-6d03173/js/tests/unit/transition.js'),
            array('twbs-bootstrap-6d03173/js/tests/vendor/jquery.js'),
            array('twbs-bootstrap-6d03173/js/tests/vendor/qunit.css'),
            array('twbs-bootstrap-6d03173/js/tests/vendor/qunit.js'),
            array('twbs-bootstrap-6d03173/package.json'),
        );
    }

    public function providerNotIgnored()
    {
        return array (
            array('twbs-bootstrap-6d03173/DOCS-LICENSE'),
            array('twbs-bootstrap-6d03173/LICENSE'),
            array('twbs-bootstrap-6d03173/LICENSE-MIT'),
            array('twbs-bootstrap-6d03173/README.md'),
            array('twbs-bootstrap-6d03173/bower.json'),
            array('twbs-bootstrap-6d03173/dist/css/bootstrap-theme.css'),
            array('twbs-bootstrap-6d03173/dist/css/bootstrap-theme.min.css'),
            array('twbs-bootstrap-6d03173/dist/css/bootstrap.css'),
            array('twbs-bootstrap-6d03173/dist/css/bootstrap.min.css'),
            array('twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.eot'),
            array('twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.svg'),
            array('twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.ttf'),
            array('twbs-bootstrap-6d03173/dist/fonts/glyphicons-halflings-regular.woff'),
            array('twbs-bootstrap-6d03173/dist/js/bootstrap.js'),
            array('twbs-bootstrap-6d03173/dist/js/bootstrap.min.js'),
            array('twbs-bootstrap-6d03173/js/affix.js'),
            array('twbs-bootstrap-6d03173/js/alert.js'),
            array('twbs-bootstrap-6d03173/js/button.js'),
            array('twbs-bootstrap-6d03173/js/carousel.js'),
            array('twbs-bootstrap-6d03173/js/collapse.js'),
            array('twbs-bootstrap-6d03173/js/dropdown.js'),
            array('twbs-bootstrap-6d03173/js/modal.js'),
            array('twbs-bootstrap-6d03173/js/popover.js'),
            array('twbs-bootstrap-6d03173/js/scrollspy.js'),
            array('twbs-bootstrap-6d03173/js/tab.js'),
            array('twbs-bootstrap-6d03173/js/tooltip.js'),
            array('twbs-bootstrap-6d03173/js/transition.js'),
            array('twbs-bootstrap-6d03173/less/alerts.less'),
            array('twbs-bootstrap-6d03173/less/badges.less'),
            array('twbs-bootstrap-6d03173/less/bootstrap.less'),
            array('twbs-bootstrap-6d03173/less/breadcrumbs.less'),
            array('twbs-bootstrap-6d03173/less/button-groups.less'),
            array('twbs-bootstrap-6d03173/less/buttons.less'),
            array('twbs-bootstrap-6d03173/less/carousel.less'),
            array('twbs-bootstrap-6d03173/less/close.less'),
            array('twbs-bootstrap-6d03173/less/code.less'),
            array('twbs-bootstrap-6d03173/less/component-animations.less'),
            array('twbs-bootstrap-6d03173/less/dropdowns.less'),
            array('twbs-bootstrap-6d03173/less/forms.less'),
            array('twbs-bootstrap-6d03173/less/glyphicons.less'),
            array('twbs-bootstrap-6d03173/less/grid.less'),
            array('twbs-bootstrap-6d03173/less/input-groups.less'),
            array('twbs-bootstrap-6d03173/less/jumbotron.less'),
            array('twbs-bootstrap-6d03173/less/labels.less'),
            array('twbs-bootstrap-6d03173/less/list-group.less'),
            array('twbs-bootstrap-6d03173/less/media.less'),
            array('twbs-bootstrap-6d03173/less/mixins.less'),
            array('twbs-bootstrap-6d03173/less/modals.less'),
            array('twbs-bootstrap-6d03173/less/navbar.less'),
            array('twbs-bootstrap-6d03173/less/navs.less'),
            array('twbs-bootstrap-6d03173/less/normalize.less'),
            array('twbs-bootstrap-6d03173/less/pager.less'),
            array('twbs-bootstrap-6d03173/less/pagination.less'),
            array('twbs-bootstrap-6d03173/less/panels.less'),
            array('twbs-bootstrap-6d03173/less/popovers.less'),
            array('twbs-bootstrap-6d03173/less/print.less'),
            array('twbs-bootstrap-6d03173/less/progress-bars.less'),
            array('twbs-bootstrap-6d03173/less/responsive-utilities.less'),
            array('twbs-bootstrap-6d03173/less/scaffolding.less'),
            array('twbs-bootstrap-6d03173/less/tables.less'),
            array('twbs-bootstrap-6d03173/less/theme.less'),
            array('twbs-bootstrap-6d03173/less/thumbnails.less'),
            array('twbs-bootstrap-6d03173/less/tooltip.less'),
            array('twbs-bootstrap-6d03173/less/type.less'),
            array('twbs-bootstrap-6d03173/less/utilities.less'),
            array('twbs-bootstrap-6d03173/less/variables.less'),
            array('twbs-bootstrap-6d03173/less/wells.less'),
        );
    }
}
