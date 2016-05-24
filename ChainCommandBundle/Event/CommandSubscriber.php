<?php

namespace ChainCommandBundle\Event;

use ChainCommandBundle\Manager\ChainCommandManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class CommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var ChainCommandManager
     */
    protected $manager;

    /**
     * CommandSubscriber constructor.
     * 
     * @param ChainCommandManager $manager
     */
    public function __construct(ChainCommandManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    /**
     * Before command event.
     * 
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();
        $output = $event->getOutput();

        $channel = $this->manager->getChain()->getChannel($command);
        if (null === $channel) {
            return;
        }
        
        $this->manager->runMasterCommand($command, $channel, $input, $output);

        $event->disableCommand();
    }

    /**
     * After command event.
     * 
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        $output = $event->getOutput();
        
        $channel = $this->manager->getChain()->getChannel($command);
        if (null === $channel) {
            return;
        }
        
        $this->manager->runChainCommands($command, $channel, $output);
    }
}