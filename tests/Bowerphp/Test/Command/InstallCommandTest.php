<?php

namespace Bowerphp\Test\Command;

use Bowerphp\Console\Application;
use Bowerphp\Repository\GithubRepository;
use FilesystemIterator;
use Github\Client;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group functional
 */
class InstallCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false]);

        $this->assertRegExp('/jquery#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');
        $this->assertFileExists(getcwd() . '/bower_components/jquery/src/jquery.js');
        $this->assertFileNotExists(getcwd() . '/bower.json');
    }

    public function testExecuteAndSave()
    {
        $application = new Application();
        //setup
        $commandTester = new CommandTester($command = $application->get('init'));
        $commandTester->execute(['command' => $command->getName()], ['interactive' => false, 'decorated' => false]);
        //install
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery', '--save' => true], ['decorated' => false]);

        //Check that the install worked
        $this->assertRegExp('/jquery#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');
        $this->assertFileExists(getcwd() . '/bower_components/jquery/src/jquery.js');

        //Check that the save worked
        $this->assertFileExists(getcwd() . '/bower.json');
        $bowerJsonDependencies = ['jquery' => '*'];
        $json = json_decode(file_get_contents(getcwd() . '/bower.json'), true);
        $this->assertArrayHasKey('dependencies', $json);
        $this->assertEquals($bowerJsonDependencies, $json['dependencies']);
    }

    /**
     * We need to make sure that it's possible to save a package even if he has already been installed separetly.
     * See https://github.com/Bee-Lab/bowerphp/issues/104
     */
    public function testExecuteAndThenTestSave()
    {
        $application = new Application();
        //setup
        $commandTester = new CommandTester($command = $application->get('init'));
        $commandTester->execute(['command' => $command->getName()], ['interactive' => false, 'decorated' => false]);
        //install
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false]);

        //Check that the install worked
        $this->assertRegExp('/jquery#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');
        $this->assertFileExists(getcwd() . '/bower_components/jquery/src/jquery.js');

        //Try to save the package in the bower.json
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery', '--save' => true], ['decorated' => false]);

        //Check that the save worked
        $this->assertFileExists(getcwd() . '/bower.json');
        $bowerJsonDependencies = ['jquery' => '*'];
        $json = json_decode(file_get_contents(getcwd() . '/bower.json'), true);
        $this->assertArrayHasKey('dependencies', $json);
        $this->assertEquals($bowerJsonDependencies, $json['dependencies']);
    }

    public function testExecuteVerbose()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery'], ['decorated' => false, 'verbosity' => OutputInterface::VERBOSITY_DEBUG]);

        $this->assertRegExp('/jquery#/', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');
        $this->assertFileExists(getcwd() . '/bower_components/jquery/src/jquery.js');
    }

    public function testExecuteWithoutPackage()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

        $this->assertRegExp('/No bower.json found/', $commandTester->getDisplay());
    }

    public function testExecuteWithPackageVersionNotFound()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery#999'], ['decorated' => false]);

        $this->assertRegExp('/Available versions/', $commandTester->getDisplay());
    }

    public function testExecuteInstallPackageWithDependencies()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery-ui'], ['decorated' => false]);

        $this->assertRegExp('/jquery#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery-ui/.bower.json');
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');
    }

    public function testExecuteInstallFromGithubEndpoint()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'https://github.com/select2/select2.git#3.5.1'], ['decorated' => false]);

        $this->assertRegExp('/select2#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/select2/.bower.json');
    }

    public function testExecuteInstallFromLocalFile()
    {
        $file = getcwd() . '/tests/Bowerphp/Test/bower.json';
        file_put_contents($file, '{"name": "test", "dependencies": {"jquery": "1.11.1"}}');

        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => $file], ['decorated' => false]);

        $this->assertRegExp('/jquery#1.11.1/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery/.bower.json');

        unlink($file);
    }

    public function testExecuteInstallFromLocalUnreadableFile()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'doesnotexist/bower.json'], ['decorated' => false]);

        $this->assertRegExp('/Cannot read/m', $commandTester->getDisplay());
    }

    public function testExecuteInstallFromLocalFileWithoutDependencies()
    {
        $file = getcwd() . '/tests/Bowerphp/Test/bower.json';
        file_put_contents($file, '{"name": "test"}');

        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => $file], ['decorated' => false]);

        $this->assertRegExp('/Nothing to install/m', $commandTester->getDisplay());

        unlink($file);
    }

    /**
     * This test is probably not ideal in that it will only work (meaning break if the code behavior change) as long
     * as the jquery-address package has no tag.
     * The first assertion is designed to make sure that the test is still valid and does it's job.
     */
    public function testExecuteInstallWithoutTag()
    {
        $client = new Client();
        $token = getenv('BOWERPHP_TOKEN');
        if (!empty($token)) {
            $client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        }
        $githubRepo = new GithubRepository();
        $githubRepo->setUrl('https://github.com/asual/jquery-address');
        $githubRepo->setHttpClient($client);
        //The test only make sense if the library has no git tags.
        $this->assertEquals([], $githubRepo->getTags());

        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'jquery-address'], ['decorated' => false]);

        $this->assertRegExp('/jquery-address#master/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/jquery-address/.bower.json');
    }

    /**
     * See https://github.com/Bee-Lab/bowerphp/issues/119
     */
    public function testExecuteInstallWithAllIgnores()
    {
        $application = new Application();
        $commandTester = new CommandTester($command = $application->get('install'));
        $commandTester->execute(['command' => $command->getName(), 'package' => 'blueimp-tmpl'], ['decorated' => false]);

        $this->assertRegExp('/blueimp-tmpl#/m', $commandTester->getDisplay());
        $this->assertFileExists(getcwd() . '/bower_components/blueimp-tmpl/js/tmpl.js');
    }

    protected function tearDown()
    {
        $dir = getcwd() . '/bower_components/';
        if (is_dir($dir)) {
            // see http://stackoverflow.com/a/15111679/369194
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            }
            rmdir($dir);
        }
        if (file_exists(getcwd() . '/bower.json')) {
            unlink(getcwd() . '/bower.json');
        }
    }
}
