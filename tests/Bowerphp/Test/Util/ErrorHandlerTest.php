<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Util\ErrorHandler;
use Bowerphp\Test\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function testHandle()
    {
        $oldErrorReporting = error_reporting(0);
        ErrorHandler::handle(1, 'foo', 'bar', 42);
        error_reporting($oldErrorReporting);
    }

    /**
     * @expectedException ErrorException
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
     * @expectedException ErrorException
     */
    public function testHandleException()
    {
        $old_error_reporting = error_reporting(1);
        ErrorHandler::handle(1, 'foo', 'bar', 42);
        error_reporting($old_error_reporting);
    }

    public function testRegister()
    {
        ErrorHandler::register();
    }
}
