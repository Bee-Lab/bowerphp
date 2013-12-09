<?php

namespace Bowerphp\Repository;

use Bowerphp\Package\PackageInterface;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

/**
 * GithubRepository
 *
 */
class GithubRepository implements RepositoryInterface
{
    protected $url, $tag, $httpClient;

    public function __construct($url, ClientInterface $httpClient)
    {
        $this->url = preg_replace('/\.git$/', '', str_replace('git://', 'https://raw.', $url));
        $this->httpClient = $httpClient;
    }

    public function hasBower()
    {
        $depBowerJsonURL = $this->url . '/master/bower.json';
        try {
            $request = $this->httpClient->get($depBowerJsonURL);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot open package git URL %s (%s).', $depBowerJsonURL, $e->getMessage()), 5);
        }

        return true;
    }

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

        // fix version
        if (substr($version, 0, 2) == '>=') {
            $version = substr($version, 2) . '.*';
        }

        foreach ($tags as $tag) {
            if (fnmatch($version, $tag['name'])) {
                $this->tag = $tag;

                return $tag['name'];
            }
        }
    }

    /**
     *
     */
    public function getRelease($type = 'zip')
    {
        $file = $this->tag[$type . 'ball_url'];
        try {
            $request = $this->httpClient->get($file);
            $response = $request->send();

            return $response->getBody();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot open file %s (%).', $file, $e->getMessage()));
        }
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
