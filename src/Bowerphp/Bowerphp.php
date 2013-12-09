<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp;

use Gaufrette\Filesystem;
use Github\Client as GithubClient;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use Camspiers\JsonPretty\JsonPretty;
 
/**
 * Main class
 */
class Bowerphp
{
    protected
        $installed = array(),
        $filesystem,
        $httpClient,
        $githubClient
    ;

    /**
     * @param Filesystem      $filesystem
     * @param ClientInterface $httpClient
     */
    public function __construct(Filesystem $filesystem, ClientInterface $httpClient)
    {
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
    }

    /**
     * @param GithubClient $githubClient
     */
    public function setGithubClient(GithubClient $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Init bower.json
     *
     */
    public function init(array $params)
    {
        $file = 'bower.json';
        $json = $this->json_readable_encode($this->createAClearBowerFile($params));

        $this->filesystem->write($file, $json);
    }

    /**
     * Install a single package
     *
     * @param  string $package
     * @param  string $version
     * @return array
     */
    public function installPackage($package, $version = '*')
    {
        $v = explode("#", $package);
        $package = isset($v[0]) ? $v[0] : $package;
        $version = isset($v[1]) ? $v[1] : "*";

        $this->executeInstallFromBower($package, $version);

        return $this->installed;
    }

    /**
     * Install all dependencies
     *
     * @return array
     */
    public function installDependencies()
    {
        $json = $this->filesystem->read('bower.json');

        $this->install($json);

        return $this->installed;
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
    {
        $structure =  array(
            'name' => $params['name'],
            'authors' => array (
                0 => 'Beelab <info@bee-lab.net>',
                1 => $params['author']
            ),
            'private' => true,
            'dependencies' => array(),
        );

        return $structure;
    }

    /**
     * @param string $bowerJson
     */
    protected function install($bowerJson)
    {
        if (empty($bowerJson) || !is_array($decode = json_decode($bowerJson, true))) {
            throw new \RuntimeException(sprintf('Malformed JSON %s.', $bowerJson), 5);
        }

        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $package => $version) {
                $this->executeInstallFromBower($package, $version);
            }
        }
    }

    /**
     * @param string $package
     * @param string $version
     */
    protected function executeInstallFromBower($package, $version)
    {
        $url = 'http://bower.herokuapp.com/packages/' . $package;
        try {
            $request = $this->httpClient->get($url);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot download package %s (%s).', $package, $e->getMessage()), 3);
        }

        $decode = json_decode($response->getBody(true), true);
        if (!is_array($decode) || empty($decode['url'])) {
            throw new \RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $package), 4);
        }

        // TODO ...
        $git = str_replace('git://', 'https://raw.', $decode['url']);
        $git = preg_replace('/\.git$/', '', $git);

        $depBowerJsonURL = $git . '/master/bower.json';

        try {
            $request = $this->httpClient->get($depBowerJsonURL);
            $response = $request->send();
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Cannot open package git URL %s (%s).', $depBowerJsonURL, $e->getMessage()), 5);
        }

        $depBowerJson = $response->getBody(true);

        // e.g. "git://github.com/components/jquery.git" -> "components", "jquery"
        list($repoUser, $repoName) = explode('/', $this->clearGitURL($decode['url']));
        try {
            $tags = $this->githubClient->api('repo')->tags($repoUser, $repoName);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot open repo <error>%s/%s</error> (%s).', $repoUser, $repoName, $e->getMessage()), 7);
        }

        // fix version
        if (substr($version, 0, 2) == '>=') {
            $version = substr($version, 2) . '.*';
        }

        foreach ($tags as $tag) {
            if (fnmatch($version, $tag['name'])) {
                if ($this->getReleaseFromGit($tag['tarball_url'])) {
                    $this->installed[$package] = $version;
                }
                $this->install($depBowerJson);

                return;
            }
        }

        throw new \RuntimeException(sprintf('Cannot find package %s version %s.', $package, $version), 6);
    }

    /**
     * @param  string  $tarballUrl
     * @return boolean
     */
    protected function getReleaseFromGit($tarballUrl)
    {
        /*
        $request = $this->httpClient->get($tarballUrl);
        $response = $request->send();
        $file = $response->getBody();

        $tmpFileName = './tmp/' . basename($tarballUrl) . '.tgz';
        $this->filesystem->write($tmpFileName, $file);
        $archive = new \PharData($tmpFileName);
        // TODO checksum mismatch error
        $archive->extractTo('./tmp/');
        */

        return true;
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
        if (substr($url, 0, 11) == 'github.com/') {
            $url = substr($url, 11);
        }
        if (substr($url, -4) == '.git') {
            $url = substr($url, 0, -4);
        }

        return $url;
    }

    /**
     * FOR php 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT
     * See http://www.php.net/manual/en/function.json-encode.php
     *
     * @param  array   $in
     * @param  integer $indent
     * @param  string  $_escape
     * @return string
     */

    private function json_readable_encode(array $in, $indent = 0, $_escape = null)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($in, JSON_PRETTY_PRINT);
        }

        $jsonPretty = new JsonPretty();

        return $jsonPretty->prettify($in,null, '    ');
    }


}
