<?php
/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * The Compiler class compiles Bower into a phar
 *
 */
class Compiler
{
    private $version;
    private $versionDate;
    private $gz;

    /**
     * @param boolean $gz
     */
    public function __construct($gz = false)
    {
        $this->gz = $gz;
    }

    /**
     * Compiles composer into a single phar file
     *
     * @throws \RuntimeException
     * @param  string            $pharFile The full path to the file to create
     */
    public function compile($pharFile = 'bowerphp.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $this->checkGitAvailability();
        $this->checkGitRepo();

        $this->version = $this->getGitTagOrHash();

        $date = $this->getGitDate();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->versionDate = $date->format('Y-m-d H:i:s');

        $phar = new \Phar($pharFile, 0, 'bowerphp.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->in(__DIR__ . '/..')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('*.pem')
            ->name('*.pem.md5')
            ->exclude('Tests')
            ->in(__DIR__ . '/../../vendor/symfony/')
            ->in(__DIR__ . '/../../vendor/guzzle/guzzle/src/')
            ->in(__DIR__ . '/../../vendor/camspiers/json-pretty/src/')
            ->in(__DIR__ . '/../../vendor/knplabs/github-api/lib/')
            ->in(__DIR__ . '/../../vendor/vierbergenlars/php-semver/src/vierbergenlars/LibJs/')
            ->in(__DIR__ . '/../../vendor/vierbergenlars/php-semver/src/vierbergenlars/SemVer/')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_real.php'));
        if (file_exists(__DIR__ . '/../../vendor/composer/include_paths.php')) {
            $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/include_paths.php'));
        }
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/ClassLoader.php'));
        $this->addComposerBin($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        if ($this->gz) {
            $phar->compressFiles(\Phar::GZ);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../LICENSE'), false);

        unset($phar);
        chmod("bowerphp.phar", 0700);
    }

    /**
     * Make sure that git is installed and accessible.
     *
     * @throws \RuntimeException
     */
    private function checkGitAvailability()
    {
        $process = new Process('git log', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException('Can\'t run git log. You must ensure that git binary is available.');
        }
    }

    /**
     * Make sure that the working directory is a git repo.
     *
     * @throws \RuntimeException
     */
    private function checkGitRepo()
    {
        $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone.');
        }
    }

    /**
     * Return version information.
     * Either the closest tag or if no tag is reachable, the hash of the commit.
     *
     * @return string
     */
    private function getGitTagOrHash()
    {
        //
        $process = new Process('git describe --tags HEAD');
        if ($process->run() == 0) {
            return trim($process->getOutput());
        } else {
            $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);

            return trim($process->getOutput());
        }
    }

    /**
     * Return the date of the last commit.
     *
     * @return \DateTime
     */
    private function getGitDate()
    {
        $process = new Process('git log -n1 --pretty=%ci HEAD', __DIR__);

        return new \DateTime(trim($process->getOutput()));
    }

    /**
     * @param Phar        $phar
     * @param SplFileInfo $file
     * @param boolean     $strip
     */
    private function addFile(\Phar $phar, \SplFileInfo $file, $strip = true)
    {
        $path = strtr(str_replace(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '', $file->getRealPath()), '\\', '/');

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n" . $content . "\n";
        }

        if ($path === 'src/Bowerphp/Bowerphp.php') {
            $content = str_replace('@package_version@', $this->version, $content);
            $content = str_replace('@release_date@', $this->versionDate, $content);
        }

        $phar->addFromString($path, $content);
    }

    /**
     * @param Phar $phar
     */
    private function addComposerBin(\Phar $phar)
    {
        $content = file_get_contents(__DIR__ . '/../../bin/bowerphp');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/bowerphp', $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    private function getStub()
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *     Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

Phar::mapPhar('bowerphp.phar');

EOF;

        // add warning once the phar is older than 30 days
        if (preg_match('{^[a-f0-9]+$}', $this->version)) {
            $warningTime = time() + 30*86400;
            $stub .= "define('BOWERPHP_DEV_WARNING_TIME', $warningTime);\n";
        }

        return $stub . <<<'EOF'
require 'phar://bowerphp.phar/bin/bowerphp';

__HALT_COMPILER();
EOF;
    }
}
