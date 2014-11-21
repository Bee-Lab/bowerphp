<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command;

use Bowerphp\Bowerphp;
use Bowerphp\Config\Config;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\Filesystem;
use Github\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Home
 */
class HomeCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('home')
            ->setDescription('Opens a package homepage into your favorite browser')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command opens a package homepage into your favorite browser.

  <info>php %command.full_name% name</info>
EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $githubClient = new Client();
        $config = new Config($filesystem);

        #$this->logHttp($githubClient, $output);
        $this->setToken($githubClient);

        $packageName = $input->getArgument('package');

        $ver = explode('#', $packageName);
        $packageName = isset($ver[0]) ? $ver[0] : $packageName;
        $version = isset($ver[1]) ? $ver[1] : '*';

        $package = new Package($packageName, $version);
        $consoleOutput = new BowerphpConsoleOutput($output);
        $bowerphp = new Bowerphp($config, $filesystem, $githubClient, new GithubRepository(), $consoleOutput);

        $url = $bowerphp->getPackageInfo($package);

        $default = $this->getDefaultBrowser();

        $arg = "$default \"$url\"";

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $output->writeln($arg);
        } else {
            $output->writeln('');
        }
        // @codeCoverageIgnoreStart
        if (!defined('PHPUNIT_BOWER_TESTSUITE')) {
            $browser = new Process($arg);
            $browser->start();
            while ($browser->isRunning()) {
                // do nothing...
            }
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    private function getDefaultBrowser()
    {
        $xdgOpen = new Process('which xdg-open');
        $xdgOpen->run();
        if (!$xdgOpen->isSuccessful()) {
            $open = new Process('which open');
            $open->run();
            if (!$open->isSuccessful()) {
                throw new \RuntimeException('Cound not open default browser.');
            }

            return trim($open->getOutput());
        }

        return trim($xdgOpen->getOutput());
    }
}
