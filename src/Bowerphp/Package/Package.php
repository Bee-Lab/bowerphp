<?php


namespace Bowerphp\Package;

use Bowerphp\Repository\RepositoryInterface;

/**
 * Package
 *
 */
class Package implements PackageInterface
{
    protected $name, $repository, $version, $targetDir, $requires = array();

    /**
     * All descendants' constructors should call this parent constructor
     *
     * @param string $name The package's name
     */
    public function __construct($name, $version = null)
    {
        $this->name = strtolower($name);
        if (!is_null($version)) {
            $this->version = $version;
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
     * @param string $targetDir
     */
    public function setTargetDir($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetDir()
    {
        if (null === $this->targetDir) {
            return;
        }

        return $this->targetDir;
    }

    /**
     * Set the required packages
     *
     * @param array $requires A set of package links
     */
    public function setRequires(array $requires)
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
