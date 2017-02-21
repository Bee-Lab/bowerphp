<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\BowerphpTestCase;
use Bowerphp\Util\Filesystem;

class FilesystemTest extends BowerphpTestCase
{
    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function testRead()
    {
        $filesystem = new Filesystem();
        $filesystem->read('/tmp/a_non_existing_file_i_hope');
    }
}
