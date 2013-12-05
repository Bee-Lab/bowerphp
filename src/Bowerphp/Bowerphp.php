<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp;

use Gaufrette\Filesystem;

/**
 * Main class
 */
class Bowerphp
{
    protected
        $installed = array(),
        $filesystem
    ;

    /**
     * TODO add dependencies...
     *
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Init bower.json
     *
     */
    public function init(array $params)
    {
        $file = 'bower.json';
        $json = $this->json_readable_encode($this->createAClearBowerFile($params));

        $this->filesystem->write($file, $json);
    }

    /**
     * Install a single package
     *
     * @param  string $package
     * @param  string $version
     * @return array
     */
    public function installPackage($package, $version = '*')
    {
        $v = explode("#", $package);
        $package = isset($v[0]) ? $v[0] : $package;
        $version = isset($v[1]) ? $v[1] : "*";

        $this->executeInstallFromBower($package, $version);

        return $this->installed;
    }

    /**
     * Install all dependencies
     *
     * @return array
     */
    public function installDependencies()
    {
        $json = $this->filesystem->read('bower.json');

        $this->install($json);

        return $this->installed;
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
        );

        return $structure;
    }

    /**
     * @param string $bowerJson
     */
    protected function install($bowerJson)
    {
        $json = json_decode($bowerJson, true);

        if (!isset($json['dependencies'])) {
            return $this->installed;
        }

        foreach ($json['dependencies'] as $lib => $version) {
            $this->executeInstallFromBower($lib, $version);
        }
    }

    /**
     * @param string $lib
     * @param string $version
     */
    protected function executeInstallFromBower($lib, $version)
    {
        $url = 'http://bower.herokuapp.com/packages/' . $lib;
        try {
            $response = $this->filesystem->readfile_get_contents($url);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(sprintf('Cannot download package <error>%s</error>.', $lib), 3);
        }

        $decode = json_decode($response, true);

        $git = str_replace('git://', 'http://raw.', $decode['url']);
        $git = preg_replace('/\.git$/', '', $git);

        // TODO use guzzle here!!!
        if (false === $depBowerJson = @file_get_contents($git . '/master/bower.json')) {
            throw new \RuntimeException(sprintf('Cannot open package git URL <error>%s/master/bower.json</error>.', $git), 4);
        }

        $this->installed[$lib] = $version;

        $this->install($depBowerJson);
    }

    /**
     * FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
     * See http://www.php.net/manual/en/function.json-encode.php
     *
     * @param  array   $in
     * @param  integer $indent
     * @param  string  $_escape
     * @return string
     */
    private function json_readable_encode(array $in, $indent = 0, $_escape = null)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($in, JSON_PRETTY_PRINT);
        }

        if (__CLASS__ && isset($this)) {
            $_myself = array($this, __FUNCTION__);
        } elseif (__CLASS__) {
            $_myself = array('self', __FUNCTION__);
        } else {
            $_myself = __FUNCTION__;
        }

        if (is_null($_escape)) {
            $_escape = function ($str) {
                return str_replace(
                        array('\\', '"', "\n", "\r", "\b", "\f", "\t", '/', '\\\\u'),
                        array('\\\\', '\\"', "\\n", "\\r", "\\b", "\\f", "\\t", '\\/', '\\u'),
                        $str);
            };
        }

        $out = '';

        foreach ($in as $key=>$value) {
            $out .= str_repeat("\t", $indent + 1);
            $out .= "\"".$_escape((string) $key)."\": ";

            if (is_object($value) || is_array($value)) {
                $out .= "\n";
                $out .= call_user_func($_myself, $value, $indent + 1, $_escape);
            } elseif (is_bool($value)) {
                $out .= $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $out .= 'null';
            } elseif (is_string($value)) {
                $out .= "\"" . $_escape($value) ."\"";
            } else {
                $out .= $value;
            }

            $out .= ",\n";
        }

        if (!empty($out)) {
            $out = substr($out, 0, -2);
        }

        $out = str_repeat("\t", $indent) . "{\n" . $out;
        $out .= "\n" . str_repeat("\t", $indent) . "}";

        return $out;
    }
}
