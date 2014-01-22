<?php

namespace Bowerphp\Installer;

use Bowerphp\Package\PackageInterface;

/**
 * Interface for the package installation manager.
 *
 */
interface InstallerInterface
{
    /**
     * Checks that provided package is installed.
     *
     * @param  PackageInterface $package package instance
     * @return boolean
     */
    public function isInstalled(PackageInterface $package);

    /**
     * Checks if provided package is extraneous (i.e. included in bower.json) or not.
     *
     * @param  PackageInterface $package      package instance
     * @param  boolean          $checkInstall if true, pre-check if package is installed
     * @return boolean
     */
    public function isExtraneous(PackageInterface $package, $checkInstall = false);

    /**
     * Installs specific package.
     *
     * @param PackageInterface $package      package instance
     * @param boolean          $isDependency
     */
    public function install(PackageInterface $package, $isDependency = false);

    /**
     * Updates specific package.
     *
     * @param  PackageInterface         $package
     * @throws InvalidArgumentException if package is not installed
     */
    public function update(PackageInterface $package);

    /**
     * Uninstalls specific package.
     *
     * @param PackageInterface $package package instance
     */
    public function uninstall(PackageInterface $package);

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string
     */
    public function getInstallPath(PackageInterface $package);

    /**
     * Get installed packages.
     *
     * @return array
     */
    public function getInstalled();

    /**
     * Find packages that depend on given package.
     *
     * @param  PackageInterface $package
     * @return array
     */
    public function findDependentPackages(PackageInterface $package);
}
