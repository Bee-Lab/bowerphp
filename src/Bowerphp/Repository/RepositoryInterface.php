<?php

namespace Bowerphp\Repository;

use Guzzle\Http\ClientInterface;

/**
 * Repository interface.
 *
 */
interface RepositoryInterface
{
    const SEARCH_FULLTEXT = 0;
    const SEARCH_NAME = 1;

    /**
     * @param string $url
     */
    public function setUrl($url);

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient(ClientInterface $httpClient);

    /**
     * Get repo bower.json
     *
     * @return string
     */
    public function getBower();

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
     * @return string file content
     */
    public function getRelease($type = 'zip');

    /**
     * @param array $tag
     */
    public function setTag(array $tag);

    /**
     * @return array
     */
    public function getTag();
}
