<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Config;

use Bowerphp\Package\PackageInterface;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\Json;
use RuntimeException;

/**
 * Config
 */
class Config implements ConfigInterface
{
    protected $cacheDir;
    protected $installDir;
    protected $filesystem;
    protected $basePackagesUrl     = 'http://bower.herokuapp.com/packages/';
    protected $allPackagesUrl      = 'https://bower-component-list.herokuapp.com/';
    protected $saveToBowerJsonFile = false;
    protected $bowerFileNames      = array('bower.json', 'package.json');
    protected $stdBowerFileName    = 'bower.json';

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->cacheDir   = getenv('HOME').'/.cache/bowerphp';
        $this->installDir = getcwd().'/bower_components';
        $rc               = getcwd().'/.bowerrc';

        if ($this->filesystem->exists($rc)) {
            $json = json_decode($this->filesystem->read($rc), true);
            if (is_null($json)) {
                throw new RuntimeException('Invalid .bowerrc file.');
            }
            if (isset($json['directory'])) {
                $this->installDir = getcwd().'/'.$json['directory'];
            }
            if (isset($json['storage']) && isset($json['storage']['packages'])) {
                $this->cacheDir = $json['storage']['packages'];
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBasePackagesUrl()
    {
        return $this->basePackagesUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllPackagesUrl()
    {
        return $this->allPackagesUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallDir()
    {
        return $this->installDir;
    }

    /**
     * {@inheritDoc}
     */
    public function isSaveToBowerJsonFile()
    {
        return $this->saveToBowerJsonFile;
    }

    /**
     * {@inheritDoc}
     */
    public function setSaveToBowerJsonFile($flag = true)
    {
        $this->saveToBowerJsonFile = $flag;
    }

    /**
     * {@inheritDoc}
     */
    public function initBowerJsonFile(array $params)
    {
        $file = getcwd().'/'.$this->stdBowerFileName;
        $json = Json::encode($this->createAClearBowerFile($params));

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBowerJsonFile(PackageInterface $package)
    {
        if (!$this->isSaveToBowerJsonFile()) {
            return false;
        }

        $decode = $this->getBowerFileContent();
        $decode['dependencies'][$package->getName()] = $package->getRequiredVersion();
        $file = getcwd().'/'.$this->stdBowerFileName;
        $json = Json::encode($decode);

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBowerJsonFile2(array $old, array $new)
    {
        $json = Json::encode(array_merge($old, $new));
        $file = getcwd().'/'.$this->stdBowerFileName;

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritDoc}
     */
    public function getBowerFileContent()
    {
        if (!$this->filesystem->exists(getcwd().'/'.$this->stdBowerFileName)) {
            throw new RuntimeException('No '.$this->stdBowerFileName.' found. You can run "init" command to create it.');
        }
        $bowerJson = $this->filesystem->read(getcwd().'/'.$this->stdBowerFileName);
        if (empty($bowerJson) || !is_array($decode = json_decode($bowerJson, true))) {
            throw new RuntimeException(sprintf('Malformed JSON %s.', $bowerJson));
        }

        return $decode;
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageBowerFileContent(PackageInterface $package)
    {
        $file = $this->getInstallDir().'/'.$package->getName().'/.bower.json';
        if (!$this->filesystem->exists($file)) {
            throw new RuntimeException(sprintf('Could not find .bower.json file for package %s.', $package->getName()));
        }
        $bowerJson = $this->filesystem->read($file);
        $bower = json_decode($bowerJson, true);
        if (is_null($bower)) {
            throw new RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $package->getName()));
        }

        return $bower;
    }

    /**
     * {@inheritDoc}
     */
    public function bowerFileExists()
    {
        return $this->filesystem->exists(getcwd().'/'.$this->stdBowerFileName);
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
    {
        $structure =  array(
            'name' => $params['name'],
            'authors' => array(
                0 => 'Beelab <info@bee-lab.net>',
                1 => $params['author'],
            ),
            'private' => true,
            'dependencies' => new \StdClass(),
        );

        return $structure;
    }
}
