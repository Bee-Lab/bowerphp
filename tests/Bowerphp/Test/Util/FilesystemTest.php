<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\TestCase;
use Bowerphp\Util\Filesystem;

class FilesystemTest extends TestCase
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
