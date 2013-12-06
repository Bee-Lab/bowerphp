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
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Guzzle\Http\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init
 */
class InitCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initializes a bower.json file.')
            ->setDefinition(array(
                // TODO add all options...
                new InputOption('verbose', 'v|vv|vvv', InputOption::VALUE_NONE, 'Shows more details including new commits pulled in when updating packages.'),
            ))
            ->setHelp(<<<EOT
The <info>init</info> command initializes a bower.json file in
the current directory.

<info>php bowerphp.phar init</info>

EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = new LocalAdapter(getcwd());
        $filesystem = new Filesystem($adapter);
        $httpClient = new Client();

        $dialog = $this->getHelperSet()->get('dialog');

        $params = array();
        $params['name'] = $dialog->ask(
            $output,
            '<question>Please specify a name for project:</question> ',
            'A name'
        );

        $params['author'] = $dialog->ask(
            $output,
            '<question>Please specify an author (Ex. Adam Smith \<noreply@example.org>):</question> ',
            'An author'
        );

        $bowerphp = new Bowerphp($filesystem, $httpClient);
        $bowerphp->init($params);

        $output->writeln('Done.');
    }
}
