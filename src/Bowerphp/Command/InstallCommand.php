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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inspired by Composer https://github.com/composer/composer
 */
class InstallCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Installs the project dependencies from the bower.json file.')
            ->setDefinition(array(
                // TODO add all options...
                new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Shows more details including new commits pulled in when updating packages.'),
            ))
            ->setHelp(<<<EOT
The <info>install</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

<info>php bowerphp.phar install</info>

EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = 'bower.json';
        if (!is_readable($file)) {
            $output->writeln('No bower.json found in the current directory.');

            return 1;
        }

        $json = file_get_contents('bower.json');
        $output->writeln('Installing dependencies:');
        $installed = array();
        try {
            $this->install($json, $installed);
        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());

            return $e->getCode();
        }

        // TODO this is ugly, since all output is in the end
        foreach ($installed as $lib => $version) {
            $output->writeln(sprintf('<info>%s</info>: %s', $lib, $version));
        }

        $output->writeln('Done.');
    }

    // TODO move to a different class
    protected function install($bowerJson, &$installed)
    {
        $json = json_decode($bowerJson, true);

        if (!isset($json['dependencies'])) {
            return $installed;
        }

        foreach ($json['dependencies'] as $lib => $version) {

            $url = 'http://bower.herokuapp.com/packages/' . $lib;
            if (false === $response = @file_get_contents($url)) {
                throw new \RuntimeException(sprintf('Cannot download package <error>%s</error>.', $lib), 3);

            }

            $decode = json_decode($response, true);

            $git = str_replace('git://', 'http://raw.', $decode['url']);
            $git = preg_replace('/\.git$/', '', $git);

            if (false === $depBowerJson = @file_get_contents($git . '/master/bower.json')) {
                throw new \RuntimeException(sprintf('Cannot open package git URL <error>%s/master/bower.json</error>.', $git), 4);
            }

            $installed[$lib] = $version;

            return $this->install($depBowerJson, $installed);
        }
    }
}
