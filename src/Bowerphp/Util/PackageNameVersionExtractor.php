<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Bowerphp\Util;

/**
 * PackageNameVersionExtractor
 *
 * @package Bowerphp\Util
 * @author Piotr Olaszewski <piotroo89 [%] gmail dot com>
 */
class PackageNameVersionExtractor
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $version;

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct($name, $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * @param  string                      $endpoint
     * @return PackageNameVersionExtractor
     */
    public static function fromString($endpoint)
    {
        $map = explode('#', $endpoint);
        $name = isset($map[0]) ? $map[0] : $endpoint;
        $version = isset($map[1]) ? $map[1] : '*';

        return new self($name, $version);
    }
}
