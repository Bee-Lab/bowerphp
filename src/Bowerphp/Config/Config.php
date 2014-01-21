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
use Bowerphp\Util\Json;
use Gaufrette\Filesystem;
use RuntimeException;

/**
 * Config
 */
class Config implements ConfigInterface
{
    protected
        $cacheDir,
        $installDir,
        $filesystem,
        $basePackagesUrl       = 'http://bower.herokuapp.com/packages/',
        $allPackagesUrl        = 'https://bower-component-list.herokuapp.com/',
        $saveToBowerJsonFile   = false,
        $bowerFileName         = array('bower.json', 'package.json'),
        $standardBowerFileName = 'bower.json'
    ;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->cacheDir   = getenv('HOME') . '/.cache/bowerphp';
        $this->installDir = getcwd() . '/bower_components';
        $rc               = getcwd() . '/.bowerrc';

        if ($this->filesystem->has($rc)) {
            $json = json_decode($this->filesystem->read($rc), true);
            if (is_null($json)) {
                throw new RuntimeException('Invalid .bowerrc file.');
            }
            if (isset($json['directory'])) {
                $this->installDir = getcwd() . '/' . $json['directory'];
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
    public function getSaveToBowerJsonFile()
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
        $file = getcwd() . '/' . $this->getBowerFileName();
        $json = Json::encode($this->createAClearBowerFile($params));

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBowerJsonFile(PackageInterface $package, $packageVersion)
    {
        if (!$this->getSaveToBowerJsonFile()) {
            return false;
        }

        $decode = $this->getBowerFileContent();
        $decode['dependencies'][$package->getName()] = $packageVersion;
        $file = getcwd() . '/' . $this->getBowerFileName();
        $json = Json::encode($decode);

        return $this->filesystem->write($file, $json, true);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBowerJsonFile2(array $old, array $new)
    {
        $json = Json::encode(array_merge($old, $new));
        $file = getcwd() . '/' . $this->getBowerFileName();

        return $this->filesystem->write($file, $json, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getBowerFileName()
    {
        return $this->standardBowerFileName;
    }

    /**
     * {@inheritDoc}
     */
    public function getBowerFileContent()
    {
        if (!$this->filesystem->has(getcwd() . '/' . $this->getBowerFileName())) {
            throw new RuntimeException('No ' . $this->getBowerFileName() . ' found. You can run "init" command to create it.');
        }
        $bowerJson = $this->filesystem->read(getcwd() . '/' . $this->getBowerFileName());
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
    }

    /**
     * {@inheritDoc}
     */
    public function writeBowerFile()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function bowerFileExists()
    {
        return $this->filesystem->has(getcwd() . '/' . $this->getBowerFileName());
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
