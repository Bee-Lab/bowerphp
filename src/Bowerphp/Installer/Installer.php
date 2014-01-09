<?php

namespace Bowerphp\Installer;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Repository\RepositoryInterface;
use Bowerphp\Util\ZipArchive;
use Gaufrette\Filesystem;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

/**
 * Package installation manager.
 *
 */
class Installer implements InstallerInterface
{
    protected
        $filesystem,
        $httpClient,
        $repository,
        $zipArchive,
        $config,
        $output
    ;

    /**
     * Initializes library installer.
     *
     * @param Filesystem            $filesystem
     * @param ClientInterface       $httpClient
     * @param RepositoryInterface   $repository
     * @param ZipArchive            $zipArchive
     * @param ConfigInterface       $config
     * @param BowerphpConsoleOutput $output
     */
    public function __construct(Filesystem $filesystem, ClientInterface $httpClient, RepositoryInterface $repository, ZipArchive $zipArchive, ConfigInterface $config, BowerphpConsoleOutput $output)
    {
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->zipArchive = $zipArchive;
        $this->config     = $config;
        $this->output     = $output;
    }

    /**
     * {@inheritDoc}
     */
    public function isInstalled(PackageInterface $package)
    {
        $package->setTargetDir($this->config->getInstallDir());

        return $this->filesystem->has($package->getTargetDir() . '/' . $package->getName() . '/.bower.json');
    }

