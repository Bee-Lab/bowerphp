<?php


namespace Bowerphp\Installer;

use Bowerphp\Package\PackageInterface;
use Bowerphp\Repository\RepositoryInterface;

/**
 * Interface for the package installation manager.
 *
 */
interface InstallerInterface
{
    /**
     * Checks that provided package is installed.
     *
     * @param PackageInterface             $package package instance
     *
     * @return bool
     */
    public function isInstalled(PackageInterface $package);

    /**
     * Installs specific package.
     *
     * @param PackageInterface             $package package instance
     */
    public function install(PackageInterface $package);

    /**
     * Updates specific package.
     *
     * @param PackageInterface             $initial already installed package version
     * @param PackageInterface             $target  updated version
     *
     * @throws InvalidArgumentException if $initial package is not installed
     */
    public function update(PackageInterface $initial, PackageInterface $target);

    /**
     * Uninstalls specific package.
     *
     * @param PackageInterface             $package package instance
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
