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
    protected $currentDir;
    protected $cacheDir;
    protected $installDir;
    protected $filesystem;
    protected $basePackagesUrl = 'http://bower.herokuapp.com/packages/';
    protected $allPackagesUrl = 'https://bower-component-list.herokuapp.com/';
    protected $saveToBowerJsonFile = false;
    protected $bowerFileNames = array('bower.json', 'package.json');
    protected $stdBowerFileName = 'bower.json';
    protected $rcFileName = '.bowerrc';

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->currentDir = getcwd();
        $this->filesystem = $filesystem;
        $this->cacheDir = $this->getHomeDir() . '/.cache/bowerphp';
        $this->installDir = $this->currentDir . '/bower_components';
        $rcPath = $this->getBowerrcPath();

        if ($rcPath) {
            $rc = $rcPath . '/' . $this->rcFileName;
            $json = json_decode($this->filesystem->read($rc), true);
            if (is_null($json)) {
              throw new RuntimeException('Invalid .bowerrc file.');
            }
            if (isset($json['cwd'])) {
              $this->currentDir = $rcPath . '/' . $json['cwd'];
            }
            if (isset($json['directory'])) {
              $this->installDir = $rcPath . '/' . $json['directory'];
            }
            if (isset($json['storage']) && isset($json['storage']['packages'])) {
              $this->cacheDir = $json['storage']['packages'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentDir() 
    {
        return $this->currentDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePackagesUrl()
    {
        return $this->basePackagesUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPackagesUrl()
    {
        return $this->allPackagesUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallDir()
    {
        return $this->installDir;
    }

    /**
     * {@inheritdoc}
     */
    public function isSaveToBowerJsonFile()
    {
        return $this->saveToBowerJsonFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setSaveToBowerJsonFile($flag = true)
    {
        $this->saveToBowerJsonFile = $flag;
    }

    /**
     * {@inheritdoc}
     */
    public function initBowerJsonFile(array $params)
    {
        $file = $this->currentDir . '/' . $this->stdBowerFileName;
        $json = Json::encode($this->createAClearBowerFile($params));

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritdoc}
     */
    public function updateBowerJsonFile(PackageInterface $package)
    {
        if (!$this->isSaveToBowerJsonFile()) {
            return 0;
        }

        $decode = $this->getBowerFileContent();
        $decode['dependencies'][$package->getName()] = $package->getRequiredVersion();
        $file = $this->currentDir . '/' . $this->stdBowerFileName;
        $json = Json::encode($decode);

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritdoc}
     */
    public function updateBowerJsonFile2(array $old, array $new)
    {
        $json = Json::encode(array_merge($old, $new));
        $file = $this->currentDir . '/' . $this->stdBowerFileName;

        return $this->filesystem->write($file, $json);
    }

    /**
     * {@inheritdoc}
     */
    public function getBowerFileContent()
    {
        if (!$this->bowerFileExists()) {
            throw new RuntimeException('No ' . $this->stdBowerFileName . ' found. You can run "init" command to create it.');
        }
        $bowerJson = $this->filesystem->read($this->currentDir . '/' . $this->stdBowerFileName);
        if (empty($bowerJson) || !is_array($decode = json_decode($bowerJson, true))) {
            throw new RuntimeException(sprintf('Malformed JSON in %s: %s.', $this->stdBowerFileName, $bowerJson));
        }

        return $decode;
    }

    /**
     * {@inheritdoc}
     */
    public function getOverridesSection()
    {
        if ($this->bowerFileExists()) {
            $bowerData = $this->getBowerFileContent();
            if ($bowerData && array_key_exists('overrides', $bowerData)) {
                return $bowerData['overrides'];
            }
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getOverrideFor($packageName)
    {
        $overrides = $this->getOverridesSection();
        if (array_key_exists($packageName, $overrides)) {
            return $overrides[$packageName];
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageBowerFileContent(PackageInterface $package)
    {
        $file = $this->getInstallDir() . '/' . $package->getName() . '/.bower.json';
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
     * {@inheritdoc}
     */
    public function bowerFileExists()
    {
        return $this->filesystem->exists($this->currentDir . '/' . $this->stdBowerFileName);
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
    {
        $structure = array(
            'name'    => $params['name'],
            'authors' => array(
                0 => 'Beelab <info@bee-lab.net>',
                1 => $params['author'],
            ),
            'private'      => true,
            'dependencies' => new \StdClass(),
        );

        return $structure;
    }

    /**
     * @return string
     */
    protected function getHomeDir()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $appData = getenv('APPDATA');
            if (empty($appData)) {
                throw new \RuntimeException('The APPDATA environment variable must be set for bowerphp to run correctly');
            }

            return strtr($appData, '\\', '/');
        }
        $home = getenv('HOME');
        if (empty($home)) {
            throw new \RuntimeException('The HOME environment variable must be set for bowerphp to run correctly');
        }

        return rtrim($home, '/');
    }

    protected function getBowerrcPath()
    {
      do {
        if ($this->filesystem->exists(getcwd() . '/' . $this->rcFileName)) {
          return getcwd();
        }
        chdir('..');
      }
      while(getcwd() !== '/');
      return false;
    }
}
