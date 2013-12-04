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
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Choose a package. Add this one on bower.json file if not-exist'
            )
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
            if(!$input->getArgument('package'))
            {
                $output->writeln('<error>No bower.json found in the current directory. Call "install [package name]" for build a new one or create first a file ;)</error>');
                return 1;
            }
            else
            {
                
                $dialog = $this->getHelperSet()->get('dialog');

                $params['name'] = $dialog->ask(
                    $output,
                    '<question>Please specify a name for bower.json:</question> ',
                    'A name'
                );

                $params['author'] = $dialog->ask(
                    $output,
                    '<question>Please specify an author (Ex. Adam Smith \<noreply@example.org>) for bower.json:</question> ',
                    'A author'
                );


                $json = json_encode($this->createAClearBowerFile($params));
                $fp = fopen($file,'w+');
                fputs($fp, $json);
                fclose($fp);
                
            }
        }

        $json = file_get_contents('bower.json');
        $output->writeln('Installing dependencies:');

        //TODO: refactoring
        $jsonDecoded = json_decode($json, true);

        if($input->getArgument('package') && !in_array($input->getArgument('package'), $jsonDecoded['dependencies']))
        {

            $v = explode("/", $input->getArgument('package'));
            $package = isset($v[0]) ? $v[0] : $input->getArgument('package');
            $version = isset($v[1]) ? $v[1] : "*";

    
            $jsonDecoded['dependencies'][$package]= $version;
            $fp = fopen($file,'w+');

            // FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
            // See http://www.php.net/manual/en/function.json-encode.php
            if (version_compare(PHP_VERSION, '5.4.0', '<')) 
            {
                fputs($fp, $this->json_readable_encode($jsonDecoded));
            }
            else
            {
                fputs($fp, json_encode($jsonDecoded,JSON_PRETTY_PRINT));
            }
            fclose($fp);
            $json = file_get_contents('bower.json');
        }


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
            $depBowerJson = $this->executeInstallFromBower($lib, $version, $installed);
        }
    }

    // TODO: to refact
    protected function executeInstallFromBower($lib, $version, &$installed)
    {
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

        $this->install($depBowerJson, $installed);

        return $depBowerJson;

    }


    protected function createAClearBowerFile($params)
    {
        $structure =  array(
            'name' => $params['name'],
            'authors' => array (
                0 => 'Beelab <info@bee-lab.net>',
                1 => $params['author']
            ),
            'private' => true,
            'dependencies' => array(),
            'ignore' => array (
                0 => '**/.*',
                1 => 'node_modules',
                2 => 'bower_components',
                3 => 'test',
                4 => 'tests'
            )
        );

        return $structure;
    }

    // FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
    // See http://www.php.net/manual/en/function.json-encode.php

    protected function json_readable_encode($in, $indent = 0, $_escape = null)
    {
        if (__CLASS__ && isset($this))
        {
            $_myself = array($this, __FUNCTION__);
        }
        elseif (__CLASS__)
        {
            $_myself = array('self', __FUNCTION__);
        }
        else
        {
            $_myself = __FUNCTION__;
        }

        if (is_null($_escape))
        {
            $_escape = function ($str)
            {
                return str_replace(
                        array('\\', '"', "\n", "\r", "\b", "\f", "\t", '/', '\\\\u'),
                        array('\\\\', '\\"', "\\n", "\\r", "\\b", "\\f", "\\t", '\\/', '\\u'),
                        $str);
            };
        }

        $out = '';

        foreach ($in as $key=>$value)
        {
            $out .= str_repeat("\t", $indent + 1);
            $out .= "\"".$_escape((string)$key)."\": ";

            if (is_object($value) || is_array($value))
            {
                $out .= "\n";
                $out .= call_user_func($_myself, $value, $indent + 1, $_escape);
            }
            elseif (is_bool($value))
            {
                $out .= $value ? 'true' : 'false';
            }
            elseif (is_null($value))
            {
                $out .= 'null';
            }
            elseif (is_string($value))
            {
                $out .= "\"" . $_escape($value) ."\"";
            }
            else
            {
                $out .= $value;
            }

            $out .= ",\n";
        }

        if (!empty($out))
        {
            $out = substr($out, 0, -2);
        }

        $out = str_repeat("\t", $indent) . "{\n" . $out;
        $out .= "\n" . str_repeat("\t", $indent) . "}";

        return $out;
    }

}
