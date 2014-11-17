<?php
namespace Bowerphp\Package;

use Bowerphp\Config\ConfigInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

class Search
{
    private $config;
    private $httpClient;

    public function __construct(ConfigInterface $config, ClientInterface $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    public function package($name)
    {
        try {
            $url = $this->prepareUrl($name);
            $request = $this->httpClient->get($url);
            $response = $request->send();

            return json_decode($response->getBody(true), true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot get package list from %s.', str_replace('/packages/', '', $this->config->getBasePackagesUrl())));
        }
    }

    private function prepareUrl($name)
    {
        $baseUrl = $this->config->getBasePackagesUrl();

        return $baseUrl.'search/'.$name;
    }
}
