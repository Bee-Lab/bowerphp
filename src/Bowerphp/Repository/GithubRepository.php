<?php

namespace Bowerphp\Repository;

use Bowerphp\Util\Json;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

/**
 * GithubRepository
 *
 */
class GithubRepository implements RepositoryInterface
{
    protected $url, $originalUrl, $tag = array(), $httpClient;

    /**
     * @param  string           $url
     * @param  boolean          $raw
     * @return GithubRepository
     */
    public function setUrl($url, $raw = true)
    {
        $this->originalUrl  = $url;
        $this->url          = preg_replace('/\.git$/', '', str_replace('git://', 'https://' . ($raw ? 'raw.' : ''), $this->originalUrl));

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
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
     * {@inheritDoc}
     */
    public function getBower($version = 'master', $includeHomepage = false, $url = '')
    {
        if (!empty($url)) {
            // we need to save current $this->url
            $oldUrl = $this->url;
            // then, we call setUrl(), to get the http url
            $this->setUrl($url, true);
        }
        $depBowerJsonURL = $this->url . '/' . $version . '/bower.json';
        try {
            $request = $this->httpClient->get($depBowerJsonURL);
            $response = $request->send();
            // we need this in case of redirect (e.g. 'less/less' becomes 'less/less.js')
            $this->setUrl($response->getEffectiveUrl());
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open package git URL %s (%s).', $depBowerJsonURL, $e->getMessage()), 5);
        }

        $json = $response->getBody(true);
        if ($includeHomepage) {
            $array = json_decode($json, true);
            if (!empty($url)) {
                // here, we set again original $this->url. to pass it in bower.json
                $this->setUrl($oldUrl, true);
            }
            $array['homepage'] = $this->url;
            $json = Json::encode($array);
        }

        return $json;
    }

    /**
     * {@inheritDoc}
     */
    public function findPackage($version = '*')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        try {
            $githubTagsURL = sprintf('https://api.github.com/repos/%s/%s/tags', $repoUser, $repoName);
            $request = $this->httpClient->get($githubTagsURL);
            $response = $request->send();
            $tags = json_decode($response->getBody(true), true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open repo %s/%s (%s).', $repoUser, $repoName, $e->getMessage()));
        }
        $version = $this->fixVersion($version);

        foreach ($tags as $tag) {
            if (fnmatch($version, $tag['name'])) {
                $this->tag = $tag;

                return $tag['name'];
            }
        }

        throw new RuntimeException(sprintf('Version %s not found.', $version), self::VERSION_NOT_FOUND);
    }

    /**
     * {@inheritDoc}
     */
    public function getRelease($type = 'zip')
    {
        $file = $this->tag[$type . 'ball_url'];
        try {
            $request = $this->httpClient->get($file);
            $response = $request->send();

            return $response->getBody();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open file %s (%s).', $file, $e->getMessage()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTags()
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        try {
            $githubTagsURL = sprintf('https://api.github.com/repos/%s/%s/tags', $repoUser, $repoName);
            $request = $this->httpClient->get($githubTagsURL);
            $response = $request->send();
            $tags = json_decode($response->getBody(true), true);

            return array_map(function ($var) {
                return $var['name'];
            }, $tags);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open repo %s/%s (%s).', $repoUser, $repoName, $e->getMessage()));
        }
    }

    /**
     * @param  string $version
     * @return string
     */
    private function fixVersion($version)
    {
        if (is_null($version)) {
            return '*';
        }
        $bits = explode('.', $version);
        if (substr($version, 0, 2) == '>=') {
            if (count($bits) == 3) {
                array_pop($bits);
                $version = implode('.', $bits);
                $version = substr($version, 2) . '.*';
            } else {
                $version = substr($version, 2) . '.*';
            }
        } else {
            if (count($bits) == 1) {
                $version = $version . '.*.*';
            } elseif (count($bits) == 2) {
                $version = $version . '.*';
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
