<?php

namespace ChainCommandBundle\Tests\Unit;

use ChainCommandBundle\Chain\Chain;
use ChainCommandBundle\Manager\ChainCommandManager;

use ChainCommandBundle\Tests\TestMasterCommand;
use ChainCommandBundle\Tests\TestMemberCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChainCommandManagerTest extends KernelTestCase
{
    const MASTER_NAME = 'namespace:master-name';
    const MEMBER_NAME = 'namespace:member-name';
    const CHANNEL_NAME = 'test';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ChainCommandManager
     */
    private $manager;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
        $this->manager = $this->container->get('chaincommandbundle.manager');
    }

    public function testRunMasterCommand()
    {
        list($master, $member) = $this->registerCommands();

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->manager->runMasterCommand($master, self::CHANNEL_NAME, $input, $output);

        $msg = 'master execute called';

        $outputMsg = $output->fetch();
        $regexp = sprintf('/%s/', $msg);
        $this->assertRegExp($regexp, $outputMsg);

        list($lines, $count) = $this->getLogFile();

        $msgArr = [
            $msg,
            sprintf(preg_quote(ChainCommandManager::MSG_EXECUTE_MASTER), self::MASTER_NAME),
            sprintf(preg_quote(ChainCommandManager::MSG_CHAIN_MEMBER), self::MASTER_NAME, self::MEMBER_NAME),
            sprintf(preg_quote(ChainCommandManager::MSG_START_CHAIN), self::MASTER_NAME),
        ];

        foreach ($msgArr as $key => $msg) {
            $regexp = sprintf('/%s/', $msg);
            $this->assertRegExp($regexp, $lines[$count-$key-1]);
        }
    }

    public function testRunMasterCommandNegative()
    {
        list($master, $member) = $this->registerCommands();

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->manager->runMasterCommand($member, self::CHANNEL_NAME, $input, $output);

        $outputMsg = $output->fetch();
        $regexp = sprintf('/%s/', sprintf(ChainCommandManager::MSG_NOT_MASTER, self::MEMBER_NAME, self::MASTER_NAME));
        $this->assertRegExp($regexp, $outputMsg);
    }

    public function testRunChainCommands()
    {
        list($master, $member) = $this->registerCommands();

        $output = new BufferedOutput();

        $this->manager->runChainCommands($master, self::CHANNEL_NAME, $output);

        $msg = 'member execute called';

        $outputMsg = $output->fetch();
        $regexp = sprintf('/%s/', $msg);
        $this->assertRegExp($regexp, $outputMsg);

        list($lines, $count) = $this->getLogFile();

        $msgArr = [
            sprintf(preg_quote(ChainCommandManager::MSG_FINISH_CHAIN), self::MASTER_NAME),
            $msg,
            sprintf(preg_quote(ChainCommandManager::MSG_EXECUTE_MEMBERS), self::MASTER_NAME),
        ];

        foreach ($msgArr as $key => $msg) {
            $regexp = sprintf('/%s/', $msg);
            $this->assertRegExp($regexp, $lines[$count-$key-1]);
        }
    }

    /**
     * @return array
     */
    protected function registerCommands()
    {
        $application = new Application();
        $application->add(new TestMasterCommand());
        $application->add(new TestMemberCommand());

        $master = $application->find(self::MASTER_NAME);
        $member = $application->find(self::MEMBER_NAME);

        /** @var Chain $chain */
        $chain = $this->container->get('chaincommandbundle.chain');
        $chain->registerCommand($master, true, self::CHANNEL_NAME);
        $chain->registerCommand($member, false, self::CHANNEL_NAME);

        return [$master, $member];
    }

    /**
     * @return array
     */
    protected function getLogFile()
    {
        $pathToLog = sprintf(
            '%s/%s',
            $this->container->getParameter('kernel.logs_dir'),
            $this->container->getParameter('command_chain_log_file')
        );

        $file = file($pathToLog);
        return [file($pathToLog), count($file)];
    }
}