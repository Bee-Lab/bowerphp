<?php

namespace Bowerphp\Installer;

use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Repository\RepositoryInterface;
use Gaufrette\Filesystem;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

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
        $baseUrl = 'http://bower.herokuapp.com/packages/',
        $installDir = 'bower_components'
    ;

    /**
     * Initializes library installer.
     *
     */
    public function __construct(Filesystem $filesystem, ClientInterface $httpClient, RepositoryInterface $repository, \ZipArchive $zipArchive)
    {
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->zipArchive = $zipArchive;
    }

    /**
     * {@inheritDoc}
     */
    public function isInstalled(PackageInterface $package)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function install(PackageInterface $package)
    {
        $package->setTargetDir($this->installDir);
        // look for package in bower
        try {
            $request = $this->httpClient->get($this->baseUrl . $package->getName());
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
            throw new \RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getVersion()), 6);
        }
        $package->setRepository($this->repository);

        // get release archive from repository
        $file = $this->repository->getRelease();

        // install files
        $tmpFileName = './tmp/' . $package->getName();
        if (!is_readable($tmpFileName)) {
            $this->filesystem->write($tmpFileName, $file);
        }
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new \RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        for ($i = 1; $i < $this->zipArchive->numFiles; $i++) {
            $stat = $this->zipArchive->statIndex($i);
            if ($stat['size'] > 0) {    // directories have sizes 0
                $fileName = $package->getTargetDir() . '/' . str_replace($dirName, $package->getName(), $stat['name']);
                $fileContent = $this->zipArchive->getStream($stat['name']);
                $this->filesystem->write($fileName, $fileContent, true);
            }
        }
        $this->zipArchive->close();

        // check for dependencies
        if (!empty($bower['dependencies'])) {
            foreach ($bower['dependencies'] as $name => $version) {
                $depPackage = new Package($name, $version);
                $this->install($depPackage);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $initial, PackageInterface $target)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(PackageInterface $package)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
    }

    protected function getPackageBasePath(PackageInterface $package)
    {

    }

}
