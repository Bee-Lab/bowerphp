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

        $author = sprintf('%s <%s>', $this->getGitInfo('user.name'), $this->getGitInfo('user.email'));

        $params = array('name' => get_current_user(), 'author' => $author);

        if ($input->isInteractive()) {
            $dialog = $this->getHelperSet()->get('dialog');

            $params['name'] = $dialog->ask(
                $output,
                $dialog->getQuestion('Please specify a name for project', $params['name']),
                $params['name']
            );

            $params['author'] = $dialog->ask(
                $output,
                $dialog->getQuestion('Please sspecify an author', $params['author']),
                $params['author']
            );
        }
        $bowerphp = new Bowerphp($config);
        $bowerphp->init($params);

        $output->writeln('');
    }

    /**
     * Get some info from local git
     *
     * @param  string $info info type
     * @return string
     */
    private function getGitInfo($info = 'user.name')
    {
        $output = array();
        $return = 0;
        $info = exec("git config --get $info", $output, $return);

        if ($return === 0) {
            return $info;
        }
    }
}
