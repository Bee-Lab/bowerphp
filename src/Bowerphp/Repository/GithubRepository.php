<?php

namespace Bowerphp\Repository;

use Bowerphp\Util\Json;
use Github\Client;
use Github\ResultPager;
use RuntimeException;
use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

/**
 * GithubRepository
 *
 */
class GithubRepository implements RepositoryInterface
{
    protected $url;
    protected $tag = array();
    protected $githubClient;

    /**
     * {@inheritdoc}
     *
     * @return GithubRepository
     */
    public function setUrl($url, $raw = true)
    {
        $url = preg_replace('/\.git$/', '', str_replace('git://', 'https://' . ($raw ? 'raw.' : ''), $url));
        $this->url = str_replace('raw.github.com', 'raw.githubusercontent.com', $url);

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
     * @param  Client           $githubClient
     * @return GithubRepository
     */
    public function setHttpClient(Client $githubClient)
    {
        $this->githubClient = $githubClient;

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function findPackage($rawCriteria = '*')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $paginator = new ResultPager($this->githubClient);
        $tags = $paginator->fetchAll($this->githubClient->api('repo'), 'tags', array($repoUser, $repoName));

        // edge case: package has no tags
        if (count($tags) === 0) {
            return 'master';
        }

        // edge case: user asked for latest package
        if ($rawCriteria == 'latest' || $rawCriteria == '*' || empty($rawCriteria)) {
            $this->tag = $tags[0];

            return $this->tag['name'];
        }

        try {
            $criteria = new expression($rawCriteria);
        } catch (SemVerException $sve) {
            throw new RuntimeException(sprintf('Criteria %s is not valid.', $rawCriteria), self::INVALID_CRITERIA);
        }
        $sortedTags = $this->sortTags($tags);

        // Yes, the php-semver lib does offer a maxSatisfying() method similar the code below.
        // We're not using it because it will throw an exception on what it considers to be an
        // "invalid" candidate version, and not continue checking the rest of the candidates.
        // So, even if it's faster than this code, it's not a complete solution..
        $matches = array_filter(
            $sortedTags, function ($tag) use ($repoName, $criteria) {
            try {
                $candidate = $tag['parsed_version'];

                return $criteria->satisfiedBy($candidate) ? $tag : false;
            } catch (\Exception $rex) {
                // @todo Find a better way to do this - we shouldn't throw because of one bad version tag,
                // but on the other hand we should probably allow the user to know (maybe via -vvv?)
                // Console output is not available at this level, so this is the least bad way to go.
                error_log(sprintf('%s: Candidate version %s is not valid, skipping', $repoName, $tag['name']));
            }
        });

        // If the array has elements, the LAST element is the best (highest numbered) version.
        if (count($matches)) {
            // @todo Get rid of this side effect?
            $this->tag = array_pop($matches);

            return $this->tag['name'];
        }

        throw new RuntimeException(sprintf('%s: No suitable version for %s was found.', $repoName, $rawCriteria), self::VERSION_NOT_FOUND);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelease($type = 'zip')
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));

        return $this->githubClient->api('repo')->contents()->archive($repoUser, $repoName, $type . 'ball', $this->tag['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $paginator = new ResultPager($this->githubClient);
        $tags = $paginator->fetchAll($this->githubClient->api('repo'), 'tags', array($repoUser, $repoName));
        // edge case: no tags
        if (count($tags) === 0) {
            return array();
        }

        $sortedTags = $this->sortTags($tags);  // Filters out bad tag specs

        return array_keys($sortedTags);
    }

    /**
     * Get remote bower.json file (or package.json file)
     *
     * @param  string $version
     * @return string
     */
    private function getDepBowerJson($version)
    {
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($this->url));
        $contents = $this->githubClient->api('repo')->contents();
        if ($contents->exists($repoUser, $repoName, 'bower.json', $version)) {
            $json = $contents->download($repoUser, $repoName, 'bower.json', $version);
        } else {
            $isPackageJson = true;
            if ($contents->exists($repoUser, $repoName, 'package.json', $version)) {
                $json = $contents->download($repoUser, $repoName, 'package.json', $version);
            } elseif ($version != 'master') {
                return $this->getDepBowerJson('master');
            }
            // try anyway. E.g. exists() return false for Modernizr, but then it downloads :-|
            $json = $contents->download($repoUser, $repoName, 'package.json', $version);
        }

        if (substr($json, 0, 3) == "\xef\xbb\xbf") {
            $json = substr($json, 3);
        }

        // for package.json, remove dependencies (see the case of Modernizr)
        if (isset($isPackageJson)) {
            $array = json_decode($json, true);
            if (isset($array['dependencies'])) {
                unset($array['dependencies']);
            }
            $json = Json::encode($array);
        }

        return $json;
    }

    /**
     * @param  string
     * @return string
     */
    private function clearGitURL($url)
    {
        $url = str_replace('git@github.com:', 'github.com/', $url);
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

    // Why do we have to do this? Your guess is as good as mine.
    // The only flaw I've seen in the semver lib we're using,
    // and the regex's in there are too complicated to mess with.
    private function fixupRawTag($rawValue)
    {
        // WHY NOT SCRUB OUT PLUS SIGNS, RIGHT?
        $found_it = strpos($rawValue, '+');
        if ($found_it !== false) {
            $rawValue = substr($rawValue, 0, $found_it);
        }
        $pieces = explode('.', $rawValue);
        $count = count($pieces);
        if ($count == 0) {
            $pieces[] = '0';
            $count = 1;
        }
        for ($add = $count; $add < 3; $add++) {
            $pieces[] = '0';
        }
        $return = implode('.', array_slice($pieces, 0, 3)
        );

        return $return;
    }

    /**
     * @param  array $tags
     * @return array
     */
    private function sortTags($tags)
    {
        $return = array();

        // Don't include invalid tags
        foreach ($tags as $tag) {
            try {
                $fixedName = $this->fixupRawTag($tag['name']);
                $v = new version($fixedName, true);
                if ($v->valid()) {
                    $tag['parsed_version'] = $v;
                    $return[$v->getVersion()] = $tag;
                }
            } catch (\Exception $ex) {
                // Skip
            }
        }

        uasort($return, function ($a, $b) {
            return version::compare($a['parsed_version'], $b['parsed_version']);
        });

        return $return;
    }
}
