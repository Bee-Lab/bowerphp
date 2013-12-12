<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Config;

/**
 * ConfigInterface
 */
interface ConfigInterface
{
    /**
     * @return string
     */
    public function getBasePackagesUrl();

    /**
     * @return string
     */
    public function getCacheDir();

    /**
     * @return string
     */
    public function getInstallDir();
}
