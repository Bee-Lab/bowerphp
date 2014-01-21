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
use Guzzle\Http\ClientInterface;
use RequestException;

/**
 * Main class
 */
class Bowerphp
{
    protected $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Init bower.json
     *
     * @param array $params
     */
    public function init(array $params)
    {
        if ($this->config->bowerFileExists()) {
            $bowerJson = $this->config->getBowerFileContent();
            $this->config->setSaveToBowerJsonFile(true);
            $this->config->updateBowerJsonFile2($bowerJson, $params);
        } else {
            $this->config->initBowerJsonFile($params);
        }
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
     * Uninstall a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function uninstallPackage(PackageInterface $package, InstallerInterface $installer)
    {
        $installer->uninstall($package);
    }

    /**
     * Search packages by name
     *
     * @param  ClientInterface $httpClient
     * @param  string          $name
     * @return array
     */
    public function searchPackages(ClientInterface $httpClient, $name)
    {
        try {
            $url = $this->config->getAllPackagesUrl();
            $request = $httpClient->get($url);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot get package list from %s.', $url));
        }
        $decode = json_decode($response->getBody(true), true);
        $return = array();
        foreach ($decode as $pkg) {
            if (false !== strpos($pkg['name'], $name)) {
                $return[] = $pkg['name'];
            }
        }

        return $return;
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
}
