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
     * Installs specific package.
     *
     * @param PackageInterface $package package instance
     */
    public function install(PackageInterface $package);

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
