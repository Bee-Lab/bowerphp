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
     * @param PackageInterface $package package instance
     *
     * @return bool
     */
    public function isInstalled(PackageInterface $package);

    /**
     * Installs specific package.
     *
     * @param PackageInterface $package package instance
     * @param Boolean $isDepency
     */
    public function install(PackageInterface $package, $isDependency = false);

    /**
     * Updates specific package.
     *
     * @param PackageInterface $package
     *
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
     * @return string           path
     */
    public function getInstallPath(PackageInterface $package);
}
