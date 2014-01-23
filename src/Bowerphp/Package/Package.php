<?php

namespace Bowerphp\Package;

use Bowerphp\Repository\RepositoryInterface;

/**
 * Package
 *
 */
class Package implements PackageInterface
{
    protected $name, $repository, $requiredVersion, $version, $requires = array(), $info = array();

    /**
     * All descendants' constructors should call this parent constructor
     *
     * @param string $name            The package's name
     * @param string $requiredVersion E.g. 1.*
     * @param string $version         E.g. 1.2.3
     * @param array  $requires        The package's dependencies
     * @param array  $info            Package info (e.g. info from bower.json)
     */
    public function __construct($name, $requiredVersion = null, $version = null, $requires = array(), $info = array())
    {
        $this->name = $name;
        $this->requiredVersion = $requiredVersion;
        $this->version = $version;
        if (!empty($requires)) {
            $this->requires = $requires;
        }
        if (!empty($info)) {
            $this->info = $info;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritDoc}
     */
    public function setVersion($version)
    {
        return $this->version = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredVersion()
    {
        return $this->requiredVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequiredVersion($version)
    {
        return $this->requiredVersion = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function setRepository(RepositoryInterface $repository)
    {
        if ($this->repository && $repository !== $this->repository) {
            throw new \LogicException('A package can only be added to one repository');
        }
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns package unique name, constructed from name, version and release type.
     *
     * @return string
     */
    public function getUniqueName()
    {
        return $this->getName().'-'.$this->getVersion();
    }

    /**
     * Set the required packages
     *
     * @param array $requires A set of package links
     */
    public function setRequires(array $requires = null)
    {
        $this->requires = $requires;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequires()
    {
        return $this->requires;
    }

    /**
     * Set the info
     *
     * @param array $info
     */
    public function setInfo(array $info)
    {
        $this->info = $info;
    }

    /**
     * {@inheritDoc}
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Converts the package into a readable and unique string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUniqueName();
    }

    public function __clone()
    {
        $this->repository = null;
    }
}
