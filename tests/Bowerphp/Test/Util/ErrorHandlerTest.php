<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\BowerphpTestCase;
use Bowerphp\Util\ErrorHandler;

class ErrorHandlerTest extends BowerphpTestCase
{
    public function testHandle()
    {
        $oldErrorReporting = error_reporting(0);
        $null = ErrorHandler::handle(1, 'foo', 'bar', 42);
        $this->assertNull($null);
        error_reporting($oldErrorReporting);
    }

    /**
     * @expectedException \ErrorException
     */
    public function testHandleXdebug()
    {
        if (!function_exists('xdebug_enable')) {
            $this->markTestSkipped('No xdebug');
        }

        $originalSet = ini_get('xdebug.scream');
        if (!$originalSet) {
            ini_set('xdebug.scream', true);
        }
        ErrorHandler::handle(1, 'foo', 'bar', 42);
        ini_set('xdebug.scream', $originalSet);
    }

    /**
     * @expectedException \ErrorException
     */
    public function testHandleException()
    {
        $oldErrorReporting = error_reporting(1);
        ErrorHandler::handle(1, 'foo', 'bar', 42);
        error_reporting($oldErrorReporting);
    }

    public function testRegister()
    {
        ErrorHandler::register();
        $this->assertTrue(true);    // jsut avoid risky test
    }
}
