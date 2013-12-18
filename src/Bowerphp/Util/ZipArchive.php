<?php

namespace Bowerphp\Util;

use ZipArchive as PhpZipArchive;

class ZipArchive extends PhpZipArchive
{
    /**
     * @return integer
     */
    public function getNumFiles()
    {
        return $this->numFiles;
    }
}
