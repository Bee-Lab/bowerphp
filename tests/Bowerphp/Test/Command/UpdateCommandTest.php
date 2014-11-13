<?php
namespace Bowerphp\Test\Command;

use Bowerphp\Factory\CommandFactory;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @group functional
 */
class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    private $packageDotBowerFile;
    private $bowerFile;

    public function setUp()
    {
        $dir = getcwd() . '/bower_components/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!is_dir($dir . 'jquery')) {
            mkdir($dir . 'jquery');
        }
        touch($dir . 'jquery/.bower.json');
        $this->packageDotBowerFile = $dir . 'jquery/.bower.json';
        $this->bowerFile = getcwd() . '/bower.json';

        file_put_contents($this->packageDotBowerFile, '{"name": "jquery", "version": "1.10.1"}');
        file_put_contents($this->bowerFile, '{"name": "test", "dependencies": {"jquery": "1.11.1"}}');
    }

    /**
     * @test
     */
    public function shouldUpdateDependencies()
    {
        //when
        CommandFactory::tester('update', array('package' => 'jquery'));

        //then
        $dotBower = json_decode(file_get_contents($this->packageDotBowerFile), true);
        $this->assertEquals('1.11.1', $dotBower['version']);
        $this->assertFileExists(getcwd() . '/bower_components/jquery/dist/jquery.js');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package nonexistent-package is not installed
     */
    public function shouldThrowExceptionWhenPackageIsNotInstalled()
    {
        //when
        $commandTester = CommandFactory::tester('update', array('package' => 'nonexistent-package'));

        //then
        $this->assertRegExp('/Package nonexistent-package is not installed/', $commandTester->getDisplay());
    }

    public function tearDown()
    {
        $dir = getcwd() . '/bower_components/';
        if (is_dir($dir)) {
            // see http://stackoverflow.com/a/15111679/369194
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            }
            rmdir($dir);
        }
        unlink(getcwd() . '/bower.json');
    }
}
