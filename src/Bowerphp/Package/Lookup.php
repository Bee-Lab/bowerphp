<?php
namespace Bowerphp\Package;

use Bowerphp\Config\ConfigInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use RuntimeException;

class Lookup
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
            $request = $this->httpClient->get($this->config->getBasePackagesUrl().urlencode($name));
            $response = $request->send();
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot download package %s (%s).', $name, $e->getMessage()));
        }
        $packageInfo = json_decode($response->getBody(true), true);
        $this->validateReturnedPackageData($name, $packageInfo);

        return $packageInfo;
    }

    private function validateReturnedPackageData($name, $packageInfo)
    {
        if (!is_array($packageInfo) || empty($packageInfo['url'])) {
            throw new RuntimeException(sprintf('Package %s has malformed json or is missing "url".', $name));
        }
    }
}
