<?php

namespace Bowerphp\Installer;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\Json;
use Bowerphp\Util\ZipArchive;
use RuntimeException;
use Symfony\Component\Finder\Finder;

/**
 * Package installation manager.
 *
 */
class Installer implements InstallerInterface
{
    protected $filesystem;
    protected $zipArchive;
    protected $config;

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
        $tmpFileName = $this->config->getCacheDir().'/tmp/'.$package->getName();
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $info = $package->getInfo();
        $files = $this->filterZipFiles($this->zipArchive, isset($info['ignore']) ? $info['ignore'] : array());
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir().'/'.str_replace($dirName, $package->getName(), $file);
            if (substr($fileName, -1) != '/') {
                $fileContent = $this->zipArchive->getStream($file);
                $this->filesystem->write($fileName, $fileContent);
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        // adjust timestamp for directories
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir().'/'.str_replace($dirName, $package->getName(), $file);
            if (is_dir($fileName) && substr($fileName, -1) == '/') {
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        $this->zipArchive->close();

        // create .bower.json metadata file
        // XXX we still need to add some other info...
        $dotBowerJson = Json::encode($package->getInfo());
        $this->filesystem->write($this->config->getInstallDir().'/'.$package->getName().'/.bower.json', $dotBowerJson);
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $package)
    {
        // install files
        $tmpFileName = $this->config->getCacheDir().'/tmp/'.$package->getName();
        if ($this->zipArchive->open($tmpFileName) !== true) {
            throw new RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $info = $package->getInfo();
        $files = $this->filterZipFiles($this->zipArchive, isset($info['ignore']) ? $info['ignore'] : array());
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir().'/'.str_replace($dirName, $package->getName(), $file);
            if (substr($fileName, -1) != '/') {
                $fileContent = $this->zipArchive->getStream($file);
                $this->filesystem->write($fileName, $fileContent);
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        // adjust timestamp for directories
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir().'/'.str_replace($dirName, $package->getName(), $file);
            if (substr($fileName, -1) == '/' && is_dir($fileName)) {
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        $this->zipArchive->close();

        // update .bower.json metadata file
        // XXX we still need to add some other info...
        $dotBowerContent = array_merge($package->getInfo(), array('version' => $package->getRequiredVersion()));
        $dotBowerJson = Json::encode($dotBowerContent);
        $this->filesystem->write($this->config->getInstallDir().'/'.$package->getName().'/.bower.json', $dotBowerJson);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(PackageInterface $package)
    {
        $this->removeDir($this->config->getInstallDir().'/'.$package->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getInstalled(Finder $finder)
    {
        $packages = array();
        if (!$this->filesystem->exists($this->config->getInstallDir())) {
            return $packages;
        }

        $directories = $finder->directories()->in($this->config->getInstallDir());

        foreach ($directories as $packageDirectory) {
            if ($this->filesystem->exists($packageDirectory.'/.bower.json')) {
                $bowerJson = $this->filesystem->read($packageDirectory.'/.bower.json');
                $bower = json_decode($bowerJson, true);
                if (is_null($bower)) {
                    throw new RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $packageDirectory));
                }
                $packages[] = new Package(
                    $bower['name'],
                    null,
                    isset($bower['version']) ? $bower['version'] : null,
                    isset($bower['dependencies']) ? $bower['dependencies'] : null
                );
            }
        }

        return $packages;
    }

    /**
     * {@inheritDoc}
     */
    public function findDependentPackages(PackageInterface $package, Finder $finder)
    {
        $return = array();
        $packages = $this->getInstalled($finder);
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
            #if ($stat['size'] > 0) {    // directories have sizes 0
                $return[] = $stat['name'];
            #}
        }
        $that = $this;
        $filter = array_filter($return, function ($var) use ($ignore, $dirName, $that) {
            return !$that->isIgnored($var, $ignore, $dirName);
        });

        return array_values($filter);
    }

    /**
     * @param string $dir
     */
    protected function removeDir($dir)
    {
        $this->filesystem->remove($dir);
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
        $vName = substr($name, strlen($dirName));
        //first check if there is line that overrides other lines
        foreach ($ignore as $pattern) {
            if (strpos($pattern, "!") !== 0) {
                continue;
            }
            $pattern = ltrim($pattern, "!");
            // the ! negates the line, otherwise the syntax is the same
            if ($this->isIgnored($name, array($pattern), $dirName)) {
                return false;
            }
        }
        foreach ($ignore as $pattern) {
            if (strpos($pattern, '**') !== false) {
                $pattern = str_replace('**', '*', $pattern);
                if (substr($pattern, 0, 1) == '/') {
                    $vName = '/'.$vName;
                }
                if (substr($vName, 0, 1) == '.') {
                    $vName = '/'.$vName;
                }
                if (fnmatch($pattern, $vName, FNM_PATHNAME)) {
                    return true;
                }
            } elseif (substr($pattern, -1) == '/') { // trailing slash
                if (substr($pattern, 0, 1) == '/') {
                    $pattern = substr($pattern, 1); // remove possible starting slash
                }
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^'.$escPattern.'#', $vName) > 0) {
                    return true;
                }
            } elseif (strpos($pattern, '/') === false) { // no slash
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^'.$escPattern.'#', $vName) > 0) {
                    return true;
                }
            } elseif (substr($pattern, 0, 1) == '/') {    // starting slash
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^'.$escPattern.'#', '/'.$vName) > 0) {
                    return true;
                }
            } else {
                $escPattern = str_replace(array('.', '*'), array('\.', '.*'), $pattern);
                if (preg_match('#^'.$escPattern.'#', $vName) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
