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

/**
 * ArrayColumn.
 */
class ArrayColumn
{
    /**
     * Wrapper for PHP < 5.5.
     * See {@link http://php.net/manual/en/function.array-column.php}.
     *
     * @param array $array
     * @param mixed $column_name
     * @param mixed $index
     *
     * @return array
     */
    public static function array_column(array $array, $column_name, $index = null)
    {
        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return array_column($array, $column_name, $index);
        }

        return array_map(function ($element) use ($column_name) {
            return $element[$column_name];
        }, $array);
    }
}
