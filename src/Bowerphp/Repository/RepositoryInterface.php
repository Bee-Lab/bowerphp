<?php

namespace Bowerphp\Repository;

use Github\Client;

/**
 * Repository interface.
 *
 */
interface RepositoryInterface
{
    const VERSION_NOT_FOUND = 3;
    const INVALID_CRITERIA = 4;
    const INVALID_CANDIDATE = 5;

    /**
     * @param string  $url
     * @param boolean $raw
     */
    public function setUrl($url, $raw = true);

    /**
     * @param Client $githubClient
     */
    public function setHttpClient(Client $githubClient);

    /**
     * Get repo bower.json
     *
     * @param  string  $version
     * @param  boolean $includeHomepage
     * @param  string  $url
     * @return string
     */
    public function getBower($version = 'master', $includeHomepage = false, $url = '');

    /**
     * Searches for the first match of a package version.
     *
     * @param  string $version package version
     * @return string
     */
    public function findPackage($version = '*');

    /**
     * Get a release
     *
     * @param  string $type "zip" or "tar"
     * @return string file content
     */
    public function getRelease($type = 'zip');

    /**
     * @return array
     */
    public function getTags();
}
