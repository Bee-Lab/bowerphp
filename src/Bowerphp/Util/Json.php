<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Util;

use Camspiers\JsonPretty\JsonPretty;

/**
 * Json
 */
class Json
{
    /**
     * For PHP 5.3 from php >= 5.4* use parameter JSON_PRETTY_PRINT.
     * See {@link http://www.php.net/manual/en/function.json-encode.php}.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function encode($value)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        $jsonPretty = new JsonPretty();

        return $jsonPretty->prettify($value, null, '    ');
    }
}
