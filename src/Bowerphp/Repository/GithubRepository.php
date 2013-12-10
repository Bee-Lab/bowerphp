<?php

namespace Bowerphp\Repository;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

/**
 * GithubRepository
 *
 */
class GithubRepository implements RepositoryInterface
{
    protected $url, $tag = array(), $httpClient;

    /**
     * @param  string           $url
     * @return GithubRepository
     */
    public function setUrl($url)
    {
        $this->url = preg_replace('/\.git$/', '', str_replace('git://', 'https://raw.', $url));

        return $this;
    }

    /**
     * @param  ClientInterface  $httpClient
     * @return GithubRepository
     */
    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @return string
     */
    public function getBower()
    {
        $depBowerJsonURL = $this->url . '/master/bower.json';
        try {
            $request = $this->httpClient->get($depBowerJsonURL);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot open package git URL %s (%s).', $depBowerJsonURL, $e->getMessage()), 5);
        }

        return $response->getBody(true);
    }

    /**
     * @param  string $version
     * @return string
     */
    public function findPackage($version = '*')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        try {
            $githubTagsURL = sprintf('https://api.github.com/repos/%s/%s/tags', $repoUser, $repoName);
            $request = $this->httpClient->get($githubTagsURL);
            $response = $request->send();
            $tags = json_decode($response->getBody(true), true);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot open repo %s/%s (%s).', $repoUser, $repoName, $e->getMessage()));
        }

        $version = $this->fixVersion($version);

        foreach ($tags as $tag) {
            if (fnmatch($version, $tag['name'])) {
                $this->setTag($tag);

                return $tag['name'];
            }
        }
    }

    /**
     * @param  string $type
     * @return string
     */
    public function getRelease($type = 'zip')
    {
        $tag = $this->getTag();
        $file = $tag[$type . 'ball_url'];
        try {
            $request = $this->httpClient->get($file);
            $response = $request->send();

            return $response->getBody();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot open file %s (%).', $file, $e->getMessage()));
        }
    }

    /**
     * @return array
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param array $tag
     */
    public function setTag(array $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @param  string $version
     * @return string
     */
    private function fixVersion($version)
    {
        if (substr($version, 0, 2) == '>=') {
            $bits = explode('.', $version);
            if (count($bits) == 3) {
                array_pop($bits);
                $version = implode('.', $bits);
                $version = substr($version, 2) . '.*';
            } else {
                $version = substr($version, 2) . '.*';
            }
        }

        return trim($version);
    }

    /**
     * @param  string
     * @return string
     */
    private function clearGitURL($url)
    {
        if (substr($url, 0, 6) == 'git://') {
            $url = substr($url, 6);
        }
        if (substr($url, 0, 8) == 'https://') {
            $url = substr($url, 8);
        }
        if (substr($url, 0, 11) == 'github.com/') {
            $url = substr($url, 11);
        } elseif (substr($url, 0, 15) == 'raw.github.com/') {
            $url = substr($url, 15);
        }
        if (substr($url, -4) == '.git') {
            $url = substr($url, 0, -4);
        }

        return $url;
    }

}
