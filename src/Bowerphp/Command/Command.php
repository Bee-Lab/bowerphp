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

use Github\Client;
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
    /**
     * Debug HTTP interactions
     * TODO find a way to apply this to Gitub API HttpClient
     *
     * @param ClientInterface $client
     * @param OutputInterface $output
     */
    protected function logHttp(ClientInterface $client, OutputInterface $output)
    {
        // debug http interactions
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $logger = function ($message) use ($output) {
                $finfo = new \finfo(FILEINFO_MIME);
                $msg =  (substr($finfo->buffer($message), 0, 4) == 'text') ? $message : '(binary string)';
                $output->writeln('<info>Guzzle</info> '.$msg);
            };
            $logAdapter = new ClosureLogAdapter($logger);
            $logPlugin = new LogPlugin($logAdapter, MessageFormatter::DEBUG_FORMAT);
            $client->addSubscriber($logPlugin);
        }
    }

    /**
     * Set oauth token (to increase API limit to 5000 per hour, instead of default 60)
     *
     * @param Client $client
     */
    protected function setToken(Client $client)
    {
        // TODO fina a way to read this value from somewhere on local system...
        #$client->authenticate('TODO', null, Client::AUTH_HTTP_TOKEN);
    }
}
