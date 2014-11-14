<?php
/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Bowerphp\Command;

use Bowerphp\Bowerphp;
use Bowerphp\Config\Config;
use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Repository\RepositoryInterface;
use Bowerphp\Util\Filesystem;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\ClientInterface;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inspired by Composer https://github.com/composer/composer
 */
class UpdateCommand extends Command
{
    private $config;
    private $filesystem;
    private $httpClient;
    private $repository;
    private $installer;

    public function __construct(Config $config, Filesystem $filesystem, ClientInterface $httpClient, RepositoryInterface $repository, InstallerInterface $installer)
    {
        parent::__construct();
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->installer = $installer;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update the project dependencies from the bower.json file or a single specified package')
            ->addArgument('package', InputArgument::OPTIONAL, 'Choose a package.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

  <info>php %command.full_name%</info>

If an optional package name is passed, only that package is updated.

  <info>php %command.full_name% packageName</info>

EOT
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');

        $this->logHttp($this->httpClient, $output);

        // http cache
        $cachePlugin = new CachePlugin(array(
            'storage' => new DefaultCacheStorage(new DoctrineCacheAdapter(new FilesystemCache($this->config->getCacheDir())), 'bowerphp', 86400)
        ));
        $this->httpClient->addSubscriber($cachePlugin);

        try {
            $bowerphpConsoleOutput = new BowerphpConsoleOutput($output);
            $bowerphp = new Bowerphp($this->config, $this->filesystem, $this->httpClient, $this->repository, $bowerphpConsoleOutput, $this->installer);
            if (is_null($packageName)) {
                $bowerphp->updatePackages();
            } else {
                $bowerphp->updatePackage(new Package($packageName));
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage());
        }
        $output->writeln('');
    }
}
