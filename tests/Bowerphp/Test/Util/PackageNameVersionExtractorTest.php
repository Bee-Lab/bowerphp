<?php

namespace Bowerphp\Test\Util;

use Bowerphp\Test\BowerphpTestCase;
use Bowerphp\Util\PackageNameVersionExtractor;

class PackageNameVersionExtractorTest extends BowerphpTestCase
{
    public function testReturnPackageNameWhenNoVersionSet()
    {
        //given
        $package = 'jquery';

        //when
        $packageNameVersion = PackageNameVersionExtractor::fromString($package);

        //then
        $this->assertEquals('jquery', $packageNameVersion->name);
        $this->assertEquals('*', $packageNameVersion->version);
    }

    public function testReturnPackageAndVersionIsSet()
    {
        //given
        $package = 'jquery#1.10.2';

        //when
        $packageNameVersion = PackageNameVersionExtractor::fromString($package);

        //then
        $this->assertEquals('jquery', $packageNameVersion->name);
        $this->assertEquals('1.10.2', $packageNameVersion->version);
    }

    public function testReturnPackageGithubUrlFromShorthand()
    {
        $package = 'jquery/jquery';

        $packageNameVersion = PackageNameVersionExtractor::fromString($package);

        $this->assertEquals('https://github.com/jquery/jquery.git', $packageNameVersion->name);
        $this->assertEquals('*', $packageNameVersion->version);
    }

    public function testReturnPackageGithubUrlAndVersionFromShorthand()
    {
        $package = 'jquery/jquery#1.10.2';

        $packageNameVersion = PackageNameVersionExtractor::fromString($package);

        $this->assertEquals('https://github.com/jquery/jquery.git', $packageNameVersion->name);
        $this->assertEquals('1.10.2', $packageNameVersion->version);
    }
}
