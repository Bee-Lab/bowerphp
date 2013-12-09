<?php

namespace Bowerphp\Repository;

use Bowerphp\Package\PackageInterface;

/**
 * Repository interface.
 *
 */
interface RepositoryInterface
{
    const SEARCH_FULLTEXT = 0;
    const SEARCH_NAME = 1;

    /**
     * Checks if repo has bower
     *
     * @return bool
     */
    public function hasBower();

    /**
     * Searches for the first match of a package version.
     *
     * @param string $version package version
     *
     * @return string|null
     */
    public function findPackage($version = '*');

    /**
     * Get a release
     *
     * @param  string $type "zip" or "tar"
     * @return string       file content
     */
    public function getRelease($type = 'zip');
}
