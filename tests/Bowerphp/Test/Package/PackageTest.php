<?php

namespace Bowerphp\Test\Package;

use Bowerphp\Package\Package;
use Bowerphp\Test\TestCase;
use Mockery;

class PackageTest extends TestCase
{
    public function testSetVersion()
    {
        $package = new Package('foo', null, '1.0.0');
        $package->setVersion('1.1.0');

        $this->assertEquals('1.1.0', $package->getVersion());
    }

    public function testSetRepository()
    {
        $repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $package = new Package('foo', null, '1.0.0');

        $package->setRepository($repository);
    }

    /**
     * @expectedException LogicException
     */
    public function testCannotChangeRepository()
    {
        $repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $repository2 = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $package = new Package('foo', null, '1.0.0');

        $package->setRepository($repository);
        $package->setRepository($repository2);
    }

    public function testGetUniqueName()
    {
        $package = new Package('foo', null, '1.0.0');

        $this->assertEquals('foo-1.0.0', $package->getUniqueName());
        $this->assertEquals('foo-1.0.0', $package->__toString());
    }

    public function testRequires()
    {
        $package = new Package('foo', null, '1.0.0');

        $this->assertEquals(array(), $package->getRequires());

        $package->setRequires(array('baz'));
        $this->assertEquals(array('baz'), $package->getRequires());
    }

    public function testGetInfo()
    {
        $package = new Package('foo', null, '1.0.0', null, array('url' => 'bar'));

        $this->assertEquals(array('url' => 'bar'), $package->getInfo());

        $package->setInfo(array('url' => 'baz'));
        $this->assertEquals(array('url' => 'baz'), $package->getInfo());
    }
}
