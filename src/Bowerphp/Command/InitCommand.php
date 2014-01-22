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
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setDescription('Initializes a bower.json file')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command initializes a bower.json file in
the current directory.

  <info>php %command.full_name%</info>
EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter    = new LocalAdapter('/');
        $filesystem = new Filesystem($adapter);
        $config     = new Config($filesystem);

        $params = array('name' => null, 'author' => null);

        if ($input->isInteractive()) {
            $dialog = $this->getHelperSet()->get('dialog');

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
        }

        $bowerphp = new Bowerphp($config);
        $bowerphp->init($params);

        $output->writeln('');
    }
}
