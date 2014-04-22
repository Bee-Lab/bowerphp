<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Util\ZipArchive;
use Bowerphp\Test\TestCase;

class ZipArchiveTest extends TestCase
{
    public function testGetNumFiles()
    {
        $zipArchive = new ZipArchive();
        $this->assertEquals(0, $zipArchive->getNumFiles());
    }
}
