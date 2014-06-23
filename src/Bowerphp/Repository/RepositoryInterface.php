<?php

namespace Bowerphp\Repository;

use Guzzle\Http\ClientInterface;

/**
 * Repository interface.
 *
 */
interface RepositoryInterface
{
    const VERSION_NOT_FOUND = 3;

    /**
     * @param string  $url
     * @param boolean $raw
     */
    public function setUrl($url, $raw = true);

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient(ClientInterface $httpClient);

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
