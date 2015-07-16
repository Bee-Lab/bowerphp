<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\TestCase;
use Bowerphp\Util\ZipArchive;

class ZipArchiveTest extends TestCase
{
    public function testGetNumFiles()
    {
        $zipArchive = new ZipArchive();
        $this->assertEquals(0, $zipArchive->getNumFiles());
    }
}
