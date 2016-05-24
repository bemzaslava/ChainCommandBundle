<?php

namespace ChainCommandBundle\Tests\Func;

use ChainCommandBundle\Chain\Chain;
use ChainCommandBundle\Tests\TestMasterCommand;
use ChainCommandBundle\Tests\TestMemberCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ChainCommandTest extends KernelTestCase
{
    const MASTER_NAME = 'namespace:master-name';
    const MEMBER_NAME = 'namespace:member-name';
    const CHANNEL_NAME = 'test';

    protected $application;
    protected $container;
    protected $output;

    protected function setUp()
    {
        static::bootKernel();
        $this->container = static::$kernel->getContainer();

        $this->application = new Console\Application(static::$kernel);
        $this->application->setAutoExit(false);
        $master = new TestMasterCommand();
        $member = new TestMemberCommand();
        $this->application->add($master);
        $this->application->add($member);

        /** @var Chain $chain */
        $chain = $this->container->get('chaincommandbundle.chain');
        $chain->registerCommand($master, true, self::CHANNEL_NAME);
        $chain->registerCommand($member, false, self::CHANNEL_NAME);

        $this->output = new BufferedOutput();
        $this->application->run(new ArgvInput([
            './bin/console',
            self::MASTER_NAME,
        ]), $this->output);
    }

    public function testGetOutput()
    {
        $output = $this->output->fetch();
        $equals = <<<EOT
master execute called
member execute called

EOT;
        $this->assertEquals($equals, $output);
    }
}