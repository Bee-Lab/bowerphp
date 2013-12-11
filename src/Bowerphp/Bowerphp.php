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

use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Gaufrette\Filesystem;
use Camspiers\JsonPretty\JsonPretty;

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
     * @param Filesystem $filesystem
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
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function installPackage(PackageInterface $package, InstallerInterface $installer)
    {
        $installer->install($package);
    }

    /**
     * Install all dependencies
     *
     * @param InstallerInterface $installer
     */
    public function installDependencies(InstallerInterface $installer)
    {
        $bowerJson = $this->filesystem->read(getcwd() . '/bower.json');

        if (empty($bowerJson) || !is_array($decode = json_decode($bowerJson, true))) {
            throw new \RuntimeException(sprintf('Malformed JSON %s.', $bowerJson));
        }

        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $name => $version) {
                $package = new Package($name, $version);
                $installer->install($package);
            }
        }
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
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
     * FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
     * See http://www.php.net/manual/en/function.json-encode.php
     *
     * @param  array  $array
     * @return string
     */
    private function json_readable_encode(array $array)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($array, JSON_PRETTY_PRINT);
        }

        $jsonPretty = new JsonPretty();

        return $jsonPretty->prettify($array, null, '    ');
    }

}
