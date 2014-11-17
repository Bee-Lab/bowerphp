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

use Bowerphp\Config\Config;
use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Repository\RepositoryInterface;
use Bowerphp\Util\Filesystem;
use finfo;
use Guzzle\Http\ClientInterface;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\Log\LogPlugin;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for Bowerphp commands
 * Inspired by Composer https://github.com/composer/composer
 */
abstract class Command extends BaseCommand
{
    protected $config;
    protected $filesystem;
    protected $httpClient;
    protected $repository;
    protected $installer;

    public function __construct(Config $config = null, Filesystem $filesystem = null, ClientInterface $httpClient = null, RepositoryInterface $repository = null, InstallerInterface $installer = null)
    {
        parent::__construct();
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->repository = $repository;
        $this->installer = $installer;
    }

    /**
     * Debug HTTP interactions
     *
     * @param ClientInterface $client
     * @param OutputInterface $output
     */
    protected function logHttp(ClientInterface $client, OutputInterface $output)
    {
        // debug http interactions
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $logger = function ($message) use ($output) {
                $finfo = new finfo(FILEINFO_MIME);
                $msg = (substr($finfo->buffer($message), 0, 4) == 'text') ? $message : '(binary string)';
                $output->writeln('<info>Guzzle</info> '.$msg);
            };
            $logAdapter = new ClosureLogAdapter($logger);
            $logPlugin = new LogPlugin($logAdapter, MessageFormatter::DEBUG_FORMAT);
            $client->addSubscriber($logPlugin);
        }
    }
}
