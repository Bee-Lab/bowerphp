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

use Gaufrette\Filesystem;

/**
 * Config
 */
class Config implements ConfigInterface
{
    protected
        $cacheDir,
        $installDir,
        $basePackagesUrl = 'http://bower.herokuapp.com/packages/'
    ;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->cacheDir = getenv('HOME') . '/.cache/bowerphp';
        $this->installDir = getcwd() . '/bower_components';
        $rc = getcwd() . '/.bowerrc';
        if ($filesystem->has($rc)) {
            $json = json_decode($filesystem->read($rc), true);
            if (is_null($json)) {
                throw new \RuntimeException('Invalid .bowerrc file.');
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
}
