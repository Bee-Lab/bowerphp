<?php

namespace Bowerphp\Test;

use Mockery;
use ReflectionClass;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param \Mockery\MockInterface
     */
    protected $filesystem;

    /**
     * XXX this is indeed a GithubClient, it should be renamed here and in all tests
     *
     * @param \Mockery\MockInterface
     */
    protected $httpClient;

    protected function setUp()
    {
        $this->filesystem = Mockery::mock('Bowerphp\Util\Filesystem');
        $this->httpClient = Mockery::mock('Github\Client');
    }

    protected function tearDown()
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
