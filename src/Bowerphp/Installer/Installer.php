<?php

namespace Bowerphp\Installer;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Util\ZipArchive;
use Gaufrette\Filesystem;
use RuntimeException;

/**
 * Package installation manager.
 *
 */
class Installer implements InstallerInterface
{
    protected
        $filesystem,
        $zipArchive,
        $config
    ;

    /**
     * Initializes library installer.
     *
     * @param Filesystem      $filesystem
     * @param ZipArchive      $zipArchive
     * @param ConfigInterface $config
     */
    public function __construct(Filesystem $filesystem, ZipArchive $zipArchive, ConfigInterface $config)
    {
        $this->filesystem = $filesystem;
        $this->zipArchive = $zipArchive;
        $this->config     = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function install(PackageInterface $package)
    {
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $info = $package->getInfo();
        $files = $this->filterZipFiles($this->zipArchive, isset($info['ignore']) ? $info['ignore'] : array());
        foreach ($files as $file) {
            $fileName = $this->config->getInstallDir() . '/' . str_replace($dirName, $package->getName(), $file);
            $fileContent = $this->zipArchive->getStream($file);
            $this->filesystem->write($fileName, $fileContent, true);
        }

        $this->zipArchive->close();

        // create .bower.json metadata file
        // XXX for now, we just save some basic info
        $dotBowerJson = json_encode(array('name' => $package->getName(), 'version' => $package->getVersion()));
        $this->filesystem->write($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json', $dotBowerJson, true);
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $package)
    {
        // install files
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $info = $package->getInfo();
        $files = $this->filterZipFiles($this->zipArchive, isset($info['ignore']) ? $info['ignore'] : array());
        foreach ($files as $file) {
            $fileName = $this->config->getInstallDir() . '/' . str_replace($dirName, $package->getName(), $file);
            $fileContent = $this->zipArchive->getStream($file);
            $this->filesystem->write($fileName, $fileContent, true);
        }
        $this->zipArchive->close();

        // update .bower.json metadata file
        // XXX for now, we just save some basic info
        $dotBowerJson = json_encode(array('name' => $package->getName(), 'version' => $package->getVersion()));
        $this->filesystem->write($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json', $dotBowerJson, true);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(PackageInterface $package)
    {
        $this->removeDir($this->config->getInstallDir() . '/' . $package->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getInstalled()
    {
        $packages = array();

        $keys = $this->filesystem->listKeys($this->config->getInstallDir());
        foreach ($keys['dirs'] as $packageDirectory) {
            if ($this->filesystem->has($packageDirectory . '/.bower.json')) {
                $bowerJson = $this->filesystem->read($packageDirectory . '/.bower.json');
                $bower = json_decode($bowerJson, true);
                if (is_null($bower)) {
                    throw new RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $packageDirectory));
                }
                $packages[] = new Package($bower['name'], null, $bower['version'], isset($bower['dependencies']) ? $bower['dependencies'] : null);
            }
        }

        return $packages;
    }

    /**
     * {@inheritDoc}
     */
    public function findDependentPackages(PackageInterface $package)
    {
        $return = array();
        $packages = $this->getInstalled();
        foreach ($packages as $installedPackage) {
            $requires = $installedPackage->getRequires();
            if (isset($requires[$package->getName()])) {
                $return[$requires[$package->getName()]] = $installedPackage;
            }
        }

        return $return;
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
        $dirName = $archive->getNameIndex(0);
        $return = array();
        $numFiles = $archive->getNumFiles();
        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $archive->statIndex($i);
            if ($stat['size'] > 0) {    // directories have sizes 0
                $return[] = $stat['name'];
            }
        }
        $filter = array_filter($return, function ($var) use ($ignore, $dirName) {
            return !$this->isIgnored($var, $ignore, $dirName);
        });

        return array_values($filter);
    }

    /**
     * @param string $dir
     */
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

    /**
     * Check if a file should be ignored
     *
     * @param  string  $name    file's name
     * @param  array   $ignore  list of ignores
     * @param  string  $dirName dir's name (to be removed from file's name)
     * @return boolean
     */
    public function isIgnored($name, array $ignore, $dirName)
    {
        $v = substr($name, strlen($dirName));
        foreach ($ignore as $pattern) {
            if (strpos($pattern, '**') !== false) {
                $pattern = str_replace('**', '*', $pattern);
                if (substr($pattern, 0, 1) == '/') {
                    $v = '/'. $v;
                }
                if (substr($v, 0, 1) == '.') {
                    $v = '/'. $v;
                }
                if (fnmatch($pattern, $v, FNM_PATHNAME)) {
                    return true;
                }
            } elseif (substr($pattern, -1) == '/') { // trailing slash
                if (substr($pattern, 0, 1) == '/') {
                    $pattern = substr($pattern, 1); // remove possible starting slash
                }
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^' . $escPattern . '#', $v) > 0) {
                    return true;
                }
            } elseif (strpos($pattern, '/') === false) { // no slash
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^' . $escPattern . '#', $v) > 0) {
                    return true;
                }
            } elseif (substr($pattern, 0, 1) == '/') {    // starting slash
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^' . $escPattern . '#', '/' . $v) > 0) {
                    return true;
                }
            } else {
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^' . $escPattern . '#', $v) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
