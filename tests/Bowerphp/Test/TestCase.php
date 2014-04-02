<?php

namespace Bowerphp\Test;

use Mockery;
use ReflectionClass;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $filesystem, $httpClient;

    public function setUp()
    {
        $this->filesystem = Mockery::mock('Bowerphp\Util\Filesystem');
        $this->httpClient = Mockery::mock('Guzzle\Http\ClientInterface');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param  string           $class
     * @param  string           $name
     * @return ReflectionMethod
     */
    protected function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
