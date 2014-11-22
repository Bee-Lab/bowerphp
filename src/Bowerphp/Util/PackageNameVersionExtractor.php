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
 * Class PackageNameVersionExtractor
 * @package Bowerphp\Util
 * @author Piotr Olaszewski <piotroo89 [%] gmail dot com>
 */
class PackageNameVersionExtractor
{
    public $name;
    public $version;

    public function __construct($name, $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    public static function fromString($package)
    {
        $map = explode('#', $package);
        $name = isset($map[0]) ? $map[0] : $package;
        $version = isset($map[1]) ? $map[1] : '*';

        return new self($name, $version);
    }
}
