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

/**
 * ConfigInterface
 */
interface ConfigInterface
{
    /**
     * @return string
     */
    public function getBasePackagesUrl();

    /**
     * @return string
     */
    public function getAllPackagesUrl();

    /**
     * @return string
     */
    public function getCacheDir();

    /**
     * @return string
     */
    public function getInstallDir();

    /**
     * @return boolean
     */
    public function getSaveToBowerJsonFile();

    /**
     * Set true|false for decide if add package reference on bower.json file during install procedure
     *
     * @params boolean $flag default true
     */
    public function setSaveToBowerJsonFile($flag = true);

    /**
     * @params array   $params
     * @return integer
     */
    public function initBowerJsonFile(array $params);

    /**
     * Update bower.json with a new added package
     *
     * @params Package $package
     * @params string  $packageVersion
     * @return integer
     */
    public function updateBowerJsonFile(PackageInterface $package, $packageVersion);

    /**
     * Update bower.json from a previous existing one
     *
     * @params array   $old values of previous bower.json
     * @params array   $new new values
     * @return integer
     */
    public function updateBowerJsonFile2(array $old, array $new);

    /**
     * @return string
     */
    public function getBowerFileName();

    /**
     * @return array
     * @throws Exception if bower.json does not exist
     */
    public function getBowerFileContent();

    /**
     * @return array
     * @throws Exception if bower.json or package.json does not exist in a dir of installed package
     */
    public function getPackageBowerFileContent(PackageInterface $package);

    /**
     * @return boolean
     */
    public function writeBowerFile();

    /**
     * @return boolean
     */
    public function bowerFileExists();
}
