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
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Repository\RepositoryInterface;
use Gaufrette\Filesystem;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Main class
 */
class Bowerphp
{
    protected $config, $filesystem, $httpClient, $repository, $output;

    /**
     * @param ConfigInterface       $config
     * @param Filesystem            $filesystem
     * @param ClientInterface       $httpClient
     * @param RepositoryInterface   $repository
     * @param BowerphpConsoleOutput $output
     */
    public function __construct(ConfigInterface $config, Filesystem $filesystem, ClientInterface $httpClient, RepositoryInterface $repository, BowerphpConsoleOutput $output)
    {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->output = $output;
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
     * @param boolean            $isDependency
     */
    public function installPackage(PackageInterface $package, InstallerInterface $installer, $isDependency = false)
    {
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . $package->getName());
            $response = $request->send();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }
        $repoUrl = $decode['url'];
        $this->repository->setUrl($repoUrl)->setHttpClient($this->httpClient);
        $bowerJson = $this->repository->getBower();
        $bower = json_decode($bowerJson, true);
        if (!is_array($bower)) {
            throw new RuntimeException(sprintf('Invalid bower.json found in package %s: %s.', $package->getName(), $bowerJson));
        }
        $packageTag = $this->repository->findPackage($package->getRequiredVersion());
        if (is_null($packageTag)) {
            throw new RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getRequiredVersion()));
        }
        $package->setRepository($this->repository);
        $package->setInfo($bower);
        $package->setVersion($packageTag);

        $this->output->writelnInfoPackage($package);

        $file = $this->repository->getRelease();

        $this->output->writelnInstalledPackage($package);

        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        $this->filesystem->write($tmpFileName, $file, true);

        $installer->install($package);

        if ($this->config->getSaveToBowerJsonFile() && !$isDependency) {
            try {
                $this->config->updateBowerJsonFile($package);
            } catch (RuntimeException $e) {
                $this->output->writelnNoBowerJsonFile();
            }
        }

        if (!empty($bower['dependencies'])) {
            foreach ($bower['dependencies'] as $name => $version) {
                $depPackage = new Package($name, $version);
                if ($this->isPackageInstalled($depPackage)) {
                    $this->installPackage($depPackage, $installer, true);
                } else {
                    $this->updatePackage($depPackage, $installer);
                }
            }
        }
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
            foreach ($decode['dependencies'] as $name => $requiredVersion) {
                $package = new Package($name, $requiredVersion);
                $this->installPackage($package, $installer);
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
        if (!$this->isPackageInstalled($package)) {
            throw new RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }

        $decode = $this->config->getBowerFileContent();

        if (empty($decode['dependencies']) || empty($decode['dependencies'][$package->getName()])) {
            throw new InvalidArgumentException(sprintf('Package %s not found in bower.json.', $package->getName()));
        }

        $package->setRequiredVersion($decode['dependencies'][$package->getName()]);

        $bowerJson = $this->filesystem->read($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json');
        $bower = json_decode($bowerJson, true);
        if (is_null($bower)) {
            throw new RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $package->getName()));
        }
        $package->setInfo($bower);
        $package->setVersion($bower['version']);
        $package->setRequires(isset($bower['dependencies']) ? $bower['dependencies'] : null);

        // look for package in bower
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . $package->getName());
            $response = $request->send();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }

        // open package repository
        $repoUrl = $decode['url'];
        $this->repository->setUrl($repoUrl)->setHttpClient($this->httpClient);
        $bowerJson = $this->repository->getBower();
        $bower = json_decode($bowerJson, true);
        if (!is_array($bower)) {
            throw new RuntimeException(sprintf('Invalid bower.json found in package %s: %s.', $package->getName(), $bowerJson));
        }
        $packageTag = $this->repository->findPackage($package->getRequiredVersion());
        if (is_null($packageTag)) {
            throw new RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getVersion()));
        }
        $package->setRepository($this->repository);

        // get release archive from repository
        $file = $this->repository->getRelease();

        // match installed package version with lastest available version
        if ($packageTag == $package->getVersion()) {
            // if version is fully matching, there's no need to update
            return;
        }
        $package->setVersion($packageTag);

        // save temporary file
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        $this->filesystem->write($tmpFileName, $file, true);

        $installer->update($package);

        // check for dependencies
        if (!empty($dependencies = $package->getRequires())) {
            foreach ($dependencies as $name => $requiredVersion) {
                $depPackage = new Package($name, $requiredVersion);
                if (!$this->isPackageInstalled($depPackage)) {
                    $this->installPackage($depPackage, $installer, true);
                } else {
                    $this->updatePackage($depPackage, $installer);
                }
            }
        }
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
            foreach ($decode['dependencies'] as $name => $requiredVersion) {
                $package = new Package($name, $requiredVersion);
                $this->updatePackage($package, $installer);
            }
        }
    }

    /**
     * @param  PackageInterface $package
     * @param  string           $info
     * @return string
     */
    public function getPackageInfo(PackageInterface $package, $info = 'url')
    {
        // look for package in bower
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . urlencode($package->getName()));
            $response = $request->send();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }
        $this->repository->setHttpClient($this->httpClient);

        if ($info == 'url') {
            $this->repository->setUrl($decode['url'], false);

            return $this->repository->getUrl();
        }

        if ($info == 'original_url') {
            $this->repository->setUrl($decode['url'], false);

            return array('name' => $decode['name'], 'url' =>$this->repository->getOriginalUrl());
        }

        if ($info == 'bower') {
            $this->repository->setUrl($decode['url'], false);
            $tag = $this->repository->findPackage($package->getRequiredVersion());

            return $this->repository->getBower($tag, true, $decode['url']);
        }

        if ($info == 'versions') {
            return $this->repository->getTags();
        }

        throw new RuntimeException(sprintf('Unsupported info option "%s".', $info));
    }

    /**
     * Uninstall a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function uninstallPackage(PackageInterface $package, InstallerInterface $installer)
    {
        if (!$this->isPackageInstalled($package)) {
            throw new RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }
        $installer->uninstall($package);
    }

    /**
     * Search packages by name
     *
     * @param  string $name
     * @return array
     */
    public function searchPackages($name)
    {
        try {
            $url = $this->config->getAllPackagesUrl();
            $request = $this->httpClient->get($url);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot get package list from %s.', $url));
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
     * Get a list of installed packages
     *
     * @param  InstallerInterface $installer
     * @return array
     */
    public function getInstalledPackages(InstallerInterface $installer)
    {
        return $installer->getInstalled();
    }

    /**
     * Check if package is installed
     *
     * @param  PackageInterface $package
     * @return boolean
     */
    public function isPackageInstalled(PackageInterface $package)
    {
        return $this->filesystem->has($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json');
    }

    /**
     * {@inheritDoc}
     */
    public function isPackageExtraneous(PackageInterface $package, $checkInstall = false)
    {
        if ($checkInstall && !$this->isPackageInstalled($package)) {
            return false;
        }
        try {
            $bower = $this->config->getBowerFileContent();
        } catch (RuntimeException $e) { // no bower.json file, package is extraneous

            return true;
        }
        if (!isset($bower['dependencies'])) {
            return true;
        }

        return !isset($bower['dependencies'][$package->getName()]);
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
    {
        $authors = array('Beelab <info@bee-lab.net>');
        if (!empty($params['author'])) {
            $authors[] = $params['author'];
        }
        $structure =  array(
            'name' => $params['name'],
            'authors' => $authors,
            'private' => true,
            'dependencies' => new \StdClass(),
        );

        return $structure;
    }
}
