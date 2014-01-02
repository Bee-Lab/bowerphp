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

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;

/**
 * Main class
 */
class Bowerphp
{
    protected $filesystem, $config;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config       = $config;
    }

    /**
     * Init bower.json
     *
     * @param array $params
     */
    public function init(array $params)
    {
        $this->config->initBowerJsonFile($params);
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
        $decode = $this->config->getBowerFileContent();
        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $name => $version) {
                $package = new Package($name, $version);
                $installer->install($package);
            }
        }
    }

    /**
     * Update a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function updatePackage(PackageInterface $package, InstallerInterface $installer)
    {
        $decode = $this->config->getBowerFileContent();

        if (empty($decode['dependencies']) || empty($decode['dependencies'][$package->getName()])) {
            throw new \InvalidArgumentException(sprintf('Package %s not found in bower.json.', $package->getName()));
        }

        $package->setVersion($decode['dependencies'][$package->getName()]);

        $installer->update($package);
    }

    /**
     * Update all dependencies
     *
     * @param InstallerInterface $installer
     */
    public function updateDependencies(InstallerInterface $installer)
    {
        $decode = $this->config->getBowerFileContent();

        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $name => $version) {
                $package = new Package($name, $version);
                $installer->update($package);
            }
        }
    }

    /**
     * @param  PackageInterface   $package
     * @param  InstallerInterface $installer
     * @param  string             $info
     * @return string
     */
    public function getPackageInfo(PackageInterface $package, InstallerInterface $installer, $info = 'url')
    {
        return $installer->getPackageInfo($package, $info);
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
            'dependencies' => new \StdClass(),
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