    /**
     * {@inheritDoc}
     */
    public function install(PackageInterface $package, $isDependency = false)
    {
        $this->output->writelnInfoPackage($package);

        $package->setTargetDir($this->config->getInstallDir());
        // look for package in bower
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . $package->getName());
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new \RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }

        // open package repository
        $repoUrl = $decode['url'];
        $this->repository->setUrl($repoUrl)->setHttpClient($this->httpClient);
        $bowerJson = $this->repository->getBower();
        $bower = json_decode($bowerJson, true);
        if (!is_array($bower)) {
            throw new \RuntimeException(sprintf('Invalid bower.json found in package %s: %s.', $package->getName(), $bowerJson));
        }
        $packageVersion = $this->repository->findPackage($package->getVersion());
        if (is_null($packageVersion)) {
            throw new \RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getVersion()));
        }
        $package->setRepository($this->repository);

        // get release archive from repository
        $file = $this->repository->getRelease();

        $this->output->writelnInstalledPackage($package, $packageVersion);

        // install files
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        $this->filesystem->write($tmpFileName, $file, true);
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new \RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $files = $this->filterZipFiles($this->zipArchive, isset($bower['ignore']) ? $bower['ignore'] : array());
        foreach ($files as $file) {
            $fileName = $package->getTargetDir() . '/' . str_replace($dirName, $package->getName(), $file);
            $fileContent = $this->zipArchive->getStream($file);
            $this->filesystem->write($fileName, $fileContent, true);
        }

        if ($this->config->getSaveToBowerJsonFile() && !$isDependency) {
            try {
                $this->config->updateBowerJsonFile($package, $packageVersion);
            } catch (RuntimeException $e) {
                $this->output->writelnNoBowerJsonFile();
            }
        }

        $this->zipArchive->close();

        // create .bower.json metadata file
        // XXX for now, we just save some basic info
        $dotBowerJson = json_encode(array('name' => $package->getName(), 'version' => $packageVersion));
        $this->filesystem->write($package->getTargetDir() . '/' . $package->getName() . '/.bower.json', $dotBowerJson, true);

        // check for dependencies
        if (!empty($bower['dependencies'])) {
            foreach ($bower['dependencies'] as $name => $version) {
                $depPackage = new Package($name, $version);
                $this->install($depPackage, true);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $package)
    {
        // look for installed package
        if (!$this->isInstalled($package)) {
            throw new \RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }
        $bowerJson = $this->filesystem->read($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json');
        $bower = json_decode($bowerJson, true);
        if (is_null($bower)) {
            throw new \RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $package->getName()));
        }
        $version = $bower['version'];

        // match installed package version with $package version
        if ($version == $package->getVersion()) {
            // if version is fully matching, OK
            return;
        }

        $package->setTargetDir($this->config->getInstallDir());

        // look for package in bower
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . $package->getName());
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new \RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }

        // open package repository
        $repoUrl = $decode['url'];
        $this->repository->setUrl($repoUrl)->setHttpClient($this->httpClient);
        $bowerJson = $this->repository->getBower();
        $bower = json_decode($bowerJson, true);
        if (!is_array($bower)) {
            throw new \RuntimeException(sprintf('Invalid bower.json found in package %s: %s.', $package->getName(), $bowerJson));
        }
        $packageVersion = $this->repository->findPackage($package->getVersion());
        if (is_null($packageVersion)) {
            throw new \RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getVersion()));
        }
        $package->setRepository($this->repository);

        // get release archive from repository
        $file = $this->repository->getRelease();

        // match installed package version with lastest available version
        if ($packageVersion == $version) {
            // if version is fully matching, OK
            return;
        }

        // install files
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        $this->filesystem->write($tmpFileName, $file, true);
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new \RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $files = $this->filterZipFiles($this->zipArchive, isset($bower['ignore']) ? $bower['ignore'] : array());
        foreach ($files as $file) {
            $fileName = $package->getTargetDir() . '/' . str_replace($dirName, $package->getName(), $file);
            $fileContent = $this->zipArchive->getStream($file);
            $this->filesystem->write($fileName, $fileContent, true);
        }
        $this->zipArchive->close();

        // update .bower.json metadata file
        // XXX for now, we just save some basic info
        $dotBowerJson = json_encode(array('name' => $package->getName(), 'version' => $packageVersion));
        $this->filesystem->write($package->getTargetDir() . '/' . $package->getName() . '/.bower.json', $dotBowerJson, true);

        // check for dependencies
        if (!empty($bower['dependencies'])) {
            foreach ($bower['dependencies'] as $name => $version) {
                $depPackage = new Package($name, $version);
                if (!$this->isInstalled($depPackage)) {
                    $this->install($depPackage);
                } else {
                    $this->update($depPackage);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(PackageInterface $package)
    {
        // look for installed package
        if (!$this->isInstalled($package)) {
            throw new \RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }
        $this->removeDir($package->getTargetDir() . '/' . $package->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
    }

    /**
     * @param  PackageInterface $package
     * @param  string           $info    one of 'url', 'bower', 'versions', 'original_url'
     * @return mixed            string if $info = 'url' or 'bower', array if $info = 'versions' or info = 'original_url'
     */
    public function getPackageInfo(PackageInterface $package, $info = 'url')
    {
        // look for package in bower
        try {
            $request = $this->httpClient->get($this->config->getBasePackagesUrl() . $package->getName());
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot download package %s (%s).', $package->getName(), $e->getMessage()));
        }
        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new \RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package->getName()));
        }
        $this->repository->setHttpClient($this->httpClient);

        if ($info == 'url') {
            $this->repository->setUrl($decode['url'], false);

            return $this->repository->getUrl();
        }

        if ($info == 'original_url') {
            $this->repository->setUrl($decode['url'], false);

            return Array('name' => $decode['name'], 'url' =>$this->repository->getOriginalUrl());
        }

        if ($info == 'bower') {
            $this->repository->setUrl($decode['url'], false);
            $tag = $this->repository->findPackage($package->getVersion());

            return $this->repository->getBower($tag, true, $decode['url']);
        }

        if ($info == 'versions') {
            return $this->repository->getTags();
        }

        throw new RuntimeException(sprintf('Unsupported info option "%s".', $info));
    }

    /**
     * Filter archive files based on an "ignore" list.
     *
     * @param  ZipArchive $archive
     * @param  array      $ignore
     * @return array
     */
    protected function filterZipFiles(ZipArchive $archive, array $ignore = array())
    {
        $return = array();
        $numFiles = $archive->getNumFiles();
        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $archive->statIndex($i);
            if ($stat['size'] > 0) {    // directories have sizes 0
                $return[] = $stat['name'];
            }
        }
        $filter = array_filter($return, function ($var) use ($ignore) {
            foreach ($ignore as $pattern) {
                if (fnmatch($pattern, $var)) {
                    return false;
                }
            }

            return true;
        });

        return array_values($filter);
    }

    protected function removeDir($dir)
    {
        $dir = substr($dir, -1) == '/' ? $dir : $dir . '/';
        $keys = $this->filesystem->listKeys($dir);

        if (!empty($keys['dirs'])) {
            foreach ($keys['dirs'] as $d) {
                $this->removeDir($d);
            }
        }
        if (!empty($keys['keys'])) {
            foreach ($keys['keys'] as $k) {
                $this->filesystem->delete($k);
            }
        }

        $this->filesystem->delete($dir);
    }
}
