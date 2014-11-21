<?php
namespace Bowerphp\Package;

use Bowerphp\Config\ConfigInterface;
use Github\HttpClient\HttpClientInterface;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

class Search
{
    private $config;
    private $httpClient;

    /**
     * @param  ConfigInterface     $config
     * @return HttpClientInterface $httpClient
     */
    public function __construct(ConfigInterface $config, HttpClientInterface $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * @param  string $name
     * @return array
     */
    public function package($name)
    {
        try {
            $url = $this->prepareUrl($name);
            $response = $this->httpClient->get($url);

            return json_decode($response->getBody(true), true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot get package list from %s.', str_replace('/packages/', '', $this->config->getBasePackagesUrl())));
        }
    }

    /**
     * @param  string $name
     * @return string
     */
    private function prepareUrl($name)
    {
        $baseUrl = $this->config->getBasePackagesUrl();

        return $baseUrl.'search/'.$name;
    }
}
