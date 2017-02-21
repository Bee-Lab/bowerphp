<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\BowerphpTestCase;
use Bowerphp\Util\ZipArchive;

class ZipArchiveTest extends BowerphpTestCase
{
    public function testGetNumFiles()
    {
        $zipArchive = new ZipArchive();
        $this->assertEquals(0, $zipArchive->getNumFiles());
    }
}
