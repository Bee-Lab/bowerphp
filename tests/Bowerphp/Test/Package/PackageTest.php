<?php

namespace Bowerphp\Test;

use Bowerphp\Package\Package;
use Bowerphp\Test\TestCase;
use Mockery;

class PackageTest extends TestCase
{
    public function testSetVersion()
    {
        $package = new Package('foo', '1.0.0');
        $package->setVersion('1.1.0');

        $this->assertEquals('1.1.0', $package->getVersion());
    }

    public function testGetRepository()
    {
        $package = new Package('foo', '1.0.0');

        $this->assertEquals('', $package->getRepository());
    }

    public function testSetRepository()
    {
        $repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $package = new Package('foo', '1.0.0');

        $package->setRepository($repository);
    }

    /**
     * @expectedException LogicException
     */
    public function testCannotChangeRepository()
    {
        $repository = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $repository2 = Mockery::mock('Bowerphp\Repository\RepositoryInterface');
        $package = new Package('foo', '1.0.0');

        $package->setRepository($repository);
        $package->setRepository($repository2);
    }

    public function testGetUniqueName()
    {
        $package = new Package('foo', '1.0.0');

        $this->assertEquals('foo-1.0.0', $package->getUniqueName());
        $this->assertEquals('foo-1.0.0', $package->__toString());
    }

    public function testGetTargetDir()
    {
        $package = new Package('foo', '1.0.0');

        $this->assertNull($package->getTargetDir());

        $package->setTargetDir('bar');
        $this->assertEquals('bar', $package->getTargetDir());
    }

    public function testRequires()
    {
        $package = new Package('foo', '1.0.0');

        $this->assertEquals(array(), $package->getRequires());

        $package->setRequires(array('baz'));
        $this->assertEquals(array('baz'), $package->getRequires());
    }

    public function testClone()
    {
        $package = new Package('foo', '1.0.0');

        $p2 = clone $package;
        $this->assertNull($p2->getRepository());
    }
}
