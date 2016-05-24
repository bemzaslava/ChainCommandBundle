<?php

namespace ChainCommandBundle\Chain;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Class Chain
 * @package ChainCommandBundle\Command
 */
class Chain
{
    /**
     * @var array
     */
    protected $chains;

    /**
     * @var array
     */
    protected $masterCommands;

    /**
     * Chain constructor.
     */
    public function __construct()
    {
        $this->chains = [];
        $this->masterCommands = [];
    }

    /**
     * Register new commands for chain. Called by CompilerPass.
     *
     * @see ChainCommandBundle\DependencyInjection\Compiler\ChainCommandCompilerPass
     *
     * @param Command $command
     * @param bool    $master
     * @param string  $channel
     * @param integer $weight
     */
    public function registerCommand(Command $command, $master, $channel, $weight = 0)
    {
        if ($master) {
            if (isset($this->masterCommands[$channel])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Can\'t define %s as master for the chain. %s already defined as master command for the chain with channel %s.',
                        get_class($command),
                        get_class($this->masterCommands[$channel]),
                        $channel
                    )
                );
            }
            $this->masterCommands[$channel] = $command;
        } else {
            $this->chains[$channel][$weight][] = $command;
        }
    }

    /**
     * Returns array of commands sort by weights DESC.
     *
     * @param string $channel
     *
     * @return array
     */
    public function getMembers($channel)
    {
        $commands = [];

        if (isset($this->chains[$channel])) {
            $chain = $this->chains[$channel];
            ksort($chain, SORT_DESC);
            foreach ($chain as $arr) {
                $commands = array_merge($commands, $arr);
            }
        }

        return $commands;
    }

    /**
     * Helper method is command a member of chain.
     * 
     * @param Command $command
     * @param string  $channel
     * 
     * @return bool
     */
    public function isMember(Command $command, $channel)
    {
        foreach ($this->chains[$channel] as $arr) {
            foreach ($arr as $member) {
                if ($member->getName() === $command->getName()) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Returns chain master command.
     *
     * @param string $channel
     *
     * @return Command|null
     */
    public function getMaster($channel)
    {
        return isset($this->masterCommands[$channel]) ? $this->masterCommands[$channel] : null;
    }

    /**
     * Return channel of current command.
     *
     * @param Command $command
     * @return string|null
     */
    public function getChannel(Command $command)
    {
        foreach ($this->masterCommands as $channel => $masterCommand) {
            if ($masterCommand->getName() === $command->getName()) {
                return $channel;
            }
        }

        foreach ($this->chains as $channel => $chain) {
            foreach ($chain as $arr) {
                foreach ($arr as $member) {
                    if ($member->getName() === $command->getName()) {
                        return $channel;
                    }
                }
            }
        }

        return null;
    }
}