<?php
namespace Bowerphp\Util;

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
