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
    public function isSaveToBowerJsonFile();

    /**
     * Set true|false for decide if add package reference on bower.json file during install procedure
     *
     * @param boolean $flag default true
     */
    public function setSaveToBowerJsonFile($flag = true);

    /**
     * Init project's bower.json file
     *
     * @param  array   $params
     * @return integer
     */
    public function initBowerJsonFile(array $params);

    /**
     * Update project's bower.json with a new added package
     *
     * @param  Package $package
     * @return integer
     */
    public function updateBowerJsonFile(PackageInterface $package);

    /**
     * Update project's bower.json from a previous existing one
     *
     * @param  array   $old values of previous bower.json
     * @param  array   $new new values
     * @return integer
     */
    public function updateBowerJsonFile2(array $old, array $new);

    /**
     * Get content from project's bower.json file
     *
     * @return array
     * @throws Exception if bower.json does not exist
     */
    public function getBowerFileContent();

    /**
     * Get content from a packages' bower.json file
     *
     * @return array
     * @throws Exception if bower.json or package.json does not exist in a dir of installed package
     */
    public function getPackageBowerFileContent(PackageInterface $package);

    /**
     * Check if project's bower.json file exists
     *
     * @return boolean
     */
    public function bowerFileExists();
}
