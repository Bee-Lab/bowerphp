<?php

namespace Bowerphp\Repository;

use Bowerphp\Util\Json;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

/**
 * GithubRepository
 *
 */
class GithubRepository implements RepositoryInterface
{
    protected $url;
    protected $originalUrl;
    protected $tag = array();
    protected $httpClient;

    /**
     * {@inheritDoc}
     *
     * @return GithubRepository
     */
    public function setUrl($url, $raw = true)
    {
        $this->originalUrl = $url;
        $this->url         = preg_replace('/\.git$/', '', str_replace('git://', 'https://' . ($raw ? 'raw.' : ''), $this->originalUrl));
        $this->url         = str_replace('raw.github.com', 'raw.githubusercontent.com', $this->url);

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
        if ($version == '*') {
            $version = 'master';
        }
        if (!empty($url)) {
            // we need to save current $this->url
            $oldUrl = $this->url;
            // then, we call setUrl(), to get the http url
            $this->setUrl($url, true);
        }
        $json = $this->getDepBowerJson($version);

        if ($includeHomepage) {
            $array = json_decode($json, true);
            if (!empty($url)) {
                // here, we set again original $this->url, to pass it in bower.json
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
            $githubTagsURL = sprintf('https://api.github.com/repos/%s/%s/tags?per_page=100', $repoUser, $repoName);
            $request = $this->httpClient->get($githubTagsURL);
            $response = $request->send();
            $tags = json_decode($response->getBody(true), true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open repo %s/%s (%s).', $repoUser, $repoName, $e->getMessage()));
        }
        $version = $this->fixVersion($version);

        // edge case: package has no tags
        if (count($tags) === 0) {
            $zipballUrl = sprintf('https://api.github.com/repos/%s/%s/zipball/master', $repoUser, $repoName);
            $this->tag = array('zipball_url' => $zipballUrl);

            return 'master';
        }

        foreach ($this->sortTags($tags) as $tag) {
            if (fnmatch($version, $tag['name']) || fnmatch('v' . $version, $tag['name'])) {
                $this->tag = $tag;

                return $tag['name'];
            }
        }

        if ($version == 'latest.*.*') {
            $this->tag = $tags[0];

            return $tags[0]['name'];
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
            $githubTagsURL = sprintf('https://api.github.com/repos/%s/%s/tags?per_page=100', $repoUser, $repoName);
            $request = $this->httpClient->get($githubTagsURL);
            $response = $request->send();
            $tags = json_decode($response->getBody(true), true);
            // edge case: no tags
            if (count($tags) === 0) {
                return array();
            }

            return array_map(function ($var) {
                return $var['name'];
            }, $tags);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open repo %s/%s (%s).', $repoUser, $repoName, $e->getMessage()));
        }
    }

    /**
     * Get remote bower.json file (or package.json file)
     *
     * @param  string $version
     * @return string
     */
    private function getDepBowerJson($version)
    {
        $depBowerJsonURL = $this->url . '/' . $version . '/bower.json';
        try {
            $request = $this->httpClient->get($depBowerJsonURL);
            $response = $request->send();
            // we need this in case of redirect (e.g. 'less/less' becomes 'less/less.js')
            $this->setUrl($response->getEffectiveUrl());
        } catch (BadResponseException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                // fallback on package.json
                $depPackageJsonURL = $this->url . '/' . $version . '/package.json';
                try {
                    $request = $this->httpClient->get($depPackageJsonURL);
                    $response = $request->send();
                    $this->setUrl($response->getEffectiveUrl());
                } catch (BadResponseException $e) {
                    if ($version != 'master' && $e->getResponse()->getStatusCode() == 404) {
                        // fallback on master
                        return $this->getDepBowerJson('master');
                    } else {
                        throw $e;
                    }
                } catch (RequestException $e) {
                    throw new RuntimeException(sprintf('Cannot open package git URL %s nor %s (%s).', $depBowerJsonURL, $depPackageJsonURL, $e->getMessage()));
                }
            } else {
                throw $e;
            }
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot open package git URL %s (%s).', $depBowerJsonURL, $e->getMessage()));
        }
        $json = $response->getBody(true);

        // remove BOM if exists
        if (substr($json, 0, 3) == "\xef\xbb\xbf") {
            $json = substr($json, 3);
        }

        // for package.json, remove dependencies (see the case of Modernizr)
        if (isset($depPackageJsonURL)) {
            $array = json_decode($json, true);
            if (isset($array['dependencies'])) {
                unset($array['dependencies']);
            }
            $json = Json::encode($array);
        }

        return $json;
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
        if (substr($version, 0, 1) == '~') {
            $version = substr($version, 1) . '*';
        } elseif (substr($version, 0, 2) == '>=') {
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
        } elseif (substr($url, 0, 26) == 'raw.githubusercontent.com/') {
            $url = substr($url, 26);
        }
        if (substr($url, -4) == '.git') {
            $url = substr($url, 0, -4);
        }

        return $url;
    }

    /**
     * @param  array $tags
     * @return array
     */
    private function sortTags($tags)
    {
        foreach ($tags as &$tag) {
            if (preg_match('/^([\d\.]*)(.*)$/', $tag['name'], $matches)) {
                $number = implode(
                    array_map(
                        function ($digit) {return str_pad($digit, 6, '0', STR_PAD_LEFT); },
                        explode('.', trim($matches[1], '.'))
                    )
                );
                $preRelease = $matches[2] ? : 'zzzzzz';

                $tag['normal_version'] = $number . $preRelease;
            } else {
                $tag['normal_version'] = 'zzzzzz';
            }
        }

        usort($tags, function ($tag1, $tag2) { return strcmp($tag2['normal_version'], $tag1['normal_version']); });

        return $tags;
    }
}
