<?php

namespace Bowerphp\Factory;

use Bowerphp\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandFactory
{
    public static function tester($command, array $parameters = [], array $options = ['decorated' => false])
    {
        $application = new Application();
        $command = $application->get($command);
        $commandTester = new CommandTester($command);
        $input = array_merge($parameters, ['command' => $command->getName()]);
        $commandTester->execute($input, $options);

        return $commandTester;
    }
}
