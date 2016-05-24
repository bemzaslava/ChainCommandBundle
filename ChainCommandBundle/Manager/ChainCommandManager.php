<?php

namespace ChainCommandBundle\Manager;

use ChainCommandBundle\Chain\Chain;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ChainCommandManager
{
    const MSG_START_CHAIN = '%s is a master command of a command chain that has registered member commands';
    const MSG_CHAIN_MEMBER = '%s registered as a member of %s command chain';
    const MSG_EXECUTE_MASTER = 'Executing %s command itself first:';
    const MSG_EXECUTE_MEMBERS = 'Executing %s chain members:';
    const MSG_FINISH_CHAIN = 'Execution of %s chain completed.';
    const MSG_NOT_MASTER = 'Error: %s command is a member of %s command chain and cannot be executed on its own.';

    /**
     * @var Chain
     */
    protected $chain;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CommandSubscriber constructor.
     *
     * @param Chain           $chain
     * @param LoggerInterface $logger
     */
    public function __construct(Chain $chain, LoggerInterface $logger)
    {
        $this->chain = $chain;
        $this->logger = $logger;
    }

    /**
     * Getter for chain.
     * 
     * @return Chain
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Run chain from master.
     * 
     * @param Command         $command
     * @param string          $channel
     * @param InputInterface  $input
     * @param OutputInterface $output
     * 
     * @return bool
     */
    public function runMasterCommand(
        Command $command,
        $channel,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $master = $this->getChain()->getMaster($channel);
        if ($master && $master->getName() === $command->getName()) {
            $this->logger->info(sprintf(self::MSG_START_CHAIN, $command->getName()));

            $members = $this->chain->getMembers($channel);
            foreach ($members as $member) {
                /** @var Command $member */
                $this->logger->info(sprintf(self::MSG_CHAIN_MEMBER, $command->getName(), $member->getName()));
            }

            $this->logger->info(sprintf(self::MSG_EXECUTE_MASTER, $command->getName()));

            $this->doRun($command, $input, $output);

            return true;
        } else {
            $output->writeln(
                sprintf(
                    self::MSG_NOT_MASTER,
                    $command->getName(),
                    $master->getName()
                )
            );
            
            return false;
        }
    }

    /**
     * Run rest of the chain.
     * 
     * @param Command         $command
     * @param string          $channel
     * @param OutputInterface $output
     * 
     * @return bool
     */
    public function runChainCommands(
        Command $command,
        $channel,
        OutputInterface $output
    )
    {
        $member = $this->getChain()->isMember($command, $channel);
        if ($member) {
            return false;
        }

        $chainCommands = $this->chain->getMembers($channel);
        if (count($chainCommands)) {
            $this->logger->info(sprintf(self::MSG_EXECUTE_MEMBERS, $command->getName()));
            foreach ($chainCommands as $chainCommand) {
                /** @var Command $chainCommand */
                $this->doRun($chainCommand, new ArrayInput([]), $output);
            }
        }

        $this->logger->info(sprintf(self::MSG_FINISH_CHAIN, $command->getName()));
        
        return true;
    }

    /**
     * Replace default output to BufferedOutput for logging and console output together.
     *
     * @param Command         $command
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    protected function doRun(Command $command, InputInterface $input, OutputInterface $output)
    {
        $bufferedOutput = new BufferedOutput();
        $command->run($input, $bufferedOutput);

        $message = trim($bufferedOutput->fetch());
        $this->logger->info($message);
        $output->writeln($message);
    }
}